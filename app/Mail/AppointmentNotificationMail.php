<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AppointmentNotificationMail extends Mailable 
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $data;

    // public function __construct($name,$data,$from_name="Puremed") 
     public function __construct($name,$data,$serviceName,$from_name="PUREGYN")  
    {
        $this->data = $data;
        $this->name = $name;
        $this->from_name = $from_name;
        $this->serviceName = $serviceName;
    }

    public function build() 
    {      
        $viewName='admin.mail.appointment-email';
        // $subject = 'Ihr Termin bei '.$this->from_name; //commented on 6-dec-23
         $subject = 'Ihre aktuelle Vorsorgeempfehlung ('.$this->serviceName.')'; //added on 6-dec-23
        $this->from('app@puremed.biz',$this->from_name);
        $this->subject($subject);

        return $this->view($viewName, ['details' => $this->data,'name' => $this->name]);
    }
}