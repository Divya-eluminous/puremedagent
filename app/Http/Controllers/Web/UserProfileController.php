<?php
namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

// Models

use App\Models\PatientsModel;
use App\Models\AppointmentTypesModel;
use App\Models\AppointmentModel;
use App\Models\RosterModel;
use App\Models\AppointmentTypeHasNonExaminationsModel;
use App\Models\CheckListHasSelectedQuestionModel;
use App\Models\PatientHasDocumentsModel;
use App\Models\CheckListModel;
use App\Models\CheckListHasHeadingSectionModel;
use App\Models\HeadingSectionHasQuestionModel;
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\ExaminationsModel;
use App\Models\SpecialistDocumentsModel;
use App\Models\AppointmentHasExaminationsModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\PatientsHasServiceControlReminderModel;
use App\Models\OrdinationHasSpecialistModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\EventTypeHasExaminationsModel;
use App\Models\SettingsModel;
use App\Models\PatientHasReminder;
// Request
use App\Http\Requests\Web\UserProfileRequest;
use App\Models\CountryCodesModel; // new model for country code lookup

//Trait
use App\Traits\GeneralTrait;

// plugins
// use Mail;
use Hash;
use Session;
use DB;
use Illuminate\Support\Facades\Log;
use Lang;
use Mail;
use Carbon\Carbon;

class UserProfileController extends Controller
{
    private $BaseModel;
    use GeneralTrait;

    public function __construct(
        PatientsModel $PatientsModel,
        AppointmentTypesModel $AppointmentTypesModel,
        AppointmentModel $AppointmentModel,
        RosterModel $RosterModel,
        AppointmentTypeHasNonExaminationsModel $AppointmentTypeHasNonExaminationsModel,
        CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
        PatientHasDocumentsModel $PatientHasDocumentsModel,
        CheckListModel $CheckListModel,
        CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
        HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
        ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
        ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        ExaminationsModel $ExaminationsModel,
        SpecialistDocumentsModel $SpecialistDocumentsModel,
        AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
        PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
        PatientsHasServiceControlReminderModel $PatientsHasServiceControlReminderModel,
        OrdinationHasSpecialistModel $OrdinationHasSpecialistModel,
        EventTypeHasExaminationsModel $EventTypeHasExaminationsModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
        SettingsModel $SettingsModel,
        PatientHasReminder $PatientHasReminder,
        CountryCodesModel $CountryCodesModel

    ) {
        $this->BaseModel = $PatientsModel;
        $this->AppointmentModel = $AppointmentModel;
        $this->AppointmentTypesModel = $AppointmentTypesModel;
        $this->RosterModel = $RosterModel;
        $this->AppointmentTypeHasNonExaminationsModel = $AppointmentTypeHasNonExaminationsModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;
        $this->PatientHasDocumentsModel = $PatientHasDocumentsModel;
        $this->CheckListModel = $CheckListModel;
        $this->CheckListHasHeadingSectionModel = $CheckListHasHeadingSectionModel;
        $this->HeadingSectionHasQuestionModel = $HeadingSectionHasQuestionModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;
        $this->ExaminationsHasMultipleCheckListModel = $ExaminationsHasMultipleCheckListModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->PatientsHasServiceControlReminderModel = $PatientsHasServiceControlReminderModel;
        $this->OrdinationHasSpecialistModel = $OrdinationHasSpecialistModel;
        $this->EventTypeHasExaminationsModel = $EventTypeHasExaminationsModel;
        $this->AppointmentTypeHasNonExaminationsModel = $AppointmentTypeHasNonExaminationsModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->SettingsModel = $SettingsModel;
        $this->PatientsModel = $PatientsModel;
        $this->PatientHasReminder = $PatientHasReminder;
        $this->CountryCodesModel = $CountryCodesModel;

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle = 'User Profile';
        $this->ModuleView = 'web.user-profile.';
        $this->ModulePath = 'web.user-profile.';

    }

    public function showUserProfile(Request $request, $encodedPatientId, $encodedAppointmentId)
    {
        session(['sucess_msg' => '']);
        session(['chk_data' => '']);
        session(['exam_arr' => '']);
        $patientId = base64_decode($encodedPatientId);
        $appointmentId = base64_decode($encodedAppointmentId);
        $nextDay = date('Y-m-d', strtotime(date('Y-m-d') . '+1 day'));
        $currentDay = date('Y-m-d', strtotime(date('Y-m-d')));

        $checkAppointment = $this->AppointmentModel->where('id', $appointmentId)
            ->whereRaw('DATE(start_date) <= ?', [$nextDay])
            ->whereRaw('DATE(end_date) >= ?', [$currentDay])
            ->first();

        if ($checkAppointment) {
            $data = $this->BaseModel->find($patientId);
            if($data){
                $this->ViewData['patientId'] = $patientId;
                $this->ViewData['appointmentId'] = $appointmentId;
                $this->ViewData['countryCode'] = $data->country_code;
                $this->ViewData['mobileNo'] = $data->mobile_no;
                $this->ViewData['firstName'] = $data->first_name;
                $this->ViewData['family_name'] = $data->family_name;
                $this->ViewData['email'] = $data->email;
                $this->ViewData['road'] = $data->road;
                $this->ViewData['streetNo'] = $data->street_no;
                $this->ViewData['postalCode'] = $data->postal_code;
                $this->ViewData['place'] = $data->place;
                $this->ViewData['birthDate'] = $data->birth_date;
                // $this->ViewData['socialSecurityNumber'] = $data->social_security_number;
                $this->ViewData['socialSecurityNumber'] = $data->insurance_number;
                $this->ViewData['gender'] = $data->gender;
                $this->ViewData['validAppointment'] = 1;
                $this->ViewData['country'] = $data->country;

                $this->ViewData['country_codes'] = $this->CountryCodesModel
                ->where('is_active',1)
                // ->orderBy('phone_code')
                ->pluck('phone_code')
                ->toArray();

            }else{
                $this->ViewData['validAppointment'] = 0;
            }
        } else {
            $this->ViewData['validAppointment'] = 0;
            // return redirect('/');
        }
        return view($this->ModuleView . 'index', $this->ViewData);
    }

    public function updateUserProfile(UserProfileRequest $request)
    {
        Log::info("in user profile web updateUserProfile function");
        Log::info($request->all()); 

        // dd($request->all());
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg'] = __('api.PATIENT_UPDATE_FAIL');

        try {
            $errors = '';
            $patientId = $request->patient_id;

            $collection = $this->BaseModel
                ->where('id', $patientId)
                ->first();
            $request_street_no = (is_object($request) && property_exists($request, 'street_no')) ? $request->street_no : '';
            $collection_street_no = (is_object($collection) && property_exists($collection, 'street_no')) ? $collection->street_no : '';

            $street_no = ($request_street_no !== '' && !empty($request_street_no)) ? $request_street_no : ($collection_street_no !== '' && !empty($collection_street_no) ? $collection_street_no : '');

            $checkedPatientExist = $this->BaseModel
                ->whereDate('birth_date', date('Y-m-d', strtotime($request->birth_date)))
                ->where('mobile_no', $request->mobile_no)
                ->where('id', '!=', $patientId)
                ->whereNULL('deleted_at')
                ->count();

            if ($checkedPatientExist > 0) {
                $message = __('api.PATIENT_MOB_DOB_UNIQUE');
                $this->JsonData['data'] = '';
                $this->JsonData['msg'] = $message;
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                return response()->json($this->JsonData);
            }


            if (!empty($collection) && ($collection->count() > 0)) {

                $gdpr = 0;
                if (!empty($request->gdpr)) {
                    $gdpr = $request->gdpr;
                }

                $old_collection = $this->BaseModel->find($collection->id);

                $current_scanned_qrcode_appitment_id = $this->AppointmentModel
                    ->where('patient_id', $collection->id)
                    ->whereDate('start_date', date('Y-m-d'))
                    ->where('appointment_status', 'Heute')
                    ->pluck('id')
                    ->first();

                if (empty($current_scanned_qrcode_appitment_id)) {
                    $current_scanned_qrcode_appitment_id = $this->AppointmentModel
                        ->where('patient_id', $collection->id)
                        ->whereDate('start_date', date('Y-m-d'))
                        ->where('appointment_status', '')
                        ->pluck('id')
                        ->first();
                }


                $this->BaseModel->where('id', $request->patient_id)
                    ->update([
                        'first_name' => self::string_operation($request->first_name),
                        'family_name' => self::string_operation($request->family_name),
                        'email' => $request->email,
                        // 'country_code' => $request->country_code,
                        'country_code' => $request->format,
                        'mobile_no' => str_replace("-", "", ltrim($request->mobile_no, 0)),
                        'birth_date' => date('Y-m-d', strtotime($request->birth_date)),
                        'road' => self::string_operation($request->road),
                        'street_no' => self::string_operation($request->street_no),
                        'postal_code' => $request->postal_code,
                        'place' => self::string_operation($request->place),
                        'gdpr' => $request->gdpr,
                        'insurance_number' => $request->social_security_number,
                        'gender' => $request->gender,
                        'country' => $request->country,//Roshani Added  for CR #102 on 10 oct 24
                    ]);


                $new_patient_flag = $this->BaseModel->find($collection->id);

                if ($new_patient_flag->patient_status_flag == '0' && $new_patient_flag->new_flag == '0') {
                    $this->BaseModel->where('id', $collection->id)
                        ->update([
                            'new_flag' => '1'
                        ]);
                } else {
                    $this->BaseModel->where('id', $collection->id)
                        ->update([
                            'update_ganydb' => '1',
                            'patient_status_flag' => '0',
                            'new_flag' => '1'
                        ]);
                }
                // --------------------------------------------------------------------
                $new_collection = $this->BaseModel->find($collection->id);
                $ordination_patient_update = self::_updatePatientOrdination($new_collection, $old_collection);
                $oldPatient = self::_oldPatient($old_collection);
                $log_id = $collection->id;
                $collection = $collection->only(['first_name', 'family_name', 'email', 'country_code', 'mobile_no', 'birth_date', 'age', 'road', 'street_no', 'place', 'postal_code', 'gdpr', 'gender']);
                $message = __('api.PATIENT_UPDATE_SUCCESS');
                $data[] = $collection;
                $appointmentData = $this->AppointmentModel->find($request->appointment_id);
                $appointmentData->qrcode_process_status = 1;
                $appointmentData->save();

                $sessionData[0]['id'] = $appointmentData->id;
                $sessionData[0]['start_date'] = $appointmentData->start_date;
                $sessionData[0]['end_date'] = $appointmentData->end_date;
                $sessionData[0]['patient_id'] = $appointmentData->patient_id;
                $sessionData[0]['doctor_id'] = $appointmentData->doctor_id;
                $sessionData[0]['appointment_type_id'] = $appointmentData->appointment_type_id;
                // $sessionData[0]['appointment_type_name'] = $collection->assignedAppointmentType->name;
                // $sessionData[0]['patient_name'] = $patientName;
                // $sessionData[0]['doctor_name'] = $doctorName;
                // $sessionData[0]['doctor_speciality'] = $collection->assignedDoctor->doctor_speciality;
                // $sessionData[0]['doctor_image'] = $collection->assignedDoctor->img_path;
                $chk_data = base64_encode(json_encode($sessionData));
                session(['chk_data' => $chk_data]);
            } else {
                $message = __('api.PATIENT_UPDATE_FAIL');
            }
            $this->JsonData['data'] = $data;
            $this->JsonData['url'] = url('/user-profile/get-check-list');
            $this->JsonData['msg'] = $message;
            $this->JsonData['status'] = __('front.RESP_SUCCESS');

        } catch (\Exception $e) {
            DB::rollback();

            $errors = $e->getMessage();
            $this->JsonData['errors'] = $errors;
        }

        return response()->json($this->JsonData);
    }

    public function getCheckList()
    {
        Log::info("in user profile web getchecklist function");


        $generalCheckList = $getExamination = [];
        $exaination_html = $document_html = $chkexistFlag = $else_flag = NULL;
        $temp_exam = [];
        $getHtmlForPerformanceCheckList = NULL;

        $session = json_decode(base64_decode(session('chk_data'), true), true);

        log::info("user profile web before session data ");
        log::info($session);

        $patient_id = $appointment_id = null;
        if (!empty($session) && sizeof($session) > 0) {
            log::info("if session data ");
            $patient_id = $session[0]['patient_id'];
            $appointment_id = $session[0]['id'];
        } else {
            return redirect('/');
        }

        if (!empty($patient_id) && !empty($appointment_id)) {
            log::info("if patient_id and appointment_id ");
            $getAppointment = $this->AppointmentModel->find($appointment_id);
            $appointment_type_id = $getAppointment->appointment_type_id;
            $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($appointment_type_id);

            $bookedExamination = $this->AppointmentHasExaminationsModel->where('appointment_id', $appointment_id)->get();
            $bookedExamination = $bookedExamination->toArray();
            $bookedExaminationIds = array_map(function ($item) {
                return $item['examination_id'];
            }, $bookedExamination);

            // Get General Check List
            $allreadyExist = self::allreadyExist($patient_id, $appointment_id);
            //dd($allreadyExist);
            $general_checklist = $examination_flag = $performance_checklist = $service_doc = $general_doc = 0;

            if (!empty($allreadyExist) && sizeof($allreadyExist) > 0) {
                log::info("if allreadyExist ");

                if (isset($allreadyExist['general_chk']) && $allreadyExist['general_chk'] == 1) {
                    $general_checklist = 1;
                }
                if (isset($allreadyExist['examination']) && $allreadyExist['examination'] == 1) {
                    $examination_flag = 1;
                }
                if (isset($allreadyExist['performance_chk']) && $allreadyExist['performance_chk'] == 1) {
                    $performance_checklist = 1;
                }
                if (isset($allreadyExist['service_doc']) && $allreadyExist['service_doc'] == 1) {
                    $service_doc = 1;
                }
                if (isset($allreadyExist['general_doc']) && $allreadyExist['general_doc'] == 1) {
                    $general_doc = 1;
                }
            }
            // ==========================================================================
            $generalCheckList = self::getAllGeneralChecklist($patient_id, $appointment_id);
            $getExamination = self::getAllExamination($patient_id, $appointment_id);

            log::info(" user profile web getCheckList function getAllExamination data");
            log::info($getExamination);

            //    dd($generalCheckList);
            if (!empty($generalCheckList) && sizeof($generalCheckList) > 0) {
                $generalCheckList = $generalCheckList;
                $this->ViewData['type'] = 0;
                $this->ViewData['chk_type'] = 'general';
            } else if (!empty($getExamination) && sizeof($getExamination) > 0) {

                
                $excludedNonServisesCollection = [];
                foreach ($getExamination as $key => $value) {
                    if (!in_array($value['id'], $getAppointmentNonServciesIds)) {
                        $excludedNonServisesCollection[] = $value;
                        if (in_array($value['id'], $bookedExaminationIds)) {
                            $excludedNonServisesCollection[$key]['checked'] = 1;
                        }

                    }
                }
                if (!empty($excludedNonServisesCollection) && sizeof($excludedNonServisesCollection) > 0) {
                    $performanceCheckList = self::getAllPerformanceDocument($bookedExaminationIds, $patient_id, $appointment_id, 0);
                    



                    $skipBookBtn = 0;
                    if (!empty($performanceCheckList) && sizeof($performanceCheckList) > 0) {
                        $skipBookBtn = 1;
                    }
                    $exaination_html = self::examinationDiv($excludedNonServisesCollection, $skipBookBtn);

                    log::info(" user profile web getCheckList function exaination_html data");
                    log::info($exaination_html); 

                    $this->ViewData['type'] = 0;
                    $this->ViewData['exam_type'] = 1;
                    $this->ViewData['chk_type'] = 'performance';
                } else {

                    $else_flag = 1;
                    $this->ViewData['type'] = 1;
                    $this->ViewData['type'] = 1;
                }
            } else {
                return redirect('/');
            }

            if ($performance_checklist == 1 || $else_flag == 1) {
                log::info(" in performance_checklist and else flag is 1");
                $generalDocumentList = self::getAllGeneralDocument($patient_id, $appointment_id);
                if (!empty($generalDocumentList) && sizeof($generalDocumentList) > 0) {
                    $document_html = self::documentDiv($generalDocumentList);
                }
            }
            // Peromance check list 
            $exam_session = json_decode(base64_decode(session('exam_arr'), true), true);
            
            if (!empty($exam_session) && sizeof($exam_session) > 0) {
                foreach ($exam_session as $exam_key => $exam_value) {
                    $temp_exam[] = $exam_value;
                }
                $performanceCheckList = self::getAllPerformanceDocument($temp_exam, $patient_id, $appointment_id, 0);

                // dump("performanceCheckList==>");
                // dump($performanceCheckList);
                
                if (!empty($performanceCheckList) && sizeof($performanceCheckList) > 0) {
                    $getHtmlForPerformanceCheckList = self::getHtmlForPerformanceCheckList($performanceCheckList);
                }
            }

            $getAllDocumentList = self::getAllGeneralDocument($patient_id, $appointment_id);

            $this->ViewData['getHtmlForPerformanceCheckList'] = $getHtmlForPerformanceCheckList;
            $this->ViewData['general_checklist'] = $general_checklist;
            $this->ViewData['examination_flag'] = $examination_flag;


             $this->ViewData['performance_checklist'] = $performance_checklist;

             //$this->ViewData['performance_checklist'] = isset($getHtmlForPerformanceCheckList)?1:$performance_checklist;

            $this->ViewData['general_doc'] = $general_doc;
            $this->ViewData['service_doc'] = $service_doc;

            $this->ViewData['document_html'] = $document_html;
            $this->ViewData['exaination_html'] = $exaination_html;
            $this->ViewData['getAllDocumentList'] = $getAllDocumentList;
            $this->ViewData['generalCheckList'] = $generalCheckList;
            $this->ViewData['getExamination'] = $getExamination;
            $this->ViewData['moduleTitle'] = $this->ModuleTitle . ' ' . __('admin.TITLE_MANAGE_TEXT');
            $this->ViewData['moduleAction'] = $this->ModuleTitle . ' ' . __('admin.TITLE_MANAGE_TEXT');
            $this->ViewData['modulePath'] = $this->ModulePath;

            log::info(" user profile web getCheckList function ViewData end");
            log::info($this->ViewData);
            
              //dump($this->ViewData);
            return view($this->ModuleView . 'checklist', $this->ViewData);
        } else {
            return redirect('/');
        }
    }

    public function allreadyExist($patient_id, $appointment_id)
    {
        $arr_flag = $exam = [];
        $getAppointmentDetails = $this->AppointmentModel->find($appointment_id);

        $rec = $this->CheckListHasSelectedQuestionModel
            ->where('fk_patient_id', $patient_id)
            ->where('fk_appointment_id', $appointment_id)
            ->where('type', 'general')
            ->get();

        if (!empty($rec) && sizeof($rec) > 0) {
            $arr_flag['general_chk'] = 1;
        }
        // Examination
        $exam_session = json_decode(base64_decode(session('exam_arr'), true), true);
  
        if (!empty($exam_session) && sizeof($exam_session) > 0) {
            foreach ($exam_session as $exam_key => $exam_value) {
                $services = $this->AppointmentHasExaminationsModel
                    ->where('patient_id', $patient_id)
                    ->where('examination_id', $exam_value)
                    ->where('appointment_id', $appointment_id)
                    ->first();
                if (!empty($services)) {
                    $arr_flag['examination'] = 1;
                }
                // Perofrmance check list
                $rec = $this->CheckListHasSelectedQuestionModel
                    ->where('fk_patient_id', $patient_id)
                    ->where('fk_appointment_id', $appointment_id)
                    ->where('fk_examination_id', $exam_value)
                    ->where('type', 'performance')
                    ->get();
                if (!empty($rec) && sizeof($rec) > 0) {
                    $arr_flag['performance_chk'] = 1;
                }
                //service Document
                $getServiceDocument = $this->PatientHasDocumentsModel
                    ->where('appointment_id', $appointment_id)
                    ->where('patient_id', $patient_id)
                    ->where('exam_app_type_id', $getAppointmentDetails->appointment_type_id)
                    ->where('fk_examinations_id', $exam_value)
                    ->where('type', 'service')
                    ->get();
                if (!empty($getServiceDocument) && sizeof($getServiceDocument) > 0) {
                    $arr_flag['service_doc'] = 1;
                }
            }
        }
        //Document general document
        $getgeneralDocument = $this->PatientHasDocumentsModel
            ->where('appointment_id', $appointment_id)
            ->where('patient_id', $patient_id)
            ->where('exam_app_type_id', $getAppointmentDetails->appointment_type_id)
            ->where('type', 'general')
            ->get();
        if (!empty($getgeneralDocument) && sizeof($getgeneralDocument) > 0) {
            $arr_flag['general_doc'] = 1;
        }
        return $arr_flag;
    }

    public function getAllGeneralChecklist($patient_id, $appointment_id)
    {
        $errors = [];
        $data = $data_collection = [];

        $message = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status = false;

        $getcheckList = $this->CheckListModel
            ->where('type_of_checklist', 'general')
            ->where('status', 1)
            ->get();

        if (!empty($getcheckList) && sizeof($getcheckList) > 0) {
            $cnt = 0;
            foreach ($getcheckList as $chk_key => $chk_value) {
                $patientDetails = $this->BaseModel
                    ->where('id', $patient_id)
                    ->first();

                if (!empty($patientDetails)) {
                    $hasDocument = $this->CheckListHasSelectedQuestionModel
                        ->where('fk_patient_id', $patient_id)
                        //->where('fk_appointment_id',$appointment_id)
                        ->where('fk_check_list_id', $chk_value['id'])
                        ->where('type', 'general')
                        ->first();

                    if (!empty($hasDocument) && ($hasDocument->count() > 0)) {

                        $chk_id = $hasDocument->fk_check_list_id;

                        $chkList = $this->CheckListModel
                            ->where('status', 1)
                            ->find($chk_id);

                        if (!empty($chkList)) {
                            $l_date = self::checkFrequency($patient_id, $chkList, $hasDocument);

                            if (!empty($l_date)) {

                                $data[$cnt]['checklist_id'] = $chkList->id;
                                $data[$cnt]['check_list_name'] = $chkList->check_list_name;
                                $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                $data[$cnt]['final_name'] = $chkList->final_name;

                                $getHEading = self::getHeadingDetails($chkList->id);

                                $data[$cnt]['heading'] = $getHEading;
                                //start added on 17-june-25 for header footer image
                                $header_image_path    = self::getFilePath($chkList->header_image_path);
                                $footer_image_path    = self::getFilePath($chkList->footer_image_path);    
                                $data[$cnt]['header_image']        = $chkList->header_image;
                                $data[$cnt]['footer_image']        = $chkList->footer_image;
                                $data[$cnt]['header_image_path']     = (isset($chkList->header_image) && !empty($chkList->header_image))?$header_image_path:'' ;
                                $data[$cnt]['footer_image_path']        = (isset($chkList->footer_image) && !empty($chkList->footer_image))?$footer_image_path:'' ;

                                //end

                                $cnt++;
                            }
                        }
                    } else {
                        $data[$cnt]['checklist_id'] = $chk_value['id'];
                        $data[$cnt]['check_list_name'] = $chk_value['check_list_name'];
                        $data[$cnt]['introduction_text'] = $chk_value['introduction_text'];
                        $data[$cnt]['final_name'] = $chk_value['final_name'];

                        $getHEading = self::getHeadingDetails($chk_value['id']);
                        $data[$cnt]['heading'] = $getHEading;

                        //start added on 17-june-25 for header footer image
                        $header_image_path    = self::getFilePath($chk_value['header_image_path']);
                        $footer_image_path    = self::getFilePath($chk_value['footer_image_path']);
                        $data[$cnt]['header_image']    = $chk_value['header_image'];
                        $data[$cnt]['footer_image']     = $chk_value['footer_image'];
                        $data[$cnt]['header_image_path']        =(isset($chk_value['header_image']) && !empty($chk_value['header_image']))?$header_image_path:'' ;;
                        $data[$cnt]['footer_image_path']   = (isset($chk_value['footer_image']) && !empty($chk_value['footer_image']))?$footer_image_path:'' ;
                        //end

                        $cnt++;

                    }
                }
            }
        }
        return $data;
    }

    public function getHeadingDetailsWithSelected($chk_id, $examination_id, $patient_id, $appointment_id)
    {
        $getHeading = $this->CheckListHasHeadingSectionModel
            ->where('fk_check_list_id', $chk_id)
            ->get();
        $data = [];
        $cnt = 0;
        if (!empty($getHeading) && sizeof($getHeading) > 0) {
            foreach ($getHeading as $h_key => $h_value) {
                $data[$cnt]['checklist_id'] = $chk_id;
                $data[$cnt]['heading_id'] = $h_value['id'];
                $data[$cnt]['heading'] = $h_value['heading_section'];

                // questions
                $getQuesList = $this->HeadingSectionHasQuestionModel
                    ->where('fk_check_list_heading_section_id', $h_value['id'])
                    ->get();
                // already selected question by user
                $chkcollections = $this->CheckListHasSelectedQuestionModel
                    ->where('fk_patient_id', $patient_id)
                    ->where('fk_appointment_id', $appointment_id)
                    ->where('fk_examination_id', $examination_id)
                    ->where('fk_check_list_id', $chk_id)
                    ->orderBy('id', 'DESC')
                    ->first();

                $cehcklist_ids = [];
                if (!empty($chkcollections)) {
                    $selected_array = json_decode($chkcollections['questions'], true);


                    $question_id = [];
                    if (!empty($selected_array)) {
                        foreach ($selected_array as $key => $value) {

                            foreach ($value['heading'] as $i_key => $i_value) {
                                foreach ($i_value['question'] as $q_key => $q_value) {
                                    if ($q_value['question']['flag'] == 1) {
                                        $cehcklist_ids[] = $q_value['question']['question_id'];
                                    }

                                }
                            }
                        }
                    }
                }

                if (!empty($getQuesList) && sizeof($getQuesList) > 0) {
                    $i = 0;
                    foreach ($getQuesList as $q_key => $q_value) {
                        $data[$cnt]['question'][$i]['checklist_id'] = $chk_id;
                        $data[$cnt]['question'][$i]['heading_id'] = $h_value['id'];
                        $data[$cnt]['question'][$i]['question_id'] = $q_value['id'];
                        $data[$cnt]['question'][$i]['question'] = $q_value['question'];
                        if (in_array($q_value['id'], $cehcklist_ids)) {
                            $data[$cnt]['question'][$i]['flag'] = 1;
                        } else {
                            $data[$cnt]['question'][$i]['flag'] = 0;
                        }

                        $i++;
                    }
                }

                $cnt++;
            }
        }

        return $data;
    }

    public function getHeadingDetails($chk_id)
    {
        $getHeading = $this->CheckListHasHeadingSectionModel
            ->where('fk_check_list_id', $chk_id)
            ->get();
        $data = [];
        $cnt = 0;
        if (!empty($getHeading) && sizeof($getHeading) > 0) {
            foreach ($getHeading as $h_key => $h_value) {
                $data[$cnt]['checklist_id'] = $chk_id;
                $data[$cnt]['heading_id'] = $h_value['id'];
                $data[$cnt]['heading'] = $h_value['heading_section'];

                // questions
                $getQuesList = $this->HeadingSectionHasQuestionModel
                    ->where('fk_check_list_heading_section_id', $h_value['id'])
                    ->get();

                if (!empty($getQuesList) && sizeof($getQuesList) > 0) {
                    $i = 0;
                    foreach ($getQuesList as $q_key => $q_value) {
                        $data[$cnt]['question'][$i]['checklist_id'] = $chk_id;
                        $data[$cnt]['question'][$i]['heading_id'] = $h_value['id'];
                        $data[$cnt]['question'][$i]['question_id'] = $q_value['id'];
                        $data[$cnt]['question'][$i]['question'] = $q_value['question'];
                        $data[$cnt]['question'][$i]['flag'] = 0;
                        $i++;
                    }
                }

                $cnt++;
            }
        }

        return $data;
    }
    public function getAllExamination($patient_id, $appointment_id)
    {
        $data = $finalDat = [];
        $getAppointment = $this->AppointmentModel->find($appointment_id);
        if (!empty($getAppointment)) {
            $appointment_type_id = $getAppointment->appointment_type_id;


            $collections1 = $this->AppointmentTypeHasExaminationsModel
                ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
                ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')

                ->get([
                    'examinations.id',
                    'examinations.name',
                    'examinations.description'
                ]);
            log::info($collections1);

            $today_date = date("Y-m-d");

            $collections1 = $collections1->filter(function ($item) use ($patient_id, $today_date) {

                $collectionsFilter = $this->PatientsHasServiceReminderModel
                    ->select(DB::raw('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
                    ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                    ->where('patient_has_service_reminder.patient_id', $patient_id)
                    ->where('patient_has_service_reminder.status', 'activate')
                    ->where('examinations.id', $item->id)
                    ->whereRaw("date(reminder_date) <= '" . $today_date . "'")
                    ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                                                select service_id from patient_has_service_reminder 
                                                where `patient_has_service_reminder`.`patient_id`=" . $patient_id . " and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'" . $today_date . "' and `patient_has_service_reminder`.`deleted_at` is null)"))
                    ->groupBy('patient_has_service_reminder.service_id')
                    ->get();

                if (isset ($collectionsFilter) && !empty ($collectionsFilter) && $collectionsFilter->count() > 0) {

                    $collectionsFilter = $collectionsFilter->filter(function ($item) use ($patient_id, $today_date) {
                        $age_service = $this->ChannelsRemindersSettingModel
                            ->where('service_id', $item->id)
                            ->where('activated_reminder', 'age')
                            ->first();

                        $general_reminder_service = $this->ChannelsRemindersSettingModel
                            ->where('service_id', $item->id)
                            ->where('activated_reminder', 'general')
                            ->first();

                        if (!empty ($age_service)) {
                            $getPatientAge = $this->BaseModel
                                ->find($patient_id);

                            if (!empty ($getPatientAge)) {
                                $patient_age = $getPatientAge->age;

                                if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                    return $item;
                                }
                            }
                        } else if (!empty ($general_reminder_service)) {
                            $checkGenaralService = $this->PatientsHasServiceReminderModel
                                ->where('service_id', $item->id)
                                ->where('patient_id', $patient_id)
                                ->where('reminder_status', 'Set')
                                ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                ->first();
                            if (empty ($checkGenaralService))
                                return $item;
                        } else {
                            return $item;
                        }
                    });

                } else {

                    $hasReminderSet = $this->PatientsHasServiceReminderModel
                        ->where('patient_has_service_reminder.patient_id', $patient_id)
                        ->where('patient_has_service_reminder.service_id', $item->id)
                        ->first();
                    if (isset ($hasReminderSet) && !empty ($hasReminderSet)) {

                    } else {
                        return $item;
                    }

                }


            });



            $exams_ids = array_unique(array_column(array_values($collections1->toArray()), 'id'));

            $today_date = date("Y-m-d");

            //cycle>=1 and app id 0 or not 0 condition added on 23-jan-26
            $collections2 = $this->PatientsHasServiceReminderModel
                ->select(DB::raw('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
                ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')

                //added on 25-apr-25
                //cycle>=2 and app id 0 or not condition added on 23-jan-26

                ->join(
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
                           OR ((deleted_at IS  NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' and type='age' and appointment_id!=0) OR (deleted_at IS  NULL AND cycle_no >= 1 AND date(reminder_date) >= '" . $today_date . "' and type='age' and appointment_id=0)) 
                        )
                    )  GROUP BY service_id) 
                    patientremidners"),
                 
                    function ($join) {
                        $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                        $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');

                    }
                )
                //added on 25-apr-25


                ->where('patient_has_service_reminder.patient_id', $patient_id)
                ->where('patient_has_service_reminder.status', 'activate')
                ->whereNotIn('examinations.id', $exams_ids)
               // ->whereRaw("date(reminder_date) <= '" . $today_date . "'")//commented on 25-apr-25 
                ->groupBy('patient_has_service_reminder.service_id')
                ->get();

            log::info($collections2);
            $collections2 = $collections2->filter(function ($item) use ($patient_id, $today_date) {
                $age_service = $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();

                if (!empty ($age_service)) {
                    $getPatientAge = $this->BaseModel
                        ->find($patient_id);

                    if (!empty ($getPatientAge)) {
                        $patient_age = $getPatientAge->age;

                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            if ($item->reminder_status == 'executed') {
                                $checkServiceReminders = $this->PatientsHasServiceReminderModel
                                    ->where('service_id', $item->id)
                                    ->where('patient_id', $patient_id)
                                    ->where('reminder_status', 'Set')
                                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                    ->first();

                                if (empty ($checkServiceReminders))
                                    return $item;
                            } else
                                return $item;
                        }
                    }
                }

                $general_reminder_service = $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'general')
                    ->first();

                if (!empty ($general_reminder_service)) {
                    $today_date = date("Y-m-d");
                    $checkServiceReminders = $this->PatientsHasServiceReminderModel
                        ->where('service_id', $item->id)
                        ->where('patient_id', $patient_id)
                        ->where('reminder_status', 'Set')
                        ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                        ->first();
                    if (empty ($checkServiceReminders))
                        return $item;
                }

                $checkup_reminder_service = $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'checkup')
                    ->first();

                if (!empty ($checkup_reminder_service)) {

                    $today_date = date("Y-m-d");
                    $checkServiceReminders = $this->PatientsHasServiceReminderModel
                        ->where('service_id', $item->id)
                        ->where('patient_id', $patient_id)
                        ->where('reminder_status', 'Set')
                        ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                        ->first();
                    if (empty ($checkServiceReminders))
                        return $item;
                }

            });
            $getrecord = $collections1->merge($collections2);
            log::info($getrecord);
            if (!empty($getrecord) && sizeof($getrecord) > 0) {
                $cnt = 0;
                foreach ($getrecord as $key => $value) {
                    $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                    if (!empty($app_type_name)) {
                        if (ucfirst($value->name) == ucfirst($app_type_name->name)) {

                            $data[$key]['checked'] = 1;
                        } else if (empty($value->description)) {
                            $data[$key]['checked'] = 1;
                        } else {
                            $data[$key]['checked'] = 0;
                        }


                    }
                    $data[$key]['id'] = $value->id;
                    $data[$key]['name'] = ucfirst($value->name);

                    $cnt++;
                }
            }
        }
        return $data;
    }

    public function examinationDiv($getExamination, $skipBookBtn)
    {

        //Added on 17-march-25
        $backgroundColor = config('button_colors_code');
        $borderColor = config('button_colors_code');


        $str = '';
        $hiddenids = '';
        $lastIdArr = [];
        $lastId = $click = '';

        $str1 = $str2 = $str3 = $str4 = $strfinal = '';
        if (!empty($getExamination) && sizeof($getExamination) > 0) {
            foreach ($getExamination as $exam_key1 => $exam_val1) {
                $getExam = $this->ExaminationsModel->find($exam_val1['id']);
                if (isset($getExam) && !empty($getExam)) {
                    if ($exam_val1['checked'] == 0) {
                        if ($exam_val1['id']) {
                            $hiddenids .= $exam_val1['id'] . ',';
                            $lastIdArr[] = $exam_val1['id'];
                        }

                    }
                }
            }
        }

        if (isset($lastIdArr) && !empty($lastIdArr)) {
            $lastId = end($lastIdArr);
        }


        $servicesLabel = __('front.SERVICES');
        $str1 .= '<div data-toggle="collapse" data-target="#examination_div" class="card card-primary" style="width: 100%;">   
                    <div class="card-header">
                        <h3 class="card-title">' . $servicesLabel . '</h3>
                    </div>
                </div>
                <div id="examination_div" class="collapse" >
                 
                    <form id="examinationForm" role="form" data-toggle="validator" action="' . url('/user-profile/get-all-examination') . '" > ';
        $str1 .= '<input type="hidden" name="_token" id="csrf-token" value="' . csrf_token() . '" />';

        $str1 .= '<input type="hidden"  class="form-check-input" name="hidden_ids" id="hidden_ids"  value="' . rtrim($hiddenids, ",") . '"  /> <br/>
                             <input type="hidden" name="last_id" id="last_id" value="' . $lastId . '" />
                             <input type="hidden" name="skipBookBtn" id="skipBookBtn" value="' . $skipBookBtn . '" />
                        <div class="card-body">';
        $cnt = 0;
        $hiddenflag = 0;
        if (!empty($getExamination) && sizeof($getExamination) > 0) {
            foreach ($getExamination as $exam_key => $exam_val) {
                $checked = 0;
                $inputType = "checkbox";
                $getDescription = $this->ExaminationsModel->find($exam_val['id']);
                if (isset($getDescription) && !empty($getDescription)) {

                    $desc = isset($getDescription) ? $getDescription->description : '';

                    if ($exam_val['checked'] == 1) {

                        $hiddenflag++;
                        $checked = 'checked';
                        $str2 .= '<input ' . $checked . ' 
                                                                type="hidden" 
                                                                class="form-check-input" 
                                                                name="app_services[' . $exam_key . ']" 
                                                                value="' . $exam_val['id'] . '"  
                                                                >';
                    } else {
                        $cnt++;

                        $str3 .= '<fieldset>
                                                        <div class="card card-primary">
                                                        <div class="card-header">
                                                            <h3><label class="form-check-label" for="status">
                                                            ' . $exam_val['name'] . '
                                                            </label></h3>

                                                        </div>
                                                        <div class="card-body">
                                                           <div class="">
                                                               <p>' . $desc . '</p>
                                                                 
                                                            </div> 
                                                        </div>
                                                      </div>';


                        if ($lastId == $exam_val['id']) {
                            $click = 'onclick="submitExamination(this)"';
                        }

                        //commented on 17-march-25
                        // $str3 .= '<input type="button" name="book"  ' . $click . '  is_booked="1"  class="book btn btn-info" value="' . __('front.BOOK') . '" id="' . $exam_val['id'] . '" key="' . $exam_key . '"  style="background-color:#bd6f66;border-color:#bd6f66"/>
                        //                               <input type="button" name="continue"  ' . $click . '  is_continue="1"  class="continue btn btn-info" value="' . __('front.CONTINUE') . '"  id="' . $exam_val['id'] . '" key="' . $exam_key . '"   style="background-color:#bd6f66;border-color:#bd6f66"/>
                        //                           </fieldset>';


                        //changed on 17-march-25
                        $str3 .= '<input type="button" name="book"  ' . $click . '  is_booked="1"  class="book btn btn-info" value="' . __('front.BOOK') . '" id="' . $exam_val['id'] . '" key="' . $exam_key . '"  style="background-color:'.$backgroundColor.';border-color:'.$borderColor.'"/>
                                                      <input type="button" name="continue"  ' . $click . '  is_continue="1"  class="continue btn btn-info" value="' . __('front.CONTINUE') . '"  id="' . $exam_val['id'] . '" key="' . $exam_key . '"   style="background-color:'.$backgroundColor.';border-color:'.$borderColor.'"/>
                                                  </fieldset>';


                    }
                }

            }
        }
        $str4 .= '</div>';
        if ($cnt == 0 && $hiddenflag >= 0) {

             // added on 26-dec-24 for 274 issue
            $uniqueIdentifier = 'skkip-examination-button';

            //commented on 26-dec-24 for 274 issue

            // $str4 .= '<div class="card-footer" id="submitSection">
            //                 <button type="button" onclick="submitExamination(this)" id="btn_examination" class="btn btn-success" >' . __('front.TITLE_SEARCH_WEB_TEXT') . '</button>
            //                </div>';

            //changed on 26-dec-24 for 274 issue  
             $str4 .= '<div class="card-footer" id="submitSection">
                            <button type="button" onclick="submitExamination(this)" id="btn_examination" class="btn btn-success '. $uniqueIdentifier .'" >' . __('front.TITLE_SEARCH_WEB_TEXT') . '</button>
                           </div>';


        }

        $str4 .= '</form></div>';

        $strfinal = $str1 . $str2 . $str3 . $str4;

        return $strfinal;
    }

    public function getAllGeneralDocument($patient_id, $appointment_id)
    {
        $data = [];
        $doc_flag = 0;
        $getDocumentList = $this->SpecialistDocumentsModel
            ->where('type_of_document', 'general')
            ->where('status', '1')
            ->get();

        if (!empty($getDocumentList) && sizeof($getDocumentList) > 0) {
            $cnt = 0;
            foreach ($getDocumentList as $doc_key => $doc_value) {
                $patientDetails = $this->BaseModel
                    ->where('id', $patient_id)
                    ->first();

                if (!empty($patientDetails)) {
                    $hasDocument = $this->PatientHasDocumentsModel
                        ->where('patient_id', $patient_id)
                        //->where('fk_appointment_id',$appointment_id)
                        ->where('fk_document_id', $doc_value['id'])
                        ->where('type', 'general')
                        ->first();

                    if (!empty($hasDocument) && ($hasDocument->count() > 0)) {

                        //commented on 11-feb-25
                        /*
                        $l_date = self::checkDocumentFrequency($patient_id, $doc_value['id'], $hasDocument);
                        if (!empty($l_date)) {
                            $doc_flag = 1;
                            $cnt++;
                        }*/

                        //start added on 11-feb-25
                        $flag = 0;
                        $DocStatus = explode(',', $hasDocument->doc_status);
                        if (isset($DocStatus) && in_array('0', $DocStatus)) 

                        {
                            $flag = 1;
                        }
                        if($flag==1)                                            
                        {
                            // dump(" in flag=1==>");

                            $l_date = self::checkDocumentFrequency($patient_id, $doc_value['id'], $hasDocument);
                            if (!empty($l_date)) {
                                $doc_flag = 1;
                                $cnt++;
                            }
                        }else{
                             $doc_flag = 0;
                        }
                        //end added on 11-feb-25



                    } else {

                         $doc_flag = 1; //commented on 7-jan-25 for google doc not shown

                        //start added on 7-jan-25 for google doc not shown
                        // if($hasDocument->doc_status=='0'){
                               
                        //     $doc_flag = 1;
                        // }
                        //end for google doc not shown


                    }
                    if ($doc_flag == 1) {
                        $data[$cnt]['doc_id'] = $doc_value['id'];
                        $data[$cnt]['exam_id'] = null;
                        $data[$cnt]['name'] = $doc_value['name'];
                        $data[$cnt]['html_text'] = $doc_value['html_text'];
                        $data[$cnt]['background_color'] = $doc_value['background_color'];
                        $data[$cnt]['header_image'] = $doc_value['header_image'];
                        $data[$cnt]['header_image_path'] = $doc_value['header_image_path'];
                        $data[$cnt]['footer_image'] = $doc_value['footer_image'];
                        $data[$cnt]['footer_image_path'] = $doc_value['footer_image_path'];
                        $data[$cnt]['background_color'] = $doc_value['background_color'];
                        $data[$cnt]['chk_type'] = 'general';
                        $cnt++;
                    }
                }
            }
        }
        return $data;
    }

    public function generateCheckListPdf(Request $request)
    {
        //dd($request);
        $errors = [];
        $data = [];
        $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $status = false;
        $exaination_html = '';
        $getAllDocumentList = [];
        $document_html = [];
        $exam = [];
        $inputdata = $request->all();

        try {
            $session = json_decode(base64_decode(session('chk_data'), true), true);
            $patient_id = $appointment_id = null;

            if (!empty ($session) && sizeof($session) > 0) {
                $patient_id = $session[0]['patient_id'];
                $appointment_id = $session[0]['id'];
            } else {
                return redirect('/');
            }
          
            $collection = self::_createGeneralPdf($inputdata, $patient_id, $appointment_id);
          
            if ($request->chk_type == 'general') {
                $getExamination = self::getAllExamination($patient_id, $appointment_id);
                $getAppointment = $this->AppointmentModel->find($appointment_id);
                $appointment_type_id = $getAppointment->appointment_type_id;
                $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($appointment_type_id);
                $excludedNonServisesCollection = [];
                foreach ($getExamination as $key => $value) {
                    if (!in_array($value['id'], $getAppointmentNonServciesIds)) {
                        $excludedNonServisesCollection[] = $value;
                    }
                }
                //
                if (!empty ($excludedNonServisesCollection) && sizeof($excludedNonServisesCollection) > 0) {
                    $skipBookBtn = 0;
                    $exaination_html = self::examinationDiv($excludedNonServisesCollection, $skipBookBtn); 

                } else {
                    $exam_session = json_decode(base64_decode(session('exam_arr'), true), true);
                    if (!empty ($exam_session) && sizeof($exam_session) > 0) {
                        foreach ($exam_session as $exam_key => $exam_value) {
                            $get_examination = $this->ExaminationsModel->find($exam_value);
                            $exam[$exam_key] = $get_examination->id;
                        }
                    }
                    $getAllDocumentList = self::getAllDocumentList($exam, $patient_id, $appointment_id);
                    if (!empty ($getAllDocumentList) && sizeof($getAllDocumentList) > 0) {
                        $document_html = self::documentDiv($getAllDocumentList);
                    }
                }
            } else {
                $exam_session = json_decode(base64_decode(session('exam_arr'), true), true);
                if (!empty ($exam_session) && sizeof($exam_session) > 0) {
                    foreach ($exam_session as $exam_key => $exam_value) {
                        $get_examination = $this->ExaminationsModel->find($exam_value);
                        $exam[$exam_key] = $get_examination->id;
                    }
                }
                $getAllDocumentList = self::getAllDocumentList($exam, $patient_id, $appointment_id);
                if (!empty ($getAllDocumentList) && sizeof($getAllDocumentList) > 0) {
                    $document_html = self::documentDiv($getAllDocumentList);
                }

            }
        } catch (\Exception $e) {
            DB::rollback();
            $errors = $e->getMessage();
            $this->JsonData['errors'] = $errors;
        }

        if (sizeof($getAllDocumentList) > 0) {
            session(['sucess_msg' => $message]);
        }

        $redirectUrl = 'https://puregyn.at';
        $ordinationWebpage = $this->SettingsModel->where('setting_key', 'ORDINATION_WEBPAGE')->first();
        if (isset ($ordinationWebpage) && !empty ($ordinationWebpage)) {
            $setting_value = $ordinationWebpage->setting_value;
            $redirectUrl = $setting_value;
        }




        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
        $this->JsonData['exaination_html'] = $exaination_html;
        $this->JsonData['getAllDocumentList'] = $getAllDocumentList;
        $this->JsonData['url'] = $redirectUrl; 
        $this->JsonData['msg'] = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $this->JsonData['patient_id'] = $patient_id;
        $this->JsonData['appointment_id'] = $appointment_id;
        $this->JsonData['document_html'] = $document_html;
        //dd($this->JsonData);
        return response()->json($this->JsonData);
    }

    public function submitExaminationData(Request $request)
    {
        $document_html = ''; //Added on 17-march-25
        
        $generalCheckList = $generalDocumentList = [];
        $getHtmlForPerformanceCheckList = '';
        $session = json_decode(base64_decode(session('chk_data'), true), true);
        if (!empty($session) && sizeof($session) > 0) {
            $patient_id = $session[0]['patient_id'];
            $appointment_id = $session[0]['id'];
        } else {
            return redirect('/');
        }
        if (isset($request->app_services)) {
            $exam = base64_encode(json_encode($request->app_services));
            session(['exam_arr' => $exam]);
            $getAppointmentRec = $this->AppointmentModel->find($appointment_id);
            $getHiddenServices = self::getHiddenExamination($patient_id, $appointment_id);
            $appServices = array_values($request->app_services);
            $newservices = $request->app_services;
            if (!empty($getHiddenServices) && sizeof($getHiddenServices) > 0) {
                foreach ($getHiddenServices as $key => $value) {
                    if (!in_array($value, $appServices)) {
                        $newservices[$key] = $value;
                    }
                }
            }
            $request->app_services = ($newservices);

            self::_deactivateReminderNew($getAppointmentRec, $request->app_services);
            $getServises = self::_appointmentTypesAgaintsServices($appointment_id, $request, $patient_id);
            $serviceEventType = self::GetServicesEventType($appointment_id, $patient_id, $request->app_services, $getAppointmentRec->appointment_type_id, 'web');

            $performanceCheckList = self::getAllPerformanceDocument($request, $patient_id, $appointment_id, 1);
            $generalDocumentList = self::getAllDocumentList($request->app_services, $patient_id, $appointment_id);
            if (!empty($performanceCheckList) && sizeof($performanceCheckList) > 0) {
                $getHtmlForPerformanceCheckList = self::getHtmlForPerformanceCheckList($performanceCheckList);

            }

            // $getAllDocumentList = self::getAllDocumentList($exam, $patient_id, $appointment_id);
            $document_html = '';
            if (!empty($generalDocumentList) && sizeof($generalDocumentList) > 0) {
                $document_html = self::documentDiv($generalDocumentList);
            }

        }

        $redirectUrl = 'https://puregyn.at';
        $ordinationWebpage = $this->SettingsModel->where('setting_key', 'ORDINATION_WEBPAGE')->first();
        if (isset ($ordinationWebpage) && !empty ($ordinationWebpage)) {
            $setting_value = $ordinationWebpage->setting_value;
            $redirectUrl = $setting_value;
        }




        $this->JsonData['getHtmlForPerformanceCheckList'] = $getHtmlForPerformanceCheckList;
        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
        $this->JsonData['getAllDocumentList'] = $generalDocumentList;
        $this->JsonData['document_html'] = $document_html;
        // $this->JsonData['url']           =  url('/');
        // $this->JsonData['url']           = 'https://puregyn.at';

        $this->JsonData['url'] = $redirectUrl;  

        $this->JsonData['msg'] = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $this->JsonData['patient_id'] = $patient_id;
        $this->JsonData['appointment_id'] = $appointment_id;
        //dd($this->JsonData);
        //return view($this->ModuleView.'checklist', $this->ViewData);
        return response()->json($this->JsonData);
    }

    public function getHiddenExamination($patient_id, $appointment_id)
    {
        $data = $finalDat = [];
        $servicesRecommanded = array();
        $getAppointment = $this->AppointmentModel->find($appointment_id);
        if (!empty ($getAppointment)) {
            $appointment_type_id = $getAppointment->appointment_type_id;
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
                ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
                // ->whereRaw("examinations.show_as_reminder='1'")
                ->get([
                    'examinations.id',
                    'examinations.name',
                    'examinations.description'
                ]);
            $today_date = date("Y-m-d");
            $collections1 = $collections1->filter(function ($item) use ($patient_id, $today_date) {
                $age_service = $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();
                $general_reminder_service = $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'general')
                    ->first();
                if (!empty ($age_service)) {
                    $getPatientAge = $this->BaseModel
                        ->find($patient_id);

                    if (!empty ($getPatientAge)) {
                        $patient_age = $getPatientAge->age;

                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            return $item;
                        }
                    }
                } else if (!empty ($general_reminder_service)) {
                    $checkGenaralService = $this->PatientsHasServiceReminderModel
                        ->where('service_id', $item->id)
                        ->where('patient_id', $patient_id)
                        ->where('reminder_status', 'Set')
                        ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                        ->first();
                    if (empty ($checkGenaralService))
                        return $item;
                } else {
                    return $item;
                }
            });
            $exams_ids = array_unique(array_column(array_values($collections1->toArray()), 'id'));
            $collections2 = $this->PatientsHasServiceReminderModel
                ->select(DB::raw('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
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
                // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                //                     select service_id from patient_has_service_reminder 
                //                     where `patient_has_service_reminder`.`patient_id`=".$patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)")) 
                // ->where('patient_has_service_reminder.reminder_status','Set') 
                ->whereRaw("examinations.show_as_reminder='1'")
                ->groupBy('patient_has_service_reminder.service_id')
                ->get();

            $collections2 = $collections2->filter(function ($item) use ($patient_id, $today_date) {
                $age_service = $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'age')
                    ->first();

                if (!empty ($age_service)) {
                    $getPatientAge = $this->BaseModel
                        ->find($patient_id);
                    if (!empty ($getPatientAge)) {
                        $patient_age = $getPatientAge->age;

                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                            if ($item->reminder_status == 'executed') {
                                $checkServiceReminders = $this->PatientsHasServiceReminderModel
                                    ->where('service_id', $item->id)
                                    ->where('patient_id', $patient_id)
                                    ->where('reminder_status', 'Set')
                                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                    ->first();
                                //echo "<pre>";print_r($checkServiceReminders);
                                if (empty ($checkServiceReminders))
                                    return $item;
                            } else
                                return $item;
                        }
                    }
                }
                $general_reminder_service = $this->ChannelsRemindersSettingModel
                    ->where('service_id', $item->id)
                    ->where('activated_reminder', 'general')
                    ->first();

                if (!empty ($general_reminder_service)) {
                    $today_date = date("Y-m-d"); // Added by divya on 11oct22
                    $checkServiceReminders = $this->PatientsHasServiceReminderModel
                        ->where('service_id', $item->id)
                        ->where('patient_id', $patient_id)
                        ->where('reminder_status', 'Set')
                        ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                        ->first();
                    if (empty ($checkServiceReminders)) // Added on 27-oct-22
                        return $item;
                }
            });


            $getrecord = $collections1->merge($collections2);
           
            if (!empty ($getrecord) && sizeof($getrecord) > 0) {
                $cnt = 0;
                foreach ($getrecord as $key => $value) {
                    $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                    if (!empty ($app_type_name)) {
                        if (ucfirst($value->name) == ucfirst($app_type_name->name)) {
                            $data[$key]['checked'] = 1;
                            $data[$key]['id'] = $value->id;
                            $data[$key]['name'] = ucfirst($value->name);
                            $servicesRecommanded[$key] = $value->id;
                        } else if (empty ($value->description)) {
                            $data[$key]['checked'] = 1;
                            $data[$key]['id'] = $value->id;
                            $data[$key]['name'] = ucfirst($value->name);
                            $servicesRecommanded[$key] = $value->id;
                        } else {
                            $data[$key]['checked'] = 0;
                        }
                    }

                }
            }
        }
        // dd($data);
        return $servicesRecommanded;
    }

    public function _createGeneralPdf($inputdata, $patient_id, $appointment_id)
    {
        //dd($inputdata);
        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = $exam_id = '';

         $inc = 0;

        foreach ($inputdata['check_list'] as $check_list) {
            /************ Added on 26-dec-22**********/
            $imagepath = '';
            $getDatabase = DB::connection('system')->table("tenants")
                ->where('ordination_id', Config('ordination_id'))->first(['uuid']);
            
            $imagepath = url('storage/tenancy/tenants/' . $getDatabase->uuid);
            
            /************ Added on 26-dec-22**********/

            //in below query added this variables: header_image,header_image_path,footer_image,footer_image_path'

            $collections = $this->CheckListModel
                ->select('id', 'check_list_name', 'introduction_text', 'final_name', 'frequency_type', 'frequency', 'date_of_last_activation', 'header_image', 'header_image_path', 'footer_image', 'footer_image_path')
                ->where('id', $check_list['checklist_id'])
                ->where('status', 1)
                ->first();

            if (!empty ($collections)) {
                //check list details 

                $data[$cnt]['signature'] = '';
                $data[$cnt]['checklist_id'] = $collections->id;
                $data[$cnt]['check_list_name'] = $collections->check_list_name;
                $data[$cnt]['introduction_text'] = $collections->introduction_text;
                $data[$cnt]['final_name'] = $collections->final_name;
                $data[$cnt]['currentDate'] = date("m/d/Y");

                /*******Added by divya on 26-dec-22*********/
                $data[$cnt]['header_image'] = $collections->header_image;
                $data[$cnt]['header_image_path'] = $imagepath . $collections->header_image_path;
                $data[$cnt]['footer_image'] = $collections->footer_image;
                $data[$cnt]['footer_image_path'] = $imagepath . $collections->footer_image_path;

                /*******Added by divya on 26-dec-22*********/

                $statusVal=0;

                // dump($inputdata['index']);
                // dump($inc);
               
                if(isset($inputdata['index']) && $inputdata['index']==$inc){

                   // dump("inc match==>");

                    $statusVal = 1;
                }

                $inc++;



                $patientFirstName = $patientLastName = "";
                $data[$cnt]['patientFullName'] = $data[$cnt]['patientDob'] = '';
                $getPatientDetails = $this->BaseModel->where('id', $patient_id)->first();
                if (isset ($getPatientDetails) && !empty ($getPatientDetails)) {
                    $patientFirstName = isset ($getPatientDetails->first_name) ? $getPatientDetails->first_name : '';
                    $patientLastName = isset ($getPatientDetails->family_name) ? $getPatientDetails->family_name : '';
                    $data[$cnt]['patientFullName'] = $patientFirstName . ' ' . $patientLastName;
                    $data[$cnt]['patientDob'] = isset ($getPatientDetails->birth_date) ? date("d-m-Y", strtotime($getPatientDetails->birth_date)) : '';
                }

                $j = 0;
                foreach ($check_list['Heading'] as $heading) {
                    //check list heading
                    $heading_name = $this->CheckListHasHeadingSectionModel
                        ->where('id', $heading['heading_id'])->first();
                    $data[$cnt]['heading'][$j]['fk_chk_id'] = $collections->id;
                    $data[$cnt]['heading'][$j]['heading_id'] = $heading_name['id'];
                    $data[$cnt]['heading'][$j]['heading'] = $heading_name['heading_section'];

                    $k = 0;
                    foreach ($heading['question_hd'] as $key => $value) {
                        //check list question
                        $question = $this->HeadingSectionHasQuestionModel
                            ->where('id', $value)->first();

                        $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $heading_name['id'];
                        $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $question['id'];
                        $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $question['question'];
                        if (isset ($heading['question'])) {
                            if (in_array($value, $heading['question'])) {
                                $data[$cnt]['heading'][$j]['question'][$k]['question']['flag'] = 1;
                            } else {
                                $data[$cnt]['heading'][$j]['question'][$k]['question']['flag'] = 0;
                            }
                        } else {
                            $data[$cnt]['heading'][$j]['question'][$k]['question']['flag'] = 0;
                        }
                        $k++;
                    }
                    $j++;
                }

                if (!empty (Config('ordination_id'))) {
                    $getDatabaseName = DB::connection('system')
                        ->table("tenants")
                        ->where('ordination_id', Config('ordination_id'))
                        ->first(['uuid']);

                    $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/' . $getDatabaseName->uuid . '/check_list_pdf/';
                } else {
                    $PdfPath = '/opt/app-shared/php/data/storage/app/public/check_list_pdf/';
                }
                        
                $PDFname = self::createPdfFileName($patient_id, $collections['check_list_name']);
                
                $StorePath = $PdfPath . $PDFname;

                $accessPath = '/check_list_pdf/' . $PDFname;

                $pdf = app('dompdf.wrapper');
                $contxt = stream_context_create([
                    'ssl' => [
                        'verify_peer' => FALSE,
                        'verify_peer_name' => FALSE,
                        'allow_self_signed' => TRUE,
                    ]
                ]);
                
                $pdf = \PDF::setOptions(['isHTML5ParserEnabled' => true, 'isRemoteEnabled' => true]);
               
                $pdf->getDomPDF()->setHttpContext($contxt);
                $PDFPath = 'admin.pdf.checkLists';
                
                $pdf->loadView($PDFPath, compact('data'))->save($StorePath);
                $current_date = date('Y-m-d H:i:s');

                $start_date = null;
                $end_date = null;
               
                switch ($collections->frequency_type) {
                    case "day":

                        $days = (int) $collections->frequency;

                        break;
                    case "month":

                        $days = 30 * (int) $collections->frequency;

                        break;
                    case "year":

                        $days = 365 * (int) $collections->frequency;
                        break;
                }

                // -------------------------
                if (!empty ($days)) {
                    $duration = (int) $days;
                    $last_date = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +" . $duration . " day");
                    $end_date = Date('Y-m-d H:i:s', $last_date);
                    $start_date = $current_date;
                }
                
                $CheckListHasSelectedQuestionModel = $this->CheckListHasSelectedQuestionModel
                    ->where('fk_patient_id', $patient_id)
                    ->where('type', 'general')
                    ->where('fk_check_list_id', $check_list['checklist_id'])
                    ->first();

                   // dump($CheckListHasSelectedQuestionModel);

                if (!empty ($CheckListHasSelectedQuestionModel)) {   

                    $statusVar = ($statusVal==1)?1:$CheckListHasSelectedQuestionModel->status;

                   // dump("in statusVar");
                   // dump($statusVar);

                    $CheckListHasSelectedQuestionModel->fk_patient_id = $patient_id;
                    $CheckListHasSelectedQuestionModel->fk_examination_id = $check_list['exam_id'];
                    $CheckListHasSelectedQuestionModel->fk_appointment_id = $appointment_id;
                    $CheckListHasSelectedQuestionModel->fk_check_list_id = $check_list['checklist_id'];
                    $CheckListHasSelectedQuestionModel->questions = json_encode($data);
                    $CheckListHasSelectedQuestionModel->created_at = Date('Y-m-d');
                    $CheckListHasSelectedQuestionModel->check_list_flag = $flag;
                    $CheckListHasSelectedQuestionModel->pdf_name = $PDFname;
                    $CheckListHasSelectedQuestionModel->pdf_path = $accessPath;
                    $CheckListHasSelectedQuestionModel->signature = $file_name;
                    $CheckListHasSelectedQuestionModel->type = $inputdata['chk_type'];
                   // $CheckListHasSelectedQuestionModel->status = '1'; //reverted on 28-oct-24

                    $CheckListHasSelectedQuestionModel->status           = $statusVar;

                   // $CheckListHasSelectedQuestionModel->status = '0'; //changes by vijay 13/9/2024
                    $CheckListHasSelectedQuestionModel->activation_start_date = $start_date;
                    $CheckListHasSelectedQuestionModel->activation_last_date = $end_date;

                    $CheckListHasSelectedQuestionModel->save();
                } else {

                 //  dump("else ");

                    $CheckListHasSelectedQuestionModelForPerformance = $this->CheckListHasSelectedQuestionModel
                        ->where('fk_patient_id', $patient_id)
                        ->where('fk_appointment_id', $appointment_id)
                        ->where('fk_check_list_id', $check_list['checklist_id'])
                        ->where('fk_examination_id', $check_list['exam_id'])
                        ->orderBy('id','DESC')
                        ->first();
                        
                                           // dump($CheckListHasSelectedQuestionModelForPerformance);

                        
                    if (!empty ($CheckListHasSelectedQuestionModelForPerformance)) {

                         $performanceStatusVar = ($statusVal==1)?1:$CheckListHasSelectedQuestionModelForPerformance->status;



                        $CheckListHasSelectedQuestionModelForPerformance->fk_patient_id = $patient_id;
                        $CheckListHasSelectedQuestionModelForPerformance->fk_examination_id = $check_list['exam_id'];
                        $CheckListHasSelectedQuestionModelForPerformance->fk_appointment_id = $appointment_id;
                        $CheckListHasSelectedQuestionModelForPerformance->fk_check_list_id = $check_list['checklist_id'];
                        $CheckListHasSelectedQuestionModelForPerformance->questions = json_encode($data);
                        $CheckListHasSelectedQuestionModelForPerformance->created_at = Date('Y-m-d');
                        $CheckListHasSelectedQuestionModelForPerformance->check_list_flag = $flag;
                        $CheckListHasSelectedQuestionModelForPerformance->pdf_name = $PDFname;
                        $CheckListHasSelectedQuestionModelForPerformance->pdf_path = $accessPath;
                        $CheckListHasSelectedQuestionModelForPerformance->signature = $file_name;
                        $CheckListHasSelectedQuestionModelForPerformance->type = $inputdata['chk_type'];



                       // $CheckListHasSelectedQuestionModelForPerformance->status = '1'; //reverted on 28-oct-24

                        $CheckListHasSelectedQuestionModelForPerformance->status       = $performanceStatusVar;

                       // $CheckListHasSelectedQuestionModelForPerformance->status = '0'; //changes by vijay 13/9/2024
                        $CheckListHasSelectedQuestionModelForPerformance->activation_start_date = $start_date;
                        $CheckListHasSelectedQuestionModelForPerformance->activation_last_date = $end_date;

                        $CheckListHasSelectedQuestionModelForPerformance->save();
                    }else{
                        $CheckListHasSelectedQuestionModel = new $this->CheckListHasSelectedQuestionModel;

                        $CheckListHasSelectedQuestionModel->fk_patient_id = $patient_id;
                        $CheckListHasSelectedQuestionModel->fk_examination_id = $check_list['exam_id'];
                        $CheckListHasSelectedQuestionModel->fk_appointment_id = $appointment_id;
                        $CheckListHasSelectedQuestionModel->fk_check_list_id = $check_list['checklist_id'];
                        $CheckListHasSelectedQuestionModel->questions = json_encode($data);
                        $CheckListHasSelectedQuestionModel->created_at = Date('Y-m-d');
                        $CheckListHasSelectedQuestionModel->check_list_flag = $flag;
                        $CheckListHasSelectedQuestionModel->pdf_name = $PDFname;
                        $CheckListHasSelectedQuestionModel->pdf_path = $accessPath;
                        $CheckListHasSelectedQuestionModel->signature = $file_name;
                        $CheckListHasSelectedQuestionModel->type = $inputdata['chk_type'];
                        //$CheckListHasSelectedQuestionModel->status = '1'; //reverted on 28-oct-24

                         $CheckListHasSelectedQuestionModel->status     = $statusVal; 

                        //$CheckListHasSelectedQuestionModel->status = '0'; //changes by vijay 13/9/2024
                        $CheckListHasSelectedQuestionModel->activation_start_date = $start_date;
                        $CheckListHasSelectedQuestionModel->activation_last_date = $end_date;
                        $CheckListHasSelectedQuestionModel->save();
                    }
                }

                $dataFinal[] = $data;
                $data = [];
                 $statusVal=0;
            }
        }

        return $dataFinal;
    }

    public function getAllPerformanceDocument($getExamination, $patient_id, $appointment_id, $type)
    {
        //dd("--->");
        $errors = [];
        $data = $finalData = [];
        $data_collection = [];
        $message = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status = false;
        if ($type == 1) {
            $exam_arr = $getExamination->app_services;
        } else if ($type == 0) {
            $exam_arr = $getExamination;
        } else {
            $exam_arr = $getExamination->app_services;
        }
        foreach ($exam_arr as $key => $value) {
            $getMultipleCheckList = $this->ExaminationsHasMultipleCheckListModel
                ->where('fk_examinations_id', $value)
                ->get();

            if (!empty ($getMultipleCheckList) && sizeof($getMultipleCheckList) > 0) {
                $cnt = 0;
                foreach ($getMultipleCheckList as $mchk_key => $mchk_value) {
                    $getcheckList = $this->CheckListModel
                        ->where('type_of_checklist', 'performance')
                        ->where('id', $mchk_value['fk_check_list_id'])
                        ->where('status', 1)
                        ->first();

                    if (!empty ($getcheckList)) {
                        $patientDetails = $this->PatientsModel
                            ->where('id', $patient_id)
                            ->first();

                        if (!empty ($patientDetails)) {

                            //commented for 187 new flow on 22-nov-24
                           /* $data[$cnt]['checklist_id'] = $getcheckList->id;
                            $data[$cnt]['check_list_name'] = $getcheckList->check_list_name;
                            $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                            $data[$cnt]['final_name'] = $getcheckList->final_name;
                            $data[$cnt]['exam_id'] = $value;

                            $getHEading = self::getHeadingDetailsWithSelected($getcheckList->id, $value, $patient_id, $appointment_id);
                            $data[$cnt]['heading'] = $getHEading;
                            $cnt++;*/

                            //start added for 187 new flow on 22-nov-24 commented on 20-dec-24
                            /*$hasChecklistUnRead = $this->CheckListHasSelectedQuestionModel 
                                ->where('fk_patient_id',$patient_id)
                                ->where('fk_appointment_id',$appointment_id)
                                ->where('fk_check_list_id',$getcheckList->id)
                                ->where('status','0')
                                ->first();

                            if(isset($hasChecklistUnRead) && !empty($hasChecklistUnRead)){

                                $data[$cnt]['checklist_id'] = $getcheckList->id;
                                $data[$cnt]['check_list_name'] = $getcheckList->check_list_name;
                                $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                                $data[$cnt]['final_name'] = $getcheckList->final_name;
                                $data[$cnt]['exam_id'] = $value;

                                $getHEading = self::getHeadingDetailsWithSelected($getcheckList->id, $value, $patient_id, $appointment_id);
                                $data[$cnt]['heading'] = $getHEading;
                                 $cnt++;
                            }//if */
                            //end added for 187 new flow on 22-nov-24

                            /***start*did changes for 268 issue*on 23-dec-24***********/
                            $hasChecklistUnRead = $this->CheckListHasSelectedQuestionModel 
                                ->where('fk_patient_id',$patient_id)
                                ->where('fk_appointment_id',$appointment_id)
                                ->where('fk_check_list_id',$getcheckList->id)
                                ->first();

                                //dump($hasChecklistUnRead);

                             if(isset($hasChecklistUnRead) && !empty($hasChecklistUnRead)){

                                if($hasChecklistUnRead['status']=='0')
                                {
                                    $data[$cnt]['checklist_id'] = $getcheckList->id;
                                    $data[$cnt]['check_list_name'] = $getcheckList->check_list_name;
                                    $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                                    $data[$cnt]['final_name'] = $getcheckList->final_name;
                                    $data[$cnt]['exam_id'] = $value;

                                    $getHEading = self::getHeadingDetailsWithSelected($getcheckList->id, $value, $patient_id, $appointment_id);
                                    $data[$cnt]['heading'] = $getHEading;

                                     //start added on 17-june for checklist header footer
                                     $header_image_path    = self::getFilePath($getcheckList->header_image_path);
                                    $footer_image_path    = self::getFilePath($getcheckList->footer_image_path);

                                     $data[$cnt]['header_image']        = $getcheckList->header_image;
                                    $data[$cnt]['footer_image']        = $getcheckList->footer_image;
                                    $data[$cnt]['header_image_path']     = (isset($getcheckList->header_image) && !empty($getcheckList->header_image))?$header_image_path:'' ;
                                    $data[$cnt]['footer_image_path']        = (isset($getcheckList->footer_image) && !empty($getcheckList->footer_image))?$footer_image_path:'' ;
                                    //end 
                                }
                                
                            }else{
                                    $data[$cnt]['checklist_id'] = $getcheckList->id;
                                    $data[$cnt]['check_list_name'] = $getcheckList->check_list_name;
                                    $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                                    $data[$cnt]['final_name'] = $getcheckList->final_name;
                                    $data[$cnt]['exam_id'] = $value;

                                    $getHEading = self::getHeadingDetailsWithSelected($getcheckList->id, $value, $patient_id, $appointment_id);
                                    $data[$cnt]['heading'] = $getHEading;

                                     //start added on 17-june for checklist header footer
                                    $header_image_path    = self::getFilePath($getcheckList->header_image_path);
                                    $footer_image_path    = self::getFilePath($getcheckList->footer_image_path);

                                    $data[$cnt]['header_image']        = $getcheckList->header_image;
                                    $data[$cnt]['footer_image']        = $getcheckList->footer_image;
                                    $data[$cnt]['header_image_path']     = (isset($getcheckList->header_image) && !empty($getcheckList->header_image))?$header_image_path:'' ;
                                    $data[$cnt]['footer_image_path']        = (isset($getcheckList->footer_image) && !empty($getcheckList->footer_image))?$footer_image_path:'' ;
                                    //end
                            }


                            /****end*did changes for 268 issue**on 23-dec-24***********/
                             $cnt++;
                        }
                    }
                }

                if (empty ($data_collection)) {
                    $data_collection = $data;
                    $data = [];
                }

            }
        }

        $finalData = array_merge($data_collection, $data);

        return $finalData;
    }

    public function getAllDocumentList($app_services, $patient_id, $appointment_id)
    {
        $generalDocumentList = $serviceDocumentList = [];
        //GENERAL DOCUMENT
        $generalDocumentList = self::getAllGeneralDocument($patient_id, $appointment_id);
        // SERVICE DOCUMENT
        if (!empty ($app_services) && sizeof($app_services) > 0) {
            $serviceDocumentList = self::getAllServicesDocument($app_services, $patient_id, $appointment_id, 1);
            // view file with data
        }

        $finalData = array_merge($generalDocumentList, $serviceDocumentList);
        //dd($serviceDocumentList);
        return $finalData;
    }

    public function getAllServicesDocument($getExamination, $patient_id, $appointment_id, $type)
    {
        $errors = [];
        $data = $finalData = $data_collection = [];
        $data_collection = null;
        $message = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status = false;
        if ($type == 0) {
            $ex_arr = $getExamination->exam;
        } else {
            $ex_arr = $getExamination;
        }
        //dd($ex_arr)
        foreach ($ex_arr as $key => $value) {
            $getMultipleDocumentList = $this->ExaminationsHasMultipleDocumentListModel
                ->where('fk_examinations_id', $value)
                ->get();
            //dd($getMultipleDocumentList,$value);
            if (!empty ($getMultipleDocumentList) && sizeof($getMultipleDocumentList) > 0) {
                $cnt = 0;
                foreach ($getMultipleDocumentList as $mdoc_key => $mdoc_value) {
                    $getDocumentList = $this->SpecialistDocumentsModel
                        ->where('type_of_document', 'service')
                        ->where('id', $mdoc_value['fk_document_list_id'])
                        ->where('status', '1')
                        ->first();
                    //dd($getDocumentList);
                    if (!empty ($getDocumentList)) {
                        $patientDetails = $this->PatientsModel
                            ->where('id', $patient_id)
                            ->first();

                        if (!empty ($patientDetails)) {

                            //commented below code on 1-oct-24 for #187 to do not show document again //reverted on 28-oct-24
                            
                             //commented below code on 15-nov-24 for #187 to do not show document again 
                           /* $data[$cnt]['doc_id'] = $getDocumentList->id;
                            $data[$cnt]['exam_id'] = $value;
                            $data[$cnt]['name'] = $getDocumentList->name;
                            $data[$cnt]['html_text'] = $getDocumentList->html_text;
                            $data[$cnt]['background_color'] = $getDocumentList->background_color;
                            $data[$cnt]['header_image'] = $getDocumentList->header_image;
                            $data[$cnt]['header_image_path'] = $getDocumentList->header_image_path;
                            $data[$cnt]['footer_image'] = $getDocumentList->footer_image;
                            $data[$cnt]['footer_image_path'] = $getDocumentList->footer_image_path;
                            $data[$cnt]['background_color'] = $getDocumentList->background_color;
                            $data[$cnt]['chk_type'] = 'service';*/

                            //start added below code on 1-oct-24 for #183 to do not show document again 
                            // commented on 28-oct-24 for revert
                             // Uncommented on 15-nov-24 for revert

                            $existRec = $this->PatientHasDocumentsModel
                             ->where('type','service')
                             ->where('patient_id',$patient_id)
                             ->where('appointment_id',$appointment_id)
                             ->where('fk_document_id',$getDocumentList->id)
                             ->orderBy('id','desc')
                             ->first();

                             Log::info($patient_id);
                             Log::info($appointment_id);
                             Log::info($getDocumentList->id);
                             Log::info("in existRec..");
                             Log::info($existRec);

                             $flag = 0;
                             if(isset($existRec))
                             {
                                $DocStatus = explode(',', $existRec->doc_status);
                                Log::info($DocStatus);
                               
                                if(in_array('0', $DocStatus))
                                {
                                    $flag = 1;
                                }
                             }//
                            
                            if($flag==1){
                                $data[$cnt]['doc_id'] = $getDocumentList->id;
                                $data[$cnt]['exam_id'] = $value;
                                $data[$cnt]['name'] = $getDocumentList->name;
                                $data[$cnt]['html_text'] = $getDocumentList->html_text;
                                $data[$cnt]['background_color'] = $getDocumentList->background_color;
                                $data[$cnt]['header_image'] = $getDocumentList->header_image;
                                $data[$cnt]['header_image_path'] = $getDocumentList->header_image_path;
                                $data[$cnt]['footer_image'] = $getDocumentList->footer_image;
                                $data[$cnt]['footer_image_path'] = $getDocumentList->footer_image_path;
                                $data[$cnt]['background_color'] = $getDocumentList->background_color;
                                $data[$cnt]['chk_type'] = 'service';
                            }//if flag is 0 means if unread then only show 
                            //end added below code on 1-oct-24 for #183 to do not show document again
                           

                            Log::info($data);
                            $cnt++;
                        }
                    }
                }
                if (empty ($data_collection)) {
                    $data_collection = $data;
                    $data = [];
                }
            }
        }

        if (!empty ($data_collection)) {
            $finalData = array_merge($data_collection, $data);
        }

        return $finalData;
    }

    public function checkFrequency($patient_id, $getCheckList, $value)
    {
        $data = [];
        $flag = 0;
        $l_date = '';
        $chk_activation_date = date('Y-m-d h:i:s', strtotime($getCheckList->date_of_last_activation));
        // ----------------------------------------------------------
        $current_date = date('Y-m-d h:i:s');
        $start_date = Date('Y-m-d  h:i:s', strtotime($value->activation_start_date));
        $end_date = Date('Y-m-d  h:i:s', strtotime($value->activation_last_date));

        if (!empty ($getCheckList)) {
            $days = null;
            if (strtotime($chk_activation_date) > strtotime($start_date)) {
                $flag = 1;
            } else if (strtotime($current_date) > strtotime($end_date)) {
                $flag = 1;
            }

            if ($flag == 1) {
                switch ($getCheckList->frequency_type) {
                    case "day":
                        $days = (int) $getCheckList->frequency;
                        break;
                    case "month":
                        $days = 30 * (int) $getCheckList->frequency;
                        break;
                    case "year":
                        $days = 365 * (int) $getCheckList->frequency;
                        break;
                }
                if (!empty ($days)) {
                    $duration = (int) $days;
                    $last_date = strtotime(date("Y-m-d h:i:s", strtotime($current_date)) . " +" . $duration . " day");
                    $l_date = Date('Y-m-d h:i:s', $last_date);
                }
            }
        }
        return $l_date;
    }

    public function getHtmlForPerformanceCheckList($generalCheckList)
    {
        //dd($generalCheckList);
        $str = '';
        $str .= '<div data-toggle="collapse" data-target="#performance_div" class="card card-primary" style="width: 100%;">   
                    <div class="card-header">
                        <h3 class="card-title">' . __('front.TITLE_PERFORMANCE_CHECK_LIST') . '</h3>
                    </div>
                </div>
                <div id="performance_div" class="collapse">';
        $chk_counter = 0;
        if (isset ($generalCheckList) && sizeof($generalCheckList) > 0) {

            //style="background:#fff" style added on 17-june-25 for header footer 

            $str .= '<form id="performancecheckListForm" role="form" data-toggle="validator" action="' . url('/user-profile/generate-check-listPdf') . '">
                        <input type="hidden" name="chk_type" id="chk_type" value="performance">
                        <div class="slideshow-container" style="background:#fff">';
            foreach ($generalCheckList as $key => $value) {
                if (isset ($value['exam_id'])) {
                    $exam_id = $value['exam_id'];
                }

                $sty = 'display:none';
                if ($key == 0) {
                    $sty = 'display:block';
                }



                $str .= '<div id="checklist-data" data-count="' . count($generalCheckList) . '" style="display: none;"></div>';

                $str .= '<div class="myPerformanceSlides" style="' . $sty . '">';

                 //start added on 17-june-25 for header footer
                if(isset($value['header_image_path']) && !empty($value['header_image_path'])) 
                {
                     $header_image_path = $value['header_image_path'];
                   $str .= '<img style="max-width: 100%;margin-bottom:40px;" src="'.$header_image_path.'" alt="'.$value['header_image'].'">';
                }
                 //end added on 17-june-25 for header footer

                               $str .= ' <div class="row">
                                            <div class="col-md-5" style="text-align:left;">
                                                <!-- Check list name -->
                                                <h2>
                                                <input type="hidden" name="check_list[' . $chk_counter . '][exam_id]" value="' . $exam_id . '">
                                                <input type="hidden" name="check_list[' . $chk_counter . '][checklist_id]" value="' . $value['checklist_id'] . '">
                                                ' . $value['check_list_name'] . '
                                                </h2>
                                                <hr>
                                                <!-- check list introduction_text -->
                                                <h6> 
                                                 ' . strip_tags($value['introduction_text']) . '
                                                </h6>
                                                <hr>
                                                <!-- check list final_name -->
                                                <h6> 
                                                 ' . strip_tags($value['final_name']) . '
                                                </h6>
                                            </div>
                                            <div class="col-md-1">
                                                &nbsp;
                                            </div>
                                            <div class="col-md-6" style="text-align:left;">';
                $h_cnt = 0;
                foreach ($value['heading'] as $hd_key => $hd_value) {
                    $str .= '<div class="col-sm-12"> 
                                                                <div class="p-0 form-group"> 
                                                                  <h4>
                                                                    <input type="hidden" name="check_list[' . $chk_counter . '][Heading][' . $h_cnt . '][heading_id]" value="' . $hd_value['heading_id'] . '">
                                                                    ' . $hd_value['heading'] . '
                                                                  </h4> 
                                                                </div>
                                                            </div>';
                    $q_cnt = 0;
                    foreach ($hd_value['question'] as $qs_key => $qs_value) {
                        $checked = ($qs_value['flag'] == 1) ? 'checked' : '';
                        $str .= '<div class="row">
                                                                            <div class="col-sm-12"> 
                                                                                <div class="p-0 form-group"> 
                                                                                    <div class="form-check" style="margin-left: 5px;">
                                                                                          <input type="hidden" name="check_list[' . $chk_counter . '][Heading][' . $h_cnt . '][question_hd][' . $q_cnt . ']" value="' . $qs_value['question_id'] . '">
                                                                                          <input 
                                                                                            type="checkbox" 
                                                                                            class="form-check-input" 
                                                                                            name="check_list[' . $chk_counter . '][Heading][' . $h_cnt . '][question][' . $q_cnt . ']" 
                                                                                            value="' . $qs_value['question_id'] . '" 
                                                                                             ' . $checked . '
                                                                                            >
                                                                                          <label class="form-check-label" for="status">
                                                                                           ' . $qs_value['question'] . '
                                                                                          </label>
                                                                                    </div>  
                                                                                </div>
                                                                            </div>
                                                                        </div>';
                        $q_cnt++;
                    }
                    $str .= '<hr>';
                    $h_cnt++;
                }

                $str .= '  </div>
                                            </div>';

                //start added on 17-june-25 for header footer
                if(isset($value['footer_image_path']) && !empty($value['footer_image_path'])) 
                {
                    $footer_image_path = $value['footer_image_path']; 

                   $str .= '<img style="max-width: 100%;" src="'.$footer_image_path.'" alt="'.$value['footer_image'].'">';
                }
                //end added on 17-june-25 for header footer                           

                if ($key != count($generalCheckList) - 1) {
                    // $str .= '<div class="col-lg-12 text-center" style="margin-top: 20px;">
                    //                                         <input class="btn btn-success" type="button" onclick="plusPerformanceSlides(1)" value="Bestätigen">
                    //                                     </div>';


                     $str .= '<div class="col-lg-12 text-center cfooter" style="margin-top: 20px;">
                     <input class="btn btn-success" type="button" onclick="submitPerformanceChecklist(this, ' . $chk_counter . ')" value="Bestätigen">
                  </div>';

          
                } else {
                    // $str .= '<div class="col-lg-12 text-center" style="margin-top: 20px;">
                    //                               <input class="btn btn-success" onclick="submitPerformanceFrm(this)" id="btn-sub" type="button" onclick="plusPerformanceSlides(1)" value="Bestätigen">
                    //                             </div>';


                    $str .='<div class="col-lg-12 text-center cfooter" style="margin-top: 20px;">
                                                  <input class="btn btn-success" onclick="submitPerformanceFrm(this, ' . $chk_counter . ')" id="btn-sub" type="button" onclick="plusPerformanceSlides(1)" value="Bestätigen">
                                                </div>';


                }
                $str .= '</div>';
                $chk_counter++;
            }
            $str .= '<a class="prev" onclick="plusPerformanceSlides(-1)">❮</a>
                                        <a class="next" onclick="plusPerformanceSlides(1)">❯</a>
                                </div>';
            // <!-- DOT -->
            // <div class="dot-container">';
            //     $dot_counter = 1;
            //     foreach ($generalCheckList as $dot_key => $dot_value)
            //     {
            //       // $str .='<span class="dot" onclick="currentPerformanceSlide('.$dot_counter.')"></span>';
            //       $dot_counter++;
            //     } 
            // $str .='</div>  
            $str .= '</form>';
        }
        $str .= '</div>';
        //dd($str);                
        return $str;
    }

    //Roshani made change this function for 178 on 18-08-2024
    public function documentDiv($generalDocumentList)
    {
        $str = '';
        $str .= '<div data-toggle="collapse" data-target="#document" class="card card-primary" style="width: 100%;">   
        <div class="card-header">
            <h3 class="card-title">' . __('front.Document') . '</h3>
        </div>
      </div>
      <div id="document" class="collapse show" style="display:block">
        <form id="frmDocument" method="post" data-toggle="validator" action="' . url('/user-profile/generate-Document-listPdf') . '">
                <input type="hidden" name="_token" value="' . csrf_token() . '">
                <div class="card card-primary" style="width: 100%;">   
                    <div class="card-body">';
        if (!empty($generalDocumentList) && sizeof($generalDocumentList) > 0) {
                        $cnt = 1;
                        foreach ($generalDocumentList as $doc_key => $doc_val) {

                            if (sizeof($generalDocumentList) <= 1) {
                                $getDocumentList = $this->SpecialistDocumentsModel->find($doc_val["doc_id"]);
                                if (!empty($getDocumentList)) {
                                    $header_image_path = self::getFilePath($getDocumentList->header_image_path);
                                    $footer_image_path = self::getFilePath($getDocumentList->footer_image_path);
                                    $str .= '<div class="row">
                                        <input type="hidden" name="hd_doc_id" id="hd_doc_id" value="' . $doc_val["doc_id"] . '">
                                        <input type="hidden" name="doc_hd[]" value="' . $doc_val["doc_id"] . '">
                                        <input type="hidden" name="exam_id[]" id="exam_id" value="' . $doc_val["exam_id"] . '">
                                        <input type="hidden" name="doc_type[]" id="doc_type" value="' . $doc_val["chk_type"] . '">
                                        <input 
                                            onclick="getDocument(' . $doc_val["doc_id"] . ')" 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            name="doc[]" 
                                            checked 
                                            value="' . $doc_val["doc_id"] . '" 
                                            style="display: none;"
                                        >
                                        ';

                                        
                                        if (isset($getDocumentList->header_image_path) && !empty($getDocumentList->header_image_path)) {

                                            //commented on 14-aug-25
                                            // $str .= '<img style="width: 100%;height: 180px;" src="' . $header_image_path . '" alt="' . $getDocumentList->header_image . '">';

                                            //added on 14-aug-25
                                            $str .= '<img style="max-width: 100%;" src="' . $header_image_path . '" alt="' . $getDocumentList->header_image . '">';
                                        }
                                        
                                        $str .= '</div>
                                        <div class="row" style="height: auto;background-color:' . $getDocumentList->background_color . '" >
                                          <div class="col-sm-12" style="margin-top: 25px"> 
                                            <div class="p-0 form-group"> 
                                                <h4>
                                                  ' . $getDocumentList->name . '
                                                </h4>
                                            </div>
                                          </div>
                                          <div class="col-sm-12"> 
                                            <div class="p-0 form-group"> 
                                                <h6>' . $getDocumentList->html_text . '</h6>
                                            </div>
                                          </div>
                                        </div>
                                        <div class="row">';
                                        
                                        if (isset($getDocumentList->footer_image_path) && !empty($getDocumentList->footer_image_path)) {

                                            //commented on 14-aug-25
                                            // $str .= '<img style="width: 100%;height: 100px;" src="' . $footer_image_path . '" alt="' . $getDocumentList->footer_image . '">';

                                            //added on 14-aug-25
                                             $str .= '<img style="max-width: 100%;" src="' . $footer_image_path . '" alt="' . $getDocumentList->footer_image . '">';
                                        }
                                        
                                    $str .= '</div>';
                                }
                            } else {
                                $str .= '<div class="row">
                                  <div class="col-sm-12"> 
                                      <div class="p-0 form-group"> 
                                          <div class="form-check" style="margin-left: 5px;">
                                                <input type="hidden" name="doc_hd[]" value="' . $doc_val["doc_id"] . '">
                                                <input type="hidden" name="exam_id[]" id="exam_id" value="' . $doc_val["exam_id"] . '">
                                                <input type="hidden" name="doc_type[]" id="doc_type" value="' . $doc_val["chk_type"] . '">
                                               <input 
                                                    onclick="getDocument(' . $doc_val["doc_id"] . ')" 
                                                    type="checkbox" 
                                                    class="form-check-input" 
                                                    name="doc[]" 
                                                    value="' . $doc_val["doc_id"] . '" 
                                                >
                                                <label class="form-check-label" for="status">
                                                  ' . ucfirst($doc_val['name']) . '
                                                </label>
                                                
                                          </div>  
                                      </div>
                                      <hr>
                                  </div>
                               
                                </div>';
                                $cnt++;
                            }
                        }
                    }
        $str .= '</div><!-- /.card-body -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success" onclick="getDoctorTimeFrames()">' . __('front.TITLE_SEARCH_WEB_TEXT_CHANGE') . '</button>
                    </div>
                </div>
        </form>
      </div>';

        return $str;
    }
    //Roshani made change this function for 178 on 18-08-2024

    public function generate_single_document(Request $request)
    {
        //dd($request->all());
        $getDocumentList = $this->SpecialistDocumentsModel->find($request->doc_id);
        if (!empty($getDocumentList)) {
            $header_image_path = self::getFilePath($getDocumentList->header_image_path);
            $footer_image_path = self::getFilePath($getDocumentList->footer_image_path);
            $str = '<div class="row" >
                <input type="hidden" name="hd_doc_id" id="hd_doc_id" value="' . $request->doc_id . '">';
            if (isset($header_image_path) && !empty($header_image_path)) {

                //commented for #270 issue
                // $str .= '<img style="width: 100%;height: 180px;" src="' . $header_image_path . '" alt="' . $getDocumentList->header_image . '">';

                //changed for #270 issue
                $str .= '<img style="max-width: 100%;" src="' . $header_image_path . '" alt="' . $getDocumentList->header_image . '">'; 
            }

            //Removed width on 16-june-25 
            // $str .= '</div>
            //     <div class="row" style="width: 103%;height: auto;background-color:' . $getDocumentList->background_color . '" >

             // <label>' . $getDocumentList->html_text . '</label> this line changed on 17-june-25 below for document wider issue 

             $str .= '</div>
                <div class="row" style="height: auto;background-color:' . $getDocumentList->background_color . '" >


                  <div class="col-sm-12" style="margin-top: 25px"> 
                    <div class="p-0 form-group"> 
                        <h4>
                          ' . $getDocumentList->name . '
                        </h4>
                    </div>
                  </div>
                  <div class="col-sm-12"> 
                    <div class="p-0 form-group"> 
                        <h6>' . $getDocumentList->html_text . '</h6>
                    </div>
                  </div>
                </div>
                <div class="row" >';
            if (isset($footer_image_path) && !empty($footer_image_path)) {    

                //commented for #270 issue
                // $str .= '<img style="width: 100%;height: 100px;" src="' . $footer_image_path . '" alt="' . $getDocumentList->footer_image . '">';

                //changed for #270 issue
                $str .= '<img style="max-width: 100%;" src="' . $footer_image_path . '" alt="' . $getDocumentList->footer_image . '">';
            }
            $str .= '</div>';
        }
        return $str;
    } 

    public function generateDocumentListPdf(Request $request)
    {
        $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $exam_data = $generalDocumentList = [];
        $session = json_decode(base64_decode(session('chk_data'),true),true);
        //dd($session);
        $patient_id = $appointment_id = null ; 
        if(!empty($session) && sizeof($session)>0)
        {
          $patient_id     = $session[0]['patient_id'];
          $appointment_id = $session[0]['id'];
        }
        else
        {
            return redirect('/');     
        }

        // $collection = self::_createGeneralDocumentPdf($request,$patient_id,$appointment_id); //commented for #277 issue on 24-dec-24
        $collection = self::_createGeneralDocumentPdfUserProfile($request,$patient_id,$appointment_id);//added for #277 issue on 24-dec-24 

        // Performance check list
        //dd($request->type);
        if($request->type == 'general')
        {
            $exam_session = json_decode(base64_decode(session('exam_arr'),true),true);
            if(!empty($exam_session) && sizeof($exam_session)>0)
            {
                
                $generalDocumentList = self::getAllServicesDocument($exam_session,$patient_id,$appointment_id,1);
               
                if(sizeof($generalDocumentList)>0)
                {
                    $this->ViewData['generalDocumentList']   = $generalDocumentList;
                    $this->ViewData['type']       = '';
                    $this->ViewData['chk_type']   = 'service';
                    $this->ViewData['moduleTitle']        = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
                    $this->ViewData['moduleAction']       = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
                    $this->ViewData['modulePath']         = $this->ModulePath;
                    return view($this->ModuleView.'document-list', $this->ViewData);
                }
                else
                {
                    session(['sucess_msg' =>$message]);
                    return redirect('/');  
                }
                // view file with data
                
            }
            else
            {
                return redirect('/');     
            }
            $this->ViewData['type']              = 'service';
            $this->ViewData['chk_type']          = 'service';
            $this->ViewData['generalDocumentList']   = $generalDocumentList;
            $this->ViewData['getExamination']   = $exam_data;
            $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
            $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
            $this->ViewData['modulePath']   = $this->ModulePath;
      
            return view($this->ModuleView.'document-list', $this->ViewData);
            
        }
        else
        {
            // return redirect(url('/online-appointment/getDocument')); 
            $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
           

            session(['sucess_msg' =>$message]);
            return redirect('/');
            // return redirect(url('puregyn.at')); 
            
        }

    }
}
