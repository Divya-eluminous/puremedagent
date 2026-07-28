<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
//Models
use App\Models\AdminUserModel; 
use App\Models\RosterModel; 
use App\Models\RosterHasDatesModel;
use App\Models\AppointmentModel; 
use App\Models\PatientsModel;
use App\Models\GoogleColorsModel;
use App\Models\DashboardNoticeModel;
use App\Models\PatientHasDocumentsModel;
use App\Models\AppointmentTypesModel;
use App\Models\AppointmentHasNotificationModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\ActivityLogModel;
use App\Models\AppointmentHasExaminationsModel;
use App\Models\ExaminationsModel;
use App\Models\SpecialistDocumentsModel;
use App\Models\SpecialistModel;
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\CheckListHasSelectedQuestionModel;
use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\PatientsHasServiceControlReminderModel;
use App\Models\PatientHasReminder;
use Illuminate\Support\Facades\Log; 
use App\Models\EventTypeHasExaminationsModel;
use App\Models\CheckListHasHeadingSectionModel;
use App\Models\HeadingSectionHasQuestionModel;
use App\Models\CheckListModel;
use App\Models\RosterHasWeeksHasTimeFramesModel;
use App\Models\DeletedAppointmentTrackModel;
use App\Models\DynamicExaminationsModel;
use App\Models\DynamicAppointmentTypesModel;
use App\Models\DynamicAppointmentTypesHasExaminationsModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\SettingsModel;
use App\Models\Event;
use App\Models\CountryCodesModel; // new model for country code lookup

use App\Traits\GeneralTrait; 
use Validator;
use DateTime;

// use Google_Client;
// use Google_Service_Calendar;
// use Google_Service_Calendar_Event;
// use Google_Service_Calendar_EventDateTime;
// use Google_Service_Exception;

use App;
use Hash;
use Mail;
use DB;
use Auth;
use Config;
use Carbon\Carbon;
use URL;

use Illuminate\Support\Facades\Lang;

// Request
use App\Http\Requests\Admin\AppointmentRequest;
use App\Models\UserHasAppointmentType;

use App\Http\Requests\Admin\PatientsRequest;  //added on 3-april-24
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024


class DashboardController extends Controller
{
    use GeneralTrait;
    private $BaseModel;
    public function __construct(
                                AdminUserModel $AdminUserModel,
                                AppointmentModel $AppointmentModel,
                                PatientsModel $PatientsModel,
                                GoogleColorsModel $GoogleColorsModel,
                                AppointmentTypesModel $AppointmentTypesModel,
                                AppointmentHasNotificationModel $AppointmentHasNotificationModel,
                                ActivityLogModel $ActivityLogModel,
                                RosterModel $RosterModel,
                                DashboardNoticeModel $DashboardNoticeModel,
                                RosterHasDatesModel $RosterHasDatesModel,
                                PatientHasDocumentsModel $PatientHasDocumentsModel,
                                AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
                                CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
                                AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
                                ExaminationsModel $ExaminationsModel,
                                HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
                                SpecialistDocumentsModel $SpecialistDocumentsModel,
                                CheckListModel $CheckListModel,
                                SpecialistModel $SpecialistModel,
                                ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
                                CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
                                ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
                                PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
                                PatientsHasServiceControlReminderModel $PatientsHasServiceControlReminderModel,
                                PatientHasReminder $PatientHasReminder,
                                EventTypeHasExaminationsModel $EventTypeHasExaminationsModel,
                                RosterHasWeeksHasTimeFramesModel $RosterHasWeeksHasTimeFramesModel,
                                DeletedAppointmentTrackModel $DeletedAppointmentTrackModel,
                                DynamicExaminationsModel $DynamicExaminationsModel,
                                DynamicAppointmentTypesModel $DynamicAppointmentTypesModel,
                                DynamicAppointmentTypesHasExaminationsModel $DynamicAppointmentTypesHasExaminationsModel,
                                ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
                                SettingsModel $SettingsModel,
                                UserHasAppointmentType $UserHasAppointmentType,
                                CountryCodesModel $CountryCodesModel

                            )
    {
        $this->ViewData             = []; 
        $this->JsonData             = [];
        $this->todosByDate          = [];
        $this->BaseModel            = $AppointmentModel; 
        $this->AdminUserModel       = $AdminUserModel;
        $this->AppointmentModel     = $AppointmentModel;
        $this->PatientsModel        = $PatientsModel;
        $this->GoogleColorsModel    = $GoogleColorsModel;
        $this->AppointmentTypesModel = $AppointmentTypesModel;
        $this->AppointmentHasNotificationModel  = $AppointmentHasNotificationModel;
        $this->ActivityLogModel                 = $ActivityLogModel;
        $this->RosterModel             = $RosterModel;
        $this->DashboardNoticeModel    = $DashboardNoticeModel;
        $this->RosterHasDatesModel     = $RosterHasDatesModel;
        $this->PatientHasDocumentsModel= $PatientHasDocumentsModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->SpecialistModel = $SpecialistModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;
        $this->ExaminationsHasMultipleCheckListModel = $ExaminationsHasMultipleCheckListModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->CheckListModel = $CheckListModel;
        $this->PatientsHasServiceControlReminderModel = $PatientsHasServiceControlReminderModel; 
        $this->PatientHasReminder = $PatientHasReminder; 
        $this->EventTypeHasExaminationsModel = $EventTypeHasExaminationsModel;
        $this->CheckListHasHeadingSectionModel = $CheckListHasHeadingSectionModel;
        $this->HeadingSectionHasQuestionModel = $HeadingSectionHasQuestionModel;
        $this->RosterHasWeeksHasTimeFramesModel = $RosterHasWeeksHasTimeFramesModel;
        $this->DeletedAppointmentTrackModel = $DeletedAppointmentTrackModel;
        $this->DynamicExaminationsModel = $DynamicExaminationsModel;
        $this->DynamicAppointmentTypesModel = $DynamicAppointmentTypesModel;
        $this->DynamicAppointmentTypesHasExaminationsModel = $DynamicAppointmentTypesHasExaminationsModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->SettingsModel = $SettingsModel;
        $this->UserHasAppointmentType   = $UserHasAppointmentType;
        $this->CountryCodesModel = $CountryCodesModel;

        $this->ModuleTitle  = __('admin.TITLE_DASHBOARD');  
        $this->ModuleView   = 'admin.dashboard.';
        $this->ModulePath   = 'admin.dashboard';
        
        $this->patientText      = 'Patient';
        $this->doctorText       = 'Arzt';
        $this->appointmentText  = 'Typ';
        $this->startDateText    = 'Beginn';
        $this->endDateText      = 'Ende';
        $this->notesText        = 'Notizen';
        $this->services         = 'Services';

        /*--------------------------------------
            | Google Client
            ------------------------------*/
            // $client = new Google_Client();
            // $client->setApplicationName('Puregyn');
            // $client->setAuthConfig(public_path('google-calendar/client_secret.json'));
            // $client->addScope(Google_Service_Calendar::CALENDAR);
            // $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false)));
            // $client->setHttpClient($guzzleClient);
            // //For Offline Access
            // $client->setAccessType('offline');
            // $client->setApprovalPrompt("force");//force //select_account consent

            // $this->client = $client;
            // $this->tokenPath = public_path('google-calendar/token.json');
            // $this->tokenPath = '/opt/app-shared/php/data/storage/app/google-calendar/token.json';
            //$this->tokenPath = 'public/google-calendar/token.json';
      /*|
        --------------------------------------*/
       
    }

    public function index()
    {
        //log::info("test-deails");
        // dd('on Dashboard Controller');
        $this->ModuleTitle  = __('admin.TITLE_DASHBOARD');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle;
        $this->ViewData['moduleAction'] = $this->ModuleTitle;
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['user'] = $this->AdminUserModel
                                       // ->where('status', 1)
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'doctor');
                                        })
                                        ->get();
         $this->ViewData['country_codes'] = $this->CountryCodesModel
            ->where('is_active',1)
            // ->orderBy('phone_code')
            ->pluck('phone_code')
            ->toArray();
                                        // dd(Auth::user()->id);
        // $userId =  Auth::user()->id;
        // $user = $this->AdminUserModel::find($userId);
        // $permissions = $user->getAllPermissions();
        // $role = $user->getRoleNames();
        // dump($role);
        // dump($permissions);

        // All patients
        /*  $patients =  $this->PatientsModel
                            ->where('status', 1)
                            ->get();
        $this->ViewData['patient'] = $this->PatientsModel
                                        ->where('status', 1)
                                        ->get(); */
        // All appointment types 
        $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->where('dynamic_appointment', 0)->get(); 
        $this->ViewData['specialist_details']= self::__GetSecialits();
        $databaseName = DB::connection()->getDatabaseName();
        $this->ViewData['databaseName'] = $databaseName;
        // added by vijay 8/3/24
        $quarter_setting = 0;
        $optimal_appointment = $this->SettingsModel->where(['setting_key' => 'OPTIMAL_APPOINTMENT'])->select('setting_key', 'setting_value')->first();
        if (isset ($optimal_appointment) && !empty ($optimal_appointment)) {
            $quarter_setting = $optimal_appointment->setting_value;
        }
        $this->ViewData['quarter_setting'] = $quarter_setting;
        return view($this->ModuleView.'index', $this->ViewData);
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



    //Did changes on 22-nov-23
    public function updateReminders($patientID)
    {
        $patient_id=base64_decode(base64_decode($patientID));

        log::info("in updateReminders function of single url..for patient id..");
        log::info($patient_id);

        $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')
                        ->where(
                            [                                       
                           // 'is_reminder_updated' => '1',
                            'type' =>'service'
                            ]
                        )->get();
            // echo "update<pre>";print_r($is_service_has_reminder);exit;
            if(!empty($is_service_has_reminder))
            {
                foreach($is_service_has_reminder as $key=>$value)
                {
                    $is_service_reminder_checked = DB::table('examinations')->where(
                        [
                        'id' => $value->service_id,
                        'show_as_reminder' => '1',
                            // 'status' => '1'
                        ])
                        ->whereNull('deleted_at')
                        ->first();
                    // print_r($is_service_reminder_checked);exit;
                    if(!empty($is_service_reminder_checked))
                    {
                        dump("in updateReminders function of is_service_reminder_checked....");
                        log::info("in updateReminders function of is_service_reminder_checked....");
                        log::info("in function is running for service id..===>");
                        log::info($value->service_id);

                        $all_patinet_ids = DB::table('patient_has_service_reminder')
                                            ->leftjoin('patients','patients.id','=','patient_has_service_reminder.patient_id')
                                            ->where('service_id',$value->service_id)
                                            ->whereNull('patients.deleted_at')
                                            ->whereNull('patient_has_service_reminder.deleted_at')
                                            ->where('patient_id',$patient_id)
                                            // ->where('appointment_id','!=',0)
                                            ->groupby('patient_id','appointment_id')
                                            ->get(['patient_has_service_reminder.*']);
                        // echo "<pre>";print_r($all_patinet_ids);exit;
                        //dd(count($all_patinet_ids));
                        log::info($all_patinet_ids);
                        foreach($all_patinet_ids as $p_key=>$p_value)
                        {
                            //dd($p_value);
                            $ids = DB::table('patient_has_service_reminder')
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
                                    
                            DB::table('patient_has_service_reminder')
                            ->where('service_id',$p_value->service_id)
                            ->where('appointment_id',$p_value->appointment_id) 
                            ->where('patient_id',$p_value->patient_id)
                            ->whereNull('deleted_at')
                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                            $reactivateReminder =  DB::table('patient_has_reminder')
                            ->whereIn('service_reminder_id',$id_holder)
                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                            $appoitment_data = DB::table('appointment')
                                ->where('id',$p_value->appointment_id)->first();

                            $patinet_data = DB::table('patients')
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
                           
                            log::info($p_value->appointment_id);
                            if($p_value->appointment_id!=0)
                                $this->_checkAndAddServiceReminderU($p_value->patient_id,$p_value->service_id,$p_value->appointment_id,$ap_start_date,$data);
                            else
                                // $this->_checkPatientAgeReminderU($p_value->patient_id,$value->service_id,$p_value->status);

                               //commented on 29-nov-23 for adding no app code
                               //  $this->_checkPatientAgeReminderU($p_value->patient_id,$value->service_id,$p_value->status,$p_value->appointment_id,$p_value->reminder_date);

                                /*******start*****added on 29-nov-23**for no appointment criteria***********/ 
                                   $checkHasBookedExamination = DB::table('appointment_has_examinations')
                                   ->where('patient_id',$p_value->patient_id)
                                   ->where('examination_id',$p_value->service_id) 
                                   ->select('appointment_id')
                                   ->first(); 
                                   //echo "<pre>";print_r($checkHasBookedExamination); 

                                   if(isset($checkHasBookedExamination))
                                   {
                                     $BookedAppointmentId = $checkHasBookedExamination->appointment_id;

                                     $bookedAppointmentData = DB::table('appointment')
                                                        ->where('id',$BookedAppointmentId)
                                                        ->where('appointment_status','Fertig')
                                                        ->select('start_date','id')
                                                        ->orderBy('id', 'desc')
                                                        ->first();
                                      // echo "<pre>";print_r($bookedAppointmentData);

                                       if(isset($bookedAppointmentData))
                                       {    
                                          $ap_start_date = $bookedAppointmentData->start_date;
                                          $a_date = explode(" ",$ap_start_date);
                                          $ap_start_date = $a_date[0]." ".$value->notify_time.":00";
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

                                /*****end****added on 29-nov-23**for no appointment criteria*******/ 

                        }//foreach
                        
                       /* $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')
                                        ->where([                                       
                                            'id' => $value->id
                                            ])
                                        ->update(['is_reminder_updated'=>'0']);*/
                    }
                }
            }
            else
            {
               log::info("No reminder parameter changed for the service...");
               // dump('No reminder parameter changed for the service...');
            }
    }//updateReminders

    //added on 29-nov-23
    public function _checkAndAddAppointmentAgeReminderU($appointment_id,$start_date,$patient_id,$service_id)
    {
       // dump('in _checkAndAddAppointmentAgeReminderU function.......');

        log::info('innnnnn _checkAndAddAppointmentAgeReminderU function..........');

        $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_id,
                                         //'is_reminder_updated' => '0'
                                        ]
                                        )->first();
               
        $default_reminder = 'general';
        if(empty($is_service_has_reminder))
        {                          
            $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
                                [
                                'type' => 'global',
                                ]
                                )->first();
        }else
        {
            $default_reminder = $is_service_has_reminder->activated_reminder;
           
        }  

       // dump("default_reminder===> ".$default_reminder);

        log::info("default_reminder===> ");
       // log::info($default_reminder);

        if($default_reminder == 'age')
        {
               //Added below query on 25-oct-23
                $getPatient = DB::table('patients')
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
                $firstReminderdate = DB::table('patient_has_service_reminder')
                                        ->where('patient_id',$patient_id)
                                        ->where('service_id',$service_id)
                                        ->where('appointment_id',$appointment_id)
                                         ->whereNull('deleted_at')
                                        ->first();

                log::info("firstReminderdate==>");
                //log::info($firstReminderdate);                        

                // if(!empty($firstReminderdate)) 
                //     $first_remidner_date=$firstReminderdate->reminder_date;
                // else $first_remidner_date=$start_date;

                $first_remidner_date=$start_date;

                //cacluate end cycle only get days here 
                $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_end_cycle,$is_service_has_reminder->age_end_cycle_frequency_type);  


                // dump("endCycleDyas ==>");
                // dump($endCycleDyas);

                log::info("endCycleDyas==>");
                log::info($endCycleDyas);   

                //get days of first #1
                $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);

                // dump("agePeriodDays ==>");
                // dump($agePeriodDays);

                 log::info("agePeriodDays==>");
                log::info($agePeriodDays); 


                //#1-#3
                $periodOneminusthird=($agePeriodDays-$value3_days);


                // dump("periodOneminusthird ==>");
                // dump($periodOneminusthird);

                log::info("periodOneminusthird==>");
                log::info($periodOneminusthird); 


                //#1-#3+#6
                $finalDays=($endCycleDyas+$periodOneminusthird); 

                // dump("finalDays ==>");
                // dump($finalDays);

                log::info("finalDays==>");
                log::info($finalDays);


                //filter holidays
                $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');

                // dump("endcycle_date ==>");
                // dump($endcycle_date);



                log::info($service_id);
                log::info($endcycle_date);

                $reminder_id = 0;
                if(!empty($reminder_array) && count($reminder_array) > 0)
                {

                     //Added below code on 4-oct-23                       
                     $checkFuturRemidner=DB::table('patient_has_service_reminder')
                                            ->where('patient_id',$patient_id)
                                            ->where('service_id',$service_id)
                                            ->where('appointment_id','>',$appointment_id)
                                            ->where('appointment_id','!=',0)
                                            ->whereNull('deleted_at')
                                            ->count();                       

                      log::info('checkFuturRemidner===>');
                      log::info($checkFuturRemidner);      
                               
                      // dump("checkFuturRemidner ==>");
                      // dump($checkFuturRemidner);

                    // echo $appointment_id."===<pre>";print_r($checkFuturRemidner);
                    for($i=0;$i<count($reminder_array);$i++)
                    { 
                         log::info('in for loop reminder_array===>');    
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


                        $getAppointmentExamination = DB::table('appointment_has_examinations')->where('examination_id',$service_id)->where('appointment_id',$appointment_id)->first();

                        // Start Added below code for optimizing code on 4-oct-23               
                        $is_exists = DB::table('patient_has_service_reminder')
                                        ->where('patient_id', $patient_id)
                                        ->where('appointment_id', $appointment_id)
                                        ->where('service_id', $service_id)
                                        ->where('reminder_date', $reminder_array[$i])
                                        ->where('reminder_status', 'Set')
                                        ->where('status', 'activate')
                                        ->where('type', 'age')
                                        ->whereNull('deleted_at')
                                        ->count();      

                        log::info('is_exists==>');
                        log::info($is_exists);

                        if($is_exists == 0)
                        {
                                 log::info('if is_exists is 0==>');
                                 log::info($reminder_tmp);

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
                                log::info('age===>');
                                log::info($age);


                                 // dump(" age is = ==>");
                                 // dump($age);

                                if($age == $is_service_has_reminder->age_from || ($age < $is_service_has_reminder->age_to && $age > $is_service_has_reminder->age_from))
                                {
                                    log::info('after age in..........===>');
                                    $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($reminder_tmp);
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
                        $parent_id = DB::table('patient_has_reminder')->insertGetId($temp);
                    }//if reminder_id condition
                   
                }  
        }//if default reminder is age

   
    }//_checkAndAddAppointmentAgeReminderU
  

    //Added on 22-nov-23
    public function _checkPatientAgeReminderU($patient_id='',$service_id='',$status,$appointment_id,$prevreminderdate)
    {
         log::info('innnnnnnnnnnnnnnn _checkPatientAgeReminderU dashboard controller....');
        
        $totalEntries = 0;
      

         $getPatient = DB::table('patients')
                            ->select('birth_date','age') // added on 29-sept-23 added for db connection tenant
                            ->where('id',$patient_id)
                            ->whereNull('deleted_at')
                            ->first();                   


        $getAgeServices = DB::table('preferred_channels_for_reminders_setting as pcr')
                            ->leftjoin('examinations','examinations.id','pcr.service_id')
                            ->where('examinations.show_as_reminder','1')
                            ->where('pcr.activated_reminder','age')
                            ->where('pcr.service_id',$service_id)
                            ->whereNull('pcr.deleted_at')
                            ->whereNull('examinations.deleted_at')
                            ->get(['examinations.id as service_id', 'pcr.notify_time', 'pcr.age_from', 'pcr.age_to','pcr.age_end_cycle','pcr.age_end_cycle_frequency_type','pcr.holiday_reminder']);

        //  dump($getAgeServices);
                        
        foreach ($getAgeServices as $ke => $ser)
        {
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

               log::info('patient_id===>');
               log::info($patient_id);               
               log::info('service_id===>');    
               log::info($service_id);            

             $checkRecord = DB::table('patient_has_service_reminder')
                           // ->select('id')
                            ->where('patient_id', $patient_id)
                            ->where('service_id', $service_id)
                            ->where('appointment_id', $appointment_id) // added on 20-oct-23
                            ->where('reminder_status', 'Set')
                           // ->where('status', 'activate')  //commented on 11-oct-23
                            ->where('type', 'age')
                            ->whereNull('deleted_at')  //added on 11-oct-23
                            //->count();
                             // ->get(['id']);   
                              ->get();                                

             log::info('checkRecord===>');
             log::info($checkRecord);
                            
             if(sizeof($checkRecord) == 0)
           // if($checkRecord == 0)                 
            {
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

                           //added below code on 8-jan-24 (9-jan) 
                           $pDate = new DateTime($prevreminderdate);
                           if($pDate->format('Y')<2015)
                           {
                             $prevreminderdate = date('Y-m-d ').$ser->notify_time.':00';
                           }
                           else
                           {
                              $prevreminderdate = $prevreminderdate;   
                           }//else            


                            $endCycleDyas = $this->_getDate(($prevreminderdate),$ser->age_end_cycle,$ser->age_end_cycle_frequency_type);  
                            $endcycle_date = $this->_filterWeekendAndHoiliday(($prevreminderdate),$endCycleDyas,$ser->holiday_reminder,'plus');

                              log::info(' in app id 0 endCycleDyas===>');
                              log::info($endCycleDyas);

                              log::info(' in app id 0 endcycle_date===>');
                              log::info($endcycle_date);

                            $reminderDate = new DateTime($prevreminderdate);
                            $endDate = new DateTime($endcycle_date);
                            $date_today=new DateTime();

                              log::info(' in app id 0 reminderDate===>');
                             // log::info($reminderDate);

                              log::info(' in app id 0 endDate===>');
                             // log::info($endDate);

                            if($endCycleDyas>0)
                            {

                                log::info(' if endCycleDyas > 0===>');                                 
                                //if($reminderDate >= $endDate ) $reminder_status='ignore';
                                 //if parameter 6 has not passed yet
                                if($endDate>$date_today){
                                   log::info("endDate is greater than todays date");
                                    $reminder_status='Set'; 
                                    $status = 'activate';
                                  
                                }//if 
                                else
                                {
                                    log::info("endDate is less than todays date");
                                    //if paramter 6 has passed (end date is passed yet)
                                     $reminder_status='ignore'; 
                                     $status = 'activate';
                                } 

                                 log::info(' in app id 0 if condition reminder_status===>');
                                 log::info($reminder_status);

                                 log::info(' in app id 0 if condition status===>');
                                 log::info($status);

                            }//if endCycleDyas > 0
                        }//if isset prevreminderdate
                    /**********calculation for the status***21-nov-23******/ 

                    //Added on 29-sept-23
                    $patientsHasServiceReminderArr = [];
                    $patientsHasServiceReminderArr['patient_id'] = $patient_id;
                    $patientsHasServiceReminderArr['appointment_id'] = 0;
                    $patientsHasServiceReminderArr['service_id'] = $service_id;
                    $patientsHasServiceReminderArr['parent_id'] = 0;
                    if(isset($prevreminderdate))
                    {

                       //added below code on 8-jan-24 (9-jan)
                       $pDate = new DateTime($prevreminderdate);
                       if($pDate->format('Y')<2015)
                       {
                         $patientsHasServiceReminderArr['reminder_date'] = date('Y-m-d ').$ser->notify_time.':00';
                       }
                       else
                       {
                          $patientsHasServiceReminderArr['reminder_date'] = $prevreminderdate;   
                       }//else                     

                       //commented below code on 8-jan-24 (9-jan)
                       // $patientsHasServiceReminderArr['reminder_date'] = $prevreminderdate;   
                    }
                    else
                    {
                     $patientsHasServiceReminderArr['reminder_date'] = date('Y-m-d ').$ser->notify_time.':00';
                     $status = 'activate';
                    }
                    // $patientsHasServiceReminderArr['reminder_status'] = 'Set';
                    $patientsHasServiceReminderArr['reminder_status'] = $reminder_status;
                    $patientsHasServiceReminderArr['type'] = 'age';
                    $patientsHasServiceReminderArr['status'] = $status;//'activate';
                    $patientsHasServiceReminderArr['created_at'] =  date('Y-m-d H:i:s');
                    $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($patientsHasServiceReminderArr);
                    log::info('after insert record.......');
                    $totalEntries++;

                }//if age 
            }
        }

        log::info('totalEntries');
        log::info($totalEntries);

        return $totalEntries;
    }//_checkPatientAgeReminderU

    //Added on 22-nov-23
    public function _checkAndAddServiceReminderU($patient_id,$service_id,$appointment_id,$appointment_start_date,$data)
    {
       log::info('innnnnnnnnnnnnnnn _checkAndAddServiceReminderU dashboard controller..');


        if($service_id!="" && $service_id > 0)
        {
            // foreach ($all_services as $service_key => $service_value) 
            // {
                $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_id,
                                         //'is_reminder_updated' => '0'
                                        ]
                                        )->first();
               
                $default_reminder = 'general';
                if(empty($is_service_has_reminder))
                {                          
                    $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'type' => 'global',
                                        ]
                                        )->first();
                    
                }else
                {
                    $default_reminder = $is_service_has_reminder->activated_reminder;

                     /*************added below code on 17-jan-24**(18-march-24)***************/

                     /*$h_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
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


                    /*********added above code on 17-jan-24***(18-march-24)**********/

                      /*************added below code on 25-jan-24*****************/

                    /* $h_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
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
                    $is_service_has_reminder->checkup_period_frequency_type =  $h_reminder->checkup_period_frequency_type;*/

                   // dump('h_reminder');
                   // dump($h_reminder);


                    /*********added above code on 25-jan-24*****************************/
                   
                } //else 


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
                $check_general_recommanded_remidner = DB::table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_id,
                                        'activated_reminder' => 'general'
                                        ]
                                        )->first(['recommanded_service_id']);
                if(!empty($check_general_recommanded_remidner) && $check_general_recommanded_remidner->recommanded_service_id)
                      $service_id = $check_general_recommanded_remidner->recommanded_service_id;
                else  $service_id = $service_id;

                Log::info('Default setting');
                Log::info($default_reminder);
                Log::info($patient_id);
                /*END Check if that service is general and it is set reminder for another service*/

                if($default_reminder == 'general')
                {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    log::info("ReminderStatus-update_checkAndAddServiceReminder");
                    Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_updategeneralReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                }          
                else if($default_reminder == 'age')
                {
                   log::info('innnnnnnnnnnnnnnn default_reminder is age..........');

                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    log::info("ReminderStatus-updateAGE-_checkAndAddServiceReminder");
                    Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_updateageReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                } 
                else if($default_reminder == 'checkup')
                {
                    // Start Added code on 15-march-24 (18-march-24) for if only doctor set the control reminder
                    if(isset($is_doctor_set_reminder) && !empty($is_doctor_set_reminder))
                    { 
                        $a_date = explode(" ",$appointment_start_date);
                        $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                        log::info("ReminderStatus-updateCHECKUP-_checkAndAddServiceReminder");
                        Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                        // Log::info($is_service_has_reminder);
                        $this->_updatecontrolReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                    }//if isset is_doctor_set_reminder
                    // End Added code on 15-march-24 (18-march-24) for if only doctor set the control reminder

                }//else if      


            // }
        }  
    }//_checkAndAddServiceReminderU

    //Added on 22-nov-23
    public function _updategeneralReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
     //   dump('innnnnn _updategeneralReminder');

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
        Log::info("ReminderStatus-_generalReminder-");
        Log::info($reminder_array);

        //Added by swati 12-May-23===================================
        $firstReminderdate =  DB::table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
                                ->whereNull('deleted_at')  // added on 13-oct-23
                                ->first();
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
                 $is_exists = DB::table('patient_has_service_reminder')
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
                    $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($reminder_tmp);
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
            $parent_id = DB::table('patient_has_reminder')->insertGetId($temp);
            //Log::info($reactive_reminder);
        }
       
    }//_updategeneralReminder


    //Added on 22-nov-23
    public function _updatecontrolReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {   
       // dump($is_service_has_reminder);
       // dump('in _updatecontrolReminder');

        $reminder_array = [];        
        // 1st reminder
        $start_date = $start_date;
        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->checkup_period_controls,$is_service_has_reminder->checkup_period_frequency_type);

       //  dump('in value1_days');
       //  dump($value1_days);

        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

        // dump('in period_date');
        // dump($period_date);

        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->checkup_first_frequency,$is_service_has_reminder->checkup_first_frequency_type);

         //dump('in value3_days');
        // dump($value3_days);


        $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');

       // dump('in first_reminder');
       // dump($first_reminder);


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

        //dump($reminder_array);

        //Added on 04-Sep-23==========================================
        $firstReminderdate =  DB::table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
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
            $checkFuturRemidner=DB::table('patient_has_service_reminder')
                                    ->where('patient_id',$patient_id)
                                    ->where('service_id',$service_id)
                                    ->where('appointment_id','>',$appointment_id)
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('deleted_at')
                                    ->get();
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
                if(!empty($checkFuturRemidner) && count($checkFuturRemidner)>0) $reminder_tmp['status'] = 'deactivate';  

                $reminder_tmp['status'] = 'activate';  
                $reminder_tmp['type'] = 'control';
                $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;
                //Added by Shyam 14-01-22
                $is_exists = DB::table('patient_has_service_reminder')
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
                }
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
            $parent_id = DB::table('patient_has_reminder')->insertGetId($temp);
        }
    }//_updatecontrolReminder

    //added on 22-nov-23
    public function _updateageReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        log::info('innnnnn _updateageReminder function..........');

         //Added below query on 25-oct-23
         $getPatient = DB::table('patients')
                            ->select('birth_date','age') // added on 29-sept-23 added for db connection tenant
                            ->where('id',$patient_id)
                            ->whereNull('deleted_at')
                            ->first();                   


        $reminder_array = [];
        // 1st reminder
        $start_date = $start_date;
        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);

        log::info("value1_days ==>");
        log::info($value1_days);

        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

        log::info("period_date ==>");
        log::info($period_date);

        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->age_first_frequency,$is_service_has_reminder->age_first_frequency_type);

        log::info("value3_days =====>");
        log::info($value3_days);

        log::info("period_date ==>");
        log::info($period_date);

        $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');

        log::info("first_reminder ==>");
        log::info($first_reminder);

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
        $firstReminderdate = DB::table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
                                 ->whereNull('deleted_at')
                                ->first();

        log::info("firstReminderdate==>");
       // log::info($firstReminderdate);                        

        if(!empty($firstReminderdate)) 
            $first_remidner_date=$firstReminderdate->reminder_date;
        else $first_remidner_date=$start_date;
        $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_end_cycle,$is_service_has_reminder->age_end_cycle_frequency_type);  
        $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);
        $periodOneminusthird=($agePeriodDays-$value3_days);
        $finalDays=($endCycleDyas+$periodOneminusthird); 
        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');
        log::info($service_id);
        log::info($endcycle_date);
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
             $checkFuturRemidner=DB::table('patient_has_service_reminder')
                                    ->where('patient_id',$patient_id)
                                    ->where('service_id',$service_id)
                                    ->where('appointment_id','>',$appointment_id)
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('deleted_at')
                                    ->count();                       

              log::info('checkFuturRemidner===>');
              log::info($checkFuturRemidner);        
                       


            // echo $appointment_id."===<pre>";print_r($checkFuturRemidner);
            for($i=0;$i<count($reminder_array);$i++)
            { 
                 log::info('in for loop reminder_array===>');    


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

                log::info('date1======>');
               // log::info($date1);
                log::info('date2======>');
              //  log::info($date2);
                log::info('endCycleDays=====>');
                log::info($endCycleDyas);

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


                $getAppointmentExamination = DB::table('appointment_has_examinations')->where('examination_id',$service_id)->where('appointment_id',$appointment_id)->first();


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
                $is_exists = DB::table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)
                                ->where('appointment_id', $appointment_id)
                                ->where('service_id', $service_id)
                                ->where('reminder_date', $reminder_array[$i])
                                ->where('reminder_status', 'Set')
                                ->where('status', 'activate')
                                ->where('type', 'age')
                                ->whereNull('deleted_at')
                                ->count();      

                log::info('is_exists==>'.$is_exists);
                log::info($is_exists);

                if($is_exists == 0)
                {
                         log::info('if is_exists is 0==>');
                         log::info($reminder_tmp);

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
                        log::info('age===>');
                        log::info($age);
                        if($age == $is_service_has_reminder->age_from || ($age < $is_service_has_reminder->age_to && $age > $is_service_has_reminder->age_from))
                        {
                            log::info('after age in..........===>');
                            $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                        }
                        //Added above query on 25-oct-23

                }//if is exists is 0

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
                $parent_id = DB::table('patient_has_reminder')->insertGetId($temp);
            }//if reminder_id condition
           
        }
       
    }//_updateageReminder


    

    public function updateReminders_renamed_on_22_nov_23($patientID)
    {
        log::info("updateReminderste");
        $patient_id=base64_decode(base64_decode($patientID));
        $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')
                        ->where(
                            [                                       
                            'is_reminder_updated' => '1',
                            'type' =>'service'
                            ]
                        )->get();
            // echo "update<pre>";print_r($is_service_has_reminder);exit;
            if(!empty($is_service_has_reminder))
            {
                foreach($is_service_has_reminder as $key=>$value)
                {
                    $is_service_reminder_checked = DB::table('examinations')->where(
                        [
                        'id' => $value->service_id,
                        'show_as_reminder' => '1',
                            // 'status' => '1'
                        ])
                        ->whereNull('deleted_at')
                        ->first();
                    // print_r($is_service_reminder_checked);exit;
                    if(!empty($is_service_reminder_checked))
                    {
                        // $all_patinet_ids = DB::table('patient_has_service_reminder')
                        //     ->where('service_id',$value->service_id) 
                        //     ->where('patient_id',31117)
                        //     // ->where('appointment_id','!=',0)
                        //     ->groupby('patient_id','appointment_id')
                        //     ->get();
                        // DB::enableQueryLog();
                        $all_patinet_ids = DB::table('patient_has_service_reminder')
                                            ->leftjoin('patients','patients.id','=','patient_has_service_reminder.patient_id')
                                            ->where('service_id',$value->service_id)
                                            ->whereNull('patients.deleted_at')
                                            ->whereNull('patient_has_service_reminder.deleted_at')
                                            ->where('patient_id',$patient_id)
                                            // ->where('appointment_id','!=',0)
                                            ->groupby('patient_id','appointment_id')
                                            ->get(['patient_has_service_reminder.*']);
                        // echo "<pre>";print_r($all_patinet_ids);exit;
                        //dd(count($all_patinet_ids));
                        log::info($all_patinet_ids);
                        foreach($all_patinet_ids as $p_key=>$p_value)
                        {
                            //dd($p_value);
                            $ids = DB::table('patient_has_service_reminder')
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
                                    
                            DB::table('patient_has_service_reminder')
                            ->where('service_id',$p_value->service_id)
                            ->where('appointment_id',$p_value->appointment_id) 
                            ->where('patient_id',$p_value->patient_id)
                            ->whereNull('deleted_at')
                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                            $reactivateReminder =  DB::table('patient_has_reminder')
                            ->whereIn('service_reminder_id',$id_holder)
                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                            $appoitment_data = DB::table('appointment')
                                ->where('id',$p_value->appointment_id)->first();

                            $patinet_data = DB::table('patients')
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
                            //$this->_checkAndAddServiceReminder($value,$p_value->patient_id,$p_value->appointment_id,$ap_start_date,$data); 
                            // echo "<pre>";print_r($value);exit;
                            log::info($p_value->appointment_id);
                            if($p_value->appointment_id!=0)
                                $this->_checkAndAddServiceReminderU($p_value->patient_id,$p_value->service_id,$p_value->appointment_id,$ap_start_date,$data);
                            else
                                $this->_checkPatientAgeReminderU($p_value->patient_id,$value->service_id,$p_value->status);
                        }
                        $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')
                                        ->where([                                       
                                            'id' => $value->id
                                            ])
                                        ->update(['is_reminder_updated'=>'0']);
                    }
                }
            }
    }
    public function _checkPatientAgeReminderU_renamed_on_22_nov_23($patient_id='',$service_id='',$status)
    {
        
        $totalEntries = 0;
        $getPatient = $this->PatientsModel
                            ->where('id',$patient_id)
                            ->whereNull('deleted_at')
                            ->first();
        $getAgeServices = DB::table('preferred_channels_for_reminders_setting as pcr')
                            ->leftjoin('examinations','examinations.id','pcr.service_id')
                            ->where('examinations.show_as_reminder','1')
                            ->where('pcr.activated_reminder','age')
                            ->where('pcr.service_id',$service_id)
                            ->whereNull('pcr.deleted_at')
                            ->whereNull('examinations.deleted_at')
                            ->get(['examinations.id as service_id', 'pcr.notify_time', 'pcr.age_from', 'pcr.age_to']);
        foreach ($getAgeServices as $ke => $ser)
        {
            $checkRecord = $this->PatientsHasServiceReminderModel
                            ->where('patient_id', $patient_id)
                            ->where('appointment_id', 0)
                            ->where('service_id', $service_id)
                            ->where('reminder_status', 'Set')
                            //->where('status', 'activate')
                            ->where('type', 'age')
                            ->get(['id']);
            if(sizeof($checkRecord) == 0)
            {
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
                    $PatientsHasServiceReminder = new $this->PatientsHasServiceReminderModel;
                    $PatientsHasServiceReminder->patient_id      = $patient_id;
                    $PatientsHasServiceReminder->appointment_id  = 0;
                    $PatientsHasServiceReminder->service_id      = $service_id;
                    $PatientsHasServiceReminder->parent_id       = 0;
                    $PatientsHasServiceReminder->reminder_date   = date('Y-m-d ').$ser->notify_time.':00';
                    $PatientsHasServiceReminder->reminder_status = 'Set';
                    $PatientsHasServiceReminder->type            = 'age';
                    $PatientsHasServiceReminder->status          = $status;//'activate';
                    $PatientsHasServiceReminder->created_at      = date('Y-m-d H:i:s');
                    $PatientsHasServiceReminder->save();
                    $totalEntries++;
                }
            }
        }
        return $totalEntries;
    }//
    public function _checkAndAddServiceReminderU_renamed_on_22_nov_23($patient_id,$service_id,$appointment_id,$appointment_start_date,$data)
    {

        if($service_id!="" && $service_id > 0)
        {
            // foreach ($all_services as $service_key => $service_value) 
            // {
                $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_id,
                                         //'is_reminder_updated' => '0'
                                        ]
                                        )->first();
               
                $default_reminder = 'general';
                if(empty($is_service_has_reminder))
                {                          
                    $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
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
                    // $h_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
                    //                     [
                    //                     'type' => 'global',
                    //                     ]
                    //                     )->first(['holiday_reminder','checkup_number_of_interval','checkup_time_interval','checkup_first_frequency','checkup_new_frequency','checkup_period_controls','checkup_time_interval_frequency_type','checkup_first_frequency_type','checkup_new_frequency_type','checkup_period_frequency_type']);
                    // $is_service_has_reminder->checkup_number_of_interval =  $h_reminder->checkup_number_of_interval;
                    // $is_service_has_reminder->checkup_time_interval =  $h_reminder->checkup_time_interval;
                    // $is_service_has_reminder->checkup_first_frequency =  $h_reminder->checkup_first_frequency;
                    // $is_service_has_reminder->checkup_new_frequency =  $h_reminder->checkup_new_frequency;
                    // $is_service_has_reminder->checkup_period_controls =  $h_reminder->checkup_period_controls;
                    // $is_service_has_reminder->checkup_time_interval_frequency_type =  $h_reminder->checkup_time_interval_frequency_type;
                    // $is_service_has_reminder->checkup_first_frequency_type =  $h_reminder->checkup_first_frequency_type;
                    // $is_service_has_reminder->checkup_new_frequency_type =  $h_reminder->checkup_new_frequency_type;
                    // $is_service_has_reminder->checkup_period_frequency_type =  $h_reminder->checkup_period_frequency_type;
                    // Log::info(json_encode($is_service_has_reminder));
                    // dd('d');
                }  
                /*$is_doctor_set_reminder = db::table('patient_has_service_control_reminder_setting')->where(
                    ['patient_id' => $patient_id,
                    'appointment_id' => $appointment_id,
                    'service_id' => $service_id,
                    'status' => '1',
                    ]
                    )->first();

                if($is_doctor_set_reminder)
                {
                    $is_service_has_reminder->checkup_period_controls =  $is_doctor_set_reminder->control_interval;
                    $is_service_has_reminder->checkup_period_frequency_type =  $is_doctor_set_reminder->control_frequency;
                } */
                //Log::info('Default reminder');
                //Log::info(json_encode($default_reminder));
                //dd($default_reminder);
                /*Check if that service is general and it is set reminder for 
                 another service added by swati 19-Sep-22*/
                $check_general_recommanded_remidner = DB::table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_id,
                                        'activated_reminder' => 'general'
                                        ]
                                        )->first(['recommanded_service_id']);
                if(!empty($check_general_recommanded_remidner) && $check_general_recommanded_remidner->recommanded_service_id)
                      $service_id = $check_general_recommanded_remidner->recommanded_service_id;
                else  $service_id = $service_id;

                Log::info('Default setting');
                Log::info($default_reminder);
                Log::info($patient_id);
                /*END Check if that service is general and it is set reminder for another service*/

                if($default_reminder == 'general')
                {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    log::info("ReminderStatus-update_checkAndAddServiceReminder");
                    Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_updategeneralReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                }          
                else if($default_reminder == 'age')
                {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    log::info("ReminderStatus-updateAGE-_checkAndAddServiceReminder");
                    Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_updateageReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                } 
                else if($default_reminder == 'checkup')
                {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    log::info("ReminderStatus-updateCHECKUP-_checkAndAddServiceReminder");
                    Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    // Log::info($is_service_has_reminder);
                    $this->_updatecontrolReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                }                   
            // }
        }  
    }//

 
    public function _updategeneralReminder_renamed_on_22_nov_23($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
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
        Log::info("ReminderStatus-_generalReminder-".$patient_id);
        Log::info($reminder_array);

        //Added by swati 12-May-23===================================
        $firstReminderdate =  DB::table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
                                ->first();
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
                $is_exists = DB::table('patient_has_service_reminder')
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
                    $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($reminder_tmp);
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
            $parent_id = DB::table('patient_has_reminder')->insertGetId($temp);
            //Log::info($reactive_reminder);
        }
       
    }//

    public function _updateageReminder_renamed_on_22_nov_23($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
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
        $firstReminderdate =  DB::table('patient_has_service_reminder')
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
        log::info($service_id);
        log::info($endcycle_date);
        $reminder_id = 0;
        if(!empty($reminder_array) && count($reminder_array) > 0)
        {
            $checkFuturRemidner=DB::table('patient_has_service_reminder')
                                    ->where('patient_id',$patient_id)
                                    ->where('service_id',$service_id)
                                    ->where('appointment_id','>',$appointment_id)
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('deleted_at')
                                    ->get();
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
                if(!empty($checkFuturRemidner) && count($checkFuturRemidner)>0) $reminder_tmp['status'] = 'deactivate';  
                //   print_r($reminder_tmp);exit;
                $reminder_tmp['type'] = 'age';
                $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;
                $getAppointmentExamination = DB::table('appointment_has_examinations')->where('examination_id',$service_id)->where('appointment_id',$appointment_id)->first();
                //Added by swati 19-Apr-23============
                // if(!empty($getAppointmentExamination))
                //     $reminder_tmp['service_read_status'] = $getAppointmentExamination->create_from;
                // else $reminder_tmp['service_read_status'] = 'App';

                //Added by Shyam 14-01-22
                $is_exists = DB::table('patient_has_service_reminder')
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
                    $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($reminder_tmp);
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
            $parent_id = DB::table('patient_has_reminder')->insertGetId($temp);
        }
       
    }//

    public function _updatecontrolReminder_renamed_on_22_nov_23($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
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
        $firstReminderdate =  DB::table('patient_has_service_reminder')
                                ->where('patient_id',$patient_id)
                                ->where('service_id',$service_id)
                                ->where('appointment_id',$appointment_id)
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
            $checkFuturRemidner=DB::table('patient_has_service_reminder')
                                    ->where('patient_id',$patient_id)
                                    ->where('service_id',$service_id)
                                    ->where('appointment_id','>',$appointment_id)
                                    ->where('appointment_id','!=',0)
                                    ->whereNull('deleted_at')
                                    ->get();
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
                if(!empty($checkFuturRemidner) && count($checkFuturRemidner)>0) $reminder_tmp['status'] = 'deactivate';  

                $reminder_tmp['status'] = 'activate';  
                $reminder_tmp['type'] = 'control';
                $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;
                //Added by Shyam 14-01-22
                $is_exists = DB::table('patient_has_service_reminder')
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
                }
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





    public function checkPatientAgeReminder($patient_id='')
    {
        Log::info("in checkPatientAgeReminder========>");


        $totalEntries = 0;
        $getAllPatients = $this->PatientsModel->where('status','1')
                                ->where('reminder_active','1')
                                ->whereNull('deleted_at');
        if(isset($patient_id) && $patient_id != '')
        {
            $getAllPatients = $getAllPatients->where('id',$patient_id);
        }
        $getAllPatients = $getAllPatients->orderby('id', 'desc')
                                ->get(['id as patient_id', 'patients.*']); 

         Log::info("in getAllPatients========>");       
         Log::info($getAllPatients);                        

        $getAgeServices = DB::table('preferred_channels_for_reminders_setting as pcr')
                            ->leftjoin('examinations','examinations.id','pcr.service_id')
                            ->where('examinations.show_as_reminder','1')
                            ->where('pcr.activated_reminder','age')
                            ->whereNull('pcr.deleted_at')
                            ->whereNull('examinations.deleted_at')
                            ->get(['examinations.id as service_id', 'pcr.notify_time', 'pcr.age_from', 'pcr.age_to']);

         Log::info("getAgeServices====>");
         Log::info($getAgeServices);                        
                    
        foreach ($getAllPatients as $k => $pat)
        {
            foreach ($getAgeServices as $ke => $ser)
            {
                $checkRecord = $this->PatientsHasServiceReminderModel
                                ->where('patient_id', $pat->patient_id)
                                ->where('service_id', $ser->service_id)
                                ->where('reminder_status', 'Set')
                                ->where('status', 'activate')
                                ->where('type', 'age')
                                ->get(['id']);

                 Log::info(" in getAgeServices  loop= checkRecord===>");
                 
                 Log::info($checkRecord);                

                if(sizeof($checkRecord) == 0)
                {
                    if($pat->birth_date) {
                        $from = new DateTime($pat->birth_date);
                        $to   = new DateTime('today');
                        $age =  $from->diff($to)->y;
                    }
                    else {
                        $age =  $pat->age;
                    }

                     Log::info(" age===>");
                     Log::info($age); 

                    if($age == $ser->age_from || ($age < $ser->age_to && $age > $ser->age_from))
                    {
                        Log::info(" in age criteria.........===>");

                        $PatientsHasServiceReminder = new $this->PatientsHasServiceReminderModel;
                        $PatientsHasServiceReminder->patient_id      = $pat->patient_id;
                        $PatientsHasServiceReminder->appointment_id  = 0;
                        $PatientsHasServiceReminder->service_id      = $ser->service_id;
                        $PatientsHasServiceReminder->parent_id       = 0;
                        $PatientsHasServiceReminder->reminder_date   = date('Y-m-d ').$ser->notify_time.':00';
                        $PatientsHasServiceReminder->reminder_status = 'Set';
                        $PatientsHasServiceReminder->type            = 'age';
                        $PatientsHasServiceReminder->status          = 'activate';
                        $PatientsHasServiceReminder->created_at      = date('Y-m-d H:i:s');
                        $PatientsHasServiceReminder->save();
                        $totalEntries++;
                        //Added by Swati 1-Aug-2022================================
                        
                       /* $mobileId = DB::table('patient_has_device')
                        ->where('patient_id',$pat->id)
                        ->get(['device_id']);
                        log::info("remider-admin");
                        $servicedata = DB::table('examinations')
                                    ->where('id',$ser->service_id)
                                    ->first(['name as service_name','id as service_id']);
                        
                        if(!empty($mobileId) && count($mobileId))
                            $this->_sendPushNotification($mobileId,$pat,$servicedata);
                        
                        $channel = DB::table('preferred_channels_for_reminders_setting')
                                               ->where('type','global')
                                               ->select('choice_of_channels')
                                               ->first();
                        log::info($channel->choice_of_channels);
                        if($channel->choice_of_channels == 'sms')
                        {
                            log::info($channel->choice_of_channels);
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
                                $this->_sendSmsReminder($phone_no,$pat,$servicedata);
                            }
                            elseif (!empty($pat->email) && $pat->sendMail==1)
                            {
                                $this->_sendMailReminder($pat,$servicedata);
                            }
                        }
                        elseif($channel->choice_of_channels == 'email')
                        {
                            if (!empty($pat->email) && $pat->sendMail==1)
                            {
                                $this->_sendMailReminder($pat,$servicedata);
                            }
                            //elseif (!empty($pat->mobile_no) && $pat->sendSMS==1)
                            //{
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
                                $this->_sendSmsReminder($phone_no,$pat,$servicedata);
                            //}
                        }*/
                        //End========================================================
                    }
                }
            }
        }
        return $totalEntries;
    }

    public function getPatients(Request $request)
    {
        $var = $request->get('keyword');  
        $birthdateKey = $request->get('birthdateKey');  
        $popup = $request->get('popup');  
        $edit = $request->get('edit');  

        //dd(date('Y-m-d',strtotime($birthdateKey)));
        // dump('eventStore'); 
        // dd($request->all());
        // $users = DB::table('users')
        //         ->where('name', 'like', 'T%') 
        //         ->get(); 
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND');   

        try{

            $firstLastName = explode(" ", $var);

            //commented below code on 10-nov-23
            /*if(array_key_exists(1, $firstLastName)){
                $family_name = $firstLastName[0];
                $first_name  = $firstLastName[1];
            }else{
                $family_name = $firstLastName[0];
                $first_name = '';
            }


            $collection = collect([]);     

            $collection = $this->PatientsModel
                             ->where('family_name', 'LIKE', $family_name. '%')
                             ->whereStatus(1);

            if(!empty($first_name)){
                // dd($first_name);
                $collection = $collection
                             ->where('first_name', 'LIKE', $first_name . '%');
            }
            if(!empty($var))
            {
                $collection = $collection->orWhere('family_name', 'LIKE', $var . '%');
            }*/
            //end commented below code on 10-nov-23 

            

            //start patient search code added below code in 16-nov-23

            /*if(array_key_exists(1, $firstLastName)){
            $first_name   = $firstLastName[0];
            $family_name  = $firstLastName[1];
            }else{
                $family_name = $firstLastName[0];
                $first_name = '';
            }

            $collection = collect([]);     

            $collection = $this->PatientsModel
                            // ->whereRaw("MATCH(patients.first_name) AGAINST('".$first_name."')")
                             ->whereStatus(1);

             if(array_key_exists(1, $firstLastName)){
                 $key[0]     = $firstLastName[0];
                 $key[1]     = $firstLastName[1];
                $collection = $collection
                              ->whereRaw("MATCH(patients.first_name) AGAINST('".$key[0]."')")  
                              ->whereRaw("MATCH(patients.family_name) AGAINST('".$key[1]."')");
            }else{

                 $key[0]     = $firstLastName[0];   
                 $collection = $collection
                              ->whereRaw("MATCH(patients.first_name) AGAINST('".$key[0]."')")  
                              ->orwhereRaw("MATCH(patients.family_name) AGAINST('".$key[0]."')");
            } */               
   
            //end patient search code added above code in 16-nov-23


            //start patient search code added below code in 29-nov-23
            if(array_key_exists(1, $firstLastName)){
                $first_name   = $firstLastName[0];
                $family_name  = $firstLastName[1];
            }else{
                $first_name = $firstLastName[0];
                $family_name = '';
            }

            $collection = collect([]);     

            $collection = $this->PatientsModel
                             //->whereRaw("MATCH(patients.first_name) AGAINST('".$first_name."')")
                            //  ->where('first_name', 'LIKE', $first_name . '%')
                             ->where('first_name', 'LIKE', '%' .$first_name . '%')
                             ->whereNull('deleted_at')
                             ->whereStatus(1);

            if(!empty($family_name)){
                $collection = $collection
                             // ->whereRaw("MATCH(patients.family_name) AGAINST('".$family_name."')");
                            //  ->where('family_name', 'LIKE', $family_name. '%');
                            ->where('family_name', 'LIKE', '%' . $family_name. '%'); //added by vijay 13/9/2024
            }
            if(!empty($var))
            {
                // $collection = $collection->orWhere('family_name', 'LIKE', $var . '%');
                $collection = $collection->orWhere('family_name', 'LIKE', '%' . $var . '%'); //added by vijay 13/9/2024
            }
            //end patient search code added above code in 29-nov-23



            if(!empty($birthdateKey)){
                // dd($first_name);
                $collection = $collection
                             ->whereDate('birth_date', '=', date('Y-m-d',strtotime($birthdateKey)));
            }
            $collection = $collection
                             ->get(['id', 'email', 'first_name', 'family_name','birth_date','insurance_number']);


             

            if((!empty($collection) && sizeof($collection) > 0)){
                $message = __('api.DATA_FOUND_SUCCESS');

                if(!empty($popup) && $popup==1){

                    $select_id = 'patient_id';
                    if(!empty($edit) && $edit==1){
                        $select_id = 'patient_idedit';
                    }

                    $data = '<select class="form-control" id ="'.$select_id.'" name="'.$select_id.'">';
                    $data .= '<option  value="" title="">PatientIn wählen</option>';
                    foreach ($collection as $key => $value) {
                        $option = $value['first_name'].' '.$value['family_name'];
                        $braket_string = '';
                        $b_date ='';
                        if(!empty($value['birth_date']))
                        {
                            $braket_string .= date('d-m-Y',strtotime($value['birth_date']));
                            $b_date = date('d-m-Y',strtotime($value['birth_date']));                            
                        }
                        if(!empty($value['insurance_number']))
                        {
                            if(!empty($braket_string))
                            {
                                $braket_string .=", ".$value['insurance_number'];  
                            }else
                            {
                                $braket_string .= $value['insurance_number'];  
                            }                                                      
                        }
                        if(!empty($braket_string))
                        {
                            $option .=" (".$braket_string.")";
                        }
                      
                        $data .= '<option  value="'.$value['id'].'" title="'.($b_date ?? '').'">'.$option.'</option>';
                    }
                    $data .='</select>'; 
                }else{

                    $data = '<select class="form-control" id ="getPatientsData">';//onchange="getPatientsData()"
                    foreach ($collection as $key => $value) {
                    // value="'.$value['id'].'"

                        $patientName = $value['first_name'].' '.$value['family_name'];
                        /*if(empty($value['email'])){
                            $value['email'] = str_replace(" ", "@",strtolower($patientName));
                        }*/
                        $data .= '<option  value="'.$patientName.'">'.$patientName.'</option>';
                    }
                    $data .='</select>'; 
                }

            }else{
                $message = __('api.ERR_NOT_FOUND');
            } 
        }
        catch(\Exception $e) {
            $message = __('admin.ERR_SOMETHING_WRONG'); 
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
        }
       
       $result = [
            "data" => $data
        ];

        return response()->json($result);  
    }

    public function getDoctors(Request $request){ 
        // log::info($request->all());
        $var = $request->get('keyword');  
        //dd('getDoctors'); 
        
        $data       = []; 
        $message    = __('admin.ERR_NOT_FOUND');   

        try{
            $collection = collect([]);      
            $collection = $this->AdminUserModel
                                ->where('first_name', 'like', $var . '%')
                                ->orWhere('last_name', 'like', $var . '%')
                                ->whereStatus(1)
                                ->whereHas('roles',function($query){
                                    $query->where('name', 'doctor');
                                })
                                ->get(['id', 'email', 'first_name', 'last_name']);;
                             // dd($collection);

            if((!empty($collection) && sizeof($collection) > 0)){
                $message = __('admin.DATA_FOUND_SUCCESS');
                //$data  = $collection;
                $data = '<select class="form-control" id ="getDoctorsData">';//onchange="getDoctorsData()"
                foreach ($collection as $key => $value) {
                // value="'.$value['id'].'"
                    $data .= '<option value="'.$value['first_name'].' '.$value['last_name'].'">'.$value['first_name'].' '.$value['last_name'].'</option>';
                }
                $data .='</select>'; 

            }else{
                $message = __('admin.ERR_NOT_FOUND');
                $data = '<select class="form-control" id ="getDoctorsData">';
                $data .= '<option value="">'.$message.'</option>';
                $data .='</select>'; 
            } 
        }
        catch(\Exception $e) {
            $message = __('admin.ERR_SOMETHING_WRONG'); 
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
        }
       
       $result = [
            "data" => $data
        ];
        return response()->json($result);  
    }

    public function getEvents_old(Request $request)
    {
        $start_date = $request->start;
        $end_date = $request->end;
        $modelQuery = $this->AppointmentModel
                    ->with(['assignedPatient','assignedDoctor','assignedAppointmentType']);
                    // ->where('id',1)
                    // ->get();
        if (!empty($start_date) && !empty($end_date))
        {
            if (strtotime($start_date)==strtotime($end_date))
            {
                $modelQuery  = $modelQuery->whereDate('appointment.start_date','=',$start_date);
            }
            else {
                $modelQuery = $modelQuery->whereDate('appointment.start_date','>=',$start_date)
                                         ->whereDate('appointment.end_date','<=',$end_date);
            }
        }
        else if(!empty($start_date) && empty($end_date))
        {
            $modelQuery = $modelQuery->whereDate('appointment.start_date','>=',$start_date);
        }
        else if(empty($start_date) && !empty($end_date))
        {
            $modelQuery = $modelQuery->whereDate('appointment.end_date','<=',$end_date);
        }
        $appointments = $modelQuery->get();
        $data=[];
        if(!empty($appointments) && count($appointments)>0)
        {
            foreach ($appointments as $key=>$appointment)
            {
                $data[$key]['title'] =  $appointment->notes;
                $data[$key]['start'] = date("Y-m-d H:i",strtotime($appointment->start_date));
                $data[$key]['end']   = date("Y-m-d H:i",strtotime($appointment->end_date));
                // $data[$key]['backgroundColor'] = '#00c0ef';
                $data[$key]['backgroundColor'] = $appointment->assignedDoctor->color?$appointment->assignedDoctor->color:'#00c0ef';
                $data[$key]['allDay'] = false;
                $data[$key]['patient_id'] = $appointment->assignedPatient->id;
                // $events[0]['url']    = url('events/edit/');
                $content = "";
                $content .= "<p><strong>".$this->patientText.":</strong> ".$appointment->assignedPatient->first_name." ".$appointment->assignedPatient->family_name." </p>";
                $content .= "<p><strong>".$this->doctorText.":</strong> ".$appointment->assignedDoctor->first_name." ".$appointment->assignedDoctor->last_name." </p>";
                $content .= "<p><strong>".$this->appointmentText.":</strong> ".$appointment->assignedAppointmentType->name." </p>";
                $content .= "<p><strong>".$this->startDateText.":</strong> ".date("F d, Y H:i",strtotime($appointment->start_date))." </p><strong>".$this->endDateText.":</strong> ".date("F d, Y H:i",strtotime($appointment->end_date))." </p>";
                $content .= "<p><strong>".$this->notesText.":</strong> ".$appointment->notes." </p>";
                $data[$key]['description'] = $content;
            }
        }
        echo json_encode($data);
        exit();
    }

    public function _getAuthenticationForToken()
    {
       // Log::info('in _getAuthenticationForToken');

       // dd('_getAuthenticationForToken');
        self::_accessTokenFile();
        // $rurl = secure_url('Admin\DashboardController@_getAuthenticationForToken');
        $rurl = action('Admin\DashboardController@_getAuthenticationForToken');

       //  Log::info($rurl);

       // $rurl ='https://dev.eluminousdev.com/trtcle/admin/dashboard/calendar/oauth';
        //dd($rurl);
        $this->client->setRedirectUri($rurl);
        // dd($this->client->isAccessTokenExpired());
        // If there is no previous token or it's expired.
        if ($this->client->isAccessTokenExpired()) 
        {
           //  Log::info("in if condition");

            // Refresh the token if possible, else fetch a new one.
            if ($this->client->getRefreshToken()) 
            {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            }
            else
            if (!isset($_GET['code'])) 
            {
                $authUrl = $this->client->createAuthUrl();
                $filtered_url = filter_var($authUrl, FILTER_SANITIZE_URL);
                return redirect($filtered_url);
            } 
            else
            {
                // Exchange authorization code for an access token.
                $accessToken =$this->client->fetchAccessTokenWithAuthCode($_GET['code']);
                $this->client->setAccessToken($accessToken);
            }

            // Save the token to a file.
            if (!file_exists(dirname($this->tokenPath))) 
            {
                mkdir(dirname($this->tokenPath), 0700, true);
            }

            file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
        }//close if
        
    }

    public function _accessTokenFile()
    {
      //  Log::info('in _accessTokenFile');

        if (file_exists($this->tokenPath)) 
        {
           // Log::info('in file exists _accessTokenFile');

            $accessToken = json_decode(file_get_contents($this->tokenPath), true);

          //  Log::info($accessToken);
            
            $this->client->setAccessToken($accessToken);
        }
        // Log::info('before client return');
         //Log::info($this->client);
        return $this->client;
    }

    public function eventStore_old(Request $request)
    {
       // Log::info('in eventStore');

        // dd($request->all());
        self::_getAuthenticationForToken();
        $service = new Google_Service_Calendar($this->client);
        if(empty(Config('google_calendar_id')))
        {
            $calendarId = 'primary';
        }
        else {
            $calendarId = Config('google_calendar_id');
        }

       //  Log::info($calendarId);

        /*$evntDateTime= date("Y-m-d",strtotime($request->eventDate));
        //get est time and attach it with  the selected date by user
        $defaultime= self::_getDefaultTimeZone();
        $time= date("H:i:s",strtotime($defaultime->updated_time));
        //$time= date("H:i:s",time());
        list($year,$month,$day) = explode("-", $request->eventDate);
        list($hour,$min,$sec) = explode(":", $time);*/
        /*$startDateTime   = Carbon::create($year,$month,$day, $hour,$min,$sec);
        $endDateTime     = Carbon::create($year,$month,$day, 23, 59, 59);
        // $time = mktime($hour, $min, $sec, $month, $day, $year);
        $startDateTime = date('Y-m-d',strtotime($startDateTime))."T".date('H:i:s',strtotime($startDateTime)).'-05:00'; 
        $endDateTime   = date('Y-m-d',strtotime($endDateTime))."T".date('H:i:s',strtotime($endDateTime)).'-05:00';
        $startDateTime   = '2020-01-11T06:50:40-07:00';
        $endDateTime   = '2020-01-11T07:59:59-07:00';
        $summary = 'test1';
        $description = '<p><strong>Patient Name:</strong> Patient2 a </p><p><strong>Doctor Name:</strong> test test </p><p><strong>Appointment Type:</strong> Findings meeting </p><p><strong>Start Date Time:</strong> January 27, 2020 02:30 </p><strong>End Date Time:</strong> January 27, 2020 02:45 </p><p><strong>Notes:</strong> notes test </p>';*/

        $summary = $request->summary;
        $description = $request->description;
        $patient_id = $request->patient_id;
        $patient_email = $request->patient_email;
        $patient_name = $request->patient_name;
        $doctor_email = $request->doctor_email;
        $color_id       = $request->color_id;
        $startDateTime = date('Y-m-d',strtotime($request->startDateTime))."T".date('H:i:s',strtotime($request->startDateTime));//.'-00:00';
        $endDateTime = date('Y-m-d',strtotime($request->endDateTime))."T".date('H:i:s',strtotime($request->endDateTime));//.'-00:00';
        //#DF1980 Pink color
        //dd($startDateTime,$endDateTime);
        $event = new Google_Service_Calendar_Event([
            'summary'       => $summary,
            'description'   => $description,
            'start'         => ['dateTime' => $startDateTime,'timeZone'=>'Europe/Berlin'],
            'end'           => ['dateTime' => $endDateTime,'timeZone'=>'Europe/Berlin'],
            'reminders'     => ['useDefault' => true],
            'backgroundColor' => "#DF1980",
            'colorId'       =>  $color_id,
            // 'colorRgbFormat' => true,
            //'patient_id' => 2,
            //"attendees"=>$attendees
        ]);
        $event->setColorId($color_id);
        //attendee
        // if ($request->has('attendee_name')) {
           /* if(!empty($patient_name)){
                $attendee->setDisplayName($patient_name);
                $patient_email = str_replace(" ", "@", $patient_name);
            }
            if(!empty($patient_email)){
                $attendee->setEmail($patient_email);
            }elseif(!empty($patient_name)){
                $patient_email = str_replace(" ", "@", $patient_name);
                $attendee->setEmail($patient_email);
            }*/
            $attendees = [];
            $attendee = new \Google_Service_Calendar_EventAttendee();
            if(empty($patient_email) || $patient_email=='')
            {
                $patient_email = str_replace(" ", "@", $patient_name);
                $attendee->setDisplayName($patient_name);
            }
            $attendee->setEmail($patient_email);
            $attendee->setId($patient_id);
            $attendee_doctor = new \Google_Service_Calendar_EventAttendee();
            $attendee_doctor->setEmail($doctor_email);
            $attendee_doctor->setOrganizer(true);
            $attendees[0] = $attendee;
            $attendees[1] = $attendee_doctor;
            // dd($attendees);
            /*foreach ($attendee_names as $index => $attendee_name) {
                $attendee_email = $attendee_emails[$index];
                if (!empty($attendee_name) && !empty($attendee_email)) {
                   //
                }
            }*/
            // $event->attendees = $attendees;
        //  }
        // dd($event);
        try{

           //  Log::info("in event create");

            // $service->calendarList->methods['insert']['parameters']['colorRgbFormat']=true;
            //dd($service);
            /*$options = [
                        'json' => [
                            "fruit" => "apple"
                           ]
                        ];
            $response = $client->post("/post", $options);*/
            $results = $service->events->insert($calendarId, $event);
            //dump($results);
            $this->JsonData['status']   = __('admin.RESP_SUCCESS');
            $this->JsonData['data']      = $results;
            $this->JsonData['msg']      = 'Google event created successfully.';
           // dd($this->JsonData);
        }
        catch(Google_Service_Exception $e)
        {
            
          //  Log::info("in catch event create");
            
            //return redirect()->route('oauthCallback');
            $msg =json_decode($e->getMessage()); 
            //dd($msg,$msg->error->message);
            $this->JsonData['status']   = __('admin.RESP_ERROR');
            $this->JsonData['msg']      = $msg->error->message; 
        }
        // dump($this->JsonData);
        return response()->json($this->JsonData);
    }

    public function eventStore(Request $request)
    {
        
        $event = Event::create([
            'summary' => $request->summary,
            'description' => $request->description,
            'patient_id' => $request->patient_id,
            'patient_email' => $request->patient_email,
            'patient_name' => $request->patient_name,
            'doctor_email' => $request->doctor_email,
            'color_id' => $request->color_id,
            'start_date_time' => $request->startDateTime,
            'end_date_time' => $request->endDateTime,
        ]);

        // Return a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $event,
            'msg' => 'Event created successfully.'
        ]);
    }

    public function appointmentIdUpdateInEvent($eventId, $appointmentId)
    {
        $updatedEvent = Event::find($eventId);
        $updatedEvent->appointment_id = $appointmentId;
        $updatedEvent->save();
    }

    public function eventUpdate(Request $request)
    {
       
        try {
            $eventId = $request->eventId;
            if (is_numeric($eventId)) {
                $eventId = $request->eventId;
            } else {
                $getEventId = $this->AppointmentModel->where('google_event_id', $eventId)->first();
                $eventId = $getEventId->event_id;
            }
            $patient_email = $request->patient_email;
            $patient_name = $request->patient_name;

            if (empty($patient_email) || $patient_email == '') {
                $patient_email = str_replace(" ", "@", $patient_name);
            }

            $updatedEvent = Event::find($eventId);
            $updatedEvent->summary = $request->summary;
            $updatedEvent->description = $request->description;
            $updatedEvent->patient_email = $patient_email;
            $updatedEvent->patient_id = $request->patient_id;
            $updatedEvent->patient_name = $request->patient_name;
            $updatedEvent->doctor_email = $request->doctor_email;
            $updatedEvent->color_id = $request->color_id;
            $updatedEvent->start_date_time = $request->startDateTime;
            $updatedEvent->end_date_time = $request->endDateTime;
            $updatedEvent->save();

            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            $this->JsonData['data'] = $updatedEvent;
            $this->JsonData['msg'] = 'Google event updated successfully.';
        } catch (\Exception $e) {
            $msg = json_decode($e->getMessage());
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg'] = $msg->error->message;
        }

        // dump($this->JsonData);

        return response()->json($this->JsonData);

    }
    public function eventUpdate_old(Request $request)
    {
        self::_getAuthenticationForToken();
        $service = new Google_Service_Calendar($this->client);

        if(empty(Config('google_calendar_id')))
            $calendarId = 'primary';
        else
            $calendarId = Config('google_calendar_id');

        $eventId        = $request->eventId;
        $summary        = $request->summary;
        $description    = $request->description;
        $color_id       = $request->color_id;
        $patient_email  = $request->patient_email; 
        $patient_name = $request->patient_name; 
        $doctor_email   = $request->doctor_email; 
        $startDateTime  = date('Y-m-d',strtotime($request->startDateTime))."T".date('H:i:s',strtotime($request->startDateTime));//.'-05:30'; 
        $endDateTime    = date('Y-m-d',strtotime($request->endDateTime))."T".date('H:i:s',strtotime($request->endDateTime));//.'-05:30'; 


        if(empty($patient_email) || $patient_email==''){
            $patient_email = str_replace(" ", "@", $patient_name);
        }
       
        /*$startDateTime = Carbon::parse($request->start_date)->toRfc3339String();
        $eventDuration = 30; //minutes
        if ($request->has('end_date')) {
            $endDateTime = Carbon::parse($request->end_date)->toRfc3339String();
        } else {
            $endDateTime = Carbon::parse($request->start_date)->addMinutes($eventDuration)->toRfc3339String();
        }*/
        // retrieve the event from the API.
        //$event = $service->events->get('primary', $eventId);
        $event = $service->events->get($calendarId, $eventId);
        $event->setSummary($summary);
        $event->setDescription($description);
        //start time
        $start = new Google_Service_Calendar_EventDateTime();
        $start->setDateTime($startDateTime);
        $start->setTimeZone('Europe/Berlin');
        $event->setStart($start);
        //end time
        $end = new Google_Service_Calendar_EventDateTime();
        $end->setDateTime($endDateTime);
        $end->setTimeZone('Europe/Berlin');

        $event->setEnd($end);

        $event->setColorId($color_id);
        // dd($patient_email);
        /*$event->setAttendees(
                [
                    new \Google_Service_Calendar_EventAttendee(['email' => $patient_email]),
                    new \Google_Service_Calendar_EventAttendee(['email' => $doctor_email]),
                ]
            );*/

        try{
            
            //$updatedEvent = $service->events->update('primary', $event->getId(), $event);
            $updatedEvent = $service->events->update($calendarId, $event->getId(), $event);
            $this->JsonData['status']   = __('admin.RESP_SUCCESS');
            $this->JsonData['data']      = $updatedEvent;
            $this->JsonData['msg']      = 'Google event updated successfully.'; 
           // dd($this->JsonData);
        }catch(Google_Service_Exception $e){
            // dump($e);
            // dd($e->getMessage());
            //return redirect()->route('oauthCallback');     
            $msg =json_decode($e->getMessage()); 
            //dd($msg,$msg->error->message);
            $this->JsonData['status']   = __('admin.RESP_ERROR');
            $this->JsonData['msg']      = $msg->error->message; 
        }

       // dump($this->JsonData);
       
        return response()->json($this->JsonData);

    }

    public function eventDelete(Request $request)
    {
       try {
            $eventId = $request->eventId;
            if($eventId){
                $deletedEvent = Event::find($eventId);

                if ($deletedEvent) {
                    $deletedEvent->delete();
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['data'] = $deletedEvent;
                    $this->JsonData['msg'] = 'Google event deleted successfully.';
                }else{
                    $this->JsonData['status'] = __('admin.RESP_ERROR');
                    $this->JsonData['msg'] = 'Event not found.';
                }
            }else{
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['msg'] = 'Google event deleted successfully.';
            }
        } catch (\Exception $e) {
            $msg = json_decode($e->getMessage());
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg'] = $msg;
        }
        return response()->json($this->JsonData);
    }
    public function eventDelete_old(Request $request)
    {
        self::_getAuthenticationForToken();
        $service = new Google_Service_Calendar($this->client);
        if(empty(Config('google_calendar_id'))) {
            $calendarId = 'primary';
        }
        else {
            $calendarId = Config('google_calendar_id');
        }
        $eventId = $request->eventId;
        try
        {
            $deletedEvent = $service->events->delete($calendarId, $eventId);
            $this->JsonData['status']   = __('admin.RESP_SUCCESS');
            $this->JsonData['data']     = $deletedEvent;
            $this->JsonData['msg']      = 'Google event deleted successfully.';
        }
        catch(Google_Service_Exception $e)
        {
            $msg =json_decode($e->getMessage()); 
            $this->JsonData['status']   = __('admin.RESP_ERROR');
            $this->JsonData['msg']      = $msg->error->message; 
        }
        return response()->json($this->JsonData);
    }

    public function getResourceId()
    {
        $resource_val = [];
        try{
                $doctor = $this->AdminUserModel
                        ->whereHas('roles',function($query){
                        $query->where('name', 'doctor');
                        })
                        ->get(); 
                $cnt = 0;
                foreach ($doctor as $key => $event)
                {
                    //$out[$last_index] = $event['first_name'].' '.$event['last_name'];
                    $name = $event['first_name'].' '.$event['last_name'];
                    $resource_val[$cnt]["id"] = ($name);
                    $resource_val[$cnt]["title"] = $event['first_name'].' '.$event['last_name'];
                    $resource_val[$cnt]["type1"] = count($doctor)-1;
                    $resource_val[$cnt]["type2"] = $cnt;
                    $cnt++;
                    //$last_index++; 
                }
                // =============================================
                // $resource_val = [];
                // $resource_arr = array_unique($out);
                // $cnt = 0;
                // foreach ($resource_arr as $key => $value) 
                // {
                //     if(!empty($value))
                //     {
                //         $resource_val[$cnt]["id"] = $value;
                //         $resource_val[$cnt]["title"] = $value;
                //         $cnt++;
                //     }
                   
                // }
            }catch(Google_Service_Exception $e){
                //
            }
        return json_encode($resource_val);
    }

    public function getEvents(Request $request)
    {
        // new data
        $patient_name = trim($request->patient_name);
        $datetime1 = new DateTime($request->start);
        $datetime2 = new DateTime($request->end);
        $interval = $datetime1->diff($datetime2);
        $days = $interval->format('%a');//now do whatever you like with $days
        // self::_getAuthenticationForToken();
        // $service = new Google_Service_Calendar($this->client);
        // if(empty(Config('google_calendar_id')))
        // {
        //     $calendarId = 'primary';
        // }
        // else {
        //     $calendarId = Config('google_calendar_id');
        // }
        $out=[];
        $last_index = 0;
        $google_dates=[];
        $start_date = $request->start;
        $end_date = $request->end;
        $colors = $this->GoogleColorsModel->get();
        $google_color = [];
        foreach ($colors as $color)
        {
            $google_color[$color->id] = $color->code;
        }
        if(!empty($start_date) && !empty($end_date))
        {
            // Print the next 10 events on the user's calendar.
            $optParams = array(
                'maxResults' => 3000,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => date("c", strtotime(date('Y-m-d H:i:s',strtotime($start_date)).'0 days')),
                'timeMax' => date("c", strtotime(date('Y-m-d H:i:s',strtotime($end_date)).'0 days')),
                'timeZone'=>'Europe/Berlin'
            );
        }
        else {
            $optParams=array();
        }
        try
        {
            $datetime1 = new DateTime($request->start);
            $datetime2 = new DateTime($request->end);
            $interval = $datetime1->diff($datetime2);
            $days = $interval->format('%a');//now do whatever you like with $days
            // if(!empty($optParams) && count($optParams)>0)
            // {
            //     $results = $service->events->listEvents($calendarId, $optParams);
            // }
            // else {
            //     $results = $service->events->listEvents($calendarId);
            // }
            // $events = $results->getItems();

            $event_start_date = Carbon::parse($request->start)->format('Y-m-d');
            $event_end_date = Carbon::parse($request->end)->format('Y-m-d');
            // $query = Event::whereBetween('start_date_time', [$event_start_date, $event_end_date]);
            $query = Event::whereBetween('start_date_time', [$event_start_date, $event_end_date])->whereHas('appointments', function ($query) {
                $query->where('is_app_booked', 1);
            })->with(['appointments', 'patient', 'doctor']);
            $events = $query->get();
            $date = '';
            $last_index = 0;
            foreach ($events as $key => $event)
            {
                // $search_string = explode("</p><p><strong>", $event->getDescription());
                $search_string = explode("</p><p><strong>", $event->description);
                $search_string = trim(str_replace("<p><strong>Patient:</strong>", "", $search_string[0]));
                $event_array_new[]=array();
                if((!empty($patient_name) && strpos(strtolower($search_string), strtolower($patient_name)) !== false) || (empty($patient_name)))
                {
                    //This IF condition Added by Shyam 01-02-22
                    // $checkEventId = $this->AppointmentModel->where('event_id', $event->id)->get(['id']);
                    // if(!empty($checkEventId) && sizeof($checkEventId) > 0)
                    // {
                        
                        // $ganny_id = $this->AppointmentModel
                        //                 ->leftjoin('patients', 'patients.id', 'appointment.patient_id')
                        //                 ->whereNull('patients.deleted_at')   //added on 5-oct-23
                        //                 ->where('appointment.is_app_booked', 1) // added by vijay 16/4/2024
                        //                 ->leftjoin('users as doctors', 'doctors.id', 'appointment.doctor_id')
                        //                 ->where('event_id', $event->id)
                        //                 ->first(['appointment.id','appointment.google_event_id', 'patients.id as patient_id', 'appointment.doctor_id', 'doctors.first_name', 'doctors.last_name','doctors.google_color_id']);

                        //if not empty condition added on 5-oct-23               
                        // if(!empty($ganny_id))
                        //  {                  
                            // $date = date("Y-m-d",strtotime($event->getStart()->getDateTime()));
                            $date = date("Y-m-d", strtotime($event->start_date_time));
                            $pushData = false;
                            // $out[$last_index]['id'] = $ganny_id->google_event_id; //$event->id; change by vijay on 3-9-24
                            $out[$last_index]['id'] = $event->appointments['0']['google_event_id']; 
                            
                            // $title                  =   ucfirst($event->getSummary());
                            $title = ucfirst($event->summary);
                            if(strlen($title) > 100)
                            {
                                $out[$last_index]['title']     =  substr($title, 0, 100)."...";
                            }
                            else {
                                $out[$last_index]['title']     =  $title;
                            }
                            // $out[$last_index]['description']   =   ucfirst($event->getDescription());
                            $out[$last_index]['description'] = ucfirst($event->description);
                            $out[$last_index]['date']          =   strtotime($date)."000";
                            // $out[$last_index]['start']         =   $event->getStart()->getDateTime();
                            $out[$last_index]['start'] = $event->start_date_time;
                            // $out[$last_index]['end']             =   $event->getEnd()->getDateTime();
                            $out[$last_index]['end'] = $event->end_date_time;
                            $out[$last_index]['backgroundColor'] =  "#f6c026";
                            $color_id = $event->color_id;
                            if(!empty($color_id))
                            {
                                 // start commented on 14-oct-24
                                // $out[$last_index]['backgroundColor'] = $google_color[$color_id];
                                // $out[$last_index]['borderColor'] = $google_color[$color_id];
                                //end commented on 14-oct-24

                                 //changed on 14-oct-24
                            $google_color_id = $event->doctor['0']['google_color_id'];
                            $out[$last_index]['backgroundColor'] = $google_color[$google_color_id];
                            $out[$last_index]['borderColor'] = $google_color[$google_color_id];
                                 //changed on 14-oct-24
                                
                            }
                            $out[$last_index]['allDay'] =  false;
                            $out[$last_index]['patient_name'] = '';
                            $out[$last_index]['doctor_name']  = '';
                            $out[$last_index]['event_type']   = 'google';
                            // $splitDescription = explode("</p><p><strong>", $event->getDescription());
                            $splitDescription = explode("</p><p><strong>", $event->description);
                            $out[$last_index]['patient_name']  = trim(str_replace("<p><strong>Patient:</strong>", "", $splitDescription[0]));
                            $out[$last_index]['doctor_name'] = trim(str_replace("Arzt:</strong>", "", $splitDescription[1] ?? ''));
                            $resourceId = trim(str_replace("Arzt:</strong>", "", $splitDescription[1] ?? ''));
                            $out[$last_index]['resourceId'] = ($resourceId);
                            // if(!empty($ganny_id))
                            // {
                                $doctor_name = $event->doctor['0']['first_name'] . ' ' . $event->doctor['0']['last_name'];
                                $out[$last_index]['description'] = str_replace($out[$last_index]['doctor_name'],$doctor_name,$out[$last_index]['description']);
                                $out[$last_index]['doctor_name'] = $doctor_name;
                                $out[$last_index]['resourceId'] = ($doctor_name);
                                // $str = $ganny_id->id.'-'.$ganny_id->patient_id;
                                $appointment_id = $event->appointments['0']['id'];
                                $patient_id = $event->patient['0']['id'];
                                $str = $appointment_id . '-' . $patient_id;
                                $qr_code = '<img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl='.$str.'&choe=UTF-8">';
                                $out[$last_index]['qr_code'] =  $str;
                                $endcode_id = base64_encode(base64_encode($appointment_id));
                                $out[$last_index]['appoinmant_id'] =  $endcode_id;
                            // }
                            // else {
                            //     $out[$last_index]['qr_code'] =  '';
                            // }
                            $last_index++;
                        // }// if(!empty($ganny_id)) 
                    // }
                }
            }
        }
        catch(Google_Service_Exception $e)
        {
            //
        }
        return $out;
    }

    public function getSelectedDateEvent(Request $request)
    {

        dd($request->all());
       
    }

     /************Sonali************************/
    public function getDoctorTimeFrames(Request $request)
    {
        // dd($request->all());
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');
        try 
        {
            $patient_id   = $request->patient_id;

            if(!empty($request->doctor_id)){
                 $doctor_id    =        $request->doctor_id;          
            }else{
                 $doctor_id    =         Auth::user()->id;          

            }
            //dd($doctor_id);
            //$doctor_id    = auth()->user()->hasRole('super-admin') ? $request->doctor_id : Auth::user()->id;
            $appointment_type_id = $request->appointment_type_id;
            $appointment_date       = date("Y-m-d",strtotime($request->appointment_date));
            $sel_time_frame         = $request->sel_time_frame;
            // dd($appointment_date);
           /* $weekDay = date('N',strtotime($appointment_date));
            //dump($patient_id,$doctor_id,$appointment_type_id,$appointment_date,$weekDay);
            */
            $doctor_appointment_time_frames = $this->BaseModel
                                                    ->where('doctor_id',$doctor_id)
                                                    ->where('appointment_type_id',$appointment_type_id)
                                                    ->whereDate('start_date',$appointment_date)
                                                    ->whereStatus(1);//1=>Confirmed
                                                    //->where('patient_id','!=',$patient_id)
                                                    // ->select( DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date"))
                                                    // ->get();
            if(!empty($sel_time_frame) && !empty($patient_id)){//edit functionality
                 $doctor_appointment_time_frames = $doctor_appointment_time_frames
                                                    ->where('patient_id','!=',$patient_id);
            } 

            $doctor_appointment_time_frames = $doctor_appointment_time_frames
                                                    ->select( DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date"))
                                                    ->get();                                                     

            $doctor_atf = array();
            if(!empty($doctor_appointment_time_frames)){

                $doctor_atf = array_column($doctor_appointment_time_frames->toArray(), 'start_date');                                                    
            }
            //dd($doctor_appointment_time_frames,$doctor_atf);

           $time_frames = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')                               
                                ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id')
                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                ->whereDate('roster_has_dates.date',$appointment_date)
                                ->where('roster_has_weeks_has_time_frames.week_day_id',$day_of_week)
                                ->where('roster_has_weeks_has_time_frames.time_frame_flag','0')
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                                ->groupBy('roster_has_weeks_has_time_frames.time_frame')
                                // ->get(['roster_has_weeks_has_time_frames.time_frame']);
                                ->get(['roster_has_weeks_has_time_frames.time_frame','roster_has_weeks_has_time_frames.id as r_id']);
            // dd($time_frames->toArray());
            
            $html= "<option value=''>Select</option>";
            $msg =  __('admin.ERR_TIME_FRAME_NOT_FOUND');

            $current_time = date("H:i",time());  
            $today_date = date("Y-m-d",time());  
            if(!empty($time_frames) && count($time_frames)>0){
                $msg = '';
                foreach($time_frames as $time_frame){   
                    $time = date("H:i",strtotime($time_frame->time_frame));  
                    $selected="";            
                    if($sel_time_frame==$time){
                        $selected="selected";            
                    }  
                    if(!in_array($time, $ignore_time_slots)) {
                        if(strtotime($today_date)==strtotime($appointment_date))
                        {
                            if(($time>=$current_time) || (!empty($sel_time_frame) && $sel_time_frame==$time) ){
                                $html.="<option ".$selected." value='".$time."' lang='".$time_frame->r_id."'>".$time."</option>";
                            }

                        }elseif(strtotime($today_date)!==strtotime($appointment_date)){

                            $html.="<option ".$selected." value='".$time."' lang='".$time_frame->r_id."'>".$time."</option>";
                        }
                    }
                }

            }
           
            $this->JsonData['html'] = $html;
            $this->JsonData['data'] = $time_frames;
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');

        } catch (Exception $e) 
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);   
        
    }

    public function store(AppointmentRequest $request)
    {

        Log::info("in dashboard store admin function");
        Log::info($request->all());


        $urlEventId = $urlPatientId = '';$startDate=date("Y-m-d");
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_CREATE');
        try
        {
            DB::beginTransaction(); 
            $startDate=date("Y-m-d H:i",strtotime($request->date." ".$request->time_frame));
            $request['start_date'] = date("Y-m-d H:i",strtotime($request->date." ".$request->time_frame));
            $request['end_date']  = self::_getEndDate($request['start_date'],$request['appointment_type_id']);
            $duplicationAppointmantself =  self::_checkDuplicationAppointmant($request,'');
            if(empty($duplicationAppointmantself) && sizeof($duplicationAppointmantself)==0)
            {
                if(!empty($request->new_patient_chkbox) && $request->new_patient_chkbox==1)
                {
                    $is_exist_patient = $this->_checkDuplicationPatient($request->family_name,$request->first_name,$request->birth_date,$request->mobile_no,'add',$id = '');
                    if(!$is_exist_patient)
                    {
                        $this->JsonData['msg'] = __('admin.ERR_MOBILE_UNIQUE'); 
                        $this->JsonData['status']   = __('admin.RESP_ERROR');
                        return response()->json($this->JsonData);
                        exit();
                    }

                    // $checkedBirthdateExist = $this->PatientsModel
                    //                         ->where(DB::raw('upper(family_name)'),'=',strtoupper($request->family_name))
                    //                         ->where(DB::raw('upper(first_name)'),'=',strtoupper($request->first_name))
                    //                         ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date)))
                    //                         ->whereNULL('deleted_at')
                    //                         ->get();
                    // //dd( $checkedBirthdateExist ); services
                    // if(count($checkedBirthdateExist) > 0 )
                    // {
                    //     $this->JsonData['msg'] = __('admin.ERR_BIRTH_DATE_UNIQUE'); 
                    //     $this->JsonData['status']   = __('admin.RESP_ERROR');
                    //     return response()->json($this->JsonData);
                    //     exit();
                    // }
                    $patient_data     = new $this->PatientsModel;    
                    $patient_data     = self::_storePatient($patient_data,$request);
                    //Log::info($this->ModuleTitle.' patient create by DashboardController line no 1051 :' .$patient_data->first_name.' '.$patient_data->family_name);
                    if(!empty(Config('ordination_id')))
                    {
                        $ordination_patient = self::_storePatientOrdination($patient_data->id);
                        //Log::info($this->ModuleTitle.' master patient create by DashboardController line no 1055 :' .$patient_data->first_name.' '.$patient_data->family_name);
                    }
                    $patient_id             = $patient_data->id;
                    $request['patient_id']  = $patient_id;
                    //Added by Shyam 16-02-22
                    if(isset($patient_id) && $patient_id != '')
                    {
                        $setReminders = app('App\Http\Controllers\Admin\DashboardController')->checkPatientAgeReminder($patient_id);
                    }
                    
                }
                else {
                    $patient_id             = $request->patient_id;
                    $getPatientDetails = $this->PatientsModel
                                        ->where('id',$patient_id)
                                        ->first();
                    if(!empty(Config('ordination_id')) && empty($getPatientDetails['country']))
                    {
                        $ordination_patient = self::addPatientCountryOnOrdination($patient_id);
                    }
                }

                //Added 07-march-2022
                $this->addDynamicAppointmentTypes($request);

                $collection     = new $this->BaseModel;   
                $request['start_date'] = date("Y-m-d H:i",strtotime($request->date." ".$request->time_frame));
                $request['end_date']  = self::_getEndDate($request['start_date'],$request['appointment_type_id']);
                // added by vijay 12/9/2024
                $loginUser = Auth::user();

                $collection->appointment_created_from = 1;
                $collection->optimal_appointment = $request->quarter_setting_check ? $request->quarter_setting_check:null;
                $collection->appointment_createdby = $loginUser->id;
                // end
                $collection     = self::_storeOrUpdate($collection,$request);

                //=============================================================== 
                $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                        ->where('id',$request->roster_time_frame_id)
                                        ->update(['time_frame_flag'=>'2',
                                                  'time_frame_flag_date'=>Date('Y-m-d H:i:s'),
                                                  'comment'=>'DashboardController store booking function app Date : '.date('Y-m-d H:i:s', strtotime($collection->start_date)).' current date: '.Date('Y-m-d H:i:s').' patient_id: '.$patient_id
                                                ]);
                //========================================================== 
                //self::_deactivateReminder($collection);
                                        //die("here now1");
                self::_deactivateReminderNew($collection,$request->app_services);
                //self::_deactivateReminderNew($collection,$request->app_services);
                $newData = $collection->toArray();

                $all_transactions = [];
                $notify_data = [];
                if ($collection) 
                {
                    $all_transactions[] = 1;

                    // $patient_doc_data = [];
                    // $patient_doc_data[] = array(
                    //                             'appointment_id'=> $collection->id,
                    //                             'patient_id'    => $collection->patient_id,
                    //                             'exam_app_type_id'=> $request['appointment_type_id'],
                    //                             'record_type'   => 1,
                    //                             'doc_status'   => 0,
                    //                             );
                    // // dd($patient_doc_data);

                    // if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                    //     $all_transactions[] = 1;
                    // }else{
                    //     $all_transactions[] = 0;
                    // }

                    //insert the entry for patient has document
                    $getDocument = self::_GetAssignedDocument($collection->id,$collection->appointment_type_id,$request->app_services,$collection->patient_id);
                    // END

                    //insert the entry for patient has Checklist
                    $getDocument = self::_GetAssignedCheckList($collection->id,$request->app_services,$collection->patient_id);
                    // END

                    $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType'])->find($collection->id);   
                        
                    $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                    $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                    $appointmentType = $collection->assignedAppointmentType->name;

                    $booking_month = __('admin.'.date('F',strtotime($request->start_date)),[],'de');
                    $appointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                    //commented on 6-nov-23
                    // $patientText = $collection->assignedPatient->salutation ?? "";
                    // $patientText .= " ".$collection->assignedPatient->family_name;

                    // $patientText = $collection->assignedPatient->salutation ? " ".$collection->assignedPatient->salutation.'.': ""; //added dot after salutation on 14-dec-23 commented on 12-dec-25

                    $patientText = $collection->assignedPatient->salutation ? $collection->assignedPatient->salutation.'.': ""; //added dot after salutation on 14-dec-23 changed on 12-dec-25

                    //$patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;  //added on 6-nov-23 commented on 12-dec-25

                    //changed on 12-dec-25
                    if(isset($collection->assignedPatient->salutation)){
                         $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;  //added on 6-nov-23

                     }else{
                         $patientText .= $collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;  //added on 6-nov-23
                     }

                   

                    $doctorSurname = $collection->assignedDoctor->last_name;
                    //Appoinment Push Notification

                    //Commented on 6-nov-23
                    // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.'('.$appointmentType.') ist am'.' '.(string)$appointmentTime;

                   


                    $notify_times = self::_getNotifyTime($request['start_date']);


                     //commented below code on 13-feb-24

                    // $content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime; //content changed on 6-nov-23 //added space after doctor surname on 14-dec-23

                   /* foreach ($notify_times as $notify_time) {
                        
                        $notify_data[] = array(
                                                'patient_id'=> $patient_id,
                                                'appointment_id'=> $collection->id,
                                                'title'=> 'Erinnerung an Ihren Termin',
                                                'content'=> $content,
                                                'notify_time'=> $notify_time,
                                                'status'=> 0,
                                            );
                    }
                    if($this->AppointmentHasNotificationModel->insert($notify_data))
                    {
                        $all_transactions[] = 1;
                    }
                    else {
                        $all_transactions[] = 0; 
                    }*/

                    //commented above code on 13-feb-24


                    /************added code on 13-feb-24***for notification from setting section*******/
                    $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));


                    $skipNotification = false; //added on 12-nov-25


                    $getSetting  = $this->AppointmentHasNotificationModel->whereStatus(3)->first();
                    if(isset($getSetting) && !empty($getSetting))
                    {   
                       // dump("in getsetting");

                        $title = $getSetting->title;
                        $content = $getSetting->content;
                        $day = $getSetting->day;
                        $notify_time = $getSetting->notify_time;
                        $appointmentDate =  date("Y-m-d",strtotime($request->start_date));

                        // dump('in notify_time..');
                        // dump($notify_time);

                        if($day==0) //current day
                        {
                            $req_notify_time   = explode(" ",$getSetting->notify_time);
                            $req_notify_time_in_seconds = $req_notify_time[1];
                            $app_notify_time   = date("Y-m-d H:i:s", strtotime($appointmentDate." ".
                                $req_notify_time_in_seconds));


                            // start added on 12-nov-25 for skip notification

                            $currentDate = date('Y-m-d');

                            if ($appointmentDate == $currentDate && strtotime($request->start_date) < strtotime($appointmentDate . ' ' . $req_notify_time_in_seconds)) {
                                $skipNotification = true;
                            }
                            //end 

                        }
                        else
                        {
                            //previous day
                            $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($request->start_date)));
                            $req_notify_time   = explode(" ",$getSetting->notify_time);
                            $req_notify_time_in_seconds = $req_notify_time[1];
                            $app_notify_time = date("Y-m-d H:i:s", strtotime($previous_day." ".
                                $req_notify_time_in_seconds));
                          
                        }

                        $content = str_replace("##PATIENT_NAME##", $patientText, $content);
                        $content = str_replace("##DOCTOR_SURNAME##", $doctorSurname, $content);
                        $content = str_replace("##APPOINTMENT_TYPE##", $appointmentType, $content);
                        $content = str_replace("##DATE_TIME##", $appointmentTime, $content); 


                    }//if isset getsetting
                    else
                    {
                        $title = 'Erinnerung an Ihren Termin';
                        $content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime; 
                        $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));
                    }

                    $notify_data= array(
                                        'patient_id'=> $patient_id,
                                        'appointment_id'=> $collection->id,
                                        'title'=>$title,
                                        'content'=> $content,
                                        'notify_time'=> $app_notify_time,
                                        'status'=> 0,
                                        );

                    //commented on 12-nov-25
                    /* if($this->AppointmentHasNotificationModel->insert($notify_data))
                    {
                        $all_transactions[] = 1;
                    }
                    else 
                    {
                        $all_transactions[] = 0; 
                    }*/

                    //start changed on 12-nov-25
                    if (!$skipNotification) 
                    {
                        if ($this->AppointmentHasNotificationModel->insert($notify_data)) {
                            $all_transactions[] = 1;
                        } else {
                            $all_transactions[] = 0; 
                        }
                    } else {
                        \Log::info("Skipped notification save for appointment ID {$collection->id} — appointment time is before notify time.");
                    }
                    //end changed on 12-nov-25




                    /*******end code**of notification setting****13-feb-24************/



                    Log::info("admin dashboard store before appointment exam store ");
                    
                    //Default appintment 
                    $getServises = self::_appointmentTypesAgaintsServices($collection->id,$request,$patient_id);
                    $serviceEventType = self::GetServicesEventType($collection->id,$patient_id,$request->app_services,$request['appointment_type_id'],'admin');

                    // END
                    // Get Appointment Type Services
                    $AppointmentHasExamination = $this->AppointmentHasExaminationsModel
                                                        ->where('appointment_id',$collection->id)
                                                        ->where('patient_id',$patient_id)
                                                        ->get(); 
                    
                    $str_exam = '';                                    
                    if(!empty($AppointmentHasExamination) && sizeof($AppointmentHasExamination)>0)
                    {
                        foreach ($AppointmentHasExamination as $exam_key => $exam_value) 
                        {
                            $services = $this->ExaminationsModel->where('id',$exam_value['examination_id'])->first();         
                            if(!empty($services))
                            $str_exam .= '<ul><li>'.$services->name.'</li></ul>';
                        }
                    }                                    
                    // End
                   
                    //Appoinment added in google calendar
                    $summary = $patientName." - ".$appointmentType;
                    $description = '<p><strong>'.$this->patientText.':</strong> '.$patientName.' </p><p><strong>'.$this->doctorText.':</strong> '.$doctorName.' </p><p><strong>'.$this->appointmentText.':</strong> '.$appointmentType.' </p><p><strong>'.$this->startDateText.':</strong> '.date('F d,Y H:i',strtotime($request->start_date)).' </p><strong>'.$this->endDateText.':</strong> '.date('F d,Y H:i',strtotime($request->end_date)).' </p><p><strong>'.$this->notesText.':</strong> '.$request->notes.' </p>';

                    $request = array(
                                     'summary'=>$summary,
                                     'description'=>$description,
                                     'startDateTime'=>$request->start_date,
                                     'endDateTime'=>$request->end_date,
                                     'patient_id'=>$patient_id,
                                     'patient_email'=>$collection->assignedPatient->email,
                                     'patient_name'=>$patientName,
                                     'doctor_email'=>$collection->assignedDoctor->email,
                                     'color_id'=>$collection->assignedDoctor->google_color_id,
                                    );

                    /*if(!empty($request->new_patient_chkbox) && $request->new_patient_chkbox==1){
                        $request['patient_name']= $patientName;
                    }*/
                    request()->merge($request);
                    //dd(request()->all());
                    $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventStore(request());
                    //$postResponse = json_decode($postCalDetails->data);
                     
                    if(!empty($postCalDetails) && $postCalDetails->original['status'] == 'success')
                    {
                        $all_transactions[] = 1;

                        $eventId = $postCalDetails->original['data']->id;
                        // event id store in both field beacuase of new calender CR
                        $collection->google_event_id = $eventId;
                        $collection->event_id = $eventId;
                        if($collection->save())
                        {
                            $updateEvent = app('App\Http\Controllers\Admin\DashboardController')->appointmentIdUpdateInEvent($eventId, $collection->id);
                            $all_transactions[] = 1;
                            //Added by Shyam 24-03-22
                            $urlEventId = $eventId;
                            $urlPatientId = $collection->assignedPatient->id;
                        }
                        else {
                            $all_transactions[] = 0;
                        }
                        // Log::info($this->ModuleTitle.'has created appointmen by DashboardController');
                        $debug_arr['data'] = 'has created appointmen by DashboardController';
                        $res_name = "DashboardController_store";   
                            //dd($debug_arr);  
                        self::debugModeappBookFun($debug_arr,$res_name);

                        $this->ActivityLogModel->addLog($this->ModuleTitle,'has created appointment','Add',null,$newData);
                        //add reminders for pass appointments added by swati 9-Jun-23================================================
                        $newdate=date("Y-m-d",strtotime($request['startDateTime']));
                        $todayDate=date('Y-m-d');
                        if($newdate < $todayDate){
                             log::info("DashboardController-Pass");
                             $this->_remindersPassAppointments($collection->id);
                            // $allServices = DB::table('appointment_has_examinations')
                            //             ->select('examinations.*')
                            //             ->leftjoin('examinations','examinations.id','appointment_has_examinations.examination_id')
                            //             ->where('appointment_id',$collection->id)
                            //             ->where('examinations.show_as_reminder','1')
                            //             ->get();
                            // if(!empty($allServices)){
                            //     if(!empty($allServices) && count($allServices) > 0)
                            //     {
                            //         foreach($allServices as $service){
                            //             $sql2 = "SELECT * FROM patient_has_reminder WHERE id IN (
                            //                 select max(patient_has_reminder.id) from patient_has_reminder
                            //                 left join patient_has_service_reminder on patient_has_service_reminder.id = patient_has_reminder.service_reminder_id
                            //                 where date(`last_reminder_date`) < CURRENT_DATE()
                            //                 and appointment_id=$collection->id and service_id=$service->id
                            //                 and reminder_status!='ignore' GROUP by patient_has_service_reminder.patient_id,service_id)";//and patient_has_reminder.deleted_at is null
                            //             $checkServiceReminder = DB::select($sql2);
                            //             log::info($checkServiceReminder);
                            //             if(!empty($checkServiceReminder)){
                            //                 log::info("_reactivePassAppoitment1");
                            //                 $this->_reactivePassAppoitment($collection->id);
                            //             }
                            //         }
                            //     }
                            // }
                        }
                        //==============================================================
                    }
                    else {
                        $all_transactions[] = 0;
                        DB::rollback();
                        $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $postCalDetails->original['msg'];
                    }
                }
                else {
                    $all_transactions[] = 0;
                }
                if (!in_array(0,$all_transactions)) 
                {
                    DB::commit();

                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    /*$this->JsonData['url']      =  route($this->ModulePath.'index');*/
                    $this->JsonData['url']      =  route($this->ModulePath);
                    $this->JsonData['msg']      = __('admin.APPOINTMENT_CREATED');
                }
            }
            else {
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                //$this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.APPOINTMENT_SLOT_ALREADY_EXIST');
            }
        }
        catch(\Exception $e) {
            DB::rollback();
            // dd($e->getMessage());
            $this->JsonData['msg']      = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        //Added by Shyam 24-03-22
        $newdate=date("Y-m-d",strtotime($startDate));
        $todayDate=date('Y-m-d');
        if(!empty($urlEventId) && !empty($urlPatientId) && $newdate >= $todayDate)
        {
            $channels = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();
            $patientData = $this->PatientsModel->where('id', $urlPatientId)->first();
            //Send Email...
            if(!empty($patientData->email) && $channels->choice_of_channels == 'email')
            {
                self::_sendMailAppointment($patientData->id,$urlEventId);
            }
            else {
                //Send SMS...
                $phone_no = '';
                $country_code = (!empty($patientData)) ? $patientData->country_code : '00';
                if(!empty($country_code)) {
                    $country_code = str_replace("00", "",$country_code);
                }
                elseif(empty($country_code) || $country_code=='0') {
                    $country_code = '43'; //Austria country code
                }
                $country_code = str_replace("+", "",$country_code);
                if(!empty($patientData->mobile_no))
                {
                    $phone_no = $country_code."".str_replace("-", "",$patientData->mobile_no);
                }
                if(!empty($phone_no))
                {
                    self::_sendSmsAppointment($phone_no,$urlEventId);
                }
                elseif(!empty($patientData->email))
                {
                    self::_sendMailAppointment($patientData->id,$urlEventId);
                }
            }
        }
        return response()->json($this->JsonData);
    }

    public function _storePatient($collection, $request) 
    {
        Log::info("in dashboard controller _storePatient function");
        Log::info($request->all());

        if(!empty($request->birth_date))
        {
            $birth_date                  = date('Y-m-d', strtotime($request->birth_date));
            $age                         = (date('Y') - date('Y',strtotime($birth_date)));
        }
        else {
            $birth_date                  = NULL;
            $age                         = 0;
        }
        $collection->first_name         = self::string_operation($request->first_name); 
        $collection->family_name        = self::string_operation($request->family_name);
        $collection->country_code       = $request->country_code;
        if(!empty($request->format))
        {
           $collection->country_code       = $request->format; 
        }  
        $mobile_no                      = str_replace(" ", "", ltrim($request->mobile_no,'0'));
        $collection->mobile_no          = $mobile_no;
        $collection->old_id             = 99999;
        $collection->birth_date         = $birth_date; 
        $collection->age                = $age; 
        $collection->email              = $request->email;
        $collection->insurance_number   = $request->insurance_number;
        $collection->gender             = $request->gender;
        $collection->postal_code        = $request->postal_code;
        
        // $collection->country             = $request->country; //Roshani added on 10 oct 24 for # 102 CR


        //Save data
        $collection->save();

        if(!empty(Config('ordination_id')))
            {
                $ordination_patient = self::addPatientCountryOnOrdination($collection->id);
            }
        return $collection;
    } 

    public function _storeOrUpdate($collection, $request)
    {
        if(!empty($request->doctor_id)){
             $doctor_id    =        $request->doctor_id;          
        }else{
             $doctor_id    =         Auth::user()->id;          

        }
        //Added by swati 8-Jun-23===================================================
        $collection->appointment_status='';
        $newdate=date("Y-m-d",strtotime($request->start_date));
        $todayDate=date('Y-m-d');
        if($newdate < $todayDate) $collection->appointment_status='Fertig';
        //=========================================================================
        $collection->patient_id  = $request->patient_id;
        $collection->doctor_id   = $doctor_id ;
        //$collection->doctor_id   = auth()->user()->hasRole('super-admin') ? $request->doctor_id : Auth::user()->id;  
        $collection->appointment_type_id = $request->appointment_type_id;
        $collection->notes      = $request->notes;
        $collection->start_date = $request->start_date;
        $collection->end_date   = $request->end_date;
        // $collection->start_date = date("Y-m-d H:i",strtotime($request->date." ".$request->time_frame));
        // $collection->end_date  = self::_getEndDate($collection->start_date);
        $collection->status    = 1;
        // dd($collection);
        // dd($collection);
        //Save data
        $collection->save();
        //echo "heer";print_r($collection);exit;

       
        return $collection;
        
    }

    public function create()
    {
        // Default site settings
        $this->ModuleTitle  = __('admin.TITLE_DASHBOARD');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle;
        $this->ViewData['moduleAction'] = $this->ModuleTitle;
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['user'] = $this->AdminUserModel
                                      //  ->where('status', 1)
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'doctor');
                                        })
                                        ->get();
        // All patients 
       /* 
       $this->ViewData['patient'] = $this->PatientsModel
                                        ->where('status', 1)
                                        ->get(); */
        // All appointment types 
        $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->get();
        // view file with data
        // added by vijay 8/3/24
        $quarter_setting = 0;
        $optimal_appointment = $this->SettingsModel->where(['setting_key' => 'OPTIMAL_APPOINTMENT'])->select('setting_key', 'setting_value')->first();
        if (isset ($optimal_appointment) && !empty ($optimal_appointment)) {
            $quarter_setting = $optimal_appointment->setting_value;
        }
        $this->ViewData['quarter_setting'] = $quarter_setting;
        $this->ViewData['country_codes'] = $this->CountryCodesModel
            ->where('is_active',1)
            // ->orderBy('phone_code')
            ->pluck('phone_code')
            ->toArray();
        return view($this->ModuleView.'create', $this->ViewData);
    }

    public function edit($encID)
    {
        //dd($encID); 
        // Default site settings
        $this->ViewData['status']   = __('admin.RESP_ERROR');

        $this->ModuleTitle              = __('admin.TITLE_APPOINTMENT_TEXT'); 
        $this->ViewData['moduleTitle']  = __('admin.TITLE_MANAGE_TEXT').' '.$this->ModuleTitle;
        $this->ViewData['moduleAction'] = __('admin.TITLE_EDIT_TEXT').' '.\Illuminate\Support\Str::singular($this->ModuleTitle);

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // Appointment
       
        $appointmentUserID = $this->AppointmentModel
                                    ->where('google_event_id', $encID)
                                    ->where('status', 1)
                                    ->first(); 
        if(isset($appointmentUserID))
        {
            $id = $appointmentUserID->id;

            $this->ViewData['modulePath']   = route($this->ModulePath.'update', [base64_encode(base64_encode($id))]);

            $appointment = $this->BaseModel->find($id);
            
            // All user which have role as doctor
            $this->ViewData['user'] = $this->AdminUserModel
                                           // ->where('status', 1)
                                            ->whereHas('roles',function($query){
                                               $query->where('name', 'doctor');
                                            })
                                            ->get(); 
            
            if(!empty($appointment)){
                $patients = $this->PatientsModel
                                  ->where('id', $appointment->patient_id)
                                  ->where('status', 1)
                                  ->get();
            }else{
                $patients = $this->PatientsModel
                                ->where('status', 1)
                                ->get();
            }


            $this->ViewData['appointment'] = $appointment;
            $appointment_id = $this->ViewData['appointment']->id;
            // Get Services
            $getService = $this->AppointmentHasExaminationsModel
                          ->where('appointment_id',$appointment_id)
                          ->get();
            $time_frames_id='';              
            //dd($getService);        
            $timeFrame = date('H:i:s',strtotime($appointment->start_date));
            $doctor_id = $appointment->doctor_id;

            $time_frames= $this->RosterHasDatesModel
                        ->leftjoin('roster','roster.id','roster_has_dates.roster_id')
                        ->whereDate('roster_has_dates.date',date('Y-m-d',strtotime($appointment->start_date)))
                        ->where('roster.doctor_id',$doctor_id)
                        ->first();
                            
            if(!empty($time_frames))
            {
                $getrec = $this->RosterHasWeeksHasTimeFramesModel
                          ->where('week_day_id',$time_frames->week_day_id)   
                          ->where('roster_id',$time_frames->roster_id) 
                          ->where('time_frame',$timeFrame)   
                          ->where('time_frame_flag','2')
                          ->first();
                if(!empty($getrec))
                {
                    $time_frames_id = $getrec->id;
                }      
                
            }
            // All patients  
            $this->ViewData['patient'] = $patients;  
             // ############# Roshani Added this code on (28/02/2024) ################# 
            $discardIdsfromAppType = $this->UserHasAppointmentType->where('user_id',$appointment->doctor_id)->pluck('appointment_type_id')->toArray();
            $filteredTypeIds = collect($discardIdsfromAppType)->diff([$appointment->appointment_type_id])->values()->all();

            // $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->whereNotIn('id',$filteredTypeIds)->get();//commented on 13-apr-26

             $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->whereNotIn('id',$filteredTypeIds)->withTrashed()->get();//changed on 13-apr-26


        // ############# Roshani Added this code on (28/02/2024) ################# 
            // All appointment types 
            // $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->get();
            
            $this->ViewData['time_frames_id'] =  $time_frames_id;
            $this->ViewData['encID'] =  $id;                                 
            $this->ViewData['result'] =  "found"; 
            $this->ViewData['status']   = __('admin.RESP_SUCCESS');
            // added by vijay 8/3/24
            $quarter_setting = 0;
            $optimal_appointment = $this->SettingsModel->where(['setting_key' => 'OPTIMAL_APPOINTMENT'])->select('setting_key', 'setting_value')->first();
            if (isset ($optimal_appointment) && !empty ($optimal_appointment)) {
                $quarter_setting = $optimal_appointment->setting_value;
            }
            $this->ViewData['quarter_setting'] = $quarter_setting;
        } 
        else
        {
            $this->ViewData['result'] =  __('admin.NO_RESULT_FOUND'); 
        }
        
        // return response()->json($this->ViewData);
        // view file with data
        //dd($this->ViewData);
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function view($encID)
    {        
        // Appointment
       
        $appointmentUserID = $this->AppointmentModel
                                    ->where('google_event_id', $encID)
                                    ->where('status', 1)
                                    ->first(); 
        $data = '';
        if(isset($appointmentUserID))
        {
            $id = $appointmentUserID->id;
            
            
            $appointment = $this->BaseModel->find($id);
            
            // All user which have role as doctor
            $doctor_name = $this->AdminUserModel
                                           // ->where('status', 1)
                                            ->where('id',$appointment->doctor_id)
                                            ->whereHas('roles',function($query){
                                               $query->where('name', 'doctor');
                                            })
                                            ->first(); 

                                          //  dd( $appointment->doctor_id,$doctor_name);

            $appointment_types = $this->AppointmentTypesModel
                                            ->where('id',$appointment->appointment_type_id)
                                            ->withTrashed()  //added on 13-apr-26 for #383
                                            ->first(); 
            
            if(isset($appointment_types) && !empty($appointment_types))
                {
                    $appointment_type_name = $appointment_types->name;
                }
                else
                {
                    $appointment_type_name = '';
                }
            
            $patients = $this->PatientsModel
                                  ->where('id', $appointment->patient_id)
                                  ->where('status', 1)
                                  ->first();

            $str = $id.'-'.$patients->id;

            $qr_code = '<img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl='.$str.'&choe=UTF-8">';
          

            //dd($doctor_name,$patients,$appointment);
            // $data ='<div class="col-md-8" id="popup_description"><p><strong>Patient:</strong> '.$patients->first_name.' '.$patients->family_name.'</p><p><strong>Arzt:</strong> '.$doctor_name->first_name.' '.$doctor_name->last_name.'</p><p><strong>Typ:</strong> '.$appointment_types->name.'</p><p><strong>Beginn:</strong> '.date('F d,Y H:i',strtotime($appointment->start_date)).'</p><strong>Ende: </strong>'.date('F d,Y H:i',strtotime($appointment->end_date)).'<p></p><p><strong>Notizen: </strong>'.$appointment->notes.'</p></div> <div class="col-md-4" id="qr_code">'.$qr_code.'</div>
            //       </div>'; 

            $first_name        = $patients->first_name ?? '';
            //$doctor_name       = $doctor_name->first_name ?? ''; //commented on 30-nov-23
            $doctor_first_name = $doctor_name->first_name ?? ''; //added on 30-nov-23
            $doctor_last_name  = $doctor_name->last_name ?? '';

            // Get Appointment Type Services
            $str_exam = '';
            $patient_id = $appointment->patient_id;
            $appointment_id = $id;
            $getRecord = $this->AppointmentTypeHasExaminationsModel
                         ->where('appoinment_id',$appointment->appointment_type_id)
                         ->with(['assignedExamination'])
                         ->wherenull('deleted_at')
                         ->get();

            // if(!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id))
            // {
            //     $getRecord = $getRecord->map(function($item) use($appointment_id,$patient_id)
            //     {
            //         $exam_id = $item->assignedExamination->id;
            //         $is_checked = $this->AppointmentHasExaminationsModel
            //                     ->where('appointment_id',$appointment_id)
            //                     ->where('patient_id',$patient_id)
            //                     ->where('examination_id',$exam_id)
            //                     ->first();
                               
            //        $item->checked = (!empty($is_checked) == 0) ? 0 : 1;
            //        return $item;
            //     });
            // } 
            $getRecord = $this->AppointmentHasExaminationsModel
                                ->where('appointment_id',$appointment_id)
                                ->where('patient_id',$patient_id)
                                ->with(['assignedExamination'])
                                //->where('examination_id',$exam_id)
                                ->get();

            if(!empty($getRecord) && sizeof($getRecord)>0)
            {
                $str_exam .= "<p><strong>Leistungen: </strong>";
                foreach ($getRecord as $key => $value) 
                {   
                    $checked ='';
                    // if($value['checked'] == 1)
                    // {
                    //     $checked = 'checked';
                    // }
                    if(isset($value['assignedExamination']->id)){
                        $str_exam .= "<h6 style='margin-left: 80px;'><input disabled type='checkbox' checked class='form-check-input' name='app_services[]'
                            name='status' value=".$value['assignedExamination']->id." 
                            >".$value['assignedExamination']->name."<h6>";  
                    }
                };
                $str_exam .= "</p>";
            }
            //month for german 7 jul 2022 added by divya=======
          
            $explod_begindate=[]; $beginmonth = $begindate =$beginyear =$fullbegindate='';
            if(isset($appointment->start_date) && !empty($appointment->start_date))
            {
                $beginDate = date('F d,Y H:i',strtotime($appointment->start_date));
                $explod_begindate = explode(" ",$beginDate);
                $beginmonth = __('admin.'.$explod_begindate[0]);
                $begindate = $explod_begindate[1];
                $beginyear = $explod_begindate[2];
                $fullbegindate = $beginmonth.' '.$begindate.' '.$beginyear;
            }//if start_date
           
             $explod_enddate=[]; $enddate=$endmonth=$endyear=$fullenddate='';
            if(isset($appointment->end_date) && !empty($appointment->end_date))
            {
                $endDate = date('F d,Y H:i',strtotime($appointment->end_date));
                $explod_enddate = explode(" ",$endDate);
                $endmonth = __('admin.'.$explod_enddate[0]);
                $enddate = $explod_enddate[1];
                $endyear = $explod_enddate[2];
                $fullenddate = $endmonth.' '.$enddate.' '.$endyear;
            }
            $data ='<p><strong>Patient:</strong> '.$first_name.' '.$patients->family_name.'</p><p><strong>Arzt:</strong> '.$doctor_first_name.' '.$doctor_last_name.'</p><p><strong>Typ:</strong> '.$appointment_type_name.'</p><p><strong>Beginn:</strong> '. $fullbegindate.'</p><strong>Ende: </strong>'.$fullenddate.'<p></p><p><strong>Notizen: </strong>'.$appointment->notes.'</p>'.$str_exam;
            //month for german 7 jul 2022 added by divya=======
            // $data ='<p><strong>Patient:</strong> '.$first_name.' '.$patients->family_name.'</p><p><strong>Arzt:</strong> '.$doctor_name.' '.$doctor_last_name.'</p><p><strong>Typ:</strong> '.$appointment_types->name.'</p><p><strong>Beginn:</strong> '.date('F d,Y H:i',strtotime($appointment->start_date)).'</p><strong>Ende: </strong>'.date('F d,Y H:i',strtotime($appointment->end_date)).'<p></p><p><strong>Notizen: </strong>'.$appointment->notes.'</p>'.$str_exam;  

            return $data;                         
            
        } 
        else
        {
            return $data;
        }
        
    }

    public function update(AppointmentRequest $request,$encID)
    {
        Log::info("in dashboard update function ");
        Log::info($request->all());

        // dd($request->all());
        $id = base64_decode(base64_decode($encID));

        Log::info("in admin dashbaord controller update function id ");
        Log::info($id);


        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_UPDATE');       
              
        try {

            DB::beginTransaction(); 
            $request['start_date'] = date("Y-m-d H:i",strtotime($request->date." ".$request->time_frame));
            $request['end_date']  = self::_getEndDate($request['start_date'],$request['appointment_type_id']);
            $duplicationAppointmantself =  self::_checkDuplicationAppointmant($request,$id);

            if(empty($duplicationAppointmantself) && sizeof($duplicationAppointmantself)==0)
            {
                $this->PatientHasDocumentsModel->where(['appointment_id'=>$id,'patient_id'=>$request->patient_id])->delete();
                $this->CheckListHasSelectedQuestionModel->where(['fk_appointment_id'=>$id,'fk_patient_id'=>$request->patient_id])->delete();
                
                // $patient_doc_data[] = array(
                //                                 'appointment_id'=> $id,
                //                                 'patient_id'    => $request->patient_id,
                //                                 'exam_app_type_id'=> $request->appointment_type_id,
                //                                 'record_type'   => 1,
                //                                 'doc_status'   => 0,
                //                                 );
                //dd($patient_doc_data);
                $getDocument = self::_GetAssignedDocument($id,$request->appointment_type_id,$request->app_services,$request->patient_id);
                    // END

                    //insert the entry for patient has Checklist
                $getDocument = self::_GetAssignedCheckList($id,$request->app_services,$request->patient_id);

                if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                    $all_transactions[] = 1;
                }else{
                    $all_transactions[] = 0;
                }

                $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType'])->find($id);   
                // dump($collection);
                $oldData = [];
                $oldData['id'] = $collection->id;
                $oldData['google_event_id'] = $collection->google_event_id;
                $oldData['start_date'] = $collection->start_date;
                $oldData['end_date'] = $collection->end_date;
                $oldData['patient_id'] = $collection->patient_id;
                $oldData['doctor_id'] = $collection->doctor_id;
                $oldData['appointment_type_id'] = $collection->appointment_type_id;
                $oldData['notes'] = $collection->notes;
                $oldData['status'] = $collection->status;
                $oldData['created_at'] = $collection->created_at;
                $oldData['updated_at'] = $collection->updated_at;
                $oldData['deleted_at'] = $collection->deleted_at;
                // dd($oldData);

                $request['start_date'] = date("Y-m-d H:i",strtotime($request->date." ".$request->time_frame));
                $request['end_date']  = self::_getEndDate($request['start_date'],$request['appointment_type_id']);
                // dd($request->all());
                // added by vijay 12/9/2024
                $loginUser = Auth::user();

                $collection->appointment_updated_from = 1;
                $collection->optimal_appointment = $request->quarter_setting_check_val ? $request->quarter_setting_check_val : null;
                $collection->appointment_createdby = $loginUser->id;
                // end
                $collection = self::_storeOrUpdate($collection,$request);
                $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType'])->find($id);   

                $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                        ->where('id',$request->roster_time_frame_id1)
                                        ->update(['time_frame_flag'=>'2',
                                                  'time_frame_flag_date'=>Date('Y-m-d H:i:s'),
                                                  'comment'=>'DashboardController update booking function app Date : '.date('Y-m-d H:i:s', strtotime($collection->start_date)).' current date: '.Date('Y-m-d H:i:s').' patient_id: '.$patient_id
                                                 ]);


                // dd($collection);
                $newData = [];
                $newData['id'] = $collection->id; 
                $newData['google_event_id'] = $collection->google_event_id;
                $newData['start_date'] = $collection->start_date;
                $newData['end_date'] = $collection->end_date;
                $newData['patient_id'] = $collection->patient_id;
                $newData['doctor_id'] = $collection->doctor_id;
                $newData['appointment_type_id'] = $collection->appointment_type_id;
                $newData['notes'] = $collection->notes;
                $newData['status'] = $collection->status;
                $newData['created_at'] = $collection->created_at;
                $newData['updated_at'] = $collection->updated_at;
                $newData['deleted_at'] = $collection->deleted_at;

                $all_transactions = [];
                $notify_data = [];
                if ($collection) 
                {
                    $all_transactions[] = 1;

                    $this->AppointmentHasNotificationModel->where('appointment_id',$collection->id)->delete();
                    // Get Appointment Type Services
                    $AppointmentHasExamination = $this->AppointmentHasExaminationsModel
                                                        ->where('appointment_id',$collection->id)
                                                        ->where('patient_id',$collection->patient_id)
                                                        ->get(); 
                    $str_exam = '';                                    
                    if(!empty($AppointmentHasExamination) && sizeof($AppointmentHasExamination)>0)
                    {
                        foreach ($AppointmentHasExamination as $exam_key => $exam_value) 
                        {
                            $services = $this->ExaminationsModel->where('id',$exam_value['examination_id'])->first();
                            $str_exam .= '<ul><li>'.$services->name.'</li></ul>';
                        }
                    }                                    
                    // End


                    
                    $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                    $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                    $appointmentType = $collection->assignedAppointmentType->name;
                    $summary = $patientName." - ".$appointmentType;
                    $description = '<p><strong>'.$this->patientText.':</strong> '.$patientName.' </p><p><strong>'.$this->doctorText.':</strong> '.$doctorName.' </p><p><strong>'.$this->appointmentText.':</strong> '.$appointmentType.' </p><p><strong>'.$this->startDateText.':</strong> '.date('F d,Y H:i',strtotime($request->start_date)).' </p><strong>'.$this->endDateText.':</strong> '.date('F d,Y H:i',strtotime($request->end_date)).' </p><p><strong>'.$this->notesText.':</strong> '.$request->notes.' </p>';

                    $booking_month = __('admin.'.date('F',strtotime($request->start_date)),[],'de');
                    $appointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                    //commnted on 6-nov-23
                    // $patientText = $collection->assignedPatient->salutation ?? "";
                    // $patientText .= " ".$collection->assignedPatient->family_name;

                    // $patientText = $collection->assignedPatient->salutation ? " ".$collection->assignedPatient->salutation.'.': "";  //changed on 6-nov-23 added dot after salutation on 14-dec-23 commented on 12-dec-25

                    $patientText = $collection->assignedPatient->salutation ? $collection->assignedPatient->salutation.'.': "";  //changed on 6-nov-23 added dot after salutation on 14-dec-23 changed on 12-dec-25




                    // $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;  //changed on 6-nov-23 //commented on 12-dec-25


                    //changed on 12-dec-25
                    if(isset($collection->assignedPatient->salutation)){
                         $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;  //changed on 6-nov-23
                     }else{
                         $patientText .= $collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;  //changed on 6-nov-23
                     }
                     


                    $doctorSurname = $collection->assignedDoctor->last_name;
                    //Appoinment Push Notification
                    //commnted on 6-nov-23
                    // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.'('.$appointmentType.') ist am'.' '.(string)$appointmentTime;

                                      

                    $notify_times = self::_getNotifyTime($request['start_date']);

                     // commented below code on 13-feb-24 for notification from setting section

                    /*$content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime; //changed content on 6-nov-23 //added space after doctor surname on 14-dec-23

                    foreach ($notify_times as $notify_time) {
                        
                        $notify_data[] = array(
                                                'patient_id'=> $request->patient_id,
                                                'appointment_id'=> $collection->id,
                                                'title'     => 'Erinnerung an Ihren Termin',
                                                'content'   => $content,
                                                'notify_time'=> $notify_time,
                                                'status'=> 0,
                                            );

                    }

                    if($this->AppointmentHasNotificationModel->insert($notify_data))
                    {
                        $all_transactions[] = 1;
                    }
                    else
                    {
                        $all_transactions[] = 0;
                    } */


                    /************added code on 13-feb-24***for notification from setting section*******/
                    $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request['start_date'])));

                    $skipNotification = false; //added on 12-nov-25


                    $getSetting  = $this->AppointmentHasNotificationModel->whereStatus(3)->first();
                    if(isset($getSetting) && !empty($getSetting))
                    {   
                      //  dump("in getsetting");


                        $title = $getSetting->title;
                        $content = $getSetting->content;
                        $day = $getSetting->day;
                        $notify_time = $getSetting->notify_time;
                        $appointmentDate =  date("Y-m-d",strtotime($request['start_date']));

                        // dump('in notify_time..');
                       //  dump($notify_time);

                        if($day==0) //current day
                        {
                            $req_notify_time   = explode(" ",$getSetting->notify_time);
                            $req_notify_time_in_seconds = $req_notify_time[1];
                            $app_notify_time   = date("Y-m-d H:i:s", strtotime($appointmentDate." ".
                                $req_notify_time_in_seconds));

                            // Log::info(strtotime($request['start_date']));
                            // Log::info(strtotime($appointmentDate . ' ' . $req_notify_time_in_seconds));          

                            // start added on 12-nov-25 for skip notification
                            $currentDate = date('Y-m-d');

                            if ($appointmentDate == $currentDate && strtotime($request['start_date']) < strtotime($appointmentDate . ' ' . $req_notify_time_in_seconds)) {
                               // Log::info("in skip condition");
                                $skipNotification = true;
                            }
                            //end 

                        }
                        else
                        {
                            //previous day
                            $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($request['start_date'])));
                            $req_notify_time   = explode(" ",$getSetting->notify_time);
                            $req_notify_time_in_seconds = $req_notify_time[1];
                            $app_notify_time = date("Y-m-d H:i:s", strtotime($previous_day." ".
                                $req_notify_time_in_seconds));
                          
                        }

                        $content = str_replace("##PATIENT_NAME##", $patientText, $content);
                        $content = str_replace("##DOCTOR_SURNAME##", $doctorSurname, $content);
                        $content = str_replace("##APPOINTMENT_TYPE##", $appointmentType, $content);
                        $content = str_replace("##DATE_TIME##", $appointmentTime, $content); 


                    }//if isset getsetting
                    else
                    {
                        $title = 'Erinnerung an Ihren Termin';
                        $content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime; 

                        $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request['start_date'])));
                    }

                    $notify_data= array(
                                        'patient_id'=> $request->patient_id,
                                        'appointment_id'=> $collection->id,
                                        'title'=>$title,
                                        'content'=> $content,
                                        'notify_time'=> $app_notify_time,
                                        'status'=> 0,
                                        );

                    //commented on 12-nov-25
                    /*if($this->AppointmentHasNotificationModel->insert($notify_data))
                    {
                        $all_transactions[] = 1;
                    }
                    else 
                    {
                        $all_transactions[] = 0; 
                    }*/


                    //start changed on 12-nov-25
                    if (!$skipNotification) 
                    {
                        if ($this->AppointmentHasNotificationModel->insert($notify_data)) {
                            $all_transactions[] = 1;
                        } else {
                            $all_transactions[] = 0; 
                        }
                    } else {
                        Log::info("Skipped notification save for appointment ID {$collection->id} — appointment time is before notify time.");
                    }
                    //end changed on 12-nov-25


                    /***********end code**of notification setting****added code on 13-feb-24*********/



                        
                    //=====================================================
                    self::_deactivateReminderNew($collection,$request->app_services);
                    //Default appintment 
                    $this->AppointmentHasExaminationsModel->where(['appointment_id'=>$id,'patient_id'=>$request->patient_id])->delete();

                    $this->AppointmentHasExaminationsModel->where(['appointment_id'=>$id,'patient_id'=>$request->patient_id])->delete();

                    Log::info("in admin dashboard controller update function before exam store");

                
                    $getServises = self::_appointmentTypesAgaintsServices($id,$request,$request->patient_id);
                    $serviceEventType = self::GetServicesEventType($id,$request->patient_id,$request->app_services,$collection->appointment_type_id,'admin');
                    // END

                    $request = array(
                                     'eventId'=>$collection->google_event_id,
                                     'summary'=>$summary,
                                     'description'=>$description,
                                     'startDateTime'=>$request->start_date,
                                     'endDateTime'=>$request->end_date,
                                     'patient_id'=>$request->patient_id,
                                     'patient_email'=>$collection->assignedPatient->email,
                                     'patient_name'=>$patientName,
                                     'color_id'=>$collection->assignedDoctor->google_color_id,
                                     'doctor_email'=>$collection->assignedDoctor->email,
                                    );
                    request()->merge($request);
                    // dd(request()->all());
                    $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventUpdate(request());
                    //$postResponse = json_decode($postCalDetails->data);
                     // dd($postCalDetails);
                    if(!empty($postCalDetails) && $postCalDetails->original['status'] == 'success')
                    {
                        // Log::info($this->ModuleTitle.'has updated appointment by DashboardController');
                        $debug_arr['data'] = 'has updated appointment by DashboardController';
                        $res_name = "DashboardController_update";   
                            //dd($debug_arr);  
                        self::debugModeappBookFun($debug_arr,$res_name);  
                        
                        $all_transactions[] = 1;
                        $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated appointment type','Update',$oldData,$newData);
                      
                    }else{
                        $all_transactions[] = 0;
                        DB::rollback();
                        $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $postCalDetails->original['msg'];
                    }
                   
                }else{
                    $all_transactions[] = 0;
                }

                //Commented on 24-march-26 for #418 send email or sms as per setting while update
                /*if (!in_array(0,$all_transactions)) 
                {
                    DB::commit();

                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']      =  route($this->ModulePath);
                    $this->JsonData['msg']      = __('admin.APPOINTMENT_UPDATED');
                }*/

                //Added on 24-march-26 for #418 send email or sms as per setting while update

                if (!in_array(0,$all_transactions)) 
                {
                    DB::commit();

                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']      =  route($this->ModulePath);
                    $this->JsonData['msg']      = __('admin.APPOINTMENT_UPDATED');

                    // ---------------------------------------------------------------
                    // Send Mail / SMS on reschedule/update added on 24-march-26 for #418
                    // ---------------------------------------------------------------
                    $updatedAppointment = $this->BaseModel
                                            ->with(['assignedPatient'])
                                            ->find($id);

                    Log::info("updatedAppointment==>");
                    Log::info($updatedAppointment);                        

                    $appointmentStartDate = date("Y-m-d", strtotime($updatedAppointment->start_date));
                    $todayDate            = date('Y-m-d');

                    if (
                        !empty($updatedAppointment->google_event_id) &&
                        !empty($updatedAppointment->patient_id) &&
                        $appointmentStartDate >= $todayDate
                    ) {
                        $channels    = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();
                        $patientData = $this->PatientsModel->where('id', $updatedAppointment->patient_id)->first();

                        if (!empty($patientData->email) && $channels->choice_of_channels == 'email') {
                            // Send Email
                            self::_sendMailAppointment($patientData->id, $updatedAppointment->google_event_id);
                        } else {
                            // Build phone number with country code
                            $phone_no     = '';
                            $country_code = (!empty($patientData)) ? $patientData->country_code : '00';

                            if (!empty($country_code)) {
                                $country_code = str_replace("00", "", $country_code);
                            } elseif (empty($country_code) || $country_code == '0') {
                                $country_code = '43'; // Austria country code
                            }

                            $country_code = str_replace("+", "", $country_code);

                            if (!empty($patientData->mobile_no)) {
                                $phone_no = $country_code . "" . str_replace("-", "", $patientData->mobile_no);
                            }

                            if (!empty($phone_no)) {
                                // Send SMS
                                self::_sendSmsAppointment($phone_no, $updatedAppointment->google_event_id);
                            } elseif (!empty($patientData->email)) {
                                // Fallback to email if no phone
                                self::_sendMailAppointment($patientData->id, $updatedAppointment->google_event_id);
                            }
                        }
                    }
                    // ------added on 24-march-26 for #418------------- 
                    
                }//if all_transactions success
            }
            else
            {
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                // $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['url']      =  route($this->ModulePath);
                $this->JsonData['msg']      = __('admin.APPOINTMENT_SLOT_ALREADY_EXIST');
            }    
            
        }
        catch(\Exception $e) {  
            DB::rollback();
            $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }
    
    public function getPatientsData($pID , Request $request)
    {   
       
        // new data
        self::_getAuthenticationForToken();

        $service = new Google_Service_Calendar($this->client);
         if(empty(Config('google_calendar_id')))
            $calendarId = 'primary';
        else
            $calendarId = Config('google_calendar_id');

        $out=[];
        $last_index = 0;
        $google_dates=[];
        $start_date = $request->start;
        $end_date = $request->end;
        
        $colors = $this->GoogleColorsModel->get();
        
        $getAppointmentRecords = [];

        $roleName = strtolower(auth()->user()->getRoleNames()->first());
        $roleDoctorAssistant = true;
        $getAppointmentRecords = $this->AppointmentModel
                                        ->where('patient_id',$pID)
                                        ->get();
                                        
        if($roleName=="doctor")
        {
            $roleDoctorAssistant = true;
            $getAppointmentRecords = $this->AppointmentModel
                                        ->where('patient_id',$pID)
                                        ->get();
        }else if($roleName=="assistant"){
            
            $roleDoctorAssistant = true;
            $doctor_id = auth()->user()->doctor_id; 
            $getAppointmentRecords = $this->AppointmentModel
                                        ->where('patient_id',$pID)
                                        ->get();
        }
        // dd($roleName,auth()->user()->hasRole('Doctor'),$getAppointmentRecords);

        $google_color = [];
        foreach ($colors as $color) {
            $google_color[$color->id] = $color->code;
        }
        dd($google_color);
       // dd($google_color);
        //$type = $request->type;
        //$start_date=Carbon::yesterday();
        //$start_date=  new Carbon('first day of January 2018');
        //$start_date=Carbon::create(2019, 6, 3, 0, 0, 0);;

        if(!empty($start_date) && !empty($end_date)){

            //On click of each day
            // $start_date=Carbon::create($request->year, $request->month,$request->day, 0, 0, 0);
            // $end_date=Carbon::create($request->year, $request->month,$request->day, 23,59, 59);  
            // Print the next 10 events on the user's calendar.
            $optParams = array(
              'maxResults' => 100,
              'orderBy' => 'startTime',
              'singleEvents' => true,
              'timeMin' => date("c", strtotime(date('Y-m-d H:i:s',strtotime($start_date)).'0 days')),
              'timeMax' => date("c", strtotime(date('Y-m-d H:i:s',strtotime($end_date)).'0 days')),
            );
            // $optParams['timeMin'] = date("c", strtotime(date('Y-m-d H:i:s').'-3 days'));
            // $optParams['timeMax'] = date("c", strtotime(date('Y-m-d H:i:s').'0 days'));
            
        }else{
            //get all events
           //  $start_date=Carbon::create(2000,1,1, 0, 0, 0);  
           //  $live_lecture_start_date = Date('Y-m-d',strtotime("2000-1-1"));
           //  $live_lecture_condition = ">=";
           // // dd($start_date);    
           //  $events = Event::get($start_date);    
            $optParams=array();
            
        }
        try{
              if(!empty($optParams) && count($optParams)>0){
                $results = $service->events->listEvents($calendarId, $optParams);
              }else{
                $results = $service->events->listEvents($calendarId);
              }  

             //dd($results->getItems());
             /* $colors = $service->colors->get();

              dd($colors);*/
              $events = $results->getItems();
              // if($type=='google' || $type=='all'){
               $date = '';
               $last_index = 0;
                foreach ($events as $key => $event) {
                    
                    $date = date("Y-m-d",strtotime($event->getStart()->getDateTime()));
                    $pushData = false;
                    if($roleDoctorAssistant == true)
                    {
                        foreach ($getAppointmentRecords as $appRec) {
                            
                            $appDate = $appRec->start_date;
                            $eventDate = date("Y-m-d H:i:s",strtotime($event->getStart()->getDateTime()));
                            if(strtotime($appDate) == strtotime($eventDate)){
                                $pushData = true;
                                break;
                            }
                           // dump($appDate,$eventDate,$pushData);

                        }

                    }
                    if($pushData==true || $roleDoctorAssistant==false){
                        $out[$last_index]['id']            =   $event->id;
                        $out[$last_index]['title']         =   ucfirst($event->getSummary()); 
                        $out[$last_index]['description']   =   ucfirst($event->getDescription()); 
                        $out[$last_index]['date']          =   strtotime($date)."000";
                        $out[$last_index]['start']         =   $event->getStart()->getDateTime();  
                        $out[$last_index]['end']           =   $event->getEnd()->getDateTime(); 

                        $out[$last_index]['backgroundColor'] =  "#f6c026";
                        
                        $color_id = $event->colorId;
                        if(!empty($color_id)){
                           $out[$last_index]['backgroundColor'] = $google_color[$color_id];
                        }

                        $out[$last_index]['allDay'] =  false;
                        //$out[$last_index]['patient_id'] =  2;
                        $out[$last_index]['patient_email'] = '';
                        if(!empty($event->attendees) && count($event->attendees)>0){
                            $out[$last_index]['patient_email'] = $event->attendees[0]['email'];
                        }
                        //dump($event->attendees);
                       /*$out[$last_index]['obj_date']      =   $date; 
                        //$out[$last_index]['obj_datetime']  =   $dateTime; 
                        $out[$last_index]['obj_time']      =   date("H:i:s",strtotime($event->getStart()->getDateTime()));
                        $out[$last_index]['end_date']      =   $event->getEnd()->getDateTime();*/ 

                        $out[$last_index]['event_type']    =   'google'; 
                        //$last_index                 =   $last_index;

                       // array_push($google_dates, $out[$last_index]['date']);

                        $last_index++;  
                    }
                                                
                }

                // $eventId='cipsr5ulf73n72v29stgfreio8';
                // $event = $service->events->get('primary', $eventId);
                // dd($event);
                //dd($out);
               // }

            }catch(Google_Service_Exception $e){
                 //dd($e);
                 //return redirect()->route('oauthCallback');     
            }
       
      //  dd($out);
        return $out;
    }

    // For Doctor
        public function getDoctorsData($dID , Request $request)
    {   
       
        // new data
        self::_getAuthenticationForToken();

        $service = new Google_Service_Calendar($this->client);
         if(empty(Config('google_calendar_id')))
            $calendarId = 'primary';
        else
            $calendarId = Config('google_calendar_id');

        $out=[];
        $last_index = 0;
        $google_dates=[];
        $start_date = $request->start;
        $end_date = $request->end;
        
        $colors = $this->GoogleColorsModel->get();
        
        $getAppointmentRecords = [];

        $roleName = strtolower(auth()->user()->getRoleNames()->first());
        $roleDoctorAssistant = true;
        $getAppointmentRecords = $this->AppointmentModel
                                        ->where('doctor_id',$dID)
                                        ->get(); 
                                        
        if($roleName=="doctor")
        {
            $roleDoctorAssistant = true;
            $getAppointmentRecords = $this->AppointmentModel
                                        ->where('doctor_id',$dID)
                                        ->get();
        }else if($roleName=="assistant"){
            
            $roleDoctorAssistant = true;
            $doctor_id = auth()->user()->doctor_id; 
            $getAppointmentRecords = $this->AppointmentModel
                                        ->where('doctor_id',$dID)
                                        ->get();
        }
        // dd($roleName,auth()->user()->hasRole('Doctor'),$getAppointmentRecords);

        $google_color = [];
        foreach ($colors as $color) {
            $google_color[$color->id] = $color->code;
        }

       // dd($google_color);
        //$type = $request->type;
        //$start_date=Carbon::yesterday();
        //$start_date=  new Carbon('first day of January 2018');
        //$start_date=Carbon::create(2019, 6, 3, 0, 0, 0);;

        if(!empty($start_date) && !empty($end_date)){

            //On click of each day
            // $start_date=Carbon::create($request->year, $request->month,$request->day, 0, 0, 0);
            // $end_date=Carbon::create($request->year, $request->month,$request->day, 23,59, 59);  
            // Print the next 10 events on the user's calendar.
            $optParams = array(
              'maxResults' => 100,
              'orderBy' => 'startTime',
              'singleEvents' => true,
              'timeMin' => date("c", strtotime(date('Y-m-d H:i:s',strtotime($start_date)).'0 days')),
              'timeMax' => date("c", strtotime(date('Y-m-d H:i:s',strtotime($end_date)).'0 days')),
            );
            // $optParams['timeMin'] = date("c", strtotime(date('Y-m-d H:i:s').'-3 days'));
            // $optParams['timeMax'] = date("c", strtotime(date('Y-m-d H:i:s').'0 days'));
            
        }else{
            //get all events
           //  $start_date=Carbon::create(2000,1,1, 0, 0, 0);  
           //  $live_lecture_start_date = Date('Y-m-d',strtotime("2000-1-1"));
           //  $live_lecture_condition = ">=";
           // // dd($start_date);    
           //  $events = Event::get($start_date);    
            $optParams=array();
            
        }
        try{
              if(!empty($optParams) && count($optParams)>0){
                $results = $service->events->listEvents($calendarId, $optParams);
              }else{
                $results = $service->events->listEvents($calendarId);
              }  

             //dd($results->getItems());
             /* $colors = $service->colors->get();

              dd($colors);*/
              $events = $results->getItems();
              // if($type=='google' || $type=='all'){
               $date = '';
               $last_index = 0;
                foreach ($events as $key => $event) {
                    
                    $date = date("Y-m-d",strtotime($event->getStart()->getDateTime()));
                    $pushData = false;
                    if($roleDoctorAssistant == true)
                    {
                        foreach ($getAppointmentRecords as $appRec) {
                            
                            $appDate = $appRec->start_date;
                            $eventDate = date("Y-m-d H:i:s",strtotime($event->getStart()->getDateTime()));
                            if(strtotime($appDate) == strtotime($eventDate)){
                                $pushData = true;
                                break;
                            }
                           // dump($appDate,$eventDate,$pushData);

                        }

                    }
                    if($pushData==true || $roleDoctorAssistant==false){
                        $out[$last_index]['id']            =   $event->id;
                        $out[$last_index]['title']         =   ucfirst($event->getSummary()); 
                        $out[$last_index]['description']   =   ucfirst($event->getDescription()); 
                        $out[$last_index]['date']          =   strtotime($date)."000";
                        $out[$last_index]['start']         =   $event->getStart()->getDateTime();  
                        $out[$last_index]['end']           =   $event->getEnd()->getDateTime(); 

                        $out[$last_index]['backgroundColor'] =  "#f6c026";
                        
                        $color_id = $event->colorId;
                        if(!empty($color_id)){
                           $out[$last_index]['backgroundColor'] = $google_color[$color_id];
                        }

                        $out[$last_index]['allDay'] =  false;
                        //$out[$last_index]['patient_id'] =  2;
                        $out[$last_index]['patient_email'] = '';
                        if(!empty($event->attendees) && count($event->attendees)>0){
                            $out[$last_index]['patient_email'] = $event->attendees[0]['email'];
                        }
                        //dump($event->attendees);
                       /*$out[$last_index]['obj_date']      =   $date; 
                        //$out[$last_index]['obj_datetime']  =   $dateTime; 
                        $out[$last_index]['obj_time']      =   date("H:i:s",strtotime($event->getStart()->getDateTime()));
                        $out[$last_index]['end_date']      =   $event->getEnd()->getDateTime();*/ 

                        $out[$last_index]['event_type']    =   'google'; 
                        //$last_index                 =   $last_index;

                       // array_push($google_dates, $out[$last_index]['date']);

                        $last_index++;  
                    }
                                                
                }

                // $eventId='cipsr5ulf73n72v29stgfreio8';
                // $event = $service->events->get('primary', $eventId);
                // dd($event);
                //dd($out);
               // }

            }catch(Google_Service_Exception $e){
                 //dd($e);
                 //return redirect()->route('oauthCallback');     
            }
       
      //  dd($out);
        return $out;
    }

    public function destroy($encID)
    {
        $this->JsonData['status']   = 'error';
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_DELETE');

        $appointmentUserID = $this->AppointmentModel->where('google_event_id', $encID)->where('status', 1)->first(); 
        if(isset($appointmentUserID))
        {
            $id = $appointmentUserID->id;
            DB::beginTransaction();             
            $collection = $this->BaseModel->find($id); 
            $collection_id = $collection->id;
            //dd($collection);
            // ===============================================================
            $timeFrame = date('H:i:s',strtotime($collection->start_date));
            $doctor_id = $collection->doctor_id;

            $time_frames_id='';

            $time_frames= $this->RosterHasDatesModel
                                ->leftjoin('roster','roster.id','roster_has_dates.roster_id')
                                ->whereDate('roster_has_dates.date',date('Y-m-d',strtotime($collection->start_date)))
                                ->where('roster.doctor_id',$doctor_id)
                                ->first();
                                
            if(!empty($time_frames))
            {
                $getrec = $this->RosterHasWeeksHasTimeFramesModel
                      ->where('week_day_id',$time_frames->week_day_id)   
                      ->where('roster_id',$time_frames->roster_id) 
                      ->where('time_frame',$timeFrame)   
                      ->where('time_frame_flag','2')
                      ->first(); 
                if(!empty($getrec))
                {
                    $oldUpdateTimeFrameFlg = $this->RosterHasWeeksHasTimeFramesModel->find($getrec->id);
               
                    $oldUpdateTimeFrameFlg->time_frame_flag = '0';
                    $oldUpdateTimeFrameFlg->comment         = 'patient_id '.$collection->patient_id.' deleted Appointment Date :'.$collection->start_date.' Appointment From  DashboardController current Date :'.date('Y-m-d H:i:s').' Time Fram Id : '.$getrec->id;
                    $oldUpdateTimeFrameFlg->save();  
                }

            }
            // ===============================================================             
                self::_activateReminderOnCancel($collection);

            // ==============deleted track============================
                $abd = self::DeletedAppointmentTrack($collection);
            // ------------------------------------
            if($collection->delete())
            {
                $newData = $collection->toArray();

                $this->AppointmentHasNotificationModel->where('appointment_id',$collection->id)->delete();

                $this->AppointmentHasExaminationsModel->where('appointment_id',$collection->id)->delete();

                $this->PatientHasDocumentsModel->where('appointment_id',$collection->id)->delete();

                
                $ids = $this->PatientsHasServiceReminderModel
                        ->where('appointment_id',$collection->id)
                        ->select('id')
                        ->get();
               
                $id_holder = [];
                if(!empty($ids) && sizeof($ids)>0)
                {
                    foreach($ids as $id=>$value)
                    {
                        $id_holder[] = $value->id;
                    }
                }

                // ==========Free roster Time frame================================

                
                $this->PatientsHasServiceReminderModel
                            ->where('appointment_id',$collection->id)
                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

               

                if(sizeof($id_holder)>0)
                {
                    
                    $reactivateReminder =  $this->PatientHasReminder
                                           ->whereIn('service_reminder_id',$id_holder)
                                           ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                                              
                }                       

                $request = array(
                                // 'eventId'=>$collection->google_event_id,
                                'eventId' => $appointmentUserID->event_id,
                            );

                request()->merge($request);
                // dd(request()->all());
                $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventDelete(request());
                //$postResponse = json_decode($postCalDetails->data);
                //dd($postCalDetails);
                if(!empty($postCalDetails) && $postCalDetails->original['status'] == 'success')
                {

                    $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData);
                    DB::commit();

                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']      = __('admin.APPOINTMENT_DELETED');
                  
                }else{
                    DB::rollback();
                    $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                    $this->JsonData['error_msg'] = $postCalDetails->original['msg'];
                }
            }
        }
        return response()->json($this->JsonData);
    }

    public function cancelAppointment($encID)
    {
        App::setLocale('de');
        $appointmentDetails = '';
        $current_date = date('Y-m-d H:i:s');
        $appointmentData = $this->AppointmentModel
                                ->join('users', 'users.id', '=', 'appointment.doctor_id')
                                ->where('appointment.google_event_id', $encID)->where('appointment.status', 1)
                                  ->where('appointment.start_date','>=',$current_date)
                                  ->whereNull('appointment.deleted_at')->first();
        if(!empty($appointmentData->start_date))
        {
            $start_date = date('H:i d.m.Y', strtotime($appointmentData->start_date));
            $newDate = explode(" ", $start_date);
            $time = @$newDate[0];
            $date = @$newDate[1];
            $docName = @$appointmentData->first_name.' '.@$appointmentData->last_name;
            if(__('admin.WARNING_TITLE_NO') == 'No')
            {
                $appointmentDetails = "Do you really want to cancel your appointment on ".$date.", ".$time." with ".$docName."?";
            }
            else {
                $appointmentDetails = "Wollen sie Ihren PureGyn Termin am ".$date.", ".$time." bei ".$docName." wirklich stornieren?";
            }
        }
        $URL=URL::current();
        $arr = explode("/cancelAppointment", $URL, 2);
        $firstURL = $arr[0];
        $this->JsonData['returnUrl']   = $firstURL;
        $this->JsonData['encID']   = $encID;
        $this->JsonData['appointmentDetails'] = $appointmentDetails;
        return view($this->ModuleView.'cancelAppointment', $this->JsonData);
    }

    public function confirmCancelAppointment($encID)
    {
        $status = 'failed';
        $current_date = date('Y-m-d H:i:s');
        $appointmentUserID = $this->AppointmentModel->where('google_event_id', $encID)->where('status', 1)
                                  ->where('start_date','>=',$current_date)->first();
        if(isset($appointmentUserID))
        {
            $id = $appointmentUserID->id;
            DB::beginTransaction();
            $collection = $this->BaseModel->find($id);
            $collection_id = $collection->id;
            // ===============================================================
            $timeFrame = date('H:i:s',strtotime($collection->start_date));
            $doctor_id = $collection->doctor_id;
            $time_frames_id='';
            $time_frames= $this->RosterHasDatesModel
                            ->leftjoin('roster','roster.id','roster_has_dates.roster_id')
                            ->whereDate('roster_has_dates.date',date('Y-m-d',strtotime($collection->start_date)))
                            ->where('roster.doctor_id',$doctor_id)
                            ->first();
            if(!empty($time_frames))
            {
                $getrec = $this->RosterHasWeeksHasTimeFramesModel
                          ->where('week_day_id',$time_frames->week_day_id)
                          ->where('roster_id',$time_frames->roster_id)
                          ->where('time_frame',$timeFrame)
                          ->where('time_frame_flag','2')
                          ->first();
                if(!empty($getrec))
                {
                    $oldUpdateTimeFrameFlg = $this->RosterHasWeeksHasTimeFramesModel->find($getrec->id);
                    $oldUpdateTimeFrameFlg->time_frame_flag = '0';
                    $oldUpdateTimeFrameFlg->comment         = 'patient_id '.$collection->patient_id.' deleted Appointment Date :'.$collection->start_date.' Appointment From  DashboardController current Date :'.date('Y-m-d H:i:s').' Time Fram Id : '.$getrec->id;
                    $oldUpdateTimeFrameFlg->save();
                }
            }
            self::_activateReminderOnCancel($collection);
            self::DeletedAppointmentTrack($collection);
            if($collection->delete())
            {
                $newData = $collection->toArray();
                $this->AppointmentHasNotificationModel->where('appointment_id',$collection->id)->delete();
                $this->AppointmentHasExaminationsModel->where('appointment_id',$collection->id)->delete();
                $this->PatientHasDocumentsModel->where('appointment_id',$collection->id)->delete();
                $ids = $this->PatientsHasServiceReminderModel
                        ->where('appointment_id',$collection->id)
                        ->select('id')->get();
                $id_holder = [];
                if(!empty($ids) && sizeof($ids)>0)
                {
                    foreach($ids as $id=>$value)
                    {
                        $id_holder[] = $value->id;
                    }
                }
                // ==========Free roster Time frame================================
                $this->PatientsHasServiceReminderModel
                            ->where('appointment_id',$collection->id)
                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                if(sizeof($id_holder) > 0)
                {
                    $reactivateReminder =  $this->PatientHasReminder
                                           ->whereIn('service_reminder_id',$id_holder)
                                           ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                }
                $request = array(
                                // 'eventId'=>$collection->google_event_id,
                                'eventId' => $appointmentUserID->event_id,
                            );
                request()->merge($request);
                $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventDelete(request());
                if(@$postCalDetails->original['status'] == 'success')
                {
                    DB::commit();
                    $status = 'success';
                }
                else {
                    DB::rollback();
                    $status = 'failed';
                }
            }
        }
        return $status;
    }
    
    public function redirectToPatient($encID)
    {
        // dd($encID);
        // Default site settings
        $appointmentUserID = $this->AppointmentModel
                                    ->where('google_event_id', $encID)
                                    ->where('status', 1)
                                    ->first(); 
        if(isset($appointmentUserID))
        {
            $id = $appointmentUserID->id;
            $appointment = $this->BaseModel->find($id);
            
            $editPatientUrl   = route('admin.patients.edit', [base64_encode(base64_encode($appointment->patient_id))]);
           

            $this->JsonData['url']     = $editPatientUrl;
            $this->JsonData['status']  = __('admin.RESP_SUCCESS');
            $this->JsonData['msg']     = 'Redirect to Patient edit page.'; 
        } 
        else
        {
            $this->JsonData['url']     = '';
            $this->JsonData['status']  = __('admin.RESP_ERROR');
            $this->JsonData['msg']     =  __('admin.NO_RESULT_FOUND');  
        }
        
        return response()->json($this->JsonData);
    }

    public function getSpecificDateRecords(Request $request)
    {
        // dd($request->all());
        $textarea_data = '';
        $div_data = __('admin.ERR_NO_NOTES_FOUND');
        $appointment_date = $request->date;
        $notice_data = $this->DashboardNoticeModel->whereDate('date',$appointment_date)->first();
        if(!empty($notice_data))
        {
            $textarea_data = $notice_data->notice;
            $div_data = $notice_data->notice;
        }
        $doctors = $this->AdminUserModel
                    ->where('status', 1)
                    ->whereHas('roles',function($query){
                       $query->where('name', 'doctor');
                    })
                    ->orWhere('id',16)
                    ->get();
        $doctors = $doctors->map(function ($item, $key)  use($appointment_date)
        {
            $doctor_id  = $item->id;
            $item->total_appoitment = $this->AppointmentModel
                                      ->where('doctor_id',$doctor_id)
                                      ->whereDate('start_date',$appointment_date)
                                      ->where('is_app_booked', 1) //added by vijay 16/4/2024 
                                      ->count();
            $roster_time_frames = $this->RosterHasDatesModel
                                    ->leftjoin('roster',function($query) use($doctor_id)
                                    {
                                        $query->on('roster.id','roster_has_dates.roster_id');
                                        $query->where('roster.doctor_id',$doctor_id);
                                    })
                                    ->whereDate('date','=',$appointment_date)
                                    ->where('roster.doctor_id',$item->id)
                                    ->get();
            $time_slot = array();
            foreach($roster_time_frames as $roster_key=>$value)
            {
                $hasDatesFromHtml= date("H",strtotime($value->from_time));
                $hasDatesToHtml= date("H",strtotime($value->to_time));
                $time_slot[] = $hasDatesFromHtml."-".$hasDatesToHtml;
            }
            if(!empty($time_slot) && count($time_slot)>0) {
                $item->available_time = implode(", ",$time_slot);
            }
            else {
                $item->available_time = '-';
            }
            return $item;
        });
        $appointment_type = $this->AppointmentTypesModel
                            // ->where('status', 1)
                            ->where('on_dashboard', '1')
                            ->get(['name','id']);
        $appointment_type = $appointment_type->map(function ($item, $key)  use($appointment_date)
        {
            $appontment_count = $this->AppointmentModel
                                ->where('appointment_type_id',$item->id)
                                ->whereDate('start_date','>=',$appointment_date)
                                ->whereDate('end_date','<=',$appointment_date)
                                ->where('is_app_booked', 1) //added by vijay 16/4/2024 
                                ->count();
            $item->count = $appontment_count;
            return $item;
        });
        $data ='<div class="current_data" id="current_data">
          <b>'.$appointment_date.'</b>
        </div>
        <div class="notice_section">
          <b>'.__('admin.TITLE_DASHBOARD_NOTE').':</b>
          <div id="notice_edit_click" style="white-space:break-spaces">'.$div_data.'</div>
          <textarea rows="3" id="calender_notice" name="calender_notice" style="display:none" required>'.$textarea_data.'</textarea>
          <span id="err_contact_name" style="display:none">'.__('admin.ERR_ENTER_NOTES_FOUND').'</span>
          <div class="button_section" style="display:none">
            <button type="button" class="btn btn-success" id="close">'.__('admin.TITLE_SAVE_BUTTON').'</button>
            <button type="button" class="btn btn-danger" id="cancel_notice">'.__('admin.TITLE_CLOSE_BUTTON').'</button>
          </div>
        </div>              
        <div class=" ">';
        foreach($doctors as $key=>$value)
        {
            $data .='<b>'.$value->last_name.':</b> '.$value->available_time.' ('.$value->total_appoitment.' '.__('admin.TITLE_APPOINTMENT_TEXT').')<br>';
        }
        $data .='</div>
        <div class="">';
        foreach($appointment_type as $key=>$value) 
        {
            $data .='<b> '.$value->name.':</b> '.$value->count.' '.__('admin.TITLE_APPOINTMENT_TEXT')."<br/>";
        }
        $data .='</div>';
        return $data;
    }
    
    public function addUpdateNotices(Request $request)
    {
        $date = $request->selectedDate;
        $text = $request->data;

        $is_exist = $this->DashboardNoticeModel->whereDate('date',$date)->first();
        
        if(!empty($is_exist))
        {
            $this->DashboardNoticeModel->where('id',$is_exist->id)->update(['date'=> $date,
                                'notice'=> $text]);
        }else
        {
            $notice_data[] = array(
                                'date'=> $date,
                                'notice'=> $text
                            );
            $this->DashboardNoticeModel->insert($notice_data);
        }
        return 'success';     
    }
    
    /**
     * Adding new code here to generate dynamic appointment types with new tables
     *
     */
    public function addDynamicAppointmentTypes($request)
    {
        try
        {
            $dynamic_appointment_name = "";
            if(!empty($request->app_services) && sizeof($request->app_services)>1)
            {
                $dynamic_appointment_type = $this->ExaminationsModel
                                                ->whereIn('id', $request->app_services)->get();
                foreach ($dynamic_appointment_type as $atypes)
                {
                    $dynamic_appointment_name = $atypes->name." + ".$dynamic_appointment_name;
                }
                $dynamic_appointment_name = rtrim($dynamic_appointment_name," + ");
            }
            /*add the condition for the duplicate check*/
            $unique_appointment_type = $this->DynamicAppointmentTypesModel
                                        ->where('name','LIKE','%'.$dynamic_appointment_name.'%')
                                        ->count();
            if($unique_appointment_type>0)
            {
                // return "dont add new";
            }
            else {
                $fk_specialist_id = '';
                if(session()->has('specialist'))
                {
                    $fk_specialist_id = session()->get('specialist');
                }
                /*Add new dynamic appointment type*/
                $collection     = new $this->DynamicAppointmentTypesModel;
                $collection->fk_specialist_id = $fk_specialist_id;
                $collection->name            = $dynamic_appointment_name;
                $collection->duration        = 10;
                $collection->description     = $dynamic_appointment_name;
                $collection->recommend_exams = 0;
                $collection->status          = '1';
                $collection->on_dashboard  = '0';
                $collection->is_dynamic = '1'; //Creating dynamic Appoinyment types and marking it 1
                //Save data
                $collection->save();
                if ($collection)
                {
                    // DEFAULT CREATE EXAMINATION
                    $ExaminationsModel = new $this->DynamicExaminationsModel;
                    $ExaminationsModel->name = $dynamic_appointment_name;
                    $ExaminationsModel->url = "";
                    $ExaminationsModel->fk_specialist_id = $fk_specialist_id;
                    $ExaminationsModel->status = 1;
                    $ExaminationsModel->default_service = 1;
                    if($ExaminationsModel->save())
                    {
                        $AppointmentTypeHasExaminationsModel = new $this->DynamicAppointmentTypesHasExaminationsModel;
                        $AppointmentTypeHasExaminationsModel->dynamic_appointment_type_id   = $collection->id;
                        $AppointmentTypeHasExaminationsModel->examination_id   = $ExaminationsModel->id;
                        $AppointmentTypeHasExaminationsModel->fk_specialist_id = $fk_specialist_id;
                        $AppointmentTypeHasExaminationsModel->save();
                    }
                    //INDIVIUALI ADD EXAMINATION
                    $all_transactions = [];
                    if (!empty($request->app_services))
                    {
                        foreach ($request->app_services as $exam)
                        {
                            $examinationObj = new $this->DynamicAppointmentTypesHasExaminationsModel;
                            $examinationObj->dynamic_appointment_type_id   = $collection->id;;
                            $examinationObj->examination_id   = $exam;
                            $examinationObj->fk_specialist_id = $fk_specialist_id;
                            if ($examinationObj->save())
                            {
                                $all_transactions[] = 1;
                            }
                            else {
                                $all_transactions[] = 0;
                            }
                        }
                    }
                    // Examination End
                    $newData = $collection->toArray();
                    $this->ActivityLogModel->addLog($this->ModuleTitle,'has created appointment type','Add',null,$newData);
                }
            }
        }
        catch(\Exception $e)
        {
            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        return true;
    }
    // ############## Roshani Added this code (22/02/2024) C) User settings ################ -->

    public function getAppointmentTypesOnDoctors(Request $request)
    {
        $doctorId = $request->input('doctor_id');
        $fromWhere = $request->input('from');

        $WebHiddenApptypeId = $request->input('WebHiddenApptypeId'); //added on 28-may-24


        // Retrieve appointment type IDs associated with the doctor
        $appointmentTypes1 = $this->UserHasAppointmentType::where('user_id', $doctorId)->pluck('appointment_type_id')->toArray();
        // Retrieve appointment types that meet the criteria
        if(isset($fromWhere) && !empty($fromWhere) && $fromWhere != null && $fromWhere == 'from_web')
        {
            //commented below code on 29-may-24
            /*$appointmentTypes = $this->AppointmentTypesModel
            ->where('status', 1)
            ->whereNotIn('id', $appointmentTypes1)
            ->where('dynamic_appointment', 0)
            ->get(['id', 'name']) // Select both 'id' and 'name' columns
            ->mapWithKeys(function ($appointmentType) {
                return [$appointmentType->id => $appointmentType->name];
            });*/

            /******* start changed below code on 29-may-24 *****************/

            if(isset($WebHiddenApptypeId) && !empty($WebHiddenApptypeId) && $WebHiddenApptypeId != 0) 
            {


                 //commented below code on 10-june-24

                /* $appointmentTypes = $this->AppointmentTypesModel
                    ->where(function($query) use ($appointmentTypes1, $WebHiddenApptypeId) {
                        $query->where('status', 1)
                              ->whereNotIn('id', $appointmentTypes1)
                              ->where('dynamic_appointment', 0)
                              ->orWhere(function($query) use ($WebHiddenApptypeId,$appointmentTypes1) {
                                  $query->where('id', $WebHiddenApptypeId)
                                  ->whereNotIn('id', $appointmentTypes1); //added on 29-may-24
                              });
                    })
                    ->get(['id', 'name']) // Select both 'id' and 'name' columns
                    ->mapWithKeys(function ($appointmentType) {
                        return [$appointmentType->id => $appointmentType->name];
                    });*/


                   $appointmentTypes = $this->AppointmentTypesModel
                    ->where(function($query) use ($appointmentTypes1, $WebHiddenApptypeId) {
                        $query->where('status', 1)
                              ->whereNotIn('id', $appointmentTypes1)
                              ->where('dynamic_appointment', 0)
                              ->orWhere(function($query) use ($WebHiddenApptypeId,$appointmentTypes1) {
                                  $query->where('id', $WebHiddenApptypeId)
                                  ->whereNotIn('id', $appointmentTypes1); //added on 29-may-24
                              });
                    })
                    ->get(['id', 'name','optimal_appointment']);   
            }
            else
            {  

                //commented below code on 10-june-24
                /*$appointmentTypes = $this->AppointmentTypesModel
                    ->where('status', 1)
                    ->whereNotIn('id', $appointmentTypes1)
                    ->where('dynamic_appointment', 0)
                    ->get(['id', 'name']) // Select both 'id' and 'name' columns
                    ->mapWithKeys(function ($appointmentType) {
                        return [$appointmentType->id => $appointmentType->name];
                    });*/

                 //changed below code on 10-june-24   
                 $appointmentTypes = $this->AppointmentTypesModel
                    ->where('status', 1)
                    ->whereNotIn('id', $appointmentTypes1)
                    ->where('dynamic_appointment', 0)
                    ->get(['id', 'name','optimal_appointment']);   


            }//else
           
            /*******end changed below code on 29-may-24 *****************/

        }else
        {
            //commented below code on 10-june-24
            /*$appointmentTypes = $this->AppointmentTypesModel
            // ->where('status', 1)
            ->whereNotIn('id', $appointmentTypes1)
            ->where('dynamic_appointment', 0)
            ->get(['id', 'name']) // Select both 'id' and 'name' columns
            ->mapWithKeys(function ($appointmentType) {
                return [$appointmentType->id => $appointmentType->name];
            });*/


            //changed below code on 10-june-24

            $appointmentTypes = $this->AppointmentTypesModel
            ->whereNotIn('id', $appointmentTypes1)
            ->where('dynamic_appointment', 0)
            ->get(['id', 'name','optimal_appointment']);
            

        }



        // Return the appointment types as JSON response
        return response()->json($appointmentTypes);
    }


    public function getDoctorsOnAppointmentTypes(Request $request)
    {
        $AppointmentTypeId = $request->input('appointmentTypeId');
        $DoctorsIds = $this->UserHasAppointmentType::where('appointment_type_id', $AppointmentTypeId)->pluck('user_id')->toArray();
        return response()->json($DoctorsIds);
    }//getDoctorsOnAppointmentTypes

    // ############## Roshani Added this code (22/02/2024) C) User settings ################ -->


    public function getDoctorsOnAppointmentTypes_reamedon30aug24(Request $request)
    {
        
        $AppointmentTypeId = $request->input('appointmentTypeId');
        $fromWhere = $request->input('from');

        $DoctorsIds = $this->UserHasAppointmentType::where('appointment_type_id', $AppointmentTypeId)->pluck('user_id')->toArray();

        if(isset($fromWhere) && !empty($fromWhere) && $fromWhere != null && $fromWhere == 'from_web')
        {
             $doctors = $this->AdminUserModel
            ->join('roster','roster.doctor_id','=','users.id')
            ->join('roster_has_dates','roster_has_dates.roster_id','=','roster.id')
            ->where('users.status', 1)
            ->whereHas('roles',function($query){
               $query->where('name', 'doctor');
            })
            ->whereDate('roster_has_dates.date','>=',date('Y-m-d'))
            ->whereNotIn('users.id', $DoctorsIds)
            ->groupBy('users.id')
            ->get([
                'users.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS name")
            ]);
        
        }
        else
        {
             $doctors = $this->AdminUserModel            
                ->whereHas('roles',function($query){
                   $query->where('name', 'doctor');
                })           
                ->whereNotIn('users.id', $DoctorsIds)
                ->groupBy('users.id')
                ->get([
                    'users.id',
                    DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS name")
                ]);

                
        }

       
        return response()->json($doctors);
    }//getDoctorsOnAppointmentTypes


    /*********start***added on 2-apr-24 for app***********************************/
    public function patientDetails($encID)
    {
        // Default site settings
        $this->ViewData['status']   = __('admin.RESP_ERROR');

        $this->ModuleTitle              = __('admin.TITLE_APPOINTMENT_TEXT'); 
        $this->ViewData['moduleTitle']  = __('admin.TITLE_MANAGE_TEXT').' '.$this->ModuleTitle;
        $this->ViewData['moduleAction'] = __('admin.TITLE_EDIT_TEXT').' '.\Illuminate\Support\Str::singular($this->ModuleTitle);

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // Appointment
       
        $appointmentUserID = $this->AppointmentModel
                                    ->where('google_event_id', $encID)
                                    ->where('status', 1)
                                    ->first(); 
        if(isset($appointmentUserID))
        {
            $id = $appointmentUserID->id;

            $this->ViewData['modulePath']   = route($this->ModulePath.'updatePatientProfile', [base64_encode(base64_encode($id))]);

            $appointment = $this->BaseModel->find($id);
       
            
            if(!empty($appointment)){
                $patients = $this->PatientsModel
                                  ->where('id', $appointment->patient_id)
                                  ->where('status', 1)
                                  ->first();
            }else{
                $patients = $this->PatientsModel
                                ->where('status', 1)
                                ->first();
            }

            $this->ViewData['appointment'] = $appointment;
            $appointment_id = $this->ViewData['appointment']->id;
          
           
            // All patients  
            $this->ViewData['patient'] = $patients;  
        
            // $this->ViewData['encID'] =  $id;  
            $this->ViewData['google_event_id'] = $encID;  //added on 8-apr-24

            $this->ViewData['result'] =  "found"; 
            $this->ViewData['status']   = __('admin.RESP_SUCCESS');
        } 
        else
        {
            $this->ViewData['result'] =  __('admin.NO_RESULT_FOUND'); 
        }
        
        return view($this->ModuleView.'patient-profile-details', $this->ViewData);
    }//

    /*************start***added on 2-apr-24 for app***********/


    /*************start***added on 2-apr-24 for app***********/

    public function updatePatientProfile(PatientsRequest $request,$encID)
    {
        //dd($request->all());

         $all_transactions = [];

        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_UPDATE');       
              
        try {

            DB::beginTransaction(); 
            

           $is_exist_patient = $this->_checkDuplicationPatient($request->family_name,$request->first_name,$request->birth_date,$request->mobile_no,'update',$id);
            if(!$is_exist_patient)
            {
                $this->JsonData['msg'] = __('admin.ERR_MOBILE_UNIQUE'); 
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                return response()->json($this->JsonData);
                exit();
            }


            $collection_old = $this->PatientsModel->find($id); 
            $collection = $this->PatientsModel->find($id);             
            $collection   = self::_storePatient($collection,$request);
            $newData = $collection->toArray();
            if ($collection)  
            {
                $ordination_patient_update = self::_updatePatientOrdination($collection,$collection_old);

                $oldPatient = self::_oldPatient($collection_old);

            }
            else{
                $all_transactions[] = 0;
            }

            if (!in_array(0,$all_transactions)) 
            {
                DB::commit();

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath);
                $this->JsonData['msg']      = __('admin.PATIENT_UPDATED');
            }else
            {
                DB::rollback();
                $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $e->getMessage();
            }
           
            
        }
        catch(\Exception $e) {  
            DB::rollback();
            $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }// updatePatientProfile



    /*************end***added on 2-apr-24 for app***********/

     /*************start***added on 2-apr-24 for app***********/

    public function checkPatient($encID)
    {
        $patientDetailsFilled = 0;

        $appointmentUserID = $this->AppointmentModel
                                    ->where('google_event_id', $encID)
                                    ->where('status', 1)
                                    ->first(); 
        if(isset($appointmentUserID))
        {
            $patientId = $appointmentUserID->patient_id;
        }
       
        $collection = $this->PatientsModel->find($patientId);   


        if ($collection)  
        {
           if(!empty($collection->first_name) && !empty($collection->family_name) && !empty($collection->email) && !empty($collection->mobile_no) && !empty($collection->gender) && !empty($collection->birth_date)) 
           {
             $patientDetailsFilled=1;

           }else{
             $patientDetailsFilled=0;
           }
           
        }
        else
        {
            $patientDetailsFilled=0;
        }
        return $patientDetailsFilled;

    }//checkPatient
    
    /*************end***added on 2-apr-24 for app***********/


    /*************start***added on 2-apr-24 for app***********/

    public function addPatientToDashboard($encID)
    {

         $all_transactions = [];

        $id = $encID;
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_UPDATE');       
              
        try {

            DB::beginTransaction(); 

            $appointmentUserID = $this->AppointmentModel
                                    ->where('google_event_id', $encID)
                                    ->where('appointment_status','!=','Aktuell')
                                    ->where('status', 1)
                                    ->first(); 
            if(isset($appointmentUserID))
            {
                $appointmentId = $appointmentUserID->id;
                $patientId     = $appointmentUserID->patient_id;
            


                $collection = $this->PatientsModel->find($patientId);             
                if($collection)  
                {
                    
                    $currentScannedQrcodeAppId = $this->AppointmentModel
                                            ->where('patient_id',$collection->id)
                                            ->whereDate('start_date',date('Y-m-d'))
                                            ->where('appointment_status','Heute')
                                            ->pluck('id')
                                            ->first();

                    if(empty($currentScannedQrcodeAppId))
                    {
                        $currentScannedQrcodeAppId = $this->AppointmentModel
                                ->where('patient_id',$collection->id)
                                ->whereDate('start_date',date('Y-m-d'))
                                ->where('appointment_status','')
                                ->pluck('id')
                                ->first();
                    }


                    if($collection->patient_status_flag =='0' && $collection->new_flag == '0')
                    {
                        $this->PatientsModel->where('id',$collection->id)
                                        ->update([
                                            'new_flag'=>'1'
                                        ]);
                    }
                    else
                    {
                        $this->PatientsModel->where('id',$collection->id)
                                        ->update([
                                            'update_ganydb'=>'1',
                                            'patient_status_flag'=>'0',
                                            'new_flag'=>'1'
                                        ]);
                    }

                    //dump($currentScannedQrcodeAppId);

                    if(isset($currentScannedQrcodeAppId))
                    {
                        $updateApprec = $this->AppointmentModel->where('id',$currentScannedQrcodeAppId)->update(['appointment_status'=>'Aktuell','assign_to_doc_dashboard'=>1]);

                        /*******added on 26-sept-24***************************************/
                        $generalCheckList = self::getAllGeneralChecklist($patientId,$appointmentId); 

                        if(isset($generalCheckList) && !empty($generalCheckList))
                        {
                             $collection = self::_createGeneralPdf($generalCheckList,$patientId,$appointmentId);

                        }//if isset generalChecklist
                        
                        /***********added on 26-sept-24************************************/

                        /******added on 09-jan-25**for google document**on button click*******/
                        
                        $getDocumentList = $this->SpecialistDocumentsModel
                        ->where('type_of_document','general')
                        ->where('status','1')
                        ->get();
                        if(!empty($getDocumentList) && sizeof($getDocumentList)>0)
                        {
                            $cnt = 0;
                            foreach ($getDocumentList as $chk_key => $chk_value) 
                            {
                                $patientDetails = $this->PatientsModel
                                   ->where('id',$patientId)
                                   ->first();
                                if(!empty($patientDetails))
                                {
                                    $hasDocument = $this->PatientHasDocumentsModel
                                     ->where('patient_id','=',$patientId)
                                     ->where('fk_document_id','=',$chk_value['id'])
                                     ->where('type','general')
                                     ->first();

                                    $getSpecilistDocument = $this->SpecialistDocumentsModel
                                               ->where('id',$chk_value['id'])
                                               ->first(); 
                                    $l_date = self::checkDocFrequency($patientId,$getSpecilistDocument);

                                    if(empty($hasDocument) && !empty($l_date))
                                    {

                                        $getrecord = new PatientHasDocumentsModel;
                                        $getrecord->patient_id       = $patientId;
                                        $getrecord->appointment_id  = $appointmentId;
                                        $getrecord->fk_document_id   = $chk_value['id'];
                                        $getrecord->type             = 'general';
                                        $getrecord->activation_start_date  = Date('Y-m-d H:i:s');
                                        $getrecord->activation_last_date   = $l_date;
                                        $getrecord->save();

                                        self::_createGeneralDocumentPdf($getrecord,$getrecord->id);

                                    }//if not empty hasDocument 

                                }// if patientDetails  

                            }//foreach getDocumentList
                        }//if getDocumentList

                        /************added on 09-jan-25*****on button click*********/
                           

                       

                        $all_transactions[] = 1;

                    }//if currentScannedQrcodeAppId
                  

                }//if collection
                else
                {
                    $all_transactions[] = 0;
                    
                }//else

            }//if
            else
            {
                $all_transactions[] = 0;
            }   

            //dump($all_transactions);

            if (!in_array(0,$all_transactions)) 
            {
                DB::commit();

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath);
                $this->JsonData['msg']      = __('admin.PATIENT_UPDATED');
            }else
            {
                DB::rollback();
                $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            }
           
            
        }
        catch(\Exception $e) {  
            DB::rollback();
            $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }// addPatientToDashboard



    /*************end***added on 2-apr-24 for app***********/

    /*************added on 26-sept-24****************************/
    public function _createGeneralPdf($inputdata,$patient_id,$appointment_id)
    {
        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = $exam_id = '';
       if(isset($inputdata) && !empty($inputdata))
       {

            foreach ($inputdata as $check_list) 
            {
                     
                $imagepath='';                
                $getDatabase = DB::connection('system')->table("tenants")
                                    ->where('ordination_id',Config('ordination_id'))->first(['uuid']);                                
                $imagepath = url('storage/tenancy/tenants/'.$getDatabase->uuid);                


                $collections = $this->CheckListModel
                                ->select('id','check_list_name','introduction_text','final_name','frequency_type','frequency','date_of_last_activation','header_image','header_image_path','footer_image','footer_image_path')
                                ->where('id',$check_list['checklist_id'])
                                ->where('status',1)
                                ->first();



                        
                if(!empty($collections))
                {    
                    //check list details 
                    
                    $data[$cnt]['signature']         = '';
                    $data[$cnt]['checklist_id']      = $collections->id;
                    $data[$cnt]['check_list_name']   = $collections->check_list_name;
                    $data[$cnt]['introduction_text'] = $collections->introduction_text;
                    $data[$cnt]['final_name']        = $collections->final_name; 
                    $data[$cnt]['currentDate']        = date("m/d/Y");

                  
                    $data[$cnt]['header_image']        = $collections->header_image;
                    $data[$cnt]['header_image_path']   = $imagepath.$collections->header_image_path;
                    $data[$cnt]['footer_image']        = $collections->footer_image;
                    $data[$cnt]['footer_image_path']   = $imagepath.$collections->footer_image_path;


                    $patientFirstName = $patientLastName = "";
                    $data[$cnt]['patientFullName']= $data[$cnt]['patientDob']= ''; 
                    $getPatientDetails = $this->PatientsModel->where('id',$patient_id)->first();
                    if(isset($getPatientDetails) && !empty($getPatientDetails))
                    {
                        $patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
                        $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
                        $data[$cnt]['patientFullName'] = $patientFirstName.' '.$patientLastName;
                        $data[$cnt]['patientDob'] = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)) :'';
                    }


                    $j = 0;
                    foreach ($check_list['heading'] as $heading) 
                    {
                        //check list heading
                        $heading_name = $this->CheckListHasHeadingSectionModel
                                        ->where('id',$heading['heading_id'])->first();

                                   
                        $data[$cnt]['heading'][$j]['fk_chk_id']= $collections->id;                
                        $data[$cnt]['heading'][$j]['heading_id']= $heading_name['id'];
                        $data[$cnt]['heading'][$j]['heading']  = $heading_name['heading_section'];

                       
                        $k=0;
                        foreach ($heading['question'] as $key => $value) 
                        {
                            //check list question
                            $question = $this->HeadingSectionHasQuestionModel
                                        ->where('id',$value['question_id'])->first();

                            $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $heading_name['id'];            
                            $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $question['id'];
                            $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $question['question'];

                              

                            if(isset($heading['question']))
                            {
                                if (in_array($value['question_id'], $heading['question']))
                                {
                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']   = 1;
                                }
                                else
                                {
                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']   = 0;
                                }
                            }
                            else
                            {
                                $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']   = 0;
                            }
                            $k++;
                        }
                        $j++;
                    }

                   
                    
                    //$PdfPath   = self::StorePath('check_list_pdf');
                    if(!empty(Config('ordination_id')))
                    {
                        $getDatabaseName = DB::connection('system')
                                    ->table("tenants")
                                    ->where('ordination_id',Config('ordination_id'))
                                    ->first(['uuid']);

                        $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/check_list_pdf/';
                    }
                    else
                    {
                        $PdfPath = '/opt/app-shared/php/data/storage/app/public/check_list_pdf/';

                    }    
                    // $PDFname = str_replace(' ', '' , $collections['check_list_name']);
                    // $PDFname   = trim($PDFname).'_'.time().'.pdf';
                    $PDFname = self::createPdfFileName($patient_id,$collections['check_list_name']);
                    //$PDFname   = $collections['check_list_name'].'_'.time().'.pdf';
                    // Invoice full path
                    $StorePath = $PdfPath.$PDFname; 

                    // echo "<pre>";print_r($data);exit;

                    $accessPath = '/check_list_pdf/'.$PDFname;
                    
                    //added by swati 19-Jul-23 to work image if ssl is changed=====================================
                     $pdf = app('dompdf.wrapper');
                     //############ Permitir ver imagenes si falla ################################
                      $contxt = stream_context_create([
                        'ssl' => [
                            'verify_peer' => FALSE,
                            'verify_peer_name' => FALSE,
                            'allow_self_signed' => TRUE,
                        ]
                    ]);

                    $pdf = \PDF::setOptions(['isHTML5ParserEnabled' => true, 'isRemoteEnabled' => true]);
                    $pdf->getDomPDF()->setHttpContext($contxt);
                    //#################################################################################
                    $PDFPath = 'admin.pdf.checkLists';  
                    $pdf->loadView($PDFPath,compact('data'))->save($StorePath);
                    // end
                    //===================================================================
                    // pdf
                    $current_date = date('Y-m-d H:i:s');               
          
                    $start_date   = null;
                    $end_date     = null;

                    switch ($collections->frequency_type) 
                    {
                        case "day":
                    
                            $days = (int)$collections->frequency;
                           
                        break;
                        case "month":

                            $days = 30 * (int)$collections->frequency;

                        break;
                        case "year":
                            
                            $days = 365 * (int)$collections->frequency;
                        break;
                    }

                    // -------------------------
                    if(!empty($days))
                    {
                        $duration  = (int)$days;
                        $last_date = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +".$duration." day");
                        $end_date    = Date('Y-m-d H:i:s',$last_date);
                        $start_date  = $current_date;
                    }
                    // ===========================================================
                     /* exam_id
                    |Check List Selected questions
                    */

                   
                    $CheckListHasSelectedQuestionModel = $this->CheckListHasSelectedQuestionModel
                                                    ->where('fk_patient_id',$patient_id)
                                                    ->where('type','general')
                                                    ->where('fk_check_list_id',$check_list['checklist_id'])
                                                    ->first();

                                             
                   
                    if(!empty($CheckListHasSelectedQuestionModel))
                    {    //dd($appointment_id);
                        $CheckListHasSelectedQuestionModel->fk_patient_id    = $patient_id;
                        $CheckListHasSelectedQuestionModel->fk_examination_id= $check_list['exam_id'];
                        $CheckListHasSelectedQuestionModel->fk_appointment_id= $appointment_id;
                        $CheckListHasSelectedQuestionModel->fk_check_list_id = $check_list['checklist_id'];
                        $CheckListHasSelectedQuestionModel->questions        = json_encode($data);
                        $CheckListHasSelectedQuestionModel->created_at       = Date('Y-m-d');
                        $CheckListHasSelectedQuestionModel->check_list_flag  = $flag;
                        $CheckListHasSelectedQuestionModel->pdf_name         = $PDFname; 
                        $CheckListHasSelectedQuestionModel->pdf_path         = $accessPath;
                        $CheckListHasSelectedQuestionModel->signature        = $file_name;
                        $CheckListHasSelectedQuestionModel->type             = 'general';
                        $CheckListHasSelectedQuestionModel->status           = '1';
                        $CheckListHasSelectedQuestionModel->activation_start_date  = $start_date;
                        $CheckListHasSelectedQuestionModel->activation_last_date   = $end_date;  
                        
                        $CheckListHasSelectedQuestionModel->save();
                    } 
                    else
                    {

                       
                        $CheckListHasSelectedQuestionModel = new $this->CheckListHasSelectedQuestionModel;

                        $CheckListHasSelectedQuestionModel->fk_patient_id    = $patient_id;
                        $CheckListHasSelectedQuestionModel->fk_examination_id= $check_list['exam_id'];
                        $CheckListHasSelectedQuestionModel->fk_appointment_id= $appointment_id;
                        $CheckListHasSelectedQuestionModel->fk_check_list_id = $check_list['checklist_id'];
                        $CheckListHasSelectedQuestionModel->questions        = json_encode($data);
                        $CheckListHasSelectedQuestionModel->created_at       = Date('Y-m-d');
                        $CheckListHasSelectedQuestionModel->check_list_flag  = $flag;
                        $CheckListHasSelectedQuestionModel->pdf_name         = $PDFname; 
                        $CheckListHasSelectedQuestionModel->pdf_path         = $accessPath;
                        $CheckListHasSelectedQuestionModel->signature        = $file_name;
                        $CheckListHasSelectedQuestionModel->type             = 'general';
                        $CheckListHasSelectedQuestionModel->status           = '0';
                        $CheckListHasSelectedQuestionModel->activation_start_date  = $start_date;
                        $CheckListHasSelectedQuestionModel->activation_last_date   = $end_date;  
                        $CheckListHasSelectedQuestionModel->save();
                    } 
              
                    $dataFinal[] = $data;
                    $data = [];
                    // ===========================================================
                    //$cnt++;
                }
            }//foreach

       }//if

        return $dataFinal;
    }//_createGeneralPdf

    public function getHeadingDetails($chk_id)
    {
        $getHeading = $this->CheckListHasHeadingSectionModel
                      ->where('fk_check_list_id',$chk_id)
                      ->get();
        $data = [];
        $cnt = 0 ;              
        if(!empty($getHeading) && sizeof($getHeading)>0)
        {
            foreach ($getHeading as $h_key => $h_value) 
            {
                $data[$cnt]['checklist_id']= $chk_id;
                $data[$cnt]['heading_id']   = $h_value['id'];
                $data[$cnt]['heading']      = $h_value['heading_section'];

                // questions
                $getQuesList = $this->HeadingSectionHasQuestionModel
                            ->where('fk_check_list_heading_section_id',$h_value['id'])
                            ->get();

                if(!empty($getQuesList) && sizeof($getQuesList)>0) 
                {
                    $i = 0;
                    foreach ($getQuesList as $q_key => $q_value) 
                    {
                        $data[$cnt]['question'][$i]['checklist_id']= $chk_id;
                        $data[$cnt]['question'][$i]['heading_id']  = $h_value['id'];
                        $data[$cnt]['question'][$i]['question_id'] = $q_value['id'];
                        $data[$cnt]['question'][$i]['question']    = $q_value['question'];
                        $data[$cnt]['question'][$i]['flag']        = 0;
                        $i++;
                    }
                }           
                
                $cnt++;
            }
        }  
    
        return $data;            
    }//getHeadingDetails


     public function checkFrequency($patient_id,$getCheckList,$value)
    {  
        $data   = [];
        $flag = 0;
        $l_date = '';
        $chk_activation_date = date('Y-m-d h:i:s',strtotime($getCheckList->date_of_last_activation));
        // ----------------------------------------------------------
        $current_date = date('Y-m-d h:i:s');               
        $start_date   = Date('Y-m-d  h:i:s',strtotime($value->activation_start_date));
        $end_date     = Date('Y-m-d  h:i:s',strtotime($value->activation_last_date));
       
        if(!empty($getCheckList))  
        {   
            $days = null;
            if(strtotime($chk_activation_date) > strtotime($start_date))
            {
                $flag = 1;
            }
            else if(strtotime($current_date) > strtotime($end_date))
            {
                $flag = 1;
            }
            
            if($flag == 1)
            {
                switch ($getCheckList->frequency_type) 
                {
                    case "day":
                        $days = (int)$getCheckList->frequency;
                    break;
                    case "month":
                        $days = 30 * (int)$getCheckList->frequency;
                    break;
                    case "year":
                        $days = 365 * (int)$getCheckList->frequency;
                    break;
                }
                if(!empty($days))
                {
                    $duration  = (int)$days;
                    $last_date = strtotime(date("Y-m-d h:i:s", strtotime($current_date)) . " +".$duration." day");
                    $l_date    = Date('Y-m-d h:i:s',$last_date);
                }
            }
        }  
        return $l_date; 
    }//checkFrequency

     // GET GENERAL CHECK LISR
    public function getAllGeneralChecklist($patient_id,$appointment_id)
    {


        $errors     = [];  
        $data       = $data_collection = []; 

        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND'); 
        $status     = false;

        $getcheckList = $this->CheckListModel
                        ->where('type_of_checklist','general')
                        ->where('status',1)
                        ->limit(1)
                        ->get();
                 
        if(!empty($getcheckList) && sizeof($getcheckList)>0)
        {
            $cnt = 0;
            foreach ($getcheckList as $chk_key => $chk_value) 
            {
                $patientDetails = $this->PatientsModel
                                   ->where('id',$patient_id)
                                   ->first();
               
                if(!empty($patientDetails))
                {
                    $hasDocument = $this->CheckListHasSelectedQuestionModel 
                        ->where('fk_patient_id',$patient_id)
                        //->where('fk_appointment_id',$appointment_id)
                        ->where('fk_check_list_id',$chk_value['id'])
                        ->where('type','general')
                        ->first();

                       
                  
                    if(!empty($hasDocument) && ($hasDocument->count() > 0))
                    {
                        
                            $chk_id = $hasDocument->fk_check_list_id;
                           
                            $chkList = $this->CheckListModel
                                        ->where('status',1) 
                                        ->find($chk_id);

                            if(!empty($chkList))
                            {
                                $l_date = self::checkFrequency($patient_id,$chkList,$hasDocument); 
                               
                                if(!empty($l_date))
                                {
                                    $data[$cnt]['chk_type']          = $chkList->type_of_checklist;  
                                    $data[$cnt]['exam_id']          = '';    
                                    $data[$cnt]['checklist_id']      = $chkList->id;
                                    $data[$cnt]['check_list_name']   = $chkList->check_list_name;
                                    $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                    $data[$cnt]['final_name']        = $chkList->final_name;
                                   
                                    $getHEading = self::getHeadingDetails($chkList->id);
                                  
                                    $data[$cnt]['heading'] = $getHEading;
                                   
                                    $cnt++;
                                }
                            }
                    }
                    else
                    {
                        $data[$cnt]['chk_type']          = $chk_value['type_of_checklist']; 
                         $data[$cnt]['exam_id']          = '';       
                        $data[$cnt]['checklist_id']      = $chk_value['id'];
                        $data[$cnt]['check_list_name']   = $chk_value['check_list_name'];
                        $data[$cnt]['introduction_text'] = $chk_value['introduction_text'];
                        $data[$cnt]['final_name']        = $chk_value['final_name'];

                        $getHEading = self::getHeadingDetails($chk_value['id']);
                        $data[$cnt]['heading'] = $getHEading;

                        $cnt++;
                       
                    }  
                }      
            }
        }             

        return $data;
    }//getAllGeneralChecklist

    /**************added on 26-sept-24*******************/

    
    /*************start***added on 8-apr-24 for app***********/

     public function checkAppointmentStatus($encID)
    {

        $appointmentStatus = 0;

        $getAppointmentStatus = $this->AppointmentModel
                                    ->where('google_event_id', $encID)
                                    //->where('appointment_status','=','Aktuell')
                                    ->where(function ($query) {
                                        $query->where('appointment_status', '=', 'Aktuell')
                                            ->orWhere('appointment_status', '=', 'fertig');
                                    })
                                    //->where('assign_to_doc_dashboard',1)
                                    ->where('status', 1)
                                    ->first(); 
        
        if (isset($getAppointmentStatus) && !empty($getAppointmentStatus))  
        {
             $appointmentStatus=1;
        }
        else
        {
            $appointmentStatus=0;
        }
        return $appointmentStatus;

    }//checkPatient
    
    /*************end***added on 8-apr-24 for app***********/

    public function checkPdf()
    {
        dump("start");
        $patient_id=46967;
        $appointment_id=96612;
        $exam_id=115;
        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = '';
        //dd($patient_id,$appointment_id,$exam_id);
        $examinations_details = $this->ExaminationsHasMultipleCheckListModel
                                ->where('fk_examinations_id',$exam_id)
                                ->get();


         /*************Added on 26-dec-22***********/     
        $imagepath='';                
        // $getDatabase = DB::connection('system')->table("tenants")
        //                     ->where('ordination_id',Config('ordination_id'))->first(['uuid']);                               
        // $imagepath = url('storage/tenancy/tenants/'.$getDatabase->uuid);                
        /*************Added on 26-dec-22***********/                                    
                             
        $flag = 0;
        $check_list_status = '0';
        if(!empty($examinations_details))
        {
            foreach ($examinations_details as $exam_key => $exam_val) 
            {
                $CheckListHasSelectedQuestionModel = $this->CheckListHasSelectedQuestionModel
                         ->where('fk_patient_id',$patient_id)
                         ->where('fk_appointment_id',$appointment_id)
                         ->where('fk_examination_id',$exam_id)
                         ->first();
                if(!empty($CheckListHasSelectedQuestionModel))
                {
                    $cnttt = 0;
                    $cnt   = 0;
                    $flag  = 1;
                    $chk_id=0;
                    $myStatus = explode(',', $CheckListHasSelectedQuestionModel->status);
                    // if (!in_array('2', $myStatus))
                    // {
                    //     $status = $CheckListHasSelectedQuestionModel->status.',2';
                    //     $re_status  = str_replace("0,", "", $status);
                    //     $check_list_status = ltrim($re_status,',');
                       
                    // }
                    //else $check_list_status = $CheckListHasSelectedQuestionModel->status;
                    $check_list_status = $CheckListHasSelectedQuestionModel->status;
                    $chk_id = $CheckListHasSelectedQuestionModel->fk_check_list_id;
                    if($CheckListHasSelectedQuestionModel)
                    {
                        $check_list        = json_decode($CheckListHasSelectedQuestionModel['questions'],true);
                        if($check_list)
                        {   
                            foreach ($check_list as $ck => $cval) 
                            {
                                $getcheckList = $this->CheckListModel
                                                ->find($cval['checklist_id']);

                                $chk_id = $cval['checklist_id'];

                                if($cval['signature'] !=null)
                                {
                                    $data[$cnt]['signature'] = $cval['signature'];
                                }
                                else
                                {
                                    $data[$cnt]['signature']         = '';
                                    $flag = '0';
                                }
                                /******* Added by roshani for display patient info ******/
                                // Get the patient details
                                $patientData = self::setPatientDetails($patient_id);
                                // Initialize $data[$cnt] with patient data
                                if(isset($patientData) && !empty($patientData))
                                {
                                    $data[$cnt] = $patientData;
                                }
                                /******* Added by roshani for display patient info ******/

                                $data[$cnt]['checklist_id']      = $cval['checklist_id'];
                                $data[$cnt]['check_list_name']   = $cval['check_list_name'];
                                $data[$cnt]['introduction_text'] = $cval['introduction_text'];
                                $data[$cnt]['final_name']        = $cval['final_name'];
                                //$data[$cnt]['signDoc']           = $getcheckList->signDoc;

                                /*******Added by divya on 26-dec-22*********/ 
                                    $data[$cnt]['header_image']        = isset($getcheckList->header_image)?$getcheckList->header_image:'';
                                    $data[$cnt]['header_image_path']   = isset($getcheckList->header_image_path)? $imagepath.$getcheckList->header_image_path:'';
                                    $data[$cnt]['footer_image']        = isset($getcheckList->footer_image)?$getcheckList->footer_image:'';
                                    $data[$cnt]['footer_image_path']   = isset($getcheckList->footer_image_path)?$imagepath.$getcheckList->footer_image_path:'';

                                /*******Added by divya on 26-dec-22*********/ 
                               

                                $j = 0;
                                foreach ($cval['heading'] as $heading) 
                                {
                                    //dd($heading['question']);
                                    //check list heading
                                    $data[$cnt]['heading'][$j]['fk_chk_id']= $heading['fk_chk_id'];                
                                    $data[$cnt]['heading'][$j]['heading_id']= $heading['heading_id'];
                                    $data[$cnt]['heading'][$j]['heading']  = $heading['heading'];
                                   
                                     $k=0;
                                    foreach ($heading['question'] as $key => $value) 
                                    {
                                        //check list question
                                        $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_chk_id'] = $heading['fk_chk_id']; 
                                        $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $value['question']['fk_heading_id'];            
                                        $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $value['question']['question_id'];
                                        $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $value['question']['question'];
                                        $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']     = $value['question']['flag'];
                                        $k++;
                                    }
                                    $j++;
                                }
                            }
                          
                        }
                    }
                    else
                    {
                       $data_final =[];
                    }
                    if(empty($data)){
                        $collections = $this->CheckListModel
                                ->where('id',$exam_val['fk_check_list_id'])
                                ->where('status',1)
                                ->first();
                        $chk_id = $collections->id;
                        //if($collections->signDoc == 'read')  $check_list_status = '1';
                        if(!empty($collections))
                        {   
                        /******* Added by roshani for display patient info ******/
                        // Get the patient details
                        $patientData = self::setPatientDetails($patient_id);
                        // Initialize $data[$cnt] with patient data
                        if(isset($patientData) && !empty($patientData))
                        {
                            $data[$cnt] = $patientData;
                        }
                        /******* Added by roshani for display patient info ******/
                            $data[$cnt]['signature']         = '';
                            $data[$cnt]['checklist_id']      = $collections->id;
                            $data[$cnt]['check_list_name']   = $collections->check_list_name;
                            $data[$cnt]['introduction_text'] = $collections->introduction_text;
                            $data[$cnt]['final_name']        = $collections->final_name;
                            $data[$cnt]['fk_exam_id']        = $exam_id;

                            /*******Added by divya on 26-dec-22*********/ 
                            $data[$cnt]['header_image']        = isset($collections->header_image)?$collections->header_image:'';
                            $data[$cnt]['header_image_path']   = isset($collections->header_image_path)? $imagepath.$collections->header_image_path:'';
                            $data[$cnt]['footer_image']        = isset($collections->footer_image)?$collections->footer_image:'';
                            $data[$cnt]['footer_image_path']   = isset($collections->footer_image_path)?$imagepath.$collections->footer_image_path:'';

                           /*******Added by divya on 26-dec-22*********/ 
                            $j = 0;
                            $heading = $this->CheckListHasHeadingSectionModel
                                                ->where('fk_check_list_id',$collections->id)->get();
                            foreach ($heading as $heading) 
                            {
                                //check list heading
                                $data[$cnt]['heading'][$j]['fk_chk_id'] = $collections->id;                
                                $data[$cnt]['heading'][$j]['heading_id']= $heading['id'];
                                $data[$cnt]['heading'][$j]['heading']   = $heading['heading_section'];
                               
                                //check list question
                                $k=0;
                                $question = $this->HeadingSectionHasQuestionModel
                                           ->where('fk_check_list_heading_section_id',$heading['id'])
                                           ->get();
                                foreach ($question as $keyv => $valque) 
                                {
                                    
                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $heading['id'];           
                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $valque['id'];
                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $valque['question'];
                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']     = 0;

                                    $k++;
                                }
                               
                                $j++;
                            }
                        }
                    }
                    $cnt++;
                    $collections = $this->CheckListModel
                                ->where('id',$chk_id)
                                ->where('status',1)
                                ->first();
                }
                else
                {
                    $flag = 1;
                    $check_list_status = '0';
                    $CheckListHasSelectedQuestionModel = new $this->CheckListHasSelectedQuestionModel;
                    $collections = $this->CheckListModel
                                ->where('id',$exam_val['fk_check_list_id'])
                                ->where('status',1)
                                ->first();
                    $chk_id = $collections->id;
                    //if($collections->signDoc == 'read')  $check_list_status = '1';
                    if(!empty($collections))
                    {    
                        /******* Added by roshani for display patient info ******/
                        // Get the patient details
                        $patientData = self::setPatientDetails($patient_id);
                        // Initialize $data[$cnt] with patient data
                        if(isset($patientData) && !empty($patientData))
                        {
                            $data[$cnt] = $patientData;
                        }
                        /******* Added by roshani for display patient info ******/
                        $data[$cnt]['signature']         = '';
                        $data[$cnt]['checklist_id']      = $collections->id;
                        $data[$cnt]['check_list_name']   = $collections->check_list_name;
                        $data[$cnt]['introduction_text'] = $collections->introduction_text;
                        $data[$cnt]['final_name']        = $collections->final_name;
                        $data[$cnt]['fk_exam_id']        = $exam_id;

                        /*******Added by divya on 26-dec-22*********/ 
                        $data[$cnt]['header_image']        = isset($collections->header_image)?$collections->header_image:'';
                        $data[$cnt]['header_image_path']   = isset($collections->header_image_path)? $imagepath.$collections->header_image_path:'';
                        $data[$cnt]['footer_image']        = isset($collections->footer_image)?$collections->footer_image:'';
                        $data[$cnt]['footer_image_path']   = isset($collections->footer_image_path)?$imagepath.$collections->footer_image_path:'';

                       /*******Added by divya on 26-dec-22*********/ 


                        $j = 0;
                        $heading = $this->CheckListHasHeadingSectionModel
                                            ->where('fk_check_list_id',$collections->id)->get();
                        foreach ($heading as $heading) 
                        {
                            //check list heading
                            $data[$cnt]['heading'][$j]['fk_chk_id'] = $collections->id;                
                            $data[$cnt]['heading'][$j]['heading_id']= $heading['id'];
                            $data[$cnt]['heading'][$j]['heading']   = $heading['heading_section'];
                           
                            //check list question
                            $k=0;
                            $question = $this->HeadingSectionHasQuestionModel
                                       ->where('fk_check_list_heading_section_id',$heading['id'])
                                       ->get();
                            foreach ($question as $keyv => $valque) 
                            {
                                
                                $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $heading['id'];           
                                $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $valque['id'];
                                $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $valque['question'];
                                $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']     = 0;

                                $k++;
                            }
                           
                            $j++;
                        }
                    }
                }  

                // PDF Generate
                //$PdfPath   = self::StorePath('check_list_pdf/');
                // if(!empty(Config('ordination_id')))
                // {
                //     $getDatabaseName = DB::connection('system')
                //                 ->table("tenants")
                //                 ->where('ordination_id',Config('ordination_id'))
                //                 ->first(['uuid']);

                //     $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/check_list_pdf/';
                // }
                // else
                // {
                //     $PdfPath = '/opt/app-shared/php/data/storage/app/public/check_list_pdf/';
                // }
                $PdfPath   = storage_path().'/check_list_pdf/';

                //$PDFname   = $collections->check_list_name.'_'.time().'.pdf';
                // $PDFname = str_replace(' ', '' , $collections->check_list_name);
                // $PDFname   = trim($PDFname).'_'.time().'.pdf';

                $PDFname = self::createPdfFileName($patient_id,$collections->check_list_name);

                // Invoice full path
                $StorePath = $PdfPath.$PDFname; 
                $accessPath = '/check_list_pdf/'.$PDFname;
                
                
                //PDF::loadView($PDFPath,compact('data'))->save($StorePath);
                //added by swati 19-Jul-23 to work image if ssl is changed=====================
                 $pdf = app('dompdf.wrapper');
                 //############ Permitir ver imagenes si falla ################################
                  $contxt = stream_context_create([
                    'ssl' => [
                        'verify_peer' => FALSE,
                        'verify_peer_name' => FALSE,
                        'allow_self_signed' => TRUE,
                    ]
                ]);
                $pdf = \PDF::setOptions(['isHTML5ParserEnabled' => true, 'isRemoteEnabled' => true]);
                $pdf->getDomPDF()->setHttpContext($contxt);
                //#################################################################################
                // log::info("generateChecklistPDFNew-appointmentAgreementcntroller");
                $PDFPath = 'admin.pdf.checkLists';  
                $pdf->loadView($PDFPath,compact('data'))->save($StorePath); 
                $filename =  $PdfPath.'/'.$PDFname;
                dd($filename);    
                /*
                |Check List Selected questions
                */
                $CheckListHasSelectedQuestionModel->fk_patient_id    = $patient_id;
                $CheckListHasSelectedQuestionModel->fk_examination_id= $exam_id;
                $CheckListHasSelectedQuestionModel->fk_appointment_id= $appointment_id;
                $CheckListHasSelectedQuestionModel->fk_check_list_id = $chk_id;
                $CheckListHasSelectedQuestionModel->questions        = json_encode($data);
                $CheckListHasSelectedQuestionModel->created_at       = Date('Y-m-d');
                $CheckListHasSelectedQuestionModel->check_list_flag  = 0;
                $CheckListHasSelectedQuestionModel->pdf_name         = $PDFname; 
                $CheckListHasSelectedQuestionModel->pdf_path         = $accessPath; 
                $CheckListHasSelectedQuestionModel->signature        = $file_name;
                $CheckListHasSelectedQuestionModel->type             = 'performance';
                $CheckListHasSelectedQuestionModel->status         = $check_list_status;
                $CheckListHasSelectedQuestionModel->save();
                //$cnt++;
            }
        }
        dump("end");
        
                                          
        return $data;
    }//


    //start added on 4-nov-24
    public function updateHeight(Request $request)
    {
        $height = $request->height;
        $all_transactions = [];

        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_UPDATE');       
              
        try {

            DB::beginTransaction(); 

            $updateApprec = DB::table('users')->update(['default_height'=>$height]);
            if($updateApprec){
                $all_transactions[] = 1;
            }
            else
            {
                $all_transactions[] = 0;
            }  

            if (!in_array(0,$all_transactions)) 
            {
                DB::commit();

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath);
                $this->JsonData['msg']      = __('admin.HEIGHT_UPDATED');
            }else
            {
                DB::rollback();
                $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            }
           
            
        }
        catch(\Exception $e) {  
            DB::rollback();
            $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }// updateHeight
    //end added on 4-nov-24  




    //added on 09-jan-25 for general document
    public function checkDocFrequency($patient_id,$getDocument)
    {  
        
        $data = [];
        $flag = 0;
        $l_date = '';
        $current_date = date('Y-m-d H:i:s'); 
        $activation_date = null ;
        $start_date = $end_date =null;
        if(!empty($getDocument['date_of_last_activation']) && $getDocument['date_of_last_activation']!= "0000-00-00 00:00:00")
        {
            $activation_date = date('Y-m-d H:i:s',strtotime($getDocument['date_of_last_activation']));
        }
        
       
        $patientHasDoc = $this->PatientHasDocumentsModel
                        ->where('patient_id',$patient_id)
                        ->where('fk_document_id',$getDocument['id'])
                        ->first();
        
        
        
        if(!empty($patientHasDoc))  
        {   
            $status = explode(',', $patientHasDoc->doc_status);
           
            if(in_array('0', $status))
            {
                $flag = 1;
            }
            else
            {

                $start_date   = Date('Y-m-d H:i:s',strtotime($patientHasDoc['activation_start_date']));
                $end_date     = Date('Y-m-d H:i:s',strtotime($patientHasDoc['activation_last_date']));
                $days = null;
                //dump(strtotime($current_date), strtotime($end_date));
                if(strtotime($activation_date) > strtotime($start_date) && !empty($activation_date))
                {
                    $flag = 1;
                }
                else if(strtotime($current_date) < strtotime($end_date))
                {
                    $flag = 1;
                }
                
                $flag = 1;
                
            }
          
        } 
        else
        {
            $flag = 1;
        }
        //dd($flag);
        if($flag == 1)
        {
            if(!empty($getDocument->frequency_type))
            {   //dd($getDocument->frequency);
                switch ($getDocument->frequency_type) 
                {
                    case "day":
                        $days = (int)$getDocument->frequency;
                    break;
                    case "month":
                        $days = 30 * (int)$getDocument->frequency;
                    break;
                    case "year":
                        $days = 365 * (int)$getDocument->frequency;
                    break;
                }
            }
            else
            {
                $l_date = $current_date;
            }
           
            if(!empty($days) || $days == 0)
            {
                
                $duration  = (int)$days;
               
                $last_date = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +".$duration." day");
               
                $l_date    = Date('Y-m-d H:i:s',$last_date);
            }
        } 
       
        return $l_date;
    }//

    //added on 09-jan-25 for general document
    public function _createGeneralDocumentPdf($doc_details,$id)
    {
        $data = $dataFinal = [];
        $flag = '0';
        $file_name ='';
      
        $collections = $this->SpecialistDocumentsModel->find($doc_details->fk_document_id);
                
        if(!empty($collections))
        {    

            //to get the header and footer path                
            $header_image_path = self::getFilePath($collections['header_image_path']);
            $footer_image_path = self::getFilePath($collections['footer_image_path']);
            

            $data['doc_id']            = $collections->id;
            $data['name']              = $collections->name;
            $data['html_text']         = $collections->html_text;
            $data['background_color']  = $collections->background_color;
            $data['header_image']      = $collections->header_image;
            // $data['header_image_path'] = $collections->header_image_path; 
            $data['header_image_path'] = $header_image_path; 
            $data['footer_image']      = $collections->footer_image;
            // $data['footer_image_path'] = $collections->footer_image_path; 
            $data['footer_image_path'] = $footer_image_path;  
            $data['background_color']  = $collections->background_color;
            $data['signature']         = $doc_details->remarks;


            /******start***Get Patient details **********/
            $patientFirstName = $patientLastName = $patientFullName= $patientDob= ''; 
            $getPatientDetails = $this->PatientsModel->where('id',$doc_details->patient_id)->first();
            if(isset($getPatientDetails) && !empty($getPatientDetails))
            {
                $patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
                $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
                $patientFullName = $patientFirstName.' '.$patientLastName;
                $patientDob = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
            } 
            
            $data['patientFullName'] = $patientFullName;
            $data['patientDob'] = $patientDob;
            $data['currentDate'] = date('m/d/Y');
            /********end**patient details***********************/


            
            //$PdfPath = self::StorePath('document_pdf/');
            if(!empty(Config('ordination_id')))
            {
                $getDatabaseName = DB::connection('system')
                            ->table("tenants")
                            ->where('ordination_id',Config('ordination_id'))
                            ->first(['uuid']);

                $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/document_pdf/';
            }
            else
            {
                $PdfPath = '/opt/app-shared/php/data/storage/app/public/document_pdf/';
            }
            //$PdfPath   = storage_path().'/app/public/document_pdf/';
            //$PDFname   = $collections['name'].'_'.time().'.pdf';
            // $PDFname = str_replace(' ', '' , $collections['name']);
            // $PDFname   = trim($PDFname).'_'.time().'.pdf';
            $PDFname = self::createPdfFileName($doc_details->patient_id,$collections['name']);
            // Invoice full path
            $StorePath = $PdfPath.$PDFname; 
            $accessPath = '/document_pdf/'.$PDFname;
            
            //added by swati 19-Jul-23 to work image if ssl is changed=====================================
             $pdf = app('dompdf.wrapper');
             //############ Permitir ver imagenes si falla ################################
              $contxt = stream_context_create([
                'ssl' => [
                    'verify_peer' => FALSE,
                    'verify_peer_name' => FALSE,
                    'allow_self_signed' => TRUE,
                ]
            ]);

            $pdf = \PDF::setOptions(['isHTML5ParserEnabled' => true, 'isRemoteEnabled' => true]);
            $pdf->getDomPDF()->setHttpContext($contxt);
            //#################################################################################
            // log::info("_createGeneralDocumentPdf-appointmentAgreementcntroller");
            $PDFPath = 'admin.pdf.documentLists';  
            $pdf->loadView($PDFPath,compact('data'))->save($StorePath);
            // end
            //========================================================================
            // pdf
            $current_date = date('Y-m-d H:i:s');               
  
            $start_date   = null;
            $end_date     = null;
            $days = null;
            if(!empty($collections->frequency_type))
            {
                switch ($collections->frequency_type) 
                {
                    case "day":
                
                        $days = (int)$collections->frequency;
                       
                    break;
                    case "month":

                        $days = 30 * (int)$collections->frequency;

                    break;
                    case "year":
                        
                        $days = 365 * (int)$collections->frequency;
                    break;
                }
            }
            // -------------------------
            if(!empty($days))
            {
                $duration  = (int)$days;
                $last_date = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +".$duration." day");
                $end_date    = Date('Y-m-d H:i:s',$last_date);
                $start_date  = $current_date;
            }
            // ===========================================================
            /* exam_id
                |Check List Selected questions inputdata
            */
            $patient_doc = $this->PatientHasDocumentsModel->find($doc_details->id);
            $patient_doc->pdf_name    = $PDFname;
            $patient_doc->pdf_path    = $accessPath;
            $patient_doc->save();
            $dataFinal[] = $data;
            $data = [];
            // ===========================================================
            //$cnt++;
        }
        // dd($dataFinal);
        return $dataFinal;
    }//



     //Did changes on 14-march-25
    public function updateRemindersOfKosten_1($patientID)
    {
        $patient_id=base64_decode(base64_decode($patientID));

        log::info("in updateRemindersOfKosten function of single url..for patient id..");
        log::info("patient_id");
        Log::info($patient_id);
        dump($patient_id);

        $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')
                        ->where(
                            [                                       
                           // 'is_reminder_updated' => '1',
                            'type' =>'service',
                            'service_id'=>183
                            ]
                        )->get();
            // echo "update<pre>";print_r($is_service_has_reminder);exit;
            if(!empty($is_service_has_reminder))
            {
                foreach($is_service_has_reminder as $key=>$value)
                {
                    $is_service_reminder_checked = DB::table('examinations')->where(
                        [
                        'id' => $value->service_id,
                        'show_as_reminder' => '1',
                            // 'status' => '1'
                        ])
                        ->whereNull('deleted_at')
                        ->first();
                    // print_r($is_service_reminder_checked);exit;
                    if(!empty($is_service_reminder_checked))
                    {
                        dump("in updateRemindersOfKosten function of is_service_reminder_checked....");
                        log::info("in updateReminders function of is_service_reminder_checked....");
                        log::info("in function is running for service id..===>");
                        dump($value->service_id);




                        $all_patinet_ids = DB::table('patient_has_service_reminder')
                                            ->leftjoin('patients','patients.id','=','patient_has_service_reminder.patient_id')
                                            // ->where('service_id',$value->service_id)
                                            ->where('service_id',185)
                                            ->whereNull('patients.deleted_at')
                                            ->whereNull('patient_has_service_reminder.deleted_at')
                                            ->where('patient_id',$patient_id)
                                            // ->where('appointment_id','!=',0)
                                            ->groupby('patient_id','appointment_id')
                                            ->get(['patient_has_service_reminder.*']);
                        // echo "<pre>";print_r($all_patinet_ids);exit;
                        //dd(count($all_patinet_ids));
                        foreach($all_patinet_ids as $p_key=>$p_value)
                        {
                           
                            $checkReminder2Exists = DB::table('appointment_has_examinations')
                            ->select('examination_id')
                            ->where('appointment_id',$p_value->appointment_id)
                            ->where('patient_id',$p_value->patient_id)
                            ->where('examination_id',$value->service_id)
                            ->first();

                            if(isset($checkReminder2Exists) && !empty($checkReminder2Exists))
                            {
                                $appoitment_data = DB::table('appointment')
                                ->where('id',$p_value->appointment_id)->first();

                                $patinet_data = DB::table('patients')
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
                               
                                log::info($p_value->appointment_id);
                                if($p_value->appointment_id!=0){
                                    dump("in appointment_id not 0 ".$p_value->patient_id);
                                    $this->_checkAndAddServiceReminderU($p_value->patient_id,$value->service_id,$p_value->appointment_id,$ap_start_date,$data);
                                }
                            }//if checkReminder2Exists
                            else{
                                dump("reminder2 service is not exists in database..");
                            }
                        }//foreach
                  
                    }//if
                }
            }
            else
            {
               log::info("No reminder parameter changed for the service...");
               // dump('No reminder parameter changed for the service...');
            }
    }//updateRemindersOfKosten

    //added on 14-march-25
    public function updateRemindersOfKosten_multi($patientIDs)
    {
        dump("in updateRemindersOfKosten function of multiple patient ids..");

        $decodedIDs = array_map(function ($id) {
            return base64_decode(base64_decode($id));
        }, explode(',', $patientIDs));

        //$patient_id=base64_decode(base64_decode($patientID));

        log::info("in updateRemindersOfKosten function of single url..for patient id..");
        log::info("decodedIDs");
        Log::info($decodedIDs);
        dump($decodedIDs);

        $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')
                        ->where(
                            [                                       
                           // 'is_reminder_updated' => '1',
                            'type' =>'service',
                            'service_id'=>183
                            ]
                        )->get();
            // echo "update<pre>";print_r($is_service_has_reminder);exit;
            if(!empty($is_service_has_reminder))
            {
                foreach($is_service_has_reminder as $key=>$value)
                {
                    $is_service_reminder_checked = DB::table('examinations')->where(
                        [
                        'id' => $value->service_id,
                        'show_as_reminder' => '1',
                            // 'status' => '1'
                        ])
                        ->whereNull('deleted_at')
                        ->first();
                    // print_r($is_service_reminder_checked);exit;
                    if(!empty($is_service_reminder_checked))
                    {
                        dump("in updateRemindersOfKosten function of is_service_reminder_checked....");
                        log::info("in updateReminders function of is_service_reminder_checked....");
                        log::info("in function is running for service id..===>");
                        dump($value->service_id);


                        $all_patinet_ids = DB::table('patient_has_service_reminder')
                                            ->leftjoin('patients','patients.id','=','patient_has_service_reminder.patient_id')
                                            // ->where('service_id',$value->service_id)
                                            ->where('service_id',185)
                                            ->whereNull('patients.deleted_at')
                                            ->whereNull('patient_has_service_reminder.deleted_at')
                                            // ->where('patient_id',$patient_id)
                                            ->whereIn('patient_id', $decodedIDs)  // Handle multiple patient IDs
                                            // ->where('appointment_id','!=',0)
                                            ->groupby('patient_id','appointment_id')
                                            ->get(['patient_has_service_reminder.*']);
                        // echo "<pre>";print_r($all_patinet_ids);exit;
                        //dd(count($all_patinet_ids));
                        foreach($all_patinet_ids as $p_key=>$p_value)
                        {
                           
                            $checkReminder2Exists = DB::table('appointment_has_examinations')
                            ->select('examination_id')
                            ->where('appointment_id',$p_value->appointment_id)
                            ->where('patient_id',$p_value->patient_id)
                            ->where('examination_id',$value->service_id)
                            ->first();

                            if(isset($checkReminder2Exists) && !empty($checkReminder2Exists))
                            {
                                $appoitment_data = DB::table('appointment')
                                ->where('id',$p_value->appointment_id)->first();

                                $patinet_data = DB::table('patients')
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
                               
                                log::info($p_value->appointment_id);
                                if($p_value->appointment_id!=0){
                                    dump("in appointment_id not 0 ".$p_value->patient_id);
                                    $this->_checkAndAddServiceReminderU($p_value->patient_id,$value->service_id,$p_value->appointment_id,$ap_start_date,$data);
                                }
                            }//if checkReminder2Exists
                            else{
                                dump("reminder2 service is not exists in database for ".$p_value->patient_id);
                            }
                        }//foreach
                  
                    }//if
                }
            }
            else
            {
               log::info("No reminder parameter changed for the service...");
               // dump('No reminder parameter changed for the service...');
            }
    }//updateRemindersOfKosten


     //added on 17-march-25
    public function updateRemindersOfKosten()
    {
        dump("in updateRemindersOfKosten function of multi patient ids..");

        $patientIDs = array('47299','47300');

        // $decodedIDs = array_map(function ($id) {
        //     return base64_decode(base64_decode($id));
        // }, explode(',', $patientIDs));

        //$patient_id=base64_decode(base64_decode($patientID));

        log::info("in updateRemindersOfKosten function of single url..for patient id..");
        log::info("decodedIDs");
        Log::info($patientIDs);
        dump($patientIDs);

        $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')
                        ->where(
                            [                                       
                           // 'is_reminder_updated' => '1',
                            'type' =>'service',
                            'service_id'=>183
                            ]
                        )->get();
            // echo "update<pre>";print_r($is_service_has_reminder);exit;
            if(!empty($is_service_has_reminder))
            {
                foreach($is_service_has_reminder as $key=>$value)
                {
                    $is_service_reminder_checked = DB::table('examinations')->where(
                        [
                        'id' => $value->service_id,
                        'show_as_reminder' => '1',
                            // 'status' => '1'
                        ])
                        ->whereNull('deleted_at')
                        ->first();
                    // print_r($is_service_reminder_checked);exit;
                    if(!empty($is_service_reminder_checked))
                    {
                        dump("in updateRemindersOfKosten function of is_service_reminder_checked....");
                        log::info("in updateReminders function of is_service_reminder_checked....");
                        log::info("in function is running for service id..===>");
                        dump($value->service_id);


                        $all_patinet_ids = DB::table('patient_has_service_reminder')
                                            ->leftjoin('patients','patients.id','=','patient_has_service_reminder.patient_id')
                                            // ->where('service_id',$value->service_id)
                                            ->where('service_id',185)
                                            ->whereNull('patients.deleted_at')
                                            ->whereNull('patient_has_service_reminder.deleted_at')
                                            // ->where('patient_id',$patient_id)
                                            ->whereIn('patient_id', $patientIDs)  // Handle multiple patient IDs
                                            // ->where('appointment_id','!=',0)
                                            ->groupby('patient_id','appointment_id')
                                            ->get(['patient_has_service_reminder.*']);
                        // echo "<pre>";print_r($all_patinet_ids);exit;
                        //dd(count($all_patinet_ids));
                        foreach($all_patinet_ids as $p_key=>$p_value)
                        {
                           
                            $checkReminder2Exists = DB::table('appointment_has_examinations')
                            ->select('examination_id')
                            ->where('appointment_id',$p_value->appointment_id)
                            ->where('patient_id',$p_value->patient_id)
                            ->where('examination_id',$value->service_id)
                            ->first();

                            if(isset($checkReminder2Exists) && !empty($checkReminder2Exists))
                            {
                                $appoitment_data = DB::table('appointment')
                                ->where('id',$p_value->appointment_id)->first();

                                $patinet_data = DB::table('patients')
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
                               
                                log::info($p_value->appointment_id);
                                if($p_value->appointment_id!=0){
                                    dump("in appointment_id not 0 ".$p_value->patient_id);
                                    $this->_checkAndAddServiceReminderU($p_value->patient_id,$value->service_id,$p_value->appointment_id,$ap_start_date,$data);
                                }
                            }//if checkReminder2Exists
                            else{
                                dump("reminder2 service is not exists in database for ".$p_value->patient_id);
                            }
                        }//foreach
                  
                    }//if
                }
            }
            else
            {
               log::info("No reminder parameter changed for the service...");
               // dump('No reminder parameter changed for the service...');
            }
    }//updateRemindersOfKosten



}