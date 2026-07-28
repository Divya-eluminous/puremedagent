<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AppointmentReminderMail extends Mailable 
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $data;

    public function __construct($name,$data,$from_name="PUREGYN")  
    {

        $this->data = $data;
        $this->name = $name;
        $this->from_name = $from_name;
    }

    public function build() 
    {      
       
        $viewName='admin.mail.appointment-reminder-email';
        $subject = 'Erinnerung an Ihren Termin '.$this->from_name;
        $this->from('app@puremed.biz',$this->from_name);
        $this->subject($subject);

        //return $this->view($viewName, ['details' => $this->data,'name' => $this->name]);//commented on 28-jan-25

        //added on 28-jan-25
        return $this->view($viewName, ['details' => $this->data,'name' => $this->name,'from_name' => $this->from_name]);
        
    }
}