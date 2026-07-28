<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFindingForPatientmail extends Mailable 
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
        $viewName='admin.mail.sendFindings';

        $email = $this->view($viewName, ['details' => $this->data])
                ->subject('Befund senden');
      
        foreach($this->data['attachments'] as $key =>$val)
        {
            $email->attach($val);
        }
       
        return $email;
        
    }
}