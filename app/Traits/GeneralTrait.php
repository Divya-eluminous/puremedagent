<?php
namespace App\Traits;
use DB;
use Request;
use Browser;
use Session;
use Artisan;
use App\Models\OrdinationsModel;
use App\Models\PatientHasOrdinationsModel;
use Illuminate\Support\Facades\Log;
use App\Models\OrdinationHasSpecialistModel;
use App\Models\SpecialistDocumentsModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\AppointmentModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\PatientsHasServiceControlReminderModel;
use App\Models\PatientHasReminder;
use App\Models\ExaminationsModel;
use App\Models\EventTypeHasExaminationsModel;
use PDF;
use Storage;
use File;
use DateTime;

//Added by Shyam 27-01-22
use Mail;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use App\Mail\ExceptionOccured;
use App\Mail\AppointmentMail; 

use WebSmsCom_Client;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_TextMessage;
use WebSmsCom_ParameterValidationException; 
use WebSmsCom_AuthorizationFailedException;
use WebSmsCom_ApiException;
use WebSmsCom_HttpConnectionException;
use WebSmsCom_UnknownResponseException;
//Added by Shyam 27-01-22

use App\Models\PatientsModel;

//added by roshani on 29-05-2024
use Carbon\Carbon;
 

trait GeneralTrait
{
    public function __construct(OrdinationsModel $OrdinationsModel,
        PatientHasOrdinationsModel $PatientHasOrdinationsModel,
        OrdinationHasSpecialistModel $OrdinationHasSpecialistModel,
        SpecialistDocumentsModel $SpecialistDocumentsModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
        AppointmentModel $AppointmentModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
        PatientsHasServiceControlReminderModel $PatientsHasServiceControlReminderModel,
        PatientHasReminder $PatientHasReminder,
        ExaminationsModel $ExaminationsModel,
        EventTypeHasExaminationsModel $EventTypeHasExaminationsModel,
        PatientsModel $PatientsModel
    ) {
        $this->OrdinationsModel = $OrdinationsModel; 
        $this->PatientHasOrdinationsModel = $PatientHasOrdinationsModel;
        $this->OrdinationHasSpecialistModel  = $OrdinationHasSpecialistModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->AppointmentModel = $AppointmentModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->PatientsHasServiceControlReminderModel = $PatientsHasServiceControlReminderModel; 
        $this->PatientHasReminder = $PatientHasReminder;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->EventTypeHasExaminationsModel = $EventTypeHasExaminationsModel;
        $this->PatientsModel = $PatientsModel;
    }

    public function _getCompanyId()
    {
        return session('company_id');
    }

    public function _setCompanyId($company_id=false)
    {
        if(empty($company_id)){
            session(['company_id' => '']);
        }elseif(empty(session('company_id')) && isset($company_id)){
            session(['company_id' => $company_id]);
        }

        return session('company_id');
    }
    
    /*-----------------------------------
    |   Appointment Modules common functions for admin and api
    -------------------------------------------------*/ 
    public function _getEndDate($start_date,$appointment_type_id)
    {
        // $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id); //commented on 13-apr-26
        $appointmentType = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id);//changed on 13-apr-26
       
        if(!empty($appointmentType)){
            $duration = $appointmentType->duration;
            $end_date = date("Y-m-d H:i", strtotime('+'.$duration.' minutes', strtotime($start_date)));
        }
        return $end_date;
    }

    public function _getNotifyTime($start_date){

        $notify_times = [];
        if(!empty($start_date)){

            $current_time = date("Y-m-d H:i", time());

            $differ_in_mins = ((strtotime($start_date) - strtotime($current_time))/60);

            if($differ_in_mins >= 0 && $differ_in_mins <= 30){
                $notify_times[] = date("Y-m-d H:i", strtotime('+2 minutes', strtotime($current_time)));
            }


            $notify_times[] = date("Y-m-d H:i", strtotime('-2 hour', strtotime($start_date)));
            
            $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($start_date)));
            $two_day_before   = date("Y-m-d H:i", strtotime('-2 day', strtotime($start_date)));
            $three_day_before   = date("Y-m-d H:i", strtotime('-3 day', strtotime($start_date)));


            $notify_times[] = date("Y-m-d H:i", strtotime($previous_day." 09:00"));//previous day morning

            //commented below timings on 18-jan-24
           // $notify_times[] = date("Y-m-d H:i", strtotime($previous_day." 12:00"));//previous day afternoon

           // $notify_times[] = date("Y-m-d H:i", strtotime($previous_day." 05:00"));//previous day evening
            
           // $notify_times[] = $two_day_before;  //two day before
           // $notify_times[] = $three_day_before; //three day before

        }
        return $notify_times;
    }


    public function string_operation($string)
    {
        $string = str_replace(array(".",","), array(".-",",-"),$string);           
        $string = ucwords(mb_convert_case($string, MB_CASE_TITLE, "UTF-8"));
        $string = str_replace(array(".-",",-"), array(".",","),$string);
        return $string ;
    }

    public function _ordinationName($id)
    {
        $ordination_name = $this->PatientHasOrdinationsModel->with('getOrdination')
        ->where('fk_patient_id',$id)->get();

        $ordination_name =  $ordination_name->map(function($index)
        {           
            if(!empty($index->getOrdination))
            {
                $index->ordination_name = $index->getOrdination->name;
                return $index; 
            }
            return $index;            
        })->toArray();

        if(!empty($ordination_name) && count($ordination_name) > 0)
        { 
            $ordination_names = array_column($ordination_name, 'ordination_name');
            $ordination_name = implode(",",$ordination_names);
        }else
        {
            $ordination_name = '';
        }

        return $ordination_name;
    }

    public function GetServicesEventType($appoinment_id,$patient_id,$services,$appointment_type_id,$type)
    {
        $collections1 = $this->AppointmentTypeHasExaminationsModel
                        ->where('appoinment_type_has_examinations.appoinment_id',$appointment_type_id)
                        ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                        ->get([
                            'examinations.id',
                        ]);           

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
                        ]);

        $getRecord = $collections1->merge($collections2);
        if(!empty($getRecord) && count($getRecord) > 0 && !empty($services))
        {
            foreach ($getRecord as $key => $value) 
            {
               if(in_array($value['id'], $services))
               {    
                    $status = 'booked';
               }
               else
               {
                    $status = 'displayed';
               }
               $isExist = $this->EventTypeHasExaminationsModel
                          ->where('patient_id',$patient_id)
                          ->where('appoinment_id',$appoinment_id)
                          ->where('service_id',$value['id'])
                          ->first();
                if(empty($isExist))
                {
                    $eventType = new $this->EventTypeHasExaminationsModel;
                } 
                else
                {
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
    }

    public function _appointmentTypesAgaintsServices($id,$request,$patient_id)
    {
        log::info(" in appointmentTypesAgaintsServices function");
        Log::info($request->all());
        Log::info("patient_id ==>".$patient_id);
        Log::info("appointment id===>".$id);


        $services = [];

        //dd($request->app_services); 
        // log::info($request->app_services);
        if(!empty($request->app_services) && sizeof($request->app_services)>0)
        {
             log::info(" in not empty  app_services ");

            foreach ($request->app_services as $key => $value) 
            {
                  Log::info(" foreach app_services value ==>".$value);

                $checkService = $this->AppointmentHasExaminationsModel 
                                        ->where('patient_id',$patient_id)
                                        ->where('appointment_id',$id)
                                        ->where('examination_id',$value)
                                        ->first();
                // log::info($id.">>".$patient_id.">>".$value);
                if(empty($checkService)){

                   Log::info(" foreach checkService empty ==>".$value);

                    $services = new $this->AppointmentHasExaminationsModel;
                    $services->patient_id     = $patient_id;
                    $services->examination_id = $value ;
                    $services->appointment_id = $id;

                    log::info(" in appointmentTypesAgaintsServices function before save");
                    Log::info($services);
                    $services->save();
                }
            }
        }
        return $services;
        
    }

    public function _checkDuplicationPatient($family_name,$first_name,$birth_date,$mobile_no,$type,$id)
    {
        if($type == 'add')
        {
            $checkedPatientExist = $this->PatientsModel
                                // ->where(DB::raw('upper(family_name)'),'=',strtoupper($family_name))
                                // ->where(DB::raw('upper(first_name)'),'=',strtoupper($first_name))
                                ->whereDate('birth_date', date('Y-m-d',strtotime($birth_date)))
                                ->where('mobile_no', $mobile_no)
                               ->whereNULL('deleted_at')
                               ->count(); 
        }else
        {
            $checkedPatientExist = $this->PatientsModel
                                // ->where(DB::raw('upper(family_name)'),'=',strtoupper($family_name))
                                // ->where(DB::raw('upper(first_name)'),'=',strtoupper($first_name))
                                ->whereDate('birth_date', date('Y-m-d',strtotime($birth_date)))
                                ->where('mobile_no', $mobile_no)
                                ->where('id','!=',$id)
                                ->whereNULL('deleted_at')
                                ->count(); 
        }      
        //dd($checkedPatientExist);  
        if($checkedPatientExist > 0)
        {
            return false;
        }else
        {
            return true;
        }
        
    }

    public function _GetAssignedCheckList($id,$exam_ids,$patient_id)
    {
        $checklist = [];
        if(!empty($exam_ids) && sizeof($exam_ids)>0)
        {
            foreach ($exam_ids as $key => $value) 
            {
                $getCheckList = $this->ExaminationsHasMultipleCheckListModel
                               ->where('fk_examinations_id',$value)
                               ->get();

                if(!empty($getCheckList) && sizeof($getCheckList)>0)
                {
                    foreach ($getCheckList as $doc_key => $chk_value) 
                    {
                        $checklist = new $this->CheckListHasSelectedQuestionModel;
                        $checklist->fk_appointment_id = $id;
                        $checklist->fk_patient_id     = $patient_id;
                        $checklist->fk_examination_id = $value ;
                        $checklist->status            = '0' ;
                        $checklist->fk_check_list_id = $chk_value['fk_check_list_id'] ;
                        $checklist->save();
                        self::generateChecklistPDFNew($patient_id,$id,$value);
                        
                    }
                }               
            }
        }
        return $checklist;
        
    }

    public function _GetAssignedDocument($id,$app_type,$exam_ids,$patient_id)
    {
        Log::info("in _GetAssignedDocument");
        Log::info($id);
        Log::info($app_type);
        Log::info($exam_ids);
        Log::info($patient_id);


        $document = [];
        if(!empty($exam_ids) && sizeof($exam_ids)>0)
        {
            foreach ($exam_ids as $key => $value) 
            {
                $getDocument = $this->ExaminationsHasMultipleDocumentListModel
                               ->where('fk_examinations_id',$value)
                               ->get();

                if(!empty($getDocument) && sizeof($getDocument)>0)
                {
                    foreach ($getDocument as $doc_key => $doc_value) 
                    {
                        $document = $this->PatientHasDocumentsModel 
                                                        ->where('patient_id',$patient_id)
                                                        ->where('appointment_id',$id)
                                                        ->where('fk_examinations_id',$value)
                                                        ->where('fk_document_id',$doc_value['fk_document_list_id'])
                                                        ->first();
                        log::info("document");
                        log::info($document);

                        //commented below code on 12-feb-26
                       /* if(empty($document)) $document = new $this->PatientHasDocumentsModel;
                        
                        $document->appointment_id     = $id;
                        $document->patient_id         = $patient_id;
                        $document->fk_examinations_id = $value;
                        $document->exam_app_type_id   = $app_type;
                        $document->type               = 'service';
                        $document->record_type        = 1 ;
                        $document->doc_status         = '0' ;
                        $document->fk_document_id     = $doc_value['fk_document_list_id'] ;

                        log::info("document again");
                        log::info($document);

                        $document->save();*/

                        //added below code on 12-feb-26
                        if(empty($document))
                        {
                            $document = new $this->PatientHasDocumentsModel;
                        
                            $document->appointment_id     = $id;
                            $document->patient_id         = $patient_id;
                            $document->fk_examinations_id = $value;
                            $document->exam_app_type_id   = $app_type;
                            $document->type               = 'service';
                            $document->record_type        = 1 ;
                            $document->doc_status         = '0' ;
                            $document->fk_document_id     = $doc_value['fk_document_list_id'] ;

                            log::info("document again");
                            log::info($document);
                            $document->save();

                        }//if 
                        
                        //log::info($document);
                        $pdf = self::generateDocumentlistPDF($patient_id,$id,$value);
                        
                    }
                }               
            }
        }
        return $document;
        
    }

    public function _currentDatabase($ordination_id)
    {
        Session::put('current_ordination', $ordination_id);
        
        // Stancl Tenancy: Get tenant uuid
        $tenant = DB::connection('system')->table('tenants')->where('ordination_id', $ordination_id)->first();
        $db_name = $tenant ? $tenant->uuid : null;

        if ($db_name) {
            Config(['database.default' => 'tenant']);
            $databaseName = Config(['database.connections.tenant.database' => $db_name]);
        }

        $specialityExist = $this->OrdinationHasSpecialistModel->where('ordination_id',$ordination_id)->count();

        if($specialityExist != 0)
        {                           
            Session::put('speciality_exist', 1);
        }
        else
        {
            Session::put('speciality_exist', 0);
        }
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('cache:forget', ['key' => 'spatie.permission.cache']);
    }


    public function generateChecklistPDF($patient_id,$appointment_id,$exam_id)
    {
        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = '';
        // log::info("generateChecklistPDF");
        /*************Added on 26-dec-22***********/     
        $imagepath='';                
        // Hyn Tenancy (commented out)
        // // Hyn Tenancy (commented out)
        // $getDatabase = DB::connection('system')->table("tenants")
        
        // Stancl Tenancy: Get tenant database name
        // $getDatabase = DB::connection('system')->table("tenants")
        //                     ->where('ordination_id',Config('ordination_id'))->first(['uuid']);
        
        // Stancl Tenancy: Get tenant database name
        // Hyn Tenancy (commented out)
        // $getDatabase = DB::connection('system')->table("tenants")
        
        $getDatabase = DB::connection('system')->table("tenants")
                            ->where('ordination_id',Config('ordination_id'))->first(['uuid']);                               
        $imagepath = url('storage/tenancy/tenants/'.$getDatabase->uuid);                
        /*************Added on 26-dec-22***********/     



        //dd($patient_id,$appointment_id,$exam_id);
        $examinations_details = $this->ExaminationsHasMultipleCheckListModel
                                ->where('fk_examinations_id',$exam_id)
                                ->get();
                             
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
                    // log::info('Data');
                    // log::info($data);
                    if(empty($data)){
                        $collections = $this->CheckListModel
                                ->where('id',$exam_val['fk_check_list_id'])
                                ->where('status',1)
                                ->first();
                        $chk_id = $collections->id;
                        if($collections->signDoc == 'read')  
                        {
                            $check_list_status = '1';
                        }         
                        if(!empty($collections))
                        {    
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
                    // log::info("ELSE");
                    $flag = 1;
                    $check_list_status = '0';
                    $CheckListHasSelectedQuestionModel = new $this->CheckListHasSelectedQuestionModel;
                    $collections = $this->CheckListModel
                                ->where('id',$exam_val['fk_check_list_id'])
                                ->where('status',1)
                                ->first();
                    $chk_id = $collections->id;
                    if($collections->signDoc == 'read')  
                    {
                        $check_list_status = '1';
                    }         
                    if(!empty($collections))
                    {    
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
                //$PdfPath   = storage_path().'/app/public/check_list_pdf/';
                //$PDFname   = $collections->check_list_name.'_'.time().'.pdf';
                // $PDFname = str_replace(' ', '' , $collections->check_list_name);
                // $PDFname   = trim($PDFname).'_'.time().'.pdf';

                $PDFname = self::createPdfFileName($patient_id,$collections->check_list_name);

                // Invoice full path
                $StorePath = $PdfPath.$PDFname; 
                $accessPath = '/check_list_pdf/'.$PDFname;
                
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
                // log::info("generateChecklistPDF-appointmentAgreementcntroller");
                $pdf->getDomPDF()->setHttpContext($contxt);
                //#################################################################################
                $pdf->loadView($PDFPath,compact('data'))->save($StorePath);     
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
                // log::info("SAVE");
                // log::info($CheckListHasSelectedQuestionModel);
                //$cnt++;
            }
        }
        
                                          
        return $data;
    }

    public function generateChecklistPDFNew($patient_id,$appointment_id,$exam_id)
    {
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
        // Hyn Tenancy (commented out)
        // $getDatabase = DB::connection('system')->table("tenants")
        
        // Stancl Tenancy: Get tenant database name
        $getDatabase = DB::connection('system')->table("tenants")
                            ->where('ordination_id',Config('ordination_id'))->first(['uuid']);                               
        $imagepath = url('storage/tenancy/tenants/'.$getDatabase->uuid);                
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

                                if(isset($cval['signature']) && $cval['signature'] !=null)
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
                // $PdfPath   = storage_path().'/check_list_pdf/';
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
        
                                          
        return $data;
    }

    public function generateDocumentlistPDF($patient_id,$appointment_id,$exam_id)
    {
        Log::info("in generateDocumentlistPDF function trait");
        Log::info($patient_id);
        Log::info($appointment_id);
        Log::info($exam_id);

        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = '';
        //dd($patient_id,$appointment_id,$exam_id);
        $examinations_details = $this->ExaminationsHasMultipleDocumentListModel
                                ->where('fk_examinations_id',$exam_id)
                                ->get();
                             
        $flag = 0;
        if(!empty($examinations_details))
        {
            foreach ($examinations_details as $exam_key => $exam_val) 
            {
                $collections = $this->SpecialistDocumentsModel->find($exam_val['fk_document_list_id']);      
                if(!empty($collections))
                {   
                    $doc_status = '0';
                    if($collections->signDoc =='read')
                    {
                        //$doc_status = '1';
                    }
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
                // $PdfPath   = storage_path().'/document_pdf/';

                    $header_image_path = self::getFilePath($collections['header_image_path']);
                    $footer_image_path = self::getFilePath($collections['footer_image_path']);
                    //dd($header_image_path,$footer_image_path);


                    //Get Patient details added at 17aug22
                    $patientFirstName = $patientLastName = $patientFullName= $patientDob= ''; 
                    $getPatientDetails = $this->PatientsModel->where('id',$patient_id)->first();
                    if(isset($getPatientDetails) && !empty($getPatientDetails))
                    {
                        $patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
                        $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
                        $patientFullName = $patientFirstName.' '.$patientLastName;
                        $patientDob = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
                    }

                    $data['doc_id']            = $collections['id'];
                    $data['name']              = $collections['name'];
                    $data['html_text']         = $collections['html_text'];
                    $data['background_color']  = $collections['background_color'];
                    $data['header_image']      = $collections['header_image'];
                    $data['header_image_path'] = $header_image_path;
                    $data['footer_image']      = $collections['footer_image'];
                    $data['footer_image_path'] = $footer_image_path;
                    $data['background_color']  = $collections['background_color'];


                    // Add patient data to data array added at 17aug22
                     $data['patientFullName'] = $patientFullName;
                     $data['patientDob'] = $patientDob;
                     $data['currentDate'] = date('m/d/Y');


                    //$cnt++;
                    //$PdfPath   = self::StorePath('document_pdf/');
                    
                    //$PdfPath   = storage_path().'/app/public/document_pdf/';
                    // $PDFname   = $collections['name'].'_'.time().'.pdf';
                    $PDFname = self::createPdfFileName($patient_id,$collections['name'],$collections['name']);
                    //dump($PDFname);
                    // $PDFname = str_replace(' ', '' , $collections['name']);
                    // $PDFname   = trim($PDFname).'_'.time().'.pdf';
                    // Invoice full path
                    $StorePath = $PdfPath.$PDFname; 
                    $accessPath = '/document_pdf/'.$PDFname;
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
                    // log::info("generateDocumentlistPDF-appointmentAgreementcntroller");
                    $pdf->loadView($PDFPath,compact('data'))->save($StorePath);
                    // PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView($PDFPath,compact('data'))->save($StorePath);
                    // end
                    // dump($PDFname);
                    // dump($accessPath);
                    //========================================================================
                    // pdf
                    $current_date = date('Y-m-d H:i:s');
                    $start_date   = null;
                    $end_date     = null;
                    $days = '';
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
                        $duration    = (int)$days;
                        $last_date   = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +".$duration." day");
                        $end_date    = Date('Y-m-d H:i:s',$last_date);
                        $start_date  = $current_date;
                    }
                    // ===========================================================
                    /* exam_id
                    |Check List Selected questions exam_arr
                    */
                    $CheckListHasSelectedQuestionModel = $this->PatientHasDocumentsModel 
                                                        ->where('patient_id',$patient_id)
                                                        ->where('appointment_id',$appointment_id)
                                                        ->where('type',$collections['type_of_document'])
                                                        ->where('fk_document_id',$collections['id'])
                                                        ->first();
                    if(!empty($CheckListHasSelectedQuestionModel))
                    {
                        $doc_flag = 1;

                        Log::info("in generateDocumentlistPDF function trait CheckListHasSelectedQuestionModel not empty ");

                        Log::info("in generateDocumentlistPDF function trait CheckListHasSelectedQuestionModel not empty id ===> ");

                        Log::info($CheckListHasSelectedQuestionModel->id);

                        $doc_status = $CheckListHasSelectedQuestionModel->doc_status; //added on 11-feb-26 for doc issue


                    } 
                    else {

                         Log::info("in generateDocumentlistPDF function trait CheckListHasSelectedQuestionModel empty ");


                        $CheckListHasSelectedQuestionModel = new $this->PatientHasDocumentsModel; 
                        $doc_flag = 1;
                    }

                     Log::info("in generateDocumentlistPDF function trait CheckListHasSelectedQuestionModel doc_flag ");
                     Log::info($doc_flag);

                    Log::info("in generateDocumentlistPDF function trait CheckListHasSelectedQuestionModel doc_status ");
                     Log::info($doc_status);


                    if($doc_flag == 1)
                    {

                        Log::info("in generateDocumentlistPDF function trait CheckListHasSelectedQuestionModel in doc_flag is 1 ");

                        Log::info("doc_status in doc_flag 1");

                        Log::info($doc_status);

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
        //dd($data);
        return $data;
    }

    function __GetSecialits()
    {
        $specilist_id = '';
        if(!empty(Session::get('specialist')))
        {
            $specilist_id = Session::get("specialist");
            $specialist_details= $this->SpecialistModel->find($specilist_id);
        }
        else
        {
            $specialist_details = $this->SpecialistModel->orderBy('id','DESC')->first();
            if(!empty($specialist_details))
            {
                Session::put('specialist',$specialist_details->id);
            }
        }

        if(empty($specialist_details))
        {
            $specialist_details = $this->SpecialistModel->orderBy('id','DESC')->first();
            if(!empty($specialist_details))
            {
                Session::put('specialist',$specialist_details->id);
            }
            
        }
         return  $specialist_details;
    }

    public function _creatWebLog($name,$data,$type='info') 
    {
      config(['logging.channels.api.path' => storage_path('logs/web/web_'.date('Y-m-d').'.log')]);
      //Log::channel('api')->$type($name,array($data));
    } 

    // GET Document General document with calculate her freqyency
    public function getAllGeneralDocument($patient_id,$appointment_id)
    {
        $data = [];
        $doc_flag = 0;
        $getDocumentList = $this->SpecialistDocumentsModel
                        ->where('type_of_document','general')
                        ->where('status','1')
                        ->get();
       
        if(!empty($getDocumentList) && sizeof($getDocumentList)>0)
        {
            $cnt = 0;
            foreach ($getDocumentList as $doc_key => $doc_value) 
            {
                $patientDetails = $this->PatientsModel
                                  ->where('id',$patient_id)
                                  ->first();
               
                if(!empty($patientDetails))
                {
                    $hasDocument = $this->PatientHasDocumentsModel 
                                  ->where('patient_id',$patient_id)
                                  //->where('fk_appointment_id',$appointment_id)
                                  ->where('fk_document_id',$doc_value['id'])
                                  ->where('type','general')
                                  ->first();
                    
                    if(!empty($hasDocument) && ($hasDocument->count() > 0))
                    {
                        $l_date = self::checkDocumentFrequency($patient_id,$doc_value['id'],$hasDocument); 
                        if(!empty($l_date))
                        {
                            $doc_flag = 1;
                            $cnt++;
                        }
                    }
                    else {
                        $doc_flag = 1;
                    }
                    if($doc_flag == 1)
                    {
                        $data[$cnt]['doc_id']            = $doc_value['id'];
                        $data[$cnt]['exam_id']           = null;
                        $data[$cnt]['name']              = $doc_value['name'];
                        $data[$cnt]['html_text']         = $doc_value['html_text'];
                        $data[$cnt]['background_color']  = $doc_value['background_color'];
                        $data[$cnt]['header_image']      = $doc_value['header_image'];
                        $data[$cnt]['header_image_path'] = $doc_value['header_image_path'];
                        $data[$cnt]['footer_image']      = $doc_value['footer_image'];
                        $data[$cnt]['footer_image_path'] = $doc_value['footer_image_path'];
                        $data[$cnt]['background_color']  = $doc_value['background_color'];
                        $data[$cnt]['chk_type']         = 'general';
                        $cnt++;
                    } 
                }      
            }
        }   
        return $data;
    }
    
    public function checkDocumentFrequency($patient_id,$doc_id,$value)
    {  
        $data   = [];
        $flag   = 0;
        $l_date = '';

        $getDocumentList = $this->SpecialistDocumentsModel->find($doc_id);
        
        if(!empty($getDocumentList))
        {
            $chk_activation_date = date('Y-m-d h:i:s',strtotime($getDocumentList->date_of_last_activation));
            // ----------------------------------------------------------
            $current_date = date('Y-m-d h:i:s');               
            $start_date   = Date('Y-m-d  h:i:s',strtotime($value->activation_start_date));
            $end_date     = Date('Y-m-d  h:i:s',strtotime($value->activation_last_date));
            
            $days = null;
            if(strtotime($chk_activation_date) > strtotime($start_date))
            {
                $flag = 1;
            }
            // else if(strtotime($current_date) > strtotime($end_date))  //commented on 11-feb-25
            else if(strtotime($current_date) < strtotime($end_date)) //changed on 11-feb-25
            {
                $flag = 1;
            }
            
            if($flag == 1)
            {
                switch ($getDocumentList->frequency_type) 
                {
                    case "day":
                        $days = (int)$getDocumentList->frequency;
                    break;
                    case "month":
                        $days = 30 * (int)$getDocumentList->frequency;
                    break;
                    case "year":
                        $days = 365 * (int)$getDocumentList->frequency;
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

    public function _createGeneralDocumentPdf($inputdata,$patient_id,$appointment_id)
    {
        //dd($inputdata->all());
        $data = $dataFinal = [];
        $doc_flag = 0;
        $flag = '0';
        $file_name = $exam_id = $app_id = '';
        $exam_arr = $inputdata->exam_id;
        $doc_type = $inputdata->doc_type;
        if(!empty($appointment_id))
        {
            $app_id = $appointment_id;
        }
        $cnt = 0;
        foreach ($inputdata['doc_hd'] as $key=>$doc_list) 
        {
            $collections = $this->SpecialistDocumentsModel->find($doc_list);
            $days ='';          
            if(!empty($collections))
            {    
                 //start added on 7-jan-25
                $header_image_path = self::getFilePath($collections['header_image_path']);
                $footer_image_path = self::getFilePath($collections['footer_image_path']);
                 //end added on 7-jan-25

                $data['doc_id']            = $collections['id'];
                $data['name']              = $collections['name'];
                $data['html_text']         = $collections['html_text'];
                $data['background_color']  = $collections['background_color'];
                $data['header_image']      = $collections['header_image'];
                // $data['header_image_path'] = $collections['header_image_path']; //commented on 7-jan-25
                $data['header_image_path'] = $header_image_path; //added on 7-jan-25
                $data['footer_image']      = $collections['footer_image'];
                // $data['footer_image_path'] = $collections['footer_image_path'];//commented on 7-jan-25
                $data['footer_image_path'] = $footer_image_path;//added on 7-jan-25

                $data['background_color']  = $collections['background_color'];


                /*********Get Patient details added on 7-jan-25 ***********/
                $patientFirstName = $patientLastName = $patientFullName= $patientDob= ''; 
                $getPatientDetails = $this->PatientsModel->where('id',$patient_id)->first();
                if(isset($getPatientDetails) && !empty($getPatientDetails))
                {
                    $patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
                    $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
                    $patientFullName = $patientFirstName.' '.$patientLastName;
                    $patientDob = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
                }

                // Add patient data to data array added on 7-jan-25
                 $data['patientFullName'] = $patientFullName;
                 $data['patientDob'] = $patientDob;
                 $data['currentDate'] = date('m/d/Y');
                 /***************************************************/

                //$cnt++;
                // $PdfPath   = storage_path().'/app/public/document_pdf/';
                if(!empty(Config('ordination_id')))
                {
                    $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
                    $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/document_pdf/';
                }
                else {
                    $PdfPath = '/opt/app-shared/php/data/storage/app/public/document_pdf/';
                }
                //$PDFname   = $collections['name'].'_'.time().'.pdf';
                $PDFname = self::createPdfFileName($patient_id,$collections['name']);
                // Invoice full path
                $StorePath = $PdfPath.$PDFname; 
                $accessPath = '/document_pdf/'.$PDFname;
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
                // log::info("_createGeneralDocumentPdf-generalTrait");
                $pdf->loadView($PDFPath,compact('data'))->save($StorePath);
                // end
                //========================================================================
                // pdf
                $current_date = date('Y-m-d H:i:s');
                $start_date   = null;
                $end_date     = null;
                $days = '';
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
                    |Check List Selected questions
                */
                $CheckListHasSelectedQuestionModel = $this->PatientHasDocumentsModel 
                                                    ->where('patient_id',$patient_id)
                                                    ->where('type',$inputdata['type'])
                                                    ->where('fk_document_id',$collections['id'])
                                                    ->first();
                if(!empty($CheckListHasSelectedQuestionModel))
                {
                    $doc_flag = 1;
                } 
                else {
                    $CheckListHasSelectedQuestionModel = new $this->PatientHasDocumentsModel; 
                    $doc_flag = 1;
                }
                if($doc_flag == 1)
                {
                    if(!empty($app_id))
                    {
                        $CheckListHasSelectedQuestionModel->appointment_id     = $app_id;
                    }
                    $CheckListHasSelectedQuestionModel->patient_id             = $patient_id;
                    //$CheckListHasSelectedQuestionModel->exam_app_type_id       = $appointment_id;
                    $CheckListHasSelectedQuestionModel->fk_examinations_id     = $exam_arr[$key];
                    $CheckListHasSelectedQuestionModel->fk_document_id         = $doc_list;

                    if (!empty($inputdata['doc']) && in_array($doc_list, $inputdata['doc']))
                    {
                        $CheckListHasSelectedQuestionModel->doc_status         = '1';
                    }
                    else {
                        $CheckListHasSelectedQuestionModel->doc_status         = '0';
                    }
                    $CheckListHasSelectedQuestionModel->pdf_name               = $PDFname;
                    $CheckListHasSelectedQuestionModel->pdf_path               = $accessPath;
                    $CheckListHasSelectedQuestionModel->type                   = $doc_type[$key];
                    //$CheckListHasSelectedQuestionModel->signature              = $file_name;
                    $CheckListHasSelectedQuestionModel->created_at             = Date('Y-m-d');
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

    public function _deactivateReminder($appoitment)
    {
        $all_services = $this->AppointmentTypeHasExaminationsModel->select('examination_id')->where(['appoinment_id'=>$appoitment->appointment_type_id])->get();
        foreach ($all_services as $key => $value) {

            $ids = $this->PatientsHasServiceReminderModel
                                ->where(['patient_id'=>$appoitment->patient_id,
                                'reminder_status'=>'Set',
                                'status'=>'activate',
                                'service_id'=>$value->examination_id])
                                ->get();
            $id_holder = [];
            //Dont deactivdate general remidner when we create the appoitment added by Swati 20-Oct-22
            $generalServcieCheck=1;
            $checkGeneralServcie=$this->ChannelsRemindersSettingModel
                ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                ->where('service_id',$value->examination_id)
                ->get();
            if(!empty($checkGeneralServcie)){
                $today_date=date("Y-m-d");
                $checkServiceReminders =  $this->PatientsHasServiceReminderModel
                                ->where('service_id',$value->examination_id)
                                ->where('patient_id',$appoitment->patient_id)
                                ->where('reminder_status','Set')
                                ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                ->first();
                if(!empty($ids) && empty($checkServiceReminders))
                {
                    foreach($ids as $id=>$value_id)
                    {                    
                        $id_holder[] = $value_id->id;
                    }
                }else $generalServcieCheck=0;
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
            //End====================================================================
            if($generalServcieCheck){
                $reactivateReminder =  $this->PatientHasReminder
                                       ->whereIn('service_reminder_id',$id_holder)
                                       ->update(['status'=>'deactivate']);
                $this->PatientsHasServiceReminderModel->where(['patient_id'=>$appoitment->patient_id,'reminder_status'=>'Set','status'=>'activate','service_id'=>$value->examination_id])->update(['status'=>'deactivate']);
            }
        }
        
    }

    public function _deactivateReminderNew($appoitment,$services=array())
    {
        $appointmentServices=array();
        $all_services = $this->AppointmentTypeHasExaminationsModel->select('examination_id')->where(['appoinment_id'=>$appoitment->appointment_type_id])->get();
        foreach ($all_services as $key => $value) {
            $appointmentServices[]=$value->examination_id;
            // Log::info($appointmentServices[]);
            if(is_array($services) && in_array($value->examination_id, $services)) //condition added in 2-jan-24
           {
                $ids = $this->PatientsHasServiceReminderModel
                                ->where(['patient_id'=>$appoitment->patient_id,
                                'status'=>'activate',
                                'service_id'=>$value->examination_id])
                                ->whereIn('reminder_status',['Set','ignore'])
                                ->get();
                $id_holder = [];
                $generalServcieCheck=1;
                //Dont deactivdate general remidner when we create the appoitment added by Swati 20-Oct-22
                 $checkGeneralServcie=DB::table('preferred_channels_for_reminders_setting')
                    ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                    ->where('service_id',$value->examination_id)
                    ->get();
                // if(!empty($checkGeneralServcie)) //commented on 2-jan-24 for deactivate services on book and //added on 2-jan-24              
                if(!empty($checkGeneralServcie) && isset($checkGeneralServcie) && $checkGeneralServcie->count() > 0) 
                {

                    $today_date=date("Y-m-d");
                    $checkServiceReminders =  $this->PatientsHasServiceReminderModel
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
                    $reactivateReminder =  $this->PatientHasReminder
                                               ->whereIn('service_reminder_id',$id_holder)
                                               ->update(['status'=>'deactivate']);
                    $this->PatientsHasServiceReminderModel->where(['patient_id'=>$appoitment->patient_id,'status'=>'activate','service_id'=>$value->examination_id])->whereIn('reminder_status',['Set','ignore'])->update(['status'=>'deactivate']);
                }
            }//if inarray   

        }//foreach
        Log::info("if services");
        if(is_array($services) && !empty($services)){
            foreach ($services as $value) {
                // log::info($value);
                // log::info($appointmentServices);
                if(!in_array($value, $appointmentServices)){
                    $ids = $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,
                                        'status'=>'activate',
                                        'service_id'=>$value])
                                        ->whereIn('reminder_status',['Set','ignore'])
                                        ->get();
                    $id_holder = [];
                    $generalServcieCheck=1;
                    //Dont deactivdate general remidner when we create the appoitment added by Swati 20-Oct-22
                    $checkGeneralServcie=DB::table('preferred_channels_for_reminders_setting')
                        ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                        ->where('service_id',$value)
                        ->get();
                    if(!empty($checkGeneralServcie)){
                        $today_date=date("Y-m-d");
                        $checkServiceReminders =  $this->PatientsHasServiceReminderModel
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
                        $reactivateReminder =  $this->PatientHasReminder
                                                   ->whereIn('service_reminder_id',$id_holder)
                                                   ->update(['status'=>'deactivate']);
                        $this->PatientsHasServiceReminderModel->where(['patient_id'=>$appoitment->patient_id,'status'=>'activate','service_id'=>$value])->whereIn('reminder_status',['Set','ignore'])->update(['status'=>'deactivate']);
                    }
                }
            }
        }
        Log::info("stop if");
    }

    public function _activateReminderOnEdit($appoitment)
    {
        $all_services = $this->AppointmentHasExaminationsModel->select('examination_id')->where(['appointment_id'=>$appoitment->id])->get();

        foreach ($all_services as $key => $value) {
            $checkservice = $this->PatientsHasServiceReminderModel
                                ->where(['patient_id'=>$appoitment->patient_id,
                                'reminder_status'=>'Set',
                                'status'=>'activate',
                                'service_id'=>$value->examination_id])
                                ->where(DB::raw('date(reminder_date)'),'>',Date("Y-m-d"))
                                ->where('appointment_id','!=',$appoitment->id)
                                ->first();
            $checkservicereminder = $this->PatientsHasServiceReminderModel
                                ->where(['patient_id'=>$appoitment->patient_id,
                                'reminder_status'=>'Set',
                                'status'=>'activate',
                                'service_id'=>$value->examination_id])
                                ->where('appointment_id','!=',0)
                                ->first();
            if(empty($checkservice) && empty($checkservicereminder)){
                $ids = $this->PatientsHasServiceReminderModel
                                    ->where(['patient_id'=>$appoitment->patient_id,
                                    'reminder_status'=>'Set',
                                    'status'=>'deactivate',
                                    'appointment_id' => 0,
                                    'service_id'=>$value->examination_id])
                                    ->get();
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
                // log::info('_activateReminderOnEdit');
                // log::info($value->examination_id);
                 $this->PatientsHasServiceReminderModel->where(['patient_id'=>$appoitment->patient_id,'reminder_status'=>'Set','status'=>'deactivate','appointment_id'=>0,'service_id'=>$value->examination_id])->update(['status'=>'activate']);
            }
        }
        
    }

    public function _activateReminderOnCancel($appoitment)
    {
        // $all_services = $this->AppointmentTypeHasExaminationsModel->select('examination_id')->where(['appoinment_id'=>$appoitment->appointment_type_id])->get();

         $all_services = $this->AppointmentHasExaminationsModel->select('examination_id')->where(['appointment_id'=>$appoitment->id])->get();

        foreach ($all_services as $key => $value) {
            $checkservice = $this->PatientsHasServiceReminderModel
                                ->where(['patient_id'=>$appoitment->patient_id,
                                'reminder_status'=>'Set',
                                'status'=>'activate',
                                'service_id'=>$value->examination_id])
                                ->where(DB::raw('date(reminder_date)'),'>',Date("Y-m-d"))
                                ->where('appointment_id','!=',$appoitment->id)
                                ->first();
            if(empty($checkservice)){
                $ids = $this->PatientsHasServiceReminderModel
                                    ->where(['patient_id'=>$appoitment->patient_id,
                                    'reminder_status'=>'Set',
                                    'status'=>'deactivate',
                                    'appointment_id'=>0,
                                    'service_id'=>$value->examination_id])
                                    ->get();
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
                 $this->PatientsHasServiceReminderModel->where(['patient_id'=>$appoitment->patient_id,'reminder_status'=>'Set','status'=>'deactivate','appointment_id'=>0,'service_id'=>$value->examination_id])->update(['status'=>'activate']);

                     /****start New code for control reminder deactivate on 23-apr-25****/

                    

                     $controlReminderAppId = $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,
                                        'reminder_status'=>'Set',
                                        'status'=>'deactivate',
                                        'service_id'=>$value->examination_id])
                                        ->where('appointment_id','!=',$appoitment->id)
                                        ->where('type','=','control')
                                        ->orderBy('id','desc')
                                        ->first(['appointment_id']);


                     if(isset($controlReminderAppId))
                     {
                        $controlReminderIds = $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,
                                        'reminder_status'=>'Set',
                                        'status'=>'deactivate',
                                        'service_id'=>$value->examination_id])
                                        ->where('appointment_id','=',$controlReminderAppId->appointment_id)
                                        ->where('type','=','control')
                                        ->orderBy('id','desc')
                                        ->get();

                        if(!empty($controlReminderIds))
                        {
                                foreach($controlReminderIds as $id=>$value_id)
                                {                    

                                    $reactivateReminder =  $this->PatientHasReminder
                                                       ->where('service_reminder_id',$value_id->id)
                                                       ->update(['status'=>'activate']);

                                    $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,'reminder_status'=>'Set','status'=>'deactivate','service_id'=>$value->examination_id])
                                        ->where('type','control')
                                        ->where('id', $value_id->id)->update(['status'=>'activate']);                   

                                }//foreach
                            
                        }//if
                     }//if issset 

                   /****end*****************************************/

                    /****start New code for age reminder deactivate on 2-june-25**#351**/
 
                     $ageReminderAppId = $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,
                                        'reminder_status'=>'Set',
                                        'status'=>'deactivate',
                                        'service_id'=>$value->examination_id])
                                        ->where('appointment_id','!=',$appoitment->id)
                                        ->where('type','=','age')
                                        ->orderBy('id','desc')
                                        ->first(['appointment_id']);


                     if(isset($ageReminderAppId))
                     {
                        $ageReminderIds = $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,
                                        'reminder_status'=>'Set',
                                        'status'=>'deactivate',
                                        'service_id'=>$value->examination_id])
                                        ->where('appointment_id','=',$ageReminderAppId->appointment_id)
                                        ->where('type','=','age')
                                        ->orderBy('id','desc')
                                        ->get();

                        if(!empty($ageReminderIds))
                        {
                                foreach($ageReminderIds as $id=>$value_id)
                                {                    

                                    $reactivateReminder =  $this->PatientHasReminder
                                                       ->where('service_reminder_id',$value_id->id)
                                                       ->update(['status'=>'activate']);

                                    $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,'reminder_status'=>'Set','status'=>'deactivate','service_id'=>$value->examination_id])
                                        ->where('type','age')
                                        ->where('id', $value_id->id)->update(['status'=>'activate']);                   

                                }//foreach
                            
                        }//if
                     }//if issset 

                   /****end*****2-june-25*******#351*****************************/
                    /****start New code for general reminder deactivate on 30-oct-25****/

                    

                     $generalReminderAppId = $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,
                                        'reminder_status'=>'Set',
                                        'status'=>'deactivate',
                                        'service_id'=>$value->examination_id])
                                        ->where('appointment_id','!=',$appoitment->id)
                                        ->where('type','=','general')
                                        ->orderBy('id','desc')
                                        ->first(['appointment_id']);

                     // Log::info("generalReminderAppId");
                     // Log::info($generalReminderAppId);                  


                     if(isset($generalReminderAppId))
                     {
                        $generalReminderIds = $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,
                                        'reminder_status'=>'Set',
                                        'status'=>'deactivate',
                                        'service_id'=>$value->examination_id])
                                        ->where('appointment_id','=',$generalReminderAppId->appointment_id)
                                        ->where('type','=','general')
                                        ->orderBy('id','desc')
                                        ->get();

                        // Log::info("generalReminderIds");
                        // Log::info($generalReminderIds);                  
                 

                        if(!empty($generalReminderIds))
                        {
                                foreach($generalReminderIds as $id=>$value_id)
                                {                    

                                   
                                     // Log::info("value_id->id");
                                     // Log::info($value_id->id);                  
                                  

                                    $reactivateReminder =  $this->PatientHasReminder
                                                       ->where('service_reminder_id',$value_id->id)
                                                       ->update(['status'=>'activate']);

                                    $this->PatientsHasServiceReminderModel
                                        ->where(['patient_id'=>$appoitment->patient_id,'reminder_status'=>'Set','status'=>'deactivate','service_id'=>$value->examination_id])
                                        ->where('type','general')
                                        ->where('id', $value_id->id)->update(['status'=>'activate']);                   

                                }//foreach
                            
                        }//if
                     }//if issset 

                   /***general****end**30-oct-25***************************************/

  

            }//



        }
        
    }

    public function current_ordination()    
    {
        dd(optional($env->hostname())->fqdn);
    }



    public function _activateReminder($appoitment)
    {
        $doneAppoitment =  $this->AppointmentModel
                            ->select('patient_id','appointment.id as appointment_id','appointment_type_id','patients.birth_date','patients.age','appointment.start_date')
                            ->leftjoin('patients','patients.id','appointment.patient_id')
                            ->where('appointment.id',$appoitment)
                            ->first();
                           

        if(!empty($doneAppoitment))
        {
            // get all services of the appoitment
            $allServices = $this->AppointmentTypeHasExaminationsModel
                            ->select('examinations.*')
                            ->leftjoin('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                            ->where('appoinment_id',$doneAppoitment->appointment_type_id)
                            ->get();

            if(!empty($doneAppoitment->birth_date))               
            {
                $from = new DateTime($doneAppoitment->birth_date);
                $to   = new DateTime('today');
                $age =  $from->diff($to)->y;
                  $data['age'] = $age; 
            }else
            {
                 $data['age'] = $doneAppoitment->age; 
            }

           
            $data['birth_date'] = $doneAppoitment->birth_date;
            if(!empty($allServices) && count($allServices) > 0)
            {
                $this->_checkAndAddServiceReminder($allServices,$doneAppoitment->patient_id,$doneAppoitment->appointment_id,$doneAppoitment->start_date,$data);  
                $this->AppointmentModel->where('id',$doneAppoitment->appointment_id)->update(['reminder_status'=>'1']);
            }
        }
    }

    public function _checkAndAddServiceReminderOld($all_services,$patient_id,$appointment_id,$appointment_start_date,$data)
    {
        if(!empty($all_services) && count($all_services) > 0)
        {
            foreach ($all_services as $service_key => $service_value) 
            {
                $is_exist = $this->PatientsHasServiceReminderModel->where(['patient_id'=>$patient_id,'reminder_status'=>'Set','status'=>'activate','service_id'=>$service_value->id])->first();
                if(empty($is_exist))
                {
                    $this->AppointmentModel->where('id',$appointment_id)->update(['appointment_status'=>'Fertig']);
                    $is_service_has_reminder = $this->ChannelsRemindersSettingModel->where(
                                            [
                                                'service_id' => $service_value->id,
                                            ])->first();
                    $default_reminder = 'general';
                    if(empty($is_service_has_reminder))
                    {
                        $is_service_has_reminder = $this->ChannelsRemindersSettingModel->where(
                                            [
                                                'type' => 'global',
                                            ])->first();
                        // Log::info('Default setting');
                        // Log::info(json_encode($is_service_has_reminder));
                    }
                    else {
                        $default_reminder = $is_service_has_reminder->activated_reminder;
                        $h_reminder = $this->ChannelsRemindersSettingModel->where(
                                            [
                                                'type' => 'global',
                                            ])->first(['holiday_reminder','checkup_number_of_interval','checkup_time_interval','checkup_first_frequency','checkup_new_frequency','checkup_period_controls','checkup_time_interval_frequency_type','checkup_first_frequency_type','checkup_new_frequency_type','checkup_period_frequency_type']);
                        $is_service_has_reminder->checkup_number_of_interval =  $h_reminder->checkup_number_of_interval;
                        $is_service_has_reminder->checkup_time_interval =  $h_reminder->checkup_time_interval;
                        $is_service_has_reminder->checkup_first_frequency =  $h_reminder->checkup_first_frequency;
                        $is_service_has_reminder->checkup_new_frequency =  $h_reminder->checkup_new_frequency;
                        $is_service_has_reminder->checkup_period_controls =  $h_reminder->checkup_period_controls;
                        $is_service_has_reminder->checkup_time_interval_frequency_type =  $h_reminder->checkup_time_interval_frequency_type;
                        $is_service_has_reminder->checkup_first_frequency_type =  $h_reminder->checkup_first_frequency_type;
                        $is_service_has_reminder->checkup_new_frequency_type =  $h_reminder->checkup_new_frequency_type;
                        $is_service_has_reminder->checkup_period_frequency_type =  $h_reminder->checkup_period_frequency_type;
                        // Log::info(json_encode($is_service_has_reminder));
                    }
                    //dd($default_reminder,$is_service_has_reminder);
                    ////Added by Shyam 27-12-21
                    // if($default_reminder == 'general')
                    // {
                    //     $this->_generalReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_value->id);
                    // }
                    // else {
                    //     if(!empty($data['age']) && $data['age']!='')
                    //     {
                    //         $this->_ageReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$data,$service_value->id);
                    //     }
                    //     if(!empty($is_service_has_reminder->checkup_number_of_interval) && $is_service_has_reminder->checkup_number_of_interval != '')
                    //     {
                    //         $this->_controlReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_value->id);
                    //     }
                    // }
                    ////Commented by Shyam 27-12-21
                    if($default_reminder == 'general')
                    {
                        $this->_generalReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_value->id);
                    }
                    else {
                        if(!empty($data['age']) && $data['age']!='')
                        {
                            $this->_ageReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$data,$service_value->id); 
                        }
                    }
                    $this->_controlReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_value->id);
                }
                else {
                    // echo "no operta";
                     echo "";
                }
            }
        }  
    }

    public function _generalReminderOld($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
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
            $parent_id = $this->PatientHasReminder->insertGetId($temp);
            //Log::info($reactive_reminder);
        }
       
    }

    public function _ageReminderOld($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$data,$service_id)
    {
        //Log::info($data['age']);
        //Log::info($is_service_has_reminder->age_from);
        //Log::info($is_service_has_reminder->age_to);

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
        elseif($data['age'] < $is_service_has_reminder->age_from)
        {

            $diff = $is_service_has_reminder->age_from - $data['age'];
            $start_date = date('Y-m-d', strtotime($data['birth_date']. ' + '.($data['age'] + $diff).' year'));

            $start_date = $this->_filterWeekendAndHoiliday($start_date,0,$is_service_has_reminder->holiday_reminder,'plus');

            $period_date = $start_date;
              //Log::info('start_date is the d'.$start_date.$data['age']);dd('s');
            $reminder_array[] = $period_date;
           // Log::info(json_encode($reminder_array));
           // dd('d');
            for($i=0; $i<($is_service_has_reminder->age_number_of_interval-1); $i++)
            {
                $value4_days = $this->_getDate($period_date,$is_service_has_reminder->age_time_interval,$is_service_has_reminder->age_time_interval_frequency_type);
                
                // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));

                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

                  $reminder_array[] = $third_reminder;
                
            }       
            sort($reminder_array);
        }
        $reminder_id = 0;
        // log::info("GeneralTrait==>_ageReminder>>".$patient_id.">>".$appointment_id);
        // log::info($reminder_array);
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
            $parent_id = $this->PatientHasReminder->insertGetId($temp);
        }
        
    }

    public function _controlReminderOld($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        $reminder_array = [];        
        // 1st reminder
        $start_date = $start_date;
        //Log::info(json_encode($is_service_has_reminder)."=".$appointment_id."=".$start_date."=".$patient_id."=".$service_id);

        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->checkup_period_controls,$is_service_has_reminder->checkup_period_frequency_type);
       
        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->checkup_first_frequency,$is_service_has_reminder->checkup_first_frequency_type);

        // $first_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($period_date)) . " -".(int)$value3_days." day"));

        $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');

        $reminder_array[] = $first_reminder;

        for($i=0; $i<($is_service_has_reminder->checkup_number_of_interval-1); $i++)
        {
            $value4_days = $this->_getDate($period_date,$is_service_has_reminder->checkup_time_interval,$is_service_has_reminder->checkup_time_interval_frequency_type);
            
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
                if(count($is_exists) == 0)
                {
                    $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
                }
            }
            $value5_days = $this->_getDate(end($reminder_array),$is_service_has_reminder->checkup_new_frequency,$is_service_has_reminder->checkup_new_frequency_type);

            // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));

            $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value5_days,$is_service_has_reminder->holiday_reminder,'plus');

            $temp = [];
            $temp['patient_id'] =  $patient_id;
            $temp['last_reminder_date'] =  end($reminder_array);
            $temp['next_reminder_date'] =  $reactive_reminder;
            $temp['service_reminder_id'] =  $reminder_id;
            $temp['status'] =  'activate';
            $parent_id = $this->PatientHasReminder->insertGetId($temp);
        }
    }


    public function _getDate($start_date,$period,$frequency_type)
    {
        $days=0;
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

    public function _filterWeekendAndHoiliday($date,$days,$is_hoilday_or_weekend,$operation)
    {
        $operator = '+';
        if($operation == 'minus')
        {
            $operator = '-';
        }
        $calculated_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($date)) . " ".$operator.(int)$days." day"));
        $weekDay = date('w', strtotime($calculated_date));
        //Log::info($date."=".$days."=".$is_hoilday_or_weekend."=".$operation."=".$calculated_date);
        if(($weekDay == 0 || $weekDay == 6))
        {
            $calculated_date = date('Y-m-d H:i:s', strtotime($calculated_date.' +1 Weekday'));
        }
        
        //Log::info($calculated_date);
        return $calculated_date;
    }

    public function putFilePath($path, $file, $fileName)    
    {
         // Log::info('in trait putFilePath function');
        if(!empty(Config('ordination_id')))
        {
            
              // Log::info('if trait ordination id');
            //$fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $fileName);
            if(!File::isDirectory($path))
            {
                File::makeDirectory($path, 0777, true, true);
            }

             // Log::info('trait before get get database name');
            // Stancl Tenancy: Get tenant database name
            $getDataBaseName = DB::connection('system')
                                ->table('tenants')
                                ->where('ordination_id', Config('ordination_id'))
                                ->first();

            // Hyn Tenancy (commented out)
            // $getDataBaseName = $this->website->get();
            // $getDataBaseName = $this->website->where('ordination_id',Config('ordination_id'))->first();

            $fileStorePath = 'public/tenancy/tenants/'.$getDataBaseName->uuid.'/'.$path;

              // Log::info($fileStorePath);
     
            $fileStorePath = Storage::putFileAs($fileStorePath, $file, $fileName);
            // Log::info($fileStorePath);
        }
        else
        {
              // Log::info('in 222');
            $path = 'public/'.$path;
            $fileStorePath = Storage::putFileAs($path, $file, $fileName);
        }

         // Log::info($path);
        // $path = 'public/'.$path;
        //     $fileStorePath = Storage::putFileAs($path, $file, $fileName);
        //dd($fileStorePath);
        return $fileStorePath;
    }

    public function getFilePath($images)
    {

        if(!empty(Config('ordination_id')))
        {
            $getDatabaseName = DB::connection('system')
                    ->table("tenants")
                    ->where('ordination_id',Config('ordination_id'))
                    ->first(['uuid']);

            $imagePath = public_path('storage/tenancy/tenants/'.$getDatabaseName->uuid.'/'.$images);
            if (!empty($images) && File::exists($imagePath)) {
            $folderPath = url('storage/tenancy/tenants/'.$getDatabaseName->uuid.'/'.$images);
            } else {
            $folderPath = '';
            }
        }
        else
        {
           $folderPath = url('storage/'.$images);
           //$folderPath = url(Storage::path($images));
            //dd($folderPath);
        }
        //dd($folderPath);
        //$folderPath = url('storage/'.$images);
        return $folderPath;
    }
    
      public function getFilePath_for_ordination_create($images)
    {
        Log::info("in  getFilePath");

        if(!empty(Config('ordination_id')))
        {
            $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
                               Log::info("getDatabaseName");
                               Log::info($getDatabaseName);

           // $folderPath = url('storage/app/tenancy/tenants/'.$getDatabaseName->uuid.'/'.$images); 
            //$folderPath = url('storage/app/tenancy/tenants/'.$getDatabaseName->uuid.'/'.$images);

        //    $folderPath = url('storage/tenancy/tenants/'.$getDatabaseName->uuid.'/'.$images);
            $folderPath = public_path('storage/tenancy/tenants/'.$getDatabaseName->uuid.'/'.$images);
            Log::info("folderPath");
            Log::info($folderPath);
            
           //  $folderPath = Storage::disk('tenancy')->get($getDatabaseName->uuid.'/'.$images);

             // dump($getDatabaseName->uuid.'/'.$images);

             // dump($folderPath);
        }
        else
        {
            Log::info("else");
        //    $folderPath = url('storage/ordination-logo'.$images);
           $folderPath = public_path('storage/'.$images);
           Log::info("folderPath");
           Log::info($folderPath);
           //$folderPath = url(Storage::path($images));
            //dd($folderPath);
        }
        //dd($folderPath);
        //$folderPath = url('storage/'.$images);
        return $folderPath;
    }
    
    public function StorePath($folder)
    {
        if(!empty(Config('ordination_id')))
        {  
            //$str_path = Storage::disk('tenant')->getDriver()->getAdapter()->getPathPrefix();

            $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
          
            $Path = 'storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid;
            $PdfPath  = $Path.'/'.$folder;
            if(!File::isDirectory($PdfPath))
            {
                File::makeDirectory($PdfPath, 0777, true, true);
            }
        }
        else
        {

            $PdfPath   = storage_path().'/app/'.$folder;
            if(!File::isDirectory($PdfPath))
            {
                File::makeDirectory($PdfPath, 0777, true, true);
            }
        }
        return $PdfPath;
    }

    public function unlinkFilePath($folder)
    {
        if(!empty(Config('ordination_id')))
        {   
             $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
          
            $Path = 'storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid;
            $Path  = $Path.'/'.$folder;

            
        }
        else
        {
            $Path   = storage_path().'/app/'.$folder;
        }
        return $Path;
    }

    public function _storePatientOrdination($patient_id)
    {

        Log::info("in _storePatientOrdination GeneralTrait function");
        Log::info($patient_id);

        $ordination_id = Config('ordination_id');
        //$ordination_id = 1;
        $flag = 0;
        $status_flag = false;    
        $parentPatientId = $this->PatientsModel
                          ->where('id',$patient_id)
                          ->first();
    
                 
        if(!empty($parentPatientId))
        {
            $getDatabaseName = DB::connection('system')
                               ->table("tenants")
                               ->where('ordination_id',$ordination_id)
                               ->first();
            //dd($ordinationDetails);
            if(!empty($getDatabaseName))
            {
                //commented below two fields on 15-dec-23 for fname and family name

                $tenantPatientId =  DB::connection('system')
                                    ->table("patients")
                                    // ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
                                    // ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
                                    ->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
                                    ->where('mobile_no', $parentPatientId->mobile_no)
                                    ->whereNULL('deleted_at')             
                                    ->orderBy('created_at','DESC')
                                    ->first();
                //dd($tenantPatientId); 
                if(!empty($tenantPatientId))
                {
                    $flag = 1;     
                    $tenantPatientInsert = $tenantPatientId->id;                 
                }
                else
                {
                    $patientRec['old_id']      = $parentPatientId->old_id;
                    $patientRec['pat_nr']      = $parentPatientId->pat_nr;
                    $patientRec['family_name'] = $parentPatientId->family_name;
                    $patientRec['first_name']  = $parentPatientId->first_name;
                    $patientRec['email']       = $parentPatientId->email;
                    $patientRec['country_code'] = $parentPatientId->country_code;
                    $patientRec['mobile_no']   = $parentPatientId->mobile_no;
                    $patientRec['ganymed_mobile_no'] = $parentPatientId->ganymed_mobile_no;
                    $patientRec['birth_date'] = $parentPatientId->birth_date;
                    $patientRec['age']        = $parentPatientId->age;
                    $patientRec['password']   = $parentPatientId->password;
                    // $patientRec['str_password'] = $parentPatientId->str_password;
                    $patientRec['login_otp']  = $parentPatientId->login_otp;
                    $patientRec['otp_created_at'] = $parentPatientId->otp_created_at;
                    $patientRec['api_access_token'] = $parentPatientId->api_access_token;
                    $patientRec['last_login_at'] = $parentPatientId->last_login_at;
                    $patientRec['login_type'] = $parentPatientId->login_type;
                    $patientRec['is_blocked'] = $parentPatientId->is_blocked;
                    $patientRec['status']     = $parentPatientId->status;
                    $patientRec['mobile_token'] = $parentPatientId->mobile_token;
                    $patientRec['token']      = $parentPatientId->token;
                    $patientRec['road']       = $parentPatientId->road;
                    $patientRec['place']      = $parentPatientId->place;
                    $patientRec['postal_code'] = $parentPatientId->postal_code;
                    $patientRec['gender']     = $parentPatientId->gender;
                    $patientRec['size']       = $parentPatientId->size;
                    $patientRec['weight']     = $parentPatientId->weight;
                    $patientRec['title']      = $parentPatientId->title;
                    $patientRec['salutation'] = $parentPatientId->salutation;
                    $patientRec['family_doctor'] = $parentPatientId->family_doctor;
                    $patientRec['insurance_number'] = $parentPatientId->insurance_number;
                    $patientRec['additional_insurance'] = $parentPatientId->additional_insurance;
                    $patientRec['gdpr']       = $parentPatientId->gdpr;
                    $patientRec['update_ganydb'] = $parentPatientId->update_ganydb;
                    $patientRec['social_security_number'] = $parentPatientId->insurance_number;
                    $patientRec['patient_status_flag'] = $parentPatientId->patient_status_flag;
                    $patientRec['note_report_request'] = $parentPatientId->note_report_request;
                    $patientRec['note_report_request_flag'] = $parentPatientId->note_report_request_flag;
                    $patientRec['additional_insurance'] = $parentPatientId->street_no;
                    $patientRec['additional_insurance'] = $parentPatientId->reminder_active;
                    $patientRec['created_at'] = Date('Y-m-d');
                    $patientRec['country'] = $parentPatientId->country;
                    
                    Log::info("in _storePatientOrdination GeneralTrait function patientRec");
                    Log::info($patientRec);

                   
                    $tenantPatientInsert  =  DB::connection('system')
                                            ->table("patients")
                                            ->insertGetId($patientRec); 
                                            // ->insert($patientRec)->lastInsertId(); 

                    $flag = 1;                  
                }
                
                if($flag == 1)
                {
                    $checkOrdination =  DB::connection('system')
                                        ->table("patients_has_ordination")
                                        ->where('fk_patient_id',$tenantPatientInsert)
                                        ->where('fk_ordination_id',$ordination_id)
                                        ->first();
                    if(empty($checkOrdination))
                    {
                        $tmp['fk_patient_id']    = $tenantPatientInsert;
                        $tmp['fk_ordination_id'] = $ordination_id;
                        $tmp['status']           = '1';
                        $checkOrdination =  DB::connection('system')
                                            ->table("patients_has_ordination")
                                            ->insert($tmp); 
                        $status_flag = true;                    
                    }
                    //this condition Added by Shyam 02-03-22
                    $checkOrdinationAgain = DB::connection('system')
                                            ->table("patients_has_ordination")
                                            ->where('fk_patient_id',$patient_id)
                                            ->where('fk_ordination_id',$ordination_id)
                                            ->first();
                    if(empty($checkOrdinationAgain))
                    {
                        $tmp['fk_patient_id']    = $patient_id;
                        $tmp['fk_ordination_id'] = $ordination_id;
                        $tmp['status']           = '1';
                        $checkOrdinationAgain = DB::connection('system')
                                                ->table("patients_has_ordination")
                                                ->insert($tmp); 
                        $status_flag = true;
                    }
                }
            }
        }
        return $status_flag;
    }

    /**
     * Remove a patient from the CURRENT ordination in the central (system) DB,
     * and delete the generic/central patient record only if the patient no
     * longer belongs to any other active ordination.
     *
     * This is the delete counterpart of _storePatientOrdination(): the generic
     * patient lives in system.patients (matched by birth_date + mobile_no, the
     * same keys the create/link flow uses) and ordination membership is tracked
     * in system.patients_has_ordination.
     *
     * @param  \App\Models\PatientsModel  $patient  the local (tenant) patient being deleted
     * @return void
     */
    public function _deletePatientOrdination($patient)
    {
        $ordination_id = Config('ordination_id');

        // Generic-patient handling only applies inside an ordination/tenant context
        if(empty($ordination_id) || empty($patient))
        {
            return;
        }

        // Resolve the generic/central patient the same way it was linked on create
        // (_storePatientOrdination matches by birth_date + mobile_no).
        $genericPatient = DB::connection('system')
                            ->table('patients')
                            ->whereDate('birth_date', date('Y-m-d', strtotime($patient->birth_date)))
                            ->where('mobile_no', $patient->mobile_no)
                            ->whereNull('deleted_at')
                            ->orderBy('created_at','DESC')
                            ->first();

        // Never synced to the central DB -> nothing to remove
        if(empty($genericPatient))
        {
            return;
        }

        $genericPatientId = $genericPatient->id;

        try
        {
            DB::connection('system')->beginTransaction();

            // 1) Remove the patient from the CURRENT ordination only.
            //    _storePatientOrdination can store two mapping rows for an ordination
            //    (keyed by the generic id and by the local tenant id), so match both.
            DB::connection('system')
                ->table('patients_has_ordination')
                ->where('fk_ordination_id', $ordination_id)
                ->whereIn('fk_patient_id', [$genericPatientId, $patient->id])
                ->whereNull('deleted_at')
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);

            // 2) Does the same patient still belong to any OTHER active ordination?
            //    (same join shape as the searchOrdination check in Api\v*\AuthController)
            $otherOrdinations = DB::connection('system')
                ->table('patients_has_ordination as pho')
                ->join('ordination as o', 'o.id', '=', 'pho.fk_ordination_id')
                ->where('pho.fk_patient_id', $genericPatientId)
                ->where('pho.fk_ordination_id', '!=', $ordination_id)
                ->whereNull('pho.deleted_at')
                ->whereNull('o.deleted_at')
                ->where('o.status', 1)
                ->count();

            // 3) If the patient no longer exists in any other ordination, delete the
            //    generic patient too and clean up its remaining mapping rows.
            if($otherOrdinations == 0)
            {
                DB::connection('system')
                    ->table('patients_has_ordination')
                    ->where('fk_patient_id', $genericPatientId)
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => date('Y-m-d H:i:s')]);

                DB::connection('system')
                    ->table('patients')
                    ->where('id', $genericPatientId)
                    ->update(['deleted_at' => date('Y-m-d H:i:s')]);
            }

            DB::connection('system')->commit();
        }
        catch(\Exception $e)
        {
            DB::connection('system')->rollback();
            Log::info('_deletePatientOrdination error: '.$e->getMessage());
        }
    }

    public function _updatePatientOrdination($collection,$parentPatientId)
    {
         Log::info('in _updatePatientOrdination GeneralTrait function');
         Log::info($parentPatientId);

        // $patient_data = array(
        //               //  'fk_patient_id' => $parentPatientId->id,
        //                 'old_id' => $parentPatientId->old_id,
        //                 'pat_nr' => $parentPatientId->pat_nr,
        //                 'family_name' => $parentPatientId->family_name,
        //                 'first_name' => $parentPatientId->first_name,
        //                 'email' => $parentPatientId->email,
        //                 'country_code' => $parentPatientId->country_code,
        //                 'mobile_no' => $parentPatientId->mobile_no,
        //                 'ganymed_mobile_no' => $parentPatientId->ganymed_mobile_no,
        //                 'birth_date' => $parentPatientId->birth_date,
        //                 'age' => $parentPatientId->age,
        //                 'password' => $parentPatientId->password,
        //                 // 'str_password' => $parentPatientId->str_password,
        //                 'login_otp' => $parentPatientId->login_otp,
        //                 'otp_created_at' => $parentPatientId->otp_created_at,
        //                 'api_access_token' => $parentPatientId->api_access_token,
        //                 'last_login_at' => $parentPatientId->last_login_at,
        //                 'login_type' => $parentPatientId->login_type,
        //                 'is_blocked' => $parentPatientId->is_blocked,
        //                 'status' => $parentPatientId->status,
        //                 'mobile_token' => $parentPatientId->mobile_token,
        //                 'token' => $parentPatientId->token,
        //                 'road' => $parentPatientId->road,
        //                 'street_no' => $parentPatientId->street_no,
        //                 'place' => $parentPatientId->place,
        //                 'postal_code' => $parentPatientId->postal_code,
        //                 'gender' => $parentPatientId->gender,
        //                 'size' => $parentPatientId->size,
        //                 'weight' => $parentPatientId->weight,
        //                 'title' => $parentPatientId->title,
        //                 'salutation' => $parentPatientId->salutation,
        //                 'family_doctor' => $parentPatientId->family_doctor,
        //                 'insurance_number' => $parentPatientId->insurance_number,
        //                 'additional_insurance' => $parentPatientId->additional_insurance,
        //                 'gdpr' => $parentPatientId->gdpr,
        //                 'update_ganydb' => $parentPatientId->update_ganydb,
        //                 'reminder_active' => $parentPatientId->reminder_active,
        //                 'note_report_request' => $parentPatientId->note_report_request,
        //                 'note_report_request_flag' => $parentPatientId->note_report_request_flag,
        //                 'social_security_number' => $parentPatientId->social_security_number,
        //                 );

         $patient_data = array(
                      //  'fk_patient_id' => $parentPatientId->id,
                        'old_id' => $collection->old_id,
                        'pat_nr' => $collection->pat_nr,
                        'family_name' => $collection->family_name,
                        'first_name' => $collection->first_name,
                        'email' => $collection->email,
                        'country_code' => $collection->country_code,
                        'mobile_no' => $collection->mobile_no,
                        'ganymed_mobile_no' => $collection->ganymed_mobile_no,
                        'birth_date' => $collection->birth_date,
                        'age' => $collection->age,
                        'password' => $collection->password,
                        // 'str_password' => $collection->str_password,
                        'login_otp' => $collection->login_otp,
                        'otp_created_at' => $collection->otp_created_at,
                        'api_access_token' => $collection->api_access_token,
                        'last_login_at' => $collection->last_login_at,
                        'login_type' => $collection->login_type,
                        'is_blocked' => $collection->is_blocked,
                        'status' => $collection->status,
                        'mobile_token' => $collection->mobile_token,
                        'token' => $collection->token,
                        'road' => $collection->road,
                        'street_no' => $collection->street_no,
                        'place' => $collection->place,
                        'postal_code' => $collection->postal_code,
                        'gender' => $collection->gender,
                        'size' => $collection->size,
                        'weight' => $collection->weight,
                        'title' => $collection->title,
                        'salutation' => $collection->salutation,
                        'family_doctor' => $collection->family_doctor,
                        'insurance_number' => $collection->insurance_number,
                        'additional_insurance' => $collection->additional_insurance,
                        'gdpr' => $collection->gdpr,
                        'update_ganydb' => $collection->update_ganydb,
                        'reminder_active' => $collection->reminder_active,
                        'note_report_request' => $collection->note_report_request,
                        'note_report_request_flag' => $collection->note_report_request_flag,
                        'social_security_number' => $collection->social_security_number,
                        );

         Log::info('in _updatePatientOrdination GeneralTrait function patient_data');
         Log::info($patient_data);
 

        if(empty(Config('ordination_id')))
        {
            //Log::info('empty .ordination_id..');

            $getDBName = DB::connection('system')
                        ->table("tenants")
                        ->where('ordination_id',Config('ordination_id'))
                        ->first();
            if(!empty($getDBName))
            {
                $getDataBaseName = $getDBName->uuid;

               // Log::info($getDataBaseName);
                       
                //commented below two fields on 15-dec-23 for fname and family name
                         
                $tenantPatientId =  DB::table($getDataBaseName.'.patients')
                                        // ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
                                        // ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
                                        ->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
                                        ->where('mobile_no', $parentPatientId->mobile_no)
                                        ->whereNULL('deleted_at')             
                                        ->orderBy('created_at','DESC')
                                        ->first();

                //Log::info($tenantPatientId);                        
                                        
                if(!empty($tenantPatientId))
                {
                    $updateQry = DB::table($getDataBaseName.'.patients')
                                    ->where('id', $tenantPatientId->id)
                                    ->update($patient_data);
                }  
            }                          
            
        }
        else
        {
             Log::info(' not empty .ordination_id..');

             //commented below two fields on 15-dec-23 for fname and family name  
            $tenantPatientId =  DB::connection('system')
                                    ->table("patients")
                                    // ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
                                    // ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
                                    ->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
                                    ->where('mobile_no', $parentPatientId->mobile_no)
                                    ->whereNULL('deleted_at')             
                                    ->orderBy('created_at','DESC')
                                    ->first();

                              
            if(!empty($tenantPatientId))
            {
                 Log::info('in tenantPatientId '); 

                // Log::info($tenantPatientId->id);       


                $updateQry = DB::connection('system')
                         ->table('patients')
                         ->where('id', $tenantPatientId->id)
                         ->update($patient_data);
            }
            else
            {
                 Log::info('else empty tenantPatientId '); 
                 $updateQry = DB::connection('system')
                         ->table('patients')
                         // ->where('id', $tenantPatientId->id)
                         ->insert($patient_data);
            }    
            
        }
        return true;
    }

    public function _oldPatient($parentPatientId)
    {
        $patient_data = array(
                        'fk_patient_id' => $parentPatientId->id,
                        'old_id' => $parentPatientId->old_id,
                        'pat_nr' => $parentPatientId->pat_nr,
                        'family_name' => $parentPatientId->family_name,
                        'first_name' => $parentPatientId->first_name,
                        'email' => $parentPatientId->email,
                        'country_code' => $parentPatientId->country_code,
                        'mobile_no' => $parentPatientId->mobile_no,
                        'ganymed_mobile_no' => $parentPatientId->ganymed_mobile_no,
                        'birth_date' => $parentPatientId->birth_date,
                        'age' => $parentPatientId->age,
                        'password' => $parentPatientId->password,
                        // 'str_password' => $parentPatientId->str_password,
                        'login_otp' => $parentPatientId->login_otp,
                        'otp_created_at' => $parentPatientId->otp_created_at,
                        'api_access_token' => $parentPatientId->api_access_token,
                        'last_login_at' => $parentPatientId->last_login_at,
                        'login_type' => $parentPatientId->login_type,
                        'is_blocked' => $parentPatientId->is_blocked,
                        'status' => $parentPatientId->status,
                        'mobile_token' => $parentPatientId->mobile_token,
                        'token' => $parentPatientId->token,
                        'road' => $parentPatientId->road,
                        'street_no' => $parentPatientId->street_no,
                        'place' => $parentPatientId->place,
                        'postal_code' => $parentPatientId->postal_code,
                        'gender' => $parentPatientId->gender,
                        'size' => $parentPatientId->size,
                        'weight' => $parentPatientId->weight,
                        'title' => $parentPatientId->title,
                        'salutation' => $parentPatientId->salutation,
                        'family_doctor' => $parentPatientId->family_doctor,
                        'insurance_number' => $parentPatientId->insurance_number,
                        'additional_insurance' => $parentPatientId->additional_insurance,
                        'gdpr' => $parentPatientId->gdpr,
                       
                        'update_ganydb' => $parentPatientId->update_ganydb,
                        'reminder_active' => $parentPatientId->reminder_active,
                        'note_report_request' => $parentPatientId->note_report_request,
                        'note_report_request_flag' => $parentPatientId->note_report_request_flag,
                        'social_security_number' => $parentPatientId->social_security_number,
                        'created_at' => Date('Y-m-d'),
                        'note_report_request_from' => 'admin',
                        );
        $tenantPatientId =  DB::table('old_patients')
                                ->where('fk_patient_id',$parentPatientId->id)
                                ->first();

        if(!empty($tenantPatientId))
        {
            $updateQry = DB::table('old_patients')
                            ->where('id', $tenantPatientId->id)
                            ->update($patient_data);
        } 
        else
        {
            $updateQry = DB::table('old_patients')
                         ->insert($patient_data);
        }   

        return true;
    }

    public function max_distance()
    {
        $sessting = $this->SettingsModel->where('setting_key','MAX_DISTANCE')->first(['setting_value']);
        //$redius = (int)$sessting->setting_value;
        if(!empty($sessting->setting_value)) {
            $redius = (int)$sessting->setting_value * 0.621371;//1 miles...
        }
        else {
            $redius = 0;
        }
        return $redius;
    }

    public function _ageReminderAppoitment($patient_id)
    {
        $is_service_has_reminder = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                    ->where([
                                        'type' => 'service',
                                        'activated_reminder' => 'age'
                                    ])->get();
        //dd($is_service_has_reminder);
        if(!empty($is_service_has_reminder) && count($is_service_has_reminder) > 0)
        {
            foreach($is_service_has_reminder as $key=>$value)
            {
                $is_service_reminder_checked = DB::connection('tenant')->table('examinations')
                                                ->where([
                                                    'id' => $value->service_id,
                                                    'show_as_reminder' => '1',
                                                    'status' => '1'
                                                ])->first();
                // dump($is_service_reminder_checked);
                if(!empty($is_service_reminder_checked))
                {
                    $global_value = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                    ->where([
                                        'type' => 'global',
                                    ])->first();
                    $value->holiday_reminder = $global_value->holiday_reminder;
                    $age_from = $value->age_from;
                    $age_to = $value->age_to;
                    $patinets = DB::connection('tenant')->table('patients')
                                ->whereNull('deleted_at')
                                ->where('id',$patient_id)
                                // ->whereNotIn('id',$patinet_ids)
                                ->get();
                    // dump($patinets);
                    foreach($patinets as $p_key=>$p_value)
                    {
                        $ids = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('service_id',$value->service_id)
                                ->where('patient_id',$p_value->id)
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
                        // $hasServiceReminders = DB::connection('tenant')->table('patient_has_service_reminder')
                        //                         ->where('service_id',$value->service_id)
                        //                         ->where('patient_id',$p_value->id)
                        //                         ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                        // $hasReminders = DB::connection('tenant')
                        //                 ->table('patient_has_reminder')
                        //                 ->whereIn('service_reminder_id',$id_holder)
                        //                 ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                        // echo "\n".$p_value->first_name.$p_value->family_name;
                        $from = new DateTime($p_value->birth_date);
                        $to   = new DateTime('today');
                        $age =  $from->diff($to)->y;
                        // dump($age,$value->age_from,$value->age_to);
                        $start_date = '';
                        if($age == $value->age_from || ($age < $value->age_to && $age > $value->age_from))
                        {
                            $start_date = date('Y-m-d', strtotime($p_value->birth_date. ' + '.($age).' year'));
                        }
                        // dump($start_date);
                        if(!empty($start_date))
                        {
                            $reminder_array = [];
                            $start_date = $this->_filterWeekendAndHoiliday($start_date,0,$value->holiday_reminder,'plus');
                            $value1_days = $this->_getDate($start_date,$value->age_period_controls,$value->age_period_frequency_type);
                            $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));
                            $value3_days = $this->_getDate($period_date,$value->age_first_frequency,$value->age_first_frequency_type);
                            $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$value->holiday_reminder,'minus');
                            $reminder_array[] = $first_reminder;
                            for($i=0; $i<($value->age_number_of_interval-1); $i++)
                            {
                                $value4_days = $this->_getDate($period_date,$value->age_time_interval,$value->age_time_interval_frequency_type);
                                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$value->holiday_reminder,'plus');
                                if( $third_reminder !=  $first_reminder)
                                {
                                    $reminder_array[] = $third_reminder;
                                }
                            }
                            sort($reminder_array);
                            $reminder_id = 0;
                            // dump($reminder_array);
                            if(!empty($reminder_array) && count($reminder_array) > 0)
                            {
                                for($i=0;$i<count($reminder_array);$i++)
                                {
                                    $reminder_tmp = [];
                                    $reminder_tmp['patient_id'] = $p_value->id;
                                    $reminder_tmp['appointment_id'] = 0;
                                    $reminder_tmp['service_id'] = $value->service_id;
                                    $reminder_tmp['reminder_date'] = $reminder_array[$i];
                                    $reminder_tmp['reminder_status'] = 'Set';
                                    $reminder_tmp['status'] = 'activate';
                                    $reminder_tmp['created_at'] = date('Y-m-d h-i-s');
                                    // $reminder_tmp['parent_id'] = $parent_id;
                                    $reminder_tmp['type'] = 'age';

                                    //Added by Shyam 14-01-22
                                    $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
                                                    ->where('patient_id', $p_value->id)
                                                    ->where('appointment_id', 0)
                                                    ->where('service_id', $value->service_id)
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
                                $value5_days = $this->_getDate(end($reminder_array),$value->age_new_frequency,$value->age_new_frequency_type);
                                $reactive_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value5_days,$value->holiday_reminder,'plus');
                                $temp = [];
                                $temp['patient_id'] =  $p_value->id;
                                $temp['last_reminder_date'] =  end($reminder_array);
                                $temp['next_reminder_date'] =  $reactive_reminder;
                                $temp['service_reminder_id'] =  $reminder_id;
                                $temp['status'] =  'activate';
                                $temp['created_at'] =  date('Y-m-d H:i:s');
                                $parent_id = DB::connection('tenant')->table('patient_has_reminder')->insertGetId($temp);
                            }
                        }
                    }
                }
            }
        }
    }

    public function _checkDuplicationAppointmant($request,$id='')
    {
        $errors = [];
        $time_frame = Date('H:i:s',strtotime($request->start_date));
        $sdate = Date('Y-m-d',strtotime($request->start_date));
        // =====================================================================
        $check_time_frame = $this->RosterModel
                            ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                            ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')
                            ->where('roster.doctor_id',$request->doctor_id)
                            ->where('roster_has_dates.is_excluded','=',0)
                            ->whereDate('roster_has_dates.date',$sdate)
                            ->where('roster_has_weeks_has_time_frames.time_frame','=',$time_frame)
                            ->get(['roster_has_weeks_has_time_frames.time_frame']);
        if(!empty($check_time_frame) && sizeof($check_time_frame)>0)
        {
            //now time slotes are available , but the appointment is booked for it then throw error message
            $check_app_date = $request->start_date;
            $appointment_type_id = $request->appointment_type_id;  

            // $appointmentTimeDuration = $this->AppointmentTypesModel->find($appointment_type_id); //commented on 13-apr-26
             $appointmentTimeDuration = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id); //changed on 13-apr-26 

            if(!empty($appointmentTimeDuration)){ 
                $duration = $appointmentTimeDuration->duration;     
                $check_app_end_date = date("Y-m-d H:i", strtotime('+'.$duration.' minutes', strtotime($check_app_date)));   
            }
            if(empty($id))
            {
                 if($duration == 10)    
                {
                    $check_doctor_booked_appointment = $this->AppointmentModel    
                        ->where('doctor_id',$request->doctor_id)    
                        ->whereStatus(1)    
                        ->where('appointment.start_date','<=',$check_app_date)  
                        ->where('appointment.end_date', '>=', $check_app_end_date)  
                        ->get(['id']);
                }
                else{
                    $check_doctor_booked_appointment = $this->AppointmentModel 
                        ->where('doctor_id',$request->doctor_id)    
                        ->whereStatus(1)    
                        ->where('appointment.start_date','>=',$check_app_date)  
                        ->where('appointment.end_date', '<=', $check_app_end_date)  
                        ->get(['id']);
                }
                // $check_doctor_booked_appointment = $this->AppointmentModel
                //                                ->where('doctor_id',$request->doctor_id)
                //                                //->where('appointment_type_id',$appointment_type_id)
                //                                ->whereStatus(1)
                //                                ->where('appointment.start_date','<=',$check_app_date)
                //                                ->where('appointment.end_date', '>=', $check_end_date)
                //                                ->get(['id']);
            }
            else
            {
                if($duration == 10) 
                {   
                    $check_doctor_booked_appointment = $this->AppointmentModel  
                    ->where('id','!=',$id)  
                    ->where('doctor_id',$request->doctor_id)    
                    ->whereStatus(1)    
                    ->where('appointment.start_date','<=',$check_app_date)  
                    ->where('appointment.end_date', '>=', $check_app_end_date)  
                    ->get(['id']);  
                }   
                else    
                {   
                    $check_doctor_booked_appointment = $this->AppointmentModel  
                    ->where('id','!=',$id)  
                    ->where('doctor_id',$request->doctor_id)    
                    ->whereStatus(1)    
                    ->where('appointment.start_date','>=',$check_app_date)  
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
            

            if(!empty($check_doctor_booked_appointment) && sizeof($check_doctor_booked_appointment)>0){
                    $errors[] = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
            }

        }
        //$errors[] = 'Terminzeitfenster sind bereits gebucht.';//Appointment time slots is already booked
        return $errors;                            
       // ======================================================================
                             
    }
    public function debugModeappBookFun($request,$name)
    {
        $type = 'info';
        $name = $name;
        $data = json_encode($request);

        config(['logging.channels.api.path' => '/opt/app-shared/php/data/storage/logs/api/debug_log_app_book'.date('Y-m-d').'.log']);
        //config(['logging.channels.api.path' => '/storage/logs/api/debug_log_app_book'.date('Y-m-d').'.log']);
        Log::channel('api')->$type($name,array($data));
        
        return "true";

    }

    public function createPdfFileName($patient_id,$name='')
    {
        $fileName = '';
        $digits = 3;
        $randomNo = rand(pow(10, $digits-1), pow(10, $digits)-1);
        //dd($randomNo);
        $patientDetails = $this->PatientsModel->find($patient_id);
        if(!empty($patientDetails))
        {
            if(!empty($name))
            {
               // $name = substr($name,0,4);//commented on 13-feb-25
                $name = substr($name,0,7);//added new on 13-feb-25
                $name = strtoupper($name);
                // $bod = Date('m-d',strtotime($patientDetails->birth_date));
                //$bod = Date('d-m-y');//commented on 13-feb-25
                $bod = date('d-m-y_H-i-s'); //added new on 13-feb-25
                $bod = str_replace('','-', $bod);
            }
            else
            {
                $name = "";
            }
            //$fileName = $patientDetails->family_name.'_'.$randomNo.'_'.$name.'_'.$bod;
            $p_name   = substr($patientDetails->family_name,0,4);
            $fileName = preg_replace("/[^a-zA-Z0-9]+/", "", $p_name).'_'.$name.'_'.$bod;
        }
      
        return $fileName.'.pdf';
    }

    public function DeletedAppointmentTrack($collection)
    {
        //dd($collection);
        if(!empty($collection))
        {
            $deletedAppointmentTrackModel = new $this->DeletedAppointmentTrackModel;
            $deletedAppointmentTrackModel->migration_id     = $collection->migration_id;
            $deletedAppointmentTrackModel->google_event_id  = $collection->google_event_id;
            $deletedAppointmentTrackModel->start_date       = $collection->start_date;
            $deletedAppointmentTrackModel->end_date         = $collection->end_date;
            $deletedAppointmentTrackModel->patient_id       = $collection->patient_id;
            $deletedAppointmentTrackModel->doctor_id        = $collection->doctor_id;
            $deletedAppointmentTrackModel->appointment_type_id = $collection->appointment_type_id;
            $deletedAppointmentTrackModel->notes            = $collection->notes;
            $deletedAppointmentTrackModel->status           = $collection->status;
            $deletedAppointmentTrackModel->reminder_status  = $collection->reminder_status;
            $deletedAppointmentTrackModel->appointment_status  = $collection->appointment_status;
            $deletedAppointmentTrackModel->created_at       = Date('Y-m-d H:i:s');
            $deletedAppointmentTrackModel->save();
        }
        return 1;
    }

    //Added by Shyam 27-01-22

    /*-----------------------------------
    |  Send mail
    -------------------------------------------------*/
    public function _sendMailAppointment($patient_id,$eventId)
    {
        $eventId=trim($eventId);
        $accountDetails=DB::connection('tenant')->table('appointment')
                                    ->where('google_event_id',$eventId)->first();
        //$this->AppointmentModel->where('google_event_id',$eventId)->first();
        $patientDetails = $this->PatientsModel->find($patient_id);
        $name = $patientDetails->first_name.' '.$patientDetails->family_name;
        $email = trim($patientDetails->email);
        //Email text
        $cancelUrl = url('/cancelAppointment').'/'.$eventId;
        // $text   = "Below are link for cancel your Appointment. <br/><b><a href='".$cancelUrl."'>Cancel Appointment</a></b>";
        // $text   = "Vielen Dank für Ihre Terminbuchung bei PureGyn. Hier können Sie den Termin stornieren: <br/><b><a href='".$cancelUrl."'>Termin stornieren</a></b>";
        $booking_month = __('admin.'.date('F',strtotime($accountDetails->start_date)),[],'de');
        $appointmentTime = date('d',strtotime($accountDetails->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($accountDetails->start_date))." Uhr.";
        $ordination_name=(!empty(config('ordination_name'))) ? ucfirst(config('ordination_name')):'Puremed';
        $text   = "Vielen Dank für Ihre Terminbuchung bei ".$ordination_name.". Wir freuen uns auf Ihren Besuch.<br/><br/>".$appointmentTime."<br/><br/>Sollten Sie zu dem Termin verhindert sein, können Sie ihn <b><a href='".$cancelUrl."'>hier stornieren</a></b>.";
        if($patientDetails->sendMail==1)
        {
            try
            {
                Mail::to($email)->send(new AppointmentMail($name,$text,$ordination_name));
            }
            catch (\Swift_TransportException $ex)
            {
                log::info($ex->getMessage().', - "'.$email.'"');
            }
        }
    }

    /*-----------------------------------
    |  Send sms
    -------------------------------------------------*/
    public function _sendSmsAppointment($phones,$eventId)
    {
        $eventId=trim($eventId);
        $accountDetails=DB::connection('tenant')->table('appointment')
                                    ->where('google_event_id',$eventId)->first();
        //$accountDetails = $this->AppointmentModel->where('google_event_id',$eventId)->first();
        $patient_id = $accountDetails->patient_id;
        $patientDetails = $this->PatientsModel->find($patient_id);
        //SMS text
        $cancelUrl = url('/cancelAppointment').'/'.$eventId;
         $booking_month = __('admin.'.date('F',strtotime($accountDetails->start_date)),[],'de');
        $appointmentTime = date('d',strtotime($accountDetails->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($accountDetails->start_date))." Uhr.";
        // $appointmentTime = date('d.F',strtotime($accountDetails->start_date)).", um ".date('H:i',strtotime($accountDetails->start_date))." Uhr.";
        //$text   = "Link for cancelling your Appointment. ".$cancelUrl;
        //$text   = "Vielen Dank für Ihre Terminbuchung bei PureGyn. Hier können Sie den Termin stornieren:".$cancelUrl; 
        $ordination_name=(!empty(config('ordination_name'))) ? ucfirst(config('ordination_name')):'Puremed';
        $text = "Ihr ".$ordination_name." Termin: ".$appointmentTime." Stornierungs-Link: ".$cancelUrl;
        // $text = "Ihr PureGyn Termin: ".$appointmentTime." Stornierungs-Link: ".$cancelUrl;
        if(!empty($phones) && $patientDetails->sendSMS==1)
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
            try
            {
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
            }// catch everything that's not a successfully sent message
            catch (WebSmsCom_ParameterValidationException $e)
            {
                $responseRecord = array(
                                    'error' => 1,
                                    'code' => 1,
                                    'message' => "ParameterValidationException caught: ".$e->getMessage()
                                );
            }
            catch (WebSmsCom_AuthorizationFailedException $e)
            {
                $responseRecord = array(
                                    'error' => 1,
                                    'code' => 1,
                                    'message' => "AuthorizationFailedException caught: ".$e->getMessage()
                                );
            }
            catch (WebSmsCom_ApiException $e)
            {
                $responseRecord['message'] = "ApiException Exception: ".$e->getCode().$e->getMessage();
            }
            catch (WebSmsCom_HttpConnectionException $e)
            {
                $responseRecord['message'] = "HttpConnectionException caught: ".$e->getMessage();
            }
            catch (WebSmsCom_UnknownResponseException $e)
            {
                $responseRecord['message'] =  "UnknownResponseException caught: ".$e->getMessage();
            }
            catch (Exception $e)
            {
                $responseRecord['message'] =  "Exception caught: ".$e->getMessage();
            }
            $responseRecord['receipient'] = $recipientAddressList;
            return $responseRecord;
        }
    }



    public static function getLogoPath()
     {
        //Code for logo path
        $logo = $logoPath = '';
        if(!empty(Config('ordination_id')))
        {
            $getDBName = DB::connection('system')
                                        ->table("tenants")
                                        ->where('ordination_id',Config('ordination_id'))
                                        ->first(['uuid']);
            $getDataBaseName = isset($getDBName->uuid)?$getDBName->uuid:'';
            $ordination = DB::table($getDataBaseName.'.ordination')->select('ordination.id','ordination.logo','ordination.logo_path')->first();
            if(isset($ordination) && !empty($ordination) && isset($getDataBaseName))
            {
              $logo = $ordination->logo;
              $logoPath = url('storage/tenancy/tenants/'.$getDataBaseName.'/'.$ordination->logo_path) ;
            }
        }//if
        return $logoPath;  
     }//getLogoPath

     // Added by Swati 02-08-22*/
    public function _ageReminderOnUpdateAge($patient_id){
        $pat = DB::table('patients')->where('id',$patient_id)->first();
        $getAgeServices = DB::table('preferred_channels_for_reminders_setting as pcr')
            ->leftjoin('examinations','examinations.id','pcr.service_id')
            ->where('pcr.activated_reminder','age')
            ->whereNull('pcr.deleted_at')
            ->where('examinations.show_as_reminder','1')
            ->whereNull('examinations.deleted_at')
            ->get(['examinations.id as service_id','examinations.name as service_name', 'pcr.notify_time', 'pcr.age_from', 'pcr.age_to']);

        if(!empty($getAgeServices)){
            foreach ($getAgeServices as $ke => $ser)
            {
                $addrecord=0;
                if($pat->birth_date) {
                    $from = new DateTime($pat->birth_date);
                    $to   = new DateTime('today');
                    $age =  $from->diff($to)->y;
                }
                else {
                    $age =  $pat->age;
                }
                log::info("_ageReminderOnUpdateAge");
                log::info($age.">>>".$ser->age_to.">>>".$ser->age_from);
                if($age == $ser->age_from || ($age < $ser->age_to && $age > $ser->age_from))
                    $addrecord=1;
                else if($age > $ser->age_to) $addrecord=2;
                else if($age==0) $addrecord=3;
                $updatePatientAge=DB::table('patients')->where('id',$pat->id)->update(['age'=>$age]);
                $checkRecord = DB::table('patient_has_service_reminder')
                                ->where('patient_id', $pat->id)
                                ->where('service_id', $ser->service_id)
                                ->where('reminder_status', 'Set')
                                ->where('type', 'age')
                                ->whereNull('deleted_at')
                                ->get(['id']);
                log::info($checkRecord);
                log::info($addrecord);
                log::info($age);
                log::info($ser->service_id);
                if(sizeof($checkRecord) == 0)
                {
                    //log::info("AgebaseServicesReminders====>".$addrecord.">>>".$pat->id);
                    if($addrecord==1)
                    {
                        $PatientsHasServiceReminder = array();
                        $PatientsHasServiceReminder['patient_id']      = $pat->id;
                        $PatientsHasServiceReminder['appointment_id']  = 0;
                        $PatientsHasServiceReminder['service_id']      = $ser->service_id;
                        $PatientsHasServiceReminder['parent_id']       = 0;
                        $PatientsHasServiceReminder['reminder_date']   = date('Y-m-d ').$ser->notify_time.':00';
                        $PatientsHasServiceReminder['reminder_status'] = 'Set';
                        $PatientsHasServiceReminder['type']            = 'age';
                        $PatientsHasServiceReminder['status']          = 'activate';
                        $PatientsHasServiceReminder['created_at']      = date('Y-m-d H:i:s');
                        //echo "<pre>";print_r($PatientsHasServiceReminder);
                        //$PatientsHasServiceReminder->save();
                        DB::table('patient_has_service_reminder')->insertGetId($PatientsHasServiceReminder);
                        //Send Notification========================================
                        $is_reminder_execute = DB::table('settings')
                                            ->where(['setting_key'=>'REMINDER_SETTING','status'=>'1'])->first();
                        $channel = DB::table('preferred_channels_for_reminders_setting')
                                       ->where('type','global')
                                       ->select('choice_of_channels')
                                       ->first();
                        if(!empty($is_reminder_execute))
                        {
                            // check patinet have installed app
                            $mobileId = DB::table('patient_has_device')
                            ->where('patient_id',$pat->id)
                            ->get(['device_id']);
                            if(!empty($mobileId) && count($mobileId))
                                self::_sendPushNotification($mobileId,$pat,$ser);
                            
                            if($channel->choice_of_channels == 'sms')
                            {
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
                                    self::_sendSmsAppointmentsReminder($phone_no,$pat,$ser);
                                }
                                elseif (!empty($pat->email) && $pat->sendMail==1)
                                {
                                    self::_sendMailReminder($pat,$ser);
                                }
                            }
                            elseif($channel->choice_of_channels == 'email')
                            {
                                if (!empty($pat->email) && $pat->sendMail==1)
                                {
                                    self::_sendMailReminder($pat,$ser);
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
                                    self::_sendSmsReminder($phone_no,$pat,$ser);
                                //}
                            }
                        }
                        //End send notification=====================================
                    }
                }
                if($addrecord!=1){
                    //log::info("dleterecord>>".$ser->service_id.">>>".$pat->id);
                    DB::table('patient_has_service_reminder')
                    ->where('service_id',$ser->service_id)
                    ->where('patient_id',$pat->id)
                    ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                }
            }
        } 
    }

     /*-----------------------------------
    |  Send push notification
    -------------------------------------------------*/
    public function _sendPushNotification($mobileId,$value,$service)
    {
        log::info("notification");
        $appointment_type   = '';        
        $fname =  '';
        $lname = '';
        $Doctor_name  = "";
        $appointment_date_time   = '';
        $appointment_time = '';
        $appointment_id     = '';
        $appointment_type_id = '';
        $doctor_speciality = '';
        // Examination Details
        $exams= [];
        $exam_name = $service->service_name;
        $doctor_image = asset('assets/admin/images/default-image.png');
        $title = 'Erinnerung an Ihren Termin';
        $patient_name = $value->first_name .' '.$value->family_name;
        // GET CONTENT
        $textContent = DB::table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->first();
        $content = 'Hallo '.$patient_name.', '.$textContent->reminder_push_notification_text." ".$exam_name;
        $mobile_uuids = array_column($mobileId->toArray(), "device_id");
        $player_ids   = $mobile_uuids;
        $headings     = array("en" => (string)$title);
        // Create an single string of all content
        $content      = array("en" => (string)$content);
        // Reminder details
        $postData = array(
                        "appointment_id" => $appointment_id,
                        "date_time"     => $appointment_date_time,
                        "doc_name"      => $Doctor_name,
                        "doc_speciality" => $doctor_speciality,
                        "appointment_type"    => $appointment_type,
                        "appointment_type_id" => $appointment_type_id,
                        "doc_img"             => $doctor_image,
                        "exams"          => $exams
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
       // dd($fields);
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
        // $updateStatus = DB::table('patient_has_service_reminder')->find($value->reminder_id);
        // $responseRecord = DB::table('patient_has_service_reminder')
        //       ->where('id',$value->reminder_id)
        //       ->update(['reminder_status'=>'executed','updated_at'=>date('Y-m-d H:i:s'),'media'=>'push_notification']); 
    }

    /*-----------------------------------
    |  Send mail
    -------------------------------------------------*/
    public function _sendMailReminder($value,$servcie)
    {
        log::info("mail-Send");
        $patientDetails = DB::table('patients')->find($value->id);
        $name = $patientDetails->first_name.' '.$patientDetails->family_name;
        $email = $patientDetails->email;
        $textContent = DB::table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->first();
        $text   = $textContent->reminder_mail_notification_text."<br/><b><a href='https://puregyn.puremed.biz/oa/services/".base64_encode($servcie->service_id)."'>".$servcie->service_name."</a></b>";               
        $result = Mail::to($email)->send(new AppointmentMail($name,$text));
        //echo "here".$result;
    }

    /*-----------------------------------
    |  Send sms
    -------------------------------------------------*/
    public function _sendSmsReminder($phones,$value,$servcie)
    {
        $textContent = DB::table('preferred_channels_for_reminders_setting')
                       ->where('id','1')
                       ->first();
        $URL='https://puregyn.puremed.biz/oa/services/'.base64_encode($servcie->service_id);
        $text   = $textContent->reminder_sms_notification_text." ".$URL."\n\n\r\r".$servcie->service_name;
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
            try
            {
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
            } // catch everything that's not a successfully sent message
            catch (WebSmsCom_ParameterValidationException $e)
            {
                $responseRecord = array(
                                        'error' => 1 ,
                                        'code' =>1,
                                        'message' => "ParameterValidationException caught: ".$e->getMessage()
                                    );
                log::info("response1 = ParameterValidationException caught: ".$e->getMessage());
                //exit("ParameterValidationException caught: ".$e->getMessage()."\n");
            }
            catch (WebSmsCom_AuthorizationFailedException $e)
            {
                //  exit("AuthorizationFailedException caught: ".$e->getMessage()."\n");
                $responseRecord = array(
                                        'error' => 1 ,
                                        'code' =>1,
                                        'message' => "AuthorizationFailedException caught: ".$e->getMessage()
                                    );
                log::info("response2 = AuthorizationFailedException caught: ".$e->getMessage());
            }
            catch (WebSmsCom_ApiException $e)
            {
                $responseRecord['message'] = "ApiException Exception: ".$e->getCode().$e->getMessage();
                log::info("response3 = ApiException Exception: ".$e->getCode().$e->getMessage());
            }
            catch (WebSmsCom_HttpConnectionException $e)
            {
                $responseRecord['message'] = "HttpConnectionException caught: ".$e->getMessage();
                log::info("response4 = HttpConnectionException caught: ".$e->getMessage());
            }
            catch (WebSmsCom_UnknownResponseException $e)
            {
                $responseRecord['message'] =  "UnknownResponseException caught: ".$e->getMessage();
                log::info("response5 = UnknownResponseException caught: ".$e->getMessage());
            }
            catch (Exception $e)
            {
                $responseRecord['message'] =  "Exception caught: ".$e->getMessage();
                log::info("response6 = Exception caught: ".$e->getMessage());
            }
            $responseRecord['receipient'] = $recipientAddressList; 
            return $responseRecord;
        }
    }  
    //Set Reminders when appointment is created for pass date Added on 9-Jun-23 by swati=========================
    function _remindersPassAppointments($appointmentID){
        $doneAppoitment =  DB::table('appointment')
                            ->select('patient_id','appointment.id as appointment_id','appointment_type_id','patients.birth_date','patients.age','appointment.start_date')
                            ->leftjoin('patients','patients.id','appointment.patient_id')
                            ->orderBy('start_date','DESC')
                            ->whereIn('appointment_status',array('Fertig'))
                            ->where('appointment.id',$appointmentID)
                            ->where('reminder_status','0')
                            ->first();
        if(!empty($doneAppoitment)){
            $allServices = DB::table('appointment_has_examinations')
            ->select('examinations.*')
            ->leftjoin('examinations','examinations.id','appointment_has_examinations.examination_id')
            ->where('appointment_id',$doneAppoitment->appointment_id)
            ->where('examinations.show_as_reminder','1')
            ->get();
            //dd($allServices);
            if(!empty($doneAppoitment->birth_date))               
            {
                $from = new DateTime($doneAppoitment->birth_date);
                $to   = new DateTime('today');
                $age =  $from->diff($to)->y;
                $data['age'] = $age; 
            }
            else {
                $data['age'] = $doneAppoitment->age; 
            }
            $data['birth_date'] = $doneAppoitment->birth_date;
            if(!empty($allServices) && count($allServices) > 0)
            {
                $this->_checkAndAddServiceReminder($allServices,$doneAppoitment->patient_id,$doneAppoitment->appointment_id,$doneAppoitment->start_date,$data);  
                DB::table('appointment')->where('id',$doneAppoitment->appointment_id)->update(['reminder_status'=>'1']);
            }
        }
    }
  
    public function _reactivePassAppoitment($appointmentID)
    {
        // DB::enableQueryLog();
        $sql = "SELECT * FROM patient_has_reminder WHERE id IN (
                  select max(patient_has_reminder.id) from patient_has_reminder
                  left join patient_has_service_reminder on patient_has_service_reminder.id = patient_has_reminder.service_reminder_id
                  where date(`last_reminder_date`) < CURRENT_DATE()
                  and appointment_id=$appointmentID and patient_has_reminder.deleted_at is null and
                  reminder_status!='ignore' GROUP by patient_has_service_reminder.patient_id,service_id)";
        $reactivateReminder = DB::select($sql);
        // print_r(DB::getQueryLog());
        // log::info($reactivateReminder);
        if(!empty($reactivateReminder) && count($reactivateReminder) > 0)
        {
            // log::info("if");
            foreach ($reactivateReminder as $reminder_key => $reminder_value) 
            {
                $is_appoitment_book = DB::table('appointment')
                                    ->whereDate('start_date', '>=', $reminder_value->last_reminder_date)
                                    ->whereDate('start_date', '<=', $reminder_value->next_reminder_date)
                                    ->where('patient_id',$reminder_value->patient_id)
                                    ->get();
                $reminder_details = DB::table('patient_has_service_reminder')
                                        ->where('id',$reminder_value->service_reminder_id)
                                        ->first();
                                        
                if(!empty($is_appoitment_book ) && count($is_appoitment_book ) > 0)
                {
                    $reactivateReminder =  DB::table('patient_has_reminder')
                                       ->where('id',$reminder_value->id)
                                       ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                    //Delete old remidners =======================
                    DB::table('patient_has_service_reminder')
                        ->where('patient_id',$reminder_details->patient_id)
                        ->where('service_id',$reminder_details->service_id)
                        ->where('appointment_id',$reminder_details->appointment_id)
                        ->where('type',$reminder_details->type)
                        ->whereDate('reminder_date','<',date("Y-m-d"))
                        ->update(['deleted_at'=>date('Y-m-d H:i:s'),'reminder_status'=>'ignore']);

                }
                else {
                    // $value1_days = $this->_getDate("",$value->age_period_controls,$value->age_period_frequency_type);
                    // $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));

                    if(!empty($reminder_details)){

                        $serviceDetail =DB::table('examinations')
                                    ->where('id',$reminder_details->service_id)
                                    ->first();

                        $patientDetails = DB::table('patients')
                                    ->select('age','birth_date','id')
                                    ->where('id',$reminder_details->patient_id)
                                    ->first();

                        if(!empty($patientDetails->birth_date))               
                        {
                            $from = new DateTime($patientDetails->birth_date);
                            $to   = new DateTime('today');
                            $age =  $from->diff($to)->y;
                            $data['age'] = $age; 
                        }else
                        {
                             $data['age'] = $patientDetails->age; 
                        }
                        $data['birth_date'] = $patientDetails->birth_date;
                        
                        $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
                                            [
                                            'service_id' => $serviceDetail->id,
                                            // 'is_reminder_updated' => '0'
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
                            $h_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
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
                        }  
                        $todays_date = $reminder_value->next_reminder_date;

                        $appointment_id = $reminder_details->appointment_id; 
                        $patient_id = $reminder_details->patient_id;
                        $ageReminder = DB::table('preferred_channels_for_reminders_setting')->where(
                            ['service_id' => $reminder_details->service_id,'activated_reminder' => 'age']
                            )->get();
                        //Delete old remidners =======================
                         DB::table('patient_has_service_reminder')
                            ->where('patient_id',$reminder_details->patient_id)
                            ->where('service_id',$reminder_details->service_id)
                            ->where('appointment_id',$reminder_details->appointment_id)
                            ->where('type',$reminder_details->type)
                            ->whereDate('reminder_date','<',date("Y-m-d"))
                            ->update(['deleted_at'=>date('Y-m-d H:i:s'),'reminder_status'=>'ignore']);

                         $reactivateReminder =  DB::table('patient_has_reminder')
                                           ->where('id',$reminder_value->id)
                                           ->update(['deleted_at'=>date('Y-m-d H:i:s')]); 

                        if($default_reminder == 'general')
                        {
                            $this->_reactivateGeneralReminder($is_service_has_reminder,$appointment_id,$todays_date,$patient_id,$serviceDetail->id);
                        }
                        else if($default_reminder == 'age'  && !empty($data['age']) && $data['age']!='' && !empty($ageReminder->toArray()) && count($ageReminder) < 2)  
                        {   
                            // log::info("_reactivateAgeReminder1");
                            $this->_reactivateAgeReminder($ageReminder[0],$appointment_id,$todays_date,$patient_id,$data,$serviceDetail->id); 
                        }  
                        else if($default_reminder == 'checkup')
                        {
                             $this->_reactivateControlReminder($is_service_has_reminder,$appointment_id,$todays_date,$patient_id,$serviceDetail->id);

                        }
                    }   
                }
            }
        } 
        
        // $allServices = DB::table('appointment_has_examinations')
        //                     ->select('examinations.*')
        //                     ->leftjoin('examinations','examinations.id','appointment_has_examinations.examination_id')
        //                     ->where('appointment_id',$appointmentID)
        //                     ->where('examinations.show_as_reminder','1')
        //                     ->get();
        // if(!empty($allServices)){
        //     if(!empty($allServices) && count($allServices))
        //     {
        //         log::info("_reactivePassAppoitment4");
        //         foreach($allServices as $service){
        //             $sql2 = "SELECT * FROM patient_has_reminder WHERE id IN (
        //                 select max(patient_has_reminder.id) from patient_has_reminder
        //                 left join patient_has_service_reminder on patient_has_service_reminder.id = patient_has_reminder.service_reminder_id
        //                 where date(`last_reminder_date`) < CURRENT_DATE()
        //                 and appointment_id=$appointmentID and service_id=$service->id
        //                 and reminder_status!='ignore' GROUP by patient_has_service_reminder.patient_id,service_id)";//and patient_has_reminder.deleted_at is null
        //             $checkServiceReminder = DB::select($sql2);
        //             if(!empty($checkServiceReminder)){
        //                 $getRemindersCount = DB::table('patient_has_service_reminder')
        //                                 ->where('service_id',$service->id)
        //                                 ->where('appointment_id',$appointmentID)
        //                                 ->get();
        //                 $reminderCount = $getRemindersCount->count();
        //                 log::info("reminderCount");
        //                 log::info($reminderCount);
        //                 if($reminderCount < 10){
        //                     //$this->_reactivePassAppoitment($appointmentID);
        //                 }
        //             }
        //         }
        //     }
        // }
    }
    public function _reactivateGeneralReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        $reminder_array = [];

        // 1st reminder
        $start_date = $start_date;

        $reminder_array[] = $start_date;

        for($i=0; $i<($is_service_has_reminder->general_number_of_interval-1); $i++)
        {
            $value4_days = $this->_getDate($start_date,$is_service_has_reminder->general_time_interval,$is_service_has_reminder->general_time_interval_frequency_type);
            
            // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));

            $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

            if( $third_reminder !=  $start_date)
            {
                $reminder_array[] = $third_reminder;
            }
        }       
        sort($reminder_array);
        $reminder_id = 0;
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
        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$endCycleDyas,$is_service_has_reminder->holiday_reminder,'plus');

        for($i=0;$i<count($reminder_array);$i++)
        { 
            $reminder_tmp = [];
            $reminder_tmp['patient_id'] = $patient_id;
            $reminder_tmp['appointment_id'] = $appointment_id;
            $reminder_tmp['service_id'] = $service_id;
            $reminder_tmp['reminder_date'] = $reminder_array[$i];
            //Added by swati 12-May-23===================================
            if($endCycleDyas!=0 && $reminder_array[$i] >= $endcycle_date ) $reminder_tmp['reminder_status']='ignore';
            else if(date("Y-m-d",strtotime($reminder_array[$i])) < date("Y-m-d") ) $reminder_tmp['reminder_status']='ignore';
            else $reminder_tmp['reminder_status'] = 'Set';
            $reminder_tmp['status'] = 'activate';
            $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;  
          //  $reminder_tmp['parent_id'] = $parent_id;
            $reminder_tmp['type'] = 'general';

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
        }

        $value5_days = $this->_getDate(current($reminder_array),$is_service_has_reminder->general_new_frequency,$is_service_has_reminder->general_new_frequency_type);

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));

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

    public function _reactivateAgeReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$data,$service_id)
    {
        // log::info('_reactivateAgeReminder');
        $reminder_array = [];
        if($data['age'] == $is_service_has_reminder->age_from || $data['age'] <= $is_service_has_reminder->age_to )
        {
            $start_date = $start_date;
           
            $reminder_array[] = $start_date;

            for($i=0; $i<($is_service_has_reminder->age_number_of_interval-1); $i++)
            {
                $value4_days = $this->_getDate($start_date,$is_service_has_reminder->age_time_interval,$is_service_has_reminder->age_time_interval_frequency_type);
                
                // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

                if( $third_reminder !=  $start_date)
                {
                    $reminder_array[] = $third_reminder;
                }
            }       
            sort($reminder_array);
        }
        elseif($data['age'] < $is_service_has_reminder->age_from)
        {
            $diff = $is_service_has_reminder->age_from - $data['age'];
            $start_date = date('Y-m-d', strtotime($data['birth_date']. ' + '.$diff.' year'));
            $period_date = $start_date." ".date('H:i:s');
            $reminder_array[] = $period_date;

            for($i=0; $i<($is_service_has_reminder->age_number_of_interval-1); $i++)
            {
                $value4_days = $this->_getDate($period_date,$is_service_has_reminder->age_time_interval,$is_service_has_reminder->age_time_interval_frequency_type);
                
                // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
                $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

                  $reminder_array[] = $third_reminder;
                
            }       
            sort($reminder_array);
        }
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
        $finalDays=($endCycleDyas+$agePeriodDays);                
        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');
        // log::info("$endcycle_date".$endcycle_date.">>>>".$finalDays);
        $reminder_id = 0;
        for($i=0;$i<count($reminder_array);$i++)
        { 
            $reminder_tmp = [];
            $reminder_tmp['patient_id'] = $patient_id;
            $reminder_tmp['appointment_id'] = $appointment_id;
            $reminder_tmp['service_id'] = $service_id;
            $reminder_tmp['reminder_date'] = $reminder_array[$i];
            //Added by swati 12-May-23===================================
            // log::info($endCycleDyas.">>>".$endcycle_date.">>>".$reminder_array[$i]);
            $Idate=date("Y-m-d",strtotime($reminder_array[$i]));
            // log::info($Idate);
            if($reminder_array[$i] >= $endcycle_date ) $reminder_tmp['reminder_status']='ignore';
            else if(date("Y-m-d",strtotime($reminder_array[$i])) < date("Y-m-d") ) $reminder_tmp['reminder_status']='ignore';
            else $reminder_tmp['reminder_status'] = 'Set';
            $reminder_tmp['status'] = 'activate'; 
            $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ; 
           // $reminder_tmp['parent_id'] = $parent_id;
            $reminder_tmp['type'] = 'age';

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

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));
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

    public function _reactivateControlReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        $reminder_array = [];        
        // 1st reminder
        $start_date = $start_date;
        
        $reminder_array[] = $start_date;
        for($i=0; $i<($is_service_has_reminder->checkup_number_of_interval-1); $i++)
        {
            $value4_days = $this->_getDate($start_date,$is_service_has_reminder->checkup_time_interval,$is_service_has_reminder->checkup_time_interval_frequency_type);
            
            // $third_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value4_days." day"));
            $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');

            if( $third_reminder !=  $start_date)
            {
                $reminder_array[] = $third_reminder;
            }
        }       
        sort($reminder_array);
        $reminder_id = 0;
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
        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$endCycleDyas,$is_service_has_reminder->holiday_reminder,'plus');

        for($i=0;$i<count($reminder_array);$i++)
        { 
            $reminder_tmp = [];
            $reminder_tmp['patient_id'] = $patient_id;
            $reminder_tmp['appointment_id'] = $appointment_id;
            $reminder_tmp['service_id'] = $service_id;
            $reminder_tmp['reminder_date'] = $reminder_array[$i];
            //Added by swati 12-May-23===================================
            if($endCycleDyas!=0 && $reminder_array[$i] >= $endcycle_date ) $reminder_tmp['reminder_status']='ignore';
            else if(date("Y-m-d",strtotime($reminder_array[$i])) < date("Y-m-d") ) $reminder_tmp['reminder_status']='ignore';
            else $reminder_tmp['reminder_status'] = 'Set';
            $reminder_tmp['status'] = 'activate';  
            $reminder_tmp['created_at'] = date('Y-m-d h-i-s') ;
          //  $reminder_tmp['parent_id'] = $parent_id;
            $reminder_tmp['type'] = 'control';

            //Added by Shyam 14-01-22
            $is_exists = DB::table('patient_has_service_reminder')
                            ->where('patient_id', $patient_id)
                            ->where('appointment_id', $appointment_id)
                            ->where('service_id', $service_id)
                            ->where('reminder_date', $reminder_array[$i])
                            ->where('reminder_status', 'Set')
                            ->where('status', 'activate')
                            ->where('type', 'control')
                            ->whereNull('deleted_at')
                            ->get();
            if(count($is_exists) == 0)
            {
                $reminder_id = DB::table('patient_has_service_reminder')->insertGetId($reminder_tmp);
            }
        }
        
        $value5_days = $this->_getDate(current($reminder_array),$is_service_has_reminder->checkup_new_frequency,$is_service_has_reminder->checkup_new_frequency_type);

        // $reactive_reminder = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime(end($reminder_array))) . " +".(int)$value5_days." day"));
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

    public function _checkAndAddServiceReminder($all_services,$patient_id,$appointment_id,$appointment_start_date,$data)
    {

        if(!empty($all_services) && count($all_services) > 0)
        {
            foreach ($all_services as $service_key => $service_value) 
            {
                $is_service_has_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_value->id,
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
                }  
                $is_doctor_set_reminder = db::table('patient_has_service_control_reminder_setting')->where(
                    ['patient_id' => $patient_id,
                    'appointment_id' => $appointment_id,
                    'service_id' => $service_value->id,
                    'status' => '1',
                    ]
                    )->first();

                if($is_doctor_set_reminder)
                {
                    // $is_service_has_reminder->checkup_period_controls =  $is_doctor_set_reminder->control_interval;
                    // $is_service_has_reminder->checkup_period_frequency_type =  $is_doctor_set_reminder->control_frequency;
                } 
                /*Check if that service is general and it is set reminder for 
                 another service added by swati 19-Sep-22*/
                $check_general_recommanded_remidner = DB::table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'service_id' => $service_value->id,
                                        'activated_reminder' => 'general'
                                        ]
                                        )->first(['recommanded_service_id']);
                if(!empty($check_general_recommanded_remidner) && $check_general_recommanded_remidner->recommanded_service_id)
                      $service_id = $check_general_recommanded_remidner->recommanded_service_id;
                else  $service_id = $service_value->id;
                // Log::info('Default setting');
                // Log::info($default_reminder);
                // Log::info($patient_id);
                /*END Check if that service is general and it is set reminder for another service*/
                if($default_reminder == 'general')
                {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    // log::info("ReminderStatus-_checkAndAddServiceReminder");
                    // Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_generalReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                }          
                else if($default_reminder == 'age')
                {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    // log::info("ReminderStatus-AGE-_checkAndAddServiceReminder");
                    // Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_ageReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                }      
                else if($default_reminder == 'checkup')
                {
                    $a_date = explode(" ",$appointment_start_date);
                    $appointment_start_date = $a_date[0]." ".$is_service_has_reminder->notify_time.":00";
                    log::info("GeneralTrait-CHECKUP-_checkAndAddServiceReminder");
                    Log::info($appointment_start_date.">>".$service_id.">>".$appointment_id);
                    $this->_controlReminder($is_service_has_reminder,$appointment_id,$appointment_start_date,$patient_id,$service_id);
                }                   
            }
        }  
    }
    public function _generalReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
    {
        $reminder_array = [];
        // 1st reminder
        $start_date = $start_date;
        $value1_days = $this->_getDate($start_date,$is_service_has_reminder->general_period,$is_service_has_reminder->general_period_frequency_type);
        $period_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($start_date)) . " +".(int)$value1_days." day"));
        $value3_days = $this->_getDate($period_date,$is_service_has_reminder->general_first_frequency,$is_service_has_reminder->general_first_frequency_type);
        $first_reminder = $this->_filterWeekendAndHoiliday($period_date,$value3_days,$is_service_has_reminder->holiday_reminder,'minus');
        $reminder_array[] = $first_reminder;
        for($i=0; $i<($is_service_has_reminder->general_number_of_interval-1); $i++)
        {
            $value4_days = $this->_getDate($period_date,$is_service_has_reminder->general_time_interval,$is_service_has_reminder->general_time_interval_frequency_type);            
            $third_reminder = $this->_filterWeekendAndHoiliday(end($reminder_array),$value4_days,$is_service_has_reminder->holiday_reminder,'plus');
            if( $third_reminder !=  $first_reminder)
            {
                $reminder_array[] = $third_reminder;
            }
        }       
        sort($reminder_array);
        // Log::info("ReminderStatus-_generalReminder-".$patient_id);
        // Log::info($reminder_array);
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

                    /*****Remove**general reminder****9-march-26*********/
                    $generalServiceId = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where('appointment_id',$appointment_id) 
                                ->where('patient_id',$patient_id)
                                ->where('type','general')
                                ->whereNull('deleted_at')
                                ->where('service_id', $service_id)
                                ->orderBy('id','desc')
                                ->get();

                    if(isset($generalServiceId) && !empty($generalServiceId))
                    {
                        //Get reminder entry for above general service id and delete it for previous appointemnt
                        $previousAppointmentIds = DB::connection('tenant')->table('patient_has_service_reminder')
                            ->where('type','control')
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

                            Log::info('id holder====>');      
                           Log::info($service_id_holder); 

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

                        }//previousAppointmentIds    
                    }//if  generalServiceId                 

                   /*****Remove**general reminder***9-march-26*******/   
                }
            }
            $value5_days = $this->_getDate(current($reminder_array),$is_service_has_reminder->general_new_frequency,$is_service_has_reminder->general_new_frequency_type);
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
       
    }

    public function _ageReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
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
        // if(!empty($firstReminderdate)) 
        //     $first_remidner_date=$firstReminderdate->reminder_date;
        // else $first_remidner_date=$start_date;
        // $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_end_cycle,$is_service_has_reminder->age_end_cycle_frequency_type);  
        // $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);
        // $finalDays=($endCycleDyas+$agePeriodDays);    
        // $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');
        if(!empty($firstReminderdate)) 
            $first_remidner_date=$firstReminderdate->reminder_date;
        else $first_remidner_date=$start_date;
        $endCycleDyas = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_end_cycle,$is_service_has_reminder->age_end_cycle_frequency_type);  
        $agePeriodDays = $this->_getDate(($first_remidner_date),$is_service_has_reminder->age_period_controls,$is_service_has_reminder->age_period_frequency_type);
        $periodOneminusthird=($agePeriodDays-$value3_days);
        $finalDays=($endCycleDyas+$periodOneminusthird); 
        $endcycle_date = $this->_filterWeekendAndHoiliday(($first_remidner_date),$finalDays,$is_service_has_reminder->holiday_reminder,'plus');
        // log::info($service_id);
        // log::info($endcycle_date);
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

                // else if(date("Y-m-d",strtotime($reminder_array[$i])) < date("Y-m-d") ) $reminder_tmp['reminder_status']='ignore';
    
                $reminder_tmp['status'] = 'activate';  
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
       
    }
    public function _controlReminder($is_service_has_reminder,$appointment_id,$start_date,$patient_id,$service_id)
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
        log::info("$first_remidner_date>>".$first_remidner_date."Endcycleday".$endCycleDyas.">>agePeriodDays".$agePeriodDays.">>periodOneminusthird".$periodOneminusthird."$finalDays".$finalDays.">>endcycle_date".$endcycle_date);
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
                //$reminder_tmp['reminder_status'] = 'executed';
                //Added on 04-Sep-23===================================
                // if($endCycleDyas!=0 && $reminder_array[$i] >= $endcycle_date ) $reminder_tmp['reminder_status']='ignore';
                // else $reminder_tmp['reminder_status'] = 'Set';
                $date1 = new DateTime($reminder_array[$i]);
                $date2 = new DateTime($endcycle_date);
                $date_today=new DateTime();
                $reminder_tmp['reminder_status'] = 'Set';
                if($endCycleDyas>0){
                    if($date1 >= $date2 ) $reminder_tmp['reminder_status']='ignore';
                    else if($date2<$date_today) $reminder_tmp['reminder_status']='ignore';
                }

                $reminder_tmp['status'] = 'activate';  
                $reminder_tmp['type'] = 'control';

                //Added by Shyam 14-01-22
                $is_exists = DB::connection('tenant')->table('patient_has_service_reminder')
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
                    Log::info("GeneralTrait-_controlReminder-".$patient_id);
                    $reminder_id = DB::connection('tenant')->table('patient_has_service_reminder')->insertGetId($reminder_tmp);
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
    }
    //================================================================================

    //Added by swati on 26-Jun-2022 to get appointment type attached without description servcies====
    public function getHiddenExamination($patient_id,$appointment_id)
    {

        Log::info("in getHiddenExamination"); 
        Log::info($patient_id);
        Log::info($appointment_id);

        $data = $finalDat = [];
        $getAppointment = $this->BaseModel->find($appointment_id);
        if(!empty($getAppointment))
        {
            $appointment_type_id = $getAppointment->appointment_type_id;
            $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                // ->whereRaw("examinations.show_as_reminder='1'")
                                ->get([
                                    'examinations.id',
                                    'examinations.name',
                                    'examinations.description'
                                ]);      


            Log::info("in getHiddenExamination collections1");
            Log::info($collections1);
                                           
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


            Log::info("in getHiddenExamination collections1 again");
            Log::info($collections1);

            $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));  
            $collections2 = $this->PatientsHasServiceReminderModel
                            ->select(DB::raw('examinations.id,examinations.name,examinations.description,max(patient_has_service_reminder.reminder_date),reminder_status'))
                            ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                            ->join(     
                                        //commeted on 25-aug-25
                                        /*DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                            FROM patient_has_service_reminder 
                                            WHERE patient_id='".$patient_id."' and reminder_status IN('ignore','Set') 
                                            and status='activate'
                                            and deleted_at is NULL GROUP BY service_id)
                                        patientremidners"),*/

                                         //start added on 25-aug-25
                                        //cycle>=2 and app id 0 or not condition added on 27-jan-26

                                          DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder 
                                           WHERE patient_id='".$patient_id."' and reminder_status IN('ignore','Set') 
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
                                        //end added on 25-aug-25   

                                        function($join)
                                        {
                                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                                        })
                            ->where('patient_has_service_reminder.patient_id',$patient_id)
                            ->where('patient_has_service_reminder.status','activate')
                            ->whereNotIn('examinations.id',$exams_ids) 

                            //->whereRaw("date(reminder_date) <= '".$today_date."'") //commented on 25-aug-25 



                            // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                            //                     select service_id from patient_has_service_reminder 
                            //                     where `patient_has_service_reminder`.`patient_id`=".$patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)")) 
                            // ->where('patient_has_service_reminder.reminder_status','Set') 
                            ->whereRaw("examinations.show_as_reminder='1'")
                            ->groupBy('patient_has_service_reminder.service_id')  
                            ->get();


                Log::info("in getHiddenExamination collections2");
                Log::info($collections2);            

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

            Log::info("in getHiddenExamination collections2 again");
            Log::info($collections2);
    

            $getrecord = $collections1->merge($collections2);

            Log::info("in getHiddenExamination getrecord");
            Log::info($getrecord);

            $servicesRecommanded=array();
            if(!empty($getrecord) && sizeof($getrecord)>0)
            {
                $cnt = 0;
                foreach ($getrecord as $key => $value) 
                {
                    // $app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);//commented on 13-apr-26
                      $app_type_name = $this->AppointmentTypesModel->withTrashed()->find($appointment_type_id); //changed on 13-apr-26

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

        Log::info("in getHiddenExamination servicesRecommanded");
        Log::info($servicesRecommanded);

        return $servicesRecommanded;
    }
    //================================================================================

    //************ Added the common function formatDate by roshani on 29-o5-2024 *************//
    public function formatDate($date)
    {
        if($date)
        {
            try {
                if (preg_match('/\d{4}\/\d{2}\/\d{2}/', $date)) {
                    // Handle date format 'Y/m/d'
                    $carbonDate = Carbon::createFromFormat('Y/m/d', $date);
                    return $carbonDate->format('d.m.Y');
                } elseif (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $date)) {
                    // Handle date and time format 'Y-m-d H:i:s'
                    $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $date);
                    return $carbonDate->format('d.m.Y H:i:s');
                } elseif (preg_match('/\d{4}-\d{2}-\d{2}/', $date)) {
                    // Handle date format 'Y-m-d'
                    $carbonDate = Carbon::createFromFormat('Y-m-d', $date);
                    return $carbonDate->format('d.m.Y');
                } elseif (preg_match('/\d{2}-\d{2}-\d{4}/', $date)) {
                    // Handle date format 'd-m-Y'
                    $carbonDate = Carbon::createFromFormat('d-m-Y', $date);
                    return $carbonDate->format('d.m.Y');
                } elseif (preg_match('/\d{2}\/\d{2}\/\d{4}/', $date)) {
                    // Handle date format 'd/m/Y'
                    $carbonDate = Carbon::createFromFormat('d/m/Y', $date);
                    return $carbonDate->format('d.m.Y');
                } elseif (preg_match('/\d{2}\.\d{2}\.\d{4}/', $date)) {
            // Handle date format 'd.m.Y'
                    $carbonDate = Carbon::createFromFormat('d.m.Y', $date);
                    return $carbonDate->format('Y-m-d');
                } else {
                    // Invalid format
                    throw new \Exception("Invalid date format");
                }
            } catch (\Exception $e) {
                return "Invalid date format";
            }
        }
    }
    //************ Added the common function for get patient details on 29-o5-2024 *************//
    public function setPatientDetails($patient_id) {
        $patientData = [];
        $patientData['currentDate'] = date("m/d/Y");
        $patientData['patientFullName'] = $patientData['patientDob'] = '';
        $getPatientDetails = $this->PatientsModel->where('id', $patient_id)->first();
        
        if (isset($getPatientDetails) && !empty($getPatientDetails)) {
            $patientFirstName = isset($getPatientDetails->first_name) ? $getPatientDetails->first_name : '';
            $patientLastName = isset($getPatientDetails->family_name) ? $getPatientDetails->family_name : '';
            $patientData['patientFullName'] = $patientFirstName . ' ' . $patientLastName;
            $patientData['patientDob'] = isset($getPatientDetails->birth_date) ? date("d-m-Y", strtotime($getPatientDetails->birth_date)) : '';
        }
        
        return $patientData;
    }
    //************ Added the common function for get patient details on 29-o5-2024 *************//
    
    //************ Added the common function for get patient details on 29-o5-2024 *************//

    //************ Added the common function formatDate by roshani for mobile number format on 29-o5-2024 *************//
    public function formatPhoneNumber($phone)
    {
        // Remove leading zeros
        $formattedPhone = ltrim($phone, '0');

        // Remove country code prefixes (+43, 0043)
        // if (substr($formattedPhone, 0, 2) == '43') {
        //     $formattedPhone = substr($formattedPhone, 2);
        // } 
        // else
        if (substr($formattedPhone, 0, 2) == '00') {
            $formattedPhone = substr($formattedPhone, 2);
        } elseif (substr($formattedPhone, 0, 3) == '+43') {
            $formattedPhone = substr($formattedPhone, 3);
        } elseif (substr($formattedPhone, 0, 1) == '+') {
            $formattedPhone = substr($formattedPhone, 1);
        }

        return $formattedPhone;
    }

    //************ Added the common function formatDate by roshani for mobile number format on 29-o5-2024 *************//

    //************ Added By vijay 17/7/2024 *************//
    public function encodeString($string)
    {
        $key = 'secret';
        $key = sha1($key);
        $strLen = strlen($string);
        $keyLen = strlen($key);
        $hash = '';  // Initialize $hash to avoid undefined variable notice
        $j = 0;  // Initialize $j to avoid undefined variable notice
        for ($i = 0; $i < $strLen; $i++) {
            $ordStr = ord(substr($string, $i, 1));
            if ($j == $keyLen) {
                $j = 0;
            }
            $ordKey = ord(substr($key, $j, 1));
            $j++;
            $hash .= strrev(base_convert(dechex($ordStr + $ordKey), 16, 36));
        }
        return $hash;
    }

    public function decodeString($string)
    {
        $key = 'secret';
        $key = sha1($key);
        $strLen = strlen($string);
        $keyLen = strlen($key);
        $hash = '';
        $j = 0;
        for ($i = 0; $i < $strLen; $i += 2) {
            $ordStr = hexdec(base_convert(strrev(substr($string, $i, 2)), 36, 16));
            if ($j == $keyLen) {
                $j = 0;
            }
            $ordKey = ord(substr($key, $j, 1));
            $j++;
            $hash .= chr($ordStr - $ordKey);
        }
        return $hash;
    }

    //************ Roshani Added the common function for add patient country on ordination on 16-10-2024 *************//

    public function addPatientCountryOnOrdination($patient_id)
    {

        Log::info("in addPatientCountryOnOrdination function");
        Log::info($patient_id);

        $addCountry = false;
        if(!empty($patient_id))
        {
            $getPatientDetails = $this->PatientsModel
                                        ->where('id',$patient_id)
                                        ->first();
            if(empty($getPatientDetails['country']))
            {
                $addCountry = false;
                $ordination_id = Config('ordination_id');
                // $ordination_id = 3;//For local device check

                $getDatabaseName = DB::connection('system')
                               ->table("ordination")
                               ->where('id',$ordination_id)
                               ->first();
                if(!empty($getDatabaseName))
                {
                    $ordinationCountry = $getDatabaseName->country;

                    Log::info("in addPatientCountryOnOrdination function ordinationCountry");
                    Log::info($ordinationCountry);

                    $getPatientDetails = $this->PatientsModel
                                        ->where('id',$patient_id)
                                        ->update(
                                            [ 'country'=> $ordinationCountry ]
                                        );
                    $addCountry = true;
                }
            }

        }
    }

    //************ Roshani Added the common function for add patient country on ordination on 16-10-2024 *************//


    //************ Roshani Added the common function for get database connection on 8-11-2024 for CR #210 *************//
    public static function getDatabaseConnection()
{
    if (!empty(config('ordination_id'))) 
    {
        // Fetch the tenant-specific UUID based on ordination_id
        $ordinationUuid = DB::connection('system')
            ->table("tenants")
            ->where('ordination_id', config('ordination_id'))
            ->value('uuid');

        if ($ordinationUuid) {
            // Purge any existing connection for this UUID
            DB::purge($ordinationUuid);

            // Dynamically configure the tenant database connection
            config()->set("database.connections.{$ordinationUuid}", [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'database' => $ordinationUuid,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);

            // Reconnect to the tenant database using the configured UUID
            DB::reconnect($ordinationUuid);

            return $ordinationUuid;  // Return the tenant database connection
        } else {
            throw new \Exception('Ordination UUID not found');
        }
    }

    // If no ordination_id is set, use the system database connection
    return 'system';  // Return the master system connection
}

    public static function checkForNewApps()
    {
        try {
            $connection = self::getDatabaseConnection();
            $apps = DB::connection($connection)
                      ->table("tablet_apks")
                      ->whereNull('deleted_at')
                      ->whereIn('app_name', ['SignDoc App', 'Master Data App', 'Waiting Number App'])
                      ->get();

            // Check if any app has `is_downloaded` set to 0
            $hasNewApp = $apps->contains(function ($app) {
                return $app->is_downloaded == 0;
            });

            // Return boolean value of new_tag directly
            return $hasNewApp;

        } catch (\Exception $e) {
            \Log::error("Error checking for new apps: " . $e->getMessage());
            return false;
        }
    }

    //************ Roshani Added the common function for get database connection on 8-11-2024 for CR #210 *************//

    // end 


    //added for #277 issue on 24-dec-24
     public function _createGeneralDocumentPdfUserProfile($inputdata,$patient_id,$appointment_id)
    {
        //dd($inputdata->all());
        $data = $dataFinal = [];
        $doc_flag = 0;
        $flag = '0';
        $file_name = $exam_id = $app_id = '';
        $exam_arr = $inputdata->exam_id;
        $doc_type = $inputdata->doc_type;
        if(!empty($appointment_id))
        {
            $app_id = $appointment_id;
        }
        $cnt = 0;
        foreach ($inputdata['doc_hd'] as $key=>$doc_list) 
        {
            $collections = $this->SpecialistDocumentsModel->find($doc_list);
            $days ='';          
            if(!empty($collections))
            {    

                //start added on 7-jan-25
                $header_image_path = self::getFilePath($collections['header_image_path']);
                $footer_image_path = self::getFilePath($collections['footer_image_path']);
                //end added on 7-jan-25



                $data['doc_id']            = $collections['id'];
                $data['name']              = $collections['name'];
                $data['html_text']         = $collections['html_text'];
                $data['background_color']  = $collections['background_color'];
                $data['header_image']      = $collections['header_image'];
                // $data['header_image_path'] = $collections['header_image_path'];
                $data['footer_image']      = $collections['footer_image'];
                // $data['footer_image_path'] = $collections['footer_image_path'];
                $data['background_color']  = $collections['background_color'];

                $data['header_image_path'] = $header_image_path; //added on 7-jan-25
                $data['footer_image_path'] = $footer_image_path;//added on 7-jan-25


                /*********Get Patient details added on 7-jan-25 ***********/
                $patientFirstName = $patientLastName = $patientFullName= $patientDob= ''; 
                $getPatientDetails = $this->PatientsModel->where('id',$patient_id)->first();
                if(isset($getPatientDetails) && !empty($getPatientDetails))
                {
                    $patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
                    $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
                    $patientFullName = $patientFirstName.' '.$patientLastName;
                    $patientDob = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
                }

                // Add patient data to data array added on 7-jan-25
                 $data['patientFullName'] = $patientFullName;
                 $data['patientDob'] = $patientDob;
                 $data['currentDate'] = date('m/d/Y');
                 /***************************************************/


                //$cnt++;
                // $PdfPath   = storage_path().'/app/public/document_pdf/';
                if(!empty(Config('ordination_id')))
                {
                    $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
                    $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/document_pdf/';
                }
                else {
                    $PdfPath = '/opt/app-shared/php/data/storage/app/public/document_pdf/';
                }
                //$PDFname   = $collections['name'].'_'.time().'.pdf';
                $PDFname = self::createPdfFileName($patient_id,$collections['name']);
                // Invoice full path
                $StorePath = $PdfPath.$PDFname; 
                $accessPath = '/document_pdf/'.$PDFname;
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
                // log::info("_createGeneralDocumentPdf-generalTrait");
                $pdf->loadView($PDFPath,compact('data'))->save($StorePath);
                // end
                //========================================================================
                // pdf
                $current_date = date('Y-m-d H:i:s');
                $start_date   = null;
                $end_date     = null;
                $days = '';
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
                    |Check List Selected questions
                */
                $CheckListHasSelectedQuestionModel = $this->PatientHasDocumentsModel 
                                                    ->where('patient_id',$patient_id)
                                                    ->where('type',$doc_type[$key]) //changed for #277 issue on 24-dec-24 
                                                    ->where('fk_document_id',$collections['id'])
                                                    ->first();
                if(!empty($CheckListHasSelectedQuestionModel))
                {
                    $doc_flag = 1;
                } 
                else {
                    $CheckListHasSelectedQuestionModel = new $this->PatientHasDocumentsModel; 
                    $doc_flag = 1;
                }
                if($doc_flag == 1)
                {
                    if(!empty($app_id))
                    {
                        $CheckListHasSelectedQuestionModel->appointment_id     = $app_id;
                    }
                    $CheckListHasSelectedQuestionModel->patient_id             = $patient_id;
                    //$CheckListHasSelectedQuestionModel->exam_app_type_id       = $appointment_id;
                    $CheckListHasSelectedQuestionModel->fk_examinations_id     = $exam_arr[$key];
                    $CheckListHasSelectedQuestionModel->fk_document_id         = $doc_list;

                    if (!empty($inputdata['doc']) && in_array($doc_list, $inputdata['doc']))
                    {
                        $CheckListHasSelectedQuestionModel->doc_status         = '1';
                    }
                    else {
                        $CheckListHasSelectedQuestionModel->doc_status         = '0';
                    }
                    $CheckListHasSelectedQuestionModel->pdf_name               = $PDFname;
                    $CheckListHasSelectedQuestionModel->pdf_path               = $accessPath;
                    $CheckListHasSelectedQuestionModel->type                   = $doc_type[$key];
                    //$CheckListHasSelectedQuestionModel->signature              = $file_name;
                    $CheckListHasSelectedQuestionModel->created_at             = Date('Y-m-d');
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
    }//_createGeneralDocumentPdfUserProfile

    //added on 11-march-25
    public function putFilePathManual($path, $file, $fileName)    
    {
         // Log::info('in trait putFilePath function');
        if(!empty(Config('ordination_id')))
        {
            
              // Log::info('if trait ordination id');
            //$fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $fileName);
            if(!File::isDirectory($path))
            {
                File::makeDirectory($path, 0777, true, true);
            }

            //commented on 11-march-25
            // $getDataBaseName = $this->website->get();
            // $getDataBaseName = $this->website
            //                        ->where('ordination_id',Config('ordination_id'))
            //                        ->first();

            //added on 11-march-25
            // Hyn Tenancy (commented out)
            // $getDataBaseName = 
            
            // Stancl Tenancy: Get tenant database name
            $getDataBaseName = DB::connection('system')->table("tenants")
                            ->where('ordination_id',Config('ordination_id'))->first(['uuid']);


            $fileStorePath = 'public/tenancy/tenants/'.$getDataBaseName->uuid.'/'.$path;

              // Log::info($fileStorePath);
     
            $fileStorePath = Storage::putFileAs($fileStorePath, $file, $fileName);
            // Log::info($fileStorePath);
        }
        else
        {
              // Log::info('in 222');
            $path = 'public/'.$path;
            $fileStorePath = Storage::putFileAs($path, $file, $fileName);
        }

         // Log::info($path);
        // $path = 'public/'.$path;
        //     $fileStorePath = Storage::putFileAs($path, $file, $fileName);
        //dd($fileStorePath);
        return $fileStorePath;
    }//added on 11-march-25
}
