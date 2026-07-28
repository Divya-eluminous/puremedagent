<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
// Hyn Tenancy imports (commented out)
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\AppointmentHasNotificationModel;
use App\Models\PatientHasDeviceModel;
use App\Models\AppointmentHasExaminationsModel;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
use Stancl\Tenancy\Facades\Tenancy;


/****************added below code on 29-jan-24**********************/
use App\Mail\AppointmentReminderMail; 
use WebSmsCom_Client;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_TextMessage;
use WebSmsCom_ParameterValidationException; 
use WebSmsCom_AuthorizationFailedException;
use WebSmsCom_ApiException;
use WebSmsCom_HttpConnectionException;
use WebSmsCom_UnknownResponseException;
use Mail;
use Config;
/***************added above code on 29-jan-24**********************/




class AppointmentPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Hyn Tenancy signature (commented out)
    // protected $signature = 'apn:daily {--website_id=}';
    
    // Stancl Tenancy signature
    protected $signature = 'apn:daily {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends daily push notification to patients according to data inserted in appointment_has_notification table with status 0 and 4';

    // Hyn Tenancy properties (commented out)
    // /**
    //  * @var Connection
    //  */
    // private $connection;

    // /**
    //  * @var WebsiteRepository
    //  */
    // private $websites;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
                                AppointmentHasNotificationModel $AppointmentHasNotificationModel,
                                PatientHasDeviceModel $PatientHasDeviceModel,
                                AppointmentHasExaminationsModel $AppointmentHasExaminationsModel
                                )
    {
        parent::__construct();
        // Hyn Tenancy initialization (commented out)
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
        $this->BaseModel  = $AppointmentHasNotificationModel;
        $this->PatientHasDeviceModel = $PatientHasDeviceModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle() 
    {

       // Log::info("AppointmentPushNotification handle function start");
        
        // Hyn Tenancy (commented out)
        // $website_id = $this->option('website_id');
        // try
        // {
        // Log::info("AppointmentPushNotification try block");
        //     if(!empty($website_id) && $website_id!='0')
        //     { 
        //         Log::info("AppointmentPushNotification try block if website_id is not empty");
        //         $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
        //         $this->connection->set($website);            
        //         self::_commandOperation($website_id);
        //         $this->connection->purge();
        //     }
           
        // } 
        // catch (ModelNotFoundException $e) 
        // {
        //     throw new RuntimeException(
        //         sprintf(
        //             'The tenancy website_id=%d does not exist.',
        //             $website_id
        //         )
        //     );
        // }
        
        // Stancl Tenancy
        $tenant_id = $this->option('tenant_id');
        try
        {
           // Log::info("AppointmentPushNotification try block");
            if(!empty($tenant_id) && $tenant_id!='0')
            { 
                //Log::info("AppointmentPushNotification try block if tenant_id is not empty");

                self::_commandOperation($tenant_id);
                 // Stancl tenancy cleanup
                 tenancy()->end();
            }
            //end added below code on 26-aug-25
           
        } 
        catch (ModelNotFoundException $e) 
        {
            throw new RuntimeException(
                sprintf(
                    'The tenancy tenant_id=%d does not exist.',
                    $tenant_id
                )
            );
        }

        //Log::info("AppointmentPushNotification handle function end");
    }
    
    public function _commandOperation($tenant_id) 
    {
        $sendEmailOrSmsFlag = 0;


        $tenant = \App\Models\Tenant::find($tenant_id);
        if ($tenant) {
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
        }

        $fqdn=$hostName=$ordinationName='';
        $getOrdination = DB::connection('system')->table("tenants")->where('id',$tenant_id)->first();

        if(isset($getOrdination) && !empty($getOrdination)){
           // Log::info(" in not empty getOrdination==>");

            $ordinationUuid = $getOrdination->uuid;
            $ordination_id  = $getOrdination->ordination_id;

            // dump("ordination_id==>");
            // dump($ordination_id);


            $getHostNameOrdination = DB::connection('system')->table("domains")->where('ordination_id',$ordination_id)->first();
            // dump($getHostNameOrdination);
            if(isset($getHostNameOrdination)){

               // Log::info("in getHostNameOrdination==>");

                $hostName = $getHostNameOrdination->fqdn;
               // Log::info("hostName==>");
               // Log::info($hostName);
              
                $fqdn = "https://".$hostName;
               // Log::info("fqdn==>");
               // Log::info($fqdn);
            }
           

            $getOrdinationName = DB::connection('system')->table("ordination")->select('name')->where('id',$ordination_id)->first();
            if(isset($getOrdinationName) && !empty($getOrdinationName)){
                $ordinationName = $getOrdinationName->name;
            }

            // dump("ordinationName==>");
            // dump($ordinationName); 

        }//if getOrdination

        

        $nextDay = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')); //for next day
        // dump($nextDay);

        $previousDay = date('Y-m-d', strtotime($nextDay . ' -1 day')); //for previous day
        //    dump($previousDay);

          $channel = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->select('choice_of_channels')
                       ->first();
        // dump("channel");
        // dump($channel);
        // dump(DB::connection('tenant')->getDatabaseName());
        // $collections = DB::connection('tenant')->table('appointment_has_notification')->where('id', 143810)->get();
        // dump($collections);
                $collections =  DB::connection('tenant')->table('appointment_has_notification')
                                ->join('appointment', 'appointment.id' , '=', 'appointment_has_notification.appointment_id')   
                                ->join('users', 'users.id' , '=', 'appointment.doctor_id')  
                                ->join('appointment_types', 'appointment_types.id' , '=', 'appointment.appointment_type_id')
                                ->join('patients', 'patients.id' , '=', 'appointment.patient_id')
                                //->whereDate('appointment_has_notification.notify_time',date('Y-m-d'))  //commented on 13-dec-23 uncommented on 11-nov-25 commented on 12-dec-25

                                ->where('appointment_has_notification.notify_time', date('Y-m-d H:i:00'))
                                //added on 12-dec-25

                                // ->whereDate('appointment_has_notification.notify_time',$previousDay)  //for previous day added on 13-dec-23 //commented on 11-nov-25
                                //->whereDate('appointment.start_date',$nextDay)  //for next day added on 13-dec-23 //commented on 11-nov-25

                                ->whereIn('appointment_has_notification.status',[0,4])
                                ->whereNull('patients.deleted_at')
                                ->where('patients.status',1) //added on 24-july-25
                            //    ->where('appointment_has_notification.patient_id',3)
                                ->get([
                                        'appointment_has_notification.id as notification_id',
                                        'appointment_has_notification.notify_time',
                                        'appointment_has_notification.content',
                                        'appointment_has_notification.title',
                                        'appointment_has_notification.appointment_id',
                                        'appointment.start_date',
                                        'appointment.end_date',
                                        'patients.first_name as patient_fname',
                                        'patients.family_name as patient_lname',
                                        'patients.salutation',
                                        'patients.id as patient_id',
                                        'users.first_name as doctor_fname',
                                        'users.last_name as doctor_lname',
                                        'users.img_path',
                                        'users.doctor_speciality',
                                        'appointment_types.name as aname',
                                        'appointment_types.id as appointment_type_id',
                                        'patients.sendMail',  //added on 29-jan-24
                                        'patients.sendSMS',  //added on 29-jan-24
                                        'patients.mobile_no',  //added on 29-jan-24
                                        'patients.country_code', //added on 29-jan-24
                                        'patients.email' //added on 29-jan-24
                                    ]); 

                // dump($collections); 

        $current_time = date("Y-m-d H:i:s",time());
        if(!empty($collections))
        {
            // dump("Collection is not empty");
            foreach ($collections as $collection) 
            {
                // dump("Collection time_diff");
                // dump($collection);
                $end_time    = strtotime(date('Y-m-d H:i:s',strtotime($collection->notify_time)));
                $start_time  = strtotime(date('Y-m-d H:i:s',time()));  
                $time_diff   = $end_time - $start_time ;

                
                // Log::info('data');
               // Log::info($end_time);
               // Log::info($start_time);
              //  Log::info($time_diff);

                // if ($time_diff >= 0 && $time_diff <= 300) //commented on 12-dec-25
                //if ($time_diff >= 0 && $time_diff <= 60) //added on 12-dec-25
                //{

                    $app_exams = DB::connection('tenant')->table('appointment_has_examinations')
                                ->where('appointment_id',$collection->appointment_id)
                                ->get();
                    //Log::info('data'.$collection->appointment_id."=".count($app_exams));
                    $exam_exist  = 0;
                    $exam_document_exist  = 0;
                    $past_exist  = 0;
                    $exams  = [];

                    if(count($app_exams) > 0) 
                    {
                        // Log::info('data'.$collection->appointment_id."=".count($app_exams));
                        // $app_exams = DB::connection('tenant')->table('appointment_has_examinations')
                        //            // ->leftjoin('examinations','examinations.id','appointment_has_examinations.examination_id')
                        //             //->with(['assignedExamination'])
                        //             ->where('appointment_id',$collection->appointment_id)
                        //             ->get();
                        // Log::info('data pass');
                        // // dd($app_exams);
                       
                        if(!empty($app_exams) && sizeof($app_exams)>0)
                        {
                            $exam_exist  = 1;

                            foreach ($app_exams as  $haskey=>$hasExamination) 
                            {
                                $exam_data = DB::connection('tenant')->table('examinations')->where('id',$hasExamination->examination_id)->first();
                                if(!empty($exam_data) )
                                {
                                    $exams[$haskey]['id'] = $exam_data->id;
                                    $exams[$haskey]['name'] = $exam_data->name;
                                    $exams[$haskey]['url'] = $exam_data->url;

                                    if(!empty($exam_data->document_name) && is_file(storage_path().$exam_data->document_path)){
                                        $exam_document_exist  = 1;
                                    }
                                }
                            }
                        }
                    }

                    //Log::info($exams);
                        if(strtotime($collection->notify_time)<strtotime($current_time))
                        {
                            $past_exist  = 1;                        
                        }
                    
                    
                        $appointment_type   = $collection->aname;
                        $Doctor_name        = $collection->doctor_fname.' '.$collection->doctor_lname;
                        $appointment_date_time   = $collection->start_date;
                        $appointment_time = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr.";
                        $appointment_id     = $collection->appointment_id;
                        $appointment_type_id = $collection->appointment_type_id;
                        $doctor_speciality = $collection->doctor_speciality;

                        $doctor_image = asset('assets/admin/images/default-image.png');
                        if (!empty($collection->img_path) && is_file(storage_path().'/app/'.$collection->img_path)) 
                        {
                            $doctor_image = url('/storage/app/'.$collection->img_path); 
                        }

                        if(!empty($collection->content)){
                            $content = $collection->content;
                            $title = $collection->title;
                        }
                        else
                        {

                            $patientText = $collection->salutation.'.' ?? "";
                            $title = 'Erinnerung an Ihren Termin';
                           // $content = 'Hallo, dein Termin fur '.' '.$appointment_type.' '.'mit Dr.'.' '.(string)$Doctor_name.' '.'ist an'.' '.(string)$appointment_time; 
                            $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$Doctor_name.'  ('.$appointment_type.') ist am'.' '.(string)$appointment_time;
                        }
                        //dd($start_time,$end_time,$time_diff,$collection->notification_id,$content);
                        $PatientId = $collection->patient_id;
                        $mobileId = DB::connection('tenant')->table('patient_has_device')
                                         ->where('patient_id',$PatientId)
                                         ->whereNull('deleted_at') 
                                         ->get(['device_id']);

                        // log::info($PatientId);

                        if(!empty($mobileId))
                        {
                           // dump("in mobilid");

                            // log::info("CurlSend");
                            $mobile_uuids = array_column($mobileId->toArray(), "device_id");

                            $player_ids   = $mobile_uuids;
                            $headings       = array("en" => (string)$title);
                            // Create an single string of all content
                            $content        = array(
                                                    "en" => (string)$content
                                                    );
                            $postData = array(
                                            "appointment_id" => $appointment_id,
                                            "date_time"     => $appointment_date_time,
                                            "doc_name"      => $Doctor_name,
                                            "doc_speciality" => $doctor_speciality,
                                            "appointment_type"    => $appointment_type,
                                            "appointment_type_id" => $appointment_type_id,
                                            "doc_img"             => $doctor_image,
                                            "exam_exist"          => $exam_exist,
                                            "exam_document_exist" => $exam_document_exist,
                                            "past_exist" => $past_exist,
                                            "exams" => $exams
                                            );
                            // $ios_img = array(
                            //                     "doc1" => 'http://puregyn-test.lcmx.at/storage/app/setting-value/20200807165614-20200630233849-mouth.jpg'
                            //                 );

                            $fields = array( 
                                'app_id'                => config('constants.ONESIGNAL_APP_ID'),
                                'include_player_ids'    => $player_ids,
                                'large_icon'            => "ic_stat_onesignal_default",
                                'headings'              => $headings,
                                'contents'              => $content,
                                'data'                  => $postData,
                                'android_group'         => 'ANDROID',
                                'android_group_message' => array("en" => "message"),
                                'ios_badge' => "1"
                               //"ios_attachments"      => $ios_img,
                            ); 

                          //  Log::info($fields);

                            $restAPIKey = config('constants.ONESIGNAL_REST_API_KEY');
                            $fields = json_encode($fields);

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                                      'Authorization: Basic '.$restAPIKey.''));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                            curl_setopt($ch, CURLOPT_HEADER, FALSE);
                            curl_setopt($ch, CURLOPT_POST, TRUE);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                            $data = curl_exec($ch);
                            curl_close($ch);  

                            //dump($data);


                            $checkNotificationSend = json_decode($data, true); //added on 11-dec-25

                            if (isset($checkNotificationSend['id']) && !empty($checkNotificationSend['id'])) 
                            {

                              $sendEmailOrSmsFlag = 0; //added on 11-dec-25

                              //send push notification to user and update status of that notification
                              $updateStatus = DB::connection('tenant')->table('appointment_has_notification')
                                            ->where('id',$collection->notification_id)
                                            ->update(['status'=>1,'one_signal_response'=>$data]);
                            }//if success
                            else{
                                //send email or sms 
                                $sendEmailOrSmsFlag = 1; //added on 11-dec-25
                            }


                        }// if mobileid
                        else
                        {
                            $sendEmailOrSmsFlag = 1; //added else on 11-dec-25
                        }

                        /*************code added on 29-jan-24**for sending email and sms*********/

                        // dump("sendEmailOrSmsFlag ") ;
                        // dump($sendEmailOrSmsFlag);

                        if($sendEmailOrSmsFlag==1)
                        {
                            if($channel->choice_of_channels == 'sms')
                            {
                                 //dump('in sms channel');

                                if (!empty($collection->mobile_no) && $collection->sendSMS==1)
                                {
                                    $country_code = $collection->country_code;
                                    if(!empty($country_code))
                                    {
                                        $country_code = str_replace("00", "",$collection->country_code);
                                    }
                                    elseif(empty($country_code) || $country_code=='0')
                                    {
                                        $country_code = '43'; //Austria country code
                                    }
                                    $country_code = str_replace("+", "",$country_code);
                                    $phone_no   = $country_code."".str_replace("-", "",$collection->mobile_no);

                                    //self::_sendSms($phone_no,$collection);//commented on 28-jan-25
                                    self::_sendSms($phone_no,$collection,$ordinationName,$fqdn);//added on 28-jan-25
                                }
                                elseif (!empty($value->email) && $value->sendMail==1)
                                {
                                    //self::_sendMail($collection);//commented on 28-jan-25
                                     self::_sendMail($collection,$ordinationName,$fqdn);//added on 28-jan-25
                                }
                            }
                            elseif($channel->choice_of_channels == 'email')
                            {
                               // dump('in email channel');

                                if (!empty($collection->email) && $collection->sendMail==1)
                                {
                                    //self::_sendMail($collection);//commented on 28-jan-25
                                    self::_sendMail($collection,$ordinationName,$fqdn);//added on 28-jan-25
                                }
                                elseif (!empty($collection->mobile_no) && $collection->sendSMS==1)
                                {
                                    // if (!empty($value->mobile_no) && $value->sendSMS==1) //For testing only
                                    $country_code = $collection->country_code;
                                    if(!empty($country_code))
                                    {
                                        $country_code = str_replace("00", "",$collection->country_code);
                                    }
                                    elseif(empty($country_code) || $country_code=='0')
                                    {
                                        $country_code = '43'; //Austria country code
                                    }
                                    $country_code = str_replace("+", "",$country_code);
                                    $phone_no   = $country_code."".str_replace("-", "",$collection->mobile_no);
                                    //self::_sendSms($phone_no,$collection);//commented on 28-jan-25
                                    self::_sendSms($phone_no,$collection,$ordinationName,$fqdn);//added on 28-jan-25
                                }
                            }
                        }//if sendemailsms flag 1
                        
                        /****************code added on 29-jan-24****************************/


                    
                                                          
                //}//if time diff commented on 11-dec-25

            }                             
        }
        // else{
        //     dump("Collection is empty");
        // }

       // Log::info("appoitment notification _commandOperation end");
    }//


    /*-----------------------------------
    |  Send mail
    -------------------------------------------------*/
    public function _sendMail_14_june_24_renamed($collection)
    {   
        // Log::info('in _sendMail function');

        $appointment_type   = $collection->aname;
        $Doctor_name        = $collection->doctor_fname.' '.$collection->doctor_lname;
        $appointment_date_time   = $collection->start_date;
        $appointment_time = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr.";

        if(!empty($collection->content))
        {
            $content = $collection->content;
            $title = $collection->title;
        }
        else
        {

            $patientText = $collection->salutation ?? "";
            $title = 'Erinnerung an Ihren Termin';
            $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$Doctor_name.' ('.$appointment_type.') ist am'.' '.(string)$appointment_time;
        }

        // added by vijay 21/3/2024
      
        $patient_id = $collection->patient_id;
        $appointment_id = $collection->appointment_id;
        $encodedPatientId = base64_encode($patient_id);
        $encodedAppointmentId = base64_encode($appointment_id);

        // $baseUrl = Config::get('app.url') . 'user-profile/';
        // $url = $baseUrl . $encodedPatientId . '/' . $encodedAppointmentId;
        // $url = url('/') . '/user-profile/'  . $encodedPatientId . '/' . $encodedAppointmentId;
        $url = 'https://puregyn-stage.puregyn.puredoc.biz/user-profile/' . $encodedPatientId . '/' . $encodedAppointmentId;
        // $content =  $content . '. Besuchen Sie ' . $url . ' für mehr Informationen.';
        $content = $content . '. Besuchen Sie <a href="' . $url . '">' . $url . '</a> für mehr Informationen.';
        //end
        

        $patientDetails = DB::connection('tenant')->table('patients')->find($collection->patient_id);
        $name = $patientDetails->first_name.' '.$patientDetails->family_name;
        $email = $patientDetails->email;                 

        //$originalMailConfig = Config::get('mail');
        //dump($originalMailConfig);

        $result = Mail::to($email)->send(new AppointmentReminderMail($name,$content));

  
    }//_sendMail


     public function _sendMail($collection,$ordinationName,$fqdn)
    {   
       // Log::info('in _sendMail function');

        $appointment_type   = $collection->aname;
        $Doctor_name        = $collection->doctor_fname.' '.$collection->doctor_lname;
        $appointment_date_time   = $collection->start_date;

         //commented below line on 14-june-24
        // $appointment_time = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr.";

        //added below line on 14-june-24
        // $appointment_time = '<span style="color:darkturquoise">'. date('d.F',strtotime($collection->start_date)).'</span>'.", um ". '<span style="color:darkturquoise">'.date('H:i',strtotime($collection->start_date)).'</span>'."";


        $booking_month = __('admin.'.date('F',strtotime($collection->start_date)),[],'de');
        $appointment_time = date('d',strtotime($collection->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($collection->start_date))."";

        

        $patientDetails = DB::connection('tenant')->table('patients')->find($collection->patient_id);

        if(isset($patientDetails) && !empty($patientDetails)){

            $name = $patientDetails->first_name.' '.$patientDetails->family_name;
            $email = $patientDetails->email;         


            //commneted below code on 14-june-24
            /*if(!empty($collection->content))
            {
                $content = $collection->content;
                $title = $collection->title;
            }
            else
            {

                $patientText = $collection->salutation ?? "";
                $title = 'Erinnerung an Ihren Termin';
                $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$Doctor_name.' ('.$appointment_type.') ist am'.' '.(string)$appointment_time;
            }*/

            //start Added below code on 14-june-24
            $patientText = $name.', <br/><br/>' ?? "";
            $title = 'Erinnerung an Ihren Termin';
            $content = 'Sehr geehrte*r '.$patientText .'<br/>';
            $content = $content.'bitte bestätigen Sie unbedingt hier Ihren morgigen Termin: <br/><br/>';
            //end Added below code on 14-june-24


          
            $patient_id = $collection->patient_id;
            $appointment_id = $collection->appointment_id;
            $encodedPatientId = base64_encode($patient_id);
            $encodedAppointmentId = base64_encode($appointment_id);

            
            // $url = 'https://puregyn-stage.puregyn.puredoc.biz/user-profile/' . $encodedPatientId . '/' . $encodedAppointmentId;

            //$url = 'https://puregyn.puremed.biz/user-profile/' . $encodedPatientId . '/' . $encodedAppointmentId; //commented on 28-jan-25

             //added on 28-jan-25
            if(isset($fqdn) && !empty($fqdn)){
                 $url = $fqdn.'/user-profile/' . $encodedPatientId . '/' . $encodedAppointmentId;
            }else{
                 $url = 'https://puregyn.puremed.biz/user-profile/' . $encodedPatientId . '/' . $encodedAppointmentId;
            }



            //commented below line on 14-june-24
            //$content = $content . '. Besuchen Sie <a href="' . $url . '">' . $url . '</a> für mehr Informationen.';

            //Added below code on 14-june-24
            $content = $content . '<a href="' . $url . '">' . $url . '</a>'.'<br/><br/>';
            $content = $content . 'Termindetails: '.(string)$appointment_time.', bei '.(string)$Doctor_name.'<br/>';
            
      
           // $result = Mail::to($email)->send(new AppointmentReminderMail($name,$content));//commented on 28-jan-25

            $result = Mail::to($email)->send(new AppointmentReminderMail($name,$content,$ordinationName));//added on 28-jan-25


            //update status of that notification added code on 11-dec-25
            $updateStatus = DB::connection('tenant')->table('appointment_has_notification')
                            ->where('id',$collection->notification_id)
                            ->update(['status'=>1,'one_signal_response'=>'email']);

        }//if isset patientDetails

  
    }//_sendMail




    /*-----------------------------------
    |  Send sms
    -------------------------------------------------*/
    public function _sendSms($phones,$collection,$ordinationName,$fqdn)
    {
       // Log::info('in _sendSms function');

        $appointment_type   = $collection->aname;
        $Doctor_name        = $collection->doctor_fname.' '.$collection->doctor_lname;
        $appointment_date_time   = $collection->start_date;

        //commented on 11-dec-25
        // $appointment_time = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr.";

        $appointment_time = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr"; //remove dot on 11-dec-25 



        $patientDetails = DB::connection('tenant')->table('patients')->find($collection->patient_id);

        if(isset($patientDetails) && !empty($patientDetails))
        {

            $name = $patientDetails->first_name.' '.$patientDetails->family_name;

            if(!empty($collection->content))
            {
                $content = $collection->content;
                $title = $collection->title;
            }
            else
            {

                //$patientText = $collection->salutation ?? "";

                $patientText = $collection->salutation ? $collection->salutation.'.': ""; 
                if(isset($collection->salutation)){
                    $patientText .= " ".$name; 
                }else{
                    $patientText .= $name; 
                }
                

                $title = 'Erinnerung an Ihren Termin';
                $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$Doctor_name.' ('.$appointment_type.') ist am'.' '.(string)$appointment_time;
            }

            // added by vijay 21/3/2024
          
            $patient_id = $collection->patient_id;
            $appointment_id = $collection->appointment_id;
            $encodedPatientId = base64_encode($patient_id);
            $encodedAppointmentId = base64_encode($appointment_id);

            // $url = 'https://puregyn-stage.puregyn.puredoc.biz/user-profile/' . $encodedPatientId . '/' . $encodedAppointmentId;

            //$url = 'https://puregyn.puremed.biz/user-profile/' . $encodedPatientId . '/' . $encodedAppointmentId;  //commented on 28-jan-25

            //added on 28-jan-25
            if(isset($fqdn) && !empty($fqdn)){
                $url = $fqdn.'/user-profile/' . $encodedPatientId . '/' . $encodedAppointmentId;
            }else{
                $url = 'https://puregyn.puremed.biz/user-profile/' . $encodedPatientId . '/' . $encodedAppointmentId;
            }

            //dump($content);



             // $content =  $content . ' Besuchen Sie ' . $url . ' für mehr Informationen.';//revert this line on 30-jan-25 commented on 23-dec-25

             $content =  $content . ' Besuchen Sie ' . $url . ' fuer mehr Informationen.';//revert this line on 30-jan-25 //changed on 23-dec-25


             //$content = $content . '. Besuchen Sie <a href="' . $url . '">' . $url . '</a> für mehr Informationen.'; //commented this line on 30-jan-25

            //end

            $text   = $content;

            //dump($text);

            if(!empty($phones) && !empty($text))
            {
                $gateway_url      = config('constants.SMS_URL'); 
                $accessToken      = config('constants.SMS_TOKEN');
                $recipientAddressList = array($phones);
                $utf8_message_text    = $text;
                // $maxSmsPerMessage     = 1; //commented on 29-jan-25
                $maxSmsPerMessage     = 2; //changed on 29-jan-25 for sms api issue.
                $test                 = false; // true: do not send sms for real, just test interface
                $responseRecord = array(
                                        'error' => 1 ,
                                        'code'  =>  1,
                                        'message'=> ''
                                    );
                try
                {
                    // 1.) -- Alternatively authenticate over access token
                    $smsClient = new WebSmsCom_Client($accessToken, '', $gateway_url, WebSmsCom_AuthenticationMode::ACCESS_TOKEN);
                    $smsClient->setVerbose(false);
                    $smsClient->setSslVerifyHost(2); // needed if CURLOPT_SSL_VERIFYHOST
                    // 2.) -- create text message ----------------
                    $message  = new WebSmsCom_TextMessage($recipientAddressList, $utf8_message_text);
                    // 3.) -- send message ------------------
                    $Response = $smsClient->send($message, $maxSmsPerMessage, $test);
                    // return success
                    $responseRecord = array(
                                            'error'=>0,
                                            'code' =>$Response->getStatusCode(), 
                                            'message'=>$Response->getStatusMessage(),
                                            'transferId'=>$Response->getTransferId(),
                                            // 'messageId'=>$Response->getClientMessageId(),
                                        );


                    //update status of that notification added code on 11-dec-25
                    $updateStatus = DB::connection('tenant')->table('appointment_has_notification')
                                ->where('id',$collection->notification_id)
                                ->update(['status'=>1,'one_signal_response'=>'sms']);


                } // catch everything that's not a successfully sent message
                catch (WebSmsCom_ParameterValidationException $e)
                {
                    $responseRecord = array(
                                            'error' => 1 ,
                                            'code' =>1,
                                            'message' => "ParameterValidationException caught: ".$e->getMessage()
                                        );
                    //log::info("response1 = ParameterValidationException caught: ".$e->getMessage());
                    //exit("ParameterValidationException caught: ".$e->getMessage()."\n");
                }
                catch (WebSmsCom_AuthorizationFailedException $e)
                {
                    //  exit("AuthorizationFailedException caught: ".$e->getMessage()."\n");
                    $responseRecord = array(
                                            'error' => 1 ,
                                            'code' =>1,
                                            'message' => "AuthorizationFailedException caught: ".$e->getMessage()
                                        );
                    //log::info("response2 = AuthorizationFailedException caught: ".$e->getMessage());
                }
                catch (WebSmsCom_ApiException $e)
                {
                    $responseRecord['message'] = "ApiException Exception: ".$e->getCode().$e->getMessage();
                    //log::info("response3 = ApiException Exception: ".$e->getCode().$e->getMessage());
                }
                catch (WebSmsCom_HttpConnectionException $e)
                {
                    $responseRecord['message'] = "HttpConnectionException caught: ".$e->getMessage();
                    //log::info("response4 = HttpConnectionException caught: ".$e->getMessage());
                }
                catch (WebSmsCom_UnknownResponseException $e)
                {
                    $responseRecord['message'] =  "UnknownResponseException caught: ".$e->getMessage();
                    //log::info("response5 = UnknownResponseException caught: ".$e->getMessage());
                }
                catch (Exception $e)
                {
                    $responseRecord['message'] =  "Exception caught: ".$e->getMessage();
                    //log::info("response6 = Exception caught: ".$e->getMessage());
                }
                $responseRecord['receipient'] = $recipientAddressList;
                
                return $responseRecord;
            }
        }//if patientdetails



    }//_sendSms   


}
