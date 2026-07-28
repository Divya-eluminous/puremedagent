<?php
namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 

// Models
use App\Models\PatientsModel; 
use App\Models\AdminUserModel;
use App\Models\AppointmentTypesModel; 
use App\Models\AppointmentModel;
use App\Models\SettingsModel;
use App\Models\RosterModel; 
use App\Models\WeekDaysModel;
use App\Models\PatientHasDocumentsModel;
use App\Models\AppointmentHasNotificationModel;
use App\Models\RosterHasDatesModel;
use App\Models\CheckListModel; 
use App\Models\CheckListHasHeadingSectionModel; 
use App\Models\HeadingSectionHasQuestionModel;
use App\Models\CheckListHasSelectedQuestionModel; 
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\ExaminationsModel;
use App\Models\SpecialistDocumentsModel;
use App\Models\AppointmentHasExaminationsModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\PatientsHasServiceControlReminderModel;
use App\Models\OrdinationHasSpecialistModel;
use PDF;
// Request
use App\Http\Requests\Web\RegisterPatientRequest;

use WebSmsCom_Client;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_TextMessage;
use WebSmsCom_ParameterValidationException; 
use WebSmsCom_AuthorizationFailedException;
use WebSmsCom_ApiException;
use WebSmsCom_HttpConnectionException;
use WebSmsCom_UnknownResponseException;

//Mail
use App\Mail\AppointmentMail; 
use App\Mail\FailedAppointmentMail; 
use App\Models\OrdinationsModel;


// Request
// use App\Http\Requests\Admin\PatientsRequest;
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
use datetime;
use Response;

class AppointmentWebController extends Controller
{
    private $BaseModel;
    use GeneralTrait;

    public function __construct(
        PatientsModel $PatientsModel,
        AdminUserModel $AdminUserModel,
        AppointmentTypesModel $AppointmentTypesModel,
        AppointmentModel $AppointmentModel,
        RosterModel $RosterModel,
        WeekDaysModel $WeekDaysModel,
        SettingsModel $SettingsModel,
        PatientHasDocumentsModel $PatientHasDocumentsModel,
        AppointmentHasNotificationModel $AppointmentHasNotificationModel,
        RosterHasDatesModel $RosterHasDatesModel,
        CheckListModel $CheckListModel,
        CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
        HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
        CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
        ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
        ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        ExaminationsModel $ExaminationsModel,
        SpecialistDocumentsModel $SpecialistDocumentsModel,
        AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
        OrdinationsModel $OrdinationsModel,
        PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
        PatientsHasServiceControlReminderModel $PatientsHasServiceControlReminderModel,
        OrdinationHasSpecialistModel $OrdinationHasSpecialistModel

     
    )
    {
        $this->BaseModel = $AppointmentModel;
        $this->OrdinationsModel   = $OrdinationsModel;
        $this->PatientsModel            = $PatientsModel;
        $this->AdminUserModel           = $AdminUserModel;
        $this->AppointmentTypesModel    = $AppointmentTypesModel;
        $this->RosterModel              = $RosterModel;
        $this->WeekDaysModel            = $WeekDaysModel;
        $this->PatientHasDocumentsModel = $PatientHasDocumentsModel;
        $this->AppointmentHasNotificationModel = $AppointmentHasNotificationModel;
        $this->SettingsModel = $SettingsModel; 
        $this->RosterHasDatesModel = $RosterHasDatesModel;
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

        $this->ViewData = [];
        $this->JsonData = []; 

        $this->ModuleTitle  =  'Appointments';
        $this->ModuleView   = 'web.appointment.';
        $this->ModulePath   = 'web.appointment.';

        // Permission Middleware
        // $this->middleware(['permission:patients-listing'], ['only' => ['index','getRecords']]);
        // $this->middleware(['permission:patients-add'], ['only' => ['create','store']]);
    }

    /*---------------------------------
    |   Home
    ------------------------------------------*/

    public function home()
    {
        $redirect = 1;
        if(!empty(Config('website_id')))
        {
            $is_speciality = $this->OrdinationHasSpecialistModel->where('ordination_id',Config('ordination_id'))->whereNull('deleted_at')->count();
            if($is_speciality > 0)
            {
                 $redirect = 0;
            }
        }   

        if($redirect == 1)
        {
              return redirect('admin/login');
        }

        $session_msg = session('sucess_msg');
        $getmsg = '';
        if(!empty($session_msg))
        {
            $getmsg = $session_msg;
            session(['sucess_msg' =>'']);
            session(['chk_data' =>'']);
            session(['exam_arr' =>'']);
        }
        $doctors = $this->AdminUserModel
                    ->where('users.status', 1)
                    ->whereHas('roles',function($query){
                        $query->where('name', 'doctor');
                    })
                    ->get();

        $ordination_logo = '';
        if(!empty(Config('ordination_id')))
        {
            $getLogo = $this->OrdinationsModel->find(config('ordination_id')); 
            if(!empty($getLogo->logo_path))
            {
                $ordination_logo = self::getFilePath($getLogo->logo_path);
            }
            Session::put('ordination_logo',$ordination_logo);
            //dd(Session::get('ordination_logo'));
        }  

        $this->ViewData['doctors'] = $doctors;
        $this->ViewData['getmsg'] = $getmsg; 

        return view($this->ModulePath.'home', $this->ViewData);
    }

    public function index($enc_doctor_id=false) 
    { 
        $doctor_id = '';
        if(!empty($enc_doctor_id)){
            $doctor_id = base64_decode(base64_decode($enc_doctor_id));
            $doctors = $this->AdminUserModel
                            ->where('id',$doctor_id)
                            ->where('status', 1)
                            ->get();
        }else{

            $doctors = $this->AdminUserModel
                            ->join('roster','roster.doctor_id','=','users.id')
                            ->join('roster_has_dates','roster_has_dates.roster_id','=','roster.id')
                            ->where('users.status', 1)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->whereDate('roster_has_dates.date','>=',date('Y-m-d'))
                            ->groupBy('users.id')
                            ->get(['users.*']);

        }
        // Default site patients
        // $this->ModuleTitle              =  __('admin.TITLE_WAITING_QUEUE_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        

        $this->ViewData['doctors'] = $doctors; 
        $this->ViewData['doctor_id'] = $doctor_id; 

        $this->ViewData['weekdays']     = $this->WeekDaysModel
                                        ->where('status', 1)
                                        ->get(); 
        // All appointment types 
        $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->get();                                         
        // $this->ViewData['addButton']    = __('admin.TITLE_ADD_BUTTON').' '.str_singular($this->ModuleTitle);

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }

    public function arrangeTimeSlot(Request $request) 
    { 
         //dd($request->all());
        $week_array = explode(",",$request->hidden_week_day);
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
        try 
        {
           // dd($request->doctor_id);
            if($request->doctor_id==0)
            {
                // $all_time = $this->RosterHasDatesModel->select('doctor_id')
                // ->leftjoin('roster','roster.id','roster_has_dates.roster_id')
                // ->whereDate('date','=',$request->roster_date) 
                // ->first();
                
                $all_time = $this->RosterHasDatesModel->select('doctor_id')
                                ->select('roster_has_weeks_has_time_frames.roster_id','roster.doctor_id')
                                ->leftjoin('roster','roster.id','roster_has_dates.roster_id')
                                ->leftjoin('roster_has_weeks_has_time_frames',function($query)
                                    {
                                        $query->on('roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id');
                                        $query->on('roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id');
                                    })

                                ->where('date','>=',$request->roster_date)
                                ->where('date','<=',$request->roster_date)
                                ->whereIn('roster_has_dates.week_day_id',$week_array)
                                ->where('time_frame',$request->roster_time_slot.":00")
                                ->get()->filter(function($item) use($request)
                                    {
                                        $appoitment_exist = $this->BaseModel->where(
                                            ['start_date'=>$request->roster_date." ".$request->roster_time_slot.":00",
                                            'doctor_id'=>$item->doctor_id])->first();
                                        if($appoitment_exist ){
                                          return false;
                                        }else
                                        {
                                            return true;
                                        }
                                    });              
                $doctor_id = $all_time->pluck('doctor_id')->first();
            }
            else
            {
                $doctor_id = $request->doctor_id;
            }

            $reqdata = [
                        'doctor_id' =>$doctor_id, 
                        'appointment_type_id' => $request->appointment_type_id, 
                        'roster_date' => $request->roster_date, 
                        'roster_time_slot' => $request->roster_time_slot, 
                   ];

            $requested_data = base64_encode(json_encode($reqdata));
            session(['appointmentData' =>$requested_data]);

            $this->JsonData['data']     = $reqdata;
            $this->JsonData['url']      = url('/online-appointment/login');
            $this->JsonData['msg']      = '';
            $this->JsonData['status']   = __('front.RESP_SUCCESS');

        } catch (Exception $e) 
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);   
        
    }

    public function bookWebAppointment(Request $request)
    {
        //dd($request->all());
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');

        $errors = '';

        $doctor_id              = $request->doctor_id;
        $appointment_type_id    = $request->appointment_type_id;
        $appointment_date       = $request->roster_date;
        $time_frame             = $request->roster_time_slot;
        $first_name             = $request->first_name;
        $family_name            = $request->family_name;
        $mobile_no              = $request->mobile_no;
        $otp_code               = $request->otp_code;
        $birth_date               = $request->birth_date;
        $country_code               = $request->country_code;

        if(empty($otp_code)){
            //error message url
            $errors = 'SMS-Code ist erforderlich.';
            $this->JsonData['msg'] = $errors; 
            return response()->json($this->JsonData);
            exit();
        }

        $splitted_first_name = preg_split("/[\s,\-,\_]+/", $first_name);
        if(count($splitted_first_name) > 1)
        {
            $first_name = $splitted_first_name[0];
        }
        $patient_data = $this->PatientsModel
                        ->whereRaw("MATCH(first_name) AGAINST('".$first_name."')")
                        ->whereRaw("MATCH(family_name) AGAINST('".$family_name."')") 
                        ->where('login_otp',trim($otp_code))                       
                        ->whereDate('birth_date',date('Y-m-d',strtotime($birth_date)))
                        ->whereIn('mobile_no',array(trim($mobile_no),ltrim($request->mobile_no,'0'),'0'.$mobile_no))
                        ->orderby('id','DESC')
                        ->first();
        //dd($patient_data);
        if(empty($patient_data))
        { 
            $patient_data = $this->PatientsModel
                        ->whereRaw("MATCH(first_name) AGAINST('".$first_name."')")
                        ->whereRaw("MATCH(family_name) AGAINST('".$family_name."')")      
                        ->whereIn('mobile_no',array(trim($mobile_no),ltrim($request->mobile_no,'0'),'0'.$mobile_no))
                        ->where('login_otp',trim($otp_code))
                        ->orderby('id','DESC')
                        ->first();

            if($patient_data)
            {
                $this->PatientsModel->where('id',$patient_data->id)->update(['birth_date'=>date('Y-m-d',strtotime($birth_date))]);
            }
        } 
        else
        {
            // $check_mobile = $this->PatientsModel                                               
            //             ->where('mobile_no',trim($mobile_no))
            //             ->where('id','<>',$patient_data->id)
            //             ->get();

            //            // dd("else",count($check_mobile));
            // if(count($check_mobile) > 0)
            // {                
            //     $errors =  __('admin.ERR_MOBILE_UNIQUE');
            //     $this->JsonData['msg'] = $errors; 
            //     return response()->json($this->JsonData);
            //     exit();
            // }
            // else
            // {
               // $this->PatientsModel->where('id',$patient_data->id)->update(['mobile_no'=>$mobile_no,'country_code'=>$country_code]);
            // }           
        }  
        //dd($patient_data);
        // $patient_data = $this->PatientsModel
        //                     // ->where('first_name',trim($first_name))
        //                     // ->where('family_name',trim($family_name))
        //                     // ->where('mobile_no',trim($mobile_no))
        //                     // ->where('login_otp',trim($otp_code))
        //                     ->whereDate('birth_date',date('Y-m-d',strtotime($birth_date)))
        //                     ->get();


           // dd($birth_date,$patient_data);
        if(!empty($patient_data))
        {
           
            $patient_id     = $patient_data->id;
            $request['patient_id'] = $patient_id;

            try {
                //Check doctor time frame is available before booking appointment, if not available then throw error message
                $check_time_frame = $this->RosterModel
                            ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                            ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                            ->where('roster.doctor_id',$doctor_id)
                            ->where('roster_has_dates.is_excluded','=',0)
                            ->whereDate('roster_has_dates.date',$appointment_date)
                            ->where('roster_has_weeks_has_time_frames.time_frame','=',$time_frame)
                            ->get(['roster_has_weeks_has_time_frames.time_frame']); 
                //dd($check_time_frame);
                if(!empty($check_time_frame) && sizeof($check_time_frame)>0){
                    //now time slotes are available , but the appointment is booked for it then throw error message
                    $check_app_date = date("Y-m-d H:i:s",strtotime($appointment_date." ".$time_frame));
                    $check_doctor_booked_appointment = $this->BaseModel
                                                            ->where('doctor_id',$doctor_id)
                                                            ->where('appointment_type_id',$appointment_type_id)
                                                            ->whereStatus(1)
                                                            ->where('appointment.start_date','=',$check_app_date)
                                                            ->get(['id']);

                    if(!empty($check_doctor_booked_appointment) && sizeof($check_doctor_booked_appointment)>0){
                            $errors = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
                            $this->JsonData['msg'] = $errors; 
                            return response()->json($this->JsonData);
                            exit();
                    }

                }else{
                     $errors = 'Terminzeitfenster können nicht gebucht werden.';//Appointment time slots is not available for booking
                    $this->JsonData['msg'] = $errors; 
                    return response()->json($this->JsonData);
                    exit();
                }

                if(empty($errors) && $errors==''){
                    //Start Booking an Appointement

                    DB::beginTransaction(); 

                    $collection     = new $this->BaseModel;   
                    $request['start_date'] = date("Y-m-d H:i",strtotime($appointment_date." ".$time_frame));
                    
                    $request['end_date']  = self::_getEndDate($request['start_date'],$appointment_type_id);
                    // dd($request->all());
                    //please get patient id and add it in request
                    $collection     = self::_storeAppointment($collection,$request);
                    self::_deactivateReminder($collection);

                    $all_transactions = [];
                    $notify_data = [];
                    $notes = '';
                    if ($collection) 
                    {
                        $all_transactions[] = 1;
                        
                        $patient_doc_data = [];
                        // $patient_doc_data[] = array(
                        //                             'appointment_id'=> $collection->id,
                        //                             'patient_id'    => $collection->patient_id,
                        //                             'exam_app_type_id'=> $appointment_type_id,
                        //                             'record_type'   => 1,
                        //                             'doc_status'   => 0,
                        //                             );
                        // // dd($patient_doc_data);

                        // if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                        //     $all_transactions[] = 1;
                        // }else{
                        //     $all_transactions[] = 0;
                        // }
                        
                        $getDocument = self::_GetAssignedDocument($collection->id,$collection->appointment_type_id,$request->app_services,$collection->patient_id);
                        // END

                        //insert the entry for patient has Checklist
                        $getDocument = self::_GetAssignedCheckList($collection->id,$request->app_services,$collection->patient_id);

                        $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType','hasExaminations'=>function($q){
                                        $q->with(['assignedExamination']);
                                    }])->find($collection->id);   
                            
                        $country_code = $collection->assignedPatient->country_code;
                        $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                        $patientEmail = $collection->assignedPatient->email;
                        $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                        $appointmentType = $collection->assignedAppointmentType->name;
                        
                        $appointmentTime = date('d.F',strtotime($request->start_date)).",um ".date('H:i',strtotime($request->start_date))." Uhr.";

                        $patientText = $collection->assignedPatient->salutation ?? "";
                        $patientText .= " ".$collection->assignedPatient->family_name;
                        $doctorSurname = $collection->assignedDoctor->last_name;
                        //Appoinment Push Notification
                        $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;

                        $booking_month = __('front.'.date('F',strtotime($request->start_date)));
                        $mailAppointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                        // $mailAppointmentTime = date('d.F',strtotime($request->start_date)).", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                        $mail_content = 'Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$mailAppointmentTime;
                        
                        $notify_times = self::_getNotifyTime($request['start_date']);
                         
                        foreach ($notify_times as $notify_time) {
                            
                            $notify_data[] = array(
                                'patient_id'    => $request->patient_id,
                                'appointment_id'=> $collection->id,
                                'title'         => 'Erinnerung an Ihren Termin',
                                'content'       => $content,
                                'notify_time'   => $notify_time,
                                'status'        => 0,
                            );
                        }

                        if($this->AppointmentHasNotificationModel->insert($notify_data))
                        {
                            $all_transactions[] = 1;
                        }
                        else
                        {
                            $all_transactions[] = 0;
                        } 
                       
                        $summary = $patientName." - ".$appointmentType;
                        $description = '<p><strong>Patient:</strong> '.$patientName.' </p><p><strong>Arzt:</strong> '.$doctorName.' </p><p><strong>Typ:</strong> '.$appointmentType.' </p><p><strong>Beginn:</strong> '.date('F d,Y H:i',strtotime($request->start_date)).' </p><strong>Ende:</strong> '.date('F d,Y H:i',strtotime($request->end_date)).' </p><p><strong>Notizen:</strong> '.$notes.' </p>';

                        $request = array(
                            'summary'=>$summary,
                            'description'=>$description,
                            'startDateTime'=>$request->start_date,
                            'endDateTime'=>$request->end_date,
                            'patient_id'=>$request->patient_id,
                            'patient_email'=>$collection->assignedPatient->email,
                            'patient_name'=>$patientName,
                            'doctor_email'=>$collection->assignedDoctor->email,
                            'color_id'=>$collection->assignedDoctor->google_color_id,
                            );
                        request()->merge($request);
                        // dd(request()->all());
                        $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventStore(request());
                        //$postResponse = json_decode($postCalDetails->data);
                         // dd($postCalDetails);
                        if(!empty($postCalDetails) && $postCalDetails->original['status'] == 'success')
                        {
                            $all_transactions[] = 1;

                            $eventId = $postCalDetails->original['data']->id;
                            $collection->google_event_id = $eventId;
                            $collection->event_id = $eventId;
                            //$collection->notes          = $notes;

                            if($collection->save()){
                                $updateEvent = app('App\Http\Controllers\Admin\DashboardController')->appointmentIdUpdateInEvent($eventId, $collection->id);
                                $all_transactions[] = 1;
                                
                            }else{
                                
                                $all_transactions[] = 0;
                            }

                        }else{
                            $all_transactions[] = 0;
                            $errors = $postCalDetails->original['msg'];
                        }
                       
                    }else{
                        $all_transactions[] = 0;
                    }

                    if (!in_array(0,$all_transactions)) 
                    {
                        DB::commit();
                        $status  = true;
                        $message = __('api.APPOINTMENT_BOOKED_SUCCESS');

                        if(!empty($country_code)){
                            $country_code = str_replace("00", "",$country_code);
                        }elseif(empty($country_code)){
                            $country_code = '43'; //Austria country code
                        }

                        $phone   = $country_code."".str_replace("-", "",$mobile_no);
                        // dd($phone,$message);
                        // $message .= "test message from puregyn api...please ignore.";
                        $this->_sendSms($phone,$content);
                        if(!empty($patientEmail))
                        {
                           $this->_sendMail($patientName,$patientEmail,$mail_content);
                        }

                        $data[0]['id']          = $collection->id;
                        $data[0]['start_date']  = $collection->start_date;
                        $data[0]['end_date']    = $collection->end_date;
                        $data[0]['patient_id']  = $collection->patient_id;
                        $data[0]['doctor_id']   = $collection->doctor_id;
                        $data[0]['appointment_type_id']    = $collection->appointment_type_id;
                        $data[0]['appointment_type_name']  = $collection->assignedAppointmentType->name;
                        $data[0]['patient_name']    = $patientName;
                        $data[0]['doctor_name']     = $doctorName;
                        $data[0]['doctor_speciality']  = $collection->assignedDoctor->doctor_speciality;
                        $data[0]['doctor_image']       = $collection->assignedDoctor->img_path;

                        session(['appointmentData' =>'']);

                        // Set Check List Data
                        $chk_data = base64_encode(json_encode($data));
                        session(['chk_data' =>$chk_data]);
                        $this->JsonData['data']   = $data;
                        $this->JsonData['url']    = url('/online-appointment/get-check-list');
                        $this->JsonData['msg']    = $message;
                        $this->JsonData['msg']    = '';
                        $this->JsonData['status'] = __('front.RESP_SUCCESS');

                        //$data[]  = $collection;
                        // self::_createLog('bookAppointment',$data,'info');
                        // $this->ActivityLogModel->addApiLog('Book Appointment','has book appointment','Create',null,$data);
                    }

                }   

            }
            catch(\Exception $e) {
                DB::rollback();
                $errors = $e->getMessage();
                $this->JsonData['errors'] = $errors; 
                // self::_createLog('bookAppointment',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        }else{
            $errors = 'Wir konnten leider keinen passenden Eintrag finden. Überprüfen sie bitte das Geburtsdatum und die Schreibweise ihres Namens';
           
            $doctor = $this->AdminUserModel->select('first_name','last_name')->where('id',$doctor_id)->first();
            $mail_content['first_name'] = $first_name;
            $mail_content['family_name'] = $family_name;
            $mail_content['doctor_name'] = $doctor->first_name." ".$doctor->last_name;
            $mail_content['mobile'] = $mobile_no;
            $mail_content['birth_date'] = $birth_date;
            $mail_content['appoitment_date'] = $appointment_date;
            $mail_content['time_frame'] = $time_frame;
            

            $admin_email = $this->SettingsModel
                        ->where('setting_key','=','ADMINISTRATOR_EMAIL')
                        ->whereStatus(1)
                        ->first();
            if($admin_email)
            {
                $this->_failedLoginMail($admin_email->setting_value,$mail_content);
            }
            $this->JsonData['msg'] = $errors; 
        }
        
        //dd($this->JsonData);
        return response()->json($this->JsonData);   
    }

    public function allreadyExist($patient_id,$appointment_id)
    {
        $arr_flag = $exam = [];
        $getAppointmentDetails = $this->BaseModel->find($appointment_id);
        $rec = $this->CheckListHasSelectedQuestionModel
               ->where('fk_patient_id',$patient_id)
               ->where('fk_appointment_id',$appointment_id)
               ->where('type','general')
               ->get();

        if(!empty($rec) && sizeof($rec)>0)
        {
            $arr_flag['general_chk'] = 1;
        }
      

        // Examination
        $exam_session = json_decode(base64_decode(session('exam_arr'),true),true);

        if(!empty($exam_session) && sizeof($exam_session)>0)
        {
            foreach ($exam_session as $exam_key => $exam_value) 
            {
                $services = $this->AppointmentHasExaminationsModel
                            ->where('patient_id',$patient_id)
                            ->where('examination_id',$exam_value)
                            ->where('appointment_id',$appointment_id)
                            ->first();

                if(!empty($services))
                {
                    $arr_flag['examination'] = 1;
                }
                // Perofrmance check list
                $rec = $this->CheckListHasSelectedQuestionModel
                       ->where('fk_patient_id',$patient_id)
                       ->where('fk_appointment_id',$appointment_id)
                       ->where('fk_examination_id',$exam_value)
                       ->where('type','performance')
                       ->get();   
                if(!empty($rec) && sizeof($rec)>0)
                {
                    $arr_flag['performance_chk'] = 1;
                } 

                //service Document
                $getServiceDocument = $this->PatientHasDocumentsModel
                              ->where('appointment_id',$appointment_id)
                              ->where('patient_id',$patient_id)
                              ->where('exam_app_type_id',$getAppointmentDetails->appointment_type_id)
                              ->where('fk_examinations_id',$exam_value)
                              ->where('type','service')
                              ->get();
                if(!empty($getServiceDocument) && sizeof($getServiceDocument)>0)
                {
                    $arr_flag['service_doc'] = 1;
                }
            }

        }

        //Document general document
        $getgeneralDocument = $this->PatientHasDocumentsModel
                              ->where('appointment_id',$appointment_id)
                              ->where('patient_id',$patient_id)
                              ->where('exam_app_type_id',$getAppointmentDetails->appointment_type_id)
                              ->where('type','general')
                              ->get();

        if(!empty($getgeneralDocument) && sizeof($getgeneralDocument)>0)
        {
            $arr_flag['general_doc'] = 1;
        }  
                          

        return $arr_flag;
             
    }
    public function getCheckList()
    {
        $generalCheckList = $getExamination  = [];
        $exaination_html = $document_html = $chkexistFlag = $else_flag = NULL;
        $temp_exam = []; 
        $getHtmlForPerformanceCheckList = NULL;

        $session = json_decode(base64_decode(session('chk_data'),true),true);
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
        if(!empty($patient_id) && !empty($appointment_id))
        {
            // Get General Check List
            $allreadyExist = self::allreadyExist($patient_id,$appointment_id);
            //dd($allreadyExist);
            $general_checklist = $examination_flag = $performance_checklist = $service_doc = $general_doc = 0;
           
            if(!empty($allreadyExist) && sizeof($allreadyExist)>0)
            {
                if(isset($allreadyExist['general_chk']) && $allreadyExist['general_chk'] == 1)
                {
                    $general_checklist = 1;
                }
                if(isset($allreadyExist['examination']) && $allreadyExist['examination'] == 1)
                {
                    $examination_flag = 1;
                }
                if(isset($allreadyExist['performance_chk']) && $allreadyExist['performance_chk'] == 1)
                {
                    $performance_checklist = 1;
                }
                if(isset($allreadyExist['service_doc']) && $allreadyExist['service_doc'] == 1)
                {
                    $service_doc = 1;
                }
                if(isset($allreadyExist['general_doc']) && $allreadyExist['general_doc'] == 1)
                {
                    $general_doc = 1;
                }
            }
            // ==========================================================================
            $generalCheckList = self::getAllGeneralChecklist($patient_id,$appointment_id); 
            $getExamination   =  self::getAllExamination($patient_id,$appointment_id);
            //dd($generalCheckList);
            if(!empty($generalCheckList) && sizeof($generalCheckList)>0)
            {
                $generalCheckList   = $generalCheckList;
                $this->ViewData['type']   = 0;
                $this->ViewData['chk_type']   = 'general';
            }
            else if(!empty($getExamination) && sizeof($getExamination)>0)
            {
                //dd($getExamination);
                if(!empty($getExamination) && sizeof($getExamination)>0)
                {
                    $exaination_html = self::examinationDiv($getExamination);
                    // If Examination is Exist and Genral document is empty
                    $this->ViewData['type']        = 0;
                    $this->ViewData['exam_type']   = 1;
                    $this->ViewData['chk_type']    = 'performance';
                }
                else
                {
                    $else_flag = 1;
                    $this->ViewData['type']   = 1;
                    $this->ViewData['type']   = 1;
                }
            }
            else
            {
                return redirect('/');
            }
            
            if($performance_checklist == 1 || $else_flag == 1)
            {
                $generalDocumentList = self::getAllGeneralDocument($patient_id,$appointment_id); 
                if(!empty($generalDocumentList) && sizeof($generalDocumentList)>0)
                {
                    $document_html = self::documentDiv($generalDocumentList);

                }
            }
            // Peromance check list 
            $exam_session = json_decode(base64_decode(session('exam_arr'),true),true);

            if(!empty($exam_session) && sizeof($exam_session)>0)
            {
                foreach ($exam_session as $exam_key => $exam_value) 
                {
                    $temp_exam[]  = $exam_value;
                }

                $performanceCheckList = self::getAllPerformanceDocument($temp_exam,$patient_id,$appointment_id,0);
           
                if(!empty($performanceCheckList) && sizeof($performanceCheckList)>0)
                {
                    $getHtmlForPerformanceCheckList = self::getHtmlForPerformanceCheckList($performanceCheckList);
                }
            }
            
            $getAllDocumentList = self::getAllGeneralDocument($patient_id,$appointment_id);

            
            $this->ViewData['getHtmlForPerformanceCheckList'] = $getHtmlForPerformanceCheckList;
            $this->ViewData['general_checklist']     = $general_checklist;
            $this->ViewData['examination_flag']      = $examination_flag;
            $this->ViewData['performance_checklist'] = $performance_checklist;
            $this->ViewData['general_doc']           = $general_doc;
            $this->ViewData['service_doc']           = $service_doc;

            $this->ViewData['document_html']      = $document_html;
            $this->ViewData['exaination_html']    = $exaination_html;
            $this->ViewData['getAllDocumentList'] = $getAllDocumentList;
            $this->ViewData['generalCheckList']   = $generalCheckList;
            $this->ViewData['getExamination']     = $getExamination;
            $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
            $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
            $this->ViewData['modulePath']   = $this->ModulePath;
            //dd($this->ViewData);
            return view($this->ModuleView.'checklist', $this->ViewData);
        }
        else
        {
            return redirect('/');     
        }      
    }

    // Generate Check list pdf`
    public function generateCheckListPdf(Request $request)
    {
        //dd($request);
        $errors     = [];  
        $data       = []; 
        $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $status     = false;
        $exaination_html = '';
        $getAllDocumentList = [];
        $document_html = [];
        $exam = [];
        $inputdata  = $request->all();
        
        try
        {
            $session = json_decode(base64_decode(session('chk_data'),true),true);
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
            //dd($patient_id,$appointment_id);
            $collection = self::_createGeneralPdf($inputdata,$patient_id,$appointment_id);

            // Performance check list
            //dd($request->chk_type);
            if($request->chk_type == 'general')
            {
                $getExamination = self::getAllExamination($patient_id,$appointment_id);
                if(!empty($getExamination) && sizeof($getExamination)>0)
                {
                    $exaination_html = self::examinationDiv($getExamination);

                }
                else
                {
                    // $generalDocumentList = self::getAllGeneralDocument($patient_id,$appointment_id); 
                    // if(!empty($generalDocumentList) && sizeof($generalDocumentList)>0)
                    // {
                    //     $document_html = self::documentDiv($generalDocumentList);

                    // }
                    // $getAllDocumentList = $generalDocumentList;
                    $exam_session = json_decode(base64_decode(session('exam_arr'),true),true);
                    if(!empty($exam_session) && sizeof($exam_session)>0)
                    {
                        foreach ($exam_session as $exam_key => $exam_value) 
                        {
                            $get_examination = $this->ExaminationsModel->find($exam_value);
                            $exam[$exam_key]  = $get_examination->id;
                        }
                    }
                    $getAllDocumentList = self::getAllDocumentList($exam,$patient_id,$appointment_id);
                    if(!empty($getAllDocumentList) && sizeof($getAllDocumentList)>0)
                    {
                        $document_html = self::documentDiv($getAllDocumentList);
                    }
                }
            }
            else
            {
                // return redirect(url('/online-appointment/getDocument')); 
                $exam_session = json_decode(base64_decode(session('exam_arr'),true),true);
                if(!empty($exam_session) && sizeof($exam_session)>0)
                {
                    foreach ($exam_session as $exam_key => $exam_value) 
                    {
                        $get_examination = $this->ExaminationsModel->find($exam_value);
                        $exam[$exam_key]  = $get_examination->id;
                    }
                }    
                $getAllDocumentList = self::getAllDocumentList($exam,$patient_id,$appointment_id);
                if(!empty($getAllDocumentList) && sizeof($getAllDocumentList)>0)
                {
                    $document_html = self::documentDiv($getAllDocumentList);
                }
                
               //dd($document_html); 
            }
        }
        catch(\Exception $e) {
            DB::rollback();
            $errors = $e->getMessage();
            $this->JsonData['errors'] = $errors; 
            // self::_createLog('bookAppointment',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
            
        if(sizeof($getAllDocumentList)>0)
        {
            // session(['chk_data' =>'']);
            // session(['exam_arr' =>'']);
            session(['sucess_msg' =>$message]);
        }

        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
        $this->JsonData['exaination_html'] = $exaination_html;
        $this->JsonData['getAllDocumentList'] = $getAllDocumentList;
        $this->JsonData['url']    =  url('/');
        $this->JsonData['msg']    = __('api.APPOINTMENT_BOOKED_SUCCESS'); 
        $this->JsonData['patient_id']    = $patient_id; 
        $this->JsonData['appointment_id']= $appointment_id;
        $this->JsonData['document_html']= $document_html; 
        //dd($this->JsonData);
        return response()->json($this->JsonData);  
    }

    public function documentDiv($generalDocumentList)
    {
        $str = '';
        $str .='<div data-toggle="collapse" data-target="#document" class="card card-primary" style="width: 100%;">   
        <div class="card-header">
            <h3 class="card-title">'.__('front.Document').'</h3>
        </div>
      </div>
      <div id="document" class="collapse show" style="display:block">
        <form id="frmDocument" method="post" data-toggle="validator" action="'.url('/online-appointment/generate-Document-listPdf').'">
                <input type="hidden" name="_token" value="'.csrf_token().'">
                <div class="card card-primary" style="width: 100%;">   
                    <div class="card-body">';
                      if(!empty($generalDocumentList) && sizeof($generalDocumentList)>0){
                        $cnt =1;
                        foreach($generalDocumentList as $doc_key =>$doc_val){
                        $str .='<div class="row">
                          <div class="col-sm-12"> 
                              <div class="p-0 form-group"> 
                                  <div class="form-check" style="margin-left: 5px;">
                                        <input type="hidden" name="doc_hd[]" value="'.$doc_val["doc_id"].'">
                                        <input type="hidden" name="exam_id[]" id="exam_id" value="'.$doc_val["exam_id"].'">
                                        <input type="hidden" name="doc_type[]" id="doc_type" value="'.$doc_val["chk_type"].'">
                                       <input 
                                            onclick="getDocument('.$doc_val["doc_id"].')"
                                            type="checkbox" 
                                            class="form-check-input" 
                                            name="doc[]" 
                                            value="'.$doc_val["doc_id"].'" 
                                        >
                                        <label class="form-check-label" for="status">
                                          '.ucfirst($doc_val['name']).'
                                        </label>
                                        
                                  </div>  
                              </div>
                              <hr>
                          </div>
                       
                        </div>';
                        $cnt++;
                        }
                     }
                    $str .='</div><!-- /.card-body -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success" onclick="getDoctorTimeFrames()">'.__('front.TITLE_SEARCH_TEXT').'</button>
                    </div>
                </div>
        </form>
      </div>';

      return $str;
    }
    public function examinationDiv($getExamination)
    {
        $str = '';
        $str .= '<div data-toggle="collapse" data-target="#examination_div" class="card card-primary" style="width: 100%;">   
                    <div class="card-header">
                        <h3 class="card-title">Service</h3>
                    </div>
                </div>
                <div id="examination_div" class="collapse" >
                 
                    <form id="examinationForm" role="form" data-toggle="validator" action="'.url('/online-appointment/get-all-examination').'"> 

                        <div class="card-body">';
                            if(!empty($getExamination) && sizeof($getExamination)>0)
                            {
                                foreach($getExamination as $exam_key =>$exam_val)
                                {

                                    $str .= '<div class="row">
                                        <div class="col-sm-12"> 
                                            <div class="p-0 form-group"> 
                                                <div class="form-check" style="margin-left: 5px;">
                                                      <input 
                                                        type="checkbox" 
                                                        class="form-check-input" 
                                                        name="app_services['.$exam_key.']" 
                                                        value="'.$exam_val['id'].'" 
                                                        >
                                                      <label class="form-check-label" for="status">
                                                       '.$exam_val['name'].'
                                                      </label>
                                                </div>  
                                            </div>
                                        </div>
                                    </div>';
                                }
                            }
                        $str .='</div><!-- /.card-body -->
                        <div class="card-footer">
                          <button type="button" onclick="submitExamination(this)" id="btn_examination" class="btn btn-success" >'.__('front.TITLE_SEARCH_TEXT').'</button>
                        </div>
                    </form>
                </div>';
        return $str;        
    }

    public function get_document()
    {
        $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $exam_data = $generalDocumentList = $serviceDocumentList = [];
        $session = json_decode(base64_decode(session('chk_data'),true),true);
        $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $patient_id = $appointment_id = null ; 
     
        if(!empty($session) && sizeof($session)>0)
        {
           $patient_id     = $session[0]['patient_id'];
           $appointment_id = $session[0]['id'];
           
            $generalDocumentList = self::getAllGeneralDocument($patient_id,$appointment_id); 
            
            // if(!empty($generalDocumentList) && sizeof($generalDocumentList)>0)
            // {
                $this->ViewData['type']       = 'general';
                $this->ViewData['chk_type']   = 'general';
            // }
            // else
            // {
                $exam_session = json_decode(base64_decode(session('exam_arr'),true),true);
                if(!empty($exam_session) && sizeof($exam_session)>0)
                {
                    foreach ($exam_session as $exam_key => $exam_value) 
                    {
                        $get_examination = $this->ExaminationsModel->find($exam_value);
                        $exam[$exam_key]  = $get_examination->id;
                       
                    }
                    
                    $serviceDocumentList = self::getAllServicesDocument($exam,$patient_id,$appointment_id,1);
                   
                    if(sizeof($generalDocumentList)>0)
                    {
                        $this->ViewData['type']       = '';
                        $this->ViewData['chk_type']   = 'service';
                    }
                    else
                    {
                        // session(['chk_data' =>'']);
                        // session(['exam_arr' =>'']);

                        session(['sucess_msg' =>$message]);
                        return redirect('/');  
                    }
                    // view file with data
                }
                else
                {
                    return redirect('/');     
                }
                
            // }
            //dd($generalDocumentList,$serviceDocumentList);
            $finalData = array_merge($generalDocumentList,$serviceDocumentList);
            
            //dd($arr_chunk);
            $this->ViewData['generalDocumentList']   = $finalData;
            $this->ViewData['getExamination']   = $exam_data;
            $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
            $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
            $this->ViewData['modulePath']   = $this->ModulePath;
          
            return view($this->ModuleView.'document-list', $this->ViewData);
        }
        else
        {
            return redirect('/');     
        }

        
    }

    public function generate_single_document(Request $request)
    {
        //dd($request->all());
        $getDocumentList = $this->SpecialistDocumentsModel->find($request->doc_id);
        if(!empty($getDocumentList))
        {
            $header_image_path = self::getFilePath($getDocumentList->header_image_path);
            $footer_image_path = self::getFilePath($getDocumentList->footer_image_path);
            $str = '<div class="row" >
                <input type="hidden" name="hd_doc_id" id="hd_doc_id" value="'.$request->doc_id.'">';
                if(isset($getDocumentList->header_image_path) && !empty($getDocumentList->header_image_path))
                {
                    $str .= '<img style="width: 100%;height: 180px;" src="'.$header_image_path.'" alt="'.$getDocumentList->header_image.'">';
                }
                $str .= '</div>
                <div class="row" style="width: 103%;height: auto;background-color:'.$getDocumentList->background_color.'" >
                  <div class="col-sm-12" style="margin-top: 25px"> 
                    <div class="p-0 form-group"> 
                        <h4>
                          '.$getDocumentList->name.'
                        </h4>
                    </div>
                  </div>
                  <div class="col-sm-12"> 
                    <div class="p-0 form-group"> 
                        <label>'.$getDocumentList->html_text.'</label>
                    </div>
                  </div>
                </div>';
                if(isset($getDocumentList->footer_image_path) && !empty($getDocumentList->footer_image_path))
                {
                    $str .= '<div class="row" >
                      <img style="width: 100%;height: 100px;" src="'.$footer_image_path.'" alt="'.$getDocumentList->footer_image.'">
                    </div>';
                }
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

        $collection = self::_createGeneralDocumentPdf($request,$patient_id,$appointment_id);
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

            return redirect(url('/')); 
            
        }

    }
    public function submitExamination(Request $request)
    {
        //dd($request->all());
        $generalCheckList = $generalDocumentList = [];
        $getHtmlForPerformanceCheckList = '';
        if(isset($request->app_services))
        {
            $exam = base64_encode(json_encode($request->app_services));
            session(['exam_arr' =>$exam]);
            
            $session = json_decode(base64_decode(session('chk_data'),true),true);
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
            // ========================

            $getServises = self::_appointmentTypesAgaintsServices($appointment_id,$request,$patient_id);
            

            $performanceCheckList = self::getAllPerformanceDocument($request,$patient_id,$appointment_id,1);
        
            if(!empty($performanceCheckList) && sizeof($performanceCheckList)>0)
            {
                $getHtmlForPerformanceCheckList = self::getHtmlForPerformanceCheckList($performanceCheckList);
                
            }
            
        }
        // GET DOCUMENT LIST
        $generalDocumentList = self::getAllDocumentList($request->app_services,$patient_id,$appointment_id);
        
        $this->JsonData['getHtmlForPerformanceCheckList']= $getHtmlForPerformanceCheckList;  

        $this->JsonData['status']        = __('admin.RESP_SUCCESS');
        $this->JsonData['getAllDocumentList'] = $generalDocumentList;
        $this->JsonData['url']           =  url('/');
        $this->JsonData['msg']           = __('api.APPOINTMENT_BOOKED_SUCCESS'); 
        $this->JsonData['patient_id']    = $patient_id; 
        $this->JsonData['appointment_id']= $appointment_id; 
        
        return response()->json($this->JsonData); 
    }

    public function getHtmlForPerformanceCheckList($generalCheckList)
    {
        //dd($generalCheckList);
        $str = '';
        $str .= '<div data-toggle="collapse" data-target="#performance_div" class="card card-primary" style="width: 100%;">   
                    <div class="card-header">
                        <h3 class="card-title">'.__('front.TITLE_PERFORMANCE_CHECK_LIST').'</h3>
                    </div>
                </div>
                <div id="performance_div" class="collapse">';
                $chk_counter = 0;
                if(isset($generalCheckList) && sizeof($generalCheckList)>0){
                $str .='<form id="performancecheckListForm" role="form" data-toggle="validator" action="'.url('/online-appointment/generate-check-listPdf').'">
                        <input type="hidden" name="chk_type" id="chk_type" value="performance">
                        <div class="slideshow-container">';
                            foreach ($generalCheckList as $key => $value){
                                if(isset($value['exam_id']))
                                { $exam_id =  $value['exam_id'];}

                                $sty = 'display:none';
                                if($key == 0)
                                {
                                    $sty = 'display:block';
                                }
                                $str .='<div class="myPerformanceSlides" style="'.$sty.'">

                                        <div class="row">
                                            <div class="col-md-5" style="text-align:left;">
                                                <!-- Check list name -->
                                                <h2>
                                                <input type="hidden" name="check_list['.$chk_counter.'][exam_id]" value="'.$exam_id.'">
                                                <input type="hidden" name="check_list['.$chk_counter.'][checklist_id]" value="'.$value['checklist_id'].'">
                                                '.$value['check_list_name'].'
                                                </h2>
                                                <hr>
                                                <!-- check list introduction_text -->
                                                <h6> 
                                                 '.strip_tags($value['introduction_text']).'
                                                </h6>
                                                <hr>
                                                <!-- check list final_name -->
                                                <h6> 
                                                 '.strip_tags($value['final_name']).'
                                                </h6>
                                            </div>
                                            <div class="col-md-1">
                                                &nbsp;
                                            </div>
                                            <div class="col-md-6" style="text-align:left;">';
                                                $h_cnt = 0;
                                                foreach($value['heading'] as $hd_key => $hd_value)
                                                {    
                                                    $str .='<div class="col-sm-12"> 
                                                                <div class="p-0 form-group"> 
                                                                  <h4>
                                                                    <input type="hidden" name="check_list['.$chk_counter.'][Heading]['.$h_cnt.'][heading_id]" value="'.$hd_value['heading_id'].'">
                                                                    '.$hd_value['heading'].'
                                                                  </h4> 
                                                                </div>
                                                            </div>';
                                                            $q_cnt = 0;
                                                            foreach($hd_value['question'] as $qs_key => $qs_value)
                                                            {    
                                                                $str .='<div class="row">
                                                                            <div class="col-sm-12"> 
                                                                                <div class="p-0 form-group"> 
                                                                                    <div class="form-check" style="margin-left: 5px;">
                                                                                          <input type="hidden" name="check_list['.$chk_counter.'][Heading]['.$h_cnt.'][question_hd]['.$q_cnt.']" value="'.$qs_value['question_id'].'">
                                                                                          <input 
                                                                                            type="checkbox" 
                                                                                            class="form-check-input" 
                                                                                            name="check_list['.$chk_counter.'][Heading]['.$h_cnt.'][question]['.$q_cnt.']" 
                                                                                            value="'.$qs_value['question_id'].'" 
                                                                                            >
                                                                                          <label class="form-check-label" for="status">
                                                                                           '.$qs_value['question'].'
                                                                                          </label>
                                                                                    </div>  
                                                                                </div>
                                                                            </div>
                                                                        </div>';
                                                                $q_cnt ++;        
                                                            }
                                                            $str .='<hr>';
                                                            $h_cnt++;
                                                }     

                                            $str .='  </div>
                                            </div>';

                                            if($key != count($generalCheckList)-1)
                                            {
                                                $str .='<div class="col-lg-12 text-center" style="margin-top: 20px;">
                                                            <input class="btn btn-success" type="button" onclick="plusPerformanceSlides(1)" value="Bestätigen">
                                                        </div>';
                                            }
                                            else
                                            {
                                                $str .='<div class="col-lg-12 text-center" style="margin-top: 20px;">
                                                  <input class="btn btn-success" onclick="submitPerformanceFrm(this)" id="btn-sub" type="button" onclick="plusPerformanceSlides(1)" value="Bestätigen">
                                                </div>';
                                            }
                                        $str .='</div>';
                                        $chk_counter++;
                                    }
                                        $str .='<a class="prev" onclick="plusPerformanceSlides(-1)">❮</a>
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
                          $str .='</form>';
                        }
                        $str .='</div>';
        //dd($str);                
        return $str;              
    }
    public function getAllDocumentList($request,$patient_id,$appointment_id)
    {
        $generalDocumentList = $serviceDocumentList = [];
        //GENERAL DOCUMENT
        $generalDocumentList = self::getAllGeneralDocument($patient_id,$appointment_id); 
        // SERVICE DOCUMENT
        if(!empty($request->app_services) && sizeof($request->app_services)>0)
        {
            $serviceDocumentList = self::getAllServicesDocument($request->app_services,$patient_id,$appointment_id,1);
            // view file with data
        }

        $finalData = array_merge($generalDocumentList,$serviceDocumentList);
        return $finalData;
    }
    public function getDocumentExamination(Request $request)
    {
        //dd($request->all()); 
        $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $generalDocumentList = [];       
        $session = json_decode(base64_decode(session('chk_data'),true),true);
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
        $generalDocumentList = self::getAllServicesDocument($request,$patient_id,$appointment_id,0);
      
        if(sizeof($generalDocumentList)>0)
        {
            $this->ViewData['generalDocumentList']   = $generalDocumentList;
            $this->ViewData['type']       = '';
            $this->ViewData['chk_type']   = 'service';
               // view file with data
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
     
    }

    public function getAllExamination($patient_id,$appointment_id)
    {
        // dd("-----");
        // dd($patient_id,$appointment_id);
        $data = $finalDat = [];
        $getAppointment = $this->BaseModel->find($appointment_id);
       
        if(!empty($getAppointment))
        {
            $appointment_type_id = $getAppointment->appointment_type_id;
            
            // $getrecord = $this->AppointmentTypeHasExaminationsModel
            //              ->where('appoinment_id',$appointment_type_id)
            //              ->get();

            $getrecord = $this->AppointmentHasExaminationsModel
                         ->where('appointment_id',$appointment_id)
                         ->get();
           
            if(!empty($getrecord) && sizeof($getrecord)>0)
            {
                $cnt = 0;
                foreach ($getrecord as $key => $value) 
                {
                    $getExamination = $this->ExaminationsModel->find($value['examination_id']);

                    if(!empty($getExamination))
                    {
                        $data[$key]['id']   = $getExamination->id; 
                        $data[$key]['name'] = ucfirst($getExamination->name); 
                        //$GetPerformanceCheckList = self::getAllPerformanceDocument($getExamination,$patient_id,$appointment_id);
                    }
                    $cnt++;
                }
            } 
        }
        return $data;
    }

    // GET PERFORMANCE CHECK LIST
    public function getAllPerformanceDocument($getExamination,$patient_id,$appointment_id,$type)
    {
        $errors     = [];  
        $data       = $finalData = []; 
        $data_collection = null;
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND'); 
        $status     = false;
        if($type ==1)
        {
            $exam_arr = $getExamination->app_services;
        }
        else if($type ==0)
        {
            $exam_arr = $getExamination;
        }
        else
        {
            $exam_arr = $getExamination->app_services;
        }
        foreach ($exam_arr as $key => $value) 
        {
            $getMultipleCheckList = $this->ExaminationsHasMultipleCheckListModel
                                    ->where('fk_examinations_id',$value)
                                    ->get();
            
            if(!empty($getMultipleCheckList) && sizeof($getMultipleCheckList)>0)
            {
                $cnt = 0;
                foreach ($getMultipleCheckList as $mchk_key => $mchk_value) 
                {
                    $getcheckList = $this->CheckListModel
                                    ->where('type_of_checklist','performance')
                                    ->where('id',$mchk_value['fk_check_list_id'])
                                    ->where('status',1)
                                    ->first();
                   
                    if(!empty($getcheckList))
                    {
                        $patientDetails = $this->PatientsModel
                                               ->where('id',$patient_id)
                                               ->first();
                           
                        if(!empty($patientDetails))
                        {
                            $data[$cnt]['checklist_id']      = $getcheckList->id;
                            $data[$cnt]['check_list_name']   = $getcheckList->check_list_name;
                            $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                            $data[$cnt]['final_name']        = $getcheckList->final_name;
                            $data[$cnt]['exam_id']           = $value;

                            $getHEading = self::getHeadingDetails($getcheckList->id);
                            $data[$cnt]['heading'] = $getHEading;
                            $cnt++;
                        } 
                    }                
                }

                if(empty($data_collection))
                {
                    $data_collection = $data;
                    $data = [];
                }
                
            }      
        }
       
        $finalData = array_merge($data_collection,$data);
       
        return $finalData;              
    }

     // GET PERFORMANCE CHECK LIST
    public function getAllServicesDocument($getExamination,$patient_id,$appointment_id,$type)
    {
        $errors     = [];  
        $data       = $finalData = $data_collection = []; 
        $data_collection = null;
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND'); 
        $status     = false;
        if($type == 0)
        {
            $ex_arr = $getExamination->exam;
        }
        else
        {
            $ex_arr = $getExamination;
        }
        //dd($ex_arr)
        foreach ($ex_arr as $key => $value) 
        {
            $getMultipleDocumentList = $this->ExaminationsHasMultipleDocumentListModel
                                    ->where('fk_examinations_id',$value)
                                    ->get();
            //dd($getMultipleDocumentList,$value);
            if(!empty($getMultipleDocumentList) && sizeof($getMultipleDocumentList)>0)
            {
                $cnt = 0;
                foreach ($getMultipleDocumentList as $mdoc_key => $mdoc_value) 
                {
                    $getDocumentList = $this->SpecialistDocumentsModel
                                    ->where('type_of_document','service')
                                    ->where('id',$mdoc_value['fk_document_list_id'])
                                    ->where('status','1')
                                    ->first();
                                    //dd($getDocumentList);
                    if(!empty($getDocumentList))
                    {
                        $patientDetails = $this->PatientsModel
                                          ->where('id',$patient_id)
                                          ->first();
                       
                        if(!empty($patientDetails))
                        {
                            $data[$cnt]['doc_id']            = $getDocumentList->id;
                            $data[$cnt]['exam_id']           = $value;
                            $data[$cnt]['name']              = $getDocumentList->name;
                            $data[$cnt]['html_text']         = $getDocumentList->html_text;
                            $data[$cnt]['background_color']  = $getDocumentList->background_color;
                            $data[$cnt]['header_image']      = $getDocumentList->header_image;
                            $data[$cnt]['header_image_path'] = $getDocumentList->header_image_path;
                            $data[$cnt]['footer_image']      = $getDocumentList->footer_image;
                            $data[$cnt]['footer_image_path'] = $getDocumentList->footer_image_path;
                            $data[$cnt]['background_color']  = $getDocumentList->background_color;
                            $data[$cnt]['chk_type']         = 'service';
                            $cnt++;
                        } 
                    }                
                }
                if(empty($data_collection))
                {
                    $data_collection = $data;
                    $data = [];
                }
            }      
        }
        
        if(!empty($data_collection))
        {
            $finalData = array_merge($data_collection,$data);
        }
        
        return $finalData;              
    }

    public function _createGeneralPdf($inputdata,$patient_id,$appointment_id)
    {
        //dd($inputdata);
        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = $exam_id = '';
       
        
        foreach ($inputdata['check_list'] as $check_list) 
        {
            $collections = $this->CheckListModel
                            ->select('id','check_list_name','introduction_text','final_name','frequency_type','frequency','date_of_last_activation')
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
                
                $j = 0;
                foreach ($check_list['Heading'] as $heading) 
                {
                    //check list heading
                    $heading_name = $this->CheckListHasHeadingSectionModel
                                    ->where('id',$heading['heading_id'])->first();
                    $data[$cnt]['heading'][$j]['fk_chk_id']= $collections->id;                
                    $data[$cnt]['heading'][$j]['heading_id']= $heading_name['id'];
                    $data[$cnt]['heading'][$j]['heading']  = $heading_name['heading_section'];
                   
                    $k=0;
                    foreach ($heading['question_hd'] as $key => $value) 
                    {
                        //check list question
                        $question = $this->HeadingSectionHasQuestionModel
                                    ->where('id',$value)->first();

                        $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $heading_name['id'];            
                        $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $question['id'];
                        $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $question['question'];
                        if(isset($heading['question']))
                        {
                            if (in_array($value, $heading['question']))
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
                
                $accessPath = '/check_list_pdf/'.$PDFname;
                
                $PDFPath = 'admin.pdf.checkLists';  
                $a = PDF::loadView($PDFPath,compact('data'))->save($StorePath);
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
                    $CheckListHasSelectedQuestionModel->type             = $inputdata['chk_type'];
                    $CheckListHasSelectedQuestionModel->status           = '1';
                    $CheckListHasSelectedQuestionModel->activation_start_date  = $start_date;
                    $CheckListHasSelectedQuestionModel->activation_last_date   = $end_date;  
                    
                    $CheckListHasSelectedQuestionModel->save();
                } 
                else
                {
                    //dd($appointment_id);
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
                    $CheckListHasSelectedQuestionModel->type             = $inputdata['chk_type'];
                    $CheckListHasSelectedQuestionModel->status           = '1';
                    $CheckListHasSelectedQuestionModel->activation_start_date  = $start_date;
                    $CheckListHasSelectedQuestionModel->activation_last_date   = $end_date;  
                    $CheckListHasSelectedQuestionModel->save();
                } 
          
                $dataFinal[] = $data;
                $data = [];
                // ===========================================================
                //$cnt++;
            }
        }

        return $dataFinal;
    }

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
    }

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
    }

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
    }

    // END
    public function _storeAppointment($collection, $request)
    {
        $collection->patient_id  = $request->patient_id;
        $collection->doctor_id   = $request->doctor_id;  
        $collection->appointment_type_id = $request->appointment_type_id;
        $collection->start_date = $request->start_date;
        $collection->end_date   = $request->end_date;
        $collection->status     = 1;

        //Save data
        $collection->save();

        return $collection;   
    }

    public function login(Request $request)
    {

        $session = json_decode(base64_decode(session('appointmentData')));
        $appointment = [];
        if(!empty($session)){
          $appointment['doctor_id']            = $session->doctor_id;
          $appointment['appointment_type_id']  = $session->appointment_type_id;
          $appointment['roster_date']          = $session->roster_date;
          $appointment['roster_time_slot']     = $session->roster_time_slot;
        }else{
            return redirect('/');     
        }

        $this->ViewData['appointment'] = $appointment; 
        // dd($this->ViewData);
        return view($this->ModuleView.'login',$this->ViewData);
    }

    public function register(Request $request)
    { 
        // $this->ModuleTitle              =  __('admin.TITLE_PATIENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath; 

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        $session = json_decode(base64_decode(session('appointmentData')));
        $appointment = [];
        if(!empty($session)){
          $appointment['doctor_id']            = $session->doctor_id;
          $appointment['appointment_type_id']  = $session->appointment_type_id;
          $appointment['roster_date']          = $session->roster_date;
          $appointment['roster_time_slot']     = $session->roster_time_slot;
        }else{
            return redirect('/');     
        }

        $this->ViewData['appointment'] = $appointment; 

        // dd($this->ViewData);
        return view($this->ModuleView.'register',$this->ViewData);
    }

    public function registerAndBookAppointment(RegisterPatientRequest $request)
    {
        

        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_PATIENT_CREATE_BOOK'); 

        try { 
            DB::beginTransaction(); 

            $errors = '';

            $doctor_id              = $request->doctor_id;
            $appointment_type_id    = $request->appointment_type_id;
            $appointment_date       = $request->roster_date;
            $time_frame             = $request->roster_time_slot;

            $mobile_no              = ltrim($request->mobile_no,'0');

            $mobile_array   = array($mobile_no,$request->mobile_no,'0'.$request->mobile_no,'00'.$request->mobile_no);

            $is_exist_patient = $this->_checkDuplicationPatient($request->family_name,$request->first_name,$request->birth_date,$request->mobile_no,'add',$id = '');

            if(!$is_exist_patient)
            {
                $this->JsonData['msg'] = __('admin.ERR_MOBILE_UNIQUE'); 
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                return response()->json($this->JsonData);
                exit();
            } 

            // $checkedBirthdateExist = $this->PatientsModel
            //             ->whereRaw("MATCH(first_name) AGAINST('".$request->first_name."')")
            //             ->whereRaw("MATCH(family_name) AGAINST('".$request->family_name."')")     
            //             // ->where(DB::raw('upper(family_name)'),'=',strtoupper($request->family_name))
            //             // ->where(DB::raw('upper(first_name)'),'=',strtoupper($request->first_name))
            //             ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date)))
            //             ->whereNULL('deleted_at')
            //             ->get(); 

            // if(count($checkedBirthdateExist) > 0 )
            // {
            //     $this->JsonData['msg'] = __('admin.ERR_BIRTH_DATE_UNIQUE'); 
            //     return response()->json($this->JsonData);
            //     exit();
            // }

            // $checkExist = $this->PatientsModel
            //             // ->where(DB::raw('upper(family_name)'),'=',strtoupper($request->family_name))
            //             // ->where(DB::raw('upper(first_name)'),'=',strtoupper($request->first_name))
            //             ->whereRaw("MATCH(first_name) AGAINST('".$request->first_name."')")
            //             ->whereRaw("MATCH(family_name) AGAINST('".$request->family_name."')")     
            //            // ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date))
            //             ->whereIn('mobile_no', $mobile_array)
            //             ->whereNULL('deleted_at')
            //             ->get();                            

            // if(count($checkedBirthdateExist) > 0 || count($checkExist) > 0)
            // {
            //     $this->JsonData['msg'] = __('admin.ERR_MOBILE_UNIQUE'); 
            //     return response()->json($this->JsonData);
            //     exit();
            // }
            $patient_data     = new $this->PatientsModel;    
            $patient_data     = self::_storePatient($patient_data,$request);
            // $ORDINATION PATIENT CHECK
            if(!empty(Config('ordination_id')))
            {
                $ordination_patient = self::_storePatientOrdination($patient_data->id);
            }
            
            // End
            if ($patient_data) 
            { 
               
                $patient_id             = $patient_data->id;
                $mobile_no              = $patient_data->mobile_no;
                $country_code       = $patient_data->country_code;
                if(!empty($patient_data->format))
                {
                    $country_code       = $patient_data->format; 
                }  
                $request['patient_id']  = $patient_id;

                try {
                    //Check doctor time frame is available before booking appointment, if not available then throw error message
                    $check_time_frame = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                ->whereDate('roster_has_dates.date',$appointment_date)
                                ->where('roster_has_weeks_has_time_frames.time_frame','=',$time_frame)
                                ->get(['roster_has_weeks_has_time_frames.time_frame']); 
                    // dd($check_time_frame);
                    if(!empty($check_time_frame) && sizeof($check_time_frame)>0){
                        //now time slotes are available , but the appointment is booked for it then throw error message
                        $check_app_date = date("Y-m-d H:i:s",strtotime($appointment_date." ".$time_frame));
                        $check_doctor_booked_appointment = $this->BaseModel
                                                                ->where('doctor_id',$doctor_id)
                                                                ->where('appointment_type_id',$appointment_type_id)
                                                                ->whereStatus(1)
                                                                ->where('appointment.start_date','=',$check_app_date)
                                                                ->get(['id']);

                        if(!empty($check_doctor_booked_appointment) && sizeof($check_doctor_booked_appointment)>0){
                                $errors = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
                                $this->JsonData['msg'] = $errors; 
                                return response()->json($this->JsonData);
                                exit();
                        }

                    }else{
                         $errors = 'Terminzeitfenster können nicht gebucht werden.';//Appointment time slots is not available for booking
                        $this->JsonData['msg'] = $errors; 
                        return response()->json($this->JsonData);
                        exit();
                    }

                    if(empty($errors) && $errors==''){
                        //Start Booking an Appointement

                        $collection     = new $this->BaseModel;   
                        $request['start_date'] = date("Y-m-d H:i",strtotime($appointment_date." ".$time_frame));
                        
                        $request['end_date']  = self::_getEndDate($request['start_date'],$appointment_type_id);
                        // dd($request->all());
                        //please get patient id and add it in request
                        $collection     = self::_storeAppointment($collection,$request);
                        self::_deactivateReminder($collection);
                        $all_transactions = [];
                        $notify_data = [];
                        $notes = '';
                       
                        if ($collection) 
                        {

                            $all_transactions[] = 1;
                            
                            // $patient_doc_data = [];
                            // $patient_doc_data[] = array(
                            //                             'appointment_id'=> $collection->id,
                            //                             'patient_id'    => $collection->patient_id,
                            //                             'exam_app_type_id'=> $appointment_type_id,
                            //                             'record_type'   => 1,
                            //                             'doc_status'   => 0,
                            //                             );
                            // // dd($patient_doc_data);

                            // if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                            //     $all_transactions[] = 1;
                            // }else{
                            //     $all_transactions[] = 0;
                            // }

                            $getDocument = self::_GetAssignedDocument($collection->id,$collection->appointment_type_id,$request,$collection->patient_id);
                            // END

                            //insert the entry for patient has Checklist
                            $getDocument = self::_GetAssignedCheckList($collection->id,$request,$collection->patient_id);
                            // END

                            //Default appintment  
                            //$getServises = self::_appointmentTypesAgaintsServices($collection->id,$request->appointment_type_id,$collection->patient_id);
                            // END

                            $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType','hasExaminations'=>function($q){
                                            $q->with(['assignedExamination']);
                                        }])->find($collection->id);   
                            
                            $country_code = $collection->assignedPatient->country_code;
                            $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                            $patientEmail = $collection->assignedPatient->email;
                            $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                            $appointmentType = $collection->assignedAppointmentType->name;
                            
                            $appointmentTime = date('d.F',strtotime($request->start_date)).",um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            $patientText = $collection->assignedPatient->salutation ?? "";
                            $patientText .= " ".$collection->assignedPatient->family_name;
                            $doctorSurname = $collection->assignedDoctor->last_name;
                            //Appoinment Push Notification
                            $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;

                            // $mailAppointmentTime = date('d.F',strtotime($request->start_date)).", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            $booking_month = __('front.'.date('F',strtotime($request->start_date)));
                            $mailAppointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            $mail_content = 'Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$mailAppointmentTime;
                            
                            $notify_times = self::_getNotifyTime($request['start_date']);
                             
                            foreach ($notify_times as $notify_time) {
                                
                                $notify_data[] = array(
                                    'patient_id'    => $request->patient_id,
                                    'appointment_id'=> $collection->id,
                                    'title'         => 'Erinnerung an Ihren Termin',
                                    'content'       => $content,
                                    'notify_time'   => $notify_time,
                                    'status'        => 0,
                                );
                            }

                            if($this->AppointmentHasNotificationModel->insert($notify_data))
                            {
                                $all_transactions[] = 1;
                            }
                            else
                            {
                                
                                $all_transactions[] = 0;
                            } 
                           
                            $summary = $patientName." - ".$appointmentType;
                            $description = '<p><strong>Patient:</strong> '.$patientName.' </p><p><strong>Arzt:</strong> '.$doctorName.' </p><p><strong>Typ:</strong> '.$appointmentType.' </p><p><strong>Beginn:</strong> '.date('F d,Y H:i',strtotime($request->start_date)).' </p><strong>Ende:</strong> '.date('F d,Y H:i',strtotime($request->end_date)).' </p><p><strong>Notizen:</strong> '.$notes.' </p>';

                            $request = array(
                                'summary'=>$summary,
                                'description'=>$description,
                                'startDateTime'=>$request->start_date,
                                'endDateTime'=>$request->end_date,
                                'patient_id'=>$request->patient_id,
                                'patient_email'=>$collection->assignedPatient->email,
                                'patient_name'=>$patientName,
                                'doctor_email'=>$collection->assignedDoctor->email,
                                'color_id'=>$collection->assignedDoctor->google_color_id,
                                );
                            request()->merge($request);
                            // dd(request()->all());
                            $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventStore(request());
                            //$postResponse = json_decode($postCalDetails->data);
                            //dd($postCalDetails); 
                            if(!empty($postCalDetails) && $postCalDetails->original['status'] == 'success')
                            {
                                $all_transactions[] = 1;

                                $eventId = $postCalDetails->original['data']->id;
                                $collection->google_event_id = $eventId;
                                $collection->event_id = $eventId;
                                $collection->notes          = $notes;

                                if($collection->save()){
                                    $updateEvent = app('App\Http\Controllers\Admin\DashboardController')->appointmentIdUpdateInEvent($eventId, $collection->id);
                                    $all_transactions[] = 1;
                                }else{
                                    
                                    $all_transactions[] = 0;
                                }

                            }else{
                                
                                $all_transactions[] = 0;
                                $errors = $postCalDetails->original['msg'];
                            }
                           
                        }else{
                            
                            $all_transactions[] = 0;
                        }
                        //dd($all_transactions);
                        if (!in_array(0,$all_transactions)) 
                        {
                            DB::commit();
                            $status  = true;
                            $message = __('api.APPOINTMENT_BOOKED_SUCCESS');

                            if(!empty($country_code)){
                                $country_code = str_replace("00", "",$country_code);
                            }elseif(empty($country_code)){
                                $country_code = '43'; //Austria country code
                            }

                            $phone   = $country_code."".str_replace("-", "",$mobile_no);
                            $this->_sendSms($phone,$content);
                            if(!empty($patientEmail))
                            {
                               $this->_sendMail($patientName,$patientEmail,$mail_content);
                            }

                            $data[0]['id']          = $collection->id;
                            $data[0]['start_date']  = $collection->start_date;
                            $data[0]['end_date']    = $collection->end_date;
                            $data[0]['patient_id']  = $collection->patient_id;
                            $data[0]['doctor_id']   = $collection->doctor_id;
                            $data[0]['appointment_type_id']    = $collection->appointment_type_id;
                            $data[0]['appointment_type_name']  = $collection->assignedAppointmentType->name;
                            $data[0]['patient_name']    = $patientName;
                            $data[0]['doctor_name']     = $doctorName;
                            $data[0]['doctor_speciality']  = $collection->assignedDoctor->doctor_speciality;
                            $data[0]['doctor_image']       = $collection->assignedDoctor->img_path;

                            session(['appointmentData' =>'']);
                            // Set Check List Data
                            $chk_data = base64_encode(json_encode($data));
                            session(['chk_data' =>$chk_data]);
                            $this->JsonData['data']   = $data;
                            //$this->JsonData['url']    = url('/');
                            $this->JsonData['url']    = url('/online-appointment/get-check-list');
                            // $this->JsonData['msg']    = $message;
                            $this->JsonData['msg']    = '';
                            $this->JsonData['status'] = __('front.RESP_SUCCESS');

                            //$data[]  = $collection;
                            // self::_createLog('bookAppointment',$data,'info');
                            // $this->ActivityLogModel->addApiLog('Book Appointment','has book appointment','Create',null,$data);
                        }

                    }   

                }
                catch(\Exception $e) {
                    DB::rollback();
                    $errors = $e->getMessage();
                    $this->JsonData['errors'] = $errors; 
                    // self::_createLog('bookAppointment',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
            }
        }
        catch(\Exception $e) {
            DB::rollback();

            $errors = $e->getMessage();
            $this->JsonData['errors'] = $errors; 
        }
        //dd($this->JsonData);
        return response()->json($this->JsonData);
    }

    public function _storePatient($collection, $request) 
    { 
        $collection->salutation         = $request->salutation;
        $collection->title              = $request->title;
        $collection->first_name         = self::string_operation($request->first_name); 
        $collection->family_name        = self::string_operation($request->family_name);
        $collection->email              = $request->email;
        if(!empty($request->birth_date)){
            $birth_date                  = date('Y-m-d', strtotime($request->birth_date));
            $age                         = (date('Y') - date('Y',strtotime($birth_date)));
        }else{
            $birth_date                  = NULL;
            $age                         = 0;
        }
        
        $mobile_no                      = str_replace(" ", "", $request->mobile_no);
        $collection->mobile_no          = $mobile_no;
        $collection->country_code       = $request->country_code;
        if(!empty($request->format))
        {
           $collection->country_code       = $request->format; 
        }  
        $collection->birth_date         = $birth_date; 
        $collection->age         = $age; 

        $collection->road               = self::string_operation($request->road);
        $collection->street_no          = $request->street_no;
        $collection->postal_code        = $request->postal_code;
        $collection->place              = self::string_operation($request->place);
        $collection->gdpr               = $request->gdpr;
        $collection->update_ganydb      = 0;
        $collection->old_id             = 99999;

        //Save data
        $collection->save();    

        return $collection;    
    } 


    public function sendOtp(Request $request) 
    { 
        //dump($request->all());

        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
        try 
        {

            $collection = $this->PatientsModel
                                ->where('first_name',trim($request->first_name))
                                ->where('family_name',trim($request->family_name))
                                ->where('mobile_no','!=','')
                                ->first();
            //dd($collection,ltrim($collection->mobile_no,'0'));
            if(!empty($collection) && ltrim($collection->mobile_no,'0') == $request->mobile_no){

                if($collection->status==1){

                    $collection = $this->_updateOtp($collection); 

                    $this->JsonData['data']     = $collection->only(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','message']);
                    $this->JsonData['msg']      = 'SMS erfolgreich gesendet, Bitte geben Sie den SMS-Code ein, um Ihren Termin zu buchen.';
                    $this->JsonData['status']   = __('front.RESP_SUCCESS');

                }else{
                    $this->JsonData['msg']  = __('api.AUTH_INACTIVE_USER');
                }
            }else{
                $this->JsonData['msg']  = __('api.AUTH_INVALID_PATIENT');
            }                                     

        } catch (Exception $e) 
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);   
        
    }

    public function _updateOtp($collection){
        
        if(!empty($collection)){

            $otp_code = rand(1000, 9999);                                

            //update otp for the patient and send sms to the patient
            $password  = Hash::make($otp_code);

            $updateQry = DB::table('patients')
                            ->where('id', $collection->id)
                            ->update([
                                        'login_otp' => $otp_code,
                                        'password' => $password,
                                        // 'str_password' => $otp_code,
                                        'otp_created_at' => date('Y-m-d H:i:s')
                                    ]);
            $country_code = $collection->country_code;
            if(!empty($country_code)){
                $country_code = str_replace("00", "",$collection->country_code);
                $country_code = str_replace("+", "",$country_code);
            }elseif(empty($country_code)){
                $country_code = '43'; //Austria country code
            }

            $phone   = $country_code."".str_replace("-", "",$collection->mobile_no);
           // $message = 'Hallo '.$collection->first_name.' , Ihr Otp:'.$otp_code.' ist der Bestätigungscode für Ihre Registrierung, der 5 Minuten gültig ist ';
            $message = 'Hallo '.$collection->salutation.'.'.$collection->family_name.', lhr Login-Code für die PUREGYN-App lautet '.$otp_code.'. Er ist 5 Minuten gültig.';
            $collection->login_otp = $otp_code;
            $collection->message = $message;
            
            // dd($phone,$message);
           // $message .= "test message from puregyn api...please ignore.";
           $this->_sendSms($phone,$message);

        }
        
        return $collection; 
    }

    public function _sendMail($name,$email,$text)
    {
        $result = Mail::to($email)->send(new AppointmentMail($name,$text));
    }

    public function _failedLoginMail($email,$text)
    {
        $result = Mail::to($email)->cc(['eluminous_se41@eluminoustechnologies.com'])->send(new FailedAppointmentMail($text));
    }


    public function _sendSms($phones,$text){

     if(!empty($phones) && !empty($text))
     {
                                 
        $gateway_url      = config('constants.SMS_URL'); 
        $accessToken      = config('constants.SMS_TOKEN');

        $recipientAddressList = array($phones);
        $utf8_message_text    = $text;
        
        $maxSmsPerMessage     = 1; 
        $test                 = false; // true: do not send sms for real, just test interface

        $responseRecord = array(
                                'error' => 1 ,
                                'code'  =>  1,
                                'message'=> ''
                                );

      try {

        // 1.) -- Alternatively authenticate over access token
        $smsClient = new WebSmsCom_Client($accessToken, '', $gateway_url, WebSmsCom_AuthenticationMode::ACCESS_TOKEN);

        $smsClient->setVerbose(false);
        $smsClient->setSslVerifyHost(2); // needed if CURLOPT_SSL_VERIFYHOST 

       // 2.) -- create text message ----------------
        $message  = new WebSmsCom_TextMessage($recipientAddressList, $utf8_message_text);

        // 3.) -- send message ------------------
        $Response = $smsClient->send($message, $maxSmsPerMessage, $test);

        // return success
         $responseRecord = array(
                                'error'=>0,
                                'code' =>$Response->getStatusCode(), 
                                'message'=>$Response->getStatusMessage(),
                                'transferId'=>$Response->getTransferId(),
                               // 'messageId'=>$Response->getClientMessageId(),
                                );
        

        // catch everything that's not a successfully sent message
      } catch (WebSmsCom_ParameterValidationException $e) {
         $responseRecord = array(
                                'error' => 1 ,
                                'code'=>'1',
                                'message' => "ParameterValidationException caught: ".$e->getMessage()
                                );
        //exit("ParameterValidationException caught: ".$e->getMessage()."\n");
        
      } catch (WebSmsCom_AuthorizationFailedException $e) {
      //  exit("AuthorizationFailedException caught: ".$e->getMessage()."\n");
        $responseRecord = array(
                                'error' => 1 ,
                                'code'=>'1',
                                'message' => "AuthorizationFailedException caught: ".$e->getMessage()
                                );
      
      } catch (WebSmsCom_ApiException $e) {
       // echo $e; // possibility to handle API status codes $e->getCode()
       // exit("ApiException Exception\n");
        $responseRecord['message'] = "ApiException Exception: ".$e->getMessage();
        
      } catch (WebSmsCom_HttpConnectionException $e) {
       // exit("HttpConnectionException caught: ".$e->getMessage()."HTTP Status: ".$e->getCode()."\n");
         $responseRecord['message'] = "HttpConnectionException caught: ".$e->getMessage();
      
      } catch (WebSmsCom_UnknownResponseException $e) {
        
       // exit("UnknownResponseException caught: ".$e->getMessage()."\n");
          $responseRecord['message'] =  "UnknownResponseException caught: ".$e->getMessage();
        
      } catch (Exception $e) {
        
          $responseRecord['message'] =  "Exception caught: ".$e->getMessage();
        //exit("Exception caught: ".$e->getMessage()."\n");
      }

      $responseRecord['receipient'] = $recipientAddressList;
      
      //Log::info($responseRecord);

      return $responseRecord;
      
      }

  }



 public function getAllDoctorSlots(Request $request)
    {
       
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');

        try 
        {
            $appointment_type_id    = $request->appointment_type_id;
            $week_day_ids            = $request->week_day_id;
            $start_date  = date("Y-m-d",strtotime(str_replace("/","-", $request->start_date)));
            $end_date  = date("Y-m-d",strtotime(str_replace("/","-", $request->end_date)));
            $from_time              = $request->from_time;
            $to_time                = $request->to_time;
            //$day_of_week = date('N',strtotime($appointment_date));
          
            $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id);
            $appointmentDuration = 0;
            if(!empty($appointmentType)){
                $appointmentDuration = $appointmentType->duration * 60;//convert min into sec
            }
            $date_array = array();
            $doctors = $this->AdminUserModel
                            ->where('users.status', 1)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            }) 
                           // ->where('id','15')                           
                            ->get();
                           // dd($doctors);
            $roster_time_slots_date_wise = array();
            foreach($doctors as $dkey=> $dvalue)
            { 
                $doctor_id = $dvalue->id;
                $roster_time_slots_date_wise_doctor[$doctor_id] = [];
                $time_frames = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id')
                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                 ->whereDate('roster_has_dates.date','>=', $start_date)
                                 ->whereDate('roster_has_dates.date','<=', $end_date)
                                 ->whereIn('roster_has_weeks_has_time_frames.week_day_id',$week_day_ids)
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                             
                                ->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_dates.week_day_id']);
            // echo $day_of_week;
           // dd($start_date,$end_date,$time_frames->toArray());
            //  exit();
            
            $response = [];
           
            $msg =  __('api.ERR_TIME_FRAME_NOT_FOUND');

            $current_time = date("H:i",time());  
            $morning_time = date("H:i", mktime(12, 0));  
            $today_date = date("Y-m-d",time());  

            
           
           //for testing purpose
            // $appointment_date = '2020-05-05';
           //  var_dump(strtotime($today_date)==strtotime($appointment_date));
           // exit();
           $ignore_time_slots = [];

           
            if(!empty($time_frames) && count($time_frames)>0){
                $msg = '';
                foreach($time_frames as $time_frame)
                { 
                   

                    $roster_time_slots_date_wise[$time_frame->date]['weekday'] = $this->WeekDaysModel->where('id',$time_frame->week_day_id)->pluck('day')->first();
                    
                    //$response['duration'] = $default_time_duration;  

                    $time = date("H:i",strtotime($time_frame->time_frame)); 
                    $added_time_frame =  date("H:i",strtotime($time) + $appointmentDuration); 
                    $selected="";                    
                                       

                    $t= Carbon::parse($time)->format('H:i');
                    $ft= Carbon::parse($from_time)->format('H:i');
                    $to= Carbon::parse($to_time)->format('H:i');

                    if( $t >= $ft && $t <= $to)
                    { 
                        $doctor_appointment_time_frames = $this->BaseModel
                                ->where('doctor_id',$doctor_id)
                                ->whereDate('start_date',$time_frame->date) 
                                ->whereStatus(1)
                                ->select(DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date,(DATE_FORMAT(end_date,'%H:%i')) as end_date"))
                                ->get();

                       
                        if(!empty($doctor_appointment_time_frames))
                        {
                            foreach ($doctor_appointment_time_frames as $doctor_appointment_time_frame) 
                            { 

                                if(strtotime($time) < strtotime($doctor_appointment_time_frame->start_date) && strtotime($added_time_frame) > strtotime($doctor_appointment_time_frame->end_date) ){
                                        //case for 9:20-9:50 from booked 9:30-9:45
                                        $ignore_time_slots[$time_frame->date][] = $time;
                                        //dump($time.'1s condition');
                                }                          
                                if($time==$doctor_appointment_time_frame->start_date || ($added_time_frame>$doctor_appointment_time_frame->start_date && $added_time_frame<=$doctor_appointment_time_frame->end_date)){
                                    //case for begin date, inbetween, overide after add
                                    $ignore_time_slots[$time_frame->date][] = $time;
                                    //dump($time.'2nd condition');
                                }                            
                                if(($time>=$doctor_appointment_time_frame->start_date && $time<$doctor_appointment_time_frame->end_date)){
                                    $ignore_time_slots[$time_frame->date][] = $time; 
                                   // dump($time.'3rd condition');  
                                }
                            }
                        }     
                       
                        if (array_key_exists($time_frame->date,$ignore_time_slots))
                        {
                            //dump($time, $ignore_time_slots[$time_frame->date]);
                            if(!in_array($time, $ignore_time_slots[$time_frame->date])) 
                            {

                                if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                               
                                }
                                elseif(strtotime($today_date)!==strtotime($time_frame->date))
                                {                    
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                }
                            }
                        }else
                        {
                              if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                               
                                }elseif(strtotime($today_date)!=strtotime($time_frame->date))
                                {
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                }
                               
                        }

                        if(!empty($roster_time_slots_date_wise[$time_frame->date]['time_slots']))
                        {
                            $roster_time_slots_date_wise[$time_frame->date]['time_slots'] = array_unique($roster_time_slots_date_wise[$time_frame->date]['time_slots']);
                            //dump($roster_time_slots_date_wise[$time_frame->date]['time_slots'] );
                        }
                    }

                }                 
                 //   dump($ignore_time_slots);
            }
            }
            
            if(count($roster_time_slots_date_wise) >0 )
            {
                ksort($roster_time_slots_date_wise);
            }
           // dd($roster_time_slots_date_wise);//,$doctor_appointment_time_frames
                    /*<thead>
                              <tr class="main_head">
                                 <th colspan="3">
                                    <h3>Online Terminvereinbarung</h3>
                                 </th>
                              </tr>
                           </thead>*/
            $html= '<table id="customers">
                    <thead>
                        <tr>
                            <td colspan="3"  style="text-align: center;">Wählen Sie einen der verfügbaren
                                Termine für die von Ihnen gewählte Terminart <b>"'.$appointmentType->name.'"</b>
                                aus.
                            </td>
                        </tr>                       
                        <tr class="custMobThead">
                            <th width="50%">Datum</th>
                            <th>Uhrzeit</th>
                            <th>&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody><input type="hidden" name="hidden_week_day" id="hidden_week_day" value="'.implode(",",$week_day_ids).'"/>
                        
                    ';
            $msg =  __('admin.ERR_TIME_FRAME_NOT_FOUND');

            $index_key = 0;
            if(!empty($roster_time_slots_date_wise) && count($roster_time_slots_date_wise)>0){
                foreach($roster_time_slots_date_wise as $roster_date=>$roster_time_slot){   

                    if(!empty($roster_time_slot['time_slots']) && sizeof($roster_time_slot['time_slots'])>0){
                        
                        $select_rosters = '<div class="custMobileVisible">Uhrzeit</div><select 
                                        name="time_slot_'.$index_key.'" 
                                        id="time_slot_'.$index_key.'"  
                                        class="form-control select2" 
                                        >';

                        sort($roster_time_slot['time_slots']);
                        
                        foreach ( $roster_time_slot['time_slots'] as $time_slot) 
                        {
                            $available_dr = $this->RosterHasDatesModel
                                ->select('roster_has_weeks_has_time_frames.roster_id','roster.doctor_id')
                                ->leftjoin('roster','roster.id','roster_has_dates.roster_id')
                                ->leftjoin('roster_has_weeks_has_time_frames',function($query)
                                    {
                                        $query->on('roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id');
                                        $query->on('roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id');
                                    })
                                ->where('date','>=',$roster_date)
                                ->where('date','<=',$roster_date)
                                ->whereIn('roster_has_dates.week_day_id',$week_day_ids)
                                ->where('time_frame',$time_slot.":00")
                                ->get();

                                // if($time_slot == '13:40')
                                // {
                                //     dd($available_dr);
                                // }
                     

                                $available_time = 0;
                            if(!empty( $available_dr))
                            {
                                foreach($available_dr as $drkey=>$drvalue)
                                {
                                    $custom_date = $roster_date." ".$time_slot.":00";
                                    //  echo "=".$drvalue->doctor_id;
                                    $is_booked = $this->BaseModel
                                         ->whereDate('start_date',$roster_date)
                                        ->where('doctor_id',$drvalue->doctor_id)
                                        ->get();


                                    $is_booked = $is_booked->filter(function($inner_item) use($time_slot,$drvalue,$custom_date)
                                        {
                                            $time_slot = $time_slot;
                                            $s_date = date('h:i',strtotime($inner_item->start_date));
                                            $e_date = date('h:i',strtotime($inner_item->end_date));
                                            if($time_slot >= $s_date  && $time_slot < $e_date)
                                            {
                                              return true;
                                            }  
                                        });
                                   
                                  
                                       // echo count($is_booked);
                                    if(count($is_booked) == 0)
                                    {
                                        $available_time = 1;

                                    }
                                    // if($time_slot == '17:30')
                                    // {
                                    //     dump( $is_booked,$drvalue->doctor_id);
                                    // }

                                }
                                
                            }
                                // if($time_slot == '13:40')
                                // {
                                //     dd($available_time);
                                // }
                           
                            if($available_time == 1)
                            {
                                $select_rosters .='<option value="'.$time_slot.'" >'.$time_slot.'</option>';
                            }
                               
                            
                        }
                        $select_rosters .= '</select>
                        <input type="hidden" name="doctor_id" value="'.$doctor_id.'" />';
                        // dd($roster_date,$roster_time_slot['weekday']);
                        $html.='<tr>
                                    <td class="right2"><div class="custMobileVisible">Datum</div><b>'.$roster_time_slot['weekday'].'</b>, '.date('d.m.Y',strtotime($roster_date)).'</td>
                                    <td>'.$select_rosters.'</td>
                                    <td  class="card-footer"><button type="button" roster_date="'.$roster_date.'" class="btn btn-success" onclick="arrangeAllDoctorTimeSlot(this,'.$index_key.')">VEREINBAREN</button>
                                    </td>
                                </tr>';
                        
                        $index_key++;

                    }
                    
                    
                }

            }else{
                $html.='<tr>
                            <td class="right2" colspan="3"><b>'.$msg.'</b></td>
                        </tr>';
            }
            $html .= '</tbody></table>';
           
            $this->JsonData['html'] = $html;
            $this->JsonData['data'] = $roster_time_slots_date_wise;
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('front.RESP_SUCCESS');

        } catch (Exception $e) 
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);   
        
    }

    public function getDoctorSlots(Request $request)
    {
        //dump($request->all());
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');

        try 
        {
            $doctor_id              = $request->doctor_id;
            $appointment_type_id    = $request->appointment_type_id;
            $week_day_ids            = $request->week_day_id;
            $start_date  = date("Y-m-d",strtotime(str_replace("/","-", $request->start_date)));
            $end_date  = date("Y-m-d",strtotime(str_replace("/","-", $request->end_date)));
            $from_time              = $request->from_time;
            $to_time                = $request->to_time;


             $setting = $this->SettingsModel
                        ->where('id',12)
                        ->first(['setting_key','setting_value']);
            // dd($settings);
            if(!empty($setting)){
                $default_time_duration = $setting['setting_value'];                         
            }else{
                $default_time_duration = 10;                         
            } 
            //$day_of_week = date('N',strtotime($appointment_date));
          
            $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id);
            $appointmentDuration = 0;
            if(!empty($appointmentType)){
                $appointmentDuration = $appointmentType->duration * 60;//convert min into sec
            }
           
             $roster_time_slots_date_wise = array();
            // dump($doctor_time_slots_date_wise); 

           
            $time_frames = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id')
                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                 ->whereDate('roster_has_dates.date','>=', $start_date)
                                 ->whereDate('roster_has_dates.date','<=', $end_date)
                                 ->whereIn('roster_has_weeks_has_time_frames.week_day_id',$week_day_ids)
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                             
                                ->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_dates.week_day_id']);
            // echo $day_of_week;
           // dd($start_date,$end_date,$time_frames->toArray());
            //  exit();
            
            $response = [];
           
            $msg =  __('api.ERR_TIME_FRAME_NOT_FOUND');

            $current_time = date("H:i",time());  
            $morning_time = date("H:i", mktime(12, 0));  
            $today_date = date("Y-m-d",time());  

            
           
           //for testing purpose
            // $appointment_date = '2020-05-05';
           //  var_dump(strtotime($today_date)==strtotime($appointment_date));
           // exit();
           $ignore_time_slots = [];

           
            if(!empty($time_frames) && count($time_frames)>0){
                $msg = '';
                foreach($time_frames as $time_frame)
                { 
                   

                    $roster_time_slots_date_wise[$time_frame->date]['weekday'] = $this->WeekDaysModel->where('id',$time_frame->week_day_id)->pluck('day')->first();
                    
                    $response['duration'] = $default_time_duration;  

                    $time = date("H:i",strtotime($time_frame->time_frame)); 
                    $added_time_frame =  date("H:i",strtotime($time) + $appointmentDuration); 
                    $selected="";   

                   
                                       

                    $t= Carbon::parse($time)->format('H:i');
                    $ft= Carbon::parse($from_time)->format('H:i');
                    $to= Carbon::parse($to_time)->format('H:i');

                    if( $t >= $ft && $t <= $to)
                    { 
                        $doctor_appointment_time_frames = $this->BaseModel
                                ->where('doctor_id',$doctor_id)
                                ->whereDate('start_date',$time_frame->date) 
                                ->whereStatus(1)
                                ->select(DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date,(DATE_FORMAT(end_date,'%H:%i')) as end_date"))
                                ->get();

                       
                        if(!empty($doctor_appointment_time_frames))
                        {
                            foreach ($doctor_appointment_time_frames as $doctor_appointment_time_frame) 
                            { 

                                if(strtotime($time) < strtotime($doctor_appointment_time_frame->start_date) && strtotime($added_time_frame) > strtotime($doctor_appointment_time_frame->end_date) ){
                                        //case for 9:20-9:50 from booked 9:30-9:45
                                        $ignore_time_slots[$time_frame->date][] = $time;
                                        //dump($time.'1s condition');
                                }                          
                                if($time==$doctor_appointment_time_frame->start_date || ($added_time_frame>$doctor_appointment_time_frame->start_date && $added_time_frame<=$doctor_appointment_time_frame->end_date)){
                                    //case for begin date, inbetween, overide after add
                                    $ignore_time_slots[$time_frame->date][] = $time;
                                    //dump($time.'2nd condition');
                                }                            
                                if(($time>=$doctor_appointment_time_frame->start_date && $time<$doctor_appointment_time_frame->end_date)){
                                    $ignore_time_slots[$time_frame->date][] = $time; 
                                   // dump($time.'3rd condition');  
                                }
                            }
                        }     
                       
                        if (array_key_exists($time_frame->date,$ignore_time_slots))
                        {
                            //dump($time, $ignore_time_slots[$time_frame->date]);
                            if(!in_array($time, $ignore_time_slots[$time_frame->date])) 
                            {

                                if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                               
                                }
                                elseif(strtotime($today_date)!==strtotime($time_frame->date))
                                {                    
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                }
                            }
                        }else
                        {
                              if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                               
                                }elseif(strtotime($today_date)!=strtotime($time_frame->date))
                                {
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                }
                               
                        }

                        if(!empty($roster_time_slots_date_wise[$time_frame->date]['time_slots']))
                        {
                            $roster_time_slots_date_wise[$time_frame->date]['time_slots'] = array_unique($roster_time_slots_date_wise[$time_frame->date]['time_slots']);
                            //dump($roster_time_slots_date_wise[$time_frame->date]['time_slots'] );
                        }
                    }

                }                 
                 //   dump($ignore_time_slots);
            }
            if(!empty($roster_time_slots_date_wise))
            {
                ksort($roster_time_slots_date_wise);

            }
        // dd($roster_time_slots_date_wise);//,$doctor_appointment_time_frames
                    /*<thead>
                              <tr class="main_head">
                                 <th colspan="3">
                                    <h3>Online Terminvereinbarung</h3>
                                 </th>
                              </tr>
                           </thead>*/
            $html= '<table id="customers">
                    <thead>
                        <tr>
                            <td colspan="3"  style="text-align: center;">Wählen Sie einen der verfügbaren
                                Termine für die von Ihnen gewählte Terminart <b>"'.$appointmentType->name.'"</b>
                                aus.
                            </td>
                        </tr>                       
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
            if(!empty($roster_time_slots_date_wise) && count($roster_time_slots_date_wise)>0){
                foreach($roster_time_slots_date_wise as $roster_date=>$roster_time_slot){   

                    if(!empty($roster_time_slot['time_slots']) && sizeof($roster_time_slot['time_slots'])>0){
                        
                        $select_rosters = '<div class="custMobileVisible">Uhrzeit</div><select 
                                        name="time_slot_'.$index_key.'" 
                                        id="time_slot_'.$index_key.'"  
                                        class="form-control select2" 
                                        >';
                        sort($roster_time_slot['time_slots']);
                        foreach ($roster_time_slot['time_slots'] as $time_slot) {
                             $select_rosters .='<option value="'.$time_slot.'">'.$time_slot.'</option>';
                        }
                        $select_rosters .= '</select>';
                        // dd($roster_date,$roster_time_slot['weekday']);
                        $html.='<tr>
                                    <td class="right2"><div class="custMobileVisible">Datum</div><b>'.$roster_time_slot['weekday'].'</b>, '.date('d.m.Y',strtotime($roster_date)).'</td>
                                    <td>'.$select_rosters.'</td>
                                    <td  class="card-footer"><button type="button" roster_date="'.$roster_date.'" class="btn btn-success" onclick="arrangeTimeSlot(this,'.$index_key.')">VEREINBAREN</button>
                                    </td>
                                </tr>';
                        
                        $index_key++;

                    }
                    
                    
                }

            }else{
                $html.='<tr>
                            <td class="right2" colspan="3"><b>'.$msg.'</b></td>
                        </tr>';
            }
            $html .= '</tbody></table>';
           
            $this->JsonData['html'] = $html;
            $this->JsonData['data'] = $roster_time_slots_date_wise;
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('front.RESP_SUCCESS');

        } catch (Exception $e) 
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);   
        
    }

    public function tempCheck()
    {
        $basePath = 'storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/document_pdf/STD-Screening_1625833614.pdf';
        //dd($basePath);
        $headers = array(
        'Content-Type: application/pdf',
        );

        return Response::download($basePath, 'STD-Screening_1625833614.pdf', $headers);
        
    }
    
   
} 
