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
];
