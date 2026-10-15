<?php

namespace App\Services\AiAssistant;

/**
 * What counts as a clear "yes" to booking or cancelling an appointment.
 *
 * Booking and cancelling cannot be taken back by the patient, so agreement is
 * never inferred from a sentence that merely CONTAINS a yes. A free-text answer
 * is consent only when every word in it is an agreeing or a harmless filler
 * word, and at least one of them agrees. Anything else - a question, a
 * refusal, a "but", a day, a time, a number, a word this list has never heard -
 * is not consent, however friendly it sounds. The patient is then asked once
 * more, or can tap the button; that costs one tap and can never cost a wrong
 * appointment.
 *
 * This is deliberately a deterministic word list, not a model: the AI is never
 * asked whether the patient agreed, and its confidence is never consulted.
 *
 * ===========================================================================
 *  REVIEW BEFORE PRODUCTION
 *  The German lists below (the GERMAN_* constants) are a first draft written
 *  for the demo. They must be reviewed by a native German speaker, or by the
 *  practice, before this goes live. The English lists want a second pair of
 *  eyes as well. Adding a word to an AFFIRM list makes that word enough to
 *  book; adding one to a FILLER list is harmless on its own. When unsure,
 *  leave a word out: an unrecognised answer only costs one more tap.
 * ===========================================================================
 */
class ConsentRules
{
    /* ---- English -------------------------------------------------------- */

    /** A word that on its own can mean "yes". */
    public const ENGLISH_AFFIRM = [
        'yes', 'yeah', 'yep', 'yup', 'ya', 'ok', 'okay', 'sure', 'confirm', 'confirmed', 'correct',
        'right', 'fine', 'perfect', 'absolutely', 'definitely', 'certainly', 'agreed', 'affirmative',
        'works', 'good', 'great', 'ahead',
    ];

    /** Only agreement to BOOK: "book it". Never counts when cancelling. */
    public const ENGLISH_AFFIRM_BOOK = ['book', 'schedule'];

    /** Only agreement to CANCEL: "cancel it". Never counts when booking. */
    public const ENGLISH_AFFIRM_CANCEL = ['cancel', 'remove', 'delete'];

    /** Harmless on their own; they may surround an agreeing word but never replace one. */
    public const ENGLISH_FILLER = [
        'please', 'thanks', 'thank', 'thx', 'you', 'that', 'thats', 'this', 'it', 'its', 'the',
        'appointment', 'booking', 'one', 'for', 'me', 'my', 'and', 'i', 'id', 'would', 'like', 'to',
        'want', 'lets', 'let', 'us', 'do', 'go', 'sounds', 'is', 'am', 'then', 'now', 'so', 'just',
        'very', 'much', 'all', 'time', 'sir', 'there', 'yes', 'looks', 'look',
        'can', 'may',
    ];

    /**
     * Short phrases that agree although none of their words is a yes. Matched
     * as whole words in a row; the rest of the answer must still be filler.
     */
    public const ENGLISH_PHRASES = ['do it', 'go for it', 'lets do it', 'please do', 'do that', 'looks good', 'looks right'];

    /** Words that rule an answer out no matter what else it says. */
    public const ENGLISH_DENY = [
        'no', 'not', 'nope', 'nah', 'dont', 'never', 'cant', 'cannot', 'wont', 'wait', 'stop', 'hold',
        'but', 'however', 'instead', 'actually', 'except', 'unless', 'if', 'maybe', 'perhaps', 'wrong',
        'incorrect', 'change', 'another', 'other', 'different', 'later', 'earlier', 'rather', 'prefer',
        'isnt', 'wasnt', 'didnt', 'doesnt', 'unsure', 'hmm',
    ];

    /* ---- German (DRAFT - needs native-speaker review) ------------------- */

    public const GERMAN_AFFIRM = [
        'ja', 'jawohl', 'jep', 'jup', 'jo', 'klar', 'gerne', 'gern', 'genau', 'richtig', 'stimmt',
        'korrekt', 'einverstanden', 'passt', 'sicher', 'natürlich', 'natuerlich', 'selbstverständlich',
        'selbstverstaendlich', 'bestätigt', 'bestaetigt', 'bestätigen', 'bestaetigen', 'okay', 'ok',
        'perfekt', 'super', 'gut', 'prima', 'wunderbar', 'ordnung',
    ];

    public const GERMAN_AFFIRM_BOOK = ['buchen', 'buche', 'vereinbaren'];

    public const GERMAN_AFFIRM_CANCEL = ['stornieren', 'storniere', 'absagen', 'löschen', 'loeschen'];

    public const GERMAN_FILLER = [
        'bitte', 'danke', 'dankeschön', 'dankeschoen', 'das', 'es', 'ihn', 'den', 'termin', 'machen',
        'wir', 'so', 'doch', 'mal', 'ist', 'für', 'fuer', 'mich', 'mir', 'ich', 'möchte', 'moechte',
        'möchten', 'will', 'und', 'dann', 'alles', 'in', 'jetzt', 'gleich', 'nehmen', 'nehme',
        'sie', 'ab', 'sagen', 'sage', 'bitteschön', 'bitteschoen', 'mach', 'sieht', 'aus',
    ];

    public const GERMAN_PHRASES = ['machen wir', 'machen wir das', 'mach das', 'mach es', 'sieht gut aus', 'in ordnung'];

    public const GERMAN_DENY = [
        'nein', 'nee', 'nö', 'noe', 'nicht', 'kein', 'keine', 'keinen', 'niemals', 'nie', 'aber',
        'jedoch', 'sondern', 'allerdings', 'eigentlich', 'stattdessen', 'lieber', 'anders', 'andere',
        'anderen', 'anderes', 'ändern', 'aendern', 'später', 'spaeter', 'früher', 'frueher', 'warte',
        'warten', 'moment', 'stopp', 'stop', 'falsch', 'vielleicht', 'wenn', 'ob', 'hmm', 'unsicher',
    ];

    /** Longest answer that can still be a plain agreement. */
    private const MAX_WORDS = 8;
    private const MAX_CHARS = 60;

    /**
     * @param  string  $text      what the patient said or typed
     * @param  string  $language  'en' or 'de' - the language the patient chose
     * @param  bool    $cancel    true when the question is "shall I cancel it?"
     */
    public function isClearConsent(string $text, string $language, bool $cancel = false): bool
    {
        $text = trim($text);

        // A question is asking, not agreeing. Checked on the raw text because
        // punctuation is stripped below.
        if ($text === '' || mb_strlen($text) > self::MAX_CHARS || str_contains($text, '?')) {
            return false;
        }

        $words = $this->words($text);

        if (!$words || count($words) > self::MAX_WORDS) {
            return false;
        }

        [$affirm, $filler, $deny, $phrases] = $this->lists($language, $cancel);

        // A single word from the deny list ends it. Checked before anything
        // is allowed, so "yes but" and "ja aber" and "yes no" all fail here
        // even if a later edit to a list were careless.
        foreach ($words as $word) {
            if (in_array($word, $deny, true)) {
                return false;
            }
        }

        // "do it" agrees although neither word is a yes.
        $agrees = false;
        $joined = ' ' . implode(' ', $words) . ' ';

        foreach ($phrases as $phrase) {
            if (str_contains($joined, ' ' . $phrase . ' ')) {
                $agrees = true;
            }
        }

        foreach ($words as $word) {
            if (in_array($word, $affirm, true)) {
                $agrees = true;
            } elseif (!in_array($word, $filler, true)) {
                // A word nobody listed: a day, a time, a name, a request. Not
                // a plain yes.
                return false;
            }
        }

        return $agrees;
    }

    /**
     * Lower-case words with apostrophes dropped ("that's" -> "thats") and
     * everything else that is not a letter or a digit treated as a break.
     * A digit is kept as a word, so a number is "a word nobody listed".
     *
     * @return array<int, string>
     */
    private function words(string $text): array
    {
        $text = mb_strtolower($text);
        $text = str_replace(["'", "\u{2019}", "\u{2018}", '`'], '', $text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return preg_split('/\s+/u', trim((string) $text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, string>, 3: array<int, string>}
     */
    private function lists(string $language, bool $cancel): array
    {
        if ($language === 'de') {
            return [
                array_merge(self::GERMAN_AFFIRM, $cancel ? self::GERMAN_AFFIRM_CANCEL : self::GERMAN_AFFIRM_BOOK),
                self::GERMAN_FILLER,
                // Agreeing to the OTHER action is a deny: "storniere" is not a yes to booking.
                array_merge(self::GERMAN_DENY, $cancel ? self::GERMAN_AFFIRM_BOOK : self::GERMAN_AFFIRM_CANCEL),
                self::GERMAN_PHRASES,
            ];
        }

        return [
            array_merge(self::ENGLISH_AFFIRM, $cancel ? self::ENGLISH_AFFIRM_CANCEL : self::ENGLISH_AFFIRM_BOOK),
            self::ENGLISH_FILLER,
            array_merge(self::ENGLISH_DENY, $cancel ? self::ENGLISH_AFFIRM_BOOK : self::ENGLISH_AFFIRM_CANCEL),
            self::ENGLISH_PHRASES,
        ];
    }
}
