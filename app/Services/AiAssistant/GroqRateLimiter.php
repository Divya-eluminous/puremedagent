<?php

namespace App\Services\AiAssistant;

/**
 * Groq's own shared budget. See ProviderRateLimiter for how it works.
 *
 * Groq reports a single token limit in x-ratelimit-*-tokens headers, with the
 * reset as a duration ("27.262s", "1m3.5s"), and a daily request limit.
 *
 * The cache keys are ai_assistant.groq.* - unchanged from when this was the
 * only limiter, so nothing already held is lost.
 */
class GroqRateLimiter extends ProviderRateLimiter
{
    protected function provider(): string
    {
        return 'groq';
    }

    protected function parseHeaders(array $headers, int $now): array
    {
        return [
            'limit' => $this->intHeader($headers, 'x-ratelimit-limit-tokens'),
            'remaining' => $this->intHeader($headers, 'x-ratelimit-remaining-tokens'),
            'reset_in' => self::parseDuration($headers['x-ratelimit-reset-tokens'] ?? null),
            'day_limit' => $this->intHeader($headers, 'x-ratelimit-limit-requests'),
        ];
    }

    /** @param array<string, string> $headers */
    private function intHeader(array $headers, string $name): ?int
    {
        $value = $headers[$name] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}
