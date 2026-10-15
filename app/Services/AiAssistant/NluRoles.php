<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Which provider is the primary and which (if any) is the fallback.
 *
 *   NLU_DRIVER    groq | claude | none   the primary. Anything else switches ALL AI off.
 *   NLU_FALLBACK  none | groq | claude   the optional second provider. Anything else, or
 *                                        the same provider as the primary, means no fallback;
 *                                        a valid primary keeps working.
 *
 * A provider that is neither the primary nor the fallback is never contacted:
 * each service checks this before it sends anything, so a mistake in .env can
 * only ever mean "less AI", never an unintended request.
 *
 * Mistakes are logged as warnings - once an hour per mistake, so a wrong value
 * does not flood the log - and the warning carries the setting's name and the
 * kind of mistake, never a secret and never a patient's message. The offending
 * value itself is cut short and limited to plain characters.
 */
final class NluRoles
{
    public const PROVIDERS = ['groq', 'claude'];

    /** @var array<string, true> warnings already written by this process */
    private static array $warned = [];

    /**
     * @return array{primary: ?string, fallback: ?string}
     */
    public static function resolve(): array
    {
        $driver = self::normalise(config('ai-assistant.nlu_driver'));
        $primary = null;

        if ($driver === '' || $driver === 'none') {
            return ['primary' => null, 'fallback' => null];
        }

        if (in_array($driver, self::PROVIDERS, true)) {
            $primary = $driver;
        } else {
            self::warn('driver', 'NLU_DRIVER is not one of groq, claude or none, so the AI is switched off', $driver);

            return ['primary' => null, 'fallback' => null];
        }

        $second = self::normalise(config('ai-assistant.nlu_fallback'));
        $fallback = null;

        if ($second === '' || $second === 'none') {
            // No fallback, on purpose.
        } elseif (!in_array($second, self::PROVIDERS, true)) {
            self::warn('fallback', 'NLU_FALLBACK is not one of none, groq or claude, so there is no fallback', $second);
        } elseif ($second === $primary) {
            self::warn('fallback-same', 'NLU_FALLBACK is the same provider as NLU_DRIVER, so there is no fallback', $second);
        } else {
            $fallback = $second;
        }

        return ['primary' => $primary, 'fallback' => $fallback];
    }

    /** Is this provider the primary or the fallback right now? */
    public static function selected(string $provider): bool
    {
        $roles = self::resolve();

        return $roles['primary'] === $provider || $roles['fallback'] === $provider;
    }

    private static function normalise(mixed $value): string
    {
        return is_string($value) ? strtolower(trim($value)) : '';
    }

    private static function warn(string $kind, string $message, string $value): void
    {
        $shown = substr(preg_replace('/[^a-z0-9,_ .-]/', '?', $value), 0, 20);
        $key = 'ai_assistant.nlu_config_warning.' . $kind . '.' . md5($value);

        try {
            // Once an hour across requests. If the cache is unavailable, fall
            // back to once per process rather than not at all.
            if (!Cache::add($key, 1, 3600)) {
                return;
            }
        } catch (\Throwable) {
            if (isset(self::$warned[$key])) {
                return;
            }
            self::$warned[$key] = true;
        }

        Log::warning('AI assistant NLU configuration: ' . $message, ['setting' => $kind === 'driver' ? 'NLU_DRIVER' : 'NLU_FALLBACK', 'value' => $shown]);
    }
}
