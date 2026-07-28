<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang; 

// Models
use App\Models\PatientsModel;
use App\Models\OldPatientsModel;
use App\Models\PatientHasOrdinationsModel;
use App\Models\ActivityLogModel; 
use App\Models\AppointmentHasExaminationsModel;
use App\Models\PatientHasDocumentsModel;  
use App\Models\SpecialistDocumentsModel;
use App\Models\SpecialistModel;
use App\Models\CheckListModel; 
use App\Models\CheckListHasHeadingSectionModel; 
use App\Models\HeadingSectionHasQuestionModel; 
use App\Models\CheckListHasSelectedQuestionModel;
use App\Models\OrdinationHasSpecialistModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\PatientHasReminder;
use App\Models\AppointmentModel;
use App\Models\PatientsHasOldFindingModel; 
use App\Models\CountryCodesModel; // new model for country code lookup
// Request
use App\Http\Requests\Admin\PatientsRequest; 
use App\Traits\GeneralTrait;
use App\Imports\PatientImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use App\Mail\SendDocumentForPatientmail;
use Carbon\Carbon;


// plugins 
use Hash; 
use Mail; 
use DB; 
use Auth;
use File; 
use Session;
use Config;
use DateTime; //added on 10-jan-24

use App\Models\PatientsHasDiagnosticFindingsModel;  //Added on 31-may-24
use App\Models\PatientHasDiagnosticFindingsHasDocumentsModel; //Added on 31-may-24

use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 18-06-2024

use App\Models\AppointmentHasNotificationModel; //Added on 29-july-24
use App\Models\PatientsHasDismissalModel;//Added on 29-july-24
use App\Models\RosterHasDatesModel; //Added on 29-july-24
use App\Models\RosterHasWeeksHasTimeFramesModel; //Added on 29-july-24


class PatientsController extends Controller
{
    private $BaseModel;
  
    use GeneralTrait;
   

    public function __construct(
        PatientsModel $PatientsModel,
        ActivityLogModel $ActivityLogModel,
        AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
        PatientHasDocumentsModel $PatientHasDocumentsModel,
        PatientHasOrdinationsModel $PatientHasOrdinationsModel,
        SpecialistDocumentsModel $SpecialistDocumentsModel,
        SpecialistModel $SpecialistModel,
        CheckListModel $CheckListModel,
        CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
        HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
        CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
        OrdinationHasSpecialistModel $OrdinationHasSpecialistModel,
        OldPatientsModel $OldPatientsModel,
        PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
        PatientHasReminder $PatientHasReminder,
        AppointmentModel $AppointmentModel,
        PatientsHasOldFindingModel $PatientsHasOldFindingModel,
        PatientsHasDiagnosticFindingsModel $PatientsHasDiagnosticFindingsModel,
        PatientHasDiagnosticFindingsHasDocumentsModel $PatientHasDiagnosticFindingsHasDocumentsModel,
        AppointmentHasNotificationModel $AppointmentHasNotificationModel, //Added on 29-july-24
        PatientsHasDismissalModel $PatientsHasDismissalModel, //Added on 29-july-24
        RosterHasDatesModel $RosterHasDatesModel, //Added on 29-july-24
        RosterHasWeeksHasTimeFramesModel $RosterHasWeeksHasTimeFramesModel,
        CountryCodesModel $CountryCodesModel
    )
    {
        $this->PatientsModel         = $PatientsModel; 
        $this->BaseModel         = $PatientsModel;
        $this->ActivityLogModel  = $ActivityLogModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
        $this->PatientHasDocumentsModel = $PatientHasDocumentsModel;
        $this->PatientHasOrdinationsModel = $PatientHasOrdinationsModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->SpecialistModel = $SpecialistModel;
        $this->CheckListModel = $CheckListModel;
        $this->CheckListHasHeadingSectionModel   = $CheckListHasHeadingSectionModel;
        $this->HeadingSectionHasQuestionModel    = $HeadingSectionHasQuestionModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;
        $this->OrdinationHasSpecialistModel  = $OrdinationHasSpecialistModel;
        $this->OldPatientsModel = $OldPatientsModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->PatientHasReminder = $PatientHasReminder;
        $this->AppointmentModel = $AppointmentModel;
        $this->PatientsHasOldFindingModel = $PatientsHasOldFindingModel;

        $this->PatientsHasDiagnosticFindingsModel = $PatientsHasDiagnosticFindingsModel;
        $this->PatientHasDiagnosticFindingsHasDocumentsModel = $PatientHasDiagnosticFindingsHasDocumentsModel;

        $this->AppointmentHasNotificationModel  = $AppointmentHasNotificationModel; //added on 29-july-24
        $this->PatientsHasDismissalModel = $PatientsHasDismissalModel; //added on 29-july-24
        $this->RosterHasDatesModel = $RosterHasDatesModel; //added on 29-july-24
        $this->RosterHasWeeksHasTimeFramesModel = $RosterHasWeeksHasTimeFramesModel; //added on 29-july-24
        $this->CountryCodesModel = $CountryCodesModel; // country codes lookup


        $this->ViewData = [];
        $this->JsonData = []; 

        $this->ModuleTitle  =  __('admin.TITLE_PATIENT_TEXT');
        $this->ModuleView   = 'admin.patients.';
        $this->ModulePath   = 'admin.patients.';

        // Permission Middleware
        $this->middleware(['permission:patients-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:patients-add'], ['only' => ['create','store']]);

       
    } 

    public function index() 
    { 

       
        // Default site patients
        $this->ModuleTitle              =  __('admin.TITLE_PATIENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');

        if(empty(Config('website_id')))
        {
            $this->ViewData['show_ordination']    = true;
        }
        else
        { 
            $this->ViewData['show_ordination']    = false;
        }
        $this->ViewData['specialist_details']= self::__GetSecialits();

       
        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    } 

    public function create()  
    { 
        // Default site settings 
        $this->ModuleTitle              =  __('admin.TITLE_PATIENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // prepare country code options for dropdown
        $this->ViewData['country_codes'] = $this->CountryCodesModel
            ->where('is_active',1)
            // ->orderBy('phone_code')
            ->pluck('phone_code')
            ->toArray();

        // dd($this->ViewData);
        // view file with data

        return view($this->ModuleView.'create', $this->ViewData);
    } 

    public function store(PatientsRequest $request)
    {
        //Check the country code format is valid or not. added on 03-march-26
        // if(isset($request->format) && !empty($request->format)) {
        //     $codeCheck = self::isValidCountryCode($request->format);
        //     if(!$codeCheck) {
        //         $this->JsonData['msg'] = __('admin.ERR_COUNTRY_CODE_WRONG'); 
        //         $this->JsonData['status']   = __('admin.RESP_ERROR');
        //         return response()->json($this->JsonData);
        //     }
        // }
        Log::info("in patient controller _storePatient function");
        Log::info($request->all());

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_PATIENT_CREATE'); 
        try { 

            $is_exist_patient = $this->_checkDuplicationPatient($request->family_name,$request->first_name,$request->birth_date,$request->mobile_no,'add',$id = '');
       
            if(!$is_exist_patient)
            {
                $this->JsonData['msg'] = __('admin.ERR_MOBILE_UNIQUE'); 
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                return response()->json($this->JsonData);
                exit();
            }

            $request->update_ganydb   = 0;

            $checkedBirthdateExist = $this->BaseModel
                                        ->where(DB::raw('upper(family_name)'),'=',strtoupper($request->family_name))
                                        ->where(DB::raw('upper(first_name)'),'=',strtoupper($request->first_name))
                                        ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date)))
                                        ->whereNULL('deleted_at')
                                        ->get(); 


                //commented below code on 15-dec-23 for patient duplication                           
               /* if(count($checkedBirthdateExist) > 0 )
                {
                    $this->JsonData['msg'] = __('admin.ERR_BIRTH_DATE_UNIQUE'); 
                    $this->JsonData['status']   = __('admin.RESP_ERROR');
                    return response()->json($this->JsonData);
                    exit();
                }*/


            $collection     = new $this->BaseModel;    
            $collection     = self::_storeOrUpdate($collection,$request);
            //Log::info('Admin side create new patient. patient Name :' .$collection->first_name.' '.$collection->family_name);

            $collection->old_id = 99999;
            $collection->save();
            if ($collection) 
            {
                if(!empty(Config('ordination_id')))
                {
                    $ordination_patient = self::_storePatientOrdination($collection->id);
                    if($ordination_patient != true)
                    {
                        //Log::info('When tenant create new patint and same patient is exist or not in master admin check. patient Name :' .$collection->first_name.' '.$collection->family_name);
                        $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['status'] = __('admin.RESP_ERROR');
                        //$this->JsonData['url']    =  route($this->ModulePath.'index');
                    }
                    else {
                        $newData = $collection->toArray();
                        $this->ActivityLogModel->addLog($this->ModuleTitle,'has created patient','Add',null,$newData);
                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['url']    =  route($this->ModulePath.'index');
                        $this->JsonData['msg']    = __('admin.PATIENT_CREATED');
                    }
                }
                else {
                    $newData = $collection->toArray();
                    $this->ActivityLogModel->addLog($this->ModuleTitle,'has created patient','Add',null,$newData);
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    =  route($this->ModulePath.'index');
                    $this->JsonData['msg']    = __('admin.PATIENT_CREATED');
                }
                //Added by Shyam 16-02-22
                if(isset($collection->id) && $collection->id != '')
                {
                    $setReminders = app('App\Http\Controllers\Admin\DashboardController')->checkPatientAgeReminder($collection->id);
                }
                
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
         /*$mobile_no = str_replace(" ", "", $request->mobile_no);
            $nationalFormat = substr($mobile_no, 0, 1);

            //avoid initial 0
            if($nationalFormat!=0 && strlen($nationalFormat)==11){
                $this->JsonData['msg'] = __('admin.ERR_MOBILE_NO_FORMAT');
                return response()->json($this->JsonData);
                exit();
            }

            //check national format
            $internationalFormat = substr($mobile_no, 0, 3);
            if($internationalFormat == '+43'){
                $mobile_no = '0'.substr($mobile_no, 3);;
            }

            $mobile_no_exists = $this->BaseModel
                                    ->where('mobile_no',$mobile_no)
                                    ->first(['id','first_name','last_name']);

            if(!empty($mobile_no_exists)){

                $this->JsonData['msg'] = __('admin.ERR_MOBILE_UNIQUE');
                return response()->json($this->JsonData);
                exit();
            }

            dd($mobile_no,$nationalFormat,$mobile_no_exists,$internationalFormat);*/

            
            /*$msg = '';
            $doctorName = $request->family_doctor;
            // dd($doctorName);
            if(!empty($doctorName)){  
                // dd('test');
                $result = preg_match( "/^[a-zA-Z ]+$/", $doctorName );
                // dd($ssa);
                if ($result == 0) {
                // if($doctorName.match($validate_name))
                    // dd('test123');
                    $msg =  __('admin.ERR_FAMILY_DOCTOR_NAME');
                    // exit();
                } 
            }
           
            if(!empty($msg)){
                $this->JsonData['msg'] = $msg; 
                return response()->json($this->JsonData);
                exit();
            }*/ 
    }

    public function edit($encID)
    {
        // Default site settings
        $this->ModuleTitle              =  __('admin.TITLE_PATIENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        $id = base64_decode(base64_decode($encID));
        //last 3 patients appointment code added by swapnil pawar 22-09-2022
        $lastAppointment = $this->AppointmentModel
            ->where('patient_id',$id)
            ->whereDate('start_date','<',date('Y-m-d'))  
            ->where('appointment_status','Fertig')
            ->orderBy('start_date', 'DESC')
            ->take(3)
            ->get(); 
        $this->ViewData['lastAppointment'] = $lastAppointment;
        //last 3 patients appointment code added by swapnil pawar 22-09-2022
        // All userdata
        
        $this->ViewData['patient'] = $this->BaseModel->find($id);

        // add country code list for dropdown
        $this->ViewData['country_codes'] = $this->CountryCodesModel
            ->where('is_active',1)
            // ->orderBy('phone_code')
            ->pluck('phone_code')
            ->toArray();
    
        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function update(PatientsRequest $request, $encID)
    {
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_PATIENT_UPDATE');       
              
        // try {

            $request->update_ganydb   = 1;

            $is_exist_patient = $this->_checkDuplicationPatient($request->family_name,$request->first_name,$request->birth_date,$request->mobile_no,'update',$id);
       
            if(!$is_exist_patient)
            {
                $this->JsonData['msg'] = __('admin.ERR_MOBILE_UNIQUE'); 
                $this->JsonData['status']   = __('admin.RESP_ERROR');
                return response()->json($this->JsonData);
                exit();
            }

            $collection_old = $this->BaseModel->find($id); 

             Log::info($collection_old);
           
            
            $collection = $this->BaseModel->find($id); 
            
            $oldData = $collection->toArray();
            $collection = self::_storeOrUpdate($collection,$request);
            if(!empty($oldData) && $oldData['birth_date'] != date('Y-m-d',strtotime($request->birth_date)))
            {
               // $this->_ageReminderOnUpdateAge($id);//commented on 28-Sep-23
               // $this->_ageReminderAppoitment($id);
            } 
            $newData = $collection->toArray();
            if ($collection)  
            {
                  Log::info($collection);
                  
                $ordination_patient_update = self::_updatePatientOrdination($collection,$collection_old);

                $ordination_patient_update = self::_oldPatient($collection_old);

                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated patient','Update',$oldData,$newData);

                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'index');
                $this->JsonData['msg']    = __('admin.PATIENT_UPDATED');
            }
            
        // }
        // catch(\Exception $e) {

        //     $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
        //     $this->JsonData['error_msg'] = $e->getMessage();
        // }

        return response()->json($this->JsonData);
    }

    public function destroy($encID)
    {
        Log::info("in patient destroy method...");

        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_PATIENT_DELETE'); 

        $id = base64_decode(base64_decode($encID));

        $BaseModel = $this->BaseModel->find($id);
        if($BaseModel->delete())
        {

             //start update deleted at field in the central (system) parent database.added on 9-nov-23
             //changed: remove from THIS ordination only, and delete the generic/central
             //patient (in system DB) ONLY if the patient no longer belongs to any other
             //ordination. Reuses the same mapping logic as _storePatientOrdination().
             self::_deletePatientOrdination($BaseModel);
           //end update deleted at field in the central (system) parent database.added on 9-nov-23


            /*********added delete code on 29-july-24*****************/

           
            //get FUTURE appointments to delete data (past appointments are kept)
            $allAppointments = $this->AppointmentModel
                                    ->where('patient_id',$id)
                                    ->where('start_date','>=',date('Y-m-d H:i:s'))
                                    ->get();

            if(isset($allAppointments) && !empty($allAppointments)){
                foreach($allAppointments as $k=>$v)
                {   
                    $appId = $v['id'];
                   
                    //Delete appointment examinations
                    $this->AppointmentHasExaminationsModel->where('patient_id',$id)->where('appointment_id',$appId)->delete();

                    //Delete appointment notifications
                    $this->AppointmentHasNotificationModel->where('appointment_id',$appId)->where('patient_id',$id)->delete();

                    //Delete appointment patient documents
                    $this->PatientHasDocumentsModel->where('appointment_id',$appId)->delete();

                    //Delete event id from goole calendar
                    $request = array(
                             'eventId'=>$v['google_event_id'],
                            );
                    request()->merge($request);
                    $postCalDetails = app('App\Http\Controllers\Admin\DashboardController')->eventDelete(request());

                    //delete patient old finding entries
                    $this->PatientsHasOldFindingModel->where('fk_patient_id',$id)->where('appointment_id',$appId)->delete();

                    //delete patient dismissal
                    $this->PatientsHasDismissalModel->where('fk_patient_id',$id)->where('appointment_id',$appId)->delete();
                    


                    //update time frame flag to use for the slots again
                    $timeFrame = date('H:i:s',strtotime($v['start_date']));
                    $doctor_id = $v['doctor_id'];

                   
                    $time_frames= $this->RosterHasDatesModel
                            ->leftjoin('roster','roster.id','roster_has_dates.roster_id')
                            ->whereDate('roster_has_dates.date',date('Y-m-d',strtotime($v['start_date'])))
                            ->where('roster.doctor_id',$doctor_id)
                            ->first();

                          

                    if(!empty($time_frames))
                    {

                        $getrec = $this->RosterHasWeeksHasTimeFramesModel
                              ->where('week_day_id',$time_frames->week_day_id)   
                              ->where('roster_id',$time_frames->roster_id) 
                              ->where('time_frame',$timeFrame)   
                              ->where('time_frame_flag','2')
                              ->first(); 

                      
                        if(!empty($getrec))
                        {

                          $oldUpdateTimeFrameFlg = $this->RosterHasWeeksHasTimeFramesModel->find($getrec->id);
                          $oldUpdateTimeFrameFlg->time_frame_flag = '0';
                          $oldUpdateTimeFrameFlg->comment         = 'patient_id '.$v['patient_id'].' deleted Appointment Date :'.$v['start_date'].' Appointment From  DashboardController current Date :'.date('Y-m-d H:i:s').' Time Fram Id : '.$getrec->id;
                          $oldUpdateTimeFrameFlg->save();  

                        }//if not empty 
                        
                    }//if not empty time_frames        
                    //update time frame flag to use for the slots again
                    

                }//foreach
            }//if all appointments

            $ids = $this->PatientsHasServiceReminderModel
                    ->where('patient_id',$id)
                    ->select('id')
                    ->get();

            $id_holder = [];
            if(!empty($ids))
            {
                foreach($ids as $id1=>$value)
                {
                    $id_holder[] = $value->id;
                }
            }
                            
            $this->PatientsHasServiceReminderModel
                ->where('patient_id',$id)
                ->update(['deleted_at'=>date('Y-m-d H:i:s')]);


            $deleteReminder =  $this->PatientHasReminder
                                ->whereIn('service_reminder_id',$id_holder)
                                ->update(['deleted_at'=>date('Y-m-d H:i:s')]);
    

             //Delete FUTURE appointments for patient (past appointments are kept)
            $this->AppointmentModel
                ->where('patient_id',$id)
                ->where('start_date','>=',date('Y-m-d H:i:s'))
                ->delete();

            /************added delete code on 29-july-24*************/


            $newData = $BaseModel->toArray();
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData);
            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.PATIENT_DELETED');
        }

        return response()->json($this->JsonData);
    }


    //Commemented by Shyam 18-03-22 
    public function getRecords_old(Request $request)
    {
  
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
                2 => 'patients.email',
                3 => 'patients.mobile_no', 
                4 => 'patients.birth_date',
                5 => 'ordination',
                6 => 'status', 
            );
       

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel;

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

                if (!empty($request->custom['mobile_no'])) 
                {
                    // $custom_search  = true;
                    // $key            = $request->custom['mobile_no'];              
                    // $modelQuery     = $modelQuery
                    // ->where('patients.mobile_no','LIKE','%'.$key.'%');

                    // dd($request->custom['mobile_no']);
                    // dd($request->custom['mobile_no']);
                    $custom_search  = true;
                    $key            = str_replace('+', '', $request->custom['mobile_no']);
                    if(strlen($key) >= 12) {
                        $codeArr = str_split($key, 2);
                        $cntryCode = '+'.$codeArr[0];
                        $mobileNum = @$codeArr[1].@$codeArr[2].@$codeArr[3].@$codeArr[4].@$codeArr[5].@$codeArr[6].@$codeArr[7];
                    }
                    else {
                        $cntryCode = '';
                        $mobileNum = trim($key);
                    }
                    if($cntryCode != '') {
                        $modelQuery = $modelQuery->where('patients.country_code','LIKE','%'.$cntryCode.'%')
                                                 ->where('patients.mobile_no','LIKE','%'.$mobileNum.'%');
                    }
                    else {
                        $modelQuery = $modelQuery->where('patients.mobile_no','LIKE','%'.$mobileNum.'%');
                    }
                    // $modelQuery = $modelQuery
                    // ->where('patients.mobile_no','LIKE','%'.$key.'%');
                    // //->whereRaw("MATCH(patients.mobile_no) AGAINST('".$key."')");
                    
                } 

                if (!empty($request->custom['birth_date'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['birth_date']; 
                    $modelQuery     = $modelQuery
                    ->where('patients.birth_date','=',$key);
                } 

                // if (!empty($request->custom['place'])) 
                // {
                //     $custom_search  = true;
                //     $key            = $request->custom['place'];
                //     $modelQuery     = $modelQuery
                //     ->where('patients.place','LIKE','%'.$key.'%');
                // }

                if (isset($request->custom['status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('patients.status', $key);
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
                        $query->orwhere('patients.mobile_no', 'LIKE', '%'.$search.'%'); 
                        $query->orwhere('patients.old_id', '=', $search); 
                        // $query->orwhere('patients.birth_date', 'LIKE', '%'.$search.'%'); 
                        // $query->orwhere('patients.birth_date', '=', $search); 
                        // $query->orwhere('patients.place', 'LIKE', '%'.$search.'%'); 
                        // if(strtolower($search)=="active"){
                        //     $query->orwhere('patients.status', '=', 1);
                        // }
                        // else{
                        //     $query->orwhere('patients.status', '=', 0);
                        // }  
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
                        // $lname = ucfirst($row->last_name);
                        $familyName = ucfirst($row->family_name);

                        $data[$key]['fullname']  = '<span title="'.'concatenateNom'.'">'.$fname.' '.$familyName.'</span>';

                        $data[$key]['email']     = '<span title="'.$row->email.'">'.$row->email.'</span>'; 

                        $intCountryCode = $row->country_code;  

                        $data[$key]['mobile_no']  =  "<span title='".$intCountryCode.$row->mobile_no."'>".$intCountryCode.$row->mobile_no."</span>";

                        $data[$key]['birth_date'] =  "<span title='".$row->birth_date."'>".$row->birth_date."</span>";

                        if(empty(Config('website_id')))
                        {
                  
                            $data[$key]['ordination'] =  "<span title='".$row->birth_date."'>".self::_ordinationName($row->id)."</span>";
                        }  else
                        {
                            $data[$key]['ordination'] =  '';
                        }

                        // $data[$key]['place']  =  "<span title='".$row->place."'>".$row->place."</span>";

                        if (!empty($row->status)) 
                        {
                            $data[$key]['status'] = '<span class="theme-green semibold text-center f-18" >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</span>';
                        }
                        else 
                        {
                            $data[$key]['status'] = '<span class="theme-black-light semibold text-center f-18" >'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</span>';
                        }

                        $edit="";
                        $delete="";

                        // Check Permission
                        if(auth()->user()->can('patients-add')){
                            $edit = '<a href="'.route($this->ModulePath.'edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                            $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route($this->ModulePath.'destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                             $viewExamination = '<a href="'.route($this->ModulePath.'examination.index', [ base64_encode(base64_encode($row->id))]).'" class="delete-user action-icon" title="'.__('admin.TITLE_VIEW_EXAMINATIONS_TEXT').'"><span class="nav-icon fas fa-diagnoses"></span></a>&nbsp&nbsp';
                             $viewDocument = '<a href="'.route($this->ModulePath.'document.index', [ base64_encode(base64_encode($row->id))]).'" class="delete-user action-icon" title="'.__('admin.TITLE_VIEW_DOCUMENT_NAME').'"><span class="nav-icon fas fa-file"></span></a>';
                        } 
 
                        $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.$viewExamination.$viewDocument.'</div>';                   
                } 
            }

            ## SEARCH HTML
            $val= '';
            if(isset($request->custom['birth_date']) && $request->custom['birth_date']!=''){
               
                $val = $request->custom['birth_date'];
            }
            
            $searchHTML['id']           =  '';    
            $searchHTML['fullname']     =  '<input type="text" class="form-control" id="fullname" value="'.($request->custom['fullname'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['email']        =  '<input type="text" class="form-control" id="email" value="'.($request->custom['email'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['mobile_no']    =  '<input type="text" class="form-control" id="mobile_no" value="'.($request->custom['mobile_no'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';  
 
            $searchHTML['birth_date']   =  '<input type="text" class="form-control" id="birth_date" value="'.$val.'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            
            $searchHTML['ordination']   =  '';
       
            // $searchHTML['place']     =  '<input type="text" class="form-control" id="place" value="'.($request->custom['place']).'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['status']   =  '<select name="status" id="status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_EXAMINATION_STATUS').'</option>
                    <option class="theme-black blue-select" value="1" '.(isset($request->custom['status']) &&  $request->custom['status'] == "1" ? 'selected' : '').' >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</option>
                    <option class="theme-black blue-select" value="0" '.( isset($request->custom['status']) && $request->custom['status'] == "0" ? 'selected' : '').'>'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</option>            
                </select>'; 
            
            $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';
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

     //Added by Shyam 18-03-22 
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
                2 => 'patients.email',
                3 => 'patients.mobile_no',
                4 => 'patients.birth_date',
                5 => 'ordination',
                6 => 'status',
                7 => 'patients.sendMail',
                8 => 'patients.sendSMS',
            );


        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel;

            // get total count
            $countQuery = clone($modelQuery);
            $totalData  = $countQuery->count();

            # FILTER OPTIONS for specific field
            $custom_search = false;
            if (!empty($request->custom))
            {
             if (!empty($request->custom['fullname'])) {
                    $raw = trim($request->custom['fullname']);
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
            //     if (!empty($request->custom['fullname'])) {

            //     $patientId = preg_replace('/\s+/', ' ', trim($request->custom['fullname']));
            //     // keep hyphen
            //     // $cleanedId = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $patientId);
            //     $cleanedId = preg_replace('/[^\p{L}0-9\s\-]/u', '', $patientId);    
                
            //     if (!empty($cleanedId)) {
            //         $name = explode(" ", $cleanedId);

            //         if (!empty($name[1])) {
            //             $key[0] = $name[0];
            //             $key[1] = $name[1];
            //             $custom_search = true;

            //             $modelQuery = $modelQuery
            //                 ->whereRaw("MATCH(patients.first_name) AGAINST(? IN BOOLEAN MODE)", ["+" . str_replace('-', ' ', $key[0]) . "*"])
            //                 ->whereRaw("MATCH(patients.family_name) AGAINST(? IN BOOLEAN MODE)", ["+" . str_replace('-', ' ', $key[1]) . "*"]);
            //         } else {
            //             $key[0] = $name[0];
            //             $custom_search = true;

            //             $modelQuery = $modelQuery->where(function ($q) use ($key) {
            //                 $search = "+" . str_replace('-', ' ', $key[0]) . "*";
            //                 $q->whereRaw("MATCH(patients.first_name) AGAINST(? IN BOOLEAN MODE)", [$search])
            //                 ->orWhereRaw("MATCH(patients.family_name) AGAINST(? IN BOOLEAN MODE)", [$search]);
            //             });
            //         }
            //     } else {
            //         $modelQuery->whereRaw('1 = 0');
            //     }
            // }
                if (!empty($request->custom['email']))
                {
                    $custom_search  = true;
                    $key            = $request->custom['email'];
                    $modelQuery     = $modelQuery
                    ->where('patients.email','LIKE','%'.$key.'%');
                }

                if (!empty($request->custom['mobile_no']))
                {
                    // $custom_search  = true;
                    // $key            = $request->custom['mobile_no'];
                    // $modelQuery     = $modelQuery
                    // ->where('patients.mobile_no','LIKE','%'.$key.'%');

                    // dd($request->custom['mobile_no']);
                    // dd($request->custom['mobile_no']);
                    $custom_search  = true;
                    $key            = str_replace('+', '', $request->custom['mobile_no']);
                    if(strlen($key) >= 12) {
                        $codeArr = str_split($key, 2);
                        $cntryCode = '+'.$codeArr[0];
                        $mobileNum = @$codeArr[1].@$codeArr[2].@$codeArr[3].@$codeArr[4].@$codeArr[5].@$codeArr[6].@$codeArr[7];
                    }
                    else {
                        $cntryCode = '';
                        $mobileNum = trim($key);
                    }
                    if($cntryCode != '') {
                        $modelQuery = $modelQuery->where('patients.country_code','LIKE','%'.$cntryCode.'%')
                                                 ->where('patients.mobile_no','LIKE','%'.$mobileNum.'%');
                    }
                    else {
                        $modelQuery = $modelQuery->where('patients.mobile_no','LIKE','%'.$mobileNum.'%');
                    }
                    // $modelQuery = $modelQuery
                    // ->where('patients.mobile_no','LIKE','%'.$key.'%');
                    // //->whereRaw("MATCH(patients.mobile_no) AGAINST('".$key."')");

                }

                if (!empty($request->custom['birth_date']))
                {
                    $custom_search  = true;
                    $key            = $request->custom['birth_date'];
                    $modelQuery     = $modelQuery
                    ->where('patients.birth_date','=',$key);
                }

                // if (!empty($request->custom['place']))
                // {
                //     $custom_search  = true;
                //     $key            = $request->custom['place'];
                //     $modelQuery     = $modelQuery
                //     ->where('patients.place','LIKE','%'.$key.'%');
                // }

                if (isset($request->custom['status']))
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('patients.status', $key);
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
                        $query->orwhere('patients.mobile_no', 'LIKE', '%'.$search.'%');
                        $query->orwhere('patients.old_id', '=', $search);
                        // $query->orwhere('patients.birth_date', 'LIKE', '%'.$search.'%');
                        // $query->orwhere('patients.birth_date', '=', $search);
                        // $query->orwhere('patients.place', 'LIKE', '%'.$search.'%');
                        // if(strtolower($search)=="active"){
                        //     $query->orwhere('patients.status', '=', 1);
                        // }
                        // else{
                        //     $query->orwhere('patients.status', '=', 0);
                        // }
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
                        // $lname = ucfirst($row->last_name);
                        $familyName = ucfirst($row->family_name);

                        $data[$key]['fullname']  = '<span title="'.'concatenateNom'.'">'.$fname.' '.$familyName.'</span>';

                        $data[$key]['email']     = '<span title="'.$row->email.'">'.$row->email.'</span>';

                        $intCountryCode = $row->country_code;

                        $data[$key]['mobile_no']  =  "<span title='".$intCountryCode.$row->mobile_no."'>".$intCountryCode.$row->mobile_no."</span>";

                        $data[$key]['birth_date'] =  "<span title='".$row->birth_date."'>".$row->birth_date."</span>";

                        if(empty(Config('website_id')))
                        {

                            $data[$key]['ordination'] =  "<span title='".$row->birth_date."'>".self::_ordinationName($row->id)."</span>";
                        }  else
                        {
                            $data[$key]['ordination'] =  '';
                        }

                        // $data[$key]['place']  =  "<span title='".$row->place."'>".$row->place."</span>";

                        if (!empty($row->status))
                        {
                            $data[$key]['status'] = '<span class="theme-green semibold text-center f-18" >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</span>';
                        }
                        else
                        {
                            $data[$key]['status'] = '<span class="theme-black-light semibold text-center f-18" >'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</span>';
                        }

                        $edit="";
                        $delete="";

                        // Check Permission
                        if(auth()->user()->can('patients-add')){
                            $edit = '<a href="'.route($this->ModulePath.'edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                            $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route($this->ModulePath.'destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                             $viewExamination = '<a href="'.route($this->ModulePath.'examination.index', [ base64_encode(base64_encode($row->id))]).'" class="delete-user action-icon" title="'.__('admin.TITLE_VIEW_EXAMINATIONS_TEXT').'"><span class="nav-icon fas fa-diagnoses"></span></a>&nbsp&nbsp';
                             $viewDocument =  '<a href="'.route($this->ModulePath.'document.index', [ base64_encode(base64_encode($row->id))]).'" class="delete-user action-icon" title="'.__('admin.TITLE_VIEW_DOCUMENT_NAME').'"><span class="nav-icon fas fa-file"></span></a>';
                        }
                        if($row->sendMail==1){
                            $data[$key]['send_email'] = '<a data-status="0" data-id="'.$row->id.'"  href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.SPECIAL_ACCESS_YES_LABEL').'" onclick="return changeStatus(this)" ><label class="switch ml-auto">
                                    <input checked type="checkbox" class="checkbox-permissions">
                                    <span class="knob"></span>
                            </label></a>';
                        }
                        else{
                            $data[$key]['send_email'] ='<a data-status="1" data-id="'.$row->id.'" href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.SPECIAL_ACCESS_NO_LABEL').'" onclick="return changeStatus(this)" ><label class="switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions">
                            <span class="knob"></span>
                            </label></a>';
                        }
                        if($row->sendSMS==1){
                            $data[$key]['send_sms'] = '<a data-status="0" data-id="'.$row->id.'"  href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.SPECIAL_ACCESS_YES_LABEL').'" onclick="return changeSMSStatus(this)" ><label class="switch ml-auto">
                                    <input checked type="checkbox" class="checkbox-permissions">
                                    <span class="knob"></span>
                            </label></a>';
                        }
                        else{
                            $data[$key]['send_sms'] ='<a data-status="1" data-id="'.$row->id.'" href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.SPECIAL_ACCESS_NO_LABEL').'" onclick="return changeSMSStatus(this)" ><label class="switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions">
                            <span class="knob"></span>
                            </label></a>';
                        }
                        $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.$viewExamination.$viewDocument.'</div>';
                }
            }

            ## SEARCH HTML
            $val= '';
            if(isset($request->custom['birth_date']) && $request->custom['birth_date']!=''){

                $val = $request->custom['birth_date'];
            }
            $searchHTML['id']           =  '';
            $searchHTML['fullname']     =  '<input type="text" class="form-control" id="fullname" value="'.($request->custom['fullname'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['email']        =  '<input type="text" class="form-control" id="email" value="'.($request->custom['email'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['mobile_no']    =  '<input type="text" class="form-control" id="mobile_no" value="'.($request->custom['mobile_no'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['birth_date']   =  '<input type="text" class="form-control" id="birth_date" value="'.$val.'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['ordination']   =  '';
            // $searchHTML['place']     =  '<input type="text" class="form-control" id="place" value="'.($request->custom['place']).'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['status']   =  '<select name="status" id="status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_EXAMINATION_STATUS').'</option>
                    <option class="theme-black blue-select" value="1" '.(isset($request->custom['status']) &&  $request->custom['status'] == "1" ? 'selected' : '').' >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</option>
                    <option class="theme-black blue-select" value="0" '.( isset($request->custom['status']) && $request->custom['status'] == "0" ? 'selected' : '').'>'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</option>
                </select>';
            $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';
            /*}*/
            $searchHTML['send_email']='';
            $searchHTML['send_sms']='';
            $searchHTML['actions'] = $seachAction;
            array_unshift($data, $searchHTML);
            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;
        return response()->json($this->JsonData);
    }
    ##Mail Send CHAANGED
    public function changeEmailStatus(Request $request)
    {
        $this->JsonData['status']   = 'error';
        $this->JsonData['msg']      = __('admin.RESP_ERROR');
        $BaseModel = $this->BaseModel->find($request->id);
        $BaseModel->sendMail = $request->send_email;
        if($BaseModel->save())
        {
            $this->JsonData['status']   = 'success';
            if($BaseModel->sendMail === '1')
                $this->JsonData['msg']      = __('admin.MAIL_STATUS_SUCCESS');
            else
               $this->JsonData['msg']      = __('admin.MAIL_STATUS_FAIL');
        }
        return response()->json($this->JsonData);
    }

    ##SMS Send CHAANGED
    public function changeSMSStatus(Request $request)
    {
        $this->JsonData['status']   = 'error';
        $this->JsonData['msg']      = __('admin.RESP_ERROR');
        $BaseModel = $this->BaseModel->find($request->id);
        $BaseModel->sendSMS = $request->send_sms;
        if($BaseModel->save())
        {
            $this->JsonData['status']   = 'success';
            if($BaseModel->send_sms === '1')
                $this->JsonData['msg']      = __('admin.SMS_STATUS_SUCCESS');
            else
               $this->JsonData['msg']      = __('admin.SMS_STATUS_FAIL');
        }
        return response()->json($this->JsonData);
    }

    public function Examinationindex($id)  
    { 
        // dd($id); 
        // Default site patients 
        $this->ModuleTitle              =  __('admin.TITLE_PATIENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON'); 
        $this->ViewData['id']    = base64_decode(base64_decode($id)); 

        // view file with data
        return view($this->ModuleView.'examination-index', $this->ViewData);
    }

    public function getExamination(Request $request, $id) 
    {
        /*--------------------------------------
        |  Variables
        ------------------------------*/ 
        // dd('testData');
            // skip and limit
            $start  = $request->start;
            $length = $request->length;

            // serach value
            $search = $request->search['value'];

            // patient id
            // order
            $column = $request->order[0]['column'];
            $dir    = $request->order[0]['dir'];

            // filter columns
            $filter = array(
                0 => 'id',
                1 => 'appointment_has_examinations.patient_id',
                2 => 'appointment_has_examinations.examination_id',
                3 => 'appointment.start_date',
                // 3 => 'appointment_has_examinations.mobile_no', 
                // 4 => 'patients.birth_date',
                // 5 => 'patients.place',
                // 6 => 'status', 
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query

            //commented below query on 20-oct-23 to add examination deleted at null condition
            /*$modelQuery =  $this->AppointmentHasExaminationsModel
                        ->leftjoin('examinations','examinations.id','=','appointment_has_examinations.examination_id')
                        ->leftjoin('appointment','appointment.id','=','appointment_has_examinations.appointment_id')
                        ->leftjoin('patients', 'patients.id' , '=', 'appointment_has_examinations.patient_id')
                        ->where('appointment_has_examinations.patient_id', $id);*/

              //added on 20-oct-23          
             $modelQuery =  $this->AppointmentHasExaminationsModel
                        ->leftjoin('examinations','examinations.id','=','appointment_has_examinations.examination_id')
                        ->leftjoin('appointment','appointment.id','=','appointment_has_examinations.appointment_id')
                        ->leftjoin('patients', 'patients.id' , '=', 'appointment_has_examinations.patient_id')
                        ->where('appointment_has_examinations.patient_id', $id)
                        ->whereNULL('examinations.deleted_at')
                        ->where('appointment.appointment_status', '!=', 'Vermisst');//added on 27-oct-25          


            // dd($modelQuery->toSql());
            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

            # FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {
                // dd(empty($request->custom['role']));
                // if (!empty($request->custom['patient_id'])) 
                // {
                //     $name = explode(" ", $request->custom['patient_id']);

                //     if(!empty($name[1])){
                //         $key[0]         = $name[0];
                //         $key[1]         = $name[1];
                //         $custom_search  = true;                
                //         $modelQuery     = $modelQuery
                //         ->where('patients.first_name','LIKE','%'.$key[0].'%')
                //         ->orWhere('patients.family_name','LIKE','%'.$key[1].'%');
                //     } else{
                //         $key[0]         = $name[0];
                //         $custom_search  = true;                
                //         $modelQuery     = $modelQuery
                //         ->where('patients.first_name','LIKE','%'.$key[0].'%');
                //     }                    
                // } 

                // if (!empty($request->custom['examination_name'])) 
                // {
                //     $custom_search  = true;
                //     $key            = $request->custom['examination_name'];                
                //     $modelQuery     = $modelQuery
                //     ->where('examinations.name','LIKE','%'.$key.'%');
                // }

                // if (!empty($request->custom['examination_url'])) 
                // {
                //     $custom_search  = true;
                //     $key            = $request->custom['examination_url'];              
                //     $modelQuery     = $modelQuery
                //     ->where('examinations.url','LIKE','%'.$key.'%');
                // } 
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
                           
                        $query->orwhere('examinations.name', 'LIKE', '%'.$search.'%');   
                        $query->orwhere('examinations.url', 'LIKE', '%'.$search.'%');  
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
                                 ->get([
                                    'patients.first_name as patient_fname',
                                    'patients.family_name as patient_lname',
                                    'examinations.name as exam_name',
                                    'examinations.url',
                                    'appointment_has_examinations.id',
                                    'appointment.start_date',
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
                    $data[$key]['id']           = $row->id;

                    $fname = ucfirst($row->patient_fname);
                    // $lname = ucfirst($row->last_name);
                    $familyName = ucfirst($row->patient_lname);
                    $data[$key]['patient_id']= '<span title="'.'concatenateNom'.'">'.$fname.' '.$familyName.'</span>';
                    $data[$key]['examination_name'] = '<span title="'.$row->exam_name.'">'.$row->exam_name.'</span>';
                    $data[$key]['examination_url'] =  '<span title="'.$row->url.'">'.$row->url.'</span>';
                    $data[$key]['start_date'] = '<span>'.date('d-m-Y H:i:s', strtotime($row->start_date)).'</span>';
                } 
            }
            ## SEARCH HTML
            $searchHTML['id']           =  '';   
            $searchHTML['patient_id']           =  '';
            $searchHTML['examination_name']           =  '';
            $searchHTML['examination_url']           =  ''; 
            $searchHTML['start_date']           =  ''; 
            // $searchHTML['patient_id']     =  '<input type="text" class="form-control" id="patient_id" value="'.($request->custom['patient_id']).'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            // $searchHTML['examination_name']        =  '<input type="text" class="form-control" id="examination_name" value="'.($request->custom['examination_name']).'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            // $searchHTML['examination_url']    =  '<input type="text" class="form-control" id="examination_url" value="'.($request->custom['examination_url']).'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';  
            
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


    // public function getdata(Request $request, $id){
    //     // dd('tttttttttt');
    //     $id = $request->id;  
    //     $collections = $this->PatientHasExaminationsModel
    //                         ->leftjoin('examinations', 'examinations.id' , '=', 'appointment_has_examinations.examination_id')
    //                         ->leftjoin('patients', 'patients.id' , '=', 'appointment_has_examinations.patient_id')
    //                         ->where('patient_id', $id)
    //                         ->get([
    //                             'patients.id as pid',
    //                             'patients.first_name as patient_fname',
    //                             'patients.family_name as patient_lname',
    //                             'examinations.name as exam_name',
    //                             'examinations.url',
    //                         ]);   
    //                         // dd($collections);  

    //             $data = ''; 
    //             $data = "<thead><tr><th>Patient Name</th><th>Exam Name</th><th>URL</th></tr></thead><tbody>";
    //             foreach ($collections as $collection){
    //             $data .= "<tr><td>".$collection->patient_fname.' '.$collection->patient_fname."</td><td>".$collection->exam_name."</td><td>".$collection->url."</td></tr>";
    //             }
    //             $data .= "</tbody>";   
    //             echo $data; 
    //     }    
 
    public function _storeOrUpdate($collection, $request) 
    { 

        Log::info("in patient controller _storeOrUpdate function");
        Log::info($request->all());

        /*$mobile_no = '+49 157 7377 8209';
        // $mobile_no = '0042 915 821 583';
        $mobile_no = '0664 202 82 53';


        $internationalFormat = substr($mobile_no, 0, 1);
        $country_code=$rest_no = '';
        if($internationalFormat == '+'){
            $country_code = trim(substr($mobile_no, 1,2));
            $rest_no      = trim(str_replace(" ","",substr($mobile_no, 3)));
        }

        $internationalFormat = substr($mobile_no, 0, 2);
        if($internationalFormat == '00'){
            $country_code = trim(substr($mobile_no, 0,4));
            $rest_no      = trim(str_replace(" ","",substr($mobile_no, 4)));
        }

        $localFormat = substr($mobile_no, 0, 1);
        if($localFormat == '0' && $internationalFormat != '00'){
            $country_code = trim(substr($mobile_no, 0,1));
            $rest_no      = trim(str_replace(" ","",substr($mobile_no, 1)));
        }
        dd($mobile_no,$country_code,$rest_no);*/
        // dd($collection);
        //$collection->old_id        =  !empty($request->old_id)?$request->old_id:0; 
        // normalize mobile number: remove single leading zero, reject double zero via validator
        $raw = str_replace(" ", "", $request->mobile_no);
        if (!preg_match('/^0{2,}/', $raw)) {
            // strip one zero if present
            $mobile_no = preg_replace('/^0/', '', $raw);
        } else {
            $mobile_no = $raw; // validation should prevent this case
        }

        /*$startingCharacter = substr($mobile_no, 0, 1);

        if($startingCharacter!=0){
            // dd('test');

        }*/

        //dd($request->all()); 
        if(!empty($request->birth_date)){
            $birth_date                  = date('Y-m-d', strtotime($request->birth_date));
            $age                         = (date('Y') - date('Y',strtotime($birth_date)));
        }else{
            $birth_date                  = NULL;
            $age                         = 0;
        }

        $collection->family_name        = self::string_operation($request->family_name);
        $collection->first_name         = self::string_operation($request->first_name);
        // $collection->last_name          = $request->last_name;
        $collection->email              = $request->email;
        $collection->country_code       = $request->country_code;
        if(!empty($request->format))
        {
           $collection->country_code       = $request->format; 
        }        
        $collection->mobile_no          = $mobile_no;
        
        $collection->birth_date         = $birth_date; 
        $collection->age                = $age; 
        $collection->road               = self::string_operation($request->road);
        $collection->street_no          = $request->street_no;
        $collection->place              = self::string_operation($request->place);
        if(!empty($request->str_password)){
           
            // $collection->str_password   = $request->str_password;
            $collection->password       = Hash::make($request->str_password);
        }
        // $collection->place              = $request->place;
        $collection->is_blocked         = !empty($request->is_blocked)?1:0;
        $collection->login_type         = 'app';
        $collection->status             = !empty($request->status)?1:0;
        $collection->postal_code        = $request->postal_code;
        $collection->gender             = $request->gender;
        $collection->size               = $request->size;
        $collection->weight             = $request->weight;
        $collection->title              = $request->title;
        $collection->salutation         = $request->salutation;
        $collection->family_doctor      = $request->family_doctor;
        $collection->insurance_number   = $request->insurance_number;
        $collection->additional_insurance = $request->additional_insurance;
        $collection->update_ganydb      = $request->update_ganydb;
        $collection->reminder_active      = $request->reminder_active;
        $collection->country            = $request->country; //Roshani added this for CR #102 on 10-oct-24


        if(!empty($request->note) && !empty($request->last_appointment))
        {
           // Log::info("In not empty note");

            $collection->note_report_request= $request->note;
            $collection->note_report_request_flag = '1';
            $collection->patient_status_flag ='0';
            $collection->note_report_request_from= 'admin';
            
            $getCollectionRecord = $this->BaseModel->find($collection->id);
               //Added at 28july22 if db note and new note same then dont update the flag
            if($getCollectionRecord->note_report_request == $request->note)
            {

            }else{
               $collection->finding_request_admin_flag=1;
            }
           //new code appointment request same code api SendRequiredAdminGetOldFindings made by swapnil pawar 22-09-2022 
            $patient_Id =$collection->id;
            $last_appointment = $request->last_appointment;
            $patient_details  = $this->PatientsModel
                ->where('id',$patient_Id) 
                ->first();
            $patient_details['note_report_request_flag'] = '2';         
            $patient_details->save();
            if(!empty($request->last_appointment))
            {
                $appoinmantId = $this->AppointmentModel->where(['patient_id'=>$patient_Id,'start_date'=>$request->last_appointment])->first();
                if(!empty($appoinmantId))
                {
                    // $aptTypeId = $appoinmantId->appointment_type_id; //commneted by swapnil on 14-nov-22
                    $aptTypeId = $appoinmantId->id; //changed by swapnil on 14-nov-22 

                    $start_date = date('Y-m-d H:i:s',strtotime($appoinmantId->start_date));

                    $data_exits = $this->PatientsHasOldFindingModel->where(['fk_patient_id'=>$patient_Id,'appoinmant_date'=>$start_date,'appointment_id'=>$aptTypeId,'imported_flag'=>'0'])->count();
                    if($data_exits > 0)
                    {
                        //already exits;
                    }
                    else
                    {
                        $OldFindingModel = new $this->PatientsHasOldFindingModel;
                        $OldFindingModel->fk_patient_id   = $patient_Id;
                        $OldFindingModel->appointment_id  = $aptTypeId;
                        $OldFindingModel->appoinmant_date = $start_date;
                        $OldFindingModel->imported_flag = '0';
                        $OldFindingModel->created_at      = Date('Y-m-d');
                        $OldFindingModel->save();
                    }
                }
                $collection->finding_request_admin_flag=1;
            }
            //new code appointment request same code api SendRequiredAdminGetOldFindings made by swapnil pawar 22-09-2022  

        }
        else
        {  
            //else condition added on 28 july 22
           // $collection->note_report_request= ""; //commented on 27-dec-22
           // $collection->finding_request_admin_flag=0; //commented on 27-dec-22
        }
       

         //Below code added for if we have note and we did not update any field then it should not show empty entry in todolist of assistant dashboard

        $getCollectionRecord = $this->BaseModel->find($collection->id);

        $old_patient = $this->OldPatientsModel
                          ->where('fk_patient_id',$collection->id)
                          ->first();


         if(isset($old_patient) && !empty($old_patient) && isset($getCollectionRecord))
         {
          
           /* if($old_patient->family_name != $getCollectionRecord->family_name || $old_patient->first_name != $getCollectionRecord->first_name || $old_patient->email != $getCollectionRecord->email || $old_patient->road != $getCollectionRecord->road || $old_patient->street_no != $getCollectionRecord->street_no || $old_patient->postal_code != $getCollectionRecord->postal_code || $old_patient->place != $getCollectionRecord->place || $old_patient->insurance_number != $getCollectionRecord->insurance_number || $old_patient->birth_date != $getCollectionRecord->birth_date || $old_patient->mobile_no != $getCollectionRecord->mobile_no || $old_patient->size != $getCollectionRecord->size || $old_patient->weight != $getCollectionRecord->weight || $old_patient->title != $getCollectionRecord->title || $old_patient->family_doctor != $getCollectionRecord->family_doctor || $old_patient->additional_insurance != $getCollectionRecord->additional_insurance || strtolower($old_patient->gender) != strtolower($getCollectionRecord->gender))*/

            //Added below code on 27-dec-22                  
            $family_name_req=$first_name_req=$email_req=$road_req=$street_no_req=$postal_code_req=$place_req=$insurance_number_req=$birth_date_req=$mobile_no_req=$size_req=$weight_req=$title_req=$family_doc_req=$additional_insur_req=$gender_req=0;

            $family_name_req=(isset($request->family_name) && $request->family_name!=$old_patient->family_name) ? 1:0;   
            $first_name_req=(isset($request->first_name) && $request->first_name!=$old_patient->first_name) ? 1:0;
            $email_req=(isset($request->email) && $request->email!=$old_patient->email) ? 1:0;
            $road_req=(isset($request->road) && $request->road!=$old_patient->road) ? 1:0;        
            $street_no_req=(isset($request->street_no) && $request->street_no!=$old_patient->street_no) ? 1:0;          
            $postal_code_req=(isset($request->postal_code) && $request->postal_code!=$old_patient->postal_code) ? 1:0;  
            $place_req=(isset($request->place) && $request->place!=$old_patient->place) ? 1:0;         
            $insurance_number_req=(isset($request->insurance_number) && $request->insurance_number!=$old_patient->insurance_number) ? 1:0;   
            $birth_date_req=(isset($birth_date) && $birth_date!=$old_patient->birth_date) ? 1:0;
            $mobile_no_req=(isset($mobile_no) && $mobile_no!=$old_patient->mobile_no) ? 1:0;
            $size_req=(isset($request->size) && $request->size!=$old_patient->size) ? 1:0;
            $weight_req=(isset($request->weight) && $request->weight!=$old_patient->weight) ? 1:0;
            $title_req=(isset($request->title) && $request->title!=$old_patient->title) ? 1:0;
            $family_doc_req=(isset($request->family_doctor) && $request->family_doctor!=$old_patient->family_doctor) ? 1:0; 
            $additional_insur_req=(isset($request->additional_insurance) && $request->additional_insurance!=$old_patient->additional_insurance) ? 1:0;                
            $gender_req=(isset($request->gender) && strtolower($request->gender)!=strtolower($old_patient->gender)) ? 1:0;                  



            if($family_name_req==1 || $first_name_req==1 || $email_req==1 || $road_req==1 || $street_no_req==1 || $postal_code_req==1 || $place_req==1 || $insurance_number_req==1 || $birth_date_req==1 || $mobile_no_req==1 || $size_req==1 || $weight_req==1 || $title_req==1 || $family_doc_req==1 || $additional_insur_req==1 || $gender_req==1)
            {
                //if updated any details
              
                 $collection->patient_status_flag ='0'; //added on 27dec22 to update any entry
            }
            else
            {
                // Log::info('not updated any details');
                // Log::info($collection->patient_status_flag);

                //if not updated details and also note is not updated but note has value then should not show empty entry
                
                //commented below condition on 22-may-23
                /*if(isset($request->note) && ($getCollectionRecord->note_report_request == $request->note))
                {
                    $collection->update_ganydb = 0;
                    $collection->patient_status_flag ='1';
                }*/

                //  Log::info($collection->patient_status_flag);
            }
         }//if        


       // Log::info($collection->toArray());

        //Save data
        $collection->save();   


        /*****roshani code for #102 added on 8-nov-24****************/
        if(!empty(Config('ordination_id')))
        {
            $ordination_patient = self::addPatientCountryOnOrdination($collection->id);
        }
        /******roshani code for #102 added on 8-nov-24******************/


        return $collection;  
        
    } 

    // public function importData(Request $request) 
    // {
    //     $this->JsonData['status'] = __('admin.RESP_ERROR');
    //     $this->JsonData['msg'] =  __('admin.FAIL_ROLE_CREATE');

    //     $filename = $request->file('select_file');

    //     if (file_exists($filename) && is_readable ($filename)) {
    //         $fileResource  = fopen($filename, "r");

    //         if ($fileResource) {
    //             $id = [];
    //             $insert_data = []; 
    //             $key = 0;
    //             while (($line = fgets($fileResource)) !== false) {
    //                 if($line)
    //                 {
    //                     $hashPos = strpos($line, '#');
    //                     // dd($id);
    //                     $idString = substr($line, 0, $hashPos);
    //                     // dd($idString);
    //                    // dump($line);
    //                     if(!in_array($idString,$id))
    //                     {
    //                         $key++;

    //                         // dump($line);
    //                         $id[] = $idString; 
                            
    //                         if(($pos = strpos($line, 'PNMR')) !== false){
                                
    //                             $patient_id = substr($line, $pos+19);
    //                              // dd($patient_id);
    //                             $insert_data[$key]['pat_nr'] = trim($patient_id); 
    //                         } 
    //                         //dump($line);
    //                         // dd(strpos($line, 'PFNM'));
    //                     }else{

    //                         if(($pos = strpos($line, '#PVNM')) !== false){
                                
    //                             $fname = substr($line, $pos+17);
    //                             // dd($fname);
    //                             $insert_data[$key]['first_name'] = trim($fname);
    //                         } 
    //                         if(($pos = strpos($line, '#PFNM')) !== false){

    //                             $lname = substr($line, $pos+17);
    //                             $insert_data[$key]['family_name'] = trim($lname);
    //                         } 
    //                         if(($pos = strpos($line, '#PGBD')) !== false){

    //                             $bdate = trim(substr($line, $pos+17));

    //                             $day = substr($bdate, 0,2);
    //                             $month = substr($bdate, 2,2);
    //                             $year = substr($bdate, 4,4);

    //                             $insert_data[$key]['birth_date'] = date("Y-m-d",strtotime($year."-".$month."-".$day));
    //                         } 
    //                         if(($pos = strpos($line, '#PVNR')) !== false){

    //                             $insurance_number = substr($line, $pos+17);
    //                             $insert_data[$key]['insurance_number'] = trim($insurance_number);
    //                         } 
    //                         if(($pos = strpos($line, '#PTL')) !== false){

    //                             $mobile_no = substr($line, $pos+17);
    //                             $insert_data[$key]['mobile_no'] = trim($mobile_no);
    //                         } 
    //                         if(($pos = strpos($line, '#PTL')) !== false){

    //                             $mobile_no = substr($line, $pos+17);
    //                             $insert_data[$key]['mobile_no'] = trim($mobile_no);
    //                         } 
    //                         if(($pos = strpos($line, '#PSTR')) !== false){

    //                             $road = substr($line, $pos+17);
    //                             $insert_data[$key]['road'] = trim($road);
    //                         } 
    //                         if(($pos = strpos($line, '#PPLZ')) !== false){

    //                             $postal_code = substr($line, $pos+17);
    //                             $insert_data[$key]['postal_code'] = trim($postal_code);
    //                         } 
    //                         if(($pos = strpos($line, '#PORT')) !== false){

    //                             $place = substr($line, $pos+17);
    //                             $insert_data[$key]['place'] = trim($place);
    //                         }
    //                         if(($pos = strpos($line, '#PGES')) !== false){

    //                             $gender = substr($line, $pos+17);
    //                             $insert_data[$key]['gender'] = trim($gender);
    //                         }
    //                     } 
    //                 }   
    //             }

    //         try {

    //             /* the below fields are not added yet, as not got idear from where to take
    //             $tmp['size']                = $patientGanymedRecord->groesse;
    //             $tmp['weight']              = $patientGanymedRecord->gewicht;
    //             $tmp['title']               = $patientGanymedRecord->titel;
    //             $tmp['salutation']          = $patientGanymedRecord->anrede;
    //             $tmp['family_doctor']       = $patientGanymedRecord->Hausarzt;
    //             $tmp['additional_insurance'] = $patientGanymedRecord->zu_vers;*/
    //              //dd($insert_data);
    //             // $this->BaseModel->updateOrInsert($insert_data); 

    //             foreach ($insert_data as $value) {
    //                 $pat_nr = $value['pat_nr'];
    //                 // $this->BaseModel->updateOrInsert($value);
    //                 $pId = $this->BaseModel->where('pat_nr', $pat_nr)->first();
    //                 // dd($pId);
    //                 if ($pId === null) { 
    //                     // dd('not found');
    //                     $this->BaseModel->insert($value);
                        
    //                 } else{
    //                     // dd('found');
    //                     // dd($insert_data);
    //                     // echo $pat_nr;
    //                     $this->BaseModel->where('pat_nr', $pat_nr)->update($value);
    //                 }
    //             }

    //             if ($insert_data)  
    //             {   
    //                 $this->JsonData['status'] = __('admin.RESP_SUCCESS');
    //                 $this->JsonData['url']    =  route($this->ModulePath.'index');
    //                 $this->JsonData['msg']    = __('admin.DATA_IMPORTED');
    //             }
    //         }
    //         catch(\Exception $e) {

    //             $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
    //             $this->JsonData['error_msg'] = $e->getMessage();
    //         }
    //     }
    //     fclose($fileResource);
    //     }
    //     return response()->json($this->JsonData);

    // } 



    public function importData_live_renamed_on_23_feb_24(Request $request) 
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] =  __('admin.FAIL_ROLE_CREATE');
        DB::beginTransaction();
        try
        {
            //fk_ordination_id
            $filename = $request->file('select_file');

            if (!file_exists($filename) || !is_readable($filename))
            return false;

            $header = null;
            $data = array();
            if (($handle = fopen($filename, 'r')) !== false)
            {
                while (($row = fgetcsv($handle, 1000)) !== false)
                {
                    if (!$header){
                        $header = $row;                   
                    }
                    else
                    {
                        $tmp = [];
                        $tmp['old_id']          = mb_convert_encoding($row[0], 'UTF-8', 'UTF-8');
                        $tmp['pat_nr']          = mb_convert_encoding($row[1], 'UTF-8', 'UTF-8');
                        $tmp['family_name']     = mb_convert_encoding($row[2], 'UTF-8', 'UTF-8');
                        $tmp['first_name']      = mb_convert_encoding($row[3], 'UTF-8', 'UTF-8');
                        $tmp['email']           = mb_convert_encoding($row[4], 'UTF-8', 'UTF-8');
                        $tmp['country_code']    = mb_convert_encoding($row[5], 'UTF-8', 'UTF-8');
                        $tmp['mobile_no']       = mb_convert_encoding($row[6], 'UTF-8', 'UTF-8');

                        if(!empty($row[7]))
                        {
                            $tmp['birth_date']  = date('Y-m-d', strtotime($row[7]));
                            $tmp['age']         = (date('Y') - date('Y',strtotime($tmp['birth_date'])));
                        }
                        else
                        {
                            $tmp['birth_date']  = null;
                            $tmp['age']         = 0;
                        }
                        $tmp['password']        = $row[8];
                        // $tmp['str_password']    = $row[9];
                        $tmp['status'] = $row[10];
                        $tmp['road']            = mb_convert_encoding($row[11], 'UTF-8', 'UTF-8');
                        $temp['street_no']      = mb_convert_encoding($row[12], 'UTF-8', 'UTF-8');
                        $tmp['place']           = mb_convert_encoding($row[13], 'UTF-8', 'UTF-8');
                        $tmp['postal_code']     = mb_convert_encoding($row[14], 'UTF-8', 'UTF-8');
                        $tmp['gender']          = mb_convert_encoding($row[15], 'UTF-8', 'UTF-8');
                        $tmp['size']            = mb_convert_encoding($row[16], 'UTF-8', 'UTF-8');
                        $tmp['weight']          = mb_convert_encoding($row[17], 'UTF-8', 'UTF-8');
                        $tmp['title']           = mb_convert_encoding($row[18], 'UTF-8', 'UTF-8');
                        $tmp['salutation']      = mb_convert_encoding($row[19], 'UTF-8', 'UTF-8');
                        $tmp['family_doctor']   = mb_convert_encoding($row[20], 'UTF-8', 'UTF-8');
                        //dd($row[21]);
                        $tmp['insurance_number']= $row[21];
                        $tmp['additional_insurance'] = $row[22]; 
                        $tmp['gdpr']            = $row[23];

                        $tmp['created_at'] = date('Y-m-d H:i:s');

                        $tenantPatientId =  DB::
                                            table("patients")
                                            ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($tmp['family_name']))
                                            ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($tmp['first_name']))
                                            ->whereNULL('deleted_at')             
                                            ->orderBy('created_at','DESC')
                                            ->pluck('id')
                                            ->first();

                        if(empty($tenantPatientId))
                        {
                            DB::commit();
                            $this->BaseModel->insert($tmp); 

                            $parentPatientId =  DB::connection('system')
                                                ->table("patients")
                                                ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($tmp['family_name']))
                                                ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($tmp['first_name']))
                                                ->whereNULL('deleted_at')             
                                                ->orderBy('created_at','DESC')
                                                ->pluck('id')
                                                ->first();
                           
                            //$ordination_id =  Session::get('current_ordination'); 
                            $ordination_id = Config('ordination_id');                               
                            if(!empty($parentPatientId))
                            {
                                
                                $assign_ordination = DB::connection('system')
                                    ->table("patients_has_ordination")
                                    ->where(['fk_ordination_id'=>$ordination_id,
                                    'fk_patient_id' => $parentPatientId])
                                    ->count();
                                  
                                if($assign_ordination > 0 )
                                {
                                    $checkInsertion = DB::connection('system')
                                                    ->table("patients_has_ordination")
                                                    ->insert(['fk_ordination_id'=>$ordination_id,
                                                    'fk_patient_id' => $parentPatientId,
                                                    'status'=>'1']);
                                }
                                DB::commit();
                            }
                            else
                            {
                               
                                $parentPatientInsertedId = DB::connection('system')
                                    ->table("patients")->insertGetId($tmp);
                                  
                                $checkInsertion = DB::connection('system')
                                ->table("patients_has_ordination")
                                ->insert(['fk_ordination_id'=> $ordination_id,
                                'fk_patient_id' => $parentPatientInsertedId,
                                'status'=>'1']);
                                 DB::commit();
                            }
                        }

                    }
                }           
            }
         
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            $this->JsonData['url']    =  route($this->ModulePath.'index');
            $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
            return response()->json($this->JsonData);
        }catch(Exception $e)
        {
            DB::rollback();
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.ERR_SOMETHING_WRONG'); 
        }
    }// importData


    //Did changes in below function for patient import on 23-feb-24
    public function importData(Request $request) 
    {
      //  dump($request->all());

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] =  __('admin.FAIL_ROLE_CREATE');
        DB::beginTransaction();
        $all_transactions = [];

        try
        {
            //fk_ordination_id
            $filename = $request->file('select_file');

            if (!file_exists($filename) || !is_readable($filename))
            return false;

            $header = null;
            $data = array();
            if (($handle = fopen($filename, 'r')) !== false)
            {
                while (($row = fgetcsv($handle, 1000,';')) !== false)
                {

                   // dump($row);

                   /* if (!$header){
                        $header = $row;                   
                    }
                    else
                    {*/
                        $tmp = [];
                        // $tmp['old_id']          = mb_convert_encoding($row[0], 'UTF-8', 'UTF-8');

                        // $tmp['pat_nr']          = mb_convert_encoding($row[0], 'UTF-8', 'UTF-8');
                        $tmp['family_name']     = mb_convert_encoding($row[0], 'UTF-8', 'UTF-8');
                        $tmp['first_name']      = mb_convert_encoding($row[1], 'UTF-8', 'UTF-8');
                        $tmp['email']           = mb_convert_encoding($row[2], 'UTF-8', 'UTF-8');
                        $tmp['country_code']    = '+'.mb_convert_encoding($row[3], 'UTF-8', 'UTF-8');
                        $tmp['mobile_no']       = mb_convert_encoding($row[4], 'UTF-8', 'UTF-8');

                        if(!empty($row[5]))
                        {
                            $tmp['birth_date']  = date('Y-m-d', strtotime($row[5]));
                            $tmp['age']         = (date('Y') - date('Y',strtotime($tmp['birth_date'])));
                        }
                        else
                        {
                            $tmp['birth_date']  = null;
                            $tmp['age']         = 0;
                        }
                        // $tmp['password']   = $row[8];
                         // $tmp['password']    = Hash::make('Test@123');



                        // $tmp['str_password']    = $row[9];
                        $tmp['status'] = $row[6];
                        $tmp['road']            = mb_convert_encoding($row[7], 'UTF-8', 'UTF-8');
                        $temp['street_no']      = mb_convert_encoding($row[8], 'UTF-8', 'UTF-8');
                        $tmp['place']           = mb_convert_encoding($row[9], 'UTF-8', 'UTF-8');
                        $tmp['postal_code']     = mb_convert_encoding($row[10], 'UTF-8', 'UTF-8');
                        $tmp['gender']          = mb_convert_encoding($row[11], 'UTF-8', 'UTF-8');
                        $tmp['size']            = mb_convert_encoding($row[12], 'UTF-8', 'UTF-8');
                        $tmp['weight']          = mb_convert_encoding($row[13], 'UTF-8', 'UTF-8');
                        $tmp['title']           = mb_convert_encoding($row[14], 'UTF-8', 'UTF-8');
                        $tmp['salutation']      = mb_convert_encoding($row[15], 'UTF-8', 'UTF-8');
                        $tmp['family_doctor']   = mb_convert_encoding($row[16], 'UTF-8', 'UTF-8');
                        //dd($row[21]);
                        $tmp['insurance_number']= $row[17];
                        $tmp['additional_insurance'] = $row[18]; 
                        $tmp['gdpr']            = $row[19];

                        $tmp['created_at'] = date('Y-m-d H:i:s');

                        // dump($tmp);

                        $tenantPatientId =  DB::table("patients")
                                            // ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($tmp['family_name']))
                                            // ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($tmp['first_name']))

                                            ->whereDate('birth_date', date('Y-m-d',strtotime($tmp['birth_date'])))
                                            ->where('mobile_no', $tmp['mobile_no'])
                                            ->whereNULL('deleted_at')             
                                            ->orderBy('created_at','DESC')
                                            ->pluck('id')
                                            ->first();

                        //  dump('temp patientid');
                        //  dump($tenantPatientId);                  

                        if(empty($tenantPatientId))
                        {
                           // dump('innnnnnn');

                            DB::commit();
                            // $this->BaseModel->insert($tmp); 

                           // dump($tmp);

                            $patientId = DB::table('patients')->insertGetId($tmp); 


                            if(isset($patientId) && $patientId != '')
                            {

                               $all_transactions[] = 1;  

                                $setReminders = app('App\Http\Controllers\Admin\DashboardController')->checkPatientAgeReminder($patientId);
                            }





                           // dump('after insert...');


                            $parentPatientId =  DB::connection('system')
                                                ->table("patients")
                                                // ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($tmp['family_name']))
                                                // ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($tmp['first_name']))
                                                ->whereDate('birth_date', date('Y-m-d',strtotime($tmp['birth_date'])))
                                                ->where('mobile_no', $tmp['mobile_no'])
                                                ->whereNULL('deleted_at')             
                                                ->orderBy('created_at','DESC')
                                                ->pluck('id')
                                                ->first();
                           
                            //$ordination_id =  Session::get('current_ordination'); 
                            $ordination_id = Config('ordination_id');                               
                            if(!empty($parentPatientId))
                            {
                                
                               // dump('in');
                                $assign_ordination = DB::connection('system')
                                    ->table("patients_has_ordination")
                                    ->where(['fk_ordination_id'=>$ordination_id,
                                    'fk_patient_id' => $parentPatientId])
                                    ->count();
                                  
                                if($assign_ordination > 0 )
                                {
                                    $checkInsertion = DB::connection('system')
                                                    ->table("patients_has_ordination")
                                                    ->insert(['fk_ordination_id'=>$ordination_id,
                                                    'fk_patient_id' => $parentPatientId,
                                                    'status'=>'1']);
                                }
                                DB::commit();
                            }
                            else
                            {
                                // dump('else ');
                                $parentPatientInsertedId = DB::connection('system')
                                    ->table("patients")->insertGetId($tmp);
                                  
                                $checkInsertion = DB::connection('system')
                                ->table("patients_has_ordination")
                                ->insert(['fk_ordination_id'=> $ordination_id,
                                'fk_patient_id' => $parentPatientInsertedId,
                                'status'=>'1']);
                                 DB::commit();
                            }
                        }//not empty tenant patient id
                        else
                        {
                            DB::rollback();
                            $all_transactions[] = 0;
                        }


                    //}
                }           
            }

            if (!in_array(0,$all_transactions))
            {
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'index');
                $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
                 
            }
            else
            {
                 DB::rollback();
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['msg']    = __('admin.ERR_IMPORT_PATIENT'); 
            }
         
            // $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            // $this->JsonData['url']    =  route($this->ModulePath.'index');
            // $this->JsonData['msg']    = __('admin.TODO_LIST_IMPORT_CREATE');
            return response()->json($this->JsonData);
        }catch(Exception $e)
        {
            DB::rollback();
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.ERR_SOMETHING_WRONG'); 
        }
    }//importData 




    public function Documentindex($id)  
    {
        // Default site patients 
        $patient_data = $this->BaseModel->where('id',base64_decode(base64_decode($id)))->first();
        $this->ModuleTitle              = $patient_data->family_name." ".$patient_data->first_name;
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_DOCUMENT_NAME');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON'); 
        $this->ViewData['id']    = base64_decode(base64_decode($id));
        // view file with data
        $this->ViewData['patient_id']   = (base64_decode(base64_decode($id)));
        $this->ViewData['doc_send_msg'] = 'Are you sure!!';
        //dd($this->ViewData);
        $this->ViewData['msg_send_doc_for_patient']= __('admin.MSG_SEND_DOC_FOR_PATIENT');
        $this->ViewData['title_warning']       = __('admin.RESP_WARNING');

        return view($this->ModuleView.'document-index', $this->ViewData);
    }


    // public function getDocument(Request $request, $id) 
    // {
    //     /*--------------------------------------
    //     |  Variables
    //     ------------------------------*/ 
    //     // dd('testData');
    //         // skip and limit
    //         $start  = $request->start;
    //         $length = $request->length;

    //         // serach value
    //         $search = $request->search['value'];

    //         // patient id
    //         // order
    //         $column = $request->order[0]['column'];
    //         $dir    = $request->order[0]['dir'];

    //         // filter columns
    //         $filter = array(
    //             0 => 'id',
    //             1 => 'name',
    //             2 => 'document_name',
    //             3 => 'document_url',
    //             4 => 'record_type', 
    //             5 => 'docuement_status',
    //             6 => 'Signature Image', 
    //             // 4 => 'patients.birth_date',
    //             // 5 => 'patients.place',
    //             // 6 => 'status', 
    //         );

    //     /*--------------------------------------
    //     |  Model query and filter
    //     ------------------------------*/

    //         // start model query
    //         $modelQuery =  $this->PatientHasDocumentsModel
                                
    //                             ->leftjoin('examinations', function($query)
    //                             {
    //                                 $query->on('examinations.id' , '=', 'patient_has_documents.exam_app_type_id');
    //                                 $query->where('record_type' ,'0');
    //                                 $query->where('examinations.name','<>' ,'');
    //                             })
    //                              ->leftjoin('appointment_types', function($query)
    //                             {
    //                                 $query->on('appointment_types.id' , '=', 'patient_has_documents.exam_app_type_id');
    //                                  $query->where('record_type' ,'1');
    //                                  $query->where('appointment_types.name','<>' ,'');
    //                             })
    //                             ->where('patient_id', $id);
    //         // get total count 
    //         $countQuery = clone($modelQuery);            
    //         $totalData  = $countQuery->count();

    //         # FILTER OPTIONS for specific field 
    //         $custom_search = false;           

    //         // Common filter options
    //         if (!empty($request->search))
    //         {
    //             if (!empty($request->search['value'])) 
    //             {
    //                 $search = $request->search['value'];

    //                 $modelQuery = $modelQuery->where(function ($query) use($search)
    //                 {                                               
    //                     $query->orwhere('examinations.document_name', 'LIKE', '%'.$search.'%');   
    //                     $query->orwhere('examinations.document_path', 'LIKE', '%'.$search.'%');
    //                     $query->orwhere('appointment_types.patient_document', 'LIKE', '%'.$search.'%');
    //                     $query->orwhere('appointment_types.patient_document_path', 'LIKE', '%'.$search.'%'); 
    //                      $query->orwhere('examinations.name', 'LIKE', '%'.$search.'%');   
    //                     $query->orwhere('appointment_types.name', 'LIKE', '%'.$search.'%'); 
    //                 });
    //             }
    //         }

    //         // get total filtered
    //         $filteredQuery  = clone($modelQuery);            
    //         $totalFiltered  = $filteredQuery->count();   
            
    //         // offset and limit
    //         $object = $modelQuery->orderBy($filter[$column], $dir)
    //                              ->skip($start)
    //                              ->take($length)
    //                              ->get(['patient_has_documents.id',
    //                                 'examinations.name as e_name',
    //                                 'appointment_types.name as a_name',
    //                                 'examinations.document_name as doc_name',
    //                                 'examinations.document_name as doc_name',
    //                                 'examinations.document_path as doc_path',
    //                                 'appointment_types.patient_document as app_doc_name',
    //                                 'appointment_types.patient_document_path as app_doc_path',
    //                                 'patient_has_documents.record_type',
    //                                 'patient_has_documents.doc_status','patient_has_documents.remarks']);  

                 
            
    //     /*--------------------------------------
    //     |  data binding
    //     ------------------------------*/
    //         $data = [];
    //         if (!empty($object) && sizeof($object) > 0) 
    //         {
    //             foreach ($object as $key => $row) 
    //             {                    
                                   

    //                 if($row->record_type == '0' )
    //                 {
    //                     $data[$key]['id']           = $row->id;    
    //                     $data[$key]['name']           = $row->e_name;    
    //                     $data[$key]['document_name']     = '<span title="'.$row->doc_name.'">'.$row->doc_name.'</span>';
    //                     $data[$key]['document_url']     = '<span title="'.url('/storage'.$row->app_doc_path).'">'.url('/storage'.$row->doc_path).'</span>';  
    //                     $data[$key]['record_type']  =  __('admin.TITLE_EXAMINATIONS_TEXT');
    //                     if($row->doc_status == '0' )
    //                     {
    //                         $data[$key]['document_status']  =  __('admin.TITLE_SELECT_DOCUMENT_UNREAD');
    //                     }
    //                     elseif($row->doc_status == '1' )
    //                     {
    //                         $data[$key]['document_status']  =  __('admin.TITLE_SELECT_DOCUMENT_READ');
    //                     }
    //                     elseif($row->doc_status == '2' )
    //                     {
    //                         $data[$key]['document_status']  =  __('admin.TITLE_SELECT_DOCUMENT_SIGN')."<a href='".url('/storage'.$row->remarks)."'> View</a>";
    //                     }

    //                 }
    //                 if($row->record_type == '1' )
    //                 {
    //                     $data[$key]['id']           = $row->id;  
    //                     $data[$key]['name']           = $row->a_name;    
    //                     $data[$key]['document_name']     = '<span title="'.$row->app_doc_name.'">'.$row->app_doc_name.'</span>'; 
    //                     $data[$key]['document_url']     = '<span title="'.url('/storage'.$row->app_doc_path).'">'.url('/storage'.$row->app_doc_path).'</span>'; 
    //                     $data[$key]['record_type']  =  __('admin.TITLE_ROSTER_APPOINTMENT_TYPE');
    //                     $data[$key]['document_status']  =  __('admin.TITLE_EXAMINATIONS_TEXT');
    //                     if($row->doc_status == '0' )
    //                     {
    //                         $data[$key]['document_status']  =  __('admin.TITLE_SELECT_DOCUMENT_UNREAD');
    //                     }
    //                     elseif($row->doc_status == '1' )
    //                     {
    //                         $data[$key]['document_status']  =  __('admin.TITLE_SELECT_DOCUMENT_READ');
    //                     }
    //                     elseif($row->doc_status == '2' )
    //                     {
    //                         $data[$key]['document_status']  = "<a href='".url('/storage'.$row->remarks)."' target='_new'>". __('admin.TITLE_SELECT_DOCUMENT_SIGN')."</a>";
    //                     }
    //                 }  

                                        
                   
    //             } 
    //         }

    //         ## SEARCH HTML 
    //         $searchHTML['id']           =  '';   
    //         $searchHTML['name']           =  '';   
    //         $searchHTML['document_name']           =  '';
    //         $searchHTML['document_url']           =  ''; 
    //         $searchHTML['record_type']  = ''; 
    //         $searchHTML['document_status']  = '';
         
            
    //         array_unshift($data, $searchHTML);

    //         // wrapping up
    //         $this->JsonData['draw']             = intval($request->draw);
    //         $this->JsonData['recordsTotal']     = intval($totalData);
    //         $this->JsonData['recordsFiltered']  = intval($totalFiltered);
    //         $this->JsonData['data']             = $data;

    //     return response()->json($this->JsonData); 
    // }

    public function getDocument_old(Request $request, $id) 
    {
        /*--------------------------------------
        |  Variables
        ------------------------------*/ 
        // dd('testData');
            // skip and limit
            $start  = $request->start;
            $length = $request->length;

            // serach value
            $search = $request->search['value'];

            // patient id
            // order
            $column = $request->order[0]['column'];
            $dir    = $request->order[0]['dir'];

            // filter columns
            $filter = array(
                0 => 'id',
                1 => 'name',
                2 => 'document_url',
                3 => 'type', 
                4 => 'docuement_status',
                // 4 => 'patients.birth_date',
                // 5 => 'patients.place',
                // 6 => 'status',  document_name
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/
       
            // start model query
            // check list 
            $modelQuery  =  $this->CheckListHasSelectedQuestionModel
                            ->selectRaw("examinations_check_list.check_list_name as name,
                                check_list_has_selected_questions.pdf_path,
                                check_list_has_selected_questions.pdf_name,
                                check_list_has_selected_questions.type,
                                check_list_has_selected_questions.id,
                                check_list_has_selected_questions.status,
                                'Checklist' as document_type"
                            )
                            ->leftjoin('examinations_has_multiple_check_list','examinations_has_multiple_check_list.fk_examinations_id','check_list_has_selected_questions.fk_examination_id')
                            ->leftjoin('examinations_check_list','examinations_check_list.id','check_list_has_selected_questions.fk_check_list_id')
                            ->where('check_list_has_selected_questions.fk_patient_id',$id)
                            ->whereNULL('examinations_check_list.deleted_at');
                            // ->whereNotNull('check_list_has_selected_questions.fk_check_list_id')
                            // ->whereNotNull('check_list_has_selected_questions.pdf_path');
                      
            //dd($modelQuery); filteredQuery
            //Document list 
            $modelQuery1  =  $this->PatientHasDocumentsModel
                             ->selectRaw("specialist_has_documents.name as name,
                                patient_has_documents.pdf_path,
                                patient_has_documents.pdf_name,
                                patient_has_documents.type,
                                patient_has_documents.id,
                                patient_has_documents.doc_status as status,
                                'Document' as document_type"
                            )
                             ->leftjoin('specialist_has_documents','specialist_has_documents.id','patient_has_documents.fk_document_id')
                             //->whereNotNull('patient_has_documents.fk_document_id')
                             ->where('patient_has_documents.patient_id',$id)
                             ->where('patient_has_documents.pdf_path','!','');

            
            //dd($modelQuery1);
            // $getcheckList  =  $this->PatientHasDocumentsModel
            //                   ->where('patient_id',$id)                  
            
            // get total count ,
            $countQuery  = clone($modelQuery);  
            $countQuery1 = clone($modelQuery1); 
            $countQuery->union($countQuery1); 
            $countQuery  = $countQuery->get();           
            $totalData   = $countQuery->count();
            //dd($totalData);
            # FILTER OPTIONS for specific field 
            $custom_search = false;           

            // Common filter options
            if (!empty($request->search))
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];

                    $modelQuery = $modelQuery->where(function ($query) use($search)
                    {                                               
                        $query->orwhere('examinations_check_list.check_list_name', 'LIKE', '%'.$search.'%');
                        $query->orwhere('check_list_has_selected_questions.type', 'LIKE', '%'.$search.'%');
                        $query->orwhere('check_list_has_selected_questions.status', 'LIKE', '%'.$search.'%'); 
                    });
                }
            }

            // get total filtered
            $countQuery  = clone($modelQuery);  
            $countQuery1 = clone($modelQuery1); 
            $countQuery->union($countQuery1); 
            $countQuery  = $countQuery->get();                 
            $totalFiltered  = $countQuery->count();   
            
            // offset and limit
            // $object = $modelQuery->orderBy($filter[$column], $dir)
            //                      ->skip($start)
            //                      ->take($length)
            //                      ->get();  
            $modelQuery->union($modelQuery1);
            $object = $modelQuery->orderBy($filter[$column], $dir)
                    ->skip($start)
                    ->take($length)
                    ->get();
            //dd($object);
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row) 
                {   
                    if (!empty($row->pdf_path)) 
                    {
                        $data[$key]['id']             = $row->id;    
                        $data[$key]['name']           = $row->name;  
                        
                        if(!empty($row->pdf_path) && $row->pdf_path!='0')
                        {
                            //dd($row->pdf_path);
                            $path = self::getFilePath($row->pdf_path);
                            $data[$key]['document_url']   = '<a href="'.$path.'" target="_blank" title="'.$path.'">'.$path.'</a>';
                        } 
                        else
                        {
                            $data[$key]['document_url']   = '';
                        } 
                          
                    
                        $data[$key]['type']    =  '<span title="'.$row->document_type.'">'.ucfirst($row->document_type).'</span>';

                        // multiple status
                        $DocStatus = explode(',', $row->status);
                        $str = '';
                        $strr = '';
                        foreach ($DocStatus as $d_key => $d_value) 
                        {
                            if($d_value == '0')
                            {
                                $str = ucfirst('unread');
                            }
                            else
                            {
                                $str = '';
                                if($d_value == '1')
                                {
                                    $strr .= ucfirst('read');
                                }
                                if($d_value == '2')
                                {
                                    $strr .= ucfirst(',signed');
                                }
                                if($d_value == '3')
                                {
                                    $strr .= ucfirst(',print,');
                                }
                                if($d_value == '4')
                                {
                                    $strr .= ucfirst(',mail');
                                }
                            }
                            
                           
                            
                        }
                        $data[$key]['document_status']  =  '<span title="'.$row->status.'">'.$str.$strr.'</span>';
                    }              
                    
                } 
            }

            ## SEARCH HTML  document_name
            // $searchHTML['id']           =  '';  
            // $searchHTML['name']           =  '';  
            // $searchHTML['document_url']           =  ''; 
            // $searchHTML['type']  = ''; 
            // $searchHTML['document_status']   =  ''; 
            
         
            /*}*/   
            
            // array_unshift($data, $searchHTML);
            array_unshift($data);

            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData); 
    }

    public function getDocument_renamedon31_may_24(Request $request, $id)
    {
       //  echo $id.'getDocument';

        //dd($request);
        /*--------------------------------------
        |  Variables
        ------------------------------*/
        // dd('testData');
            // skip and limit
            $start  = $request->start;
            $length = $request->length;

            // serach value
            $search = $request->search['value'];

            // patient id
            // order
            $column = $request->order[0]['column'];
            $dir    = $request->order[0]['dir'];

            // filter columns
            $filter = array(
                0 => 'id',
                1 => 'name',
                2 => 'document_url',
                3 => 'type',
                4 => 'document_status',
                5=> 'updated_at',
                6=> 'print_me',
                7=> 'email_at',
                // 4 => 'patients.birth_date',
                // 5 => 'patients.place',
                // 6 => 'status',  document_name
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            // check list

            $modelQuery  =  $this->CheckListHasSelectedQuestionModel
                            ->selectRaw("examinations_check_list.check_list_name as name,
                                check_list_has_selected_questions.pdf_path,
                                check_list_has_selected_questions.pdf_name,
                                check_list_has_selected_questions.type,
                                check_list_has_selected_questions.id,
                                check_list_has_selected_questions.status,
                                'checklist' as document_type,
                                check_list_has_selected_questions.updated_at,
                                check_list_has_selected_questions.id as uqid",

                            )
                            ->leftjoin('examinations_has_multiple_check_list','examinations_has_multiple_check_list.fk_examinations_id','check_list_has_selected_questions.fk_examination_id')
                            ->leftjoin('examinations_check_list','examinations_check_list.id','check_list_has_selected_questions.fk_check_list_id')
                            ->where('check_list_has_selected_questions.fk_patient_id',$id)
                            ->whereNULL('examinations_check_list.deleted_at');
                            // ->whereNotNull('check_list_has_selected_questions.fk_check_list_id')
                            // ->whereNotNull('check_list_has_selected_questions.pdf_path');

            //dd($modelQuery); filteredQuery
            //Document list
            $modelQuery1  =  $this->PatientHasDocumentsModel
                             ->selectRaw("specialist_has_documents.name as name,
                                patient_has_documents.pdf_path,
                                patient_has_documents.pdf_name,
                                patient_has_documents.type,
                                patient_has_documents.id,
                                patient_has_documents.doc_status as status,
                                'document' as document_type,
                                patient_has_documents.updated_at as updated_at,
                                patient_has_documents.id as uqid"

                            )
                             ->leftjoin('specialist_has_documents','specialist_has_documents.id','patient_has_documents.fk_document_id')
                             //->whereNotNull('patient_has_documents.fk_document_id')
                             ->where('patient_has_documents.patient_id',$id)
                             //->where('patient_has_documents.pdf_path','!',''); //Commented this condition on 22sept22
                             ->where('patient_has_documents.pdf_path','!=','');


             $modelQuery2  =  $this->PatientHasDiagnosticFindingsHasDocumentsModel
                             ->selectRaw("patients_has_diagnostic_findings.document_name as name,
                                patient_has_diagnostic_findings_has_documents.file as pdf_path,
                                patient_has_diagnostic_findings_has_documents.original_name,
                                patients_has_diagnostic_findings.date,
                                patients_has_diagnostic_findings.id,
                                patients_has_diagnostic_findings.status,
                                 'finding' as document_type,
                                patients_has_diagnostic_findings.updated_at as updated_at,
                                patients_has_diagnostic_findings.id as uqid"

                            )
                             ->leftjoin('patients_has_diagnostic_findings','patients_has_diagnostic_findings.id','patient_has_diagnostic_findings_has_documents.finding_id')
                             //->whereNotNull('patient_has_documents.fk_document_id')
                             ->where('patients_has_diagnostic_findings.patient_id',$id)
                             ->where('patient_has_diagnostic_findings_has_documents.file','!=','');                 




            //dd($modelQuery1);
            // $getcheckList  =  $this->PatientHasDocumentsModel
            //                   ->where('patient_id',$id)

            // get total count ,
            $countQuery  = clone($modelQuery);
            $countQuery1 = clone($modelQuery1);

            $countQuery2 = clone($modelQuery2);//added on 31-may-24

            //$countQuery->union($countQuery1); //commented above line on 31-may-24
            $countQuery->union($countQuery1)->union($countQuery2); // added line on 31-may-24


            $countQuery  = $countQuery->get();
            $totalData   = $countQuery->count();
            //dd($totalData);
            # FILTER OPTIONS for specific field
            $custom_search = false;

            // Common filter options
            if (!empty($request->search))
            {
                if (!empty($request->search['value']))
                {
                    $search = $request->search['value'];

                    $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orwhere('examinations_check_list.check_list_name', 'LIKE', '%'.$search.'%');
                        $query->orwhere('check_list_has_selected_questions.type', 'LIKE', '%'.$search.'%');
                        $query->orwhere('check_list_has_selected_questions.status', 'LIKE', '%'.$search.'%');
                    });
                }
            }

            // get total filtered
            $countQuery  = clone($modelQuery);
            $countQuery1 = clone($modelQuery1);

            $countQuery2 = clone($modelQuery2); //added on 31-may-24;


           // $countQuery->union($countQuery1);  //commented on 31-may-24
            $countQuery->union($countQuery1)->union($countQuery2); //added on 31-may-24;



            $countQuery  = $countQuery->get();
            $totalFiltered  = $countQuery->count();

            // offset and limit
            // $object = $modelQuery->orderBy($filter[$column], $dir)
            //                      ->skip($start)
            //                      ->take($length)
            //                      ->get();


           // $modelQuery->union($modelQuery1);//commented on 31-may-24
            $modelQuery->union($modelQuery1)->union($modelQuery2); //added on 31-may-24



            $object = $modelQuery->orderBy($filter[$column], $dir)
                    ->skip($start)
                    ->take($length)
                    ->get();
            //dd($object);
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0)
            {
                foreach ($object as $key => $row)
                {
                    if (!empty($row->pdf_path))
                    {
                        $data[$key]['id']             = $row->id;
                        $data[$key]['name']           = $row->name;

                        if(!empty($row->pdf_path) && $row->pdf_path!='0')
                        {
                            //dd($row->pdf_path);
                            $path = self::getFilePath($row->pdf_path);
                            $data[$key]['document_url']   = '<a href="'.$path.'" target="_blank" title="'.$path.'">'.$path.'</a>';
                        }
                        else
                        {
                            $data[$key]['document_url']   = '';
                        }


                        $data[$key]['type']    =  '<span title="'.$row->document_type.'">'.ucfirst($row->document_type).'</span>';
                        $data[$key]['updated_at']  =  '<span title="'.$row->updated_at.'">'.$row->updated_at.'</span>';
                        $data[$key]['print_me']  =  '<span title="'.$row->status.'"><a href="'.$path.'" target="_blank" title="'.$path.'"><i class="fa fa-print" aria-hidden="true" ></i></a></span>';
                        $data[$key]['email_at']  =  '<span title="'.$row->status.'"> <a  lang-type="'.$row->document_type.'" lang-exam="'.$row->id.'" onclick="sendDocumentToPatients(this,'.$id.')"><i class="fa fa-envelope" aria-hidden="true" ></i></span>
                        ';
                        // "'.$row->id.'","","'.$id.'","'.$row->document_type.'")

                        /********commented below code*on 31-may-24*********************************/
                            // multiple status
                         /*   $DocStatus = explode(',', $row->status);
                            $str = '';
                            $strr = '';
                            foreach ($DocStatus as $d_key => $d_value)
                            {
                                if($d_value == '0')
                                {
                                    $str = ucfirst('unread');
                                }
                                else
                                {
                                    $str = '';
                                    if($d_value == '1')
                                    {
                                        $strr .= ucfirst('read');
                                    }
                                    if($d_value == '2')
                                    {
                                        $strr .= ucfirst(',signed');
                                    }
                                    if($d_value == '3')
                                    {
                                        $strr .= ucfirst(',print,');
                                    }
                                    if($d_value == '4')
                                    {
                                        $strr .= ucfirst(',mail');
                                    }
                                }



                            }*/

                        /*********commented below code*on 31-may-24***********************/
                        
                        /********added below code*on 31-may-24*********************************/
                         if($row->document_type=="finding"){
                            $str = '';
                            $strr = '-';
                        }
                        else
                        {
                            // multiple status
                            $DocStatus = explode(',', $row->status);
                            $str = '';
                            $strr = '';
                            foreach ($DocStatus as $d_key => $d_value)
                            {
                                if($d_value == '0')
                                {
                                    $str = ucfirst('unread');
                                }
                                else
                                {
                                    $str = '';
                                    if($d_value == '1')
                                    {
                                        $strr .= ucfirst('read');
                                    }
                                    if($d_value == '2')
                                    {
                                        $strr .= ucfirst(',signed');
                                    }
                                    if($d_value == '3')
                                    {
                                        $strr .= ucfirst(',print,');
                                    }
                                    if($d_value == '4')
                                    {
                                        $strr .= ucfirst(',mail');
                                    }
                                }

                            }//foreach
                        }//else
                        /********added above code*on 31-may-24*********************************/ 

                        $data[$key]['document_status']  =  '<span title="'.$row->status.'">'.$str.$strr.'</span>';

                    }

                }
            }

            ## SEARCH HTML  document_name
            // $searchHTML['id']           =  '';
            // $searchHTML['name']           =  '';
            // $searchHTML['document_url']           =  '';
            // $searchHTML['type']  = '';
            // $searchHTML['document_status']   =  '';


            /*}*/

            // array_unshift($data, $searchHTML);
            array_unshift($data);

            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData);
    }//getDocument_local


    public function getDocument(Request $request, $id)
    {
      //  echo $id.'getDocument';

       //dd($request);
        /*--------------------------------------
        |  Variables
        ------------------------------*/
        // dd('testData');
            // skip and limit
            $start  = $request->start;
            $length = $request->length;

            // serach value
            $search = $request->search['value'];

            // patient id
            // order
            $column = $request->order[0]['column'];
            $dir    = $request->order[0]['dir'];

            $column = 5;
            //dump($column);

            // filter columns
            $filter = array(
                0 => 'id',
                1 => 'name',
                2 => 'document_url',
                3 => 'type',
                4 => 'document_status',
                5=> 'updated_at',
                6=> 'print_me',
                7=> 'email_at',
                // 4 => 'patients.birth_date',
                // 5 => 'patients.place',
                // 6 => 'status',  document_name
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            // check list

            $modelQuery  =  $this->CheckListHasSelectedQuestionModel
                            ->selectRaw("examinations_check_list.check_list_name as name,
                                check_list_has_selected_questions.pdf_path,
                                check_list_has_selected_questions.pdf_name,
                                check_list_has_selected_questions.type,
                                check_list_has_selected_questions.id,
                                check_list_has_selected_questions.status,
                                'checklist' as document_type,
                                check_list_has_selected_questions.updated_at,
                                check_list_has_selected_questions.id as uqid",

                            )
                            ->leftjoin('examinations_has_multiple_check_list','examinations_has_multiple_check_list.fk_examinations_id','check_list_has_selected_questions.fk_examination_id')
                            ->leftjoin('examinations_check_list','examinations_check_list.id','check_list_has_selected_questions.fk_check_list_id')
                            ->where('check_list_has_selected_questions.fk_patient_id',$id)
                            ->whereNULL('examinations_check_list.deleted_at');
                            // ->whereNotNull('check_list_has_selected_questions.fk_check_list_id')
                            // ->whereNotNull('check_list_has_selected_questions.pdf_path');

            //dd($modelQuery); filteredQuery
            //Document list
            $modelQuery1  =  $this->PatientHasDocumentsModel
                             ->selectRaw("specialist_has_documents.name as name,
                                patient_has_documents.pdf_path,
                                patient_has_documents.pdf_name,
                                patient_has_documents.type,
                                patient_has_documents.id,
                                patient_has_documents.doc_status as status,
                                'document' as document_type,
                                patient_has_documents.updated_at as updated_at,
                                patient_has_documents.id as uqid"

                            )
                             ->leftjoin('specialist_has_documents','specialist_has_documents.id','patient_has_documents.fk_document_id')
                             //->whereNotNull('patient_has_documents.fk_document_id')
                             ->where('patient_has_documents.patient_id',$id)
                             //->where('patient_has_documents.pdf_path','!',''); //Commented this condition on 22sept22
                             ->where('patient_has_documents.pdf_path','!=','');

            $modelQuery2  =  $this->PatientHasDiagnosticFindingsHasDocumentsModel
                             ->selectRaw("patients_has_diagnostic_findings.document_name as name,
                                patient_has_diagnostic_findings_has_documents.file as pdf_path,
                                patient_has_diagnostic_findings_has_documents.original_name,
                                patients_has_diagnostic_findings.date,
                                patients_has_diagnostic_findings.id,
                                patients_has_diagnostic_findings.status,
                                'finding' as document_type,
                                patients_has_diagnostic_findings.updated_at as updated_at,
                                patients_has_diagnostic_findings.id as uqid"

                            )
                             ->leftjoin('patients_has_diagnostic_findings','patients_has_diagnostic_findings.id','patient_has_diagnostic_findings_has_documents.finding_id')
                             //->whereNotNull('patient_has_documents.fk_document_id')
                             ->where('patients_has_diagnostic_findings.patient_id',$id)
                             ->where('patient_has_diagnostic_findings_has_documents.file','!=','');                                  

            //dump($modelQuery2->get()->toArray());
            // $getcheckList  =  $this->PatientHasDocumentsModel
            //                   ->where('patient_id',$id)

            // get total count ,
            $countQuery  = clone($modelQuery);
            $countQuery1 = clone($modelQuery1);
            $countQuery2 = clone($modelQuery2);//added on 31-may-24
            // $countQuery->union($countQuery1); //commented on 31-may-24
            $countQuery->union($countQuery1)->union($countQuery2); //added on 31-may-24


            $countQuery  = $countQuery->get();
            $totalData   = $countQuery->count();
            //dd($totalData);
            # FILTER OPTIONS for specific field
            $custom_search = false;

            // Common filter options
            if (!empty($request->search))
            {
                if (!empty($request->search['value']))
                {
                    $search = $request->search['value'];

                    $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orwhere('examinations_check_list.check_list_name', 'LIKE', '%'.$search.'%');
                        $query->orwhere('check_list_has_selected_questions.type', 'LIKE', '%'.$search.'%');
                        $query->orwhere('check_list_has_selected_questions.status', 'LIKE', '%'.$search.'%');
                    });
  
                }//if request search
            }

            // get total filtered
            $countQuery  = clone($modelQuery);
            $countQuery1 = clone($modelQuery1);

            $countQuery2 = clone($modelQuery2); //added on 31-may-24;
            // $countQuery->union($countQuery1);  //commented on 31-may-24
            $countQuery->union($countQuery1)->union($countQuery2); //added on 31-may-24;


            $countQuery  = $countQuery->get();
            $totalFiltered  = $countQuery->count();

            // offset and limit
            // $object = $modelQuery->orderBy($filter[$column], $dir)
            //                      ->skip($start)
            //                      ->take($length)
            //                      ->get();



            // $modelQuery->union($modelQuery1);//commented on 31-may-24
             $modelQuery->union($modelQuery1)->union($modelQuery2); //added on 31-may-24

        


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
                    if (!empty($row->pdf_path))
                    {
                        $data[$key]['id']             = $row->id;
                        $data[$key]['name']           = $row->name;

                        if(!empty($row->pdf_path) && $row->pdf_path!='0')
                        {
                            //dd($row->pdf_path);
                            $path = self::getFilePath($row->pdf_path);
                            $data[$key]['document_url']   = '<a href="'.$path.'" target="_blank" title="'.$path.'">'.$path.'</a>';
                        }
                        else
                        {
                            $data[$key]['document_url']   = '';
                        }


                        $data[$key]['type']    =  '<span title="'.$row->document_type.'">'.ucfirst($row->document_type).'</span>';
                        $data[$key]['updated_at']  =  '<span title="'.$row->updated_at.'">'.$row->updated_at.'</span>';
                        $data[$key]['print_me']  =  '<span title="'.$row->status.'"><a href="'.$path.'" target="_blank" title="'.$path.'"><i class="fa fa-print" aria-hidden="true" ></i></a></span>';
                        $data[$key]['email_at']  =  '<span title="'.$row->status.'"> <a  lang-type="'.$row->document_type.'" lang-exam="'.$row->id.'" onclick="sendDocumentToPatients(this,'.$id.')"><i class="fa fa-envelope" aria-hidden="true" ></i></span>
                        ';
                        // "'.$row->id.'","","'.$id.'","'.$row->document_type.'")

                        /********commented below code*on 31-may-24*********************************/
                            // multiple status
                           /* $DocStatus = explode(',', $row->status);
                            $str = '';
                            $strr = '';
                            foreach ($DocStatus as $d_key => $d_value)
                            {
                                if($d_value == '0')
                                {
                                    $str = ucfirst('unread');
                                }
                                else
                                {
                                    $str = '';
                                    if($d_value == '1')
                                    {
                                        $strr .= ucfirst('read');
                                    }
                                    if($d_value == '2')
                                    {
                                        $strr .= ucfirst(',signed');
                                    }
                                    if($d_value == '3')
                                    {
                                        $strr .= ucfirst(',print,');
                                    }
                                    if($d_value == '4')
                                    {
                                        $strr .= ucfirst(',mail');
                                    }
                                }

                            }*/

                        /*********commented below code*on 31-may-24***********************/


                        /********added below code*on 31-may-24*********************************/
                        if($row->document_type=="finding"){
                            $str = '';
                            $strr = '-';
                        }else{
                             // multiple status
                            $DocStatus = explode(',', $row->status);
                            $str = '';
                            $strr = '';
                            foreach ($DocStatus as $d_key => $d_value)
                            {
                                if($d_value == '0')
                                {
                                    $str = ucfirst('unread');
                                }
                                else
                                {
                                    $str = '';
                                    if($d_value == '1')
                                    {
                                        $strr .= ucfirst('read');
                                    }
                                    if($d_value == '2')
                                    {
                                        $strr .= ucfirst(',signed');
                                    }
                                    if($d_value == '3')
                                    {
                                        $strr .= ucfirst(',print,');
                                    }
                                    if($d_value == '4')
                                    {
                                        $strr .= ucfirst(',mail');
                                    }
                                }

                            }
                        }//else

                         /********added above code*on 31-may-24*********************************/

                       
                        $data[$key]['document_status']  =  '<span title="'.$row->status.'">'.$str.$strr.'</span>';

                    }

                }
            }

            ## SEARCH HTML  document_name
            // $searchHTML['id']           =  '';
            // $searchHTML['name']           =  '';
            // $searchHTML['document_url']           =  '';
            // $searchHTML['type']  = '';
            // $searchHTML['document_status']   =  '';


            /*}*/

            // array_unshift($data, $searchHTML);
            array_unshift($data);

            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData);
    }

    // reminder section
    public function patient_reminder() 
    { 

       
        // Default site patients
        $this->ModuleTitle              =  __('admin.TITLE_PATIENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');

        if(empty(Config('website_id')))
        {
            $this->ViewData['show_ordination']    = true;
        }
        else
        { 
            $this->ViewData['show_ordination']    = false;
        }
        $this->ViewData['specialist_details']= self::__GetSecialits();

       
        // view file with data
        return view($this->ModuleView.'index-reminder', $this->ViewData);
    } 

    public function getReminderRecords_renamedon_25_june_25(Request $request)
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
                2 => 'patients.email',
                3 => 'patients.mobile_no',
                4 => 'patients.reminder',
            );
        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/
            // start model query
            //$modelQuery =  $this->BaseModel;
            //                ->with('patient_has_service_reminder');
            // DB::enableQueryLog();
            $modelQuery =  $this->PatientsHasServiceReminderModel
                            ->select('patients.id','patients.first_name','patients.family_name','patients.email','patients.mobile_no','patient_has_service_reminder.patient_id')
                            ->leftJoin('patients', function($join) {
                                $join->on('patients.id', '=', 'patient_has_service_reminder.patient_id');
                                $join->whereNull('patients.deleted_at');
                            })->groupBy('patient_id');
            // get total count
            $countQuery = clone($modelQuery);
            $totalData  = $countQuery->get()->count();
            # FILTER OPTIONS for specific field
            $custom_search = false;
            if (!empty($request->custom))
            {
                // dd(empty($request->custom['role']));ordination
                if (!empty($request->custom['fullname']))
                {
                    $name = explode(" ", $request->custom['fullname']);
                    if(!empty($name[1]))
                    {
                        $key[0]         = $name[0];
                        $key[1]         = $name[1];
                        $custom_search  = true;
                        $modelQuery     = $modelQuery
                        // ->where('patients.first_name','LIKE','%'.$key[0].'%')
                        // ->orWhere('patients.family_name','LIKE','%'.$key[1].'%');
                        ->whereRaw("MATCH(patients.first_name) AGAINST('".$key[0]."')")
                        ->whereRaw("MATCH(patients.family_name) AGAINST('".$key[1]."')");
                    }
                    else {
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
                if (!empty($request->custom['mobile_no']))
                {
                    // dd($request->custom['mobile_no']);
                    $custom_search  = true;
                    $key            = $request->custom['mobile_no'];
                    $modelQuery     = $modelQuery
                    ->where('patients.mobile_no','LIKE','%'.$key.'%');
                    //->whereRaw("MATCH(patients.mobile_no) AGAINST('".$key."')");
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
                        $query->orwhere('patients.mobile_no', 'LIKE', '%'.$search.'%'); 
                        $query->orwhere('patients.old_id', '=', $search); 
                        // $query->orwhere('patients.birth_date', 'LIKE', '%'.$search.'%'); 
                        // $query->orwhere('patients.birth_date', '=', $search); 
                        // $query->orwhere('patients.place', 'LIKE', '%'.$search.'%'); 
                        // if(strtolower($search)=="active"){
                        //     $query->orwhere('patients.status', '=', 1);
                        // }
                        // else{
                        //     $query->orwhere('patients.status', '=', 0);
                        // }  
                    });
                }
            }
            // get total filtered
            $filteredQuery  = clone($modelQuery);            
            $totalFiltered  = $filteredQuery->get()->count();
            // offset and limit
            //dump($filter[$column], $dir);
            //dump($start, $length);
            //->whereNull('patients.deleted_at')->
            $object = $modelQuery->orderBy('patients.id', $dir)
                                 ->skip($start)
                                 ->take($length)
                                 ->get();
             // print_r(DB::getQueryLog());exit;
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row) 
                { 
                    $data[$key]['id']  = $row->id;
                    $fname = ucfirst($row->first_name);
                    // $lname = ucfirst($row->last_name);
                    $familyName = ucfirst($row->family_name);
                    $data[$key]['fullname']  = '<span title="'.'concatenateNom'.'">'.$fname.' '.$familyName.'</span>';
                    $data[$key]['email']     = '<span title="'.$row->email.'">'.$row->email.'</span>';
                    $intCountryCode = $row->country_code;
                    $data[$key]['mobile_no']  =  "<span title='".$intCountryCode.$row->mobile_no."'>".$intCountryCode.$row->mobile_no."</span>";
                    $data[$key]['reminder'] =  '<button type="button" class="btn fc-button-primary btn-reminder-model" patientid="'.$row->id.'" title="'.__('admin.TITLE_REMINDER').'"><span class="nav-icon fas fa-bell"></span>
                            </button>';
                    // Reminder
                    // $getRemider = $this->PatientsHasServiceReminderModel
                    //               ->select('patient_has_service_reminder.*','examinations.name')
                    //               ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                    //               ->where('patient_id',$row->id)
                    //               ->where('patient_has_service_reminder.status','activate')//Added by Shyam 14-03-22
                    //               ->where('patient_has_service_reminder.reminder_status','Set')//Added by Shyam 14-03-22
                    //               ->whereNull('patient_has_service_reminder.deleted_at')
                    //               ->orderBy('patient_has_service_reminder.id','asc')
                    //               ->groupBy('patient_has_service_reminder.service_id')
                    //               ->get();
                    // if(!empty($getRemider) && sizeof($getRemider)>0)
                    // {   
                    //     $trTable =  $future_date = '';
                    //     foreach ($getRemider as $key_rem => $value_rem) 
                    //     {
                    //         $getFutureDetails = $this->PatientHasReminder
                    //                             ->where('service_reminder_id',$value_rem->id)
                    //                             ->first();
                    //         if(!empty($getFutureDetails) && !empty($getFutureDetails->next_reminder_date))
                    //         {
                    //             $future_date = date('d-m-Y H:i:s',strtotime($getFutureDetails->next_reminder_date));
                    //         }                    
                    //         if(!empty($value_rem['appointment_id']))
                    //         {
                    //             $getdataapp = $this->AppointmentModel->find($value_rem['appointment_id']);
                    //             $appDate = Date('d-m-Y H:i:s',strtotime($getdataapp->start_date));
                    //         }
                    //         else {
                    //             $appDate = '';
                    //         }
                    //         $read_status = 'ungelesen';
                    //         if($value_rem->read_status == '1')
                    //         {
                    //             $read_status = 'gelesen';
                    //         }
                    //         $reminderDate = Date('d-m-Y H:i:s',strtotime($value_rem->reminder_date));
                    //         $currentDate = Date('d-m-Y H:i:s');
                    //         $status ='aktiv';
                    //         if(strtotime($reminderDate) < strtotime($currentDate))
                    //         {
                    //             $status =  'inaktiv';
                    //         }
                    //         if($value_rem['type'] == 'age')
                    //         {
                    //             $services_type = 'Profil';
                    //         }
                    //         else {
                    //             $services_type = $value_rem['type'];
                    //         }
                    //         $trTable .= '<tr>
                    //             <td>'.$value_rem['name'].'</td>
                    //             <td>'.Date('d-m-Y H:i:s',strtotime($value_rem->reminder_date)).'</td>
                    //             <td>'.$services_type.'</td>
                    //             <td>'.$appDate.'</td>
                    //             <td>'.ucfirst($value_rem['media']).'</td>
                    //             <td>'.$status.'</td>
                    //             <td>'.$read_status.'</td>
                    //         </tr>';
                    //     };
                    //     $data[$key]['reminder'] =  '<button type="button" class="btn fc-button-primary" data-toggle="modal" data-target="#getReminder_'.$row->id.'" title="'.__('admin.TITLE_REMINDER').'"><span class="nav-icon fas fa-bell"></span>
                    //         </button>
                    //         <div class="modal fade" id="getReminder_'.$row->id.'" style="position:fixed;">
                    //               <div class="modal-dialog modal-dialog-scrollable">
                    //                   <div class="modal-content" style="max-height: calc(100vh - -20.5rem)!important;width: 150%!important;margin: 20px;">
                    //                       <div class="modal-header">
                    //                         <h3 class="card-title">'.__("admin.TITLE_REMINDER").'</h3>
                    //                         <button id="btnClose" type="button" class="close" data-dismiss="modal" aria-label="Close">
                    //                         <span aria-hidden="true">&times;</span></button>
                    //                       </div>               
                    //                       <div class="modal-body">
                    //                         <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >  
                    //                             <thead class="">    
                    //                                 <tr>
                    //                                     <th class="w-140-px">'.__('admin.TITLE_SERVICE').' </th>
                    //                                     <th class="w-200-px">'.__('admin.TITLE_REMINDER_DATE').'</th>
                    //                                     <th class="w-200-px">'.__('admin.TITLE_REMINDER_TYPE').'</th> 
                    //                                     <th class="w-200-px">'.__('admin.TITLE_REMINDER_APPOINTMENT_DATE').'</th>
                    //                                     <th class="w-200-px">'.__('admin.TITLE_REMINDER_MEDIA').'</th>
                    //                                    <th class="w-200-px">Status</th>
                    //                                    <th class="w-200-px">'.__('admin.TITLE_REMINDER_READ_STATUS').'</th>
                    //                                 </tr>
                    //                             </thead>
                    //                             <tbody>'.$trTable.'</tbody></table>
                    //                       </div>
                    //                   </div><!-- /.modal-content -->
                    //               </div><!-- /.modal-dialog -->
                    //             </div>';
                    // }
                    // else {
                    //     $data[$key]['reminder'] = '';
                    // }        

                } 
            }

            ## SEARCH HTML birth
            
            
            $searchHTML['id']           =  '';    
            $searchHTML['fullname']     =  '<input type="text" class="form-control" id="fullname" value="'.($request->custom['fullname'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['email']        =  '<input type="text" class="form-control" id="email" value="'.($request->custom['email'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['mobile_no']    =  '<input type="text" class="form-control" id="mobile_no" value="'.($request->custom['mobile_no'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';  
            $searchHTML['reminder']    = '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';
            
            array_unshift($data, $searchHTML);
           
            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData); 
    }//

    public function getReminderRecords(Request $request)
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
                2 => 'patients.email',
                3 => 'patients.mobile_no',
                4 => 'patients.reminder',
            );
        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/
            // start model query
            //$modelQuery =  $this->BaseModel;
            //                ->with('patient_has_service_reminder');
            // DB::enableQueryLog();
            $modelQuery =  $this->BaseModel
                            ->select('patients.id','patients.first_name','patients.family_name','patients.email','patients.mobile_no')
                            ->whereNull('patients.deleted_at');
            // get total count
            $countQuery = clone($modelQuery);
            // $totalData  = $countQuery->get()->count();
            $totalData  = $countQuery->count();

            # FILTER OPTIONS for specific field
            $custom_search = false;
            if (!empty($request->custom))
            {
                // if (!empty($request->custom['fullname']))
                // {
                //     $name = explode(" ", $request->custom['fullname']);
                //     if(!empty($name[1]))
                //     {
                //         $key[0]         = $name[0];
                //         $key[1]         = $name[1];
                //         $custom_search  = true;
                //         $modelQuery     = $modelQuery
                //         // ->where('patients.first_name','LIKE','%'.$key[0].'%')
                //         // ->orWhere('patients.family_name','LIKE','%'.$key[1].'%');
                //         ->whereRaw("MATCH(patients.first_name) AGAINST('".$key[0]."')")
                //         ->whereRaw("MATCH(patients.family_name) AGAINST('".$key[1]."')");
                //     }
                //     else {
                //         $key[0]         = $name[0];
                //         $custom_search  = true;
                //         $modelQuery     = $modelQuery
                //         // ->where('patients.first_name','LIKE','%'.$key[0].'%')
                //         // ->orWhere('patients.family_name','LIKE','%'.$key[0].'%');
                //         ->whereRaw("MATCH(patients.first_name) AGAINST('".$key[0]."')")
                //         ->orwhereRaw("MATCH(patients.family_name) AGAINST('".$key[0]."')");
                //     }
                // }
                // if (!empty($request->custom['fullname']))
                // {
                //     $patientId = preg_replace('/\s+/', ' ', $request->custom['fullname']);
                //     $name = explode(" ", $request->custom['fullname']);
                //     $name = preg_replace('/\s+/', ' ', $name);
                //     if (preg_match('/^[a-zA-Z0-9\s]+$/', $patientId)) {
                //         if(!empty($name[1])){
                //         $key[0]         = $name[0];
                //         $key[1]         = $name[1];
                //         $custom_search  = true;
                //         $modelQuery     = $modelQuery
                //         ->whereRaw("MATCH(patients.first_name) AGAINST('".$key[0]."')")
                //         ->whereRaw("MATCH(patients.family_name) AGAINST('".$key[1]."')");
                //         } else{
                //             $key[0]         = $name[0];
                //             $custom_search  = true;
                //             $modelQuery     = $modelQuery
                //             ->whereRaw("MATCH(patients.first_name) AGAINST('".$key[0]."')")
                //             ->orwhereRaw("MATCH(patients.family_name) AGAINST('".$key[0]."')");
                //         }
                //     }                    
                // }
                 if (!empty($request->custom['fullname'])) {
                    $raw = trim($request->custom['fullname']);
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
                if (!empty($request->custom['email']))
                {
                    $custom_search  = true;
                    $key            = $request->custom['email'];
                    $modelQuery     = $modelQuery
                    ->where('patients.email','LIKE','%'.$key.'%');
                }
                if (!empty($request->custom['mobile_no']))
                {
                    // dd($request->custom['mobile_no']);
                    $custom_search  = true;
                    $key            = $request->custom['mobile_no'];
                    $modelQuery     = $modelQuery
                    ->where('patients.mobile_no','LIKE','%'.$key.'%');
                    //->whereRaw("MATCH(patients.mobile_no) AGAINST('".$key."')");
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
                        $query->orwhere('patients.mobile_no', 'LIKE', '%'.$search.'%'); 
                        $query->orwhere('patients.old_id', '=', $search); 
                       
                    });
                }
            }
            // get total filtered
            $filteredQuery  = clone($modelQuery);            
            $totalFiltered  = $filteredQuery->get()->count();
            // offset and limit
            //dump($filter[$column], $dir);
            //dump($start, $length);
            //->whereNull('patients.deleted_at')->
            $object = $modelQuery->orderBy('patients.id', $dir)
                                 ->skip($start)
                                 ->take($length)
                                 ->get();
             // print_r(DB::getQueryLog());exit;
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row) 
                { 
                    $data[$key]['id']  = $row->id;
                    $fname = ucfirst($row->first_name);
                    // $lname = ucfirst($row->last_name);
                    $familyName = ucfirst($row->family_name);
                    $data[$key]['fullname']  = '<span title="'.'concatenateNom'.'">'.$fname.' '.$familyName.'</span>';
                    $data[$key]['email']     = '<span title="'.$row->email.'">'.$row->email.'</span>';
                    $intCountryCode = $row->country_code;
                    $data[$key]['mobile_no']  =  "<span title='".$intCountryCode.$row->mobile_no."'>".$intCountryCode.$row->mobile_no."</span>";
                    $data[$key]['reminder'] =  '<button type="button" class="btn fc-button-primary btn-reminder-model" patientid="'.$row->id.'" title="'.__('admin.TITLE_REMINDER').'"><span class="nav-icon fas fa-bell"></span>
                            </button>';
                } 
            }

            ## SEARCH HTML birth
            
            
            $searchHTML['id']           =  '';    
            $searchHTML['fullname']     =  '<input type="text" class="form-control" id="fullname" value="'.($request->custom['fullname'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['email']        =  '<input type="text" class="form-control" id="email" value="'.($request->custom['email'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['mobile_no']    =  '<input type="text" class="form-control" id="mobile_no" value="'.($request->custom['mobile_no'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';  
            $searchHTML['reminder']    = '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';
            
            array_unshift($data, $searchHTML);
           
            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData); 
    }  



    //Added on 31jan2022
    // SEND DOCUMENT TO Patients
    public function getDocPatientsDetails(Request $request)
    {
        $collection = [];
        //dump($request->id);
        $patient_details = $this->PatientsModel
                            ->where('id',$request->id)
                            ->first();

        if(!empty($patient_details))
        {
            $collection['patient_name'] = $patient_details['first_name'].' '.$patient_details['family_name'];
            $collection['p_id']         = $patient_details['id'];
            $collection['email']        = $patient_details['email'];
        return $collection;
            }
        //dump($collection);
    }

    public function sendDocumentsToPatients(Request $request)
    {
       //dump($request->all());
        $data['msg']  = __('admin.ERR_SOMETHING_WRONG');
        $data['flag'] = 'false';
        $attachments = $fileExists = '';
        $collection = $data = $result = [];
        try
        {
            // PATIENT DETAILS
            $patient_details = $this->PatientsModel
                               ->where('id',$request->hd_patient_id)
                               ->first();
            if(!empty($patient_details))
            {
                $getDatabaseName = DB::connection('system')
                                    ->table("tenants")
                                    ->where('ordination_id',Config('ordination_id'))
                                    ->first(['uuid']);
                $collection['type'] = $request->type;
                $collection['patients_name'] = $patient_details['first_name'].' '.$patient_details['family_name'];
                if(!empty($request->to))
                {
                    $email = $request->to;
                }
                else {
                    $email = $patient_details['email'];
                }
                if($request->type == 'doc')
                {
                    if($request->doc_type == 'services')
                    {
                        $result = $this->PatientHasDocumentsModel
                              ->where('patient_id',$request->hd_patient_id)
                              ->where('fk_document_id',$request->hd_doc_id)
                              ->where('fk_examinations_id',$request->exam_id)
                              // ->where('type','service')
                              ->first();
                    }
                    else {
                        $result = $this->PatientHasDocumentsModel
                              ->where('patient_id',$request->hd_patient_id)
                              ->where('id',$request->hd_doc_id)
                              // ->where('type','general')
                              ->first();
                    }
                    if(!empty($result))
                    {
                        if(!empty($result->pdf_path))
                        {
                            $attachments = url('storage/tenancy/tenants/'.$getDatabaseName->uuid.$result->pdf_path);
                            $fileExists = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.$result->pdf_path;
                        }
                        else {
                            $pdf_path = self::generateDocumentPDF($request->hd_doc_id,$request->hd_patient_id);
                            $attachments = url('storage/tenancy/tenants/'.$getDatabaseName->uuid.$pdf_path);
                            $fileExists = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.$pdf_path;
                        }
                    }
                }
                else if($request->type == 'checklist')
                {
                    $result = $this->CheckListHasSelectedQuestionModel
                                ->where('fk_patient_id',$request->hd_patient_id)
                                ->where('id',$request->hd_doc_id)
                                // ->where('type','general')
                                ->first();
                    if(!empty($result))
                    {
                        if(!empty($result->pdf_path))
                        {
                            $attachments = url('storage/tenancy/tenants/'.$getDatabaseName->uuid.$result->pdf_path);
                            $fileExists = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.$result->pdf_path;
                        }
                        else {
                            $pdf_path = self::generateChecklistPDF($request->hd_doc_id,$request->hd_patient_id);
                            $attachments = url('storage/tenancy/tenants/'.$getDatabaseName->uuid.$pdf_path);
                            $fileExists = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.$pdf_path;
                        }
                    }
                }
                else if($request->type == 'finding') //else if added on 31-may-24
                {
                    $result = $this->PatientHasDiagnosticFindingsHasDocumentsModel
                                ->where('finding_id',$request->hd_doc_id)
                                ->first();
                    if(!empty($result))
                    {
                        if(!empty($result->file))
                        {
                            $attachments = url('storage/tenancy/tenants/'.$getDatabaseName->uuid.$result->file);
                            $fileExists = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.$result->file;
                        }                      
                    }
                }//else if added on 31-may-24




                if(!empty($email))
                {
                    if (file_exists($fileExists)) {
                        $collection['attachments'] = $attachments;
                    }
                    else {
                        $collection['attachments'] = '';
                    }
                    $email_result = Mail::to($email)->send(new SendDocumentForPatientmail($collection));
                    if(empty($email_result))
                    {
                        if($request->type == 'checklist')
                        {
                            $chk_rec = $this->CheckListHasSelectedQuestionModel->find($result->id);
                            $myStatus = explode(',', $chk_rec->status);
                            if (!in_array('4', $myStatus))
                            {
                                $status = $chk_rec->status.',4';
                                $re_status  = str_replace("0,", "", $status);
                                $chk_rec->status = ltrim($re_status,',');
                                $chk_rec->save();
                            }
                        }
                        else {
                            $doc_rec = $this->PatientHasDocumentsModel->find($result->id);
                            $myStatus = explode(',', $doc_rec->doc_status);
                            if (!in_array('4', $myStatus))
                            {
                                $doc_status = $doc_rec->doc_status.',4';
                                $re_status  = str_replace("0,", "", $doc_status);
                                $chk_rec->doc_status = ltrim($doc_status,',');
                                $doc_rec->save();
                            }
                        }
                        $data['msg']  = __('admin.TITLE_DOCUMENT_SEND');
                        $data['flag'] = 'true';
                        $data['p_id'] = $request->hd_patient_id;
                    }
                }
                else {
                    $data['msg']  = __('admin.ERR_SOMETHING_WRONG');
                    $data['flag'] = 'false';
                    $data['p_id'] = '';
                }
            }
        }
        catch(\Exception $e)
        {
            $message = __('admin.ERR_SOMETHING_WRONG');
            $errors[] = [
                    "error" => $e->getMessage(),
                ];
        }
        Session::put('success', __('admin.TITLE_DOCUMENT_SEND'));
        Session::put('redirect_arr',$data);
        return redirect('admin/document/'.base64_encode(base64_encode($request->hd_patient_id)));
    }

    public function testEmail()
    {
        // $targetFolder = '/opt/app-data/wwwroot/storage/app';
        // $linkFolder = '/opt/app-shared/php/data/storage/app/public';
        // symlink($targetFolder,$linkFolder);
        // echo 'Symlink process successfully completed';
        // die;

        $user = [
            'type' => 'Document',
            'patients_name' => 'Testing patients',
            'attachments' => ''
        ];
        \Mail::to('eluminous.se68@gmail.com')->send(new SendDocumentForPatientmail($user));
        dd("success");
    }

    //Function added to get the reminder data from popup on 21-sept-22 added by divya
     public function getReminderData(Request $request)
    {
        $patientid = $request->patientid;
        if($patientid)
        {
            // $getRemider = $this->PatientsHasServiceReminderModel
            //                       ->select('patient_has_service_reminder.*','examinations.name')
            //                       ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
            //                       ->where('patient_id',$patientid)
            //                       ->where('patient_has_service_reminder.status','activate')//Added by Shyam 14-03-22
            //                       ->where('patient_has_service_reminder.reminder_status','Set')//Added by Shyam 14-03-22
            //                       ->whereNull('patient_has_service_reminder.deleted_at')
            //                       ->orderBy('patient_has_service_reminder.id','asc')
            //                       ->groupBy('patient_has_service_reminder.service_id')
            //                       ->get();
            //DB::enableQueryLog();


             //Commented below query on 20-oct-23 for examination deleted at null condition
            /*$getRemider = DB::select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                                    JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
                                    JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate') or (appointment_id!=0 and reminder_status='Set' and status='deactivate')))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));*/


             //commented below live query on 9-feb-24 for testing                       
             /*$getRemider = DB::select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                                    JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
                                    AND examinations.deleted_at IS NULL
                                    JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate') or (appointment_id!=0 and reminder_status='Set' and status='deactivate')))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));*/

             //added below query on 9-feb-24                       
             /*$getRemider = DB::select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                                    JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
                                    AND examinations.deleted_at IS NULL
                                    JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC")); */

            //added and type not general condition on 16-feb-24
                                    

           //commented on 4-july-24                         
           /* $getRemider = DB::select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                                    JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
                                    AND examinations.deleted_at IS NULL
                                    JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));  */


            //added on 4-july-24   
            //Roshani hidden the below query for laravel upgrade where DB::raw not supported

            // $getRemider = DB::select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
            //                         JOIN examinations on examinations.id=t1.service_id 
            //                         AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
            //                         JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
            //                             and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
            //                             ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
            //                             and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
            //                         WHERE t1.reminder_status IN('Set','ignore') AND
            //                         t1.patient_id=$patientid and t1.deleted_at is NULL
            //                         GROUP by t1.service_id
            //                         ORDER by t1.id DESC"));

            //Roshani added the below query for laravel upgrade where DB::raw not supported
             

            //commented on 17-nov-25 for #352 general reminder
            /*$getRemider = DB::select(("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                                    JOIN examinations on examinations.id=t1.service_id 
                                    AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                                    JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));*/


            //Did changes on 17-nov-25 for #352 general reminder      
           //commented on 24-march-26                  
           /* $getRemider = DB::select(("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                                    JOIN examinations on examinations.id=t1.service_id 
                                    AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                                    JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' AND type NOT IN ('general','control'))))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));  */

             //added on 24-march-26 for age base deactivate entries for #351  
             //commented on 21-apr-26                        
            /* $getRemider = DB::select(("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                                    JOIN examinations on examinations.id=t1.service_id 
                                    AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                                    JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' AND type NOT IN ('general','control','age'))))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));  */


              //changed on 21-apr-26 for 2imfs control reminder not shown on popup as service not have reminder                        
             $getRemider = DB::select(("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
                                    JOIN examinations on examinations.id=t1.service_id 
                                    AND ((t1.type = 'general' AND examinations.deleted_at IS NULL) OR (t1.type = 'control' AND examinations.deleted_at IS NULL) OR (show_as_reminder = '1' AND examinations.deleted_at IS NULL))
                                    JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
                                        and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
                                        ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' AND type NOT IN ('general','control','age'))))
                                        and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
                                    WHERE t1.reminder_status IN('Set','ignore') AND
                                    t1.patient_id=$patientid and t1.deleted_at is NULL
                                    GROUP by t1.service_id
                                    ORDER by t1.id DESC"));                                                                            
                                                                       
                           

            $getRemider = collect($getRemider)->map(function($x){ 
                return (array) $x; 
            })->toArray();    


           
            if(!empty($getRemider) && sizeof($getRemider)>0)
            {   
                $trTable =  $future_date = '';
                foreach ($getRemider as $key_rem => $value_rem) 
                {
                    $getFutureDetails = $this->PatientHasReminder
                                                ->where('service_reminder_id',$value_rem['id'])
                                                ->first();
                    if(!empty($getFutureDetails) && !empty($getFutureDetails->next_reminder_date))
                    {
                        $future_date = date('d-m-Y H:i:s',strtotime($getFutureDetails->next_reminder_date));
                    }                    
                    if(!empty($value_rem['appointment_id']))
                    {
                        $getdataapp = $this->AppointmentModel->find($value_rem['appointment_id']);
                        $appDate = Date('d-m-Y H:i:s',strtotime($getdataapp->start_date));
                    }
                    else {
                        $appDate = '';
                    }
                    $read_status = 'ungelesen';
                    if($value_rem['read_status'] == '1')
                    {
                        $read_status = 'gelesen';
                    }
                    $reminderDate = Date('d-m-Y H:i:s',strtotime($value_rem['reminder_date']));
                    $currentDate = Date('d-m-Y H:i:s');
                    $status ='inaktiv';
                    // if((strtotime($reminderDate) < strtotime($currentDate)) || (strtotime($reminderDate)==strtotime($currentDate)))

                    //commented on 27-march-25
                    // if((strtotime($reminderDate) < strtotime($currentDate)) || $value_rem['appointment_id']==0){
                    //      $status =  'aktiv';
                    // }

                    
                    
                    //added on 27-march-25
                    if((strtotime($reminderDate) < strtotime($currentDate)) || ((strtotime($reminderDate) > strtotime($currentDate)) &&  $value_rem['appointment_id']!=0 && $value_rem['cycle_no']!=1)  || $value_rem['appointment_id']==0)
                    {
                        $status =  'aktiv';
                    }
               

                    // added by vijay 23/7/24  #165 a
                    if($value_rem['status'] == 'deactivate'){
                        $status = 'inaktiv';
                    }
                    // end
                    if($value_rem['reminder_status']=='ignore' && $value_rem['status']!='deactivate')  
                        $status=__('admin.IGNORE_STATUS');
                    if($value_rem['reminder_status']=='ignore' && $value_rem['status']=='deactivate')  
                        $status='inaktiv';
                    if(($value_rem['appointment_id']==0 && $value_rem['status']=='deactivate'))$status ='inaktiv';
                    if($value_rem['type'] == 'age')
                    {
                        $services_type = 'Profil-LE';
                    }
                    else {
                        $services_type = ($value_rem['type']=='control') ? 'Kontroll-LE': 'Folge-LE' ;
                    }


                    //added below code on 10-jan-24 for control reminder status ignore
                    // if($patientid==44999) //konle2 puremed
                    // if($patientid==47350) //testcontrol
                    // if($patientid==47376) //Testcontrolnew
                   // {

                        if($value_rem['type']=='control')
                        {
                            //dump($status);

                             $is_doctor_set_reminder = db::table('patient_has_service_control_reminder_setting')->where(
                            ['patient_id' => $patientid,
                            'appointment_id' =>$value_rem['appointment_id'],
                            'service_id' => $value_rem['service_id'],
                            'status' => '1',
                            ]
                            )->first();

                            if($is_doctor_set_reminder)
                            {

                                //commented on 24-jan-25
                                // $checkup_period_controls =  $is_doctor_set_reminder->control_interval;
                                // $checkup_period_frequency_type = $is_doctor_set_reminder->control_frequency;

                                //start added on 24-jan-25

                                /*$is_set_reminder = db::table('preferred_channels_for_reminders_setting')->where(
                                 ['service_id' => $value_rem['service_id']])->first();*/

                                 $is_set_reminder = DB::table('preferred_channels_for_reminders_setting')
                                        ->join('examinations', 'examinations.id', '=', 'preferred_channels_for_reminders_setting.service_id')
                                        ->where('preferred_channels_for_reminders_setting.service_id', $value_rem['service_id'])
                                        ->where('preferred_channels_for_reminders_setting.activated_reminder', 'checkup')
                                        ->where('examinations.show_as_reminder', '1')  // added: only use service-level setting if show_as_reminder is enabled
                                        ->whereNull('examinations.deleted_at')
                                        ->first();

                                
                                if($is_set_reminder)
                                {

                                 $checkup_period_controls = $is_set_reminder->checkup_end_cycle;
                                 $checkup_period_frequency_type = $is_set_reminder->checkup_end_cycle_frequency_type;
                                }
                                //end added on 24-jan-25
                                else
                                {

                                    $is_set_reminder = DB::table('preferred_channels_for_reminders_setting')->where(
                                        [
                                        'type' => 'global',
                                        ]
                                        )->first();

                                    $checkup_period_controls =  $is_set_reminder->checkup_end_cycle;
                                    $checkup_period_frequency_type = $is_set_reminder->checkup_end_cycle_frequency_type;

                                }



                                $endCycleDyas = $this->_getDate(($reminderDate),$checkup_period_controls,$checkup_period_frequency_type);  
                                $endcycle_date = $this->_filterWeekendAndHoiliday(($reminderDate),$endCycleDyas,0,'plus');
                               
                                // dump(' inendCycleDyas===>');
                                // dump($endCycleDyas);

                                // dump(' in app id 0 endcycle_date===>');
                                // dump($endcycle_date);

                                $reminderDate = new DateTime($reminderDate);
                                $endDate = new DateTime($endcycle_date);
                                //$endDate = '2024-01-10 09:00:00';                             
                                $date_today=new DateTime();


                                /*if($patientid==47521)
                                {
                                  dump(' endCycleDyas===>');
                                  dump($endCycleDyas);

                                  dump(' reminderDate===>');
                                  dump($reminderDate);

                                  dump(' endDate===>');
                                  dump($endDate);
                                }*/

                                  // dump(' reminderDate===>');
                                  // dump($reminderDate);

                                  // dump(' endDate===>');
                                  // dump($endDate);

                                  if($endCycleDyas>0)
                                 {
                                    //added by vijay 19/7/2024 #165  
                                    $date_today = $date_today->format('Y-m-d H:i:s');
                                    $date_today = Carbon::parse($date_today);
                                    $reminderDate = $reminderDate->format('Y-m-d H:i:s');
                                    $reminderDate = Carbon::parse($reminderDate);
                                    $endDate = $endDate->format('Y-m-d H:i:s');
                                    $endDate = Carbon::parse($endDate);

                                    $comparison1 = $endDate->lessThan($date_today);
                                    $comparison2 = $reminderDate->greaterThan($endDate);
                                    
                                    // if($endDate<$date_today){
                                    //      $status=__('admin.IGNORE_STATUS');
                                    // }
                                    // else if($reminderDate>=$endDate)
                                    // {
                                    //     $status=__('admin.IGNORE_STATUS');
                                    // } 
                                    if ($comparison1) {
                                        $status = __('admin.IGNORE_STATUS');
                                    } else if ($comparison2) {
                                        $status = __('admin.IGNORE_STATUS');
                                    }
                                    // end

                                // dump(' in app id 0 if condition reminder_status===>');
                                     // dump($status);

                                 }//if endCycleDyas > 0

                            }//if is_doctor_set_reminder 
                        }//if reminder type is control
                  //  }//if patientid
                    
                    //added above code on 10-jan-24 for control reminder status ignore
                    // added by vijay 23/7/2024 #165 b)
                        if($value_rem['type']=='general')
                        {
                            //commented below code on 12-dec-24

                            // $is_set_reminder = db::table('preferred_channels_for_reminders_setting')->where(
                            //     ['service_id' => $value_rem['service_id']])->first();

                            //added below code on 12-dec-24
                            $is_set_reminder1 = db::table('preferred_channels_for_reminders_setting')->where(
                                'recommanded_service_id', $value_rem['service_id'])->where( 'activated_reminder','general')->first();
                            if(isset($is_set_reminder1)){
                                $is_set_reminder = $is_set_reminder1;
                            }else{
                                $is_set_reminder = db::table('preferred_channels_for_reminders_setting')->where(
                                 ['service_id' => $value_rem['service_id']])->first();

                            } 

                       
                            if($is_set_reminder)
                            {
                                //commented on 15-oct-24
                                // $checkup_period_controls =  $is_set_reminder->general_first_frequency;
                                // $checkup_period_frequency_type = $is_set_reminder->general_first_frequency_type;


                                //added on 15-oct-24
                                $checkup_period_controls = $is_set_reminder->general_end_cycle;
                                $checkup_period_frequency_type = $is_set_reminder->general_end_cycle_frequency_type;


                                $endCycleDyas = $this->_getDate(($reminderDate),$checkup_period_controls,$checkup_period_frequency_type);  
                                $endcycle_date = $this->_filterWeekendAndHoiliday(($reminderDate),$endCycleDyas,0,'plus');
                          
                                // $reminderDate = new DateTime($reminderDate);
                                $endDate = new DateTime($endcycle_date);
                                $date_today=new DateTime();

                                $date_today = $date_today->format('Y-m-d H:i:s');
                                $date_today = Carbon::parse($date_today);
                                // $reminderDate = $reminderDate->format('Y-m-d H:i:s');
                                // $reminderDate = Carbon::parse($reminderDate);
                                $endDate = $endDate->format('Y-m-d H:i:s');
                                $endDate = Carbon::parse($endDate);

                                if ($date_today->toDateString() > $endDate->toDateString()) {
                                    $status = __('admin.IGNORE_STATUS');
                                }
                            }
                        }
                        // end

                    $trTable .= '<tr>
                        <td>'.$value_rem['name'].'</td>
                        <td>'.Date('d-m-Y H:i:s',strtotime($value_rem['reminder_date'])).'</td>
                        <td>'.$services_type.'</td>
                        <td>'.$appDate.'</td>
                        <td>'.ucfirst($value_rem['media']).'</td>
                        <td>'.$status.'</td>
                        <td>'.$read_status.'</td>
                        <td>'.$value_rem['cycle_no'].'</td>
                    </tr>';
                }//foreach
            }//if
            else
            {     $trTable ='';
                  $trTable .= '<tr><td colspan="8" align="center">'.__('admin.ERR_NOT_FOUND').'</td></tr>';
            }         
            return $trTable;      
        }//if patientid
    }//getReminderData
    // public function getReminderData(Request $request)
    // {
    //     $patientid = $request->patientid;
    //     if($patientid)
    //     {
    //         // $getRemider = $this->PatientsHasServiceReminderModel
    //         //                       ->select('patient_has_service_reminder.*','examinations.name')
    //         //                       ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
    //         //                       ->where('patient_id',$patientid)
    //         //                       ->where('patient_has_service_reminder.status','activate')//Added by Shyam 14-03-22
    //         //                       ->where('patient_has_service_reminder.reminder_status','Set')//Added by Shyam 14-03-22
    //         //                       ->whereNull('patient_has_service_reminder.deleted_at')
    //         //                       ->orderBy('patient_has_service_reminder.id','asc')
    //         //                       ->groupBy('patient_has_service_reminder.service_id')
    //         //                       ->get();
    //         //DB::enableQueryLog();


    //          //Commented below query on 20-oct-23 for examination deleted at null condition
    //         /*$getRemider = DB::select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
    //                                 JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
    //                                 JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
    //                                     and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
    //                                     ((reminder_status='ignore' and status='deactivate') or (appointment_id!=0 and reminder_status='Set' and status='deactivate')))
    //                                     and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
    //                                 WHERE t1.reminder_status IN('Set','ignore') AND
    //                                 t1.patient_id=$patientid and t1.deleted_at is NULL
    //                                 GROUP by t1.service_id
    //                                 ORDER by t1.id DESC"));*/


    //          //commented below live query on 9-feb-24 for testing                       
    //          /*$getRemider = DB::select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
    //                                 JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
    //                                 AND examinations.deleted_at IS NULL
    //                                 JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
    //                                     and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
    //                                     ((reminder_status='ignore' and status='deactivate') or (appointment_id!=0 and reminder_status='Set' and status='deactivate')))
    //                                     and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
    //                                 WHERE t1.reminder_status IN('Set','ignore') AND
    //                                 t1.patient_id=$patientid and t1.deleted_at is NULL
    //                                 GROUP by t1.service_id
    //                                 ORDER by t1.id DESC"));*/

    //          //added below query on 9-feb-24                       
    //          /*$getRemider = DB::select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
    //                                 JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
    //                                 AND examinations.deleted_at IS NULL
    //                                 JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
    //                                     and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
    //                                     ((reminder_status='ignore' and status='deactivate') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
    //                                     and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
    //                                 WHERE t1.reminder_status IN('Set','ignore') AND
    //                                 t1.patient_id=$patientid and t1.deleted_at is NULL
    //                                 GROUP by t1.service_id
    //                                 ORDER by t1.id DESC")); */

    //         //added and type not general condition on 16-feb-24
                                    
    //         $getRemider = DB::select(DB::raw("SELECT t1.*,examinations.name FROM patient_has_service_reminder t1
    //                                 JOIN examinations on examinations.id=t1.service_id and show_as_reminder='1'
    //                                 AND examinations.deleted_at IS NULL
    //                                 JOIN (SELECT service_id, MAX(appointment_id) appointment_id FROM patient_has_service_reminder WHERE patient_id=$patientid
    //                                     and id not IN(SELECT id FROM `patient_has_service_reminder` WHERE `patient_id` = $patientid AND 
    //                                     ((reminder_status='ignore' and status='deactivate' and type!='general') or (appointment_id!=0 and reminder_status='Set' and status='deactivate' and type!='control')))
    //                                     and deleted_at is NULL GROUP BY service_id) t2 ON t1.service_id = t2.service_id AND t1.appointment_id = t2.appointment_id
    //                                 WHERE t1.reminder_status IN('Set','ignore') AND
    //                                 t1.patient_id=$patientid and t1.deleted_at is NULL
    //                                 GROUP by t1.service_id
    //                                 ORDER by t1.id DESC"));                                                
                        

    //         $getRemider = collect($getRemider)->map(function($x){ 
    //             return (array) $x; 
    //         })->toArray();    


           
    //         if(!empty($getRemider) && sizeof($getRemider)>0)
    //         {   
    //             $trTable =  $future_date = '';
    //             foreach ($getRemider as $key_rem => $value_rem) 
    //             {
    //                 $getFutureDetails = $this->PatientHasReminder
    //                                             ->where('service_reminder_id',$value_rem['id'])
    //                                             ->first();
    //                 if(!empty($getFutureDetails) && !empty($getFutureDetails->next_reminder_date))
    //                 {
    //                     $future_date = date('d-m-Y H:i:s',strtotime($getFutureDetails->next_reminder_date));
    //                 }                    
    //                 if(!empty($value_rem['appointment_id']))
    //                 {
    //                     $getdataapp = $this->AppointmentModel->find($value_rem['appointment_id']);
    //                     $appDate = Date('d-m-Y H:i:s',strtotime($getdataapp->start_date));
    //                 }
    //                 else {
    //                     $appDate = '';
    //                 }
    //                 $read_status = 'ungelesen';
    //                 if($value_rem['read_status'] == '1')
    //                 {
    //                     $read_status = 'gelesen';
    //                 }
    //                 $reminderDate = Date('d-m-Y H:i:s',strtotime($value_rem['reminder_date']));
    //                 $currentDate = Date('d-m-Y H:i:s');
    //                 $status ='inaktiv';
    //                 // if((strtotime($reminderDate) < strtotime($currentDate)) || (strtotime($reminderDate)==strtotime($currentDate)))
    //                 if((strtotime($reminderDate) < strtotime($currentDate)) || $value_rem['appointment_id']==0)
    //                 {
    //                     $status =  'aktiv';
    //                 }
    //                 if($value_rem['reminder_status']=='ignore' && $value_rem['status']!='deactivate')  
    //                     $status=__('admin.IGNORE_STATUS');
    //                 if($value_rem['reminder_status']=='ignore' && $value_rem['status']=='deactivate')  
    //                     $status='inaktiv';
    //                 if(($value_rem['appointment_id']==0 && $value_rem['status']=='deactivate'))$status ='inaktiv';
    //                 if($value_rem['type'] == 'age')
    //                 {
    //                     $services_type = 'Profil-LE';
    //                 }
    //                 else {
    //                     $services_type = ($value_rem['type']=='control') ? 'Kontroll-LE': 'Folge-LE' ;
    //                 }


    //                 //added below code on 10-jan-24 for control reminder status ignore
    //                 // if($patientid==44999) //konle2 puremed
    //                 // if($patientid==47350) //testcontrol
    //                 // if($patientid==47376) //Testcontrolnew
    //                // {

    //                     if($value_rem['type']=='control')
    //                     {
    //                         //dump($status);

    //                          $is_doctor_set_reminder = db::table('patient_has_service_control_reminder_setting')->where(
    //                         ['patient_id' => $patientid,
    //                         'appointment_id' =>$value_rem['appointment_id'],
    //                         'service_id' => $value_rem['service_id'],
    //                         'status' => '1',
    //                         ]
    //                         )->first();

    //                         if($is_doctor_set_reminder)
    //                         {
    //                             $checkup_period_controls =  $is_doctor_set_reminder->control_interval;
    //                             $checkup_period_frequency_type = $is_doctor_set_reminder->control_frequency;


    //                             $endCycleDyas = $this->_getDate(($reminderDate),$checkup_period_controls,$checkup_period_frequency_type);  
    //                             $endcycle_date = $this->_filterWeekendAndHoiliday(($reminderDate),$endCycleDyas,0,'plus');
                               
    //                             // dump(' inendCycleDyas===>');
    //                             // dump($endCycleDyas);

    //                             // dump(' in app id 0 endcycle_date===>');
    //                             // dump($endcycle_date);

    //                             $reminderDate = new DateTime($reminderDate);
    //                             $endDate = new DateTime($endcycle_date);
    //                             //$endDate = '2024-01-10 09:00:00';                             
    //                             $date_today=new DateTime();


    //                             /*if($patientid==47521)
    //                             {
    //                               dump(' endCycleDyas===>');
    //                               dump($endCycleDyas);

    //                               dump(' reminderDate===>');
    //                               dump($reminderDate);

    //                               dump(' endDate===>');
    //                               dump($endDate);
    //                             }*/

    //                               // dump(' reminderDate===>');
    //                               // dump($reminderDate);

    //                               // dump(' endDate===>');
    //                               // dump($endDate);

    //                               if($endCycleDyas>0)
    //                              {

    //                                 //dump(' if endCycleDyas > 0===>');                                 
    //                                  //if parameter 6 has not passed yet
    //                                 if($endDate<$date_today){
    //                                    // dump("endDate is less than todays date");
    //                                     //if paramter 6 has passed (end date is passed yet)
    //                                      $status=__('admin.IGNORE_STATUS');
    //                                 }
    //                                 else if($reminderDate>=$endDate)
    //                                 {
    //                                     // dump("reminderDate is greater than end date");
    //                                     $status=__('admin.IGNORE_STATUS');
    //                                 } 

    //                                  // dump(' in app id 0 if condition reminder_status===>');
    //                                  // dump($status);

    //                              }//if endCycleDyas > 0

    //                         }//if is_doctor_set_reminder 
    //                     }//if reminder type is control
    //               //  }//if patientid
                    
    //                 //added above code on 10-jan-24 for control reminder status ignore



    //                 $trTable .= '<tr>
    //                     <td>'.$value_rem['name'].'</td>
    //                     <td>'.Date('d-m-Y H:i:s',strtotime($value_rem['reminder_date'])).'</td>
    //                     <td>'.$services_type.'</td>
    //                     <td>'.$appDate.'</td>
    //                     <td>'.ucfirst($value_rem['media']).'</td>
    //                     <td>'.$status.'</td>
    //                     <td>'.$read_status.'</td>
    //                 </tr>';
    //             }//foreach
    //         }//if
    //         else
    //         {     $trTable ='';
    //               $trTable .= '<tr><td colspan="7" align="center">'.__('admin.ERR_NOT_FOUND').'</td></tr>';
    //         }         
    //         return $trTable;      
    //     }//if patientid
    // }//getReminderData


    public function _getDate($start_date,$period,$frequency_type)
    {
        $days = 0;
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
        return $days;
    }//_getDate

     public function _filterWeekendAndHoiliday($date,$days,$is_hoilday_or_weekend,$operation)
    { 

        $operator = '+';
        if($operation == 'minus')
        {
            $operator = '-';
        }
        $calculated_date = Date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", strtotime($date)) . " ".$operator.(int)$days." day"));
        $weekDay = date('w', strtotime($calculated_date));
          // Log::info($date."=".$days."=".$is_hoilday_or_weekend."=".$operation."=".$calculated_date);
          // dump($date."=".$days."=".$is_hoilday_or_weekend."=".$operation."=".$calculated_date);
        // if($is_hoilday_or_weekend == 1 && ($weekDay == 0 || $weekDay == 6))
        // {
        //     $time = date('H:i:s',strtotime($calculated_date));
        //     $calculated_date = Date('Y-m-d', strtotime($calculated_date.' +1 Weekday'));
        //     $calculated_date = $calculated_date." ".$time;
        //     // dump($calculated_date);
        // }
        // dump($calculated_date);
        //Log::info($calculated_date);
        return $calculated_date;
    }//
} 
