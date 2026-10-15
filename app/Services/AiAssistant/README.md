# PureMed AI assistant - operations notes

How free text is understood, what can be switched off, and what must change before this runs on more than one server.

## How a message is handled (English and German alike)

1. A button tap never goes to the AI. Registration steps (mobile, date of birth, email, name, gender, the code) never go to the AI.
2. Messages a deterministic rule owns are answered by the rules without the AI: a plain yes/no at the booking or cancellation question, "start over", "show me more", "are there other doctors", "no thanks, that's all", "past appointments", a follow-up about the appointment list ("what about the second one"), and a bare number at the time question ("11").
3. Everything else on an AI-eligible step goes to Groq. Its answer is checked by `NluManager::guard()`, turned into a plain phrase, and run through the same rule matchers as typed text. The AI never touches PureMed.
4. If the AI has nothing usable, or fails, the rules run on what the patient actually said.

**Consent is never the AI's call.** `book()` and `cancelAppointment()` refuse to act unless `consents()` recorded a consent for that very answer. A free-text answer is consent only when `ConsentRules` finds it to be a short run of agreeing/filler words in the patient's language. A chip counts only on the confirmation question it belongs to, with the booking or cancellation actually pending.

## Verifying who is asking

Everyone reads a four digit code back before the assistant shows or does anything of theirs.

- **A new patient** is sent a code to the mobile number and email they gave. They are not created until it is read back.
- **A returning patient** (mobile number and date of birth match a record) is sent a code to the mobile number and email **on the practice's record**, never to anything said in the conversation, using that record's own country code. The text message is required: if it does not go out, nobody gets in. With no email on file the text alone is used. An inactive patient is refused.
- Until the code is read back nothing is revealed (no name, no email, no appointments) and **nothing is written to the patient's record**: the lookup is read-only, and the login token is only created after the code is right.
- A verified session carries a signature (`auth_proof`) made with the application key over the patient, the mobile number, the date of birth and the session itself. It is checked every time the session is loaded. A session that names a patient without a valid signature is dropped, so setting a patient id, copying a state from another session, or changing the number or date of birth after verification does not work. A patient is not asked again during the same conversation; a new session, a restart or "New patient" starts from the top.

### Limits that no chat session can reset (`OtpGuard`)

Kept per **mobile number** (digits only, leading zeros dropped, so every way of typing it is one number), in the shared cache, and updated under a lock:

| Limit | Value | Setting |
|---|---|---|
| Codes sent per number in any rolling hour | 3 | `AI_ASSISTANT_OTP_SENDS_PER_HOUR` |
| Wrong codes before the number is locked | 5 | `AI_ASSISTANT_OTP_ATTEMPTS` |
| Lockout | 15 minutes | `AI_ASSISTANT_OTP_LOCKOUT_MINUTES` |

A wrong attempt is counted **before** the code is compared, so simultaneous guesses are counted as they arrive and the sixth onwards is never checked. While locked, the number can neither be sent a code nor verified, even with the right code. A send that was never delivered is not counted. If the cache cannot be read or locked, the request is refused (fail closed). The same limits apply to new-patient codes.

The file cache is correct for **one application server only**. With several servers each keeps its own counters and would allow the full number of guesses: use a shared store (Redis, Memcached or the database) and set `OTP_GUARD_CACHE_STORE` to it.

### Known limitations (for future review)

- **A returning patient can be told apart from a new one.** When a mobile number and date of birth match, the person hears that a code was sent; when they don't, they are set up as a new patient. That shows whether someone is a patient to anyone who already knows both details. The rate limits above bound how much this can be probed, but it is not removed. Hiding it would mean changing registration so a new patient also goes through a code first.
- **A new patient's code goes to the mobile number and email they typed.** That is inherent in registering someone the practice has never seen; nothing about an existing patient is exposed by it.
- **Locking a number can be done by someone who knows a patient's mobile number and date of birth** (they can request codes and enter wrong ones). The lockout expires after 15 minutes and does not touch the patient's record.

## Switches (all in `.env`; run `php artisan config:clear` after changing one)

| Setting | Effect |
|---|---|
| `AI_ASSISTANT_AI_FIRST=false` | **Rollback.** English returns to rules-first (the AI is asked only when the rules fail). German keeps its behaviour: AI first. |
| `AI_ASSISTANT_VERIFY_EXISTING=false` | **Rollback for returning patients.** Back to recognising them by mobile number and date of birth alone, with no code and no signature check. |
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

## Choosing the AI provider (`NLU_DRIVER` and `NLU_FALLBACK`)

Either Groq or Claude (Anthropic, `claude-haiku-5-5`) can be the primary provider, and the other can be an optional fallback. Switching is `.env` only, then `php artisan config:clear`. No code change.

| `NLU_DRIVER` | `NLU_FALLBACK` | Behaviour | Needs in `.env` |
|---|---|---|---|
| `groq` (**current**) | `none` (**current**) | Groq only. Anthropic is never contacted. | `GROQ_API_KEY`, `GROQ_MODEL` |
| `groq` | `claude` | Groq first; Claude only when Groq fails. | the above + `ANTHROPIC_API_KEY` |
| `claude` | `none` | Claude only. Groq is never contacted. | `ANTHROPIC_API_KEY` |
| `claude` | `groq` | Claude first; Groq only when Claude fails. | both keys |
| `none` | any | No AI at all (rules and buttons only). | nothing |

- **Mistakes are safe.** An invalid `NLU_DRIVER` (anything but groq, claude, none) switches ALL AI off. An invalid `NLU_FALLBACK`, or one equal to the primary, disables only the fallback; a valid primary keeps working. Each mistake logs one warning an hour (the setting name and the value cut short, never a secret or a patient's message). A provider that is neither the primary nor the fallback is never contacted, and a selected provider without its key is treated as unavailable.
- **What asks the fallback:** only a failure of the primary - timeout, rate limit (including this application's own budget), outage or network failure, or an answer that was malformed, cut short or refused. A message the primary read but could not use ("unknown", low confidence, an answer the guard rejected) is not a failure: it goes straight to the rule-based flow with no second model.
- **Same safeguards for both providers:** the same prompt contract and response validation (`NluContract`), the same `NluManager::guard()`, the same deterministic `ConsentRules`, OTP verification, booking and cancellation rules. Neither provider can book, cancel or consent. Registration and verification steps never reach either provider.
- **Separate budgets:** each provider has its own limiter (`GROQ_*`, `CLAUDE_*`). A 429 is never retried; `Retry-After` and the provider's rate-limit headers are respected. Anthropic's monthly spend cap (429, `enforced_spend_limit_reached`) stands Claude down for `CLAUDE_SPEND_CAP_BACKOFF` seconds (3600). A limiter that cannot be read refuses the call. When both providers are limited the shorter wait is reported ("very busy").
- **Claude settings:** `CLAUDE_MODEL`, `CLAUDE_TIMEOUT` (6), `CLAUDE_CONNECT_RETRIES` (0), `CLAUDE_MAX_TOKENS` (400), `CLAUDE_THINKING` (disabled), `CLAUDE_EFFORT` (low), `CLAUDE_MIN_CONFIDENCE` (0.6), `CLAUDE_TOKENS_PER_MINUTE` (200000), `CLAUDE_REQUESTS_PER_DAY` (0 = none), `CLAUDE_RATE_SAFETY_MARGIN`, `CLAUDE_DEFAULT_BACKOFF`, `CLAUDE_MAX_BACKOFF`. `NLU_FALLBACK_ON` lists which failures may ask the fallback (`timeout,rate_limited,unavailable,malformed`, default all). The key is read from `ANTHROPIC_API_KEY` only; it is never in code, git or logs.
- **The cache store must be shared (Redis) when more than one server runs**, as for Groq.

**Privacy - using Claude at all, and especially as the primary.** Per call, Anthropic receives: the step name, the labels of the options on the patient's screen, the choices made so far (labels), the assistant's last line, and the patient's one message, each cut short. No identifiers, tokens, mobile numbers, dates of birth, email addresses or codes. But the patient's free text can contain whatever they type, so it is personal data. As the **fallback**, only the messages Groq failed on go to Anthropic. As the **primary**, EVERY AI-handled message does, so the practice's data-sharing approval must cover all traffic. To confirm with the practice before enabling: their data-sharing policy, Anthropic's API retention terms (and any zero-data-retention arrangement), and whether a data-processing agreement is needed.

**Cost** (an estimate to confirm from real `usage`): about 1,500 input + 100 output tokens per call, about $0.0002 at $0.10 / $0.50 per million tokens. As the fallback it is paid only when the primary fails; as the primary, on every AI-handled message.

**Rollback:** set `NLU_DRIVER=groq` and `NLU_FALLBACK=none` (or remove `ANTHROPIC_API_KEY`), then `php artisan config:clear`. To stop all AI: `NLU_DRIVER=none`.

## German wording

The German consent and filler word lists in `ConsentRules` (the `GERMAN_*` constants) and the German messages in `Translator` are a first draft. **They must be reviewed by a native German speaker or the practice before production.**

## Tests

Run any suite with `php tests/ai-assistant/<name>_test.php`. The live-tenant suites need the dedicated test patients: `php tests/ai-assistant/fixtures/seed_test_patients.php` (it refuses to run unless the environment is `test`/`local` with a local database; `--status` changes nothing, `--remove` deletes only those rows). `tests/ai-assistant/tools/prompt_compare.php` is a live comparison tool and is never run by the suites.
