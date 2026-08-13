<?php

namespace App\Http\Controllers\AiAssistant;

use App\Http\Controllers\Controller;
use App\Services\AiAssistant\NluManager;
use App\Services\AiAssistant\PatientAuthenticator;
use App\Services\AiAssistant\PureMedApiClient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    /**
     * The shape of the conversation flow.
     *
     * Bump this whenever the order of the steps changes, or a step is added or
     * removed. Conversations live for two hours and survive a browser close, so
     * without it a patient who was mid-conversation when the flow changed is
     * resumed onto a step that no longer fits - being asked for a name before
     * the practice has any way to recognise them, for instance.
     *
     * 1: first name -> last name -> mobile -> date of birth -> email -> gender
     * 2: mobile -> date of birth, then registration only if unrecognised
     * 3: mobile and date of birth in one question, full name in one question
     */
    private const FLOW_VERSION = 3;

    /**
     * Where the one-turn undo snapshot lives.
     *
     * Exactly one snapshot is kept - the state as it was before the patient's
     * most recent message - because only that message can be corrected.
     */
    private const UNDO_KEY = 'ai_assistant.undo';

    /**
     * Steps reached only by a completed PureMed write.
     *
     * A booking has been made or an appointment has been cancelled at the
     * practice. Nothing on this side may suggest that can be taken back, so no
     * snapshot survives into or out of these steps.
     */
    private const TERMINAL_STEPS = ['done', 'cancelled'];

    /** Chip value that reveals the next page of choices. */
    private const MORE = '__more__';

    /**
     * Shortest and longest run of digits accepted as a mobile number.
     *
     * The register API itself sets no length rule, so these are the assistant's
     * own: four digits or fewer is a mis-heard fragment, not a phone number.
     */
    private const MOBILE_MIN_DIGITS = 5;
    private const MOBILE_MAX_DIGITS = 15;

    /**
     * Words that mean the answer is a request or a pleasantry rather than a
     * name. Deliberately excludes name particles such as van, der, de and la.
     */
    private const NOT_NAME_WORDS = [
        'book', 'booking', 'booked', 'appointment', 'appointments', 'cancel', 'reschedule',
        'doctor', 'dr', 'want', 'wants', 'need', 'needs', 'like', 'please', 'yes', 'no',
        'yeah', 'yep', 'nope', 'thanks', 'thank', 'hello', 'hi', 'hey', 'today', 'tomorrow',
        'morning', 'afternoon', 'evening', 'slot', 'slots', 'time', 'date', 'change', 'help',
        'and', 'for', 'with', 'about', 'the', 'you', 'your', 'me', 'we', 'they', 'checkup',
        'consultation', 'start', 'over', 'again', 'sorry', 'ok', 'okay',
    ];

    /**
     * Steps that collect patient details, in the order they are asked for.
     *
     * Mobile and date of birth come first because together they are the only
     * thing needed to recognise a patient the practice already has - and they
     * are the practice's own duplicate key too (GeneralTrait::
     * _checkDuplicationPatient compares birth_date and mobile_no). Asking them
     * first means anyone who gives up before registering has left no name or
     * email behind.
     *
     * The two are asked for in one question and the two names in another, so a
     * returning patient is recognised from a single answer and a new one
     * registers in four: mobile and date of birth, full name, email, gender.
     * The steps stay separate here because each still has its own validator and
     * its own question for when only half an answer arrives. The fields are all
     * required by the register API and are collected unchanged.
     */
    private const REGISTRATION_STEPS = [
        'mobile_no',
        'birth_date',
        'first_name',
        'last_name',
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
    public function converse(
        Request $request,
        PureMedApiClient $client,
        PatientAuthenticator $authenticator,
        NluManager $grok
    ): JsonResponse {
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

        $text = trim((string) $request->input('text', ''));
        $choiceValue = (string) $request->input('choice.value', '');
        $source = (string) $request->input('source', '');

        if ($starting) {
            // Open on intent, not on a field - the patient says what they want
            // before the assistant asks for anything.
            if ($state['step'] === 'intent' && empty($state['patient'])) {
                $replies[] = $this->say("Hi, I'm your PureMed Assistant.");
            } elseif ($state['step'] === 'closed') {
                // The last conversation was closed politely, so there is
                // nothing to pick up. That step has no question of its own -
                // resuming into it left the patient looking at a greeting with
                // no way forward. Land somewhere that offers them one instead.
                $state['step'] = $state['patient_id'] ? 'appointments' : 'intent';
                $name = $state['patient']['first_name'] ?? null;

                $replies[] = $this->say($name ? 'Welcome back, ' . $name . '.' : 'Welcome back.');
            } else {
                $replies[] = $this->say("Welcome back. Let's pick up where we left off.");
            }
        } else {
            $stateBefore = $state;
            $replies = $this->handleAnswer($text, $choiceValue, $source, $state, $client, $authenticator);

            // The deterministic matcher above is the primary path and has just
            // failed to move the conversation on. Ask Grok what the sentence
            // meant, then run THE SAME matchers again with its interpretation.
            // Grok never resolves anything itself - it only rephrases the
            // patient into something the existing matchers already understand.
            // A handler can answer without moving the conversation on - showing
            // the slot list again, for instance. That is a complete answer, so
            // the LLM must not be asked to interpret the same message a second
            // time: one message, one response path.
            $handledByRules = !empty($state['_handled']);
            unset($state['_handled']);

            if (!$handledByRules
                && $state['step'] === $stepBefore
                && $text !== ''
                && $choiceValue === ''          // a chip selection never reaches the LLM
                && $this->nluEligible($stepBefore)) {

                $nlu = $grok->interpret(
                    $stepBefore,
                    $this->nluOptions($stateBefore),
                    $text,
                    // One line of context: the question the patient is replying
                    // to. Never the transcript, never patient details.
                    $stateBefore['last_assistant'] ?? null,
                    $this->nluContext($stateBefore)
                );
                $retries = $nlu ? $this->nluInput($stepBefore, $nlu) : [];

                foreach ($retries as $index => $retry) {
                    if ($index === 0) {
                        $state = $stateBefore;   // discard the failed attempt cleanly
                        $replies = [];
                    }

                    $before = $state['step'];
                    $replies = array_merge(
                        $replies,
                        $this->handleAnswer($retry, '', $source, $state, $client, $authenticator)
                    );

                    // Stop as soon as a follow-up stops making progress.
                    if ($state['step'] === $before) {
                        break;
                    }
                }

                // The retry replays a normalised phrase such as "Dr Gunnar
                // Gauff", so anything else said in the same breath - "for
                // Friday" - would be lost. Keep it for the slot step.
                if ($retries && empty($state['slot_hint']) && ($hint = $this->slotHint($text))) {
                    $state['slot_hint'] = $hint;
                }
            }
        }

        // "Show more" only extends the chip list; repeating the question under it
        // would read like the assistant forgot it had asked.
        $suppressPrompt = !empty($state['_no_prompt']);
        unset($state['_no_prompt'], $state['_handled']);

        // A new question starts from the first page of chips again.
        if ($state['step'] !== $stepBefore) {
            $state['chip_page'] = 0;
        }

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

        // A step with no question of its own - the closed conversation - must
        // not add an empty bubble.
        if (!$rejected && !$alreadyDone && !$suppressPrompt && trim((string) $prompt['text']) !== '') {
            $replies[] = $this->say($prompt['text']);
        }

        // Remember only the last thing the assistant said, so the next message
        // can be interpreted against the question it answers. Saved here rather
        // than earlier because the closing question is added just above.
        $last = end($replies);
        $state['last_assistant'] = is_array($last) ? ($last['text'] ?? null) : null;

        $this->save($state);
        $this->rememberForEdit($starting, $text, $choiceValue, $source, $stateBefore ?? null, $state);

        return response()->json([
            'messages' => $replies,
            // Whether the browser may offer "edit" on the message just sent.
            // The server decides: the snapshot either exists or it does not.
            'can_edit' => session()->has(self::UNDO_KEY),
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

    /**
     * Correct the message just sent.
     *
     * The patient said something the assistant misread - or, far more often,
     * the microphone did. This restores the conversation to how it was before
     * that message and runs the corrected one as a fresh turn.
     *
     * It rewrites session state and nothing else. No PureMed call is made here,
     * and no snapshot ever spans a booking or a cancellation, so there is
     * nothing to reverse and nothing that could imply a reversal.
     */
    public function edit(
        Request $request,
        PureMedApiClient $client,
        PatientAuthenticator $authenticator,
        NluManager $grok
    ): JsonResponse {
        $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'source' => ['nullable', 'string', 'in:voice,text'],
        ]);

        $snapshot = session(self::UNDO_KEY);
        $current = $this->state();

        // Checked before the snapshot, so that after a booking or cancellation
        // the patient is told why rather than getting a vague refusal. By this
        // point the snapshot has already been dropped by rule 2 anyway.
        if (in_array($current['step'], self::TERMINAL_STEPS, true)) {
            return $this->editRefused('That is already settled with the practice, so I cannot change this message now. I can help you with something else, though.');
        }

        if (!is_array($snapshot) || !is_array($snapshot['state'] ?? null)) {
            return $this->editRefused('There is nothing to change just now.');
        }

        // Belt and braces: a snapshot reaching back across a completed booking
        // should not exist, and must never be restored if it somehow does.
        if (in_array($snapshot['state']['step'] ?? '', self::TERMINAL_STEPS, true)) {
            return $this->editRefused('That is already settled with the practice, so I cannot change this message now.');
        }

        if (empty($snapshot['state']['appointment']) && !empty($current['appointment'])) {
            return $this->editRefused('That booking is already with the practice, so I cannot change this message now.');
        }

        // Consumed: a correction is itself a new turn, and converse() below
        // decides on its own whether that new turn is editable in its turn.
        session()->forget(self::UNDO_KEY);
        $this->save($snapshot['state']);

        // Deliberately the same path as any other message. Nothing about
        // matching, NLU, availability or booking behaves differently here.
        return $this->converse($request, $client, $authenticator, $grok);
    }

    private function editRefused(string $message): JsonResponse
    {
        session()->forget(self::UNDO_KEY);

        return response()->json(['can_edit' => false, 'error' => $message], 409);
    }

    /**
     * Decide whether the message just handled may still be corrected.
     *
     * Storing no snapshot is what makes an edit impossible, so every rule here
     * is expressed as "forget it". The dangerous case - a turn that booked or
     * cancelled - is caught by the terminal-step checks: the snapshot is
     * dropped at the moment the write succeeds.
     */
    private function rememberForEdit(
        bool $starting,
        string $text,
        string $choiceValue,
        string $source,
        ?array $stateBefore,
        array $stateAfter
    ): void {
        $keep = !$starting
            && $stateBefore !== null
            && $text !== ''
            // A chip is exactly what the patient meant; there is nothing to
            // correct, and re-picking is already one tap.
            && $choiceValue === ''
            && !in_array($stateBefore['step'], self::TERMINAL_STEPS, true)
            && !in_array($stateAfter['step'], self::TERMINAL_STEPS, true)
            // A booking appeared during this turn.
            && !(empty($stateBefore['appointment']) && !empty($stateAfter['appointment']));

        if (!$keep) {
            session()->forget(self::UNDO_KEY);

            return;
        }

        session([self::UNDO_KEY => [
            'state' => $stateBefore,
            'text' => $text,
            'source' => $source,
        ]]);
    }

    public function reset(): JsonResponse
    {
        session()->forget(self::SESSION_KEY);
        session()->forget(self::UNDO_KEY);

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
    private function handleAnswer(string $text, string $choiceValue, string $source, array &$state, PureMedApiClient $client, PatientAuthenticator $authenticator): array
    {
        $text = trim($text);

        if ($text === '' && $choiceValue === '') {
            return [];
        }

        // "Are there more doctors?" is a question about who the practice has,
        // and deserves a real answer. Checked before the "show more" hatch
        // below, which would silently redraw the same chips and say "here are
        // a few more" when there are none.
        if ($choiceValue === '' && $state['step'] === 'doctor' && $this->asksForOtherDoctors($text)) {
            $state['_handled'] = true;

            return $this->answerOtherDoctors($state, $client);
        }

        // "Show more" just reveals the next page of chips for the same question.
        if ($choiceValue === self::MORE || $this->wantsMore($text)) {
            $state['chip_page'] = ($state['chip_page'] ?? 0) + 1;
            $state['_no_prompt'] = true;
            // The rules have answered this; the LLM must not read it as a choice.
            $state['_handled'] = true;

            // A tapped chip speaks for itself. Someone who asked out loud gets
            // an answer - they may not be able to see the chips arrive.
            return $choiceValue === self::MORE ? [] : [$this->say('Of course - here are a few more.')];
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

        // A follow-up about the appointments just listed - "and the second one?"
        // - is answered from the list already held, not by fetching it again.
        // Checked before both hatches below: "when is my next appointment"
        // reads as a request for the whole list, and the bare word
        // "appointment" reads as a new booking.
        if ($state['step'] === 'appointments' && $choiceValue === ''
            && ($answer = $this->answerAppointmentQuestion($state, $text))) {
            $state['_handled'] = true;

            return $answer;
        }

        // Asking to see or cancel appointments works from any point once we know
        // who the patient is - that is how people ask, rather than navigating.
        if ($state['patient_id'] && $state['token']
            && !in_array($state['step'], ['cancel_select', 'cancel_confirm'], true)) {
            // "past appointments" asks for the history, not the upcoming list.
            // Checked first: the list matcher recognises the word too.
            if ($this->wantsPast($text)) {
                return $this->loadPastAppointments($state, $client);
            }

            if ($this->wantsAppointmentList($text)) {
                return $this->loadAppointmentList($state, $client);
            }

            if ($this->wantsCancel($text)) {
                return $this->loadCancellableAppointments($state, $client);
            }
        }

        // Changing their mind about booking at all. Without this it reached the
        // step's own matcher, which could only answer "I didn't catch that one"
        // and ask the same question again, however many times they said it.
        if ($choiceValue === ''
            && !in_array($state['step'], ['done', 'cancelled', 'closed', 'appointments',
                'cancel_select', 'cancel_confirm'], true)
            && $this->abandonsBooking($text)) {
            $state = array_merge($state, [
                'goal' => 'book',
                'doctor' => null,
                'appointment_types' => [],
                'appointment_type' => null,
                'slots' => [],
                'slot_date' => null,
                'slot_window' => null,
                'slot' => null,
                'slot_hint' => null,
                // Who they are is kept: they may well want something else.
                'step' => $state['patient_id'] ? 'appointments' : 'intent',
                '_handled' => true,
            ]);

            $name = $state['patient']['first_name'] ?? null;

            return [$this->say($name
                ? "No problem " . $name . " - I won't book anything."
                : "No problem - I won't book anything.")];
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

            // "another doctor available today" asks two things. Clearing the
            // slots above would throw the day away, so carry it to the slot
            // step - where it is checked against real availability, as always.
            if ($choiceValue === '' && ($hint = $this->slotHint($text))) {
                $state['slot_hint'] = $hint;
            }

            return [$this->say('Sure, let me show you the doctors again.')];
        }

        // The same escape hatch for the reason for the visit. The doctor stays
        // as chosen - only the appointment type and what depends on it is reset.
        if ($state['appointment_types'] && $this->wantsAnotherType($choiceValue, $text)) {
            $state['appointment_type'] = null;
            $state['slots'] = [];
            $state['slot_date'] = null;
            $state['slot'] = null;
            $state['step'] = 'appointment_type';

            if ($choiceValue === '' && ($hint = $this->slotHint($text))) {
                $state['slot_hint'] = $hint;
            }

            return [$this->say('No problem. What do you need the appointment for?')];
        }

        switch ($state['step']) {
            case 'intent':
                // Cancelling is checked first: "cancel my appointments" reads as
                // a list request to the wording below, but the intent is clearly
                // to cancel.
                if ($this->wantsCancel($text) || $choiceValue === 'cancel') {
                    $state['goal'] = 'cancel';
                    $state['step'] = 'mobile_no';

                    return [$this->say("Of course. I'll just need a few details to find your booking.")];
                }

                if ($this->wantsAppointmentList($text) || $choiceValue === 'list') {
                    $state['goal'] = 'list';
                    $state['step'] = 'mobile_no';

                    return [$this->say("Happy to. I'll just need a few details to look them up.")];
                }

                if ($this->wantsBooking($text) || $choiceValue === 'book') {
                    $state['goal'] = 'book';
                    $state['step'] = 'mobile_no';
                    // "I'd like an appointment tomorrow morning" says two things.
                    // Remember the second one until there is real availability
                    // to check it against.
                    $state['slot_hint'] = $this->slotHint($text);

                    return [$this->say("Sure, I'd be happy to help with that.")];
                }

                // Deliberately not a list of features - the patient says what
                // they need and the assistant works it out.
                return [$this->say("Sorry, I didn't quite catch that. Tell me what you need - for example, \"I'd like to book an appointment\".")];

            case 'first_name':
            case 'last_name':
                // The surname question is only ever reached when the surname is
                // the one thing outstanding, so the whole answer is it - "van
                // der Berg" stays one surname rather than being cut in two.
                if ($state['step'] === 'last_name') {
                    $surname = $this->cleanName($text);
                    if (!$surname) {
                        return [$this->notAName($text, 'last name')];
                    }
                    $state['patient']['last_name'] = $surname;
                    $state['step'] = $this->nextRegistrationStep('last_name');

                    return [];
                }

                $parts = $this->splitName($text);
                if (!$parts['first']) {
                    return [$this->notAName($text, 'name')];
                }

                $state['patient']['first_name'] = $parts['first'];

                // Both names in one answer, which is how most people reply to
                // being asked their name. Only ask again when they gave one.
                if ($parts['last']) {
                    $state['patient']['last_name'] = $parts['last'];
                    $state['step'] = $this->nextRegistrationStep('last_name');

                    return [];
                }

                $state['step'] = 'last_name';

                return [];

            case 'mobile_no':
                // One question asks for the number and the date of birth
                // together, so the answer may carry either or both. Splitting
                // decides only which words go to which validator; the
                // validators themselves are the ones that always ran.
                $identity = $this->splitIdentity($text);

                if (!$identity['mobile'] && !$identity['birth_date']) {
                    // Nothing usable at all. Which question to repeat depends on
                    // what is still outstanding.
                    if (!empty($state['patient']['birth_date'])) {
                        return [$this->say("That doesn't look like a valid mobile number. Please tell me the number again, digits only.")];
                    }

                    if ($identity['tried_date']) {
                        return [$this->say("I couldn't read that as a date of birth. Could you give me your mobile number and date of birth again? For example: 664 1234567, 27.03.1993.")];
                    }

                    return [$this->say("Sorry, I didn't catch that. Could you give me your mobile number and your date of birth? For example: 664 1234567, 27.03.1993.")];
                }

                $replies = [];

                if ($identity['mobile']) {
                    $state['patient']['mobile_no'] = $identity['mobile'];

                    // Read it back - speech recognition mishears digits, and the
                    // patient can only correct what they can see.
                    $replies[] = $this->say('Got it, ' . $identity['mobile'] . '.');
                }

                if ($identity['birth_date']) {
                    $state['patient']['birth_date'] = $identity['birth_date']->format('Y-m-d');
                }

                // Ask only for whichever half is still missing. The prompts know
                // what is already answered, so neither is asked for twice.
                if (empty($state['patient']['mobile_no'])) {
                    $state['step'] = 'mobile_no';

                    return $replies;
                }

                if (empty($state['patient']['birth_date'])) {
                    $state['step'] = 'birth_date';

                    // A date was attempted and could not be read. Say so rather
                    // than moving on as if it had never been mentioned.
                    if ($identity['tried_date']) {
                        $replies[] = $this->say("I couldn't read that as a date of birth, though.");
                    }

                    return $replies;
                }

                return array_merge($replies, $this->identifyPatient($state, $client, $authenticator));

            case 'birth_date':
                $date = $this->parseBirthDate($text);
                if (!$date) {
                    return [$this->say("I couldn't read that as a date. Could you include the year too? For example 27.03.1993 or 5 April 1990.")];
                }
                $state['patient']['birth_date'] = $date->format('Y-m-d');

                return $this->identifyPatient($state, $client, $authenticator);

            case 'email':
                $capture = $this->captureEmail($text);

                Log::info('AI assistant email capture', [
                    'source' => $source ?: 'text',
                    'valid' => $capture['valid'],
                    'confidence' => $capture['confidence'],
                    'uncertain' => $capture['uncertain'],
                    // Never the patient's words, and only a masked address.
                    'email' => $this->maskEmail($capture['email']),
                    'suggestion' => $this->maskEmail($capture['suggestion']),
                ]);

                if (!$capture['valid']) {
                    return [$this->say("Hmm, that doesn't look like a valid email address. Could you repeat it, spell it out, or type it in?")];
                }

                // Words were said that did not reach the address. Accepting it
                // would hand the practice an address the patient never gave.
                if ($capture['confidence'] < 0.5) {
                    return [$this->say("I only caught part of that - I heard " . $capture['email']
                        . ". Could you say the whole address again, spell it out, or type it in?")];
                }

                // A domain that looks like a mis-hearing. The address is NOT
                // changed here: the corrected one is put to the patient and
                // only used if they say yes.
                if ($capture['suggestion'] !== null) {
                    $state['pending_email'] = $capture['suggestion'];
                    $state['pending_email_heard'] = $capture['email'];
                    $state['step'] = 'email_confirm';

                    return [];
                }

                // Speech recognition mangles domains it does not know - "yopmail"
                // comes back as "yup mail" or "of mail" - and the result can
                // still parse into a valid but WRONG address. Read it back
                // before trusting it. Typed answers are already on screen, so
                // they go straight through.
                if ($source === 'voice') {
                    $state['pending_email'] = $capture['email'];
                    $state['pending_email_heard'] = null;
                    $state['step'] = 'email_confirm';

                    return [];
                }

                $state['patient']['email'] = $capture['email'];
                $state['step'] = 'gender';

                return [$this->say('Thanks, I have ' . $capture['email'] . '.')];

            case 'email_confirm':
                if ($this->saidYes($choiceValue, $text)) {
                    $state['patient']['email'] = $state['pending_email'];
                    $state['pending_email'] = null;
                    $state['pending_email_heard'] = null;
                    $state['step'] = 'gender';

                    return [$this->say('Great, thank you.')];
                }

                // They may simply say it again rather than answer yes or no.
                if ($choiceValue === '' && ($corrected = $this->cleanEmail($text))
                    && $corrected !== $state['pending_email']) {
                    $state['pending_email'] = $corrected;
                    $state['pending_email_heard'] = null;

                    return [];
                }

                // Otherwise it was wrong. Saying it again would most likely be
                // misheard the same way, so hand over to the keyboard.
                $state['pending_email'] = null;
                $state['pending_email_heard'] = null;
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
                // Questions about who else is available are answered further
                // up, before the "show more" hatch.

                // "the earliest one" is about time, not about which doctor.
                // Remember it for the slot step instead of letting it pick.
                if ($choiceValue === '' && $this->isOnlyTimePreference($text)) {
                    $state['slot_hint'] = $text;

                    return [$this->say("I'll keep that in mind for the times. First, which doctor would you like to see?")];
                }

                $doctor = $this->matchOption($state['doctors'], $choiceValue, $text);
                if (!$doctor) {
                    return [$this->say("I didn't catch which doctor you meant. Please pick one from the list.")];
                }
                $state['doctor'] = $doctor;

                // "Dr Gunnar for Friday" says two things. Keep the second one
                // for the slot step instead of discarding it.
                if ($choiceValue === '' && ($hint = $this->slotHint($text))) {
                    $state['slot_hint'] = $hint;
                }

                return $this->loadAppointmentTypes($state, $client);

            case 'appointment_type':
                if ($choiceValue === '' && $this->isOnlyTimePreference($text)) {
                    $state['slot_hint'] = $text;

                    return [$this->say("I'll remember that for the times. First, what do you need the appointment for?")];
                }

                $type = $this->matchOption($state['appointment_types'], $choiceValue, $text);
                if (!$type) {
                    return [$this->say("I didn't catch that one. Please choose an appointment type from the list.")];
                }
                $state['appointment_type'] = $type;

                if ($choiceValue === '' && ($hint = $this->slotHint($text))) {
                    $state['slot_hint'] = $hint;
                }

                return $this->loadSlots($state, $client);

            case 'slot_date':
                // "I don't want 13 August" names the day it is refusing.
                // Choosing it would be the exact opposite of the request.
                if ($choiceValue === '' && $this->rejectsNamedDay($text)
                    && ($refused = $this->matchSlotDate($state['slots'], '', $text))) {
                    $others = array_values(array_diff($this->availableDates($state['slots']), [$refused]));
                    $state['_handled'] = true;
                    $state['_no_prompt'] = true;

                    if (!$others) {
                        // Saying "here are the days" and showing only the one
                        // they just refused would read as not listening.
                        return [$this->say($refused . ' is the only day I have free for this appointment. '
                            . 'I can try a different appointment type, or another doctor, if you prefer.')];
                    }

                    return [$this->say('Of course - which of these days would suit instead?')];
                }

                if ($choiceValue === '' && $this->wantsSlotList($text)) {
                    return $this->showSlotList($state, $text);
                }

                $date = $this->matchSlotDate($state['slots'], $choiceValue, $text);
                if (!$date) {
                    // Naming a specific day that is not on the list means it is
                    // not available. Quietly booking a different day would be
                    // worse than saying so.
                    if ($this->mentionsSpecificDay($text)) {
                        return [$this->say("I don't have anything free on that day. Here is what I do have.")];
                    }

                    // They gave a preference rather than a day - "morning", "the
                    // earliest". Take them to the soonest day that can satisfy
                    // it, but show the times: a preference is not a choice.
                    $slot = $choiceValue === '' ? $this->matchSlotAnyDay($state['slots'], $text) : null;

                    if ($slot) {
                        $state['slot_date'] = $slot['slot_date'];
                        $state['step'] = 'slot_time';

                        if (preg_match('/\b(morning|afternoon|evening)\b/i', $text, $window)) {
                            $state['slot_window'] = mb_strtolower($window[1]);
                        }

                        // One message that also asks the question.
                        $state['_no_prompt'] = true;

                        return [$this->say('The soonest I have for that is ' . $slot['slot_date']
                            . '. Here are the times - which one suits?')];
                    }

                    return [$this->say("I didn't catch that day. Which of these works for you?")];
                }
                $state['slot_date'] = $date;
                $state['step'] = 'slot_time';

                // "book it for 11 August at 4:30" answers both questions at
                // once. Only a stated clock time is taken: the day number in
                // "11 August" would otherwise read as 11:00 and book an hour
                // the patient never asked for.
                if ($choiceValue === '' && $this->extractTime($text) !== '') {
                    $slot = $this->matchSlot($state['slots'], $date, '', $text);

                    // Unmatched means PureMed has no such time free, so the
                    // times for that day are shown as usual - never a silent
                    // substitution.
                    if ($slot) {
                        $state['slot'] = $slot;
                        $state['step'] = 'confirm';

                        return [];
                    }
                }

                // "11 August morning" narrows the day without choosing for them.
                if ($choiceValue === '' && preg_match('/\b(morning|afternoon|evening)\b/i', $text, $window)) {
                    $state['slot_window'] = mb_strtolower($window[1]);
                }

                return [];

            case 'slot_time':
                // "are there any other times?" and "I don't want this one" both
                // mean the same thing here: show the list again. Neither should
                // reach the LLM or fall through to "I didn't catch that time".
                // "I want another day" is a request for a different DAY, not
                // more times on this one.
                if ($choiceValue === '' && $this->wantsAnotherDay($text)) {
                    $state['slot_date'] = null;
                    $state['slot_window'] = null;
                    $state['slot'] = null;
                    $state['step'] = 'slot_date';
                    $state['_handled'] = true;
                    $state['_no_prompt'] = true;

                    return [$this->say('Of course - which of these days would you prefer?')];
                }

                if ($choiceValue === '' && ($this->wantsSlotList($text) || $this->saidNo('', $text))) {
                    if (preg_match('/\b(other|another|more|else)\b/', $this->normalizeText($text))) {
                        $state['chip_page'] = ($state['chip_page'] ?? 0) + 1;
                        $state['_no_prompt'] = true;
                        $state['_handled'] = true;

                        return [$this->say('Of course, here are more times.')];
                    }

                    return $this->showSlotList($state, $text);
                }

                // "tomorrow morning" while looking at Tuesday names a different
                // day. Without this the day was ignored and "morning" picked a
                // slot on the day already on screen.
                if ($choiceValue === '' && $this->mentionsSpecificDay($text)) {
                    return $this->showSlotList($state, $text);
                }

                // "book for 11" is not a position in the list. If 11:00 is not
                // free, the honest answer is to ask - counting down to whatever
                // sits eleventh would book a time nobody named. Handled here so
                // the LLM is never asked to turn a number into a position.
                if ($choiceValue === '' && ($ask = $this->clarifyAmbiguousNumber($state, $text))) {
                    $state['_handled'] = true;

                    return [$this->say($ask)];
                }

                $slot = $this->matchSlot($state['slots'], $state['slot_date'], $choiceValue, $text, $state['slot_window'] ?? null);
                if (!$slot) {
                    return [$this->say("I didn't catch that time. Please pick one of the times shown.")];
                }

                return $this->holdSlot($state, $slot, $client);

            case 'confirm':
                // Asking to see the times is not a rejection. Keep the doctor,
                // the appointment type and the day - only the pending time goes
                // back on the table.
                if ($choiceValue === '' && $this->wantsSlotList($text)) {
                    return $this->showSlotList($state, $text);
                }

                // "sorry, can you book for 13 August" turns this down AND says
                // what to do instead. Backing out to the day list would throw
                // the day away and make the patient say it a second time.
                if ($choiceValue === '' && !$this->saidYes($choiceValue, $text)) {
                    // "the 11th one" is a position in the list, not the 11th of
                    // the month. Without this the day matcher claimed it first
                    // and quietly moved the appointment to another date.
                    $positional = $this->saysPosition($text);
                    $date = $positional ? null : $this->matchSlotDate($state['slots'], '', $text);
                    $target = $date ?: $state['slot_date'];

                    // A stated time is taken first, so "book 13 August at 9:00"
                    // works whether or not the day itself changed. Only a time
                    // the patient actually said counts - see the day step.
                    $spokenTime = $this->extractTime($text) !== '';
                    $slot = $target && ($spokenTime || $positional)
                        ? $this->matchSlot($state['slots'], $target, '', $text)
                        : null;

                    // "you have taken wrong time I said 17" names the hour in
                    // the middle of a correction. Only the hour is looked up -
                    // a number here is never counted into the list.
                    $hour = $this->singleNumberIn($text);

                    if (!$slot && $target && $hour !== null) {
                        $slot = $this->matchSlotByHour($this->slotsOn($state['slots'], $target), 'at ' . $hour);
                        $spokenTime = $spokenTime || $slot !== null;
                    }

                    if ($slot && $slot !== $state['slot']) {
                        $state['slot_date'] = $target;
                        $state['slot'] = $slot;
                        $state['_handled'] = true;

                        return [];
                    }

                    // Reading the same time back - "you're saying 1400?" - is
                    // not a change and certainly not an unavailable time. Let it
                    // fall through so the confirmation is simply asked again.
                    if ($slot) {
                        $state['_handled'] = true;

                        return [];
                    }

                    // A different day, with no time named: show that day's times.
                    // The same day again is not a change of mind, so it falls
                    // through to the yes/no handling below.
                    if ($date && $date !== $state['slot_date']) {
                        $state['slot'] = null;
                        $state['slot_date'] = $date;
                        $state['step'] = 'slot_time';
                        $state['_handled'] = true;

                        return [];
                    }

                    // They named a day PureMed has nothing on, or a time that is
                    // not free. Saying so beats dropping them back on the list
                    // with no explanation - and never a silent substitution.
                    if (!$date && !$slot && ($this->mentionsSpecificDay($text) || $spokenTime)) {
                        $state['slot'] = null;
                        $state['step'] = 'slot_date';
                        $state['_handled'] = true;

                        return [$this->say("I don't have that free, I'm afraid. Here is what I do have.")];
                    }
                }

                if ($this->saidNo($choiceValue, $text)) {
                    $state['slot'] = null;

                    // "can I change the day" is a different request from "can I
                    // change my time", and only the first one wants the list of
                    // days back.
                    if (($choiceValue === '' && $this->wantsAnotherDay($text)) || empty($state['slot_date'])) {
                        $state['slot_date'] = null;
                        $state['slot_window'] = null;
                        $state['step'] = 'slot_date';

                        return [$this->say('Of course - which day would you prefer?')];
                    }

                    // The button says "Pick another time", so that is what this
                    // does: the other times on the day they already chose.
                    // Sending them back to the days would undo a choice they
                    // never asked to change.
                    $state['step'] = 'slot_time';

                    return [$this->say("No problem. Let's pick another time.")];
                }

                // Booking is irreversible for the patient, so it needs an
                // explicit yes. Anything else leaves the step unchanged, which
                // lets the NLU fallback interpret it and, failing that, re-asks
                // with the buttons.
                if ($this->saidYes($choiceValue, $text)) {
                    return $this->book($state, $client);
                }

                return [];

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
                // "cancel" answering "shall I cancel this appointment?" is a
                // yes. Only here - anywhere else the word STARTS a cancellation
                // rather than confirming one, so it must not become a general
                // agreement. A refusal still wins: "no, don't cancel" keeps it.
                $confirmsCancel = $choiceValue === ''
                    && !$this->saidNo($choiceValue, $text)
                    && preg_match('/\b(cancel|remove|delete)\b/u', $this->normalizeText($text)) === 1;

                if (!$this->saidYes($choiceValue, $text) && !$confirmsCancel) {
                    $state['cancel_target'] = null;
                    $state['rebook_after_cancel'] = false;
                    $state['step'] = $state['appointment'] ? 'done' : 'doctor';

                    return [$this->say("I've kept that appointment. Nothing has been cancelled.")];
                }

                return $this->cancelAppointment($state, $client);

            case 'appointments':
                if ($choiceValue === 'past' || $this->wantsPast($text)) {
                    return $this->loadPastAppointments($state, $client);
                }

                if ($choiceValue === '' && $this->saidNothingElse($text)) {
                    return $this->closeConversation($state);
                }

                if ($choiceValue === '' && !empty($state['last_cancelled'])
                    && $this->wantsSameAgain($text)) {
                    $state['_handled'] = true;

                    return $this->offerSameAgain($state, $client);
                }

                // Follow-up questions about the list are answered further up,
                // before the appointment-list and booking hatches.
                if ($choiceValue === 'book' || $this->wantsBooking($text)) {
                    return $this->startBooking($state, $client);
                }

                return [$this->say("I can book a new appointment or cancel one for you - just say which.")];

            case 'cancelled':
            case 'closed':
                // "Is there anything else?" has just been asked, so a plain
                // "no" is an answer to it. Without this it matched nothing and
                // the same menu was repeated at every further message.
                if ($choiceValue === '' && $this->saidNothingElse($text)) {
                    return $this->closeConversation($state);
                }

                // "Book the same one again" - checked before the general
                // booking hatch, which would start from the doctor list and
                // ask for everything the assistant already knows.
                if ($choiceValue === '' && !empty($state['last_cancelled'])
                    && $this->wantsSameAgain($text)) {
                    $state['_handled'] = true;

                    return $this->offerSameAgain($state, $client);
                }

                if ($this->wantsBooking($text)) {
                    return $this->beginAnotherBooking($state, $client, $text);
                }

                return [$this->say("Anything else? Say 'cancel my appointment' to cancel another, or 'book another' to make a new one.")];

            case 'done':
                if ($choiceValue === '' && $this->saidNothingElse($text)) {
                    return $this->closeConversation($state);
                }

                // "Change the time for this appointment" is neither a new
                // booking nor a question. Checked before wantsBooking, which
                // reads the word "appointment" and would start again from
                // scratch - leaving the original still booked.
                if ($state['appointment'] && $choiceValue === '' && $this->wantsReschedule($text)) {
                    $state['cancel_target'] = $state['appointment'];
                    $state['rebook_after_cancel'] = true;
                    $state['step'] = 'cancel_confirm';
                    $state['_handled'] = true;

                    return [$this->say("I can't move an appointment once it is booked, but I can cancel this one and book you a new time.")];
                }

                // "I want to book an appointment for tomorrow morning" is a new
                // booking, not a question about the finished one. Only the exact
                // phrase "start over" used to be recognised here.
                if ($this->wantsBooking($text)) {
                    return $this->beginAnotherBooking($state, $client, $text);
                }

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
                // Asked as one question, because that is how people answer it.
                // Only narrowed to the given name when the surname is somehow
                // already known.
                return [
                    'text' => empty($patient['last_name'])
                        ? 'May I know your full name?'
                        : 'And your first name?',
                    'input' => $this->input(),
                ];

            case 'last_name':
                return [
                    'text' => 'Nice to meet you, ' . ($patient['first_name'] ?? '') . ". What's your last name?",
                    'input' => $this->input(),
                ];

            case 'mobile_no':
                // The first thing asked now, so it no longer thanks the patient
                // for an answer they have not given yet. Both halves are asked
                // together, and narrowed to the number alone when the date of
                // birth has already been given.
                return [
                    'text' => empty($patient['birth_date'])
                        ? 'What mobile number can the practice reach you on, and your date of birth?'
                        : 'And what mobile number can the practice reach you on?',
                    'input' => $this->input(),
                ];

            case 'birth_date':
                return ['text' => 'And your date of birth?', 'input' => $this->input()];

            case 'email':
                return ['text' => "What's the best email address for your confirmation?", 'input' => $this->input()];

            case 'email_confirm':
                return [
                    // When the domain looked like a mis-hearing, both are shown:
                    // what was heard, and what it is probably meant to be. The
                    // patient decides, not the assistant.
                    'text' => !empty($state['pending_email_heard'])
                        ? 'I heard ' . $state['pending_email_heard'] . ' - did you mean '
                            . ($state['pending_email'] ?? '') . '?'
                        : 'I heard ' . ($state['pending_email'] ?? '') . ' - is that right?',
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
                    'text' => ($state['slot_window'] ?? null)
                        ? 'Here are the ' . $state['slot_window'] . ' times on ' . $state['slot_date'] . '. Which one suits?'
                        : 'Here is what I have free on ' . $state['slot_date'] . '. Which time suits?',
                    'options' => $this->page('slot_time', $this->timeCards($state['slots'], $state['slot_date'], $state['slot_window'] ?? null), $state),
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

            case 'closed':
                // The conversation has been closed politely. Asking "anything
                // else?" again underneath would undo the goodbye - the patient
                // can still type, and every flow is still reachable.
                return [
                    'text' => '',
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

        // The name now comes from the practice's record rather than from the
        // patient, because it is no longer asked for before they are found.
        $known = trim((string) ($state['patient']['first_name'] ?? ''));

        $replies = [$this->say($known !== ''
            ? 'Welcome back, ' . $known . '! I have your details already.'
            : 'Welcome back! I have your details already.')];

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
            // The practice's own rules left nothing bookable - a different
            // thing from a full diary, and worth saying differently. The reason
            // is not guessed at: only the practice knows it.
            $refused = !empty($state['booking_window']['none']);

            $state['appointment_type'] = null;
            // The type was understood; PureMed simply has nothing free. That is
            // a complete answer, and it leaves the step unchanged - without this
            // the LLM would be asked to reinterpret a message the rules already
            // handled, costing a needless call on every unavailable type.
            $state['_handled'] = true;

            return [$this->say($refused
                ? "The practice isn't offering that appointment with " . $this->doctorDisplay($state['doctor'])
                    . ' at the moment. You could try a different appointment type, or another doctor - '
                    . 'the practice can tell you more.'
                : "I couldn't find any free times for that appointment in the next "
                    . config('ai-assistant.slot_window_days') . ' days. You can try a different appointment type, or pick another doctor.',
                'error')];
        }

        $state['slots'] = $slots;
        $state['step'] = 'slot_date';

        $replies = [$this->say($state['appointment_type']['name'] . ' it is.')];

        // The patient mentioned a day or time when they asked to book. Now that
        // PureMed has told us what actually exists, see whether it can be
        // honoured. If not, they simply get the normal list of days.
        $hint = $state['slot_hint'] ?? null;
        $state['slot_hint'] = null;
        $state['slot_window'] = null;

        if ($hint) {
            // "morning" narrows a day that can hold 130 slots to the handful
            // the patient actually meant.
            if (preg_match('/\b(morning|afternoon|evening)\b/i', $hint, $window)) {
                $state['slot_window'] = mb_strtolower($window[1]);
            }

            $date = $this->matchSlotDate($slots, '', $hint);

            if ($date) {
                // Take them to that day's times, but let them choose. Jumping
                // straight to confirmation from a passing remark means the
                // patient never sees what else was available.
                $state['slot_date'] = $date;
                $state['step'] = 'slot_time';

                // The one exception: they named an actual clock time, so they
                // have already chosen.
                if ($this->extractTime($hint) !== '') {
                    $slot = $this->matchSlot($slots, $date, '', $hint);

                    if ($slot) {
                        return array_merge($replies, $this->holdSlot($state, $slot, $client));
                    }
                }
            } elseif ($this->mentionsSpecificDay($hint)) {
                // They asked for a day this doctor does not offer. Say so
                // rather than quietly showing a different one.
                $replies[] = $this->say("I don't have that day for this appointment, though.");
            }
        }

        return $replies;
    }

    /**
     * Re-check availability at selection time, then move to confirmation.
     *
     * Slots are validated twice: once when the list is built, and again here in
     * case somebody booked the same time while the patient was choosing.
     */
    /**
     * Refuse to book anything that is not exactly what is on screen right now.
     *
     * Checked immediately before the booking call, and deliberately not earlier:
     * the slot list is re-fetched when the patient picks a time, but minutes can
     * pass between that and their "yes". Everything here is compared against
     * PureMed's own current availability - never against a remembered list, and
     * never against anything the language model returned.
     *
     * @return array<int, array>|null  the reply to send instead of booking
     */
    private function refuseUnsafeBooking(array &$state, PureMedApiClient $client): ?array
    {
        $slot = $state['slot'] ?? null;
        $doctorId = $state['doctor']['id'] ?? null;
        $typeId = $state['appointment_type']['id'] ?? null;

        // Nothing chosen, or chosen only in part. Sending this would let PureMed
        // decide what the patient meant.
        if (!is_array($slot) || !$doctorId || !$typeId
            || empty($slot['slot_date']) || empty($slot['time']) || !isset($slot['time_slot_id'])) {
            $state['slot'] = null;
            $state['step'] = $state['doctor'] ? 'appointment_type' : 'doctor';

            return [$this->say("Let's start that again - I want to be sure I book the right appointment.", 'error')];
        }

        // The day on the confirmation card and the day of the slot must agree.
        // If they ever disagree, an older selection has survived a change the
        // patient made, and the safe answer is to ask rather than guess.
        if (!empty($state['slot_date']) && $slot['slot_date'] !== $state['slot_date']) {
            Log::warning('AI assistant: booking refused, slot did not match the chosen day', [
                'slot_date' => $slot['slot_date'],
                'state_date' => $state['slot_date'],
            ]);

            $state['slot'] = null;
            $state['step'] = 'slot_date';

            return [$this->say("I want to be sure of the day before I book. Which of these works for you?", 'error')];
        }

        // PureMed's current availability for THIS doctor and THIS appointment
        // type. The slot must still be in it, matched on the key built from the
        // API's own fields - so a slot that was never offered cannot be booked.
        $fresh = $this->fetchSlots($state, $client);
        $stillFree = collect($fresh)->firstWhere('slot_key', $slot['slot_key'] ?? null);

        if (!$stillFree) {
            $state['slots'] = $fresh;
            $state['slot'] = null;
            $state['step'] = empty($fresh) ? 'appointment_type' : 'slot_date';

            return [$this->say("I'm sorry - that time was taken while we were talking. Could you pick another one?", 'error')];
        }

        // Book the row PureMed just gave us, not the copy held in the session.
        $state['slot'] = $stillFree;

        return null;
    }

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
        // The last gate before the only irreversible step in the conversation.
        if ($refusal = $this->refuseUnsafeBooking($state, $client)) {
            return $refusal;
        }

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
            'doctor' => Str::title((string) data_get($appointment, 'doctor_name')),
            'appointment' => data_get($appointment, 'appointment_type_name'),
            'date' => $slot['slot_date'],
            'time' => $slot['time'],
        ];
        // Whether the patient may book again depends on the appointments they
        // hold, and they have just gained one. The remembered window is about
        // the patient as they were a moment ago, so it is thrown away rather
        // than reused for a second booking.
        $state['booking_window'] = null;
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
        // Cancelling is its own subject; the viewing context ends here.
        $state['appointments_context'] = [];
        $state['discussed_appointment'] = 0;

        $result = $client->getAppointments($state['token'], [
            'patient_id' => $state['patient_id'],
            // get-appointment only filters out past appointments when this is
            // present; without it the API returns the whole history too.
            'today_date' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $appointments = $result['ok'] ? $this->keepFields((array) $result['data'], [
            // doctor_id and appointment_type_id are what a rebooking needs:
            // names cannot be sent to the booking API.
            'id', 'start_date', 'doctor_name', 'appointment_type_name',
            'doctor_id', 'appointment_type_id',
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
            // doctor_id and appointment_type_id are what a rebooking needs:
            // names cannot be sent to the booking API.
            'id', 'start_date', 'doctor_name', 'appointment_type_name',
            'doctor_id', 'appointment_type_id',
        ]) : [];

        $state['step'] = 'appointments';

        if (empty($appointments)) {
            $state['appointment_list'] = [];

            return [$this->say("You don't have any upcoming appointments at the moment.")];
        }

        $state['cancellable'] = $appointments;
        $state['appointment_list'] = $this->appointmentRows($appointments);
        // Kept for follow-up questions about this same list.
        $state['appointments_context'] = $appointments;
        // The next one is named below, so that is what has been discussed.
        $state['discussed_appointment'] = 1;

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
            // doctor_id and appointment_type_id are what a rebooking needs:
            // names cannot be sent to the booking API.
            'id', 'start_date', 'doctor_name', 'appointment_type_name',
            'doctor_id', 'appointment_type_id',
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

    /**
     * Start another booking for a patient we already know.
     *
     * Their details stay, only the previous selections are cleared. Any day or
     * time mentioned in the same breath is remembered and applied once real
     * availability has been loaded - it is a preference, never a promise.
     */
    private function beginAnotherBooking(array &$state, PureMedApiClient $client, string $text): array
    {
        $state = array_merge($state, [
            'appointments_context' => [],
            'discussed_appointment' => 0,
            'doctor' => null,
            'appointment_types' => [],
            'appointment_type' => null,
            'slots' => [],
            'slot_date' => null,
            'slot' => null,
            'appointment' => null,
            'cancellable' => [],
            'cancel_target' => null,
            // A new booking asks the practice again what it will allow.
            'booking_window' => null,
            // Consumed at the slot step, once PureMed has told us what exists.
            'slot_hint' => $this->slotHint($text),
        ]);

        $replies = $this->startBooking($state, $client);
        $name = $state['patient']['first_name'] ?? null;

        return array_merge(
            [$this->say($name
                ? 'Of course ' . $name . " - let's book another appointment."
                : "Of course - let's book another appointment.")],
            $replies
        );
    }

    /**
     * Put the real, already-loaded PureMed slots back in front of the patient.
     *
     * No new booking logic and no new API call: the same availability the flow
     * fetched is simply shown again. The doctor, appointment type and day are
     * kept, because asking to see the times is not rejecting the appointment.
     *
     * @return array<int, array>
     */
    private function showSlotList(array &$state, string $text = ''): array
    {
        $state['slot'] = null;
        // The rules have answered this message; no need to ask the LLM as well.
        $state['_handled'] = true;

        if (empty($state['slots'])) {
            // Nothing loaded yet - the appointment type step fetches it.
            $state['step'] = $state['doctor'] ? 'appointment_type' : 'doctor';

            return [$this->say("Let's get to the times - I just need a couple more details first.")];
        }

        // A day named in THIS message replaces the one already chosen.
        // "Can I have slots for Friday?" must not keep showing Thursday.
        if ($text !== '') {
            $requested = $this->matchSlotDate($state['slots'], '', $text);

            // A day named in order to refuse it is not a request for that day.
            if ($requested && $this->rejectsNamedDay($text)) {
                $others = array_values(array_diff($this->availableDates($state['slots']), [$requested]));

                if (!$others) {
                    $state['_no_prompt'] = true;
                    $state['step'] = $state['slot_date'] ? 'slot_time' : 'slot_date';

                    return [$this->say($requested . ' is the only day I have free for this appointment. '
                        . 'I can try a different appointment type, or another doctor, if you prefer.')];
                }

                // Drop the day they refused and let them choose again.
                $state['slot_date'] = null;
                $state['slot_window'] = null;
                $state['step'] = 'slot_date';
                $state['_no_prompt'] = true;

                return [$this->say('Of course - which of these days would suit instead?')];
            }

            if ($requested) {
                $state['slot_date'] = $requested;
            } elseif ($this->mentionsSpecificDay($text)) {
                // Nothing free on the day they asked about. If they had already
                // settled on a day, keep it and show its times again - clearing
                // it would undo a choice they never asked to change and make
                // them pick the same day a second time.
                if ($state['slot_date']) {
                    $state['step'] = 'slot_time';
                    $state['_no_prompt'] = true;

                    return [
                        $this->say("I don't have anything free on that day."),
                        $this->say('Here is what I have on ' . $state['slot_date'] . '. Which time suits?'),
                    ];
                }

                $state['slot_window'] = null;
                $state['step'] = 'slot_date';
                $state['_no_prompt'] = true;

                return [
                    $this->say("I don't have anything free on that day."),
                    $this->say('Which of these days works for you?'),
                ];
            }

            if (preg_match('/\b(morning|afternoon|evening)\b/i', $text, $window)) {
                $state['slot_window'] = mb_strtolower($window[1]);
            }
        }

        // One acknowledgement that also asks the question, instead of an
        // acknowledgement followed by the step prompt repeating it.
        $state['_no_prompt'] = true;

        if ($state['slot_date']) {
            $state['step'] = 'slot_time';
            $window = $state['slot_window'] ?? null;

            return [$this->say('Of course - here are the ' . ($window ? $window . ' ' : '')
                . 'times I have on ' . $state['slot_date'] . '. Which one suits?')];
        }

        $state['step'] = 'slot_date';

        return [$this->say('Of course - here are the days I have available. Which one works for you?')];
    }

    /**
     * Is this message only about when, with nothing about who or what?
     *
     * "the earliest one" at the doctor step is a timing preference. Left alone,
     * an LLM reads it as "option 1" and silently picks the first doctor.
     */
    private function isOnlyTimePreference(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        $aboutTime = preg_match('/\b(earliest|soonest|latest|asap|morning|afternoon|evening|tomorrow|today|next week|as soon as possible)\b/', $value) === 1
            // "around 3" and "on the 13th" are times and dates; "the 3rd one"
            // is an option, so the leading words are what tell them apart.
            || preg_match('/\b(around|about|at|after|before)\s+\d{1,2}\b/', $value) === 1
            || preg_match('/\bon the\s+\d{1,2}\b/', $value) === 1
            || $this->mentionsSpecificDay($text)
            || $this->extractTime($text) !== '';

        if (!$aboutTime) {
            return false;
        }

        // A message that also names a person or a service is not "only" timing.
        return preg_match('/\b(dr|doctor|checkup|check up|screening|consultation|scan|test|appointment type)\b/', $value) !== 1;
    }

    /**
     * The part of a booking request that talks about when.
     *
     * Returns the message only if it mentions a day or time worth remembering,
     * so an ordinary "book an appointment" carries no stale preference.
     */
    private function slotHint(string $text): ?string
    {
        if ($this->mentionsSpecificDay($text) || $this->extractTime($text) !== '') {
            return $text;
        }

        return preg_match('/\b(tomorrow|today|morning|afternoon|evening|earliest|soonest|next week)\b/i', $text) === 1
            ? $text
            : null;
    }

    /** Make sure doctors are loaded, then move to doctor selection. */
    private function startBooking(array &$state, PureMedApiClient $client): array
    {
        $state['goal'] = 'book';
        // A new booking is a change of subject: questions about the listed
        // appointments no longer have a list to be about.
        $state['appointments_context'] = [];
        $state['discussed_appointment'] = 0;
        // And what the practice will allow is asked again, not remembered.
        $state['booking_window'] = null;

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
            // The appointment still stands, so there is nothing to rebook.
            $state['rebook_after_cancel'] = false;

            return [$this->say($this->readableError($result, "I couldn't cancel that appointment. Please contact the practice."), 'error')];
        }

        // If the cancelled one was the booking made in this chat, drop it so the
        // confirmation card does not keep claiming it is still booked.
        if (!empty($state['appointment']['id']) && (int) $state['appointment']['id'] === (int) $target['id']) {
            $state['appointment'] = null;
        }

        $state['cancellable'] = [];
        $state['cancel_target'] = null;
        // Cancelling can make a patient eligible again, so the window has to be
        // asked for afresh rather than remembered from before.
        $state['booking_window'] = null;
        $state['step'] = 'cancelled';

        // Remember what was cancelled, so "book the same again" does not make
        // the patient repeat four answers the assistant already has. Ids only -
        // everything is revalidated against PureMed before anything is offered.
        $state['last_cancelled'] = [
            'doctor_id' => $target['doctor_id'] ?? null,
            'doctor_name' => $target['doctor_name'] ?? null,
            'appointment_type_id' => $target['appointment_type_id'] ?? null,
            'appointment_type_name' => $target['appointment_type_name'] ?? null,
            'date' => $this->appointmentDay($target),
            'time' => $this->appointmentClock($target),
        ];

        // Changing the time is a cancellation followed by a booking, because
        // PureMed has no way to move an appointment. The cancellation has now
        // really happened, so say so plainly before going on - the patient must
        // never be left thinking the old time is still held for them.
        if (!empty($state['rebook_after_cancel'])) {
            $state['rebook_after_cancel'] = false;

            $replies = [$this->say('Done - your appointment on ' . $this->appointmentLabel($target)
                . ' has been cancelled. Now let me find you a new time.')];

            // Same doctor and same appointment type: they asked to change the
            // time, nothing else. Times come from PureMed, as always.
            if ($state['doctor'] && $state['appointment_type']) {
                return array_merge($replies, $this->loadSlots($state, $client));
            }

            $state['step'] = $state['doctor'] ? 'appointment_type' : 'doctor';

            return $replies;
        }

        return [$this->say('Done - your appointment on ' . $this->appointmentLabel($target) . ' has been cancelled. The time is free for someone else now.')];
    }

    /**
     * @return array<int, array> normalised, bookable slots
     */
    private function fetchSlots(array &$state, PureMedApiClient $client): array
    {
        $window = $this->bookingWindow($state, $client);

        // The practice's rules leave nothing bookable, so there is nothing to
        // ask for. Asking anyway would return dates it will not accept.
        if (!empty($window['none'])) {
            return [];
        }

        $result = $client->getDoctorSlots($state['token'], $this->buildSlotRequestPayload(
            $state['doctor'],
            $state['appointment_type'],
            $window
        ));

        return $result['ok'] ? $this->normalizeSlots((array) $result['data']) : [];
    }

    /**
     * get-doctor-slots validates start_date/end_date as date_format:d.m.Y
     * (Api\v3\OptimalAppointmentController::getDoctorSlots).
     */
    /**
     * @param  array{start_date: string, end_date: string, from_time: string, to_time: string}  $window
     */
    private function buildSlotRequestPayload(?array $doctor, ?array $appointmentType, array $window): array
    {
        return [
            'doctor_id' => $doctor['id'] ?? null,
            'appointment_type_id' => $appointmentType['id'] ?? null,
            // The practice's window and hours, not the assistant's. Asking for
            // 00:00-23:59 returned times no practice would offer - 04:00, 22:50.
            'start_date' => $window['start_date'],
            'end_date' => $window['end_date'],
            'from_time' => $window['from_time'],
            'to_time' => $window['to_time'],
            'week_day_id' => '1,2,3,4,5,6,7',
        ];
    }

    /**
     * The window the practice will actually take bookings in.
     *
     * Read from get-from-date, which is where the quarter rules, the booking
     * timeframe and the appointment type's optimal_appointment flag are applied
     * - the same call the main app makes. Held in the session per doctor and
     * appointment type, because slots are fetched several times in one booking:
     * when a time is picked, when it is held, and again before booking.
     *
     * @return array{start_date: string, end_date: string, from_time: string, to_time: string, key: string}
     */
    private function bookingWindow(array &$state, PureMedApiClient $client): array
    {
        $doctorId = $state['doctor']['id'] ?? null;
        $typeId = $state['appointment_type']['id'] ?? null;
        $key = $doctorId . '|' . $typeId;

        $cached = $state['booking_window'] ?? null;

        if (is_array($cached) && ($cached['key'] ?? null) === $key) {
            return $cached;
        }

        $window = null;

        // Only once we know who is booking, with whom, and for what - the call
        // requires all three.
        if ($state['patient_id'] && $state['token'] && $doctorId && $typeId) {
            $result = $client->getFromDate($state['token'], [
                'patient_id' => $state['patient_id'],
                'doctor_id' => $doctorId,
                'appointment_type_id' => $typeId,
            ]);

            $data = (array) ($result['data'] ?? []);
            $body = (array) ($result['body'] ?? []);

            // Did the practice evaluate its rules and decline, or did the call
            // simply not work? A decline comes back with no errors and a data
            // key; an expired token comes back with errors and no data. Telling
            // a patient "the practice isn't offering that" because a token had
            // expired would be worse than showing nothing at all.
            $refused = (int) ($result['http_status'] ?? 0) === 200
                && empty($result['errors'])
                && array_key_exists('data', $body);

            if ($result['ok'] && !empty($data['start_date']) && !empty($data['end_date'])) {
                $defaults = $this->defaultBookingWindow();

                $window = [
                    'start_date' => (string) $data['start_date'],
                    'end_date' => (string) $data['end_date'],
                    'from_time' => (string) ($data['from_time'] ?: $defaults['from_time']),
                    'to_time' => (string) ($data['to_time'] ?: $defaults['to_time']),
                ];
            } elseif ($refused) {
                // The practice answered and offered NO window - its own rules
                // say this patient cannot book this appointment at the moment,
                // for instance because they already have one this quarter.
                // Falling back here would offer dates the practice refuses,
                // which is exactly what it did before this branch existed.
                Log::info('AI assistant: the practice offers no booking window', [
                    'doctor_id' => $doctorId,
                    'appointment_type_id' => $typeId,
                    'reason' => $result['message'] ?? null,
                ]);

                $window = ['none' => true, 'start_date' => '', 'end_date' => '',
                    'from_time' => '', 'to_time' => ''];
            }
        }

        // Only a call that never reached the practice falls back to a window of
        // our own; a refusal is never overridden.
        if ($window === null) {
            $window = $this->defaultBookingWindow();
        }

        $window['key'] = $key;
        $state['booking_window'] = $window;

        return $window;
    }

    /** Used only when the practice's own window cannot be read. */
    private function defaultBookingWindow(): array
    {
        return [
            'start_date' => Carbon::today()->format('d.m.Y'),
            'end_date' => Carbon::today()
                ->addDays((int) config('ai-assistant.slot_window_days', 30))->format('d.m.Y'),
            'from_time' => (string) config('ai-assistant.default_from_time', '06:00'),
            'to_time' => (string) config('ai-assistant.default_to_time', '21:00'),
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

    /**
     * @param  string|null  $window  morning, afternoon or evening, when the
     *                               patient asked for one. A day can hold well
     *                               over a hundred slots, so showing the part
     *                               of the day they actually asked about beats
     *                               starting at midnight.
     */
    private function timeCards(array $slots, ?string $date, ?string $window = null): array
    {
        $onDate = collect($slots)->where('slot_date', $date);

        if ($window) {
            $inWindow = $onDate->filter(fn ($slot) => $this->withinWindow($slot['time'], $window));

            if ($inWindow->isNotEmpty()) {
                $onDate = $inWindow;
            }
        }

        return $onDate
            ->map(fn ($slot) => [
                'value' => $slot['time'],
                'title' => $slot['time'],
                'subtitle' => '',
            ])
            ->values()
            ->all();
    }

    private function withinWindow(string $time, string $window): bool
    {
        $normalised = $this->normalizeTime($time);

        if ($normalised === '') {
            return false;
        }

        $minutes = (int) substr($normalised, 0, 2) * 60 + (int) substr($normalised, 2, 2);

        return match ($window) {
            'morning' => $minutes >= 6 * 60 && $minutes <= 11 * 60 + 59,
            'afternoon' => $minutes >= 12 * 60 && $minutes <= 16 * 60 + 59,
            'evening' => $minutes >= 17 * 60 && $minutes <= 21 * 60 + 59,
            default => true,
        };
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
    /** The day of an appointment as PureMed writes days: 11.08.2026. */
    private function appointmentDay(array $appointment): ?string
    {
        if (!empty($appointment['date'])) {
            return $appointment['date'];
        }

        try {
            return Carbon::parse($appointment['start_date'])->format('d.m.Y');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function appointmentClock(array $appointment): ?string
    {
        if (!empty($appointment['time'])) {
            return $appointment['time'];
        }

        try {
            return Carbon::parse($appointment['start_date'])->format('H:i');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function appointmentLabel(array $appointment): string
    {
        // Two shapes reach here: one listed by PureMed for cancelling, which
        // carries start_date, and the booking just made in this chat, which
        // carries the day and time already formatted for the patient.
        if (!empty($appointment['date'])) {
            return trim($appointment['date'] . ' ' . (string) ($appointment['time'] ?? ''));
        }

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

        // A bare number is only a position when the patient says so. Without
        // this, "20 August would be better" was read as "the 20th option" and
        // silently selected a slot on a completely different day.
        $positional = preg_match('/\b(?:number|option|no|#)\s*(\d{1,2})\b/', $spoken, $matches)
            || preg_match('/\b(\d{1,2})\s*(?:st|nd|rd|th)\b/', $spoken, $matches)
            // normalizeText() has already stripped the ordinal suffix, so
            // "the 2nd one" arrives here as "the 2 one".
            || preg_match('/\b(?:the\s+)?(\d{1,2})\s+(?:one|slot|option|doctor|appointment)\b/', $spoken, $matches)
            || preg_match('/^(\d{1,2})$/', $spoken, $matches);

        if ($positional) {
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

        // Resolve a named day on its own terms first. Matching only against the
        // days that happen to be free lets a short alias win: "the day after
        // tomorrow" was being answered with tomorrow, because tomorrow was open
        // and the day after was not. Laravel does this arithmetic, never the LLM.
        $requested = $this->resolveRequestedDate($text);

        if ($requested) {
            $formatted = $requested->format('d.m.Y');

            if (array_key_exists($formatted, $dates)) {
                return $formatted;
            }

            // "tomorrow" or "next Friday" is unambiguous: if it is not offered,
            // say so. A bare weekday is not - said on a Monday, "Monday" might
            // mean today or the one coming - so fall through and let the alias
            // search find the nearest offered match.
            if ($this->isStrictDayReference($text)) {
                return null;
            }
        }

        // Look for a date reference INSIDE the sentence rather than requiring the
        // whole message to be one. "20 August would be better" names a day just
        // as clearly as "20.08.2026" does. The longest alias wins, so "20 august"
        // beats the bare "20" of another date.
        $best = null;
        $bestLength = 0;

        foreach ($dates as $slotDate => $weekday) {
            foreach ($this->dateAliases($slotDate, $weekday) as $alias) {
                if ($alias === '' || mb_strlen($alias) <= $bestLength) {
                    continue;
                }

                if ($spoken === $alias || $this->containsPhrase($spoken, $alias)) {
                    $best = $slotDate;
                    $bestLength = mb_strlen($alias);
                }
            }
        }

        if ($best !== null) {
            return $best;
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
     * Turn a spoken day reference into a real calendar date.
     *
     * The LLM only ever returns a semantic value such as "tomorrow" or
     * "Friday"; this is where that becomes a date, using the application clock.
     * Returns null when the message names no particular day.
     */
    private function resolveRequestedDate(string $text): ?Carbon
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return null;
        }

        $today = Carbon::today();

        // Longest phrases first: "day after tomorrow" contains "tomorrow".
        if (preg_match('/\b(day after tomorrow|ubermorgen|übermorgen)\b/u', $value)) {
            return $today->copy()->addDays(2);
        }

        if (preg_match('/\b(tomorrow|morgen)\b/u', $value)) {
            return $today->copy()->addDay();
        }

        if (preg_match('/\b(today|heute|tonight)\b/u', $value)) {
            return $today->copy();
        }

        $weekdays = [
            'monday' => Carbon::MONDAY, 'montag' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY, 'dienstag' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY, 'mittwoch' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY, 'donnerstag' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY, 'freitag' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY, 'samstag' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY, 'sonntag' => Carbon::SUNDAY,
        ];

        foreach ($weekdays as $name => $day) {
            if (!preg_match('/\b(next|this|coming)?\s*' . $name . '\b/u', $value, $matches)) {
                continue;
            }

            // "Friday" and "this Friday" mean the coming one; today counts if
            // today is that day. "next Friday" is the week after that.
            $date = $today->isDayOfWeek($day) ? $today->copy() : $today->copy()->next($day);

            if (($matches[1] ?? '') === 'next') {
                $date = $today->copy()->next($day)->addWeek();
            }

            return $date;
        }

        return null;
    }

    /** Does the message pin down one particular day beyond doubt? */
    private function isStrictDayReference(string $text): bool
    {
        $value = $this->normalizeText($text);

        return preg_match('/\b(today|tomorrow|day after tomorrow|heute|morgen|ubermorgen|übermorgen|tonight)\b/u', $value) === 1
            || preg_match('/\b(next|this|coming)\s+\w+/u', $value) === 1
            || $this->mentionsSpecificDay($text) && preg_match('/\d/', $text) === 1;
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

        // Longer aliases win in matchSlotDate(), which is what stops "the day
        // after tomorrow" being swallowed by the shorter "tomorrow".
        $today = Carbon::today();
        // Carbon 3 returns a float here, so === would never match.
        $daysAway = (int) $today->diffInDays($date, false);

        if ($daysAway === 2) {
            $aliases[] = 'day after tomorrow';
            $aliases[] = 'the day after tomorrow';
            $aliases[] = 'ubermorgen';
            $aliases[] = 'übermorgen';
        }

        // "this Friday" is the coming one; "next Friday" is the week after.
        if ($daysAway >= 1 && $daysAway <= 7) {
            $aliases[] = 'this ' . $date->format('l');
            $aliases[] = 'coming ' . $date->format('l');
        }

        if ($daysAway >= 8 && $daysAway <= 14) {
            $aliases[] = 'next ' . $date->format('l');
        }

        return array_values(array_unique(array_map(fn ($alias) => $this->normalizeText($alias), $aliases)));
    }

    /**
     * Match a spoken or typed time against the offered slots.
     *
     * Every candidate is reduced to four digits, so "10:40", "1040", "10 40",
     * "10 colon 40" and "ten forty" all resolve to the same slot.
     */
    /**
     * @param  string|null  $window  the part of the day currently on screen. An
     *                               explicit time is still searched across the
     *                               whole day, but "the second one" must mean
     *                               the second chip the patient can see.
     */
    private function matchSlot(array $slots, ?string $date, string $choiceValue, string $text, ?string $window = null): ?array
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

        // Positional and preference answers refer to the list on screen.
        $visible = $onDate;

        if ($window) {
            $inWindow = array_values(array_filter($onDate, fn ($slot) => $this->withinWindow($slot['time'], $window)));

            if ($inWindow) {
                $visible = $inWindow;
            }
        }

        // A bare "15" answering "which time suits?" is three o'clock, not the
        // fifteenth chip in the list. The positional matcher below used to
        // count down the times and land on whatever sat at that position -
        // 16:20, on a list starting at 14:00. Named positions ("the second
        // one", "number 15") still reach that matcher untouched.
        $byBareHour = $this->matchSlotByHour($visible, $text, true);

        if ($byBareHour) {
            return $byBareHour;
        }

        // "earliest", "morning", "afternoon", "anything after 2" - people ask
        // for a part of the day far more often than an exact minute.
        $preferred = $this->matchSlotByPreference($visible, $this->normalizeText($text));

        if ($preferred) {
            return $preferred;
        }

        // "book me in for 7" means seven o'clock.
        return $this->matchSlotByHour($visible, $text);
    }

    /**
     * "for 7", "at 7", "around 3" - an hour named without minutes.
     *
     * Requires a leading preposition so a bare number keeps meaning a position
     * in the list. Falls back to the afternoon reading, because a patient
     * asking for "2" on a list that runs to 16:00 means 14:00.
     *
     * @param  array<int, array>  $slots
     */
    /**
     * The question to ask when a number could be a time or a position.
     *
     * Answering "which time suits?" with a number almost always means the hour,
     * so when that hour is free the existing matcher takes it and this returns
     * nothing. The dangerous case is the hour NOT being free: the number then
     * falls through to the positional matcher, which happily counts down the
     * list and picks something the patient never said. That is what turned
     * "book for 11" into 15:40.
     *
     * Explicit wording is left alone: "the 11th one", "number 11", "11 o'clock"
     * and "11:00" all say plainly which is meant.
     */
    private function clarifyAmbiguousNumber(array $state, string $text): ?string
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return null;
        }

        // Said as a position - the existing matcher resolves it.
        if ($this->saysPosition($text)) {
            return null;
        }

        // Said unmistakably as a time. Never offered a position below - if the
        // hour is not free the patient is told so, not counted into the list.
        $saidAsTime = preg_match('/\d{1,2}\s*[:.]\s*\d{2}/u', $text) === 1
            || preg_match('/\b(?:am|pm|o clock|oclock|uhr)\b/u', $value) === 1;

        $number = $this->singleNumberIn($text);

        if ($number === null) {
            return null;
        }

        $onDate = $this->slotsOn($state['slots'] ?? [], $state['slot_date'] ?? null);

        if (!$onDate || $number < 1) {
            return null;
        }

        // The hour is free, so it is a time and the matcher will find it.
        if ($this->matchSlotByHour($onDate, 'at ' . $number)) {
            return null;
        }

        $clock = sprintf('%02d:00', $number > 23 ? 0 : $number);

        // The hour is not free, but that many times exist - genuinely ambiguous,
        // unless they said it in a way that can only be a time.
        if (!$saidAsTime && $number <= count($onDate)) {
            return 'I don\'t have ' . $clock . ' free on ' . $state['slot_date']
                . '. Did you mean the ' . $this->ordinalName($number) . ' time in the list, or a different time?';
        }

        return 'I don\'t have ' . $clock . ' free on ' . $state['slot_date'] . '. Here are the times I do have.';
    }

    /**
     * The one small number in a message, or null.
     *
     * Null when there is none, more than one, or when it is written as a
     * position ("the 11th one") - those the positional matcher owns.
     */
    private function singleNumberIn(string $text): ?int
    {
        $value = $this->normalizeText($text);

        if ($value === '' || $this->saysPosition($text)) {
            return null;
        }

        if (preg_match_all('/(?<!\d)(\d{1,2})(?!\d)/u', $value, $matches) !== 1) {
            return null;
        }

        $number = (int) $matches[1][0];

        return $number >= 1 && $number <= 23 ? $number : null;
    }

    /**
     * "Do you have any other doctors?" - a question about the list, not a
     * choice from it.
     *
     * Only consulted at the doctor question. Later in the booking the same
     * words mean "change the doctor I picked", which the escape hatch handles.
     */
    private function asksForOtherDoctors(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        $who = '(doctors?|drs?|physicians?|gps?|specialists?|someone|somebody|anyone|anybody)';

        // "do you have any other doctors", "are there more doctors",
        // "what other doctors do you have", "is there anyone else"
        if (preg_match('/\b(do you have|have you got|are there|is there|what other|which other|any other|anything else)\b[^.?]{0,32}\b' . $who . '\b/u', $value) === 1) {
            return true;
        }

        // "anyone else available", "someone else", "another doctor available"
        return preg_match('/\b' . $who . '\b[^.?]{0,24}\b(else|available|other than|besides|apart from)\b/u', $value) === 1
            || preg_match('/\b(anyone|anybody|someone|somebody)\s+else\b/u', $value) === 1
            || preg_match('/\b(can i|could i|may i)\b[^.?]{0,16}\b(choose|pick|see|have)\b[^.?]{0,16}\b(another|other|different)\b[^.?]{0,8}\b' . $who . '\b/u', $value) === 1;
    }

    /**
     * Answer that question from the practice's real list.
     *
     * The list is read again from PureMed rather than from the session, so the
     * answer is about what is actually on offer. No doctor is ever named that
     * PureMed did not return, and nothing else about the booking is touched.
     */
    private function answerOtherDoctors(array &$state, PureMedApiClient $client): array
    {
        $fresh = $client->getDoctors($state['token']);

        if ($fresh['ok'] && !empty($fresh['data'])) {
            $state['doctors'] = $this->keepFields((array) $fresh['data'],
                ['id', 'first_name', 'last_name', 'doctor_speciality']);
        }

        $all = $state['doctors'] ?? [];

        if (!$all) {
            return [$this->say("I can't see the doctor list just now. Please try again in a moment.", 'error')];
        }

        $perPage = (int) config('ai-assistant.chips_per_page', 6);
        $page = (int) ($state['chip_page'] ?? 0);
        $shown = min(count($all), $perPage * ($page + 1));

        // There are more than the patient can currently see.
        if (count($all) > $shown) {
            $state['chip_page'] = $page + 1;
            $state['_no_prompt'] = true;

            return [$this->say('Yes - here are the others I have.')];
        }

        $state['_no_prompt'] = true;

        return [$this->say('Currently, I only have ' . $this->doctorNameList($all)
            . ' available. Would you like to choose one of them?')];
    }

    /** "Dr Gunnar Gauff and Dr Albert Munnar", from the real records. */
    private function doctorNameList(array $doctors): string
    {
        $names = array_values(array_filter(array_map(
            fn ($doctor) => $this->doctorName($doctor) === '' ? null : $this->doctorDisplay($doctor),
            $doctors
        )));

        if (count($names) <= 1) {
            return $names[0] ?? 'no doctors';
        }

        $last = array_pop($names);

        return implode(', ', $names) . ' and ' . $last;
    }

    /**
     * "Can you book the same appointment again?"
     *
     * Only meaningful right after a cancellation, which is where it is checked.
     */
    private function wantsSameAgain(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        if (preg_match('/\b(rebook|re book)\b/u', $value) === 1) {
            return true;
        }

        // "the same appointment", "the same one", "same date and time"
        if (preg_match('/\bsame\b[^.]{0,20}\b(appointment|one|time|slot|date|day|doctor)\b/u', $value) === 1) {
            return true;
        }

        // "book it again", "book that again"
        return preg_match('/\b(book|booking)\b[^.]{0,16}\bagain\b/u', $value) === 1
            || preg_match('/\bagain\b[^.]{0,16}\b(same|that one|it)\b/u', $value) === 1;
    }

    /**
     * Offer the appointment that was just cancelled, if it is still bookable.
     *
     * Nothing here books anything. The doctor, the appointment type and the
     * time are each looked up again through the same PureMed calls the normal
     * flow uses, and the patient is taken to the ordinary confirmation step -
     * so the existing checks before booking still apply in full.
     */
    private function offerSameAgain(array &$state, PureMedApiClient $client): array
    {
        $previous = $state['last_cancelled'] ?? null;

        if (!is_array($previous) || empty($previous['doctor_id']) || empty($previous['appointment_type_id'])) {
            return [$this->say("I don't have the details of that one to hand. Let's book it fresh - which doctor would you like to see?")];
        }

        // The doctor must still be one the practice offers.
        $doctors = $client->getDoctors($state['token']);
        $doctor = collect($doctors['ok'] ? (array) $doctors['data'] : [])
            ->firstWhere('id', $previous['doctor_id']);

        if (!$doctor) {
            $state['step'] = 'doctor';

            return [$this->say(($previous['doctor_name'] ? Str::title($previous['doctor_name']) : 'That doctor')
                . ' is not available for booking any more. Which doctor would you like to see?', 'error')];
        }

        $state['doctors'] = $this->keepFields((array) $doctors['data'], ['id', 'first_name', 'last_name', 'doctor_speciality']);
        $state['doctor'] = collect($state['doctors'])->firstWhere('id', $previous['doctor_id']);

        // The appointment type must still exist for that doctor.
        $types = $client->getAppointmentTypes($state['token'], ['doctor_id' => $previous['doctor_id']]);
        $type = collect($types['ok'] ? (array) $types['data'] : [])
            ->firstWhere('id', $previous['appointment_type_id']);

        if (!$type) {
            $state['appointment_types'] = $this->keepFields((array) ($types['data'] ?? []), ['id', 'name', 'duration', 'description']);
            $state['step'] = 'appointment_type';

            return [$this->say(($previous['appointment_type_name'] ?: 'That appointment type')
                . ' is not offered any more. What do you need the appointment for?', 'error')];
        }

        $state['appointment_types'] = $this->keepFields((array) $types['data'], ['id', 'name', 'duration', 'description']);
        $state['appointment_type'] = collect($state['appointment_types'])->firstWhere('id', $previous['appointment_type_id']);

        // And the time must still be free.
        $state['slots'] = $this->fetchSlots($state, $client);
        $state['slot'] = null;
        $state['slot_window'] = null;

        if (empty($state['slots'])) {
            $state['slot_date'] = null;
            $state['step'] = 'appointment_type';

            return [$this->say("I couldn't find any free times for that appointment any more. You could try a different appointment type, or another doctor.", 'error')];
        }

        $slot = collect($state['slots'])
            ->firstWhere(fn ($candidate) => $candidate['slot_date'] === $previous['date']
                && $candidate['time'] === $previous['time']);

        if ($slot) {
            $state['slot_date'] = $slot['slot_date'];
            $state['slot'] = $slot;
            $state['step'] = 'confirm';
            $state['last_cancelled'] = null;

            // The confirmation card and its Yes/No follow from the step itself.
            return [$this->say('Sure - I still have those details.')];
        }

        // The exact time has gone. Offer that day if it still has anything,
        // otherwise the days that are left.
        $sameDay = $this->slotsOn($state['slots'], $previous['date']);
        $state['last_cancelled'] = null;

        if ($sameDay) {
            $state['slot_date'] = $previous['date'];
            $state['step'] = 'slot_time';
            $state['_no_prompt'] = true;

            return [$this->say($previous['time'] . ' on ' . $previous['date']
                . ' has been taken since. Here is what I still have that day - which one suits?')];
        }

        $state['slot_date'] = null;
        $state['step'] = 'slot_date';
        $state['_no_prompt'] = true;

        return [$this->say('I no longer have anything free on ' . $previous['date']
            . '. Which of these days would suit instead?')];
    }

    /**
     * "I don't want to book my appointment" - a change of mind about the whole
     * booking, rather than about one of the answers in it.
     *
     * Naming a day, a time or a position makes it a change of CHOICE, and those
     * are handled by the step they belong to: "I don't want this time" asks for
     * another time, "I don't want 13 August" asks for another day.
     */
    private function abandonsBooking(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '' || $this->mentionsSpecificDay($text)
            || $this->extractTime($text) !== '' || $this->saysPosition($text)) {
            return false;
        }

        if (preg_match('/\b(never mind|nevermind|forget it|forget about it|leave it|not interested'
            . '|changed my mind|change my mind)\b/u', $value) === 1) {
            return true;
        }

        // "don't want to book", "I do not want to book anything", "no booking"
        return preg_match('/\b(dont|do not|don t|not|no)\b[^.]{0,24}\b(book|booking)\b/u', $value) === 1
            || preg_match('/\b(dont|do not|don t)\b[^.]{0,16}\bwant\b[^.]{0,12}\b(an|any)\s+appointment\b/u', $value) === 1;
    }

    /**
     * Whether the number in a message is a position in the list rather than a
     * time or a day: "the 11th one", "number 11", "option 11".
     */
    private function saysPosition(string $text): bool
    {
        $value = $this->normalizeText($text);

        return $value !== '' && (
            preg_match('/\b\d{1,2}\s*(?:st|nd|rd|th)\b/u', $value) === 1
            || preg_match('/\b(?:number|option|no|#)\s*\d{1,2}\b/u', $value) === 1
            || preg_match('/\b(?:the\s+)?\d{1,2}\s+(?:one|slot|option)\b/u', $value) === 1
        );
    }

    /** @return array<int, array> */
    private function slotsOn(array $slots, ?string $date): array
    {
        return array_values(array_filter($slots, fn ($slot) => ($slot['slot_date'] ?? null) === $date));
    }

    private function ordinalName(int $position): string
    {
        $names = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth',
            6 => 'sixth', 7 => 'seventh', 8 => 'eighth', 9 => 'ninth', 10 => 'tenth'];

        if (isset($names[$position])) {
            return $names[$position];
        }

        $suffix = ($position % 100 >= 11 && $position % 100 <= 13)
            ? 'th'
            : ([1 => 'st', 2 => 'nd', 3 => 'rd'][$position % 10] ?? 'th');

        return $position . $suffix;
    }

    private function matchSlotByHour(array $slots, string $text, bool $allowBareNumber = false): ?array
    {
        $value = $this->normalizeText($text);

        if (preg_match('/\b(?:at|for|around|about|by|from|near)\s+(\d{1,2})\b/', $value, $matches)) {
            $hour = (int) $matches[1];
        } elseif ($allowBareNumber && preg_match('/^(\d{1,2})$/', $value, $matches)) {
            // Answering "15" to "which time suits?" is an hour, not a position.
            $hour = (int) $matches[1];
        } else {
            return null;
        }

        if ($hour > 23) {
            return null;
        }

        foreach ([$hour, $hour <= 12 ? $hour + 12 : $hour] as $candidate) {
            // Prefer the exact hour, then anything within it.
            foreach ([true, false] as $exactOnly) {
                foreach ($slots as $slot) {
                    $normalised = $this->normalizeTime($slot['time']);

                    if ($normalised === '') {
                        continue;
                    }

                    if ($exactOnly && $normalised === sprintf('%02d00', $candidate)) {
                        return $slot;
                    }

                    if (!$exactOnly && (int) substr($normalised, 0, 2) === $candidate) {
                        return $slot;
                    }
                }
            }
        }

        return null;
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

        // Compare by clock time rather than array position: "earliest" must mean
        // the earliest time, not whichever slot the API happened to list first.
        if (preg_match('/\b(earliest|first available|asap|soonest|as soon as possible)\b/', $spoken)) {
            return collect($onDate)->sortBy($minutes)->first();
        }

        if (preg_match('/\b(latest|last)\b/', $spoken)) {
            return collect($onDate)->sortByDesc($minutes)->first();
        }

        // Clinic hours, not clock arithmetic: 01:00 is not "morning" to anyone.
        $windows = [
            'morning' => [6 * 60, 11 * 60 + 59],
            'afternoon' => [12 * 60, 16 * 60 + 59],
            'evening' => [17 * 60, 21 * 60 + 59],
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

        if ($value === '') {
            return false;
        }

        // People rarely repeat the word "doctor": "can I see someone else",
        // "is anyone else available".
        if (preg_match('/\b(someone|somebody|anyone|anybody)\s+else\b/', $value) === 1) {
            return true;
        }

        return preg_match('/\b(another|different|change|other)\b.*\b(doctor|physician|gp|specialist)\b/', $value) === 1;
    }

    /**
     * "I picked the wrong reason for the visit."
     *
     * Deliberately narrow: "another appointment" on its own means booking a
     * second one, which is a different thing entirely.
     */
    private function wantsAnotherType(string $choiceValue, string $text): bool
    {
        if ($choiceValue === '__type__') {
            return true;
        }

        $value = mb_strtolower(trim($text));

        return $value !== '' && preg_match(
            '/\b(another|different|change|other|wrong)\b.*\b(appointment type|type of appointment|appointment reason|reason for)\b/',
            $value
        ) === 1;
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

    /**
     * Does the message name a particular calendar day?
     *
     * Used to tell "20 August" (a day that may not be offered) apart from
     * "morning" (a preference that can be satisfied on any day).
     */
    private function mentionsSpecificDay(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        $months = implode('|', array_map(
            fn ($m) => preg_quote($m, '/'),
            array_merge(self::GERMAN_MONTHS, [
                'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december',
            ])
        ));

        // Relative days name a day just as definitely as "20 August" does.
        // Without these, "tomorrow" on a day with no availability fell through
        // and a different day was quietly chosen instead.
        $weekdays = 'monday|tuesday|wednesday|thursday|friday|saturday|sunday'
            . '|montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag';

        if (preg_match('/\b\d{1,2}\s*(?:' . $months . ')\b/u', $value) === 1
            || preg_match('/\b(?:' . $months . ')\s*\d{1,2}\b/u', $value) === 1
            || preg_match('/\b(tomorrow|today|day after tomorrow|tonight|heute|morgen)\b/u', $value) === 1
            || preg_match('/\b(?:' . $weekdays . ')\b/u', $value) === 1) {
            return true;
        }

        // "13.08" is a day; "15.00" and "16.20" are how times are written here.
        // The only thing telling them apart is whether the second part could be
        // a month, so an impossible month means this is a clock time.
        if (preg_match('/\b(\d{1,2})[.\/-](\d{1,2})\b/', $text, $parts) === 1) {
            $month = (int) $parts[2];

            return $month >= 1 && $month <= 12;
        }

        return false;
    }

    /** Whole-word containment, so "5" does not match inside "540". */
    private function containsPhrase(string $haystack, string $needle): bool
    {
        return (bool) preg_match('/(?<!\w)' . preg_quote($needle, '/') . '(?!\w)/u', $haystack);
    }

    /**
     * "No, thanks" to "Is there anything else I can help you with?".
     *
     * Only ever consulted in the post-action steps, where that question has
     * just been asked. A bare "no" anywhere else still means what it means
     * there - declining a time, keeping an appointment - and is untouched.
     */
    private function saidNothingElse(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        // Politeness wrapped around a request is still a request: "thanks, book
        // another one" must book, and "no, show my appointments" must list.
        if (preg_match('/\b(book|cancel|reschedule|rebook|change|move|show|list)\b/u', $value) === 1) {
            return false;
        }

        // "no thanks", "nope", "not right now", "no I'm fine"
        if (preg_match('/^(no|nope|nah|nein)\b/u', $value) === 1) {
            return true;
        }

        // Gratitude on its own ends a conversation just as plainly as "no"
        // does - "thanks", "thank you very much", "cheers". Only reachable in
        // the post-action steps, so it can never be read as an intent.
        if (preg_match('/\b(thank|thanks|thankyou|thx|cheers|danke|appreciate)/u', $value) === 1) {
            return true;
        }

        return preg_match(
            '/\b(nothing else|nothing more|nothing|that s all|thats all|that is all|that s it|thats it'
            . '|i m done|im done|i am done|all done|we re done|not right now|not now'
            . '|that will be all|all good|i m good|im good|i m fine|im fine)\b/u',
            $value
        ) === 1;
    }

    /**
     * End the conversation warmly, without offering the menu again.
     */
    private function closeConversation(array &$state): array
    {
        $name = $state['patient']['first_name'] ?? null;
        $state['step'] = 'closed';
        // The goodbye is the whole answer; "anything else?" underneath it would
        // be the very thing the patient just declined.
        $state['_no_prompt'] = true;
        $state['_handled'] = true;

        return [$this->say($name
            ? "You're welcome, " . $name . '. Have a great day!'
            : "You're welcome. Have a great day!")];
    }

    /**
     * Which of the appointments just listed the patient is asking about.
     *
     * Returns a 1-based position, or null when the message is not a reference
     * at all. The position is all this decides - the answer itself is read from
     * the PureMed list held in state, never composed from the patient's words.
     *
     * @param  int  $discussed  the position last talked about, for "the other one"
     */
    private function appointmentReference(string $text, int $count, int $discussed = 0): ?int
    {
        $value = $this->normalizeText($text);

        if ($value === '' || $count < 1) {
            return null;
        }

        // Booking and cancelling are their own flows and must win outright.
        if (preg_match('/\b(book|cancel)\b/u', $value) === 1) {
            return null;
        }

        $found = null;
        $at = -1;

        // The LAST reference in the sentence is the one being asked about:
        // "I know the first one, when is the second?" is about the second.
        $take = function (string $pattern, callable $position) use ($value, &$found, &$at) {
            if (preg_match($pattern, $value, $matches, PREG_OFFSET_CAPTURE) === 1 && $matches[0][1] > $at) {
                $at = $matches[0][1];
                $found = $position($matches);
            }
        };

        foreach (['first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5] as $word => $position) {
            $take('/\b' . $word . '\b/u', fn () => $position);
        }

        $take('/\b(?:number|no|#)\s*(\d{1,2})\b/u', fn ($m) => (int) $m[1][0]);
        $take('/\b(\d{1,2})\s*(?:st|nd|rd|th)\b/u', fn ($m) => (int) $m[1][0]);
        $take('/\b(?:last|final)\b/u', fn () => $count);
        $take('/\bnext\b/u', fn () => 1);
        // "the other one" means the one that is not being talked about.
        $take('/\bother\b/u', fn () => $discussed === 1 ? min(2, $count) : 1);

        return $found;
    }

    /**
     * Answer a follow-up question about the appointments already listed.
     *
     * Every word of the answer comes from the PureMed rows in state. Returns
     * null when the message is not a question about them, so booking and
     * cancelling carry on working as before.
     */
    private function answerAppointmentQuestion(array &$state, string $text): ?array
    {
        $appointments = array_values($state['appointments_context'] ?? []);
        $count = count($appointments);

        if (!$count) {
            return null;
        }

        $position = $this->appointmentReference($text, $count, (int) ($state['discussed_appointment'] ?? 0));

        if ($position === null) {
            return null;
        }

        if ($position > $count) {
            return [$this->say('You only have ' . $count . ' upcoming '
                . ($count === 1 ? 'appointment' : 'appointments') . '.')];
        }

        $appointment = $appointments[$position - 1];
        $state['discussed_appointment'] = $position;

        $names = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth'];
        $which = $count === 1 ? 'Your appointment' : ('Your ' . ($names[$position] ?? ('number ' . $position)) . ' appointment');

        $type = trim((string) ($appointment['appointment_type_name'] ?? ''));

        return [$this->say($which . ' is on ' . $this->appointmentLabel($appointment)
            . ' with ' . $this->doctorDisplay(['first_name' => $appointment['doctor_name'] ?? ''])
            . ($type !== '' ? ', for ' . $type : '') . '.')];
    }

    /**
     * A question ABOUT existing appointments, rather than a request to make one.
     *
     * This has to be generous, because wantsBooking() matches the bare word
     * "appointment" and is checked straight after: anything not caught here is
     * treated as a new booking. "How many appointments do I have?" used to fall
     * through and open the doctor list.
     */
    private function wantsAppointmentList(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        // Booking and cancelling own their own words. This matters because the
        // caller checks this before wantsCancel(), so without it "cancel my
        // appointment" would be answered with a list instead.
        if (preg_match('/\b(book|cancel|reschedule|rebook)\b/u', $value) === 1) {
            return false;
        }

        $value = $this->repairSpokenPast($value);

        // "how many appointments do I have", "how many appointments I have"
        if (preg_match('/\bhow many\b[^.]{0,20}\bappointments?\b/u', $value) === 1) {
            return true;
        }

        // "is there any upcoming appointment I have", "do I have any appointment"
        if (preg_match('/\b(is there|are there|do i have|have i got|did i have)\b[^.?]{0,28}\bappointments?\b/u', $value) === 1) {
            return true;
        }

        // "upcoming appointment", "past appointment", "future appointments" -
        // singular as well, which every pattern here used to miss.
        if (preg_match('/\b(upcoming|past|previous|future|earlier)\s+appointments?\b/u', $value) === 1) {
            return true;
        }

        // "what appointment I have", "the appointment I have" - and the same
        // said the other way round, "I have any appointment".
        if (preg_match('/\bappointments?\b[^.?]{0,12}\bi have\b/u', $value) === 1
            || preg_match('/\bi have\b[^.?]{0,12}\bappointments?\b/u', $value) === 1) {
            return true;
        }

        // "my appointments", and the same with a word in between: "my total
        // appointments", "my all appointments", "my remaining appointments".
        if (preg_match('/\bmy\b[^.]{0,24}\bappointments?\b/u', $value) === 1) {
            return true;
        }

        // "show me all appointments", "every appointment"
        if (preg_match('/\b(all|every|each)\s+(of\s+)?(the\s+)?appointments?\b/u', $value) === 1) {
            return true;
        }

        // "when is my next appointment (with the doctor)?", "my next appointment"
        if (preg_match('/\bnext appointment\b/u', $value) === 1) {
            return true;
        }

        // "can you check my appointments", "tell me my appointments"
        if (preg_match('/\b(check|tell me|see|view|show)\b[^.]{0,16}\bmy (appointments?|bookings?)\b/u', $value) === 1) {
            return true;
        }

        return preg_match(
            '/\b(my appointments|my bookings|show me my appointments|list my appointments|what appointments'
            . '|which appointments|do i have any appointments|upcoming appointments|all my appointments'
            . '|any appointments)\b/u',
            $value
        ) === 1;
    }

    private function wantsPast(string $text): bool
    {
        $value = $this->repairSpokenPast($this->normalizeText($text));

        if ($value === '') {
            return false;
        }

        // The noun is required: "in the past" on its own is not a request for
        // an appointment history.
        return preg_match('/\b(past|previous|earlier|old)\s+(appointments?|visits?|bookings?)\b/u', $value) === 1
            || preg_match('/\b(appointment\s+)?history\b/u', $value) === 1;
    }

    /**
     * "pasta appointment" is what the speech engine makes of "past
     * appointment". Repaired only when the next word settles the meaning.
     */
    private function repairSpokenPast(string $value): string
    {
        return preg_replace('/\b(pasta|paste|fast)\b(?=\s+(appointments?|visits?))/u', 'past', $value);
    }

    private function wantsBooking(string $text): bool
    {
        $value = mb_strtolower(trim($text));

        if ($value === '') {
            return false;
        }

        // Booking needs a VERB. The bare word "appointment" used to be enough,
        // which meant every question containing it - "how many appointments do
        // I have", "show me all appointments" - started a new booking whenever
        // the viewing matcher did not recognise the phrasing first. Six
        // different phrasings hit that before this was tightened; an unfamiliar
        // one now falls through to a harmless reply instead.
        if (preg_match('/\b(book|booking|schedule|scheduling|termin)\b/u', $value) === 1) {
            return true;
        }

        // "see Dr Gunnar" is a request to be seen, and so is "see a doctor".
        // "which doctor should I see?" is a question and deliberately misses,
        // because "see" is not followed by a doctor there.
        if (preg_match('/\bsee (?:a |the |dr |doctor)/u', $value) === 1) {
            return true;
        }

        // "I want an appointment", "I need a new appointment", "can I get an
        // appointment tomorrow" - asking for one, rather than about one.
        return preg_match(
            '/\b(want|need|like|get|make|take|fix|arrange)\b[^.?]{0,14}\b(an|a|another|new|one more)\s+appointment\b/u',
            $value
        ) === 1;
    }

    /**
     * A request to see more of the list, not a choice from it.
     *
     * Worth being generous here: "show me more appointment types" used to fall
     * through to the matcher, which found a type actually named "swati app
     * type" in the words "appointment type" and selected it.
     */
    private function wantsMore(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        // "show me more...", "can I see more...", "do you have more..."
        if (preg_match('/\b(show|see|list|give|have|got)\b[^.]{0,16}\bmore\b/u', $value) === 1) {
            return true;
        }

        // "more appointment types", "more doctors", "more options please"
        if (preg_match('/\bmore\b\s+(options|choices|types?|appointments?|doctors?|times?|slots?|days?|dates?)\b/u', $value) === 1) {
            return true;
        }

        // "show me other appointment types", "any different sorts"
        // Deliberately no "doctor" here: "a different doctor" means change the
        // one already chosen, which the escape hatch further down handles.
        $things = '(option|options|choice|choices|type|types|sort|sorts|kind|kinds)';

        if (preg_match('/\b(show|see|list|give|have|got|any)\b[^.?]{0,18}\b(other|another|different)\b[^.?]{0,14}\b' . $things . '\b/u', $value) === 1) {
            return true;
        }

        if (preg_match('/\b(other|different)\s+(appointment\s+)?' . $things . '\b/u', $value) === 1) {
            return true;
        }

        return preg_match('/\b(show more|see more|what else|anything else available|other options|further options)\b/u', $value) === 1;
    }

    private function wantsCancel(string $text): bool
    {
        $value = mb_strtolower(trim($text));

        return $value !== '' && preg_match('/\bcancel\b/', $value) === 1;
    }

    /**
     * Is the patient asking to SEE the available times?
     *
     * This is not a rejection. "You haven't shown me the slots" used to match
     * the bare "not" in saidNo() and threw away the chosen appointment.
     */
    private function wantsSlotList(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        // "haven't shown me...", "I can't see any times"
        if (preg_match('/\b(shown|showed|seen|see)\b/', $value)
            && preg_match('/\b(slot|slots|time|times|option|options|availability|available|appointments)\b/', $value)) {
            return true;
        }

        // "what times are available", "what times can I choose"
        if (preg_match('/\bwhat\b.*\b(time|times|slot|slots|appointments)\b/', $value)) {
            return true;
        }

        // "show me the slots", "let me choose a time", "any other times"
        return preg_match('/\b(show|list|display|give)\b.*\b(slot|slots|time|times|option|options)\b/', $value) === 1
            || preg_match('/\b(let me|can i|could i|i want to|i would like to)\b.*\b(choose|pick|see)\b.*\b(time|times|slot|slots)\b/', $value) === 1
            || preg_match('/\b(any|other|another|more)\b.*\b(time|times|slot|slots)\b/', $value) === 1;
    }

    /**
     * Asking for a different day, rather than a different time on the same day.
     */
    /**
     * "Can I change the time for this appointment?" asked about a booking that
     * has already been made.
     *
     * Deliberately requires a word about changing AND a word about what is being
     * changed, so that "book another appointment" - which also mentions an
     * appointment - is left to the new-booking path.
     */
    private function wantsReschedule(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        if (preg_match('/\b(reschedule|rebook|re book)\b/u', $value) === 1) {
            return true;
        }

        return preg_match('/\b(change|move|shift|swap|switch|amend|edit)\b/u', $value) === 1
            && preg_match('/\b(time|slot|appointment|booking|day|date)\b/u', $value) === 1;
    }

    /**
     * "I don't want 13 August" names a day in order to refuse it.
     *
     * Only a negation BEFORE the day counts. One after it usually belongs to
     * something else entirely - "13 August, no problem" is an acceptance.
     */
    private function rejectsNamedDay(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        $day = '/\b(\d{1,2}\s|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec'
            . '|mon|tues|tue|wed|thur|thu|fri|sat|sun|today|tomorrow|heute|morgen)/u';

        if (!preg_match($day, $value, $found, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $before = mb_substr($value, 0, (int) $found[0][1]);

        return preg_match('/\b(dont|don t|do not|not|never|no|anything but|other than|except|besides|instead of|rather than)\b/u', $before) === 1;
    }

    /** The distinct days PureMed returned, in the order they were given. */
    private function availableDates(array $slots): array
    {
        $dates = [];

        foreach ($slots as $slot) {
            $date = $slot['slot_date'] ?? null;

            if ($date && !in_array($date, $dates, true)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    private function wantsAnotherDay(string $text): bool
    {
        $value = $this->normalizeText($text);

        return $value !== ''
            && preg_match('/\b(another|other|different|change|new)\b.{0,20}\b(day|date)\b/', $value) === 1;
    }

    /**
     * Declining, or asking for something different.
     *
     * "not" on its own is deliberately absent: it appears in requests such as
     * "you have not shown me the slots", which are not rejections.
     */
    private function saidNo(string $choiceValue, string $text): bool
    {
        $value = $this->normalizeText($choiceValue !== '' ? $choiceValue : $text);

        if ($value === '') {
            return false;
        }

        // An outright "no" is a rejection even if the same sentence goes on to
        // ask for something else. normalizeText turns "don't" into "don t".
        if (preg_match('/\b(no|nope|nah|dont|don t|do not|doesn t|not that|not this|dont want|don t want)\b/', $value) === 1) {
            return true;
        }

        // Otherwise, asking to see the options is a request, not a refusal.
        if ($this->wantsSlotList($value)) {
            return false;
        }

        return preg_match('/\b(n|another|other|change|different|later|wait|keep it|keep them all|pick another)\b/', $value) === 1;
    }

    /**
     * Agreeing.
     *
     * Booking and cancelling are both irreversible for the patient, so this
     * must never be "anything that is not a no". A negative anywhere in the
     * sentence wins outright, so "actually no, book another" cannot agree.
     */
    private function saidYes(string $choiceValue, string $text): bool
    {
        $value = $this->normalizeText($choiceValue !== '' ? $choiceValue : $text);

        if ($value === '' || $this->saidNo($choiceValue, $text)) {
            return false;
        }

        return preg_match(
            '/\b(yes|y|yeah|yep|yup|ok|okay|sure|confirm|confirmed|correct|right|go ahead|book it|do it|fine|works|sounds good|please do|perfect|lets do it)\b/',
            $value
        ) === 1;
    }

    /* -----------------------------------------------------------------
    |  Input cleaning / validation
    ------------------------------------------------------------------*/

    /**
     * A name, not a sentence.
     *
     * The old pattern accepted any letters and spaces, so "I want to book an
     * appointment" was stored as a first name. A name is short, is at most a
     * few words, and does not contain conversational or command words.
     */
    private function cleanName(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));

        // People answer with a lead-in far more often than with a bare name.
        $value = preg_replace(
            "/^(?:my\s+(?:first\s+|last\s+|sur)?name(?:'s|\s+is)?|i\s*am|i'm|it'?s|this\s+is|call\s+me)\s+/iu",
            '',
            $value
        );
        $value = trim($value, " \t.,!?;:");

        if ($value === '' || mb_strlen($value) > 40) {
            return null;
        }

        $words = preg_split('/\s+/u', $value);

        // "van der Berg" is a surname; a sentence is not. Four words rather
        // than three because the name is now asked for in one question, so a
        // given name and a surname of several parts arrive together - "Jan van
        // der Berg". What actually keeps sentences out is the word check
        // below, not the count.
        if (count($words) > 4) {
            return null;
        }

        foreach ($words as $word) {
            if (in_array(mb_strtolower(trim($word, ".,'-")), self::NOT_NAME_WORDS, true)) {
                return null;
            }
        }

        if (!preg_match("/^[\p{L}][\p{L}\-'. ]{1,39}$/u", $value)) {
            return null;
        }

        // ucwords keeps O'Brien and Anne-Marie readable; Str::title lowercases
        // the letter after an apostrophe.
        return ucwords(mb_strtolower($value), " -'");
    }

    /**
     * PureMed stores mobile numbers as digits (spaces and leading zeros
     * stripped). Speech recognition often adds spaces, so strip everything
     * that is not a digit.
     */
    /**
     * The mobile number, or null when it is not one.
     *
     * The rule is the practice's own, copied from the register API validator
     * ('mobile_no' => 'required|regex:/^(?!0{2})0?[0-9]+$/|numeric'): digits
     * only, and not starting with a double zero. That validator sets no minimum
     * length, so the short-value check below is the assistant's own guard
     * against a mis-heard fragment being accepted as a phone number.
     */
    private function cleanMobile(string $value): ?string
    {
        // A date is not part of a phone number. One question now asks for both,
        // so "76643421 and 1 January 2002" arrives as a single answer - and
        // every digit of the date would otherwise be absorbed into the number,
        // storing a thirteen digit mobile the patient never gave.
        $value = $this->withoutBirthDate($value);

        // Dictated numbers arrive as words: "one two one two one two".
        $digits = preg_replace('/\D+/', '', $this->wordsToNumbers(mb_strtolower(trim($value))));

        if (strlen($digits) < self::MOBILE_MIN_DIGITS || strlen($digits) > self::MOBILE_MAX_DIGITS) {
            return null;
        }

        // The register API's own rule, applied here so the patient hears about
        // it now rather than after answering three more questions.
        if (preg_match('/^(?!0{2})0?[0-9]+$/', $digits) !== 1) {
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
    /**
     * Everything known about an email address the patient just gave.
     *
     * cleanEmail() answers "what address is in these words". This answers the
     * questions that decide what to do about it: is it well formed, how much of
     * the patient's words survived into it, and does the domain look like a
     * near miss for a common one. Nothing here changes the address - a
     * suggestion is only ever offered for the patient to confirm.
     *
     * @return array{raw: string, email: ?string, valid: bool, confidence: float,
     *               suggestion: ?string, uncertain: ?string}
     */
    private function captureEmail(string $spoken): array
    {
        $result = [
            'raw' => trim($spoken),
            'email' => null,
            'valid' => false,
            'confidence' => 0.0,
            'suggestion' => null,
            'uncertain' => null,
        ];

        $email = $this->cleanEmail($spoken);

        if ($email === null || !$this->emailShapeValid($email)) {
            return $result;
        }

        $result['email'] = $email;
        $result['valid'] = true;
        $result['confidence'] = 1.0;

        // Words were spoken that did not survive into the address. "john
        // smith@gmail.com" quietly became "smith@gmail.com" - the patient would
        // have been given an address they never said.
        if ($this->emailDroppedWords($spoken, $email)) {
            $result['confidence'] = 0.4;
            $result['uncertain'] = 'local';

            return $result;
        }

        // A domain one or two letters away from a common one: "gamil.com".
        $domain = substr($email, (int) strrpos($email, '@') + 1);
        $suggested = $this->suggestEmailDomain($domain);

        if ($suggested !== null) {
            $result['confidence'] = 0.7;
            $result['uncertain'] = $domain;
            $result['suggestion'] = substr($email, 0, (int) strrpos($email, '@') + 1) . $suggested;
        }

        return $result;
    }

    /**
     * Shape checks the extraction regex alone does not make.
     */
    private function emailShapeValid(string $email): bool
    {
        if ($email === '' || mb_strlen($email) > 254 || str_contains($email, ' ')) {
            return false;
        }

        if (substr_count($email, '@') !== 1 || str_contains($email, '..')) {
            return false;
        }

        [$local, $domain] = explode('@', $email);

        if ($local === '' || mb_strlen($local) > 64 || $domain === '') {
            return false;
        }

        // A domain needs a dot and a plausible top level: "john@gmail" is not
        // an address a practice can write to.
        return preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}$/', $domain) === 1;
    }

    /**
     * Whether the patient said words that never reached the address.
     *
     * Only the local part is checked: a word immediately before it means the
     * extraction started mid-phrase and threw the beginning away.
     */
    private function emailDroppedWords(string $spoken, string $email): bool
    {
        $local = substr($email, 0, (int) strpos($email, '@'));
        $haystack = mb_strtolower($spoken);
        $position = mb_strpos($haystack, $local . '@');

        if ($position === false) {
            // The address was assembled from spoken words rather than lifted
            // whole, which the normaliser is there to do.
            return false;
        }

        $before = rtrim(mb_substr($haystack, 0, $position));

        return $before !== '' && preg_match('/[a-z0-9._%+-]$/', $before) === 1;
    }

    /**
     * A common domain this one is probably a mis-hearing of, or null.
     */
    private function suggestEmailDomain(string $domain): ?string
    {
        $known = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com',
            'yopmail.com', 'live.com', 'aol.com', 'gmx.at', 'gmx.de', 'web.de'];

        if (in_array($domain, $known, true)) {
            return null;
        }

        foreach ($known as $candidate) {
            $distance = levenshtein($domain, $candidate);

            // One or two letters out on a domain of a reasonable length. Short
            // domains are left alone: "web.de" is two edits from "gmx.de".
            if ($distance > 0 && $distance <= 2 && mb_strlen($candidate) >= 8) {
                return $candidate;
            }
        }

        return null;
    }

    /** j***@gmail.com - enough to debug with, not enough to identify anyone. */
    private function maskEmail(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return '(none)';
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1) . '***@' . $domain;
    }

    private function cleanEmail(string $value): ?string
    {
        $value = mb_strtolower(trim($value));

        // Spelled out letter by letter - "y o p m a i l dot com" - which is
        // exactly what people do once the engine has misheard the domain twice.
        // Each letter comes back as its own word; without joining them the
        // address is either rejected outright or, worse, silently reduced to
        // its last letter ("p r i t i" became "i").
        $value = preg_replace_callback(
            '/(?<![a-z0-9])(?:[a-z]\s+){1,}[a-z](?![a-z0-9])/u',
            static fn (array $m): string => preg_replace('/\s+/', '', $m[0]),
            $value
        );

        // Longest first: str_replace works through these in order, so
        // "at the rate of" must be spent before "at the" or "at".
        $spoken = [
            ' at the rate of ' => '@',
            ' at the rate ' => '@',
            ' at rate of ' => '@',
            ' at rate ' => '@',
            ' at sign ' => '@',
            ' at symbol ' => '@',
            ' at the ' => '@',
            ' underscore ' => '_',
            ' hyphen ' => '-',
            ' dash ' => '-',
            ' plus ' => '+',
            ' dot ' => '.',
            ' period ' => '.',
            ' full stop ' => '.',
            ' at ' => '@',
        ];

        // Providers are often transcribed as two words ("g mail", "hot mail").
        // This is speech repair only - any valid domain still passes below.
        $providers = [
            ' g mail' => ' gmail', ' gee mail' => ' gmail', ' google mail' => ' gmail',
            ' hot mail' => ' hotmail', ' out look' => ' outlook', ' outlook mail' => ' outlook',
            ' yop mail' => ' yopmail', ' yahoo mail' => ' yahoo', ' ya hoo' => ' yahoo',
            ' i cloud' => ' icloud', ' eye cloud' => ' icloud',
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

        $formats = ['d.m.Y', 'j.n.Y', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'Y-m-d', 'Y/m/d',
            'j F Y', 'j M Y', 'F j Y', 'M j Y'];

        // People answer in sentences: "my birth date is 1 January 1992". Each
        // candidate is the date lifted out of that, so the same formats and the
        // same plausibility check below decide, exactly as before.
        foreach ($this->birthDateCandidates($value) as $candidate) {
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat('!' . $format, $candidate);
                } catch (\Throwable $exception) {
                    continue;
                }

                if ($date && !$this->overflowed() && $this->isPlausibleBirthDate($date)) {
                    return $date;
                }
            }
        }

        // Last resort, and only on a candidate rather than the whole sentence:
        // Carbon happily reads a stray number out of surrounding words.
        foreach ($this->birthDateCandidates($value) as $candidate) {
            try {
                $date = Carbon::parse($candidate);
            } catch (\Throwable $exception) {
                continue;
            }

            if ($date && !$this->overflowed() && $this->isPlausibleBirthDate($date)) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Whether the date just parsed was rolled over rather than read.
     *
     * PHP accepts "31 February" and quietly returns 2 March. A birth date the
     * patient never gave is worse than asking them again, so an overflow is
     * treated as no date at all.
     */
    private function overflowed(): bool
    {
        $errors = Carbon::getLastErrors();

        return is_array($errors)
            && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0);
    }

    /**
     * The date inside whatever the patient said, most specific first.
     *
     * Returns the whole answer as well, so a plain "27.03.1993" is unaffected.
     *
     * @return array<int, string>
     */
    private function birthDateCandidates(string $value): array
    {
        $tidy = function (string $candidate): string {
            $candidate = str_replace(',', ' ', mb_strtolower($candidate));
            // "1st January 1992" - the suffix is not part of any format.
            $candidate = preg_replace('/\b(\d{1,2})(st|nd|rd|th)\b/u', '$1', $candidate);

            return trim(preg_replace('/\s+/', ' ', $candidate));
        };

        $candidates = [];
        $patterns = array_merge(
            // Only here, not in the shared list: a two digit year is enough to
            // read a date out of an answer that is meant to be one, but not
            // enough to cut a span out of an answer that may be a phone number.
            ['/\b\d{1,2}\s*[.\/-]\s*\d{1,2}\s*[.\/-]\s*\d{2,4}\b/u'],
            $this->birthDatePatterns()
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value, $matches) === 1) {
                $candidates[] = $tidy(str_replace(['.', '/', '-'], ['.', '/', '-'], $matches[0]));
            }
        }

        $candidates[] = $tidy($value);

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * The shapes a written date takes, each carrying a full four digit year.
     *
     * The year is what makes a span safe to cut out of a longer answer: a
     * phone number does not contain 18xx, 19xx or 20xx between separators, so
     * removing these leaves a real number whole. Deliberately narrower than
     * the shapes birthDateCandidates() will read a date from.
     *
     * @return array<int, string>
     */
    private function birthDatePatterns(): array
    {
        return [
            // 01.01.1992, 01/01/1992, 01-01-1992
            '/\b\d{1,2}\s*[.\/-]\s*\d{1,2}\s*[.\/-]\s*(?:18|19|20)\d{2}\b/u',
            // 1992-01-01
            '/\b(?:18|19|20)\d{2}\s*[.\/-]\s*\d{1,2}\s*[.\/-]\s*\d{1,2}\b/u',
            // 1 January 1992, 1st Jan 1992
            '/\b\d{1,2}(?:st|nd|rd|th)?\s+[a-z]+\.?\s+(?:18|19|20)\d{2}\b/iu',
            // January 1, 1992
            '/\b[a-z]+\.?\s+\d{1,2}(?:st|nd|rd|th)?\s*,?\s*(?:18|19|20)\d{2}\b/iu',
        ];
    }

    /**
     * The answer with any written date taken out of it.
     *
     * Used to keep a date's digits out of a phone number. The span goes
     * whether or not it reads as a *plausible* birth date - "1 January 2099"
     * is a date the patient meant as one, and letting its digits fall into the
     * mobile number would be worse than asking them for the date again.
     */
    private function withoutBirthDate(string $value): string
    {
        return trim(preg_replace($this->birthDatePatterns(), ' ', $value));
    }

    /**
     * Whether the patient appears to have attempted a date at all.
     *
     * Tells "they only gave a number" apart from "they gave a date that cannot
     * be right", so the second case can be met with the date question again
     * rather than a silent skip.
     */
    private function mentionsDate(string $value): bool
    {
        foreach ($this->birthDatePatterns() as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * The mobile number and date of birth inside one answer.
     *
     * The patient is asked for both together, so an answer may hold either or
     * both: "76643421 and 1 January 2002", "my mobile is 76643421", or just
     * the date. Splitting is all this does - each part is then handed to the
     * SAME validator it has always gone through, so nothing is accepted here
     * that would not have been accepted when the two were asked separately.
     *
     * @return array{mobile: ?string, birth_date: ?Carbon, tried_date: bool}
     */
    private function splitIdentity(string $text): array
    {
        return [
            'mobile' => $this->cleanMobile($text),
            'birth_date' => $this->parseBirthDate($text),
            'tried_date' => $this->mentionsDate($text),
        ];
    }

    /**
     * A full name split into the two fields the register API stores.
     *
     * cleanName() decides whether this is a name at all - sentences, requests
     * and pleasantries are refused there exactly as before. This only decides
     * where to cut: the first word is the given name and everything after it
     * the surname, so "van der Berg" survives as one surname.
     *
     * @return array{first: ?string, last: ?string}
     */
    private function splitName(string $text): array
    {
        $name = $this->cleanName($text);

        if ($name === null) {
            return ['first' => null, 'last' => null];
        }

        $words = preg_split('/\s+/u', $name);
        $first = array_shift($words);

        return ['first' => $first, 'last' => $words === [] ? null : implode(' ', $words)];
    }

    /**
     * What the assistant says when an answer is not a name.
     *
     * Repeating the request mid-registration is common. Saying "I didn't catch
     * that" to someone who simply said "book an appointment" again reads as if
     * the assistant has forgotten what it is doing.
     */
    private function notAName(string $text, string $field): array
    {
        if ($this->wantsBooking($text)) {
            return $this->say("We're already booking that - I just need your " . $field . ' to carry on.');
        }

        return $this->say("Sorry, I didn't catch that. Could you tell me your " . $field . ' again?');
    }

    /**
     * What happens once the mobile number and date of birth are both known.
     *
     * Mobile + date of birth is exactly how the register API decides a patient
     * already exists, so this is where a returning patient is recognised and
     * spared the details PureMed already holds. Shared by the combined question
     * and the follow-up one, so identification happens in one place however the
     * two answers arrived.
     */
    private function identifyPatient(array &$state, PureMedApiClient $client, PatientAuthenticator $authenticator): array
    {
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
            // Both are asked for again, so clear the pair rather than leaving a
            // date of birth behind that would shorten the question to the
            // number alone.
            unset($state['patient']['mobile_no'], $state['patient']['birth_date']);

            return [$this->say("I couldn't find any records for that mobile number and date of birth. Could you check the number for me?", 'error')];
        }

        // Not a patient the practice holds, so the rest of the details the
        // register API requires are collected from here on.
        $state['step'] = $this->nextRegistrationStep('birth_date');

        return [$this->say("Thanks. I'll just need a few details to set you up.")];
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

    /* -----------------------------------------------------------------
    |  Natural language fallback
    |
    |  Grok is an interpretation layer only. It is asked what a sentence
    |  meant, and its answer is turned into the SAME plain text the existing
    |  matchers already accept - so it can never select a doctor, type or slot
    |  that PureMed did not return, and never reaches the booking APIs.
    ------------------------------------------------------------------*/

    /**
     * Steps allowed to use NLU.
     *
     * Registration steps are absent by design: those answers are patient
     * identifying information and must not leave this server. The service
     * asserts the same list independently.
     */
    private function nluEligible(string $step): bool
    {
        return in_array($step, [
            'intent',
            'doctor',
            'appointment_type',
            'slot_date',
            'slot_time',
            'confirm',
            'cancel_select',
            'cancel_confirm',
            'appointments',
        ], true);
    }

    /**
     * The option labels for the current question - never ids, never patient data.
     *
     * @return array<int, string>
     */
    private function nluOptions(array $state): array
    {
        return match ($state['step']) {
            'doctor' => array_column($this->doctorCards($state['doctors']), 'title'),
            'appointment_type' => array_column($this->typeCards($state['appointment_types']), 'title'),
            'slot_date' => array_column($this->dateCards($state['slots']), 'title'),
            'slot_time' => array_column($this->timeCards($state['slots'], $state['slot_date']), 'title'),
            'confirm' => ['Yes, book it', 'Pick another time'],
            'cancel_select' => array_column($this->appointmentCards($state['cancellable']), 'title'),
            'cancel_confirm' => ['Yes, cancel it', 'No, keep it'],
            'appointments' => ['Book an appointment', 'Cancel an appointment', 'Past appointments'],
            default => [],
        };
    }

    /**
     * What the patient has chosen so far, as the labels already on their screen.
     *
     * Without this the model cannot tell "another doctor" from "another time":
     * at the slot step it sees a list of clock times and nothing else.
     *
     * This is the ONLY builder of NLU context, and it is an allowlist: it reads
     * four named fields and returns display strings. The sensitive state fields
     * - patient, patient_id, token, pending_email - are not reachable from here,
     * and no id is ever included, not even the doctor's or the appointment
     * type's, because the model resolves nothing.
     *
     * @return array{doctor?: string, appointment?: string, date?: string, time?: string}
     */
    private function nluContext(array $state): array
    {
        $doctor = $state['doctor'] ?? null;
        $type = $state['appointment_type'] ?? null;
        $slot = $state['slot'] ?? null;

        return array_filter([
            // doctorDisplay, so the label reads exactly as the option list did.
            'doctor' => is_array($doctor) && $this->doctorName($doctor) !== ''
                ? $this->doctorDisplay($doctor)
                : null,
            'appointment' => is_array($type) ? ($type['name'] ?? null) : null,
            'date' => $state['slot_date'] ?? null,
            'time' => is_array($slot) ? ($slot['time'] ?? null) : null,
        ], static fn ($value) => is_string($value) && trim($value) !== '');
    }

    /**
     * Turn the interpretation into the plain text the existing matchers take.
     *
     * Returns a short sequence, because one sentence can answer two questions:
     * "tomorrow morning" picks the day, then the time.
     *
     * @param  array<string, mixed>  $nlu
     * @return array<int, string>
     */
    private function nluInput(string $step, array $nlu): array
    {
        $intent = (string) ($nlu['intent'] ?? '');
        $entity = $nlu['entity'] ?? null;
        $ordinal = $nlu['ordinal'] ?? null;

        if ($step === 'intent') {
            return match ($intent) {
                'book_appointment' => ['book appointment'],
                'view_appointments' => ['my appointments'],
                'cancel_appointment' => ['cancel my appointment'],
                default => [],
            };
        }

        // request_slots is handled by the controller, not by replaying text.
        // "anything available Friday afternoon" is also a slot request, but it
        // names a day: fall through so the day is applied instead of being
        // dropped, because showing that day's times IS showing the slots.
        if ($intent === 'request_slots') {
            $named = !empty($nlu['date']) || !empty($nlu['time']) || !empty($nlu['time_preference']);

            if (!$named || !in_array($step, ['slot_date', 'slot_time'], true)) {
                return ['show me the available times'];
            }
        }

        // A change request is replayed as the phrase the existing escape hatches
        // already understand. Only the part the patient named is changed - the
        // rest of their selection is left alone by these phrases.
        if ($intent === 'change_doctor') {
            // Only the swap is replayed. A day said in the same breath is kept
            // from the patient's original message further up, so replaying it
            // here as well would answer the same sentence twice and repeat the
            // question back at them.
            return ['another doctor'];
        }

        if ($intent === 'change_appointment_type') {
            return ['change appointment type'];
        }

        if ($step === 'confirm' || $step === 'cancel_confirm') {
            return match ($intent) {
                'confirm_booking' => ['yes'],
                'reject_booking', 'change_slot', 'change_date' => ['no'],
                default => [],
            };
        }

        // "another time" must not disturb the chosen day, so it is replayed as a
        // request to see that day's times again.
        if ($intent === 'change_slot' && empty($nlu['date']) && empty($nlu['time'])
            && empty($nlu['time_preference'])) {
            return ['show me the available times'];
        }

        // Slot steps can carry a day and a time in one sentence.
        if ($step === 'slot_date' || $step === 'slot_time') {
            $inputs = [];

            if (!empty($nlu['date'])) {
                $inputs[] = (string) $nlu['date'];
            }

            if (!empty($nlu['time'])) {
                $inputs[] = (string) $nlu['time'];
            } elseif (!empty($nlu['time_preference'])) {
                $inputs[] = (string) $nlu['time_preference'];
            } elseif ($ordinal !== null) {
                $inputs[] = $this->ordinalWord((int) $ordinal);
            } elseif ($entity !== null) {
                $inputs[] = (string) $entity;
            }

            return $inputs;
        }

        // Doctor, appointment type, cancel_select, appointments.
        if ($entity !== null) {
            return [(string) $entity];
        }

        if ($ordinal !== null) {
            return [$this->ordinalWord((int) $ordinal)];
        }

        return [];
    }

    /** The existing matchers understand position words, not raw indexes. */
    private function ordinalWord(int $position): string
    {
        $words = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth',
            6 => 'sixth', 7 => 'seventh', 8 => 'eighth', 9 => 'ninth', 10 => 'tenth'];

        return $words[$position] ?? ('number ' . $position);
    }

    private function state(): array
    {
        $stored = session(self::SESSION_KEY, []);

        // Started under an older flow, so the step it holds may no longer mean
        // what it did. Nothing here is worth salvaging - it is one unfinished
        // conversation, not patient data - so drop it and open cleanly on
        // 'intent'. The undo snapshot goes with it: that holds a state of the
        // same old shape, and restoring it would put the patient right back.
        if ($stored !== [] && ($stored['flow'] ?? 0) !== self::FLOW_VERSION) {
            session()->forget(self::SESSION_KEY);
            session()->forget(self::UNDO_KEY);
            $stored = [];
        }

        return array_merge($this->freshState(), $stored);
    }

    private function freshState(): array
    {
        return [
            // Which flow this conversation began under. See FLOW_VERSION.
            'flow' => self::FLOW_VERSION,
            'step' => 'intent',
            'goal' => 'book',
            'chip_page' => 0,
            'patient' => [],
            'pending_email' => null,
            // What was heard, when it differs from what is being suggested.
            'pending_email_heard' => null,
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
            // Set only while a "change the time" request is in flight: the
            // cancellation is confirmed first, then a new time is chosen.
            'rebook_after_cancel' => false,
            'appointment_list' => [],
            // The appointments just listed, kept so follow-up questions - "when
            // is the second one?" - are answered from the real PureMed data
            // rather than asked for again. Cleared when the patient moves on to
            // booking or cancelling, or starts a new session.
            'appointments_context' => [],
            // Which of them was last talked about, so "the other one" has
            // something to be relative to.
            'discussed_appointment' => 0,
            // The practice's bookable window for the doctor and appointment
            // type currently chosen, so it is read once rather than on every
            // slot fetch. Keyed by both, so changing either re-reads it.
            'booking_window' => null,
            // The appointment cancelled in this conversation, kept so it can be
            // offered again without asking for the same four answers.
            'last_cancelled' => null,
            'slot_hint' => null,
            'slot_window' => null,
            'last_assistant' => null,
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
