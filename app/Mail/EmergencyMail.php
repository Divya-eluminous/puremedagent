<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;  
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue; 

class EmergencyMail extends Mailable 
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
        $viewName='admin.mail.emergency-email';

        foreach ($this->data['settingData'] as $key=>$setting)  
        {   
            if($setting->setting_key == 'EMERGENCY_BUTTON_SUBJECT'){
                $emailSubject = $setting->setting_value;
            }  
        }
        $subject = $emailSubject;
        // dd($subject);

        $this->subject($subject);

        return $this->view($viewName, ['patient' => $this->data['patientData'], 'details' => $this->data]);
    }
}