<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\DB;

/**
 * Where the time in one conversation turn goes.
 *
 * begin() starts the clock at the top of a turn; the AI call, each PureMed
 * operation and this application's own database queries add their durations as
 * they happen; summary() is what ChatController writes to the log once the turn
 * is over.
 *
 * Only durations, counts and the names of steps, operations and providers are
 * kept. Never a message, a name, a number, a date, an appointment, a token, a
 * key or any part of a response - there is nowhere in this class to put one.
 */
final class TurnTimer
{
    private static bool $listening = false;

    private bool $active = false;

    private float $started = 0.0;

    private int $aiNs = 0;

    private int $aiCalls = 0;

    /** @var array<int, string> */
    private array $aiProviders = [];

    private ?string $aiOutcome = null;

    /** @var array<string, array{n: int, ms: int, failed: int}> */
    private array $puremed = [];

    private float $dbMs = 0.0;

    private int $dbQueries = 0;

    /** The timer for the request being served. */
    public static function current(): self
    {
        app()->singletonIf(self::class, fn () => new self());

        return app(self::class);
    }

    public function begin(): void
    {
        $this->active = true;
        $this->started = hrtime(true);
        $this->aiNs = 0;
        $this->aiCalls = 0;
        $this->aiProviders = [];
        $this->aiOutcome = null;
        $this->puremed = [];
        $this->dbMs = 0.0;
        $this->dbQueries = 0;

        if (!self::$listening) {
            self::$listening = true;

            // Counts and durations only; the SQL and its bindings are not read.
            DB::listen(function ($query) {
                $timer = self::current();

                if ($timer->active) {
                    $timer->dbQueries++;
                    $timer->dbMs += (float) $query->time;
                }
            });
        }
    }

    public function end(): void
    {
        $this->active = false;
    }

    /** Start a stopwatch for one AI or PureMed call. */
    public function now(): int
    {
        return hrtime(true);
    }

    /**
     * @param  array<int, string>  $providers  the providers that were asked, in order
     */
    public function ai(int $startedAt, array $providers, ?string $outcome): void
    {
        if (!$this->active) {
            return;
        }

        $this->aiNs += hrtime(true) - $startedAt;
        $this->aiCalls += count($providers);
        $this->aiProviders = array_merge($this->aiProviders, $providers);
        $this->aiOutcome = $outcome;
    }

    public function puremedCall(string $operation, int $startedAt, bool $failed): void
    {
        if (!$this->active) {
            return;
        }

        $this->puremed[$operation] ??= ['n' => 0, 'ms' => 0, 'failed' => 0];
        $this->puremed[$operation]['n']++;
        $this->puremed[$operation]['ms'] += $this->ms(hrtime(true) - $startedAt);
        $this->puremed[$operation]['failed'] += $failed ? 1 : 0;
    }

    /** @return array<string, array{n: int, ms: int, failed: int}> */
    public function puremed(): array
    {
        return $this->puremed;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(string $stepBefore, string $stepAfter, string $lang, ?string $shortcut): array
    {
        return [
            'step_before' => $stepBefore,
            'step_after' => $stepAfter,
            'lang' => $lang,
            'shortcut' => $shortcut,
            'total_ms' => $this->ms(hrtime(true) - $this->started),
            'ai_calls' => $this->aiCalls,
            'ai_providers' => $this->aiProviders,
            'ai_ms' => $this->ms($this->aiNs),
            'ai_outcome' => $this->aiOutcome,
            'puremed_ms' => (int) array_sum(array_column($this->puremed, 'ms')),
            'puremed_calls' => $this->puremed,
            'db_queries' => $this->dbQueries,
            'db_ms' => (int) round($this->dbMs),
        ];
    }

    private function ms(int $nanoseconds): int
    {
        return max(0, (int) round($nanoseconds / 1_000_000));
    }
}
