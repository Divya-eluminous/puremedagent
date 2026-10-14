<?php

namespace App\Services\AiAssistant;

use Illuminate\Http\Client\ConnectionException;
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
 * Every failure path returns null and raises failed(), so the assistant can
 * tell the patient the AI is temporarily unavailable instead of guessing.
 * Choices made with the on-screen buttons never reach this class, so they keep
 * working with no key configured or with the Groq API down.
 *
 * The patient may write in English or German. The model reads either, but its
 * answer is always expressed in English terms - "tomorrow", "Friday", "morning"
 * - because the PureMed rules that act on it work on one vocabulary.
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

    private const LANGUAGES = ['en', 'de'];

    /**
     * What happened on the last interpret(), so the caller can tell a message
     * the AI could not read from an AI that could not be reached:
     *
     *  ok            an interpretation came back and passed validation
     *  unrecognized  the AI answered but had nothing usable: "unknown", too
     *                little confidence, or nothing to interpret
     *  timeout       no answer in time
     *  malformed     an answer arrived but broke the contract: not JSON, cut
     *                short, or outside the schema
     *  rate_limited  Groq answered 429, or the shared budget said wait
     *  unavailable   not configured, an error response, or the network is down
     */
    public const OUTCOME_OK = 'ok';
    public const OUTCOME_UNRECOGNIZED = 'unrecognized';
    public const OUTCOME_TIMEOUT = 'timeout';
    public const OUTCOME_MALFORMED = 'malformed';
    public const OUTCOME_RATE_LIMITED = 'rate_limited';
    public const OUTCOME_UNAVAILABLE = 'unavailable';

    /** The outcomes where the AI itself could not be used. */
    public const FAILURES = [
        self::OUTCOME_TIMEOUT,
        self::OUTCOME_MALFORMED,
        self::OUTCOME_RATE_LIMITED,
        self::OUTCOME_UNAVAILABLE,
    ];

    private string $outcome = self::OUTCOME_UNRECOGNIZED;

    /** Seconds the shared limiter said to wait, when the outcome is rate_limited. */
    private int $retryAfter = 0;

    /** Diagnostics from the last call: tokens, finish reason, latency. Never the message. */
    private array $meta = [];

    public function outcome(): string
    {
        return $this->outcome;
    }

    /**
     * Whether the last interpret() failed to get an answer from Groq - missing
     * configuration, a network error, a rate limit or a broken reply. A reply
     * that arrived but was not useful is not a failure.
     */
    public function failed(): bool
    {
        return in_array($this->outcome, self::FAILURES, true);
    }

    public function retryAfter(): int
    {
        return $this->retryAfter;
    }

    /** @return array<string, mixed> */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Interpret one patient message in the context of the current question.
     *
     * @param  string  $step  the conversation step being answered
     * @param  array<int, string>  $optionLabels  labels already visible to the patient
     * @param  string  $message  what the patient typed or said
     * @return array{intent: string, entity: ?string, ordinal: ?int, date: ?string,
     *               time_preference: ?string, time: ?string, confidence: float}|null
     */
    public function interpret(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = [], string $language = 'en'): ?array
    {
        $this->outcome = self::OUTCOME_UNRECOGNIZED;
        $this->retryAfter = 0;
        $this->meta = [];
        $language = in_array($language, self::LANGUAGES, true) ? $language : 'en';

        if (!$this->enabled()) {
            // Asked for, but there is no key or model to ask with.
            $this->outcome = self::OUTCOME_UNAVAILABLE;

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

        $payload = $this->payload($step, $optionLabels, $message, $previousAssistantMessage, $context, $language);

        // Spend from the shared budget first. Every session draws on the same
        // Groq key, so this is checked before anything is sent.
        $limiter = app(GroqRateLimiter::class);
        $estimate = $this->estimateTokens($payload);
        $grant = $limiter->reserve($estimate);

        if (!$grant['allowed']) {
            $this->outcome = self::OUTCOME_RATE_LIMITED;
            $this->retryAfter = (int) $grant['retry_after'];
            $this->meta = ['limiter' => $grant['reason']];

            Log::info('AI assistant NLU: not sent, rate limit', [
                'step' => $step,
                'reason' => $grant['reason'],
                'retry_after' => $this->retryAfter,
            ]);

            return null;
        }

        $started = microtime(true);

        try {
            $response = Http::withToken((string) config('ai-assistant.groq.api_key'))
                ->timeout((int) config('ai-assistant.groq.timeout', 6))
                ->acceptJson()
                ->asJson()
                // One more try, for a dropped connection only. The first request
                // after a quiet spell is the slow one, and an outage message for
                // a hiccup would be a false alarm. Never for a 429 or any other
                // answer from Groq: asking again straight away only spends more
                // of a budget that has just been refused.
                ->retry(2, 400, fn ($exception) => $exception instanceof ConnectionException, false)
                ->post((string) config('ai-assistant.groq.endpoint'), $payload);
        } catch (Throwable $exception) {
            $this->outcome = $this->looksLikeTimeout($exception->getMessage())
                ? self::OUTCOME_TIMEOUT
                : self::OUTCOME_UNAVAILABLE;
            $this->meta = ['latency_ms' => (int) ((microtime(true) - $started) * 1000)];

            // Message body is deliberately not logged - it is the patient's words.
            Log::warning('AI assistant NLU: request failed', [
                'step' => $step,
                'outcome' => $this->outcome,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $headers = $this->lowerHeaders($response);
        $usage = $response->json('usage.total_tokens');
        $this->meta = [
            'latency_ms' => (int) ((microtime(true) - $started) * 1000),
            'status' => $response->status(),
            'prompt_tokens' => $response->json('usage.prompt_tokens'),
            'completion_tokens' => $response->json('usage.completion_tokens'),
            'finish_reason' => $response->json('choices.0.finish_reason'),
        ];

        // What Groq reports about the budget is read on every answer, refusals
        // included, and the reservation is corrected to the real cost.
        $limiter->settle($estimate, is_numeric($usage) ? (int) $usage : null, $headers);

        if ($response->status() === 429) {
            $this->outcome = self::OUTCOME_RATE_LIMITED;
            $this->retryAfter = $limiter->trip(GroqRateLimiter::parseRetryAfter($headers['retry-after'] ?? null));

            Log::warning('AI assistant NLU: rate limited by Groq', [
                'step' => $step,
                'retry_after' => $this->retryAfter,
            ]);

            return null;
        }

        if (!$response->successful()) {
            $this->outcome = self::OUTCOME_UNAVAILABLE;

            Log::warning('AI assistant NLU: non-success response', [
                'step' => $step,
                'status' => $response->status(),
            ]);

            return null;
        }

        // Cut off by the completion limit: whatever is there is not a document
        // to trust, however much of it parses.
        if (($this->meta['finish_reason'] ?? null) === 'length') {
            $this->outcome = self::OUTCOME_MALFORMED;

            Log::warning('AI assistant NLU: completion truncated', ['step' => $step]);

            return null;
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (!is_string($content) || $content === '') {
            $this->outcome = self::OUTCOME_MALFORMED;

            Log::warning('AI assistant NLU: empty completion', ['step' => $step]);

            return null;
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            $this->outcome = self::OUTCOME_MALFORMED;

            Log::warning('AI assistant NLU: completion was not JSON', ['step' => $step]);

            return null;
        }

        return $this->validate($decoded, $step);
    }

    /** A rough cost for the reservation: the prompt's size plus a typical reply. */
    private function estimateTokens(array $payload): int
    {
        $chars = 0;

        foreach ((array) ($payload['messages'] ?? []) as $message) {
            $chars += mb_strlen((string) ($message['content'] ?? ''));
        }

        $prompt = max(
            (int) ceil($chars / 3.5),
            (int) config('ai-assistant.groq.rate_limit.estimated_prompt_tokens', 1300) * ($this->compact() ? 0.5 : 1)
        );

        return (int) $prompt + (int) config('ai-assistant.groq.rate_limit.estimated_completion_tokens', 350);
    }

    private function looksLikeTimeout(string $message): bool
    {
        return preg_match('/timed out|timeout|cURL error 28/i', $message) === 1;
    }

    /** @return array<string, string> */
    private function lowerHeaders($response): array
    {
        $out = [];

        foreach ($response->headers() as $name => $values) {
            $out[strtolower((string) $name)] = is_array($values) ? (string) ($values[0] ?? '') : (string) $values;
        }

        return $out;
    }

    private function compact(): bool
    {
        return (string) config('ai-assistant.groq.prompt', 'current') === 'compact';
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
    private function payload(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = [], string $language = 'en'): array
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
            // Optional (low|medium|high). Absent unless configured, which is the
            // behaviour the assistant had before this setting existed.
            ...(filled(config('ai-assistant.groq.reasoning_effort'))
                ? ['reasoning_effort' => (string) config('ai-assistant.groq.reasoning_effort')]
                : []),
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt($language)],
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

    private function systemPrompt(string $language = 'en'): string
    {
        if ($this->compact()) {
            return $this->compactPrompt($language);
        }

        return $this->languageRule($language) . 'You interpret a patient message for a medical appointment booking assistant. '
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
     * The shorter candidate prompt (GROQ_PROMPT=compact). Same rules, fewer
     * words. NOT the default: it is adopted only when
     * tests/ai-assistant/tools/prompt_compare.php shows it is as accurate and
     * as safe as the original in both languages.
     */
    private function compactPrompt(string $language): string
    {
        return 'You interpret one message from a patient of a medical practice, for an appointment '
            . 'assistant, and reply with the JSON schema only. '
            . 'The patient writes English or German; option labels may be either. Return "date" and '
            . '"time_preference" in English terms (tomorrow, day after tomorrow, Friday, next Monday, '
            . '13 August; morning, afternoon, evening). German: morgen=tomorrow, uebermorgen=day after '
            . 'tomorrow, Vormittag or morgens=morning, Nachmittag=afternoon, abends=evening, '
            . 'Uhr=o clock, ja=yes, nein=no. '
            . ($language === 'de' ? 'This patient is writing German. ' : '')
            . '"entity" is an option label copied exactly from the list, else null. "ordinal" is that '
            . 'same option number, else null; they must name the SAME option. Never invent a doctor, '
            . 'type, date, time or availability. You never book anything. '
            . 'Intents: request_slots = wants to SEE the available times; reject_booking = declines the '
            . 'appointment itself; change_doctor, change_appointment_type, change_date, change_slot = '
            . 'swapping only that part ("another time" keeps the date, "another doctor" keeps the type '
            . 'and time; two changes at once are allowed, put the day in "date"); '
            . 'select_slot_preference = names only a part of the day; view_appointments = any question '
            . 'about appointments they already have; book_appointment = wants a NEW one. '
            . 'CURRENT SELECTION is background only: never copy from it. "date" and "time" are the '
            . 'patient own words, else null. If they do not mind which option, return the select '
            . 'intent with entity and ordinal null. If unsure return intent "unknown" with low confidence.';
    }

    /**
     * The patient may write in English or German, and the labels may be in
     * either. Everything the model returns is expressed in English terms.
     */
    private function languageRule(string $language): string
    {
        $rule = 'The patient may write in English or German, and option labels may be in either language. '
            . 'Understand both, but ALWAYS return "date" and "time_preference" in English terms '
            . '("tomorrow", "day after tomorrow", "Friday", "next Monday", "13 August", "morning", '
            . '"afternoon", "evening") whatever language the patient used. German examples: '
            . '"morgen" is tomorrow, "übermorgen" is day after tomorrow, "Vormittag" and "morgens" are '
            . 'morning, "Nachmittag" is afternoon, "abends" is evening, "Montag" to "Sonntag" are '
            . 'Monday to Sunday, "nächste Woche" is next week, "um 14 Uhr" is time 14:00, '
            . '"ja" means yes and "nein" means no, "ein anderer Arzt" is change_doctor, '
            . '"meine Termine" is view_appointments, "Termin absagen" or "stornieren" is cancel_appointment, '
            . '"Termin buchen" or "ich möchte einen Termin" is book_appointment. ';

        if ($language === 'de') {
            $rule .= 'The patient is currently writing in German. ';
        }

        return $rule;
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

        // Outside the schema: not an intent we know. That is a broken reply,
        // not a message the AI could not read.
        if (!is_string($intent) || !in_array($intent, self::INTENTS, true)) {
            $this->outcome = self::OUTCOME_MALFORMED;

            return null;
        }

        // The model's own "I don't know".
        if ($intent === 'unknown') {
            $this->outcome = self::OUTCOME_UNRECOGNIZED;

            return null;
        }

        $confidence = $decoded['confidence'] ?? null;

        if (!is_numeric($confidence)) {
            $this->outcome = self::OUTCOME_MALFORMED;

            return null;
        }

        $confidence = (float) $confidence;
        $minimum = (float) config('ai-assistant.groq.min_confidence', 0.6);

        if ($confidence < $minimum) {
            $this->outcome = self::OUTCOME_UNRECOGNIZED;

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

        $this->outcome = self::OUTCOME_OK;

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
