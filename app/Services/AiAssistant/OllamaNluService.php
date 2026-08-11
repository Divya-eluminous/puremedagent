<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Natural language understanding via a local Ollama model.
 *
 * Runs entirely on this machine: no API key, no external service, no patient
 * data leaving the server. It receives the step name, the option labels already
 * on the patient's screen and their message, and returns an interpretation or
 * null - never an identifier.
 *
 * Results are validated again by NluManager before the conversation engine sees
 * them, because small local models contradict themselves.
 */
class OllamaNluService implements NluDriver
{
    /**
     * Steps allowed to use NLU. Registration steps are deliberately absent:
     * those answers are patient identifying information.
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

    private const MAX_MESSAGE_CHARS = 200;
    private const MAX_OPTIONS = 40;
    private const MAX_OPTION_CHARS = 80;

    public function interpret(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = []): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        // Defence in depth: the caller checks this too.
        if (!in_array($step, self::ELIGIBLE_STEPS, true)) {
            Log::warning('AI assistant NLU: refused ineligible step', ['step' => $step]);

            return null;
        }

        $message = trim($message);

        if ($message === '') {
            return null;
        }

        $started = microtime(true);

        try {
            $response = Http::timeout((int) config('ai-assistant.ollama.timeout', 30))
                ->acceptJson()
                ->asJson()
                ->post(
                    rtrim((string) config('ai-assistant.ollama.base_url'), '/') . '/api/chat',
                    $this->payload($step, $optionLabels, $message, $previousAssistantMessage, $context)
                );
        } catch (Throwable $exception) {
            // The patient's words are never logged.
            Log::warning('AI assistant NLU: request failed', [
                'step' => $step,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $latency = round((microtime(true) - $started) * 1000);

        if (!$response->successful()) {
            Log::warning('AI assistant NLU: non-success response', [
                'step' => $step,
                'status' => $response->status(),
                'latency_ms' => $latency,
            ]);

            return null;
        }

        $content = data_get($response->json(), 'message.content');

        if (!is_string($content) || trim($content) === '') {
            Log::warning('AI assistant NLU: empty completion', ['step' => $step, 'latency_ms' => $latency]);

            return null;
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            Log::warning('AI assistant NLU: completion was not JSON', ['step' => $step, 'latency_ms' => $latency]);

            return null;
        }

        return $this->validate($decoded, $step, $latency);
    }

    /** No model or base URL, or switched off, means the assistant uses its own rules. */
    private function enabled(): bool
    {
        return (bool) config('ai-assistant.ollama.enabled')
            && filled(config('ai-assistant.ollama.base_url'))
            && filled(config('ai-assistant.ollama.model'));
    }

    /**
     * @param  array<int, string>  $optionLabels
     * @return array<string, mixed>
     */
    private function payload(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = []): array
    {
        return [
            'model' => (string) config('ai-assistant.ollama.model'),
            'stream' => false,
            // Ollama constrains generation to this JSON schema.
            'format' => $this->schema(),
            'options' => [
                'temperature' => 0,
                'num_predict' => (int) config('ai-assistant.ollama.max_tokens', 200),
            ],
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $this->userPrompt($step, $optionLabels, $message, $previousAssistantMessage, $context)],
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
     * Step name, the labels the patient can already see, and their message.
     * Nothing else - no identifiers, tokens or patient details.
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

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'required' => ['intent', 'entity', 'ordinal', 'date', 'time_preference', 'time', 'confidence'],
            'properties' => [
                'intent' => ['type' => 'string', 'enum' => self::INTENTS],
                'entity' => ['type' => ['string', 'null']],
                'ordinal' => ['type' => ['integer', 'null']],
                'date' => ['type' => ['string', 'null']],
                'time_preference' => ['type' => ['string', 'null'], 'enum' => ['morning', 'afternoon', 'evening', null]],
                'time' => ['type' => ['string', 'null']],
                'confidence' => ['type' => 'number'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>|null
     */
    private function validate(array $decoded, string $step, float $latency): ?array
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

        if ($confidence < (float) config('ai-assistant.ollama.min_confidence', 0.7)) {
            Log::info('AI assistant NLU: below confidence threshold', [
                'step' => $step,
                'confidence' => $confidence,
                'latency_ms' => $latency,
            ]);

            return null;
        }

        $ordinal = $decoded['ordinal'] ?? null;
        $timePreference = $decoded['time_preference'] ?? null;

        Log::info('AI assistant NLU: interpreted', [
            'step' => $step,
            'intent' => $intent,
            'confidence' => $confidence,
            'latency_ms' => $latency,
        ]);

        return [
            'intent' => $intent,
            'entity' => $this->text($decoded['entity'] ?? null),
            'ordinal' => is_int($ordinal) && $ordinal >= 1 ? $ordinal : null,
            'date' => $this->text($decoded['date'] ?? null),
            'time_preference' => in_array($timePreference, self::TIME_PREFERENCES, true) ? $timePreference : null,
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
