<?php

namespace App\Http\Controllers\AiAssistant;

use App\Http\Controllers\Controller;
use App\Services\AiAssistant\PatientAuthenticator;
use App\Services\AiAssistant\PureMedApiClient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Conversational booking assistant.
 *
 * The whole conversation runs through ONE JSON endpoint (converse) so the page
 * never reloads. The conversation state lives in the Laravel session, which
 * keeps the browser free of business logic - chat input and voice input reach
 * exactly the same code path.
 *
 * PureMed remains the source of truth: registration, doctors, appointment
 * types, slots and the booking itself are all the existing v3 APIs.
 */
class ChatController extends Controller
{
    private const SESSION_KEY = 'ai_assistant.conversation';

    /** Chip value that reveals the next page of choices. */
    private const MORE = '__more__';

    /** Steps that collect patient details, in order. */
    private const REGISTRATION_STEPS = [
        'first_name',
        'last_name',
        'mobile_no',
        'birth_date',
        'email',
        'gender',
    ];

    /** The slot list shows German weekdays, so accept German months too. */
    private const GERMAN_MONTHS = [
        1 => 'januar', 2 => 'februar', 3 => 'märz', 4 => 'april', 5 => 'mai', 6 => 'juni',
        7 => 'juli', 8 => 'august', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'dezember',
    ];

    /** Steps with no cards - a failed answer re-asks itself. */
    private const FREE_TEXT_STEPS = [
        'first_name',
        'last_name',
        'mobile_no',
        'birth_date',
        'email',
    ];

    public function index()
    {
        return view('ai-assistant.index', [
            'pageTitle' => 'PureMed AI Assistant',
        ]);
    }

    /**
     * Single entry point for every patient turn - typed or spoken.
     */
    public function converse(Request $request, PureMedApiClient $client, PatientAuthenticator $authenticator): JsonResponse
    {
        $request->validate([
            'text' => ['nullable', 'string', 'max:500'],
            'choice' => ['nullable', 'array'],
            'choice.type' => ['nullable', 'string', 'max:40'],
            'choice.value' => ['nullable', 'string', 'max:120'],
            'start' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'in:voice,text'],
        ]);

        $state = $this->state();
        $replies = [];
        $stepBefore = $state['step'];
        $starting = $request->boolean('start');

        if ($starting) {
            // Open on intent, not on a field - the patient says what they want
            // before the assistant asks for anything.
            if ($state['step'] === 'intent' && empty($state['patient'])) {
                $replies[] = $this->say("Hi, I'm your PureMed Assistant.");
            } else {
                $replies[] = $this->say("Welcome back. Let's pick up where we left off.");
            }
        } else {
            $replies = $this->handleAnswer($request, $state, $client, $authenticator);
        }

        // "Show more" only extends the chip list; repeating the question under it
        // would read like the assistant forgot it had asked.
        $suppressPrompt = !empty($state['_no_prompt']);
        unset($state['_no_prompt']);

        // A new question starts from the first page of chips again.
        if ($state['step'] !== $stepBefore) {
            $state['chip_page'] = 0;
        }

        $this->save($state);

        $prompt = $this->promptFor($state);

        // On a free-text step the re-ask message already contains the question,
        // so repeating the prompt would sound robotic. Steps that show chips
        // always re-render the prompt so the choices come back.
        $rejected = !$starting && $replies && $state['step'] === $stepBefore
            && in_array($state['step'], self::FREE_TEXT_STEPS, true);

        // Once the booking is done the confirmation card and the closing
        // question belong to that turn only - repeating them on every further
        // message stacks duplicate cards down the chat.
        $alreadyDone = $state['step'] === 'done' && $stepBefore === 'done' && !$starting;

        if (!$rejected && !$alreadyDone && !$suppressPrompt) {
            $replies[] = $this->say($prompt['text']);
        }

        return response()->json([
            'messages' => $replies,
            'options' => $alreadyDone ? null : ($prompt['options'] ?? null),
            'input' => $prompt['input'] ?? ['enabled' => true, 'placeholder' => 'Message PureMed Assistant'],
            // What the browser should narrate WHILE the next answer is being
            // processed. Sent ahead of time because the API calls happen before
            // the next response exists - otherwise the status would appear
            // after the wait instead of during it.
            'pending' => $this->pendingFor($state['step']),
            'step' => $state['step'],
            'done' => $state['step'] === 'done',
        ]);
    }

    public function reset(): JsonResponse
    {
        session()->forget(self::SESSION_KEY);

        $state = $this->state();
        $prompt = $this->promptFor($state);

        return response()->json([
            'messages' => [
                // The header button clears the patient too, so be explicit that
                // details will be collected from scratch.
                $this->say("Starting fresh for a new patient. Hi, I'm your PureMed Assistant."),
                $this->say($prompt['text']),
            ],
            'options' => $prompt['options'] ?? null,
            'input' => $prompt['input'] ?? $this->input(),
            'pending' => $this->pendingFor($state['step']),
            'step' => $state['step'],
            'done' => false,
        ]);
    }

    /* -----------------------------------------------------------------
    |  Conversation
    ------------------------------------------------------------------*/

    /**
     * Validate the patient's answer for the current step and advance.
     *
     * @return array<int, array> assistant messages to show before the next question
     */
    private function handleAnswer(Request $request, array &$state, PureMedApiClient $client, PatientAuthenticator $authenticator): array
    {
        $text = trim((string) $request->input('text', ''));
        $choiceValue = (string) $request->input('choice.value', '');

        if ($text === '' && $choiceValue === '') {
            return [];
        }

        // "Show more" just reveals the next page of chips for the same question.
        if ($choiceValue === self::MORE || $this->wantsMore($text)) {
            $state['chip_page'] = ($state['chip_page'] ?? 0) + 1;
            $state['_no_prompt'] = true;

            return [];
        }

        // Handled server side as well as in the browser, so a spoken "could you
        // start over please" works and not just the exact phrase.
        if ($this->wantsRestart($text)) {
            // The patient is already registered, so asking for their name and
            // date of birth again would be rude. Keep who they are and only
            // clear what they are booking. The header button is the way to
            // hand the screen to a different patient.
            if ($state['patient_id'] && $state['token'] && $state['doctors']) {
                $firstName = $state['patient']['first_name'] ?? null;

                $state = array_merge($state, [
                    'doctor' => null,
                    'appointment_types' => [],
                    'appointment_type' => null,
                    'slots' => [],
                    'slot_date' => null,
                    'slot' => null,
                    'appointment' => null,
                    'step' => 'doctor',
                ]);

                return [$this->say($firstName
                    ? 'Of course ' . $firstName . " - let's book another appointment. I still have your details."
                    : "Of course - let's book another appointment. I still have your details.")];
            }

            $state = $this->freshState();

            return [$this->say("Sure - let's start a new booking.")];
        }

        // Patients correct themselves a moment too late ("no, it's
        // divya@mail.com") once the assistant has already moved on. An email
        // address is unmistakable, so accept it as a correction while details
        // are still being collected - after registration the record exists in
        // PureMed and changing it here would be a lie.
        if (!empty($state['patient']['email'])
            && $state['step'] !== 'email'
            && in_array($state['step'], self::REGISTRATION_STEPS, true)) {
            $corrected = $this->cleanEmail($text);

            if ($corrected) {
                if ($corrected === $state['patient']['email']) {
                    return [$this->say('No problem - I already have ' . $corrected . ' on file.')];
                }

                $state['patient']['email'] = $corrected;

                return [$this->say("Thanks, I've updated your email to " . $corrected . ".")];
            }
        }

        // Asking to see or cancel appointments works from any point once we know
        // who the patient is - that is how people ask, rather than navigating.
        if ($state['patient_id'] && $state['token']
            && !in_array($state['step'], ['cancel_select', 'cancel_confirm'], true)) {
            if ($this->wantsAppointmentList($text)) {
                return $this->loadAppointmentList($state, $client);
            }

            if ($this->wantsCancel($text)) {
                return $this->loadCancellableAppointments($state, $client);
            }
        }

        // Global escape hatches. Without these a doctor with no free slots
        // would trap the patient in the appointment-type step forever.
        if ($state['doctors'] && $this->wantsAnotherDoctor($choiceValue, $text)) {
            $state['doctor'] = null;
            $state['appointment_type'] = null;
            $state['slots'] = [];
            $state['slot_date'] = null;
            $state['slot'] = null;
            $state['step'] = 'doctor';

            return [$this->say('Sure, let me show you the doctors again.')];
        }

        switch ($state['step']) {
            case 'intent':
                // Cancelling is checked first: "cancel my appointments" reads as
                // a list request to the wording below, but the intent is clearly
                // to cancel.
                if ($this->wantsCancel($text) || $choiceValue === 'cancel') {
                    $state['goal'] = 'cancel';
                    $state['step'] = 'first_name';

                    return [$this->say("Of course. I'll just need a few details to find your booking.")];
                }

                if ($this->wantsAppointmentList($text) || $choiceValue === 'list') {
                    $state['goal'] = 'list';
                    $state['step'] = 'first_name';

                    return [$this->say("Happy to. I'll just need a few details to look them up.")];
                }

                if ($this->wantsBooking($text) || $choiceValue === 'book') {
                    $state['goal'] = 'book';
                    $state['step'] = 'first_name';

                    return [$this->say("Sure, I'd be happy to help with that.")];
                }

                // Deliberately not a list of features - the patient says what
                // they need and the assistant works it out.
                return [$this->say("Sorry, I didn't quite catch that. Tell me what you need - for example, \"I'd like to book an appointment\".")];

            case 'first_name':
            case 'last_name':
                $name = $this->cleanName($text);
                if (!$name) {
                    return [$this->say("Sorry, I didn't catch that. Could you tell me your " . str_replace('_', ' ', $state['step']) . " again?")];
                }
                $state['patient'][$state['step']] = $name;
                $state['step'] = $this->nextRegistrationStep($state['step']);

                return [];

            case 'mobile_no':
                $mobile = $this->cleanMobile($text);
                if (!$mobile) {
                    return [$this->say("That doesn't look like a phone number I can use. Could you give me your mobile number again, digits only?")];
                }
                $state['patient']['mobile_no'] = $mobile;
                $state['step'] = 'birth_date';

                // Read it back - speech recognition mishears digits, and the
                // patient can only correct what they can see.
                return [$this->say('Got it, ' . $mobile . '.')];

            case 'birth_date':
                $date = $this->parseBirthDate($text);
                if (!$date) {
                    return [$this->say("I couldn't read that as a date. Could you include the year too? For example 27.03.1993 or 5 April 1990.")];
                }
                $state['patient']['birth_date'] = $date->format('Y-m-d');

                // Mobile + date of birth is exactly how the register API decides
                // a patient already exists. Checking here means a returning
                // patient is not asked for details PureMed already holds, and
                // the booking uses the record the practice actually has.
                $existing = $authenticator->authenticate(
                    $state['patient']['mobile_no'],
                    $state['patient']['birth_date']
                );

                if ($existing) {
                    return $this->resumeExistingPatient($state, $existing, $client);
                }

                // Nothing to show or cancel if we have never seen this person.
                if (in_array($state['goal'] ?? 'book', ['cancel', 'list'], true)) {
                    $state['step'] = 'mobile_no';

                    return [$this->say("I couldn't find any records for that mobile number and date of birth. Could you check the number for me?", 'error')];
                }

                $state['step'] = 'email';

                return [];

            case 'email':
                $email = $this->cleanEmail($text);
                if (!$email) {
                    return [$this->say("Hmm, that doesn't look like a valid email address. Could you repeat it, or type it in?")];
                }

                // Speech recognition mangles domains it does not know - "yopmail"
                // comes back as "yup mail" or "of mail" - and the result can
                // still parse into a valid but WRONG address. Read it back
                // before trusting it. Typed answers are already on screen, so
                // they go straight through.
                if ($request->input('source') === 'voice') {
                    $state['pending_email'] = $email;
                    $state['step'] = 'email_confirm';

                    return [];
                }

                $state['patient']['email'] = $email;
                $state['step'] = 'gender';

                return [$this->say('Thanks, I have ' . $email . '.')];

            case 'email_confirm':
                if ($this->saidYes($choiceValue, $text)) {
                    $state['patient']['email'] = $state['pending_email'];
                    $state['pending_email'] = null;
                    $state['step'] = 'gender';

                    return [$this->say('Great, thank you.')];
                }

                // They may simply say it again rather than answer yes or no.
                if ($choiceValue === '' && ($corrected = $this->cleanEmail($text))
                    && $corrected !== $state['pending_email']) {
                    $state['pending_email'] = $corrected;

                    return [];
                }

                // Otherwise it was wrong. Saying it again would most likely be
                // misheard the same way, so hand over to the keyboard.
                $state['pending_email'] = null;
                $state['step'] = 'email';

                return [$this->say("No problem - email addresses are hard to hear correctly. Could you type it in instead?", 'focus')];

            case 'gender':
                $gender = $this->normalizeGender($choiceValue !== '' ? $choiceValue : $text);
                if (!$gender) {
                    return [$this->say("Sorry, I didn't understand. Please answer male or female.")];
                }
                $state['patient']['gender'] = $gender;

                return $this->registerAndLoadDoctors($state, $client, $authenticator);

            case 'doctor':
                $doctor = $this->matchOption($state['doctors'], $choiceValue, $text);
                if (!$doctor) {
                    return [$this->say("I didn't catch which doctor you meant. Please pick one from the list.")];
                }
                $state['doctor'] = $doctor;

                return $this->loadAppointmentTypes($state, $client);

            case 'appointment_type':
                $type = $this->matchOption($state['appointment_types'], $choiceValue, $text);
                if (!$type) {
                    return [$this->say("I didn't catch that one. Please choose an appointment type from the list.")];
                }
                $state['appointment_type'] = $type;

                return $this->loadSlots($state, $client);

            case 'slot_date':
                $date = $this->matchSlotDate($state['slots'], $choiceValue, $text);
                if (!$date) {
                    // They answered with a time rather than a day - find the
                    // soonest day that offers it instead of making them repeat.
                    $slot = $choiceValue === '' ? $this->matchSlotAnyDay($state['slots'], $text) : null;

                    if ($slot) {
                        $state['slot_date'] = $slot['slot_date'];

                        return $this->holdSlot($state, $slot, $client);
                    }

                    return [$this->say("I didn't catch that day. Which of these works for you?")];
                }
                $state['slot_date'] = $date;
                $state['step'] = 'slot_time';

                return [];

            case 'slot_time':
                $slot = $this->matchSlot($state['slots'], $state['slot_date'], $choiceValue, $text);
                if (!$slot) {
                    return [$this->say("I didn't catch that time. Please pick one of the times shown.")];
                }

                return $this->holdSlot($state, $slot, $client);

            case 'confirm':
                if ($this->saidNo($choiceValue, $text)) {
                    $state['slot'] = null;
                    $state['step'] = 'slot_date';

                    return [$this->say("No problem. Let's pick another time.")];
                }

                return $this->book($state, $client);

            case 'cancel_select':
                $appointment = $this->matchAppointment($state['cancellable'], $choiceValue, $text);

                if (!$appointment) {
                    if ($this->saidNo($choiceValue, $text)) {
                        $state['step'] = $state['appointment'] ? 'done' : 'doctor';

                        return [$this->say("No problem, I've left your appointments as they are.")];
                    }

                    return [$this->say("I'm not sure which one you mean. You can tell me the time, like 5:40, or tap one below.")];
                }

                $state['cancel_target'] = $appointment;
                $state['step'] = 'cancel_confirm';

                return [];

            case 'cancel_confirm':
                if (!$this->saidYes($choiceValue, $text)) {
                    $state['cancel_target'] = null;
                    $state['step'] = $state['appointment'] ? 'done' : 'doctor';

                    return [$this->say("I've kept that appointment. Nothing has been cancelled.")];
                }

                return $this->cancelAppointment($state, $client);

            case 'appointments':
                if ($choiceValue === 'past' || $this->wantsPast($text)) {
                    return $this->loadPastAppointments($state, $client);
                }

                if ($choiceValue === 'book' || $this->wantsBooking($text)) {
                    return $this->startBooking($state, $client);
                }

                return [$this->say("I can book a new appointment or cancel one for you - just say which.")];

            case 'cancelled':
                return [$this->say("Anything else? Say 'cancel my appointment' to cancel another, or 'book another' to make a new one.")];

            case 'done':
                // Booking is finished. Answer what was actually asked instead of
                // repeating one paragraph at every question.
                return [$this->say($this->afterBookingReply($text))];
        }

        return [];
    }

    /**
     * The question the assistant asks for the current step.
     */
    private function promptFor(array $state): array
    {
        $patient = $state['patient'] ?? [];

        switch ($state['step']) {
            case 'intent':
                return [
                    'text' => 'How can I help you today?',
                    // One suggestion only - the opening screen is a conversation
                    // starter, not a feature menu. Viewing and cancelling
                    // appointments are reached by asking for them; the values
                    // stay supported above for the contextual chips elsewhere.
                    'options' => $this->options('intent', [
                        ['value' => 'book', 'title' => 'Book an appointment'],
                    ]),
                    'input' => $this->input(),
                ];

            case 'appointments':
                return [
                    'text' => 'Anything else I can help you with?',
                    'options' => [
                        'type' => 'appointments',
                        'summary' => $state['appointment_list'] ?? [],
                        'items' => [
                            ['value' => 'book', 'title' => 'Book an appointment'],
                            ['value' => 'cancel', 'title' => 'Cancel an appointment'],
                            ['value' => 'past', 'title' => 'Past appointments'],
                        ],
                    ],
                    'input' => $this->input(),
                ];

            case 'first_name':
                return ['text' => 'May I know your first name?', 'input' => $this->input()];

            case 'last_name':
                return [
                    'text' => 'Nice to meet you, ' . ($patient['first_name'] ?? '') . ". What's your last name?",
                    'input' => $this->input(),
                ];

            case 'mobile_no':
                return ['text' => 'Thanks. What mobile number can the practice reach you on?', 'input' => $this->input()];

            case 'birth_date':
                return ['text' => 'And your date of birth?', 'input' => $this->input()];

            case 'email':
                return ['text' => "What's the best email address for your confirmation?", 'input' => $this->input()];

            case 'email_confirm':
                return [
                    'text' => 'I heard ' . ($state['pending_email'] ?? '') . ' - is that right?',
                    'options' => $this->options('email_confirm', [
                        ['value' => 'yes', 'title' => "Yes, that's right"],
                        ['value' => 'no', 'title' => "No, I'll type it"],
                    ]),
                    'input' => $this->input(),
                ];

            case 'gender':
                return [
                    'text' => 'Last one - are you male or female?',
                    'options' => $this->options('gender', [
                        ['value' => 'M', 'title' => 'Male'],
                        ['value' => 'W', 'title' => 'Female'],
                    ]),
                    'input' => $this->input(),
                ];

            case 'doctor':
                return [
                    'text' => 'Which doctor would you like to see?',
                    'options' => $this->page('doctor', $this->doctorCards($state['doctors']), $state),
                    'input' => $this->input(),
                ];

            case 'appointment_type':
                return [
                    'text' => 'What do you need the appointment for?',
                    'options' => $this->page('appointment_type', $this->typeCards($state['appointment_types']), $state, [
                        ['value' => '__doctor__', 'title' => 'Different doctor'],
                    ]),
                    'input' => $this->input(),
                ];

            case 'slot_date':
                return [
                    'text' => 'Which day works best for you?',
                    'options' => $this->page('slot_date', $this->dateCards($state['slots']), $state),
                    'input' => $this->input(),
                ];

            case 'slot_time':
                return [
                    'text' => 'Here is what I have free on ' . $state['slot_date'] . '. Which time suits?',
                    'options' => $this->page('slot_time', $this->timeCards($state['slots'], $state['slot_date']), $state),
                    'input' => $this->input(),
                ];

            case 'confirm':
                return [
                    // Say it back the way a receptionist would, then show a small
                    // card underneath for scanning.
                    'text' => $this->confirmSentence($state),
                    'options' => [
                        'type' => 'confirm',
                        'summary' => $this->summary($state),
                        'items' => [
                            ['value' => 'yes', 'title' => 'Yes, book it'],
                            ['value' => 'no', 'title' => 'Pick another time'],
                        ],
                    ],
                    'input' => $this->input(),
                ];

            case 'cancel_select':
                return [
                    'text' => 'Which appointment would you like to cancel?',
                    'options' => $this->options('cancel_select', array_merge(
                        $this->appointmentCards($state['cancellable']),
                        [['value' => 'no', 'title' => 'Keep them all', 'subtitle' => '']]
                    )),
                    'input' => $this->input(),
                ];

            case 'cancel_confirm':
                return [
                    'text' => 'Just to be sure - shall I cancel this appointment? This cannot be undone.',
                    'options' => [
                        'type' => 'confirm',
                        'summary' => $this->cancelSummary($state['cancel_target']),
                        'items' => [
                            ['value' => 'yes', 'title' => 'Yes, cancel it'],
                            ['value' => 'no', 'title' => 'No, keep it'],
                        ],
                    ],
                    'input' => $this->input(),
                ];

            case 'cancelled':
                return [
                    'text' => 'Is there anything else I can help you with?',
                    'input' => $this->input("Say 'book another' to make a new appointment"),
                ];

            case 'done':
                return [
                    'text' => 'Is there anything else I can help you with?',
                    'options' => [
                        'type' => 'booked',
                        'summary' => $state['appointment'],
                        'items' => [],
                    ],
                    'input' => $this->input("Say 'start over' to book another appointment"),
                ];
        }

        return ['text' => 'Sorry, something went wrong. Say "start over" to begin again.', 'input' => $this->input()];
    }

    /* -----------------------------------------------------------------
    |  PureMed calls
    ------------------------------------------------------------------*/

    /**
     * Continue with the patient record PureMed already holds.
     *
     * Nothing is written back: the stored email, name and gender win over what
     * the conversation collected, because the practice record is authoritative
     * and the confirmation mail is sent to that address. The address is read
     * out so the patient can see where the confirmation will go.
     */
    private function resumeExistingPatient(array &$state, array $existing, PureMedApiClient $client): array
    {
        $state['patient_id'] = $existing['patient_id'];
        $state['token'] = $existing['token'];

        foreach (['email', 'gender'] as $field) {
            if (!empty($existing[$field])) {
                $state['patient'][$field] = $existing[$field];
            }
        }

        if (!empty($existing['first_name'])) {
            $state['patient']['first_name'] = $existing['first_name'];
        }

        if (!empty($existing['family_name'])) {
            $state['patient']['last_name'] = $existing['family_name'];
        }

        $doctors = $client->getDoctors($state['token']);

        if (!$doctors['ok'] || empty($doctors['data'])) {
            return [$this->say($this->readableError($doctors, "I found your details, but I couldn't load the doctor list. Please try again in a moment."), 'error')];
        }

        $state['doctors'] = $this->keepFields($doctors['data'], ['id', 'first_name', 'last_name', 'doctor_speciality']);
        $state['step'] = 'doctor';

        $replies = [$this->say('Welcome back, ' . $state['patient']['first_name'] . '! I have your details already.')];

        // Take them straight to what they came for.
        if (($state['goal'] ?? 'book') === 'cancel') {
            return array_merge($replies, $this->loadCancellableAppointments($state, $client));
        }

        if (($state['goal'] ?? 'book') === 'list') {
            return array_merge($replies, $this->loadAppointmentList($state, $client));
        }

        if (!empty($existing['email'])) {
            $replies[] = $this->say("I'll send the confirmation to " . $existing['email'] . '.');
        }

        return $replies;
    }

    private function registerAndLoadDoctors(array &$state, PureMedApiClient $client, PatientAuthenticator $authenticator): array
    {
        $patient = $state['patient'];
        $birthDate = Carbon::parse($patient['birth_date']);

        $payload = [
            'first_name' => $patient['first_name'],
            'family_name' => $patient['last_name'],
            'mobile_no' => $patient['mobile_no'],
            'birth_date' => $birthDate->format('Y-m-d'),
            'email' => $patient['email'],
            'gender' => $patient['gender'],
            'age' => $birthDate->age,
            'country_code' => config('ai-assistant.default_country_code'),
            'postal_code' => config('ai-assistant.default_postal_code'),
            'country' => config('ai-assistant.default_country'),
            'password' => Str::random(12),
            'login_type' => config('ai-assistant.login_type'),
        ];

        $registration = $client->registerPatient($payload);

        // Registration may report the patient already exists; either way we look
        // the record up so a returning patient can keep going.
        $credentials = $authenticator->authenticate($payload['mobile_no'], $payload['birth_date']);

        if (!$credentials) {
            return [$this->say($this->readableError($registration, "I couldn't save your details just now. Could you check your mobile number and date of birth?"), 'error')];
        }

        $state['patient_id'] = $credentials['patient_id'];
        $state['token'] = $credentials['token'];

        $doctors = $client->getDoctors($state['token']);

        if (!$doctors['ok'] || empty($doctors['data'])) {
            return [$this->say($this->readableError($doctors, "I couldn't load the doctor list right now. Please try again in a moment."), 'error')];
        }

        $state['doctors'] = $this->keepFields($doctors['data'], ['id', 'first_name', 'last_name', 'doctor_speciality']);
        $state['step'] = 'doctor';

        return [$this->say('Thanks ' . $patient['first_name'] . ", you're all set.")];
    }

    private function loadAppointmentTypes(array &$state, PureMedApiClient $client): array
    {
        $types = $client->getAppointmentTypes($state['token'], [
            'doctor_id' => $state['doctor']['id'],
        ]);

        if (!$types['ok'] || empty($types['data'])) {
            $state['doctor'] = null;

            return [$this->say($this->readableError($types, "That doctor has no appointment types available. Could you pick a different doctor?"), 'error')];
        }

        $state['appointment_types'] = $this->keepFields($types['data'], ['id', 'name', 'duration', 'description']);
        $state['step'] = 'appointment_type';

        return [$this->say('Good choice - ' . $this->doctorDisplay($state['doctor']) . '.')];
    }

    private function loadSlots(array &$state, PureMedApiClient $client): array
    {
        $slots = $this->fetchSlots($state, $client);

        if (empty($slots)) {
            $state['appointment_type'] = null;

            return [$this->say("I couldn't find any free times for that appointment in the next " . config('ai-assistant.slot_window_days') . " days. You can try a different appointment type, or pick another doctor.", 'error')];
        }

        $state['slots'] = $slots;
        $state['step'] = 'slot_date';

        return [$this->say($state['appointment_type']['name'] . ' it is.')];
    }

    /**
     * Re-check availability at selection time, then move to confirmation.
     *
     * Slots are validated twice: once when the list is built, and again here in
     * case somebody booked the same time while the patient was choosing.
     */
    private function holdSlot(array &$state, array $slot, PureMedApiClient $client): array
    {
        $fresh = $this->fetchSlots($state, $client);
        $stillFree = collect($fresh)->firstWhere('slot_key', $slot['slot_key']);

        if (!$stillFree) {
            $state['slots'] = $fresh;
            $state['slot'] = null;
            $state['step'] = empty($fresh) ? 'appointment_type' : 'slot_date';

            return [$this->say("I'm sorry - that time was just taken while we were talking. Could you pick another one?", 'error')];
        }

        $state['slots'] = $fresh;
        $state['slot'] = $stillFree;
        $state['step'] = 'confirm';

        return [];
    }

    private function book(array &$state, PureMedApiClient $client): array
    {
        $slot = $state['slot'];

        $result = $client->bookAppointment($state['token'], [
            'patient_id' => $state['patient_id'],
            'doctor_id' => $state['doctor']['id'],
            'appointment_type_id' => $state['appointment_type']['id'],
            'appointment_date' => $slot['slot_date'],   // already d.m.Y
            'time_frame' => $slot['time'],
            'time_slot_id' => $slot['time_slot_id'],
        ]);

        if (!$result['ok']) {
            // PureMed rejects double bookings itself - surface its reason and
            // send the patient back to the slot list.
            $state['slot'] = null;
            $state['step'] = 'slot_date';

            return [$this->say($this->readableError($result, "I couldn't confirm that appointment. Please choose another time."), 'error')];
        }

        $appointment = data_get($result['data'], '0', []);

        // Date and Time already say when it is - repeating start_date on the
        // card just gives the patient the same fact three times.
        $state['appointment'] = [
            'id' => data_get($appointment, 'id'),
            'patient' => data_get($appointment, 'patient_name'),
            'doctor' => data_get($appointment, 'doctor_name'),
            'appointment' => data_get($appointment, 'appointment_type_name'),
            'date' => $slot['slot_date'],
            'time' => $slot['time'],
        ];
        $state['step'] = 'done';

        return [
            $this->say("All done! Your appointment is confirmed."),
        ];
    }

    /**
     * Fetch the patient's upcoming appointments so they can pick one to cancel.
     *
     * get-appointment already scopes to the patient_id in the token, so a
     * patient can only ever see - and therefore cancel - their own bookings.
     */
    private function loadCancellableAppointments(array &$state, PureMedApiClient $client): array
    {
        $result = $client->getAppointments($state['token'], [
            'patient_id' => $state['patient_id'],
            // get-appointment only filters out past appointments when this is
            // present; without it the API returns the whole history too.
            'today_date' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $appointments = $result['ok'] ? $this->keepFields((array) $result['data'], [
            'id', 'start_date', 'doctor_name', 'appointment_type_name',
        ]) : [];

        if (empty($appointments)) {
            // Leave the step alone so the patient carries on where they were.
            return [$this->say("You don't have any upcoming appointments to cancel.")];
        }

        $state['cancellable'] = $appointments;
        $state['cancel_target'] = null;
        $state['step'] = 'cancel_select';

        return [$this->say('Of course. Here are your upcoming appointments.')];
    }

    /**
     * Show the patient what they already have booked.
     *
     * Read only - the same get-appointment call the cancel flow uses, scoped to
     * the patient_id in their own token.
     */
    private function loadAppointmentList(array &$state, PureMedApiClient $client): array
    {
        $result = $client->getAppointments($state['token'], [
            'patient_id' => $state['patient_id'],
            // get-appointment only filters out past appointments when this is
            // present; without it the API returns the whole history too.
            'today_date' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $appointments = $result['ok'] ? $this->keepFields((array) $result['data'], [
            'id', 'start_date', 'doctor_name', 'appointment_type_name',
        ]) : [];

        $state['step'] = 'appointments';

        if (empty($appointments)) {
            $state['appointment_list'] = [];

            return [$this->say("You don't have any upcoming appointments at the moment.")];
        }

        $state['cancellable'] = $appointments;
        $state['appointment_list'] = $this->appointmentRows($appointments);

        $next = $appointments[0];

        return [
            $this->say('You have ' . count($appointments) . ' upcoming '
                . (count($appointments) === 1 ? 'appointment' : 'appointments') . '.'),
            // Say the next one out loud - a voice user should not have to read
            // the card to hear what matters most.
            $this->say('The next one is on ' . $this->appointmentLabel($next)
                . ' with ' . $this->doctorDisplay(['first_name' => $next['doctor_name'] ?? '']) . '.'),
        ];
    }

    private function loadPastAppointments(array &$state, PureMedApiClient $client): array
    {
        $result = $client->getAppointmentHistory($state['token'], [
            'patient_id' => $state['patient_id'],
        ]);

        $past = $result['ok'] ? $this->keepFields((array) $result['data'], [
            'id', 'start_date', 'doctor_name', 'appointment_type_name',
        ]) : [];

        $state['step'] = 'appointments';

        if (empty($past)) {
            $state['appointment_list'] = [];

            return [$this->say("I couldn't find any past appointments on your record.")];
        }

        // Most recent first reads better than oldest first.
        $past = array_reverse($past);
        $state['appointment_list'] = $this->appointmentRows($past);

        return [$this->say('You have had ' . count($past) . ' '
            . (count($past) === 1 ? 'appointment' : 'appointments') . ' with us before.')];
    }

    /**
     * Render appointments as "when => what" rows for the summary card.
     *
     * Numbered so two appointments at the same time cannot collide.
     *
     * @return array<string, string>
     */
    private function appointmentRows(array $appointments): array
    {
        $rows = [];

        foreach (array_values($appointments) as $index => $appointment) {
            $label = ($index + 1) . '. ' . $this->appointmentLabel($appointment);
            $rows[$label] = trim(($appointment['appointment_type_name'] ?? '')
                . ' - ' . Str::title($appointment['doctor_name'] ?? ''), ' -');
        }

        return $rows;
    }

    /** Make sure doctors are loaded, then move to doctor selection. */
    private function startBooking(array &$state, PureMedApiClient $client): array
    {
        $state['goal'] = 'book';

        if (!empty($state['doctors'])) {
            $state['step'] = 'doctor';

            return [];
        }

        $doctors = $client->getDoctors($state['token']);

        if (!$doctors['ok'] || empty($doctors['data'])) {
            return [$this->say($this->readableError($doctors, "I couldn't load the doctor list right now. Please try again in a moment."), 'error')];
        }

        $state['doctors'] = $this->keepFields($doctors['data'], ['id', 'first_name', 'last_name', 'doctor_speciality']);
        $state['step'] = 'doctor';

        return [];
    }

    /**
     * Hand the cancellation to PureMed. The API frees the roster time frame and
     * removes the calendar event, so the slot becomes bookable again.
     */
    private function cancelAppointment(array &$state, PureMedApiClient $client): array
    {
        $target = $state['cancel_target'];

        $result = $client->cancelAppointment($state['token'], [
            'patient_id' => $state['patient_id'],
            'appointment_id' => $target['id'],
        ]);

        if (!$result['ok']) {
            $state['step'] = 'cancel_select';

            return [$this->say($this->readableError($result, "I couldn't cancel that appointment. Please contact the practice."), 'error')];
        }

        // If the cancelled one was the booking made in this chat, drop it so the
        // confirmation card does not keep claiming it is still booked.
        if (!empty($state['appointment']['id']) && (int) $state['appointment']['id'] === (int) $target['id']) {
            $state['appointment'] = null;
        }

        $state['cancellable'] = [];
        $state['cancel_target'] = null;
        $state['step'] = 'cancelled';

        return [$this->say('Done - your appointment on ' . $this->appointmentLabel($target) . ' has been cancelled. The time is free for someone else now.')];
    }

    /**
     * @return array<int, array> normalised, bookable slots
     */
    private function fetchSlots(array $state, PureMedApiClient $client): array
    {
        $result = $client->getDoctorSlots($state['token'], $this->buildSlotRequestPayload(
            $state['doctor'],
            $state['appointment_type']
        ));

        return $result['ok'] ? $this->normalizeSlots((array) $result['data']) : [];
    }

    /**
     * get-doctor-slots validates start_date/end_date as date_format:d.m.Y
     * (Api\v3\OptimalAppointmentController::getDoctorSlots).
     */
    private function buildSlotRequestPayload(?array $doctor, ?array $appointmentType): array
    {
        return [
            'doctor_id' => $doctor['id'] ?? null,
            'appointment_type_id' => $appointmentType['id'] ?? null,
            'start_date' => Carbon::today()->format('d.m.Y'),
            'end_date' => Carbon::today()->addDays((int) config('ai-assistant.slot_window_days', 30))->format('d.m.Y'),
            'from_time' => '00:00',
            'to_time' => '23:59',
            'week_day_id' => '1,2,3,4,5,6,7',
        ];
    }

    /**
     * Flatten the date-grouped slot response into one entry per bookable time.
     *
     * `time_slots` and `time_slots_id` are two parallel lists - the id is
     * matched by POSITION, not by time value. book-newtest uses that id to mark
     * the roster time frame as consumed.
     */
    private function normalizeSlots(array $slotGroups): array
    {
        $slots = [];

        foreach ($slotGroups as $group) {
            $slotDate = data_get($group, 'slot_date');
            $weekday = data_get($group, 'weekday');
            $times = array_values((array) data_get($group, 'time_slots', []));
            $ids = array_values((array) data_get($group, 'time_slots_id', []));

            foreach ($times as $index => $time) {
                $id = $ids[$index] ?? null;

                $slots[] = [
                    'slot_key' => $slotDate . '|' . $time . '|' . $id,
                    'slot_date' => $slotDate,
                    'weekday' => $weekday,
                    'time' => $time,
                    'time_slot_id' => $id,
                ];
            }
        }

        return $slots;
    }

    /* -----------------------------------------------------------------
    |  Cards
    ------------------------------------------------------------------*/

    private function doctorCards(array $doctors): array
    {
        return array_map(function ($doctor) {
            $name = $this->doctorName($doctor);
            $speciality = trim((string) ($doctor['doctor_speciality'] ?? ''));

            // Some records just repeat "doctor" or the name as the speciality,
            // which is noise on a chip.
            $generic = in_array(mb_strtolower($speciality), ['', 'doctor', 'arzt', mb_strtolower($name)], true);

            return [
                'value' => (string) $doctor['id'],
                'title' => 'Dr ' . Str::title($name),
                'subtitle' => $generic ? '' : $speciality,
            ];
        }, $doctors);
    }

    private function typeCards(array $types): array
    {
        return array_map(fn ($type) => [
            'value' => (string) $type['id'],
            'title' => $type['name'] ?? 'Appointment',
            'subtitle' => !empty($type['duration']) ? $type['duration'] . ' min' : '',
        ], $types);
    }

    private function dateCards(array $slots): array
    {
        $cards = [];

        foreach ($slots as $slot) {
            $date = $slot['slot_date'];

            if (!isset($cards[$date])) {
                $cards[$date] = [
                    'value' => $date,
                    'title' => $date,
                    'subtitle' => $slot['weekday'] ?? '',
                    'count' => 0,
                ];
            }

            $cards[$date]['count']++;
        }

        return array_values(array_map(static function (array $card) {
            $card['subtitle'] = trim($card['subtitle'] . ' - ' . $card['count'] . ' free', ' -');
            unset($card['count']);

            return $card;
        }, $cards));
    }

    private function timeCards(array $slots, ?string $date): array
    {
        return collect($slots)
            ->where('slot_date', $date)
            ->map(fn ($slot) => [
                'value' => $slot['time'],
                'title' => $slot['time'],
                'subtitle' => '',
            ])
            ->values()
            ->all();
    }

    private function appointmentCards(array $appointments): array
    {
        return array_map(fn ($appointment) => [
            'value' => (string) $appointment['id'],
            'title' => $this->appointmentLabel($appointment),
            'subtitle' => trim(($appointment['appointment_type_name'] ?? '') . ' - ' . ($appointment['doctor_name'] ?? ''), ' -'),
        ], $appointments);
    }

    /** get-appointment returns start_date as Y-m-d H:i:s. */
    private function appointmentLabel(array $appointment): string
    {
        try {
            return Carbon::parse($appointment['start_date'])->format('d.m.Y H:i');
        } catch (\Throwable $exception) {
            return (string) ($appointment['start_date'] ?? '');
        }
    }

    private function cancelSummary(?array $appointment): array
    {
        if (!$appointment) {
            return [];
        }

        return [
            'Appointment' => $appointment['appointment_type_name'] ?? '',
            'Doctor' => $appointment['doctor_name'] ?? '',
            'When' => $this->appointmentLabel($appointment),
        ];
    }

    private function summary(array $state): array
    {
        return [
            'Patient' => trim(($state['patient']['first_name'] ?? '') . ' ' . ($state['patient']['last_name'] ?? '')),
            'Doctor' => $this->doctorDisplay($state['doctor']),
            'Appointment' => $state['appointment_type']['name'] ?? '',
            'Date' => $state['slot']['slot_date'] ?? '',
            'Time' => $state['slot']['time'] ?? '',
        ];
    }

    /* -----------------------------------------------------------------
    |  Matching patient answers (typed or spoken) to a card
    ------------------------------------------------------------------*/

    /**
     * Match a doctor or appointment type from a chip click or free text.
     *
     * People answer with a title ("Dr Albert"), a fragment ("albert"), a
     * sentence ("I'll see Dr Albert") or a position ("the first one"), so all
     * of those resolve rather than only an exact label.
     */
    private function matchOption(array $items, string $choiceValue, string $text): ?array
    {
        if ($choiceValue !== '') {
            $match = collect($items)->firstWhere('id', (int) $choiceValue);
            if ($match) {
                return $match;
            }
        }

        $spoken = $this->normalizeText($text);
        if ($spoken === '') {
            return null;
        }

        $labels = array_map(
            fn ($item) => $this->normalizeText($this->doctorName($item) ?: ($item['name'] ?? '')),
            $items
        );

        // Whole label, either way round.
        foreach ($labels as $index => $label) {
            if ($label !== '' && ($label === $spoken || Str::contains($spoken, $label) || Str::contains($label, $spoken))) {
                return $items[$index];
            }
        }

        // A distinctive word from the label - "dr albert" -> "albert munnar".
        $stop = ['dr', 'doctor', 'the', 'a', 'an', 'i', 'ill', 'will', 'see', 'with', 'want', 'to', 'please', 'for', 'my'];
        $spokenWords = array_diff(explode(' ', $spoken), $stop);

        foreach ($labels as $index => $label) {
            foreach (explode(' ', $label) as $word) {
                if (mb_strlen($word) >= 4 && in_array($word, $spokenWords, true)) {
                    return $items[$index];
                }
            }
        }

        $ordinal = $this->ordinalIndex($spoken, count($items));

        return $ordinal === null ? null : $items[$ordinal];
    }

    /**
     * "first", "the second one", "number 3", "3" -> zero based index.
     */
    private function ordinalIndex(string $spoken, int $count): ?int
    {
        $words = [
            'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5,
            'sixth' => 6, 'seventh' => 7, 'eighth' => 8, 'ninth' => 9, 'tenth' => 10,
            'last' => $count,
        ];

        foreach ($words as $word => $position) {
            if (preg_match('/\b' . $word . '\b/', $spoken)) {
                return $position >= 1 && $position <= $count ? $position - 1 : null;
            }
        }

        if (preg_match('/\b(?:number|option|no)?\s*(\d{1,2})(?:st|nd|rd|th)?\b/', $spoken, $matches)) {
            $position = (int) $matches[1];

            return $position >= 1 && $position <= $count ? $position - 1 : null;
        }

        return null;
    }

    /**
     * Match a spoken or typed day against the offered dates.
     *
     * Patients say "6 August", "Thursday", "tomorrow" or "13" - not
     * "13.08.2026". Rather than guess at the patient's wording, every offered
     * date is expanded into the forms someone might actually use and the answer
     * is looked up among them.
     */
    private function matchSlotDate(array $slots, string $choiceValue, string $text): ?string
    {
        $dates = [];
        foreach ($slots as $slot) {
            $dates[$slot['slot_date']] = $slot['weekday'] ?? null;
        }

        if ($choiceValue !== '' && array_key_exists($choiceValue, $dates)) {
            return $choiceValue;
        }

        $spoken = $this->normalizeText($text);
        if ($spoken === '') {
            return null;
        }

        foreach ($dates as $slotDate => $weekday) {
            if (in_array($spoken, $this->dateAliases($slotDate, $weekday), true)) {
                return $slotDate;
            }
        }

        $keys = array_keys($dates);

        if (preg_match('/\b(earliest|soonest|asap|as soon as possible|first available)\b/', $spoken)) {
            return $keys[0] ?? null;
        }

        $ordinal = $this->ordinalIndex($spoken, count($keys));

        return $ordinal === null ? null : $keys[$ordinal];
    }

    /**
     * "afternoon" or "2 pm" at the day step is really a time preference, so
     * take the soonest day that can satisfy it rather than re-asking.
     */
    private function matchSlotAnyDay(array $slots, string $text): ?array
    {
        foreach (array_unique(array_column($slots, 'slot_date')) as $date) {
            $slot = $this->matchSlot($slots, $date, '', $text);

            if ($slot) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * @return array<int, string> normalised spellings of one offered date
     */
    private function dateAliases(string $slotDate, ?string $weekday): array
    {
        try {
            $date = Carbon::createFromFormat('!d.m.Y', $slotDate);
        } catch (\Throwable $exception) {
            return [$this->normalizeText($slotDate)];
        }

        $aliases = [
            $slotDate,
            $date->format('j.n.Y'),
            $date->format('d.m'),
            $date->format('j.n'),
            $date->format('Y-m-d'),
            $date->format('d/m/Y'),
            $date->format('j F'),
            $date->format('j F Y'),
            $date->format('j M'),
            $date->format('F j'),
            $date->format('M j'),
            $date->format('l'),          // Thursday
            $date->format('D'),          // Thu
            $date->format('j'),          // bare day number
        ];

        // The slot list shows German weekdays, so accept what the patient sees.
        if ($weekday) {
            $aliases[] = $weekday;
        }

        $germanMonth = self::GERMAN_MONTHS[(int) $date->format('n')] ?? null;
        if ($germanMonth) {
            // Accept the umlaut and its ASCII spelling (maerz as well as marz).
            foreach (array_unique([$germanMonth, str_replace(['ä', 'ö', 'ü'], ['ae', 'oe', 'ue'], $germanMonth)]) as $month) {
                $aliases[] = $date->format('j') . ' ' . $month;
                $aliases[] = $date->format('j') . ' ' . $month . ' ' . $date->format('Y');
            }
        }

        if ($date->isToday()) {
            $aliases[] = 'today';
            $aliases[] = 'heute';
        }

        if ($date->isTomorrow()) {
            $aliases[] = 'tomorrow';
            $aliases[] = 'morgen';
        }

        return array_values(array_unique(array_map(fn ($alias) => $this->normalizeText($alias), $aliases)));
    }

    /**
     * Match a spoken or typed time against the offered slots.
     *
     * Every candidate is reduced to four digits, so "10:40", "1040", "10 40",
     * "10 colon 40" and "ten forty" all resolve to the same slot.
     */
    private function matchSlot(array $slots, ?string $date, string $choiceValue, string $text): ?array
    {
        $onDate = array_values(array_filter($slots, fn ($slot) => $slot['slot_date'] === $date));

        if (!$onDate) {
            return null;
        }

        $wanted = $choiceValue !== ''
            ? $this->normalizeTime($choiceValue)
            : $this->extractTime($text);

        if ($wanted !== '') {
            foreach ($onDate as $slot) {
                if ($this->normalizeTime($slot['time']) === $wanted) {
                    return $slot;
                }
            }
        }

        if ($choiceValue !== '') {
            return null;
        }

        // "earliest", "morning", "afternoon", "anything after 2" - people ask
        // for a part of the day far more often than an exact minute.
        return $this->matchSlotByPreference($onDate, $this->normalizeText($text));
    }

    /**
     * @param  array<int, array>  $onDate  slots for the chosen day, in time order
     */
    private function matchSlotByPreference(array $onDate, string $spoken): ?array
    {
        if ($spoken === '') {
            return null;
        }

        $minutes = fn (array $slot) => (int) substr($this->normalizeTime($slot['time']), 0, 2) * 60
            + (int) substr($this->normalizeTime($slot['time']), 2, 2);

        if (preg_match('/\b(earliest|first available|asap|soonest|as soon as possible)\b/', $spoken)) {
            return $onDate[0];
        }

        if (preg_match('/\b(latest|last)\b/', $spoken)) {
            return $onDate[count($onDate) - 1];
        }

        $windows = [
            'morning' => [0, 719],
            'afternoon' => [720, 1019],
            'evening' => [1020, 1439],
        ];

        foreach ($windows as $word => [$from, $to]) {
            if (preg_match('/\b' . $word . '\b/', $spoken)) {
                foreach ($onDate as $slot) {
                    $at = $minutes($slot);
                    if ($at >= $from && $at <= $to) {
                        return $slot;
                    }
                }

                return null;
            }
        }

        $ordinal = $this->ordinalIndex($spoken, count($onDate));

        return $ordinal === null ? null : $onDate[$ordinal];
    }

    /**
     * Once the booking is done patients ask about other things. Phase 1 only
     * books appointments, so name the specific limit rather than answering
     * every question with the same paragraph - which reads as broken.
     */
    private function afterBookingReply(string $text): string
    {
        $value = mb_strtolower(trim($text));

        $isHistory = preg_match('/\b(how many|my appointments|previous|past|history|booked (so far|till now|until now)|upcoming|list)\b/', $value) === 1;
        $isChange = preg_match('/\b(reschedule|re-schedule|change|move|postpone|cancel|delete)\b/', $value) === 1;

        if ($isChange) {
            return "I can cancel an appointment for you - just say 'cancel my appointment' and I'll show you your upcoming ones. "
                . "To move an appointment, cancel it and book a new time, or contact the practice.";
        }

        if ($isHistory) {
            return "I can't look up your appointment history yet - I can only book new appointments. "
                . "The practice can give you the full list. To book another one, say 'start over'.";
        }

        return "Your appointment is confirmed and the practice has it on file. Booking appointments is all I can help with "
            . "for now, so please contact the practice for anything else. To book another one, say 'start over'.";
    }

    private function wantsRestart(string $text): bool
    {
        $value = mb_strtolower(trim($text));

        return $value !== '' && preg_match('/\b(start over|start again|restart|reset|book another)\b/', $value) === 1;
    }

    private function wantsAnotherDoctor(string $choiceValue, string $text): bool
    {
        if ($choiceValue === '__doctor__') {
            return true;
        }

        $value = mb_strtolower(trim($text));

        return $value !== '' && preg_match('/\b(another|different|change|other)\b.*\bdoctor\b/', $value) === 1;
    }

    /**
     * Match an appointment from a chip click or free text.
     *
     * People identify an appointment by its time ("540", "the 5:40 one"), its
     * day ("13 August"), both, or its position ("the first one") - not by
     * repeating the whole label back.
     */
    private function matchAppointment(array $appointments, string $choiceValue, string $text): ?array
    {
        if ($choiceValue !== '') {
            foreach ($appointments as $appointment) {
                if ((string) $appointment['id'] === $choiceValue) {
                    return $appointment;
                }
            }
        }

        $spoken = $this->normalizeText($text);
        if ($spoken === '' || !$appointments) {
            return null;
        }

        $candidates = $appointments;

        // Narrow by day, if one was mentioned.
        $byDate = array_values(array_filter($candidates, function ($appointment) use ($spoken) {
            foreach ($this->dateAliases($this->appointmentDate($appointment), null) as $alias) {
                if ($alias !== '' && $this->containsPhrase($spoken, $alias)) {
                    return true;
                }
            }

            return false;
        }));

        if ($byDate) {
            $candidates = $byDate;
        }

        // Narrow by time, if one was mentioned.
        $wanted = $this->extractTime($text);

        if ($wanted !== '') {
            $byTime = array_values(array_filter(
                $candidates,
                fn ($appointment) => $this->normalizeTime($this->appointmentTime($appointment)) === $wanted
            ));

            // A time they do not actually have should not silently fall through
            // to some other appointment.
            if (!$byTime) {
                return null;
            }

            $candidates = $byTime;
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        $ordinal = $this->ordinalIndex($spoken, count($appointments));

        return $ordinal === null ? null : $appointments[$ordinal];
    }

    private function appointmentDate(array $appointment): string
    {
        try {
            return Carbon::parse($appointment['start_date'])->format('d.m.Y');
        } catch (\Throwable $exception) {
            return '';
        }
    }

    private function appointmentTime(array $appointment): string
    {
        try {
            return Carbon::parse($appointment['start_date'])->format('H:i');
        } catch (\Throwable $exception) {
            return '';
        }
    }

    /** Whole-word containment, so "5" does not match inside "540". */
    private function containsPhrase(string $haystack, string $needle): bool
    {
        return (bool) preg_match('/(?<!\w)' . preg_quote($needle, '/') . '(?!\w)/u', $haystack);
    }

    private function wantsAppointmentList(string $text): bool
    {
        $value = $this->normalizeText($text);

        return $value !== '' && preg_match(
            '/\b(my appointments|my bookings|show me my appointments|list my appointments|what appointments|which appointments|do i have any appointments|upcoming appointments|all my appointments)\b/',
            $value
        ) === 1;
    }

    private function wantsPast(string $text): bool
    {
        $value = $this->normalizeText($text);

        return $value !== '' && preg_match('/\b(past|previous|history|earlier|old) (appointment|appointments|visits)?\b/', $value) === 1;
    }

    private function wantsBooking(string $text): bool
    {
        $value = mb_strtolower(trim($text));

        return $value !== '' && preg_match('/\b(book|booking|appointment|appointments|termin|schedule|see a doctor|make an appointment)\b/', $value) === 1;
    }

    private function wantsMore(string $text): bool
    {
        $value = $this->normalizeText($text);

        return $value !== '' && preg_match('/\b(show more|more options|more times|see more|other options|anything else available)\b/', $value) === 1;
    }

    private function wantsCancel(string $text): bool
    {
        $value = mb_strtolower(trim($text));

        return $value !== '' && preg_match('/\bcancel\b/', $value) === 1;
    }

    private function saidNo(string $choiceValue, string $text): bool
    {
        $value = mb_strtolower($choiceValue !== '' ? $choiceValue : $text);

        return in_array($value, ['no', 'n', 'nope', 'change', 'another', 'no thanks', 'keep it', 'keep them all'], true);
    }

    /**
     * Cancelling is irreversible, so it needs an explicit yes rather than
     * "anything that is not a no".
     */
    private function saidYes(string $choiceValue, string $text): bool
    {
        $value = mb_strtolower(trim($choiceValue !== '' ? $choiceValue : $text));

        return in_array($value, ['yes', 'y', 'yeah', 'yep', 'confirm', 'ok', 'okay', 'cancel it', 'yes cancel it'], true);
    }

    /* -----------------------------------------------------------------
    |  Input cleaning / validation
    ------------------------------------------------------------------*/

    private function cleanName(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));

        if (!preg_match("/^[\p{L}][\p{L}\-'. ]{1,60}$/u", $value)) {
            return null;
        }

        return Str::title($value);
    }

    /**
     * PureMed stores mobile numbers as digits (spaces and leading zeros
     * stripped). Speech recognition often adds spaces, so strip everything
     * that is not a digit.
     */
    private function cleanMobile(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (strlen($digits) < 6 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    /**
     * Speech recognition writes emails as words ("divya at the rate gmail dot
     * com"). Longer phrases are replaced first - handling " at " before " at
     * the rate " would turn the rest of the phrase into part of the domain and
     * silently store a valid-looking but wrong address.
     */
    private function cleanEmail(string $value): ?string
    {
        $value = mb_strtolower(trim($value));

        $spoken = [
            ' at the rate of ' => '@',
            ' at the rate ' => '@',
            ' at sign ' => '@',
            ' at symbol ' => '@',
            ' underscore ' => '_',
            ' hyphen ' => '-',
            ' dash ' => '-',
            ' dot ' => '.',
            ' at ' => '@',
        ];

        // Providers are often transcribed as two words ("g mail", "hot mail").
        $providers = [
            ' g mail' => ' gmail', ' gee mail' => ' gmail', ' google mail' => ' gmail',
            ' hot mail' => ' hotmail', ' out look' => ' outlook',
            ' yop mail' => ' yopmail', ' yahoo mail' => ' yahoo',
        ];

        $value = str_replace(array_keys($providers), array_values($providers), ' ' . $value . ' ');
        $value = str_replace(array_keys($spoken), array_values($spoken), $value);

        // Close the gaps speech leaves around the separators, but keep other
        // spaces so surrounding words stay separate - stripping every space
        // turns "it's divya@mail.com" into "it'sdivya@mail.com".
        $value = preg_replace('/\s*@\s*/', '@', $value);
        $value = preg_replace('/\s*\.\s*/', '.', $value);

        // Pull the address out of whatever the patient said around it.
        if (!preg_match('/[a-z0-9._%+-]+@[a-z0-9-]+(?:\.[a-z0-9-]+)+/', $value, $matches)) {
            return null;
        }

        $email = rtrim($matches[0], '.');

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Lower case, drop ordinal suffixes and punctuation, collapse whitespace.
     * Used on both sides of a comparison so "06.08.2026" and "6 8 2026" line up.
     */
    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/(\d+)(st|nd|rd|th|\.)\b/u', '$1', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Reduce any spoken or typed time to a four digit HHMM string.
     * Returns '' when the text holds no usable time.
     */
    private function normalizeTime(string $value): string
    {
        $value = mb_strtolower(trim($value));

        // Speech writes the separator as a word, and German uses "Uhr".
        $value = str_replace(['colon', "o'clock", 'oclock', 'uhr', 'hours', 'hrs', 'a.m.', 'p.m.'], [' ', ' 00', ' 00', ' ', ' ', ' ', 'am', 'pm'], $value);

        $isPm = (bool) preg_match('/\bpm\b/', $value);
        $isAm = (bool) preg_match('/\bam\b/', $value);
        $value = preg_replace('/\b(am|pm)\b/', ' ', $value);

        $value = $this->wordsToNumbers($value);

        // "half past ten" / "quarter past ten" / "quarter to eleven"
        if (preg_match('/\b(half|quarter)\s+(past|to)\s+(\d{1,2})\b/', $value, $m)) {
            $hour = (int) $m[3];
            $minute = $m[1] === 'half' ? 30 : 15;

            if ($m[2] === 'to') {
                $minute = 60 - $minute;
                $hour = $hour === 0 ? 23 : $hour - 1;
            }

            return $this->buildTime($hour, $minute, $isAm, $isPm);
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) >= 4) {
            $hour = (int) substr($digits, 0, 2);
            $minute = (int) substr($digits, 2, 2);
        } elseif (strlen($digits) === 3) {
            $hour = (int) substr($digits, 0, 1);
            $minute = (int) substr($digits, 1, 2);
        } else {
            $hour = (int) $digits;
            $minute = 0;
        }

        return $this->buildTime($hour, $minute, $isAm, $isPm);
    }

    /**
     * Pull a time out of a sentence that may hold other numbers.
     *
     * "it's 13 August 540 appointment" must yield 05:40, not 13:54 - so the
     * digits are read as tokens rather than squashed together.
     */
    private function extractTime(string $text): string
    {
        $value = mb_strtolower(trim($text));
        $isPm = (bool) preg_match('/\b(pm|p\.m\.)\b/', $value);
        $isAm = (bool) preg_match('/\b(am|a\.m\.)\b/', $value);

        // "10:40", "10.40"
        if (preg_match('/(?<!\d)(\d{1,2})\s*[:.]\s*(\d{2})(?!\d)/', $value, $matches)) {
            return $this->buildTime((int) $matches[1], (int) $matches[2], $isAm, $isPm);
        }

        // A standalone block like "540" or "1120".
        if (preg_match('/(?<!\d)(\d{3,4})(?!\d)/', $value, $matches)) {
            return $this->normalizeTime($matches[1] . ($isPm ? ' pm' : ($isAm ? ' am' : '')));
        }

        $digits = preg_replace('/\D+/', '', $value);

        // "10 40" or "10 colon 40" - separate tokens that together read as a time.
        if (strlen($digits) === 3 || strlen($digits) === 4) {
            return $this->normalizeTime($digits . ($isPm ? ' pm' : ($isAm ? ' am' : '')));
        }

        // "2 pm", "9 o'clock"
        if ($digits !== '' && strlen($digits) <= 2 && ($isAm || $isPm || preg_match('/\b(o clock|oclock|uhr)\b/', $value))) {
            return $this->buildTime((int) $digits, 0, $isAm, $isPm);
        }

        // No digits at all: "ten forty", "half past nine".
        if ($digits === '') {
            // "first one" / "the second one" are positions, not times - without
            // this the trailing "one" would be read as 01:00.
            if (preg_match('/\b(first|second|third|fourth|fifth|sixth|last|next|earliest|latest|soonest|morning|afternoon|evening)\b/', $value)) {
                return '';
            }

            return $this->normalizeTime($value);
        }

        return '';
    }

    private function buildTime(int $hour, int $minute, bool $isAm, bool $isPm): string
    {
        if ($isPm && $hour < 12) {
            $hour += 12;
        }

        if ($isAm && $hour === 12) {
            $hour = 0;
        }

        if ($hour > 23 || $minute > 59) {
            return '';
        }

        return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . str_pad((string) $minute, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Turn spoken number words into digits so "ten forty" reads as "10 40".
     */
    private function wordsToNumbers(string $value): string
    {
        $units = [
            'zero' => 0, 'oh' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4,
            'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9,
        ];
        $teens = [
            'ten' => 10, 'eleven' => 11, 'twelve' => 12, 'thirteen' => 13, 'fourteen' => 14,
            'fifteen' => 15, 'sixteen' => 16, 'seventeen' => 17, 'eighteen' => 18, 'nineteen' => 19,
        ];
        $tens = ['twenty' => 20, 'thirty' => 30, 'forty' => 40, 'fourty' => 40, 'fifty' => 50];

        // Two word combinations first, so "twenty five" does not become "20 5".
        foreach ($tens as $tenWord => $tenValue) {
            foreach ($units as $unitWord => $unitValue) {
                if ($unitValue === 0) {
                    continue;
                }
                $value = preg_replace('/\b' . $tenWord . '\s+' . $unitWord . '\b/', (string) ($tenValue + $unitValue), $value);
            }
        }

        foreach ($tens + $teens + $units as $word => $number) {
            $value = preg_replace('/\b' . $word . '\b/', (string) $number, $value);
        }

        return $value;
    }

    /**
     * Accept the date formats a patient would actually say or type.
     * A four digit year is required - "5 April" alone is ambiguous and used to
     * silently register patients with age 0.
     */
    private function parseBirthDate(string $value): ?Carbon
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));

        if (!preg_match('/\b(18|19|20)\d{2}\b/', $value)) {
            return null;
        }

        $formats = ['d.m.Y', 'j.n.Y', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'j F Y', 'j M Y', 'F j Y', 'M j Y'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
            } catch (\Throwable $exception) {
                continue;
            }

            if ($date && $this->isPlausibleBirthDate($date)) {
                return $date;
            }
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }

        return $this->isPlausibleBirthDate($date) ? $date : null;
    }

    private function isPlausibleBirthDate(Carbon $date): bool
    {
        return $date->isPast() && $date->year >= 1900 && $date->age <= 120;
    }

    /**
     * patients.gender is char(1) using PureMed's M / W convention.
     */
    private function normalizeGender(string $gender): ?string
    {
        $value = mb_strtolower(trim($gender));

        if (in_array($value, ['w', 'f', 'female', 'woman', 'weiblich', 'frau'], true)) {
            return 'W';
        }

        if (in_array($value, ['m', 'male', 'man', 'maennlich', 'männlich', 'mann'], true)) {
            return 'M';
        }

        return null;
    }

    /* -----------------------------------------------------------------
    |  Helpers
    ------------------------------------------------------------------*/

    private function state(): array
    {
        return array_merge($this->freshState(), session(self::SESSION_KEY, []));
    }

    private function freshState(): array
    {
        return [
            'step' => 'intent',
            'goal' => 'book',
            'chip_page' => 0,
            'patient' => [],
            'pending_email' => null,
            'patient_id' => null,
            'token' => null,
            'doctors' => [],
            'doctor' => null,
            'appointment_types' => [],
            'appointment_type' => null,
            'slots' => [],
            'slot_date' => null,
            'slot' => null,
            'appointment' => null,
            'cancellable' => [],
            'cancel_target' => null,
            'appointment_list' => [],
        ];
    }

    private function save(array $state): void
    {
        session([self::SESSION_KEY => $state]);
    }

    private function nextRegistrationStep(string $current): string
    {
        $index = array_search($current, self::REGISTRATION_STEPS, true);

        return self::REGISTRATION_STEPS[$index + 1] ?? 'gender';
    }

    private function say(string $text, string $kind = 'text'): array
    {
        return ['role' => 'assistant', 'text' => $text, 'kind' => $kind];
    }

    private function options(string $type, array $items): array
    {
        return ['type' => $type, 'items' => $items];
    }

    /**
     * Show a handful of chips and a "Show more" instead of dumping the lot.
     *
     * A chat should offer a few obvious choices, not a scrollable picker with a
     * hundred entries in it.
     *
     * @param  array<int, array>  $extra  chips always pinned after the list
     */
    private function page(string $type, array $items, array $state, array $extra = []): array
    {
        $perPage = max(3, (int) config('ai-assistant.chips_per_page', 6));
        $shown = $perPage * (1 + (int) ($state['chip_page'] ?? 0));
        $visible = array_slice($items, 0, $shown);

        if (count($items) > count($visible)) {
            $visible[] = [
                'value' => self::MORE,
                'title' => 'Show more',
                'subtitle' => (count($items) - count($visible)) . ' more',
            ];
        }

        return $this->options($type, array_merge($visible, $extra));
    }

    private function input(string $placeholder = 'Message PureMed Assistant'): array
    {
        return ['enabled' => true, 'placeholder' => $placeholder];
    }

    /**
     * What the browser narrates while the patient's next answer is processed.
     *
     * Keyed by the step being answered, because that is what determines which
     * PureMed calls are about to run.
     *
     * @return array<int, string>
     */
    private function pendingFor(string $step): array
    {
        return match ($step) {
            'birth_date' => ['Just checking our records...'],
            'gender' => ['Registering your details...', 'Finding available doctors...'],
            'doctor' => ['Loading appointment types...'],
            'appointment_type' => ['Checking available slots...'],
            'slot_time' => ['Verifying that slot is still free...'],
            'confirm' => ['Booking your appointment...'],
            'cancel_select' => ['Fetching that appointment...'],
            'cancel_confirm' => ['Cancelling your appointment...'],
            default => [],
        };
    }

    private function confirmSentence(array $state): string
    {
        return 'So that is ' . ($state['appointment_type']['name'] ?? 'an appointment')
            . ' with ' . $this->doctorDisplay($state['doctor'])
            . ' on ' . ($state['slot']['slot_date'] ?? '')
            . ' at ' . ($state['slot']['time'] ?? '')
            . '. Shall I book it?';
    }

    private function doctorName(?array $doctor): string
    {
        if (!$doctor) {
            return '';
        }

        return trim(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? ''));
    }

    /** How the doctor is spoken and written to the patient. */
    private function doctorDisplay(?array $doctor): string
    {
        $name = $this->doctorName($doctor);

        return $name === '' ? 'the doctor' : 'Dr ' . Str::title($name);
    }

    /**
     * Prefer PureMed's own message so the patient sees the real reason.
     */
    private function readableError(array $result, string $fallback): string
    {
        $message = trim((string) ($result['message'] ?? ''));

        return $message !== '' ? $message : $fallback;
    }

    /**
     * Keep the session small - the API returns far more than the chat needs.
     */
    private function keepFields(iterable $rows, array $fields): array
    {
        $kept = [];

        foreach ($rows as $row) {
            $row = (array) $row;
            $kept[] = array_intersect_key($row, array_flip($fields));
        }

        return $kept;
    }
}
