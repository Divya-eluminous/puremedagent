<?php

namespace App\Services\AiAssistant;

/**
 * A natural language understanding backend.
 *
 * Implementations interpret one patient sentence in the context of the question
 * currently being asked. They receive only the step name, the option labels the
 * patient can already see, and the message itself - never identifiers, tokens or
 * patient details - and they return an interpretation or null.
 *
 * A driver never resolves anything: the conversation engine matches whatever
 * comes back against the real PureMed list.
 */
interface NluDriver
{
    /**
     * @param  string  $step  the conversation step being answered
     * @param  array<int, string>  $optionLabels  labels already shown to the patient
     * @param  string  $message  what the patient typed or said
     * @param  array{doctor?: ?string, appointment?: ?string, date?: ?string, time?: ?string}  $context
     *         display labels for what the patient has already chosen - never ids
     * @return array{intent: string, entity: ?string, ordinal: ?int, date: ?string,
     *               time_preference: ?string, time: ?string, confidence: float}|null
     */
    public function interpret(
        string $step,
        array $optionLabels,
        string $message,
        ?string $previousAssistantMessage = null,
        array $context = []
    ): ?array;
}
