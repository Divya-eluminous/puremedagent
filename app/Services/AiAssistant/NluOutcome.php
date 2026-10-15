<?php

namespace App\Services\AiAssistant;

/**
 * What can happen when a provider is asked to interpret a message.
 *
 *  OK            an interpretation came back and passed validation
 *  UNRECOGNIZED  the provider answered but had nothing usable: "unknown", too
 *                little confidence, or nothing to interpret. NOT a failure: the
 *                message goes on to the deterministic rules, and no second
 *                provider is asked
 *  TIMEOUT       no answer in time
 *  MALFORMED     an answer arrived but broke the contract: not JSON, cut short,
 *                outside the schema, or a refusal
 *  RATE_LIMITED  the provider answered 429 (or its monthly spend cap), or this
 *                application's own budget for it said wait
 *  UNAVAILABLE   not configured, an error response, or the network is down
 *
 * The four failures are the ones where the PROVIDER could not be used, and the
 * only ones that can make the fallback provider be asked.
 */
final class NluOutcome
{
    public const OK = 'ok';
    public const UNRECOGNIZED = 'unrecognized';
    public const TIMEOUT = 'timeout';
    public const MALFORMED = 'malformed';
    public const RATE_LIMITED = 'rate_limited';
    public const UNAVAILABLE = 'unavailable';

    public const FAILURES = [self::TIMEOUT, self::MALFORMED, self::RATE_LIMITED, self::UNAVAILABLE];

    public static function isFailure(string $outcome): bool
    {
        return in_array($outcome, self::FAILURES, true);
    }
}
