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
class GroqNluService implements NluProvider
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
    private const ELIGIBLE_STEPS = NluContract::ELIGIBLE_STEPS;

    private const INTENTS = NluContract::INTENTS;

    private const TIME_PREFERENCES = NluContract::TIME_PREFERENCES;

    /** Keep prompts small: cost control, and a long message is not a selection. */
    private const MAX_MESSAGE_CHARS = NluContract::MAX_MESSAGE_CHARS;
    private const MAX_OPTIONS = NluContract::MAX_OPTIONS;
    private const MAX_OPTION_CHARS = NluContract::MAX_OPTION_CHARS;

    private const LANGUAGES = NluContract::LANGUAGES;

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
    public const OUTCOME_OK = NluOutcome::OK;
    public const OUTCOME_UNRECOGNIZED = NluOutcome::UNRECOGNIZED;
    public const OUTCOME_TIMEOUT = NluOutcome::TIMEOUT;
    public const OUTCOME_MALFORMED = NluOutcome::MALFORMED;
    public const OUTCOME_RATE_LIMITED = NluOutcome::RATE_LIMITED;
    public const OUTCOME_UNAVAILABLE = NluOutcome::UNAVAILABLE;

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

        // The model declined to answer (a `refusal` where the content would be).
        // That is a broken contract, not a message it could not read, so it is
        // a failure the fallback provider may be asked about.
        if (filled(data_get($response->json(), 'choices.0.message.refusal'))) {
            $this->outcome = self::OUTCOME_MALFORMED;

            Log::warning('AI assistant NLU: provider refused', ['provider' => 'groq', 'step' => $step]);

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
    public function available(): bool
    {
        return $this->enabled();
    }

    private function enabled(): bool
    {
        // Never contacted unless NLU_DRIVER or NLU_FALLBACK names it.
        return NluRoles::selected('groq')
            && (bool) config('ai-assistant.groq.enabled')
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

        return NluContract::systemPrompt($language);
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
     * Only the step name, the option labels the patient can already see, and
     * their message. No identifiers, no tokens, no patient details.
     *
     * @param  array<int, string>  $optionLabels
     */
    private function userPrompt(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = []): string
    {
        return NluContract::userPrompt($step, $optionLabels, $message, $previousAssistantMessage, $context);
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
     * the assistant falls back to deterministic matching (or, when it is
     * switched on, to the fallback provider - for a broken reply, never for one
     * that was merely not useful).
     *
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>|null
     */
    private function validate(array $decoded, string $step): ?array
    {
        [$result, $this->outcome] = NluContract::validate(
            $decoded,
            $step,
            (float) config('ai-assistant.groq.min_confidence', 0.6),
            'groq'
        );

        return $result;
    }
}
