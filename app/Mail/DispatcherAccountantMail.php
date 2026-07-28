<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatcherAccountantMail extends Mailable
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
        // $this->type = $type;
    }

    public function build()
    {
        if(count($this->data)>0){

            //Attachment
            if (!empty($this->data['uploaded']) && sizeof($this->data['uploaded']) > 0) 
            {
                foreach ($this->data['uploaded'] as $key=>$attachment) 
                {                               
                    $this->attach($attachment['path']);
                }
            }
        }
        // dd($this->type);
        $this->subject('Internal Communication');
        $this->replyTo(config('constants.ADMINEMAIL'),config('ADMINFROMNAME'));
        $viewName='admin.mail.internal-communication';
        /*if($this->type=="customer"){
            $this->subject('Your Order is Created');
        }else{
            $viewName='admin.mail.order-enquiry';
            $this->subject('New Order Received');
        }*/
        return $this->view($viewName, ['orderinfo' => $this->data['order']]);
    }
}