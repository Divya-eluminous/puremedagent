<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\Log;

/**
 * Chooses the NLU backend and guards whatever it returns.
 *
 * A small local model is a helpful interpreter and an unreliable witness: in
 * testing it named one doctor while giving another one's position, and reported
 * high confidence either way. So every result passes through the checks below
 * before the conversation engine is allowed to see it.
 *
 * The rule is: the model extracts language, this class validates it, and the
 * existing PureMed logic decides.
 */
class NluManager implements NluDriver
{
    public function __construct(
        private OllamaNluService $ollama,
        private GroqNluService $groq,
    ) {
    }

    public function interpret(
        string $step,
        array $optionLabels,
        string $message,
        ?string $previousAssistantMessage = null
    ): ?array {
        $driver = $this->driver();

        if (!$driver) {
            return null;
        }

        $result = $driver->interpret($step, $optionLabels, $message, $previousAssistantMessage);

        if ($result === null) {
            return null;
        }

        return $this->guard($result, $optionLabels, $step);
    }

    private function driver(): ?NluDriver
    {
        return match ((string) config('ai-assistant.nlu_driver')) {
            'ollama' => $this->ollama,
            'groq' => $this->groq,
            default => null,
        };
    }

    /**
     * Reject anything the model could have invented or contradicted itself on.
     *
     * @param  array<string, mixed>  $result
     * @param  array<int, string>  $optionLabels
     * @return array<string, mixed>|null
     */
    private function guard(array $result, array $optionLabels, string $step): ?array
    {
        $labels = array_values(array_filter($optionLabels));

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
        $standalone = in_array($result['intent'] ?? '', [
            'request_slots', 'confirm_booking', 'reject_booking', 'change_selection',
            'book_appointment', 'view_appointments', 'cancel_appointment', 'help', 'greeting',
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

            if ($haystack !== '' && (str_contains($haystack, $needle) || str_contains($needle, $haystack))) {
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
