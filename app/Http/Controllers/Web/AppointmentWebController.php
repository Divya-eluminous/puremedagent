<?php
namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

// Models

use App\Models\PatientsModel;
use App\Models\AdminUserModel;
use App\Models\AppointmentTypesModel;
use App\Models\OldPatientsModel;
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
use App\Models\ChannelsRemindersSettingModel;
use App\Models\EventTypeHasExaminationsModel;
use App\Models\AppointmentTypeHasNonExaminationsModel;
use App\Models\CountryCodesModel; //Roshani added on 13-06-2024 for country code
use App\Models\DeletedAppointmentTrackModel; //used to archive appointments before hard delete
use App\Models\PatientsHasOldFindingModel; //used in delete-account cascade
use App\Models\PatientsHasDismissalModel; //used in delete-account cascade
use PDF;
// Request
use App\Http\Requests\Web\RegisterPatientRequest;
use App\Http\Requests\Web\ForgotPasswordRequest;
use App\Http\Requests\Web\ResetPasswordRequestWeb;

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
use App\Models\PatientHasReminder;
use App\Models\RosterHasWeeksHasTimeFramesModel;

use \Illuminate\Auth\Passwords\PasswordBroker;
use App\Mail\ForgotPasswordMailWeb;
use App\PasswordReset;
use App\Mail\ConfirmAppointmentWeb;

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
use stdClass;
use App;

use App\Models\UserHasAppointmentType; //added on 8-may-24

use App\Mail\OtpWebAppointment;  //added on 13-may-24

use App\Models\PatientsOtpModel; //added on 14-may-24
use App\Models\ActivityLogModel; //Roshani added on 05-08-2024

use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

use Illuminate\Support\Facades\Http; //added on 27-may-25 for header footer image

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
        OldPatientsModel $OldPatientsModel,
        WeekDaysModel $WeekDaysModel,
        SettingsModel $SettingsModel,
        PatientHasDocumentsModel $PatientHasDocumentsModel,
        AppointmentHasNotificationModel $AppointmentHasNotificationModel,
        RosterHasDatesModel $RosterHasDatesModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
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
        OrdinationHasSpecialistModel $OrdinationHasSpecialistModel,
        PatientHasReminder $PatientHasReminder,
        EventTypeHasExaminationsModel $EventTypeHasExaminationsModel,
        RosterHasWeeksHasTimeFramesModel $RosterHasWeeksHasTimeFramesModel,
        AppointmentTypeHasNonExaminationsModel $AppointmentTypeHasNonExaminationsModel,
        PasswordBroker $PasswordBroker,
        PasswordReset $PasswordResetModel,
        UserHasAppointmentType $UserHasAppointmentType,
        PatientsOtpModel $PatientsOtpModel,
        ActivityLogModel $ActivityLogModel, //Roshani added on 05-08-2024
        CountryCodesModel $CountryCodesModel, //Roshani added on 13-06-2024 for country code
        DeletedAppointmentTrackModel $DeletedAppointmentTrackModel, //used to archive appointments before hard delete
        PatientsHasOldFindingModel $PatientsHasOldFindingModel, //used in delete-account cascade
        PatientsHasDismissalModel $PatientsHasDismissalModel //used in delete-account cascade



    )
    {
        $this->BaseModel = $AppointmentModel;
        $this->DeletedAppointmentTrackModel = $DeletedAppointmentTrackModel;
        $this->PatientsHasOldFindingModel = $PatientsHasOldFindingModel;
        $this->PatientsHasDismissalModel = $PatientsHasDismissalModel;
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
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
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
        $this->PatientHasReminder  = $PatientHasReminder;
        $this->EventTypeHasExaminationsModel = $EventTypeHasExaminationsModel;
        $this->RosterHasWeeksHasTimeFramesModel = $RosterHasWeeksHasTimeFramesModel;
        $this->OldPatientsModel = $OldPatientsModel;
        $this->AppointmentTypeHasNonExaminationsModel = $AppointmentTypeHasNonExaminationsModel;
        $this->PasswordBroker = $PasswordBroker;
        $this->PasswordResetModel = $PasswordResetModel;
        $this->UserHasAppointmentType   = $UserHasAppointmentType;
        $this->PatientsOtpModel = $PatientsOtpModel;
        $this->ActivityLogModel  = $ActivityLogModel; //Roshani added on 05-08-2024
        $this->CountryCodesModel = $CountryCodesModel; //Roshani added on 13-06-2024 for country code
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

      public function testpdf()
    {

        // $file='https://puregyn.puremed.biz/storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/check_list_pdf/Varg_VERH_16-01-24.pdf';

         // $file='storage/app/public/tenancy/tenants/99c841cba21040218437726d397d936b/advertisement_rep_16-01-2024.pdf';
         // return response()->download($file);

         return view('web.pdf');
    }

     public function uploadtestpdf(Request $request)
    {
       // dump('innnnnnnn');
       // dump($request->all());

      // if (request()->has('pdf'))
       //{
          $pdfuploaded = request()->file('pdf');
          // dump($pdfuploaded);

          $pdfname = $request->book_name . time() . '.' . $pdfuploaded->getClientOriginalExtension();
          // dump($pdfname);

          $pdfpath = public_path('/uploads/pdf');
          $pdfuploaded->move($pdfpath, $pdfname);

          // dump('after move ..');

          $book->book_file = '/uploads/pdf/' . $pdfname;
          $pdf = $book->book_file;
          // dd($pdf);

          $pdfO = new Spatie\PdfToImage\Pdf($pdfpath . '/' . $pdfname);
          $thumbnailPath = public_path('/uploads/thumbnails');

          // dump($thumbnailPath);

          $thumbnail = $pdfO->setPage(1)
            ->setOutputFormat('png')
            ->saveImage($thumbnailPath . '/' . 'YourFileName.png');
          // This is where you save the cover path to your database.
     // }

    }//uploadtestpdf

      public function downloadpdf()
    {

        // $file='https://puregyn.puremed.biz/storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/check_list_pdf/Varg_VERH_16-01-24.pdf';

          $file='storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/check_list_pdf/Varg_VERH_16-01-24.pdf';
          return response()->download($file);

    }

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

        /********start*****commented below code on 6-sept-24*********/
       /* $doctors = $this->AdminUserModel
                    ->where('users.status', 1)
                    ->whereHas('roles',function($query){
                        $query->where('name', 'doctor');
                    })
                    ->get();*/

        /********end********************************/

        //changed below code on 6-sept-24
        $doctors = $this->AdminUserModel
                            ->join('roster','roster.doctor_id','=','users.id')
                            ->join('roster_has_dates','roster_has_dates.roster_id','=','roster.id')
                            ->where('users.status', 1)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->whereDate('roster_has_dates.date','>=',date('Y-m-d'))
                            ->groupBy('users.id')
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

        //start added on 18-oct-24
        $webHomeContent = $this->SettingsModel->where('setting_key', 'WEBBUCHUNG_TEXT_STARTSEITE')->first(['setting_value']);
        $gdprData = '';
        if(!empty($webHomeContent)){
            $gdprData = $webHomeContent->setting_value;

        }
        $this->ViewData['webContent'] = $gdprData;
        //end added on 18-oct-24

        return view($this->ModulePath.'home', $this->ViewData);
    }

    public function index_old($enc_doctor_id=false)
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
                                                    ->where('dynamic_appointment', 0)
                                                    ->get();
        // $this->ViewData['addButton']    = __('admin.TITLE_ADD_BUTTON').' '.str_singular($this->ModuleTitle);

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }
    // Smart Appointment 15-Sep-22 ======


    public function index($enc_doctor_id=false,$hidden_service_id=false)
    {
        Log::info("in index function"); 
        session(['exam_arr' =>'']);

        $is_already_registered=0;
        //get session patient login data
        $session = json_decode(base64_decode(session('loginPatientData')));

        $appointment=[];
        if(!empty($session)){

          $is_already_registered=1;

          $appointment['patient_id']  = $session->patient_id;
          $appointment['otp_code']  = $session->otp_code;
        }else{
           // return redirect('/');
        }

        //code shifted above
        // below added on 18-dec-23 (29-feb-24) for appointment type selection
        $hiden_service_id =$serviceName='';
        if(isset($hidden_service_id) && !empty($hidden_service_id))
        {
            $getExamName = $this->ExaminationsModel->find(base64_decode($hidden_service_id));

            if(isset($getExamName)){
                  $serviceName = $getExamName->name;
                  $hiddenAppTypeArr =  $this->AppointmentTypesModel
                  //->where('status', 1) //commented on 29-may-24 for showing app type
                  ->where('name',$serviceName)
                  ->first();
                  if(isset($hiddenAppTypeArr) && !empty($hiddenAppTypeArr)){
                     $hidenApptypeId = $hiddenAppTypeArr->id;
                  }
            }

        }//

        $this->ViewData['hidenApptypeId'] = isset($hidenApptypeId)?$hidenApptypeId:'';
        $this->ViewData['hidden_service_id'] = isset($hidden_service_id)?base64_decode($hidden_service_id):'';
        //above code added on 18-dec-23 (29-feb-24) for appointment type selection
        //code shifted above



        $doctor_id = '';
        // if(!empty($enc_doctor_id)){  //commented on 20-feb-24
        if(!empty($enc_doctor_id) && base64_decode($enc_doctor_id)!='null')//changed on 18-dec-23 (29-feb-24)
         {

            $doctor_id = base64_decode(base64_decode($enc_doctor_id));

             //commented below qry on 30-jan-23
           /* $doctors = $this->AdminUserModel
                            ->where('id',$doctor_id)
                            ->where('status', 1)
                            ->get();*/

             //added below qry on 30-jan-23
             $doctors = $this->AdminUserModel
                            ->join('roster','roster.doctor_id','=','users.id')
                            ->join('roster_has_dates','roster_has_dates.roster_id','=','roster.id')
                            ->where('users.id',$doctor_id)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->whereDate('roster_has_dates.date','>=',date('Y-m-d'))
                            ->where('users.status', 1)
                            ->groupBy('users.id')
                            ->get(['users.*']);

        }else{

            //commented below code on 4-sept-24
           /* $doctors = $this->AdminUserModel
                            ->join('roster','roster.doctor_id','=','users.id')
                            ->join('roster_has_dates','roster_has_dates.roster_id','=','roster.id')
                            ->where('users.status', 1)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->whereDate('roster_has_dates.date','>=',date('Y-m-d'))
                            ->groupBy('users.id')
                            ->get(['users.*']);*/

           //changed below code on 5-sept-24
            if(isset($hidden_service_id) && !empty($hidden_service_id))
            {
                //get blocked users of selected appointment type
                $blockedUsers = $this->UserHasAppointmentType->select('user_id')->where('appointment_type_id', $hidenApptypeId)->pluck('user_id')->toArray();


                //get only those doctors by excluding blocked users of selected app type
                $doctors = $this->AdminUserModel
                            ->join('roster','roster.doctor_id','=','users.id')
                            ->join('roster_has_dates','roster_has_dates.roster_id','=','roster.id')
                            ->where('users.status', 1)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->whereDate('roster_has_dates.date','>=',date('Y-m-d'))
                            ->whereNotIn('users.id', $blockedUsers)
                            ->groupBy('users.id')
                            ->get(['users.*']);


            }//if hidden_service_id
            else
            {
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
            }//else
           //changed above code on 5-sept-24


        }//else

        // $this->ModuleTitle              =  __('admin.TITLE_WAITING_QUEUE_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;


        $this->ViewData['doctors'] = $doctors;
        $this->ViewData['doctor_id'] = $doctor_id;




       // dd($this->ViewData['hidenApptypeId']);


        $this->ViewData['weekdays']     = $this->WeekDaysModel
                                        ->where('status', 1)
                                        ->get();
        //All appointment types

        //commented below code on 8-may-24
        /*$this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get(); */



        //start added below code on 8-may-24 for if doctor id then restrict appointent type
        if(!empty($enc_doctor_id) && base64_decode($enc_doctor_id)!='null')
        {
             $doctorId = base64_decode(base64_decode($enc_doctor_id));

             $userAppointmentTypes = $this->UserHasAppointmentType::where('user_id', $doctorId)->pluck('appointment_type_id')->toArray();

             if(isset($userAppointmentTypes) && !empty($userAppointmentTypes))
             {
                 $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->whereNotIn('id',$userAppointmentTypes)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get();
             }else{
                 $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get();
             }


        }else{

            //commented below code on 29-may-24 to show inactive app type
            /* $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get(); */

            //changed below code added if condition on 29-may-24 to show url app type if its active or inactive
            if (isset($hidenApptypeId) && !empty($hidenApptypeId)) {
                $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                        ->where(function ($query) use ($hidenApptypeId) {
                            $query->where('status', 1)
                                  ->where('dynamic_appointment', 0)
                                  ->orWhere(function ($query) use ($hidenApptypeId) {
                                      $query->where('id', $hidenApptypeId);
                                  });
                        })
                        ->get();
            }
            else
            {
                $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get();
            }//else
            //changed below code on 29-may-24

        }
        //end added below code on 8-may-24 for if doctor id then restrict appointent type


       $this->ViewData['appointment'] = $appointment;
       $this->ViewData['is_already_registered'] = $is_already_registered;


       //Take settings for booking timeframe and quarter setting
       $appoinmentStartDate = $appointmentEndDate ='';
       $bookingtimeframe = $this->SettingsModel->where('setting_key','BOOKING_TIMEFRAME')->first();
       if(isset($bookingtimeframe) && !empty($bookingtimeframe))
       {
             $appoinmentStartDate = date('Y-m-d');

             $setting_value = $bookingtimeframe->setting_value;
             $description = $bookingtimeframe->description;

             /*if($description=="month")
             {
                 $appointmentEndDate =  date('Y-m-d', strtotime("+$setting_value months", strtotime($appoinmentStartDate)));
             }

              if($description=="week")
             {

                 $appointmentEndDate =  date('Y-m-d',strtotime("+$setting_value week",strtotime($appoinmentStartDate)));
             }//week
             */

            //Added below code on 30-jan-23 for doctor id selection


              // if(isset($enc_doctor_id) && !empty($enc_doctor_id) && isset($doctors) && !empty($doctors->toArray())) //commented on 29-feb-24

              if(isset($enc_doctor_id) && !empty($enc_doctor_id) && base64_decode($enc_doctor_id)!='null' && isset($doctors) && !empty($doctors->toArray()))   //added enc_doctor_id in 18-dec-23 (29-feb-24)
             {

                $todaysdate = date('Y-m-d');
                $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$doctor_id and STATUS=1 AND start_date>='$todaysdate'");
                $appointdatesarr = isset($getStartDates[0]->appointment_dates)?$getStartDates[0]->appointment_dates:0;
                  $data = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                    $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
                  })->where('roster.doctor_id', $doctor_id)
                  ->where('roster_has_dates.is_excluded', '=', 0)
                  ->where('roster_has_dates.date', '>=', $todaysdate)
                  ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                  ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))
                  ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")
                  ->groupBy('roster_has_dates.date')
                  ->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);

                if(isset($data) && !empty($data))
                {
                    $avaliable_date1 = $data->date;
                    $appoinmentStartDate =  date("Y-m-d", strtotime($avaliable_date1));
                    if($description=="week")
                    {
                        $appointmentEndDate =  date('Y-m-d', strtotime($appoinmentStartDate. ' + '.$setting_value.' week'));
                    }
                    elseif($description=="month")
                    {
                        $appointmentEndDate =  date('Y-m-d', strtotime($appoinmentStartDate. ' + '.$setting_value.' month'));
                    }
                }else{
                      if($description=="month")
                     {
                         $appointmentEndDate =  date('Y-m-d', strtotime("+$setting_value months", strtotime($appoinmentStartDate)));
                     }

                      if($description=="week")
                     {

                         $appointmentEndDate =  date('Y-m-d',strtotime("+$setting_value week",strtotime($appoinmentStartDate)));
                     }//week

                 } //else

             }//if doctor id
             else
             {
                 if($description=="month")
                 {
                     $appointmentEndDate =  date('Y-m-d', strtotime("+$setting_value months", strtotime($appoinmentStartDate)));
                 }

                  if($description=="week")
                 {
                     $appointmentEndDate =  date('Y-m-d',strtotime("+$setting_value week",strtotime($appoinmentStartDate)));
                 }//week

             }//else of doctor id
            //End of Added below code on 30-jan-23 for doctor id selection


       }//bookingtimeframe



       $this->ViewData['appoinmentStartDate'] = isset($appoinmentStartDate)?date("d/m/Y",strtotime($appoinmentStartDate)):'';
       $this->ViewData['appointmentEndDate'] = isset($appointmentEndDate)?date("d/m/Y",strtotime($appointmentEndDate)):'';

       $quarter_setting = $this->SettingsModel->where('setting_key','OPTIMAL_APPOINTMENT')->first();
       $this->ViewData['quarter_setting'] = isset($quarter_setting)?$quarter_setting:[];


     //Added on 14-sept-22 fro quarter setting flag
      $quarter_setting_val=0;
      if(isset($quarter_setting) && !empty($quarter_setting))
      {
        $quarter_setting_val = $quarter_setting->setting_value;
      }
      $this->ViewData['quarter_setting_val'] = $quarter_setting_val;

       //dd($this->ViewData);
        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }//index
    // public function index($enc_doctor_id=false) //commented on 29-feb-24
    public function index_new_30_aug_24($enc_doctor_id=false,$hidden_service_id=false)
    {
        $is_already_registered=0;
        //get session patient login data
        $session = json_decode(base64_decode(session('loginPatientData')));

        $appointment=[];
        if(!empty($session)){

          $is_already_registered=1;

          $appointment['patient_id']  = $session->patient_id;
          $appointment['otp_code']  = $session->otp_code;
        }else{
           // return redirect('/');
        }

        //Shifted below code above for app type change get doctor in the dropdown
        // below added on 18-dec-23 (29-feb-24) for appointment type selection
        $hiden_service_id =$serviceName='';
        if(isset($hidden_service_id) && !empty($hidden_service_id))
        {
            $getExamName = $this->ExaminationsModel->find(base64_decode($hidden_service_id));

            if(isset($getExamName)){
                  $serviceName = $getExamName->name;
                  $hiddenAppTypeArr =  $this->AppointmentTypesModel
                  //->where('status', 1) //commented on 29-may-24 for showing app type
                  ->where('name',$serviceName)
                  ->first();
                  if(isset($hiddenAppTypeArr) && !empty($hiddenAppTypeArr)){
                     $hidenApptypeId = $hiddenAppTypeArr->id;
                  }
            }

        }//

        $this->ViewData['hidenApptypeId'] = isset($hidenApptypeId)?$hidenApptypeId:'';
        $this->ViewData['hidden_service_id'] = isset($hidden_service_id)?base64_decode($hidden_service_id):'';
        //above code added on 18-dec-23 (29-feb-24) for appointment type selection

       // dd($this->ViewData['hidenApptypeId']);



        $doctor_id = '';
        // if(!empty($enc_doctor_id)){  //commented on 20-feb-24
        if(!empty($enc_doctor_id) && base64_decode($enc_doctor_id)!='null')//changed on 18-dec-23 (29-feb-24)
         {

            $doctor_id = base64_decode(base64_decode($enc_doctor_id));

             //commented below qry on 30-jan-23
           /* $doctors = $this->AdminUserModel
                            ->where('id',$doctor_id)
                            ->where('status', 1)
                            ->get();*/

             //added below qry on 30-jan-23
             $doctors = $this->AdminUserModel
                            ->join('roster','roster.doctor_id','=','users.id')
                            ->join('roster_has_dates','roster_has_dates.roster_id','=','roster.id')
                            ->where('users.id',$doctor_id)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->whereDate('roster_has_dates.date','>=',date('Y-m-d'))
                            ->where('users.status', 1)
                            ->groupBy('users.id')
                            ->get(['users.*']);

        }else{

            //commented below code on 30-aug-24
            /*$doctors = $this->AdminUserModel
                            ->join('roster','roster.doctor_id','=','users.id')
                            ->join('roster_has_dates','roster_has_dates.roster_id','=','roster.id')
                            ->where('users.status', 1)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->whereDate('roster_has_dates.date','>=',date('Y-m-d'))
                            ->groupBy('users.id')
                            ->get(['users.*']);*/

             /*********changed below code on 30-aug-24******************/

            if(isset($hidden_service_id) && !empty($hidden_service_id) && isset($hidenApptypeId))
            {
                //get blocked users of selected appointment type
                $blockedUsers = $this->UserHasAppointmentType->select('user_id')->where('appointment_type_id', $hidenApptypeId)->pluck('user_id')->toArray();

                //get only those doctors by excluding blocked users of selected app type
                 $doctors = $this->AdminUserModel
                            ->join('roster','roster.doctor_id','=','users.id')
                            ->join('roster_has_dates','roster_has_dates.roster_id','=','roster.id')
                            ->where('users.status', 1)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->whereDate('roster_has_dates.date','>=',date('Y-m-d'))
                            ->whereNotIn('users.id', $blockedUsers)
                            ->groupBy('users.id')
                            ->get(['users.*']);

                // dd($doctors);
            }
            else
            {
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
            }//else

            /************changed above code on 30-aug-24*****************/

        }//else

        // $this->ModuleTitle              =  __('admin.TITLE_WAITING_QUEUE_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;


        $this->ViewData['doctors'] = $doctors;
        $this->ViewData['doctor_id'] = $doctor_id;





        $this->ViewData['weekdays']     = $this->WeekDaysModel
                                        ->where('status', 1)
                                        ->get();
        //All appointment types

        //commented below code on 8-may-24
        /*$this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get(); */



        //start added below code on 8-may-24 for if doctor id then restrict appointent type
        if(!empty($enc_doctor_id) && base64_decode($enc_doctor_id)!='null')
        {
             $doctorId = base64_decode(base64_decode($enc_doctor_id));

             $userAppointmentTypes = $this->UserHasAppointmentType::where('user_id', $doctorId)->pluck('appointment_type_id')->toArray();

             if(isset($userAppointmentTypes) && !empty($userAppointmentTypes))
             {
                 $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->whereNotIn('id',$userAppointmentTypes)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get();
             }else{
                 $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get();
             }


        }else{

            //commented below code on 29-may-24 to show inactive app type
            /* $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get(); */

            //changed below code added if condition on 29-may-24 to show url app type if its active or inactive
            if (isset($hidenApptypeId) && !empty($hidenApptypeId)) {
                $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                        ->where(function ($query) use ($hidenApptypeId) {
                            $query->where('status', 1)
                                  ->where('dynamic_appointment', 0)
                                  ->orWhere(function ($query) use ($hidenApptypeId) {
                                      $query->where('id', $hidenApptypeId);
                                  });
                        })
                        ->get();
            }
            else
            {
                $this->ViewData['appointment_type'] = $this->AppointmentTypesModel
                                                    ->where('status', 1)
                                                    ->where('dynamic_appointment', 0)
                                                    ->get();
            }//else
            //changed below code on 29-may-24

        }
        //end added below code on 8-may-24 for if doctor id then restrict appointent type


       $this->ViewData['appointment'] = $appointment;
       $this->ViewData['is_already_registered'] = $is_already_registered;


       //Take settings for booking timeframe and quarter setting
       $appoinmentStartDate = $appointmentEndDate ='';
       $bookingtimeframe = $this->SettingsModel->where('setting_key','BOOKING_TIMEFRAME')->first();
       if(isset($bookingtimeframe) && !empty($bookingtimeframe))
       {
             $appoinmentStartDate = date('Y-m-d');

             $setting_value = $bookingtimeframe->setting_value;
             $description = $bookingtimeframe->description;

             /*if($description=="month")
             {
                 $appointmentEndDate =  date('Y-m-d', strtotime("+$setting_value months", strtotime($appoinmentStartDate)));
             }

              if($description=="week")
             {

                 $appointmentEndDate =  date('Y-m-d',strtotime("+$setting_value week",strtotime($appoinmentStartDate)));
             }//week
             */

            //Added below code on 30-jan-23 for doctor id selection


              // if(isset($enc_doctor_id) && !empty($enc_doctor_id) && isset($doctors) && !empty($doctors->toArray())) //commented on 29-feb-24

              if(isset($enc_doctor_id) && !empty($enc_doctor_id) && base64_decode($enc_doctor_id)!='null' && isset($doctors) && !empty($doctors->toArray()))   //added enc_doctor_id in 18-dec-23 (29-feb-24)
             {

                $todaysdate = date('Y-m-d');
                $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$doctor_id and STATUS=1 AND start_date>='$todaysdate'");
                $appointdatesarr = isset($getStartDates[0]->appointment_dates)?$getStartDates[0]->appointment_dates:0;
                  $data = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                    $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
                  })->where('roster.doctor_id', $doctor_id)
                  ->where('roster_has_dates.is_excluded', '=', 0)
                  ->where('roster_has_dates.date', '>=', $todaysdate)
                  ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                  ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))
                  ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")
                  ->groupBy('roster_has_dates.date')
                  ->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);

                if(isset($data) && !empty($data))
                {
                    $avaliable_date1 = $data->date;
                    $appoinmentStartDate =  date("Y-m-d", strtotime($avaliable_date1));
                    if($description=="week")
                    {
                        $appointmentEndDate =  date('Y-m-d', strtotime($appoinmentStartDate. ' + '.$setting_value.' week'));
                    }
                    elseif($description=="month")
                    {
                        $appointmentEndDate =  date('Y-m-d', strtotime($appoinmentStartDate. ' + '.$setting_value.' month'));
                    }
                }else{
                      if($description=="month")
                     {
                         $appointmentEndDate =  date('Y-m-d', strtotime("+$setting_value months", strtotime($appoinmentStartDate)));
                     }

                      if($description=="week")
                     {

                         $appointmentEndDate =  date('Y-m-d',strtotime("+$setting_value week",strtotime($appoinmentStartDate)));
                     }//week

                 } //else

             }//if doctor id
             else
             {
                 if($description=="month")
                 {
                     $appointmentEndDate =  date('Y-m-d', strtotime("+$setting_value months", strtotime($appoinmentStartDate)));
                 }

                  if($description=="week")
                 {
                     $appointmentEndDate =  date('Y-m-d',strtotime("+$setting_value week",strtotime($appoinmentStartDate)));
                 }//week

             }//else of doctor id
            //End of Added below code on 30-jan-23 for doctor id selection


       }//bookingtimeframe



       $this->ViewData['appoinmentStartDate'] = isset($appoinmentStartDate)?date("d/m/Y",strtotime($appoinmentStartDate)):'';
       $this->ViewData['appointmentEndDate'] = isset($appointmentEndDate)?date("d/m/Y",strtotime($appointmentEndDate)):'';

       $quarter_setting = $this->SettingsModel->where('setting_key','OPTIMAL_APPOINTMENT')->first();
       $this->ViewData['quarter_setting'] = isset($quarter_setting)?$quarter_setting:[];


     //Added on 14-sept-22 fro quarter setting flag
      $quarter_setting_val=0;
      if(isset($quarter_setting) && !empty($quarter_setting))
      {
        $quarter_setting_val = $quarter_setting->setting_value;
      }
      $this->ViewData['quarter_setting_val'] = $quarter_setting_val;

       //dd($this->ViewData);
        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }//index new

    public function arrangeTimeSlot_old(Request $request)
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
                        'roster_time_slot_hd_id' => $request->roster_time_slot_hd_id,
                        'dr_type' => $request->doctors_slot_type,
                   ];

            //dd($reqdata);
            $requested_data = base64_encode(json_encode($reqdata));
            session(['appointmentData' =>$requested_data]);

            $this->JsonData['data']     = $reqdata;
            $this->JsonData['url']      = url('/online-appointment/login');
            $this->JsonData['msg']      = '';
            $this->JsonData['status']   = __('front.RESP_SUCCESS');
        }
        catch (Exception $e)
        {
            $this->JsonData['exception'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }
    // Smart Appointment 15-Sep-22 Added By divya======
    public function arrangeTimeSlot(Request $request)
    {
       // dd($request->all());

        Log::info("in arrangeTimeSlot function..");
        Log::info($request->all());


        $week_array = explode(",",$request->hidden_week_day);
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
        try
        {
            if($request->doctor_id==0)
            {
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
                        'roster_time_slot_hd_id' => $request->roster_time_slot_hd_id,
                        'dr_type' => $request->doctors_slot_type,
                   ];

             $requested_data = base64_encode(json_encode($reqdata));
             session(['appointmentData' =>$requested_data]);


             //Check if patient is logged in other wise redirect it to the register
             $loginPatientsession = json_decode(base64_decode(session('loginPatientData')));
             $sessionAppointmentData = json_decode(base64_decode(session('appointmentData')));



             //comment code for registration flow
             if(empty($loginPatientsession) && isset($sessionAppointmentData) && $request->is_already_registered==0)
             {


                //If patient fill appointment form and login of patient is empty and isregistered is 0 then go to register

                $this->JsonData['data']     = "";
                $this->JsonData['url']      = url('/online-appointment/register');
                $this->JsonData['msg']      = '';
                $this->JsonData['status']   = __('front.RESP_SUCCESS');
                return response()->json($this->JsonData);
             }//if
            else if(isset($loginPatientsession) && isset($sessionAppointmentData) && $request->is_already_registered==1)
             {

                    //If user is already logged in and filled appointment form then directly do the appointment

                    //Adding code here
                    $jsondata =  $this->bookingAppointment($request);
                    if(isset($jsondata) && !empty($jsondata))
                    {
                        $this->JsonData = $jsondata;
                    }//if
             }//else
             else{


                $this->JsonData['data']     = "";
                $this->JsonData['url']      = url('/online-appointment/register');
                $this->JsonData['msg']      = '';
                $this->JsonData['status']   = __('front.RESP_SUCCESS');
                return response()->json($this->JsonData);
             }


            //Adding code here
           /* $jsondata =  $this->bookingAppointment($request);
            if(isset($jsondata) && !empty($jsondata))
            {
                $this->JsonData = $jsondata;
            }//if
            */

        }
        catch (Exception $e)
        {
            $this->JsonData['exception'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }//arrangeTimeSlot
    public function bookWebAppointment(Request $request)
    {
        $urlEventId = $urlPatientId = '';
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
        $birth_date             = $request->birth_date;
        $country_code           = $request->country_code;
        if(empty($otp_code))
        {
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
        else {
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
            // else {
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
                            ->whereDate('roster_has_dates.date',Date('Y_m-d',strtotime($appointment_date)))
                            ->where('roster_has_weeks_has_time_frames.time_frame','=',$time_frame)
                            ->get(['roster_has_weeks_has_time_frames.time_frame']);
                //dd($check_time_frame);
                if(!empty($check_time_frame) && sizeof($check_time_frame)>0)
                {
                    //now time slotes are available,but the appointment is booked for it then throw error message
                    $check_app_date = date("Y-m-d H:i:s",strtotime($appointment_date." ".$time_frame));
                    $check_app_end_date  = self::_getEndDate($check_app_date,$appointment_type_id);
                    $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id);
                    $duration = $appointmentType->duration;
                    if($duration == 10)
                    {
                        $check_doctor_booked_appointment = $this->BaseModel
                        ->where('doctor_id',$doctor_id)
                        ->whereStatus(1)
                        ->where('appointment.start_date','<=',$check_app_date)
                        ->where('appointment.end_date', '>=', $check_app_end_date)
                        ->get(['id']);
                    }
                    else
                    {
                        $check_doctor_booked_appointment = $this->BaseModel
                        ->where('doctor_id',$doctor_id)
                        ->whereStatus(1)
                        ->where('appointment.start_date','>=',$check_app_date)
                        ->where('appointment.end_date', '<=', $check_app_end_date)
                        ->get(['id']);
                    }
                    // $check_doctor_booked_appointment = $this->BaseModel
                    //                                         ->where('doctor_id',$doctor_id)
                    //                                         // ->where('appointment_type_id',$appointment_type_id)
                    //                                         ->whereStatus(1)
                    //                                         ->where('appointment.start_date', '<=', $check_app_date)
                    //                                         ->where('appointment.end_date', '>=', $check_app_end_date)
                    //                                         //->where('appointment.start_date','=',$check_app_date)
                    //                                         ->get(['id']);
                    if(!empty($check_doctor_booked_appointment) && sizeof($check_doctor_booked_appointment)>0)
                    {
                        $errors = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
                        $this->JsonData['msg'] = $errors;
                        return response()->json($this->JsonData);
                        exit();
                    }
                }
                else {
                    $errors = 'Terminzeitfenster können nicht gebucht werden.';//Appointment time slots is not available for booking
                    $this->JsonData['msg'] = $errors;
                    return response()->json($this->JsonData);
                    exit();
                }
                if(empty($errors) && $errors=='')
                {
                    //Start Booking an Appointement
                    DB::beginTransaction();
                    $collection     = new $this->BaseModel;
                    $request['start_date'] = date("Y-m-d H:i",strtotime($appointment_date." ".$time_frame));

                    $request['end_date']  = self::_getEndDate($request['start_date'],$appointment_type_id);
                    // dd($request->all());
                    //please get patient id and add it in request
                    $collection     = self::_storeAppointment($collection,$request);

                    //===============================================================
                    //dump($request->roster_time_slot_hd_id);
                    $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                        ->where('id',$request->roster_time_slot_hd_id)
                                        ->update(['time_frame_flag'=>'2',
                                                  'time_frame_flag_date'=>Date('Y-m-d H:i:s'),
                                                  'comment'=>'AppointmentWebController '.$request->dr_type.' booking function app Date : '.Date('Y_m-d',strtotime($appointment_date)).'current Date : '.Date('Y-m-d H:i:s').' .patient_name: '.$patient_id
                                                ]);
                    //Log::info('has created appointment by AppointmentWebController');
                    $debug_arr['data'] = 'has created appointment by AppointmentWebController';
                    $res_name = "AppointmentWebController_store";
                    //dd($debug_arr);
                    self::debugModeappBookFun($debug_arr,$res_name);
                    log::info($collection);
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
                        $booking_month = __('admin.'.date('F',strtotime($request->start_date)),[],'de');
                        $appointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";
                        $patientText = $collection->assignedPatient->salutation ?? "";
                        $patientText .= " ".$collection->assignedPatient->family_name;
                        $doctorSurname = $collection->assignedDoctor->last_name;
                        //Appoinment Push Notification
                        $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;

                        $mailAppointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";
                        // $mailAppointmentTime = date('d.F',strtotime($request->start_date)).", um ".date('H:i',strtotime($request->start_date))." Uhr.";
                        $mail_content = 'Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$mailAppointmentTime;
                        $notify_times = self::_getNotifyTime($request['start_date']);
                        foreach ($notify_times as $notify_time)
                        {
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
                        else {
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
                        }
                        else {
                            $all_transactions[] = 0;
                            $errors = $postCalDetails->original['msg'];
                        }
                    }
                    else {
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
                           $this->_sendMails($patientName,$patientEmail,$mail_content);
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


            $ordination_email = $this->SettingsModel
                        ->where('setting_key','=','ORDINATION_EMAIL_ADDRESS')
                        ->whereStatus(1)
                        ->first();
            $admin_email = $this->SettingsModel
                        ->where('setting_key','=','ADMINISTRATOR_EMAIL')
                        ->whereStatus(1)
                        ->first();
            if($admin_email && $ordination_email)
            {
                $this->_failedLoginMail($ordination_email->setting_value,$admin_email->setting_value,$mail_content);
            }
            $this->JsonData['msg'] = $errors;
        }
        //Added by Shyam 24-03-22
        if(!empty($urlEventId) && !empty($urlPatientId))
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

    public function allreadyExist($patient_id,$appointment_id)
    {

        Log::info("in allreadyExist function");

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

        Log::info("in allreadyExist function exam_session");
        Log::info($exam_session);

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

                   Log::info("in allreadyExist function exam_value");
                  Log::info($exam_value);       
                  Log::info("in allreadyExist function rec");
                  Log::info($rec);       
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

        log::info("in web appointment controller getCheckList function ");

        $generalCheckList = $getExamination  = [];
        $exaination_html = $document_html = $chkexistFlag = $else_flag = NULL;
        $temp_exam = [];
        $getHtmlForPerformanceCheckList = NULL;

        $session = json_decode(base64_decode(session('chk_data'),true),true);

        /***********below condition aded on 2-may-24***********************************/
        if(!empty($session) && sizeof($session)>0)
        {
            if (isset($session[0]['id']) && !empty($session[0]['id']))
            {
                $appointment_id = $session[0]['id'];
                $appointmentData = $this->BaseModel->find($appointment_id);
                if(empty($appointmentData))
                {
                     return redirect('/online-appointments/');
                }//if empty
            }

        }//if session not empty

        /********below condition aded on 2-may-24**************************************/

        log::info("web before session data ");
        log::info($session);

        $patient_id = $appointment_id = null ;
        if(!empty($session) && sizeof($session)>0)
        {
            log::info("if session data ");
          $patient_id     = $session[0]['patient_id'];
          $appointment_id = $session[0]['id'];
        }
        else {
            return redirect('/');
        }
        if(!empty($patient_id) && !empty($appointment_id))
        {
             log::info("if patient_id and appointment_id ");

            // Get General Check List
            $allreadyExist = self::allreadyExist($patient_id,$appointment_id);

            // Log::info("in getCheckList allreadyExist");
            // Log::info($allreadyExist);

            //dd($allreadyExist);
            $general_checklist = $examination_flag = $performance_checklist = $service_doc = $general_doc = 0;

            if(!empty($allreadyExist) && sizeof($allreadyExist)>0)
            {
                 log::info("if allreadyExist ");

                if(isset($allreadyExist['general_chk']) && $allreadyExist['general_chk'] == 1)
                {
                    $general_checklist = 1;
                }
                if(isset($allreadyExist['examination']) && $allreadyExist['examination'] == 1)
                {
                     $examination_flag = 1; //commented code temp on 10-oct-24
                   //$examination_flag = 0; //added temp on 10-oct-25 for exam show
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

             log::info(" web getCheckList function getAllExamination data");
             log::info($getExamination);

            //dd($generalCheckList);
            if(!empty($generalCheckList) && sizeof($generalCheckList)>0)
            {
                log::info("getCheckList-IF-generalCheckList");
                log::info($patient_id);
                log::info($appointment_id);

                $generalCheckList   = $generalCheckList;
                $this->ViewData['type']   = 0;
                $this->ViewData['chk_type']   = 'general';
            }
            else if(!empty($getExamination) && sizeof($getExamination)>0)
            {
                log::info("getCheckList-ELS-IF-generalCheckList");
                log::info($patient_id);
                log::info($appointment_id);
                //dd($getExamination);
                // added by vijay 15/3/2024
                $getAppointment = $this->BaseModel->find($appointment_id);
                $appointment_type_id = $getAppointment->appointment_type_id;
                $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($appointment_type_id);
                $excludedNonServisesCollection = [];
                foreach ($getExamination as $key => $value) {
                    if (!in_array($value['id'], $getAppointmentNonServciesIds)) {
                        $excludedNonServisesCollection[] = $value;
                    }
                }
                //
                if(!empty($excludedNonServisesCollection) && sizeof($excludedNonServisesCollection)>0)
                {

                    log::info("getCheckList-ELS-IF-generalCheckList-1");
                    // $exaination_html = self::examinationDiv($getExamination); //commented on 22-dec-23-temp

                     $exaination_html = self::examinationDiv1($excludedNonServisesCollection); //added on 22-dec-23-temp

                     log::info(" web getCheckList function exaination_html data");
                     log::info($exaination_html);


                    // If Examination is Exist and Genral document is empty
                    $this->ViewData['type']        = 0;
                    $this->ViewData['exam_type']   = 1;
                    $this->ViewData['chk_type']    = 'performance';
                }
                else {

                    log::info("getCheckList-ELS-IF-generalCheckList-2");
                    $else_flag = 1;
                    $this->ViewData['type']   = 1;
                    $this->ViewData['type']   = 1;
                }
            }
            else {
                return redirect('/');
            }

             log::info(" web getCheckList function performance_checklist flag");
             log::info($performance_checklist);

             log::info(" web getCheckList function performance_checklist else_flag");
             log::info($else_flag);

            if($performance_checklist == 1 || $else_flag == 1)
            {
                log::info(" in performance_checklist and else flag is 1");
                $generalDocumentList = self::getAllGeneralDocument($patient_id,$appointment_id);
                if(!empty($generalDocumentList) && sizeof($generalDocumentList)>0)
                {
                    $document_html = self::documentDiv($generalDocumentList);
                }
            }
            // Peromance check list



            $exam_session = json_decode(base64_decode(session('exam_arr'),true),true);

            log::info(" in getchecklist exam_session");
            Log::info($exam_session);

            if(!empty($exam_session) && sizeof($exam_session)>0)
            {
                foreach ($exam_session as $exam_key => $exam_value)
                {
                    $temp_exam[]  = $exam_value;
                }

                Log::info("in getchecklist function temp_exam before getAllPerformanceDocument function");
                Log::info($temp_exam);

                $performanceCheckList = self::getAllPerformanceDocument($temp_exam,$patient_id,$appointment_id,0);
                if(!empty($performanceCheckList) && sizeof($performanceCheckList)>0)
                {
                    $getHtmlForPerformanceCheckList = self::getHtmlForPerformanceCheckList($performanceCheckList);
                }
            }
            $getAllDocumentList = self::getAllGeneralDocument($patient_id,$appointment_id);

              log::info(" web getchecklist function after getAllDocumentList");
              Log::info($getAllDocumentList);


            // if(empty($getHtmlForPerformanceCheckList) && sizeof($generalCheckList)== 0)
            // {
            //     return redirect('/');
            // }
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


            log::info(" web getCheckList function ViewData end");
            log::info($this->ViewData);


            return view($this->ModuleView.'checklist', $this->ViewData);
        }
        else {
            return redirect('/');
        }
    }
    // Generate Check list pdf`
    public function generateCheckListPdf(Request $request)
    {
        Log::info("in generateCheckListPdf function");

        // dd($request->all());
        $errors     = [];
        $data       = [];
        $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $status     = false;
        $exaination_html = '';
        $getAllDocumentList = [];
        $document_html = [];
        $exam = [];
        $inputdata  = $request->all();
        Log::info($inputdata);


        try
        {
            $session = json_decode(base64_decode(session('chk_data'),true),true);
            $patient_id = $appointment_id = null ;

            Log::info("in generateCheckListPdf function session");
            Log::info($session);


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
            //echo $patient_id.">>".$appointment_id."<pre>";print_r($inputdata);exit;
            $collection = self::_createGeneralPdf($inputdata,$patient_id,$appointment_id);
            // Performance check list
            //dd($request->chk_type);
            if($request->chk_type == 'general')
            {
                $getExamination = self::getAllExamination($patient_id,$appointment_id);
                log::info("generateCheckListPdf-IF");
                log::info($patient_id);
                log::info($appointment_id);
                // added by vijay 15/3/2024
                $getAppointment = $this->BaseModel->find($appointment_id);
                $appointment_type_id = $getAppointment->appointment_type_id;
                $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($appointment_type_id);
                $excludedNonServisesCollection = [];
                foreach ($getExamination as $key => $value) {
                    if (!in_array($value['id'], $getAppointmentNonServciesIds)) {
                        $excludedNonServisesCollection[] = $value;
                    }
                }
                //
                if(!empty($excludedNonServisesCollection) && sizeof($excludedNonServisesCollection)>0)
                {
                    // $exaination_html = self::examinationDiv($getExamination); //commented on 22-dec-23
                     $exaination_html = self::examinationDiv1($excludedNonServisesCollection); // added on 22-dec=23

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
                log::info("generateCheckListPdf-Else");
                log::info($patient_id);
                log::info($appointment_id);
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

            // if(!empty($session) && sizeof($session)>0)
            // {
            //     $patient_id     = $session[0]['patient_id'];
            //     $appointment_id = $session[0]['id'];
            //     $getPatientEmail = $this->PatientsModel
            //                         ->where('patient_id',$patient_id)
            //                         ->first();
            //     $encodePatientId = base64_encode(base64_encode($appointment_id));
            //     $appointmentConfirmUrl->url = url('/online-appointment/confirm-web-appointment/'.$encodePatientId);
            //     $result = Mail::to($getPatientEmail->email)->send(new ConfirmAppointmentWeb($appointmentConfirmUrl,'web'));
            //     dump("mail send");
            // }


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


         // Aded code on 30-jan-23 for redirection point
        //$redirectUrl = 'https://puregyn.at'; //commented on 19-dec-25
          $redirectUrl = 'https://web.puremed.biz'; //changed on 19-dec-25

        $ordinationWebpage = $this->SettingsModel->where('setting_key','ORDINATION_WEBPAGE')->first();
        if(isset($ordinationWebpage) && !empty($ordinationWebpage))
        {
            $setting_value = $ordinationWebpage->setting_value;
            $redirectUrl = $setting_value;
        }//if ordinationWebpage
        // End Aded code on 30-jan-23 for redirection point




        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
        $this->JsonData['exaination_html'] = $exaination_html;
        $this->JsonData['getAllDocumentList'] = $getAllDocumentList;
        // $this->JsonData['url']    =  url('/');
      //  $this->JsonData['url']    =  'https://puregyn.at';
        $this->JsonData['url']   =  $redirectUrl; // added on 30-jan-23 for redirection point
        // $this->JsonData['msg']    = __('api.APPOINTMENT_BOOKED_SUCCESS'); //commented on 2-jan-25
        $this->JsonData['msg']    = __('front.APPOINTMENT_SECOND_SUCCESS');  //changed on 2-jan-25
        $this->JsonData['patient_id']    = $patient_id;
        $this->JsonData['appointment_id']= $appointment_id;
        $this->JsonData['document_html']= $document_html;
        //dd($this->JsonData);
        return response()->json($this->JsonData);
    }

    //Roshani made change this function for 178 on 29-08-2024
    public function documentDiv($generalDocumentList)
    {
        $str = '';
        $str .= '<div data-toggle="collapse" data-target="#document" class="card card-primary" style="width: 100%;">
        <div class="card-header">
            <h3 class="card-title">' . __('front.Document') . '</h3>
        </div>
      </div>
      <div id="document" class="collapse show" style="display:block">
        <form id="frmDocument" method="post" data-toggle="validator" action="' . url('/online-appointment/generate-Document-listPdf') . '">
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
                    //Roshani added this text 'TITLE_SEARCH_WEB_TEXT_CHANGE' for CR #185 on 5-nov-2024
                    $str .= '</div><!-- /.card-body -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success" onclick="getDoctorTimeFrames()" style="
    width: 150px;">' . __('front.TITLE_SEARCH_WEB_TEXT_CHANGE') . '</button>
                    </div>
                </div>
        </form>
      </div>';

        return $str;
    }
    //Roshani made change this function for 178 on 29-08-2024

    public function documentDiv_old($generalDocumentList)
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
                        <button type="submit" class="btn btn-success" onclick="getDoctorTimeFrames()">'.__('front.TITLE_SEARCH_WEB_TEXT').'</button>
                    </div>
                </div>
        </form>
      </div>';

      return $str;
    }
    public function examinationDiv_old($getExamination)
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
                                    $checked = 0;
                                    $inputType="checkbox";
                                    if ($exam_val['checked'] == 1)
                                    {
                                        $checked = 'checked';
                                        $str .= '<input '.$checked.'
                                                            type="hidden"
                                                            class="form-check-input"
                                                            name="app_services['.$exam_key.']"
                                                            value="'.$exam_val['id'].'"
                                                            >';
                                    }
                                    else{
                                         $str .= '<div class="row">
                                            <div class="col-sm-12">
                                                <div class="p-0 form-group">
                                                    <div class="form-check" style="margin-left: 5px;">
                                                          <input
                                                          '.$checked.'
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
                            }
                        $str .='</div><!-- /.card-body -->
                        <div class="card-footer">
                          <button type="button" onclick="submitExamination(this)" id="btn_examination" class="btn btn-success" >'.__('front.TITLE_SEARCH_WEB_TEXT').'</button>
                        </div>
                    </form>
                </div>';
        return $str;
    }

    //commented on 22-dec-23
     public function examinationDiv($getExamination)
    {

       // Log::info('in examinationDiv');
       // Log::info($getExamination);

           // dd($getExamination);
            //echo "<pre>";print_r($getExamination);
            $str = '';
            $hiddenids =''; $lastIdArr=[]; $lastId=$click='';
            if(!empty($getExamination) && sizeof($getExamination)>0){
                foreach($getExamination as $exam_key1 =>$exam_val1)
                {
                    $getExam=$this->ExaminationsModel->find($exam_val1['id']);
                    if(isset($getExam) && !empty($getExam))
                    {
                        if($exam_val1['checked']==0)
                        {
                            if($exam_val1['id'])
                            {
                                 $hiddenids .= $exam_val1['id'].',';
                                 $lastIdArr[] = $exam_val1['id'];
                            }

                        }
                    }//if isset
                }
            }

            if(isset($lastIdArr) && !empty($lastIdArr)){
                $lastId = end($lastIdArr);
            }

           //  Log::info('in lastId');
          //   Log::info($lastId);


            $servicesLabel = __('front.SERVICES');
            $str .= '<div data-toggle="collapse" data-target="#examination_div" class="card card-primary" style="width: 100%;">
                    <div class="card-header">
                        <h3 class="card-title">'.$servicesLabel.'</h3>
                    </div>
                </div>
                <div id="examination_div" class="collapse" >

                    <form id="examinationForm" role="form" data-toggle="validator" action="'.url('/online-appointment/get-all-examination').'" > ';
                    $str .= '<input type="hidden" name="_token" id="csrf-token" value="'.csrf_token().'" />';

                    $str .= '<input type="hidden"  class="form-check-input" name="hidden_ids" id="hidden_ids"  value="'.rtrim($hiddenids,",").'"  /> <br/>
                             <input type="hidden" name="last_id" id="last_id" value="'.$lastId.'" />
                        <div class="card-body">';
                            $cnt = 0; $hiddenflag=0;
                            if(!empty($getExamination) && sizeof($getExamination)>0)
                            {
                                foreach($getExamination as $exam_key =>$exam_val)
                                {
                                    $checked = 0;
                                    $inputType="checkbox";
                                    $getDescription=$this->ExaminationsModel->find($exam_val['id']);
                                    if(isset($getDescription) && !empty($getDescription))
                                    {
                                        // Log::info('after getDescription');
                                       // Log::info($getDescription);

                                       $desc = isset($getDescription)?$getDescription->description:'';

                                        if ($exam_val['checked'] == 1)
                                        {
                                          //  log::info("examinationDiv");
                                          //  log::info($exam_key);
                                           // log::info($exam_val['id']);

                                            $hiddenflag++;
                                            $checked = 'checked';
                                             $str .= '<input '.$checked.'
                                                                type="hidden"
                                                                class="form-check-input"
                                                                name="app_services['.$exam_key.']"
                                                                value="'.$exam_val['id'].'"
                                                                >';
                                        }
                                        else{



                                            //  $str .= '<div class="row">
                                            //     <div class="col-sm-12">
                                            //         <div class="p-0 form-group">
                                            //             <div class="form-check" style="margin-left: 5px;">
                                            //                   <input
                                            //                   '.$checked.'
                                            //                     type="checkbox"
                                            //                     class="form-check-input"
                                            //                     name="app_services['.$exam_key.']"
                                            //                     value="'.$exam_val['id'].'"
                                            //                     >
                                            //                   <label class="form-check-label" for="status">
                                            //                    '.$exam_val['name'].'
                                            //                   </label>
                                            //             </div>
                                            //         </div>
                                            //     </div>
                                            // </div>';
                                              $cnt++;

                                              // Log::info('else else part ie checked is 0');
                                             //  Log::info($exam_val['id']);



                                              $str .='<fieldset>
                                                        <div class="card card-primary">
                                                        <div class="card-header">
                                                            <h3><label class="form-check-label" for="status">
                                                            '.$exam_val['name'].'
                                                            </label></h3>

                                                        </div>
                                                        <div class="card-body">
                                                           <div class="">
                                                               <p>'.$desc.'</p>

                                                            </div>
                                                        </div>
                                                      </div>';


                                              if($lastId==$exam_val['id']){
                                                $click = 'onclick="submitExamination(this)"';
                                              }

                                              $str .= '<input type="button" name="book"  '.$click.'  is_booked="1"  class="book btn btn-info" value="'.__('front.BOOK').'" id="'.$exam_val['id'].'" key="'.$exam_key.'"  style="background-color:#bd6f66;border-color:#bd6f66"/>
                                                      <input type="button" name="continue"  '.$click.'  is_continue="1"  class="continue btn btn-info" value="'.__('front.CONTINUE').'"  id="'.$exam_val['id'].'" key="'.$exam_key.'"   style="background-color:#bd6f66;border-color:#bd6f66"/>
                                                  </fieldset>';

                                              // Log::info($str);

                                        }//else part
                                    }//if isset

                                }
                            }
                        $str .='</div>';
                        if($cnt==0 && $hiddenflag>=0)
                        {

                           $str .='<div class="card-footer">
                            <button type="button" onclick="submitExamination(this)" id="btn_examination" class="btn btn-success" >'.__('front.TITLE_SEARCH_WEB_TEXT').'</button>
                           </div>';

                        }//if empty getExamination

                   $str .= '</form></div>';

        return $str;
    }//

    //added on 22-dec-23
    public function examinationDiv1($getExamination)
    {
        //Added on 17-march-25
        $backgroundColor = config('button_colors_code');
        $borderColor = config('button_colors_code');

       // Log::info('in examinationDiv1');
       // Log::info($getExamination);

           // dd($getExamination);
            //echo "<pre>";print_r($getExamination);


            $str = '';
            $hiddenids =''; $lastIdArr=[]; $lastId=$click='';

            $str1 = $str2 = $str3 =$str4= $strfinal='';

            if(!empty($getExamination) && sizeof($getExamination)>0){
                foreach($getExamination as $exam_key1 =>$exam_val1)
                {
                    $getExam=$this->ExaminationsModel->find($exam_val1['id']);
                    if(isset($getExam) && !empty($getExam))
                    {
                        if($exam_val1['checked']==0)
                        {
                            if($exam_val1['id'])
                            {
                                 $hiddenids .= $exam_val1['id'].',';
                                 $lastIdArr[] = $exam_val1['id'];
                            }

                        }
                    }//if isset
                }
            }

            if(isset($lastIdArr) && !empty($lastIdArr)){
                $lastId = end($lastIdArr);
            }

            // Log::info('in lastId');
           //  Log::info($lastId);


            $servicesLabel = __('front.SERVICES');
            $str1 .= '<div data-toggle="collapse" data-target="#examination_div" class="card card-primary" style="width: 100%;">
                    <div class="card-header">
                        <h3 class="card-title">'.$servicesLabel.'</h3>
                    </div>
                </div>
                <div id="examination_div" class="collapse" >

                    <form id="examinationForm" role="form" data-toggle="validator" action="'.url('/online-appointment/get-all-examination').'" > ';
                    $str1 .= '<input type="hidden" name="_token" id="csrf-token" value="'.csrf_token().'" />';

                    $str1 .= '<input type="hidden"  class="form-check-input" name="hidden_ids" id="hidden_ids"  value="'.rtrim($hiddenids,",").'"  /> <br/>
                             <input type="hidden" name="last_id" id="last_id" value="'.$lastId.'" />
                        <div class="card-body">';
                            $cnt = 0; $hiddenflag=0;
                            if(!empty($getExamination) && sizeof($getExamination)>0)
                            {
                                foreach($getExamination as $exam_key =>$exam_val)
                                {
                                    $checked = 0;
                                    $inputType="checkbox";
                                    $getDescription=$this->ExaminationsModel->find($exam_val['id']);
                                    if(isset($getDescription) && !empty($getDescription))
                                    {
                                       //  Log::info('after getDescription');
                                      //  Log::info($getDescription);

                                       $desc = isset($getDescription)?$getDescription->description:'';

                                        if ($exam_val['checked'] == 1)
                                        {

                                           // log::info("examinationDiv");
                                           // log::info($exam_key);
                                           // log::info($exam_val['id']);

                                            $hiddenflag++;
                                            $checked = 'checked';
                                            $str2 .= '<input '.$checked.'
                                                                type="hidden"
                                                                class="form-check-input"
                                                                name="app_services['.$exam_key.']"
                                                                value="'.$exam_val['id'].'"
                                                                >';
                                        }
                                        else{



                                            //  $str .= '<div class="row">
                                            //     <div class="col-sm-12">
                                            //         <div class="p-0 form-group">
                                            //             <div class="form-check" style="margin-left: 5px;">
                                            //                   <input
                                            //                   '.$checked.'
                                            //                     type="checkbox"
                                            //                     class="form-check-input"
                                            //                     name="app_services['.$exam_key.']"
                                            //                     value="'.$exam_val['id'].'"
                                            //                     >
                                            //                   <label class="form-check-label" for="status">
                                            //                    '.$exam_val['name'].'
                                            //                   </label>
                                            //             </div>
                                            //         </div>
                                            //     </div>
                                            // </div>';

                                              $cnt++;

                                             //  Log::info('else else part ie checked is 0');
                                             //  Log::info($exam_val['id']);



                                              $str3 .='<fieldset>
                                                        <div class="card card-primary">
                                                        <div class="card-header">
                                                            <h3><label class="form-check-label" for="status">
                                                            '.$exam_val['name'].'
                                                            </label></h3>

                                                        </div>
                                                        <div class="card-body">
                                                           <div class="">
                                                               <p>'.$desc.'</p>

                                                            </div>
                                                        </div>
                                                      </div>';


                                              if($lastId==$exam_val['id']){
                                                $click = 'onclick="submitExamination(this)"';
                                              }

                                              //commented below code on 17-march-25
                                              // $str3 .= '<input type="button" name="book"  '.$click.'  is_booked="1"  class="book btn btn-info" value="'.__('front.BOOK').'" id="'.$exam_val['id'].'" key="'.$exam_key.'"  style="background-color:#bd6f66;border-color:#bd6f66"/>
                                              //         <input type="button" name="continue"  '.$click.'  is_continue="1"  class="continue btn btn-info" value="'.__('front.CONTINUE').'"  id="'.$exam_val['id'].'" key="'.$exam_key.'"   style="background-color:#bd6f66;border-color:#bd6f66"/>
                                              //     </fieldset>';

                                               //changed below code on 17-march-25
                                               $str3 .= '<input type="button" name="book"  '.$click.'  is_booked="1"  class="book btn btn-info" value="'.__('front.BOOK').'" id="'.$exam_val['id'].'" key="'.$exam_key.'"  style="background-color:'.$backgroundColor.';border-color:'.$borderColor.'"/>
                                                      <input type="button" name="continue"  '.$click.'  is_continue="1"  class="continue btn btn-info" value="'.__('front.CONTINUE').'"  id="'.$exam_val['id'].'" key="'.$exam_key.'"   style="background-color:'.$backgroundColor.';border-color:'.$borderColor.'"/>
                                                  </fieldset>';

                                              // Log::info($str3);

                                        }//else part
                                    }//if isset

                                }
                            }
                        $str4 .='</div>';
                        if($cnt==0 && $hiddenflag>=0)
                        {

                        //    $str4 .='<div class="card-footer">
                        //     <button type="button" style="display:none;" onclick="submitExamination(this)" id="btn_examination" class="btn btn-success" >'.__('front.TITLE_SEARCH_WEB_TEXT').'</button>
                        //    </div>';
                        // added by vijay
                            $uniqueIdentifier = 'skkip-examination-button';

                            $str4 .= '<div class="card-footer">
                                <button type="button" onclick="submitExamination(this)" id="btn_examination" class="btn btn-success ' . $uniqueIdentifier . '" >' . __('front.TITLE_SEARCH_WEB_TEXT') . '</button>
                            </div>';
                        // end
                        }//if empty getExamination

                   $str4 .= '</form></div>';

        $strfinal =  $str1.$str2.$str3.$str4;

      //  return $str;
       return $strfinal;
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
                if(isset($header_image_path) && !empty($header_image_path)) {

                    //commented for #270 issue
                    // $str .= '<img style="width: 100%;height: 180px;" src="'.$header_image_path.'" alt="'.$getDocumentList->header_image.'">';

                   //commented on 27-may-25 for header footer not display 
                   $str .= '<img style="max-width: 100%;" src="'.$header_image_path.'" alt="'.$getDocumentList->header_image.'">'; 

                   //added on 27-may-25 for header footer not display   
                  /* $response = Http::withOptions([
                        'verify' => false, // disables SSL cert validation
                    ])->head($footer_image_path);
                    if ($response->ok()) {  
                       //changed for #270 issue
                       $str .= '<img style="max-width: 100%;" src="'.$header_image_path.'" alt="'.$getDocumentList->header_image.'">';
                    }*/
                }

                //Removed width on 16-june-25
                // $str .= '</div>
                // <div class="row" style="width: 103%;height: auto;background-color:'.$getDocumentList->background_color.'" > 

                 $str .= '</div>
                <div class="row" style="height: auto;background-color:'.$getDocumentList->background_color.'" >


                  <div class="col-sm-12" style="margin-top: 25px">
                    <div class="p-0 form-group">
                        <h4>
                          '.$getDocumentList->name.'
                        </h4>
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="p-0 form-group">
                        <h6>'.$getDocumentList->html_text.'</h6>
                    </div>
                  </div>
                </div>
                <div class="row" >';
                if(isset($footer_image_path) && !empty($footer_image_path)) {

                    //commented for #270 issue
                    // $str .= '<img style="width: 100%;height: 100px;" src="'.$footer_image_path.'" alt="'.$getDocumentList->footer_image.'">';

                    //changed for #270 issue commented on 27-may-25 for header footer issue
                    $str .= '<img style="max-width: 100%;" src="'.$footer_image_path.'" alt="'.$getDocumentList->footer_image.'">';

                    //added on 27-may-25 for header footer not display   
                    //$response = Http::head($header_image_path);
                    /*$response = Http::withOptions([
                        'verify' => false, // disables SSL cert validation
                    ])->head($footer_image_path);
                    if ($response->ok()) {
                        $str .= '<img style="max-width: 100%;" src="'.$footer_image_path.'" alt="'.$getDocumentList->footer_image.'">';
                    }*/

                }
            $str .= '</div>';
        }
        return $str;
    }

    public function generateDocumentListPdf(Request $request)
    {
        // $message = __('api.APPOINTMENT_BOOKED_SUCCESS'); //commented on 2-jan-25
         $message = __('front.APPOINTMENT_SECOND_SUCCESS'); //changed on 2-jan-25

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

            // $message = __('api.APPOINTMENT_BOOKED_SUCCESS'); //commented on 2-jan-25
            $message = __('front.APPOINTMENT_SECOND_SUCCESS'); //changed on 2-jan-25
            session(['sucess_msg' =>$message]);


            // return redirect('/'); //commented on 22-dec-25


            // start Added below code on 22-dec-25
            $redirectUrl = 'https://web.puremed.biz'; //changed on 19-dec-25

            $ordinationWebpage = $this->SettingsModel->where('setting_key','ORDINATION_WEBPAGE')->first();

            if(isset($ordinationWebpage) && !empty($ordinationWebpage))
            {
                 Log::info(" generateDocumentListPdf in ordinationWebpage =>");

                $setting_value = $ordinationWebpage->setting_value;
                $redirectUrl = $setting_value;
            }//if ordinationWebpage

             Log::info("generateDocumentListPdf redirectUrl =>");
             Log::info($redirectUrl);


            return redirect()->away($redirectUrl); 
            //end Added above code on 22-dec-25




            // return redirect(url('puregyn.at'));

        }

    }

     public function submitExamination(Request $request)
    {
        Log::info("in web appointment controller submit examination function ");
        Log::info($request->all());

        //dd($request->all());
        $generalCheckList = $generalDocumentList = [];
        $getHtmlForPerformanceCheckList = '';
        $document_html = '';
        $session = json_decode(base64_decode(session('chk_data'),true),true);
        if(!empty($session) && sizeof($session)>0)
        {
          $patient_id     = $session[0]['patient_id'];
          $appointment_id = $session[0]['id'];
        }
        else
        {
            return redirect('/');
        }
        if(isset($request->app_services))
        {
            $exam = base64_encode(json_encode($request->app_services));
            session(['exam_arr' =>$exam]);
            // ========================
            $getAppointmentRec = $this->BaseModel->find($appointment_id);
            //Added by swati 22-Jun-23=========================================
            log::info("SubmitExamination-app-services-hiddenservices-newservices-app-servcies");
            log::info($request->app_services);
            $getHiddenServices=self::getHiddenExamination($patient_id,$appointment_id);
            $appServices=array_values($request->app_services);

            log::info("web SubmitExamination getHiddenServices");
            log::info($getHiddenServices);

            //$newservices=$request->app_services; //commented code on 13-oct-25

            // added by vijay 30/7/24
            $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($getAppointmentRec->appointment_type_id);
            // end
            if(!empty($getHiddenServices) && sizeof($getHiddenServices)>0)
            {
                foreach ($getHiddenServices as $key=>$value)
                {
                    if(!in_array($value, $appServices) && !in_array($value, $getAppointmentNonServciesIds)){
                        //$newservices[$key]=$value; // commented code on 10-oct-25
                        $appServices[] = $value; // added code on 10-oct-25
                    }
                }
            }

            $request->app_services = $appServices;// added code on 13-oct-25


            //log::info("web SubmitExamination newservices");
           // log::info($newservices);

            //$request->app_services=($newservices); // commented code on 13-oct-25

            log::info("web SubmitExamination app_services after newservices");
            log::info($request->app_services);
            //================================================================
            self::_deactivateReminderNew($getAppointmentRec,$request->app_services);

            log::info("web SubmitExamination before exam store");

            $getServises = self::_appointmentTypesAgaintsServices($appointment_id,$request,$patient_id);
            //dump($appointment_id,$patient_id,$request->app_services,$getAppointmentRec->appointment_type_id,'web');
            $serviceEventType = self::GetServicesEventType($appointment_id,$patient_id,$request->app_services,$getAppointmentRec->appointment_type_id,'web');
            //dump($serviceEventType);

            //dump("ooo");
            $performanceCheckList = self::getAllPerformanceDocument($request,$patient_id,$appointment_id,1);
            $generalDocumentList = self::getAllDocumentList($request->app_services,$patient_id,$appointment_id);
        //dd($performanceCheckList);
            if(!empty($performanceCheckList) && sizeof($performanceCheckList)>0)
            {
                $getHtmlForPerformanceCheckList = self::getHtmlForPerformanceCheckList($performanceCheckList);

            }

            // changes b vijay 3/4/2024
            if (!empty($generalDocumentList) && sizeof($generalDocumentList) > 0) {
                $document_html = self::documentDiv($generalDocumentList);
            }

        }
        // GET DOCUMENT LIST
        //dump($request->app_services,$patient_id,$appointment_id);

        //dd($generalDocumentList);
        // if(empty($getHtmlForPerformanceCheckList) && sizeof($generalDocumentList)==0)
        // {
        //     $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
        //     session(['sucess_msg' =>$message]);
        //     return redirect('/');
        // }


         // Aded code on 30-jan-23 for redirection point
        // $redirectUrl = 'https://puregyn.at'; //commented on 19-dec-25
         $redirectUrl = 'https://web.puremed.biz'; //changed on 19-dec-25

        $ordinationWebpage = $this->SettingsModel->where('setting_key','ORDINATION_WEBPAGE')->first();
        if(isset($ordinationWebpage) && !empty($ordinationWebpage))
        {
            $setting_value = $ordinationWebpage->setting_value;
            $redirectUrl = $setting_value;
        }//if ordinationWebpage




        $this->JsonData['getHtmlForPerformanceCheckList']= $getHtmlForPerformanceCheckList;
        $this->JsonData['status']        = __('admin.RESP_SUCCESS');
        $this->JsonData['getAllDocumentList'] = $generalDocumentList;
        $this->JsonData['document_html'] = $document_html;
        // $this->JsonData['url']           =  url('/');
       // $this->JsonData['url']           = 'https://puregyn.at';

       $this->JsonData['url']      = $redirectUrl;  // added on 30-jan-23 for redirection point

       //$this->JsonData['msg']           = __('api.APPOINTMENT_BOOKED_SUCCESS'); //commented on 2-jan-25
       $this->JsonData['msg']           = __('front.APPOINTMENT_SECOND_SUCCESS');//changed on 2-jan-25

        $this->JsonData['patient_id']    = $patient_id;
        $this->JsonData['appointment_id']= $appointment_id;
        //dd($this->JsonData);
        //return view($this->ModuleView.'checklist', $this->ViewData);

        Log::info("in submitexamination data");
        Log::info($this->JsonData);

        return response()->json($this->JsonData);
    }//

    public function submitExamination_10_oct_25_renamed(Request $request)
    {
        Log::info("in web appointment controller submit examination function ");
        Log::info($request->all());

        //dd($request->all());
        $generalCheckList = $generalDocumentList = [];
        $getHtmlForPerformanceCheckList = '';
        $document_html = '';
        $session = json_decode(base64_decode(session('chk_data'),true),true);
        if(!empty($session) && sizeof($session)>0)
        {
          $patient_id     = $session[0]['patient_id'];
          $appointment_id = $session[0]['id'];
        }
        else
        {
            return redirect('/');
        }
        if(isset($request->app_services))
        {
            $exam = base64_encode(json_encode($request->app_services));
            session(['exam_arr' =>$exam]);
            // ========================
            $getAppointmentRec = $this->BaseModel->find($appointment_id);
            //Added by swati 22-Jun-23=========================================
            log::info("SubmitExamination-app-services-hiddenservices-newservices-app-servcies");
            log::info($request->app_services);
            $getHiddenServices=self::getHiddenExamination($patient_id,$appointment_id);
            $appServices=array_values($request->app_services);

            log::info("web SubmitExamination getHiddenServices");
            log::info($getHiddenServices);

            $newservices=$request->app_services;
            // added by vijay 30/7/24
            $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($getAppointmentRec->appointment_type_id);
            // end
            if(!empty($getHiddenServices) && sizeof($getHiddenServices)>0)
            {
                foreach ($getHiddenServices as $key=>$value)
                {
                    if(!in_array($value, $appServices) && !in_array($value, $getAppointmentNonServciesIds)){
                        $newservices[$key]=$value;
                    }
                }
            }

            log::info("web SubmitExamination newservices");
            log::info($newservices);

            $request->app_services=($newservices);

            log::info("web SubmitExamination app_services after newservices");
            log::info($request->app_services);
            //================================================================
            self::_deactivateReminderNew($getAppointmentRec,$request->app_services);

            log::info("web SubmitExamination before exam store");

            $getServises = self::_appointmentTypesAgaintsServices($appointment_id,$request,$patient_id);
            //dump($appointment_id,$patient_id,$request->app_services,$getAppointmentRec->appointment_type_id,'web');
            $serviceEventType = self::GetServicesEventType($appointment_id,$patient_id,$request->app_services,$getAppointmentRec->appointment_type_id,'web');
            //dump($serviceEventType);

            //dump("ooo");
            $performanceCheckList = self::getAllPerformanceDocument($request,$patient_id,$appointment_id,1);
            $generalDocumentList = self::getAllDocumentList($request->app_services,$patient_id,$appointment_id);
        //dd($performanceCheckList);
            if(!empty($performanceCheckList) && sizeof($performanceCheckList)>0)
            {
                $getHtmlForPerformanceCheckList = self::getHtmlForPerformanceCheckList($performanceCheckList);

            }

            // changes b vijay 3/4/2024
            if (!empty($generalDocumentList) && sizeof($generalDocumentList) > 0) {
                $document_html = self::documentDiv($generalDocumentList);
            }

        }
        // GET DOCUMENT LIST
        //dump($request->app_services,$patient_id,$appointment_id);

        //dd($generalDocumentList);
        // if(empty($getHtmlForPerformanceCheckList) && sizeof($generalDocumentList)==0)
        // {
        //     $message = __('api.APPOINTMENT_BOOKED_SUCCESS');
        //     session(['sucess_msg' =>$message]);
        //     return redirect('/');
        // }


         // Aded code on 30-jan-23 for redirection point
        //$redirectUrl = 'https://puregyn.at'; //commented on 19-dec-25
          $redirectUrl = 'https://web.puremed.biz'; //changed on 19-dec-25
        $ordinationWebpage = $this->SettingsModel->where('setting_key','ORDINATION_WEBPAGE')->first();
        if(isset($ordinationWebpage) && !empty($ordinationWebpage))
        {
            $setting_value = $ordinationWebpage->setting_value;
            $redirectUrl = $setting_value;
        }//if ordinationWebpage




        $this->JsonData['getHtmlForPerformanceCheckList']= $getHtmlForPerformanceCheckList;
        $this->JsonData['status']        = __('admin.RESP_SUCCESS');
        $this->JsonData['getAllDocumentList'] = $generalDocumentList;
        $this->JsonData['document_html'] = $document_html;
        // $this->JsonData['url']           =  url('/');
       // $this->JsonData['url']           = 'https://puregyn.at';

       $this->JsonData['url']      = $redirectUrl;  // added on 30-jan-23 for redirection point

        $this->JsonData['msg']           = __('api.APPOINTMENT_BOOKED_SUCCESS');
        $this->JsonData['patient_id']    = $patient_id;
        $this->JsonData['appointment_id']= $appointment_id;
        //dd($this->JsonData);
        //return view($this->ModuleView.'checklist', $this->ViewData);
        return response()->json($this->JsonData);
    }//

     public function getHiddenExaminationOld($patient_id,$appointment_id)
    {
        $data = $finalDat = [];
        $getAppointment = $this->BaseModel->find($appointment_id);
        if(!empty($getAppointment))
        {
            $appointment_type_id = $getAppointment->appointment_type_id;
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                ->get([
                                    'examinations.id',
                                    'examinations.name',
                                    'examinations.description'
                                ]);
            $today_date=date("Y-m-d");
            $collections1 = $collections1->filter(function($item) use ($patient_id,$today_date)
            {
                $age_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','age')
                                    ->first();
                $general_reminder_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','general')
                                    ->first();
                if(!empty($age_service))
                {
                    $getPatientAge = $this->PatientsModel
                                     ->find($patient_id);

                    if(!empty($getPatientAge))
                    {
                        $patient_age = $getPatientAge->age;

                        if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                        {
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
                }
                else
                {
                    return $item;
                }
            });
            $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
            $collections2 = $this->PatientsHasServiceReminderModel
                            ->select(('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
                            ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                            ->where('patient_has_service_reminder.patient_id',$patient_id)
                            ->where('patient_has_service_reminder.status','activate')
                            ->whereNotIn('examinations.id',$exams_ids)
                            ->whereRaw("date(reminder_date) <= '".$today_date."'")
                            ->groupBy('patient_has_service_reminder.service_id')
                            ->get();

                $collections2 = $collections2->filter(function($item) use ($patient_id,$today_date)
                {
                    $age_service =  $this->ChannelsRemindersSettingModel
                                        ->where('service_id',$item->id)
                                        ->where('activated_reminder','age')
                                        ->first();

                    if(!empty($age_service))
                    {
                        $getPatientAge = $this->PatientsModel
                                         ->find($patient_id);
                        if(!empty($getPatientAge))
                        {
                            $patient_age = $getPatientAge->age;

                            if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                            {
                                if($item->reminder_status=='executed'){
                                    $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                                    ->where('service_id',$item->id)
                                                    ->where('patient_id',$patient_id)
                                                    ->where('reminder_status','Set')
                                                    ->whereRaw("date(reminder_date) >= '".$today_date."'")
                                                    ->first();
                                    //echo "<pre>";print_r($checkServiceReminders);
                                    if(empty($checkServiceReminders))
                                        return $item;
                                }
                                else return $item;
                            }
                        }
                    }
                    $general_reminder_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','general')
                                    ->first();

                    if(!empty($general_reminder_service))
                    {
                            $today_date=date("Y-m-d"); // Added by divya on 11oct22
                            $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                            ->where('service_id',$item->id)
                                            ->where('patient_id',$patient_id)
                                            ->where('reminder_status','Set')
                                            ->whereRaw("date(reminder_date) >= '".$today_date."'")
                                            ->first();
                            if(empty($checkServiceReminders)) // Added on 27-oct-22
                                return $item;
                    }
                });


            $getrecord = $collections1->merge($collections2);
            $servicesRecommanded=array();
            if(!empty($getrecord) && sizeof($getrecord)>0)
            {
                $cnt = 0;
                foreach ($getrecord as $key => $value)
                {
                    $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                    if(!empty($app_type_name))
                    {
                        if(ucfirst($value->name) == ucfirst($app_type_name->name))
                        {
                            $data[$key]['checked']   = 1;
                            $data[$key]['id']   = $value->id;
                            $data[$key]['name'] = ucfirst($value->name);
                            $servicesRecommanded[$key]=$value->id;
                        }
                        else if(empty($value->description))
                        {
                            $data[$key]['checked']   = 1;
                            $data[$key]['id']   = $value->id;
                            $data[$key]['name'] = ucfirst($value->name);
                            $servicesRecommanded[$key]=$value->id;
                        }
                        else
                        {
                            $data[$key]['checked']   = 0;
                        }
                    }

                }
            }
        }
       // dd($data);
        return $servicesRecommanded;
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
                        <div class="slideshow-container" style="background:#fff">';
                            foreach ($generalCheckList as $key => $value){
                                if(isset($value['exam_id']))
                                { $exam_id =  $value['exam_id'];}

                                $sty = 'display:none';
                                if($key == 0)
                                {
                                    $sty = 'display:block';
                                }

               
                

                $str .= '<div id="checklist-data" data-count="' . count($generalCheckList) . '" style="display: none;"></div>';

                $str .='<div class="myPerformanceSlides" style="'.$sty.'">';

                //start added on 17-june-25 for header footer
                if(isset($value['header_image_path']) && !empty($value['header_image_path'])) 
                {
                    $header_image_path = $value['header_image_path'];
                    $str .= '<img style="max-width: 100%;margin-bottom:40px;" src="'.$header_image_path.'" alt="'.$value['header_image'].'">';
                }
                 //end added on 17-june-25 for header footer

                                 $str .='<div class="row">
                                            <div class="col-md-5" style="text-align:left; word-wrap: break-word;">
                                                <!-- Check list name -->
                                                <h2>
                                                <input type="hidden" name="check_list['.$chk_counter.'][exam_id]" value="'.$exam_id.'">
                                                <input type="hidden" name="check_list['.$chk_counter.'][checklist_id]" value="'.$value['checklist_id'].'">
                                                '.$value['check_list_name'].'
                                                </h2>
                                                <hr>
                                                <!-- check list introduction_text -->
                                                <h6 style="word-wrap: break-word">
                                                 '.($value['introduction_text']).'
                                                </h6>
                                                <hr>
                                                <!-- check list final_name -->
                                                <h6 style="word-wrap: break-word">
                                                 '.($value['final_name']).'
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

                                             //start added on 17-june-25 for header footer
                                            if(isset($value['footer_image_path']) && !empty($value['footer_image_path'])) 
                                            {
                                               $footer_image_path = $value['footer_image_path']; 
 
                                               $str .= '<img style="max-width: 100%;" src="'.$footer_image_path.'" alt="'.$value['footer_image'].'">';
                                            }
                                             //end added on 17-june-25 for header footer

                                            if($key != count($generalCheckList)-1)
                                            {
                                                    $str .= '<div class="col-lg-12 text-center cfooter" style="margin-top: 40px;">
                                                <input class="btn btn-success" type="button" onclick="submitPerformanceChecklist(this, ' . $chk_counter . ')" value="Bestätigen">
                                               </div>';

                                             }
                                            else
                                            {
                                                $str .='<div class="col-lg-12 text-center cfooter" style="margin-top: 40px;">
                                                  <input class="btn btn-success" onclick="submitPerformanceFrm(this, ' . $chk_counter . ')" id="btn-sub" type="button" onclick="plusPerformanceSlides(1)" value="Bestätigen">
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
    public function getAllDocumentList($app_services,$patient_id,$appointment_id)
    {
        $generalDocumentList = $serviceDocumentList = [];
        //GENERAL DOCUMENT
        $generalDocumentList = self::getAllGeneralDocument($patient_id,$appointment_id);
        // SERVICE DOCUMENT
        if(!empty($app_services) && sizeof($app_services)>0)
        {
            $serviceDocumentList = self::getAllServicesDocument($app_services,$patient_id,$appointment_id,1);
            // view file with data
        }

        $finalData = array_merge($generalDocumentList,$serviceDocumentList);
        //dd($finalData);
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

    // public function getAllExamination($patient_id,$appointment_id)
    // {
    //     // dd("-----");
    //     // dd($patient_id,$appointment_id);
    //     $data = $finalDat = [];
    //     $getAppointment = $this->BaseModel->find($appointment_id);

    //     if(!empty($getAppointment))
    //     {
    //         $appointment_type_id = $getAppointment->appointment_type_id;

    //         // $getrecord = $this->AppointmentTypeHasExaminationsModel
    //         //              ->where('appoinment_id',$appointment_type_id)
    //         //              ->get();

    //         $getrecord = $this->AppointmentHasExaminationsModel
    //                      ->where('appointment_id',$appointment_id)
    //                      ->get();

    //         if(!empty($getrecord) && sizeof($getrecord)>0)
    //         {
    //             $cnt = 0;
    //             foreach ($getrecord as $key => $value)
    //             {
    //                 $getExamination = $this->ExaminationsModel->find($value['examination_id']);

    //                 if(!empty($getExamination))
    //                 {
    //                     $data[$key]['id']   = $getExamination->id;
    //                     $data[$key]['name'] = ucfirst($getExamination->name);
    //                     //$GetPerformanceCheckList = self::getAllPerformanceDocument($getExamination,$patient_id,$appointment_id);
    //                 }
    //                 $cnt++;
    //             }
    //         }
    //     }
    //     return $data;
    // }
    public function getAllExaminationOld($patient_id,$appointment_id)
    {
        //dd("-----");
        // dd($patient_id,$appointment_id);
        //$patient_id = 2;
        //$appointment_id = 2;
        $data = $finalDat = [];
        $getAppointment = $this->BaseModel->find($appointment_id);
        //dd($getAppointment);
        if(!empty($getAppointment))
        {
            $appointment_type_id = $getAppointment->appointment_type_id;

            // $getrecord = $this->AppointmentTypeHasExaminationsModel
            //              ->where('appoinment_id',$appointment_type_id)
            //              ->get();

            // $getrecord = $this->AppointmentHasExaminationsModel
            //              ->where('appointment_id',$appointment_id)
            //              ->get();
            //dd($appointment_type_id,$appointment_id);
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                //->whereNotNull('examinations.description')
                                //->where('examinations.show_as_recommended','1')
                                ->get([
                                    'examinations.id',
                                    'examinations.name',
                                    'examinations.description'
                                ]);

            $collections1 = $collections1->filter(function($item) use ($patient_id)
            {
                $age_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','age')
                                    ->first();

                if(!empty($age_service))
                {
                    $getPatientAge = $this->PatientsModel
                                     ->find($patient_id);

                    if(!empty($getPatientAge))
                    {
                        $patient_age = $getPatientAge->age;

                        if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                        {
                            return $item;
                        }
                    }
                }
                else
                {
                    return $item;
                }
            });

            $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
            $collections2 = $this->PatientsHasServiceReminderModel
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
                                            'examinations.description'
                                        ]);
                                    //dd($collections2);
                $collections2 = $collections2->filter(function($item) use ($patient_id)
                {
                    $age_service =  $this->ChannelsRemindersSettingModel
                                        ->where('service_id',$item->id)
                                        ->where('activated_reminder','age')
                                        ->first();

                    if(!empty($age_service))
                    {
                        $getPatientAge = $this->PatientsModel
                                         ->find($patient_id);

                        if(!empty($getPatientAge))
                        {
                            $patient_age = $getPatientAge->age;

                            if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                            {
                                return $item;
                            }
                        }
                    }
                    else
                    {
                        return $item;
                    }
                });
            $getrecord = $collections1->merge($collections2);

            if(!empty($getrecord) && sizeof($getrecord)>0)
            {
                $cnt = 0;
                foreach ($getrecord as $key => $value)
                {
                    $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                    if(!empty($app_type_name))
                    {
                        if(ucfirst($value->name) == ucfirst($app_type_name->name))
                        {

                            $data[$key]['checked']   = 1;
                        }
                        else if(empty($value->description))
                        {
                            $data[$key]['checked']   = 1;
                        }
                        else
                        {
                            $data[$key]['checked']   = 0;
                        }


                    }
                    $data[$key]['id']   = $value->id;
                    $data[$key]['name'] = ucfirst($value->name);
                    // $getExamination = $this->ExaminationsModel->find($value['examination_id']);

                    // if(!empty($getExamination))
                    // {
                    //     $data[$key]['id']   = $getExamination->id;
                    //     $data[$key]['name'] = ucfirst($getExamination->name);
                    //     //$GetPerformanceCheckList = self::getAllPerformanceDocument($getExamination,$patient_id,$appointment_id);
                    // }
                    $cnt++;
                }
            }
        }
        //dd($data);

        return $data;
    }
    public function getAllExamination($patient_id,$appointment_id)
    {
        Log::info('in getAllExamination');

        //added on 13-oct-25
        $existingExams  = $this->AppointmentHasExaminationsModel
                        ->where('appointment_id', $appointment_id)
                        ->where('patient_id', $patient_id)
                        ->pluck('examination_id')
                        ->toArray();

         Log::info('in getAllExamination existingExams');     
         Log::info($existingExams);


        //dd("-----");
        // dd($patient_id,$appointment_id);
        //$patient_id = 2;
        //$appointment_id = 2;
        $data = $finalDat = [];
        $getAppointment = $this->BaseModel->find($appointment_id);
        //dd($getAppointment);
        if(!empty($getAppointment))
        {
            $appointment_type_id = $getAppointment->appointment_type_id;

            // $getrecord = $this->AppointmentTypeHasExaminationsModel
            //              ->where('appoinment_id',$appointment_type_id)
            //              ->get();

            // $getrecord = $this->AppointmentHasExaminationsModel
            //              ->where('appointment_id',$appointment_id)
            //              ->get();
            //dd($appointment_type_id,$appointment_id);
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                //->whereNotNull('examinations.description')
                                //->where('examinations.show_as_recommended','1')
                                // ->whereRaw("examinations.show_as_reminder='1'")

                                //  ->when(!empty($existingExams), function ($q) use ($existingExams) {
                                //     $q->whereNotIn('appoinment_type_has_examinations.examination_id', $existingExams);
                                // })//added code on 13-oct-25
                                ->get([
                                    'examinations.id',
                                    'examinations.name',
                                    'examinations.description'
                                ]);
            log::info($collections1);
            $today_date=date("Y-m-d");


            //commented below code on 27-dec-23 (2-jan-24)

            /*$collections1 = $collections1->filter(function($item) use ($patient_id,$today_date)
            {
                $age_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','age')
                                    ->first();
                //Added by swati 02-nov-22==========
                $general_reminder_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','general')
                                    ->first();
                //======================================
                if(!empty($age_service))
                {
                    $getPatientAge = $this->PatientsModel
                                     ->find($patient_id);

                    if(!empty($getPatientAge))
                    {
                        $patient_age = $getPatientAge->age;

                        if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                        {
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
                }//Added by swati 2-nov-22
                else
                {
                    return $item;
                }
            });*/


            //added below code on 27-dec-23 (2-jan-24)
            $collections1 = $collections1->filter(function($item) use ($patient_id,$today_date,$appointment_type_id)
            {
                //added below lines for default service issue solved on 5-nov-24
                $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                if ($item->name == $app_type_name->name) {

                    return $item;
                }
                else
                {

                     $collectionsFilter = $this->PatientsHasServiceReminderModel
                            ->select(DB::raw('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
                            ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                            ->where('patient_has_service_reminder.patient_id',$patient_id)
                            ->where('patient_has_service_reminder.status','activate')
                            ->where('examinations.id',$item->id)
                            ->whereRaw("date(reminder_date) <= '".$today_date."'")
                            ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                                                select service_id from patient_has_service_reminder
                                                where `patient_has_service_reminder`.`patient_id`=".$patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)"))
                            ->groupBy('patient_has_service_reminder.service_id')
                            ->get();

                        if(isset($collectionsFilter) && !empty($collectionsFilter) && $collectionsFilter->count()>0)
                        {

                            $collectionsFilter = $collectionsFilter->filter(function($item) use ($patient_id,$today_date)
                           {
                                $age_service =  $this->ChannelsRemindersSettingModel
                                            ->where('service_id',$item->id)
                                            ->where('activated_reminder','age')
                                            ->first();
                                //Added by swati 02-nov-22==========
                                $general_reminder_service =  $this->ChannelsRemindersSettingModel
                                                    ->where('service_id',$item->id)
                                                    ->where('activated_reminder','general')
                                                    ->first();
                                //======================================
                                if(!empty($age_service))
                                {
                                    $getPatientAge = $this->PatientsModel
                                                     ->find($patient_id);

                                    if(!empty($getPatientAge))
                                    {
                                        $patient_age = $getPatientAge->age;

                                        if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                                        {
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
                                }//Added by swati 2-nov-22
                                else
                                {
                                    return $item;
                                }
                           });//if collection filter

                        }//if isset collectionsFilter
                        else
                        {
                             //  dump('else collection filter empty');

                            $hasReminderSet = $this->PatientsHasServiceReminderModel
                            ->where('patient_has_service_reminder.patient_id',$patient_id)
                            ->where('patient_has_service_reminder.service_id',$item->id)
                            ->first();
                            if(isset($hasReminderSet) && !empty($hasReminderSet))
                            {
                                // dump('if hasReminderSet');
                                //  dump($item->id);
                            }//if hasReminderSet
                            else
                            {
                              //  dump('else no reminder set');
                               //  dump($item->id);
                                return $item;
                            }


                           // return $item;
                        }//else

                }//else
            });



            $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
            // $collections2 = $this->PatientsHasServiceReminderModel
            //                         ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
            //                         ->where('patient_has_service_reminder.patient_id',$patient_id)
            //                         ->where('patient_has_service_reminder.type','age')
            //                         ->where('patient_has_service_reminder.status','activate')
            //                         ->whereNotIn('examinations.id',$exams_ids)
            //                         ->where('patient_has_service_reminder.reminder_status','Set')
            //                         ->groupBy('patient_has_service_reminder.service_id')
            //                         ->get([
            //                                 'examinations.id',
            //                                 'examinations.name',
            //                                 'examinations.description'
            //                             ]);
            $today_date=date("Y-m-d");



            $collections2 = $this->PatientsHasServiceReminderModel
                            ->select(DB::raw('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
                            ->join('examinations','examinations.id','patient_has_service_reminder.service_id')

                            //added on 7-apr-25
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
                                       OR (
                                           (deleted_at IS  NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' and type='age' and appointment_id!=0) 
                                           OR 
                                           (deleted_at IS  NULL AND cycle_no >= 1 AND date(reminder_date) >= '" . $today_date . "' and type='age' and appointment_id=0))
                                    )
                                )  GROUP BY service_id) 
                                patientremidners"),
                             
                                function ($join) {
                                    $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                    $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');

                                }
                            )
                            //added on 7-apr-25
                            



                            ->where('patient_has_service_reminder.patient_id',$patient_id)
                            //->where('patient_has_service_reminder.type','age')
                            ->where('patient_has_service_reminder.status','activate')
                            ->whereNotIn('examinations.id',$exams_ids)
                            // ->when(!empty($existingExams), function ($q) use ($existingExams) {
                            //     $q->whereNotIn('examinations.id', $existingExams);

                            // })//added code on 13-oct-25



                            //->where('patient_has_service_reminder.reminder_status','Set')


                           // ->whereRaw("date(reminder_date) <= '".$today_date."'") //commented on 7-apr-25



                            // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                            //                     select service_id from patient_has_service_reminder
                            //                     where `patient_has_service_reminder`.`patient_id`=".$patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)"))
                            // ->where('patient_has_service_reminder.reminder_status','Set')
                            // ->whereRaw("examinations.show_as_reminder='1'")
                            ->groupBy('patient_has_service_reminder.service_id')
                            ->get();
                            //dd($collections2);


                log::info($collections2);
                $collections2 = $collections2->filter(function($item) use ($patient_id,$today_date)
                {
                    $age_service =  $this->ChannelsRemindersSettingModel
                                        ->where('service_id',$item->id)
                                        ->where('activated_reminder','age')
                                        ->first();

                    if(!empty($age_service))
                    {
                        $getPatientAge = $this->PatientsModel
                                         ->find($patient_id);

                        if(!empty($getPatientAge))
                        {
                            $patient_age = $getPatientAge->age;

                            if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                            {
                                if($item->reminder_status=='executed'){
                                    $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                                    ->where('service_id',$item->id)
                                                    ->where('patient_id',$patient_id)
                                                    ->where('reminder_status','Set')
                                                    ->whereRaw("date(reminder_date) >= '".$today_date."'")
                                                    ->first();
                                    //echo "<pre>";print_r($checkServiceReminders);
                                    if(empty($checkServiceReminders))
                                        return $item;
                                }
                                else return $item;
                            }
                        }
                    }
                    // else
                    // {
                    //     return $item;
                    // }
                    //Added by swati 02-nov-22==========================
                    $general_reminder_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','general')
                                    ->first();

                    if(!empty($general_reminder_service))
                    {
                        $today_date=date("Y-m-d");
                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                        ->where('service_id',$item->id)
                                        ->where('patient_id',$patient_id)
                                        ->where('reminder_status','Set')
                                        ->whereRaw("date(reminder_date) >= '".$today_date."'")
                                        ->first();
                        if(empty($checkServiceReminders))
                        return $item;
                    }
                    //======================================================
                    //Add checkup remidners as recommandation 4-sep-23=========================
                    $checkup_reminder_service =  $this->ChannelsRemindersSettingModel
                                        ->where('service_id',$item->id)
                                        ->where('activated_reminder','checkup')
                                        ->first();

                     if(!empty($checkup_reminder_service))
                    {

                        $today_date=date("Y-m-d");
                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                        ->where('service_id',$item->id)
                                        ->where('patient_id',$patient_id)
                                        ->where('reminder_status','Set')
                                        ->whereRaw("date(reminder_date) >= '".$today_date."'")
                                        ->first();
                        if(empty($checkServiceReminders))
                        return $item;
                    }
                    //================================================
                });
            $getrecord = $collections1->merge($collections2);
            log::info($getrecord);
            if(!empty($getrecord) && sizeof($getrecord)>0)
            {
                $cnt = 0;
                foreach ($getrecord as $key => $value)
                {
                    $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);
                    if(!empty($app_type_name))
                    {
                        if(ucfirst($value->name) == ucfirst($app_type_name->name))
                        {

                            $data[$key]['checked']   = 1;
                        }
                        else if(empty($value->description))
                        {
                            $data[$key]['checked']   = 1;
                        }
                        else
                        {
                            $data[$key]['checked']   = 0;
                        }


                    }
                    $data[$key]['id']   = $value->id;
                    $data[$key]['name'] = ucfirst($value->name);
                    // $getExamination = $this->ExaminationsModel->find($value['examination_id']);

                    // if(!empty($getExamination))
                    // {
                    //     $data[$key]['id']   = $getExamination->id;
                    //     $data[$key]['name'] = ucfirst($getExamination->name);
                    //     //$GetPerformanceCheckList = self::getAllPerformanceDocument($getExamination,$patient_id,$appointment_id);
                    // }
                    $cnt++;
                }
            }
        }
        //dd($data);
        return $data;
    }


    // GET PERFORMANCE CHECK LIST
    public function getAllPerformanceDocument($getExamination,$patient_id,$appointment_id,$type)
    {
        Log::info("in getAllPerformanceDocument function");
        Log::info($getExamination);
        Log::info($patient_id);
        Log::info($appointment_id);
        Log::info($type);

        //dd("--->");
        $errors     = [];
        $data       = $finalData = [];
        $data_collection = [];
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

        Log::info("in getAllPerformanceDocument function exam_arr");
        Log::info($exam_arr);

        foreach ($exam_arr as $key => $value)
        {
            $getMultipleCheckList = $this->ExaminationsHasMultipleCheckListModel
                                    ->where('fk_examinations_id',$value)
                                    ->get();

             // Log::info("in getAllPerformanceDocument function getMultipleCheckList");
             // Log::info($getMultipleCheckList);                        

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


                     // Log::info("in getAllPerformanceDocument function getcheckList");
                     //  Log::info($getcheckList);                        
                

                    if(!empty($getcheckList))
                    {
                        $patientDetails = $this->PatientsModel
                                               ->where('id',$patient_id)
                                               ->first();

                        if(!empty($patientDetails))
                        {


                           /* $data[$cnt]['checklist_id']      = $getcheckList->id;
                            $data[$cnt]['check_list_name']   = $getcheckList->check_list_name;
                            $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                            $data[$cnt]['final_name']        = $getcheckList->final_name;
                            $data[$cnt]['exam_id']           = $value;

                            $getHEading = self::getHeadingDetails($getcheckList->id);
                            $data[$cnt]['heading'] = $getHEading;*/


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
                                    $data[$cnt]['checklist_id']      = $getcheckList->id;
                                    $data[$cnt]['check_list_name']   = $getcheckList->check_list_name;
                                    $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                                    $data[$cnt]['final_name']        = $getcheckList->final_name;
                                    $data[$cnt]['exam_id']           = $value;

                                    $getHEading = self::getHeadingDetails($getcheckList->id);
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
                                    $data[$cnt]['checklist_id']      = $getcheckList->id;
                                    $data[$cnt]['check_list_name']   = $getcheckList->check_list_name;
                                    $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                                    $data[$cnt]['final_name']        = $getcheckList->final_name;
                                    $data[$cnt]['exam_id']           = $value;

                                    $getHEading = self::getHeadingDetails($getcheckList->id);
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

                            /***start*did changes for 268 issue*on 5-march-25***********/

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

        // Log::info("in getAllPerformanceDocument function finaldata");
        // Log::info($finalData);

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
        Log::info("in _createGeneralPdf function");

        Log::info($inputdata);

        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = $exam_id = '';

          $inc = 0;
        foreach ($inputdata['check_list'] as $check_list)
        {
            /************ Added on 26-dec-22**********/
            $imagepath='';
            $getDatabase = DB::connection('system')->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))->first(['uuid']);
            $imagepath = url('storage/tenancy/tenants/'.$getDatabase->uuid);
            /************ Added on 26-dec-22**********/

            //in below query added this variables: header_image,header_image_path,footer_image,footer_image_path'

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

                /*******Added by divya on 26-dec-22*********/
                $data[$cnt]['header_image']        = $collections->header_image;
                $data[$cnt]['header_image_path']   = $imagepath.$collections->header_image_path;
                $data[$cnt]['footer_image']        = $collections->footer_image;
                $data[$cnt]['footer_image_path']   = $imagepath.$collections->footer_image_path;

                /*******Added by divya on 26-dec-22*********/

                 //dump($inputdata['index']);

                  Log::info("inc==>");
                 Log::info($inc);

                 $statusVal=0;
               
                if(isset($inputdata['index']) && $inputdata['index']==$inc){

                    Log::info("inc match==>");

                    $statusVal = 1;
                }

                $inc++;

                 Log::info("inc==>");
                 Log::info($inc);

                 Log::info("statusVal==>");
                 Log::info($statusVal);

                 Log::info("statusVal==>");
                 Log::info($statusVal);

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

                //commented on 15-oct-25
               /* $CheckListHasSelectedQuestionModel = $this->CheckListHasSelectedQuestionModel
                                                ->where('fk_patient_id',$patient_id)
                                                //->where('type','general')
                                                ->where('fk_check_list_id',$check_list['checklist_id'])
                                                ->first();*/

                //added code on 15-oct-25                                
                if($inputdata['chk_type']=="general"){
                     $CheckListHasSelectedQuestionModel = $this->CheckListHasSelectedQuestionModel
                                                ->where('fk_patient_id',$patient_id)
                                                //->where('type','general')
                                                ->where('fk_check_list_id',$check_list['checklist_id'])
                                                ->first();
                }
                else{
                     $CheckListHasSelectedQuestionModel = $this->CheckListHasSelectedQuestionModel
                                                ->where('fk_patient_id',$patient_id)
                                                ->where('fk_appointment_id',$appointment_id)
                                                ->where('type','performance')
                                                ->where('fk_check_list_id',$check_list['checklist_id'])
                                                ->first();
                }
                //end 
               

                //Log::info("in _createGeneralPdf function CheckListHasSelectedQuestionModel");
                //Log::info($CheckListHasSelectedQuestionModel);

                if(!empty($CheckListHasSelectedQuestionModel))
                {    //dd($appointment_id);


                    Log::info("in _createGeneralPdf function not empty CheckListHasSelectedQuestionModel");

                    $statusVar = ($statusVal==1)?1:$CheckListHasSelectedQuestionModel->status;

                   // $statusVar = $CheckListHasSelectedQuestionModel->status;

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
                    // $CheckListHasSelectedQuestionModel->status           = '1';
                     $CheckListHasSelectedQuestionModel->status           = $statusVar;
                    $CheckListHasSelectedQuestionModel->activation_start_date  = $start_date;
                    $CheckListHasSelectedQuestionModel->activation_last_date   = $end_date;

                    $CheckListHasSelectedQuestionModel->save();
                }
                else
                {

                 Log::info("in _createGeneralPdf function empty CheckListHasSelectedQuestionModel");


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
                   // $CheckListHasSelectedQuestionModel->status           = '1'; //reverted on 28-oct-24


                    if($inputdata['chk_type']=="general"){
                         $CheckListHasSelectedQuestionModel->status    = '1';
                    }else{
                        $CheckListHasSelectedQuestionModel->status     = $statusVal; //reverted on 28-oct-24

                    }

                    //$CheckListHasSelectedQuestionModel->status           = '0'; //added by vj for #187
                    $CheckListHasSelectedQuestionModel->activation_start_date  = $start_date;
                    $CheckListHasSelectedQuestionModel->activation_last_date   = $end_date;

                   Log::info("in _createGeneralPdf function before save CheckListHasSelectedQuestionModel");
                   Log::info($CheckListHasSelectedQuestionModel);

                    $CheckListHasSelectedQuestionModel->save();
                }

                $dataFinal[] = $data;
                $data = [];

                $statusVal=0;
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

                                    $header_image_path    = self::getFilePath($chkList->header_image_path);
                                    $footer_image_path    = self::getFilePath($chkList->footer_image_path);

                                    $data[$cnt]['checklist_id']      = $chkList->id;
                                    $data[$cnt]['check_list_name']   = $chkList->check_list_name;
                                    $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                    $data[$cnt]['final_name']        = $chkList->final_name;

                                    $getHEading = self::getHeadingDetails($chkList->id);

                                    $data[$cnt]['heading'] = $getHEading;

                                    $data[$cnt]['header_image']        = $chkList->header_image;
                                    $data[$cnt]['footer_image']        = $chkList->footer_image;
                                    $data[$cnt]['header_image_path']     = (isset($chkList->header_image) && !empty($chkList->header_image))?$header_image_path:'' ;
                                    $data[$cnt]['footer_image_path']        = (isset($chkList->footer_image) && !empty($chkList->footer_image))?$footer_image_path:'' ;

                                    $cnt++;
                                }
                            }
                    }
                    else
                    {

                        $header_image_path    = self::getFilePath($chk_value['header_image_path']);
                        $footer_image_path    = self::getFilePath($chk_value['footer_image_path']);

                        $data[$cnt]['checklist_id']      = $chk_value['id'];
                        $data[$cnt]['check_list_name']   = $chk_value['check_list_name'];
                        $data[$cnt]['introduction_text'] = $chk_value['introduction_text'];
                        $data[$cnt]['final_name']        = $chk_value['final_name'];

                        $getHEading = self::getHeadingDetails($chk_value['id']);
                        $data[$cnt]['heading'] = $getHEading;

                         $data[$cnt]['header_image']    = $chk_value['header_image'];
                        $data[$cnt]['footer_image']     = $chk_value['footer_image'];
                        $data[$cnt]['header_image_path']        =(isset($chk_value['header_image']) && !empty($chk_value['header_image']))?$header_image_path:'' ;;
                        $data[$cnt]['footer_image_path']   = (isset($chk_value['footer_image']) && !empty($chk_value['footer_image']))?$footer_image_path:'' ;;

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
        //dd($request->all());
        $collection->patient_id  = $request->patient_id;
        $collection->doctor_id   = $request->doctor_id;
        $collection->appointment_type_id = $request->appointment_type_id;
        $collection->start_date = $request->start_date;
        $collection->end_date   = $request->end_date;
        $collection->status     = 1;
        // $collection->is_app_booked  = 0; //commented on 6-may-24
        $collection->is_app_booked  = 1; //added on 6-may-24 to confirm appoitment dont wait for patient
        // added by vijay 12/9/2024

        $collection->appointment_created_from = 4;
        $checkAppointmentType = $this->AppointmentTypesModel->where('id', $request->appointment_type_id)->first();
        if ($checkAppointmentType) {
            $collection->optimal_appointment = $checkAppointmentType->optimal_appointment;
        } else {
            $collection->optimal_appointment = null;
        }
        $collection->appointment_createdby = $request->patient_id;
        // end
        //Save data
        $collection->save();

        return $collection;
    }


    // public function login(Request $request,$doctorId=false) //commented on 29-feb-24
     public function login(Request $request,$doctorId=null,$service_id=null)
    {
        //die("here");
        session(['appointmentData' =>'']);
        session(['loginPatientData' =>'']);

        //added below code on 26-apr-24
        session()->forget('mobile_no');
        session()->forget('birth_date');
        session()->forget('format');
        session()->forget('email'); //added on 17-may-24


         //added pm 25-jan-23 for doctor id comes in url then redirect to booking form page
        //commented below line on 20-feb-23 for login doctor id point
        /* if(isset($doctorId) && !empty($doctorId))
         {
             //commented below condition on 20-feb-23 for login form redirection to login only
            //return redirect(url('/online-appointment/booking/'.$doctorId));
            //return redirect(url('/online-appointments'));
         }*/



        //Commenetd on 15-Sep-22 By Divya for Smart Appointment===================
        //$session = json_decode(base64_decode(session('appointmentData')));
        //$appointment = [];
        // if(!empty($session)){
        //   $appointment['doctor_id']            = $session->doctor_id;
        //   $appointment['appointment_type_id']  = $session->appointment_type_id;
        //   $appointment['roster_date']          = $session->roster_date;
        //   $appointment['roster_time_slot']     = $session->roster_time_slot;
        //   $appointment['roster_time_slot_hd_id']     = $session->roster_time_slot_hd_id;
        //   $appointment['dr_type']              = $session->dr_type;
        // }else{
        //     return redirect('/');
        // }

        // $this->ViewData['appointment'] = $appointment;
        //End ======================================================================


       // $this->ViewData['doctorId'] = isset($doctorId)?$doctorId:''; //commented on 29-feb-24

        $this->ViewData['doctorId'] = (isset($doctorId) && $doctorId!='null')?$doctorId:'';  //added on 18-dec-23 (29-feb-24)
        $this->ViewData['service_id'] = isset($service_id)?$service_id:''; //added on 18-dec-23 (29-feb-24)

         $this->ViewData['country_codes'] = $this->CountryCodesModel
                ->where('is_active',1)
                // ->orderBy('phone_code')
                ->pluck('phone_code')
                ->toArray();
        return view($this->ModuleView.'login',$this->ViewData);

    }

    public function register(Request $request)
    {
        session(['loginPatientData' =>'']);
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
          $appointment['roster_time_slot_hd_id']     = $session->roster_time_slot_hd_id;
          $appointment['dr_type']     = $session->dr_type;
        }else{
            return redirect('/');
        }

        $this->ViewData['appointment'] = $appointment;

        // dd($this->ViewData);
        return view($this->ModuleView.'register',$this->ViewData);
    }
    public function registerAndBookAppointment(RegisterPatientRequest $request)
    {
        //dd($request->all());

        Log::info('in registerAndBookAppointment function..');
        Log::info($request->all());


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
            /************Register otp code start***************/
                if (Session::has('register_mobile') && Session::has('Otp_code'))
               {
                   $session_register_mobile = session('register_mobile');
                   $session_otp_code = session('Otp_code');
                   if($request->mobile_no == $session_register_mobile && $request->otp_code==$session_otp_code)
                   {

                   }else{
                       $this->JsonData['status']   = __('admin.RESP_ERROR');
                       $this->JsonData['msg'] = __('front.ERR_WRONG_OTP');
                       return response()->json($this->JsonData);
                   }//else
               }//if session has register mobile

            /************Register otp code end******************/
            $patient_data     = new $this->PatientsModel;
            $patient_data     = self::_storePatient($patient_data,$request);

            Log::info('web side create patient AppointmentWebController line number 2166. patient Name :' .$patient_data->first_name.' '.$patient_data->family_name);
            // $ORDINATION PATIENT CHECK
            if(!empty(Config('ordination_id')))
            {
                $ordination_patient = self::_storePatientOrdination($patient_data->id);
                Log::info('web side create patient(_storePatientOrdination) AppointmentWebController line number 2171. patient Name :' .$patient_data->first_name.' '.$patient_data->family_name);

                   /*******start****#238**issue added on 25-oct-24***********************/
                    $ordination_id = Config('ordination_id');
                    $getDatabaseName = DB::connection('system')
                               ->table("ordination")
                               ->where('id',$ordination_id)
                               ->first();
                    if(!empty($getDatabaseName))
                    {
                         $tenantPatientId =  DB::connection('system')
                                    ->table("patients")
                                    ->whereDate('birth_date', date('Y-m-d',strtotime($patient_data->birth_date)))
                                    ->where('mobile_no', $patient_data->mobile_no)
                                    ->whereNULL('deleted_at')
                                    ->orderBy('created_at','DESC')
                                    ->first();

                        if(!empty($tenantPatientId))
                        {
                          $getPatientDetails = DB::connection('system')
                                             ->table("patients")
                                            ->where('id',$tenantPatientId->id)
                                            ->update(
                                                [ 'is_updated'=> '1' ]
                                            );
                        }
                    }//if not empty getDatabaseName
                /*****end****#238***issue added on 25-oct-24*************************/


            }
            //Added by Shyam 16-02-22
            if(isset($patient_data->id) && $patient_data->id != '')
            {
                $setReminders = app('App\Http\Controllers\Admin\DashboardController')->checkPatientAgeReminder($patient_data->id);
            }
            // End
            //dd($patient_data);
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
                                                                //->where('appointment_type_id',$appointment_type_id)
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

                        //===============================================================

                        $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                                ->where('id',$request->roster_time_slot_hd_id)
                                                ->update([
                                                        'time_frame_flag'=>'2',
                                                        'time_frame_flag_date'=>Date('Y-m-d H:i:s'),
                                                        'comment'=>'AppointmentWebController register '.$request->dr_type.' booking function app Date : '.Date('Y_m-d',strtotime($appointment_date)).'current Date : '.Date('Y-m-d H:i:s').' .patient_name: '.$patient_id
                                                         ]);


                        //==========================================================

                         Log::info(' has created appointment by AppointmentWebController line no 4751 :');
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

                            $getDocument = self::_GetAssignedDocument($collection->id,$collection->appointment_type_id,$request->app_services,$collection->patient_id);
                            // END

                            //insert the entry for patient has Checklist
                            $getDocument = self::_GetAssignedCheckList($collection->id,$request->app_services,$collection->patient_id);
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
                            $booking_month = __('admin.'.date('F',strtotime($request->start_date)),[],'de');
                            $appointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            //commented on 6-nov-23
                            // $patientText = $collection->assignedPatient->salutation ?? "";
                            // $patientText .= " ".$collection->assignedPatient->family_name;

                             //changed on 6-nov-23
                            // $patientText = $collection->assignedPatient->salutation ? " ".$collection->assignedPatient->salutation.'.': ""; //added dot after salutation on 14-dec-23 commented on 12-dec-25


                            $patientText = $collection->assignedPatient->salutation ? $collection->assignedPatient->salutation.'.': ""; //added dot after salutation on 14-dec-23 changed on 12-dec-25



                            // $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name; //commented on 12-dec-25

                            //changed on 12-dec-25
                            if(isset($collection->assignedPatient->salutation)){
                                $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;
                            }else{
                                $patientText .= $collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;
                            }
                            



                            $doctorSurname = $collection->assignedDoctor->last_name;
                            //Appoinment Push Notification
                            //commented on 6-nov-23
                            // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;




                            // $mailAppointmentTime = date('d.F',strtotime($request->start_date)).", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            $booking_month = __('front.'.date('F',strtotime($request->start_date)),[],'de');
                            $mailAppointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            $mail_content = 'Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$mailAppointmentTime;

                            $notify_times = self::_getNotifyTime($request['start_date']);


                            //commented below code on 13-feb-24 for notification setting

                           /* $content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime; //changed on 6-nov-23

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
                            } */


                             /****added code on 13-feb-24***for notification from setting section*******/

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
                                        $app_notify_time = date("Y-m-d H:i:s",strtotime($previous_day." ".$req_notify_time_in_seconds));

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


                            /***********end code**of notification setting****13-feb-24****************/





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
                                $urlEventId=$postCalDetails->original['data']->id;
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

                            // if(!empty($country_code)){
                            //     $country_code = str_replace("00", "",$country_code);
                            //     $country_code = str_replace("+", "",$country_code); //Added on 13 sept to send sms
                            // }elseif(empty($country_code)){
                            //     $country_code = '43'; //Austria country code
                            // }
                            // $phone   = $country_code."".str_replace("-", "",$mobile_no);
                            // $this->_sendSms($phone,$content);
                            // if(!empty($patientEmail))
                            // {
                            //    $this->_sendMails($patientName,$patientEmail,$mail_content);
                            // }
                            //Mail Send added by swati 15-Jun-23===============
                            $urlPatientId=$collection->patient_id;
                            $channels = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();
                            $patientData = $this->PatientsModel->where('id', $collection->patient_id)->first();
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
                            if(!empty($urlEventId) && !empty($urlPatientId))
                            {
                                //This code is hide for seniding email of cancel appointment
                                // //Send Email...
                                // if(!empty($patientData->email) && $channels->choice_of_channels == 'email')
                                // {
                                //      self::_sendMailAppointment($patientData->id,$urlEventId);
                                // }
                                // else {
                                //     if(!empty($phone_no))
                                //         self::_sendSmsAppointment($phone_no,$urlEventId);
                                //     elseif(!empty($patientData->email))
                                //         self::_sendMailAppointment($patientData->id,$urlEventId);
                                // }


                                //Code added by roshani on 16-04-2024
                                if(!empty($collection))
                                    {
                                        $firstName = '';
                                        $lastName = '';

                                        $patient_id     = $collection->patient_id;
                                        $appointment_id = $collection->id;
                                        $getPatientEmail = $this->PatientsModel
                                                            ->where('id',$patient_id)
                                                            ->first();
                                        $appointmentDetail = $this->BaseModel
                                                        ->where('id', $collection->id)
                                                        ->first();
                                        $encodePatientId = base64_encode(base64_encode($appointment_id));
                                        $appointmentConfirmUrl = url('/online-appointment/confirm-web-appointment/'.$encodePatientId);
                                        $timestamp = strtotime($appointmentDetail->start_date);

                                        setlocale(LC_TIME, 'de_AT.utf8');
                                        $formattedDate = strftime("%e. %B, um %H:%M Uhr", $timestamp);
                                        $cancelUrl = url('/cancelAppointment').'/'.$urlEventId;
                                        $firstName = isset($getPatientEmail->first_name) && !empty($getPatientEmail->first_name) ? $getPatientEmail->first_name : '';
                                        $lastName = isset($getPatientEmail->family_name) && !empty($getPatientEmail->family_name) ? $getPatientEmail->family_name : '';

                                        $patientAndAppDetail = [
                                            'Confirm_url' =>  $appointmentConfirmUrl,
                                            'patient_name' => $firstName .' '. $lastName,
                                            'datetime' => $formattedDate,
                                            'Cancel_url' =>$cancelUrl,
                                        ];

                                        //start added below line on 8-may-24
                                        $ordinationName=(!empty(config('ordination_name'))) ? ucfirst(config('ordination_name')):'Puremed';
                                        //end added below line on 8-may-24

                                        // $result = Mail::to($getPatientEmail->email)->send(new ConfirmAppointmentWeb($patientAndAppDetail,'web'));

                                        //added ordination on 8-may-24
                                        $result = Mail::to($getPatientEmail->email)->send(new ConfirmAppointmentWeb($patientAndAppDetail,'web',$ordinationName));
                                    }

                                //code added by roshani
                            }
                            else{
                                if(!empty($patientData->email) && $channels->choice_of_channels == 'email')
                                {
                                    $this->_sendMails($patientName,$patientEmail,$mail_content);
                                }
                                else {
                                    if(!empty($phone_no)) $this->_sendSms($phone,$content);
                                    elseif(!empty($patientData->email))
                                        $this->_sendMails($patientName,$patientEmail,$mail_content);
                                }
                            }
                            //===============================================


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


                            /**********start code for services insert on 10-oct-25************/

                            $getAppointment = DB::table('appointment as a')
                            ->join('appointment_types', 'a.appointment_type_id', '=', 'appointment_types.id')
                            ->join('examinations', 'appointment_types.name', '=', 'examinations.name')
                            ->leftJoin('appointment_has_examinations as ahx', function($join) {
                                $join->on('ahx.appointment_id', '=', 'a.id')
                                     ->on('ahx.examination_id', '=', 'examinations.id')
                                     ->on('ahx.patient_id', '=', 'a.patient_id');
                            })
                            ->select(
                                'a.id',
                                'a.patient_id',
                                'a.appointment_type_id',
                                'appointment_types.id as appointment_type_id',
                                'appointment_types.name as appointment_type_name',
                                'examinations.id as examination_id',
                                'examinations.name as examination_name'
                            )
                            ->whereNull('examinations.deleted_at')
                            ->whereNull('ahx.id') 
                            ->where('a.id',$collection->id)
                            ->where('a.patient_id',$collection->patient_id)
                            ->orderBy('a.id', 'desc')
                            ->first();    


                            if(isset($getAppointment) && !empty($getAppointment))
                            {
                                $appointment_id = $collection->id;
                                $appointment_type_id = $getAppointment->appointment_type_id; 
                                $examination_id = $getAppointment->examination_id; 
                                $patient_id = $collection->patient_id; 

                                $appointmentHasExaminations = DB::table('appointment_has_examinations')
                                ->where('appointment_id', $collection->id)
                                ->where('examination_id', $examination_id)
                                ->where('patient_id', $collection->patient_id)
                                ->first();
                                if(isset($appointmentHasExaminations) && !empty($appointmentHasExaminations)) 
                                {     
                                        Log::info("innnn already exists examinationid=>".$examination_id.'==>appointment_id=>'.$appointment_id.'==>patient_id=>'.$patient_id);
                                }
                                else
                                {
                                    Log::info("innnn not exists examinationid=>".$examination_id.'==>appointment_id=>'.$appointment_id.'==>patient_id=>'.$patient_id);

                                    $collections1 = DB::table('appoinment_type_has_examinations')
                                    ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')    
                                    ->where('appoinment_type_has_examinations.appoinment_id', $getAppointment->appointment_type_id)
                                    ->whereNull('appoinment_type_has_examinations.deleted_at') // ignore deleted rows
                                    ->get([
                                        'examinations.id',
                                        'examinations.name',
                                        'examinations.url',
                                        'examinations.description',
                                        'examinations.status',
                                        'examinations.created_at',
                                        'examinations.show_as_recommended'
                                    ]);

                                    Log::info($collections1);
                                    Log::info("collections1 data:", $collections1->toArray());

                                    $today_date = date("Y-m-d");

                                    $collections1 = $collections1->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) 
                                    {

                                        $app_type_name = DB::table('appointment_types')
                                            ->where('id', $appointment_type_id)
                                            ->first();


                                        if ($item->name == $app_type_name->name) {

                                            return $item;
                                        }
                                        else
                                        {

                                            $collectionsFilter = DB::table('patient_has_service_reminder')
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

                                            if (isset($collectionsFilter) && !empty($collectionsFilter) && $collectionsFilter->count() > 0) 
                                            {

                                                $collectionsFilter = $collectionsFilter->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) {

                                              
                                                $app_type_name = DB::table('appointment_types')
                                                    ->where('id', $appointment_type_id)
                                                    ->first();

                                                $age_service =  DB::table('preferred_channels_for_reminders_setting')
                                                    ->where('service_id', $item->id)
                                                    ->where('activated_reminder', 'age')
                                                    ->first();
                                                //Added by swati 2-nov-22=========================
                                                $general_reminder_service =  DB::table('preferred_channels_for_reminders_setting')
                                                    ->where('service_id', $item->id)
                                                    ->where('activated_reminder', 'general')
                                                    ->first();
                                                //============================                  
                                                if (!empty($age_service) && $item->name != $app_type_name->name) {
                                                    //$getPatientAge = $this->PatientsModel->find($patient_id);
                                                    $getPatientAge = DB::table('patients')
                                                                 ->find($patient_id);


                                                    if (!empty($getPatientAge)) {
                                                        $patient_age = $getPatientAge->age;
                                                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                                            //commented on 26-dec-23
                                                            return $item;
                                                        } //if
                                                    }
                                                } else if (!empty($general_reminder_service)) {
                                                    $checkGenaralService =   DB::table('patient_has_service_reminder')
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
                                            else 
                                            {

                                                $hasReminderSet =  DB::table('patient_has_service_reminder')
                                                    ->where('patient_has_service_reminder.patient_id', $patient_id)
                                                    ->where('patient_has_service_reminder.service_id', $item->id)
                                                    ->first();
                                                if (isset($hasReminderSet) && !empty($hasReminderSet)) {
                                                } //if hasReminderSet
                                                else {
                                                    return $item;
                                                }

                                            } //else   
                                        } //else not defaultservice name

                                    });

                                    Log::info("2nd ...collections1.again..");
                                    Log::info($collections1);

                                    $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
                                    Log::info("Extracted exams_ids:", $exams_ids);

                                    //cycle>=2 and app id 0 or not condition added on 23-jan-26

                                    $collections2 = DB::table('patient_has_service_reminder')
                                        ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status,patient_has_service_reminder.id as reminderid'))
                                        ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
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
                                                   OR ((deleted_at IS  NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' and type='age' and appointment_id!=0) 
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
                                        ->whereRaw("examinations.show_as_reminder='1'")
                                        ->whereNotIn('examinations.id', $exams_ids)
                                        ->groupBy('patient_has_service_reminder.service_id')
                                        ->get();

                                    Log::info("collections2 data:", $collections2->toArray());              
                                    Log::info($collections2);
                                    //dump($collections2);



                                    $collections2 = $collections2->filter(function ($item) use ($patient_id, $today_date) {
                                            $age_service =  DB::table('preferred_channels_for_reminders_setting')
                                                            ->where('service_id', $item->id)
                                                            ->where('activated_reminder', 'age')
                                                            ->first();
                                            if (!empty($age_service)) {
                                                //log::info($patient_id);


                                                //$getPatientAge = $this->PatientsModel->find($patient_id);
                                                $getPatientAge = DB::table('patients')
                                                                         ->find($patient_id);
                                                                     
                                                if (!empty($getPatientAge)) {

                                                    Log::info("in getPatientAge ..");

                                                    $patient_age = $getPatientAge->age;

                                                    Log::info($patient_age);      


                                                    if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                                        if ($item->reminder_status == 'executed') {
                                                            $checkServiceReminders =  DB::table('patient_has_service_reminder')
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
                                           
                                            $general_reminder_service =  DB::table('preferred_channels_for_reminders_setting')
                                                ->where('service_id', $item->id)
                                                ->where('activated_reminder', 'general')
                                                ->first();

                                            if (!empty($general_reminder_service)) {

                                                $today_date = date("Y-m-d");


                                                if($item->reminder_status == 'executed')
                                                {     
                                                    $checkServiceReminders =  DB::table('patient_has_service_reminder')
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

                                            }
                                            $checkup_reminder_service =  DB::table('preferred_channels_for_reminders_setting')
                                                ->where('service_id', $item->id)
                                                ->where('activated_reminder', 'checkup')
                                                ->first();

                                            if (!empty($checkup_reminder_service)) {

                                                $today_date = date("Y-m-d");
                                                
                                                if($item->reminder_status == 'executed')
                                                {
                                                    $checkServiceReminders =  DB::table('patient_has_service_reminder')
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

                                            }
                                            //================================================
                                    });
                                      
                                    Log::info("collections2 .again..");
                                    Log::info($collections2);

                                    $getRecord = $collections1->merge($collections2);

                                    Log::info("getRecord.");
                                    Log::info($getRecord);

                                    if (!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id)) 
                                    {

                                        // Fetch appointment type once
                                        $appTypeNameDefault = DB::table('appointment_types')
                                            ->where('id', $appointment_type_id)
                                            ->first();

                                        // Fetch non-examination IDs for this appointment type
                                        $getAppointmentNonServciesIds = DB::table('appoinment_type_has_non_examinations')
                                            ->where('appointment_type_id', $appointment_type_id)
                                            ->pluck('examination_id'); // collection of IDs

                                        $getRecord = $getRecord->map(function ($item) use ($appTypeNameDefault, $getAppointmentNonServciesIds) {

                                            // Exclude non-examination records
                                            if ($getAppointmentNonServciesIds->contains($item->id)) {
                                                return null;
                                            }

                                            // When description is blank
                                            if (empty($item->description)) {
                                                return $item;
                                            }

                                            // When name matches appointment type
                                            if (!empty($appTypeNameDefault) && $item->name == $appTypeNameDefault->name) {
                                                return $item;
                                            }

                                            return null; // exclude everything else
                                        })
                                        ->filter() // remove nulls
                                        ->values(); // reindex collection
                                    }

                                    Log::info("getRecord.again");
                                    Log::info($getRecord);

                                    $final = $getRecord->values();

                                    Log::info("final services ...");
                                    Log::info($final);

                                    //Added on 7-oct-25
                                    $insertedServiceIds = [];

                                    foreach ($final as $item) {

                                        Log::info("final services .item..");
                                        Log::info($item->id);

                                        $exists = DB::table('appointment_has_examinations')
                                            ->where('appointment_id', $collection->id)
                                            ->where('patient_id', $collection->patient_id)
                                            ->where('examination_id', $item->id)
                                            ->exists();

                                        if (!$exists) {
                                            DB::table('appointment_has_examinations')->insert([
                                                'appointment_id'  => $collection->id,
                                                'patient_id'      => $collection->patient_id,
                                                'examination_id'  => $item->id,
                                                'dismissal_flag'  => 0,
                                                'create_from'     => null,
                                                'created_at'      => now(),
                                                'updated_at'      => now(),
                                            ]);

                                            // collect only the inserted IDs
                                            $insertedServiceIds[] = $item->id;
                                        }
                                    }

                                    Log::info($insertedServiceIds); 

                                    // Only pass inserted IDs to deactivate function
                                    if (!empty($insertedServiceIds)) {
                                        $getAppointmentRec = DB::table('appointment')
                                            ->where('id', $collection->id)
                                            ->first();

                                        $this->deactivateReminderNew($getAppointmentRec, $insertedServiceIds);
                                    } 
                                    //end



                                }//else insert

                            }//if getAppointment


                            /*******end code insert services*10-oct-25*****************/



                            $this->JsonData['data']   = $data;
                            //$this->JsonData['url']    = url('/');
                            $this->JsonData['url']    = url('/online-appointment/get-check-list');
                            // $this->JsonData['msg']    = $message;
                            //$this->JsonData['msg']    = 'Ihr Termin wurde gebucht'; //commented on 30-dec-25
                            $this->JsonData['msg']    = __('front.APPOINTMENT_DATE_BOOKED_SUCCESS'); //added on 30-dec-25


                            $this->JsonData['status'] = __('front.RESP_SUCCESS');

                            //$data[]  = $collection;
                            self::_createLog('bookAppointment',$data,'info');
                            $this->ActivityLogModel->addApiLog('Book Appointment','has book appointment','Create',null,$data);
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

        return response()->json($this->JsonData);
    }

     public function sendRegisterOtp(Request $request)
    {
       // dump($request->all());
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
        try
        {

             // Start Patient duplication code added on 15-dec-23 only date of birth and mobile number.
             $is_exist_patient = $this->_checkDuplicationPatient($request->family_name,$request->first_name,$request->birth_date,$request->mobile_no,'add',$id = '');

            if(!$is_exist_patient)
            {
                $this->JsonData['msg'] = __('front.ERR_MOBILE_UNIQUE');
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                return response()->json($this->JsonData);
                exit();
            }
            // End Patient duplication code added on 4-dec-23


            $otp_code = rand(1000, 9999);
            $country_code = $request->country_code;
            if(!empty($country_code)){
                $country_code = str_replace("00", "",$request->country_code);
                $country_code = str_replace("+", "",$country_code);
            }elseif(empty($country_code)){
                $country_code = '43'; //Austria country code
            }

            $phone   = $country_code."".str_replace("-", "",$request->mobile_no);
            $message = 'Hallo ,Ihr SMS-Code ist '.$otp_code;

            $issmssend = $this->_sendSms($phone,$message);
            if($issmssend['error']==0)
            {

                if (Session::has('register_mobile'))
                {
                   Session::forget('register_mobile');
                   Session::forget('Otp_code');
                   session(['register_mobile' => $request->mobile_no,'Otp_code'=>$otp_code]);

                   //$register_mobile = session('register_mobile');
                  // $Otp_code = session('Otp_code');

                }else{
                   session(['register_mobile' => $request->mobile_no,'Otp_code'=>$otp_code]);
                }


                $this->JsonData['msg']     = 'SMS erfolgreich gesendet, Bitte geben Sie den SMS-Code ein, um Ihren Termin zu buchen.';
                $this->JsonData['status']  = __('front.RESP_SUCCESS');
                $this->JsonData['otp']  = $otp_code;
            }
            else{
                $this->JsonData['status'] = __('front.RESP_ERROR');
                $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
                $this->JsonData['otp']  = '';
            }//else



        } catch (Exception $e)
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);

    }//sendRegisterOtp

    public function _storePatient($collection, $request)
    {

        Log::info("in web controller _storePatient function");
        Log::info($request->all());

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
        $collection->gender              = $request->gender;
        $collection->password        = $request->password ? Hash::make($request->password):'';
        $collection->is_updated  = '1'; //added on 25-oct-24 for #238 issue

        $collection->country        = $request->country; //Roshani added on 10 oct 2024 for #102 CR

        //Save data
        $collection->save();
                // dump(Config('ordination_id'));
                // $ordination_patient = self::addPatientCountryOnOrdination($collection->id);// for check localy

        // $ORDINATION PATIENT CHECK
            if(!empty(Config('ordination_id')))
            {
                $ordination_patient = self::addPatientCountryOnOrdination($collection->id);//roshani cr #102

            }

        return $collection;
    }


    public function sendOtp(Request $request)
    {
        //dump($request->all());

        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
        try
        {
            // dd($request->all());
            $collection = $this->PatientsModel
                                // ->where('first_name',trim($request->first_name))
                                // ->where('family_name',trim($request->family_name))
                                ->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))
                                ->where('mobile_no','=',$request->mobile_no)
                                ->first();
            // dd($collection,ltrim($collection->mobile_no,'0'));
            if(!empty($collection) && ltrim($collection->mobile_no,'0') == $request->mobile_no){

                if($collection->status==1){

                    // $collection = $this->_updateOtp($collection);

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

    public function _sendMails($name,$email,$text)
    {
        $from=(!empty(config('ordination_name'))) ? ucfirst(config('ordination_name')):'Puremed';
        $result = Mail::to($email)->send(new AppointmentMail($name,$text,$from));
    }
    public function _failedLoginMail($email,$adminemail,$text)
    {
        $from=(!empty(config('ordination_name'))) ? ucfirst(config('ordination_name')):'Puremed';
        // log::info("FaildMail");
        // log::info($email);
        // log::info($adminemail);
        //->bcc(['eluminous.se65@gmail.com'])
        $result = Mail::to($email)->cc([$adminemail])->send(new FailedAppointmentMail($text,$from));
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

 public function getDoctorSlots(Request $request)
    {
        Log::info("in getDoctorSlots function..");
        Log::info($request->all());

        //dump($request->all());
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');

        try
        {
            $start = Carbon::createFromFormat('d/m/Y', $request->start_date, 'UTC');
            $end = Carbon::createFromFormat('d/m/Y', $request->end_date, 'UTC');
            if ($start && $end && $end->lt($start)) {
                 $html = '<table id="customers">
                <thead>
                <tr>
                <td colspan="3" style="text-align: center;">';
                $html .= 'Das Enddatum darf nicht vor dem Startdatum liegen.';
                $html .= '</td>
                </tr> </thead>  </table>';

                $this->JsonData['html'] = $html;
                $this->JsonData['data'] = [];
                $this->JsonData['type'] = 'All doctors';
                $this->JsonData['msg']  = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
                $this->JsonData['status'] = __('front.RESP_SUCCESS');
                return response()->json($this->JsonData);
            }

           

            $doctor_id              = $request->doctor_id;
            $appointment_type_id    = $request->appointment_type_id;
            $week_day_ids            = $request->week_day_id;
            $start_date  = date("Y-m-d",strtotime(str_replace("/","-", $request->start_date)));
            $end_date  = date("Y-m-d",strtotime(str_replace("/","-", $request->end_date)));
            $from_time              = $request->from_time;
            $to_time                = $request->to_time;
            $hidden_patient_id      = $request->hidden_patient_id;

           /*************************************************************/

           /**************added on 8-aug-25-for user manually enter date issue*************/

            if(isset($request->doctor_id) && !empty($request->doctor_id))
            {
               Log::info("in getDoctorSlots function.user manual enter date code doc not empty.");


                $quarterSetting=0;
                $OPTIMAL_APPOINTMENT ='OPTIMAL_APPOINTMENT';
                $optimal_appointment_setting = DB::table('settings')->where(['setting_key'=>$OPTIMAL_APPOINTMENT])->select('setting_key','setting_value','description')->first();
                if(isset($optimal_appointment_setting) && !empty($optimal_appointment_setting))
                {
                    $quarterSetting = $optimal_appointment_setting->setting_value;


                }//if optimal appointment

                Log::info("in getDoctorSlots function.user manual enter date quarterSetting");
                Log::info($quarterSetting);


                // changes by vijay 7/3/24
                $optimalAppointment = 0;
                if (isset ($request->appointment_type_id) && !empty ($request->appointment_type_id)) {
                    $checkAppointmentType = $this->AppointmentTypesModel->where('id', $request->appointment_type_id)->first();
                    $optimalAppointment = $checkAppointmentType->optimal_appointment;
                }
                //end changes

                Log::info("in getDoctorSlots function.user manual enter date optimalAppointment");
                Log::info($optimalAppointment);


                $todaysdate = date('Y-m-d');
               
                // if ($optimalAppointment == 1 && $request->is_already_registered == 1 && ($quarterSetting == 1 || $quarterSetting == 0))
                // {
                //     Log::info("in getDoctorSlots function.user manual enter date optimalAppointment 1 ");

                //         // Get quarter and year from that start date
                //         $quarter = ceil(date('n', strtotime($start_date)) / 3);
                //         $year = date('Y', strtotime($start_date));
                //         $patient_id = $request->patient_id;

                //        //  dump($quarter);
                //        // dump($year);
                //        // dump($patient_id);

                //         Log::info("in getDoctorSlots function.user manual enter date quarter");
                //         Log::info($quarter);

                //         Log::info("in getDoctorSlots function.user manual enter date year");
                //         Log::info($year);

                //         if(isset($patient_id))
                //         {

                //             $check_appointment_exists = $this->BaseModel
                //                 ->whereRaw("quarter(start_date) = $quarter AND year(start_date) = $year")
                //                 ->where('patient_id', $patient_id)
                //                 ->where('status', 1)
                //                 ->where('appointment_status', '!=', 'Vermisst')
                //                 ->first();

                //             Log::info("in getDoctorSlots function.user manual enter date check_appointment_exists");

                //             if (!empty($check_appointment_exists)) {
                //                 // STOP here and return error message
                              
                //                Log::info("in getDoctorSlots function.user manual enter date check_appointment_exists not empty ");

                //                     //added new code on 13-aug-25
                //                     $html1 = '<table id="customers">
                //                         <thead>
                //                             <tr>
                //                                 <td colspan="3" style="text-align: center;">';

            
                //                                  // No dates available at all
                //                                  $html1 .= 'Im ausgewählten Zeitraum ist kein Termin verfügbar.';
            
                //                                 $html1 .= '</td>
                //                           </tr>';

           
                //                          $html1 .= '</thead>
                //                         <tbody>';

                //                     // $html.='<tr>
                //                     //         <td class="right2" colspan="3"><b>'.$msg.'</b></td>
                //                     //     </tr>';
                            
                //                     $roster_time_slots_date_wise = array();

                //                     $this->JsonData['html'] = $html1;
                //                     $this->JsonData['data'] = $roster_time_slots_date_wise;
                //                     $this->JsonData['type'] = 'All doctors';
                //                     $this->JsonData['msg']  = '';
                //                     $this->JsonData['status'] = __('front.RESP_SUCCESS');
                //                     return response()->json($this->JsonData);

                //                     // $this->JsonData['errmsg']  = __('front.ERR_DOUBLE_BOOKING');
                //                     // $this->JsonData['status'] = __('front.RESP_ERROR');
                //                     // return response()->json($this->JsonData);


                //             }//if not empty check_appointment_exists
                //         }

                // }//if settings 1
            }//if doctor id

            /***********end 8-aug-25********************************************************/



            $quarter_setting=0;
            $OPTIMAL_APPOINTMENT ='OPTIMAL_APPOINTMENT';
            $optimal_appointment = DB::table('settings')->where(['setting_key'=>$OPTIMAL_APPOINTMENT])->select('setting_key','setting_value','description')->first();
            if(isset($optimal_appointment) && !empty($optimal_appointment))
            {
                $quarter_setting = $optimal_appointment->setting_value;
            }//if optimal appointment

            Log::info("in getDoctorSlots function.quarter_setting.");



            if($quarter_setting==1 && isset($request->hidden_patient_id))
           {
                Log::info("in getDoctorSlots function.quarter_setting. is 1..");

                $ignoreQuarterArr = $ignoreYearArr=$ignoreArray=[];
                $get_quarters = $this->get_quarters($start_date,$end_date);
                if(isset($get_quarters) && !empty($get_quarters))
                {
                    $ignoreArr=[]; $whereQuarter=''; $quarterCheckFlag=0;
                    $whereQuarter="Case ";
                    foreach($get_quarters as $k=>$v)
                    {
                       $quarter = $v->period;
                       $year = $v->year;

                        Log::info("quarter===>");
                        Log::info($quarter);

                        Log::info("year===>");
                        Log::info($year);

                        $checkAppoimentBooked = $this->BaseModel
                                            ->whereRaw("quarter(start_date)=$quarter and year(start_date)=$year")
                                           // ->where('doctor_id',$doctor_id) //commented on 14-sept-22
                                            ->where('patient_id',$hidden_patient_id)
                                            ->where('status',1)
                                            // ->where('appointment_status','!=','Vermisst')   // Added on 21-sept-22 //Roshani hidden the line on 15-april-25 for point Trello 281
                                            ->first();

                        Log::info("in getDoctorSlots function.quarter_setting checkAppoimentBooked ");                    

                        if(isset($checkAppoimentBooked) && !empty($checkAppoimentBooked))
                        {
                            Log::info("in getDoctorSlots function.quarter_setting in checkAppoimentBooked ");  

                            $ignoreQuarterArr[] = $quarter;
                            $ignoreYearArr[] = $year;
                            $ignoreArr['quarter'] = $quarter;
                            $ignoreArr['year'] = $year;

                            $whereQuarter.="WHEN quarter(roster_has_dates.date)=$quarter THEN year(roster_has_dates.date)!='$year'";

                            $quarterCheckFlag=1;

                        }//if checkAppoimentBooked

                        if(isset($ignoreArr) && !empty($ignoreArr))
                        {
                             $ignoreArray[] = $ignoreArr;
                         }//



                    }//foreach

                    $whereQuarter.="ELSE 1=1 END ";
                }//if
           }

           /*************************************************************/



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
            //Roshani added chnages for get appointment quqrter
            if(isset($quarter_setting) && $quarter_setting==1)
                {
                    $patient_id = $request->patient_id;

                    // 🔎 Get all booked quarters grouped by year
                    $bookedQuartersByYear = $this->BaseModel
                        ->selectRaw("YEAR(start_date) as year, QUARTER(start_date) as quarter")
                        ->where('patient_id', $patient_id)
                        ->where('status', 1)
                        ->where('appointment_status', '!=', 'Vermisst')
                        ->groupBy('year', 'quarter')
                        ->orderBy('year')
                        ->orderBy('quarter')
                        ->get();

                    Log::info("Booked quarters by year: " . json_encode($bookedQuartersByYear));

                    $bookedRanges = [];

                    // build start & end dates for each booked quarter across all years
                    foreach ($bookedQuartersByYear as $row) {
                        $y = $row->year;
                        $q = $row->quarter;

                        switch ($q) {
                            case 1:
                                $bookedRanges[] = [
                                    'start' => Carbon::create($y, 1, 1),
                                    'end'   => Carbon::create($y, 3, 31),
                                ];
                                break;
                            case 2:
                                $bookedRanges[] = [
                                    'start' => Carbon::create($y, 4, 1),
                                    'end'   => Carbon::create($y, 6, 30),
                                ];
                                break;
                            case 3:
                                $bookedRanges[] = [
                                    'start' => Carbon::create($y, 7, 1),
                                    'end'   => Carbon::create($y, 9, 30),
                                ];
                                break;
                            case 4:
                                $bookedRanges[] = [
                                    'start' => Carbon::create($y, 10, 1),
                                    'end'   => Carbon::create($y, 12, 31),
                                ];
                                break;
                        }
                    }

                    Log::info("Booked ranges across years: " . json_encode(array_map(function ($r) {
                        return $r['start']->toDateString() . " → " . $r['end']->toDateString();
                    }, $bookedRanges)));

                }
            //Roshani added chnages for get appointment quqrter
            $roster_time_slots_date_wise = array();
                // dump($start_date);
            $time_frames = $this->RosterModel
    ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
    ->join('roster_has_weeks_has_time_frames', function ($join) {
        $join->on('roster_has_weeks_has_time_frames.roster_id', '=', 'roster_has_dates.roster_id')
            ->on('roster_has_weeks_has_time_frames.week_day_id', '=', 'roster_has_dates.week_day_id');
    })
    ->where('roster.doctor_id',$doctor_id)
    ->where('roster_has_dates.is_excluded','=',0)
    ->whereDate('roster_has_dates.date','>=', $start_date)
    ->whereDate('roster_has_dates.date','<=', $end_date)
    ->whereIn('roster_has_weeks_has_time_frames.week_day_id',$week_day_ids)
    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
    ->where('roster_has_weeks_has_time_frames.start_date','<=',$end_date)
    ->where('roster_has_weeks_has_time_frames.end_date','>=',$start_date);


//commented on 26-nov-25 for 405
// Exclude booked quarter ranges only if quarter_setting == 1 and ranges exist
/*if (isset($quarter_setting) && $quarter_setting == 1 && !empty($bookedRanges)) {
    foreach ($bookedRanges as $range) {
        $time_frames->whereNotBetween('roster_has_dates.date', [
            $range['start']->toDateString(),
            $range['end']->toDateString()
        ]);
    }

    if (isset($quarterCheckFlag) && $quarterCheckFlag == 1) {
        $time_frames = $time_frames->whereRaw($whereQuarter);
    }
}*/

//Added on 26-nov-25 for 405
if (isset($quarter_setting) && $quarter_setting == 1 && !empty($bookedRanges)) {
    if (isset($quarter_setting) && $quarter_setting == 1 && !empty($bookedRanges) && $optimalAppointment == 1) {
        foreach ($bookedRanges as $range) {
            $time_frames->whereNotBetween('roster_has_dates.date', [
                $range['start']->toDateString(),
                $range['end']->toDateString()
            ]);
        }
    }
    if (isset($quarterCheckFlag) && $quarterCheckFlag == 1) {
        $time_frames = $time_frames->whereRaw($whereQuarter);
    }
}



$time_frames = $time_frames->get([
    'roster_has_dates.date',
    'roster_has_weeks_has_time_frames.time_frame',
    'roster_has_dates.start_date',
    'roster_has_dates.end_date',
    'roster_has_weeks_has_time_frames.week_day_id',
    'roster_has_weeks_has_time_frames.id as r_id',
    'roster_has_dates.to_time as roster_to_time'
]);

                //   $time_frames = $this->RosterModel
                //                 ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                //                 // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')


                //                 //commented on 26-may-25    
                //                 // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id')

                //                 ->join('roster_has_weeks_has_time_frames', function ($join) {
                //                     $join->on('roster_has_weeks_has_time_frames.roster_id', '=', 'roster_has_dates.roster_id')
                //                          ->on('roster_has_weeks_has_time_frames.week_day_id', '=', 'roster_has_dates.week_day_id');
                //                 }) //added on 26-may-25 


                //                 ->where('roster.doctor_id',$doctor_id)
                //                 ->where('roster_has_dates.is_excluded','=',0)
                //                  ->whereDate('roster_has_dates.date','>=', $start_date)
                //                  ->whereDate('roster_has_dates.date','<=', $end_date)
                //                  ->whereIn('roster_has_weeks_has_time_frames.week_day_id',$week_day_ids)
                //                 ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                //                 ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                //                 // ->where(function($query) use($end_date,$start_date){
                //                 //     $query->where(function($query) use($end_date,$start_date){
                //                 //         $query->where('roster_has_weeks_has_time_frames.start_date','<',$end_date)
                //                 //         ->where('roster_has_weeks_has_time_frames.end_date','>',$start_date);
                //                 //     })->orWhere(function($query){
                //                 //         $query->whereNull('roster_has_weeks_has_time_frames.start_date')
                //                 //         ->whereNull('roster_has_weeks_has_time_frames.end_date');
                //                 //     });
                //                 // })
                //                 ->where('roster_has_weeks_has_time_frames.start_date','<=',$end_date)
                //                 ->where('roster_has_weeks_has_time_frames.end_date','>=',$start_date);
                //                 //->where('roster_has_weeks_has_time_frames.time_frame_flag','0')

                //      if(isset($quarterCheckFlag) && $quarterCheckFlag==1 && $quarter_setting==1)
                //      {
                //         $time_frames =$time_frames->whereRaw($whereQuarter);
                //      }

                //      // $time_frames = $time_frames->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_weeks_has_time_frames.week_day_id','roster_has_weeks_has_time_frames.id as r_id','roster_has_dates.to_time as roster_to_time']);//roster_to_time Added on 3-march-23 for last slot should not come

                //      $time_frames = $time_frames->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_weeks_has_time_frames.week_day_id','roster_has_weeks_has_time_frames.id as r_id','roster_has_dates.to_time as roster_to_time']);//roster_to_time Added on 3-march-23 for last slot should not come
                //      //Roshani made changes 'roster_has_weeks_has_time_frames.week_day_id' on 08-april-2025

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

                     //Added on 3-march-23 for last slot should not come
                    $roster_to_time = date("H:i",strtotime($time_frame->roster_to_time));

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

                         // Added on 3-march-23 for last slot should not come
                        if($added_time_frame>$roster_to_time)
                        {
                          $ignore_time_slots[$time_frame->date][] = $time;
                        }

                        if (array_key_exists($time_frame->date,$ignore_time_slots))
                        {
                            //dump($time, $ignore_time_slots[$time_frame->date]);
                            if(!in_array($time, $ignore_time_slots[$time_frame->date]))
                            {

                                if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];

                                }
                                elseif(strtotime($today_date)!==strtotime($time_frame->date))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                                }
                            }
                        }
                        else
                        {
                              if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];

                                }elseif(strtotime($today_date)!=strtotime($time_frame->date))
                                {
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
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


            Log::info("in getDoctorSlots function.roster_time_slots_date_wise.");
            Log::info($roster_time_slots_date_wise);


            //dd($roster_time_slots_date_wise);//,$doctor_appointment_time_frames
                    /*<thead>
                              <tr class="main_head">
                                 <th colspan="3">
                                    <h3>Online Terminvereinbarung</h3>
                                 </th>
                              </tr>
                           </thead>*/

                           /*Below commented on 27-may-25 by aishwarya*/
            /*$html= '<table id="customers">
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

                    ';*/

            /**************Aishwarya start added this code on 28-may-25************/
                    $html = '<table id="customers">
            <thead>
            <tr>
            <td colspan="3" style="text-align: center;">';

            if (count($roster_time_slots_date_wise) > 0) {
                // Check if time slots exist inside any of the dates
                $has_time_slots = false;
                foreach ($roster_time_slots_date_wise as $date => $data) {
                    if (!empty($data['time_slots']) && count($data['time_slots']) > 0) {
                        $has_time_slots = true;
                        break;
                    }
                }

                if ($has_time_slots) {
                    // Both dates and time slots exist
                    $html .= 'Wählen Sie einen der verfügbaren Termine für die von Ihnen gewählte Terminart <b>"' . $appointmentType->name . '"</b> aus.';
                } else {
                    // Dates exist but no time slots available
                    $html .= 'Im ausgewählten Zeitraum ist für die gewählte Terminart <b>"' . $appointmentType->name . '"</b> keine Termin verfügbar.';
                }
            } else {
                // No dates available at all
                $html .= 'Im ausgewählten Zeitraum ist für die gewählte Terminart <b>"' . $appointmentType->name . '"</b> kein Termin verfügbar.';
            }

            $html .= '</td>
            </tr>';

            if (count($roster_time_slots_date_wise) > 0 && !empty($has_time_slots)) {
                $html .= '
                <tr class="custMobThead">
                    <th width="50%">Datum</th>
                    <th>Uhrzeit</th>
                    <th>&nbsp;</th>
                </tr>';
            }

            $html .= '</thead>
            <tbody>
                <input type="hidden" name="hidden_week_day" id="hidden_week_day" value="' . implode(",", $week_day_ids) . '"/>
            ';


            /***********Aishwarya stop added this code on 28-may-25********/
            $msg =  __('admin.ERR_TIME_FRAME_NOT_FOUND');

            $index_key = 0;
            if(!empty($roster_time_slots_date_wise) && count($roster_time_slots_date_wise)>0){
                foreach($roster_time_slots_date_wise as $roster_date=>$roster_time_slot){

                    if(!empty($roster_time_slot['time_slots']) && sizeof($roster_time_slot['time_slots'])>0){

                        $select_rosters = '<div class="custMobileVisible">Uhrzeit</div><select
                                        name="time_slot_'.$index_key.'" onChange="assignValueToText('.$index_key.')"
                                        id="time_slot_'.$index_key.'"
                                        class="form-control select2"
                                        >';
                        sort($roster_time_slot['time_slots']);
                        // sort($roster_time_slot['time_slots_id']);

                        foreach ($roster_time_slot['time_slots'] as $key=>$time_slot) {

                             $select_rosters .='<option data-dr="single doctor" value="'.$time_slot.'" lang="'.$roster_time_slot['time_slots_id'][$time_slot].'">'.$time_slot.'</option>';
                        }
                        $select_rosters .= '</select>';
                        // dd($roster_date,$roster_time_slot['weekday']);
                        $html.='<tr>
                                    <td class="right2"><div class="custMobileVisible">Datum</div><b>'.$roster_time_slot['weekday'].'</b>, '.date('d.m.Y',strtotime($roster_date)).'</td>
                                    <td>'.$select_rosters.'</td>
                                    <td  class="card-footer"><button type="button" roster_date="'.$roster_date.'" id="roster_date" class="btn btn-success" onclick="arrangeTimeSlot(this,'.$index_key.')">VEREINBAREN</button>
                                    </td>
                                </tr>';

                        $index_key++;

                    }


                }

            }
            // else{
            //     $html.='<tr>
            //                 <td class="right2" colspan="3"><b>'.$msg.'</b></td>
            //             </tr>';
            // }
            $html .= '<input type="hidden" id="time_fram_hd_id" name="time_fram_hd_id" value=""></tbody></table>';

            $this->JsonData['html'] = $html;
            $this->JsonData['data'] = $roster_time_slots_date_wise;
            $this->JsonData['type'] = 'All doctors';
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('front.RESP_SUCCESS');

        } catch (Exception $e)
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);

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
                                 //->where('roster_has_weeks_has_time_frames.time_frame_flag','0')
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))

                                ->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_dates.week_day_id','roster_has_weeks_has_time_frames.id as r_id']);
            // echo $day_of_week;
           // dd($start_date,$end_date,$time_frames->toArray());
            //  exit();
             Log::info("i got the time frames");  
             Log::info($time_frame);
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
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];

                                }
                                elseif(strtotime($today_date)!==strtotime($time_frame->date))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                                }
                            }
                        }else
                        {
                              if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];

                                }elseif(strtotime($today_date)!=strtotime($time_frame->date))
                                {
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                     $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                                }

                        }

                        if(!empty($roster_time_slots_date_wise[$time_frame->date]['time_slots']))
                        {
                            $roster_time_slots_date_wise[$time_frame->date]['time_slots'] = array_unique($roster_time_slots_date_wise[$time_frame->date]['time_slots']);
                             $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
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
                                $select_rosters .='<option data-dr="all doctors" value="'.$time_slot.'" lang="'.$roster_time_slot['time_slots_id'][$time_slot].'">'.$time_slot.'</option>';
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
            $this->JsonData['type'] = 'All doctors';
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('front.RESP_SUCCESS');

        } catch (Exception $e)
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);

    }

    public function getDoctorSlots_old(Request $request)
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
                                // ->where(function($query) use($end_date,$start_date){
                                //     $query->where(function($query) use($end_date,$start_date){
                                //         $query->where('roster_has_weeks_has_time_frames.start_date','<',$end_date)
                                //         ->where('roster_has_weeks_has_time_frames.end_date','>',$start_date);
                                //     })->orWhere(function($query){
                                //         $query->whereNull('roster_has_weeks_has_time_frames.start_date')
                                //         ->whereNull('roster_has_weeks_has_time_frames.end_date');
                                //     });
                                // })
                                ->where('roster_has_weeks_has_time_frames.start_date','<=',$end_date)
                                ->where('roster_has_weeks_has_time_frames.end_date','>=',$start_date)
                                //->where('roster_has_weeks_has_time_frames.time_frame_flag','0')
                                ->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_dates.week_day_id','roster_has_weeks_has_time_frames.id as r_id']);
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
                                ->select(("(DATE_FORMAT(start_date,'%H:%i')) as start_date,(DATE_FORMAT(end_date,'%H:%i')) as end_date"))
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
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];

                                }
                                elseif(strtotime($today_date)!==strtotime($time_frame->date))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                                }
                            }
                        }
                        else
                        {
                              if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];

                                }elseif(strtotime($today_date)!=strtotime($time_frame->date))
                                {
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
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

            //dd($roster_time_slots_date_wise);//,$doctor_appointment_time_frames
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
                                        name="time_slot_'.$index_key.'" onChange="assignValueToText('.$index_key.')"
                                        id="time_slot_'.$index_key.'"
                                        class="form-control select2"
                                        >';
                        sort($roster_time_slot['time_slots']);
                        // sort($roster_time_slot['time_slots_id']);

                        foreach ($roster_time_slot['time_slots'] as $key=>$time_slot) {

                             $select_rosters .='<option data-dr="single doctor" value="'.$time_slot.'" lang="'.$roster_time_slot['time_slots_id'][$time_slot].'">'.$time_slot.'</option>';
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
            $html .= '<input type="hidden" id="time_fram_hd_id" name="time_fram_hd_id" value=""></tbody></table>';

            $this->JsonData['html'] = $html;
            $this->JsonData['data'] = $roster_time_slots_date_wise;
            $this->JsonData['type'] = 'All doctors';
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('front.RESP_SUCCESS');

        } catch (Exception $e)
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);

    }
     public function getDoctorSlots_stage_26_may_25_org(Request $request)
    {
       // dump($request->all());
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
            $hidden_patient_id      = $request->hidden_patient_id;

           /*************************************************************/

            $quarter_setting=0;
            $OPTIMAL_APPOINTMENT ='OPTIMAL_APPOINTMENT';
            $optimal_appointment = DB::table('settings')->where(['setting_key'=>$OPTIMAL_APPOINTMENT])->select('setting_key','setting_value','description')->first();
            if(isset($optimal_appointment) && !empty($optimal_appointment))
            {
                $quarter_setting = $optimal_appointment->setting_value;
            }//if optimal appointment


            if($quarter_setting==1 && isset($request->hidden_patient_id))
           {

                $ignoreQuarterArr = $ignoreYearArr=$ignoreArray=[];
                $get_quarters = $this->get_quarters($start_date,$end_date);
                if(isset($get_quarters) && !empty($get_quarters))
                {
                    $ignoreArr=[]; $whereQuarter=''; $quarterCheckFlag=0;
                    $whereQuarter="Case ";
                    foreach($get_quarters as $k=>$v)
                    {
                       $quarter = $v->period;
                       $year = $v->year;

                        $checkAppoimentBooked = $this->BaseModel
                                            ->whereRaw("quarter(start_date)=$quarter and year(start_date)=$year")
                                           // ->where('doctor_id',$doctor_id) //commented on 14-sept-22
                                            ->where('patient_id',$hidden_patient_id)
                                            ->where('status',1)
                                            // ->where('appointment_status','!=','Vermisst')   // Added on 21-sept-22 //Roshani hidden the line on 15-april-25 for point Trello 281
                                            ->first();

                        if(isset($checkAppoimentBooked) && !empty($checkAppoimentBooked))
                        {
                            $ignoreQuarterArr[] = $quarter;
                            $ignoreYearArr[] = $year;
                            $ignoreArr['quarter'] = $quarter;
                            $ignoreArr['year'] = $year;

                            $whereQuarter.="WHEN quarter(roster_has_dates.date)=$quarter THEN year(roster_has_dates.date)!='$year'";

                            $quarterCheckFlag=1;

                        }//if checkAppoimentBooked

                        if(isset($ignoreArr) && !empty($ignoreArr))
                        {
                             $ignoreArray[] = $ignoreArr;
                         }//



                    }//foreach

                    $whereQuarter.="ELSE 1=1 END ";
                }//if
           }

           /*************************************************************/



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

                 // dump($start_date);
                 // dump($end_date);
                 // dump($doctor_id);

                  $time_frames = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')

                                // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id')
                                //commented on 26-may-25 for #336

                                  ->join('roster_has_weeks_has_time_frames', function ($join) {
                                    $join->on('roster_has_weeks_has_time_frames.roster_id', '=', 'roster_has_dates.roster_id')
                                         ->on('roster_has_weeks_has_time_frames.week_day_id', '=', 'roster_has_dates.week_day_id');
                                })//changed on 26-may-25 for #336


                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                 ->whereDate('roster_has_dates.date','>=', $start_date)
                                 ->whereDate('roster_has_dates.date','<=', $end_date)
                                 ->whereIn('roster_has_weeks_has_time_frames.week_day_id',$week_day_ids)
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                                // ->where(function($query) use($end_date,$start_date){
                                //     $query->where(function($query) use($end_date,$start_date){
                                //         $query->where('roster_has_weeks_has_time_frames.start_date','<',$end_date)
                                //         ->where('roster_has_weeks_has_time_frames.end_date','>',$start_date);
                                //     })->orWhere(function($query){
                                //         $query->whereNull('roster_has_weeks_has_time_frames.start_date')
                                //         ->whereNull('roster_has_weeks_has_time_frames.end_date');
                                //     });
                                // })
                                ->where('roster_has_weeks_has_time_frames.start_date','<=',$end_date)
                                ->where('roster_has_weeks_has_time_frames.end_date','>=',$start_date);
                                //->where('roster_has_weeks_has_time_frames.time_frame_flag','0')

                     if(isset($quarterCheckFlag) && $quarterCheckFlag==1 && $quarter_setting==1)
                     {
                        $time_frames =$time_frames->whereRaw($whereQuarter);
                     }

                      // $time_frames = $time_frames->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_dates.week_day_id','roster_has_weeks_has_time_frames.id as r_id','roster_has_dates.to_time as roster_to_time']);//roster_to_time Added on 3-march-23 for last slot should not come

                    

                      $time_frames = $time_frames->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_weeks_has_time_frames.week_day_id','roster_has_weeks_has_time_frames.id as r_id','roster_has_dates.to_time as roster_to_time']);//roster_to_time Added on 3-march-23 for last slot should not come
                     //Roshani made changes 'roster_has_weeks_has_time_frames.week_day_id' on 08-april-2025


                     // dd($time_frames);

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


            // dump("Raw time_frames count: " . count($time_frames));
            // foreach ($time_frames as $tf) {
            //     dump("Date: {$tf->date}, Weekday ID: {$tf->week_day_id}, Time Frame: {$tf->time_frame}");
            // }




            if(!empty($time_frames) && count($time_frames)>0){
                $msg = '';
                foreach($time_frames as $time_frame)
                {


                     // $roster_time_slots_date_wise[$time_frame->date]['weekday'] = $this->WeekDaysModel->where('id',$time_frame->week_day_id)->pluck('day')->first();

                    // $dayName = $this->WeekDaysModel->where('id', $time_frame->week_day_id)->pluck('day')->first();
                    // $roster_time_slots_date_wise[$time_frame->date]['weekday'][$time_frame->week_day_id] = $dayName;

                    $date_key = $time_frame->date . '_' . $time_frame->week_day_id;
                    if (!isset($roster_time_slots_date_wise[$date_key]['weekday'])) {
                        $roster_time_slots_date_wise[$date_key]['weekday'] = $this->WeekDaysModel->where('id', $time_frame->week_day_id)->pluck('day')->first();
                    }


                    $response['duration'] = $default_time_duration;

                    $time = date("H:i",strtotime($time_frame->time_frame));
                    $added_time_frame =  date("H:i",strtotime($time) + $appointmentDuration);

                    // dump("added_time_frame==>");
                    // dump($added_time_frame);

                    $selected="";




                    $t= Carbon::parse($time)->format('H:i');
                    $ft= Carbon::parse($from_time)->format('H:i');
                    $to= Carbon::parse($to_time)->format('H:i');

                     //Added on 3-march-23 for last slot should not come
                    $roster_to_time = date("H:i",strtotime($time_frame->roster_to_time));






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

                         // Added on 3-march-23 for last slot should not come
                        if($added_time_frame>$roster_to_time)
                        {
                          $ignore_time_slots[$time_frame->date][] = $time;
                        }

                        if (array_key_exists($time_frame->date,$ignore_time_slots))
                        {
                            //dump($time, $ignore_time_slots[$time_frame->date]);
                            if(!in_array($time, $ignore_time_slots[$time_frame->date]))
                            {

                                if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$date_key]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$date_key]['time_slots_id'][$time] = $time_frame['r_id'];

                                }
                                elseif(strtotime($today_date)!==strtotime($time_frame->date))
                                {
                                   $roster_time_slots_date_wise[$date_key]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$date_key]['time_slots_id'][$time] = $time_frame['r_id'];
                                }
                            }
                        }
                        else
                        {
                              if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$date_key]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$date_key]['time_slots_id'][$time] = $time_frame['r_id'];

                                }elseif(strtotime($today_date)!=strtotime($time_frame->date))
                                {
                                    $roster_time_slots_date_wise[$date_key]['time_slots'][] = $time;
                                    $roster_time_slots_date_wise[$date_key]['time_slots_id'][$time] = $time_frame['r_id'];
                                }

                        }

                        if(!empty($roster_time_slots_date_wise[$time_frame->date]['time_slots']))
                        {
                            $roster_time_slots_date_wise[$date_key]['time_slots'] = array_unique($roster_time_slots_date_wise[$time_frame->date]['time_slots']);
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

            //dump($roster_time_slots_date_wise);

            //dd($roster_time_slots_date_wise);//,$doctor_appointment_time_frames
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



           /* if(!empty($roster_time_slots_date_wise) && count($roster_time_slots_date_wise)>0){
                foreach($roster_time_slots_date_wise as $roster_date=>$roster_time_slot){

                    if(!empty($roster_time_slot['time_slots']) && sizeof($roster_time_slot['time_slots'])>0){

                        $select_rosters = '<div class="custMobileVisible">Uhrzeit</div><select
                                        name="time_slot_'.$index_key.'" onChange="assignValueToText('.$index_key.')"
                                        id="time_slot_'.$index_key.'"
                                        class="form-control select2"
                                        >';
                        sort($roster_time_slot['time_slots']);
                        // sort($roster_time_slot['time_slots_id']);

                        foreach ($roster_time_slot['time_slots'] as $key=>$time_slot) {

                             $select_rosters .='<option data-dr="single doctor" value="'.$time_slot.'" lang="'.$roster_time_slot['time_slots_id'][$time_slot].'">'.$time_slot.'</option>';
                        }
                        $select_rosters .= '</select>';
                        // dd($roster_date,$roster_time_slot['weekday']);
                        $html.='<tr>
                                    <td class="right2"><div class="custMobileVisible">Datum</div><b>'.$roster_time_slot['weekday'].'</b>, '.date('d.m.Y',strtotime($roster_date)).'</td>
                                    <td>'.$select_rosters.'</td>
                                    <td  class="card-footer"><button type="button" roster_date="'.$roster_date.'" id="roster_date" class="btn btn-success" onclick="arrangeTimeSlot(this,'.$index_key.')">VEREINBAREN</button>
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
            $html .= '<input type="hidden" id="time_fram_hd_id" name="time_fram_hd_id" value=""></tbody></table>';*/


           // Example: $roster_time_slots_date_wise is your array as shown above
                
                //dump($roster_time_slots_date_wise);
                 
                if (!empty($roster_time_slots_date_wise)) {


                    foreach ($roster_time_slots_date_wise as $roster_key => $roster_time_slot) {



                        // Split the key to get date and weekday_id
                        $parts = explode('_', $roster_key);
                        $roster_date = $parts[0];
                        $weekday = isset($roster_time_slot['weekday']) ? $roster_time_slot['weekday'] : '';

                        
                      

                        // Only show rows with time slots
                        if (!empty($roster_time_slot['time_slots'])) {

                           // dump($roster_date.'---'.$weekday);

                            $select_rosters = '<div class="custMobileVisible">Uhrzeit</div><select
                                name="time_slot_' . $index_key . '" onChange="assignValueToText(' . $index_key . ')"
                                id="time_slot_' . $index_key . '"
                                class="form-control select2"
                >';
                            sort($roster_time_slot['time_slots']);
                            foreach ($roster_time_slot['time_slots'] as $time_slot) {
                                $select_rosters .= '<option data-dr="single doctor" value="' . $time_slot . '" lang="' . $roster_time_slot['time_slots_id'][$time_slot] . '">' . $time_slot . '</option>';
                            }
                            $select_rosters .= '</select>';

                 
                            $html .= '<tr>
                <td class="right2"><div class="custMobileVisible">Datum</div><b>' . $weekday . '</b>, ' . date('d.m.Y', strtotime($roster_date)) . '</td>
                <td>' . $select_rosters . '</td>
                <td class="card-footer"><button type="button" roster_date="' . $roster_date . '" id="roster_date" class="btn btn-success" onclick="arrangeTimeSlot(this,' . $index_key . ')">VEREINBAREN</button>
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
            $this->JsonData['type'] = 'All doctors';
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('front.RESP_SUCCESS');

        } catch (Exception $e)
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);

    }//getDoctorSlots
    //Smart Appoimnet 15-Sep-22 Replace by divya ======================
    public function getDoctorSlots1(Request $request)
    {
        
        Log::info("in getDoctorSlots function..");
        Log::info($request->all());

        //dump($request->all());
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');

        try
        {

            ///Roshani added the code for check date
//             $today = Carbon::today();
// $errors = [];

// // ✅ safely parse start_date
// $start = null;
// if (!empty($request->start_date)) {
//     $start = Carbon::createFromFormat('d/m/Y', $request->start_date, 'UTC');
//     if ($start === false) {
//         $this->JsonData['errmsg'] = __('api.ERR_INVALID_DATE_FORMAT');
//         $this->JsonData['status'] = __('front.RESP_ERROR');
//         return response()->json($this->JsonData);
//     }
// }

// // ✅ safely parse end_date
// $end = null;
// if (!empty($request->end_date)) {
//     $end = Carbon::createFromFormat('d/m/Y', $request->end_date, 'UTC');
//     if ($end === false) {
//         $this->JsonData['errmsg'] = __('api.ERR_INVALID_DATE_FORMAT');
//         $this->JsonData['status'] = __('front.RESP_ERROR');
//         return response()->json($this->JsonData);
//     }
// }

// // 🚫 start_date cannot be in the past
// if ($start && $start->lt($today)) {
//     $this->JsonData['errmsg'] = __('api.ERR_START_DATE_PAST');
//     $this->JsonData['status'] = __('front.RESP_ERROR');
//     return response()->json($this->JsonData);
// }

// // 🚫 end_date must be today or future
// if ($end && $end->lt($today)) {
//     $this->JsonData['errmsg'] = __('api.ERR_END_DATE_PAST');
//     $this->JsonData['status'] = __('front.RESP_ERROR');
//     return response()->json($this->JsonData);
// }

// // 🚫 end_date must be >= start_date
// if ($start && $end && $end->lt($start)) {
//     $this->JsonData['errmsg'] = __('api.ERR_END_BEFORE_START');
//     $this->JsonData['status'] = __('front.RESP_ERROR');
//     return response()->json($this->JsonData);
// }
            ///Roshani added the code for check date


            $doctor_id              = $request->doctor_id;
            $appointment_type_id    = $request->appointment_type_id;
            $week_day_ids            = $request->week_day_id;
            $start_date  = date("Y-m-d",strtotime(str_replace("/","-", $request->start_date)));
            $end_date  = date("Y-m-d",strtotime(str_replace("/","-", $request->end_date)));
            $from_time              = $request->from_time;
            $to_time                = $request->to_time;
            $hidden_patient_id      = $request->hidden_patient_id;

           /*************************************************************/

           /**************added on 8-aug-25-for user manually enter date issue*************/

            if(isset($request->doctor_id) && !empty($request->doctor_id))
            {
                Log::info("if doctor is present");
               Log::info("in getDoctorSlots function.user manual enter date code doc not empty.");


                $quarterSetting=0;
                $OPTIMAL_APPOINTMENT ='OPTIMAL_APPOINTMENT';
                $optimal_appointment_setting = DB::table('settings')->where(['setting_key'=>$OPTIMAL_APPOINTMENT])->select('setting_key','setting_value','description')->first();
                Log::info($optimal_appointment_setting->setting_value);
                if(isset($optimal_appointment_setting) && !empty($optimal_appointment_setting))
                {
                    $quarterSetting = $optimal_appointment_setting->setting_value;


                }//if optimal appointment

                Log::info("in getDoctorSlots function.user manual enter date quarterSetting");
                Log::info($quarterSetting);


                // changes by vijay 7/3/24
                $optimalAppointment = 0;
                if (isset ($request->appointment_type_id) && !empty ($request->appointment_type_id)) {
                    $checkAppointmentType = $this->AppointmentTypesModel->where('id', $request->appointment_type_id)->first();
                    $optimalAppointment = $checkAppointmentType->optimal_appointment;
                }
                //end changes

                Log::info("in getDoctorSlots function.user manual enter date optimalAppointment");
                Log::info($optimalAppointment);
                Log::info('Already registerd =>'.$request->is_already_registered);
                Log::info('quarterly setting =>'.$quarterSetting);




                $todaysdate = date('Y-m-d');
                
                // if ($optimalAppointment == 1 && $request->is_already_registered == 1 && ($quarterSetting == 1 || $quarterSetting == 0))
                // {
                //     log::info("in if of optimalAppointment");
                //     Log::info("in getDoctorSlots function.user manual enter date optimalAppointment 1 ");

                //         // Get quarter and year from that start date
                //         $quarter = ceil(date('n', strtotime($start_date)) / 3);
                //         $year = date('Y', strtotime($start_date));
                //         $patient_id = $request->patient_id;

                //        //  dump($quarter);
                //        // dump($year);
                //        // dump($patient_id);

                //         Log::info("in getDoctorSlots function.user manual enter date quarter");
                //         Log::info($quarter);

                //         Log::info("in getDoctorSlots function.user manual enter date year");
                //         Log::info($year);

                //         if(isset($patient_id))
                //         {
                            
                //             $check_appointment_exists = $this->BaseModel
                //                 ->whereRaw("quarter(start_date) = $quarter AND year(start_date) = $year")
                //                 ->where('patient_id', $patient_id)
                //                 ->where('status', 1)
                //                 ->where('appointment_status', '!=', 'Vermisst')
                //                 ->first();

                //             Log::info("in getDoctorSlots function.user manual enter date check_appointment_exists");

                //             if (!empty($check_appointment_exists)) {
                //                 // STOP here and return error message
                              
                //                Log::info("in getDoctorSlots function.user manual enter date check_appointment_exists not empty ");

                //                     //added new code on 13-aug-25
                //                     $html1 = '<table id="customers">
                //                         <thead>
                //                             <tr>
                //                                 <td colspan="3" style="text-align: center;">';

            
                //                                  // No dates available at all
                //                                  $html1 .= 'Im ausgewählten Zeitraum ist kein Termin verfügbar.';
            
                //                                 $html1 .= '</td>
                //                           </tr>';

           
                //                          $html1 .= '</thead>
                //                         <tbody>';

                //                     // $html.='<tr>
                //                     //         <td class="right2" colspan="3"><b>'.$msg.'</b></td>
                //                     //     </tr>';
                            
                //                     $roster_time_slots_date_wise = array();

                //                     $this->JsonData['html'] = $html1;
                //                     $this->JsonData['data'] = $roster_time_slots_date_wise;
                //                     $this->JsonData['type'] = 'All doctors';
                //                     $this->JsonData['msg']  = '';
                //                     $this->JsonData['status'] = __('front.RESP_SUCCESS');
                //                     return response()->json($this->JsonData);

                //                     // $this->JsonData['errmsg']  = __('front.ERR_DOUBLE_BOOKING');
                //                     // $this->JsonData['status'] = __('front.RESP_ERROR');
                //                     // return response()->json($this->JsonData);


                //             }//if not empty check_appointment_exists
                //         }

                // }//if settings 1
                Log::info("outsite is setting 1");
            }//if doctor id
            Log::info("outeside if");
            /***********end 8-aug-25********************************************************/



            $quarter_setting=0;
            $OPTIMAL_APPOINTMENT ='OPTIMAL_APPOINTMENT';
            $optimal_appointment = DB::table('settings')->where(['setting_key'=>$OPTIMAL_APPOINTMENT])->select('setting_key','setting_value','description')->first();
            if(isset($optimal_appointment) && !empty($optimal_appointment))
            {
                $quarter_setting = $optimal_appointment->setting_value;
            }//if optimal appointment

            Log::info("in getDoctorSlots function.quarter_setting.");
            Log::info("quarter_setting". $quarter_setting);
            Log::info("request->hidden_patient_id". $request->hidden_patient_id);

            if($quarter_setting==1 && isset($request->hidden_patient_id))
           {
                Log::info("in getDoctorSlots function.quarter_setting. is 1..");

                $ignoreQuarterArr = $ignoreYearArr=$ignoreArray=[];
                $get_quarters = $this->get_quarters($start_date,$end_date);
                if(isset($get_quarters) && !empty($get_quarters))
                {
                    $ignoreArr=[]; $whereQuarter=''; $quarterCheckFlag=0;
                    $whereQuarter="Case ";
                    foreach($get_quarters as $k=>$v)
                    {
                       $quarter = $v->period;
                       $year = $v->year;

                        Log::info("quarter===>");
                        Log::info($quarter);

                        Log::info("year===>");
                        Log::info($year);

                        $checkAppoimentBooked = $this->BaseModel
                                            ->whereRaw("quarter(start_date)=$quarter and year(start_date)=$year")
                                           // ->where('doctor_id',$doctor_id) //commented on 14-sept-22
                                            ->where('patient_id',$hidden_patient_id)
                                            ->where('status',1)
                                            // ->where('appointment_status','!=','Vermisst')   // Added on 21-sept-22 //Roshani hidden the line on 15-april-25 for point Trello 281
                                            ->first();

                        Log::info("in getDoctorSlots function.quarter_setting checkAppoimentBooked ");                    

                        if(isset($checkAppoimentBooked) && !empty($checkAppoimentBooked))
                        {
                            Log::info("in getDoctorSlots function.quarter_setting in checkAppoimentBooked ");  

                            $ignoreQuarterArr[] = $quarter;
                            $ignoreYearArr[] = $year;
                            $ignoreArr['quarter'] = $quarter;
                            $ignoreArr['year'] = $year;

                            $whereQuarter.="WHEN quarter(roster_has_dates.date)=$quarter THEN year(roster_has_dates.date)!='$year'";

                            $quarterCheckFlag=1;

                        }//if checkAppoimentBooked

                        if(isset($ignoreArr) && !empty($ignoreArr))
                        {
                             $ignoreArray[] = $ignoreArr;
                         }//



                    }//foreach

                    $whereQuarter.="ELSE 1=1 END ";
                }//if
           }

           /*************************************************************/



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
            /**************** check the appointment is booked in this quarter already? ******************/
        if(isset($quarter_setting) && $quarter_setting==1)
        {
             $patient_id = $request->patient_id;

            // 🔎 Get all booked quarters grouped by year
            $bookedQuartersByYear = $this->BaseModel
                ->selectRaw("YEAR(start_date) as year, QUARTER(start_date) as quarter")
                ->where('patient_id', $patient_id)
                ->where('status', 1)
                ->where('appointment_status', '!=', 'Vermisst')
                ->groupBy('year', 'quarter')
                ->orderBy('year')
                ->orderBy('quarter')
                ->get();

            Log::info("Booked quarters by year: " . json_encode($bookedQuartersByYear));

            $bookedRanges = [];

            // build start & end dates for each booked quarter across all years
            foreach ($bookedQuartersByYear as $row) {
                $y = $row->year;
                $q = $row->quarter;

                switch ($q) {
                    case 1:
                        $bookedRanges[] = [
                            'start' => Carbon::create($y, 1, 1),
                            'end'   => Carbon::create($y, 3, 31),
                        ];
                        break;
                    case 2:
                        $bookedRanges[] = [
                            'start' => Carbon::create($y, 4, 1),
                            'end'   => Carbon::create($y, 6, 30),
                        ];
                        break;
                    case 3:
                        $bookedRanges[] = [
                            'start' => Carbon::create($y, 7, 1),
                            'end'   => Carbon::create($y, 9, 30),
                        ];
                        break;
                    case 4:
                        $bookedRanges[] = [
                            'start' => Carbon::create($y, 10, 1),
                            'end'   => Carbon::create($y, 12, 31),
                        ];
                        break;
                }
            }

            Log::info("Booked ranges across years: " . json_encode(array_map(function ($r) {
                return $r['start']->toDateString() . " → " . $r['end']->toDateString();
            }, $bookedRanges)));

        }
           
/**************** check the appointment is booked in this quarter already? ******************/
// $roster_time_slots_date_wise = array();
$roster_time_slots_date_wise = array();
$time_frames = $this->RosterModel
    ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
    ->join('roster_has_weeks_has_time_frames', function ($join) {
        $join->on('roster_has_weeks_has_time_frames.roster_id', '=', 'roster_has_dates.roster_id')
            ->on('roster_has_weeks_has_time_frames.week_day_id', '=', 'roster_has_dates.week_day_id');
    })
    ->where('roster.doctor_id',$doctor_id)
    ->where('roster_has_dates.is_excluded','=',0)
    ->whereDate('roster_has_dates.date','>=', $start_date)
    ->whereDate('roster_has_dates.date','<=', $end_date)
    ->whereIn('roster_has_weeks_has_time_frames.week_day_id',$week_day_ids)
    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
    ->where('roster_has_weeks_has_time_frames.start_date','<=',$end_date)
    ->where('roster_has_weeks_has_time_frames.end_date','>=',$start_date);

// 🚫 Exclude booked quarter ranges only if quarter_setting == 1 and ranges exist
if (isset($quarter_setting) && $quarter_setting == 1 && !empty($bookedRanges)) {
    foreach ($bookedRanges as $range) {
        $time_frames->whereNotBetween('roster_has_dates.date', [
            $range['start']->toDateString(),
            $range['end']->toDateString()
        ]);
    }

    if (isset($quarterCheckFlag) && $quarterCheckFlag == 1) {
        $time_frames = $time_frames->whereRaw($whereQuarter);
    }
}

$time_frames = $time_frames->get([
    'roster_has_dates.date',
    'roster_has_weeks_has_time_frames.time_frame',
    'roster_has_dates.start_date',
    'roster_has_dates.end_date',
    'roster_has_weeks_has_time_frames.week_day_id',
    'roster_has_weeks_has_time_frames.id as r_id',
    'roster_has_dates.to_time as roster_to_time'
]);
                //   $time_frames = $this->RosterModel
                //                 ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                //                 // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')


                //                 //commented on 26-may-25    
                //                 // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id')

                //                 ->join('roster_has_weeks_has_time_frames', function ($join) {
                //                     $join->on('roster_has_weeks_has_time_frames.roster_id', '=', 'roster_has_dates.roster_id')
                //                          ->on('roster_has_weeks_has_time_frames.week_day_id', '=', 'roster_has_dates.week_day_id');
                //                 }) //added on 26-may-25 


                //                 ->where('roster.doctor_id',$doctor_id)
                //                 ->where('roster_has_dates.is_excluded','=',0)
                //                  ->whereDate('roster_has_dates.date','>=', $start_date)
                //                  ->whereDate('roster_has_dates.date','<=', $end_date)
                //                  ->whereIn('roster_has_weeks_has_time_frames.week_day_id',$week_day_ids)
                //                 ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                //                 ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                //                 // ->where(function($query) use($end_date,$start_date){
                //                 //     $query->where(function($query) use($end_date,$start_date){
                //                 //         $query->where('roster_has_weeks_has_time_frames.start_date','<',$end_date)
                //                 //         ->where('roster_has_weeks_has_time_frames.end_date','>',$start_date);
                //                 //     })->orWhere(function($query){
                //                 //         $query->whereNull('roster_has_weeks_has_time_frames.start_date')
                //                 //         ->whereNull('roster_has_weeks_has_time_frames.end_date');
                //                 //     });
                //                 // })
                //                 ->where('roster_has_weeks_has_time_frames.start_date','<=',$end_date)
                //                 ->where('roster_has_weeks_has_time_frames.end_date','>=',$start_date);
                //                 //->where('roster_has_weeks_has_time_frames.time_frame_flag','0')

                //      if(isset($quarterCheckFlag) && $quarterCheckFlag==1 && $quarter_setting==1)
                //      {
                //         $time_frames =$time_frames->whereRaw($whereQuarter);
                //      }

                //      // $time_frames = $time_frames->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_weeks_has_time_frames.week_day_id','roster_has_weeks_has_time_frames.id as r_id','roster_has_dates.to_time as roster_to_time']);//roster_to_time Added on 3-march-23 for last slot should not come

                //      $time_frames = $time_frames->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_weeks_has_time_frames.week_day_id','roster_has_weeks_has_time_frames.id as r_id','roster_has_dates.to_time as roster_to_time']);//roster_to_time Added on 3-march-23 for last slot should not come
                //      //Roshani made changes 'roster_has_weeks_has_time_frames.week_day_id' on 08-april-2025

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

                     //Added on 3-march-23 for last slot should not come
                    $roster_to_time = date("H:i",strtotime($time_frame->roster_to_time));

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

                         // Added on 3-march-23 for last slot should not come
                        if($added_time_frame>$roster_to_time)
                        {
                          $ignore_time_slots[$time_frame->date][] = $time;
                        }

                        if (array_key_exists($time_frame->date,$ignore_time_slots))
                        {
                            //dump($time, $ignore_time_slots[$time_frame->date]);
                            if(!in_array($time, $ignore_time_slots[$time_frame->date]))
                            {

                                if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];

                                }
                                elseif(strtotime($today_date)!==strtotime($time_frame->date))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                                }
                            }
                        }
                        else
                        {
                              if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                {
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                   $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];

                                }elseif(strtotime($today_date)!=strtotime($time_frame->date))
                                {
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                    $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
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


            Log::info("in getDoctorSlots function.roster_time_slots_date_wise.");
            Log::info($roster_time_slots_date_wise);


            //dd($roster_time_slots_date_wise);//,$doctor_appointment_time_frames
                    /*<thead>
                              <tr class="main_head">
                                 <th colspan="3">
                                    <h3>Online Terminvereinbarung</h3>
                                 </th>
                              </tr>
                           </thead>*/

                           /*Below commented on 27-may-25 by aishwarya*/
            /*$html= '<table id="customers">
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

                    ';*/

            /**************Aishwarya start added this code on 28-may-25************/
                    $html = '<table id="customers">
            <thead>
            <tr>
            <td colspan="3" style="text-align: center;">';

            if (count($roster_time_slots_date_wise) > 0) {
                // Check if time slots exist inside any of the dates
                $has_time_slots = false;
                foreach ($roster_time_slots_date_wise as $date => $data) {
                    if (!empty($data['time_slots']) && count($data['time_slots']) > 0) {
                        $has_time_slots = true;
                        break;
                    }
                }

                if ($has_time_slots) {
                    // Both dates and time slots exist
                    $html .= 'Wählen Sie einen der verfügbaren Termine für die von Ihnen gewählte Terminart <b>"' . $appointmentType->name . '"</b> aus.';
                } else {
                    // Dates exist but no time slots available
                    $html .= 'Im ausgewählten Zeitraum ist für die gewählte Terminart <b>"' . $appointmentType->name . '"</b> keine Termin verfügbar.';
                }
            } else {
                // No dates available at all
                $html .= 'Im ausgewählten Zeitraum ist für die gewählte Terminart <b>"' . $appointmentType->name . '"</b> kein Termin verfügbar.';
            }

            $html .= '</td>
            </tr>';

            if (count($roster_time_slots_date_wise) > 0 && !empty($has_time_slots)) {
                $html .= '
                <tr class="custMobThead">
                    <th width="50%">Datum</th>
                    <th>Uhrzeit</th>
                    <th>&nbsp;</th>
                </tr>';
            }

            $html .= '</thead>
            <tbody>
                <input type="hidden" name="hidden_week_day" id="hidden_week_day" value="' . implode(",", $week_day_ids) . '"/>
            ';


            /***********Aishwarya stop added this code on 28-may-25********/
            $msg =  __('admin.ERR_TIME_FRAME_NOT_FOUND');

            $index_key = 0;
            if(!empty($roster_time_slots_date_wise) && count($roster_time_slots_date_wise)>0){
                foreach($roster_time_slots_date_wise as $roster_date=>$roster_time_slot){

                    if(!empty($roster_time_slot['time_slots']) && sizeof($roster_time_slot['time_slots'])>0){

                        $select_rosters = '<div class="custMobileVisible">Uhrzeit</div><select
                                        name="time_slot_'.$index_key.'" onChange="assignValueToText('.$index_key.')"
                                        id="time_slot_'.$index_key.'"
                                        class="form-control select2"
                                        >';
                        sort($roster_time_slot['time_slots']);
                        // sort($roster_time_slot['time_slots_id']);

                        foreach ($roster_time_slot['time_slots'] as $key=>$time_slot) {

                             $select_rosters .='<option data-dr="single doctor" value="'.$time_slot.'" lang="'.$roster_time_slot['time_slots_id'][$time_slot].'">'.$time_slot.'</option>';
                        }
                        $select_rosters .= '</select>';
                        // dd($roster_date,$roster_time_slot['weekday']);
                        $html.='<tr>
                                    <td class="right2"><div class="custMobileVisible">Datum</div><b>'.$roster_time_slot['weekday'].'</b>, '.date('d.m.Y',strtotime($roster_date)).'</td>
                                    <td>'.$select_rosters.'</td>
                                    <td  class="card-footer"><button type="button" roster_date="'.$roster_date.'" id="roster_date" class="btn btn-success" onclick="arrangeTimeSlot(this,'.$index_key.')">VEREINBAREN</button>
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
            $html .= '<input type="hidden" id="time_fram_hd_id" name="time_fram_hd_id" value=""></tbody></table>';

            $this->JsonData['html'] = $html;
            $this->JsonData['data'] = $roster_time_slots_date_wise;
            $this->JsonData['type'] = 'All doctors';
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('front.RESP_SUCCESS');

        } catch (Exception $e)
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);

    }//getDoctorSlots
    //End Smart Appoimnet 15-Sep-22 Replace by divya ======================

    public function selectTimeFrame(Request $request)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');
        try
        {
            $updateTimeFrameFlg = $this->RosterHasWeeksHasTimeFramesModel->find($request->time_frame_id);
            $updateTimeFrameFlg->time_frame_flag = '1';
            $updateTimeFrameFlg->time_frame_flag_date = Date('Y-m-d H:i:s');
            $updateTimeFrameFlg->save();

            if(isset($request->time_frame_id_old) && !empty($request->time_frame_id_old) && $request->time_frame_id_old!= 'undefined')
            {
                $oldUpdateTimeFrameFlg = $this->RosterHasWeeksHasTimeFramesModel->find($request->time_frame_id_old);
                $oldUpdateTimeFrameFlg->time_frame_flag = '0';
                $oldUpdateTimeFrameFlg->time_frame_flag_date = Date('Y-m-d H:i:s');
                $oldUpdateTimeFrameFlg->save();
            }
        }
        catch (Exception $e)
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
    public function test()
    {
        return view($this->ModuleView.'test', $this->ViewData);
    }

    public function testcall()
    {
        return 1;
    }

    public function updateOldPatient()
    {
        $getrecord = $this->OldPatientsModel->get();
        //dd($getrecord);
        if(!empty($getrecord) && count($getrecord)>0)
        {
            $cnt = 1;
            foreach ($getrecord as $key => $value)
            {
                $newRecord = $this->PatientsModel
                             ->where('update_ganydb',0)
                             ->find($value['fk_patient_id']);
                             //dd($newRecord);
                if(!empty($newRecord))
                {
                    // dump($newRecord->id);
                    // dump($newRecord->id);
                    $this->PatientsModel
                    ->where('id',$newRecord->id)
                    ->update(['update_ganydb'=>1]);
                    $cnt++;

                }
            }
        }
        dd("completed....");
    }

    // public function getServiceDetails($service_id,$patient_id)
     public function getServiceDetails($service_id,$patient_id=false)
    {
        $service_id = base64_decode($service_id);

        //added on 19-dec-25
        if(isset($patient_id) && !empty($patient_id))
        {

            //start added on 24-nov-25
            $patient_id = base64_decode($patient_id);
            $patientDetails = $this->PatientsModel->where('id',$patient_id)->first();

            // If patient not found then show error added on 24-nov-25
            if (empty($patientDetails)) {
                return view($this->ModuleView.'service-details', [
                    'error' => __('front.ERR_PATIENT_DELETED'),
                ]);
            }
            //end added on 24-nov-25 

        }//added on 19-dec-25


        $getExaminationDetails = $this->ExaminationsModel->find($service_id);
        $this->ViewData['moduleTitle']  = __('admin.TITLE_APPOINTMENT_SERVICES');
        $this->ViewData['moduleAction'] = __('admin.TITLE_APPOINTMENT_SERVICES');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['serviceDetails'] = $getExaminationDetails;
        $this->ViewData['null_parameter']   = base64_encode('null'); //added on 18-dec-23 (29-feb-24)
        $this->ViewData['service_name']   = base64_encode($service_id); //added on 18-dec-23 (29-feb-24)

        return view($this->ModuleView.'service-details', $this->ViewData);

    }//getServiceDetails

    //Smart Appoitment 15-Sep-22 added by divya===============================
     public function rename_roshani_on_08_april_2024_postPatient(Request $request)
    {
        $urlEventId = $urlPatientId = '';
        //dd($request->all());

         /**********added on 20-feb-23***for login form redirection************/
        $hidden_doc_id = isset($request->hidden_doc_id)?$request->hidden_doc_id:'';
        /**********added on 20-feb-23***for login form redirection**********/



        /**********added on 18-dec-23* (29-feb-24)**for login form redirection*******/
        $hidden_service_id = isset($request->hidden_service_id)?$request->hidden_service_id:'';
        /**********added on 18-dec-23* (29-feb-24)**for login form redirection**********/


        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
        $errors = '';
        // $doctor_id              = $request->doctor_id;
        // $appointment_type_id    = $request->appointment_type_id;
        // $appointment_date       = $request->roster_date;
        // $time_frame             = $request->roster_time_slot;
        $first_name             = $request->first_name;
        $family_name            = $request->family_name;
        $mobile_no              = $request->mobile_no;
        $otp_code               = $request->otp_code;
        $birth_date             = $request->birth_date;
        $country_code           = $request->country_code;
        if(empty($otp_code))
        {
            //error message url
            $errors = 'SMS-Code ist erforderlich.';
            $this->JsonData['msg'] = $errors;
            return response()->json($this->JsonData);
            exit();
        }

        //Commented below login first name validation on 22 sept 22

        /*$splitted_first_name = preg_split("/[\s,\-,\_]+/", $first_name);
        if(count($splitted_first_name) > 1)
        {
            $first_name = $splitted_first_name[0];
        }*/


        $patient_data = $this->PatientsModel
                        // ->whereRaw("MATCH(first_name) AGAINST('".$first_name."')")
                        // ->whereRaw("MATCH(family_name) AGAINST('".$family_name."')")
                        ->where("first_name",$first_name)
                        ->where("family_name",$family_name)
                        ->where('login_otp',trim($otp_code))
                        ->whereDate('birth_date',date('Y-m-d',strtotime($birth_date)))
                        ->whereIn('mobile_no',array(trim($mobile_no),ltrim($request->mobile_no,'0'),'0'.$mobile_no))
                        ->orderby('id','DESC')
                        ->first();

        /*if(empty($patient_data))
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
        else {

        }*/

        if(!empty($patient_data))
        {
            $patient_id     = $patient_data->id;
            $request['patient_id'] = $patient_id;
            try {
                    $reqdata = [
                        'patient_id' =>$patient_id,
                        'otp_code' => $request->otp_code,

                   ];
                //dd($reqdata);
                $requested_data = base64_encode(json_encode($reqdata));
                session(['loginPatientData' =>$requested_data]);

                /***Start****added below condition on 20-feb-23* for doctor in url redirect to booking form with doctor selection in the dropdown*******************/


                 //commented below code on 18-dec-23 (29-feb-24)

                /*if(isset($hidden_doc_id) && !empty($hidden_doc_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/'.$hidden_doc_id);
                }
                else
                {
                  $this->JsonData['url']      = url('/online-appointment/booking');
                }*/


                //if and else if condition changes added on 18-dec-23 (29-feb-24)

                if(isset($hidden_doc_id) && !empty($hidden_doc_id) && isset($hidden_service_id) && !empty($hidden_service_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/'.$hidden_doc_id.'/'.$hidden_service_id);
                }
                else if(isset($hidden_doc_id) && !empty($hidden_doc_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/'.$hidden_doc_id);
                }
                else if(isset($hidden_service_id) && !empty($hidden_service_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/null/'.$hidden_service_id);
                }
                else
                {
                  $this->JsonData['url']      = url('/online-appointment/booking');
                }
                //if and else if condition changes added on 18-dec-23 (29-feb-24)




                /***End*condition on 20-feb-23******************/







                $this->JsonData['data']     = $reqdata;
               // $this->JsonData['url']      = url('/online-appointment/booking');//commented on 20-feb-23
                $this->JsonData['msg']      = '';
                $this->JsonData['status']   = __('front.RESP_SUCCESS');
            }
            catch(\Exception $e) {
                DB::rollback();
                $errors = $e->getMessage();
                $this->JsonData['errors'] = $errors;
            }
        }else{

            $errors = 'Wir konnten leider keinen passenden Eintrag finden. Überprüfen sie bitte das Geburtsdatum und die Schreibweise ihres Namens';

           // $doctor = $this->AdminUserModel->select('first_name','last_name')->where('id',$doctor_id)->first();
            $mail_content['first_name'] = $first_name;
            $mail_content['family_name'] = $family_name;
           // $mail_content['doctor_name'] = $doctor->first_name." ".$doctor->last_name;
            $mail_content['mobile'] = $mobile_no;
            $mail_content['birth_date'] = $birth_date;
            // $mail_content['appoitment_date'] = $appointment_date;
            // $mail_content['time_frame'] = $time_frame;


            $ordination_email = $this->SettingsModel
                        ->where('setting_key','=','ORDINATION_EMAIL_ADDRESS')
                        ->whereStatus(1)
                        ->first();
            $admin_email = $this->SettingsModel
                        ->where('setting_key','=','ADMINISTRATOR_EMAIL')
                        ->whereStatus(1)
                        ->first();
            if($admin_email && $ordination_email)
            {
                $this->_failedLoginMail($ordination_email->setting_value,$admin_email->setting_value,$mail_content);
            }
            $this->JsonData['msg'] = $errors;
        }


        //Added by Shyam 24-03-22
       /* if(!empty($urlEventId) && !empty($urlPatientId))
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
        }*/
        return response()->json($this->JsonData);
    }//
    //Rename above function by roshani at 08/04/2024 and same copy of that function
     //Smart Appoitment 15-Sep-22 added by divya===============================
     public function postPatient(Request $request)
    {
       // dump($request->all());

          // ############# Roshani Added this code on 116-mar-24 #################
            //Custom error
            if (isset($request->hid_pass_check) && !empty($request->hid_pass_check) && empty($request->password)) {

                if($request->hid_pass_check == 'show_password_error')
                {
                    $this->JsonData['status'] = "custom_error_password";
                    $this->JsonData['msg']    = __('front.ERR_PASSWORD_REQUIRED');
                    return response()->json($this->JsonData);
                }
            }
        // ############# Roshani Added this code on 16-mar-24 #################
        $password_empty = 0;
        $urlEventId = $urlPatientId = '';
        //dd($request->all());

         /**********added on 20-feb-23***for login form redirection************/
        $hidden_doc_id = isset($request->hidden_doc_id)?$request->hidden_doc_id:'';
        /**********added on 20-feb-23***for login form redirection**********/



        /**********added on 18-dec-23* (29-feb-24)**for login form redirection*******/
        $hidden_service_id = isset($request->hidden_service_id)?$request->hidden_service_id:'';
        /**********added on 18-dec-23* (29-feb-24)**for login form redirection**********/

        //Roshani start added the mobile number function for issue no. 134  on 05-07-2024
        // if(isset($request->mobile_no) && !empty($request->mobile_no))
        // {
        //     $formatted_mobile_no = self::formatPhoneNumber($request->mobile_no);
        // }
        //Roshani stop added the mobile number function for issue no. 134 on 05-07-2024


        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
        $errors = '';
        //Roshani start added the mobile number function for issue no. 134  on 05-07-2024
        if (isset($request->mobile_no) && !empty($request->mobile_no)) {
            $formatted_mobile_no = self::formatPhoneNumber($request->mobile_no);
        }
        //Roshani stop added the mobile number function for issue no. 134 on 05-07-2024


        $first_name    = $request->first_name;
        $family_name   = $request->family_name;
        //Roshani start added the mobile number function for issue no. 134  on 05-07-2024
        // $mobile_no      = isset($request->mobile_no) ? $request->mobile_no: $request->mobile_no_hidden ;
        $mobile_no = isset($request->mobile_no) ? $formatted_mobile_no : self::formatPhoneNumber($request->mobile_no_hidden);
        $otp_code       = $request->otp_code;
        $birth_date    = isset($request->birth_date) ? $request->birth_date: $request->birth_date_hidden;
        $country_code  = $request->country_code;
        $format        = isset($request->format) ? $request->format: $request->format_hidden;




        $showEmail=0; $isLogin=0;

        if(empty($request->password))
        {
            $patient_data = $this->PatientsModel
                ->whereDate('birth_date',date('Y-m-d',strtotime($birth_date)))
                ->whereIn('mobile_no',array(trim($mobile_no),ltrim($request->mobile_no,'0'),'0'.$mobile_no))
                ->orderby('id','DESC')
                ->first();
                // if ($patient_data) {
                //     if ($patient_data->country_code != $format)
                //     {
                //         $errors = 'Ungültiger Ländercode.';
                //         $this->JsonData['msg'] = $errors;
                //         return response()->json($this->JsonData);
                //         exit();
                //     }
                // }
                if ($patient_data) {

                    // 🧩 Normalize both formats: treat +43 and 0043 as equal
                    $requestFormat = trim($format);
                    $dbFormat = trim($patient_data->country_code);

                    // Convert both to comparable variants
                    $normalizedRequest1 = preg_replace('/^\+/', '00', $requestFormat); // +43 → 0043
                    $normalizedRequest2 = preg_replace('/^00/', '+', $requestFormat); // 0043 → +43

                    // ✅ Compare with normalization
                    if (!in_array($dbFormat, [$requestFormat, $normalizedRequest1, $normalizedRequest2])) {
                        // $errors = 'Ungültiger Ländercode.'; //commented on 5-nov-25
                         $errors = 'Falsche Telefonnummer – bitte überprüfen Sie auch die Landesvorwahl.'; //Added on 5-nov-25
                        $this->JsonData['msg'] = $errors;
                        return response()->json($this->JsonData);
                        exit();
                    }
                }
        }
        else
        {
            $patient_data = $this->PatientsModel
                ->whereDate('birth_date',date('Y-m-d',strtotime($birth_date)))
                ->whereIn('mobile_no',array(trim($mobile_no),ltrim($request->mobile_no,'0'),'0'.$mobile_no))
                ->orderby('id','DESC')
                ->first();
                if (isset($patient_data->password) && Hash::check($request->password, $patient_data->password))
                {
                  $patient_data =$patient_data;

                }else
                {
                  $patient_data = Null;
                  $password_empty = 1;
                }
        }
        // dd($patient_data);


        /**********added on 27-may-24********************************/
        $dbEmailExists = 0;
        /***********added on 27-may-24*******************************/


        //dump($patient_data);

        if(!empty($patient_data))
        {
            $isLogin=1;
            //Roshnai added this code for check inactive patient
            if (!$patient_data->status)
            {
                $this->JsonData['msg'] = __('front.FAIL_FORGOT_PASSWORD_DISABLED');
                return response()->json($this->JsonData);exit;
            }
            //Roshnai added this code for check inactive patient
            //Roshani added this chnages for check minimum age validation
            $minimumAgeSetting = $this->SettingsModel
                        ->where('setting_key','=','MINIMUM_AGE')
                        ->whereStatus(1)
                        ->first();

            if(isset($minimumAgeSetting))
            {
                $birthDate = Carbon::parse($birth_date);
                $minAgeYears = (int)$minimumAgeSetting['setting_value'];
                $today = Carbon::now();

                $minAgeDate = $birthDate->copy()->addYears($minAgeYears);
                if ($today->lt($minAgeDate)) 
                {
                    $errors = __('front.ERR_MIMIMUM_AGE');
                    $this->JsonData['msg'] = $errors;
                    return response()->json($this->JsonData);
                    exit();
                }
            }
            //Roshani added this chnages for check minimum age validation


            /**********added on 27-may-24********************************/
            if(isset($patient_data->email) && !empty($patient_data->email))
            {
                $dbEmailExists = 1;
            }

            /***********added on 27-may-24*******************************/

            $patient_id     = $patient_data->id;
            $request['patient_id'] = $patient_id;
            // $patient_email    = $patient_data->email;//commented on 27-may-24
            $patient_email    = isset($patient_data->email)?$patient_data->email:''; //added on 27-may-24

            try {

                //start below code is added on 14-may-24
                if(isset($request->shown_otp_field) && $request->shown_otp_field==1)
                {
                    if(empty($otp_code))
                    {
                        //error message url
                        $errors = 'OTP-Code ist erforderlich.';
                        $this->JsonData['msg'] = $errors;
                        return response()->json($this->JsonData);
                        exit();
                    }

                    //commented below code on 27-may-24
                    /*
                    $checkPatientOtpData = $this->PatientsOtpModel
                        ->where('login_otp',trim($otp_code))
                        ->whereDate('birth_date',date('Y-m-d',strtotime($birth_date)))
                        ->where('mobile_no',$mobile_no)
                        ->where('email',$patient_email)
                        ->first();
                    if(empty($checkPatientOtpData))
                    {
                        $this->JsonData['data']     = '';
                        $this->JsonData['status']   = __('front.RESP_ERROR');
                        $this->JsonData['msg']      = __('front.OTP_NOT_MATCHED');
                        $this->JsonData['isLogin']      = $isLogin;
                        return response()->json($this->JsonData);
                    }//if checkPatientOtpData
                    */

                    //start Added below code on 27-may-24
                    if($request->dbEmailExists==1){
                         $checkPatientOtpData = $this->PatientsOtpModel
                        ->where('login_otp',trim($otp_code))
                        ->whereDate('birth_date',date('Y-m-d',strtotime($birth_date)))
                        ->where('mobile_no',$mobile_no)
                        ->where('email',$patient_email)
                        ->first();
                    }//if dbEmailExists is 1
                    else
                    {
                         $checkPatientOtpData = $this->PatientsOtpModel
                        ->where('login_otp',trim($otp_code))
                        ->whereDate('birth_date',date('Y-m-d',strtotime($birth_date)))
                        ->where('mobile_no',$mobile_no)
                        ->first();
                    }
                    if(empty($checkPatientOtpData))
                    {
                        $this->JsonData['data']     = '';
                        $this->JsonData['status']   = __('front.RESP_ERROR');
                        $this->JsonData['msg']      = __('front.OTP_NOT_MATCHED');
                        $this->JsonData['isLogin']      = $isLogin;
                        return response()->json($this->JsonData);
                    }//if checkPatientOtpData
                    //end Added below code on 27-may-24


                }//if isset
                //end below code is added on 14-may-24



                $reqdata = [
                    'patient_id' =>$patient_id,
                    'otp_code' => $request->otp_code,
                    'patient_email' => $patient_email,
               ];
                $requested_data = base64_encode(json_encode($reqdata));
                session(['loginPatientData' =>$requested_data]);

                if(isset($hidden_doc_id) && !empty($hidden_doc_id) && isset($hidden_service_id) && !empty($hidden_service_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/'.$hidden_doc_id.'/'.$hidden_service_id);
                }
                else if(isset($hidden_doc_id) && !empty($hidden_doc_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/'.$hidden_doc_id);
                }
                else if(isset($hidden_service_id) && !empty($hidden_service_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/null/'.$hidden_service_id);
                }
                else
                {
                  $this->JsonData['url']      = url('/online-appointment/booking');
                }

                $this->JsonData['data']     = $reqdata;
                $this->JsonData['msg']      = '';
                $this->JsonData['password_show']      = isset($request->password) && !empty($request->password) ? 'no' : 'yes';


                $this->JsonData['status']   = __('front.RESP_SUCCESS');

                $this->JsonData['isLogin'] = $isLogin;
                $this->JsonData['patient_email'] = isset($patient_email) && !empty($patient_email) ? $patient_email : '';
                $this->JsonData['dbEmailExists'] = $dbEmailExists; //added on 27-may-24


            }
            catch(\Exception $e) {
                DB::rollback();
                $errors = $e->getMessage();
                $this->JsonData['errors'] = $errors;
            }
        }else{


            /*********CR#191***added on 24-oct-24**************************/
            $minimumAgeSetting = $this->SettingsModel
                        ->where('setting_key','=','MINIMUM_AGE')
                        ->whereStatus(1)
                        ->first();

             if(isset($minimumAgeSetting))
             {
                // $age   = (date('Y') - date('Y',strtotime($request->birth_date)));//commented on 29-may-25 for #343
 

                // commented on 2-june-25
                //if($age<=$minimumAgeSetting['setting_value'])
                // {

                //added on 2-june-25 for #343
                // $birthDate = Carbon::parse($request->birth_date);
                $birthDate = Carbon::parse($birth_date);
                $minAgeYears = (int)$minimumAgeSetting['setting_value'];
                $today = Carbon::now();

                $minAgeDate = $birthDate->copy()->addYears($minAgeYears);
                if ($today->lt($minAgeDate)) 
                {
                    $errors = __('front.ERR_MIMIMUM_AGE');
                    $this->JsonData['msg'] = $errors;
                    return response()->json($this->JsonData);
                    exit();
                }
             }
            /***********CR#191****added on 24-oct-24****************************/



            $isLogin=0;

            if($password_empty == 0)
            {
                //dump('in pw empty');

                $sessionData = [
                'mobile_no' => $mobile_no,
                'birth_date' => $birth_date,
                'format' => $format,
                'email'=>isset($request->email) ? $request->email:''  //added on 16-may-24 to add email in session
                ];


                //start below code is added on 14-may-24
                if(isset($request->shown_otp_field) && $request->shown_otp_field==1)
                {
                    if(empty($otp_code))
                    {
                        //error message url
                        $errors = 'SMS-Code ist erforderlich.';
                        $this->JsonData['msg'] = $errors;
                        return response()->json($this->JsonData);
                        exit();
                    }
                    $checkPatientOtpData = $this->PatientsOtpModel
                        ->where('login_otp',trim($request->otp_code))
                        ->whereDate('birth_date',date('Y-m-d',strtotime($birth_date)))
                        ->where('mobile_no',$mobile_no)
                        ->where('email',$request->email)
                        ->first();
                    if(empty($checkPatientOtpData))
                    {
                        $this->JsonData['data']     = '';
                        $this->JsonData['status']   = __('front.RESP_ERROR');
                        $this->JsonData['msg']      = __('front.OTP_NOT_MATCHED');
                        $this->JsonData['isLogin']      = $isLogin;
                        return response()->json($this->JsonData);
                    }//if checkPatientOtpData

                }//if isset
                //end below code is added on 14-may-24


                session($sessionData);
                // $this->JsonData['has_email']      = isset($request->email) && !empty($request->email) ? '1' : '0';

                /***********added below code on 16-may-24**********************/

                if(isset($hidden_doc_id) && !empty($hidden_doc_id) && isset($hidden_service_id) && !empty($hidden_service_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/'.$hidden_doc_id.'/'.$hidden_service_id);
                }
                else if(isset($hidden_doc_id) && !empty($hidden_doc_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/'.$hidden_doc_id);
                }
                else if(isset($hidden_service_id) && !empty($hidden_service_id))
                {
                  $this->JsonData['url']      = url('/online-appointment/booking/null/'.$hidden_service_id);
                }
                else
                {
                  $this->JsonData['url']      = url('/online-appointment/booking');
                }

                /**********added below code on 16-may-24**********************/


                $this->JsonData['data']     = '';
                $this->JsonData['status']   = __('front.RESP_SUCCESS');
                $this->JsonData['msg']      = '';
               // $this->JsonData['url']      = url('/online-appointment/booking');
                $this->JsonData['isLogin']      = $isLogin;
                $this->JsonData['dbEmailExists'] = $dbEmailExists; //added on 27-may-24


            }
            else
            {
                $isLogin=1;
                $this->JsonData['data']     = '';
                $this->JsonData['status']   = __('front.RESP_ERROR');
                $this->JsonData['msg']      = __('front.PASSWORD_NOT_MATCHED');
                $this->JsonData['isLogin']      = $isLogin;
                // $this->JsonData['url']      = url('/online-appointments');
                $this->JsonData['dbEmailExists'] = $dbEmailExists; //added on 27-may-24

            }


            // $errors = 'Wir konnten leider keinen passenden Eintrag finden. Überprüfen sie bitte das Geburtsdatum und die Schreibweise ihres Namens';

            // $mail_content['first_name'] = $first_name;
            // $mail_content['family_name'] = $family_name;
            // $mail_content['mobile'] = $mobile_no;
            // $mail_content['birth_date'] = $birth_date;

            // $ordination_email = $this->SettingsModel
            //             ->where('setting_key','=','ORDINATION_EMAIL_ADDRESS')
            //             ->whereStatus(1)
            //             ->first();
            // $admin_email = $this->SettingsModel
            //             ->where('setting_key','=','ADMINISTRATOR_EMAIL')
            //             ->whereStatus(1)
            //             ->first();
            // if($admin_email && $ordination_email)
            // {
            //     $this->_failedLoginMail($ordination_email->setting_value,$admin_email->setting_value,$mail_content);
            // }
            // $this->JsonData['msg'] = $errors;
        }
        return response()->json($this->JsonData);
    }//

     //Added new function on 25 aug 22
    public function bookingAppointment($request)
    {
        Log::info("in bookAppointment function");
        Log::info($request->all());

             $session = json_decode(base64_decode(session('loginPatientData')));
             if(!empty($session)){
                $patient_id = $session->patient_id;
                $otp_code = $session->otp_code;

             // get patient data
             $patient_data = $this->PatientsModel->where('id',$patient_id)->first();
             if(isset($patient_data) && !empty($patient_data))
             {

                $first_name             = $patient_data->first_name;
                $family_name            = $patient_data->family_name;
                $mobile_no              = $patient_data->mobile_no;
                $otp_code               = $patient_data->otp_code;
                $birth_date             = $patient_data->birth_date;
                $country_code           = $patient_data->country_code;
                $request->patient_id = $patient_id;
                $request->first_name = $first_name;
                $request->family_name = $family_name;
                $request->mobile_no = $mobile_no;
                $request->otp_code = $otp_code;
                $request->birth_date = $birth_date;
                $request->country_code = $country_code;
             }//if patient data
            // $ORDINATION PATIENT CHECK
                // $ordination_patient = self::addPatientCountryOnOrdination($patient_id);
            if(!empty(Config('ordination_id')) && empty($patient_data->country))
            {
                $ordination_patient = self::addPatientCountryOnOrdination($patient_id);
            }

            $urlEventId = $urlPatientId = '';
            $this->JsonData['status'] = __('front.RESP_ERROR');
            $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
            $errors = '';
            $doctor_id              = $request->doctor_id;
            $appointment_type_id    = $request->appointment_type_id;
            $appointment_date       = $request->roster_date;
            $time_frame             = $request->roster_time_slot;
            $roster_time_slot_hd_id = $request->roster_time_slot_hd_id;
            $dr_type                = $request->dr_type;

            try{

                $check_time_frame = $this->RosterModel
                            ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                            ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                            ->where('roster.doctor_id',$doctor_id)
                            ->where('roster_has_dates.is_excluded','=',0)
                            ->whereDate('roster_has_dates.date',Date('Y_m-d',strtotime($appointment_date)))
                            ->where('roster_has_weeks_has_time_frames.time_frame','=',$time_frame)
                            ->get(['roster_has_weeks_has_time_frames.time_frame']);


                 //dd($check_time_frame);
                if(!empty($check_time_frame) && sizeof($check_time_frame)>0)
                {
                    //now time slotes are available,but the appointment is booked for it then throw error message
                    $check_app_date = date("Y-m-d H:i:s",strtotime($appointment_date." ".$time_frame));
                    $check_app_end_date  = self::_getEndDate($check_app_date,$appointment_type_id);
                    $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id);
                    $duration = $appointmentType->duration;
                    if($duration == 10)
                    {
                        $check_doctor_booked_appointment = $this->BaseModel
                        ->where('doctor_id',$doctor_id)
                        ->whereStatus(1)
                        ->where('appointment.start_date','<=',$check_app_date)
                        ->where('appointment.end_date', '>=', $check_app_end_date)
                        ->get(['id']);
                    }
                    else
                    {
                        $check_doctor_booked_appointment = $this->BaseModel
                        ->where('doctor_id',$doctor_id)
                        ->whereStatus(1)
                        ->where('appointment.start_date','>=',$check_app_date)
                        ->where('appointment.end_date', '<=', $check_app_end_date)
                        ->get(['id']);
                        // dump(sizeof($check_doctor_booked_appointment));
                    }

                    if(!empty($check_doctor_booked_appointment) && sizeof($check_doctor_booked_appointment)>0)
                    {

                        $errors = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
                        $this->JsonData['msg'] = $errors;
                        return response()->json($this->JsonData);
                        exit();
                    }
                }
                else {
                    $errors = 'Terminzeitfenster können nicht gebucht werden.';//Appointment time slots is not available for booking
                    $this->JsonData['msg'] = $errors;
                    return response()->json($this->JsonData);
                    exit();
                }

                 if(empty($errors) && $errors=='')
                 {
                    //Start Booking an Appointement
                    DB::beginTransaction();
                    $collection     = new $this->BaseModel;
                    $request['start_date'] = date("Y-m-d H:i",strtotime($appointment_date." ".$time_frame));
                    $request['end_date']  = self::_getEndDate($request['start_date'],$appointment_type_id);
                    $collection     = self::_storeAppointment($collection,$request);
                    //===============================================================

                    $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                        ->where('id',$request->roster_time_slot_hd_id)
                                        ->update(['time_frame_flag'=>'2',
                                                  'time_frame_flag_date'=>Date('Y-m-d H:i:s'),
                                                  'comment'=>'AppointmentWebController '.$request->dr_type.' booking function app Date : '.Date('Y_m-d',strtotime($appointment_date)).'current Date : '.Date('Y-m-d H:i:s').' .patient_name: '.$patient_id
                                                ]);
                    Log::info('has created appointment by AppointmentWebController');
                    $debug_arr['data'] = 'has created appointment by AppointmentWebController';
                    $res_name = "AppointmentWebController_store";
                    self::debugModeappBookFun($debug_arr,$res_name);
                    log::info($collection);
                    self::_deactivateReminder($collection);
                    $all_transactions = [];
                    $notify_data = [];
                    $notes = '';
                    if ($collection)
                    {
                        $all_transactions[] = 1;
                        $patient_doc_data = [];

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
                        $booking_month = __('admin.'.date('F',strtotime($request->start_date)),[],'de');
                        $appointmentType = $collection->assignedAppointmentType->name;
                        $appointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                        //commented on 6-nov-23
                        // $patientText = $collection->assignedPatient->salutation ?? "";
                        // $patientText .= " ".$collection->assignedPatient->family_name;

                         //changed on 6-nov-23
                        // $patientText = $collection->assignedPatient->salutation ? " ".$collection->assignedPatient->salutation.'.': ""; //added dot after salutation on 14-dec-23 //commented on 12-dec-25


                        $patientText = $collection->assignedPatient->salutation ? $collection->assignedPatient->salutation.'.': ""; //added dot after salutation on 14-dec-23 //changed on 12-dec-25

                        // $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name; //commented on 12-dec-25

                        //changed on 12-dec-25
                        if(isset($collection->assignedPatient->salutation)){
                            $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;
                        }else{
                            $patientText .= $collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;
                        }
                        



                        $doctorSurname = $collection->assignedDoctor->last_name;

                         //Appoinment Push Notification
                        //commented on 6-nov-23
                        // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;





                        $mailAppointmentTime = date('d',strtotime($request->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($request->start_date))." Uhr.";
                        // $mailAppointmentTime = date('d.F',strtotime($request->start_date)).", um ".date('H:i',strtotime($request->start_date))." Uhr.";
                        $mail_content = 'Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$mailAppointmentTime;
                        $notify_times = self::_getNotifyTime($request['start_date']);


                        //commented below code on 13-feb-24 for notification from settings section

                       /* $content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;//changed on 6-nov-23

                         foreach ($notify_times as $notify_time)
                        {
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
                        else {
                            $all_transactions[] = 0;
                        }*/


                         /*******added code on 13-feb-24***for notification from setting section*******/

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


                    /***********end code**of notification setting**13-feb-24*************************/



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

                        //Commented code for testing book appointment on 30-aug-22

                        $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventStore(request());
                        // dump($postCalDetails);
                         if(!empty($postCalDetails) && $postCalDetails->original['status'] == 'success')
                        {
                            // dump("yes");
                            $all_transactions[] = 1;
                            $eventId = $postCalDetails->original['data']->id;
                            $collection->google_event_id = $eventId;
                            $collection->event_id = $eventId;

                            //$collection->notes          = $notes;
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
                        }
                        else {
                            $all_transactions[] = 0;
                            $errors = $postCalDetails->original['msg'];
                        }//else

                        //Log::info('url Event Id :');
                        //Log::info($urlEventId);

                        if(!in_array(0,$all_transactions))
                        {
                            DB::commit();
                            $status  = true;
                            $message = __('api.APPOINTMENT_BOOKED_SUCCESS');

                            // if(!empty($country_code)){
                            //     //$country_code = str_replace("00", "",$country_code);
                            //     $country_code = str_replace("+", "",$country_code); //Added on 13 sept to send sms
                            // }elseif(empty($country_code)){
                            //     $country_code = '43'; //Austria country code
                            // }

                            // $phone   = $country_code."".str_replace("-", "",$mobile_no);
                            // $message .= "test message from puregyn api...please ignore.";
                            //Mail Send added by swati 15-Jun-23===============
                            $channels = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();
                            $patientData = $this->PatientsModel->where('id', $collection->patient_id)->first();

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
                            if(!empty($urlEventId) && !empty($collection->patient_id))
                            {
                                //This code is hide for seniding email of cancel appointment
                                //Send Email...
                                // if(!empty($patientData->email) && $channels->choice_of_channels == 'email')
                                // {
                                //      self::_sendMailAppointment($patientData->id,$urlEventId);
                                // }
                                // else {
                                //     if(!empty($phone_no))
                                //         self::_sendSmsAppointment($phone_no,$urlEventId);
                                //     elseif(!empty($patientData->email))
                                //         self::_sendMailAppointment($patientData->id,$urlEventId);
                                // }


                                 //Code added by roshani on 16-04-2024
                                 if(!empty($collection))
                                    {
                                        $firstName = '';
                                        $lastName = '';

                                        $patient_id     = $collection->patient_id;
                                        $appointment_id = $collection->id;
                                        $getPatientEmail = $this->PatientsModel
                                                            ->where('id',$patient_id)
                                                            ->first();
                                        $appointmentDetail = $this->BaseModel
                                                        ->where('id', $collection->id)
                                                        ->first();
                                        $encodePatientId = base64_encode(base64_encode($appointment_id));
                                        $appointmentConfirmUrl = url('/online-appointment/confirm-web-appointment/'.$encodePatientId);
                                        $timestamp = strtotime($appointmentDetail->start_date);

                                        setlocale(LC_TIME, 'de_AT.utf8');
                                        $formattedDate = strftime("%e. %B, um %H:%M Uhr", $timestamp);
                                        $cancelUrl = url('/cancelAppointment').'/'.$urlEventId;
                                        $firstName = isset($getPatientEmail->first_name) && !empty($getPatientEmail->first_name) ? $getPatientEmail->first_name : '';
                                        $lastName = isset($getPatientEmail->family_name) && !empty($getPatientEmail->family_name) ? $getPatientEmail->family_name : '';
                                        $patientAndAppDetail = [
                                            'Confirm_url' =>  $appointmentConfirmUrl,
                                            'patient_name' => $firstName .' '. $lastName,
                                            'datetime' => $formattedDate,
                                            'Cancel_url' =>$cancelUrl,
                                        ];

                                        //start added below line on 8-may-24
                                        $ordinationName=(!empty(config('ordination_name'))) ? ucfirst(config('ordination_name')):'Puremed';
                                        //end added below line on 8-may-24

                                        // $result = Mail::to($getPatientEmail->email)->send(new ConfirmAppointmentWeb($patientAndAppDetail,'web'));

                                        //added ordination name on 8-may-24

                                        //commented on 27-may-24
                                        // $result = Mail::to($getPatientEmail->email)->send(new ConfirmAppointmentWeb($patientAndAppDetail,'web',$ordinationName));

                                        if(isset($getPatientEmail->email) && !empty($getPatientEmail->email)){
                                        $result = Mail::to($getPatientEmail->email)->send(new ConfirmAppointmentWeb($patientAndAppDetail,'web',$ordinationName));
                                        }//if condition added on 27-may-24



                                    }

                                //code added by roshani

                            }
                            else{
                                if(!empty($patientData->email) && $channels->choice_of_channels == 'email')
                                {
                                    $this->_sendMails($patientName,$patientEmail,$mail_content);
                                }
                                else {
                                    if(!empty($phone_no)) $this->_sendSms($phone,$content);
                                    elseif(!empty($patientData->email))
                                        $this->_sendMails($patientName,$patientEmail,$mail_content);
                                }
                            }
                            //===============================================

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
                            session(['loginPatientData' =>'']);

                            // Set Check List Data
                            $chk_data = base64_encode(json_encode($data));
                            session(['chk_data' =>$chk_data]);

                            Log::info("before insert services..");

                            /**********start code for services insert on 13-oct-25************/

                            $getAppointment = DB::table('appointment as a')
                            ->join('appointment_types', 'a.appointment_type_id', '=', 'appointment_types.id')
                            ->join('examinations', 'appointment_types.name', '=', 'examinations.name')
                            ->leftJoin('appointment_has_examinations as ahx', function($join) {
                                $join->on('ahx.appointment_id', '=', 'a.id')
                                     ->on('ahx.examination_id', '=', 'examinations.id')
                                     ->on('ahx.patient_id', '=', 'a.patient_id');
                            })
                            ->select(
                                'a.id',
                                'a.patient_id',
                                'a.appointment_type_id',
                                'appointment_types.id as appointment_type_id',
                                'appointment_types.name as appointment_type_name',
                                'examinations.id as examination_id',
                                'examinations.name as examination_name'
                            )
                            ->whereNull('examinations.deleted_at')
                            ->whereNull('ahx.id') 
                            ->where('a.id',$collection->id)
                            ->where('a.patient_id',$collection->patient_id)
                            ->orderBy('a.id', 'desc')
                            ->first();    


                            if(isset($getAppointment) && !empty($getAppointment))
                            {
                                $appointment_id = $collection->id;
                                $appointment_type_id = $getAppointment->appointment_type_id; 
                                $examination_id = $getAppointment->examination_id; 
                                $patient_id = $collection->patient_id; 

                                $appointmentHasExaminations = DB::table('appointment_has_examinations')
                                ->where('appointment_id', $collection->id)
                                ->where('examination_id', $examination_id)
                                ->where('patient_id', $collection->patient_id)
                                ->first();
                                if(isset($appointmentHasExaminations) && !empty($appointmentHasExaminations)) 
                                {     
                                        Log::info("innnn already exists examinationid=>".$examination_id.'==>appointment_id=>'.$appointment_id.'==>patient_id=>'.$patient_id);
                                }
                                else
                                {
                                    Log::info("innnn not exists examinationid=>".$examination_id.'==>appointment_id=>'.$appointment_id.'==>patient_id=>'.$patient_id);

                                    $collections1 = DB::table('appoinment_type_has_examinations')
                                    ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')    
                                    ->where('appoinment_type_has_examinations.appoinment_id', $getAppointment->appointment_type_id)
                                    ->whereNull('appoinment_type_has_examinations.deleted_at') // ignore deleted rows
                                    ->get([
                                        'examinations.id',
                                        'examinations.name',
                                        'examinations.url',
                                        'examinations.description',
                                        'examinations.status',
                                        'examinations.created_at',
                                        'examinations.show_as_recommended'
                                    ]);

                                    Log::info($collections1);
                                    Log::info("collections1 data:", $collections1->toArray());

                                    $today_date = date("Y-m-d");

                                    $collections1 = $collections1->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) 
                                    {

                                        $app_type_name = DB::table('appointment_types')
                                            ->where('id', $appointment_type_id)
                                            ->first();


                                        if ($item->name == $app_type_name->name) {

                                            return $item;
                                        }
                                        else
                                        {

                                            $collectionsFilter = DB::table('patient_has_service_reminder')
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

                                            if (isset($collectionsFilter) && !empty($collectionsFilter) && $collectionsFilter->count() > 0) 
                                            {

                                                $collectionsFilter = $collectionsFilter->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) {

                                              
                                                $app_type_name = DB::table('appointment_types')
                                                    ->where('id', $appointment_type_id)
                                                    ->first();

                                                $age_service =  DB::table('preferred_channels_for_reminders_setting')
                                                    ->where('service_id', $item->id)
                                                    ->where('activated_reminder', 'age')
                                                    ->first();
                                                //Added by swati 2-nov-22=========================
                                                $general_reminder_service =  DB::table('preferred_channels_for_reminders_setting')
                                                    ->where('service_id', $item->id)
                                                    ->where('activated_reminder', 'general')
                                                    ->first();
                                                //============================                  
                                                if (!empty($age_service) && $item->name != $app_type_name->name) {
                                                    //$getPatientAge = $this->PatientsModel->find($patient_id);
                                                    $getPatientAge = DB::table('patients')
                                                                 ->find($patient_id);


                                                    if (!empty($getPatientAge)) {
                                                        $patient_age = $getPatientAge->age;
                                                        if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                                            //commented on 26-dec-23
                                                            return $item;
                                                        } //if
                                                    }
                                                } else if (!empty($general_reminder_service)) {
                                                    $checkGenaralService =   DB::table('patient_has_service_reminder')
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
                                            else 
                                            {

                                                $hasReminderSet =  DB::table('patient_has_service_reminder')
                                                    ->where('patient_has_service_reminder.patient_id', $patient_id)
                                                    ->where('patient_has_service_reminder.service_id', $item->id)
                                                    ->first();
                                                if (isset($hasReminderSet) && !empty($hasReminderSet)) {
                                                } //if hasReminderSet
                                                else {
                                                    return $item;
                                                }

                                            } //else   
                                        } //else not defaultservice name

                                    });

                                    Log::info("2nd ...collections1.again..");
                                    Log::info($collections1);

                                    $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
                                    Log::info("Extracted exams_ids:", $exams_ids);

                                    //cycle>=2 and app id 0 or not condition added on 23-jan-26

                                    $collections2 = DB::table('patient_has_service_reminder')
                                        ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status,patient_has_service_reminder.id as reminderid'))
                                        ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
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
                                        ->whereRaw("examinations.show_as_reminder='1'")
                                        ->whereNotIn('examinations.id', $exams_ids)
                                        ->groupBy('patient_has_service_reminder.service_id')
                                        ->get();

                                    Log::info("collections2 data:", $collections2->toArray());              
                                    Log::info($collections2);
                                    //dump($collections2);



                                    $collections2 = $collections2->filter(function ($item) use ($patient_id, $today_date) {
                                            $age_service =  DB::table('preferred_channels_for_reminders_setting')
                                                            ->where('service_id', $item->id)
                                                            ->where('activated_reminder', 'age')
                                                            ->first();
                                            if (!empty($age_service)) {
                                                //log::info($patient_id);


                                                //$getPatientAge = $this->PatientsModel->find($patient_id);
                                                $getPatientAge = DB::table('patients')
                                                                         ->find($patient_id);
                                                                     
                                                if (!empty($getPatientAge)) {

                                                    Log::info("in getPatientAge ..");

                                                    $patient_age = $getPatientAge->age;

                                                    Log::info($patient_age);      


                                                    if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                                        if ($item->reminder_status == 'executed') {
                                                            $checkServiceReminders =  DB::table('patient_has_service_reminder')
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
                                           
                                            $general_reminder_service =  DB::table('preferred_channels_for_reminders_setting')
                                                ->where('service_id', $item->id)
                                                ->where('activated_reminder', 'general')
                                                ->first();

                                            if (!empty($general_reminder_service)) {

                                                $today_date = date("Y-m-d");


                                                if($item->reminder_status == 'executed')
                                                {     
                                                    $checkServiceReminders =  DB::table('patient_has_service_reminder')
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

                                            }
                                            $checkup_reminder_service =  DB::table('preferred_channels_for_reminders_setting')
                                                ->where('service_id', $item->id)
                                                ->where('activated_reminder', 'checkup')
                                                ->first();

                                            if (!empty($checkup_reminder_service)) {

                                                $today_date = date("Y-m-d");
                                                
                                                if($item->reminder_status == 'executed')
                                                {
                                                    $checkServiceReminders =  DB::table('patient_has_service_reminder')
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

                                            }
                                            //================================================
                                    });
                                      
                                    Log::info("collections2 .again..");
                                    Log::info($collections2);

                                    $getRecord = $collections1->merge($collections2);

                                    Log::info("getRecord.");
                                    Log::info($getRecord);

                                    if (!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id)) 
                                    {

                                        // Fetch appointment type once
                                        $appTypeNameDefault = DB::table('appointment_types')
                                            ->where('id', $appointment_type_id)
                                            ->first();

                                        // Fetch non-examination IDs for this appointment type
                                        $getAppointmentNonServciesIds = DB::table('appoinment_type_has_non_examinations')
                                            ->where('appointment_type_id', $appointment_type_id)
                                            ->pluck('examination_id'); // collection of IDs

                                        $getRecord = $getRecord->map(function ($item) use ($appTypeNameDefault, $getAppointmentNonServciesIds) {

                                            // Exclude non-examination records
                                            if ($getAppointmentNonServciesIds->contains($item->id)) {
                                                return null;
                                            }

                                            // When description is blank
                                            if (empty($item->description)) {
                                                return $item;
                                            }

                                            // When name matches appointment type
                                            if (!empty($appTypeNameDefault) && $item->name == $appTypeNameDefault->name) {
                                                return $item;
                                            }

                                            return null; // exclude everything else
                                        })
                                        ->filter() // remove nulls
                                        ->values(); // reindex collection
                                    }

                                    Log::info("getRecord.again");
                                    Log::info($getRecord);

                                    $final = $getRecord->values();

                                    Log::info("final services ...");
                                    Log::info($final);

                                    //Added on 7-oct-25
                                    $insertedServiceIds = [];

                                    foreach ($final as $item) {

                                        Log::info("final services .item..");
                                        Log::info($item->id);

                                        $exists = DB::table('appointment_has_examinations')
                                            ->where('appointment_id', $collection->id)
                                            ->where('patient_id', $collection->patient_id)
                                            ->where('examination_id', $item->id)
                                            ->exists();

                                        if (!$exists) {
                                            DB::table('appointment_has_examinations')->insert([
                                                'appointment_id'  => $collection->id,
                                                'patient_id'      => $collection->patient_id,
                                                'examination_id'  => $item->id,
                                                'dismissal_flag'  => 0,
                                                'create_from'     => null,
                                                'created_at'      => now(),
                                                'updated_at'      => now(),
                                            ]);

                                            // collect only the inserted IDs
                                            $insertedServiceIds[] = $item->id;
                                        }
                                    }

                                    Log::info($insertedServiceIds); 

                                    // Only pass inserted IDs to deactivate function
                                    if (!empty($insertedServiceIds)) {

                                        $getAppointmentRec = DB::table('appointment')
                                            ->where('id', $collection->id)
                                            ->first();

                                        $this->deactivateReminderNew($getAppointmentRec, $insertedServiceIds);
                                    } 
                                    //end
                                    



                                }//else insert

                            }//if getAppointment



                            /*******end***code of service 13-oct-25*******************************/


                            $this->JsonData['data']   = $data;
                            $this->JsonData['url']    = url('/online-appointment/get-check-list');
                            $this->JsonData['msg']    = $message;
                            // $this->JsonData['msg']    = ''; //commented on 30-dec-25
                            $this->JsonData['msg']    = __('front.APPOINTMENT_DATE_BOOKED_SUCCESS');//changed on 30-dec-25
                        
                            $this->JsonData['status'] = __('front.RESP_SUCCESS');

                        }//if not in array

                    }//if collection
                    else {
                        $all_transactions[] = 0;
                    }//else
                 }//if empty errors


            } catch(\Exception $e){
                DB::rollback();
                $errors = $e->getMessage();
                $this->JsonData['errors'] = $errors;

            }//catch


            //Commenting below code for local working functionality 25aug22
            // if(!empty($urlEventId) && !empty($urlPatientId))
            // {
            //      Log::info('In url event id');

            //     $channels = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();
            //     $patientData = $this->PatientsModel->where('id', $urlPatientId)->first();
            //     //Send Email...
            //     if(!empty($patientData->email) && $channels->choice_of_channels == 'email')
            //     {
            //          //Log::info('In if part');
            //          //Log::info($urlEventId);
            //         self::_sendMailAppointment($patientData->id,$urlEventId);
            //     }
            //     else {
            //         //Send SMS...
            //         $phone_no = '';
            //         $country_code = $patientData->country_code;
            //         if(!empty($country_code)) {
            //             $country_code = str_replace("00", "",$country_code);
            //         }
            //         elseif(empty($country_code) || $country_code=='0') {
            //             $country_code = '43'; //Austria country code
            //         }
            //         $country_code = str_replace("+", "",$country_code);
            //         if(!empty($patientData->mobile_no))
            //         {
            //             $phone_no = $country_code."".str_replace("-", "",$patientData->mobile_no);
            //         }
            //         if(!empty($phone_no))
            //         {
            //             self::_sendSmsAppointment($phone_no,$urlEventId);
            //         }
            //         elseif(!empty($patientData->email))
            //         {
            //             self::_sendMailAppointment($patientData->id,$urlEventId);
            //         }
            //     }
            // }//if urlEventId and urlPatientId



        // return response()->json($this->JsonData);
        return $this->JsonData;
       }//if not empty session data

    }//bookAppointment 25 aug 22

    //Added new function on 14-oct-25
    public function deactivateReminderNew($appoitment,$services=array())
    {
        
        Log::info("in _deactivateReminderNew function");
        Log::info("appoitment=id=>");
        Log::info($appoitment->id);

        Log::info("services==>");
        Log::info($services);

        $appointmentServices=array();
        $all_services = DB::connection('tenant')
                        ->table('appoinment_type_has_examinations')
                        ->select('examination_id')
                        ->where(['appoinment_id'=>$appoitment->appointment_type_id])
                        ->get();

        Log::info("all_services==>");
        Log::info($all_services);                


        foreach ($all_services as $key => $value) {
            $appointmentServices[]=$value->examination_id;
            // Log::info($appointmentServices[]);
            if(is_array($services) && in_array($value->examination_id, $services)) //condition added in 2-jan-24
           {
                $ids = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where(['patient_id'=>$appoitment->patient_id,
                                'status'=>'activate',
                                'service_id'=>$value->examination_id])
                                ->whereIn('reminder_status',['Set','ignore'])
                                ->get();
                $id_holder = [];
                $generalServcieCheck=1;
                //Dont deactivdate general remidner when we create the appoitment added by Swati 20-Oct-22
                 $checkGeneralServcie=DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                    ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                    ->where('service_id',$value->examination_id)
                    ->get();
                // if(!empty($checkGeneralServcie)) //commented on 2-jan-24 for deactivate services on book and //added on 2-jan-24              
                if(!empty($checkGeneralServcie) && isset($checkGeneralServcie) && $checkGeneralServcie->count() > 0) 
                {

                    $today_date=date("Y-m-d");
                    $checkServiceReminders = DB::connection('tenant')->table('patient_has_service_reminder')
                                    ->where('service_id',$value->examination_id)
                                    ->where('patient_id',$appoitment->patient_id)
                                    ->whereIn('reminder_status',['Set','ignore'])
                                    ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                    ->first();

                    if(!empty($ids) && empty($checkServiceReminders))
                    {

                        foreach($ids as $id=>$value_id)
                        {                    
                            $id_holder[] = $value_id->id;
                        }
                    }
                    else $generalServcieCheck=0;
                }
                else{
                    Log::info("in else");
                     if(!empty($ids))
                    {
                        foreach($ids as $id=>$value_id)
                        {                    
                            $id_holder[] = $value_id->id;
                        }
                    }
                }
                //End====================================================================
                if($generalServcieCheck){
                    $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                               ->whereIn('service_reminder_id',$id_holder)
                                               ->update(['status'=>'deactivate']);


                   if(isset($value->examination_id)){
                        Log::info("in above condition exam to be updated deactivate is ".$value->examination_id);                  
                   }                            
                   DB::connection('tenant')->table('patient_has_service_reminder')->where(['patient_id'=>$appoitment->patient_id,'status'=>'activate','service_id'=>$value->examination_id])->whereIn('reminder_status',['Set','ignore'])->update(['status'=>'deactivate']);
                }
            }//if inarray   

        }//foreach

        Log::info("if services below condition");
        if(is_array($services) && !empty($services)){
            foreach ($services as $value) {
                // log::info($value);
                // log::info($appointmentServices);
                if(!in_array($value, $appointmentServices)){
                    $ids = DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where(['patient_id'=>$appoitment->patient_id,
                                        'status'=>'activate',
                                        'service_id'=>$value])
                                        ->whereIn('reminder_status',['Set','ignore'])
                                        ->get();
                    $id_holder = [];
                    $generalServcieCheck=1;
                    //Dont deactivdate general remidner when we create the appoitment added by Swati 20-Oct-22
                    $checkGeneralServcie=DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                        ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                        ->where('service_id',$value)
                        ->get();
                    if(!empty($checkGeneralServcie)){
                        $today_date=date("Y-m-d");
                        $checkServiceReminders =  DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where('service_id',$value)
                                        ->where('patient_id',$appoitment->patient_id)
                                        ->whereIn('reminder_status',['Set','ignore'])
                                        ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                        ->first();
                        if(!empty($ids) && empty($checkServiceReminders))
                        {
                            foreach($ids as $id=>$value_id)
                            {                    
                                $id_holder[] = $value_id->id;
                            }
                        }
                    }
                    else{
                         if(!empty($ids))
                        {
                            foreach($ids as $id=>$value_id)
                            {                    
                                $id_holder[] = $value_id->id;
                            }
                        }
                    }
                    if($generalServcieCheck){
                        // log::info($value);
                        $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                                   ->whereIn('service_reminder_id',$id_holder)
                                                   ->update(['status'=>'deactivate']);

                        if(isset($value)){
                           Log::info("in below condition exam to be updated deactivate is".$value);
                        }                              
                                                     
                        DB::connection('tenant')->table('patient_has_service_reminder')->where(['patient_id'=>$appoitment->patient_id,'status'=>'activate','service_id'=>$value])->whereIn('reminder_status',['Set','ignore'])->update(['status'=>'deactivate']);
                    }
                }
            }
        }
        Log::info("stop if"); 
    }//


     public function getWebAppointmentStartDate(Request $request)
    {
       //Check if quarter setting is off then show the first avaliable date of doctor selected if its on then check according quarter if appointment booked in current quarter then it should show the first avaliable date of current quarter otherwise check for the next quarter first avaliable date.

       if(isset($request->doctor_id) && !empty($request->doctor_id))
       {

        $quarter_setting=0;
        $OPTIMAL_APPOINTMENT ='OPTIMAL_APPOINTMENT';
        $optimal_appointment = DB::table('settings')->where(['setting_key'=>$OPTIMAL_APPOINTMENT])->select('setting_key','setting_value','description')->first();
        if(isset($optimal_appointment) && !empty($optimal_appointment))
        {
            $quarter_setting = $optimal_appointment->setting_value;


        }//if optimal appointment

        // changes by vijay 7/3/24
        $optimalAppointment = 0;
        if (isset ($request->appoinmant_type_id) && !empty ($request->appoinmant_type_id)) {
            $checkAppointmentType = $this->AppointmentTypesModel->where('id', $request->appoinmant_type_id)->first();
            $optimalAppointment = $checkAppointmentType->optimal_appointment;
        }
        //end changes

        $todaysdate = date('Y-m-d');
        $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$request->doctor_id and STATUS=1 AND start_date>='$todaysdate'");

        //is_already_registered means if its login
        // if($quarter_setting==1 && $request->is_already_registered==1)  //changes by vijay 8/3/24
        if ($optimalAppointment == 1 && $request->is_already_registered == 1 && ($quarter_setting == 1 || $quarter_setting == 0))
        {

                $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
                $bookingtimeframe = DB::table('settings')->where(['setting_key'=>$APPOINTMENT_TIME_PERIOD])->select('setting_key','setting_value','description')->first();
                $description =  $bookingtimeframe->description;
                $setting_value =  $bookingtimeframe->setting_value;
                $patient_id = $request->patient_id;
                $doctor_id = $request->doctor_id;
                $month = date("n");
                $Quarter = ceil($month / 3);

                $todaysdate = date('Y-m-d');
                $flag_set = array('0','1');
                $year = date("Y");

                $count = 0;
                $avaliable_date = "";
                $no_of_days = 0;

                for ($i = $Quarter;$i <= 6;$i++) {
                    $j = $i;
                    $quarters = [5 => 1, 6 => 2, 7 => 3, 8 => 4];
                    if (in_array($i, [5, 6, 7, 8])) {
                        $j = $quarters[$i];
                        $year = date("Y", strtotime("+1 year"));
                    }
                    $time_slots=[];

                    $check_appointment_exists = $this->BaseModel
                                        ->whereRaw("quarter(start_date)=$j and year(start_date)=$year")
                                        //->where('doctor_id',$doctor_id) //commented on 14-sept-22
                                        ->where('patient_id',$patient_id)->where('status',1)
                                        ->where('appointment_status','!=','Vermisst')   // Added on 21-sept-22
                                        ->first();
                    if(empty($check_appointment_exists)){

                    $appointdatesarr = isset($getStartDates[0]->appointment_dates)?$getStartDates[0]->appointment_dates:0;


                      $time_slots = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                ->join("roster_has_weeks_has_time_frames",function($join){
                                    $join->on("roster_has_weeks_has_time_frames.roster_id","=","roster_has_dates.roster_id")
                                        ->on("roster_has_weeks_has_time_frames.start_date","=","roster_has_dates.start_date")
                                        ->on("roster_has_weeks_has_time_frames.end_date","=","roster_has_dates.end_date");

                                })

                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                ->where('roster_has_dates.date','>=',$todaysdate)
                                ->whereRaw("quarter(date)=$j and year(date)=$year")
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                                // ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN (SELECT start_date FROM appointment where doctor_id=$doctor_id and STATUS=1))")
                                 ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")

                                ->groupBy('roster_has_dates.date')
                                ->first(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_weeks_has_time_frames.id as r_id']);
                            }

                            //dd($time_slots);
                    if (!empty($time_slots) && isset($time_slots)) {
                        $time_slots = $time_slots->toArray();
                         //dd($time_slots);
                        //exit;
                        $count=1;

                       // $avaliable_date1 =$time_slots[0]['date'];
                        $avaliable_date1 =$time_slots['date'];
                        $avaliable_date =  date("Y/m/d", strtotime($avaliable_date1));
                        if($description=="week")
                        {
                            $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' week'));
                        }
                        elseif($description=="month")
                        {
                          $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' month'));
                        }
                        $now = strtotime($avaliable_date);
                        $your_date = strtotime($endDate);
                        $datediff = $your_date- $now;
                        $no_of_days =  round($datediff / (60 * 60 * 24));

                        $dataArray = [
                            'count'=>$count,
                            'avaliable_date'=>(isset($avaliable_date) && !empty($avaliable_date))?date("d/m/Y",strtotime($avaliable_date)):'',
                            'end_date'=> (isset($endDate) && !empty($endDate))?date("d/m/Y",strtotime($endDate)):'',
                            'description'=>$description,
                            'setting_value'=>$setting_value,
                            'no_of_days'=>$no_of_days
                        ];
                        return $dataArray;

                    }//if time slot

                }//for


        }//if quarter setting is 1
        else
        {

            $avaliable_date=$endDate='';
            $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
            $bookingtimeframe = DB::table('settings')->where(['setting_key'=>$APPOINTMENT_TIME_PERIOD])->select('setting_key','setting_value','description')->first();
            $doctor_id = $request->doctor_id;
            $todaysdate = date('Y-m-d');
            $flag_set = array('0','1');
            $patient_id = $request->patient_id;
            $doctor_id = $request->doctor_id;

            // commented below code on 2 sept 22
           /* $data = DB::table('roster_has_dates')->select('roster_has_dates.date as avaliable_date')
                ->join('roster', 'roster.id', '=', 'roster_has_dates.roster_id')
                ->join('roster_has_weeks_has_time_frames', 'roster_has_weeks_has_time_frames.roster_id', '=', 'roster_has_dates.roster_id')
                ->where('roster_has_dates.date', '>=', $todaysdate)->where('roster.doctor_id',$doctor_id)
                ->WhereIn('roster_has_weeks_has_time_frames.time_frame_flag',$flag_set)
                ->orderBy('roster_has_dates.date','ASC')
                ->get();*/



            if(isset($patient_id) && !empty($patient_id))
            {

                // $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$request->doctor_id and STATUS=1 AND start_date>='$todaysdate' and patient_id=$patient_id");

                $appointdatesarr = isset($getStartDates[0]->appointment_dates)?$getStartDates[0]->appointment_dates:0;

                 $data = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                    $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
                })->where('roster.doctor_id', $doctor_id)
                 ->where('roster_has_dates.is_excluded', '=', 0)
                 ->where('roster_has_dates.date', '>=', $todaysdate)
                 ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                 ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))

                 // ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN (SELECT start_date FROM appointment where doctor_id=$doctor_id and STATUS=1 and patient_id=$patient_id))")

                 ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")

                 ->groupBy('roster_has_dates.date')
                 ->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);
            }else
            {

                 // $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$request->doctor_id and STATUS=1 AND start_date>='$todaysdate'");

                 $appointdatesarr = isset($getStartDates[0]->appointment_dates)?$getStartDates[0]->appointment_dates:0;


                  $data = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                    $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
                })->where('roster.doctor_id', $doctor_id)
                  ->where('roster_has_dates.is_excluded', '=', 0)
                  ->where('roster_has_dates.date', '>=', $todaysdate)
                  ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                  ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))

                  // ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN (SELECT start_date FROM appointment where doctor_id=$doctor_id and STATUS=1))")

                  ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")

                  ->groupBy('roster_has_dates.date')
                  ->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);
            }//else



            // dump($data);
            $description =  $bookingtimeframe->description;
            $setting_value =  $bookingtimeframe->setting_value;
            if(isset($data) && !empty($data))
            {
                $count = 1;
                // $avaliable_date1 =$data[0]->avaliable_date;
                $avaliable_date1 = $data->date;
                $avaliable_date =  date("Y/m/d", strtotime($avaliable_date1));
                if($description=="week")
                {
                    $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' week'));
                }
                elseif($description=="month")
                {
                  $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' month'));
                }
                $now = strtotime($avaliable_date);
                $your_date = strtotime($endDate);
                $datediff = $your_date- $now;
                $no_of_days =  round($datediff / (60 * 60 * 24));
            }
            else
            {
                $count = 0;
                $avaliable_date = "";
                $description =  $bookingtimeframe->description;
                $setting_value =  $bookingtimeframe->setting_value;
                $no_of_days = 0;
            }

            $dataArray = [
                'count'=>$count,
                'avaliable_date'=>(isset($avaliable_date) && !empty($avaliable_date))?date("d/m/Y",strtotime($avaliable_date)):'',
                'end_date'=> (isset($endDate) && !empty($endDate))?date("d/m/Y",strtotime($endDate)):'',
                'description'=>$description,
                'setting_value'=>$setting_value,
                'no_of_days'=>$no_of_days
            ];
            return $dataArray;
       }//else of quarter setting
      }//if isset doctor id
    }//getWebAppointmentStartDate

      public function getWebAppointmentEndDate(Request $request)
    {


        if(isset($request->start_date) && !empty($request->start_date))
        {
            $start_date = $request->start_date;
            $end_date='';
            $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
            $bookingtimeframe = DB::table('settings')->where(['setting_key'=>$APPOINTMENT_TIME_PERIOD])->select('setting_key','setting_value','description')->first();

            $description =  $bookingtimeframe->description;
            $setting_value =  $bookingtimeframe->setting_value;

            if($description=="week")
            {
                $end_date =  date('Y/m/d', strtotime($start_date. ' + '.$setting_value.' week'));
            }
            elseif($description=="month")
            {

              $end_date =  date('Y/m/d', strtotime($start_date. ' + '.$setting_value.' month'));
            }

        }//if not empty start date

        $dataArray = [
            'start_date'=>(isset($start_date) && !empty($start_date))?date("d/m/Y",strtotime($start_date)):'',
            'end_date'=> (isset($end_date) && !empty($end_date))?date("d/m/Y",strtotime($end_date)):'',
            'description'=>$description,
            'setting_value'=>$setting_value
        ];
        return $dataArray;
    }//getWebAppointmentEndDate

        //New function added by divya on page load after login to get first date on 14-sept-22
     public function getWebStartDate(Request $request)
    {

        $quarter_setting=0;
        $OPTIMAL_APPOINTMENT ='OPTIMAL_APPOINTMENT';
        $optimal_appointment = DB::table('settings')->where(['setting_key'=>$OPTIMAL_APPOINTMENT])->select('setting_key','setting_value','description')->first();
        if(isset($optimal_appointment) && !empty($optimal_appointment))
        {
            $quarter_setting = $optimal_appointment->setting_value;
        }//if optimal appointment

        $todaysdate = date('Y-m-d');

        //is_already_registered means if its login
        if($quarter_setting==1 && $request->is_already_registered==1)
        {

                $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
                $bookingtimeframe = DB::table('settings')->where(['setting_key'=>$APPOINTMENT_TIME_PERIOD])->select('setting_key','setting_value','description')->first();
                $description =  $bookingtimeframe->description;
                $setting_value =  $bookingtimeframe->setting_value;
                $patient_id = $request->patient_id;
                $month = date("n");
                $Quarter = ceil($month / 3);
                $todaysdate = date('Y-m-d');
                $flag_set = array('0','1');
                $year = date("Y");

                $count = 0;
                $avaliable_date = "";
                $no_of_days = 0;

               // $allQuarterIsBooked=0; //flag added on 2-may-24

                for ($i = $Quarter;$i <= 6;$i++) {

                    $j = $i;
                    $quarters = [5 => 1, 6 => 2, 7 => 3, 8 => 4];
                    if (in_array($i, [5, 6, 7, 8])) {
                        $j = $quarters[$i];
                        $year = date("Y", strtotime("+1 year"));
                    }
                    $time_slots=[];

                    $check_appointment_exists = $this->BaseModel
                                        ->whereRaw("quarter(start_date)=$j and year(start_date)=$year")
                                        ->where('patient_id',$patient_id)
                                        ->where('appointment_status','!=','Vermisst')   // Added on 21-sept-22
                                        ->where('status',1)
                                        ->first();
                    // dump($j);
                    // dump($year);
                    // dump($check_appointment_exists);
                    $first_date="";
                    if(empty($check_appointment_exists))
                    {
                        $first_date = date('Y-m-d', strtotime($year . '-' . (($j * 3) - 2) . '-1'));
                        if($first_date<$todaysdate)
                        {
                             $first_date = $todaysdate;
                        }
                        else
                        {
                             $first_date = $first_date;
                        }

                        $count=1;
                        $avaliable_date1 =$first_date;
                        $avaliable_date =  date("Y/m/d", strtotime($avaliable_date1));
                        if($description=="week")
                        {
                            $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' week'));
                        }
                        elseif($description=="month")
                        {
                          $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' month'));
                        }//else if

                        $now = strtotime($avaliable_date);
                        $your_date = strtotime($endDate);
                        $datediff = $your_date- $now;
                        $no_of_days =  round($datediff / (60 * 60 * 24));

                        $dataArray = [
                            'count'=>$count,
                            'avaliable_date'=>(isset($avaliable_date) && !empty($avaliable_date))?date("d/m/Y",strtotime($avaliable_date)):'',
                            'end_date'=> (isset($endDate) && !empty($endDate))?date("d/m/Y",strtotime($endDate)):'',
                           // 'description'=>$description,
                           // 'setting_value'=>$setting_value,
                           // 'no_of_days'=>$no_of_days
                            'quarter_setting'=>$quarter_setting
                        ];
                        return $dataArray;

                    }//if empty appointment exists
                    // else{

                    //     //this else part added on 2-may-24 for is all quarters booked condition
                    //     $allQuarterIsBooked=1;
                    // }


                }//for

                //below if condition added on 2-may-24
                // if($allQuarterIsBooked==1)
                // {
                //     $dataArray = [
                //             'msg'=>'all quarters booked'
                //           ];
                //     return $dataArray;
                // }


        }//if quarter setting is 1
        else
        {
            // dump('quarter setting is off');

            $avaliable_date=$endDate='';
            $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
            $bookingtimeframe = DB::table('settings')->where(['setting_key'=>$APPOINTMENT_TIME_PERIOD])->select('setting_key','setting_value','description')->first();
            $todaysdate = date('Y-m-d');

            $count = 0;
            $no_of_days = 0;
            $appoinmentStartDate = "";
            $appointmentEndDate="";

              //Take settings for booking timeframe and quarter setting
            $appoinmentStartDate = $appointmentEndDate ='';
            if(isset($bookingtimeframe) && !empty($bookingtimeframe))
            {
                 $appoinmentStartDate = date('Y-m-d');

                 $setting_value = $bookingtimeframe->setting_value;
                 $description = $bookingtimeframe->description;

                  /* commented on 30-jan-23 for getting selected doctor dates
                 if($description=="month")
                 {
                     $appointmentEndDate =  date('Y-m-d', strtotime("+$setting_value months", strtotime($appoinmentStartDate)));
                 }

                  if($description=="week")
                 {
                     $appointmentEndDate =  date('Y-m-d',strtotime("+$setting_value week",strtotime($appoinmentStartDate)));
                 }//week
                 */

                  //added on 30-jan-23 for getting selected doctor dates
                 if(isset($request->doctor_id) && !empty($request->doctor_id))
                 {

                    $todaysdate = date('Y-m-d');
                    $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$request->doctor_id and STATUS=1 AND start_date>='$todaysdate'");

                    $appointdatesarr = isset($getStartDates[0]->appointment_dates)?$getStartDates[0]->appointment_dates:0;

                    $data = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                           $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
                        })->where('roster.doctor_id', $request->doctor_id)
                          ->where('roster_has_dates.is_excluded', '=', 0)
                          ->where('roster_has_dates.date', '>=', $todaysdate)
                          ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                          ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))
                          ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")

                          ->groupBy('roster_has_dates.date')
                          ->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);


                    if(isset($data) && !empty($data))
                    {
                        $avaliable_date1 = $data->date;
                        $appoinmentStartDate =  date("Y/m/d", strtotime($avaliable_date1));
                        if($description=="week")
                        {
                            $appointmentEndDate =  date('Y/m/d', strtotime($appoinmentStartDate. ' + '.$setting_value.' week'));
                        }
                        elseif($description=="month")
                        {
                          $appointmentEndDate =  date('Y/m/d', strtotime($appoinmentStartDate. ' + '.$setting_value.' month'));
                        }
                    }//if data
                    else
                    {
                         if($description=="month")
                         {
                             $appointmentEndDate =  date('Y-m-d', strtotime("+$setting_value months", strtotime($appoinmentStartDate)));
                         }
                          if($description=="week")
                         {
                             $appointmentEndDate =  date('Y-m-d',strtotime("+$setting_value week",strtotime($appoinmentStartDate)));
                         }//week
                     }//else of data

                 }//if isset doctor id
                 else
                 {
                     if($description=="month")
                     {
                         $appointmentEndDate =  date('Y-m-d', strtotime("+$setting_value months", strtotime($appoinmentStartDate)));
                     }
                      if($description=="week")
                     {
                         $appointmentEndDate =  date('Y-m-d',strtotime("+$setting_value week",strtotime($appoinmentStartDate)));
                     }//week
                 }//else of doctor id not avaliable

                //End added on 30-jan-23 for getting selected doctor dates


            }//bookingtimeframe


            $dataArray = [
                'count'=>$count,
                'avaliable_date'=>(isset($appoinmentStartDate) && !empty($appoinmentStartDate))?date("d/m/Y",strtotime($appoinmentStartDate)):'',
                'end_date'=> (isset($appointmentEndDate) && !empty($appointmentEndDate))?date("d/m/Y",strtotime($appointmentEndDate)):'',
                 'quarter_setting'=>$quarter_setting
            ];
            return $dataArray;
       }//else of quarter setting
    }//getWebAppointmentStartDate

      // get month name from number
    function month_name($month_number){
        return date('F', mktime(0, 0, 0, $month_number, 10));
    }
     // get get last date of given month (of year)
    function month_end_date($year, $month_number){
        return date("t", strtotime("$year-$month_number-0"));
    }

    // return two digit month or day, e.g. 04 - April
    function zero_pad($number){
        if($number < 10)
            return "0$number";

        return "$number";
    }

    // Return quarters between tow dates. Array of objects
    function get_quarters($start_date, $end_date){

        $quarters = array();

        $start_month = date( 'm', strtotime($start_date) );
        $start_year = date( 'Y', strtotime($start_date) );

        $end_month = date( 'm', strtotime($end_date) );
        $end_year = date( 'Y', strtotime($end_date) );

        $start_quarter = ceil($start_month/3);
        $end_quarter = ceil($end_month/3);

        $quarter = $start_quarter; // variable to track current quarter

        // Loop over years and quarters to create array
        for( $y = $start_year; $y <= $end_year; $y++ ){
            if($y == $end_year)
                $max_qtr = $end_quarter;
            else
                $max_qtr = 4;

            for($q=$quarter; $q<=$max_qtr; $q++){

                $current_quarter = new stdClass();

                $end_month_num = $this->zero_pad($q * 3);
                $start_month_num = ($end_month_num - 2);

                $q_start_month = $this->month_name($start_month_num);
                $q_end_month = $this->month_name($end_month_num);

                //$current_quarter->period = "Qtr $q ($q_start_month - $q_end_month) $y";
                $current_quarter->period = "$q";
                $current_quarter->year = "$y";
                $current_quarter->period_start = "$y-$start_month_num-01";      // yyyy-mm-dd
                $current_quarter->period_end = "$y-$end_month_num-" . $this->month_end_date($y, $end_month_num);

                $quarters[] = $current_quarter;
                unset($current_quarter);
            }

            $quarter = 1; // reset to 1 for next year
        }

        return $quarters;

    }//get_quarters

    //End Smart Appoitment 15-Sep-22 added by divya===============================


  /*---------------------------------
    |   FORGOT PASSWORD
    ------------------------------------------*/

        public function forgotPasswordWeb(ForgotPasswordRequest $request)
        {
            App::setLocale('de');
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.FAIL_FORGOT_PASSWORD_MATCH');

            $email = self::_validateUsername($request->email);
            $patientId = $request->patient_id;
            $patientCollection = $this->PatientsModel->where('id',$patientId)->first();

            if (!empty($patientCollection))
            {
                if (!$patientCollection->status)
                {
                    $this->JsonData['msg'] = __('admin.FAIL_FORGOT_PASSWORD_DISABLED');
                    return response()->json($this->JsonData);exit;
                }

                $patientCollection->username = $patientCollection->first_name." ".$patientCollection->family_name;
                // $patientCollection->patient_id = $patientId;
                $token = $this->PasswordBroker->createToken($patientCollection);
                $patient_id = base64_encode(base64_encode($patientId));
                $patientCollection->url = url('/online-appointment/reset-password-web/'.$token.'/'.$patient_id);

                $ordinationName=(!empty(config('ordination_name'))) ? ucfirst(config('ordination_name')):'Puremed'; //added on 17-june-25 #367


                try {

                    //commented on 17-june-25 #367
                    // $result = Mail::to($patientCollection->email)->send(new ForgotPasswordMailWeb($patientCollection,'web'));

                    //changed on 17-june-25 #367
                    $result = Mail::to($patientCollection->email)->send(new ForgotPasswordMailWeb($patientCollection,'web',$ordinationName));

                    $post = $this->PasswordResetModel->create([
                        'email' => $patientCollection->email,
                        'token' => $token
                    ]);

                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']      = __('admin.FORGOT_PASSWORD_STATUS');
                    $this->JsonData['url']      = url('/online-appointments/');
                }
               catch(\Exception $e) {

                    $this->JsonData['exception'] = $e->getMessage();
                    return response()->json($this->JsonData);exit;

                }
            }

            return response()->json($this->JsonData);
        }
/*---------------------------------
    |   SUBTITUTE FUNCTIONS
    ------------------------------------------*/
        public function _validateUsername($email)
        {
            // dd($email);
            //$email = $username;
            // dd(filter_var($email, FILTER_VALIDATE_EMAIL));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            {
                //dd('Testtttt');
                $patientCollection = $this->PatientsModel
                                        ->where('email',  $email)
                                        ->whereStatus(1)
                                        ->first();
                                        // dd($patientCollection);

                if(empty($patientCollection))
                {
                    return response()->json($this->JsonData);exit;
                }
                /*if(!empty($patientCollection) && !$patientCollection->hasRole('super-admin'))
                {   //
                    return response()->json($this->JsonData);exit;
                }*/

                $email = $patientCollection->email;
            }

            return $email;
        }

     /*---------------------------------
    |   RESET PASSWORD
    ------------------------------------------*/

        public function resetPasswordWeb($token,$patient_id)
        {
            $patient_id = base64_decode(base64_decode($patient_id));
            App::setLocale('de');
            $this->ViewData['moduleTitle']  = __('admin.TITLE_RESET_PASSWORD_MODULE');
            $this->ViewData['moduleAction'] = __('admin.TITLE_RESET_PASSWORD_MODULE');
            $this->ViewData['modulePath']   = $this->ModulePath.'reset.password';

            $collection = $this->PasswordResetModel
                            ->where('token',$token)
                            ->where('created_at','>',Carbon::now()->subHours(24))
                            ->first();

            if(!empty($collection))
            {
                $this->ViewData['email'] = $collection->email;
                $this->ViewData['token'] = $token;
                $this->ViewData['patient_id'] = $patient_id;


                return view($this->ModuleView.'reset_password_web', $this->ViewData);
            }
            else
            {
                return view($this->ModuleView.'reset_token_expired', $this->ViewData);
            }
        }
    ////Roshani added on 05-08-2024
    public function resetPasswordWebSubmit(ResetPasswordRequestWeb $request, $token)
        {
            // App::setLocale('de');
            // $this->JsonData['status'] = __('admin.RESP_ERROR');
            // $this->JsonData['msg']    = __('admin.FAIL_RESET_PASSWORD_STATUS_CHANGE');

            // $isValidObject = $this->PasswordResetModel->where('token',$token)->first();
            // if($isValidObject)
            // {
            //     $collection = $this->PatientsModel->where('id',$request->hid_patient_id)->first();
            //     // $this->PatientsModel->where('id',$collection->id)->update(['password' => Hash::make($request->password),'str_password'=>$request->password]);
            //     $this->PatientsModel->where('id',$collection->id)->update(['password' => Hash::make($request->password)]);
            //     $this->PasswordResetModel->where('token',$token)->delete();

            //     $this->JsonData['status']   = __('admin.RESP_SUCCESS');
            //     $this->JsonData['msg']      = __('admin.RESET_PASSWORD_STATUS');
            //     $this->JsonData['url']      = url('/online-appointments/');
            // }
            // return response()->json($this->JsonData);
            try
            {
                $password = Hash::make($request->password);
                $patient_id = $request->hid_patient_id;
                if(isset($password) && !empty($password))
                {
                    $masterPatientupdate = DB::table('patients')
                                ->where('id',$patient_id)
                                ->update(['password'=>$password,'is_updated'=>'1']);

                    if(!empty($masterPatientupdate)){
                        $log_id = $patient_id;
                        $collection = DB::table('patients')->select(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','social_security_number'])->where('id',$patient_id)->first();
                        $data[]  = $collection;
                        // self::_createLog('SignupSendOtp',$log_id,'info');
                        // $this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
                    }else
                    {
                        $this->JsonData['status'] = __('front.RESP_ERROR');
                        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
                        return response()->json($this->JsonData);
                    }
                    $getOrdination=DB::table('patients_has_ordination')->where('fk_patient_id',$patient_id)->get();
                    if(!empty($getOrdination) && !empty($masterPatientupdate))
                    {
                        foreach($getOrdination as $ordination)
                        {
                            $getWebsites = DB::connection('system')
                                   ->table('websites')
                                   ->where('ordination_id',$ordination->fk_ordination_id)
                                   ->first(['uuid']);
                            $getWebsites = DB::connection('system')
                                   ->table('users')
                                   // ->where('ordination_id',$ordination->fk_ordination_id)
                                   ->where('id',1)
                                   ->first();

                            $database_name = $getWebsites->uuid;
                            $patient = DB::table('patients')
                                        ->where('id',$patient_id)
                                        ->first();

                            //commented first name and family name on 15-dec-23
                            $ordination_patient  = DB::table($database_name.'.patients')
                                                ->whereDate('birth_date', date('Y-m-d',strtotime($patient->birth_date)))
                                                ->where('mobile_no', $patient->mobile_no)
                                                ->whereNULL('deleted_at')
                                                ->first();
                            if(!empty($ordination_patient)){
                                $Patientupdate = DB::table($database_name.'.patients')
                                            ->where('id',$ordination_patient->id)
                                            ->update(['password'=>$password,'is_updated'=>'1','updated_at'=>date("Y-m-d H:i:s")]);
                            }
                        }//foreach($getOrdination as $ordination)
                        $log_id = $patient_id;
                        $collection = DB::table('patients')->select(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','social_security_number'])->where('id',$patient_id)->first();

                        //Added for master data table'
                        if(isset($collection) && !empty($collection))
                        {
                             $getWebsites = DB::connection('system')
                            ->table('patients')
                            ->whereDate('birth_date', date('Y-m-d',strtotime($collection->birth_date)))
                            ->where('mobile_no', $collection->mobile_no)
                            ->update(['password'=>$password,'is_updated'=>'1']);
                        }

                        //Added for master data table

                        $data[]  = $collection;

                        // self::_createLog('SignupSendOtp',$log_id,'info');

                        // $this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);

                        //WEB TOKEN EXPIRED START
                        $isValidObject = $this->PasswordResetModel->where('token',$token)->first();
                        if($isValidObject)
                        {
                            $this->PasswordResetModel->where('token',$token)->delete();
                        }
                        //WEB TOKEN EXPIRED END

                        $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                        $this->JsonData['msg']      = __('front.RESET_PASSWORD_SUCCESS');
                        $this->JsonData['url']      = url('/online-appointments/');
                    }//if(!empty($getOrdination) && !empty($masterPatientupdate))
                    else
                    {
                        $this->JsonData['status'] = __('front.RESP_ERROR');
                        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
                        return response()->json($this->JsonData);
                    }
                }//if(isset($password) && !empty($password))
            }//try
            catch(\Exception $e) {

                    $this->JsonData['exception'] = $e->getMessage();
                    return response()->json($this->JsonData);exit;

                }
            return response()->json($this->JsonData);
        }
    ////Roshani added on 05-08-2024

    ////Roshani added on 05-08-2024
    public function _createLog($name,$data,$type='info')
    {
      // config(['logging.channels.api.path' => storage_path('logs/api/api_'.date('Y-m-d').'.log')]);

      config(['logging.channels.api.path' => '/opt/app-shared/php/data/storage/logs/api/api_'.date('Y-m-d').'.log']);
      //Log::channel('api')->$type($name,array($data));
    }
    ////Roshani added on 05-08-2024
    public function confirmWebAppointment($app_id)
    {
        $app_id = base64_decode(base64_decode($app_id));

        // Initialize the variable
        $cancelAppoinmentStatus = null;

        if ($app_id) {
            $getPatientEmail = $this->BaseModel
                ->where('id', $app_id)
                ->first();
            if ($getPatientEmail) {
                if ($getPatientEmail['is_app_booked'] == 0) {
                    App::setLocale('de');
                    $this->ViewData['moduleTitle'] = __('admin.TITLE_CONFIRM_WEB_APPOINTMENT');
                    $this->ViewData['moduleAction'] = __('admin.TITLE_CONFIRM_WEB_APPOINTMENT');
                    $this->ViewData['app_id'] = $app_id;
                    return view($this->ModuleView . 'confirm_web_appointment', $this->ViewData);
                } else {
                    $patientData = $this->PatientsModel->where('id',$getPatientEmail->patient_id)->first();
                    $timestamp = strtotime($getPatientEmail->start_date);

                    setlocale(LC_TIME, 'de_AT.utf8');
                    $formattedDate = strftime("%e. %B, um %H:%M Uhr", $timestamp);
                    $this->ViewData['dateTime'] = $formattedDate;
                    $this->ViewData['name'] = $patientData->first_name. ' '.$patientData->family_name ;
                    // $cancelUrl = url('/cancelAppointment').'/'.$eventId;
                    // $this->ViewData['cancelLink'] = $cancelUrl;

                    $cancelAppoinmentStatus = 0;
                }
            } else {
                $cancelAppoinmentStatus = 1;
            }
        }

        // Pass the cancel appointment status variable to the view
        $this->ViewData['cancelAppoinmentStatus'] = $cancelAppoinmentStatus;
        return view($this->ModuleView . 'confirmed_web_appointment', $this->ViewData);
    }

    public function confirmOrNotWebAppointment(Request $request)
        {
            if(!empty($request))
            {
                $confirmation = $request->confirmation;

                if($confirmation == 'yes')
                {
                    $this->BaseModel->where('id',$request->app_id)->update(['is_app_booked' => 1]);
                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']      = __('front.APPOINTMENT_BOOKED');
                    $this->JsonData['url']      = url('/online-appointments/');
                }else
                {
                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']      = __('admin.APPOINTMENT_CANCEL');
                    $this->JsonData['url']      = url('/online-appointments/');
                }
            }
            return response()->json($this->JsonData);
        }//

    /*---------------------------------------------------------------------
    |  Delete Account
    |  Deletes the logged-in patient and their FUTURE appointments.
    |  Past appointments are left untouched. Mirrors the related-record
    |  cleanup done by the admin PatientsController::destroy() flow
    |  (scoped to future appointments), including Google Calendar deletion.
    ----------------------------------------------------------------------*/
    public function deleteAccount(Request $request)
    {
        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');

        // Identify the logged-in patient from the web-booking session
        $sessionData = session('loginPatientData');
        if(empty($sessionData))
        {
            return response()->json($this->JsonData);
        }

        $session   = json_decode(base64_decode($sessionData));
        $patientId = $session->patient_id;

        try
        {
            DB::beginTransaction();

            // All FUTURE appointments for this patient (past appointments excluded)
            $futureAppointments = $this->BaseModel
                                        ->where('patient_id', $patientId)
                                        ->where('start_date', '>=', date('Y-m-d H:i:s'))
                                        ->get();

            foreach($futureAppointments as $collection)
            {
                $appId = $collection->id;

                // Reactivate the patient's service reminders tied to this appointment
                // (must run BEFORE the examination rows are deleted, as it reads them).
                // Same as the cancel flow (cancelAppointmentNew).
                self::_activateReminderOnCancel($collection);

                // archive before removing (same as existing cancel/destroy flow)
                self::DeletedAppointmentTrack($collection);

                // Cascade delete related records for this appointment
                $this->AppointmentHasExaminationsModel->where('patient_id',$patientId)->where('appointment_id',$appId)->delete(); //hard
                $this->AppointmentHasNotificationModel->where('appointment_id',$appId)->where('patient_id',$patientId)->delete(); //hard
                $this->PatientHasDocumentsModel->where('appointment_id',$appId)->delete(); //hard
                $this->PatientsHasOldFindingModel->where('fk_patient_id',$patientId)->where('appointment_id',$appId)->delete(); //soft
                $this->PatientsHasDismissalModel->where('fk_patient_id',$patientId)->where('appointment_id',$appId)->delete(); //soft

                // Delete the linked Google Calendar event (best-effort: a calendar
                // failure must not abort the account deletion / DB transaction)
                try
                {
                    request()->merge(['eventId'=>$collection->google_event_id]);
                    app('App\Http\Controllers\Admin\DashboardController')->eventDelete(request());
                }
                catch(\Exception $ex)
                {
                    Log::info('deleteAccount calendar delete failed for appointment '.$appId.': '.$ex->getMessage());
                }

                // Free the roster time-frame slot so it can be booked again
                $timeFrame = date('H:i:s',strtotime($collection->start_date));
                $time_frames = $this->RosterHasDatesModel
                                    ->leftjoin('roster','roster.id','roster_has_dates.roster_id')
                                    ->whereDate('roster_has_dates.date',date('Y-m-d',strtotime($collection->start_date)))
                                    ->where('roster.doctor_id',$collection->doctor_id)
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
                        $oldUpdateTimeFrameFlg->comment         = 'patient_id '.$patientId.' deleted Appointment Date :'.$collection->start_date.' Appointment From deleteAccount current Date :'.date('Y-m-d H:i:s').' Time Fram Id : '.$getrec->id;
                        $oldUpdateTimeFrameFlg->save();
                    }
                }

                // Finally hard delete the appointment (SoftDeletes disabled on AppointmentModel)
                $collection->delete();
            }

            // Soft delete the patient's reminders (patient-wide, as in destroy())
            $reminderIds = $this->PatientsHasServiceReminderModel->where('patient_id',$patientId)->pluck('id')->toArray();
            $this->PatientsHasServiceReminderModel->where('patient_id',$patientId)->update(['deleted_at'=>date('Y-m-d H:i:s')]);
            if(!empty($reminderIds))
            {
                $this->PatientHasReminder->whereIn('service_reminder_id',$reminderIds)->update(['deleted_at'=>date('Y-m-d H:i:s')]);
            }

            // Delete the patient's LOCAL (this ordination) record (soft delete -> sets
            // deleted_at, same as the admin PatientsController::destroy() flow; PatientsModel
            // uses SoftDeletes)
            $patient = $this->PatientsModel->find($patientId);
            if(!empty($patient))
            {
                $patient->delete();

                // Remove the patient from THIS ordination in the central (system) DB, and
                // delete the generic/central patient only if they no longer belong to any
                // other ordination. Operates on the system connection (own transaction).
                self::_deletePatientOrdination($patient);
            }

            DB::commit();

            // Log the patient out of the web-booking session
            session(['loginPatientData' => '']);

            $this->JsonData['status'] = __('front.RESP_SUCCESS');
            $this->JsonData['msg']    = __('front.DELETE_ACCOUNT_SUCCESS', [], 'de');
            $this->JsonData['url']    = url('/');
        }
        catch(\Exception $e)
        {
            DB::rollback();
            Log::info('deleteAccount error: '.$e->getMessage());
        }

        return response()->json($this->JsonData);
    }//


    //start function added on 14-may-24 for web cr
      public function sendPatientOtp(Request $request)
    {

       // dump($request->all());

        $this->JsonData['status'] = __('front.RESP_ERROR');
        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
        try
        {

            $patientName = '';
            $patientFname = $patientLname= '';
            //Roshani start added the mobile number function for issue no. 134  on 05-07-2024
            if(isset($request->mobile_no) && !empty($request->mobile_no))
            {
                $formatted_mobile_no = self::formatPhoneNumber($request->mobile_no);
            }
            $mobile_no = isset($request->mobile_no) ? $formatted_mobile_no : $request->mobile_no_hidden;

            //Roshani stop added the mobile number function for issue no. 134 on 05-07-2024
            /***********added on 27-may-24****************************/
            $country_code = $request->country_code;
            if(!empty($request->format))
            {
                $country_code = $request->format;
            }

            if(!empty($country_code)) {
                    $country_code = str_replace("00", "",$country_code);
            }
            elseif(empty($country_code) || $country_code=='0') {
                $country_code = '43'; //Austria country code
            }
            $country_code = str_replace("+", "",$country_code);
             /***********added on 27-may-24****************************/


            //in case of login
            if($request->isLogin==1)
            {
                if(empty($request->password) || empty($mobile_no) || empty($request->birth_date))
                {
                    $this->JsonData['status'] = __('front.RESP_ERROR');
                    $this->JsonData['msg']    = __('front.ERR_ALL_FIELDS');
                    return response()->json($this->JsonData);
                }

                /*******start***added**on*17-may-24**************************/
                 // $patient_data = $this->PatientsModel
                 //    ->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))
                 //    ->where('mobile_no',$mobile_no)
                 //    ->where('email','=',$request->email)
                 //    ->orderby('id','DESC')
                 //    ->first();

                /*******start***added**on*27-may-24**************************/
                if(isset($request->dbEmailExists) && $request->dbEmailExists==0)
                {
                     $patient_data = $this->PatientsModel
                    ->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))
                    ->where('mobile_no',$mobile_no)
                    ->orderby('id','DESC')
                    ->first();
                }else{
                     $patient_data = $this->PatientsModel
                    ->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))
                    ->where('mobile_no',$mobile_no)
                    ->where('email','=',$request->email)
                    ->orderby('id','DESC')
                    ->first();
                }
                /*******start***added**on*27-may-24**************************/



                if (isset($patient_data) && Hash::check(base64_decode($request->password), $patient_data->password))
                {
                    $patientFname = isset($patient_data->first_name)?$patient_data->first_name:'';
                    $patientLname = isset($patient_data->family_name)?$patient_data->family_name:'';
                    $patientName = "Sehr geehrte*r ".$patientFname.' '.$patientLname;

                }else{
                    $this->JsonData['status'] = __('front.RESP_ERROR');
                    $this->JsonData['msg']    = __('front.PASSWORD_NOT_MATCHED');
                    return response()->json($this->JsonData);
                }
               /*******end***added**on*17-may-24*****************************/


            }//if login

            if($request->isLogin==0)
            {
                if(empty($request->email) || empty($mobile_no) || empty($request->birth_date))
                {
                    $this->JsonData['status'] = __('front.RESP_ERROR');
                    $this->JsonData['msg']    = __('front.ERR_ALL_FIELDS');
                    return response()->json($this->JsonData);
                }

                if (!filter_var($request->email, FILTER_VALIDATE_EMAIL))
                {
                    $this->JsonData['status'] = __('front.RESP_ERROR');
                    $this->JsonData['msg']    = __('front.ERR_VALID_EMAIL_ADDRESS');
                    return response()->json($this->JsonData);
                }

                 //$patientName = $request->email;
                 $patientName = "Sehr geehrte*r Patient*in";

            }//if register


            $otp_code = rand(1000, 9999);

            $updateFlag=0;

             //commented on 27-may-24
            // $getUserOtp = $this->PatientsOtpModel->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))->where('mobile_no','=',$mobile_no)->where('email','=',$request->email)->first();

            /*******start***added**on*27-may-24**************************/
            if(isset($request->dbEmailExists) && $request->dbEmailExists==0)
            {
                $getUserOtp = $this->PatientsOtpModel->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))->where('mobile_no','=',$mobile_no)->first();
            }else{
                $getUserOtp = $this->PatientsOtpModel->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))->where('mobile_no','=',$mobile_no)->where('email','=',$request->email)->first();
            }
            /*******end***added**on*27-may-24**************************/


            if(isset($getUserOtp) && !empty($getUserOtp))
            {

                //commented on 27-may-24

                // $updateData =$this->PatientsOtpModel
                //         ->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))
                //         ->where('mobile_no','=',$mobile_no)
                //         ->where('email','=',$request->email)
                //         ->update([
                //                     'login_otp' => $otp_code,
                //                     'otp_created_at' => date('Y-m-d H:i:s')
                //                 ]);

                /*******start***added**on*27-may-24**************************/
               if(isset($request->dbEmailExists) && $request->dbEmailExists==0)
               {
                 $updateData =$this->PatientsOtpModel
                        ->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))
                        ->where('mobile_no','=',$mobile_no)
                        ->update([
                                    'login_otp' => $otp_code,
                                    'otp_created_at' => date('Y-m-d H:i:s')
                                ]);
               }else{
                 $updateData =$this->PatientsOtpModel
                        ->whereDate('birth_date',date('Y-m-d',strtotime($request->birth_date)))
                        ->where('mobile_no','=',$mobile_no)
                        ->where('email','=',$request->email)
                        ->update([
                                    'login_otp' => $otp_code,
                                    'otp_created_at' => date('Y-m-d H:i:s')
                                ]);
               }
               /*******end***added**on*27-may-24**************************/



                $updateFlag=1;
            }//if getUserOtp
            else
            {
                 $otp_data= array(
                                    'birth_date'=> date('Y-m-d',strtotime($request->birth_date)),
                                    'mobile_no'=> $mobile_no,
                                    'email'=>isset($request->email)?$request->email:'',
                                    'login_otp'=> $otp_code,
                                    'otp_created_at'=>date('Y-m-d H:i:s')
                                );

                $insertOtpData = $this->PatientsOtpModel->insert($otp_data);
                $updateFlag=1;
            }//else

            if($updateFlag==1)
            {
                $details = [
                    'otp_code' =>  $otp_code,
                    'email' => $request->email,
                    'patientName' => $patientName
                    ];

                $ordinationName=(!empty(config('ordination_name'))) ? ucfirst(config('ordination_name')):'Puremed';

                //commented on 27-may-24
                // $result = Mail::to($request->email)->send(new OtpWebAppointment($details,$ordinationName));


                /**************27-may-24***********************/
                $channels = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();

                // if(isset($channels) && $channels->choice_of_channels=="email")
                // {

                    if($request->isLogin==1 && isset($request->dbEmailExists) && $request->dbEmailExists==0 && empty($request->email))
                    {

                        $phone   = $country_code."".str_replace("-", "",$mobile_no);

                        $message = 'Hallo ,Bitte verwenden Sie folgenden Code, um die Buchung zu bestätigen. '.$otp_code;

                        $issmssend = $this->_sendSms($phone,$message);

                        if($issmssend['error']==0)
                        {

                            $this->JsonData['msg']      = 'Sie haben eine E-Mail mit einem 4-stelligen Code erhalten. Bitte geben Sie den Code ein, um mit der Buchung Ihres Termins fortzufahren';
                            $this->JsonData['status']  = __('front.RESP_SUCCESS');
                            $this->JsonData['otp']  = $otp_code;


                        }else{
                            $this->JsonData['status'] = __('front.RESP_ERROR');
                            $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
                            $this->JsonData['otp']  = '';
                        }



                    }//if
                    else if($request->isLogin==1 && isset($request->dbEmailExists) && $request->dbEmailExists==1 && isset($request->email))
                    {

                        //send email in this case
                        $result = Mail::to($request->email)->send(new OtpWebAppointment($details,$ordinationName));
                        $this->JsonData['msg']      = 'Sie haben eine E-Mail mit einem 4-stelligen Code erhalten. Bitte geben Sie den Code ein, um mit der Buchung Ihres Termins fortzufahren';
                        $this->JsonData['status']  = __('front.RESP_SUCCESS');
                        $this->JsonData['otp']  = $otp_code;

                    }//if
                    else if($request->isLogin==0)
                    {

                        //send email in this case
                        $result = Mail::to($request->email)->send(new OtpWebAppointment($details,$ordinationName));
                        $this->JsonData['msg']      = 'Sie haben eine E-Mail mit einem 4-stelligen Code erhalten. Bitte geben Sie den Code ein, um mit der Buchung Ihres Termins fortzufahren';
                        $this->JsonData['status']  = __('front.RESP_SUCCESS');
                        $this->JsonData['otp']  = $otp_code;

                    }//if

                //}//if email
                /*else
                {

                    //Send sms directly in case of login or registration
                     $phone   = $country_code."".str_replace("-", "",$mobile_no);
                     $message = 'Hallo ,Bitte verwenden Sie folgenden Code, um die Buchung zu bestätigen. '.$otp_code;
                     $issmssend = $this->_sendSms($phone,$message);
                    if($issmssend['error']==0)
                    {

                        $this->JsonData['msg']      = 'Sie haben eine E-Mail mit einem 4-stelligen Code erhalten. Bitte geben Sie den Code ein, um mit der Buchung Ihres Termins fortzufahren';
                        $this->JsonData['status']  = __('front.RESP_SUCCESS');
                        $this->JsonData['otp']  = $otp_code;


                    }else{
                        $this->JsonData['status'] = __('front.RESP_ERROR');
                        $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
                        $this->JsonData['otp']  = '';
                    }

                }//else sms
                */



                /**************27-may-24***********************/



                //commented on 27-may-24

                // $this->JsonData['msg']      = 'Sie haben eine E-Mail mit einem 4-stelligen Code erhalten. Bitte geben Sie den Code ein, um mit der Buchung Ihres Termins fortzufahren';
                // $this->JsonData['status']  = __('front.RESP_SUCCESS');
                // $this->JsonData['otp']  = $otp_code;
            }
            else
            {
                $this->JsonData['status'] = __('front.RESP_ERROR');
                $this->JsonData['msg']    = __('front.ERR_SOMETHING_WRONG');
                $this->JsonData['otp']  = '';
            }//else updateFlag


        } catch (Exception $e)
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        $this->JsonData['otp'] = '';
        return response()->json($this->JsonData);

    }//sendPatientOtp

    //end function added on 14-may-24 for web cr

}//class