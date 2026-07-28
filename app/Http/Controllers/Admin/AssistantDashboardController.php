<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use thiagoalessio\TesseractOCR\TesseractOCR;

//Models
use App\Models\AdminUserModel; 
use App\Models\RosterModel; 
use App\Models\RosterHasDatesModel;
use App\Models\AppointmentModel; 
use App\Models\PatientsModel;
use App\Models\OldPatientsModel;
use App\Models\GoogleColorsModel;
use App\Models\DashboardNoticeModel;
use App\Models\PatientHasDocumentsModel;
use App\Models\AppointmentTypesModel;
use App\Models\AppointmentHasNotificationModel;
use App\Models\ActivityLogModel;
use App\Models\AppointmentHasQueueNumberModel;
use App\Models\DismissalModel;
use App\Models\PatientsHasDismissalModel;
use App\Models\DiagnosticFindingsTypesModel;
use App\Models\PatientsHasDiagnosticFindingsModel;
use App\Models\PatientHasDiagnosticFindingsHasDocumentsModel;
use App\Models\PatientsHasOldFindingModel;
use App\Models\SettingsModel;
use App\Models\PatientHasDeviceModel;
use App\Models\FindingHasNotificationModel;
use App\Models\AppointmentHasExaminationsModel;
use App\Models\ExaminationsModel;
use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use Illuminate\Contracts\Filesystem\Filesystem;
use App\Models\PatientsHasServiceControlReminderModel;
use App\Models\EventTypeHasExaminationsModel;
use App\Models\CheckListHasSelectedQuestionModel;
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\SpecialistDocumentsModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\CheckListHasHeadingSectionModel;
use App\Models\HeadingSectionHasQuestionModel;
use App\Models\RosterHasWeeksHasTimeFramesModel;
// Hyn tenancy code (commented out)
// use Hyn\Tenancy\Models\Website;
use App\Models\CheckListModel;
use App\Models\DeletedAppointmentTrackModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\PatientHasReminder;
use App\Models\Event;
use App\Models\CountryCodesModel; // new model for country code lookup

use Illuminate\Support\Facades\Log;  

use App\Traits\GeneralTrait; 
use Validator;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Exception;

use Hash;
use Mail;
use DB;
use Storage;
use Auth;
use Carbon\Carbon;
use Session;
use Illuminate\Support\Facades\Lang;

// Request
use App\Http\Requests\Admin\AppointmentRequest;
use App\Http\Requests\Admin\AssistantDashboardRequest;

//mail
use App\Mail\SendFindingForPatientmail;
use App\Models\UserHasAppointmentType;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

class AssistantDashboardController extends Controller
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
                                CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
                                ActivityLogModel $ActivityLogModel,
                                SpecialistDocumentsModel $SpecialistDocumentsModel,
                                 RosterModel $RosterModel,
                                 ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
                                 HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
                                CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
                                 DashboardNoticeModel $DashboardNoticeModel,
                                 RosterHasDatesModel $RosterHasDatesModel,
                                 CheckListModel $CheckListModel,
                                 PatientHasDocumentsModel $PatientHasDocumentsModel,
                                 AppointmentHasQueueNumberModel $AppointmentHasQueueNumberModel,
                                DismissalModel $DismissalModel,
                                ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
                                PatientsHasDismissalModel $PatientsHasDismissalModel,
                                DiagnosticFindingsTypesModel $DiagnosticFindingsTypesModel,
                                PatientsHasDiagnosticFindingsModel $PatientsHasDiagnosticFindingsModel,
                                PatientHasDiagnosticFindingsHasDocumentsModel $PatientHasDiagnosticFindingsHasDocumentsModel,
                                PatientsHasOldFindingModel $PatientsHasOldFindingModel,
                                SettingsModel $SettingsModel,
                                PatientHasDeviceModel $PatientHasDeviceModel,
                                FindingHasNotificationModel $FindingHasNotificationModel,
                                AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
                                ExaminationsModel $ExaminationsModel,
                                AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
                                                                  PatientsHasServiceControlReminderModel $PatientsHasServiceControlReminderModel,
                                  OldPatientsModel $OldPatientsModel,
                                  // Hyn tenancy code (commented out)
                                  // Website $website,
                                  PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
                                EventTypeHasExaminationsModel $EventTypeHasExaminationsModel,
                                RosterHasWeeksHasTimeFramesModel $RosterHasWeeksHasTimeFramesModel,
                                DeletedAppointmentTrackModel $DeletedAppointmentTrackModel,
                                ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
                                PatientHasReminder $PatientHasReminder,
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
        $this->ActivityLogModel               = $ActivityLogModel;
        $this->RosterModel                    = $RosterModel;
        $this->DashboardNoticeModel           = $DashboardNoticeModel;
        $this->RosterHasDatesModel            = $RosterHasDatesModel;
        $this->PatientHasDocumentsModel       = $PatientHasDocumentsModel;
        $this->AppointmentHasQueueNumberModel = $AppointmentHasQueueNumberModel;
        $this->DismissalModel  = $DismissalModel;
        $this->HeadingSectionHasQuestionModel = $HeadingSectionHasQuestionModel;
        $this->ExaminationsHasMultipleCheckListModel = $ExaminationsHasMultipleCheckListModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;
        $this->PatientsHasDismissalModel = $PatientsHasDismissalModel;
        $this->DiagnosticFindingsTypesModel   = $DiagnosticFindingsTypesModel; 
        $this->PatientsHasDiagnosticFindingsModel   = $PatientsHasDiagnosticFindingsModel;
        $this->PatientHasDiagnosticFindingsHasDocumentsModel = $PatientHasDiagnosticFindingsHasDocumentsModel; 
        $this->PatientsHasOldFindingModel = $PatientsHasOldFindingModel;
        $this->SettingsModel = $SettingsModel;
        $this->PatientHasDeviceModel = $PatientHasDeviceModel;
        $this->FindingHasNotificationModel = $FindingHasNotificationModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->CheckListModel = $CheckListModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->PatientsHasServiceControlReminderModel = $PatientsHasServiceControlReminderModel;
        $this->OldPatientsModel = $OldPatientsModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;
        $this->EventTypeHasExaminationsModel = $EventTypeHasExaminationsModel;
                  $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
          $this->CheckListHasHeadingSectionModel = $CheckListHasHeadingSectionModel;
          // Hyn tenancy code (commented out)
          // $this->website  = $website;
          $this->RosterHasWeeksHasTimeFramesModel = $RosterHasWeeksHasTimeFramesModel;
        $this->DeletedAppointmentTrackModel = $DeletedAppointmentTrackModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->PatientHasReminder=$PatientHasReminder;
        $this->UserHasAppointmentType = $UserHasAppointmentType;
        $this->CountryCodesModel = $CountryCodesModel;
        
        $this->ModuleTitle  = __('admin.TITLE_DASHBOARD');  
        $this->ModuleView   = 'admin.assistant-dashboard.';
        $this->ModulePath   = 'admin.assistant-dashboard';
        
        $this->patientText      = 'Patient';
        $this->doctorText       = 'Arzt';
        $this->appointmentText  = 'Typ';
        $this->startDateText    = 'Beginn';
        $this->endDateText      = 'Ende';
        $this->notesText        = 'Notizen';

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
        $data = Session::get('redirect_arr');
        $mesg = $patient_id = $finding_imp_suc = '';
        if(!empty($data))
        {
            if(!empty($data['patient_id']))
            {
                $finding_imp_suc = $data['msg'];
                $patient_id = $data['patient_id'];//set success mesg for import finding
                Session::put('redirect_arr','');
            }
        }
        //Temp set sesson for import msg
        $this->ModuleTitle  = __('admin.TITLE_DASHBOARD');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle;
        $this->ViewData['moduleAction'] = $this->ModuleTitle;
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['user'] = $this->AdminUserModel
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'doctor');
                                        })
                                        ->get();
        $sql = "SELECT `patients`.`id` FROM `patients` 
                WHERE `status` = 1 AND `patient_status_flag` = '0' AND `old_id` != '0'
                AND `update_ganydb` = '1' AND `new_flag`='1'
                AND id In(
                select DISTINCT (patients.id) from `patients` 
                inner join `old_patients` on `patients`.`id` = `old_patients`.`fk_patient_id` 
                    where (patients.road != old_patients.road or patients.size != old_patients.size 
                    or patients.email != old_patients.email or patients.title != old_patients.title 
                    or patients.weight != old_patients.weight or patients.gender != old_patients.gender 
                    or patients.mobile_no != old_patients.mobile_no or patients.birth_date != old_patients.birth_date 
                    or patients.first_name != old_patients.first_name or patients.postal_code != old_patients.postal_code 
                    or patients.family_doctor != old_patients.family_doctor 
                    or patients.insurance_number != old_patients.insurance_number 
                    or patients.additional_insurance != old_patients.additional_insurance))";
        $results = DB::select($sql);
        $updateIds = [];
        foreach ($results as $ptnt)
        {
            $updateIds[] = $ptnt->id;
        }
        $patient_cnt = $this->PatientsModel
                    ->orWhere(function($q) 
                    {
                        $q->whereNotNull('note_report_request')
                            ->Where('note_report_request_flag','>', '0');
                    })->orWhere(function($q1) {
                        $q1->Where('update_ganydb','1')
                            ->Where('new_flag','1');
                    })->orWhere(function($q2) {
                        $q2->Where('new_flag','1');
                    })
                    ->with(['getOldAppoinmant'])
                    ->orderBy('updated_at','DESC')
                    ->get();
        $patient_cnt = $patient_cnt->filter(function($item)
        {
            if($item->patient_status_flag == '0' && $item->status == '1' && $item->old_id != '0')
            {
                return $item;
            }
        });
        $patient_cnt = $patient_cnt->filter(function($item) use($updateIds)
        {
            if($item->new_flag == '1' && $item->update_ganydb!=1)
            {
                return $item;
            }
            elseif(($item->note_report_request_flag == '1' || $item->note_report_request_flag == '2'))
            {
                return $item;
            }
            elseif($item->update_ganydb == 1)
            {
                if(in_array($item->id, $updateIds))
                {
                    return $item;
                }
            }
            elseif ($item->update_ganydb == 1 && $item->new_flag =='1' && $item->old_id != '0')
            {
                return $item;
            }
        });
        $this->ViewData['patient_cnt'] = count($patient_cnt);
        // DISMISSAL 
        $getTotalDismissal   = $this->PatientsHasDismissalModel
                               ->where('dismissal_flag','0')
                               ->count();
        $this->ViewData['getTotalDismissal'] = $getTotalDismissal;
        // Examination && dismissal record
        $this->ViewData['getDismissalHasPatients'] = $this->getExaminationAndDismissal();
        // All appointment types
        $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->get();
        // error message
        $this->ViewData['waiting_list'] =  $this->AppointmentHasQueueNumberModel
                                            ->leftjoin('appointment', 'appointment.id' , '=', 'appointment_has_queue_number.appointment_id')
                                            ->get();
        //FINDING TYPES
        $this->ViewData['finding_type'] = $this->DiagnosticFindingsTypesModel
                                          ->where('status',1)
                                          ->get();
        //Get Setting
        $this->ViewData['import_finding_setting'] = $this->SettingsModel->where('setting_key','Import Setting')->first();
        $this->ViewData['import_finding_via_email_setting'] = $this->SettingsModel->where('setting_key','SEND_FINDING_VIA_EMAIL')->where('status','1')->first();
        $this->ViewData['patient_id']  = $patient_id;
        $this->ViewData['success_msg'] = __('admin.DISSMISSAL_SUCCESS_MSG');
        $this->ViewData['error_msg']   = __('admin.DISSMISSAL_ERROR_MSG');
        $this->ViewData['copy_success_msg']    = __('admin.COPY_MSG_SUCCESS');
        $this->ViewData['copy_error_msg']      = __('admin.COPY_MSG_ERROR');
        $this->ViewData['warning_todo_list']   = __('admin.ERR_CLEAR_TODO_LIST_WARNING_MSG');
        $this->ViewData['todo_list_confirmation']   = __('admin.WARNING_TITLE');
        $this->ViewData['title_todo_warning']  = __('admin.TITLTE_TODO_WARNING');
        $this->ViewData['completed_msg']       = __('admin.TITLTE_TODO_COMPLETED_MSG');
        $this->ViewData['completed_not_msg']   = __('admin.TITLTE_TODO_NOT_COMPLETED_MSG');
        $this->ViewData['finding_imp_suc']     = $finding_imp_suc;
        $this->ViewData['title_warning']       = __('admin.RESP_WARNING');
        $this->ViewData['msg_finding_via_mail']= __('admin.MSG_FINDING_SEND_VIA_MAIL');
        $this->ViewData['msg_msg_finding_push_notification']= __('admin.MSG_FINDING_SEND_PUSH_NOTIFICATION');
        $this->ViewData['err_something_wrong']=__('admin.ERR_SOMETHING_WRONG');
        $this->ViewData['todolist_title']     =__('admin.TITLE_ASSISTANT_DASHBOARD_TODO_LIST');
        $this->ViewData['setting_value'] = $this->SettingsModel
                                            ->where('setting_key','NEW_WINDOW_SETTING')
                                            ->pluck('setting_value')
                                            ->first();
        $setting_value = json_decode($this->ViewData['setting_value']);
        $this->ViewData['width'] = $setting_value->width;
        $this->ViewData['height'] = $setting_value->height;
        $this->ViewData['position'] = $setting_value->position;
        $sql_query = "SELECT first_name,family_name,birth_date,mobile_no,email, COUNT(*) occurrences FROM patients where deleted_at is NULL GROUP BY first_name,family_name,birth_date,mobile_no HAVING COUNT(*) > 1 and mobile_no!='' and birth_date!=''";
        $duplicateRecord = $this->PatientsModel
                            ->selectRaw('first_name,family_name,birth_date,mobile_no,email, COUNT(*) occurrences')
                            ->whereNull('deleted_at')
                            ->groupBy('first_name','family_name','birth_date','mobile_no')
                            ->havingRaw("COUNT(*) > 1 and mobile_no!='' and birth_date Is NOT NUll")
                            ->get();
        $duplicateRecord = $duplicateRecord->map(function($item)
        {
            $multiple_record = $this->PatientsModel
                                    ->select('id')
                                    ->whereNull('deleted_at') 
                                    ->where('mobile_no',$item->mobile_no)
                                    ->where('birth_date',$item->birth_date)
                                    ->where('first_name',$item->first_name)
                                    ->where('family_name',$item->family_name)
                                    ->get();
            $ids = array_column($multiple_record->toArray(),'id');
            $item->link_ids = $ids;
            return $item;
        });
        $this->ViewData['duplicateRecord'] = $duplicateRecord;
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
        return view($this->ModuleView.'index', $this->ViewData);
    }

    // public function getExaminationAndDismissal()
    // {
    //     $arr_dismissal = $arr_final_dismissal = $arr_examination = $arr_fial_examination = [];
    //     $getDismissalPatientExamination = $this->PatientsHasDismissalModel
    //                                       ->select('fk_patient_id')
    //                                       ->where('dismissal_flag','0')
    //                                       ->groupBy('fk_patient_id')
    //                                       ->pluck('fk_patient_id')
    //                                       ->toArray();
    //     //dd($getDismissalPatientExamination);
    //     if(sizeof($getDismissalPatientExamination)>0)
    //     {
    //         $c=0;
    //         foreach ($getDismissalPatientExamination as $key => $value) 
    //         {
    //             $getPatients = $this->PatientsModel->find($value);
    //             if(!empty($getPatients))
    //             {
    //                 $arr_dismissal[$c]['patient']['p_id']      = $getPatients->id;
    //                 $arr_dismissal[$c]['patient']['full_name'] = ucfirst($getPatients->first_name).' '.$getPatients->family_name;
    //                 $current_date = Date('Y-m-d');
    //                 $getADismissal = $this->PatientsHasDismissalModel
    //                                            ->select('appointment_id')
    //                                            ->where('fk_patient_id',$value)
    //                                            ->where('dismissal_flag','0')
    //                                            ->groupBy('appointment_id')
    //                                            ->get();
    //                 //dd($getADismissal);                           
    //                 $exist_arr = [];                           
    //                 if(!empty($getADismissal))
    //                 {
    //                     $a=0;
    //                     foreach ($getADismissal as $a_key => $a_val) 
    //                     {
    //                         $getAppoinmenthasDismissal = $this->AppointmentModel->find($a_val['appointment_id']);
    //                         $s_date = null;
    //                         if(!empty($getAppoinmenthasDismissal))
    //                         {
    //                             $s_date = date('Y-m-d H:i',strtotime($getAppoinmenthasDismissal->start_date));
    //                         }
    //                         // Dismissal
    //                         $getFinalDismisal = $this->PatientsHasDismissalModel
    //                                             ->where('appointment_id',$a_val['appointment_id'])
    //                                             ->where('fk_patient_id',$value)
    //                                             ->where('dismissal_flag','0')
    //                                             ->where('type','dismissal')
    //                                             ->get();
    //                         //dd($getFinalDismisal);
    //                         if(!empty($getFinalDismisal) && sizeof($getFinalDismisal)>0)
    //                         {
    //                             $cnt = 0;
    //                             foreach ($getFinalDismisal as $d_key => $d_value) 
    //                             {
    //                                 $dismissalDetails = $this->DismissalModel->where('id',$d_value['fk_dismissal_id'])->first();
    //                                 $dismissalName = $dismissalDetails['name'];
    //                                 $arr_dismissal[$c]['patient']['appoinmant'][$a]['dismissal'][$cnt]['id']   = $d_value['fk_dismissal_id'];
    //                                 $arr_dismissal[$c]['patient']['appoinmant'][$a]['dismissal'][$cnt]['name'] = $dismissalName;
    //                                 $arr_dismissal[$c]['patient']['appoinmant'][$a]['dismissal'][$cnt]['appointment_id']   = $a_val['appointment_id'];
    //                                 $arr_dismissal[$c]['patient']['appoinmant'][$a]['dismissal'][$cnt]['appointment_date'] = $s_date;
    //                                 $cnt++;
    //                             }
    //                         }
    //                         //Examination
    //                          $getFinalExamination = $this->AppointmentHasExaminationsModel
    //                                             ->where('appointment_id',$a_val['appointment_id'])
    //                                             ->where('patient_id',$value)
    //                                             ->get();
    //                         //dd($getFinalExamination);
    //                         if(!empty($getFinalExamination) && sizeof($getFinalExamination)>0)
    //                         {
    //                             $cnt = 0;
    //                             foreach ($getFinalExamination as $e_key => $e_value) 
    //                             {
    //                                 $getExamination = $this->ExaminationsModel->find($e_value['examination_id']);
    //                                 if(!empty($getExamination))
    //                                 {
    //                                     $arr_dismissal[$c]['patient']['appoinmant'][$a]['examination'][$cnt]['id']   = $getExamination->id;
    //                                     $arr_dismissal[$c]['patient']['appoinmant'][$a]['examination'][$cnt]['name'] = $getExamination->name;
    //                                     $arr_dismissal[$c]['patient']['appoinmant'][$a]['examination'][$cnt]['appointment_id']   = $a_val['appointment_id'];
    //                                     $arr_dismissal[$c]['patient']['appoinmant'][$a]['examination'][$cnt]['appointment_date'] = $s_date;
    //                                 }
    //                                 $cnt++;
    //                             }
    //                         } 
    //                         else
    //                         {
    //                             $arr_dismissal[$c]['patient']['appoinmant'][$a]['examination'] = [];
    //                         }
    //                     $a++;
    //                     }
    //                 }                           
    //             }
    //             $c++;
    //         }
    //     }
    //     return $arr_dismissal;
    // }

    public function getExaminationAndDismissal()
    {
        $arr_dismissal = $arr_final_dismissal = $arr_examination = $arr_fial_examination = [];
        $getAppoinment = $this->AppointmentModel
                         // ->where('appointment_status','Aktuell')
                         ->where('appointment_status','!=','Heute')
                         ->whereDate('start_date',Date('Y-m-d'))
                         ->get();
        if(sizeof($getAppoinment)>0) {
            $c = 0;
            //log::info("getExaminationAndDismissal");

            foreach ($getAppoinment as $key => $value) {
                $getPatients = $this->PatientsModel->find($value['patient_id']);
                if ($getPatients) {
                    $getAppoinmentDate = $this->AppointmentModel->find($value['id']);

                    $s_date = (!empty($getAppoinmentDate)) ? date('Y-m-d H:i', strtotime($getAppoinmentDate->start_date)) : '';

                    // Dismissal
                    $getDismissalPatient = $this->PatientsHasDismissalModel
                        ->where('appointment_id', $value['id'])
                        ->where('fk_patient_id', $value['patient_id'])
                        ->where('dismissal_flag', '0')
                        ->where('type', 'dismissal')
                        ->get();

                    $exist_arr = [];
                    $cnt = 0;
                    if (!empty($getDismissalPatient) && sizeof($getDismissalPatient) > 0) {

                        foreach ($getDismissalPatient as $d_key => $d_value) {

                            // Dismissal
                            $dismissalDetails = $this->DismissalModel->where('id', $d_value['fk_dismissal_id'])->first();

                            if (!empty($dismissalDetails['name'])) {
                                $dismissalName = $dismissalDetails['name'] ?? '';
                                $arr_dismissal[$c]['patient']['appoinmant']['dismissal'][$cnt]['id'] = $d_value['fk_dismissal_id'];
                                $arr_dismissal[$c]['patient']['appoinmant']['dismissal'][$cnt]['name'] = $dismissalName;
                                $arr_dismissal[$c]['patient']['appoinmant']['dismissal'][$cnt]['appointment_id'] = $value['id'];
                                $arr_dismissal[$c]['patient']['appoinmant']['dismissal'][$cnt]['appointment_date'] = $s_date;

                                $cnt++;
                            }
                        }
                    }

                    //Examination
                    $getFinalExamination = $this->AppointmentHasExaminationsModel
                        ->leftjoin('examinations', 'examinations.id', 'appointment_has_examinations.examination_id')
                        ->where('appointment_id', $value['id'])
                        ->where('patient_id', $value['patient_id'])
                        ->where('dismissal_flag', '0')
                        //->where('on_dashboard', '1') //commented on 6-jan-26 for #396
                        ->get();
                    $ecnt = 0;

                    if (!empty($getFinalExamination) && sizeof($getFinalExamination) > 0) {

                        foreach ($getFinalExamination as $e_key => $e_value) {

                            // $getExamination = $this->ExaminationsModel
                            //                   ->where('on_dashboard','1')
                            //                   ->find($e_value['examination_id']);
                            // if(!empty($getExamination))
                            // {
                            $arr_dismissal[$c]['patient']['appoinmant']['examination'][$ecnt]['id'] = $e_value['examination_id'];
                            $arr_dismissal[$c]['patient']['appoinmant']['examination'][$ecnt]['name'] = $e_value['name'];
                            $arr_dismissal[$c]['patient']['appoinmant']['examination'][$ecnt]['appointment_id'] = $value['id'];
                            $arr_dismissal[$c]['patient']['appoinmant']['examination'][$ecnt]['appointment_date'] = $s_date;
                            // }
                            $ecnt++;
                        }
                    }

                    // Reminder
                    $getDismissalPatienthasReminder = $this->PatientsHasDismissalModel
                        ->where('appointment_id', $value['id'])
                        ->where('fk_patient_id', $value['patient_id'])
                        ->where('dismissal_flag', '0')
                        ->where('type', 'reminder')
                        ->get();

                    $exist_arr = [];
                    $rcnt = 0;
                    if (!empty($getDismissalPatienthasReminder) && sizeof($getDismissalPatienthasReminder) > 0) {

                        foreach ($getDismissalPatienthasReminder as $r_key => $r_value) {

                            $getreminder = $this->PatientsHasServiceControlReminderModel->where('id', $r_value['fk_dismissal_id'])->first();
                            if (!empty($getreminder)) {
                                $getExamName = $this->ExaminationsModel->find($getreminder->service_id);
                                if (!empty($getExamName)) {
                                    $serviceReminderName = $getExamName->name;
                                    $arr_dismissal[$c]['patient']['appoinmant']['reminder'][$rcnt]['id'] = "";
                                    $arr_dismissal[$c]['patient']['appoinmant']['reminder'][$rcnt]['name'] = $serviceReminderName;
                                    $arr_dismissal[$c]['patient']['appoinmant']['reminder'][$rcnt]['appointment_id'] = $value['id'];
                                    $arr_dismissal[$c]['patient']['appoinmant']['reminder'][$rcnt]['appointment_date'] = $s_date;
                                    ////Commened by Shyam 29-12-21
                                    // $arr_dismissal[$c]['patient']['appoinmant']['reminder'][$rcnt]['control_interval'] = $getreminder->control_interval.' '.$getreminder->control_frequency;
                                    ////Added by Shyam 29-12-21
                                    $inGerman = '';
                                    if ($getreminder->control_frequency == 'month') {
                                        $inGerman = 'Monaten';
                                    } elseif ($getreminder->control_frequency == 'week') {
                                        $inGerman = 'Wochen';
                                    } elseif ($getreminder->control_frequency == 'day') {
                                        $inGerman = 'Tagen';
                                    } elseif ($getreminder->control_frequency == 'year') {
                                        $inGerman = 'Jahren';
                                    }
                                    $finalVal = 'in ' . $getreminder->control_interval . ' ' . $inGerman;
                                    $arr_dismissal[$c]['patient']['appoinmant']['reminder'][$rcnt]['control_interval'] = $finalVal;
                                    ////Added by Shyam 29-12-21
                                }
                                $rcnt++;

                            }

                        }
                    }

                    if (
                        (!empty($getDismissalPatient) && sizeof($getDismissalPatient) > 0) ||
                        (!empty($getFinalExamination) && sizeof($getFinalExamination) > 0) ||
                        (!empty($getDismissalPatienthasReminder) && sizeof($getDismissalPatienthasReminder) > 0)
                    ) {


                        if (sizeof($arr_dismissal) > 0) {
                            $arr_dismissal[$c]['patient']['p_id'] = ($getPatients) ? $getPatients->id : 0;
                            $arr_dismissal[$c]['patient']['full_name'] = ($getPatients) ? ucfirst($getPatients->first_name) . ' ' . $getPatients->family_name : "";
                            $arr_dismissal[$c]['patient']['appointment_date'] = $s_date;
                            $arr_dismissal[$c]['patient']['appointment_id'] = $value['id'];
                        }

                    }
                    // ---------------
                    $c++;
                }
            }
        } 
        //dd($arr_dismissal);
        Log::info(count($arr_dismissal));
        return $arr_dismissal;                   
    }

    public function getDismissal()
    {       
        $arr_dismissal = $arr_final_dismissal = [];
        $getDismissalPatient = $this->PatientsHasDismissalModel
                        ->select('patients_has_dismissal.fk_patient_id')
                        ->leftjoin('patients','patients.id','patients_has_dismissal.fk_dismissal_id')
                        ->where('patients_has_dismissal.dismissal_flag','0')
                        ->groupBy('patients_has_dismissal.fk_patient_id')
                        ->get();   
        
        if(count($getDismissalPatient)>0)
        {
            $cnt = 0;
            foreach ($getDismissalPatient as $key => $value) 
            {
                $getDismissalPatient = $this->PatientsHasDismissalModel
                                       ->select('fk_dismissal_id')
                                       ->where('fk_patient_id',$value['fk_patient_id'])
                                       ->where('dismissal_flag','0')
                                       ->groupBy('fk_dismissal_id')
                                       ->get();
                $j=0;                      
                if(!empty($getDismissalPatient) && count($getDismissalPatient)>0)
                {
                    //dd($getDismissalPatient);
                    foreach ($getDismissalPatient as $d_key => $d_value) 
                    {
                        //dd($d_value['fk_patient_id']);
                        $getPatients = $this->PatientsModel->find($value['fk_patient_id']);
                        $arr_dismissal[$cnt]['patient']['p_id']    = $getPatients->id;
                        $arr_dismissal[$cnt]['patient']['full_name'] = ucfirst($getPatients->first_name).' '.$getPatients->family_name;
                        $dismissal = $this->DismissalModel->find($d_value['fk_dismissal_id']);
                        $arr_dismissal[$cnt]['patient']['dismissal'][$j]['id'] = $d_value['id'];
                        $arr_dismissal[$cnt]['patient']['dismissal'][$j]['name'] = $dismissal->name;
                        $j++;
                    }
                }  
                $cnt++;                     
            }
        } 
        //dd($arr_dismissal);
        return $arr_dismissal;                  
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
        try
        {
            $firstLastName = explode(" ", $var);

            //start commented below code on 16-nov-23 
            /*if(array_key_exists(1, $firstLastName))
            {
                $family_name = $firstLastName[0];
                $first_name  = $firstLastName[1];
            }
            else {
                $family_name = $firstLastName[0];
                $first_name = '';
            }
            // dump($firstLastName,$family_name,$first_name);
            $collection = collect([]);
            $collection = $this->PatientsModel
                             ->where('family_name', 'LIKE', $family_name. '%')
                             ->whereStatus(1);
            if(!empty($first_name))
            {
                $collection = $collection->where('first_name', 'LIKE', $first_name . '%');
            }
            if(!empty($var))
            {
                $collection = $collection->orWhere('family_name', 'LIKE', $var . '%');
            }*/
            //end commented below code on 16-nov-23
            

            //start patient search code added below code in 16-nov-23
            /*if(array_key_exists(1, $firstLastName))
            {
                $first_name   = $firstLastName[0];
                $family_name  = $firstLastName[1];
            }
            else {
                $first_name = $firstLastName[0];
                $family_name = '';
            }

            $collection = collect([]);
            $collection = $this->PatientsModel
                            // ->whereRaw("MATCH(patients.first_name) AGAINST('".$first_name."')")
                             ->whereStatus(1);
          
            if(array_key_exists(1, $firstLastName))
            {
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
            }*/                       
            //end patient search code added above code in 16-nov-23


            //start patient search code added below code in 30-nov-23
            if(array_key_exists(1, $firstLastName))
            {
                $first_name   = $firstLastName[0];
                $family_name  = $firstLastName[1];
            }
            else {
                $first_name = $firstLastName[0];
                $family_name = '';
            }
        
            $collection = collect([]);
            $collection = $this->PatientsModel
                              // ->whereRaw("MATCH(patients.first_name) AGAINST('".$first_name."')")
                                ->where('first_name', 'LIKE', '%' .$first_name . '%')
                                ->whereNull('deleted_at')
                                ->whereStatus(1);
            if(!empty($family_name))
            {
                $collection = $collection
                                //->whereRaw("MATCH(patients.family_name) AGAINST('".$family_name."')");
                               ->where('family_name', 'LIKE', '%' .$family_name. '%');
            }
            if(!empty($var))
            {
                $collection = $collection->orWhere('family_name', 'LIKE', '%' .$var . '%');
            }
            //end patient search code added above code in 30-nov-23




            if(!empty($birthdateKey))
            {
                $collection = $collection->whereDate('birth_date', '=', date('Y-m-d',strtotime($birthdateKey)));
            }
            $collection = $collection->get(['id','email','first_name','family_name','birth_date','insurance_number']);
            if((!empty($collection) && sizeof($collection) > 0))
            {
                $message = __('api.DATA_FOUND_SUCCESS');
                if(!empty($popup) && $popup==1)
                {
                    $select_id = 'patient_id';
                    if(!empty($edit) && $edit==1)
                    {
                        $select_id = 'patient_idedit';
                    }
                    $data = '<select class="form-control" id ="'.$select_id.'" name="'.$select_id.'">';
                    $data .= '<option  value="" title="">PatientIn wählen</option>';
                    foreach ($collection as $key => $value)
                    {
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
                            }
                            else {
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
                }
                else {
                    $data = '<select class="form-control" id ="getPatientsData">';//onchange="getPatientsData()"
                    foreach ($collection as $key => $value)
                    {
                        // value="'.$value['id'].'"
                        $patientName = $value['first_name'].' '.$value['family_name'];
                        /*if(empty($value['email'])){
                            $value['email'] = str_replace(" ", "@",strtolower($patientName));
                        }*/
                        $data .= '<option  value="'.$patientName.'">'.$patientName.'</option>';
                    }
                    $data .='</select>';
                }
            }
            else {
                $message = __('api.ERR_NOT_FOUND');
            }
        }
        catch(\Exception $e)
        {
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
                    // dd($modelQuery);

        if (!empty($start_date) && !empty($end_date))  
        {
            if (strtotime($start_date)==strtotime($end_date)){
   
                $modelQuery  = $modelQuery
                                    ->whereDate('appointment.start_date','=',$start_date);

            }else{
                $modelQuery = $modelQuery
                                        ->whereDate('appointment.start_date','>=',$start_date)
                                        ->whereDate('appointment.end_date','<=',$end_date);
            }

        }else if(!empty($start_date) && empty($end_date)) 
        {

            $modelQuery = $modelQuery
                                ->whereDate('appointment.start_date','>=',$start_date);

        }else if(empty($start_date) && !empty($end_date)) 
        {

            $modelQuery = $modelQuery
                                ->whereDate('appointment.end_date','<=',$end_date);
        }   
        $appointments = $modelQuery->get();

        $data=[];
        if(!empty($appointments) && count($appointments)>0){

            foreach ($appointments as $key=>$appointment) {

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
       // dd('_getAuthenticationForToken');
        self::_accessTokenFile();
        // $rurl = secure_url('Admin\DashboardController@_getAuthenticationForToken');
        $rurl = action('Admin\DashboardController@_getAuthenticationForToken');
       // $rurl ='https://dev.eluminousdev.com/trtcle/admin/dashboard/calendar/oauth';
        //dd($rurl);
        $this->client->setRedirectUri($rurl);
        // dd($this->client->isAccessTokenExpired());
        // If there is no previous token or it's expired.
        if ($this->client->isAccessTokenExpired()) 
        {
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
        if (file_exists($this->tokenPath)) 
        {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }

        return $this->client;
    }

    public function eventStore(Request $request)
    {
        // dump('eventStore'); 
        // dd($request->all()); 
        self::_getAuthenticationForToken();
        $service = new Google_Service_Calendar($this->client);
         if(!empty(Config('google_calendar_id')))
        $calendarId = Config('google_calendar_id');
        else
        $calendarId = 'primary';

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
            if(empty($patient_email) || $patient_email==''){
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
                   
                }
            }*/

          //  $event->attendees = $attendees;
      //  }
      // dd($event);
        try{
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

    public function eventUpdate(Request $request)
    {
        self::_getAuthenticationForToken();
        $service = new Google_Service_Calendar($this->client);
         if(!empty(Config('google_calendar_id')))
        $calendarId = Config('google_calendar_id');
        else
        $calendarId = 'primary';

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
        $event = $service->events->get('primary', $eventId);
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

            $updatedEvent = $service->events->update('primary', $event->getId(), $event);

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
        self::_getAuthenticationForToken();
        $service = new Google_Service_Calendar($this->client);
         if(!empty(Config('google_calendar_id')))
        $calendarId = Config('google_calendar_id');
        else
        $calendarId = 'primary';
        $eventId = $request->eventId;

        try{
            $deletedEvent = $service->events->delete($calendarId, $eventId);

            $this->JsonData['status']   = __('admin.RESP_SUCCESS');
            $this->JsonData['data']     = $deletedEvent;
            $this->JsonData['msg']      = 'Google event deleted successfully.'; 
        }catch(Google_Service_Exception $e){
            $msg =json_decode($e->getMessage()); 
            $this->JsonData['status']   = __('admin.RESP_ERROR');
            $this->JsonData['msg']      = $msg->error->message; 
        }

        return response()->json($this->JsonData);
    }

    // public function getEvents(Request $request)
    // {
    //     //dd($request->all());
    //     // new data
    //     self::_getAuthenticationForToken();

    //     $service = new Google_Service_Calendar($this->client);
    //     $calendarId = 'primary';

    //     $out=[];
    //     $last_index = 0;
    //     $google_dates=[];
    //     $start_date = $request->start;
    //     $end_date = $request->end;
        
    //     $colors = $this->GoogleColorsModel->get();
    //     $google_color = [];
    //     foreach ($colors as $color) {
    //         $google_color[$color->id] = $color->code;
    //     }

        
    //     // $getAppointmentRecords = [];

    //     // $roleName = strtolower(auth()->user()->getRoleNames()->first());
    //     // $roleDoctorAssistant = false;
    //     // if($roleName=="doctor")
    //     // {
    //     //     $roleDoctorAssistant = true;
    //     //     $getAppointmentRecords = $this->AppointmentModel
    //     //                                 ->where('doctor_id',auth()->user()->id)
    //     //                                 ->get();
    //     // }else if($roleName=="assistant"){
            
    //     //     $roleDoctorAssistant = true;
    //     //     $doctor_id = auth()->user()->doctor_id;

    //     //     $getAppointmentRecords = $this->AppointmentModel
    //     //                                 ->where('doctor_id',$doctor_id)
    //     //                                 ->get();
    //     // }
    //     // dd($roleName,auth()->user()->hasRole('Doctor'),$getAppointmentRecords);


    //    // dd($google_color);
    //     //$type = $request->type;
    //     //$start_date=Carbon::yesterday();
    //     //$start_date=  new Carbon('first day of January 2018');
    //     //$start_date=Carbon::create(2019, 6, 3, 0, 0, 0);;

    //     if(!empty($start_date) && !empty($end_date)){

    //         //On click of each day
    //         // $start_date=Carbon::create($request->year, $request->month,$request->day, 0, 0, 0);
    //         // $end_date=Carbon::create($request->year, $request->month,$request->day, 23,59, 59);  
    //         // Print the next 10 events on the user's calendar.
    //         $optParams = array(
    //           'maxResults' => 3000,
    //           'orderBy' => 'startTime',
    //           'singleEvents' => true,
    //           'timeMin' => date("c", strtotime(date('Y-m-d H:i:s',strtotime($start_date)).'0 days')),
    //           'timeMax' => date("c", strtotime(date('Y-m-d H:i:s',strtotime($end_date)).'0 days')),
    //           'timeZone'=>'Europe/Berlin'
    //         );
    //         // $optParams['timeMin'] = date("c", strtotime(date('Y-m-d H:i:s').'-3 days'));
    //         // $optParams['timeMax'] = date("c", strtotime(date('Y-m-d H:i:s').'0 days'));
            
    //     }else{
    //         //get all events
    //        //  $start_date=Carbon::create(2000,1,1, 0, 0, 0);  
    //        //  $live_lecture_start_date = Date('Y-m-d',strtotime("2000-1-1"));
    //        //  $live_lecture_condition = ">=";
    //        // // dd($start_date);    
    //        //  $events = Event::get($start_date);    
    //         $optParams=array();
            
    //     }
    //     try{


    //           if(!empty($optParams) && count($optParams)>0){
    //             $results = $service->events->listEvents($calendarId, $optParams);
    //           }else{
    //             $results = $service->events->listEvents($calendarId);
    //           }  
    //          //dd($roleDoctorAssistant);
    //          // dd($results->getItems());
    //          /* $colors = $service->colors->get();

    //           dd($colors);*/
    //           $events = $results->getItems();

    //           // if($type=='google' || $type=='all'){
    //            $date = '';
    //            $last_index = 0;
    //             foreach ($events as $key => $event) {
               
                    
    //                 $date = date("Y-m-d",strtotime($event->getStart()->getDateTime()));
    //                 $pushData = false;
    //                 /*if($roleDoctorAssistant == true)
    //                 {
    //                     foreach ($getAppointmentRecords as $appRec) {
                            
    //                         $appDate = $appRec->start_date;
    //                         $eventDate = date("Y-m-d H:i:s",strtotime($event->getStart()->getDateTime()));
    //                         if(strtotime($appDate) == strtotime($eventDate)){
    //                             $pushData = true;
    //                             break;
    //                         }
    //                        // dump($appDate,$eventDate,$pushData);

    //                     }

    //                 }*/


    //                 //if($pushData==true || $roleDoctorAssistant==false){
    //                 //dd($event->getStart()->getDateTime(),$event->getEnd()->getDateTime());
                 
    //                 //$st_date = date("H:i",strtotime($event->getStart()->getDateTime()));

    //                     $out[$last_index]['id']            =   $event->id;
    //                     $out[$last_index]['title']         =   ucfirst($event->getSummary()); 
    //                     $out[$last_index]['description']   =   ucfirst($event->getDescription()); 

    //                     $out[$last_index]['date']          =   strtotime($date)."000";
    //                     $out[$last_index]['start']         =   $event->getEnd()->getDateTime();
    //                     $out[$last_index]['end']           =   $event->getEnd()->getDateTime(); 

    //                     $out[$last_index]['backgroundColor'] =  "#f6c026";
                        
    //                     $color_id = $event->colorId;
    //                     if(!empty($color_id)){
    //                        $out[$last_index]['backgroundColor'] = $google_color[$color_id];
    //                     }

    //                     $out[$last_index]['allDay'] =  false;
    //                     //$out[$last_index]['patient_id'] =  2;
    //                    // print_r($event->attendees);
    //                     // exit();

    //                     $out[$last_index]['patient_name'] = '';
    //                     $out[$last_index]['doctor_name']  = '';

    //                     // $out[$last_index]['patient_email'] = '';
    //                     // $out[$last_index]['doctor_email'] = 'adsdfd@asdf.com';
    //                     // if(!empty($event->attendees) && count($event->attendees)>0){
    //                     //     if(array_key_exists(1, $event->attendees)){

    //                     //         $doctor_email_exist = $this->AdminUserModel
    //                     //                                 ->where('email',$event->attendees[1]['email'])
    //                     //                                 ->first();
    //                     //                                 //dump($doctor_email_exist);
    //                     //         if(!empty($doctor_email_exist)){
    //                     //             $out[$last_index]['patient_email'] = $event->attendees[0]['email'];
    //                     //             $out[$last_index]['doctor_email'] = $event->attendees[1]['email'];
    //                     //         }else{
    //                     //             $out[$last_index]['patient_email'] = $event->attendees[1]['email'];
    //                     //             $out[$last_index]['doctor_email'] = $event->attendees[0]['email'];
    //                     //         }

                                
    //                     //     }else{
    //                     //         $out[$last_index]['patient_email'] = $event->attendees[0]['email'];
    //                     //     }
    //                     // }
    //                     //dump($event->attendees);
    //                    /*$out[$last_index]['obj_date']      =   $date; 
    //                     //$out[$last_index]['obj_datetime']  =   $dateTime; 
    //                     $out[$last_index]['obj_time']      =   date("H:i:s",strtotime($event->getStart()->getDateTime()));
    //                     $out[$last_index]['end_date']      =   $event->getEnd()->getDateTime();*/ 

    //                     $out[$last_index]['event_type']    =   'google'; 
    //                     //$last_index                 =   $last_index;
                        
    //                     $splitDescription = explode("</p><p><strong>", $out[$last_index]['description']);

    //                     $out[$last_index]['patient_name']  = trim(str_replace("<p><strong>Patient:</strong>", "", $splitDescription[0]));
    //                     $out[$last_index]['doctor_name'] = trim(str_replace("Arzt:</strong>", "", $splitDescription[1] ?? ''));

    //                     $ganny_id = $this->AppointmentModel
    //                                 ->leftjoin('patients','patients.id' , 'appointment.patient_id')
    //                                 ->where('google_event_id',$event->id)
    //                                 ->first(['appointment.id','patients.id as patient_id']);
             
    //                     if(!empty($ganny_id))
    //                     {
    //                         $str = $ganny_id->id.'-'.$ganny_id->patient_id;

    //                         $qr_code = '<img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl='.$str.'&choe=UTF-8">';
                          
    //                         $out[$last_index]['qr_code'] =  $str; 
    //                     }  else
    //                     {

    //                     $out[$last_index]['qr_code'] =  '';
    //                     } 
    //                    // array_push($google_dates, $out[$last_index]['date']);

    //                     $last_index++;  
    //                 //}
                                                
    //             }

    //             // $eventId='cipsr5ulf73n72v29stgfreio8';
    //             // $event = $service->events->get('primary', $eventId);
    //             // dd($event);
               
    //            // }

    //         }catch(Google_Service_Exception $e){
    //              //dd($e);
    //              //return redirect()->route('oauthCallback');     
    //         }
       
    //   //  dd($out);
    //     return $out;
    // }
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
                  
            }
       
       
        return json_encode($resource_val);
    }

    public function getEvents(Request $request)
    {
        // new data
        // self::_getAuthenticationForToken();
        // $service = new Google_Service_Calendar($this->client);
        // if(!empty(Config('google_calendar_id')))
        // {
        //     $calendarId = Config('google_calendar_id');
        // }
        // else {
        //     $calendarId = 'primary';
        // }
        $out = [];
        $last_index = 0;
        $google_dates = [];
        $start_date = $request->start;
        $end_date = $request->end;
        $colors = $this->GoogleColorsModel->get();
        $google_color = [];
        foreach ($colors as $color) {
            $google_color[$color->id] = $color->code;
        }
        if (!empty($start_date) && !empty($end_date)) {

            $optParams = array(
                'maxResults' => 3000,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => date("c", strtotime(date('Y-m-d H:i:s', strtotime($start_date)) . '0 days')),
                'timeMax' => date("c", strtotime(date('Y-m-d H:i:s', strtotime($end_date)) . '0 days')),
                'timeZone' => 'Europe/Berlin'
            );

        } else {

            $optParams = array();
        }
        try {

            $event_start_date = Carbon::parse($request->start)->format('Y-m-d');
            $event_end_date = Carbon::parse($request->end)->format('Y-m-d');
            $query = Event::whereBetween('start_date_time', [$event_start_date, $event_end_date])->whereHas('appointments', function ($query) {
                $query->where('is_app_booked', 1);
            })->with(['appointments', 'patient', 'doctor']);
            $events = $query->get();
            $date = '';
            $last_index = 0;
            foreach ($events as $key => $event) {
                //This IF condition Added by Shyam 01-02-22
                // $checkEventId = $this->AppointmentModel
                //     ->where('is_app_booked', 1) //added by vijay 16/4/2024 
                //     ->where('event_id', $event->id)->get(['id']);
                // if (!empty($checkEventId) && sizeof($checkEventId) > 0) {
                // $date = date("Y-m-d",strtotime($event->getStart()->getDateTime()));
                $date = date("Y-m-d", strtotime($event->start_date_time));
                $pushData = false;

                $out[$last_index]['id'] = $event->id;
                // $out[$last_index]['title']         =   ucfirst($event->getSummary()); 
                $out[$last_index]['title'] = ucfirst($event->summary);
                $out[$last_index]['description'] = ucfirst($event->description); //ucfirst($event->getDescription()); 
                $out[$last_index]['date'] = strtotime($date) . "000";
                $out[$last_index]['start'] = $event->start_date_time;  //$event->getStart()->getDateTime();  
                $out[$last_index]['end'] = $event->end_date_time; //$event->getEnd()->getDateTime(); 
                $out[$last_index]['backgroundColor'] = "#f6c026";
                $color_id = $event->color_id;




                $out[$last_index]['allDay'] = false;
                //$out[$last_index]['patient_id'] =  2;
                // print_r($event->attendees);
                // exit();
                $out[$last_index]['patient_name'] = '';
                $out[$last_index]['doctor_name'] = '';

                $out[$last_index]['event_type'] = 'google';
                //$last_index                 =   $last_index;
                $splitDescription = explode("</p><p><strong>", $out[$last_index]['description']);
                $out[$last_index]['patient_name'] = trim(str_replace("<p><strong>Patient:</strong>", "", $splitDescription[0]));


                //added on 14-oct-24             
                // $ganny_id = $this->AppointmentModel
                //     ->leftjoin('patients', 'patients.id', 'appointment.patient_id')
                //     ->leftjoin('users as doctors', 'doctors.id', 'appointment.doctor_id')
                //     ->where('event_id', $event->id)
                //     ->first(['appointment.id', 'patients.id as patient_id', 'doctors.google_color_id', 'doctors.first_name', 'doctors.last_name']);

                //dd($event->id,$ganny_id);
                // if (!empty($ganny_id)) {

                //start Added on 14-oct-24    
                if (!empty($event->doctor)) {

                    $google_color_id = $event->doctor[0]['google_color_id'] ?? null; // or use a default value
                } else {
                    $google_color_id = null;
                }
                // $google_color_id = $event->doctor['0']['google_color_id'];
                if (!empty($color_id) && $google_color_id) {

                    $out[$last_index]['backgroundColor'] = $google_color[$google_color_id];
                } else {
                    $out[$last_index]['backgroundColor'] = $google_color[$color_id];
                }
                //end Added on 14-oct-24 

                $appointment_id = $event->appointments['0']['id'];
                $patient_id = $event->patient['0']['id'];


                $str = $appointment_id . '-' . $patient_id;
                $qr_code = '<img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . $str . '&choe=UTF-8">';
                $out[$last_index]['qr_code'] = $str;
                $endcode_id = base64_encode(base64_encode($appointment_id));
                $out[$last_index]['appoinmant_id'] = $endcode_id;
                // } else {
                //     $out[$last_index]['qr_code'] = '';
                //     $out[$last_index]['appoinmant_id'] = null;
                // }
                // array_push($google_dates, $out[$last_index]['date']);
                $out[$last_index]['resourceId'] = $event->doctor['0']['first_name'] . ' ' . $event->doctor['0']['last_name'];
                $out[$last_index]['doctor_name'] = $event->doctor['0']['first_name'] . ' ' . $event->doctor['0']['last_name'];
                $last_index++;
                //}
                // }
            }
            // $eventId='cipsr5ulf73n72v29stgfreio8';
            // $event = $service->events->get('primary', $eventId);
            // dd($event);
            // }
        } catch (Google_Service_Exception $e) {
            //return redirect()->route('oauthCallback');     
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
                                $html.="<option ".$selected." value='".$time."' attr='".$time_frame->r_id."'>".$time."</option>";
                            }

                        }elseif(strtotime($today_date)!==strtotime($appointment_date)){

                            $html.="<option ".$selected." value='".$time."' attr='".$time_frame->r_id."'>".$time."</option>";
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
    public function store(AssistantDashboardRequest $request)
    {
        Log::info("in assistant Dashboard store admin function ");
        Log::info($request->all());


        $urlEventId = $urlPatientId = '';$startDate=date("Y-m-d");
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_CREATE');

        try {

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
                       // $this->JsonData['msg'] = __('admin.ERR_EMAIL_DUP_PATIENT');   //commneted on 15-dec-23 for duplicate patient

                        $this->JsonData['msg'] = __('admin.ERR_MOBILE_UNIQUE'); // did changes on 15-dec-23 for duplicate patient

                        $this->JsonData['status']   = __('admin.RESP_ERROR');
                        return response()->json($this->JsonData);
                        exit();
                    }
                    $patient_data     = new $this->PatientsModel;    
                    $patient_data     = self::_storePatient($patient_data,$request);
                    if(!empty(Config('ordination_id')))
                    {
                        $ordination_patient = self::_storePatientOrdination($patient_data->id);
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
                $collection     = new $this->BaseModel;   
                $request['start_date'] = date("Y-m-d H:i",strtotime($request->date." ".$request->time_frame));
                $request['end_date']  = self::_getEndDate($request['start_date'],$request['appointment_type_id']);

                // added by vijay 12/9/2024
                $loginUser = Auth::user();

                $collection->appointment_created_from = 2;
                $collection->optimal_appointment = $request->quarter_setting_check ? $request->quarter_setting_check : null;
                $collection->appointment_createdby = $loginUser->id;
                // end
                $collection     = self::_storeOrUpdate($collection,$request);

                //=============================================================== 

                $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                        ->where('id',$request->roster_time_frame_id)
                                        ->update([
                                                 'time_frame_flag'=>'2',
                                                 'time_frame_flag_date'=>Date('Y-m-d H:i:s'),
                                                 'comment'=>'AssistantDashboardController store booking function app Date:'.date('Y-m-d H:i:s', strtotime($collection->start_date)).' current date:'.Date('Y-m-d H:i:s').' patient_id: '.$patient_id
                                             ]);


               // //===============================================================             
               //  self::_activateReminderOnCancel($collection);
                //================================================================= 
                self::_deactivateReminderNew($collection,$request->app_services); 
                $newData = $collection->toArray();

                $all_transactions = [];
                $notify_data = [];
                if ($collection) 
                {
                    $all_transactions[] = 1;
                     //insert the entry for patient has document
                    $getDocument = self::_GetAssignedDocument($collection->id,$collection->appointment_type_id,$request->app_services,$collection->patient_id);
                    // END
                    //insert the entry for patient has Checklist
                    $getDocument = self::_GetAssignedCheckList($collection->id,$request->app_services,$collection->patient_id);
                    // END
                    $patient_doc_data = [];
                    $patient_doc_data[] = array(
                                                'appointment_id'=> $collection->id,
                                                'patient_id'    => $collection->patient_id,
                                                'exam_app_type_id'=> $request['appointment_type_id'],
                                                'record_type'   => 1,
                                                'doc_status'   => 0,
                                                );
                    // dd($patient_doc_data);

                    if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                        $all_transactions[] = 1;
                    }else{
                        $all_transactions[] = 0;
                    }

                    $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType'])->find($collection->id);   
                        
                    $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                    $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                    $appointmentType = $collection->assignedAppointmentType->name;
                    $booking_month = __('admin.'.date('F',strtotime($request->start_date)),[],'de');
                    $appointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                    //commented on 6-nov-23
                    // $patientText = $collection->assignedPatient->salutation ?? "";
                    // $patientText .= " ".$collection->assignedPatient->family_name;

                    // $patientText = $collection->assignedPatient->salutation ? " ".$collection->assignedPatient->salutation.'.': ""; //changed on 6-nov-23 added dot after salutation on 14-dec-23 //commented on 12-dec-25


                     $patientText = $collection->assignedPatient->salutation ? $collection->assignedPatient->salutation.'.': ""; //changed on 6-nov-23 added dot after salutation on 14-dec-23 changed on 12-dec-25



                    // $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name; //added first name on 6-nov-23 commented on 12-dec-25

                     //changed on 12-dec-25
                     if(isset($collection->assignedPatient->salutation)){
                        $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name; //added first name on 6-nov-23
                     }else{
                        $patientText .= $collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name; //added first name on 6-nov-23
                     }

                    

                    $doctorSurname = $collection->assignedDoctor->last_name;
                    //Appoinment Push Notification

                    //Commented on 6-nov-23
                    // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.'('.$appointmentType.') ist am'.' '.(string)$appointmentTime;

                    $notify_times = self::_getNotifyTime($request['start_date']);


                    //commented below code on 13-feb-24 for notification setting section
                    /*
                    $content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime; //content changed on 6-nov-23 //added space after doctor surname on 14-dec-23


                    foreach ($notify_times as $notify_time) {
                        
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
                    else
                    {
                        $all_transactions[] = 0; 
                    } */



                    /************added code on 13-feb-24***for notification from setting section*******/

                    $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));

                    $skipNotification = false; //added on 12-nov-25


                    $getSetting  = $this->AppointmentHasNotificationModel->whereStatus(3)->first();
                    if(isset($getSetting) && !empty($getSetting))
                    {   

                        $title = $getSetting->title;
                        $content = $getSetting->content;
                        $day = $getSetting->day;
                        $notify_time = $getSetting->notify_time;
                        $appointmentDate =  date("Y-m-d",strtotime($request->start_date));


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
                        \Log::info("Skipped notification save for appointment ID {$collection->id} — appointment time is before notify time.");
                    }
                    //end changed on 12-nov-25


                    /***********end code**of notification setting**13-feb-24*******************/

                    Log::info("admin assistant dashboard store before appointment exam store ");


                    //Default appintment 
                    $getServises = self::_appointmentTypesAgaintsServices($collection->id,$request,$patient_id);
                    $serviceEventType = self::GetServicesEventType($collection->id,$patient_id,$request->app_services,$request['appointment_type_id'],'admin');
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
                     //dd($postCalDetails);
                    if(!empty($postCalDetails) && $postCalDetails->original['status'] == 'success')
                    {
                        $all_transactions[] = 1;

                        $eventId = $postCalDetails->original['data']->id;
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

                        $this->ActivityLogModel->addLog($this->ModuleTitle,'has created appointment','Add',null,$newData);
                        //add reminders for pass appointments added by swati 9-Jun-23================================================
                        $newdate=date("Y-m-d",strtotime($request['startDateTime']));
                        $todayDate=date('Y-m-d');
                        if($newdate < $todayDate){
                            $this->_remindersPassAppointments($collection->id);
                        }
                        //==============================================================

                    }else{
                        $all_transactions[] = 0;
                        DB::rollback();
                        $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $postCalDetails->original['msg'];
                    }
                   
                }else{
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
            else
            {
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.APPOINTMENT_SLOT_ALREADY_EXIST');
            }    
           
        }
        catch(\Exception $e) {
            DB::rollback();

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
                $country_code = $patientData->country_code;
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

        Log::info("in assistant dashboard controller _storePatient function");
        Log::info($request->all());

        if(!empty($request->birth_date)){
            $birth_date                  = date('Y-m-d', strtotime($request->birth_date));
            $age                         = (date('Y') - date('Y',strtotime($birth_date)));
        }else{
            $birth_date                  = NULL;
            $age                         = 0;
        }

        $collection->first_name         = $request->first_name; 
        $collection->family_name        = $request->family_name;
        
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
        $collection->gender              = $request->gender;
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
        // added by vijay 8/3/24
        $quarter_setting = 0;
        $optimal_appointment = $this->SettingsModel->where(['setting_key' => 'OPTIMAL_APPOINTMENT'])->select('setting_key', 'setting_value')->first();
        if (isset ($optimal_appointment) && !empty ($optimal_appointment)) {
            $quarter_setting = $optimal_appointment->setting_value;
        }
        $this->ViewData['quarter_setting'] = $quarter_setting;


        // All patients 
       /* 
       $this->ViewData['patient'] = $this->PatientsModel
                                        ->where('status', 1)
                                        ->get(); */

        // All appointment types 
        $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->get(); 
        $this->ViewData['country_codes'] = $this->CountryCodesModel
            ->where('is_active',1)
            // ->orderBy('phone_code')
            ->pluck('phone_code')
            ->toArray();
        // view file with data
        return view($this->ModuleView.'create', $this->ViewData);
    }

    public function edit($encID)
    {
        // dd($encID);
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

            $this->ViewData['appointment'] = $appointment;

            // All patients  
            $this->ViewData['patient'] = $patients;  
             // ############# Roshani Added this code on (28/02/2024) ################# 
            $discardIdsfromAppType = $this->UserHasAppointmentType->where('user_id',$appointment->doctor_id)->pluck('appointment_type_id')->toArray();
            $filteredTypeIds = collect($discardIdsfromAppType)->diff([$appointment->appointment_type_id])->values()->all();

            // $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->whereNotIn('id',$filteredTypeIds)->get(); //commented on 13-apr-26

             $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->whereNotIn('id',$filteredTypeIds)->withTrashed()->get(); //changed on 13-apr-26


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
                                            //->where('status', 1)
                                            ->where('id',$appointment->doctor_id)
                                            ->whereHas('roles',function($query){
                                               $query->where('name', 'doctor');
                                            })
                                            ->first(); 

            $appointment_types = $this->AppointmentTypesModel
                                            ->where('id',$appointment->appointment_type_id)
                                            ->withTrashed() //added on 13-apr-26
                                            ->first(); 
            
            
            
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
          //  $doctor_name     = $doctor_name->first_name ?? ''; //commented on 30-nov-23
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

                    if(isset($value['assignedExamination']->id))
                    {
                      $str_exam .= "<h6 style='margin-left: 80px;'><input disabled type='checkbox' checked class='form-check-input' name='app_services[]'
                            name='status' value=".$value['assignedExamination']->id." 
                            >".$value['assignedExamination']->name."<h6>"; 
                    } 
                }; 
                $str_exam .= "</p>";
            }
            $data ='<p><strong>Patient:</strong> '.$first_name.' '.$patients->family_name.'</p><p><strong>Arzt:</strong> '.$doctor_first_name.' '.$doctor_last_name.'</p><p><strong>Typ:</strong> '.$appointment_types->name.'</p><p><strong>Beginn:</strong> '.date('F d,Y H:i',strtotime($appointment->start_date)).'</p><strong>Ende: </strong>'.date('F d,Y H:i',strtotime($appointment->end_date)).'<p></p><p><strong>Notizen: </strong>'.$appointment->notes.'</p>'.$str_exam;  

            return $data;                             
            
        } 
        else
        {
            return $data;
        }
        
    }

    public function update(AssistantDashboardRequest $request,$encID)
    {

        Log::info("in assistant dashboard update function ");
        Log::info($request->all());

        $id = base64_decode(base64_decode($encID));

        Log::info("in admin assistant dashbaord controller update function id ");
        Log::info($id);

        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_APPOINTMENT_UPDATE');       
              
        // try { roster_time_frame_id1

            DB::beginTransaction(); 
            $request['start_date'] = date("Y-m-d H:i",strtotime($request->date." ".$request->time_frame));
            $request['end_date']  = self::_getEndDate($request['start_date'],$request['appointment_type_id']);

            $duplicationAppointmantself = self::_checkDuplicationAppointmant($request,$id);
            $checkCompleteAppointment = $this->BaseModel->find($id);  
            //if(empty($duplicationAppointmantself) && sizeof($duplicationAppointmantself)==0)
            if(empty($duplicationAppointmantself) && sizeof($duplicationAppointmantself)==0 && !empty($checkCompleteAppointment) && $checkCompleteAppointment->appointment_status!='Fertig')
            {
                // $this->PatientHasDocumentsModel->where(['appointment_id'=>$id,'patient_id'=>$request->patient_id])->delete();
                // $patient_doc_data[] = array(
                //                                 'appointment_id'=> $id,
                //                                 'patient_id'    => $request->patient_id,
                //                                 'exam_app_type_id'=> $request->appointment_type_id,
                //                                 'record_type'   => 1,
                //                                 'doc_status'   => 0,
                //                                 );
                // //dd($patient_doc_data);

                // if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                //     $all_transactions[] = 1;
                // }else{
                //     $all_transactions[] = 0;
                // }
                $this->PatientHasDocumentsModel->where(['appointment_id'=>$id,'patient_id'=>$request->patient_id])->delete();

                $this->CheckListHasSelectedQuestionModel->where(['fk_appointment_id'=>$id,'fk_patient_id'=>$request->patient_id])->delete();

                $getDocument = self::_GetAssignedDocument($id,$request->appointment_type_id,$request->app_services,$request->patient_id);
                    // END

                    //insert the entry for patient has Checklist
                $getDocument = self::_GetAssignedCheckList($id,$request->app_services,$request->patient_id);

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
                $collection->appointment_updatedby = $loginUser->id;
                // end
                $collection = self::_storeOrUpdate($collection,$request);
                $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType'])->find($id); 


                // 

                $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                        ->where('id',$request->roster_time_frame_id1)
                                        ->update([
                                                  'time_frame_flag'=>'2',
                                                  'time_frame_flag_date'=>Date('Y-m-d H:i:s'),
                                                  'comment'=>'AssistantDashboardController update booking function app Date:'.date('Y-m-d H:i:s', strtotime($collection->start_date)).' current date:'.Date('Y-m-d H:i:s').' patient_id: '.$collection->patient_id
                                                 ]);


               
                //
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

                //=====================================================
                 self::_activateReminderOnEdit($collection);
                 self::_deactivateReminderNew($collection,$request->app_services);
                 
                $all_transactions = [];
                $notify_data = [];
                if ($collection) 
                {
                    $all_transactions[] = 1;

                    $this->AppointmentHasNotificationModel->where('appointment_id',$collection->id)->delete();

                    $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                    $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                    $appointmentType = $collection->assignedAppointmentType->name;
                    $summary = $patientName." - ".$appointmentType;
                    $description = '<p><strong>'.$this->patientText.':</strong> '.$patientName.' </p><p><strong>'.$this->doctorText.':</strong> '.$doctorName.' </p><p><strong>'.$this->appointmentText.':</strong> '.$appointmentType.' </p><p><strong>'.$this->startDateText.':</strong> '.date('F d,Y H:i',strtotime($request->start_date)).' </p><strong>'.$this->endDateText.':</strong> '.date('F d,Y H:i',strtotime($request->end_date)).' </p><p><strong>'.$this->notesText.':</strong> '.$request->notes.' </p>';

                    $booking_month = __('admin.'.date('F',strtotime($request->start_date)),[],'de');
                    $appointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                    //commented on 6-nov-23
                    // $patientText = $collection->assignedPatient->salutation ?? "";
                    // $patientText .= " ".$collection->assignedPatient->family_name;

                     //changed on 6-nov-23
                    // $patientText = $collection->assignedPatient->salutation ? " ".$collection->assignedPatient->salutation.'.': ""; //added dot after salutation on 14-dec-23 commented on 12-dec-25


                    $patientText = $collection->assignedPatient->salutation ? $collection->assignedPatient->salutation.'.': ""; //added dot after salutation on 14-dec-23 changed on 12-dec-25


                    // $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name; //changed on 6-nov-23 commented on 12-dec-25

                    if(isset($collection->assignedPatient->salutation)){
                        $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name; //changed on 6-nov-23
                    }else{
                        $patientText .= $collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name; //changed on 6-nov-23
                    }
                    


                    $doctorSurname = $collection->assignedDoctor->last_name;

                    //Appoinment Push Notification
                    //commented on 6-nov-23
                    // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.'('.$appointmentType.') ist am'.' '.(string)$appointmentTime;

                    $notify_times = self::_getNotifyTime($request['start_date']);


                    //commented below code on 13-feb-24 for notification setting

                    /*$content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime; //changed on 6-nov-23 //added space after doctor surname on 14-dec-23

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
                    }*/


                    /********added code on 13-feb-24***for notification from setting section*******/

                    $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));

                    $skipNotification = false; //added on 12-nov-25


                    $getSetting  = $this->AppointmentHasNotificationModel->whereStatus(3)->first();
                    if(isset($getSetting) && !empty($getSetting))
                    {   

                        $title = $getSetting->title;
                        $content = $getSetting->content;
                        $day = $getSetting->day;
                        $notify_time = $getSetting->notify_time;
                        $appointmentDate =  date("Y-m-d",strtotime($request->start_date));

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


                    /***********end code**of notification setting***13-feb-24**************/





                    //Default appintment 
                    $this->AppointmentHasExaminationsModel->where(['appointment_id'=>$id,'patient_id'=>$request->patient_id])->delete();

                    $this->EventTypeHasExaminationsModel->where(['appoinment_id'=>$id,'patient_id'=>$request->patient_id])->delete();

                    Log::info("in admin assistant dashboard controller update function before exam store");

                    
                    $getServises = self::_appointmentTypesAgaintsServices($id,$request,$request->patient_id);
                    $serviceEventType = self::GetServicesEventType($collection->id,$request->patient_id,$request->app_services,$collection->appointment_type_id,'admin');
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
                    // Log::info($this->ModuleTitle.'has updated appointment by AssistantDashboardController');
                    $debug_arr['data'] = 'has updated appointment by AssistantDashboardController';    
                    $res_name = "AssistantDashboardController_update";   
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

                if (!in_array(0,$all_transactions)) 
                {
                    DB::commit();

                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']      =  route($this->ModulePath);
                    $this->JsonData['msg']      = __('admin.APPOINTMENT_UPDATED');

                     // Send Mail / SMS on reschedule/update added on 24-march-26
                    $updatedAppointment = $this->BaseModel->with(['assignedPatient'])->find($id);

                    Log::info("assistant updatedAppointment==>");
                    Log::info($updatedAppointment); 

                    $appointmentStartDate = date("Y-m-d", strtotime($updatedAppointment->start_date));
                    $todayDate = date('Y-m-d');

                    if (!empty($updatedAppointment->google_event_id) && !empty($updatedAppointment->patient_id) && $appointmentStartDate >= $todayDate)
                    {
                        $channels    = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();
                        $patientData = $this->PatientsModel->where('id', $updatedAppointment->patient_id)->first();

                        Log::info("assistant updatedAppointment channels==>");
                        Log::info($channels); 

                        Log::info("assistant updatedAppointment patientData==>");
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
                    // end of code to send sms or email as per setting while updating the appointment
                }//if transactions
            }
            else
            {
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                //$this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['url']      =  route($this->ModulePath);
                $this->JsonData['msg']      = __('admin.APPOINTMENT_SLOT_ALREADY_EXIST');
            }    
            
        // }
        // catch(\Exception $e) {  
        //     DB::rollback();
        //     $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
        //     $this->JsonData['error_msg'] = $e->getMessage();
        // }

    

        return response()->json($this->JsonData);
    }
    
    public function getPatientsData($pID , Request $request)
    {   
       
        // new data
        self::_getAuthenticationForToken();

        $service = new Google_Service_Calendar($this->client);
         if(!empty(Config('google_calendar_id')))
        $calendarId = Config('google_calendar_id');
        else
        $calendarId = 'primary';

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
        if(!empty(Config('google_calendar_id')))
        $calendarId = Config('google_calendar_id');
        else
        $calendarId = 'primary';

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
            //===============================================================             
                self::_activateReminderOnCancel($collection);
            // ==============deleted track============================
                self::DeletedAppointmentTrack($collection);
            // ------------------------------------
            if($collection->delete())
            {
                $newData = $collection->toArray();
                $this->AppointmentHasNotificationModel->where('appointment_id',$collection->id)->delete();

                $request = array(
                                 'eventId'=>$collection->google_event_id,
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
            if(!empty($time_slot) && count($time_slot)>0)
                $item->available_time = implode(", ",$time_slot);
            else
                $item->available_time = '-';
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
          <div id="notice_edit_click">'.$div_data.'</div>
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
            // if($value->count > 0)
            // {
                $data .='<b> '.$value->name.':</b> '.$value->count.' '.__('admin.TITLE_APPOINTMENT_TEXT')."<br/>";
            // }
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

    public function getRecordsForWaitingList(Request $request)
    {
        /*--------------------------------------
        |  Variables
        ------------------------------*/
  
            // skip and limit
            $start  = $request->start;
            $length = $request->length;

            // serach value
            $search = $request->search['value']; 

            // order
            $column = $request->order[0]['column'];
            $dir    = $request->order[0]['dir'];

            // filter columns
            $filter = array(
                0 => 'appointment_has_queue_number.id',
                1 => 'appointment_has_queue_number.id',
                2 => 'appointment_has_queue_number.queue_number',
                3 => 'appointment_has_queue_number.queue_number_type',
                4 => 'appointment_has_queue_number.created_at',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->AppointmentHasQueueNumberModel
                            ->select('appointment_has_queue_number.*')
                            ->leftjoin('appointment', 'appointment.id' , '=', 'appointment_has_queue_number.appointment_id');
                              
            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

            // get total filtered
            $filteredQuery  = clone($modelQuery);            
            $totalFiltered  = $filteredQuery->count(); 
            
            // offset and limit
            $object = $modelQuery->orderBy($filter[$column], $dir)
                                ->skip($start)
                                ->take($length)
                                ->get();            
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = []; 
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row)  
                { 
                    $queue_number_type = $row->queue_number_type == 0?'App':'Tablet';
                    $data[$key]['id']           = $row->id;
                    if(!empty($row->appointment_id) && $row->appointment_id>0)
                    {
                        $getAppoinmentDetails = $this->AppointmentModel->find($row->appointment_id);

                        if(!empty($getAppoinmentDetails))
                        {
                            $getPatients = $this->PatientsModel->find($row->patient_id);
                          
                            if(!empty($getPatients))
                            {

                                $data[$key]['full_name']  =  "<span title='".$getPatients->first_name.' '.$getPatients->family_name."'>".$getPatients->first_name.' '.$getPatients->family_name."</span>"; 
                            }
                            else
                            {
                                $data[$key]['full_name']  =  " "; 
                            }
                            
                        }
                        else
                        {
                            $data[$key]['full_name']  =  " "; 
                        }
                       
                    }
                    else
                    {
                        $data[$key]['full_name']  =  " "; 
                    }
                    
                    $data[$key]['queue_number']  =  "<span title='".$row->queue_number."'>".$row->queue_number."</span>";
                    if($queue_number_type == 'App')
                    {
                        $data[$key]['queue_number_type']  =  '<a target="_blank" class="btn fc-button-primary" href="'.url('admin/assistant-dashboard/viewAppoinmant/'.base64_encode(base64_encode($row->id))).'" >'.$queue_number_type.'</a>';
                    }
                    else
                    {
                        $data[$key]['queue_number_type']  =  "<span>".$queue_number_type."</span>";;
                    }
                    
                    $data[$key]['created_at']  =  "<span>".$row->created_at."</span>";
                  

                    $called_status = 'fa-microphone-slash';
                    $called_status_title = 'Not Called';
                    if($row->called_status==1){
                        $called_status = 'fa-microphone';
                        $called_status_title = 'Called';
                    }

                    $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.waiting-queue-number.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>'; 
                   
                    $updateCallStatus = '<span class="theme-green semibold text-center f-18" title="Call to Patient"><a href="javascript:void(0)" class="delete-user action-icon" title="'.$called_status_title.'" onclick="return updateCallStatus(this)" data-href="'.route('admin.waiting-queue-number.updatecallstatus', [base64_encode(base64_encode($row->id))]) .'" data-status='.$row->called_status.'><i class="fa '.$called_status.'" aria-hidden="true"></a></i></span>'; 

                    if ($row->status==1)  
                    {
                        $data[$key]['actions'] = '  '.$updateCallStatus.' '.$delete.'</span>';
                    }
                    else 
                    {
                        $data[$key]['actions'] = '<span class="theme-black-light semibold text-center f-18" ></i>  '.$delete.' '.$updateCallStatus.'</span>';
                    } 
                    $data[$key]['actions'] = '<span class="theme-black-light semibold text-center f-18" ></i>  '.$delete.' '.$updateCallStatus.'</span>';   
                } 
            }
            //$searchHTML = [];

            //array_unshift($data, $searchHTML);

            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData);
    }   

    public function viewAppoinmant($encID)
    {
        //dd($encID);
        // Default site settings 
        $this->ModuleTitle              = __('admin.TITLE_APPOINTMENT_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_VIEW_BUTTON');
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT'); 
        $this->ViewData['modulePath']   = $this->ModulePath;

        // Appointment
        $id = base64_decode(base64_decode($encID));
        $app_id = $this->AppointmentModel->find($id);
        //dd($app_id);
       
        $this->ViewData['appointment'] = $app_id;
        // All user which have role as doctor
        $this->ViewData['user'] = $this->AdminUserModel
                                       // ->where('status', 1)
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'doctor');
                                        })
                                        ->get(); 

        // All patients  
        $this->ViewData['patient'] = $this->PatientsModel
                                        ->where('status', 1)
                                        ->where('id',$app_id->patient_id)
                                        ->first(); 

        // Get Appointment Type Services
        $patient_id = $app_id->patient_id;
        $appointment_id = $app_id->id;
        $getRecord = $this->AppointmentTypeHasExaminationsModel
                         ->where('appoinment_id',$app_id->appointment_type_id)
                         ->with(['assignedExamination'])
                         ->wherenull('deleted_at')
                         ->get();

        if(!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id))
        {
            $getRecord = $getRecord->map(function($item) use($appointment_id,$patient_id)
            {
                $exam_id = $item->assignedExamination->id;
                $is_checked = $this->AppointmentHasExaminationsModel
                            ->where('appointment_id',$appointment_id)
                            ->where('patient_id',$patient_id)
                            ->where('examination_id',$exam_id)
                            ->first();
                           
               $item->checked = (!empty($is_checked) == 0) ? 0 : 1;
               return $item;
            });
        } 
                                          
        $this->ViewData['services'] = $getRecord;                                

        if(!empty($this->ViewData['patient']))
        {
            //dd($id,$app_id->patient_id);
            $str = $id.'-'.$app_id->patient_id;
            $this->ViewData['qr_code'] = $str;
        }  
        else
        {
            $this->ViewData['qr_code'] = '';
        }                                
        // All appointment types 
        $this->ViewData['appointment_type'] = $this->AppointmentTypesModel->get();
        // view file with data
        return view($this->ModuleView.'appoinmant', $this->ViewData);
    }

    public function viewPatientDetails($encID)
    {
        // Default site settings 
        $this->ModuleTitle              = __('admin.TITLE_PATIENT_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_VIEW_BUTTON');
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT'); 
        $this->ViewData['modulePath']   = $this->ModulePath;

        // Appointment
        $id = base64_decode(base64_decode($encID));
        
        $patient = $this->PatientsModel->find($id);
        $this->ViewData['patient'] = $patient;

      
        // UPDATE PATIENTS STATUS FLAG
        $isUpdate = $this->PatientsModel->find($id);
        $isUpdate->patient_status_flag      = '1';
        $isUpdate->note_report_request      = null;
        $isUpdate->note_report_request_flag = '0';
        $isUpdate->save();
        // view file with data
        return view($this->ModuleView.'patient', $this->ViewData);
    }

    public function dismissalDone(Request $request)
    {
        $str = '';
        // Dismissal
        if(!empty($request->dismissal) && count($request->dismissal)>0)
        {
            foreach ($request->dismissal as $de_key => $de_val) 
            {
                foreach ($de_val as $d_key => $d_val) 
                {
                 
                    $getDismissalPatient = $this->PatientsHasDismissalModel
                                       ->where('fk_patient_id',$request->p_id[0])
                                       ->where('appointment_id',$de_key)
                                       ->where('fk_dismissal_id',$d_val)
                                       ->where('dismissal_flag','0')
                                       ->first();
                    
                    if(!empty($getDismissalPatient))
                    {
                        $getDismissalPatient->dismissal_flag = '1';
                        $getDismissalPatient->save();
                    }
                }
                    
            }
            //Unchecked 
        }
        // Examination
        if(!empty($request->examination) && count($request->examination)>0)
        {
            foreach ($request->examination as $ae_key => $ae_val) 
            {
                foreach ($ae_val as $e_key => $e_val) 
                {
                    $ExaminationPatient = new $this->PatientsHasDismissalModel;
                    $ExaminationPatient->fk_patient_id  = $request->p_id[0];
                    $ExaminationPatient->fk_dismissal_id = $e_val;
                    $ExaminationPatient->appointment_id =  $ae_key;
                    $ExaminationPatient->status         = '1';
                    $ExaminationPatient->dismissal_flag = '1';
                    $ExaminationPatient->type           = 'examinations';
                    $ExaminationPatient->save();

                    $getFinalExamination = $this->AppointmentHasExaminationsModel
                                           ->where('appointment_id',$ae_key)
                                           ->where('examination_id',$e_val)
                                           ->where('dismissal_flag','0')
                                           ->where('patient_id',$request->p_id[0])
                                           ->first();

                    if(!empty($getFinalExamination))
                    {
                        $updateFinalExamination = $this->AppointmentHasExaminationsModel->find($getFinalExamination->id);
                        $updateFinalExamination->dismissal_flag = '1';
                        $updateFinalExamination->save(); 
                    }
                }
            }
        }
        // Dismissal
        $getUncheckDismissalPatient = $this->PatientsHasDismissalModel
                                       ->where('fk_patient_id',$request->p_id[0])
                                       ->where('dismissal_flag','0')
                                       ->get();
                                 //dd($getUncheckDismissalPatient);      
        // 
        foreach ($getUncheckDismissalPatient as $u_key => $u_value) 
        {
            $getPDismissalPatient = $this->PatientsHasDismissalModel->find($u_value['id']);
            if(!empty($getPDismissalPatient))
            {
                $getPDismissalPatient->dismissal_flag = '2';
                $getPDismissalPatient->save();
            }
        }
        // Examination
        $pendingExamination = $this->AppointmentHasExaminationsModel
                              ->where('patient_id',$request->p_id[0])
                              ->where('dismissal_flag','0')
                              ->get();
                                 //dd($getUncheckDismissalPatient);      
        // 
        if(sizeof($pendingExamination)>0)
        {
            foreach ($pendingExamination as $ue_key => $ue_value) 
            {
                $getPexaminationPatient = $this->AppointmentHasExaminationsModel->find($ue_value['id']);
                if(!empty($getPexaminationPatient))
                {
                    $getPexaminationPatient->dismissal_flag = '2';
                    $getPexaminationPatient->save();
                }
            }
        }                      
       

        // ---------------------------------------------
        //$getDismissalHasPatients = $this->getDismissal();
        $getDismissalHasPatients   = $this->getExaminationAndDismissal();

        $getTotalDismissal   = $this->PatientsHasDismissalModel
                               ->where('dismissal_flag','0')
                               ->count();
      
        $str .='<div class="row">
                <div class="col-12">
                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">';  

                        if(!empty($getDismissalHasPatients) && count($getDismissalHasPatients)>0){
                            foreach($getDismissalHasPatients as $key => $val){;
                               if((!empty($val['patient']['appoinmant']) && sizeof($val['patient']['appoinmant'])>0)){;
                              $str .='<form id="frm_'.$val['patient']['p_id'].'" class="dismissal_frm" method="post"> 
                                  <input type="hidden" name="hd_dismissal_cnt" id="hd_dismissal_cnt" value="'.$getTotalDismissal.'">  
                                <div class="row">
                                  <div class="col-sm-3"> 
                                    <div class="form-group">
                                      <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">
                                      <label class="theme-blue" style="font-weight: 500!important;font-size: 18px;">
                                        <p style="font-weight: 600;font-size: 20px;">'.$val['patient']['full_name'].'</p>
                                      </label>
                                    </div>
                                  </div>';
                                   
                                        $str .='<div class="col-sm-9"> 
                                        <div class="p-0 form-group"> 
                                          <button onclick="dismissalDone('.$val['patient']['p_id'].')" type="button" lang="'.$val['patient']['p_id'].'" class="btn btn-primary dismissal_done">'.__('admin.TITLE_DISMISSAL_BUTTON').'</button> 
                                        </div>
                                      </div>
                                </div>
                                <!-- Dismissal -->';
                                if(!empty($val['patient']['appoinmant']['dismissal'])>0 && sizeof($val['patient']['appoinmant']['dismissal'])>0)
                                {

                                    $str .='<div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">';
                                                // $str .='<p style="font-weight: 600;">Appoitment : '.$val['patient']['appointment_date'].'</p>';
                                            $str .='</div>
                                        </div>
                                      </div>
                                      <div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">
                                             
                                                <p style="font-weight: 600;">'.__('admin.TITLE_ASSISTANT_DASHBOARD_DISMISSAL').'
                                                 
                                                </p>
                                              
                                            </div>
                                        </div>';
                                        foreach($val['patient']['appoinmant']['dismissal'] as $ad_key => $ad_val){;
                                        $str .='<div class="col-sm-3">
                                          <div class="form-group">
                                            <div class="form-check"> 
                                              <input type="checkbox" class="form-check-input"
                                                    name="dismissal['.$ad_val['appointment_id'].'][]" value="'.$ad_val['id'].'" 
                                                    >
                                              
                                              <label class="form-check-label" for="new_patient_chkbox">'.$ad_val['name'].'</label>
                                            </div>
                                          </div>
                                        </div>';
                                        };
                                      $str .='</div>';
                                      
                                        }
                                };

                                if(!empty($val['patient']['appoinmant']['reminder'])>0 && sizeof($val['patient']['appoinmant']['reminder'])>0)
                                {
                                    $str .='<div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">';
                                                // $str .='<p style="font-weight: 600;">Appoitment : '.$val['patient']['appointment_date'].'</p>';
                                            $str .='</div>
                                        </div>
                                      </div>
                                      <div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">
                                             
                                                <p style="font-weight: 600;">'.__('admin.TITLE_REMINDER').'
                                                 
                                                </p>
                                              
                                            </div>
                                        </div>';
                                        foreach($val['patient']['appoinmant']['reminder'] as $ad_key => $ad_val){;
                                        $str .='<div class="col-sm-3">
                                          <div class="form-group">
                                            <div class="form-check"> 
                                              <input type="checkbox" class="form-check-input"
                                                    name="dismissal['.$ad_val['appointment_id'].'][]" value="'.$ad_val['id'].'" 
                                                    >
                                              
                                              <label class="form-check-label" for="new_patient_chkbox">'.$ad_val['name'].' ('.$ad_val['control_interval'].')</label>
                                            </div>
                                          </div>
                                        </div>';
                                        };


                                      $str .='</div>';
                                }

                                if(!empty($val['patient']['appoinmant']['examination'])>0 && sizeof($val['patient']['appoinmant']['examination'])>0)
                                {   
                                      $str .='<input type="hidden" name="hd_examinaton_cnt" id="hd_examinaton_cnt" value="'.count($val['patient']['appoinmant']['examination']).'"><div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">
                                              
                                                <p style="font-weight: 600;">'.__('admin.TITLE_EXAMINATIONS_TEXT').'
                                                 
                                                </p>
                                             
                                            </div>
                                        </div>';
                                        foreach($val['patient']['appoinmant']['examination'] as $e_key => $e_val){
                                        $str .='<div class="col-md-3 col-sm-6">
                                          <div class="form-group">
                                            <div class="form-check"> 
                                              <input  type="checkbox" class="form-check-input"
                                                    name="examination['.$e_val['appointment_id'].'][]" value="'.$e_val['id'].'" 
                                                    >
                                              
                                              <label class="form-check-label" for="new_patient_chkbox">'.$e_val['name'].'</label>
                                            </div>
                                          </div>
                                        </div>';
                                        }
                                      $str .='</div>'; 
                                    
                                    }; 
                                $str .='<!-- Examination -->';
                                
                              $str .='</form>
                            <hr>';
                          }
                        }
                        else
                        {;
                              $str .='<div class="row">
                              <div class="col-sm-12"> 
                                <div class="form-group" style="margin-left: 300px;font-size: 20px;">
                                  <label class="theme-blue">
                                    <p>'.__('admin.ERR_DISMISSAL_REQUIRED_NOT_EXIST').'</p>
                                  </label>
                                </div>
                              </div>
                            </div>';
                        };
                        $str .='</div>
                    </div>
                </div>
            </div>';
        // ---------------------------------------------
        return $str.'****'.count($getDismissalHasPatients);
        //return $str;
        
    }

    public function clearTodoListOld(Request $request)
    {
        $isUpdate = $this->PatientsModel->where('id', $request->p_id)->update(['patient_status_flag'=>'1']);
        if(!empty($isUpdate))
        {
            return 'true';
        }
        else
        {
            return 'false';
        }
    }
      public function clearTodoList(Request $request)
    {
        $input = $request->all();
        $user=Auth::user()->id;
        
        if(empty($input['hd_patient_id'])){
            $isUpdate = $this->PatientsModel->where('id', $request->p_id)->update(['patient_status_flag'=>'1','finding_request_admin_flag'=>0]);
            $this->ActivityLogModel->addApiLog('TODOLIST','clear todo list','Create',$request->p_id,$user);
            if(!empty($isUpdate))
            {
                // changes by vijay 13/9/24 #195
                // return 'true';
                $sql = "SELECT `patients`.`id` FROM `patients` 
                WHERE `status` = 1 AND `patient_status_flag` = '0' AND `old_id` != '0'
                AND `update_ganydb` = '1' AND `new_flag`='1'
                AND id In(
                select DISTINCT (patients.id) from `patients` 
                inner join `old_patients` on `patients`.`id` = `old_patients`.`fk_patient_id` 
                    where (patients.road != old_patients.road or patients.size != old_patients.size 
                    or patients.email != old_patients.email or patients.title != old_patients.title 
                    or patients.weight != old_patients.weight or patients.gender != old_patients.gender 
                    or patients.mobile_no != old_patients.mobile_no or patients.birth_date != old_patients.birth_date 
                    or patients.first_name != old_patients.first_name or patients.postal_code != old_patients.postal_code 
                    or patients.family_doctor != old_patients.family_doctor 
                    or patients.insurance_number != old_patients.insurance_number 
                    or patients.additional_insurance != old_patients.additional_insurance))";
                $results = DB::select($sql);
                $updateIds = [];
                foreach ($results as $ptnt) {
                    $updateIds[] = $ptnt->id;
                }
                $patient_cnt = $this->PatientsModel->orWhere(function ($q) {
                    $q->whereNotNull('note_report_request')
                        ->Where('note_report_request_flag', '>', '0');
                })->orWhere(function ($q1) {
                    $q1->Where('update_ganydb', '1')
                        ->Where('new_flag', '1');
                })->orWhere(function ($q2) {
                    $q2->Where('new_flag', '1');
                })
                    ->with(['getOldAppoinmant'])
                    ->orderBy('updated_at', 'DESC')
                    ->get();
                $patient_cnt = $patient_cnt->filter(function ($item) {
                    if ($item->patient_status_flag == '0' && $item->status == '1' && $item->old_id != '0') {
                        return $item;
                    }
                });
                $patient_cnt = $patient_cnt->filter(function ($item) use ($updateIds) {
                    if ($item->new_flag == '1' && $item->update_ganydb != 1) {
                        return $item;
                    } elseif (($item->note_report_request_flag == '1' || $item->note_report_request_flag == '2')) {
                        return $item;
                    } elseif ($item->update_ganydb == 1) {
                        if (in_array($item->id, $updateIds)) {
                            return $item;
                        }
                    } elseif ($item->update_ganydb == 1 && $item->new_flag == '1' && $item->old_id != '0') {
                        return $item;
                    }
                });
                return ['status' => 'true', 'count' => count($patient_cnt)];
                // end
            }
            else
            {
                return 'false';
            }
        }
        else{
            $input = $request->all();
            $hd_patient_id = explode(",", $input['hd_patient_id']);        
            $old_date_id = explode(",", $input['old_date_id']);
            $hd_date = explode(",", $input['hd_date']);
            foreach ($hd_patient_id as $key => $p_id) {  
                $n_old_date_id= $old_date_id[$key];         
                $patient_details = $this->PatientsModel->find($p_id);
                $type = $this->PatientsHasDiagnosticFindingsModel->where('patient_id',$p_id)->first();  
                $p_type = isset($type['finding_type_id']) ? $type['finding_type_id'] : "1";

                $Finding_type    = $this->DiagnosticFindingsTypesModel->find($p_type);
                $old_date        = $this->PatientsHasOldFindingModel->find($n_old_date_id);
                $collection                     = new $this->PatientsHasDiagnosticFindingsModel;
                $collection->old_id             = 0;
                $collection->patient_id         = $p_id;
                $collection->finding_type_id    = $p_type;
                $collection->old_finding_id     = $n_old_date_id;
                $collection->document_name      = $Finding_type['name'].'('.date('Y-m-d', strtotime($old_date['appoinmant_date'])).')';
                $collection->date               = date('Y-m-d', strtotime($old_date['appoinmant_date']));
                $collection->status             = 1;
                $collection->comment            = 'completed';
                if($collection->save())
                {
                        $old_dates = $this->PatientsHasOldFindingModel->find($n_old_date_id);
                        $old_dates->imported_flag = '1';
                        $old_dates->save();
                        // Update Patient Status Flag.... Added by Shyam 23-12-21
                        $allOldFinding = $this->PatientsHasOldFindingModel
                                            ->where('fk_patient_id',$p_id)
                                            ->whereIn('imported_flag', ['0'])->get();
                        $import_finding = $this->SettingsModel->where('setting_key','SEND_FINDING_VIA_EMAIL')->where('status','1')->first();
                        if(sizeof($allOldFinding) == 0 && @$import_finding['status'] != '1') {
                            $patient_details->patient_status_flag = '1';
                            $patient_details->save();
                        }

                        $isUpdate = $this->PatientsModel->where('id', $p_id)->update(['patient_status_flag'=>'1','finding_request_admin_flag'=>0]);
                        $this->ActivityLogModel->addApiLog('TODOLIST','clear todo list','Create',$p_id,$user);
                        if(!empty($isUpdate))
                        {
                            echo 'true';
                        }
                        else
                        {
                            echo  'false';
                        }
                    
                }
               
            }
        } 
    }
    ////Added by Shyam 04-01-22
     public function importFinding(Request $request)
    {
        $input = $request->all();
        $error_arr = $upload_arr = [];
        $this->JsonData['msg_for_todo_list'] = '';
        $patient_details = $this->PatientsModel->find($input['hd_patient_id']);
        $Finding_type    = $this->DiagnosticFindingsTypesModel->find($input['type']);
        $old_date        = $this->PatientsHasOldFindingModel->find($input['old_date_id']);
        $collection                     = new $this->PatientsHasDiagnosticFindingsModel;
        $collection->old_id             = 0;
        $collection->patient_id         = $request->hd_patient_id;
        $collection->finding_type_id    = $request->type;
        $collection->old_finding_id     = $request->old_date_id;
        $collection->document_name      = $Finding_type->name.'('.date('Y-m-d', strtotime($old_date->appoinmant_date)).')';
        $collection->date               = date('Y-m-d', strtotime($old_date->appoinmant_date));
        $collection->status             = 1;
        $collection->comment            = $request->comment;

        if($collection->save())
        {
            if(empty($input['import'])){
                $old_dates = $this->PatientsHasOldFindingModel->find($request->old_date_id);
                $old_dates->imported_flag = '1';
                $old_dates->save();
                // Update Patient Status Flag.... Added by Shyam 23-12-21
                $allOldFinding = $this->PatientsHasOldFindingModel
                                    ->where('fk_patient_id',$input['hd_patient_id'])
                                    ->whereIn('imported_flag', ['0'])->get();
                $import_finding = $this->SettingsModel->where('setting_key','SEND_FINDING_VIA_EMAIL')->where('status','1')->first();
                if(sizeof($allOldFinding) == 0 && @$import_finding['status'] != '1') {
                    $patient_details->patient_status_flag = '1';
                    $patient_details->save();
                }// Added by Shyam 23-12-21
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['patient_id']=  $input['hd_patient_id'];
                $this->JsonData['msg_for_todo_list']= __('admin.MSG_FINDING_IMPORTED_SUCCESSFULLY');
                $this->JsonData['url']    =  route($this->ModulePath);
                $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
                $this->JsonData['error']= 0;
            }
            else{
                foreach ($input['import'] as $key => $value)
                {
                    if($file=$value)
                    {
                        $path = 'diagnostic_findings';
                        $original_file  = strtolower($file->getClientOriginalName());
                        $extension      = strtolower($file->getClientOriginalExtension());
                        $fileName    = date('YmdHis').'-'.$original_file;
                        $filePath  = '/diagnostic_findings/'.date('YmdHis').'-'.$original_file;

                        $fileStorePath = self::putFilePath($path,$file,$fileName); //Store PDF file here

                        // Hyn tenancy code (commented out)
                        // $getDataBaseName = $this->website->where('ordination_id',Config('ordination_id'))->first();
                        // Stancl tenancy code
                        $getDataBaseName = tenancy()->tenant;
                        $txtArray = $imageArray = array();
                        $firstNameCounter = $lastNameCounter = 0;
                        // $storagePath = storage_path().'/app/'; //Commented by Shyam 17-01-22
                        $storagePath = '/opt/app-shared/php/data/storage/app/'; //Added by Shyam 17-01-22
                        $imgFilePath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDataBaseName->uuid.'/diagnostic_findings/';
                        # $imgFilePath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/';
                        $pdfFilePath = $storagePath.$fileStorePath;
                        $pdf = new \App\Libraries\PdfToImage($pdfFilePath);
                        foreach (range(1, $pdf->getNumberOfPages()) as $pageNum)
                        {
                            //log::info("finding pdf1-1");
                            // $imageName = $storagePath.'public/diagnostic_findings/'.date('YmdHis').'_'.$pageNum.'.png';
                            $imageName = $imgFilePath.date('YmdHis').'_'.$pageNum;
                            $pdf->setPage($pageNum)->saveImage($imageName);
                            $imageName = $imageName.'.jpeg';
                            array_push($imageArray, $imageName);

                            $ocr_temp_file = date('YmdHis').$pageNum.'_ocr';
                            // $newOCRfile = $storagePath.'public/diagnostic_findings/'.$ocr_temp_file; //txt file path
                            $newOCRfile = $imgFilePath.$ocr_temp_file; //txt file path
                            // shell_exec('"C:\\Program Files (x86)\\Tesseract-OCR\\tesseract" "'.$imageName.'" '.$newOCRfile.'');
                            //shell_exec('"/usr/bin/tesseract" "'.$imageName.'" '.$newOCRfile.' -l deu');
                            shell_exec('"/usr/bin/tesseract" "'.$imageName.'" '.$newOCRfile.'');
                            //Check patient is Exist or not in OCR txt file
                            $getOCRfile = $newOCRfile.'.txt'; //full path of txt file
                            array_push($txtArray, $getOCRfile);
                            // dump($getOCRfile);
                            //Check First name is exists or not...
                            $matchFirstName = self::findMatchingData($patient_details->first_name,$getOCRfile);
                            //log::info("First Name:".$patient_details->first_name);
                            // dd($matchFirstName);
                            if($matchFirstName == 1)
                            {
                                $firstNameCounter++;
                            }
                            //Check Last name is exists or not...
                            $matchFamilyName = self::findMatchingData($patient_details->family_name,$getOCRfile);
                            //log::info("Family Name:".$patient_details->family_name);
                            if($matchFamilyName == 1)
                            {
                                $lastNameCounter++;
                            }
                        }
                        if($firstNameCounter > 0 && $lastNameCounter > 0)
                        {
                            $findingDocumentObj = new $this->PatientHasDiagnosticFindingsHasDocumentsModel;
                            $findingDocumentObj->finding_id    = $collection->id;
                            $findingDocumentObj->patient_id    = $input['hd_patient_id'];
                            $findingDocumentObj->text          = $request->document_name;
                            $findingDocumentObj->original_name = $fileName;
                            $findingDocumentObj->file          = $filePath;
                            //Save data
                            if ($findingDocumentObj->save())
                            {
                                $upload_arr[] = $original_file;
                                $old_dates = $this->PatientsHasOldFindingModel->find($request->old_date_id);
                                $old_dates->imported_flag = '1';
                                $old_dates->save();
                                // Update Patient Status Flag.... Added by Shyam 23-12-21
                                $allOldFinding = $this->PatientsHasOldFindingModel
                                                    ->where('fk_patient_id',$input['hd_patient_id'])
                                                    ->whereIn('imported_flag', ['0'])->get();
                                $import_finding = $this->SettingsModel->where('setting_key','SEND_FINDING_VIA_EMAIL')->where('status','1')->first();
                                if(sizeof($allOldFinding) == 0 && @$import_finding['status'] != '1') {
                                    $patient_details->patient_status_flag = '1';
                                    $patient_details->save();
                                }// Added by Shyam 23-12-21
                                // unlink all images & txt files after execution
                                foreach($txtArray as $txtFile) {
                                    unlink($txtFile); //Unlink all txt file
                                }
                                foreach($imageArray as $images) {
                                    unlink($images); //Unlink all images file
                                }
                                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                                $this->JsonData['patient_id']=  $input['hd_patient_id'];
                                $this->JsonData['msg_for_todo_list']= __('admin.MSG_FINDING_IMPORTED_SUCCESSFULLY');
                                $this->JsonData['url']    =  route($this->ModulePath);
                                $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
                                $this->JsonData['error']= 0;
                            }
                            else {
                                $error_arr[] = $original_file; 
                                // unlink PDF file, all images & txt files...
                                // if($pdfFilePath) {
                                //     unlink($pdfFilePath); //Unlink PDF file
                                // }
                                foreach($txtArray as $txtFile) {
                                    unlink($txtFile); //Unlink all txt file
                                }
                                foreach($imageArray as $images) {
                                    unlink($images); //Unlink all images file
                                }
                                $this->JsonData['status'] = __('admin.RESP_ERROR');
                                $this->JsonData['status_msg'] = "patient-name-match";
                                $this->JsonData['url'] = $pdfFilePath;
                                $this->JsonData['msg'] = __('admin.MSG_FINDING_IMPORTED_FAILED');
                                $this->JsonData['patient_id']=  $input['hd_patient_id'];
                                $this->JsonData['msg_for_todo_list']= __('admin.MSG_FINDING_IMPORTED_FAILED');
                                $this->JsonData['error_msg'] = __('admin.MSG_FINDING_IMPORTED_ERR_MSG');
                                DB::rollback();
                                //Delete current finding entry =================
                                $this->PatientsHasDiagnosticFindingsModel->where(['id'=>$collection->id,'patient_id'=>$collection->patient_id])->delete();
                                //===============================================
                            }
                        }
                        else {
                            $error_arr[] = $original_file;
                            // unlink PDF file, all images & txt files...
                            // if($pdfFilePath) {
                            //     unlink($pdfFilePath); //Unlink PDF file
                            // }
                            foreach($txtArray as $txtFile) {
                                unlink($txtFile); //Unlink all txt file
                            }
                            foreach($imageArray as $images) {
                                unlink($images); //Unlink all images file
                            }
                            $this->JsonData['status'] = __('admin.RESP_ERROR');
                            $this->JsonData['status_msg'] = "patient-name-match";
                            $this->JsonData['url'] = self::getFilePath($filePath);
                            $this->JsonData['msg'] = __('admin.MSG_FINDING_IMPORTED_FAILED');
                            $this->JsonData['patient_id']=  $input['hd_patient_id'];
                            $this->JsonData['msg_for_todo_list']= __('admin.MSG_FINDING_IMPORTED_FAILED');
                            $this->JsonData['error_msg'] = __('admin.MSG_FINDING_IMPORTED_ERR_MSG');
                            DB::rollback();
                            //Delete current finding entry =================
                            $this->PatientsHasDiagnosticFindingsModel->where(['id'=>$collection->id,'patient_id'=>$collection->patient_id])->delete();
                            //===============================================
                        }
                    }
                }
            }
        }
        if(sizeof($upload_arr)>0)
        {
            $string_version = implode(',', $upload_arr);
            $this->JsonData['msg_for_todo_list'] .= __('admin.MSG_FINDING_IMPORTED_SUCCESS').$string_version;
        }
        if(sizeof($error_arr)>0)
        {
            $string_err = implode(',',$error_arr);
            $this->JsonData['msg_for_todo_list'] .= __('admin.MSG_FINDING_IMPORTED_FAILURE').$string_err;
        }
         // Session::put('redirect_arr',$this->JsonData);
         // return redirect('admin/assistant-dashboard?tab=todoList');
         return response()->json($this->JsonData);
    }

     public function importFindingNew(Request $request)
    {
        $input = $request->all();
        $error_arr = $upload_arr = [];
        $this->JsonData['msg_for_todo_list'] = '';
        $patient_details = $this->PatientsModel->find($input['hd_patient_id']);
        $Finding_type    = $this->DiagnosticFindingsTypesModel->find($input['type']);
        $old_date        = $this->PatientsHasOldFindingModel->find($input['old_date_id']);
        $collection                     = new $this->PatientsHasDiagnosticFindingsModel;
        $collection->old_id             = 0;
        $collection->patient_id         = $request->hd_patient_id;
        $collection->finding_type_id    = $request->type;
        $collection->old_finding_id     = $request->old_date_id;
        $collection->document_name      = $Finding_type->name.'('.date('Y-m-d', strtotime($old_date->appoinmant_date)).')';
        $collection->date               = date('Y-m-d', strtotime($old_date->appoinmant_date));
        $collection->status             = 1;
        $collection->comment            = $request->comment;
        if($collection->save())
        {
            foreach ($input['import'] as $key => $value)
            {
                if($file=$value)
                {
                    $path = 'diagnostic_findings';
                    $original_file  = strtolower($file->getClientOriginalName());
                    $extension      = strtolower($file->getClientOriginalExtension());
                    $fileName    = date('YmdHis').'-'.$original_file;
                    $filePath  = '/diagnostic_findings/'.date('YmdHis').'-'.$original_file;

                    $fileStorePath = self::putFilePath($path,$file,$fileName); //Store PDF file here

                    // Hyn tenancy code (commented out)
                    // $getDataBaseName = $this->website->where('ordination_id',Config('ordination_id'))->first();
                    // Stancl tenancy code
                    $getDataBaseName = tenancy()->tenant;
                    $txtArray = $imageArray = array();
                    $firstNameCounter = $lastNameCounter = 0;
                    // $storagePath = storage_path().'/app/'; //Commented by Shyam 17-01-22
                    $storagePath = '/opt/app-shared/php/data/storage/app/'; //Added by Shyam 17-01-22
                    $imgFilePath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDataBaseName->uuid.'/diagnostic_findings/';
                    # $imgFilePath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/';
                    $pdfFilePath = $storagePath.$fileStorePath;
                    $pdf = new \App\Libraries\PdfToImage($pdfFilePath);
                    foreach (range(1, $pdf->getNumberOfPages()) as $pageNum)
                    {
                        log::info("finding new pdf1-1");
                        // $imageName = $storagePath.'public/diagnostic_findings/'.date('YmdHis').'_'.$pageNum.'.png';
                        $imageName = $imgFilePath.date('YmdHis').'_'.$pageNum;
                        $pdf->setPage($pageNum)->saveImage($imageName);
                        $imageName = $imageName.'.jpeg';
                        array_push($imageArray, $imageName);

                        $ocr_temp_file = date('YmdHis').$pageNum.'_ocr';
                        // $newOCRfile = $storagePath.'public/diagnostic_findings/'.$ocr_temp_file; //txt file path
                        $newOCRfile = $imgFilePath.$ocr_temp_file; //txt file path
                        // shell_exec('"C:\\Program Files (x86)\\Tesseract-OCR\\tesseract" "'.$imageName.'" '.$newOCRfile.'');
                        //shell_exec('"/usr/bin/tesseract" "'.$imageName.'" '.$newOCRfile.' -l deu');
                        shell_exec('"/usr/bin/tesseract" "'.$imageName.'" '.$newOCRfile.'');
                        //Check patient is Exist or not in OCR txt file
                        $getOCRfile = $newOCRfile.'.txt'; //full path of txt file
                        array_push($txtArray, $getOCRfile);
                        // dump($getOCRfile);
                        //Check First name is exists or not...
                        // $matchFirstName = self::findMatchingData($patient_details->first_name,$getOCRfile);
                        // log::info("First Name:".$patient_details->first_name);
                        // // dd($matchFirstName);
                        // if($matchFirstName == 1)
                        // {
                        //     $firstNameCounter++;
                        // }
                        // //Check Last name is exists or not...
                        // $matchFamilyName = self::findMatchingData($patient_details->family_name,$getOCRfile);
                        // log::info("Family Name:".$patient_details->family_name);
                        // if($matchFamilyName == 1)
                        // {
                        //     $lastNameCounter++;
                        // }
                    }
                    //if($firstNameCounter > 0 && $lastNameCounter > 0)
                    //{
                        $findingDocumentObj = new $this->PatientHasDiagnosticFindingsHasDocumentsModel;
                        $findingDocumentObj->finding_id    = $collection->id;
                        $findingDocumentObj->patient_id    = $input['hd_patient_id'];
                        $findingDocumentObj->text          = $request->document_name;
                        $findingDocumentObj->original_name = $fileName;
                        $findingDocumentObj->file          = $filePath;
                        //Save data
                        if ($findingDocumentObj->save())
                        {
                            $upload_arr[] = $original_file;
                            $old_dates = $this->PatientsHasOldFindingModel->find($request->old_date_id);
                            $old_dates->imported_flag = '1';
                            $old_dates->save();
                            // Update Patient Status Flag.... Added by Shyam 23-12-21
                            $allOldFinding = $this->PatientsHasOldFindingModel
                                                ->where('fk_patient_id',$input['hd_patient_id'])
                                                ->whereIn('imported_flag', ['0'])->get();
                            $import_finding = $this->SettingsModel->where('setting_key','SEND_FINDING_VIA_EMAIL')->where('status','1')->first();
                            if(sizeof($allOldFinding) == 0 && @$import_finding['status'] != '1') {
                                $patient_details->patient_status_flag = '1';
                                $patient_details->save();
                            }// Added by Shyam 23-12-21
                            // unlink all images & txt files after execution
                            foreach($txtArray as $txtFile) {
                                unlink($txtFile); //Unlink all txt file
                            }
                            foreach($imageArray as $images) {
                                unlink($images); //Unlink all images file
                            }
                            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                            $this->JsonData['patient_id']=  $input['hd_patient_id'];
                            $this->JsonData['msg_for_todo_list']= __('admin.MSG_FINDING_IMPORTED_SUCCESSFULLY');
                            $this->JsonData['url']    =  route($this->ModulePath);
                            $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
                            $this->JsonData['error']= 0;
                        }
                        else {
                            $error_arr[] = $original_file; 
                            // unlink PDF file, all images & txt files...
                            if($pdfFilePath) {
                                unlink($pdfFilePath); //Unlink PDF file
                            }
                            foreach($txtArray as $txtFile) {
                                unlink($txtFile); //Unlink all txt file
                            }
                            foreach($imageArray as $images) {
                                unlink($images); //Unlink all images file
                            }
                            $this->JsonData['status'] = __('admin.RESP_ERROR');
                            $this->JsonData['msg'] = __('admin.MSG_FINDING_IMPORTED_FAILED');
                            $this->JsonData['patient_id']=  $input['hd_patient_id'];
                            $this->JsonData['msg_for_todo_list']= __('admin.MSG_FINDING_IMPORTED_FAILED');
                            $this->JsonData['error_msg'] = __('admin.MSG_FINDING_IMPORTED_ERR_MSG');
                            DB::rollback();
                        }
                    // }
                    // else {
                    //     $error_arr[] = $original_file;
                    //     // unlink PDF file, all images & txt files...
                    //     if($pdfFilePath) {
                    //         unlink($pdfFilePath); //Unlink PDF file
                    //     }
                    //     foreach($txtArray as $txtFile) {
                    //         unlink($txtFile); //Unlink all txt file
                    //     }
                    //     foreach($imageArray as $images) {
                    //         unlink($images); //Unlink all images file
                    //     }
                    //     $this->JsonData['status'] = __('admin.RESP_ERROR');
                    //     $this->JsonData['msg'] = __('admin.MSG_FINDING_IMPORTED_FAILED');
                    //     $this->JsonData['patient_id']=  $input['hd_patient_id'];
                    //     $this->JsonData['msg_for_todo_list']= __('admin.MSG_FINDING_IMPORTED_FAILED');
                    //     $this->JsonData['error_msg'] = __('admin.MSG_FINDING_IMPORTED_ERR_MSG');
                    //     DB::rollback();
                    // }
                }
            }
        }
        if(sizeof($upload_arr)>0)
        {
            $string_version = implode(',', $upload_arr);
            $this->JsonData['msg_for_todo_list'] .= __('admin.MSG_FINDING_IMPORTED_SUCCESS').$string_version;
        }
        if(sizeof($error_arr)>0)
        {
            $string_err = implode(',',$error_arr);
            $this->JsonData['msg_for_todo_list'] .= __('admin.MSG_FINDING_IMPORTED_FAILURE').$string_err;
        }
        return response()->json($this->JsonData);
    }
    /*** // Commented by Shyam 04-01-22
    public function importFinding(Request $request)
    {
        $input = $request->all();
        //dd($request->all());
        $error_arr = $upload_arr = [];
        $this->JsonData['msg_for_todo_list'] = '';
        $patient_details = $this->PatientsModel->find($input['hd_patient_id']);
        $Finding_type    = $this->DiagnosticFindingsTypesModel->find($input['type']);
        $old_date        = $this->PatientsHasOldFindingModel->find($input['old_date_id']);
        $date                           = strtotime($request->date);
        $collection                     = new $this->PatientsHasDiagnosticFindingsModel;
        $collection->old_id             = 0;
        $collection->patient_id         = $request->hd_patient_id;
        $collection->finding_type_id    = $request->type;
        $collection->old_finding_id     = $request->old_date_id;
        $collection->document_name      = $Finding_type->name.'('.date('Y-m-d', strtotime($old_date->appoinmant_date)).')';
        $collection->date               = date('Y-m-d', strtotime($old_date->appoinmant_date));
        $collection->status             = 1;
        if($collection->save())
        {
            foreach ($input['import'] as $key => $value) 
            {
                //dd($value);
                if($file=$value)
                {
                    $findingDocumentObj     = new $this->PatientHasDiagnosticFindingsHasDocumentsModel;
                    $path = 'diagnostic_findings';
                    $original_file  = strtolower($file->getClientOriginalName());
                    $extension      = strtolower($file->getClientOriginalExtension()); 
                    $fileName    = date('YmdHis').'-'.$original_file;
                    $filePath  = '/diagnostic_findings/'.date('YmdHis').'-'.$original_file;
                    //dd($filePath);
                    //Storage::putFileAs($path, $file, $fileName              
                    // $fileStorePath = Storage::putFileAs($path, $file, $fileName);
                    // dd($fileStorePath);
                    $fileStorePath = self::putFilePath($path,$file,$fileName);
                    //dd($fileStorePath);
                    // $ocr_temp_file = date('YmdHis').'_ocr';
                    // $getOCRfile = storage_path().'/app/public/diagnostic_findings/'.$fileName;
                    // $newOCRfile = storage_path().'/app/public/diagnostic_findings/'.$ocr_temp_file;
                    // // dd("--",$newOCRfile);
                    // shell_exec('"C:\\Program Files (x86)\\Tesseract-OCR\\tesseract" "'.$getOCRfile.'" '.$newOCRfile.'');
                    // // check patient is Existor not in OCR txt file
                    // $txtfile = $newOCRfile.'.txt';
                    // $newOCRFile = $ocr_temp_file.'txt';
                    // //dump($txtfile);
                    // $getOCRfile = $txtfile;
                    // $matchFirstName = self::findMatchingData($patient_details->family_name,$getOCRfile);
                    // if($matchFirstName == 1)
                    // {
                    //     $MatchFamilyName = self::findMatchingData($patient_details->first_name,$getOCRfile);
                    //     if($MatchFamilyName == 1)
                    //     {
                    //         $exam_flag = 1;
                    //         dd("ppppfbdkfbsdkf");
                    //         if($exam_flag == 1)
                    //         {
                    //             $findingDocumentObj->finding_id    = $collection->id;
                    //             $findingDocumentObj->patient_id    = $input['hd_patient_id'];
                    //             $findingDocumentObj->text          = $request->document_name;
                    //             $findingDocumentObj->original_name = $fileName;    
                    //             $findingDocumentObj->file          = $filePath;
                    //             //Save data
                    //             if ($findingDocumentObj->save()) 
                    //             { 
                    //                 $upload_arr[] = $original_name;
                    //                 $old_dates = $this->PatientsHasOldFindingModel->find($request->old_date_id);
                    //                 $old_dates->imported_flag = '1';
                    //                 $old_dates->save();
                    //                 $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    //                 $this->JsonData['patient_id']=  $input['hd_patient_id'];
                    //                 //$this->JsonData['msg_for_todo_list']= __('admin.MSG_FINDING_IMPORTED_SUCCESSFULLY');
                    //                 $this->JsonData['url']    =  route($this->ModulePath);
                    //                 $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE'); 
                    //                 $this->JsonData['error']= 0;
                    //             }
                    //             else
                    //             {
                    //                 $error_arr[] = $original_file; 
                    //                 $file = self::unlinkFilePath('diagnostic_findings/'.$newOCRFile);
                    //                 if(is_file($file))
                    //                 {
                    //                     unlink($file);
                    //                     //unlink(storage_path().$request->old_logo);
                    //                 } 
                    //                 // $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                    //                 // $this->JsonData['patient_id']=  $input['hd_patient_id'];
                    //                 // $this->JsonData['msg_for_todo_list']= __('admin.ERR_SOMETHING_WRONG');
                    //                 // $this->JsonData['error']= 1;
                    //                 // //$this->JsonData['error_msg'] = $e->getMessage();
                    //                 // DB::rollback();
                    //             }    
                    //         }
                    //         else
                    //         {
                    //             $error_arr[] = $original_file; 
                    //             $file = self::unlinkFilePath('diagnostic_findings/'.$newOCRFile);
                    //             if(is_file($file))
                    //             {
                    //                 unlink($file);
                    //                 //unlink(storage_path().$request->old_logo);
                    //             } 
                    //             // $this->JsonData['msg']              = __('admin.ERR_SOMETHING_WRONG');
                    //             // $this->JsonData['patient_id']       =  $input['hd_patient_id'];
                    //             // // $this->JsonData['msg_for_todo_list']= __('admin.ERR_SOMETHING_WRONG');
                    //             // $this->JsonData['err_msg_for_todo_list']= __('admin.TODO_LIST_IMPORT_OCR'); 
                    //             // $this->JsonData['error']= 1;
                    //             // //$this->JsonData['error_msg']        = $e->getMessage();
                    //             // DB::rollback();
                    //         }
                    //     }
                    //     else
                    //     {
                    //         $error_arr[] = $original_file; 
                    //         $file = self::unlinkFilePath('diagnostic_findings/'.$newOCRFile);
                    //         if(is_file($file))
                    //         {
                    //             unlink($file);
                    //             //unlink(storage_path().$request->old_logo);
                    //         } 
                    //         // $this->JsonData['msg']              = __('admin.ERR_SOMETHING_WRONG');
                    //         // $this->JsonData['patient_id']       =  $input['hd_patient_id'];
                    //         // // $this->JsonData['msg_for_todo_list']= __('admin.ERR_SOMETHING_WRONG');
                    //         // $this->JsonData['msg_for_todo_list']= __('admin.TODO_LIST_IMPORT_OCR'); 
                    //         // $this->JsonData['error']= 1;
                    //         // // $this->JsonData['error_msg']        = $e->getMessage();
                    //         // DB::rollback();
                    //     }
                    // }
                    // else
                    // {
                    //     $error_arr[] = $original_file; 
                    //     $file = self::unlinkFilePath('diagnostic_findings/'.$newOCRFile);
                    //     if(is_file($file))
                    //     {
                    //         unlink($file);
                    //         //unlink(storage_path().$request->old_logo);
                    //     } 
                    //     // $this->JsonData['msg']              = __('admin.ERR_SOMETHING_WRONG');
                    //     // $this->JsonData['patient_id']       =  $input['hd_patient_id'];
                    //     // // $this->JsonData['msg_for_todo_list']= __('admin.ERR_SOMETHING_WRONG');
                    //     // $this->JsonData['msg_for_todo_list']= __('admin.TODO_LIST_IMPORT_OCR'); 
                    //     // $this->JsonData['error']= 1;
                    //     // // $this->JsonData['error_msg']        = $e->getMessage();
                    //     // DB::rollback();
                    // }
                    //$fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $fileName);
                    $findingDocumentObj->finding_id    = $collection->id;
                    $findingDocumentObj->patient_id    = $input['hd_patient_id'];
                    $findingDocumentObj->text          = $request->document_name;
                    $findingDocumentObj->original_name = $fileName;    
                    $findingDocumentObj->file          = $filePath;
                    //Save data
                    if ($findingDocumentObj->save()) 
                    {
                        $old_dates = $this->PatientsHasOldFindingModel->find($request->old_date_id);
                        $old_dates->imported_flag = '1';
                        $old_dates->save();
                        // Update Patient Status Flag.... Added by Shyam 23-12-21
                        $allOldFinding = $this->PatientsHasOldFindingModel
                                            ->where('fk_patient_id',$input['hd_patient_id'])
                                            ->whereIn('imported_flag', ['0'])->get();
                        if(sizeof($allOldFinding) == 0) {
                            $patient_details->patient_status_flag = '1';
                            $patient_details->save();
                        }// Added by Shyam 23-12-21
                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['patient_id']=  $input['hd_patient_id'];
                        //$this->JsonData['msg_for_todo_list']= __('admin.MSG_FINDING_IMPORTED_SUCCESSFULLY');
                        $this->JsonData['url']    =  route($this->ModulePath);
                        $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE'); 
                    }
                    else
                    {
                        $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['patient_id']=  $input['hd_patient_id'];
                        $this->JsonData['msg_for_todo_list']= __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $e->getMessage();
                        DB::rollback();
                    }
                }
            }
        }
        // if(sizeof($upload_arr)>0)
        // {
        //     $string_version = implode(',', $upload_arr);
        //     $this->JsonData['msg_for_todo_list'] .= 'Below are the files which are matched with the patient data : '.$string_version;
        // }
        // if(sizeof($error_arr)>0)
        // {
        //     $string_err = implode(',', $error_arr);
        //     $this->JsonData['msg_for_todo_list'] .= 'Below are the files which are not matched with the patient data : '.$string_version;
        // }
        Session::put('redirect_arr',$this->JsonData);
        //return response()->json($this->JsonData);
        //return redirect('admin/assistant-dashboard');
        return redirect('admin/assistant-dashboard?tab=todoList');
    } **/

    public function findMatchingData($name,$file)
    {
        $searchfor = ucfirst($name);
        $searchlow = lcfirst($name);
        $replace = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
        $searchfor = strtolower(str_replace(array_keys($replace), $replace,$name));
        $searchlow = strtolower(str_replace(array_keys($replace), $replace,$name));
        header('Content-Type: text/plain');
        $contents = strtolower(file_get_contents($file));
        $pattern = preg_quote($searchfor, '/');
        $pattern = "/^.*$pattern.*\$/m";
        //log::info("Match".$pattern.">>>".$contents."==1".$searchfor);
        if(preg_match_all($pattern, $contents, $matches))
        {
            return 1;
        }
        else {
            $pattern = preg_quote($searchlow, '/');
            $pattern = "/^.*$pattern.*\$/m";
            //log::info("Match-else".$pattern.">>>".$contents."==1".$searchlow);
            if(preg_match_all($pattern, $contents, $matches))
            {
                return 1;
            }
            else {
                $replace2 = array('Ö'=>'OE','ö'=>'oe','Ü'=>'UE','ü'=>'ue','Ä'=>'AE','ä'=>'ae','ß'=>'ss' );
                $searchfor2 = strtolower(str_replace(array_keys($replace2), $replace2,$name));
                $searchlow2= strtolower(str_replace(array_keys($replace2), $replace2,$name));
                $pattern = preg_quote($searchfor2, '/');
                $pattern = "/^.*$pattern.*\$/m";
                if(preg_match_all($pattern, $contents, $matches)){
                    return 1;
                }
                else{
                    return 0;
                } 
            }
        }
    }
 
    //Added by Shyam 16-12-21...
    public function todoList()
    {
        // TO DO List
        $limit = (intval($_GET['limit']) != 0 ) ? $_GET['limit'] : 200;
        $offset = (intval($_GET['offset']) != 0 ) ? $_GET['offset'] : 0;
        $new_flag  = '0';
        $finging_flag = '';
        $finding_type = $this->DiagnosticFindingsTypesModel
                                        ->where('status',1)
                                        ->get();
        $sql = "SELECT `patients`.`id` FROM `patients` 
                WHERE `status` = 1 AND `patient_status_flag` = '0' AND `old_id` != '0'
                AND `update_ganydb` = '1' AND `new_flag`='1'
                AND id In(
                select DISTINCT (patients.id) from `patients` 
                inner join `old_patients` on `patients`.`id` = `old_patients`.`fk_patient_id` 
                    where (patients.road != old_patients.road or patients.size != old_patients.size 
                    or patients.email != old_patients.email or patients.title != old_patients.title 
                    or patients.weight != old_patients.weight or patients.gender != old_patients.gender 
                    or patients.mobile_no != old_patients.mobile_no or patients.birth_date != old_patients.birth_date 
                    or patients.first_name != old_patients.first_name or patients.postal_code != old_patients.postal_code 
                    or patients.family_doctor != old_patients.family_doctor 
                    or patients.insurance_number != old_patients.insurance_number
                    or patients.street_no != old_patients.street_no
                    or patients.place != old_patients.place 
                    or patients.additional_insurance != old_patients.additional_insurance))";
        $results = DB::select($sql);
        $updateIds = [];
        foreach ($results as $ptnt)
        {
            $updateIds[] = $ptnt->id;
        }
        Log::info($updateIds);
        $patient = $this->PatientsModel
                    ->orWhere(function($q) 
                    {
                        $q->whereNotNull('note_report_request')
                            ->Where('note_report_request_flag','>', '0');
                    })->orWhere(function($q1) {
                        $q1->Where('update_ganydb','1')
                            ->Where('new_flag','1');
                    })->orWhere(function($q2) {
                        $q2->Where('new_flag','1');
                    })
                    ->with(['getOldAppoinmant','getAppointment'])
                    ->orderBy('updated_at','DESC')
                    ->get();
        $patient = $patient->filter(function($item)
        {
            if($item->patient_status_flag == '0' && $item->status == '1' && $item->old_id != '0')
            {
                return $item;
            }
        });


        $patient = $patient->filter(function($item) use($updateIds)
        {
            if($item->new_flag == '1' && $item->update_ganydb!=1)
            {
                return $item;
            }
            elseif(($item->note_report_request_flag == '1' || $item->note_report_request_flag == '2'))
            {
                return $item;
            }
            elseif($item->update_ganydb == 1)
            {
                if(in_array($item->id, $updateIds))
                {
                    return $item;
                }
            }
            elseif ($item->update_ganydb == 1 && $item->new_flag =='1' && $item->old_id != '0')
            {
                return $item;
            }
        });
        $patient = $patient->map(function($item)
        {
            $item->flag = '0';
            $old_app = $item->getOldAppoinmant;
            if(!empty($old_app) && count($old_app) > 0)
            {
                $old_app = $old_app->map(function($sub_item) use($item)
                {
                    if($sub_item->imported_flag == '1')
                    {
                        $item->flag = '1';
                    }
                    return $sub_item;
                });
            }
            return $item;
        });
        $str = '';
        $setting_flag = '';

         //commented below line on 7-feb-23 for entries completed issue 
        //$old_date_id = $hd_patient_id = $hd_date = []; 

        if(!empty($patient) && sizeof($patient)>0)
        {
            $import_finding_setting = $this->SettingsModel->where('setting_key','IMPORT_SETTING')->first();
            $import_finding_via_email_setting = $this->SettingsModel->where('setting_key','SEND_FINDING_VIA_EMAIL')->where('status','1')->first();
            // if(@$import_finding_via_email_setting['setting_value'] == 'off')
            if(@$import_finding_via_email_setting['status'] == '1')
            {
                $setting_flag = '1';//off
            }
            else {
                $setting_flag = '0';//on
            }
            foreach ($patient as $key => $patient)
            {
                $endcode_id = base64_encode(base64_encode($patient['id']));
                if($patient['flag'] == '1')
                {
                    // $cls = 'btn btn-success';//Commented by Shyam 23-03-22
                    $cls = 'btn btn-warning';//Added by Shyam 23-03-22
                }
                else {
                    $cls = 'btn btn-warning'; //updated_at
                }
                if($patient['old_id'] != '0')
                {
                    $tempFlag = $upFlag = 0;
                    if(sizeof($updateIds)>0)
                    {
                        $upFlag = 1;
                    }
                    ////Added by Shyam 16-12-21
                    if(!empty($patient->getAppointment) && sizeof($patient->getAppointment))
                    {
                        $tempFlag = 1;
                    }




                    ////Added by Shyam 16-12-21
                    $checkAppoint = $this->AppointmentModel->where('patient_id',$patient['id'])
                                        ->whereNull('deleted_at')->get(['id']);
                    $checkAppointIds  = array_unique(array_column(array_values($checkAppoint->toArray()),'id'));
                    $checkFindingCount = $this->PatientsHasOldFindingModel
                                              ->where('fk_patient_id',$patient['id'])
                                              ->whereIn('appointment_id',$checkAppointIds)
                                              // commented 7-jul-22 by swati
                                              //->whereIn('imported_flag',['0', '1'])
                                              ->whereIn('imported_flag',['0'])
                                              ->count();
                    if($tempFlag == 1)
                    {
                        $str .= '<p data-f="'.$tempFlag.'" class="clgtoggle rrr" id="main_'.$patient['id'].'"><input type="hidden" name="ssss" value="'.$patient['update_ganydb'].'==>'.$patient['old_id'].'===>'.$patient['old_id'].'"/>';
                        if($patient['update_ganydb']==1 && $patient['new_flag']!='1' && $patient['old_id']!='0')
                        {
                            $str .='<a lang="'.$patient['id'].'" class="labelCls" href="javascript:void(0)" onclick="removeClass(`'.$endcode_id.'`,`'.$patient['id'].'`)" target="_blank"  style="color: black;" >'.ucfirst($patient['first_name']).'&nbsp;<span id="input_'.$patient['id'].'">'.$patient['family_name'].'</span>';
                            if(!empty($patient['birth_date']))
                            {
                                $str .='('.Date('d.m.Y',strtotime($patient['birth_date'])).') ';
                            }
                            $str .='</a><span><a  class="btn btn-primary highlight copyPatient" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                            </a><input type="hidden" name="copypatientname" value="'.$patient['family_name']." ".$patient['first_name'].'">';
                        }
                        else {
                            $str .='<a lang="'.$patient['id'].'" class="labelCls" href="javascript:void(0)" onclick="hideDiv('.$patient['id'].')" style="color: black;" >'.ucfirst($patient['first_name']).'&nbsp;<span id="input_'.$patient['id'].'">'.$patient['family_name'].'</span>';
                            if(!empty($patient['birth_date']))
                            {
                                $str .='('.Date('d.m.Y',strtotime($patient['birth_date'])).') ';
                            }
                            $str .='</a><span><a  class="btn btn-primary highlight copyPatient" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                            </a><input type="hidden" name="copypatientname" value="'.$patient['family_name']." ".$patient['first_name'].'">';
                        }
                        // if ($patient['note_report_request_flag'] == '2') //Finding
                        if ($checkFindingCount > 0 || $patient['finding_request_admin_flag']==1) //Finding
                        {
                            $new_flag = '1';
                            $str .= '<a id="btn_finding_'.$patient['id'].'" lang="'.$patient['id'].'"  onclick="getPatientsDiv('.$patient['old_id'].','.$patient['id'].',2)" class="'.$cls.' findingCls btn_next_cls" data-toggle="collapse" href="#collapseFinding_'.$patient['id'].'" role="button" aria-expanded="false" aria-controls="collapseFinding_'.$patient['id'].'">'.__('admin.TITLE_FINDING').'</a>';
                        }
                        else {
                            $appointmentPendingCount = $this->AppointmentModel->where('patient_id',$patient['id'])
                                                        ->where('appointment_status','!=','Fertig')->count();
                            $appointmentDoneCount = $this->AppointmentModel->where('patient_id',$patient['id'])
                                                        ->where('appointment_status','Fertig')->count();
                            if($appointmentPendingCount == 1 && $appointmentDoneCount < 1) //New
                            Log::info('new patient');

                            {
                                $new_flag = '1';
                                $str .= '<a id="btn_new_'.$patient['id'].'" lang="'.$patient['id'].'" onclick="getPatientsDiv('.$patient['old_id'].','.$patient['id'].',1)" class="btn btn-primary newCls btn_next_cls" lang="'.$patient['id'].'" data-toggle="collapse" href="#collapseNew_'.$patient['id'].'" role="button" aria-expanded="false" aria-controls="collapseNew_'.$patient['id'].'" >'.__('admin.TITLE_NEW').'</a>';
                            }
                            if($appointmentDoneCount > 0 || $appointmentPendingCount > 1) //Update
                            {
                                $str .='<a id="btn_update_'.$patient['id'].'" lang="'.$patient['id'].'"   class="btn btn-primary highlight btn_next_cls swati" onClick="getPatientsDiv('.$patient['old_id'].','.$patient['id'].',3)" href="#collapseUpdate_'.$patient['id'].'" role="button" data-toggle="collapse" aria-expanded="false" aria-controls="collapseExample" >'.__('admin.TITLE_CHANGE_PASSWORD_BUTTON').'</a>';
                            }
                        }
                        $str .= '
                            </span>
                        </p>';
                    }
                    //for Updated records...
                    if($upFlag == 1 && $tempFlag != 1)
                    {
                        $str .= '<p data-f="'.$upFlag.'" class="clgtoggle rrr" id="main_'.$patient['id'].'"><input type="hidden" name="ssss" value="'.$patient['update_ganydb'].'==>'.$patient['old_id'].'===>'.$patient['old_id'].'"/>';
                        if($patient['update_ganydb'] == 1 && $patient['new_flag']!='1' && $patient['old_id'] != '0')
                        {
                            $str .='<a  lang="'.$patient['id'].'" class="labelCls" href="javascript:void(0)" onclick="removeClass(`'.$endcode_id.'`,`'.$patient['id'].'`)" target="_blank"  style="color: black;" >'.ucfirst($patient['first_name']).'&nbsp;<span id="input_'.$patient['id'].'">'.$patient['family_name'].'</span>';
                            if(!empty($patient['birth_date']))
                            {
                                $str .='('.Date('d.m.Y',strtotime($patient['birth_date'])).') ';
                            }
                            $str .='</a><span><a  class="btn btn-primary highlight copyPatient" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                            </a><input type="hidden" name="copypatientname" value="'.$patient['family_name']." ".$patient['first_name'].'">';
                        }
                        else {
                            $str .='<a  lang="'.$patient['id'].'" class="labelCls" href="javascript:void(0)" onclick="hideDiv('.$patient['id'].')" style="color: black;" >'.ucfirst($patient['first_name']).'&nbsp;<span id="input_'.$patient['id'].'">'.$patient['family_name'].'</span>';
                            if(!empty($patient['birth_date']))
                            {
                                $str .='('.Date('d.m.Y',strtotime($patient['birth_date'])).') ';
                            }
                            $str .='</a><span><a  class="btn btn-primary highlight copyPatient" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                            </a><input type="hidden" name="copypatientname" value="'.$patient['family_name']." ".$patient['first_name'].'">';
                        }
                        // if ($patient['note_report_request_flag'] == '2') //Finding
                        if ($checkFindingCount > 0) //Finding
                        {
                            $new_flag = '1';
                            $str .= '<a id="btn_finding_'.$patient['id'].'" lang="'.$patient['id'].'"  onclick="getPatientsDiv('.$patient['old_id'].','.$patient['id'].',2)" class="'.$cls.' findingCls btn_next_cls" data-toggle="collapse" href="#collapseFinding_'.$patient['id'].'" role="button" aria-expanded="false" aria-controls="collapseFinding_'.$patient['id'].'">'.__('admin.TITLE_FINDING').'</a>';
                        }
                        else { //Update
                            $str .='<a id="btn_update_'.$patient['id'].'" lang="'.$patient['id'].'"   class="btn btn-primary highlight btn_next_cls" onClick="getPatientsDiv('.$patient['old_id'].','.$patient['id'].',3)" href="#collapseUpdate_'.$patient['id'].'" role="button" data-toggle="collapse" aria-expanded="false" aria-controls="collapseExample" >'.__('admin.TITLE_CHANGE_PASSWORD_BUTTON').'</a>';
                        }
                        $str .= '
                            </span>
                        </p>';
                    }////Added by Shyam 16-12-21

                    $icon='';
                    if(isset($patient['note_report_request_from']) && ($patient['note_report_request_from']=="admin"))
                    {
                        $icon = '<strong><i class="nav-icon fas fa-cog" style="font-size:25px"></i></strong>';
                    }else if(isset($patient['note_report_request_from']) && ($patient['note_report_request_from']=="app"))
                    {
                         $icon = '<img src="'.url('/').'/assets/admin/images/cell-phone.png" width="30px" height="30px"/>';
                    }

                    $str .='<div class="collapse findClass" id="collapseFinding_'.$patient['id'].'">
                    <div class="card card-body ">
                        <div class="table-responsive">
                            <table class="old-appoinmant table table-bordered tableBorder " style="width:60%;" >
                                <tr class="tableBorder"> 
                                    <td class="tableBorder" style="width: 25%">
                                        <strong>Notiz Patient
                                    </td>
                                    <td collapse=2 class="tableBorder" style="width: 45%">
                                        <textarea
                                        readonly
                                        type="text" 
                                        name="notes" 
                                        id="notes"
                                        class="form-control" 
                                        >'.$patient['note_report_request'].'</textarea>
                                    </td>
                                    <td class="tableBorder" style="width: 40%">
                                     '.$icon.'
                                     </td>
                                </tr>';
                                // dump($patient->getOldAppoinmant);

                                $old_date_id = $hd_patient_id = $hd_date = []; // added on 7-feb-23

                                //Log::info("patient id in todo list function ======>");
                               // Log::info($patient['id']);

                               //  Log::info("getOldAppoinmant ======>");
                               // Log::info($patient->getOldAppoinmant);

                                if(!empty($patient->getOldAppoinmant) && sizeof($patient->getOldAppoinmant))  
                                {
                                    $cnt = 1;
                                    $flag = '1';
                                    foreach($patient->getOldAppoinmant as $d_key => $d_val)
                                    {
                                         //  Log::info(" in getOldAppoinmant ======>");
                                         //  Log::info(" setting_flag ======>");
                                         //   Log::info($setting_flag);


                                        $cls = '';
                                        if($d_val['imported_flag'] == '1')
                                        {
                                            $cls = 'changeCol';
                                        }
                                        else {
                                            $flag = '0';
                                        };

                                        // Log::info(" cls ======>");
                                        // Log::info($cls);

                                       //  Log::info(" flag ======>");
                                       //  Log::info($flag);


                                        if($setting_flag == '1' && !empty($cls))
                                        {
                                             // Log::info(" in if condition ======>");
                                              // Log::info($patient['id']);

                                            $str .= '<tr class="tableBorder"> 
                                                    <td class="tableBorder" style="width: 25%">
                                                      <strong>Befunddatum</strong>
                                                    </td>
                                                    <td class="tableBorder" style="width: 30%">
                                                      <input readonly class="form-control" type="text" name="date[]" id="date_'.$d_val['id'].'" value="'.Date('d.m.Y',strtotime($d_val['appoinmant_date'])).'"></td>
                                                    <td class="tableBorder" style="width: 40%">';
                                            $str .= '<a onclick="showSendFinding(this,'.$setting_flag.','.$patient['id'].','.$d_val['id'].',1)" title="'.__('admin.TITLE_TODO_LIST_IMPORT_FINDING["setting_value"]').'" type="button" class="btn btn-success" >
                                                      '.__('admin.TITLE_TODO_LIST_SEND').'
                                                      </a>';
                                            $str .= '</td>
                                            </tr>';
                                        }
                                        elseif($cls == '')
                                        {

                                             // Log::info(" in else if condition ======>");
                                             //  Log::info($patient['id']);

                                            $str .= '<tr class="tableBorder"> 
                                                    <td class="tableBorder" style="width: 25%">
                                                      <strong>Befunddatum</strong>
                                                    </td>
                                                    <td class="tableBorder" style="width: 30%">
                                                      <input readonly class="form-control" type="text" name="date[]" id="date_'.$d_val['id'].'" value="'.Date('d.m.Y',strtotime($d_val['appoinmant_date'])).'"></td>
                                                    <td class="tableBorder" style="width: 40%">';
                                                if($import_finding_setting['setting_value'] == 'on')
                                                {
                                                    array_push($old_date_id,$d_val['id']);
                                                    array_push($hd_patient_id,$patient['id']);
                                                    array_push($hd_date,date('Y/m/d',strtotime($d_val['appoinmant_date'])));
                                                    $str .= '<a style="color:#fff;" onclick="showImportaFinding(this,'.$d_val['id'].','.$patient['id'].','.date('Y/m/d',strtotime($d_val['appoinmant_date'])).')" title="'.__('admin.TITLE_TODO_LIST_IMPORT_FINDING').'" type="button" class="btn btn-primary" >
                                                             '.__('admin.TITLE_TODO_LIST_IMPORT_FINDING').'
                                                             </a>';
                                                }
                                            $str .= '</td>
                                            </tr>';
                                        }
                                        // if($cnt == sizeof($patient->getOldAppoinmant)) {
                                        //     $str .= '<tr>
                                        //       <td class="tableBorder" style="width: 25%">
                                        //           <strong>'.__('admin.TITLE_APPOINTMENT_NOTE').'</strong>
                                        //         </td>
                                        //         <td class="tableBorder" style="width: 30%">
                                        //           <textarea
                                        //           type="text" 
                                        //           name="notes" 
                                        //           id="notes"
                                        //           class="form-control" 
                                        //           ></textarea>
                                        //         <td class="tableBorder" style="width: 40%">';
                                        //           if($flag == '1' && $setting_flag == '1'){;
                                        //             $str .= '<a onclick="showSendFinding(this,'.$setting_flag.','.$patient['id'].',null,2)" title="'.__('admin.TITLE_TODO_LIST_IMPORT_FINDING').'" type="button" class="btn btn-success" >
                                        //             '.__('admin.TITLE_TODO_LIST_SEND_ALL').'
                                        //             </a>';
                                        //           };
                                        //         $str .= '</td>
                                        //     </tr>';
                                        // }
                                        $cnt++;
                                    }
                                };
                                $old_date_idData = "'".implode(',', array_filter($old_date_id))."'";
                                $hd_patient_idData = "'".implode(',', array_filter($hd_patient_id))."'";
                                $hd_dateData = "'".implode(',', array_filter($hd_date))."'";
                                $str .= '</table>';

                                 /**divyas code here*for-14-nov-22*for when finding and update both then show updated field also**/

                                if($tempFlag == 1 || $upFlag == 1)
                                {


                                  $old_patient = $this->OldPatientsModel
                                  ->where('fk_patient_id',$patient['id'])
                                  ->first();

                                   if(!empty($old_patient))
                                    {
                                          Log::info("==not empty old patient==>");

                                           Log::info($patient['id']);

                                        $str .= '<table class="new-patients table table-bordered tableBorder" >';
                                        if($old_patient->family_name != $patient['family_name'])
                                        {
                                              Log::info("==family_name==>");

                                            $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_FAMILY_NAME").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="family_name" id="family_name_u_'.$patient['id'].'" value="'.$patient['family_name'].'"></td></tr>';
                                        }

                                        if($old_patient->first_name != $patient['first_name'])
                                        {             
                                            Log::info("==first_name==>");     

                                            $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_FIRST_NAME").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="first_name" id="first_name_u_'.$patient['id'].'" value="'.$patient['first_name'].'"></td>
                                                          </tr>';
                                        }
                                        
                                        if($old_patient->email != $patient['email'])
                                        {
                                            Log::info("==email==>");  

                                           $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_EMAIL").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="email" id="email_u_'.$patient['id'].'" value="'.$patient['email'].'"></td>
                                                          </tr>';
                                        }

                                        if($old_patient->road != $patient['road'])
                                        {
                                             Log::info("==road==>");  

                                            $str .= '<tr class="tableBorder"><tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_ROAD").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="road" id="road_u_'.$patient['id'].'" value="'.$patient['road'].'"></td>
                                                          </tr>';
                                        } 

                                        if($old_patient->street_no != $patient['street_no'])
                                        {
                                             Log::info("==street no==>"); 

                                            $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_STREET_NO").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="street_no" id="street_no_u_'.$patient['id'].'" value="'.$patient['street_no'].'"></td>
                                                          </tr>';
                                        }
                                                                   
                                        
                                        if($old_patient->postal_code != $patient['postal_code'])
                                        {
                                            Log::info("==postal code==>"); 

                                            $str .= '
                                            <tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_POSTAL_CODE").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="postal_code" id="postal_code_u_'.$patient['id'].'" value="'.$patient['postal_code'].'"></td>
                                                          </tr>';
                                        }
                                        
                                        if($old_patient->place != $patient['place'])
                                        {
                                              Log::info("==place==>");

                                            $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_PLACE").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="place" id="place_u_'.$patient['id'].'" value="'.$patient['place'].'"></td>
                                                          </tr>';
                                        }

                                        
                                        
                                        if($old_patient->insurance_number != $patient['insurance_number'])
                                        {
                                             Log::info("==insurance_number==>");

                                            $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_TODO_ENSURANCE_NUMBER").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="insurance_number" id="insurance_number_u_'.$patient['id'].'" value="'.$patient['insurance_number'].'"></td>
                                                          </tr>';
                                        }
                                        if($old_patient->birth_date != $patient['birth_date'])
                                        {
                                             Log::info("==birth_date==>");


                                            $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_BIRTH_DATE").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="birth_date" id="birth_date_u_'.$patient['id'].'" value="'.$patient['birth_date'].'"></td>
                                                          </tr>';
                                        } 
                                      

                                        if($old_patient->mobile_no != $patient['mobile_no'])
                                        {
                                             Log::info("==mobile_no==>");

                                            $str .=' <tr><td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_MOBILE_NO').'-  '.__('admin.TITLE_PATIENT_MOBILE_NO').'</strong></td>
                                            <td class="tableBorder w-100-px">
                                            <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                            </td>
                                            <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="mobile_no" id="mobile_no_u_'.$patient['id'].'" value="'.$patient['country_code'] . $patient['mobile_no'] .'">
                                            </td>
                                            </tr>';
                                        }

                                       
                                        if($old_patient->size != $patient['size'])
                                        {
                                             Log::info("==size==>");

                                           $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_SIZE").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'</a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="size" id="size_u_'.$patient['id'].'" value="'.$patient['size'].'"></td>
                                                          </tr>';
                                        }
                                       
                                        if($old_patient->weight != $patient['weight'])
                                        {
                                             Log::info("==weight==>");

                                            $str .= '<tr class="tableBorder"> <tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_WEIGHT").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="weight" id="weight_u_'.$patient['id'].'" value="'.$patient['weight'].'"></td>
                                                          </tr>';
                                        }
                                        
                                        if($old_patient->title != $patient['title'])
                                        {
                                             Log::info("==title==>");

                                           $str .= '<tr class="tableBorder"><tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_TITLE").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="title" id="title_u_'.$patient['id'].'" value="'.$patient['title'].'"></td>
                                                          </tr>';
                                        }

                                        if($old_patient->family_doctor != $patient['family_doctor'])
                                        {
                                             Log::info("==family_doctor==>");

                                            $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_FAMILY_DOCTOR").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="family_doctor" id="family_doctor_u_'.$patient['id'].'" value="'.$patient['family_doctor'].'"></td>
                                                          </tr>';
                                        }                            
                                       
                                        if($old_patient->additional_insurance != $patient['additional_insurance'])
                                        {
                                              Log::info("==additional_insurance==>");

                                            $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_ADDITIONAL_ENSURANCE").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="additional_insurance" id="additional_insurance_u_'.$patient['id'].'" value="'.$patient['additional_insurance'].'"></td>
                                                          </tr>';
                                        }

                                       
                                        if(strtolower($old_patient->gender) != strtolower($patient['gender']))
                                        {
                                               Log::info("==gender==>");

                                            $str .= '<tr class="tableBorder"> 
                                                              <td class="tableBorder tdClsW">
                                                                <strong>'.__("admin.TITLE_PATIENT_GENDER").'</strong>
                                                              </td>
                                                              <td class="tableBorder w-100-px">
                                                                <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                                                </a>
                                                              </td>
                                                              <td class="tableBorder">
                                                                <input readonly class="form-control" type="text" name="gender" id="gender_u_'.$patient['id'].'" value="'.$patient['gender'].'"></td>
                                                          </tr>';
                                        }


                                        $str .= '</table>';
                                        
                                    }//if old_patient
                                }//if tmpglag 1 and upflag 1

                                /***************divyas code here*for-11-nov-22**************************/

                                

                                $str .='<span class="span_cls">
                                  <a onclick="completedNew(this,'.$patient->id.',\'completed\',\'finding\','.$old_date_idData.','.$hd_patient_idData.','.$hd_dateData.')" class="btn btn-primary" href="javascript:void(0)" >'.__('admin.TITLE_TOTO_NEW_COMPLETED').'</a>
                                  <a onclick="completedNew(this,'.$patient->id.',\'completed\',\'finding\','.$old_date_idData.','.$hd_patient_idData.','.$hd_dateData.')" class="btn btn-primary" href="javascript:void(0)" >'.__('admin.TITLE_TOTO_NEW_COMPLETED_NEXT').'</a>
                                  <a onclick="CancelNew(this,`'.$patient['id'].'`)" class="btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_TOTO_ABORT').'</a>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="collapse newClass" id="collapseNew_'.$patient['id'].'">
                    <div class="card card-body">
                        <table class="new-patients table table-bordered tableBorder" style="width:100%;" >'; 
                            if(!empty($patient->family_name)) { ;
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW">
                                    <strong>'.__('admin.TITLE_PATIENT_FAMILY_NAME').'</strong>
                                  </td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="family_name" id="family_name_'.$patient->id.'" value="'.$patient->family_name.'"></td>
                                </tr>';
                            };
                            if(!empty($patient->first_name)) { ;  
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW">
                                    <strong>'.__('admin.TITLE_PATIENT_FIRST_NAME').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="first_name" id="first_name_'.$patient->id.'" value="'.$patient->first_name.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->email)) {;  
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_EMAIL').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="email" id="email_'.$patient->id.'" value="'.$patient->email.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->road)){;     
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_ROAD').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').
                                    '</a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="road" id="road_'.$patient->id.'" value="'.$patient->road.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->street_no)){;
                                $str .= '<tr class="tableBorder">
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_STREET_NO').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="street_no" id="street_no_'.$patient->id.'" value="'.$patient->street_no.'">
                                  </td>
                                </tr>';
                            };
                            if(!empty($patient->postal_code)){ ;    
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_POSTAL_CODE').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control " type="text" name="postal_code" id="postal_code_'.$patient->id.'" value="'.$patient->postal_code.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->place)) {;
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_PLACE').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                     <input readonly class="form-control" type="text" name="place" id="place_'.$patient->id.'" value="'.$patient->place.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->gender)){ ;    
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_GENDER').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control " type="text" name="gender" id="gender_'.$patient->id.'" value="'.$patient->gender.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->insurance_number)) {;
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_TODO_ENSURANCE_NUMBER').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="insurance_number" id="insurance_number_'.$patient->id.'" value="'.$patient->insurance_number .'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->country_code) || !empty($patient->mobile_no)){; 
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_TODO_MOBILE_NO').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="mobile_no" id="mobile_no_'.$patient->id.'" value="'.$patient->country_code.' '.$patient->mobile_no .'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->size)) {;
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_SIZE').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="size" id="size_'.$patient->id.'" value="'.$patient->size.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->weight)) {;
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_WEIGHT').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="weight" id="weight_'.$patient->id.'" value="'.$patient->weight.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->title)) {;
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_TITLE').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').
                                    '</a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="title" id="title_'.$patient->id.'" value="'.$patient->title.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->family_doctor)) {;
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_FAMILY_DOCTOR').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="family_doctor" id="family_doctor_'.$patient->id.'" value="'.$patient->family_doctor.'">
                                   </td>
                                </tr>';
                            }
                            if(!empty($patient->additional_insurance)) {;
                                $str .= '<tr class="tableBorder"> 
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_ADDITIONAL_ENSURANCE').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="additional_insurance" id="additional_insurance_'.$patient->id.'" value="'.$patient->additional_insurance.'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->birth_date)){
                                $str .= '<tr class="tableBorder">
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_BIRTH_DATE').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="birth_date_" id="birth_date_'.$patient->id.'" value="'.date('d.m.Y',strtotime($patient->birth_date)).'">
                                  </td>
                                </tr>';
                            }
                            if(!empty($patient->pat_nr)){;
                                $str .= '<tr class="tableBorder">
                                  <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_GANY_PAT_NR').'</strong></td>
                                  <td class="tableBorder w-100-px">
                                    <a class="copybutton btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                    </a>
                                  </td>
                                  <td class="tableBorder">
                                    <input readonly class="form-control" type="text" name="pat_nr" id="pat_nr_'.$patient->id.'" value="'.$patient->pat_nr.'">
                                  </td>
                                </tr>';
                            }
                            $str .= '</table>
                            <span class="span_cls">
                              <a onclick="completedNew(this,'.$patient->id.',\'completed\',\'new\')" class="btn btn-primary" href="javascript:void(0)" >'.__('admin.TITLE_TOTO_NEW_COMPLETED').'</a>
                              <a onclick="completedNew(this,'.$patient->id.',\'next\',\'new\')" class="btn btn-primary" href="javascript:void(0)" >'.__('admin.TITLE_TOTO_NEW_COMPLETED_NEXT').'</a>
                              <a onclick="CancelNew(this,`'.$patient->id.'`)" class="btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_TOTO_ABORT').'</a>
                            </span>
                        </div>
                    </div>
                    ';
                    if($tempFlag == 1 || $upFlag == 1)
                    {
                        $str .= '<div class="collapse updateClass" id="collapseUpdate_'.$patient['id'].'"> <div class="card card-body updateRec_'.$patient['old_id'].'">
                        </div></div> ';
                    }
                }
            }
        }
        return $str;
    }

    public function sendFindingEmail(Request $request)
    {
        //dd($request->all());
        $result = '';
        try
        {
            $collection = collect([]);
            $admin_email = $this->SettingsModel
                           ->where('setting_key','=','ADMINISTRATOR_EMAIL')
                           ->whereStatus(1)
                           ->first();
            if($admin_email)
            {
                $AdminEmail = $admin_email->setting_value;
            }
            $patient_details  = $this->PatientsModel
                                ->where('id',$request->hd_finding_patient_id)
                                ->first();
            $collection['notes'] = $request->hd_notes ?? '';
            $old_finding = $this->PatientsHasOldFindingModel
                                ->where('id',$request->hd_finding_old_id)
                                ->where('imported_flag','1')
                                ->first();
            $documant_arr = [];
            //dump($old_finding);
            if(!empty($old_finding))
            {
                $document = $this->PatientsHasDiagnosticFindingsModel
                       ->where('patient_id',$request->hd_finding_patient_id)
                       ->where('old_finding_id',$request->hd_finding_old_id)
                       // ->whereDate('date',date('Y-m-d',strtotime($old_value['appoinmant_date'])))
                       ->with(['hasFindingDocument'])
                       ->get();
                //dd($document);
                if(!empty($document))
                {
                    foreach ($document as $finding_key => $finding_value)
                    {
                        foreach ($finding_value['hasFindingDocument'] as $doc_key => $doc_value)
                        {
                            $findingDocument = '';
                            $file_path = str_replace('\\', '/', $doc_value['file']);
                            if(!empty($file_path) && $doc_value['original_name'] == 'create_pdf' )
                            {
                                if(empty($doc_value['pdf_file']))
                                {
                                    $file_path = str_replace("\r\n", "<br/>", $file_path);
                                    $dompdf = new Dompdf();
                                    $data = '<!doctype html>
                                                <html lang="de">
                                                    <head>
                                                        <meta charset="UTF-8">
                                                        <meta name="viewport"
                                                              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
                                                        <meta http-equiv="X-UA-Compatible" content="ie=edge">
                                                        <title>Document</title>
                                                        <style>
                                                            body {
                                                                font-size:24px;
                                                            }
                                                        </style>
                                                    </head>
                                                    <body style="width: 100%;">'.$file_path.'
                                                    </body>
                                                </html>';
                                                 $dompdf->loadHTML($data);
                                    // $dompdf->setPaper('A4', 'landscape');
                                    // Render the HTML as PDF
                                    $dompdf->render();
                                    $output = $dompdf->output();
                                    $path   = self::StorePath('diagnostic_findings/pdf/');
                                    //$path = storage_path().'/app/diagnostic_findings/pdf/';
                                    $file_name = uniqid().'.pdf';
                                    file_put_contents($path.$file_name, $output);
                                    $findingDocument = $file_name;
                                    $this->PatientHasDiagnosticFindingsHasDocumentsModel->where('id',$doc_value['id'])->update(['pdf_file'=>$file_name]);
                                }
                                else{
                                    $findingDocument = $doc_value['pdf_file'];
                                }
                            }
                            elseif(!empty($file_path))
                            {
                                $ext = pathinfo($file_path, PATHINFO_EXTENSION);
                                if($ext != 'dcm')
                                {
                                    $findingDocument = $file_path;
                                }
                            }
                            $findingDocument = self::getFilePath($findingDocument);
                            $attachment      = $findingDocument;
                            $documant_arr[]  = $attachment;
                        }
                    }
                }
            }
            $collection['patient_name'] = ucfirst($request->patient_name);
            $collection['attachments']   = $documant_arr;
            $email =  $request->to;
            //GET APPOINMANT DATES
            $ap_date = [];
            $cnt = 0;
            // GET APPOINMANT DATES End
            $collection['appoinmant_date']= $ap_date;
            $this->PatientsHasOldFindingModel
                ->where('id',$request->hd_finding_old_id)
                ->update(['imported_flag'=>'2']);
            //dd($collection);
            $result = Mail::to($email)->send(new SendFindingForPatientmail($collection));
            // Update Patient Status Flag.... Added by Shyam 23-12-21
            $allOldFinding = $this->PatientsHasOldFindingModel->where('fk_patient_id',$request->hd_finding_patient_id)
                                    ->whereIn('imported_flag', ['0', '1'])->get();
            if(sizeof($allOldFinding) == 0) {
                $patient_details->patient_status_flag = '1';
                $patient_details->save();
            }// Added by Shyam 23-12-21
            // if(empty($result))
            // {
            //     $aa = $this->PatientsHasOldFindingModel->get();
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['patient_id']=  $request->hd_finding_patient_id;
                $this->JsonData['msg_for_todo_list']= __('admin.FINDING_SEND_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath);
                $this->JsonData['msg']    = __('admin.FINDING_SEND_SUCCESS');
            // }
            // else {
            //     $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            //     $this->JsonData['patient_id']=  '';
            //     $this->JsonData['msg_for_todo_list']=  __('admin.ERR_SOMETHING_WRONG');
            //     //$this->JsonData['error_msg'] = $e->getMessage();
            // }
        }
        catch(\Exception $e)
        {
            $message = __('admin.ERR_SOMETHING_WRONG');
            $errors[] = [
                    "error" => $e->getMessage(),
                ];
        }
        Session::put('redirect_arr',$this->JsonData);
        return redirect('admin/assistant-dashboard?tab=todoList');
    }

    //PUSH NOTIFICATION FOR THE PATIENT
    public function pushNotificationForPetient(Request $request)
    {
        $rec = 'false';
        $collections =  $this->PatientsModel
                        ->where('id',$request->p_id)
                        ->first();
       
        $current_time = date("Y-m-d H:i:s",time());
        if(!empty($collections))
        {
            if(!empty($request->old_id))
            {
                $old_finding = $this->PatientsHasOldFindingModel
                              ->where('id',$request->old_id)
                              ->where('imported_flag','1')
                              ->get();
            }
            else
            {
                $old_finding = $this->PatientsHasOldFindingModel
                              ->where('fk_patient_id',$request->p_id)
                              ->where('imported_flag','1')
                              ->get();

            }
           
            if(!empty($old_finding) && count($old_finding)>0)
            {
                // $end_time    = strtotime(date('Y-m-d H:i:s',strtotime($collection->notify_time)));
                $end_time    = strtotime(date('Y-m-d H:i:s'));
                $start_time  = strtotime(date('Y-m-d H:i:s',time()));  
                $time_diff   = $end_time - $start_time ;

                //OLD DATE ARRAY
                $old_finding_date = '';
                $old_finding_date_arr = [];
                $cnt = 1;
                foreach ($old_finding as $old_key => $old_value) 
                {
                    $old_finding_date_arr[] = date('d-m-Y',strtotime($old_value['appoinmant_date']));
                    if($cnt ==1)
                    {
                        $old_finding_date .= date('d-m-Y',strtotime($old_value['appoinmant_date']));
                    }
                    else
                    {
                        $old_finding_date .= ','.date('d-m-Y',strtotime($old_value['appoinmant_date']));
                    }
                    
                    $cnt++;
                }
                
                if($time_diff>=0 && $time_diff<=300)
                {
                    $note = null;
                    if(!empty($request->notes))
                    {
                        $note = 'Notes : '. $request->notes;
                    }
                    $PatientId   = $request->p_id;

                    $content     = 'Hello '.$collections->first_name.' '.$collections->family_name.
                                    ', Importieren Sie den alten Befund für unten stehende Daten :'.$old_finding_date.' '.$note;

                    $content_ins   = 'Hello '.$collections->first_name.' '.$collections->family_name.
                                    'Befunde, die sie angefragt haben, sind nun für sie in der App verfügbar.';                

                    $patientText = $collection->salutation ?? "";
                    $title = 'Import zu Erkenntnissen';

                    $mobileId = $this->PatientHasDeviceModel
                                ->where('patient_id',$PatientId)
                                ->get(['device_id']);
                   
                    if(!empty($mobileId))
                    {
                        $mobile_uuids = array_column($mobileId->toArray(), "device_id");
                       
                        $player_ids   = $mobile_uuids;
                        $headings       = array("en" => (string)$title);
                        // Create an single string of all content
                        $content        = array(
                                                "en" => (string)$content
                                                );
                        $postData = array(
                                        "dates" => json_encode($old_finding_date_arr),
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
                        curl_close($ch);  
                        
                        $result = json_decode($data, true);
                     
                        if(isset($result['errors']) && sizeof($result['errors'])>0)
                        {
                            if(empty($player_ids))
                            {
                                $rec = __('admin.TITLE_DOCUMENT_SEND');
                            }
                            else
                            {
                                $rec = 'false';
                            }
                        }
                        else
                        {
                            $updateStatus = new $this->FindingHasNotificationModel;
                            $updateStatus->patient_id  = 5;
                            $updateStatus->notify_time = date('Y-m-d H:i:s'); 
                            $updateStatus->title       = $title;
                            $updateStatus->content     = $content_ins;
                            $updateStatus->status      = 1; 
                            $updateStatus->one_signal_response = $data;
                            $updateStatus->created_at          = date('Y-m-d');
                            if($updateStatus->save())
                            {
                                $rec= 'true';

                            }
                            else
                            {
                                $rec='false';
                            }    
                        }
                    }
                }
            }
        }

        return $rec;
        
    }

    // Send push notification
    public function sendPushNotification($request)
    {
        $collection = collect([]);
        $admin_email = $this->SettingsModel
                       ->where('setting_key','=','ADMINISTRATOR_EMAIL')
                       ->whereStatus(1)
                       ->first();

        $admin_email = $this->SettingsModel
                    ->where('setting_key','=','ADMINISTRATOR_EMAIL')
                    ->whereStatus(1)
                    ->first();
        if($admin_email)
        {
            $AdminEmail = $admin_email->setting_value;
        }               
           
        $patient_details  = $this->PatientsModel
                            ->where('id',$request->p_id) 
                            ->first();
                            dd($patient_details->email);
        if(!empty($patient_details) && !empty($patient_details->email))
        {
            //dd("-->");
            if(isset($request->notes) && !empty($request->notes))
            {
                $old_finding = $this->PatientsHasOldFindingModel
                                ->where('fk_patient_id',$request->p_id) 
                                ->get();
                $collection['notes']   = $request->notes;
            }
            else
            {
                $old_finding = $this->PatientsHasOldFindingModel
                                ->where('id',$request->old_id)
                                ->where('imported_flag','1')
                                ->get();
                $collection['notes']   = null;
            }                    
                           
            $documant_arr = [];
            if(!empty($old_finding) && count($old_finding)>0)
            {
                foreach ($old_finding as $old_key => $old_value) 
                {
                    $document = $this->PatientsHasDiagnosticFindingsModel
                           ->where('patient_id',$request->p_id)
                           ->whereDate('date',date('Y-m-d',strtotime($old_value['appoinmant_date'])))
                           ->with(['hasFindingDocument'])
                           ->get();
                
                    if(!empty($document))
                    {
                        foreach ($document as $finding_key => $finding_value) 
                        {
                            foreach ($finding_value['hasFindingDocument'] as $doc_key => $doc_value) 
                            {
                                $findingDocument = '';
                                $file_path = str_replace('\\', '/', $doc_value['file']);

                               if(!empty($file_path) && $doc_value['original_name'] == 'create_pdf' )
                                {                                    
                                    if(empty($doc_value['pdf_file']))
                                    {

                                        $file_path = str_replace("\r\n", "<br/>", $file_path);

                                        $dompdf = new Dompdf();
                                       
                                        $data = '<!doctype html>
                                                    <html lang="de">
                                                        <head>
                                                            <meta charset="UTF-8">
                                                            <meta name="viewport"
                                                                  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
                                                            <meta http-equiv="X-UA-Compatible" content="ie=edge">
                                                            <title>Document</title>
                                                            <style>
                                                                body {
                                                                    font-size:24px;
                                                                }
                                                            </style>
                                                        </head>

                                                        <body style="width: 100%;">'.$file_path.'
                                                        </body>
                                                    </html>';
                                                     $dompdf->loadHTML($data);
                                       // $dompdf->setPaper('A4', 'landscape');

                                        // Render the HTML as PDF
                                        $dompdf->render();
                                      
                                        $output = $dompdf->output();
                                        $path   = self::StorePath('diagnostic_findings/pdf/'); 
                                        //$path = storage_path().'/app';
                                        $file_name = uniqid().'.pdf';
                                      
                                        file_put_contents($path.$file_name, $output);
                                       
                                        $findingDocument = $file_name; 
                                    }
                                    else
                                    {
                                        $findingDocument = $doc_value['pdf_file'];
                                    }
                                }
                                // elseif(!empty($file_path) && is_file(storage_path().'/app/'.$file_path))
                                elseif(!empty($file_path)) 
                                {
                                    $ext = pathinfo($file_path, PATHINFO_EXTENSION);

                                    if($ext != 'dcm'  )
                                    {
                                        $findingDocument = $file_path;
                                    }  
                                }
                                $attachment = url('/storage/app'.$findingDocument);
                                $documant_arr[] = $attachment;
                            }
                        }
                    } 
                }
            } 
            $collection['patient_name'] = ucfirst($patient_details->family_name).' '.$patient_details->first_name;
            $collection['attachments']   = $documant_arr;
            $email =  $email->to;   
            //GET APPOINMANT DATES
            $ap_date = [];
            $cnt = 0;
            // GET APPOINMANT DATES End
           
            $collection['appoinmant_date']= $ap_date;

            $result = Mail::to($email)->send(new SendFindingForPatientmail($collection));
           
            if(empty($result))
            {
                return 'true';
            }
            else
            {
                return 'false';
            }
        }
        else
        {
            return 'false';
        }
    }

    public function getOldAppoinmant(Request $request)
    {
        $PatientsHasOldFindingModel = $this->PatientsHasOldFindingModel
                                      // ->groupBy('appoinmant_date')
                                      ->find($request->old_id);
                                     

        $str = '';
        if(!empty($PatientsHasOldFindingModel))
        {
            //foreach ($PatientsHasOldFindingModel as $key => $value) 
            // {
                $str .= '<ul><li>'.Date('d.y.Y',strtotime($PatientsHasOldFindingModel->appoinmant_date)).'</li></ul>';
            //}
        } 

        return $str;   
    }

    public function checkRecordWithGanymed(Request $request)
    {
        $id = $request->id;
        $update_record= '';
        $checkRecord =  DB::table("patients")
                            ->where('id','=',$id)
                            ->first();       

                           // dd($checkRecord);

        try 
        { 

            $old_patient = $this->OldPatientsModel
                          ->where('fk_patient_id',$checkRecord->id)
                          ->first();

                //dd($old_patient);   
            if(!empty($checkRecord) || !empty($old_patient))
            {

              // }
          
               //  $gany_mobile_no     = trim($patientGanymedRecord->tel_nr);


               
               //  $birth_date = date("Y-m-d", strtotime(trim($patientGanymedRecord->geb_dat)));
               //  $age = (date('Y') - date('Y',strtotime($birth_date)));

               //  $internationalFormat = substr($gany_mobile_no, 0, 1);
               //  $country_code = '';
               //  $mobile_no    = '';
               //  if($internationalFormat == '+'){
               //      $country_code = trim(substr($gany_mobile_no, 1,2));
               //      $mobile_no      = trim(str_replace(" ","",substr($gany_mobile_no, 3)));
               //  }

               //  $internationalFormat = substr($gany_mobile_no, 0, 2);
               //  if($internationalFormat == '00'){
               //      $country_code = trim(substr($gany_mobile_no, 0,4));
               //      $mobile_no      = trim(str_replace(" ","",substr($gany_mobile_no, 4)));
               //  }

               //  $localFormat = substr($gany_mobile_no, 0, 1);
               //  if($localFormat == '0' && $internationalFormat != '00'){
               //      $country_code = trim(substr($gany_mobile_no, 0,1));
               //      $mobile_no      = trim(str_replace(" ","",substr($gany_mobile_no, 1)));
               //  }

               //  $mobile_no      = trim(str_replace("/","",$mobile_no));
               //  $mobile_no      = trim(str_replace("-","",$mobile_no));
               //  $mobile_no      = trim($mobile_no,"'");

               //  $tmp = [];
               //  $prev_tmp = [];
               // $famname = trim(trim($patientGanymedRecord->famname),"'");
                $update_record .= '<table class="new-patients table table-bordered tableBorder" >';
                if ($old_patient) {
                    if($old_patient->family_name != $checkRecord->family_name)
                    {
                        $update_record .= '
                                            <tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_FAMILY_NAME").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="family_name" id="family_name_u_'.$checkRecord->id.'" value="'.$checkRecord->family_name.'"></td>
                                    </tr>
                                    ';
                    }
                    // $vorname = trim(trim($patientGanymedRecord->vorname),"'");
                    
                    if($old_patient->first_name != $checkRecord->first_name)
                    {               
                        $update_record .= '
                                        <tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_FIRST_NAME").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="first_name" id="first_name_u_'.$checkRecord->id.'" value="'.$checkRecord->first_name.'"></td>
                                    </tr>';
                    }
                    // $eMail = trim(trim($patientGanymedRecord->eMail),"'");
                    
                    if($old_patient->email != $checkRecord->email)
                    {
                    $update_record .= '<tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_EMAIL").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="email" id="email_u_'.$checkRecord->id.'" value="'.$checkRecord->email.'"></td>
                                    </tr>';
                    }
                    // $strasse = trim(trim($patientGanymedRecord->strasse),"'");

                    if($old_patient->road != $checkRecord->road)
                    {
                        $update_record .= '<tr class="tableBorder"><tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_ROAD").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="road" id="road_u_'.$checkRecord->id.'" value="'.$checkRecord->road.'"></td>
                                    </tr>';
                    } 

                    if($old_patient->street_no != $checkRecord->street_no)
                    {
                        $update_record .= '
                                        <tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_STREET_NO").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="street_no" id="street_no_u_'.$checkRecord->id.'" value="'.$checkRecord->street_no.'"></td>
                                    </tr>';
                    }
                                            
                    // $plz = trim(trim($patientGanymedRecord->plz),"'");
                    
                    if($old_patient->postal_code != $checkRecord->postal_code)
                    {
                    
                        $update_record .= '
                        <tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_POSTAL_CODE").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="postal_code" id="postal_code_u_'.$checkRecord->id.'" value="'.$checkRecord->postal_code.'"></td>
                                    </tr>';
                    }
                    // $ort = trim(trim($patientGanymedRecord->ort),"'");
                    
                    if($old_patient->place != $checkRecord->place)
                    {
                        $update_record .= '
                                        <tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_PLACE").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="place" id="place_u_'.$checkRecord->id.'" value="'.$checkRecord->place.'"></td>
                                    </tr>';
                    }

                    
                    // $vers_nr = trim(trim($patientGanymedRecord->vers_nr),"'");
                    
                    if($old_patient->insurance_number != $checkRecord->insurance_number)
                    {
                        $update_record .= '
                                            <tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_TODO_ENSURANCE_NUMBER").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="insurance_number" id="insurance_number_u_'.$checkRecord->id.'" value="'.$checkRecord->insurance_number.'"></td>
                                    </tr>';
                    }
                    if($old_patient->birth_date != $checkRecord->birth_date)
                    {
                        $update_record .= '<tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_BIRTH_DATE").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="birth_date" id="birth_date_u_'.$checkRecord->id.'" value="'.$checkRecord->birth_date.'"></td>
                                    </tr>';
                    } 
                    // $update_mob_no= 0;
                    // if(!empty($country_code) && trim($country_code) != $checkRecord->country_code)
                    // {
                    //     $update_mob_no= 1;
                    // }
                    // if(!empty($mobile_no) && trim($mobile_no) != $checkRecord->mobile_no)
                    // {
                    //     $update_mob_no= 1;
                    // }

                    if($old_patient->mobile_no != $checkRecord->mobile_no)
                    {
                        $update_record .=' <tr> 
                        <td class="tableBorder tdClsW"><strong>'.__('admin.TITLE_PATIENT_MOBILE_NO').'-  '.__('admin.TITLE_PATIENT_MOBILE_NO').'</strong></td>
                        <td class="tableBorder w-100-px">
                        <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                        </a>
                        </td>
                        <td class="tableBorder">
                        <input readonly class="form-control" type="text" name="mobile_no" id="mobile_no_u_'.$checkRecord->id.'" value="'.$checkRecord->country_code . $checkRecord->mobile_no .'">
                        </td>
                        </tr>';
                    }

                    // $groesse = trim(trim($patientGanymedRecord->groesse),"'");
                
                    if($old_patient->size != $checkRecord->size)
                    {
                    $update_record .= '<tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_SIZE").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'</a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="size" id="size_u_'.$checkRecord->id.'" value="'.$checkRecord->size.'"></td>
                                    </tr>';
                    }
                    // $gewicht = trim(trim($patientGanymedRecord->gewicht),"'");
                
                    if($old_patient->weight != $checkRecord->weight)
                    {
                        $update_record .= '<tr class="tableBorder"> <tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_WEIGHT").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="weight" id="weight_u_'.$checkRecord->id.'" value="'.$checkRecord->weight.'"></td>
                                    </tr>';
                    }
                    // $titel = trim(trim($patientGanymedRecord->titel),"'");
                    
                    if($old_patient->title != $checkRecord->title)
                    {
                    $update_record .= '<tr class="tableBorder"><tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_TITLE").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__("admin.TITLE_COPY").'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="title" id="title_u_'.$checkRecord->id.'" value="'.$checkRecord->title.'"></td>
                                    </tr>';
                    }
                    // $Hausarzt = trim(trim($patientGanymedRecord->Hausarzt),"'");

                    if($old_patient->family_doctor != $checkRecord->family_doctor)
                    {
                        $update_record .= '<tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_FAMILY_DOCTOR").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a  class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="family_doctor" id="family_doctor_u_'.$checkRecord->id.'" value="'.$checkRecord->family_doctor.'"></td>
                                    </tr>';
                    }                            
                    // $zu_vers = trim(trim($patientGanymedRecord->zu_vers),"'");
                
                    if($old_patient->additional_insurance != $checkRecord->additional_insurance)
                    {
                        $update_record .= '<tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_ADDITIONAL_ENSURANCE").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="additional_insurance" id="additional_insurance_u_'.$checkRecord->id.'" value="'.$checkRecord->additional_insurance.'"></td>
                                    </tr>';
                    }

                    // additional_insurance $geschl = trim(trim($patientGanymedRecord->geschl),"'");
                
                    if(strtolower($old_patient->gender) != strtolower($checkRecord->gender))
                    {
                        $update_record .= '<tr class="tableBorder"> 
                                        <td class="tableBorder tdClsW">
                                            <strong>'.__("admin.TITLE_PATIENT_GENDER").'</strong>
                                        </td>
                                        <td class="tableBorder w-100-px">
                                            <a   class="copybutton btn btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_COPY').'
                                            </a>
                                        </td>
                                        <td class="tableBorder">
                                            <input readonly class="form-control" type="text" name="gender" id="gender_u_'.$checkRecord->id.'" value="'.$checkRecord->gender.'"></td>
                                    </tr>';
                    }
                }

                $update_record .= '</table><span class="span_cls">
                                          <a onclick="completedNew(this,'.$request->id.',\'completed\',\'update\')" class="btn btn btn-primary" href="javascript:void(0)" >'.__('admin.TITLE_TOTO_NEW_COMPLETED').'</a>
                                          <a onclick="completedNew(this,'.$request->id.',\'next\',\'update\')" class="btn btn btn-primary" href="javascript:void(0)" >'.__('admin.TITLE_TOTO_NEW_COMPLETED_NEXT').'</a>
                                          <a onclick="CancelNew(this,`'.$request->id.'`)" class="btn btn btn-primary highlight" href="javascript:void(0)" >'.__('admin.TITLE_TOTO_ABORT').'</a>
                                      </span>';
               
            } 

            //Log::info('try case in ganymed connection');

            return $update_record;           
        }
        catch(\Illuminate\Database\QueryException $ex){ 
            //Log::info('catch case in ganymed connection'.$ex->getMessage());
          return $update_record;
        }

    }

    public function updateCount($id)
    {
        $checkRecord =  DB::table("patients")
                            ->where('id','=',$id)
                            ->first();
        $old_patient = $this->OldPatientsModel
                        ->where('fk_patient_id',$checkRecord->id)
                        ->first();
        if(!empty($old_patient))
        {
            $temp = 0;
            if($old_patient->first_name != $checkRecord->first_name)
            {    
                $temp = 1;           
            }
            if($old_patient->email != $checkRecord->email)
            {
                $temp = 1;
            }
            if($old_patient->road != $checkRecord->road)
            {
                $temp = 1;
            }
            if($old_patient->postal_code != $checkRecord->postal_code)
            {
                $temp = 1;
            }
            if($old_patient->place != $checkRecord->place)
            {
                $temp = 1;
            }
            if($old_patient->insurance_number != $checkRecord->insurance_number)
            {
                $temp = 1;
            }
            if($old_patient->birth_date != $checkRecord->birth_date)
            {
                $temp = 1;
            }
            if($old_patient->mobile_no != $checkRecord->mobile_no)
            {
                $temp = 1;
            }
            if($old_patient->size != $checkRecord->size)
            {
                $temp = 1;
            }
            if($old_patient->weight != $checkRecord->weight)
            {
                $temp = 1;
            }
            if($old_patient->title != $checkRecord->title)
            {
                $temp = 1;
            }
            if($old_patient->family_doctor != $checkRecord->family_doctor)
            {
                $temp = 1;
            }
            if($old_patient->additional_insurance != $checkRecord->additional_insurance)
            {
                $temp = 1;
            }
            if(strtolower($old_patient->gender) != strtolower($checkRecord->gender))
            {
                $temp = 1;
            }
        }
        else {
            $temp =  0;
        }
        return $temp;
    }

    public function getDismissalCount(Request $request)
    { 
        $str = $newexaminationCnt = '';
        $Eflag = $Dflag = $Aflag = 0;
        $count            = $request->get('count');  
        $DismissalCount   = $request->get('dismissalcnt');
        $examinationCount = $request->get('examinaton_cnt');
        
        //dd($count,$DismissalCount);  
        // Dismisal record count
        $getTotalDismissal   = $this->PatientsHasDismissalModel
                               ->where('dismissal_flag','0')
                               ->count();

        $dismissal_new_count = $getTotalDismissal;
        // Dismissal End
        $getDismissal   = $this->getExaminationAndDismissal();

        $newCount = count($getDismissal);
        $oldcount = (int)$count;
        $oldDismissalCount   = (int)$DismissalCount;    
        $oldexaminationCount = (int)$examinationCount;
        // Patirnts count check
        if($newCount != $oldcount)
        {
            $Aflag = 1;
        }
       
        if(!empty($getDismissal) && count($getDismissal)>0)
        {
            
            foreach($getDismissal as $key => $val)
            {
                
                if(!empty($val['patient']['appoinmant']) && sizeof($val['patient']['appoinmant'])>0)
                {
                   // Examination Count check
                    if(!empty($val['patient']['appoinmant']['examination']) && sizeof($val['patient']['appoinmant']['examination'])>0)
                    {
                        $newexaminationCnt = count($val['patient']['appoinmant']['examination']);
                        if($oldexaminationCount != $newexaminationCnt)
                        {
                            $Eflag = 1;
                        }
                        
                    }
                    else
                    {
                        $newexaminationCnt = 0;
                        if($oldexaminationCount != $newexaminationCnt)
                        {
                            $Eflag = 1;
                        }
                    }
                    // Disminssal count 
                    if(!empty($val['patient']['appoinmant']['dismissal']) && sizeof($val['patient']['appoinmant']['dismissal'])>0)
                    {
                        $newDismissalCnt = count($val['patient']['appoinmant']['dismissal']);
                       
                        if($oldDismissalCount !=  $newDismissalCnt)
                        {
                            $Dflag = 1;
                        }
                    }
                    else
                    {
                        $newDismissalCnt = 0;
                        if($oldDismissalCount !=  $newDismissalCnt)
                        {
                            $Dflag = 1;
                        }
                    }
                }
              
            }
        }
        // IF count change then return value
        if($Aflag == 1 || $Eflag == 1 || $Dflag == 1)
        {
            return 1;
        }
        else
        {
            return 0;
        }
        
    }

    public function getDismissalRefreshData(Request $request)
    {
        $str = '';
        // ---------------------------------------------
       
        $getDismissalHasPatients = $this->getExaminationAndDismissal();
        $getTotalDismissal   = $this->PatientsHasDismissalModel
                               ->where('dismissal_flag','0')
                               ->count();
      
       $str .='<div class="row">
                <div class="col-12">
                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">';  

                        if(!empty($getDismissalHasPatients) && count($getDismissalHasPatients)>0){
                            foreach($getDismissalHasPatients as $key => $val){;
                               if((!empty($val['patient']['appoinmant']) && sizeof($val['patient']['appoinmant'])>0)){;
                              $str .='<form id="frm_'.$val['patient']['p_id'].'" class="dismissal_frm" method="post"> 
                                  <input type="hidden" name="hd_dismissal_cnt" id="hd_dismissal_cnt" value="'.$getTotalDismissal.'">  
                                <div class="row">
                                  <div class="col-sm-3"> 
                                    <div class="form-group">
                                      <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">
                                      <label class="theme-blue" style="font-weight: 500!important;font-size: 18px;">
                                        <p style="font-weight: 600;font-size: 20px;">'.$val['patient']['full_name'].'</p>
                                      </label>
                                    </div>
                                  </div>';
                                   
                                        $str .='<div class="col-sm-9"> 
                                        <div class="p-0 form-group"> 
                                          <button onclick="dismissalDone('.$val['patient']['p_id'].')" type="button" lang="'.$val['patient']['p_id'].'" class="btn btn-primary dismissal_done">'.__('admin.TITLE_DISMISSAL_BUTTON').'</button> 
                                        </div>
                                      </div>
                                </div>
                                <!-- Dismissal -->';
                                if(!empty($val['patient']['appoinmant']['dismissal'])>0 && sizeof($val['patient']['appoinmant']['dismissal'])>0)
                                {

                                    $str .='<div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">';
                                                // $str .='<p style="font-weight: 600;">Appoitment : '.$val['patient']['appointment_date'].'</p>';
                                            $str .='</div>
                                        </div>
                                      </div>
                                      <div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">
                                             
                                                <p style="font-weight: 600;">'.__('admin.TITLE_ASSISTANT_DASHBOARD_DISMISSAL').'
                                                 
                                                </p>
                                              
                                            </div>
                                        </div>';
                                        foreach($val['patient']['appoinmant']['dismissal'] as $ad_key => $ad_val){;
                                        $str .='<div class="col-sm-3">
                                          <div class="form-group">
                                            <div class="form-check"> 
                                              <input type="checkbox" class="form-check-input"
                                                    name="dismissal['.$ad_val['appointment_id'].'][]" value="'.$ad_val['id'].'" 
                                                    >
                                              
                                              <label class="form-check-label" for="new_patient_chkbox">'.$ad_val['name'].'</label>
                                            </div>
                                          </div>
                                        </div>';
                                        };


                                      $str .='</div>';


                                        }
                                };

                                if(!empty($val['patient']['appoinmant']['reminder'])>0 && sizeof($val['patient']['appoinmant']['reminder'])>0)
                                {
                                    $str .='<div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">';
                                                // $str .='<p style="font-weight: 600;">Appoitment : '.$val['patient']['appointment_date'].'</p>';
                                            $str .='</div>
                                        </div>
                                      </div>
                                      <div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">
                                             
                                                <p style="font-weight: 600;">'.__('admin.TITLE_REMINDER').'
                                                 
                                                </p>
                                              
                                            </div>
                                        </div>';
                                        foreach($val['patient']['appoinmant']['reminder'] as $ad_key => $ad_val){;
                                        $str .='<div class="col-sm-3">
                                          <div class="form-group">
                                            <div class="form-check"> 
                                              <input type="checkbox" class="form-check-input"
                                                    name="dismissal['.$ad_val['appointment_id'].'][]" value="'.$ad_val['id'].'" 
                                                    >
                                              
                                              <label class="form-check-label" for="new_patient_chkbox">'.$ad_val['name'].' ('.$ad_val['control_interval'].')</label>
                                            </div>
                                          </div>
                                        </div>';
                                        };


                                      $str .='</div>';
                                }

                                if(!empty($val['patient']['appoinmant']['examination'])>0 && sizeof($val['patient']['appoinmant']['examination'])>0)
                                {   
                                      $str .='<input type="hidden" name="hd_examinaton_cnt" id="hd_examinaton_cnt" value="'.count($val['patient']['appoinmant']['examination']).'"><div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="form-group">
                                              <input type="hidden" name="p_id[]" value="'.$val['patient']['p_id'].'">
                                              
                                                <p style="font-weight: 600;">'.__('admin.TITLE_EXAMINATIONS_TEXT').'
                                                 
                                                </p>
                                             
                                            </div>
                                        </div>';
                                        foreach($val['patient']['appoinmant']['examination'] as $e_key => $e_val){
                                        $str .='<div class="col-md-3 col-sm-6">
                                          <div class="form-group">
                                            <div class="form-check"> 
                                              <input  type="checkbox" class="form-check-input"
                                                    name="examination['.$e_val['appointment_id'].'][]" value="'.$e_val['id'].'" 
                                                    >
                                              
                                              <label class="form-check-label" for="new_patient_chkbox">'.$e_val['name'].'</label>
                                            </div>
                                          </div>
                                        </div>';
                                        }
                                      $str .='</div>'; 
                                    
                                }; 
                                $str .='<!-- Examination -->';
                                
                              $str .='</form>
                            <hr>';
                          }
                        }
                        else
                        {;
                              $str .='<div class="row">
                              <div class="col-sm-12"> 
                                <div class="form-group" style="margin-left: 300px;font-size: 20px;">
                                  <label class="theme-blue">
                                    <p>'.__('admin.ERR_DISMISSAL_REQUIRED_NOT_EXIST').'</p>
                                  </label>
                                </div>
                              </div>
                            </div>';
                        };
                        $str .='</div>
                    </div>
                </div>
            </div>';
        // ---------------------------------------------
        return $str.'****'.count($getDismissalHasPatients);
        //return $str;
    }

    public function getTodoListCount(Request $request)
    {
        $count = $request->get('count');
        $sql = "SELECT `patients`.`id` FROM `patients` 
                WHERE `status` = 1 AND `patient_status_flag` = '0' AND `old_id` != '0'
                AND `update_ganydb` = '1' AND `new_flag`='1'
                AND id In(
                select DISTINCT (patients.id) from `patients` 
                inner join `old_patients` on `patients`.`id` = `old_patients`.`fk_patient_id` 
                    where (patients.road != old_patients.road or patients.size != old_patients.size 
                    or patients.email != old_patients.email or patients.title != old_patients.title 
                    or patients.weight != old_patients.weight or patients.gender != old_patients.gender 
                    or patients.mobile_no != old_patients.mobile_no or patients.birth_date != old_patients.birth_date 
                    or patients.first_name != old_patients.first_name or patients.postal_code != old_patients.postal_code 
                    or patients.family_doctor != old_patients.family_doctor 
                    or patients.insurance_number != old_patients.insurance_number 
                    or patients.additional_insurance != old_patients.additional_insurance))";
        $results = DB::select($sql);
        $updateIds = [];
        foreach ($results as $ptnt)
        {
            $updateIds[] = $ptnt->id;
        }
        $patient_cnt = $this->PatientsModel
                    ->orWhere(function($q) 
                    {
                        $q->whereNotNull('note_report_request')
                            ->Where('note_report_request_flag','>', '0');
                    })->orWhere(function($q1) {
                        $q1->Where('update_ganydb','1')
                            ->Where('new_flag','1');
                    })->orWhere(function($q2) {
                        $q2->Where('new_flag','1');
                    })
                    ->with(['getOldAppoinmant'])
                    ->orderBy('updated_at','DESC')
                    ->get();
        $patient_cnt = $patient_cnt->filter(function($item)
        {
            if($item->patient_status_flag == '0' && $item->status == '1' && $item->old_id != '0')
            {
                return $item;
            }
        });
        $patient_cnt = $patient_cnt->filter(function($item) use($updateIds)
        {
            if($item->new_flag == '1' && $item->update_ganydb!=1)
            {
                return $item;
            }
            elseif(($item->note_report_request_flag == '1' || $item->note_report_request_flag == '2'))
            {
                return $item;
            }
            elseif($item->update_ganydb == 1)
            {
                if(in_array($item->id, $updateIds))
                {
                    return $item;
                }
            }
            elseif ($item->update_ganydb == 1 && $item->new_flag =='1' && $item->old_id != '0')
            {
                return $item;
            }
        });
        $newCount = count($patient_cnt);
        $oldcount = (int)$count;
        if($oldcount == $newCount)
        {
            return 0;
        }
        else {
            return $newCount;
        }
    }

}