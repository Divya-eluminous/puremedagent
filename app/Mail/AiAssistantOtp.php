<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The verification code email sent by the AI assistant, in English or German.
 *
 * The practice's own OtpWebAppointment mail is German only, so the assistant
 * has its own that follows the language the patient is using. The German text
 * is the same wording that mail uses.
 */
class AiAssistantOtp extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private array $details,
        private string $ordination = 'Puremed',
        private string $language = 'en'
    ) {
    }

    public function build()
    {
        $german = $this->language === 'de';

        $this->subject($german
            ? 'Code für Web-Terminbestätigung bei ' . $this->ordination
            : 'Your verification code for ' . $this->ordination);
        $this->from('app@puremed.biz', $this->ordination);
        $this->replyTo(config('constants.ADMINEMAIL'), config('constants.ADMINFROMNAME'));

        return $this->view('ai-assistant.mail.otp', [
            'details' => $this->details,
            'ordination' => $this->ordination,
            'german' => $german,
        ]);
    }
}
