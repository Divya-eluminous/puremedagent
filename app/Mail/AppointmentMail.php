<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AppointmentMail extends Mailable 
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $data;

    public function __construct($name,$data,$from_name="Puremed")  
    {
        $this->data = $data;
        $this->name = $name;
        $this->from_name = $from_name;
    }

    public function build() 
    {      
        $viewName='admin.mail.appointment-email';
        // $subject = 'Ihre Vorsorgeempfehlung von PureGyn'; //commented on 9-may-24
         $subject = 'Ihre Terminbuchung bei '.$this->from_name;
        // $this->from('app@puremed.biz','PureGyn'); //commented on 9-may-24
        $this->from('app@puremed.biz',$this->from_name);
        $this->subject($subject);

        return $this->view($viewName, ['details' => $this->data,'name' => $this->name]);
    }
}