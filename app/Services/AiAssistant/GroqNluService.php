<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Natural language understanding for the booking assistant, via the Groq API.
 *
 * This class is an INTERPRETATION LAYER ONLY. It turns a sentence into an
 * intent plus a hint about which of the options already on the patient's
 * screen they meant. It cannot reach PureMed, the database, the session or the
 * booking flow, and it never returns an identifier - the conversation engine
 * resolves whatever comes back against the real PureMed list.
 *
 * Every failure path returns null so the assistant falls back to its existing
 * deterministic matching. The booking flow keeps working with this switched
 * off, with no key configured, or with the Groq API down.
 */
class GroqNluService implements NluDriver
{
    /**
     * Steps allowed to use NLU.
     *
     * Registration steps (first_name, last_name, mobile_no, birth_date, email,
     * email_confirm, gender) are deliberately absent: those answers are patient
     * identifying information and must never leave this server. The controller
     * checks this too - it is asserted here as well so a future caller cannot
     * quietly widen the surface.
     */
    private const ELIGIBLE_STEPS = [
        'intent',
        'doctor',
        'appointment_type',
        'slot_date',
        'slot_time',
        'confirm',
        'cancel_select',
        'cancel_confirm',
        'appointments',
    ];

    private const INTENTS = [
        'book_appointment',
        'view_appointments',
        'cancel_appointment',
        'select_doctor',
        'change_doctor',
        'select_appointment_type',
        'change_appointment_type',
        'select_slot',
        'change_slot',
        'select_slot_preference',
        'change_date',
        'request_slots',
        'confirm_booking',
        'reject_booking',
        'unknown',
    ];

    private const TIME_PREFERENCES = ['morning', 'afternoon', 'evening'];

    /** Keep prompts small: cost control, and a long message is not a selection. */
    private const MAX_MESSAGE_CHARS = 200;
    private const MAX_OPTIONS = 40;
    private const MAX_OPTION_CHARS = 80;

    /**
     * Interpret one patient message in the context of the current question.
     *
     * @param  string  $step  the conversation step being answered
     * @param  array<int, string>  $optionLabels  labels already visible to the patient
     * @param  string  $message  what the patient typed or said
     * @return array{intent: string, entity: ?string, ordinal: ?int, date: ?string,
     *               time_preference: ?string, time: ?string, confidence: float}|null
     */
    public function interpret(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = []): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        // Defence in depth. Reaching here for a registration step would be a
        // caller bug, so it is loud in the log but still safe.
        if (!in_array($step, self::ELIGIBLE_STEPS, true)) {
            Log::warning('AI assistant NLU: refused ineligible step', ['step' => $step]);

            return null;
        }

        $message = trim($message);

        if ($message === '') {
            return null;
        }

        try {
            $response = Http::withToken((string) config('ai-assistant.groq.api_key'))
                ->timeout((int) config('ai-assistant.groq.timeout', 6))
                ->acceptJson()
                ->asJson()
                ->post((string) config('ai-assistant.groq.endpoint'), $this->payload($step, $optionLabels, $message, $previousAssistantMessage, $context));
        } catch (Throwable $exception) {
            // Message body is deliberately not logged - it is the patient's words.
            Log::warning('AI assistant NLU: request failed', [
                'step' => $step,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful()) {
            Log::warning('AI assistant NLU: non-success response', [
                'step' => $step,
                'status' => $response->status(),
            ]);

            return null;
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (!is_string($content) || $content === '') {
            Log::warning('AI assistant NLU: empty completion', ['step' => $step]);

            return null;
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            Log::warning('AI assistant NLU: completion was not JSON', ['step' => $step]);

            return null;
        }

        return $this->validate($decoded, $step);
    }

    /**
     * NLU is optional. No key, no model, or switched off means the assistant
     * simply carries on with its deterministic matching.
     */
    private function enabled(): bool
    {
        return (bool) config('ai-assistant.groq.enabled')
            && filled(config('ai-assistant.groq.api_key'))
            && filled(config('ai-assistant.groq.model'))
            && filled(config('ai-assistant.groq.endpoint'));
    }

    /**
     * @param  array<int, string>  $optionLabels
     * @return array<string, mixed>
     */
    private function payload(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = []): array
    {
        return [
            // Never hardcoded - supplied through GROQ_MODEL.
            'model' => (string) config('ai-assistant.groq.model'),
            'temperature' => 0,
            // Reasoning models spend tokens before emitting the JSON document.
            // 150 truncated them mid-document and the API rejected the call with
            // json_validate_failed; 600 is enough for every tested phrase. The
            // reply itself is tiny, so this is a ceiling, not a typical cost.
            'max_completion_tokens' => (int) config('ai-assistant.groq.max_tokens', 600),
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $this->userPrompt($step, $optionLabels, $message, $previousAssistantMessage, $context)],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'puremed_nlu',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return 'You interpret a patient message for a medical appointment booking assistant. '
            . 'Decide what the patient means in the context of the CURRENT STEP. '
            . '"entity" must be copied exactly from the option list, character for character, or null. '
            . '"ordinal" is the number of that same option, or null. entity and ordinal must always refer '
            . 'to the SAME option. Never invent a doctor, appointment type, date, time or availability, '
            . 'and never refer to anything not in the list. You do not decide what is available and you '
            . 'never book anything. '
            . 'Use request_slots when the patient is asking to SEE the available times rather than '
            . 'rejecting anything, for example "you have not shown me the slots", "what times are '
            . 'available", "let me see the available times", "are there any other times". '
            . 'Use reject_booking only when they are declining the appointment itself. '
            . 'When the patient wants to swap something in CURRENT SELECTION, use the intent for the '
            . 'part they are changing: change_doctor ("another doctor", "someone else"), '
            . 'change_appointment_type, change_date ("what about Friday", "any day next week") or '
            . 'change_slot ("another time", "something later"). Change only what they actually '
            . 'mentioned: "another time" does not change the date, and "another doctor" does not '
            . 'change the appointment type or the time. A patient may change two things at once, for '
            . 'example "another doctor tomorrow" - return change_doctor and put the day in "date". '
            . 'Use select_slot_preference when they name only a part of the day, such as "morning". '
            . 'Use view_appointments for any question ABOUT appointments the patient already has, '
            . 'for example "how many appointments do I have", "when is my next appointment", '
            . '"do I have any appointments", "what appointments are coming up", "list my appointments". '
            . 'Those are questions, not requests to make a new booking - use book_appointment only when '
            . 'they want a NEW appointment. '
            . 'CURRENT SELECTION is background only. Never copy anything out of it into your answer: '
            . '"date" and "time" must be the patient\'s own words, and must be null when the patient '
            . 'did not mention a day or a time. '
            . 'If the patient does not mind which option, return the select intent with entity and '
            . 'ordinal both null - never pick one for them. '
            . 'If you are unsure, return intent "unknown" with a low confidence.';
    }

    /**
     * Only the step name, the option labels the patient can already see, and
     * their message. No identifiers, no tokens, no patient details.
     *
     * @param  array<int, string>  $optionLabels
     */
    private function userPrompt(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = []): string
    {
        $prompt = 'CURRENT STEP: ' . $step . "\n";

        // What the patient has chosen so far, as the labels already on their
        // screen. Without this "another doctor" and "another time" look
        // identical to the model. Labels only - never ids, never patient data.
        $selection = array_filter([
            'Doctor' => $context['doctor'] ?? null,
            'Appointment' => $context['appointment'] ?? null,
            'Date' => $context['date'] ?? null,
            'Time' => $context['time'] ?? null,
        ]);

        if ($selection) {
            $prompt .= "CURRENT SELECTION:\n";

            foreach ($selection as $label => $value) {
                $prompt .= '- ' . $label . ': ' . mb_substr((string) $value, 0, self::MAX_OPTION_CHARS) . "\n";
            }
        }

        $options = array_slice(array_values(array_filter($optionLabels)), 0, self::MAX_OPTIONS);

        if ($options) {
            $prompt .= "AVAILABLE OPTIONS:\n";

            foreach ($options as $index => $label) {
                $prompt .= ($index + 1) . '. ' . mb_substr((string) $label, 0, self::MAX_OPTION_CHARS) . "\n";
            }
        }

        // One line of context - what the assistant just said - so a reply like
        // "you haven't shown me the slots" can be read against the question it
        // answers. Never the whole conversation, and never patient details.
        if ($previousAssistantMessage) {
            $prompt .= "PREVIOUS ASSISTANT MESSAGE:\n\""
                . mb_substr($previousAssistantMessage, 0, self::MAX_MESSAGE_CHARS) . "\"\n";
        }

        return $prompt . "\nPATIENT MESSAGE:\n\"" . mb_substr($message, 0, self::MAX_MESSAGE_CHARS) . '"';
    }

    /**
     * Strict schema: the model cannot return a field or an intent outside this.
     *
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            // Strict mode requires every property to be listed; optional values
            // are expressed as nullable rather than omitted.
            'required' => ['intent', 'entity', 'ordinal', 'date', 'time_preference', 'time', 'confidence'],
            'properties' => [
                'intent' => [
                    'type' => 'string',
                    'enum' => self::INTENTS,
                    'description' => 'What the patient wants to do.',
                ],
                'entity' => [
                    'type' => ['string', 'null'],
                    'description' => 'The option label the patient named, copied from the list. Null if they did not name one.',
                ],
                'ordinal' => [
                    'type' => ['integer', 'null'],
                    'description' => 'The 1-based position the patient referred to, e.g. "the second one". Null otherwise.',
                ],
                'date' => [
                    'type' => ['string', 'null'],
                    'description' => 'A day the patient mentioned, exactly as they said it, e.g. "tomorrow" or "13 August". Null otherwise.',
                ],
                'time_preference' => [
                    'type' => ['string', 'null'],
                    'enum' => ['morning', 'afternoon', 'evening', null],
                    'description' => 'Part of the day the patient asked for. Null otherwise.',
                ],
                'time' => [
                    'type' => ['string', 'null'],
                    'description' => 'A clock time the patient mentioned, as HH:MM. Null otherwise.',
                ],
                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                    'description' => 'How certain the interpretation is.',
                ],
            ],
        ];
    }

    /**
     * Trust nothing. A reply that does not fit the contract is discarded, and
     * the assistant falls back to deterministic matching.
     *
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>|null
     */
    private function validate(array $decoded, string $step): ?array
    {
        $intent = $decoded['intent'] ?? null;

        if (!is_string($intent) || !in_array($intent, self::INTENTS, true) || $intent === 'unknown') {
            return null;
        }

        $confidence = $decoded['confidence'] ?? null;

        if (!is_numeric($confidence)) {
            return null;
        }

        $confidence = (float) $confidence;
        $minimum = (float) config('ai-assistant.groq.min_confidence', 0.6);

        if ($confidence < $minimum) {
            Log::info('AI assistant NLU: below confidence threshold', [
                'step' => $step,
                'intent' => $intent,
                'confidence' => $confidence,
            ]);

            return null;
        }

        $ordinal = $decoded['ordinal'] ?? null;
        $ordinal = is_int($ordinal) && $ordinal >= 1 ? $ordinal : null;

        $timePreference = $decoded['time_preference'] ?? null;
        $timePreference = in_array($timePreference, self::TIME_PREFERENCES, true) ? $timePreference : null;

        return [
            'intent' => $intent,
            'entity' => $this->text($decoded['entity'] ?? null),
            'ordinal' => $ordinal,
            'date' => $this->text($decoded['date'] ?? null),
            'time_preference' => $timePreference,
            'time' => $this->text($decoded['time'] ?? null),
            'confidence' => $confidence,
        ];
    }

    private function text(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim(mb_substr($value, 0, self::MAX_OPTION_CHARS));

        return $value === '' ? null : $value;
    }
}
