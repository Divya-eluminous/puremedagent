<?php

namespace App\Http\Controllers\Api\v2;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MenusSettingsModel;
use App\Models\SettingsModel;
use App\Models\AppointmentTypesModel;
use App\Models\MenstruationCycleModel; 
use App\Models\ActivityLogModel;
use App\Models\BeaconsModel;
use App\Models\SpecialistModel;
use App\Models\ExaminationsModel;
use App\Models\MenstruationCycleHasCyclesModel; 
use App\Models\MenstruationCycleHasCalendarModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use Validator;
use Carbon\Carbon; 
use DB;
use App\Traits\GeneralTrait;
use App\Models\UserHasAppointmentType;

class SettingsController extends BaseController
{
    use GeneralTrait;
	public function __construct(
        MenusSettingsModel $MenusSettingsModel,
        SettingsModel $SettingsModel,
        AppointmentTypesModel $AppointmentTypesModel,
        MenstruationCycleModel $MenstruationCycleModel,
        ActivityLogModel $ActivityLogModel,
        MenstruationCycleHasCyclesModel $MenstruationCycleHasCyclesModel,
        MenstruationCycleHasCalendarModel $MenstruationCycleHasCalendarModel,
        BeaconsModel $BeaconsModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        UserHasAppointmentType $UserHasAppointmentType

    )
    {
        $this->BaseModel                = $SettingsModel;
        $this->MenusSettingsModel            = $MenusSettingsModel;
        $this->AppointmentTypesModel    = $AppointmentTypesModel;
        $this->MenstruationCycleModel   = $MenstruationCycleModel;
        $this->ActivityLogModel     = $ActivityLogModel;
        $this->MenstruationCycleHasCyclesModel   = $MenstruationCycleHasCyclesModel;
        $this->MenstruationCycleHasCalendarModel = $MenstruationCycleHasCalendarModel; 
        $this->BeaconsModel = $BeaconsModel; 
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->UserHasAppointmentType = $UserHasAppointmentType;
        

        // $this->ViewData = [];
        // $this->JsonData = []; 

        // $this->ModuleTitle = 'Patients';
        // $this->ModuleView  = 'admin.patients.';
        // $this->ModulePath = 'admin.patients.';
    } 

    /*---------------------------------
    |   Settings Listing
    ------------------------------------------*/
    public function getSettingSalutations(){
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;

        try{
            $collections = collect([]);   
            $collections = $this->BaseModel
                                ->where('setting_key','=','APP_LOGGED_IN_SALUTATION_TEXT')
                                ->orWhere('setting_key','=','APP_LOGGED_OUT_SALUTATION_TEXT')
                                ->orWhere('setting_key','=','HOSPITAL_LATITUDE')
                                ->orWhere('setting_key','=','HOSPITAL_LONGITUDE')
                                ->orWhere('setting_key','=','HOSPITAL_DISTANCE')
                                ->whereStatus(1)
                                ->get();

            // print_r($collections->toArray());
            // exit();

            if((!empty($collections) && sizeof($collections) > 0)){
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');

                if(!empty($collections)){

                    foreach ($collections as $key=>$collection) {

                        if($collection->setting_key=="APP_LOGGED_IN_SALUTATION_TEXT"){
                            $data[$key]['logged_in'] = array();
                            $data[$key]['logged_in'][0]['id']  = $collection->id;
                            $data[$key]['logged_in'][0]['setting_key']  = $collection->setting_key;
                            $data[$key]['logged_in'][0]['setting_value']  = $collection->setting_value;
                            $data[$key]['logged_in'][0]['description']  = $collection->description;
                            $data[$key]['logged_in'][0]['status']  = $collection->status;
                        }elseif($collection->setting_key=="APP_LOGGED_OUT_SALUTATION_TEXT"){
                            $data[$key]['logged_out'] = array();
                            $data[$key]['logged_out'][0]['id']  = $collection->id;
                            $data[$key]['logged_out'][0]['setting_key']  = $collection->setting_key;
                            $data[$key]['logged_out'][0]['setting_value']  = $collection->setting_value;
                            $data[$key]['logged_out'][0]['description']  = $collection->description;
                            $data[$key]['logged_out'][0]['status']  = $collection->status;
                        }elseif($collection->setting_key=="HOSPITAL_LATITUDE"){
                            $data[$key]['lat'] = array();
                            $data[$key]['lat'][0]['id']             = $collection->id;
                            $data[$key]['lat'][0]['setting_key']    = $collection->setting_key;
                            $data[$key]['lat'][0]['setting_value']  = $collection->setting_value;
                            $data[$key]['lat'][0]['description']    = $collection->description;
                            $data[$key]['lat'][0]['status']         = $collection->status;
                        }elseif($collection->setting_key=="HOSPITAL_LONGITUDE"){
                            $data[$key]['lon'] = array();
                            $data[$key]['lon'][0]['id']             = $collection->id;
                            $data[$key]['lon'][0]['setting_key']    = $collection->setting_key;
                            $data[$key]['lon'][0]['setting_value']  = $collection->setting_value;
                            $data[$key]['lon'][0]['description']    = $collection->description;
                            $data[$key]['lon'][0]['status']         = $collection->status;
                        }elseif($collection->setting_key=="HOSPITAL_DISTANCE"){
                            $data[$key]['distance'] = array();
                            $data[$key]['distance'][0]['id']            = $collection->id;
                            $data[$key]['distance'][0]['setting_key']   = $collection->setting_key;
                            $data[$key]['distance'][0]['setting_value'] = $collection->setting_value;
                            $data[$key]['distance'][0]['description']   = $collection->description;
                            $data[$key]['distance'][0]['status']        = $collection->status;
                        }
                       
                    }
                }
               // $data  = $collection; 
                //self::_createLog('getSettingSalutations',array($data),'info');

            }else{
                $message = __('api.ERR_NOT_FOUND');
            } 
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getSettingSalutations',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getSettingLoggedImages(){

        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;

        try{
            $collections = collect([]);   
            $collections = $this->BaseModel
                                ->where('setting_key','=','APP_LOGGED_IN_IMAGE_LINK')
                                ->whereStatus(1)
                                ->get();

            if((!empty($collections) && sizeof($collections) > 0)){
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');

                if(!empty($collections)){

                    foreach ($collections as $key=>$collection) {

                            $logged_out_images = explode("||", $collection->setting_value);

                            foreach ($logged_out_images as $image_key=>$logged_out_image) {
                                $data[$image_key]['id']  = $collection->id;
                                $data[$image_key]['setting_key']  = $collection->setting_key;
                                $data[$image_key]['image']  = $logged_out_image;
                                $data[$image_key]['description']  = $collection->description;
                                $data[$image_key]['status']  = $collection->status;
                            }
                       
                       
                    }
                }
                //$data  = $collection; 
                //self::_createLog('getSettingLoggedImages',array($data),'info');

            }else{
                $message = __('api.ERR_NOT_FOUND');
            } 
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getSettingLoggedImages',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       
       return self::_sendResult($message,$data,$errors,$status);
        
    }

    public function getSettingLogoutImages(){

        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;

        try{
            $collections = collect([]);   
            $collections = $this->BaseModel
                                ->where('setting_key','=','APP_LOGGED_OUT_IMAGE_LINK')
                                ->whereStatus(1)
                                ->get();

            if((!empty($collections) && sizeof($collections) > 0)){
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');

                if(!empty($collections)){

                    foreach ($collections as $key=>$collection) {

                            $logged_out_images = explode("||", $collection->setting_value);

                            foreach ($logged_out_images as $image_key=>$logged_out_image) {
                                $data[$image_key]['id']  = $collection->id;
                                $data[$image_key]['setting_key']  = $collection->setting_key;
                                $data[$image_key]['image']  = $logged_out_image;
                                $data[$image_key]['description']  = $collection->description;
                                $data[$image_key]['status']  = $collection->status;
                            }
                       
                       
                    }
                }
               // $data  = $collection; 
                //self::_createLog('getSettingLogoutImages',array($data),'info');

            }else{
                $message = __('api.ERR_NOT_FOUND');
            } 
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getSettingLogoutImages',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       
       return self::_sendResult($message,$data,$errors,$status);

    }

    public function getSettingBeacons(){
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;

        try{
            $collections = collect([]);   
            $collections = $this->BeaconsModel 
                                ->where('status','1')                               
                                ->get();   

            if((!empty($collections) && sizeof($collections) > 0)){
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                $data  = $collections; 
                self::_createLog('getSettingBeacons',array($data),'info');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');

            }else{
                $message = __('api.ERR_NOT_FOUND');
            }        
            
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getSettingBeacons',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }
    

    public function getMenuSettings(){ 
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;

        try{
            $collection = collect([]);   
            $collection = DB::
                          table("menus_settings")          
                          ->where('status',1)
                          ->get();  
         
            if((!empty($collection) && sizeof($collection) > 0)){
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                $data  = $collection; 
                self::_createLog('getMenuSettings',array($data),'info');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');

            }else{
                $message = __('api.ERR_NOT_FOUND');
            } 
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getMenuSettings',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }
       
       return self::_sendResult($message,$data,$errors,$status);
    }

    /*---------------------------------
    |   Appointment Listing
    ------------------------------------------*/
    public function getAppointmentTypes(Request $request){

        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;
        $exam_ids       =  $request->exam_ids;
        try{
            $collection = collect([]); 

            if(!empty($exam_ids) && strlen($exam_ids)>0)
            {
                $exam_ids = explode(",", $exam_ids);
             
                $collection = $this->AppointmentTypeHasExaminationsModel
                              ->select('appointment_types.*')
                              ->join('appointment_types','appointment_types.id','appoinment_type_has_examinations.appoinment_id')
                              ->join('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                               ->whereIn('appoinment_type_has_examinations.examination_id',$exam_ids)
                              ->where('appointment_types.status',1)
                              ->groupBy('appointment_types.id');

                              //############## Roshani hide this code (13/03/2024) C) User settings ################ -->

                              // ->get();
            }
            else
            {
                $collection = $this->AppointmentTypesModel->whereStatus(1)->where('dynamic_appointment', 0);
                //############## Roshani hide this code (13/03/2024) C) User settings ################ -->

                // ->get();
            }
            //############## Roshani Added this code (27/02/2024) C) User settings ################ -->
            // dump($collection);
            if(isset($request->doctor_id) && !empty($request->doctor_id) && !empty($collection))
            {
                $doctorId = $request->doctor_id;
                $doctorHasAppointmentTypes = $this->UserHasAppointmentType::where('user_id', $doctorId)->pluck('appointment_type_id')->toArray();
                $collection = $collection->whereNotIn('appointment_types.id', $doctorHasAppointmentTypes);
                // dd($collection);
            }
            // ############## Roshani Added this code (27/02/2024) C) User settings ################ -->
            //############## Roshani added this code (13/03/2024) C) User settings ################ -->

            $collection = $collection->get();
            //############## Roshani added this code (13/03/2024) C) User settings ################ -->
            if((!empty($collection) && sizeof($collection) > 0)){
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                $data  = $collection;
                self::_createLog('getAppointmentTypes',array($data),'info');

            }else{
                $message = __('api.ERR_NOT_FOUND');
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getAppointmentTypes',$errors,'error');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getAppointmentTypesForDynamic(Request $request){
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;
        $exam_ids       =  $request->exam_ids;
        try{
            $exam_ids = explode(",", $exam_ids);
            $getAppointments = new ExaminationsModel();
            $getAppointments = $getAppointments->select(DB::raw("GROUP_CONCAT(name order by name ASC SEPARATOR ' + ') as services"))->whereIn('id',$exam_ids)->get();
            if((!empty($getAppointments) && sizeof($getAppointments) > 0) && $getAppointments[0]->services!=""){
               $name=trim($getAppointments[0]->services);
               $getSameAppointmentsType = new AppointmentTypesModel();
               $getSameAppointmentsType = $getSameAppointmentsType->where('name', 'LIKE', ''.$name.'')->first();
               if(empty($getSameAppointmentsType)){
                    $getSpecialist = new SpecialistModel();
                    $getSpecialist = $getSpecialist->get();
                    $collection=new AppointmentTypesModel;
                    $collection->fk_specialist_id = empty($getSpecialist) ? "" :$getSpecialist[0]->id;
                    $collection->name            = $name;
                    $collection->duration        = 10;
                    //$collection->description     = $request->description;//$collection->recommend_exams = 0;
                    $collection->status          = 1;
                    $collection->dynamic_appointment = 1;
                    if($collection->save())
                    {
                        $getServices = new ExaminationsModel();
                        $getServices = $getServices->whereIn('id',$exam_ids)->get();
                        if(!empty($getServices)){
                            foreach($getServices as $services){
                                $AppointmentHasExaminationsModel = new $this->AppointmentTypeHasExaminationsModel;
                                $AppointmentHasExaminationsModel->appoinment_id    = $collection->id;
                                $AppointmentHasExaminationsModel->examination_id   = $services->id;
                                $AppointmentHasExaminationsModel->fk_specialist_id = $services->fk_specialist_id;
                                $AppointmentHasExaminationsModel->save();
                            }
                        }
                    }
                    $data  = $collection;
                }
                else $data  = $getSameAppointmentsType;
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                self::_createLog('getAppointmentTypes',array($data),'info');
            }else{
                $message = __('api.ERR_NOT_FOUND');
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('getAppointmentTypes',$errors,'error');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }
    /*---------------------------------
    |   Create an Menstruation Cycle 
    ------------------------------------------*/
    public function menstruationCycleCreate(Request $request)
    {
        $errors     = []; 
        $data       = [];
        $message    = __('api.ERR_INVALID_DATA');
        $status     = false;

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'latest_date'     => 'required',
                          'latest_length'   => 'required',
                          'patient_id'      => 'required',
                        ], 
                        [
                          'latest_date.required' => __('api.ERR_LATEST_DATE_REQ'), 
                          'latest_length.required' => __('api.ERR_LATEST_LENGTH_REQ'),    
                          'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) {           
          $errors[] = $validator->errors(); 
        }else{
         try {  

                DB::beginTransaction();   
                $all_transactions = [];

                $status = true;
                $patient_id     = $request->patient_id;
                $latest_date    = date('Y-m-d', strtotime($request->latest_date));
                $latest_length  = $request->latest_length;

                //Step 1: Check if the patient has record in table, if have then update its record in menstruation_cycle table or insert new record
                $checRecordExist = $this->MenstruationCycleModel
                                        ->where('patient_id',$patient_id)
                                        ->first(['id']);


                if(!empty($checRecordExist)){
                    //update

                    $updateRecord = [];
                    $updateRecord['latest_date']    = $latest_date;
                    $updateRecord['latest_length']  = $latest_length;

                    $this->MenstruationCycleModel
                                        ->where('id',$checRecordExist->id)
                                        ->update($updateRecord);

                    $all_transactions[] = 1;
                    $menstruation_cycle_id = $checRecordExist->id;

                }else{
                    //insert

                    $collection                 = new $this->MenstruationCycleModel;
                    $collection->patient_id     = $patient_id;
                    $collection->latest_date    = $latest_date;            
                    $collection->latest_length  = $latest_length;
                    if ($collection->save()){                            
                        $all_transactions[] = 1;
                    }else{
                        $all_transactions[] = 0;
                    } 

                   $menstruation_cycle_id = $collection->id;

                }

                //Step 2 : Maintain the history log in another table.i.e.menstruation_cycle_has_cycles

                $recordExistCount = $this->MenstruationCycleHasCyclesModel
                                        ->join('menstruation_cycle','menstruation_cycle.id','=','menstruation_cycle_has_cycles.menstruation_cycle_id')
                                        ->where('menstruation_cycle.patient_id',$patient_id)
                                        ->where('menstruation_cycle_has_cycles.date','=',$latest_date)
                                        ->count();

                if($recordExistCount==0){

                    $menstruationCycleHasCyclesModel = new $this->MenstruationCycleHasCyclesModel;
                    $menstruationCycleHasCyclesModel->menstruation_cycle_id     = $menstruation_cycle_id ;
                    $menstruationCycleHasCyclesModel->date    = $latest_date;            
                    $menstruationCycleHasCyclesModel->length  = $latest_length;
                    if($menstruationCycleHasCyclesModel->save()){                            
                        $all_transactions[] = 1;
                    }else{
                        $all_transactions[] = 0;
                    } 

                }  

                if (!in_array(0,$all_transactions)) 
                {   
                   $data[]  = $inputdata; 
                   $message = __('api.DATA_INSERTED'); 
                   self::_createLog('menstruationCycleCreate',$data,'info');
                   $this->ActivityLogModel->addApiLog('Menstruation Cycle Create','has created menstruation cycle','Create',null,$data);
                   DB::commit();
                }else
                {
                    DB::rollback();
                    $errors[]  = $e->getMessage();
                    self::_createLog('menstruationCycleCreate',$errors,'error');
                }
   
            }
            catch(\Exception $e) {
                DB::rollback();
                $errors[]  = $e->getMessage();
                self::_createLog('menstruationCycleCreate',$errors,'error');
            }
        }       
        return self::_sendResult($message,$data,$errors,$status);
    } 

    /*--------------------------------- 
    |   Get mwnstruation Cycle
    ------------------------------------------*/
    public function getMenstruationCycle(Request $request)
    {
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $inputdata  = $request->all();

        try { 
            $validator  = Validator::make($inputdata,[
                              // 'latest_date'  => 'required',
                              'patient_id'   => 'required',
                            ], 
                            [
                              // 'latest_date.required' => __('api.ERR_BEGINNING_DATE_REQ'), 
                              'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),     
                            ]
                            ); 

            if ($validator->fails()) 
            {           
              $errors[] = $validator->errors(); 
            }else
            {
                // $date = date('y-m-d', strtotime($request->latest_date));

                $collection = collect([]); 
                // $collection = $this->MenstruationCycleModel
                //                          ->where('patient_id','=',trim($request->patient_id))
                //                          ->where('latest_date',trim($date))
                //                          ->get();
                $collection = $this->MenstruationCycleModel
                                         ->where('patient_id','=',trim($request->patient_id))
                                         ->get();

                 if(!empty($collection) && sizeof($collection) > 0){
                        $status = true;
                        $message = __('api.DATA_FOUND_SUCCESS');
                        $data  = $collection;
                        self::_createLog('getMenstruationCycle',array($data),'info'); 

                    }else{
                        $message  = __('api.ERR_DATA_NOT_MATCH');
                        $errors[] = [
                              "error" => __('api.DATE_NOT_FOUND'),
                          ];
                        self::_createLog('getMenstruationCycle',$errors,'error');
                    }
                }
            }
            catch(\Exception $e) {
                DB::rollback();
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[]  = $e->getMessage();
                self::_createLog('getMenstruationCycle',$errors,'error');
            }
       return self::_sendResult($message,$data,$errors,$status);
    }

    public function menstruationCycleCalender(Request $request)
    {
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'start_date'  => 'required',
                          'length'   => 'required',
                          'patient_id'   => 'required',
                        ], 
                        [
                            'start_date.required' => __('api.ERR_LATEST_DATE_REQ'), 
                            'length.required' => __('api.ERR_LATEST_LENGTH_REQ'),    
                            'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 
        }else
        {
            try {  
                DB::beginTransaction();   
                $all_transactions = [];

                $status = true;
                $patient_id     = $request->patient_id;
                $start_date     = date('Y-m-d', strtotime($request->start_date));
                $length         = $request->length;

                $date = date_parse_from_format("Y-m-d", $start_date);
                $month = $date["month"]; 

                $insert_data = [];

                $key = 0;
                $firstDate = $date["year"]."-".$date["month"]."-10";
                $lastDate = date('Y-m-d', strtotime("+11 months", strtotime($firstDate)));
                // dd($lastDate);

                for($month; strtotime($start_date) <= strtotime($lastDate); $month++)
                {
                    // $counter++; 
                    $key++;
                    // if($counter == 0){
                    //    $start_date = date('Y-m-d', strtotime($request->start_date)); 
                    // } else{
                    //     $start_date = $insert_data[$counter-1]['menstruation'];
                    // }
                    // $key = date('M-d', strtotime($start_date));
                    // $dates = date_parse_from_format("Y-m-d", $start_date);
                    // $months = $date["month"]; 

                    $insert_data[$key]['start_date'] = $start_date;
                    $insert_data[$key]['patient_id'] = $patient_id;
                    $insert_data[$key]['length']     = $length;

                    $responseData[$key]['start_date'] = $start_date;
                    $responseData[$key]['patient_id'] = $patient_id;
                    $responseData[$key]['length']     = $length;

                    $menstruation = date('Y-m-d', strtotime($start_date. ' + '.$length.' days'));

                    $ovulation = date('Y-m-d', strtotime($start_date. ' + 14 days'));

                    $insert_data[$key]['ovulation'] = $ovulation;
                    $responseData[$key]['ovulation'] = $ovulation;

                    $implantation = [];
                    $implantation[] =  date('Y-m-d', strtotime($ovulation. ' + 4 days')); 
                    $implantation[] = date('Y-m-d', strtotime($ovulation. ' + 5 days'));

                    $insert_data[$key]['implantation'] = json_encode($implantation);
                    $responseData[$key]['implantation'] = $implantation;

                    $blood_test_possible = date('Y-m-d', strtotime($ovulation. ' + 12 days'));

                    $insert_data[$key]['blood_test_possible'] = $blood_test_possible;
                    $responseData[$key]['blood_test_possible'] = $blood_test_possible;

                    $urine_test_possible = date('Y-m-d', strtotime($ovulation. ' + 15 days'));

                    $insert_data[$key]['urine_test_possible'] = $urine_test_possible;
                    $responseData[$key]['urine_test_possible'] = $urine_test_possible;
                    
                    $insert_data[$key]['menstruation'] = $menstruation;
                    $responseData[$key]['menstruation'] = $menstruation;

                    $fertile   = [];
                    $fertile[] = date('Y-m-d', strtotime($ovulation. ' - 5 days'));
                    $fertile[] = date('Y-m-d', strtotime($ovulation. ' - 4 days'));
                    $fertile[] = date('Y-m-d', strtotime($ovulation. ' - 3 days'));

                    $insert_data[$key]['fertile'] = json_encode($fertile);
                    $responseData[$key]['fertile'] = $fertile;

                    $very_fertile = [];
                    $very_fertile[] = date('Y-m-d', strtotime($ovulation. ' - 2 days'));
                    $very_fertile[] = date('Y-m-d', strtotime($ovulation. ' - 1 days'));
                    $very_fertile[] = $ovulation;
                    $very_fertile[] = date('Y-m-d', strtotime($ovulation. ' + 1 days'));

                    $insert_data[$key]['very_fertile'] = json_encode($very_fertile);
                    $responseData[$key]['very_fertile'] = $very_fertile;

                    $start_date = $menstruation;
                }
                // dd($insert_data);
                foreach ($insert_data as $value) 
                {
                    $startDate = $value['start_date'];
                    // $this->BaseModel->updateOrInsert($value);
                    $last_start_date = $this->MenstruationCycleHasCalendarModel->where('start_date', $startDate)->first();
                    // dd($last_start_date);
                    if ($last_start_date === null) { 
                        // dd('not found');
                        $this->MenstruationCycleHasCalendarModel->insert($value);
                        
                    } else{
                        // dd('found');
                        $this->MenstruationCycleHasCalendarModel->where('start_date', $startDate)->update($value);
                    }
                }
                // dd($insert_data);
                if ($insert_data)  
                {   
                    $data[0]['length']          = $key; 
                    $data[0]['cycle_calendar'][]  = $responseData; 
                    $message = __('api.DATA_INSERTED'); 
                    self::_createLog('menstruationCycleCalender', $data,'info');
                    DB::commit();
                }else
                {
                    DB::rollback();
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            }
            catch(\Exception $e) {
                DB::rollback();
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                    "error" => $e->getMessage(), 
                ];
                self::_createLog('menstruationCycleCalender', $errors,'error');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    }//


    //Added on 11-oct-23
    public function getMenstruationCycleCalender(Request $request)
    {
        // dump('innnnnnnn');
        // dd("sjdfsdfksd");

        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $inputdata  = $request->all();

        $validator  = Validator::make($inputdata,[
                          'patient_id'   => 'required',
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
            try {  
                DB::beginTransaction();   
                $all_transactions = [];

                $status = true;
                $patient_id     = $request->patient_id;                
                $getData = $this->MenstruationCycleHasCalendarModel->where('patient_id', $request->patient_id)->get();

                $j=0; $respData=[];
                if(isset($getData) && !empty($getData))
                {
                     for($key=0;$key<count($getData);$key++)
                    {
                        $j++;

                        $responseData['start_date'] = $getData[$key]['start_date'];
                        $responseData['patient_id'] = $getData[$key]['patient_id'];
                        $responseData['length']     = $getData[$key]['length'];
                        $responseData['ovulation'] =$getData[$key]['ovulation'];
                        $responseData['implantation'] = json_decode($getData[$key]['implantation']);
                        $responseData['blood_test_possible'] = $getData[$key]['blood_test_possible'];
                        $responseData['urine_test_possible'] = $getData[$key]['urine_test_possible'];
                        $responseData['menstruation'] =$getData[$key]['menstruation'];
                        $responseData['fertile'] = json_decode($getData[$key]['fertile']);
                        $responseData['very_fertile'] = json_decode($getData[$key]['very_fertile']);
                        $respData[$j] = $responseData;

                    }
                }//if isset 

               // dump($j);
                //dd($responseData);
                if($respData)  
                {   
                    $data[0]['length']          = $j; 
                    $data[0]['cycle_calendar'][]  = $respData; 
                    $message = __('api.DATA_INSERTED'); 
                   // self::_createLog('getmenstruationCycleCalender', $respData,'info');
                }else
                {
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            }
            catch(\Exception $e) {
                DB::rollback();
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                    "error" => $e->getMessage(), 
                ];
                self::_createLog('menstruationCycleCalender', $errors,'error');
            }
        }
       return self::_sendResult($message,$data,$errors,$status);
    }//getMenstruationCycleCalender



    public function menstruationCycleCalenderApp(Request $request)
    {
        // dump('innnnnnnn');
        // dd("sjdfsdfksd");

        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        $inputdata  = $request->all();

        if($request->start_date && $request->length && $request->patient_id)
        {
                 $validator  = Validator::make($inputdata,[
                          'start_date'  => 'required',
                          'length'   => 'required',
                          'patient_id'   => 'required',
                        ], 
                        [
                            'start_date.required' => __('api.ERR_LATEST_DATE_REQ'), 
                            'length.required' => __('api.ERR_LATEST_LENGTH_REQ'),    
                            'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),     
                        ]
                        ); 

                if ($validator->fails()) 
                {           
                  $errors[] = $validator->errors(); 
                }else
                {
                    try {  
                        DB::beginTransaction();   
                        $all_transactions = [];

                        $status = true;
                        $patient_id     = $request->patient_id;
                        $start_date     = date('Y-m-d', strtotime($request->start_date));
                        $length         = $request->length;

                        $date = date_parse_from_format("Y-m-d", $start_date);
                        $month = $date["month"]; 

                        $insert_data = [];

                        $key = 0;
                        $firstDate = $date["year"]."-".$date["month"]."-10";
                        $lastDate = date('Y-m-d', strtotime("+11 months", strtotime($firstDate)));
                        //  dump($month);
                        // dump($firstDate);
                        //  dump($lastDate);

                        for($month; strtotime($start_date) <= strtotime($lastDate); $month++)
                        {
                            // $counter++; 
                            $key++;
                            // if($counter == 0){
                            //    $start_date = date('Y-m-d', strtotime($request->start_date)); 
                            // } else{
                            //     $start_date = $insert_data[$counter-1]['menstruation'];
                            // }
                            // $key = date('M-d', strtotime($start_date));
                            // $dates = date_parse_from_format("Y-m-d", $start_date);
                            // $months = $date["month"]; 

                            $insert_data[$key]['start_date'] = $start_date;
                            $insert_data[$key]['patient_id'] = $patient_id;
                            $insert_data[$key]['length']     = $length;

                            $responseData[$key]['start_date'] = $start_date;
                            $responseData[$key]['patient_id'] = $patient_id;
                            $responseData[$key]['length']     = $length;

                            $menstruation = date('Y-m-d', strtotime($start_date. ' + '.$length.' days'));

                            $ovulation = date('Y-m-d', strtotime($start_date. ' + 14 days'));

                            $insert_data[$key]['ovulation'] = $ovulation;
                            $responseData[$key]['ovulation'] = $ovulation;

                            $implantation = [];
                            $implantation[] =  date('Y-m-d', strtotime($ovulation. ' + 4 days')); 
                            $implantation[] = date('Y-m-d', strtotime($ovulation. ' + 5 days'));

                            $insert_data[$key]['implantation'] = json_encode($implantation);
                            $responseData[$key]['implantation'] = $implantation;

                            $blood_test_possible = date('Y-m-d', strtotime($ovulation. ' + 12 days'));

                            $insert_data[$key]['blood_test_possible'] = $blood_test_possible;
                            $responseData[$key]['blood_test_possible'] = $blood_test_possible;

                            $urine_test_possible = date('Y-m-d', strtotime($ovulation. ' + 15 days'));

                            $insert_data[$key]['urine_test_possible'] = $urine_test_possible;
                            $responseData[$key]['urine_test_possible'] = $urine_test_possible;
                            
                            $insert_data[$key]['menstruation'] = $menstruation;
                            $responseData[$key]['menstruation'] = $menstruation;

                            $fertile   = [];
                            $fertile[] = date('Y-m-d', strtotime($ovulation. ' - 5 days'));
                            $fertile[] = date('Y-m-d', strtotime($ovulation. ' - 4 days'));
                            $fertile[] = date('Y-m-d', strtotime($ovulation. ' - 3 days'));

                            $insert_data[$key]['fertile'] = json_encode($fertile);
                            $responseData[$key]['fertile'] = $fertile;

                            $very_fertile = [];
                            $very_fertile[] = date('Y-m-d', strtotime($ovulation. ' - 2 days'));
                            $very_fertile[] = date('Y-m-d', strtotime($ovulation. ' - 1 days'));
                            $very_fertile[] = $ovulation;
                            $very_fertile[] = date('Y-m-d', strtotime($ovulation. ' + 1 days'));

                            $insert_data[$key]['very_fertile'] = json_encode($very_fertile);
                            $responseData[$key]['very_fertile'] = $very_fertile;

                            $start_date = $menstruation;
                        }
                     
                        if($insert_data)
                        {
                            //start added below code on 13-oct-23
                            $getData = $this->MenstruationCycleHasCalendarModel->where('patient_id', $request->patient_id)->get();
                            if(isset($getData))
                            {
                              $this->MenstruationCycleHasCalendarModel->where('patient_id', $request->patient_id)->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                            }//if isset getData
                            //end added above code on 13-oct-23


                            foreach ($insert_data as $value) 
                            {
                                $startDate = $value['start_date'];
                                // $this->BaseModel->updateOrInsert($value);
                                $last_start_date = $this->MenstruationCycleHasCalendarModel->where('start_date', $startDate)->first();
                                // dd($last_start_date);
                                if ($last_start_date === null) { 
                                    // dd('not found');
                                    $this->MenstruationCycleHasCalendarModel->insert($value);
                                    
                                } else{
                                    // dd('found');
                                    $this->MenstruationCycleHasCalendarModel->where('start_date', $startDate)->update($value);
                                }
                            }
                        }//if insert_data



                        // dd($insert_data);
                        if ($insert_data)  
                        {   
                            $data[0]['length']          = $key; 
                            $data[0]['cycle_calendar'][]  = $responseData; 
                            $message = __('api.DATA_INSERTED'); 
                            self::_createLog('menstruationCycleCalender', $data,'info');
                            DB::commit();
                        }else
                        {
                            DB::rollback();
                            $message = __('api.ERR_SOMETHING_WRONG');
                        }
                    }
                    catch(\Exception $e) {
                        DB::rollback();
                        $message = __('api.ERR_SOMETHING_WRONG');
                        $errors[] = [
                            "error" => $e->getMessage(), 
                        ];
                        self::_createLog('menstruationCycleCalender', $errors,'error');
                    }
                }
        }
        else if($request->patient_id)
        {
              $validator  = Validator::make($inputdata,[
                          'patient_id'   => 'required',
                        ], 
                        [
                            'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),     
                        ]
                        ); 

                if ($validator->fails()) 
                {           
                  $errors[] = $validator->errors(); 
                }
                else
                {
                    try {  
                        DB::beginTransaction();   
                        $all_transactions = [];

                        $status = true;
                        $patient_id     = $request->patient_id;                
                        $getData = $this->MenstruationCycleHasCalendarModel->where('patient_id', $request->patient_id)->get();

                        $j=0; $respData=[];
                        if(isset($getData) && !empty($getData))
                        {
                             for($key=0;$key<count($getData);$key++)
                            {
                                $j++;

                                $responseData['start_date'] = $getData[$key]['start_date'];
                                $responseData['patient_id'] = $getData[$key]['patient_id'];
                                $responseData['length']     = $getData[$key]['length'];
                                $responseData['ovulation'] =$getData[$key]['ovulation'];
                                $responseData['implantation'] = json_decode($getData[$key]['implantation']);
                                $responseData['blood_test_possible'] = $getData[$key]['blood_test_possible'];
                                $responseData['urine_test_possible'] = $getData[$key]['urine_test_possible'];
                                $responseData['menstruation'] =$getData[$key]['menstruation'];
                                $responseData['fertile'] = json_decode($getData[$key]['fertile']);
                                $responseData['very_fertile'] = json_decode($getData[$key]['very_fertile']);
                                $respData[$j] = $responseData;

                            }
                        }//if isset 

                       // dump($j);
                        //dd($responseData);
                        if($respData)  
                        {   
                            $data[0]['length']          = $j; 
                            $data[0]['cycle_calendar'][]  = $respData; 
                            $message = __('api.DATA_INSERTED'); 
                           // self::_createLog('getmenstruationCycleCalender', $respData,'info');
                        }else
                        {
                            $message = __('api.ERR_SOMETHING_WRONG');
                        }
                    }
                    catch(\Exception $e) {
                        DB::rollback();
                        $message = __('api.ERR_SOMETHING_WRONG');
                        $errors[] = [
                            "error" => $e->getMessage(), 
                        ];
                        self::_createLog('menstruationCycleCalender', $errors,'error');
                    }
                }    
        }//else 

       return self::_sendResult($message,$data,$errors,$status);
       
    }//menstruationCycleCalenderApp

    
}


    