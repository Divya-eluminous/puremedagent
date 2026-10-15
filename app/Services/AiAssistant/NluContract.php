<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\Log;

/**
 * What every AI provider is asked, and what every answer must look like.
 *
 * Groq and Claude are interchangeable behind NluDriver, so the words they are
 * given and the rules their answers are held to live HERE, once. Two copies
 * would drift apart, and the fallback provider would then be judged by a
 * different standard from the primary one.
 *
 * Nothing in this class talks to a provider. It builds the prompt text, and it
 * turns a decoded answer into the one shape the conversation engine accepts -
 * or into a classification of why it could not.
 *
 * What is sent is deliberately small: the step name, the labels of the options
 * already on the patient's screen, what they have chosen so far (labels only),
 * the assistant's last line, and the patient's one message, each cut to a short
 * length. No identifiers, no tokens, no registration details.
 */
final class NluContract
{
    /**
     * Steps allowed to use NLU.
     *
     * Registration steps (first_name, last_name, mobile_no, birth_date, email,
     * email_confirm, gender, otp_verify) are deliberately absent: those answers
     * are patient identifying information and must never leave this server.
     */
    public const ELIGIBLE_STEPS = [
        'intent',
        'doctor',
        'appointment_type',
        'slot_date',
        'slot_time',
        'confirm',
        'cancel_select',
        'cancel_confirm',
        'appointments',
    ];

    public const INTENTS = [
        'book_appointment',
        'view_appointments',
        'cancel_appointment',
        'select_doctor',
        'change_doctor',
        'select_appointment_type',
        'change_appointment_type',
        'select_slot',
        'change_slot',
        'select_slot_preference',
        'change_date',
        'request_slots',
        'confirm_booking',
        'reject_booking',
        'unknown',
    ];

    public const TIME_PREFERENCES = ['morning', 'afternoon', 'evening'];

    /** Keep prompts small: cost control, and a long message is not a selection. */
    public const MAX_MESSAGE_CHARS = 200;
    public const MAX_OPTIONS = 40;
    public const MAX_OPTION_CHARS = 80;

    public const LANGUAGES = ['en', 'de'];

    public static function language(string $language): string
    {
        return in_array($language, self::LANGUAGES, true) ? $language : 'en';
    }

    /** The instructions every provider is given. */
    public static function systemPrompt(string $language = 'en'): string
    {
        return self::languageRule($language) . 'You interpret a patient message for a medical appointment booking assistant. '
            . 'Decide what the patient means in the context of the CURRENT STEP. '
            . '"entity" must be copied exactly from the option list, character for character, or null. '
            . '"ordinal" is the number of that same option, or null. entity and ordinal must always refer '
            . 'to the SAME option. Never invent a doctor, appointment type, date, time or availability, '
            . 'and never refer to anything not in the list. You do not decide what is available and you '
            . 'never book anything. '
            . 'Use request_slots when the patient is asking to SEE the available times rather than '
            . 'rejecting anything, for example "you have not shown me the slots", "what times are '
            . 'available", "let me see the available times", "are there any other times". '
            . 'Use reject_booking only when they are declining the appointment itself. '
            . 'When the patient wants to swap something in CURRENT SELECTION, use the intent for the '
            . 'part they are changing: change_doctor ("another doctor", "someone else"), '
            . 'change_appointment_type, change_date ("what about Friday", "any day next week") or '
            . 'change_slot ("another time", "something later"). Change only what they actually '
            . 'mentioned: "another time" does not change the date, and "another doctor" does not '
            . 'change the appointment type or the time. A patient may change two things at once, for '
            . 'example "another doctor tomorrow" - return change_doctor and put the day in "date". '
            . 'Use select_slot_preference when they name only a part of the day, such as "morning". '
            . 'Use view_appointments for any question ABOUT appointments the patient already has, '
            . 'for example "how many appointments do I have", "when is my next appointment", '
            . '"do I have any appointments", "what appointments are coming up", "list my appointments". '
            . 'Those are questions, not requests to make a new booking - use book_appointment only when '
            . 'they want a NEW appointment. '
            . 'CURRENT SELECTION is background only. Never copy anything out of it into your answer: '
            . '"date" and "time" must be the patient\'s own words, and must be null when the patient '
            . 'did not mention a day or a time. '
            . 'If the patient does not mind which option, return the select intent with entity and '
            . 'ordinal both null - never pick one for them. '
            . 'If you are unsure, return intent "unknown" with a low confidence.';
    }

    /**
     * The patient may write in English or German, and the labels may be in
     * either. Everything the model returns is expressed in English terms.
     */
    public static function languageRule(string $language): string
    {
        $rule = 'The patient may write in English or German, and option labels may be in either language. '
            . 'Understand both, but ALWAYS return "date" and "time_preference" in English terms '
            . '("tomorrow", "day after tomorrow", "Friday", "next Monday", "13 August", "morning", '
            . '"afternoon", "evening") whatever language the patient used. German examples: '
            . '"morgen" is tomorrow, "übermorgen" is day after tomorrow, "Vormittag" and "morgens" are '
            . 'morning, "Nachmittag" is afternoon, "abends" is evening, "Montag" to "Sonntag" are '
            . 'Monday to Sunday, "nächste Woche" is next week, "um 14 Uhr" is time 14:00, '
            . '"ja" means yes and "nein" means no, "ein anderer Arzt" is change_doctor, '
            . '"meine Termine" is view_appointments, "Termin absagen" or "stornieren" is cancel_appointment, '
            . '"Termin buchen" or "ich möchte einen Termin" is book_appointment. ';

        if ($language === 'de') {
            $rule .= 'The patient is currently writing in German. ';
        }

        return $rule;
    }

    /**
     * Only the step name, the option labels the patient can already see, and
     * their message. No identifiers, no tokens, no patient details.
     *
     * @param  array<int, string>  $optionLabels
     */
    public static function userPrompt(string $step, array $optionLabels, string $message, ?string $previousAssistantMessage = null, array $context = []): string
    {
        $prompt = 'CURRENT STEP: ' . $step . "\n";

        // What the patient has chosen so far, as the labels already on their
        // screen. Without this "another doctor" and "another time" look
        // identical to the model. Labels only - never ids, never patient data.
        $selection = array_filter([
            'Doctor' => $context['doctor'] ?? null,
            'Appointment' => $context['appointment'] ?? null,
            'Date' => $context['date'] ?? null,
            'Time' => $context['time'] ?? null,
        ]);

        if ($selection) {
            $prompt .= "CURRENT SELECTION:\n";

            foreach ($selection as $label => $value) {
                $prompt .= '- ' . $label . ': ' . mb_substr((string) $value, 0, self::MAX_OPTION_CHARS) . "\n";
            }
        }

        $options = array_slice(array_values(array_filter($optionLabels)), 0, self::MAX_OPTIONS);

        if ($options) {
            $prompt .= "AVAILABLE OPTIONS:\n";

            foreach ($options as $index => $label) {
                $prompt .= ($index + 1) . '. ' . mb_substr((string) $label, 0, self::MAX_OPTION_CHARS) . "\n";
            }
        }

        // One line of context - what the assistant just said - so a reply like
        // "you haven't shown me the slots" can be read against the question it
        // answers. Never the whole conversation, and never patient details.
        if ($previousAssistantMessage) {
            $prompt .= "PREVIOUS ASSISTANT MESSAGE:\n\""
                . mb_substr($previousAssistantMessage, 0, self::MAX_MESSAGE_CHARS) . "\"\n";
        }

        return $prompt . "\nPATIENT MESSAGE:\n\"" . mb_substr($message, 0, self::MAX_MESSAGE_CHARS) . '"';
    }

    /**
     * Trust nothing. A reply that does not fit the contract is discarded, and
     * the assistant falls back to deterministic matching.
     *
     * @param  array<string, mixed>  $decoded
     * @return array{0: array<string, mixed>|null, 1: string}  the interpretation (or null) and its NluOutcome
     */
    public static function validate(array $decoded, string $step, float $minimumConfidence, string $provider): array
    {
        $intent = $decoded['intent'] ?? null;

        // Outside the schema: not an intent we know. That is a broken reply,
        // not a message the AI could not read.
        if (!is_string($intent) || !in_array($intent, self::INTENTS, true)) {
            return [null, NluOutcome::MALFORMED];
        }

        // The model's own "I don't know".
        if ($intent === 'unknown') {
            return [null, NluOutcome::UNRECOGNIZED];
        }

        $confidence = $decoded['confidence'] ?? null;

        if (!is_numeric($confidence)) {
            return [null, NluOutcome::MALFORMED];
        }

        $confidence = (float) $confidence;

        if ($confidence < $minimumConfidence) {
            // Never the message itself: it is the patient's words.
            Log::info('AI assistant NLU: below confidence threshold', [
                'provider' => $provider,
                'step' => $step,
                'intent' => $intent,
                'confidence' => $confidence,
            ]);

            return [null, NluOutcome::UNRECOGNIZED];
        }

        $ordinal = $decoded['ordinal'] ?? null;
        $ordinal = is_int($ordinal) && $ordinal >= 1 ? $ordinal : null;

        $timePreference = $decoded['time_preference'] ?? null;
        $timePreference = in_array($timePreference, self::TIME_PREFERENCES, true) ? $timePreference : null;

        return [[
            'intent' => $intent,
            'entity' => self::text($decoded['entity'] ?? null),
            'ordinal' => $ordinal,
            'date' => self::text($decoded['date'] ?? null),
            'time_preference' => $timePreference,
            'time' => self::text($decoded['time'] ?? null),
            'confidence' => $confidence,
        ], NluOutcome::OK];
    }

    public static function text(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim(mb_substr($value, 0, self::MAX_OPTION_CHARS));

        return $value === '' ? null : $value;
    }
}
