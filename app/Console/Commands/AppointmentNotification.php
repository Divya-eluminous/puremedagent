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
use App\Models\SettingsModel;
use App\Models\AppointmentHasExaminationsModel;
use App\Models\AppointmentHasQueueNumberModel;
use App\Models\WaitingNumberSymbolsModel;
use App\Models\AppointmentHasNotificationModel;

use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
use DateTime;

class AppointmentNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AppointmentNotification:Notify {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'waiting number notification';

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
                                AppointmentModel $AppointmentModel,
                                AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
                                ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
                                PatientsHasServiceControlReminderModel $PatientsHasServiceControlReminderModel,
                                PatientHasReminder $PatientHasReminder,
                                PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
                                ExaminationsModel $ExaminationsModel,
                                PatientsModel $PatientsModel,   
                                SettingsModel $SettingsModel,   
                                AppointmentHasNotificationModel $AppointmentHasNotificationModel,                    
                                AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
                                AppointmentHasQueueNumberModel  $AppointmentHasQueueNumberModel,
                                WaitingNumberSymbolsModel $WaitingNumberSymbolsModel)
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
        $this->SettingsModel = $SettingsModel;
        $this->AppointmentHasNotificationModel = $AppointmentHasNotificationModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
		$this->AppointmentHasQueueNumberModel =$AppointmentHasQueueNumberModel;
		$this->WaitingNumberSymbolsModel=$WaitingNumberSymbolsModel;
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
        //         self::_commandOperation();
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
    //public function _commandOperation()
    {
        log::info("tenant_id=in commandoperation function==>");
        //log::info($tenant_id);

        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
            Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
            Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }

        $today_date =  date('Y-m-d H:i:s', strtotime(now()));  
       // dump($today_date);

        $getKeys = ['HOSPITAL_WAITING_MINUTES'];
        $getSettingData = DB::connection('tenant')->table('settings')
        					->whereIn('setting_key', $getKeys)->whereStatus(1)->get();
        $settingData = [];                            
        foreach ($getSettingData as $key => $value) {
            $settingData[$value->setting_key] = $value->setting_value;
        }
        $collection =  DB::connection('tenant')->table('appointment')
                            ->join('patients','appointment.patient_id','=','patients.id')
                            ->join('users','appointment.doctor_id','=','users.id')
                            ->join('appointment_types','appointment.appointment_type_id','=','appointment_types.id')
                            ->whereRaw('TIMESTAMPDIFF(MINUTE,CURRENT_TIMESTAMP,start_date) BETWEEN 0 AND '.$settingData['HOSPITAL_WAITING_MINUTES'])
                            ->where('appointment.status',1)
                            ->where('start_date','>=',$today_date)
                            ->where('appointment.patient_id',46726)
                            ->selectRaw('TIMESTAMPDIFF(MINUTE,CURRENT_TIMESTAMP,start_date) as mins,
                                        appointment.id as id,appointment.start_date as date,
                                        users.first_name as doctor_first_name,
                                        users.last_name as doctor_last_name,
                                        users.doctor_speciality,
                                        users.img_path,
                                        appointment_types.id as appointment_type_id,
                                        patients.id as patient_id,
                                        patients.first_name as patient_first_name,
                                        patients.family_name as patient_last_name,
                                        appointment_types.name as aname')
                            ->orderBy('start_date', 'ASC')
                            ->get();

                      //  dump($collection);    
        // log::info('Appointment-count cron => 1 - '.json_encode($collection));
        if(!empty($collection))
        {
            foreach($collection as $row)
            {
                // log::info('Appointment-count cron => 2 - '.json_encode($row));
                $appointment_id = $row->id;
                $patient_id = $row->patient_id;
                $appointment_date_time   = $row->date ?? '';
                $doctorName =$row->doctor_first_name." ".$row->doctor_last_name; 
                $appointment_type_id = $row->appointment_type_id ?? '';
                $doctor_speciality = $row->doctor_speciality ?? '';
                $appointment_type   = $row->aname ?? '';
                $appointment_time = '';
                $patient_name=$row->patient_first_name." ".$row->patient_last_name; 
                if(!empty($appointment_date_time))
                {
                    $appointment_time = date('d.F',strtotime($row->date)).",um ".date('H:i',strtotime($row->date))." Uhr.";
                }
                $patientQueue = DB::connection('tenant')->table('appointment_has_queue_number')
                                    ->join('waiting_number_symbols','appointment_has_queue_number.symbol_id','=','waiting_number_symbols.id')
                                    ->where('patient_id',$patient_id)
                                    ->where('appointment_id',$appointment_id)
                                    ->first(); 

                 // dump($patientQueue);
                                    
                if(!empty($patientQueue))
                {
                   //  dump("in patientQueue");

                    $url = $patientQueue->url;
                    $strName = $patientQueue->name;
                    $message = 'Willkommen bei Ihrem Termin mit '.$doctorName.'. Nehmen Sie bitte im Wartebereich Platz. Sie werden über die App und den Bildschirm im Wartebereich aufgerufen.';
                    // $data['row']         = $row; 
                    // $data['url']         = $url;
                    // $data['symbol_name'] = $strName;
                    // self::_createLog('createWaitingNumber',$data,'info');
                    // DB::connection('tenant')->table('ActivityLogModel')->addLog('Create Waiting Number','has created waiting number','Create',null,$data);
                }
                else {
                  //  dump("else patientQueue");
                    $queue_date = date('Y-m-d');
                    $symbolId = DB::connection('tenant')->table('appointment_has_queue_number')
                    			->where('date',$queue_date)->pluck('symbol_id');
                    $waitingSymbol = DB::connection('tenant')->table('waiting_number_symbols')
                    				->whereNotIn('id', $symbolId)->first();
                    $strName = isset($waitingSymbol->name)?$waitingSymbol->name:'';
                    $url = isset($waitingSymbol->url)?$waitingSymbol->url:'';
                    $id = isset($waitingSymbol->id)?$waitingSymbol->id:'';

					$HasQueueNum = [];
					$HasQueueNum['patient_id'] =  $patient_id;
					$HasQueueNum['appointment_id'] = $appointment_id;
					$HasQueueNum['symbol_id'] =  $id;
					$HasQueueNum['date'] =  $queue_date;
					$HasQueueNum['queue_number'] =  $strName; 
					$HasQueueNum['queue_number_type'] =  0;
					$HasQueueNum['status'] =  1;
					$HasQueueNum['created_at'] =  date('Y-m-d H:i:s');
                    $AppointmentHasQueueNum = DB::connection('tenant')->table('appointment_has_queue_number')
                                                ->where('patient_id',$patient_id)
                                                ->where('appointment_id',$appointment_id)
                                                ->where('date',$queue_date)
                                                ->whereNull('deleted_at')
                                                ->get();
                    if(sizeof($AppointmentHasQueueNum) == 0)
                    {
                        DB::connection('tenant')->table('appointment_has_queue_number')->insertGetId($HasQueueNum);
                    }
                    $doctor_image = asset('assets/admin/images/default-image.png');
                    if (!empty($row->img_path) && is_file(storage_path().'/app/'.$row->img_path)) 
                    {
                        $doctor_image = url('/storage/app/'.$row->img_path); 
                    }
                    $title = 'Erinnerung an Ihren Termin';
                    // $mobileId = DB::table('patient_has_device')
                    //                     ->where('patient_id',$patient_id)
                    //                     ->get(['device_id']);

                    $mobileId = DB::connection('tenant')->table('patient_has_device')
                                        ->where('patient_id',$patient_id)
                                        ->get(['device_id']);

                    // dump("mobileId");
                    // dump($mobileId);
                                        
                    log::info('mobileId'. json_encode($mobileId));
                    $content = 'Hallo '.$patient_name.', Wating number : '.$strName;

                     // dump("content===>");
                     // dump($content);

                    if(!empty($mobileId))
                    {
                        $mobile_uuids = array_column($mobileId->toArray(), "device_id");
                        $player_ids   = $mobile_uuids;
                        $headings     = array("en" => (string)$title);
                        $content      = array("en" => (string)$content);            
                        $postData = array(
                                    "appointment_id" => $appointment_id,
                                    "date_time"     => $appointment_date_time,
                                    "doc_name"      => $doctorName,
                                    "doc_speciality" => $doctor_speciality,
                                    "appointment_type"    => $appointment_type,
                                    "appointment_type_id" => $appointment_type_id,
                                    "doc_img"             => $doctor_image,
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
                        ); 
                        $restAPIKey = config('constants.ONESIGNAL_REST_API_KEY');
                        $fields = json_encode($fields);

                       // dump($mobile_uuids);
                       // dump($restAPIKey);
                       // dump($fields);


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

                        // dump($data);

                        log::info('Appointment-Notify - '.$data);
                    }
                }
            }
        }
    }


}
