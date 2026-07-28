<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDocumentForPatientmail extends Mailable 
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
        $viewName='admin.mail.SendDocumentForPatient';

        $email = $this->view($viewName, ['details' => $this->data])
                ->subject('Dokument gesendet');

        // dd($this->data['attachments']);
        if (!empty($this->data['attachments']))
        {
            $email->attach($this->data['attachments']);
        }
       
        return $email;
        
    }
}