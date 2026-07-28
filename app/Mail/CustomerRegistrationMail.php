<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomerRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {      
        $viewName='admin.mail.email-registration';
        $subject = 'Welcome to '.config('constants.SITENAME'); 
        // dd($subject);
        $this->subject($subject);
        $this->replyTo(config('constants.ADMINEMAIL'),config('constants.ADMINFROMNAME'));
        return $this->view($viewName, ['user' => $this->data]);
    }
}