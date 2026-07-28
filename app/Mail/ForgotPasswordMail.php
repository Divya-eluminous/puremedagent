<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $data;

    public function __construct($data,$view)
    {
        $this->data = $data;
        $this->view = $view;
        // dd('build',$this->data);
    }

    public function build()
    {      
        if($this->view=='admin')
        {
            $viewName='admin.mail.forgot-password-email';
        }
        /*else if($this->view=='web')
        {
            $viewName='web.forgot.forgot-password-email';
        }*/
        $this->replyTo(config('constants.ADMINEMAIL'),config('constants.ADMINFROMNAME'));
       

        return $this->view($viewName, ['user' => $this->data]);
    }
}