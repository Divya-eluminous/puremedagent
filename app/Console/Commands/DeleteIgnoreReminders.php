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

use Carbon\Carbon;
use DB;
use Mail;
use DateTime;
use Stancl\Tenancy\Facades\Tenancy; 


class DeleteIgnoreReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Hyn Tenancy signature (commented out)
    // protected $signature = 'deletereminder:daily {--website_id=}';
    
    // Stancl Tenancy signature
    protected $signature = 'deletereminder:daily {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete ignore states checkup and follow up reminders';

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
        //log::info("DeleteIgnoreReminders handle function start");

        // Hyn Tenancy (commented out)
        // $website_id = $this->option('website_id');
        // try
        // {            
        //     if(!empty($website_id) && $website_id!='0')
        //     {               
        //         $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
        //         $this->connection->set($website);   

        //          //log::info($website_id);   

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

        //log::info("DeleteIgnoreReminders handle function end");

    }

    /*-------------------------------------- 
      | Actual functionality
    --------------------------------------*/


    public function _commandOperation($tenant_id)
    //public function _commandOperation($website_id)
    {

         //log::info("In DeleteIgnoreReminders _commandOperation function ..........");
         
         // Stancl Tenancy - Get tenant and initialize context
         $tenant = \App\Models\Tenant::find($tenant_id);
         if($tenant) {
            // Log::info("Found tenant: " . $tenant->ordination_name);
             tenancy()->initialize($tenant);
             config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
             DB::purge('tenant');
            // Log::info("Tenant context initialized for: " . $tenant->ordination_name);
         }

        $is_reminder_execute = DB::connection('tenant')->table('settings')->where(['setting_key'=>'REMINDER_SETTING','status'=>'1'])->first();


        //log::info(date('Y-m-d'));
        //log::info(date('H:i'));
               

        if(!empty($is_reminder_execute))
        {
    
            
           $collections =  DB::connection('tenant')->table('patient_has_service_reminder')
                        ->join('patients', 'patients.id' , '=', 'patient_has_service_reminder.patient_id')
                        ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                        ->whereDate('patient_has_service_reminder.reminder_date', '<', date('Y-m-d'))
                        //->where('patient_has_service_reminder.reminder_status','Set')
                         //->where('patients.id',36005) //local general reminder
                         //->where('patients.id',47691) //local checkup reminder
                         // ->where('patients.id', 86) //stage checkup reminder

                        //->whereIn('patients.id',[47521,47522,47523,47744,47691])
                        ->where('patient_has_service_reminder.type','!=','age')//commented on 18-apr-24
                        ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                        ->whereNull('patients.deleted_at') 
                        ->where('patient_has_service_reminder.is_deleted_from_ignore_state',0) 
                        ->groupBy('patient_has_service_reminder.patient_id')
                        ->orderBy('patient_has_service_reminder.id','desc')
                        ->get(['patient_has_service_reminder.id as reminder_id','patient_has_service_reminder.reminder_date','patients.id as patient_id','patients.mobile_no','patients.age as patient_age','patients.country_code','patients.email','examinations.name as name','examinations.id as exam_id','patients.sendMail','patients.sendSMS',
                            'patient_has_service_reminder.appointment_id',
                            'patient_has_service_reminder.status',  
                            'patient_has_service_reminder.reminder_status', 
                            'patient_has_service_reminder.service_id', 
                            'patient_has_service_reminder.type',  
                            'patients.first_name',
                            'patients.family_name'
                       ]);

           //   dump("Collections....");
             // dd($collections);

            Log::info("Collections..in DeleteIgnoreReminders cron..");
            Log::info($collections);            

            $countCollections = $collections->count(); // Get the count of the results            
            // dump("Count of collections=======>");
            // dd($countCollections);    


            $currentDate = Date('d-m-Y');    //8-apr-24  


             // dump("currentDate===>");  
             // dump($currentDate);   



 
            if(!empty($collections))
            {
                foreach ($collections as $key => $value)
                {

                     //log::info('reminder_id');
                    // log::info($value->reminder_id);

                    $patientid= $value->patient_id;

                    //commented on 11-march-26

                    /*$getRemider = DB::connection('tenant')->select(("SELECT t1.*,patients.mobile_no,patients.age,patients.country_code,patients.email,patients.sendMail,patients.sendSMS,examinations.name FROM patient_has_service_reminder t1
                                    JOIN patients on patients.id=t1.patient_id 
                                    AND patients.deleted_at IS NULL
                                    JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
                                    AND examinations.deleted_at IS NULL
                                    JOIN (SELECT service_id,reminder_date,next_reminder_date, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.type IN('control','general') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL

                                      -- AND (DATE(t2.reminder_date) = CURDATE() OR DATE(t2.next_reminder_date) = CURDATE())
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));  */

                     //added on 11-march-26 commented on 30-march-26              
                    /* $getRemider = DB::connection('tenant')->select(("SELECT t1.*,patients.mobile_no,patients.age,patients.country_code,patients.email,patients.sendMail,patients.sendSMS,examinations.name FROM patient_has_service_reminder t1
                                    JOIN patients on patients.id=t1.patient_id 
                                    AND patients.deleted_at IS NULL
                                    JOIN examinations on examinations.id=t1.service_id
                                    AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                                    
                                    JOIN (SELECT service_id,reminder_date,next_reminder_date, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.type IN('control','general') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL

                                      -- AND (DATE(t2.reminder_date) = CURDATE() OR DATE(t2.next_reminder_date) = CURDATE())
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC")); */


                    //changed on 30-march-26 type not in
                     //commneted on 21-apr-26   
                    /* $getRemider = DB::connection('tenant')->select(("SELECT t1.*,patients.mobile_no,patients.age,patients.country_code,patients.email,patients.sendMail,patients.sendSMS,examinations.name FROM patient_has_service_reminder t1
                                    JOIN patients on patients.id=t1.patient_id 
                                    AND patients.deleted_at IS NULL
                                    JOIN examinations on examinations.id=t1.service_id
                                    AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                                    
                                    JOIN (SELECT service_id,reminder_date,next_reminder_date, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type NOT IN ('general','control'))))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.type IN('control','general') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL

                                      -- AND (DATE(t2.reminder_date) = CURDATE() OR DATE(t2.next_reminder_date) = CURDATE())
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));  */


                    //changed on 21-apr-26 for 2imst as control reminder              
                     $getRemider = DB::connection('tenant')->select(("SELECT t1.*,patients.mobile_no,patients.age,patients.country_code,patients.email,patients.sendMail,patients.sendSMS,examinations.name FROM patient_has_service_reminder t1
                                    JOIN patients on patients.id=t1.patient_id 
                                    AND patients.deleted_at IS NULL
                                    JOIN examinations on examinations.id=t1.service_id
                                    AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (t1.type = 'control' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                                    
                                    JOIN (SELECT service_id,reminder_date,next_reminder_date, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type NOT IN ('general','control'))))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.type IN('control','general') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL

                                      -- AND (DATE(t2.reminder_date) = CURDATE() OR DATE(t2.next_reminder_date) = CURDATE())
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));                                                                                                                                                                     
                        

                    $getRemider = collect($getRemider)->map(function($x){ 
                        return (array) $x; 
                    })->toArray();
                    
                     // dump("getRemider=======>");
                     // dump($getRemider);

                    Log::info("getRemider..in DeleteIgnoreReminders cron..");
                    Log::info($getRemider);  

                    if(!empty($getRemider) && sizeof($getRemider)>0)
                    {     

                
                        foreach ($getRemider as $key_rem => $value_rem) 
                        {


                                $reminderDate = Date('d-m-Y',strtotime($value_rem['reminder_date']));    

                                //log::info($reminderDate);

                                $status =  'inaktiv';

                                if((strtotime($reminderDate) < strtotime($currentDate)) || $value_rem['appointment_id']==0)
                                {
                                    $status =  'aktiv';
                                }
                                if($value_rem['reminder_status']=='ignore' && $value_rem['status']!='deactivate')
                                {
                                     $status=__('admin.IGNORE_STATUS');
                                } 
                                if($value_rem['reminder_status']=='ignore' && $value_rem['status']=='deactivate')
                                {
                                    $status ='inaktiv';
                                }   

                                if($value_rem['appointment_id']==0 && $value_rem['status']=='deactivate')
                                {
                                    $status ='inaktiv';
                                }


                                 // dump("status=======>");
                                 // dump($status);

                                 // dump("appointment id=======>");
                                 // dump($value_rem['appointment_id']);

                                Log::info("status..in DeleteIgnoreReminders cron..");
                                Log::info($status); 

                                Log::info("patientid.in DeleteIgnoreReminders cron..");
                                Log::info($patientid); 

                                Log::info("appointment.id.in DeleteIgnoreReminders cron..");
                                Log::info($value_rem['appointment_id']); 

                            

                                if($value_rem['type']=='control')
                                {
                                    //dump($status);

                                    Log::info("in DeleteIgnoreReminders cron.type is control.");


                                     $is_doctor_set_reminder =  DB::connection('tenant')->table('patient_has_service_control_reminder_setting')->where(
                                    ['patient_id' => $patientid,
                                    'appointment_id' =>$value_rem['appointment_id'],
                                    'service_id' => $value_rem['service_id'],
                                    'status' => '1',
                                    ]
                                    )->first();

                                    $isSetByDoctor=0;
                               
                                    if($is_doctor_set_reminder)
                                    {

                                        Log::info("in DeleteIgnoreReminders .type is control.in is_doctor_set_reminder");
  
                                        $isSetByDoctor=1;

                                        //commented on 6-nov-25
                                        // $checkup_period_controls =  $is_doctor_set_reminder->control_interval;
                                        // $checkup_period_frequency_type = $is_doctor_set_reminder->control_frequency;

                                         //start added on 6-nov-25

                                       //commented on 21-apr-26  
                                       /* $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                         ['service_id' => $value_rem['service_id'],'activated_reminder' => 'checkup'])->first();*/


                                        //changed on 21-apr-26   
                                        $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                        ->join('examinations', 'examinations.id', '=', 'preferred_channels_for_reminders_setting.service_id')
                                        ->where('preferred_channels_for_reminders_setting.service_id', $value_rem['service_id'])
                                        ->where('preferred_channels_for_reminders_setting.activated_reminder', 'checkup')
                                        ->where('examinations.show_as_reminder', '1')  // added: only use service-level setting if show_as_reminder is enabled
                                        ->whereNull('examinations.deleted_at')
                                        ->first();

                                    
                                        if($is_service_has_reminder)
                                        {

                                           Log::info("in DeleteIgnoreReminders .type is control.in is_doctor_set_reminder in is_service_has_reminder");

                                            $checkup_period_controls = $is_service_has_reminder->checkup_end_cycle;
                                            $checkup_period_frequency_type = $is_service_has_reminder->checkup_end_cycle_frequency_type;

                                            Log::info("in DeleteIgnoreReminders .type is control.in is_doctor_set_reminder in is_service_has_reminder checkup_period_controls");
                                            Log::info($checkup_period_controls);

                                            Log::info("in DeleteIgnoreReminders .type is control.in is_doctor_set_reminder in is_service_has_reminder checkup_period_frequency_type");
                                            Log::info($checkup_period_frequency_type);

                                        }// is_set_reminder 
                                        else
                                        {

                                            Log::info("in else part is_service_has_reminder global");

                                            $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                                [
                                                'type' => 'global',
                                                ]
                                                )->first();

                                            $checkup_period_controls =  $is_service_has_reminder->checkup_end_cycle;
                                            $checkup_period_frequency_type = $is_service_has_reminder->checkup_end_cycle_frequency_type;

                                            Log::info("in DeleteIgnoreReminders .type is control.in is_doctor_set_reminder in global checkup_period_controls");
                                            Log::info($checkup_period_controls);

                                            Log::info("in DeleteIgnoreReminders .type is control.in is_doctor_set_reminder in global checkup_period_frequency_type");
                                            Log::info($checkup_period_frequency_type);
                                        }

                                        //end added on 6-nov-25 

                                         // dump("checkup_period_controls");
                                         // dump($checkup_period_controls);

                                         // dump("checkup_period_frequency_type");
                                         // dd($checkup_period_frequency_type);


                                        //get first cycle date for getting ignore date added on 16-march-26

                                        $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                                          ->where('patient_id',$patientid)
                                          ->where('service_id',$value_rem['service_id'])
                                          ->where('appointment_id',$value_rem['appointment_id'])
                                          ->where('cycle_no',1) 
                                          ->first();
     

                                        if(!empty($firstReminderdate)) 
                                        {

                                            Log::info("in DeleteIgnoreReminders cron.type is control in is_set_reminder not empty firstReminderdate");
                                            $reminderDate=Date('d-m-Y',strtotime($firstReminderdate->reminder_date));
                                            Log::info($reminderDate);


                                        }else{

                                            Log::info("in DeleteIgnoreReminders cron.type is control in is_set_reminder empty firstReminderdate");
                                            $reminderDate=$reminderDate;
                                            Log::info($reminderDate);

                                        }




                                        $endCycleDyas = $this->_getDate(($reminderDate),$checkup_period_controls,$checkup_period_frequency_type);  
                                        $endcycle_date = $this->_filterWeekendAndHoiliday(($reminderDate),$endCycleDyas,0,'plus');

                                        Log::info("in DeleteIgnoreReminders cron.type is control in is_set_reminder empty reminderDate");
                                        Log::info($reminderDate);

                                        Log::info("in DeleteIgnoreReminders cron.type is control in is_set_reminder empty endcycle_date");
                                        Log::info($endcycle_date);

                                      
                                   
                                        // dump(' inendCycleDyas===>');
                                        // dump($endCycleDyas);

                                        // dump(' in app id 0 endcycle_date===>');
                                        // dump($endcycle_date);

                                        $reminderDate = new DateTime($reminderDate);
                                        $endDate = new DateTime($endcycle_date);
                                        //$endDate = '2024-01-10 09:00:00';                             
                                        $date_today=new DateTime();

                                      
                                        // dump(' reminderDate===>');
                                        // dump($reminderDate);

                                        // dump(' endDate===>');
                                        // dump($endDate);

                                   
                                       
                                                                                               

                                        if($endCycleDyas>0)
                                        {

                                            //added by vijay 19/7/2024 #165  c)
                                            $date_today = $date_today->format('Y-m-d H:i:s');
                                            $date_today = Carbon::parse($date_today);
                                            $reminderDate = $reminderDate->format('Y-m-d H:i:s');
                                            $reminderDate = Carbon::parse($reminderDate);
                                            $endDate = $endDate->format('Y-m-d H:i:s');
                                            $endDate = Carbon::parse($endDate);
                                            $comparison1 = $endDate->lessThan($date_today);
                                            // $comparison2 = $reminderDate->greaterThanOrEqualTo($endDate);


                                            $comparison2 = $reminderDate->greaterThan($endDate);
                                                //if parameter 6 has not passed yet
                                                // if($endDate<$date_today){
                                                //      $status=__('admin.IGNORE_STATUS');
                                                // }
                                                // else if($reminderDate>=$endDate)
                                                // {
                                                // 
                                                //     $status=__('admin.IGNORE_STATUS');
                                                // } 
                                            if ($comparison1) {
                                                $status = __('admin.IGNORE_STATUS');
                                            } else if ($comparison2) {
                                                $status = __('admin.IGNORE_STATUS');
                                            }
                                            // end

                                        }//if endCycleDyas > 0

                                    }//if is_doctor_set_reminder 

                                }//if reminder type is control

                            // added by vijay 23/7/2024 #165 b)
                            if ($value_rem['type'] == 'general') {


                                Log::info("in DeleteIgnoreReminders cron.type is general.");

                                //commented on 20-march-25
                                /*$is_set_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                    ['service_id' => $value_rem['service_id']]
                                )->first();*/

                                 //added below code on 20-march-25 
                                $is_set_reminder1 = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                    'recommanded_service_id', $value_rem['service_id'])->where( 'activated_reminder','general')->first();
                                if(isset($is_set_reminder1)){
                                    $is_set_reminder = $is_set_reminder1;
                                }else{
                                    $is_set_reminder =DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                     ['service_id' => $value_rem['service_id']])->first();

                                } 

                                if ($is_set_reminder) {

                                   Log::info("in DeleteIgnoreReminders cron.type is general in is_set_reminder");

                                    //commented on 20-march-25
                                    // $checkup_period_controls = $is_set_reminder->general_first_frequency;
                                    // $checkup_period_frequency_type = $is_set_reminder->general_first_frequency_type;

                                    //changed on 20-march-25
                                    $checkup_period_controls = $is_set_reminder->general_end_cycle;
                                    $checkup_period_frequency_type = $is_set_reminder->general_end_cycle_frequency_type;


                                    //get first cycle date for getting ignore date added on 16-march-26
                                    $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                                      ->where('patient_id',$patientid)
                                      ->where('service_id',$value_rem['service_id'])
                                      ->where('appointment_id',$value_rem['appointment_id'])
                                      ->where('cycle_no',1) 
                                      ->first();
 

                                    if(!empty($firstReminderdate)) 
                                    {
                                        Log::info("in DeleteIgnoreReminders cron.type is general in is_set_reminder not empty firstReminderdate");
                                        $reminderDate=Date('d-m-Y',strtotime($firstReminderdate->reminder_date));
                                        Log::info($reminderDate);


                                    }else{

                                        Log::info("in DeleteIgnoreReminders cron.type is general in is_set_reminder empty firstReminderdate");
                                        $reminderDate=$reminderDate;
                                        Log::info($reminderDate);

                                    }


                                    //commented on 16-march-26
                                    $endCycleDyas = $this->_getDate(($reminderDate), $checkup_period_controls, $checkup_period_frequency_type);
                                    $endcycle_date = $this->_filterWeekendAndHoiliday(($reminderDate), $endCycleDyas, 0, 'plus');

                                  
                                    Log::info("in DeleteIgnoreReminders cron.type is general in is_set_reminder empty reminderDate");
                                    Log::info($reminderDate);

                                    Log::info("in DeleteIgnoreReminders cron.type is general in is_set_reminder empty endcycle_date");
                                    Log::info($endcycle_date);



                                    // $reminderDate = new DateTime($reminderDate);
                                    $endDate = new DateTime($endcycle_date);
                                    $date_today = new DateTime();

                                    $date_today = $date_today->format('Y-m-d H:i:s');
                                    $date_today = Carbon::parse($date_today);
                                    // $reminderDate = $reminderDate->format('Y-m-d H:i:s');
                                    // $reminderDate = Carbon::parse($reminderDate);
                                    $endDate = $endDate->format('Y-m-d H:i:s');
                                    $endDate = Carbon::parse($endDate);

                                    if ($date_today->toDateString() > $endDate->toDateString()) {

                                        Log::info("in DeleteIgnoreReminders cron.type is general in is_set_reminder ignore date condition");
                                        $status = __('admin.IGNORE_STATUS');
                                    }
                                }
                            }
                            // end

                             
                                 // dump("end status=======>");
                                 // dump($status);

                                Log::info("end status=======>");
                                Log::info($status);

                           
                                if(($value_rem['type']=='control' && $status=="ignored")) 
                                {
                                   // dump("in ignored status====>");

                                     $ids =  DB::connection('tenant')->table('patient_has_service_reminder')
                                    ->where('type','control')
                                    ->whereNull('deleted_at')
                                    ->where('service_id',$value_rem['service_id']) 
                                    ->where('patient_id',$patientid)
                                    ->where('appointment_id','!=',0)
                                    ->pluck('id')
                                    ->toArray();   

                                  
                                    if(isset($ids) && !empty($ids))
                                    {
                                        

                                        // dump('id holder====>');      
                                        // dump($ids);   

                                        // dump($value_rem['service_id']);

                                        DB::connection('tenant')->table('patient_has_service_reminder')
                                                    ->where('type','control')
                                                    ->whereNull('deleted_at')
                                                    ->where('service_id',$value_rem['service_id'])  
                                                    ->where('patient_id',$patientid)
                                                    ->where('appointment_id','!=',0)
                                                    ->whereNull('deleted_at')
                                                    ->update(['deleted_at'=>date('Y-m-d H:i:s'),'is_deleted_from_ignore_state'=>1]);                   


                                        $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                                    ->whereIn('service_reminder_id',$ids)
                                                    ->update(['deleted_at'=>date('Y-m-d H:i:s')]); 
                                    }//isset ids   
 
                
                                }//if status is ignored for control
                                 

                                if(($value_rem['type']=='general' && $status=="ignored")) 
                                {
                                   // dump("in general ignored status====>");

                                     $ids =  DB::connection('tenant')->table('patient_has_service_reminder')
                                            ->where('type', 'general')
                                            ->whereNull('deleted_at')
                                            ->where('service_id', $value_rem['service_id'])
                                            ->where('patient_id', $patientid)
                                            ->where('appointment_id', '!=', 0)
                                            ->pluck('id')
                                            ->toArray();
                                     if(isset($ids) && !empty($ids))
                                    {
                                        
                                        // dump('id holder====>');      
                                        // dump($ids);   


                                         DB::connection('tenant')->table('patient_has_service_reminder')
                                                    ->where('type','general')
                                                    ->whereNull('deleted_at')
                                                    ->where('service_id',$value_rem['service_id'])  
                                                    ->where('patient_id',$patientid)
                                                    ->where('appointment_id','!=',0)
                                                    ->whereNull('deleted_at')
                                                    ->update(['deleted_at'=>date('Y-m-d H:i:s'),'is_deleted_from_ignore_state'=>1]);                   


                                        $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                                    ->whereIn('service_reminder_id',$ids)
                                                    ->update(['deleted_at'=>date('Y-m-d H:i:s')]); 
                                    }//isset ids   
 
                
                                }//if status is ignored general
                                

                        }//foreach get reminder
                    }//if getreminder

                }//foreach
            }//if
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
    }//

}
