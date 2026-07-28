<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ForgotPasswordMailWeb extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $data;

    // public function __construct($data,$view) //commented on 17-june-25 for #367
     public function __construct($data,$view,$ordination="Puremed")//added on 17-june-25 for #367
    
    {
        $this->data = $data;
        $this->view = $view;
        $this->ordination = $ordination;//added on 17-june-25 for #367
        // dd('build',$this->data);
    }

    public function build()
    {      
        if($this->view=='web')
        {
            $viewName='web.mail.forgot-password-email';
            // $subject = 'Passwort vergessen PureGyn';//commented on 17-june-25 for #367
            $subject = 'Passwort vergessen '.$this->ordination;//changed on 17-june-25 for #367 
            $this->subject($subject);
        }
        /*else if($this->view=='web')
        {
            $viewName='web.forgot.forgot-password-email';
        }*/

        $this->from('app@puremed.biz',$this->ordination);  //added on 17-june-25 for #367
        $this->replyTo(config('constants.ADMINEMAIL'),config('constants.ADMINFROMNAME'));
       

        // return $this->view($viewName, ['user' => $this->data]);//commented on 17-june-25 for #367
         return $this->view($viewName, ['user' => $this->data,'ordination'=>$this->ordination]);//changed on 17-june-25 for #367
    }
}