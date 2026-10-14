<?php

namespace App\Services\AiAssistant;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One shared budget for every call the assistant makes to Groq.
 *
 * Groq's limits belong to the API key, not to a patient: eight thousand tokens
 * a minute and a daily request count are spent by every session at once. A
 * per-session cooldown would let ten patients each stay "within limits" and
 * together exhaust the key, so the budget lives in the application cache and
 * every call reserves against it first.
 *
 * Three safeguards, in the order they are checked:
 *  1. A "blocked until" time, set when Groq answers 429. Retry-After is
 *     respected; nothing is sent until it has passed.
 *  2. What Groq itself last reported (remaining tokens and when they reset).
 *     The configured limits are only a starting point; the response headers
 *     are the truth and replace them.
 *  3. A local reservation of the tokens a call is expected to cost, so several
 *     requests arriving in the same second cannot all be sent on the strength
 *     of the same remaining balance. Reservations are made under a lock, and
 *     corrected to the real usage once the response arrives.
 *
 * FAIL CLOSED. If the cache cannot be read or the lock cannot be taken, the
 * call is refused rather than assumed to be free. The assistant then runs on
 * its rules, which is always safe; spending Groq's budget blind is not.
 *
 * The default store is the application's cache. With the file store that is
 * correct for ONE application server. Several servers each have their own
 * files and would each spend the whole budget: they need a shared store
 * (Redis, Memcached or the database) - set GROQ_RATE_CACHE_STORE to one.
 */
class GroqRateLimiter
{
    private const STATE_KEY = 'ai_assistant.groq.budget';
    private const BLOCK_KEY = 'ai_assistant.groq.blocked_until';
    private const LOCK_KEY = 'ai_assistant.groq.budget.lock';

    /**
     * Take part of the budget for one call.
     *
     * @return array{allowed: bool, retry_after: int, reason: string}
     */
    public function reserve(int $estimatedTokens): array
    {
        if (!$this->enabled()) {
            return $this->allow();
        }

        $now = time();

        try {
            $blocked = (int) $this->cache()->get(self::BLOCK_KEY, 0);
        } catch (Throwable $e) {
            return $this->failClosed($e);
        }

        if ($blocked > $now) {
            return $this->deny($blocked - $now, 'blocked');
        }

        try {
            $lock = $this->cache()->lock(self::LOCK_KEY, 5);
            $held = $lock->block(2);
        } catch (Throwable $e) {
            return $this->failClosed($e);
        }

        try {
            return $this->reserveLocked($estimatedTokens, $now);
        } catch (Throwable $e) {
            return $this->failClosed($e);
        } finally {
            try {
                $lock->release();
            } catch (Throwable $e) {
                // The lock expires by itself after five seconds.
            }
        }
    }

    /**
     * Correct the reservation to what was really spent, and learn from the
     * rate-limit headers Groq sent back.
     *
     * @param  array<string, string>  $headers  lower-case header name => value
     */
    public function settle(int $reserved, ?int $actualTokens, array $headers): void
    {
        if (!$this->enabled()) {
            return;
        }

        try {
            $lock = $this->cache()->lock(self::LOCK_KEY, 5);
            $lock->block(2);
        } catch (Throwable $e) {
            return;   // the reservation already counted; erring high is safe
        }

        try {
            $state = $this->load();
            $now = time();

            if ($actualTokens !== null) {
                $state['reserved'] = max(0, (int) $state['reserved'] - $reserved + $actualTokens);
            }

            $limit = $this->intHeader($headers, 'x-ratelimit-limit-tokens');
            $remaining = $this->intHeader($headers, 'x-ratelimit-remaining-tokens');
            $reset = $this->durationHeader($headers, 'x-ratelimit-reset-tokens');
            $dayLimit = $this->intHeader($headers, 'x-ratelimit-limit-requests');

            if ($limit !== null) {
                $state['header_limit_tokens'] = $limit;
            }

            if ($remaining !== null) {
                $state['header_remaining_tokens'] = $remaining;
                $state['header_reset_at'] = $now + (int) ceil($reset ?? 60);
                // A fresh balance: only what is reserved after it counts against it.
                $state['reserved_since_header'] = 0;
            }

            if ($dayLimit !== null) {
                $state['header_limit_requests'] = $dayLimit;
            }

            if (($limit !== null || $dayLimit !== null) && empty($state['verified'])) {
                $state['verified'] = true;
                // Once, so the log shows what Groq actually allows this key.
                Log::info('AI assistant: Groq rate limits from response headers', [
                    'tokens_per_minute' => $limit,
                    'requests_per_day' => $dayLimit,
                    'configured_tokens_per_minute' => (int) config('ai-assistant.groq.rate_limit.tokens_per_minute'),
                    'configured_requests_per_day' => (int) config('ai-assistant.groq.rate_limit.requests_per_day'),
                ]);
            }

            $this->save($state);
        } catch (Throwable $e) {
            // Best effort only.
        } finally {
            try {
                $lock->release();
            } catch (Throwable $e) {
            }
        }
    }

    /**
     * Groq said no. Nothing is sent for as long as it asked, shared by every
     * session. A missing or absurd Retry-After is replaced by a sane value.
     */
    public function trip(?int $retryAfterSeconds): int
    {
        $max = max(1, (int) config('ai-assistant.groq.rate_limit.max_backoff_seconds', 300));
        $default = max(1, (int) config('ai-assistant.groq.rate_limit.default_backoff_seconds', 30));
        $seconds = ($retryAfterSeconds !== null && $retryAfterSeconds > 0) ? min($retryAfterSeconds, $max) : $default;

        try {
            $this->cache()->put(self::BLOCK_KEY, time() + $seconds, $seconds + 5);
        } catch (Throwable $e) {
            Log::warning('AI assistant: could not record a Groq rate-limit pause', ['error' => $e->getMessage()]);
        }

        return $seconds;
    }

    /** What the limiter currently believes - for diagnostics and the tests. */
    public function snapshot(): array
    {
        try {
            return $this->load() + ['blocked_until' => (int) $this->cache()->get(self::BLOCK_KEY, 0)];
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /** Forget everything. Used by the tests. */
    public function reset(): void
    {
        try {
            $this->cache()->forget(self::STATE_KEY);
            $this->cache()->forget(self::BLOCK_KEY);
        } catch (Throwable $e) {
        }
    }

    /**
     * Seconds Retry-After asks for. It may be a number of seconds or an HTTP
     * date; anything else is ignored.
     */
    public static function parseRetryAfter(?string $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return max(0, (int) ceil((float) $value));
        }

        $at = strtotime($value);

        return $at === false ? null : max(0, $at - time());
    }

    /**
     * "27.262s", "1m3.5s", "15m50.4s", "250ms", "1h2m3s" as seconds.
     */
    public static function parseDuration(?string $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('/^(?:(\d+(?:\.\d+)?)h)?(?:(\d+(?:\.\d+)?)m(?!s))?(?:(\d+(?:\.\d+)?)s)?(?:(\d+(?:\.\d+)?)ms)?$/', $value, $m) !== 1) {
            return is_numeric($value) ? (float) $value : null;
        }

        $seconds = ((float) ($m[1] ?? 0)) * 3600 + ((float) ($m[2] ?? 0)) * 60 + (float) ($m[3] ?? 0) + ((float) ($m[4] ?? 0)) / 1000;

        return $seconds > 0 ? $seconds : null;
    }

    /* -----------------------------------------------------------------
    |  Internals
    ------------------------------------------------------------------*/

    /** @return array{allowed: bool, retry_after: int, reason: string} */
    private function reserveLocked(int $estimate, int $now): array
    {
        $state = $this->load();
        $cfg = (array) config('ai-assistant.groq.rate_limit', []);

        // A new minute, a new token window.
        if ($now - (int) $state['window_start'] >= 60) {
            $state['window_start'] = $now;
            $state['reserved'] = 0;
        }

        // A new day, a new request count.
        if (($state['day'] ?? '') !== date('Y-m-d', $now)) {
            $state['day'] = date('Y-m-d', $now);
            $state['day_count'] = 0;
        }

        $dayLimit = $this->lower((int) ($cfg['requests_per_day'] ?? 0), $state['header_limit_requests'] ?? null);

        if ($dayLimit > 0 && (int) $state['day_count'] >= $dayLimit) {
            $tomorrow = strtotime('tomorrow', $now) - $now;
            $this->save($state);

            return $this->deny(min($tomorrow, (int) ($cfg['max_backoff_seconds'] ?? 300)), 'daily_limit');
        }

        $tokenLimit = $this->lower((int) ($cfg['tokens_per_minute'] ?? 0), $state['header_limit_tokens'] ?? null);
        $budget = $tokenLimit > 0 ? (int) floor($tokenLimit * (1 - max(0, min(0.9, (float) ($cfg['safety_margin'] ?? 0.1))))) : PHP_INT_MAX;

        // What Groq last said it had left, while that statement is still true.
        if (isset($state['header_remaining_tokens'], $state['header_reset_at'])
            && $now < (int) $state['header_reset_at']
            && (int) $state['header_remaining_tokens'] - (int) $state['reserved_since_header'] < $estimate) {
            $this->save($state);

            return $this->deny((int) $state['header_reset_at'] - $now, 'tokens_exhausted');
        }

        if ((int) $state['reserved'] + $estimate > $budget) {
            $this->save($state);

            return $this->deny(max(1, (int) $state['window_start'] + 60 - $now), 'token_budget');
        }

        $state['reserved'] = (int) $state['reserved'] + $estimate;
        $state['reserved_since_header'] = (int) ($state['reserved_since_header'] ?? 0) + $estimate;
        $state['day_count'] = (int) $state['day_count'] + 1;
        $this->save($state);

        return $this->allow();
    }

    /** @return array<string, mixed> */
    private function load(): array
    {
        $state = $this->cache()->get(self::STATE_KEY);

        return array_merge([
            'window_start' => 0,
            'reserved' => 0,
            'reserved_since_header' => 0,
            'day' => '',
            'day_count' => 0,
        ], is_array($state) ? $state : []);
    }

    private function save(array $state): void
    {
        // A header balance is a snapshot: reservations made since it was taken
        // are subtracted from it, and a fresh snapshot starts that count again.
        $this->cache()->put(self::STATE_KEY, $state, 90000);
    }

    private function cache(): Repository
    {
        $store = config('ai-assistant.groq.rate_limit.cache_store');

        return $store ? Cache::store($store) : Cache::store();
    }

    private function enabled(): bool
    {
        return (bool) config('ai-assistant.groq.rate_limit.enabled', true);
    }

    private function lower(int $configured, mixed $fromHeader): int
    {
        $header = is_numeric($fromHeader) ? (int) $fromHeader : 0;

        if ($configured > 0 && $header > 0) {
            return min($configured, $header);
        }

        return $configured > 0 ? $configured : $header;
    }

    /** @param array<string, string> $headers */
    private function intHeader(array $headers, string $name): ?int
    {
        $value = $headers[$name] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /** @param array<string, string> $headers */
    private function durationHeader(array $headers, string $name): ?float
    {
        return self::parseDuration($headers[$name] ?? null);
    }

    /** @return array{allowed: bool, retry_after: int, reason: string} */
    private function allow(): array
    {
        return ['allowed' => true, 'retry_after' => 0, 'reason' => ''];
    }

    /** @return array{allowed: bool, retry_after: int, reason: string} */
    private function deny(int $retryAfter, string $reason): array
    {
        return ['allowed' => false, 'retry_after' => max(1, $retryAfter), 'reason' => $reason];
    }

    /**
     * The shared state cannot be read or locked. Treat the budget as spent.
     *
     * @return array{allowed: bool, retry_after: int, reason: string}
     */
    private function failClosed(Throwable $e): array
    {
        Log::warning('AI assistant: Groq rate limiter unavailable, refusing the call', ['error' => $e->getMessage()]);

        return $this->deny(10, 'limiter_unavailable');
    }
}
