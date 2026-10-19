<?php

namespace App\Http\Controllers\AiAssistant;

use App\Http\Controllers\Controller;
use App\Services\AiAssistant\ConsentRules;
use App\Services\AiAssistant\TurnTimer;
use App\Services\AiAssistant\NluManager;
use App\Services\AiAssistant\PatientAuthenticator;
use App\Services\AiAssistant\OtpService;
use App\Services\AiAssistant\PureMedApiClient;
use App\Services\AiAssistant\Translator;
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
     * 4: a new patient verifies a code before anything about them is created
     * 5: a returning patient does too, and a signed-in session carries a proof
     *    that it was verified. Older conversations hold an identity with no such
     *    proof and are dropped rather than trusted.
     */
    private const FLOW_VERSION = 7;

    /** Asked when "Other" is chosen on the country question. */
    private const COUNTRY_OTHER_PROMPT = 'Please type your country code, for example +91 or 0091.';

    /** Said when the country question could not read the answer. */
    private const COUNTRY_REASK = "I didn't catch the country. Please choose one from the list, or say \"other\".";

    /** Said when a typed country code is not one. */
    private const COUNTRY_CODE_REASK = "That doesn't look like a country code. It starts with + or 00, for example +91 or 0091.";

    /** Said when the services step could not read the answer. */
    private const SERVICES_REASK = 'Please tick the services you would like and press Continue, or say "all", "none" or "continue".';

    /**
     * Where the one-turn undo snapshot lives.
     *
     * Exactly one snapshot is kept - the state as it was before the patient's
     * most recent message - because only that message can be corrected.
     */
    private const UNDO_KEY = 'ai_assistant.undo';

    /**
     * The language the patient chose, kept apart from the conversation state so
     * that starting a new patient does not switch the language back.
     */
    private const LANGUAGE_KEY = 'ai_assistant.language';

    /**
     * Steps reached only by a completed PureMed write.
     *
     * A booking has been made or an appointment has been cancelled at the
     * practice. Nothing on this side may suggest that can be taken back, so no
     * snapshot survives into or out of these steps.
     */
    private const TERMINAL_STEPS = ['done', 'cancelled'];

    /** What the patient is told when the AI service cannot be used. */
    private const AI_UNAVAILABLE = "My AI helper is temporarily unavailable, so I couldn't understand that. "
        . 'Please tap one of the options, try again in a moment, or call the practice.';

    /** Said after a goodbye, which has already been worded. */
    private const ENDED_NOTICE = 'This conversation is now over and your details have been cleared. This screen will reset shortly.';

    /** The last thing said after a booking, when the conversation ends there. */
    private const GOODBYE = 'Thank you - have a great day! This conversation is now over and your details have been cleared. '
        . 'This screen will reset shortly.';

    /** When codes cannot be checked or sent at all - never a way in. */
    private const OTP_UNAVAILABLE = 'I cannot check verification codes right now. Please try again in a moment or call the practice.';

    /** What the patient is told when the AI is rate limited. */
    private const AI_BUSY = "My AI helper is very busy right now, so I couldn't understand that. "
        . 'Please tap one of the options or try again in a moment.';

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
        // German requests and pleasantries, for the same reason.
        'ja', 'nein', 'termin', 'termine', 'buchen', 'hallo', 'danke', 'bitte', 'arzt', 'ärztin',
        'heute', 'morgen', 'absagen', 'ändern', 'hilfe', 'ich', 'möchte', 'moechte', 'brauche',
        'einen', 'eine', 'für', 'und', 'zum',
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
        // The email comes before the name so the verification code has
        // somewhere to go besides the mobile: a patient whose SMS does not
        // arrive can read the same code in their inbox. This is also the order
        // the practice's own web booking uses for someone it does not know.
        'email',
        'first_name',
        'last_name',
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
        NluManager $grok,
        OtpService $otp
    ): JsonResponse {
        $request->validate([
            'text' => ['nullable', 'string', 'max:500'],
            'choice' => ['nullable', 'array'],
            'choice.type' => ['nullable', 'string', 'max:40'],
            'choice.value' => ['nullable', 'string', 'max:120'],
            'start' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'in:voice,text'],
            'lang' => ['nullable', 'string', 'in:en,de'],
        ]);

        $this->rememberLanguage($request);

        $timer = TurnTimer::current();
        $timing = (bool) config('ai-assistant.timing_log', true);
        $shortcut = null;

        if ($timing) {
            $timer->begin();
        }

        $state = $this->state();
        $replies = [];
        $stepBefore = $state['step'];
        $starting = $request->boolean('start');

        $text = trim((string) $request->input('text', ''));
        $choiceValue = (string) $request->input('choice.value', '');
        $choiceType = (string) $request->input('choice.type', '');
        $source = (string) $request->input('source', '');

        // A chip is the answer to the question it was drawn under. The two
        // confirmation questions draw their buttons as type "confirm"; a "yes"
        // that arrives labelled as anything else - a stale button, or one from
        // another question - is not an answer to this one, and is dropped
        // rather than read as consent.
        if ($choiceValue !== '' && in_array($state['step'], ['confirm', 'cancel_confirm'], true) && $choiceType !== 'confirm') {
            $choiceValue = '';
        }

        // And the read-back before registration: its buttons are only read there.
        if ($choiceValue !== '' && (($state['step'] === 'details_confirm') !== ($choiceType === 'details_confirm'))) {
            $choiceValue = '';
        }

        // The same for the services tick list: its submission means something
        // only on the services step, and nothing else is read as ticks there.
        foreach (['services', 'country'] as $chipStep) {
            if ($choiceValue !== '' && (($state['step'] === $chipStep) !== ($choiceType === $chipStep))) {
                $choiceValue = '';
            }
        }

        if ($starting) {
            // Open on intent, not on a field - the patient says what they want
            // before the assistant asks for anything.
            if ($state['step'] === 'intent' && empty($state['patient'])) {
                $replies[] = $this->say("Hi, I'm your PureMed Assistant.");
            } elseif ($state['step'] === 'otp_failed') {
                // A dead end - the code could not be sent, or the limit was hit -
                // with nothing in it to resume: nobody was verified and nothing
                // was created. Resuming it only showed "please call the practice"
                // again, for the rest of the session, even once the wait was over.
                // Start clean instead. The limits are kept per mobile number on
                // the server, so this gets round none of them: the same number is
                // refused again, with the time left.
                $state = $this->freshState();
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

            // Resuming on the appointment list means re-reading it, not
            // replaying it. What is in the session was true when it was
            // written; the practice may have cancelled or booked something
            // since, on another device or at the desk. PureMed answers, and
            // nothing here judges the reply - loadAppointmentList() stores
            // whatever get-appointment returns, exactly as it does when the
            // patient asks for the list themselves.
            if ($state['step'] === 'appointments' && $state['patient_id'] && $state['token']) {
                $replies = array_merge($replies, $this->loadAppointmentList($state, $client));
            }
        } else {
            $stateBefore = $state;
            $language = $this->language();

            // Free text on a step the AI may interpret. A chip is exactly what
            // the patient meant, so it never goes to the AI, and registration
            // steps are not on the eligible list at all.
            $aiEligible = $text !== '' && $choiceValue === '' && $this->nluEligible($stepBefore);

            // AI first, for both languages, unless a deterministic rule owns
            // the message (a plain yes or no, "start over", "show me more",
            // "are there other doctors", a follow-up about the appointment list,
            // an ambiguous number...). Those need no model, and a model could
            // only get them wrong.
            //
            // ai_first = false is the rollback: English goes back to trying the
            // rules first, German stays as it has always been - AI first, since
            // the rules only know English.
            $ruleOwned = $aiEligible && $this->deterministicShortcut($stepBefore, $text, $stateBefore);
            $aiFirst = $aiEligible && $this->aiFirst($language) && !$ruleOwned;

            $nlu = null;
            $aiTried = false;
            $replies = null;
            $rulesOwned = false;

            // An answer that names one option exactly needs no AI: the rules are
            // given that option's own label, as they are when the AI has picked
            // it. If that does not move the conversation on, nothing is lost -
            // the turn starts again on the ordinary path below.
            if ($aiEligible && ($direct = $this->selectionShortcut($stepBefore, $text, $stateBefore, $language)) !== null) {
                $replies = $this->replayInterpretation([$direct], $stateBefore, $state, $source, $client, $authenticator, $otp);

                if ($state['step'] === $stepBefore && empty($state['_handled']) && empty($state['_no_prompt'])) {
                    $state = $stateBefore;
                    $replies = null;
                } else {
                    $shortcut = 'selection';
                    $ruleOwned = true;
                }
            }

            if ($ruleOwned && $shortcut === null) {
                $shortcut = 'rule';
            }

            if ($aiFirst && $replies === null) {
                $aiTried = true;
                $clock = $timer->now();
                $nlu = $grok->interpret(
                    $stepBefore,
                    $this->nluOptions($stateBefore),
                    $text,
                    // One line of context: the question the patient is replying
                    // to. Never the transcript, never patient details.
                    $stateBefore['last_assistant'] ?? null,
                    $this->nluContext($stateBefore),
                    $language
                );
                $timer->ai($clock, $grok->attempts(), $grok->outcome());

                $retries = $nlu ? $this->nluInput($stepBefore, $nlu) : [];

                if ($retries) {
                    $replies = $this->replayInterpretation($retries, $stateBefore, $state, $source, $client, $authenticator, $otp);

                    if ($state['step'] === $stepBefore && empty($state['_handled']) && empty($state['_no_prompt'])) {
                        // The interpretation did not move the conversation on,
                        // so it did not help. Start the turn again on the rules.
                        $state = $stateBefore;
                        $replies = null;
                    } elseif (empty($state['slot_hint'])) {
                        // "Dr Patel tomorrow morning" names a day as well as a
                        // doctor. The rules read the day from the patient's own
                        // words when they can (English); the AI's reading of it
                        // covers what they cannot (German).
                        $hint = $language === 'de'
                            ? ($this->interpretedSlotHint($nlu) ?: null)
                            : ($this->slotHint($text) ?: $this->interpretedSlotHint($nlu));

                        if ($hint) {
                            $state['slot_hint'] = $hint;
                        }
                    }
                }
            }

            if ($replies === null) {
                $state = $stateBefore;
                $replies = $this->handleAnswer($text, $choiceValue, $source, $state, $client, $authenticator, $otp);

                // A handler can answer without moving the conversation on -
                // showing the slot list again, for instance. That is a complete
                // answer, so the AI must not be asked to interpret the same
                // message a second time: one message, one response path.
                $rulesOwned = !empty($state['_handled']);
                unset($state['_handled']);

                // Rules first (ai_first = false, English): the rules have just
                // failed to move the conversation on, so ask the AI what the
                // sentence meant and run THE SAME matchers on its answer. The AI
                // never resolves anything itself - it only rephrases the patient
                // into something the existing matchers already understand.
                // Not for a message a deterministic rule owns: "start over" can
                // leave the step where it was, but it has been answered.
                if (!$aiTried
                    && !$rulesOwned
                    && !$ruleOwned
                    && $state['step'] === $stepBefore
                    && $aiEligible) {
                    $aiTried = true;

                    $clock = $timer->now();
                    $nlu = $grok->interpret(
                        $stepBefore,
                        $this->nluOptions($stateBefore),
                        $text,
                        $stateBefore['last_assistant'] ?? null,
                        $this->nluContext($stateBefore),
                        $language
                    );
                    $timer->ai($clock, $grok->attempts(), $grok->outcome());
                    $retries = $nlu ? $this->nluInput($stepBefore, $nlu) : [];

                    if ($retries) {
                        $replies = $this->replayInterpretation($retries, $stateBefore, $state, $source, $client, $authenticator, $otp);

                        // The retry replays a normalised phrase such as "Dr
                        // Gunnar Gauff", so anything else said in the same
                        // breath - "for Friday" - would be lost. Keep it for
                        // the slot step.
                        if (empty($state['slot_hint']) && ($hint = $this->slotHint($text))) {
                            $state['slot_hint'] = $hint;
                        }
                    }
                }
            }

            // The AI could not be used and nothing else moved the conversation
            // on. Saying so beats a re-ask that blames the patient for being
            // misunderstood - but ONLY then: if the rules answered, or the turn
            // went somewhere, there is nothing to apologise for. A message the
            // AI merely could not read ("unrecognized") is not an outage either.
            // Buttons keep working in every case.
            $answered = $rulesOwned || !empty($state['_handled']) || !empty($state['_no_prompt']);

            if ($aiTried && $grok->unavailable() && $state['step'] === $stepBefore && !$answered) {
                $replies = [$this->say(
                    $grok->outcome() === 'rate_limited' ? self::AI_BUSY : self::AI_UNAVAILABLE,
                    'error'
                )];
            }
        }

        // "Show more" only extends the chip list; repeating the question under it
        // would read like the assistant forgot it had asked.
        $suppressPrompt = !empty($state['_no_prompt']);

        // A booking was confirmed, or the patient was said goodbye to, and the
        // conversation ends with it. Which one decides what is said.
        $endKind = (string) ($state['_ended'] ?? '');
        $ended = !$starting && $endKind !== '';

        // A consent only lasts for the answer that gave it.
        unset($state['_no_prompt'], $state['_handled'], $state['_consent'], $state['_ended']);

        // A new question starts from the first page of chips again.
        if ($state['step'] !== $stepBefore) {
            $state['chip_page'] = 0;
        }

        $this->syncServices($state);

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
        if (!$rejected && !$alreadyDone && !$suppressPrompt && !$ended && trim((string) $prompt['text']) !== '') {
            $replies[] = $this->say($prompt['text']);
        }

        // "Is there anything else?" would invite a conversation that is over.
        // After a goodbye ("no thanks") the farewell has already been said, so
        // only the fact that it has ended is added.
        if ($ended) {
            $replies[] = $this->say($endKind === 'goodbye' ? self::ENDED_NOTICE : self::GOODBYE);
        }

        // Remember only the last thing the assistant said, so the next message
        // can be interpreted against the question it answers. Saved here rather
        // than earlier because the closing question is added just above.
        $last = end($replies);
        $state['last_assistant'] = is_array($last) ? ($last['text'] ?? null) : null;

        if ($ended) {
            // Nothing about this patient outlives the booking: not who they
            // are, not their token, not what they chose. The language they
            // picked is kept separately, so the next person starts in it.
            session()->forget(self::SESSION_KEY);
            session()->forget(self::UNDO_KEY);
        } else {
            $this->save($state);
            $this->rememberForEdit($starting, $text, $choiceValue, $source, $stateBefore ?? null, $state);
        }

        // Where the time went: durations, counts and operation names only.
        if ($timing) {
            Log::info('AI assistant timing: turn', $timer->summary($stepBefore, (string) $state['step'], $this->language(), $shortcut));
            $timer->end();
        }

        return response()->json($this->localize([
            'messages' => $replies,
            // Whether the browser may offer "edit" on the message just sent.
            // The server decides: the snapshot either exists or it does not.
            'can_edit' => !$ended && session()->has(self::UNDO_KEY),
            'options' => $alreadyDone ? null : ($prompt['options'] ?? null),
            // After the goodbye there is nothing left to type into.
            'input' => $ended
                ? ['enabled' => false, 'placeholder' => 'This conversation has ended.']
                : ($prompt['input'] ?? ['enabled' => true, 'placeholder' => 'Message PureMed Assistant']),
            // Set only when the conversation has ended: the page clears itself
            // and returns to the start screen after this many seconds.
            'ended' => $ended,
            'reset_after' => $ended ? max(5, (int) config('ai-assistant.end_reset_seconds', 15)) : null,
            // What the browser should narrate WHILE the next answer is being
            // processed. Sent ahead of time because the API calls happen before
            // the next response exists - otherwise the status would appear
            // after the wait instead of during it.
            'pending' => $this->pendingFor($state['step']),
            'step' => $state['step'],
            'done' => $state['step'] === 'done',
        ]));
    }

    /**
     * Should free text go to the AI before the rules?
     *
     * German always does - the rules only know English. English does while the
     * ai_first flag is on, and falls back to rules-first when it is off.
     */
    private function aiFirst(string $language): bool
    {
        return $language === 'de' || (bool) config('ai-assistant.ai_first', true);
    }

    /**
     * The label to give the rules when the patient's whole answer names exactly
     * one option on screen - or null, and the AI is asked as before.
     *
     * Only the four choosing steps; only when that step's list is really in the
     * conversation; only an exact label or a bare ordinal word inside the options
     * shown ("the second one", "die zweite") with nothing else in the sentence;
     * and only when the existing matcher, run on that label, picks the very same
     * option. Anything doubtful - a part of a name, "the other one", "not the
     * second", a day name, a number, a question - returns null.
     *
     * It decides only that the AI need not be asked. The rules then run in full,
     * so every check they make is still made.
     */
    private function selectionShortcut(string $step, string $text, array $state, string $language): ?string
    {
        if (!config('ai-assistant.selection_shortcuts', true)) {
            return null;
        }

        // A conversation that is not signed in as a patient has no business on
        // these steps; leave it to the ordinary checks.
        if (empty($state['patient_id']) || empty($state['token'])) {
            return null;
        }

        $spoken = $this->normalizeText($text);

        if ($spoken === '' || mb_strlen($text) > 60 || str_contains($text, '?') || count(explode(' ', $spoken)) > 8) {
            return null;
        }

        $date = null;

        switch ($step) {
            case 'doctor':
                $items = $state['doctors'] ?? [];
                $cards = $items ? $this->doctorCards($items) : [];
                break;

            case 'appointment_type':
                $items = $state['appointment_types'] ?? [];
                $cards = ($items && !empty($state['doctor'])) ? $this->typeCards($items) : [];
                break;

            case 'slot_date':
                $items = $state['slots'] ?? [];
                $cards = ($items && !empty($state['appointment_type'])) ? $this->dateCards($items) : [];
                break;

            case 'slot_time':
                $items = $state['slots'] ?? [];
                $date = $state['slot_date'] ?? null;
                $cards = ($items && $date && !empty($state['appointment_type'])) ? $this->timeCards($items, $date, $state['slot_window'] ?? null) : [];
                break;

            default:
                return null;
        }

        if (!$cards) {
            return null;
        }

        // Positions count from the first card; dates arrive keyed by date.
        $cards = array_values($cards);

        if ($step === 'doctor') {
            $items = array_values($items);
        }

        // What is on screen: the first page of chips, then "Show more".
        $visible = max(3, (int) config('ai-assistant.chips_per_page', 6)) * (1 + (int) ($state['chip_page'] ?? 0));

        $index = null;
        $position = $this->bareOrdinal($spoken, $language);

        if ($position !== null) {
            if ($position > min($visible, count($cards))) {
                return null;
            }

            $index = $position - 1;
        } else {
            $hits = [];

            foreach ($cards as $i => $card) {
                $names = [$this->normalizeText((string) $card['title'])];

                if ($step === 'doctor') {
                    $names[] = $this->normalizeText($this->doctorName($items[$i]));
                }

                if (in_array($spoken, $names, true)) {
                    $hits[] = $i;
                }
            }

            if (count($hits) !== 1) {
                return null;
            }

            $index = $hits[0];
        }

        $label = (string) $cards[$index]['title'];

        // The rules, on that label, must land on the same option.
        $agrees = match ($step) {
            'doctor', 'appointment_type' => ($m = $this->matchOption($items, '', $label)) && (string) $m['id'] === (string) $cards[$index]['value'],
            'slot_date' => $this->matchSlotDate($items, '', $label) === $cards[$index]['value'],
            'slot_time' => ($m = $this->matchSlot($items, $date, '', $label, $state['slot_window'] ?? null)) && ($m['time'] ?? null) === $cards[$index]['value'],
        };

        return $agrees ? $label : null;
    }

    /**
     * The position a patient names when the ordinal is their WHOLE answer:
     * "second", "the second one please", "I will take the third", and in German
     * "die zweite", "den zweiten Arzt", "ich nehme die dritte". Null for anything
     * with more in it - or less: "the last one", "number 2" and bare digits are
     * not trusted here.
     */
    private function bareOrdinal(string $spoken, string $language): ?int
    {
        if ($language === 'de') {
            $pattern = '/^(?:(?:ich nehme|ich nehm|ich möchte|ich moechte|ich hätte gern|ich haette gern|nehmen wir|dann) )?(?:(?:die|der|das|den|dem) )?'
                . '(erst|zweit|dritt|viert|fünft|fuenft|sechst|siebt|siebent|acht|neunt|zehnt)(?:e|er|en|es|em)'
                . '(?: (?:option|arzt|ärztin|aerztin|termin|uhrzeit|zeit|tag|datum))?(?: bitte)?$/u';
            $words = ['erst' => 1, 'zweit' => 2, 'dritt' => 3, 'viert' => 4, 'fünft' => 5, 'fuenft' => 5, 'sechst' => 6, 'siebt' => 7, 'siebent' => 7, 'acht' => 8, 'neunt' => 9, 'zehnt' => 10];
        } else {
            $pattern = '/^(?:(?:i (?:will|ll|d|would) (?:take|like|have|go with)|let s go with|go with|i take|take) )?(?:the )?'
                . '(first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth)'
                . '(?: (?:one|option|doctor|appointment|slot|time|day|date))?(?: please)?$/u';
            $words = ['first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5, 'sixth' => 6, 'seventh' => 7, 'eighth' => 8, 'ninth' => 9, 'tenth' => 10];
        }

        return preg_match($pattern, $spoken, $m) ? ($words[$m[1]] ?? null) : null;
    }

    /**
     * Is this message one a deterministic rule owns outright?
     *
     * For these the rules are exact and a model can only be a worse answer, a
     * wasted second and a slice of a shared token budget. Every check here is a
     * pure function of the text and the state: nothing is changed, nothing is
     * sent anywhere.
     *
     * Deliberately NOT here: picking a doctor, a day or a time in a sentence,
     * "another doctor", "show me the times". Those are the natural language the
     * AI is for; the rules remain the fallback if it cannot read them. The one
     * exception - an answer that IS an option's exact label or a bare ordinal -
     * is selectionShortcut(), which checks that the rules agree first.
     */
    private function deterministicShortcut(string $step, string $text, array $state): bool
    {
        // A bare, unmistakable yes or no to a yes/no question.
        if ($this->isPlainConfirmation($step, $text)) {
            return true;
        }

        if ($this->wantsRestart($text) || $this->wantsMore($text) || $this->abandonsBooking($text)) {
            return true;
        }

        // "Are there other doctors?" is answered from PureMed's own list.
        if ($step === 'doctor' && $this->asksForOtherDoctors($text)) {
            return true;
        }

        if ($step === 'appointments') {
            // "No thanks, that's all."
            if ($this->saidNothingElse($text)) {
                return true;
            }

            // "Past appointments".
            if ($this->wantsPast($text)) {
                return true;
            }

            // "What about the second one?" - answered from the list already
            // shown. Left to the AI it was read as a request to cancel.
            $held = array_values($state['appointments_context'] ?? []);

            if ($held && $this->appointmentReference($text, count($held), (int) ($state['discussed_appointment'] ?? 0)) !== null) {
                return true;
            }
        }

        // "11" at the time question: the clarification the rules ask - "did you
        // mean the 11th time, or 11 o'clock?" - is on purpose, and a model would
        // turn the bare number into a time or a position without asking.
        if ($step === 'slot_time' && $this->clarifyAmbiguousNumber($state, $text) !== null) {
            return true;
        }

        // A named clock time, and the answers to the questions asked about one,
        // are the rules' to settle: a model would pick a period for the patient.
        if ($step === 'slot_time' && $this->slotsOn($state['slots'] ?? [], $state['slot_date'] ?? null) !== [] && (
            !empty($state['offer_other_date'])
            || is_array($state['time_ambiguous'] ?? null)
            || $this->parseTimeRequest($text) !== null
        )) {
            return true;
        }

        return false;
    }

    /**
     * Run the phrases the AI came up with through the ordinary matchers.
     *
     * One sentence can answer two questions - "tomorrow morning" picks the day,
     * then the time - so this is a short sequence rather than a single answer.
     * It starts again from the state as the message found it, so nothing a
     * failed earlier attempt did is carried over.
     *
     * @param  array<int, string>  $retries
     * @return array<int, array>
     */
    private function replayInterpretation(
        array $retries,
        array $stateBefore,
        array &$state,
        string $source,
        PureMedApiClient $client,
        PatientAuthenticator $authenticator,
        ?OtpService $otp
    ): array {
        $replies = [];

        foreach ($retries as $index => $retry) {
            if ($index === 0) {
                $state = $stateBefore;   // discard the failed attempt cleanly
            }

            $before = $state['step'];
            $replies = array_merge(
                $replies,
                $this->handleAnswer($retry, '', $source, $state, $client, $authenticator, $otp)
            );

            // Stop as soon as a follow-up stops making progress.
            if ($state['step'] === $before) {
                break;
            }
        }

        return $replies;
    }

    /**
     * A bare, unmistakable yes or no to a yes/no question. The rules read it
     * exactly, so it never needs the AI.
     */
    private function isPlainConfirmation(string $step, string $text): bool
    {
        if (!in_array($step, ['confirm', 'cancel_confirm'], true)) {
            return false;
        }

        return app(ConsentRules::class)->isClearConsent($text, $this->language(), $step === 'cancel_confirm')
            || (mb_strlen($text) <= 30 && $this->saidNo('', $text));
    }

    /**
     * The day and time the AI heard in a booking request, in English terms.
     *
     * Kept so "Termin bei Dr. Patel morgen Vormittag" still carries the day and
     * the part of the day to the slot step once the doctor has been chosen -
     * the rules cannot read the German original.
     *
     * @param  array<string, mixed>|null  $nlu
     */
    private function interpretedSlotHint(?array $nlu): ?string
    {
        if (!$nlu) {
            return null;
        }

        $hint = trim(implode(' ', array_filter([
            $nlu['date'] ?? null,
            $nlu['time'] ?? ($nlu['time_preference'] ?? null),
        ])));

        return $hint === '' ? null : $hint;
    }

    /* -----------------------------------------------------------------
    |  Language
    ------------------------------------------------------------------*/

    /** 'en' or 'de' - what the patient chose, or the configured default. */
    private function language(): string
    {
        $chosen = session(self::LANGUAGE_KEY);
        $allowed = (array) config('ai-assistant.languages', ['en', 'de']);

        if (is_string($chosen) && in_array($chosen, $allowed, true)) {
            return $chosen;
        }

        $default = (string) config('ai-assistant.default_language', 'en');

        return in_array($default, $allowed, true) ? $default : 'en';
    }

    /** The page sends its language with every request; the latest one wins. */
    private function rememberLanguage(Request $request): void
    {
        $lang = (string) $request->input('lang', '');

        if (in_array($lang, (array) config('ai-assistant.languages', ['en', 'de']), true)) {
            session([self::LANGUAGE_KEY => $lang]);
        }
    }

    /**
     * Put a response into the patient's language on its way out.
     *
     * The conversation runs and stores everything in English; only what the
     * patient sees and hears is translated, and only here. Doctor names,
     * appointment types, dates and anything else PureMed supplied pass through
     * untouched because no translation matches them.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function localize(array $payload): array
    {
        $lang = $this->language();

        if ($lang === 'en') {
            return $payload;
        }

        $t = app(Translator::class);

        foreach ($payload['messages'] ?? [] as $i => $message) {
            if (is_array($message) && isset($message['text'])) {
                $payload['messages'][$i]['text'] = $t->translate((string) $message['text'], $lang);
            }
        }

        if (!empty($payload['options']) && is_array($payload['options'])) {
            if (isset($payload['options']['continue']) && is_string($payload['options']['continue'])) {
                $payload['options']['continue'] = $t->translate($payload['options']['continue'], $lang);
            }

            foreach ($payload['options']['items'] ?? [] as $i => $item) {
                foreach (['title', 'subtitle'] as $field) {
                    if (isset($item[$field]) && is_string($item[$field])) {
                        $payload['options']['items'][$i][$field] = $t->translate($item[$field], $lang);
                    }
                }
            }

            if (in_array($payload['options']['type'] ?? '', ['confirm', 'booked'], true)
                && is_array($payload['options']['summary'] ?? null)) {
                $summary = [];

                foreach ($payload['options']['summary'] as $key => $value) {
                    $summary[$t->summaryKey((string) $key, $lang)] = $value;
                }

                $payload['options']['summary'] = $summary;
            }
        }

        if (isset($payload['input']['placeholder'])) {
            $payload['input']['placeholder'] = $t->translate((string) $payload['input']['placeholder'], $lang);
        }

        if (!empty($payload['pending']) && is_array($payload['pending'])) {
            $payload['pending'] = array_map(fn ($line) => $t->translate((string) $line, $lang), $payload['pending']);
        }

        if (isset($payload['error'])) {
            $payload['error'] = $t->translate((string) $payload['error'], $lang);
        }

        return $payload;
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
        NluManager $grok,
        OtpService $otp
    ): JsonResponse {
        $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'source' => ['nullable', 'string', 'in:voice,text'],
            'lang' => ['nullable', 'string', 'in:en,de'],
        ]);

        $this->rememberLanguage($request);

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
        return $this->converse($request, $client, $authenticator, $grok, $otp);
    }

    private function editRefused(string $message): JsonResponse
    {
        session()->forget(self::UNDO_KEY);

        return response()->json($this->localize(['can_edit' => false, 'error' => $message]), 409);
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

    public function reset(Request $request): JsonResponse
    {
        $request->validate(['lang' => ['nullable', 'string', 'in:en,de']]);
        $this->rememberLanguage($request);

        session()->forget(self::SESSION_KEY);
        session()->forget(self::UNDO_KEY);

        $state = $this->state();
        $prompt = $this->promptFor($state);

        return response()->json($this->localize([
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
        ]));
    }

    /* -----------------------------------------------------------------
    |  Conversation
    ------------------------------------------------------------------*/

    /**
     * Validate the patient's answer for the current step and advance.
     *
     * @return array<int, array> assistant messages to show before the next question
     */
    private function handleAnswer(string $text, string $choiceValue, string $source, array &$state, PureMedApiClient $client, PatientAuthenticator $authenticator, ?OtpService $otp = null): array
    {
        // Optional so the many callers that only exercise the conversation can
        // stay as they are. Nothing is weakened by the default: the code is
        // required at the point the patient is CREATED, in
        // registerAndLoadDoctors(), not by whichever step happens to be next.
        $otp = $otp ?: app(OtpService::class);

        $text = trim($text);

        if ($text === '' && $choiceValue === '') {
            return [];
        }

        // "I typed the wrong number" before anyone is registered or verified:
        // nothing exists that the number could be tied to, so it can simply be
        // asked for again. (After registration there is no update call; the
        // ordinary "I can't change that" answer stands.)
        if ($choiceValue === ''
            && empty($state['patient_id'])
            && !empty($state['patient']['mobile_no'])
            && in_array($state['step'], ['country', 'email', 'email_confirm', 'otp_verify', 'first_name', 'last_name', 'gender', 'details_confirm', 'details_edit'], true)
            && $this->wantsToChangePhone($text)) {
            return $this->restartWithNewNumber($state);
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
            // Nothing left to reveal: say so, rather than "here are a few more"
            // over the very same chips.
            if ($choiceValue === '' && ($nothingMore = $this->nothingMoreToShow($state)) !== null) {
                $state['_no_prompt'] = true;
                $state['_handled'] = true;

                return [$this->say($nothingMore)];
            }

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

            // The chip is answered here rather than inside a step, so tapping
            // "Cancel an appointment" and saying it reach the same line. It is
            // a button, not a sentence: it needs no interpreting, and a chip
            // never reaches the NLU anyway. Either way the list is re-read from
            // PureMed rather than trusted from state.
            if ($choiceValue === 'cancel' || $this->wantsCancel($text)) {
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

                return array_merge($replies, $this->identifyPatient($state, $client, $authenticator, $otp));

            case 'birth_date':
                $date = $this->parseBirthDate($text);
                if (!$date) {
                    return [$this->say("I couldn't read that as a date. Could you include the year too? For example 27.03.1993 or 5 April 1990.")];
                }
                $state['patient']['birth_date'] = $date->format('Y-m-d');

                return $this->identifyPatient($state, $client, $authenticator, $otp);

            case 'email':
                // The answer may carry the name as well as the address. They are
                // split, and each goes through the validator it always had; an
                // answer whose name cannot be read changes nothing and asks again.
                $combined = $this->splitNameEmail($text);

                if ($combined['name_text'] !== null) {
                    // Gender may be said with the name ("Anna Meier, female");
                    // it is taken out first so it is never read as part of it.
                    $withGender = $this->splitGenderFromName($combined['name_text']);
                    $name = $this->splitName((string) $withGender['name']);

                    if (!$name['first'] && $withGender['gender'] !== null && $withGender['name'] === null) {
                        // Only a gender was given with the address: keep it, ask the name later.
                        $state['patient']['gender'] = $withGender['gender'];
                        $name = ['first' => null, 'last' => null];
                    } elseif (!$name['first']) {
                        return [$this->say("I couldn't read a name in that. Could you tell me your full name and your email address again?")];
                    } elseif ($withGender['gender'] !== null) {
                        $state['patient']['gender'] = $withGender['gender'];
                    }

                    if ($name['first']) {
                        $state['patient']['first_name'] = $name['first'];
                    }

                    if ($name['last']) {
                        $state['patient']['last_name'] = $name['last'];
                    }

                }

                // A typed address is taken as it is, whatever was said around it.
                if ($combined['email'] !== null) {
                    $text = $combined['email'];
                }

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
                    // The patient gave the name and not the address yet: keep the
                    // name, so it is not asked for a second time, and ask for the
                    // address alone. Only a full name that is plainly not part of
                    // a half-heard address ("anna at gmail"), through the same
                    // name validator as ever; one word, or anything address-like,
                    // is left to the messages below.
                    if (empty($state['patient']['last_name']) && $combined['email'] === null
                        && preg_match('/@|\d|\b(?:at|dot|punkt|underscore|gmail|googlemail|yahoo|hotmail|outlook|yopmail|icloud|gmx|e-?mail|mail|com)\b/iu', $text) !== 1) {
                        $found = $this->splitGenderFromName($text);
                        $nameOnly = $found['name'] !== null ? $this->splitName($found['name']) : ['first' => null, 'last' => null];

                        if ($nameOnly['first'] && $nameOnly['last']) {
                            $state['patient']['first_name'] = $nameOnly['first'];
                            $state['patient']['last_name'] = $nameOnly['last'];

                            if ($found['gender'] !== null) {
                                $state['patient']['gender'] = $found['gender'];
                            }

                            return [$this->say('Got your name, ' . $nameOnly['first'] . ' ' . $nameOnly['last'] . '.')];
                        }
                    }

                    // Asked for the name too, so a reply without an address says
                    // what is still needed rather than only blaming the email.
                    if (empty($state['patient']['last_name']) && empty($state['patient']['first_name'])) {
                        return [$this->say("I couldn't find an email address in that. Please tell me your full name and your email address, for example: Anna Meier, anna@example.com.")];
                    }

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

                return array_merge(
                    [$this->say('Thanks, I have ' . $capture['email'] . '.')],
                    $this->sendVerificationCode($state, $otp)
                );

            case 'email_confirm':
                if ($this->saidYes($choiceValue, $text)) {
                    $state['patient']['email'] = $state['pending_email'];
                    $state['pending_email'] = null;
                    $state['pending_email_heard'] = null;

                    return array_merge(
                        [$this->say('Great, thank you.')],
                        $this->sendVerificationCode($state, $otp)
                    );
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


            case 'otp_verify':
                // Either a NEW patient, who is not created until the code is read
                // back, or a RETURNING one, who is not shown anything of theirs
                // until it is. The code is checked against the practice's own
                // patients_otp table, and the attempts are counted against the
                // mobile number across every session (OtpGuard), not in here.
                // The four digits and nothing else (spaces, punctuation and a few
                // harmless words around them are fine). A stray letter - "7134s" -
                // is a slip or a mishearing, not a code, and is asked for again
                // without being compared, so it costs no attempt.
                $entered = $this->readCode($choiceValue !== '' ? $choiceValue : $text) ?? '';

                if (!$otp) {
                    // No way to check a code means no way in. Never a way round.
                    return [$this->say(self::OTP_UNAVAILABLE, 'error')];
                }

                if ($this->wantsAnotherCode($text)) {
                    return $this->resendVerificationCode($state, $otp);
                }

                if ($entered === '') {
                    return [$this->say('Could you read me the code? It is four digits.')];
                }

                $check = $otp->verifyDetailed($entered, $state['patient']['mobile_no'], $state['patient']['birth_date']);

                if ($check['ok']) {
                    // Remembered against the number it was proved for, so a
                    // later change of mobile cannot inherit this verification.
                    $state['otp_verified'] = true;
                    $state['otp_verified_mobile'] = $state['patient']['mobile_no'];
                    $state['otp_attempts'] = 0;

                    if (!empty($state['otp_login'])) {
                        return $this->finishReturningLogin($state, $client, $authenticator);
                    }

                    // Name and email may have been given together before the
                    // code, so only what is still missing is asked.
                    $state['step'] = empty($state['patient']['first_name'])
                        ? 'first_name'
                        : (empty($state['patient']['last_name']) ? 'last_name' : 'gender');

                    // Everything was given up front: nothing is left to ask, and
                    // the code has just been read back, so the patient is set up.
                    if ($state['step'] === 'gender' && !empty($state['patient']['gender'])) {
                        return array_merge(
                            [$this->say('Thank you - that is verified.')],
                            $this->askDetailsConfirm($state, $client, $authenticator)
                        );
                    }

                    return [$this->say('Thank you - that is verified.')];
                }

                if ($check['unavailable']) {
                    return [$this->say(self::OTP_UNAVAILABLE, 'error')];
                }

                if ($check['locked']) {
                    // Too many wrong codes, from this session or any other. The
                    // number is shut for a while, so there is nothing left to try.
                    $state['step'] = 'otp_failed';
                    $state['otp_login'] = null;

                    return [$this->say($this->lockedMessage((int) $check['retry_after']), 'error')];
                }

                return [$this->say("That code is not right. Could you check it and read it to me again?", 'error')];

            case 'details_confirm':
                // The last look before the patient exists. Nothing is created
                // until a clear yes, and then only through the same gate as
                // ever: registerAndLoadDoctors() refuses an unverified patient.
                if ($choiceValue === 'edit' || $this->saidNo($choiceValue, $text)) {
                    $state['step'] = 'details_edit';

                    return [];
                }

                if ($this->saidYes($choiceValue, $text)) {
                    return $this->registerAndLoadDoctors($state, $client, $authenticator);
                }

                // Neither yes nor no: it may be the correction itself.
                $edit = $this->applyDetailsEdit($choiceValue === '' ? $text : '', $state, true);

                return $edit ?? [$this->say('Please tap "Yes, that\'s right" or "Change something".')];

            case 'details_edit':
                $edit = $this->applyDetailsEdit($text, $state);

                return $edit ?? [$this->say("I couldn't find a name, gender or email address in that. Please tell me what to change - for example: Anna Meier, female, anna@example.com.")];

            case 'gender':
                $gender = $this->normalizeGender($choiceValue !== '' ? $choiceValue : $text);
                if (!$gender) {
                    return [$this->say("Sorry, I didn't understand. Please answer male or female.")];
                }
                $state['patient']['gender'] = $gender;

                return $this->askDetailsConfirm($state, $client, $authenticator);

            case 'doctor':
                // Questions about who else is available are answered further
                // up, before the "show more" hatch.

                // "the earliest one" is about time, not about which doctor.
                // Remember it for the slot step instead of letting it pick.
                if ($choiceValue === '' && $this->isOnlyTimePreference($text)) {
                    $state['slot_hint'] = $text;

                    return [$this->say("I'll keep that in mind for the times. First, which doctor would you like to see?")];
                }

                // "can I change my gender" is a question about themselves, not
                // a doctor. Without this it reached the name matcher and came
                // back as "I didn't catch which doctor you meant", which reads
                // as though the assistant had not listened at all.
                if ($choiceValue === '' && $this->asksToChangeDetails($text)) {
                    $state['_handled'] = true;

                    return [$this->say("I can't change your details here, I'm afraid - the practice can update those for you.")];
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

                return $this->offerServices($state, $client);

            case 'services':
                return $this->answerServices($text, $choiceValue, $state, $client);

            case 'country':
                return $this->answerCountry($text, $choiceValue, $state);

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

                // A day that has already gone cannot be booked, and saying "nothing
                // free that day" would suggest it might have been. Only for a day
                // that is NOT on the list: whatever PureMed offers is chosen
                // exactly as before, so this is a message, not a rule that
                // changes what can be booked.
                if ($choiceValue === '' && ($passed = $this->pastDayNamed($text))
                    && !in_array($passed->format('d.m.Y'), $this->availableDates($state['slots']), true)) {
                    $state['_handled'] = true;

                    return [$this->say($this->passedDayMessage($passed))];
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
                // After "...Would you like to try another date?" a yes goes to the days.
                if (!empty($state['offer_other_date'])) {
                    $state['offer_other_date'] = false;

                    if ($choiceValue === '' && $this->saidYes('', $text)) {
                        $state['slot_date'] = null;
                        $state['slot_window'] = null;
                        $state['slot'] = null;
                        $state['step'] = 'slot_date';
                        $state['_handled'] = true;
                        $state['_no_prompt'] = true;

                        return [$this->say('Of course - which of these days would you prefer?')];
                    }
                }

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

                // "I don't want any of these" turns down the whole list, not one
                // time in it. Showing the same times again - which is what a
                // refusal used to do here - answers a question they did not ask.
                // The day is what is not working, so this is the same place
                // "another day" goes. The doctor and the appointment type they
                // already chose are kept.
                if ($choiceValue === '' && $this->rejectsAllSlots($text)) {
                    $state['slot'] = null;
                    $state['slot_window'] = null;
                    $state['slot_date'] = null;
                    $state['step'] = 'slot_date';
                    $state['_handled'] = true;
                    $state['_no_prompt'] = true;

                    return [$this->say("No problem - none of those, then. Which day would suit you better?")];
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

                // A clock time the patient named ("at 5:00", "5 in the morning",
                // "6:30"). It is held only when that exact time is free; otherwise
                // the patient is told and offered what is really free, never moved
                // to another time or to the other half of the day.
                if ($choiceValue === '' && ($timed = $this->resolveTimeRequest($state, $text, $client)) !== null) {
                    return $timed;
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
                if ($choiceValue === '' && !$this->consents('book', $choiceValue, $text, $state)) {
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
                if ($this->consents('book', $choiceValue, $text, $state)) {
                    $state['_consent'] = 'book';

                    return $this->book($state, $client);
                }

                // It sounds like agreement, but not clearly enough to book on:
                // "yes but not that one", "yes if it's cheap", a long ramble.
                // Ask once more instead of guessing; the buttons are still there.
                if ($choiceValue === '' && $this->saidYes('', $text)) {
                    $state['_handled'] = true;

                    return [$this->say('I want to be sure before I book anything. Please answer yes or no, or use the buttons.')];
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
                // Cancelling needs a clear yes, decided by the same word lists as
                // booking, with "cancel it" and "stornieren" counting as one too.
                // The old rule - any sentence containing the word "cancel" - let
                // "can you cancel the other one instead" through.
                if ($this->consents('cancel', $choiceValue, $text, $state)) {
                    $state['_consent'] = 'cancel';

                    return $this->cancelAppointment($state, $client);
                }

                // Loosely agreeing but not clearly: ask again, keep the appointment
                // for now. A plain no, or anything that is not agreement at all,
                // keeps it for good, as before.
                $cancelVerb = preg_match('/\b(cancel|remove|delete|stornieren|absagen)\b/u', $this->normalizeText($text)) === 1;

                if ($choiceValue === '' && !$this->saidNo('', $text) && ($this->saidYes('', $text) || $cancelVerb)) {
                    $state['_handled'] = true;

                    return [$this->say('I want to be sure before I cancel anything. Please answer yes or no, or use the buttons.')];
                }

                $state['cancel_target'] = null;
                $state['rebook_after_cancel'] = false;
                $state['step'] = $state['appointment'] ? 'done' : 'doctor';

                return [$this->say("I've kept that appointment. Nothing has been cancelled.")];

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

            case 'otp_failed':
                // A dead end with nothing left to ask. "ok" or "thank you" is the patient
                // taking the news, so answer it with a goodbye rather than the same
                // "please call the practice" line again. Nothing else is accepted here.
                if ($choiceValue === '' && ($this->saidNothingElse($text) || $this->acknowledges($text))) {
                    return $this->closeConversation($state);
                }

                return [];

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
                // Cancelling is only offered when there is something to cancel.
                // What counts is the practice's own answer, held in
                // 'cancellable' exactly as get-appointment returned it - that
                // endpoint decides (status = 1 and start_date >= now) and
                // nothing is re-judged here. Saying "you have no upcoming
                // appointments" and then offering to cancel one read as a bug
                // to the patient, and it was one.
                // The appointments themselves come first, so "you have 2" is
                // followed by both of them rather than by a menu. They are
                // read-only; the choices below are what can be acted on.
                $items = $this->appointmentRowCards($state['appointment_list_cards'] ?? []);

                $items[] = ['value' => 'book', 'title' => 'Book an appointment'];

                if (!empty($state['cancellable'])) {
                    $items[] = ['value' => 'cancel', 'title' => 'Cancel an appointment'];
                }

                $items[] = ['value' => 'past', 'title' => 'Past appointments'];

                return [
                    'text' => 'Anything else I can help you with?',
                    'options' => [
                        'type' => 'appointments',
                        'summary' => $state['appointment_list'] ?? [],
                        'items' => $items,
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
                // Name and email are asked together; once both names are known
                // (or an earlier answer gave them) it is the email alone.
                return ['text' => empty($patient['last_name'])
                    ? 'May I have your full name, your gender and the best email address for your confirmation?'
                    : "What's the best email address for your confirmation?", 'input' => $this->input()];

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

            case 'otp_verify':
                return [
                    'text' => 'Please read me the four digit code.',
                    'input' => $this->input('Enter the 4-digit code'),
                ];

            case 'otp_failed':
                return [
                    'text' => 'Please call the practice and they will book this for you.',
                    'input' => $this->input(),
                ];

            case 'details_confirm':
                return [
                    'text' => 'So I have ' . trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''))
                        . ', ' . (($patient['gender'] ?? '') === 'M' ? 'male' : 'female')
                        . ', ' . ($patient['email'] ?? '') . '. Is that right?',
                    'options' => $this->options('details_confirm', [
                        ['value' => 'yes', 'title' => "Yes, that's right"],
                        ['value' => 'edit', 'title' => 'Change something'],
                    ]),
                    'input' => $this->input(),
                ];

            case 'details_edit':
                return [
                    'text' => 'Of course. Tell me what to change - your full name, your gender or your email address (please type the email).',
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

            case 'country':
                if (!empty($state['country_free'])) {
                    return ['text' => self::COUNTRY_OTHER_PROMPT, 'input' => $this->input()];
                }

                return [
                    'text' => 'Which country is your mobile number from?',
                    'options' => $this->countryOptions($state),
                    'input' => $this->input(),
                ];

            case 'services':
                return [
                    'text' => 'Which services would you like to include? Tick the ones you want, then press Continue.',
                    'options' => $this->serviceOptions($state),
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
        // The gate, and it is here rather than on a step for a reason: a step
        // is a value in the session, and a session is something a caller can
        // try to move. Creating the patient is the thing that must not happen
        // unverified, so this is where it is checked. Forcing the conversation
        // straight to the name question reaches exactly this line and stops.
        if (!$this->verifiedForRegistration($state)) {
            $state['step'] = 'otp_verify';

            return [$this->say('Before I set you up, please read me the four digit code I sent you.', 'error')];
        }

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
            'country_code' => $this->chosenCountryCode($patient),
            'postal_code' => config('ai-assistant.default_postal_code'),
            'country' => $this->chosenCountry($patient),
            'password' => Str::random(12),
            'login_type' => config('ai-assistant.login_type'),
        ];

        $registration = $client->registerPatient($payload);
        $state['new_patient'] = (bool) ($registration['ok'] ?? false);

        // Registration may report the patient already exists; either way we look
        // the record up so a returning patient can keep going.
        $credentials = $authenticator->authenticate($payload['mobile_no'], $payload['birth_date']);

        if (!$credentials) {
            return [$this->say($this->readableError($registration, "I couldn't save your details just now. Could you check your mobile number and date of birth?"), 'error')];
        }

        $state['patient_id'] = $credentials['patient_id'];
        $state['token'] = $credentials['token'];
        // Created after a code was read back for this number, so this session is
        // verified for them from here on.
        $state['auth_proof'] = $this->authProof($state);

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
            } elseif ($passed = $this->pastDayNamed($hint)) {
                $replies[] = $this->say($this->passedDayMessage($passed));
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
        // Defence in depth. Whatever path led here, nothing is booked without a
        // consent recorded by consents() for THIS answer.
        if (!$this->takeConsent($state, 'book')) {
            return [$this->say('I need a clear yes before I book anything. Shall I book it?', 'error')];
        }

        // The last gate before the only irreversible step in the conversation.
        if ($refusal = $this->refuseUnsafeBooking($state, $client)) {
            return $refusal;
        }

        $slot = $state['slot'];

        $booking = [
            'patient_id' => $state['patient_id'],
            'doctor_id' => $state['doctor']['id'],
            'appointment_type_id' => $state['appointment_type']['id'],
            'appointment_date' => $slot['slot_date'],   // already d.m.Y
            'time_frame' => $slot['time'],
            'time_slot_id' => $slot['time_slot_id'],
        ];

        // Only ids PureMed offered for THIS appointment type. PureMed adds the
        // type's own services itself, so nothing ticked means nothing is sent.
        if ($ticked = $this->chosenServiceIds($state)) {
            $booking['exam_ids'] = implode(',', $ticked);
        }

        $result = $client->bookAppointment($state['token'], $booking);

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

        // The visit is done. converse() ends the conversation once this turn has
        // been answered: the card and a goodbye are shown, and nothing about the
        // patient is kept. Switched off with ai-assistant.end_after_booking.
        if (config('ai-assistant.end_after_booking', true)) {
            $state['_ended'] = 'booking';
        }

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
            // The practice has just said there is nothing, so anything held
            // from an earlier fetch is out of date. Left behind, it would keep
            // offering a Cancel button for appointments that are gone.
            $state['cancellable'] = [];

            // The step is left alone deliberately: the patient keeps the
            // doctor, type, day and time they had already chosen.
            $replies = [$this->say("You don't have any upcoming appointments to cancel.")];

            // Mid-booking, the step's own question is about to be asked again.
            // On its own that reads as though the request had been ignored -
            // "nothing to cancel" followed by "which doctor?" - so say why it
            // is coming back. At the "anything else?" menu the question already
            // follows on its own, and a second sentence would only pad it.
            if (in_array($state['step'], ['doctor', 'appointment_type', 'slot_date', 'slot_time', 'confirm'], true)) {
                $replies[] = $this->say('We can carry on with the new one, though.');
            }

            return $replies;
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
            $state['appointment_list_cards'] = [];
            // Same response, same list: nothing to show is nothing to cancel.
            $state['cancellable'] = [];

            return [$this->say("You don't have any upcoming appointments at the moment.")];
        }

        $state['cancellable'] = $appointments;
        $state['appointment_list'] = $this->appointmentRows($appointments);
        // What the prompt lists back. The summary card these rows were built
        // for is never rendered, so the appointments are shown the way the
        // cancel step already shows them.
        $state['appointment_list_cards'] = $appointments;
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
            $state['appointment_list_cards'] = [];

            return [$this->say("I couldn't find any past appointments on your record.")];
        }

        // Most recent first reads better than oldest first.
        $past = array_reverse($past);
        $state['appointment_list'] = $this->appointmentRows($past);
        // Listed back for the same reason the upcoming ones are: a count on
        // its own is not an answer to "show me my appointments".
        $state['appointment_list_cards'] = $past;

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
        // A past day is remembered too ('book 10 oct'), so it can be answered once there is a day list.
        if ($this->mentionsSpecificDay($text) || $this->extractTime($text) !== '' || $this->pastDayNamed($text) !== null) {
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
        if (!$this->takeConsent($state, 'cancel')) {
            return [$this->say('I need a clear yes before I cancel anything. Shall I cancel this appointment?', 'error')];
        }

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
        // The viewing list was read BEFORE this cancellation, so it still holds
        // the appointment that has just gone. Left behind, a page reload
        // rendered it again from the session and the cancelled appointment came
        // back as though it were still booked.
        $state['appointment_list'] = [];
        $state['appointment_list_cards'] = [];
        $state['appointments_context'] = [];
        $state['discussed_appointment'] = 0;
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

    /**
     * The same appointments the cancel step lists, shown only to be read.
     *
     * "You have 2 upcoming appointments" followed by one of them read out is
     * not a list. The cancel step already renders every appointment clearly,
     * so viewing borrows that presentation rather than inventing another - the
     * same appointment then looks the same wherever the patient meets it.
     * Marked read-only: tapping your own appointment must not start anything.
     *
     * @return array<int, array>
     */
    private function appointmentRowCards(array $appointments): array
    {
        return array_map(fn ($card) => $card + ['readonly' => true],
            $this->appointmentCards($appointments));
    }

    private function appointmentCards(array $appointments): array
    {
        return array_map(fn ($appointment) => [
            'value' => (string) $appointment['id'],
            'title' => $this->appointmentLabel($appointment),
            'subtitle' => trim(($appointment['appointment_type_name'] ?? '') . ' - ' . Str::title((string) ($appointment['doctor_name'] ?? '')), ' -'),
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
            'Doctor' => Str::title((string) ($appointment['doctor_name'] ?? '')),
            'When' => $this->appointmentLabel($appointment),
        ];
    }

    private function summary(array $state): array
    {
        return [
            'Patient' => trim(($state['patient']['first_name'] ?? '') . ' ' . ($state['patient']['last_name'] ?? '')),
            'Doctor' => $this->doctorDisplay($state['doctor']),
            'Appointment' => $state['appointment_type']['name'] ?? '',
        ] + ($this->chosenServiceNames($state) !== '' ? ['Services' => $this->chosenServiceNames($state)] : []) + [
            'Date' => $state['slot']['slot_date'] ?? '',
            'Time' => $state['slot']['time'] ?? '',
        ];
    }

    /* -----------------------------------------------------------------
    |  Where a new patient's mobile number is from
    ------------------------------------------------------------------*/

    /**
     * For a patient the practice does not know: fetch the practice's own
     * country list and, if there is one, make the next step the question.
     * Returns false - and changes nothing - when the switch is off or the list
     * cannot be had; the Austrian default then applies, exactly as before.
     */
    private function askCountry(array &$state, PureMedApiClient $client): bool
    {
        unset($state['patient']['country_code'], $state['patient']['country']);
        $state['country_options'] = [];
        $state['country_free'] = false;

        if (!config('ai-assistant.ask_country', true)) {
            return false;
        }

        $result = $client->getCountryCodes();
        $options = $result['ok'] ? $this->cleanCountries($result['data']) : [];

        if (!$options) {
            return false;
        }

        $state['country_options'] = $options;
        $state['step'] = 'country';

        return true;
    }

    /**
     * What PureMed sent, reduced to iso, names and a code that looks like one.
     * Anything else is dropped.
     *
     * @return array<int, array{iso: string, name_en: string, name_de: string, code: string}>
     */
    private function cleanCountries(mixed $data): array
    {
        $out = [];

        foreach ((array) $data as $row) {
            if (!is_array($row)
                || !is_string($row['code'] ?? null) || !preg_match('/^(\+|00)[1-9][0-9]{0,3}$/', $row['code'])
                || !is_string($row['name_en'] ?? null) || trim($row['name_en']) === '') {
                continue;
            }

            $out[$row['code']] = [
                'iso' => mb_substr((string) ($row['iso'] ?? ''), 0, 3),
                'name_en' => mb_substr(trim($row['name_en']), 0, 60),
                'name_de' => mb_substr(trim((string) ($row['name_de'] ?? '')) ?: trim($row['name_en']), 0, 60),
                'code' => $row['code'],
            ];
        }

        return array_slice(array_values($out), 0, 20);
    }

    /** The country chips, named in the patient's language, then "Other". */
    private function countryOptions(array $state): array
    {
        $german = $this->language() === 'de';

        $items = array_map(fn ($c) => [
            'value' => $c['code'],
            'title' => ($german ? $c['name_de'] : $c['name_en']) . ' (' . $c['code'] . ')',
        ], $state['country_options'] ?? []);

        $items[] = ['value' => '__other__', 'title' => 'Other country'];

        return $this->options('country', $items);
    }

    /**
     * The patient's answer: a chip, a country's name in either language, its
     * code, or "other" and then a code of their own. Never the AI, and nothing
     * that is not a code PureMed listed - or, for "other", + or 00 followed by
     * one to four digits, which is the register API's own pattern.
     */
    private function answerCountry(string $text, string $choiceValue, array &$state): array
    {
        $options = $state['country_options'] ?? [];

        // Not a place a conversation without a number can be.
        if (empty($state['patient']['mobile_no']) || empty($state['patient']['birth_date'])) {
            $state['step'] = 'mobile_no';
            $state['country_free'] = false;

            return [];
        }

        if (!$options) {
            // A session that lost its list: carry on with the default.
            $state['step'] = $this->nextRegistrationStep('birth_date');

            return [];
        }

        if ($choiceValue !== '') {
            if ($choiceValue === '__other__') {
                $state['country_free'] = true;

                return [];
            }

            foreach ($options as $option) {
                if ($option['code'] === $choiceValue) {
                    return $this->acceptCountry($state, $option['code']);
                }
            }

            $state['_handled'] = true;

            return [$this->say(self::COUNTRY_REASK)];
        }

        if (str_contains($text, '?') || mb_strlen($text) > 60) {
            $state['_handled'] = true;

            return [$this->say(!empty($state['country_free']) ? self::COUNTRY_CODE_REASK : self::COUNTRY_REASK)];
        }

        $filler = ['please', 'bitte', 'it', 'is', 'its', 'my', 'the', 'number', 'from', 'ist', 'aus', 'ja', 'yes', 'ok', 'okay', 'country', 'land', 'code', 'vorwahl', 'nummer', 'thanks', 'danke'];
        $tokens = array_values(array_filter(
            preg_split('/\s+/u', mb_strtolower(trim(str_replace(["'", '’'], '', $text))), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fn ($t) => !in_array(trim($t, '.,!;:'), $filler, true)
        ));
        $tokens = array_map(fn ($t) => trim($t, '.,!;:'), $tokens);

        if (!empty($state['country_free'])) {
            if (count($tokens) === 1 && preg_match('/^(\+|00)[1-9][0-9]{0,3}$/', $tokens[0])) {
                return $this->acceptCountry($state, $tokens[0]);
            }

            $state['_handled'] = true;

            return [$this->say(self::COUNTRY_CODE_REASK)];
        }

        $spoken = implode(' ', $tokens);

        if (in_array($spoken, ['other', 'another', 'other country', 'another country', 'weitere', 'weiteres', 'andere', 'anderes', 'anderes land', 'anderes', 'else'], true)) {
            $state['country_free'] = true;

            return [];
        }

        // A name in either language, or the code itself (+49, 0049, 49).
        $hits = [];
        $plain = fn ($v) => str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], mb_strtolower($v));

        foreach ($options as $option) {
            $names = [$plain($option['name_en']), $plain($option['name_de']), mb_strtolower($option['name_en']), mb_strtolower($option['name_de'])];
            $digits = ltrim(preg_replace('/^00/', '', ltrim($option['code'], '+')), '+');

            if (in_array($spoken, $names, true) || in_array($plain($spoken), $names, true)
                || preg_match('/^(\+|00)?' . preg_quote($digits, '/') . '$/', $spoken)) {
                $hits[$option['code']] = $option['code'];
            }
        }

        if (count($hits) === 1) {
            return $this->acceptCountry($state, reset($hits));
        }

        // A code of their own, typed with its + or 00, that the list does not hold.
        if (!$hits && count($tokens) === 1 && preg_match('/^(\+|00)[1-9][0-9]{0,3}$/', $tokens[0])) {
            return $this->acceptCountry($state, $tokens[0]);
        }

        $state['_handled'] = true;

        return [$this->say(self::COUNTRY_REASK)];
    }

    /** Remember the code and the country the register API will accept for it, and move on. */
    private function acceptCountry(array &$state, string $code): array
    {
        $state['patient']['country_code'] = $code;
        $state['patient']['country'] = $this->countryNameFor($code);
        $state['country_free'] = false;
        $state['step'] = $this->nextRegistrationStep('birth_date');

        return [$this->say('Got it, country code ' . $code . '.')];
    }

    /** The register API only knows Austria, Germany and Switzerland as countries. */
    private function countryNameFor(string $code): string
    {
        $digits = preg_replace('/^(\+|00)/', '', $code);

        return match ($digits) {
            '49' => 'Germany',
            '41' => 'Switzerland',
            default => 'Austria',
        };
    }

    /** The code to register with: the one chosen if it is a real code, otherwise the default. */
    private function chosenCountryCode(array $patient): string
    {
        $code = trim((string) ($patient['country_code'] ?? ''));

        return preg_match('/^(\+|00)[1-9][0-9]{0,3}$/', $code) ? $code : (string) config('ai-assistant.default_country_code');
    }

    /** The country to register with: one the register API accepts, otherwise the default. */
    private function chosenCountry(array $patient): string
    {
        $country = (string) ($patient['country'] ?? '');

        return in_array($country, ['Austria', 'Germany', 'Switzerland'], true) ? $country : (string) config('ai-assistant.default_country');
    }

    /* -----------------------------------------------------------------
    |  Services for the chosen appointment type
    ------------------------------------------------------------------*/

    /**
     * After the type is chosen: ask PureMed which services go with it and, if
     * there are any, let the patient tick them before the dates. No services,
     * or a failed call, leaves the flow exactly as it was: straight to the dates.
     */
    private function offerServices(array &$state, PureMedApiClient $client): array
    {
        $typeId = (int) ($state['appointment_type']['id'] ?? 0);
        $state['services'] = [];
        $state['services_for'] = null;
        $state['service_ids'] = null;

        $payload = ['appointment_type_id' => $typeId, 'patient_id' => $state['patient_id']];

        // Someone registered in this conversation has no history to read; what
        // PureMed adds for them comes from their age.
        if (!empty($state['new_patient']) && !empty($state['patient']['birth_date'])) {
            $payload['is_new_patient'] = 1;
            $payload['birth_date'] = Carbon::parse($state['patient']['birth_date'])->format('Y-m-d');
        }

        // The switch is off: the flow is exactly what it was before this step existed.
        if (!config('ai-assistant.services_step', true)) {
            return $this->loadSlots($state, $client);
        }

        $result = $typeId > 0 ? $client->getAppointmentTypeServices((string) $state['token'], $payload) : ['ok' => false, 'data' => []];
        $list = $result['ok'] ? $this->cleanServices($result['data']) : [];

        if (!$list) {
            return $this->loadSlots($state, $client);
        }

        $state['services'] = $list;
        $state['services_for'] = $typeId;
        $state['service_ids'] = array_values(array_map(fn ($s) => $s['id'], array_filter($list, fn ($s) => $s['checked'] || $s['locked'])));
        $state['step'] = 'services';

        // No "<type> it is." here: loadSlots() says it once the services are settled.
        return [];
    }

    /**
     * What PureMed sent, reduced to id, name and the two flags. Anything that
     * does not look like a service is dropped.
     *
     * @return array<int, array{id: int, name: string, checked: bool, locked: bool}>
     */
    private function cleanServices(mixed $data): array
    {
        $out = [];

        foreach ((array) $data as $row) {
            if (!is_array($row) || !isset($row['id']) || !is_numeric($row['id']) || (int) $row['id'] < 1
                || !is_string($row['name'] ?? null) || trim($row['name']) === '') {
                continue;
            }

            $out[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'name' => mb_substr(trim($row['name']), 0, 120),
                'checked' => !empty($row['checked']) || !empty($row['locked']),
                'locked' => !empty($row['locked']),
            ];
        }

        return array_slice(array_values($out), 0, 40);
    }

    /** The tick list drawn under the question. */
    private function serviceOptions(array $state): array
    {
        $ticked = $state['service_ids'] ?? [];

        return [
            'type' => 'services',
            'continue' => 'Continue',
            'items' => array_map(fn ($s) => [
                'value' => (string) $s['id'],
                'title' => $s['name'],
                'checked' => $s['locked'] || in_array($s['id'], $ticked, true),
                'locked' => $s['locked'],
            ], $state['services'] ?? []),
        ];
    }

    /**
     * The patient's answer on the services step: the tick list submitted, or
     * "continue", "none", "all", or the name of a service to add or remove.
     * Never the AI, and never anything that is not an id PureMed offered.
     */
    private function answerServices(string $text, string $choiceValue, array &$state, PureMedApiClient $client): array
    {
        $offered = $state['services'] ?? [];

        if (!$offered || (int) ($state['services_for'] ?? 0) !== (int) ($state['appointment_type']['id'] ?? -1)) {
            // A session that lost its list: carry on as if there had been none.
            $state['services'] = [];
            $state['services_for'] = null;
            $state['service_ids'] = null;

            return $this->loadSlots($state, $client);
        }

        $ids = array_map(fn ($s) => $s['id'], $offered);
        $locked = array_map(fn ($s) => $s['id'], array_filter($offered, fn ($s) => $s['locked']));
        $current = array_values(array_intersect($state['service_ids'] ?? [], $ids));

        if ($choiceValue !== '') {
            if (str_starts_with($choiceValue, 'svc:')) {
                $picked = array_filter(array_map('trim', explode(',', substr($choiceValue, 4))), fn ($v) => preg_match('/^\d{1,9}$/', $v));
                $picked = array_map('intval', $picked);
                $keep = array_values(array_unique(array_merge($locked, array_intersect($picked, $ids))));
                $state['service_ids'] = array_values(array_filter($ids, fn ($id) => in_array($id, $keep, true)));

                return $this->loadSlots($state, $client);
            }

            $state['_handled'] = true;

            return [$this->say(self::SERVICES_REASK)];
        }

        $spoken = ' ' . $this->normalizeText($text) . ' ';
        $named = array_values(array_filter($offered, function ($s) use ($spoken) {
            $name = $this->normalizeText($s['name']);

            return $name !== '' && str_contains($spoken, ' ' . $name . ' ');
        }));

        if ($named) {
            $removing = (bool) preg_match('/ (no|not|without|remove|untick|don|nicht|ohne|entfernen|kein|keine|keinen) /u', $spoken);
            $names = array_map(fn ($s) => $s['name'], $named);

            foreach ($named as $s) {
                if ($removing && !$s['locked']) {
                    $current = array_values(array_diff($current, [$s['id']]));
                } elseif (!$removing && !in_array($s['id'], $current, true)) {
                    $current[] = $s['id'];
                }
            }

            $state['service_ids'] = $current;
            $state['_handled'] = true;

            return [$this->say(($removing ? 'Unticked: ' : 'Ticked: ') . implode(', ', $names) . '. Press Continue when you are happy, or tick more.')];
        }

        $words = trim(preg_replace('/ (please|bitte|thanks|thank you|danke|dankeschon) /u', ' ', $spoken));
        $words = trim(preg_replace('/\s+/', ' ', $words));
        $language = $this->language();

        $none = $language === 'de'
            ? ['keine', 'keins', 'keines', 'nein', 'nein danke', 'ohne', 'uberspringen', 'überspringen', 'nichts']
            : ['none', 'no', 'no thanks', 'nothing', 'skip', 'without', 'without any', 'no services'];
        $all = $language === 'de'
            ? ['alle', 'alles', 'alle bitte', 'alle auswahlen', 'alle auswählen']
            : ['all', 'all of them', 'everything', 'select all', 'tick all'];
        $next = $language === 'de'
            ? ['weiter', 'fertig', 'fortfahren', 'los', 'naechster schritt']
            : ['continue', 'next', 'proceed', 'done', 'go on', 'carry on', 'that is all', 'that s all'];

        if (in_array($words, $none, true)) {
            $state['service_ids'] = array_values($locked);

            return $this->loadSlots($state, $client);
        }

        if (in_array($words, $all, true)) {
            $state['service_ids'] = array_values($ids);

            return $this->loadSlots($state, $client);
        }

        if (in_array($words, $next, true) || app(ConsentRules::class)->isClearConsent($text, $language)) {
            // Whatever is ticked now. Agreeing to carry on is not consent to book.
            $state['service_ids'] = $current;

            return $this->loadSlots($state, $client);
        }

        $state['_handled'] = true;

        return [$this->say(self::SERVICES_REASK)];
    }

    /**
     * The ids to send as exam_ids: ticked, offered, and offered for the type
     * being booked - in the order PureMed listed them. Empty when no list was
     * shown, or it was for another type.
     *
     * @return array<int, int>
     */
    private function chosenServiceIds(array $state): array
    {
        $typeId = (int) ($state['appointment_type']['id'] ?? 0);

        if (empty($state['services']) || $typeId < 1 || (int) ($state['services_for'] ?? 0) !== $typeId) {
            return [];
        }

        $offered = array_map(fn ($s) => (int) $s['id'], $state['services']);
        $ticked = array_map('intval', array_filter((array) ($state['service_ids'] ?? []), 'is_numeric'));

        return array_values(array_filter($offered, fn ($id) => in_array($id, $ticked, true)));
    }

    /** The ticked services by name, for the confirmation card. */
    private function chosenServiceNames(array $state): string
    {
        $ids = $this->chosenServiceIds($state);

        return implode(', ', array_map(
            fn ($s) => $s['name'],
            array_values(array_filter($state['services'] ?? [], fn ($s) => in_array((int) $s['id'], $ids, true)))
        ));
    }

    /**
     * A list of services belongs to one appointment type. When the type changes
     * or is cleared - another doctor, another type, "start over" - the list and
     * the ticks go with it.
     */
    private function syncServices(array &$state): void
    {
        $typeId = (int) ($state['appointment_type']['id'] ?? 0);

        if (($state['services'] ?? []) && ($typeId < 1 || (int) ($state['services_for'] ?? 0) !== $typeId)) {
            $state['services'] = [];
            $state['services_for'] = null;
            $state['service_ids'] = null;
        }

        if ($state['step'] === 'services' && empty($state['services'])) {
            $state['step'] = 'appointment_type';
        }
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
     * A clock time the patient named, or null when the message names none.
     *
     * "at 5:00", "5 in the morning", "5 AM", "morning 5", "5 pm", "around 6:30",
     * "um 17 Uhr", "5 Uhr nachmittags", and a bare hour after at / for / around /
     * about / um. Positions ("the 5th one"), dates and longer numbers are not
     * times here. The period is whatever the patient said - morning words mean
     * am, afternoon and evening words mean pm - and null when they said none.
     *
     * @return array{h: int, m: int, period: ?string, minutes: bool, zero: bool}|null
     */
    private function parseTimeRequest(string $text): ?array
    {
        $value = mb_strtolower(trim($text));

        if ($value === '' || $this->saysPosition($text)) {
            return null;
        }

        $am = preg_match('/\d\s*a\.?\s?m\.?(?![\p{L}])/u', $value) === 1
            || preg_match('/\b(?:morning|morgens|vormittags?|früh|fruh)\b/u', $value) === 1;
        $pm = preg_match('/\d\s*p\.?\s?m\.?(?![\p{L}])/u', $value) === 1
            || preg_match('/\b(?:afternoon|evening|nachmittags?|abends?)\b/u', $value) === 1;

        if ($am && $pm) {
            return null;
        }

        $period = $am ? 'am' : ($pm ? 'pm' : null);

        if (preg_match('/(?<!\d)(\d{1,2})\s*[:.]\s*(\d{2})(?!\d)/u', $value, $x)) {
            $hour = (int) $x[1];
            $minute = (int) $x[2];
            $minutes = true;
            $zero = strlen($x[1]) === 2 && $x[1][0] === '0';
        } else {
            if (preg_match_all('/(?<!\d)(\d{1,2})(?!\d)/u', $value, $all) !== 1 || count($all[1]) !== 1) {
                return null;
            }

            $hour = (int) $all[1][0];
            $minute = 0;
            $minutes = false;
            $zero = strlen($all[1][0]) === 2 && $all[1][0][0] === '0';

            $namesATime = $period !== null
                || preg_match('/\b(?:at|for|around|about|by|from|near|um|gegen)\s+0?' . $hour . '\b/u', $value) === 1
                || preg_match('/\b(?:o clock|o\'clock|oclock|uhr)\b/u', $value) === 1
                || preg_match('/^\s*\d{1,2}\s*$/u', $value) === 1;

            if (!$namesATime) {
                return null;
            }
        }

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return ['h' => $hour, 'm' => $minute, 'period' => $period, 'minutes' => $minutes, 'zero' => $zero];
    }

    /**
     * Settle a named clock time against the day's real slots.
     *
     * - The exact time is free: it is held, as any chosen time is.
     * - It is not free: say so, and offer the closest times that really are
     *   free (within three hours); with none that close, ask about another date.
     * - Only an hour with no period, where morning and afternoon are both
     *   possible, is asked about: "Do you mean 5:00 in the morning or 5:00 in
     *   the afternoon?" Nothing is guessed, and no other time is substituted.
     *
     * Nothing here picks a time for the patient: the offered times are chips to
     * choose from, and a chosen time still goes through the confirmation.
     *
     * @return array<int, array>|null  null when the message is not a time request
     */
    private function resolveTimeRequest(array &$state, string $text, PureMedApiClient $client): ?array
    {
        $date = $state['slot_date'] ?? null;
        $onDate = $this->slotsOn($state['slots'] ?? [], $date);

        $pending = $state['time_ambiguous'] ?? null;
        $state['time_ambiguous'] = null;

        if (!$onDate) {
            return null;
        }

        $request = null;

        // The answer to "morning or afternoon?".
        if (is_array($pending)) {
            $value = mb_strtolower($text);
            $am = preg_match('/\b(?:morning|morgens|vormittags?|früh|fruh|am|a\.m\.)\b/u', $value) === 1;
            $pm = preg_match('/\b(?:afternoon|evening|nachmittags?|abends?|pm|p\.m\.)\b/u', $value) === 1;

            if ($am !== $pm) {
                $request = ['h' => (int) $pending['h'], 'm' => (int) $pending['m'], 'period' => $am ? 'am' : 'pm',
                    'minutes' => (bool) $pending['minutes'], 'zero' => false];
            }
        }

        $request ??= $this->parseTimeRequest($text);

        if ($request === null) {
            return null;
        }

        $hours = [];

        foreach ($onDate as $slot) {
            $normalised = $this->normalizeTime($slot['time']);

            if ($normalised !== '') {
                $hours[(int) substr($normalised, 0, 2)] = true;
            }
        }

        $hasMorning = (bool) array_filter(array_keys($hours), fn ($h) => $h < 12);
        $hasAfternoon = (bool) array_filter(array_keys($hours), fn ($h) => $h >= 12);

        $h = $request['h'];

        if ($h === 0 || $h >= 13 || $request['zero']) {
            $hour24 = $h;
        } elseif ($request['period'] === 'am') {
            $hour24 = $h === 12 ? 0 : $h;
        } elseif ($request['period'] === 'pm') {
            $hour24 = $h < 12 ? $h + 12 : 12;
        } elseif ($h === 12) {
            $hour24 = 12;
        } else {
            // 1 to 11 with no period. Only the practice's own day can settle it.
            $amOffered = isset($hours[$h]);
            $pmOffered = isset($hours[$h + 12]);

            if ($amOffered && !$pmOffered) {
                $hour24 = $h;
            } elseif ($pmOffered && !$amOffered && !$hasMorning) {
                // A day with nothing before noon: "2" can only be the afternoon.
                $hour24 = $h + 12;
            } elseif (!$amOffered && !$pmOffered && !$request['minutes']) {
                // A bare number matching no time at all: the existing question
                // (the 5th time in the list, or another time?) is the right one.
                return null;
            } elseif (!$amOffered && !$pmOffered) {
                // Minutes were named, so it is a clock time that is simply not
                // free: the morning reading, unless the day has no morning.
                $hour24 = $hasMorning ? $h : $h + 12;
            } else {
                $clock = $h . ':' . str_pad((string) $request['m'], 2, '0', STR_PAD_LEFT);
                $state['time_ambiguous'] = ['h' => $h, 'm' => $request['m'], 'minutes' => $request['minutes']];
                $state['_handled'] = true;
                $state['_no_prompt'] = true;

                return [$this->say('Do you mean ' . $clock . ' in the morning or ' . $clock . ' in the afternoon?')];
            }
        }

        $wanted = str_pad((string) $hour24, 2, '0', STR_PAD_LEFT) . str_pad((string) $request['m'], 2, '0', STR_PAD_LEFT);

        foreach ($onDate as $slot) {
            if ($this->normalizeTime($slot['time']) === $wanted) {
                return $this->holdSlot($state, $slot, $client);
            }
        }

        // Not free. The closest times that really are, within three hours.
        $target = $hour24 * 60 + $request['m'];
        $near = [];

        foreach ($onDate as $slot) {
            $normalised = $this->normalizeTime($slot['time']);

            if ($normalised === '') {
                continue;
            }

            $distance = abs((int) substr($normalised, 0, 2) * 60 + (int) substr($normalised, 2, 2) - $target);

            if ($distance <= 180) {
                $near[] = ['distance' => $distance, 'time' => $normalised, 'label' => $slot['time']];
            }
        }

        usort($near, fn ($a, $b) => [$a['distance'], $a['time']] <=> [$b['distance'], $b['time']]);
        $near = array_slice($near, 0, 2);
        usort($near, fn ($a, $b) => $a['time'] <=> $b['time']);

        $label = substr($wanted, 0, 2) . ':' . substr($wanted, 2, 2);
        $state['_handled'] = true;
        $state['_no_prompt'] = true;

        if (!$near) {
            $state['offer_other_date'] = true;

            return [$this->say("I don't have " . $label . ' available on ' . $date . ', and there is nothing close to that time. Would you like to try another date?')];
        }

        $names = array_column($near, 'label');

        return [$this->say("I don't have {$label} available on {$date}. "
            . (count($names) === 1
                ? "The closest available time is {$names[0]}."
                : "The closest available times are {$names[0]} and {$names[1]}.")
            . ' Which time suits you?')];
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
            . '|changed my mind|change my mind'
            . '|vergiss es|schon gut|doch nicht|lassen wir es|kein interesse|hat sich erledigt)\b/u', $value) === 1) {
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

        return $value !== '' && (preg_match('/\b(start over|start again|restart|reset|book another)\b/', $value) === 1
            || preg_match('/\b(neu starten|neustart|von vorn|von vorne|zurücksetzen|noch einen buchen|noch einen termin)\b/u', $value) === 1);
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
    /** What the patient is told when the day they named is already behind us. */
    private function passedDayMessage(Carbon $day): string
    {
        return "You can't book an appointment on a past date (" . $day->format('d.m.Y') . '). Please pick one of the days I have free.';
    }

    /**
     * The day the patient named, when that day is before today - otherwise null.
     *
     * "10 Oct", "10.10.2026", "2026-10-10", "yesterday", "last Monday" and the
     * German "10. Oktober", "gestern", "letzten Montag". A date without a year
     * only counts as past when it is within the last two months: "5 January"
     * said in October means the coming one, which is not for this to judge.
     * "10.10" alone is left out, because that is how a time is written here.
     */
    private function pastDayNamed(string $text): ?Carbon
    {
        $raw = mb_strtolower($text);
        $today = Carbon::today();

        if (preg_match('/\b(day before yesterday|vorgestern)\b/u', $raw)) {
            return $today->copy()->subDays(2);
        }

        if (preg_match('/\b(yesterday|gestern)\b/u', $raw)) {
            return $today->copy()->subDay();
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
            if (preg_match('/\b(?:last|previous|letzten?|vergangenen?|vorigen?)\s+' . $name . '\b/u', $raw)) {
                return $today->copy()->previous($day);
            }
        }

        $past = static fn (?Carbon $date): ?Carbon => $date && $date->lt(Carbon::today()) ? $date : null;
        $make = static function (int $year, int $month, int $day): ?Carbon {
            return checkdate($month, $day, $year) ? Carbon::create($year, $month, $day)->startOfDay() : null;
        };

        if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $raw, $m)) {
            return $past($make((int) $m[1], (int) $m[2], (int) $m[3]));
        }

        if (preg_match('/\b(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})\b/', $raw, $m)) {
            return $past($make((int) $m[3], (int) $m[2], (int) $m[1]));
        }

        $months = [
            'january' => 1, 'jan' => 1, 'januar' => 1, 'february' => 2, 'feb' => 2, 'februar' => 2,
            'march' => 3, 'mar' => 3, 'märz' => 3, 'maerz' => 3, 'mär' => 3, 'april' => 4, 'apr' => 4,
            'may' => 5, 'mai' => 5, 'june' => 6, 'jun' => 6, 'juni' => 6, 'july' => 7, 'jul' => 7, 'juli' => 7,
            'august' => 8, 'aug' => 8, 'september' => 9, 'sep' => 9, 'sept' => 9,
            'october' => 10, 'oct' => 10, 'oktober' => 10, 'okt' => 10, 'november' => 11, 'nov' => 11,
            'december' => 12, 'dec' => 12, 'dezember' => 12, 'dez' => 12,
        ];
        $names = implode('|', array_map(fn ($n) => preg_quote($n, '/'), array_keys($months)));

        $day = $month = $year = null;

        if (preg_match('/\b(\d{1,2})(?:st|nd|rd|th)?\.?\s*(?:of\s+)?(' . $names . ')\b\.?(?:,?\s*(\d{4}))?/u', $raw, $m)) {
            [$day, $month, $year] = [(int) $m[1], $months[$m[2]], isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null];
        } elseif (preg_match('/\b(' . $names . ')\b\.?\s*(\d{1,2})(?:st|nd|rd|th)?\b(?:,?\s*(\d{4}))?/u', $raw, $m)) {
            [$day, $month, $year] = [(int) $m[2], $months[$m[1]], isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null];
        }

        if ($day === null) {
            return null;
        }

        if ($year !== null) {
            return $past($make($year, $month, $day));
        }

        $thisYear = $make((int) $today->year, $month, $day);

        return $thisYear && $thisYear->lt($today) && $thisYear->diffInDays($today) <= 60 ? $thisYear : null;
    }

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
            || preg_match('/\b(tomorrow|today|day after tomorrow|tonight|heute|morgen|yesterday|gestern|vorgestern)\b/u', $value) === 1
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
     * The answer to "show me more" when every chip of the current question is
     * already on screen, or null when there really is more to show.
     */
    private function nothingMoreToShow(array $state): ?string
    {
        $step = (string) ($state['step'] ?? '');

        $total = match ($step) {
            'doctor' => count($state['doctors'] ?? []),
            'slot_date' => count($this->dateCards($state['slots'] ?? [])),
            'slot_time' => count($this->timeCards($state['slots'] ?? [], $state['slot_date'] ?? null, $state['slot_window'] ?? null)),
            default => null,
        };

        if ($total === null) {
            return null;
        }

        $shown = max(3, (int) config('ai-assistant.chips_per_page', 6)) * (1 + (int) ($state['chip_page'] ?? 0));

        if ($total > $shown) {
            return null;
        }

        return match ($step) {
            'doctor' => "Those are all the doctors I have. Please pick one from the list.",
            'slot_time' => "Those are all the free times for that day. Please pick one, or choose another day.",
            default => "Those are all the free days I can see right now. Please pick one, or tell me a day that suits you.",
        };
    }

    /**
     * A bare acknowledgement - "ok", "ok ok", "alright", "bye". Only used at a dead end,
     * where there is nothing to answer, so it can never be read as an intent.
     */
    private function acknowledges(string $text): bool
    {
        $value = $this->normalizeText($text);

        return $value !== ''
            && preg_match('/^(?:(?:ok|okay|okey|k|alright|all right|fine|got it|i see|understood|sure|bye|goodbye|see you|tschüss|tschuss|tschus|ciao|ok danke|alles klar|gut|verstanden)(?:\s+|$))+$/u', $value) === 1;
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

        // Said goodbye to: the conversation is over, and so is the session.
        // converse() clears it once this turn has been answered.
        if (config('ai-assistant.end_after_goodbye', true)) {
            $state['_ended'] = 'goodbye';
        }

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

    /**
     * Turning down the whole list of times rather than one time in it.
     *
     * "not that one" means show me the others; "I don't want any of these"
     * means none of them work. Both are refusals, so saidNo() cannot separate
     * them - what does is the "any", "anything" or "none". Called only from the
     * slot_time step, where that difference decides whether the same list is
     * worth showing again.
     *
     * "I don't want this" and "not this" are deliberately NOT matched: "this"
     * may well be one particular time, and re-showing the list is the right
     * answer to that.
     */
    private function rejectsAllSlots(string $text): bool
    {
        $value = $this->normalizeText($text);

        // A named time or a position is a choice, however it is worded.
        if ($value === '' || $this->extractTime($text) !== '' || $this->saysPosition($text)) {
            return false;
        }

        return preg_match('/\bnone of (these|them|those)\b/u', $value) === 1
            || preg_match('/\b(dont|do not|don t)\b[^.]{0,20}\b(any|anything|none)\b/u', $value) === 1
            || preg_match('/\b(any|anything) of (these|them|those)\b/u', $value) === 1;
    }

    /**
     * Asking to change something the practice holds about them.
     *
     * The assistant registers a patient once and has no update call - there is
     * no such endpoint in PureMedApiClient - so this can only ever be answered
     * honestly, never acted on. Saying so is still far better than the doctor
     * matcher's "I didn't catch which doctor you meant".
     *
     * Both halves are required: a verb that means change AND one of the fields
     * a patient actually has. "change the time" and "change the day" are about
     * the appointment and are handled by their own steps, so neither the times
     * nor the days appear in the second list.
     */
    private function asksToChangeDetails(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        return preg_match(
            '/\b(change|update|correct|edit|amend|fix)\b[^.]{0,14}'
            . '\b(gender|sex|name|email|e mail|date of birth|birth date|dob'
            . '|mobile|phone|details|information)\b/u',
            $value
        ) === 1;
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
    /**
     * A refusal built out of an agreement word: "it's not right".
     *
     * The affirmative matcher sees "right" and nothing else, so without this a
     * patient who said no had their email stored, their appointment booked or
     * their booking cancelled.
     *
     * Deliberately narrow. The negator has to sit directly in front of the
     * agreement word, with at most one word between them for "not quite
     * right". A bare "not" anywhere in the sentence is NOT a refusal and must
     * never become one: it used to be, and "you haven't shown me the slots"
     * threw away the appointment the patient had chosen (see wantsSlotList()).
     *
     * normalizeText() has already turned "isn't" into "isn t".
     */
    private function negatesAgreement(string $value): bool
    {
        return preg_match(
            '/\b(not|isn t|ain t|aint|wasn t|won t)\b(\s+\w+)?\s+\b(right|correct|ok|okay|fine|good|perfect|sure|yes|yeah|yep)\b/',
            $value
        ) === 1;
    }

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

        // "it's not right" - a refusal made out of an agreement word.
        if ($this->negatesAgreement($value)) {
            return true;
        }

        // The same refusals in German. A separate pattern because it needs /u
        // for the umlauts, which the English ones above were never written for.
        if (preg_match('/\b(nein|nee|nö|noe|nicht|kein|keine|falsch|abbrechen)\b/u', $value) === 1) {
            return true;
        }

        // The whole answer is the refusal: "wrong", "that's wrong",
        // "incorrect", "a mistake". Anchored on purpose. "you have taken wrong
        // time I said 17" also contains "wrong", but it names a new time and
        // the slot step corrects the booking with it - reading that as a bare
        // refusal would throw the correction away.
        if (preg_match('/^(that s |it s |this is |thats )?(a )?(wrong|incorrect|mistake|not right|not correct)$/', $value) === 1) {
            return true;
        }

        // Otherwise, asking to see the options is a request, not a refusal.
        if ($this->wantsSlotList($value)) {
            return false;
        }

        return preg_match('/\b(n|another|other|change|different|later|wait|keep it|keep them all|pick another)\b/', $value) === 1
            || preg_match('/\b(andere|anderen|anderes|ändern|aendern|später|spaeter|warten|behalten|stopp)\b/u', $value) === 1;
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

        // saidNo() already refuses these, so this is a second lock on the same
        // door. It stays because of what is behind the door: this answer books
        // an appointment, cancels one, or stores the address a confirmation is
        // sent to, and none of those can be taken back.
        if ($this->negatesAgreement($value)) {
            return false;
        }

        return preg_match(
            '/\b(yes|y|yeah|yep|yup|ok|okay|sure|confirm|confirmed|correct|right|go ahead|book it|do it|fine|works|sounds good|please do|perfect|lets do it)\b/',
            $value
        ) === 1
            // German. "buchen" and "absagen" alone are deliberately NOT here: a
            // bare "Termin buchen" is a request, and must never count as agreeing.
            || preg_match(
                '/\b(ja|jawohl|jep|jup|klar|gerne|genau|richtig|stimmt|korrekt|in ordnung|einverstanden|passt|sicher|natürlich|natuerlich|selbstverständlich|bestätigt|bestaetigt)\b/u',
                $value
            ) === 1;
    }

    /**
     * Has the patient clearly agreed to book ('book') or to cancel ('cancel')?
     *
     * The ONLY gate in front of book() and cancelAppointment(). Two things must
     * both hold:
     *  - the conversation is really at that question, with what it asks about
     *    actually chosen. A "yes" that arrives anywhere else, or before there
     *    is anything to say yes to, is not consent;
     *  - the answer is a chip tap on that question, or a short free-text answer
     *    that ConsentRules recognises as a plain yes in the patient's language.
     *
     * The AI plays no part: its reading of a sentence, and its confidence in
     * that reading, are never consulted.
     */
    private function consents(string $kind, string $choiceValue, string $text, array $state): bool
    {
        if (!$this->consentContextValid($kind, $state)) {
            return false;
        }

        // A button is exactly what the patient chose - but only the "yes" one.
        if ($choiceValue !== '') {
            return $choiceValue === 'yes';
        }

        return app(ConsentRules::class)->isClearConsent($text, $this->language(), $kind === 'cancel');
    }

    /** Is there a pending booking, or a pending cancellation, to agree to? */
    private function consentContextValid(string $kind, array $state): bool
    {
        if ($kind === 'book') {
            $slot = $state['slot'] ?? null;

            return ($state['step'] ?? '') === 'confirm'
                && is_array($slot)
                && !empty($slot['slot_date'])
                && !empty($slot['time'])
                && isset($slot['time_slot_id'])
                && !empty($state['doctor']['id'])
                && !empty($state['appointment_type']['id'])
                && !empty($state['patient_id'])
                && !empty($state['token']);
        }

        $target = $state['cancel_target'] ?? null;

        return ($state['step'] ?? '') === 'cancel_confirm'
            && is_array($target)
            && isset($target['id'])
            && !empty($state['patient_id'])
            && !empty($state['token']);
    }

    /**
     * Spend the consent given for this answer. It can be used once, for the
     * action it was given for, and only if consents() recorded it.
     */
    private function takeConsent(array &$state, string $kind): bool
    {
        $given = ($state['_consent'] ?? null) === $kind;
        unset($state['_consent']);

        if (!$given) {
            Log::warning('AI assistant: refused to act without recorded consent', [
                'action' => $kind,
                'step' => $state['step'] ?? null,
            ]);
        }

        return $given;
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
        // "full", "complete" and "whole" are here because the question itself
        // asks for a full name, so answering "my full name is Kritika Sen" is
        // the most natural thing to do - and without them the whole sentence
        // reached the word cap below and was turned away as not a name.
        $value = preg_replace(
            "/^(?:my\s+(?:first\s+|last\s+|sur|full\s+|complete\s+|whole\s+)?name(?:'s|\s+is)?"
            . "|i\s*am|i'm|it'?s|this\s+is|call\s+me"
            . "|mein\s+(?:vor|nach|voll\w*\s+)?name\s+ist|ich\s+hei(?:ß|ss)e|ich\s+bin|das\s+ist)\s+/iu",
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
            // German: "punkt", "ät" and "klammeraffe" are how it is said aloud.
            ' bindestrich ' => '-',
            ' unterstrich ' => '_',
            ' klammeraffe ' => '@',
            ' ät ' => '@',
            ' punkt ' => '.',
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
    /**
     * Does this text name a day and a month of its own?
     *
     * The guard for the last-resort parse. Carbon defaults anything absent to
     * today's values, so a year on its own silently becomes a full date. Only
     * two shapes count: numbers separated the way a date is written, or a
     * number sitting beside a month name.
     */
    private function carriesDayAndMonth(string $candidate): bool
    {
        // 5/12, 05.12, 5-12 - two numbers written as a date.
        if (preg_match('/\d{1,2}\s*[.\/-]\s*\d{1,2}/u', $candidate) === 1) {
            return true;
        }

        // "5 december" or "december 5" - a day beside a month name.
        $months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';

        return preg_match('/\b\d{1,2}\b[^0-9]{0,12}\b(?:' . $months . ')/iu', $candidate) === 1
            || preg_match('/\b(?:' . $months . ')[a-z]*\b[^0-9]{0,12}\b\d{1,2}\b/iu', $candidate) === 1;
    }

    /**
     * "15. März 1990" read as "15 March 1990", so the formats below, which know
     * English month names, apply to a German answer unchanged. The full stop
     * after the day is how German writes an ordinal and is dropped with it.
     */
    private function germanMonthsToEnglish(string $value): string
    {
        $months = [
            'januar' => 'January', 'jänner' => 'January', 'jaenner' => 'January', 'februar' => 'February',
            'märz' => 'March', 'maerz' => 'March', 'april' => 'April', 'mai' => 'May', 'juni' => 'June',
            'juli' => 'July', 'august' => 'August', 'september' => 'September', 'oktober' => 'October',
            'november' => 'November', 'dezember' => 'December', 'okt' => 'Oct', 'dez' => 'Dec',
            'mär' => 'Mar', 'mrz' => 'Mar',
        ];

        $value = preg_replace('/\b(\d{1,2})\.\s*(?=\p{L})/u', '$1 ', $value);

        return preg_replace_callback(
            '/\b(' . implode('|', array_keys($months)) . ')\b\.?/iu',
            static fn (array $m): string => $months[mb_strtolower($m[1])],
            $value
        );
    }

    private function parseBirthDate(string $value): ?Carbon
    {
        // Spacing only, never a rule: what counts as a plausible birth date is
        // decided below exactly as it was. Without this the year in "april1994"
        // has no word boundary in front of it, and the guard turns the answer
        // away before a single format is tried.
        $value = trim(preg_replace('/\s+/', ' ', $this->spaceAroundMonth($this->germanMonthsToEnglish($value))));

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
            // Carbon fills in whatever the text leaves out, and what it fills
            // in is TODAY. A bare "1990" came back as the 19th of August 1990
            // on the 19th of August - a date of birth the patient never gave,
            // stored without a word. Nothing here is trustworthy unless the
            // patient supplied a day and a month themselves.
            if (!$this->carriesDayAndMonth($candidate)) {
                continue;
            }

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
            // A month written hard against the day or the year, as speech and
            // fast typing produce it: "1april 1994".
            $candidate = $this->spaceAroundMonth($candidate);
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
    /**
     * Put back the space a month name lost.
     *
     * "1april 1994" and "1april1994" are what fast typing and speech produce.
     * Every date pattern expects the day, the month and the year to be
     * separated, so without those spaces the date is invisible - and an
     * invisible date does not get taken out of the answer before the phone
     * number is read from it. "9876543454 and 1april 1994" became the fifteen
     * digit mobile number 987654345411994, one digit under the limit that
     * would have rejected it.
     *
     * Deliberately anchored on the month names themselves rather than on any
     * digit meeting any letter: it can only ever fire where a real month sits
     * against a number, so a time, an email or a phone number cannot be
     * touched by it.
     */
    private function spaceAroundMonth(string $value): string
    {
        $months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';

        // "1april" -> "1 april"
        $value = preg_replace('/\b(\d{1,2})(?=(?:' . $months . ')[a-z]*)/iu', '$1 ', $value);

        // "april1994" -> "april 1994"
        return preg_replace('/\b((?:' . $months . ')[a-z]*)(?=(?:18|19|20)\d{2}\b)/iu', '$1 ', $value);
    }

    private function withoutBirthDate(string $value): string
    {
        return trim(preg_replace($this->birthDatePatterns(), ' ', $this->spaceAroundMonth($value)));
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
     * Split "Anna Meier, anna@example.com" into the words that may be a name and
     * the address. Only a typed address (it contains an @) is split off; a spoken
     * one ("anna at example dot com") is left whole for the email capture, as
     * before. Nothing here judges either half - cleanName() and captureEmail()
     * still do.
     *
     * @return array{name_text: ?string, email: ?string}
     */
    private function splitNameEmail(string $text): array
    {
        $none = ['name_text' => null, 'email' => null];

        if (!preg_match('/[^\s,;<>()]+@[^\s,;<>()]+\.[^\s,;<>()]+/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return $none;
        }

        $email = rtrim($m[0][0], '.');
        $rest = substr($text, 0, $m[0][1]) . ' ' . substr($text, $m[0][1] + strlen($m[0][0]));

        // The words that introduce the address are not part of the name.
        $lead = '(?:(?:and|und)\s+)?(?:(?:my|the|meine|mein|die)\s+)?(?:e-?mail(?:[\s-]+address)?|e-?mail-adresse|adresse)(?:\s+(?:is|ist))?';
        $rest = preg_replace('/[\s,;:\-]*' . $lead . '[\s,;:\-]*$/iu', ' ', $rest);
        $rest = preg_replace('/^[\s,;:\-]*' . $lead . '[\s,;:\-]*/iu', ' ', $rest);
        $rest = trim(preg_replace('/\s+/u', ' ', $rest), " 	.,;:-");

        // "it's anna@example.com" or "here is anna@example.com" leave only
        // words that introduce the address, which are not a name either.
        $filler = ['it', "it's", 'its', 'is', 'here', "here's", 'this', "this's", 'that', "that's", 'my', 'the', 'and',
            'und', 'ist', 'das', 'hier', 'meine', 'mein', 'die', 'e-mail', 'email', 'mail', 'address', 'adresse'];
        $words = preg_split('/\s+/u', mb_strtolower($rest), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === [] || array_diff($words, $filler) === []) {
            $rest = '';
        }

        return ['name_text' => $rest === '' ? null : $rest, 'email' => $email];
    }

    /**
     * Before the patient is created: read the details back, unless the
     * practice switched that off. Reached only once the code has been read
     * back; a caller forcing the step gets the code question instead.
     */
    private function askDetailsConfirm(array &$state, PureMedApiClient $client, PatientAuthenticator $authenticator): array
    {
        if (!$this->verifiedForRegistration($state)) {
            $state['step'] = 'otp_verify';

            return [$this->say('Before I set you up, please read me the four digit code I sent you.', 'error')];
        }

        if (!config('ai-assistant.confirm_details', true)) {
            return $this->registerAndLoadDoctors($state, $client, $authenticator);
        }

        $state['step'] = 'details_confirm';

        return [];
    }

    /**
     * Apply a correction to the details given so far: a full name, a gender, an
     * email address, or any of them together. Each goes through the validator
     * it always had.
     *
     * @param  bool  $strict  true when the text was not asked for as a correction, so a lone word is not taken for a name
     * @return array<int, array>|null  replies (empty when applied, a message when refused), or null when the
     *                                 text held nothing that could be a name, a gender or an address
     */
    private function applyDetailsEdit(string $text, array &$state, bool $strict = false): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $combined = $this->splitNameEmail($text);
        $rest = $combined['email'] !== null ? $combined['name_text'] : $text;
        $changes = [];

        if ($combined['email'] !== null) {
            $capture = $this->captureEmail($combined['email']);

            if (!$capture['valid'] || $capture['confidence'] < 0.5 || $capture['suggestion'] !== null) {
                return [$this->say("That doesn't look like a valid email address. Please type it in.")];
            }

            $changes['email'] = $capture['email'];
        }

        if ($rest !== null && $rest !== '') {
            $found = $this->splitGenderFromName($rest);

            if ($found['gender'] !== null) {
                $changes['gender'] = $found['gender'];
            }

            if ($found['name'] !== null) {
                $parts = $this->splitName($found['name']);

                if (!$parts['first']) {
                    return [$this->say("I couldn't read a name in that. Could you tell me your full name again?")];
                }

                if (!$parts['last']) {
                    // Typed at the read-back, a single word is more likely "perhaps" than a name.
                    return $strict ? null : [$this->say('Please give me your first and last name.')];
                }

                $changes['first_name'] = $parts['first'];
                $changes['last_name'] = $parts['last'];
            }
        }

        if (!$changes) {
            return null;
        }

        foreach ($changes as $field => $value) {
            $state['patient'][$field] = $value;
        }

        $state['step'] = 'details_confirm';

        return [];
    }

    /**
     * Take a gender out of the words that may be a name.
     *
     * Only when it stands apart: its own item ("Anna Meier, female", "female;
     * Anna Meier") or, with a name before it, one of the words that cannot be a
     * name ("Anna Meier female"). "Thomas Mann" and "Frau" inside a name are
     * left alone, and the speech-recogniser's "mail" for "male" is never taken.
     *
     * @return array{name: ?string, gender: ?string}
     */
    private function splitGenderFromName(string $text): array
    {
        $gender = null;
        $kept = [];

        // Two of them ("male female") is a muddle, not an answer: nothing is
        // taken, so the whole answer is asked for again.
        if (preg_match_all('/\b(?:male|female|weiblich|m(?:ä|ae)nnlich)\b/iu', $text) > 1) {
            return ['name' => null, 'gender' => null];
        }

        foreach (preg_split('/\s*[,;]\s*|\s+[-–]\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $segment) {
            $bare = preg_replace('/^(?:(?:gender|sex|geschlecht)\s*[:=]?\s*)?(?:(?:i\s*am|i\'m|ich\s+bin)\s+)?/iu', '', trim($segment));

            if ($gender === null && preg_match('/^(male|female|man|woman|weiblich|m(?:ä|ae)nnlich|frau|mann|m|w|f)\.?$/iu', $bare) === 1) {
                $gender = $this->normalizeGender($bare);

                if ($gender !== null) {
                    continue;
                }
            }

            $kept[] = $segment;
        }

        $name = trim(implode(' ', $kept));

        // "Anna Meier female": a word that cannot be part of a name, after one that can.
        if ($gender === null && preg_match('/^(.+?)\s+(male|female|weiblich|m(?:ä|ae)nnlich)\.?$/iu', $name, $m) === 1) {
            $gender = $this->normalizeGender($m[2]);

            if ($gender !== null) {
                $name = trim($m[1]);
            }
        }

        return ['name' => $name === '' ? null : $name, 'gender' => $gender];
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
    /**
     * Send a new patient the code that has to be confirmed before they exist.
     *
     * Both channels are tried for the same code, so an SMS that never arrives
     * is not the end of the road - the same digits are in their inbox. What
     * the patient is told names only the channel that actually worked: sending
     * them to an inbox nothing reached is worse than saying nothing.
     */
    private function sendVerificationCode(array &$state, OtpService $otp): array
    {
        $mobile = (string) ($state['patient']['mobile_no'] ?? '');
        $birth = (string) ($state['patient']['birth_date'] ?? '');
        $email = $state['patient']['email'] ?? null;

        $result = $otp->send($mobile, $birth, $email, '', $this->language(), $state['patient']['country_code'] ?? null);

        if (!$result['sent']) {
            // Nothing was sent, so nothing is remembered and nothing is
            // created. The patient is told plainly rather than left waiting
            // for a code that is not coming.
            $state['step'] = 'otp_failed';

            if (!empty($result['blocked'])) {
                return [$this->say($this->blockedMessage($result), 'error')];
            }

            return [$this->say("I could not send you a verification code just now. Please call the practice and they will book this for you.", 'error')];
        }

        $state['step'] = 'otp_verify';
        $state['otp_attempts'] = 0;

        return [$this->say('I have sent a four digit code to ' . $this->codeWentTo($result) . '.')];
    }

    /**
     * A fresh code, up to the point where asking again is abuse rather than
     * bad luck. Each one replaces the last, so only the newest works.
     *
     * Two limits apply: this conversation's own, and - the one that cannot be
     * got round by starting again - the hourly limit on the number itself.
     */
    private function resendVerificationCode(array &$state, OtpService $otp): array
    {
        $sent = (int) ($state['otp_resends'] ?? 0);

        if ($sent >= (int) config('ai-assistant.otp_max_resends', 2)) {
            return [$this->say("I have sent that code a few times now. Please call the practice and they will help you from here.", 'error')];
        }

        $state['otp_resends'] = $sent + 1;
        $state['otp_attempts'] = 0;

        if (!empty($state['otp_login'])) {
            return $this->sendReturningCode($state, $otp, false);
        }

        return $this->sendVerificationCode($state, $otp);
    }

    /**
     * Send the code a returning patient must read back, to the mobile and email
     * the practice holds for them - never to anything said in the conversation.
     *
     * @param  bool  $intro  say why a code is being sent (the first time, not on a resend)
     */
    private function sendReturningCode(array &$state, OtpService $otp, bool $intro = true): array
    {
        $login = $state['otp_login'];

        $result = $otp->sendToPatient(
            (string) $state['patient']['mobile_no'],
            (string) $state['patient']['birth_date'],
            $login,
            $this->language()
        );

        if (!$result['sent']) {
            $state['step'] = 'otp_failed';
            $state['otp_login'] = null;

            if (!empty($result['blocked'])) {
                return [$this->say($this->blockedMessage($result), 'error')];
            }

            // The text message did not go out. A returning patient is verified
            // through their phone, so there is no way in without it.
            return [$this->say('I could not send a verification code to the mobile number on file, so for your security I cannot continue. Please call the practice.', 'error')];
        }

        $state['step'] = 'otp_verify';
        $state['otp_attempts'] = 0;

        $replies = [];

        if ($intro) {
            $replies[] = $this->say('For your security, I will send you a verification code first.');
        }

        $replies[] = $this->say('I have sent a four digit code to ' . $this->codeWentTo($result) . '.');

        return $replies;
    }

    /**
     * The code was right. Only now does the patient get a token, a name said
     * back to them, or anything of theirs.
     */
    private function finishReturningLogin(array &$state, PureMedApiClient $client, PatientAuthenticator $authenticator): array
    {
        $login = $state['otp_login'];

        // Looked up afresh and compared with who the code was sent to, rather
        // than trusting what was remembered a minute ago.
        $existing = $authenticator->authenticate(
            (string) $state['patient']['mobile_no'],
            (string) $state['patient']['birth_date'],
            true
        );

        if (!$existing || (int) $existing['patient_id'] !== (int) ($login['patient_id'] ?? 0)) {
            $state['step'] = 'otp_failed';
            $state['otp_login'] = null;
            $state['otp_verified'] = false;

            return [$this->say('I could not complete the verification. Please call the practice.', 'error')];
        }

        $state['otp_login'] = null;

        $replies = $this->resumeExistingPatient($state, $existing, $client);

        // Signed last, once the identity is final, so it covers exactly what the
        // session will carry from here on.
        if (!empty($state['patient_id'])) {
            $state['auth_proof'] = $this->authProof($state);
        }

        return $replies;
    }

    /** What to say when a number has run into the send limit or a lockout. */
    private function blockedMessage(array $result): string
    {
        if (($result['blocked'] ?? '') === 'guard_unavailable') {
            return self::OTP_UNAVAILABLE;
        }

        if (($result['blocked'] ?? '') === 'locked') {
            return $this->lockedMessage((int) ($result['retry_after'] ?? 0));
        }

        $minutes = max(1, (int) ceil(((int) ($result['retry_after'] ?? 60)) / 60));

        return 'I have already sent several codes to this number. For your security I cannot send another for '
            . $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes') . ' - please try again later or call the practice.';
    }

    private function lockedMessage(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return 'Too many incorrect codes. For your security, verification is locked for '
            . $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes') . '. Please try again later or call the practice.';
    }

    /** Only the channels that genuinely delivered. */
    private function codeWentTo(array $result): string
    {
        if ($result['sms'] && $result['email']) {
            return 'your mobile and your email';
        }

        return $result['sms'] ? 'your mobile' : 'your email';
    }

    /** "send it again", "I didn't get it", "resend the code". */
    /**
     * Is the patient asking to use a different mobile number?
     *
     * Needs a change word AND the number - "change my gender", "send me a new
     * code" and "my code is 7134" are not it - in English or German.
     */
    private function wantsToChangePhone(string $text): bool
    {
        // A code, even with words around it, is an answer to the question.
        if ($this->readCode($text) !== null) {
            return false;
        }

        $t = ' ' . $this->normalizeText($text) . ' ';
        $number = '(?:phone|mobile|cell|cellphone|number|nr)';

        $english = [
            '/ (?:change|changing|update|edit|correct|fix|switch|replace|alter) (?:\w+ ){0,4}' . $number . ' /u',
            '/ (?:wrong|incorrect|false) (?:\w+ )?' . $number . ' /u',
            '/ (?:different|another|other|new) (?:phone|mobile|cell|cellphone)(?: (?:number|no|nr))? /u',
            '/ (?:different|another|other|new) number /u',
            '/ not my (?:phone |mobile |cell )?(?:number|phone|mobile) /u',
        ];

        foreach ($english as $pattern) {
            if (preg_match($pattern, $t)) {
                return true;
            }
        }

        // German: a number word together with a word for changing, wrong, or another.

        if (preg_match('/(?:nummer|handy|telefon|mobil)/u', $t)) {
            if (preg_match('/ (?:ändern|aendern|andern|korrigieren|wechseln|ersetzen|aktualisieren|ändere|korrigiere)/u', $t)
                || preg_match('/ (?:falsch|falsche|falschen|falscher|falsches|andere|anderen|anderes|neue|neuen|neues|nicht meine|stimmt nicht)/u', $t)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Forget the number (and the country chosen for it, and any verification
     * earned for it) and ask for a number again. The date of birth and the other
     * details given are kept. Nothing is sent, and the limits - which belong to
     * the mobile number on the server - are untouched.
     */
    private function restartWithNewNumber(array &$state): array
    {
        unset($state['patient']['mobile_no'], $state['patient']['country_code'], $state['patient']['country']);

        $state['otp_login'] = null;
        $state['otp_verified'] = false;
        $state['otp_verified_mobile'] = null;
        $state['otp_attempts'] = 0;
        $state['otp_resends'] = 0;
        $state['country_options'] = [];
        $state['country_free'] = false;
        $state['pending_email'] = null;
        $state['pending_email_heard'] = null;
        $state['step'] = 'mobile_no';
        $state['_handled'] = true;

        return [$this->say("No problem - let's use a different number.")];
    }

    /**
     * The four digit code in what the patient typed or said, or null.
     *
     * Digits may be separated by spaces or punctuation ("7 1 3 4", "7-1-3-4"),
     * and a few harmless words may surround them ("my code is 7134", "mein Code
     * ist 7134 bitte"). Any other word, any letter stuck to a digit, and any
     * count of digits but four gives null.
     */
    private function readCode(string $text): ?string
    {
        $filler = [
            'code', 'is', 'its', 'it', 'my', 'the', 'please', 'thanks', 'thank', 'you', 'number', 'here', 'yes', 'ok', 'okay', 'sorry',
            'der', 'die', 'das', 'ist', 'mein', 'meine', 'bitte', 'danke', 'nummer', 'hier', 'ja',
        ];

        $tokens = preg_split('/[^\p{L}\p{N}]+/u', str_replace(["'", '’', '`'], '', mb_strtolower(trim($text))), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $digits = '';

        foreach ($tokens as $token) {
            if (preg_match('/^[0-9]+$/', $token)) {
                $digits .= $token;
            } elseif (!in_array($token, $filler, true)) {
                return null;
            }
        }

        return strlen($digits) === 4 ? $digits : null;
    }

    private function wantsAnotherCode(string $text): bool
    {
        $value = $this->normalizeText($text);

        if ($value === '') {
            return false;
        }

        return preg_match('/\b(resend|send it again|send again|another code|new code|didn t get|did not get|not received|never got|nothing came'
            . '|nochmal|noch einmal senden|erneut senden|erneut|neuen code|neuer code|code erneut|nicht erhalten|nicht bekommen|nichts bekommen|nicht angekommen)\b/u', $value) === 1;
    }

    /**
     * Has this patient proved the number they are registering with?
     *
     * Checked at the point of creation rather than at a step, because a step
     * lives in the session and a session can be tampered with. Forcing the
     * conversation forward reaches this and is refused here. The number is
     * compared too: a code proved for one mobile does not verify another.
     */
    private function verifiedForRegistration(array $state): bool
    {
        return !empty($state['otp_verified'])
            && (string) ($state['otp_verified_mobile'] ?? '') !== ''
            && (string) $state['otp_verified_mobile'] === (string) ($state['patient']['mobile_no'] ?? '');
    }

    private function identifyPatient(array &$state, PureMedApiClient $client, PatientAuthenticator $authenticator, ?OtpService $otp = null): array
    {
        // A returning patient reads back a code before anything of theirs is
        // shown or done. The lookup below creates no token, writes nothing and
        // reveals nothing: a mobile number and a date of birth are not proof.
        if (config('ai-assistant.verify_existing', true)) {
            $found = $authenticator->find($state['patient']['mobile_no'], $state['patient']['birth_date']);

            if ($found) {
                if (empty($found['active'])) {
                    $state['step'] = 'otp_failed';

                    return [$this->say('I cannot continue with this record. Please call the practice.', 'error')];
                }

                if (!$otp) {
                    $state['step'] = 'otp_failed';

                    return [$this->say(self::OTP_UNAVAILABLE, 'error')];
                }

                $state['otp_login'] = $found;

                return $this->sendReturningCode($state, $otp);
            }
        } else {
            $existing = $authenticator->authenticate(
                $state['patient']['mobile_no'],
                $state['patient']['birth_date']
            );

            if ($existing) {
                return $this->resumeExistingPatient($state, $existing, $client);
            }
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
        // register API requires are collected from here on - starting with where
        // their mobile number is from, so the code goes to the right country.
        $state['step'] = $this->nextRegistrationStep('birth_date');
        $this->askCountry($state, $client);

        return [$this->say("Thanks. I'll just need a few details to set you up.")];
    }

    private function isPlausibleBirthDate(Carbon $date): bool
    {
        return $date->isPast() && $date->year >= 1900 && $date->age <= 120;
    }

    /**
     * patients.gender is char(1) using PureMed's M / W convention.
     *
     * Matched word by word rather than against the whole answer, because people
     * reply "I'm male" rather than "male", and speech recognition writes that
     * word as "mail" - often twice, "mail mail", when it re-hears itself.
     *
     * Splitting into words is what makes this safe: "female" contains "male"
     * and "woman" contains "man", so a substring check would read both as male.
     * As whole words they are only ever themselves. Punctuation is trimmed from
     * each end but left inside, so "male." counts and "e-mail" stays one word
     * that matches nothing.
     *
     * Only the words listed here count - no other answer becomes a gender. An
     * answer carrying both, such as the question repeated back, is ambiguous
     * rather than a decision, so it is refused too.
     */
    private function normalizeGender(string $gender): ?string
    {
        $value = mb_strtolower(trim($gender));

        // An answer that is plainly about an email address is not an answer to
        // this question - and "mail" is exactly what the recogniser writes for
        // "male", so this has to be settled before any word is matched.
        if (preg_match('/@|\be[-\s]?mails?\b/u', $value) === 1) {
            return null;
        }

        $female = ['w', 'f', 'female', 'females', 'femail', 'femails', 'femaile',
            'woman', 'women', 'weiblich', 'frau'];
        $male = ['m', 'male', 'males', 'mail', 'mails', 'maile', 'mael',
            'man', 'men', 'maennlich', 'männlich', 'mann'];

        $found = [];

        foreach (preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            // Strip what surrounds the word, keep what is inside it.
            $word = preg_replace('/^[^\p{L}]+|[^\p{L}]+$/u', '', $word);

            if (in_array($word, $female, true)) {
                $found['W'] = true;
            }

            if (in_array($word, $male, true)) {
                $found['M'] = true;
            }
        }

        return count($found) === 1 ? array_key_first($found) : null;
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

        // The AI may steer a patient AWAY from a booking or cancellation here -
        // "no", "another time" - but never into one. Agreement is decided by
        // ConsentRules on the patient's own words; replaying "yes" on the
        // strength of the model's reading would let its judgement stand in for
        // theirs.
        if ($step === 'confirm' || $step === 'cancel_confirm') {
            return match ($intent) {
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

        // A conversation that says who the patient is must be able to PROVE a
        // code was read back for them in THIS session. The proof is a signature
        // over the patient, the number and date of birth they were verified
        // for, and the session itself, made with the application key - so it
        // cannot be copied from another session, kept after the identity
        // changes, or conjured by setting a patient id. One that fails is not
        // repaired: it is dropped, and the person starts again from the top.
        if (config('ai-assistant.verify_existing', true)
            && $stored !== []
            && (!empty($stored['patient_id']) || !empty($stored['token']))
            && !hash_equals($this->authProof($stored), (string) ($stored['auth_proof'] ?? ''))) {
            Log::warning('AI assistant: dropped a session whose verification could not be proved');
            session()->forget(self::SESSION_KEY);
            session()->forget(self::UNDO_KEY);
            $stored = [];
        }

        return array_merge($this->freshState(), $stored);
    }

    /**
     * The signature that says this session was verified for this patient.
     *
     * Bound to the session id as well as the identity, so a state copied into a
     * different session proves nothing there.
     */
    private function authProof(array $state): string
    {
        $material = implode('|', [
            (string) ($state['patient_id'] ?? ''),
            (string) ($state['patient']['mobile_no'] ?? ''),
            (string) ($state['patient']['birth_date'] ?? ''),
            (string) session()->getId(),
        ]);

        return hash_hmac('sha256', $material, (string) config('app.key'));
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
            // Verification of a new patient's mobile. Held against the number
            // it was proved for, so changing the mobile does not inherit it.
            'otp_verified' => false,
            'otp_verified_mobile' => null,
            // A returning patient being verified: what the practice holds for
            // them (never shown to the patient), until the code is read back.
            'otp_login' => null,
            // Signed once a code has been read back; see authProof().
            'auth_proof' => null,
            'otp_attempts' => 0,
            'otp_resends' => 0,
            // What was heard, when it differs from what is being suggested.
            'pending_email_heard' => null,
            'patient_id' => null,
            'token' => null,
            'doctors' => [],
            'doctor' => null,
            'appointment_types' => [],
            'appointment_type' => null,
            // The services PureMed offered for the chosen type (id, name, ticked,
            // locked), which type they were offered for, and which the patient
            // ticked. exam_ids is built from these and from nothing else.
            'services' => [],
            'services_for' => null,
            'service_ids' => null,
            // The practice's country list while a new patient is asked where their number is from.
            'country_options' => [],
            'country_free' => false,
            // True only when this conversation created the patient.
            'new_patient' => false,
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
            // The same appointments, shaped as the rows the prompt lists back.
            'appointment_list_cards' => [],
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
            'details_confirm' => ['Registering your details...', 'Finding available doctors...'],
            'doctor' => ['Loading appointment types...'],
            'appointment_type' => ['Loading the services...'],
            'services' => ['Checking available slots...'],
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
