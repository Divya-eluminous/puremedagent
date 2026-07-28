<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
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

class UpdatedReminderStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdatedReminderStatus:update {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update reminder process';

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
        // $website_id = $this->option('website_id');
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
           // Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }
        
        self::_fetchUpdatedService();              
        //Log::info("called status cron".$website_id);
    } 

    public function _fetchUpdatedService()
    {

        $is_service_has_reminder = DB::connection('tenant')
                        ->table('preferred_channels_for_reminders_setting')
                        ->where(
                            [                                       
                            'is_reminder_updated' => '1',
                            'type' =>'service'
                            ]
                        )->get();

                       // dd($is_service_has_reminder);

        if(!empty($is_service_has_reminder) && count($is_service_has_reminder) > 0)
        {
            //Log::info('updated set');
            foreach($is_service_has_reminder as $key=>$value)
            {
                //delete all previous reminder
                //dump($value);
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
                    $all_patinet_ids = DB::connection('tenant')
                        ->table('patient_has_service_reminder')
                        ->where('service_id',$value->service_id) 
                        ->where('patient_id','>',19406)
                        ->groupby('patient_id','appointment_id')
                        ->get();

                    //dd(count($all_patinet_ids));

                    foreach($all_patinet_ids as $p_key=>$p_value)
                    {
                        //dd($p_value);
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
                                
                        DB::connection('tenant')->table('patient_has_service_reminder')
                        ->where('service_id',$p_value->service_id)
                        ->where('appointment_id',$p_value->appointment_id) 
                        ->where('patient_id',$p_value->patient_id)
                        ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                        $reactivateReminder =  DB::connection('tenant')
                        ->table('patient_has_reminder')
                        ->whereIn('service_reminder_id',$id_holder)
                        ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                        $appoitment_data = DB::connection('tenant')
                            ->table('appointment')
                            ->where('id',$p_value->appointment_id)->first();

                        $patinet_data = DB::connection('tenant')
                            ->table('patients')
                            ->where('id',$p_value->patient_id)->first();

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

                        // set new reminder
                        $this->_checkAndAddServiceReminder($value,$p_value->patient_id,$p_value->appointment_id,$ap_start_date,$data);

                        
                    }

                   
                }

                $is_service_has_reminder = DB::connection('tenant')
                            ->table('preferred_channels_for_reminders_setting')
                            ->where(
                                [                                       
                                'id' => $value->id
                                ]
                            )
                            ->update(['is_reminder_updated'=>'0']);

            }
        }

        dump('out');
    }

    public function _checkAndAddServiceReminder($service_value,$patient_id,$appointment_id,$appointment_start_date,$data)
    {
       if(!empty($service_value))
       {
        $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                                [
                                'service_id' => $service_value->service_id,
                                'is_reminder_updated' => '1'
                                ]
                                )->first();
       
        $default_reminder = 'general';
       
       if(!empty($is_service_has_reminder))
       {
        //  dump($is_service_has_reminder);
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
                $is_service_has_reminder->holiday_reminder =  $h_reminder->holiday_reminder;
              

                if($default_reminder == 'general' && !empty($appointment_start_date))
                {
                    $this->_generalReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$is_service_has_reminder->service_id);
                }
                else
                {
                    if(!empty($data['age']) && $data['age']!='')
                    {

                        $start_date = date('Y-m-d H:i:s', strtotime($data['birth_date']. ' + '.($data['age']).' year'));

                        //dd($start_date );
                       $this->_ageReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$data,$is_service_has_reminder->service_id);  
                    }
                } 
            }
        } 
    }

    public function _getDate($start_date,$period,$frequency_type)
    {
      
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
        return $days ;
    }

    public function _generalReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        $reminder_array = [];

        // 1st reminder
        $start_date = $start_date;

        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->general_period,$is_service_has_reminder->general_period_frequency_type);

        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->general_first_frequency,$is_service_has_reminder->general_first_frequency_type,'minus');

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
                $reminder_tmp['reminder_status'] = 'Set';
                $reminder_tmp['status'] = 'activate';  
                //  $reminder_tmp['parent_id'] = $parent_id;
                $reminder_tmp['type'] = 'general';
                $reminder_tmp['created_at'] = date('Y-m-d H:i:s') ;
                
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
                }
                // Log::info('temp');
                // Log::info($reminder_id);
            }

            $value5_days = $this->_getDate(end($reminder_array),$is_service_has_reminder->general_new_frequency,$is_service_has_reminder->general_new_frequency_type);

            // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +"   .(int)$value5_days." day"));
            $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');
            //Log::info(end($reminder_array)."---".$reactive_reminder );
            // dd('sssss');
            $temp = [];
            $temp['patient_id'] =  $patient_id;
            $temp['last_reminder_date'] =  end($reminder_array);
            $temp['next_reminder_date'] =  $reactive_reminder;
            $temp['service_reminder_id'] =  $reminder_id;
            $temp['status'] =  'activate';
            $temp['created_at'] = date('Y-m-d H:i:s');
            $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
            //Log::info($reactive_reminder);
        }       
    }
 
    public function _ageReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$data,$service_id)
    {
       dump($data['age']);
        dump($is_service_has_reminder->age_from);
        dump($is_service_has_reminder->age_to);

        if($data['age'] == $is_service_has_reminder->age_from || ($data['age'] < $is_service_has_reminder->age_to && $data['age'] > $is_service_has_reminder->age_from))
        {

            $start_date = $start_date;
            //Log::info('start_date is the a'.$start_date);dd('s');

            $value1_days = $this->_getDate($start_date,$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);

            $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

            $value3_days = $this->_getDate($period_date,$is_service_has_reminder->age_first_frequency,$is_service_has_reminder->age_first_frequency_type);

            // $first_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($period_date)) . " -".(int)$value3_days." day"));
            $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');
            $reminder_array[] = $first_reminder;
            // log::info('sssss');
            // Log::info(json_encode($reminder_array));
            // dd('daaa');
            for($i=0; $i<($is_service_has_reminder->age_number_of_interval-1); $i++)
            {
                $value4_days = $this->_getDate($period_date,$is_service_has_reminder->age_time_interval,$is_service_has_reminder->age_time_interval_frequency_type);
                
                // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));

                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');
                if( $third_reminder !=  $first_reminder)
                {
                    $reminder_array[] = $third_reminder;
                }
            }       
            sort($reminder_array);
        }
        // elseif($data['age'] < $is_service_has_reminder->age_from)
        // {
        //     $diff = $is_service_has_reminder->age_from - $data['age'];
        //     $start_date = date('Y-m-d', strtotime($data['birth_date']. ' + '.($data['age'] + $diff).' year'));

        //     dd($start_date);
        //     $start_date = $this->_filterWeekendAndHoiliday($start_date,0,$is_service_has_reminder->holiday_reminder,'plus');

        //     $period_date = $start_date;
        //       //Log::info('start_date is the d'.$start_date.$data['age']);dd('s');
        //     $reminder_array[] = $period_date;
        //    // Log::info(json_encode($reminder_array));
        //    // dd('d');
        //     for($i=0; $i<($is_service_has_reminder->age_number_of_interval-1); $i++)
        //     {
        //         $value4_days = $this->_getDate($period_date,$is_service_has_reminder->age_time_interval,$is_service_has_reminder->age_time_interval_frequency_type);
                
        //         // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));

        //         $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

        //           $reminder_array[] = $third_reminder;
                
        //     }       
        //     sort($reminder_array);
        // }
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
                $reminder_tmp['reminder_status'] = 'Set';
                $reminder_tmp['status'] = 'activate';  
               // $reminder_tmp['parent_id'] = $parent_id;
                $reminder_tmp['type'] = 'age';
                $reminder_tmp['created_at'] = date('Y-m-d H:i:s');

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
            $temp['created_at'] = date('Y-m-d H:i:s');
            $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
        }
        
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
        //     dump($calculated_date);
        // }
        dump($calculated_date);
        //Log::info($calculated_date);
        return $calculated_date;
    }

}
