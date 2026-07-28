<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;

// Models
use App\Models\AppointmentModel;
use App\Models\PatientsModel;
use App\Models\ExportPathModel;
use App\Models\AdminUserModel;
use App\Models\AppointmentTypesModel;
use App\Models\ActivityLogModel;
use App\Models\RosterModel;
use App\Models\AppointmentHasNotificationModel;
use App\Models\AppointmentHasExaminationsModel;
use App\Models\PatientHasDocumentsModel;
use App\Models\GoogleColorsModel;
use App\Models\ProfilesTemplatesModel;
use App\Models\RosterHasDatesModel;
use App\Models\ExaminationsModel;
use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\PatientsHasDiagnosticFindingsModel;
use App\Models\PatientHasDiagnosticFindingsHasDocumentsModel;
use App\Models\DismissalModel;
use App\Models\PatientsHasDismissalModel;
use App\Models\SettingsModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\CheckListModel;
use App\Models\CheckListHasHeadingSectionModel;
use App\Models\HeadingSectionHasQuestionModel;
use App\Models\CheckListHasSelectedQuestionModel;
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\SpecialistDocumentsModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\PatientsHasServiceControlReminderModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\PatientHasReminder;
use App\Models\EventTypeHasExaminationsModel;
use App\Models\RosterHasWeeksHasTimeFramesModel;
use App\Models\DeletedAppointmentTrackModel;
use App\Models\AppointmentTypeHasNonExaminationsModel;
use App\Models\Event;
use PDF;
// Request
use App\Http\Requests\Admin\AppointmentRequest;

//Trait
use App\Traits\GeneralTrait;

// plugins
use Hash;
use Mail;
use DB;
use Auth;
use File;
use Storage;
use Carbon\Carbon;
use Session;
use DateTime;
//Added by swapnil 
use App\Models\WeekDaysModel;
use stdClass;
use Illuminate\Support\Facades\Log;

//mail
use App\Mail\SendDocumentForPatientmail;
use App\Models\UserHasAppointmentType;
use App\Models\CountryCodesModel; // new model for country code lookup

use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

/*****start***added on 23-feb-24 for import appointment**********************/

// use Google_Client;
// use Google_Service_Calendar;
// use Google_Service_Calendar_Event;
// use Google_Service_Calendar_EventDateTime;
// use Google_Service_Exception;    


/***********end***added on 23-feb-24 for import appointment****************/

use Validator; //added on 25-june-24


class AppointmentController extends Controller
{
    private $BaseModel;
    use GeneralTrait;

    public function __construct(
        AppointmentModel $AppointmentModel,
        AdminUserModel $AdminUserModel,
        ExportPathModel $ExportPathModel,
        ActivityLogModel $ActivityLogModel,
        AppointmentTypesModel $AppointmentTypesModel,
        PatientsModel $PatientsModel,
        RosterModel $RosterModel,
        AppointmentHasNotificationModel $AppointmentHasNotificationModel,
        AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
        PatientHasDocumentsModel $PatientHasDocumentsModel,
        SettingsModel $SettingsModel,
        RosterHasDatesModel $RosterHasDatesModel,
        GoogleColorsModel $GoogleColorsModel,
        ProfilesTemplatesModel $ProfilesTemplatesModel,
        ExaminationsModel $ExaminationsModel,
        ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
        PatientsHasDiagnosticFindingsModel $PatientsHasDiagnosticFindingsModel,
        PatientHasDiagnosticFindingsHasDocumentsModel $PatientHasDiagnosticFindingsHasDocumentsModel,
        DismissalModel $DismissalModel,
        PatientsHasDismissalModel $PatientsHasDismissalModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        CheckListModel $CheckListModel,
        CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
        HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
        CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
        ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
        SpecialistDocumentsModel $SpecialistDocumentsModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
        PatientsHasServiceControlReminderModel $PatientsHasServiceControlReminderModel,
        PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
        PatientHasReminder $PatientHasReminder,
        EventTypeHasExaminationsModel $EventTypeHasExaminationsModel,
        RosterHasWeeksHasTimeFramesModel $RosterHasWeeksHasTimeFramesModel,
        DeletedAppointmentTrackModel $DeletedAppointmentTrackModel,
        WeekDaysModel $WeekDaysModel,
        AppointmentTypeHasNonExaminationsModel $AppointmentTypeHasNonExaminationsModel,
        UserHasAppointmentType $UserHasAppointmentType,
        CountryCodesModel $CountryCodesModel

    ) {

        $this->BaseModel            = $AppointmentModel;
        $this->AppointmentModel            = $AppointmentModel;
        $this->ActivityLogModel     = $ActivityLogModel;
        $this->AdminUserModel       = $AdminUserModel;
        $this->ExportPathModel       = $ExportPathModel;
        $this->AppointmentTypesModel = $AppointmentTypesModel;
        $this->PatientsModel        = $PatientsModel;
        $this->RosterModel          = $RosterModel;
        $this->AppointmentHasNotificationModel  = $AppointmentHasNotificationModel;
        $this->AppointmentHasExaminationsModel  = $AppointmentHasExaminationsModel;
        $this->PatientHasDocumentsModel  = $PatientHasDocumentsModel;
        $this->GoogleColorsModel  = $GoogleColorsModel;
        $this->RosterHasDatesModel            = $RosterHasDatesModel;
        $this->ProfilesTemplatesModel  = $ProfilesTemplatesModel;
        $this->ExaminationsModel  = $ExaminationsModel;
        $this->ExaminationsHasMultipleCheckListModel  = $ExaminationsHasMultipleCheckListModel;
        $this->PatientsHasDiagnosticFindingsModel  = $PatientsHasDiagnosticFindingsModel;
        $this->PatientHasDiagnosticFindingsHasDocumentsModel  = $PatientHasDiagnosticFindingsHasDocumentsModel;
        $this->DismissalModel  = $DismissalModel;
        $this->PatientsHasDismissalModel = $PatientsHasDismissalModel;
        $this->SettingsModel = $SettingsModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->CheckListModel = $CheckListModel;
        $this->CheckListHasHeadingSectionModel = $CheckListHasHeadingSectionModel;
        $this->HeadingSectionHasQuestionModel = $HeadingSectionHasQuestionModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->PatientsHasServiceControlReminderModel = $PatientsHasServiceControlReminderModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->PatientHasReminder = $PatientHasReminder;
        $this->EventTypeHasExaminationsModel = $EventTypeHasExaminationsModel;
        $this->RosterHasWeeksHasTimeFramesModel = $RosterHasWeeksHasTimeFramesModel;
        $this->DeletedAppointmentTrackModel = $DeletedAppointmentTrackModel;
        $this->WeekDaysModel            = $WeekDaysModel;
        $this->AppointmentTypeHasNonExaminationsModel = $AppointmentTypeHasNonExaminationsModel;
        $this->UserHasAppointmentType = $UserHasAppointmentType;
        $this->CountryCodesModel = $CountryCodesModel;
        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle  = __('admin.TITLE_APPOINTMENT_TEXT');
        $this->ModuleView   = 'admin.appointment.';
        $this->ModulePath   = 'admin.appointment.';

        $this->patientText      = 'Patient';
        $this->doctorText       = 'Arzt';
        $this->appointmentText  = 'Typ';
        $this->startDateText    = 'Beginn';
        $this->endDateText      = 'Ende';
        $this->notesText        = 'Notizen';

        // Permission Middleware
        $this->middleware(['permission:appointment-listing'], ['only' => ['index', 'getRecords']]);
        $this->middleware(['permission:appointment-add'], ['only' => ['create', 'store']]);


        /*************start added on 23-feb-24 for import appointment******/

           
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
            
        /*************end added on 23-feb-24 for import appointment******/
    }

    public function index()
    {


        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_APPOINTMENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle . ' ' . __('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle . ' ' . __('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');
        // $this->ViewData['addButton']    = str_singular($this->ModuleTitle) . ' ' . __('admin.TITLE_ADD_BUTTON');

        // view file with data
        return view($this->ModuleView . 'index', $this->ViewData);
    }

    public function create()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_APPOINTMENT_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT'); 
        $this->ViewData['modulePath']   = $this->ModulePath; 

        // All user which have role as doctor

        try {
            $this->ViewData['user'] = $this->AdminUserModel
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'doctor');
                })
                ->get();
        } catch (Exception $e) {
        }

        // All appointment types 
        // $this->ViewData['patient'] = $this->PatientsModel
        //                                 ->where('status', 1)
        //                                 ->get();  
        // All appointment types 
        $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->get();
        // added by vijay 8/3/24
        $quarter_setting = 0;
        $optimal_appointment = $this->SettingsModel->where(['setting_key' => 'OPTIMAL_APPOINTMENT'])->select('setting_key', 'setting_value')->first();
        if (isset($optimal_appointment) && !empty($optimal_appointment)) {
            $quarter_setting = $optimal_appointment->setting_value;
        }
        $this->ViewData['quarter_setting'] = $quarter_setting;
        // end
         // prepare country code options for dropdown
        $this->ViewData['country_codes'] = $this->CountryCodesModel
            ->where('is_active',1)
            // ->orderBy('phone_code')
            ->pluck('phone_code')
            ->toArray();
        // view file with data
        return view($this->ModuleView . 'create', $this->ViewData);
    }

    public function store(AppointmentRequest $request)
    {

         Log::info('in admin appointment controller store function ');
         Log::info($request->all());


        $urlEventId = $urlPatientId = '';
        $startDate = date("Y-m-d");
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_CREATE');
        try {
            DB::beginTransaction();
            $startDate = date("Y-m-d H:i", strtotime($request->date . " " . $request->time_frame));
            $request['start_date'] = date("Y-m-d H:i:s", strtotime($request->date . " " . $request->time_frame));
            $request['end_date']  = self::_getEndDate($request['start_date'], $request['appointment_type_id']);
            $duplicationAppointmantself =  self::_checkDuplicationAppointmant($request, '');
            if (empty($duplicationAppointmantself) && sizeof($duplicationAppointmantself) == 0) {

                // Log::info('in empty appointment');


                if (!empty($request->new_patient_chkbox) && $request->new_patient_chkbox == 1) {

                    $is_exist_patient = $this->_checkDuplicationPatient($request->family_name, $request->first_name, $request->birth_date, $request->mobile_no, 'add', $id = '');

                    if (!$is_exist_patient) {
                        $this->JsonData['msg'] = __('admin.ERR_MOBILE_UNIQUE');
                        $this->JsonData['status']   = __('admin.RESP_ERROR');
                        return response()->json($this->JsonData);
                        exit();
                    }
                    //   $checkedBirthdateExist = $this->PatientsModel
                    //                         ->where(DB::raw('upper(family_name)'),'=',strtoupper($request->family_name))
                    //                         ->where(DB::raw('upper(first_name)'),'=',strtoupper($request->first_name))
                    //                         ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date)))
                    //                         ->whereNULL('deleted_at')
                    //                         ->get();
                    // if(count($checkedBirthdateExist) > 0 )
                    // {
                    //     $this->JsonData['msg'] = __('admin.ERR_BIRTH_DATE_UNIQUE');
                    //     $this->JsonData['status']   = __('admin.RESP_ERROR');
                    //     return response()->json($this->JsonData);
                    //     exit();
                    // }

                    $patient_data     = new $this->PatientsModel;
                    $patient_data     = self::_storePatient($patient_data, $request);
                    //Log::info('Patient create Appointment Controller line number : 240. patient Name :' . $patient_data->first_name.' '.$patient_data->family_name);
                    if (!empty(Config('ordination_id'))) {
                        $ordination_patient = self::_storePatientOrdination($patient_data->id);
                        //Log::info('When create new appointment and create new patient(master) line number: 245. patient Name :' . $patient_data->first_name.' '.$patient_data->family_name);
                    }
                    $patient_id            = $patient_data->id;
                    $request['patient_id'] = $patient_id;
                    //Added by Shyam 16-02-22
                    if (isset($patient_id) && $patient_id != '') {
                        $setReminders = app('App\Http\Controllers\Admin\DashboardController')->checkPatientAgeReminder($patient_id);
                    }
                } else {
                    $patient_id            = $request->patient_id;
                    $getPatientDetails = $this->PatientsModel
                                        ->where('id',$patient_id)
                                        ->first();
                    if(!empty(Config('ordination_id')) && empty($getPatientDetails['country']))
                    {
                        $ordination_patient = self::addPatientCountryOnOrdination($patient_id);
                    }
                }
                $collection     = new $this->BaseModel;
                $request['start_date'] = date("Y-m-d H:i", strtotime($request->date . " " . $request->time_frame));
                $request['end_date']  = self::_getEndDate($request['start_date'], $request['appointment_type_id']);


                // Log::info('before collection');
                // added by vijay 12/9/2024
                $loginUser = Auth::user();

                $collection->appointment_created_from = 3;
                $collection->optimal_appointment = $request->quarter_setting_check ? $request->quarter_setting_check : null;
                $collection->appointment_createdby = $loginUser->id;
                // end

                $collection     = self::_storeOrUpdate($collection, $request);

                //  Log::info('after collection');

                //  Log::info($collection);

                //===============================================================
                $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                    ->where('id', $request->roster_time_frame_id)
                    ->update([
                        'time_frame_flag' => '2',
                        'time_frame_flag_date' => Date('Y-m-d H:i:s'),
                        'comment' => 'AppointmentController store booking function app Date:' . date('Y-m-d H:i:s', strtotime($collection->start_date)) . ' current date:' . Date('Y-m-d H:i:s') . ' patient_id: ' . $patient_id
                    ]);

                //==========================================================
                // if($request->date < date('Y-m-d')) //commented by swapnil on 10-jan23
                //  if (date('Y-m-d',strtotime($request->date)) < date('Y-m-d')) //swapnil added on 10-jan-23                          
                // {
                //     self::_activateReminder($collection->id);
                // }
                // else {
                //     self::_deactivateReminder($collection);
                // }
                //====================================
                self::_activateReminderOnEdit($collection);
                self::_deactivateReminderNew($collection, $request->app_services);


                //======================================
                $newData = $collection->toArray();
                $all_transactions = [];
                $notify_data = [];
                if ($collection) {


                    //  Log::info('after collection save');


                    $all_transactions[] = 1;
                    // $patient_doc_data[] = array(
                    //                             'appointment_id'  => $collection->id,
                    //                             'patient_id'      => $collection->patient_id,
                    //                             'exam_app_type_id'=> $request['appointment_type_id'],
                    //                             'fk_document_id'  => $request['appointment_type_id'],
                    //                             'record_type'     => 1,
                    //                             'doc_status'      => 0,
                    //                             );
                    // if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                    //     $all_transactions[] = 1;
                    // }else{
                    //     $all_transactions[] = 0;
                    // }
                    $getDocument = self::_GetAssignedDocument($collection->id, $collection->appointment_type_id, $request->app_services, $collection->patient_id);
                    // END
                    //insert the entry for patient has Checklist
                    $getDocument = self::_GetAssignedCheckList($collection->id, $request->app_services, $collection->patient_id);
                    // END
                    $collection = $this->BaseModel->with(['assignedPatient', 'assignedDoctor', 'assignedAppointmentType'])->find($collection->id);
                    $patientName = $collection->assignedPatient->first_name . " " . $collection->assignedPatient->family_name;
                    $doctorName = $collection->assignedDoctor->first_name . " " . $collection->assignedDoctor->last_name;
                    $appointmentType = $collection->assignedAppointmentType->name;
                    $booking_month = __('admin.' . date('F', strtotime($request->start_date)), [], 'de');
                    $appointmentTime = date('d', strtotime($request->start_date)) . '.' . $booking_month . ", um " . date('H:i', strtotime($request->start_date)) . " Uhr.";

                    //commented on 6-nov-23
                    // $patientText = $collection->assignedPatient->salutation ?? "";
                    // $patientText .= " ".$collection->assignedPatient->family_name;

                    //changed on 6-nov-23
                    // $patientText = $collection->assignedPatient->salutation ? " " . $collection->assignedPatient->salutation . '.' : ""; //added dot after salutation on 14-dec-23 commented on 12-dec-25


                    $patientText = $collection->assignedPatient->salutation ? $collection->assignedPatient->salutation . '.' : ""; //added dot after salutation on 14-dec-23 changed on 12-dec-25



                    // $patientText .= " " . $collection->assignedPatient->first_name . ' ' . $collection->assignedPatient->family_name; //commented on 12-dec-25

                    //changed on 12-dec-25
                    if(isset($collection->assignedPatient->salutation)){
                        $patientText .= " " . $collection->assignedPatient->first_name . ' ' . $collection->assignedPatient->family_name;
                    }else{
                        $patientText .= $collection->assignedPatient->first_name . ' ' . $collection->assignedPatient->family_name;
                    }
                    


                    $doctorSurname = $collection->assignedDoctor->last_name;

                    //Appoinment Push Notification
                    //commented on 6-nov-23
                    // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;



                    $notify_times = self::_getNotifyTime($request['start_date']);


                    //commented below code on 13-feb-24 for notification from setting section

                    /* $content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime; //changed on 6-nov-23

                    foreach ($notify_times as $notify_time)
                    {
                        $notify_data[] = array(
                                                'patient_id'    => $patient_id,
                                                'appointment_id'=> $collection->id,
                                                'title'     => 'Erinnerung an Ihren Termin',
                                                'content'   => $content,
                                                'notify_time'=> $notify_time,
                                                'status'     => 0,
                                            );
                    }
                    if($this->AppointmentHasNotificationModel->insert($notify_data))
                    {
                        $all_transactions[] = 1;
                    }
                    else {
                        $all_transactions[] = 0;
                    }*/


                    /********added code on 13-feb-24***for notification from setting section*******/

                    $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));

                    $skipNotification = false; //added on 12-nov-25


                    $getSetting  = $this->AppointmentHasNotificationModel->whereStatus(3)->first();
                    if (isset($getSetting) && !empty($getSetting)) {

                        $title = $getSetting->title;
                        $content = $getSetting->content;
                        $day = $getSetting->day;
                        $notify_time = $getSetting->notify_time;
                        $appointmentDate =  date("Y-m-d", strtotime($request->start_date));

                        if ($day == 0) //current day
                        {
                            $req_notify_time   = explode(" ", $getSetting->notify_time);
                            $req_notify_time_in_seconds = $req_notify_time[1];
                            $app_notify_time   = date("Y-m-d H:i:s", strtotime($appointmentDate . " " .
                                $req_notify_time_in_seconds));

                            // start added on 12-nov-25 for skip notification
                            $currentDate = date('Y-m-d');

                            if ($appointmentDate == $currentDate && strtotime($request->start_date) < strtotime($appointmentDate . ' ' . $req_notify_time_in_seconds)) {
                                $skipNotification = true;
                            }
                            //end 

                        } else {
                            //previous day
                            $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($request->start_date)));
                            $req_notify_time   = explode(" ", $getSetting->notify_time);
                            $req_notify_time_in_seconds = $req_notify_time[1];
                            $app_notify_time = date("Y-m-d H:i:s", strtotime($previous_day . " " .
                                $req_notify_time_in_seconds));
                        }

                        $content = str_replace("##PATIENT_NAME##", $patientText, $content);
                        $content = str_replace("##DOCTOR_SURNAME##", $doctorSurname, $content);
                        $content = str_replace("##APPOINTMENT_TYPE##", $appointmentType, $content);
                        $content = str_replace("##DATE_TIME##", $appointmentTime, $content);
                    } //if isset getsetting
                    else {
                        $title = 'Erinnerung an Ihren Termin';
                        $content = 'Hallo' . $patientText . ', ihr Termin mit Dr. ' . (string)$doctorSurname . ' (' . $appointmentType . ') ist am' . ' ' . (string)$appointmentTime;
                        $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));
                    }

                    $notify_data = array(
                        'patient_id' => $patient_id,
                        'appointment_id' => $collection->id,
                        'title' => $title,
                        'content' => $content,
                        'notify_time' => $app_notify_time,
                        'status' => 0,
                    );


                    // Log::info('after notify_data');
                    // Log::info($notify_data);


                    //commented on 12-nov-25
                    /*if ($this->AppointmentHasNotificationModel->insert($notify_data)) {
                        $all_transactions[] = 1;
                    } else {
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


                    /***********end code**of notification setting****13-feb-24***************/


                    Log::info("in appointment controller store function before exam store");

                    //Default appintment
                    $getServises = self::_appointmentTypesAgaintsServices($collection->id, $request, $patient_id);
                    $serviceEventType = self::GetServicesEventType($collection->id, $patient_id, $request->app_services, $collection->appointment_type_id, 'admin');
                    // END
                    //Appoinment added in google calendar
                    $summary = $patientName . " - " . $appointmentType;
                    $description = '<p><strong>' . $this->patientText . ':</strong> ' . $patientName . ' </p><p><strong>' . $this->doctorText . ':</strong> ' . $doctorName . ' </p><p><strong>' . $this->appointmentText . ':</strong> ' . $appointmentType . ' </p><p><strong>' . $this->startDateText . ':</strong> ' . date('F d,Y H:i', strtotime($request->start_date)) . ' </p><strong>' . $this->endDateText . ':</strong> ' . date('F d,Y H:i', strtotime($request->end_date)) . ' </p><p><strong>' . $this->notesText . ':</strong> ' . $request->notes . ' </p>';
                    $request = array(
                        'summary' => $summary,
                        'description' => $description,
                        'startDateTime' => $request->start_date,
                        'endDateTime' => $request->end_date,
                        'patient_id' => $patient_id,
                        'patient_email' => $collection->assignedPatient->email,
                        'patient_name' => $patientName,
                        'doctor_email' => $collection->assignedDoctor->email,
                        'color_id' => $collection->assignedDoctor->google_color_id,
                    );
                    /*if(!empty($request->new_patient_chkbox) && $request->new_patient_chkbox==1){
                        $request['patient_name']= $patientName;
                    }*/
                    request()->merge($request);
                    $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventStore(request());

                    //  Log::info("after postCalDetails");
                    //   Log::info($postCalDetails);

                    // $postResponse = json_decode($postCalDetails->data);
                    if (!empty($postCalDetails) && $postCalDetails->original['status'] == 'success') {
                        //  Log::info("innnn postCalDetails");

                        $all_transactions[] = 1;
                        $eventId = $postCalDetails->original['data']->id;
                        // eventId store both places due to CR of new calender
                        $collection->google_event_id = $eventId;
                        $collection->event_id = $eventId;
                        if($collection->save())
                        {
                            $updateEvent = app('App\Http\Controllers\Admin\DashboardController')->appointmentIdUpdateInEvent($eventId, $collection->id);
                            $all_transactions[] = 1;
                            //Added by Shyam 24-03-22
                            $urlEventId = $eventId;
                            $urlPatientId = $collection->assignedPatient->id;
                        } else {
                            $all_transactions[] = 0;
                        }
                        //Log::info($this->ModuleTitle.'has created appointment by AppointmentController');
                        $debug_arr['data'] = 'has created appointment by AppointmentController';
                        $res_name = "AppointmentController_store";
                        //dd($debug_arr);
                        self::debugModeappBookFun($debug_arr, $res_name);
                        // $newData = $collection->toArray();
                        $this->ActivityLogModel->addLog($this->ModuleTitle, 'has created appointment', 'Add', null, $newData);
                        //add reminders for pass appointments added by swati 9-Jun-23================================================
                        $newdate = date("Y-m-d", strtotime($request['startDateTime']));
                        $todayDate = date('Y-m-d');
                        if ($newdate < $todayDate) {
                            $this->_remindersPassAppointments($collection->id);
                        }
                        //==============================================================
                    } else {
                        Log::info("else postCalDetails");

                        $all_transactions[] = 0;
                        DB::rollback();
                        $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $postCalDetails->original['msg'];
                    }
                } else {

                    // Log::info("else all_transactions 0");

                    $all_transactions[] = 0;
                }
                if (!in_array(0, $all_transactions)) {
                    DB::commit();
                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']      =  route($this->ModulePath . 'index');
                    $this->JsonData['msg']      = __('admin.APPOINTMENT_CREATED');
                }
            } else {

                //  Log::info("else slot already exists");

                $this->JsonData['status']   = __('admin.RESP_ERROR');
                $this->JsonData['url']      =  route($this->ModulePath . 'index');
                $this->JsonData['msg']      = __('admin.APPOINTMENT_SLOT_ALREADY_EXIST');
            }
        } catch (\Exception $e) {

            //  Log::info("in catch ..");

            DB::rollback();
            //dd($e->getMessage());
            $this->JsonData['msg']      = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        //Added by Shyam 24-03-22
        $newdate = date("Y-m-d", strtotime($startDate));
        $todayDate = date('Y-m-d');
        if (!empty($urlEventId) && !empty($urlPatientId) && $newdate >= $todayDate) {
            $channels = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();
            $patientData = $this->PatientsModel->where('id', $urlPatientId)->first();
            //Send Email...
            if (!empty($patientData->email) && $channels->choice_of_channels == 'email') {
                self::_sendMailAppointment($patientData->id, $urlEventId);
            } else {
                //Send SMS...
                $phone_no = '';
                $country_code = $patientData->country_code;
                if (!empty($country_code)) {
                    $country_code = str_replace("00", "", $country_code);
                } elseif (empty($country_code) || $country_code == '0') {
                    $country_code = '43'; //Austria country code
                }
                $country_code = str_replace("+", "", $country_code);
                if (!empty($patientData->mobile_no)) {
                    $phone_no = $country_code . "" . str_replace("-", "", $patientData->mobile_no);
                }
                if (!empty($phone_no)) {
                    self::_sendSmsAppointment($phone_no, $urlEventId);
                } elseif (!empty($patientData->email)) {
                    self::_sendMailAppointment($patientData->id, $urlEventId);
                }
            }
        }
        return response()->json($this->JsonData);
    }

    public function _storePatient($collection, $request)
    {

        Log::info("in admin appointment controller _storePatient function");
        Log::info($request->all());


        if (!empty($request->birth_date)) {
            $birth_date                  = date('Y-m-d', strtotime($request->birth_date));
            $age                         = (date('Y') - date('Y', strtotime($birth_date)));
        } else {
            $birth_date                  = NULL;
            $age                         = 0;
        }

        $collection->first_name         = self::string_operation($request->first_name);
        $collection->family_name        = self::string_operation($request->family_name);

        $collection->country_code       = $request->country_code;
        if (!empty($request->format)) {
            $collection->country_code       = $request->format;
        }
        $mobile_no                      = str_replace(" ", "", $request->mobile_no);
        $collection->mobile_no          = ltrim($mobile_no, '0');

        $collection->birth_date         = $birth_date;
        $collection->age                = $age;
        $collection->email              = $request->email;
        $collection->insurance_number   = $request->insurance_number;

        $collection->old_id             = 99999;
        $collection->postal_code        = $request->postal_code;
        
        // $collection->country            = $request->country; //Roshani added on 10 oct 24 for # 102 CR

        $collection->gender    = $request->gender; //added on 29-aug-25 


        //Save data
        $collection->save();
        if(!empty(Config('ordination_id')))
            {
                $ordination_patient = self::addPatientCountryOnOrdination($collection->id);
            }
        return $collection;
    }

    public function show($id)
    {
        dd('show');
    }

    public function edit($encID)
    {
        // Default site settings 
        $this->ModuleTitle              = __('admin.TITLE_APPOINTMENT_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT'); 

        $this->ViewData['modulePath']   = $this->ModulePath;

        // Appointment
        $id = base64_decode(base64_decode($encID));
        $appointment = $this->BaseModel->find($id);
        $this->ViewData['appointment'] = $appointment;

        $time_frames_id = '';
        //dd($getService);        
        $timeFrame = date('H:i:s', strtotime($appointment->start_date));
        $doctor_id = $appointment->doctor_id;

        $time_frames = $this->RosterHasDatesModel
            ->leftjoin('roster', 'roster.id', 'roster_has_dates.roster_id')
            ->whereDate('roster_has_dates.date', date('Y-m-d', strtotime($appointment->start_date)))
            ->where('roster.doctor_id', $doctor_id)
            ->first();

        if (!empty($time_frames)) {
            $getrec = $this->RosterHasWeeksHasTimeFramesModel
                ->where('week_day_id', $time_frames->week_day_id)
                ->where('roster_id', $time_frames->roster_id)
                ->where('time_frame', $timeFrame)
                ->where('time_frame_flag', '2')
                ->first();
            if (!empty($getrec)) {
                $time_frames_id = $getrec->id;
            }
        }

        // All user which have role as doctor
        $this->ViewData['user'] = $this->AdminUserModel
            // ->where('status', 1)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'doctor');
            })
            ->get();

        //Get services
        // $getServices = $this->AppointmentTypeHasExaminationsModel
        //                  ->where('appoinment_id',$id)
        //                  ->with(['assignedExamination'])
        //                  ->wherenull('deleted_at')
        //                  ->get(); 

        //$this->ViewData['appointment_type'] = $getServices;                

        // All patients  
        $this->ViewData['patient'] = $this->PatientsModel
            ->where('status', 1)
            ->get();

        // All appointment types 
        $this->ViewData['time_frames_id'] =  $time_frames_id;
        // ############# Roshani Added this code on (28/02/2024) ################# 
        $discardIdsfromAppType = $this->UserHasAppointmentType->where('user_id', $appointment->doctor_id)->pluck('appointment_type_id')->toArray();
        $filteredTypeIds = collect($discardIdsfromAppType)->diff([$appointment->appointment_type_id])->values()->all();

        // $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->whereNotIn('id', $filteredTypeIds)->get(); //commented on 13-apr-26

         $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->whereNotIn('id', $filteredTypeIds)->withTrashed()->get(); //changed on 13-apr-26



        // ############# Roshani Added this code on (28/02/2024) ################# 

        // $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->get();
        // added by vijay 8/3/24
        $quarter_setting = 0;
        $optimal_appointment = $this->SettingsModel->where(['setting_key' => 'OPTIMAL_APPOINTMENT'])->select('setting_key', 'setting_value')->first();
        if (isset($optimal_appointment) && !empty($optimal_appointment)) {
            $quarter_setting = $optimal_appointment->setting_value;
        }
        $this->ViewData['quarter_setting'] = $quarter_setting;

        // view file with data
        return view($this->ModuleView . 'edit', $this->ViewData);
    }

    public function update(AppointmentRequest $request, $encID)
    {
        Log::info("in admin appointment controller update function");
        Log::info($request->all());

        $id = base64_decode(base64_decode($encID));

        Log::info("in admin appointment controller update function id ");
        Log::info($id);

        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_UPDATE');

        try {

            $msg = self::_validateAppointment($request, $id);
            if (!empty($msg)) {
                $this->JsonData['msg'] = $msg;
                return response()->json($this->JsonData);
                exit();
            }

            DB::beginTransaction();
            $request['start_date'] = date("Y-m-d H:i", strtotime($request->date . " " . $request->time_frame));
            $request['end_date']  = self::_getEndDate($request['start_date'], $request['appointment_type_id']);

            $duplicationAppointmantself =  self::_checkDuplicationAppointmant($request, $id);

            if (empty($duplicationAppointmantself) && sizeof($duplicationAppointmantself) == 0) {
                $this->PatientHasDocumentsModel->where(['appointment_id' => $id, 'patient_id' => $request->patient_id])->delete();

                // // $patient_doc_data[] = array(
                // //     exam_ids                            'appointment_id'=> $id,
                // //                                 'patient_id'    => $request->patient_id,
                // //                                 'exam_app_type_id'=> $request->appointment_type_id,
                // //                                 'record_type'   => 1,
                // //                                 'doc_status'   => 0,
                // //                                 );
                // // //dd($patient_doc_data);

                // // if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                //     $all_transactions[] = 1;
                // }else{
                //     $all_transactions[] = 0;
                // }


                $collection = $this->BaseModel->with(['assignedPatient', 'assignedDoctor', 'assignedAppointmentType'])->find($id);

                $this->CheckListHasSelectedQuestionModel->where(['fk_appointment_id' => $id, 'fk_patient_id' => $request->patient_id])->delete();

                $this->PatientHasDocumentsModel->where(['appointment_id' => $id, 'patient_id' => $request->patient_id])->delete();

                //insert the entry for patient has document
                //dd($request->app_services);
                $getDocument = self::_GetAssignedDocument($collection->id, $collection->appointment_type_id, $request->app_services, $collection->patient_id);
                // END
                //dd($id);
                //insert the entry for patient has Checklist
                $getDocument = self::_GetAssignedCheckList($collection->id, $request->app_services, $collection->patient_id);
                // END  

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
                $oldData['created_at'] = date("Y-m-d H:i:s", strtotime($collection->created_at));
                $olda['google_event_id'] = $collection->google_event_id;
                $oldData['start_date'] = $collection->start_date;
                $oldData['end_date'] = $collection->end_date;
                $oldData['patient_id'] = $collection->patient_id;
                $oldData['doctor_id'] = $collection->doctor_id;
                $oldData['appointment_type_id'] = $collection->appointment_type_id;
                $oldData['notes'] = $collection->notes;
                $oldData['status'] = $collection->status;
                $oldData['created_at'] = date("Y-m-d H:i:s", strtotime($collection->created_at));
                $oldData['updated_at'] = date("Y-m-d H:i:s", strtotime($collection->updated_at));
                $oldData['deleted_at'] = $collection->deleted_at != null ? date("Y-m-d H:i:s", strtotime($collection->deleted_at)) : '';
                // dd($oldData);

                $request['start_date'] = date("Y-m-d H:i", strtotime($request->date . " " . $request->time_frame));
                $request['end_date']  = self::_getEndDate($request['start_date'], $request['appointment_type_id']);
                // dd($request->all());
                // added by vijay 12/9/2024
                $loginUser = Auth::user();

                $collection->appointment_updated_from = 3;
                $collection->optimal_appointment = $request->quarter_setting_check_val ? $request->quarter_setting_check_val : null;
                $collection->appointment_updatedby = $loginUser->id;
                // end
                $collection = self::_storeOrUpdate($collection, $request);

                // 
                $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                    ->where('id', $request->roster_time_frame_id)
                    ->update([
                        'time_frame_flag' => '2',
                        'time_frame_flag_date' => Date('Y-m-d H:i:s'),
                        'comment' => 'AppointmentController update booking function app Date:' . date('Y-m-d H:i:s', strtotime($collection->start_date)) . ' current date:' . Date('Y-m-d H:i:s') . ' patient_id: ' . $collection->patient_id
                    ]);



                // 


                // if($request->date < date('Y-m-d'))
                // {
                //     self::_activateReminder($collection->id);
                // }else
                // {
                //     self::_deactivateReminder($collection);
                // }
                //====================================
                self::_activateReminderOnEdit($collection);
                self::_deactivateReminderNew($collection, $request->app_services);
                //======================================
                $collection = $this->BaseModel->with(['assignedPatient', 'assignedDoctor', 'assignedAppointmentType'])->find($id);
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
                $newData['created_at'] = date("Y-m-d H:i:s", strtotime($collection->created_at));
                $newData['updated_at'] = date("Y-m-d H:i:s", strtotime($collection->updated_at));
                $newData['deleted_at'] = $collection->deleted_at != null ? date("Y-m-d H:i:s", strtotime($collection->deleted_at)) : '';
                // dd($newData);

                $all_transactions = [];
                $notify_data = [];
                if ($collection) {
                    $all_transactions[] = 1;

                    $patientName = $collection->assignedPatient->first_name . " " . $collection->assignedPatient->family_name;
                    $doctorName = $collection->assignedDoctor->first_name . " " . $collection->assignedDoctor->last_name;
                    $appointmentType = $collection->assignedAppointmentType->name;
                    $summary = $patientName . " - " . $appointmentType;
                    $description = '<p><strong>' . $this->patientText . ':</strong> ' . $patientName . ' </p><p><strong>' . $this->doctorText . ':</strong> ' . $doctorName . ' </p><p><strong>' . $this->appointmentText . ':</strong> ' . $appointmentType . ' </p><p><strong>' . $this->startDateText . ':</strong> ' . date('F d,Y H:i', strtotime($request->start_date)) . ' </p><strong>' . $this->endDateText . ':</strong> ' . date('F d,Y H:i', strtotime($request->end_date)) . ' </p><p><strong>' . $this->notesText . ':</strong> ' . $request->notes . ' </p>';

                    $this->AppointmentHasNotificationModel->where('appointment_id', $collection->id)->delete();

                    $booking_month = __('admin.' . date('F', strtotime($request->start_date)), [], 'de');
                    $appointmentTime = date('d', strtotime($request->start_date)) . '.' . $booking_month . ", um " . date('H:i', strtotime($request->start_date)) . " Uhr.";

                    //commented on 6-nov-23
                    // $patientText = $collection->assignedPatient->salutation ?? "";
                    // $patientText .= " ".$collection->assignedPatient->family_name;

                    //changed on 6-nov-23
                    // $patientText = $collection->assignedPatient->salutation ? " " . $collection->assignedPatient->salutation . '.' : ""; //added dot after salutation on 14-dec-23 commented on 12-dec-25


                    $patientText = $collection->assignedPatient->salutation ? $collection->assignedPatient->salutation . '.' : ""; //added dot after salutation on 14-dec-23 changed on 12-dec-25


                    // $patientText .= " " . $collection->assignedPatient->first_name . ' ' . $collection->assignedPatient->family_name; //commented on 12-dec-25

                    //changed on 12-dec-25
                    if(isset($collection->assignedPatient->salutation)){
                         $patientText .= " " . $collection->assignedPatient->first_name . ' ' . $collection->assignedPatient->family_name;
                    }else{
                         $patientText .= $collection->assignedPatient->first_name . ' ' . $collection->assignedPatient->family_name;
                    }
                   


                    $doctorSurname = $collection->assignedDoctor->last_name;

                    //Appoinment Push Notification
                    //commented on 6-nov-23
                    // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;


                    //commented below code on 13-feb-24 for notification from setting section

                    /* $content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;  //changed on 6-nov-23

                    $notify_times = self::_getNotifyTime($request['start_date']);
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


                    /******added code on 13-feb-24***for notification from setting section*******/

                    $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));

                    $skipNotification = false; //added on 12-nov-25


                    $getSetting  = $this->AppointmentHasNotificationModel->whereStatus(3)->first();
                    if (isset($getSetting) && !empty($getSetting)) {
                        //dump("in getsetting");

                        $title = $getSetting->title;
                        $content = $getSetting->content;
                        $day = $getSetting->day;
                        $notify_time = $getSetting->notify_time;
                        $appointmentDate =  date("Y-m-d", strtotime($request->start_date));

                        // dump('in notify_time..');
                        // dump($notify_time);

                        if ($day == 0) //current day
                        {
                            $req_notify_time   = explode(" ", $getSetting->notify_time);
                            $req_notify_time_in_seconds = $req_notify_time[1];
                            $app_notify_time   = date("Y-m-d H:i:s", strtotime($appointmentDate . " " .
                                $req_notify_time_in_seconds));

                             // start added on 12-nov-25 for skip notification
                            $currentDate = date('Y-m-d');

                            if ($appointmentDate == $currentDate && strtotime($request->start_date) < strtotime($appointmentDate . ' ' . $req_notify_time_in_seconds)) {
                                $skipNotification = true;
                            }
                            //end 

                        } else {
                            //previous day
                            $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($request->start_date)));
                            $req_notify_time   = explode(" ", $getSetting->notify_time);
                            $req_notify_time_in_seconds = $req_notify_time[1];
                            $app_notify_time = date("Y-m-d H:i:s", strtotime($previous_day . " " .
                                $req_notify_time_in_seconds));
                        }

                        $content = str_replace("##PATIENT_NAME##", $patientText, $content);
                        $content = str_replace("##DOCTOR_SURNAME##", $doctorSurname, $content);
                        $content = str_replace("##APPOINTMENT_TYPE##", $appointmentType, $content);
                        $content = str_replace("##DATE_TIME##", $appointmentTime, $content);
                    } //if isset getsetting
                    else {
                        $title = 'Erinnerung an Ihren Termin';
                        $content = 'Hallo' . $patientText . ', ihr Termin mit Dr. ' . (string)$doctorSurname . ' (' . $appointmentType . ') ist am' . ' ' . (string)$appointmentTime;
                        $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));
                    }

                    $notify_data = array(
                        'patient_id' => $request->patient_id,
                        'appointment_id' => $collection->id,
                        'title' => $title,
                        'content' => $content,
                        'notify_time' => $app_notify_time,
                        'status' => 0,
                    );


                    //commented on 12-nov-25
                    /*if ($this->AppointmentHasNotificationModel->insert($notify_data)) {
                        $all_transactions[] = 1;
                    } else {
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


                    /***********end code**of notification setting***13-feb-24*****************/




                    $deleteAppointmentHasExamination = $this->AppointmentHasExaminationsModel
                        ->where('appointment_id', $collection->id)
                        ->where('patient_id', $request->patient_id)
                        ->delete();

                    $this->EventTypeHasExaminationsModel->where(['appoinment_id' => $collection->id, 'patient_id' => $request->patient_id])->delete();

                    Log::info("in admin appointment controller update function before exam store");

                    $getServises = self::_appointmentTypesAgaintsServices($collection->id, $request, $request->patient_id);

                    $serviceEventType = self::GetServicesEventType($collection->id, $request->patient_id, $request->app_services, $collection->appointment_type_id, 'admin');

                    $request = array(
                        'eventId' => $collection->google_event_id,
                        'summary' => $summary,
                        'description' => $description,
                        'startDateTime' => $request->start_date,
                        'endDateTime' => $request->end_date,
                        'patient_id' => $request->patient_id,
                        'patient_email' => $collection->assignedPatient->email,
                        'patient_name' => $patientName,
                        'color_id' => $collection->assignedDoctor->google_color_id,
                        'doctor_email' => $collection->assignedDoctor->email,
                    );
                    request()->merge($request);
                    // dd(request()->all());
                    $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventUpdate(request());
                    //$postResponse = json_decode($postCalDetails->data);
                    // dd($postCalDetails);
                    if (!empty($postCalDetails) && $postCalDetails->original['status'] == 'success') {
                        $all_transactions[] = 1;
                        // Log::info($this->ModuleTitle.'has update appointment by AppointmentController');
                        $debug_arr['data'] = 'has update appointment by AppointmentController';
                        $res_name = "AppointmentController_update";
                        //dd($debug_arr);  
                        self::debugModeappBookFun($debug_arr, $res_name);

                        $this->ActivityLogModel->addLog($this->ModuleTitle, 'has updated appointment type', 'Update', $oldData, $newData);
                    } else {
                        $all_transactions[] = 0;
                        DB::rollback();
                        $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $postCalDetails->original['msg'];
                    }
                } else {
                    $all_transactions[] = 0;
                }

                if (!in_array(0, $all_transactions)) {
                    DB::commit();

                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']      =  route($this->ModulePath . 'index');
                    $this->JsonData['msg']      = __('admin.APPOINTMENT_UPDATED');

                    // Send Mail / SMS on reschedule/update added on 24-march-26 for #418
                    $updatedAppointment = $this->BaseModel->with(['assignedPatient'])->find($id);

                    Log::info("app manage updatedAppointment==>");
                    Log::info($updatedAppointment); 

                    $appointmentStartDate = date("Y-m-d", strtotime($updatedAppointment->start_date));
                    $todayDate = date('Y-m-d');

                    if (!empty($updatedAppointment->google_event_id) && !empty($updatedAppointment->patient_id) && $appointmentStartDate >= $todayDate)
                    {
                        $channels    = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();
                        $patientData = $this->PatientsModel->where('id', $updatedAppointment->patient_id)->first();

                        Log::info("app manage updatedAppointment channels==>");
                        Log::info($channels); 

                        Log::info("app manage updatedAppointment patientData==>");
                        Log::info($patientData); 

                        if (!empty($patientData->email) && $channels->choice_of_channels == 'email') {
                            self::_sendMailAppointment($patientData->id, $updatedAppointment->google_event_id);
                        } else {
                            $phone_no     = '';
                            $country_code = (!empty($patientData->country_code)) ? $patientData->country_code : '00';

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
                                self::_sendSmsAppointment($phone_no, $updatedAppointment->google_event_id);
                            } elseif (!empty($patientData->email)) {
                                self::_sendMailAppointment($patientData->id, $updatedAppointment->google_event_id);
                            }
                        }
                    }
                    // Send Mail / SMS on reschedule/update added on 24-march-26 for #418



                }//if transactions
            } else {
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                $this->JsonData['url']      =  route($this->ModulePath . 'index');
                $this->JsonData['msg']      = __('admin.APPOINTMENT_SLOT_ALREADY_EXIST');
            }
        } catch (\Exception $e) {
            DB::rollback();
            $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function _validateAppointment($request, $id = false)
    {


        // $start_date = $request->date." ".$request->time_frame.":00"; //commneted by swapnil on 10-jan-23
        $start_date = date('Y-m-d', strtotime($request->date)) . " " . $request->time_frame . ":00"; // swapnil added code 10-jan23

        // dump('validate',$request->all(),$id,$start_date);

        $appointment_exist = $this->BaseModel
            ->where('start_date', $start_date)
            ->where('id', '!=', $id)
            ->where('doctor_id', $request->doctor_id) // Added on 3 oct 22 by divya
            ->get();

        // dd($appointment_exist);
        $msg = '';
        if (!empty($appointment_exist) && sizeof($appointment_exist) > 0) {

            $msg =  'This time slot is already booked by other appointment.';
        }

        return $msg;
        exit();
    }

    public function destroy($encID)
    {
        $this->JsonData['status']   = 'error';
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_DELETE');
        $id = base64_decode(base64_decode($encID));

        DB::beginTransaction();

        $collection = $this->BaseModel->find($id);

        // ===============================================================
        $timeFrame = date('H:i:s', strtotime($collection->start_date));
        $doctor_id = $collection->doctor_id;

        $time_frames_id = '';

        $time_frames = $this->RosterHasDatesModel
            ->leftjoin('roster', 'roster.id', 'roster_has_dates.roster_id')
            ->whereDate('roster_has_dates.date', date('Y-m-d', strtotime($collection->start_date)))
            ->where('roster.doctor_id', $doctor_id)
            ->first();

        if (!empty($time_frames)) {

            $getrec = $this->RosterHasWeeksHasTimeFramesModel
                ->where('week_day_id', $time_frames->week_day_id)
                ->where('roster_id', $time_frames->roster_id)
                ->where('time_frame', $timeFrame)
                ->where('time_frame_flag', '2')
                ->first();
            if (!empty($getrec)) {
                $oldUpdateTimeFrameFlg = $this->RosterHasWeeksHasTimeFramesModel->find($getrec->id);

                $oldUpdateTimeFrameFlg->time_frame_flag = '0';
                $oldUpdateTimeFrameFlg->comment         = 'patient_id ' . $collection->patient_id . ' deleted Appointment Date :' . $collection->start_date . ' Appointment From  DashboardController current Date :' . date('Y-m-d H:i:s') . ' Time Fram Id : ' . $getrec->id;
                $oldUpdateTimeFrameFlg->save();
            }
        }
        //===============================================================             
        self::_activateReminderOnCancel($collection);
        //===============================================================     
        self::DeletedAppointmentTrack($collection);
        // ------------------------------------
        if ($collection->delete()) {
            $newData = $collection->toArray();
            $this->AppointmentHasNotificationModel->where('appointment_id', $collection->id)->delete();
            $this->AppointmentHasExaminationsModel->where('appointment_id', $collection->id)->delete();
            $this->PatientHasDocumentsModel->where('appointment_id', $collection->id)->delete();

            $ids = $this->PatientsHasServiceReminderModel
                ->where('appointment_id', $collection->id)
                ->select('id')
                ->get();
            $id_holder = [];
            if (!empty($ids)) {
                foreach ($ids as $id => $value) {
                    $id_holder[] = $value->id;
                }
            }

            $this->PatientsHasServiceReminderModel
                ->where('appointment_id', $collection->id)
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);

            $reactivateReminder =  $this->PatientHasReminder
                ->whereIn('service_reminder_id', $id_holder)
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);

            $request = array(
                // 'eventId' => $collection->google_event_id,
                'eventId' => $collection->event_id,
            );
            request()->merge($request);
            // dd(request()->all());
            $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventDelete(request());
            //$postResponse = json_decode($postCalDetails->data);
            //dd($postCalDetails);
            if (!empty($postCalDetails) && $postCalDetails->original['status'] == 'success') {

                $this->ActivityLogModel->addLog($this->ModuleTitle, 'has deleted appointment', 'Delete', null, $newData);
                DB::commit();

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['msg']      = __('admin.APPOINTMENT_DELETED');
            } else {
                DB::rollback();
                $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $postCalDetails->original['msg'];
            }
        }

        return response()->json($this->JsonData);
    }
    public function getRecords(Request $request)
    {
        /*--------------------------------------
        |  Variables
        ------------------------------*/

        // skip and limit
        $start  = $request->start;
        $length = $request->length;

        // Login user id 
        $userId = Auth::user()->id;

        // serach value
        $search = $request->search['value'];

        // order
        $column = $request->order[0]['column'];
        $dir    = $request->order[0]['dir'];

        // filter columns
        $filter = array(
            0 => 'id',
            1 => 'appointment.start_date',
            2 => 'appointment.end_date',
            3 => 'appointment.patient_id',
            4 => 'appointment.appointment_status',
            5 => 'appointment.doctor_id',
            6 => 'appointment.appointment_type_id',
        );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/
        // dump(auth()->user());
        // start model query 
        if (auth()->user()->hasRole('super-admin')) {
            $modelQuery =  $this->BaseModel
                ->leftjoin('users', 'users.id', '=', 'appointment.doctor_id')
                ->leftjoin('appointment_types', 'appointment_types.id', '=', 'appointment.appointment_type_id')
                ->leftjoin('patients', 'patients.id', '=', 'appointment.patient_id')
                ->whereNULL('patients.deleted_at');
        } elseif (auth()->user()->hasRole('Assistant') && auth()->user()->can('appointment-add')) {

            $modelQuery =  $this->BaseModel
                ->leftjoin('users', 'users.id', '=', 'appointment.doctor_id')
                ->leftjoin('appointment_types', 'appointment_types.id', '=', 'appointment.appointment_type_id')
                ->leftjoin('patients', 'patients.id', '=', 'appointment.patient_id')
                ->whereNULL('patients.deleted_at');
        } elseif (auth()->user()->hasRole('Doctor')) {
            $modelQuery =  $this->BaseModel
                ->leftjoin('users', 'users.id', '=', 'appointment.doctor_id')
                ->leftjoin('appointment_types', 'appointment_types.id', '=', 'appointment.appointment_type_id')
                ->leftjoin('patients', 'patients.id', '=', 'appointment.patient_id')
                ->where('users.id', $userId)
                ->whereNULL('patients.deleted_at');
        } elseif (auth()->user()->hasRole('Lead-Assistant') && auth()->user()->can('appointment-add')) {
            //added above else if condition 1-feb-24 for lead assistant role

            $modelQuery =  $this->BaseModel
                ->leftjoin('users', 'users.id', '=', 'appointment.doctor_id')
                ->leftjoin('appointment_types', 'appointment_types.id', '=', 'appointment.appointment_type_id')
                ->leftjoin('patients', 'patients.id', '=', 'appointment.patient_id')
                //->where('users.id', $userId)
                ->whereNULL('patients.deleted_at');
        }
        $modelQuery = $modelQuery->where('appointment.is_app_booked', 1); //added by vijay 16/4/2024
        //$modelQuery =  $this->BaseModel->whereNULL('appointment.deleted_at');

        // get total count 
        $countQuery = clone ($modelQuery);
        $totalData  = $countQuery->count();

        ## FILTER OPTIONS for specific field 
        $custom_search = false;
        if (!empty($request->custom)) {
            if (!empty($request->custom['start_date'])) {
                $custom_search  = true;
                $key            = $request->custom['start_date'];
                $modelQuery     = $modelQuery
                    ->where('appointment.start_date', 'LIKE', '%' . $key . '%');
            }
            //Comment the below code and add new one bcz existing not working
            
            if (!empty($request->custom['patient_id'])) {
                    $raw = trim($request->custom['patient_id']);
                    // keep letters, numbers, spaces, hyphen
                    $cleaned = preg_replace('/[^\p{L}0-9\s\-]/u', '', $raw);

                    if (empty($cleaned)) {
                        $modelQuery->whereRaw('1 = 0'); // no results
                    } else {
                        // Escape regex metachars for MySQL RLIKE (simple escaping)
                        $regexEscaped = preg_replace('/([\\\\.\^\$\|\?\*\+\(\)\[\{\]])/', '\\\\$1', $cleaned);

                        // MySQL word-boundary style
                        // $mysqlRegex = '[[:<:]]' . $regexEscaped . '[[:>:]]';
                        $mysqlRegex = $regexEscaped;

                        // Also prepare a safe LIKE pattern (escape % and _)
                        $likeEscaped = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $cleaned);

                        $modelQuery = $modelQuery->where(function ($q) use ($mysqlRegex, $likeEscaped) {
                            // whole-word match in first_name OR family_name OR full name
                            $q->whereRaw("patients.first_name RLIKE ?", [$mysqlRegex])
                            ->orWhereRaw("patients.family_name RLIKE ?", [$mysqlRegex])
                            ->orWhereRaw("CONCAT(patients.first_name, ' ', patients.family_name) RLIKE ?", [$mysqlRegex])
                            // fallback exact-substring (if you want exact but allow within a longer field)
                            ->orWhere('patients.first_name', 'LIKE', "%{$likeEscaped}%")
                            ->orWhere('patients.family_name', 'LIKE', "%{$likeEscaped}%")
                            ->orWhereRaw("CONCAT(patients.first_name, ' ', patients.family_name) LIKE ?", ["%{$likeEscaped}%"]);
                        });
                    }
                }
            if (isset($request->custom['doctor_id'])) {
                $custom_search  = true;
                $key            = $request->custom['doctor_id'];
                $modelQuery     = $modelQuery
                    ->where('appointment.doctor_id', $key);
            }

            if (isset($request->custom['appointment_type_id'])) {
                $custom_search  = true;
                $key            = $request->custom['appointment_type_id'];
                $modelQuery     = $modelQuery
                    ->where('appointment.appointment_type_id', $key);
            }
            /*
                if (isset($request->custom['appointment_status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['appointment_status'];
                    $modelQuery     = $modelQuery
                    ->where('appointment.appointment_status', $key);
                }
                */
            //updated
            if (isset($request->custom['appointment_status'])) {
                $custom_search  = true;

                //commented on 7-feb-25 for #285 issue
                // $key            = (ucfirst($request->custom['appointment_status']) == 'Verpasst' || ucfirst($request->custom['appointment_status']) == 'Vermisst') ? 'Vermisst' : '';

                //did changes on 7-feb-25 for #285 issue
                $key            = (ucfirst($request->custom['appointment_status']) == 'Verpasst' || ucfirst($request->custom['appointment_status']) == 'Vermisst') ? 'Vermisst' : $request->custom['appointment_status'];

                $modelQuery     = $modelQuery
                    ->where('appointment.appointment_status', $key);
            }
        }

        // Common filter options
        if (!empty($request->search)) {
            if (!empty($request->search['value'])) {
                $search = $request->search['value'];

                $modelQuery = $modelQuery->where(function ($query) use ($search) {
                    $query->orwhere('appointment.start_date', 'LIKE', '%' . $search . '%');
                    $query->orwhere('appointment.end_date', 'LIKE', '%' . $search . '%');
                    $query->orWhere(DB::raw("CONCAT(patients.first_name, ' ', patients.family_name)"), 'LIKE', "%" . $search . "%");
                    $query->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'LIKE', "%" . $search . "%");
                    $query->orwhere('appointment_types.name', 'LIKE', '%' . $search . '%');
                });
            }
        }

        // get total filtered
        $filteredQuery = clone ($modelQuery);
        $totalFiltered  = $filteredQuery->count();

        // offset and limit
        $object = $modelQuery->whereNULL('appointment.deleted_at')
            ->where('appointment.is_app_booked', 1) //added by vijay 16/4/2024 condition added because when filter applied above condition not working 
            ->orderBy($filter[$column], $dir)
            ->skip($start)
            ->take($length)
            ->get([
                'appointment.id',
                'appointment.start_date',
                'appointment.end_date',
                'patients.first_name as patient_fname',
                'patients.family_name as patient_lname',
                'users.first_name as doctor_fname',
                'users.last_name as doctor_lname',
                'appointment_types.name as aname',
                'appointment.appointment_status as appointment_status',
            ]);
        // dd($object);                     
        /*--------------------------------------
        |  data binding
        ------------------------------*/
        $data = [];
        if (!empty($object) && sizeof($object) > 0) {
            foreach ($object as $key => $row) {
                $fname          = $row->patient_fname;
                $lname          = $row->patient_lname;
                $patient_name   = $fname . ' ' . $lname;

                $fname          = $row->doctor_fname;
                $lname          = $row->doctor_lname;
                $doctor_name    = $fname . ' ' . $lname;

                $data[$key]['id']  = $row->id;
                $data[$key]['start_date']   = '<span title="' . $row->start_date . '">' . $row->start_date . '</span>';
                $data[$key]['end_date']     = '<span title="' . $row->end_date . '">' . $row->end_date . '</span>';
                $data[$key]['patient_id']   = '<span title="' . ucfirst($patient_name) . '">' . ucfirst($patient_name) . '</span>';
                //$data[$key]['appointment_status']   = '<span title="'.ucfirst($row->appointment_status).'">'.ucfirst($row->appointment_status).'</span>';

                $newStatus = ucfirst($row->appointment_status);
                if ($newStatus == 'Vermisst') {
                    $newStatus = 'Verpasst';
                }
                $data[$key]['appointment_status']   = '<span title="' . $newStatus . '">' . $newStatus . '</span>';

                $id = $row->patientId;
                $data[$key]['doctor_id']   = '<span title="' . ucfirst($doctor_name) . '">' . ucfirst($doctor_name) . '</span>';
                $data[$key]['appointment_type_id']   = '<span>' . $row->aname . '</span>';

                $edit = "";
                $delete = "";

                // Check Permission
                if (auth()->user()->can('appointment-add')) {
                    $edit = '<a href="' . route($this->ModulePath . 'edit', [base64_encode(base64_encode($row->id))]) . '" class="edit-user action-icon" title="' . __('admin.TITLE_EDIT_TEXT') . '"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                    $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="' . __('admin.TITLE_DELETE_BUTTON') . '" onclick="return deleteCollection(this)" data-href="' . route($this->ModulePath . 'destroy', [base64_encode(base64_encode($row->id))]) . '" ><span class="fas fa-trash"></span></a>';
                }

                $data[$key]['actions'] = '<div class="text-center">' . $edit . $delete . '</div>';
            }
        }

        ## SEARCH HTML 
        // Patients
        // $patient = $this->PatientsModel
        //                 ->where('status', 1)
        //                 ->get(['id','first_name','last_name']);

        // Doctors
        $user = $this->AdminUserModel
            // ->where('status', 1)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'doctor');
            })
            ->get();

        // Appointment Types commented on 13-apr-26
        // $appointment_type = $this->AppointmentTypesModel
        //     ->get();


         $appointment_type = $this->AppointmentTypesModel->withTrashed()
            ->get();    //changed on 13-apr-26

        // Search start date
        if (!empty($request->custom['start_date']) && $request->custom['start_date'] == '') {
            $val = '';
        } else {
            $val = $request->custom['start_date'] ?? '';
        }

        // Search for patient column
        /* $patientName= '<select name="patient_id" id="patient_id" class="form-control my-select">';

            $patientName.='<option class="theme-black blue-select" value="">'.__('admin.TITLE_SELECT_PATIENT').'</option>';

            foreach ($patient as $patients) {
                $pname = $patients->first_name.' '.$patients->last_name;
                $patientName.='<option class="theme-black blue-select" value='.$patients->id.' '. ( $request->custom['patient_id'] == $patients->id ? 'selected' : '').'>'.$pname.'</option>';
            }             
            $patientName.= "</select>";*/

        // Search for doctor column
        $doctorName = '';
        //if(auth()->user()->hasRole('super-admin')){
        $doctorName = '<select name="doctor_id" id="doctor_id" class="form-control my-select">';

        $doctorName .= '<option class="theme-black blue-select" value="">' . __('admin.TITLE_SELECT_DOCTOR') . '</option>';

        foreach ($user as $users) {
            $dname = $users->first_name . ' ' . $users->last_name;
            $doctorName .= '<option class="theme-black blue-select" value=' . $users->id . ' ' . (!empty($request->custom['doctor_id']) && $request->custom['doctor_id'] == $users->id ? 'selected' : '') . '>' . $dname . '</option>';
        }
        $doctorName .= "</select>";
        //}

        // Search for appointment type column
        $appointmentTypeName = '<select name="appointment_type_id" id="appointment_type_id" class="form-control my-select">';

        $appointmentTypeName .= '<option class="theme-black blue-select" value="">' . __('admin.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE') . '</option>';

        foreach ($appointment_type as $appointment_types) {
            $pname = $appointment_types->name;
            $appointmentTypeName .= '<option class="theme-black blue-select" value=' . $appointment_types->id . ' ' . (!empty($request->custom['appointment_type_id']) &&  $request->custom['appointment_type_id'] == $appointment_types->id ? 'selected' : '') . '>' . $pname . '</option>';
        }
        $appointmentTypeName .= "</select>";

        // SEARCH HTML
        $searchHTML['id']               =  '';

        $searchHTML['start_date']       =  '<input type="text" class="form-control" id="start_date" value="' . $val . '" placeholder=' . __('admin.TITLE_SEARCH_TEXT') . '>';
        $searchHTML['end_date']         =  '';

        $searchHTML['patient_id']     =  '<input type="text" class="form-control" id="patient_id" value="' . ($request->custom['patient_id'] ?? '') . '" placeholder=' . __('admin.TITLE_SEARCH_TEXT') . '>';
        $searchHTML['appointment_status']  =  '<input type="text" class="form-control" id="appointment_status" value="' . ($request->custom['appointment_status'] ?? '') . '" placeholder=' . __('admin.TITLE_SEARCH_TEXT') . '>';
        //$searchHTML['doctor_id']        =  auth()->user()->hasRole('super-admin')?$doctorName : ''; 
        $searchHTML['doctor_id']        =  $doctorName ?? '';
        $searchHTML['appointment_type_id']   =  $appointmentTypeName ?? '';

        $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';
        /*}*/

        $searchHTML['actions'] = $seachAction;

        //dd($searchHTML);
        array_unshift($data, $searchHTML);

        // wrapping up
        $this->JsonData['draw']             = intval($request->draw);
        $this->JsonData['recordsTotal']     = intval($totalData);
        $this->JsonData['recordsFiltered']  = intval($totalFiltered);
        $this->JsonData['data']             = $data;
        //dd( $this->JsonData['data'] );
        return response()->json($this->JsonData);
    }

    public function _storeOrUpdate($collection, $request)
    {

        Log::info('in _storeOrUpdate');

        if (!empty($request->doctor_id)) {
            $doctor_id = $request->doctor_id;
        } else {
            $doctor_id = Auth::user()->id;
        }
        //Added by swati 8-Jun-23===================================================
        $collection->appointment_status = '';
        $newdate = date("Y-m-d", strtotime($request->start_date));
        $todayDate = date('Y-m-d');
        if ($newdate < $todayDate) $collection->appointment_status = 'Fertig';
        //=========================================================================
        $collection->patient_id  = $request->patient_id;
        $collection->doctor_id   = $doctor_id;
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


        Log::info('in collection');
        Log::info($collection);


        $collection->save();
        return $collection;
    }

    /*public function _getEndDate($start_date,$appointment_type_id){

        $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id);
        //->first(['duration'])
        // dd($appointmentType);
        if(!empty($appointmentType)){
            $duration = $appointmentType->duration;
            $end_date = date("Y-m-d H:i", strtotime('+'.$duration.' minutes', strtotime($start_date)));
        }
        // dd($start_date,$end_date);
        return $end_date;
    }*/
   public function _getNotifyTime($start_date){

        $notify_times = [];
        if(!empty($start_date)){

            $notify_times[] = date("Y-m-d H:i", strtotime('-2 hour', strtotime($start_date)));
            
            $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($start_date)));
            $two_day_before   = date("Y-m-d H:i", strtotime('-2 day', strtotime($start_date)));
            $three_day_before   = date("Y-m-d H:i", strtotime('-3 day', strtotime($start_date)));


            $notify_times[] = date("Y-m-d H:i", strtotime($previous_day." 09:00"));//previous day morning

            $notify_times[] = date("Y-m-d H:i", strtotime($previous_day." 12:00"));//previous day afternoon

            $notify_times[] = date("Y-m-d H:i", strtotime($previous_day." 05:00"));//previous day evening
            
            $notify_times[] = $two_day_before;  //two day before
            $notify_times[] = $three_day_before; //three day before

        }
        // dd($start_date,$end_date);
        return $notify_times;
    }


    public function getDoctorTimeFrames(Request $request)
    {
        // dump($request->all());
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');
        try {
            if (!empty($request->doctor_id)) {
                $doctor_id = $request->doctor_id;
            } else {
                $doctor_id = Auth::user()->id;
            }
            $patient_id   = $request->patient_id;
            //$doctor_id    = auth()->user()->hasRole('super-admin') ? $request->doctor_id : Auth::user()->id;
            $appointment_type_id = $request->appointment_type_id;
            $appointment_date       = date("Y-m-d", strtotime($request->appointment_date));
            $sel_time_frame         = $request->sel_time_frame;
            $day_of_week = date('N', strtotime($appointment_date));
            // $edit_appointment_id    = $request->edit_appointment_id;
            // dd($appointment_date);
            /*$weekDay = date('N',strtotime($appointment_date));
            //dump($patient_id,$doctor_id,$appointment_type_id,$appointment_date,$weekDay);*/

            // $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id); //commented on 13-apr-26
             $appointmentType = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id);//changed on 13-apr-26

            $appointmentDuration = 0;
            if (!empty($appointmentType)) {
                $appointmentDuration = $appointmentType->duration * 60; //convert min into sec
            }
            $doctor_appointment_time_frames = $this->BaseModel
                ->where('doctor_id', $doctor_id)
                // ->where('appointment_type_id',$appointment_type_id)
                ->whereDate('start_date', $appointment_date)
                //->whereNotNull('deleted_at')
                ->whereStatus(1); //1=>Confirmed
            //->where('patient_id','!=',$patient_id)
            // ->select( DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date"))
            // ->get();
            if (!empty($sel_time_frame) && !empty($patient_id)) { //edit functionality
                $doctor_appointment_time_frames = $doctor_appointment_time_frames
                    ->where('patient_id', '!=', $patient_id);
            }
            /*if(!empty($edit_appointment_id) && !empty($edit_appointment_id)){//edit functionality
                $edit_appointment_id = base64_decode(base64_decode($edit_appointment_id));
                $doctor_appointment_time_frames = $doctor_appointment_time_frames
                                                    ->where('id','=',$edit_appointment_id);
                //need to check appointment id to get start date of that appointment ,instead of patient id
            }*/
            $doctor_appointment_time_frames = $doctor_appointment_time_frames
                ->select(DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date,(DATE_FORMAT(end_date,'%H:%i')) as end_date"))
                ->get();
            /*$doctor_atf = array();
            if(!empty($doctor_appointment_time_frames)){
                $doctor_atf = array_column($doctor_appointment_time_frames->toArray(), 'start_date');
            }*/
            // dump($doctor_appointment_time_frames,$doctor_atf);
            /*old logic
            $time_frames = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                ->join('roster_has_dates_has_time_frames','roster_has_dates_has_time_frames.date_id','roster_has_dates.id')
                                ->where('roster.doctor_id',$doctor_id)
                                // ->where('roster.appointment_type_id',$appointment_type_id)
                                ->whereDate('roster_has_dates.date',$appointment_date)
                                ->get(['roster_has_dates_has_time_frames.time_frame']);*/
            $time_frames = $this->RosterModel
                ->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')
                //->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id')
                ->join("roster_has_weeks_has_time_frames", function ($join) {
                    $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")
                        ->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")
                        ->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
                })
                ->where('roster.doctor_id', $doctor_id)
                ->where('roster_has_dates.is_excluded', '=', 0)
                ->whereDate('roster_has_dates.date', $appointment_date)
                ->where('roster_has_weeks_has_time_frames.week_day_id', $day_of_week)
                //->where('roster_has_weeks_has_time_frames.time_frame_flag','0')
                // ->where(function($query) use($appointment_date){
                //     $query->where(function($query) use($appointment_date){
                //         $query->where('roster_has_weeks_has_time_frames.start_date','<=',$appointment_date)
                //         ->where('roster_has_weeks_has_time_frames.end_date','>=',$appointment_date);
                //     })->orWhere(function($query){
                //         $query->whereNull('roster_has_weeks_has_time_frames.start_date')
                //         ->whereNull('roster_has_weeks_has_time_frames.end_date');
                //     });
                // })
                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))
                ->groupBy('roster_has_weeks_has_time_frames.time_frame')
                // ->get(['roster_has_weeks_has_time_frames.time_frame']);
                ->get(['roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);
            $html = "<option value=''>" . __('admin.TITLE_SELECT_TIME_FRAME_TEXT') . "</option>";
            $msg =  __('admin.ERR_TIME_FRAME_NOT_FOUND');
            $current_time = date("H:i", time());
            $today_date = date("Y-m-d", time());
            $ignore_time_slots = [];
            if (!empty($time_frames) && count($time_frames) > 0) {
                //Added by Shyam 24-02-22
                $allTimeFrames = [];
                foreach ($time_frames as $timeFrame) {
                    $allTimeFrames[] = date("H:i", strtotime($timeFrame->time_frame));
                }
                //Added by Shyam 24-02-22
                $msg = '';
                foreach ($time_frames as $key => $time_frame) {
                    $time      = date("H:i", strtotime($time_frame->time_frame));
                    $added_time_frame =  date("H:i", strtotime($time) + $appointmentDuration);
                    //if Condition Added by Shyam 24-02-22
                    $newAdded_time_frame =  date("H:i", strtotime($time) + ($appointmentDuration - 600));
                    $timeText = '';
                    if (count($time_frames) - 1 == $key && $appointmentType->duration == 10) //default 10 mins
                    {
                        $timeText = 'stop';
                    } elseif (in_array($added_time_frame, $allTimeFrames)) {
                        $timeText = 'continue';
                    } elseif (count($time_frames) - 2 == $key && in_array($newAdded_time_frame, $allTimeFrames)) {
                        $timeText = 'continue';
                    }
                    if (in_array($time, $allTimeFrames) && ($timeText == 'continue' || $timeText == 'stop')) {
                        $selected = "";
                        if ($sel_time_frame == $time) {
                            $selected = "selected";
                        }
                        if (!empty($doctor_appointment_time_frames)) {
                            foreach ($doctor_appointment_time_frames as $doctor_appointment_time_frame) {
                                if (strtotime($time) < strtotime($doctor_appointment_time_frame->start_date) && strtotime($added_time_frame) > strtotime($doctor_appointment_time_frame->end_date)) {
                                    //case for 9:20-9:50 from booked 9:30-9:45
                                    $ignore_time_slots[] = $time;
                                }
                                //|| ($time>=$doctor_appointment_time_frame->start_date && $time<=$doctor_appointment_time_frame->end_date)
                                if ($time == $doctor_appointment_time_frame->start_date  || ($added_time_frame > $doctor_appointment_time_frame->start_date && $added_time_frame <= $doctor_appointment_time_frame->end_date)) {
                                    //case for begin date, inbetween, overide after add
                                    $ignore_time_slots[] = $time;
                                }
                                if (($time >= $doctor_appointment_time_frame->start_date && $time < $doctor_appointment_time_frame->end_date)) {
                                    $ignore_time_slots[] = $time;
                                }
                            }
                        }
                        //dump($ignore_time_slots,$added_time_frame);
                        /*if(!empty($sel_time_frame)){//edit functionality
                           if(($key = array_search($sel_time_frame, $ignore_time_slots)) !== false) {
                                unset($ignore_time_slots[$key]);
                            }
                        }*/
                        // dump($ignore_time_slots);
                        // if(!in_array($time, $doctor_atf)) {
                        if (!in_array($time, $ignore_time_slots)) {
                            if (strtotime($today_date) == strtotime($appointment_date)) {
                                if (($time >= $current_time) || (!empty($sel_time_frame) && $sel_time_frame == $time)) {
                                    $html .= "<option " . $selected . " value='" . $time . "' lang='" . $time_frame->r_id . "'>" . $time . "</option>";
                                }
                            } elseif (strtotime($today_date) !== strtotime($appointment_date)) {
                                $html .= "<option " . $selected . " value='" . $time . "' lang='" . $time_frame->r_id . "'>" . $time . "</option>";
                            }
                        }
                        // }
                    }
                }
            }
            $this->JsonData['html'] = $html;
            $this->JsonData['data'] = $time_frames;
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
        } catch (Exception $e) {
            $this->JsonData['exception'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }

    public function selectTimeFrame(Request $request)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');
        try {
            $updateTimeFrameFlg = $this->RosterHasWeeksHasTimeFramesModel->find($request->time_frame_id);
            $updateTimeFrameFlg->time_frame_flag = '1';
            $updateTimeFrameFlg->time_frame_flag_date = Date('Y-m-d H:i:s');
            $updateTimeFrameFlg->save();
            if (isset($request->time_frame_id_old) && !empty($request->time_frame_id_old) && $request->time_frame_id_old != 'undefined') {
                $oldUpdateTimeFrameFlg = $this->RosterHasWeeksHasTimeFramesModel->find($request->time_frame_id_old);
                $oldUpdateTimeFrameFlg->time_frame_flag = '0';
                $oldUpdateTimeFrameFlg->time_frame_flag_date = Date('Y-m-d H:i:s');
                $oldUpdateTimeFrameFlg->comment = $oldUpdateTimeFrameFlg->comment . '##### Edit Time frame current Date : ' . Date('Y-m-d H:i:s');
                $oldUpdateTimeFrameFlg->save();
            }
        } catch (Exception $e) {
            $this->JsonData['exception'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }

    public function _createAppointmentNotification()
    {
        //Appointment notification according to datetime by checking appointment id

        //add the data for notification

        //appointment id exisit then delete and add

        //create nofity time slots , iterated it and save it one by one


        $request = array(
            'patient_id' => $collection->google_event_id,
            'appointment_id' => $summary,
            'notify_time' => $description,
            'status' => $request->start_date,
        );

        $appointment_has_notify = new $this->AppointmentHasNotificationModel;
        $appointment_has_notify->patient_id = $patient_id;
    }

    public function doctorDashboard()
    {
        $data = Session::get('redirect_arr');
        $patient_id = $doc_send_msg = '';
        if (!empty($data)) {
            if (!empty($data['p_id'])) {
                $doc_send_msg = __('admin.TITLE_DOCUMENT_SEND');
                $patient_id = $data['p_id']; //set success mesg for import finding
                Session::put('redirect_arr', '');
            }
        }
        $this->ModuleTitle  = __('admin.TITLE_DASHBOARD');
        $this->ViewData['moduleTitle']  = 'Doctor Dashboard';
        $this->ViewData['moduleAction'] = $this->ModuleTitle;
        $this->ViewData['modulePath']   = 'admin.doctor-dashboard';
        $this->ViewData['patient_id']   = $patient_id;
        $this->ViewData['doc_send_msg'] = $doc_send_msg;


        $this->ViewData['user'] = $this->AdminUserModel
            ->whereHas('roles', function ($query) {
                $query->where('name', 'doctor');
            })
            ->get();
        $this->ViewData['setting_value'] = $this->SettingsModel
            ->where('setting_key', 'NEW_WINDOW_SETTING')
            ->pluck('setting_value')
            ->first();
        $setting_value = json_decode($this->ViewData['setting_value']);
        $this->ViewData['width'] = $setting_value->width;
        $this->ViewData['height'] = $setting_value->height;
        $this->ViewData['position'] = $setting_value->position;
        //dd($this->ViewData);
        $this->ViewData['msg_send_doc_for_patient'] = __('admin.MSG_SEND_DOC_FOR_PATIENT');
        $this->ViewData['title_warning']       = __('admin.RESP_WARNING');
        return view($this->ModuleView . 'doctor-dashboard', $this->ViewData);
    }

    public function storeDismissal(Request $request)
    {
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_CREATE');

        try {

            DB::beginTransaction();

            if (!empty($request->id) && !empty($request->p_id)) {
                //dd($request->flag);
                if ($request->flag == "false") {

                    $oldRecord = $this->PatientsHasDismissalModel
                        ->where('fk_patient_id', $request->p_id)
                        ->where('fk_dismissal_id', $request->id)
                        ->where('appointment_id', $request->a_id)
                        ->delete();


                    DB::commit();
                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']      = __('admin.APPOINTMENT_CREATED');
                } else {

                    $oldRecord = $this->PatientsHasDismissalModel
                        ->where('fk_patient_id', $request->p_id)
                        ->where('fk_dismissal_id', $request->id)
                        ->where('appointment_id', $request->a_id)
                        ->first();

                    if (empty($oldRecord)) {
                        $DismissalModel = new $this->PatientsHasDismissalModel;
                        $DismissalModel->fk_patient_id   = $request->p_id;
                        $DismissalModel->fk_dismissal_id = $request->id;
                        $DismissalModel->appointment_id  = $request->a_id;
                        $DismissalModel->type            = 'dismissal';
                        $DismissalModel->status          = '1';
                        $DismissalModel->created_at      = date('Y-m-d');
                        $DismissalModel->save();

                        DB::commit();
                        $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                        $this->JsonData['msg']      = __('admin.APPOINTMENT_CREATED');
                    } else {
                    }
                }
            } else {
                $this->JsonData['msg']      = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $e->getMessage();
            }
        } catch (\Exception $e) {
            DB::rollback();

            $this->JsonData['msg']      = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function fetchExaminationForDashboard($patientAge, $patient_id, $appointment_id)
    {
        $getRelatedServices = $this->ExaminationsModel
            ->join('appointment_has_examinations', function ($index) use ($patient_id, $appointment_id) {
                $index->on('examination_id', 'examinations.id');
                $index->where('appointment_id', $appointment_id);
                $index->where('patient_id', $patient_id);
            })
            ->withTrashed()//added on 13-apr-26 for #383
            ->get([
                'examinations.id',
                'examinations.name',
                'examinations.show_as_control',
                'examinations.on_dashboard',
                'examinations.status',
                'appointment_has_examinations.id as exam_sort_id'
            ]);

        return $getRelatedServices;
    }
    public function getEvents(Request $request)
    {
        $slot_array = [];
        $start = strtotime('07:00');
        $end = strtotime('23:00');
        if (isset($request->doctor_id) && $request->doctor_id != 'undefined') {
            $doctor_id = $request->doctor_id;
        } elseif (auth()->user()->hasRole('Doctor')) {
            $doctor_id = auth()->user()->id;
        }
        $out = [];
        $str = '';
        // DB::enableQueryLog();
        $result = $this->BaseModel
            ->select('patients.id as p_id', 'patients.age as age', 'patients.family_name', 'patients.first_name', 'appointment.start_date', 'appointment.google_event_id', 'appointment_types.name', 'appointment_types.id as type_id', 'google_colors.code', 'appointment.id as a_id', 'appointment.notes', 'appointment.end_date')
            ->leftjoin('patients', 'patients.id', 'appointment.patient_id')
            ->leftjoin('users', 'users.id', 'appointment.doctor_id')
            ->leftjoin('google_colors', 'google_colors.id', 'users.google_color_id')
            ->leftjoin('appointment_types', 'appointment_types.id', 'appointment.appointment_type_id');
        if (!empty($doctor_id)) {
            /*Aishwarya added on 11-june-25*/
            $result =  $result->where('appointment.doctor_id',$doctor_id);
          /*  Aishwarya commented on 11-june-25 */
            //$result = $result->whereIn('appointment.doctor_id', array($doctor_id, 16));
        }
        $result = $result->where('appointment_status', 'Aktuell')
            ->orderby('start_date', 'ASC')
            ->get();
        // dd(DB::getQueryLog());
        if (!empty($result) && count($result)) {
            // DISMISSAL RESULT status
            $dismissal_result = $this->DismissalModel
                ->where('status', '1')
                ->get();
            //dd($dismissal_result);                    
            //END OF DISSMISSAL RESULT 
            foreach ($result as $key => $value) {
                $PatientsHasDismissalModel = $this->PatientsHasDismissalModel
                    ->where('appointment_id', $value->a_id)
                    ->pluck('fk_dismissal_id')
                    ->toArray();
                $gdf_path = Null;
                $findings_count = [];
                $checklist = [];
                $examination_recommended_count = collect();
                $checklist_count = collect();
                $generalChecklist_count = collect();
                $findings_count = collect();

                $unsigned_document_count = collect();
                $unsigned_checklist_count = collect();
                $unsigned_general_checklist_count = collect();
                $unsigned_general_document_count = collect();

                $signed_document_count = collect();
                $signed_checklist_count = collect();
                $signed_general_checklist_count = collect();
                $signed_general_document_count = collect();

                $qr_string = $value->a_id . "-" . $value->p_id;
                $examination = self::fetchExamination($value->age, $value->p_id, $value->a_id);
                $examination_control = self::fetchExaminationControl($value->age, $value->p_id, $value->a_id);

                //Added new function call for services
                $getRelatedServices = self::fetchExaminationForDashboard($value->age, $value->p_id, $value->a_id);
                if (!empty($examination) && count($examination) > 0) {
                    $examination_recommended_count = $examination->filter(function ($item) {
                        //if($item->exam_sort_id !='' && $item->on_dashboard == '1')
                        if ($item->exam_sort_id != '') {
                            return $item;
                        }
                    });
                    // $examination_control = $examination_recommended_count->filter(function($item)
                    //     {
                    //         if($item->show_as_control == 1 && $item->status == 1)
                    //         {
                    //             return $item;
                    //         }
                    //     });
                    //dump($examination_control);
                    if (!empty($examination_control)) {
                        $examination_control = $examination_control->map(function ($item) use ($value) {
                            $is_exist = $this->PatientsHasServiceControlReminderModel->where(['patient_id' => $value->p_id, 'appointment_id' => $value->a_id, 'service_id' => $item->id])->first();

                            if (empty($is_exist)) {
                                $isSettingSet = $this->ChannelsRemindersSettingModel
                                    ->where(['type' => 'service', 'service_id' => $item->id])
                                    ->where('checkup_period_controls', '!=', '0')
                                    ->select('checkup_period_controls', 'checkup_period_frequency_type')->first();
                                if (empty($isSettingSet)) {
                                    $control_data = $this->ChannelsRemindersSettingModel->where('type', 'global')->select('checkup_period_controls', 'checkup_period_frequency_type')->first();
                                    if (!empty($control_data)) {
                                        $item->checkup_period_controls = $control_data->checkup_period_controls;
                                        $item->checkup_period_frequency_type = $control_data->checkup_period_frequency_type;
                                        $item->status = 1;
                                        $item->checked = 0;
                                    } else {
                                        $item->checkup_period_controls = NULL;
                                        $item->checkup_period_frequency_type = NULL;
                                        $item->status = 1;
                                        $item->checked = 0;
                                    }
                                } else {
                                    $item->checkup_period_controls = $isSettingSet->checkup_period_controls;
                                    $item->checkup_period_frequency_type = $isSettingSet->checkup_period_frequency_type;
                                    $item->status = 1;
                                    $item->checked = 0;
                                }
                            } else {
                                $item->checkup_period_controls = $is_exist->control_interval;
                                $item->checkup_period_frequency_type = $is_exist->control_frequency;
                                $item->status = $is_exist->status;
                                $item->checked = 1;
                            }
                            return $item;
                        });
                    }
                    $examination = $examination->filter(function ($item) {
                        if ($item->on_dashboard == '1') {
                            return $item;
                        }
                    });
                    //Added collection merge with services related to appointment
                    $examination = $examination->merge($getRelatedServices);
                }
                //exam_ids=array_column($examination_recommended_count->toArray(), 'id');
                $exam_ids_new = array_column($examination_recommended_count->toArray(), 'id');
                $exams_ids1 = array_unique(array_column(array_values($getRelatedServices->toArray()), 'id'));
                $exam_ids = array_merge($exams_ids1, $exam_ids_new);
                $document = self::fetchDocuments($exam_ids, $value->a_id, $value->p_id);
                $GeneralDocument = self::fetchGeneralDocuments($value->p_id, $value->a_id);
               // dump($document);
                if (!empty($document) && count($document) > 0) {

                    $unsigned_document_count = $document->filter(function ($item) {
                        if (!empty($item->document_name)) {
                            $current_status = explode(",", $item->doclist_status);
                            if ((in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc == 'sign') {
                                return $item;
                            }
                            // elseif(( (in_array('1',$current_status)) || (in_array('0',$current_status)) ) && !in_array('2',$current_status) && $item->signDoc == 'read')
                            // {
                            //     return $item;
                            // }
                            elseif ((!in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc == 'read') {
                                return $item;
                            } elseif ((in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc != 'read') {
                                return $item;
                            } elseif ((!in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc != 'read') {
                                return $item;
                            }
                        }
                    });
                   // dump("unsigned_document_count==>");
                   //  dump($unsigned_document_count);

                    $signed_document_count = $document->filter(function ($item) {

                        //dump($item->export_status);
                        $current_status = explode(",", $item->doclist_status);
                        // if(in_array('2',$current_status) && $item->signDoc == 'sign' && $item->export_status == '0' && !empty($item->document_path))
                        // {
                        //     return $item; 
                        // }
                        if (in_array('1', $current_status) && $item->signDoc == 'read' && $item->export_status == '0' && !empty($item->document_path)) {
                            return $item;
                        } elseif (in_array('2', $current_status) && $item->export_status == '0' && !empty($item->document_path)) {
                            return $item;
                        }
                    });

                    // dump("signed_document_count==>");
                    // dump($signed_document_count);
                }
                //dump(count($unsigned_document_count));
                // Geneal Document
                if (!empty($GeneralDocument) && count($GeneralDocument) > 0) {
                    $generalDocument_count = count($GeneralDocument);
                    $unsigned_general_document_count = $GeneralDocument->filter(function ($item) {
                        if (!empty($item->document_name)) {
                           // $current_status = explode(",", $item->doc_status); //commented on 4-dec-24
                             $current_status = explode(",", $item->doclist_status);//added on 4-dec-24

                            if ((in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc == 'sing') {
                                return $item;
                            } elseif ((!in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc == 'read') {
                                return $item;
                            } elseif ((in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc != 'read') {
                                return $item;
                            } elseif ((!in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc != 'read') {
                                return $item;
                            }
                        }
                    });
                    //dd($unsigned_general_document_count);
                    $signed_general_document_count = $GeneralDocument->filter(function ($item) {
                        // $current_status = explode(",", $item->doc_status);//commented on 4-dec-24
                        // $current_status = explode(",", $item->doc_status);//commented on 4-dec-24
                        $current_status = explode(",", $item->doclist_status); //added on 4-dec-24

                        if (in_array('1', $current_status) && $item->signDoc == 'read' && $item->export_status == '0' && $item->document_path) {
                            return $item;
                        } elseif (in_array('2', $current_status) && $item->export_status == '0' && $item->document_path) {
                            return $item;
                        }
                    });
                }
                //$exam_ids = array_column($examination_recommended_count->toArray(), 'id');
                $checklist = self::fetchChecklistDocuments($exam_ids, $value->a_id, $value->p_id);
                // GENERAL CHECK LIST
                $checklist_general = self::fetchGeneralChecklistDocuments($value->p_id, $value->a_id);
                //checklist_status


                //dump("checklist_general==>");
                //dump($checklist_general);


                if (!empty($checklist_general) && count($checklist_general) > 0) {
                    $generalChecklist_count = count($checklist_general);

                     //dump("generalChecklist_count==>");
                    //  dump($generalChecklist_count);

                    $unsigned_general_checklist_count = $checklist_general->filter(function ($item) {
                        // $current_status = explode(",",$item->checklist_status);
                        // if((in_array('1',$current_status) ) && !in_array('2',$current_status))
                        // {
                        //     return $item;
                        // }
                        $current_status = explode(",", $item->checklist_status);
                        if ((in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc == 'sing') {
                            return $item;
                        } elseif ((!in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc == 'read') {
                            return $item;
                        } elseif ((in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc != 'read') {
                            return $item;
                        } elseif ((!in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc != 'read') {
                            return $item;
                        }
                    });

                    // dump("unsigned_general_checklist_count==>");
                    //  dump($unsigned_general_checklist_count);
                      
                    $signed_general_checklist_count = $checklist_general->filter(function ($item) {
                        //$current_status = explode(",",$item->checklist_status);
                        $current_status = explode(",", $item->checklist_status);
                        if (in_array('1', $current_status) && $item->signDoc == 'read' && !empty($item->checklist_path)) {
                            return $item;
                        } elseif (in_array('2', $current_status) && !empty($item->checklist_path)) {
                            return $item;
                        }
                    });

                    // dump("signed_general_checklist_count==>");
                    //  dump($signed_general_checklist_count);
                }
                //checklist_status
                if (!empty($checklist) && count($checklist) > 0) {
                    $unsigned_checklist_count = $checklist->filter(function ($item) {
                        $current_status = explode(",", $item->checklist_status);
                        // if((in_array('1',$current_status) || in_array('0',$current_status)) && !in_array('2',$current_status))
                        // {
                        //     return $item;  
                        // }
                        $current_status = explode(",", $item->checklist_status);
                        if ((in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc == 'sign') {
                            return $item;
                        } elseif ((!in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc == 'read') {
                            return $item;
                        } elseif ((in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc != 'read') {
                            return $item;
                        } elseif ((!in_array('1', $current_status)) && !in_array('2', $current_status) && $item->signDoc != 'read') {
                            return $item;
                        }
                    });
                    $signed_checklist_count = $checklist->filter(function ($item) {
                        $current_status = explode(",", $item->checklist_status);
                        $checklist_path = ($item->checklist_path) ? $item->checklist_path : $item->pdf_path;
                        if (in_array('1', $current_status) && $item->export_status == '0' && $item->signDoc == 'read' && !empty($checklist_path)) {
                            return $item;
                        } elseif (in_array('2', $current_status) && $item->export_status == '0' && !empty($checklist_path)) {
                            return $item;
                        }
                        //Added by Shyam 05-02-22
                        // elseif((!in_array('1',$current_status)) && 
                        //         !in_array('2',$current_status) && $item->signDoc == 'read')
                        // {
                        //     return $item;
                        // }
                    });
                }
                $findings = self::fetchFindings($value->p_id);
                if (!empty($findings) && count($findings) > 0) {
                    //$main_image = str_replace("/crop/", "/", $request->old_image);
                    // $old_new_img = self::StorePath($item->file);
                    // Log::info('------>');
                    // Log::info($old_new_img);
                    // Log::info('------>');
                    $findings_count = $findings->filter(function ($item) {
                        //dump($value->p_id);
                        if ($item->export_status == 0 && !empty($item->file)) {
                            return $item;
                        }
                    });
                }
                //collapsed-card
                //dd("-->",date('h:i',strtotime($value->start_date)).' - '.$value->family_name.' '.$value->first_name.' - '.$value->name);
                $str .= '<div id="' . $value->p_id . '_main_div_id" class="card  collapsed-card blue-table-box ' . $value->p_id . $value->a_id . '_main_div">
                    <div class="card-header " style="background-color:' . $value->code . '">
                        <div class="card-tools">                               

                             <span class="notification_class" lang="' . $value->p_id . $value->a_id . '" type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Leistungen"><span class="">' . count(array_unique($exam_ids_new)) . '</span></span>
                            <span class="notification_class"  lang="' . $value->p_id . $value->a_id . '"  type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Revers"><span class="">' . (count($unsigned_document_count) + count($unsigned_checklist_count) + count($unsigned_general_checklist_count) + count($unsigned_general_document_count)) . '</span></span>
                            <span class="notification_class" lang="' . $value->p_id . $value->a_id . '"  type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Befunde"><span class="">' . (count($findings_count) + count($signed_document_count) + count($signed_checklist_count) + count($signed_general_checklist_count) + count($signed_general_document_count)) . '
                            <input type="hidden" value="' . count($findings_count) . '+' . count($signed_document_count) . '+' . count($signed_checklist_count) . '+' . count($signed_general_checklist_count) . '+' . count($signed_general_document_count) . '" name="sss"/></span></span>
                        </div>
                        <div class="card-title" data-card-widget="collapse" > <span>' . date('H:i', strtotime($value->start_date)) . '</span> <div><p> ' . $value->family_name . ' ' . $value->first_name . '</p> <p>' . $value->name . '</p></div></div>

                        
                    </div>
                    <div id="' . $value->p_id . '_sub_id" class="card-body card-wrapper-block ' . $value->p_id . $value->a_id . '_sub" >
                    <div class="col-md-12">
                        <div class="col-md-12 col-sm-12 contrent-wrapper " id="popup_description">
                            <div class=left-content>
                            <p><strong>Patient:</strong> <span><a href="' . route('admin.patients.edit', [base64_encode(base64_encode($value->p_id))]) . '" class="edit-user action-icon" title="' . __('admin.TITLE_EDIT_TEXT') . '">' . $value->first_name . ' ' . $value->family_name . '</a>&nbsp;&nbsp;&nbsp;<a href="'.route('admin.patients.document.index', [ base64_encode(base64_encode($value->p_id))]).'" class="delete-user action-icon" title="'.__('admin.TITLE_VIEW_DOCUMENT_NAME').'"><i class="fa fa-file"></i></a></span></p>

                            <p><strong>Typ:</strong> <span>' . $value->name . '&nbsp;<i class="fa fa-edit  editAppointmentTypeModal" data-toggle="modal" data-id="' . $value->google_event_id . '"  data-target="#editAppointmentTypeModal" class="editType" title="Edit Appointment type"></i><span></p>

                            <p><strong>Notizen: </strong><span>' . $value->notes . '</span></p>
                            </div>
                            <div class=right-content>
                            <div class="row extra-padding">
                            <div class="col-12 col-sm-12 mb-2 ' . $value->p_id . $value->a_id . '_Examination examination_section" style="display:none">
                            <div class="wrapper-examination_section">
                            <p><strong>' . __('admin.TITLE_EXAMINATIONS_TEXT') . ': </strong></p>';
                if (!empty($examination) && count($examination) > 0) {
                     
                    $str .= '<div class="dropdown examination-extra-padding">';
                    $str .= '<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $str .= __('admin.TITLE_SELECT_EXAMINATIONS_TEXT');
                    $str .= '</button>';
                    $str .= '<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';
                    foreach ($examination as $key => $e_value) {
                        $checked = '';
                        $css = '';
                        if ($e_value->exam_sort_id != '') {
                            $checked = 'checked=checked';
                            $css = ' dont_operate ';
                        }
                        if ($e_value->recommented == '1') {
                            $css = ' dont_operate ';
                        }
                        $exam_name = '';
                        if (strlen($e_value->name) > 20) {
                            $exam_name = substr($e_value->name, 0, 20) . '...';
                        } else {
                            $exam_name = $e_value->name;
                        }
                        // $str .= '<div class="'.$value->p_id.' '.$css.'" lang="'.$value->a_id.'" ><input type="checkbox" class="addExamination" name="examination[]" value="'.$e_value->id.'" '.$checked.'>&nbsp;&nbsp;'.$exam_name."</div>";
                        $str .= '<label class="dropdown-item ' . $css . '">';
                        $str .= '<input type="checkbox" class="addExamination" name="examination[]" value="' . $e_value->id . '" ' . $checked . ' data-patient-id="' . $value->p_id . '" data-appointment-id="' . $value->a_id . '">&nbsp;&nbsp;' . $exam_name;
                        $str .= '</label>';

                    }
                    $str .= '</div>';
                    $str .= '<div class="service-text">';
                    foreach ($examination as $key => $e_value) {
                        if ($e_value->exam_sort_id != '') {
                            $exam_name = substr($e_value->name, 0, 30) . '...';
                            $str .= '<div style="padding-right: 20px;text-align: left;">' . htmlspecialchars($exam_name) . '</div>';
                        }
                    }
                    $str .= '</div>';
                    $str .= '</div>';
                    // $str .= '<input type="button" name="" value="' . __('admin.SHOW_EXAMINATION') . '" id="' . $value->p_id . '" class="displayExamination exam_btn mt-2" />';
                } else {
                    $str .= ' No Examination';
                }
                $str .= '</div></div>';
               
                // $str .='<div class="col-12 col-sm-12 mb-2  examination_section '.$value->p_id.$value->a_id.'_Reminder" style="display:none">';
                //         if(!empty($examination_control) && count($examination_control) > 0)
                //         {
                //             $str .='<p class="clg_reminder"><input type="button" name="" value="'.__("admin.TITLE_DR_DASH_REMINDER").'"  class=" exam_btn reminderAction " > </p>';
                //         }
                //         $str .='<form name="reminderUpdate'.$value->a_id.'" action="" class="reminderForm" style="display:none">
                //         <input type="hidden" name="appoitment_id" id="appoitment_id" value="'.$value->a_id.'"/>
                //         <input type="hidden" name="patient_id" id="patient_id" value="'.$value->p_id.'"/>                                    

                //         <div class="'.$value->p_id.'_control_section row frm_check_col">';
                //         if(!empty($examination_control) && count($examination_control) > 0)
                //         {
                //             foreach ($examination_control as $key => $exam_value) 
                //             {
                //                 $checkup_period_frequency_type = $exam_value->checkup_period_frequency_type;
                //                 $dcls = $mcls =$ycls = $wcls = '';
                //                 if($checkup_period_frequency_type == 'day')
                //                 {
                //                     $dcls = 'selected';
                //                 }
                //                 elseif($checkup_period_frequency_type == 'week')
                //                 {
                //                     $wcls = 'selected';
                //                 }
                //                 elseif($checkup_period_frequency_type == 'month')
                //                 {
                //                     $mcls = 'selected';
                //                 }
                //                 elseif($checkup_period_frequency_type == 'year')
                //                 {
                //                     $ycls = 'selected';
                //                 }
                //                 $checked= '';
                //                 $sty = 'style="display:none"';
                //                 if($exam_value->checked == 1)
                //                 {
                //                     $checked= "checked='checked'";
                //                     $sty = 'style="display:block"';
                //                     // onClick="sendDocumentForPatients(this,'.$value->p_id.','.$value->a_id.',null,'.$d_value->doc_id.',\'doc\')"
                //                 }
                //                 $str .= '<div class="col-sm-6 ">
                //                 <div class="checkbox_wraper">
                //                     <input data-rem-p-id="'.$value->p_id.'" data-rem-a-id="'.$value->a_id.'" data-rem-id="'.$exam_value->id.'"  onClick="chk_reminder(this)" id="rem_checkbox_reminder_'.$exam_value->id.'_'.$value->p_id.'_'.$value->a_id.'" type="checkbox"  name="checkbox['.$exam_value->id.']" lang="1" '.$checked.' value="1" >&nbsp;<span>'.$exam_value->name.'</span></div>
                //                 <div class="row">
                //                 <div '.$sty.' id="rem_div_'.$exam_value->id.'_'.$value->p_id.'_'.$value->a_id.'" class="col-sm-2 form-group reminder_input">  
                //                     <input                                       
                //                         type="text" 
                //                         name="checkup_period_controls['.$exam_value->id.']"
                //                         id="checkup_period_controls" 
                //                         class="form-control number" 
                //                         required 
                //                         value="'.$exam_value->checkup_period_controls.'"
                //                     >
                //                 </div>
                //                 <div '.$sty.' id="period_frequency_div_'.$exam_value->id.'_'.$value->p_id.'_'.$value->a_id.'" class="col-sm-4 reminder_input" >       
                //                     <select                                        
                //                     class="form-control" 
                //                     name="checkup_period_frequency_type['.$exam_value->id.']"
                //                     id="checkup_period_frequency_type">
                //                     <option value="day" '.$dcls.'>'.__("admin.TITLE_FREQUENCY_DAY").'</option>
                //                     <option value="week" '.$wcls.'>'.__("admin.TITLE_FREQUENCY_WEEK").'</option>
                //                     <option value="month"  '.$mcls.'>'.__("admin.TITLE_FREQUENCY_MONTH").'</option>
                //                     <option value="year" '.$ycls.'>'.__("admin.TITLE_FREQUENCY_YEAR").'</option>
                //                     </select>
                //                 </div></div></div>
                //                 ';
                //             }
                //         }                                    
                //         $str .='<div class="col-sm-6 ">
                //         <input reminder-a-id="'.$value->a_id.'"  reminder-p-id="'.$value->p_id.'" type="button" name="" value="'.__("admin.TITLE_CHANGE_PASSWORD_BUTTON")." ".__("admin.TITLE_REMINDER").'"  class="update_reminder exam_btn mt-2" "></div></form></div></div>
                //         </div>
                //         <div class="col-md-12 col-sm-12 extra-padding" style="clear: both;">
                //         <div class="'.$value->p_id.$value->a_id.'_Document document_section" style="display:none">
                //         <p><strong>'.__('admin.TITLE_DOCUMENT_NAME').': </strong></p>
                //         <div class="col-md-12">
                //         <div class="row" style="display: flex;flex-wrap: nowrap;">
                //         <div class="col-md-2">'.__('admin.TITLE_SELECT_DOCUMENT_READ').'</div>
                //         <div class="col-md-3" style> '.__('admin.TITLE_SELECT_DOCUMENT_SIGN').'</div>
                //         <div class="col-md-7"> </div>
                //     </div>
                // </div>';
                $str .= '<div class="col-12 col-sm-12 mb-2 examination_section ' . $value->p_id . $value->a_id . '_Reminder" style="display:none">';
                $str .= '<div class="wrapper-examination_section"> <p><strong>';
                $str .= __("admin.TITLE_DR_DASH_CONTROL_REMINDER");
                $str .= ':</strong></p>';

                // if (!empty($examination_control) && count($examination_control) > 0) {
                //     $str .= '<p class="clg_reminder">';
                //     $str .= '<input type="button" name="" value="' . __("admin.TITLE_DR_DASH_REMINDER") . '" class="exam_btn reminderAction">';
                //     $str .= '</p>';
                // }

                $str .= '<form name="reminderUpdate' . $value->a_id . '" action="" class="reminderForm" >';
                $str .= '<input type="hidden" name="appoitment_id" id="appoitment_id" value="' . $value->a_id . '"/>';
                $str .= '<input type="hidden" name="patient_id" id="patient_id" value="' . $value->p_id . '"/>';

                $str .= '<div class="' . $value->p_id . '_control_section row frm_check_col">';

                if (!empty($examination_control) && count($examination_control) > 0) {
                    $str .= '<div class="dropdown">';
                    $str .= '<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $str .= __("admin.TITLE_DR_DASH_SELECT_REMINDER");
                    $str .= '</button>';
                    $str .= '<div class="dropdown-menu p-3" aria-labelledby="dropdownMenuButton" style="width: 300px;">';

                    foreach ($examination_control as $key => $exam_value) {
                        $checked = '';
                        $css = '';

                        if ($exam_value->checked == 1) {
                            $checked = "checked='checked'";
                            $css = 'dont_operate';
                        }

                        $exam_name = (strlen($exam_value->name) > 20) ? substr($exam_value->name, 0, 20) . '...' : $exam_value->name;

                        // Checkbox with label inside dropdown
                        $str .= '<label class="dropdown-item ' . $css . '">';
                        $str .= '<input data-rem-p-id="' . $value->p_id . '" data-rem-a-id="' . $value->a_id . '" data-rem-id="' . $exam_value->id . '" onClick="chk_reminder(this)" id="rem_checkbox_reminder_' . $exam_value->id . '_' . $value->p_id . '_' . $value->a_id . '" type="checkbox" name="checkbox[' . $exam_value->id . ']" lang="1" ' . $checked . ' value="1">&nbsp;<span>' . $exam_name . '</span>';
                        $str .= '</label>';

                        // Additional inputs shown when checkbox is selected
                        $sty = 'style="display:none"';
                        if ($exam_value->checked == 1) {
                            $sty = 'style="display:block"';
                        }

                        $dcls = $wcls = $mcls = $ycls = '';
                        switch ($exam_value->checkup_period_frequency_type) {
                            case 'day':
                                $dcls = 'selected';
                                break;
                            case 'week':
                                $wcls = 'selected';
                                break;
                            case 'month':
                                $mcls = 'selected';
                                break;
                            case 'year':
                                $ycls = 'selected';
                                break;
                        }

                        $str .= '<div class=" mb-2" ' . $sty . ' id="rem_div_' . $exam_value->id . '_' . $value->p_id . '_' . $value->a_id . '"><div class="row mt-2">';
                        $str .= '<div class="col-sm-6 form-group reminder_input">';
                        $str .= '<input type="text" name="checkup_period_controls[' . $exam_value->id . ']" id="checkup_period_controls" class="form-control number" required value="' . $exam_value->checkup_period_controls . '">';
                        $str .= '</div>';
                        $str .= '<div class="col-sm-6 reminder_input">';
                        $str .= '<select class="form-control" name="checkup_period_frequency_type[' . $exam_value->id . ']" id="checkup_period_frequency_type">';
                        $str .= '<option value="day" ' . $dcls . '>' . __("admin.TITLE_FREQUENCY_DAY") . '</option>';
                        $str .= '<option value="week" ' . $wcls . '>' . __("admin.TITLE_FREQUENCY_WEEK") . '</option>';
                        $str .= '<option value="month" ' . $mcls . '>' . __("admin.TITLE_FREQUENCY_MONTH") . '</option>';
                        $str .= '<option value="year" ' . $ycls . '>' . __("admin.TITLE_FREQUENCY_YEAR") . '</option>';
                        $str .= '</select>';
                        $str .= '</div>';
                        $str .= '</div></div>';
                    }

                    $str .= '</div>';
                    $str .= '</div>';
                }

                $str .= '<div class="col-md-12">';
                $str .= '<input reminder-a-id="' . $value->a_id . '" reminder-p-id="' . $value->p_id . '" type="button" name="" value="' . __("admin.TITLE_CHANGE_PASSWORD_BUTTON") . " " . __("admin.TITLE_REMINDER") . '" class="update_reminder exam_btn mt-2 btn">';
                $str .= '</div>';

                $str .= '</form>';
                $str .= '</div>';
                $str .= '</div>';
                $str .= '</div>';
                $str .= '<div class="col-md-12 col-sm-12 extra-padding mt-2" style="clear: both;">';
                $str .= '<div class="' . $value->p_id . $value->a_id . '_Document document_section" style="display:none">';
                $str .= '<p><strong>' . __('admin.TITLE_DOCUMENT_NAME') . ': </strong></p>';
                $str .= '<div class="col-md-12">';
                $str .= '<div class="row" style="display: flex;flex-wrap: nowrap;">';
                // $str .= '<div class="col-md-6">' . __('admin.TITLE_SELECT_DOCUMENT_READ') . '</div>';
                // $str .= '<div class="col-md-6">' . __('admin.TITLE_SELECT_DOCUMENT_SIGN') . '</div>';
                $str .= '<div class="col-md-6"> </div>';
                $str .= '</div>';
                $str .= '</div>';


                if (!empty($unsigned_document_count) && count($unsigned_document_count) > 0) {

                    // Log::info($unsigned_document_count);

                   // dump("in unsigned_document_count==>");
                   // dump($unsigned_document_count);

                    foreach ($unsigned_document_count as $key => $d_value) {
                        if (!empty($d_value->document_path)) {
                            $gdf_path = self::getFilePath($d_value->document_path);

                        }
                        $read = '';
                        $signed = '';
                        $myStatus = explode(',', $d_value['doc_status']);
                        if (empty($d_value['doc_status'])) {
                            $myStatus = explode(',', $d_value['doclist_status']);
                        }

                        // Log::info($myStatus);
                        //dump($c_value->checklist_path);
                        if (in_array('1', $myStatus)) {
                            $read = "checked='checked'";
                        }
                        if (in_array('2', $myStatus)) {
                            $signed = "checked='checked'";
                        }
                        // echo $d_value->doc_id."<pre>";print_r($myStatus);
                        $str .= '<div class="col-md-12">
                                    <div class="row" style="display: flex;flex-wrap: wrap;">
                                    <div class="col-md-6">';
                        if (!in_array('2', $myStatus)) {
                            $str .= '<input type="checkbox"  name="examination[]"  ' . $read . ' lang="1" value="' . $d_value->doc_id . '" class="updateDocumentStatus">';

                            $str .= '&nbsp;&nbsp&nbsp;<i style="color:blue;"  class="fa fa-file" aria-hidden="true" title="' . __('admin.TITLE_DOCUMENT_BUTTON') . '"></i>
                                        <a href="' . $gdf_path . '" target="__blank">' . $d_value->document_name . '</a>';
                        }

                        $str .= '</div>';

                        if (in_array('2', $myStatus)) {
                            $str .= '<div class="col-md-6">';
                            $str .= '<input  type="checkbox"  name="examination[]" lang="2" class="updateDocumentStatus"  ' . $signed . ' value="' . $d_value->doc_id . '">&nbsp;&nbsp&nbsp;';
                            $str .= '&nbsp;&nbsp&nbsp;<i style="color:blue;"  class="fa fa-file" aria-hidden="true" title="' . __('admin.TITLE_DOCUMENT_BUTTON') . '"></i>
                                        <a href="' . $gdf_path . '" target="__blank">' . $d_value->document_name . '</a></div>';
                        }
                        $str .= '<div class="col-md-6">
                                    <a lang-type="services" lang-exam="' . $d_value->id . '" onClick="sendDocumentForPatients(this,' . $value->p_id . ',' . $value->a_id . ',null,' . $d_value->doc_id . ',\'doc\')" title="' . __('admin.TITLE_TODO_LIST_SEND_FINDING') . '"><i class="fa fa-envelope" aria-hidden="true"></i></a>&nbsp;&nbsp;';
                        //dump($d_value->document_path);
                        if (!empty($d_value->document_path)) {

                            $gdf_path = self::getFilePath($d_value->document_path);
                            $extension = explode(".", $d_value->document_path);
                            if (sizeof($extension) > 0) {
                                if ($extension[1] == 'pdf' || $extension[1] == 'PDF') {
                                    $str .= '<a lang-exam="' . $d_value->id . '" lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" lang="' . $d_value->doc_id . '" lang-type="doc"  class="updatePrintStatus" title="' . __('admin.TITLE_PRINT') . '" href="' . $gdf_path . '" target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                                } else {
                                    $str .= '<a lang-exam="' . $d_value->id . '" lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" lang-path="' . $gdf_path . '" onClick="PrintDiv(this)" class="updatePrintStatus" lang="' . $d_value->doc_id . '" lang-type="doc" title="' . __('admin.TITLE_PRINT') . '" href="' . $gdf_path . '" target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                                }
                            }
                        }
                        $str .= '</div></div></div>';
                    }
                }
                if (!empty($unsigned_general_document_count) && count($unsigned_general_document_count) > 0) {

                  //  dump("unsigned_general_document_count==>");
                  // dump($unsigned_general_document_count);


                    foreach ($unsigned_general_document_count as $key => $gd_value) {
                        if (isset($gd_value->document_path) && !empty($gd_value->document_path)) {


                            $gdf_path = self::getFilePath($gd_value->document_path);
                        }
                        $read = '';
                        $signed = '';
                        $myStatus = explode(',', $gd_value['doclist_status']);
                        //dump($c_value->checklist_path);
                        if (in_array('1', $myStatus)) {
                            $read = "checked='checked'";
                        }
                        if (in_array('2', $myStatus)) {
                            $signed = "checked='checked'";
                        }

                        //commented on 14-jan-25 for double checkbox
                        // $str .= '<div class="col-md-12">
                        //             <div class="row" style="display: flex;flex-wrap: nowrap;">
                        //             <div class="col-md-6"><input  type="checkbox"  name="examination[]" ' . $read . ' lang="1" value="' . $gd_value->doc_id . '" class="updateDocumentStatus">
                        //             </div>
                        //             <div class="col-md-6">
                        //             <input  type="checkbox"  name="examination[]" lang="2" class="updateDocumentStatus" ' . $signed . ' value="' . $gd_value->doc_id . '">&nbsp;&nbsp&nbsp;&nbsp&nbsp;&nbsp;<i style="color:blue;"  class="fa fa-file" aria-hidden="true" title="' . __('admin.TITLE_DOCUMENT_BUTTON') . '"></i><a href="' . $gdf_path . '" target="__blank">' . $gd_value->document_name . '</a></div>
                        //             <div class="col-md-6">
                        //             <a lang-type="services" lang-exam="' . $gd_value->id . '" onClick="sendDocumentForPatients(this,' . $value->p_id . ',' . $value->a_id . ',null,' . $gd_value->doc_id . ',\'doc\')" title="' . __('admin.TITLE_TODO_LIST_SEND_FINDING') . '"><i class="fa fa-envelope" aria-hidden="true"></i></a>&nbsp;&nbsp;';


                        //did changes on 14-jan-25 for double checkbox   
                        $str .= '<div class="col-md-12">
                                    <div class="row" style="display: flex;flex-wrap: nowrap;">
                                    
                                    <div class="col-md-6">
                                    <input  type="checkbox"  name="examination[]" lang="2" class="updateDocumentStatus" ' . $signed . ' value="' . $gd_value->doc_id . '">&nbsp;&nbsp&nbsp;<i style="color:blue;"  class="fa fa-file" aria-hidden="true" title="' . __('admin.TITLE_DOCUMENT_BUTTON') . '"></i>&nbsp;<a href="' . $gdf_path . '" target="__blank">' . $gd_value->document_name . '</a></div>
                                    <div class="col-md-6">
                                    <a lang-type="services" lang-exam="' . $gd_value->id . '" onClick="sendDocumentForPatients(this,' . $value->p_id . ',' . $value->a_id . ',null,' . $gd_value->doc_id . ',\'doc\')" title="' . __('admin.TITLE_TODO_LIST_SEND_FINDING') . '"><i class="fa fa-envelope" aria-hidden="true"></i></a>&nbsp;&nbsp;';
                                                
                        //dump($gd_value->document_path);
                        if (!empty($gd_value->document_path)) {
                            $gdf_path = self::getFilePath($gd_value->document_path);
                            $extension = explode(".", $gd_value->document_path);
                            if ($extension[1] == 'pdf' || $extension[1] == 'PDF') {
                                $str .= '<a lang-exam="' . $gd_value->id . '" lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" lang="' . $gd_value->doc_id . '" lang-type="doc"  class="updatePrintStatus" title="' . __('admin.TITLE_PRINT') . '" href="' . $gdf_path . '" target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                            } else {
                                $str .= '<a lang-exam="' . $gd_value->id . '" lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" lang-path="' . $gdf_path . '" onClick="PrintDiv(this)" class="updatePrintStatus" lang="' . $gd_value->doc_id . '" lang-type="doc" title="' . __('admin.TITLE_PRINT') . '" href="' . $gdf_path . '" target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                            }
                        }
                        $str .= '</div></div></div>';
                    }
                }



                if (!empty($unsigned_checklist_count) && count($unsigned_checklist_count) > 0) {
                    // Log::info($unsigned_checklist_count);

                    // dump("unsigned_checklist_count==>");
                   // dump($unsigned_checklist_count);


                    foreach ($unsigned_checklist_count as $key => $c_value) {
                        $read = '';
                        $signed = '';
                        $chk_path = '';
                        $myStatus = explode(',', $c_value['checklist_status']);
                        //dump($c_value->checklist_path);
                        Log::info($myStatus);
                        if (!empty($c_value->checklist_path)) {
                            $chk_path = self::getFilePath($c_value->checklist_path);
                        }
                        if (in_array('1', $myStatus)) {
                            $read = "checked='checked'";
                        }
                        //Log::info("=====".$read);
                        if (in_array('2', $myStatus)) {
                            $signed = "checked='checked'";
                        }
                        //Log::info($signed);
                        $str .= '<div class="col-md-12 checklist"><div class="row" style="display: flex;flex-wrap: nowrap;">';
                        $str .= '<div class="col-md-6">';
                        if (!in_array('2', $myStatus)) {
                            $str .= '<input type="checkbox" lang-a-id="' . $value->a_id . '"  lang-p-id="' . $value->p_id . '" lang-chk-id="' . $c_value->chk_id . '" name="examination[]" readonly ' . $read . ' lang="1" value="' . $c_value->fk_examinations_id . '" class="updateChecklistStatus" lang-type="performance">';
                            $str .= '&nbsp;&nbsp&nbsp;<i style="color:blue;"  class="fa fa-file" aria-hidden="true" title="' . __('admin.TITLE_DOCUMENT_BUTTON') . '"></i>
                                        <a href="' . $chk_path . '" target="__blank">' . $c_value->check_list_name . '</a>';
                        }
                        $str .= '</div>';
                        
                        if (in_array('2', $myStatus)) {
                            $str .= '<div class="col-md-3">';
                            $str .= '<input type="checkbox" lang-a-id="' . $value->a_id . '"  lang-p-id="' . $value->p_id . '" name="examination[]" lang-chk-id="' . $c_value->chk_id . '" lang="2" class="updateChecklistStatus" lang-type="performance" readonly ' . $signed . ' value="' . $c_value->fk_examinations_id . '">&nbsp;&nbsp&nbsp;&nbsp&nbsp;&nbsp;';
                            if (!empty($c_value->checklist_path)) {
                                $chk_path = self::getFilePath($c_value->checklist_path);
                                $str .= '<i class="fa fa-check" aria-hidden="true"></i><a href="' . $chk_path . '" target="__blank">' . $c_value->check_list_name . '</a>';
                            } else {
                                $str .= '<i class="fa fa-check" aria-hidden="true"></i><a href="javascript:void(0)">' . $c_value->check_list_name . '</a>';
                            }
                            $str .= '</div>';
                        }

                        
                        $str .= '<div class="col-md-7">';
                        if (!empty($c_value->checklist_path)) {
                            $chk_path = self::getFilePath($c_value->checklist_path);
                            $str .= '<a lang-exam="' . $c_value->fk_examinations_id . '"  lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" title="' . __('admin.TITLE_PRINT') . '" lang="' . $c_value->chk_id . '" lang-type="check_list" class="updatePrintStatus" href="' . $chk_path . '" target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                        } else {
                            $str .= '<a onClick="generateChecklistPDF(' . $c_value->chk_id . ',' . $value->p_id . ')" title="' . __('admin.TITLE_PRINT') . '" lang="' . $c_value->chk_id . '" lang-type="check_list" class=""  target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                        }
                        $str .= '</div></div>';
                        $str .= '</div>';
                    }
                }
                // if (!empty($unsigned_general_checklist_count) && count($unsigned_general_checklist_count) > 0) {
                //     foreach ($unsigned_general_checklist_count as $key => $c_value) {
                //         $read = '';
                //         $signed = '';
                //         $myStatus = explode(',', $c_value['checklist_status']);
                //         if (in_array('1', $myStatus)) {
                //             $read = "checked='checked'";
                //         }
                //         if (in_array('2', $myStatus)) {
                //             $signed = "checked='checked'";
                //         }
                //         $str .= '<div class="col-md-12 row checklist">';
                //         $str .= '<div class="col-md-2"><input type="checkbox" lang-a-id="' . $value->a_id . '"  lang-p-id="' . $value->p_id . '" lang-chk-id="' . $c_value->chk_id . '" name="examination[]" readonly ' . $read . ' lang="1"  class="updateChecklistStatus" lang-type="general">
                //                     </div>
                //                     <div class="col-md-3"><input type="checkbox" lang-a-id="' . $value->a_id . '"  lang-p-id="' . $value->p_id . '" name="examination[]" lang-chk-id="' . $c_value->chk_id . '" lang="2" class="updateChecklistStatus" lang-type="general" readonly ' . $signed . ' >&nbsp;&nbsp&nbsp;&nbsp&nbsp;&nbsp;';

                //         if (!empty($c_value->checklist_path)) {
                //             $chk_path = self::getFilePath($c_value->checklist_path);
                //             $str .= '<i class="fa fa-check" aria-hidden="true"></i><a href="' . $chk_path . '" target="__blank">' . $c_value->check_list_name . '</a>';
                //         } else {
                //             $str .= '<i class="fa fa-check" aria-hidden="true"></i><a href="javascript:void(0)">' . $c_value->check_list_name . '</a>';
                //         }
                //         $str .= '</div>';
                //         $str .= '<div class="col-md-7">';
                //         if (!empty($c_value->checklist_path)) {
                //             $chk_path = self::getFilePath($c_value->checklist_path);
                //             $str .= '<a lang-exam=""  lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" title="' . __('admin.TITLE_PRINT') . '" lang="' . $c_value->chk_id . '" lang-type="check_list" class="updatePrintStatus" href="' . $chk_path . '" target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                //         } else {
                //             $str .= '<a onClick="generateChecklistPDF(' . $c_value->chk_id . ',' . $value->p_id . ')" title="' . __('admin.TITLE_PRINT') . '" lang="' . $c_value->chk_id . '" lang-type="check_list" class=""  target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                //         }
                //         $str .= '</div>';
                //         $str .= '</div> </div>';
                //     }
                // }

                // added by vijay 9/9/24 (#187) old code commented above
                if (!empty($unsigned_general_checklist_count) && count($unsigned_general_checklist_count) > 0) {

                    // dump("unsigned_general_checklist_count==>");
                    // dump($unsigned_general_checklist_count);

                    foreach ($unsigned_general_checklist_count as $key => $c_value) {
                        $read = '';
                        $signed = '';
                        $chk_path ='';
                        $myStatus = explode(',', $c_value['checklist_status']);
                        //dump($myStatus);
                        if (in_array('1', $myStatus)) {
                            $read = "checked='checked'";
                        }
                        if (in_array('2', $myStatus)) {
                            $signed = "checked='checked'";
                        }
                        $str .= '<div class="col-md-12 checklist"><div class="row" style="display: flex;flex-wrap: nowrap;">';
                        $str .= '<div class="col-md-6">';
                        if (!in_array('2', $myStatus)) {
                            if (!empty($c_value->checklist_path)) {
                                $chk_path = self::getFilePath($c_value->checklist_path);
                            }
                            $str .= '<input type="checkbox" lang-a-id="' . $value->a_id . '"  lang-p-id="' . $value->p_id . '" lang-chk-id="' . $c_value->chk_id . '" name="examination[]" readonly ' . $read . ' lang="1" value="' . $c_value->fk_examinations_id . '" class="updateChecklistStatus" lang-type="performance">';
                            $str .= '&nbsp;&nbsp&nbsp;<i style="color:blue;"  class="fa fa-file" aria-hidden="true" title="' . __('admin.TITLE_DOCUMENT_BUTTON') . '"></i>
                                        <a href="' . $chk_path . '" target="__blank">' . $c_value->check_list_name . '</a>';
                        }
                        $str .= '</div>';
                        
                        if (in_array('2', $myStatus)) {
                            $str .= '<div class="col-md-3">';
                            $str .= '<input type="checkbox" lang-a-id="' . $value->a_id . '"  lang-p-id="' . $value->p_id . '" name="examination[]" lang-chk-id="' . $c_value->chk_id . '" lang="2" class="updateChecklistStatus" lang-type="performance" readonly ' . $signed . ' value="' . $c_value->fk_examinations_id . '">&nbsp;&nbsp&nbsp;&nbsp&nbsp;&nbsp;';
                            if (!empty($c_value->checklist_path)) {
                                $chk_path = self::getFilePath($c_value->checklist_path);
                                $str .= '<i class="fa fa-check" aria-hidden="true"></i><a href="' . $chk_path . '" target="__blank">' . $c_value->check_list_name . '</a>';
                            } else {
                                $str .= '<i class="fa fa-check" aria-hidden="true"></i><a href="javascript:void(0)">' . $c_value->check_list_name . '</a>';
                            }
                             $str .= '</div>';
                        }
                        $str .= '<div class="col-md-7">';
                        if (!empty($c_value->checklist_path)) {
                            $chk_path = self::getFilePath($c_value->checklist_path);
                            $str .= '<a lang-exam=""  lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" title="' . __('admin.TITLE_PRINT') . '" lang="' . $c_value->chk_id . '" lang-type="check_list" class="updatePrintStatus" href="' . $chk_path . '" target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                        } else {
                            $str .= '<a onClick="generateChecklistPDF(' . $c_value->chk_id . ',' . $value->p_id . ')" title="' . __('admin.TITLE_PRINT') . '" lang="' . $c_value->chk_id . '" lang-type="check_list" class=""  target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>';
                        }
                        $str .= '</div>';
                        $str .= '</div>';
                        
                        $str .= '</div> ';
                    }
                }
                // end
                $path = asset("assets/admin/images/Spinner.gif");
                $str .= '<br/><img id="barcode"
                                src="https://api.qrserver.com/v1/create-qr-code/?data=' . $qr_string . '&amp;size=150x150"  width="150" height="150" style="background: url(' . $path . ') 50% no-repeat;"/></div></div><div class="' . $value->p_id . $value->a_id . '_Findings finding_section " style="display:none;width:100%"><p><strong>' . __('admin.TITLE_FINDING_TEXT') . ': </strong></p>
                                <div class="wrapper-examination_section"><div class="col-md-12 row">
                                <div class="col-md-12">Export</div>
                                ';
                $str .= '<form name="frmExport" id="frmExport' . $value->a_id . '" method="post" class="col-md-12 row">
                                <input type="hidden" name="export_url" class="export_url" value="' . route($this->ModulePath . 'exportFindings') . '"/>

                                <input type="hidden" name="export_patient_id" class="export_patient_id" data-lang-id="' . $value->p_id . '" value="' . $value->p_id . '"/>';

                if (!empty($findings) && count($findings)) {
                    foreach ($findings as $key => $f_value) {
                        if ($f_value->export_status == 0) {
                            $fd_path = self::getFilePath($f_value->file);
                            if (!empty($f_value->file)) {
                                $str .= '<div class="col-md-12"><div class="row">
                                                <div class="col-md-10"><input type="checkbox"   name="findings[]" checked class="export_status_finding' . $value->a_id . '" value="' . $f_value->id . '" >&nbsp;&nbsp;' . date('d.m.Y', strtotime($f_value->date)) . '&nbsp&nbsp;&nbsp;<a href="' . $fd_path . '" target="_blank">' . $f_value->document_name . '</a></div> 
                                                <div class="col-md-2"><a lang-exam="' . $f_value->id . '" lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" lang="' . $f_value->id . '" lang-type="doc"  class="updatePrintStatus" title="' . __('admin.TITLE_PRINT') . '" href="' . $fd_path . '" target="_blank" ><i class="fa fa-print" aria-hidden="true"></i></a></div></div></div> 
                                                ';
                            }
                        }
                    }
                }
                if (!empty($signed_general_checklist_count) && count($signed_general_checklist_count) > 0) {

                    // dump("signed_general_checklist_count==>");
                    //dump($signed_general_checklist_count);


                    foreach ($signed_general_checklist_count as $key => $f_value) {

                         //dump(" in signed_general_checklist_count==>");

                        if ($f_value->export_status == 0) {
                              //dump(" in export signed_general_checklist_count==>");

                            if (!empty($f_value->checklist_path)) {
                                 //dump(" in not empty signed_general_checklist_count==>");

                                $fk_path = self::getFilePath($f_value->checklist_path);

                               // dump($fk_path);
                                //replaced <div class="col-md-10"> instead of<div class="col-md-4"> on 11-june-25
                                $str .= '<div class="col-md-12"><div class="row">
                                                <div class="col-md-10"><input type="checkbox"   name="checklist[]" checked class="export_status_checklist' . $value->a_id . '" value="' . $f_value->chk_id . '" >&nbsp;&nbsp;&nbsp&nbsp;&nbsp;<a href="' . $fk_path . '" target="_blank" class="link">' . $f_value->check_list_name . '</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a lang-exam="' . $f_value->id . '" lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" lang-type="checklist"  lang="' . $f_value->id . '" lang-type="doc"  class="updatePrintStatus" title="' . __('admin.TITLE_PRINT') . '"  href="' . $fk_path . '" target="_new"><i class="fa fa-print" aria-hidden="true"></i></a></div></div></div>';

                                //dump($str);
                                                
                            }
                        }
                    }
                }
                if (!empty($signed_checklist_count) && count($signed_checklist_count) > 0) {  //dd($signed_checklist_count);

                    //dump("signed_checklist_count==>");
                    //dump($signed_checklist_count);


                    foreach ($signed_checklist_count as $key => $f_value) {
                        if ($f_value->export_status == 0) {
                            if (!empty($f_value->checklist_path)) {
                                $fk_path = self::getFilePath($f_value->checklist_path);
                                $str .= '<div class="col-md-12"><div class="row">
                                                <div class="col-md-10"><input type="checkbox" name="checklist[]" checked class="export_status_checklist' . $value->a_id . '" value="' . $f_value->chk_id . '" >&nbsp;&nbsp;&nbsp&nbsp;&nbsp;<a href="' . $fk_path . '" target="_blank" class="link">' . $f_value->check_list_name . '</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a lang-exam="' . $f_value->id . '" lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" lang-type="checklist" lang="' . $f_value->id . '" lang-type="doc"  class="updatePrintStatus" title="' . __('admin.TITLE_PRINT') . '" href="' . $fk_path . '" target="_blank"><i class="fa fa-print" aria-hidden="true"></i></a></div></div></div>';
                            }
                            // else { //This else condition Added by Shyam 05-02-22
                            //     $str .= '<div class="col-md-12"><div class="row">
                            //     <div class="col-md-4"><input type="checkbox" name="checklist[]" checked class="export_status_checklist'.$value->a_id.'" value="'.$f_value->chk_id.'" >&nbsp;&nbsp;&nbsp&nbsp;&nbsp;<a href="javascript:void(0)" class="link">'.$f_value->check_list_name.'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a lang-exam="'.$f_value->id.'" lang-p-id="'.$value->p_id.'" lang-a-id="'.$value->a_id.'" lang-type="checklist" lang="'.$f_value->id.'" lang-type="doc"  class="updatePrintStatus" title="'.__('admin.TITLE_PRINT').'" href="javascript:void(0)"><i class="fa fa-print" aria-hidden="true"></i></a></div></div></div>';
                            // }
                        }
                    }
                }
                if (!empty($signed_document_count) && count($signed_document_count)) {
                    foreach ($signed_document_count as $key => $sd_value) {
                        if ($sd_value->export_status == 0) {
                            if (!empty($sd_value->document_path)) {
                                $gdf_path = self::getFilePath($sd_value->document_path);
                                $str .= '<div class="col-md-12"><div class="row">
                                                <div class="col-md-10">
                                                <input type="checkbox"   name="document[]" checked class="export_status_document' . $value->a_id . '" value="' . $sd_value->doc_id . '" >&nbsp;&nbsp;&nbsp&nbsp;&nbsp;<a href="' . $gdf_path . '" target="_blank" class="link">' . $sd_value->document_name . '</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a lang-exam="' . $sd_value->doc_id . '" lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" lang-type="document"  lang="' . $sd_value->doc_id . '" lang-type="doc"  class="updatePrintStatus" title="' . __('admin.TITLE_PRINT') . '" href="' . $gdf_path . '" target="_blank"><i class="fa fa-print" aria-hidden="true"></i></a>&nbsp;&nbsp;<a lang-type="services" lang-exam="' . $sd_value->doc_id . '" onClick="sendDocumentForPatients(this,' . $value->p_id . ',' . $value->a_id . ',null,' . $sd_value->id . ',\'doc\')" title="' . __('admin.TITLE_TODO_LIST_SEND_FINDING') . '"><i class="fa fa-envelope" aria-hidden="true"></i></a></div></div></div>';
                            }
                        }
                    }
                }
                if (!empty($signed_general_document_count) && count($signed_general_document_count)) {

                   // dump("signed_general_document_count==>");
                   // dump($signed_general_document_count);

                    
                    foreach ($signed_general_document_count as $key => $sdd_value) {
                        if ($sdd_value->export_status == 0) {
                            if (!empty($sdd_value->document_path)) {
                                $gdf_path = self::getFilePath($sdd_value->document_path);
                                $str .= '<div class="col-md-12"><div class="row">
                                                <div class="col-md-10"><input type="checkbox"   name="document[]" checked class="export_status_document' . $value->a_id . '" value="' . $sdd_value->doc_id . '" >&nbsp;&nbsp;&nbsp&nbsp;&nbsp;<a href="' . $gdf_path . '" target="_blank"  class="link">' . $sdd_value->document_name . '</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a lang-exam="' . $sdd_value->doc_id . '" lang-p-id="' . $value->p_id . '" lang-a-id="' . $value->a_id . '" lang-type="document"  lang="' . $sdd_value->id . '" lang-type="doc"  class="updatePrintStatus" title="' . __('admin.TITLE_PRINT') . '" href="' . $gdf_path . '" target="__blank"><i class="fa fa-print" aria-hidden="true"></i></a>&nbsp;&nbsp;<a lang-type="services" lang-exam="' . $sdd_value->doc_id . '" onClick="sendDocumentForPatients(this,' . $value->p_id . ',' . $value->a_id . ',null,' . $sdd_value->doc_id . ',\'doc\')" title="' . __('admin.TITLE_TODO_LIST_SEND_FINDING') . '"><i class="fa fa-envelope" aria-hidden="true"></i></a></div></div></div>';
                            }
                        }
                    }
                }
                $str .= '<div class="col-md-12"><input type="button" name="" value="Export" id="' . $value->p_id . '" lang="' . $value->a_id . '" class="exportFindings exam_btn btn" /></div></div></form>';
                $str .= '</div></div></div>
                            <div class="' . $value->p_id . $value->a_id . '_Dismissal_section dis-extra-padding">
                            
                            <div class="wrapper-examination_section">
                            <p class="m-0"><strong>' . __('admin.TITLE_DISMISSAL_TEXT') . ':</strong> &nbsp;</p>
                                <div class="row extra-padding">';



                if (!empty($dismissal_result) && count($dismissal_result) > 0) {
                    $str .= '<div class="dropdown">';
                    $str .= '<button class="btn btn-default dropdown-toggle" type="button" id="dismissalDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $str .= __('admin.TITLE_SELECT_DISMISSAL_TEXT');
                    $str .= '</button>';
                    $str .= '<div class="dropdown-menu p-3" aria-labelledby="dismissalDropdown" style="width: 300px;">';

                    // foreach($dismissal_result as $keyd=>$d_value)
                    // {
                    //     //dd($PatientsHasDismissalModel);
                    //     $checked = '';
                    //     $css = 'hide_me';
                    //     if (in_array($d_value->id, $PatientsHasDismissalModel))
                    //     {
                    //         $checked = 'checked=checked';
                    //     }
                    //     $str .= '<div class="col-md-3" lang="'.$value->a_id.'" ><input type="checkbox" onClick="storeDismissal('.$d_value->id.','.$value->p_id.','.$value->a_id.')" class="addDismissal" name="dismissal[]" id="chk_'.$d_value->id.'_'.$value->p_id.'_'.$value->a_id.'" value="'.$d_value->id.'" '.$checked.'>&nbsp;&nbsp;'.$d_value->name."</div>";
                    // } 
                    // $str .= '<div class="col-md-12"><input type="button" name="" value="'.__('admin.TITLE_DISMISSAL_TEXT').'" class="dismissalButton exam_btn" lang="'.$value->a_id.'"></div></div>';
                    foreach ($dismissal_result as $keyd => $d_value) {
                        $checked = '';
                        if (in_array($d_value->id, $PatientsHasDismissalModel)) {
                            $checked = 'checked="checked"';
                        }

                        $str .= '<label class="dropdown-item">';
                        $str .= '<input type="checkbox" onClick="storeDismissal(' . $d_value->id . ',' . $value->p_id . ',' . $value->a_id . ')" class="addDismissal" name="dismissal[]" id="chk_' . $d_value->id . '_' . $value->p_id . '_' . $value->a_id . '" value="' . $d_value->id . '" ' . $checked . '>';
                        $str .= '&nbsp;&nbsp;' . $d_value->name;
                        $str .= '</label>';
                    }

                    $str .= '</div>';
                    $str .= '</div>';

                    $str .= '<div class="col-md-12 mt-0">';
                    $str .= '<input type="button" name="" value="' . __('admin.TITLE_DISMISSAL_TEXT') . '" class="dismissalButton exam_btn btn" lang="' . $value->a_id . '">';
                    $str .= '</div> </div>';
                } else {
                    $str .= ' No Dismissal';
                }


                $str .= '
                                    </div>
                                    </div>
                                    </div></div>
                            <!-- /.card-body -->
                            </div>
                        </div>
                    </div>
                </div>';
            }
        }
        $str .= '</div>';
        //echo "<pre>";print_r($str);exit;
        return $str;
    }
 
    public function viewPopup(Request $request)
    {

        $appointment_id = $request->appoitment_id;
        $type = $request->type;

        $result = $this->BaseModel
            ->select('patients.family_name', 'patients.first_name', 'appointment.start_date', 'appointment.end_date', 'appointment_types.name', 'appointment.id as a_id')
            ->leftjoin('patients', 'patients.id', 'appointment.patient_id')
            ->leftjoin('users', 'users.id', 'appointment.doctor_id')

            ->leftjoin('appointment_types', 'appointment_types.id', 'appointment.appointment_type_id')
            ->where('appointment.id', $appointment_id)
            ->first();


        $data = '<p><strong>Patient:</strong> ' . $result->first_name . ' ' . $result->family_name . '</p><p><strong>Typ:</strong> ' . $result->name . '</p><p><strong>Beginn:</strong> ' . date('H:i', strtotime($result->start_date)) . ' - ' . date('H:i', strtotime($result->end_date)) . '<p></p><p><strong>Notizen: </strong>' . $result->notes . '</p>';

        if ($type == 'examination') {
            $data .= '<p><strong>Examination: </strong>' . $result->notes . '</p>';
        }
        if ($type == 'document') {
            $data .= '<p><strong>Document: </strong>' . $result->notes . '</p>';
        }
        if ($type == 'finding') {
            $data .= '<p><strong>Findings: </strong>' . $result->notes . '</p>';
        }
        return $data;
    }

    public function getDoctors(Request $request)
    {
        // log::info($request->all());
        $var = $request->get('keyword');
        //dd('getDoctors'); 

        $data       = [];
        $message    = __('admin.ERR_NOT_FOUND');

        try {
            $collection = collect([]);
            $collection = $this->AdminUserModel
                ->where('first_name', 'like', $var . '%')
                ->orWhere('last_name', 'like', $var . '%')
                ->whereStatus(1)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'doctor');
                })
                ->get(['id', 'email', 'first_name', 'last_name']);;
            // dd($collection);

            if ((!empty($collection) && sizeof($collection) > 0)) {
                $message = __('admin.DATA_FOUND_SUCCESS');
                //$data  = $collection;
                $data = '<select class="form-control" id ="getDoctorsData">'; //onchange="getDoctorsData()"
                foreach ($collection as $key => $value) {
                    // value="'.$value['id'].'"
                    $data .= '<option value="' . $value['id'] . '">' . $value['first_name'] . ' ' . $value['last_name'] . '</option>';
                }
                $data .= '</select>';
            } else {
                $message = __('admin.ERR_NOT_FOUND');
                $data = '<select class="form-control" id ="getDoctorsData">';
                $data .= '<option value="">' . $message . '</option>';
                $data .= '</select>';
            }
        } catch (\Exception $e) {
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

    public function _getAuthenticationForToken()
    {
        // dd('_getAuthenticationForToken');
        self::_accessTokenFile();
        // $rurl = secure_url('Admin\DashboardController@_getAuthenticationForToken');
        $rurl = action('Admin\DashboardController@_getAuthenticationForToken');
        // $rurl ='https://dev.eluminousdev.com/trtcle/admin/dashboard/calendar/oauth';
        //dd($rurl);
        $this->client->setRedirectUri($rurl);
        // dd($this->client->isAccessTokenExpired());
        // If there is no previous token or it's expired.
        if ($this->client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            } else
            if (!isset($_GET['code'])) {
                $authUrl = $this->client->createAuthUrl();
                $filtered_url = filter_var($authUrl, FILTER_SANITIZE_URL);
                return redirect($filtered_url);
            } else {
                // Exchange authorization code for an access token.
                $accessToken = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
                $this->client->setAccessToken($accessToken);
            }
            // Save the token to a file.
            if (!file_exists(dirname($this->tokenPath))) {
                mkdir(dirname($this->tokenPath), 0700, true);
            }

            file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
        } //close if

    }

    public function _accessTokenFile()
    {
        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }
        return $this->client;
    }

    public function fetchExaminationControl($patientAge, $patient_id, $appointment_id)
    {
        $examinations = $this->ExaminationsModel
            //  ->where('examinations.status','=',1) 
            ->where('examinations.show_as_control', '1')
            ->get([
                'examinations.id',
                'examinations.name',
                'examinations.show_as_control',
                'examinations.on_dashboard',
                'examinations.status'
            ]);
        // $examinations = $examinations->map(function($item) use($patient_id,$appointment_id)
        // {     
        //     $is_checked = $this->PatientsHasServiceControlReminderModel
        //                 ->where('appointment_id',$appointment_id)
        //                 ->where('patient_id',$patient_id)
        //                 ->where('service_id',$item->id)
        //                 ->first();

        //     $item->checked = (!empty($is_checked) == 0) ? 0 : 1;
        //     return $item;

        // });                
        return $examinations;
    }

    public function fetchExamination($patientAge, $patient_id, $appointment_id)
    {
        // $patientProfile = $this->ProfilesTemplatesModel
        //                 ->where('age_from', '<=' ,$patientAge)
        //                 ->where('age_to', '>=' ,$patientAge) 
        //                 ->whereStatus(1)
        //                 ->first();
        $examinations = $this->ExaminationsModel
            ->leftjoin('appointment_has_examinations', function ($index) use ($patient_id, $appointment_id) {
                $index->on('examination_id', 'examinations.id');
                $index->where('appointment_id', $appointment_id);
                $index->where('patient_id', $patient_id);
            })
            ->get([
                'examinations.id',
                'examinations.name',
                'examinations.show_as_control',
                'examinations.on_dashboard',
                'examinations.status',
                'appointment_has_examinations.id as exam_sort_id'
            ]);
        // if(!empty($examinations) && count($examinations) > 0)
        // {
        //     $examinations = $examinations->map(function($item) use($patient_id,$appointment_id)
        //     {
        //         $is_checked = $this->AppointmentHasExaminationsModel
        //                     ->where('appointment_id',$appointment_id)
        //                     ->where('patient_id',$patient_id)
        //                     ->where('examination_id',$item->id)
        //                     ->first();
        //         if(!empty($is_checked))            
        //         {
        //             // if(!empty($patientProfile))
        //             // {
        //             //     $is_recommended = $this->ExaminationsModel
        //             //                 ->leftjoin('profile_has_examinations','profile_has_examinations.examination_id',
        //             //                     '=','examinations.id')                
        //             //                 ->where('profile_id','=',$patientProfile->id) 
        //             //                 ->where('examinations.id','=',$item->id) 
        //             //                 ->orWhere('examinations.trigger_exam_flag','=',1) 
        //             //                 ->count();
        //             //     $item->recommented = ($is_recommended == 0) ? 0 : 1;
        //             // }else
        //             // {
        //             //     $item->recommented = 0;
        //             // }
        //             //$item->checked = ($is_checked == 0) ? 0 : 1;
        //             $item->exam_sort_id = $is_checked->id;
        //         }
        //          $item->checked = (!empty($is_checked) == 0) ? 0 : 1;
        //          return $item;
        //     });
        // }
        // // $examinations = $examinations->sortByDesc('recommented');
        // $examinations = $examinations->sortBy('exam_sort_id');
        return $examinations;
    }

    public function assignExamination(Request $request)
    {
        Log::info("in admin appcontroller assignExamination");
        Log::info($request->all());

        //dd('appointment_has_examinations');
        $appointment_id = $request->appoitment_id;
        $patient_id  = $request->patinet_id;
        $examination_id = $request->exam_id;

        $is_checked = $this->AppointmentHasExaminationsModel
            ->where('appointment_id', $appointment_id)
            ->where('patient_id', $patient_id)
            ->where('examination_id', $examination_id)
            ->first();
        if (!empty($is_checked)) {

            Log::info("in admin appcontroller assignExamination not empty is_checked examination_id");
            Log::info($examination_id);
            Log::info("in admin appcontroller assignExamination not empty is_checked patient_id");
            Log::info($patient_id);

            $this->AppointmentHasExaminationsModel->where('id', $is_checked->id)->delete();



            /****start New code for age reminder deactivate on 7-oct-25****/


               $ids = $this->PatientsHasServiceReminderModel
                                    ->where(['patient_id'=>$patient_id,
                                    'reminder_status'=>'Set',
                                    'status'=>'deactivate',
                                    'appointment_id'=>0,
                                    'service_id'=>$examination_id])
                                    ->get();

                Log::info("ids in assignExamination");
                Log::info($ids);                   
                $id_holder = [];
                if(!empty($ids))
                {
                    foreach($ids as $id=>$value_id)
                    {                    
                        $id_holder[] = $value_id->id;
                    }
                    
                }
                $reactivateReminder =  $this->PatientHasReminder
                                           ->whereIn('service_reminder_id',$id_holder)
                                           ->update(['status'=>'activate']);
                $this->PatientsHasServiceReminderModel->where(['patient_id'=>$patient_id,'reminder_status'=>'Set','status'=>'deactivate','appointment_id'=>0,'service_id'=>$examination_id])->update(['status'=>'activate']);
 
                $ageReminderAppId = $this->PatientsHasServiceReminderModel
                                ->where(['patient_id'=>$patient_id,
                                'reminder_status'=>'Set',
                                'status'=>'deactivate',
                                'service_id'=>$examination_id])
                                ->where('appointment_id','!=',$appointment_id)
                                ->where('type','=','age')
                                ->orderBy('id','desc')
                                ->first(['appointment_id']);

           

                if(isset($ageReminderAppId))
                {
                    $ageReminderIds = $this->PatientsHasServiceReminderModel
                                    ->where(['patient_id'=>$patient_id,
                                    'reminder_status'=>'Set',
                                    'status'=>'deactivate',
                                    'service_id'=>$examination_id])
                                    ->where('appointment_id','=',$ageReminderAppId->appointment_id)
                                    ->where('type','=','age')
                                    ->orderBy('id','desc')
                                    ->get();

                    Log::info("ageReminderIds");
                    Log::info($ageReminderIds);                 

                    if(!empty($ageReminderIds))
                    {
                            foreach($ageReminderIds as $id=>$value_id)
                            {                    

                                Log::info("value_id");
                                Log::info($value_id->id);   

                                Log::info("examination_id");
                                Log::info($examination_id);                 
     

                                $reactivateReminder =  $this->PatientHasReminder
                                                   ->where('service_reminder_id',$value_id->id)
                                                   ->update(['status'=>'activate']);

                                $this->PatientsHasServiceReminderModel
                                    ->where(['patient_id'=>$patient_id,'reminder_status'=>'Set','status'=>'deactivate','service_id'=>$examination_id])
                                    ->where('type','age')
                                    ->where('id', $value_id->id)->update(['status'=>'activate']);                   

                            }//foreach
                        
                    }//if
                }//if issset 

            /****end*****7-oct-25***********************************/

            




            $this->JsonData['status']   = __('admin.RESP_SUCCESS');
            $this->JsonData['msg']      = __('admin.APPOITMENT_EXAM_UPDATED');
        } else {

            Log::info("in admin appcontroller assignExamination else insert data exam_data");

            $exam_data[] = array(
                'patient_id' => $patient_id,
                'examination_id' => $examination_id,
                'appointment_id' => $appointment_id,
            );

            Log::info($exam_data);

            // Examination add dismissal table
            // $DismissalModel = new $this->PatientsHasDismissalModel;
            // $DismissalModel->fk_patient_id   = $patient_id;
            // $DismissalModel->fk_dismissal_id = $examination_id;
            // $DismissalModel->appointment_id  = $appointment_id;
            // $DismissalModel->type            = 'examinations';
            // $DismissalModel->status          = '0';
            // $DismissalModel->created_at      = date('Y-m-d');
            // $DismissalModel->save();
            // End examination add dismissal table
            if ($this->AppointmentHasExaminationsModel->insert($exam_data)) {

                Log::info("in admin appcontroller assignExamination else insert data exam_data inserted");
 

                $pdf = self::generateExaminationChecklistPDF($patient_id, $appointment_id, $examination_id);
                $pdfDoc = self::generateExaminationDocumentlistPDF($patient_id, $appointment_id, $examination_id);
                // if(sizeof($pdf)>0 &&)
                // {
                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['msg']      = __('admin.APPOITMENT_EXAM_UPDATED');
                //}


            } else {
                $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $postCalDetails->original['msg'];
            }
        }
        return response()->json($this->JsonData);
    }
    public function generateExaminationDocumentlistPDF($patient_id, $appointment_id, $exam_id)
    {
        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = '';
        //dd($patient_id,$appointment_id,$exam_id);
        $examinations_details = $this->ExaminationsHasMultipleDocumentListModel
            ->where('fk_examinations_id', $exam_id)
            ->get();
        $flag = 0;
        if (!empty($examinations_details)) {
            foreach ($examinations_details as $exam_key => $exam_val) {
                $collections = $this->SpecialistDocumentsModel->find($exam_val['fk_document_list_id']);
                $doc_status = '0';
                if ($collections->signDoc == 'read') {
                    $doc_status = '1';
                }
                if (!empty($collections)) {
                    if (!empty(Config('ordination_id'))) {
                        $getDatabaseName = DB::connection('system')
                            ->table("tenants")
                            ->where('ordination_id', Config('ordination_id'))
                            ->first(['uuid']);
                        $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/' . $getDatabaseName->uuid . '/document_pdf/';
                    } else {
                        $PdfPath = '/opt/app-shared/php/data/storage/app/public/document_pdf/';
                    }
                    $header_image_path = self::getFilePath($collections['header_image_path']);
                    $footer_image_path = self::getFilePath($collections['footer_image_path']);
                    //dd($header_image_path,$footer_image_path);
                    $data['doc_id']            = $collections['id'];
                    $data['name']              = $collections['name'];
                    $data['html_text']         = $collections['html_text'];
                    $data['background_color']  = $collections['background_color'];
                    $data['header_image']      = $collections['header_image'];
                    $data['header_image_path'] = $header_image_path;
                    $data['footer_image']      = $collections['footer_image'];
                    $data['footer_image_path'] = $footer_image_path;
                    $data['background_color']  = $collections['background_color'];
                    //$cnt++;
                    //$PdfPath   = self::StorePath('document_pdf/');
                    //$PdfPath   = storage_path().'/app/public/document_pdf/';
                    // $PDFname   = $collections['name'].'_'.time().'.pdf';
                    $PDFname = self::createPdfFileName($patient_id, $collections['name'], $collections['name']);
                    //dump($PDFname);
                    // $PDFname = str_replace(' ', '' , $collections['name']);
                    // $PDFname   = trim($PDFname).'_'.time().'.pdf';
                    // Invoice full path
                    $StorePath = $PdfPath . $PDFname;
                    $accessPath = '/document_pdf/' . $PDFname;
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
                    $PDFPath = 'admin.pdf.documentLists';
                    $pdf->loadView($PDFPath, compact('data'))->save($StorePath);
                    // end
                    // dump($PDFname);
                    // dump($accessPath);
                    //========================================================================
                    // pdf
                    $current_date = date('Y-m-d H:i:s');
                    $start_date   = null;
                    $end_date     = null;
                    $days = '';
                    if (!empty($collections->frequency_type)) {
                        switch ($collections->frequency_type) {
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
                    if (!empty($days)) {
                        $duration    = (int)$days;
                        $last_date   = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +" . $duration . " day");
                        $end_date    = Date('Y-m-d H:i:s', $last_date);
                        $start_date  = $current_date;
                    }
                    // ===========================================================
                    /* exam_id
                    |Check List Selected questions exam_arr
                    */
                    $CheckListHasSelectedQuestionModel = $this->PatientHasDocumentsModel
                        ->where('patient_id', $patient_id)
                        ->where('appointment_id', $appointment_id)
                        ->where('type', $collections['type_of_document'])
                        ->where('fk_document_id', $collections['id'])
                        ->first();
                    if (!empty($CheckListHasSelectedQuestionModel)) {
                        $doc_flag = 1;
                    } else {
                        $CheckListHasSelectedQuestionModel = new $this->PatientHasDocumentsModel;
                        $doc_flag = 1;
                    }
                    if ($doc_flag == 1) {
                        // if(!empty($app_id))
                        // {
                        //     $CheckListHasSelectedQuestionModel->appointment_id     = $app_id;
                        // }
                        $CheckListHasSelectedQuestionModel->patient_id             = $patient_id;
                        //$CheckListHasSelectedQuestionModel->exam_app_type_id       = $appointment_id; appointment_id
                        $CheckListHasSelectedQuestionModel->appointment_id     = $appointment_id;
                        $CheckListHasSelectedQuestionModel->fk_examinations_id     = $exam_id;
                        $CheckListHasSelectedQuestionModel->fk_document_id         = $collections['id'];

                        $CheckListHasSelectedQuestionModel->doc_status             = $doc_status;
                        $CheckListHasSelectedQuestionModel->pdf_name               = $PDFname;
                        $CheckListHasSelectedQuestionModel->pdf_path               = $accessPath;
                        $CheckListHasSelectedQuestionModel->type                   = $collections['type_of_document'];
                        //$CheckListHasSelectedQuestionModel->signature              = $file_name;
                        $CheckListHasSelectedQuestionModel->created_at             = Date('Y-m-d');
                        $CheckListHasSelectedQuestionModel->activation_start_date  = $start_date;
                        $CheckListHasSelectedQuestionModel->activation_last_date   = $end_date;
                        $CheckListHasSelectedQuestionModel->save();
                    }
                    $dataFinal[] = $data;
                    $data = [];
                    // ===========================================================
                    //$cnt++; inputdata
                }
            }
        }
        return $data;
    }

    public function generateExaminationChecklistPDF($patient_id, $appointment_id, $exam_id)
    {

        Log::info('In admin appointment controller generateExaminationChecklistPDF function 26-dec-22');

        $data = $dataFinal = [];

        $cnt = 0;
        $flag = '0';
        $file_name = '';
        //dd($patient_id,$appointment_id,$exam_id);
        $examinations_details = $this->ExaminationsHasMultipleCheckListModel
            ->where('fk_examinations_id', $exam_id)
            ->get();


        /*******Added by divya on 26-dec-22*********/
        $imagepath = '';
        $getDatabase = DB::connection('system')->table("tenants")
            ->where('ordination_id', Config('ordination_id'))->first(['uuid']);
        $imagepath = url('storage/tenancy/tenants/' . $getDatabase->uuid);

        /*******Added by divya on 26-dec-22*********/


        $flag = 0;
        if (!empty($examinations_details)) {
            foreach ($examinations_details as $exam_key => $exam_val) {
                $CheckListHasSelectedQuestionModel = $this->CheckListHasSelectedQuestionModel
                    ->where('fk_patient_id', $patient_id)
                    ->where('fk_appointment_id', $appointment_id)
                    ->where('fk_examination_id', $exam_id)
                    ->first();

                $check_list_status = 0;

                if (!empty($CheckListHasSelectedQuestionModel)) {
                    $cnttt = 0;
                    $cnt   = 0;
                    $flag  = 1;
                    $chk_id = 0;
                    // $myStatus = explode(',', $CheckListHasSelectedQuestionModel->status);
                    // if (!in_array('2', $myStatus))
                    // {
                    //     $status = $CheckListHasSelectedQuestionModel->status.',2';
                    //     $re_status  = str_replace("0,", "", $status);
                    //     $check_list_status = ltrim($re_status,',');

                    // }
                    // else $check_list_status = $CheckListHasSelectedQuestionModel->status;
                    $check_list_status = $CheckListHasSelectedQuestionModel->status;
                    $chk_id = $CheckListHasSelectedQuestionModel->fk_check_list_id;
                    if ($CheckListHasSelectedQuestionModel) {
                        $check_list        = json_decode($CheckListHasSelectedQuestionModel['questions'], true);
                        if ($check_list) {
                            foreach ($check_list as $ck => $cval) {
                                $getcheckList = $this->CheckListModel
                                    ->find($cval['checklist_id']);

                                $chk_id = $cval['checklist_id'];

                                if ($cval['signature'] != null) {
                                    $data[$cnt]['signature'] = $cval['signature'];
                                } else {
                                    $data[$cnt]['signature']         = '';
                                    $flag = '0';
                                }

                                $data[$cnt]['checklist_id']      = $cval['checklist_id'];
                                $data[$cnt]['check_list_name']   = $cval['check_list_name'];
                                $data[$cnt]['introduction_text'] = $cval['introduction_text'];
                                $data[$cnt]['final_name']        = $cval['final_name'];
                                //$data[$cnt]['signDoc']           = $getcheckList->signDoc;

                                //Added on 30-nov-22
                                $data[$cnt]['currentDate']        = date("d-m-Y");
                                $patientFirstName = $patientLastName = "";
                                $data[$cnt]['patientFullName'] = $data[$cnt]['patientDob'] = '';
                                $getPatientDetails = $this->PatientsModel->where('id', $patient_id)->first();
                                if (isset($getPatientDetails) && !empty($getPatientDetails)) {
                                    $patientFirstName = isset($getPatientDetails->first_name) ? $getPatientDetails->first_name : '';
                                    $patientLastName = isset($getPatientDetails->family_name) ? $getPatientDetails->family_name : '';
                                    $data[$cnt]['patientFullName'] = $patientFirstName . ' ' . $patientLastName;
                                    $data[$cnt]['patientDob'] = isset($getPatientDetails->birth_date) ? date("d-m-Y", strtotime($getPatientDetails->birth_date)) : '';
                                }
                                //Added on 30-nov-22

                                /*******Added by divya on 26-dec-22*********/
                                $data[$cnt]['header_image']        = isset($cval['header_image']) ? $cval['header_image'] : "";
                                $data[$cnt]['header_image_path']   = isset($cval['header_image_path']) ? $cval['header_image_path'] : "";
                                $data[$cnt]['footer_image']        = isset($cval['footer_image']) ? $cval['footer_image'] : "";
                                $data[$cnt]['footer_image_path']   = isset($cval['footer_image_path']) ? $cval['footer_image_path'] : "";
                                Log::info($data);
                                /*******Added by divya on 26-dec-22*********/



                                $j = 0;
                                foreach ($cval['heading'] as $heading) {
                                    //dd($heading['question']);
                                    //check list heading
                                    $data[$cnt]['heading'][$j]['fk_chk_id'] = $heading['fk_chk_id'];
                                    $data[$cnt]['heading'][$j]['heading_id'] = $heading['heading_id'];
                                    $data[$cnt]['heading'][$j]['heading']  = $heading['heading'];

                                    $k = 0;
                                    foreach ($heading['question'] as $key => $value) {
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
                    } else {
                        $data_final = [];
                    }
                    $cnt++;
                    $collections = $this->CheckListModel
                        ->where('id', $chk_id)
                        ->where('status', 1)
                        ->first();
                } else {

                    /************ Added on 26-dec-22**********/
                    $imagepath = '';
                    $getDatabase = DB::connection('system')->table("tenants")
                        ->where('ordination_id', Config('ordination_id'))->first(['uuid']);
                    $imagepath = url('storage/tenancy/tenants/' . $getDatabase->uuid);
                    /************ Added on 26-dec-22**********/



                    $flag = 1;
                    $check_list_status = '0';
                    $CheckListHasSelectedQuestionModel = new $this->CheckListHasSelectedQuestionModel;
                    $collections = $this->CheckListModel
                        ->where('id', $exam_val['fk_check_list_id'])
                        ->where('status', 1)
                        ->first();
                    $chk_id = $collections->id;
                    if ($collections->signDoc == 'read') {
                        $check_list_status = '0';
                    }

                    if (!empty($collections)) {
                        $data[$cnt]['signature']         = '';
                        $data[$cnt]['checklist_id']      = $collections->id;
                        $data[$cnt]['check_list_name']   = $collections->check_list_name;
                        $data[$cnt]['introduction_text'] = $collections->introduction_text;
                        $data[$cnt]['final_name']        = $collections->final_name;
                        $data[$cnt]['fk_exam_id']        = $exam_id;

                        /*******Added by divya on 26-dec-22*********/
                        $data[$cnt]['header_image']        = isset($collections->header_image) ? $collections->header_image : "";
                        $data[$cnt]['header_image_path']   = isset($collections->header_image_path) ? $collections->header_image_path : "";
                        $data[$cnt]['footer_image']        = isset($collections->footer_image) ? $collections->footer_image : "";
                        $data[$cnt]['footer_image_path']   = isset($collections->footer_image_path) ? $collections->footer_image_path : "";

                        /*******Added by divya on 26-dec-22*********/


                        //Added on 30-nov-22
                        $data[$cnt]['currentDate']        = date("d-m-Y");
                        $patientFirstName = $patientLastName = "";
                        $data[$cnt]['patientFullName'] = $data[$cnt]['patientDob'] = '';
                        $getPatientDetails = $this->PatientsModel->where('id', $patient_id)->first();
                        if (isset($getPatientDetails) && !empty($getPatientDetails)) {
                            $patientFirstName = isset($getPatientDetails->first_name) ? $getPatientDetails->first_name : '';
                            $patientLastName = isset($getPatientDetails->family_name) ? $getPatientDetails->family_name : '';
                            $data[$cnt]['patientFullName'] = $patientFirstName . ' ' . $patientLastName;
                            $data[$cnt]['patientDob'] = isset($getPatientDetails->birth_date) ? date("d-m-Y", strtotime($getPatientDetails->birth_date)) : '';
                        }
                        //Added on 30-nov-22




                        $j = 0;
                        $heading = $this->CheckListHasHeadingSectionModel
                            ->where('fk_check_list_id', $collections->id)->get();
                        foreach ($heading as $heading) {
                            //check list heading
                            $data[$cnt]['heading'][$j]['fk_chk_id'] = $collections->id;
                            $data[$cnt]['heading'][$j]['heading_id'] = $heading['id'];
                            $data[$cnt]['heading'][$j]['heading']   = $heading['heading_section'];

                            //check list question
                            $k = 0;
                            $question = $this->HeadingSectionHasQuestionModel
                                ->where('fk_check_list_heading_section_id', $heading['id'])
                                ->get();
                            foreach ($question as $keyv => $valque) {

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
                if (!empty(Config('ordination_id'))) {
                    $getDatabaseName = DB::connection('system')
                        ->table("tenants")
                        ->where('ordination_id', Config('ordination_id'))
                        ->first(['uuid']);

                    $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/' . $getDatabaseName->uuid . '/check_list_pdf/';
                } else {
                    $PdfPath = '/opt/app-shared/php/data/storage/app/public/check_list_pdf/';
                }
                //$PdfPath   = storage_path().'/app/public/check_list_pdf/';
                //$PDFname   = $collections->check_list_name.'_'.time().'.pdf';
                // $PDFname = str_replace(' ', '' , $collections->check_list_name);
                // $PDFname   = trim($PDFname).'_'.time().'.pdf';
                //log::info($collections);
                $PDFname = self::createPdfFileName($patient_id, $collections->check_list_name);

                // Invoice full path
                $StorePath = $PdfPath . $PDFname;
                $accessPath = '/check_list_pdf/' . $PDFname;

                //log::info($data);

                $PDFPath = 'admin.pdf.checkLists';
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
                $pdf->loadView($PDFPath, compact('data'))->save($StorePath);
                /*
                |Check List Selected questions
                */

                $CheckListHasSelectedQuestionModel->fk_patient_id    = $patient_id;
                $CheckListHasSelectedQuestionModel->fk_examination_id = $exam_id;
                $CheckListHasSelectedQuestionModel->fk_appointment_id = $appointment_id;
                $CheckListHasSelectedQuestionModel->fk_check_list_id = $chk_id;
                $CheckListHasSelectedQuestionModel->questions        = json_encode($data);
                $CheckListHasSelectedQuestionModel->created_at       = Date('Y-m-d H:i:s');
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


        return $data;
    }

    // public function fetchDocuments($appointment_id)
    // {        
    //     $appointment_type_docs = $this->AppointmentTypesModel->with('hasPatientDocuments')
    //                 ->join('appointment','appointment.appointment_type_id','=','appointment_types.id')                   
    //                 ->where('appointment.id','=',$appointment_id)
    //                 ->whereNotNull('appointment_types.patient_document_path') 
    //                 ->get([
    //                 'appointment_types.id',
    //                 'appointment_types.patient_document as document_name',
    //                 'appointment_types.patient_document_path as document_path',
    //                 ]);

    //     $appointment_exams_docs = $this->ExaminationsModel->with('hasPatientDocuments')
    //                 ->join('appointment_has_examinations','appointment_has_examinations.examination_id','=','examinations.id')
    //                 ->where('appointment_has_examinations.appointment_id','=',$appointment_id)
    //                 ->whereNotNull('examinations.document_path')
    //                 ->get([
    //                     'examinations.id',
    //                     'examinations.document_name',
    //                     ]); 

    //     if((!empty($appointment_type_docs) && sizeof($appointment_type_docs) > 0))
    //     {
    //         $appointment_type_docs = $appointment_type_docs->map(function($item) use($appointment_id)
    //         {
    //             $item->doc_status = 0;

    //             if(!empty($item->hasPatientDocuments) && sizeof($item->hasPatientDocuments)>0){
    //                 foreach ($item->hasPatientDocuments as $hasPatientDocument) {
    //                     if($hasPatientDocument->appointment_id==$appointment_id){
    //                         $item->doc_status = $hasPatientDocument->doc_status;
    //                         $item->doc_id = $hasPatientDocument->id;
    //                         break;
    //                     }
    //                 }
    //             }
    //             return $item;
    //         });
    //     }
    //     if((!empty($appointment_exams_docs) && sizeof($appointment_exams_docs) > 0))
    //     {
    //         $appointment_exams_docs = $appointment_exams_docs->map(function($item) use($appointment_id)
    //         {
    //             $item->doc_status = 0;
    //             if(!empty($item->hasPatientDocuments) && sizeof($item->hasPatientDocuments)>0){
    //                // $item->doc_status = $item->hasPatientDocuments[0]->doc_status;
    //                 foreach ($item->hasPatientDocuments as $hasPatientDocument) {
    //                     if($hasPatientDocument->appointment_id==$appointment_id){
    //                         $item->doc_status = $hasPatientDocument->doc_status;
    //                         $item->doc_id = $hasPatientDocument->id;
    //                         break;
    //                     }
    //                 }
    //             }


    //             return $item;
    //         });

    //     }


    //     return $collections = $appointment_type_docs->merge($appointment_exams_docs);
    //     //dd($collections);
    // }
    public function fetchGeneralDocuments($p_id, $a_id)
    {
        //dump($p_id,$a_id);
        $doc_list_status = $this->PatientHasDocumentsModel
            ->select(
                'patient_has_documents.doc_status as doclist_status',
                'patient_has_documents.pdf_path as document_path',
                'specialist_has_documents.id as doc_id',
                'patient_has_documents.fk_examinations_id',
                'specialist_has_documents.name as document_name',
                'specialist_has_documents.signDoc',
                'patient_has_documents.export_status',
            )
            ->leftjoin('specialist_has_documents', 'specialist_has_documents.id', 'patient_has_documents.fk_document_id')
            ->where('patient_has_documents.patient_id', $p_id)
            ->where('specialist_has_documents.type_of_document', 'general')
            ->where('patient_has_documents.appointment_id', $a_id)
            ->whereNotNull('patient_has_documents.fk_document_id')
            ->get();

        ///dump($doc_list_status);
        return $doc_list_status;
    }

    public function fetchDocuments($exam_ids, $a_id, $p_id)
    {
        $documentList = $this->ExaminationsHasMultipleDocumentListModel
            ->select('examinations_has_multiple_document_list.fk_examinations_id', 'specialist_has_documents.name as document_name', 'specialist_has_documents.id as doc_id', 'specialist_has_documents.signDoc')
            ->leftjoin('specialist_has_documents', 'specialist_has_documents.id', 'examinations_has_multiple_document_list.fk_document_list_id')
            ->whereIn('fk_examinations_id', $exam_ids)
            ->where('specialist_has_documents.type_of_document', 'service')
            ->get();
        $documentList = $documentList->map(function ($item) use ($a_id, $p_id) {
            $exam_doc_pdf = $this->ExaminationsModel
                ->with('hasMultipleDcoQR')
                ->where('show_as_control', '1')
                ->find($item->fk_examinations_id);
            //Added by Shyam 10-02-22
            $doc_list_check = $this->PatientHasDocumentsModel
                // ->where('fk_examinations_id',$item->fk_examinations_id)
                ->where('fk_examinations_id', $item->fk_examinations_id)
                ->where('appointment_id', $a_id)
                ->where('fk_document_id', $item->doc_id)
                ->where('patient_id', $p_id)
                ->whereNotNull('pdf_path')
                ->first();
            //Added by Shyam 10-02-22
            if (!empty($exam_doc_pdf)) {
                if (!empty($exam_doc_pdf->hasMultipleDcoQR) && sizeof($exam_doc_pdf->hasMultipleDcoQR) > 0) {
                    foreach ($exam_doc_pdf->hasMultipleDcoQR as $key => $value) {
                        if (empty($doc_list_check)) //if Condition Added by Shyam 10-02-22
                        {
                            self::generateExaminationDocumentlistPDF($p_id, $a_id, $item->fk_examinations_id);
                        }
                    }
                }
            }
            // $check_list_status = $this->ExaminationsModel->where('id',$item->fk_examinations_id)->pluck('check_list_status')->first();
            // if( $check_list_status)
            // $item->checklist_status = $check_list_status;

            // else
            //  $item->checklist_status = 0;
            // $check_list_status = $this->ExaminationsModel->where('id',$item->fk_examinations_id)->first();

            $doc_list_status = $this->PatientHasDocumentsModel
                // ->where('fk_examinations_id',$item->fk_examinations_id)
                ->where('fk_examinations_id', $item->fk_examinations_id)
                ->where('appointment_id', $a_id)
                ->where('fk_document_id', $item->doc_id)
                ->where('patient_id', $p_id)
                ->first();

            //    dump("doc_list_status==>");
        // dump($doc_list_status);


            $item->signDoc = $item->signDoc;
            //dump($doc_list_status);
            if (!empty($doc_list_status)) {
                $item->doclist_status = $doc_list_status->doc_status;
                $item->document_path  = $doc_list_status->pdf_path;
                $item->doc_id         = $item->doc_id;
                $item->export_status  = $doc_list_status->export_status;
            } else {
                $item->doclist_status  = 0;
                $item->document_path   = null;
                $item->doc_id          = $item->doc_id;
                $item->export_status   = '0';
            }
            return $item;
        });
        //dump("documentList==>");
        // dump($documentList);
        return $documentList;
    }
    public function getServicesByEdit($appointment_type_id, $patient_id, $appointment_id)
    {
        Log::info('in getServicesByEdit function');

        $str = '';
        $collections1 = $this->AppointmentTypeHasExaminationsModel
            ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
            ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
            //->whereNotNull('examinations.description')
            //->where('examinations.show_as_recommended','1')
            ->get([
                'examinations.id',
                'examinations.name',
                'examinations.url',
                'examinations.description',
                'examinations.document_name',
                'examinations.document_path',
                'examinations.document_status',
                'examinations.status',
                'examinations.created_at',
                'examinations.show_as_recommended'
            ]);

        Log::info($collections1);

        $collections1 = $collections1->filter(function ($item) use ($patient_id) {
            $age_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'age')
                ->first();

            if (!empty($age_service)) {
                Log::info('in age service...');

                $getPatientAge = $this->PatientsModel
                    ->find($patient_id);

                if (!empty($getPatientAge)) {
                    $patient_age = $getPatientAge->age;

                    if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                        Log::info('in age criteria...');
                        return $item;
                    }
                }
            } else {
                return $item;
            }
        });

        $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));

        Log::info($exams_ids);

        $collections2 = $this->PatientsHasServiceReminderModel
            ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
            ->where('patient_has_service_reminder.patient_id', $patient_id)
            ->where('patient_has_service_reminder.type', 'age')
            ->where('patient_has_service_reminder.status', 'activate')
            ->whereNotIn('examinations.id', $exams_ids)
            ->where('patient_has_service_reminder.reminder_status', 'Set')
            ->groupBy('patient_has_service_reminder.service_id')
            ->get([
                'examinations.id',
                'examinations.name',
                'examinations.url',
                'examinations.description',
                'examinations.document_name',
                'examinations.document_path',
                'examinations.document_status',
                'examinations.status',
                'examinations.created_at',
                'examinations.show_as_recommended'
            ]);


        Log::info("collections2...");
        Log::info($collections2);

        $getRecord = $collections1->merge($collections2);

        Log::info("getRecord...");
        Log::info($getRecord);

        if ($appointment_id == 'undefined') {
            $appointment_id = '';
        }
        if (!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id)) {
            $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {
                Log::info("in getRecord...");
                $app_type_name = $this->AppointmentHasExaminationsModel
                    ->where('appointment_id', $appointment_id)
                    ->where('patient_id', $patient_id)
                    ->where('examination_id', $item->id)
                    ->with(['assignedExamination'])
                    //->where('examination_id',$exam_id)
                    ->first();

                if (!empty($app_type_name)) {
                    $item->checked = 1;
                    return $item;
                }
                if (empty($item->description)) {
                    $item->checked = 1;
                    return $item;
                }
                // When Discription is blank

                // =========================
                return $item;
            });
        } else {
            Log::info("else  getRecord...");
            $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {
                $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                if (!empty($app_type_name)) {
                    if ($item->name == $app_type_name->name) {
                        $item->checked = 1;
                    } else if (empty($item->description)) {
                        $item->checked = 1;
                    }

                    return $item;
                }
            });
        }


        Log::info("after getRecord...");
        Log::info($getRecord);

        if (!empty($getRecord) && sizeof($getRecord) > 0) {
            $str .= "<label class='theme-blue'> 
                    " . __('admin.TITLE_APPOINTMENT_SERVICES') . "</label>";
            foreach ($getRecord as $key => $value) {
                $checked = '';
                if ($value['checked'] == 1) {
                    $checked = 'checked';
                };
                $str .= "<div class='form-check'> 
                                 <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]'
                                name='status' value=" . $value->id . " 
                                >
                                <label class='form-check-label' for='status'>" . $value->name . "</label>
                                </div>";
            };
        }

        return $str;
    } //

    //Added below function on 180dec-23
    public function getServicesByEdit1($appointment_type_id, $patient_id, $appointment_id)
    {
        Log::info('in getServicesByEdit1 function');

        $str = '';
        $collections1 = $this->AppointmentTypeHasExaminationsModel
            ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
            ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
            //->whereNotNull('examinations.description')
            //->where('examinations.show_as_recommended','1')
            ->get([
                'examinations.id',
                'examinations.name',
                'examinations.url',
                'examinations.description',
                'examinations.document_name',
                'examinations.document_path',
                'examinations.document_status',
                'examinations.status',
                'examinations.created_at',
                'examinations.show_as_recommended'
            ]);

        $today_date = date("Y-m-d");    //added on 7-dec-23

        // $collections1 = $collections1->filter(function($item) use ($patient_id) 
        $collections1 = $collections1->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) {

            // $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id); //added on 7-dec-23 //commented on 13-apr-26

            $app_type_name = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id); //added on 7-dec-23 //changed on 13-apr-26

            $age_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'age')
                ->first();

            //added on 7-dec-23                   
            $general_reminder_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'general')
                ->first();

            Log::info("general_reminder_service===>.");
            Log::info($general_reminder_service);

            // if(!empty($age_service)) //commented on 7-dec-23
            if (!empty($age_service) && $item->name != $app_type_name->name) //added on 7-dec-23
            {
                Log::info(" if .....age_service===>.");
                $getPatientAge = $this->PatientsModel
                    ->find($patient_id);

                if (!empty($getPatientAge)) {
                    $patient_age = $getPatientAge->age;

                    Log::info(" .patient_age===>.");
                    Log::info($patient_age);

                    if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                        return $item;
                    }
                }
            } else if (!empty($general_reminder_service)) //else if condition added on 7-dec-23
            {
                $checkGenaralService =  $this->PatientsHasServiceReminderModel
                    ->where('service_id', $item->id)
                    ->where('patient_id', $patient_id)
                    ->where('reminder_status', 'Set')
                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                    ->first();
                if (empty($checkGenaralService)) return $item;
            } //Added this else by swati 2-nov-22
            else {
                return $item;
            }
        });

        $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));

        Log::info('in exams_ids');
        Log::info($exams_ids);

        //commented on 7-dec-23
        /* $collections2 = $this->PatientsHasServiceReminderModel
                                ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                                ->where('patient_has_service_reminder.patient_id',$patient_id)
                                ->where('patient_has_service_reminder.type','age')
                                ->where('patient_has_service_reminder.status','activate')
                                ->whereNotIn('examinations.id',$exams_ids) 
                                ->where('patient_has_service_reminder.reminder_status','Set') 
                                ->groupBy('patient_has_service_reminder.service_id')  
                                ->get([
                                        'examinations.id',
                                        'examinations.name',
                                        'examinations.url',
                                        'examinations.description',
                                        'examinations.document_name',
                                        'examinations.document_path',
                                        'examinations.document_status',
                                        'examinations.status',
                                        'examinations.created_at',
                                        'examinations.show_as_recommended'
                                    ]);*/

        //added on 7-dec-23                           
        $collections2 = $this->PatientsHasServiceReminderModel
            ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status'))
            ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
            ->join(

                //commented on 9-apr-25
                /*DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                            FROM patient_has_service_reminder 
                                            WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                            and status='activate'
                                            and deleted_at is NULL GROUP BY service_id)
                                        patientremidners"),*/

                //start changed on 9-apr-25                        
                 DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                            FROM patient_has_service_reminder 
                                            WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                            and status='activate'
                                           AND (
                                            ( (deleted_at IS NULL AND cycle_no = 1 AND date(reminder_date) <= '" . $today_date . "' AND type!='age' ) 
                                               OR
                                               (  deleted_at IS NULL and cycle_no>=0 AND date(reminder_date) <= '" . $today_date . "' and type='age' 
                                               )
                                            )
                                            OR 
                                            ( (deleted_at IS NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' AND type!='age') 
                                               OR (deleted_at IS  NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' and type='age') 
                                            )
                                        )  GROUP BY service_id)
                                        patientremidners"),                          

                //end changed on 9-apr-25

                function ($join) {
                    $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                    $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                }
            )
            ->where('patient_has_service_reminder.patient_id', $patient_id)
            ->where('patient_has_service_reminder.status', 'activate')
            // ->where('examinations.status.status','activate')
            ->whereRaw("examinations.show_as_reminder='1'")
            ->whereNotIn('examinations.id', $exams_ids)
            //->whereRaw("date(reminder_date) <= '" . $today_date . "'")//commented on 9-apr-25
            ->groupBy('patient_has_service_reminder.service_id')
            ->get();

        Log::info('collections2');
        Log::info($collections2);    

        //added on 7-dec-23               
        $collections2 = $collections2->filter(function ($item) use ($patient_id, $today_date) {
            $age_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'age')
                ->first();
            if (!empty($age_service)) {
                //log::info($patient_id);
                $getPatientAge = $this->PatientsModel->find($patient_id);
                if (!empty($getPatientAge)) {
                    $patient_age = $getPatientAge->age;
                    // log::info($age_service->age_from."<=".$patient_age."&&".$age_service->age_to.">=". $patient_age);
                    if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                        // log::info($item);
                        if ($item->reminder_status == 'executed') {
                            $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                ->where('service_id', $item->id)
                                ->where('patient_id', $patient_id)
                                ->where('reminder_status', 'Set')
                                ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                ->first();
                            //echo "<pre>";print_r($checkServiceReminders);
                            if (empty($checkServiceReminders))
                                return $item;
                        } else return $item;
                    }
                }
            }
            // else {
            //     if($item->reminder_status=='executed'){
            //         $checkServiceReminders =  $this->PatientsHasServiceReminderModel
            //                         ->where('service_id',$item->id)
            //                         ->where('patient_id',$patient_id)
            //                         ->where('reminder_status','Set')
            //                         ->whereRaw("date(reminder_date) >= '".$today_date."'") 
            //                         ->first();
            //         //echo "<pre>";print_r($checkServiceReminders);
            //         if(empty($checkServiceReminders))
            //             return $item;
            //     } 
            //     else return $item;
            // }

            //Added by swati 2-nov-22=========================
            $general_reminder_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'general')
                ->first();

            if (!empty($general_reminder_service)) {

                $today_date = date("Y-m-d");
                $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                    ->where('service_id', $item->id)
                    ->where('patient_id', $patient_id)
                    ->where('reminder_status', 'Set')
                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                    ->first();
                if (empty($checkServiceReminders))
                    return $item;
            }
            //Add checkup remidners as recommandation 4-sep-23=========================
            $checkup_reminder_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'checkup')
                ->first();

            if (!empty($checkup_reminder_service)) {

                $today_date = date("Y-m-d");
                $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                    ->where('service_id', $item->id)
                    ->where('patient_id', $patient_id)
                    ->where('reminder_status', 'Set')
                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                    ->first();
                if (empty($checkServiceReminders))
                    return $item;
            }
            //================================================
        });

        Log::info('collections2 again');
        Log::info($collections2);  

        $getRecord = $collections1->merge($collections2);
        if ($appointment_id == 'undefined') {
            $appointment_id = '';
        } //

        Log::info('getRecord');
        Log::info($getRecord);  

        if (!empty($appointment_id)) {
            $getAppointmentServcies = $this->AppointmentHasExaminationsModel
                ->where('appointment_id', $appointment_id)
                ->where('patient_id', $patient_id)
                ->get();
            if (!empty($getAppointmentServcies)) {
                foreach ($getAppointmentServcies as $key => $value) {
                    $appointment_exam[] = $value->examination_id;
                }
                $collections3 = $this->PatientsHasServiceReminderModel
                    ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status'))
                    ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                    ->join(
                        DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                                FROM patient_has_service_reminder 
                                                WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                                and status='activate'
                                                and deleted_at is NULL GROUP BY service_id)
                                            patientremidners"),
                        function ($join) {
                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                        }
                    )
                    ->where('patient_has_service_reminder.patient_id', $patient_id)
                    ->where('patient_has_service_reminder.status', 'activate')
                    ->whereIn('examinations.id', $appointment_exam)
                    ->whereRaw("date(reminder_date) <= '" . $today_date . "'")
                    ->groupBy('patient_has_service_reminder.service_id')
                    ->get();


                Log::info('collections3');
                Log::info($collections3);      

                $collections3 = $collections3->filter(function ($item) use ($patient_id, $today_date) {
                    $age_service =  $this->ChannelsRemindersSettingModel
                        ->where('service_id', $item->id)
                        ->where('activated_reminder', 'age')
                        ->first();
                    if (!empty($age_service)) {
                        $getPatientAge = $this->PatientsModel->find($patient_id);
                        if (!empty($getPatientAge)) {
                            $patient_age = $getPatientAge->age;
                            if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                if ($item->reminder_status == 'executed') {
                                    $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                        ->where('service_id', $item->id)
                                        ->where('patient_id', $patient_id)
                                        ->where('reminder_status', 'Set')
                                        ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                        ->first();
                                    //echo "<pre>";print_r($checkServiceReminders);
                                    if (empty($checkServiceReminders))
                                        return $item;
                                } else return $item;
                            }
                        }
                    }
                    $general_reminder_service =  $this->ChannelsRemindersSettingModel
                        ->where('service_id', $item->id)
                        ->where('activated_reminder', 'general')
                        ->first();
                    if (!empty($general_reminder_service)) {

                        $today_date = date("Y-m-d");
                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                            ->where('service_id', $item->id)
                            ->where('patient_id', $patient_id)
                            ->where('reminder_status', 'Set')
                            ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                            ->first();
                        if (empty($checkServiceReminders))
                            return $item;
                    }
                    //Add checkup remidners as recommandation 4-sep-23=========================
                    $checkup_reminder_service =  $this->ChannelsRemindersSettingModel
                        ->where('service_id', $item->id)
                        ->where('activated_reminder', 'checkup')
                        ->first();

                    if (!empty($checkup_reminder_service)) {

                        $today_date = date("Y-m-d");
                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                            ->where('service_id', $item->id)
                            ->where('patient_id', $patient_id)
                            ->where('reminder_status', 'Set')
                            ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                            ->first();
                        if (empty($checkServiceReminders))
                            return $item;
                    }
                    //================================================

                });
            }

            Log::info('collections3 again');
            Log::info($collections3); 

            $duplicateRecord = $getRecord->merge($collections3);
            $getRecord = $duplicateRecord->unique();
        }
        // log::info($getRecord);


        Log::info('getRecord again');
        Log::info($getRecord); 


        if (!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id)) {
            $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {

                $app_type_name = $this->AppointmentHasExaminationsModel
                    ->where('appointment_id', $appointment_id)
                    ->where('patient_id', $patient_id)
                    ->where('examination_id', $item->id)
                    ->with(['assignedExamination'])
                    //->where('examination_id',$exam_id)
                    ->first();

                if (!empty($app_type_name)) {
                    $item->checked = 1;
                    return $item;
                }
                if (empty($item->description)) {
                    $item->checked = 1;
                    return $item;
                }
                // When Discription is blank

                // =========================
                return $item;
            });
        } else {
            $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {
                // $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);//commneted on 13-apr-26

                $app_type_name = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id); //changed on 13-apr-26

                if (!empty($app_type_name)) {
                    if ($item->name == $app_type_name->name) {
                        $item->checked = 1;
                    } else if (empty($item->description)) {
                        $item->checked = 1;
                    }

                    return $item;
                }
            });
        }

        if (!empty($getRecord) && sizeof($getRecord) > 0) {
            $str .= "<label class='theme-blue'> 
                    " . __('admin.TITLE_APPOINTMENT_SERVICES') . "</label>";
            foreach ($getRecord as $key => $value) {
                $checked = '';
                if ($value['checked'] == 1) {
                    $checked = 'checked';
                };
                $str .= "<div class='form-check'> 
                             <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]'
                            name='status' value=" . $value->id . " 
                            >
                            <label class='form-check-label' for='status'>" . $value->name . "</label>
                            </div>";
            };
        }

        return $str;
    } //getServicesByEdit1

    public function fetchType($google_code_id)
    {
        $appointment_data = $this->BaseModel->where('google_event_id', $google_code_id)->first();
        $appointment_type = $this->AppointmentTypesModel->withTrashed()->get();//uncommented line for #415 issue roshani
        //dd($appointment_type);
        //code for Get blacklist appointment types
            $doctor_id = $appointment_data->doctor_id;
            // dump($doctor_id);
            if(!empty($doctor_id)){
                $discardIdsfromAppType = $this->UserHasAppointmentType->where('user_id', $appointment_data->doctor_id)->where('deleted_at', null)->pluck('appointment_type_id')->toArray();
                $filteredTypeIds = collect($discardIdsfromAppType)->diff([$appointment_data->appointment_type_id])->values()->all();
                if(!empty($discardIdsfromAppType)){
                    $appointment_type = $this->AppointmentTypesModel->whereNotIn('id', $filteredTypeIds)->withTrashed()->get(); 
                }
            }
            else
            {
                $appointment_type = $this->AppointmentTypesModel->get();
            }
        //End code for blacklist appointment types       

        //commented below line on 18-dec-23
        // $getservices = self::getServicesByEdit($appointment_data->appointment_type_id,$appointment_data->patient_id,$appointment_data->id);

        //added below line on 18-dec-23
        $getservices = self::getServicesByEdit1($appointment_data->appointment_type_id, $appointment_data->patient_id, $appointment_data->id);

        $select_data = '<select 
        name="appointment_type_id" 
        id="appointment_type_id"  
        required
        onChange="GetServices(this.value,' . $appointment_data->patient_id . ',' . $appointment_data->id . ')"
        data-error="' . __('admin.ERR_APPOINTMENT_TYPE_REQUIRED') . '"
        class="form-control select2"
        >';
        // <option value="">'.__('admin.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE').'</option>';
        foreach ($appointment_type as $appointment_types) {
            $sel = '';
            if ($appointment_types->id == $appointment_data->appointment_type_id) {
                $sel = 'selected="selected"';
            }
            $select_data .= '<option value="' . $appointment_types->id . '" ' . $sel . '>' . $appointment_types->name . '(' . $appointment_types->duration . ')</option>';
        }
        $path = route('admin.typeupdate', [base64_encode(base64_encode($appointment_data->id))]);
        $select_data .= ' </select><br><div class="form-group appointment_type_services" id="appointment_type_services">' . $getservices . '</div>';
        $select_data .= '<div class="modal-footer">
        <button type="submit" class="btn btn-success" id="save_apptype_btn">' . __('admin.TITLE_SAVE_BUTTON') . '</button>
        </div><input type="hidden" name="update_url" id="update_url" value="' . $path . '">
        <input type="hidden" name="appointment_id" id="appointment_id" value="' . $appointment_data->id . '">';

        return $select_data;
    }
    public function updateType(Request $request)
    {

        Log::info("in admin appointment controller updateType function called in doc dash");
        Log::info($request->all());

        $appointment_id = $request->appointment_id;
        $appointment_type = $request->appointment_type_id;

        $getapp = $this->BaseModel->where('id', $request->appointment_id)->first();

        //commented below query on 31-jan-24
        /*$appHasExamination = $this->AppointmentHasExaminationsModel
                            ->where('appointment_id',$request->appointment_id)
                            ->delete();*/

        /*************added below code on 31-jan-24**(1-feb-24)age services should not delete********/
        $getAppointmentServcies = $this->AppointmentHasExaminationsModel
            ->where('appointment_id', $request->appointment_id)
            ->get();



        if (!empty($getAppointmentServcies)) {

           Log::info("in admin appointment controller updateType function called in doc dash getAppointmentServcies");
    
      
            foreach ($getAppointmentServcies as $key => $value) {
                $appointment_exam = $value->examination_id;

                $age_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $value->examination_id)
                    ->where('activated_reminder', 'age')
                    ->first();
                if (!empty($age_service)) {
                } else {

                   Log::info("in admin appointment controller updateType function called in doc dash getAppointmentServcies else part delete services");
                   Log::info("updateType appointment_id before delete => ".$request->appointment_id);
                   Log::info("updateType examination_id before delete=> ".$value->examination_id);


                    $appHasExamination = $this->AppointmentHasExaminationsModel
                        ->where('appointment_id', $request->appointment_id)
                        ->where('examination_id', $value->examination_id)
                        ->delete();
                }
            }
        } //if getAppointmentServcies                    

        /**************added above code on 31-jan-24**age services*should not delete**************/


        $this->BaseModel->where('id', $request->appointment_id)->update(['appointment_type_id' => $appointment_type]);

        Log::info("in admin appointment controller updateType function called in doc dash before exam store"); 

        $getServises = self::_appointmentTypesAgaintsServices($request->appointment_id, $request, $getapp->patient_id);

        $getDocument = self::_GetAssignedDocument($request->appointment_id, $request->appointment_type_id, $request->app_services, $getapp->patient_id);
        // END

        // log::info("updateType-_GetAssignedCheckList");
        $checklist = $this->CheckListHasSelectedQuestionModel
            ->whereNOTIN('fk_examination_id', $request->app_services)
            ->where(['fk_appointment_id' => $request->appointment_id, 'fk_patient_id' => $getapp->patient_id])
            ->delete();
        // log::info($checklist);          
        //insert the entry for patient has Checklist
        $getDocument = self::_GetAssignedCheckList($request->appointment_id, $request->app_services, $getapp->patient_id);

        $patient_id = $this->BaseModel->where(['id' => $request->appointment_id])->pluck('patient_id')->first();

        // $this->PatientHasDocumentsModel->where(['appointment_id'=>$appointment_id,'patient_id'=>$patient_id])->delete();

        $patient_doc_data[] = array(
            'appointment_id' => $appointment_id,
            'patient_id'    => $patient_id,
            'exam_app_type_id' => $appointment_type,
            'record_type'   => 1,
            'doc_status'   => 0,
        );

        if ($this->PatientHasDocumentsModel->insert($patient_doc_data)) {
            $all_transactions[] = 1;
        } else {
            $all_transactions[] = 0;
        }

        $this->JsonData['status']   = __('admin.RESP_SUCCESS');
        $this->JsonData['msg']      = __('admin.DOCTOR_APPOINTMENT_TYPE_UPDATED');
        return response()->json($this->JsonData);
    }

    public function updateDocumentStatus(Request $request)
    {
        //dd($request->all());
        $doc_id     = $request->doc_id;
        $doc_status = $request->doc_status;

        $chkStatus = $this->PatientHasDocumentsModel->where('id', $doc_id)->first();
        if (!empty($chkStatus)) {
            $myStatus = explode(',', $chkStatus->doc_status);
            if (!in_array($doc_status, $myStatus)) {
                $doc_status = $chkStatus->doc_status . ',' . $doc_status;
                $re_status  = str_replace("0,", "", $doc_status);
                $chkStatus->doc_status = $re_status;

                $chkStatus->save();
            }
        }


        $this->JsonData['status']   = __('admin.RESP_SUCCESS');
        $this->JsonData['msg']      = __('admin.DOCUMENT_STATUS_UPDATED');
        return response()->json($this->JsonData);
    }

    public function fetchGeneralChecklistDocuments($p_id, $a_id)
    {
        $checklist = $this->CheckListHasSelectedQuestionModel
            ->select(
                'examinations_check_list.id as chk_id',
                'check_list_has_selected_questions.status as checklist_status',
                'check_list_has_selected_questions.pdf_path as checklist_path',
                'examinations_check_list.check_list_name',
                'check_list_has_selected_questions.export_status',
                'examinations_check_list.signDoc'
            )
            ->leftjoin('examinations_check_list', 'examinations_check_list.id', 'check_list_has_selected_questions.fk_check_list_id')
            ->where('fk_patient_id', $p_id)
            ->where('fk_appointment_id', $a_id)
            ->where('check_list_has_selected_questions.export_status', '0')
            ->where('type', 'general')
            ->get();

        return $checklist;
    }



    public function fetchChecklistDocuments($exam_ids, $a_id, $p_id)
    {
        $checklist = $this->ExaminationsHasMultipleCheckListModel
            ->select('examinations_has_multiple_check_list.id', 'examinations_has_multiple_check_list.fk_examinations_id', 'examinations_check_list.check_list_name', 'examinations_check_list.id as chk_id', 'examinations_check_list.signDoc')
            ->join('examinations_check_list', 'examinations_check_list.id', 'examinations_has_multiple_check_list.fk_check_list_id')
            ->whereIn('fk_examinations_id', $exam_ids)
            ->get();

        $checklist = $checklist->map(function ($item) use ($a_id, $p_id) {

            $exam_check_pdf = $this->ExaminationsModel
                ->with('hasMultipleChecklistQR')
                ->where('show_as_control', '1')
                ->find($item->fk_examinations_id);

            if (!empty($exam_check_pdf)) {
                if (!empty($exam_check_pdf->hasMultipleChecklistQR) && sizeof($exam_check_pdf->hasMultipleChecklistQR) > 0) {
                    foreach ($exam_check_pdf->hasMultipleChecklistQR as $key => $value) {
                        //$pdf = self::generateExaminationChecklistPDF($p_id,$a_id,$item->fk_examinations_id); //commented on 21-dec-22
                    }
                }
            }
            $check_list_status = $this->CheckListHasSelectedQuestionModel
                ->where('fk_examination_id', $item->fk_examinations_id)
                ->where('fk_appointment_id', $a_id)
                ->where('fk_patient_id', $p_id)
                ->where('fk_check_list_id', $item->chk_id)
                ->where('type', 'performance')
                ->first();


            if (!empty($check_list_status)) {
                $item->checklist_status = $check_list_status->status;
                $item->checklist_path   = $check_list_status->pdf_path;
                $item->chk_id           = $item->chk_id;
                $item->export_status    = $check_list_status->export_status;
            } else {
                $item->checklist_status = 0;
                $item->checklist_path   = '';
                $item->chk_id           = $item->chk_id;
                $item->export_status    = 0;
            }
            return $item;
        });

        return $checklist;
    }

    public function updatePrintStatus(Request $request)
    {
        $exam_id = $request->exam_id;
        $a_id = $request->a_id;
        $p_id = $request->p_id;
        $id   = $request->id;
        $type = $request->type;

        if ($request->type == 'doc') {
            if (!empty($exam_id)) {
                $rec =  $this->PatientHasDocumentsModel
                    ->where('appointment_id', $a_id)
                    ->where('patient_id', $p_id)
                    ->where('fk_examinations_id', $exam_id)
                    ->where('fk_document_id', $id)
                    ->first();
            } else {
                $rec =  $this->PatientHasDocumentsModel
                    ->where('appointment_id', $a_id)
                    ->where('patient_id', $p_id)
                    ->where('fk_document_id', $id)
                    ->first();
            }

            if (!empty($rec)) {
                $myStatus = explode(',', $rec->doc_status);
                if (!in_array('3', $myStatus)) {
                    $doc_status = $rec->doc_status . ',3';
                    $re_status  = str_replace("0,", "", $doc_status);
                    $rec->doc_status = ltrim($re_status, ',');
                    $rec->save();
                }
            }
        } else if ($request->type == 'check_list') {
            if (!empty($exam_id)) {
                $rec = $this->CheckListHasSelectedQuestionModel
                    ->where('fk_appointment_id', $a_id)
                    ->where('fk_patient_id', $p_id)
                    ->where('fk_examination_id', $exam_id)
                    ->where('fk_check_list_id', $id)
                    ->first();
            } else {
                $rec = $this->CheckListHasSelectedQuestionModel
                    ->where('fk_appointment_id', $a_id)
                    ->where('fk_patient_id', $p_id)
                    ->where('fk_check_list_id', $id)
                    ->first();
            }

            if (!empty($rec)) {
                $myStatus = explode(',', $rec->status);
                if (!in_array('3', $myStatus)) {
                    $doc_status = $rec->status . ',3';
                    $re_status  = str_replace("0,", "", $doc_status);
                    $rec->status =  ltrim($re_status, ',');
                    $rec->save();
                }
            }
        }
        $this->JsonData['status']   = __('admin.RESP_SUCCESS');
        $this->JsonData['msg']      = __('admin.DOCUMENT_STATUS_UPDATED');
        return response()->json($this->JsonData);
    }


    public function updateChecklistStatus(Request $request)
    {
        //dd($request->all());
        $exam_id = $request->exam_id ?? '';
        $checklist_status = $request->checklist_status;
        $a_id    = $request->a_id;
        $p_id    = $request->p_id;
        $chkg_id = $request->chkg_id;
        $type = $request->type;

        // $this->ExaminationsModel->where('id',$exam_id)->update(['check_list_status'=>$checklist_status]);
        if ($type == 'performance') {
            $check_list_status = $this->CheckListHasSelectedQuestionModel
                ->where('fk_examination_id', $exam_id)
                ->where('fk_appointment_id', $a_id)
                ->where('fk_patient_id', $p_id)
                ->where('fk_check_list_id', $chkg_id)
                ->first();
        } else {
            $check_list_status = $this->CheckListHasSelectedQuestionModel
                ->where('fk_appointment_id', $a_id)
                ->where('fk_patient_id', $p_id)
                ->where('fk_check_list_id', $chkg_id)
                ->first();
        }

        if (!empty($check_list_status)) {
            $myStatus = explode(',', $check_list_status->status);
            if (!in_array($checklist_status, $myStatus)) {
                $status = $check_list_status->status . ',' . $checklist_status;
                $re_status  = str_replace("0,", "", $status);
                $check_list_status->status = ltrim($re_status, ',');
                $check_list_status->save();
            }
        } else {
            $tmp = [];
            $tmp['fk_patient_id'] = $p_id;
            $tmp['fk_appointment_id'] = $a_id;
            $tmp['fk_examination_id'] = $exam_id;
            $tmp['fk_check_list_id'] = $chkg_id;
            $tmp['status'] = $checklist_status;
            $tmp['type'] = $type;
            $tmp['check_list_flag'] = 0;
            $this->CheckListHasSelectedQuestionModel->insert($tmp);
        }

        $this->JsonData['status']   = __('admin.RESP_SUCCESS');
        $this->JsonData['msg']      = __('admin.DOCUMENT_STATUS_UPDATED');
        return response()->json($this->JsonData);
    }

    public function fetchFindings($patientId)
    {
        $collection = $this->PatientsHasDiagnosticFindingsModel
            ->leftjoin('diagnostic_findings_types', 'diagnostic_findings_types.id', '=', 'patients_has_diagnostic_findings.finding_type_id')
            ->leftjoin('patient_has_diagnostic_findings_has_documents', 'patient_has_diagnostic_findings_has_documents.finding_id', '=', 'patients_has_diagnostic_findings.id')
            ->leftjoin('patients', 'patients.id', '=', 'patients_has_diagnostic_findings.patient_id')
            ->where('patients_has_diagnostic_findings.patient_id', $patientId)
            ->where('patients_has_diagnostic_findings.export_status', '0')
            ->where('patients_has_diagnostic_findings.finding_type_id', '!=', 5)   //Added on 26sept22
            ->where('diagnostic_findings_types.name', '!=', 'Arztbrief / Entlassbericht') //Added on 26sept22
            ->orderBy('patients_has_diagnostic_findings.date', 'DESC')
            ->get([
                'patients_has_diagnostic_findings.id',
                'patients_has_diagnostic_findings.document_name',
                'patients_has_diagnostic_findings.date',
                'patients_has_diagnostic_findings.export_status',
                'patient_has_diagnostic_findings_has_documents.file',
                'patient_has_diagnostic_findings_has_documents.id as file_id',
                'patient_has_diagnostic_findings_has_documents.jpg_file',
                'patient_has_diagnostic_findings_has_documents.pdf_file',
            ]);

        if (!empty($collection) && sizeof($collection) > 0) {
            return $collection;
        }
    }

    public function exportFindings(Request $request)
    {
        //dd($request->all());
        Log::info($request->all());
        $arr_donload = [];
        // try
        // {
        $appoitment_id = $request->appoitment_id;

        $doctor_id = $this->BaseModel->where('id', $appoitment_id)->pluck('doctor_id')->first();
        $path = $this->ExportPathModel->where('doctor_id', $doctor_id)->pluck('directory_path')->first();

        if (!empty($request->findings)) {
            $findins_ids = explode(",", $request->findings);
            foreach ($findins_ids as $key => $value) {
                $findingPath = $this->PatientHasDiagnosticFindingsHasDocumentsModel
                    ->leftjoin('patients_has_diagnostic_findings', 'patients_has_diagnostic_findings.id', 'patient_has_diagnostic_findings_has_documents.finding_id')
                    ->where('finding_id', $value)
                    ->get([
                        'patient_has_diagnostic_findings_has_documents.file',
                        'patient_has_diagnostic_findings_has_documents.original_name',
                        'patient_has_diagnostic_findings_has_documents.pdf_file',
                        'patient_has_diagnostic_findings_has_documents.jpg_file',
                        'patients_has_diagnostic_findings.finding_type_id',
                        'patients_has_diagnostic_findings.status'
                    ]);

                foreach ($findingPath as $key => $finding) {
                    $findingPath = self::getFilePath($finding->file);
                    $arr_donload[] = $findingPath;
                    $this->PatientsHasDiagnosticFindingsModel->where('id', $value)->update(['export_status' => '1']);
                }
            }
        }
        if (!empty($request->document)) {
            $document_ids = explode(",", $request->document);
            foreach ($document_ids as $key => $value) {
                $doc_data = $this->PatientHasDocumentsModel
                    ->where('appointment_id', $appoitment_id)
                    ->where('patient_id', $request->patient_id)
                    ->where('fk_document_id', $value)
                    ->first();
                if (!empty($doc_data)) {
                    $docBasePath = self::getFilePath($doc_data->pdf_path);
                    $arr_donload[] = $docBasePath;
                    $this->PatientHasDocumentsModel->where('id', $doc_data['id'])->update(['export_status' => '1']);
                }
            }
        }
        if (!empty($request->checklist)) {
            $checklist_ids = explode(",", $request->checklist);
            foreach ($checklist_ids as $key => $value) {
                $checklist_data = $this->CheckListHasSelectedQuestionModel
                    ->where('fk_appointment_id', $appoitment_id)
                    ->where('fk_patient_id', $request->patient_id)
                    ->where('fk_check_list_id', $value)
                    ->whereNotNull('pdf_name')
                    ->first();
                //dd($checklist_data,$appoitment_id,$request->patient_id);      
                $checkBasePath = self::getFilePath($checklist_data->pdf_path);
                $arr_donload[] = $checkBasePath;
                $this->CheckListHasSelectedQuestionModel->where('id', $checklist_data->id)->update(['export_status' => '1']);
            }
        }
        $this->JsonData['arr_donload']   = $arr_donload;
        $this->JsonData['status']        = __('admin.RESP_SUCCESS');
        $this->JsonData['msg']           = __('admin.DOCUMENT_EXPORTED');
        //}
        // catch(\Exception $e) 
        // { 
        //     $this->JsonData['error']   = __('admin.ERR_SOMETHING_WRONG');
        //     $this->JsonData['error_msg'] = $e->getMessage();
        // }
        return response()->json($this->JsonData);
    }


    // SEND DOCUMENT TO Pestient
    public function getPatientDetails(Request $request)
    {
        $collection = [];
        $patient_details = $this->PatientsModel
            ->where('id', $request->p_id)
            ->first();

        if (!empty($patient_details)) {
            $collection['patient_name'] = $patient_details['first_name'] . ' ' . $patient_details['family_name'];
            $collection['p_id']         = $patient_details['id'];
            $collection['doc_id']       = $request->doc_id;
            $collection['email']        = $patient_details['email'];
        }
        return $collection;
    }

    public function sendDocumentForPatients(Request $request)
    {

        $data['msg']  = __('admin.ERR_SOMETHING_WRONG');
        $data['flag'] = 'false';
        $collection = $data = $result = [];
        try {

            // PATIENT DETAILS
            $patient_details = $this->PatientsModel
                ->where('id', $request->hd_patient_id)
                ->first();

            if (!empty($patient_details)) {
                $collection['type'] = $request->type;
                $collection['patients_name'] = $patient_details['first_name'] . ' ' . $patient_details['family_name'];

                if (!empty($request->to)) {
                    $email = $request->to;
                } else {
                    $email = $patient_details['email'];
                }


                if ($request->type == 'doc') {

                    //DOCUMENT DETSILS
                    //dd($request->doc_type);
                    if ($request->doc_type == 'services') {
                        $result = $this->PatientHasDocumentsModel
                            ->where('appointment_id', $request->a_id)
                            ->where('patient_id', $request->hd_patient_id)
                            ->where('fk_document_id', $request->hd_doc_id)
                            ->where('fk_examinations_id', $request->exam_id)
                            ->where('type', 'service')
                            ->first();
                    } else {
                        //dd($request->a_id,$request->hd_patient_id,$request->hd_doc_id);
                        $result = $this->PatientHasDocumentsModel
                            ->where('appointment_id', $request->a_id)
                            ->where('patient_id', $request->hd_patient_id)
                            ->where('fk_document_id', $request->hd_doc_id)
                            ->where('type', 'general')
                            ->first();
                        //dd($result);      
                    }

                    if (!empty($result)) {
                        if (!empty($result->pdf_path)) {
                            $collection['attachments'] = url($result->pdf_path);
                        } else {
                            $pdf_path = self::generateDocumentPDF($request->hd_doc_id, $request->hd_patient_id);
                            $collection['attachments'] = url($pdf_path);
                        }
                    }
                } else if ($request->type == 'check_list') {
                    if ($request->doc_type == 'performance') {
                        $result = $this->CheckListHasSelectedQuestionModel
                            ->where('fk_patient_id', $request->hd_patient_id)
                            ->where('fk_appointment_id', $request->a_id)
                            ->where('fk_examination_id', $request->exam_id)
                            ->where('fk_check_list_id', $request->hd_doc_id)
                            ->where('type', 'performance')
                            ->first();
                    } else {
                        $result = $this->CheckListHasSelectedQuestionModel
                            ->where('fk_patient_id', $request->hd_patient_id)
                            ->where('fk_appointment_id', $request->a_id)
                            ->where('fk_check_list_id', $request->hd_doc_id)
                            ->where('type', 'general')
                            ->first();
                    }
                    if (!empty($result)) {
                        if (!empty($result->pdf_path)) {

                            $collection['attachments'] = url($result->pdf_path);
                        } else {
                            $pdf_path  = self::generateChecklistPDF($request->hd_doc_id, $request->hd_patient_id);
                            $collection['attachments'] = url($pdf_path);
                        }
                    }
                }

                if (!empty($email)) {
                    $email_result = Mail::to($email)->send(new SendDocumentForPatientmail($collection));
                    if (empty($email_result)) {
                        if ($request->type == 'check_list') {
                            $chk_rec = $this->CheckListHasSelectedQuestionModel->find($result->id);
                            $myStatus = explode(',', $chk_rec->status);;
                            if (!in_array('4', $myStatus)) {
                                $status = $chk_rec->status . ',4';
                                $re_status  = str_replace("0,", "", $status);

                                $chk_rec->status = ltrim($re_status, ',');
                                $chk_rec->save();
                            }
                        } else {
                            $doc_rec = $this->PatientHasDocumentsModel->find($result->id);
                            $myStatus = explode(',', $doc_rec->doc_status);
                            if (!in_array('4', $myStatus)) {
                                $doc_status = $doc_rec->doc_status . ',4';
                                $re_status  = str_replace("0,", "", $doc_status);
                                $chk_rec->doc_status = ltrim($doc_status, ',');
                                $doc_rec->save();
                            }
                        }

                        $data['msg']  = __('admin.TITLE_DOCUMENT_SEND');
                        $data['flag'] = 'true';
                        $data['p_id'] = $request->hd_patient_id;
                    }
                } else {
                    $data['msg']  = __('admin.ERR_SOMETHING_WRONG');
                    $data['flag'] = 'false';
                    $data['p_id'] = '';
                }
            }
        } catch (\Exception $e) {
            $message = __('admin.ERR_SOMETHING_WRONG');
            $errors[] = [
                "error" => $e->getMessage(),
            ];
        }
        Session::put('redirect_arr', $data);
        return redirect('admin/doctor-dashboard');
    }
    // Generate Checklisr pdf
    public function generateChecklistPDF($chk_id, $patient_id = '')
    {
        // log::info('generateChecklistPDF 26-dec-22');

        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = $exam_id = '';

        $collections = $this->CheckListModel
            ->where('id', $chk_id)
            ->where('status', 1)
            ->first();

        /*************Added on 26-dec-22***********/
        $imagepath = '';
        $getDatabase = DB::connection('system')->table("tenants")
            ->where('ordination_id', Config('ordination_id'))->first(['uuid']);
        $imagepath = url('storage/tenancy/tenants/' . $getDatabase->uuid);
        /*************Added on 26-dec-22***********/


        if (!empty($collections)) {
            //check list details
            $data[$cnt]['signature']         = '';
            $data[$cnt]['checklist_id']      = $collections->id;
            $data[$cnt]['check_list_name']   = $collections->check_list_name;
            $data[$cnt]['introduction_text'] = $collections->introduction_text;
            $data[$cnt]['final_name']        = $collections->final_name;

            /*******Added by divya on 26-dec-22*********/
            $data[$cnt]['header_image']        = isset($collections->header_image) ? $collections->header_image : "";
            $data[$cnt]['header_image_path']   = isset($collections->header_image_path) ? $collections->header_image_path : "";
            $data[$cnt]['footer_image']        = isset($collections->footer_image) ? $collections->footer_image : "";
            $data[$cnt]['footer_image_path']   = isset($collections->footer_image_path) ? $collections->footer_image_path : "";

            /*******Added by divya on 26-dec-22*********/


            $j = 0;
            $heading = $this->CheckListHasHeadingSectionModel
                ->where('fk_check_list_id', $chk_id)->get();
            foreach ($heading as $heading) {
                //check list heading
                $data[$cnt]['heading'][$j]['fk_chk_id'] = $collections->id;
                $data[$cnt]['heading'][$j]['heading_id'] = $heading['id'];
                $data[$cnt]['heading'][$j]['heading']  = $heading['heading_section'];

                $k = 0;
                $question = $this->HeadingSectionHasQuestionModel
                    ->where('fk_check_list_heading_section_id', $heading['id'])
                    ->get();
                foreach ($question as $key => $value) {
                    //check list question
                    $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $heading['id'];
                    $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $value['id'];
                    $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $value['question'];
                    $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']   = 0;
                    $k++;
                }
                $j++;
            }
            //$PdfPath   = self::StorePath('check_list_pdf/');
            if (!empty(Config('ordination_id'))) {
                $getDatabaseName = DB::connection('system')
                    ->table("tenants")
                    ->where('ordination_id', Config('ordination_id'))
                    ->first(['uuid']);
                $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/' . $getDatabaseName->uuid . '/check_list_pdf/';
            } else {
                $PdfPath = '/opt/app-shared/php/data/storage/app/public/check_list_pdf/';
            }
            //$PdfPath   = storage_path().'/app/public/check_list_pdf/';
            //$PDFname   = $collections['check_list_name'].'_'.time().'.pdf';
            // $PDFname = str_replace(' ', '' , $collections['check_list_name']);
            // $PDFname   = trim($PDFname).'_'.time().'.pdf';

            $PDFname = self::createPdfFileName($patient_id, $collections['check_list_name']);
            // Invoice full path
            $StorePath = $PdfPath . $PDFname;
            $accessPath = '/check_list_pdf/' . $PDFname;
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
            $PDFPath = 'admin.pdf.checkLists';
            $pdf->loadView($PDFPath, compact('data'))->save($StorePath, 'UTF-8');
            // end
            return $accessPath;
        }
    }
    // Generate Document PFG
    public function generateDocumentPDF($doc_id, $patient_id = '')
    {
        $data = $dataFinal = [];
        $doc_flag = 0;
        $flag = '0';
        $file_name = $exam_id = $app_id = '';
        $collections = $this->SpecialistDocumentsModel->find($doc_id);
        if (!empty($collections)) {
            $data['doc_id']            = $collections['id'];
            $data['name']              = $collections['name'];
            $data['html_text']         = $collections['html_text'];
            $data['background_color']  = $collections['background_color'];
            $data['header_image']      = $collections['header_image'];
            $data['header_image_path'] = $collections['header_image_path'];
            $data['footer_image']      = $collections['footer_image'];
            $data['footer_image_path'] = $collections['footer_image_path'];
            $data['background_color']  = $collections['background_color'];
            //$cnt++;

            //$PdfPath   = self::StorePath('document_pdf/');
            if (!empty(Config('ordination_id'))) {
                $getDatabaseName = DB::connection('system')
                    ->table("tenants")
                    ->where('ordination_id', Config('ordination_id'))
                    ->first(['uuid']);
                $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/' . $getDatabaseName->uuid . '/document_pdf/';
            } else {
                $PdfPath = '/opt/app-shared/php/data/storage/app/public/document_pdf/';
            }
            //$PdfPath   = storage_path().'/app/public/document_pdf/';
            // $PDFname   = $collections['name'].'_'.time().'.pdf';
            // $PDFname = str_replace(' ', '' , $collections['name']);
            // $PDFname   = trim($PDFname).'_'.time().'.pdf';
            $PDFname = self::createPdfFileName($patient_id, $collections['name']);
            // Invoice full path
            $StorePath = $PdfPath . $PDFname;
            $accessPath = '/document_pdf/' . $PDFname;
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
            $PDFPath = 'admin.pdf.documentLists';
            $pdf->loadView($PDFPath, compact('data'))->save($StorePath);
        }
        return $accessPath;
    }

    public function getServices(Request $request)
    {
        // dd($request->all());

        Log::info("=== getServices function START ===");
        Log::info("Request data:", $request->all());

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');
        try {
            $appointment_type_id = $request->appointment_type_id; //42
            $patient_id          = $request->patient_id; //5197
            $appointment_id      = $request->a_id;
            $str = '';
            Log::info("Executing collections1 query...");
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
                ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
                ->get([
                    'examinations.id',
                    'examinations.name',
                    'examinations.url',
                    'examinations.description',
                    'examinations.document_name',
                    'examinations.document_path',
                    'examinations.document_status',
                    'examinations.status',
                    'examinations.created_at',
                    'examinations.show_as_recommended'
                ]);
            Log::info("collections1 query completed. Count: " . $collections1->count());
            Log::info("collections1 data:", $collections1->toArray());
            // dump("collections1...");
            // dump($collections1);

                

            $today_date = date("Y-m-d");

            //commented below code on 27-dec-23
            // Filter add for age base services
            /* $collections1 = $collections1->filter(function($item) use ($patient_id,$appointment_type_id,$today_date) 
            {
                 Log::info("collections1.filter.function.");      

                $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                $age_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','age')
                                    ->first();
                //Added by swati 2-nov-22=========================
                    Log::info("service_id===>.");                       
                  Log::info($item->id);    
                   Log::info("age_service===>.");                      
                   Log::info($age_service);                   

                $general_reminder_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','general')
                                    ->first();
                //============================          

                      Log::info("general_reminder_service===>.");                      
                   Log::info($general_reminder_service);                         

                if(!empty($age_service) && $item->name != $app_type_name->name)
                {
                        Log::info(" if .....age_service===>."); 
                    $getPatientAge = $this->PatientsModel->find($patient_id);

                    Log::info($getPatientAge);    

                    if(!empty($getPatientAge))
                    {
                        $patient_age = $getPatientAge->age;

                          Log::info(" .patient_age===>."); 
                             Log::info($patient_age);  

                        if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                        {
                             Log::info("in patient age criteria===>."); 
                            return $item;
                        }
                    }
                }
                else if(!empty($general_reminder_service))
                {
                    $checkGenaralService =  $this->PatientsHasServiceReminderModel
                                                ->where('service_id',$item->id)
                                                ->where('patient_id',$patient_id)
                                                ->where('reminder_status','Set')
                                                ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                                ->first();                         
                    if(empty($checkGenaralService)) return $item;                            
                }//Added this else by swati 2-nov-22
                else {
                    return $item;
                }
            });            

            */

            /******************start**********below code on 27-dec-23***(2-jan-24)***********************/

            Log::info("Starting collections1 filter...");
            $collections1 = $collections1->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) {

                // $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id); //commented on 13-apr-26
                $app_type_name = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id);  //changed on 13-apr-26

                if ($item->name == $app_type_name->name) {

                    return $item;
                } else {

                    $collectionsFilter = $this->PatientsHasServiceReminderModel
                        ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status'))
                        ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                        ->join(
                            DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                            FROM patient_has_service_reminder 
                                            WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                            and status='activate'
                                            and deleted_at is NULL GROUP BY service_id)
                                        patientremidners"),
                            function ($join) {
                                $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                            }
                        )
                        ->where('patient_has_service_reminder.patient_id', $patient_id)
                        ->where('patient_has_service_reminder.status', 'activate')
                        ->where('examinations.id', $item->id)
                        ->whereRaw("date(reminder_date) <= '" . $today_date . "'")
                        // ->where(function ($query) use ($today_date) {
                        //     $query->whereRaw("(date(reminder_date) <= '" . $today_date . "' 
                        //                      )")
                        //           ->orWhereRaw("(date(reminder_date) > '" . $today_date . "' 
                        //                      AND patient_has_service_reminder.cycle_no != 1 
                        //                      AND patient_has_service_reminder.appointment_id!=0)");
                        // })
                        ->groupBy('patient_has_service_reminder.service_id')
                        ->get();

            

                    if (isset($collectionsFilter) && !empty($collectionsFilter) && $collectionsFilter->count() > 0) {

                        $collectionsFilter = $collectionsFilter->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) {

                            // $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);//commented on 13-apr-26

                            $app_type_name = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id);

                            $age_service =  $this->ChannelsRemindersSettingModel
                                ->where('service_id', $item->id)
                                ->where('activated_reminder', 'age')
                                ->first();
                            //Added by swati 2-nov-22=========================
                            $general_reminder_service =  $this->ChannelsRemindersSettingModel
                                ->where('service_id', $item->id)
                                ->where('activated_reminder', 'general')
                                ->first();
                            //============================                  
                            if (!empty($age_service) && $item->name != $app_type_name->name) {
                                $getPatientAge = $this->PatientsModel->find($patient_id);
                                if (!empty($getPatientAge)) {
                                    $patient_age = $getPatientAge->age;
                                    if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                        //commented on 26-dec-23
                                        return $item;
                                    } //if
                                }
                            } else if (!empty($general_reminder_service)) {
                                $checkGenaralService =  $this->PatientsHasServiceReminderModel
                                    ->where('service_id', $item->id)
                                    ->where('patient_id', $patient_id)
                                    ->where('reminder_status', 'Set')
                                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                    ->first();
                                if (empty($checkGenaralService)) return $item;
                            } //Added this else by swati 2-nov-22
                            else {
                                return $item;
                            }
                        });
                    } //if isset collection filter
                    else {

                        $hasReminderSet = $this->PatientsHasServiceReminderModel
                            ->where('patient_has_service_reminder.patient_id', $patient_id)
                            ->where('patient_has_service_reminder.service_id', $item->id)
                            ->first();
                        if (isset($hasReminderSet) && !empty($hasReminderSet)) {
                        } //if hasReminderSet
                        else {
                            return $item;
                        }

                        // return $item;  
                    } //else   

                } //else

            });
            Log::info("collections1 filter completed. Count: " . $collections1->count());

            /************end******above code on 27-dec-23***(2-jan-24)*************************/


            // dump("collections1 again...");
            // dump($collections1);

            Log::info("2nd ...collections1...");
            Log::info($collections1);

            $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
            Log::info("Extracted exams_ids:", $exams_ids);

            //   dump("exams_ids ...");
            // dump($exams_ids);

            $today_date = date("Y-m-d");
            /*$collections2 = $this->PatientsHasServiceReminderModel
                                ->select(DB::raw('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
                                ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                                ->where('patient_has_service_reminder.patient_id',$patient_id)
                                //->where('patient_has_service_reminder.type','age')
                                ->where('patient_has_service_reminder.status','activate')
                                ->whereNotIn('examinations.id',$exams_ids)
                                ->whereRaw("date(reminder_date) <= '".$today_date."'")  
                                // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                                //                 select service_id from patient_has_service_reminder 
                                //                 where `patient_has_service_reminder`.`patient_id`=".$patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)")) 
                                //->where('patient_has_service_reminder.reminder_status','Set') 
                                ->groupBy('patient_has_service_reminder.service_id') 
                                ->get(); 
                                    // //->get([
                                    //         'examinations.id',
                                    //         'examinations.name',
                                    //         'examinations.url',
                                    //         'examinations.description',
                                    //         'examinations.document_name',
                                    //         'examinations.document_path',
                                    //         'examinations.document_status',
                                    //         'examinations.status',
                                    //         'examinations.created_at',
                                    //         'examinations.show_as_recommended'
                                    //     ]);*/
            Log::info("Executing collections2 query...");
            $collections2 = $this->PatientsHasServiceReminderModel
                ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status,patient_has_service_reminder.id as reminderid'))
                ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                ->join(
                    // DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                    //                         FROM patient_has_service_reminder 
                    //                         WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                    //                         and status='activate'
                    //                         and deleted_at is NULL GROUP BY service_id)
                    //                     patientremidners"),  

                    DB::raw("(SELECT service_id,patient_has_service_reminder.id as reminderid,MAX(appointment_id) appointment_id 
                        FROM patient_has_service_reminder 
                        WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                        and status='activate'
                        AND (
                        ( (deleted_at IS NULL AND cycle_no = 1 AND date(reminder_date) <= '" . $today_date . "' AND type!='age' ) 
                           OR
                           (  deleted_at IS NULL and cycle_no>=0 AND date(reminder_date) <= '" . $today_date . "' and type='age' 
                           )
                        )                        
                        OR 
                        ( (deleted_at IS NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' AND type!='age') 
                           OR (
                              (deleted_at IS  NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' and type='age' and appointment_id!=0) 
                              OR (deleted_at IS  NULL AND cycle_no >= 1 AND date(reminder_date) >= '" . $today_date . "' and type='age' and appointment_id=0)) 
                        )
                    )  GROUP BY service_id) 
                    patientremidners"),
                 


                    function ($join) {
                        $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                        $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');

                    }
                )
                ->where('patient_has_service_reminder.patient_id', $patient_id)
                ->where('patient_has_service_reminder.status', 'activate')
                // ->where('examinations.status.status','activate')
                ->whereRaw("examinations.show_as_reminder='1'")
                ->whereNotIn('examinations.id', $exams_ids)
                // ->whereRaw("date(reminder_date) <= '" . $today_date . "'") //commented on 27-march-25
                // ->where(function ($query) use ($today_date) {
                //             $query->whereRaw("(date(reminder_date) <= '" . $today_date . "' 
                //                              )")
                //                   ->orWhereRaw("(date(reminder_date) > '" . $today_date . "' 
                //                              AND patient_has_service_reminder.cycle_no != 1 
                //                              AND patient_has_service_reminder.appointment_id!=0)");
                //         })
                //added on 27-march-25

                ->groupBy('patient_has_service_reminder.service_id')
                ->get();
            Log::info("collections2 query completed. Count: " . $collections2->count());
            Log::info("collections2 data:", $collections2->toArray());
            // print_r(DB::getQueryLog());
            // dd($collections2);
            // log::info("getServices");
            // log::info($collections2);

            Log::info("in collections2...");
            Log::info($collections2);

            // dump("collections2 ...");
            // dump($collections2);


            $collections2 = $collections2->filter(function ($item) use ($patient_id, $today_date) {
                $age_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();
                if (!empty($age_service)) {
                    //log::info($patient_id);
                    $getPatientAge = $this->PatientsModel->find($patient_id);
                    if (!empty($getPatientAge)) {
                        $patient_age = $getPatientAge->age;
                        // log::info($age_service->age_from."<=".$patient_age."&&".$age_service->age_to.">=". $patient_age);
                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            // log::info($item);
                            if ($item->reminder_status == 'executed') {
                                $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                    ->where('service_id', $item->id)
                                    ->where('patient_id', $patient_id)
                                    ->where('reminder_status', 'Set')
                                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                    ->first();
                                //echo "<pre>";print_r($checkServiceReminders);
                                if (empty($checkServiceReminders))
                                    return $item;
                            } else return $item;
                        }
                    }
                }
                // else {
                //     if($item->reminder_status=='executed'){
                //         $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                //                         ->where('service_id',$item->id)
                //                         ->where('patient_id',$patient_id)
                //                         ->where('reminder_status','Set')
                //                         ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                //                         ->first();
                //         //echo "<pre>";print_r($checkServiceReminders);
                //         if(empty($checkServiceReminders))
                //             return $item;
                //     } 
                //     else return $item;
                // }

                //Added by swati 2-nov-22=========================
                $general_reminder_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'general')
                    ->first();

                if (!empty($general_reminder_service)) {

                    $today_date = date("Y-m-d");

                    //commented on 15-oct-24
                    /*$checkServiceReminders =  $this->PatientsHasServiceReminderModel
                        ->where('service_id', $item->id)
                        ->where('patient_id', $patient_id)
                        ->where('reminder_status', 'Set')
                        ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                        ->first();
                    if (empty($checkServiceReminders))
                        return $item;*/

                    //start added executed condition on 15-ot-24    
                    if($item->reminder_status == 'executed')
                    {     
                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                            ->where('service_id', $item->id)
                            ->where('patient_id', $patient_id)
                            ->where('reminder_status', 'Set')
                            ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                            ->first();
                        if (empty($checkServiceReminders))
                            return $item;   
                    }else{
                        return $item;
                    }
                    //end added executed condition on 15-ot-24   

                }
                //Add checkup remidners as recommandation 4-sep-23=========================
                $checkup_reminder_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'checkup')
                    ->first();

                if (!empty($checkup_reminder_service)) {

                    $today_date = date("Y-m-d");

                    //commented on 15-oct-24
                    /*$checkServiceReminders =  $this->PatientsHasServiceReminderModel
                        ->where('service_id', $item->id)
                        ->where('patient_id', $patient_id)
                        ->where('reminder_status', 'Set')
                        ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                        ->first();
                    if (empty($checkServiceReminders))
                        return $item;*/
                        
                     //start added executed condition on 15-ot-24    
                    if($item->reminder_status == 'executed')
                    {
                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                        ->where('service_id', $item->id)
                        ->where('patient_id', $patient_id)
                        ->where('reminder_status', 'Set')
                        ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                        ->first();
                        if (empty($checkServiceReminders))
                         return $item;
                    }else{
                        return $item;
                    }    
                    //end added executed condition on 15-ot-24  

                }
                //================================================
            });
            Log::info("collections2 filter completed. Count: " . $collections2->count());
            // log::info("getRecord");
            // log::info("getServices");

            // dump("collections2 .again..");
            // dump($collections2);

            Log::info("Merging collections1 and collections2...");
            $getRecord = $collections1->merge($collections2);
            Log::info("Merge completed. Total records: " . $getRecord->count());
            // log::info($getRecord);
            if ($appointment_id == 'undefined') {
                $appointment_id = '';
            }
            $appointment_exam = [];
            Log::info("Checking if appointment_id is not empty: " . (!empty($appointment_id) ? 'Yes' : 'No'));
            if (!empty($appointment_id)) {
                $getAppointmentServcies = $this->AppointmentHasExaminationsModel
                    ->where('appointment_id', $appointment_id)
                    ->where('patient_id', $patient_id)
                    ->get();
                if (!empty($getAppointmentServcies)) {
                    foreach ($getAppointmentServcies as $key => $value) {
                        $appointment_exam[] = $value->examination_id;
                    }
                    $collections3 = $this->PatientsHasServiceReminderModel
                        ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status'))
                        ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                        ->join(
                            DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                                    FROM patient_has_service_reminder 
                                                    WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                                    and status='activate'
                                                    and deleted_at is NULL GROUP BY service_id)
                                                patientremidners"),
                            function ($join) {
                                $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                            }
                        )
                        ->where('patient_has_service_reminder.patient_id', $patient_id)
                        ->where('patient_has_service_reminder.status', 'activate')
                        ->whereIn('examinations.id', $appointment_exam)
                        ->whereRaw("date(reminder_date) <= '" . $today_date . "'")
                        ->groupBy('patient_has_service_reminder.service_id')
                        ->get();



                    /*$this->PatientsHasServiceReminderModel
                                        ->select(DB::raw('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
                                        ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                                        ->where('patient_has_service_reminder.patient_id',$patient_id)
                                        ->whereIn('examinations.id',$appointment_exam)
                                        ->whereRaw("date(reminder_date) <= '".$today_date."'") 
                                        // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                                        //         select service_id from patient_has_service_reminder 
                                        //         where `patient_has_service_reminder`.`patient_id`=".$patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)"))   
                                        ->groupBy('patient_has_service_reminder.service_id') 
                                        ->get(); */
                    $collections3 = $collections3->filter(function ($item) use ($patient_id, $today_date) {
                        $age_service =  $this->ChannelsRemindersSettingModel
                            ->where('service_id', $item->id)
                            ->where('activated_reminder', 'age')
                            ->first();
                        if (!empty($age_service)) {
                            $getPatientAge = $this->PatientsModel->find($patient_id);
                            if (!empty($getPatientAge)) {
                                $patient_age = $getPatientAge->age;
                                if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                    if ($item->reminder_status == 'executed') {
                                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                            ->where('service_id', $item->id)
                                            ->where('patient_id', $patient_id)
                                            ->where('reminder_status', 'Set')
                                            ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                            ->first();
                                        //echo "<pre>";print_r($checkServiceReminders);
                                        if (empty($checkServiceReminders))
                                            return $item;
                                    } else return $item;
                                }
                            }
                        }
                        $general_reminder_service =  $this->ChannelsRemindersSettingModel
                            ->where('service_id', $item->id)
                            ->where('activated_reminder', 'general')
                            ->first();
                        if (!empty($general_reminder_service)) {

                            $today_date = date("Y-m-d");
                            $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                ->where('service_id', $item->id)
                                ->where('patient_id', $patient_id)
                                ->where('reminder_status', 'Set')
                                ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                ->first();
                            if (empty($checkServiceReminders))
                                return $item;
                        }
                        //Add checkup remidners as recommandation 4-sep-23=========================
                        $checkup_reminder_service =  $this->ChannelsRemindersSettingModel
                            ->where('service_id', $item->id)
                            ->where('activated_reminder', 'checkup')
                            ->first();

                        if (!empty($checkup_reminder_service)) {

                            $today_date = date("Y-m-d");
                            $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                ->where('service_id', $item->id)
                                ->where('patient_id', $patient_id)
                                ->where('reminder_status', 'Set')
                                ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                ->first();
                            if (empty($checkServiceReminders))
                                return $item;
                        }
                        //================================================

                    });
                }
                $duplicateRecord = $getRecord->merge($collections3);
                $getRecord = $duplicateRecord->unique();
            }
            // log::info($getRecord);

            /**********added on 13-feb-26******************************/

            $originalAppointment = $this->AppointmentModel->find($appointment_id);

            // if ($originalAppointment && $originalAppointment->appointment_type_id == $request->appointment_type_id)

            if ($originalAppointment)  
            {
              

                $existingServices = $this->AppointmentHasExaminationsModel
                ->where('appointment_id', $appointment_id)
                ->where('patient_id', $patient_id)
                ->pluck('examination_id')
                ->toArray();

                Log::info("existingServices");
                log::info($existingServices);

                // 2️⃣ Only keep services that exist in reminder table added on 16-feb-26
                $reminderServiceIds = DB::table('patient_has_service_reminder')
                    ->where('patient_id', $patient_id)
                    ->whereIn('service_id', $existingServices)
                    ->whereNull('deleted_at')
                    ->pluck('service_id')
                    ->toArray();

                Log::info("reminderServiceIds");
                log::info($reminderServiceIds);    

                $forcedServices = $this->ExaminationsModel
                    ->whereIn('id', $reminderServiceIds)
                    ->get();

                Log::info("forcedServices");
                log::info($forcedServices);   
                $getRecord = $getRecord->merge($forcedServices)->unique('id');
            }//if

            Log::info("getRecord");
            log::info($getRecord); 

            /*************added on 13-feb-26****************************/

            if (!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id)) {
                $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {

                    $app_type_name = $this->AppointmentHasExaminationsModel
                        ->where('appointment_id', $appointment_id)
                        ->where('patient_id', $patient_id)
                        ->where('examination_id', $item->id)
                        ->with(['assignedExamination'])
                        //->where('examination_id',$exam_id)
                        ->first();

                    //added on 29-nov-24 for #253 issue    
                    // $appTypeNameDefault = $this->AppointmentTypesModel->find($appointment_type_id); //commented on 13-apr-26
                     $appTypeNameDefault = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id);  //changed on 13-apr-26  
                    
                      //start added on 29-nov-24 for #253 issue
                    if(!empty($appTypeNameDefault))
                    {
                       
                        if ($item->name == $appTypeNameDefault->name) 
                        { 
                            $item->checked = 1;
                            $item->sameName = 1;
                            return $item; //added on 5-jan-26
                        }
                    }
                    //end added on 29-nov-24 for #253 issue

                    if (!empty($app_type_name)) {
                        $item->checked = 1;
                        $item->sameName = 0;
                        return $item;
                    }
                    if (empty($item->description)) //When Discription is blank
                    {
                        $item->checked = 1;
                        $item->sameName = 0;
                        return $item;
                    }



                    return $item;
                });
            } else {
                $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {

                    // $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);//commented on 13-apr-26

                    $app_type_name = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id);  //changed on 13-apr-26  


                    if (!empty($app_type_name)) {
                        if ($item->name == $app_type_name->name) {
                            $item->checked = 1;
                            $item->sameName = 1;
                        } else if (empty($item->description)) {
                            $item->checked = 1;
                            $item->sameName = 0;
                        }
                        return $item;
                    }
                });
            }
            // dd($getRecord);
            // log::info($getRecord);
            //added by vijay 13/3/2024
            Log::info("Getting appointment non-services IDs...");
            $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($appointment_type_id);
            Log::info("Non-services IDs:", $getAppointmentNonServciesIds);
            Log::info("Final getRecord check - count: " . sizeof($getRecord));
            if (!empty($getRecord) && sizeof($getRecord) > 0) {
                $str .= "<label class='theme-blue'>" . __('admin.TITLE_APPOINTMENT_SERVICES') . "</label>";
                Log::info("Starting services loop. Total records to process: " . $getRecord->count());
                foreach ($getRecord as $key => $value) {
                    Log::info("Processing service ID: " . $value->id . ", Name: " . $value->name);
                    // condition added by vijay 13/3/2024
                    if (!in_array($value->id, $getAppointmentNonServciesIds)) {
                        ////Added by Shyam 29-12-21
                        $checked = '';
                        // log::info($key.">>>".$value);
                        if (empty($appointment_id)) {
                            $getReminderDate = DB::table('patient_has_service_reminder')
                                ->where('patient_id', $patient_id)->where('service_id', $value->id)
                                ->where('status', 'activate')->where('reminder_status', 'Set')
                                ->whereNull('deleted_at')->orderBy('reminder_date', 'DESC')
                                ->pluck('reminder_date')->first();
                            if ($value['checked'] == 1 && $value['show_as_recommended'] != '1') {
                                $checked = 'checked';

                                //start added on 5-jan-26 for #383
                                $lock = '';
                                if ($value['sameName'] == 1) {
                                    $lock = 'onclick="return false;"';
                                }
                                //end added on 5-jan-26 for #383

                                $str .= "<div class='form-check'> 
                                    <input type='checkbox' " . $checked . " ".$lock." class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                    <label class='form-check-label' for='status'>" . $value->name . "</label>
                                </div>";
                                // if(!empty($getReminderDate) && $value['sameName'] != 1)
                                // {
                                //     $getData =  $this->AppointmentHasExaminationsModel
                                //                     ->leftjoin('appointment','appointment.id','=','appointment_has_examinations.appointment_id')
                                //                     ->where('appointment_has_examinations.patient_id', $patient_id)
                                //                     ->where('appointment_has_examinations.examination_id', $value->id)
                                //                     ->where('appointment.patient_id', $patient_id)
                                //                     ->where('appointment.start_date', '<', $getReminderDate)
                                //                     ->get(['appointment.start_date', 'appointment.patient_id']);
                                // }
                                // if(empty(@$getData[0]->start_date) || strtotime(@$getData[0]->start_date) < strtotime(date('Y-m-d')))
                                // {
                                //     $str .= "<div class='form-check'> 
                                //         <input type='checkbox' ".$checked." class='form-check-input' name='app_services[]' name='status' value=".$value->id." >
                                //         <label class='form-check-label' for='status'>".$value->name."</label>
                                //     </div>";
                                // }
                            }
                            // if($value['show_as_recommended'] == '1' && $checked = '')
                            else if ($value['show_as_recommended'] == '1' || $value['description'] != '') {
                                $checked = '';
                                $lock = '';  // added on 5-jan-26 for #383
                                if ($value['sameName'] == 1) {
                                    $checked = 'checked';
                                    $lock = 'onclick="return false;"'; // added on 5-jan-26 for #383
                                }
                                $str .= "<div class='form-check'> 
                                    <input type='checkbox' " . $checked . " " . $lock . "  class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                    <label class='form-check-label' for='status'>" . $value->name . "</label>
                                </div>";
                                // if(!empty($getReminderDate) && $value['sameName'] != 1)
                                // {
                                //     $getData =  $this->AppointmentHasExaminationsModel
                                //                 ->leftjoin('appointment','appointment.id','=','appointment_has_examinations.appointment_id')
                                //                 ->where('appointment_has_examinations.patient_id', $patient_id)
                                //                 ->where('appointment_has_examinations.examination_id', $value->id)
                                //                 ->where('appointment.patient_id', $patient_id)
                                //                 ->where('appointment.start_date', '<', $getReminderDate)
                                //                 ->get(['appointment.start_date', 'appointment.patient_id']);
                                // }
                                // if(empty(@$getData[0]->start_date) || strtotime(@$getData[0]->start_date) < strtotime(date('Y-m-d')))
                                // {
                                //     $checked = '';
                                //     if($value['sameName'] == 1) {
                                //         $checked = 'checked';
                                //     }
                                //     $str .= "<div class='form-check'> 
                                //             <input type='checkbox' ".$checked." class='form-check-input' name='app_services[]' name='status' value=".$value->id." >
                                //             <label class='form-check-label' for='status'>".$value->name."</label>
                                //         </div>";
                                // }
                            }
                        } else {
                            if ($value['checked'] == 1) {
                                $checked = 'checked';
                            }

                            $lock = '';  // added on 5-jan-26 for #383
                            if ($value['sameName'] == 1) {
                                $lock = 'onclick="return false;"'; // added on 5-jan-26 for #383
                            }


                            $str .= "<div class='form-check'> 
                                    <input type='checkbox' " . $checked . " " . $lock . "  class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                    <label class='form-check-label' for='status'>" . $value->name . "</label>
                                </div>";
                        }
                    }
                    ////Commented by Shyam 29-12-21
                    // $checked ='';
                    // //Added by Shyam 21-12-21
                    // $checkDate = date('Y-m-d H:i:s', strtotime('-30 day', strtotime(date('Y-m-d H:i:s'))));
                    // $isExists = DB::table('appointment')
                    //             ->leftjoin('appointment_has_examinations as ae','ae.appointment_id','appointment.id')
                    //             ->where('appointment.patient_id',$patient_id)
                    //             ->where('appointment.start_date', '>', $checkDate)
                    //             ->where('appointment.appointment_type_id',$appointment_type_id)
                    //             ->where('ae.examination_id',$value->id)
                    //             ->get(['ae.id','ae.examination_id','appointment.start_date','appointment.patient_id']);
                    // // if($value['checked'] == 1)
                    // // if($value['checked'] == 1 && (empty($isExists) || sizeof($isExists) == 0)) //Added by Shyam 21-12-21
                    // if($value['checked'] == 1) //Added by Shyam 21-12-21
                    // {
                    //     $checked = 'checked';
                    // }
                    // if($value['show_as_recommended'] == '1' && (!empty($isExists) || sizeof($isExists) > 0))
                    // {
                    //     $checked = 'skip';
                    // }
                    // // if($checked != 'skip')
                    // // {
                    //     $str .= "<div class='form-check'> 
                    //                 <input type='checkbox' ".$checked." class='form-check-input' name='app_services[]' name='status' value=".$value->id." >
                    //                 <label class='form-check-label' for='status'>".$value->name."</label>
                    //             </div>";
                    // // }
                }
            }
            $this->JsonData['services'] = $str;
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
        } catch (Exception $e) {
            $this->JsonData['exception'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }

    public function getServicesOld(Request $request)
    {
        // dd($request->all());
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');
        try {
            $appointment_type_id = $request->appointment_type_id; //42
            $patient_id          = $request->patient_id; //5197
            $appointment_id      = $request->a_id;
            $str = '';
            // $getRecord = $this->AppointmentTypeHasExaminationsModel
            //              ->where('appoinment_id',$appointment_type_id)
            //              ->with(['assignedExamination'])
            //              ->wherenull('deleted_at')
            //              ->get();
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
                ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
                //->whereNotNull('examinations.description')
                //->where('examinations.show_as_recommended','1')
                ->get([
                    'examinations.id',
                    'examinations.name',
                    'examinations.url',
                    'examinations.description',
                    'examinations.document_name',
                    'examinations.document_path',
                    'examinations.document_status',
                    'examinations.status',
                    'examinations.created_at',
                    'examinations.show_as_recommended'
                ]);
            // dd($collections1);
            // Filter add for age base services
            $collections1 = $collections1->filter(function ($item) use ($patient_id, $appointment_type_id) {
                $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                $age_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();
                if (!empty($age_service) && $item->name != $app_type_name->name) {
                    $getPatientAge = $this->PatientsModel->find($patient_id);
                    if (!empty($getPatientAge)) {
                        $patient_age = $getPatientAge->age;
                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            return $item;
                        }
                    }
                } else {
                    return $item;
                }
            });
            //End
            $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
            // dd($exams_ids);
            $collections2 = $this->PatientsHasServiceReminderModel
                ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                ->where('patient_has_service_reminder.patient_id', $patient_id)
                //->where('patient_has_service_reminder.type','age')
                ->where('patient_has_service_reminder.status', 'activate')
                ->whereNotIn('examinations.id', $exams_ids)
                ->where('patient_has_service_reminder.reminder_status', 'Set')
                ->groupBy('patient_has_service_reminder.service_id')
                ->get([
                    'examinations.id',
                    'examinations.name',
                    'examinations.url',
                    'examinations.description',
                    'examinations.document_name',
                    'examinations.document_path',
                    'examinations.document_status',
                    'examinations.status',
                    'examinations.created_at',
                    'examinations.show_as_recommended'
                ]);
            // dd($collections2);                        
            $collections2 = $collections2->filter(function ($item) use ($patient_id) {
                $age_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();
                //log::info('Test-6');log::info($age_service); 
                if (!empty($age_service)) {
                    $getPatientAge = $this->PatientsModel->find($patient_id);
                    if (!empty($getPatientAge)) {
                        $patient_age = $getPatientAge->age;
                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            return $item;
                        }
                    }
                } else {
                    return $item;
                }
            });
            $getRecord = $collections1->merge($collections2);
            //log::info('Test-8');log::info($getRecord); 
            if ($appointment_id == 'undefined') {
                $appointment_id = '';
            }
            if (!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id)) {
                $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {
                    $app_type_name = $this->AppointmentHasExaminationsModel
                        ->where('appointment_id', $appointment_id)
                        ->where('patient_id', $patient_id)
                        ->where('examination_id', $item->id)
                        ->with(['assignedExamination'])
                        //->where('examination_id',$exam_id)
                        ->first();
                    if (!empty($app_type_name)) {
                        $item->checked = 1;
                        $item->sameName = 0;
                        return $item;
                    }
                    if (empty($item->description)) //When Discription is blank
                    {
                        $item->checked = 1;
                        $item->sameName = 0;
                        return $item;
                    }
                    return $item;
                });
            } else {
                $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {
                    $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                    if (!empty($app_type_name)) {
                        if ($item->name == $app_type_name->name) {
                            $item->checked = 1;
                            $item->sameName = 1;
                        } else if (empty($item->description)) {
                            $item->checked = 1;
                            $item->sameName = 0;
                        }
                        return $item;
                    }
                });
            }
            // dd($getRecord);
            if (!empty($getRecord) && sizeof($getRecord) > 0) {
                $str .= "<label class='theme-blue'>" . __('admin.TITLE_APPOINTMENT_SERVICES') . "</label>";
                foreach ($getRecord as $key => $value) {
                    ////Added by Shyam 29-12-21
                    $checked = '';
                    if (empty($appointment_id)) {
                        $getReminderDate = DB::table('patient_has_service_reminder')
                            ->where('patient_id', $patient_id)->where('service_id', $value->id)
                            ->where('status', 'activate')->where('reminder_status', 'Set')
                            ->whereNull('deleted_at')->orderBy('reminder_date', 'DESC')
                            ->pluck('reminder_date')->first();
                        //log::info('Test-11');log::info($getReminderDate);
                        if ($value['checked'] == 1 && $value['show_as_recommended'] != '1') {
                            $checked = 'checked';
                            if (!empty($getReminderDate) && $value['sameName'] != 1) {
                                $getData =  $this->AppointmentHasExaminationsModel
                                    ->leftjoin('appointment', 'appointment.id', '=', 'appointment_has_examinations.appointment_id')
                                    ->where('appointment_has_examinations.patient_id', $patient_id)
                                    ->where('appointment_has_examinations.examination_id', $value->id)
                                    ->where('appointment.patient_id', $patient_id)
                                    ->where('appointment.start_date', '<', $getReminderDate)
                                    ->get(['appointment.start_date', 'appointment.patient_id']);
                                //log::info('Test-11-1');log::info($getData);
                            }
                            if (empty(@$getData[0]->start_date) || strtotime(@$getData[0]->start_date) < strtotime(date('Y-m-d'))) {
                                //log::info('Test-11-2');log::info($value->name);
                                $str .= "<div class='form-check'> 
                                    <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                    <label class='form-check-label' for='status'>" . $value->name . "</label>
                                </div>";
                            }
                        }
                        // if($value['show_as_recommended'] == '1' && $checked = '')
                        else if ($value['show_as_recommended'] == '1' || $value['description'] != '') {
                            if (!empty($getReminderDate) && $value['sameName'] != 1) {
                                $getData =  $this->AppointmentHasExaminationsModel
                                    ->leftjoin('appointment', 'appointment.id', '=', 'appointment_has_examinations.appointment_id')
                                    ->where('appointment_has_examinations.patient_id', $patient_id)
                                    ->where('appointment_has_examinations.examination_id', $value->id)
                                    ->where('appointment.patient_id', $patient_id)
                                    ->where('appointment.start_date', '<', $getReminderDate)
                                    ->get(['appointment.start_date', 'appointment.patient_id']);
                                //log::info('Test-11-3');log::info($getData);
                            }
                            if (empty(@$getData[0]->start_date) || strtotime(@$getData[0]->start_date) < strtotime(date('Y-m-d'))) {
                                //log::info('Test-11-4');log::info($value->name."===".$value['sameName']);
                                $checked = '';
                                if ($value['sameName'] == 1) {
                                    $checked = 'checked';
                                }
                                $str .= "<div class='form-check'> 
                                        <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                        <label class='form-check-label' for='status'>" . $value->name . "</label>
                                    </div>";
                            }
                        }
                    } else {
                        if ($value['checked'] == 1) {
                            $checked = 'checked';
                        }
                        //log::info('Test-12');log::info($value->name.">>>".$checked);
                        $str .= "<div class='form-check'> 
                                <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                <label class='form-check-label' for='status'>" . $value->name . "</label>
                            </div>";
                    }
                    ////Commented by Shyam 29-12-21
                    // $checked ='';
                    // //Added by Shyam 21-12-21
                    // $checkDate = date('Y-m-d H:i:s', strtotime('-30 day', strtotime(date('Y-m-d H:i:s'))));
                    // $isExists = DB::table('appointment')
                    //             ->leftjoin('appointment_has_examinations as ae','ae.appointment_id','appointment.id')
                    //             ->where('appointment.patient_id',$patient_id)
                    //             ->where('appointment.start_date', '>', $checkDate)
                    //             ->where('appointment.appointment_type_id',$appointment_type_id)
                    //             ->where('ae.examination_id',$value->id)
                    //             ->get(['ae.id','ae.examination_id','appointment.start_date','appointment.patient_id']);
                    // // if($value['checked'] == 1)
                    // // if($value['checked'] == 1 && (empty($isExists) || sizeof($isExists) == 0)) //Added by Shyam 21-12-21
                    // if($value['checked'] == 1) //Added by Shyam 21-12-21
                    // {
                    //     $checked = 'checked';
                    // }
                    // if($value['show_as_recommended'] == '1' && (!empty($isExists) || sizeof($isExists) > 0))
                    // {
                    //     $checked = 'skip';
                    // }
                    // // if($checked != 'skip')
                    // // {
                    //     $str .= "<div class='form-check'> 
                    //                 <input type='checkbox' ".$checked." class='form-check-input' name='app_services[]' name='status' value=".$value->id." >
                    //                 <label class='form-check-label' for='status'>".$value->name."</label>
                    //             </div>";
                    // // }
                }
            }
            $this->JsonData['services'] = $str;
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
        } catch (Exception $e) {
            $this->JsonData['exception'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }

    public function getServicesNew(Request $request)
    {
        try {
            $appointment_type_id = 25; //41;29
            $patient_id          = 33890; //35053;
            $patient_id  = 35362;
            $appointment_id  = 46246;
            $appointment_id      = '';
            $str = '';
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
                ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
                ->get([
                    'examinations.id',
                    'examinations.name',
                    'examinations.url',
                    'examinations.description',
                    'examinations.document_name',
                    'examinations.document_path',
                    'examinations.document_status',
                    'examinations.status',
                    'examinations.created_at',
                    'examinations.show_as_recommended'
                ]);
            // dd($collections1);
            // Filter add for age base services
            $collections1 = $collections1->filter(function ($item) use ($patient_id, $appointment_type_id) {
                $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                $age_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();
                if (!empty($age_service) && $item->name != $app_type_name->name) {
                    $getPatientAge = $this->PatientsModel->find($patient_id);
                    if (!empty($getPatientAge)) {
                        $patient_age = $getPatientAge->age;
                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            return $item;
                        }
                    }
                } else {
                    return $item;
                }
            });
            //End
            $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
            // dd($exams_ids);
            $collections2 = $this->PatientsHasServiceReminderModel
                ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                ->where('patient_has_service_reminder.patient_id', $patient_id)
                //->where('patient_has_service_reminder.type','age')
                ->where('patient_has_service_reminder.status', 'activate')
                ->whereNotIn('examinations.id', $exams_ids)
                ->where('patient_has_service_reminder.reminder_status', 'Set')
                ->groupBy('patient_has_service_reminder.service_id')
                ->get([
                    'examinations.id',
                    'examinations.name',
                    'examinations.url',
                    'examinations.description',
                    'examinations.document_name',
                    'examinations.document_path',
                    'examinations.document_status',
                    'examinations.status',
                    'examinations.created_at',
                    'examinations.show_as_recommended'
                ]);
            ///echo "<pre>";print_r($collections2->toArray());
            // dd($collections2); 
            $collections2 = $collections2->filter(function ($item) use ($patient_id) {
                $age_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();
                //log::info('Test-6');log::info($age_service); 
                if (!empty($age_service)) {
                    $getPatientAge = $this->PatientsModel->find($patient_id);
                    if (!empty($getPatientAge)) {
                        $patient_age = $getPatientAge->age;
                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            return $item;
                        }
                    }
                } else {
                    return $item;
                }
            });
            $getRecord = $collections1->merge($collections2);
            //log::info('Test-8');log::info($getRecord); 
            if ($appointment_id == 'undefined') {
                $appointment_id = '';
            }
            if (!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id)) {
                $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {
                    $app_type_name = $this->AppointmentHasExaminationsModel
                        ->where('appointment_id', $appointment_id)
                        ->where('patient_id', $patient_id)
                        ->where('examination_id', $item->id)
                        ->with(['assignedExamination'])
                        //->where('examination_id',$exam_id)
                        ->first();
                    if (!empty($app_type_name)) {
                        $item->checked = 1;
                        $item->sameName = 0;
                        return $item;
                    }
                    if (empty($item->description)) //When Discription is blank
                    {
                        $item->checked = 1;
                        $item->sameName = 0;
                        return $item;
                    }
                    return $item;
                });
            } else {
                $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {
                    $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                    if (!empty($app_type_name)) {
                        if ($item->name == $app_type_name->name) {
                            $item->checked = 1;
                            $item->sameName = 1;
                        } else if (empty($item->description)) {
                            $item->checked = 1;
                            $item->sameName = 0;
                        }
                        return $item;
                    }
                });
            }
            // dd($getRecord);
            if (!empty($getRecord) && sizeof($getRecord) > 0) {
                $str .= "<label class='theme-blue'>" . __('admin.TITLE_APPOINTMENT_SERVICES') . "</label>";
                foreach ($getRecord as $key => $value) {
                    ////Added by Shyam 29-12-21
                    $checked = '';
                    if (empty($appointment_id)) {
                        $getReminderDate = DB::table('patient_has_service_reminder')
                            ->where('patient_id', $patient_id)->where('service_id', $value->id)
                            ->where('status', 'activate')->where('reminder_status', 'Set')
                            ->whereNull('deleted_at')->orderBy('reminder_date', 'DESC')
                            ->pluck('reminder_date')->first();
                        echo $value->id . "==>" . $value['checked'] . "====" . $value['show_as_recommended'] . "===><pre>";
                        print_r($getReminderDate);
                        echo "<br/>";
                        //log::info('Test-11');log::info($getReminderDate);
                        if ($value['checked'] == 1 && $value['show_as_recommended'] != '1') {
                            $checked = 'checked';
                            if (!empty($getReminderDate) && $value['sameName'] != 1) {
                                $getData =  $this->AppointmentHasExaminationsModel
                                    ->leftjoin('appointment', 'appointment.id', '=', 'appointment_has_examinations.appointment_id')
                                    ->where('appointment_has_examinations.patient_id', $patient_id)
                                    ->where('appointment_has_examinations.examination_id', $value->id)
                                    ->where('appointment.patient_id', $patient_id)
                                    ->where('appointment.start_date', '<', $getReminderDate)
                                    ->get(['appointment.start_date', 'appointment.patient_id']);
                            }
                            if (empty(@$getData[0]->start_date) || strtotime(@$getData[0]->start_date) < strtotime(date('Y-m-d'))) {
                                $str .= "<div class='form-check'> 
                                    <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                    <label class='form-check-label' for='status'>" . $value->name . "</label>
                                </div>";
                            }
                        } else if ($value['show_as_recommended'] == '1' || $value['description'] != '') {
                            if (!empty($getReminderDate) && $value['sameName'] != 1) {
                                $getData =  $this->AppointmentHasExaminationsModel
                                    ->leftjoin('appointment', 'appointment.id', '=', 'appointment_has_examinations.appointment_id')
                                    ->where('appointment_has_examinations.patient_id', $patient_id)
                                    ->where('appointment_has_examinations.examination_id', $value->id)
                                    ->where('appointment.patient_id', $patient_id)
                                    ->where('appointment.start_date', '<', $getReminderDate)
                                    ->get(['appointment.start_date', 'appointment.patient_id']);
                            }
                            if (empty(@$getData[0]->start_date) || strtotime(@$getData[0]->start_date) < strtotime(date('Y-m-d'))) {
                                $checked = '';
                                if ($value['sameName'] == 1) {
                                    $checked = 'checked';
                                }
                                $str .= "<div class='form-check'> 
                                        <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                        <label class='form-check-label' for='status'>" . $value->name . "</label>
                                    </div>";
                            }
                        }
                    } else {
                        if ($value['checked'] == 1) {
                            $checked = 'checked';
                        }
                        $str .= "<div class='form-check'> 
                                <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                <label class='form-check-label' for='status'>" . $value->name . "</label>
                            </div>";
                    }
                }
            }
            $this->JsonData['services'] = $str;
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
        } catch (Exception $e) {
            $this->JsonData['exception'] = $e->getMessage();
        }
        print_r($this->JsonData);
    }
    public function getServicesWeb(Request $request)
    {
        //dd("-----");
        // dd($patient_id,$appointment_id);
        //$patient_id = 2;
        //$appointment_id = 2;
        $patient_id  = 35362;
        $appointment_id  = 46274;
        $data = $finalDat = [];
        $getAppointment = $this->BaseModel->find($appointment_id);
        //dd($getAppointment);
        if (!empty($getAppointment)) {
            $appointment_type_id = $getAppointment->appointment_type_id;
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
                ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
                //->whereNotNull('examinations.description')
                //->where('examinations.show_as_recommended','1')
                ->get([
                    'examinations.id',
                    'examinations.name',
                    'examinations.description'
                ]);
            $collections1 = $collections1->filter(function ($item) use ($patient_id) {
                $age_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();

                if (!empty($age_service)) {
                    $getPatientAge = $this->PatientsModel
                        ->find($patient_id);

                    if (!empty($getPatientAge)) {
                        $patient_age = $getPatientAge->age;

                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            return $item;
                        }
                    }
                } else {
                    return $item;
                }
            });

            $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
            //DB::enableQueryLog();
            $today_date = '2023-07-20'; //date("Y-m-d");
            $collections2 = $this->PatientsHasServiceReminderModel
                ->select(DB::raw('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
                ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                ->where('patient_has_service_reminder.patient_id', $patient_id)
                //->where('patient_has_service_reminder.type','age')
                ->where('patient_has_service_reminder.status', 'activate')
                ->whereNotIn('examinations.id', $exams_ids)
                //->where('patient_has_service_reminder.reminder_status','Set')
                ->whereRaw("date(reminder_date) <= '" . $today_date . "'")
                ->groupBy('patient_has_service_reminder.service_id')
                ->get();
            //print_r(DB::getQueryLog());
            //echo "<pre>";print_r($collections2);//dd($collections2);
            $collections2 = $collections2->filter(function ($item) use ($patient_id, $today_date) {
                $age_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();

                if (!empty($age_service)) {
                    $getPatientAge = $this->PatientsModel
                        ->find($patient_id);

                    if (!empty($getPatientAge)) {
                        $patient_age = $getPatientAge->age;

                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            if ($item->reminder_status == 'executed') {
                                $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                    ->where('service_id', $item->id)
                                    ->where('patient_id', $patient_id)
                                    ->where('reminder_status', 'Set')
                                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                    ->first();
                                //echo "<pre>";print_r($checkServiceReminders);
                                if (empty($checkServiceReminders))
                                    return $item;
                            } else return $item;
                            //return $item;
                        }
                    }
                }
                // else
                // {
                //     if($item->reminder_status=='executed'){
                //         $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                //                         ->where('service_id',$item->id)
                //                         ->where('patient_id',$patient_id)
                //                         ->where('reminder_status','Set')
                //                         ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                //                         ->first();
                //         //echo "<pre>";print_r($checkServiceReminders);
                //         if(empty($checkServiceReminders))
                //             return $item;
                //     } 
                //     else return $item;
                // }               
            });
            $getrecord = $collections1->merge($collections2);

            if (!empty($getrecord) && sizeof($getrecord) > 0) {
                $cnt = 0;
                foreach ($getrecord as $key => $value) {
                    $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                    if (!empty($app_type_name)) {
                        if (ucfirst($value->name) == ucfirst($app_type_name->name)) {

                            $data[$key]['checked']   = 1;
                        } else if (empty($value->description)) {
                            $data[$key]['checked']   = 1;
                        } else {
                            $data[$key]['checked']   = 0;
                        }
                    }
                    $data[$key]['id']   = $value->id;
                    $data[$key]['name'] = ucfirst($value->name);
                    $cnt++;
                }
            }
        }
        //dd($data);
        echo "<pre>";
        print_r($data);
    }

    public function getServicesApp(Request $request)
    {
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $patient_id  = 35362;
        //$patientAge  = $request->patient_age;
        $patient_id  = 35362;
        $appointment_id  = 46274;

        $inputdata  = $request->all();
        try {
            $collection = collect([]);
            $getAppointmentRec = $this->AppointmentModel->find($appointment_id);

            if (!empty($getAppointmentRec)) {
                $all_exam_ids = $this->AppointmentHasExaminationsModel
                    ->select('examination_id')
                    ->where('appointment_id', $appointment_id)
                    ->get();

                $all_exams_ids  = array_unique(array_column(array_values($all_exam_ids->toArray()), 'examination_id'));
                //dump($all_exams_ids);              
                $collections1 = $this->AppointmentTypeHasExaminationsModel
                    ->where('appoinment_type_has_examinations.appoinment_id', $getAppointmentRec->appointment_type_id)
                    ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
                    ->whereNotIn('examinations.id', $all_exams_ids)
                    ->whereNotNull('examinations.description')
                    ->where('examinations.show_as_recommended', '1')
                    ->get([
                        'examinations.id',
                        'examinations.name',
                        'examinations.url',
                        'examinations.description',
                        'examinations.document_name',
                        'examinations.document_path',
                        'examinations.document_status',
                        'examinations.status',
                        'examinations.created_at'
                    ]);
                $collections1 = $collections1->filter(function ($item) use ($patient_id) {
                    $age_service =  $this->ChannelsRemindersSettingModel
                        ->where('service_id', $item->id)
                        ->where('activated_reminder', 'age')
                        ->first();

                    if (!empty($age_service)) {
                        $getPatientAge = $this->PatientsModel
                            ->find($patient_id);

                        if (!empty($getPatientAge)) {
                            $patient_age = $getPatientAge->age;

                            if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                return $item;
                            }
                        }
                    } else {
                        return $item;
                    }
                });
                //dd($collections1);
                $exams_ids    = array_unique(array_column(array_values($collections1->toArray()), 'id'));
                $new_exams_ids =   array_merge($exams_ids, $all_exams_ids);
                //dd($new_exams_ids);       
                //DB::enableQueryLog();
                $collections2 = $this->PatientsHasServiceReminderModel
                    ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                    //->whereNotNull('examinations.description')
                    ->where('patient_has_service_reminder.patient_id', $request->patient_id)
                    ->where('patient_has_service_reminder.type', 'age')
                    ->where('patient_has_service_reminder.status', 'activate')
                    // ->whereNotIn('examinations.id',$exams_ids) 
                    ->whereNotIn('examinations.id', $new_exams_ids)
                    //->where('examinations.show_as_recommended','1')
                    ->where('patient_has_service_reminder.reminder_status', 'Set')
                    ->groupBy('patient_has_service_reminder.service_id')
                    ->get([
                        'examinations.id',
                        'examinations.name',
                        'examinations.url',
                        'examinations.description',
                        'examinations.document_name',
                        'examinations.document_path',
                        'examinations.document_status',
                        'examinations.status',
                        'examinations.created_at'
                    ]);
                //print_r(DB::getQueryLog()); 
                //echo "<pre>";print_r($collections2);
                $collections2 = $collections2->filter(function ($item) use ($patient_id) {
                    $age_service =  $this->ChannelsRemindersSettingModel
                        ->where('service_id', $item->id)
                        ->where('activated_reminder', 'age')
                        ->first();

                    if (!empty($age_service)) {
                        $getPatientAge = $this->PatientsModel
                            ->find($patient_id);

                        if (!empty($getPatientAge)) {
                            $patient_age = $getPatientAge->age;

                            if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                return $item;
                            }
                        }
                    } else {
                        return $item;
                    }
                });
                $collections = $collections1->merge($collections2);

                $appointment_type_details = [];
                if (!empty($appointment_id)) {
                    $appointment_type_details = $this->AppointmentModel
                        ->join('appointment_types', 'appointment_types.id', '=', 'appointment.appointment_type_id')
                        ->where('appointment.id', '=', $appointment_id)
                        // ->where('appointment_types.recommend_exams','=','0')
                        ->get([
                            'appointment_types.id',
                            'appointment_types.name',
                            'appointment_types.recommend_exams'
                        ]);
                }

                if (!empty($collections) && ($collections->count() > 0)) {
                    $collections = $collections->map(function ($item) {
                        $doc_path = '';
                        // if (!empty($item->document_path) && is_file(storage_path().$item->document_path)) 
                        // {
                        //     $doc_path = url('/storage'.$item->document_path); 
                        // }
                        $new_doc_path = self::StorePath($item->document_path . '/');

                        if (!empty($item->document_path)) {
                            $doc_path = self::getFilePath($item->document_path);
                            //$doc_path = url('/storage'.$item->document_path); 
                        }
                        $item->document_path = $doc_path;

                        if (empty($item->description)) {
                            $item->description = '';
                        }
                        if (empty($item->document_name)) {
                            $item->document_name = '';
                        }

                        return $item;
                    });

                    if (!empty($collections) && ($collections->count() > 0)) {
                        foreach ($collections as $key => $value) {
                            $isExist = $this->EventTypeHasExaminationsModel
                                ->where('patient_id', $request->patient_id)
                                ->where('appoinment_id', $appointment_id)
                                ->where('service_id', $value['id'])
                                ->first();

                            if (empty($isExist)) {
                                $eventType = new $this->EventTypeHasExaminationsModel;
                            } else {
                                $eventType = $this->EventTypeHasExaminationsModel->find($isExist->id);
                            }

                            $eventType->patient_id    = $request->patient_id;
                            $eventType->appoinment_id = $appointment_id;
                            $eventType->service_id    = $value['id'];
                            $eventType->event_type    = 'smart_phone';
                            $eventType->status        = 'displayed';
                            $eventType->save();
                        }
                    }


                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data[0]['exams']  = $collections;
                    $data[0]['appointment_type']  = $appointment_type_details;

                    $status  = true;
                    $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                    self::_createLog('getProfileExaminations', array($data), 'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                } else {

                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            } else {
                $status     = false;
                $message = __('api.ERR_SOMETHING_WRONG');
            }
        } catch (\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                "error" => $e->getMessage(),
            ];
            self::_createLog('getProfileExaminations', $errors, 'error');
            $this->ActivityLogModel->addApiLog('SignupSendOtp', 'send otp for login', 'Get');
        }
        //echo "<pre>";print_r($data);
    }
    ////Added by Shyam 29-12-21
    public function getExtraServices(Request $request)
    {
        // dd($request->all());
        Log::info("=== getExtraServices function START ===");
        Log::info("Request data:", $request->all());
        
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');
        try {
            $birth_date = $request->birth_date;
            $appointment_type_id = $request->appointment_type_id;
            $str = '';
            
            Log::info("Parameters extracted:");
            Log::info("birth_date: " . $birth_date);
            Log::info("appointment_type_id: " . $appointment_type_id);
            Log::info("Executing collections1 query...");
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
                ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
                ->get(['examinations.id']);
            Log::info("collections1 query completed. Count: " . $collections1->count());
            Log::info("collections1 data:", $collections1->toArray());
            $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
            Log::info("Extracted exams_ids:", $exams_ids);
            
            Log::info("Executing getRecord query...");
            $getRecord = $this->ChannelsRemindersSettingModel
                ->join('examinations', 'examinations.id', 'preferred_channels_for_reminders_setting.service_id')
                ->where('preferred_channels_for_reminders_setting.type', 'service')
                ->where('preferred_channels_for_reminders_setting.activated_reminder', 'age')
                ->where('examinations.show_as_reminder', '1')
                ->whereNull('examinations.deleted_at') //added on 15-dec-23 for "testservice1" shown
                ->whereNotIn('examinations.id', $exams_ids)
                ->get([
                    'examinations.id',
                    'examinations.name',
                    'examinations.url',
                    'examinations.description',
                    'examinations.document_name',
                    'examinations.document_path',
                    'examinations.document_status',
                    'examinations.status',
                    'examinations.created_at',
                    'examinations.show_as_recommended'
                ]);
            Log::info("getRecord query completed. Count: " . $getRecord->count());
            Log::info("getRecord data:", $getRecord->toArray());
            Log::info("Starting getRecord filter...");
            $getRecord = $getRecord->filter(function ($item) use ($birth_date) {
                Log::info("Filtering item - ID: " . $item->id . ", Name: " . $item->name);
                
                $age_service =  $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();
                
                Log::info("Age service found for item " . $item->id . ": " . (!empty($age_service) ? 'Yes' : 'No'));
                
                if (!empty($age_service)) {
                    Log::info("Age service details - age_from: " . $age_service->age_from . ", age_to: " . $age_service->age_to);
                    
                    if (!empty($birth_date)) {
                        $patient_age = (date('Y') - date('Y', strtotime($birth_date)));
                        Log::info("Patient age calculated: " . $patient_age . " (birth_date: " . $birth_date . ")");
                        
                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            Log::info("Item " . $item->id . " passed age filter - INCLUDED");
                            return $item;
                        } else {
                            Log::info("Item " . $item->id . " failed age filter - EXCLUDED (age " . $patient_age . " not in range " . $age_service->age_from . "-" . $age_service->age_to . ")");
                        }
                    } else {
                        Log::info("Birth date is empty for item " . $item->id . " - EXCLUDED");
                    }
                } else {
                    Log::info("No age service for item " . $item->id . " - INCLUDED (no age restriction)");
                    return $item;
                }
            });
            Log::info("getRecord filter completed. Count: " . $getRecord->count());
            
            Log::info("Starting getRecord map...");
            $getRecord = $getRecord->map(function ($item) use ($appointment_type_id) {
                Log::info("Mapping item - ID: " . $item->id . ", Name: " . $item->name);
                
                $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                Log::info("Appointment type found: " . (!empty($app_type_name) ? $app_type_name->name : 'Not found'));
                
                if (!empty($app_type_name)) {
                    if ($item->name == $app_type_name->name) {
                        $item->checked = 1;
                        Log::info("Item " . $item->id . " name matches appointment type - CHECKED");
                    } else if (empty($item->description)) {
                        $item->checked = 1;
                        Log::info("Item " . $item->id . " has empty description - CHECKED");
                    } else {
                        Log::info("Item " . $item->id . " - NOT CHECKED");
                    }
                    return $item;
                } else {
                    Log::info("Appointment type not found for item " . $item->id . " - returning null");
                    return null;
                }
            });
            Log::info("getRecord map completed. Count: " . $getRecord->count());

            //added by vijay 20/3/2024
            Log::info("Getting appointment non-services IDs...");
            $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($appointment_type_id);
            Log::info("Non-services IDs:", $getAppointmentNonServciesIds);

            // 
            Log::info("Final getRecord check - count: " . sizeof($getRecord));
            if (!empty($getRecord) && sizeof($getRecord) > 0) {
                Log::info("Starting services loop. Total records to process: " . $getRecord->count());
                foreach ($getRecord as $key => $value) {
                    Log::info("Processing service ID: " . $value->id . ", Name: " . $value->name);
                    // condition added by vijay 20/3/2024
                    if (!in_array($value->id, $getAppointmentNonServciesIds)) {
                        Log::info("Service " . $value->id . " not in non-services list - PROCESSING");
                        $checked = '';
                        if ($value['checked'] == 1) {
                            $checked = 'checked';
                            Log::info("Service " . $value->id . " will be checked");
                        } else {
                            Log::info("Service " . $value->id . " will NOT be checked");
                        }
                        $str .= "<div class='form-check'>
                                <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                                <label class='form-check-label' for='status'>" . $value->name . "</label>
                            </div>";
                        Log::info("Added service to string: " . $value->name . " (ID: " . $value->id . ")");
                    } else {
                        Log::info("Service " . $value->id . " is in non-services list - SKIPPED");
                    }
                }
            }
            $this->JsonData['msg'] = '';
            $this->JsonData['services'] = $str;
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
        } catch (Exception $e) {
            Log::info("Exception in getExtraServices function:", ['message' => $e->getMessage()]);
            $this->JsonData['exception'] = $e->getMessage();
        }
        Log::info("getExtraServices function completed. Response:", $this->JsonData);
        return response()->json($this->JsonData);
    }
    ////Added by Shyam 29-12-21

    public function updateReminder(Request $request)
    {
        Log::info(' in updateReminder function appointment controller');

        //dd($request->all())
        $appoitment_id = $request->appoitment_id;
        $patient_id = $request->patient_id;

        Log::info($request->all());

        if (!empty($request->checkbox) && count($request->checkbox)) {
            foreach ($request->checkbox as $key => $value) {
                // $request->checkup_period_controls[$key];
                // if($value != 0)
                if ($request->checkup_period_controls[$key] != 0) {
                    //dd($key);
                    $checkbox_status = isset($request->checkbox[$key]) ? 1 : 0;

                    $is_exist = $this->PatientsHasServiceControlReminderModel->where(['patient_id' => $patient_id, 'appointment_id' => $appoitment_id, 'service_id' => $key])->first();

                    Log::info($is_exist);

                    if (!empty($is_exist)) {
                        Log::info(" in is exists ..");

                        $tmp = [];
                        // $tmp['control_interval'] =  $value;
                        $tmp['control_interval'] =  $request->checkup_period_controls[$key];
                        $tmp['control_frequency'] =  $request->checkup_period_frequency_type[$key];
                        $tmp['status'] = $checkbox_status;
                        $this->PatientsHasServiceControlReminderModel->where(['patient_id' => $patient_id, 'appointment_id' => $appoitment_id, 'service_id' => $key])->update($tmp);

                        $getPatientReminder = $this->PatientsHasServiceControlReminderModel
                            ->where('patient_id', $patient_id)
                            ->where('appointment_id', $appoitment_id)
                            ->where('service_id', $key)
                            ->first();
                        //dd($getPatientReminder);
                        if (!empty($getPatientReminder)) {
                            $tmpDismissal['dismissal_flag'] = '0';
                            $updateDismissal = $this->PatientsHasDismissalModel
                                ->where('fk_patient_id', $patient_id)
                                ->where('appointment_id', $appoitment_id)
                                ->where('fk_dismissal_id', $getPatientReminder->id)
                                ->first();
                            if (!empty($updateDismissal)) {
                                $updateDismissalrec = $this->PatientsHasDismissalModel->find($updateDismissal->id);
                                $updateDismissalrec->dismissal_flag = '0';
                                $updateDismissalrec->save();
                            }
                        }
                    } else {

                        Log::info(" in else is exists ..");

                        $tmp = [];
                        // $tmp['appointment_id'] =  $appoitment_id;
                        // $tmp['patient_id'] =  $patient_id;
                        // $tmp['service_id'] =  $key;
                        // $tmp['status'] =  $checkbox_status;
                        // $tmp['control_interval'] =  $value;
                        // $tmp['control_frequency'] =   $request->checkup_period_frequency_type[$key];
                        // $this->PatientsHasServiceControlReminderModel->insert($tmp);

                        $PatientsHasReminder = new $this->PatientsHasServiceControlReminderModel;
                        $PatientsHasReminder->appointment_id =  $appoitment_id;
                        $PatientsHasReminder->patient_id     =  $patient_id;
                        $PatientsHasReminder->service_id      =  $key;
                        $PatientsHasReminder->status =  $checkbox_status;
                        $PatientsHasReminder->control_interval =  $request->checkup_period_controls[$key];
                        $PatientsHasReminder->control_frequency = $request->checkup_period_frequency_type[$key];
                        $PatientsHasReminder->created_at = date('Y-m-d H:i:s');

                        if ($PatientsHasReminder->save()) {
                            $DismissalModel = new $this->PatientsHasDismissalModel;
                            $DismissalModel->fk_patient_id   = $patient_id;
                            $DismissalModel->fk_dismissal_id = $PatientsHasReminder->id;
                            $DismissalModel->appointment_id  = $appoitment_id;
                            $DismissalModel->type            = 'reminder';
                            $DismissalModel->status          = '1';
                            $DismissalModel->dismissal_flag  = '0';
                            $DismissalModel->created_at      = date('Y-m-d');
                            $DismissalModel->created_at = date('Y-m-d H:i:s');
                            $DismissalModel->save();
                        }
                    }
                    // $is_service_has_control_reminder = $this->PatientsHasServiceControlReminderModel->where('type' , 'global')->first();
                    $is_exist = $this->PatientsHasServiceReminderModel->where(['patient_id' => $patient_id, 'appointment_id' => $appoitment_id, 'service_id' => $key])->orderby('id', 'desc')->get();

                    Log::info(" is exists patient has service reminder..");
                    Log::info($is_exist);

                    if (!empty($is_exist) && count($is_exist) > 0) {
                        Log::info(" in is exists of patient has service reminder..");
                        $this->PatientHasReminder->where('service_reminder_id', $is_exist[0]->id)->update(['deleted_at' => date('y-m-d H:i:s')]);
                        $this->PatientsHasServiceReminderModel->where(['patient_id' => $patient_id, 'appointment_id' => $appoitment_id, 'service_id' => $key])->update(['deleted_at' => date('y-m-d H:i:s')]);
                    }
                    //Added by Shyam 28-12-21
                    $is_service_has_reminder = $this->ChannelsRemindersSettingModel
                        ->where('type', 'service')
                        ->where('service_id', $key)
                        ->whereIn('activated_reminder', ['', 'checkup'])
                        ->first();
                    if (empty($is_service_has_reminder)) {
                        $is_service_has_reminder = $this->ChannelsRemindersSettingModel
                            ->where('type', 'global')->first();
                    }
                    //Commented by Shyam 28-12-21
                    // $is_service_has_reminder =  $this->ChannelsRemindersSettingModel
                    // ->where('type' , 'global')->first();

                    $is_doctor_set_reminder = $this->PatientsHasServiceControlReminderModel->where(
                        [
                            'patient_id' => $patient_id,
                            'appointment_id' => $appoitment_id,
                            'service_id' => $key,
                            'status' => '1',
                        ]
                    )->first();

                    if (!empty($is_doctor_set_reminder)) {

                        //Log::info(" is is_doctor_set_reminder..");


                        $is_service_has_reminder->checkup_period_controls =  $is_doctor_set_reminder->control_interval;
                        $is_service_has_reminder->checkup_period_frequency_type =  $is_doctor_set_reminder->control_frequency;
                    }

                    if (!empty($is_service_has_reminder)) {
                        $start_date = $this->BaseModel->where('id', $appoitment_id)->pluck('start_date')->first();
                        $start_date = date('Y-m-d', strtotime($start_date)) . " " . $is_service_has_reminder->notify_time . ":00";
                        // dump($key,$patient_id,$appoitment_id,$start_date);
                        $this->_controlReminder($is_service_has_reminder, $patient_id, $appoitment_id, $start_date, $key);
                    }

                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']      = __('admin.TITLE_REMINDER_UPDATE');
                    $this->JsonData['flag']     = 1;
                } else {
                    if ($request->checkup_period_controls[$key] == 0) {
                        $this->PatientsHasServiceControlReminderModel->where(['patient_id' => $patient_id, 'appointment_id' => $appoitment_id, 'service_id' => $key])->Delete();
                    }

                    // $this->JsonData['status']   = __('admin.ERR_SOMETHING_WRONG');
                    // $this->JsonData['msg']      = __('admin.TITLE_REMINDER_UPDATE');
                    // $this->JsonData['flag']     = 0;
                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']      = __('admin.TITLE_REMINDER_UPDATE');
                    $this->JsonData['flag']     = 1;
                }
            }
        } else {
            $this->JsonData['status']   = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['msg']      = __('admin.TITLE_REMINDER_UPDATE');
            $this->JsonData['flag']     = 0;
        }

        return response()->json($this->JsonData);
    }
    // function test()
    // {
    //     //dd("--->");
    //     header('Content-disposition: inline');
    //     header('Content-type: application/msword'); // not sure if this is the correct MIME type
    //     readfile(url('/storage/app/public/Puregyn New Features Estimate.xlsx'));
    //     exit;
    // }
    public function updateAppoitmentStatus(Request $request)
    {
        Log::info('in updateAppoitmentStatus..=====>');
        Log::info($request->all());

        $appoitment_id = $request->appoitment_id;
        $this->BaseModel->where('id', $appoitment_id)->update(['appointment_status' => 'Fertig']);
        $user = Auth::user()->id;
        $this->ActivityLogModel->addLog($this->ModuleTitle, 'dissmisal the appointment from doctors dashboard', 'Doctor Dashabord Dissmisal', $appoitment_id, $user);


        //take last appointment entry here to delete the reminder for the service which set as control reminder added on 27-nov-23

        /* $getCurrentAppointment =  DB::table('appointment')->where('id',$appoitment_id)->where('appointment_status','Fertig')->orderBy('id','desc')->first();

        Log::info('getCurrentAppointment..=====>');
        //Log::info($getCurrentAppointment);

       // dump($getCurrentAppointment);

        $getAppoitments = DB::table('appointment')->where('id','!=',$appoitment_id)->where('patient_id',$getCurrentAppointment->patient_id)->get();

        Log::info('getAppoitments..=====>');
       // Log::info($getAppoitments);

        if(isset($getAppoitments) && !empty($getAppoitments) && isset($getCurrentAppointment))
        {
             Log::info(' in getAppoitments..=====>');

            $getPreviousAppointment = DB::table('appointment')->where('id','!=',$appoitment_id)->where('patient_id',$getCurrentAppointment->patient_id)->where('appointment_status','Fertig')->orderBy('id','desc')->first();
            //dump($getPreviousAppointment);

            Log::info('getPreviousAppointment..=====>');
           // Log::info($getPreviousAppointment);

            if(isset($getPreviousAppointment))
            {
                 Log::info('in getPreviousAppointment..=====>');

                if(isset($getCurrentAppointment) && !empty($getCurrentAppointment))
                {
                    Log::info('in getCurrentAppointment..=====>');

                    //Check for current appointment control service set or not and which server

                    $serviceId = DB::table('patient_has_service_reminder')
                                    ->where('appointment_id',$getCurrentAppointment->id) 
                                    ->where('patient_id',$getCurrentAppointment->patient_id)
                                    ->where('type','control')
                                    ->whereNull('deleted_at')
                                    ->select('service_id')
                                    ->orderBy('id','desc')
                                    ->get();

                        Log::info('after service id .=====>');  
                       // Log::info($serviceId);    

                       // dump($serviceId);
         

                        if(isset($serviceId) && !empty($serviceId))
                        {
                             Log::info('in after service id .=====>');  

                                $serviceid_holder = [];
                                if(!empty($serviceId))
                                {
                                    foreach($serviceId as $id1=>$value_id1)
                                    { 
                                        $serviceid_holder[] = $value_id1->service_id;
                                    }                        
                                }//if not empty ids         


                              // dump($serviceid_holder);   

                            if(isset($serviceid_holder))
                            {
                               //Get reminder entry for above checkup service id and delete it for previous appointemnt
                               $ids = DB::table('patient_has_service_reminder')
                                ->where('type','control')
                                ->whereNull('deleted_at')
                               // ->where('service_id',$serviceId->service_id) //commented for single service
                                ->whereIn('service_id',$serviceid_holder)
                                ->where('appointment_id',$getPreviousAppointment->id) 
                                ->where('patient_id',$getPreviousAppointment->patient_id)
                                ->select('id')
                                ->get();    

                              //  dump($ids);       

                               Log::info('after ids====>');      
                               Log::info($ids);

                                if(isset($ids) && !empty($ids))
                                {
                                    $id_holder = [];
                                    if(!empty($ids))
                                    {
                                        foreach($ids as $id=>$value_id)
                                        { 
                                            $id_holder[] = $value_id->id;
                                        }                        
                                    }//if not empty ids         

                                    Log::info('id holder====>');      
                                    Log::info($id_holder);   


                                    // dump($id_holder);    

                                    DB::table('patient_has_service_reminder')
                                                ->where('type','control')
                                                ->whereNull('deleted_at')
                                               // ->where('service_id',$serviceId->service_id)
                                                ->whereIn('service_id',$serviceid_holder)  //commented for single service
                                                ->where('appointment_id',$getPreviousAppointment->id) 
                                                ->where('patient_id',$getPreviousAppointment->patient_id)
                                                ->whereNull('deleted_at')
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s')]);                   


                                    $reactivateReminder =  DB::table('patient_has_reminder')
                                                ->whereIn('service_reminder_id',$id_holder)
                                                ->update(['deleted_at'=>date('Y-m-d H:i:s')]); 
                                }//isset ids   

                            }//if isset serviceid_holder   
                                
                        }//serviceId                
                      
                }//getCurrentAppointment

            }//if isset getPreviousAppointment

        }//getAppoitments
        */

        //take previous entry for service and patient which has current appointment check up reminder to delete the reminder for the service which set as control reminder added on 6-dec-23

        $getCurrentAppointment =  DB::table('appointment')->where('id', $appoitment_id)->where('appointment_status', 'Fertig')->orderBy('id', 'desc')->first();

        Log::info('getCurrentAppointment..=====>');
        //Log::info($getCurrentAppointment);

        //dump($getCurrentAppointment);

        $getAppoitments = DB::table('appointment')->where('id', '!=', $appoitment_id)->where('patient_id', $getCurrentAppointment->patient_id)->get();

        Log::info('getAppoitments..=====>');
        // Log::info($getAppoitments);

        // dump($getAppoitments);

        if (isset($getAppoitments) && !empty($getAppoitments) && isset($getCurrentAppointment)) {
            Log::info(' in getAppoitments..=====>');
            Log::info('in getPreviousAppointment..=====>');

            if (isset($getCurrentAppointment) && !empty($getCurrentAppointment)) {
                Log::info('in getCurrentAppointment..=====>');

                //Check for current appointment control service set or not and which server

                $serviceId = DB::table('patient_has_service_reminder')
                    ->where('appointment_id', $getCurrentAppointment->id)
                    ->where('patient_id', $getCurrentAppointment->patient_id)
                    ->where('type', 'control')
                    ->whereNull('deleted_at')
                    ->select('service_id')
                    ->orderBy('id', 'desc')
                    ->get();

                Log::info('after service id .=====>');
                Log::info($serviceId);

                //dump($serviceId);


                if (isset($serviceId) && !empty($serviceId)) {
                    Log::info('in after service id .=====>');

                    $serviceid_holder = [];
                    if (!empty($serviceId)) {
                        foreach ($serviceId as $id1 => $value_id1) {
                            $serviceid_holder[] = $value_id1->service_id;
                        }
                    } //if not empty ids         


                    //dump($serviceid_holder);   

                    if (isset($serviceid_holder)) {
                        //Get reminder entry for above checkup service id and delete it for previous appointemnt
                        $ids = DB::table('patient_has_service_reminder')
                            ->where('type', 'control')
                            ->whereNull('deleted_at')
                            // ->where('service_id',$serviceId->service_id) //commented for single service
                            ->whereIn('service_id', $serviceid_holder)
                            //->where('appointment_id',$getPreviousAppointment->id) 
                            // ->where('patient_id',$getPreviousAppointment->patient_id)
                            ->where('patient_id', $getCurrentAppointment->patient_id)
                            ->where('appointment_id', '!=', $getCurrentAppointment->id)
                            ->where('appointment_id', '!=', 0)
                            ->select('id')
                            ->get();

                        // dump($ids);       

                        Log::info('after ids====>');
                        Log::info($ids);

                        //  dump($ids);   

                        if (isset($ids) && !empty($ids)) {
                            $id_holder = [];
                            if (!empty($ids)) {
                                foreach ($ids as $id => $value_id) {
                                    $id_holder[] = $value_id->id;
                                }
                            } //if not empty ids         

                            Log::info('id holder====>');
                            Log::info($id_holder);
                            // dump($id_holder);   

                            // dump($id_holder);    

                            DB::table('patient_has_service_reminder')
                                ->where('type', 'control')
                                ->whereNull('deleted_at')
                                ->whereIn('service_id', $serviceid_holder)  //commented for single service
                                //->where('appointment_id',$getPreviousAppointment->id) 
                                //->where('patient_id',$getPreviousAppointment->patient_id)
                                ->where('patient_id', $getCurrentAppointment->patient_id)
                                ->where('appointment_id', '!=', $getCurrentAppointment->id)
                                ->where('appointment_id', '!=', 0)
                                ->whereNull('deleted_at')
                                ->update(['deleted_at' => date('Y-m-d H:i:s')]);


                            $reactivateReminder =  DB::table('patient_has_reminder')
                                ->whereIn('service_reminder_id', $id_holder)
                                ->update(['deleted_at' => date('Y-m-d H:i:s')]);
                        } //isset ids   

                    } //if isset serviceid_holder   

                } //serviceId      


                // Start: Get default service for this appointment and delete services added on 15-apr-24
                $appointmentTypeId = $getCurrentAppointment->appointment_type_id;

                // $getAppType = $this->AppointmentTypesModel->where('id', $appointmentTypeId)->first(); //commented on 13-apr-26

                $getAppType = $this->AppointmentTypesModel->where('id', $appointmentTypeId)->withTrashed()->first(); //changed on 13-apr-26

                //dump($getAppType);
                Log::info("getAppType===>");
                // Log::info($getAppType);

                if (isset($getAppType) && !empty($getAppType)) {
                    $getAppTypeName = $getAppType->name;

                    // $examinationName = $this->ExaminationsModel->where('name', $getAppTypeName)->where('default_service', 1)->first(); //commented on 13-apr-26

                    $examinationName = $this->ExaminationsModel->where('name', $getAppTypeName)->where('default_service', 1)->withTrashed()->first(); //changed on 13-apr-26

                    // dump($examinationName);

                    Log::info("examinationName===>");
                    // Log::info($examinationName);
                    if (isset($examinationName) && !empty($examinationName)) {
                        Log::info('in examinationName ===>');
                        $defaultServiceId = $examinationName->id;
                        // Check if current appointment has default service booked
                        $isBooked = $this->AppointmentHasExaminationsModel
                            ->where('appointment_id', $appoitment_id)
                            ->where('patient_id', $getCurrentAppointment->patient_id)
                            ->where('examination_id', $defaultServiceId)
                            ->first();
                        Log::info("isBooked===>");
                        //Log::info($isBooked);     
                        if (isset($isBooked) && !empty($isBooked)) {
                            Log::info("in isBooked===>");
                            // Check if the doctor has set a reminder for this service
                            $isDoctorSetReminder = db::table('patient_has_service_control_reminder_setting')
                                ->where([
                                    'patient_id' => $getCurrentAppointment->patient_id,
                                    'appointment_id' => $appoitment_id,
                                    'service_id' => $defaultServiceId,
                                    'status' => '1',
                                ])->first();


                            // Log::info($isDoctorSetReminder);       

                            if (!$isDoctorSetReminder) {

                                Log::info("in isDoctorSetReminder===>");

                                // Get reminder IDs for the service
                                $reminderIds = DB::table('patient_has_service_reminder')
                                    // ->whereIn('type', ['control', 'general'])
                                    ->where('type', 'control')
                                    ->whereNull('deleted_at')
                                    ->where('service_id', $defaultServiceId)
                                    ->where('patient_id', $getCurrentAppointment->patient_id)
                                    ->where('appointment_id', '!=', $getCurrentAppointment->id)
                                    ->where('appointment_id', '!=', 0)
                                    ->pluck('id')
                                    ->toArray();
                                Log::info("in reminderIds===>");
                                //Log::info($reminderIds);    

                                if (!empty($reminderIds)) {
                                    // Soft delete service reminders
                                    DB::table('patient_has_service_reminder')
                                        ->whereIn('id', $reminderIds)
                                        ->update(['deleted_at' => now()]);
                                    // Soft delete related reminders
                                    DB::table('patient_has_reminder')
                                        ->whereIn('service_reminder_id', $reminderIds)
                                        ->update(['deleted_at' => now()]);
                                }
                            } //if not isDoctorSetReminder
                        } //if isBooked
                    } //if examinationName
                } //if getAppType

                // End: Remove default service control reminder added on 15-apr-24


                /***start***get all services of appointment**in booking and delete those reminders for prev app*on 7-june-24****/

                $getAppointmentServices = $this->AppointmentHasExaminationsModel
                    ->where('appointment_id', $getCurrentAppointment->id)
                    ->where('patient_id', $getCurrentAppointment->patient_id)
                    ->get();

                if (isset($getAppointmentServices) && !empty($getAppointmentServices)) {
                    foreach ($getAppointmentServices as $k => $v) {
                        $serviceId = $v->examination_id;

                        $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                            [
                                'service_id' => $v->examination_id,
                            ]
                        )->first();
                        if (isset($is_service_has_reminder) && !empty($is_service_has_reminder)) {
                            $default_reminder = $is_service_has_reminder->activated_reminder;

                            if ($default_reminder == 'checkup') {
                                // Get reminder IDs for checkup service
                                $reminderIds = DB::table('patient_has_service_reminder')
                                    ->where('type', 'control')
                                    ->whereNull('deleted_at')
                                    ->where('service_id', $v->examination_id)
                                    ->where('patient_id', $getCurrentAppointment->patient_id)
                                    ->where('appointment_id', '!=', $getCurrentAppointment->id)
                                    ->where('appointment_id', '!=', 0)
                                    ->pluck('id')
                                    ->toArray();
                                Log::info("in reminderIds===>");
                                Log::info($reminderIds);

                                if (!empty($reminderIds)) {
                                    // Soft delete service reminders
                                    DB::table('patient_has_service_reminder')
                                        ->whereIn('id', $reminderIds)
                                        ->update(['deleted_at' => date('Y-m-d H:i:s')]);
                                    // Soft delete related reminders
                                    DB::table('patient_has_reminder')
                                        ->whereIn('service_reminder_id', $reminderIds)
                                        ->update(['deleted_at' => date('Y-m-d H:i:s')]);
                                } //if reminderIds
                            } //if checkup


                        } //if isset is_service_has_reminder

                        //Delete reminders of previous appointment for this services

                        // Start Get reminder IDs for the service booked which are control or general type added on 9-march-26
                        $controlGeneralReminderIds = DB::table('patient_has_service_reminder')
                            ->whereIn('type', ['control', 'general'])
                            //->where('type', 'control')
                            ->whereNull('deleted_at')
                            ->where('service_id', $serviceId)
                            ->where('patient_id', $getCurrentAppointment->patient_id)
                            ->where('appointment_id', '!=', $getCurrentAppointment->id)
                            ->where('appointment_id', '!=', 0)
                            ->pluck('id')
                            ->toArray();
                        Log::info("in controlGeneralReminderIds===>");
                        // Log::info($reminderIds);    

                        if (!empty($controlGeneralReminderIds)) {
                            // Soft delete service reminders
                            DB::table('patient_has_service_reminder')
                                ->whereIn('id', $controlGeneralReminderIds)
                                ->update(['deleted_at' => now()]);
                            // Soft delete related reminders
                            DB::table('patient_has_reminder')
                                ->whereIn('service_reminder_id', $controlGeneralReminderIds)
                                ->update(['deleted_at' => now()]);
                        }//if reminderIds

                        //end 9-march-26




                    } //foreach
                } //if             
                /****end***get all services of appointment**in booking and delete those reminders for prev app*on 7-june-24*****/
            } //getCurrentAppointment


        } //getAppoitments


    } //updateappointementstatus


    public function _controlReminder($is_service_has_reminder, $patient_id, $appointment_id, $start_date, $service_id)
    {
        Log::info('in _controlReminder');
        Log::info($patient_id);
        Log::info($appointment_id);
        Log::info($service_id);
        Log::info($start_date);

        Log::info('in checkup_period_controls');
        Log::info($is_service_has_reminder->checkup_period_controls);


        Log::info('in checkup_period_frequency_type');
        Log::info($is_service_has_reminder->checkup_period_frequency_type);


        Log::info("is_service_has_reminder====>");
        Log::info($is_service_has_reminder);

        $reminder_array = [];
        // 1st reminder
        $start_date = $start_date;




        // Log::info(json_encode($is_service_has_reminder)."=".$appointment_id."=".$start_date."=".$patient_id."=".$service_id);

        $value1_days = $this->_getDate($start_date, $is_service_has_reminder->checkup_period_controls, $is_service_has_reminder->checkup_period_frequency_type);

        Log::info('in value1_days');
        Log::info($value1_days);

        //commented below code on 3-dec-25
        /*if ($is_service_has_reminder->checkup_period_frequency_type == 'month' || $is_service_has_reminder->checkup_period_frequency_type == 'year') {
            if ($is_service_has_reminder->checkup_period_frequency_type == 'month') {
                $months = (int)$is_service_has_reminder->checkup_period_controls;
                $period_date = date('Y-m-d H:i:s', strtotime("+" . $months . " months", strtotime($start_date)));
            }
            if ($is_service_has_reminder->checkup_period_frequency_type == 'year') {
                $months = 12 * (int)$is_service_has_reminder->checkup_period_controls;
                $period_date = date('Y-m-d H:i:s', strtotime("+" . $months . " months", strtotime($start_date)));
            }
        } else {
            $period_date = Date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +" . (int)$value1_days . " day"));
        }*/

        //added above condition code here on 3-dec-25
        $period_date = Date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +" . (int)$value1_days . " day"));




        Log::info('in period_date');
        Log::info($period_date);

        $value3_days = $this->_getDate($period_date, $is_service_has_reminder->checkup_first_frequency, $is_service_has_reminder->checkup_first_frequency_type);

        Log::info('in value3_days');
        Log::info($value3_days);

        // $first_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($period_date)) . " -".(int)$value3_days." day"));
        //dd($start_date,$value1_days,$period_date,$value3_days);
        $first_reminder = $this->_filterWeekendAndHoiliday($period_date, $value3_days, $is_service_has_reminder->holiday_reminder, 'minus');

        Log::info('in first_reminder');
        Log::info($first_reminder);

        $reminder_array[] = $first_reminder;
        for ($i = 0; $i < ($is_service_has_reminder->checkup_number_of_interval - 1); $i++) {
            $value4_days = $this->_getDate($period_date, $is_service_has_reminder->checkup_time_interval, $is_service_has_reminder->checkup_time_interval_frequency_type);
            // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));

            $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array), $value4_days, $is_service_has_reminder->holiday_reminder, 'plus');

            if ($third_reminder !=  $first_reminder) {
                $reminder_array[] = $third_reminder;
            }
        }
        sort($reminder_array);
        //ddd($reminder_array);
        //Added by swati 12-May-23===================================
        $firstReminderdate =  DB::table('patient_has_service_reminder')
            ->where('patient_id', $patient_id)
            ->where('service_id', $service_id)
            ->where('appointment_id', $appointment_id)
            ->first();
        if (!empty($firstReminderdate))
            $first_remidner_date = $firstReminderdate->reminder_date;
        else $first_remidner_date = $start_date;


        Log::info("first_remidner_date====>");
        Log::info($first_remidner_date);


        $endCycleDyas = $this->_getDate(($first_remidner_date), $is_service_has_reminder->checkup_end_cycle, $is_service_has_reminder->checkup_end_cycle_frequency_type);

        Log::info("endCycleDyas====>");
        Log::info($endCycleDyas);


        $agePeriodDays = $this->_getDate(($first_remidner_date), $is_service_has_reminder->checkup_period_controls, $is_service_has_reminder->checkup_period_frequency_type);

        Log::info("agePeriodDays====>");
         Log::info($agePeriodDays);

        $periodOneminusthird = ($agePeriodDays - $value3_days);

        Log::info("periodOneminusthird====>");
        Log::info($periodOneminusthird);


        $finalDays = ($endCycleDyas + $periodOneminusthird);

        Log::info("finalDays====>");
        Log::info($finalDays);


        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date), $finalDays, $is_service_has_reminder->holiday_reminder, 'plus');

        Log::info("endcycle_date====>");
         Log::info($endcycle_date);


        // log::info("endCycleDyas>>".$endCycleDyas.">>agePeriodDays>>".$agePeriodDays.">>value3_days>>".$value3_days.">>periodOneminusthird>>".$periodOneminusthird.">>endcycle_date>>".$endcycle_date);


        $reminder_id = 0;
        if (!empty($reminder_array) && count($reminder_array) > 0) {
            for ($i = 0; $i < count($reminder_array); $i++) {
                $reminder_tmp = [];
                $reminder_tmp['patient_id'] = $patient_id;
                $reminder_tmp['appointment_id'] = $appointment_id;
                $reminder_tmp['service_id'] = $service_id;
                $reminder_tmp['reminder_date'] = $reminder_array[$i];
                //Added on 14-Aug-2023=====
                $reminder_tmp['reminder_status'] = 'Set';
                // if($endCycleDyas>0){
                //     if($date1>=$date2) $reminder_tmp['reminder_status']='ignore';
                //     else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                // }
                $date1 = new DateTime($reminder_array[$i]);
                $date2 = new DateTime($endcycle_date);
                $date_today = new DateTime();
                $reminder_tmp['reminder_status'] = 'Set';

                //  Log::info("reminder_array====>");
                // Log::info($reminder_array[$i]);

                // Log::info("endCycleDyas====>");
                // Log::info($endCycleDyas);


                // dump($date1);
                // dump($date2);

                if ($endCycleDyas > 0) {
                    // Log::info("in endCycleDyas====>");
                    if ($date1 >= $date2) {

                        // Log::info("date1 is greater than date 2====>");    
                        $reminder_tmp['reminder_status'] = 'ignore';
                    } else if ($date2 < $date_today) {

                        //  Log::info("date2 is less than today====>");  
                        $reminder_tmp['reminder_status'] = 'ignore';
                    }
                }

                // $reminder_tmp['reminder_status'] = 'executed';
                $reminder_tmp['status'] = 'activate';
                $reminder_tmp['created_at'] = date('Y-m-d H:i:s');
                //  $reminder_tmp['parent_id'] = $parent_id;
                $reminder_tmp['type'] = 'control';

                 Log::info($reminder_tmp);

                //dd($reminder_tmp);
                $reminder_id = $this->PatientsHasServiceReminderModel->insertGetId($reminder_tmp);
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

                    }//previousAppointmentIds remove general reminder

                    //Delete control reminder of previous appointment

                    //Get reminder entry for above control service id and delete it for previous appointment
                    $previousAppointmentIds = DB::connection('tenant')->table('patient_has_service_reminder')
                        ->where('type','control')
                        ->whereNull('deleted_at')
                        ->where('service_id', $service_id)
                        ->where('patient_id',$patient_id)
                        ->where('appointment_id','!=',$appointment_id)
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
                                        ->where('type','control')
                                        ->whereNull('deleted_at')
                                        ->where('service_id', $service_id)
                                        ->where('patient_id',$patient_id)
                                        ->where('appointment_id','!=',$appointment_id)
                                        ->whereNull('deleted_at')
                                        ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                    

                        $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                        ->whereIn('service_reminder_id',$service_id_holder)
                                        ->update(['deleted_at'=>date('Y-m-d H:i:s')]);                

                    }//previousAppointmentIds remove control reminder





                }//if  controlServiceId                 

               /*****Remove**general reminder***6-march-26********/   



            }
            $value5_days = $this->_getDate(end($reminder_array), $is_service_has_reminder->checkup_new_frequency, $is_service_has_reminder->checkup_new_frequency_type);
            // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));

            $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array), $value5_days, $is_service_has_reminder->holiday_reminder, 'plus');

            $temp = [];
            $temp['patient_id'] =  $patient_id;
            $temp['last_reminder_date'] =  end($reminder_array);
            $temp['next_reminder_date'] =  $reactive_reminder;
            $temp['service_reminder_id'] =  $reminder_id;
            $temp['status'] =  'activate';
            $parent_id = $this->PatientHasReminder->insertGetId($temp);
        }
    }

    public function _filterWeekendAndHoiliday($date, $days, $is_hoilday_or_weekend, $operation)
    {

        $operator = '+';
        if ($operation == 'minus') {
            $operator = '-';
        }
        $calculated_date = Date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s", strtotime($date)) . " " . $operator . (int)$days . " day"));
        $weekDay = date('w', strtotime($calculated_date));
        // Log::info($date."=".$days."=".$is_hoilday_or_weekend."=".$operation."=".$calculated_date);
        // dump($date."=".$days."=".$is_hoilday_or_weekend."=".$operation."=".$calculated_date);
        // if ($is_hoilday_or_weekend == 1 && ($weekDay == 0 || $weekDay == 6)) {
        //     $time = date('H:i:s', strtotime($calculated_date));
        //     $calculated_date = Date('Y-m-d', strtotime($calculated_date . ' +1 Weekday'));
        //     $calculated_date = $calculated_date . " " . $time;
        //     // dump($calculated_date);
        // }
        // dump($calculated_date);
        //  Log::info($calculated_date);
        return $calculated_date;
    }

    public function _getDate($start_date, $period, $frequency_type)
    {
        $days = 0;
        switch ($frequency_type) {
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
    public function importData_live_renamed_on_23_feb_24(Request $request)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] =  __('admin.FAIL_ROLE_CREATE');
        $google_event_id = Null;
        $appointmentType = Null;
        try {
            //fk_ordination_id
            $filename = $request->file('select_file');

            if (!file_exists($filename) || !is_readable($filename))
                return false;

            $header = null;
            $data = array();
            if (($handle = fopen($filename, 'r')) !== false) {
                while (($row = fgetcsv($handle, 1000)) !== false) {
                    if (!$header) {
                        $header = $row;
                    } else {
                        //dd($row);
                        $tmp = [];
                        $tmp['family_name']  = mb_convert_encoding($row[0], 'UTF-8', 'UTF-8');
                        $tmp['first_name']   = mb_convert_encoding($row[1], 'UTF-8', 'UTF-8');
                        $tmp['email']        = mb_convert_encoding($row[2], 'UTF-8', 'UTF-8');
                        $tmp['mobile_no']    = mb_convert_encoding($row[3], 'UTF-8', 'UTF-8');


                        if (!empty($row[4])) {
                            $tmp['birth_date']  = date('Y-m-d', strtotime($row[4]));
                            $tmp['age']         = (date('Y') - date('Y', strtotime($tmp['birth_date'])));
                        } else {
                            $tmp['birth_date']  = null;
                            $tmp['age']         = 0;
                        }
                        $tmp['start_date']  = date('Y-m-d', strtotime($row[5]));
                        $tmp['end_date']    = date('Y-m-d', strtotime($row[6]));
                        $tmp['doctor_first_name'] = mb_convert_encoding($row[7], 'UTF-8', 'UTF-8');
                        $tmp['doctor_last_name'] = mb_convert_encoding($row[8], 'UTF-8', 'UTF-8');
                        $tmp['created_at'] = date('Y-m-d H:i:s');


                        $patient_id =  DB::table("patients")
                            ->where(DB::raw('upper(family_name)'), '=', mb_strtoupper($tmp['family_name']))
                            ->where(DB::raw('upper(first_name)'), '=', mb_strtoupper($tmp['first_name']))
                            ->whereDate('birth_date', date('Y-m-d', strtotime($tmp['birth_date'])))
                            ->where('mobile_no', $tmp['mobile_no'])
                            ->whereNULL('deleted_at')
                            ->orderBy('created_at', 'DESC')
                            ->pluck('id')
                            ->first();


                        if (!empty($patient_id)) {
                            $doctor_id   = Null;
                            $doctorName  = Null;
                            $notes       = Null;
                            $doctor_email = Null;
                            $color_id    = 0;
                            $getDoctorId = DB::table("users")
                                ->where(DB::raw('upper(first_name)'), '=', mb_strtoupper($tmp['doctor_first_name']))
                                ->where(DB::raw('upper(last_name)'), '=', mb_strtoupper($tmp['doctor_last_name']))
                                ->first();

                            if (!empty($getDoctorId)) {
                                $doctor_id    = $getDoctorId->id;
                                $doctorName   = $getDoctorId->first_name . " " . $getDoctorId->last_name;
                                $doctor_email = $getDoctorId->email;
                                $color_id     = $getDoctorId->google_color_id;
                            } else {
                                $drRec['first_name'] = $tmp['doctor_first_name'];
                                $drRec['last_name'] = $tmp['doctor_last_name'];
                                $drRec['status']    = 1;

                                $insertDr = DB::table("users")
                                    ->insertGetId($drRec);

                                $doctor_id    = $insertDr;
                                $doctorName   = $tmp['doctor_first_name'] . " " . $tmp['doctor_last_name'];
                                $doctor_email = Null;
                                $color_id     = Null;
                            }

                            //Appoinment added in google calendar
                            $patientName = $tmp['first_name'] . " " . $tmp['family_name'];



                            $summary = $patientName . " - " . $appointmentType;
                            $description = '<p><strong>' . $this->patientText . ':</strong> ' . $patientName . ' </p><p><strong>' . $this->doctorText . ':</strong> ' . $doctorName . ' </p><p><strong>' . $this->appointmentText . ':</strong> ' . $appointmentType . ' </p><p><strong>' . $this->startDateText . ':</strong> ' . date('F d,Y H:i', strtotime($tmp['start_date'])) . ' </p><strong>' . $this->endDateText . ':</strong> ' . date('F d,Y H:i', strtotime($tmp['end_date'])) . ' </p><p><strong>' . $this->notesText . ':</strong> ' . $notes . ' </p>';

                            $request = array(
                                'summary' => $summary,
                                'description' => $description,
                                'startDateTime' => $tmp['start_date'],
                                'endDateTime' => $tmp['end_date'],
                                'patient_id' => $patient_id,
                                'patient_email' => $tmp['email'],
                                'patient_name' => $patientName,
                                'doctor_email' => $doctor_email,
                                'color_id' => $color_id,
                            );
                            /*if(!empty($request->new_patient_chkbox) && $request->new_patient_chkbox==1){
                                    $request['patient_name']= $patientName;
                                }*/

                            request()->merge($request);

                            $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventStore(request());
                            //$postResponse = json_decode($postCalDetails->data);

                            if (!empty($postCalDetails) && $postCalDetails->original['status'] == 'success') {
                                $all_transactions[] = 1;
                                $eventId = $postCalDetails->original['data']->id;
                                $google_event_id = $eventId;
                            }
                            $app['google_event_id'] = $google_event_id;
                            $app['start_date']      = $tmp['start_date'];
                            $app['end_date']        = $tmp['end_date'];
                            $app['patient_id']      = $patient_id;
                            $app['doctor_id']       = $doctor_id;
                            $app['status']          = 1;
                            $this->BaseModel->insert($app);
                        }
                    }
                }
            }

            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            $this->JsonData['url']    =  route($this->ModulePath . 'index');
            $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
            return response()->json($this->JsonData);
        } catch (Exception $e) {
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.ERR_SOMETHING_WRONG');
        }
    } //importData


    public function event_type_list()
    {
        // dd("----->");
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_EXAMINATIONS_TEXT') . ' ' . __('admin.TITLE_EXAMINATIONS_EVENT_TYPE');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle . ' ' . __('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle . ' ' . __('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');

        // view file with data
        return view($this->ModuleView . 'event_type', $this->ViewData);
    }

    public function getEventTypeRecords(Request $request)
    {
        // dump($request->all());
        /*--------------------------------------
        |  Variables
        ------------------------------*/

        // skip and limit
        $start  = $request->start;
        $length = $request->length;

        // Login user id 
        $userId = Auth::user()->id;

        // serach value
        $search = $request->search['value'];

        // order
        $column = $request->order[0]['column'];
        $dir    = $request->order[0]['dir'];

        // filter columns
        $filter = array(
            0 => 'id',
            1 => 'appointment.start_date',
            2 => 'service_event_type.created_at',
            3 => 'patients.first_name',
            4 => 'examinations.name',
            5 => 'preferred_channels_for_reminders_setting.activated_reminder',
            6 => 'service_event_type.event_type',
            7 => 'service_event_type.status',
        );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/
        $modelQuery =  $this->EventTypeHasExaminationsModel
            ->leftjoin('examinations', 'examinations.id', '=', 'service_event_type.service_id')
            ->leftjoin('preferred_channels_for_reminders_setting', 'preferred_channels_for_reminders_setting.service_id', '=', 'examinations.id')
            ->leftjoin('appointment', 'appointment.id', '=', 'service_event_type.appoinment_id')
            ->leftjoin('patients', 'patients.id', '=', 'service_event_type.patient_id')
            ->whereNull('examinations.deleted_at');

        // get total count 
        $countQuery = clone ($modelQuery);
        $totalData  = $countQuery->count();

        ## FILTER OPTIONS for specific field 
        $custom_search = false;
        if (!empty($request->custom)) {
            if (!empty($request->custom['start_date'])) {
                $custom_search  = true;
                $key            = date("Y-m-d", strtotime($request->custom['start_date']));
                $modelQuery     = $modelQuery
                    ->whereDate('appointment.start_date', $key);
            }

            if (!empty($request->custom['event_date'])) {
                $custom_search  = true;
                $key            = date("Y-m-d", strtotime($request->custom['event_date']));
                $modelQuery     = $modelQuery
                    ->whereDate('service_event_type.created_at', $key);
            }

            if (!empty($request->custom['patient_id'])) {
                    $raw = trim($request->custom['patient_id']);
                    // keep letters, numbers, spaces, hyphen
                    $cleaned = preg_replace('/[^\p{L}0-9\s\-]/u', '', $raw);

                    if (empty($cleaned)) {
                        $modelQuery->whereRaw('1 = 0'); // no results
                    } else {
                        // Escape regex metachars for MySQL RLIKE (simple escaping)
                        $regexEscaped = preg_replace('/([\\\\.\^\$\|\?\*\+\(\)\[\{\]])/', '\\\\$1', $cleaned);

                        // MySQL word-boundary style
                        // $mysqlRegex = '[[:<:]]' . $regexEscaped . '[[:>:]]';
                        $mysqlRegex = $regexEscaped;

                        // Also prepare a safe LIKE pattern (escape % and _)
                        $likeEscaped = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $cleaned);

                        $modelQuery = $modelQuery->where(function ($q) use ($mysqlRegex, $likeEscaped) {
                            // whole-word match in first_name OR family_name OR full name
                            $q->whereRaw("patients.first_name RLIKE ?", [$mysqlRegex])
                            ->orWhereRaw("patients.family_name RLIKE ?", [$mysqlRegex])
                            ->orWhereRaw("CONCAT(patients.first_name, ' ', patients.family_name) RLIKE ?", [$mysqlRegex])
                            // fallback exact-substring (if you want exact but allow within a longer field)
                            ->orWhere('patients.first_name', 'LIKE', "%{$likeEscaped}%")
                            ->orWhere('patients.family_name', 'LIKE', "%{$likeEscaped}%")
                            ->orWhereRaw("CONCAT(patients.first_name, ' ', patients.family_name) LIKE ?", ["%{$likeEscaped}%"]);
                        });
                    }
                }
            if (isset($request->custom['event_type'])) {
                $custom_search  = true;
                $key            = $request->custom['event_type'];
                $modelQuery     = $modelQuery
                    ->where('service_event_type.event_type', $key);
            }

            if (isset($request->custom['status'])) {
                $custom_search  = true;
                $key            = $request->custom['status'];
                $modelQuery     = $modelQuery
                    ->where('service_event_type.status', $key);
            }
            if (isset($request->custom['name'])) {
                $custom_search  = true;
                $key            = $request->custom['name'];
                $modelQuery     = $modelQuery
                    ->where('examinations.name', 'LIKE', '%' . $key . '%');
            }
            if (isset($request->custom['service_type'])) {
                $custom_search  = true;

                if ($request->custom['service_type'] == 'Alter') {
                    $key   = 'age';
                } elseif ($request->custom['service_type'] == 'Empfehlung') {
                    $key   = 'general';
                } else {
                    $key   = '';
                }

                $modelQuery     = $modelQuery
                    ->where('preferred_channels_for_reminders_setting.activated_reminder', 'LIKE', '%' . $key . '%');
            }
        }

        // Common filter options
        if (!empty($request->search)) {
            if (!empty($request->search['value'])) {
                $search = $request->search['value'];

                $modelQuery = $modelQuery->where(function ($query) use ($search) {
                    $query->orwhere('appointment.start_date', 'LIKE', '%' . $search . '%');
                    $query->orwhere('service_event_type.created_at', 'LIKE', '%' . $search . '%');
                    $query->orWhere(DB::raw("CONCAT(patients.first_name, ' ', patients.family_name)"), 'LIKE', "%" . $search . "%");
                    $query->orwhere('examinations.name', 'LIKE', '%' . $search . '%');
                    $query->orwhere('preferred_channels_for_reminders_setting.activated_reminder', 'LIKE', '%' . $search . '%');
                    $query->orwhere('service_event_type.status', 'LIKE', '%' . $search . '%');
                    $query->orwhere('service_event_type.event_type', 'LIKE', '%' . $search . '%');
                });
            }
        }

        // get total filtered
        $filteredQuery = clone ($modelQuery);
        $totalFiltered  = $filteredQuery->count();

        // offset and limit
        $object = $modelQuery->orderBy($filter[$column], $dir)
            ->skip($start)
            ->take($length)
            ->get([
                'appointment.start_date',
                'appointment.end_date',
                'patients.first_name as patient_fname',
                'patients.family_name as patient_lname',
                'examinations.name as name',
                'appointment.appointment_status as appointment_status',
                'service_event_type.*',
                'preferred_channels_for_reminders_setting.activated_reminder'
            ]);

        /*--------------------------------------
        |  data binding
        ------------------------------*/
        $data = [];
        if (!empty($object) && sizeof($object) > 0) {
            foreach ($object as $key => $row) {
                $fname          = $row->patient_fname;
                $lname          = $row->patient_lname;
                $patient_name   = $fname . ' ' . $lname;

                $data[$key]['id']  = $row->id;
                $data[$key]['start_date']   = '<span title="' . $row->start_date . '">' . $row->start_date . '</span>';
                $data[$key]['event_date']   = '<span title="' . $row->created_at . '">' . $row->created_at . '</span>';
                $data[$key]['patient_id']   = '<span title="' . ucfirst($patient_name) . '">' . ucfirst($patient_name) . '</span>';
                $data[$key]['name']   = '<span>' . $row->name . '</span>';

                if (!empty($row->activated_reminder)) {
                    if ($row->activated_reminder == 'age') {
                        $data[$key]['service_type'] = '<span>Alter</span>';
                    } else if ($row->activated_reminder == 'general') {
                        $data[$key]['service_type'] = '<span>Empfehlung</span>';
                    } else {
                        $data[$key]['service_type'] = '<span> </span>';
                    }
                } else {
                    $data[$key]['service_type']   = '<span>Termintyp</span>';
                }

                $data[$key]['event_type']   = '<span title="' . ucfirst($row->event_type) . '">' . ucfirst($row->event_type) . '</span>';

                $id = $row->patientId;
                $data[$key]['status']   = '<span title="' . ucfirst($row->status) . '">' . ucfirst($row->status) . '</span>';
            }
        }
        ## SEARCH HTML 
        $user = $this->AdminUserModel
            ->whereHas('roles', function ($query) {
                $query->where('name', 'doctor');
            })
            ->get();

        // Search start date
        if (!empty($request->custom['start_date']) && $request->custom['start_date'] == '') {
            $val = '';
        } else {
            $val = $request->custom['start_date'] ?? '';
        }

        // Search for appointment type column
        $eventType = '<select name="event_type" id="event_type" class="form-control my-select">
            <option class="theme-black blue-select" value="">' . __('admin.TITLE_SEARCH_TEXT') . '</option><option class="theme-black blue-select" value="admin" ' . (isset($request->custom['event_type']) && $request->custom['event_type'] == "admin" ? 'selected' : '') . '>' . __('admin.TITLE_EXAMINATIONS_EVENT_ADMIN') . '</option><option class="theme-black blue-select" value="web" ' . (isset($request->custom['event_type']) && $request->custom['event_type'] == "web" ? 'selected' : '') . '>' . __('admin.TITLE_EXAMINATIONS_EVENT_WEB') . '</option><option class="theme-black blue-select" value="smart_phone" ' . (isset($request->custom['event_type']) && $request->custom['event_type'] == "smart_phone" ? 'selected' : '') . '>' . __('admin.TITLE_EXAMINATIONS_EVENT_SMARTPHONE') . '</option><option class="theme-black blue-select" value="tablet" ' . (isset($request->custom['event_type']) && $request->custom['event_type'] == "tablet" ? 'selected' : '') . '>' . __('admin.TITLE_EXAMINATIONS_EVENT_TABLET') . '</option></select>';

        $status = '<select name="status" id="status" class="form-control my-select">
            <option class="theme-black blue-select" value="">' . __('admin.TITLE_SEARCH_TEXT') . '</option><option class="theme-black blue-select" value="displayed" ' . (isset($request->custom['status']) && $request->custom['status'] == "displayed" ? 'selected' : '') . '>' . __('admin.TITLE_EXAMINATIONS_EVENT_STATUS_DISPLAYED') . '</option><option class="theme-black blue-select" value="booked" ' . (isset($request->custom['status']) && $request->custom['status'] == "booked" ? 'selected' : '') . '>' . __('admin.TITLE_EXAMINATIONS_EVENT_STATUS_BOOKED') . '</option></select>';

        // SEARCH HTML
        $searchHTML['id']               =  '';

        $searchHTML['start_date']       =  '<input type="text" class="form-control" id="start_date" name="start_date" value="' . $val . '" placeholder=' . __('admin.TITLE_SEARCH_TEXT') . '>';

        $searchHTML['event_date']       =  '<input type="text" class="form-control" id="event_date" name="event_date" value="' . ($request->custom['event_date'] ?? '') . '" placeholder=' . __('admin.TITLE_SEARCH_TEXT') . '>';
        $searchHTML['patient_id']     =  '<input type="text" class="form-control" id="patient_id" name="patient_id" value="' . ($request->custom['patient_id'] ?? '') . '" placeholder=' . __('admin.TITLE_SEARCH_TEXT') . '>';
        $searchHTML['name']  =  '<input type="text" class="form-control" id="name" value="' . ($request->custom['name'] ?? '') . '" placeholder=' . __('admin.TITLE_SEARCH_TEXT') . '>
            ';
        $searchHTML['service_type']  =  '';

        $searchHTML['event_type']  =  $eventType;
        $searchHTML['status'] = $status . ' <div class="text-center" style="padding-top:10px;"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';

        array_unshift($data, $searchHTML);

        // wrapping up
        $this->JsonData['draw']             = intval($request->draw);
        $this->JsonData['recordsTotal']     = intval($totalData);
        $this->JsonData['recordsFiltered']  = intval($totalFiltered);
        $this->JsonData['data']             = $data;
        return response()->json($this->JsonData);
    }

    public function checkCronRactivereminder(Request $request)
    {
        $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
            ->whereDate('last_reminder_date', '<=', date('Y-m-d'))
            ->whereDate('last_reminder_date', '>=', '2021-01-01')
            ->whereNull('deleted_at')
            ->where('patient_id', '36067')
            ->get();
        if (!empty($reactivateReminder) && count($reactivateReminder) > 0) {
            foreach ($reactivateReminder as $reminder_key => $reminder_value) {
                print_r($reminder_value);
                exit;
                $is_appoitment_book = DB::connection('tenant')->table('appointment')
                    ->whereDate('start_date', '>=', $reminder_value->last_reminder_date)
                    ->whereDate('start_date', '<=', $reminder_value->next_reminder_date)
                    ->where('patient_id', $reminder_value->patient_id)
                    ->get();

                if (!empty($is_appoitment_book) && count($is_appoitment_book) > 0) {
                    $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                        ->where('id', $reminder_value->id)
                        ->update(['deleted_at' => date('Y-m-d H:i:s')]);
                } else {
                    $reminder_details = DB::connection('tenant')->table('patient_has_service_reminder')->where('id', $reminder_value->service_reminder_id)->first();

                    DB::connection('tenant')->table('patient_has_service_reminder')
                        ->where('patient_id', $reminder_details->patient_id)
                        ->where('service_id', $reminder_details->service_id)
                        ->where('appointment_id', $reminder_details->appointment_id)
                        ->where('type', $reminder_details->type)
                        ->update(['deleted_at' => date('Y-m-d H:i:s')]);

                    $serviceDetail = DB::connection('tenant')->table('examinations')
                        ->where('id', $reminder_details->service_id)
                        ->first();

                    $patientDetails = DB::connection('tenant')->table('patients')
                        ->select('age', 'birth_date', 'id')
                        ->where('id', $reminder_details->patient_id)
                        ->first();

                    if (!empty($patientDetails->birth_date)) {
                        $from = new DateTime($patientDetails->birth_date);
                        $to   = new DateTime('today');
                        $age =  $from->diff($to)->y;
                        $data['age'] = $age;
                    } else {
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
                    if (empty($is_service_has_reminder)) {
                        $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                            [
                                'type' => 'global',
                            ]
                        )->first();
                    } else {
                        $default_reminder = $is_service_has_reminder->activated_reminder;
                        $h_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')->where(
                            [
                                'type' => 'global',
                            ]
                        )->first(['holiday_reminder', 'checkup_number_of_interval', 'checkup_time_interval', 'checkup_first_frequency', 'checkup_new_frequency', 'checkup_period_controls', 'checkup_time_interval_frequency_type', 'checkup_first_frequency_type', 'checkup_new_frequency_type', 'checkup_period_frequency_type']);
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
                    if ($default_reminder == 'general') {
                        $this->_reactivateGeneralReminder($is_service_has_reminder, $appointment_id, $todays_date, $patient_id, $serviceDetail->id);
                    } else {
                        if (!empty($data['age']) && $data['age'] != '') {
                            $this->_reactivateAgeReminder($is_service_has_reminder, $appointment_id, $todays_date, $patient_id, $data, $serviceDetail->id);
                        }
                    }

                    $is_doctor_set_reminder = db::connection('tenant')->table('patient_has_service_control_reminder_setting')->where(
                        [
                            'patient_id' => $patient_id,
                            'appointment_id' => $appointment_id,
                            'service_id' => $serviceDetail->id,
                            'status' => '1',
                        ]
                    )->first();

                    if ($is_doctor_set_reminder) {
                        $is_service_has_reminder->checkup_period_controls =  $is_doctor_set_reminder->control_interval;
                        $is_service_has_reminder->checkup_period_frequency_type =  $is_doctor_set_reminder->control_frequency;
                    }
                    $this->_reactivateControlReminder($is_service_has_reminder, $appointment_id, $todays_date, $patient_id, $serviceDetail->id);

                    $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                        ->where('id', $reminder_value->id)
                        ->update(['deleted_at' => date('Y-m-d H:i:s')]);
                }
            }
        }
    }
    public function _reactivateGeneralReminder($is_service_has_reminder, $appointment_id, $start_date, $patient_id, $service_id)
    {
        $reminder_array = [];

        // 1st reminder
        $start_date = $start_date;

        $reminder_array[] = $start_date;

        for ($i = 0; $i < ($is_service_has_reminder->general_number_of_interval - 1); $i++) {
            $value4_days = $this->_getDate($start_date, $is_service_has_reminder->general_time_interval, $is_service_has_reminder->general_time_interval_frequency_type);

            // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));

            $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array), $value4_days, $is_service_has_reminder->holiday_reminder, 'plus');

            if ($third_reminder !=  $start_date) {
                $reminder_array[] = $third_reminder;
            }
        }
        sort($reminder_array);
        $reminder_id = 0;
        for ($i = 0; $i < count($reminder_array); $i++) {
            $reminder_tmp = [];
            $reminder_tmp['patient_id'] = $patient_id;
            $reminder_tmp['appointment_id'] = $appointment_id;
            $reminder_tmp['service_id'] = $service_id;
            $reminder_tmp['reminder_date'] = $reminder_array[$i];
            $reminder_tmp['reminder_status'] = 'Set';
            $reminder_tmp['status'] = 'activate';
            $reminder_tmp['created_at'] = date('Y-m-d h-i-s');
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
            if (count($is_exists) == 0) {
                $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
            }
        }

        $value5_days = $this->_getDate(end($reminder_array), $is_service_has_reminder->general_new_frequency, $is_service_has_reminder->general_new_frequency_type);

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));

        $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array), $value5_days, $is_service_has_reminder->holiday_reminder, 'plus');

        $temp = [];
        $temp['patient_id'] =  $patient_id;
        $temp['last_reminder_date'] =  end($reminder_array);
        $temp['next_reminder_date'] =  $reactive_reminder;
        $temp['service_reminder_id'] =  $reminder_id;
        $temp['status'] =  'activate';
        $temp['created_at'] =  date('Y-m-d H:i:s');
        $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
    }


    public function _reactivateAgeReminder($is_service_has_reminder, $appointment_id, $start_date, $patient_id, $data, $service_id)
    {
        $reminder_array = [];
        if ($data['age'] == $is_service_has_reminder->age_from || $data['age'] <= $is_service_has_reminder->age_to) {
            $start_date = $start_date;

            $reminder_array[] = $start_date;

            for ($i = 0; $i < ($is_service_has_reminder->age_number_of_interval - 1); $i++) {
                $value4_days = $this->_getDate($start_date, $is_service_has_reminder->age_time_interval, $is_service_has_reminder->age_time_interval_frequency_type);

                // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array), $value4_days, $is_service_has_reminder->holiday_reminder, 'plus');

                if ($third_reminder !=  $start_date) {
                    $reminder_array[] = $third_reminder;
                }
            }
            sort($reminder_array);
        } elseif ($data['age'] < $is_service_has_reminder->age_from) {
            $diff = $is_service_has_reminder->age_from - $data['age'];
            $start_date = date('Y-m-d', strtotime($data['birth_date'] . ' + ' . $diff . ' year'));
            $period_date = $start_date . " " . date('H:i:s');
            $reminder_array[] = $period_date;

            for ($i = 0; $i < ($is_service_has_reminder->age_number_of_interval - 1); $i++) {
                $value4_days = $this->_getDate($period_date, $is_service_has_reminder->age_time_interval, $is_service_has_reminder->age_time_interval_frequency_type);

                // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array), $value4_days, $is_service_has_reminder->holiday_reminder, 'plus');

                $reminder_array[] = $third_reminder;
            }
            sort($reminder_array);
        }
        $reminder_id = 0;
        for ($i = 0; $i < count($reminder_array); $i++) {
            $reminder_tmp = [];
            $reminder_tmp['patient_id'] = $patient_id;
            $reminder_tmp['appointment_id'] = $appointment_id;
            $reminder_tmp['service_id'] = $service_id;
            $reminder_tmp['reminder_date'] = $reminder_array[$i];
            $reminder_tmp['reminder_status'] = 'Set';
            $reminder_tmp['status'] = 'activate';
            $reminder_tmp['created_at'] = date('Y-m-d h-i-s');
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
            if (count($is_exists) == 0) {
                $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
            }
        }
        $value5_days = $this->_getDate(end($reminder_array), $is_service_has_reminder->age_new_frequency, $is_service_has_reminder->age_new_frequency_type);

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));
        $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array), $value5_days, $is_service_has_reminder->holiday_reminder, 'plus');

        $temp = [];
        $temp['patient_id'] =  $patient_id;
        $temp['last_reminder_date'] =  end($reminder_array);
        $temp['next_reminder_date'] =  $reactive_reminder;
        $temp['service_reminder_id'] =  $reminder_id;
        $temp['status'] =  'activate';
        $temp['created_at'] =  date('Y-m-d H:i:s');
        $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
    }

    public function _reactivateControlReminder($is_service_has_reminder, $appointment_id, $start_date, $patient_id, $service_id)
    {
        $reminder_array = [];
        // 1st reminder
        $start_date = $start_date;

        $reminder_array[] = $start_date;

        for ($i = 0; $i < ($is_service_has_reminder->checkup_number_of_interval - 1); $i++) {
            $value4_days = $this->_getDate($start_date, $is_service_has_reminder->checkup_time_interval, $is_service_has_reminder->checkup_time_interval_frequency_type);

            // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
            $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array), $value4_days, $is_service_has_reminder->holiday_reminder, 'plus');

            if ($third_reminder !=  $start_date) {
                $reminder_array[] = $third_reminder;
            }
        }
        sort($reminder_array);
        $reminder_id = 0;
        for ($i = 0; $i < count($reminder_array); $i++) {
            $reminder_tmp = [];
            $reminder_tmp['patient_id'] = $patient_id;
            $reminder_tmp['appointment_id'] = $appointment_id;
            $reminder_tmp['service_id'] = $service_id;
            $reminder_tmp['reminder_date'] = $reminder_array[$i];
            $reminder_tmp['reminder_status'] = 'Set';
            $reminder_tmp['status'] = 'activate';
            $reminder_tmp['created_at'] = date('Y-m-d h-i-s');
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
            if (count($is_exists) == 0) {
                $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
            }
        }

        $value5_days = $this->_getDate(end($reminder_array), $is_service_has_reminder->checkup_new_frequency, $is_service_has_reminder->checkup_new_frequency_type);

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));
        $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array), $value5_days, $is_service_has_reminder->holiday_reminder, 'plus');

        $temp = [];
        $temp['patient_id'] =  $patient_id;
        $temp['last_reminder_date'] =  end($reminder_array);
        $temp['next_reminder_date'] =  $reactive_reminder;
        $temp['service_reminder_id'] =  $reminder_id;
        $temp['status'] =  'activate';
        $temp['created_at'] =  date('Y-m-d H:i:s');
        $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
    }

    //Smart Appoitment 20-Aug-22 Added by Divya (Swapil)=======================
    //Added by swapnil on 15-sept-22
    public function dateSelected(Request $request)
    {


        $quarter_setting_check = $request->quarter_setting_check;
        $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
        
        $bookingtimeframe = DB::table('settings')->where(['setting_key' => $APPOINTMENT_TIME_PERIOD])->select('setting_key', 'setting_value', 'description')->first();

        $optimal_appointment = 0;
        if (isset($quarter_setting_check)) {
            $optimal_appointment = $quarter_setting_check;
        }
        
        $doctor_id = $request->doctor_id;
        $patient_id = $request->patient_id;
        $doctor_status = $request->doctor_status;
        $appointment_type_id = $request->appointment_type_id;
        $todaysdate = date('Y-m-d');
        $flag_set = array('0', '1');
        
        $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$doctor_id and STATUS=1 AND start_date>='$todaysdate'");

        $appointdatesarr =  isset($getStartDates[0]->appointment_dates) && !empty($getStartDates[0]->appointment_dates) ? $getStartDates[0]->appointment_dates : '';
        if (isset($request->patient_id) && !empty($request->patient_id)) {

            $time_slots = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")
                    ->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")
                    ->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
            })
                ->where('roster.doctor_id', $doctor_id)
                ->where('roster_has_dates.is_excluded', '=', 0)
                ->where('roster_has_dates.date', '>=', $todaysdate)
                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))
                ->whereRaw(!empty($appointdatesarr) ? "(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))" : "1=1")
                ->groupBy('roster_has_dates.date')
                ->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);
        } else {

            $time_slots = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")
                    ->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")
                    ->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
            })
                ->where('roster.doctor_id', $doctor_id)
                ->where('roster_has_dates.is_excluded', '=', 0)
                ->where('roster_has_dates.date', '>=', $todaysdate)
                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))
                ->whereRaw(!empty($appointdatesarr) ? "(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))" : "1=1")
                ->groupBy('roster_has_dates.date')
                ->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);
        }
        $description =  isset($bookingtimeframe->description) ?  $bookingtimeframe->description : '';
        $setting_value =isset($bookingtimeframe->setting_value) ? $bookingtimeframe->setting_value : '';
        
        //main code
        if (!empty($time_slots)) {
            $calender_date1 = $time_slots->date;
            $calender_date = $time_slots->date;
            
            //$optimal_appointment ==1  means quarter setting active
            if ($optimal_appointment == '1') {
                $quarter_data =  $this->optimalAppointmentCheck($doctor_id, $patient_id, $optimal_appointment, $description, $setting_value);
                if (!empty($quarter_data)) {
                    $count = 1;
                    $quarter_setting = 1;
                    $calender_date = "";
                    $description =  isset($bookingtimeframe->description) ?  $bookingtimeframe->description : '';
                    $setting_value =  isset($bookingtimeframe->setting_value) ? $bookingtimeframe->setting_value : '';
                    $no_of_days = $quarter_data['no_of_days'];
                    $calender_date1 = date("Y-m-d", strtotime($quarter_data['calender_date1']));
                    $calender_date2 = date("Y-m-d", strtotime($quarter_data['calender_date2']));
                } else {
                    $count = 0;
                    $quarter_setting = 1;
                    $calender_date = "";
                    $description =  isset($bookingtimeframe->description) ?  $bookingtimeframe->description : '';
                    $setting_value =  isset($bookingtimeframe->setting_value) ? $bookingtimeframe->setting_value : '';
                    $no_of_days = 0;
                    $calender_date1 = "";
                    $calender_date2 = "";
                }
            } else {
                $quarter_setting = 0;
                $count = 1;
                $calender_date1 = $calender_date1;
                
                if ($description == "week") {
                    $newdate =  date('Y/m/d', strtotime($calender_date . ' + ' . $setting_value . ' week'));
                    $calender_date2 = date('Y-m-d', strtotime($calender_date . ' + ' . $setting_value . ' week'));
                } elseif ($description == "month") {
                    $newdate =  date('Y/m/d', strtotime($calender_date . ' + ' . $setting_value . ' month'));
                    $calender_date2 = date('Y-m-d', strtotime($calender_date . ' + ' . $setting_value . ' month'));
                }
                $now = strtotime($calender_date);
                $your_date = strtotime($newdate);
                $datediff = $your_date - $now;
                $no_of_days1 =  round($datediff / (60 * 60 * 24));
                $no_of_days = $no_of_days1 + 1;
            }
        } else {
            $count = 0;
            $quarter_setting = 0;
            $calender_date = "";
            $description =  isset($bookingtimeframe->description) ?  $bookingtimeframe->description : '';
            $setting_value =  isset($bookingtimeframe->setting_value) ? $bookingtimeframe->setting_value : '';
            $no_of_days = 0;
            $calender_date1 = "";
            $calender_date2 = "";
            $calender_date = "";
        }
        //main code
        if (!empty($request->calender_search == '1')) {
            $start_date = $request->appointment_from_date;
            $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $no_of_days . ' days'));
        } else if (!empty($request->calender_search == '2')) {
            $start_date = $request->appointment_from_date;
            $end_date = date('Y-m-d', strtotime($request->appointment_to_date));
        } else {
            $start_date = $calender_date1;
            $end_date = $calender_date2;
        }
        
        $html = $this->gettimeSlot($doctor_id, $appointment_type_id, $start_date, $end_date, $optimal_appointment, $patient_id);
        //calender start date and enddate
        //from date
        $from_date_year = date("Y", strtotime($calender_date1));
        $from_date_month1 = date("n", strtotime($calender_date1));
        if ($from_date_month1 == 1) {
            $from_date_month = 0;
        } else {
            $from_date_month = $from_date_month1 - 1;
        }
        $from_date_date1 = date("d", strtotime($calender_date1));
        $from_date_date = ltrim($from_date_date1, "0");
        //from date
        //to date
        $to_date_year = date("Y", strtotime($calender_date2));
        $to_date_month1 = date("n", strtotime($calender_date2));
        if ($to_date_month1 == 1) {
            $to_date_month = 0;
        } else {
            $to_date_month = $to_date_month1 - 1;
        }
        $to_date_date1 = date("d", strtotime($calender_date2));
        $to_date_date = ltrim($to_date_date1, "0");
        //to date
        //calender start date and enddate
        $todatdate = date('Y-m-d');
        $now1 = strtotime($start_date);
        $your_date1 = strtotime($todatdate);
        $datediff1 = $now1 - $your_date1;
        $no_of_days1 =  round($datediff1 / (60 * 60 * 24));
        $no_of_days2 = $no_of_days1 . 'd';

        $todatdate2 = date('Y-m-d');
        $now2 = strtotime($end_date);
        $your_date2 = strtotime($todatdate2);
        $datediff2 = $now2 - $your_date2;
        $no_of_days_enddate =  round($datediff2 / (60 * 60 * 24));
        $no_of_days_enddate = $no_of_days_enddate . 'd';

        //new code added by swapnil 10-jan23
        $getsecond_date = "";
        if ($description == "week") {
            $newdate = date('Y/m/d', strtotime($start_date . ' + ' . $setting_value . ' week'));
            $getsecond_date = date('d-m-Y', strtotime($newdate));
        }
        //new code added by swapnil 10-jan23


        //Commented below array on 10-jan23
        /*
            $dataArray = [
                'count'=>$count,
                'quarter_setting'=>$quarter_setting,
                'calender_date1'=>$start_date,
                'calender_date2'=> $end_date,
                'calender_date'=> $calender_date,
                'description'=>$description,
                'setting_value'=>$setting_value,
                'no_of_days'=>$no_of_days,
                'html'=>$html,
                'from_date_year'=>$from_date_year,
                'from_date_month'=>$from_date_month,
                'from_date_date'=>$from_date_date,
                'to_date_year'=>$to_date_year,
                'to_date_month'=>$to_date_month,
                'to_date_date'=>$to_date_date,
                'hidedate'=>$no_of_days2,
                'hideenddate'=>$no_of_days_enddate
            ];*/

        $dataArray = [
            'count' => $count,
            'quarter_setting' => $quarter_setting,
            // 'calender_date1'=>$start_date,  //commented by swapnil on 10-jan-23
            // 'calender_date2'=> $end_date,   //commented by swapnil on 10-jan-23
            'calender_date1' => date('d-m-Y', strtotime($start_date)), //added by swapnil on 10-jan-23
            'calender_date2' => date('d-m-Y', strtotime($end_date)), //added by swapnil on 10-jan-23
            'calender_date' => $calender_date,
            'description' => $description,
            'setting_value' => $setting_value,
            'no_of_days' => $no_of_days,
            'html' => $html,
            'from_date_year' => $from_date_year,
            'from_date_month' => $from_date_month,
            'from_date_date' => $from_date_date,
            'to_date_year' => $to_date_year,
            'to_date_month' => $to_date_month,
            'to_date_date' => $to_date_date,
            'hidedate' => $no_of_days2,
            'hideenddate' => $no_of_days_enddate,
            'getsecond_date' => $getsecond_date //new code added by swapnil 10-jan-23
        ];

        return $dataArray;
    } //
    // get month name from number
    function month_name($month_number)
    {
        return date('F', mktime(0, 0, 0, $month_number, 10));
    }
    // get get last date of given month (of year)
    function month_end_date($year, $month_number)
    {
        return date("t", strtotime("$year-$month_number-0"));
    }
    // return two digit month or day, e.g. 04 - April
    function zero_pad($number)
    {
        if ($number < 10)
            return "0$number";
        return "$number";
    }

    function get_quarters($start_date, $end_date)
    {
        $quarters = array();
        $start_month = date('m', strtotime($start_date));
        $start_year = date('Y', strtotime($start_date));
        $end_month = date('m', strtotime($end_date));
        $end_year = date('Y', strtotime($end_date));
        $start_quarter = ceil($start_month / 3);
        $end_quarter = ceil($end_month / 3);
        $quarter = $start_quarter; // variable to track current quarter
        // Loop over years and quarters to create array
        for ($y = $start_year; $y <= $end_year; $y++) {
            if ($y == $end_year)
                $max_qtr = $end_quarter;
            else
                $max_qtr = 4;
            for ($q = $quarter; $q <= $max_qtr; $q++) {
                $current_quarter = new stdClass();
                $end_month_num = $this->zero_pad($q * 3);
                $start_month_num = ($end_month_num - 2);
                $q_start_month = $this->month_name($start_month_num);
                $q_end_month = $this->month_name($end_month_num);
                $current_quarter->period = $q;
                $current_quarter->year = $y;
                $current_quarter->period_start = "$y-$start_month_num-01";      // yyyy-mm-dd    
                $current_quarter->period_end = "$y-$end_month_num-" . $this->month_end_date($y, $end_month_num);
                $quarters[] = $current_quarter;
                unset($current_quarter);
            }
            $quarter = 1; // reset to 1 for next year
        }
        return $quarters;
    } //
    //Added by swapnil on 15-sept-22
    public function gettimeSlot($doctor_id, $appointment_type_id, $start_date_old, $end_date_old, $optimal_appointment, $patient_id)
    {
        if ($optimal_appointment == '1') {
            /*************************************************************/
            $start_date = $start_date_old;
            $end_date = $end_date_old;
            $querterSetting = '1';
            $ignoreQuarterArr = $ignoreYearArr = $ignoreArray = [];
            $get_quarters = $this->get_quarters($start_date, $end_date);
            if (isset($get_quarters) && !empty($get_quarters)) {
                $ignoreArr = [];
                $whereQuarter = '';
                $quarterCheckFlag = 0;
                $whereQuarter = "Case ";
                foreach ($get_quarters as $k => $v) {
                    $quarter = $v->period;
                    $year = $v->year;

                    $checkAppoimentBooked = $this->BaseModel
                        ->whereRaw("quarter(start_date)=$quarter and year(start_date)=$year")
                        // ->where('doctor_id',$doctor_id)  //commented on 14-09-2022
                        ->where('patient_id', $patient_id)
                        ->where('status', 1)
                        // ->where('appointment_status','!=','Verpasst')   // Added on 21-sept-22
                        ->where('appointment_status', '!=', 'Vermisst')   // Added on 1-feb-24
                        ->first();

                    if (isset($checkAppoimentBooked) && !empty($checkAppoimentBooked)) {
                        $ignoreQuarterArr[] = $quarter;
                        $ignoreYearArr[] = $year;
                        $ignoreArr['quarter'] = $quarter;
                        $ignoreArr['year'] = $year;

                        $whereQuarter .= "WHEN quarter(roster_has_dates.date)=$quarter THEN year(roster_has_dates.date)!='$year'";
                        $quarterCheckFlag = 1;
                    } //if checkAppoimentBooked                    
                    if ($querterSetting == '1') {
                        if (isset($ignoreArr) && !empty($ignoreArr)) {
                            $ignoreArray[] = $ignoreArr;
                        } //
                    }
                } //foreach
                $whereQuarter .= "ELSE 1=1 END ";
            } //if
            /*************************************************************/
        } else {
            $querterSetting = '0';
            $start_date = $start_date_old;
            $end_date = $end_date_old;
        }
        $doctor_id              = $doctor_id;
        $appointment_type_id    = $appointment_type_id;
        $week_day_ids           = ['1', '2', '3', '4', '5', '6', '7'];
        $start_date  = date("Y-m-d", strtotime(str_replace("/", "-", $start_date)));
        $end_date  = date("Y-m-d", strtotime(str_replace("/", "-", $end_date)));
        $from_time              = "1:00";
        $to_time                = "23:00";
        $appointmentType = DB::table('appointment_types')->where('id', $appointment_type_id)->first();
        $setting = $this->SettingsModel
            ->where('id', 12)->first(['setting_key', 'setting_value']);
        if (!empty($setting)) {
            $default_time_duration = $setting['setting_value'];
        } else {
            $default_time_duration = 10;
        }
        // $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id); //commented on 13-apr-26

        $appointmentType = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id);//changed on 13-apr-26

        $appointmentDuration = 0;
        if (!empty($appointmentType)) {
            $appointmentDuration = $appointmentType->duration * 60; //convert min into sec
        }
        $roster_time_slots_date_wise = array();
        $time_frames = $this->RosterModel
            ->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')
            ->join('roster_has_weeks_has_time_frames', 'roster_has_weeks_has_time_frames.roster_id', 'roster_has_dates.roster_id')
            ->where('roster.doctor_id', $doctor_id)
            ->where('roster_has_dates.is_excluded', '=', 0)
            ->whereDate('roster_has_dates.date', '>=', $start_date)
            ->whereDate('roster_has_dates.date', '<=', $end_date)
            ->whereIn('roster_has_weeks_has_time_frames.week_day_id', $week_day_ids)
            ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
            ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))
            ->where('roster_has_weeks_has_time_frames.start_date', '<=', $end_date)
            ->where('roster_has_weeks_has_time_frames.end_date', '>=', $start_date);
        if (isset($quarterCheckFlag) && $quarterCheckFlag == 1) {
            $time_frames = $time_frames->whereRaw($whereQuarter);
        }
        $time_frames = $time_frames->get(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_dates.start_date', 'roster_has_dates.end_date', 'roster_has_dates.week_day_id', 'roster_has_weeks_has_time_frames.id as r_id', 'roster_has_dates.to_time as roster_to_time']); //roster_to_time Added on 3-march-23 for last slot should not come

        $response = [];
        $msg =  __('api.ERR_TIME_FRAME_NOT_FOUND');
        $current_time = date("H:i", time());
        $morning_time = date("H:i", mktime(12, 0));
        $today_date = date("Y-m-d", time());
        $ignore_time_slots = [];
        if (!empty($time_frames) && count($time_frames) > 0) {
            $msg = '';
            foreach ($time_frames as $time_frame) {
                $roster_time_slots_date_wise[$time_frame->date]['weekday'] = $this->WeekDaysModel->where('id', $time_frame->week_day_id)->pluck('day')->first();
                $response['duration'] = $default_time_duration;
                $time = date("H:i", strtotime($time_frame->time_frame));
                $added_time_frame =  date("H:i", strtotime($time) + $appointmentDuration);
                $selected = "";
                $t = Carbon::parse($time)->format('H:i');
                $ft = Carbon::parse($from_time)->format('H:i');
                $to = Carbon::parse($to_time)->format('H:i');

                //Added on 3-march-23 for last slot should not come
                $roster_to_time = date("H:i", strtotime($time_frame->roster_to_time));

                if ($t >= $ft && $t <= $to) {
                    $doctor_appointment_time_frames = $this->BaseModel
                        ->where('doctor_id', $doctor_id)
                        ->whereDate('start_date', $time_frame->date)
                        ->whereStatus(1)
                        ->select(DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date,(DATE_FORMAT(end_date,'%H:%i')) as end_date"))
                        ->get();
                    if (!empty($doctor_appointment_time_frames)) {
                        foreach ($doctor_appointment_time_frames as $doctor_appointment_time_frame) {
                            if (strtotime($time) < strtotime($doctor_appointment_time_frame->start_date) && strtotime($added_time_frame) > strtotime($doctor_appointment_time_frame->end_date)) {
                                $ignore_time_slots[$time_frame->date][] = $time;
                            }
                            if ($time == $doctor_appointment_time_frame->start_date || ($added_time_frame > $doctor_appointment_time_frame->start_date && $added_time_frame <= $doctor_appointment_time_frame->end_date)) {
                                $ignore_time_slots[$time_frame->date][] = $time;
                            }
                            if (($time >= $doctor_appointment_time_frame->start_date && $time < $doctor_appointment_time_frame->end_date)) {
                                $ignore_time_slots[$time_frame->date][] = $time;
                            }
                        }
                    }

                    // Added on 3-march-23 for last slot should not come
                    if ($added_time_frame > $roster_to_time) {
                        $ignore_time_slots[$time_frame->date][] = $time;
                    }

                    if (array_key_exists($time_frame->date, $ignore_time_slots)) {
                        if (!in_array($time, $ignore_time_slots[$time_frame->date])) {
                            if (strtotime($today_date) == strtotime($time_frame->date) && ($time >= $current_time)) {
                                $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                            } elseif (strtotime($today_date) !== strtotime($time_frame->date)) {
                                $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                            }
                        }
                    } else {
                        if (strtotime($today_date) == strtotime($time_frame->date) && ($time >= $current_time)) {
                            $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                            $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                        } elseif (strtotime($today_date) != strtotime($time_frame->date)) {
                            $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                            $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                        }
                    }
                    if (!empty($roster_time_slots_date_wise[$time_frame->date]['time_slots'])) {
                        $roster_time_slots_date_wise[$time_frame->date]['time_slots'] = array_unique($roster_time_slots_date_wise[$time_frame->date]['time_slots']);
                    }
                }
            }
        }
        if (!empty($roster_time_slots_date_wise)) {
            ksort($roster_time_slots_date_wise);
        }
        $html = '<table id="customers">
                    <thead>
                        <tr class="custMobThead">
                            <th width="50%">Datum</th>
                            <th>Uhrzeit</th>
                            <th>&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>

                    ';
        $msg =  __('admin.ERR_TIME_FRAME_NOT_FOUND');
        $index_key = 0;
        if (!empty($roster_time_slots_date_wise) && count($roster_time_slots_date_wise) > 0) {
            foreach ($roster_time_slots_date_wise as $roster_date => $roster_time_slot) {
                if (!empty($roster_time_slot['time_slots']) && sizeof($roster_time_slot['time_slots']) > 0) {
                    $select_rosters = '<div class="custMobileVisible"></div><select
                                        name="time_slot_' . $index_key . '" onChange="gettimetoradiobutton(' . $index_key . ')"
                                        id="time_slot_' . $index_key . '"
                                        class="form-control select2 radiobuttontimeslot"
                                        data_time_timeslot="' . $index_key . '"
                                        >';
                    sort($roster_time_slot['time_slots']);
                    foreach ($roster_time_slot['time_slots'] as $key => $time_slot) {

                        $select_rosters .= '<option data-dr="single doctor" value="' . $time_slot . '" lang="' . $roster_time_slot['time_slots_id'][$time_slot] . '">' . $time_slot . '</option>';
                    }
                    $select_rosters .= '</select>';
                    $html .= '<tr>
                                    <td class="right2"><div class="custMobileVisible"></div><b>' . $roster_time_slot['weekday'] . '</b>, ' . date('d.m.Y', strtotime($roster_date)) . '</td>
                                    <td width="100%">' . $select_rosters . '</td>
                                    <td  class="card-footer"><input type="radio" class="select_appointment"  id="select_appointment_' . $index_key . '" name="select_appointment" data-select_appointment_date=' . date('Y-m-d', strtotime($roster_date)) . ' value="" onChange="getradioselectdateTime(' . $index_key . ')" class="selectradiobutton" data_time_timeslotradio="' . $index_key . '" ><br>
                                    </td>
                                </tr>';
                    $index_key++;
                }
            }
        } else {
            $html .= '<tr>
                            <td class="right2" colspan="3"><b>' . $msg . '</b></td>
                        </tr>';
        }
        $html .= '<input type="hidden" id="time_fram_hd_id" name="time_fram_hd_id" value=""></tbody></table>';
        $this->JsonData['html'] = $html;
        $this->JsonData['data'] = $roster_time_slots_date_wise;
        return $html;
    } //
    //Added by swapnil on 15-sept-22
    public function optimalAppointmentCheck($doctor_id, $patient_id, $optimal_appointment, $description, $setting_value)
    {
        $avaliable_date = $endDate = '';
        $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
        $doctor_id = $doctor_id;
        $todaysdate = date('Y-m-d');
        $flag_set = array('0', '1');
        $patient_id = $patient_id;
        $quarter_setting = $optimal_appointment;
        $month = date("n");
        $Quarter = ceil($month / 3);
        $year = date("Y");
        $description =  $description;
        $setting_value = $setting_value;
        $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$doctor_id and STATUS=1 AND start_date>='$todaysdate'");
        $appointdatesarr = isset($getStartDates[0]->appointment_dates) ? $getStartDates[0]->appointment_dates : 0;
        if ($quarter_setting == 1) {
            for ($i = $Quarter; $i <= 6; $i++) {
                $j = $i;
                $quarters = [5 => 1, 6 => 2, 7 => 3, 8 => 4];
                if (in_array($i, [5, 6, 7, 8])) {
                    $j = $quarters[$i];
                    $year = date("Y", strtotime("+1 year"));
                }
                $time_slots = [];
                $check_appointment_exists = $this->BaseModel->whereRaw("quarter(start_date)=$j and year(start_date)=$year")
                    // ->where('doctor_id', $doctor_id) //commented on 14-09-2022
                    ->where('patient_id', $patient_id)->where('status', 1)
                    // ->where('appointment_status','!=','Verpasst')   // Added on 21-sept-22

                    // ->where('appointment_status', '!=', 'Vermisst')   // Added on 1-feb-24 //Roshani hidden the line on 15-april-25 for point Trello 281
                    ->get()->toArray();
                if (empty($check_appointment_exists)) {
                    $time_slots = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                        $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")
                            ->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")
                            ->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
                    })->where('roster.doctor_id', $doctor_id)
                        ->where('roster_has_dates.is_excluded', '=', 0)
                        ->where('roster_has_dates.date', '>=', $todaysdate)
                        ->whereRaw("quarter(date)=$j and year(date)=$year")
                        ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                        ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))
                        ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")
                        // ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN (SELECT start_date FROM appointment where doctor_id=$doctor_id and STATUS=1))")
                        ->groupBy('roster_has_dates.date')->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);
                }
                //dd($time_slots);
                if (!empty($time_slots) && isset($time_slots)) {
                    $time_slots = $time_slots->toArray();
                    $avaliable_date1 = $time_slots['date'];
                    $calender_date1 = $avaliable_date1;
                    $calender_date = date("Y/m/d", strtotime($calender_date1));
                    if ($description == "week") {
                        $newdate = date('Y/m/d', strtotime($calender_date . ' + ' . $setting_value . ' week'));
                    } elseif ($description == "month") {
                        $newdate = date('Y/m/d', strtotime($calender_date . ' + ' . $setting_value . ' month'));
                    }
                    $now = strtotime($calender_date);
                    $your_date = strtotime($newdate);
                    $datediff = $your_date - $now;
                    $no_of_days = round($datediff / (60 * 60 * 24));
                    return  $result = ['calender_date1' => $calender_date, 'no_of_days' => $no_of_days, 'status' => '2', 'calender_date2' => $newdate];
                }
            }
        }
    } //

    //ENd Smart Appoitment 20-Aug-22 Added by (Swapil)=======================



    //did changes on 23-feb-24 for import data
    public function importData_renamed_20_june_24(Request $request)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] =  __('admin.FAIL_ROLE_CREATE');
        $google_event_id = Null;
        $appointmentType = Null;

        $all_transactions = [];

        try {
            //fk_ordination_id
            $filename = $request->file('select_file');
            // dump($filename);
            // dump($request->file('select_file')->getMimeType(),$request->file('select_file')->getClientOriginalExtension() );

            // $this->validate($request, [
            // 'select_file' => 'required|mimes:csv'
            //  ]);


            // $fileContents = file($filename->getPathname());

            //  $r = array_map("utf8_encode", $fileContents);

            //dd($fileContents);

            if (!file_exists($filename) || !is_readable($filename))
                return false;
            $header = null;
            $data = array();
            if (($handle = fopen($filename, 'r')) !== false) {
                // dump('innnn');
                while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                    //   dump($row);
                    /* if (!$header){
                        $header = $row;     
                    }
                    else
                    {*/


                    $tmp = [];
                    // $tmp['family_name']  = mb_convert_encoding($row[0], 'UTF-8', 'UTF-8');
                    // $tmp['first_name']   = mb_convert_encoding($row[1], 'UTF-8', 'UTF-8');
                    $tmp['email']        = mb_convert_encoding($row[0], 'UTF-8', 'UTF-8');
                    $tmp['mobile_no']    = mb_convert_encoding($row[1], 'UTF-8', 'UTF-8');


                    if (!empty($row[4])) {
                        $tmp['birth_date']  = date('Y-m-d', strtotime($row[2]));
                        $tmp['age']         = (date('Y') - date('Y', strtotime($tmp['birth_date'])));
                    } else {
                        $tmp['birth_date']  = null;
                        $tmp['age']         = 0;
                    }
                    $tmp['start_date']  = date('Y-m-d H:i', strtotime($row[3]));


                    // $tmp['end_date']    = date('Y-m-d', strtotime($row[6]));
                    $tmp['doctor_first_name'] = mb_convert_encoding($row[4], 'UTF-8', 'UTF-8');
                    $tmp['doctor_last_name'] = mb_convert_encoding($row[5], 'UTF-8', 'UTF-8');
                    $tmp['appointment_type'] = $row[6];
                    $tmp['created_at'] = date('Y-m-d H:i:s');

                    //dump($tmp);

                    $patient_id =  DB::table("patients")
                        // ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($tmp['family_name']))
                        // ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($tmp['first_name']))
                        ->whereDate('birth_date', date('Y-m-d', strtotime($tmp['birth_date'])))
                        ->where('mobile_no', $tmp['mobile_no'])
                        ->whereNULL('deleted_at')
                        ->orderBy('created_at', 'DESC')
                        ->pluck('id')
                        ->first();


                    if (!empty($patient_id)) {
                        // dump('in not empty patient id');

                        $doctor_id   = Null;
                        $doctorName  = Null;
                        $notes       = Null;
                        $doctor_email = Null;
                        $color_id    = 0;

                        $isDoctorRecongnized = 0;

                        $getDoctorId = DB::table("users")
                            ->where(DB::raw('upper(first_name)'), '=', mb_strtoupper($tmp['doctor_first_name']))
                            ->where(DB::raw('upper(last_name)'), '=', mb_strtoupper($tmp['doctor_last_name']))
                            ->first();

                        if (!empty($getDoctorId)) {
                            $isDoctorRecongnized = 1;

                            $doctor_id    = $getDoctorId->id;
                            $doctorName   = $getDoctorId->first_name . " " . $getDoctorId->last_name;
                            $doctor_email = $getDoctorId->email;
                            $color_id     = $getDoctorId->google_color_id;
                        } else {
                            $isDoctorRecongnized = 0;


                            $getEmergencyDoctor = DB::table("users")->where('first_name', 'Doctor')->where('last_name', 'Emergency')->first();

                            if (isset($getEmergencyDoctor) && !empty($getEmergencyDoctor)) {
                                $doctor_id    = $getEmergencyDoctor->id;
                                $doctorName   = $getEmergencyDoctor->first_name . " " . $getEmergencyDoctor->last_name;
                                $doctor_email = $getEmergencyDoctor->email;
                                $color_id     = $getEmergencyDoctor->google_color_id;
                            } else {
                                //dump('in doctor not available');

                                $usersCollection = new AdminUserModel;
                                $usersCollection->first_name = 'Doctor';
                                $usersCollection->last_name = 'Emergency';
                                $usersCollection->email = 'emergency@gmail.com';
                                $usersCollection->google_color_id = 4;
                                $usersCollection->doctor_speciality = 'Emergency Doctor';
                                $usersCollection->status = 0;
                                $usersCollection->save();

                                $usersCollection->assignRole(strtolower('Doctor'));
                                $doctor_id = $usersCollection->id;
                                //dump($doctor_id);

                            } //else           
                        }

                        $tmp['doctor_id'] = $doctor_id;
                        $tmp['isDoctorRecongnized'] = $isDoctorRecongnized;


                        //Appoinment added in google calendar
                        // $patientName = $tmp['first_name']." ".$tmp['family_name'];
                        $appointment_type_id = 7;
                        $appointmentTypeName = $this->AppointmentTypesModel->where('name', $tmp['appointment_type'])->first();
                        if (isset($appointmentTypeName) && !empty($appointmentTypeName)) {
                            $appointment_type_id = $appointmentTypeName->id;
                        }

                        $tmp['appointment_type_id'] = $appointment_type_id;

                        $tmp['end_date']  = self::_getEndDate($tmp['start_date'], $appointment_type_id);
                        //dump($tmp);


                        $duplicationAppointmantself =  $this->_checkDuplicatedAppointment($tmp, '');

                        // dump("duplicationAppointmantself ===> ");
                        // dump($duplicationAppointmantself);


                        if (empty($duplicationAppointmantself) && sizeof($duplicationAppointmantself) == 0) {

                            $app['appointment_status'] = "";

                            $todayDate = date('Y-m-d');
                            $newdate = date("Y-m-d", strtotime($tmp['start_date']));
                            if ($newdate < $todayDate) {
                                $app['appointment_status'] = 'Fertig';
                            }
                            $app['patient_id']      = $patient_id;
                            $app['doctor_id']       = $doctor_id;
                            $app['appointment_type_id'] = $appointment_type_id;
                            $app['notes'] = "";
                            $app['start_date']      = $tmp['start_date'];
                            $app['end_date']        = $tmp['end_date'];
                            $app['status']          = 1;
                            $appointmentData = AppointmentModel::create($app);

                            //dump($appointmentData->id);
                            /*************update the time frame***************/

                            $time_frame = Date('H:i:s', strtotime($tmp['start_date']));
                            $sdate = Date('Y-m-d', strtotime($tmp['start_date']));

                            if ($isDoctorRecongnized == 1) {
                                $isExists_time_frame = $this->RosterModel
                                    ->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')
                                    // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                    ->join('roster_has_weeks_has_time_frames', 'roster_has_weeks_has_time_frames.roster_id', 'roster_has_dates.roster_id')
                                    ->where('roster.doctor_id', $doctor_id)
                                    ->where('roster_has_dates.is_excluded', '=', 0)
                                    ->whereDate('roster_has_dates.date', $sdate)
                                    ->where('roster_has_weeks_has_time_frames.time_frame', '=', $time_frame)
                                    ->first(['roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id']);

                                //  dump("after isExists_time_frame");
                                // dump($isExists_time_frame);

                                if (isset($isExists_time_frame) && !empty($isExists_time_frame)) {
                                    $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                        ->where('id', $isExists_time_frame->id)
                                        ->update([
                                            'time_frame_flag' => '2',
                                            'time_frame_flag_date' => Date('Y-m-d H:i:s'),
                                            'comment' => 'AppointmentController store booking function app Date:' . date('Y-m-d H:i:s', strtotime($appointmentData->start_date)) . ' current date:' . Date('Y-m-d H:i:s') . ' patient_id: ' . $patient_id
                                        ]);
                                } //if    
                            } //if isDoctorRecongnized




                            /************update the time frame**************/

                            $services_input = array('patient_id' => $patient_id, 'appointment_type_id' => $appointment_type_id, 'a_id' => '');

                            //  dump("mergedArray ===>");                  
                            $mergedArray = $this->getServices_import($services_input);
                            // dump($mergedArray);


                            self::_activateReminderOnEdit($appointmentData);

                            self::_deactivateReminderNew($appointmentData, $mergedArray);

                            $getDocument = self::_GetAssignedDocument($appointmentData->id, $appointmentData->appointment_type_id, $mergedArray, $appointmentData->patient_id);

                            $getDocument = self::_GetAssignedCheckList($appointmentData->id, $mergedArray, $appointmentData->patient_id);


                            $collection = $this->BaseModel->with(['assignedPatient', 'assignedDoctor', 'assignedAppointmentType'])->find($appointmentData->id);
                            $patientName = $collection->assignedPatient->first_name . " " . $collection->assignedPatient->family_name;
                            $doctorName = $collection->assignedDoctor->first_name . " " . $collection->assignedDoctor->last_name;
                            $appointmentType = $collection->assignedAppointmentType->name;
                            $booking_month = __('admin.' . date('F', strtotime($tmp['start_date'])), [], 'de');
                            $appointmentTime = date('d', strtotime($tmp['start_date'])) . '.' . $booking_month . ", um " . date('H:i', strtotime($tmp['start_date'])) . " Uhr.";

                            $patientText = $collection->assignedPatient->salutation ? " " . $collection->assignedPatient->salutation . '.' : ""; //added dot after salutation 

                            $patientText .= " " . $collection->assignedPatient->first_name . ' ' . $collection->assignedPatient->family_name;

                            $doctorSurname = $collection->assignedDoctor->last_name;



                            /********added code on 13-feb-24***for notification from setting section*******/

                            $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($tmp['start_date'])));

                            $getSetting  = $this->AppointmentHasNotificationModel->whereStatus(3)->first();
                            if (isset($getSetting) && !empty($getSetting)) {
                                // dump("in getsetting");

                                $title = $getSetting->title;
                                $content = $getSetting->content;
                                $day = $getSetting->day;
                                $notify_time = $getSetting->notify_time;
                                $appointmentDate =  date("Y-m-d", strtotime($tmp['start_date']));


                                if ($day == 0) //current day
                                {
                                    $req_notify_time   = explode(" ", $getSetting->notify_time);
                                    $req_notify_time_in_seconds = $req_notify_time[1];
                                    $app_notify_time   = date("Y-m-d H:i:s", strtotime($appointmentDate . " " .
                                        $req_notify_time_in_seconds));
                                } else {
                                    //previous day
                                    $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($tmp['start_date'])));
                                    $req_notify_time   = explode(" ", $getSetting->notify_time);
                                    $req_notify_time_in_seconds = $req_notify_time[1];
                                    $app_notify_time = date("Y-m-d H:i:s", strtotime($previous_day . " " .
                                        $req_notify_time_in_seconds));
                                }

                                $content = str_replace("##PATIENT_NAME##", $patientText, $content);
                                $content = str_replace("##DOCTOR_SURNAME##", $doctorSurname, $content);
                                $content = str_replace("##APPOINTMENT_TYPE##", $appointmentType, $content);
                                $content = str_replace("##DATE_TIME##", $appointmentTime, $content);
                            } //if isset getsetting
                            else {
                                $title = 'Erinnerung an Ihren Termin';
                                $content = 'Hallo' . $patientText . ', ihr Termin mit Dr. ' . (string)$doctorSurname . ' (' . $appointmentType . ') ist am' . ' ' . (string)$appointmentTime;
                                $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($tmp['start_date'])));
                            }

                            $notify_data = array(
                                'patient_id' => $patient_id,
                                'appointment_id' => $appointmentData->id,
                                'title' => $title,
                                'content' => $content,
                                'notify_time' => $app_notify_time,
                                'status' => 0,
                            );

                            if ($this->AppointmentHasNotificationModel->insert($notify_data)) {
                                $all_transactions[] = 1;
                            } else {
                                $all_transactions[] = 0;
                            }


                            /*****end code**of notification setting****13-feb-24******/

                            //Default appintment
                            $getServises = $this->appointmentTypesAgaintsServices_import($appointmentData->id, $mergedArray, $patient_id);

                            $serviceEventType = $this->GetServicesEventType_import($appointmentData->id, $patient_id, $mergedArray, $appointmentData->appointment_type_id, 'admin');


                            $summary = $patientName . " - " . $appointmentType;
                            $description = '<p><strong>' . $this->patientText . ':</strong> ' . $patientName . ' </p><p><strong>' . $this->doctorText . ':</strong> ' . $doctorName . ' </p><p><strong>' . $this->appointmentText . ':</strong> ' . $appointmentType . ' </p><p><strong>' . $this->startDateText . ':</strong> ' . date('F d,Y H:i', strtotime($tmp['start_date'])) . ' </p><strong>' . $this->endDateText . ':</strong> ' . date('F d,Y H:i', strtotime($tmp['end_date'])) . ' </p><p><strong>' . $this->notesText . ':</strong> ' . $notes . ' </p>';

                            $request = array(
                                'summary' => $summary,
                                'description' => $description,
                                'startDateTime' => $tmp['start_date'],
                                'endDateTime' => $tmp['end_date'],
                                'patient_id' => $patient_id,
                                'patient_email' => $tmp['email'],
                                'patient_name' => $patientName,
                                'doctor_email' => $doctor_email,
                                'color_id' => $color_id,
                            );

                            //commented below code on 31-oct-23 for create appoinemnt on local 
                            // request()->merge($request);

                            $postCalDetails = $this->eventStore_import($request);
                            //dump('after postCalDetails');
                            //dump($postCalDetails);

                            //$postResponse = json_decode($postCalDetails->data);
                            if (!empty($postCalDetails) && $postCalDetails->original['status'] == 'success') {
                                $all_transactions[] = 1;
                                $eventId = $postCalDetails->original['data']->id;

                                //  dump('event Id');
                                //  dump($eventId);

                                $updateEvent = $this->BaseModel
                                    ->where('id', $appointmentData->id)
                                    ->update(['google_event_id' => $eventId]);


                                if ($updateEvent) {
                                    $all_transactions[] = 1;
                                    $urlEventId = $eventId;
                                    $urlPatientId = $patient_id;
                                } else {
                                    $all_transactions[] = 0;
                                }


                                $debug_arr['data'] = 'has created appointment by AppointmentController';
                                $res_name = "AppointmentController_store";
                                self::debugModeappBookFun($debug_arr, $res_name);
                                $newData = $appointmentData->toArray();
                                $this->ActivityLogModel->addLog($this->ModuleTitle, 'has created appointment', 'Add', null, $newData);
                                //add reminders for pass appointments added by swati 9-Jun-23================================================
                                $newdate = date("Y-m-d", strtotime($request['startDateTime']));
                                $todayDate = date('Y-m-d');
                                if ($newdate < $todayDate) {
                                    $this->_remindersPassAppointments($appointmentData->id);
                                }
                                //==============================================
                            } else {
                                $all_transactions[] = 0;
                                DB::rollback();
                            }



                            $all_transactions[] = 1;
                        } //if empty duplicated appointment 
                        else {
                            $all_transactions[] = 0;
                        }

                        $all_transactions[] = 1;
                    } //if not empty patient id
                    else {
                        $all_transactions[] = 0;
                    }
                    // }
                } //while           
            } //if handle

            if (!in_array(0, $all_transactions)) {
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath . 'index');
                $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
            } else {
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['msg']    = __('admin.ERR_IMPORT_APPOINTMENT');
            }

            return response()->json($this->JsonData);
        } catch (Exception $e) {
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.ERR_SOMETHING_WRONG');
        }
    } //importData 
    public function importData_25_june_24_working(Request $request)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] =  __('admin.FAIL_ROLE_CREATE');
        $google_event_id = Null;
        $appointmentType = Null;

        $all_transactions = [];

        try {
            //fk_ordination_id
            $filename = $request->file('select_file');


            // Get the file extension
            $extension = $filename->getClientOriginalExtension();

            // Check the file extension
            if (!in_array($extension, ['csv', 'txt'])) {
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['msg']    = __('admin.ERR_CSV_IMPORT');
                return response()->json($this->JsonData);
            } //if


            if (!file_exists($filename) || !is_readable($filename))
                return false;

            $header = null;
            $data = array();
            if (($handle = fopen($filename, 'r')) !== false) {
                // dump('innnn');
                while (($row = fgetcsv($handle, 1000, ';')) !== false) {

                    $tmp = [];
                    $patientImportArr = [];

                    /*if (count($row) < 10) {
                            continue;
                        }*/


                    $tmp['family_name']  = $row[0];
                    $tmp['first_name']   = $row[1];
                    $tmp['email']        = $row[2];
                    $tmp['country_code'] = $row[3];
                    $tmp['mobile_no']    = str_replace(' ', '', $row[4]);

                    if (!empty($row[5])) {
                        $tmp['birth_date']  = date('Y-m-d', strtotime($row[5]));
                        $tmp['age']         = (date('Y') - date('Y', strtotime($tmp['birth_date'])));
                    }


                    $tmp['start_date']  = date('Y-m-d H:i', strtotime($row[6]));

                    // $tmp['end_date']    = date('Y-m-d', strtotime($row[6]));
                    $tmp['doctor_first_name'] = $row[7];
                    $tmp['doctor_last_name'] = $row[8];
                    $tmp['appointment_type'] = $row[9];
                    $tmp['created_at'] = date('Y-m-d H:i:s');

                    //dump($tmp);

                    $patient_id =  DB::table("patients")
                        // ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($tmp['family_name']))
                        // ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($tmp['first_name']))
                        ->whereDate('birth_date', date('Y-m-d', strtotime($tmp['birth_date'])))
                        ->where('mobile_no', $tmp['mobile_no'])
                        ->whereNULL('deleted_at')
                        ->orderBy('created_at', 'DESC')
                        ->pluck('id')
                        ->first();

                    // dump($patient_id);

                    $patientImportArr['first_name'] = isset($tmp['first_name']) ? $tmp['first_name'] : '';
                    $patientImportArr['family_name'] = isset($tmp['family_name']) ? $tmp['family_name'] : '';
                    $patientImportArr['birth_date'] = isset($tmp['birth_date']) ? $tmp['birth_date'] : '';
                    $patientImportArr['age'] = isset($tmp['age']) ? $tmp['age'] : '';
                    $patientImportArr['email'] = isset($tmp['email']) ? $tmp['email'] : '';
                    $patientImportArr['mobile_no'] = isset($tmp['mobile_no']) ? $tmp['mobile_no'] : '';
                    $patientImportArr['country_code'] = isset($tmp['country_code']) ? $tmp['country_code'] : '';


                    if (empty($patient_id) && isset($patientImportArr)) {
                        $patient_id = DB::table('patients')->insertGetId($patientImportArr);
                        if (isset($patient_id)) {
                            $setReminders = app('App\Http\Controllers\Admin\DashboardController')->checkPatientAgeReminder($patient_id);
                        } //if patient id

                        $parentPatientId =  DB::connection('system')
                            ->table("patients")
                            ->whereDate('birth_date', date('Y-m-d', strtotime($tmp['birth_date'])))
                            ->where('mobile_no', $tmp['mobile_no'])
                            ->whereNULL('deleted_at')
                            ->orderBy('created_at', 'DESC')
                            ->pluck('id')
                            ->first();

                        $ordination_id = Config('ordination_id');
                        if (!empty($parentPatientId)) {

                            $assign_ordination = DB::connection('system')
                                ->table("patients_has_ordination")
                                ->where([
                                    'fk_ordination_id' => $ordination_id,
                                    'fk_patient_id' => $parentPatientId
                                ])
                                ->count();

                            if ($assign_ordination > 0) {
                                $checkInsertion = DB::connection('system')
                                    ->table("patients_has_ordination")
                                    ->insert([
                                        'fk_ordination_id' => $ordination_id,
                                        'fk_patient_id' => $parentPatientId,
                                        'status' => '1'
                                    ]);
                            }
                            DB::commit();
                        } else {
                            $parentPatientInsertedId = DB::connection('system')
                                ->table("patients")->insertGetId($patientImportArr);

                            $checkInsertion = DB::connection('system')
                                ->table("patients_has_ordination")
                                ->insert([
                                    'fk_ordination_id' => $ordination_id,
                                    'fk_patient_id' => $parentPatientInsertedId,
                                    'status' => '1'
                                ]);
                            DB::commit();
                        }
                    } //if empty patient id
                    else {
                        $patient_id = $patient_id;
                    } //else

                    // dump("patient_id====>");
                    // dump($patient_id);

                    if (!empty($patient_id)) {
                        // dump('in not empty patient id');

                        $doctor_id   = Null;
                        $doctorName  = Null;
                        $notes       = Null;
                        $doctor_email = Null;
                        $color_id    = 0;

                        $isDoctorRecongnized = 0;

                        $getDoctorId = DB::table("users")
                            ->where(DB::raw('upper(first_name)'), '=', mb_strtoupper($tmp['doctor_first_name']))
                            ->where(DB::raw('upper(last_name)'), '=', mb_strtoupper($tmp['doctor_last_name']))
                            ->first();

                        //commented on 21-june-24

                        /* if(!empty($getDoctorId))
                            {
                                $isDoctorRecongnized=1;

                                $doctor_id    = $getDoctorId->id;
                                $doctorName   = $getDoctorId->first_name." ".$getDoctorId->last_name;
                                $doctor_email = $getDoctorId->email;
                                $color_id     = $getDoctorId->google_color_id;
                            }
                            else
                            {
                                $isDoctorRecongnized =0;
                                
                                $getEmergencyDoctor = DB::table("users")->where('first_name','Doctor')->where('last_name','Emergency')->first();

                                if(isset($getEmergencyDoctor) && !empty($getEmergencyDoctor))
                                {
                                    $doctor_id    = $getEmergencyDoctor->id;
                                    $doctorName   = $getEmergencyDoctor->first_name." ".$getEmergencyDoctor->last_name;
                                    $doctor_email = $getEmergencyDoctor->email;
                                    $color_id     = $getEmergencyDoctor->google_color_id;
                                }
                                else
                                {
                                    //dump('in doctor not available');

                                    $usersCollection = new AdminUserModel;
                                    $usersCollection->first_name = 'Doctor';
                                    $usersCollection->last_name = 'Emergency';
                                    $usersCollection->email = 'emergency@gmail.com';
                                    $usersCollection->google_color_id = 4;
                                    $usersCollection->doctor_speciality = 'Emergency Doctor';
                                    $usersCollection->status = 0;
                                    $usersCollection->save();

                                    $usersCollection->assignRole(strtolower('Doctor'));
                                    $doctor_id = $usersCollection->id;
                                    //dump($doctor_id);

                                }//else      
                                


                            }//else of doctor not exists
                            */

                        if (!empty($getDoctorId)) {

                            if ($getDoctorId->status == 1) {
                                $isDoctorRecongnized = 1;
                            } else {
                                $isDoctorRecongnized = 0;
                            }

                            $doctor_id    = $getDoctorId->id;
                            $doctorName   = $getDoctorId->first_name . " " . $getDoctorId->last_name;
                            $doctor_email = $getDoctorId->email;
                            $color_id     = $getDoctorId->google_color_id;
                        } //if not empty doctor id
                        else {

                            $isDoctorRecongnized = 0;

                            $getEmergencyDoctor = DB::table("users")->where('first_name', 'Doctor')->where('last_name', 'Emergency')->first();

                            if (isset($getEmergencyDoctor) && !empty($getEmergencyDoctor)) {
                                $doctor_id    = $getEmergencyDoctor->id;
                                $doctorName   = $getEmergencyDoctor->first_name . " " . $getEmergencyDoctor->last_name;
                                $doctor_email = $getEmergencyDoctor->email;
                                $color_id     = $getEmergencyDoctor->google_color_id;
                            } else {
                                //dump('in doctor not available');

                                $usersCollection = new AdminUserModel;
                                $usersCollection->first_name = 'Doctor';
                                $usersCollection->last_name = 'Emergency';
                                $usersCollection->email = 'emergency@gmail.com';
                                $usersCollection->google_color_id = 4;
                                $usersCollection->doctor_speciality = 'Emergency Doctor';
                                $usersCollection->status = 0;
                                $usersCollection->save();

                                $usersCollection->assignRole(strtolower('Doctor'));
                                $doctor_id = $usersCollection->id;
                                //dump($doctor_id);

                            } //else      

                        } //else of doctor not exists


                        $tmp['doctor_id'] = $doctor_id;
                        $tmp['isDoctorRecongnized'] = $isDoctorRecongnized;


                        //Appoinment added in google calendar
                        // $patientName = $tmp['first_name']." ".$tmp['family_name'];
                        $appointment_type_id = 7;
                        $appointmentTypeName = $this->AppointmentTypesModel->where('name', $tmp['appointment_type'])->first();
                        if (isset($appointmentTypeName) && !empty($appointmentTypeName)) {
                            $appointment_type_id = $appointmentTypeName->id;
                        }

                        $tmp['appointment_type_id'] = $appointment_type_id;

                        $tmp['end_date']  = self::_getEndDate($tmp['start_date'], $appointment_type_id);

                        // dump("tmp====>");
                        // dump($tmp);

                        $duplicationAppointmantself =  $this->_checkDuplicatedAppointment($tmp, '');

                        // dump("duplicationAppointmantself ===> ");
                        // dump($duplicationAppointmantself);


                        if (empty($duplicationAppointmantself) && sizeof($duplicationAppointmantself) == 0) {

                            $app['appointment_status'] = "";

                            $todayDate = date('Y-m-d');
                            $newdate = date("Y-m-d", strtotime($tmp['start_date']));
                            if ($newdate < $todayDate) {
                                $app['appointment_status'] = 'Fertig';
                            }
                            $app['patient_id']      = $patient_id;
                            $app['doctor_id']       = $doctor_id;
                            $app['appointment_type_id'] = $appointment_type_id;
                            $app['notes'] = "";
                            $app['start_date']      = $tmp['start_date'];
                            $app['end_date']        = $tmp['end_date'];
                            $app['status']          = 1;

                            //dump($app);

                            $appointmentData = AppointmentModel::create($app);

                            //dump($appointmentData->id);
                            /*************update the time frame***************/

                            $time_frame = Date('H:i:s', strtotime($tmp['start_date']));
                            $sdate = Date('Y-m-d', strtotime($tmp['start_date']));

                            if ($isDoctorRecongnized == 1) {
                                $isExists_time_frame = $this->RosterModel
                                    ->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')
                                    // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                    ->join('roster_has_weeks_has_time_frames', 'roster_has_weeks_has_time_frames.roster_id', 'roster_has_dates.roster_id')
                                    ->where('roster.doctor_id', $doctor_id)
                                    ->where('roster_has_dates.is_excluded', '=', 0)
                                    ->whereDate('roster_has_dates.date', $sdate)
                                    ->where('roster_has_weeks_has_time_frames.time_frame', '=', $time_frame)
                                    ->first(['roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id']);

                                //  dump("after isExists_time_frame");
                                // dump($isExists_time_frame);

                                if (isset($isExists_time_frame) && !empty($isExists_time_frame)) {
                                    $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                        ->where('id', $isExists_time_frame->id)
                                        ->update([
                                            'time_frame_flag' => '2',
                                            'time_frame_flag_date' => Date('Y-m-d H:i:s'),
                                            'comment' => 'AppointmentController store booking function app Date:' . date('Y-m-d H:i:s', strtotime($appointmentData->start_date)) . ' current date:' . Date('Y-m-d H:i:s') . ' patient_id: ' . $patient_id
                                        ]);
                                } //if    
                            } //if isDoctorRecongnized




                            /************update the time frame**************/

                            $services_input = array('patient_id' => $patient_id, 'appointment_type_id' => $appointment_type_id, 'a_id' => '');

                            //  dump("mergedArray ===>");                  
                            $mergedArray = $this->getServices_import($services_input);
                            // dump($mergedArray);


                            self::_activateReminderOnEdit($appointmentData);

                            self::_deactivateReminderNew($appointmentData, $mergedArray);

                            $getDocument = self::_GetAssignedDocument($appointmentData->id, $appointmentData->appointment_type_id, $mergedArray, $appointmentData->patient_id);

                            $getDocument = self::_GetAssignedCheckList($appointmentData->id, $mergedArray, $appointmentData->patient_id);


                            $collection = $this->BaseModel->with(['assignedPatient', 'assignedDoctor', 'assignedAppointmentType'])->find($appointmentData->id);
                            $patientName = $collection->assignedPatient->first_name . " " . $collection->assignedPatient->family_name;
                            $doctorName = $collection->assignedDoctor->first_name . " " . $collection->assignedDoctor->last_name;
                            $appointmentType = $collection->assignedAppointmentType->name;
                            $booking_month = __('admin.' . date('F', strtotime($tmp['start_date'])), [], 'de');
                            $appointmentTime = date('d', strtotime($tmp['start_date'])) . '.' . $booking_month . ", um " . date('H:i', strtotime($tmp['start_date'])) . " Uhr.";

                            $patientText = $collection->assignedPatient->salutation ? " " . $collection->assignedPatient->salutation . '.' : ""; //added dot after salutation 

                            $patientText .= " " . $collection->assignedPatient->first_name . ' ' . $collection->assignedPatient->family_name;

                            $doctorSurname = $collection->assignedDoctor->last_name;



                            /********added code on 13-feb-24***for notification from setting section*******/

                            $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($tmp['start_date'])));

                            $getSetting  = $this->AppointmentHasNotificationModel->whereStatus(3)->first();
                            if (isset($getSetting) && !empty($getSetting)) {
                                // dump("in getsetting");

                                $title = $getSetting->title;
                                $content = $getSetting->content;
                                $day = $getSetting->day;
                                $notify_time = $getSetting->notify_time;
                                $appointmentDate =  date("Y-m-d", strtotime($tmp['start_date']));


                                if ($day == 0) //current day
                                {
                                    $req_notify_time   = explode(" ", $getSetting->notify_time);
                                    $req_notify_time_in_seconds = $req_notify_time[1];
                                    $app_notify_time   = date("Y-m-d H:i:s", strtotime($appointmentDate . " " .
                                        $req_notify_time_in_seconds));
                                } else {
                                    //previous day
                                    $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($tmp['start_date'])));
                                    $req_notify_time   = explode(" ", $getSetting->notify_time);
                                    $req_notify_time_in_seconds = $req_notify_time[1];
                                    $app_notify_time = date("Y-m-d H:i:s", strtotime($previous_day . " " .
                                        $req_notify_time_in_seconds));
                                }

                                $content = str_replace("##PATIENT_NAME##", $patientText, $content);
                                $content = str_replace("##DOCTOR_SURNAME##", $doctorSurname, $content);
                                $content = str_replace("##APPOINTMENT_TYPE##", $appointmentType, $content);
                                $content = str_replace("##DATE_TIME##", $appointmentTime, $content);
                            } //if isset getsetting
                            else {
                                $title = 'Erinnerung an Ihren Termin';
                                $content = 'Hallo' . $patientText . ', ihr Termin mit Dr. ' . (string)$doctorSurname . ' (' . $appointmentType . ') ist am' . ' ' . (string)$appointmentTime;
                                $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($tmp['start_date'])));
                            }

                            $notify_data = array(
                                'patient_id' => $patient_id,
                                'appointment_id' => $appointmentData->id,
                                'title' => $title,
                                'content' => $content,
                                'notify_time' => $app_notify_time,
                                'status' => 0,
                            );

                            if ($this->AppointmentHasNotificationModel->insert($notify_data)) {
                                $all_transactions[] = 1;
                            } else {
                                $all_transactions[] = 0;
                            }


                            /*****end code**of notification setting****13-feb-24******/

                            //Default appintment
                            $getServises = $this->appointmentTypesAgaintsServices_import($appointmentData->id, $mergedArray, $patient_id);

                            $serviceEventType = $this->GetServicesEventType_import($appointmentData->id, $patient_id, $mergedArray, $appointmentData->appointment_type_id, 'admin');


                            $summary = $patientName . " - " . $appointmentType;
                            $description = '<p><strong>' . $this->patientText . ':</strong> ' . $patientName . ' </p><p><strong>' . $this->doctorText . ':</strong> ' . $doctorName . ' </p><p><strong>' . $this->appointmentText . ':</strong> ' . $appointmentType . ' </p><p><strong>' . $this->startDateText . ':</strong> ' . date('F d,Y H:i', strtotime($tmp['start_date'])) . ' </p><strong>' . $this->endDateText . ':</strong> ' . date('F d,Y H:i', strtotime($tmp['end_date'])) . ' </p><p><strong>' . $this->notesText . ':</strong> ' . $notes . ' </p>';

                            $request = array(
                                'summary' => $summary,
                                'description' => $description,
                                'startDateTime' => $tmp['start_date'],
                                'endDateTime' => $tmp['end_date'],
                                'patient_id' => $patient_id,
                                'patient_email' => $tmp['email'],
                                'patient_name' => $patientName,
                                'doctor_email' => $doctor_email,
                                'color_id' => $color_id,
                            );

                            //commented below code on 31-oct-23 for create appoinemnt on local 
                            // request()->merge($request);

                            $postCalDetails = $this->eventStore_import($request);
                            //dump('after postCalDetails');
                            //dump($postCalDetails);

                            //$postResponse = json_decode($postCalDetails->data);
                            if (!empty($postCalDetails) && $postCalDetails->original['status'] == 'success') {
                                $all_transactions[] = 1;
                                $eventId = $postCalDetails->original['data']->id;

                                //  dump('event Id');
                                //  dump($eventId);

                                $updateEvent = $this->BaseModel
                                    ->where('id', $appointmentData->id)
                                    ->update(['google_event_id' => $eventId]);


                                if ($updateEvent) {
                                    $all_transactions[] = 1;
                                    $urlEventId = $eventId;
                                    $urlPatientId = $patient_id;
                                } else {
                                    $all_transactions[] = 0;
                                }


                                $debug_arr['data'] = 'has created appointment by AppointmentController';
                                $res_name = "AppointmentController_store";
                                self::debugModeappBookFun($debug_arr, $res_name);
                                $newData = $appointmentData->toArray();
                                $this->ActivityLogModel->addLog($this->ModuleTitle, 'has created appointment', 'Add', null, $newData);
                                //add reminders for pass appointments added by swati 9-Jun-23================================================
                                $newdate = date("Y-m-d", strtotime($request['startDateTime']));
                                $todayDate = date('Y-m-d');
                                if ($newdate < $todayDate) {
                                    $this->_remindersPassAppointments($appointmentData->id);
                                }
                                //==============================================
                            } else {
                                $all_transactions[] = 0;
                                DB::rollback();
                            }



                            $all_transactions[] = 1;
                        } //if empty duplicated appointment 
                        else {
                            $all_transactions[] = 0;
                        }

                        $all_transactions[] = 1;
                    } //if not empty patient id
                    else {
                        $all_transactions[] = 0;
                    }
                } //while           
            } //if handle

            if (!in_array(0, $all_transactions)) {
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath . 'index');
                $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
            } else {
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['msg']    = __('admin.ERR_IMPORT_APPOINTMENT');
            }

            return response()->json($this->JsonData);
        } catch (Exception $e) {
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.ERR_SOMETHING_WRONG');
        }
    } //importData 


    public function validateCsv($filename)
    {
        $errors = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            $rowNumber = 0;
            while (($row = fgetcsv($handle, 1000, ';')) !== false) {

                $tmp = [];

                $tmp['family_name']  = isset($row[0]) ? $row[0] : '';
                $tmp['first_name']   = isset($row[1]) ? $row[1] : '';
                $tmp['email']        = isset($row[2]) ? $row[2] : '';
                $tmp['country_code'] = isset($row[3]) ? $row[3] : '';
                $tmp['mobile_no']    = isset($row[4]) ? str_replace(' ', '', $row[4]) : '';

                if (!empty($row[5])) {
                    $tmp['birth_date']  = date('Y-m-d', strtotime($row[5]));
                    $tmp['age']         = (date('Y') - date('Y', strtotime($tmp['birth_date'])));
                }

                $tmp['start_date']  = isset($row[6]) ? date('Y-m-d H:i', strtotime($row[6])) : '';
                $tmp['doctor_first_name'] = isset($row[7]) ? $row[7] : '';
                $tmp['doctor_last_name'] = isset($row[8]) ? $row[8] : '';
                $tmp['appointment_type'] = isset($row[9]) ? $row[9] : '';
                $tmp['created_at'] = date('Y-m-d H:i:s');

                //dump($tmp);

                $validator = Validator::make($tmp, [
                    'family_name' => 'required',
                    'first_name' => 'required',
                    'email' => 'required|email',
                    'country_code' => 'required',
                    'mobile_no' => 'required',
                    'birth_date' => 'required|date',
                    'start_date' => 'required|date',
                    'doctor_first_name' => 'required',
                    'doctor_last_name' => 'required',
                    'appointment_type' => 'required',
                ]);
                $rowNumber++;
                // Handle validation errors
                if ($validator->fails()) {

                    Log::info('in csv errors...');
                    // dump("Row $rowNumber: " . implode(', ', $validator->errors()->all()));
                    Log::info(implode(', ', $validator->errors()->all()));
                    // $errors[] = "Row $rowNumber: " . implode(', ', $validator->errors()->all());
                    $errors[] = $rowNumber;
                    Log::info("Row $rowNumber");
                }
            } //while           
        } //if handle

        if (!empty($errors)) {
            return $errors;
        }
    } //validateCsv
    //did changes on 25-june-24
    public function importData(Request $request)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] =  __('admin.FAIL_ROLE_CREATE');
        $google_event_id = Null;
        $appointmentType = Null;

        $all_transactions = [];

        try {
            //fk_ordination_id
            $filename = $request->file('select_file');


            // Get the file extension
            $extension = $filename->getClientOriginalExtension();

            // Check the file extension
            if (!in_array($extension, ['csv', 'txt'])) {
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['msg']    = __('admin.ERR_CSV_IMPORT');
                return response()->json($this->JsonData);
            } //if


            if (!file_exists($filename) || !is_readable($filename))
                return false;

            $header = null;
            $data = array();

            /**************************************/

            $errorsArr = $this->validateCsv($filename);
            if (isset($errorsArr) && !empty($errorsArr)) {
                Log::info('in csv errorsArr...');
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                // $this->JsonData['msg'] = implode('; ', $errorsArr);
                $this->JsonData['msg'] = __('admin.ERR_CSV_VALID_DATA') . implode('; ', $errorsArr);
                Log::info(implode('; ', $errorsArr));
                return response()->json($this->JsonData);
            }

            /*************************************/




            if (($handle = fopen($filename, 'r')) !== false) {
                // dump('innnn');
                while (($row = fgetcsv($handle, 1000, ';')) !== false) {

                    $tmp = [];
                    $patientImportArr = [];

                    /*if (count($row) < 10) {
                            continue;
                        }*/


                    $tmp['family_name']  = $row[0];
                    $tmp['first_name']   = $row[1];
                    $tmp['email']        = $row[2];
                    $tmp['country_code'] = $row[3];
                    $tmp['mobile_no']    = str_replace(' ', '', $row[4]);

                    if (!empty($row[5])) {
                        $tmp['birth_date']  = date('Y-m-d', strtotime($row[5]));
                        $tmp['age']         = (date('Y') - date('Y', strtotime($tmp['birth_date'])));
                    }


                    $tmp['start_date']  = date('Y-m-d H:i', strtotime($row[6]));

                    // $tmp['end_date']    = date('Y-m-d', strtotime($row[6]));
                    $tmp['doctor_first_name'] = $row[7];
                    $tmp['doctor_last_name'] = $row[8];
                    $tmp['appointment_type'] = $row[9];
                    $tmp['created_at'] = date('Y-m-d H:i:s');

                    //dump($tmp);

                    $patient_id =  DB::table("patients")
                        // ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($tmp['family_name']))
                        // ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($tmp['first_name']))
                        ->whereDate('birth_date', date('Y-m-d', strtotime($tmp['birth_date'])))
                        ->where('mobile_no', $tmp['mobile_no'])
                        ->whereNULL('deleted_at')
                        ->orderBy('created_at', 'DESC')
                        ->pluck('id')
                        ->first();

                    // dump($patient_id);

                    $patientImportArr['first_name'] = isset($tmp['first_name']) ? $tmp['first_name'] : '';
                    $patientImportArr['family_name'] = isset($tmp['family_name']) ? $tmp['family_name'] : '';
                    $patientImportArr['birth_date'] = isset($tmp['birth_date']) ? $tmp['birth_date'] : '';
                    $patientImportArr['age'] = isset($tmp['age']) ? $tmp['age'] : '';
                    $patientImportArr['email'] = isset($tmp['email']) ? $tmp['email'] : '';
                    $patientImportArr['mobile_no'] = isset($tmp['mobile_no']) ? $tmp['mobile_no'] : '';
                    $patientImportArr['country_code'] = isset($tmp['country_code']) ? $tmp['country_code'] : '';


                    if (empty($patient_id) && isset($patientImportArr)) {
                        $patient_id = DB::table('patients')->insertGetId($patientImportArr);
                        if (isset($patient_id)) {
                            $setReminders = app('App\Http\Controllers\Admin\DashboardController')->checkPatientAgeReminder($patient_id);
                        } //if patient id

                        $parentPatientId =  DB::connection('system')
                            ->table("patients")
                            ->whereDate('birth_date', date('Y-m-d', strtotime($tmp['birth_date'])))
                            ->where('mobile_no', $tmp['mobile_no'])
                            ->whereNULL('deleted_at')
                            ->orderBy('created_at', 'DESC')
                            ->pluck('id')
                            ->first();

                        $ordination_id = Config('ordination_id');
                        if (!empty($parentPatientId)) {

                            $assign_ordination = DB::connection('system')
                                ->table("patients_has_ordination")
                                ->where([
                                    'fk_ordination_id' => $ordination_id,
                                    'fk_patient_id' => $parentPatientId
                                ])
                                ->count();

                            if ($assign_ordination > 0) {
                                $checkInsertion = DB::connection('system')
                                    ->table("patients_has_ordination")
                                    ->insert([
                                        'fk_ordination_id' => $ordination_id,
                                        'fk_patient_id' => $parentPatientId,
                                        'status' => '1'
                                    ]);
                            }
                            DB::commit();
                        } else {
                            $parentPatientInsertedId = DB::connection('system')
                                ->table("patients")->insertGetId($patientImportArr);

                            $checkInsertion = DB::connection('system')
                                ->table("patients_has_ordination")
                                ->insert([
                                    'fk_ordination_id' => $ordination_id,
                                    'fk_patient_id' => $parentPatientInsertedId,
                                    'status' => '1'
                                ]);
                            DB::commit();
                        }
                    } //if empty patient id
                    else {
                        $patient_id = $patient_id;
                    } //else

                    // dump("patient_id====>");
                    // dump($patient_id);

                    if (!empty($patient_id)) {
                        // dump('in not empty patient id');

                        $doctor_id   = Null;
                        $doctorName  = Null;
                        $notes       = Null;
                        $doctor_email = Null;
                        $color_id    = 0;

                        $isDoctorRecongnized = 0;

                        $getDoctorId = DB::table("users")
                            ->where(DB::raw('upper(first_name)'), '=', mb_strtoupper($tmp['doctor_first_name']))
                            ->where(DB::raw('upper(last_name)'), '=', mb_strtoupper($tmp['doctor_last_name']))
                            ->first();

                        if (!empty($getDoctorId)) {

                            if ($getDoctorId->status == 1) {
                                $isDoctorRecongnized = 1;
                            } else {
                                $isDoctorRecongnized = 0;
                            }

                            $doctor_id    = $getDoctorId->id;
                            $doctorName   = $getDoctorId->first_name . " " . $getDoctorId->last_name;
                            $doctor_email = $getDoctorId->email;
                            $color_id     = $getDoctorId->google_color_id;
                        } //if not empty doctor id
                        else {

                            $isDoctorRecongnized = 0;

                            $getEmergencyDoctor = DB::table("users")->where('first_name', 'Doctor')->where('last_name', 'Emergency')->first();

                            if (isset($getEmergencyDoctor) && !empty($getEmergencyDoctor)) {
                                $doctor_id    = $getEmergencyDoctor->id;
                                $doctorName   = $getEmergencyDoctor->first_name . " " . $getEmergencyDoctor->last_name;
                                $doctor_email = $getEmergencyDoctor->email;
                                $color_id     = $getEmergencyDoctor->google_color_id;
                            } else {
                                //dump('in doctor not available');

                                $usersCollection = new AdminUserModel;
                                $usersCollection->first_name = 'Doctor';
                                $usersCollection->last_name = 'Emergency';
                                $usersCollection->email = 'emergency@gmail.com';
                                $usersCollection->google_color_id = 4;
                                $usersCollection->doctor_speciality = 'Emergency Doctor';
                                $usersCollection->status = 0;
                                $usersCollection->save();

                                $usersCollection->assignRole(strtolower('Doctor'));
                                $doctor_id = $usersCollection->id;
                                //dump($doctor_id);

                            } //else      

                        } //else of doctor not exists


                        $tmp['doctor_id'] = $doctor_id;
                        $tmp['isDoctorRecongnized'] = $isDoctorRecongnized;


                        //Appoinment added in google calendar
                        // $patientName = $tmp['first_name']." ".$tmp['family_name'];
                        $appointment_type_id = 7;
                        $appointmentTypeName = $this->AppointmentTypesModel->where('name', $tmp['appointment_type'])->first();
                        if (isset($appointmentTypeName) && !empty($appointmentTypeName)) {
                            $appointment_type_id = $appointmentTypeName->id;
                        }

                        $tmp['appointment_type_id'] = $appointment_type_id;

                        $tmp['end_date']  = self::_getEndDate($tmp['start_date'], $appointment_type_id);

                        // dump("tmp====>");
                        // dump($tmp);

                        $duplicationAppointmantself =  $this->_checkDuplicatedAppointment($tmp, '');

                        // dump("duplicationAppointmantself ===> ");
                        // dump($duplicationAppointmantself);


                        if (empty($duplicationAppointmantself) && sizeof($duplicationAppointmantself) == 0) {

                            $app['appointment_status'] = "";

                            $todayDate = date('Y-m-d');
                            $newdate = date("Y-m-d", strtotime($tmp['start_date']));
                            if ($newdate < $todayDate) {
                                $app['appointment_status'] = 'Fertig';
                            }
                            $app['patient_id']      = $patient_id;
                            $app['doctor_id']       = $doctor_id;
                            $app['appointment_type_id'] = $appointment_type_id;
                            $app['notes'] = "";
                            $app['start_date']      = $tmp['start_date'];
                            $app['end_date']        = $tmp['end_date'];
                            $app['status']          = 1;

                            //dump($app);

                            $appointmentData = AppointmentModel::create($app);

                            //dump($appointmentData->id);
                            /*************update the time frame***************/

                            $time_frame = Date('H:i:s', strtotime($tmp['start_date']));
                            $sdate = Date('Y-m-d', strtotime($tmp['start_date']));

                            if ($isDoctorRecongnized == 1) {
                                $isExists_time_frame = $this->RosterModel
                                    ->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')
                                    // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                    ->join('roster_has_weeks_has_time_frames', 'roster_has_weeks_has_time_frames.roster_id', 'roster_has_dates.roster_id')
                                    ->where('roster.doctor_id', $doctor_id)
                                    ->where('roster_has_dates.is_excluded', '=', 0)
                                    ->whereDate('roster_has_dates.date', $sdate)
                                    ->where('roster_has_weeks_has_time_frames.time_frame', '=', $time_frame)
                                    ->first(['roster_has_weeks_has_time_frames.time_frame', 'roster_has_dates.date', 'roster_has_weeks_has_time_frames.id', 'roster_has_weeks_has_time_frames.roster_id']);

                                //  dump("after isExists_time_frame");
                                // dump($isExists_time_frame);

                                if (isset($isExists_time_frame) && !empty($isExists_time_frame)) {
                                    $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                        ->where('id', $isExists_time_frame->id)
                                        ->update([
                                            'time_frame_flag' => '2',
                                            'time_frame_flag_date' => Date('Y-m-d H:i:s'),
                                            'comment' => 'AppointmentController store booking function app Date:' . date('Y-m-d H:i:s', strtotime($appointmentData->start_date)) . ' current date:' . Date('Y-m-d H:i:s') . ' patient_id: ' . $patient_id
                                        ]);
                                } //if    
                            } //if isDoctorRecongnized




                            /************update the time frame**************/

                            $services_input = array('patient_id' => $patient_id, 'appointment_type_id' => $appointment_type_id, 'a_id' => '');

                            //  dump("mergedArray ===>");                  
                            $mergedArray = $this->getServices_import($services_input);
                            // dump($mergedArray);


                            self::_activateReminderOnEdit($appointmentData);

                            self::_deactivateReminderNew($appointmentData, $mergedArray);

                            $getDocument = self::_GetAssignedDocument($appointmentData->id, $appointmentData->appointment_type_id, $mergedArray, $appointmentData->patient_id);

                            $getDocument = self::_GetAssignedCheckList($appointmentData->id, $mergedArray, $appointmentData->patient_id);


                            $collection = $this->BaseModel->with(['assignedPatient', 'assignedDoctor', 'assignedAppointmentType'])->find($appointmentData->id);
                            $patientName = $collection->assignedPatient->first_name . " " . $collection->assignedPatient->family_name;
                            $doctorName = $collection->assignedDoctor->first_name . " " . $collection->assignedDoctor->last_name;
                            $appointmentType = $collection->assignedAppointmentType->name;
                            $booking_month = __('admin.' . date('F', strtotime($tmp['start_date'])), [], 'de');
                            $appointmentTime = date('d', strtotime($tmp['start_date'])) . '.' . $booking_month . ", um " . date('H:i', strtotime($tmp['start_date'])) . " Uhr.";

                            $patientText = $collection->assignedPatient->salutation ? " " . $collection->assignedPatient->salutation . '.' : ""; //added dot after salutation 

                            $patientText .= " " . $collection->assignedPatient->first_name . ' ' . $collection->assignedPatient->family_name;

                            $doctorSurname = $collection->assignedDoctor->last_name;



                            /********added code on 13-feb-24***for notification from setting section*******/

                            $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($tmp['start_date'])));

                            $getSetting  = $this->AppointmentHasNotificationModel->whereStatus(3)->first();
                            if (isset($getSetting) && !empty($getSetting)) {
                                // dump("in getsetting");

                                $title = $getSetting->title;
                                $content = $getSetting->content;
                                $day = $getSetting->day;
                                $notify_time = $getSetting->notify_time;
                                $appointmentDate =  date("Y-m-d", strtotime($tmp['start_date']));


                                if ($day == 0) //current day
                                {
                                    $req_notify_time   = explode(" ", $getSetting->notify_time);
                                    $req_notify_time_in_seconds = $req_notify_time[1];
                                    $app_notify_time   = date("Y-m-d H:i:s", strtotime($appointmentDate . " " .
                                        $req_notify_time_in_seconds));
                                } else {
                                    //previous day
                                    $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($tmp['start_date'])));
                                    $req_notify_time   = explode(" ", $getSetting->notify_time);
                                    $req_notify_time_in_seconds = $req_notify_time[1];
                                    $app_notify_time = date("Y-m-d H:i:s", strtotime($previous_day . " " .
                                        $req_notify_time_in_seconds));
                                }

                                $content = str_replace("##PATIENT_NAME##", $patientText, $content);
                                $content = str_replace("##DOCTOR_SURNAME##", $doctorSurname, $content);
                                $content = str_replace("##APPOINTMENT_TYPE##", $appointmentType, $content);
                                $content = str_replace("##DATE_TIME##", $appointmentTime, $content);
                            } //if isset getsetting
                            else {
                                $title = 'Erinnerung an Ihren Termin';
                                $content = 'Hallo' . $patientText . ', ihr Termin mit Dr. ' . (string)$doctorSurname . ' (' . $appointmentType . ') ist am' . ' ' . (string)$appointmentTime;
                                $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($tmp['start_date'])));
                            }

                            $notify_data = array(
                                'patient_id' => $patient_id,
                                'appointment_id' => $appointmentData->id,
                                'title' => $title,
                                'content' => $content,
                                'notify_time' => $app_notify_time,
                                'status' => 0,
                            );

                            if ($this->AppointmentHasNotificationModel->insert($notify_data)) {
                                $all_transactions[] = 1;
                            } else {
                                $all_transactions[] = 0;
                            }


                            /*****end code**of notification setting****13-feb-24******/

                            //Default appintment
                            $getServises = $this->appointmentTypesAgaintsServices_import($appointmentData->id, $mergedArray, $patient_id);

                            $serviceEventType = $this->GetServicesEventType_import($appointmentData->id, $patient_id, $mergedArray, $appointmentData->appointment_type_id, 'admin');


                            $summary = $patientName . " - " . $appointmentType;
                            $description = '<p><strong>' . $this->patientText . ':</strong> ' . $patientName . ' </p><p><strong>' . $this->doctorText . ':</strong> ' . $doctorName . ' </p><p><strong>' . $this->appointmentText . ':</strong> ' . $appointmentType . ' </p><p><strong>' . $this->startDateText . ':</strong> ' . date('F d,Y H:i', strtotime($tmp['start_date'])) . ' </p><strong>' . $this->endDateText . ':</strong> ' . date('F d,Y H:i', strtotime($tmp['end_date'])) . ' </p><p><strong>' . $this->notesText . ':</strong> ' . $notes . ' </p>';

                            $request = array(
                                'summary' => $summary,
                                'description' => $description,
                                'startDateTime' => $tmp['start_date'],
                                'endDateTime' => $tmp['end_date'],
                                'patient_id' => $patient_id,
                                'patient_email' => $tmp['email'],
                                'patient_name' => $patientName,
                                'doctor_email' => $doctor_email,
                                'color_id' => $color_id,
                            );

                            //commented below code on 31-oct-23 for create appoinemnt on local 
                            // request()->merge($request);

                            $postCalDetails = $this->eventStore_import($request);
                            //dump('after postCalDetails');
                            //dump($postCalDetails);

                            //$postResponse = json_decode($postCalDetails->data);
                            if (!empty($postCalDetails) && $postCalDetails->original['status'] == 'success') {
                                $all_transactions[] = 1;
                                $eventId = $postCalDetails->original['data']->id;

                                //  dump('event Id');
                                //  dump($eventId);

                                $updateEvent = $this->BaseModel
                                    ->where('id', $appointmentData->id)
                                    ->update(['google_event_id' => $eventId,'event_id' => $eventId]);

                                    
                                    if($updateEvent)
                                    {
                                    $updateEvent = app('App\Http\Controllers\Admin\DashboardController')->appointmentIdUpdateInEvent($eventId, $appointmentData->id);
                                        $all_transactions[] = 1;
                                        $urlEventId = $eventId;
                                        $urlPatientId = $patient_id;
                                    }
                                    else {
                                        $all_transactions[] = 0;
                                    }

                                if ($updateEvent) {
                                    $all_transactions[] = 1;
                                    $urlEventId = $eventId;
                                    $urlPatientId = $patient_id;
                                } else {
                                    $all_transactions[] = 0;
                                }


                                $debug_arr['data'] = 'has created appointment by AppointmentController';
                                $res_name = "AppointmentController_store";
                                self::debugModeappBookFun($debug_arr, $res_name);
                                $newData = $appointmentData->toArray();
                                $this->ActivityLogModel->addLog($this->ModuleTitle, 'has created appointment', 'Add', null, $newData);
                                //add reminders for pass appointments added by swati 9-Jun-23================================================
                                $newdate = date("Y-m-d", strtotime($request['startDateTime']));
                                $todayDate = date('Y-m-d');
                                if ($newdate < $todayDate) {
                                    $this->_remindersPassAppointments($appointmentData->id);
                                }
                                //==============================================
                            } else {
                                $all_transactions[] = 0;
                                DB::rollback();
                            }



                            $all_transactions[] = 1;
                        } //if empty duplicated appointment 
                        else {
                            $all_transactions[] = 0;
                        }

                        $all_transactions[] = 1;
                    } //if not empty patient id
                    else {
                        $all_transactions[] = 0;
                    }
                } //while           
            } //if handle

            if (!in_array(0, $all_transactions)) {
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath . 'index');
                $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
            } else {
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['msg']    = __('admin.ERR_IMPORT_APPOINTMENT');
            }

            return response()->json($this->JsonData);
        } catch (Exception $e) {
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.ERR_SOMETHING_WRONG');
        }
    } //importData 



    //added on 23-feb-24 for import data
    public function _checkDuplicatedAppointment($value, $id = '')
    {
        // dump('in _checkDuplicatedAppointment');
        // dump($value);

        $errors = [];
        $time_frame = Date('H:i:s', strtotime($value['start_date']));
        $sdate = Date('Y-m-d', strtotime($value['start_date']));

        // dump('time_frame');
        // dump($time_frame);

        // dump('sdate');
        // dump($sdate);

        if ($value['isDoctorRecongnized'] == 1) {

            // =====================================================================
            $check_time_frame = $this->RosterModel
                ->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')
                // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')

                ->join('roster_has_weeks_has_time_frames', 'roster_has_weeks_has_time_frames.roster_id', 'roster_has_dates.roster_id')

                ->where('roster.doctor_id', $value['doctor_id'])
                ->where('roster_has_dates.is_excluded', '=', 0)
                ->whereDate('roster_has_dates.date', $sdate)
                ->where(
                    'roster_has_weeks_has_time_frames.time_frame',
                    '=',
                    $time_frame
                )
                // ->select(['roster_has_weeks_has_time_frames.time_frame','roster_has_dates.date','roster_has_weeks_has_time_frames.id','roster_has_weeks_has_time_frames.roster_id'])->toSql();
                ->get(['roster_has_weeks_has_time_frames.time_frame', 'roster_has_dates.date', 'roster_has_weeks_has_time_frames.id', 'roster_has_weeks_has_time_frames.roster_id']);

            // dump("check_time_frame");                 
            // dump($check_time_frame);                    


            if (!empty($check_time_frame) && sizeof($check_time_frame) > 0) {
                //dump("in check_time_frame");

                //now time slotes are available , but the appointment is booked for it then throw error message
                $check_app_date = $value['start_date'];
                $appointment_type_id = $value['appointment_type_id'];


                // $appointmentTimeDuration = $this->AppointmentTypesModel->find($appointment_type_id); //commented on 13-apr-26

                $appointmentTimeDuration = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id);//changed on 13-apr-26


                if (!empty($appointmentTimeDuration)) {
                    $duration = $appointmentTimeDuration->duration;
                    $check_app_end_date = date("Y-m-d H:i", strtotime('+' . $duration . ' minutes', strtotime($check_app_date)));
                }
                if (empty($id)) {

                    if ($duration == 10) {
                        $check_doctor_booked_appointment = $this->AppointmentModel
                            ->where('doctor_id', $value['doctor_id'])
                            ->whereStatus(1)
                            ->where('appointment.start_date', '<=', $check_app_date)
                            ->where('appointment.end_date', '>=', $check_app_end_date)
                            ->get(['id']);
                    } else {
                        $check_doctor_booked_appointment = $this->AppointmentModel
                            ->where('doctor_id', $value['doctor_id'])
                            ->whereStatus(1)
                            ->where('appointment.start_date', '>=', $check_app_date)
                            ->where('appointment.end_date', '<=', $check_app_end_date)
                            ->get(['id']);
                    }

                    //dump($check_doctor_booked_appointment);

                } else {
                    if ($duration == 10) {
                        $check_doctor_booked_appointment = $this->AppointmentModel
                            ->where('id', '!=', $id)
                            ->where('doctor_id', $value['doctor_id'])
                            ->whereStatus(1)
                            ->where('appointment.start_date', '<=', $check_app_date)
                            ->where('appointment.end_date', '>=', $check_app_end_date)
                            ->get(['id']);
                    } else {
                        $check_doctor_booked_appointment = $this->AppointmentModel
                            ->where('id', '!=', $id)
                            ->where('doctor_id', $value['doctor_id'])
                            ->whereStatus(1)
                            ->where('appointment.start_date', '>=', $check_app_date)
                            ->where('appointment.end_date', '<=', $check_app_end_date)
                            ->get(['id']);
                    }
                    // $check_doctor_booked_appointment = $this->AppointmentModel
                    //                    ->where('id','!=',$id)
                    //                    ->where('doctor_id',$request->doctor_id)
                    //                    //->where('appointment_type_id',$appointment_type_id)
                    //                    ->whereStatus(1)
                    //                    ->where('appointment.start_date','<=',$check_app_date)
                    //                    ->where('appointment.end_date', '>=', $check_end_date)
                    //                    ->get(['id']);
                }

                // dump($check_doctor_booked_appointment);    

                if (!empty($check_doctor_booked_appointment) && sizeof($check_doctor_booked_appointment) > 0) {
                    $errors[] = 'Terminzeitfenster sind bereits gebucht.'; //Appointment time slots is already booked
                }
            } else {
                $errors[] = 'Terminzeitfenster nicht verfügbar';
            }
        } //if
        //$errors[] = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
        return $errors;
        // ======================================================================

    } //
    //added on 23-feb-24 for import data
    public function getServices_import($val)
    {
        // dump('getServices_import');

        $serviceArr = [];

        $appointment_type_id = $val['appointment_type_id']; //42
        $patient_id          = $val['patient_id']; //5197
        $appointment_id      = $val['a_id'];
        $str = '';
        $collections1 = $this->AppointmentTypeHasExaminationsModel
            ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
            ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
            ->get([
                'examinations.id',
                'examinations.name',
                'examinations.url',
                'examinations.description',
                'examinations.document_name',
                'examinations.document_path',
                'examinations.document_status',
                'examinations.status',
                'examinations.created_at',
                'examinations.show_as_recommended'
            ]);
        $today_date = date("Y-m-d");

        //  dump("collections1===>");
        //  dump($collections1);

        /********start*********below code on 27-dec-23***********************/
        $collections1 = $collections1->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) {
            //  dump('in collection ====>');
            //  dump($item->id);

            $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
            if ($item->name == $app_type_name->name) {
                //dump('if same item');
                // dump($item->name);
                return $item;
            } else {
                // dump('not same item');
                // dump($item->id);



                $collectionsFilter = $this->PatientsHasServiceReminderModel
                    ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status'))
                    ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                    ->join(
                        DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                        FROM patient_has_service_reminder 
                                        WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                        and status='activate'
                                        and deleted_at is NULL GROUP BY service_id)
                                    patientremidners"),
                        function ($join) {
                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                        }
                    )
                    ->where('patient_has_service_reminder.patient_id', $patient_id)
                    ->where('patient_has_service_reminder.status', 'activate')
                    ->where('examinations.id', $item->id)
                    ->whereRaw("date(reminder_date) <= '" . $today_date . "'")
                    ->groupBy('patient_has_service_reminder.service_id')
                    ->get();

                // dump($collectionsFilter);     


                if (isset($collectionsFilter) && !empty($collectionsFilter) && $collectionsFilter->count() > 0) {
                    //  dump('in collection filter not empty');

                    $collectionsFilter = $collectionsFilter->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) {
                        //dump($item->id);

                        $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);

                        $age_service =  $this->ChannelsRemindersSettingModel
                            ->where('service_id', $item->id)
                            ->where('activated_reminder', 'age')
                            ->first();
                        //Added by swati 2-nov-22=========================
                        $general_reminder_service =  $this->ChannelsRemindersSettingModel
                            ->where('service_id', $item->id)
                            ->where('activated_reminder', 'general')
                            ->first();
                        //============================                  
                        if (!empty($age_service) && $item->name != $app_type_name->name) {
                            $getPatientAge = $this->PatientsModel->find($patient_id);
                            if (!empty($getPatientAge)) {
                                $patient_age = $getPatientAge->age;
                                if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                    //commented on 26-dec-23
                                    return $item;
                                } //if
                            }
                        } else if (!empty($general_reminder_service)) {
                            $checkGenaralService =  $this->PatientsHasServiceReminderModel
                                ->where('service_id', $item->id)
                                ->where('patient_id', $patient_id)
                                ->where('reminder_status', 'Set')
                                ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                ->first();
                            if (empty($checkGenaralService)) return $item;
                        } //Added this else by swati 2-nov-22
                        else {
                            return $item;
                        }
                    });
                } //if isset collection filter
                else {
                    //  dump('else collection filter empty');

                    $hasReminderSet = $this->PatientsHasServiceReminderModel
                        ->where('patient_has_service_reminder.patient_id', $patient_id)
                        ->where('patient_has_service_reminder.service_id', $item->id)
                        ->first();
                    if (isset($hasReminderSet) && !empty($hasReminderSet)) {
                        // dump('if hasReminderSet');
                        //  dump($item->id);
                    } //if hasReminderSet
                    else {
                        //  dump('else no reminder set');
                        //  dump($item->id);
                        return $item;
                    }


                    // return $item;  
                } //else   


            } //else

        });

        /************end***********above code on 27-dec-23*********************/

        //dump($collections1);

        $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));

        // dump($exams_ids);


        $today_date = date("Y-m-d");

        $collections2 = $this->PatientsHasServiceReminderModel
            ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status'))
            ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
            ->join(
                DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                        FROM patient_has_service_reminder 
                                        WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                        and status='activate'
                                        and deleted_at is NULL GROUP BY service_id)
                                    patientremidners"),
                function ($join) {
                    $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                    $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                }
            )
            ->where('patient_has_service_reminder.patient_id', $patient_id)
            ->where('patient_has_service_reminder.status', 'activate')
            ->whereNotIn('examinations.id', $exams_ids)
            ->whereRaw("date(reminder_date) <= '" . $today_date . "'")
            ->groupBy('patient_has_service_reminder.service_id')
            ->get();

        // dump($collections2);
        // log::info("getServices");
        // log::info($collections2);
        $collections2 = $collections2->filter(function ($item) use ($patient_id, $today_date) {
            $age_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'age')
                ->first();
            if (!empty($age_service)) {
                //log::info($patient_id);
                $getPatientAge = $this->PatientsModel->find($patient_id);
                if (!empty($getPatientAge)) {
                    $patient_age = $getPatientAge->age;
                    // log::info($age_service->age_from."<=".$patient_age."&&".$age_service->age_to.">=". $patient_age);
                    if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                        // log::info($item);
                        if ($item->reminder_status == 'executed') {
                            $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                ->where('service_id', $item->id)
                                ->where('patient_id', $patient_id)
                                ->where('reminder_status', 'Set')
                                ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                ->first();
                            //echo "<pre>";print_r($checkServiceReminders);
                            if (empty($checkServiceReminders))
                                return $item;
                        } else return $item;
                    }
                }
            }
            // else {
            //     if($item->reminder_status=='executed'){
            //         $checkServiceReminders =  $this->PatientsHasServiceReminderModel
            //                         ->where('service_id',$item->id)
            //                         ->where('patient_id',$patient_id)
            //                         ->where('reminder_status','Set')
            //                         ->whereRaw("date(reminder_date) >= '".$today_date."'") 
            //                         ->first();
            //         //echo "<pre>";print_r($checkServiceReminders);
            //         if(empty($checkServiceReminders))
            //             return $item;
            //     } 
            //     else return $item;
            // }

            //Added by swati 2-nov-22=========================
            $general_reminder_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'general')
                ->first();

            if (!empty($general_reminder_service)) {

                $today_date = date("Y-m-d");
                $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                    ->where('service_id', $item->id)
                    ->where('patient_id', $patient_id)
                    ->where('reminder_status', 'Set')
                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                    ->first();
                if (empty($checkServiceReminders))
                    return $item;
            }
            //Add checkup remidners as recommandation 4-sep-23=========================
            $checkup_reminder_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'checkup')
                ->first();

            if (!empty($checkup_reminder_service)) {

                $today_date = date("Y-m-d");
                $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                    ->where('service_id', $item->id)
                    ->where('patient_id', $patient_id)
                    ->where('reminder_status', 'Set')
                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                    ->first();
                if (empty($checkServiceReminders))
                    return $item;
            }
            //================================================
        });
        // log::info("getRecord");
        // log::info("getServices");


        $getRecord = $collections1->merge($collections2);
        // $getRecord = $collectionsFilter->merge($collections2);

        // dump("getRecord");
        // dump($getRecord);

        if ($appointment_id == 'undefined') {
            $appointment_id = '';
        }
        $appointment_exam = [];
        if (!empty($appointment_id)) {


            $getAppointmentServcies = $this->AppointmentHasExaminationsModel
                ->where('appointment_id', $appointment_id)
                ->where('patient_id', $patient_id)
                ->get();
            if (!empty($getAppointmentServcies)) {
                foreach ($getAppointmentServcies as $key => $value) {
                    $appointment_exam[] = $value->examination_id;
                }
                $collections3 = $this->PatientsHasServiceReminderModel
                    ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status'))
                    ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                    ->join(
                        DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                                FROM patient_has_service_reminder 
                                                WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                                and status='activate'
                                                and deleted_at is NULL GROUP BY service_id)
                                            patientremidners"),
                        function ($join) {
                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                        }
                    )
                    ->where('patient_has_service_reminder.patient_id', $patient_id)
                    ->where('patient_has_service_reminder.status', 'activate')
                    ->whereIn('examinations.id', $appointment_exam)
                    ->whereRaw("date(reminder_date) <= '" . $today_date . "'")
                    ->groupBy('patient_has_service_reminder.service_id')
                    ->get();




                $collections3 = $collections3->filter(function ($item) use ($patient_id, $today_date) {
                    $age_service =  $this->ChannelsRemindersSettingModel
                        ->where('service_id', $item->id)
                        ->where('activated_reminder', 'age')
                        ->first();
                    if (!empty($age_service)) {
                        $getPatientAge = $this->PatientsModel->find($patient_id);
                        if (!empty($getPatientAge)) {
                            $patient_age = $getPatientAge->age;
                            if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                if ($item->reminder_status == 'executed') {
                                    $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                        ->where('service_id', $item->id)
                                        ->where('patient_id', $patient_id)
                                        ->where('reminder_status', 'Set')
                                        ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                        ->first();
                                    //echo "<pre>";print_r($checkServiceReminders);
                                    if (empty($checkServiceReminders))
                                        return $item;
                                } else return $item;
                            }
                        }
                    }
                    $general_reminder_service =  $this->ChannelsRemindersSettingModel
                        ->where('service_id', $item->id)
                        ->where('activated_reminder', 'general')
                        ->first();
                    if (!empty($general_reminder_service)) {

                        $today_date = date("Y-m-d");
                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                            ->where('service_id', $item->id)
                            ->where('patient_id', $patient_id)
                            ->where('reminder_status', 'Set')
                            ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                            ->first();
                        if (empty($checkServiceReminders))
                            return $item;
                    }
                    //Add checkup remidners as recommandation 4-sep-23=========================
                    $checkup_reminder_service =  $this->ChannelsRemindersSettingModel
                        ->where('service_id', $item->id)
                        ->where('activated_reminder', 'checkup')
                        ->first();

                    if (!empty($checkup_reminder_service)) {

                        $today_date = date("Y-m-d");
                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                            ->where('service_id', $item->id)
                            ->where('patient_id', $patient_id)
                            ->where('reminder_status', 'Set')
                            ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                            ->first();
                        if (empty($checkServiceReminders))
                            return $item;
                    }
                    //================================================

                });
            }
            $duplicateRecord = $getRecord->merge($collections3);
            $getRecord = $duplicateRecord->unique();
        }
        // log::info($getRecord);


        //  dump($getRecord);

        if (!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id)) {
            $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {
                // dump("appointment_id==>");
                // dump($appointment_id); 
                // dump("patient_id==>");
                // dump($patient_id);
                //  dump("item_id==>");
                // dump($item->id);

                $app_type_name = $this->AppointmentHasExaminationsModel
                    ->where('appointment_id', $appointment_id)
                    ->where('patient_id', $patient_id)
                    ->where('examination_id', $item->id)
                    ->with(['assignedExamination'])
                    //->where('examination_id',$exam_id)
                    ->first();
                if (!empty($app_type_name)) {
                    // dump("in.......==>");
                    $item->checked = 1;
                    $item->sameName = 0;
                    return $item;
                }
                if (empty($item->description)) //When Discription is blank
                {
                    // dump("in...empty desc....==>");
                    $item->checked = 1;
                    $item->sameName = 0;
                    return $item;
                }
                return $item;
            });
        } else {

            //  dump("in...else part..............==>");

            $getRecord = $getRecord->map(function ($item) use ($appointment_id, $patient_id, $appointment_type_id) {
                $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                if (!empty($app_type_name)) {
                    if ($item->name == $app_type_name->name) {
                        // dump("in...else part.....same name.........==>");
                        $item->checked = 1;
                        $item->sameName = 1;
                    } else if (empty($item->description)) {
                        // dump("in...else part....desc.........==>");
                        $item->checked = 1;
                        $item->sameName = 0;
                    }
                    return $item;
                }
            });
        }

        //  dump($getRecord);

        // log::info($getRecord);
        if (!empty($getRecord) && sizeof($getRecord) > 0) {

            // dump('in getRecord');
            // dump($getRecord);

            // $serviceArr = [];

            $str .= "<label class='theme-blue'>" . __('admin.TITLE_APPOINTMENT_SERVICES') . "</label>";
            foreach ($getRecord as $key => $value) {
                ////Added by Shyam 29-12-21
                $checked = '';
                // log::info($key.">>>".$value);
                if (empty($appointment_id)) {
                    //  dump('in empty appointment_id');
                    //  dump($value->id);

                    $getReminderDate = DB::table('patient_has_service_reminder')
                        ->where('patient_id', $patient_id)->where('service_id', $value->id)
                        ->where('status', 'activate')->where('reminder_status', 'Set')
                        ->whereNull('deleted_at')->orderBy('reminder_date', 'DESC')
                        ->pluck('reminder_date')->first();

                    //  dump('in getReminderDate');
                    //  dump($getReminderDate);                   

                    if ($value['checked'] == 1 && $value['show_as_recommended'] != '1') {

                        $serviceArr[] = $value->id;
                        // dump('in 1111111111111');

                        $checked = 'checked';
                        $str .= "<div class='form-check'> 
                            <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                            <label class='form-check-label' for='status'>" . $value->name . "</label>
                        </div>";
                    }
                    // if($value['show_as_recommended'] == '1' && $checked = '')
                    else if ($value['show_as_recommended'] == '1' || $value['description'] != '') {
                        // dump('in 222222222');

                        $checked = '';
                        if ($value['sameName'] == 1) {
                            $checked = 'checked';

                            $serviceArr[] = $value->id;
                        }
                        $str .= "<div class='form-check'> 
                            <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                            <label class='form-check-label' for='status'>" . $value->name . "</label>
                        </div>";
                    }
                } //empty appointment_id
            } //foreach getRecord

            // dump('after foreach');
            // dd($serviceArr);
        }

        return $serviceArr;
    } //getServices_import

    //added on 23-feb-24 for import data
    public function getExtraServices_import($val)
    {
        // dd($request->all());

        $extraServices = [];

        $birth_date = $val['birth_date'];
        $appointment_type_id = $val['appointment_type_id'];
        $str = '';
        $collections1 = $this->AppointmentTypeHasExaminationsModel
            ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
            ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
            ->get(['examinations.id']);
        $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
        $getRecord = $this->ChannelsRemindersSettingModel
            ->join('examinations', 'examinations.id', 'preferred_channels_for_reminders_setting.service_id')
            ->where('preferred_channels_for_reminders_setting.type', 'service')
            ->where('preferred_channels_for_reminders_setting.activated_reminder', 'age')
            ->where('examinations.show_as_reminder', '1')
            ->whereNull('examinations.deleted_at') //added on 15-dec-23
            ->whereNotIn('examinations.id', $exams_ids)
            ->get([
                'examinations.id',
                'examinations.name',
                'examinations.url',
                'examinations.description',
                'examinations.document_name',
                'examinations.document_path',
                'examinations.document_status',
                'examinations.status',
                'examinations.created_at',
                'examinations.show_as_recommended'
            ]);
        $getRecord = $getRecord->filter(function ($item) use ($birth_date) {
            $age_service =  $this->ChannelsRemindersSettingModel
                ->where('service_id', $item->id)
                ->where('activated_reminder', 'age')
                ->first();
            if (!empty($age_service)) {
                if (!empty($birth_date)) {
                    $patient_age = (date('Y') - date('Y', strtotime($birth_date)));
                    if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                        return $item;
                    }
                }
            } else {
                return $item;
            }
        });
        $getRecord = $getRecord->map(function ($item) use ($appointment_type_id) {
            $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
            if (!empty($app_type_name)) {
                if ($item->name == $app_type_name->name) {
                    $item->checked = 1;
                } else if (empty($item->description)) {
                    $item->checked = 1;
                }
                return $item;
            }
        });
        if (!empty($getRecord) && sizeof($getRecord) > 0) {
            foreach ($getRecord as $key => $value) {

                $checked = '';
                if ($value['checked'] == 1) {
                    $checked = 'checked';
                    $extraServices[] = $value->id;
                }
                $str .= "<div class='form-check'>
                        <input type='checkbox' " . $checked . " class='form-check-input' name='app_services[]' name='status' value=" . $value->id . " >
                        <label class='form-check-label' for='status'>" . $value->name . "</label>
                    </div>";
            }
        }
        //dd($extraServices);
        return $extraServices;
    } //

    //added on 23-feb-24 for import data
    public function appointmentTypesAgaintsServices_import($id, $val, $patient_id)
    {
        // dump('in appointmentTypesAgaintsServices_import');
        // dump($val);

        // log::info("_appointmentTypesAgaintsServices");
        $services = [];

        //dd($request->app_services); 
        // log::info($request->app_services);
        if (!empty($val) && sizeof($val) > 0) {
            foreach ($val as $key => $value) {
                $checkService = $this->AppointmentHasExaminationsModel
                    ->where('patient_id', $patient_id)
                    ->where('appointment_id', $id)
                    ->where('examination_id', $value)
                    ->first();
                // log::info($id.">>".$patient_id.">>".$value);
                if (empty($checkService)) {
                    $services = new $this->AppointmentHasExaminationsModel;
                    $services->patient_id     = $patient_id;
                    $services->examination_id = $value;
                    $services->appointment_id = $id;
                    $services->save();
                }
            }
        }
        return $services;
    } //_appointmentTypesAgaintsServices_import
    //added on 23-feb-24 for import data
    public function GetServicesEventType_import($appoinment_id, $patient_id, $services, $appointment_type_id, $type)
    {
        $collections1 = $this->AppointmentTypeHasExaminationsModel
            ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
            ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
            ->get([
                'examinations.id',
            ]);

        $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));

        $collections2 = $this->PatientsHasServiceReminderModel
            ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
            ->where('patient_has_service_reminder.patient_id', $patient_id)
            ->where('patient_has_service_reminder.type', 'age')
            ->where('patient_has_service_reminder.status', 'activate')
            ->whereNotIn('examinations.id', $exams_ids)
            ->where('patient_has_service_reminder.reminder_status', 'Set')
            ->groupBy('patient_has_service_reminder.service_id')
            ->get([
                'examinations.id',
            ]);

        $getRecord = $collections1->merge($collections2);
        if (!empty($getRecord) && count($getRecord) > 0 && !empty($services)) {
            foreach ($getRecord as $key => $value) {
                if (in_array($value['id'], $services)) {
                    $status = 'booked';
                } else {
                    $status = 'displayed';
                }
                $isExist = $this->EventTypeHasExaminationsModel
                    ->where('patient_id', $patient_id)
                    ->where('appoinment_id', $appoinment_id)
                    ->where('service_id', $value['id'])
                    ->first();
                if (empty($isExist)) {
                    $eventType = new $this->EventTypeHasExaminationsModel;
                } else {
                    $eventType = $this->EventTypeHasExaminationsModel->find($isExist->id);
                }

                $eventType->patient_id    = $patient_id;
                $eventType->appoinment_id = $appoinment_id;
                $eventType->service_id    = $value['id'];
                $eventType->event_type    = $type;
                $eventType->status        = $status;
                $eventType->save();
            }
        }
        return 1;
    } //

    // new calender CR changes 8/2/2024
    public function eventStore_import($val)
    {
       
        try {
            $summary = $val['summary'];
            $description = $val['description'];
            $patient_id = $val['patient_id'];
            $patient_email = $val['patient_email'];
            $patient_name = $val['patient_name'];
            $doctor_email = $val['doctor_email'];
            $color_id = $val['color_id'];
            // $startDateTime = date('Y-m-d', strtotime($val['startDateTime'])) . "T" . date('H:i:s', strtotime($val['startDateTime']));
            // $endDateTime = date('Y-m-d', strtotime($val['endDateTime'])) . "T" . date('H:i:s', strtotime($val['endDateTime']));

            if (empty($patient_email) || $patient_email == '') {
                $patient_email = str_replace(" ", "@", $patient_name);
            }

            $event = Event::create([
                'summary' => $summary,
                'description' => $description,
                'patient_id' => $patient_id,
                'patient_email' => $patient_email,
                'patient_name' => $patient_name,
                'doctor_email' => $doctor_email,
                'color_id' => $color_id,
                'start_date_time' => $val['startDateTime'],
                'end_date_time' => $val['endDateTime'],
            ]);
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            $this->JsonData['data'] = $event;
            $this->JsonData['msg'] = 'Google event created successfully.';
         } catch (\Exception $e) {
            $msg = json_decode($e->getMessage());
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg'] = $msg->error->message;
        }
        return response()->json($this->JsonData);
    }
}//class

// get Examination
// appitment recommented examination
// checked examination