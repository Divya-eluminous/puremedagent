<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
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

use App\Mail\ReminderNotificationMail; 

use DateTime; 


class ReminderNotificationPast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'remindernewpast:daily {--website_id=}';
    protected $signature = 'remindernewpast:daily {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set next reminder dates in database';

     /**
     * @var Connection
     */
    private $connection;

    /**
     * @var WebsiteRepository
     */
    private $websites;

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
        // //log::info("ReminderStatus-Handle");
        // $website_id = $this->option('website_id');

        // log::info("website_id=in handle function=of ReminderNotificationPast=====>");
        // log::info($website_id);
 
        // try
        // {

        //     //commented below code on 31-dec-24
        //     if(!empty($website_id) && $website_id!='0')
        //     {
        //         $website = $this->websites->query()->where('id', $website_id)->firstOrFail();

        //         $this->connection->set($website);
        //         self::_commandOperation($website_id);
        //         $this->connection->purge();
        //     }

        //     //added below code on 31-dec-24
        //     // $websites = $this->websites->query()->select('id')->get();
        //     // if(isset($websites) && !empty($websites)){
        //     //     foreach($websites as $k=>$v){
        //     //         $website = $this->websites->query()->where('id', $v->id)->firstOrFail();
        //     //         $this->connection->set($website);
        //     //         self::_commandOperation($v->id);
        //     //         $this->connection->purge();
        //     //     }
        //     // }

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
        log::info("tenant_id=in handle function=of ReminderNotificationPast=====>");
        log::info($tenant_id);
        
        
        try
        {
            if(!empty($tenant_id) && $tenant_id!='0')
            {
                self::_commandOperation($tenant_id);
                
                // Stancl tenancy cleanup
                tenancy()->end();
            }
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

    public function _commandOperation($tenant_id)
    //public function _commandOperation($website_id)
    {
       
        log::info("In ReminderNotificationPast _commandOperation function ..........");

        log::info("tenant_id===>");
        log::info($tenant_id);

        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
            Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
            Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }

        //start code for ordination name and url
        $fqdn=$hostName=$ordinationName='';
        $getOrdination = DB::connection('system')->table("tenants")->where('id',$tenant_id)->first();
        if(isset($getOrdination) && !empty($getOrdination)){
            Log::info(" in not empty getOrdination==>");

            $ordinationUuid = $getOrdination->uuid;
            $ordination_id  = $getOrdination->ordination_id;

            Log::info("ordination_id==>");
            Log::info($ordination_id);


            $getHostNameOrdination = DB::connection('system')->table("domains")->where('ordination_id',$ordination_id)->first();

            if(isset($getHostNameOrdination)){

                Log::info("in getHostNameOrdination==>");

                $hostName = $getHostNameOrdination->fqdn;
                Log::info("hostName==>");
                Log::info($hostName);
              
                $fqdn = "https://".$hostName;
                Log::info("fqdn==>");
                Log::info($fqdn);
            }
           

            $getOrdinationName = DB::connection('system')->table("ordination")->select('name')->where('id',$ordination_id)->first();
            if(isset($getOrdinationName) && !empty($getOrdinationName)){
                $ordinationName = $getOrdinationName->name;
            }

            Log::info("ordinationName==>");
            Log::info($ordinationName); 

        }//if getOrdination 
        //end code added for ordination name and url
         

        $is_reminder_execute = DB::connection('tenant')->table('settings')->where(['setting_key'=>'REMINDER_SETTING','status'=>'1'])->first();
        $channel = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->select('choice_of_channels')
                       ->first();
    


        log::info(date('Y-m-d'));
        log::info(date('H:i'));
               

        if(!empty($is_reminder_execute))
        {
             //client 33890
            
           $collections =  DB::connection('tenant')->table('patient_has_service_reminder')
                        ->join('patients', 'patients.id' , '=', 'patient_has_service_reminder.patient_id')
                        ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                        ->where(function($query) {
                            // $query->whereDate('patient_has_service_reminder.reminder_date', '<', date('Y-m-d'));

                            $query->whereDate('patient_has_service_reminder.reminder_date', '>=', '2025-02-01')
                            ->whereDate('patient_has_service_reminder.reminder_date', '<=', '2025-02-06');
                             
                        })
                        ->whereNull('patient_has_service_reminder.next_reminder_date') 

                        ->where('patient_has_service_reminder.reminder_status','Set')
                        
                         // ->where('patients.id',47584) //live commented by vijay 4/4/2024
                        ->whereIn('patients.id',[47584,47585]) //live commented by vijay 4/4/2024
                         ->where('patient_has_service_reminder.is_sent',0) 

                        ->whereNull('patient_has_service_reminder.deleted_at')
                        ->whereNull('patients.deleted_at')   

                        ->orderBy('patient_has_service_reminder.id','desc')
                        ->get(['patient_has_service_reminder.id as reminder_id','patient_has_service_reminder.reminder_date','patients.id as patient_id','patients.mobile_no','patients.age as patient_age','patients.country_code','patients.email','examinations.name as name','examinations.id as exam_id','patients.sendMail','patients.sendSMS',
                            'patient_has_service_reminder.appointment_id', 
                            'patient_has_service_reminder.status',  
                            'patient_has_service_reminder.reminder_status',
                            'patient_has_service_reminder.service_id',  
                            'patient_has_service_reminder.type',  
                            'patient_has_service_reminder.next_reminder_date',
                       ]);

             log::info("Send Notify before collections");
             log::info($collections);

              $currentDate = Date('d-m-Y');    //8-apr-24  


             log::info("currentDate===>");  
             log::info($currentDate);   


 
            if(!empty($collections))
            {
                foreach ($collections as $key => $value)
                {

                     log::info('reminder_id');
                     log::info($value->reminder_id);

                    $patientid= $value->patient_id;

                    $getRemider = DB::connection('tenant')->select(DB::raw("SELECT t1.*,patients.mobile_no,patients.age,patients.country_code,patients.email,patients.sendMail,patients.sendSMS,patients.birth_date,examinations.name FROM patient_has_service_reminder t1
                                    JOIN patients on patients.id=t1.patient_id 
                                    AND patients.deleted_at IS NULL
                                    JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
                                    AND examinations.deleted_at IS NULL
                                    JOIN (SELECT service_id,reminder_date,next_reminder_date, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL

                                      -- AND (DATE(t2.reminder_date) = CURDATE() OR DATE(t2.next_reminder_date) = CURDATE())
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));                                                
                        

                    $getRemider = collect($getRemider)->map(function($x){ 
                        return (array) $x; 
                    })->toArray();    

                     log::info("getRemider=======>");
                     log::info($getRemider);

                    if(!empty($getRemider) && sizeof($getRemider)>0)
                    {     


                        //Added by Shyam 01-02-22
                        // log::info("Send Notify");

                        // Below condition added on 26-march-24 for active services needs to have the reminder send

                        // $reminderDate = Date('d-m-Y H:i',strtotime($value->reminder_date)); //commeted H:i on 8-apr-24

                        foreach ($getRemider as $key_rem => $value_rem) 
                        {
                            log::info("value->reminder_date===>");
                            log::info($value->reminder_date);

                            log::info("value_rem['reminder_date']==>");
                            log::info($value_rem['reminder_date']);

                           if($value->reminder_date==$value_rem['reminder_date'])
                           {  

                                log::info("both date are same ==>");


                                $reminderDate = Date('d-m-Y',strtotime($value_rem['reminder_date']));    

                                log::info($reminderDate);

                                $nextReminderDate='';

                               // $status =  'aktiv';

                                $status =  'aktiv';

                                if((strtotime($reminderDate) < strtotime($currentDate)) || $value_rem['appointment_id']==0)
                                {
                                    $status =  'aktiv';
                                }
                                if($value_rem['reminder_status']=='ignore' && $value_rem['status']!='deactivate')
                                {
                                    $status ='inaktiv';
                                } 
                                if($value_rem['reminder_status']=='ignore' && $value_rem['status']=='deactivate')
                                {
                                    $status ='inaktiv';
                                }   

                                if($value_rem['appointment_id']==0 && $value_rem['status']=='deactivate')
                                {
                                    $status ='inaktiv';
                                }

                               


                                log::info("status====>");
                                log::info($status);

                                if($status=="aktiv") //if status active condition added on 26-march-24
                                {   
                                    log::info($value_rem['service_id']);
                                    log::info("in active status====>");


                                    $checkReminder = 'Send';
                                    $checkPatientAge = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(['service_id'=>$value_rem['service_id'],'activated_reminder'=>'age'])->first();

                                    $ageFrom = $ageTo = 0;$age =0;
                                    if(!empty($checkPatientAge->age_from))
                                    {
                                        $ageFrom = $checkPatientAge->age_from;
                                    }
                                    if(!empty($checkPatientAge->age_to))
                                    {
                                        $ageTo = $checkPatientAge->age_to;
                                    }

                                    /*********added on 5-july-24****************************/
                                     if($value_rem['birth_date']) {
                                        $from = new DateTime($value_rem['birth_date']);
                                        $to   = new DateTime('today');
                                        $age =  $from->diff($to)->y;
                                    }
                                    else {
                                        $age =  $value_rem['age'];
                                    }
                                    /***********added on 5-july-24*************************/

                                   

                                    //changed on 5-july-24
                                    if(!empty($age) && $ageFrom > 0 && $ageTo > 0 && ($age < $ageFrom || $age > $ageTo))
                                    {
                                        $checkReminder = 'Not Send';
                                    }

                                    log::info($age);


                                    log::info("Send Notify");
                                    $reminder_active = DB::connection('tenant')->table('patients')->where(['id'=>$value_rem['patient_id'],'reminder_active'=>'1'])->first();

                                     $sendEmailFlag=$sendSmsFlag=$updateCount=0;


                                     log::info("next_reminder_date");
                                     log::info($value_rem['next_reminder_date']);
                                     log::info('appid 0 or not');
                                     log::info($value_rem['appointment_id']);

                                     if(empty($value_rem['next_reminder_date']) && $value_rem['appointment_id']==0)
                                    {
                                        log::info('in 1');

                                        // $reminderDate = Date('d-m-Y H:i',strtotime($value->reminder_date)); //commeted H:i on 8-apr-24

                                         $reminderDate = Date('d-m-Y',strtotime($value_rem['reminder_date'])); 


                                    }
                                    else if($value_rem['appointment_id']==0)
                                    {
                                         log::info('in 2');
                                        // $reminderDate = Date('d-m-Y H:i',strtotime($value->next_reminder_date)); //commeted H:i on 8-apr-24

                                        $reminderDate = Date('d-m-Y',strtotime($value_rem['next_reminder_date']));  
                                    }
                                    else
                                    {
                                         log::info('in 3');
                                        // $reminderDate = Date('d-m-Y H:i',strtotime($value->reminder_date)); //commeted H:i on 8-apr-24

                                        $reminderDate = Date('d-m-Y',strtotime($value_rem['reminder_date']));  
                                    }   


                                     log::info("reminderDate===>");
                                     log::info($reminderDate);

                                     $reminderDate2 = Date('d-m-Y',strtotime($value->reminder_date));  
                                     log::info("reminderDate2===>");
                                     log::info($reminderDate2);

                                    // if($value_rem['appointment_id']==0 && $reminderDate==$currentDate && $value_rem['type']=='age')

                                    if($value_rem['appointment_id']==0 && $value_rem['type']=='age' && $reminderDate==$reminderDate2)
                                    {
                                        log::info("in appointment id 0 and date equal..");

                                        /****start**code for apponitment id**0**flags**************/

                                     

                                            $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                                                   ->where('service_id',$value_rem['service_id'])
                                                                   ->where('activated_reminder',$value_rem['type'])
                                                                   ->first();

                                            if(isset($reminderSetting) && !empty($reminderSetting))
                                            {
                                                $age_number_of_interval = $reminderSetting->age_number_of_interval;   

                                                 log::info("age_number_of_interval===>");
                                                 log::info($age_number_of_interval);   

                                                $getReminderCount =  DB::connection('tenant')
                                                            ->table('patient_has_service_reminder')
                                                            ->select('notification_count')
                                                            ->where('id',$value_rem['id'])
                                                            ->where('patient_id',$value_rem['patient_id'])
                                                            ->where('service_id',$value_rem['service_id'])
                                                            ->where('type',$value_rem['type'])
                                                            ->where('appointment_id','=',0)
                                                            ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                            ->first();

                                                $cnt = $getReminderCount->notification_count; 

                                                 log::info("cnt===>");
                                                 log::info($cnt);    

                                                 if($cnt<$age_number_of_interval)
                                                {
                                                    $updateCount = $cnt+1;       

                                                     log::info("updateCount===>");
                                                     log::info($updateCount);   

                                                    /****start*code for change reminder date***********/
                                                    if($cnt>=0)
                                                    {

                                                         $checkNextReminders =  DB::connection('tenant')
                                                        ->table('patient_has_service_reminder')
                                                        ->where('patient_id',$value_rem['patient_id'])
                                                        ->where('service_id',$value_rem['service_id'])
                                                        ->where('type',$value_rem['type'])
                                                        ->where('appointment_id','!=',0)
                                                        ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                        ->get();

                                                       // dump($checkNextReminders);

                                                        if(isset($checkNextReminders) && !empty($checkNextReminders) && count($checkNextReminders)>0)
                                                        {
                                                            //dump('in checkNextReminders...');

                                                        }//if checkNextReminders
                                                        else
                                                        {
                                                            //dump('else checkNextReminders...');

                                                            $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                                               ->where('service_id',$value_rem['service_id'])
                                                               ->where('activated_reminder',$value_rem['type'])
                                                               ->first();

                                                             //  dump($reminderSetting);

                                                            $age_time_interval = $reminderSetting->age_time_interval;
                                                            $age_time_interval_frequency = $reminderSetting->age_time_interval_frequency_type;

                                                            $period_date = Date('d-m-Y H:i:s',strtotime($value_rem['reminder_date']));    

                                                            if(empty($value_rem['next_reminder_date']))
                                                            {

                                                                //dump('in empty next reminder date...');

                                                                     $value4_days = $this->_getDate($period_date,$age_time_interval,$age_time_interval_frequency);

                                                                     $nextReminderDate = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($reminderDate)) . " +".(int)$value4_days." day"));
                                                            }
                                                            else
                                                            {
                                                               // dump('in not empty next reminder date...');

                                                                $value4_days = $this->_getDate($value_rem['next_reminder_date'],$age_time_interval,$age_time_interval_frequency);

                                                                $nextReminderDate = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($value_rem['next_reminder_date'])) . " +".(int)$value4_days." day"));
                                                            }

                                                            // dump($reminderDate);


                                                        }//else if
                                                    }//if updatecount is greater than 0

                                                    /********end*code for change reminder date*********/


                                                    $sendEmailFlag=1;
                                                    $sendSmsFlag=1;

                                                }//if 
                                                else
                                                {
                                                    $sendEmailFlag=0;
                                                    $sendSmsFlag=0;
                                                }

                                            }//if isset reminderSetting                      

                                      

                                        /****end***code for appointment id 0**flags************/
                                        

                                    }//if value of appointment id 0
                                    else
                                    {

                                        log::info("else app id not 0");

                                        
                                        $reminderDate1 = date("Y-m-d",strtotime($value->reminder_date));
                                        Log::info($reminderDate1);


                                        log::info("else app id not 0 reminderDate1");
                                        log::info($reminderDate1);

                                        $checkNextReminder =  DB::connection('tenant')
                                                        ->table('patient_has_service_reminder')
                                                        ->where('patient_id',$value_rem['patient_id'])
                                                        ->where('service_id',$value_rem['service_id'])
                                                        ->where('type',$value_rem['type'])
                                                        ->where('appointment_id','!=',0)
                                                        ->whereDate('reminder_date', $reminderDate1)
                                                        ->where('appointment_id',$value_rem['appointment_id'])
                                                        ->where('is_sent',0)
                                                        ->whereNull('patient_has_service_reminder.deleted_at') 
                                                        ->orderBy('id','desc')
                                                        ->first();

                                        //log::info($checkNextReminder);   
                                                     
                                        if(isset($checkNextReminder) && !empty($checkNextReminder))
                                        {

                                            $reminderDate = isset($checkNextReminder->reminder_date)? Date('d-m-Y',strtotime($checkNextReminder->reminder_date)):'';  
                                        }                
                                        log::info("checkNextReminder reminder date after changes app id not 0");
                                        log::info($reminderDate);

                                        $sendEmailFlag=1;
                                        $sendSmsFlag=1;

                                    }//else

                                   log::info("reminderDate===>");  
                                   log::info($reminderDate);
                                   log::info($currentDate);

                                    // if($reminder_active && $checkReminder == 'Send' && $reminderDate==$currentDate) //Added by Shyam 01-02-22

                                    if($reminder_active && $checkReminder == 'Send' && $reminderDate==$reminderDate2) //Added by Shyam 01-02-22
                                    {

                                        log::info('in ..................');

                                         log::info("Patient id====>");
                                         log::info($value_rem['patient_id']);


                                        // check patinet have installed app
                                        $mobileId = DB::connection('tenant')->table('patient_has_device')
                                                    ->where('patient_id',$value_rem['patient_id'])
                                                    ->get(['device_id']);
                                        if(!empty($mobileId) && count($mobileId))
                                        {
                                            //PUSHNOTIFICATION

                                          if($sendSmsFlag==1)
                                          { 
                                             log::info('in send push 1');

                                            //self::_sendPushNotification($mobileId,$value,$updateCount,$nextReminderDate); //commented

                                          }//if _sendPushNotification

                                        }
                                        if($channel->choice_of_channels == 'sms')
                                        {

                                            if (!empty($value_rem['mobile_no']) && $value_rem['sendSMS']==1)
                                            {
                                                $country_code = $value_rem['country_code'];
                                                if(!empty($country_code))
                                                {
                                                    $country_code = str_replace("00", "",$value_rem['country_code']);
                                                }
                                                elseif(empty($country_code) || $country_code=='0')
                                                {
                                                    $country_code = '43'; //Austria country code
                                                }
                                                $country_code = str_replace("+", "",$country_code);
                                                $phone_no   = $country_code."".str_replace("-", "",$value_rem['mobile_no']);

                                                if($sendSmsFlag==1)
                                                {
                                                    log::info('in send sms 1');

                                                    self::_sendSms($phone_no,$value_rem,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn); //added on 28-jan-25

                                                }//if sendSmsFlag 1

                                                
                                            }
                                            elseif (!empty($value_rem['email']) && $value_rem['sendMail']==1 && $this->isValidEmail($value_rem['email']))
                                            {
                                                if($sendEmailFlag==1)
                                                {
                                                     log::info('in send email 1');

                                                     self::_sendMail($value_rem,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn);//changed on 28-jan-25

                                                }//if condition added on 28-march-24
                                                
                                            }
                                        }
                                        elseif($channel->choice_of_channels == 'email')
                                        {
                                           
                                            log::info('in send mail call....');


                                            if (!empty($value_rem['email']) && $value_rem['sendMail']==1 && $this->isValidEmail($value_rem['email']))
                                            {


                                                if($sendEmailFlag==1)
                                                {
                                                     log::info('in send email 1');

                                                    self::_sendMail($value_rem,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn);//changed on 28-jan-25   

                                                }//if condition added on 28-march-24   


                                            }
                                            elseif (!empty($value_rem['mobile_no']) && $value_rem['sendSMS']==1) //uncommented condition on 28-march-24
                                            {
                                               
                                                $country_code = $value_rem['country_code'];
                                                if(!empty($country_code))
                                                {
                                                    $country_code = str_replace("00", "",$value_rem['country_code']);
                                                }
                                                elseif(empty($country_code) || $country_code=='0')
                                                {
                                                    $country_code = '43'; //Austria country code
                                                }
                                                $country_code = str_replace("+", "",$country_code);
                                                $phone_no   = $country_code."".str_replace("-", "",$value_rem['mobile_no']);

                                                if($sendSmsFlag==1)
                                                {
                                                    log::info('in send sms 1');

                                                  self::_sendSms($phone_no,$value_rem,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn); //changed on 28-jan-25 

                                                }//if sendSmsFlag


                                            }
                                        }//else if
                                    }//if
                                    else{
                                       // dump('in else of reminder active.........');
                                    }

                                }//if status is active condition added on 26-march-24
                            }//if same
                        }//foreach get reminder
                    }//if getreminder

                }//foreach
            }//if
        }//if reminder execute
    }//_commandOperation

     /*-----------------------------------
    |  Send push notification
    -------------------------------------------------*/
    public function _sendPushNotification($mobileId,$value,$updateCount,$nextReminderDate)
    {

       // dump("in");
        $collection =  DB::connection('tenant')->table('patient_has_service_reminder')
                        ->join('appointment', 'appointment.id' , '=', 'patient_has_service_reminder.appointment_id')  
                        ->join('patients', 'patients.id' , '=', 'patient_has_service_reminder.patient_id')
                        ->join('appointment_types', 'appointment_types.id' , '=', 'appointment.appointment_type_id')
                        ->join('users', 'users.id' , '=', 'appointment.doctor_id')
                        ->where('patient_has_service_reminder.id',$value['id'])
                        ->first([
                            'patient_has_service_reminder.service_id',
                            'appointment.start_date',
                            'appointment.end_date',
                            'patients.first_name as patient_fname',
                            'patients.family_name as patient_lname',
                            'patients.salutation',
                            'patients.id as patient_id',
                            'appointment_types.name as aname',
                            'appointment_types.id as appointment_type_id',
                            'users.first_name as doctor_fname',
                            'users.last_name as doctor_lname',
                            'users.img_path',
                            'users.doctor_speciality',
                        ]);

        $appointment_type   = $collection->aname ?? '';
        //$Doctor_name        = $collection->doctor_fname.' '.$collection->doctor_lname;

        $fname =  $collection->doctor_fname ?? '';
        $lname = $collection->doctor_lname ?? '';
        $Doctor_name  = $fname." ".$lname;
        $appointment_date_time   = $collection->start_date ?? '';
        $appointment_time = '';
        if(!empty($appointment_date_time))
        {
            $appointment_time = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr.";
        }
        $appointment_id     = $collection->appointment_id ?? '';
        $appointment_type_id = $collection->appointment_type_id ?? '';
        $doctor_speciality = $collection->doctor_speciality ?? '';
        // Examination Details
        $exams= [];
        $exam_name = $value['name'];
        // if(!empty($collection->service_id))
        // {
        //     $getExam = DB::connection('tenant')->table('examinations')->find($collection->service_id);
        //     if(!empty($getExam))
        //     {
        //         $exams['id']   = $getExam->id;
        //         $exams['name'] = $getExam->name;
        //         $exam_name = $getExam->name;
        //         $exams['url']  = $getExam->url;
        //     }
        // }
        // end
        $doctor_image = asset('assets/admin/images/default-image.png');
        if (!empty($collection->img_path) && is_file(storage_path().'/app/'.$collection->img_path)) 
        {
            $doctor_image = url('/storage/app/'.$collection->img_path); 
        }

        $title = 'Erinnerung an Ihren Termin';
        // Patint Details
        $patientDetails = DB::connection('tenant')->table('patients')->find($value['patient_id']);
        $patient_name = $patientDetails->first_name .' '.$patientDetails->family_name;
        // GET CONTENT
        $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->first();

        $content = 'Hallo '.$patient_name.', '.$textContent->reminder_push_notification_text." ".$exam_name;

       // dd($content);

        $mobile_uuids = array_column($mobileId->toArray(), "device_id");

        $player_ids   = $mobile_uuids;
        $headings     = array("en" => (string)$title);
        // Create an single string of all content
        $content      = array(
                                "en" => (string)$content
                                );
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
        //dump("out");

        // DB::connection('tenant')->table('ActivityLogModel')->addLog('reminder for push notification','has sent push notification','sent',null,$postData);
        //send push notification to user and update status of that notification
        $updateStatus = DB::connection('tenant')->table('patient_has_service_reminder')->find($value['id']);


        //Commented below code on 27-march-24 
       /* $responseRecord = DB::connection('tenant')
              ->table('patient_has_service_reminder')
              ->where('id',$value->reminder_id)
              ->update(['reminder_status'=>'executed','updated_at'=>date('Y-m-d H:i:s'),'media'=>'push_notification']);*/



        if($value['appointment_id']==0)
        {
             if(isset($updateCount))
             {
                 $responseRecord = DB::connection('tenant')
                  ->table('patient_has_service_reminder')
                  ->where('id',$value['reminder_id'])
                  ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'push_notification','notification_count'=>$updateCount,'is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]);     


                /************added***on*2-apr-24*********************/
                $getReminderCount =  DB::connection('tenant')
                                                            ->table('patient_has_service_reminder')
                                                            ->select('notification_count')
                                                            ->where('id',$value['id'])
                                                            ->where('patient_id',$value['patient_id'])
                                                            ->where('service_id',$value['service_id'])
                                                            ->where('type',$value['type'])
                                                            ->where('appointment_id','=',0)
                                                            ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                            ->first();

                $cnt = $getReminderCount->notification_count;

                if(isset($cnt) && $cnt>0 && !empty($nextReminderDate))
                {
                     // dump("nextReminderDate=in send email function=>");
                     // dump($nextReminderDate);     

                     $responseRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                      ->where('id',$value['id'])
                      ->update(['next_reminder_date'=>$nextReminderDate]);

                }//if cnt  

                /*************added***on*2-apr-24****************************/  

             }//if



            
        }
        else
        {
             $responseRecord = DB::connection('tenant')
              ->table('patient_has_service_reminder')
              ->where('id',$value['id'])
              ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'push_notification','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]);     
        }      

        


        
        // $updateStatus->reminder_status          = 'executed';
        // $updateStatus->reminder_channel= 'notification';
        // $updateStatus->content         = 'Hallo '.$patient_name.', '.$textContent->reminder_push_notification_text;;
        // if($updateStatus->save())
        // {
        //     return 'true';
        // }  
    }//_sendPushNotification


    function isValidEmail($email)
    { 
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }


    public function _sendMail($value,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn) 
    {
        log::info('in send mail function....');
        log::info('nextReminderDate....');
        log::info($nextReminderDate);

        $reminderDate = date("Y-m-d",strtotime($reminderDate));//added on 22-jan-25
        log::info($reminderDate);

        $patientDetails = DB::connection('tenant')->table('patients')->find($value['patient_id']);
        $name = $patientDetails->first_name.' '.$patientDetails->family_name;
        $email = $patientDetails->email;

        log::info($email);

        log::info("check is valid email");
        log::info($this->isValidEmail($email));

        if($this->isValidEmail($email))
        {
            log::info("valid email");

            $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                               ->where('type','global')
                               ->first();

            //start added on 28-jan-25           
            if(isset($fqdn) && !empty($fqdn)){
                $text   = $textContent->reminder_mail_notification_text."<br/><b><a href='".$fqdn."/oa/services/".base64_encode($value['service_id'])."'>".$value['name']."</a></b>"; 
            }else{
                $text   = $textContent->reminder_mail_notification_text."<br/><b><a href='https://puregyn.puremed.biz/oa/services/".base64_encode($value['service_id'])."'>".$value['name']."</a></b>"; 
            }
             //end added on 28-jan-25  

              
            $updateStatus = DB::connection('tenant')->table('patient_has_service_reminder')->find($value['id']);

            if($value['appointment_id']==0)
            {
                log::info("updateCount===>");
               log::info($updateCount);   

                if(isset($updateCount))
                {
                    $getReminderCount =  DB::connection('tenant')
                                        ->table('patient_has_service_reminder')
                                        ->select('notification_count','is_sent') 
                                        ->where('id',$value['id'])
                                        ->where('patient_id',$value['patient_id'])
                                        ->where('service_id',$value['service_id'])
                                        ->where('type',$value['type'])
                                        ->where('appointment_id','=',0)
                                        ->whereNull('patient_has_service_reminder.deleted_at') 
                                        ->first();

                    $cnt = $getReminderCount->notification_count; 
                    
                    if(isset($cnt) && $cnt>=0 && !empty($nextReminderDate)) //changed on 4-feb-25
                    {
                           //added on 3-feb-25
                          if(isset($getReminderCount->is_sent) && $getReminderCount->is_sent==0)
                          {
                      
                            // $responseRecord = DB::connection('tenant')
                            //   ->table('patient_has_service_reminder')
                            //   ->where('id',$value['id'])
                            //   ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','notification_count'=>$updateCount,'is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); 


                            $responseRecord = DB::connection('tenant')
                              ->table('patient_has_service_reminder')
                              ->where('id',$value['id'])
                              ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','notification_count'=>$updateCount]); 


                            $responseRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                              ->where('id',$value['id'])
                              ->update(['next_reminder_date'=>$nextReminderDate]);

                          }//added on 3-feb-25

                    }//if cnt
                }//if updatecnt

            }//if value of appoitnment id is 0    
            else
            {
               log::info('else update count');

                /*********start****3-feb-25********************************/

                $getRecord = DB::connection('tenant')
                   ->table('patient_has_service_reminder')
                   ->whereDate('reminder_date', $reminderDate)
                   ->where('patient_id',$value['patient_id'])
                   ->where('service_id',$value['service_id'])
                   ->where('appointment_id',$value['appointment_id'])
                   ->where('type',$value['type'])
                   ->where('is_sent',0)
                   ->whereNull('deleted_at')
                   ->first(); 

              
               //log::info($getRecord);    

                if(isset($getRecord) && !empty($getRecord)){

                    log::info(" in getRecord==>");

                    $responseRecord = DB::connection('tenant')
                   ->table('patient_has_service_reminder')
                   ->whereDate('reminder_date', $reminderDate)
                   ->where('patient_id',$value['patient_id'])
                   ->where('service_id',$value['service_id'])
                   ->where('appointment_id',$value['appointment_id'])
                   ->where('type',$value['type'])
                   ->whereNull('deleted_at')
                   // ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); 
                   ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail']); 
                }
                /*********end*****3-feb-25******************************/
            }//else  
        }//if valid email
    }//_sendMail



    /*-----------------------------------
    |  Send sms
    -------------------------------------------------*/
    // public function _sendSms($phones,$value,$updateCount,$nextReminderDate) //commented on 22-jan-25
    public function _sendSms($phones,$value,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn) //reminderDate on 22-jan-25 added ordname and fqdn on 28-jan-25
    {
        log::info('in send sms function....');

        $reminderDate = date("Y-m-d",strtotime($reminderDate)); //added on 22-jan-25
        log::info($reminderDate);

        $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                   ->where('id','1')
                   ->first();


        //added on 28-jan-25           
        if(isset($fqdn) && !empty($fqdn)){
             $URL= $fqdn.'/oa/services/'.base64_encode($value['service_id']);  
        }else{
            $URL='https://puregyn.puremed.biz/oa/services/'.base64_encode($value['service_id']);
        }           
                
        $text   = $textContent->reminder_sms_notification_text." ".$URL."\n\n\r\r".$value['name'];

        if($value['appointment_id']==0)
        {
           if(isset($updateCount))
           {

              $getReminderCount =  DB::connection('tenant')
                                    ->table('patient_has_service_reminder')
                                    // ->select('notification_count')//commented on 3-feb-25
                                     ->select('notification_count','is_sent')  //added on 3-feb-25
                                    ->where('id',$value['id'])
                                    ->where('patient_id',$value['patient_id'])
                                    ->where('service_id',$value['service_id'])
                                    ->where('type',$value['type'])
                                    ->where('appointment_id','=',0)
                                    ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                    ->first();

                $cnt = $getReminderCount->notification_count; 

                // if(isset($cnt) && $cnt>0 && !empty($nextReminderDate))//commented on 4-feb-25
                if(isset($cnt) && $cnt>=0 && !empty($nextReminderDate))//changed on 4-feb-25
                {
                     
                   
                     //start added on 3-feb-25
                    if(isset($getReminderCount->is_sent) && $getReminderCount->is_sent==0)
                    {  
                        $responseRecord = DB::connection('tenant')
                          ->table('patient_has_service_reminder')
                          ->where('id',$value['id'])
                          // ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms','notification_count'=>$updateCount,'is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]);
                          ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms','notification_count'=>$updateCount]);

                        $responseRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                          ->where('id',$value['id'])
                          ->update(['next_reminder_date'=>$nextReminderDate]);  

                    }//if   
                    //end added on 3-feb-25
                }//if cnt

           }//if 
        }//if value of appoitnment id is 0    
        else
        {      

             log::info('sms else update count appid is 0');

            /*********start****3-feb-25********************************/

                $getRecord = DB::connection('tenant')
                   ->table('patient_has_service_reminder')
                   ->whereDate('reminder_date', $reminderDate)
                   ->where('patient_id',$value['patient_id'])
                   ->where('service_id',$value['service_id'])
                   ->where('appointment_id',$value['appointment_id'])
                   ->where('type',$value['type'])
                   ->where('is_sent',0)
                   ->whereNull('deleted_at')
                   ->first(); 

                if(isset($getRecord) && !empty($getRecord))
                {

                    $responseRecord = DB::connection('tenant')
                   ->table('patient_has_service_reminder')
                   ->whereDate('reminder_date', $reminderDate)
                   ->where('patient_id',$value['patient_id'])
                   ->where('service_id',$value['service_id'])
                   ->where('appointment_id',$value['appointment_id'])
                   ->where('type',$value['type']) 
                   // ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); 
                    ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms']); 

                }//if getRecord
            /*********end*****3-feb-25******************************/
        }//else    
    }//_sendSms


    //added on 28-march-24
    public function _getDate($start_date,$period,$frequency_type)
    {
        $days = 0;
        switch ($frequency_type) 
        {
            case "day":
                $days = (int)$period;
            break;
            case "month":
                $days = 30 * (int)$period;
            break;
            case "year":
                $days = 365 * (int)$period;
            break;
            case "week":
                $days = 7 * (int)$period;
            break;
        }        
        return $days;
    }//_getDate





}
