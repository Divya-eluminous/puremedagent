<?php

namespace App\Services\AiAssistant;

use App\Models\PatientsOtpModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The verification code a new patient is asked for before they are registered.
 *
 * Stored in patients_otp, the table the practice already uses to verify a
 * booking from someone who has no patient record yet. That is exactly the case
 * here: the row is keyed on mobile number and date of birth, because at this
 * point in the conversation there is no patient id to key it on - and there
 * must not be, since nothing may be created until the code is confirmed.
 *
 * Three things are added that the web booking flow does not do, because a
 * four digit code guarding a real appointment deserves them:
 *
 *   - the row is written only AFTER a code has actually been sent, so a failed
 *     SMS cannot leave a live code sitting in the table;
 *   - expiry is enforced on verification;
 *   - the row is deleted once used, or once the attempts run out.
 *
 * The code is never returned to the browser and never written to a log.
 */
class OtpService
{
    private ?OtpGuard $guard;

    /**
     * @param  OtpGuard|null  $guard  the shared limits; resolved from the container when not given
     */
    public function __construct(private OtpNotifier $notifier, ?OtpGuard $guard = null)
    {
        $this->guard = $guard;
    }

    private function guard(): OtpGuard
    {
        return $this->guard ??= app(OtpGuard::class);
    }

    /**
     * Send a code to both channels, and remember it only if one arrived.
     *
     * @return array{sent: bool, sms: bool, email: bool}
     */
    public function send(string $mobile, string $birthDate, ?string $email, string $patientName = '', string $language = 'en'): array
    {
        // A number can be sent only so many codes an hour, whichever session asks.
        $grant = $this->guard()->reserveSend($mobile);

        if (!$grant['allowed']) {
            return ['sent' => false, 'sms' => false, 'email' => false, 'blocked' => $grant['reason'], 'retry_after' => $grant['retry_after']];
        }

        $code = (string) random_int(1000, 9999);

        $this->notifier->useLanguage($language);

        $delivered = $this->notifier->send(
            $code,
            $mobile,
            (string) config('ai-assistant.default_country_code', '0043'),
            $email,
            $patientName
        );

        // Nothing arrived, so nothing is remembered. Writing the row anyway is
        // what leaves a usable code behind for a patient who never saw it.
        if (!$delivered['sms'] && !$delivered['email']) {
            // Nothing went out, so it does not count against the hour.
            $this->guard()->releaseSend($mobile, $grant['token']);

            return ['sent' => false, 'sms' => false, 'email' => false];
        }

        $this->store($code, $mobile, $birthDate, $email);

        return ['sent' => true, 'sms' => $delivered['sms'], 'email' => $delivered['email']];
    }

    /**
     * Send a code to a patient the practice already holds.
     *
     * It goes to the mobile number and the email address ON THE PATIENT'S
     * RECORD, never to anything typed in the conversation: someone who knows a
     * mobile number and a date of birth must not be able to point the code at
     * an address of their own. The practice's own country code is used for the
     * text, not the default.
     *
     * The text message is REQUIRED. If it does not go out, no code is stored
     * and the answer is "not sent", even when the email did go - a returning
     * patient is verified through their phone, and the email is a second copy,
     * not a way round it. With no email on file the text alone is enough.
     *
     * @param  string  $typedMobile  the number as the patient gave it - the key the code and the limits use
     * @param  array{patient_id: int, mobile_no: string, country_code: string, email: string, active: bool}  $patient  from PatientAuthenticator::find()
     * @return array{sent: bool, sms: bool, email: bool, blocked?: string, retry_after?: int}
     */
    public function sendToPatient(string $typedMobile, string $birthDate, array $patient, string $language = 'en'): array
    {
        $grant = $this->guard()->reserveSend($typedMobile);

        if (!$grant['allowed']) {
            return ['sent' => false, 'sms' => false, 'email' => false, 'blocked' => $grant['reason'], 'retry_after' => $grant['retry_after']];
        }

        $code = (string) random_int(1000, 9999);
        $email = trim((string) ($patient['email'] ?? ''));
        $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;

        $this->notifier->useLanguage($language);

        $delivered = $this->notifier->send(
            $code,
            (string) $patient['mobile_no'],
            (string) ($patient['country_code'] ?? ''),
            $email,
            ''
        );

        if (!$delivered['sms']) {
            $this->guard()->releaseSend($typedMobile, $grant['token']);

            return ['sent' => false, 'sms' => false, 'email' => (bool) $delivered['email']];
        }

        $this->store($code, $typedMobile, $birthDate, $email);

        return ['sent' => true, 'sms' => true, 'email' => (bool) $delivered['email']];
    }

    /**
     * Is this the code that was sent, to this number, and is it still current?
     *
     * Matched on the same three fields the web booking flow matches on, with
     * the expiry check that flow is missing.
     */
    public function verify(string $code, string $mobile, string $birthDate): bool
    {
        return $this->verifyDetailed($code, $mobile, $birthDate)['ok'];
    }

    /**
     * Is this the code, and may it be tried at all?
     *
     * The attempt is counted BEFORE the code is compared, against the number and
     * across every session. Once the allowed wrong attempts are used the number
     * is locked and even the right code is refused until the lockout ends.
     *
     * @return array{ok: bool, locked: bool, retry_after: int, attempts_left: int, unavailable: bool}
     */
    public function verifyDetailed(string $code, string $mobile, string $birthDate): array
    {
        $none = ['ok' => false, 'locked' => false, 'retry_after' => 0, 'attempts_left' => 0, 'unavailable' => false];
        $code = trim($code);

        if ($code === '') {
            return $none;
        }

        $attempt = $this->guard()->beginAttempt($mobile);

        if (!$attempt['allowed']) {
            // Locked, or the limiter cannot be trusted. Either way: not checked.
            if ($attempt['locked']) {
                $this->forget($mobile, $birthDate);
            }

            return ['ok' => false, 'locked' => $attempt['locked'], 'retry_after' => $attempt['retry_after'],
                'attempts_left' => 0, 'unavailable' => $attempt['unavailable']];
        }

        $correct = $this->codeMatches($code, $mobile, $birthDate);
        $after = $this->guard()->finishAttempt($mobile, $correct);

        if ($after['locked']) {
            // Spent: the code is dropped rather than left to be guessed at.
            $this->forget($mobile, $birthDate);
        }

        return ['ok' => $correct, 'locked' => $after['locked'], 'retry_after' => $after['retry_after'],
            'attempts_left' => $after['attempts_left'], 'unavailable' => false];
    }

    private function codeMatches(string $code, string $mobile, string $birthDate): bool
    {
        try {
            $row = PatientsOtpModel::where('mobile_no', $mobile)
                ->whereDate('birth_date', $birthDate)
                ->where('login_otp', $code)
                ->orderByDesc('id')
                ->first();

            if (!$row) {
                return false;
            }

            if ($this->expired($row)) {
                // An expired code is spent, not merely wrong.
                $this->forget($mobile, $birthDate);

                return false;
            }

            // Used once and gone, so the same digits cannot be replayed.
            $this->forget($mobile, $birthDate);

            return true;
        } catch (Throwable $e) {
            Log::warning('AI assistant: could not verify the code', ['reason' => $e->getMessage()]);

            return false;
        }
    }

    /** Drop whatever is held for this number, used or abandoned. */
    public function forget(string $mobile, string $birthDate): void
    {
        try {
            PatientsOtpModel::where('mobile_no', $mobile)
                ->whereDate('birth_date', $birthDate)
                ->delete();
        } catch (Throwable $e) {
            Log::warning('AI assistant: could not clear the code', ['reason' => $e->getMessage()]);
        }
    }

    /**
     * One row per mobile and date of birth, replaced rather than piled up, so
     * only the newest code is ever valid.
     */
    private function store(string $code, string $mobile, string $birthDate, ?string $email): void
    {
        $this->forget($mobile, $birthDate);

        try {
            $row = new PatientsOtpModel();
            $row->mobile_no = $mobile;
            $row->birth_date = Carbon::parse($birthDate)->format('Y-m-d');
            $row->email = (string) ($email ?? '');
            $row->login_otp = $code;
            $row->otp_created_at = Carbon::now()->format('Y-m-d H:i:s');
            $row->save();
        } catch (Throwable $e) {
            Log::warning('AI assistant: could not store the code', ['reason' => $e->getMessage()]);
        }
    }

    private function expired(PatientsOtpModel $row): bool
    {
        $created = $row->otp_created_at ?? null;

        if (empty($created)) {
            return true;
        }

        return Carbon::parse($created)->diffInMinutes(Carbon::now())
            > (int) config('ai-assistant.otp_ttl_minutes', 5);
    }
}
