<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
// Hyn Tenancy imports (commented out)
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\ActivityLogModel;
use App\Models\PatientHasDeviceModel;
use App\Models\PatientsModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\AppointmentModel;
use App\Models\ExaminationsModel;
use App\Mail\AppointmentMail; 

use WebSmsCom_Client;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_TextMessage;
use WebSmsCom_ParameterValidationException; 
use WebSmsCom_AuthorizationFailedException;
use WebSmsCom_ApiException;
use WebSmsCom_HttpConnectionException;
use WebSmsCom_UnknownResponseException;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
use Mail;
use DateTime;
use Stancl\Tenancy\Facades\Tenancy;

class AgebaseServicesReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Hyn Tenancy signature (commented out)
    // protected $signature = 'agereminder:daily {--website_id=}';
    
    // Stancl Tenancy signature
    protected $signature = 'agereminder:daily {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check today birthday and set age reminder also send notifciation ';

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
                                PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
                                PatientHasDeviceModel $PatientHasDeviceModel,
                                PatientsModel $PatientsModel,
                                ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
                                AppointmentModel $AppointmentModel,
                                ExaminationsModel $ExaminationsModel,
                                ActivityLogModel $ActivityLogModel
                                )
    {

        parent::__construct();
        // Hyn Tenancy initialization (commented out)
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
        $this->BaseModel  = $PatientsHasServiceReminderModel;
        $this->PatientHasDeviceModel = $PatientHasDeviceModel;
        $this->PatientsModel = $PatientsModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->AppointmentModel = $AppointmentModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->ActivityLogModel  = $ActivityLogModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        log::info("AgebaseServceReminders cron handle function"); 
        
        // Hyn Tenancy (commented out)
        // $website_id = $this->option('website_id');
        // try
        // {
        //     //commented below code on 4-feb-25
        //     /*if(!empty($website_id) && $website_id!='0')
        //     {
        //         $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
        //         $this->connection->set($website);
        //         self::_commandOperation($website_id);
        //         $this->connection->purge();
        //     }*/

        //     //added below code on 4-feb-25
        //     $websites = $this->websites->query()->select('id')->get();
        //     if(isset($websites) && !empty($websites)){
        //         foreach($websites as $k=>$v){
        //             $website = $this->websites->query()->where('id', $v->id)->firstOrFail();
        //             $this->connection->set($website);
        //             self::_commandOperation($v->id);
        //             $this->connection->purge();
        //         }
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
            if(!empty($tenant_id) && $tenant_id!='0')
            {
                self::_commandOperation($tenant_id);
                
                // Stancl tenancy cleanup
                tenancy()->end();
            }
            // else
            // {
            //     // Process all tenants if no specific tenant_id provided
            //     $tenants = \App\Models\Tenant::all();
            //     if(isset($tenants) && !empty($tenants)){
            //         foreach($tenants as $tenant){
            //             self::_commandOperation($tenant->id);
                        
            //             // Stancl tenancy cleanup for each tenant
            //             tenancy()->end();
            //         }
            //     }
            // }
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
    }

    /*-------------------------------------- 
      | Actual functionality
    --------------------------------------*/

    public function _commandOperation($tenant_id) 
    //public function _commandOperation($website_id) 
    {
        
       // log::info("AgebaseServiceReminders cron...");
       // log::info("AgebaseServceReminders cron tenant_id");
       // log::info($tenant_id);
        
        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
           // Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
           // Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }

        $patientCnt =0;
        $allPatients =  DB::connection('tenant')
                                        ->table('patients')
                                        //->where('id',56786)
                                        ->select('id','birth_date','age','mobile_no','email','sendSMS','sendMail')
                                        ->whereNull('deleted_at')
                                        ->get();

       // log::info($allPatients);

        if(!empty($allPatients)){

           // log::info("in all patients flag..");

            foreach($allPatients as $k => $pat){

                $getAgeServices = DB::connection('tenant')->table('preferred_channels_for_reminders_setting as pcr')
                    ->leftjoin('examinations','examinations.id','pcr.service_id')
                    ->where('pcr.activated_reminder','age')
                    ->where('examinations.show_as_reminder','1')
                    ->whereNull('pcr.deleted_at')
                    ->whereNull('examinations.deleted_at')
                    ->get(['examinations.id as service_id','examinations.name as service_name', 'pcr.notify_time', 'pcr.age_from', 'pcr.age_to']);

                //log::info("AgebaseServicesReminders==patientid==>>>>>".$pat->id);

                if(!empty($getAgeServices)){
                    foreach ($getAgeServices as $ke => $ser)
                    {
                        $addrecord=0;
                        if($pat->birth_date) {
                            $from = new DateTime($pat->birth_date);
                            $to   = new DateTime('today');
                            $age =  $from->diff($to)->y;
                        }
                        else {
                            $age =  $pat->age;
                        }

                      
                        if($age == $ser->age_from || ($age < $ser->age_to && $age > $ser->age_from))
                            $addrecord=1;
                        else if($age > $ser->age_to) $addrecord=2;
                        else $addrecord=2; //added on 24-sept-25


                  
                        $checkRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where('patient_id', $pat->id)
                                        ->where('service_id', $ser->service_id)
                                        ->where('reminder_status', 'Set')
                                        ->where('type', 'age')
                                        ->whereNull('deleted_at')
                                        ->count();

                       // log::info("service_id==>".$ser->service_id);              

                        if($checkRecord == 0)                
                        {
                           // log::info("AgebaseServicesReminders====>".$addrecord);
                            if($addrecord==1)
                            {
                                log::info('in insertrecord 1....');
                                log::info($ser->service_id."=====".$pat->id);

                                $PatientsHasServiceReminder = array();
                                $PatientsHasServiceReminder['patient_id']      = $pat->id;
                                $PatientsHasServiceReminder['appointment_id']  = 0;
                                $PatientsHasServiceReminder['service_id']      = $ser->service_id;
                                $PatientsHasServiceReminder['parent_id']       = 0;
                                $PatientsHasServiceReminder['reminder_date']   = date('Y-m-d ').$ser->notify_time.':00';
                                $PatientsHasServiceReminder['reminder_status'] = 'Set';
                                $PatientsHasServiceReminder['type']            = 'age';
                                $PatientsHasServiceReminder['status']          = 'activate';
                                $PatientsHasServiceReminder['created_at']      = date('Y-m-d H:i:s');
                                $PatientsHasServiceReminder['is_added_from_age_reminder'] = 1;

                               
                                DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($PatientsHasServiceReminder);

                              //  log::info("before=patientCnt==>".$patientCnt);
                                $patientCnt++;
                           
                            }//if addrecord 1
                        }//if
                        if($addrecord==2){

                            Log::info("delete reminders ..");                            
                            log::info($ser->service_id."=====".$pat->id);
                            DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('service_id',$ser->service_id)
                            ->where('patient_id',$pat->id)
                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                        }//if

                    }//foreach
                }//getAgeServices 
            }//foreach
        }//allPatients

       // log::info("Total entries ===>".$patientCnt);
    }//_commandOperation




    public function _commandOperation_live_renamed_1_july_24($website_id)
    {
        //echo "here";exit;
        $birthdayPatients =  DB::connection('tenant')->table('patients')
                                        ->whereRaw("DAY(birth_date) = DAY(CURRENT_DATE())
                                        AND MONTH(birth_date) = MONTH(CURRENT_DATE())") 
                                        //->where('id',36067)
                                        ->get();
        //$dbName=DB::connection('tenant')->getDatabaseName();                             
        if(!empty($birthdayPatients)){
            foreach($birthdayPatients as $k => $pat){
                $getAgeServices = DB::connection('tenant')->table('preferred_channels_for_reminders_setting as pcr')
                    ->leftjoin('examinations','examinations.id','pcr.service_id')
                    ->where('pcr.activated_reminder','age')
                    ->whereNull('pcr.deleted_at')
                    ->whereNull('examinations.deleted_at')
                    ->get(['examinations.id as service_id','examinations.name as service_name', 'pcr.notify_time', 'pcr.age_from', 'pcr.age_to']);
                log::info("AgebaseServicesReminders====>>>>>".$pat->id);
                if(!empty($getAgeServices)){
                    foreach ($getAgeServices as $ke => $ser)
                    {
                        $addrecord=0;
                        if($pat->birth_date) {
                            $from = new DateTime($pat->birth_date);
                            $to   = new DateTime('today');
                            $age =  $from->diff($to)->y;
                        }
                        else {
                            $age =  $pat->age;
                        }
                        if($age == $ser->age_from || ($age < $ser->age_to && $age > $ser->age_from))
                            $addrecord=1;
                        else if($age > $ser->age_to) $addrecord=2;
                        $updatePatientAge=DB::connection('tenant')->table('patients')->where('id',$pat->id)->update(['age'=>$age]);
                        $checkRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where('patient_id', $pat->id)
                                        ->where('service_id', $ser->service_id)
                                        ->where('reminder_status', 'Set')
                                        //->where('status', 'activate')
                                        ->where('type', 'age')
                                        //->whereNull('deleted_at')
                                        ->get(['id']);
                        if(sizeof($checkRecord) == 0)
                        {
                            log::info("AgebaseServicesReminders====>".$addrecord);
                            if($addrecord==1)
                            {
                                $PatientsHasServiceReminder = array();
                                $PatientsHasServiceReminder['patient_id']      = $pat->id;
                                $PatientsHasServiceReminder['appointment_id']  = 0;
                                $PatientsHasServiceReminder['service_id']      = $ser->service_id;
                                $PatientsHasServiceReminder['parent_id']       = 0;
                                $PatientsHasServiceReminder['reminder_date']   = date('Y-m-d ').$ser->notify_time.':00';
                                $PatientsHasServiceReminder['reminder_status'] = 'Set';
                                $PatientsHasServiceReminder['type']            = 'age';
                                $PatientsHasServiceReminder['status']          = 'activate';
                                $PatientsHasServiceReminder['created_at']      = date('Y-m-d H:i:s');
                                //echo "<pre>";print_r($PatientsHasServiceReminder);
                                //$PatientsHasServiceReminder->save();
                                DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($PatientsHasServiceReminder);
                                //Send Notification========================================
                                $is_reminder_execute = DB::connection('tenant')->table('settings')
                                                    ->where(['setting_key'=>'REMINDER_SETTING','status'=>'1'])->first();
                                $channel = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                               ->where('type','global')
                                               ->select('choice_of_channels')
                                               ->first();
                                if(!empty($is_reminder_execute))
                                {
                                    // check patinet have installed app
                                    $mobileId = DB::connection('tenant')->table('patient_has_device')
                                    ->where('patient_id',$pat->id)
                                    ->get(['device_id']);
                                    if(!empty($mobileId) && count($mobileId))
                                        self::_sendPushNotification($mobileId,$pat,$ser);
                                    
                                    if($channel->choice_of_channels == 'sms')
                                    {
                                        if (!empty($pat->mobile_no) && $pat->sendSMS==1)
                                        {
                                            $country_code = $pat->country_code;
                                            if(!empty($country_code))
                                            {
                                                $country_code = str_replace("00", "",$pat->country_code);
                                            }
                                            elseif(empty($country_code) || $country_code=='0')
                                            {
                                                $country_code = '43'; //Austria country code
                                            }
                                            $country_code = str_replace("+", "",$country_code);
                                            $phone_no   = $country_code."".str_replace("-", "",$pat->mobile_no);
                                            self::_sendSms($phone_no,$pat,$ser);
                                        }
                                        elseif (!empty($pat->email) && $pat->sendMail==1)
                                        {
                                            self::_sendMail($pat,$ser);
                                        }
                                    }
                                    elseif($channel->choice_of_channels == 'email')
                                    {
                                        if (!empty($pat->email) && $pat->sendMail==1)
                                        {
                                            self::_sendMail($pat,$ser);
                                        }
                                        elseif (!empty($pat->mobile_no) && $pat->sendSMS==1)
                                        {
                                            if (!empty($pat->mobile_no) && $pat->sendSMS==1) //For testing only
                                            $country_code = $pat->country_code;
                                            if(!empty($country_code))
                                            {
                                                $country_code = str_replace("00", "",$pat->country_code);
                                            }
                                            elseif(empty($country_code) || $country_code=='0')
                                            {
                                                $country_code = '43'; //Austria country code
                                            }
                                            $country_code = str_replace("+", "",$country_code);
                                            $phone_no   = $country_code."".str_replace("-", "",$pat->mobile_no);
                                            self::_sendSms($phone_no,$pat,$ser);
                                        }
                                    }
                                }
                                //End send notification=====================================
                            }
                        }
                        if($addrecord==2){
                            // log::info($ser->service_id."=====".$pat->id);
                            // DB::connection('tenant')->table('patient_has_service_reminder')
                            // ->where('service_id',$ser->service_id)
                            // ->where('patient_id',$pat->id)
                            // ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                        }
                    }
                } 
            }
        }
    }

    /*-----------------------------------
    |  Send push notification
    -------------------------------------------------*/
    public function _sendPushNotification($mobileId,$value,$service)
    {
        log::info("notification");
        $appointment_type   = '';        
        $fname =  '';
        $lname = '';
        $Doctor_name  = "";
        $appointment_date_time   = '';
        $appointment_time = '';
        $appointment_id     = '';
        $appointment_type_id = '';
        $doctor_speciality = '';
        // Examination Details
        $exams= [];
        $exam_name = $service->service_name;
        $doctor_image = asset('assets/admin/images/default-image.png');
        $title = 'Erinnerung an Ihren Termin';
        $patient_name = $value->first_name .' '.$value->family_name;
        // GET CONTENT
        $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->first();
        $content = 'Hallo '.$patient_name.', '.$textContent->reminder_push_notification_text." ".$exam_name;
        $mobile_uuids = array_column($mobileId->toArray(), "device_id");
        $player_ids   = $mobile_uuids;
        $headings     = array("en" => (string)$title);
        // Create an single string of all content
        $content      = array("en" => (string)$content);
        // Reminder details
        $postData = array(
                        "appointment_id" => $appointment_id,
                        "date_time"     => $appointment_date_time,
                        "doc_name"      => $Doctor_name,
                        "doc_speciality" => $doctor_speciality,
                        "appointment_type"    => $appointment_type,
                        "appointment_type_id" => $appointment_type_id,
                        "doc_img"             => $doctor_image,
                        "exams"          => $exams
                        );
        

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
       // dd($fields);
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
        $updateStatus = DB::connection('tenant')->table('patient_has_service_reminder')->find($value->reminder_id);

        $responseRecord = DB::connection('tenant')
              ->table('patient_has_service_reminder')
              ->where('id',$value->reminder_id)
              ->update(['reminder_status'=>'executed','updated_at'=>date('Y-m-d H:i:s'),'media'=>'push_notification']); 
    }

    /*-----------------------------------
    |  Send mail
    -------------------------------------------------*/
    public function _sendMail($value,$servcie)
    {
      
        $patientDetails = DB::connection('tenant')->table('patients')->find($value->id);
        $name = $patientDetails->first_name.' '.$patientDetails->family_name;
        $email = $patientDetails->email;
        $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->first();
        $text   = $textContent->reminder_mail_notification_text."<br/><b><a href='https://puregyn.puremed.biz/oa/services/".base64_encode($servcie->service_id)."'>".$servcie->service_name."</a></b>";               
        $result = Mail::to($email)->send(new AppointmentMail($name,$text));
        //echo "here".$result;
    }

    /*-----------------------------------
    |  Send sms
    -------------------------------------------------*/
    public function _sendSms($phones,$value,$servcie)
    {
        $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                       ->where('id','1')
                       ->first();
        $URL='https://puregyn.puremed.biz/oa/services/'.base64_encode($servcie->service_id);
        $text   = $textContent->reminder_sms_notification_text." ".$URL."\n\n\r\r".$servcie->service_name;
        if(!empty($phones) && !empty($text))
        {
            $gateway_url      = config('constants.SMS_URL'); 
            $accessToken      = config('constants.SMS_TOKEN');
            $recipientAddressList = array($phones);
            $utf8_message_text    = $text;
            $maxSmsPerMessage     = 1;
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
            } // catch everything that's not a successfully sent message
            catch (WebSmsCom_ParameterValidationException $e)
            {
                $responseRecord = array(
                                        'error' => 1 ,
                                        'code' =>1,
                                        'message' => "ParameterValidationException caught: ".$e->getMessage()
                                    );
                log::info("response1 = ParameterValidationException caught: ".$e->getMessage());
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
                log::info("response2 = AuthorizationFailedException caught: ".$e->getMessage());
            }
            catch (WebSmsCom_ApiException $e)
            {
                $responseRecord['message'] = "ApiException Exception: ".$e->getCode().$e->getMessage();
                log::info("response3 = ApiException Exception: ".$e->getCode().$e->getMessage());
            }
            catch (WebSmsCom_HttpConnectionException $e)
            {
                $responseRecord['message'] = "HttpConnectionException caught: ".$e->getMessage();
                log::info("response4 = HttpConnectionException caught: ".$e->getMessage());
            }
            catch (WebSmsCom_UnknownResponseException $e)
            {
                $responseRecord['message'] =  "UnknownResponseException caught: ".$e->getMessage();
                log::info("response5 = UnknownResponseException caught: ".$e->getMessage());
            }
            catch (Exception $e)
            {
                $responseRecord['message'] =  "Exception caught: ".$e->getMessage();
                log::info("response6 = Exception caught: ".$e->getMessage());
            }
            $responseRecord['receipient'] = $recipientAddressList; 
            return $responseRecord;
        }
    }   
}
