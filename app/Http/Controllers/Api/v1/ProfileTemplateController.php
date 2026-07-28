<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProfilesTemplatesModel;
use App\Models\ProfileHasExaminationsModel;  
use App\Models\ActivityLogModel;
use App\Models\ExaminationsModel; 
use Validator; 
use App\Traits\GeneralTrait;

class ProfileTemplateController extends BaseController
{
    use GeneralTrait;
	public function __construct(
        ProfilesTemplatesModel $ProfilesTemplatesModel,
        ExaminationsModel $ExaminationsModel,
        ProfileHasExaminationsModel $ProfileHasExaminationsModel,
        ActivityLogModel $ActivityLogModel
    )
    {
        $this->BaseModel          = $ProfilesTemplatesModel; 
        $this->ExaminationsModel  = $ExaminationsModel;
        $this->ProfileHasExaminationsModel = $ProfileHasExaminationsModel ; 
        $this->ActivityLogModel     = $ActivityLogModel; 

        // $this->ViewData = [];
        // $this->JsonData = []; 

        // $this->ModuleTitle = 'Patients';
        // $this->ModuleView  = 'admin.patients.';
        // $this->ModulePath = 'admin.patients.';
    }

    /*---------------------------------
    |   Profile Template Listing 
    ------------------------------------------*/
    public function getProfileTemplate(Request $request)
    {
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND'); 
        $status     = false;

        $patientAge  = $request->patient_age;
        // dd($patientAge);

        $inputdata  = $request->all();
        try{

            $validator  = Validator::make($inputdata,[
                              'patient_age'      => 'required',
                            ], 
                            [
                              'patient_age.required'    => __('api.AUTH_PATIENT_AGE_REQ'),     
                            ]
                            ); 

            if ($validator->fails()) 
            {           
              $errors[] = $validator->errors();  
            }else
            {
            $collection = collect([]); 
            // $collection = $this->BaseModel->with(['hasProfileExaminations'=>function($query){
            //                             $query->with(['assignedProfile','assignedExamination']);
            //                             }])->whereStatus(1)->get();
            // dd($collection); 
             
            $collection = $this->BaseModel->with(['hasProfileExaminations'=>function($query){
                                        $query->with(['assignedExamination']);
                                        }])
                                        ->where('age_from', '<=' ,$patientAge)
                                        ->where('age_to', '>=' ,$patientAge) 
                                        ->whereStatus(1)
                                        ->get();
             // dd($collection);

             if((!empty($collection) && sizeof($collection) > 0)){
                    $status  = true;
                    $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                    $data  = $collection;
                    self::_createLog('getProfileTemplate',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }else{
                    $status  = false;
                    $message = __('api.ERR_PROFILE_NOT_FOUND'); 
                    self::_createLog('getProfileTemplate',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getProfileTemplate',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

       return self::_sendResult($message,$data,$errors,$status);
    }

    public function getProfileExaminations(Request $request)
    {
        $errors     = [];  
        $data       = []; 
        $message    = __('api.ERR_PROFILE_DATA_NOT_FOUND'); 
        $status     = false;

        $profileId  = $request->profile_id;
        // dd($patientAge);

        $inputdata  = $request->all();
        try{

            $validator  = Validator::make($inputdata,[
                              'profile_id'      => 'required',
                            ], 
                            [
                              'profile_id.required'    => __('api.AUTH_PATIENT_AGE_REQ'),     
                            ]
                            ); 

            if ($validator->fails()) 
            {           
              $errors[] = $validator->errors();  
            }else
            {
                $collection = collect([]);     
                               
                $collections = $this->ExaminationsModel
                                    ->leftjoin('profile_has_examinations','profile_has_examinations.examination_id','=','examinations.id')
                                    ->where('profile_id','=',$profileId) 
                                    ->orWhere('examinations.trigger_exam_flag','=',1) 
                                    ->get([
                                            'examinations.id',
                                            'examinations.name',
                                            'examinations.url',
                                            'examinations.status',
                                            ]);

                if(!empty($collections) && ($collections->count() > 0))
                {
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = [];
                    foreach ($collections as $key => $collection)
                    {
                        $data[$key]['profile_id']  = $profileId;
                        $data[$key]['id']  = $collection->id;
                        $data[$key]['name']  = $collection->name;
                        $data[$key]['url']  = $collection->url;
                        $data[$key]['status']  = $collection->status;
                    }
                    $status  = true;
                    $message = __('api.PROFILE_DATA_FOUND_SUCCESS');
                    self::_createLog('getProfileExaminations',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }
                else{
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

}
    