<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class FailedAppointmentMail extends Mailable 
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $data;

    public function __construct($data,$from_name="Puremed")  
    {
        $this->data = $data;
        $this->from_name = $from_name;
        // print_r($this->data); die;
    }

    public function build() 
    {      
        $viewName='admin.mail.failed-appointment-email';

        $subject = 'Termin fehlgeschlagen';
        // dd($subject);
        $this->from('app@puremed.biz',$this->from_name);
        $this->subject($subject);

        return $this->view($viewName, ['details' => $this->data]);
    }
}