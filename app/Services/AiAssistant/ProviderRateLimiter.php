<?php

namespace App\Services\AiAssistant;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One shared budget, per AI provider, for every call the assistant makes.
 *
 * A provider's limits belong to the API key, not to a patient: they are spent
 * by every session at once. A per-session cooldown would let ten patients each
 * stay "within limits" and together exhaust the key, so the budget lives in the
 * application cache and every call reserves against it first.
 *
 * EACH PROVIDER HAS ITS OWN. Groq and Claude are limited differently, by
 * different numbers, reset on different clocks and report it in different
 * headers. A pause because Groq said 429 must not stop Claude from answering -
 * that is the whole point of having a fallback - so the two never share a key,
 * a counter or a lock. Subclasses supply the name and the header format.
 *
 * Three safeguards, in the order they are checked:
 *  1. A "blocked until" time, set when the provider answers 429. Retry-After is
 *     respected; nothing is sent until it has passed.
 *  2. What the provider itself last reported (remaining tokens and when they
 *     reset). The configured limits are only a starting point; the response
 *     headers are the truth and replace them.
 *  3. A local reservation of the tokens a call is expected to cost, so several
 *     requests arriving in the same second cannot all be sent on the strength
 *     of the same remaining balance. Reservations are made under a lock, and
 *     corrected to the real usage once the response arrives.
 *
 * FAIL CLOSED. If the cache cannot be read or the lock cannot be taken, the
 * call is refused rather than assumed to be free. The assistant then runs on
 * its rules, which is always safe; spending a provider's budget blind is not.
 *
 * The default store is the application's cache. With the file store that is
 * correct for ONE application server. Several servers each have their own
 * files and would each spend the whole budget: they need a shared store
 * (Redis, Memcached or the database) - set the provider's cache_store setting.
 */
abstract class ProviderRateLimiter
{
    /** 'groq' or 'claude': names the cache keys and the config block. */
    abstract protected function provider(): string;

    /**
     * Read this provider's rate-limit headers.
     *
     * @param  array<string, string>  $headers  lower-case header name => value
     * @return array{limit: ?int, remaining: ?int, reset_in: ?float, day_limit: ?int}
     */
    abstract protected function parseHeaders(array $headers, int $now): array;

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
            $blocked = (int) $this->cache()->get($this->blockKey(), 0);
        } catch (Throwable $e) {
            return $this->failClosed($e);
        }

        if ($blocked > $now) {
            return $this->deny($blocked - $now, 'blocked');
        }

        try {
            $lock = $this->cache()->lock($this->lockKey(), 5);
            $lock->block(2);
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
     * rate-limit headers the provider sent back.
     *
     * @param  array<string, string>  $headers  lower-case header name => value
     */
    public function settle(int $reserved, ?int $actualTokens, array $headers): void
    {
        if (!$this->enabled()) {
            return;
        }

        try {
            $lock = $this->cache()->lock($this->lockKey(), 5);
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

            $seen = $this->parseHeaders($headers, $now);

            if ($seen['limit'] !== null) {
                $state['header_limit_tokens'] = $seen['limit'];
            }

            if ($seen['remaining'] !== null) {
                $state['header_remaining_tokens'] = $seen['remaining'];
                $state['header_reset_at'] = $now + (int) ceil($seen['reset_in'] ?? 60);
                // A fresh balance: only what is reserved after it counts against it.
                $state['reserved_since_header'] = 0;
            }

            if ($seen['day_limit'] !== null) {
                $state['header_limit_requests'] = $seen['day_limit'];
            }

            if (($seen['limit'] !== null || $seen['day_limit'] !== null) && empty($state['verified'])) {
                $state['verified'] = true;
                // Once, so the log shows what the provider actually allows this key.
                Log::info('AI assistant: ' . $this->provider() . ' rate limits from response headers', [
                    'provider' => $this->provider(),
                    'tokens_per_minute' => $seen['limit'],
                    'requests_per_day' => $seen['day_limit'],
                    'configured_tokens_per_minute' => (int) $this->config('tokens_per_minute'),
                    'configured_requests_per_day' => (int) $this->config('requests_per_day'),
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
     * The provider said no. Nothing is sent to it for as long as it asked,
     * shared by every session. A missing or absurd Retry-After is replaced by a
     * sane value.
     *
     * @param  int|null  $ceiling  a longer limit than max_backoff_seconds, for the
     *                             one case that is not a matter of seconds: a
     *                             spend cap that lasts until the month turns over
     */
    public function trip(?int $retryAfterSeconds, ?int $ceiling = null): int
    {
        $max = max(1, $ceiling ?? (int) $this->config('max_backoff_seconds', 300));
        $default = max(1, (int) $this->config('default_backoff_seconds', 30));
        $seconds = ($retryAfterSeconds !== null && $retryAfterSeconds > 0) ? min($retryAfterSeconds, $max) : min($default, $max);

        try {
            $this->cache()->put($this->blockKey(), time() + $seconds, $seconds + 5);
        } catch (Throwable $e) {
            Log::warning('AI assistant: could not record a ' . $this->provider() . ' rate-limit pause', ['error' => $e->getMessage()]);
        }

        return $seconds;
    }

    /** What the limiter currently believes - for diagnostics and the tests. */
    public function snapshot(): array
    {
        try {
            return $this->load() + ['blocked_until' => (int) $this->cache()->get($this->blockKey(), 0)];
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /** Forget everything. Used by the tests. */
    public function reset(): void
    {
        try {
            $this->cache()->forget($this->stateKey());
            $this->cache()->forget($this->blockKey());
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

        $dayLimit = $this->lower((int) $this->config('requests_per_day', 0), $state['header_limit_requests'] ?? null);

        if ($dayLimit > 0 && (int) $state['day_count'] >= $dayLimit) {
            $tomorrow = strtotime('tomorrow', $now) - $now;
            $this->save($state);

            return $this->deny(min($tomorrow, (int) $this->config('max_backoff_seconds', 300)), 'daily_limit');
        }

        $tokenLimit = $this->lower((int) $this->config('tokens_per_minute', 0), $state['header_limit_tokens'] ?? null);
        $budget = $tokenLimit > 0 ? (int) floor($tokenLimit * (1 - max(0, min(0.9, (float) $this->config('safety_margin', 0.1))))) : PHP_INT_MAX;

        // What the provider last said it had left, while that statement is still true.
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
        $state = $this->cache()->get($this->stateKey());

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
        $this->cache()->put($this->stateKey(), $state, 90000);
    }

    private function stateKey(): string { return 'ai_assistant.' . $this->provider() . '.budget'; }

    private function blockKey(): string { return 'ai_assistant.' . $this->provider() . '.blocked_until'; }

    private function lockKey(): string { return 'ai_assistant.' . $this->provider() . '.budget.lock'; }

    protected function config(string $key, mixed $default = null): mixed
    {
        return config('ai-assistant.' . $this->provider() . '.rate_limit.' . $key, $default);
    }

    private function cache(): Repository
    {
        $store = $this->config('cache_store');

        return $store ? Cache::store($store) : Cache::store();
    }

    private function enabled(): bool
    {
        return (bool) $this->config('enabled', true);
    }

    private function lower(int $configured, mixed $fromHeader): int
    {
        $header = is_numeric($fromHeader) ? (int) $fromHeader : 0;

        if ($configured > 0 && $header > 0) {
            return min($configured, $header);
        }

        return $configured > 0 ? $configured : $header;
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
        Log::warning('AI assistant: ' . $this->provider() . ' rate limiter unavailable, refusing the call', [
            'provider' => $this->provider(),
            'error' => $e->getMessage(),
        ]);

        return $this->deny(10, 'limiter_unavailable');
    }
}
