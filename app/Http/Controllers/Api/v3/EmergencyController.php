<?php

namespace App\Http\Controllers\Api\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;  
use App\Models\EmergyncyModel; 
use App\Models\PatientsModel;
use App\Models\SettingsModel;
use App\Mail\EmergencyMail;
use App\Models\ActivityLogModel;
use Validator;
use Carbon\Carbon; 
use Mail;
use DB;

class EmergencyController extends BaseController
{
	public function __construct(
        EmergyncyModel $EmergyncyModel,
        PatientsModel $PatientsModel,
        SettingsModel $SettingsModel,
        ActivityLogModel $ActivityLogModel
    )
    {
        $this->BaseModel      = $EmergyncyModel;
        $this->PatientsModel  = $PatientsModel;
        $this->SettingsModel  = $SettingsModel;
        $this->ActivityLogModel     = $ActivityLogModel;
    }


    /*---------------------------------
    |   Create an Emergency with sending mail
    ------------------------------------------*/
    public function emergencyCreate(Request $request)
    {
        $errors   = [];  
        $data     = [];
        $message  = __('api.ERR_INVALID_DATA');
        $status   = false;

        $patientId   = $request->patient_id;

        $patientData = $this->PatientsModel
                            ->where('id', $patientId)
                            ->whereStatus(1)
                            ->get(['first_name','family_name','email','country_code', 'mobile_no','age','login_type','birth_date']);

        // Get emergency subject and address from setting
        $settingData = $this->SettingsModel
                            ->where('setting_key', 'EMERGENCY_BUTTON_SUBJECT')
                            ->orwhere('setting_key', 'EMERGENCY_BUTTON_EMAIL_ADDRESS')
                            ->whereStatus(1)
                            ->get();
        foreach ($settingData as $setting) 
        {
            if($setting->setting_key == 'EMERGENCY_BUTTON_EMAIL_ADDRESS'){
                $address = $setting->setting_value;
            }          
        }
        if(isset($address) && !empty($address))
        {////added on 03-march-26 for ordination email not found condition for #325                 
            // print_r($address); die;
            $inputdata   = $request->all(); 

            $validator = Validator::make($inputdata,[
                            'patient_id'  => 'required',
                            //'current_complaint'        => 'required',
                            //'previous_treatment' => 'required', 
                            ], 
                            [
                            'patient_id.required'  => __('api.ERR_PATIENT_ID_REQ'),
                            // 'current_complaint.required'        => __('api.ERR_CURRENT_COMPLAINT_REQ'),  
                            // 'previous_treatment.required' => __('api.ERR_PREVIOUS_TREATMENT_REQ'),     
                            ]
                            );  

            if ($validator->fails()) 
            {           
                $errors[] = $validator->errors(); 
            }else{
                
                try { 
                        $collection     =  new $this->BaseModel;
                        $collection->patient_id     = $request->patient_id;      
                        
                        $collection->current_complaint           = $request->current_complaint ?? '';
                        $collection->previous_treatment    = $request->previous_treatment ?? '';

                
                        //Save data
                        $collection->save(); 
                        $newData = $collection->toArray();

                        $collection->patientData = $patientData;
                        $collection->settingData = $settingData;

                        try {
                            // Send Emial  
                            $result = Mail::to($address)->send(new EmergencyMail($collection));
                        } 
                        catch (\Throwable $th) 
                        {
                            
                        }
    
                        $status  = true;
                        $message = __('api.SUCCESS_REQUEST_SEND');
                        $data[]  = $collection; 
                        // dd($data);
                        self::_createLog('emergencyCreate',$data,'info');
                        $this->ActivityLogModel->addApiLog('emergencyCreate','has create an emergency','Create',null,$newData);
                    
                        }
                catch(\Exception $e) {

                        $errors[] = $e->getMessage();
                        self::_createLog('emergencyCreate',$errors,'error');
                        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
            } 
        }else{
            $message = __('api.ERR_ORDINATION_EMAIL_NOT_FOUND');
            $errors[] = [
                  "error" => __('api.ERR_ORDINATION_EMAIL_NOT_FOUND'),
              ];
            self::_createLog('createAppointmentDelayReport',$errors,'error');  
            // $this->ActivityLogModel->addApiLog('Create Appointment Delay Report','has created appointment delay report','Create');  
        }//added on 03-march-26 for ordination email not found condition for #325        
        return self::_sendResult($message,$data,$errors,$status);
    } 

}
    