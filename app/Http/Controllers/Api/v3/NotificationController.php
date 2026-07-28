<?php

namespace App\Http\Controllers\Api\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AppointmentHasNotificationModel;
use App\Models\PatientsModel;
use App\Models\AppointmentModel;
use App\Models\ActivityLogModel;  
use App\Models\PatientsHasServiceReminderModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\ExaminationsModel;
use App\Models\ExaminationsHasMultipleDocumentListModel;
use Validator; 
use App\Traits\GeneralTrait;
use Log;

class NotificationController extends BaseController
{
    use GeneralTrait;
  public function __construct(
        AppointmentHasNotificationModel $AppointmentHasNotificationModel,
        PatientsModel $PatientsModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
        ActivityLogModel $ActivityLogModel,
        AppointmentModel $AppointmentModel,
        PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
        ExaminationsModel $ExaminationsModel,
        ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel
    )
    {
        $this->BaseModel = $AppointmentHasNotificationModel;
        $this->PatientsModel = $PatientsModel;
        $this->ActivityLogModel     = $ActivityLogModel; 
        $this->AppointmentModel     = $AppointmentModel; 
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;

    }

    /*---------------------------------
    |   Send notification from android app 
    ------------------------------------------*/
    public function send_notification(){

        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;
        $patient_id =  $_REQUEST['patient_id']; 

        // All data for send in notification
        $collection = $this->PatientsModel
                            ->leftjoin('appointment', 'patient_id' , '=', 'patients.id')
                            ->leftjoin('users', 'users.id' , '=', 'appointment.doctor_id')
                            ->leftjoin('appointment_types', 'appointment_types.id' , '=', 'appointment.appointment_type_id')
                            ->leftjoin('patient_has_device', 'patient_id' , '=', 'patients.id')
                            ->where("patients.id",'=',$patient_id)
                            ->get([
                                    'patients.id',
                                    'appointment.created_at',
                                    'appointment_types.name as appointment_type',
                                    'users.first_name as doctor_fname',
                                    'users.last_name as doctor_lname',
                                    'patient_has_device.device_id',
                                ])
                            ->first(); 

        $player_ids[]       = $collection->device_id;
        $appointment_type   = $collection->appointment_type;
        $Doctor_name        = $collection->doctor_fname.' '.$collection->doctor_lname;
        $appointment_time   = $collection->created_at->format('Y.m.d H:i:s');
        $headings           = array("en" => "Reminder of your appointment: ");

        // Create an single string of all content
        $content        = array(
            "en" => (string)'Hello, your appointment for '.' '.$appointment_type.' '.'with Dr.'.' '.(string)$Doctor_name.' '.'is on'.' '.(string)$appointment_time
        );

        $fields = array( 
            'app_id'                => config('constants.ONESIGNAL_APP_ID'),
            'include_player_ids'    => $player_ids,
            'large_icon'            => "ic_stat_onesignal_default",
            'headings'              => $headings,
            'contents'              => $content,
            'android_group'         => 'ANDROID',
            'android_group_message' => array("en" => "message")
        ); 

        $restAPIKey = config('constants.ONESIGNAL_REST_API_KEY');
        $fields     = json_encode($fields);
        
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
        // dd($data);

        try{
           if($data)  
            {
                $message = __('api.DATA_FOUND_SUCCESS');
                $status  = true;
                return self::_sendResult($data,$errors,$message,$status);
                self::_createLog('send_notification',array($data),'info');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            } else{ 
                $message = __('api.ERR_NOT_FOUND');
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('send_notification',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
        
    } 

    public function getReninderNotification($patient_id) 
    {
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;  

        $textContent = $this->ChannelsRemindersSettingModel
                       ->where('type','global')
                       ->first();

        $get_all_reminder = $this->PatientsHasServiceReminderModel
                ->with(['assignedAppointment'=>function($q){
                                $q->with(['assignedPatient','assignedDoctor','assignedAppointmentType','hasExaminations'=>function($q1){
                                        $q1->with(['assignedExamination']);

                                    }]);
                            }
                    ])
                ->where('patient_id',$patient_id)
                ->where('read_status','0')
                ->where('reminder_status','executed')
                //->where('status','activate')
                ->get();
//dd($get_all_reminder);
        if(!empty($get_all_reminder) && sizeof($get_all_reminder)>0)
        {

            $get_all_reminder = $get_all_reminder->map(function($item)
            {
                $item->book_status = $item->status;
                $exams = [];
                if(!empty($item->service_id))
                {
                    $getservices = $this->ExaminationsModel->find($item->service_id);

                   
                    if(!empty($getservices))
                    {
                        $exams['id']  = $getservices->id;
                        $exams['name'] = $getservices->name; 
                        $exams['url'] = $getservices->url;  
                        $exams['description'] = $getservices->description; 
                        $exams['document_name'] = $getservices->document_name; 
                        $exams['document_path'] = $getservices->document_path; 
                        $exams['document_status'] = $getservices->document_status; 
                        $exams['status'] = $getservices->status; 
                        $exams['created_at'] = date('Y-m-d H:i:s',strtotime($getservices->created_at));
                       
                    }
                }
                $item->exams = $exams;
                 return $item;
            });

           //dd($get_all_reminder );

            foreach ($get_all_reminder as $key => $collection)
            {
                $patientDetails = $this->PatientsModel->find($patient_id);
                $patient_name = $patientDetails->first_name .' '.$patientDetails->family_name;
                $content = 'Hallo '.$patient_name.', '.$textContent->reminder_push_notification_text.'('. $collection->exams['name'].')ist am '.Date('d.F').',um 09:00 Uhr.';

                
                $data[$key]['id']  = $collection->id;
                $data[$key]['type']  = 'reminder';
                $data[$key]['appointment_id']  = $collection->appointment_id;
                $data[$key]['notify_time']  = $collection->reminder_date;
                $data[$key]['content']  = $content;
                $data[$key]['title']  = "Erinnerung an Ihren Termin";
                $data[$key]['status']  = 0;
                $data[$key]['book_status']  =$collection->status;

                $data[$key]['appointment_type_id']  = 0;
                $data[$key]['appointment_type_name']  = '';
                $data[$key]['doctor_name']  = '';
                $data[$key]['doctor_speciality']  = '';
                $data[$key]['doc_img']  = asset('assets/admin/images/default-image.png');
                $data[$key]['exam_exist']  = 0;
                $data[$key]['exam_document_exist']  = 0;
                $data[$key]['past_exist']  = 0;

                $data[$key]['start_date']  = '';
                $data[$key]['exams']  = [];
                if(!empty($collection->assignedAppointment)){
                    
                    $data[$key]['start_date']  = $collection->assignedAppointment->start_date;
                    if(!empty($collection->assignedAppointment->assignedAppointmentType)){
                        $data[$key]['appointment_type_id']  = $collection->assignedAppointment->assignedAppointmentType->id;
                        $data[$key]['appointment_type_name']  = $collection->assignedAppointment->assignedAppointmentType->name;
                    }

                    if(!empty($collection->assignedAppointment->assignedDoctor)){
                        $data[$key]['doctor_name']  = $collection->assignedAppointment->assignedDoctor->first_name." ".$collection->assignedAppointment->assignedDoctor->last_name;
                        $data[$key]['doctor_speciality']  = $collection->assignedAppointment->assignedDoctor->doctor_speciality;
                        // if (!empty($collection->assignedAppointment->assignedDoctor->img_path) && is_file(storage_path().'/app/'.$collection->assignedAppointment->assignedDoctor->img_path)) 
                        $new_assignedDoctor = self::StorePath($collection->assignedAppointment->assignedDoctor->img_path.'/');

                        if (!empty($collection->assignedAppointment->assignedDoctor->img_path)) 
                        {
                            $data[$key]['doc_img']  = self::getFilePath($collection->assignedAppointment->assignedDoctor->img_path);
                            //$data[$key]['doc_img']  = url('/storage/app/'.$collection->assignedAppointment->assignedDoctor->img_path); 
                        }

                    }
                }

                $data[$key]['exams'] = [];
                if(!empty($collection->exams) && sizeof($collection->exams)>0)
                {
                    //dd($collection->exams['id'],$collection->exams['name']);
                    $data[$key]['exam_exist']  = 1;
                    $data[$key]['exams'][0]['id'] = $collection->exams['id'];
                    $data[$key]['exams'][0]['name'] = $collection->exams['name'];
                    $data[$key]['exams'][0]['url'] = '';
                    $data[$key]['exams'][0]['description'] = $collection->exams['description'];
                    $data[$key]['exams'][0]['document_name'] = $collection->exams['document_name'];

                    $data[$key]['exams'][0]['document_path'] = ''; 
                    $data[$key]['exams'][0]['document_status'] = ''; 
                    $data[$key]['exams'][0]['status'] = $collection->exams['status']; 
                    $data[$key]['exams'][0]['created_at'] = $collection->exams['created_at'];

                    $getDocument = $this->ExaminationsHasMultipleDocumentListModel
                                   ->where('fk_examinations_id',$collection->exams['id'])
                                   ->get();
                    if(!empty($getDocument)&& sizeof($getDocument)>0)
                    {
                        $data[$key]['exam_document_exist']  = 1;      
                    }
                }
            }
        } 
        //dd($data);
        return $data;
    }

    public function getNotification(Request $request)
    {
        //Log::info('in getNotification....');

        $errors = [];
        $data = [];
        $data1 = [];
        $message = __('api.ERR_NOT_FOUND');
        $status = false;
        $patientId   = $request->patient_id;
        $inputdata  = $request->all();
        $validator  = Validator::make($inputdata,[
                                    'patient_id'      => 'required',
                                ], [
                                  'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),
                                ]);
        if($validator->fails())
        {
            $errors[] = $validator->errors();
        }
        else {
            try
            {
                $status = true;
                $collections = $this->BaseModel
                                ->with(['assignedAppointment'=>function($q){
                                    $q->with(['assignedPatient','assignedDoctor','assignedAppointmentType','hasExaminations'=>function($q1)
                                    {
                                        $q1->with(['assignedExamination']);
                                    }]);
                            }])
                            ->where('patient_id', $patientId)
                            //->whereRaw('notify_time > curdate()') //commented on 6-nov-23 for only previous notifications
                            ->whereRaw('notify_time <= NOW()') // Compare with current date and time
                            ->orderBy('notify_time','DESC')
                            ->get();


                 // Log::info('qry collection data....');    
                 
                 // Log::info($collections);


                $current_time = date("Y-m-d H:i:s",time());
                $data  = [];
                foreach ($collections as $key => $collection)
                {
                    $data[$key]['id']  = $collection->id;
                    $data[$key]['patient_id']  = '';//Roshani Added on 16-07-24 for issue 140
                    $data[$key]['type']  = 'notification';
                    $data[$key]['appointment_id']  = $collection->appointment_id;
                    $data[$key]['notify_time']  = $collection->notify_time;
                    $data[$key]['content']  = $collection->content;
                    $data[$key]['title']  = $collection->title;
                    $data[$key]['status']  = $collection->status;
                    $data[$key]['appointment_type_id']  = 0;
                    $data[$key]['appointment_type_name']  = '';
                    $data[$key]['doctor_id']  = '';//Roshani Added on 16-07-24 for issue 140
                    $data[$key]['doctor_name']  = '';
                    $data[$key]['doctor_speciality']  = '';
                    $data[$key]['doc_img']  = asset('assets/admin/images/default-image.png');
                    $data[$key]['exam_exist']  = 0;
                    $data[$key]['exam_document_exist']  = 0;
                    $data[$key]['past_exist']  = 0;
                    if(strtotime($collection->notify_time)<strtotime($current_time))
                    {
                        $data[$key]['past_exist']  = 1;
                    }
                    $data[$key]['start_date']  = '';
                    $data[$key]['end_date']  = '';//Roshani Added on 16-07-24 for issue 140
                    $data[$key]['exams']  = array();
                    if(!empty($collection->assignedAppointment))
                    {
                        $data[$key]['start_date']  = $collection->assignedAppointment->start_date;
                        /**********Roshani Added on 16-07-24 for issue 140**********/
                        $data[$key]['end_date']  = $collection->assignedAppointment->end_date;

                        if(!empty($collection->assignedAppointment->assignedPatient))
                        {
                            $data[$key]['patient_id']  = $collection->assignedAppointment->assignedPatient->id;
                            $data[$key]['patient_name']  = $collection->assignedAppointment->assignedPatient->family_name." ".$collection->assignedAppointment->assignedPatient->first_name;
                        }
                        
                        /**********Roshani Added on 16-07-24 for issue 140***********/
                        if(!empty($collection->assignedAppointment->assignedAppointmentType))
                        {
                            $data[$key]['appointment_type_id']  = $collection->assignedAppointment->assignedAppointmentType->id;
                            $data[$key]['appointment_type_name']  = $collection->assignedAppointment->assignedAppointmentType->name;
                        }
                        if(!empty($collection->assignedAppointment->assignedDoctor))
                        {
                            $data[$key]['doctor_id']  = $collection->assignedAppointment->assignedDoctor->id;//Roshani Added on 16-07-24 for issue 140
                            $data[$key]['doctor_name']  = $collection->assignedAppointment->assignedDoctor->first_name." ".$collection->assignedAppointment->assignedDoctor->last_name;
                            $data[$key]['doctor_speciality']  = $collection->assignedAppointment->assignedDoctor->doctor_speciality;
                            $new_assignedDoctor = self::StorePath($collection->assignedAppointment->assignedDoctor->img_path.'/');
                            if (!empty($collection->assignedAppointment->assignedDoctor->img_path))
                            {
                                $data[$key]['doc_img']  = self::getFilePath($collection->assignedAppointment->assignedDoctor->img_path);
                            }
                        }
                        if(!empty($collection->assignedAppointment->hasExaminations) && sizeof($collection->assignedAppointment->hasExaminations)>0)
                        {
                            $data[$key]['exam_exist']  = 1;
                            foreach ($collection->assignedAppointment->hasExaminations as  $haskey=>$hasExamination)
                            {
                                if(!empty($hasExamination->assignedExamination)){

                                    // $data[$key]['exams'][$haskey]['id'] = $hasExamination->assignedExamination->id;
                                    // $data[$key]['exams'][$haskey]['name'] = $hasExamination->assignedExamination->name;
                                    // $data[$key]['exams'][$haskey]['url'] = $hasExamination->assignedExamination->url;
                                    // $data[$key]['exams'][$haskey]['description'] = $hasExamination->assignedExamination->description;
                                    // $data[$key]['exams'][$haskey]['c'] = $hasExamination->assignedExamination->document_name;
                                    // $data[$key]['exams'][$haskey]['document_path'] = $hasExamination->assignedExamination->document_path;
                                    // $data[$key]['exams'][$haskey]['document_status'] = $hasExamination->assignedExamination->document_status;
                                    // $data[$key]['exams'][$haskey]['status'] = $hasExamination->assignedExamination->status;
                                    // $data[$key]['exams'][$haskey]['created_at'] = date('Y-m-d H:i:s',strtotime($hasExamination->assignedExamination->created_at));
                                    $data[$key]['exams'][]=array(
                                        'id'=>$hasExamination->assignedExamination->id,
                                        'name'=>$hasExamination->assignedExamination->name,
                                        'url'=>$hasExamination->assignedExamination->url,
                                        'description'=>$hasExamination->assignedExamination->description,
                                        'description'=>$hasExamination->assignedExamination->description,
                                        'document_path'=>$hasExamination->assignedExamination->document_path,
                                        'document_status'=>$hasExamination->assignedExamination->document_status,
                                        'status'=>$hasExamination->assignedExamination->status,
                                        'created_at'=>date('Y-m-d H:i:s',strtotime($hasExamination->assignedExamination->created_at))
                                        );
                                    $new_assignedExamination_path = self::StorePath($hasExamination->assignedExamination->document_path.'/');
                                    $getDocument = $this->ExaminationsHasMultipleDocumentListModel
                                       ->where('fk_examinations_id',$hasExamination->assignedExamination->id)
                                       ->get();
                                    if(!empty($getDocument)&& sizeof($getDocument)>0)
                                    {
                                        $data[$key]['exam_document_exist']  = 1;
                                    }
                                }
                            }
                        }
                    }
                }
                if(!empty($collections) && sizeof($collections) > 0)
                {
                   // Log::info('in not empty collection data....');  

                    $getReninderNotification = self::getReninderNotification($patientId);

                   //  Log::info('after getReninderNotification....');  

                   //   Log::info($getReninderNotification);  


                    $data = array_merge($data,$getReninderNotification);
                    array_multisort(array_column($data, 'notify_time'), SORT_DESC, $data);
                    $message = __('api.DATA_FOUND_SUCCESS');
                    self::_createLog('getNotification',$data,'info');
                }
                else {
                    $message = __('api.ERR_NOT_FOUND');
                    self::_createLog('getNotification',$message,'error');
                }
            }
            catch(\Exception $e)
            {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                        "error" => $e->getMessage(),
                    ];
                self::_createLog('getNotification',$errors,'error');
            }
        }
        return self::_sendResult($message,$data,$errors,$status);
    }

    public function updateNotificationStatus(Request $request) 
    {
        $errors = [];
        $data = [];
        $message = __('api.ERR_NOT_FOUND');
        $status = false;

        $notificationId   = $request->notification_id; 
        $type   = $request->type; 
  
        $validator = Validator::make($request->all(), [
                    'notification_id' => 'required',
                  ], 
            [
              'notification_id.required' => __('api.NOTIFICATION_ID_REQ'),
            ]);
        if($validator->fails()) {           
          $errors[] = $validator->errors();
        }else{

        try
        {
            $status = true;  
            if($type == 'notification')
            {
                $collection = $this->BaseModel->find($notificationId);
                $oldData = [];
                $oldData['first_name'] = $collection->first_name;
                $oldData['family_name'] = $collection->family_name;
                $oldData['email'] = $collection->email;
                $oldData['country_code'] = $collection->country_code;
                $oldData['mobile_no'] = $collection->mobile_no;
                $oldData['birth_date'] = date('y-m-d', strtotime($collection->birth_date));
                $oldData['age'] = $collection->age;
                        // dd($oldData);
                if($collection){
                $collection->update([
                              'status' => 2, 
                            ]);
                // dd($collection); 
                $collection = $collection->only(['notify_time','content','title','status']);
                        $message = __('api.NOTIFICATION_UPDATE_SUCCESS'); 
                        $data[] = $collection;
                        // dd($oldData);
                        // dd($data[0]);
                        self::_createLog('updateNotificationStatus',$data,'info');
                        $this->ActivityLogModel->addApiLog('Update Notification Status','Update Notification Status','Update',$oldData,$data[0]);
                        } else{
                            $message = __('api.NOTIFICATION_UPDATE_FAIL');
                            self::_createLog('updateNotificationStatus',$message,'error');
                        }
            }
            else if($type == 'reminder')
            {
                $get_all_reminder = $this->PatientsHasServiceReminderModel->find($notificationId);
                if(!empty($get_all_reminder))
                {
                    $collection = $this->PatientsModel->find($get_all_reminder->patient_id);

                    $oldData = [];
                    $oldData['first_name'] = $collection->first_name;
                    $oldData['family_name'] = $collection->family_name;
                    $oldData['email'] = $collection->email;
                    $oldData['country_code'] = $collection->country_code;
                    $oldData['mobile_no'] = $collection->mobile_no;
                    $oldData['birth_date'] = date('y-m-d', strtotime($collection->birth_date));
                    $oldData['age'] = $collection->age;
                                    //dd($oldData);
                  
                    $get_all_reminder->read_status = '1';
                    $get_all_reminder->save();

                   $collection = $collection->only(['notify_time','content','title','status']);
                        $message = __('api.NOTIFICATION_UPDATE_SUCCESS'); 
                        $data[] = $collection;
                    
                          
                }
            }
           
        }
        catch(\Exception $e) {

            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                "error" => $e->getMessage(),
            ];
            self::_createLog('updateNotificationStatus',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

        }                 
      }
      
      return self::_sendResult($message,$data,$errors,$status);

    }

    public function unreadNotificationCount(Request $request)
    {
        $errors = [];
        $data = [];
        $message = __('api.ERR_NOT_FOUND');
        $status = false;
        $notificationCnt = $reminderCnt = 0;
        $patientId   = $request->patient_id;
        $count = 0;
        $inputdata  = $request->all();

        Log::info($inputdata);

        $validator  = Validator::make($inputdata,[
                                    'patient_id'      => 'required',
                                ], [
                                    'patient_id.required'    => __('api.AUTH_PATIENT_ID_REQ'),
                                ]);
        if($validator->fails())
        {
            $errors[] = $validator->errors();
        }
        else {
            try
            {
                $status = true;
                //['status', '=', 0]
                $collection = $this->BaseModel
                                    ->where([
                                            ['patient_id', '=', $patientId],
                                            ['status', '!=', 2]
                                        ])
                                    //->whereRaw('notify_time > curdate()') //commented on 6-nov-23 for hide upcoming notification count
                                    ->whereRaw('notify_time <= NOW()') //added on 6-nov-23 for Compare with current date and time
                                    ->get();

                 Log::info("======collection===========");                 
                 Log::info($collection);
                    

                $getreminder = $this->PatientsHasServiceReminderModel
                                     ->where('read_status','0')
                                     ->where('reminder_status','Set')
                                     ->where('patient_id',$patientId)
                                     ->whereNull('deleted_at')
                                     ->get();

                Log::info("======getreminder===========");
                Log::info($getreminder);

                     
                if(!empty($collection) && sizeof($collection) > 0)
                {
                    $notificationCnt = $collection->count();

                      Log::info("======notificationCnt===========");
                      Log::info($notificationCnt);

                }
                if(!empty($getreminder) && sizeof($getreminder) > 0)
                {
                    $reminderCnt = $getreminder->count();

                    Log::info("======reminderCnt===========");
                    Log::info($reminderCnt);

                }
                //Commented by Shyam 17-03-22
                // $count = (int)$notificationCnt + (int)$reminderCnt;
                //Added by Shyam 17-03-22
                $count = (int)$notificationCnt;
                  Log::info($count);
                if(!empty($count) && $count>0)
                {
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data = $count;
                    self::_createLog('unreadNotificationCount',$data,'info');
                }
                else {
                       $data = 0; //added on 9-oct-24
                    $message = __('api.ERR_NOT_FOUND');
                    self::_createLog('unreadNotificationCount',$message,'error');
                }
            }
            catch(\Exception $e)
            {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                    "error" => $e->getMessage(),
                ];
                self::_createLog('unreadNotificationCount',$errors,'error');
            }
        }
        return self::_sendResult($message,$data,$errors,$status);
    }

    public function getCountForRemindersAndNotification(Request $request)
    {
        $errors = [];
        $data = [];
        $message = __('api.ERR_NOT_FOUND');
        $status = false;
        $notificationCnt = $reminderCnt = 0;
        $patientId   = $request->patient_id;

        $validator = Validator::make($request->all(), [
                    'patient_id' => 'required',
                  ],
            [
              'patient_id.required' => __('api.ERR_PATIENT_ID_REQ'),
            ]);
        if($validator->fails()) {
          $errors[] = $validator->errors();
        }else{

            try{
                  if(!empty($patientId) && intval($patientId)) {
                        //AppointmentHasNotificationModel
                         $collection = $this->BaseModel
                                            //->selectRaw("count(id) as id")
                                            ->where('status','=','0')
                                            ->where('patient_id','=',$patientId)
                                            ->get();
                         $getreminder = $this->PatientsHasServiceReminderModel
                                            //->selectRaw("count(id) as id")
                                            ->where('patient_id','=',$patientId)
                                            ->where('read_status','=','0')
                                            ->groupBy('service_id')
                                            ->get();
                    if(!empty($collection) && sizeof($collection) > 0)
                    {
                        $notificationCnt = $collection->count();
                    }

                    if(!empty($getreminder) && sizeof($getreminder) > 0)
                    {
                        $reminderCnt = $getreminder->count();
                    }
                     //dd($notificationCnt,$reminderCnt);
                    $count = (int)$notificationCnt + (int)$reminderCnt;

                    if(!empty($count) && $count>0)
                    {
                        $message = __('api.DATA_FOUND_SUCCESS');
                        //$data = $count;
                        $status = true;
                        $this->JsonData['countNotification'] = $count;
                        $data[] = [
                            'countNotification' => $count,
                        ];
                        self::_createLog('getCountForRemindersAndNotification',$data,'info');
                    } else
                    {
                        $message = __('api.ERR_NOT_FOUND');
                        self::_createLog('getCountForRemindersAndNotification',$message,'error');
                    }
                 }
             }  catch(\Exception $e) {

                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                    "error" => $e->getMessage(),
                ];
                self::_createLog('getCountForRemindersAndNotification',$errors,'error');
            }
        }
        return self::_sendResult($message,$data,$errors,$status);
    }



}

    


