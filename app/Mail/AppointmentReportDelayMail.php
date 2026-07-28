<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AppointmentReportDelayMail extends Mailable 
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
        // print_r($this->data); die;
    }

    public function build() 
    {      
        $viewName='admin.mail.appointment-report-delay-email';

        $subject = 'Appointment Delay Report';
        // dd($subject);

        $this->subject($subject);

        return $this->view($viewName, ['patient' => $this->data['patientData'], 'details' => $this->data]);
    }
}