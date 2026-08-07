<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin HTTP client for the existing PureMed v3 APIs.
 *
 * This class only transports requests. It never re-implements booking,
 * slot or registration logic - PureMed stays the single source of truth.
 *
 * Auth rules (see routes/api_v3.php):
 *   - register                 -> ApiCustomKeyToken  -> APP-TOKEN header
 *   - get-doctors              -> jwt.verify         -> Authorization: Bearer
 *   - get-appointment-types    -> jwt.verify         -> Authorization: Bearer
 *   - get-doctor-slots         -> jwt.verify         -> Authorization: Bearer
 *   - appointment/book-newtest -> jwt.verify         -> Authorization: Bearer
 */
class PureMedApiClient
{
    public function registerPatient(array $payload): array
    {
        return $this->post('register_endpoint', $payload, [
            'APP-TOKEN' => config('constants.APP_TOKEN'),
        ]);
    }

    public function getDoctors(string $token, array $payload = []): array
    {
        return $this->post('doctors_endpoint', $payload, $this->bearer($token));
    }

    public function getAppointmentTypes(string $token, array $payload = []): array
    {
        return $this->post('appointment_types_endpoint', $payload, $this->bearer($token));
    }

    public function getDoctorSlots(string $token, array $payload = []): array
    {
        return $this->post('doctor_slots_endpoint', $payload, $this->bearer($token));
    }

    public function bookAppointment(string $token, array $payload): array
    {
        return $this->post('booking_endpoint', $payload, $this->bearer($token));
    }

    /** Upcoming appointments for a patient. */
    public function getAppointments(string $token, array $payload): array
    {
        return $this->post('appointments_endpoint', $payload, $this->bearer($token));
    }

    public function cancelAppointment(string $token, array $payload): array
    {
        return $this->post('cancel_endpoint', $payload, $this->bearer($token));
    }

    /** Past appointments for a patient. */
    public function getAppointmentHistory(string $token, array $payload): array
    {
        return $this->post('history_endpoint', $payload, $this->bearer($token));
    }

    /**
     * Build the absolute endpoint URL.
     *
     * Defaults to the root of the current request so the API call stays on the
     * same host - and therefore the same tenant database - as the patient.
     */
    public function url(string $endpoint): string
    {
        $baseUrl = config('ai-assistant.puremed_base_url') ?: url('/');

        return rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    private function bearer(string $token): array
    {
        // Some PureMed endpoints hand the token back already prefixed.
        $token = trim($token);
        if (stripos($token, 'bearer ') !== 0) {
            $token = 'Bearer ' . $token;
        }

        return ['Authorization' => $token];
    }

    /**
     * POST to a configured endpoint and normalise the reply.
     *
     * The PureMed APIs answer HTTP 200 even when they fail, so callers must
     * never trust the HTTP status alone - `ok` reflects the body `status`.
     *
     * @return array{ok: bool, message: string, data: mixed, errors: mixed, http_status: int, body: mixed}
     */
    private function post(string $endpointKey, array $payload, array $headers): array
    {
        $url = $this->url((string) config('ai-assistant.' . $endpointKey));

        try {
            $response = Http::withHeaders($headers)
                ->timeout((int) config('ai-assistant.timeout', 30))
                ->asForm()
                ->post($url, $payload);
        } catch (Throwable $exception) {
            Log::error('AI assistant: PureMed API request failed', [
                'endpoint' => $endpointKey,
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return $this->failure(__('api.ERR_SOMETHING_WRONG'), 0);
        }

        $body = $response->json();

        if (!is_array($body)) {
            Log::error('AI assistant: PureMed API returned a non-JSON body', [
                'endpoint' => $endpointKey,
                'url' => $url,
                'http_status' => $response->status(),
            ]);

            return $this->failure(__('api.ERR_SOMETHING_WRONG'), $response->status());
        }

        $result = [
            'ok' => (bool) data_get($body, 'status', false),
            'message' => (string) data_get($body, 'message', ''),
            'data' => data_get($body, 'data', []),
            'errors' => data_get($body, 'errors', []),
            'http_status' => $response->status(),
            'body' => $body,
        ];

        if (!$result['ok']) {
            Log::warning('AI assistant: PureMed API reported a failure', [
                'endpoint' => $endpointKey,
                'message' => $result['message'],
                'errors' => $result['errors'],
            ]);
        }

        return $result;
    }

    private function failure(string $message, int $httpStatus): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'data' => [],
            'errors' => [],
            'http_status' => $httpStatus,
            'body' => null,
        ];
    }
}
