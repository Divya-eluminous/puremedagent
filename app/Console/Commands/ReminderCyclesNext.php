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
use Stancl\Tenancy\Facades\Tenancy;


use App\Mail\ReminderNotificationMail; //added on 4-june-24 (13-june-24)

use DateTime; //added on 5-july-24


class ReminderCyclesNext extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'remindercycle:daily {--website_id=}';
    protected $signature = 'remindercyclenext:daily {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set next reminder cycles of patients';

    //  /**
    //  * @var Connection
    //  */
    // private $connection;

    // /**
    //  * @var WebsiteRepository
    //  */
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

// public function handle1()
public function handle()
{
    //log::info("ReminderStatus-Handle");
    // $website_id = $this->option('website_id');

    // log::info("website_id=in handle function=of ReminderCycles=====>");
    // log::info($website_id);

    // try
    // {

    //     //commented below code for single website
    //     /*if(!empty($website_id) && $website_id!='0')
    //     {
    //         $website = $this->websites->query()->where('id', $website_id)->firstOrFail();

    //         $this->connection->set($website);
    //         self::_commandOperation($website_id);
    //         $this->connection->purge();
    //     }*/

    //     //added below code for multiple websites
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
    //log::info("tenant_id=in handle function=of ReminderCycles=====>");
    //log::info($tenant_id);
    
    try
    {
        if(!empty($tenant_id) && $tenant_id!='0')
        {
            self::_commandOperation($tenant_id);
            
            // Stancl tenancy cleanup
            tenancy()->end();
        }
        else
        {
            // Process all tenants if no specific tenant_id provided
            $tenants = \App\Models\Tenant::all();
            if(isset($tenants) && !empty($tenants)){
                foreach($tenants as $tenant){
                    self::_commandOperation($tenant->id);
                    
                    // Stancl tenancy cleanup for each tenant
                    tenancy()->end();
                }
            }
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
// public function handle()
{
    //log::info("tenant_id=in commandoperation function==>");
    //log::info($tenant_id);

    // Stancl Tenancy - Get tenant and initialize context
    $tenant = \App\Models\Tenant::find($tenant_id);
    if($tenant) {
        //Log::info("Found tenant: " . $tenant->ordination_name);
        tenancy()->initialize($tenant);
        config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
        //Log::info("Tenant context initialized for: " . $tenant->ordination_name);
    }

    log::info("In ReminderCycles Next _commandOperation function ..........");

    $is_reminder_execute = DB::connection('tenant')->table('settings')->where(['setting_key'=>'REMINDER_SETTING','status'=>'1'])->first();
    $channel = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                   ->where('type','global')
                   ->select('choice_of_channels')
                   ->first();
    
   // log::info(date('Y-m-d'));
    //log::info(date('H:i')); 
           

    if(!empty($is_reminder_execute)) 
    {
         //client 33890
        
        //commented on 17-sept-25
        /*$collections =  DB::connection('tenant')->table('reminder_patients')
        ->join('patients', 'patients.id' , '=', 'reminder_patients.patient_id')
        ->join('examinations','examinations.id','reminder_patients.service_id')
        ->where('reminder_patients.status','pending')
        ->whereNull('reminder_patients.deleted_at') 
        ->whereNull('patients.deleted_at')  
         //->where('patients.id',56376)
        //  ->where('service_id',199)
        //->orderBy('reminder_patients.id','asc')
        ->inRandomOrder()
        ->limit(3000)
        ->get([
            'reminder_patients.patient_id',
            'reminder_patients.status', 
            'reminder_patients.service_id',  
            'reminder_patients.type',  
            'reminder_patients.cycle_no',
       ]);*/

       //added on 17-sept-25 // take only pending records who already have any cycle >=2
      $collections = DB::connection('tenant')
            ->table('reminder_patients as r')
            ->join('patients as p', 'p.id', '=', 'r.patient_id')
            ->join('examinations as e', 'e.id', '=', 'r.service_id')
            ->where('r.status', 'pending')
            ->where('r.is_processed', 0)
            ->whereNull('r.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('reminder_patients as r2')
                      ->whereRaw('r2.patient_id = r.patient_id')
                      ->whereRaw('r2.service_id = r.service_id')
                      ->whereRaw('r2.type = r.type')
                      ->where('r2.status', 'completed')
                      ->where('r2.cycle_no', '>=', 2)
                      ->whereNOTNull('r2.deleted_at');
            })
            ->orderBy('r.created_at', 'desc')
            ->limit(1000)
            ->get([
                'r.id',
                'r.patient_id',
                'r.status',
                'r.service_id',
                'r.type',
                'r.cycle_no',
            ]);

        log::info("collections");
        log::info($collections);

      
        $currentDate = Date('Y-m-d H:i:s');
       // log::info("currentDate===>");  
      //  log::info($currentDate);

        if($collections->count() > 0)
        {
             Log::info("in remindercycle next not empty collection");

            foreach ($collections as $key => $value)
            {

                $patientid= $value->patient_id;
                $serviceId = $value->service_id;

                //commented on 21-may-25
                /*$getRemider = DB::connection('tenant')->select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                JOIN examinations on examinations.id=t1.service_id 
                AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder 
                    WHERE patient_id=$patientid

                    AND service_id = $serviceId

                    and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid 
                         AND service_id = $serviceId
                    AND 
                    ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                    and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                WHERE t1.reminder_status IN('Set','ignore') 
                AND t1.patient_id=$patientid
                AND t1.service_id = $serviceId
                and t1.deleted_at is NULL
                GROUP by t1.service_id
                ORDER by t1.id DESC"));*/

                //changed on 21-may-25
                $getRemider = DB::connection('tenant')->select("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                JOIN examinations on examinations.id=t1.service_id 
                AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder 
                    WHERE patient_id=$patientid

                    AND service_id = $serviceId

                    and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid 
                         AND service_id = $serviceId
                    AND 
                    ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                    and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                WHERE t1.reminder_status IN('Set','ignore') 
                AND t1.patient_id=$patientid
                AND t1.service_id = $serviceId
                and t1.deleted_at is NULL
                GROUP by t1.service_id
                ORDER by t1.id DESC");                                                                       
                    

                $getRemider = collect($getRemider)->map(function($x){ 
                    return (array) $x; 
                })->toArray();    

                //log::info("getRemider=======>");
                //log::info($getRemider);

                if(!empty($getRemider) && sizeof($getRemider)>0)
                {    
                    foreach ($getRemider as $key_rem => $value_rem) 
                    {
                        if($value_rem['appointment_id']!=0)
                        {
                            //log::info("innnnnnnnn app id not 0");

                            // log::info("cycle_no===>");  
                            // log::info($value_rem['cycle_no']);

                            // log::info("service_id===>");  
                            // log::info($value_rem['service_id']);



                            //$reminderDate = Date('d-m-Y',strtotime($value_rem['reminder_date'])); 
                            $reminderDate = Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date']));

                            //log::info("reminderDate===>");  
                           // log::info($reminderDate);

                            $status =  'inaktiv';

                            // if((strtotime($reminderDate) < strtotime($currentDate)) || $value_rem['appointment_id']==0)

                            //commented on 16-apr-25
                            /* if(((strtotime($reminderDate) < strtotime($currentDate)) && $value_rem['cycle_no']<2) || $value_rem['appointment_id']==0)
                            {
                                $status =  'aktiv';
                            }
                            if((strtotime($reminderDate) > strtotime($currentDate)) && $value_rem['cycle_no']>=2 || $value_rem['appointment_id']==0)
                            {
                                $status =  'aktiv';
                            }*/

                            //added on 27-march-25
                            if((strtotime($reminderDate) < strtotime($currentDate)) || ((strtotime($reminderDate) > strtotime($currentDate)) &&  $value_rem['appointment_id']!=0 && $value_rem['cycle_no']!=1)  || $value_rem['appointment_id']==0)
                            {
                                $status =  'aktiv';
                            }

                            if($value_rem['reminder_status']=='ignore' && $value_rem['status']!='deactivate')
                            {
                                // $status ='inaktiv'; //commented on 17-apr-25
                                $status='ignored';//added on 17-apr-25
                            } 
                            if($value_rem['reminder_status']=='ignore' && $value_rem['status']=='deactivate')
                            {
                                $status ='inaktiv';
                            }   

                            if($value_rem['appointment_id']==0 && $value_rem['status']=='deactivate')
                            {
                                $status ='inaktiv';
                            }

                           // log::info("status======================>");
                           // log::info($status);

                            if($status=="aktiv")  
                            {

                                $patinet_data = DB::connection('tenant')->table('patients')
                                                        ->where('id',$value_rem['patient_id'])
                                                          ->whereNull('patients.deleted_at')
                                                          ->first();

                                $checkPatientAge = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(['service_id'=>$value_rem['service_id'],'activated_reminder'=>'age'])->first();

                                if($value_rem['type']=='age'){

                                     $ageFrom = $ageTo = 0;$age =0;
                                    if(!empty($checkPatientAge->age_from))
                                    {
                                        $ageFrom = $checkPatientAge->age_from;
                                    }
                                    if(!empty($checkPatientAge->age_to))
                                    {
                                        $ageTo = $checkPatientAge->age_to;
                                    }

                                    if($patinet_data->birth_date) {
                                        $from = new DateTime($patinet_data->birth_date);
                                        $to   = new DateTime('today');
                                        $age =  $from->diff($to)->y;
                                    }
                                    else {
                                        $age =  $value_rem['age'];
                                    }

                                }//if type is age

                                if($value_rem['type']=='age')
                                {
                                    //Log::info("in age..........");


                                    $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                       ->where('service_id',$value_rem['service_id'])
                                       ->where('activated_reminder',$value_rem['type'])
                                       ->first();

                                    //log::info("in age reminderSetting..........");
                                    //log::info($reminderSetting);   

                                  
                                }else if($value_rem['type']=='general')
                                {   
                                     //log::info("in general..........");
                                     // dump("value_rem['service_id']===>");
                                     // dump($value_rem['service_id']);

                                        $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                           ->where(['service_id' => $value_rem['service_id']])
                                           ->where( 'activated_reminder','general')
                                           ->first();

                                        // if(empty($reminderSetting))
                                        // {
                                        //    $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(['type' => 'global',])->first();
                                        // }  

                                        //log::info("in general reminderSetting..........");
                                        //dump($reminderSetting);


                                     
                                        $reminderSetting1 = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                            'recommanded_service_id', $value_rem['service_id'])->where( 'activated_reminder','general')->first();
                                        if(isset($reminderSetting1)){
                                            $reminderSetting = $reminderSetting1;
                                            $service_id = $reminderSetting1->recommanded_service_id;
                                        }else{
                                            
                                            $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                             ['service_id' => $value_rem['service_id']])->first();
                                           $service_id = $value_rem['service_id'];

                                        } 

                                        //log::info("in general service_id..........");
                                        //log::info($service_id);

                                }
                                else if($value_rem['type']=='control')
                                {
                                    //log::info("in control reminder......");
                                    //log::info($value_rem['service_id']);

                                    $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                       ->where(['service_id' => $value_rem['service_id']])
                                       ->where('activated_reminder','checkup')
                                       ->first();

                                    //log::info("in control reminderSetting..........");
                                    //dump($reminderSetting);   

                                    if(empty($reminderSetting))
                                    {
                                       // log::info("in global control reminderSetting..........");
                                       $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(['type' => 'global',])->first();
                                    }   

                                 
                                    //dump($reminderSetting);   
                                        
                                }

                                if(isset($reminderSetting) && !empty($reminderSetting))
                                {
                                    $age_number_of_interval = $reminderSetting->age_number_of_interval;   
                                }//no use

                               

                               // $firstReminderEntryDate = date("Y-m-d",strtotime($value_rem['reminder_date']));

                                //Take patient_has_service_reminder desc order last date entry
                                $getLastDateDescOrder =  DB::connection('tenant')->table('patient_has_service_reminder')
                                               ->where('patient_id',$value_rem['patient_id'])
                                               ->where('service_id',$value_rem['service_id'])
                                               ->where('appointment_id',$value_rem['appointment_id'])
                                               ->orderBy('reminder_date','desc')
                                               ->whereNull('deleted_at')
                                               ->first();

                                //log::info("getLastDateDescOrder");
                                //dump($getLastDateDescOrder);
                                               
                                if(isset($getLastDateDescOrder) && !empty($getLastDateDescOrder) && isset($reminderSetting))
                                {
                                    //log::info("in getLastDateDescOrder");

                                    $lastDate =  Date('Y-m-d H:i:s',strtotime($getLastDateDescOrder->reminder_date));

                                    // log::info("lastDate");
                                    // log::info($lastDate);
                                    // log::info("currentDate");
                                    // log::info($currentDate);

                                    if(isset($lastDate) && $lastDate < $currentDate)
                                    {
                                        // log::info("lastDate is less than current date..");
                                        // log::info($getLastDateDescOrder->id);

                                          //Take patient_has_reminder entry of last date matched for 2nd cycle 1st date
                                         $patient_has_reminder =  DB::connection('tenant')->table('patient_has_reminder')
                                                   ->where('patient_id',$value_rem['patient_id'])
                                                   ->where('service_reminder_id',$getLastDateDescOrder->id)
                                                   ->whereNull('deleted_at')
                                                   ->first();

                                         //log::info("patient_has_reminder");
                                         //log::info($patient_has_reminder);  

                                        if(isset($patient_has_reminder) && !empty($patient_has_reminder))
                                        {
                                            $last_service_reminder_id = $patient_has_reminder->service_reminder_id;

                                            $last_reminder_date = $patient_has_reminder->last_reminder_date;
                                            $next_reminder_date_of_next_cycle = $patient_has_reminder->next_reminder_date;

                                            if($value_rem['type']=='age')
                                            {
                                                if($reminderSetting->age_new_frequency!=0)
                                                {
                                                    $a_date = explode(" ",$next_reminder_date_of_next_cycle);
                                                    $next_start_date = $a_date[0]." ".$reminderSetting->notify_time.":00";

                                                    $this->_ageReminder($reminderSetting,$value_rem['appointment_id'],$next_start_date,$value_rem['patient_id'],$value_rem['service_id'],$last_service_reminder_id);
                                                }//#2 not 0
                                            }//if age
                                            if($value_rem['type']=='general')
                                            {

                                                //log::info("in set general condition");
                                               // log::info($reminderSetting->general_new_frequency);

                                                if($reminderSetting->general_new_frequency!=0)
                                                {
                                                    $a_date = explode(" ",$next_reminder_date_of_next_cycle);
                                                    $next_start_date = $a_date[0]." ".$reminderSetting->notify_time.":00";


                                                     $this->_generalReminder($reminderSetting,$value_rem['appointment_id'],$next_start_date,$value_rem['patient_id'],$service_id,$last_service_reminder_id);
                                                }//#2 not 0
                                                
                                            }//if general
                                            if($value_rem['type']=='control')
                                            {
                                                $is_doctor_set_reminder = DB::connection('tenant')->table('patient_has_service_control_reminder_setting')->where(
                                                        ['patient_id' => $value_rem['patient_id'],
                                                        'appointment_id' => $value_rem['appointment_id'],
                                                        'service_id' => $value_rem['service_id'],
                                                        'status' => '1',
                                                        ]
                                                        )->first();

                                                if($is_doctor_set_reminder)
                                                {
                                                    // $is_service_has_reminder->checkup_period_controls =  $is_doctor_set_reminder->control_interval;
                                                    // $is_service_has_reminder->checkup_period_frequency_type =  $is_doctor_set_reminder->control_frequency;

                                                    if($reminderSetting->checkup_new_frequency!=0)
                                                   {
                                                        $a_date = explode(" ",$next_reminder_date_of_next_cycle);
                                                        $next_start_date = $a_date[0]." ".$reminderSetting->notify_time.":00";
                                                         $this->_controlReminder($reminderSetting,$value_rem['appointment_id'],$next_start_date,$value_rem['patient_id'],$value_rem['service_id'],$last_service_reminder_id);
                                                   }//if checkup_new_frequency 
                                                } 

                                            }//if control

                                        }//if isset patient_has_reminder    
                                    }//last cycle date not passed to calculate next cycle  
                                    else{
                                        //log::info("Not able to calculate the next cycle.");
                                    }   
                                }//if isset getLastDateDescOrder

                            }//if status is active
                                         
                        }//if appid is not 0
                        else
                        {
                            //Do code for app id 0
                             //log::info("in elseeeeeeeeeeee appointment id 0===>");  

                            // log::info("service_id===>");  
                            // log::info($value_rem['service_id']);

                            //$reminderDate = Date('d-m-Y',strtotime($value_rem['reminder_date'])); 
                            $reminderDate = Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date']));

                            // log::info("reminderDate===>");  
                            // log::info($reminderDate);

                            // log::info("currentDate===>");  
                            // log::info($currentDate);

                            // log::info("cycle_no===>");  
                            // log::info($value_rem['cycle_no']);

                            // log::info("appointment_id===>");  
                            // log::info($value_rem['appointment_id']);

                            $status =  'inaktiv';

                            // if((strtotime($reminderDate) < strtotime($currentDate)) || $value_rem['appointment_id']==0)
                             if(((strtotime($reminderDate) < strtotime($currentDate)) && $value_rem['cycle_no']<2) || $value_rem['appointment_id']==0)
                            {
                                //log::info("in 1st");
                                $status =  'aktiv';
                            }
                            if((strtotime($reminderDate) > strtotime($currentDate)) && $value_rem['cycle_no']>=2 || $value_rem['appointment_id']==0)
                            {
                                // log::info("in 2nd");
                                $status =  'aktiv';
                            }
                            if($value_rem['reminder_status']=='ignore' && $value_rem['status']!='deactivate')
                            {
                                // $status ='inaktiv';//commeted on 17-apr-25
                                 $status='ignored';//added on 17-apr-25
                            } 
                            if($value_rem['reminder_status']=='ignore' && $value_rem['status']=='deactivate')
                            {
                                $status ='inaktiv';
                            }   

                            if($value_rem['appointment_id']==0 && $value_rem['status']=='deactivate')
                            {
                                $status ='inaktiv';
                            }

                            //log::info("status======================>");
                            //log::info($status);
                            if($status=="aktiv") 
                            {
                                $patinet_data = DB::connection('tenant')->table('patients')
                                                        ->where('id',$value_rem['patient_id'])
                                                          ->whereNull('patients.deleted_at')
                                                          ->first();
                                $checkPatientAge = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(['service_id'=>$value_rem['service_id'],'activated_reminder'=>'age'])->first();

                                if($value_rem['type']=='age')
                                {

                                    $ageFrom = $ageTo = 0;$age =0;
                                    if(!empty($checkPatientAge->age_from))
                                    {
                                        $ageFrom = $checkPatientAge->age_from;
                                    }
                                    if(!empty($checkPatientAge->age_to))
                                    {
                                        $ageTo = $checkPatientAge->age_to;
                                    }

                                    if($patinet_data->birth_date) {
                                        $from = new DateTime($patinet_data->birth_date);
                                        $to   = new DateTime('today');
                                        $age =  $from->diff($to)->y;
                                    }
                                    else {
                                        $age =  $value_rem['age'];
                                    }

                                }//if type is age

                                if($value_rem['type']=='age')
                                {
                                    $checkReminder = 'Send';
                                    if(!empty($age) && $ageFrom > 0 && $ageTo > 0 && ($age < $ageFrom || $age > $ageTo))
                                    {
                                        $checkReminder = 'Not Send';
                                    }


                                   /* if(empty($value_rem['next_reminder_date']) && $value_rem['appointment_id']==0)
                                    {
                                        log::info('in 1');
                                        $reminderDate = Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date'])); 

                                    }
                                    else if($value_rem['appointment_id']==0)
                                    {
                                       log::info('in 2');
                                       $reminderDate = Date('Y-m-d H:i:s',strtotime($value_rem['next_reminder_date']));  
                                    }
                                    else
                                    {
                                       log::info('in 3');
                                       $reminderDate = Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date']));  
                                    }*/

                                     $reminderDate = Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date'])); 


                                   $checkNextReminders =  DB::connection('tenant')
                                    ->table('patient_has_service_reminder')
                                    ->where('patient_id',$value_rem['patient_id'])
                                    ->where('service_id',$value_rem['service_id'])
                                    ->where('type',$value_rem['type'])
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('patient_has_service_reminder.deleted_at') 
                                    ->count(); 

                                    //log::info("checkNextReminders======================>");
                                    //log::info($checkNextReminders); 

                                    //if($checkNextReminders==0) 
                                    //{
                                        $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                       ->where('service_id',$value_rem['service_id'])
                                       ->where('activated_reminder',$value_rem['type'])
                                       ->first();

                                        //log::info("reminderSetting======================>");
                                        //dump($reminderSetting);   

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
                                            ->whereNull('patient_has_service_reminder.deleted_at') 
                                            ->first();

                                            $cnt = $getReminderCount->notification_count;

                                            //log::info("notification_count===>");
                                            //log::info($cnt);  

                                            if($cnt<=$age_number_of_interval)
                                            {
                                                $age_time_interval = $reminderSetting->age_time_interval;
                                                $age_time_interval_frequency = $reminderSetting->age_time_interval_frequency_type;

                                                if($age_number_of_interval>0){
                                                    $totalAgeNumberOfInterval = $age_number_of_interval-1;
                                                }else{
                                                    $totalAgeNumberOfInterval = $age_number_of_interval;
                                                }
                                                

                                                //log::info("totalAgeNumberOfInterval===>");
                                                //log::info($totalAgeNumberOfInterval); 

                                                $period_date = Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date']));   

                                                $next_reminder_date = Date('Y-m-d H:i:s',strtotime($value_rem['next_reminder_date']));   

                                                $value4_days = $this->_getDate($period_date,$age_time_interval,$age_time_interval_frequency);

                                                $value4_days = $totalAgeNumberOfInterval*$value4_days;
                                                //log::info("value4_days===>");
                                                //log::info($value4_days); 

                                                //log::info("reminderDate===>");
                                                //log::info($reminderDate); 

                                                //calculate next_reminder_date column value here
                                                $lastNextReminderDate = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($reminderDate)) . " +".(int)$value4_days." day"));

                                                //log::info("lastNextReminderDate===>");
                                               // log::info($lastNextReminderDate); 


                                                if(!empty($lastNextReminderDate))
                                                {
                                                    //check if next_reminder_date column value is passed then calculate next cycle date
                                                    if($lastNextReminderDate<$currentDate)
                                                    {
                                                        //calculate next cycle
                                                        //log::info('calculate next cycle here in empty next reminder date.........');

                                                        $value5_days = $this->_getDate($period_date,$reminderSetting->age_new_frequency,$reminderSetting->age_new_frequency_type);

                                                        //log::info("value5_days");
                                                        //log::info($value5_days);

                                                      
                                                        $reactive_reminder = $this->_filterWeekendAndHoiliday($period_date,$value5_days,$reminderSetting->holiday_reminder,'plus');//next cycle date

                                                        //log::info("reactive_reminder");
                                                        //log::info($reactive_reminder);

                                                        //Check last cycle
                                                        $getLastCycleNo =  DB::connection('tenant')->table('patient_has_service_reminder')
                                                            ->where('patient_id',$value_rem['patient_id'])
                                                            ->where('service_id',$value_rem['service_id'])
                                                            ->where('appointment_id',0)
                                                            ->where('id',$value_rem['id'])
                                                            ->whereNull('deleted_at')
                                                            ->first(['cycle_no']);

                                                        if(isset($getLastCycleNo)) 
                                                        {
                                                            $lastCycleNo = $getLastCycleNo->cycle_no;
                                                            $cycle_no = $lastCycleNo+1;
                                                        }               
                                                        //log::info("getLastCycleNo===>");                
                                                       // log::info($getLastCycleNo);

                                                        $reminder_tmp = [];
                                                        $reminder_tmp['patient_id'] = $value_rem['patient_id'];
                                                        $reminder_tmp['appointment_id'] = 0;
                                                        $reminder_tmp['service_id'] = $value_rem['service_id'];
                                                        $reminder_tmp['reminder_date'] = $reactive_reminder;
                                                        $reminder_tmp['reminder_status'] = 'Set';
                                                       
                                                        $reminder_tmp['status'] = 'activate';  
                                                        $reminder_tmp['type'] = 'age';
                                                        $reminder_tmp['created_at'] = date('Y-m-d H:i:s') ;
                                                        $reminder_tmp['cycle_no'] = $cycle_no;
                                                        

                                                        //log::info("reminder_tmp");
                                                       // log::info($reminder_tmp);

                                                        $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                                        ->where('patient_id', $value_rem['patient_id'])
                                                        ->where('appointment_id', 0)
                                                        ->where('service_id', $value_rem['service_id'])
                                                        ->where('reminder_date', $value_rem['reminder_date'])
                                                        ->where('id',$value_rem['id'])
                                                        ->where('reminder_status', 'Set')
                                                        ->where('status', 'activate')
                                                        ->where('type', 'age')
                                                        ->whereNull('deleted_at')
                                                        ->count();

                                                          // log::info("is_exists");
                                                          // log::info($is_exists);

                                                        if($is_exists == 1)
                                                        {
                                                            //log::info("in is_exists");

                                                            $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                                                                ->where('patient_id',$value_rem['patient_id'])
                                                                ->where('service_id',$value_rem['service_id'])
                                                                ->where('appointment_id',0)
                                                                ->where('id',$value_rem['id'])
                                                                ->where('cycle_no',1) 
                                                                ->first();


                                                            if(!empty($firstReminderdate)) 
                                                                $first_cycle_remidner_date=$firstReminderdate->reminder_date;
                                                            else $first_cycle_remidner_date=$reactive_reminder;

                                                            //log::info("first_cycle_remidner_date==>");
                                                            //log::info($first_cycle_remidner_date);

                                                             $endCycleDyas = $this->_getDate(($first_cycle_remidner_date),$reminderSetting->age_end_cycle,$reminderSetting->age_end_cycle_frequency_type);  //added new code on 27-march-25

                                                           // log::info("endCycleDyas==>");
                                                          //  log::info($endCycleDyas);

                                                            $ignoreStateEndCycleDate = $this->_filterWeekendAndHoiliday(($first_cycle_remidner_date),$endCycleDyas,$reminderSetting->holiday_reminder,'plus');

                                                            //  log::info("reactive_reminder==>");
                                                            // log::info($reactive_reminder);

                                                            // log::info("ignoreStateEndCycleDate==>");
                                                            // log::info($ignoreStateEndCycleDate);

                                                            // if($reactive_reminder>=$ignoreStateEndCycleDate) //commented on 17-apr-25
                                                            if($ignoreStateEndCycleDate < $currentDate)//added on 17-apr-25
                                                            {
                                                                //log::info("in ignore state");
                                                            }
                                                            else
                                                            {
                                                                //log::info("not in ignore state");
                                                              //insert next cycle here
                                                              $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);

                                                              //update patient reminder table
                                                               DB::connection('tenant')->table('reminder_patients')
                                                                ->where('service_id',$value_rem['service_id'])
                                                                ->where('patient_id',$value_rem['patient_id'])
                                                                ->whereNull('deleted_at')
                                                                ->update(['status'=>'completed','deleted_at'=>date('Y-m-d H:i:s'),'cycle_no'=>$cycle_no]);


                                                              $deletePrevReminders =  DB::connection('tenant')->table('patient_has_service_reminder')
                                                                ->where('service_id',$value_rem['service_id'])
                                                                ->where('appointment_id',0) 
                                                                ->where('patient_id',$value_rem['patient_id'])
                                                                ->where('reminder_date', $value_rem['reminder_date'])
                                                                ->where('id',$value_rem['id'])
                                                                ->whereNull('deleted_at')
                                                                ->update(['deleted_at'=>date('Y-m-d H:i:s'),'is_deleted_through_cycle'=>1]);



                                                            }//else

                                                        }//if exists 1

                                                    }//if last less current
                                                    else{
                                                        //log::info('lastNextReminderDate is greater than current date');
                                                    }

                                                }//if empty next reminder date
                                               

                                            }//if cnt less


                                        }//isset reminderSetting
                                   // }//if checkNextReminders 0 

                                }//if type age
                            }//if status active

                        }//else appointment id is 0 for age base

                    }//foreach
                }//if getreminder

                 DB::connection('tenant')->table('reminder_patients')
                    ->where('id',$value->id)
                    ->where('service_id',$value->service_id)
                    ->where('patient_id',$value->patient_id)
                    ->where('status','pending')
                    ->update(['is_processed'=>1]);

            }//foreach
        }//if
        else
        {
             Log::info("in remindercycle next  empty collection");

              DB::connection('tenant')
                   ->table('reminder_patients as r')
                    ->join('patients as p', 'p.id', '=', 'r.patient_id')
                    ->join('examinations as e', 'e.id', '=', 'r.service_id')
                    ->where('r.status','pending')
                    ->whereExists(function($query) {
                        $query->select(DB::raw(1))
                              ->from('reminder_patients as r2')
                              ->whereRaw('r2.patient_id = r.patient_id')
                              ->whereRaw('r2.service_id = r.service_id')
                              ->whereRaw('r2.type = r.type')
                              ->where('r2.status', 'completed')
                              ->where('r2.cycle_no', '>=', 2)
                              ->whereNOTNull('r2.deleted_at');
                    })
                    ->whereNull('r.deleted_at')
                    ->whereNull('p.deleted_at')
                    ->update(['is_processed'=>0]);
        }
    }//if reminder execute
}//_commandOperation

  

 
public function _controlReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id,$last_service_reminder_id)
{

     // log::info("in _controlReminder reminder....");

     // log::info("last_service_reminder_id===>");
     // log::info($last_service_reminder_id);

     // log::info("is_service_has_reminder==>");
     // log::info($is_service_has_reminder);

     // log::info("service_id==>");
     // log::info($service_id);


    $reminder_array = [];        
    // 1st reminder
    $start_date = $start_date;

     // log::info("start date===>");
     // log::info($start_date);


    // Log::info(json_encode($is_service_has_reminder)."=".$appointment_id."=".$start_date."=".$patient_id."=".$service_id);

    $value1_days = $this->_getDate($start_date,$is_service_has_reminder->checkup_period_controls,$is_service_has_reminder->checkup_period_frequency_type);

    // log::info("value1_days===>");
    // log::info($value1_days);

   
    $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

    //log::info("period_date===>");
    //log::info($period_date);

    $value3_days = $this->_getDate($period_date,$is_service_has_reminder->checkup_first_frequency,$is_service_has_reminder->checkup_first_frequency_type);

   // log::info("value3_days===>");
   // log::info($value3_days);


    
    //$first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus'); //commented on 26-march-25

    $first_reminder = $start_date;

    //log::info("first_reminder===>");
    //log::info($first_reminder);

    $reminder_array[] = $first_reminder;

    for($i=0; $i<($is_service_has_reminder->checkup_number_of_interval-1); $i++)
    {

        $value4_days = $this->_getDate($period_date,$is_service_has_reminder->checkup_time_interval,$is_service_has_reminder->checkup_time_interval_frequency_type);
        
        // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));

        $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

        if( $third_reminder !=  $first_reminder)
        {
            $reminder_array[] = $third_reminder;
        }
    }       
    sort($reminder_array);

    //log::info("reminder_array===>");
    //log::info($reminder_array);

    //ddd($reminder_array);
    //Added on 04-Sep-23==========================================
    $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('patient_id',$patient_id)
                            ->where('service_id',$service_id)
                            ->where('appointment_id',$appointment_id)
                            ->where('cycle_no',1)//added on 26-march-25
                            ->first();


    if(!empty($firstReminderdate)) 
        $first_cycle_remidner_date=$firstReminderdate->reminder_date;
    else $first_cycle_remidner_date=$start_date;  

    //log::info("first_cycle_remidner_date==>");
    //log::info($first_cycle_remidner_date);

    //commented on 26-march-25
    /*$endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->checkup_end_cycle,$is_service_has_reminder->checkup_end_cycle_frequency_type);
    $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->checkup_period_controls,$is_service_has_reminder->checkup_period_frequency_type);
    $periodOneminusthird=($agePeriodDays-$value3_days);
    $finalDays=($endCycleDyas+$periodOneminusthird); 
    $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');*/


    $endCycleDyas = $this->_getDate(($first_cycle_remidner_date),$is_service_has_reminder->checkup_end_cycle,$is_service_has_reminder->checkup_end_cycle_frequency_type);//added new code on 26-march-25

    // log::info("endCycleDyas==>");
    // log::info($endCycleDyas);

    $ignoreStateEndCycleDate = $this->_filterWeekendAndHoiliday(($first_cycle_remidner_date),$endCycleDyas,$is_service_has_reminder->holiday_reminder,'plus');

    // log::info("ignoreStateEndCycleDate==>");
    // log::info($ignoreStateEndCycleDate);

    $currentDate = Date('Y-m-d H:i:s');
    // log::info("currentDate===>");  
    // log::info($currentDate);


     // if($start_date>=$ignoreStateEndCycleDate){//commented on 17-apr-25
     if($ignoreStateEndCycleDate<$currentDate){ //added on 17-apr-25
       // log::info("in ignore state");
    }
    else
    {
       // log::info("not in ignore state ==============> ");

        $reminder_id = 0;
        if(!empty($reminder_array) && count($reminder_array) > 0)
        {
            //Check last cycle
            $getLastCycleNo =  DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('patient_id',$patient_id)
                            ->where('service_id',$service_id)
                            ->where('appointment_id',$appointment_id)
                            ->whereNull('deleted_at')
                            ->first(['cycle_no']);

            if(isset($getLastCycleNo)) 
            {
                $lastCycleNo = $getLastCycleNo->cycle_no;
                $cycle_no = $lastCycleNo+1;
            }               
           // log::info("getLastCycleNo===>");                
            //log::info($getLastCycleNo);



            for($i=0;$i<count($reminder_array);$i++)
            { 
                $reminder_tmp = [];
                $reminder_tmp['patient_id'] = $patient_id;
                $reminder_tmp['appointment_id'] = $appointment_id;
                $reminder_tmp['service_id'] = $service_id;
                $reminder_tmp['reminder_date'] = $reminder_array[$i];
                //$reminder_tmp['reminder_status'] = 'executed';
                //Added on 04-Sep-23===================================
                // if($endCycleDyas!=0 && $reminder_array[$i] >= $endcycle_date ) $reminder_tmp['reminder_status']='ignore';
                // else $reminder_tmp['reminder_status'] = 'Set';


                //$date1 = new DateTime($reminder_array[$i]); //commented on 26-march-25
                //$date2 = new DateTime($endcycle_date);//commented on 26-march-25

                $date_today=new DateTime();
                $reminder_tmp['reminder_status'] = 'Set';

                //start commented on 26-march-25
               /* if($endCycleDyas>0){
                    if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                    else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                }*/
                //end commented on 26-march-25

                $reminder_tmp['status'] = 'activate';  
                //  $reminder_tmp['parent_id'] = $parent_id;
                $reminder_tmp['type'] = 'control';
                $reminder_tmp['cycle_no'] = $cycle_no;



                //dd($reminder_tmp);

                //Added by Shyam 14-01-22
                $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', 0)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'executed')
                                ->where('status', 'activate')
                                ->where('type', 'control')
                                ->whereNull('deleted_at')
                                ->get();
                if(count($is_exists) == 0)
                {
                    //Log::info("ReminderStatus-_controlReminder-".$patient_id);
                    $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);

                     //update patient reminder table
                       DB::connection('tenant')->table('reminder_patients')
                        ->where('service_id',$service_id)
                        ->where('patient_id',$patient_id)
                        ->whereNull('deleted_at')
                        ->update(['status'=>'completed','deleted_at'=>date('Y-m-d H:i:s'),'cycle_no'=>$cycle_no]);

                    $deletePrevReminders =  DB::connection('tenant')->table('patient_has_service_reminder')
                                                ->where('service_id',$service_id)
                                                ->where('appointment_id',$appointment_id) 
                                                ->where('patient_id',$patient_id)
                                                ->where('type','control')
                                                ->where('cycle_no',$lastCycleNo)
                                                ->whereNull('deleted_at')
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s'),'is_deleted_through_cycle'=>1]);            
                 

                }//if count is exists

            }//for loop
        
            $value5_days = $this->_getDate(end($reminder_array),$is_service_has_reminder->checkup_new_frequency,$is_service_has_reminder->checkup_new_frequency_type);

            // log::info("value5_days");
            // log::info($value5_days);

            // log::info("current reminder_array");
            // log::info(end($reminder_array));

            // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));

            $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

            $temp = [];
            $temp['patient_id'] =  $patient_id;
            $temp['last_reminder_date'] =  end($reminder_array);
            $temp['next_reminder_date'] =  $reactive_reminder;
            $temp['service_reminder_id'] =  $reminder_id;
            $temp['status'] =  'activate'; 
            $temp['created_at'] =  date('Y-m-d H:i:s');
            $temp['cycle_no'] = $cycle_no;

            // log::info("patient_has_reminder array");
            // log::info($temp);

            $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);

            $deletePrevReminders =  DB::connection('tenant')->table('patient_has_reminder')
                                                ->where('patient_id',$patient_id)
                                                ->where('cycle_no',$lastCycleNo)
                                                ->where('service_reminder_id',$last_service_reminder_id)
                                                ->whereNull('deleted_at')
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s'),'is_deleted_through_cycle'=>1]);

        }//if
    }//else not ignore state
}//_controlReminder


public function _generalReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id,$last_service_reminder_id)
{
    // log::info("in general reminder....");

    // log::info("last_service_reminder_id===>");
    // log::info($last_service_reminder_id);

    // dump("is_service_has_reminder==>");
    // dump($is_service_has_reminder);

    // log::info("service_id==>");
    // log::info($service_id);
    
  

    $reminder_array = [];

    // 1st reminder
    $start_date = $start_date;
    // log::info("start date===>");
    // log::info($start_date);

    $value1_days = $this->_getDate($start_date,$is_service_has_reminder->general_period,$is_service_has_reminder->general_period_frequency_type);

    // log::info("value1_days===>");
    // log::info($value1_days);

    $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

    // log::info("period_date===>");
    // log::info($period_date);

    $value3_days = $this->_getDate($period_date,$is_service_has_reminder->general_first_frequency,$is_service_has_reminder->general_first_frequency_type);

    // log::info("value3_days===>");
    // log::info($value3_days);

  
   // $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');

    $first_reminder = $start_date;

    // log::info("first_reminder===>");
    // log::info($first_reminder);

    $reminder_array[] = $first_reminder;
    // Log::info('Default reminder');
    // Log::info(json_encode($reminder_array));
    // Log::info($period_date);
    // dd('s');
    for($i=0; $i<($is_service_has_reminder->general_number_of_interval-1); $i++)
    {
        $value4_days = $this->_getDate($period_date,$is_service_has_reminder->general_time_interval,$is_service_has_reminder->general_time_interval_frequency_type);
        
        // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
        $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

        if( $third_reminder !=  $first_reminder)
        {
            $reminder_array[] = $third_reminder;
        }
    }       
    sort($reminder_array);
    // Log::info("ReminderStatus-_generalReminder-".$patient_id);
    // Log::info($reminder_array);

    // log::info("reminder_array===>");
    // log::info($reminder_array);

    //Added by swati 12-May-23===================================
    $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('patient_id',$patient_id)
                            ->where('service_id',$service_id)
                            ->where('appointment_id',$appointment_id)
                            ->where('cycle_no',1)
                            ->first();
    
    if(!empty($firstReminderdate)) 
        $first_cycle_remidner_date=$firstReminderdate->reminder_date;
    else $first_cycle_remidner_date=$start_date;  


    // log::info("first_cycle_remidner_date==>");
    // log::info($first_cycle_remidner_date);


    // $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->general_end_cycle,$is_service_has_reminder->general_end_cycle_frequency_type);//commneted prev code on 25-march-25

    // dump("endCycleDyas=180 days of 6 month==>");
    // dump($endCycleDyas);


    // $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->general_period,$is_service_has_reminder->general_period_frequency_type);

    // dump("agePeriodDays=180 days of 6 month=of #1==>");
    // dump($agePeriodDays);


    // $periodOneminusthird=($agePeriodDays-$value3_days);

    // dump("periodOneminusthird===180=minus 0 week as 180 days=>");
    // dump($periodOneminusthird);

    // $finalDays=($endCycleDyas+$periodOneminusthird); 

    // dump("periodOneminusthird===180=minus 0 week as 180 days=>");
    // dump($periodOneminusthird);


    // $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');//commneted prev code on 25-march-25
    // $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$endCycleDyas,$is_service_has_reminder->holiday_reminder,'plus');//added new code on 25-march-25

    // dump("endcycle_date====>");
    // dump($endcycle_date);         


    $endCycleDyas = $this->_getDate(($first_cycle_remidner_date),$is_service_has_reminder->general_end_cycle,$is_service_has_reminder->general_end_cycle_frequency_type);//added new code on 25-march-25

    // log::info("endCycleDyas==>");
    // log::info($endCycleDyas);

    $ignoreStateEndCycleDate = $this->_filterWeekendAndHoiliday(($first_cycle_remidner_date),$endCycleDyas,$is_service_has_reminder->holiday_reminder,'plus');

    // log::info("ignoreStateEndCycleDate==>");
    // log::info($ignoreStateEndCycleDate);

    $currentDate = Date('Y-m-d H:i:s');
    // log::info("currentDate===>");  
    // log::info($currentDate);


    // if($start_date>=$ignoreStateEndCycleDate){ //commented on 17-apr-25
    if($ignoreStateEndCycleDate<$currentDate){  //added on 17-apr-25
       // log::info("in ignore state");
    }else{
       // log::info("not in ignore state ==============> ");

        $reminder_id = 0;
        if(!empty($reminder_array) && count($reminder_array) > 0)
        {

            //Check last cycle
            $getLastCycleNo =  DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('patient_id',$patient_id)
                            ->where('service_id',$service_id)
                            ->where('appointment_id',$appointment_id)
                            ->whereNull('deleted_at')
                            ->first(['cycle_no']);

            if(isset($getLastCycleNo)) 
            {
                $lastCycleNo = $getLastCycleNo->cycle_no;
                $cycle_no = $lastCycleNo+1;
            }               
           // log::info("getLastCycleNo===>");                
           // log::info($getLastCycleNo);

            for($i=0;$i<count($reminder_array);$i++)
            { 
               
                $reminder_tmp = [];
                $reminder_tmp['patient_id'] = $patient_id;
                $reminder_tmp['appointment_id'] = $appointment_id;
                $reminder_tmp['service_id'] = $service_id;
                $reminder_tmp['reminder_date'] = $reminder_array[$i];
                //Added by swati 12-May-23===================================
                // if($endCycleDyas!=0 && $reminder_array[$i] >= $endcycle_date ) $reminder_tmp['reminder_status']='ignore';
                // else $reminder_tmp['reminder_status'] = 'Set';


                // $date1 = new DateTime($reminder_array[$i]); //commented on 25-march-25
                // $date2 = new DateTime($endcycle_date);//commented on 25-march-25


                $date_today=new DateTime();
                $reminder_tmp['reminder_status'] = 'Set';

                //start commented on 25-march-25
                // if($endCycleDyas>0){
                //     if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                //     else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                // }
                //end commented on 25-march-25

                $reminder_tmp['status'] = 'activate';  
                $reminder_tmp['type'] = 'general';
                $reminder_tmp['created_at'] = date('Y-m-d h:i:s') ;
                $reminder_tmp['cycle_no'] = $cycle_no;


                // log::info("patient_has_service_reminder array");
                // log::info($reminder_tmp);

                //Added by Shyam 14-01-22
                $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', $service_id)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'Set')
                                ->where('status', 'activate')
                                ->where('type', 'general')
                                ->whereNull('deleted_at')
                                ->get();
                if(count($is_exists) == 0)
                {
                    $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);


                     //update patient reminder table
                       DB::connection('tenant')->table('reminder_patients')
                        ->where('service_id',$service_id)
                        ->where('patient_id',$patient_id)
                        ->whereNull('deleted_at')
                        ->update(['status'=>'completed','deleted_at'=>date('Y-m-d H:i:s'),'cycle_no'=>$cycle_no]);

                     $deletePrevReminders =  DB::connection('tenant')->table('patient_has_service_reminder')
                                                ->where('service_id',$service_id)
                                                ->where('appointment_id',$appointment_id) 
                                                ->where('patient_id',$patient_id)
                                                ->where('cycle_no',$lastCycleNo)
                                                ->whereNull('deleted_at')
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s'),'is_deleted_through_cycle'=>1]);            
             
   

                }//if count exists
               
            }//foreach

            $value5_days = $this->_getDate(current($reminder_array),$is_service_has_reminder->general_new_frequency,$is_service_has_reminder->general_new_frequency_type);

            // log::info("value5_days");
            // log::info($value5_days);

            // log::info("current reminder_array");
            // log::info(current($reminder_array));

            // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +"   .(int)$value5_days." day"));
            $reactive_reminder = $this->_filterWeekendAndHoiliday(current($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

           // log::info("reactive_reminder");
           // log::info($reactive_reminder);


            //Log::info(end($reminder_array)."---".$reactive_reminder );
            // dd('sssss');
            $temp = [];
            $temp['patient_id'] =  $patient_id;
            $temp['last_reminder_date'] =  end($reminder_array);
            $temp['next_reminder_date'] =  $reactive_reminder;
            $temp['service_reminder_id'] =  $reminder_id;
            $temp['status'] =  'activate';
            $temp['created_at'] =  date('Y-m-d H:i:s');
            $temp['cycle_no'] = $cycle_no;

            //log::info("patient_has_reminder array");
            //log::info($temp);
            $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);

           $deletePrevReminders =  DB::connection('tenant')->table('patient_has_reminder')
                                                ->where('patient_id',$patient_id)
                                                ->where('cycle_no',$lastCycleNo)
                                                ->where('service_reminder_id',$last_service_reminder_id)
                                                ->whereNull('deleted_at')
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s'),'is_deleted_through_cycle'=>1]); 
        }
    }//else not ignore state
   
}//general reminder


  
public function _ageReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id,$last_service_reminder_id)
{

   // log::info("in age reminder");

    $reminder_array = [];
    // 1st reminder
    $start_date = $start_date;
    // log::info("start_date");
    // log::info($start_date);


    $value1_days = $this->_getDate($start_date,$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);

    // log::info("value1_days");
    // log::info($value1_days);

    $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

    // log::info("period_date");
    // log::info($period_date);

    $value3_days = $this->_getDate($period_date,$is_service_has_reminder->age_first_frequency,$is_service_has_reminder->age_first_frequency_type);

    // log::info("value3_days");
    // log::info($value3_days);

    //commented on 27-march-25
    //$first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');

    $first_reminder = $start_date;

    // log::info("first_reminder");
    // log::info($first_reminder);

    $reminder_array[] = $first_reminder;
    for($i=0; $i<($is_service_has_reminder->age_number_of_interval-1); $i++)
    {
        $value4_days = $this->_getDate($period_date,$is_service_has_reminder->age_time_interval,$is_service_has_reminder->age_time_interval_frequency_type);

        $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

        if( $third_reminder !=  $first_reminder)
        {
            $reminder_array[] = $third_reminder;
        }
    }       
    sort($reminder_array);

    // log::info("reminder_array");
    // log::info($reminder_array);


    //Added by swati 12-May-23===================================
    $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('patient_id',$patient_id)
                            ->where('service_id',$service_id)
                            ->where('appointment_id',$appointment_id)
                            ->where('cycle_no',1) //added on 27-march-25
                            ->first();


    if(!empty($firstReminderdate)) 
        $first_cycle_remidner_date=$firstReminderdate->reminder_date;
    else $first_cycle_remidner_date=$start_date;

    // log::info("first_cycle_remidner_date==>");
    // log::info($first_cycle_remidner_date);

    //commented on 27-march-25
    /*$endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_end_cycle,$is_service_has_reminder->age_end_cycle_frequency_type);  
    $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);
    $periodOneminusthird=($agePeriodDays-$value3_days);
    $finalDays=($endCycleDyas+$periodOneminusthird); 
    $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');
    log::info($service_id);
    log::info($endcycle_date);*/


    $endCycleDyas = $this->_getDate(($first_cycle_remidner_date),$is_service_has_reminder->age_end_cycle,$is_service_has_reminder->age_end_cycle_frequency_type);  //added new code on 27-march-25
    // log::info("endCycleDyas==>");
    // log::info($endCycleDyas);

    $ignoreStateEndCycleDate = $this->_filterWeekendAndHoiliday(($first_cycle_remidner_date),$endCycleDyas,$is_service_has_reminder->holiday_reminder,'plus');

    // log::info("ignoreStateEndCycleDate==>");
    // log::info($ignoreStateEndCycleDate);

    $currentDate = Date('Y-m-d H:i:s');
    // log::info("currentDate===>");  
    // log::info($currentDate);


    // if($start_date>=$ignoreStateEndCycleDate){  //commented on 17-apr-25
    if($ignoreStateEndCycleDate<$currentDate){ //added on 17-apr-25
       // log::info("in ignore state");
    }
    else
    {
        //log::info("not in ignore state ==============> ");

        $reminder_id = 0;
        if(!empty($reminder_array) && count($reminder_array) > 0)
        {

             //Check last cycle
            $getLastCycleNo =  DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('patient_id',$patient_id)
                            ->where('service_id',$service_id)
                            ->where('appointment_id',$appointment_id)
                            ->whereNull('deleted_at')
                            ->first(['cycle_no']);

            if(isset($getLastCycleNo)) 
            {
                $lastCycleNo = $getLastCycleNo->cycle_no;
                $cycle_no = $lastCycleNo+1;
            }               
           // log::info("getLastCycleNo===>");                
            //log::info($getLastCycleNo);

            for($i=0;$i<count($reminder_array);$i++)
            { 
                $reminder_tmp = [];
                $reminder_tmp['patient_id'] = $patient_id;
                $reminder_tmp['appointment_id'] = $appointment_id;
                $reminder_tmp['service_id'] = $service_id;
                $reminder_tmp['reminder_date'] = $reminder_array[$i];
                 //Added by swati 12-May-23===================================
                // if($endCycleDyas!=0 && $reminder_array[$i] >= $endcycle_date ) $reminder_tmp['reminder_status']='ignore';
                // else $reminder_tmp['reminder_status'] = 'Set';

                //$date1 = new DateTime($reminder_array[$i]);//commented on 27-march-25
                //$date2 = new DateTime($endcycle_date);//commented on 27-march-25


                $date_today=new DateTime();
                $reminder_tmp['reminder_status'] = 'Set';

                //start commented on 27-march-25
                // if($endCycleDyas>0){
                //     if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                //     else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                // }
                 //end commented on 27-march-25

                $reminder_tmp['status'] = 'activate';  
                $reminder_tmp['type'] = 'age';
                $reminder_tmp['created_at'] = date('Y-m-d H:i:s') ;
                $reminder_tmp['cycle_no'] = $cycle_no;

                //log::info("patient_has_service_reminder array");
                //log::info($reminder_tmp);

                $getAppointmentExamination = DB::connection('tenant')->table('appointment_has_examinations')->where('examination_id',$service_id)->where('appointment_id',$appointment_id)->first();
                

                //Added by Shyam 14-01-22
                $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', $service_id)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'Set')
                                ->where('status', 'activate')
                                ->where('type', 'age')
                                ->whereNull('deleted_at')
                                ->get();
                if(count($is_exists) == 0)
                {
                    $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);


                     //update patient reminder table
                       DB::connection('tenant')->table('reminder_patients')
                        ->where('service_id',$service_id)
                        ->where('patient_id',$patient_id)
                        ->whereNull('deleted_at')
                        ->update(['status'=>'completed','deleted_at'=>date('Y-m-d H:i:s'),'cycle_no'=>$cycle_no]);





                     $deletePrevReminders =  DB::connection('tenant')->table('patient_has_service_reminder')
                                                ->where('service_id',$service_id)
                                                ->where('appointment_id',$appointment_id) 
                                                ->where('patient_id',$patient_id)
                                                ->where('cycle_no',$lastCycleNo)
                                                ->whereNull('deleted_at')
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s'),'is_deleted_through_cycle'=>1]);       


                                                    
                                              
             
                }
            }//for

            $value5_days = $this->_getDate(current($reminder_array),$is_service_has_reminder->age_new_frequency,$is_service_has_reminder->age_new_frequency_type);

            // log::info("value5_days");
            // log::info($value5_days);

            // log::info("current reminder_array");
            // log::info(current($reminder_array));

            $reactive_reminder = $this->_filterWeekendAndHoiliday(current($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

            //log::info("reactive_reminder");
            //log::info($reactive_reminder);

            $temp = [];
            $temp['patient_id'] =  $patient_id;
            $temp['last_reminder_date'] =  end($reminder_array);
            $temp['next_reminder_date'] =  $reactive_reminder;
            $temp['service_reminder_id'] =  $reminder_id;
            $temp['status'] =  'activate';
            $temp['created_at'] =  date('Y-m-d H:i:s');
            $temp['cycle_no'] = $cycle_no;

           // log::info("patient_has_reminder array");
           // log::info($temp);

            $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);

            $deletePrevReminders =  DB::connection('tenant')->table('patient_has_reminder')
                                                ->where('patient_id',$patient_id)
                                                ->where('cycle_no',$lastCycleNo)
                                                ->where('service_reminder_id',$last_service_reminder_id)
                                                ->whereNull('deleted_at')
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s'),'is_deleted_through_cycle'=>1]);

        }//if reminder_array
   }//else
   
}//age reminder

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

public function _filterWeekendAndHoiliday($date,$days,$is_hoilday_or_weekend,$operation)
{ 

    $operator = '+';
    if($operation == 'minus')
    {
        $operator = '-';
    }
    $calculated_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($date)) . " ".$operator.(int)$days." day"));
    $weekDay = date('w', strtotime($calculated_date));
      // Log::info($date."=".$days."=".$is_hoilday_or_weekend."=".$operation."=".$calculated_date);
      // dump($date."=".$days."=".$is_hoilday_or_weekend."=".$operation."=".$calculated_date);
    // if($is_hoilday_or_weekend == 1 && ($weekDay == 0 || $weekDay == 6))
    // {
    //     $time = date('H:i:s',strtotime($calculated_date));
    //     $calculated_date = Date('Y-m-d', strtotime($calculated_date.' +1 Weekday'));
    //     $calculated_date = $calculated_date." ".$time;
    //     // dump($calculated_date);
    // }
    // dump($calculated_date);
    //Log::info($calculated_date);
    return $calculated_date;
}//_filterWeekendAndHoiliday
 



}
 