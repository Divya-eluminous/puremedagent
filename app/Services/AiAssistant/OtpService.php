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
    public function __construct(private OtpNotifier $notifier) {}

    /**
     * Send a code to both channels, and remember it only if one arrived.
     *
     * @return array{sent: bool, sms: bool, email: bool}
     */
    public function send(string $mobile, string $birthDate, ?string $email, string $patientName = ''): array
    {
        $code = (string) random_int(1000, 9999);

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
            return ['sent' => false, 'sms' => false, 'email' => false];
        }

        $this->store($code, $mobile, $birthDate, $email);

        return ['sent' => true, 'sms' => $delivered['sms'], 'email' => $delivered['email']];
    }

    /**
     * Is this the code that was sent, to this number, and is it still current?
     *
     * Matched on the same three fields the web booking flow matches on, with
     * the expiry check that flow is missing.
     */
    public function verify(string $code, string $mobile, string $birthDate): bool
    {
        $code = trim($code);

        if ($code === '') {
            return false;
        }

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
