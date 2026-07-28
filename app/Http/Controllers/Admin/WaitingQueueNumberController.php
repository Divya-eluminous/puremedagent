<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Lang; 

// Models
use App\Models\AppointmentHasQueueNumberModel;
use App\Models\PatientsModel; 
use App\Models\AdminUserModel;
use App\Models\AppointmentTypesModel; 
use App\Models\WaitingNumberSymbolsModel;
use App\Models\ActivityLogModel; 
use App\Models\SettingsModel; 

// Request
// use App\Http\Requests\Admin\PatientsRequest;
use Log;
// plugins
// use Hash;
// use Mail;
use DB; 
// use Auth;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

class WaitingQueueNumberController extends Controller
{
    private $BaseModel;

    public function __construct(
        AppointmentHasQueueNumberModel $AppointmentHasQueueNumberModel,
        PatientsModel $PatientsModel,
        AdminUserModel $AdminUserModel,
        AppointmentTypesModel $AppointmentTypesModel,
        WaitingNumberSymbolsModel $WaitingNumberSymbolsModel,
        ActivityLogModel $ActivityLogModel,
        SettingsModel $SettingsModel
    )
    {
        $this->BaseModel                = $AppointmentHasQueueNumberModel;
        $this->PatientsModel            = $PatientsModel;
        $this->AdminUserModel           = $AdminUserModel;
        $this->AppointmentTypesModel    = $AppointmentTypesModel;
        $this->WaitingNumberSymbolsModel = $WaitingNumberSymbolsModel;
        $this->ActivityLogModel         = $ActivityLogModel;
        $this->SettingsModel            = $SettingsModel;

        $this->ViewData = [];
        $this->JsonData = []; 

        $this->ModuleTitle  =  __('admin.TITLE_WAITING_QUEUE_TEXT');
        $this->ModuleView   = 'admin.waiting-queue-number.';
        $this->ModulePath   = 'admin.waiting-queue-number.';

        // Permission Middleware
        // $this->middleware(['permission:patients-listing'], ['only' => ['index','getRecords']]);
        // $this->middleware(['permission:patients-add'], ['only' => ['create','store']]);
    }

    public function index() 
    { 
        // Default site patients
        $this->ModuleTitle              =  __('admin.TITLE_WAITING_QUEUE_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;

        // $this->ViewData['addButton']    = __('admin.TITLE_ADD_BUTTON').' '.\Illuminate\Support\Str::singular($this->ModuleTitle);

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
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
                1 => 'appointment_has_queue_number.patient_id',
                2 => 'appointment.doctor_id',
                3 => 'appointment.appointment_type_id',
                4 => 'appointment_has_queue_number.date', 
                5 => 'appointment.appointment_status',
                6 => 'appointment_has_queue_number.queue_number',
                7 => 'appointment_has_queue_number.queue_number_type',
                8 => 'appointment_has_queue_number.created_at',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            // $modelQuery =  $this->BaseModel
            //                     ->leftjoin('appointment', 'appointment.id' , '=', 'appointment_has_queue_number.appointment_id')
            //                     ->leftjoin('patients', 'patients.id' , '=', 'appointment_has_queue_number.patient_id')
            //                     ->leftjoin('users', 'users.id' , '=', 'appointment.doctor_id')  
            //                     ->leftjoin('appointment_types', 'appointment_types.id' , '=', 'appointment.appointment_type_id');

            $modelQuery = $this->BaseModel
            ->select([
                'appointment_has_queue_number.id',
                'appointment_has_queue_number.queue_number',
                'appointment_has_queue_number.queue_number_type',
                'appointment_has_queue_number.called_status',
                'appointment_has_queue_number.status',
                'appointment_has_queue_number.created_at',
                'appointment.start_date',
                'appointment.appointment_status as appointment_status',
                'patients.country_code',
                'patients.mobile_no',
                'patients.first_name as patient_fname',
                'patients.family_name as patient_lname',
                'users.first_name as doctor_fname',
                'users.last_name as doctor_lname',
                'appointment_types.name as aname',
            ])
            ->leftJoin('appointment', 'appointment.id', '=', 'appointment_has_queue_number.appointment_id')
            ->leftJoin('patients', 'patients.id', '=', 'appointment_has_queue_number.patient_id')
            ->leftJoin('users', 'users.id', '=', 'appointment.doctor_id')
            ->leftJoin('appointment_types', 'appointment_types.id', '=', 'appointment.appointment_type_id');
            
            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

            # FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {
                if (!empty($request->custom['patient_id'])) {
                    $raw = trim($request->custom['patient_id']);
                    // keep letters, numbers, spaces, hyphen
                    $cleaned = preg_replace('/[^\p{L}0-9\s\-]/u', '', $raw);

                    if (empty($cleaned)) {
                        $modelQuery->whereRaw('1 = 0'); // no results
                    } else {
                        // Escape regex metachars for MySQL RLIKE (simple escaping)
                        $regexEscaped = preg_replace('/([\\\\.\^\$\|\?\*\+\(\)\[\{\]])/', '\\\\$1', $cleaned);

                        // MySQL word-boundary style
                        // $mysqlRegex = '[[:<:]]' . $regexEscaped . '[[:>:]]';
                        $mysqlRegex = $regexEscaped;

                        // Also prepare a safe LIKE pattern (escape % and _)
                        $likeEscaped = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $cleaned);

                        $modelQuery = $modelQuery->where(function ($q) use ($mysqlRegex, $likeEscaped) {
                            // whole-word match in first_name OR family_name OR full name
                            $q->whereRaw("patients.first_name RLIKE ?", [$mysqlRegex])
                            ->orWhereRaw("patients.family_name RLIKE ?", [$mysqlRegex])
                            ->orWhereRaw("CONCAT(patients.first_name, ' ', patients.family_name) RLIKE ?", [$mysqlRegex])
                            // fallback exact-substring (if you want exact but allow within a longer field)
                            ->orWhere('patients.first_name', 'LIKE', "%{$likeEscaped}%")
                            ->orWhere('patients.family_name', 'LIKE', "%{$likeEscaped}%")
                            ->orWhereRaw("CONCAT(patients.first_name, ' ', patients.family_name) LIKE ?", ["%{$likeEscaped}%"]);
                        });
                    }
                }
                if (isset($request->custom['doctor_id'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['doctor_id'];
                    $modelQuery     = $modelQuery
                    ->where('appointment.doctor_id', $key);
                }

                if (isset($request->custom['appointment_type_id'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['appointment_type_id'];
                    $modelQuery     = $modelQuery
                    ->where('appointment.appointment_type_id', $key);
                }
                if (isset($request->custom['appointment_status'])) {
                    // dump($request->custom['appointment_status']);
                    $custom_search  = true;

                        $key            = (ucfirst($request->custom['appointment_status']) == 'Verpasst' || ucfirst($request->custom['appointment_status']) == 'Vermisst') ? 'Vermisst' : $request->custom['appointment_status'];

                    $modelQuery     = $modelQuery
                        ->where('appointment.appointment_status', $key);
                        // dump($modelQuery->toSql());
                }
                // if (!empty($request->custom['start_date']))  
                // {
                //     $custom_search  = true;
                //     $key            = date('Y-m-d', strtotime($request->custom['start_date'])); 
                //     $modelQuery     = $modelQuery
                //     ->whereDate('appointment.start_date','=',$key);
                // } 
                if (!empty($request->custom['start_date'])) {
                    $custom_search  = true;
                    $key            = $request->custom['start_date'];
                    $modelQuery     = $modelQuery
                        ->where('appointment.start_date', 'LIKE', '%' . $key . '%');
                }
                if (!empty($request->custom['queue_number']))  
                {
                    // dump($request->custom['queue_number']);
                    $custom_search  = true;
                    $key            = $request->custom['queue_number'];              
                    $modelQuery     = $modelQuery
                    ->where('appointment_has_queue_number.queue_number','LIKE','%'.$key.'%');
                        // dump($modelQuery->toSql());

                }
            }

            // Common filter options
            if (!empty($request->search))
            {
                // dump($request->search);
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];

                     $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orWhere(DB::raw("CONCAT(patients.first_name, ' ', patients.family_name)"), 'LIKE', "%".$search."%");   
                        $query->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'LIKE', "%".$search."%");   
                        $query->orwhere('appointment_types.name', 'LIKE', '%'.$search.'%');  
                        $query->orwhere('appointment_has_queue_number.date', '=', $search);
                        $query->orwhere('appointment_has_queue_number.queue_number', '=', $search);
                        $query->orwhere('appointment.appointment_status', 'LIKE', '%'.$search.'%');
                    });
                }
            }

            // get total filtered
            $filteredQuery  = clone($modelQuery);            
            $totalFiltered  = $filteredQuery->count(); 
            
            // offset and limit
            // $object = $modelQuery->orderBy($filter[$column], $dir)
            //                     ->skip($start)
            //                     ->take($length)
            //                     ->get([
            //                         'appointment_has_queue_number.id',
            //                         'appointment_has_queue_number.queue_number',
            //                         'appointment_has_queue_number.queue_number_type',
            //                         'appointment_has_queue_number.called_status',
            //                         'appointment_has_queue_number.status',
            //                         'appointment_has_queue_number.created_at',
            //                         'appointment.start_date',
            //                         'patients.country_code',
            //                         'patients.mobile_no',
            //                         'patients.first_name as patient_fname',
            //                         'patients.family_name as patient_lname',
            //                         'users.first_name as doctor_fname',
            //                         'users.last_name as doctor_lname',
            //                         'appointment_types.name as aname',
            //                     ]); 
            
            $object = $modelQuery->orderBy($filter[$column], $dir)
                                ->skip($start)
                                ->take($length)
                                ->get();    
            
            // dd($object);
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = []; 
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row)  
                { 
                        $fname          = $row->patient_fname;
                        $lname          = $row->patient_lname;
                        $patient_name   = $fname .' '. $lname;

                        $fname          = $row->doctor_fname;
                        $lname          = $row->doctor_lname; 
                        $doctor_name    = $fname .' '. $lname; 

                        /*$patient_phone    = '';
                        if(!empty($row->mobile_no)){

                            $country_code   = $row->country_code;
                            $mobile_no      = $row->mobile_no; 
                            $patient_phone    = $country_code.$mobile_no; 
                        }*/

                        $queue_number_type = $row->queue_number_type==0?'App':'Tablet';

                        $data[$key]['id']           = $row->id; 

                        $data[$key]['patient_id']   = '<span title="'.(trim($patient_name) !== '' ? ucfirst($patient_name) : '-').'">'.(trim($patient_name) !== '' ? ucfirst($patient_name) : '-').'</span>';
                        $data[$key]['doctor_id']    = '<span title="'.(trim($doctor_name) !== '' ? ucfirst($doctor_name) : '-').'">'.(trim($doctor_name) !== '' ? ucfirst($doctor_name) : '-').'</span>';
                        $data[$key]['appointment_type_id']   = '<span>'.($row->aname ?: '-').'</span>';
                        $data[$key]['start_date'] =  "<span title='".($row->start_date ?: '-')."'>".($row->start_date ?: '-')."</span>";
                        $data[$key]['appointment_status'] =  "<span title='".($row->appointment_status ?: '-')."'>".($row->appointment_status ?: '-')."</span>";
                        $data[$key]['queue_number']  =  "<span title='".($row->queue_number ?: '-')."'>".($row->queue_number ?: '-')."</span>";
                        $data[$key]['queue_number_type']  =  "<span>".($queue_number_type ?: '-')."</span>";
                        $data[$key]['created_at']  =  "<span>".($row->created_at ?: '-')."</span>";

                        $called_status = 'fa-microphone-slash';
                        $called_status_title = 'Not Called';
                        if($row->called_status==1){
                            $called_status = 'fa-microphone';
                            $called_status_title = 'Called';
                        }

                        $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.waiting-queue-number.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>'; 
                       
                        $updateCallStatus = '<span class="theme-green semibold text-center f-18" title="Call to Patient"><a href="javascript:void(0)" class="delete-user action-icon" title="'.$called_status_title.'" onclick="return updateCallStatus(this)" data-href="'.route('admin.waiting-queue-number.updatecallstatus', [base64_encode(base64_encode($row->id))]) .'" data-status='.$row->called_status.'><i class="fa '.$called_status.'" aria-hidden="true"></a></i></span>'; 

                        if ($row->status==1)  
                        {
                            $data[$key]['actions'] = '  '.$updateCallStatus.' '.$delete.'</span>';
                        }
                        else 
                        {
                            $data[$key]['actions'] = '<span class="theme-black-light semibold text-center f-18" ></i>  '.$delete.' '.$updateCallStatus.'</span>';
                        }        
                } 
            }

            // Patient
            $patient = $this->PatientsModel
                            ->where('status', 1)
                            ->get();

            // Doctors
            $user = $this->AdminUserModel
                            ->where('status', 1)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->get();

            // Appointment Types
            $appointment_type = $this->AppointmentTypesModel
                                        ->where('status', 1)
                                        ->get();
            
            // Search date
            if(!empty($request->custom['start_date']) && $request->custom['start_date']==''){
                $val = '';
            }
            else{
                $val = $request->custom['start_date'] ?? '';
            }

            // Search for patient column
            // $patientName= '<select name="patient_id" id="patient_id" class="form-control my-select">';

            // $patientName.='<option class="theme-black blue-select" value="">'.__('admin.TITLE_SELECT_PATIENT').'</option>';

            // foreach ($patient as $patients) { 
            //     $patient_name = $patients->first_name;
            //     $lname = $patients->family_name;
            //     if($lname!=null){
            //         $patient_name = $patient_name.' '.$lname;
            //     }
               
            //     $patientName.='<option class="theme-black blue-select" value='.$patients->id.' '. (!empty($request->custom['patient_id']) && $request->custom['patient_id'] == $patients->id ? 'selected' : '').'>'.$patient_name.'</option>';
            // }             
            // $patientName.= "</select>";
            $patientName  =  '<input type="text" class="form-control" id="patient_id" value="'.($request->custom['patient_id'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';


            // Search for doctor column
            $doctorName = '';
            if(auth()->user()->hasRole('super-admin')){
                $doctorName= '<select name="doctor_id" id="doctor_id" class="form-control my-select">';

                $doctorName.='<option class="theme-black blue-select" value="">'.__('admin.TITLE_SELECT_DOCTOR').'</option>';

                foreach ($user as $users) {
                    $dname = $users->first_name.' '.$users->last_name; 
                    $doctorName.='<option class="theme-black blue-select" value='.$users->id.' '. (!empty($request->custom['doctor_id']) && $request->custom['doctor_id'] == $users->id ? 'selected' : '').'>'.$dname.'</option>';
                }             
                $doctorName.= "</select>"; 
            }

            // Search for appointment type column
            $appointmentTypeName= '<select name="appointment_type_id" id="appointment_type_id" class="form-control my-select">';

            $appointmentTypeName.='<option class="theme-black blue-select" value="">'.__('admin.TITLE_SELECT_TYPE').'</option>';

            foreach ($appointment_type as $appointment_types) {
                $pname = $appointment_types->name;
                $appointmentTypeName.='<option class="theme-black blue-select" value='.$appointment_types->id.' '. (!empty($request->custom['appointment_type_id']) && $request->custom['appointment_type_id'] == $appointment_types->id ? 'selected' : '').'>'.$pname.'</option>';
            }             
            $appointmentTypeName.= "</select>";  

            ## SEARCH HTML
            
            $searchHTML['id']               =  ''; 
            $searchHTML['patient_id']       =  $patientName;  
            $searchHTML['doctor_id']        =  auth()->user()->hasRole('super-admin')?$doctorName : ''; 
            $searchHTML['appointment_type_id']   =  $appointmentTypeName; 
 
            $searchHTML['start_date']   =  '<input type="text" class="form-control" id="start_date" value="'.$val.'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            // $searchHTML['appointment_status']  =  ''; 
            $searchHTML['appointment_status']  =  '<input type="text" class="form-control" id="appointment_status" value="' . ($request->custom['appointment_status'] ?? '') . '" placeholder=' . __('admin.TITLE_SEARCH_TEXT') . '>';
            $searchHTML['queue_number']    =  '<input type="text" class="form-control" id="queue_number" value="'.($request->custom['queue_number'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>'; 
            $searchHTML['queue_number_type']               =  ''; 
            $searchHTML['created_at']               =  ''; 
            // $searchHTML['status']   =  '<select name="status" id="status" class="form-control my-select">
            //         <option class="theme-black blue-select" value="">'.__('admin.TITLE_EXAMINATION_STATUS').'</option>
            //         <option class="theme-black blue-select" value="1" '.( $request->custom['status'] == "1" ? 'selected' : '').' >Call</option>
            //         <option class="theme-black blue-select" value="2" '.( $request->custom['status'] == "2" ? 'selected' : '').'>Done</option>            
            //     </select>';
            
            $seachAction  =  '<div class="text-center d-flex justify-content-center gap-2" style="gap: 5px;">
                <a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a>
                <a style="cursor:pointer;" onclick="return removeSearch(this)" class="btn btn-primary"><span class="fa fa-times"></span></a>
            </div>';
            /*}*/   

            $searchHTML['actions'] = $seachAction;
            array_unshift($data, $searchHTML);
            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData);
    } 

    public function destroy($encID)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_DELETE'); 

        $id = base64_decode(base64_decode($encID));

        $BaseModel = $this->BaseModel->find($id); 
       
        if($BaseModel->delete())
        {
            $newData = $BaseModel->toArray();
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted','Delete',null,$newData);

            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.WAITING_NUMBER_DELETED');
        }
        
        return response()->json($this->JsonData);
    } 

    public function updateCallStatus($encID)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_DELETE'); 

        $id = base64_decode(base64_decode($encID));
        $BaseModel = $this->BaseModel->find($id); 
        $called_status = $BaseModel->called_status;
        if($called_status==0){
            $BaseModel->called_status = 1;
            $BaseModel->called_time = date('Y-m-d H:i:s',time());
        }else{
            $BaseModel->called_status = 0;
            $BaseModel->called_time = NULL;
        }

        if($BaseModel->save())
        {
            //$newData = $BaseModel->toArray();
          //  $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted','Delete',null,$newData);

            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = 'Wartenummer wurde aufgerufen';
        }
        return response()->json($this->JsonData);
    } 

    public function getLatestWaitingRecords()
    {
        $this->ModuleView   = 'web.waiting-number';
         $audio_data = $this->SettingsModel
                            ->where('setting_key','=','AUDIO_SOUND_FILE')
                            ->whereStatus(1)
                            ->first();
        $waiting_details = $this->BaseModel
                                ->with(['assignedSymbol'])
                                ->where('called_status', '1')
                                ->orderBy('called_time','DESC')
                                ->first();

                                /*,'assignedPatient',
                                    'assignedAppointment'=>function($q){
                                        $q->with(['assignedDoctor','assignedAppointmentType']);

                                    }*/
       
        // dd($waiting_details);
        $queue_img = '';
        if(!empty($waiting_details) && !empty($waiting_details->assignedSymbol)){
           // $doctor_name = $waiting_details->assignedAppointment->assignedDoctor->first_name." ".$waiting_details->assignedAppointment->assignedDoctor->last_name;
            $queue_img = $waiting_details->assignedSymbol->url;

        }
        $this->ViewData['waiting_details'] = $waiting_details;
        $this->ViewData['queue_img'] = $queue_img;
        $this->ViewData['audio_sound']   = $audio_data->setting_value;

        //$this->ViewData['doctor_name']     = $doctor_name;
            // dd($waiting_details);
        // return view('GDPR.gdpr-details', $this->ViewData);
        return view($this->ModuleView, $this->ViewData);    
    }

    public function getWaitingRecord()
    {

        $response = [];
        $this->JsonData['data'] = $response;
        
        try{
         

            $waiting_details = $this->BaseModel
                                    ->with(['assignedSymbol'])
                                    ->where('called_status', '1')
                                    ->orderBy('called_time','DESC')
                                    ->first();
                                    // ->with(['assignedPatient','assignedSymbol',
                                    //     'assignedAppointment'=>function($q){
                                    //         $q->with(['assignedDoctor','assignedAppointmentType']);

                                    //     }]
                                    //     )
            // dd($waiting_details);
            $doctor_name = '';
            $queue_number = '';
            if(!empty($waiting_details)){
                $queue_number = $waiting_details->queue_number;
            }
            $queue_img = '';
            if(!empty($waiting_details) && !empty($waiting_details->assignedSymbol)){
               // $doctor_name = $waiting_details->assignedAppointment->assignedDoctor->first_name." ".$waiting_details->assignedAppointment->assignedDoctor->last_name;
                $queue_img = $waiting_details->assignedSymbol->url;

            }
           /* if(!empty($waiting_details) && !empty($waiting_details->assignedAppointment)){
                $doctor_name = $waiting_details->assignedAppointment->assignedDoctor->first_name." ".$waiting_details->assignedAppointment->assignedDoctor->last_name;

            }*/
            $html = '';
            if(!empty($waiting_details->queue_number)){

                $html = '<h3>Wir bitten Wartenummer</h3>';
                $html .= '<h2 id="q_number">'.$waiting_details->queue_number.'</h2>';
                $html .= '<h3 class="btm-head">zur Anmeldung zu kommen</h3>';
            }else{

                $html = '<h3>Bitte warten Sie bis Ihre</h3><br>';
                $html .= '<h3 class="btm-head">Wartenummer aufgerufen wird</h3>';
            }

            //$html = 'Wir bitten Wartenummer '.$queue_number.' ins Ordinationszimmer zu '.$doctor_name;
           // $html = $queue_number;
            
            $response['queue_number'] = $queue_number;
            $response['queue_img'] = $queue_img;
            //$response['doctor_name']  = $doctor_name;
            $response['html']         = $html;

            $this->JsonData['status']   = __('admin.RESP_SUCCESS');
            $this->JsonData['data']      = $response;
            $this->JsonData['msg']      = 'Waiting Record.'; 

        }catch(\Exception $e) {

            $this->JsonData['msg']      = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

         return response()->json($this->JsonData);
    }

} 
