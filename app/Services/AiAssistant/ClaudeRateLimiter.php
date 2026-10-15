<?php

namespace App\Services\AiAssistant;

/**
 * Claude's own shared budget, entirely separate from Groq's.
 * See ProviderRateLimiter for how it works.
 *
 * Anthropic limits requests, INPUT tokens and OUTPUT tokens per minute
 * separately. This assistant's calls are short classifications whose input is
 * many times their output, so the budget tracks input tokens (ITPM): the
 * estimate reserved for a call is its prompt size, and the headers read are the
 * input-token ones. Resets arrive as RFC 3339 timestamps, not durations.
 *
 * The cache keys are ai_assistant.claude.*, so a pause for Groq never blocks
 * Claude and a pause for Claude never blocks Groq.
 */
class ClaudeRateLimiter extends ProviderRateLimiter
{
    protected function provider(): string
    {
        return 'claude';
    }

    protected function parseHeaders(array $headers, int $now): array
    {
        $limit = $this->intHeader($headers, 'anthropic-ratelimit-input-tokens-limit');
        $remaining = $this->intHeader($headers, 'anthropic-ratelimit-input-tokens-remaining');
        $resetIn = $this->secondsUntil($headers['anthropic-ratelimit-input-tokens-reset'] ?? null, $now);

        // Out of REQUESTS is just as final as out of tokens, and arrives under
        // its own headers. Treated as no tokens left until that reset.
        $requestsLeft = $this->intHeader($headers, 'anthropic-ratelimit-requests-remaining');

        if ($requestsLeft !== null && $requestsLeft <= 0) {
            $remaining = 0;
            $resetIn = $this->secondsUntil($headers['anthropic-ratelimit-requests-reset'] ?? null, $now) ?? $resetIn;
        }

        return ['limit' => $limit, 'remaining' => $remaining, 'reset_in' => $resetIn, 'day_limit' => null];
    }

    /** @param array<string, string> $headers */
    private function intHeader(array $headers, string $name): ?int
    {
        $value = $headers[$name] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /** Seconds from now to an RFC 3339 time, or null if it is not one. */
    private function secondsUntil(?string $timestamp, int $now): ?float
    {
        $timestamp = trim((string) $timestamp);

        if ($timestamp === '') {
            return null;
        }

        $at = strtotime($timestamp);

        return $at === false ? null : (float) max(0, $at - $now);
    }
}
