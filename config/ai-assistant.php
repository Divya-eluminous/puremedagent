<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PureMed API base URL
    |--------------------------------------------------------------------------
    |
    | Leave this empty so the assistant calls the PureMed API on the SAME host
    | the patient is currently browsing. EnforceTenancy resolves the tenant
    | database from the request host, so a hardcoded host would make the
    | assistant talk to a different tenant than the one the patient is on.
    | Only set PUREMED_BASE_URL if the API really lives on another host.
    |
    */
    'puremed_base_url' => env('PUREMED_BASE_URL'),

    'register_endpoint' => env('PUREMED_REGISTER_ENDPOINT', '/api/v3/register'),
    'doctors_endpoint' => env('PUREMED_DOCTORS_ENDPOINT', '/api/v3/get-doctors'),
    'appointment_types_endpoint' => env('PUREMED_APPOINTMENT_TYPES_ENDPOINT', '/api/v3/get-appointment-types'),
    // The practice's own booking window: quarter rules, booking timeframe and
    // the appointment type's optimal_appointment flag all decide what this
    // returns. The assistant asks for it rather than assuming a window, so it
    // offers the same dates and hours the main app does.
    'from_date_endpoint' => env('PUREMED_FROM_DATE_ENDPOINT', '/api/v3/get-from-date'),
    'doctor_slots_endpoint' => env('PUREMED_DOCTOR_SLOTS_ENDPOINT', '/api/v3/get-doctor-slots'),
    'booking_endpoint' => env('PUREMED_BOOKING_ENDPOINT', '/api/v3/appointment/book-newtest'),
    'appointments_endpoint' => env('PUREMED_APPOINTMENTS_ENDPOINT', '/api/v3/get-appointment'),
    'cancel_endpoint' => env('PUREMED_CANCEL_ENDPOINT', '/api/v3/appointment/cancel-new'),
    'history_endpoint' => env('PUREMED_HISTORY_ENDPOINT', '/api/v3/appointment/history'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('PUREMED_API_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Slot search window
    |--------------------------------------------------------------------------
    |
    | Only used when get-from-date cannot tell us the practice's own window -
    | the assistant asks for that first and prefers it. These are the fallback
    | so a failed call still shows availability rather than none, and they match
    | the hours the main app uses.
    |
    */
    'slot_window_days' => (int) env('PUREMED_SLOT_WINDOW_DAYS', 30),
    'default_from_time' => env('PUREMED_DEFAULT_FROM_TIME', '06:00'),
    'default_to_time' => env('PUREMED_DEFAULT_TO_TIME', '21:00'),

    /*
    |--------------------------------------------------------------------------
    | Quick reply chips shown per question
    |--------------------------------------------------------------------------
    |
    | A conversation offers a few obvious choices, not a scrollable list of a
    | hundred. Anything beyond this sits behind "Show more".
    |
    */
    'chips_per_page' => (int) env('PUREMED_CHIPS_PER_PAGE', 6),

    /*
    |--------------------------------------------------------------------------
    | Registration defaults
    |--------------------------------------------------------------------------
    |
    | The register API requires these fields but the conversation does not ask
    | for them in Phase 1. Country must be Austria, Germany or Switzerland.
    |
    | login_type must be a value of the patients.login_type ENUM('app','google').
    | Anything else is silently stored as '' by MySQL in non-strict mode.
    |
    */
    'default_country_code' => env('PUREMED_DEFAULT_COUNTRY_CODE', '0043'),

    /*
    |--------------------------------------------------------------------------
    | Verifying a new patient
    |--------------------------------------------------------------------------
    |
    | A code is sent before a patient the practice has never seen is created.
    | Five minutes matches what the app's own OTP allows. The limits are the
    | assistant's own: a four digit code with unlimited guesses is not a check
    | at all, and an unattended tab must not be able to spend the practice's
    | SMS credit.
    |
    */
    'otp_ttl_minutes' => (int) env('AI_ASSISTANT_OTP_TTL', 5),
    'otp_max_attempts' => (int) env('AI_ASSISTANT_OTP_ATTEMPTS', 3),
    'otp_max_resends' => (int) env('AI_ASSISTANT_OTP_RESENDS', 2),
    'default_country' => env('PUREMED_DEFAULT_COUNTRY', 'Austria'),
    'default_postal_code' => env('PUREMED_DEFAULT_POSTAL_CODE', '1010'),
    'login_type' => env('PUREMED_LOGIN_TYPE', 'app'),

    /*
    |--------------------------------------------------------------------------
    | Groq natural language understanding
    |--------------------------------------------------------------------------
    |
    | Groq is the assistant's AI backend. It interprets what a patient meant -
    | in English or German - on selection steps. PureMed remains the source of
    | truth for doctors, appointment types, availability and booking, and every
    | answer the model gives is checked against the real options first.
    |
    | The API key is read here and used server side only - it is never passed
    | to a view, to JavaScript or into any API response.
    |
    | There is deliberately NO default model. Structured outputs with a strict
    | json_schema are only supported by some Groq models (openai/gpt-oss-20b and
    | openai/gpt-oss-120b at the time of writing), so a guessed name would fail
    | at request time. With GROQ_MODEL or GROQ_API_KEY unset the assistant tells
    | the patient the AI is temporarily unavailable; buttons keep working.
    |
    | NLU_DRIVER is 'groq' by default. Any other value switches the AI off
    | entirely (used by the automated tests, which run on the rules alone).
    |
    */
    'nlu_driver' => env('NLU_DRIVER', 'groq'),

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | The patient chooses English or German on the page. The choice is sent with
    | each message and drives the replies, the speech recognition and the voice,
    | and the language of the verification code messages.
    |
    */
    'languages' => ['en', 'de'],
    'default_language' => env('AI_ASSISTANT_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | AI-first processing (feature flag)
    |--------------------------------------------------------------------------
    |
    | true:  English and German free text on the AI-eligible steps both go to the
    |        AI first (after the deterministic shortcuts), with the rules as the
    |        fallback.
    | false: ROLLBACK. English returns to its original rules-first flow (the AI
    |        is asked only when the rules could not move the conversation on),
    |        and German keeps the behaviour it has always had here: AI first.
    |
    | Set AI_ASSISTANT_AI_FIRST=false in .env and run `php artisan config:clear`.
    | NLU_DRIVER=none is the separate, bigger switch: no AI at all.
    |
    */
    'ai_first' => (bool) env('AI_ASSISTANT_AI_FIRST', true),

    /*
    |--------------------------------------------------------------------------
    | End the conversation after a booking
    |--------------------------------------------------------------------------
    |
    | true:  once an appointment is confirmed the assistant says goodbye, the
    |        confirmation card stays on screen, and everything the session held
    |        about the patient is cleared. The page resets to the start screen
    |        after end_reset_seconds, or sooner if the patient taps the button.
    | false: the old behaviour - "anything else?", and the patient stays
    |        identified for the next request (cancel, list, book again).
    |
    | A shared screen is the reason: the next person must not inherit the
    | previous patient's session.
    |
    */
    'end_after_booking' => (bool) env('AI_ASSISTANT_END_AFTER_BOOKING', true),
    'end_reset_seconds' => (int) env('AI_ASSISTANT_END_RESET_SECONDS', 60),
    // The same ending after a goodbye ("no thanks, that's all"), whatever came before it.
    'end_after_goodbye' => (bool) env('AI_ASSISTANT_END_AFTER_GOODBYE', true),

    'groq' => [
        'enabled' => (bool) env('AI_ASSISTANT_NLU_ENABLED', true),
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL'),
        'endpoint' => env('GROQ_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions'),
        'timeout' => (int) env('GROQ_TIMEOUT', 6),
        'min_confidence' => (float) env('GROQ_MIN_CONFIDENCE', 0.6),
        // Reasoning models need headroom before they emit the JSON document.
        // 600 still truncated occasionally in testing (json_validate_failed),
        // so the ceiling is 1000. The reply itself is a few dozen tokens - this
        // is headroom for the model's reasoning, not typical spend.
        // A lower value (about 400) is ONLY to be adopted once the comparison
        // in tests/ai-assistant/tools/prompt_compare.php has shown no extra
        // truncation. 1000 is the known-safe setting and the rollback value.
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 1000),

        // 'current' is the original system prompt and the rollback. 'compact' is
        // the shorter candidate; it is not the default until the comparison
        // says it is as accurate and as safe.
        'prompt' => env('GROQ_PROMPT', 'current'),
        // Optional. gpt-oss models accept low|medium|high; empty sends nothing,
        // which is the behaviour the assistant has always had.
        'reasoning_effort' => env('GROQ_REASONING_EFFORT'),

        /*
        | Shared rate limit
        |
        | These are the limits MEASURED on the key in use (response headers:
        | x-ratelimit-limit-tokens 8000, x-ratelimit-limit-requests 1000/day).
        | They are only the starting point: the limiter replaces them with what
        | the response headers say, and takes the lower of the two. The budget is
        | one for the whole application - every patient session draws on it.
        */
        'rate_limit' => [
            'enabled' => (bool) env('GROQ_RATE_LIMIT_ENABLED', true),
            'tokens_per_minute' => (int) env('GROQ_TOKENS_PER_MINUTE', 8000),
            'requests_per_day' => (int) env('GROQ_REQUESTS_PER_DAY', 1000),
            // Keep this fraction of the budget unspent, so a burst the limiter
            // could not foresee does not tip over the line.
            'safety_margin' => (float) env('GROQ_RATE_SAFETY_MARGIN', 0.1),
            // A rough size for the prompt, used until a real one is measured.
            'estimated_prompt_tokens' => (int) env('GROQ_ESTIMATED_PROMPT_TOKENS', 1300),
            // And a typical reply (measured 138 to 305 tokens), used to reserve budget.
            'estimated_completion_tokens' => (int) env('GROQ_ESTIMATED_COMPLETION_TOKENS', 350),
            // How long to wait when Groq refuses without saying for how long.
            'default_backoff_seconds' => (int) env('GROQ_DEFAULT_BACKOFF', 30),
            // Never trust a Retry-After longer than this (seconds).
            'max_backoff_seconds' => (int) env('GROQ_MAX_BACKOFF', 300),
            'cache_store' => env('GROQ_RATE_CACHE_STORE'),
        ],
    ],
];
