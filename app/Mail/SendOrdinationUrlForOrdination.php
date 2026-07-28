<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrdinationUrlForOrdination extends Mailable 
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
        $viewName='admin.mail.SendOrdinationUrlForOrdination';
        //dd($this->data);
        $email = $this->view($viewName, ['details' => $this->data])
                ->subject('Ordination Url senden');
      
        // foreach($this->data['attachments'] as $key =>$val)
        // {
        //     $email->attach($val);
        // }
       
        return $email;
        
    }
}