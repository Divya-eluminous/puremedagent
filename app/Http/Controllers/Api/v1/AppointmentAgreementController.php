<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;  
use App\Http\Controllers\Controller; 

//Models
use App\Models\AppointmentModel;
use App\Models\AppointmentDelayReportModel;
use App\Models\SettingsModel;
use App\Models\PatientsModel;
use App\Models\AppointmentHasQueueNumberModel;
use App\Mail\AppointmentReportDelayMail;
use App\Models\RosterModel;
use App\Models\AdminUserModel;
use App\Models\AppointmentTypesModel;
use App\Models\AppointmentHasNotificationModel;
use App\Models\ActivityLogModel;
use App\Models\WaitingNumberSymbolsModel;
use App\Models\AppointmentHasExaminationsModel;
use App\Models\ExaminationsModel;
use App\Models\PatientHasDocumentsModel;
use App\Models\SpecialistDocumentsModel;
use App\Models\RosterHasWeeksHasTimeFramesModel;

use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\CheckListModel;
 use App\Models\CheckListHasHeadingSectionModel; 
use App\Models\HeadingSectionHasQuestionModel;
use App\Models\CheckListHasSelectedQuestionModel; 
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\PatientsHasServiceReminderModel;
// TODO: Replace with Stancl tenancy equivalent
// use Hyn\Tenancy\Models\Website;
use App\Models\PatientHasReminder;
use App\Models\EventTypeHasExaminationsModel;
use App\Models\DeletedAppointmentTrackModel;
use App\Models\RosterHasDatesModel;
use Illuminate\Support\Facades\Log;
use App\Models\ChannelsRemindersSettingModel;

//Trait
use App\Traits\GeneralTrait; 

use Validator;
use Carbon\Carbon;
use Mail;
use DB; 
use Storage;
use PDF;


class AppointmentAgreementController extends BaseController
{
    private $BaseModel;
    use GeneralTrait;

    public function __construct(
        AppointmentModel $AppointmentModel,
        AppointmentDelayReportModel $AppointmentDelayReportModel,
        SettingsModel $SettingsModel,
        PatientsModel $PatientsModel,
        AppointmentHasQueueNumberModel $AppointmentHasQueueNumberModel,
        RosterModel $RosterModel,
        AdminUserModel $AdminUserModel,
        AppointmentTypesModel $AppointmentTypesModel,
        AppointmentHasNotificationModel $AppointmentHasNotificationModel,
        ActivityLogModel $ActivityLogModel,
        WaitingNumberSymbolsModel $WaitingNumberSymbolsModel,
        AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
        ExaminationsModel $ExaminationsModel,
        PatientHasDocumentsModel $PatientHasDocumentsModel,
        ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
        CheckListModel $CheckListModel,
        RosterHasDatesModel $RosterHasDatesModel,
        CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
        HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
        CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
        ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
        SpecialistDocumentsModel $SpecialistDocumentsModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
        EventTypeHasExaminationsModel $EventTypeHasExaminationsModel,
        Website $website,
        PatientHasReminder $PatientHasReminder,
        RosterHasWeeksHasTimeFramesModel $RosterHasWeeksHasTimeFramesModel,
        DeletedAppointmentTrackModel $DeletedAppointmentTrackModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel
    )
    {
        $this->BaseModel                       = $AppointmentModel;
        $this->AppointmentDelayReportModel     = $AppointmentDelayReportModel;
        $this->SettingsModel                   = $SettingsModel;
        $this->PatientsModel                   = $PatientsModel;
        $this->AppointmentHasQueueNumberModel  = $AppointmentHasQueueNumberModel;
        $this->RosterModel                      = $RosterModel;
        $this->AdminUserModel                   = $AdminUserModel;
        $this->AppointmentTypesModel            = $AppointmentTypesModel;
        $this->AppointmentHasNotificationModel  = $AppointmentHasNotificationModel;
        $this->ActivityLogModel                 = $ActivityLogModel;
        $this->WaitingNumberSymbolsModel        = $WaitingNumberSymbolsModel;
        $this->AppointmentHasExaminationsModel  = $AppointmentHasExaminationsModel;
        $this->ExaminationsModel                = $ExaminationsModel;
        $this->PatientHasDocumentsModel         = $PatientHasDocumentsModel;
        $this->ExaminationsHasMultipleCheckListModel = $ExaminationsHasMultipleCheckListModel;
        $this->CheckListModel = $CheckListModel;
        $this->CheckListHasHeadingSectionModel = $CheckListHasHeadingSectionModel;
        $this->HeadingSectionHasQuestionModel = $HeadingSectionHasQuestionModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->website  = $website;
        $this->RosterHasDatesModel            = $RosterHasDatesModel;
        $this->PatientHasReminder  = $PatientHasReminder;
        $this->EventTypeHasExaminationsModel = $EventTypeHasExaminationsModel;
        $this->RosterHasWeeksHasTimeFramesModel = $RosterHasWeeksHasTimeFramesModel;
        $this->DeletedAppointmentTrackModel = $DeletedAppointmentTrackModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        // $this->ViewData = [];
        // $this->JsonData = []; 
        // $this->ModuleTitle = 'Patients';
        // $this->ModuleView  = 'admin.patients.';
        // $this->ModulePath = 'admin.patients.';
    } 


    /*---------------------------------
    |   Doctors Listing
    ------------------------------------------*/
    public function getDoctors(Request $request)
    {
        $errors     = [];   
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        try{

            $collection = collect([]); 
            $modelQuery = $this->AdminUserModel
                                ->whereStatus(1)
                                ->whereHas('roles',function($query){
                                    $query->where('name', 'doctor');
                                });
            
            $search =   $request->doctor_name;                          
            if(!empty($search)){

                $modelQuery = $modelQuery->where(function ($query) use($search)
                        {
                            $query->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'LIKE', "%".$search."%");  
                            //$query->orwhere('users.email', 'LIKE', '%'.$search.'%'); 
                        }); 
            }

            $collection = $modelQuery->get();  

            if(!empty($collection) && ($collection->count() > 0))
            {
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');

                    $collection = $collection->map(function($item)
                            {

                                $profileImage = asset('assets/admin/images/default-image.png');
                                $new_img_path = self::StorePath($item->img_path.'/');
                                if (!empty($item->img_path)) 
                                {
                                    $profileImage = self::getFilePath($item->img_path);
                                    //$profileImage = url('/storage/app/'.$item->img_path); 
                                }

                                $item->image = $profileImage;

                                return $item;
                            });

                    $data  = $collection;
                    self::_createLog('getDoctors',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('Get Docors','Get doctors list','Get',null,$data);

            }else{
                $message  = __('api.ERR_NOT_FOUND');
                $errors[] = [
                      "error" => __('api.DATA_NOT_FOUND'),
                  ];
                self::_createLog('getDoctors',$errors,'error');
                // $this->ActivityLogModel->addApiLog('Get Docors','display doctors list','Show');
            }
        }
        catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
                self::_createLog('getDoctors',$errors,'error');
                // $this->ActivityLogModel->addApiLog('Get Docors','display doctors list','Show');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }


    /*---------------------------------
    |   Listing of Login patient appointments 
    ------------------------------------------*/
    public function getAppointment(Request $request){
       
         // Log::info('getAppointment....');

        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        
        $patientId  = $request->patient_id;
        $offset     = $request->offset;
        $limit      = $request->limit;
        $today_date  = $request->today_date;
        $current_date  = $request->current_date; // to fetch the records greater than current date

        $inputdata  = $request->all();
        // print_r($inputdata);
        // exit();
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'      => 'required',
                            ], 
                            [
                              'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),     
                            ]
                            ); 

            if ($validator->fails()) 
            {           
              $errors[] = $validator->errors(); 
            }else
            {

                $collections = collect([]); 
                $modelQuery = $this->BaseModel
                                    ->with(['assignedPatient','assignedDoctor','assignedAppointmentType',
                                        'hasExaminations'=>function($q){
                                            $q->with(['assignedExamination']);

                                        }]
                                    )
                                    ->where('patient_id', $patientId)
                                    ->whereStatus(1); 

                 // for single next appointment
                 if(!empty($today_date)){
                    // $today_date =  date('Y-m-d H:i:s', strtotime($today_date));
                    $today_date = date('Y-m-d H:i:s', strtotime(now()));
                    // $date = date('Y-m-d h:i:00', time());
                    // dd($today_date);
                    $modelQuery = $modelQuery
                                       ->where('start_date','>=',$today_date);
                 }

                 // for all today's appointment
                 if(!empty($current_date)){
                    // $current_date =  date('Y-m-d', strtotime($current_date));
                    $current_date = date('Y-m-d H:i:s', strtotime(now()));
                    $modelQuery = $modelQuery
                                        ->where('start_date','>=',$current_date);
                 }

                 if(!empty($limit)){

                    $modelQuery = $modelQuery
                                        ->skip($offset)
                                        ->take($limit); 
                 }

                 $collections = $modelQuery->orderBy('start_date', 'ASC')->get();

                // Log::info('collections....');
               //  Log::info($collections);

                 if(!empty($collections) && sizeof($collections) > 0){
                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');

                        $data  = [];
                        foreach ($collections as $key => $collection)
                        {

                          //  Log::info($collection->id);
                           
                            $data[$key]['id']  = $collection->id;
                            $data[$key]['start_date']  = $collection->start_date;
                            $data[$key]['end_date']  = $collection->end_date;
                            $data[$key]['patient_id']  = $collection->patient_id;
                            $data[$key]['doctor_id']  = $collection->doctor_id;
                            $data[$key]['appointment_type_id']  = $collection->appointment_type_id;
                            //dd($collection->assignedAppointmentType);
                            $data[$key]['appointment_type_name']  = $collection->assignedAppointmentType->name;

                            //commented below code on 22-nov-23 for isset condition 
                            // $data[$key]['patient_name']  = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                            // $data[$key]['doctor_name']  = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                            // $data[$key]['doctor_speciality']  = $collection->assignedDoctor->doctor_speciality; 

                            //Below code added on 22-nov-23
                            $pfname = $plname ='';
                            $pfname =isset($collection->assignedPatient->first_name)?$collection->assignedPatient->first_name:'';
                            $plname =isset($collection->assignedPatient->family_name)?$collection->assignedPatient->family_name:'';

                            $data[$key]['patient_name']  =$pfname." ".$plname;

                            $drfname = $drLname ='';
                            $drfname = isset($collection->assignedDoctor->first_name)?$collection->assignedDoctor->first_name:'';
                            $drLname = isset($collection->assignedDoctor->last_name)?$collection->assignedDoctor->last_name:'';
                               
                            $data[$key]['doctor_name']  = $drfname." ".$drLname;
                            
                            $data[$key]['doctor_speciality']  = isset($collection->assignedDoctor->doctor_speciality)?$collection->assignedDoctor->doctor_speciality:''; 
                            
                            //Above code added on 22-nov-23

                             $profileImage = asset('assets/admin/images/default-image.png');
                         
                            // if (!empty($collection->assignedDoctor->img_path) && is_file(storage_path().'/app/'.$collection->assignedDoctor->img_path)) 
                            if (!empty($collection->assignedDoctor->img_path)) 
                            {
                               
                                $assignedDoctor_img_path = self::StorePath($collection->assignedDoctor->img_path.'/');
                                $profileImage = self::getFilePath($collection->assignedDoctor->img_path);
                                //$profileImage = url('/storage/app/'.$collection->assignedDoctor->img_path); 
                            }

                            $data[$key]['doctor_image']  = $profileImage;

                            $data[$key]['exam_exist']  = 0;
                            
                            $data[$key]['exam_document_exist']  = 0;
                            $data[$key]['exams']  = [];
                            
                            $assignedAppointmentType_img_path = self::StorePath($collection->assignedAppointmentType->patient_document_path.'/');

                           //  Log::info('after collection id....');

                            // if (!empty($collection->assignedAppointmentType->patient_document_path) && is_file(storage_path().$collection->assignedAppointmentType->patient_document_path))
                            if (!empty($collection->assignedAppointmentType->patient_document_path)) 
                            {
                                //$data[$key]['exam_document_exist']  = 1;
                            }

                             // Log::info('collection has examination....');

                              // Log::info($collection->hasExaminations);

                            if(!empty($collection->hasExaminations) && sizeof($collection->hasExaminations)>0)
                            {
                                $data[$key]['exam_exist']  = 1;
                                foreach ($collection->hasExaminations as  $haskey=>$hasExamination) {
                                    
                                    //if(!empty($hasExamination->assignedExamination->id))


                                    //below isset conditions added on 16-nov-23 because assigned examination array is blank 

                                    // $data[$key]['exams'][$haskey]['id'] = $hasExamination->assignedExamination->id?$hasExamination->assignedExamination->id:'';

                                      $data[$key]['exams'][$haskey]['id'] = isset($hasExamination->assignedExamination->id)?$hasExamination->assignedExamination->id:'';


                                    //if(!empty($hasExamination->assignedExamination->name))

                                    // $data[$key]['exams'][$haskey]['name'] = $hasExamination->assignedExamination->name?$hasExamination->assignedExamination->name:'';


                                    $data[$key]['exams'][$haskey]['name'] = isset($hasExamination->assignedExamination->name)?$hasExamination->assignedExamination->name:'';


                                    //if(!empty($hasExamination->assignedExamination->url))

                                    // $data[$key]['exams'][$haskey]['url'] = $hasExamination->assignedExamination->url?$hasExamination->assignedExamination->url:'';

                                     $data[$key]['exams'][$haskey]['url'] = isset($hasExamination->assignedExamination->url)?$hasExamination->assignedExamination->url:'';


                                    //if(!empty($hasExamination->assignedExamination->id))
                                    $data[$key]['exams'][$haskey]['document_path'] = '';

                                    if(!empty($hasExamination->assignedExamination->name) && !empty($hasExamination->assignedExamination->url)){
                                        $data[$key]['exams'][$haskey]['document_path'] = url('storage'.$hasExamination->assignedExamination->url);
                                        //$data[$key]['exam_document_exist']  = 1;
                                    }
                                   

                                    if(!empty($hasExamination->assignedExamination->id)){
                                        $getServices = $this->ExaminationsModel
                                           ->join('examinations_has_multiple_document_list','examinations_has_multiple_document_list.fk_examinations_id','examinations.id')
                                           ->where('examinations.id',$hasExamination->assignedExamination->id)
                                           ->get();
                                        if(!empty($getServices) && sizeof($getServices)>0)
                                        {
                                            $data[$key]['exam_document_exist']  = 1;
                                        }
                                    }



                                }//foreach hasExaminations

                            }// not empty collection examination
                        
                        }//foreach

                        //$data  = $collection;
                        self::_createLog('getAppointment',$data,'info');
                        // $this->ActivityLogModel->addApiLog('Get Appointment','Get appointment List','Get'); 

                    }else{
                        $message  = __('api.ERR_NOT_FOUND');
                        $errors[] = [
                              "error" => __('api.DATA_NOT_FOUND'),
                          ];
                        self::_createLog('getAppointment',$errors,'error');
                        // $this->ActivityLogModel->addApiLog('getAppointment','send otp for login','Get');
                    }
            }
        }
        catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
                self::_createLog('getAppointment',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    /*---------------------------------------------------------
    |   Create an appointment delay report with sending mail to ordination
    ------------------------------------------------------------*/
    public function createAppointmentDelayReport(Request $request)
    { 
        $errors     = []; 
        $data       = [];
        $message    = __('api.ERR_INVALID_DATA');
        $status     = false;

        $patientId   = $request->patient_id;  

        // Get ordination email address
        $ordinationEmail = $this->SettingsModel
                            ->where('setting_key', 'ORDINATION_EMAIL_ADDRESS')
                            ->whereStatus(1)
                            ->first(['setting_value']);

        // Get patient data for send detail in email
        $patientData = $this->PatientsModel
                            ->where('id', $patientId)
                            ->whereStatus(1)
                            ->get(['first_name', 'family_name', 'email', 'birth_date']); 
                            
        $inputdata = $request->all();
        try {
        $validator = Validator::make($inputdata,[
                          'patient_id'      => 'required',
                          'appointment_id'  => 'required',
                          'delay_time'      => 'required',
                          'custome_message' => 'required',
                      
                        ],  
                        [
                          'patient_id.required'     => __('api.ERR_DELAY_REPORT_PATIENT_ID_REQ'),
                          'appointment_id.required' => __('api.ERR_APPOINTMENT_REQ'),
                          'delay_time.required'     => __('api.ERR_DELAY_TIME_REQ'),
                          'custome_message.required' => __('api.ERR_MESSAGE_REQ'),         
                        ]
                        ); 

        if ($validator->fails()) {           
          $errors[] = $validator->errors();  
        }else{

            $status = true;
            $collection                   = new $this->AppointmentDelayReportModel;
            $collection->patient_id       = $request->patient_id;
            $collection->appointment_id   = $request->appointment_id;
            $collection->delay_time       = $request->delay_time;
            $collection->custome_message  = $request->custome_message;
               
            //Save data
            $collection->save(); 

            // Send patient data for send detail in email 
            $collection->patientData = $patientData; 
            // dd($collection);
            $newData = [];
            $newData['patient_id'] = $collection->patient_id;
            $newData['appointment_id'] = $collection->appointment_id;
            $newData['delay_time'] = $collection->delay_time;
            $newData['custome_message'] = $collection->custome_message;

            // dd($newData);

            // Send Email
            
                $result = Mail::to($ordinationEmail->setting_value)->send(new AppointmentReportDelayMail($collection));
                $status     = true; 
                $data[]     = $collection; 
                $message    = __('api.DATA_INSERTED');

                //Log::info(' has created appointment by AppointmentAgreementController line no 434 :');
                self::_createLog('createAppointmentDelayReport',$collection->id,'info'); 
                $this->ActivityLogModel->addApiLog('Create Appointment Delay Report','has created appointment delay report','Create',null,$newData);
            }
        }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = $e->getMessage();
                self::_createLog('createAppointmentDelayReport',$errors,'error');  
                // $this->ActivityLogModel->addApiLog('Create Appointment Delay Report','has created appointment delay report','Create');  
        }
        
        return self::_sendResult($message,$data,$errors,$status);
    } 

    public function createWaitingNumber(Request $request) 
    {
      //  Log::info('in createWaitingNumber....');

        $errors    = []; 
        $data      = [];
        $message   = __('api.ERR_INVALID_DATA'); 
        $status    = false;

        $inputdata = $request->all();
        // dd($request->all());
        $validator = Validator::make($inputdata,[
                          'patient_id'   => 'required',
                          // 'patient_lat'  => 'required',
                          // 'patient_lon'  => 'required',
                        ],  
                        [
                          'patient_id.required'=> __('api.ERR_FINDINGS_PATIENT_ID_REQ'),
                          // 'patient_lat.required'=> __('api.ERR_LAT_REQ'),
                          // 'patient_lon.required'=> __('api.ERR_LON_REQ'),
                        ]
                        ); 

        if ($validator->fails()) {           
          $errors[] = $validator->errors(); 
        }else{

            $status = true;
            // Request Parameters
            $patientId   = $request->patient_id; 
            // $patientLat  = $request->patient_lat; 
            // $patientLon  = $request->patient_lon;
            $offset     = $request->offset;
            $limit      = $request->limit;
            $today_date  = $request->today_date;

            if(empty($today_date)){
                $today_date =  date('Y-m-d H:i:s', strtotime(now()));
            }else{
                $today_date =  date('Y-m-d', strtotime($today_date))." ".date("H:i");
            }
          //  echo $today_date;
            // exit;
            // if(!empty($today_date)){ 
            //     $today_date =  date('Y-m-d H:i:s', strtotime(now()));
            // }
            
            try{
                // Check Appointment of patient
                $getKeys = ['HOSPITAL_WAITING_MINUTES'];
                $getSettingData = $this->SettingsModel
                                    ->whereIn('setting_key', $getKeys)
                                    ->whereStatus(1)
                                    ->get();
                // dd($getSettingData);
                $settingData = [];                            
                foreach ($getSettingData as $key => $value) {
                   $settingData[$value->setting_key] = $value->setting_value;
                }
                // print_r($settingData);
                // exit();
                //DB::connection()->enableQueryLog();
                // $collection = $this->BaseModel
                                    // ->with(['assignedPatient','assignedDoctor','assignedAppointmentType'])
                                    // ->where('patient_id', $patientId)
                                    // ->whereStatus(1); 
                $collection = $this->BaseModel
                                    ->join('users','appointment.doctor_id','=','users.id')
                                    ->join('patients','appointment.patient_id','=','patients.id')
                                    ->join('appointment_types','appointment.appointment_type_id','=','appointment_types.id')
                                    ->whereRaw('TIMESTAMPDIFF(MINUTE,CURRENT_TIMESTAMP,start_date) BETWEEN 0 AND '.$settingData['HOSPITAL_WAITING_MINUTES'])
                                    ->where('patient_id', $patientId)
                                    ->where('appointment.status',1)
                                    ->where('start_date','>=',$today_date)
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
                                    // ->skip($offset)
                                    // ->take($limit)
                                    ->orderBy('start_date', 'ASC')
                                    ->get();
               // dd($today_date,$collection); 

                Log::info($collection);

                $totalRec = $collection->count();
                if($totalRec>0)
                { 
                    $appointment_id = $collection[0]->id;
                    $doctorName = $collection[0]->doctor_first_name." ".$collection[0]->doctor_last_name; 

                    $patientQueue = $this->AppointmentHasQueueNumberModel
                                        ->join('waiting_number_symbols','appointment_has_queue_number.symbol_id','=','waiting_number_symbols.id')
                                        ->where('patient_id',$patientId)
                                        ->where('appointment_id',$appointment_id)
                                        ->first(); 

                     //  dump($patientQueue);   

                    //  Log::info('patientQueue....');   
                     // Log::info($patientQueue);                   


                    if(!empty($patientQueue))
                    {
                       //  Log::info('in patientQueue....');   
                        //  dump('innnnnnn');  
                        $url = $patientQueue->url;
                        $strName = $patientQueue->name;
                        // $strNameWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $strName);
                        // $message = 'Welcome to your appointment with '.$doctorName.'. Please take a seat in the waiting area. You are called via the app and on the screen. Your number is '.$strNameWithoutExt;
                        $message = 'Willkommen bei Ihrem Termin mit '.$doctorName.'. Nehmen Sie bitte im Wartebereich Platz. Sie werden über die App und den Bildschirm im Wartebereich aufgerufen.';

                        $data[0]               = $collection[0]; 
                        $data[0]['url']         = $url;
                        $data[0]['symbol_name'] = $strName;
                        self::_createLog('createWaitingNumber',$data,'info');
                        $this->ActivityLogModel->addApiLog('Create Waiting Number','has created waiting number','Create',null,$data);
                    }
                    else
                    {

                        // Log::info('else patientQueue....');   
                        // dump('else');  
                        $queue_date = date('Y-m-d');
                        $symbolId = $this->AppointmentHasQueueNumberModel
                                    ->where('date',$queue_date)
                                    ->pluck('symbol_id');
                                    // dd($symbolId);
                        // $symbolId = $patientQueue->symbol_id;


                              // Log::info('queue_date...');                              
                              //  Log::info($queue_date);   

                              //   Log::info('symbolId...');                              
                              //  Log::info($symbolId);        

                        $waitingSymbol = $this->WaitingNumberSymbolsModel
                                                ->whereNotIn('id', $symbolId)
                                                ->first();

                             // Log::info('waitingSymbol...');                              
                             //   Log::info($waitingSymbol);                          

                        // dd($waitingSymbol);
                        // dd($waitingSymbol->name);
                        $strName = $waitingSymbol->name;
                        // $strNameWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $strName);
                        // dd($strNameWithoutExt);
                        $url = $waitingSymbol->url;
                        $id = $waitingSymbol->id;
                        // $queueNumber = $url."\'".$name;
                        // $queueNumber = $url.'\''.$name;
                        // dd($queueNumber);



                         // Log::info('waitingSymbol..name.');                              
                         //       Log::info($strName);   



                        $AppointmentHasQueueNumberModel = new $this->AppointmentHasQueueNumberModel;
                        $AppointmentHasQueueNumberModel->patient_id     = $patientId;
                        $AppointmentHasQueueNumberModel->appointment_id = $appointment_id;
                        $AppointmentHasQueueNumberModel->symbol_id = $id;
                        $AppointmentHasQueueNumberModel->date         = $queue_date;
                        $AppointmentHasQueueNumberModel->queue_number = $strName;
                        $AppointmentHasQueueNumberModel->queue_number_type = 0;
                        $AppointmentHasQueueNumberModel->status       = 1;
                        if($AppointmentHasQueueNumberModel->save())
                        {
                            //Push Notification of waiting number to patient need to send immediately
                           /* $AppointmentHasNotificationModel = new $this->AppointmentHasNotificationModel;
                            $AppointmentHasNotificationModel->patient_id     = $patientId;
                            $AppointmentHasNotificationModel->appointment_id = $appointment_id;
                            $AppointmentHasNotificationModel->notify_time    = $queue_date;
                            $AppointmentHasNotificationModel->status         = 0;*/

                            // $collection = $queueNumber;
                            $data[0]     = $collection[0];
                            $data[0]['url'] = $url;
                            $data[0]['symbol_name'] = $strName; 
                            // $message = 'Welcome to your appointment with '.$doctorName.'. Please take a seat in the waiting area. You are called via the app and on the screen. Your number is '.$strNameWithoutExt;
                            $message = 'Willkommen bei Ihrem Termin mit '.$doctorName.'. Nehmen Sie bitte im Wartebereich Platz. Sie werden über die App und den Bildschirm im Wartebereich aufgerufen.';
                            self::_createLog('createWaitingNumber',$data,'info');
                           $this->ActivityLogModel->addApiLog('Create Waiting Number','has created waiting number','Create',null,$data);
                        //Send Push notification 04-04-22===
                            $doctor_speciality = $collection[0]->doctor_speciality ?? '';
                            $appointment_date_time   = $collection[0]->date ?? '';
                            $appointment_type_id = $collection[0]->appointment_type_id ?? '';
                            $appointment_type   = $collection[0]->aname ?? '';
                            $patient_name=$collection[0]->patient_first_name." ".$collection[0]->patient_last_name; 
                            $doctor_image = asset('assets/admin/images/default-image.png');
                            if (!empty($collection[0]->img_path) && is_file(storage_path().'/app/'.$collection[0]->img_path)) 
                            {
                                $doctor_image = url('/storage/app/'.$collection[0]->img_path); 
                            }
                            $appointment_time = '';
                            if(!empty($appointment_date_time))
                            {
                                $appointment_time = date('d.F',strtotime($queue_date)).",um ".date('H:i',strtotime($queue_date))." Uhr.";
                            }
                            $title = 'Erinnerung an Ihren Termin';
                            $mobileId = DB::table('patient_has_device')
                                                ->where('patient_id',$patientId)
                                                ->where('deleted_at','=' , null)
                                                ->get(['device_id']);
                            // $content = 'Hallo '.$patient_name.', Wating symbol : '.$strName; //commented on 20-dec-23

                              $content = 'Hallo '.$patient_name.', Ihr Wartenummern-Symbol : '.$strName; //added on 20-dec-23                   
                              
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
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                                        'Authorization: Basic '.$restAPIKey.''));
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                                curl_setopt($ch, CURLOPT_POST, TRUE);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                //$data = curl_exec($ch);
                                curl_exec($ch);
                                curl_close($ch);
                                log::info('Appointment-Notify - Done');
                            } 
                            //End 04-04-22==========================


                        }
                    }

                    // Add new flag

                    $new_patient_flag = $this->PatientsModel->find($patientId);
                    
                   if($new_patient_flag->patient_status_flag =='0' && $new_patient_flag->new_flag != '1')
                    {
                        $this->PatientsModel->where('id',$patientId)
                                        ->update([
                                          'new_flag'=>'1'
                                        ]);
                    }
                    //======================================================
                    //=======Update Appointment status================
                    $updateAppStatus = $this->BaseModel->find($appointment_id);
                    $updateAppStatus->appointment_status = 'Aktuell';
                    $updateAppStatus->save();
                    //=======END Update Appointment status==========

                }
                else
                {
                    $status  = false;
                    $message = __('api.APPOINTMENT_NOT_FOUND');
                } 
            }
            catch(\Exception $e) {
                $errors[] = $e->getMessage();
                self::_createLog('createWaitingNumber',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }   
        }//else close                       

        return self::_sendResult($message,$data,$errors,$status);
       
    }

    public function kisokCreateWaitingNumber(Request $request) 
    {
        // dd('test');
        $errors    = []; 
        $data      = [];
        $message   = __('api.ERR_INVALID_DATA'); 
        $status    = false;

        $inputdata = $request->all();
        // dd($inputdata);

            $status = true;
            // Request Parameters
            $offset     = $request->offset;
            $limit      = $request->limit;
            $today_date  = $request->today_date;
            
            $symbol_name     = $request->symbol;

            if(empty($today_date)){
                $today_date =  date('Y-m-d H:i:s', strtotime(now()));
            }else{
                $today_date =  date('Y-m-d', strtotime($today_date))." ".date("H:i");
            }

            try{
                
                //DB::connection()->enableQueryLog();
               
                $queue_date = date('Y-m-d');
                $symbolId = $this->AppointmentHasQueueNumberModel
                            ->where('date',$queue_date)
                            ->pluck('symbol_id');
                // $symbolId = $patientQueue->symbol_id;

                $waitingSymbol = $this->WaitingNumberSymbolsModel
                                        ->whereNotIn('id', $symbolId)
                                        ->where('name','LIKE','%'.$symbol_name.'%')
                                        ->first();

                // dd($waitingSymbol);
                if(!empty($waitingSymbol) && !empty($symbol_name)){
                    // dd($waitingSymbol->name);
                    $strName = $waitingSymbol->name;
                    // $strNameWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $strName);
                    // dd($strNameWithoutExt);
                    $url = $waitingSymbol->url;
                    $id = $waitingSymbol->id;
                    // $queueNumber = $url."\'".$name;
                    // $queueNumber = $url.'\''.$name;
                    // dd($queueNumber);

                    $AppointmentHasQueueNumberModel = new $this->AppointmentHasQueueNumberModel;
                    $AppointmentHasQueueNumberModel->patient_id     = 0;
                    $AppointmentHasQueueNumberModel->appointment_id = 0;
                    $AppointmentHasQueueNumberModel->symbol_id      = $id;
                    $AppointmentHasQueueNumberModel->date           = $queue_date;
                    $AppointmentHasQueueNumberModel->queue_number   = $strName;
                    $AppointmentHasQueueNumberModel->queue_number_type = 1;
                    $AppointmentHasQueueNumberModel->status         = 1;
                    if($AppointmentHasQueueNumberModel->save())
                    {

                        // $collection = $queueNumber;
                        $data[0]['url'] = $url;
                        $data[0]['symbol_name'] = $strName; 
                        //$message = 'Thank you very much, we have registered you. Please take a seat in the waiting area. You will be called up via the fruit symbol on the waiting room monitor';
                        $message = 'Vielen Dank, wir haben Sie registriert. Bitte nehmen Sie im Wartebereich Platz. Sie werden über das Symbol auf Ihrem Würfel am Wartezimmermonitor aufgerufen. Den Würfel geben Sie bitte bei einer Assistentin ab sobald Sie aufgerufen wurden.';
                        
                        //self::_createLog('createWaitingNumber',$data,'info');
                      // $this->ActivityLogModel->addApiLog('Create Waiting Number','has created waiting number','Create',null,$data);

                    }
                }else if(!empty($symbol_name)){

                    $waitingSymbol = $this->WaitingNumberSymbolsModel
                                        ->where('name','LIKE','%'.$symbol_name.'%')
                                        ->first();

                    $strName = $waitingSymbol->name;
                    // $strNameWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $strName);
                    // dd($strNameWithoutExt);
                    $url = $waitingSymbol->url;
                    $id = $waitingSymbol->id;
                    // $queueNumber = $url."\'".$name;
                    // $queueNumber = $url.'\''.$name;
                    // dd($queueNumber);

                    $AppointmentHasQueueNumberModel = new $this->AppointmentHasQueueNumberModel;
                    $AppointmentHasQueueNumberModel->patient_id     = 0;
                    $AppointmentHasQueueNumberModel->appointment_id = 0;
                    $AppointmentHasQueueNumberModel->symbol_id      = $id;
                    $AppointmentHasQueueNumberModel->date           = $queue_date;
                    $AppointmentHasQueueNumberModel->queue_number   = $strName;
                    $AppointmentHasQueueNumberModel->queue_number_type = 1;
                    $AppointmentHasQueueNumberModel->status         = 1;
                    if($AppointmentHasQueueNumberModel->save())
                    {

                        // $collection = $queueNumber;
                        $data[0]['url'] = $url;
                        $data[0]['symbol_name'] = $strName; 
                        //$message = 'Thank you very much, we have registered you. Please take a seat in the waiting area. You will be called up via the fruit symbol on the waiting room monitor';
                        $message = 'Vielen Dank, wir haben Sie registriert. Bitte nehmen Sie im Wartebereich Platz. Sie werden über das Symbol auf Ihrem Würfel am Wartezimmermonitor aufgerufen. Den Würfel geben Sie bitte bei einer Assistentin ab sobald Sie aufgerufen wurden.';
                        
                        //self::_createLog('createWaitingNumber',$data,'info');
                      // $this->ActivityLogModel->addApiLog('Create Waiting Number','has created waiting number','Create',null,$data);

                    }

                }else{
                    $errors[] = 'Ungultiger Symbolname:'.$symbol_name;
                }
               
                

                
            }
            catch(\Exception $e) {
                $errors[] = $e->getMessage();
                self::_createLog('kioskcreateWaitingNumber',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }   
       // }//else close                       

        return self::_sendResult($message,$data,$errors,$status);
       
    }

    public function getAppointmentFilterByDate(Request $request){
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        $patientId  = $request->patient_id;
        $start = date('Y-m-d', strtotime($request->start_date));
        $end = date('Y-m-d', strtotime($request->end_date));

        $inputdata  = $request->all(); 
        try{
            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              'start_date'  => 'required',
                              'end_date'    => 'required',
                            ], 
                          [
                              'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),  
                              'start_date.required'    => __('api.APPOINTMENT_START_DATE_REQ'),
                              'end_date.required'    => __('api.APPOINTMENT_END_DATE_REQ'),   
                            ]
                            ); 

            if ($validator->fails()) 
            {           
              $errors[] = $validator->errors(); 
            }else
            {
                // dd($start);
                $collections = collect([]);
                if(!empty($start)){
                    $today_date =  date("Y-m-d");
                    // dd($today_date);
                    if($start == $today_date){
                        $start = date('Y-m-d H:i:s', strtotime(now()));
                    }   
                } 
                // dd($start);
                $collections = $this->BaseModel
                                    ->with(['assignedPatient','assignedDoctor','assignedAppointmentType',
                                        'hasExaminations'=>function($q){
                                            $q->with(['assignedExamination']);
                                    }])
                                    ->whereDate('end_date','<=',$end)
                                    ->where('start_date','>=',$start)
                                    ->where('patient_id', $patientId)
                                    ->whereStatus(1)
                                    ->get(); 

                 if(!empty($collections) && ($collections->count() > 0)){
                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data  = [];
                        foreach ($collections as $key => $collection){
                            $data[$key]['id']  = $collection->id;
                            $data[$key]['start_date']  = $collection->start_date;
                            $data[$key]['end_date']  = $collection->end_date;
                            $data[$key]['patient_id']  = $collection->patient_id;
                            $data[$key]['doctor_id']  = $collection->doctor_id;
                            $data[$key]['appointment_type_id']  = $collection->appointment_type_id;
                            $data[$key]['appointment_type_name']  = $collection->assignedAppointmentType->name;
                            $data[$key]['patient_name']  = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                            $data[$key]['doctor_name']  = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                            $data[$key]['doctor_speciality']  = $collection->assignedDoctor->doctor_speciality; 

                            $profileImage = asset('assets/admin/images/default-image.png');

                            $assignedDoctor_img_path = self::StorePath($collection->assignedDoctor->img_path.'/');
                            
                            // if (!empty($collection->assignedDoctor->img_path) && is_file(storage_path().'/app/'.$collection->assignedDoctor->img_path)) 
                            if (!empty($collection->assignedDoctor->img_path))
                            {
                                $profileImage = self::getFilePath($collection->assignedDoctor->img_path);
                                //$profileImage = url('/storage/app/'.$collection->assignedDoctor->img_path); 
                            }

                            $data[$key]['doctor_image']  = $profileImage;

                            $data[$key]['exams']  = [];
                            if(!empty($collection->hasExaminations) && sizeof($collection->hasExaminations)>0){
                                foreach ($collection->hasExaminations as  $haskey=>$hasExamination) {
                                    $data[$key]['exams'][$haskey]['id'] = $hasExamination->assignedExamination->id;
                                    $data[$key]['exams'][$haskey]['name'] = $hasExamination->assignedExamination->name;
                                    $data[$key]['exams'][$haskey]['url'] = $hasExamination->assignedExamination->url;
                                }

                            }
                        }
                        self::_createLog('getAppointmentFilterByDate',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('Get Appointment Filter By Date','Filter appointment by date','Get',null,$data);

                    }else{
                        $message  = __('api.ERR_NOT_FOUND');
                        $errors[] = [
                              "error" => __('api.DATA_NOT_FOUND'),
                          ];
                        self::_createLog('getAppointmentFilterByDate',$errors,'error');
                        // $this->ActivityLogModel->addApiLog('Get Appointment','send otp for login','Get');
                    }
                }
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
                self::_createLog('getAppointmentFilterByDate',$errors,'error');
                // $this->ActivityLogModel->addApiLog('Get Appointment','send otp for login','Get');
            }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getAppointmentFilterByType(Request $request)
    {
        $errors     = [];   
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        $patientId  = $request->patient_id;
        $appointmentTypentId  = $request->appointment_type_id;

        $inputdata  = $request->all();
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'      => 'required',
                              'appointment_type_id'=> 'required'
                            ], 
                            [
                              'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),
                              'appointment_type_id.required'=>__('api.APPOINTMENT_TYPE_ID_REQ'),     
                            ]
                            ); 

            if ($validator->fails()) 
            {           
              $errors[] = $validator->errors(); 
            }else
            {

                $collection = collect([]); 
                $collection = $this->BaseModel
                                    ->with(['assignedPatient','assignedDoctor','assignedAppointmentType'])
                                    ->where('patient_id', $patientId)
                                    ->where('appointment_type_id', $appointmentTypentId)
                                    ->whereStatus(1)
                                    ->get();

                 if(!empty($collection) && ($collection->count() > 0)){
                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data  = $collection;
                        self::_createLog('getAppointmentFilterByType',array($data),'info');
                         // $this->ActivityLogModel->addApiLog('Get Appointment Filter By Type','Filter appointment by type','Get',null,$data);

                    }else{
                        $message  = __('api.ERR_NOT_FOUND');
                        $errors[] = [
                              "error" => __('api.DATA_NOT_FOUND'),
                          ];
                        self::_createLog('getAppointmentFilterByType',$errors,'error');
                         // $this->ActivityLogModel->addApiLog('Get Appointment Filter By Date','Filter appointment by date','Get');
                    }
                }
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
                self::_createLog('getAppointmentFilterByType',$errors,'error');
                 // $this->ActivityLogModel->addApiLog('Get Appointment Filter By Date','Filter appointment by date','Get');
            }
       return self::_sendResult($message,$data,$errors,$status);
    }

    /*-------------------------------------------------------------
    |   Get Doctor Time Slots
    -----------------------------------------------------------------------*/
    public function getDoctorTimeSlots(Request $request){

        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');   
        $status     = false;
        
        $patient_id  = $request->patient_id;
        $doctor_id  = $request->doctor_id;
        $appointment_type_id  = $request->appointment_type_id;
        $appointment_date   = date("Y-m-d",strtotime($request->appointment_date));
        $day_of_week = date('N',strtotime($appointment_date));

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'patient_id'          => 'required',
                          'doctor_id'           => 'required',
                          //'appointment_type_id' => 'required',
                          'appointment_date'    => 'required|date_format:Y-m-d|after_or_equal:today',
                        ], 
                        [
                          'patient_id.required'     => __('api.ERR_APP_PATIENT_ID_REQ'),
                          'doctor_id.required'      => __('api.ERR_APP_DOCTOR_ID_REQ'),
                          //'appointment_type_id.required' =>__('api.ERR_APP_TYPE_ID_REQ'),     
                          'appointment_date.required'    => __('api.ERR_APP_DATE_REQ'),
                          'appointment_date.date_format'    => __('api.ERR_APP_DATE_FORMAT_REQ'),
                          'appointment_date.after_or_equal'    => __('api.ERR_APP_VALID_DATE_REQ'),
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 
        }else
        {

        try{
            $collection = collect([]); 

             $setting = $this->SettingsModel
                        ->where('id',12)
                        ->first(['setting_key','setting_value']);
            // dd($settings);
            if(!empty($setting)){
                $default_time_duration = $setting['setting_value'];                         
            }else{
                $default_time_duration = 10;                         
            } 

            $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id);
            $appointmentDuration = 0;
            if(!empty($appointmentType)){
                $appointmentDuration = $appointmentType->duration * 60;//convert min into sec
            }  

            //get the booked appointment time frames of the doctors
            $doctor_appointment_time_frames = $this->BaseModel
                ->where('doctor_id',$doctor_id)
                //->where('appointment_type_id',$appointment_type_id)
                ->whereDate('start_date',$appointment_date)
                ->whereStatus(1)
                ->select(DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date,(DATE_FORMAT(end_date,'%H:%i')) as end_date"))
                ->get();//1=>Confirmed


            $time_frames = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id')
                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                ->whereDate('roster_has_dates.date',$appointment_date)
                                ->where('roster_has_weeks_has_time_frames.week_day_id',$day_of_week)
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                                ->where('roster_has_weeks_has_time_frames.time_frame_flag','0')
                                // ->where('roster_has_weeks_has_time_frames.start_date','<=',$appointment_date)
                                // ->where('roster_has_weeks_has_time_frames.end_date','>=',$appointment_date)
                                ->groupBy('roster_has_weeks_has_time_frames.time_frame')
                                ->get(['roster_has_weeks_has_time_frames.time_frame','roster_has_weeks_has_time_frames.id as r_id']);
            // echo $day_of_week;
            // dd($$doctor_appointment_time_frames->toArray());
            //  exit();
            
            $response = [];
            $response['morning'] = [];
            $response['evening'] = [];
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
                foreach($time_frames as $time_frame){ 
                    
                    $response['duration'] = $default_time_duration;  

                    $time = date("H:i",strtotime($time_frame->time_frame)); 
                    $added_time_frame =  date("H:i",strtotime($time) + $appointmentDuration); 
                    $selected="";            

                    if(!empty($doctor_appointment_time_frames)){

                        foreach ($doctor_appointment_time_frames as $doctor_appointment_time_frame) {

                            if(strtotime($time) < strtotime($doctor_appointment_time_frame->start_date) && strtotime($added_time_frame) > strtotime($doctor_appointment_time_frame->end_date) ){
                                    //case for 9:20-9:50 from booked 9:30-9:45
                                    $ignore_time_slots[] = $time;
                            }
                            // || ($time>=$doctor_appointment_time_frame->start_date && $time<=$doctor_appointment_time_frame->end_date)
                            if($time==$doctor_appointment_time_frame->start_date || ($added_time_frame>$doctor_appointment_time_frame->start_date && $added_time_frame<=$doctor_appointment_time_frame->end_date)){
                                //case for begin date, inbetween, overide after add
                                $ignore_time_slots[] = $time;
                            }

                             if(($time>=$doctor_appointment_time_frame->start_date && $time<$doctor_appointment_time_frame->end_date)){
                                $ignore_time_slots[] = $time;   
                            }

                            
                        }
                    }
                    
                    //ignore all the doctors booked appointments from roster time frames
                    if(!in_array($time, $ignore_time_slots)) {
                        if(strtotime($today_date)==strtotime($appointment_date) && ($time>=$current_time))
                        {

                           /*  $current_date_time = date('Y-m-d H:i',strtotime($time));
                            $end_time = date("H:i",strtotime("+".(int)$time_frame->duration." minutes", strtotime($current_date_time)));
                            $response['timeslots'][] = $time ." - ".$end_time;*/

                            if(($time<=$morning_time) ){
                                //morning slots before 12pm
                                $response['morning'][] = $time;
                            }else{
                                //evening slots after 12pm
                                $response['evening'][] = $time;
                            }

                        }elseif(strtotime($today_date)!==strtotime($appointment_date)){
                            
                            /* $current_date_time = date('Y-m-d H:i',strtotime($time));
                            $end_time = date("H:i",strtotime("+".(int)$time_frame->duration." minutes", strtotime($current_date_time)));
                            $response['timeslots'][] = $time ." - ".$end_time;*/

                            if(($time<=$morning_time) ){
                                //morning slots before 12pm
                                $response['morning'][] = $time;
                            }else{
                                //evening slots after 12pm
                                $response['evening'][] = $time;
                            }

                        }

                    }
                }

            }

            $compare_function = function($a,$b) {
 
                $a_timestamp = strtotime($a); // convert a (string) date/time to a (int) timestamp
                $b_timestamp = strtotime($b);
         
                // new feature in php 7
                return $a_timestamp <=> $b_timestamp;
            };

            /*$sort_data = ["11:20","11:30","11:40","11:50","12:00","09:00","09:10","09:20","09:30","09:40","09:50","10:00","10:10","10:20","10:30","10:40","10:50"];
            print_r($sort_data);
            usort($sort_data, $compare_function);
            print_r($sort_data);*/
            if(count($response['morning'])>0){
              usort($response['morning'], $compare_function);
            }
            if(count($response['evening'])>0){
              usort($response['evening'], $compare_function);
            }
            if(!empty($response) && sizeof($response)>0){
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $response;
                    self::_createLog('getDoctorTimeSlots',$data,'info');
                    // $this->ActivityLogModel->addApiLog(', Get Doctor TimeSlots','Get doctors available time slots','Get');

            }else{
                $message  = __('api.ERR_NOT_FOUND');
                $errors[] = [
                      "error" => __('api.DATA_NOT_FOUND'),
                  ];
                self::_createLog('getDoctorTimeSlots',$errors,'error');
                // $this->ActivityLogModel->addApiLog('getDoctorTimeSlots','send otp for login','Get');
            }
                
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
                self::_createLog('getDoctorTimeSlots',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }   
        }
       return self::_sendResult($message,$data,$errors,$status);
    }


    public function getDoctorTimeSlotsNew(Request $request){

        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');   
        $status     = false;
        
        $patient_id  = $request->patient_id;
        $doctor_id  = $request->doctor_id;
        $appointment_type_id  = $request->appointment_type_id;
        $appointment_date   = date("Y-m-d",strtotime($request->appointment_date));
        $day_of_week = date('N',strtotime($appointment_date));

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'patient_id'          => 'required',
                          'doctor_id'           => 'required',
                          //'appointment_type_id' => 'required',
                          'appointment_date'    => 'required|date_format:Y-m-d|after_or_equal:today',
                        ], 
                        [
                          'patient_id.required'     => __('api.ERR_APP_PATIENT_ID_REQ'),
                          'doctor_id.required'      => __('api.ERR_APP_DOCTOR_ID_REQ'),
                          //'appointment_type_id.required' =>__('api.ERR_APP_TYPE_ID_REQ'),     
                          'appointment_date.required'    => __('api.ERR_APP_DATE_REQ'),
                          'appointment_date.date_format'    => __('api.ERR_APP_DATE_FORMAT_REQ'),
                          'appointment_date.after_or_equal'    => __('api.ERR_APP_VALID_DATE_REQ'),
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 
        }else
        {

        try{
            $collection = collect([]); 

             $setting = $this->SettingsModel
                        ->where('id',12)
                        ->first(['setting_key','setting_value']);
            // dd($settings);
            if(!empty($setting)){
                $default_time_duration = $setting['setting_value'];                         
            }else{
                $default_time_duration = 10;                         
            } 

            $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id);
            $appointmentDuration = 0;
            if(!empty($appointmentType)){
                $appointmentDuration = $appointmentType->duration * 60;//convert min into sec
            }  

            //get the booked appointment time frames of the doctors
            $doctor_appointment_time_frames = $this->BaseModel
                ->where('doctor_id',$doctor_id)
                //->where('appointment_type_id',$appointment_type_id)
                ->whereDate('start_date',$appointment_date)
                ->whereStatus(1)
                ->select(DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date,(DATE_FORMAT(end_date,'%H:%i')) as end_date"))
                ->get();//1=>Confirmed


            // $time_frames = $this->RosterModel
            //                     ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
            //                     ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id')
            //                     ->where('roster.doctor_id',$doctor_id)
            //                     ->where('roster_has_dates.is_excluded','=',0)
            //                     ->whereDate('roster_has_dates.date',$appointment_date)
            //                     ->where('roster_has_weeks_has_time_frames.week_day_id',$day_of_week)
            //                     ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
            //                     ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
            //                     ->groupBy('roster_has_weeks_has_time_frames.time_frame')
            //                     ->get(['roster_has_weeks_has_time_frames.time_frame','roster_has_weeks_has_time_frames.id as r_id']);

            $time_frames = $this->RosterModel
                                    ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                    ->join("roster_has_weeks_has_time_frames",function($join){
                                        $join->on("roster_has_weeks_has_time_frames.roster_id","=","roster_has_dates.roster_id")
                                            ->on("roster_has_weeks_has_time_frames.start_date","=","roster_has_dates.start_date")
                                            ->on("roster_has_weeks_has_time_frames.end_date","=","roster_has_dates.end_date");
                                    })
                                    ->where('roster.doctor_id',$doctor_id)
                                    ->where('roster_has_dates.is_excluded','=',0)
                                    ->whereDate('roster_has_dates.date',$appointment_date)
                                    ->where('roster_has_weeks_has_time_frames.week_day_id',$day_of_week)
                                    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                                    ->groupBy('roster_has_weeks_has_time_frames.time_frame')
                                    ->get(['roster_has_weeks_has_time_frames.time_frame','roster_has_weeks_has_time_frames.id as r_id']);
            $response = [];
            $response['morning'] = [];
            $response['morning_value'] = [];

            $new_response['morning_value'] = [];

            $response['evening'] = [];
            $response['evening_value'] = [];
            $new_response['evening_value'] = [];
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
                foreach($time_frames as $time_frame){ 
                    
                    $response['duration'] = $default_time_duration;  

                    $time = date("H:i",strtotime($time_frame->time_frame));
                    $time_value = $time_frame->r_id; 
                    $added_time_frame =  date("H:i",strtotime($time) + $appointmentDuration); 
                    $selected="";            

                    if(!empty($doctor_appointment_time_frames)){

                        foreach ($doctor_appointment_time_frames as $doctor_appointment_time_frame) {

                            if(strtotime($time) < strtotime($doctor_appointment_time_frame->start_date) && strtotime($added_time_frame) > strtotime($doctor_appointment_time_frame->end_date) ){
                                    //case for 9:20-9:50 from booked 9:30-9:45
                                    $ignore_time_slots[] = $time;
                            }
                            // || ($time>=$doctor_appointment_time_frame->start_date && $time<=$doctor_appointment_time_frame->end_date)
                            if($time==$doctor_appointment_time_frame->start_date || ($added_time_frame>$doctor_appointment_time_frame->start_date && $added_time_frame<=$doctor_appointment_time_frame->end_date)){
                                //case for begin date, inbetween, overide after add
                                $ignore_time_slots[] = $time;
                            }

                             if(($time>=$doctor_appointment_time_frame->start_date && $time<$doctor_appointment_time_frame->end_date)){
                                $ignore_time_slots[] = $time;   
                            }

                            
                        }
                    }
                    
                    //ignore all the doctors booked appointments from roster time frames
                    if(!in_array($time, $ignore_time_slots)) {
                        if(strtotime($today_date)==strtotime($appointment_date) && ($time>=$current_time))
                        {

                           /*  $current_date_time = date('Y-m-d H:i',strtotime($time));
                            $end_time = date("H:i",strtotime("+".(int)$time_frame->duration." minutes", strtotime($current_date_time)));
                            $response['timeslots'][] = $time ." - ".$end_time;*/

                            if(($time<=$morning_time) ){
                                //morning slots before 12pm
                                $response['morning'][] = $time;
                                $new_response['morning_value'][$time] = $time_value;
                            }else{
                                //evening slots after 12pm
                                $response['evening'][] = $time;
                                $new_response['evening_value'][$time] = $time_value;
                            }

                        }elseif(strtotime($today_date)!==strtotime($appointment_date)){
                            
                            /* $current_date_time = date('Y-m-d H:i',strtotime($time));
                            $end_time = date("H:i",strtotime("+".(int)$time_frame->duration." minutes", strtotime($current_date_time)));
                            $response['timeslots'][] = $time ." - ".$end_time;*/

                            if(($time<=$morning_time) ){
                                //morning slots before 12pm
                                $response['morning'][] = $time;
                                $new_response['morning_value'][$time] = $time_value;
                            }else{
                                //evening slots after 12pm
                                $response['evening'][] = $time;
                                $new_response['evening_value'][$time] = $time_value;
                            }

                        }

                    }
                }

            }
            //dump($response);
            $compare_function = function($a,$b) {
 
                $a_timestamp = strtotime($a); //convert a (string) date/time to a (int) timestamp
                $b_timestamp = strtotime($b);
         
                // new feature in php 7
                return $a_timestamp <=> $b_timestamp;
            };
            //dd($response,$compare_function);
            /*$sort_data = ["11:20","11:30","11:40","11:50","12:00","09:00","09:10","09:20","09:30","09:40","09:50","10:00","10:10","10:20","10:30","10:40","10:50"];
            print_r($sort_data);
            usort($sort_data, $compare_function);
            print_r($sort_data);*/
            if(count($response['morning'])>0){
              usort($response['morning'], $compare_function);
            }
            if(count($response['evening'])>0){
              usort($response['evening'], $compare_function);
            }
            //$new_morning = [];
            if(sizeof($new_response['morning_value'])>0)
            {
                $index = 0;
                foreach ($new_response['morning_value'] as $key => $mrg) 
                {
                  
                    if($key == $response['morning'][$index])
                    {
                        $response['morning_value'][] = $mrg;

                    }
                    $index++;
                }
            }
            if(sizeof($new_response['evening_value'])>0)
            {
                $evening_index = 0;
                foreach ($new_response['evening_value'] as $key => $mrg) 
                {
                  
                    if($key == $response['evening'][$evening_index])
                    {
                        $response['evening_value'][] = $mrg;

                    }
                    $evening_index++;
                }
            }
          
            if(!empty($response) && sizeof($response)>0){
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $response;
                    self::_createLog('getDoctorTimeSlots',$data,'info');
                    // $this->ActivityLogModel->addApiLog(', Get Doctor TimeSlots','Get doctors available time slots','Get');

            }else{
                $message  = __('api.ERR_NOT_FOUND');
                $errors[] = [
                      "error" => __('api.DATA_NOT_FOUND'),
                  ];
                self::_createLog('getDoctorTimeSlots',$errors,'error');
                // $this->ActivityLogModel->addApiLog('getDoctorTimeSlots','send otp for login','Get');
            }
                
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
                self::_createLog('getDoctorTimeSlots',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }   
        }
       return self::_sendResult($message,$data,$errors,$status);
    }
    
    /*-------------------------------------------------------------
    |   Book Appointment
    -----------------------------------------------------------------------*/
    public function bookAppointment(Request $request){
        
        
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        
        $patient_id     = $request->patient_id;
        $doctor_id      = $request->doctor_id;
        $appointment_type_id  = $request->appointment_type_id;
        $appointment_date     = $request->appointment_date;
        $time_frame     =  $request->time_frame;
        $exam_ids       =  $request->exam_ids;

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'patient_id'          => 'required',
                          'doctor_id'           => 'required',
                          'appointment_type_id' => 'required',
                          'appointment_date'    => 'required|date_format:Y-m-d|after_or_equal:today',
                          'time_frame'          => 'required',
                        ], 
                        [
                          'patient_id.required'     => __('api.ERR_APP_PATIENT_ID_REQ'),
                          'doctor_id.required'      => __('api.ERR_APP_DOCTOR_ID_REQ'),
                          'appointment_type_id.required' =>__('api.ERR_APP_TYPE_ID_REQ'),     
                          'appointment_date.required'    => __('api.ERR_APP_DATE_REQ'),
                          'appointment_date.date_format'    => __('api.ERR_APP_DATE_FORMAT_REQ'),
                          'appointment_date.after_or_equal'    => __('api.ERR_APP_VALID_DATE_REQ'),
                          'time_frame.required'    => __('api.ERR_APP_TIMEFRAME_REQ'),
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 

        }else
        {
             try {
                    //Check doctor time frame is available before booking appointment, if not available then throw error message
                    $check_time_frame = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                ->whereDate('roster_has_dates.date',Date('Y-m-d',strtotime($appointment_date)))
                                ->where('roster_has_weeks_has_time_frames.time_frame','=',$time_frame)
                                ->get(['roster_has_weeks_has_time_frames.time_frame']); 
                    // dd($check_time_frame);
                    if(!empty($check_time_frame) && sizeof($check_time_frame)>0){
                        //now time slotes are available , but the appointment is booked for it then throw error message
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
                            //->where('appointment_type_id',$appointment_type_id)
                            ->whereStatus(1)
                            //->where('appointment.start_date','=',$check_app_date)
                            ->where('appointment.start_date','>=',$check_app_date) 
                            ->where('appointment.end_date', '<=', $check_app_end_date)
                            ->get(['id']);
                        }    
                        if(!empty($check_doctor_booked_appointment) && sizeof($check_doctor_booked_appointment)>0){
                                $errors[] = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
                        }

                    }else{
                         $errors[] = 'Terminzeitfenster können nicht gebucht werden.';//Appointment time slots is not available for booking
                    }
  
                    if(empty($errors) && sizeof($errors)==0){
                        //Start Booking an Appointement

                        DB::beginTransaction(); 

                        $collection     = new $this->BaseModel;   
                        $request['start_date'] = date("Y-m-d H:i",strtotime($request->appointment_date." ".$time_frame));
                        
                        $request['end_date']  = self::_getEndDate($request['start_date'],$request['appointment_type_id']);
                        
                        $collection     = self::_storeAppointment($collection,$request);


                        // ================================================================

                        // ================================================================
                        //Log::info('Book Appointment AppointmentAgreementController line NO:1268');
                        self::_deactivateReminder($collection);
                        $all_transactions = [];
                        $notify_data = [];
                        $notes = '';
                        if ($collection) 
                        {
                            $all_transactions[] = 1;
                            
                            $patient_doc_data = [];
                            $patient_doc_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'exam_app_type_id'=> $appointment_type_id,
                                                        'record_type'   => 1,
                                                        'doc_status'   => 0,
                                                        );

                            if(!empty($exam_ids) && strlen($exam_ids)>0){

                                $exam_ids = explode(",", $exam_ids);
                                $exam_data = [];
                                
                                foreach ($exam_ids as $examId) {
                                    $exam_data[] = array(
                                                    'appointment_id'=> $collection->id,
                                                    'patient_id'    => $collection->patient_id,
                                                    'examination_id'=> $examId,
                                                    );

                                    $patient_doc_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'exam_app_type_id'=> $examId,
                                                        'record_type'   => 0,
                                                        'doc_status'   => 0,
                                                        );

                                    $isExist = $this->EventTypeHasExaminationsModel
                                              ->where('patient_id',$collection->patient_id)
                                              ->where('appoinment_id',$collection->id)
                                              ->where('service_id',$examId)
                                              ->first();
                                    if(empty($isExist))
                                    {
                                        $eventType = new $this->EventTypeHasExaminationsModel;
                                    } 
                                    else
                                    {
                                        $eventType = $this->EventTypeHasExaminationsModel->find($isExist->id);
                                    }

                                    $eventType->patient_id    = $collection->patient_id;
                                    $eventType->appoinment_id = $collection->id;
                                    $eventType->service_id    = $examId;
                                    $eventType->event_type    = 'smart_phone';
                                    $eventType->status        = 'booked';
                                    $eventType->save();  
                                    // =================================================
                                }

                                if($this->AppointmentHasExaminationsModel->insert($exam_data)){ 
                                    $all_transactions[] = 1;
                                }else{
                                    $all_transactions[] = 0;
                                }

                                

                                $exam_details = $this->ExaminationsModel
                                                    ->whereIn('id',$exam_ids)
                                                    ->get([
                                                        'examinations.id',
                                                        'examinations.name'
                                                        ]); 

                                $exam_names = array_column($exam_details->toArray(), "name");
                                $exam_names = implode(",", $exam_names);

                                if(strlen($exam_names)>0){
                                    //$notes = $exam_names." gebucht";
                                }
                            }
                            
                            // dd($patient_doc_data);

                            // if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                            //     $all_transactions[] = 1;
                            // }else{
                            //     $all_transactions[] = 0;
                            // }

                            $getDocument = self::_GetAssignedDocument($collection->id,$collection->appointment_type_id,$exam_ids,$collection->patient_id);
                            // END

                            //insert the entry for patient has Checklist
                            $getDocument = self::_GetAssignedCheckList($collection->id,$exam_ids,$collection->patient_id);
                            // END

                            $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType','hasExaminations'=>function($q){
                                            $q->with(['assignedExamination']);
                                        }])->find($collection->id);   
                                
                            $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                            $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                            $appointmentType = $collection->assignedAppointmentType->name;
                            
                            $appointmentTime = date('d.F',strtotime($request->start_date)).",um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            $patientText = $collection->assignedPatient->salutation ?? "";
                            $patientText .= " ".$collection->assignedPatient->family_name;
                            $doctorSurname = $collection->assignedDoctor->last_name;
                            //Appoinment Push Notification
                            $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;
                            
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

                            //Default appintment 
                            //$getServises = self::_appointmentTypesAgaintsServices($collection->id,$request->appointment_type_id,$request->patient_id);
                            // END 
                           
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
                            //dd(request()->all());
                            $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventStore(request());
                            //$postResponse = json_decode($postCalDetails->data);
                            // dd($postCalDetails);
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
                                DB::rollback();
                                $errors[] = $postCalDetails->original['msg'];
                            }
                           
                        }else{
                            $all_transactions[] = 0;
                        }

                        if (!in_array(0,$all_transactions)) 
                        {
                            DB::commit();
                            $status  = true;
                            $message = __('api.APPOINTMENT_BOOKED_SUCCESS');

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

                            $doctorImage = asset('assets/admin/images/default-image.png');
                            // if (!empty($collection->assignedDoctor->img_path) && is_file(storage_path().'/app/'.$collection->assignedDoctor->img_path))
                            $assignedDoctor_img_path = self::StorePath($collection->assignedDoctor->img_path.'/');

                            if (!empty($collection->assignedDoctor->img_path)) 
                            {
                                $doctorImage = self::getFilePath($collection->assignedDoctor->img_path);
                                //$doctorImage = url('/storage/app/'.$collection->assignedDoctor->img_path); 
                            }

                            $data[0]['doctor_image']       = $doctorImage;

                            $data[0]['exams']  = [];
                            if(!empty($collection->hasExaminations) && sizeof($collection->hasExaminations)>0){
                                foreach ($collection->hasExaminations as  $haskey=>$hasExamination) {
                                    $data[0]['exams'][$haskey]['id'] = $hasExamination->assignedExamination->id;
                                    $data[0]['exams'][$haskey]['name'] = $hasExamination->assignedExamination->name;
                                    $data[0]['exams'][$haskey]['url'] = $hasExamination->assignedExamination->url;
                                }

                            }
                            //$data[]  = $collection;
                            // Log::info('Book Appointment by AppointmentAgreementController');
                            $debug_arr['data'] = 'has created appointment by AppointmentController';    
                            $res_name = "AppointmentAgreementController_store";   
                            //dd($debug_arr);  
                            self::debugModeappBookFun($debug_arr,$res_name);  

                            self::_createLog('bookAppointment',$data,'info');
                            $this->ActivityLogModel->addApiLog('Book Appointment','has book appointment','Create',null,$data);
                        }

                    }   

                }
                catch(\Exception $e) {
                    DB::rollback();
                    $errors[] = $e->getMessage();
                    self::_createLog('bookAppointment',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
        }
        return self::_sendResult($message,$data,$errors,$status);

    }


    public function bookAppointmentNew(Request $request){
        

        //dd($request->all()); time_slot_id
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        
        $patient_id     = $request->patient_id;
        $doctor_id      = $request->doctor_id;
        $appointment_type_id  = $request->appointment_type_id;
        $appointment_date     = $request->appointment_date;
        $time_frame     =  $request->time_frame;
        $exam_ids       =  $request->exam_ids;

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'patient_id'          => 'required',
                          'doctor_id'           => 'required',
                          'appointment_type_id' => 'required',
                          'appointment_date'    => 'required|date_format:Y-m-d|after_or_equal:today',
                          'time_frame'          => 'required',
                        ], 
                        [
                          'patient_id.required'     => __('api.ERR_APP_PATIENT_ID_REQ'),
                          'doctor_id.required'      => __('api.ERR_APP_DOCTOR_ID_REQ'),
                          'appointment_type_id.required' =>__('api.ERR_APP_TYPE_ID_REQ'),     
                          'appointment_date.required'    => __('api.ERR_APP_DATE_REQ'),
                          'appointment_date.date_format'    => __('api.ERR_APP_DATE_FORMAT_REQ'),
                          'appointment_date.after_or_equal'    => __('api.ERR_APP_VALID_DATE_REQ'),
                          'time_frame.required'    => __('api.ERR_APP_TIMEFRAME_REQ'),
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 

        }else
        {
             try {
                    //Check doctor time frame is available before booking appointment, if not available then throw error message
                    $check_time_frame = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                ->whereDate('roster_has_dates.date',Date('Y-m-d',strtotime($appointment_date)))
                                ->where('roster_has_weeks_has_time_frames.time_frame','=',$time_frame)
                                ->get(['roster_has_weeks_has_time_frames.time_frame']); 
                    // dd($check_time_frame);
                    if(!empty($check_time_frame) && sizeof($check_time_frame)>0){
                        //now time slotes are available , but the appointment is booked for it then throw error message
                        $check_app_date = date("Y-m-d H:i:s",strtotime($request->appointment_date." ".$time_frame));
                        $check_app_end_date  = self::_getEndDate($check_app_date,$appointment_type_id);
                        $check_doctor_booked_appointment = $this->BaseModel
                            ->where('doctor_id',$doctor_id)
                            //->where('appointment_type_id',$appointment_type_id)
                            ->whereStatus(1)
                            ->where('appointment.start_date', '<=', $check_app_date)
                            ->where('appointment.end_date', '>=', $check_app_end_date)
                            //->where('appointment.start_date','=',$check_app_date)
                            ->get(['id']);

                        if(!empty($check_doctor_booked_appointment) && sizeof($check_doctor_booked_appointment)>0){
                                $errors[] = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
                        }

                    }else{
                         $errors[] = 'Terminzeitfenster können nicht gebucht werden.';//Appointment time slots is not available for booking
                    }
  
                    if(empty($errors) && sizeof($errors)==0){
                        //Start Booking an Appointement

                        DB::beginTransaction(); 

                        $collection     = new $this->BaseModel;   
                        $request['start_date'] = date("Y-m-d H:i",strtotime($request->appointment_date." ".$time_frame));
                        
                        $request['end_date']  = self::_getEndDate($request['start_date'],$request['appointment_type_id']);
                        
                        $collection     = self::_storeAppointment($collection,$request);


                        // ================================================================
                        if(isset($request->time_slot_id) && !empty($request->time_slot_id))
                        {
                            $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                                   ->where('id',$request->time_slot_id)
                                                   ->update([
                                                            'time_frame_flag'=>'2',
                                                            'time_frame_flag_date'=>Date('Y-m-d H:i:s'),
                                                            'comment'=>'AppointmentAgreementController(API) store booking function App Date :'.Date('Y-m-d H:i:s',strtotime($appointment_date)).' Current Date: '.Date('Y-m-d H:i:s').' Patient_id: '.$collection->patient_id
                                                            ]);
                        }
                        
                        // ================================================================
                        //Log::info('Book Appointment AppointmentAgreementController line NO:1268');
                        //self::_deactivateReminder($collection);
                        $all_transactions = [];
                        $notify_data = [];
                        $notes = '';
                        if ($collection) 
                        {
                            $all_transactions[] = 1;
                            self::_deactivateReminderNew($collection,$exam_ids);
                            $patient_doc_data = [];
                            $patient_doc_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'exam_app_type_id'=> $appointment_type_id,
                                                        'record_type'   => 1,
                                                        'doc_status'   => 0,
                                                        );
                            //Same name services are booked default===========
                            $getAppointmentTypeExam = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$collection->appointment_type_id)
                                ->get(['examination_id']);  
                            $examArray=array(); 
                            if(!empty($getAppointmentTypeExam)){
                                foreach($getAppointmentTypeExam as $appointment_exam){
                                    //$examArray[]=$appointment_exam->examination_id;
                                    //Added by swati 20-Oct-22===================
                                    $checkGeneralServcie=DB::table('preferred_channels_for_reminders_setting')
                                    ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                                    ->where('service_id',$appointment_exam->examination_id)
                                    ->get();
                                    if(!empty($checkGeneralServcie)){
                                        $today_date=date("Y-m-d");
                                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                                        ->where('service_id',$appointment_exam->examination_id)
                                                        ->where('patient_id',$collection->patient_id)
                                                        ->where('reminder_status','Set')
                                                        ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                                        ->first();
                                        if(empty($checkServiceReminders)){
                                            $examArray[]=$appointment_exam->examination_id;
                                        } 
                                    }
                                    else $examArray[]=$appointment_exam->examination_id;
                                    //End ===============================
                                }
                            }
                            //Added by swati 2-Nov-22===================
                            if(!empty($exam_ids) && strlen($exam_ids)>0){
                                $exam_ids = array_filter(explode(",", $exam_ids));
                                foreach ($exam_ids as $key => $value) {
                                    $checkGeneralServcie=DB::table('preferred_channels_for_reminders_setting')
                                    ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                                    ->where('service_id',$value)
                                    ->get();
                                    if(!empty($checkGeneralServcie)){
                                        $today_date=date("Y-m-d");
                                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                                        ->where('service_id',$value)
                                                        ->where('patient_id',$collection->patient_id)
                                                        ->where('reminder_status','Set')
                                                        ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                                        ->first();
                                        if(!empty($checkServiceReminders)){
                                            unset($exam_ids[$key]);
                                        } 
                                    }
                                }
                            }
                            //End ===============================
                            if(!empty($exam_ids)){
                                $exam_ids= array_filter(array_unique(array_merge($exam_ids,$examArray)));
                            }
                            else $exam_ids=$examArray;
                            // log::info("test");
                            // log::info($exam_ids);
                            
                            //Same name services end=========================
                            if(!empty($exam_ids) && count($exam_ids)>0){

                                //$exam_ids = explode(",", $exam_ids);
                                $exam_data = [];
                                
                                foreach ($exam_ids as $examId) {
                                    $exam_data[] = array(
                                                    'appointment_id'=> $collection->id,
                                                    'patient_id'    => $collection->patient_id,
                                                    'examination_id'=> $examId,
                                                    );

                                    $patient_doc_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'exam_app_type_id'=> $examId,
                                                        'record_type'   => 0,
                                                        'doc_status'   => 0,
                                                        );

                                    $isExist = $this->EventTypeHasExaminationsModel
                                              ->where('patient_id',$collection->patient_id)
                                              ->where('appoinment_id',$collection->id)
                                              ->where('service_id',$examId)
                                              ->first();
                                    if(empty($isExist))
                                    {
                                        $eventType = new $this->EventTypeHasExaminationsModel;
                                    } 
                                    else
                                    {
                                        $eventType = $this->EventTypeHasExaminationsModel->find($isExist->id);
                                    }

                                    $eventType->patient_id    = $collection->patient_id;
                                    $eventType->appoinment_id = $collection->id;
                                    $eventType->service_id    = $examId;
                                    $eventType->event_type    = 'smart_phone';
                                    $eventType->status        = 'booked';
                                    $eventType->save();  
                                    // =================================================
                                }

                                if($this->AppointmentHasExaminationsModel->insert($exam_data)){ 
                                    $all_transactions[] = 1;
                                }else{
                                    $all_transactions[] = 0;
                                }

                                

                                $exam_details = $this->ExaminationsModel
                                                    ->whereIn('id',$exam_ids)
                                                    ->get([
                                                        'examinations.id',
                                                        'examinations.name'
                                                        ]); 

                                $exam_names = array_column($exam_details->toArray(), "name");
                                $exam_names = implode(",", $exam_names);

                                if(strlen($exam_names)>0){
                                    //$notes = $exam_names." gebucht";
                                }
                            }
                            
                            // dd($patient_doc_data);

                            // if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                            //     $all_transactions[] = 1;
                            // }else{
                            //     $all_transactions[] = 0;
                            // }

                            $getDocument = self::_GetAssignedDocument($collection->id,$collection->appointment_type_id,$exam_ids,$collection->patient_id);
                            // END

                            //insert the entry for patient has Checklist
                            $getDocument = self::_GetAssignedCheckList($collection->id,$exam_ids,$collection->patient_id);
                            // END

                            $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType','hasExaminations'=>function($q){
                                            $q->with(['assignedExamination']);
                                        }])->find($collection->id);   
                                
                            $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                            $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                            $appointmentType = $collection->assignedAppointmentType->name;
                                

                             //commented below on 6-nov-23 for text changes    
                            // $appointmentTime = date('d.F',strtotime($request->start_date)).",um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            //change below text on 6-nov-23 
                            $appointmentTime = date('d.F',strtotime($request->start_date)).", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            //commented on 6-nov-23
                            // $patientText = $collection->assignedPatient->salutation ?? "";
                            // $patientText .= " ".$collection->assignedPatient->family_name;

                            $patientText = $collection->assignedPatient->salutation ? " ".$collection->assignedPatient->salutation.'.': ""; //changed on 6-nov-23 added dot after salutation on 14-dec-23
                            $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;         //added first name on 6-nov-23


                            $doctorSurname = $collection->assignedDoctor->last_name;

                            //Appoinment Push Notification
                            //commented on 6-nov-23
                            // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;
                            
                          
                            $notify_times = self::_getNotifyTime($request['start_date']);


                             //commented below code on 13-feb-24

                            /*$content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;  //changed content on 6-nov-23
                             
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
                            }*/


                            /******added code on 13-feb-24***for notification from setting section*****/

                            $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));

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

                            if($this->AppointmentHasNotificationModel->insert($notify_data))
                            {
                                $all_transactions[] = 1;
                            }
                            else 
                            {
                                $all_transactions[] = 0; 
                            }


                            /***********end code**of notification setting***13-feb-24*******************/




                            //Default appintment 
                            //$getServises = self::_appointmentTypesAgaintsServices($collection->id,$request->appointment_type_id,$request->patient_id);
                            // END 
                           
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
                            //dd(request()->all());
                            $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventStore(request());
                            //$postResponse = json_decode($postCalDetails->data);
                            // dd($postCalDetails);
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
                                DB::rollback();
                                $errors[] = $postCalDetails->original['msg'];
                            }
                           
                        }else{
                            $all_transactions[] = 0;
                        }

                        if (!in_array(0,$all_transactions)) 
                        {
                            DB::commit();
                            $status  = true;
                            $message = __('api.APPOINTMENT_BOOKED_SUCCESS');

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

                            $doctorImage = asset('assets/admin/images/default-image.png');
                            // if (!empty($collection->assignedDoctor->img_path) && is_file(storage_path().'/app/'.$collection->assignedDoctor->img_path))
                            $assignedDoctor_img_path = self::StorePath($collection->assignedDoctor->img_path.'/');

                            if (!empty($collection->assignedDoctor->img_path)) 
                            {
                                $doctorImage = self::getFilePath($collection->assignedDoctor->img_path);
                                //$doctorImage = url('/storage/app/'.$collection->assignedDoctor->img_path); 
                            }

                            $data[0]['doctor_image']       = $doctorImage;

                            $data[0]['exams']  = [];
                            if(!empty($collection->hasExaminations) && sizeof($collection->hasExaminations)>0){
                                foreach ($collection->hasExaminations as  $haskey=>$hasExamination) {
                                    if(isset($hasExamination->assignedExamination->id)){
                                        $data[0]['exams'][$haskey]['id'] = $hasExamination->assignedExamination->id;
                                        $data[0]['exams'][$haskey]['name'] = $hasExamination->assignedExamination->name;
                                        $data[0]['exams'][$haskey]['url'] = $hasExamination->assignedExamination->url;
                                    }
                                }

                            }
                            //$data[]  = $collection;
                            // Log::info('Book Appointment by AppointmentAgreementController');
                            $debug_arr['data'] = 'has created appointment by AppointmentController';    
                            $res_name = "AppointmentAgreementController_store";   
                            //dd($debug_arr);  
                            self::debugModeappBookFun($debug_arr,$res_name);  

                            self::_createLog('bookAppointment',$data,'info');
                            $this->ActivityLogModel->addApiLog('Book Appointment','has book appointment','Create',null,$data);
                        }

                    }   

                }
                catch(\Exception $e) {
                    DB::rollback();
                    $errors[] = $e->getMessage();
                    self::_createLog('bookAppointment',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
        }
        return self::_sendResult($message,$data,$errors,$status);

    }
    //Age service not have description then it is added here In working==================
    public function bookAppointmentNewTest(Request $request){
        
       // Log::info('in bookAppointmentNewTest');

        //dd($request->all()); time_slot_id
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        
        $patient_id     = $request->patient_id;
        $doctor_id      = $request->doctor_id;
        $appointment_type_id  = $request->appointment_type_id;
        $appointment_date     = $request->appointment_date;
        $time_frame     =  $request->time_frame;
        $exam_ids       =  $request->exam_ids;

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'patient_id'          => 'required',
                          'doctor_id'           => 'required',
                          'appointment_type_id' => 'required',
                          'appointment_date'    => 'required|date_format:Y-m-d|after_or_equal:today',
                          'time_frame'          => 'required',
                        ], 
                        [
                          'patient_id.required'     => __('api.ERR_APP_PATIENT_ID_REQ'),
                          'doctor_id.required'      => __('api.ERR_APP_DOCTOR_ID_REQ'),
                          'appointment_type_id.required' =>__('api.ERR_APP_TYPE_ID_REQ'),     
                          'appointment_date.required'    => __('api.ERR_APP_DATE_REQ'),
                          'appointment_date.date_format'    => __('api.ERR_APP_DATE_FORMAT_REQ'),
                          'appointment_date.after_or_equal'    => __('api.ERR_APP_VALID_DATE_REQ'),
                          'time_frame.required'    => __('api.ERR_APP_TIMEFRAME_REQ'),
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 

        }else
        {
             try {
                    //Check doctor time frame is available before booking appointment, if not available then throw error message
                    $check_time_frame = $this->RosterModel
                                ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                                ->where('roster.doctor_id',$doctor_id)
                                ->where('roster_has_dates.is_excluded','=',0)
                                ->whereDate('roster_has_dates.date',Date('Y-m-d',strtotime($appointment_date)))
                                ->where('roster_has_weeks_has_time_frames.time_frame','=',$time_frame)
                                ->get(['roster_has_weeks_has_time_frames.time_frame']); 
                    // dd($check_time_frame);
                    if(!empty($check_time_frame) && sizeof($check_time_frame)>0){
                        //now time slotes are available , but the appointment is booked for it then throw error message
                        $check_app_date = date("Y-m-d H:i:s",strtotime($request->appointment_date." ".$time_frame));
                        $check_app_end_date  = self::_getEndDate($check_app_date,$appointment_type_id);
                        $check_doctor_booked_appointment = $this->BaseModel
                            ->where('doctor_id',$doctor_id)
                            //->where('appointment_type_id',$appointment_type_id)
                            ->whereStatus(1)
                            ->where('appointment.start_date', '<=', $check_app_date)
                            ->where('appointment.end_date', '>=', $check_app_end_date)
                            //->where('appointment.start_date','=',$check_app_date)
                            ->get(['id']);

                        if(!empty($check_doctor_booked_appointment) && sizeof($check_doctor_booked_appointment)>0){
                                $errors[] = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
                        }

                    }else{
                         $errors[] = 'Terminzeitfenster können nicht gebucht werden.';//Appointment time slots is not available for booking
                    }
  
                    if(empty($errors) && sizeof($errors)==0){
                        //Start Booking an Appointement

                        DB::beginTransaction(); 

                        $collection     = new $this->BaseModel;   
                        $request['start_date'] = date("Y-m-d H:i",strtotime($request->appointment_date." ".$time_frame));
                        
                        $request['end_date']  = self::_getEndDate($request['start_date'],$request['appointment_type_id']);
                        
                        $collection     = self::_storeAppointment($collection,$request);


                        // ================================================================
                        if(isset($request->time_slot_id) && !empty($request->time_slot_id))
                        {
                            $updateRosterTimeFram = $this->RosterHasWeeksHasTimeFramesModel
                                                   ->where('id',$request->time_slot_id)
                                                   ->update([
                                                            'time_frame_flag'=>'2',
                                                            'time_frame_flag_date'=>Date('Y-m-d H:i:s'),
                                                            'comment'=>'AppointmentAgreementController(API) store booking function App Date :'.Date('Y-m-d H:i:s',strtotime($appointment_date)).' Current Date: '.Date('Y-m-d H:i:s').' Patient_id: '.$collection->patient_id
                                                            ]);
                        }
                        
                        // ================================================================
                        //Log::info('Book Appointment AppointmentAgreementController line NO:1268');
                        //self::_deactivateReminder($collection);
                        $all_transactions = [];
                        $notify_data = [];
                        $notes = '';
                        if ($collection) 
                        {
                            $all_transactions[] = 1;
                            $patient_doc_data = [];
                            $patient_doc_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'exam_app_type_id'=> $appointment_type_id,
                                                        'record_type'   => 1,
                                                        'doc_status'   => 0,
                                                        );
                            //Same name services are booked default===========
                            $getAppointmentTypeExam = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$collection->appointment_type_id)
                                ->get(['examination_id']);  
                            $examArray=array(); 
                            if(!empty($getAppointmentTypeExam)){
                                foreach($getAppointmentTypeExam as $appointment_exam){
                                    //$examArray[]=$appointment_exam->examination_id;
                                    //Added by swati 20-Oct-22===================
                                    $checkGeneralServcie=DB::table('preferred_channels_for_reminders_setting')
                                    ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                                    ->where('service_id',$appointment_exam->examination_id)
                                    ->get();
                                    if(!empty($checkGeneralServcie)){
                                        $today_date=date("Y-m-d");
                                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                                        ->where('service_id',$appointment_exam->examination_id)
                                                        ->where('patient_id',$collection->patient_id)
                                                        ->where('reminder_status','Set')
                                                        ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                                        ->first();
                                        if(empty($checkServiceReminders)){
                                            $examArray[]=$appointment_exam->examination_id;
                                        } 
                                    }
                                    else $examArray[]=$appointment_exam->examination_id;
                                    //End ===============================
                                }
                            }
                            //Added to check servicess ===================
                            if(!empty($exam_ids) && strlen($exam_ids)>0){
                                $exam_ids = array_filter(explode(",", $exam_ids));
                                foreach ($exam_ids as $key => $value) {
                                    $checkGeneralServcie=DB::table('preferred_channels_for_reminders_setting')
                                    ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                                    ->where('service_id',$value)
                                    ->get();
                                    if(!empty($checkGeneralServcie)){
                                        $today_date=date("Y-m-d");
                                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                                        ->where('service_id',$value)
                                                        ->where('patient_id',$collection->patient_id)
                                                        ->where('reminder_status','Set')
                                                        ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                                        ->first();
                                        if(!empty($checkServiceReminders)){
                                            unset($exam_ids[$key]);
                                        } 
                                    }
                                }
                            }
                            //End ===============================
                            //To get services with no desription if it is not get from api======================
                            $getHiddenExamination=self::getHiddenExamination($collection->patient_id,$collection->id);
                            // log::info("getHiddenExamination");
                            // log::info($getHiddenExamination);
                            // log::info($collection->patient_id."==>".$collection->id);
                            if(!is_array($exam_ids))
                            $exam_ids=array_filter(explode(",", $exam_ids));
                            $appServices=array_values($getHiddenExamination);
                            $exam_ids=array_filter(array_unique(array_merge($exam_ids,$appServices)));
                            // $exam_ids=implode(",", $exam_ids);
                            // log::info($exam_ids);
                            //===========================================================================
                            self::_deactivateReminderNew($collection,$exam_ids);

                            if(!empty($exam_ids)){
                                $exam_ids= array_filter(array_unique(array_merge($exam_ids,$examArray)));
                            }
                            else $exam_ids=$examArray;
                            // log::info("test");
                            // log::info($exam_ids);
                            
                            //Same name services end=========================
                            if(!empty($exam_ids) && count($exam_ids)>0){

                                //$exam_ids = explode(",", $exam_ids);
                                $exam_data = [];
                                
                                foreach ($exam_ids as $examId) {
                                    $exam_data[] = array(
                                                    'appointment_id'=> $collection->id,
                                                    'patient_id'    => $collection->patient_id,
                                                    'examination_id'=> $examId,
                                                    'created_at' => date("Y-m-d H:i:s")
                                                    );

                                    $patient_doc_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'exam_app_type_id'=> $examId,
                                                        'record_type'   => 0,
                                                        'doc_status'   => 0,
                                                        );

                                    $isExist = $this->EventTypeHasExaminationsModel
                                              ->where('patient_id',$collection->patient_id)
                                              ->where('appoinment_id',$collection->id)
                                              ->where('service_id',$examId)
                                              ->first();
                                    if(empty($isExist))
                                    {
                                        $eventType = new $this->EventTypeHasExaminationsModel;
                                    } 
                                    else
                                    {
                                        $eventType = $this->EventTypeHasExaminationsModel->find($isExist->id);
                                    }

                                    $eventType->patient_id    = $collection->patient_id;
                                    $eventType->appoinment_id = $collection->id;
                                    $eventType->service_id    = $examId;
                                    $eventType->event_type    = 'smart_phone';
                                    $eventType->status        = 'booked';
                                    $eventType->save();  
                                    // =================================================
                                }

                                if($this->AppointmentHasExaminationsModel->insert($exam_data)){ 
                                    $all_transactions[] = 1;
                                }else{
                                    $all_transactions[] = 0;
                                }

                                

                                $exam_details = $this->ExaminationsModel
                                                    ->whereIn('id',$exam_ids)
                                                    ->get([
                                                        'examinations.id',
                                                        'examinations.name'
                                                        ]); 

                                $exam_names = array_column($exam_details->toArray(), "name");
                                $exam_names = implode(",", $exam_names);

                                if(strlen($exam_names)>0){
                                    //$notes = $exam_names." gebucht";
                                }
                            }
                            
                            // dd($patient_doc_data);

                            // if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                            //     $all_transactions[] = 1;
                            // }else{
                            //     $all_transactions[] = 0;
                            // }

                            $getDocument = self::_GetAssignedDocument($collection->id,$collection->appointment_type_id,$exam_ids,$collection->patient_id);
                            // END

                            //insert the entry for patient has Checklist
                            $getDocument = self::_GetAssignedCheckList($collection->id,$exam_ids,$collection->patient_id);
                            // END

                            $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType','hasExaminations'=>function($q){
                                            $q->with(['assignedExamination']);
                                        }])->find($collection->id);   
                                
                            $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                            $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                            $appointmentType = $collection->assignedAppointmentType->name;
                            
                            //commented below on 6-nov-23 for text changes  
                            // $appointmentTime = date('d.F',strtotime($request->start_date)).",um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            //change below text on 6-nov-23 
                            $appointmentTime = date('d.F',strtotime($request->start_date)).", um ".date('H:i',strtotime($request->start_date))." Uhr.";

                            //commented on 6-nov-23
                            // $patientText = $collection->assignedPatient->salutation ?? "";
                            // $patientText .= " ".$collection->assignedPatient->family_name;

                            $patientText = $collection->assignedPatient->salutation ? " ".$collection->assignedPatient->salutation.'.': ""; //changed on 6-nov-23 added dot after salutation on 14-dec-23
                            $patientText .= " ".$collection->assignedPatient->first_name.' '.$collection->assignedPatient->family_name;         //added first name on 6-nov-23


                            $doctorSurname = $collection->assignedDoctor->last_name;


                            //Appoinment Push Notification
                            //commented on 6-nov-23
                            // $content = 'Hallo '.$patientText.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;

                            
                            $notify_times = self::_getNotifyTime($request['start_date']);

                            //commented below code on 13-feb-24 for notification section

                            /*$content = 'Hallo'.$patientText.', ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;   //changed content on 6-nov-23
                             
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
                            }*/


                            /******added code on 13-feb-24***for notification from setting section*****/

                            $app_notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($request->start_date)));

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

                            if($this->AppointmentHasNotificationModel->insert($notify_data))
                            {
                                $all_transactions[] = 1;
                            }
                            else 
                            {
                                $all_transactions[] = 0; 
                            }


                            /***********end code**of notification setting***13-feb-24*******************/



                            //Default appintment 
                            //$getServises = self::_appointmentTypesAgaintsServices($collection->id,$request->appointment_type_id,$request->patient_id);
                            // END 
                           
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
                            //dd(request()->all());
                            $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventStore(request());
                            //$postResponse = json_decode($postCalDetails->data);
                            // dd($postCalDetails);
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
                                DB::rollback();
                                $errors[] = $postCalDetails->original['msg'];
                            }
                           
                        }else{
                            $all_transactions[] = 0;
                        }

                        if (!in_array(0,$all_transactions)) 
                        {
                            DB::commit();
                            $status  = true;
                            $message = __('api.APPOINTMENT_BOOKED_SUCCESS');

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

                            $doctorImage = asset('assets/admin/images/default-image.png');
                            // if (!empty($collection->assignedDoctor->img_path) && is_file(storage_path().'/app/'.$collection->assignedDoctor->img_path))
                            $assignedDoctor_img_path = self::StorePath($collection->assignedDoctor->img_path.'/');

                            if (!empty($collection->assignedDoctor->img_path)) 
                            {
                                $doctorImage = self::getFilePath($collection->assignedDoctor->img_path);
                                //$doctorImage = url('/storage/app/'.$collection->assignedDoctor->img_path); 
                            }

                            $data[0]['doctor_image']       = $doctorImage;

                            $data[0]['exams']  = [];
                            if(!empty($collection->hasExaminations) && sizeof($collection->hasExaminations)>0){
                                foreach ($collection->hasExaminations as  $haskey=>$hasExamination) {
                                    if(isset($hasExamination->assignedExamination->id)){
                                        $data[0]['exams'][$haskey]['id'] = $hasExamination->assignedExamination->id;
                                        $data[0]['exams'][$haskey]['name'] = $hasExamination->assignedExamination->name;
                                        $data[0]['exams'][$haskey]['url'] = $hasExamination->assignedExamination->url;
                                    }
                                }

                            }
                            //$data[]  = $collection;
                            // Log::info('Book Appointment by AppointmentAgreementController');
                            $debug_arr['data'] = 'has created appointment by AppointmentController';    
                            $res_name = "AppointmentAgreementController_store";   
                            //dd($debug_arr);  
                            self::debugModeappBookFun($debug_arr,$res_name);  

                            self::_createLog('bookAppointment',$data,'info');
                            $this->ActivityLogModel->addApiLog('App Book Appointment','has book appointment','Create',null,$data);


                            /*********start**Code to send sms or email**on 21-dec-23********************/
                               // Log::info('after code start bookAppointmentNewTest');

                                $channels = $this->ChannelsRemindersSettingModel->where(['type' => 'global'])->first();

                                // Log::info($channels);
                                  // Log::info('before patient id');
                                 // Log::info($collection->patient_id);
                                 //    Log::info('before app id');
                                //  Log::info($collection->id);

                                if(isset($collection->patient_id) && isset($collection->id))
                                { 
                                    // Log::info("innnnnnnnnnn");

                                    $patientData = $this->PatientsModel->where('id', $collection->patient_id)->first();
                                    $appointmentData = $this->BaseModel->find($collection->id);

                                   //  Log::info($patientData);
                                    //   Log::info($appointmentData);

                                    if(isset($appointmentData) && isset($patientData))
                                    {
                                         $urlEventId = $appointmentData->google_event_id;
                                        //Send Email...
                                        if(!empty($patientData->email) && $channels->choice_of_channels == 'email')
                                        {
                                            self::_sendMailAppointment($patientData->id,$urlEventId);
                                        }
                                        else 
                                        {
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
                                        }//else
                                    }//if isset appdata and patientdata
                                }//if patient id and collection id

                            /**********end**Code to send sms or email**on 21-dec-23*******************/

                        }//if

                    }   

                }
                catch(\Exception $e) {
                    DB::rollback();
                    $errors[] = $e->getMessage();
                    self::_createLog('bookAppointment',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
        }
        return self::_sendResult($message,$data,$errors,$status);

    }

    public function editAppointment(Request $request)
    {
        //dd($request->all());
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        $exam_data = [];
        $appointment_id = $request->appointment_id;
        $exam_ids       =  $request->exam_ids;

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'appointment_id'          => 'required',
                        ], 
                        [
                          'appointment_id.required'    => __('api.ERR_APP_ID_REQ'),
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 

        }else
        {
             try {
                        DB::beginTransaction(); 
                        $notes = '';
                        $collection     = $this->BaseModel->find($appointment_id);   

                        $all_transactions = [];
                        $notify_data = [];
                        $patient_doc_data = []; 
                        if ($collection) 
                        {
                            $all_transactions[] = 1;
                            if(!empty($exam_ids) && strlen($exam_ids)>0){
                                $patient_doc_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'exam_app_type_id'=> $collection->appointment_type_id,
                                                        'record_type'   => 1,
                                                        'doc_status'   => 0,
                                                        );

                                // $this->AppointmentHasExaminationsModel->where('appointment_id',$collection->id)->delete();
                                // $this->PatientHasDocumentsModel->where('appointment_id',$collection->id)->delete();

                                $getExamination = $this->AppointmentHasExaminationsModel
                                                  ->select('examination_id as id')
                                                  ->where('appointment_id',$collection->id)
                                                  ->get();

                                $exams_ids1  = array_unique(array_column(array_values($getExamination->toArray()), 'id'));
                                //dd($exams_ids1);
                                $exam_ids = explode(",", $exam_ids);

                                foreach ($exam_ids as $examId) 
                                {
                                    if (!in_array((int)$examId, $exams_ids1))
                                    {
                                        $exam_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'examination_id'=> $examId,
                                                        'created_at' => date("Y-m-d H:i:s")
                                                        );
                                        $patient_doc_data[] = array(
                                                            'appointment_id'=> $collection->id,
                                                            'patient_id'    => $collection->patient_id,
                                                            'exam_app_type_id'=> $examId,
                                                            'record_type'   => 0,
                                                            'doc_status'   => 0,
                                                            );  
                                    } 
                                }

                                $getDocument = self::_GetAssignedDocument($collection->id,$collection->appointment_type_id,$exam_ids,$collection->patient_id);
                                // END

                                //insert the entry for patient has Checklist
                                $getDocument = self::_GetAssignedCheckList($collection->id,$exam_ids,$collection->patient_id);
                                // END
                                self::_deactivateReminderNew($collection,$exam_ids);
                                
                                $checkAppointment = $this->AppointmentHasExaminationsModel
                                        ->where('appointment_id', $collection->id)
                                        ->where('patient_id', $collection->patient_id)
                                        ->where('examination_id', $examId)
                                        ->first();
                                if(empty($checkAppointment)){
                                    if($this->AppointmentHasExaminationsModel->insert($exam_data)){ 
                                        $all_transactions[] = 1;
                                    }else{
                                        $all_transactions[] = 0;
                                    }
                                }

                                if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                                    $all_transactions[] = 1;
                                }else{
                                    $all_transactions[] = 0;
                                }

                                $exam_details = $this->ExaminationsModel
                                                    ->whereIn('id',$exam_ids)
                                                    ->get([
                                                        'examinations.id',
                                                        'examinations.name'
                                                        ]); 
                                $exam_names = array_column($exam_details->toArray(), "name");
                                // $exam_names = implode(",", $exam_names);

                                // if(strlen($exam_names)>0){
                                //     $notes = $exam_names." gebucht";
                                // }
                            }

                            $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType','hasExaminations'=>function($q){
                                            $q->with(['assignedExamination']);
                                        }])->find($collection->id);   
                                
                            $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                            $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                            $appointmentType = $collection->assignedAppointmentType->name;
                            
                            $appointmentTime = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr.";

                            $patientText = $collection->assignedPatient->salutation ?? "";
                            $patientText .= " ".$collection->assignedPatient->family_name;
                            $doctorSurname = $collection->assignedDoctor->last_name;
                           
                            $final_note = $collection->notes;
                            // if(!empty($exam_names) && count($exam_names) > 0)
                            // {
                            //     foreach($exam_names as $k=>$v)
                            //     {
                            //         if(empty($collection->notes))
                            //         {                             
                            //             $final_note .= ", ".$v;
                            //         }
                            //         else
                            //         {
                            //             if(strpos($collection->notes, $v) !== false)
                            //             {                                       
                            //             }else
                            //             {
                            //               $final_note .= ", ".$v;                    
                            //             }
                            //         }                                    
                            //     } 
                            // } 
                            
                            // $final_note = ltrim($final_note,", ");
                            // $final_note = rtrim($final_note,", ");
                            $summary = $patientName." - ".$appointmentType;
                            $description = '<p><strong>Patient:</strong> '.$patientName.' </p><p><strong>Arzt:</strong> '.$doctorName.' </p><p><strong>Typ:</strong> '.$appointmentType.' </p><p><strong>Beginn:</strong> '.date('F d,Y H:i',strtotime($collection->start_date)).' </p><strong>Ende:</strong> '.date('F d,Y H:i',strtotime($collection->end_date)).' </p><p><strong>Notizen:</strong> '.$final_note.' </p>';

                            $request = array(
                                'eventId'=>$collection->google_event_id,
                                'summary'=>$summary,
                                'description'=>$description,
                                'startDateTime'=>$collection->start_date,
                                'endDateTime'=>$collection->end_date,
                                'patient_id'=>$collection->patient_id,
                                'patient_email'=>$collection->assignedPatient->email,
                                'patient_name'=>$patientName,
                                'doctor_email'=>$collection->assignedDoctor->email,
                                'color_id'=>$collection->assignedDoctor->google_color_id,
                                );
                            request()->merge($request);
                            // dd(request()->all());
                            $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventUpdate(request());
                            //$postResponse = json_decode($postCalDetails->data);
                             // dd($postCalDetails);
                            if(!empty($postCalDetails) && $postCalDetails->original['status'] == 'success')
                            {
                                $all_transactions[] = 1;

                                $eventId = $postCalDetails->original['data']->id;
                                $collection->google_event_id = $eventId;
                                $collection->notes          = $final_note;

                                if($collection->save()){

                                    $all_transactions[] = 1;
                                    
                                }else{
                                    
                                    $all_transactions[] = 0;
                                }

                            }else{
                                $all_transactions[] = 0;
                                DB::rollback();
                                $errors[] = $postCalDetails->original['msg'];
                            }
                           
                        }else{
                            $all_transactions[] = 0;
                        }

                        if (!in_array(0,$all_transactions)) 
                        {
                            DB::commit();
                            $status  = true;
                            $message = __('api.APPOINTMENT_UPDATED_SUCCESS');

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

                            $doctorImage = asset('assets/admin/images/default-image.png');
                            $assignedDoctor_img_path = self::StorePath($collection->assignedDoctor->img_path.'/');

                            // if (!empty($collection->assignedDoctor->img_path) && is_file(storage_path().'/app/'.$collection->assignedDoctor->img_path))
                            if (!empty($collection->assignedDoctor->img_path)) 
                            {
                                $doctorImage = self::getFilePath($collection->assignedDoctor->img_path);
                                //$doctorImage = url('/storage/app/'.$collection->assignedDoctor->img_path); 
                            }

                            $data[0]['doctor_image']       = $doctorImage;

                            $data[0]['exams']  = [];
                            if(!empty($collection->hasExaminations) && sizeof($collection->hasExaminations)>0){
                                foreach ($collection->hasExaminations as  $haskey=>$hasExamination) {
                                    if(isset($hasExamination->assignedExamination->id)){
                                        $data[0]['exams'][$haskey]['id'] = $hasExamination->assignedExamination->id;
                                        $data[0]['exams'][$haskey]['name'] = $hasExamination->assignedExamination->name;
                                        $data[0]['exams'][$haskey]['url'] = $hasExamination->assignedExamination->url;
                                    }
                                }

                            }
                            //$this->AppointmentHasExaminationsModel->where('appointment_id',$collection->id)->where('examination_id',0)->delete();
                            //$data[]  = $collection;
                            self::_createLog('editAppointment',$data,'info');
                            $this->ActivityLogModel->addApiLog('Edit Appointment','has edited appointment','Edit',null,$data);
                        }

                }
                catch(\Exception $e) {
                    DB::rollback();
                    $errors[] = $e->getMessage();
                    self::_createLog('editAppointment',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
        }
        return self::_sendResult($message,$data,$errors,$status);

    }
    public function editAppointmentQRCode(Request $request)
    {
         Log::info('in editAppointmentQRCode....');

        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        $exam_data  = [];
        $appointment_id = $request->appointment_id;
        $exam_ids       =  $request->exam_ids;
        $inputdata  = $request->all();

         Log::info($inputdata);

        $validator  = Validator::make($inputdata,[
                          'appointment_id'          => 'required',
                        ], 
                        [
                          'appointment_id.required'    => __('api.ERR_APP_ID_REQ'),
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 

        }else
        {
             try {
                        DB::beginTransaction(); 
                        $notes = '';
                        $collection     = $this->BaseModel->find($appointment_id);   

                        $appPatientId = $collection->patient_id; //added on 28-march-24

                        //dump($collection);

                        $all_transactions = [];
                        $notify_data = [];
                        $patient_doc_data = []; 
                        if ($collection) 
                        {
                            $all_transactions[] = 1;

                            if(!empty($exam_ids) && strlen($exam_ids)>0){


                                $patient_doc_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'exam_app_type_id'=> $collection->appointment_type_id,
                                                        'record_type'   => 1,
                                                        'doc_status'   => 0,
                                                        );

                                // $this->AppointmentHasExaminationsModel->where('appointment_id',$collection->id)->delete();
                                // $this->PatientHasDocumentsModel->where('appointment_id',$collection->id)->delete();

                                $getExamination = $this->AppointmentHasExaminationsModel
                                                  ->select('examination_id as id')
                                                  ->where('appointment_id',$collection->id)
                                                  ->where('patient_id',$collection->patient_id) //added on 28-mrach-24
                                                  ->get();

                                  // dump($getExamination);               

                                $exams_ids1  = array_unique(array_column(array_values($getExamination->toArray()), 'id'));

                               // dump($exams_ids1);

                                $exam_ids = explode(",", $exam_ids);

                               // dump($exam_ids);


                                //  Log::info("exams_ids1");
                                // Log::info($exams_ids1);

                                // Log::info("exam_ids");
                                // Log::info($exam_ids);

                                foreach ($exam_ids as $examId) 
                                {
                                    if (!in_array((int)$examId, $exams_ids1))
                                    {
                                        $exam_data[] = array(
                                                        'appointment_id'=> $collection->id,
                                                        'patient_id'    => $collection->patient_id,
                                                        'examination_id'=> $examId,
                                                        'created_at'=> Date('Y-m-d'),
                                                        );
                                        $patient_doc_data[] = array(
                                                            'appointment_id'=> $collection->id,
                                                            'patient_id'    => $collection->patient_id,
                                                            'exam_app_type_id'=> $examId,
                                                            'record_type'   => 0,
                                                            'doc_status'   => 0,
                                                            );  

                                        // =================================================
                                        $isExist = $this->EventTypeHasExaminationsModel
                                                  ->where('patient_id',$collection->patient_id)
                                                  ->where('appoinment_id',$collection->id)
                                                  ->where('service_id',$examId)
                                                  ->first();
                                        if(empty($isExist))
                                        {
                                            $eventType = new $this->EventTypeHasExaminationsModel;
                                        } 
                                        else
                                        {
                                            $eventType = $this->EventTypeHasExaminationsModel->find($isExist->id);
                                        }

                                        $eventType->patient_id    = $collection->patient_id;
                                        $eventType->appoinment_id = $collection->id;
                                        $eventType->service_id    = $examId;
                                        $eventType->event_type    = 'tablet';
                                        $eventType->status        = 'booked';
                                        $eventType->save(); 
                                        //log::info("testing");
                                        //log::info($examId);
                                    }     
                                    // =================================================
                                }
                                self::_deactivateReminderNew($collection,$exam_ids);
                                //dd($exam_data);

                                // Log::info("exam_data");
                                // Log::info($exam_data);


                                if($this->AppointmentHasExaminationsModel->insert($exam_data)){ 
                                    $all_transactions[] = 1;
                                }else{
                                    $all_transactions[] = 0;
                                }
                                //dd($all_transactions);
                                //Added by swati 12-jan-23 =======================
                                $checkPatientDoc=$this->PatientHasDocumentsModel
                                                ->where('appointment_id',$collection->id)
                                                ->where('patient_id',$collection->patient_id)
                                                ->where('exam_app_type_id',$collection->appointment_type_id)
                                                ->first();
                                if(empty($checkPatientDoc)){
                                    if($this->PatientHasDocumentsModel->insert($patient_doc_data)){ 
                                    $all_transactions[] = 1;
                                    }else{
                                        $all_transactions[] = 0;
                                    }
                                }    

                                $exam_details = $this->ExaminationsModel
                                                    ->whereIn('id',$exam_ids)
                                                    ->get([
                                                        'examinations.id',
                                                        'examinations.name'
                                                        ]); 

                                                  //  dd($exam_details);
                                $exam_names = array_column($exam_details->toArray(), "name");
                                // $exam_names = implode(", ", $exam_names);

                                // if(strlen($exam_names)>0){
                                //     $notes = $exam_names." gebucht";
                                // }
                            }

                            //commented on 7-march-24 (8-march-24) for app type query 
                            /*$collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType','hasExaminations'=>function($q){
                                            $q->with(['assignedExamination']);
                                        }])->find($collection->id);   */


                             //added on 7-march-24 (8-march-24) for app type function  
                             //commented below query on 28-march-24          
                           /* $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentTypeQRCodeApp',
                                          'hasExaminations'=>function($q)
                                          {
                                            $q->with(['assignedExamination']);
                                          }
                                    ])->find($collection->id);  */




                            //did changes in below query on 28-march-24 for patient id condition added
                             $collection = $this->BaseModel->with(['assignedPatient','assignedDoctor','assignedAppointmentTypeQRCodeApp',
                                          'hasExaminations'=>function($q) use($appPatientId)
                                          {
                                            $q->where('patient_id',$appPatientId);
                                            $q->with(['assignedExamination']);
                                          }
                                    ])->find($collection->id);          


                              // dump("collection");
                              // dump($collection);

                            
                            $patientName = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                            $doctorName = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;

                            //commented on 7-march-24 (8-march-24)  
                            //$appointmentType = $collection->assignedAppointmentType->name;

                            //added on 7-march-24 (8-march-24)
                            $appointmentType = $collection->assignedAppointmentTypeQRCodeApp->name;
                            
                            $appointmentTime = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr.";

                            $patientText = $collection->assignedPatient->salutation ?? "";
                            $patientText .= " ".$collection->assignedPatient->family_name;
                            $doctorSurname = $collection->assignedDoctor->last_name;
                           
                            $final_note = $collection->notes;
                            // if(!empty($exam_names) && count($exam_names) > 0)
                            // {
                            //     foreach($exam_names as $k=>$v)
                            //     {
                            //         if(empty($collection->notes))
                            //         {                             
                            //             $final_note .= ", ".$v;
                            //         }
                            //         else
                            //         {
                            //             if(strpos($collection->notes, $v) !== false)
                            //             {                                       
                            //             }else
                            //             {
                            //               $final_note .= ", ".$v;                    
                            //             }
                            //         }                                    
                            //     } 
                            // } 
                            
                            // $final_note = ltrim($final_note,", ");
                            // $final_note = rtrim($final_note,", ");
                            //dd($final_note);
                           
                            $summary = $patientName." - ".$appointmentType;
                            $description = '<p><strong>Patient:</strong> '.$patientName.' </p><p><strong>Arzt:</strong> '.$doctorName.' </p><p><strong>Typ:</strong> '.$appointmentType.' </p><p><strong>Beginn:</strong> '.date('F d,Y H:i',strtotime($collection->start_date)).' </p><strong>Ende:</strong> '.date('F d,Y H:i',strtotime($collection->end_date)).' </p><p><strong>Notizen:</strong> '.$final_note.' </p>';

                            $request = array(
                                'eventId'=>$collection->google_event_id,
                                'summary'=>$summary,
                                'description'=>$description,
                                'startDateTime'=>$collection->start_date,
                                'endDateTime'=>$collection->end_date,
                                'patient_id'=>$collection->patient_id,
                                'patient_email'=>$collection->assignedPatient->email,
                                'patient_name'=>$patientName,
                                'doctor_email'=>$collection->assignedDoctor->email,
                                'color_id'=>$collection->assignedDoctor->google_color_id,
                                );
                            request()->merge($request);
                            // dd(request()->all());
                            $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventUpdate(request());
                            //$postResponse = json_decode($postCalDetails->data);
                             // dd($postCalDetails);

                            if(!empty($postCalDetails) && $postCalDetails->original['status'] == 'success')
                            {
                                $all_transactions[] = 1;

                                $eventId = $postCalDetails->original['data']->id;
                                $collection->google_event_id = $eventId;
                                $collection->notes          = $final_note;

                                if($collection->save()){

                                    $all_transactions[] = 1;
                                    
                                }else{
                                    
                                    $all_transactions[] = 0;
                                }

                            }else{
                                $all_transactions[] = 0;
                                DB::rollback();
                                $errors[] = $postCalDetails->original['msg'];
                            }
                           
                        }else{
                            $all_transactions[] = 0;
                        }

                        if (!in_array(0,$all_transactions)) 
                        {
                            DB::commit();
                            $status  = true;
                            $message = __('api.APPOINTMENT_UPDATED_SUCCESS');

                            $data[0]['id']          = $collection->id;
                            $data[0]['start_date']  = $collection->start_date;
                            $data[0]['end_date']    = $collection->end_date;
                            $data[0]['patient_id']  = $collection->patient_id;
                            $data[0]['doctor_id']   = $collection->doctor_id;
                            $data[0]['appointment_type_id']    = $collection->appointment_type_id;

                            //commented on 7-march-24 (8-march-24)    
                            // $data[0]['appointment_type_name']  = $collection->assignedAppointmentType->name;

                             //added on 7-march-24 (8-march-24)
                            $data[0]['appointment_type_name']  = $collection->assignedAppointmentTypeQRCodeApp->name;


                            $data[0]['patient_name']    = $patientName;
                            $data[0]['doctor_name']     = $doctorName;
                            $data[0]['doctor_speciality']  = $collection->assignedDoctor->doctor_speciality;

                            $doctorImage = asset('assets/admin/images/default-image.png');
                            $assignedDoctor_img_path = self::StorePath($collection->assignedDoctor->img_path.'/');

                            // if (!empty($collection->assignedDoctor->img_path) && is_file(storage_path().'/app/'.$collection->assignedDoctor->img_path))
                            if (!empty($collection->assignedDoctor->img_path)) 
                            {
                                $doctorImage = self::getFilePath($collection->assignedDoctor->img_path);
                                //$doctorImage = url('/storage/app/'.$collection->assignedDoctor->img_path); 
                            }

                            $data[0]['doctor_image']       = $doctorImage;

                            $data[0]['exams']  = [];
                            if(!empty($collection->hasExaminations) && sizeof($collection->hasExaminations)>0){
                                foreach ($collection->hasExaminations as  $haskey=>$hasExamination) {
                                    $data[0]['exams'][$haskey]['id'] = $hasExamination->assignedExamination->id;
                                    $data[0]['exams'][$haskey]['name'] = $hasExamination->assignedExamination->name;
                                    $data[0]['exams'][$haskey]['url'] = $hasExamination->assignedExamination->url;
                                }

                            }
                            //$data[]  = $collection;
                            self::_createLog('editAppointment',$data,'info');
                            $this->ActivityLogModel->addApiLog('Edit Appointment','has edited appointment','Edit',null,$data);
                        }

                }
                catch(\Exception $e) {
                    DB::rollback();
                    $errors[] = $e->getMessage();
                    self::_createLog('editAppointment',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
        }
        return self::_sendResult($message,$data,$errors,$status);

    }

    public function _storeAppointment($collection, $request)
    {
        $collection->patient_id  = $request->patient_id;
        $collection->doctor_id   = $request->doctor_id;  
        $collection->appointment_type_id = $request->appointment_type_id;
        $collection->appointment_type_id = $request->appointment_type_id;
        $collection->start_date = $request->start_date;
        $collection->end_date   = $request->end_date;
        $collection->notes      = $request->notes;
        $collection->status     = 1;

        //Save data
        $collection->save();
        return $collection;   
    }

     /*-------------------------------------------------------------
    |   Cancel Appointment
    -----------------------------------------------------------------------*/
    public function cancelAppointment(Request $request)
    {
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        
        $patient_id  = $request->patient_id;
        $appointment_id  = $request->appointment_id;

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'patient_id'          => 'required',
                          'appointment_id' => 'required',
                        ], 
                        [
                          'patient_id.required'     => __('api.ERR_APP_PATIENT_ID_REQ'),
                          'appointment_id.required' => __('api.ERR_APP_ID_REQ'),
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 
        }else
        {
            try {
                    DB::beginTransaction();
                    $collection = $this->BaseModel->find($appointment_id);
                    self::_activateReminderOnCancel($collection);
                    if($collection->delete())
                    {
                        $this->AppointmentHasNotificationModel->where('appointment_id',$collection->id)->delete();
                        $this->AppointmentHasExaminationsModel->where('appointment_id',$collection->id)->delete();
                        $this->PatientHasDocumentsModel->where('appointment_id',$collection->id)->delete();

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
                            DB::commit();
                            $status  = true;
                            $message = __('api.APPOINTMENT_CANCEL_SUCCESS');
                            $data[0]['id']  = $collection->id;
                            $data[0]['start_date']  = $collection->start_date;
                            $data[0]['end_date']  = $collection->end_date;
                            $data[0]['patient_id']  = $collection->patient_id;
                            $data[0]['doctor_id']  = $collection->doctor_id;
                            $data[0]['appointment_type_id']  = $collection->appointment_type_id;
                            self::_createLog('cancelAppointment',$data,'info');
                            $this->ActivityLogModel->addApiLog('Cancel Appointment','has cancel appointment','Delete',null,$data);
                        }else{
                            DB::rollback();
                            $errors[] = $postCalDetails->original['msg'];
                            self::_createLog('cancelAppointment',$errors,'error'); 
                            // $this->ActivityLogModel->addApiLog('cancelAppointment','send otp for login','Get');
                        }
                    }
                }
                catch(\Exception $e) {
                    DB::rollback();
                    $errors[] = $e->getMessage();
                    self::_createLog('cancelAppointment',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get'); 
                }
        }
        return self::_sendResult($message,$data,$errors,$status);
    }


    public function cancelAppointmentNew(Request $request)
    {
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        
        $patient_id  = $request->patient_id;
        $appointment_id  = $request->appointment_id;

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'patient_id'          => 'required',
                          'appointment_id' => 'required',
                        ], 
                        [
                          'patient_id.required'     => __('api.ERR_APP_PATIENT_ID_REQ'),
                          'appointment_id.required' => __('api.ERR_APP_ID_REQ'),
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 
        }else
        {
            try {
                    DB::beginTransaction();
                    $collection = $this->BaseModel->find($appointment_id);

                    // ===================================================================
                 
                    $timeFrame = date('H:i:s',strtotime($collection->start_date));
                    $doctor_id = $collection->doctor_id;

                    $time_frames_id='';

                    $time_frames= $this->RosterHasDatesModel
                                        ->leftjoin('roster','roster.id','roster_has_dates.roster_id')
                                        ->whereDate('roster_has_dates.date',date('Y-m-d',strtotime($collection->start_date)))
                                        ->where('roster.doctor_id',$doctor_id)
                                        ->first();
                    //dd($time_frames);                    
                    if(!empty($time_frames))
                    {

                        $getrec = $this->RosterHasWeeksHasTimeFramesModel
                              ->where('week_day_id',$time_frames->week_day_id)   
                              ->where('roster_id',$time_frames->roster_id) 
                              ->where('time_frame',$timeFrame)   
                              ->where('time_frame_flag','2')
                              ->first();
                        //dd($getrec);       
                        if(!empty($getrec))
                        {
                            $oldUpdateTimeFrameFlg = $this->RosterHasWeeksHasTimeFramesModel->find($getrec->id);
                       
                            $oldUpdateTimeFrameFlg->time_frame_flag = '0';
                            $oldUpdateTimeFrameFlg->comment         = 'patient_id '.$collection->patient_id.' deleted Appointment Date :'.$collection->start_date.' Appointment From  DashboardController current Date :'.date('Y-m-d H:i:s').' Time Fram Id : '.$getrec->id;
                            $oldUpdateTimeFrameFlg->save();  
                        }

                    }
                    //========================================================================
                    self::_activateReminderOnCancel($collection);

                    // ==============deleted track============================
                    self::DeletedAppointmentTrack($collection);
                    // ------------------------------------
                    if($collection->delete())
                    {
                        $this->AppointmentHasNotificationModel->where('appointment_id',$collection->id)->delete();
                        $this->AppointmentHasExaminationsModel->where('appointment_id',$collection->id)->delete();
                        $this->PatientHasDocumentsModel->where('appointment_id',$collection->id)->delete();


                        //Delete reminders aslo for the appoinment 2-jun-23=====
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
                        $this->PatientsHasServiceReminderModel
                            ->where('appointment_id',$collection->id)
                            ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                        if(sizeof($id_holder)>0)
                        {
                            $reactivateReminder =  $this->PatientHasReminder
                                               ->whereIn('service_reminder_id',$id_holder)
                                               ->update(['deleted_at'=>date('Y-m-d H:i:s')]);      
                        }                       
                        //==================================================

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
                            DB::commit();
                            $status  = true;
                            $message = __('api.APPOINTMENT_CANCEL_SUCCESS');
                            $data[0]['id']  = $collection->id;
                            $data[0]['start_date']  = $collection->start_date;
                            $data[0]['end_date']  = $collection->end_date;
                            $data[0]['patient_id']  = $collection->patient_id;
                            $data[0]['doctor_id']  = $collection->doctor_id;
                            $data[0]['appointment_type_id']  = $collection->appointment_type_id;
                            self::_createLog('cancelAppointment',$data,'info');
                            $this->ActivityLogModel->addApiLog('Cancel Appointment','has cancel appointment','Delete',null,$data);
                        }else{
                            DB::rollback();
                            $errors[] = $postCalDetails->original['msg'];
                            self::_createLog('cancelAppointment',$errors,'error'); 
                            // $this->ActivityLogModel->addApiLog('cancelAppointment','send otp for login','Get');
                        }
                    }
                }
                catch(\Exception $e) {
                    DB::rollback();
                    $errors[] = $e->getMessage();
                    self::_createLog('cancelAppointment',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get'); 
                }
        }
        return self::_sendResult($message,$data,$errors,$status);
    }

    public function getAppointmentHistory(Request $request)
    {
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        $patientId  = $request->patient_id;

        $inputdata  = $request->all(); 
        try{
            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                            ], 
                            [
                              'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),     
                            ]
                            ); 

            if ($validator->fails()) 
            {           
              $errors[] = $validator->errors(); 
            }else
            {
                $today_date =  date('Y-m-d H:i:s', strtotime(now()));
                // dd($today_date);
                $collections = collect([]);  
                $collections = $this->BaseModel
                                    ->with(['assignedPatient','assignedDoctor','assignedAppointmentType'])
                                    ->where('patient_id', $patientId)
                                    ->where('start_date','<=',$today_date)
                                    ->whereStatus(1)
                                    ->orderBy('start_date', 'asc')
                                    ->get();  
                                    // dd($collections);
                 if(!empty($collections) && ($collections->count() > 0)){
                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data  = [];
                        foreach ($collections as $key => $collection){
                            $data[$key]['id']  = $collection->id;
                            $data[$key]['start_date']  = $collection->start_date;
                            $data[$key]['end_date']  = $collection->end_date;
                            $data[$key]['patient_id']  = $collection->patient_id;
                            $data[$key]['doctor_id']  = $collection->doctor_id;
                            $data[$key]['appointment_type_id']  = $collection->appointment_type_id;
                            $data[$key]['appointment_type_name']  = $collection->assignedAppointmentType->name;
                            $data[$key]['patient_name']  = $collection->assignedPatient->first_name." ".$collection->assignedPatient->family_name;
                            $data[$key]['doctor_name']  = $collection->assignedDoctor->first_name." ".$collection->assignedDoctor->last_name;
                            $data[$key]['doctor_speciality']  = $collection->assignedDoctor->doctor_speciality;

                            $profileImage = asset('assets/admin/images/default-image.png');
                            // if (!empty($collection->assignedDoctor->img_path) && is_file(storage_path().'/app/'.$collection->assignedDoctor->img_path))
                            $assignedDoctor_img_path = self::StorePath($collection->assignedDoctor->img_path.'/');

                            if (!empty($collection->assignedDoctor->img_path)) 
                            {
                                $profileImage = self::getFilePath($collection->assignedDoctor->img_path);
                                //$profileImage = url('/storage/app/'.$collection->assignedDoctor->img_path); 
                            }

                            $data[$key]['doctor_image']  = $profileImage;
                        }
                        self::_createLog('getAppointmentHistory',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('Get Appointment History','has get appointment history','Get');

                    }else{
                        $message  = __('api.ERR_NOT_FOUND');
                        $errors[] = [
                              "error" => __('api.DATA_NOT_FOUND'),
                          ];
                        self::_createLog('getAppointmentHistory',$errors,'error');
                        // $this->ActivityLogModel->addApiLog('getAppointmentHistory','send otp for login','Get');
                    }
                }
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
                self::_createLog('getAppointmentHistory',$errors,'error');
                // $this->ActivityLogModel->addApiLog('getAppointmentHistory','send otp for login','Get');
            }
       return self::_sendResult($message,$data,$errors,$status);
    } 
 
    public function getAppointmentTypeDetail(Request $request){
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;
        // $patientId  = $request->patient_id;
        $appointmentTypeId  = $request->appointment_type_id;
        // dd($appointmentTypentId);

        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                          'appointment_type_id'=> 'required'
                        ], 
                        [
                          'appointment_type_id.required'=>__('api.APPOINTMENT_TYPE_ID_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
            $errors[] = $validator->errors(); 
        }else
        {
            try{
                $collection = collect([]);  
                $collection = $this->AppointmentTypesModel->where('id', $appointmentTypeId)->get();
                      

                $status  = true;
                if((!empty($collection) && sizeof($collection) > 0)){
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collection;
                    self::_createLog('getAppointmentTypeDetail',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }else{
                    $message = __('api.ERR_NOT_FOUND');
                }
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(),
                  ];
                self::_createLog('getAppointmentTypeDetail',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    // public function getAppointmentDocuments(Request $request){
    //     $errors     = [];  
    //     $data       = []; 
    //     $message    = __('api.ERR_NOT_FOUND'); 
    //     $status     = false;

    //     $appointmentId  = $request->appointment_id;

    //     $inputdata  = $request->all();
    //     $validator  = Validator::make($inputdata,[
    //                       'appointment_id'=> 'required'
    //                     ], 
    //                     [
    //                       'appointment_id.required'=>__('api.APPOINTMENT_ID_REQ'),     
    //                     ]
    //                     ); 

    //     if ($validator->fails()) 
    //     {           
    //         $errors[] = $validator->errors(); 
    //     }else
    //     {
    //         try{

    //             $collections = collect([]);  
    //             $appointment_type_docs = collect([]);  
    //             $appointment_exams_docs = collect([]);  

    //             $appointment_type_docs = $this->AppointmentTypesModel->with('hasPatientDocuments')
    //                                             ->join('appointment','appointment.appointment_type_id','=','appointment_types.id')
    //                                             ->where('appointment.id','=',$appointmentId)
    //                                             ->whereNotNull('appointment_types.patient_document_path')
    //                                             // ->where('appointment_types.recommend_exams','=','0')
    //                                             ->get([
    //                                                 'appointment_types.id',
    //                                                 'appointment_types.patient_document as document_name',
    //                                                 'appointment_types.patient_document_path as document_path',
    //                                                 //'appointment_types.recommend_exams',
    //                                                // 'appointment_types.patient_document_status'
    //                                                 ]);

    //             // dd($appointmentId,$appointment_type_docs->toArray());
    //             $appointment_exams_docs = $this->ExaminationsModel->with('hasPatientDocuments')
    //                                             ->join('appointment_has_examinations','appointment_has_examinations.examination_id','=','examinations.id')
    //                                             ->where('appointment_has_examinations.appointment_id','=',$appointmentId)
    //                                             ->whereNotNull('examinations.document_path')
    //                                             ->get([
    //                                                 'examinations.id',
    //                                                 'examinations.document_name',
    //                                                 'examinations.document_path',
    //                                                 // '"exams" as record_type'
    //                                                 ]); 
    //             // dd($appointment_type_docs->toArray(),$appointment_exams_docs->toArray());
               
    //             if((!empty($appointment_type_docs) && sizeof($appointment_type_docs) > 0))
    //             {
    //                 $appointment_type_docs = $appointment_type_docs->map(function($item) use($appointmentId)
    //                 {
    //                     $item->record_type = 'appointment_types';
                        
    //                     $item->doc_status = 0;
    //                     if(!empty($item->hasPatientDocuments) && sizeof($item->hasPatientDocuments)>0){
    //                         foreach ($item->hasPatientDocuments as $hasPatientDocument) {
    //                             if($hasPatientDocument->appointment_id==$appointmentId){
    //                                 $item->doc_status = $hasPatientDocument->doc_status;
    //                                 break;
    //                             }
    //                         }
    //                         // $item->doc_status = $item->hasPatientDocuments[0]->doc_status;
    //                     }

    //                     $doc_path = '';
    //                     if (!empty($item->document_path) && is_file(storage_path().$item->document_path)) 
    //                     {
    //                         $doc_path = url('/storage'.$item->document_path); 
    //                     }
    //                     $item->document_path = $doc_path;
    //                     return $item;
    //                 });
    //             }

    //             if((!empty($appointment_exams_docs) && sizeof($appointment_exams_docs) > 0)){

    //                 $appointment_exams_docs = $appointment_exams_docs->map(function($item) use($appointmentId)
    //                             {
    //                                 $item->record_type = 'exams';

    //                                 $item->doc_status = 0;
    //                                 if(!empty($item->hasPatientDocuments) && sizeof($item->hasPatientDocuments)>0){
    //                                    // $item->doc_status = $item->hasPatientDocuments[0]->doc_status;
    //                                     foreach ($item->hasPatientDocuments as $hasPatientDocument) {
    //                                         if($hasPatientDocument->appointment_id==$appointmentId){
    //                                             $item->doc_status = $hasPatientDocument->doc_status;
    //                                             break;
    //                                         }
    //                                     }
    //                                 }

    //                                 $doc_path = '';
    //                                 if (!empty($item->document_path) && is_file(storage_path().$item->document_path)) 
    //                                 {
    //                                     $doc_path = url('/storage'.$item->document_path); 
    //                                 }
    //                                 $item->document_path = $doc_path;
    //                                 return $item;
    //                             });

                
    //             }
                
    //             $collections = $appointment_type_docs->merge($appointment_exams_docs);

    //              // dd($appointmentId,$appointment_exams_docs,$appointment_type_docs);

    //             $status  = true;
    //             if((!empty($collections) && sizeof($collections) > 0)){
    //                 $message = __('api.DATA_FOUND_SUCCESS');
    //                 $data  = $collections;
    //                 // self::_createLog('getAppointmentTypeDetail',array($data),'info');
    //                 // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

    //             }else{
    //                 $data  = [];
    //                 $message = __('api.ERR_NOT_FOUND');
    //             }
    //         }
    //         catch(\Exception $e) {
    //             $message = __('api.ERR_SOMETHING_WRONG');
    //             $errors[] = [
    //                   "error" => $e->getMessage(),
    //               ];
    //             //self::_createLog('getAppointmentTypeDetail',$errors,'error');
    //             // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    //         }
    //     }
    //    return self::_sendResult($message,$data,$errors,$status);
    // }

    public function getAppointmentDocuments(Request $request)
    {
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $appointmentId  = $request->appointment_id;

        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                          'appointment_id'=> 'required'
                        ], 
                        [
                          'appointment_id.required'=>__('api.APPOINTMENT_ID_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
            $errors[] = $validator->errors(); 
        }else
        {
            try{

                $collections = collect([]);  
                $appointment_type_docs = collect([]);  
                $appointment_exams_docs = collect([]);  
                $service_doc = $finalSeviceDoc = [];
                $data_collection  = null;
                $str = '';
                // Service Document
                $getAppointment = $this->BaseModel->find($appointmentId);
              
                if(!empty($getAppointment))
                {
                    // GENERAL DOCUMENT
                    $getGeneralDocument = $this->SpecialistDocumentsModel
                               ->where('status','1')
                               ->where('type_of_document','general')
                               ->get();

                    if(!empty($getGeneralDocument) && sizeof($getGeneralDocument)>0)
                    {
                        foreach ($getGeneralDocument as $generalDoc_key => $generalDoc_val) 
                        {
                            $getSpecilistDocument = $this->SpecialistDocumentsModel
                                                   ->where('id',$generalDoc_val['id'])
                                                   ->first();
                            if(!empty($getSpecilistDocument))
                            {
                                $l_date = self::checkFrequency($getAppointment->patient_id,$appointmentId,$getSpecilistDocument,null);
                           
                                if(!empty($l_date))
                                {
                                    $getdocrecord = self::saveGeneralDocument($getSpecilistDocument['id'],$getAppointment,'general',$l_date,null);



                                   /* $str .= '<div style="width: 100%;"> 
                                      <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                    if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                    {
                                        $str .= '<img style="width: 100%;height:auto;" src="'.url('storage'.$getSpecilistDocument['header_image_path']).'" >';
                                    }
                                    $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                    if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                    {
                                        $str .= '<img style="width: 100%;height: auto;" src="'.url('storage'.$getSpecilistDocument['footer_image_path']).'" >';
                                    }
                                    $str .= '</div>
                                      <div>
                                       
                                        <p>Ihr pureGyn Team</p>
                                      </div>
                                    </div>';*/


                                     $str .= '<div style="width: 100%;"> 
                                      <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                    if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                    {
                                        $str .= '<img style="width: 100%;height:auto;" src="'.url('storage'.$getSpecilistDocument['header_image_path']).'" >';
                                    }
                                    $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                   
                                    $str .= '</div><br/>
                                      <div style="margin-left: 52px;">
                                        <p>Ihr pureGyn Team</p>
                                      </div>';

                                     if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                    {
                                        $str .= '<img style="width: 100%;height: auto;" src="'.url('storage'.$getSpecilistDocument['footer_image_path']).'" >';
                                    }  

                                     $str .= '</div>';



                                    $service_doc[$generalDoc_key]['Html']  = $str;
                                    $service_doc[$generalDoc_key]['examination_id']  = null;
                                    $service_doc[$generalDoc_key]['doc_id']  = $getdocrecord->id;
                                    if($getdocrecord->doc_status == 0)
                                    {
                                        $service_doc[$generalDoc_key]['doc_status']  = 0;
                                    }
                                    elseif(in_array('1', $getdocrecord->doc_status))
                                    {
                                        $service_doc[$generalDoc_key]['doc_status']  = 1;
                                    }
                                    
                                    $service_doc[$generalDoc_key]['type']    = 'general';
                                    $service_doc[$generalDoc_key]['name']    = $getSpecilistDocument['name'];
                                    $service_doc[$generalDoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($getSpecilistDocument->created_at));
                                    $str = '';
                                }
                            }                     
                            
                        }
                      
                        $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
                        $service_doc = []; 
                        $str = ''; 
                    }
                    // END GENERAL DOCUMENT

                    $getExamDocument = $this->AppointmentTypeHasExaminationsModel
                                        ->where('appoinment_id',$getAppointment->appointment_type_id)
                                        ->get();
                                        //dd($getExamDocument);
                    // $getExamDocument = $this->AppointmentHasExaminationsModel
                    //                    ->where('appointment_id',$appointmentId) 
                    //                     ->get();
                                
                   if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                    {
                        foreach ($getExamDocument as $exam_key => $exam_val) 
                        {
                            $getExamDocument = $this->ExaminationsHasMultipleDocumentListModel
                                                 ->where('fk_examinations_id',$exam_val['examination_id'])
                                                 ->get();
                            //dd($getExamDocument);                     
                            if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                            {
                                foreach ($getExamDocument as $examdoc_key => $examdoc_val) 
                                {
                                    $getSpecilistDocument = $this->SpecialistDocumentsModel
                                                           ->where('status','1')
                                                           ->where('type_of_document','service')
                                                           ->where('id',$examdoc_val['fk_document_list_id'])
                                                           ->first();
                                    //dd($getSpecilistDoc);                       
                                    if(!empty($getSpecilistDocument))   
                                    {
                                        // check Frequency generalDoc_key
                                        $l_date = self::checkFrequency($getAppointment->patient_id,$appointmentId,$getSpecilistDocument,$exam_val['examination_id']);
                                        // End Frequency
                                        $header_path = self::getFilePath($getSpecilistDocument['header_image_path']);
                                        $footer_path = self::getFilePath($getSpecilistDocument['footer_image_path']);
                                        // dump($header_path);
                                        // dd($footer_path);
                                        if(!empty($l_date))
                                        {


                                           /* $str .= '<div style="width: 100%;"> 
                                            <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                            if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                            {
                                                $str .= '<img style="width: 100%;height: auto;" src="'.$header_path.'" >';
                                            }
                                          $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                          if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                          {
                                            $str .= '<img style="width: 100%;height:auto;" src="'.$footer_path.'" >';
                                          }
                                          $str .= '</div>
                                          <div>
                                           
                                            <p>Ihr pureGyn Team</p>
                                          </div>
                                        </div>';*/

                                         $str .= '<div style="width: 100%;"> 
                                            <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                            if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                            {
                                                $str .= '<img style="width: 100%;height: auto;" src="'.$header_path.'" >';
                                            }
                                          $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                         
                                          $str .= '</div><br/>
                                              <div style="margin-left: 52px;">
                                                <p>Ihr pureGyn Team</p>
                                              </div>';

                                           if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                          {
                                            $str .= '<img style="width: 100%;height:auto;" src="'.$footer_path.'" >';
                                          }    
                                          $str .= '</div>';


                                   
                                            $getdocrecord =self::saveGeneralDocument($getSpecilistDocument['id'],$getAppointment,'service',$l_date,$exam_val['examination_id']);
                                            $service_doc[$examdoc_key]['Html']  = $str;
                                           $service_doc[$examdoc_key]['examination_id']  = $exam_val['examination_id'];
                                           $service_doc[$examdoc_key]['doc_id']  = $getdocrecord->id;
                                           $service_doc[$examdoc_key]['doc_status']  = $getdocrecord->doc_status;
                                           $service_doc[$examdoc_key]['type']  = 'service';
                                           $service_doc[$examdoc_key]['name']  = $getSpecilistDocument['name'];
                                           $service_doc[$examdoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($getSpecilistDocument->created_at));
                                           $str = '';
                                        }
                                    } 
                                }
                                $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc); 
                                $service_doc = [];
                                $str = '';
                                
                            }                     
                        }
                    }                     
                   
                } 
               

                $status  = true;
                if((!empty($finalSeviceDoc) && sizeof($finalSeviceDoc) > 0)){
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $finalSeviceDoc;
                    // self::_createLog('getAppointmentTypeDetail',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }else{
                    $data  = [];
                    $message = __('api.ERR_NOT_FOUND');
                }
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(),
                  ];
                //self::_createLog('getAppointmentTypeDetail',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

   
    
    public function saveGeneralDocument($doc_id,$getAppointment,$type,$last_date,$exam_id)
    {
        // dump($type,$getAppointment->patient_id,$getAppointment->id,$getAppointment->appointment_type_id);
        if($type == 'general')
        {
            $existRec = $this->PatientHasDocumentsModel
                         ->where('type',$type)
                         ->where('patient_id',$getAppointment->patient_id)
                         ->where('appointment_id',$getAppointment->id)
                         ->where('exam_app_type_id',$getAppointment->appointment_type_id)
                         ->where('record_type',0)
                         ->where('fk_document_id',$doc_id)
                         ->first();
            $record_type = 0;             
        }
        else
        {
            $existRec = $this->PatientHasDocumentsModel
                         ->where('type','service')
                         ->where('patient_id',$getAppointment->patient_id)
                         ->where('appointment_id',$getAppointment->id)
                         ->where('exam_app_type_id',$getAppointment->appointment_type_id)
                         ->where('record_type',1)
                         ->where('fk_document_id',$doc_id)
                         ->first();

            $record_type = 1; 
        }
      
        if(!empty($existRec))
        {
            $getrecord = $this->PatientHasDocumentsModel->find($existRec->id);
            $getrecord->activation_start_date = Date('Y-m-d H:i:s');
            $getrecord->activation_last_date  = $last_date;
            // $getrecord->doc_status            ='0';// commented on 24-nov-23
             $getrecord->doc_status            =$getrecord->doc_status; // added on 24-nov-23
            $getrecord->record_type      = $record_type;
            $getrecord->fk_document_id   = $doc_id;
            $getrecord->save();
            $id = $getrecord->id;
        } 
        else
        {
            $getrecord = new PatientHasDocumentsModel;
            $getrecord->appointment_id   = $getAppointment->id;
            $getrecord->patient_id       = $getAppointment->patient_id;
            $getrecord->exam_app_type_id = $getAppointment->appointment_type_id;
            $getrecord->fk_examinations_id = $exam_id;
            $getrecord->fk_document_id   = $doc_id;
            $getrecord->record_type      = $record_type;
             $getrecord->doc_status    = 0;  // commented on 24-nov-23
            //$getrecord->doc_status       =$getrecord->doc_status; // added on 24-nov-23
            $getrecord->type             = $type;
            $getrecord->activation_start_date  = Date('Y-m-d H:i:s');
            $getrecord->activation_last_date   = $last_date;
            $getrecord->save();
            $id = $getrecord->id;
        } 
        return $getrecord ;
    }
    public function getAppointmentDocumentsQRCode(Request $request)
    {
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $appointmentId  = $request->appointment_id;

        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                          'appointment_id'=> 'required'
                        ], 
                        [
                          'appointment_id.required'=>__('api.APPOINTMENT_ID_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
            $errors[] = $validator->errors(); 
        }else
        {
            try{

                $collections = collect([]);  
                $appointment_type_docs = collect([]);  
                $appointment_exams_docs = collect([]);  

                $appointment_type_docs = $this->AppointmentTypesModel->with('hasPatientDocuments')
                                                ->join('appointment','appointment.appointment_type_id','=','appointment_types.id')
                                                ->where('appointment.id','=',$appointmentId)
                                                ->whereNotNull('appointment_types.patient_document_path')
                                                // ->where('appointment_types.recommend_exams','=','0')
                                                ->get([
                                                    'appointment_types.id',
                                                    'appointment_types.patient_document as document_name',
                                                    'appointment_types.patient_document_path as document_path',
                                                    //'appointment_types.recommend_exams',
                                                   // 'appointment_types.patient_document_status'
                                                    ]);

                // dd($appointmentId,$appointment_type_docs->toArray());
                $appointment_exams_docs = $this->ExaminationsModel->with('hasPatientDocuments')
                                                ->join('appointment_has_examinations','appointment_has_examinations.examination_id','=','examinations.id')
                                                ->where('appointment_has_examinations.appointment_id','=',$appointmentId)
                                                ->whereNotNull('examinations.document_path')
                                                ->get([
                                                    'examinations.id',
                                                    'examinations.document_name',
                                                    'examinations.document_path',
                                                    // '"exams" as record_type'
                                                    ]); 
                // dd($appointment_type_docs->toArray(),$appointment_exams_docs->toArray());
               
                if((!empty($appointment_type_docs) && sizeof($appointment_type_docs) > 0)){

                    $appointment_type_docs = $appointment_type_docs->map(function($item) use($appointmentId)
                                {
                                    $item->record_type = 'appointment_types';
                                    
                                    $item->doc_status = 0;
                                    if(!empty($item->hasPatientDocuments) && sizeof($item->hasPatientDocuments)>0){
                                        foreach ($item->hasPatientDocuments as $hasPatientDocument) {
                                            if($hasPatientDocument->appointment_id==$appointmentId){
                                                $item->doc_status = $hasPatientDocument->doc_status;
                                                break;
                                            }
                                        }
                                        // $item->doc_status = $item->hasPatientDocuments[0]->doc_status;
                                    }

                                    $doc_path = '';
                                    $new_doc_path = self::StorePath($item->document_path.'/');
                                    
                                    if (!empty($item->document_path)) 
                                    {
                                        $doc_path = self::getFilePath($item->document_path);
                                        //$doc_path = url('/storage'.$item->document_path); 
                                    }
                                    $item->document_path = $doc_path;
                                    return $item;
                                });

                     

                }

                if((!empty($appointment_exams_docs) && sizeof($appointment_exams_docs) > 0)){

                    $appointment_exams_docs = $appointment_exams_docs->map(function($item) use($appointmentId)
                                {
                                    $item->record_type = 'exams';

                                    $item->doc_status = 0;
                                    if(!empty($item->hasPatientDocuments) && sizeof($item->hasPatientDocuments)>0){
                                       // $item->doc_status = $item->hasPatientDocuments[0]->doc_status;
                                        foreach ($item->hasPatientDocuments as $hasPatientDocument) {
                                            if($hasPatientDocument->appointment_id==$appointmentId){
                                                $item->doc_status = $hasPatientDocument->doc_status;
                                                break;
                                            }
                                        }
                                    }

                                    // $doc_path = '';
                                    // if (!empty($item->document_path) && is_file(storage_path().$item->document_path)) 
                                    // {
                                    //     $doc_path = url('/storage'.$item->document_path); 
                                    // }
                                    $doc_path = '';
                                    $new_doc_path = self::StorePath($item->document_path.'/');
                                    
                                    if (!empty($item->document_path)) 
                                    {
                                        $doc_path = self::getFilePath($item->document_path);
                                        //$doc_path = url('/storage'.$item->document_path); 
                                    }
                                    $item->document_path = $doc_path;
                                    return $item;
                                });

                
                }
                
                $collections = $appointment_type_docs->merge($appointment_exams_docs);

                 // dd($appointmentId,$appointment_exams_docs,$appointment_type_docs);

                $status  = true;
                if((!empty($collections) && sizeof($collections) > 0)){
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collections;
                    // self::_createLog('getAppointmentTypeDetail',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }else{
                    $data  = [];
                    $message = __('api.ERR_NOT_FOUND');
                }
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(),
                  ];
                //self::_createLog('getAppointmentTypeDetail',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

     public function saveGeneralDocumentNew($doc_id,$getAppointment,$type,$last_date,$exam_id)
    {
        // dump($type,$getAppointment->patient_id,$getAppointment->id,$getAppointment->appointment_type_id);
        if($type == 'general')
        {
            $existRec = $this->PatientHasDocumentsModel
                         ->where('type',$type)
                         ->where('patient_id',$getAppointment->patient_id)
                         ->where('appointment_id',$getAppointment->id)
                         ->where('exam_app_type_id',$getAppointment->appointment_type_id)
                         ->where('record_type',0)
                         ->where('fk_document_id',$doc_id)
                         ->first();
            $record_type = 0;             
        }
        else
        {
            $existRec = $this->PatientHasDocumentsModel
                         ->where('type','service')
                         ->where('patient_id',$getAppointment->patient_id)
                         ->where('appointment_id',$getAppointment->id)
                         ->where('exam_app_type_id',$getAppointment->appointment_type_id)
                         ->where('record_type',1)
                         ->where('fk_document_id',$doc_id)
                         ->first();

            $record_type = 1; 
        }
      
        if(!empty($existRec))
        {
            $getrecord = $this->PatientHasDocumentsModel->find($existRec->id);
            $getrecord->activation_start_date = Date('Y-m-d H:i:s');
            $getrecord->activation_last_date  = $last_date;
            //$getrecord->doc_status            ='0';
            $getrecord->record_type      = $record_type;
            $getrecord->fk_document_id   = $doc_id;
            $getrecord->save();
            $id = $getrecord->id;
        } 
        else
        {
            $getrecord = new PatientHasDocumentsModel;
            $getrecord->appointment_id   = $getAppointment->id;
            $getrecord->patient_id       = $getAppointment->patient_id;
            $getrecord->exam_app_type_id = $getAppointment->appointment_type_id;
            $getrecord->fk_examinations_id = $exam_id;
            $getrecord->fk_document_id   = $doc_id;
            $getrecord->record_type      = $record_type;
           //$getrecord->doc_status       = 0;
            $getrecord->type             = $type;
            $getrecord->activation_start_date  = Date('Y-m-d H:i:s');
            $getrecord->activation_last_date   = $last_date;
            $getrecord->save();
            $id = $getrecord->id;
        } 
        return $getrecord ;
    }

    public function getDocumentsQRCode(Request $request)
    {
        Log::info('in getDocumentsQRCode....');

        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $appointmentId  = $request->appointment_id;

        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                          'appointment_id'=> 'required'
                        ], 
                        [
                          'appointment_id.required'=>__('api.APPOINTMENT_ID_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
            $errors[] = $validator->errors(); 
        }else
        {
            try{

                $collections = collect([]);  
                $appointment_type_docs = collect([]);  
                $appointment_exams_docs = collect([]);  
                $service_doc = $finalSeviceDoc = [];
                $data_collection  = null;
                $str = '';
                // Service Document
                $getAppointment = $this->BaseModel->find($appointmentId);
                //dd($getAppointment);

                  Log::info('in getAppointment....');
                   Log::info($getAppointment);

                if(!empty($getAppointment))
                {
                    // GENERAL DOCUMENT
                    $getGeneralDocument = $this->SpecialistDocumentsModel
                               ->where('status','1')
                               ->where('type_of_document','general')
                               ->get();

                     Log::info('in getGeneralDocument....');
                     Log::info($getGeneralDocument);

                    if(!empty($getGeneralDocument) && sizeof($getGeneralDocument)>0)
                    {
                        foreach ($getGeneralDocument as $generalDoc_key => $generalDoc_val) 
                        {
                            $getSpecilistDocument = $this->SpecialistDocumentsModel
                                                   ->where('id',$generalDoc_val['id'])
                                                   ->first();

                               Log::info('in getSpecilistDocument....');   
                               Log::info($getSpecilistDocument);                   

                            if(!empty($getSpecilistDocument))
                            {
                               Log::info('in not empty getSpecilistDocument....');   
                               Log::info($getAppointment->patient_id);   
                               Log::info($appointmentId); 
                                Log::info($getSpecilistDocument);   

                                $l_date = self::checkFrequency($getAppointment->patient_id,$appointmentId,$getSpecilistDocument,null);

                                 Log::info('in l_date...'); 
                                 Log::info($l_date);   
                           
                                if(!empty($l_date))
                                {
                                    //dump($getSpecilistDocument['id'],$getAppointment);
                                    $getdocrecord = self::saveGeneralDocumentNew($getSpecilistDocument['id'],$getAppointment,'general',$l_date,null);
                                    $header_path_gen = self::getFilePath($getSpecilistDocument['header_image_path']);
                                    //dump($header_path_gen);
                                    $footer_path_gen = self::getFilePath($getSpecilistDocument['footer_image_path']);
                                    //dump($header_path_gen);

                                    //commented below on 17oct22
                                   /* $str .= '<div style="width: 100%;"> 
                                      <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                      if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                      {
                                        $str .= '<img style="width: 100%;height:auto;" src="'.$header_path_gen.'" >';
                                      }
                                        $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                        if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                        {
                                          $str .= '<img style="width: 100%;height:auto;" src="'.$footer_path_gen.'" >';
                                        }
                                      $str .= '</div>
                                      <div>                                       
                                        <p>Ihr pureGyn Team</p>
                                      </div>
                                    </div>';*/

                                     $str .= '<div style="width: 100%;"> 
                                      <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                      if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                      {
                                        $str .= '<img style="width: 100%;height:auto;" src="'.$header_path_gen.'" >';
                                      }
                                        $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                        
                                      $str .= '</div><br/>
                                      <div style="margin-left: 52px;">                                       
                                        <p>Ihr pureGyn Team</p>
                                      </div>';

                                     if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                        {
                                          $str .= '<img style="width: 100%;height:auto;" src="'.$footer_path_gen.'" >';
                                        } 

                                     $str .= '</div>';

                                    $service_doc[$generalDoc_key]['Html']  = $str;
                                    $service_doc[$generalDoc_key]['examination_id']  = null;
                                    $service_doc[$generalDoc_key]['doc_id']  = $getdocrecord->id;
                                    $service_doc[$generalDoc_key]['doc_status']  = $getdocrecord->doc_status;
                                    $service_doc[$generalDoc_key]['type']    = 'general';
                                    $service_doc[$generalDoc_key]['name']    = $getSpecilistDocument['name'];
                                    $service_doc[$generalDoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($getSpecilistDocument->created_at));
                                    $str = '';
                                }
                            }                     
                            
                        }
                        
                        $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
                        $service_doc = []; 
                        $str = '';
                    }
                    // END GENERAL DOCUMENT

                    // $getExamDocument = $this->AppointmentTypeHasExaminationsModel
                    //                     ->where('appoinment_id',$getAppointment->appointment_type_id)
                    //                     ->get();
                    $getExamDocument = $this->AppointmentHasExaminationsModel
                                       ->where('appointment_id',$appointmentId) 
                                       ->where('patient_id',$getAppointment->patient_id) //added on 28-march-24
                                        ->get(); 
                    // dump($getExamDocument);

                      Log::info(' getExamDocument....'); 
                       Log::info($getExamDocument);                  

                    if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                    {
                        foreach ($getExamDocument as $exam_key => $exam_val) 
                        {
                            $getExamDocument = $this->ExaminationsHasMultipleDocumentListModel
                                                 ->where('fk_examinations_id',$exam_val['examination_id'])
                                                 ->get();
                            //dd($getExamDocument);      

                               Log::info(' in loop getExamDocument....'); 
                               Log::info($getExamDocument);                  

                            if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                            {
                                 Log::info(' in not empty getExamDocument....'); 

                                foreach ($getExamDocument as $examdoc_key => $examdoc_val) 
                                {
                                    $getSpecilistDocument = $this->SpecialistDocumentsModel
                                                           ->where('status','1')
                                                           ->where('type_of_document','service')
                                                           ->where('id',$examdoc_val['fk_document_list_id'])
                                                           ->first();
                                    //dd($getSpecilistDocument);    

                                     Log::info(' in loop getSpecilistDocument....'); 
                                    Log::info($getSpecilistDocument);                        


                                    if(!empty($getSpecilistDocument))   
                                    {
                                          Log::info(' in not empty getSpecilistDocument....');

                                        // check Frequency generalDoc_key
                                        $l_date = self::checkFrequency($getAppointment->patient_id,$appointmentId,$getSpecilistDocument,$exam_val['examination_id']);

                                        Log::info('l_date..');
                                        Log::info($l_date);

                                        // End Frequency
                                        $header_path = self::getFilePath($getSpecilistDocument['header_image_path']);
                                        $footer_path = self::getFilePath($getSpecilistDocument['footer_image_path']);
                                        // dump($header_path);
                                        //dd($l_date);
                                        if(!empty($l_date))
                                        {

                                              Log::info(' in not empty l_date..');

                                            //commented on 17oct22
                                           /* $str .= '<div style="width: 100%;"> 
                                            <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                            if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                            {
                                                $str .= '<img style="width: 100%;height: auto;" src="'.$header_path.'" >';
                                            }
                                          $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                          if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                          {
                                            $str .= '<img style="width: 100%;height:auto;" src="'.$footer_path.'" >';
                                          }
                                          $str .= '</div>
                                          <div>
                                           
                                            <p>Ihr pureGyn Team</p>
                                          </div>
                                         </div>';*/


                                          $str .= '<div style="width: 100%;"> 
                                            <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                            if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                            {
                                                $str .= '<img style="width: 100%;height: auto;" src="'.$header_path.'" >';
                                            }
                                          $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                         
                                          $str .= '</div><br/>
                                          <div style="margin-left: 52px;">                                           
                                            <p>Ihr pureGyn Team</p>
                                          </div>';

                                          if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                          {
                                            $str .= '<img style="width: 100%;height:auto;" src="'.$footer_path.'" >';
                                          }
                                         $str .='</div>';

                                   
                                            $getdocrecord =self::saveGeneralDocumentNew($getSpecilistDocument['id'],$getAppointment,'service',$l_date,$exam_val['examination_id']);


                                            Log::info('after saveGeneralDocumentNew ..');
                                            Log::info($getdocrecord);

                                            $service_doc[$examdoc_key]['Html']  = $str;
                                           $service_doc[$examdoc_key]['examination_id']  = $exam_val['examination_id'];
                                           $service_doc[$examdoc_key]['doc_id']  = $getdocrecord->id;
                                           $service_doc[$examdoc_key]['doc_status']  = $getdocrecord->doc_status;
                                           $service_doc[$examdoc_key]['type']  = 'service';
                                           $service_doc[$examdoc_key]['name']  = $getSpecilistDocument['name'];
                                           $service_doc[$examdoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($getSpecilistDocument->created_at));
                                           $str = '';
                                        }
                                        
                                    } 

                                }
                                $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc); 
                                $service_doc = [];
                                $str = '';
                                
                            }                     
                        }
                    }                    
                   
                } 
               

                $status  = true;
                if((!empty($finalSeviceDoc) && sizeof($finalSeviceDoc) > 0)){
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $finalSeviceDoc;
                    // self::_createLog('getAppointmentTypeDetail',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }else{
                    $data  = [];
                    $message = __('api.ERR_NOT_FOUND');
                }
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(),
                  ];
                //self::_createLog('getAppointmentTypeDetail',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    }


    public function testgetDocumentsQRCode(Request $request)
    {
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $appointmentId  = $request->appointment_id;

        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                          'appointment_id'=> 'required'
                        ], 
                        [
                          'appointment_id.required'=>__('api.APPOINTMENT_ID_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
            $errors[] = $validator->errors(); 
        }else
        {
            try{

                $collections = collect([]);  
                $appointment_type_docs = collect([]);  
                $appointment_exams_docs = collect([]);  
                $service_doc = $finalSeviceDoc = [];
                $data_collection  = null;
                $str = '';
                // Service Document
                $getAppointment = $this->BaseModel->find($appointmentId);
              
                if(!empty($getAppointment))
                {
                    // GENERAL DOCUMENT
                    $getGeneralDocument = $this->SpecialistDocumentsModel
                               ->where('status','1')
                               ->where('type_of_document','general')
                               ->get();

                    if(!empty($getGeneralDocument) && sizeof($getGeneralDocument)>0)
                    {
                        foreach ($getGeneralDocument as $generalDoc_key => $generalDoc_val) 
                        {
                            $getSpecilistDocument = $this->SpecialistDocumentsModel
                                                   ->where('id',$generalDoc_val['id'])
                                                   ->first();
                            if(!empty($getSpecilistDocument))
                            {
                                $l_date = self::checkFrequency($getAppointment->patient_id,$appointmentId,$getSpecilistDocument,null);
                           
                                if(!empty($l_date))
                                {
                                    //dump($getSpecilistDocument['id'],$getAppointment);
                                    $getdocrecord = self::saveGeneralDocument($getSpecilistDocument['id'],$getAppointment,'general',$l_date,null);
                                    $header_path_gen = self::getFilePath($getSpecilistDocument['header_image_path']);
                                    //dump($header_path_gen);
                                    $footer_path_gen = self::getFilePath($getSpecilistDocument['footer_image_path']);
                                    //dump($header_path_gen);
                                    $str .= '<div style="width: 100%;"> 
                                      <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                      if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                      {
                                        $str .= '<img style="width: 100%;height: 100px;" src="'.$header_path_gen.'" >';
                                      }
                                        $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                        if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                        {
                                          $str .= '<img style="width: 100%;height: 70px;" src="'.$footer_path_gen.'" >';
                                        }
                                      $str .= '</div>
                                      <div>
                                        <p>Herzlichen Dank fur lhre Unterstutzung</p>
                                        <p>Ihr pureGyn Team</p>
                                      </div>
                                    </div>';

                                    $service_doc[$generalDoc_key]['Html']  = $str;



                                    $service_doc[$generalDoc_key]['examination_id']  = null;
                                    $service_doc[$generalDoc_key]['doc_id']  = $getdocrecord->id;
                                    $service_doc[$generalDoc_key]['doc_status']  = $getdocrecord->doc_status;
                                    $service_doc[$generalDoc_key]['type']    = 'general';
                                    $service_doc[$generalDoc_key]['name']    = $getSpecilistDocument['name'];
                                    $service_doc[$generalDoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($getSpecilistDocument->created_at));
                                    $str = '';
                                }
                            }                     
                            
                        }
                        
                        $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
                        $service_doc = []; 
                        $str = '';
                    }
                    // END GENERAL DOCUMENT

                    $getExamDocument = $this->AppointmentTypeHasExaminationsModel
                                        ->where('appoinment_id',$getAppointment->appointment_type_id)
                                        ->get();
                    // $getExamDocument = $this->AppointmentHasExaminationsModel
                    //                    ->where('appointment_id',$appointmentId) 
                    //                     ->get(); 
                    //dd($getExamDocument);
                    if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                    {
                        foreach ($getExamDocument as $exam_key => $exam_val) 
                        {
                            $getExamDocument = $this->ExaminationsHasMultipleDocumentListModel
                                                 ->where('fk_examinations_id',$exam_val['examination_id'])
                                                 ->get();
                            //dd($getExamDocument);                     
                            if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                            {
                                foreach ($getExamDocument as $examdoc_key => $examdoc_val) 
                                {
                                    $getSpecilistDocument = $this->SpecialistDocumentsModel
                                                           ->where('status','1')
                                                           ->where('type_of_document','service')
                                                           ->where('id',$examdoc_val['fk_document_list_id'])
                                                           ->first();
                                    //dd($getSpecilistDoc);                       
                                    if(!empty($getSpecilistDocument))   
                                    {
                                        // check Frequency generalDoc_key
                                        $l_date = self::checkFrequency($getAppointment->patient_id,$appointmentId,$getSpecilistDocument,$exam_val['examination_id']);
                                        // End Frequency
                                        $header_path = self::getFilePath($getSpecilistDocument['header_image_path']);
                                        $footer_path = self::getFilePath($getSpecilistDocument['footer_image_path']);
                                        // dump($header_path);
                                        // dd($footer_path);
                                        if(!empty($l_date))
                                        {
                                            $str .= '<div style="width: 100%;"> 
                                            <div  style="background-color: '.$getSpecilistDocument['background_color'].'">';
                                            if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
                                            {
                                                $str .= '<img style="width: 100%;height: 100px;" src="'.$header_path.'" >';
                                            }
                                          $str .= '<div style="margin-left: 52px;margin-right: 20px;">
                                            <h4>'.ucfirst($getSpecilistDocument['name']).'</h4>
                                            <p>'.ucfirst($getSpecilistDocument['html_text']).'</p>
                                          </div>';
                                          if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
                                          {
                                            $str .= '<img style="width: 100%;height: 70px;" src="'.$footer_path.'" >';
                                          }
                                          $str .= '</div>
                                          <div>
                                            <p>Herzlichen Dank fur lhre Unterstutzung</p>
                                            <p>Ihr pureGyn Team</p>
                                          </div>
                                        </div>';
                                   
                                            $getdocrecord =self::saveGeneralDocument($getSpecilistDocument['id'],$getAppointment,'service',$l_date,$exam_val['examination_id']);
                                            $service_doc[$examdoc_key]['Html']  = $str;
                                           $service_doc[$examdoc_key]['examination_id']  = $exam_val['examination_id'];
                                           $service_doc[$examdoc_key]['doc_id']  = $getdocrecord->id;
                                           $service_doc[$examdoc_key]['doc_status']  = $getdocrecord->doc_status;
                                           $service_doc[$examdoc_key]['type']  = 'service';
                                           $service_doc[$examdoc_key]['name']  = $getSpecilistDocument['name'];
                                           $service_doc[$examdoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($getSpecilistDocument->created_at));
                                           $str = '';
                                        }
                                        
                                    } 

                                }
                                $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc); 
                                $service_doc = [];
                                $str = '';
                                
                            }                     
                        }
                    }                    
                   
                } 
               

                $status  = true;
                if((!empty($finalSeviceDoc) && sizeof($finalSeviceDoc) > 0)){
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $finalSeviceDoc;
                    // self::_createLog('getAppointmentTypeDetail',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }else{
                    $data  = [];
                    $message = __('api.ERR_NOT_FOUND');
                }
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(),
                  ];
                //self::_createLog('getAppointmentTypeDetail',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    }
    public function checkFrequency($patient_id,$appointment_id,$getDocument,$exam_id)
    {  
        //dd($getDocument['date_of_last_activation']);
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
        
        if(!empty($exam_id))
        {
            $patientHasDoc = $this->PatientHasDocumentsModel
                             ->where('appointment_id',$appointment_id)
                             ->where('patient_id',$patient_id)
                             ->where('fk_document_id',$getDocument['id'])
                             ->where('fk_examinations_id',$exam_id)
                             ->first();
        }
        else
        {
            $patientHasDoc = $this->PatientHasDocumentsModel
                            ->where('appointment_id',$appointment_id)
                            ->where('patient_id',$patient_id)
                            ->where('fk_document_id',$getDocument['id'])
                            ->first();
        }
        
        // ----------------------------------------------------------
        //dd($patientHasDoc);
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
                //dd($flag);
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
            //dd($days);
            if(!empty($days) || $days == 0)
            {
                //dd($days);
                $duration  = (int)$days;
                //dump($duration);
                $last_date = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +".$duration." day");
                //dump($last_date);
                $l_date    = Date('Y-m-d H:i:s',$last_date);
            }
        } 
        // dd($l_date);
        return $l_date;
    }

    // public function updateDocumentRead(Request $request)
    // {
    //     $errors     = [];  
    //     $data       = []; 
    //     $message    = __('api.ERR_NOT_FOUND'); 
    //     $status     = false;

    //     $inputdata  = $request->all();
    //     $validator  = Validator::make($inputdata,[
    //                       'patient_id'      => 'required',
    //                       'appointment_id'      => 'required',
    //                       'exam_app_type_id'=> 'required',
    //                       'record_type'     => 'required',
    //                       'doc_status'      => 'required',
    //                     ], 
    //                     [
    //                       'patient_id.required'         =>__('api.AUTH_PATIENT_ID_REQ'),     
    //                       'appointment_id.required'     =>__('api.ERR_APPOINTMENT_REQ'),     
    //                       'exam_app_type_id.required'   =>__('api.APPOINTMENT_ID_REQ'),     
    //                       'record_type.required'        =>__('api.RECORD_TYPE_REQ'),     
    //                       'doc_status.required'         =>__('api.DOC_STATUS_REQ'),     
    //                     ]
    //                     ); 

    //     if ($validator->fails()) 
    //     {           
    //         $errors[] = $validator->errors(); 
    //     }else
    //     {
    //         try{

    //             $record_exist = $this->PatientHasDocumentsModel
    //                                  ->where('patient_id','=',$inputdata['patient_id'])
    //                                  ->where('appointment_id','=',$inputdata['appointment_id'])
    //                                  ->where('exam_app_type_id','=',$inputdata['exam_app_type_id'])
    //                                  ->where('record_type','=',$inputdata['record_type'])
    //                                  ->first(['id']);

    //             if(!empty($record_exist)){
    //                 //update
    //                 //dd('update');
    //                 $id = $record_exist->id;
    //                 $PatientHasDocumentsModel = $this->PatientHasDocumentsModel->find($id);
    //                 $PatientHasDocumentsModel->doc_status       = $inputdata['doc_status'];

    //                 if($PatientHasDocumentsModel->save()){
    //                     $status  = true;
    //                     $message = __('api.DATA_FOUND_SUCCESS');
    //                     $data  = $PatientHasDocumentsModel;
    //                     // self::_createLog('getAppointmentTypeDetail',array($data),'info');
    //                     // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

    //                 }else{
    //                     $message = __('api.ERR_NOT_FOUND');
    //                 }

    //             }else{
    //                 $message = __('api.ERR_NOT_FOUND');
    //             }
    //             /*else{
    //                 //insert
    //                //dd('insert');
    //                 $PatientHasDocumentsModel = new $this->PatientHasDocumentsModel;
    //             }*/
    //             /*if(!empty($inputdata['appointment_id'])){
    //                 $PatientHasDocumentsModel->appointment_id   = $inputdata['appointment_id'];
    //             }else{
    //                 $PatientHasDocumentsModel->appointment_id   = 0;
    //             }
    //             $PatientHasDocumentsModel->patient_id       = $inputdata['patient_id'];
    //             $PatientHasDocumentsModel->exam_app_type_id = $inputdata['exam_app_type_id'];
    //             $PatientHasDocumentsModel->record_type      = $inputdata['record_type'];
    //             $PatientHasDocumentsModel->doc_status       = $inputdata['doc_status'];*/
    //             //dd($PatientHasDocumentsModel);
                
    //         }
    //         catch(\Exception $e) {
    //             $message = __('api.ERR_SOMETHING_WRONG');
    //             $errors[] = [
    //                   "error" => $e->getMessage(),
    //               ];
    //             //self::_createLog('getAppointmentTypeDetail',$errors,'error');
    //             // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    //         }
    //     }
    //    return self::_sendResult($message,$data,$errors,$status);
    // }

    public function _createGeneralDocumentPdf($doc_details,$id)
    {

        $data = $dataFinal = [];
        $flag = '0';
        $file_name ='';
      
        $collections = $this->SpecialistDocumentsModel->find($doc_details->fk_document_id);
                
        if(!empty($collections))
        {    
            $data['doc_id']            = $collections->id;
            $data['name']              = $collections->name;
            $data['html_text']         = $collections->html_text;
            $data['background_color']  = $collections->background_color;
            $data['header_image']      = $collections->header_image;
            $data['header_image_path'] = $collections->header_image_path;
            $data['footer_image']      = $collections->footer_image;
            $data['footer_image_path'] = $collections->footer_image_path;
            $data['background_color']  = $collections->background_color;
            $data['signature']         = $doc_details->remarks;
            
            //$PdfPath = self::StorePath('document_pdf/');
            if(!empty(Config('ordination_id')))
            {
                $getDatabaseName = DB::connection('system')
                            ->table("websites")
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
    }

    public function updateDocumentRead(Request $request)
    {
        //dd($request->all());
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                          'doc_id'      => 'required',
                        ], 
                        [
                          'doc_id.required'         =>__('api.AUTH_DOC_Id_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
            $errors[] = $validator->errors(); 
        }else
        {
            try{
                // $record_exist = $this->PatientHasDocumentsModel
                //                  ->where('patient_id','=',$inputdata['patient_id'])
                //                  ->where('appointment_id','=',$inputdata['appointment_id'])
                //                  ->where('exam_app_type_id','=',$inputdata['exam_app_type_id'])
                //                  ->where('record_type','=',$inputdata['record_type'])
                //                  ->where('fk_examinations_id','=',$inputdata['examinations_id'])
                //                  ->type('type',$inputdata['type'])
                //                  ->first(['id']);
                $record_exist = $this->PatientHasDocumentsModel
                                 ->where('id','=',$inputdata['doc_id'])
                                 ->first(['id']);
               
                if(!empty($record_exist))
                {
                    //update
                    //dd('update');
                    $id = $record_exist->id;
                    $PatientHasDocumentsModel = $this->PatientHasDocumentsModel->find($id);
                    $PatientHasDocumentsModel->doc_status   = $inputdata['doc_status'];

                    if($PatientHasDocumentsModel->save())
                    {
                        self::_createGeneralDocumentPdf($PatientHasDocumentsModel,$id);
                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        //$data  = $PatientHasDocumentsModel;
                        $data['id']  = $PatientHasDocumentsModel->id;
                        $data['appointment_id']  = $PatientHasDocumentsModel->appointment_id;
                        $data['patient_id']  = $PatientHasDocumentsModel->patient_id;
                        $data['exam_app_type_id']  = $PatientHasDocumentsModel->exam_app_type_id;
                        $data['doc_status']  = $inputdata['doc_status'];
                        $data['type']  = $PatientHasDocumentsModel->type;
                      

                        // self::_createLog('getAppointmentTypeDetail',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                    }
                    else
                    {
                        $message = __('api.ERR_NOT_FOUND');
                    }

                }else{
                    $message = __('api.ERR_NOT_FOUND');
                }
                /*else{
                    //insert
                   //dd('insert');
                    $PatientHasDocumentsModel = new $this->PatientHasDocumentsModel;
                }*/
                /*if(!empty($inputdata['appointment_id'])){
                    $PatientHasDocumentsModel->appointment_id   = $inputdata['appointment_id'];
                }else{
                    $PatientHasDocumentsModel->appointment_id   = 0;
                }
                $PatientHasDocumentsModel->patient_id       = $inputdata['patient_id'];
                $PatientHasDocumentsModel->exam_app_type_id = $inputdata['exam_app_type_id'];
                $PatientHasDocumentsModel->record_type      = $inputdata['record_type'];
                $PatientHasDocumentsModel->doc_status       = $inputdata['doc_status'];*/
                //dd($PatientHasDocumentsModel);
                
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(),
                  ];
                //self::_createLog('getAppointmentTypeDetail',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function testupdateDocumentReadQrcode(Request $request)
    {
        //dd($request->all());
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                          'doc_id'      => 'required',
                        ], 
                        [
                          'doc_id.required'         =>__('api.AUTH_DOC_Id_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
            $errors[] = $validator->errors(); 
        }else
        {
            try{
                // $record_exist = $this->PatientHasDocumentsModel
                //                  ->where('patient_id','=',$inputdata['patient_id'])
                //                  ->where('appointment_id','=',$inputdata['appointment_id'])
                //                  ->where('exam_app_type_id','=',$inputdata['exam_app_type_id'])
                //                  ->where('record_type','=',$inputdata['record_type'])
                //                  ->where('fk_examinations_id','=',$inputdata['examinations_id'])
                //                  ->type('type',$inputdata['type'])
                //                  ->first(['id']);
                $record_exist = $this->PatientHasDocumentsModel
                                 ->where('id','=',$inputdata['doc_id'])
                                 ->first(['id']);
               
                if(!empty($record_exist))
                {
                    //update
                    //dd('update');
                    $id = $record_exist->id;
                    $PatientHasDocumentsModel = $this->PatientHasDocumentsModel->find($id);
                    $PatientHasDocumentsModel->doc_status   = $inputdata['doc_status'];

                    if($PatientHasDocumentsModel->save())
                    {
                        self::_createGeneralDocumentPdf($PatientHasDocumentsModel,$id);
                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        //$data  = $PatientHasDocumentsModel;
                        $data['id']  = $PatientHasDocumentsModel->id;
                        $data['appointment_id']  = $PatientHasDocumentsModel->appointment_id;
                        $data['patient_id']  = $PatientHasDocumentsModel->patient_id;
                        $data['exam_app_type_id']  = $PatientHasDocumentsModel->exam_app_type_id;
                        $data['doc_status']  = $inputdata['doc_status'];
                        $data['type']  = $PatientHasDocumentsModel->type;
                      

                        // self::_createLog('getAppointmentTypeDetail',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                    }
                    else
                    {
                        $message = __('api.ERR_NOT_FOUND');
                    }

                }else{
                    $message = __('api.ERR_NOT_FOUND');
                }
                /*else{
                    //insert
                   //dd('insert');
                    $PatientHasDocumentsModel = new $this->PatientHasDocumentsModel;
                }*/
                /*if(!empty($inputdata['appointment_id'])){
                    $PatientHasDocumentsModel->appointment_id   = $inputdata['appointment_id'];
                }else{
                    $PatientHasDocumentsModel->appointment_id   = 0;
                }
                $PatientHasDocumentsModel->patient_id       = $inputdata['patient_id'];
                $PatientHasDocumentsModel->exam_app_type_id = $inputdata['exam_app_type_id'];
                $PatientHasDocumentsModel->record_type      = $inputdata['record_type'];
                $PatientHasDocumentsModel->doc_status       = $inputdata['doc_status'];*/
                //dd($PatientHasDocumentsModel);
                
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(),
                  ];
                //self::_createLog('getAppointmentTypeDetail',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function updateDocumentReadQrcode(Request $request)
    {
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                          'doc_id'      => 'required',
                        ], 
                        [
                          'doc_id.required'         =>__('api.AUTH_DOC_Id_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
            $errors[] = $validator->errors(); 
        }else
        {
            try{
                // $record_exist = $this->PatientHasDocumentsModel
                //                  ->where('patient_id','=',$inputdata['patient_id'])
                //                  ->where('appointment_id','=',$inputdata['appointment_id'])
                //                  ->where('exam_app_type_id','=',$inputdata['exam_app_type_id'])
                //                  ->where('record_type','=',$inputdata['record_type'])
                //                  ->where('fk_examinations_id','=',$inputdata['examinations_id'])
                //                  ->type('type',$inputdata['type'])
                //                  ->first(['id']);
                $record_exist = $this->PatientHasDocumentsModel
                                 ->where('id','=',$inputdata['doc_id'])
                                 ->first(['id']);
               
                if(!empty($record_exist))
                {
                    //update
                    //dd('update');
                    $id = $record_exist->id;
                    $PatientHasDocumentsModel = $this->PatientHasDocumentsModel->find($id);
                    $PatientHasDocumentsModel->doc_status       = $inputdata['doc_status'];

                    if($PatientHasDocumentsModel->save())
                    {
                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data  = $PatientHasDocumentsModel;
                        // self::_createLog('getAppointmentTypeDetail',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                    }
                    else
                    {
                        $message = __('api.ERR_NOT_FOUND');
                    }

                }else{
                    $message = __('api.ERR_NOT_FOUND');
                }
                /*else{
                    //insert
                   //dd('insert');
                    $PatientHasDocumentsModel = new $this->PatientHasDocumentsModel;
                }*/
                /*if(!empty($inputdata['appointment_id'])){
                    $PatientHasDocumentsModel->appointment_id   = $inputdata['appointment_id'];
                }else{
                    $PatientHasDocumentsModel->appointment_id   = 0;
                }
                $PatientHasDocumentsModel->patient_id       = $inputdata['patient_id'];
                $PatientHasDocumentsModel->exam_app_type_id = $inputdata['exam_app_type_id'];
                $PatientHasDocumentsModel->record_type      = $inputdata['record_type'];
                $PatientHasDocumentsModel->doc_status       = $inputdata['doc_status'];*/
                //dd($PatientHasDocumentsModel);
                
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(),
                  ];
                //self::_createLog('getAppointmentTypeDetail',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    } 

    //GET WAITING number 
    public function getWaitingNumber(Request $request) 
    {
        $errors    = []; 
        $data      = [];
        $message   = __('api.ERR_INVALID_DATA'); 
        $status    = false;

        $inputdata = $request->all();
        $validator = Validator::make($inputdata,[
                          'patient_id'   => 'required',
                        
                        ],  
                        [
                          'patient_id.required'=> __('api.ERR_FINDINGS_PATIENT_ID_REQ'),
                        ]
                        ); 

        if ($validator->fails()) {           
          $errors[] = $validator->errors(); 
        }else{

            $status = true;
            // Request Parameters
            $patientId   = $request->patient_id; 
            $today_date  = $request->today_date;

            if(empty($today_date)){
                $today_date =  date('Y-m-d H:i:s', strtotime(now()));
            }else{
                $today_date =  date('Y-m-d', strtotime($today_date))." ".date("H:i");
            }

            
            try{
                // Check Appointment of patient
                $getKeys = ['HOSPITAL_WAITING_MINUTES'];
                $getSettingData = $this->SettingsModel
                                    ->whereIn('setting_key', $getKeys)
                                    ->whereStatus(1)
                                    ->get();
                //dd($getSettingData);
                $settingData = [];                            
                foreach ($getSettingData as $key => $value) {
                   $settingData[$value->setting_key] = $value->setting_value;
                }
                //dd($settingData);
                $collection = $this->BaseModel
                                    ->join('users','appointment.doctor_id','=','users.id')
                                    ->join('patients','appointment.patient_id','=','patients.id')
                                    ->join('appointment_types','appointment.appointment_type_id','=','appointment_types.id')
                                    ->whereRaw('TIMESTAMPDIFF(MINUTE,CURRENT_TIMESTAMP,start_date) BETWEEN 0 AND '.$settingData['HOSPITAL_WAITING_MINUTES'])
                                    ->where('patient_id', $patientId)
                                    ->where('appointment.status',1)
                                    ->where('start_date','>=',$today_date)
                                    ->selectRaw('TIMESTAMPDIFF(MINUTE,CURRENT_TIMESTAMP,start_date) as mins,appointment.id as id,appointment.start_date as date,users.first_name as doctor_first_name,users.last_name as doctor_last_name,appointment_types.id as appointment_type_id,patients.id as patient_id,patients.first_name as patient_first_name,patients.family_name as patient_last_name')
                                    ->orderBy('start_date', 'ASC')
                                    ->get();
                //dd($today_date,$collection); 

                $totalRec = $collection->count();
                if($totalRec>0)
                { 
                    $appointment_id = $collection[0]->id;
                    $doctorName = $collection[0]->doctor_first_name." ".$collection[0]->doctor_last_name; 

                    $patientQueue = $this->AppointmentHasQueueNumberModel
                                        ->join('waiting_number_symbols','appointment_has_queue_number.symbol_id','=','waiting_number_symbols.id')
                                        ->where('patient_id',$patientId)
                                        ->where('appointment_id',$appointment_id)
                                        ->first(); 
                    

                    if(!empty($patientQueue))
                    {
                        $url = $patientQueue->url;
                        $strName = $patientQueue->name;
                    
                        $message = 'Willkommen bei Ihrem Termin mit '.$doctorName.'. Nehmen Sie bitte im Wartebereich Platz. Sie werden über die App und den Bildschirm im Wartebereich aufgerufen.';

                        $data[0]               = $collection[0]; 
                        $data[0]['url']         = $url;
                        $data[0]['symbol_name'] = $strName;

                        self::_createLog('createWaitingNumber',$data,'info');
                        $this->ActivityLogModel->addApiLog('Create Waiting Number','has created waiting number','Create',null,$data);
                    }
                }
                else
                {
                    $status  = false;
                    $message = __('api.APPOINTMENT_NOT_FOUND');
                   
                } 
            }
            catch(\Exception $e) {
                $errors[] = $e->getMessage();
                self::_createLog('createWaitingNumber',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }   
        }//else close                       

        return self::_sendResult($message,$data,$errors,$status);
       
    } 
}