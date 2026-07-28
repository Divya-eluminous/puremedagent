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

use App\Mail\ReminderNotificationMail; //added on 4-june-24 (13-june-24)

use DateTime; //added on 5-july-24
use Stancl\Tenancy\Facades\Tenancy;


class ReminderNotificationNew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Hyn Tenancy signature (commented out)
    // protected $signature = 'remindernew:daily {--website_id=}';
    
    // Stancl Tenancy signature
    protected $signature = 'remindernew:daily {--tenant_id=}';

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
        // Stancl Tenancy
        $tenant_id = $this->option('tenant_id');
        //log::info("tenant_id=in handle function=of ReminderNotificationNew=====>");
        //log::info($tenant_id);
        
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
 
    public function _commandOperation($tenant_id)
    //public function _commandOperation($website_id)
    //public function handle()
    {
        //log::info("tenant_id=in commandoperation function==>");
        //log::info($tenant_id);

        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        $domain = \App\Models\Domain::where('tenant_id', $tenant_id)->first();
        if($tenant) {
            //Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
           // Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }

        //log::info("In ReminderNotification _commandOperation function ..........");


        //start code added on 28-jan-25 for ordination name and url (updated for Stancl Tenancy)
        $fqdn=$hostName=$ordinationName='';
        
        // Stancl Tenancy - Get tenant information
        if($tenant) {
            //Log::info(" in not empty tenant==>");

            $ordinationName = $tenant->ordination_name;
            $hostName = $domain->domain ?? '';
            
            if(!empty($hostName)) {
                //Log::info("hostName==>");
               // Log::info($hostName);
              
                $fqdn = "https://".$hostName;
                //Log::info("fqdn==>");
                //Log::info($fqdn);
            }

            //Log::info("ordinationName==>");
            //Log::info($ordinationName); 
        }
        //end code added on 28-jan-25 for ordination name and url (updated for Stancl Tenancy)
        
         

        $is_reminder_execute = DB::connection('tenant')->table('settings')->where(['setting_key'=>'REMINDER_SETTING','status'=>'1'])->first();
        $channel = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->select('choice_of_channels')
                       ->first();

        


        //log::info(date('Y-m-d'));
        //log::info(date('H:i'));
               

        if(!empty($is_reminder_execute))
        {
             //client 33890
            
           $collections =  DB::connection('tenant')->table('patient_has_service_reminder')
                        ->join('patients', 'patients.id' , '=', 'patient_has_service_reminder.patient_id')
                        ->join('examinations','examinations.id','patient_has_service_reminder.service_id')

                        // ->whereRaw(DB::raw('(patient_has_service_reminder.reminder_date between "'.date('Y-m-d H:i:00', (time() - 60 * 5)).'" and "'.date('Y-m-d H:i:00', (time() + 60 * 5)).'")'))

                        ->where(function($query) {
                                    $query->where(function($query) {
                                        $query->whereDate('patient_has_service_reminder.reminder_date', '=', date('Y-m-d'));
                                              // ->whereTime('patient_has_service_reminder.reminder_date', '=', date('H:i'));
                                    })
                                    ->orWhere(function($query) {
                                        $query->whereDate('patient_has_service_reminder.next_reminder_date', '=', date('Y-m-d'));
                                              // ->whereTime('patient_has_service_reminder.next_reminder_date', '=', date('H:i'));
                                    });
                        })


                        ->where('patient_has_service_reminder.reminder_status','Set')

                        
                        //  ->where('patients.id',35) //live commented by vijay 4/4/2024
                        //  ->whereIn('patients.id',[56528,56529]) //added by vijay 4/4/2024 for testing
                         ->where('patient_has_service_reminder.is_sent',0) 


                        // ->where('patient_has_service_reminder.type','age')//commented on 19-apr-24
                        ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                         ->whereNull('patients.deleted_at')  
                         ->where('patients.status',1) //added on 24-july-25


                         // ->groupBy('patient_has_service_reminder.patient_id') //commented on 27-june-24
                          ->orderBy('patient_has_service_reminder.id','desc')
                        ->get(['patient_has_service_reminder.id as reminder_id','patient_has_service_reminder.reminder_date','patients.id as patient_id','patients.mobile_no','patients.age as patient_age','patients.country_code','patients.email','examinations.name as name','examinations.id as exam_id','patients.sendMail','patients.sendSMS',
                             'patient_has_service_reminder.appointment_id', //added on 26-march-24 for #2 issue only send active notification
                            'patient_has_service_reminder.status',  //added on 26-march-24 for #2 issue only send active notification
                            'patient_has_service_reminder.reminder_status', //added on 26-march-24 for #2 issue only send active notification
                             'patient_has_service_reminder.service_id',  //added on 28-march-24 for #2 issue only send active notification
                              'patient_has_service_reminder.type',  
                              'patient_has_service_reminder.next_reminder_date',
                       ]);

            //log::info("Send Notify before collections");
            // dump($collections);

             // $currentDate = Date('d-m-Y H:i');    //commented on 8-apr-24  

              $currentDate = Date('d-m-Y');    //8-apr-24  

            // $currentDate = Date('09-04-2024');    



             //log::info("currentDate===>");  
             //log::info($currentDate);   


 
            if(!empty($collections))
            {
                foreach ($collections as $key => $value)
                {

                    // log::info('reminder_id');
                   //  log::info($value->reminder_id);

                    $patientid= $value->patient_id;

                    //commented on 13-may-25   
                    
                    /*$getRemider = DB::connection('tenant')->select(DB::raw("SELECT t1.*,patients.mobile_no,patients.age,patients.country_code,patients.email,patients.sendMail,patients.sendSMS,patients.birth_date,examinations.name FROM patient_has_service_reminder t1
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
                                    ORDER by t1.id DESC")); */


                     //did changes in below query for general reminder condition on 13-may-25               
                     $getRemider = DB::connection('tenant')->select("SELECT t1.*,patients.mobile_no,patients.age,patients.country_code,patients.email,patients.sendMail,patients.sendSMS,patients.birth_date,examinations.name FROM patient_has_service_reminder t1
                                    JOIN patients on patients.id=t1.patient_id 
                                    AND patients.deleted_at IS NULL
                                    JOIN examinations on examinations.id=t1.service_id 
                                    -- and show_as_reminder='1'
                                    -- AND examinations.deleted_at IS NULL

                                     AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))

                                    JOIN (SELECT service_id,reminder_date,next_reminder_date, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL

                                      -- AND (DATE(t2.reminder_date) = CURDATE() OR DATE(t2.next_reminder_date) = CURDATE())
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC");                                                               
                        

                    $getRemider = collect($getRemider)->map(function($x){ 
                        return (array) $x; 
                    })->toArray();    

                   //  log::info("getRemider=======>");
                   //  log::info($getRemider);


                    if(!empty($getRemider) && sizeof($getRemider)>0)
                    {     


                        //Added by Shyam 01-02-22
                        // log::info("Send Notify");

                        // Below condition added on 26-march-24 for active services needs to have the reminder send

                        // $reminderDate = Date('d-m-Y H:i',strtotime($value->reminder_date)); //commeted H:i on 8-apr-24

                        foreach ($getRemider as $key_rem => $value_rem) 
                        {



                                $reminderDate = Date('d-m-Y',strtotime($value_rem['reminder_date']));    

                               // log::info($reminderDate);

                                $nextReminderDate='';


                                // $status =  'aktiv'; //commented on 2-apr-25
                                 $status =  'inaktiv'; //changed on 2-apr-25

                               //commented on 2-apr-25  
                               /* if((strtotime($reminderDate) < strtotime($currentDate)) || $value_rem['appointment_id']==0)
                                {
                                    $status =  'aktiv';
                                }*/


                                //start added code on 2-apr-25  
                                $reminderDateToCompare = Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date']));

                                $currentDateToCompare = Date('Y-m-d H:i:s');

                                //log::info($reminderDateToCompare);
                                //log::info($currentDateToCompare);


                                if((strtotime($reminderDateToCompare) < strtotime($currentDateToCompare)) || $value_rem['appointment_id']==0)
                                {
                                    //log::info("in 1");

                                    $status =  'aktiv';
                                }

                                if((strtotime($reminderDateToCompare) > strtotime($currentDateToCompare)) && $value_rem['cycle_no']!=1 || $value_rem['appointment_id']==0)
                                {
                                    //log::info("in 2 ");
                                    $status =  'aktiv';
                                }
                                //end added code on 2-apr-25 


                                //added on 18-apr-25
                                if($value_rem['status'] == 'deactivate')
                                {
                                    $status = 'inaktiv';
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

                               

                                // log::info("service====>");
                                // log::info($value_rem['service_id']);
                                // log::info("status====>");
                                // log::info($status);

                                if($status=="aktiv") //if status active condition added on 26-march-24
                                {
                                      //log::info("in active status====>");


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

                                    //commented on 5-july-24
                                    // if(!empty($value_rem['age']) && $ageFrom > 0 && $ageTo > 0 && ($value_rem['age'] < $ageFrom || $value_rem['age'] > $ageTo))
                                    // {
                                    //     $checkReminder = 'Not Send';
                                    // }

                                    //changed on 5-july-24
                                    if(!empty($age) && $ageFrom > 0 && $ageTo > 0 && ($age < $ageFrom || $age > $ageTo))
                                    {
                                        $checkReminder = 'Not Send';
                                    }

                                   // log::info($age);


                                    //log::info("Send Notify");
                                    //Added by Shyam 01-02-22
                                    $reminder_active = DB::connection('tenant')->table('patients')->where(['id'=>$value_rem['patient_id'],'reminder_active'=>'1'])->first();

                                    //dump($value->appointment_id);

                                     


                                    $sendEmailFlag=$sendSmsFlag=$updateCount=0;


                                     // log::info("next_reminder_date");
                                     // log::info($value_rem['next_reminder_date']);
                                     // log::info('appid 0 or not');
                                     // log::info($value_rem['appointment_id']);

                                     if(empty($value_rem['next_reminder_date']) && $value_rem['appointment_id']==0)
                                    {
                                        //log::info('in 1');

                                        // $reminderDate = Date('d-m-Y H:i',strtotime($value->reminder_date)); //commeted H:i on 8-apr-24

                                         $reminderDate = Date('d-m-Y',strtotime($value_rem['reminder_date'])); 


                                    }
                                    else if($value_rem['appointment_id']==0)
                                    {
                                         //log::info('in 2');
                                        // $reminderDate = Date('d-m-Y H:i',strtotime($value->next_reminder_date)); //commeted H:i on 8-apr-24

                                        $reminderDate = Date('d-m-Y',strtotime($value_rem['next_reminder_date']));  
                                    }
                                    else
                                    {
                                        // log::info('in 3');
                                        // $reminderDate = Date('d-m-Y H:i',strtotime($value->reminder_date)); //commeted H:i on 8-apr-24

                                        $reminderDate = Date('d-m-Y',strtotime($value_rem['reminder_date']));  
                                    }


                                    if($value_rem['appointment_id']==0 && $reminderDate==$currentDate && $value_rem['type']=='age')
                                    {
                                         //log::info("in appointment id 0 and date equal..");

                                        /****start**code for apponitment id**0**flags**************/

                                     

                                            $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                                                   ->where('service_id',$value_rem['service_id'])
                                                                   ->where('activated_reminder',$value_rem['type'])
                                                                   ->first();

                                            if(isset($reminderSetting) && !empty($reminderSetting))
                                            {
                                                $age_number_of_interval = $reminderSetting->age_number_of_interval;   

                                                 // log::info("age_number_of_interval===>");
                                                 // log::info($age_number_of_interval);   

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

                                                 // log::info("cnt===>");
                                                 // log::info($cnt);    

                                                 if($cnt<$age_number_of_interval)
                                                {
                                                    $updateCount = $cnt+1;       

                                                     // log::info("updateCount===>");
                                                     // log::info($updateCount);   

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

                                        //Log::info("else app id not 0");

                                        /*********start*****22-jan-25********************************/
                                        
                                        $reminderDate1 = date("Y-m-d",strtotime($value->reminder_date));
                                        //Log::info($reminderDate1);

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
                                         // Log::info("checkNextReminder reminder date after changes");
                                         // log::info($reminderDate);

                                        /*******end****22-jan-25************************************/

                                        $sendEmailFlag=1;
                                        $sendSmsFlag=1;

                                    }//else

                                  
                                   //  log::info($reminderDate);
                                   // log::info($currentDate);

                                    if($reminder_active && $checkReminder == 'Send' && $reminderDate==$currentDate) //Added by Shyam 01-02-22
                                    {
                                        //Roshani added code here for send notification
                                        $mobileId = DB::connection('tenant')->table('patient_has_device')
                                                                ->where('patient_id',$value->patient_id)
                                                                ->get(['device_id']);
                                        $pushNotification = self::_sendPushNotification($mobileId,$value,$ordinationName,$updateCount,$nextReminderDate,$reminderDate,$fqdn,$value_rem);
                                        if (isset($pushNotification['success']) && $pushNotification['success'] === true)
                                        {
                                        } //pushNotification if
                                        else
                                        {
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
                                                        //log::info('in send sms 1');

                                                        //commented on 22-jan-25
                                                        // self::_sendSms($phone_no,$value_rem,$updateCount,$nextReminderDate); // 8-apr-24

                                                        //changed on 22-jan-25

                                                        // self::_sendSms($phone_no,$value_rem,$updateCount,$nextReminderDate,$reminderDate); //commented on 28-jan-25

                                                        self::_sendSms($phone_no,$value_rem,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn); //added on 28-jan-25



                                                        

                                                    }//if sendSmsFlag 1

                                                    
                                                }
                                                elseif (!empty($value_rem['email']) && $value_rem['sendMail']==1 && $this->isValidEmail($value_rem['email']))
                                                {
                                                    if($sendEmailFlag==1)
                                                    {
                                                        // log::info('in send email 1');

                                                        //commented on 22-jan-25 
                                                        //self::_sendMail($value_rem,$updateCount,$nextReminderDate); //8-apr-24

                                                        //changed on 22-jan-25

                                                        // self::_sendMail($value_rem,$updateCount,$nextReminderDate,$reminderDate);//commented on 28-jan-25

                                                        self::_sendMail($value_rem,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn);//changed on 28-jan-25

                                                    }//if condition added on 28-march-24
                                                    
                                                }
                                            }
                                            elseif($channel->choice_of_channels == 'email')
                                            {
                                            
                                                //log::info('in send mail call....');


                                                if (!empty($value_rem['email']) && $value_rem['sendMail']==1 && $this->isValidEmail($value_rem['email']))
                                                {


                                                    if($sendEmailFlag==1)
                                                    {
                                                        //log::info('in send email 1');

                                                    //commented on 22-jan-25  
                                                    // self::_sendMail($value_rem,$updateCount,$nextReminderDate); 

                                                    //changed on 22-jan-25   

                                                    // self::_sendMail($value_rem,$updateCount,$nextReminderDate,$reminderDate); //commented on 28-jan-25

                                                        self::_sendMail($value_rem,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn);//changed on 28-jan-25   

                                                    }//if condition added on 28-march-24   


                                                }
                                                elseif (!empty($value_rem['mobile_no']) && $value_rem['sendSMS']==1) //uncommented condition on 28-march-24
                                                {
                                                    // if (!empty($value->mobile_no) && $value->sendSMS==1) //For testing only
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
                                                        //log::info('in send sms 1');
                                                            
                                                    //commented on 22-jan-25      
                                                    // self::_sendSms($phone_no,$value_rem,$updateCount,$nextReminderDate); //8-apr-24

                                                    //changed on 22-jan-25    
                                                    // self::_sendSms($phone_no,$value_rem,$updateCount,$nextReminderDate,$reminderDate); //commented on 28-jan-25 

                                                    self::_sendSms($phone_no,$value_rem,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn); //changed on 28-jan-25 

                                                    }//if sendSmsFlag


                                                }
                                            }//else if

                                        }//pushNotification if else

                                        // log::info('in .send now.................');

                                        //  log::info("Patient id====>");
                                        //  log::info($value_rem['patient_id']);

                                        // start commented on 28-apr-25 
                                        // check patinet have installed app
                                        /*$mobileId = DB::connection('tenant')->table('patient_has_device')
                                                    ->where('patient_id',$value_rem['patient_id'])
                                                    ->get(['device_id']);
                                        if(!empty($mobileId) && count($mobileId))
                                        {
                                            //PUSHNOTIFICATION

                                          if($sendSmsFlag==1)
                                          { 
                                             log::info('in send push 1');

                                        
                                            self::_sendPushNotification($mobileId,$value,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn);


                                          }//if _sendPushNotification

                                        }*/
                                        // end commented on 28-apr-25 


                                        
                                    }//if
                                    else{
                                       // dump('in else of reminder active.........');
                                    }

                                }//if status is active condition added on 26-march-24
                        }//foreach get reminder
                    }//if getreminder

                }//foreach
            }//if
        }//if reminder execute
    }//_commandOperation 

     /*-----------------------------------
    |  Send push notification
    -------------------------------------------------*/
    public function _sendPushNotification($mobileId,$value,$ordinationName,$updateCount,$nextReminderDate,$reminderDate,$fqdn,$value_rem)
    {
        $errorMsg = '';
        log::info('in _sendPushNotification function....');
        //Roshani added code here for send notification
        if(isset($value_rem))
        {
            // Examination Details
            $exams= [];
            $exam_name = $value_rem['name'];
            //$title = 'Erinnerung an Ihren Termin'; //commented on 8-may-25
            $title = 'Ihre Vorsorgeempfehlung von '.$ordinationName;  //changed on 8-may-25
            // Patint Details
            $patientDetails = DB::connection('tenant')->table('patients')->find($value_rem['patient_id']);
            $patient_name='';    
            if(isset($patientDetails))
            {
                $patient_name = $patientDetails->first_name .' '.$patientDetails->family_name;
            }
            // GET CONTENT
            $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                           ->where('type','global')
                           ->first();
            $content = 'Hallo '.$patient_name.', '.$textContent->reminder_push_notification_text." ".$exam_name;
            Log::info("content===>".$content);
            //code for sending push noti    
            if(!empty($mobileId) && !empty($content))
            {
                $mobile_uuids = array_column($mobileId->toArray(), "device_id");
                
                $player_ids   = $mobile_uuids;
                $headings     = array("en" => (string)$title);
                // Create an single string of all content
                $content      = array(
                                        "en" => (string)$content
                                    );
                // Reminder details
                
                $postData = array(
                                "appointment_id" => $value_rem['appointment_id'],
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

                $checkNotificationSend = json_decode($data, true);
                
                if (isset($checkNotificationSend['id']) && !empty($checkNotificationSend['id'])) 
                {
                    $reminderDate = date("Y-m-d",strtotime($reminderDate));//added on 22-jan-25

                    $updateStatus = DB::connection('tenant')->table('patient_has_service_reminder')->find($value_rem['id']);
                    
                    if($value_rem['appointment_id']==0)
                    {
                        if(isset($updateCount))
                        {

                            //commented on 3-feb-25
                                /*$responseRecord = DB::connection('tenant')
                                ->table('patient_has_service_reminder')
                                ->where('id',$value_rem['id'])
                                ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','notification_count'=>$updateCount,'is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]);*/ 



                                $getReminderCount =  DB::connection('tenant')
                                                                        ->table('patient_has_service_reminder')
                                                                        // ->select('notification_count') //commented on 3-feb-25
                                                                        ->select('notification_count','is_sent')  //added on 3-feb-25
                                                                        ->where('id',$value_rem['id'])
                                                                        ->where('patient_id',$value_rem['patient_id'])
                                                                        ->where('service_id',$value_rem['service_id'])
                                                                        ->where('type',$value_rem['type'])
                                                                        ->where('appointment_id','=',0)
                                                                        ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                                        ->first();

                                $cnt = $getReminderCount->notification_count; 
                                
                                // if(isset($cnt) && $cnt>0 && !empty($nextReminderDate))//commnted on 4-feb-25
                                if(isset($cnt) && $cnt>=0 && !empty($nextReminderDate)) //changed on 4-feb-25
                                {
                                    //commented on 3-feb-25
                                    /* $responseRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where('id',$value_rem['id'])
                                        ->update(['next_reminder_date'=>$nextReminderDate]);*/

                                        //added on 3-feb-25
                                        if(isset($getReminderCount->is_sent) && $getReminderCount->is_sent==0)
                                        {

                                        //$result = Mail::to($email)->send(new ReminderNotificationMail($name,$text,$ordinationName));//commented on 5-feb-25

                                        $responseRecord = DB::connection('tenant')
                                            ->table('patient_has_service_reminder')
                                            ->where('id',$value_rem['id'])
                                            ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'PN','notification_count'=>$updateCount,'is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); 

                                        $responseRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                                            ->where('id',$value_rem['id'])
                                            ->update(['next_reminder_date'=>$nextReminderDate]);
                                        }
                                        //added on 3-feb-25
                                }//if cnt
                        }//if updatecnt
                    }//if value of appoitnment id is 0    
                    else
                    {
                        $getRecord = DB::connection('tenant')
                            ->table('patient_has_service_reminder')
                            ->whereDate('reminder_date', $reminderDate)
                            ->where('patient_id',$value_rem['patient_id'])
                            ->where('service_id',$value_rem['service_id'])
                            ->where('appointment_id',$value_rem['appointment_id'])
                            ->where('type',$value_rem['type'])
                            ->where('is_sent',0)
                            ->whereNull('deleted_at')
                            ->first(); 

                        if(isset($getRecord) && !empty($getRecord)){

                            $responseRecord = DB::connection('tenant')
                            ->table('patient_has_service_reminder')
                            ->whereDate('reminder_date', $reminderDate)
                            ->where('patient_id',$value_rem['patient_id'])
                            ->where('service_id',$value_rem['service_id'])
                            ->where('appointment_id',$value_rem['appointment_id'])
                            ->where('type',$value_rem['type'])
                            ->whereNull('deleted_at')
                            ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'PN','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); 
                        }
                    }//else value of appoitnment id is 0  
                    $successMsg = "Push Notification sent successfully. ID: " . $checkNotificationSend['id'];
                    Log::info($successMsg);
                    curl_close($ch);
                    return ['success' => true, 'message' => $successMsg, 'id' => $checkNotificationSend['id']];
                } else {
                    log::info('in failed else....');

                    $errorMsg = "Failed to send Push Notification.";
                    if (isset($checkNotificationSend['errors'])) {
                        $errorMsg .= " Errors: " . json_encode($checkNotificationSend['errors']);
                    }
                    Log::error($errorMsg);
                    curl_close($ch);
                    return ['success' => false, 'message' => $errorMsg, 'response' => $checkNotificationSend];
                }
            }//if player ids
        }//if isset value collection                
        //Roshani added code here for send notification
    }//_sendPushNotification


    function isValidEmail($email)
    { 
          return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }


    // public function _sendMail($value,$updateCount,$nextReminderDate) //commented on 22-jan-25
    public function _sendMail($value,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn) //remindate on 22-jan-25 //added ordiname and fqdn on 28-jan-25
    {
        //log::info('in send mail function....');

        $reminderDate = date("Y-m-d",strtotime($reminderDate));//added on 22-jan-25
        //log::info($reminderDate);

        $patientDetails = DB::connection('tenant')->table('patients')->find($value['patient_id']);
        $name = $patientDetails->first_name.' '.$patientDetails->family_name;
        $email = $patientDetails->email;

         //log::info($email);

         //log::info("check is valid email");
         //log::info($this->isValidEmail($email));

        if($this->isValidEmail($email))
        {
              //log::info("valid email");

                $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                               ->where('type','global')
                               ->first();

                // $text   = $textContent->reminder_mail_notification_text."<br/><b><a href='https://puregyn.puremed.biz/'>".$value->name."</a></b>";


                 //commented on 28-jan-25              
                // $text   = $textContent->reminder_mail_notification_text."<br/><b><a href='https://puregyn.puremed.biz/oa/services/".base64_encode($value['service_id'])."'>".$value['name']."</a></b>";               
            

                 //start added on 28-jan-25           
                if(isset($fqdn) && !empty($fqdn)){
                    //$text   = $textContent->reminder_mail_notification_text."<br/><b><a href='".$fqdn."/oa/services/".base64_encode($value['service_id'])."'>".$value['name']."</a></b>"; 

                    $text = $textContent->reminder_mail_notification_text 
                        . "<br/><b><a href='"
                        . $fqdn . "/oa/services/" 
                        . base64_encode($value['service_id']) . "/" 
                        . base64_encode($value['patient_id']) 
                        . "'>"
                        . $value['name']
                        . "</a></b>";


                }else{
                    //$text   = $textContent->reminder_mail_notification_text."<br/><b><a href='https://puregyn.puremed.biz/oa/services/".base64_encode($value['service_id'])."'>".$value['name']."</a></b>"; 

                    $text = $textContent->reminder_mail_notification_text
                        . "<br/><b><a href='https://puregyn.puremed.biz/oa/services/"
                        . base64_encode($value['service_id']) . "/"
                        . base64_encode($value['patient_id'])
                        . "'>"
                        . $value['name']
                        . "</a></b>";

                }
                 //end added on 28-jan-25  



                 //commented below line on 10-may-24 (13-june-24)
               //  $result = Mail::to($email)->send(new AppointmentMail($name,$text));


                //start added below line on 10-may-24 commented on 28-jan-25
                //$ordinationName=(!empty(config('ordination_name'))) ? ucfirst(config('ordination_name')):'Puregyn';
                //end added below line on 10-may-24
                

                 //changed below line on 10-may-24 (13-june-24)
                //$result = Mail::to($email)->send(new ReminderNotificationMail($name,$text,$ordinationName));//commented on 3-feb-25



                // DB::connection('tenant')->table('ActivityLogModel')->addLog('reminder for email','has sent email','sent',null,$value);
                $updateStatus = DB::connection('tenant')->table('patient_has_service_reminder')->find($value['id']);



                if($value['appointment_id']==0)
                {
                     //log::info("updateCount===>");
                    //log::info($updateCount);   

                    if(isset($updateCount))
                    {

                        //commented on 3-feb-25
                         /*$responseRecord = DB::connection('tenant')
                          ->table('patient_has_service_reminder')
                          ->where('id',$value['id'])
                          ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','notification_count'=>$updateCount,'is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]);*/ 



                            $getReminderCount =  DB::connection('tenant')
                                                                    ->table('patient_has_service_reminder')
                                                                    // ->select('notification_count') //commented on 3-feb-25
                                                                    ->select('notification_count','is_sent')  //added on 3-feb-25
                                                                    ->where('id',$value['id'])
                                                                    ->where('patient_id',$value['patient_id'])
                                                                    ->where('service_id',$value['service_id'])
                                                                    ->where('type',$value['type'])
                                                                    ->where('appointment_id','=',0)
                                                                    ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                                    ->first();

                            $cnt = $getReminderCount->notification_count; 
                            
                            // if(isset($cnt) && $cnt>0 && !empty($nextReminderDate))//commnted on 4-feb-25
                            if(isset($cnt) && $cnt>=0 && !empty($nextReminderDate)) //changed on 4-feb-25
                            {
                                //commented on 3-feb-25
                                /* $responseRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                                  ->where('id',$value['id'])
                                  ->update(['next_reminder_date'=>$nextReminderDate]);*/

                                   //added on 3-feb-25
                                  if(isset($getReminderCount->is_sent) && $getReminderCount->is_sent==0)
                                  {

                                    //$result = Mail::to($email)->send(new ReminderNotificationMail($name,$text,$ordinationName));//commented on 5-feb-25

                                    $responseRecord = DB::connection('tenant')
                                      ->table('patient_has_service_reminder')
                                      ->where('id',$value['id'])
                                      ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','notification_count'=>$updateCount,'is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); 

                                    $responseRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                                      ->where('id',$value['id'])
                                      ->update(['next_reminder_date'=>$nextReminderDate]);

                                        /*******start***added on 5-feb-25***************************/  
                                         $CheckReminderUpdated =  DB::connection('tenant')
                                                                        ->table('patient_has_service_reminder')
                                                                        ->select('is_sent')
                                                                        ->where('id',$value['id'])
                                                                        ->where('patient_id',$value['patient_id'])
                                                                        ->where('service_id',$value['service_id'])
                                                                        ->where('type',$value['type'])
                                                                        ->where('appointment_id','=',0)
                                                                        ->whereNull('patient_has_service_reminder.deleted_at')
                                                                        ->first();   
                                         if(isset($CheckReminderUpdated) && !empty($CheckReminderUpdated)){
                                            if(isset($CheckReminderUpdated->is_sent) && $CheckReminderUpdated->is_sent==1){

                                                 $result = Mail::to($email)->send(new ReminderNotificationMail($name,$text,$ordinationName));//added here on 4-feb-25


                                                /**********5-may-25**push noti****/
                                                    // check patinet have installed app
                                                //     $mobileId = DB::connection('tenant')->table('patient_has_device')
                                                //                 ->where('patient_id',$value['patient_id'])
                                                //                 ->get(['device_id']);
                                                //     //log::info("mobileId");
                                                //    // log::info($mobileId);            
                                                //     if(!empty($mobileId) && count($mobileId))
                                                //     {
                                                //          // log::info(" in mobileId");
                                                //         //PUSHNOTIFICATION
                                                //         // log::info('in send push 1');
                                                //         // self::_sendPushNotification($mobileId,$value,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn);
                                                //         self::_sendPushNotification($mobileId,$value,$ordinationName);
                                                //     }

                                                /**********5-may-25********************/ 





                                            }//if
                                         }//if CheckReminderUpdated                              
                                         /*******end***added on 5-feb-25******************************/  
                                  }
                                  //added on 3-feb-25



                            }//if cnt



                    }//if updatecnt

                    

                }//if value of appoitnment id is 0    
                else
                {
                   // log::info('else update count');

                    

                    /*********commented on*****22-jan-25********************************/
                    /* $responseRecord = DB::connection('tenant')
                      ->table('patient_has_service_reminder')
                      ->where('id',$value['id'])
                      ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); */

                     /*********start*****22-jan-25**commented on 3-feb-25******************************/
  
                    /*$responseRecord = DB::connection('tenant')
                       ->table('patient_has_service_reminder')
                       ->whereDate('reminder_date', $reminderDate)
                       ->where('patient_id',$value['patient_id'])
                       ->where('service_id',$value['service_id'])
                       ->where('appointment_id',$value['appointment_id'])
                       ->where('type',$value['type'])
                       ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); */
                    /*********end*****22-jan-25***commented on 3-feb-25*****************************/


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

                    if(isset($getRecord) && !empty($getRecord)){

                        $responseRecord = DB::connection('tenant')
                       ->table('patient_has_service_reminder')
                       ->whereDate('reminder_date', $reminderDate)
                       ->where('patient_id',$value['patient_id'])
                       ->where('service_id',$value['service_id'])
                       ->where('appointment_id',$value['appointment_id'])
                       ->where('type',$value['type'])
                       ->whereNull('deleted_at')
                       ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); 

                       //$result = Mail::to($email)->send(new ReminderNotificationMail($name,$text,$ordinationName));//comented on 5-feb-25

                        /*******start***added on 5-feb-25***************************/  
                        $CheckReminderUpdated =  DB::connection('tenant')
                                                        ->table('patient_has_service_reminder')
                                                        ->select('is_sent')
                                                        ->whereDate('reminder_date', $reminderDate)
                                                        ->where('patient_id',$value['patient_id'])
                                                        ->where('service_id',$value['service_id'])
                                                        ->where('type',$value['type'])
                                                         ->where('appointment_id',$value['appointment_id'])
                                                        ->whereNull('patient_has_service_reminder.deleted_at')
                                                        ->first();   
                         if(isset($CheckReminderUpdated) && !empty($CheckReminderUpdated)){
                            if(isset($CheckReminderUpdated->is_sent) && $CheckReminderUpdated->is_sent==1){

                                 $result = Mail::to($email)->send(new ReminderNotificationMail($name,$text,$ordinationName));//added here on 4-feb-25


                                 /**********5-may-25**push noti****/
                                    // check patinet have installed app
                                //     $mobileId = DB::connection('tenant')->table('patient_has_device')
                                //                 ->where('patient_id',$value['patient_id'])
                                //                 ->get(['device_id']);

                                //    // log::info("mobileId");
                                //    // log::info($mobileId);

                                //     if(!empty($mobileId) && count($mobileId))
                                //     {
                                //         //log::info("in mobileId");
                                //         //PUSHNOTIFICATION
                                //         // log::info('in send push 1');
                                //          // self::_sendPushNotification($mobileId,$value,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn);
                                //          self::_sendPushNotification($mobileId,$value,$ordinationName);
                                //     }

                                /**********5-may-25********************/ 

                            }//if
                         }//if CheckReminderUpdated                              
                        /*******end***added on 5-feb-25******************************/  
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
        //log::info('in send sms function....');

        $reminderDate = date("Y-m-d",strtotime($reminderDate)); //added on 22-jan-25
       // log::info($reminderDate);

        $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                   ->where('id','1')
                   ->first();
        //$text   = $textContent->reminder_sms_notification_text."\n\n\r\r".$value->name;


        // $URL='https://puregyn.puremed.biz/oa/services/'.base64_encode($value['service_id']); //commented on 28-jan-25

        //added on 28-jan-25           
        if(isset($fqdn) && !empty($fqdn))
        {
         //$URL= $fqdn.'/oa/services/'.base64_encode($value['service_id']);  //commented on 25-nov-25
          $URL = $fqdn . '/oa/services/'. base64_encode($value['service_id']). '/' . base64_encode($value['patient_id']); //added on 25-nov-25

        }
        else
        {
           // $URL='https://puregyn.puremed.biz/oa/services/'.base64_encode($value['service_id']); //commented on 25-nov-25

           $URL = 'https://puregyn.puremed.biz/oa/services/'. base64_encode($value['service_id']). '/' 
        . base64_encode($value['patient_id']); //added on 25-nov-25

        }           
                


            $text   = $textContent->reminder_sms_notification_text." ".$URL."\n\n\r\r".$value['name'];

            //commented on 3-feb-25
            /*if(!empty($phones) && !empty($text))
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
                $updateStatus = DB::connection('tenant')->table('patient_has_service_reminder')->find($value['id']);
                 return $responseRecord;
            }//if phones
            */
           

            if($value['appointment_id']==0)
            {
                
              

               if(isset($updateCount))
               {

                   //commented on 3-feb-25
                    /*$responseRecord = DB::connection('tenant')
                      ->table('patient_has_service_reminder')
                      ->where('id',$value['id'])
                      ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms','notification_count'=>$updateCount,'is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]);*/ 


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
                         
                        //commented on 3-feb-25
                       /* $responseRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                          ->where('id',$value['id'])
                          ->update(['next_reminder_date'=>$nextReminderDate]);*/

                         //start added on 3-feb-25
                        if(isset($getReminderCount->is_sent) && $getReminderCount->is_sent==0)
                        {  
                            $responseRecord = DB::connection('tenant')
                              ->table('patient_has_service_reminder')
                              ->where('id',$value['id'])
                              ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms','notification_count'=>$updateCount,'is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]);

                            $responseRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                              ->where('id',$value['id'])
                              ->update(['next_reminder_date'=>$nextReminderDate]);  

                             /*******start***added on 5-feb-25***************************/  
                                $CheckReminderUpdated =  DB::connection('tenant')
                                                                ->table('patient_has_service_reminder')
                                                                ->select('is_sent')
                                                                ->where('id',$value['id'])
                                                                ->where('patient_id',$value['patient_id'])
                                                                ->where('service_id',$value['service_id'])
                                                                ->where('type',$value['type'])
                                                                ->where('appointment_id','=',0)
                                                                ->whereNull('patient_has_service_reminder.deleted_at')
                                                                ->first();   
                                 if(isset($CheckReminderUpdated) && !empty($CheckReminderUpdated)){
                                    if(isset($CheckReminderUpdated->is_sent) && $CheckReminderUpdated->is_sent==1){
                                        //code for sending sms    
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
                                            $updateStatus = DB::connection('tenant')->table('patient_has_service_reminder')->find($value['id']);

                                                /**********5-may-25**push noti****/
                                                    // check patinet have installed app
                                                    // $mobileId = DB::connection('tenant')->table('patient_has_device')
                                                    //             ->where('patient_id',$value['patient_id'])
                                                    //             ->get(['device_id']);
                                                    // //log::info("mobileId");
                                                    // //log::info($mobileId);            
                                                    // if(!empty($mobileId) && count($mobileId))
                                                    // {
                                                    //      // log::info(" in mobileId");
                                                    //     //PUSHNOTIFICATION
                                                    //     // log::info('in send push 1');
                                                    //     // self::_sendPushNotification($mobileId,$value,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn);
                                                    //     self::_sendPushNotification($mobileId,$value,$ordinationName);
                                                    // }

                                                /**********5-may-25********************/ 


                                            return $responseRecord;
                                        }//if phones
                                        
                                    }//if
                                 }//if CheckReminderUpdated                              
                            /*******end***added on 5-feb-25******************************/

                        }//if   
                        //end added on 3-feb-25
                        

                    }//if cnt



               }//if 

            

            }//if value of appoitnment id is 0    
            else
            {      

                 //log::info('sms else update count appid is 0');
                /*********commented on*****22-jan-25********************************/              
               /* $responseRecord = DB::connection('tenant')
                                  ->table('patient_has_service_reminder')
                                  ->where('id',$value['id'])
                                  ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); */
                                  

                 /*********start*****22-jan-25****commented on 3-feb-25**************/
  
                    
                  /*$responseRecord = DB::connection('tenant')
                   ->table('patient_has_service_reminder')
                   ->whereDate('reminder_date', $reminderDate)
                   ->where('patient_id',$value['patient_id'])
                   ->where('service_id',$value['service_id'])
                   ->where('appointment_id',$value['appointment_id'])
                   ->where('type',$value['type']) 
                   ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); */  
                /*********end*****22-jan-25*****commented on 3-feb-25******************/



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
                       ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms','is_sent'=>1,'sent_date'=>date('Y-m-d H:i:s')]); 

                        
                         /*******start***added on 5-feb-25***************************/  
                            $CheckReminderUpdated =  DB::connection('tenant')
                                                            ->table('patient_has_service_reminder')
                                                            ->select('is_sent')
                                                            ->whereDate('reminder_date', $reminderDate)
                                                            ->where('patient_id',$value['patient_id'])
                                                            ->where('service_id',$value['service_id'])
                                                            ->where('type',$value['type'])
                                                             ->where('appointment_id',$value['appointment_id'])
                                                            ->whereNull('patient_has_service_reminder.deleted_at')
                                                            ->first();   
                             if(isset($CheckReminderUpdated) && !empty($CheckReminderUpdated)){
                                if(isset($CheckReminderUpdated->is_sent) && $CheckReminderUpdated->is_sent==1){

                                      //code for sending sms
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
                                                $updateStatus = DB::connection('tenant')->table('patient_has_service_reminder')->find($value['id']);

                                                /*********5-may-25**push noti****/
                                                    // check patinet have installed app
                                                //     $mobileId = DB::connection('tenant')->table('patient_has_device')
                                                //                 ->where('patient_id',$value['patient_id'])
                                                //                 ->get(['device_id']);
                                                //     //log::info("mobileId");
                                                //    // log::info($mobileId);            
                                                //     if(!empty($mobileId) && count($mobileId))
                                                //     {
                                                //          // log::info(" in mobileId");
                                                //         //PUSHNOTIFICATION
                                                //         // log::info('in send push 1');
                                                //         // self::_sendPushNotification($mobileId,$value,$updateCount,$nextReminderDate,$reminderDate,$ordinationName,$fqdn);
                                                //         self::_sendPushNotification($mobileId,$value,$ordinationName);
                                                //     }

                                                /**********5-may-25********************/


                                                return $responseRecord;
                                        }//if phones
                                }//if
                             }//if CheckReminderUpdated                              
                        /*******end***added on 5-feb-25******************************/ 

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


    public function _sendPushNotification_stage29apr25($mobileId,$value)
    {
        log::info('in _sendPushNotification function....');
         // dump($mobileId);
         // dump($value);
        
  
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

        if(isset($collection))
        {
            $appointment_type   = $collection->aname ?? '';

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
           
            $doctor_image = asset('assets/admin/images/default-image.png');
            if (!empty($collection->img_path) && is_file(storage_path().'/app/'.$collection->img_path)) 
            {
                $doctor_image = url('/storage/app/'.$collection->img_path); 
            }

            $title = 'Erinnerung an Ihren Termin';
            // Patint Details
            $patientDetails = DB::connection('tenant')->table('patients')->find($value['patient_id']);

            $patient_name='';    
            if(isset($patientDetails))
            {
                $patient_name = $patientDetails->first_name .' '.$patientDetails->family_name;
            }

           
            // GET CONTENT
            $textContent = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                           ->where('type','global')
                           ->first();

            $content = 'Hallo '.$patient_name.', '.$textContent->reminder_push_notification_text." ".$exam_name;

            log::info("content");
            log::info($content);
        
            //code for sending push noti    
            if(!empty($player_ids) && !empty($content))
            {

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
            }//if player ids
        }//if isset collection                


       
    }//_sendPushNotification


    public function _sendPushNotification_oldrenamedon25_apr_25($mobileId,$value,$updateCount,$nextReminderDate)
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

}
 