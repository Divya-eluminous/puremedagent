<?php

namespace App\Services\AiAssistant;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Natural language understanding via Claude - the FALLBACK provider.
 *
 * Groq is the primary. NluManager asks this class only when Groq could not be
 * used, and only when NLU_FALLBACK=claude. It does the same job as
 * GroqNluService and is held to the same contract (NluContract): the same
 * prompt, the same JSON shape, the same validation, the same outcomes. It is an
 * INTERPRETATION LAYER ONLY: it cannot reach PureMed, the database or the
 * session, it never returns an identifier, and whatever it says is checked by
 * NluManager::guard() and acted on by the deterministic rules. Whether the
 * patient agreed to book or cancel is never its call - that is ConsentRules.
 *
 * Plain HTTP through Laravel's client, like the Groq service, so there is no
 * extra dependency to install on every machine.
 *
 * EXPLICIT BY DESIGN:
 *  - Timeout: claude.timeout seconds, then outcome "timeout".
 *  - Retries: none for any answer from Anthropic - a 429 or a 5xx is reported,
 *    never asked again. Only a dropped CONNECTION may be retried, and only if
 *    claude.connect_retries says so (default 0).
 *  - 429: the shared budget (ClaudeRateLimiter) is paused for Retry-After. The
 *    monthly SPEND CAP is told apart from a rate limit and stands the budget
 *    down for far longer.
 *  - A refusal, a reply cut short by max_tokens, text that is not JSON, or an
 *    intent outside the schema: "malformed" - a failure, not a message it
 *    could not read.
 *
 * WHAT IS LOGGED: the provider, the step, the outcome, the HTTP status, and
 * Anthropic's error type and code. Never the patient's message, the prompt, the
 * response body or the API key.
 *
 * WHAT IS SENT: see config/ai-assistant.php (the Fallback provider note).
 */
class ClaudeNluService implements NluProvider
{
    private string $outcome = NluOutcome::UNRECOGNIZED;

    /** Seconds the shared limiter said to wait, when the outcome is rate_limited. */
    private int $retryAfter = 0;

    /** Diagnostics from the last call: tokens, stop reason, latency. Never the message. */
    private array $meta = [];

    public function outcome(): string
    {
        return $this->outcome;
    }

    public function failed(): bool
    {
        return NluOutcome::isFailure($this->outcome);
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
     * Is Claude switched on AND able to be used? Everything here must hold, so
     * that turning the fallback off - or never giving it a key - guarantees no
     * request is made to Anthropic.
     */
    public function available(): bool
    {
        // Never contacted unless NLU_DRIVER or NLU_FALLBACK names it.
        return NluRoles::selected('claude')
            && (bool) config('ai-assistant.claude.enabled')
            && filled(config('ai-assistant.claude.api_key'))
            && filled(config('ai-assistant.claude.model'))
            && filled(config('ai-assistant.claude.endpoint'));
    }

    /**
     * @return array{intent: string, entity: ?string, ordinal: ?int, date: ?string,
     *               time_preference: ?string, time: ?string, confidence: float}|null
     */
    public function interpret(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = [], string $language = 'en'): ?array
    {
        $this->outcome = NluOutcome::UNRECOGNIZED;
        $this->retryAfter = 0;
        $this->meta = [];
        $language = NluContract::language($language);

        // The last line of defence for "off means off": even if a caller got
        // here by mistake, nothing is sent unless this provider is the primary or the fallback.
        if (!$this->available()) {
            $this->outcome = NluOutcome::UNAVAILABLE;

            return null;
        }

        // Defence in depth. Registration steps never leave this server.
        if (!in_array($step, NluContract::ELIGIBLE_STEPS, true)) {
            Log::warning('AI assistant NLU: refused ineligible step', ['provider' => 'claude', 'step' => $step]);

            return null;
        }

        $message = trim($message);

        if ($message === '') {
            return null;
        }

        $payload = $this->payload($step, $optionLabels, $message, $previousAssistantMessage, $context, $language);

        // Spend from Claude's own shared budget first. It is not Groq's: a pause
        // for Groq does not stop this, which is what a fallback is for.
        $limiter = app(ClaudeRateLimiter::class);
        $estimate = $this->estimateTokens($payload);
        $grant = $limiter->reserve($estimate);

        if (!$grant['allowed']) {
            $this->outcome = NluOutcome::RATE_LIMITED;
            $this->retryAfter = (int) $grant['retry_after'];
            $this->meta = ['limiter' => $grant['reason']];

            Log::info('AI assistant NLU: not sent, rate limit', [
                'provider' => 'claude',
                'step' => $step,
                'reason' => $grant['reason'],
                'retry_after' => $this->retryAfter,
            ]);

            return null;
        }

        $started = microtime(true);

        try {
            $request = Http::withHeaders([
                'x-api-key' => (string) config('ai-assistant.claude.api_key'),
                'anthropic-version' => (string) config('ai-assistant.claude.version', '2023-06-01'),
            ])
                ->timeout((int) config('ai-assistant.claude.timeout', 6))
                ->acceptJson()
                ->asJson();

            // Only a dropped connection may be tried again, and only if asked
            // for. Never a 429, a 5xx or any other answer from Anthropic.
            $retries = max(0, (int) config('ai-assistant.claude.connect_retries', 0));

            if ($retries > 0) {
                $request = $request->retry($retries + 1, 400, fn ($exception) => $exception instanceof ConnectionException, false);
            }

            $response = $request->post((string) config('ai-assistant.claude.endpoint'), $payload);
        } catch (Throwable $exception) {
            $this->outcome = $this->looksLikeTimeout($exception->getMessage())
                ? NluOutcome::TIMEOUT
                : NluOutcome::UNAVAILABLE;
            $this->meta = ['latency_ms' => (int) ((microtime(true) - $started) * 1000)];

            // The exception text is the transport's, not the message or the key.
            Log::warning('AI assistant NLU: request failed', [
                'provider' => 'claude',
                'step' => $step,
                'outcome' => $this->outcome,
                'error' => $this->scrub($exception->getMessage()),
            ]);

            return null;
        }

        $headers = $this->lowerHeaders($response);
        $body = $response->json();
        $usage = (int) data_get($body, 'usage.input_tokens', 0) + (int) data_get($body, 'usage.cache_creation_input_tokens', 0);
        $this->meta = [
            'latency_ms' => (int) ((microtime(true) - $started) * 1000),
            'status' => $response->status(),
            'input_tokens' => data_get($body, 'usage.input_tokens'),
            'output_tokens' => data_get($body, 'usage.output_tokens'),
            'stop_reason' => data_get($body, 'stop_reason'),
            'request_id' => $headers['request-id'] ?? null,
        ];

        // What Anthropic reports about the budget is read on every answer,
        // refusals included. Only uncached input counts towards ITPM.
        $limiter->settle($estimate, $usage > 0 ? $usage : null, $headers);

        if ($response->status() === 429) {
            return $this->rateLimited($response, $headers, $step, $limiter);
        }

        if (!$response->successful()) {
            $this->outcome = NluOutcome::UNAVAILABLE;

            // The error TYPE and CODE (invalid_request_error, authentication_error,
            // overloaded_error...), never the message, never the body.
            Log::warning('AI assistant NLU: non-success response', [
                'provider' => 'claude',
                'step' => $step,
                'status' => $response->status(),
                'error_type' => data_get($body, 'error.type'),
                'error_code' => data_get($body, 'error.details.error_code'),
            ]);

            return null;
        }

        $stop = data_get($body, 'stop_reason');

        // The model declined. A refusal comes back as a 200 and its output may
        // not match the schema, so it is never read as an answer.
        if ($stop === 'refusal') {
            $this->outcome = NluOutcome::MALFORMED;

            Log::warning('AI assistant NLU: provider refused', ['provider' => 'claude', 'step' => $step]);

            return null;
        }

        // Cut off by max_tokens: whatever is there is not a document to trust,
        // however much of it parses.
        if ($stop === 'max_tokens') {
            $this->outcome = NluOutcome::MALFORMED;

            Log::warning('AI assistant NLU: completion truncated', ['provider' => 'claude', 'step' => $step]);

            return null;
        }

        $text = null;

        foreach ((array) data_get($body, 'content', []) as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                $text = $block['text'];
                break;
            }
        }

        if ($text === null || trim($text) === '') {
            $this->outcome = NluOutcome::MALFORMED;

            Log::warning('AI assistant NLU: empty completion', ['provider' => 'claude', 'step' => $step]);

            return null;
        }

        $decoded = json_decode($text, true);

        if (!is_array($decoded)) {
            $this->outcome = NluOutcome::MALFORMED;

            Log::warning('AI assistant NLU: completion was not JSON', ['provider' => 'claude', 'step' => $step]);

            return null;
        }

        [$result, $this->outcome] = NluContract::validate(
            $decoded,
            $step,
            (float) config('ai-assistant.claude.min_confidence', 0.6),
            'claude'
        );

        return $result;
    }

    /**
     * HTTP 429: pause Claude's own budget, for everyone, and say so.
     *
     * A rate limit says how long to wait (Retry-After). The monthly SPEND CAP
     * does not: it lasts until the month turns over, and asking again meanwhile
     * only collects more refusals, so the budget is stood down for much longer
     * and the log says plainly why.
     */
    private function rateLimited($response, array $headers, string $step, ClaudeRateLimiter $limiter): ?array
    {
        $this->outcome = NluOutcome::RATE_LIMITED;
        $code = data_get($response->json(), 'error.details.error_code');
        $spendCap = $code === 'enforced_spend_limit_reached';

        if ($spendCap) {
            $seconds = max(60, (int) config('ai-assistant.claude.rate_limit.spend_cap_backoff_seconds', 3600));
            $this->retryAfter = $limiter->trip($seconds, $seconds);

            Log::error('AI assistant NLU: Anthropic monthly spend cap reached - the Claude fallback is paused', [
                'provider' => 'claude',
                'step' => $step,
                'paused_seconds' => $this->retryAfter,
            ]);

            return null;
        }

        $this->retryAfter = $limiter->trip(ProviderRateLimiter::parseRetryAfter($headers['retry-after'] ?? null));

        Log::warning('AI assistant NLU: rate limited by Anthropic', [
            'provider' => 'claude',
            'step' => $step,
            'retry_after' => $this->retryAfter,
        ]);

        return null;
    }

    /** @return array<string, mixed> */
    private function payload(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage, array $context, string $language): array
    {
        $payload = [
            'model' => (string) config('ai-assistant.claude.model'),
            'max_tokens' => max(100, (int) config('ai-assistant.claude.max_tokens', 400)),
            // NO temperature, top_p or top_k: the current Haiku rejects any value
            // but its default. The output is held in line by the schema, the
            // guard and the rules instead.
            'system' => NluContract::systemPrompt($language),
            'messages' => [
                ['role' => 'user', 'content' => NluContract::userPrompt($step, $optionLabels, $message, $previousAssistantMessage, $context)],
            ],
            'output_config' => ['format' => ['type' => 'json_schema', 'schema' => $this->schema()]],
        ];

        $thinking = (string) config('ai-assistant.claude.thinking', 'disabled');

        if ($thinking === 'disabled' || $thinking === 'adaptive') {
            $payload['thinking'] = ['type' => $thinking];
        }

        $effort = (string) config('ai-assistant.claude.effort', 'low');

        if ($effort !== '') {
            $payload['output_config']['effort'] = $effort;
        }

        return $payload;
    }

    /**
     * The same fields as Groq's, in the subset of JSON Schema that Anthropic's
     * structured outputs accept: no minimum/maximum (so confidence is described
     * rather than bounded - NluContract::validate() enforces the threshold),
     * nullable fields as anyOf, and additionalProperties false on the object.
     *
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        $nullable = fn (array $type, string $description): array => [
            'anyOf' => [$type, ['type' => 'null']],
            'description' => $description,
        ];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['intent', 'entity', 'ordinal', 'date', 'time_preference', 'time', 'confidence'],
            'properties' => [
                'intent' => [
                    'type' => 'string',
                    'enum' => NluContract::INTENTS,
                    'description' => 'What the patient wants to do.',
                ],
                'entity' => $nullable(['type' => 'string'], 'The option label the patient named, copied from the list. Null if they did not name one.'),
                'ordinal' => $nullable(['type' => 'integer'], 'The 1-based position the patient referred to, e.g. "the second one". Null otherwise.'),
                'date' => $nullable(['type' => 'string'], 'A day the patient mentioned, in English terms, e.g. "tomorrow" or "13 August". Null otherwise.'),
                'time_preference' => $nullable(['type' => 'string', 'enum' => NluContract::TIME_PREFERENCES], 'Part of the day the patient asked for. Null otherwise.'),
                'time' => $nullable(['type' => 'string'], 'A clock time the patient mentioned, as HH:MM. Null otherwise.'),
                'confidence' => [
                    'type' => 'number',
                    'description' => 'How certain the interpretation is, from 0 (not at all) to 1 (completely).',
                ],
            ],
        ];
    }

    /** A rough cost for the reservation: the size of what is sent (input tokens - the ITPM budget). */
    private function estimateTokens(array $payload): int
    {
        $chars = mb_strlen((string) ($payload['system'] ?? ''))
            + mb_strlen((string) json_encode($payload['messages'] ?? []))
            + mb_strlen((string) json_encode($payload['output_config'] ?? []));

        return max(
            (int) ceil($chars / 3.0),
            (int) config('ai-assistant.claude.rate_limit.estimated_prompt_tokens', 1500)
        );
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

    /** Belt and braces: the key must never reach a log line, whatever the transport said. */
    private function scrub(string $text): string
    {
        $key = (string) config('ai-assistant.claude.api_key');

        if ($key !== '') {
            $text = str_replace($key, '[redacted]', $text);
        }

        return mb_substr(preg_replace('/sk-ant-[A-Za-z0-9_\-]+/', '[redacted]', $text) ?? '', 0, 200);
    }
}
