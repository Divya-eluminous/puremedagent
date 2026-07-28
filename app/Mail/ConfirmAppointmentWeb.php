<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ConfirmAppointmentWeb extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $data;

    public function __construct($data,$view,$ordination="Puremed") //added ordination on 8-may-24
    {
        $this->data = $data;
        $this->view = $view;
        $this->ordination = $ordination;
    }

    public function build()
    {      
        if($this->view=='web')
        {
            $viewName='web.mail.confirm-web-appointment';
            // $subject = 'Ihre Terminanfrage bei PureGyn';
            // $subject = 'Ihre Terminanfrage bei '.$this->ordination;  //commented on 22-may-24
            $subject = 'Ihre Terminbuchung bei '.$this->ordination;  //changed on 22-may-24
            $this->subject($subject);
            $this->from('app@puremed.biz',$this->ordination); //added on 8-may-24
        }
        /*else if($this->view=='web')
        {
            $viewName='web.forgot.forgot-password-email';
        }*/
        $this->replyTo(config('constants.ADMINEMAIL'),config('constants.ADMINFROMNAME'));
       
        return $this->view($viewName, ['user' => $this->data,'ordination'=>$this->ordination]);
    }
}