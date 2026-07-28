<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
// Hyn Tenancy imports (commented out)
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\AppointmentModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\PatientsHasServiceControlReminderModel;
use App\Models\PatientHasReminder;
use App\Models\ExaminationsModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\PatientsModel;

use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
use DateTime;
use Stancl\Tenancy\Facades\Tenancy;

class ReminderStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Hyn Tenancy signature (commented out)
    // protected $signature = 'ReminderStatus:update {--website_id=}';
    
    // Stancl Tenancy signature
    protected $signature = 'ReminderStatus:update {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update reminder process';

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
                                AppointmentModel $AppointmentModel,
                                AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
                                ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
                                PatientsHasServiceControlReminderModel $PatientsHasServiceControlReminderModel,
                                PatientHasReminder $PatientHasReminder,
                                PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
                                ExaminationsModel $ExaminationsModel,
                                PatientsModel $PatientsModel
                                )
    {
        
        parent::__construct();
        // Hyn Tenancy initialization (commented out)
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
        $this->BaseModel = $AppointmentModel; 
        $this->AppointmentTypeHasExaminationsModel  = $AppointmentTypeHasExaminationsModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->PatientsHasServiceControlReminderModel = $PatientsHasServiceControlReminderModel; 
        $this->PatientHasReminder = $PatientHasReminder;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->PatientsModel = $PatientsModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        //log::info("ReminderStatus-Handle");
        
        // Hyn Tenancy (commented out)
        // $website_id = $this->option('website_id');
        // // $website_id = 1;
        // // dd("hi");
        // try
        // {
        //     //self::_commandOperation();

        //     if(!empty($website_id) && $website_id!='0')
        //     {
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
    //public function handle()
    {
        //log::info('in handle function start');
        // dd("jh");
       // log::info("ReminderStatus");
        
        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
           // Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
           // Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }
        
        self::_completedAppoitment();
        self::_missedAppoitment();

        //log::info('in handle function end');
    } 

    public function _completedAppoitment()
    { 
        //log::info("ReminderStatus _CompleteAppointemnt");
        $doneAppoitments =  DB::connection('tenant')->table('appointment')
                            ->select('patient_id','appointment.id as appointment_id','appointment_type_id','patients.birth_date','patients.age','appointment.start_date')
                            ->leftjoin('patients','patients.id','appointment.patient_id')
                            ->orderBy('start_date','DESC')
                            //->where('patient_id',79) 
                            ->whereIn('appointment_status',array('Fertig'))
                            ->where('reminder_status','0')
                            ->get();
            if(!empty($doneAppoitments))
            {
                foreach ($doneAppoitments as $doneAppoitment) 
                {
                    $allServices = DB::connection('tenant')->table('appointment_has_examinations')
                    ->select('examinations.*')
                    ->leftjoin('examinations','examinations.id','appointment_has_examinations.examination_id')
                    ->where('appointment_id',$doneAppoitment->appointment_id)
                    ->whereRaw("examinations.show_as_reminder='1'")
                    ->get();
                    if(!empty($doneAppoitment->birth_date))               
                    {
                        $from = new DateTime($doneAppoitment->birth_date);
                        $to   = new DateTime('today');
                        $age =  $from->diff($to)->y;
                        $data['age'] = $age; 
                    }
                    else {
                        $data['age'] = $doneAppoitment->age; 
                    }
                    $data['birth_date'] = $doneAppoitment->birth_date;
                    if(!empty($allServices) && count($allServices) > 0)
                    {
                        $this->_checkAndAddServiceReminder($allServices,$doneAppoitment->patient_id,$doneAppoitment->appointment_id,$doneAppoitment->start_date,$data);  
                        DB::connection('tenant')->table('appointment')->where('id',$doneAppoitment->appointment_id)->update(['reminder_status'=>'1']);
                    }
                }    
            }
        //log::info("ReminderStatus _CompleteAppointemnt end");

    }


    public function _reactiveAppoitment()
    {
       // log::info("ReminderStatus=>_reactiveAppoitment");
        $sql = "SELECT * FROM `patient_has_reminder` WHERE id IN (
                  select max(patient_has_reminder.id) from `patient_has_reminder` 
                  left join `patient_has_service_reminder` on `patient_has_service_reminder`.`id` = `patient_has_reminder`.`service_reminder_id` 
                  where date(`last_reminder_date`) <= '".date('Y-m-d')."' and date(`last_reminder_date`) >= '2022-10-10' 
                  and `patient_has_reminder`.`deleted_at` is null  and appointment_id!=0 and patient_has_reminder.status='activate'
                  GROUP by patient_has_service_reminder.patient_id,service_id)";
        $reactivateReminder = DB::connection('tenant')->select($sql);
        if(!empty($reactivateReminder) && count($reactivateReminder) > 0)
        {
            foreach ($reactivateReminder as $reminder_key => $reminder_value) 
            {
                $is_appoitment_book = DB::connection('tenant')->table('appointment')
                                    ->whereDate('start_date', '>=', $reminder_value->last_reminder_date)
                                    ->whereDate('start_date', '<=', $reminder_value->next_reminder_date)
                                    ->where('patient_id',$reminder_value->patient_id)
                                    ->get();
                
                if(!empty($is_appoitment_book ) && count($is_appoitment_book ) > 0)
                {
                    $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                       ->where('id',$reminder_value->id)
                                       ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                }
                else {
                    $reminder_details = DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where('id',$reminder_value->service_reminder_id)
                                        ->first();
                    
                    if(!empty($reminder_details)){

                        $serviceDetail =DB::connection('tenant')->table('examinations')
                                    ->where('id',$reminder_details->service_id)
                                    ->first();
                        $patientDetails = DB::connection('tenant')->table('patients')
                                    ->select('age','birth_date','id')
                                    ->where('id',$reminder_details->patient_id)
                                    ->first();
                        if($serviceDetail->show_as_reminder==1 && !empty($patientDetails)){
                            
                            if(!empty($patientDetails->birth_date))               
                            {
                                $from = new DateTime($patientDetails->birth_date);
                                $to   = new DateTime('today');
                                $age =  $from->diff($to)->y;
                                $data['age'] = $age; 
                            }else
                            {
                                 $data['age'] = $patientDetails->age; 
                            }
                            $data['birth_date'] = $patientDetails->birth_date;
                            
                            $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                                [
                                                'service_id' => $serviceDetail->id,
                                                 'is_reminder_updated' => '0'
                                                ]
                                                )->first();
                            $default_reminder = 'general';
                            if(empty($is_service_has_reminder))
                            {                          
                                $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                                    [
                                                    'type' => 'global',
                                                    ]
                                                    )->first();
                            }else
                            {
                                $default_reminder = $is_service_has_reminder->activated_reminder;
                                $h_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                                [
                                                'type' => 'global',
                                                ]
                                                )->first(['holiday_reminder','checkup_number_of_interval','checkup_time_interval','checkup_first_frequency','checkup_new_frequency','checkup_period_controls','checkup_time_interval_frequency_type','checkup_first_frequency_type','checkup_new_frequency_type','checkup_period_frequency_type']);
                                $is_service_has_reminder->checkup_number_of_interval =  $h_reminder->checkup_number_of_interval;
                                $is_service_has_reminder->checkup_time_interval =  $h_reminder->checkup_time_interval;
                                $is_service_has_reminder->checkup_first_frequency =  $h_reminder->checkup_first_frequency;
                                $is_service_has_reminder->checkup_new_frequency =  $h_reminder->checkup_new_frequency;
                                $is_service_has_reminder->checkup_period_controls =  $h_reminder->checkup_period_controls;
                                $is_service_has_reminder->checkup_time_interval_frequency_type =  $h_reminder->checkup_time_interval_frequency_type;
                                $is_service_has_reminder->checkup_first_frequency_type =  $h_reminder->checkup_first_frequency_type;
                                $is_service_has_reminder->checkup_new_frequency_type =  $h_reminder->checkup_new_frequency_type;
                                $is_service_has_reminder->checkup_period_frequency_type =  $h_reminder->checkup_period_frequency_type;
                            }  
                            $todays_date = $reminder_value->next_reminder_date;

                            $appointment_id = $reminder_details->appointment_id; 
                            $patient_id = $reminder_details->patient_id;
                            $ageReminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                ['service_id' => $reminder_details->service_id,'activated_reminder' => 'age']
                                )->get();
                            if(!empty($data['age']) && $data['age']!='' && !empty($ageReminder->toArray()) && count($ageReminder) < 2)  
                            {   
                                DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id',$reminder_details->patient_id)
                                ->where('service_id',$reminder_details->service_id)
                                ->where('appointment_id',$reminder_details->appointment_id)
                                ->where('type',$reminder_details->type)
                                ->whereDate('reminder_date','<',date('Y-m-d'))
                                ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                                $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                               ->leftjoin('patient_has_service_reminder','patient_has_service_reminder.id','patient_has_reminder.service_reminder_id')
                                               ->whereDate('last_reminder_date','<',date('Y-m-d'))
                                               ->whereDate('last_reminder_date','>=','2021-01-01')
                                               ->where('patient_has_reminder.patient_id',$reminder_value->patient_id)
                                               ->where('patient_has_service_reminder.service_id',$serviceDetail->id)
                                               ->update(['patient_has_reminder.deleted_at'=>date('Y-m-d H:i:s')]);

                                $this->_reactivateAgeReminder($ageReminder[0],$appointment_id,$todays_date,$patient_id,$data,$serviceDetail->id); 
                                //log::info($patient_id);
                                //log::info($serviceDetail->id);
                            }
                        }   
                    }   
                }
            }
        } 
    }
    public function _reactiveAppoitmentOLD()
    {
        $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                           ->whereDate('last_reminder_date','<=',date('Y-m-d'))
                           ->whereDate('last_reminder_date','>=','2021-01-01')
                           ->whereNull('deleted_at')
                           ->get();
        if(!empty($reactivateReminder) && count($reactivateReminder) > 0)
        {
            foreach ($reactivateReminder as $reminder_key => $reminder_value) 
            {
                $is_appoitment_book = DB::connection('tenant')->table('appointment')
                                    ->whereDate('start_date', '>=', $reminder_value->last_reminder_date)
                                    ->whereDate('start_date', '<=', $reminder_value->next_reminder_date)
                                    ->where('patient_id',$reminder_value->patient_id)
                                    ->get();

                if(!empty($is_appoitment_book ) && count($is_appoitment_book ) > 0)
                {
                    $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                       ->where('id',$reminder_value->id)
                                       ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                }
            }
        }   
    }

    public function _missedAppoitment()
    {
        //log::info("_missedAppoitment");
       //Missed appoitment
                $missedAppoitments =  DB::connection('tenant')->table('appointment')
                                ->select('patient_id','appointment.id as appointment_id','appointment_type_id','patients.birth_date','patients.age','appointment.start_date')
                                ->leftjoin('patients','patients.id','appointment.patient_id')
                                ->orderBy('start_date','DESC')
                                ->where('reminder_status','0')
                                ->whereIn('appointment_status',array('Vermisst'))
                                ->get();

                foreach($missedAppoitments as $missed_key=>$missed_value)
                {
                    $allServices = DB::connection('tenant')->table('appointment_has_examinations')
                                        ->select('examinations.*')
                                        ->leftjoin('examinations','examinations.id','appointment_has_examinations.examination_id')
                                        ->where('appointment_id',$missed_value->appointment_id)
                                        ->where('examinations.show_as_reminder','1')
                                        ->get();


                    if(!empty($allServices) && count($allServices) > 0)
                    {
                        foreach ($allServices as $service_key => $service_value) 
                        {
                            //log::info("_missedAppoitment-foreach");
                            //log::info($missed_value->patient_id);
                            $active_ids = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where(['patient_id'=>$missed_value->patient_id,
                                'reminder_status'=>'Set',
                                'status'=>'activate',
                                'service_id'=>$service_value->id])
                                ->whereDate('reminder_date','>=',date('Y-m-d'))
                                ->get();
                            $active_ids_holder = [];
                            if(!empty($active_ids) && count($active_ids) > 0)
                            {
                                foreach($active_ids as $id=>$value)
                                {                    
                                    $active_ids_holder[] = $value->id;
                                }
                                
                            }
                            if(!empty($active_ids_holder) && count($active_ids_holder) > 0)
                            {
                            $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                       ->whereIn('service_reminder_id',$active_ids_holder)
                                       ->update(['status'=>'activate']);
                                   }

                            $ids = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id',$missed_value->patient_id)
                                ->where('service_id',$service_value->id)
                                ->where('appointment_id',$missed_value->appointment_id)
                                ->select('id')
                                ->get();
                                $id_holder = [];
                                if(!empty($ids))
                                {
                                    foreach($ids as $id=>$value)
                                    {
                                        
                                        $id_holder[] = $value->id;
                                    }
                                    
                                }
                            
                             DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id',$missed_value->patient_id)
                                ->where('service_id',$service_value->id)
                                ->where('appointment_id',$missed_value->appointment_id)
                                ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                                $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                       ->whereIn('service_reminder_id',$id_holder)
                                       ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                             DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id',$missed_value->patient_id)
                                ->where('service_id',$service_value->id)
                                ->where('status','deactivate')
                                ->update(['status'=>'activate']);
                        }
                    }
                    DB::connection('tenant')->table('appointment')->where('id',$missed_value->appointment_id)->update(['reminder_status'=>'1']);                
                }  
    }

    public function _checkAndAddServiceReminder($all_services,$patient_id,$appointment_id,$appointment_start_date,$data)
    {

        if(!empty($all_services) && count($all_services) > 0)
        {
            foreach ($all_services as $service_key => $service_value) 
            {
                $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_value->id,
                                         //'is_reminder_updated' => '0'
                                        ]
                                        )->first();
               
                $default_reminder = 'general';
                if(empty($is_service_has_reminder))
                {                          
                    $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'type' => 'global',
                                        ]
                                        )->first();
                    // Log::info('Default setting');
                    // Log::info(json_encode($is_service_has_reminder));
                    // dd('sss');
                }else
                {
                    $default_reminder = $is_service_has_reminder->activated_reminder;

                    //commented below code on 20-march-24 for dont take global setting 

                    /*
                    $h_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'type' => 'global',
                                        ]
                                        )->first(['holiday_reminder','checkup_number_of_interval','checkup_time_interval','checkup_first_frequency','checkup_new_frequency','checkup_period_controls','checkup_time_interval_frequency_type','checkup_first_frequency_type','checkup_new_frequency_type','checkup_period_frequency_type']);
                    $is_service_has_reminder->checkup_number_of_interval =  $h_reminder->checkup_number_of_interval;
                    $is_service_has_reminder->checkup_time_interval =  $h_reminder->checkup_time_interval;
                    $is_service_has_reminder->checkup_first_frequency =  $h_reminder->checkup_first_frequency;
                    $is_service_has_reminder->checkup_new_frequency =  $h_reminder->checkup_new_frequency;
                    $is_service_has_reminder->checkup_period_controls =  $h_reminder->checkup_period_controls;
                    $is_service_has_reminder->checkup_time_interval_frequency_type =  $h_reminder->checkup_time_interval_frequency_type;
                    $is_service_has_reminder->checkup_first_frequency_type =  $h_reminder->checkup_first_frequency_type;
                    $is_service_has_reminder->checkup_new_frequency_type =  $h_reminder->checkup_new_frequency_type;
                    $is_service_has_reminder->checkup_period_frequency_type =  $h_reminder->checkup_period_frequency_type;

                    */

                    // Log::info(json_encode($is_service_has_reminder));
                    // dd('d');


                }  
                $is_doctor_set_reminder = db::connection('tenant')->table('patient_has_service_control_reminder_setting')->where(
                    ['patient_id' => $patient_id,
                    'appointment_id' => $appointment_id,
                    'service_id' => $service_value->id,
                    'status' => '1',
                    ]
                    )->first();

                if($is_doctor_set_reminder)
                {
                    $is_service_has_reminder->checkup_period_controls =  $is_doctor_set_reminder->control_interval;
                    $is_service_has_reminder->checkup_period_frequency_type =  $is_doctor_set_reminder->control_frequency;
                } 
                //Log::info('Default reminder');
                //Log::info(json_encode($default_reminder));
                //dd($default_reminder);
                /*Check if that service is general and it is set reminder for 
                 another service added by swati 19-Sep-22*/
                $check_general_recommanded_remidner = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_value->id,
                                        'activated_reminder' => 'general'
                                        ]
                                        )->first(['recommanded_service_id']);
                if(!empty($check_general_recommanded_remidner) && $check_general_recommanded_remidner->recommanded_service_id)
                      $service_id = $check_general_recommanded_remidner->recommanded_service_id;
                else  $service_id = $service_value->id;


               
                //Log::info('Default setting');
               // Log::info($default_reminder);
               // Log::info($patient_id);
                /*END Check if that service is general and it is set reminder for another service*/

                if($default_reminder == 'general')
                {

                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    //dump("ReminderStatus-_checkAndAddServiceReminder _generalReminder");
                    //Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_generalReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                }          
                else if($default_reminder == 'age')
                {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    //log::info("ReminderStatus-AGE-_checkAndAddServiceReminder");
                    //Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_ageReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                }
                else if($default_reminder == 'checkup')
                {
                    // start code when doctor set reminder from dcotor dashboard then only set the reminder added on 15-march-24
                  if(isset($is_doctor_set_reminder) && !empty($is_doctor_set_reminder))
                  {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    //log::info("ReminderStatus-CHECKUP-_checkAndAddServiceReminder");
                    //Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_controlReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);

                    }//if isset is_doctor_set_reminder

                  //end code when doctor set reminder from dcotor dashboard then only set the reminder added on 15-march-24
                }                        
            }
        }  
    }

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
    }

    public function _generalReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        // dump("in general reminder...");
        // dump("is_service_has_reminder==>");
        // dump($is_service_has_reminder);

        // dump("service_id==>");
        // dump($service_id);
        

        $reminder_array = [];

        // 1st reminder
        $start_date = $start_date;

        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->general_period,$is_service_has_reminder->general_period_frequency_type);

        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->general_first_frequency,$is_service_has_reminder->general_first_frequency_type);

        // $first_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($period_date)) . " -".(int)$value3_days." day"));
        $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');
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
        //Log::info("ReminderStatus-_generalReminder-".$patient_id);
        //Log::info($reminder_array);

        //Added by swati 12-May-23===================================
        $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
                                ->first();
        // if(!empty($firstReminderdate)) 
        //     $first_remidner_date=$firstReminderdate->reminder_date;
        // else $first_remidner_date=$start_date;  
        // $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->general_end_cycle,$is_service_has_reminder->general_end_cycle_frequency_type);
        // $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$endCycleDyas,$is_service_has_reminder->holiday_reminder,'plus');
        if(!empty($firstReminderdate)) 
            $first_remidner_date=$firstReminderdate->reminder_date;
        else $first_remidner_date=$start_date;  
        $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->general_end_cycle,$is_service_has_reminder->general_end_cycle_frequency_type);
        $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->general_period,$is_service_has_reminder->general_period_frequency_type);
        $periodOneminusthird=($agePeriodDays-$value3_days);
        $finalDays=($endCycleDyas+$periodOneminusthird); 
        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');                        

        $reminder_id = 0;
        if(!empty($reminder_array) && count($reminder_array) > 0)
        {
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
                $date1 = new DateTime($reminder_array[$i]);
                $date2 = new DateTime($endcycle_date);
                $date_today=new DateTime();
                $reminder_tmp['reminder_status'] = 'Set';
                if($endCycleDyas>0){
                    if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                    else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                }
                $reminder_tmp['status'] = 'activate';  
                //  $reminder_tmp['parent_id'] = $parent_id;
                $reminder_tmp['type'] = 'general';
                $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;

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


                    /*****Remove**general reminder****22-march-24*********/
                    $generalServiceId = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('appointment_id',$appointment_id) 
                                ->where('patient_id',$patient_id)
                                ->where('type','general')
                                ->whereNull('deleted_at')
                                ->where('service_id', $service_id)
                                ->orderBy('id','desc')
                                ->get();

                    if(isset($generalServiceId) && !empty($generalServiceId))
                    {
                        //Get reminder entry for above general service id and delete it for previous appointemnt
                        $previousAppointmentIds = DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('type','general')
                            ->whereNull('deleted_at')
                            ->where('service_id', $service_id)
                            ->where('patient_id',$patient_id)
                            ->where('appointment_id','!=',$appointment_id)
                            //->where('appointment_id','!=',0)
                            ->select('id')
                            ->get();    

                        if(isset($previousAppointmentIds) && !empty($previousAppointmentIds))
                        {
                            $service_id_holder = [];
                            if(!empty($previousAppointmentIds))
                            {
                                foreach($previousAppointmentIds as $id=>$value_id)
                                { 
                                    $service_id_holder[] = $value_id->id;
                                }                        
                            }//if not empty ids         

                            //Log::info('id holder====>');      
                           // Log::info($service_id_holder); 

                            DB::connection('tenant')->table('patient_has_service_reminder')
                                            ->where('type','general')
                                            ->whereNull('deleted_at')
                                            ->where('service_id', $service_id)
                                            ->where('patient_id',$patient_id)
                                            ->where('appointment_id','!=',$appointment_id)
                                            //->where('appointment_id','!=',0)
                                            ->whereNull('deleted_at')
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                        

                            $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                            ->whereIn('service_reminder_id',$service_id_holder)
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);                

                        }//previousAppointmentIds    
                    }//if  generalServiceId                 

                   /*****Remove**general reminder***22-march-24*******/   
   

                }
                // Log::info('temp');
                // Log::info($reminder_id);
            }

            $value5_days = $this->_getDate(current($reminder_array),$is_service_has_reminder->general_new_frequency,$is_service_has_reminder->general_new_frequency_type);

            // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +"   .(int)$value5_days." day"));
            $reactive_reminder = $this->_filterWeekendAndHoiliday(current($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');
            //Log::info(end($reminder_array)."---".$reactive_reminder );
            // dd('sssss');
            $temp = [];
            $temp['patient_id'] =  $patient_id;
            $temp['last_reminder_date'] =  end($reminder_array);
            $temp['next_reminder_date'] =  $reactive_reminder;
            $temp['service_reminder_id'] =  $reminder_id;
            $temp['status'] =  'activate';
            $temp['created_at'] =  date('Y-m-d H:i:s');
            $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
            //Log::info($reactive_reminder);
        }
       
    }

    public function _ageReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        $reminder_array = [];
        // 1st reminder
        $start_date = $start_date;
        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);
        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));
        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->age_first_frequency,$is_service_has_reminder->age_first_frequency_type);

        $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');

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
        //Added by swati 12-May-23===================================
        $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
                                ->first();
        if(!empty($firstReminderdate)) 
            $first_remidner_date=$firstReminderdate->reminder_date;
        else $first_remidner_date=$start_date;
        $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_end_cycle,$is_service_has_reminder->age_end_cycle_frequency_type);  
        $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);
        $periodOneminusthird=($agePeriodDays-$value3_days);
        $finalDays=($endCycleDyas+$periodOneminusthird); 
        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');

        //log::info($service_id);
        //log::info($endcycle_date);

        $reminder_id = 0;
        if(!empty($reminder_array) && count($reminder_array) > 0)
        {
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

                $date1 = new DateTime($reminder_array[$i]);
                $date2 = new DateTime($endcycle_date);
                $date_today=new DateTime();
                $reminder_tmp['reminder_status'] = 'Set';
                if($endCycleDyas>0){
                    if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                    else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                }

                $reminder_tmp['status'] = 'activate';  
                $reminder_tmp['type'] = 'age';
                $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;
                $getAppointmentExamination = DB::connection('tenant')->table('appointment_has_examinations')->where('examination_id',$service_id)->where('appointment_id',$appointment_id)->first();
                //Added by swati 19-Apr-23============
                // if(!empty($getAppointmentExamination))
                //     $reminder_tmp['service_read_status'] = $getAppointmentExamination->create_from;
                // else $reminder_tmp['service_read_status'] = 'App';

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

                    /*****Remove**general reminder*of same service booked***6-march-26********/
                    $ageServiceId = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('appointment_id',$appointment_id) 
                                ->where('patient_id',$patient_id)
                                ->where('type','age')
                                ->whereNull('deleted_at')
                                ->where('service_id', $service_id)
                                ->orderBy('id','desc')
                                ->get();

                    if(isset($ageServiceId) && !empty($ageServiceId))
                    {
                        //Get reminder entry for above general service id and delete it for previous appointemnt
                        $previousAppointmentIds = DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('type','general')
                            ->whereNull('deleted_at')
                            ->where('service_id', $service_id)
                            ->where('patient_id',$patient_id)
                            ->where('appointment_id','!=',$appointment_id)
                            //->where('appointment_id','!=',0)
                            ->select('id')
                            ->get();    

                        if(isset($previousAppointmentIds) && !empty($previousAppointmentIds))
                        {
                            $service_id_holder = [];
                            if(!empty($previousAppointmentIds))
                            {
                                foreach($previousAppointmentIds as $id=>$value_id)
                                { 
                                    $service_id_holder[] = $value_id->id;
                                }                        
                            }//if not empty ids         

                            //Log::info('id holder====>');      
                           // Log::info($service_id_holder); 

                            DB::connection('tenant')->table('patient_has_service_reminder')
                                            ->where('type','general')
                                            ->whereNull('deleted_at')
                                            ->where('service_id', $service_id)
                                            ->where('patient_id',$patient_id)
                                            ->where('appointment_id','!=',$appointment_id)
                                            ->whereNull('deleted_at')
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                        

                            $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                            ->whereIn('service_reminder_id',$service_id_holder)
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);                

                        }//previousAppointmentIds of general reminder  

                        //Get reminder entry for above control service id and delete it for previous appointemnt
                        $previousControlAppointmentIds = DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('type','control')
                            ->whereNull('deleted_at')
                            ->where('service_id', $service_id)
                            ->where('patient_id',$patient_id)
                            ->where('appointment_id','!=',$appointment_id)
                            //->where('appointment_id','!=',0)
                            ->select('id')
                            ->get();    

                        if(isset($previousControlAppointmentIds) && !empty($previousControlAppointmentIds))
                        {
                            $service_id_holders = [];
                            if(!empty($previousControlAppointmentIds))
                            {
                                foreach($previousControlAppointmentIds as $id=>$value_id)
                                { 
                                    $service_id_holders[] = $value_id->id;
                                }                        
                            }//if not empty ids         

                            //Log::info('id holder====>');      
                           // Log::info($service_id_holders); 

                            DB::connection('tenant')->table('patient_has_service_reminder')
                                            ->where('type','control')
                                            ->whereNull('deleted_at')
                                            ->where('service_id', $service_id)
                                            ->where('patient_id',$patient_id)
                                            ->where('appointment_id','!=',$appointment_id)
                                            ->whereNull('deleted_at')
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                        

                            $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                            ->whereIn('service_reminder_id',$service_id_holders)
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);                

                        }//previousControlAppointmentIds of control reminder  


                    }//if  ageServiceId                 

                   /*****Remove**general and control reminder***6-march-26********/ 


                }
            }

            $value5_days = $this->_getDate(current($reminder_array),$is_service_has_reminder->age_new_frequency,$is_service_has_reminder->age_new_frequency_type);
            $reactive_reminder = $this->_filterWeekendAndHoiliday(current($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');
            $temp = [];
            $temp['patient_id'] =  $patient_id;
            $temp['last_reminder_date'] =  end($reminder_array);
            $temp['next_reminder_date'] =  $reactive_reminder;
            $temp['service_reminder_id'] =  $reminder_id;
            $temp['status'] =  'activate';
            $temp['created_at'] =  date('Y-m-d H:i:s');
            $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
        }
       
    }

     public function _ageReminderAppoitment()
    {
        $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'type' => 'service',
                                        'activated_reminder' => 'age',
                                         'is_reminder_updated' => '0'
                                        ]
                                        )->get();
        //dd($is_service_has_reminder);
    

        if(!empty($is_service_has_reminder) && count($is_service_has_reminder) > 0)
        {
            foreach($is_service_has_reminder as $key=>$value)
            {
                $is_service_reminder_checked = DB::connection('tenant')->table('examinations')->where(
                                        [
                                        'id' => $value->service_id,
                                        'show_as_reminder' => '1',
                                         'status' => '1'
                                        ]
                                        )->first();

                //dd($is_service_reminder_checked);
                if(!empty($is_service_reminder_checked))
                {
                    $global_value = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                            [
                                            'type' => 'global',
                                            ]
                                            )->first();

                    $value->holiday_reminder = $global_value->holiday_reminder;
                    $age_from = $value->age_from;
                    $age_to = $value->age_to;
                    
                    // $is_reminder_set = DB::connection('tenant')
                    //         ->table('patient_has_service_reminder')
                    //         ->where('type','age')
                    //         ->where('service_id',$value->service_id)
                    //         ->where('status','activate')
                    //         ->select('patient_id')
                    //          ->whereNull('deleted_at')
                    //         ->get()
                    //         ->toArray();
      
                    // $patinet_ids  = array_unique(array_column(array_values($is_reminder_set), 'patient_id'));

                    // $patinets =DB::connection('tenant')->table('patients')
                    // ->whereNull('deleted_at')    
                    // //->where('id',1498)                
                    // ->whereNotIn('id',$patinet_ids)
                    // ->get();

                    $patinets = DB::connection('tenant')->select("select patients.* from patients where deleted_at is NULL and id NOT IN (select patient_id from patient_has_service_reminder where type='age' and service_id='".$value->service_id."' and status='activate' and deleted_at is NULL)");
                  
                    //dd( $patinet_ids);
                    foreach($patinets as $p_key=>$p_value)
                    {                   
                        // Log::info("here".$p_value->first_name.$p_value->family_name);
                        $from = new DateTime($p_value->birth_date);
                        $to   = new DateTime('today');
                        $age =  $from->diff($to)->y;
                        //dump($age,$value->age_from,$value->age_to);
                         $start_date = '';
                        if($age == $value->age_from || ($age < $value->age_to && $age > $value->age_from))
                        {
                            // $start_date = date('Y-m-d', strtotime($p_value->birth_date. ' + '.($age).' year'));
                            
                            $start_date = date('Y-m-d');
                            if(!empty($value->notify_time))
                            $start_date = $start_date." ".$value->notify_time.":00";
                            else
                             $start_date = $start_date." 09:00:00";
                            $str = "\n".$p_value->id."=".$p_value->first_name." ".$p_value->last_name."=".$age."=".$value->age_from."=".$value->age_to."==".$start_date;
                            //log::info("ReminderStatus==>".$str);
                            //$start_date = date('Y-m-d H:i:s');                            
                        }
                        
                       //dd($start_date);
                        if(!empty($start_date))
                        {
                            $reminder_array = [];
                            /*$start_date = $this->_filterWeekendAndHoiliday($start_date,0,$value->holiday_reminder,'plus');

                            //$start_date = date('Y-m-d h:i:s');
                            //Log::info('start_date is the a'.$start_date);dd('s');

                            $value1_days = $this->_getDate($start_date,$value->age_period_controls,$value->age_period_frequency_type);

                            $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

                            $value3_days = $this->_getDate($period_date,$value->age_first_frequency,$value->age_first_frequency_type);
                           
                            $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$value->holiday_reminder,'minus');
                            $reminder_array[] = $first_reminder;
                            // log::info('sssss');
                            // Log::info(json_encode($reminder_array));
                            // dd('daaa');
                            for($i=0; $i<($value->age_number_of_interval-1); $i++)
                            {
                                $value4_days = $this->_getDate($period_date,$value->age_time_interval,$value->age_time_interval_frequency_type);
                                
                                // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));

                                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$value->holiday_reminder,'plus');
                                if( $third_reminder !=  $first_reminder)
                                {
                                    $reminder_array[] = $third_reminder;
                                }
                            }  */
                            $reminder_array[] = $start_date;     
                            sort($reminder_array);
                            $reminder_id = 0;
                            //dd($reminder_array);
                            if(!empty($reminder_array) && count($reminder_array) > 0)
                            {
                                for($i=0;$i<count($reminder_array);$i++)
                                { 
                                    if($reminder_array[$i] != '0000-00-00 00:00:00')
                                    {
                                        $reminder_tmp = [];
                                        $reminder_tmp['patient_id'] = $p_value->id;
                                        $reminder_tmp['appointment_id'] = 0;
                                        $reminder_tmp['service_id'] = $value->service_id;
                                        $reminder_tmp['reminder_date'] = $reminder_array[$i];
                                        $reminder_tmp['reminder_status'] = 'Set';
                                        $reminder_tmp['status'] = 'activate'; 
                                        $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;
                                       // $reminder_tmp['parent_id'] = $parent_id;
                                        $reminder_tmp['type'] = 'age';

                                        //Added by Shyam 14-01-22
                                        $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                                        ->where('patient_id', $p_value->id)
                                                        ->where('appointment_id', 0)
                                                        ->where('service_id', $value->service_id)
                                                        //->where('reminder_date', $reminder_array[$i])
                                                        ->where('reminder_status', 'Set')
                                                        //->where('status', 'activate')
                                                        ->where('type', 'age')
                                                        ->whereNull('deleted_at')
                                                        ->get();
                                        if(count($is_exists) == 0)
                                        {
                                            $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                                        }
                                    }
                                }
                                /*$value5_days = $this->_getDate(end($reminder_array),$value->age_new_frequency,$value->age_new_frequency_type);


                                // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));
                                $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value5_days,$value->holiday_reminder,'plus');

                                $temp = [];
                                $temp['patient_id'] =  $p_value->id;
                                $temp['last_reminder_date'] =  end($reminder_array);
                                $temp['next_reminder_date'] =  $reactive_reminder;
                                $temp['service_reminder_id'] =  $reminder_id;
                                $temp['status'] =  'activate'; 
                                $temp['created_at'] =  date('Y-m-d H:i:s');
                                $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);*/
                            }
                        }
                                         

                    }
                }
              //  dd('done');
            }
        }       
        
    }

    public function _controlReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        // dump("in control reminder...");

        // dump("is_service_has_reminder");
        // dump($is_service_has_reminder);

        //  dump("start_date");
        // dump($start_date);

        //  dump("service_id");
        // dump($service_id);

        $reminder_array = [];        
        // 1st reminder
        $start_date = $start_date;

        //  dump("start_date");
        // dump($start_date);

        // Log::info(json_encode($is_service_has_reminder)."=".$appointment_id."=".$start_date."=".$patient_id."=".$service_id);

        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->checkup_period_controls,$is_service_has_reminder->checkup_period_frequency_type);

        //   dump("value1_days");
        // dump($value1_days);
       
        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

        //  dump("period_date");
        // dump($period_date);

        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->checkup_first_frequency,$is_service_has_reminder->checkup_first_frequency_type);

        //  dump("value3_days");
        // dump($value3_days);


        // $first_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($period_date)) . " -".(int)$value3_days." day"));
       //dd($start_date,$value1_days,$period_date,$value3_days);
        $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');

        //   dump("first_reminder");
        // dump($first_reminder);

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

        // dump("reminder_array");
        // dump($reminder_array);

        //ddd($reminder_array);
        //Added on 04-Sep-23==========================================
        $firstReminderdate =  DB::table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
                                ->first();
        if(!empty($firstReminderdate)) 
            $first_remidner_date=$firstReminderdate->reminder_date;
        else $first_remidner_date=$start_date;  

        // dump("first_remidner_date");
        // dump($first_remidner_date);


        $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->checkup_end_cycle,$is_service_has_reminder->checkup_end_cycle_frequency_type);

        //  dump("endCycleDyas");
        // dump($endCycleDyas);

        $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->checkup_period_controls,$is_service_has_reminder->checkup_period_frequency_type);

        //  dump("agePeriodDays");
        // dump($agePeriodDays);

        $periodOneminusthird=($agePeriodDays-$value3_days);

        // dump("periodOneminusthird");
        // dump($periodOneminusthird);

        $finalDays=($endCycleDyas+$periodOneminusthird); 

        // dump("finalDays");
        // dump($finalDays);

        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');

        //  dump("endcycle_date");
        // dump($endcycle_date);
        

        $reminder_id = 0;
        if(!empty($reminder_array) && count($reminder_array) > 0)
        {
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
                $date1 = new DateTime($reminder_array[$i]);
                $date2 = new DateTime($endcycle_date);
                $date_today=new DateTime();
                $reminder_tmp['reminder_status'] = 'Set';
                if($endCycleDyas>0){
                    if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                    else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                }

                $reminder_tmp['status'] = 'activate';  
                //  $reminder_tmp['parent_id'] = $parent_id;
                $reminder_tmp['type'] = 'control';
                //dd($reminder_tmp);

                 // dump("reminder_tmp");
                 // dump($reminder_tmp);
        

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

                    //commented on 26-march-25
                   // $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);

                     //added on 26-march-25
                     $serviceExistsAlready = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', $service_id)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'Set')
                                ->where('status', 'activate')
                                ->where('type', 'control')
                                ->whereNull('deleted_at')
                                ->get();
                     if(count($serviceExistsAlready)==0)
                     {
                        $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                     }           


                    /*****Remove**checkup reminder****22-march-24*********/

                    /*$checkupServiceId = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('appointment_id',$appointment_id) 
                                ->where('patient_id',$patient_id)
                                ->where('type','control')
                                ->whereNull('deleted_at')
                                ->where('service_id', $service_id)
                                ->orderBy('id','desc')
                                ->get();

                    if(isset($checkupServiceId) && !empty($checkupServiceId))
                    {
                        //Get reminder entry for above checkup service id and delete it for previous appointments

                        $previousAppointmentIds = DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('type','control')
                            ->whereNull('deleted_at')
                            ->where('service_id', $service_id)
                            ->where('patient_id',$patient_id)
                            ->where('appointment_id','!=',$appointment_id)
                            //->where('appointment_id','!=',0)
                            ->select('id')
                            ->get();    

                        if(isset($previousAppointmentIds) && !empty($previousAppointmentIds))
                        {
                            $service_id_holder = [];
                            if(!empty($previousAppointmentIds))
                            {
                                foreach($previousAppointmentIds as $id=>$value_id)
                                { 
                                    $service_id_holder[] = $value_id->id;
                                }                        
                            }//if not empty ids         

                            Log::info('pk id holder====>');      
                            Log::info($service_id_holder); 

                            DB::connection('tenant')->table('patient_has_service_reminder')
                                            ->where('type','control')
                                            ->whereNull('deleted_at')
                                            ->where('service_id', $service_id)
                                            ->where('patient_id',$patient_id)
                                            ->where('appointment_id','!=',$appointment_id)
                                            //->where('appointment_id','!=',0)
                                            ->whereNull('deleted_at')
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                        

                            $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                            ->whereIn('service_reminder_id',$service_id_holder)
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);                

                        }//previousAppointmentIds    
                    }//if  checkupServiceId       
                    */          

                   /*****Remove**checkup reminder***22-march-24*******/   

                    /*****Remove**general reminder*of same service booked***6-march-26********/
                    $controlServiceId = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('appointment_id',$appointment_id) 
                                ->where('patient_id',$patient_id)
                                ->where('type','control')
                                ->whereNull('deleted_at')
                                ->where('service_id', $service_id)
                                ->orderBy('id','desc')
                                ->get();

                    if(isset($controlServiceId) && !empty($controlServiceId))
                    {
                        //Get reminder entry for above general service id and delete it for previous appointemnt
                        $previousAppointmentIds = DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('type','general')
                            ->whereNull('deleted_at')
                            ->where('service_id', $service_id)
                            ->where('patient_id',$patient_id)
                            ->where('appointment_id','!=',$appointment_id)
                            //->where('appointment_id','!=',0)
                            ->select('id')
                            ->get();    

                        if(isset($previousAppointmentIds) && !empty($previousAppointmentIds))
                        {
                            $service_id_holder = [];
                            if(!empty($previousAppointmentIds))
                            {
                                foreach($previousAppointmentIds as $id=>$value_id)
                                { 
                                    $service_id_holder[] = $value_id->id;
                                }                        
                            }//if not empty ids         

                            //Log::info('id holder====>');      
                           // Log::info($service_id_holder); 

                            DB::connection('tenant')->table('patient_has_service_reminder')
                                            ->where('type','general')
                                            ->whereNull('deleted_at')
                                            ->where('service_id', $service_id)
                                            ->where('patient_id',$patient_id)
                                            ->where('appointment_id','!=',$appointment_id)
                                            //->where('appointment_id','!=',0)
                                            ->whereNull('deleted_at')
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                        

                            $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                            ->whereIn('service_reminder_id',$service_id_holder)
                                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);                

                        }//previousAppointmentIds    
                    }//if  controlServiceId                 

                   /*****Remove**general reminder***6-march-26********/   



                }//if count is exists

            }//for loop
        
        $value5_days = $this->_getDate(end($reminder_array),$is_service_has_reminder->checkup_new_frequency,$is_service_has_reminder->checkup_new_frequency_type);

        // dump("value5_days");
        // dump($value5_days);

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));

        $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

        // dump("reactive_reminder");
        // dump($reactive_reminder);

        $temp = [];
        $temp['patient_id'] =  $patient_id;
        $temp['last_reminder_date'] =  end($reminder_array);
        $temp['next_reminder_date'] =  $reactive_reminder;
        $temp['service_reminder_id'] =  $reminder_id;
        $temp['status'] =  'activate'; 
        $temp['created_at'] =  date('Y-m-d H:i:s');

        // dump("temp");
        // dump($temp);


        $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
        }//if
    }

    public function _reactivateGeneralReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        $reminder_array = [];

        // 1st reminder
        $start_date = $start_date;

        $reminder_array[] = $start_date;

        for($i=0; $i<($is_service_has_reminder->general_number_of_interval-1); $i++)
        {
            $value4_days = $this->_getDate($start_date,$is_service_has_reminder->general_time_interval,$is_service_has_reminder->general_time_interval_frequency_type);
            
            // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));

            $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

            if( $third_reminder !=  $start_date)
            {
                $reminder_array[] = $third_reminder;
            }
        }       
        sort($reminder_array);
        $reminder_id = 0;
        for($i=0;$i<count($reminder_array);$i++)
        { 
            $reminder_tmp = [];
            $reminder_tmp['patient_id'] = $patient_id;
            $reminder_tmp['appointment_id'] = $appointment_id;
            $reminder_tmp['service_id'] = $service_id;
            $reminder_tmp['reminder_date'] = $reminder_array[$i];
            $reminder_tmp['reminder_status'] = 'Set';
            $reminder_tmp['status'] = 'activate';
            $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;  
          //  $reminder_tmp['parent_id'] = $parent_id;
            $reminder_tmp['type'] = 'general';

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
                Log::info("ReminderStatus-_ReactivegeneralReminder-".$patient_id);
                $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
            }
        }

        $value5_days = $this->_getDate(end($reminder_array),$is_service_has_reminder->general_new_frequency,$is_service_has_reminder->general_new_frequency_type);

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));

        $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

        $temp = [];
        $temp['patient_id'] =  $patient_id;
        $temp['last_reminder_date'] =  end($reminder_array);
        $temp['next_reminder_date'] =  $reactive_reminder;
        $temp['service_reminder_id'] =  $reminder_id;
        $temp['status'] =  'activate';
        $temp['created_at'] =  date('Y-m-d H:i:s');
        $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
    }


    public function _reactivateAgeReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$data,$service_id)
    {
        $reminder_array = [];
        if($data['age'] == $is_service_has_reminder->age_from || $data['age'] <= $is_service_has_reminder->age_to )
        {
            $start_date = $start_date;
           
            $reminder_array[] = $start_date;

            for($i=0; $i<($is_service_has_reminder->age_number_of_interval-1); $i++)
            {
                $value4_days = $this->_getDate($start_date,$is_service_has_reminder->age_time_interval,$is_service_has_reminder->age_time_interval_frequency_type);
                
                // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

                if( $third_reminder !=  $start_date)
                {
                    $reminder_array[] = $third_reminder;
                }
            }       
            sort($reminder_array);
        }
        elseif($data['age'] < $is_service_has_reminder->age_from)
        {
            $diff = $is_service_has_reminder->age_from - $data['age'];
            $start_date = date('Y-m-d', strtotime($data['birth_date']. ' + '.$diff.' year'));
            $period_date = $start_date." ".date('H:i:s');
            $reminder_array[] = $period_date;

            for($i=0; $i<($is_service_has_reminder->age_number_of_interval-1); $i++)
            {
                $value4_days = $this->_getDate($period_date,$is_service_has_reminder->age_time_interval,$is_service_has_reminder->age_time_interval_frequency_type);
                
                // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

                  $reminder_array[] = $third_reminder;
                
            }       
            sort($reminder_array);
        }
        $reminder_id = 0;
        for($i=0;$i<count($reminder_array);$i++)
        { 
            $reminder_tmp = [];
            $reminder_tmp['patient_id'] = $patient_id;
            $reminder_tmp['appointment_id'] = $appointment_id;
            $reminder_tmp['service_id'] = $service_id;
            $reminder_tmp['reminder_date'] = $reminder_array[$i];
            $reminder_tmp['reminder_status'] = 'Set';
            $reminder_tmp['status'] = 'activate'; 
            $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ; 
           // $reminder_tmp['parent_id'] = $parent_id;
            $reminder_tmp['type'] = 'age';

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
                //Log::info("ReminderStatus-_ReactiveAgeReminder-".$patient_id);
                $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
            }
        }
        $value5_days = $this->_getDate(end($reminder_array),$is_service_has_reminder->age_new_frequency,$is_service_has_reminder->age_new_frequency_type);

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));
        $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

        $temp = [];
        $temp['patient_id'] =  $patient_id;
        $temp['last_reminder_date'] =  end($reminder_array);
        $temp['next_reminder_date'] =  $reactive_reminder;
        $temp['service_reminder_id'] =  $reminder_id;
        $temp['status'] =  'activate';
        $temp['created_at'] =  date('Y-m-d H:i:s');
        $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
        
    }

    public function _reactivateControlReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        $reminder_array = [];        
        // 1st reminder
        $start_date = $start_date;
        
        $reminder_array[] = $start_date;

        for($i=0; $i<($is_service_has_reminder->checkup_number_of_interval-1); $i++)
        {
            $value4_days = $this->_getDate($start_date,$is_service_has_reminder->checkup_time_interval,$is_service_has_reminder->checkup_time_interval_frequency_type);
            
            // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
            $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

            if( $third_reminder !=  $start_date)
            {
                $reminder_array[] = $third_reminder;
            }
        }       
        sort($reminder_array);
        $reminder_id = 0;
        for($i=0;$i<count($reminder_array);$i++)
        { 
            $reminder_tmp = [];
            $reminder_tmp['patient_id'] = $patient_id;
            $reminder_tmp['appointment_id'] = $appointment_id;
            $reminder_tmp['service_id'] = $service_id;
            $reminder_tmp['reminder_date'] = $reminder_array[$i];
            $reminder_tmp['reminder_status'] = 'Set';
            $reminder_tmp['status'] = 'activate';  
            $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;
          //  $reminder_tmp['parent_id'] = $parent_id;
            $reminder_tmp['type'] = 'control';

            //Added by Shyam 14-01-22
            $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('patient_id', $patient_id)
                            ->where('appointment_id', $appointment_id)
                            ->where('service_id', $service_id)
                            ->where('reminder_date', $reminder_array[$i])
                            ->where('reminder_status', 'Set')
                            ->where('status', 'activate')
                            ->where('type', 'control')
                            ->whereNull('deleted_at')
                            ->get();
            if(count($is_exists) == 0)
            {
               // Log::info("ReminderStatus-_ReactiveControlReminder-".$patient_id);
                $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
            }
        }
        
        $value5_days = $this->_getDate(end($reminder_array),$is_service_has_reminder->checkup_new_frequency,$is_service_has_reminder->checkup_new_frequency_type);

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));
        $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

        $temp = [];
        $temp['patient_id'] =  $patient_id;
        $temp['last_reminder_date'] =  end($reminder_array);
        $temp['next_reminder_date'] =  $reactive_reminder;
        $temp['service_reminder_id'] =  $reminder_id;
        $temp['status'] =  'activate';
        $temp['created_at'] =  date('Y-m-d H:i:s');
        $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
    }

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
        // }
        //Log::info($calculated_date);
        return $calculated_date;
    }




}
