<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;  
// use Illuminate\Support\Arr;
// use \Dimsav\Translatable\Translatable;  

// Models
use App\Models\AppointmentHasNotificationModel;  
use App\Models\PatientsModel;
use App\Models\PatientHasDeviceModel; 
use App\Models\ActivityLogModel;  
use App\Models\AppointmentHasExaminationsModel;  
use App\Models\AppointmentModel;  

// exports 
// use Maatwebsite\Excel\Facades\Excel;
// use App\Exports\CollectionExport;

// plugins
use Hash;
use Mail;
use DB; 
use Auth;
use status;
use App\Traits\GeneralTrait;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024
use Log;

class AppointmentHasNotificationController extends Controller
{
    use GeneralTrait;
    private $BaseModel;
    // public $translatedAttributes = ['name']; 

    public function __construct(
        // array $attributes = [],
        AppointmentHasNotificationModel $AppointmentHasNotificationModel,
        PatientsModel $PatientsModel,
        PatientHasDeviceModel $PatientHasDeviceModel,
        ActivityLogModel $ActivityLogModel,
        AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
        AppointmentModel $AppointmentModel
    )
    {
        $this->BaseModel        = $AppointmentHasNotificationModel; 
        $this->PatientsModel    = $PatientsModel;
        $this->PatientHasDeviceModel    = $PatientHasDeviceModel;
        $this->ActivityLogModel         = $ActivityLogModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
        $this->AppointmentModel                = $AppointmentModel;

        $this->ViewData = [];
        $this->JsonData = [];   

        $this->ModuleTitle  = __('admin.TITLE_NOTIFICATION_TEXT'); 
        $this->ModuleView   = 'admin.notification.';
        $this->ModulePath   = 'admin.notification.'; 
        
        // Permission Middleware
        // $this->middleware(['permission:activity-logs'], ['only' => ['index']]);

       
        $this->defaultLocale = 'en'; 
    } 

    public function index()    
    { 
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_NOTIFICATION_TEXT');
        $this->ViewData['ModuleTitle']  = __('admin.TITLE_MANAGE_TEXT').' '.$this->ModuleTitle;
        // $this->ViewData['ModuleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT'); 
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['ModulePath']   = $this->ModulePath;

        // dd($this->ViewData['ModulePath']);
        // view file with data 
        return view($this->ModuleView.'index', $this->ViewData);
    }

    public function create() 
    {
        $this->ModuleTitle              = __('admin.TITLE_NOTIFICATION_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle);
        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT'); 

        // All userdata
        // dd('create');
        $notification = $this->BaseModel->whereStatus(3)->first();

        // dd($notification);
        $this->ViewData['notification'] = $notification;
    
        // view file with data
        return view($this->ModuleView.'create', $this->ViewData);

      
    }

    public function store(Request $request)
    {
        // dump($request->all());
        
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_NOTIFICATION_UPDATE');

        try {

            $notify_data=[]; //added on 24-jan-25 

            DB::beginTransaction(); 
            $all_transactions = [];

            $record_exist = $this->BaseModel
                                 ->whereStatus(3)
                                 ->first(['id']);
            // dd($record_exist);
            if(!empty($record_exist)){
                //update
                //dd('update');
                $id = $record_exist->id;
                $collection = $this->BaseModel->find($id);

            }else{
                //insert
               //dd('insert');
                $collection     = new $this->BaseModel;    
            }

            $current_date = date("Y-m-d",time());

            $collection->patient_id     = 0;
            $collection->appointment_id = 0;
            $collection->notify_time    = $current_date." ".$request->notify_time;
            $collection->day            = $request->day;
            $collection->content        = $request->content;
            $collection->title          = $request->title;
            $collection->status         = $request->status;

            $collection     = self::_storeOrUpdate($collection,$request);
            if ($collection->save()) 
            {
                 $all_transactions[] = 1;
               
            }else
            {
                $all_transactions[] = 0;
            } 


            $getAppointments =   $this->AppointmentModel->with(['assignedPatient','assignedDoctor','assignedAppointmentType'])
                                    ->whereStatus(1)
                                    ->whereDate('start_date','>=',$current_date)
                                    ->get();                                        

            foreach ($getAppointments as $getAppointment) 
            {
                // dump($getAllAppointment);
                $start_date = $getAppointment->start_date;
                if($request->day==0){

                    $req_notify_time            = explode(":",$request->notify_time);
                    $req_notify_time_in_seconds = (($req_notify_time[0]*60*60)+($req_notify_time[1]*60));

                    //commented on 10-nov-25
                    // $app_notify_time            = date("Y-m-d H:i:s", strtotime($start_date) - $req_notify_time_in_seconds);

                    //changed on 10-nov-25
                     $app_notify_time = date("Y-m-d H:i:s",strtotime(date("Y-m-d", strtotime($start_date)) . " " . $request->notify_time));

                    //dump($req_notify_time_in_seconds,$app_notify_time,$start_date,$getAppointment->toArray());
                    //$notify_time = date("Y-m-d H:i", strtotime('-2 hour', strtotime($start_date)));
                }else{
                    $previous_day   = date("Y-m-d", strtotime('-1 day', strtotime($start_date)));
                    $app_notify_time = date("Y-m-d H:i:s", strtotime($previous_day." ".$request->notify_time));
                }


                // Added on 13-Nov-25 — skip notification if appointment time < notify time for same day
                $appointmentDate = date("Y-m-d", strtotime($start_date));
                $currentDate = date("Y-m-d");

               // Log::info(strtotime($start_date));
               // Log::info(strtotime($app_notify_time));

                $shouldSkip = (
                    $request->day == 0 &&                              // only for same-day notifications
                    date('Y-m-d', strtotime($start_date)) == date('Y-m-d') && // today's appointments
                    strtotime($start_date) < strtotime($app_notify_time)       // appointment earlier than notify time
                );



                //$patientName = $getAppointment->assignedPatient->salutation.'.' ?? "";
                $patientName = $getAppointment->assignedPatient->salutation ? $getAppointment->assignedPatient->salutation.'.': ""; 



                // below issset condition added on 18-jan-24 commented on 12-dec-25
                // if(isset($getAppointment->assignedPatient))
                // {
                //  $patientName .= $getAppointment->assignedPatient->family_name?" ".$getAppointment->assignedPatient->family_name:'';
                // }

                //changed on 12-dec-25
                if(isset($getAppointment->assignedPatient->salutation))
                {
                    $patientName .=  " ".$getAppointment->assignedPatient->first_name." ".$getAppointment->assignedPatient->family_name;
                }
                else
                {
                    $patientName .= $getAppointment->assignedPatient->first_name." ".$getAppointment->assignedPatient->family_name;
                }


                if(isset($getAppointment->assignedDoctor))
                {
                 $doctorSurname   = $getAppointment->assignedDoctor->last_name?$getAppointment->assignedDoctor->last_name:'';
                }
                if(isset($getAppointment->assignedAppointmentType))
                {
                 $appointmentType = $getAppointment->assignedAppointmentType->name?$getAppointment->assignedAppointmentType->name:'';
                }

                $appointmentTime = date('d.F',strtotime($app_notify_time)).",um ".date('H:i',strtotime($app_notify_time))." Uhr.";

                //added on 2-feb-24
                // $app_start_date =  date('d.F',strtotime($getAppointment->start_date)).",um ".date('H:i',strtotime($getAppointment->start_date))." Uhr."; 

                //added on 5-feb-24
                $booking_month = __('admin.'.date('F',strtotime($getAppointment->start_date)),[],'de');
                $app_start_date =  date('d',strtotime($getAppointment->start_date)).'.'.$booking_month.", um ".date('H:i',strtotime($getAppointment->start_date))." Uhr."; 

                $content = $request->content;
                $content = str_replace("##PATIENT_NAME##", $patientName, $content);
                $content = str_replace("##DOCTOR_SURNAME##", $doctorSurname, $content);
                $content = str_replace("##APPOINTMENT_TYPE##", $appointmentType, $content);
                // $content = str_replace("##DATE_TIME##", $appointmentTime, $content);//commented on 2-feb-24
                $content = str_replace("##DATE_TIME##", $app_start_date, $content);//added on 2-feb-24

                //$content = 'Hallo '.$patientName.', Ihr Termin mit Dr. '.(string)$doctorSurname.' ('.$appointmentType.') ist am'.' '.(string)$appointmentTime;

                //Delete its status 0 data 
                $old_status_data[] = array(
                                    'patient_id'    => $getAppointment->patient_id,
                                    'appointment_id'=> $getAppointment->id,
                                    'status'        => 0,
                                );


                // Only add to notify_data if not skipped
                if ($shouldSkip) {
                    Log::info("Skipped notification for appointment ID {$getAppointment->id} — appointment time ({$start_date}) is before notify time ({$app_notify_time}).");
                } 
                else 
                {
                  $notify_data[] = array(
                                    'patient_id'    => $getAppointment->patient_id,
                                    'appointment_id'=> $getAppointment->id,
                                    'title'         => 'Erinnerung an Ihren Termin',
                                    'content'       => $content,
                                    'notify_time'   => $app_notify_time,
                                    'status'        => 4,
                                );
                }


               


                // dd($notify_data);
            }                                    
            //dd($notify_data,$getAppointments->toArray());

            //start added on 11-nov-25

            // DELETE OLD STATUS DATA HERE
            if (!empty($old_status_data)) {
                foreach ($old_status_data as $old) {
                    $this->BaseModel
                        ->where('patient_id', $old['patient_id'])
                        ->where('appointment_id', $old['appointment_id'])
                        ->where('status', 0)
                        ->update(['status'=>5]);
                }
            }

            //end added on 11-nov-25



            $this->BaseModel->where('status',4)->delete();
            if($this->BaseModel->insert($notify_data))
            {
                $all_transactions[] = 1;
            }
            else
            {
                $all_transactions[] = 0;
            } 

            if (!in_array(0,$all_transactions)) 
            {
                DB::commit();
                // dd($collection);
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'index');
                $this->JsonData['msg']    = __('admin.NOTIFICATION_UPDATED'); 
            }
            

        }
        catch(\Exception $e) {
            DB::rollback();
            // dd($e->getMessage());
            $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }


    public function edit($encID)
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_NOTIFICATION_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT'); 

        // All userdata
        $id = base64_decode(base64_decode($encID)); 
        $notification = $this->BaseModel->find($id);

        // dd($notification);
        $this->ViewData['patient'] = '';
        if(!empty($notification)){

            $this->ViewData['patient'] = $this->PatientsModel
                                            ->where('status', 1)
                                            ->where('id', $notification->patient_id)
                                            ->get();  
        }
        $this->ViewData['notification'] = $notification;
    
        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    } 

    public function show($id)
    {
        dd('show');
    }

    public function update(Request $request, $encID)
    {
        $id = base64_decode(base64_decode($encID)); 
        $this->JsonData['status'] = __('admin.RESP_ERROR'); 
        $this->JsonData['msg']    = __('admin.FAIL_NOTIFICATION_UPDATE');                      
        try {
            $collection = $this->BaseModel->find($id); 
            $oldData = $collection->toArray();

            // $collection = self::_storeOrUpdate($collection,$request); //commented on 18-jan-24
             $collection = self::_storeOrUpdateNew($collection,$request); //added on 18-jan-24

            $newData = $collection->toArray();
            if ($collection) 
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated notification','Update',$oldData,$newData);

                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'index');
                $this->JsonData['msg']    = __('admin.NOTIFICATION_UPDATED');
            }
        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }

   
    public function getRecords(Request $request) 
    {
        /*--------------------------------------
        |  Variables
        ------------------------------*/
  
            // skip and limit
            $start  = $request->start; 
            $length = $request->length;

            // serach value
            $search = $request->search['value'];  

            // order
            $column = $request->order[0]['column'];
            $dir    = $request->order[0]['dir'];

            // filter columns
            $filter = array(
                0 => 'id',
                1 => 'appointment_has_notification.patient_id',
                2 => 'appointment_has_notification.notify_time', 
                3 => 'appointment_has_notification.content',
                4 => 'appointment_has_notification.title',
                5 => 'appointment_has_notification.status',
                // 5 => 'appointment_has_notification.ip',
                // 6 => 'appointment_has_notification.agent',
                // 7 => 'appointment_has_notification.appointment_id',
                // 8 => 'appointment_has_notification.created_at', 
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel
                                ->leftjoin('patients', 'patients.id' , '=', 'appointment_has_notification.patient_id')
                                ->where('appointment_has_notification.status','!=',5); 

            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

            # FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {

                // if (!empty($request->custom['patient_id'])) 
                // {
                //     $custom_search = true;
                //     $key = $request->custom['patient_id'];                
                //     $modelQuery = $modelQuery
                //     ->where('appointment_has_notification.patient_id','LIKE','%'.$key.'%'); 
                // } 

                // if (!empty($request->custom['notify_time'])) 
                // {
                //     $custom_search = true;
                //     $key = $request->custom['notify_time'];                
                //     $modelQuery = $modelQuery
                //     ->where('appointment_has_notification.notify_time','LIKE','%'.$key.'%');
                // }

                // if (!empty($request->custom['title'])) 
                // {
                //     $custom_search = true;
                //     $key = $request->custom['title']; 
                //     $modelQuery = $modelQuery
                //     ->where('appointment_has_notification.title','LIKE','%'.$key.'%');
                // } 

                // if (!empty($request->custom['title'])) 
                // {
                //     $custom_search = true;
                //     $key = $request->custom['title']; 
                //     $modelQuery = $modelQuery
                //     ->where('appointment_has_notification.title','LIKE','%'.$key.'%');
                // } 

                // if (isset($request->custom['status'])) 
                // {
                //     $custom_search = true;
                //     $key = $request->custom['status'];
                //     $modelQuery = $modelQuery
                //     ->where('appointment_has_notification.status', $key);
                // }

            }

            //filter options
            if (!empty($request->search))
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];

                     $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orwhere('appointment_has_notification.patient_id', 'LIKE', '%'.$search.'%'); 
                        $query->orwhere('appointment_has_notification.notify_time', 'LIKE', '%'.$search.'%');
                         $query->orwhere('appointment_has_notification.title', 'LIKE', '%'.$search.'%');
                          $query->orwhere('appointment_has_notification.content', 'LIKE', '%'.$search.'%');
                        $query->orwhere('appointment_has_notification.status', 'LIKE', '%'.$search.'%');  
                    }); 
                }
            }  

            // get total filtered
            $filteredQuery = clone($modelQuery);            
            $totalFiltered  = $filteredQuery->count(); 
            
            // offset and limit
            $object = $modelQuery->orderBy($filter[$column], $dir)
                                 ->skip($start)
                                 ->take($length) 
                                 ->get([
                                    'patients.id as patient_id',
                                    'patients.first_name as patient_first_name',
                                    'patients.family_name as patient_last_name',
                                    'appointment_has_notification.id',
                                    'appointment_has_notification.notify_time',
                                    'appointment_has_notification.content',
                                    'appointment_has_notification.title',
                                    'appointment_has_notification.status'
                            ]); 
            // dd($object);  
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row)  
                { 
                        $fname = $row->patient_first_name;
                        $lname = $row->patient_last_name;
                        $name  = $fname .' '. $lname;

                        $data[$key]['id']       = $row->id;

                        $data[$key]['patient_id']   = '<span title="'.$name.'">'.$name.'</span>';

                        $data[$key]['notify_time']   = '<span title="'.$row->notify_time.'">'.$row->notify_time.'</span>';

                        $data[$key]['title']  = '<span title="'.$row->title.'">'.$row->title.'</span>';

                        $data[$key]['content']  = '<span title="'.$row->content.'">'.$row->content.'</span>';

                        $data[$key]['status']      =  "<span title='".$row->status."'>".$row->status."</span>";

                        if ($row->status==0)  
                        {
                            $status = '<span class="badge bg-primary theme-green semibold text-center f-18" title="Call to Patient">Added</span>';
                        }
                        elseif($row->status==1) 
                        {
                            $status = '<span class="theme-black-light semibold text-center f-18 badge bg-warning">Notified</span>'; 
                        } 
                        elseif($row->status==3) 
                        {
                            $status = '<span class="theme-black-light semibold text-center f-18 badge bg-warning">Default</span>'; 
                        } 
                        elseif($row->status==4)    
                        {
                             $status = '<span class="badge bg-primary theme-green semibold text-center f-18" title="Call to Patient">Added</span>';
                        } 
                        else   
                        {
                            $status = '<span class="badge bg-success theme-green semibold text-center f-18 action-icon" title="Call to Patient">Read</span>&nbsp&nbsp';
                        } 
                        
                        $edit = '';
                        if($row->status!=3){

                        $edit = '<a href="'.route($this->ModulePath.'edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                        }
 
                        $notification = '<a href = "#" id="sendnotification" data-id="'.$row->id.'" data-url="'.url('admin/notification/sendnotification',['id'=>$row->id]).'"> Send </a>'; 


                        $data[$key]['status'] = '<div class="text-center">'.$status.$notification.$edit.'</div>';                          
                } 
            }

            ## SEARCH HTML  
            
            $searchHTML['id']       =  '';     
            $searchHTML['patient_id']   =  '';

            $searchHTML['notify_time']   =  '';

            $searchHTML['title']  =  '';

            $searchHTML['content']  =  '';

            $searchHTML['status']      =  '';
            
            // $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';
            /*}*/   

            // $searchHTML['actions'] = $seachAction;
            array_unshift($data, $searchHTML);  

            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData); 
    }

     public function _storeOrUpdate($collection, $request)
    {

        $collection->patient_id      = 0;
        $collection->appointment_id  = 0;
        $collection->day             = $request->day;
        $collection->notify_time     = date("Y-m-d H:i:s",strtotime($request->notify_time));
        $collection->title           = $request->title;
        $collection->content         = $request->content;
        $collection->status          = $request->status;
        // dd($collection);
        //Save data
        $collection->save();
        return $collection;
        
    }//

     //Added below function on 17-jan-24 (18-jan-24)
     public function _storeOrUpdateNew($collection, $request)
    {

        $collection->patient_id      = $request->patient_id;
        $collection->appointment_id  = $request->appointment_id;
        $collection->day             = $request->day;
        $collection->notify_time     = date("Y-m-d H:i:s",strtotime($request->notify_time));
        $collection->title           = $request->title;
        $collection->content         = $request->content;
        $collection->status          = $request->status;
        // dd($collection);
        //Save data
        $collection->save();
        return $collection;        
    }

    public function sendNotification(Request $request, $id)    
    { 
        $id = $request->input('id'); 
        // dd($id);
        $collections = $this->BaseModel
                        ->join('appointment', 'appointment.id' , '=', 'appointment_has_notification.appointment_id')   
                        ->leftjoin('users', 'users.id' , '=', 'appointment.doctor_id')  
                        ->leftjoin('appointment_types', 'appointment_types.id' , '=', 'appointment.appointment_type_id')
                        ->leftjoin('patients', 'patients.id' , '=', 'appointment.patient_id')
                        // ->whereDate('appointment_has_notification.notify_time',date('Y-m-d'))
                        // ->where('appointment_has_notification.status',0)
                        ->where("appointment_has_notification.id",'=',$id)
                        ->get([
                                'appointment_has_notification.id as notification_id',
                                'appointment_has_notification.notify_time',
                                'appointment_has_notification.content',
                                'appointment_has_notification.title',
                                'appointment_has_notification.appointment_id',
                                'appointment.start_date',
                                'appointment.end_date',
                                'patients.first_name as patient_fname',
                                'patients.family_name as patient_lname',
                                'patients.salutation',
                                'patients.id as patient_id',
                                'users.first_name as doctor_fname',
                                'users.last_name as doctor_lname',
                                'users.img_path',
                                'users.doctor_speciality',
                                'appointment_types.name as aname',
                                'appointment_types.id as appointment_type_id'
                            ]);  


         // dump($collections);  
        // dd(date('Y-m-d H:i:s'));
        $current_time = date("Y-m-d H:i:s",time());
        if(!empty($collections)) 
        {
            foreach ($collections as $collection) 
            {

                $app_exams = $this->AppointmentHasExaminationsModel->with(['assignedExamination'])
                                ->where('appointment_id',$collection->appointment_id)
                                ->get();

                // dd($app_exams);
                $exam_exist  = 0;
                $exam_document_exist  = 0;
                $past_exist  = 0;
                $exams  = [];
                if(!empty($app_exams) && sizeof($app_exams)>0){
                    $exam_exist  = 1;
                    foreach ($app_exams as  $haskey=>$hasExamination) {

                        $exams[$haskey]['id'] = $hasExamination->assignedExamination->id;
                        $exams[$haskey]['name'] = $hasExamination->assignedExamination->name;
                        $exams[$haskey]['url'] = $hasExamination->assignedExamination->url;

                        //$document_path = self::getFilePath($hasExamination->assignedExamination->document_path);
                        //$document_path = storage_path().$hasExamination->assignedExamination->document_path

                        if(!empty($hasExamination->assignedExamination->document_name) && is_file(storage_path().$hasExamination->assignedExamination->document_path)){
                            $exam_document_exist  = 1;
                        }
                    }

                }
                if(strtotime($collection->notify_time)<strtotime($current_time))
                {
                    $past_exist  = 1;                        
                }
                // $end_time    = strtotime(date('Y-m-d H:i:s',strtotime($collection->notify_time))); 
                // $end_time    = date("Y-m-d H:i",strtotime($request->notify_time));
                // dd($end_time);
                // $start_time  = strtotime(date('Y-m-d H:i:s',time()));  
                // $start_time  = date("Y-m-d H:i",strtotime(now()));
                // dd($start_time);
                // $time_diff   = $end_time - $start_time ;
                // $time_diff   = $end_time->diffForHumans($start_time);
                // dd($time_diff);
                
                // if($time_diff>=0 && $time_diff<=60){
                    // dd($collection->notification_id);
                    $appointment_type   = $collection->aname;
                    $Doctor_name        = $collection->doctor_fname.' '.$collection->doctor_lname;
                    $appointment_date_time   = $collection->start_date;
                    $appointment_time = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr.";
                    $appointment_id     = $collection->appointment_id;
                    $appointment_type_id = $collection->appointment_type_id;
                    $doctor_speciality = $collection->doctor_speciality;

                    $doctor_image = asset('assets/admin/images/default-image.png');
                    
                    if(!empty(Config('ordination_id')))
                    {
                        $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);

                        $new_img_path = url('storage/app/tenancy/tenants/'.$getDatabaseName->uuid.'/'.$collection->img_path);
                    }
                    else
                    {
                        $new_img_path = url(storage_path().'/app/'.$collection->img_path);
                    }
                    //
                    //if (!empty($collection->img_path) && is_file(storage_path().'/app/'.$collection->img_path)) 
                    if (!empty($collection->img_path)) 
                    {

                        $doctor_image = self::getFilePath($collection->img_path);
                        //$doctor_image = url('/storage/app/'.$collection->img_path); 
                    }

                    if(!empty($collection->content)){
                        $content = $collection->content;
                        $title = $collection->title;
                    }else{
                        $title = 'Erinnerung an Ihren Termin:';
                        $content = 'Hallo, dein Termin fur '.' '.$appointment_type.' '.'mit Dr.'.' '.(string)$Doctor_name.' '.'ist an'.' '.(string)$appointment_time; 
                    }
                    //dd($start_time,$end_time,$time_diff,$collection->notification_id,$content);
                    $PatientId = $collection->patient_id;
                    $mobileId = $this->PatientHasDeviceModel
                                     ->where('patient_id',$PatientId)
                                     ->get(['device_id']);
                    
                    // dump($mobileId);

                    if(!empty($mobileId) && sizeof($mobileId))
                    {
                        $mobile_uuids = array_column($mobileId->toArray(), "device_id");

                        $player_ids   = $mobile_uuids;
                        $headings       = array("en" => (string)$title);
                        // Create an single string of all content
                        $content        = array(
                                                "en" => (string)$content
                                                );

                        $postData = array(
                                        "appointment_id" => $appointment_id,
                                        "date_time"     => $appointment_date_time,
                                        "doc_name"      => $Doctor_name,
                                        "doc_speciality" => $doctor_speciality,
                                        "appointment_type"    => $appointment_type,
                                        "appointment_type_id" => $appointment_type_id,
                                        "doc_img"             => $doctor_image,
                                        "exam_exist"          => $exam_exist,
                                        "exam_document_exist" => $exam_document_exist,
                                        "past_exist" => $past_exist,
                                        "exams" => $exams
                                        );
                        // print_r($postData);
                        // exit();
                        // appointment_id: 318
                        //doc_img: http://puregyn-test.lcmx.at/storage/app/profile-images/crop/20200626192329-gunnar gauff.jpeg
                        // date_time: Mon, 14 Sep 2020, 11:20
                        // doc_name: Gunnar Gauff
                        // doc_speciality: Facharzt für Gynäkologie
                        // appointment_type: Vorsorge
                        // appointment_type_id: 4
                        // $postData = json_encode($postData);
                        //["35ef8bc5-d2c0-4795-a5c8-b275fa40d53c"] for ios
                        $ios_img = array(
                                            "doc_img" => 'http://puregyn-test.lcmx.at/storage/app/setting-value/20200807165614-20200630233849-mouth.jpg'
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
                            "ios_attachments"      => $ios_img,
                            'ios_badge' => "1"
                        ); 

                        // dd($mobile_uuids,$fields);

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

                       if($data)  
                        {

                            //start commented on 10-nov-25
                            //send push notification to user and update status of that notification
                           /* $updateStatus = $this->BaseModel
                                        ->where('id',$collection->notification_id)
                                        ->update(['status'=>1,'one_signal_response'=>$data]);
                            $data = json_decode($data);
                            return response()->json($data);*/ 

                            //end commented on 10-nov-25            

                            //Added on 10-nov-25
            
                            $checkNotificationSend = json_decode($data, true);

                            // dump($checkNotificationSend);

                            if (isset($checkNotificationSend['id']) && !empty($checkNotificationSend['id']))
                            {
                                $updateStatus = $this->BaseModel
                                        ->where('id',$collection->notification_id)
                                        ->update(['status'=>1,'one_signal_response'=>$data]);
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => __('admin.MSG_SEND_PUSH_NOTIFICATION'),
                                        'onesignal' => $data
                                    ]);
                            }
                            else
                            {
                                
                                $errorMsg = __('admin.ERR_DEVICE_IDS');
                            
                                return response()->json([
                                'status' => 'error',
                                'message' => $errorMsg
                               ]);
                            }

                            //end Added on 10-nov-25


                        } else{ 

                            //commented on 10-nov-25
                            // $message = __('api.ERR_SOMETHING_WRONG');
                            // return response()->json($message);

                            //Added on 10-nov-25
                            return response()->json([
                                'status' => 'error',
                                'message' => __('api.ERR_SOMETHING_WRONG')
                            ]);


                        }//else data 
       
                    }//if mobileId
                    else{
                         //Added on 10-nov-25
                        return response()->json([
                            'status' => 'error',
                            'message' => __('admin.ERR_DEVICE_ID_NOT_EXISTS')
                        ]);
                    }
                                                          
                // }

            }                             

        }
    }

    
   

} 
