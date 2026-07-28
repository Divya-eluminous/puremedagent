<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang; 

// Models
use App\Models\PatientsModel;
use App\Models\ActivityLogModel; 
// Request
use App\Http\Requests\Admin\NotificationPatientsRequest; 
use App\Traits\GeneralTrait;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

// plugins 
use Hash; 
use Mail; 
use DB; 
use Auth;
use File; 
use Session;
use Config;


// Added below code new on 21 july 22 for send notification
use App\Mail\AppointmentMail; 
use WebSmsCom_Client;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_TextMessage;
use WebSmsCom_ParameterValidationException; 
use WebSmsCom_AuthorizationFailedException;
use WebSmsCom_ApiException;
use WebSmsCom_HttpConnectionException;
use WebSmsCom_UnknownResponseException;

use App\Mail\AppointmentNotificationMail;  //added on 6-dec-23
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024


class NotificationPatientController extends Controller
{
    private $BaseModel;
  
    use GeneralTrait;
   

    public function __construct(
        PatientsModel $PatientsModel,
        ActivityLogModel $ActivityLogModel                 
       
       
    )
    {
        $this->PatientsModel     = $PatientsModel; 
        $this->BaseModel         = $PatientsModel;
        $this->ActivityLogModel  = $ActivityLogModel;      
       
        $this->ViewData = [];
        $this->JsonData = []; 

        $this->ModuleTitle  =  __('admin.MANAGE_NOTIFICATION_PATIENT');
        $this->ModuleView   = 'admin.notificationpatients.';
        $this->ModulePath   = 'admin.notification-patient.';

        // Permission Middleware
        $this->middleware(['permission:notification-patients-list'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:notification-patients-add'], ['only' => ['create','store']]);

       
    } 

    public function index() 
    { 

        // Default site patients
        $this->ModuleTitle              =  __('admin.MANAGE_NOTIFICATION_PATIENT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle;
        $this->ViewData['moduleAction'] = $this->ModuleTitle;
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = __('admin.TITTLE_NOTIFICATION_PATIENT').' '.__('admin.TITLE_ADD_BUTTON');
        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    } 

    public function create()  
    { 
        $getPatients = $this->BaseModel->select('id','first_name','family_name','sendNotification')
                             ->where('status',1)
                             ->where('is_blocked',0)
                             ->orderBy('id','desc')                             
                             ->get()
                             ->toArray();
        // Default site settings 
        $this->ModuleTitle              =  __('admin.TITTLE_NOTIFICATION_PATIENT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        $this->ViewData['getPatients'] = isset($getPatients)?$getPatients:[]; 
        // dd($this->ViewData);
        // view file with data
        return view($this->ModuleView.'create', $this->ViewData);
    } 

    public function store(NotificationPatientsRequest $request)
    {

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_PATIENT_CREATE'); 
        try { 

            if(isset($request->user_name) && !empty($request->user_name))
            {
                 $update = $this->BaseModel->whereIn('id',$request->user_name)->update(['sendNotification'=>1]);
                 $update_otherfield = $this->BaseModel->whereNotIn('id',$request->user_name)->update(['sendNotification'=>0]);
            }
            if ($update) 
            {
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'index');
                $this->JsonData['msg']    = __('admin.PATIENT_SAVED');
            }
        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function show($id)
    {
        dd('show'); 
    }

    public function edit($encID)
    {
       
    }

    public function update(NotificationPatientsRequest $request, $encID)
    {
       
    }

     public function destroy($encID)
    {
        $newData=[];
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_PATIENT_DELETE'); 
        $id = base64_decode(base64_decode($encID));
        $BaseModel = $this->BaseModel->find($id);
        if($BaseModel)
        {
            $update = $this->BaseModel->where('id',$id)->update(['sendNotification'=>0]);
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated patient notification flag','Update',null,$newData);
            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.PATIENT_DELETED');
        }
        return response()->json($this->JsonData);
    }//destroy
 
   
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
                1 => 'patients.first_name',
                2 => 'patients.email'
               
            );


        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel->where('sendNotification',1);

            // get total count
            $countQuery = clone($modelQuery);
            $totalData  = $countQuery->count();

            # FILTER OPTIONS for specific field
            $custom_search = false;
            if (!empty($request->custom))
            {
                // dd(empty($request->custom['role']));ordination
                if (!empty($request->custom['fullname']))
                {
                    $name = explode(" ", $request->custom['fullname']);

                    if(!empty($name[1])){
                        $key[0]         = $name[0];
                        $key[1]         = $name[1];
                        $custom_search  = true;
                        $modelQuery     = $modelQuery
                        // ->where('patients.first_name','LIKE','%'.$key[0].'%')
                        // ->orWhere('patients.family_name','LIKE','%'.$key[1].'%');
                        ->whereRaw("MATCH(patients.first_name) AGAINST('".$key[0]."')")
                        ->whereRaw("MATCH(patients.family_name) AGAINST('".$key[1]."')")
                        ->orwhereRaw("MATCH(patients.first_name) AGAINST('".$request->custom['fullname']."')")
                        ->orwhereRaw("MATCH(patients.family_name) AGAINST('".$request->custom['fullname']."')");
                    } else{
                        $key[0]         = $name[0];
                        $custom_search  = true;
                        $modelQuery     = $modelQuery
                        // ->where('patients.first_name','LIKE','%'.$key[0].'%')
                        // ->orWhere('patients.family_name','LIKE','%'.$key[0].'%');
                         ->whereRaw("MATCH(patients.first_name) AGAINST('".$key[0]."')")
                        ->orwhereRaw("MATCH(patients.family_name) AGAINST('".$key[0]."')");
                    }
                }

                if (!empty($request->custom['email']))
                {
                    $custom_search  = true;
                    $key            = $request->custom['email'];
                    $modelQuery     = $modelQuery
                    ->where('patients.email','LIKE','%'.$key.'%');
                }

            }

            // Common filter options
            if (!empty($request->search))
            {
                if (!empty($request->search['value']))
                {
                    $search = $request->search['value'];

                     $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orWhere(DB::raw("CONCAT(patients.first_name, ' ', patients.family_name)"), 'LIKE', "%".$search."%");

                        $query->orwhere('patients.email', 'LIKE', '%'.$search.'%');
                      
                    });
                }
            }

            // get total filtered

            $filteredQuery  = clone($modelQuery);
            $totalFiltered  = $filteredQuery->count();

            // offset and limit
            $object = $modelQuery->orderBy($filter[$column], $dir)
                                 ->skip($start)
                                 ->take($length)
                                 ->get();


        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0)
            {
                foreach ($object as $key => $row)
                {
                        $data[$key]['id']           = $row->id;

                        $fname = ucfirst($row->first_name);
                        $familyName = ucfirst($row->family_name);
                        $data[$key]['fullname']  = '<span title="'.'concatenateNom'.'">'.$fname.' '.$familyName.'</span>';
                        $data[$key]['email']     = isset($row->email)?'<span title="'.$row->email.'">'.$row->email.'</span>':'-';

                        $edit="";
                        $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route($this->ModulePath.'destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';

                        // Check Permission
                        if(auth()->user()->can('notification-patients-add')){
                          
                            // $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route($this->ModulePath.'destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                        }
                        $data[$key]['actions'] = '<div class="text-center">'.$delete.'</div>';
                }//foreach
            }//if

            ## SEARCH HTML
            $val= '';
            $searchHTML['fullname']     ='';
            $searchHTML['id']           =  '';
            $searchHTML['fullname']     =  '<input type="text" class="form-control" id="fullname" value="'.($request->custom['fullname'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['email']        =  '<input type="text" class="form-control" id="email" value="'.($request->custom['email'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';
       
            $searchHTML['actions'] = $seachAction;
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
        $collection->family_name        = self::string_operation($request->family_name);
        $collection->first_name         = self::string_operation($request->first_name);
        $collection->save();    
        return $collection;  
    }//

      public function sendNotification_renamedon_2_april_24(Request $request)
    {   

         // log::info("in sendNotification");

        $is_reminder_execute = DB::table('settings')->where(['setting_key'=>'REMINDER_SETTING','status'=>'1'])->first();
        $channel = DB::table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->select('choice_of_channels')
                       ->first();

           // dump($channel);         

           dump(date('Y-m-d'));
           dump(date('H:i'));

        
            if(isset($is_reminder_execute) && !empty($is_reminder_execute))
            {

                $newCollection = DB::table('patient_has_service_reminder')
                        ->join('patients', 'patients.id' , '=', 'patient_has_service_reminder.patient_id')
                        ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                         ->where('patient_has_service_reminder.reminder_status','Set')
                          ->where('patients.sendNotification',1) 
                           ->whereNull('patients.deleted_at')  
                            ->where('patient_has_service_reminder.type','age')
                            ->whereNull('patient_has_service_reminder.deleted_at') // added this condition on 
                         ->where('patient_has_service_reminder.notification_count', '=', 0)
                         ->where(DB::raw('DATE(patient_has_service_reminder.reminder_date)'), '=', date('Y-m-d'))
                         ->where(DB::raw('TIME(patient_has_service_reminder.reminder_date)'), '=', date('H:i'))
                         ->get();




                 $collections =  DB::table('patient_has_service_reminder')
                            ->join('patients', 'patients.id' , '=', 'patient_has_service_reminder.patient_id')
                            ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                            //->whereRaw(DB::raw('(patient_has_service_reminder.reminder_date between "'.date('Y-m-d H:i:00', (time() - 60 * 5)).'" and "'.date('Y-m-d H:i:00', (time() + 60 * 5)).'")'))
                            //->whereRaw(DB::raw('patient_has_service_reminder.reminder_date="'.date("Y-m-d").'"')

                            //commented below on 1-april-24

                            // ->whereRaw(DB::raw('DATE(patient_has_service_reminder.reminder_date)="'.date("Y-m-d").'"'))

                             //added below on 1-april-24

                            // ->where(DB::raw('DATE(patient_has_service_reminder.reminder_date)'), '=', date('Y-m-d'))
                            // ->where(DB::raw('TIME(patient_has_service_reminder.reminder_date)'), '=', date('H:i'))


                         ->where(function($query) {
                            $query->where(function($subQuery) {
                                $subQuery->where('patient_has_service_reminder.appointment_id', 0)
                                         ->whereNotNull('patient_has_service_reminder.next_reminder_date')
                                         ->where('patient_has_service_reminder.notification_count', '>', 0)
                                         ->where(function($subSubQuery) {
                                             $subSubQuery->where(DB::raw('DATE(patient_has_service_reminder.next_reminder_date)'), '=', date('Y-m-d'))
                                                         ->where(DB::raw('TIME(patient_has_service_reminder.next_reminder_date)'), '=', date('H:i'));
                                         });
                            })->orWhere(function($subQuery) {
                                $subQuery->where(function($subSubQuery) {
                                    $subSubQuery->where(DB::raw('DATE(patient_has_service_reminder.reminder_date)'), '=', date('Y-m-d'))
                                                ->where(DB::raw('TIME(patient_has_service_reminder.reminder_date)'), '=', date('H:i'));
                                });
                            });
                        })




                           

                            


                            ->where('patient_has_service_reminder.reminder_status','Set')
                            //->where('patients.id',35608) // added this condition statically
                            ->where('patients.sendNotification',1) // added this condition statically
                            //->where('patient_has_service_reminder.appointment_id',"!=",0)

                            // ->where('patient_has_service_reminder.type','!=','control') //commented on 1-apr-24

                            ->where('patient_has_service_reminder.type','age')//added on 1-apr-24

                            ->whereNull('patients.deleted_at') // added this condition on 5-dec-23 (14-dec-23) 
                            ->whereNull('patient_has_service_reminder.deleted_at') // added this condition on 5-dec-23 (14-dec-23) 

                            // ->select(['patient_has_service_reminder.id as reminder_id','patient_has_service_reminder.reminder_date','patients.id as patient_id','patients.mobile_no','patients.age as patient_age','patients.country_code','patients.email','examinations.name as name','examinations.id as exam_id','patients.sendMail','patients.sendSMS','patient_has_service_reminder.appointment_id','patient_has_service_reminder.status','patient_has_service_reminder.reminder_status',                                'patient_has_service_reminder.service_id','patient_has_service_reminder.type'])
                            // ->toSql();


                            ->get(['patient_has_service_reminder.id as reminder_id','patient_has_service_reminder.reminder_date','patients.id as patient_id','patients.mobile_no','patients.age as patient_age','patients.country_code','patients.email','examinations.name as name','examinations.id as exam_id','patients.sendMail','patients.sendSMS','patient_has_service_reminder.appointment_id','patient_has_service_reminder.status','patient_has_service_reminder.reminder_status',

                                'patient_has_service_reminder.service_id',  //added on 1-apr-24 for #2 issue only send active notification
                                'patient_has_service_reminder.type',  
                                'patient_has_service_reminder.next_reminder_date',
                            ]);

                 dump($collections);  

                //dump($collections->toArray());         

                 $currentDate = Date('d-m-Y H:i'); //commented on 1-apr-24 temporary

                // $currentDate = Date('d-m-Y'); //added on 1-apr-24         


                if(!empty($collections->toArray()))
                {
                    if(!empty($collections))
                    {
                        foreach ($collections as $key => $value)
                        {
                              //dump($value->patient_id);


                            //Added by Shyam 01-02-22
                            // log::info("Send Notify");

                            // Below condition added on 5-dec-23 (14-dec-23) for active services needs to have the reminder send

                              $nextReminderDate='';

                            if(empty($value->next_reminder_date))
                            {

                                $reminderDate = Date('d-m-Y H:i',strtotime($value->reminder_date));  //commented temporaryon 1-apr-24
                            }
                            else
                            {
                                $reminderDate = Date('d-m-Y H:i',strtotime($value->next_reminder_date));  //commented temporaryon 1-apr-24
                            }

                           

                             // dump("reminderDate====>");
                             // dump($reminderDate);



                           // $reminderDate = Date('d-m-Y',strtotime($value->reminder_date));  

                             $status =  'aktiv'; //added on 1-apr-24

                            //commented on 1-apr-24
                            /*if((strtotime($reminderDate) < strtotime($currentDate)) || $value->appointment_id==0)
                            {
                                $status =  'aktiv';
                            }*/


                            if($value->appointment_id==0 && $value->status=='deactivate')
                            {
                                $status ='inaktiv';
                            }

                            //commented on 1-apr-24

                            /*if($value->reminder_status=='ignore' && $value->status!='deactivate')
                            {
                                $status='ignored';
                            }  
                            if($value->reminder_status=='ignore' && $value->status=='deactivate') 
                            {
                                $status='inaktiv';
                            } 
                            if(($value->appointment_id==0 && $value->status=='deactivate'))
                            {
                                $status ='inaktiv';
                            }*/





                            // above condition added on 5-dec-23 (14-dec-23) for active services needs to have the reminder send

                            // log::info("status");
                            // log::info($status);

                            // Below if condition added on 5-dec-23 (14-dec-23) for active services needs to have the reminder send

                            dump("status===>");
                            dump($status);

                            if($status=="aktiv")
                            {
                               //  log::info("in sendNotification status active condition....");

                                $checkReminder = 'Send';
                                $checkPatientAge = DB::table('preferred_channels_for_reminders_setting')->where(['service_id'=>$value->exam_id,'activated_reminder'=>'age'])->first();
                                $ageFrom = $ageTo = 0;
                                if(!empty($checkPatientAge->age_from))
                                {
                                    $ageFrom = $checkPatientAge->age_from;
                                }
                                if(!empty($checkPatientAge->age_to))
                                {
                                    $ageTo = $checkPatientAge->age_to;
                                }
                                if(!empty($value->patient_age) && $ageFrom > 0 && $ageTo > 0 && ($value->patient_age < $ageFrom || $value->patient_age > $ageTo))
                                {
                                    $checkReminder = 'Not Send';
                                }
                               // dump("Send Notify");
                                //Added by Shyam 01-02-22
                                $reminder_active = DB::table('patients')->where(['id'=>$value->patient_id,'reminder_active'=>'1'])->first();


                                $sendEmailFlag=$sendSmsFlag=$updateCount=0; //added on 1-apr-24


                                if($value->appointment_id==0)
                                {

                                  /****start**code for apponitment id**0**flags**************/

                                    $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                                           ->where('service_id',$value->service_id)
                                                           ->where('activated_reminder',$value->type)
                                                           ->first();

                                    if(isset($reminderSetting) && !empty($reminderSetting))
                                    {
                                        $age_number_of_interval = $reminderSetting->age_number_of_interval;   

                                        dump("age_number_of_interval===>");
                                        dump($age_number_of_interval);   

                                        $getReminderCount =  DB::connection('tenant')
                                                    ->table('patient_has_service_reminder')
                                                    ->select('notification_count')
                                                    ->where('id',$value->reminder_id)
                                                    ->where('patient_id',$value->patient_id)
                                                    ->where('service_id',$value->service_id)
                                                    ->where('type',$value->type)
                                                    ->where('appointment_id','=',0)
                                                    ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                    ->first();

                                        $cnt = $getReminderCount->notification_count; 

                                        dump("cnt===>");
                                        dump($cnt);    

                                         if($cnt<$age_number_of_interval)
                                        {
                                            $updateCount = $cnt+1;       

                                            dump("updateCount===>");
                                            dump($updateCount);   

                                            /****start*code for change reminder date***********/
                                            if($cnt>=0)
                                            {

                                                 $checkNextReminders =  DB::connection('tenant')
                                                ->table('patient_has_service_reminder')
                                                ->where('patient_id',$value->patient_id)
                                                ->where('service_id',$value->service_id)
                                                ->where('type',$value->type)
                                                ->where('appointment_id','!=',0)
                                                ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                ->get();

                                                dump($checkNextReminders);

                                                if(isset($checkNextReminders) && !empty($checkNextReminders) && count($checkNextReminders)>0)
                                                {
                                                    dump('in checkNextReminders...');

                                                }//if checkNextReminders
                                                else
                                                {
                                                    dump('else checkNextReminders...');

                                                    $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                                       ->where('service_id',$value->service_id)
                                                       ->where('activated_reminder',$value->type)
                                                       ->first();

                                                     //  dump($reminderSetting);

                                                    $age_time_interval = $reminderSetting->age_time_interval;
                                                    $age_time_interval_frequency = $reminderSetting->age_time_interval_frequency_type;

                                                    $period_date = Date('d-m-Y H:i:s',strtotime($value->reminder_date));    

                                                    if(empty($value->next_reminder_date)){

                                                        dump('in empty next reminder date...');

                                                         $value4_days = $this->_getDate($period_date,$age_time_interval,$age_time_interval_frequency);

                                                         $nextReminderDate = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($reminderDate)) . " +".(int)$value4_days." day"));
                                                    }
                                                    else
                                                    {
                                                        dump('in not empty next reminder date...');

                                                        $value4_days = $this->_getDate($value->next_reminder_date,$age_time_interval,$age_time_interval_frequency);

                                                        $nextReminderDate = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($value->next_reminder_date)) . " +".(int)$value4_days." day"));
                                                    }

                                                    dump($value4_days);
                                                    dump("nextReminderDate ===>");
                                                    dump($nextReminderDate);


                                                }//else if
                                            }//if updatecount is greater than 0

                                            /********end*code for change reminder date*********/

                                            $sendEmailFlag=1;
                                            $sendSmsFlag=1;

                                        }//if 
                                        else
                                        {
                                            $sendEmailFlag=0;
                                            $sendSmsFlag=0;
                                        }

                                    }//if isset reminderSetting                      

                                 /****end***code for appointment id 0**flags************/
                                    

                                }//if value of appointment id 0
                                else
                                {
                                    $sendEmailFlag=1;
                                    $sendSmsFlag=1;

                                }//else

                                // dump("sendEmailFlag===>");
                                // dump($sendEmailFlag);
                                // dump("sendSmsFlag===>");
                                // dump($sendSmsFlag);

                                //  dump("reminder_active===>");
                                // dump($reminder_active);

                                //  dump("checkReminder===>");
                                // dump($checkReminder);

                                 dump("reminderDate===>");
                                dump($reminderDate);

                                dump("currentDate===>");
                                dump($currentDate);

                                 dump("nextReminderDate===>");
                                dump($nextReminderDate);


                                // if($reminder_active && $checkReminder == 'Send') //Added by Shyam 01-02-22

                                // if($reminder_active && $checkReminder == 'Send' && $reminderDate==$currentDate) //Added by Shyam 01-02-22

                                 if($reminder_active && $checkReminder == 'Send' && (($reminderDate==$currentDate && $value->appointment_id!=0)) || ($value->appointment_id==0 && $reminderDate==$currentDate)) 
                                {

                                    dump("in ...............");

                                    // check patinet have installed app
                                    $mobileId = DB::table('patient_has_device')
                                                ->where('patient_id',$value->patient_id)
                                                ->get(['device_id']);
                                    if(!empty($mobileId) && count($mobileId))
                                    {
                                        //PUSHNOTIFICATION
                                        //self::_sendPushNotification($mobileId,$value); //commented on 1-apr-24
                                    }
                                    if($channel->choice_of_channels == 'sms')
                                    {
                                       // dump("in sms call before...");

                                        if (!empty($value->mobile_no) && $value->sendSMS==1)
                                        {
                                            $country_code = $value->country_code;
                                            if(!empty($country_code))
                                            {
                                                $country_code = str_replace("00", "",$value->country_code);
                                            }
                                            elseif(empty($country_code) || $country_code=='0')
                                            {
                                                $country_code = '43'; //Austria country code
                                            }
                                            $country_code = str_replace("+", "",$country_code);
                                            $phone_no   = $country_code."".str_replace("-", "",$value->mobile_no);


                                             if($sendSmsFlag==1) //added on 1-apr-24
                                             {
                                               //dump("in before..sms function call.");         
                                               self::_sendSms($phone_no,$value,$updateCount,$nextReminderDate);

                                             }//if 




                                        }
                                        elseif (!empty($value->email) && $value->sendMail==1)
                                        {
                                             if($sendEmailFlag==1)  //added on 1-apr-24
                                            {
                                               self::_sendMail($value,$updateCount,$nextReminderDate);

                                            }//if sendEmailFlag
                                        }
                                    }
                                    elseif($channel->choice_of_channels == 'email')
                                    {
                                        //dump("in email call before...");

                                        if (!empty($value->email) && $value->sendMail==1)
                                        {
                                            if($sendEmailFlag==1) //added on 1-apr-24
                                            {
                                                 // dump("in send email call before...");

                                                  self::_sendMail($value,$updateCount,$nextReminderDate);
                                            }
                                        }
                                        elseif (!empty($value->mobile_no) && $value->sendSMS==1)
                                        {
                                            // if (!empty($value->mobile_no) && $value->sendSMS==1) //For testing only
                                            $country_code = $value->country_code;
                                            if(!empty($country_code))
                                            {
                                                $country_code = str_replace("00", "",$value->country_code);
                                            }
                                            elseif(empty($country_code) || $country_code=='0')
                                            {
                                                $country_code = '43'; //Austria country code
                                            }
                                            $country_code = str_replace("+", "",$country_code);
                                            $phone_no   = $country_code."".str_replace("-", "",$value->mobile_no);

                                            if($sendSmsFlag==1)
                                            {
                                               self::_sendSms($phone_no,$value,$updateCount,$nextReminderDate);

                                            }//if sendSmsFlag

                                        }
                                    }
                                }//if reminder active and send 



                            }//if status is aktiv

                        }//foreach

                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['url']    =  route($this->ModulePath.'index');
                        $this->JsonData['msg']    = __('admin.NOTIFICATION_SEND_SUCCESS');  

                        return response()->json($this->JsonData);

                    }//if not empty collection
                }//if
                else{
                    $this->JsonData['status'] = __('admin.RESP_ERROR');
                    $this->JsonData['url']    =  route($this->ModulePath.'index');
                    $this->JsonData['msg']    = __('admin.ERR_PATIENT_NOT_FOUND');
                    return response()->json($this->JsonData);
                }
            }
    }//_sendPushNotificationtification




    public function sendNotification(Request $request)
    {   

         // log::info("in sendNotification");

        $is_reminder_execute = DB::table('settings')->where(['setting_key'=>'REMINDER_SETTING','status'=>'1'])->first();
        $channel = DB::table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->select('choice_of_channels')
                       ->first();

           // dump($channel);         

           // dump(date('Y-m-d'));
           // dump(date('H:i'));

        
            if(isset($is_reminder_execute) && !empty($is_reminder_execute))
            {

               
                 $collections =  DB::table('patient_has_service_reminder')
                            ->join('patients', 'patients.id' , '=', 'patient_has_service_reminder.patient_id')
                            ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                            //->whereRaw(DB::raw('(patient_has_service_reminder.reminder_date between "'.date('Y-m-d H:i:00', (time() - 60 * 5)).'" and "'.date('Y-m-d H:i:00', (time() + 60 * 5)).'")'))
                            //->whereRaw(DB::raw('patient_has_service_reminder.reminder_date="'.date("Y-m-d").'"')

                            //commented below on 1-april-24

                            // ->whereRaw(DB::raw('DATE(patient_has_service_reminder.reminder_date)="'.date("Y-m-d").'"'))

                             //added below on 1-april-24

                            // ->where(DB::raw('DATE(patient_has_service_reminder.reminder_date)'), '=', date('Y-m-d'))
                            // ->where(DB::raw('TIME(patient_has_service_reminder.reminder_date)'), '=', date('H:i'))

                            ->where(function($query) {
                                    $query->where(function($query) {
                                        $query->whereDate('patient_has_service_reminder.reminder_date', '=', date('Y-m-d'))
                                              ->whereTime('patient_has_service_reminder.reminder_date', '=', date('H:i'));
                                    })
                                    ->orWhere(function($query) {
                                        $query->whereDate('patient_has_service_reminder.next_reminder_date', '=', date('Y-m-d'))
                                              ->whereTime('patient_has_service_reminder.next_reminder_date', '=', date('H:i'));
                                    });
                            })

                            ->where('patient_has_service_reminder.reminder_status','Set')
                            //->where('patients.id',35608) // added this condition statically
                            ->where('patients.sendNotification',1) 
                            ->where('patient_has_service_reminder.type','age')

                            ->whereNull('patients.deleted_at') 
                            ->whereNull('patient_has_service_reminder.deleted_at') 

                            // ->select(['patient_has_service_reminder.id as reminder_id','patient_has_service_reminder.reminder_date','patients.id as patient_id','patients.mobile_no','patients.age as patient_age','patients.country_code','patients.email','examinations.name as name','examinations.id as exam_id','patients.sendMail','patients.sendSMS','patient_has_service_reminder.appointment_id','patient_has_service_reminder.status','patient_has_service_reminder.reminder_status',                                'patient_has_service_reminder.service_id','patient_has_service_reminder.type'])
                            // ->toSql();


                            ->get(['patient_has_service_reminder.id as reminder_id','patient_has_service_reminder.reminder_date','patients.id as patient_id','patients.mobile_no','patients.age as patient_age','patients.country_code','patients.email','examinations.name as name','examinations.id as exam_id','patients.sendMail','patients.sendSMS','patient_has_service_reminder.appointment_id','patient_has_service_reminder.status','patient_has_service_reminder.reminder_status',

                                'patient_has_service_reminder.service_id',  //added on 1-apr-24 for #2 issue only send active notification
                                'patient_has_service_reminder.type',  
                                'patient_has_service_reminder.next_reminder_date',
                            ]);

                // dump($collections);  

                //dump($collections->toArray());         

                 $currentDate = Date('d-m-Y H:i'); //commented on 1-apr-24 temporary

                // $currentDate ='30-04-2024 07:49';     


                if(!empty($collections->toArray()))
                {
                    if(!empty($collections))
                    {
                        foreach ($collections as $key => $value)
                        {
                              //dump($value->patient_id);


                            //Added by Shyam 01-02-22
                            // log::info("Send Notify");

                            // Below condition added on 5-dec-23 (14-dec-23) for active services needs to have the reminder send

                            $nextReminderDate='';

                            
                             // dump("reminderDate====>");
                             // dump($reminderDate);

                           // $reminderDate = Date('d-m-Y',strtotime($value->reminder_date));  

                            $status =  'aktiv'; //added on 1-apr-24

                            //commented on 1-apr-24
                            /*if((strtotime($reminderDate) < strtotime($currentDate)) || $value->appointment_id==0)
                            {
                                $status =  'aktiv';
                            }*/


                            if($value->appointment_id==0 && $value->status=='deactivate')
                            {
                                $status ='inaktiv';
                            }

                            //commented on 1-apr-24

                            /*if($value->reminder_status=='ignore' && $value->status!='deactivate')
                            {
                                $status='ignored';
                            }  
                            if($value->reminder_status=='ignore' && $value->status=='deactivate') 
                            {
                                $status='inaktiv';
                            } 
                            if(($value->appointment_id==0 && $value->status=='deactivate'))
                            {
                                $status ='inaktiv';
                            }*/





                            // above condition added on 5-dec-23 (14-dec-23) for active services needs to have the reminder send

                            // log::info("status");
                            // log::info($status);

                            // Below if condition added on 5-dec-23 (14-dec-23) for active services needs to have the reminder send

                            // dump("status===>");
                            // dump($status);

                            // dump("patient_id===>");
                            // dump($value->patient_id);

                            // dump("service_id===>");
                            // dump($value->service_id);

                            if($status=="aktiv")
                            {
                               //  log::info("in sendNotification status active condition....");

                                $checkReminder = 'Send';
                                $checkPatientAge = DB::table('preferred_channels_for_reminders_setting')->where(['service_id'=>$value->exam_id,'activated_reminder'=>'age'])->first();
                                $ageFrom = $ageTo = 0;
                                if(!empty($checkPatientAge->age_from))
                                {
                                    $ageFrom = $checkPatientAge->age_from;
                                }
                                if(!empty($checkPatientAge->age_to))
                                {
                                    $ageTo = $checkPatientAge->age_to;
                                }
                                if(!empty($value->patient_age) && $ageFrom > 0 && $ageTo > 0 && ($value->patient_age < $ageFrom || $value->patient_age > $ageTo))
                                {
                                    $checkReminder = 'Not Send';
                                }
                               // dump("Send Notify");
                                //Added by Shyam 01-02-22
                                $reminder_active = DB::table('patients')->where(['id'=>$value->patient_id,'reminder_active'=>'1'])->first();


                                $sendEmailFlag=$sendSmsFlag=$updateCount=0; //added on 1-apr-24

                                if(empty($value->next_reminder_date) && $value->appointment_id==0)
                                {
                                   // dump('in 1');

                                    $reminderDate = Date('d-m-Y H:i',strtotime($value->reminder_date)); 
                                }
                                else if($value->appointment_id==0)
                                {
                                   // dump('in 2');
                                    $reminderDate = Date('d-m-Y H:i',strtotime($value->next_reminder_date));  
                                }
                                else
                                {
                                  //  dump('in 3');
                                    $reminderDate = Date('d-m-Y H:i',strtotime($value->reminder_date));  
                                }


                                 // dump("reminder date in conditions===>");
                                 // dump($reminderDate);


                                if($value->appointment_id==0 && $reminderDate==$currentDate)
                                {

                                  /****start**code for apponitment id**0**flags**************/

                                    $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                                           ->where('service_id',$value->service_id)
                                                           ->where('activated_reminder',$value->type)
                                                           ->first();

                                    if(isset($reminderSetting) && !empty($reminderSetting))
                                    {
                                        $age_number_of_interval = $reminderSetting->age_number_of_interval;   

                                        // dump("age_number_of_interval===>");
                                        // dump($age_number_of_interval);   

                                        $getReminderCount =  DB::connection('tenant')
                                                    ->table('patient_has_service_reminder')
                                                    ->select('notification_count')
                                                    ->where('id',$value->reminder_id)
                                                    ->where('patient_id',$value->patient_id)
                                                    ->where('service_id',$value->service_id)
                                                    ->where('type',$value->type)
                                                    ->where('appointment_id','=',0)
                                                    ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                    ->first();

                                        $cnt = $getReminderCount->notification_count; 

                                        // dump("cnt===>");
                                        // dump($cnt);    

                                         if($cnt<$age_number_of_interval)
                                        {
                                            $updateCount = $cnt+1;       

                                            // dump("updateCount===>");
                                            // dump($updateCount);   

                                            /****start*code for change reminder date***********/
                                            if($cnt>=0)
                                            {

                                                 $checkNextReminders =  DB::connection('tenant')
                                                ->table('patient_has_service_reminder')
                                                ->where('patient_id',$value->patient_id)
                                                ->where('service_id',$value->service_id)
                                                ->where('type',$value->type)
                                                ->where('appointment_id','!=',0)
                                                ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                ->get();

                                               // dump($checkNextReminders);

                                                if(isset($checkNextReminders) && !empty($checkNextReminders) && count($checkNextReminders)>0)
                                                {
                                                   // dump('in checkNextReminders...');

                                                }//if checkNextReminders
                                                else
                                                {
                                                   // dump('else checkNextReminders...');

                                                    $reminderSetting = DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                                                       ->where('service_id',$value->service_id)
                                                       ->where('activated_reminder',$value->type)
                                                       ->first();

                                                     //  dump($reminderSetting);

                                                    $age_time_interval = $reminderSetting->age_time_interval;
                                                    $age_time_interval_frequency = $reminderSetting->age_time_interval_frequency_type;

                                                    $period_date = Date('d-m-Y H:i:s',strtotime($value->reminder_date));    

                                                    if(empty($value->next_reminder_date)){

                                                       // dump('in empty next reminder date...');

                                                         $value4_days = $this->_getDate($period_date,$age_time_interval,$age_time_interval_frequency);

                                                         $nextReminderDate = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($reminderDate)) . " +".(int)$value4_days." day"));
                                                    }
                                                    else
                                                    {
                                                       // dump('in not empty next reminder date...');

                                                        $value4_days = $this->_getDate($value->next_reminder_date,$age_time_interval,$age_time_interval_frequency);

                                                        $nextReminderDate = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($value->next_reminder_date)) . " +".(int)$value4_days." day"));
                                                    }

                                                    // dump($value4_days);
                                                    // dump("nextReminderDate ===>");
                                                    // dump($nextReminderDate);


                                                }//else if
                                            }//if updatecount is greater than 0

                                            /********end*code for change reminder date*********/

                                            $sendEmailFlag=1;
                                            $sendSmsFlag=1;

                                        }//if 
                                        else
                                        {
                                            $sendEmailFlag=0;
                                            $sendSmsFlag=0;
                                        }

                                    }//if isset reminderSetting                      

                                 /****end***code for appointment id 0**flags************/
                                    

                                }//if value of appointment id 0
                                else
                                {
                                    $sendEmailFlag=1;
                                    $sendSmsFlag=1;

                                }//else

                                // dump("sendEmailFlag===>");
                                // dump($sendEmailFlag);
                                // dump("sendSmsFlag===>");
                                // dump($sendSmsFlag);

                                //  dump("reminder_active===>");
                                // dump($reminder_active);

                                //  dump("checkReminder===>");
                                // dump($checkReminder);

                                //  dump("reminderDate===>");
                                // dump($reminderDate);

                                // dump("currentDate===>");
                                // dump($currentDate);

                                //  dump("nextReminderDate===>");
                                // dump($nextReminderDate);


                                // if($reminder_active && $checkReminder == 'Send') //Added by Shyam 01-02-22

                                // if($reminder_active && $checkReminder == 'Send' && $reminderDate==$currentDate) //Added by Shyam 01-02-22

                                 if($reminder_active && $checkReminder == 'Send' && $reminderDate==$currentDate) 
                                {

                                   // dump("in ...............");

                                    // check patinet have installed app
                                    $mobileId = DB::table('patient_has_device')
                                                ->where('patient_id',$value->patient_id)
                                                ->get(['device_id']);
                                    if(!empty($mobileId) && count($mobileId))
                                    {
                                        //PUSHNOTIFICATION
                                        //self::_sendPushNotification($mobileId,$value); //commented on 1-apr-24
                                    }
                                    if($channel->choice_of_channels == 'sms')
                                    {
                                       // dump("in sms call before...");

                                        if (!empty($value->mobile_no) && $value->sendSMS==1)
                                        {
                                            $country_code = $value->country_code;
                                            if(!empty($country_code))
                                            {
                                                $country_code = str_replace("00", "",$value->country_code);
                                            }
                                            elseif(empty($country_code) || $country_code=='0')
                                            {
                                                $country_code = '43'; //Austria country code
                                            }
                                            $country_code = str_replace("+", "",$country_code);
                                            $phone_no   = $country_code."".str_replace("-", "",$value->mobile_no);


                                             if($sendSmsFlag==1) //added on 1-apr-24
                                             {
                                               //dump("in before..sms function call.");         
                                               self::_sendSms($phone_no,$value,$updateCount,$nextReminderDate);

                                             }//if 

                                        }
                                        elseif (!empty($value->email) && $value->sendMail==1)
                                        {
                                             if($sendEmailFlag==1)  //added on 1-apr-24
                                            {
                                               self::_sendMail($value,$updateCount,$nextReminderDate);

                                            }//if sendEmailFlag
                                        }
                                    }
                                    elseif($channel->choice_of_channels == 'email')
                                    {
                                        //dump("in email call before...");

                                        if (!empty($value->email) && $value->sendMail==1)
                                        {
                                            if($sendEmailFlag==1) //added on 1-apr-24
                                            {
                                                 // dump("in send email call before...");

                                                  self::_sendMail($value,$updateCount,$nextReminderDate);
                                            }
                                        }
                                        elseif (!empty($value->mobile_no) && $value->sendSMS==1)
                                        {
                                            // if (!empty($value->mobile_no) && $value->sendSMS==1) //For testing only
                                            $country_code = $value->country_code;
                                            if(!empty($country_code))
                                            {
                                                $country_code = str_replace("00", "",$value->country_code);
                                            }
                                            elseif(empty($country_code) || $country_code=='0')
                                            {
                                                $country_code = '43'; //Austria country code
                                            }
                                            $country_code = str_replace("+", "",$country_code);
                                            $phone_no   = $country_code."".str_replace("-", "",$value->mobile_no);

                                            if($sendSmsFlag==1)
                                            {
                                               self::_sendSms($phone_no,$value,$updateCount,$nextReminderDate);

                                            }//if sendSmsFlag

                                        }
                                    }
                                }//if reminder active and send 



                            }//if status is aktiv

                        }//foreach

                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['url']    =  route($this->ModulePath.'index');
                        $this->JsonData['msg']    = __('admin.NOTIFICATION_SEND_SUCCESS');  

                        return response()->json($this->JsonData);

                    }//if not empty collection
                }//if
                else{
                    $this->JsonData['status'] = __('admin.RESP_ERROR');
                    $this->JsonData['url']    =  route($this->ModulePath.'index');
                    $this->JsonData['msg']    = __('admin.ERR_PATIENT_NOT_FOUND');
                    return response()->json($this->JsonData);
                }
            }
    }//_sendPushNotificationtification




      /*-----------------------------------
    |  Send push notification
    -------------------------------------------------*/
    public function _sendPushNotification($mobileId,$value,$updateCount,$nextReminderDate)
    {
       // dd("in _sendPushNotification");

       // dump("in _sendPushNotification..function...");

        $collection =  DB::table('patient_has_service_reminder')
                        ->join('appointment', 'appointment.id' , '=', 'patient_has_service_reminder.appointment_id')  
                        ->join('patients', 'patients.id' , '=', 'patient_has_service_reminder.patient_id')
                        ->join('appointment_types', 'appointment_types.id' , '=', 'appointment.appointment_type_id')
                        ->join('users', 'users.id' , '=', 'appointment.doctor_id')
                        ->where('patient_has_service_reminder.id',$value->reminder_id)

                        ->where('patients.sendNotification',1) // added this condition static
                       // ->where('patients.id',33493)  // added this condition static

                        ->first([
                            'patient_has_service_reminder.service_id',
                            'appointment.start_date',
                            'appointment.end_date',
                            'patients.first_name as patient_fname',
                            'patients.family_name as patient_lname',
                            'patients.salutation',
                            'patients.id as patient_id',
                            'appointment_types.name as aname',
                            'appointment_types.id as appointment_type_id',
                            'users.first_name as doctor_fname',
                            'users.last_name as doctor_lname',
                            'users.img_path',
                            'users.doctor_speciality',
                        ]);

        $appointment_type   = $collection->aname ?? '';
        //$Doctor_name        = $collection->doctor_fnamcAW1e.' '.$collection->doctor_lname;

        $fname =  $collection->doctor_fname ?? '';
        $lname = $collection->doctor_lname ?? '';
        $Doctor_name  = $fname." ".$lname;
        $appointment_date_time   = $collection->start_date ?? '';
        $appointment_time = '';
        if(!empty($appointment_date_time))
        {
            $appointment_time = date('d.F',strtotime($collection->start_date)).",um ".date('H:i',strtotime($collection->start_date))." Uhr.";
        }
        $appointment_id     = $collection->appointment_id ?? '';
        $appointment_type_id = $collection->appointment_type_id ?? '';
        $doctor_speciality = $collection->doctor_speciality ?? '';
        // Examination Details
        $exams= [];
        $exam_name = $value->name;
        // end
        $doctor_image = asset('assets/admin/images/default-image.png');
        if (!empty($collection->img_path) && is_file(storage_path().'/app/'.$collection->img_path)) 
        {
            $doctor_image = url('/storage/app/'.$collection->img_path); 
        }

        $title = 'Erinnerung an Ihren Termin';
        // Patint Details
        $patientDetails = DB::table('patients')->find($value->patient_id);
        $patient_name = $patientDetails->first_name .' '.$patientDetails->family_name;
        // GET CONTENT
        $textContent = DB::table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->first();

        $content = 'Hallo '.$patient_name.', '.$textContent->reminder_push_notification_text." ".$exam_name;

       // dd($content);

        $mobile_uuids = array_column($mobileId->toArray(), "device_id");

        $player_ids   = $mobile_uuids;
        $headings     = array("en" => (string)$title);
        // Create an single string of all content
        $content      = array(
                                "en" => (string)$content
                                );
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
        //dump("out");

        // DB::connection('tenant')->table('ActivityLogModel')->addLog('reminder for push notification','has sent push notification','sent',null,$postData);
        //send push notification to user and update status of that notification
        $updateStatus = DB::table('patient_has_service_reminder')->find($value->reminder_id);
        //commented by me


        //commented on 1-apr-24
        /*if($value->appointment_id!=0){
            $responseRecord = DB::table('patient_has_service_reminder')
              ->where('id',$value->reminder_id)
              ->update(['reminder_status'=>'executed','updated_at'=>date('Y-m-d H:i:s'),'media'=>'push_notification']);
        }*/


        //added below condition on 1-apr-24
        if($value->appointment_id==0)
        {
             if(isset($updateCount))
             {
                $responseRecord = DB::table('patient_has_service_reminder')
                  ->where('id',$value->reminder_id)
                  ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'push_notification','notification_count'=>$updateCount]);

                $getReminderCount =  DB::connection('tenant')
                                                            ->table('patient_has_service_reminder')
                                                            ->select('notification_count')
                                                            ->where('id',$value->reminder_id)
                                                            ->where('patient_id',$value->patient_id)
                                                            ->where('service_id',$value->service_id)
                                                            ->where('type',$value->type)
                                                            ->where('appointment_id','=',0)
                                                            ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                            ->first();

                $cnt = $getReminderCount->notification_count; 

                // dump("cnt==of notification_count====>");
                // dump($cnt); 

                if(isset($cnt) && $cnt>0 && !empty($nextReminderDate))
                {
                     // dump("nextReminderDate=in send email function=>");
                     // dump($nextReminderDate);     

                     $responseRecord = DB::table('patient_has_service_reminder')
                      ->where('id',$value->reminder_id)
                      ->update(['next_reminder_date'=>$nextReminderDate]);

                }//if cnt


             }//if
        
        }
        else
        {
             $responseRecord = DB::table('patient_has_service_reminder')
              ->where('id',$value->reminder_id)
              ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'push_notification']);
        }//else 




    }

    /*-----------------------------------
    |  Send mail
    -------------------------------------------------*/
    public function _sendMail($value,$updateCount,$nextReminderDate)
    {
        // dump("in _sendMail..function...");

        // dump($value);

       $patientDetails = DB::table('patients')->find($value->patient_id);
        $name = $patientDetails->first_name.' '.$patientDetails->family_name;
        $email = $patientDetails->email;

        $textContent = DB::table('preferred_channels_for_reminders_setting')
                       ->where('type','global')
                       ->first();

        // $text   = $textContent->reminder_mail_notification_text."<br/><b><a href='https://puregyn.puremed.biz/'>".$value->name."</a></b>";
        $text   = $textContent->reminder_mail_notification_text."<br/><b><a href='https://puregyn.puremed.biz/oa/services/".base64_encode($value->exam_id)."'>".$value->name."</a></b>";               
        
         $serviceName = $value->name?$value->name:''; //added on 6-dec-23      
        //$result = Mail::to($email)->send(new AppointmentMail($name,$text));//commented on 6-dec-23
        $result = Mail::to($email)->send(new AppointmentNotificationMail($name,$text,$serviceName)); //added on 6-dec-23

        // DB::connection('tenant')->table('ActivityLogModel')->addLog('reminder for email','has sent email','sent',null,$value);
        $updateStatus = DB::table('patient_has_service_reminder')->find($value->reminder_id);
        
        // commented by me

        //commented on 1-apr-24 
       /* if($value->appointment_id!=0){
            $responseRecord = DB::table('patient_has_service_reminder')
              ->where('id',$value->reminder_id)
              ->update(['reminder_status'=>'executed','updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail']);
        }*/




        //added on 1-apr-24
        if($value->appointment_id==0)
        {
            if(isset($updateCount))
            {

                $responseRecord = DB::table('patient_has_service_reminder')
                      ->where('id',$value->reminder_id)
                      ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail','notification_count'=>$updateCount]);



                $getReminderCount =  DB::connection('tenant')
                                                            ->table('patient_has_service_reminder')
                                                            ->select('notification_count')
                                                            ->where('id',$value->reminder_id)
                                                            ->where('patient_id',$value->patient_id)
                                                            ->where('service_id',$value->service_id)
                                                            ->where('type',$value->type)
                                                            ->where('appointment_id','=',0)
                                                            ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                            ->first();

                $cnt = $getReminderCount->notification_count; 

                // dump("cnt==of notification_count====>");
                // dump($cnt); 

               

                if(isset($cnt) && $cnt>0 && !empty($nextReminderDate))
                {
                    // dump("nextReminderDate=in send email function=>");
                    // dump($nextReminderDate);     

                     $responseRecord = DB::table('patient_has_service_reminder')
                      ->where('id',$value->reminder_id)
                      ->update(['next_reminder_date'=>$nextReminderDate]);

                }
                 
            }
        }
        else
        {
             $responseRecord = DB::table('patient_has_service_reminder')
                  ->where('id',$value->reminder_id)
                  ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'mail']);
        }



    }//_sendMail

    /*-----------------------------------
    |  Send sms
    -------------------------------------------------*/
    public function _sendSms($phones,$value,$updateCount,$nextReminderDate)
    {
       // dump("in _sendSms function call........");
      //  dump($value);

        $textContent = DB::table('preferred_channels_for_reminders_setting')
                       ->where('id','1')
                       ->first();
        //$text   = $textContent->reminder_sms_notification_text."\n\n\r\r".$value->name;
        $URL='https://puregyn.puremed.biz/oa/services/'.base64_encode($value->exam_id);
        $text   = $textContent->reminder_sms_notification_text." ".$URL."\n\n\r\r".$value->name;
        // $text   = $textContent->reminder_sms_notification_text;
        //log::info("send __sms==".$text);
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
            $updateStatus = DB::table('patient_has_service_reminder')->find($value->reminder_id);


            //commented by me

            //commented on 1-apr-24
            /*if($value->appointment_id!=0){
                $responseRecord = DB::table('patient_has_service_reminder')
                              ->where('id',$value->reminder_id)
                              ->update(['reminder_status'=>'executed','updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms']);
            }*/

            if($value->appointment_id==0)
            {
               if(isset($updateCount))
               {
                    $responseRecord = DB::table('patient_has_service_reminder')
                              ->where('id',$value->reminder_id)
                              ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms','notification_count'=>$updateCount]);

                    $getReminderCount =  DB::connection('tenant')
                                                            ->table('patient_has_service_reminder')
                                                            ->select('notification_count')
                                                            ->where('id',$value->reminder_id)
                                                            ->where('patient_id',$value->patient_id)
                                                            ->where('service_id',$value->service_id)
                                                            ->where('type',$value->type)
                                                            ->where('appointment_id','=',0)
                                                            ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                                                            ->first();

                    $cnt = $getReminderCount->notification_count; 

                    // dump("cnt==of notification_count====>");
                    // dump($cnt); 
                    
                     if(isset($cnt) && $cnt>0 && !empty($nextReminderDate))
                    {
                         // dump("nextReminderDate=in send sms function=>");
                         // dump($nextReminderDate);     

                         $responseRecord = DB::table('patient_has_service_reminder')
                          ->where('id',$value->reminder_id)
                          ->update(['next_reminder_date'=>$nextReminderDate]);

                    }//if
                


               }//if updatecount
            }
            else
            {
                 $responseRecord = DB::table('patient_has_service_reminder')
                              ->where('id',$value->reminder_id)
                              ->update(['updated_at'=>date('Y-m-d H:i:s'),'media'=>'sms']);
            }//else




            return $responseRecord;
        }
    }//_sendSms  
 
  

} 
