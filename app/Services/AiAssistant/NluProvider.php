<?php

namespace App\Services\AiAssistant;

/**
 * What NluManager needs from a provider (Groq or Claude), beyond interpreting:
 * whether it may be used at all, and how its last call ended.
 */
interface NluProvider extends NluDriver
{
    /** Selected as primary or fallback, enabled, and holding a key, model and endpoint. */
    public function available(): bool;

    /** One of NluOutcome's values, for the last interpret(). */
    public function outcome(): string;

    /** @return array<string, mixed> timing and token counts of the last call (empty if nothing was sent) */
    public function meta(): array;

    /** Seconds to wait after a rate limit, otherwise 0. */
    public function retryAfter(): int;
}
