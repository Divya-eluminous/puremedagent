<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OtpWebAppointment extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $data;

    public function __construct($data,$ordination="Puremed")
    {
        $this->data = $data;
        $this->ordination = $ordination;
    }

    public function build()
    {      
       
        $viewName='web.mail.web-otp-appointment';
       // $subject = 'Web-Terminbestätigung OTP '.$this->ordination;  //commented on 22-may-24
        $subject = 'Code fur Web-Terminbestätigung bei '.$this->ordination;//added on 22-may-24
         
        $this->subject($subject);
        $this->from('app@puremed.biz',$this->ordination);        
        $this->replyTo(config('constants.ADMINEMAIL'),config('constants.ADMINFROMNAME'));       
        return $this->view($viewName, ['details' => $this->data,'ordination'=>$this->ordination]);
    }
}