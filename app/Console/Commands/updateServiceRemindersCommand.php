<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// Hyn Tenancy imports (commented out)
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;

use Log;
use DB;
use DateTime;
use App\Models\PatientsModel;
use App\Models\PatientsHasServiceReminderModel;
use Stancl\Tenancy\Facades\Tenancy;

class updateServiceRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     // Hyn Tenancy signature (commented out)
     // protected $signature = 'updatereminder:cron {--website_id=}';
     
     // Stancl Tenancy signature
     protected $signature = 'updatereminder:cron {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update reminder cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
      public function __construct()
    {
        parent::__construct();
        // Hyn Tenancy initialization (commented out)
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */

     public function handle()
    {
        //log::info("updateServiceRemindersCommand handle function start........");
        
        // Hyn Tenancy (commented out)
        // $website_id = $this->option('website_id');
        // log::info($website_id);
        // //dd($website_id);
        // try
        // {
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
       // log::info($tenant_id);

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
    }//handle


  

    public function _commandOperation($tenant_id)
    //public function _commandOperation($website_id)
    {
        //if($tenant_id==2)
        //{
        // log::info("in _commandOperation function...tenant_id id 2.......");
         
         // Stancl Tenancy - Get tenant and initialize context
         $tenant = \App\Models\Tenant::find($tenant_id);
         if($tenant) {
            // Log::info("Found tenant: " . $tenant->ordination_name);
             tenancy()->initialize($tenant);
             config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
             DB::purge('tenant');
            // Log::info("Tenant context initialized for: " . $tenant->ordination_name);
         }

          /*$is_service_has_reminder =DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                        ->select('id','notify_time','service_id')
                        ->where(
                            [                                       
                            'is_reminder_updated' => '1',
                            'type' =>'service',
                            'service_id'=>108                            
                            ]
                        )->get();*/

           $is_service_has_reminder =DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                        ->select('id','notify_time','service_id')
                        ->where(
                            [                                       
                            'is_reminder_updated' => '1',
                            'type' =>'service'
                            ]
                        )->get();
             
           // dd($is_service_has_reminder);     
                    
            if(!empty($is_service_has_reminder))
            {
                foreach($is_service_has_reminder as $key=>$value)
                {
                  //  dump("==service_id===".$value->service_id);
                    $is_service_reminder_checked = DB::connection('tenant')->table('examinations')
                      ->select('id')       // added on 27-sept-23
                      ->where(
                        [
                            'id' => $value->service_id,
                            'show_as_reminder' => '1',
                            // 'status' => '1'
                        ])
                        ->whereNull('deleted_at')
                        ->first();

                   // dump($is_service_reminder_checked);
                      
                    if(!empty($is_service_reminder_checked))
                    {
                        $isUpdateServiceRecordModel =DB::connection('tenant')->table('update_service_record')
                                            ->select('id','max_patient_id') // added on 27-sept-23
                                            ->where('is_reminder_updated',1)
                                            ->where('service_id',$value->service_id)
                                            ->first();

                       // dump($isUpdateServiceRecordModel);
                    

                        if(!empty($isUpdateServiceRecordModel))
                        {                 
                           // log::info("in not empty isUpdateServiceRecordModel function..........");

                            $updateId= $isUpdateServiceRecordModel->id;      

                            $all_patient_ids =  DB::connection('tenant')->table('update_service_reminders') 
                                         ->join('patient_has_service_reminder','patient_has_service_reminder.patient_id','=','update_service_reminders.patient_id') 
                                         ->join('patients','patients.id','=','update_service_reminders.patient_id')
                                          ->whereNull('patient_has_service_reminder.deleted_at')
                                          ->whereNull('update_service_reminders.deleted_at')
                                          ->whereNull('patients.deleted_at')
                                          ->where('update_service_reminders.service_id',$value->service_id)  
                                          ->where('patient_has_service_reminder.service_id',$value->service_id)  //new added 11oct23
                                        // ->whereIn('patient_has_service_reminder.patient_id', [16011,7666,42237,39749,34712,35795,31037,20053,20303,15746,22227,13466,26252,12848,20295,31961,19164,21530,16913])
                                        //->whereIn('patient_has_service_reminder.patient_id', [47311])
                                          ->groupby('patient_has_service_reminder.patient_id','patient_has_service_reminder.appointment_id')

                                          // ->select(['update_service_reminders.service_id',
                                          //     'patient_has_service_reminder.appointment_id','update_service_reminders.patient_id',
                                          //    'patient_has_service_reminder.status'])
                                          // ->toSql();

                                            ->limit(150)
                                             ->get(['update_service_reminders.service_id','patient_has_service_reminder.appointment_id','update_service_reminders.patient_id',
                                               'patient_has_service_reminder.status',
                                               'patient_has_service_reminder.reminder_date']);          
                              // log::info($all_patient_ids);               
                                
                            //dd($all_patient_ids);

                            if(!empty($all_patient_ids))
                            {
                                //log::info("if not empty all_patient_ids ..........");

                                    foreach($all_patient_ids as $p_key=>$p_value)
                                    {
                                        //log::info("in loop of all_patient_ids ..........");

                                        $patinet_data = DB::connection('tenant')->table('patients')
                                                ->where('id',$p_value->patient_id)
                                                  ->whereNull('patients.deleted_at')
                                                  ->first();


                                         // log::info("patient data");

                                                  
                                         //added on 9oct23
                                        $isUpdateServiceRecordExists =DB::connection('tenant')->table('update_service_record')
                                            ->select('id','max_patient_id','service_id') 
                                            ->where('is_reminder_updated',1)
                                            ->where('service_id',$value->service_id)
                                            ->where('id',$isUpdateServiceRecordModel->id)
                                            ->first();   

                                           // dump("isUpdateServiceRecordExists==>");
                                           // dump($isUpdateServiceRecordExists);  

                                            // log::info("isUpdateServiceRecordExists");

                                        if(!empty($isUpdateServiceRecordExists) && !empty($patinet_data))
                                        { 

                                            //log::info("if in both isUpdateServiceRecordExists and patient data present");

                                          //  log::info("appointment_id----");
                                          //  log::info($p_value->appointment_id);

                                                $ids = DB::connection('tenant')->table('patient_has_service_reminder')
                                                ->where('service_id',$p_value->service_id)
                                                ->where('appointment_id',$p_value->appointment_id) 
                                                ->where('patient_id',$p_value->patient_id)
                                                ->select('id')
                                                ->get();

                                                $id_holder = [];
                                                if(!empty($ids))
                                                {
                                                    foreach($ids as $id=>$value_id)
                                                    { 
                                                        $id_holder[] = $value_id->id;
                                                    }                        
                                                }

                                                 // Log::info('in before delete.........');
                                                 // Log::info($p_value->service_id);
                                                 // Log::info($p_value->appointment_id);
                                                 // Log::info($p_value->patient_id);
                                                   
                                                DB::connection('tenant')->table('patient_has_service_reminder')
                                                ->where('service_id',$p_value->service_id)
                                                ->where('appointment_id',$p_value->appointment_id) 
                                                ->where('patient_id',$p_value->patient_id)
                                                ->whereNull('deleted_at')
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                                                $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                                ->whereIn('service_reminder_id',$id_holder)
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                                                $appoitment_data = DB::connection('tenant')->table('appointment')
                                                    ->where('id',$p_value->appointment_id)->first();

                                                if(!empty($patinet_data->birth_date))               
                                                {
                                                    $from = new DateTime($patinet_data->birth_date);
                                                    $to   = new DateTime('today');
                                                    $age =  $from->diff($to)->y;
                                                    $data['age'] = $age;                         
                                                }else
                                                {
                                                    $data['age'] = $patinet_data->age; 
                                                }
                                                $data['birth_date'] = $patinet_data->birth_date." ".$value->notify_time.":00";
                                                if(!empty($appoitment_data->start_date))
                                                {
                                                    $ap_start_date = $appoitment_data->start_date." ".$value->notify_time.":00";
                                                }else
                                                {
                                                    $ap_start_date = '';
                                                }
                                                 
                                                // log::info("appointment_id----");
                                                // log::info($p_value->appointment_id);
                                                // log::info("patient_id----");
                                                // log::info($p_value->patient_id); 

                                                if($p_value->appointment_id!=0){
                                                   // log::info("in if condition--app id is not 0--");
                                                    $this->_checkAndAddServiceReminderU($p_value->patient_id,$p_value->service_id,$p_value->appointment_id,$ap_start_date,$data);
                                                }
                                                else
                                                {
                                                    //log::info("in else condition----");

                                                     //check for patient appointment for this service if its booked then take that as latest appointment added on 27-nov-23
                                       
                                                    //commented below code on 14-dec-23  
                                                   // $this->_checkPatientAgeReminderU($p_value->patient_id,$value->service_id,$p_value->status,$p_value->appointment_id,$p_value->reminder_date);


                                                /*******start*****added on 29-nov-23*(14-dec-23)*for no appointment criteria***********/ 
                                                   $checkHasBookedExamination = DB::connection('tenant')->table('appointment_has_examinations')
                                                   ->where('patient_id',$p_value->patient_id)
                                                   ->where('examination_id',$p_value->service_id) 
                                                   ->select('appointment_id')
                                                   ->first(); 
                                                   //echo "<pre>";print_r($checkHasBookedExamination); 
                                                  // dump($checkHasBookedExamination);

                                                   if(isset($checkHasBookedExamination))
                                                   {
                                                    // log::info("in checkHasBookedExamination----");

                                                     $BookedAppointmentId = $checkHasBookedExamination->appointment_id;

                                                     $bookedAppointmentData = DB::connection('tenant')->table('appointment')
                                                                        ->where('id',$BookedAppointmentId)
                                                                        ->where('appointment_status','Fertig')
                                                                        ->select('start_date','id')
                                                                        ->orderBy('id', 'desc')
                                                                        ->first();
                                                      // dump($bookedAppointmentData);

                                                       if(isset($bookedAppointmentData))
                                                       {    
                                                          //log::info("in bookedAppointmentData----");

                                                         // log::info(" bookedAppointmentData-start date---");

                                                          $ap_start_date = $bookedAppointmentData->start_date;

                                                          //log::info($ap_start_date);

                                                          $a_date = explode(" ",$ap_start_date);
                                                          $ap_start_date = $a_date[0]." ".$value->notify_time.":00";
                                                          
                                                           //log::info($ap_start_date);

                                                           $this->_checkPatientAgeReminderU($p_value->patient_id,$value->service_id,$p_value->status,$p_value->appointment_id,$p_value->reminder_date);

                                                          $this->_checkAndAddAppointmentAgeReminderU($bookedAppointmentData->id,$ap_start_date,$p_value->patient_id,$p_value->service_id);

                                                       }//if isset bookedappointmentData
                                                       else
                                                       {
                                                           $this->_checkPatientAgeReminderU($p_value->patient_id,$value->service_id,$p_value->status,$p_value->appointment_id,$p_value->reminder_date);
                                                       }
                                                   }//if isset checkHasBookedExamination
                                                   else
                                                   {
                                                       $this->_checkPatientAgeReminderU($p_value->patient_id,$value->service_id,$p_value->status,$p_value->appointment_id,$p_value->reminder_date);
                                                   }

                                                /****end**added on 29-nov-23**(14-dec-23)for no appointment criteria*****/ 


                                                }//else

                                               
                                                DB::connection('tenant')->table('update_service_reminders')
                                                    ->where('patient_id',$p_value->patient_id)
                                                    ->where('service_id',$p_value->service_id)
                                                    ->where('update_service_id',$isUpdateServiceRecordModel->id)//added on 9oct23
                                                    ->whereNull('deleted_at')
                                                    ->update(['deleted_at'=>date('Y-m-d H:i:s'),'deleted_through'=>'cron']);

                                                 //Update start and end size in update_service_record
                                                    $updateEntries =  DB::connection('tenant')->table('update_service_record')
                                                    ->where('id',$updateId)
                                                    ->where('is_reminder_updated',1)
                                                    ->update(['end_patient_id'=>$p_value->patient_id]);     


                                        }//if not empty isUpdateServiceRecordExists

                                    }//foreach patient records
                              
                                

                                //Update start and end size in update_service_record
                               /* $updateEntries =  DB::table('update_service_record')
                                ->where('id',$updateId)
                                ->where('is_reminder_updated',1)
                                ->where('service_id',$value->service_id)
                                ->update(['start_patient_id'=>$startSize,'end_patient_id'=>$endSize]); */

                                /******start*****update*is_reminder_updated*to*0*****************/
                               
                                $isUpdateServiceRecordExists = DB::connection('tenant')->table('update_service_record')
                                                ->where('id',$updateId)
                                                ->where('is_reminder_updated',1)
                                                ->where('service_id',$value->service_id)
                                                ->first(); 


                                if(!empty($isUpdateServiceRecordExists))
                                {
                                    //$end_patient_id = $isUpdateServiceRecordExists->end_patient_id;

                                    $checkDeletedCount = DB::connection('tenant')->table('update_service_reminders')
                                                    ->where('update_service_id',$updateId)
                                                     // ->whereNull('deleted_through') // Check if 'deleted_through' is NULL

                                                     ->where(function($query) {
                                                            $query->whereNull('deleted_through')
                                                                  ->orWhere('deleted_through', '=', ''); // Check for empty string
                                                     })
                                                     ->whereNull('deleted_at') // Check if 'deleted_through' is NULL
                                                    ->count();
                                   // log::info("checkDeletedCount====>");                
                                   // log::info($checkDeletedCount);                

                                    // if($end_patient_id == $maxPatientId){
                                    if($checkDeletedCount==0) 
                                    {            

                                         $is_service_has_reminder =DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                                    ->where([                                       
                                                        'id' => $value->id
                                                        ])
                                                    ->update(['is_reminder_updated'=>'0']);

                                        DB::connection('tenant')->table('update_service_record')
                                                ->where('id',$updateId)
                                                ->where('is_reminder_updated',1)
                                                ->where('service_id',$value->service_id)
                                                ->update(['is_reminder_updated'=>0,'updated_by'=>'Cron','inserted_through'=>'Cron']);
                                    }
                                }
                                /*******end****update*is_reminder_updated*to*0*****************/    


                            }//if all_patinet_ids    

                        }//if not empty isUpdateServiceRecordModel

                    }//if
                }//foreach is_service_has_reminder
            }//if not empty is_service_has_reminder
        //}
    }//_commandOperation


    public function _checkAndAddAppointmentAgeReminderU($appointment_id,$start_date,$patient_id,$service_id)
    {
       // dump('in _checkAndAddAppointmentAgeReminderU function.......');

        //log::info('innnnnn _checkAndAddAppointmentAgeReminderU function..........');

        $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_id,
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
        }else
        {
            $default_reminder = $is_service_has_reminder->activated_reminder;
           
        }  

       // dump("default_reminder===> ".$default_reminder);

       // log::info("default_reminder===> ");
       // log::info($default_reminder);

        if($default_reminder == 'age')
        {
               //Added below query on 25-oct-23
                $getPatient = DB::connection('tenant')->table('patients')
                                    ->select('birth_date','age') // added on 29-sept-23 added for db connection tenant
                                    ->where('id',$patient_id)
                                    ->whereNull('deleted_at')
                                    ->first();                   

               // dump($getPatient);
                                    
                $reminder_array = [];
                // 1st reminder
                $start_date = $start_date;
                $value1_days = $this->_getDate($start_date,$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);

                // dump("value1_days ==>");
                // dump($value1_days);

                //  dump("start_date ==>");
                // dump($start_date);

                $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

                // dump("period_date ==>");
                // dump($period_date);

                $value3_days = $this->_getDate($period_date,$is_service_has_reminder->age_first_frequency,$is_service_has_reminder->age_first_frequency_type);

                // dump("value3_days =====>");
                // dump($value3_days);

                // dump("period_date ==>");
                // dump($period_date);

                $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');

                // dump("first_reminder ==>");
                // dump($first_reminder);
                

                $reminder_array[] = $first_reminder;
                for($i=0; $i<($is_service_has_reminder->age_number_of_interval-1); $i++)
                {
                    $value4_days = $this->_getDate($period_date,$is_service_has_reminder->age_time_interval,$is_service_has_reminder->age_time_interval_frequency_type);

                    // dump("value4_days =====>");
                    // dump($value4_days);

                    $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

                    if( $third_reminder !=  $first_reminder)
                    {
                        $reminder_array[] = $third_reminder;
                    }
                }//

                // dump("reminder_array ==>");
                // dump($reminder_array);

                sort($reminder_array);


                // dump("sorted reminder_array ==>");
                // dump($reminder_array);



                //Added by swati 12-May-23===================================
                $firstReminderdate = DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where('patient_id',$patient_id)
                                        ->where('service_id',$service_id)
                                        ->where('appointment_id',$appointment_id)
                                         ->whereNull('deleted_at')
                                        ->first();

                //log::info("firstReminderdate==>");
                //log::info($firstReminderdate);                        

                // if(!empty($firstReminderdate)) 
                //     $first_remidner_date=$firstReminderdate->reminder_date;
                // else $first_remidner_date=$start_date;

                $first_remidner_date=$start_date;

                //cacluate end cycle only get days here 
                $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_end_cycle,$is_service_has_reminder->age_end_cycle_frequency_type);  


                // dump("endCycleDyas ==>");
                // dump($endCycleDyas);

                //log::info("endCycleDyas==>");
                //log::info($endCycleDyas);   

                //get days of first #1
                $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);

                // dump("agePeriodDays ==>");
                // dump($agePeriodDays);

                 //log::info("agePeriodDays==>");
                //log::info($agePeriodDays); 


                //#1-#3
                $periodOneminusthird=($agePeriodDays-$value3_days);


                // dump("periodOneminusthird ==>");
                // dump($periodOneminusthird);

                //log::info("periodOneminusthird==>");
                //log::info($periodOneminusthird); 


                //#1-#3+#6
                $finalDays=($endCycleDyas+$periodOneminusthird); 

                // dump("finalDays ==>");
                // dump($finalDays);

                //log::info("finalDays==>");
                //log::info($finalDays);


                //filter holidays
                $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');

                // dump("endcycle_date ==>");
                // dump($endcycle_date);



                //log::info($service_id);
                //log::info($endcycle_date);

                $reminder_id = 0;
                if(!empty($reminder_array) && count($reminder_array) > 0)
                {

                     //Added below code on 4-oct-23                       
                     $checkFuturRemidner=DB::connection('tenant')->table('patient_has_service_reminder')
                                            ->where('patient_id',$patient_id)
                                            ->where('service_id',$service_id)
                                            ->where('appointment_id','>',$appointment_id)
                                            ->where('appointment_id','!=',0)
                                            ->whereNull('deleted_at')
                                            ->count();                       

                      //log::info('checkFuturRemidner===>');
                      //log::info($checkFuturRemidner);      
                               
                      // dump("checkFuturRemidner ==>");
                      // dump($checkFuturRemidner);

                    // echo $appointment_id."===<pre>";print_r($checkFuturRemidner);
                    for($i=0;$i<count($reminder_array);$i++)
                    { 
                         //log::info('in for loop reminder_array===>');    
                         //dump('in reminder oop array...');


                        $reminder_tmp = [];
                        $reminder_tmp['patient_id'] = $patient_id;
                        $reminder_tmp['appointment_id'] = $appointment_id;
                        $reminder_tmp['service_id'] = $service_id;
                        $reminder_tmp['reminder_date'] = $reminder_array[$i];
                        //Added by swati 12-May-23===================================
                        $date1 = new DateTime($reminder_array[$i]);
                        $date2 = new DateTime($endcycle_date);
                        $date_today=new DateTime();
                        $reminder_tmp['reminder_status'] = 'Set';

                        //echo "<pre>";print_r($reminder_tmp);

                        // log::info('date1======>');
                        // log::info($date1);
                        // log::info('date2======>');
                        // log::info($date2);
                        // log::info('endCycleDays=====>');
                        // log::info($endCycleDyas);


                        // dump("date1 ==>");
                        //  dump($date1);

                        // dump("date2 ==>");
                        //  dump($date2);

                        // dump("endCycleDays ==>");
                        // dump($endCycleDyas);



                        if($endCycleDyas>0){
                            if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                            else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                        }

                        $reminder_tmp['status'] = 'activate';

                 

                        //added below code on 4-oct-23
                        if($checkFuturRemidner>0) $reminder_tmp['status'] = 'deactivate';  


                        //   print_r($reminder_tmp);exit;
                        $reminder_tmp['type'] = 'age';
                        $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;


                        $getAppointmentExamination = DB::connection('tenant')->table('appointment_has_examinations')->where('examination_id',$service_id)->where('appointment_id',$appointment_id)->first();

                        // Start Added below code for optimizing code on 4-oct-23               
                        $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where('patient_id', $patient_id)
                                        ->where('appointment_id', $appointment_id)
                                        ->where('service_id', $service_id)
                                        ->where('reminder_date', $reminder_array[$i])
                                        ->where('reminder_status', 'Set')
                                        ->where('status', 'activate')
                                        ->where('type', 'age')
                                        ->whereNull('deleted_at')
                                        ->count();      

                       // log::info('is_exists==>');
                       // log::info($is_exists);

                        if($is_exists == 0)
                        {
                                 //log::info('if is_exists is 0==>');
                                 //log::info($reminder_tmp);

                                  // dump(" in is_exists is 0= ==>");
                                  // dump($is_exists);

                                 //Added below query on 25-oct-23
                                 if($getPatient->birth_date) 
                                 {
                                    $from = new DateTime($getPatient->birth_date);
                                    $to   = new DateTime('today');
                                    $age =  $from->diff($to)->y;
                                }
                                else 
                                {
                                    $age =  $getPatient->age;
                                }
                               // log::info('age===>');
                               // log::info($age);


                                 // dump(" age is = ==>");
                                 // dump($age);

                                if($age == $is_service_has_reminder->age_from || ($age < $is_service_has_reminder->age_to && $age > $is_service_has_reminder->age_from))
                                {
                                    //log::info('after age in..........===>');
                                    $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                                }
                                //Added above query on 25-oct-23

                        }//if is exists is 0

                        // End Added below code for optimizing code on 4-oct-23      

                    }//for loop

                        

                    $value5_days = $this->_getDate(current($reminder_array),$is_service_has_reminder->age_new_frequency,$is_service_has_reminder->age_new_frequency_type);
                    $reactive_reminder = $this->_filterWeekendAndHoiliday(current($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

                     // dump('value5_days ==>');
                     // dump($value5_days);

                     // dump('reactive_reminder ==>');
                     // dump($reactive_reminder);


                    //if condition added on 25-oct-23
                    if($reminder_id)
                    {
                        $temp = [];
                        $temp['patient_id'] =  $patient_id;
                        $temp['last_reminder_date'] =  end($reminder_array);
                        $temp['next_reminder_date'] =  $reactive_reminder;
                        $temp['service_reminder_id'] =  $reminder_id;
                        $temp['status'] =  'activate';
                        $temp['created_at'] =  date('Y-m-d H:i:s');

                        //echo"<pre>";print_r($temp);
                        $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
                    }//if reminder_id condition
                   
                }  
        }//if default reminder is age

   
    }//_checkAndAddAppointmentAgeReminderU




     public function _commandOperation_testing($website_id)
    {   
          //log::info($website_id);
          if($website_id==2)
          {
            //  log::info("in _commandOperation--function 23-oct-23--");
              $is_service_has_reminder =DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                        ->select('id','notify_time','service_id')
                        ->where(
                            [                                       
                            'is_reminder_updated' => '1',
                            'type' =>'service'
                            ]
                        )->get();
          }
    }//

    //Roshani removed the optional feature for =>(PHP Deprecated:  Optional parameter $service_id declared before required parameter $prevreminderdate is implicitly treated as a required parameter in /opt/app-data/wwwroot/app/Console/Commands/updateServiceRemindersCommand.php on line 782)
    // public function _checkPatientAgeReminderU($patient_id='',$service_id='',$status,$appointment_id,$prevreminderdate)
    public function _checkPatientAgeReminderU($patient_id,$service_id,$status,$appointment_id,$prevreminderdate)
    {
        //dump('innnnnnnnnnnnnnnn _checkPatientAgeReminderU');

        // log::info("in _checkPatientAgeReminderU----");
        
        $totalEntries = 0;
        /*$getPatient = PatientsModel::
                            select('birth_date','age') // added on 27-sept-23 needs to add it
                            ->where('id',$patient_id)
                            ->whereNull('deleted_at')
                            ->first();*/

         $getPatient = DB::connection('tenant')->table('patients')
                            ->select('birth_date','age') // added on 29-sept-23 added for db connection tenant
                            ->where('id',$patient_id)
                            ->whereNull('deleted_at')
                            ->first();                   
       // log::info("in getPatient----");      

        $getAgeServices = DB::connection('tenant')->table('preferred_channels_for_reminders_setting as pcr')
                            ->leftjoin('examinations','examinations.id','pcr.service_id')
                            ->where('examinations.show_as_reminder','1')
                            ->where('pcr.activated_reminder','age')
                            ->where('pcr.service_id',$service_id)
                            ->whereNull('pcr.deleted_at')
                            ->whereNull('examinations.deleted_at')
                            ->get(['examinations.id as service_id', 'pcr.notify_time', 'pcr.age_from', 'pcr.age_to','pcr.age_end_cycle','pcr.age_end_cycle_frequency_type','pcr.holiday_reminder']);
                             //,'pcr.age_end_cycle','pcr.age_end_cycle_frequency_type','pcr.holiday_reminder' //added on 21-nov-23

        //  dump($getAgeServices);
                        
         //log::info("in if getAgeServices----");      
        // log::info($getAgeServices);   

        foreach ($getAgeServices as $ke => $ser)
        {

            // log::info("in getAgeServices-loop---");    

            /*$checkRecord = PatientsHasServiceReminderModel::
                            where('patient_id', $patient_id)
                            ->where('service_id', $service_id)
                            ->where('reminder_status', 'Set')
                            ->where('status', 'activate')
                            ->where('type', 'age')
                            ->get(['id']); */

            //Added on 29-sept-23 for db connection with tenant
                            
           /* $checkRecord = DB::table('patient_has_service_reminder')
                            ->where('patient_id', $patient_id)
                            ->where('service_id', $service_id)
                            ->where('reminder_status', 'Set')
                            ->where('status', 'activate')
                            ->where('type', 'age')
                            ->get(['id']); */

                // log::info("in before checkRecord---");  
                // log::info("in  patient_id---");    
                // log::info($patient_id);                 

                // log::info("in service_id---");    
                // log::info($service_id); 

             $checkRecord = DB::connection('tenant')->table('patient_has_service_reminder')
                            //->select('id')
                            ->where('patient_id', $patient_id)
                            ->where('service_id', $service_id)
                             ->where('appointment_id', $appointment_id) // added on 20-oct-23
                            ->where('reminder_status', 'Set')
                           //->where('status', 'activate') //commented on 11-oct-23
                            ->where('type', 'age')
                             ->whereNull('deleted_at')  //added on 12-oct-23
                            //->count();
                            // ->get(['id']);     
                            ->get();                           

            // dump('checkRecord===>'.$checkRecord);

               //  log::info("in checkRecord---");    
                 
              //  log::info($checkRecord);                 
             
                            
             if(sizeof($checkRecord) == 0)
          //  if($checkRecord == 0)                 
            {
                 // log::info("if checkRecord is 0---");   
                  
                if($getPatient->birth_date) {
                    $from = new DateTime($getPatient->birth_date);
                    $to   = new DateTime('today');
                    $age =  $from->diff($to)->y;
                }
                else {
                    $age =  $getPatient->age;
                }
                if($age == $ser->age_from || ($age < $ser->age_to && $age > $ser->age_from))
                {
                     // log::info("in if age condtion part.......");  

                    //commented on 29-sept-23 for tenant connection
                   /* $PatientsHasServiceReminder = new PatientsHasServiceReminderModel;
                    $PatientsHasServiceReminder->patient_id      = $patient_id;
                    $PatientsHasServiceReminder->appointment_id  = 0;
                    $PatientsHasServiceReminder->service_id      = $service_id;
                    $PatientsHasServiceReminder->parent_id       = 0;
                    $PatientsHasServiceReminder->reminder_date   = date('Y-m-d ').$ser->notify_time.':00';
                    $PatientsHasServiceReminder->reminder_status = 'Set';
                    $PatientsHasServiceReminder->type            = 'age';
                    $PatientsHasServiceReminder->status          = $status;//'activate';
                    $PatientsHasServiceReminder->created_at      = date('Y-m-d H:i:s');
                    $PatientsHasServiceReminder->save();*/



                    /**********calculation for the status***21-nov-23******/ 
                        $status = $status;
                        $reminder_status ='Set';

                        if(isset($prevreminderdate))
                        {


                           //added below code on 8-jan-23 for 1970 temporary commented
                           /*$pDate = new DateTime($prevreminderdate);
                           if($pDate->format('Y')<2015)
                           {
                             $prevreminderdate = date('Y-m-d ').$ser->notify_time.':00';
                           }
                           else
                           {
                              $prevreminderdate = $prevreminderdate;   
                           }//else     
                           */    


                            $endCycleDyas = $this->_getDate(($prevreminderdate),$ser->age_end_cycle,$ser->age_end_cycle_frequency_type);  
                            $endcycle_date = $this->_filterWeekendAndHoiliday(($prevreminderdate),$endCycleDyas,$ser->holiday_reminder,'plus');

                              // log::info(' in app id 0 endCycleDyas===>');
                              // log::info($endCycleDyas);

                              // log::info(' in app id 0 endcycle_date===>');
                              // log::info($endcycle_date);

                            $reminderDate = new DateTime($prevreminderdate);
                            $endDate = new DateTime($endcycle_date);
                            $date_today=new DateTime();

                              //log::info(' in app id 0 reminderDate===>');
                             // log::info($reminderDate);

                              //log::info(' in app id 0 endDate===>');
                             // log::info($endDate);

                            if($endCycleDyas>0)
                            {

                               // log::info(' if endCycleDyas > 0===>');                                 
                                //if($reminderDate >= $endDate ) $reminder_status='ignore';
                                 //if parameter 6 has not passed yet

                                if($endDate>$date_today){

                                 //  log::info("endDate is greater than todays date");
                                    $reminder_status='Set'; 
                                    $status = 'activate';
                                  
                                }//if 
                                else
                                {
                                    //log::info("endDate is less than todays date");
                                    //if paramter 6 has passed (end date is passed yet)
                                     $reminder_status='ignore'; 
                                     $status = 'activate';
                                } 

                                // log::info(' in app id 0 if condition reminder_status===>');
                                // log::info($reminder_status);

                                 //log::info(' in app id 0 if condition status===>');
                                 //log::info($status);

                            }//if endCycleDyas > 0
                        }//if isset prevreminderdate
                    /**********calculation for the status***21-nov-23******/ 

                     // log::info("after status calculation conditions...reminder status is....");
                     // log::info($reminder_status);

                     // log::info("after status calculation conditions...status is....");
                     // log::info($status);

                    //Added on 29-sept-23
                    $patientsHasServiceReminderArr = [];
                    $patientsHasServiceReminderArr['patient_id'] = $patient_id;
                    $patientsHasServiceReminderArr['appointment_id'] = 0;
                    $patientsHasServiceReminderArr['service_id'] = $service_id;
                    $patientsHasServiceReminderArr['parent_id'] = 0;

                    if(isset($prevreminderdate))
                    {

                      //added below code on 8-jan-23 for 1970 commented temporary
                      /* $pDate = new DateTime($prevreminderdate);
                       if($pDate->format('Y')<2015)
                       {
                         $patientsHasServiceReminderArr['reminder_date'] = date('Y-m-d ').$ser->notify_time.':00';
                       }
                       else
                       {
                          $patientsHasServiceReminderArr['reminder_date'] = $prevreminderdate;   
                       }//else    
                       */     


                         //start added below code on 4-apr-25
                        $checkFuturRemidner=DB::connection('tenant')->table('patient_has_service_reminder')
                                    ->where('patient_id',$patient_id)
                                    ->where('service_id',$service_id)
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('deleted_at')
                                    ->count();                       
                      
                        if($checkFuturRemidner>0){
                             $status = 'deactivate';
                        } 
                        //end added below code on 4-apr-25


                       $patientsHasServiceReminderArr['reminder_date'] = $prevreminderdate;   
                    }
                    else
                    {
                     $patientsHasServiceReminderArr['reminder_date'] = date('Y-m-d ').$ser->notify_time.':00';
                     $status = 'activate';
                    }
                    // $patientsHasServiceReminderArr['reminder_status'] = 'Set';
                    $patientsHasServiceReminderArr['reminder_status'] = $reminder_status; //changed on 21-nov-23
                    $patientsHasServiceReminderArr['type'] = 'age';
                    $patientsHasServiceReminderArr['status'] = $status;//'activate';
                    $patientsHasServiceReminderArr['created_at'] =  date('Y-m-d H:i:s');

                    //log::info($patientsHasServiceReminderArr);


                    $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($patientsHasServiceReminderArr);
                   // dump('after insert record.......');

                    // log::info("after insert record.......");    


                    $totalEntries++;



                }//if age 
            }
        }
        return $totalEntries;
    }
    public function _checkAndAddServiceReminderU($patient_id,$service_id,$appointment_id,$appointment_start_date,$data)
    {
      // dump('innnnnnnnnnnnnnnn _checkAndAddServiceReminderU');
       // log::info("in _checkAndAddServiceReminderU----");

        if($service_id!="" && $service_id > 0)
        {
            // foreach ($all_services as $service_key => $service_value) 
            // {
                $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_id,
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
                   
                }  

                   /*************added below code on 25-jan-24*****************/
                    $is_doctor_set_reminder = db::connection('tenant')->table('patient_has_service_control_reminder_setting')->where(
                        ['patient_id' => $patient_id,
                        'appointment_id' => $appointment_id,
                        'service_id' => $service_id,
                        'status' => '1',
                        ]
                        )->first();

                    // dump('is_doctor_set_reminder');
                   // dump($is_doctor_set_reminder);

                    if(isset($is_doctor_set_reminder))
                    {
                        //dump(' in is_doctor_set_reminder');
                        $is_service_has_reminder->checkup_period_controls =  $is_doctor_set_reminder->control_interval;
                        $is_service_has_reminder->checkup_period_frequency_type =  $is_doctor_set_reminder->control_frequency;
                    } 

                 /*********added above code on 25-jan-24*****************************/


               
                /*Check if that service is general and it is set reminder for 
                 another service added by swati 19-Sep-22*/
                $check_general_recommanded_remidner = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_id,
                                        'activated_reminder' => 'general'
                                        ]
                                        )->first(['recommanded_service_id']);
                if(!empty($check_general_recommanded_remidner) && $check_general_recommanded_remidner->recommanded_service_id)
                      $service_id = $check_general_recommanded_remidner->recommanded_service_id;
                else  $service_id = $service_id;

                // Log::info('Default setting');
                // Log::info($default_reminder);
                // Log::info($patient_id);

                /*END Check if that service is general and it is set reminder for another service*/

                if($default_reminder == 'general')
                {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                   // log::info("ReminderStatus-update_checkAndAddServiceReminder");
                   // Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_updategeneralReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                }          
                else if($default_reminder == 'age')
                {
                    //log::info("ig default reminder is age----");

                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    //log::info("ReminderStatus-updateAGE-_checkAndAddServiceReminder");
                    //Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_updateageReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                } 
                else if($default_reminder == 'checkup')
                {
                    // Start Added code on 15-march-24 for if only doctor set the control reminder

                    if(isset($is_doctor_set_reminder) && !empty($is_doctor_set_reminder))
                    {
                        $a_date = explode(" ",$appointment_start_date);
                        $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                        //log::info("ReminderStatus-updateCHECKUP-_checkAndAddServiceReminder");
                       // Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                        // Log::info($is_service_has_reminder);
                        $this->_updatecontrolReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);

                    }//if isset is_doctor_set_reminder

                    // End Added code on 15-march-24 for if only doctor set the control reminder    
                }                   
            // }
        }  
    }//

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
    }

    public function _updategeneralReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
     //   dump('innnnnn _updategeneralReminder');
        // Log::info(" in _updategeneralReminder----");

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
                                 ->whereNull('deleted_at')
                                ->first();
        if(!empty($firstReminderdate)) 
            $first_remidner_date=$firstReminderdate->reminder_date;
        else $first_remidner_date=$start_date;  

        // Log::info("patient_id----".$patient_id);
        // Log::info("appointment_id----".$appointment_id);

        // Log::info("first_remidner_date----".$first_remidner_date);

        $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->general_end_cycle,$is_service_has_reminder->general_end_cycle_frequency_type);


        // Log::info("endCycleDyas----".$endCycleDyas);


        $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->general_period,$is_service_has_reminder->general_period_frequency_type);

        //Log::info("agePeriodDays----".$agePeriodDays);


        $periodOneminusthird=($agePeriodDays-$value3_days);

        //Log::info("periodOneminusthird----".$periodOneminusthird);

        $finalDays=($endCycleDyas+$periodOneminusthird); 

        //Log::info("finalDays----".$finalDays);


        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');

        //Log::info("endcycle_date----".$endcycle_date);


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

                //Added by Shyam 14-01-22 commented on 4-oct-23
               /* $is_exists = DB::table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', $service_id)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'Set')
                                ->where('status', 'activate')
                                ->where('type', 'general')
                                ->whereNull('deleted_at')
                                ->get();*/

               /* if(count($is_exists) == 0)
                {
                    $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                }*/
                                

                // Start Added below query on 4-oct-23 for optimize code                
                 $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', $service_id)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'Set')
                                ->where('status', 'activate')
                                ->where('type', 'general')
                                ->whereNull('deleted_at')
                                ->count();     

              //  dump('is_exists===>'.$is_exists);        
                                  
                if($is_exists == 0)
                {
                    $reminder_id =DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                }
                // End Added below query on 4-oct-23 for optimize code       


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

    public function _updateageReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
      //  dump('innnnnn _updateageReminder');

        // log::info(" in _updateageReminder");

        // log::info(" appointment_id is ===>");
        // log::info($appointment_id);

        // log::info(" start_date is ===>");
        // log::info($start_date);

        //Added below query on 25-oct-23
        $getPatient = DB::connection('tenant')->table('patients')
                            ->select('birth_date','age') // added on 29-sept-23 added for db connection tenant
                            ->where('id',$patient_id)
                            ->whereNull('deleted_at')
                            ->first();       

        $reminder_array = [];
        // 1st reminder
        $start_date = $start_date;
        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);

        // log::info(" value1_days is ===>");
        // log::info($value1_days);


        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

        // log::info(" period_date is ===>");
        // log::info($period_date);


        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->age_first_frequency,$is_service_has_reminder->age_first_frequency_type);

        $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');

        //log::info(" first_reminder is ===>");
        //log::info($first_reminder);

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
        $firstReminderdate = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
                                 ->whereNull('deleted_at')
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

            //commented below code on 4-oct-23
           /* $checkFuturRemidner=DB::table('patient_has_service_reminder')
                                    ->where('patient_id',$patient_id)
                                    ->where('service_id',$service_id)
                                    ->where('appointment_id','>',$appointment_id)
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('deleted_at')
                                    ->get();*/

             //Added below code on 4-oct-23                       
             $checkFuturRemidner=DB::connection('tenant')->table('patient_has_service_reminder')
                                    ->where('patient_id',$patient_id)
                                    ->where('service_id',$service_id)
                                    ->where('appointment_id','>',$appointment_id)
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('deleted_at')
                                    ->count();                       

            //  dump('checkFuturRemidner===>'.$checkFuturRemidner);        
                       


            // echo $appointment_id."===<pre>";print_r($checkFuturRemidner);
            for($i=0;$i<count($reminder_array);$i++)
            { 
                $reminder_tmp = [];
                $reminder_tmp['patient_id'] = $patient_id;
                $reminder_tmp['appointment_id'] = $appointment_id;
                $reminder_tmp['service_id'] = $service_id;
                $reminder_tmp['reminder_date'] = $reminder_array[$i];
                //Added by swati 12-May-23===================================
                $date1 = new DateTime($reminder_array[$i]);
                $date2 = new DateTime($endcycle_date);
                $date_today=new DateTime();
                $reminder_tmp['reminder_status'] = 'Set';
                if($endCycleDyas>0){
                    if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                    else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                }

                $reminder_tmp['status'] = 'activate';

                //commented below code on 4-oct-23
                // if(!empty($checkFuturRemidner) && count($checkFuturRemidner)>0) $reminder_tmp['status'] = 'deactivate';  

                //added below code on 4-oct-23
                if($checkFuturRemidner>0) $reminder_tmp['status'] = 'deactivate';  


                //   print_r($reminder_tmp);exit;
                $reminder_tmp['type'] = 'age';
                $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;
                $getAppointmentExamination = DB::connection('tenant')->table('appointment_has_examinations')->where('examination_id',$service_id)->where('appointment_id',$appointment_id)->first();
                //Added by swati 19-Apr-23============
                // if(!empty($getAppointmentExamination))
                //     $reminder_tmp['service_read_status'] = $getAppointmentExamination->create_from;
                // else $reminder_tmp['service_read_status'] = 'App';

                //Added by Shyam 14-01-22 commented on 4-oct-23
               /* $is_exists = DB::table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', $service_id)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'Set')
                                ->where('status', 'activate')
                                ->where('type', 'age')
                                ->whereNull('deleted_at')
                                ->get();*/

                /* if(count($is_exists) == 0)
                {
                    $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                }*/               


                // Start Added below code for optimizing code on 4-oct-23               
                $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', $service_id)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'Set')
                                ->where('status', 'activate')
                                ->where('type', 'age')
                                ->whereNull('deleted_at')
                                ->count();      

               // dump('is_exists==>'.$is_exists);

                if($is_exists == 0)
                {
                    //log::info("if is_exists is 0----");
                    // $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);

                          //Added below query on 25-oct-23
                            if($getPatient->birth_date) 
                            {
                                $from = new DateTime($getPatient->birth_date);
                                $to   = new DateTime('today');
                                $age =  $from->diff($to)->y;
                            }
                            else 
                            {
                                $age =  $getPatient->age;
                            }
                           // dump($age);

                            // log::info("patient age in is_exists is 0----");
                            // log::info($age);

                            if($age == $is_service_has_reminder->age_from || ($age < $is_service_has_reminder->age_to && $age > $is_service_has_reminder->age_from))
                            {
                              //log::info("in age condition----");
                               $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                            }
                         //Added above query on 25-oct-23
                }//if

                // End Added below code for optimizing code on 4-oct-23      

            }

            $value5_days = $this->_getDate(current($reminder_array),$is_service_has_reminder->age_new_frequency,$is_service_has_reminder->age_new_frequency_type);
            $reactive_reminder = $this->_filterWeekendAndHoiliday(current($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

            //if condition added on 25-oct-23
            if($reminder_id)
            {
                $temp = [];
                $temp['patient_id'] =  $patient_id;
                $temp['last_reminder_date'] =  end($reminder_array);
                $temp['next_reminder_date'] =  $reactive_reminder;
                $temp['service_reminder_id'] =  $reminder_id;
                $temp['status'] =  'activate';
                $temp['created_at'] =  date('Y-m-d H:i:s');
                $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
            }//if condition added on 25-oct-23
        }
       
    }

    public function _updatecontrolReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
      //  dump('innnnnn _updatecontrolReminder');

        $reminder_array = [];        
        // 1st reminder
        $start_date = $start_date;
        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->checkup_period_controls,$is_service_has_reminder->checkup_period_frequency_type);
        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));
        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->checkup_first_frequency,$is_service_has_reminder->checkup_first_frequency_type);
        $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');
        $reminder_array[] = $first_reminder;
        for($i=0; $i<($is_service_has_reminder->checkup_number_of_interval-1); $i++)
        {
            $value4_days = $this->_getDate($period_date,$is_service_has_reminder->checkup_time_interval,$is_service_has_reminder->checkup_time_interval_frequency_type);   
            $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');
            if( $third_reminder !=  $first_reminder)
            {
                $reminder_array[] = $third_reminder;
            }
        }       
        sort($reminder_array);

        //Added on 04-Sep-23==========================================
        $firstReminderdate =  DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
                                 ->whereNull('deleted_at')
                                ->first();
        if(!empty($firstReminderdate)) 
            $first_remidner_date=$firstReminderdate->reminder_date;
        else $first_remidner_date=$start_date;  
        $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->checkup_end_cycle,$is_service_has_reminder->checkup_end_cycle_frequency_type);
        $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->checkup_period_controls,$is_service_has_reminder->checkup_period_frequency_type);
        $periodOneminusthird=($agePeriodDays-$value3_days);
        $finalDays=($endCycleDyas+$periodOneminusthird); 
        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');

        $reminder_id = 0;
        if(!empty($reminder_array) && count($reminder_array) > 0)
        {   
            //commented below code on 4-oct-23
           /* $checkFuturRemidner=DB::table('patient_has_service_reminder')
                                    ->where('patient_id',$patient_id)
                                    ->where('service_id',$service_id)
                                    ->where('appointment_id','>',$appointment_id)
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('deleted_at')
                                    ->get();*/

            //Added below code on 4-oct-23 for optmizing code                      
            $checkFuturRemidner=DB::connection('tenant')->table('patient_has_service_reminder')
                                    ->where('patient_id',$patient_id)
                                    ->where('service_id',$service_id)
                                    ->where('appointment_id','>',$appointment_id)
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('deleted_at')
                                    ->count();                       

            //   dump('checkFuturRemidner==>'.$checkFuturRemidner);                      

            for($i=0;$i<count($reminder_array);$i++)
            { 
                $reminder_tmp = [];
                $reminder_tmp['patient_id'] = $patient_id;
                $reminder_tmp['appointment_id'] = $appointment_id;
                $reminder_tmp['service_id'] = $service_id;
                $reminder_tmp['reminder_date'] = $reminder_array[$i];
                //Added on 04-Sep-23===================================
                $date1 = new DateTime($reminder_array[$i]);
                $date2 = new DateTime($endcycle_date);
                $date_today=new DateTime();
                // if($date1>=$date2) $reminder_tmp['reminder_status']='ignore';
                // else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                // else $reminder_tmp['reminder_status'] = 'Set';
                $reminder_tmp['reminder_status'] = 'Set';
                if($endCycleDyas>0){
                    if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                    else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                }
                $reminder_tmp['status'] = 'activate';

                //commented below code on 4-oct-23
                // if(!empty($checkFuturRemidner) && count($checkFuturRemidner)>0) $reminder_tmp['status'] = 'deactivate';  

                //Added below code on 4-oct-23
                if($checkFuturRemidner>0) $reminder_tmp['status'] = 'deactivate';  

                $reminder_tmp['status'] = 'activate';  
                $reminder_tmp['type'] = 'control';
                $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;

                //Added by Shyam 14-01-22  commented on 4-oct-23
                /*$is_exists = DB::table('patient_has_service_reminder')
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
                    Log::info("ReminderStatus-_upatecontrolReminder-".$patient_id);
                    $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                }*/


                //Added below code on 4-oct-23 for optmizing 
                 $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', 0)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'executed')
                                ->where('status', 'activate')
                                ->where('type', 'control')
                                ->whereNull('deleted_at')
                                ->count();

              //  dump('is_exists ====>'.$is_exists);
                                
                if($is_exists == 0)
                {
                   // Log::info("ReminderStatus-_upatecontrolReminder-".$patient_id);
                    $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                }
                //Added below code on 4-oct-23 for optmizing 


            }
        
            $value5_days = $this->_getDate(end($reminder_array),$is_service_has_reminder->checkup_new_frequency,$is_service_has_reminder->checkup_new_frequency_type);

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
    }//



    public function _commandOperation_live_renamedon_20_oct_23($website_id)
    {

      // dd('innnnn _commandOperation');

        // $patient_id=base64_decode(base64_decode($patientID));
        $is_service_has_reminder =DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                        ->select('id','notify_time','service_id')
                        ->where(
                            [                                       
                            'is_reminder_updated' => '1',
                            'type' =>'service'
                            ]
                        )->get();

           // dump($is_service_has_reminder);

           // log::info($is_service_has_reminder);            
                        
            if(!empty($is_service_has_reminder))
            {
                foreach($is_service_has_reminder as $key=>$value)
                {
                  //  dump("==service_id===".$value->service_id);
                    $is_service_reminder_checked = DB::connection('tenant')->table('examinations')
                      ->select('id')       // added on 27-sept-23
                      ->where(
                        [
                            'id' => $value->service_id,
                            'show_as_reminder' => '1',
                            // 'status' => '1'
                        ])
                        ->whereNull('deleted_at')
                        ->first();

                   // dump($is_service_reminder_checked);

                   // log::info($is_service_reminder_checked);            

                      
                    if(!empty($is_service_reminder_checked))
                    {
                        $isUpdateServiceRecordModel =DB::connection('tenant')->table('update_service_record')
                                            ->select('id','max_patient_id') // added on 27-sept-23
                                            ->where('is_reminder_updated',1)
                                            ->where('service_id',$value->service_id)
                                            ->first();

                       // dump($isUpdateServiceRecordModel);
                         // log::info($isUpdateServiceRecordModel);            


                        if(!empty($isUpdateServiceRecordModel))
                        {                 
                          $chunkSize = 1000;
                          $startSize = 1;
                          $updateId= $isUpdateServiceRecordModel->id;
                          $maxPatientId = $isUpdateServiceRecordModel->max_patient_id; 

                          // dump("==startSize====".$startSize);
                          // dump("==chunkSize===".$chunkSize);
                          // dump("===maxPatientId=======".$maxPatientId);

                           while ($startSize <= $maxPatientId)
                           {
                                   // Calculate the end size for the current chunk
                                   $endSize = $startSize + $chunkSize - 1;
                                   if($endSize>$maxPatientId)
                                   {
                                     $endSize = $maxPatientId;
                                   }
                                //  dump("==startSize====".$startSize);  
                                 // dump("===endSize=======".$endSize);

                                    // log::info("startSize===>");          
                                    // log::info($startSize);            
                                    // log::info("endSize===>");          
                                    // log::info($endSize);  


                                  /* $all_patient_ids_old = DB::table('patient_has_service_reminder')
                                                ->leftjoin('patients','patients.id','=','patient_has_service_reminder.patient_id')
                                                ->where('service_id',$value->service_id)
                                                ->whereNull('patients.deleted_at')
                                                ->whereNull('patient_has_service_reminder.deleted_at')
                                               // ->where('patient_id',$patient_id) //commented
                                                ->whereBetween('patient_has_service_reminder.patient_id', [$startSize, $endSize])
                                                ->groupby('patient_id','appointment_id')
                                                // ->get(['patient_has_service_reminder.service_id','patient_has_service_reminder.appointment_id','patient_has_service_reminder.patient_id','patient_has_service_reminder.status']);
                                                // ->get(['patient_has_service_reminder.*']);
                                                ->select(['patient_has_service_reminder.service_id','patient_has_service_reminder.appointment_id','patient_has_service_reminder.patient_id','patient_has_service_reminder.status'])
                                                ->toSql();
                                                */
                                               
                                     // dd($all_patient_ids_old);            

                                    $all_patient_ids =  DB::connection('tenant')->table('update_service_reminders') 
                                                 ->join('patient_has_service_reminder','patient_has_service_reminder.patient_id','=','update_service_reminders.patient_id') 
                                                 ->whereNull('update_service_reminders.deleted_at')
                                                 ->whereNull('patient_has_service_reminder.deleted_at')
                                                 ->where('update_service_reminders.service_id',$value->service_id)  
                                                  ->where('patient_has_service_reminder.service_id',$value->service_id)  //new added 11oct23
                                                  
                                                  // ->whereBetween('patient_has_service_reminder.patient_id', [$startSize, $endSize]) 

                                                 // ->whereIn('patient_has_service_reminder.patient_id', [16011,7666,42237,39749,34712,35795,31037,20053,20303,15746,22227,13466,26252,12848,20295,31961,19164,21530,16913])
                                                
                                                 ->whereIn('patient_has_service_reminder.patient_id', [45263])

                                                 //->whereIn('patient_has_service_reminder.patient_id', [16913])
                                                  ->groupby('patient_has_service_reminder.patient_id','patient_has_service_reminder.appointment_id')
                                                 //  ->select(['update_service_reminders.service_id',
                                                 //     'patient_has_service_reminder.appointment_id','update_service_reminders.patient_id',
                                                 //    'patient_has_service_reminder.status'])
                                                 // ->toSql();
                                                  ->get(['update_service_reminders.service_id','patient_has_service_reminder.appointment_id','update_service_reminders.patient_id',
                                                    'patient_has_service_reminder.status']);          

                                        
                                    //dd($all_patient_ids);

                                    if(!empty($all_patient_ids))
                                    {
                                         //  dump('in all_patient_ids........');

                                        // log::info(" not empty all_patient_ids===>");     
                                      
                                        foreach($all_patient_ids as $p_key=>$p_value)
                                        {
                                            $patinet_data = DB::connection('tenant')->table('patients')
                                                    ->where('id',$p_value->patient_id)
                                                      ->whereNull('patients.deleted_at')
                                                      ->first();

                                           //  dump('in patient data........'.$p_key.'...'.$endSize);
                                             //Added below qry and condtion on 9-oct-23
                                            $isUpdateServiceRecordExists =DB::connection('tenant')->table('update_service_record')
                                                    ->select('id','max_patient_id','service_id') 
                                                    ->where('is_reminder_updated',1)
                                                    ->where('service_id',$value->service_id)
                                                    ->where('id',$isUpdateServiceRecordModel->id)
                                                    ->first();   

                                                // Log::info("isUpdateServiceRecordExists==>");
                                                // Log::info($isUpdateServiceRecordExists);    
                                            if(!empty($isUpdateServiceRecordExists))
                                            {  

                                                if(!empty($patinet_data))
                                                {

                                                    //log::info("appointment_id----");
                                                    //log::info($p_value->appointment_id);

                                                      $ids = DB::connection('tenant')->table('patient_has_service_reminder')
                                                    ->where('service_id',$p_value->service_id)
                                                    ->where('appointment_id',$p_value->appointment_id) 
                                                    ->where('patient_id',$p_value->patient_id)
                                                    ->select('id')
                                                    ->get();

                                                    $id_holder = [];
                                                    if(!empty($ids))
                                                    {
                                                        foreach($ids as $id=>$value_id)
                                                        { 
                                                            $id_holder[] = $value_id->id;
                                                        }                        
                                                    }

                                                     // Log::info('in before delete.........');
                                                     // Log::info($p_value->service_id);
                                                     // Log::info($p_value->appointment_id);
                                                     // Log::info($p_value->patient_id);

                                                  //  Log::info(" before delete==>");
                                                       
                                                   DB::connection('tenant')->table('patient_has_service_reminder')
                                                    ->where('service_id',$p_value->service_id)
                                                    ->where('appointment_id',$p_value->appointment_id) 
                                                    ->where('patient_id',$p_value->patient_id)
                                                    ->whereNull('deleted_at')
                                                    ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                                                    $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                                    ->whereIn('service_reminder_id',$id_holder)
                                                    ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                                                    $appoitment_data = DB::connection('tenant')->table('appointment')
                                                        ->where('id',$p_value->appointment_id)->first();

                                                    if(!empty($patinet_data->birth_date))               
                                                    {
                                                        $from = new DateTime($patinet_data->birth_date);
                                                        $to   = new DateTime('today');
                                                        $age =  $from->diff($to)->y;
                                                        $data['age'] = $age;                         
                                                    }else
                                                    {
                                                            $data['age'] = $patinet_data->age; 
                                                    }
                                                    $data['birth_date'] = $patinet_data->birth_date." ".$value->notify_time.":00";
                                                    if(!empty($appoitment_data->start_date))
                                                    {
                                                        $ap_start_date = $appoitment_data->start_date." ".$value->notify_time.":00";
                                                    }else
                                                    {
                                                        $ap_start_date = '';
                                                    }

                                                    //  log::info("appointment_id----");
                                                    // log::info($p_value->appointment_id);
                                                    //  log::info("patient_id----");
                                                    // log::info($p_value->patient_id);
                                                   
                                                    if($p_value->appointment_id!=0)
                                                    {
                                                         //log::info("in if condition----");
                                                        $this->_checkAndAddServiceReminderU($p_value->patient_id,$p_value->service_id,$p_value->appointment_id,$ap_start_date,$data);
                                                    }
                                                    else{
                                                        //log::info("in else condition----");
                                                        $this->_checkPatientAgeReminderU($p_value->patient_id,$value->service_id,$p_value->status,$p_value->appointment_id);
                                                    }

                                                 
                                                    DB::connection('tenant')->table('update_service_reminders')
                                                        ->where('patient_id',$p_value->patient_id)
                                                        ->where('service_id',$p_value->service_id)
                                                        ->where('update_service_id',$isUpdateServiceRecordModel->id)
                                                        ->whereNull('deleted_at')
                                                        ->update(['deleted_at'=>date('Y-m-d H:i:s'),'deleted_through'=>'cron']);

                                                } //if not empty patient data      

                                            }//if not empty isUpdateServiceRecordExists 

                                        }//foreach patient records
                                        

                                        //Update start and end size in update_service_record
                                        $updateEntries =  DB::connection('tenant')->table('update_service_record')
                                        ->where('id',$updateId)
                                        ->where('is_reminder_updated',1)
                                        ->where('service_id',$value->service_id)
                                        ->update(['start_patient_id'=>$startSize,'end_patient_id'=>$endSize]); 

                                        /******start*****update*is_reminder_updated*to*0*****************/
                                       
                                        $isUpdateServiceRecordExists = DB::connection('tenant')->table('update_service_record')
                                                        ->where('id',$updateId)
                                                        ->where('is_reminder_updated',1)
                                                        ->where('service_id',$value->service_id)
                                                        ->first(); 


                                        if(!empty($isUpdateServiceRecordExists))
                                        {
                                            $end_patient_id = $isUpdateServiceRecordExists->end_patient_id;
                                            if($end_patient_id == $maxPatientId){

                                                 $is_service_has_reminder =DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                                            ->where([                                       
                                                                'id' => $value->id
                                                                ])
                                                            ->update(['is_reminder_updated'=>'0']);

                                                 DB::connection('tenant')->table('update_service_record')
                                                        ->where('id',$updateId)
                                                        ->where('is_reminder_updated',1)
                                                        ->where('service_id',$value->service_id)
                                                        ->update(['is_reminder_updated'=>0,'updated_by'=>'Cron','inserted_through'=>'Cron']);
                                            }
                                        }
                                        /*******end****update*is_reminder_updated*to*0*****************/    
                                    }//if all_patinet_ids    


                                 // Increment the start size for the next chunk
                                  $startSize += $chunkSize;
                               }// chunk size  

                           // Log::info(" after chunk size==>");         

                        }//if not empty isUpdateServiceRecordModel

                       // Log::info(" after not empty isUpdateServiceRecordModel==>");         

                    }//if

                     // Log::info(" before foreach==>");         
                }//foreach

            }
    }//handle
}
