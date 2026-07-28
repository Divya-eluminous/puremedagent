<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\PatientHasDeviceModel;
use App\Models\PatientsModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\ExaminationsModel;

use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
use DateTime; 
use Stancl\Tenancy\Facades\Tenancy;


class ReminderPatientsNext extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'reminderpatients:daily {--website_id=}';
    protected $signature = 'reminderpatientsnext:daily {--tenant_id=}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set patients for reminder cycle';

    //  /**
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
                                ExaminationsModel $ExaminationsModel
                                )
    {
        parent::__construct();
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
        $this->BaseModel  = $PatientsHasServiceReminderModel;
        $this->PatientHasDeviceModel = $PatientHasDeviceModel;
        $this->PatientsModel = $PatientsModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->ExaminationsModel = $ExaminationsModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    //public function handle1()
    public function handle()
    {
        //log::info("ReminderStatus-Handle");
        // $website_id = $this->option('website_id');

        // log::info("website_id=in handle function=of ReminderPatients=====>");
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
        //log::info("tenant_id=in handle function=of ReminderPatients=====>");
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

    //  public function _commandOperation($website_id)
     public function _commandOperation($tenant_id)
     //public function handle()
    {

         log::info("In Reminderpatient Next _commandOperation function ..........");

        //log::info("tenant_id=in commandoperation function==>");
        //log::info($tenant_id);

        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
           // Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
           // Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }

        //log::info("In ReminderPatients _commandOperation function ..........");

        $is_reminder_execute = DB::connection('tenant')->table('settings')->where(['setting_key'=>'REMINDER_SETTING','status'=>'1'])->first();
        $channel = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->select('choice_of_channels')
                       ->first();
        
        //log::info(date('Y-m-d'));
       // log::info(date('H:i'));
               

        if(!empty($is_reminder_execute))
        {
             //client 33890
                
               //commented on 17-sept-25 
              /* $collections = DB::connection('tenant')->table('patient_has_service_reminder')
                ->join('patients', 'patients.id', '=', 'patient_has_service_reminder.patient_id')
                ->join('examinations', 'examinations.id', '=', 'patient_has_service_reminder.service_id')
                ->where('patient_has_service_reminder.reminder_status', 'Set')
                ->whereNull('patient_has_service_reminder.deleted_at')
                ->where('patients.id',56530)
                ->whereNull('patients.deleted_at')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('reminder_patients')
                        ->whereRaw("reminder_patients.patient_id = patient_has_service_reminder.patient_id")
                        ->whereRaw("reminder_patients.service_id = patient_has_service_reminder.service_id")
                        ->whereRaw("reminder_patients.type = patient_has_service_reminder.type")                
                        ->where('reminder_patients.status', 'pending'); // Only exclude pending
                })
                //->orderBy('patient_has_service_reminder.id','asc')
                ->inRandomOrder()
                ->distinct()
                ->limit(1000)
                ->get([
                    'patients.id as patient_id',
                    'patient_has_service_reminder.service_id',
                    'patient_has_service_reminder.type',
                    'patient_has_service_reminder.id'
                ]);*/


                //changed on 17-sept-25 //take those records who have atleast one entry and does not have pending record in reminder only take completed records patient table

               /*$collections = DB::connection('tenant')
                    ->table('patient_has_service_reminder as psr')
                    ->join('patients as p', 'p.id', '=', 'psr.patient_id')
                    ->join('examinations as e', 'e.id', '=', 'psr.service_id')
                    ->where('psr.reminder_status', 'Set')
                    ->whereNull('psr.deleted_at')
                    ->whereNull('p.deleted_at')
                    // Must have at least one entry in reminder_patients
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('reminder_patients as rp')
                            ->whereRaw("rp.patient_id = psr.patient_id")
                            ->whereRaw("rp.service_id = psr.service_id")
                            ->whereRaw("rp.type = psr.type");
                    })
                    // Exclude if there is any pending entry
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('reminder_patients as rp')
                            ->whereRaw("rp.patient_id = psr.patient_id")
                            ->whereRaw("rp.service_id = psr.service_id")
                            ->whereRaw("rp.type = psr.type")
                            ->where('rp.status', 'pending')
                            ->whereNull('rp.deleted_at');
                    })
                    ->orderBy('psr.created_at', 'asc')
                    ->distinct()
                    ->limit(2)
                    ->get([
                        'p.id as patient_id',
                        'psr.service_id',
                        'psr.type'
                    ]);*/


           $collections = DB::connection('tenant')
                ->table('reminder_patients as r')
                ->join('patients as p', 'p.id', '=', 'r.patient_id')
                ->join('examinations as e', 'e.id', '=', 'r.service_id')
                ->where('r.status', 'completed')
                ->whereNOTNull('r.deleted_at')
                ->whereNull('p.deleted_at')
                ->where('r.is_processed', 0)
                //->where('r.patient_id',56530)
                // Exclude if any pending entry exists
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('reminder_patients as rp')
                        ->whereRaw('rp.patient_id = r.patient_id')
                        ->whereRaw('rp.service_id = r.service_id')
                        ->whereRaw('rp.type = r.type')
                        ->where('rp.status', 'pending')
                        ->whereNull('rp.deleted_at');
                })
                // Keep only the latest completed cycle per patient/service/type
                ->whereRaw('r.cycle_no = (
                    SELECT MAX(r2.cycle_no)
                    FROM reminder_patients r2
                    WHERE r2.patient_id = r.patient_id
                      AND r2.service_id = r.service_id
                      AND r2.type = r.type
                      AND r2.status = "completed"
                      AND r2.deleted_at IS NOT NULL
                )')
                ->orderBy('r.created_at', 'desc')
                ->limit(1000)
                ->get([
                    'r.id',
                    'r.patient_id',
                    'r.service_id',
                    'r.type',
                    'r.cycle_no'
                ]);



              log::info("collections===>");  
              log::info($collections);


             $currentDate = Date('Y-m-d H:i:s');

            if($collections->count() > 0)
            {

                 Log::info("in reminderpatient next not empty collection");

                foreach ($collections as $key => $value)
                {
                    $patientId= $value->patient_id;
                    $serviceId= $value->service_id;
                    $type= $value->type;

                    // log::info($patientId);
                    // log::info($serviceId); 
                    // log::info($type);

                    $is_exists = DB::connection('tenant')->table('reminder_patients')
                                    ->where('patient_id', $patientId)
                                    ->where('service_id', $serviceId)
                                    // ->where('status', 'completed')
                                    ->where('type', $type)
                                    // ->whereNOTNull('deleted_at')
                                    ->limit(1)
                                    ->orderBy('id','desc')
                                    ->first();

                    
                    // log::info($is_exists);

                    if(empty($is_exists))
                    {
                        //log::info(" empty is_exists==>");
                        $insertRecord=1;

                    }else
                    {

                        //log::info("innnnnnnnnnnnnnnn.not empty .is_exists....");

                        if ($is_exists->status === 'completed' && !is_null($is_exists->deleted_at)) 
                        {
                            //log::info("in completed...");

                            if($type=='age')
                            {
                                $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                   ->where('service_id',$serviceId)
                                   ->where('activated_reminder',$type)
                                   ->first();
                              
                            }
                            else if($type=='general')
                            {   
                                
                                 $reminderSetting1 = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                    'recommanded_service_id', $serviceId)->where( 'activated_reminder','general')->first();
                                 
                                if(isset($reminderSetting1)){
                                    $reminderSetting = $reminderSetting1;
                                    $service_id = $reminderSetting1->recommanded_service_id;
                                }else{
                                    
                                    $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                     ['service_id' => $serviceId])->first();
                                   $service_id = $serviceId;

                                } 
                            }
                            else if($type=='control')
                            {
                                $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                     ['service_id' => $serviceId])->first();
                                    
                            }


                            //changed on 21-may-25
                            $getRemider = DB::connection('tenant')->select("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                            JOIN examinations on examinations.id=t1.service_id 
                            AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                            JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder 
                                WHERE patient_id=$patientId
                                AND service_id = $serviceId
                                and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientId 
                                     AND service_id = $serviceId
                                AND 
                                ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                                and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                            WHERE t1.reminder_status IN('Set','ignore') 
                            AND t1.patient_id=$patientId
                            AND t1.service_id = $serviceId
                            and t1.deleted_at is NULL
                            GROUP by t1.service_id
                            ORDER by t1.id DESC");   


                            $getRemider = collect($getRemider)->map(function($x){ 
                                return (array) $x; 
                            })->toArray();    


                            // log::info("getRemider===>");
                            //log::info($getRemider);

                            if(isset($getRemider) && !empty($getRemider))
                            {
                                foreach ($getRemider as $key_rem => $value_rem) 
                               {
                                    // log::info("cycle_no===>");  
                                    // log::info($value_rem['cycle_no']);

                                    // log::info("service_id===>");  
                                    // log::info($value_rem['service_id']);

                                    $patientId=  $value_rem['patient_id'];
                                    $serviceId= $value_rem['service_id'];
                                    $type=  $value_rem['type'];
                                    $appointmentId=  $value_rem['appointment_id'];

                                    $getLastDateDescOrder =  DB::connection('tenant')->table('patient_has_service_reminder')                                   
                                    ->where('patient_has_service_reminder.reminder_status','Set')
                                    ->where('patient_has_service_reminder.patient_id',$patientId)
                                    ->where('patient_has_service_reminder.service_id',$serviceId)
                                    ->where('patient_has_service_reminder.type',$type)
                                    ->where('appointment_id',$appointmentId) //added on 21-apr-25
                                    ->whereNull('patient_has_service_reminder.deleted_at') 
                                    //->orderBy('patient_has_service_reminder.id','desc')
                                    ->orderBy('reminder_date','desc') //added on 21-apr-25
                                    ->first();

                                   
                                    //dump($getLastDateDescOrder);

                                    if(isset($getLastDateDescOrder) && !empty($getLastDateDescOrder))
                                    {
                                         //log::info("in getLastDateDescOrder..."); 

                                        $lastDate =  Date('Y-m-d H:i:s',strtotime($getLastDateDescOrder->reminder_date));

                                        // log::info("lastDate==>");
                                        //  log::info($lastDate);

                                         //commented on 21-apr-25 
                                        //$appointmentId =  $getLastDateDescOrder->appointment_id;

                                         // log::info("appointmentId==>");
                                         // log::info($appointmentId);

                                        if($getLastDateDescOrder->type=="age"){

                                            //dump("in age dscorder");

                                            if($appointmentId==0)
                                            {
                                                //log::info("appointmentId is 0");

                                                $patinet_data = DB::connection('tenant')->table('patients')
                                                  ->where('id',$patientId)
                                                  ->whereNull('patients.deleted_at')
                                                  ->first();

                                                $checkPatientAge = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(['service_id'=>$serviceId,'activated_reminder'=>'age'])->first();

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

                                                $checkReminder = 'Send';
                                                if(!empty($age) && $ageFrom > 0 && $ageTo > 0 && ($age < $ageFrom || $age > $ageTo))
                                                {
                                                    $checkReminder = 'Not Send';
                                                }


                                                // $reminderDate =Date('Y-m-d H:i:s',strtotime($getLastDateDescOrder->reminder_date)); //commented on 21-apr-25

                                                //added new on 21-apr-25
                                                 $reminderDate =Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date']));

                                                //Check status is active or not
                                                 $status =  'inaktiv';

                                             

                                                //added on 27-march-25
                                                if((strtotime($reminderDate) < strtotime($currentDate)) || ((strtotime($reminderDate) > strtotime($currentDate)) &&  $value_rem['appointment_id']!=0 && $value_rem['cycle_no']!=1)  || $value_rem['appointment_id']==0)
                                                {
                                                    $status =  'aktiv';
                                                }

                                                if($value_rem['reminder_status']=='ignore' && $value_rem['status']!='deactivate')
                                                {
                                                   
                                                    $status='ignored';
                                                } 
                                                if($value_rem['reminder_status']=='ignore' && $value_rem['status']=='deactivate')
                                                {
                                                    $status ='inaktiv';
                                                }   

                                                if($value_rem['appointment_id']==0 && $value_rem['status']=='deactivate')
                                                {
                                                    $status ='inaktiv';
                                                }

                                                //log::info("status===>");
                                               //log::info($status);

                                                if($status=="aktiv") 
                                                {

                                                    //if condition commented on 21-apr-25
                                                   /* $checkNextReminders =  DB::connection('tenant')
                                                    ->table('patient_has_service_reminder')
                                                    ->where('patient_id',$patientId)
                                                    ->where('service_id',$serviceId)
                                                    ->where('type',$type)
                                                    ->where('appointment_id','!=',0)
                                                    ->whereNull('patient_has_service_reminder.deleted_at') 
                                                    ->count(); 

                                                    dump("checkNextReminders======================>");
                                                    dump($checkNextReminders); 

                                                    if($checkNextReminders==0 && $checkReminder=="Send") 
                                                    { */

                                                        //check next reminder date is passed or not


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


                                                            //log::info("totalAgeNumberOfInterval=>");
                                                            //log::info($totalAgeNumberOfInterval); 

                                                            $period_date = Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date']));  

                                                            $value4_days = $this->_getDate($period_date,$age_time_interval,$age_time_interval_frequency);

                                                            $value4_days = $totalAgeNumberOfInterval*$value4_days;
                                                           // log::info("value4_days===>");
                                                           // log::info($value4_days); 

                                                            //log::info("reminderDate===>");
                                                            //log::info($reminderDate); 

                                                            //calculate next_reminder_date column value here
                                                            $lastNextReminderDate = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($reminderDate)) . " +".(int)$value4_days." day"));

                                                           //log::info("lastNextReminderDate===>".$patientId."===".$serviceId);
                                                           //log::info($lastNextReminderDate);

                                                            if(!empty($lastNextReminderDate))
                                                            {
                                                                 if($lastNextReminderDate<$currentDate)
                                                                {
                                                                    //log::info("lastNextReminderDate  passed =>");

                                                                    $checkExists = $this->checkExists($patientId,$serviceId,'age');

                                                                   // log::info("checkExists =>");
                                                                   // log::info($checkExists);

                                                                    if($checkExists==0)
                                                                    {
                                                                        $temp = [];
                                                                        $temp['patient_id'] =  $patientId;
                                                                        $temp['service_id'] =  $serviceId;
                                                                        $temp['type']       =  $type;
                                                                        $temp['status']      =  'pending';
                                                                        $temp['created_at']  =  date('Y-m-d H:i:s');
                                                                        $temp['cycle_no']    = 1;

                                                                        //log::info("reminder_patients array");
                                                                       // log::info($temp);

                                                                        $parent_id = DB::connection('tenant')->table('reminder_patients')->insertGetId($temp);
                                                                   }//if checkexists 0
                                                                }//if last date passed
                                                                else{
                                                                 //log::info("lastnextreminderdate not passed");
                                                                }//else not passed
                                                            }//if not empty lastNextReminderDate 
 
                                                        }//if count
                                                    

                                                        $endCycleDyas = $this->_getDate(($reminderDate),$reminderSetting->age_end_cycle,$reminderSetting->age_end_cycle_frequency_type);//added new code on 27-march-25
                                                        //log::info("endCycleDyas==>");
                                                        //log::info($endCycleDyas);

                                                        $ignoreStateEndCycleDate = $this->_filterWeekendAndHoiliday(($reminderDate),$endCycleDyas,$reminderSetting->holiday_reminder,'plus');

                                                      
                                                        //log::info("ignoreStateEndCycleDate==>". $patientId."===".$serviceId);
                                                        //log::info($ignoreStateEndCycleDate);

                                                        if($ignoreStateEndCycleDate<$currentDate)
                                                        {

                                                            $checkExists = $this->checkExists($patientId,$serviceId,'age');
                                                            if($checkExists==0)
                                                            {
                                                                $temp = [];
                                                                $temp['patient_id'] =  $patientId;
                                                                $temp['service_id'] =  $serviceId;
                                                                $temp['type']       =  $type;
                                                                $temp['status']      =  'pending';
                                                                $temp['created_at']  =  date('Y-m-d H:i:s');
                                                                $temp['cycle_no']    = 1;

                                                                //log::info("reminder_patients array");
                                                               // log::info($temp);

                                                                $parent_id = DB::connection('tenant')->table('reminder_patients')->insertGetId($temp);
                                                            }//if checkExists 0
                                                            

                                                        }//if ignoreStateEndCycleDate

                                                   // }//if checkNextReminders 0
                                                }//if status is active

                                            }
                                            else
                                            {
                                              // log::info("appointmentId is not 0" . $serviceId."===>".$patientId);

                                                //commented on 21-apr-25
                                                //get last of 1st row date
                                             

                                                //added new on 21-apr-25
                                                 $reminderDate =Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date']));

                                                 //log::info("getLastFirstDateAscOrder");
                                                 //log::info($reminderDate); 


                                                 //log::info("currentDate");
                                                 //log::info($currentDate); 

                                                //check status here if active then allow
                                                $status =  'inaktiv';

                                                //changed on 21-apr-25
                                                if((strtotime($reminderDate) < strtotime($currentDate)) || ((strtotime($reminderDate) > strtotime($currentDate)) &&  $appointmentId!=0 && $value_rem['cycle_no']!=1)  || $appointmentId==0)
                                                    {
                                                         log::info("in status active condition..");
                                                        $status =  'aktiv';
                                                    }

                                                    if($value_rem['status'] == 'deactivate'){
                                                        $status = 'inaktiv';
                                                    }

                                                    if($value_rem['reminder_status']=='ignore' && $value_rem['status']!='deactivate')
                                                    {
                                                        // $status ='inaktiv';//commented on 17-apr-25
                                                        $status='ignored';//added on 17-apr-25
                                                    } 
                                                    if($value_rem['reminder_status']=='ignore' && $value_rem['status']=='deactivate')
                                                    {
                                                        $status ='inaktiv';
                                                    }   

                                                    if($value_rem['appointment_id']==0 && $$value_rem['status']=='deactivate')
                                                    {
                                                        $status ='inaktiv';
                                                    }
                                            

                                               // log::info("status======================>");
                                               //log::info($status);

                                                if($status=="aktiv")
                                                {

                                                    //log::info("lastDate");
                                                    //log::info($lastDate);

                                                    if(isset($lastDate) && $lastDate < $currentDate)
                                                    {
                                                        //log::info("last date less than current date means passed");


                                                        $checkExists = $this->checkExists($patientId,$serviceId,$type);

                                                        //log::info("checkExists =>");
                                                        //log::info($checkExists);

                                                        if($checkExists==0){
                                                            $temp = [];
                                                            $temp['patient_id'] =  $patientId;
                                                            $temp['service_id'] =  $serviceId;
                                                            $temp['type']      =  $type;
                                                            $temp['status']    =  'pending';
                                                            $temp['created_at']  =  date('Y-m-d H:i:s');
                                                            $temp['cycle_no']    = 1;

                                                            //log::info("reminder_patients array");
                                                           // log::info($temp);

                                                            $parent_id = DB::connection('tenant')->table('reminder_patients')->insertGetId($temp);
                                                        }//if checkExists 0

                                                    }//if lastDate 
                                                    else{
                                                        //log::info("last date not passed");
                                                    }

                                                 //add code here for ignore state

                                                   //Take patient_has_reminder entry of last date matched for 2nd cycle 1st date
                                                    $patient_has_reminder =  DB::connection('tenant')
                                                       ->table('patient_has_reminder')
                                                       ->where('patient_id',$patientId)
                                                       ->where('service_reminder_id',$getLastDateDescOrder->id)
                                                       ->whereNull('deleted_at')
                                                       ->first();

                                                    
                                                    //dump($patient_has_reminder); 

                                                    if(isset($patient_has_reminder) && !empty($patient_has_reminder)) 
                                                    {
                                                        //log::info(" in patient_has_reminder");

                                                        $next_reminder_date_of_next_cycle = $patient_has_reminder->next_reminder_date;

                                                        $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                                                        ->where('patient_id',$patientId)
                                                        ->where('service_id',$serviceId)
                                                        ->where('appointment_id',$appointmentId)
                                                        ->where('cycle_no',1) //added on 27-march-25
                                                        ->first();
                                                         if(!empty($firstReminderdate)) 
                                                         {
                                                            $first_cycle_remidner_date=$firstReminderdate->reminder_date;
                                                        
                                                         }

                                                        //log::info("first_cycle_remidner_date==>");
                                                        //log::info($first_cycle_remidner_date);

                                                        $endCycleDyas = $this->_getDate(($first_cycle_remidner_date),$reminderSetting->age_end_cycle,$reminderSetting->age_end_cycle_frequency_type);  //added new code on 27-march-25
                                                       // log::info("endCycleDyas==>");
                                                       // log::info($endCycleDyas);

                                                        $ignoreStateEndCycleDate = $this->_filterWeekendAndHoiliday(($first_cycle_remidner_date),$endCycleDyas,$reminderSetting->holiday_reminder,'plus');

                                                        // log::info("next_reminder_date_of_next_cycle==>");
                                                        // log::info($next_reminder_date_of_next_cycle);

                                                        //log::info("ignoreStateEndCycleDate==>");
                                                        //log::info($ignoreStateEndCycleDate);

                                                        // if($next_reminder_date_of_next_cycle>=$ignoreStateEndCycleDate)
                                                         if($ignoreStateEndCycleDate<$currentDate)
                                                        {

                                                            $checkExists = $this->checkExists($patientId,$serviceId,$type);

                                                           // log::info("checkExists =>");
                                                           // log::info($checkExists);

                                                            if($checkExists==0)
                                                            {

                                                                $temp = [];
                                                                $temp['patient_id'] =  $patientId;
                                                                $temp['service_id'] =  $serviceId;
                                                                $temp['type']       =  $type;
                                                                $temp['status']      =  'pending';
                                                                $temp['created_at']  =  date('Y-m-d H:i:s');
                                                                $temp['cycle_no']    = 1;

                                                               // log::info("reminder_patients array");
                                                               // log::info($temp);

                                                                 $parent_id = DB::connection('tenant')->table('reminder_patients')->insertGetId($temp);
                                                            }//if checkexists 0


                                                        }//if ignorestate condition

                                                    } //if patient_has_reminder    


                                                }//if status active

                                            }//else not 0
                                        }else{

                                            //log::info("another reminders not age");

                                               

                                                //commented on 22-apr-25
                                                $reminderDate =Date('Y-m-d H:i:s',strtotime($value_rem['reminder_date']));

                                                 //log::info("getLastFirstDateAscOrder");
                                                 //log::info($reminderDate); 

                                                //check status here if active then allow
                                                $status =  'inaktiv';
                                             

                                                //changed on 21-apr-25
                                                if((strtotime($reminderDate) < strtotime($currentDate)) || ((strtotime($reminderDate) > strtotime($currentDate)) &&  $appointmentId!=0 && $value_rem['cycle_no']!=1)  || $appointmentId==0)
                                                    {
                                                         log::info("in status active condition..");
                                                        $status =  'aktiv';
                                                    }

                                                    if($value_rem['status'] == 'deactivate')
                                                    {
                                                        $status = 'inaktiv';
                                                    }

                                                    if($value_rem['reminder_status']=='ignore' && $value_rem['status']!='deactivate')
                                                    {
                                                        // $status ='inaktiv';//commented on 17-apr-25
                                                        $status='ignored';//added on 17-apr-25
                                                    } 
                                                    if($value_rem['reminder_status']=='ignore' && $value_rem['status']=='deactivate')
                                                    {
                                                        $status ='inaktiv';
                                                    }   

                                                    if($value_rem['appointment_id']==0 && $$value_rem['status']=='deactivate')
                                                    {
                                                        $status ='inaktiv';
                                                    }
                                                //log::info("status======================>");
                                               // log::info($status);
                                                if($status=="aktiv")
                                                {
                                                   // log::info(" in activ status======>");

                                                    if(isset($lastDate) && $lastDate < $currentDate)
                                                    {
                                                      // log::info("last date less than current date means passed");


                                                       $checkExists = $this->checkExists($patientId,$serviceId,$type);

                                                       // log::info("checkExists =>");
                                                       // log::info($checkExists);

                                                        if($checkExists==0){
                                                            $temp = [];
                                                            $temp['patient_id'] =  $patientId;
                                                            $temp['service_id'] =  $serviceId;
                                                            $temp['type']      =  $type;
                                                            $temp['status']    =  'pending';
                                                            $temp['created_at']  =  date('Y-m-d H:i:s');
                                                            $temp['cycle_no']    = 1;

                                                            //log::info("reminder_patients array");
                                                            //log::info($temp);

                                                            $parent_id = DB::connection('tenant')->table('reminder_patients')->insertGetId($temp);
                                                        }//if not exists
                                               
                                                    }//if
                                                    else{
                                                        //log::info('last date not passed');
                                                    }

                                                  //add code here for ignore state
                                                  //Take patient_has_reminder entry of last date matched for 2nd cycle 1st date
                                                    $patient_has_reminder =  DB::connection('tenant')
                                                       ->table('patient_has_reminder')
                                                       ->where('patient_id',$patientId)
                                                       ->where('service_reminder_id',$getLastDateDescOrder->id)
                                                       ->whereNull('deleted_at')
                                                       ->first();

                                                    //log::info("patient_has_reminder");
                                                    //dump($patient_has_reminder);  

                                                    if(isset($patient_has_reminder) && !empty($patient_has_reminder)) 
                                                    {
                                                         //log::info("in patient_has_reminder");

                                                        $next_reminder_date_of_next_cycle = $patient_has_reminder->next_reminder_date;

                                                        $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                                                        ->where('patient_id',$patientId)
                                                        ->where('service_id',$serviceId)
                                                        ->where('appointment_id',$appointmentId)
                                                        ->where('cycle_no',1) //added on 27-march-25
                                                        ->first();
                                                         if(!empty($firstReminderdate)) 
                                                         {
                                                            $first_cycle_remidner_date=$firstReminderdate->reminder_date;
                                                        
                                                         }

                                                        //log::info("first_cycle_remidner_date==>");
                                                        //log::info($first_cycle_remidner_date);

                                                        //log::info("type");
                                                        //log::info($type);

                                                        if($type=="control")
                                                        {
                                                            //log::info("in control");
                                                            //log::info($patientId.''.$appointmentId.''.$serviceId);

                                                            $is_doctor_set_reminder = DB::connection('tenant')->table('patient_has_service_control_reminder_setting')->where(
                                                                ['patient_id' => $patientId,
                                                                'appointment_id' =>$appointmentId,
                                                                'service_id' => $serviceId,
                                                                'status' => '1',
                                                                ]
                                                                )->first();

                                                            //log::info("is_doctor_set_reminder==>");
                                                            //dump($is_doctor_set_reminder);
                                                            if($is_doctor_set_reminder)
                                                            {
                                                               // log::info(" in is_doctor_set_reminder==>");


                                                                $end_cycle = $reminderSetting->checkup_end_cycle;

                                                                $end_cycle_frequency_type = $reminderSetting->checkup_end_cycle_frequency_type;


                                                            }//if is_doctor_set_reminder

                                                         }//if control 
                                                         else if($type=="general")
                                                         {
                                                            $end_cycle = $reminderSetting->general_end_cycle;
                                                            $end_cycle_frequency_type = $reminderSetting->general_end_cycle_frequency_type;

                                                         }//general


                                                        $endCycleDyas = $this->_getDate(($first_cycle_remidner_date),$end_cycle,$end_cycle_frequency_type);  //added new code on 27-march-25
                                                        //log::info("endCycleDyas==>");
                                                        //log::info($endCycleDyas);

                                                        $ignoreStateEndCycleDate = $this->_filterWeekendAndHoiliday(($first_cycle_remidner_date),$endCycleDyas,$reminderSetting->holiday_reminder,'plus');

                                                       //log::info("first_cycle_remidner_date==>");
                                                       // log::info($first_cycle_remidner_date);

                                                        //log::info("ignoreStateEndCycleDate==>");
                                                        //log::info($ignoreStateEndCycleDate);

                                                        // if($next_reminder_date_of_next_cycle>=$ignoreStateEndCycleDate)
                                                         if($ignoreStateEndCycleDate<$currentDate)
                                                        {
                                                            //log::info("in ignore less than current means in ignore state..");

                                                            $checkExists = $this->checkExists($patientId,$serviceId,$type);

                                                           // log::info("checkExists =>");
                                                           // log::info($checkExists);

                                                            if($checkExists==0){

                                                                $temp = [];
                                                                $temp['patient_id'] =  $patientId;
                                                                $temp['service_id'] =  $serviceId;
                                                                $temp['type']       =  $type;
                                                                $temp['status']      =  'pending';
                                                                $temp['created_at']  =  date('Y-m-d H:i:s');
                                                                $temp['cycle_no']    = 1;

                                                                //log::info("reminder_patients array");
                                                                //log::info($temp);

                                                                $parent_id = DB::connection('tenant')->table('reminder_patients')->insertGetId($temp);
                                                            }//if checkexists 0


                                                        }//if ignorestate condition

                                                    } //if patient_has_reminder   



                                                }//if status active
                                                 
                                        }//else
                                      
                                    }//if getLastDateDescOrder

                                }//foreach getreminder
                            }//if isset getRemider

                               

                         // dump("in 1");
                         $insertRecord = 0;

                        } else if ($is_exists->status === 'pending' && is_null($is_exists->deleted_at)) {
                           // log::info("in 2 pending exists");
                            $insertRecord = 0;

                        } else {
                           // log::info("innn 3 not exists");
                            $insertRecord = 1;
                        }

                    }
                   
                    if($insertRecord==1)
                    {
                        if(isset($is_exists->cycle_no))
                        {
                            $cycle_no = $is_exists->cycle_no;
                            $cycle_no = $cycle_no+1;
                        }else{
                            $cycle_no=1;
                        }
                        
                        $temp = [];
                        $temp['patient_id'] =  $patientId;
                        $temp['service_id'] =  $serviceId;
                        $temp['type']       =  $type;
                        $temp['status']      =  'pending';
                        $temp['created_at']  =  date('Y-m-d H:i:s');
                        $temp['cycle_no']    = $cycle_no;

                        //log::info("reminder_patients array");
                       // log::info($temp);

                        $parent_id = DB::connection('tenant')->table('reminder_patients')->insertGetId($temp);

                    }//if insertrecord is 1


                    DB::connection('tenant')->table('reminder_patients')
                    ->where('id',$value->id)
                    ->where('service_id',$value->service_id)
                    ->where('patient_id',$value->patient_id)
                    ->where('status','completed')
                    ->update(['is_processed'=>1]);


                }//foreach
            }//if collection not empty
            else{
                 Log::info("in ReminderPatientsNext next collection empty");

                    DB::connection('tenant')
                    ->table('reminder_patients as r')
                    ->join('patients as p', 'p.id', '=', 'r.patient_id')
                    ->join('examinations as e', 'e.id', '=', 'r.service_id')
                    ->where('r.status', 'completed')
                    ->whereNOTNull('r.deleted_at')
                    ->whereNull('p.deleted_at')
                    //->where('r.patient_id',56531)
                    // Exclude if any pending entry exists
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('reminder_patients as rp')
                            ->whereRaw('rp.patient_id = r.patient_id')
                            ->whereRaw('rp.service_id = r.service_id')
                            ->whereRaw('rp.type = r.type')
                            ->where('rp.status', 'pending')
                            ->whereNull('rp.deleted_at');
                    })
                    // Keep only the latest completed cycle per patient/service/type
                    ->whereRaw('r.cycle_no = (
                        SELECT MAX(r2.cycle_no)
                        FROM reminder_patients r2
                        WHERE r2.patient_id = r.patient_id
                          AND r2.service_id = r.service_id
                          AND r2.type = r.type
                          AND r2.status = "completed"
                          AND r2.deleted_at IS NOT NULL
                    )')
                    ->update(['is_processed'=>0]);

            }//else empty
        }//if reminder execute
    }//_commandOperation

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

    public function checkExists($patientId,$serviceId,$type)
    {
       // log::info("in checkExistsfunction==>");
        $is_exists = DB::connection('tenant')->table('reminder_patients')
                                    ->where('patient_id', $patientId)
                                    ->where('service_id', $serviceId)
                                     ->where('status', 'pending')
                                    ->where('type', $type)
                                    ->count();

       // log::info("is_exists==>");
       // log::info($is_exists);
        if($is_exists==0){
            return 0;
        }else{
             return 1;
        }
    }//checkExists

}
