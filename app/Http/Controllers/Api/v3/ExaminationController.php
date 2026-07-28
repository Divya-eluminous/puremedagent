<?php

namespace App\Http\Controllers\Api\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ExaminationsModel;
use App\Models\ProfilesTemplatesModel;
use App\Models\ActivityLogModel;
use App\Models\ProfileHasExaminationsModel;
use App\Models\AppointmentHasExaminationsModel;
use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\CheckListModel;
use App\Models\CheckListHasHeadingSectionModel;
use App\Models\HeadingSectionHasQuestionModel;
use App\Models\AppointmentModel;
use App\Models\CheckListHasSelectedQuestionModel;
use App\Models\PatientHasGeneralDocumentsModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\PatientsModel;
use App\Models\AppointmentTypesModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use Validator;
use Illuminate\Contracts\Filesystem\Filesystem;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\EventTypeHasExaminationsModel;
use App\Models\AppointmentTypeHasNonExaminationsModel;

use Hash;
use DB;
use Auth;
use Storage;
use PDF;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\Log; 

class ExaminationController extends BaseController
{
    use GeneralTrait;
    public function __construct(
        ExaminationsModel $ExaminationsModel,
        ActivityLogModel $ActivityLogModel,
        ProfilesTemplatesModel $ProfilesTemplatesModel,
        AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
        AppointmentModel $AppointmentModel,
        CheckListModel $CheckListModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
        ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
        CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
        HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
        CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
        PatientHasGeneralDocumentsModel $PatientHasGeneralDocumentsModel,
        PatientsModel $PatientsModel,
        AppointmentTypesModel $AppointmentTypesModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
        EventTypeHasExaminationsModel $EventTypeHasExaminationsModel,
        AppointmentTypeHasNonExaminationsModel $AppointmentTypeHasNonExaminationsModel
    )
    {
        $this->BaseModel  = $ExaminationsModel;
        $this->ActivityLogModel     = $ActivityLogModel;
        $this->ProfilesTemplatesModel   = $ProfilesTemplatesModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
        $this->AppointmentModel = $AppointmentModel;
        $this->CheckListModel   = $CheckListModel;
        $this->ExaminationsHasMultipleCheckListModel = $ExaminationsHasMultipleCheckListModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->CheckListHasHeadingSectionModel       = $CheckListHasHeadingSectionModel;
        $this->HeadingSectionHasQuestionModel        = $HeadingSectionHasQuestionModel;
        $this->CheckListHasSelectedQuestionModel     = $CheckListHasSelectedQuestionModel;
        $this->PatientHasGeneralDocumentsModel       = $PatientHasGeneralDocumentsModel;
        $this->PatientsModel = $PatientsModel;
        $this->AppointmentTypesModel = $AppointmentTypesModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->EventTypeHasExaminationsModel = $EventTypeHasExaminationsModel;
        $this->AppointmentTypeHasNonExaminationsModel = $AppointmentTypeHasNonExaminationsModel;

        // $this->ViewData = [];
        // $this->JsonData = [];

        // $this->ModuleTitle = 'Patients';
        // $this->ModuleView  = 'admin.patients.';
        // $this->ModulePath = 'admin.patients.';
    }

    /*---------------------------------
    |   Examination Listing
    ------------------------------------------*/
    public function getExaminations(){
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;

        try{

            $collections = $this->BaseModel
                                ->whereStatus(1)->orderBy('sorting_order','asc')
                                ->get();

             if(!empty($collections)){

                   $collections = $collections->map(function($item)
                    {
                        $item->description =strip_tags(($item->description));
                        $item->url = stripslashes($item->url);
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

                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collections;
                    self::_createLog('getExaminations',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('Get Examinations','get examinations','Get',null,$data);
                }else{
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                    self::_createLog('getExaminations',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
                }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getPatientOrTriggerExaminations(Request $request)
    {

        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;
        $today_date =  date('Y-m-d H:i:s', strtotime(now()));
        $patient_id  = $request->patient_id;
        //$patientAge  = $request->patient_age;
        $appointment_id  = $request->appointment_id;

        $inputdata  = $request->all();
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              // 'patient_age'      => 'required',
                            ],
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                              // 'patient_age.required'    => __('api.AUTH_PATIENT_AGE_REQ'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }else
            {
                $collection = collect([]);

                // $patientProfile = $this->ProfilesTemplatesModel
                //                         ->where('age_from', '<=' ,$patientAge)
                //                         ->where('age_to', '>=' ,$patientAge)
                //                         ->whereStatus(1)
                //                         ->first();

                // if(!empty($patientProfile)){

                //     $profileId = $patientProfile->id;

                //     $collections = $this->BaseModel
                //                         ->leftjoin('profile_has_examinations','profile_has_examinations.examination_id','=','examinations.id')
                //                         ->where('profile_id','=',$profileId)
                //                         ->orWhere('examinations.trigger_exam_flag','=',1)
                //                         ->groupBy('examinations.id')
                //                         ->get([
                //                                 'examinations.id',
                //                                 'examinations.name',
                //                                 'examinations.url',
                //                                 'examinations.description',
                //                                 'examinations.document_name',
                //                                 'examinations.document_path',
                //                                 'examinations.document_status',
                //                                 'examinations.status',
                //                                 ]);


                // }else{
                //     $collections = $this->BaseModel
                //                         ->where('examinations.trigger_exam_flag','=',1)
                //                         ->get([
                //                                 'examinations.id',
                //                                 'examinations.name',
                //                                 'examinations.url',
                //                                 'examinations.description',
                //                                 'examinations.document_name',
                //                                 'examinations.document_path',
                //                                 'examinations.document_status',
                //                                 'examinations.status',
                //                                 ]);
                // }
                //dd($appointment_id);
                $getAppointmentRec = $this->AppointmentModel->find($appointment_id);

                // $collections = $this->AppointmentTypeHasExaminationsModel
                //                 ->where('appoinment_id',$getAppointmentRec->appointment_type_id)
                //                 ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                //                 ->get([
                //                     'examinations.id',
                //                     'examinations.name',
                //                     'examinations.url',
                //                     'examinations.description',
                //                     'examinations.document_name',
                //                     'examinations.document_path',
                //                     'examinations.document_status',
                //                     'examinations.status',
                //                     'examinations.created_at'
                //                 ]);
                //dd($getAppointmentRec);
                if(!empty($getAppointmentRec))
                {
                    $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$getAppointmentRec->appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                ->whereNotNull('examinations.description')
                                ->where('examinations.show_as_recommended','1')
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
                                    ->join(DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder
                                            WHERE patient_id='".$request->patient_id."' and reminder_status IN('ignore','Set')
                                            and status='activate'
                                            and deleted_at is NULL GROUP BY service_id)
                                        patientremidners"),
                                        function($join)
                                        {
                                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                                        })
                                    //->whereNotNull('examinations.description')
                                    ->where('patient_has_service_reminder.patient_id',$request->patient_id)
                                    // ->where('patient_has_service_reminder.type','age')
                                    ->where('patient_has_service_reminder.status','activate')
                                    ->whereNotIn('examinations.id',$exams_ids)
                                    ->where('examinations.show_as_recommended','1')
                                    ->whereRaw("date(reminder_date) <= '".$today_date."'")
                                    // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                                    //             select service_id from patient_has_service_reminder
                                    //             where `patient_has_service_reminder`.`patient_id`=".$request->patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)"))
                                    // ->where('patient_has_service_reminder.reminder_status','Set')
                                    ->whereRaw("examinations.show_as_reminder='1'")
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
                    $collections = $collections1->merge($collections2);

                    $appointment_type_details = [];
                    if(!empty($appointment_id)){
                        $appointment_type_details = $this->AppointmentModel
                                                    ->join('appointment_types','appointment_types.id','=','appointment.appointment_type_id')
                                                    ->where('appointment.id','=',$appointment_id)
                                                    // ->where('appointment_types.recommend_exams','=','0')
                                                    ->get([
                                                        'appointment_types.id',
                                                        'appointment_types.name',
                                                        'appointment_types.recommend_exams'
                                                        ]);
                    }

                    if(!empty($collections) && ($collections->count() > 0))
                    {
                        $collections = $collections->map(function($item)
                        {
                            $doc_path = '';
                            // if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
                            // {
                            //     $doc_path = url('/storage'.$item->document_path);
                            // }
                            $new_doc_path = self::StorePath($item->document_path.'/');

                            if (!empty($item->document_path))
                            {
                                $doc_path = self::getFilePath($item->document_path);
                                //$doc_path = url('/storage'.$item->document_path);
                            }
                            $item->document_path = $doc_path;

                            if (empty($item->description)){
                                 $item->description = '';
                            }
                            if (empty($item->document_name)){
                                 $item->document_name = '';
                            }

                            return $item;
                        });

                        if(!empty($collections) && ($collections->count() > 0))
                        {
                            foreach ($collections as $key => $value)
                            {
                                $isExist = $this->EventTypeHasExaminationsModel
                                        ->where('patient_id',$request->patient_id)
                                        ->where('appoinment_id',$appointment_id)
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
                        self::_createLog('getProfileExaminations',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                    }
                    else{

                        $message = __('api.ERR_SOMETHING_WRONG');
                    }
                }
                else{
                    $status     = false;
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }


    public function getRecommendedServiceReminder(Request $request)
    {
        Log::info("in api getRecommendedServiceReminder app home screen");
        Log::info($request->all());

        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;
        $patient_id  = $request->patient_id;
        $inputdata  = $request->all();


        try
        {
            $collections = collect([]);
            // $collections = $this->PatientsHasServiceReminderModel
            //                 ->where('patient_id',$patient_id)
            //                 ->where('patient_has_service_reminder.status','activate')
            //                 ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
            //                 ->groupBy('patient_has_service_reminder.service_id')
            //                 ->whereNull('examinations.deleted_at')
            //                 ->whereNotNull('examinations.description')
            //                 ->get([
            //                     'examinations.id',
            //                     'examinations.name',
            //                     'examinations.url',
            //                     'examinations.description',
            //                     'examinations.document_name',
            //                     'examinations.document_path',
            //                     'examinations.document_status',
            //                     'examinations.status',
            //                     'examinations.created_at'
            //                 ]);
            $today_date=date("Y-m-d");



            $collections = $this->PatientsHasServiceReminderModel
                            ->select(DB::raw('examinations.id,examinations.name,examinations.description,examinations.document_name,examinations.document_path,examinations.document_status,examinations.status,examinations.created_at,reminder_status'))
                            ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                            ->join(

                                //commented on 7-apr-25
                                /*DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder
                                            WHERE patient_id='".$request->patient_id."' and reminder_status IN('ignore','Set')
                                            and status='activate'
                                            and deleted_at is NULL GROUP BY service_id)
                                        patientremidners"),*/


                                    //start added on 7-apr-25   
                                    //cycle>=1 and app id 0 not 0 conditions added on 23-jan-26  
                                   DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder
                                            WHERE patient_id='".$request->patient_id."' and reminder_status IN('ignore','Set')
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

                                     //end added on 7-apr-25

                                        function($join)
                                        {
                                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                                        })
                            ->where('patient_has_service_reminder.patient_id',$request->patient_id)
                            ->where('patient_has_service_reminder.status','activate')
                            //->whereNotIn('examinations.id',$new_exams_ids)
                            // ->whereRaw("date(reminder_date) <= '".$today_date."'")

                            //->whereRaw("date(reminder_date) <= '".$today_date."'")//commented on 7-apr-25


                            // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                            //                     select service_id from patient_has_service_reminder
                            //                     where `patient_has_service_reminder`.`patient_id`=".$request->patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)"))
                            // ->where('patient_has_service_reminder.reminder_status','Set')
                            ->whereRaw("examinations.show_as_reminder='1'")
                            ->whereNull('examinations.deleted_at')
                            ->whereNotNull('examinations.description')
                            ->groupBy('patient_has_service_reminder.service_id')
                            ->get();

                Log::info("in api getRecommendedServiceReminder app home screen collections");
                Log::info($collections);             

               /* $collections1 = $this->PatientsHasServiceReminderModel
                            ->select(DB::raw('examinations.id,examinations.name,examinations.description,examinations.document_name,examinations.document_path,examinations.document_status,examinations.status,examinations.created_at,reminder_status'))
                            ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                            ->join(DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder
                                            WHERE patient_id='".$request->patient_id."' and reminder_status IN('ignore','Set')
                                            and status='activate'
                                            and deleted_at is NULL GROUP BY service_id)
                                        patientremidners"),
                                        function($join)
                                        {
                                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                                        })
                            ->where('patient_has_service_reminder.patient_id',$request->patient_id)
                            ->where('patient_has_service_reminder.status','activate')
                            ->whereRaw("date(reminder_date) <= '".$today_date."'")
                            ->whereRaw("examinations.show_as_reminder='1'")
                            ->whereNull('examinations.deleted_at')
                            ->whereNotNull('examinations.description')
                            ->groupBy('patient_has_service_reminder.service_id')
                            ->toSql();
                            // ->get();  */




              // log::info($collections1);

            // Filter add for age base services... Added by Shyam 19-01-22
            $collections = $collections->filter(function($item) use ($patient_id,$today_date)
            {
                $age_service =  $this->ChannelsRemindersSettingModel
                                    ->where('service_id',$item->id)
                                    ->where('activated_reminder','age')
                                    ->first();
                if(!empty($age_service))
                {
                    $getPatientAge = $this->PatientsModel->find($patient_id);
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
                // else {
                //     //return $item;
                // }
            });
            //dd($collections);

            Log::info("in api getRecommendedServiceReminder app home screen collections again");
            Log::info($collections);             



            $newCollection = collect([]);

            //// Added by Shyam 19-01-22
            if(!empty($collections) && ($collections->count() == 1))
            {
                foreach ($collections as $key => $value) {
                    $newCollection[] = $value;
                }
            }
            else {
                //$newCollection = $collections;
                foreach ($collections as $key => $value) {
                    $newCollection[] = $value;
                }
            }

            Log::info("in api getRecommendedServiceReminder app home screen newCollection");
            Log::info($newCollection);             


            if(!empty($newCollection) && ($newCollection->count() > 0))
            {
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                $data['exams']  = $newCollection;
                $status  = true;
                $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                self::_createLog('getProfileExaminations',array($data),'info');
            }
            else {
                $message = __('api.ERR_SOMETHING_WRONG');
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getRecommendedServiceReminder_old(Request $request)
    {
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $patient_id  = $request->patient_id;
        $inputdata  = $request->all();
        try{
                $collections = collect([]);
                $collections = $this->PatientsHasServiceReminderModel
                                ->where('patient_id',$patient_id)
                                ->where('patient_has_service_reminder.status','activate')
                                ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                                ->groupBy('patient_has_service_reminder.service_id')
                                ->whereNull('examinations.deleted_at')
                                ->whereNotNull('examinations.description')
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
                // Filter add for age base services... Added by Shyam 19-01-22
                $collections = $collections->filter(function($item) use ($patient_id)
                {
                    $age_service =  $this->ChannelsRemindersSettingModel
                                        ->where('service_id',$item->id)
                                        ->where('activated_reminder','age')
                                        ->first();
                    if(!empty($age_service))
                    {
                        $getPatientAge = $this->PatientsModel->find($patient_id);
                        if(!empty($getPatientAge))
                        {
                            $patient_age = $getPatientAge->age;
                            if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                            {
                                return $item;
                            }
                        }
                    }
                    else {
                        return $item;
                    }
                });
                //// Added by Shyam 19-01-22
                if(!empty($collections) && ($collections->count() > 0))
                {
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data['exams']  = $collections;
                    $status  = true;
                    $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                    self::_createLog('getProfileExaminations',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
                else {
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getPatientOrTriggerExaminationsQRCode(Request $request)
    {
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $patient_id  = $request->patient_id;
        // $patientAge  = $request->patient_age;
        $appointment_id  = $request->appointment_id;


        $inputdata  = $request->all();
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              // 'patient_age'      => 'required',
                            ],
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                              // 'patient_age.required'    => __('api.AUTH_PATIENT_AGE_REQ'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }else
            {
                $collection = collect([]);
                $getAppointmentRec = $this->AppointmentModel->find($appointment_id);
                // $collections = $this->AppointmentTypeHasExaminationsModel
                                  //->where('appoinment_id',$getAppointmentRec->appointment_type_id)
                if(!empty($getAppointmentRec)){
                    $all_exam_ids = $this->AppointmentHasExaminationsModel
                                    ->select('examination_id')
                                    ->where('appointment_id', $appointment_id)
                                    ->get();

                    $all_exams_ids  = array_unique(array_column(array_values($all_exam_ids->toArray()), 'examination_id'));
                    $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$getAppointmentRec->appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                ->whereNotIn('examinations.id',$all_exams_ids)
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
                                //dd($collections);
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
                    $new_exams_ids =   array_merge($exams_ids, $all_exams_ids);

                    $collections2 = $this->PatientsHasServiceReminderModel
                                    ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                                    ->where('patient_has_service_reminder.patient_id',$request->patient_id)
                                    ->where('patient_has_service_reminder.type','age')
                                    ->where('patient_has_service_reminder.status','activate')
                                    ->whereNotIn('examinations.id',$new_exams_ids)
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
                                        ]);
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
                    $collections = $collections2->merge($collections1);
                    //sort($collections);

                    $appointment_type_details = [];
                    if(!empty($appointment_id)){
                        $appointment_type_details = $this->AppointmentModel
                                                    ->join('appointment_types','appointment_types.id','=','appointment.appointment_type_id')
                                                    ->where('appointment.id','=',$appointment_id)
                                                     ->where('appointment_types.recommend_exams','=','0')
                                                    ->get([
                                                        'appointment_types.id',
                                                        'appointment_types.name',
                                                        'appointment_types.recommend_exams',
                                                        'appointment_types.created_at'
                                                        ]);
                    }

                    $all_exam_ids = $this->AppointmentHasExaminationsModel
                                    ->select('examination_id')
                                    ->where('appointment_id', $appointment_id)
                                    ->get();
                    //dd($all_exam_ids);
                    $all_exam_ids = $all_exam_ids->map(function($key)
                    {
                        return $key->examination_id;
                    });

                    if(!empty($collections) && ($collections->count() > 0))
                    {
                        $collections = $collections->map(function($item)
                        {
                            $doc_path = '';
                            // if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
                            // {
                            //     $doc_path = url('/storage'.$item->document_path);
                            // }
                            $new_doc_path = self::StorePath($item->document_path.'/');

                            if (!empty($item->document_path))
                            {
                                $doc_path = self::getFilePath($item->document_path);
                                //$doc_path = url('/storage'.$item->document_path);
                            }
                            $item->document_path = $doc_path;

                            if (empty($item->description)){
                                 $item->description = '';
                            }
                            if (empty($item->document_name)){
                                 $item->document_name = '';
                            }
                            return $item;
                        });

                        /*
                        |GET CHECKLIST
                        */
                        $cnt = 0;
                        $arr_checklist = [];

                        foreach ($collections as $key => $value)
                        {
                            // Event Type Services
                            $isExist = $this->EventTypeHasExaminationsModel
                                        ->where('patient_id',$patient_id)
                                        ->where('appoinment_id',$appointment_id)
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
                            $eventType->appoinment_id = $appointment_id;
                            $eventType->service_id    = $value['id'];
                            $eventType->event_type    = 'tablet';
                            $eventType->status        = 'displayed';
                            $eventType->save();

                            // End
                            $checkListCollection = $this->getExaminationsCheckListQRCode($value['id'],$appointment_id,$patient_id);
                            if(!empty($checkListCollection) && count($checkListCollection)>0)
                            {
                                $checkListCollection  = $checkListCollection->map(function($index) use($appointment_id,$patient_id)
                                {
                                    $chkcollections = $this->CheckListHasSelectedQuestionModel
                                            ->where('fk_patient_id',$patient_id)
                                            ->where('fk_appointment_id',$appointment_id)
                                            ->where('fk_examination_id',$index->fk_examinations_id)
                                            ->first();

                                    if(!empty($chkcollections))
                                    {
                                        $selected_array= json_decode($chkcollections['questions'],true);

                                        $cehcklist_ids = [];
                                        $question_id = [];
                                        if(!empty($selected_array))
                                        {
                                        foreach($selected_array as $key=>$value)
                                        {
                                            $cehcklist_ids[] = $value['checklist_id'];
                                           // $question_id[] = $value['question_id'];
                                            foreach($value['heading'] as $i_key=>$i_value)
                                            {
                                                foreach($i_value['question'] as $q_key=>$q_value)
                                                {
                                                    if($q_value['question']['flag'] == 1)
                                                    {
                                                        $question_id[] = $q_value['question']['question_id'];
                                                    }
                                                }
                                            }
                                        }
                                    }

                                        $index->exam_flag = 1;

                                        $checklist = $index->getChecklistQR;

                                        $checklist = $checklist->map(function($checklist_index) use($cehcklist_ids,$selected_array,$index,$question_id)
                                        {

                                            if(in_array($checklist_index->id,$cehcklist_ids))
                                            {
                                                $checklist_index->checklist_flag = 1;


                                                $heading_section= $checklist_index->getHEadingSectionQR->map(function($heading_index) use($selected_array,$question_id)
                                                {
                                                     $question_section= $heading_index->getQuestionQR->map(function($question_index) use($selected_array,$question_id)
                                                    {
                                                        if(in_array($question_index->id,$question_id))
                                                        {
                                                            $question_index->question_flag = 1;
                                                        }else
                                                        {
                                                            $question_index->question_flag = 0;
                                                        }
                                                        return $question_index;
                                                    });

                                                    return $heading_index;
                                                });

                                            }
                                            else
                                            {
                                                $checklist_index->checklist_flag = 0;
                                            }
                                            // dd($checklist_index->getHEadingSectionQR);
                                            return $checklist_index;
                                        });
                                    }
                                    else
                                    {
                                        $index->exam_flag = 0;
                                    }
                                    return $index;
                                });
                                $collections[$cnt]['checklist'] = $checkListCollection;
                            }
                            else
                            {
                                $collections[$cnt]['checklist'] = [];
                            }
                            $cnt++;
                        }


                        // foreach ($collections as $key => $value)
                        // {
                        //     // $checkListCollection = $this->getExaminationsCheckListQRCode($value['id'],$appointment_id,$patient_id);

                        //     $chkcollections = $this->CheckListHasSelectedQuestionModel
                        //                       ->where('fk_patient_id',$patient_id)
                        //                       ->where('fk_appointment_id',$appointment_id)
                        //                       ->get();

                            // if(!empty($chkcollections) && sizeof($chkcollections)>0)
                            // {
                            //     foreach ($chkcollections as $key => $chkcollections_val)
                            //     {

                            //         if($chkcollections_val['questions'])
                            //         {
                            //             $collections[$cnt]['with_checked_qus'] = json_decode($chkcollections_val['questions'],true);
                            //         }
                            //         else
                            //         {
                            //             $collections[$cnt]['with_checked_qus'] = [];
                            //         }
                            //     }
                            // }
                            // else
                            // {
                            //     $examinationsDetails = $this->BaseModel->where('id',$value['id'])->first();
                            //     $checkListCollection = $this->ExaminationsHasMultipleCheckListModel
                            //                 ->where('fk_examinations_id',$value['id'])
                            //                 ->with(['getChecklistQR.getHEadingSectionQR.getQuestionQR'])
                            //                 ->get();

                            //     if(!empty($checkListCollection) && count($checkListCollection)>0)
                            //     {
                            //         $collections[$cnt]['checklist'] = $checkListCollection;
                            //     }
                            //     else
                            //     {
                            //         $collections[$cnt]['checklist'] = [];
                            //     }

                            // }
                            // ==============
                            // $cnt++;

                        // }

                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data[0]['exams']  = $collections;
                        $data[0]['appointment_type']  = $appointment_type_details;
                        $data[0]['exam_ids']  = $all_exam_ids;

                        $status  = true;
                        $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                        self::_createLog('getProfileExaminations',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                    }
                    else{
                        $status = false;
                        $message = __('api.ERR_SOMETHING_WRONG');
                    }
                }
                else{
                     $status = false;
                        $message = __('api.ERR_SOMETHING_WRONG');
                    }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getPatientSelectedExaminations_old(Request $request)
    {
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $patient_id  = $request->patient_id;
        //$patientAge  = $request->patient_age;
        $appointment_id  = $request->appointment_id;

        $inputdata  = $request->all();
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              // 'patient_age'      => 'required',
                            ],
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                              // 'patient_age.required'    => __('api.AUTH_PATIENT_AGE_REQ'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }else
            {
                $collection = collect([]);
                $getAppointmentRec = $this->AppointmentModel->find($appointment_id);

                if(!empty($getAppointmentRec))
                {
                    $all_exam_ids = $this->AppointmentHasExaminationsModel
                                    ->select('examination_id')
                                    ->where('appointment_id', $appointment_id)
                                    ->get();

                    $all_exams_ids  = array_unique(array_column(array_values($all_exam_ids->toArray()), 'examination_id'));
                    //dump($all_exams_ids);
                    $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$getAppointmentRec->appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                ->whereNotIn('examinations.id',$all_exams_ids)
                                ->whereNotNull('examinations.description')
                                ->where('examinations.show_as_recommended','1')
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
                                //dd($collections1);
                    $exams_ids    = array_unique(array_column(array_values($collections1->toArray()), 'id'));
                    $new_exams_ids =   array_merge($exams_ids, $all_exams_ids);
                    //dd($new_exams_ids);
                    //DB::enableQueryLog();
                    $collections2 = $this->PatientsHasServiceReminderModel
                                    ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                                    //->whereNotNull('examinations.description')
                                    ->where('patient_has_service_reminder.patient_id',$request->patient_id)
                                    ->where('patient_has_service_reminder.type','age')
                                    ->where('patient_has_service_reminder.status','activate')
                                    // ->whereNotIn('examinations.id',$exams_ids)
                                    ->whereNotIn('examinations.id',$new_exams_ids)
                                    ->where('examinations.show_as_recommended','1')
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
                                            'examinations.created_at'
                                        ]);
                    //print_r(DB::getQueryLog()); exit;
                    //echo "<pre>";print_r($collections2);exit;
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
                    $collections = $collections1->merge($collections2);

                    $appointment_type_details = [];
                    if(!empty($appointment_id)){
                        $appointment_type_details = $this->AppointmentModel
                                                    ->join('appointment_types','appointment_types.id','=','appointment.appointment_type_id')
                                                    ->where('appointment.id','=',$appointment_id)
                                                    // ->where('appointment_types.recommend_exams','=','0')
                                                    ->get([
                                                        'appointment_types.id',
                                                        'appointment_types.name',
                                                        'appointment_types.recommend_exams'
                                                        ]);
                    }

                    if(!empty($collections) && ($collections->count() > 0))
                    {
                        $collections = $collections->map(function($item)
                        {
                            $doc_path = '';
                            // if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
                            // {
                            //     $doc_path = url('/storage'.$item->document_path);
                            // }
                            $new_doc_path = self::StorePath($item->document_path.'/');

                            if (!empty($item->document_path))
                            {
                                $doc_path = self::getFilePath($item->document_path);
                                //$doc_path = url('/storage'.$item->document_path);
                            }
                            $item->document_path = $doc_path;

                            if (empty($item->description)){
                                 $item->description = '';
                            }
                            if (empty($item->document_name)){
                                 $item->document_name = '';
                            }

                            return $item;
                        });

                        if(!empty($collections) && ($collections->count() > 0))
                        {
                            foreach ($collections as $key => $value)
                            {
                                $isExist = $this->EventTypeHasExaminationsModel
                                        ->where('patient_id',$request->patient_id)
                                        ->where('appoinment_id',$appointment_id)
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
                        self::_createLog('getProfileExaminations',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                    }
                    else{

                        $message = __('api.ERR_SOMETHING_WRONG');
                    }
                }
                else{
                    $status     = false;
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }//

    public function getPatientSelectedExaminations_origialrenamedon_10nov23(Request $request)
    {


        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;
        $patient_id  = $request->patient_id;
        //$patientAge  = $request->patient_age;
        $appointment_id  = $request->appointment_id;
        $inputdata  = $request->all();
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              // 'patient_age'      => 'required',
                            ],
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                              // 'patient_age.required'    => __('api.AUTH_PATIENT_AGE_REQ'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }else
            {
                $collection = collect([]);
                $getAppointmentRec = $this->AppointmentModel->find($appointment_id);
                $appointmentTypeID=0;
                if(!empty($getAppointmentRec))
                {
                    $appointmentTypeID=$getAppointmentRec->appointment_type_id;
                    // $all_exam_ids = $this->AppointmentHasExaminationsModel
                    //                 ->select('examination_id')
                    //                 ->where('appointment_id', $appointment_id)
                    //                 ->get();

                    // $all_exams_ids  = array_unique(array_column(array_values($all_exam_ids->toArray()), 'examination_id'));
                    //dump($all_exams_ids);
                    $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$getAppointmentRec->appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                //->whereNotIn('examinations.id',$all_exams_ids)
                                ->whereNotNull('examinations.description')
                                ->where('examinations.show_as_recommended','1')
                                // ->whereRaw("examinations.show_as_reminder='1'")
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
                                //dd($collections1);
                    $exams_ids    = array_unique(array_column(array_values($collections1->toArray()), 'id'));
                    $new_exams_ids = $exams_ids;


                    //$new_exams_ids =   array_merge($exams_ids, $all_exams_ids);
                    //dd($new_exams_ids);
                    //DB::enableQueryLog();
                    $today_date=date("Y-m-d");
                    $collections2 = $this->PatientsHasServiceReminderModel
                                    ->select(DB::raw('examinations.id,examinations.name,examinations.description,examinations.document_name,examinations.document_path,examinations.document_status,examinations.url,examinations.status,examinations.created_at,max(patient_has_service_reminder.reminder_date),reminder_status'))
                                    ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                                    ->join(DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder
                                            WHERE patient_id='".$request->patient_id."' and reminder_status IN('ignore','Set')
                                            and status='activate'
                                            and deleted_at is NULL GROUP BY service_id)
                                        patientremidners"),
                                        function($join)
                                        {
                                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                                        })
                                    // ->whereNotNull('examinations.description')
                                    ->where('patient_has_service_reminder.patient_id',$request->patient_id)
                                    // ->where('patient_has_service_reminder.type','age')
                                    ->where('patient_has_service_reminder.status','activate')
                                    // ->whereNotIn('examinations.id',$exams_ids)
                                    ->whereNotIn('examinations.id',$new_exams_ids)
                                    ->where('examinations.show_as_recommended','1')
                                    ->whereRaw("date(reminder_date) <= '".$today_date."'")
                                    // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                                    //             select service_id from patient_has_service_reminder
                                    //             where `patient_has_service_reminder`.`patient_id`=".$patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)"))
                                    // ->whereRaw("examinations.show_as_reminder='1'")
                                    //->where('patient_has_service_reminder.reminder_status','Set')
                                    ->groupBy('patient_has_service_reminder.service_id')
                                    ->get();
                    // print_r(DB::getQueryLog()); exit;
                    // echo "<pre>";print_r($collections2);exit;




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
                    });
                    $collections = $collections1->merge($collections2);



                    $appointment_type_details = [];
                    if(!empty($appointment_id)){
                        $appointment_type_details = $this->AppointmentModel
                                                    ->join('appointment_types','appointment_types.id','=','appointment.appointment_type_id')
                                                    ->where('appointment.id','=',$appointment_id)
                                                    // ->where('appointment_types.recommend_exams','=','0')
                                                    ->get([
                                                        'appointment_types.id',
                                                        'appointment_types.name',
                                                        'appointment_types.recommend_exams'
                                                        ]);
                    }

                    if(!empty($collections) && ($collections->count() > 0))
                    {
                        $collections = $collections->map(function($item)
                        {
                            $doc_path = '';
                            // if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
                            // {
                            //     $doc_path = url('/storage'.$item->document_path);
                            // }
                            $new_doc_path = self::StorePath($item->document_path.'/');

                            if (!empty($item->document_path))
                            {
                                $doc_path = self::getFilePath($item->document_path);
                                //$doc_path = url('/storage'.$item->document_path);
                            }
                            $item->document_path = $doc_path;

                            if (empty($item->description)){
                                 $item->description = '';
                            }
                            if (empty($item->document_name)){
                                 $item->document_name = '';
                            }

                            return $item;
                        });

                        $newCollection=array();
                        if(!empty($collections) && ($collections->count() > 0))
                        {
                            foreach ($collections as $key => $value)
                            {
                                $isExist = $this->EventTypeHasExaminationsModel
                                        ->where('patient_id',$request->patient_id)
                                        ->where('appoinment_id',$appointment_id)
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

                                $eventType->patient_id    = $request->patient_id;
                                $eventType->appoinment_id = $appointment_id;
                                $eventType->service_id    = $value['id'];
                                $eventType->event_type    = 'smart_phone';
                                $eventType->status        = 'displayed';
                                $eventType->save();

                                $app_type_name = $this->AppointmentTypesModel->find($getAppointmentRec->appointment_type_id);
                                if(!empty($app_type_name))
                                {
                                    if(ucfirst($value['name']) == ucfirst($app_type_name->name)){
                                        $collections->forget($key);
                                    }
                                }
                            }
                        }
                        $status  = false;
                        if($collections->values()->toArray()) $status=true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data[0]['exams']  = $collections->values();
                        $data[0]['appointment_type']  = $appointment_type_details;
                        $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                        self::_createLog('getProfileExaminations',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                    }
                    else{
                        $message = __('api.ERR_SOMETHING_WRONG');
                    }
                }
                else{
                    $status     = false;
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }//

    //Below function added on 10-nov-23 for vertung shown if already booked
    public function getPatientSelectedExaminations(Request $request)
    {
        Log::info("in api getPatientSelectedExaminations");
        Log::info($request->all());

        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;
        $patient_id  = $request->patient_id;
        //$patientAge  = $request->patient_age;
        $appointment_id  = $request->appointment_id;
        $from_edit = $request->from_edit; //added on 18-march-26

        $inputdata  = $request->all();
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              // 'patient_age'      => 'required',
                            ],
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                              // 'patient_age.required'    => __('api.AUTH_PATIENT_AGE_REQ'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }else
            {
                $collection = collect([]);
                $getAppointmentRec = $this->AppointmentModel->find($appointment_id);
                $appointmentTypeID=0;
                if(!empty($getAppointmentRec))
                {
                    $appointmentTypeID=$getAppointmentRec->appointment_type_id;

                     //start uncommented below code on 10-bov-23
                     $all_exam_ids = $this->AppointmentHasExaminationsModel
                                    ->select('examination_id')
                                    ->where('appointment_id', $appointment_id)
                                    ->get();
                     $all_exams_ids  = array_unique(array_column(array_values($all_exam_ids->toArray()), 'examination_id'));
                     //end uncommented below code on 10-bov-23

                    Log::info("in api getPatientSelectedExaminations all_exam_ids");
                    Log::info($all_exam_ids);

                    //commented on 18-march-26
                    /*$collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$getAppointmentRec->appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                //->whereNotIn('examinations.id',$all_exams_ids) //uncommented code on 10-bov-23
                                ->whereNotNull('examinations.description')
                                ->where('examinations.show_as_recommended','1')
                                ->whereNull('examinations.deleted_at')
                                // ->whereRaw("examinations.show_as_reminder='1'")
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
                                ]);*/

                    //Did changes for from edit flag on 18-march-26            
                    $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$getAppointmentRec->appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                 ->when(!empty($from_edit) && !empty($all_exams_ids), function ($query) use ($all_exams_ids) {
                                        $query->whereNotIn('examinations.id', $all_exams_ids);
                                })
                                ->whereNotNull('examinations.description')
                                ->where('examinations.show_as_recommended','1')
                                ->whereNull('examinations.deleted_at')
                                // ->whereRaw("examinations.show_as_reminder='1'")
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


                    Log::info("in api getPatientSelectedExaminations all_exam_ids");
                    Log::info($all_exam_ids);            

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


                    Log::info("in api getPatientSelectedExaminations collections1");
                    Log::info($collections1);  

                                //dd($collections1);
                    $exams_ids    = array_unique(array_column(array_values($collections1->toArray()), 'id'));
                    $new_exams_ids = $exams_ids; //commented this line on 11-nov-23

                    Log::info("in api getPatientSelectedExaminations new_exams_ids");
                    Log::info($new_exams_ids);  

                   // $new_exams_ids =   array_merge($exams_ids, $all_exams_ids); //uncommented this line on 11-nov-23


                    //$new_exams_ids =   array_merge($exams_ids, $all_exams_ids);
                    //dd($new_exams_ids);
                    //DB::enableQueryLog();
                    $today_date=date("Y-m-d");
                    $collections2 = $this->PatientsHasServiceReminderModel
                                    ->select(DB::raw('examinations.id,examinations.name,examinations.description,examinations.document_name,examinations.document_path,examinations.document_status,examinations.url,examinations.status,examinations.created_at,max(patient_has_service_reminder.reminder_date),reminder_status'))
                                    ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                                    ->join(

                                        /* commented on 7-apr-25
                                        DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder
                                            WHERE patient_id='".$request->patient_id."' and reminder_status IN('ignore','Set')
                                            and status='activate'
                                            and deleted_at is NULL GROUP BY service_id)
                                        patientremidners"),*/

                                         //start added on 7-apr-25
                                          DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder 
                                           WHERE patient_id='".$request->patient_id."' and reminder_status IN('ignore','Set') 
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
                                        //end added on 7-apr-25    


                                        function($join)
                                        {
                                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                                        })
                                    // ->whereNotNull('examinations.description')
                                    ->where('patient_has_service_reminder.patient_id',$request->patient_id)
                                    // ->where('patient_has_service_reminder.type','age')
                                    ->where('patient_has_service_reminder.status','activate')
                                    // ->whereNotIn('examinations.id',$exams_ids)
                                    ->whereNotIn('examinations.id',$new_exams_ids)
                                    ->where('examinations.show_as_recommended','1')
                                    ->whereNull('examinations.deleted_at')
                                    //->whereRaw("date(reminder_date) <= '".$today_date."'") //commented on 7-apr-25 




                                    // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                                    //             select service_id from patient_has_service_reminder
                                    //             where `patient_has_service_reminder`.`patient_id`=".$patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)"))
                                    // ->whereRaw("examinations.show_as_reminder='1'")
                                    //->where('patient_has_service_reminder.reminder_status','Set')
                                    ->groupBy('patient_has_service_reminder.service_id')
                                    ->get();
                    // print_r(DB::getQueryLog()); exit;
                    // echo "<pre>";print_r($collections2);exit;

                    Log::info("in api getPatientSelectedExaminations collections2");
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
                        // else
                        // {
                        //     return $item;
                        // }
                    });


                    Log::info("in api getPatientSelectedExaminations collections2 after filter");
                    Log::info($collections2); 

                   // $collections = $collections1->merge($collections2); //commented on 7-may-24

                   //added below code on 7-may-24
                    if(!empty($collections2) && ($collections2->count() > 0))
                    {
                      $collections = $collections1->merge($collections2);
                    }else{
                         $collections = $collections1;
                    }

                    Log::info("in api getPatientSelectedExaminations collections after merge both");
                    Log::info($collections); 

                    $appointment_type_details = [];
                    if(!empty($appointment_id)){
                        $appointment_type_details = $this->AppointmentModel
                                                    ->join('appointment_types','appointment_types.id','=','appointment.appointment_type_id')
                                                    ->where('appointment.id','=',$appointment_id)
                                                    // ->where('appointment_types.recommend_exams','=','0')
                                                    ->get([
                                                        'appointment_types.id',
                                                        'appointment_types.name',
                                                        'appointment_types.recommend_exams'
                                                        ]);
                    }

                    if(!empty($collections) && ($collections->count() > 0))
                    {
                        $collections = $collections->map(function($item)
                        {
                            $doc_path = '';
                            // if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
                            // {
                            //     $doc_path = url('/storage'.$item->document_path);
                            // }
                            $new_doc_path = self::StorePath($item->document_path.'/');

                            if (!empty($item->document_path))
                            {
                                $doc_path = self::getFilePath($item->document_path);
                                //$doc_path = url('/storage'.$item->document_path);
                            }
                            $item->document_path = $doc_path;

                            if (empty($item->description)){
                                 $item->description = '';
                            }
                            if (empty($item->document_name)){
                                 $item->document_name = '';
                            }

                            return $item;
                        });

                        $newCollection=array();
                        if(!empty($collections) && ($collections->count() > 0))
                        {
                            foreach ($collections as $key => $value)
                            {
                                $isExist = $this->EventTypeHasExaminationsModel
                                        ->where('patient_id',$request->patient_id)
                                        ->where('appoinment_id',$appointment_id)
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

                                $eventType->patient_id    = $request->patient_id;
                                $eventType->appoinment_id = $appointment_id;
                                $eventType->service_id    = $value['id'];
                                $eventType->event_type    = 'smart_phone';
                                $eventType->status        = 'displayed';
                                $eventType->save();

                                $app_type_name = $this->AppointmentTypesModel->find($getAppointmentRec->appointment_type_id);
                                if(!empty($app_type_name))
                                {
                                    if(ucfirst($value['name']) == ucfirst($app_type_name->name)){
                                        $collections->forget($key);
                                    }
                                }
                            }
                        }
                        $status  = false;
                        if($collections->values()->toArray()) $status=true;
                        // added by vijay 14/3/2024
                        $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($appointmentTypeID);
                        $status = false;
                        if ($collections->values()->toArray())
                            $status = true;
                        $excludedNonServisesCollection = [];
                        foreach ($collections as $key => $value) {
                            if (!in_array($value->id, $getAppointmentNonServciesIds)) {
                                $excludedNonServisesCollection[] = $value;
                            }
                        }
                        // end

                        Log::info("in api getPatientSelectedExaminations excludedNonServisesCollection");
                        Log::info($excludedNonServisesCollection); 



                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data[0]['exams']  = $excludedNonServisesCollection;
                        $data[0]['appointment_type']  = $appointment_type_details;
                        $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                        self::_createLog('getProfileExaminations',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                    }
                    else{

                        //$message = __('api.ERR_SOMETHING_WRONG'); //comented on 7-may-24

                        //start added below code on 7-may-24
                        $message    = __('api.DATA_NOT_FOUND');
                        $data[0]['exams']  = [];
                        $data[0]['appointment_type']  = isset($appointment_type_details)?$appointment_type_details:[];
                        //end added below code on 7-may-24

                    }
                }
                else{
                    $status     = false;
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getPatientOrTriggerExaminationsQRCodetest(Request $request)
    {
        Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data");
        Log::info($request->all());

        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $patient_id  = $request->patient_id;
        // $patientAge  = $request->patient_age;
        $appointment_id  = $request->appointment_id;


        $inputdata  = $request->all();
       // dump($inputdata);
        
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              // 'patient_age'      => 'required',
                            ],
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                              // 'patient_age.required'    => __('api.AUTH_PATIENT_AGE_REQ'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }else
            {
                $collection = collect([]);
                $getAppointmentRec = $this->AppointmentModel->find($appointment_id);
                // added by vijay 14/3/24
                $appointmentTypeID = $getAppointmentRec->appointment_type_id;
                

                //end
                // $collections = $this->AppointmentTypeHasExaminationsModel
                                  //->where('appoinment_id',$getAppointmentRec->appointment_type_id)
                if(!empty($getAppointmentRec)){
                    $all_exam_ids = $this->AppointmentHasExaminationsModel
                                    ->select('examination_id')
                                    ->where('appointment_id', $appointment_id)
                                    ->get();
                    $all_exams_ids  = array_unique(array_column(array_values($all_exam_ids->toArray()), 'examination_id'));

                    Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data all_exams_ids");
                    Log::info($all_exams_ids);

                    if (!empty($appointment_id)) {
                        $getSpecialistId = $this->AppointmentModel
                                            ->with('appointmentType')
                                            ->find($appointment_id)
                                            ->appointmentType
                                            ->fk_specialist_id ?? null;
                    }
                    $collections1 = $this->AppointmentTypeHasExaminationsModel
                                ->where('appoinment_type_has_examinations.appoinment_id',$getAppointmentRec->appointment_type_id)
                                ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                ->where('examinations.fk_specialist_id', $getSpecialistId)
                                //->whereNotIn('examinations.id',$all_exams_ids)
                                //->whereNotNull('examinations.description')
                                //->where('examinations.show_as_recommended','1')
                                // ->whereRaw("examinations.show_as_reminder='1'")
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
                                //dd($collections);


                    Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data collections1");
                    Log::info($collections1);
                                
                    $collections1 = $collections1->filter(function($item) use ($patient_id,$appointment_id)
                    {
                        $selected_Services = $this->AppointmentHasExaminationsModel
                                             ->select('examination_id')
                                             ->where('appointment_id', $appointment_id)
                                             ->where('examination_id', $item->id)
                                             ->first();
                                             // dump($selected_Services);
                                             // dump($item->id);
                        if(!empty($selected_Services))
                        {
                            $item->selected_on = 1;
                        }
                        else
                        {
                            $item->selected_on = 0;
                        }
                        return $item;

                    });

                    Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data collections1 again");
                    Log::info($collections1);

                     /******* Roshani commented the code for isuue 148 ===> peter comment => the checklist needs to be presented - independent from the age. <= *********/
                        // $collections1 = $collections1->filter(function($item) use ($patient_id,$appointment_id)
                        // {
                        //     $age_service =  $this->ChannelsRemindersSettingModel
                        //                         ->where('service_id',$item->id)
                        //                         ->where('activated_reminder','age')
                        //                         ->first();

                        //     if(!empty($age_service))
                        //     {
                        //         $getPatientAge = $this->PatientsModel
                        //                          ->find($patient_id);

                        //         if(!empty($getPatientAge))
                        //         {
                        //             $patient_age = $getPatientAge->age;

                        //             if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                        //             {
                        //                 return $item;
                        //             }
                        //         }
                        //     }
                        //     else
                        //     {
                        //         return $item;
                        //     }

                        // });

                     /******* Roshani commented the code for isuue 148 ===> peter comment => the checklist needs to be presented - independent from the age. <= *********/

                    $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
                    $new_exams_ids =   array_merge($exams_ids, $all_exams_ids);

                    Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data exams_ids");
                    Log::info($exams_ids);

                    Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data new_exams_ids");
                    Log::info($new_exams_ids);

                    $today_date=date("Y-m-d");
                    $collections2 = $this->PatientsHasServiceReminderModel
                                    ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                                    ->join(

                                        /* commented on 8-apr-25
                                        DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder
                                            WHERE patient_id='".$request->patient_id."' and reminder_status IN('ignore','Set')
                                            and status='activate'
                                            and deleted_at is NULL GROUP BY service_id)
                                        patientremidners"),*/

                                        //start added on 8-apr-25
                                        //cycle>=1 and appid 0 not 0 condition added on 23-jan-26
                                         DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                                            FROM patient_has_service_reminder
                                            WHERE patient_id='".$request->patient_id."' and reminder_status IN('ignore','Set')
                                            and status='activate'
                                              AND (
                                                ( (deleted_at IS NULL AND cycle_no = 1 AND date(reminder_date) <= '" . $today_date . "' AND type!='age' ) 
                                                   OR
                                                   (  deleted_at IS NULL and cycle_no>=0 AND date(reminder_date) <= '" . $today_date . "' and type='age' 
                                                   )
                                                )
                                                OR 
                                                ( (deleted_at IS NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' AND type!='age') 
                                                   OR ((deleted_at IS NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' and type='age' and appointment_id!=0) 
                                                      OR (deleted_at IS NULL AND cycle_no >= 1 AND date(reminder_date) >= '" . $today_date . "' and type='age' and appointment_id=0)) 
                                                )
                                            )  GROUP BY service_id)
                                        patientremidners"),
                                        // end added on 8-apr-25

                                        function($join)
                                        {
                                            $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                            $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                                        })
                                    ->where('patient_has_service_reminder.patient_id',$request->patient_id)
                                    //->where('patient_has_service_reminder.type','age')
                                    ->where('patient_has_service_reminder.status','activate')
                                    ->whereNotIn('examinations.id',$new_exams_ids)
                                    ->where('examinations.fk_specialist_id', $getSpecialistId)



                                    //->whereRaw("date(reminder_date) <= '".$today_date."'")//commented on 8-apr-25


                                    // ->whereRaw(DB::raw("patient_has_service_reminder.service_id NOT IN(
                                    //             select service_id from patient_has_service_reminder
                                    //             where `patient_has_service_reminder`.`patient_id`=".$request->patient_id." and `patient_has_service_reminder`.`status`='activate' and date(reminder_date)>'".$today_date."' and `patient_has_service_reminder`.`deleted_at` is null)"))
                                    // ->where('patient_has_service_reminder.reminder_status','Set')
                                    ->whereRaw("examinations.show_as_reminder='1'")
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

                    Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data collections2");
                    Log::info($collections2);                

                    $collections2 = $collections2->filter(function($item) use ($patient_id,$appointment_id)
                    {
                        $selected_Services = $this->AppointmentHasExaminationsModel
                                             ->select('examination_id')
                                             ->where('appointment_id', $appointment_id)
                                             ->where('examination_id', $item->id)
                                             ->first();

                        if(!empty($selected_Services))
                        {
                             $item->selected_on = 1;
                        }
                        else
                        {
                            $item->selected_on = 0;
                        }
                        return $item;

                    });

                    Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data collections2 again");
                    Log::info($collections2); 

                     /******* Roshani commented the code for isuue 148 ===> peter comment => the checklist needs to be presented - independent from the age. <= *********/
                        // $collections2 = $collections2->filter(function($item) use ($patient_id)
                        // {
                        //     $age_service =  $this->ChannelsRemindersSettingModel
                        //                         ->where('service_id',$item->id)
                        //                         ->where('activated_reminder','age')
                        //                         ->first();

                        //     if(!empty($age_service))
                        //     {
                        //         $getPatientAge = $this->PatientsModel
                        //                          ->find($patient_id);

                        //         if(!empty($getPatientAge))
                        //         {
                        //             $patient_age = $getPatientAge->age;

                        //             if($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age)
                        //             {
                        //                 return $item;
                        //             }
                        //         }
                        //     }
                        //     else
                        //     {
                        //         return $item;
                        //     }
                        // });

                     /******* Roshani commented the code for isuue 148 ===> peter comment => the checklist needs to be presented - independent from the age. <= *********/

                    $collections = $collections2->merge($collections1);
                    //sort($collections);

                    $appointment_type_details = [];
                    if(!empty($appointment_id)){
                        $appointment_type_details = $this->AppointmentModel
                                                    ->join('appointment_types','appointment_types.id','=','appointment.appointment_type_id')
                                                    ->where('appointment.id','=',$appointment_id)
                                                     ->where('appointment_types.recommend_exams','=','0')
                                                    ->get([
                                                        'appointment_types.id',
                                                        'appointment_types.name',
                                                        'appointment_types.recommend_exams',
                                                        'appointment_types.created_at'
                                                        ]);
                    }

                    $all_exam_ids = $this->AppointmentHasExaminationsModel
                                    ->select('examination_id')
                                    ->where('appointment_id', $appointment_id)
                                    ->get();
                    //dd($all_exam_ids);
                    $all_exam_ids = $all_exam_ids->map(function($key)
                    {
                        return $key->examination_id;
                    });

                    if(!empty($collections) && ($collections->count() > 0))
                    {
                        $collections = $collections->map(function($item)
                        {
                            $doc_path = '';
                            // if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
                            // {
                            //     $doc_path = url('/storage'.$item->document_path);
                            // }
                            $new_doc_path = self::StorePath($item->document_path.'/');

                            if (!empty($item->document_path))
                            {
                                $doc_path = self::getFilePath($item->document_path);
                                //$doc_path = url('/storage'.$item->document_path);
                            }
                            $item->document_path = $doc_path;

                            if (empty($item->description)){
                                 $item->description = '';
                            }
                            if (empty($item->document_name)){
                                 $item->document_name = '';
                            }
                            return $item;
                        });

                        /*
                        |GET CHECKLIST
                        */
                        $cnt = 0;
                        $arr_checklist = [];
                        foreach ($collections as $key => $value)
                        {
                            // Event Type Services
                            $isExist = $this->EventTypeHasExaminationsModel
                                        ->where('patient_id',$patient_id)
                                        ->where('appoinment_id',$appointment_id)
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
                            $eventType->appoinment_id = $appointment_id;
                            $eventType->service_id    = $value['id'];
                            $eventType->event_type    = 'tablet';
                            $eventType->status        = 'displayed';
                            $eventType->save();

                            // End

                            //commented on 30-sept-24 for #187 reverted on 28-oct-24
                            //commented on 18-nov-24 for 187
                            // $checkListCollection = $this->getExaminationsCheckListQRCode($value['id'],$appointment_id,$patient_id);

                            //added on 30-sept-24 for #187 //commented on 28-oct-24
                            //uncommented on 18-nov-24 for 187 new flow called in stamdaten app
                            $checkListCollection = $this->getExaminationsCheckListQRCodeUnread($value['id'],$appointment_id,$patient_id); //added on 30-sept-24 for #187


                            $s_id = $value['id'];
                            if(!empty($checkListCollection) && count($checkListCollection)>0)
                            {
                                $checkListCollection  = $checkListCollection->map(function($index) use($appointment_id,$patient_id,$s_id)
                                {
                                    $chkcollections = $this->CheckListHasSelectedQuestionModel
                                            ->where('fk_patient_id',$patient_id)
                                            ->where('fk_appointment_id',$appointment_id)
                                             //->where('fk_examination_id',$index->fk_examinations_id)

                                            ->where('fk_check_list_id',$index->fk_check_list_id)
                                            ->first();

                                   //  dump($chkcollections);       
                                    if(!empty($chkcollections))
                                    {
                                      if($chkcollections['status']=='0')
                                      {
                                      //  dump($chkcollections['status']);   

                                        $selected_array= json_decode($chkcollections['questions'],true);
                                        $cehcklist_ids = [];
                                        $question_id = [];
                                        if(!empty($selected_array))
                                        {
                                            foreach($selected_array as $key=>$value)
                                            {
                                                $cehcklist_ids[] = $value['checklist_id'];
                                               // $question_id[] = $value['question_id'];
                                                foreach($value['heading'] as $i_key=>$i_value)
                                                {
                                                    foreach($i_value['question'] as $q_key=>$q_value)
                                                    {
                                                        if($q_value['question']['flag'] == 1)
                                                        {
                                                            $question_id[] = $q_value['question']['question_id'];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $selected_Services = $this->AppointmentHasExaminationsModel
                                             ->select('examination_id')
                                             ->where('appointment_id', $appointment_id)
                                             ->where('examination_id', $s_id)
                                             ->first();

                                        if(!empty($selected_Services))
                                        {
                                            $index->selected_on = 1;
                                        }
                                        else
                                        {
                                            $index->selected_on = 0;
                                        }
                                        // log::info($selected_Services);
                                        $index->exam_flag = 1;

                                        $checklist = $index->getChecklistQR;
                                        //dump($checklist);


                                       
                                        $checklist = $checklist->map(function($checklist_index) use($cehcklist_ids,$selected_array,$index,$question_id)
                                        {

                                                if(in_array($checklist_index->id,$cehcklist_ids))
                                                {
                                                    $checklist_index->checklist_flag = 1;

                                                    $heading_section= $checklist_index->getHEadingSectionQR->map(function($heading_index) use($selected_array,$question_id)
                                                    {
                                                         $question_section= $heading_index->getQuestionQR->map(function($question_index) use($selected_array,$question_id)
                                                        {
                                                            if(in_array($question_index->id,$question_id))
                                                            {
                                                                $question_index->question_flag = 1;
                                                            }else
                                                            {
                                                                $question_index->question_flag = 0;
                                                            }
                                                            return $question_index;
                                                        });

                                                        return $heading_index;
                                                    });

                                                }
                                                else
                                                {
                                                    $checklist_index->checklist_flag = 0;

                                                }
                                                // dd($checklist_index->getHEadingSectionQR);
                                                 return $checklist_index;
                                            
                                           
                                        });
                                         return $index;//added on 11-dec-24 for vertung not shown issue
                                      }//if chkcollections status  unread conditon added on 11-dec-24
                                    }
                                    else
                                    {
                                        $index->exam_flag = 0;
                                        return $index;//added on 11-dec-24 for vertung not shown issue
                                    }
                                   // return $index; //commented on 11-dec-24 for vertung not shown issue
                                });

                               
                               //dump($checkListCollection);                     

                               // $collections[$cnt]['checklist'] = $checkListCollection; //commented on 11-dec-24 for vertung not shown issue

                               //start added on 11-dec-24 for vertung not shown issue
                                if ($checkListCollection->isEmpty() || $checkListCollection->filter()->isEmpty()) {
                                    $collections[$cnt]['checklist'] = [];

                                }else{

                                     //$checkListCollection = $checkListCollection->toArray();




                                   /*$chkUnreadChecklists = $this->CheckListHasSelectedQuestionModel
                                        ->where('fk_patient_id', $patient_id)
                                        ->where('fk_appointment_id', $appointment_id)
                                        ->where('status', '=', '0')
                                        ->get();

                                    // Check if any checklists are retrieved
                                    if ($chkUnreadChecklists->isNotEmpty()) {
                                        // Extract the fk_check_list_id values into an array
                                        $checkListIds = $chkUnreadChecklists->pluck('fk_check_list_id')->toArray();

                                        //dump($checkListIds);
                                        
                                        // Filter the $checkListCollection to only include matching fk_check_list_id values
                                        $newCheck = array_filter($checkListCollection, function ($item) use ($checkListIds) {
                                            return in_array($item['fk_check_list_id'], $checkListIds);
                                        });

                                        // Ensure the array is re-indexed properly

                                        $collections[$cnt]['checklist'] = array_values($newCheck);
                                    }*/
                                    //Roshani added this code on 09-july-2025 for header and footer image
                                    foreach ($checkListCollection as $checkListItem) {
                                        if ($checkListItem->getChecklistQR && $checkListItem->getChecklistQR->count()) {
                                             foreach ($checkListItem->getChecklistQR as $checklist) {
                                                if (!empty($checklist->header_image_path)) {
                                                    // Prepend the base URL or modify as needed
                                                    $checklist->header_image_path = self::getFilePath($checklist->header_image_path);
                                                    $checklist->footer_image_path = self::getFilePath($checklist->footer_image_path);
                                                } else {
                                                    $checklist->header_image_path = null; // or set a default 
                                                    $checklist->footer_image_path = null; // or set a default
                                                }
                                            }
                                        }
                                    }
                                    //Roshani added this code on 09-july-2025 for header and footer image
                                    $collections[$cnt]['checklist'] = array_values(array_filter($checkListCollection->toArray(), function ($item) {
                                        return !is_null($item);
                                    }));


                                }
                                //end added on 11-dec-24 for vertung not shown issue

                            }
                            else
                            {
                                $collections[$cnt]['checklist'] = [];
                            }
                            $cnt++;
                        }

                        // foreach ($collections as $key => $value)
                        // {
                        //     // $checkListCollection = $this->getExaminationsCheckListQRCode($value['id'],$appointment_id,$patient_id);

                        //     $chkcollections = $this->CheckListHasSelectedQuestionModel
                        //                       ->where('fk_patient_id',$patient_id)
                        //                       ->where('fk_appointment_id',$appointment_id)
                        //                       ->get();

                            // if(!empty($chkcollections) && sizeof($chkcollections)>0)
                            // {
                            //     foreach ($chkcollections as $key => $chkcollections_val)
                            //     {

                            //         if($chkcollections_val['questions'])
                            //         {
                            //             $collections[$cnt]['with_checked_qus'] = json_decode($chkcollections_val['questions'],true);
                            //         }
                            //         else
                            //         {
                            //             $collections[$cnt]['with_checked_qus'] = [];
                            //         }
                            //     }
                            // }
                            // else
                            // {
                            //     $examinationsDetails = $this->BaseModel->where('id',$value['id'])->first();
                            //     $checkListCollection = $this->ExaminationsHasMultipleCheckListModel
                            //                 ->where('fk_examinations_id',$value['id'])
                            //                 ->with(['getChecklistQR.getHEadingSectionQR.getQuestionQR'])
                            //                 ->get();

                            //     if(!empty($checkListCollection) && count($checkListCollection)>0)
                            //     {
                            //         $collections[$cnt]['checklist'] = $checkListCollection;
                            //     }
                            //     else
                            //     {
                            //         $collections[$cnt]['checklist'] = [];
                            //     }

                            // }
                            // ==============
                            // $cnt++;

                        // }
                        // added by vijay 14/3/2024
                        $getAppointmentNonServciesIds = $this->AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($appointmentTypeID);
                        $excludedNonServisesCollection = [];
                        foreach ($collections as $key => $value) {
                            if (!in_array($value->id, $getAppointmentNonServciesIds)) {
                                $excludedNonServisesCollection[] = $value;
                            }
                        }
                        // end


                        Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data excludedNonServisesCollection");
                        Log::info($excludedNonServisesCollection);
                         Log::info("in api getPatientOrTriggerExaminationsQRCodetest master data all_exam_ids");
                        Log::info($all_exam_ids);

                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data[0]['exams']  = $excludedNonServisesCollection;
                        $data[0]['appointment_type']  = $appointment_type_details;
                        $data[0]['exam_ids']  = $all_exam_ids;

                        $status  = true;
                        $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                        self::_createLog('getProfileExaminations',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                    }
                    else{
                        $status = true;
                        $message = __('api.DATE_NOT_FOUND');
                    }
                }
                else{
                    $status = false;
                    $message = __('api.DATE_NOT_FOUND');
                }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function createPatientExaminations(Request $request)
    {
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;

        $inputdata  = $request->all();
        $pId = $request->patient_id;
        $examIds = $request->examination_id;

        try{

             $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                            ],
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }else
            {
                if(!empty($examIds) && count($examIds)>0){

                    $exam_data = [];
                    foreach ($examIds as $key => $examId) {
                        $exam_data[] = array(
                                            'patient_id'=> $pId,
                                            'examination_id'=> $examId,
                                        );
                    }

                    $this->AppointmentHasExaminationsModel->where('patient_id', $pId)->delete();
                    if($this->AppointmentHasExaminationsModel->insert($exam_data)){

                        $status  = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data  = $exam_data;
                        self::_createLog('createExaminations',array($data),'info');
                        // $this->ActivityLogModel->addApiLog('Get Examinations','get examinations','Get',null,$data);
                    }else{
                        $message  = __('api.ERR_NOT_FOUND');
                        $errors[] = [
                              "error" => __('api.DATA_NOT_FOUND'),
                          ];
                        self::_createLog('createExaminations',$errors,'error');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
                    }

                }else{
                        $message  = __('api.ERR_EXAMS_NOT_SELECTED');
                        $errors[] = [
                              "error" => __('api.ERR_EXAMS_NOT_SELECTED'),
                          ];
                        self::_createLog('createExaminations',$errors,'error');

                }


            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('createExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

       return self::_sendResult($message,$data,$errors,$status);
    }

    // public function _storeOrUpdate($collection, $request)
    // {
    //     // dump($request->all());

    //     $exams = $request->examination_id;
    //     foreach ($exams[0] as $exam) {

    //         $collection->patient_id     = $request->patient_id;
    //         $collection->examination_id = $exam;
    //         $collection->save();
    //     }
    //     //Save data

    //     return $collection;
    // }

    public function getPatientExaminations(Request $request){
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;
        $patientId   = $request->patient_id;

        $inputdata  = $request->all();

        try{
            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                            ],
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }else
            {

            $collection = $this->AppointmentHasExaminationsModel
                                ->leftjoin('examinations', 'examinations.id' , '=', 'patient_has_examinations.examination_id')
                                ->where('patient_has_examinations.patient_id', $patientId)
                                ->get([
                                    'examinations.id',
                                    'examinations.name',
                                    'examinations.url',
                                ]);

             if(!empty($collection) && sizeof($collection) > 0){
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collection;
                    self::_createLog('getExaminations',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('Get Examinations','get examinations','Get',null,$data);
                }else{
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                    self::_createLog('getExaminations',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
                }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getExaminationsCheckList($id,$appointment_id,$patient_id)
    {
        try
        {
            $examinationsDetails = $this->BaseModel->where('id',$id)->first();
            $collections = $this->ExaminationsHasMultipleCheckListModel
                        ->where('fk_examinations_id',$id)
                        ->with(['getChecklistQR.getHEadingSectionQR.getQuestionQR'])
                        ->get();

        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return $collections;

    }
    /*
    |Examination Check List
    */
    public function getExaminationsCheckListSingQRCode($id,$appointment_id,$patient_id)
    {
        try
        {
            $examinationsDetails = $this->BaseModel->where('id',$id)->first();
            $collections = $this->ExaminationsHasMultipleCheckListModel
                           ->where('fk_examinations_id',$id)
                           ->with(['getChecklistSingQR.getHEadingSectionQR.getQuestionQR'])
                           ->get();


        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return $collections;

    }
    public function getExaminationsCheckListQRCode($id,$appointment_id,$patient_id)
    {

        try
        {
             if (!empty($appointment_id)) {
                            $getSpecialistId = $this->AppointmentModel
                                                ->with('appointmentType')
                                                ->find($appointment_id)
                                                ->appointmentType
                                                ->fk_specialist_id ?? null;

                        }
            $examinationsDetails = $this->BaseModel->where('id',$id)->first();
            $collections = $this->ExaminationsHasMultipleCheckListModel
                           ->where('fk_examinations_id',$id)
                           ->where('fk_specialist_id', $getSpecialistId)
                           ->with(['getChecklistQR.getHEadingSectionQR.getQuestionQR'])
                           ->get();


        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return $collections;

    }
    public function generateExaminationCheckListPdf(Request $request)
    {

        Log::info("in generateExaminationCheckListPdf api function");
        Log::info($request->all());

        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $inputdata  = $request->all();
        try
        {
           if(!empty($inputdata['pdf']) && sizeof($inputdata['pdf']) > 0)
            {
               foreach ($inputdata['pdf'] as $key => $value)
               {
                    $examinations_id     = $value['examinations_id'];

                    Log::info("in generateExaminationCheckListPdf api function examinations_id");
                    Log::info($examinations_id);


                    $examinationsDetails = $this->BaseModel->where('id',$examinations_id)->first();

                    $collection = self::_createPdf($examinations_id,$value['check_list'],$inputdata);
               }
            }

            $status        = true;
            $message       = __('api.DATA_FOUND_SUCCESS');
            //$data['date']  = $inputdata;
            $data['date']  = 'PDF generated successfully.';

            $status  = true;
            $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
            self::_createLog('examinationsChecklistPdf',array($data),'info');
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('generateExaminationCheckListPdf',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

       return self::_sendResult($message,$data,$errors,$status);
    }

    public function generateExaminationCheckListPdfQrcode(Request $request)
    {



        //dd($request->all());
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;
        $collection = [];

        $inputdata  = $request->all();
        //dd($inputdata['pdf']);



        try
        {



            if(!empty($inputdata['pdf']) && sizeof($inputdata['pdf']) > 0)
            {
               foreach ($inputdata['pdf'] as $key => $value)
               {
                    $examinations_id     = $value['examinations_id'];
                    $examinationsDetails = $this->BaseModel->where('id',$examinations_id)->first();
                    if(isset($value['check_list']))
                    {
                      $collection = self::_createPdf($examinations_id,$value['check_list'],$inputdata);
                    }

               }
            }

            $status        = true;
            $message       = __('api.DATA_FOUND_SUCCESS');
            //$data['date']  = $inputdata;
            $data['date']  = 'PDF generated successfully.';

            $status  = true;
            $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
            self::_createLog('examinationsChecklistPdf',array($data),'info');
        }
        catch(\Exception $e) {


            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getPatientCheckList(Request $request)
    {
        $data = $dataFinal = [];
        $cnt = 0;
        $file_name = '';
        $inputdata = $request->all();
        $data_final = [];
        $errors     = [];

        $errors     = [];
        $status     = false;

        try
        {
            $collections = $this->CheckListHasSelectedQuestionModel
                           ->where('fk_patient_id',$inputdata['patient_id'])
                           ->where('fk_appointment_id',$inputdata['appointment_id'])
                           ->where('type','performance')
                           ->get();

             if(!empty($collections))
            {
                $cnttt = 0;
                $cnt = 0;
                foreach ($collections as $key => $collections)
                {
                    if($collections['questions'])
                    {
                        //$data['check_list']        = json_decode($collections['questions'],true);
                        $check_list        = json_decode($collections['questions'],true);

                        if($check_list)
                        {

                            foreach ($check_list as $ck => $cval)
                            {
                                $getcheckList = $this->CheckListModel
                                                ->find($cval['checklist_id']);

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
                                $data[$cnt]['signDoc']           = $getcheckList->signDoc;


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
                            //$data_final[] = $data;
                            //$data = [];
                        }
                    }
                    else
                    {
                       $data_final =[];
                    }
                    $cnt++;
                }
            }
            $status        = true;
            $message       = __('api.DATA_FOUND_SUCCESS');
            // $data['data']  = $data_final;
            $data['data']  = $data;

            self::_createLog('getPatientCheckList',array($data),'info');
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getPatientCheckList',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

       return self::_sendResult($message,$data,$errors,$status);

        return $data;
    }

    public function getPatientGeneralCheckList(Request $request)
    {
        $data = $dataFinal = [];
        $cnt = 0;
        $file_name = '';
        $inputdata = $request->all();
        $data_final = [];
        $errors     = [];

        $errors     = [];
        $status     = false;

        try
        {
            //dd($inputdata['patient_id'],$inputdata['appointment_id']);
            $collections = $this->CheckListHasSelectedQuestionModel
                            ->where('fk_patient_id',$inputdata['patient_id'])
                            //->where('fk_appointment_id',$inputdata['appointment_id'])
                            ->where('type','general')
                            //->where('status','1')
                            //->orWhere('status','0')
                            //->where('check_list_flag',$request->check_list_flag) chk
                            ->get();


            if(!empty($collections))
            {
                $cnttt = 0;
                $cnt = 0;
                foreach ($collections as $key => $collections)
                {
                    //dd($collections['status']);
                    $myStatus = explode(',', $collections['status']);
                    if (!in_array('2', $myStatus))
                    {
                        if($collections['questions'])
                        {
                            //$data['check_list']        = json_decode($collections['questions'],true);
                            $check_list        = json_decode($collections['questions'],true);

                            if($check_list)
                            {

                                foreach ($check_list as $ck => $cval)
                                {
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
                                           //dd($value['question']['fk_heading_id']);
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

                                //$data_final[] = $data;
                                //$data = [];
                            }

                        }
                        else
                        {
                           $data_final =[];
                        }
                        $cnt++;
                    }

                }
            }
            //dd($data);
            $status        = true;
            $message       = __('api.DATA_FOUND_SUCCESS');
            //$data[]  = $data_final;

            self::_createLog('getPatientGeneralCheckList',array($data),'info');
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getPatientGeneralCheckList',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);

        return $data;
    }





    public function getPatientPerformanceCheckList(Request $request)
    {

        Log::info("in getPatientPerformanceCheckList api function");
        Log::info($request->all());

        $errors     = [];
        $data       = $data_collection = $data_collection = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;
        $inputdata  = $request->all();
        $patient_id  = $inputdata['patient_id'];
        $appointment_id = $inputdata['appointment_id'];
        $exam_arr = $inputdata['examinations'];
        //$data['appointment_id']=$appointment_id;
        $arr_exam_temp = [];
        try{
            $getAppointmentRec = $this->AppointmentModel->find($appointment_id);
            //dd($getAppointmentRec);

            //start commnted on 24-jan-25
            /* if(!empty($getAppointmentRec))
            {
                $getExam =  $this->AppointmentTypeHasExaminationsModel
                            ->where('appoinment_type_has_examinations.appoinment_id',$getAppointmentRec->appointment_type_id)
                            ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                            // ->where('examinations.show_as_recommended','0')//commented for #267 issue
                            ->get([
                                    'examinations.id',
                                ]);
            }


            if(!empty($getExam))
            {
                foreach ($getExam as $e_key => $e_value)
                {
                    $arr_exam_temp[] = $e_value['id'];
                }
            }*/
            //end commnted on 24-jan-25

            /*********start**added on 24-jan-25**************************/
            if(!empty($getAppointmentRec))
            {
                $appointmentTypeId = $getAppointmentRec->appointment_type_id;
                $getAppType = $this->AppointmentTypesModel->where('id', $appointmentTypeId)->first();
                if (isset($getAppType) && !empty($getAppType))
                {
                    $getAppTypeName = $getAppType->name;
                    $examinationName = $this->BaseModel->where('name', $getAppTypeName)->where('default_service', 1)->first();
                    if (isset($examinationName) && !empty($examinationName)) {
                        $defaultServiceId = $examinationName->id;
                        $arr_exam_temp[] = $defaultServiceId;
                    }
                }
            }
             /*********end***added on 24-jan-25*************************/


            $finalExam = array_merge($exam_arr,$arr_exam_temp);
            //dd($finalExam);
            if(!empty($finalExam))
            {
                foreach ($finalExam as $e_key => $e_value)
                {
                    $hasExam = $this->AppointmentHasExaminationsModel
                               ->where('appointment_id',$appointment_id)
                               ->where('patient_id',$patient_id)
                               ->where('examination_id',$e_value)
                               ->first();
                    if(empty($hasExam))
                    {
                        $hasInsertExam = new $this->AppointmentHasExaminationsModel;
                        $hasInsertExam->appointment_id = $appointment_id;
                        $hasInsertExam->patient_id = $patient_id;
                        $hasInsertExam->examination_id = $e_value;
                        $hasInsertExam->save();
                    }

                    $getMultipleCheckList = $this->ExaminationsHasMultipleCheckListModel
                                          ->where('fk_examinations_id',$e_value)
                                          ->whereNull('deleted_at')
                                          ->get();
                                          //dd($getMultipleCheckList);
                    if(!empty($getMultipleCheckList) && sizeof($getMultipleCheckList)>0)
                    {
                        $cnt = 0;
                        foreach ($getMultipleCheckList as $mchk_key => $mchk_value)
                        {
                            //dd($mchk_value['fk_check_list_id']);

                            //Roshani made changes at 23-july-25
                            if (!empty($appointment_id)) {
                                $getSpecialistId = $this->AppointmentModel
                                                    ->with('appointmentType')
                                                    ->find($appointment_id)
                                                    ->appointmentType
                                                    ->fk_specialist_id ?? null;
                               
                            }
                            //Roshani made changes at 23-july-25

                            $getcheckList = $this->CheckListModel
                                            ->where('type_of_checklist','performance')
                                            ->where('id',$mchk_value['fk_check_list_id'])
                                            ->where('fk_specialist_id', $getSpecialistId)
                                            ->where('status',1)
                                            ->first();
                            //dd($getcheckList);
                            if(!empty($getcheckList))
                            {
                                $patientDetails = $this->PatientsModel
                                                       ->where('id',$patient_id)
                                                       ->first();

                                if(!empty($patientDetails))
                                {

                                     //commented below code on 3-oct-24 for waiting no process //uncommneted on 28-oct-24 for revert

                                    //commneted on 18-nov-24 for 187 new flow
                                    /*$data[$cnt]['checklist_id']      = $getcheckList->id;
                                    $data[$cnt]['check_list_name']   = $getcheckList->check_list_name;
                                    $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                                    $data[$cnt]['final_name']        = $getcheckList->final_name;
                                    $data[$cnt]['exam_id']           = $e_value;
                                    //dd($data);
                                    $getHEading = self::getHeadingDetails($getcheckList->id);
                                    $data[$cnt]['heading'] = $getHEading;
                                    $cnt++;*/

                                    //commented on 28-oct-24 for revert
                                    //start added below code on 3-oct-24 for #187 wating no process
                                    //uncommneted on 18-nov-24 for 187 new flow
                                    $existRec = $this->CheckListHasSelectedQuestionModel
                                                ->where('fk_patient_id',$patient_id)
                                                ->where('fk_appointment_id',$appointment_id)
                                                ->where('fk_check_list_id',$getcheckList->id)
                                                ->where('fk_examination_id',$e_value)
                                                ->first();

                                    
                                     $flag = 0;
                                     if(isset($existRec))
                                     {
                                        $chkStatus = explode(',', $existRec->status);

                                        if(in_array('0', $chkStatus))
                                        {
                                            $flag = 1;
                                        }
                                     }//

                                    if($flag==1)
                                    {
                                        // ✅ STEP 1: Get already added checklist IDs
                                        $allChecklistIds = array_merge(
                                            array_column($data_collection, 'checklist_id'),
                                            array_column($data, 'checklist_id')
                                        );

                                        // ✅ STEP 2: If already added, skip it
                                        if (in_array($getcheckList->id, $allChecklistIds)) {
                                            continue; // this will skip to the next loop
                                        }
                                        //Roshani added this code on 08-july-2025 for header and footer image

                                        $header_image_path    = self::getFilePath($getcheckList->header_image_path);
                                        $footer_image_path    = self::getFilePath($getcheckList->footer_image_path);

                                        //Roshani added this code on 08-july-2025 for header and footer image
                                        $data[$cnt]['checklist_id']      = $getcheckList->id;
                                        $data[$cnt]['check_list_name']   = $getcheckList->check_list_name;
                                        $data[$cnt]['introduction_text'] = $getcheckList->introduction_text;
                                        $data[$cnt]['final_name']        = $getcheckList->final_name;
                                        $data[$cnt]['exam_id']           = $e_value;
                                        //dd($data);
                                        //Roshani added this code on 08-july-2025 for header and footer image
                                        $data[$cnt]['header_image']        = $getcheckList->header_image;
                                        $data[$cnt]['footer_image']        = $getcheckList->footer_image;
                                        $data[$cnt]['header_image_path']     = (isset($getcheckList->header_image) && !empty($getcheckList->header_image))?$header_image_path:'' ;
                                        $data[$cnt]['footer_image_path']        = (isset($getcheckList->footer_image) && !empty($getcheckList->footer_image))?$footer_image_path:'' ;
                                        //Roshani added this code on 08-july-2025 for header and footer image
                                        $getHEading = self::getHeadingDetails($getcheckList->id);
                                        $data[$cnt]['heading'] = $getHEading;

                                    }//if flag 1
                                    $cnt++;
                                    //end added below code on 3-oct-24 for #187 wating no process



                                }//if
                            }
                        }
                        if(empty($data_collection))
                        {
                            $data_collection = $data;
                            $data = [];
                        }
                    }
                }
            }

            //dd($data_collection,$data);
            $finalData = array_merge($data_collection,$data);
            $status  = true;
            $message = __('api.DATA_FOUND_SUCCESS');
            $data_collection  = $finalData;
            //dd($data_collection);

            Log::info("in getPatientPerformanceCheckList api function response");
            Log::info($data_collection);

            $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
            self::_createLog('getAllGeneralDocument',array($data_collection),'info');

        }
        catch(\Exception $e)
        {

            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];

            self::_createLog('getPatientCheckListQrcode',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

       return self::_sendResult($message,$data_collection,$errors,$status);
    }

    // public function getPatientCheckListQrcode(Request $request)
    // {
    //     $data = $dataFinal = [];

    //     $file_name = '';
    //     $inputdata = $request->all();
    //     $data_final = [];
    //     $errors     = [];

    //     $errors     = [];
    //     $status     = false;

    //     try
    //     {
    //         $collections = $this->CheckListHasSelectedQuestionModel
    //                        ->where('fk_patient_id',$inputdata['patient_id'])
    //                        ->where('fk_appointment_id',$inputdata['appointment_id'])
    //                        ->where('type','performance')
    //                        ->get();

    //         if(!empty($collections))
    //         {
    //             foreach ($collections as $key => $collections)
    //             {
    //                 $data['fk_patient_id']     = $collections['fk_patient_id'];
    //                 $data['fk_appointment_id'] = $collections['fk_appointment_id'];
    //                 $data['fk_examination_id'] = $collections['fk_examination_id'];
    //                 // $data['check_list']        = json_decode($collections['questions'],true);
    //                 if($collections['questions'])
    //                 {
    //                     $check_list        = json_decode($collections['questions'],true);

    //                     if($check_list)
    //                     {
    //                        $cnt = 0;
    //                         foreach ($check_list as $ck => $cval)
    //                         {
    //                             $getcheckList = $this->CheckListModel
    //                                             ->find($cval['checklist_id']);

    //                             if($cval['signature'] !=null)
    //                             {
    //                                 $data['check_list']['signature'] = $cval['signature'];
    //                             }
    //                             else
    //                             {
    //                                 $data['check_list']['signature']         = '';
    //                                 $flag = '0';
    //                             }



    //                             $data['check_list']['checklist_id']      = $cval['checklist_id'];
    //                             $data['check_list']['check_list_name']   = $cval['check_list_name'];
    //                             $data['check_list']['introduction_text'] = $cval['introduction_text'];
    //                             $data['check_list']['final_name']        = $cval['final_name'];
    //                             $data['check_list']['signDoc']           = $getcheckList->signDoc;


    //                             $j = 0;
    //                             foreach ($cval['heading'] as $heading)
    //                             {
    //                                 $data['check_list']['heading'][$j]['fk_chk_id']= $heading['fk_chk_id'];
    //                                 $data['check_list']['heading'][$j]['heading_id']= $heading['heading_id'];
    //                                 $data['check_list']['heading'][$j]['heading']  = $heading['heading'];

    //                                 $k=0;
    //                                 foreach ($heading['question'] as $key => $value)
    //                                 {
    //                                     //check list question
    //                                    //dd($value['question']['fk_heading_id']);
    //                                     $data['check_list']['heading'][$j]['question'][$k]['question']['fk_chk_id'] = $heading['fk_chk_id'];
    //                                     $data['check_list']['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $value['question']['fk_heading_id'];
    //                                     $data['check_list']['heading'][$j]['question'][$k]['question']['question_id'] = $value['question']['question_id'];
    //                                     $data['check_list']['heading'][$j]['question'][$k]['question']['question'] = $value['question']['question'];
    //                                     $data['check_list']['heading'][$j]['question'][$k]['question']['flag']     = $value['question']['flag'];
    //                                     $k++;
    //                                 }
    //                                 $j++;
    //                             }
    //                             $data_final[] = $data;
    //                             $data = [];
    //                            $cnt++;
    //                         }
    //                     }
    //                 }
    //                 else
    //                 {
    //                    $data_final =[];
    //                 }


    //                 //selected check list array
    //                 $que = json_decode($collections['questions'],true);

    //             }
    //         }
    //         $status        = true;
    //         $message       = __('api.DATA_FOUND_SUCCESS');
    //         $data['data']  = $data_final;

    //         self::_createLog('getPatientCheckListQrcode',array($data),'info');
    //     }
    //     catch(\Exception $e) {
    //         $message = __('api.ERR_SOMETHING_WRONG');
    //         $errors[] = [
    //               "error" => $e->getMessage(),
    //           ];
    //         self::_createLog('getPatientCheckListQrcode',$errors,'error');
    //         // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    //     }

    //    return self::_sendResult($message,$data,$errors,$status);

    //     return $data;
    // }

    public function getPatientCheckListQrcode(Request $request)
    {



        $data = $dataFinal = [];
        $cnt = 0;
        $file_name = '';
        $inputdata = $request->all();
        $data_final = [];
        $errors     = [];
        $data['data']  = [];
        $errors     = [];
        $status     = false;



        try
        {

            //commented on 18-nov-24 for 187 new flow on 18-nov-24
            /*$collections = $this->CheckListHasSelectedQuestionModel
                           ->where('fk_patient_id',$inputdata['patient_id'])
                           ->where('fk_appointment_id',$inputdata['appointment_id'])
                           ->where('type','performance')
                          // ->where('status','0') //added on 30-sept-24 for #187 to do not show performance chklist in signdoc app again //commented on 28-oct-24 for revert changes of #187
                           ->groupBy('fk_examination_id','fk_check_list_id')
                           ->get();*/

             //did changes for 187 new flow on 18-nov-24 //commented on 29-nov-24
             /*$collections = $this->CheckListHasSelectedQuestionModel
                          ->join('examinations_check_list', 'examinations_check_list.id', '=', 'check_list_has_selected_questions.fk_check_list_id') //added new on 18-nov-24
                           ->where('fk_patient_id',$inputdata['patient_id'])
                           ->where('fk_appointment_id',$inputdata['appointment_id'])
                           ->where('type','performance')
                            ->where('examinations_check_list.signDoc', 'sign') //added new on 18-nov-24
                             ->where('check_list_has_selected_questions.status','!=','1,2')//added on 19-nov-24
                           ->groupBy('fk_examination_id','fk_check_list_id')
                           ->get();*/
            //Roshani added the code
            $appointment_id = $request->appointment_id;
            $getSpecialistId = null;
            if (!empty($appointment_id)) {
                    $getSpecialistId = $this->AppointmentModel
                                        ->with('appointmentType')
                                        ->find($appointment_id)
                                        ->appointmentType
                                        ->fk_specialist_id ?? null;

                }
              //added on 29-nov-24  for signdoc_app_unread flow
             $collections = $this->CheckListHasSelectedQuestionModel
                          ->join('examinations_check_list', 'examinations_check_list.id', '=', 'check_list_has_selected_questions.fk_check_list_id') //added new on 18-nov-24
                           ->where('fk_patient_id',$inputdata['patient_id'])
                           ->where('fk_appointment_id',$inputdata['appointment_id'])
                           ->where('type','performance')
                            //->where('examinations_check_list.signDoc', 'sign') //added new on 18-nov-24 //commented on 29-nov-24
                            // ->where('check_list_has_selected_questions.status','!=','1,2')//added on 19-nov-24 //commented on 29-nov-24
                            ->where('examinations_check_list.fk_specialist_id', $getSpecialistId)
                            ->where(function ($query){
                                  $query->where(function ($subQuery) {
                                      // Condition 1: signDoc is 'sign' and status not equal to '1,2'
                                      $subQuery->where('examinations_check_list.signDoc', 'sign')
                                               ->where('check_list_has_selected_questions.status', '!=', '1,2');

                                        // if (!empty($getSpecialistId)) {
                                        //     $subQuery->where('examinations_check_list.fk_specialist_id', $getSpecialistId);
                                        // }
                                  })
                                  ->orWhere(function ($subQuery) {
                                      // Condition 2: status is '0' (unread)
                                      $subQuery->where('check_list_has_selected_questions.status', '0');
                                  });
                              }) // Added on 29-nov-24 for signdoc unread flow

                           ->groupBy('fk_examination_id','fk_check_list_id')
                           ->get();


            // dd($collections);
            // log::info('getPatientCheckListQrcode');
            // log::info($collections);
            if(!empty($collections))
            {


                foreach ($collections as $key => $collections)
                {
                    $getcheckList = $this->CheckListModel->find($collections['fk_check_list_id']);

                    $data['fk_patient_id']     = $collections['fk_patient_id'];
                    $data['fk_appointment_id'] = $collections['fk_appointment_id'];
                    $data['fk_examination_id'] = $collections['fk_examination_id'];
                    // $data['check_list']        = json_decode($collections['questions'],true);
                    //Roshani sonawane added this code for check checklist header and footer image path exist or not on 9-july-25
                    if($collections['questions'])
                    {
                        $questions = json_decode($collections['questions'], true);
                        if (is_array($questions)) {
                            foreach ($questions as &$question) {
                                if (!empty($question['header_image_path'])) {
                                    $relativePath = str_replace(url('/'), '', $question['header_image_path']);
                                    $localPath = public_path($relativePath);
                                    if (file_exists($localPath) && is_file($localPath)) {
                                        $question['header_image_exists'] = true;
                                    } else {
                                        $question['header_image_path'] = '';
                                        $question['header_image_exists'] = false;
                                    }
                                } else {
                                    $question['header_image_exists'] = false;
                                    $question['header_image_path'] = '';
                                }
                                if (!empty($question['footer_image_path'])) {
                                    $relativePath = str_replace(url('/'), '', $question['footer_image_path']);
                                    $localPath = public_path($relativePath);
                                    if (file_exists($localPath) && is_file($localPath)) {
                                        $question['footer_image_exists'] = true;
                                    } else {
                                        $question['footer_image_path'] = '';
                                        $question['footer_image_exists'] = false;
                                    }
                                } else {
                                    $question['footer_image_exists'] = false;
                                    $question['footer_image_path'] = '';
                                }
                            }
                            unset($question); // break reference
                            $data['check_list'] = $questions;
                            $data['check_list'][0]['singDoc'] = $getcheckList->signDoc;
                        } else {
                            $data['check_list'] = [];
                        }
                    }
                    //Roshani sonawane added this code for check checklist header and footer image path exist or not on 9-july-25

                    //selected check list array
                    $que = json_decode($collections['questions'],true);
                    $data_final[] = $data;
                }
            }
            $status        = true;
            $message       = __('api.DATA_FOUND_SUCCESS');
            if(sizeof($data_final)>0)
            {
                $data['data']  = $data_final;
            }
            self::_createLog('getPatientCheckListQrcode',array($data),'info');
        }
        catch(\Exception $e) {



            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                    "error" => $e->getMessage(),
                ];
            self::_createLog('getPatientCheckListQrcode',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
        return self::_sendResult($message,$data,$errors,$status);
        // return $data;
    }
    //demo server
    public function getSingDocPerformaceCheckListQrcode(Request $request)
    {

        //patient_id
        $data = $dataFinal = [];
        $cnt = 0;
        $file_name = '';
        $inputdata = $request->all();
        $data_final = [];
        $errors     = [];
        $chk_collections = [];
        $errors     = [];
        $status     = false;
        $patient_id = $inputdata['patient_id'];
        $appointment_id = $inputdata['appointment_id'];
        try
        {
            $getAppointmentDetails = $this->AppointmentModel->find($inputdata['appointment_id']);
            if(!empty($getAppointmentDetails))
            {
                $getAppointmentType = $this->AppointmentTypeHasExaminationsModel
                                      ->where('appoinment_id',$getAppointmentDetails['appointment_type_id'])
                                      ->get();
                //dump($getAppointmentType,$getAppointmentDetails['appointment_type_id']); chk_collections
                foreach ($getAppointmentType as $m_key=> $m_value)
                {
                    //commented on 30-sept-24 for #187 to do not show perforamance checklist again //reverted on 28-oct-24
                     //commented on 18-nov-24 for 187 new flow
                     // $checkListCollection = $this->getExaminationsCheckListSingQRCode($m_value['examination_id'],$inputdata['appointment_id'],$inputdata['patient_id']);

                      //added on 18-nov-24 for 187 new flow
                      $checkListCollection = $this->getExaminationsCheckListSingQRCodeBackend($m_value['examination_id'],$inputdata['appointment_id'],$inputdata['patient_id']);


                    //Added on 30-sept-24 for #187 to do not show perforamance checklist again //commented on 28-oct-24
                    //$checkListCollection = $this->getExaminationsCheckListQRCodeUnread($m_value['examination_id'],$inputdata['appointment_id'],$inputdata['patient_id']);
                    // dump($checkListCollection);

                    //Roshani added this code on 10-july-2025 for header and footer image

                    foreach ($checkListCollection as $checkListItem) {
                        if ($checkListItem->getChecklistQR && $checkListItem->getChecklistQR->count()) {
                            foreach ($checkListItem->getChecklistQR as $checklist) {
                                if (!empty($checklist->header_image_path)) {
                                    // Prepend the base URL or modify as needed
                                    $checklist->header_image_path = self::getFilePath($checklist->header_image_path);
                                    $checklist->footer_image_path = self::getFilePath($checklist->footer_image_path);
                                } else {
                                    $checklist->header_image_path = null; // or set a default 
                                    $checklist->footer_image_path = null; // or set a default
                                }
                            }
                        }
                    }

                    //Roshani added this code on 10-july-2025 for header and footer image

                    //dd($checkListCollection);
                    if(!empty($checkListCollection) && count($checkListCollection)>0)
                    {

                        $checkListCollection  = $checkListCollection->map(function($index) use($appointment_id,$patient_id)
                        {
                            $chkcollections = $this->CheckListHasSelectedQuestionModel
                                    ->where('fk_patient_id',$patient_id)
                                    ->where('fk_appointment_id',$appointment_id)
                                    ->where('fk_examination_id',$index->fk_examinations_id)
                                    ->first();

                            if(!empty($chkcollections))
                            {
                                $selected_array= json_decode($chkcollections['questions'],true);

                                $cehcklist_ids = [];
                                $question_id = [];
                                foreach($selected_array as $key=>$value)
                                {
                                    $cehcklist_ids[] = $value['checklist_id'];
                                   // $question_id[] = $value['question_id'];
                                    foreach($value['heading'] as $i_key=>$i_value)
                                    {
                                        foreach($i_value['question'] as $q_key=>$q_value)
                                        {
                                            if($q_value['question']['flag'] == 1)
                                            {
                                                $question_id[] = $q_value['question']['question_id'];
                                            }
                                        }
                                    }
                                }

                                $index->exam_flag = 1;

                                $checklist = $index->getChecklistQR;

                                $checklist = $checklist->map(function($checklist_index) use($cehcklist_ids,$selected_array,$index,$question_id)
                                {

                                    if(in_array($checklist_index->id,$cehcklist_ids))
                                    {
                                        $checklist_index->checklist_flag = 1;


                                        $heading_section= $checklist_index->getHEadingSectionQR->map(function($heading_index) use($selected_array,$question_id)
                                        {
                                             $question_section= $heading_index->getQuestionQR->map(function($question_index) use($selected_array,$question_id)
                                            {
                                                if(in_array($question_index->id,$question_id))
                                                {
                                                    $question_index->question_flag = 1;
                                                }else
                                                {
                                                    $question_index->question_flag = 0;
                                                }
                                                return $question_index;
                                            });

                                            return $heading_index;
                                        });

                                    }
                                    else
                                    {
                                        $checklist_index->checklist_flag = 0;
                                    }
                                    // dd($checklist_index->getHEadingSectionQR);
                                    return $checklist_index;
                                });
                            }
                            else
                            {
                                $index->exam_flag = 0;
                            }
                            return $index;
                        });
                        $chk_collections[$cnt]['checklist'] = $checkListCollection;
                    }
                    else
                    {
                        $chk_collections[$cnt]['checklist'] = [];
                    }
                    $cnt++;
                }
            }

            $status        = true;
            $message       = __('api.DATA_FOUND_SUCCESS');
            $data  = $chk_collections;
            self::_createLog('getSingDocPerformaceCheckListQrcode',array($data),'info');
        }
        catch(\Exception $e)
        {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getSingDocPerformaceCheckListQrcode',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
        return self::_sendResult($message,$data,$errors,$status);
    }

    //live server
    // public function getSingDocPerformaceCheckListQrcode(Request $request)
    // {
    //     $data = $dataFinal = [];
    //     $cnt = 0;
    //     $file_name = '';
    //     $inputdata = $request->all();
    //     $data_final = [];
    //     $errors     = [];

    //     $errors     = [];
    //     $status     = false;

    //     $patient_id     = $inputdata['patient_id'];
    //     $appointment_id = $inputdata['appointment_id'];
    //     $collection     = collect([]);
    //     try
    //     {
    //         $patientRec     = $this->PatientsModel->find($inputdata['patient_id']);
    //         $patientAge     = $patientRec->age;

    //         $patientProfile = $this->ProfilesTemplatesModel
    //                                 ->where('age_from', '<=' ,$patientAge)
    //                                 ->where('age_to', '>=' ,$patientAge)
    //                                 ->whereStatus(1)
    //                                 ->first();

    //         if(!empty($patientProfile)){

    //             $profileId = $patientProfile->id;

    //             $collections = $this->BaseModel
    //                                 ->leftjoin('profile_has_examinations','profile_has_examinations.examination_id','=','examinations.id')
    //                                 ->where('profile_id','=',$profileId)
    //                                 ->orWhere('examinations.trigger_exam_flag','=',1)
    //                                 ->groupBy('examinations.id')
    //                                 ->get([
    //                                         'examinations.id',
    //                                         'examinations.name',
    //                                         'examinations.url',
    //                                         'examinations.description',
    //                                         'examinations.document_name',
    //                                         'examinations.document_path',
    //                                         'examinations.document_status',
    //                                         'examinations.status',
    //                                         'examinations.created_at'
    //                                         ]);


    //         }else{
    //             $collections = $this->BaseModel
    //                                 ->where('examinations.trigger_exam_flag','=',1)
    //                                 ->get([
    //                                         'examinations.id',
    //                                         'examinations.name',
    //                                         'examinations.url',
    //                                         'examinations.description',
    //                                         'examinations.document_name',
    //                                         'examinations.document_path',
    //                                         'examinations.document_status',
    //                                         'examinations.status',
    //                                         'examinations.created_at'
    //                                         ]);
    //         }


    //         $appointment_type_details = [];
    //         if(!empty($appointment_id)){
    //             $appointment_type_details = $this->AppointmentModel
    //                                         ->join('appointment_types','appointment_types.id','=','appointment.appointment_type_id')
    //                                         ->where('appointment.id','=',$appointment_id)
    //                                          ->where('appointment_types.recommend_exams','=','0')
    //                                         ->get([
    //                                             'appointment_types.id',
    //                                             'appointment_types.name',
    //                                             'appointment_types.recommend_exams',
    //                                             'appointment_types.created_at'
    //                                             ]);
    //         }

    //         $all_exam_ids = $this->AppointmentHasExaminationsModel
    //                             ->select('examination_id')
    //                             ->where('appointment_id', $appointment_id)
    //                             ->get();

    //         $all_exam_ids = $all_exam_ids->map(function($key)
    //         {
    //             return $key->examination_id;
    //         });

    //         if(!empty($collections) && ($collections->count() > 0))
    //         {
    //             /*
    //             |GET CHECKLIST
    //             */
    //             $cnt = 0;
    //             $arr_checklist = [];

    //             foreach ($collections as $key => $value)
    //             {
    //                 $checkListCollection = $this->getExaminationsCheckListQRCode($value['id'],$appointment_id,$patient_id);

    //                 if(!empty($checkListCollection) && count($checkListCollection)>0)
    //                 {
    //                     $checkListCollection  = $checkListCollection->map(function($index) use($appointment_id,$patient_id)
    //                     {
    //                         $chkcollections = $this->CheckListHasSelectedQuestionModel
    //                                 ->where('fk_patient_id',$patient_id)
    //                                 ->where('fk_appointment_id',$appointment_id)
    //                                 ->where('fk_examination_id',$index->fk_examinations_id)
    //                                 ->first();

    //                         if(!empty($chkcollections))
    //                         {
    //                             $selected_array= json_decode($chkcollections['questions'],true);

    //                             $cehcklist_ids = [];
    //                             $question_id = [];
    //                             foreach($selected_array as $key=>$value)
    //                             {
    //                                 $cehcklist_ids[] = $value['checklist_id'];
    //                                // $question_id[] = $value['question_id'];
    //                                 foreach($value['heading'] as $i_key=>$i_value)
    //                                 {
    //                                     foreach($i_value['question'] as $q_key=>$q_value)
    //                                     {
    //                                         if($q_value['question']['flag'] == 1)
    //                                         {
    //                                             $question_id[] = $q_value['question']['question_id'];
    //                                         }
    //                                     }
    //                                 }
    //                             }

    //                             $index->exam_flag = 1;

    //                             $checklist = $index->getChecklistQR;

    //                             $checklist = $checklist->map(function($checklist_index) use($cehcklist_ids,$selected_array,$index,$question_id)
    //                             {

    //                                 if(in_array($checklist_index->id,$cehcklist_ids))
    //                                 {
    //                                     $checklist_index->checklist_flag = 1;


    //                                     $heading_section= $checklist_index->getHEadingSectionQR->map(function($heading_index) use($selected_array,$question_id)
    //                                     {
    //                                          $question_section= $heading_index->getQuestionQR->map(function($question_index) use($selected_array,$question_id)
    //                                         {
    //                                             if(in_array($question_index->id,$question_id))
    //                                             {
    //                                                 $question_index->question_flag = 1;
    //                                             }else
    //                                             {
    //                                                 $question_index->question_flag = 0;
    //                                             }
    //                                             return $question_index;
    //                                         });

    //                                         return $heading_index;
    //                                     });

    //                                 }
    //                                 else
    //                                 {
    //                                     $checklist_index->checklist_flag = 0;
    //                                 }
    //                                 // dd($checklist_index->getHEadingSectionQR);
    //                                 return $checklist_index;
    //                             });
    //                         }
    //                         else
    //                         {
    //                             $index->exam_flag = 0;
    //                         }
    //                         return $index;
    //                     });
    //                     $collections[$cnt]['checklist'] = $checkListCollection;
    //                 }
    //                 else
    //                 {
    //                     $collections[$cnt]['checklist'] = [];
    //                 }
    //                 $cnt++;
    //             }
    //             $status  = true;
    //             $message = __('api.DATA_FOUND_SUCCESS');
    //             $data  = $collections;
    //             // $data[0]['appointment_type']  = $appointment_type_details;
    //             // $data[0]['exam_ids']  = $all_exam_ids;

    //             $status  = true;
    //             $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
    //             self::_createLog('getProfileExaminations',array($data),'info');
    //             // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

    //         }
    //         else{
    //             $message = __('api.ERR_SOMETHING_WRONG');
    //         }
    //     }
    //     catch(\Exception $e)
    //     {
    //         $message = __('api.ERR_SOMETHING_WRONG');
    //         $errors[] = [
    //               "error" => $e->getMessage(),
    //           ];
    //         self::_createLog('getSingDocPerformaceCheckListQrcode',$errors,'error');
    //         // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    //     }
    //     return self::_sendResult($message,$data,$errors,$status);
    // }

    public function _createPdf($exam_id,$check_list,$inputdata)
    {

        Log::info("in _createPdf api function exam_id");
        Log::info($exam_id);

        Log::info("in _createPdf api function check_list");
        Log::info($check_list);

        Log::info("in _createPdf api function inputdata");
        Log::info($inputdata);
        
        //dd($exam_id,$check_list);
        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = '';
        $examinations_details = $this->BaseModel->where('id',$exam_id)->first();

        foreach ($check_list as $check_list)
        {
            $statusCondition = 0;
            foreach ($check_list['Heading'] as $heading) {
                foreach ($heading['questions'] as $questionData) {
                    foreach ($questionData['questions'] as $questionId => $value) {
                        if ($value == 1) {

                            $statusCondition = 1;
                            break 3;
                        }
                    }
                }
            }

            


            $collections = $this->CheckListModel
                            ->where('id',$check_list['checklist_id'])
                            //->where('id',2)
                             //->where('status',$statusCondition)
                            ->where('status',1)
                            ->first();


                            

            if(!empty($collections))
            {
                //check list details
                if(isset($check_list['sign']) && $check_list['sign'] !=null && $check_list['sign']!="")
                {
                    if(!empty(Config('ordination_id')))
                    {
                        $getDatabaseName = DB::connection('system')
                                    ->table("tenants")
                                    ->where('ordination_id',Config('ordination_id'))
                                    ->first(['uuid']);

                        $signPath = '/tenancy/tenants/'.$getDatabaseName->uuid.'/sign/';
                    }
                    else
                    {
                        $signPath = '/opt/app-shared/php/data/storage/app/public/sign/';
                    }

                    $flag = '1';
                    $file_data = $check_list['sign'];
                    $file_name = 'signature_' . time() . '.png'; //generating unique file name;

                    if ($file_data != "")
                    {
                        // storing image in storage/app/public Folder
                        Storage::disk('public')->put($signPath.$file_name, base64_decode($file_data));
                        $sign_path = self::getFilePath('/sign/'.$file_name);

                        // $data[$cnt]['signature'] = $file_name;
                        $data[$cnt]['signature'] = $sign_path;
                        //$data[$cnt]['signature'] = $file_name;

                    }
                    // log::info("IF");
                    // log::info($data[$cnt]['signature']);
                }
                else
                {
                    $data[$cnt]['signature']         = '';
                    $flag = '0';
                    // log::info("ELSE");
                    // log::info($data[$cnt]['signature']);
                }
                // dd($data);
                $data[$cnt]['checklist_id']      = $collections->id;
                $data[$cnt]['check_list_name']   = $collections->check_list_name;
                $data[$cnt]['introduction_text'] = $collections->introduction_text;
                $data[$cnt]['final_name']        = $collections->final_name;
                $data[$cnt]['fk_exam_id']        = $exam_id;

                /*******Added by divya on 26-dec-22*********/
                $checklistimagepath='';
                if(!empty(Config('ordination_id')))
                {
                    $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);

                    $checklistimagepath = url('storage/tenancy/tenants/'.$getDatabaseName->uuid);
                }
                else
                {
                    $checklistimagepath = '/opt/app-shared/php/data/storage/app/public/';
                }

                $data[$cnt]['header_image']        = isset($collections->header_image)?$collections->header_image:"";
                $data[$cnt]['header_image_path']   = isset($collections->header_image_path)?$checklistimagepath.$collections->header_image_path:"";
                $data[$cnt]['footer_image']        = isset($collections->footer_image)?$collections->footer_image:"";
                $data[$cnt]['footer_image_path']   = isset($collections->footer_image_path)?$checklistimagepath.$collections->footer_image_path:"";


                /*******Added by divya on 26-dec-22*********/




                $data[$cnt]['currentDate']        = date("m/d/Y");
                $patientFirstName = $patientLastName = "";
                $data[$cnt]['patientFullName']= $data[$cnt]['patientDob']= '';
                $getPatientDetails = $this->PatientsModel->where('id',$inputdata['patient_id'])->first();
                if(isset($getPatientDetails) && !empty($getPatientDetails))
                {
                    $patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
                    $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
                    $data[$cnt]['patientFullName'] = $patientFirstName.' '.$patientLastName;
                    $data[$cnt]['patientDob'] = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
                }
                $j = 0;
                foreach ($check_list['Heading'] as $heading)
                {
                    //check list heading
                    $heading_name = $this->CheckListHasHeadingSectionModel
                                    ->where('id',$heading['heading_id'])->first();

                    //isset heading name conditon added on 13-sept-24
                    if(isset($heading_name)){

                            $data[$cnt]['heading'][$j]['fk_chk_id']= $collections->id;
                            $data[$cnt]['heading'][$j]['heading_id']= $heading_name['id'];
                            $data[$cnt]['heading'][$j]['heading']  = $heading_name['heading_section'];

                            ksort($heading['questions'][0]['questions']);
                            foreach ($heading['questions'][0] as $key => $value)
                            {
                                //check list question
                                $k=0;
                                foreach ($value as $keyv => $valque)
                                {
                                    $question = $this->HeadingSectionHasQuestionModel
                                                ->where('id',$keyv)->first();

                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $heading_name['id'];
                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $question['id'];
                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $question['question'];
                                    $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']     = $valque;
                                    $k++;
                                }
                            }
                    }//isset heading name conditon added on 13-sept-24
                    $j++;
                }

                // PDF Generate
                //$PdfPath = self::StorePath('check_list_pdf/');
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
                $PDFname = self::createPdfFileName($inputdata['patient_id'],$collections->check_list_name);
                // Invoice full path
                $StorePath = $PdfPath.$PDFname;

                $accessPath = '/check_list_pdf/'.$PDFname;


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
                //dd($data);
                $pdf->loadView($PDFPath,compact('data'))->save($StorePath);

                /*
                |Check List Selected questions
                */

                $CheckListHasSelectedQuestionModel = $this->CheckListHasSelectedQuestionModel
                                                ->where('fk_patient_id',$inputdata['patient_id'])
                                                ->where('fk_appointment_id',$inputdata['appointment_id'])
                                                ->where('fk_check_list_id',$collections->id)
                                                ->where('fk_examination_id',$exam_id)
                                                ->first();

                Log::info("in _createPdf api function CheckListHasSelectedQuestionModel fk_check_list_id");
                Log::info($collections->id);

                Log::info("in _createPdf api function CheckListHasSelectedQuestionModel exam_id");
                Log::info($exam_id);
                                

                if(!empty($CheckListHasSelectedQuestionModel))
                {

                    Log::info("in _createPdf api function not empty CheckListHasSelectedQuestionModel");

                    $CheckListHasSelectedQuestionModel->fk_patient_id    = $inputdata['patient_id'];
                    $CheckListHasSelectedQuestionModel->fk_examination_id= $exam_id;
                    $CheckListHasSelectedQuestionModel->fk_appointment_id= $inputdata['appointment_id'];
                    $CheckListHasSelectedQuestionModel->fk_check_list_id = $collections->id;
                    $CheckListHasSelectedQuestionModel->questions        = json_encode($data);
                    $CheckListHasSelectedQuestionModel->created_at       = Date('Y-m-d H:i:s');
                    $CheckListHasSelectedQuestionModel->check_list_flag  = $flag;
                    $CheckListHasSelectedQuestionModel->pdf_name         = $PDFname;
                    $CheckListHasSelectedQuestionModel->pdf_path         = $accessPath;
                    $CheckListHasSelectedQuestionModel->signature        = $file_name;
                    $CheckListHasSelectedQuestionModel->export_status    = 0;
                    $CheckListHasSelectedQuestionModel->type             = 'performance';
                    if(!empty($data[$cnt]['signature']))
                    {
                        $CheckListHasSelectedQuestionModel->status         = '1,2';
                    }
                    else
                    {
                        // log::info("ELSE-Signature-1");
                        //$CheckListHasSelectedQuestionModel->status         = '1'; //commented on 27-sept-24 for #187 //reverted on 28-oct-24 //commented on 6-march-25


                        $statusVar = ($statusCondition==1)?1:$CheckListHasSelectedQuestionModel->status;
                        $CheckListHasSelectedQuestionModel->status           = $statusVar;


                        //added on 27-sept-24 for #187  //commented on 28-oct-24 for revert
                       /* if (isset($inputdata['fromSignDoc']) && !empty($inputdata['fromSignDoc'])) {

                            $CheckListHasSelectedQuestionModel->status = '1';
                        } else {

                            $CheckListHasSelectedQuestionModel->status = '0';
                        }*/
                        //added on 27-sept-24 for #187

                    }
                    $CheckListHasSelectedQuestionModel->save();

                }
                else
                {

                    Log::info("in _createPdf api function empty CheckListHasSelectedQuestionModel");

                    $CheckListHasSelectedQuestionModel = new $this->CheckListHasSelectedQuestionModel;

                    $CheckListHasSelectedQuestionModel->fk_patient_id    = $inputdata['patient_id'];
                    $CheckListHasSelectedQuestionModel->fk_examination_id= $exam_id;
                    $CheckListHasSelectedQuestionModel->fk_appointment_id= $inputdata['appointment_id'];
                    $CheckListHasSelectedQuestionModel->fk_check_list_id = $collections->id;
                    $CheckListHasSelectedQuestionModel->questions        = json_encode($data);
                    $CheckListHasSelectedQuestionModel->created_at       = Date('Y-m-d H:i:s');
                    $CheckListHasSelectedQuestionModel->check_list_flag  = $flag;
                    $CheckListHasSelectedQuestionModel->pdf_name         = $PDFname;
                    $CheckListHasSelectedQuestionModel->pdf_path         = $accessPath;
                    $CheckListHasSelectedQuestionModel->signature        = $file_name;
                    $CheckListHasSelectedQuestionModel->export_status    = 0;
                    $CheckListHasSelectedQuestionModel->type             = 'performance';
                    if(!empty($data[$cnt]['signature']))
                    {
                        $CheckListHasSelectedQuestionModel->status         = '1,2';
                    }
                    else
                    {
                        //$CheckListHasSelectedQuestionModel->status         = '1'; //for #187 on 7-oct-24 //reverted on 28-oct-24 //commeted on 6-march-25

                         $CheckListHasSelectedQuestionModel->status         = $statusCondition;//added on 6-march-25


                        //$CheckListHasSelectedQuestionModel->status         = '0'; //for #187 on 7-oct-24 //commented on 28-oct-24 for revert
                    }
                    $CheckListHasSelectedQuestionModel->save();
                }
            }
            $dataFinal[] = $data;
            $data = [];
            //$cnt++;
        }

        return $data;
    }


    public function getAllGeneralDocument(Request $request)
    {

        Log::info("in getAllGeneralDocument api function inputdata");
        Log::info($request->all());

        $errors     = [];
        $data       = $data_collection = [];

        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $patient_id  = $request->patient_id;
        $appointment_id  = $request->appointment_id;
        $type        = $request->type;
        $inputdata  = $request->all();
        $patient_id  = $inputdata['patient_id'];
        $type        = $inputdata['type'];
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              'type'        => 'required',
                            ],
                            [
                              'patient_id.required' => __('api.ERR_PATIENT_ID_REQ'),
                              'type.required'       => __('api.AUTH_PATIENT_GENERAL_TYPE'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }
            else
            {
                //Roshani made changes at 23-july-25
                $getcheckListQuery = $this->CheckListModel
                    ->where('type_of_checklist', 'general')
                    ->where('status', 1);

                if (!empty($appointment_id)) {
                    $getSpecialistId = $this->AppointmentModel
                                        ->with('appointmentType')
                                        ->find($appointment_id)
                                        ->appointmentType
                                        ->fk_specialist_id ?? null;

                    if ($getSpecialistId) {
                        $getcheckListQuery->where('fk_specialist_id', $getSpecialistId);
                    }
                }

                $getcheckList = $getcheckListQuery->get();
                //Roshani made changes at 23-july-25

                // $getcheckList = $this->CheckListModel
                //                 ->where('type_of_checklist','general')
                //                 ->where('status',1)
                //                 ->get();
                //dd($getcheckList);
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
                                if($type == 'check_list')
                                {
                                    $chk_id = $hasDocument->fk_check_list_id;

                                    $chkList = $this->CheckListModel
                                                ->where('status',1)
                                                ->find($chk_id);

                                    if(!empty($chkList))
                                    {
                                        //Roshani added this code on 08-july-2025 for header and footer image

                                        $header_image_path    = self::getFilePath($chkList->header_image_path);
                                        $footer_image_path    = self::getFilePath($chkList->footer_image_path);

                                        //Roshani added this code on 08-july-2025 for header and footer image
                                        //dump($l_date);
                                        if(!empty($l_date))
                                        {
                                            //dump($cnt);
                                            $data[$cnt]['checklist_id']      = $chkList->id;
                                            $data[$cnt]['check_list_name']   = $chkList->check_list_name;
                                            $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                            $data[$cnt]['final_name']        = $chkList->final_name;

                                            //Roshani added this code on 08-july-2025 for header and footer image
                                            $data[$cnt]['header_image']        = $chkList->header_image;
                                            $data[$cnt]['footer_image']        = $chkList->footer_image;
                                            $data[$cnt]['header_image_path']     = (isset($chkList->header_image) && !empty($chkList->header_image))?$header_image_path:'' ;
                                            $data[$cnt]['footer_image_path']        = (isset($chkList->footer_image) && !empty($chkList->footer_image))?$footer_image_path:'' ;
                                            //Roshani added this code on 08-july-2025 for header and footer image

                                            //dd($data);
                                            $getHEading = self::getHeadingDetails($chkList->id);
                                            //dd($getHEading);
                                            $data[$cnt]['heading'] = $getHEading;
                                            //dump('--->'.$cnt);
                                            $cnt++;
                                        }
                                    }
                                }
                            }
                            else
                            {
                                //Roshani added this code on 08-july-2025 for header and footer image

                                $header_image_path    = self::getFilePath($chk_value['header_image_path']);
                                $footer_image_path    = self::getFilePath($chk_value['footer_image_path']);

                                //Roshani added this code on 08-july-2025 for header and footer image
                                $data[$cnt]['checklist_id']      = $chk_value['id'];
                                $data[$cnt]['check_list_name']   = $chk_value['check_list_name'];
                                $data[$cnt]['introduction_text'] = $chk_value['introduction_text'];
                                $data[$cnt]['final_name']        = $chk_value['final_name'];
                                 //Roshani added this code on 08-july-2025 for header and footer image
                                $data[$cnt]['header_image']        = $chk_value['header_image'];
                                $data[$cnt]['footer_image']        = $chk_value['footer_image'];
                                $data[$cnt]['header_image_path']     = (isset($chk_value['header_image']) && !empty($chk_value['header_image']))?$header_image_path:'' ;
                                $data[$cnt]['footer_image_path']        = (isset($chk_value['footer_image']) && !empty($chk_value['footer_image']))?$footer_image_path:'' ;
                                //Roshani added this code on 08-july-2025 for header and footer image
                                $getHEading = self::getHeadingDetails($chk_value['id']);
                                $data[$cnt]['heading'] = $getHEading;

                                $cnt++;

                            }
                        }
                    }
                }
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                $data_collection  = $data;

                Log::info("in getAllGeneralDocument api function response");
                Log::info($data_collection);

                $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                self::_createLog('getAllGeneralDocument',array($data_collection),'info');
            }
        }
        catch(\Exception $e)
        {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getAllGeneralDocument',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data_collection,$errors,$status);
    }

    public function generateGeneralCheckListPdf(Request $request)
    {

        Log::info("in generateGeneralCheckListPdf api function");
        Log::info($request->all());

        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $inputdata  = $request->all();

        try
        {
            // log::info("generateGeneralCheckListPdf");
            // log::info($inputdata);
            $collection = self::_createGeneralPdf($inputdata);

            $status        = true;
            $message       = __('api.DATA_FOUND_SUCCESS');
            //$data['date']  = $inputdata;
            $data['date']  = 'PDF generated successfully.';

            $status  = true;
            $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
            self::_createLog('examinationsChecklistPdf',array($data),'info');
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getProfileExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

       return self::_sendResult($message,$data,$errors,$status);
    }

    // Table API
    public function getPatientGeneralCheckListQrcode(Request $request)
    {



        $data = $dataFinal = [];
        $cnt = 0;
        $file_name = '';
        $inputdata = $request->all();
        $data_final = [];
        $errors     = [];

        $errors     = [];
        $status     = false;



        try
        {

            //dd($inputdata['patient_id'],$inputdata['appointment_id']);

             //commented on 29-nov-24 for signdocapp unread flow
            /*$collections = $this->CheckListHasSelectedQuestionModel
                            ->where('fk_patient_id',$inputdata['patient_id'])
                            //->where('fk_appointment_id',$inputdata['appointment_id'])
                            ->where('type','general')
                           // ->where('status','1') //reverted on 28-oct-24 //commented on 18-nov-24 for 187 new flow
                           //->where('status','0') //commented on 28-oct-24 for revert changes
                            //->orWhere('status','0')
                            //->where('check_list_flag',$request->check_list_flag) chk
                            ->get(); */

            $appointment_id = $request->appointment_id;
            $getSpecialistId = null;
            if (!empty($appointment_id)) {
                    $getSpecialistId = $this->AppointmentModel
                                        ->with('appointmentType')
                                        ->find($appointment_id)
                                        ->appointmentType
                                        ->fk_specialist_id ?? null;

                }
            //did changes for signdocapp unread flow on 29-nov-24
            $collections = $this->CheckListHasSelectedQuestionModel
                             ->join('examinations_check_list', 'examinations_check_list.id', '=', 'check_list_has_selected_questions.fk_check_list_id')
                            ->where('fk_patient_id',$inputdata['patient_id'])
                           ->where(function ($query) use ($getSpecialistId) {
                                $query->where(function ($subQuery) use ($getSpecialistId) {
                                    // Condition 1: signDoc is 'sign' and status not equal to '1,2'
                                    $subQuery->where('examinations_check_list.signDoc', 'sign')
                                             ->where('check_list_has_selected_questions.status', '!=', '1,2');
                                     if (!empty($getSpecialistId)) {

                                            $subQuery->where('examinations_check_list.fk_specialist_id', $getSpecialistId);
                                        }
                                })
                                ->orWhere(function ($subQuery) {
                                    // Condition 2: status is '0' (unread)
                                    $subQuery->where('check_list_has_selected_questions.status', '0');
                                });
                            }) // Added on 29-nov-24 for signdoc unread flow
                            ->where('type','general')
                            ->get();

            // dump($collections);

            if(!empty($collections))
            {



                $cnttt = 0;
                $cnt = 0;
                foreach ($collections as $key => $collections)
                {
                    //dd($collections['status']);
                    $myStatus = explode(',', $collections['status']);
                    if (!in_array('2', $myStatus))
                    {
                        if($collections['questions'])
                        {
                            //$data['check_list']        = json_decode($collections['questions'],true);
                            $check_list        = json_decode($collections['questions'],true);

                            if($check_list)
                            {
                                foreach ($check_list as $ck => $cval)
                                {
                                    $getcheckList = $this->CheckListModel
                                                     ->where('signDoc','sign')//#187 on 7-oct-24 //reverted on 28-oct-24
                                                    ->find($cval['checklist_id']);
                                    if(!empty($getcheckList))
                                    {
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

                                        $data[$cnt]['introduction_text'] = html_entity_decode($cval['introduction_text']);
                                        $data[$cnt]['final_name']        = html_entity_decode($cval['final_name']);
                                        //Roshani added this code on 10-july-2025 for header and footer image
                                        $data[$cnt]['header_image']        = $cval['header_image'];
                                        if (!empty($cval['header_image_path']) && $cval['header_image_path'] != null) {
                                            $relativePath = str_replace(url('/'), '', $cval['header_image_path']);
                                            $localPath = public_path($relativePath);
                                            if (file_exists($localPath) && is_file($localPath)) {
                                                // $data[$cnt]['header_image_exists'] = true;
                                                $data[$cnt]['header_image_path'] = $cval['header_image_path'];
                                            } else {
                                                $data[$cnt]['header_image_path'] = '';
                                                // $data[$cnt]['header_image_exists'] = false;
                                            }
                                        } else {
                                            // $data[$cnt]['header_image_exists'] = false;
                                            $data[$cnt]['header_image_path'] = '';
                                        }
                                        $data[$cnt]['footer_image']        = $cval['footer_image'];
                                        if (!empty($cval['footer_image_path']) && $cval['footer_image_path'] != null) {
                                            $relativePath = str_replace(url('/'), '', $cval['footer_image_path']);
                                            $localPath = public_path($relativePath);
                                            if (file_exists($localPath) && is_file($localPath)) {
                                                // $data[$cnt]['header_image_exists'] = true;
                                                $data[$cnt]['footer_image_path'] = $cval['footer_image_path'];
                                            } else {
                                                $data[$cnt]['footer_image_path'] = '';
                                                // $data[$cnt]['header_image_exists'] = false;
                                            }
                                        } else {
                                            // $data[$cnt]['header_image_exists'] = false;
                                            $data[$cnt]['footer_image_path'] = '';
                                        }
                                            //Roshani added this code on 10-july-2025 for header and footer image
                                        // changes by vijay 11/9/24 #187
                                        //$data[$cnt]['signDoc']  = $getcheckList->signDoc;
                                        $data[$cnt]['signDoc'] = $getcheckList->signDoc == 'sign' ? $getcheckList->signDoc : '';

                                        //end

                                        //
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
                                               //dd($value['question']['fk_heading_id']);
                                                $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_chk_id'] = $heading['fk_chk_id'];
                                                $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $value['question']['fk_heading_id'];
                                                $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $value['question']['question_id'];
                                                $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $value['question']['question'];
                                                $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']     = $value['question']['flag'];
                                                $k++;
                                            }
                                            $j++;
                                        }
                                        $cnt++;
                                    }
                                    else
                                    {
                                        $data_final =[];
                                    }
                                }

                                //$data_final[] = $data;
                                //$data = [];
                            }

                        }
                        else
                        {
                           $data_final =[];
                        }

                    }

                }
            }
            //dd($data);
            $status        = true;
            $message       = __('api.DATA_FOUND_SUCCESS');
            //$data[]  = $data_final;

            self::_createLog('getPatientCheckList',array($data),'info');
        }
        catch(\Exception $e) {


            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getPatientCheckList',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

       return self::_sendResult($message,$data,$errors,$status);

        return $data;
    }

      public function getAllGeneralDocumentQRCode1(Request $request)
    {
        $errors     = [];
        $data       = $data_collection =$data1       = [];

        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $patient_id  = $request->patient_id;
        $appointment_id  = $request->appointment_id;
        $type        = $request->type;
        $inputdata  = $request->all();
        $patient_id  = $inputdata['patient_id'];
        $type        = $inputdata['type'];
        // $fromSignDoc = $inputdata['fromSignDoc'];
        $fromSignDoc = isset($inputdata['fromSignDoc'])?$inputdata['fromSignDoc']:0;

        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              'type'        => 'required',
                            ],
                            [
                              'patient_id.required' => __('api.ERR_PATIENT_ID_REQ'),
                              'type.required'       => __('api.AUTH_PATIENT_GENERAL_TYPE'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }
            else
            {
                $getcheckList = $this->CheckListModel
                                ->where('type_of_checklist','general')
                                ->where('status',1)
                                ->get();
                //dd($getcheckList);
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
                                if($type == 'check_list')
                                {
                                    $chk_id = $hasDocument->fk_check_list_id;

                                    $check_list        = json_decode($hasDocument->questions,true);//added on 24-sept-24

                                    $chkList = $this->CheckListModel
                                                ->where('status',1)
                                                ->find($chk_id);

                                    if(!empty($chkList))
                                    {
                                        // added by vijay 10/9/24 (if condition is added else part is existing)

                                         //if condition commented on 3-dec-24 for signdoc unread flow
                                        // if($hasDocument->status == 0){
                                         if($hasDocument->status == 0 && ($fromSignDoc==1 || $fromSignDoc==0)){
                                             //condition added on 3-dec-24 for signdoc unread flow
                                            $data[$cnt]['singDoc'] = $chkList->signDoc;
                                            $data[$cnt]['checklist_id'] = $chkList->id;
                                            $data[$cnt]['check_list_name'] = $chkList->check_list_name;
                                            $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                            $data[$cnt]['final_name'] = $chkList->final_name;

                                            //commented below 2 lines on 24-sept-24
                                            // $getHEading = self::getHeadingDetails($chkList->id);
                                            // $data[$cnt]['heading'] = $getHEading;


                                             /***start added on 24-sept-24********************/
                                            foreach ($check_list as $ck => $cval)
                                            {
                                                    $j = 0;
                                                    foreach ($cval['heading'] as $heading)
                                                    {
                                                        //dd($heading['question']);
                                                        //check list heading
                                                        $data[$cnt]['heading'][$j]['checklist_id']= $heading['fk_chk_id'];
                                                        $data[$cnt]['heading'][$j]['heading_id']= $heading['heading_id'];
                                                        $data[$cnt]['heading'][$j]['heading']  = $heading['heading'];

                                                         $k=0;

                                                        foreach ($heading['question'] as $key => $value)
                                                        {
                                                            //check list question
                                                           //dd($value['question']['fk_heading_id']);
                                                            $data[$cnt]['heading'][$j]['question'][$k]['checklist_id'] = $heading['fk_chk_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['heading_id'] = $value['question']['fk_heading_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['question_id'] = $value['question']['question_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['question'] = $value['question']['question'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['flag']  = $value['question']['flag'];
                                                            $k++;
                                                        }
                                                        $j++;
                                                    }//foreach
                                            }//foreach
                                             /*******end added on 24-sept-24*******************/


                                            $cnt++;
                                        }
                                        else if($chkList->signDoc=='sign' && $hasDocument->status!= '1,2' && $fromSignDoc==1)
                                        {

                                            $data[$cnt]['singDoc'] = $chkList->signDoc;
                                            $data[$cnt]['checklist_id'] = $chkList->id;
                                            $data[$cnt]['check_list_name'] = $chkList->check_list_name;
                                            $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                            $data[$cnt]['final_name'] = $chkList->final_name;
                                            foreach ($check_list as $ck => $cval)
                                            {
                                                    $j = 0;
                                                    foreach ($cval['heading'] as $heading)
                                                    {
                                                        //dd($heading['question']);
                                                        //check list heading
                                                        $data[$cnt]['heading'][$j]['checklist_id']= $heading['fk_chk_id'];
                                                        $data[$cnt]['heading'][$j]['heading_id']= $heading['heading_id'];
                                                        $data[$cnt]['heading'][$j]['heading']  = $heading['heading'];

                                                         $k=0;

                                                        foreach ($heading['question'] as $key => $value)
                                                        {
                                                            //check list question
                                                           //dd($value['question']['fk_heading_id']);
                                                            $data[$cnt]['heading'][$j]['question'][$k]['checklist_id'] = $heading['fk_chk_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['heading_id'] = $value['question']['fk_heading_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['question_id'] = $value['question']['question_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['question'] = $value['question']['question'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['flag']  = $value['question']['flag'];
                                                            $k++;
                                                        }
                                                        $j++;
                                                    }//foreach
                                            }//foreach

                                        }//elseif condition added on 3-dec-24 for signdoc unread flow
                                        else{
                                            $l_date = self::checkFrequency($patient_id, $chkList, $hasDocument);
                                            //dump($l_date);
                                             if (isset($l_date) && !empty($l_date)) {
                                                //dump($cnt);
                                                $data[$cnt]['singDoc'] = $chkList->signDoc;
                                                $data[$cnt]['checklist_id'] = $chkList->id;
                                                $data[$cnt]['check_list_name'] = $chkList->check_list_name;
                                                $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                                $data[$cnt]['final_name'] = $chkList->final_name;

                                                 //commented below 2 lines on 24-sept-24
                                                // $getHEading = self::getHeadingDetails($chkList->id);
                                                // $data[$cnt]['heading'] = $getHEading;

                                                 /*******start added on 24-sept-24***********************/
                                                foreach ($check_list as $ck => $cval)
                                                {
                                                        $j = 0;
                                                        foreach ($cval['heading'] as $heading)
                                                        {
                                                            //dd($heading['question']);
                                                            //check list heading
                                                            $data[$cnt]['heading'][$j]['checklist_id']= $heading['fk_chk_id'];
                                                            $data[$cnt]['heading'][$j]['heading_id']= $heading['heading_id'];
                                                            $data[$cnt]['heading'][$j]['heading']  = $heading['heading'];

                                                             $k=0;

                                                            foreach ($heading['question'] as $key => $value)
                                                            {
                                                                //check list question
                                                               //dd($value['question']['fk_heading_id']);
                                                                $data[$cnt]['heading'][$j]['question'][$k]['checklist_id'] = $heading['fk_chk_id'];
                                                                $data[$cnt]['heading'][$j]['question'][$k]['heading_id'] = $value['question']['fk_heading_id'];
                                                                $data[$cnt]['heading'][$j]['question'][$k]['question_id'] = $value['question']['question_id'];
                                                                $data[$cnt]['heading'][$j]['question'][$k]['question'] = $value['question']['question'];
                                                                $data[$cnt]['heading'][$j]['question'][$k]['flag']  = $value['question']['flag'];
                                                                $k++;
                                                            }
                                                            $j++;
                                                        }//foreach
                                                }//foreach
                                                /*********end added on 24-sept-24*************/

                                                $cnt++;
                                            }
                                        }
                                        // end
                                    }
                                }
                            }
                            else
                            {
                                $data[$cnt]['singDoc']           = $chk_value['signDoc'];
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

                    // $data[$cnt]['singDoc']           = 'read';
                    //  $data[$cnt]['divs']           = 'test';
                    // $data[$cnt]['checklist_id']      = 26;
                    // $data[$cnt]['check_list_name']   = 'A-CL-L';
                    // $data[$cnt]['introduction_text'] = '<p>A-CL-L</p>';
                    // $data[$cnt]['final_name']        = '<p>A-CL-L</p>';
                    // $cnt++;
                }


                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                $data_collection  = $data;

                $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                self::_createLog('getAllGeneralDocument',array($data_collection),'info');
            }
        }
        catch(\Exception $e)
        {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getAllGeneralDocument',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data_collection,$errors,$status);
    }


      public function getAllGeneralDocumentQRCode(Request $request)
    {
        $errors     = [];
        $data       = $data_collection =$data1       = [];

        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $patient_id  = $request->patient_id;
        $appointment_id  = $request->appointment_id;
        $type        = $request->type;
        $inputdata  = $request->all();
        $patient_id  = $inputdata['patient_id'];
        $type        = $inputdata['type'];
        // $fromSignDoc = $inputdata['fromSignDoc'];
        $fromSignDoc = isset($inputdata['fromSignDoc'])?$inputdata['fromSignDoc']:0;

        try{

            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                              'type'        => 'required',
                            ],
                            [
                              'patient_id.required' => __('api.ERR_PATIENT_ID_REQ'),
                              'type.required'       => __('api.AUTH_PATIENT_GENERAL_TYPE'),
                            ]
                            );

            if ($validator->fails())
            {
              $errors[] = $validator->errors();
            }
            else
            {
                //Roshani made changes at 23-july-25
                $getcheckListQuery = $this->CheckListModel
                    ->where('type_of_checklist', 'general')
                    ->where('status', 1);
                if (!empty($appointment_id)) {
                    // $getSpecialistId = $this->AppointmentModel
                    //                     ->with('appointmentType')
                    //                     ->find($appointment_id)
                    //                     ->appointmentType
                    //                     ->fk_specialist_id ?? null;
                    $getSpecialistId = \DB::table('appointment as a')
                    ->leftJoin('appointment_types as at', 'a.appointment_type_id', '=', 'at.id')
                    ->where('a.id', $appointment_id)
                    ->select('at.fk_specialist_id')
                    ->first();
                    if ($getSpecialistId) {
                        $getcheckListQuery->where('fk_specialist_id', $getSpecialistId->fk_specialist_id);
                    }
                }

                $getcheckList = $getcheckListQuery->get();
                //Roshani made changes at 23-july-25
                
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
                                if($type == 'check_list')
                                {
                                    $chk_id = $hasDocument->fk_check_list_id;

                                    $check_list        = json_decode($hasDocument->questions,true);//added on 24-sept-24

                                    $chkList = $this->CheckListModel
                                                ->where('status',1)
                                                ->find($chk_id);

                                    if(!empty($chkList))
                                    {
                                        //Roshani added this code on 08-july-2025 for header and footer image

                                        $header_image_path    = self::getFilePath($chkList->header_image_path);
                                        $footer_image_path    = self::getFilePath($chkList->footer_image_path);

                                        //Roshani added this code on 08-july-2025 for header and footer image


                                        // added by vijay 10/9/24 (if condition is added else part is existing)

                                         //if condition commented on 3-dec-24 for signdoc unread flow
                                        // if($hasDocument->status == 0){
                                         if($hasDocument->status == 0 && ($fromSignDoc==1 || $fromSignDoc==0)){
                                             //condition added on 3-dec-24 for signdoc unread flow
                                            $data[$cnt]['singDoc'] = $chkList->signDoc;

                                            $data[$cnt]['checklist_id'] = $chkList->id;
                                            $data[$cnt]['check_list_name'] = $chkList->check_list_name;
                                            $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                            $data[$cnt]['final_name'] = $chkList->final_name;
                                            //Roshani added this code on 08-july-2025 for header and footer image
                                            $data[$cnt]['header_image']        = $chkList->header_image;
                                            $data[$cnt]['footer_image']        = $chkList->footer_image;
                                            $data[$cnt]['header_image_path']     = (isset($chkList->header_image) && !empty($chkList->header_image))?$header_image_path:'' ;
                                            $data[$cnt]['footer_image_path']        = (isset($chkList->footer_image) && !empty($chkList->footer_image))?$footer_image_path:'' ;
                                            //Roshani added this code on 08-july-2025 for header and footer image


                                            //commented below 2 lines on 24-sept-24
                                            // $getHEading = self::getHeadingDetails($chkList->id);
                                            // $data[$cnt]['heading'] = $getHEading;


                                             /***start added on 24-sept-24********************/
                                            foreach ($check_list as $ck => $cval)
                                            {
                                                    $j = 0;
                                                    foreach ($cval['heading'] as $heading)
                                                    {
                                                        //dd($heading['question']);
                                                        //check list heading
                                                        $data[$cnt]['heading'][$j]['checklist_id']= $heading['fk_chk_id'];
                                                        $data[$cnt]['heading'][$j]['heading_id']= $heading['heading_id'];
                                                        $data[$cnt]['heading'][$j]['heading']  = $heading['heading'];

                                                         $k=0;

                                                        foreach ($heading['question'] as $key => $value)
                                                        {
                                                            //check list question
                                                           //dd($value['question']['fk_heading_id']);
                                                            $data[$cnt]['heading'][$j]['question'][$k]['checklist_id'] = $heading['fk_chk_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['heading_id'] = $value['question']['fk_heading_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['question_id'] = $value['question']['question_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['question'] = $value['question']['question'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['flag']  = $value['question']['flag'];
                                                            $k++;
                                                        }
                                                        $j++;
                                                    }//foreach
                                            }//foreach
                                             /*******end added on 24-sept-24*******************/


                                            $cnt++;
                                        }
                                        else if($chkList->signDoc=='sign' && $hasDocument->status!= '1,2' && $fromSignDoc==1)
                                        {
                                            //Roshani added this code on 08-july-2025 for header and footer image

                                            $header_image_path    = self::getFilePath($chkList->header_image_path);
                                            $footer_image_path    = self::getFilePath($chkList->footer_image_path);

                                            //Roshani added this code on 08-july-2025 for header and footer image

                                            $data[$cnt]['singDoc'] = $chkList->signDoc;
                                            $data[$cnt]['checklist_id'] = $chkList->id;
                                            $data[$cnt]['check_list_name'] = $chkList->check_list_name;
                                            $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                            $data[$cnt]['final_name'] = $chkList->final_name;
                                            //Roshani added this code on 08-july-2025 for header and footer image
                                            $data[$cnt]['header_image']        = $chkList->header_image;
                                            $data[$cnt]['footer_image']        = $chkList->footer_image;
                                            $data[$cnt]['header_image_path']     = (isset($chkList->header_image) && !empty($chkList->header_image))?$header_image_path:'' ;
                                            $data[$cnt]['footer_image_path']        = (isset($chkList->footer_image) && !empty($chkList->footer_image))?$footer_image_path:'' ;
                                            //Roshani added this code on 08-july-2025 for header and footer image
                                            foreach ($check_list as $ck => $cval)
                                            {
                                                    $j = 0;
                                                    foreach ($cval['heading'] as $heading)
                                                    {
                                                        //dd($heading['question']);
                                                        //check list heading
                                                        $data[$cnt]['heading'][$j]['checklist_id']= $heading['fk_chk_id'];
                                                        $data[$cnt]['heading'][$j]['heading_id']= $heading['heading_id'];
                                                        $data[$cnt]['heading'][$j]['heading']  = $heading['heading'];

                                                         $k=0;

                                                        foreach ($heading['question'] as $key => $value)
                                                        {
                                                            //check list question
                                                           //dd($value['question']['fk_heading_id']);
                                                            $data[$cnt]['heading'][$j]['question'][$k]['checklist_id'] = $heading['fk_chk_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['heading_id'] = $value['question']['fk_heading_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['question_id'] = $value['question']['question_id'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['question'] = $value['question']['question'];
                                                            $data[$cnt]['heading'][$j]['question'][$k]['flag']  = $value['question']['flag'];
                                                            $k++;
                                                        }
                                                        $j++;
                                                    }//foreach
                                            }//foreach

                                        }//elseif condition added on 3-dec-24 for signdoc unread flow
                                        else{
                                            $l_date = self::checkFrequency($patient_id, $chkList, $hasDocument);
                                            //dump($l_date);
                                             if (isset($l_date) && !empty($l_date)) {

                                                //Roshani added this code on 08-july-2025 for header and footer image

                                                $header_image_path    = self::getFilePath($chkList->header_image_path);
                                                $footer_image_path    = self::getFilePath($chkList->footer_image_path);

                                                //Roshani added this code on 08-july-2025 for header and footer image
                                                //dump($cnt);
                                                $data[$cnt]['singDoc'] = $chkList->signDoc;
                                                $data[$cnt]['checklist_id'] = $chkList->id;
                                                $data[$cnt]['check_list_name'] = $chkList->check_list_name;
                                                $data[$cnt]['introduction_text'] = $chkList->introduction_text;
                                                $data[$cnt]['final_name'] = $chkList->final_name;
                                                //Roshani added this code on 08-july-2025 for header and footer image
                                                $data[$cnt]['header_image']        = $chkList->header_image;
                                                $data[$cnt]['footer_image']        = $chkList->footer_image;
                                                $data[$cnt]['header_image_path']     = (isset($chkList->header_image) && !empty($chkList->header_image))?$header_image_path:'' ;
                                                $data[$cnt]['footer_image_path']        = (isset($chkList->footer_image) && !empty($chkList->footer_image))?$footer_image_path:'' ;
                                                //Roshani added this code on 08-july-2025 for header and footer image
                                                 //commented below 2 lines on 24-sept-24
                                                // $getHEading = self::getHeadingDetails($chkList->id);
                                                // $data[$cnt]['heading'] = $getHEading;

                                                 /*******start added on 24-sept-24***********************/
                                                foreach ($check_list as $ck => $cval)
                                                {
                                                        $j = 0;
                                                        foreach ($cval['heading'] as $heading)
                                                        {
                                                            //dd($heading['question']);
                                                            //check list heading
                                                            $data[$cnt]['heading'][$j]['checklist_id']= $heading['fk_chk_id'];
                                                            $data[$cnt]['heading'][$j]['heading_id']= $heading['heading_id'];
                                                            $data[$cnt]['heading'][$j]['heading']  = $heading['heading'];

                                                             $k=0;

                                                            foreach ($heading['question'] as $key => $value)
                                                            {
                                                                //check list question
                                                               //dd($value['question']['fk_heading_id']);
                                                                $data[$cnt]['heading'][$j]['question'][$k]['checklist_id'] = $heading['fk_chk_id'];
                                                                $data[$cnt]['heading'][$j]['question'][$k]['heading_id'] = $value['question']['fk_heading_id'];
                                                                $data[$cnt]['heading'][$j]['question'][$k]['question_id'] = $value['question']['question_id'];
                                                                $data[$cnt]['heading'][$j]['question'][$k]['question'] = $value['question']['question'];
                                                                $data[$cnt]['heading'][$j]['question'][$k]['flag']  = $value['question']['flag'];
                                                                $k++;
                                                            }
                                                            $j++;
                                                        }//foreach
                                                }//foreach
                                                /*********end added on 24-sept-24*************/

                                                $cnt++;
                                            }
                                        }
                                        // end
                                    }
                                }
                            }
                            else
                            {
                                //Roshani added this code on 08-july-2025 for header and footer image

                                $header_image_path    = self::getFilePath($chk_value['header_image_path']);
                                $footer_image_path    = self::getFilePath($chk_value['footer_image_path']);

                                //Roshani added this code on 08-july-2025 for header and footer image

                                $data[$cnt]['singDoc']           = $chk_value['signDoc'];
                                $data[$cnt]['checklist_id']      = $chk_value['id'];
                                $data[$cnt]['check_list_name']   = $chk_value['check_list_name'];
                                $data[$cnt]['introduction_text'] = $chk_value['introduction_text'];
                                $data[$cnt]['final_name']        = $chk_value['final_name'];
                                $getHEading = self::getHeadingDetails($chk_value['id']);
                                $data[$cnt]['heading'] = $getHEading;

                                //Roshani added this code on 08-july-2025 for header and footer image

                                $data[$cnt]['header_image']    = $chk_value['header_image'];
                                $data[$cnt]['footer_image']     = $chk_value['footer_image'];
                                $data[$cnt]['header_image_path']        =(isset($chk_value['header_image']) && !empty($chk_value['header_image']))?$header_image_path:'' ;;
                                $data[$cnt]['footer_image_path']   = (isset($chk_value['footer_image']) && !empty($chk_value['footer_image']))?$footer_image_path:'' ;;
                                //Roshani added this code on 08-july-2025 for header and footer image

                                $cnt++;

                            }
                        }
                    }

                   
                }


                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                $data_collection  = $data;

                $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                self::_createLog('getAllGeneralDocument',array($data_collection),'info');
            }
        }
        catch(\Exception $e)
        {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getAllGeneralDocument',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data_collection,$errors,$status);
    }


    public function checkFrequency($patient_id,$getCheckList,$value)
    {
        $data   = [];
        $flag = 0;
        $l_date = '';
        $chk_activation_date = date('Y-m-d H:i:s',strtotime($getCheckList->date_of_last_activation));
        // ----------------------------------------------------------
        $current_date = date('Y-m-d H:i:s');
        $start_date   = Date('Y-m-d H:i:s',strtotime($value->activation_start_date));
        $end_date     = Date('Y-m-d H:i:s',strtotime($value->activation_last_date));
        //dd($end_date);
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
                    $last_date = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +".$duration." day");
                    $l_date    = Date('Y-m-d H:i:s',$last_date);
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
        //dd($data);
        return $data;
    }

    public function generateGeneralCheckListPdfQrcode(Request $request)
    {
       
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND');
        $status     = false;

        $inputdata  = $request->all();

        try
        {
            $collection = self::_createGeneralPdf($inputdata);
           
            $status        = true;
            $message       = __('api.DATA_FOUND_SUCCESS');
            //$data['date']  = $inputdata;
            $data['date']  = 'PDF generated successfully.';

            $status  = true;
            $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
            self::_createLog('examinationsChecklistPdf',array($data),'info');
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('generateGeneralCheckListPdfQrcode',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
        
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function _createGeneralPdf($inputdata)
    {
        Log::info("in _createGeneralPdf api function");
        Log::info($inputdata);


        $data = $dataFinal = [];
        $cnt = 0;
        $flag = '0';
        $file_name = '';
        foreach ($inputdata['check_list'] as $check_list)
        {
            $statusCondition = 0;

            foreach ($check_list['Heading'] as $heading) {
                foreach ($heading['questions'] as $questionData) {
                    foreach ($questionData['questions'] as $questionId => $value) {
                        if ($value == 1) {

                            $statusCondition = 1;
                            break 3;
                        }
                    }
                }
            }
            $collections = $this->CheckListModel
                            ->where('id',$check_list['checklist_id'])
                            //->where('status',$statusCondition) //commented on 10-march-25
                             ->where('status',1) //reverted on 10-marhc-25
                            ->first();
           
            if(!empty($collections))
            {
                //check list details
                if(isset($check_list['sign']) && $check_list['sign'] !=null)
                {
                    if(!empty(Config('ordination_id')))
                    {
                        $getDatabaseName = DB::connection('system')
                                    ->table("tenants")
                                    ->where('ordination_id',Config('ordination_id'))
                                    ->first(['uuid']);

                        $signPath = '/tenancy/tenants/'.$getDatabaseName->uuid.'/sign/';
                    }
                    else
                    {
                        $signPath = '/opt/app-shared/php/data/storage/app/public/sign/';
                    }
                    $flag = '1';
                    $file_data = $check_list['sign'];
                    $file_name = 'signature_' . time() . '.png'; //generating unique file name;

                    if ($file_data != "")
                    {
                        // storing image in storage/app/public Folder
                        Storage::disk('public')->put($signPath.$file_name, base64_decode($file_data));

                        //file_put_contents('img.png', base64_decode($file_data));
                        //chmod($file_name, 777);
                        $sign_path = self::getFilePath('/sign/'.$file_name);

                        // $data[$cnt]['signature'] = $file_name;
                        $data[$cnt]['signature'] = $sign_path;

                    }
                }
                else
                {
                    $data[$cnt]['signature']         = '';
                    $flag = '0';
                }

                $data[$cnt]['checklist_id']      = $collections->id;
                $data[$cnt]['check_list_name']   = $collections->check_list_name;
                $data[$cnt]['introduction_text'] = $collections->introduction_text;
                $data[$cnt]['final_name']        = $collections->final_name;
                $data[$cnt]['currentDate']        = date("m/d/Y");
                $patientFirstName = $patientLastName = "";
                $data[$cnt]['patientFullName']= $data[$cnt]['patientDob']= '';
                $getPatientDetails = $this->PatientsModel->where('id',$inputdata['patient_id'])->first();
                if(isset($getPatientDetails) && !empty($getPatientDetails))
                {
                    $patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
                    $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
                    $data[$cnt]['patientFullName'] = $patientFirstName.' '.$patientLastName;
                    $data[$cnt]['patientDob'] = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
                }//


                /*******Added by divya on 26-dec-22*********/
                    $checklistimagepath='';
                    if(!empty(Config('ordination_id')))
                    {
                        $getDatabaseName = DB::connection('system')
                                    ->table("tenants")
                                    ->where('ordination_id',Config('ordination_id'))
                                    ->first(['uuid']);

                        $checklistimagepath = url('storage/tenancy/tenants/'.$getDatabaseName->uuid);
                    }
                    else
                    {
                        $checklistimagepath = '/opt/app-shared/php/data/storage/app/public/';
                    }

                $data[$cnt]['header_image']        = isset($collections->header_image)?$collections->header_image:"";
                $data[$cnt]['header_image_path']   = isset($collections->header_image_path)?$checklistimagepath.$collections->header_image_path:"";
                $data[$cnt]['footer_image']        = isset($collections->footer_image)?$collections->footer_image:"";
                $data[$cnt]['footer_image_path']   = isset($collections->footer_image_path)?$checklistimagepath.$collections->footer_image_path:"";
                

                /*******Added by divya on 26-dec-22*********/



                $j = 0;
                foreach ($check_list['Heading'] as $heading)
                {
                    //check list heading
                    $heading_name = $this->CheckListHasHeadingSectionModel
                                    ->where('id',$heading['heading_id'])->first();
                    $data[$cnt]['heading'][$j]['fk_chk_id']= $collections->id;
                    $data[$cnt]['heading'][$j]['heading_id']= $heading_name['id'];
                    $data[$cnt]['heading'][$j]['heading']  = $heading_name['heading_section'];

                    ksort($heading['questions'][0]['questions']);
                    foreach ($heading['questions'][0] as $key => $value)
                    {
                        //check list question
                        $k=0;
                        foreach ($value as $keyv => $valque)
                        {
                            $question = $this->HeadingSectionHasQuestionModel
                                        ->where('id',$keyv)->first();

                            $data[$cnt]['heading'][$j]['question'][$k]['question']['fk_heading_id'] = $heading_name['id'];
                            $data[$cnt]['heading'][$j]['question'][$k]['question']['question_id'] = $question['id'];
                            $data[$cnt]['heading'][$j]['question'][$k]['question']['question'] = $question['question'];
                            $data[$cnt]['heading'][$j]['question'][$k]['question']['flag']     = $valque;

                            $k++;
                        }
                    }
                    $j++;
                }

                //$PdfPath = self::StorePath('check_list_pdf/');
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
                //dd($PdfPath);
                //$PdfPath   = storage_path().'/app/public/check_list_pdf/';
                //$PDFname   = $collections['check_list_name'].'_'.time().'.pdf';
                // $PDFname = str_replace(' ', '' , $collections['check_list_name']);
                // $PDFname   = trim($PDFname).'_'.time().'.pdf';
                $PDFname = self::createPdfFileName($inputdata['patient_id'],$collections['check_list_name']);
                // Invoice full path
                $StorePath = $PdfPath.$PDFname;
                //dd($StorePath);

               // Log::info($data);

                $accessPath = '/check_list_pdf/'.$PDFname;
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
                $pdf->loadView($PDFPath,compact('data'))->save($StorePath);
                // end

                //========================================================================
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
                //dump($data);
                //dd($inputdata['appointment_id']);
                $CheckListHasSelectedQuestionModel = $this->CheckListHasSelectedQuestionModel
                                                ->where('fk_patient_id',$inputdata['patient_id'])
                                                ->where('type','general')
                                                ->where('fk_check_list_id',$check_list['checklist_id'])
                                                ->first();
                //dump($CheckListHasSelectedQuestionModel);

                Log::info("in _createGeneralPdf api function CheckListHasSelectedQuestionModel fk_check_list_id");
                Log::info($check_list['checklist_id']);
                                        
                if(!empty($CheckListHasSelectedQuestionModel))
                {

                    Log::info("in _createGeneralPdf api function not empty CheckListHasSelectedQuestionModel");


                    $CheckListHasSelectedQuestionModel->fk_patient_id    = $inputdata['patient_id'];
                    $CheckListHasSelectedQuestionModel->fk_examination_id= '';
                    $CheckListHasSelectedQuestionModel->fk_appointment_id= $inputdata['appointment_id'];
                    $CheckListHasSelectedQuestionModel->fk_check_list_id = $check_list['checklist_id'];
                    $CheckListHasSelectedQuestionModel->questions        = json_encode($data);
                    $CheckListHasSelectedQuestionModel->created_at       = Date('Y-m-d');
                    $CheckListHasSelectedQuestionModel->check_list_flag  = $flag;
                    $CheckListHasSelectedQuestionModel->pdf_name         = $PDFname;
                    $CheckListHasSelectedQuestionModel->pdf_path         = $accessPath;
                    $CheckListHasSelectedQuestionModel->signature        = $file_name;
                    $CheckListHasSelectedQuestionModel->export_status    = 0;
                    $CheckListHasSelectedQuestionModel->type             = 'general';

                    if(!empty($data[$cnt]['signature']))
                    {

                        $CheckListHasSelectedQuestionModel->status         = '1,2';
                    }
                    else
                    {
                       // $CheckListHasSelectedQuestionModel->status         = '1'; //comented by vj for #187 //reverted on 28-oct-24 commented on 10-march-25

                        //added on 10-march-25
                        $statusVar = ($statusCondition==1)?1:$CheckListHasSelectedQuestionModel->status;
                        $CheckListHasSelectedQuestionModel->status           = $statusVar;




                        // added by vijay #187 if scan from signDoc then signDoc value get
                        // if (isset($inputdata['signDoc']) && !empty($inputdata['signDoc'])) {
                        //     $CheckListHasSelectedQuestionModel->status = '1';
                        // } else {
                        //     $CheckListHasSelectedQuestionModel->status = '0';
                        // }
                        // end commented on 28-oct-24 for revert changes

                    }
                    $CheckListHasSelectedQuestionModel->activation_start_date  = $start_date;
                    $CheckListHasSelectedQuestionModel->activation_last_date   = $end_date;

                    $CheckListHasSelectedQuestionModel->save();
                }
                else
                {

                   Log::info("in _createGeneralPdf api function empty CheckListHasSelectedQuestionModel");
                    
                    $CheckListHasSelectedQuestionModel = new $this->CheckListHasSelectedQuestionModel;

                    $CheckListHasSelectedQuestionModel->fk_patient_id    = $inputdata['patient_id'];
                    $CheckListHasSelectedQuestionModel->fk_appointment_id = $inputdata['appointment_id'];
                    $CheckListHasSelectedQuestionModel->fk_check_list_id = $check_list['checklist_id'];
                    $CheckListHasSelectedQuestionModel->questions        = json_encode($data);
                    $CheckListHasSelectedQuestionModel->created_at       = Date('Y-m-d');
                    $CheckListHasSelectedQuestionModel->check_list_flag  = $flag;
                    $CheckListHasSelectedQuestionModel->pdf_name         = $PDFname;
                    $CheckListHasSelectedQuestionModel->pdf_path         = $accessPath;
                    $CheckListHasSelectedQuestionModel->signature        = $file_name;
                    $CheckListHasSelectedQuestionModel->export_status    = 0;
                    $CheckListHasSelectedQuestionModel->type             = 'general';

                    if(!empty($data[$cnt]['signature']))
                    {
                        $CheckListHasSelectedQuestionModel->status         = '1,2';
                    }
                    else
                    {
                       // $CheckListHasSelectedQuestionModel->status         = '1'; //reverted on 28-oct-24 //commented on 10-march-25

                         $CheckListHasSelectedQuestionModel->status         = $statusCondition;//added on 10-march-25

                        //$CheckListHasSelectedQuestionModel->status         = '0'; //added by vijay 11/9/24 #187 //commented on 28-oct-24 for revert
                    }
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


      //Added by Swati Mam
    public function changeSMSStatus(Request $request)
    {
        $errors = [];
        $data = [];
        $is_available = 0;
        $getOrdination = [];
        $message = __('api.ERR_SOMETHING_WRONG');
        $status = false;
        $patientID=$request->patient_id;
        $sendSMS = $request->send_sms_status;
        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                                  'patient_id' => 'required','send_sms_status' => 'required',
                                ],
                                [
                                  'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ')
                                ]);
        if($validator->fails()) {
          $errors[] = $validator->errors();
        }else{
            try
            {
                $status = true;
                $PatientsModel = $this->PatientsModel->find($patientID);
                $PatientsModel->sendSMS = $request->send_sms_status;
                if($PatientsModel->save())
                {
                    $message = __('api.DATA_FOUND_SUCCESS');
                    self::_createLog('SMSStatusPatient',$message,'info');
                }
                else
                {
                    $message = __('api.ERR_NOT_FOUND');
                    self::_createLog('SMSStatusPatient',$message,'error');
                }
            }
            catch (Exception $e)
            {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                            "error" => __('api.ERR_SOMETHING_WRONG'),
                            "error_msg" => $e->getMessage(),
                                        ];
                self::_createLog('SMSStatusPatient',$errors,'error');
            }
        }
        return self::_sendResult($message,$data,$errors,$status);
    }
    public function changeMailStatus(Request $request)
    {
        $errors = [];
        $data = [];
        $is_available = 0;
        $getOrdination = [];
        $message = __('api.ERR_SOMETHING_WRONG');
        $status = false;
        $patientID=$request->patient_id;
        $sendMail = $request->send_mail_status;
        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                                  'patient_id' => 'required','send_mail_status' => 'required',
                                ],
                                [
                                  'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),
                                ]);
        if($validator->fails()) {
          $errors[] = $validator->errors();
        }else{
            try
            {
                $status = true;
                $PatientsModel = $this->PatientsModel->find($patientID);
                $PatientsModel->sendMail = $request->send_mail_status;
                if($PatientsModel->save())
                {
                    $message = __('api.DATA_FOUND_SUCCESS');
                    self::_createLog('SMSStatusPatient',$message,'info');
                }
                else
                {
                    $message = __('api.ERR_NOT_FOUND');
                    self::_createLog('SMSStatusPatient',$message,'error');
                }
            }
            catch (Exception $e)
            {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                            "error" => __('api.ERR_SOMETHING_WRONG'),
                            "error_msg" => $e->getMessage(),
                                        ];
                self::_createLog('SMSStatusPatient',$errors,'error');
            }
        }
        return self::_sendResult($message,$data,$errors,$status);
    }

    public function getPateintSettings(Request $request)
    {
        $errors = [];
        $data = [];
        $is_available = 0;
        $getOrdination = [];
        $message = __('api.ERR_SOMETHING_WRONG');
        $status = false;
        $patientID=$request->patient_id;
        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                                  'patient_id' => 'required',
                                ],
                                [
                                  'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),
                                ]);
        if($validator->fails()) {
          $errors[] = $validator->errors();
        }else{
            try
            {
                $status = true;
                $PatientsModel = $this->PatientsModel->select('sendMail','sendSMS')->find($patientID);
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                $data  = $PatientsModel;
                self::_createLog('getPatientSettings',array($data),'info');
            }
            catch (Exception $e)
            {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                            "error" => __('api.ERR_SOMETHING_WRONG'),
                            "error_msg" => $e->getMessage(),
                                        ];
                self::_createLog('getPatientSettings',$errors,'error');
            }
        }
        return self::_sendResult($message,$data,$errors,$status);
    }


     //Added on 30-spet-34 #187
     public function getExaminationsCheckListQRCodeUnread($id,$appointment_id,$patient_id)
    {
        // Initialize $collections to avoid "Undefined variable" error
        $collections = [];

        try
        {

            //get specialist id on appointment id
           
            $examinationsDetails = $this->BaseModel->where('id',$id)->first();

            //uncommented below code on 11-dec-24 checklist not shown for vertun

            //Roshani added this changes on 23-07-2025
            $collections = $this->ExaminationsHasMultipleCheckListModel
                           ->where('fk_examinations_id',$id)
                           ->with(['getChecklistQR.getHEadingSectionQR.getQuestionQR']);
                           

             if (!empty($appointment_id)) {
                    $getSpecialistId = $this->AppointmentModel
                                        ->with('appointmentType')
                                        ->find($appointment_id)
                                        ->appointmentType
                                        ->fk_specialist_id ?? null;

                    if ($getSpecialistId) {
                        $collections->where('fk_specialist_id', $getSpecialistId);
                    }
                }

            $collections = $collections->get();
            //Roshani added this changes on 23-07-2025

            //commented below code on 11-dec-24 for checklist not shown issue
            /*$collections = $this->ExaminationsHasMultipleCheckListModel
                ->where('fk_examinations_id', $id)
                ->whereHas('getChecklistQR.checklistHasSelectedQuestions', function($query) use ($patient_id, $appointment_id, $id) {
                    $query->where('fk_patient_id', $patient_id)
                          ->where('fk_appointment_id', $appointment_id)
                          ->where('fk_examination_id', $id)
                          ->where('status','0');
                })
                ->with(['getChecklistQR.getHEadingSectionQR.getQuestionQR'])
                ->get();*/


           // dump($collections);





        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return $collections;

    }//

    //added on 18-nov-24 for 187 new flow
     public function getExaminationsCheckListSingQRCodeBackend($id,$appointment_id,$patient_id)
    {
        try
        {
            $examinationsDetails = $this->BaseModel->where('id',$id)->first();
            //commented on 29-nov-24 for signdoc app unread flow
            /*$collections = $this->ExaminationsHasMultipleCheckListModel
                           ->where('fk_examinations_id',$id)
                           ->whereHas('getChecklistSingQR.checklistHasSelectedQuestions', function($query) use ($patient_id, $appointment_id, $id) {
                                $query->where('fk_patient_id', $patient_id)
                                      ->where('fk_appointment_id', $appointment_id)
                                      ->where('fk_examination_id', $id)
                                      ->where('check_list_has_selected_questions.status','!=','1,2');//added on 19-nov-24
                                     // ->where('status','0');
                            })
                           ->with(['getChecklistSingQR.getHEadingSectionQR.getQuestionQR'])
                           ->get();*/
            // $appointment_id = $request->appointment_id;
            $getSpecialistId = null;
            if (!empty($appointment_id)) {
                    $getSpecialistId = $this->AppointmentModel
                                        ->with('appointmentType')
                                        ->find($appointment_id)
                                        ->appointmentType
                                        ->fk_specialist_id ?? null;

                }

           //added on 29-nov-24 for signdoc app unread flow
            $collections = $this->ExaminationsHasMultipleCheckListModel
                           ->where('fk_examinations_id',$id)
                           ->whereHas('getChecklistQR.checklistHasSelectedQuestions', function($query) use ($patient_id, $appointment_id, $id, $getSpecialistId) {
                                $query->where('fk_patient_id', $patient_id)
                                      ->where('fk_appointment_id', $appointment_id)
                                      ->where('fk_examination_id', $id)
                                      ->where('examinations_check_list.fk_specialist_id', $getSpecialistId)
                                     // ->where('check_list_has_selected_questions.status','!=','1,2');//added on 19-nov-24
                                    //   ->where(function ($query) use ($getSpecialistId) {
                                      ->where(function ($query) {
                                          $query->where(function ($subQuery) {
                                              // Condition 1: signDoc is 'sign' and status not equal to '1,2'
                                              $subQuery->where('examinations_check_list.signDoc', 'sign')
                                                       ->where('check_list_has_selected_questions.status', '!=', '1,2');
                                          })
                                          ->orWhere(function ($subQuery) {
                                              // Condition 2: status is '0' (unread)
                                              $subQuery->where('check_list_has_selected_questions.status', '0');
                                          });
                                      }); // Added on 29-nov-24 for signdoc unread flow
                            })
                           ->with(['getChecklistQR.getHEadingSectionQR.getQuestionQR'])
                           ->get();


        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getExaminations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return $collections;

    }// getExaminationsCheckListSingQRCodeBackend
}
