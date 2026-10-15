<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\Log;

/**
 * Runs the configured NLU provider (and its optional fallback) and guards whatever it returns.
 *
 * A small model is a helpful interpreter and an unreliable witness: in
 * testing it named one doctor while giving another one's position, and reported
 * high confidence either way. So every result passes through the checks below
 * before the conversation engine is allowed to see it.
 *
 * The rule is: the model extracts language, this class validates it, and the
 * existing PureMed logic decides.
 */
class NluManager implements NluDriver
{
    /**
     * What happened on the most recent interpret(): one of NluOutcome's values,
     * or 'off' when the AI is switched off on purpose, or 'unrecognized' when
     * the model answered but the guard threw the answer out.
     */
    private string $outcome = 'off';

    /** Which provider the answer (or the final failure) belongs to: 'groq', 'claude' or null. */
    private ?string $provider = null;

    /** The providers asked on the last call, in order. */
    private array $attempts = [];

    /** Seconds to wait before the AI is worth asking again, after a rate limit. */
    private int $retryAfterSeconds = 0;

    public function __construct(
        private GroqNluService $groq,
        private ?ClaudeNluService $claude = null,
    ) {
    }

    /** The provider object for a name from NluRoles, or null if it was not supplied. */
    private function service(?string $name): ?NluProvider
    {
        return match ($name) {
            'groq' => $this->groq,
            'claude' => $this->claude,
            default => null,
        };
    }

    public function outcome(): string
    {
        return $this->outcome;
    }

    /** The provider the result came from ('groq' or 'claude'), or null if there was none. */
    public function provider(): ?string
    {
        return $this->provider;
    }

    /** @return array<int, string> every provider asked on the last call, in order */
    public function attempts(): array
    {
        return $this->attempts;
    }

    /** Seconds to wait before the AI is worth asking again, after a rate limit. */
    public function retryAfter(): int
    {
        return $this->outcome === NluOutcome::RATE_LIMITED ? $this->retryAfterSeconds : 0;
    }

    /**
     * Did the last interpret() fail because the AI could not be used - not
     * configured, unreachable, too slow, rate limited, or returning something
     * that broke the contract? With the fallback on, this means BOTH providers
     * could not be used.
     *
     * A model that answered but had nothing useful to say is NOT unavailable:
     * that is an ordinary "didn't understand" and the patient is simply asked
     * again. Always false when NLU is switched off on purpose.
     */
    public function unavailable(): bool
    {
        return NluOutcome::isFailure($this->outcome);
    }

    public function interpret(
        string $step,
        array $optionLabels,
        string $message,
        ?string $previousAssistantMessage = null,
        array $context = [],
        string $language = 'en'
    ): ?array {
        $this->outcome = 'off';
        $this->provider = null;
        $this->attempts = [];
        $this->retryAfterSeconds = 0;

        // NLU_DRIVER picks the primary, NLU_FALLBACK the optional second one.
        // An invalid driver means no AI; an invalid fallback means no fallback.
        $roles = NluRoles::resolve();
        $primary = $this->service($roles['primary']);

        if (!$primary) {
            return null;
        }

        // The primary is always asked first.
        $result = $primary->interpret($step, $optionLabels, $message, $previousAssistantMessage, $context, $language);
        $this->record($primary, $roles['primary'], 'primary', $step, $language);
        $this->outcome = $primary->outcome();
        $this->provider = $roles['primary'];
        $this->attempts[] = $roles['primary'];
        $this->retryAfterSeconds = $primary->retryAfter();

        // Only a FAILURE of the primary asks the fallback. "unrecognized" - the
        // primary read the message and had nothing usable - is an answer, and
        // it goes on to the rules without a second model being asked.
        $second = $this->service($roles['fallback']);

        if ($result === null && $second && $this->shouldFallBack($second, $this->outcome)) {
            $firstOutcome = $this->outcome;
            $firstRetry = $this->retryAfterSeconds;

            $result = $second->interpret($step, $optionLabels, $message, $previousAssistantMessage, $context, $language);
            $this->record($second, $roles['fallback'], 'fallback', $step, $language);
            $secondOutcome = $second->outcome();
            $this->attempts[] = $roles['fallback'];
            $this->provider = $roles['fallback'];

            // Which providers and why - never what the patient said.
            Log::info('AI assistant NLU: fell back to the second provider', [
                'from' => $roles['primary'],
                'because' => $firstOutcome,
                'to' => $roles['fallback'],
                'result' => $secondOutcome,
                'step' => $step,
            ]);

            if ($secondOutcome === NluOutcome::OK) {
                $this->outcome = NluOutcome::OK;
                $this->retryAfterSeconds = 0;
            } elseif ($secondOutcome === NluOutcome::UNRECOGNIZED) {
                // The second provider read it and had nothing usable: an answer, not an outage.
                $this->outcome = NluOutcome::UNRECOGNIZED;
                $this->retryAfterSeconds = 0;
            } else {
                // Neither could be used. If either was rate limited the patient
                // should hear that it is busy, not that it is broken.
                $limited = array_filter([
                    $firstOutcome === NluOutcome::RATE_LIMITED ? $firstRetry : null,
                    $secondOutcome === NluOutcome::RATE_LIMITED ? $second->retryAfter() : null,
                ], fn ($v) => $v !== null);

                $this->outcome = $limited ? NluOutcome::RATE_LIMITED : $secondOutcome;
                $this->retryAfterSeconds = $limited ? min($limited) : 0;
            }
        }

        if ($result === null) {
            return null;
        }

        $guarded = $this->guard($result, $optionLabels, $step, $context);

        if ($guarded === null) {
            // The model answered; the guard did not accept it. Not a failure,
            // so no second provider is asked about it.
            $this->outcome = NluOutcome::UNRECOGNIZED;
        }

        return $guarded;
    }

    /**
     * One line per request actually sent to a provider, so "was the AI used,
     * and how did it go?" can be answered from the log: which provider, in
     * which role, the step and language, how it ended, how long it took and
     * what it cost in tokens. Never the patient's message, the prompt, the
     * answer or any key - only counts and names.
     */
    private function record(NluProvider $provider, string $name, string $role, string $step, string $language): void
    {
        $meta = $provider->meta();

        if (!isset($meta['latency_ms'])) {
            return; // nothing was sent (not available, paused or refused locally)
        }

        Log::info('AI assistant NLU: provider call', [
            'provider' => $name,
            'role' => $role,
            'step' => $step,
            'lang' => $language,
            'outcome' => $provider->outcome(),
            'status' => $meta['status'] ?? null,
            'latency_ms' => $meta['latency_ms'],
            'input_tokens' => $meta['input_tokens'] ?? $meta['prompt_tokens'] ?? null,
            'output_tokens' => $meta['output_tokens'] ?? $meta['completion_tokens'] ?? null,
        ]);
    }

    /**
     * May the fallback be asked about a primary result with this outcome?
     *
     * All of these must hold: a fallback is configured, it is ready to run (on,
     * with a key), and the outcome is one of the failures the settings list.
     * Any one missing means the second provider is never contacted.
     */
    private function shouldFallBack(NluProvider $second, string $outcome): bool
    {
        if (!NluOutcome::isFailure($outcome)) {
            return false;
        }

        if (!in_array($outcome, (array) config('ai-assistant.nlu_fallback_on', NluOutcome::FAILURES), true)) {
            return false;
        }

        return $second->available();
    }

    /**
     * Reject anything the model could have invented or contradicted itself on.
     *
     * @param  array<string, mixed>  $result
     * @param  array<int, string>  $optionLabels
     * @return array<string, mixed>|null
     */
    private function guard(array $result, array $optionLabels, string $step, array $context = []): ?array
    {
        $labels = array_values(array_filter($optionLabels));

        // PROTECTION 3 (checked first, before the entity rules below): a small
        // model reads CURRENT SELECTION as something the
        // patient said and hands the already-chosen day or time straight back.
        // Measured on llama3.2:3b: "no I do not want this appointment" returned
        // the selected date, which would have replayed a rejection as a date
        // pick. A patient re-picking what is already selected changes nothing,
        // so echoes are always safe to drop.
        foreach (['date' => 'date', 'time' => 'time'] as $field => $contextKey) {
            if (!empty($result[$field]) && !empty($context[$contextKey])
                && $this->normalise((string) $result[$field]) === $this->normalise((string) $context[$contextKey])) {
                Log::info('AI assistant NLU: discarded echoed ' . $field, ['step' => $step]);
                $result[$field] = null;
            }
        }

        // Models like to echo the list numbering back, e.g. "2. Dr Gunnar Gauff".
        $entity = $this->stripNumbering($result['entity'] ?? null);
        $ordinal = $result['ordinal'] ?? null;
        $ordinal = is_int($ordinal) && $ordinal >= 1 ? $ordinal : null;

        $matchedIndex = null;

        if ($entity !== null && $labels) {
            $matchedIndex = $this->indexOfLabel($entity, $labels);

            // PROTECTION 1: a name that is not on the list was invented. Drop it
            // rather than hand it to the matcher.
            if ($matchedIndex === null) {
                Log::info('AI assistant NLU: discarded unmatched entity', ['step' => $step]);
                $entity = null;
            }
        }

        // PROTECTION 2: the model named one option but gave another one's
        // position. There is no safe way to choose between them, so the whole
        // interpretation is rejected and the patient is asked again.
        if ($entity !== null && $matchedIndex !== null && $ordinal !== null && $ordinal !== $matchedIndex + 1) {
            Log::info('AI assistant NLU: rejected contradictory result', [
                'step' => $step,
                'named_position' => $matchedIndex + 1,
                'stated_ordinal' => $ordinal,
            ]);

            return null;
        }

        // An ordinal pointing past the end of the list is not a real choice.
        if ($ordinal !== null && $labels && $ordinal > count($labels)) {
            $ordinal = null;
        }

        $result['entity'] = $entity;
        $result['ordinal'] = $ordinal;

        // Some intents are meaningful on their own - "show me the slots" carries
        // no entity by design, and "I don't mind which doctor" deliberately
        // carries none either.
        // "another doctor" and "show me the times" name nothing by design, and
        // "I don't mind which doctor" deliberately names nothing either.
        $standalone = in_array($result['intent'] ?? '', [
            'request_slots', 'confirm_booking', 'reject_booking',
            'change_doctor', 'change_appointment_type', 'change_slot', 'change_date',
            'select_slot_preference',
            'book_appointment', 'view_appointments', 'cancel_appointment',
        ], true);

        // Nothing usable left: let the deterministic re-ask handle it.
        if (!$standalone && $entity === null && $ordinal === null
            && empty($result['date']) && empty($result['time']) && empty($result['time_preference'])
            && !in_array($step, ['intent', 'confirm', 'cancel_confirm'], true)) {
            return null;
        }

        return $result;
    }

    private function stripNumbering(mixed $entity): ?string
    {
        if (!is_string($entity)) {
            return null;
        }

        $entity = trim(preg_replace('/^\s*\d{1,2}\s*[.)-]\s*/', '', $entity));

        return $entity === '' ? null : $entity;
    }

    /**
     * Position of the label the entity refers to, or null when it matches none.
     *
     * @param  array<int, string>  $labels
     */
    private function indexOfLabel(string $entity, array $labels): ?int
    {
        $needle = $this->normalise($entity);

        if ($needle === '') {
            return null;
        }

        foreach ($labels as $index => $label) {
            if ($this->normalise($label) === $needle) {
                return $index;
            }
        }

        // Allow a partial reference such as "Gunnar Gauff" for "Dr Gunnar Gauff",
        // but only when exactly one option could be meant.
        $candidates = [];

        foreach ($labels as $index => $label) {
            $haystack = $this->normalise($label);

            // A fragment of fewer than three letters is not a reference to
            // anyone: "nobody" contains "b", and that is not Dr B.
            $shorter = min(mb_strlen($haystack), mb_strlen($needle));

            if ($haystack !== '' && $shorter >= 3 && (str_contains($haystack, $needle) || str_contains($needle, $haystack))) {
                $candidates[] = $index;
            }
        }

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    private function normalise(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\b(dr|doctor|prof)\b\.?/u', ' ', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
