<?php

namespace App\Services\AiAssistant;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Limits on verification codes that no chat session can reset.
 *
 * A four digit code is only as strong as the number of guesses allowed, and an
 * SMS costs money and lands on a real person's phone. Both limits therefore
 * belong to the MOBILE NUMBER, held in the shared cache - not to the chat
 * session, where starting again, opening another tab or clearing cookies would
 * hand the counters back at zero.
 *
 *   - At most `otp_max_sends_per_hour` codes per number in any rolling hour.
 *   - At most `otp_max_attempts` wrong codes, then the number is locked for
 *     `otp_lockout_minutes`. While locked it can neither be sent a code nor
 *     verified, so a resend or a lucky correct guess cannot get around it.
 *
 * The key is the number with everything but its digits removed and leading
 * zeros dropped, so "0664 123 45", "664-12345" and "66412345" are one number.
 * It is NOT keyed on date of birth: guessing birth dates must not mint a fresh
 * allowance for the same phone.
 *
 * CONCURRENCY. Every read-then-update happens under a cache lock, so two
 * requests arriving together cannot both be told "there is room". A wrong
 * attempt is counted BEFORE the code is compared (beginAttempt) rather than
 * after: a hundred simultaneous guesses are then each counted as they arrive
 * and the sixth onwards is refused without being checked at all.
 *
 * FAIL CLOSED. If the cache cannot be read or the lock cannot be taken, the
 * request is refused. A limit that disappears when the cache is down is not a
 * limit.
 *
 * With the file cache store this is correct for ONE application server. Several
 * servers each have their own files and each would allow the full number of
 * guesses: they need a shared store (Redis, Memcached or the database) - set
 * OTP_GUARD_CACHE_STORE to one.
 */
class OtpGuard
{
    private const PREFIX = 'ai_assistant.otp.guard.';

    /** How long a record is kept with no activity, in seconds. */
    private const KEEP = 14400;

    /** A wrong attempt is forgotten after this long without another. */
    private const FAILURE_MEMORY = 3600;

    /**
     * The same number however it is typed.
     */
    public static function identity(string $mobile): string
    {
        $digits = ltrim((string) preg_replace('/\D+/', '', $mobile), '0');

        return hash('sha256', $digits === '' ? 'none' : $digits);
    }

    /**
     * Take one of the hourly sends, if there is one left.
     *
     * @return array{allowed: bool, retry_after: int, reason: string, token: int}
     */
    public function reserveSend(string $mobile): array
    {
        return $this->locked($mobile, function (array &$r, int $now) {
            if ((int) $r['locked_until'] > $now) {
                return $this->deny((int) $r['locked_until'] - $now, 'locked');
            }

            $window = 3600;
            $r['sends'] = array_values(array_filter($r['sends'], fn ($t) => $t > ($now - $window) * 1000000));
            $max = max(1, (int) config('ai-assistant.otp_max_sends_per_hour', 3));

            if (count($r['sends']) >= $max) {
                $oldest = (int) floor($r['sends'][0] / 1000000);

                return $this->deny(max(1, $oldest + $window - $now), 'send_limit');
            }

            $token = (int) (microtime(true) * 1000000);
            $r['sends'][] = $token;

            return ['allowed' => true, 'retry_after' => 0, 'reason' => '', 'token' => $token];
        }, $this->denyUnavailable());
    }

    /**
     * Give back a send that never went out, because nothing was delivered.
     */
    public function releaseSend(string $mobile, int $token): void
    {
        $this->locked($mobile, function (array &$r) use ($token) {
            $r['sends'] = array_values(array_filter($r['sends'], fn ($t) => $t !== $token));

            return true;
        }, false);
    }

    /**
     * About to compare a code: count the attempt first.
     *
     * @return array{allowed: bool, locked: bool, retry_after: int, attempts_left: int, unavailable: bool}
     */
    public function beginAttempt(string $mobile): array
    {
        return $this->locked($mobile, function (array &$r, int $now) {
            if ((int) $r['locked_until'] > $now) {
                return ['allowed' => false, 'locked' => true, 'retry_after' => (int) $r['locked_until'] - $now, 'attempts_left' => 0, 'unavailable' => false];
            }

            // Old wrong attempts fade, so a patient is not held to a mistake
            // made yesterday.
            if ($r['failures'] > 0 && $now - (int) $r['failures_at'] > self::FAILURE_MEMORY) {
                $r['failures'] = 0;
            }

            $max = max(1, (int) config('ai-assistant.otp_max_attempts', 5));

            if ((int) $r['failures'] >= $max) {
                $r['locked_until'] = $now + $this->lockoutSeconds();
                $r['failures'] = 0;

                return ['allowed' => false, 'locked' => true, 'retry_after' => $this->lockoutSeconds(), 'attempts_left' => 0, 'unavailable' => false];
            }

            // Counted now. A correct code clears it again in finishAttempt().
            $r['failures'] = (int) $r['failures'] + 1;
            $r['failures_at'] = $now;

            return ['allowed' => true, 'locked' => false, 'retry_after' => 0, 'attempts_left' => $max - (int) $r['failures'], 'unavailable' => false];
        }, ['allowed' => false, 'locked' => false, 'retry_after' => 10, 'attempts_left' => 0, 'unavailable' => true]);
    }

    /**
     * The attempt has been compared. A right code clears the count; a wrong
     * one that was the last allowed starts the lockout.
     *
     * @return array{locked: bool, retry_after: int, attempts_left: int}
     */
    public function finishAttempt(string $mobile, bool $correct): array
    {
        return $this->locked($mobile, function (array &$r, int $now) use ($correct) {
            $max = max(1, (int) config('ai-assistant.otp_max_attempts', 5));

            if ($correct) {
                $r['failures'] = 0;
                $r['locked_until'] = 0;

                return ['locked' => false, 'retry_after' => 0, 'attempts_left' => $max];
            }

            if ((int) $r['failures'] >= $max) {
                $r['locked_until'] = $now + $this->lockoutSeconds();
                $r['failures'] = 0;

                return ['locked' => true, 'retry_after' => $this->lockoutSeconds(), 'attempts_left' => 0];
            }

            return ['locked' => false, 'retry_after' => 0, 'attempts_left' => max(0, $max - (int) $r['failures'])];
        }, ['locked' => false, 'retry_after' => 0, 'attempts_left' => 0]);
    }

    /**
     * Is this number locked right now? Read only.
     *
     * @return array{locked: bool, retry_after: int, unavailable: bool}
     */
    public function status(string $mobile): array
    {
        try {
            $r = $this->load($mobile);
            $left = (int) $r['locked_until'] - time();

            return ['locked' => $left > 0, 'retry_after' => max(0, $left), 'unavailable' => false];
        } catch (Throwable $e) {
            return ['locked' => true, 'retry_after' => 10, 'unavailable' => true];
        }
    }

    /** Forget everything held for a number. Used by the tests. */
    public function reset(string $mobile): void
    {
        try {
            $this->cache()->forget(self::PREFIX . self::identity($mobile));
        } catch (Throwable $e) {
        }
    }

    /* -----------------------------------------------------------------
    |  Internals
    ------------------------------------------------------------------*/

    /**
     * Run a read-modify-write on one number's record under a lock.
     *
     * @template T
     * @param  callable(array, int): T  $work   receives the record by reference
     * @param  mixed  $onFailure  what to return if the cache or the lock fails
     * @return mixed
     */
    private function locked(string $mobile, callable $work, mixed $onFailure): mixed
    {
        $key = self::identity($mobile);

        try {
            $lock = $this->cache()->lock(self::PREFIX . 'lock.' . $key, 5);
            $lock->block(2);
        } catch (Throwable $e) {
            return $this->failClosed($e, $onFailure);
        }

        try {
            $record = $this->load($mobile);
            $result = $work($record, time());
            $this->cache()->put(self::PREFIX . $key, $record, self::KEEP);

            return $result;
        } catch (Throwable $e) {
            return $this->failClosed($e, $onFailure);
        } finally {
            try {
                $lock->release();
            } catch (Throwable $e) {
                // It expires on its own after five seconds.
            }
        }
    }

    /** @return array{sends: array<int, int>, failures: int, failures_at: int, locked_until: int} */
    private function load(string $mobile): array
    {
        $record = $this->cache()->get(self::PREFIX . self::identity($mobile));

        return array_merge(['sends' => [], 'failures' => 0, 'failures_at' => 0, 'locked_until' => 0], is_array($record) ? $record : []);
    }

    private function cache(): Repository
    {
        $store = config('ai-assistant.otp_guard.cache_store');

        return $store ? Cache::store($store) : Cache::store();
    }

    private function lockoutSeconds(): int
    {
        return max(60, (int) config('ai-assistant.otp_lockout_minutes', 15) * 60);
    }

    /** @return array{allowed: bool, retry_after: int, reason: string, token: int} */
    private function deny(int $retryAfter, string $reason): array
    {
        return ['allowed' => false, 'retry_after' => max(1, $retryAfter), 'reason' => $reason, 'token' => 0];
    }

    /** @return array{allowed: bool, retry_after: int, reason: string, token: int} */
    private function denyUnavailable(): array
    {
        return $this->deny(10, 'guard_unavailable');
    }

    private function failClosed(Throwable $e, mixed $onFailure): mixed
    {
        Log::warning('AI assistant: the verification code limiter is unavailable, refusing', ['error' => $e->getMessage()]);

        return $onFailure;
    }
}
