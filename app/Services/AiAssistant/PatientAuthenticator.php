<?php

namespace App\Services\AiAssistant;

use App\Models\PatientsModel;
use App\Models\SettingsModel;
use Illuminate\Support\Facades\Log;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Resolves the patient id and the JWT the protected PureMed APIs require.
 *
 * The existing /api/v3/register endpoint returns only demographic fields - no
 * patient id and no token (see Api\v3\AuthController::registerPatient). But
 * get-doctors, get-appointment-types, get-doctor-slots and book-newtest all sit
 * behind jwt.verify, and book-newtest additionally needs a patient_id.
 *
 * PatientsModel already implements JWTSubject and the app already authenticates
 * patients through the `api` JWT guard, so we simply mint a token for the
 * patient PureMed just created. No parallel auth system, no OTP detour.
 */
class PatientAuthenticator
{
    /**
     * Find the patient PureMed stored for this registration and issue a token.
     *
     * Works both for a patient that was just created and for one that already
     * existed (register answers ERR_PATIENT_UNIQUE for a repeat mobile +
     * birth date), so a returning patient is not a dead end.
     *
     * @param  string  $mobileNo   as typed by the patient
     * @param  string  $birthDate  Y-m-d
     * @return array{patient_id: int, token: string}|null
     */
    public function authenticate(string $mobileNo, string $birthDate): ?array
    {
        try {
            $patient = PatientsModel::where('mobile_no', $this->normalizeMobile($mobileNo))
                ->whereDate('birth_date', $birthDate)
                ->orderByDesc('id')
                ->first();

            if (!$patient) {
                Log::warning('AI assistant: no patient row found after registration', [
                    'mobile_no' => $this->normalizeMobile($mobileNo),
                    'birth_date' => $birthDate,
                ]);

                return null;
            }

            // Mirrors AuthController::generateAuthTokent so assistant sessions
            // expire on the same schedule as app/web logins.
            config()->set('jwt.ttl', $this->tokenTtlMinutes());

            $token = JWTAuth::fromUser($patient);

            $patient->api_access_token = $token;
            $patient->save();

            return [
                'patient_id' => (int) $patient->id,
                'token' => $token,
                // The record PureMed already holds is the source of truth - the
                // confirmation mail goes to this address, not to anything the
                // conversation collected.
                'email' => $patient->email,
                'first_name' => $patient->first_name,
                'family_name' => $patient->family_name,
                'gender' => $patient->gender,
            ];
        } catch (Throwable $exception) {
            Log::error('AI assistant: could not issue a patient token', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * PureMed stores mobile numbers stripped of spaces and leading zeros
     * (Api\v3\AuthController::registerPatient / _storeOrUpdate).
     */
    public function normalizeMobile(string $mobileNo): string
    {
        return ltrim(str_replace(' ', '', $mobileNo), '0');
    }

    private function tokenTtlMinutes(): int
    {
        $setting = SettingsModel::where('setting_key', 'APP_LOGGED_MINS')
            ->whereStatus(1)
            ->first(['setting_value']);

        if (!empty($setting) && (int) $setting->setting_value > 0) {
            return (int) $setting->setting_value;
        }

        return 60 * 24; // same default the existing auth flow uses
    }
}
