# PureMed AI assistant - operations notes

How free text is understood, what can be switched off, and what must change before this runs on more than one server.

## How a message is handled (English and German alike)

1. A button tap never goes to the AI. Registration steps (mobile, date of birth, email, name, gender, the code) never go to the AI.
2. Messages a deterministic rule owns are answered by the rules without the AI: a plain yes/no at the booking or cancellation question, "start over", "show me more", "are there other doctors", "no thanks, that's all", "past appointments", a follow-up about the appointment list ("what about the second one"), and a bare number at the time question ("11").
3. Everything else on an AI-eligible step goes to Groq. Its answer is checked by `NluManager::guard()`, turned into a plain phrase, and run through the same rule matchers as typed text. The AI never touches PureMed.
4. If the AI has nothing usable, or fails, the rules run on what the patient actually said.

**Consent is never the AI's call.** `book()` and `cancelAppointment()` refuse to act unless `consents()` recorded a consent for that very answer. A free-text answer is consent only when `ConsentRules` finds it to be a short run of agreeing/filler words in the patient's language. A chip counts only on the confirmation question it belongs to, with the booking or cancellation actually pending.

## Switches (all in `.env`; run `php artisan config:clear` after changing one)

| Setting | Effect |
|---|---|
| `AI_ASSISTANT_AI_FIRST=false` | **Rollback.** English returns to rules-first (the AI is asked only when the rules fail). German keeps its behaviour: AI first. |
| `NLU_DRIVER=none` | No AI at all. Rules only. (The automated tests run this way.) |
| `AI_ASSISTANT_END_AFTER_BOOKING=false` | Switches off ending the conversation after a booking. On (default): goodbye, the confirmation card stays, the session is cleared, and the page resets after `AI_ASSISTANT_END_RESET_SECONDS` (60) or when the patient taps "Start a new conversation". |
| `AI_ASSISTANT_END_AFTER_GOODBYE=false` | Switches off ending the conversation after a goodbye ("no thanks, that's all"). On (default): the same ending as after a booking. |
| `GROQ_PROMPT=current` / `compact` | Which system prompt. `current` is the original and the default. |
| `GROQ_MAX_TOKENS=1000` | Completion cap. 1000 is the known-safe value. |
| `GROQ_REASONING_EFFORT` | Empty (default) sends nothing. |
| `GROQ_RATE_LIMIT_ENABLED=false` | Switches the shared limiter off. Not recommended. |
| `GROQ_TOKENS_PER_MINUTE`, `GROQ_REQUESTS_PER_DAY`, `GROQ_RATE_SAFETY_MARGIN` | The working limits (8000 / 1000 / 0.1). The limiter replaces them with what Groq's response headers report, using the lower value. |

## Rate limiting

`GroqRateLimiter` keeps one budget for the whole application, in the Laravel cache. HTTP 429 is never retried; `Retry-After` is respected and pauses every session. If the cache cannot be read or locked, the call is **refused** (the assistant then runs on its rules), never assumed to be free.

**The default cache store is `file`, which is correct for ONE application server only.** With several servers each has its own files and each would spend the whole Groq budget. Before running more than one server, use a shared store (Redis, Memcached or the database) and set `GROQ_RATE_CACHE_STORE` to it.

## German wording

The German consent and filler word lists in `ConsentRules` (the `GERMAN_*` constants) and the German messages in `Translator` are a first draft. **They must be reviewed by a native German speaker or the practice before production.**

## Tests

Run any suite with `php tests/ai-assistant/<name>_test.php`. The live-tenant suites need the dedicated test patients: `php tests/ai-assistant/fixtures/seed_test_patients.php` (it refuses to run unless the environment is `test`/`local` with a local database; `--status` changes nothing, `--remove` deletes only those rows). `tests/ai-assistant/tools/prompt_compare.php` is a live comparison tool and is never run by the suites.
