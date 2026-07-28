<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendRquiredforadminmail extends Mailable 
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
        $viewName='admin.mail.appoinment-finding-reuiest-email';

        $subject = 'Befundanfrage aus der Smartphone App';
        // dd($subject);

        $this->subject($subject);

        return $this->view($viewName, ['details' => $this->data]);
    }
}