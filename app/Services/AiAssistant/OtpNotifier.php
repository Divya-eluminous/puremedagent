<?php

namespace App\Services\AiAssistant;

use App\Mail\OtpWebAppointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_Client;
use WebSmsCom_TextMessage;

/**
 * Delivers a verification code, by SMS and by email at the same time.
 *
 * The practice already sends SMS through websms.com and OTP emails through
 * OtpWebAppointment, so this uses both - the same gateway, the same token, the
 * same email the web booking flow sends. What it deliberately does NOT do is
 * call AppointmentWebController::_sendSms(): the assistant is meant to be
 * portable into the main project as a self-contained set of files, and a
 * dependency on a method inside a web controller would break the first time
 * anyone refactored it, at run time, when a code failed to send.
 *
 * The one thing borrowed from the host application is the OtpWebAppointment
 * mailable, so a patient gets the same email whichever way they book. If that
 * class is ever removed, email delivery here fails loudly rather than sending
 * something different.
 *
 * Both channels are attempted for every code. A patient whose SMS does not
 * arrive - poor signal, a mistyped number - can read the same code in their
 * email without asking for anything.
 */
class OtpNotifier
{
    /**
     * Send one code to both channels.
     *
     * Neither failure stops the other being tried, and the caller is told
     * exactly which arrived so the patient can be pointed at the right place.
     * Saying "check your email" when the email bounced is worse than silence.
     *
     * @return array{sms: bool, email: bool}
     */
    public function send(string $code, string $mobile, string $countryCode, ?string $email, string $patientName = ''): array
    {
        return [
            'sms' => $this->sendSms($code, $mobile, $countryCode),
            'email' => $email ? $this->sendEmail($code, $email, $patientName) : false,
        ];
    }

    /**
     * The practice's own gateway, reached the same way the rest of the app
     * reaches it - the client library and the credentials in config, not the
     * controller method that happens to wrap them.
     */
    private function sendSms(string $code, string $mobile, string $countryCode): bool
    {
        $phone = $this->dialable($mobile, $countryCode);

        if ($phone === '') {
            return false;
        }

        try {
            $client = new WebSmsCom_Client(
                (string) config('constants.SMS_TOKEN'),
                '',
                (string) config('constants.SMS_URL'),
                WebSmsCom_AuthenticationMode::ACCESS_TOKEN
            );
            $client->setVerbose(false);
            $client->setSslVerifyHost(2);

            $message = new WebSmsCom_TextMessage(
                [$phone],
                'Ihr Code lautet ' . $code . '. Er ist ' . $this->ttl() . ' Minuten gültig.'
            );

            // false: send for real rather than only exercising the interface.
            $client->send($message, 1, false);

            $this->note('sms', $mobile, true);

            return true;
        } catch (Throwable $e) {
            // The gateway throws a different exception for each failure -
            // credentials, parameters, connection. None of them should stop the
            // email being tried, and none of them may carry the code into a log.
            $this->note('sms', $mobile, false, $e->getMessage());

            return false;
        }
    }

    /**
     * The same email the web booking flow sends, wrapped in a try/catch that
     * flow does not have - there, a mail server that is down still tells the
     * patient a code is on its way.
     */
    private function sendEmail(string $code, string $email, string $patientName): bool
    {
        try {
            $ordination = config('ordination_name') ? ucfirst((string) config('ordination_name')) : 'Puremed';

            Mail::to($email)->send(new OtpWebAppointment([
                'otp_code' => $code,
                'email' => $email,
                'patientName' => $patientName,
            ], $ordination));

            $this->note('email', $email, true);

            return true;
        } catch (Throwable $e) {
            $this->note('email', $email, false, $e->getMessage());

            return false;
        }
    }

    /**
     * The number in the form the gateway wants: country code, then digits.
     *
     * Mirrors what the practice already does elsewhere - a leading 00 dropped,
     * Austria assumed when nothing is set.
     */
    private function dialable(string $mobile, string $countryCode): string
    {
        $digits = preg_replace('/\D+/', '', $mobile);

        if ($digits === '') {
            return '';
        }

        $code = preg_replace('/\D+/', '', $countryCode);
        $code = preg_replace('/^00/', '', (string) $code);

        if ($code === '' || $code === '0') {
            $code = '43';   // Austria, as the rest of the application assumes
        }

        return $code . ltrim($digits, '0');
    }

    private function ttl(): int
    {
        return (int) config('ai-assistant.otp_ttl_minutes', 5);
    }

    /**
     * What was attempted and whether it worked - never the code itself, and
     * never the whole address or number.
     */
    private function note(string $channel, string $destination, bool $ok, string $why = ''): void
    {
        Log::info('AI assistant: verification code ' . ($ok ? 'sent' : 'failed'), array_filter([
            'channel' => $channel,
            'to' => $this->mask($destination),
            'reason' => $ok ? null : mb_substr($why, 0, 120),
        ]));
    }

    /** j***@gmail.com, or 7****421 - enough to trace, not enough to identify. */
    private function mask(string $value): string
    {
        if (str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);

            return mb_substr($local, 0, 1) . '***@' . $domain;
        }

        return mb_strlen($value) > 4
            ? mb_substr($value, 0, 1) . str_repeat('*', mb_strlen($value) - 4) . mb_substr($value, -3)
            : '***';
    }
}
