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
    | How many days ahead of today the assistant asks get-doctor-slots for.
    |
    */
    'slot_window_days' => (int) env('PUREMED_SLOT_WINDOW_DAYS', 30),

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
    'default_country' => env('PUREMED_DEFAULT_COUNTRY', 'Austria'),
    'default_postal_code' => env('PUREMED_DEFAULT_POSTAL_CODE', '1010'),
    'login_type' => env('PUREMED_LOGIN_TYPE', 'app'),

    /*
    |--------------------------------------------------------------------------
    | Groq natural language understanding (optional)
    |--------------------------------------------------------------------------
    |
    | Used only to interpret what a patient meant on selection steps, and only
    | after the deterministic matcher has already failed. PureMed remains the
    | source of truth for doctors, appointment types, availability and booking.
    |
    | The API key is read here and used server side only - it is never passed
    | to a view, to JavaScript or into any API response.
    |
    | There is deliberately NO default model. Structured outputs with a strict
    | json_schema are only supported by some Groq models (openai/gpt-oss-20b and
    | openai/gpt-oss-120b at the time of writing), so a guessed name would fail
    | at request time. With GROQ_MODEL or GROQ_API_KEY unset the service stays
    | disabled and the assistant runs on its existing deterministic matching.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Which NLU backend to use
    |--------------------------------------------------------------------------
    |
    | 'ollama' runs a model locally with no API key and no data leaving this
    | machine. 'groq' uses the hosted API. Anything else disables NLU entirely,
    | and the assistant runs on its deterministic matching alone.
    |
    */
    'nlu_driver' => env('NLU_DRIVER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | Ollama (local)
    |--------------------------------------------------------------------------
    |
    | Local development default. No credentials by design - Ollama listens on
    | the loopback interface of this machine. The timeout is generous because a
    | cold model load can take considerably longer than a warm request.
    |
    */
    'ollama' => [
        'enabled' => (bool) env('AI_ASSISTANT_NLU_ENABLED', true),
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:3b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 30),
        'min_confidence' => (float) env('AI_ASSISTANT_NLU_MIN_CONFIDENCE', 0.7),
        'max_tokens' => (int) env('OLLAMA_MAX_TOKENS', 200),
    ],

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
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 1000),
    ],
];
