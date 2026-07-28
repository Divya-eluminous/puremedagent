<?php

namespace App\Http\Controllers\Admin; 

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 

// Models
use App\Models\ExaminationsModel;
use App\Models\CheckListModel; 
use App\Models\CheckListHasHeadingSectionModel; 
use App\Models\ActivityLogModel; 
use App\Models\ExaminationsHasMultipleCheckListModel; 
use App\Models\AppointmentTypesModel; 
use App\Models\AppointmentTypeHasExaminationsModel; 
use App\Models\SpecialistDocumentsModel;
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\ChannelsRemindersSettingModel;
// Request
use App\Http\Requests\Admin\ExaminationsRequest;
use App\Models\SpecialistModel;
use App\Models\CheckListHasSelectedQuestionModel;

//Trait
use App\Traits\GeneralTrait;

// plugins
use Hash;
use DB;
use Auth;
use Storage; 
use Session;

//added below 3 models on 10-oct-23
use App\Models\UpdateServiceRecordModel; // Added on 8-sept-23
use App\Models\PatientsModel; // Added on 8-sept-23
use App\Models\UpdateServiceRemindersModel; // Added on 3-oct-23
use Log;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024



class ExaminationsController extends Controller
{
    private $BaseModel;
    use GeneralTrait;

    public function __construct(
        ExaminationsModel $ExaminationsModel,
        ActivityLogModel $ActivityLogModel,
        CheckListModel $CheckListModel,
        CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
        ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
        AppointmentTypesModel $AppointmentTypesModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        SpecialistDocumentsModel $SpecialistDocumentsModel,
        ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
        SpecialistModel $SpecialistModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
        CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
        UpdateServiceRecordModel $UpdateServiceRecordModel,      // Added on 10-oct-23
        PatientsModel $PatientsModel,                            // Added on 10-oct-23    
        UpdateServiceRemindersModel $UpdateServiceRemindersModel  // Added on 10-oct-23
    )
    {
        $this->BaseModel        = $ExaminationsModel; 
        $this->AdminUserModel   = $ExaminationsModel;
        $this->ActivityLogModel = $ActivityLogModel;;
        $this->CheckListModel   = $CheckListModel;
        $this->CheckListHasHeadingSectionModel   = $CheckListHasHeadingSectionModel;
        $this->ExaminationsHasMultipleCheckListModel  = $ExaminationsHasMultipleCheckListModel;
        $this->AppointmentTypesModel = $AppointmentTypesModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;
        $this->SpecialistModel = $SpecialistModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;

        $this->UpdateServiceRecordModel = $UpdateServiceRecordModel;   // Added on 10-oct-23
        $this->PatientsModel = $PatientsModel;            // Added on 10-oct-23
        $this->UpdateServiceRemindersModel = $UpdateServiceRemindersModel; // Added on 10-oct-23

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle  = __('admin.TITLE_EXAMINATIONS_TEXT');  
        $this->ModuleView   = 'admin.examinations.';
        $this->ModulePath   = 'admin.examinations'; 

        // Permission Middleware
        $this->middleware(['permission:exams-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:exams-add'], ['only' => ['create','store']]);
    }

    public function index()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_EXAMINATIONS_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');
        $this->ViewData['specialists']       = $this->SpecialistModel->get();
        $this->ViewData['specialist_details']= self::__GetSecialits();

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }

    public function create() 
    {
        // Default site settings
        $specilist_id = '';
        if(!empty(Session::get('specialist')))
        {
            $specilist_id       = Session::get("specialist");
            $specialist_details = $this->SpecialistModel->find($specilist_id);
        }
        $this->ModuleTitle              = __('admin.TITLE_EXAMINATIONS_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['specialist_details'] = self::__GetSecialits();
        $this->ViewData['specialists']       = $this->SpecialistModel->get();

        // All examsdata
        $this->ViewData['examinations'] = $this->BaseModel->get();

        //Get All Check List
        $this->ViewData['checkList'] = $this->CheckListModel
                                        ->where('fk_specialist_id',$specilist_id)
                                         ->where('type_of_checklist','performance')
                                        ->get();
         //dd($this->ViewData['checkList']);                               
        $this->ViewData['DocumentList'] = $this->SpecialistDocumentsModel
                                          ->where('fk_specialist_id',$specilist_id)
                                           ->where('type_of_document','service')
                                          ->where('status','1')->get();
        $this->ViewData['specilist_id'] = $specilist_id;


         //Added code on 21-sept-23        
        $this->ViewData['channel_reminders'] = $this->ChannelsRemindersSettingModel->where('type','global')->first();

        // view file with data
        return view($this->ModuleView.'create', $this->ViewData); 
    }

    public function store(ExaminationsRequest $request)
    {
        DB::beginTransaction();
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');

        try {

            $collection     = new $this->BaseModel;    
            $request->add   = 1;
            $maxSortOrder = $this->BaseModel->max('sorting_order'); 
            $request->sorting_order=$maxSortOrder+1;
            $collection     = self::_storeOrUpdate($collection,$request);
            if ($collection) 
            {
                try {
                    // Update sequenc number
                    $updateSequence = $this->BaseModel->find($collection->id); 
                    $sequence_no             = $collection->id;
                    $collection->sequence_no = $sequence_no;
                    $collection->save();
                    // $newData = [];
                    // $newData['name'] = $collection->name;
                    // $newData['url'] = $collection->url;
                    // $newData['status'] = $collection->status;
                    // $newData['created_at'] = $collection->created_at;
                    // $newData['updated_at'] = $collection->updated_at;
                    $newData = $collection->toArray(); 
                    $this->ActivityLogModel->addLog($this->ModuleTitle,'has created exam','Add',null,$newData); 
                    DB::commit();

                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    =  route($this->ModulePath.'.index');
                    $this->JsonData['msg']    = __('admin.EXAM_CREATED'); 
                }
                catch(\Exception $e) {
                    $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                    $this->JsonData['error_msg'] = $e->getMessage();
                    DB::rollback();
                }
            }
            else
            {
                 DB::rollback();
            }

        }
        catch(\Exception $e) {

            $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }

    public function show($id)
    {
        $collections = $this->CheckListHasSelectedQuestionModel
                       ->where('temp_flag','0')
                       ->limit(1)
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
                    dd($data);
                }
                else
                {
                   $data_final =[];
                }
                $cnt++;
            }
        }
    }

    public function edit($encID)
    {
        // Default site settings
        if(!empty(Session::get('specialist')))
        {
            $specilist_id       = Session::get("specialist");
            $specialist_details = $this->SpecialistModel->find($specilist_id);
        }
        $this->ModuleTitle              = __('admin.TITLE_EXAMINATIONS_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['specialist_details'] = self::__GetSecialits();;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All examsdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['exams'] = $this->BaseModel->find($id);

        //Get All Check List
        $this->ViewData['checkList']    = $this->CheckListModel
                                        ->where('fk_specialist_id',$specilist_id)
                                        ->where('type_of_checklist','performance')
                                        ->get();
        $this->ViewData['DocumentList'] = $this->SpecialistDocumentsModel
                                          ->where('fk_specialist_id',$specilist_id)
                                           ->where('type_of_document','service')
                                          ->where('status','1')->get();

        //Get witj in exam check list
        $this->ViewData['MultipleCheckList'] = $this->ExaminationsHasMultipleCheckListModel
                                               ->select('examinations_check_list.id','examinations_check_list.check_list_name')
                                               ->join('examinations_check_list','examinations_check_list.id','examinations_has_multiple_check_list.fk_check_list_id')
                                               ->where('fk_examinations_id',$id)
                                               ->get();

        $this->ViewData['MultipleDocumentList'] = $this->ExaminationsHasMultipleDocumentListModel
                                               ->select('specialist_has_documents.id','specialist_has_documents.name')
                                               ->join('specialist_has_documents','specialist_has_documents.id','examinations_has_multiple_document_list.fk_document_list_id')
                                               ->where('fk_examinations_id',$id)
                                               ->get(); 

        $isSettingSet =  $this->ChannelsRemindersSettingModel->where(['type'=>'service','service_id'=>$id])->first();
        if(empty($isSettingSet))
        {
            $this->ViewData['channel_reminders'] = $this->ChannelsRemindersSettingModel->where('type','global')->first();
        }else
        {
            $this->ViewData['channel_reminders'] = $isSettingSet;
        }
       // $this->ViewData['examinations'] = $this->BaseModel->where('status','1')->get(); //commneted on 16-jan-24 for showing all servies in general reminder popup.

        $this->ViewData['examinations'] = $this->BaseModel->get(); //removed status condition on 16-jan-24

        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }
 
    public function update(ExaminationsRequest $request, $encID)
    {
        DB::beginTransaction();
        
        $id = base64_decode(base64_decode($encID));

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');       
              
        try {

            $collection = $this->BaseModel->find($id);
            // dd($collection);
            $oldData = $collection->toArray();
            // dd($oldData);
            $request->add = 0;
            $collection = self::_storeOrUpdate($collection,$request);
            $newData = $collection->toArray();
            if ($collection) 
            {
                if(!empty($request->input())) 
                {
                    try {
                        // dd($request->all());

                        $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated exam','Update',$oldData,$newData);


                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['url']    = route($this->ModulePath.'.index');
                        $this->JsonData['msg']    = __('admin.EXAM_UPDATED'); 
                        DB::commit();
                    }
                    catch(\Exception $e) {

                        $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $e->getMessage();
                        DB::rollback();
                    }
                }
                else
                {
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    = route($this->ModulePath.'.index');
                    $this->JsonData['msg']    = __('admin.EXAM_UPDATED');
                    DB::commit();
                }
            }
            else 
            {
                 DB::rollback();
            }   
        }
        catch(\Exception $e) {

            $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }//

    public function isGeneralChnagesDone($old_array,$new_array)
    {
      // dump('in isGeneralChnagesDone');

         Log::info('in isGeneralChnagesDone function...');


        if(!empty($old_array) && !empty($new_array))
        {
            Log::info('in isGeneralChnagesDone function.conditions..');

            $change = 0;
           
            if($old_array->recommanded_service_id !=$new_array->reminder_service )
            {
                 $change = 1;
                //  dump('in 1');
            }

            if($old_array->general_period !=$new_array->general_period )
            {
                 $change = 1;
                // dump('in 2');
            }

            if($old_array->general_period_frequency_type !=$new_array->general_period_frequency_type )
            {
                 $change = 1;
               // dump('in 3');
            }
            if($old_array->general_new_frequency !=$new_array->general_new_frequency )
            {
                 $change = 1;
               // dump('in 4');
            }
            if($old_array->general_new_frequency_type !=$new_array->general_new_frequency_type )
            {
                 $change = 1;
                // dump('in 5');
            }
            if($old_array->general_first_frequency !=$new_array->general_first_frequency )
            {
                 $change = 1;
               // dump('in 6');
            }

            if($old_array->general_first_frequency_type !=$new_array->general_first_frequency_type )
            {
                 $change = 1;
                // dump('in 7');
            }
            if($old_array->general_time_interval !=$new_array->general_time_interval )
            {
                 $change = 1;
                // dump('in 8');
            }

            if($old_array->general_time_interval_frequency_type !=$new_array->general_time_interval_frequency_type )
            {
                 $change = 1;
               // dump('in 9');
            }

            if($old_array->general_number_of_interval !=$new_array->general_number_of_interval )
            {
                 $change = 1;
               //  dump('in 10');
            }         
            if($old_array->general_end_cycle !=$new_array->general_end_cycle )
            {
                 $change = 1;
               // dump('in 11');
            }

            if($old_array->general_end_cycle_frequency_type !=$new_array->general_end_cycle_frequency_type )
            {
                 $change = 1;
                // dump('in 12');
            }   
            Log::info('in isGeneralChnagesDone change==>');
            Log::info($change);
            return $change;
        }
       //   dump('change==>'.$change);
       

    }//isGeneralChnagesDone


    public function updateReminder(Request $request, $encID)
    {
        DB::beginTransaction();
        
        $id = base64_decode(base64_decode($encID));

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');       
              
        try {

            $collection = $this->ChannelsRemindersSettingModel->where('service_id',$id)->where('type','service')->first();
            if(empty($collection))
            {
                $collection =  new $this->ChannelsRemindersSettingModel;
            }
            else
            {
                $old_collection =  $collection;
            }
            if(!empty($old_collection))
            {
                $status = $this->_chnagesDone($old_collection,$request);
                if($status == 1)
                {
                    $collection->is_reminder_updated =1;
                }                
            }else
            {
                $collection->is_reminder_updated =0;
            }
            $notify_time =  $this->ChannelsRemindersSettingModel->where('type','global')->pluck('notify_time')->first();
            $collection->notify_time       = $notify_time; 
            $collection->activated_reminder       = 'general'; 
            $collection->general_period       = $request->general_period;
            $collection->general_period_frequency_type       = $request->general_period_frequency_type;
            $collection->general_new_frequency       = $request->general_new_frequency;
            $collection->general_new_frequency_type       = $request->general_new_frequency_type;
            $collection->general_first_frequency       = $request->general_first_frequency;
            $collection->general_first_frequency_type       = $request->general_first_frequency_type;
            $collection->general_time_interval       = $request->general_time_interval;
            $collection->general_time_interval_frequency_type       = $request->general_time_interval_frequency_type;
            $collection->general_number_of_interval       = $request->general_number_of_interval;
           //Added by swati 9-May-23 (6-Jun-23)===============================
            $collection->general_end_cycle       = $request->general_end_cycle;
            $collection->general_end_cycle_frequency_type = $request->general_end_cycle_frequency_type;

            $collection->type       = 'service'; 
            $collection->service_id       =  $id;
            $collection->recommanded_service_id       =  $request->reminder_service;

            /****************27-oct-23*****uploaded on 31-oct-23********************************/
            $isChange=0;
            $oldDbCollection = $this->ChannelsRemindersSettingModel->where('service_id',$id)->where('type','service')->where('activated_reminder','general')->first();
            if(!empty($oldDbCollection))
            {
               $isChange =  $this->isGeneralChnagesDone($oldDbCollection,$request);
            }
            /******************27-oct-23*****uploaded on 31-oct-23****************************/

            $collection->save();
            if ($collection) 
            {
                 //update show as control field to 0 for general reminder
                 $serviceCollection = $this->BaseModel->find($id);
                 $serviceCollection->show_as_control = 0;
                 $serviceCollection->save();

                if(!empty($request->input())) 
                {
                    try {             

                         /********divya code added on 10-sept-23***********************/
                        if($isChange==1)
                        {
                            //Get max patient id
                            $maxPatientId = PatientsModel::max('id');                      
                            $updateServicecollection = $this->UpdateServiceRecordModel->where('service_id',$id)->orderBy('id','desc')->first();
                            if(!empty($updateServicecollection))
                            {
                                //If already updated by cron then not update previous record
                                if($updateServicecollection->is_reminder_updated==0 && $updateServicecollection->updated_by=="Cron"){

                                }
                                else
                                {
                                    $this->UpdateServiceRecordModel->where('service_id',$id)->update(['is_reminder_updated'=>0,'updated_by'=>'Admin']);
                                }
                              
                            }//if not empty updateServicecollection
                            $updateServicecollection =  new $this->UpdateServiceRecordModel;
                            $updateServicecollection->user_id = auth()->user()->id;
                            $updateServicecollection->service_id = $id;
                            $updateServicecollection->is_reminder_updated = 1;
                            $updateServicecollection->start_patient_id = 0;
                            $updateServicecollection->max_patient_id = $maxPatientId;
                            $updateServicecollection->updated_by = 'Admin';
                            $updateServicecollection->inserted_through = 'Admin';
                            $updateServicecollection->activated_reminder  = 'general';
                            $updateServicecollection->general_period       = $request->general_period;
                            $updateServicecollection->general_period_frequency_type = $request->general_period_frequency_type;
                            $updateServicecollection->general_new_frequency       = $request->general_new_frequency;
                            $updateServicecollection->general_new_frequency_type  = $request->general_new_frequency_type;
                            $updateServicecollection->general_first_frequency     = $request->general_first_frequency;
                            $updateServicecollection->general_first_frequency_type= $request->general_first_frequency_type;
                            $updateServicecollection->general_time_interval       = $request->general_time_interval;
                            $updateServicecollection->general_time_interval_frequency_type = $request->general_time_interval_frequency_type;
                            $updateServicecollection->general_number_of_interval = $request->general_number_of_interval;
                            $updateServicecollection->general_end_cycle       = $request->general_end_cycle;
                            $updateServicecollection->general_end_cycle_frequency_type = $request->general_end_cycle_frequency_type; 
                            $updateServicecollection->recommanded_service_id  =  $request->reminder_service;
                            $updateServicecollection->save();

                           /********divya code added on 8-sept-23***********************/  

                           /*************code added on 3-oct-23*****************/

                           /* $getAllPatientIds =  DB::table('patient_has_service_reminder')
                                                ->select('patient_has_service_reminder.patient_id', 'patient_has_service_reminder.service_id')     
                                                ->join('patients','patients.id','=','patient_has_service_reminder.patient_id')
                                                ->where('patient_has_service_reminder.service_id',$id)
                                                ->whereNull('patients.deleted_at')
                                                 ->distinct()
                                                 ->get();

                             if(isset($getAllPatientIds) && !empty($getAllPatientIds)){

                                foreach ($getAllPatientIds as $ke => $v){
                                    $updateServiceReminderArr = [];
                                    $updateServiceReminderArr['patient_id'] = $v->patient_id;
                                    $updateServiceReminderArr['service_id'] = $v->service_id;
                                    $updateServiceReminderArr['type'] = 'General';
                                    $reminder_id = DB::table('update_service_reminders')->insertGetId(
                                        $updateServiceReminderArr);    
                                }
                              }//if getAllPatientIds  
                             */

                                $checkIsExists = DB::table('update_service_reminders')
                                 ->whereNull('deleted_at')
                                 ->where('service_id',$id)->count();
                                if($checkIsExists>0){
                                    DB::table('update_service_reminders')
                                    ->whereNull('deleted_at')
                                    ->where('service_id',$id)
                                    ->update(['deleted_at'=>date('Y-m-d H:i:s'),'deleted_through'=>'admin']);
                                    //->delete();
                                }

                                 /* DB::statement("
                                    INSERT INTO update_service_reminders (patient_id, service_id, type,deleted_through)
                                    SELECT DISTINCT patient_id, service_id, 'general','' as type
                                    FROM patient_has_service_reminder
                                    JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                    WHERE service_id = :id
                                    AND patients.deleted_at IS NULL", ['id' => $id]);*/

                               /*DB::statement("
                                    INSERT INTO update_service_reminders (patient_id, service_id, type, deleted_through, update_service_id)
                                    SELECT DISTINCT patient_id, service_id, 'general', '', :type
                                    FROM patient_has_service_reminder
                                    JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                    WHERE service_id = :id
                                    AND patients.deleted_at IS NULL
                                    AND patient_has_service_reminder.deleted_at IS NULL
                                    AND patient_id IN (16011,7666,42237,39749,34712,35795,31037,20053,20303,15746,22227,13466,26252,12848,20295,31961,19164,21530,16913)", ['id' => $id, 'type' => $updateServicecollection->id]);    */


                                DB::statement("
                                    INSERT INTO update_service_reminders (patient_id, service_id, type, deleted_through, update_service_id)
                                    SELECT DISTINCT patient_id, service_id, 'general', '', :type
                                    FROM patient_has_service_reminder
                                    JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                    WHERE service_id = :id
                                    AND patients.deleted_at IS NULL
                                    AND patient_has_service_reminder.deleted_at IS NULL
                                    ", ['id' => $id, 'type' => $updateServicecollection->id]);

                        }//if isChange is 1                                   

                        /************code added on 10-oct-23******************************/  


                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['url']    = route($this->ModulePath.'.index');
                        $this->JsonData['msg']    = __('admin.TITLE_REMINDER_UPDATE'); 
                        DB::commit();
                    }
                    catch(\Exception $e) {

                        $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $e->getMessage();
                        DB::rollback();
                    }
                }
                else
                {
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    = route($this->ModulePath.'.index');
                    $this->JsonData['msg']    = __('admin.EXAM_UPDATED');
                    DB::commit();
                }
            }
            else 
            {
                 DB::rollback();
            }   
        }
        catch(\Exception $e) {

            $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }//updateReminder

     public function isAgeChangesDone($old_array,$new_array)
    {
       // dump('in isAgeChangesDone'); 
          Log::info('in isAgeChangesDone function...');

        if(!empty($old_array) && !empty($new_array))
        {
              Log::info('in isAgeChangesDone function.conditions..');
            $change = 0;

            if($old_array->age_from !=$new_array->age_age_from )
            {
                 $change = 1;
                // dump('in 1');
            }
            if($old_array->age_to !=$new_array->age_age_to )
            {
                 $change = 1;
                 // dump('in 2');
            }

            if($old_array->age_period_controls !=$new_array->age_period_controls )
            {
                 $change = 1;
                //  dump('in 3');
            }
            if($old_array->age_period_frequency_type !=$new_array->age_period_frequency_type )
            {
                 $change = 1;
                //  dump('in 4');
            }
            if($old_array->age_new_frequency !=$new_array->age_new_frequency )
            {
                 $change = 1;
                // dump('in 5');
            }

            if($old_array->age_new_frequency_type !=$new_array->age_new_frequency_type )
            {
                 $change = 1;
                 // dump('in 6');
            }
            if($old_array->age_first_frequency !=$new_array->age_first_frequency )
            {
                 $change = 1;
                // dump('in 7');
            }

            if($old_array->age_first_frequency_type !=$new_array->age_first_frequency_type )
            {
                 $change = 1;
                // dump('in 8');
            }

            if($old_array->age_time_interval !=$new_array->age_time_interval )
            {
                 $change = 1;
                // dump('in 9');
            }
            if($old_array->age_time_interval_frequency_type !=$new_array->age_time_interval_frequency_type )
            {
                 $change = 1;
                // dump('in 10'); 
            }

            if($old_array->age_number_of_interval !=$new_array->age_number_of_interval )
            {
                 $change = 1;
                // dump('in 11'); 
            }

            if($old_array->age_end_cycle !=$new_array->age_end_cycle )
            {
                 $change = 1;
                // dump('in 12');
            }

            if($old_array->age_end_cycle_frequency_type !=$new_array->age_end_cycle_frequency_type )
            {
                 $change = 1;
                // dump('in 13');
            }
               Log::info('in isAgeChangesDone change==>');
               Log::info($change);
           return $change;
        }
       // dump('change==>'.$change); 
     
    }//isAgeChangesDone

    public function updateAgeReminder(Request $request, $encID)
    {
        DB::beginTransaction();
        
        $id = base64_decode(base64_decode($encID));

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');       
              
        try {

            $collection = $this->ChannelsRemindersSettingModel->where('service_id',$id)->where('type','service')->first();
            if(empty($collection))
            {
                $collection =  new $this->ChannelsRemindersSettingModel;
            }
            else
            {
                $old_collection =  $collection;
            }

        
            if(!empty($old_collection))
            {
                $status = $this->_chnagesDone($old_collection,$request);
                if($status == 1)
                {
                    $collection->is_reminder_updated =1;
                }                
            }else
            {
                $collection->is_reminder_updated =0;
            }

            $collection->activated_reminder       = 'age'; 
            
            $notify_time =  $this->ChannelsRemindersSettingModel->where('type','global')->pluck('notify_time')->first();
            $collection->notify_time       = $notify_time;
            $collection->age_period_controls       = $request->age_period_controls;
            $collection->age_period_frequency_type       = $request->age_period_frequency_type;
            $collection->age_new_frequency       = $request->age_new_frequency;
            $collection->age_new_frequency_type       = $request->age_new_frequency_type;
            $collection->age_first_frequency       = $request->age_first_frequency;
            $collection->age_first_frequency_type       = $request->age_first_frequency_type;
            $collection->age_time_interval       = $request->age_time_interval;
            $collection->age_time_interval_frequency_type       = $request->age_time_interval_frequency_type;
            $collection->age_number_of_interval       = $request->age_number_of_interval;
            $collection->age_from       = $request->age_age_from;
            $collection->age_to       = $request->age_age_to;
            //Added by swati 9-May-23 (6-Jun-23)===============================
            $collection->age_end_cycle       = $request->age_end_cycle;
            $collection->age_end_cycle_frequency_type = $request->age_end_cycle_frequency_type;

            $collection->type       = 'service'; 
            $collection->service_id       =  $id;

            /****************27-oct-23******uploaded on 31-oct-23******************/
            $isChange=0;
            $oldDbCollection = $this->ChannelsRemindersSettingModel->where('service_id',$id)->where('type','service')->where('activated_reminder','age')->first();
            if(!empty($oldDbCollection))
            {
               $isChange =  $this->isAgeChangesDone($oldDbCollection,$request);
            }
            /******************27-oct-23*****uploaded on 31-oct-23****************/


            $collection->save();
            if ($collection) 
            {
                //update show as control field to 0 for age reminder
                 $serviceCollection = $this->BaseModel->find($id);
                 $serviceCollection->show_as_control = 0;
                 $serviceCollection->save();

                if(!empty($request->input())) 
                {
                    try {  

                         /********divya code added on 10-oct-23***********************/

                        if($isChange==1)
                        {
                                //Get max patient id
                                $maxPatientId = PatientsModel::max('id');                      
                                $updateServicecollection = $this->UpdateServiceRecordModel->where('service_id',$id)->orderBy('id','desc')->first();
                                if(!empty($updateServicecollection))
                                {
                                    //If already updated by cron then not update previous record
                                    if($updateServicecollection->is_reminder_updated==0 && $updateServicecollection->updated_by=="Cron"){

                                    }
                                    else
                                    {
                                        $this->UpdateServiceRecordModel->where('service_id',$id)->update(['is_reminder_updated'=>0,'updated_by'=>'Admin']);
                                    }
                                }
                                $updateServicecollection =  new $this->UpdateServiceRecordModel;
                                $updateServicecollection->user_id = auth()->user()->id;
                                $updateServicecollection->service_id = $id;
                                $updateServicecollection->is_reminder_updated = 1;
                                $updateServicecollection->start_patient_id = 0;
                                $updateServicecollection->max_patient_id = $maxPatientId;
                                $updateServicecollection->updated_by = 'Admin';
                                $updateServicecollection->inserted_through = 'Admin';
                                $updateServicecollection->activated_reminder = 'age'; 
                                $updateServicecollection->age_period_controls       = $request->age_period_controls;
                                $updateServicecollection->age_period_frequency_type = $request->age_period_frequency_type;
                                $updateServicecollection->age_new_frequency       = $request->age_new_frequency;
                                $updateServicecollection->age_new_frequency_type  = $request->age_new_frequency_type;
                                $updateServicecollection->age_first_frequency     = $request->age_first_frequency;
                                $updateServicecollection->age_first_frequency_type = $request->age_first_frequency_type;
                                $updateServicecollection->age_time_interval       = $request->age_time_interval;
                                $updateServicecollection->age_time_interval_frequency_type = $request->age_time_interval_frequency_type;
                                $updateServicecollection->age_number_of_interval = $request->age_number_of_interval;
                                $updateServicecollection->age_from       = $request->age_age_from;
                                $updateServicecollection->age_to         = $request->age_age_to;
                                $updateServicecollection->age_end_cycle  = $request->age_end_cycle;
                                $updateServicecollection->age_end_cycle_frequency_type = $request->age_end_cycle_frequency_type;
                                $updateServicecollection->save();                      
                              
                                /********divya code added on 8-sept-23***********************/

                                 /*************code added on 3-oct-23*****************/

                                /* $getAllPatientIds =  DB::table('patient_has_service_reminder')
                                                        ->select('patient_has_service_reminder.patient_id', 'patient_has_service_reminder.service_id')     
                                                        ->join('patients','patients.id','=','patient_has_service_reminder.patient_id')
                                                        ->where('patient_has_service_reminder.service_id',$id)
                                                        ->whereNull('patients.deleted_at')
                                                         ->distinct()
                                                         ->get();

                                  if(isset($getAllPatientIds) && !empty($getAllPatientIds)){

                                    foreach ($getAllPatientIds as $ke => $v){
                                        $updateServiceReminderArr = [];
                                        $updateServiceReminderArr['patient_id'] = $v->patient_id;
                                        $updateServiceReminderArr['service_id'] = $v->service_id;
                                         $updateServiceReminderArr['type'] = 'Age';
                                        $reminder_id = DB::table('update_service_reminders')->insertGetId(
                                            $updateServiceReminderArr);    
                                    }

                                  }//if getAllPatientIds   
                                  */

                                    $checkIsExists = DB::table('update_service_reminders')
                                    ->whereNull('deleted_at')
                                    ->where('service_id',$id)->count();
                                    if($checkIsExists>0){
                                        DB::table('update_service_reminders')
                                        ->whereNull('deleted_at')
                                        ->where('service_id',$id)
                                        ->update(['deleted_at'=>date('Y-m-d H:i:s'),'deleted_through'=>'admin']);
                                       // ->delete();
                                    }

                                    /*  DB::statement("
                                        INSERT INTO update_service_reminders (patient_id, service_id, type,deleted_through)
                                        SELECT DISTINCT patient_id, service_id, 'age','' as type
                                        FROM patient_has_service_reminder
                                        JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                        WHERE service_id = :id
                                        AND patients.deleted_at IS NULL", ['id' => $id]);*/


                                     /* DB::statement("
                                        INSERT INTO update_service_reminders (patient_id, service_id, type,deleted_through,update_service_id)
                                        SELECT DISTINCT patient_id, service_id, 'age','',$updateServicecollection->id as type
                                        FROM patient_has_service_reminder
                                        JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                        WHERE service_id = :id
                                        AND patients.deleted_at IS NULL
                                        AND patient_has_service_reminder.deleted_at IS NULL
                                        AND patient_id IN (16913)", ['id' => $id]); */
         

                                      /* DB::statement("
                                        INSERT INTO update_service_reminders (patient_id, service_id, type,deleted_through,update_service_id)
                                        SELECT DISTINCT patient_id, service_id, 'age','',$updateServicecollection->id as type
                                        FROM patient_has_service_reminder
                                        JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                        WHERE service_id = :id
                                        AND patients.deleted_at IS NULL
                                        AND patient_has_service_reminder.deleted_at IS NULL
                                        AND patient_id IN (16011,7666,42237,39749,34712,35795,31037,20053,20303,15746,22227,13466,26252,12848,20295,31961,19164,21530,16913)", ['id' => $id]);*/


                                        DB::statement("
                                        INSERT INTO update_service_reminders (patient_id, service_id, type,deleted_through,update_service_id)
                                        SELECT DISTINCT patient_id, service_id, 'age','',$updateServicecollection->id as type
                                        FROM patient_has_service_reminder
                                        JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                        WHERE service_id = :id
                                        AND patients.deleted_at IS NULL
                                        AND patient_has_service_reminder.deleted_at IS NULL 
                                        ", ['id' => $id]);

                        }//if isChange is 1    
                               

                        /************code added on 10-oct-23******************************/
                     

                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['url']    = route($this->ModulePath.'.index');
                        $this->JsonData['msg']    = __('admin.TITLE_REMINDER_UPDATE'); 
                        DB::commit();
                    }
                    catch(\Exception $e) {

                        $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $e->getMessage();
                        DB::rollback();
                    }
                }
                else
                {
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    = route($this->ModulePath.'.index');
                    $this->JsonData['msg']    = __('admin.EXAM_UPDATED');
                    DB::commit();
                }
            }
            else 
            {
                 DB::rollback();
            }   
        }
        catch(\Exception $e) {

            $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }//updateAgeReminder

     public function isCheckupChangesDone($old_array,$new_array)
    {
       // dump('in isCheckupChangesDone'); 
        Log::info('in isCheckupChangesDone function...');

        if(!empty($old_array) && !empty($new_array))
        {
            Log::info('in isCheckupChangesDone function.conditions..');

            $change = 0;
             if($old_array->checkup_period_controls !=$new_array->checkup_period_controls )
            {
                 $change = 1;
               //  dump('in 1');
            }
            if($old_array->checkup_period_frequency_type !=$new_array->checkup_period_frequency_type )
            {
                 $change = 1;
               //  dump('in 2');
            }
            if($old_array->checkup_new_frequency !=$new_array->checkup_new_frequency )
            {
                 $change = 1;
               //  dump('in 3');
            }

            if($old_array->checkup_new_frequency_type !=$new_array->checkup_new_frequency_type )
            {
                 $change = 1;
               //  dump('in 4');
            }

             if($old_array->checkup_first_frequency !=$new_array->checkup_first_frequency )
            {
                 $change = 1;
                //  dump('in 5');
            }

            if($old_array->checkup_first_frequency_type !=$new_array->checkup_first_frequency_type )
            {
                 $change = 1;
                // dump('in 6');
            }
            if($old_array->checkup_time_interval !=$new_array->checkup_time_interval )
            {
                 $change = 1;
                 // dump('in 7');
            }
            if($old_array->checkup_time_interval_frequency_type !=$new_array->checkup_time_interval_frequency_type )
            {
                 $change = 1;
                 // dump('in 8');
            }

            if($old_array->checkup_number_of_interval !=$new_array->checkup_number_of_interval )
            {
                 $change = 1;
                // dump('in 9');
            }
            if($old_array->checkup_end_cycle !=$new_array->checkup_end_cycle )
            {
                 $change = 1;
                // dump('in 10'); 
            }

            if($old_array->checkup_end_cycle_frequency_type !=$new_array->checkup_end_cycle_frequency_type )
            {
                 $change = 1;
                // dump('in 11'); 
            }

            Log::info('in isCheckupChangesDone change==>');
            Log::info($change);
           return $change;
        }
       // dump('change==>'.$change); 
    }//isCheckupChangesDone

    public function updateCheckupReminder(Request $request, $encID)
    {
        DB::beginTransaction();
        
        $id = base64_decode(base64_decode($encID));

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');       
              
        try {

            $collection = $this->ChannelsRemindersSettingModel->where('service_id',$id)->where('type','service')->first();
            if(empty($collection))
            {
                $collection =  new $this->ChannelsRemindersSettingModel;
            }
            else
            {
                $old_collection =  $collection;
            }
            if(!empty($old_collection))
            {
                $status = $this->_chnagesDone($old_collection,$request);
                if($status == 1)
                {
                    $collection->is_reminder_updated =1;
                }                
            }else
            {
                $collection->is_reminder_updated =0;
            }
            $notify_time =  $this->ChannelsRemindersSettingModel->where('type','global')->pluck('notify_time')->first();
            $collection->notify_time       = $notify_time; 
            $collection->activated_reminder       = 'checkup'; 
            $collection->checkup_period_controls       = $request->checkup_period_controls;
            $collection->checkup_period_frequency_type       = $request->checkup_period_frequency_type;
            $collection->checkup_new_frequency       = $request->checkup_new_frequency;
            $collection->checkup_new_frequency_type       = $request->checkup_new_frequency_type;
            $collection->checkup_first_frequency       = $request->checkup_first_frequency;
            $collection->checkup_first_frequency_type       = $request->checkup_first_frequency_type;
            $collection->checkup_time_interval       = $request->checkup_time_interval;
            $collection->checkup_time_interval_frequency_type       = $request->checkup_time_interval_frequency_type;
            $collection->checkup_number_of_interval       = $request->checkup_number_of_interval;
            //Added by swati 9-May-23 (6-Jun-23)===============================
            $collection->checkup_end_cycle       = $request->checkup_end_cycle;
            $collection->checkup_end_cycle_frequency_type = $request->checkup_end_cycle_frequency_type;

            $collection->type       = 'service'; 
            $collection->service_id       =  $id;

            /****************27-oct-23*****uploaded on 31-oct-23****************/
            $isChange=0;
            $oldDbCollection = $this->ChannelsRemindersSettingModel->where('service_id',$id)->where('type','service')->where('activated_reminder','checkup')->first();
            if(!empty($oldDbCollection))
            {
               $isChange =  $this->isCheckupChangesDone($oldDbCollection,$request);
            }
            /******************27-oct-23****uploaded on 31-oct-23****************/

            $collection->save();
            if ($collection) 
            {
                //update show as control field to 1 for checkup reminder
                 $serviceCollection = $this->BaseModel->find($id);
                 $serviceCollection->show_as_control = 1;
                 $serviceCollection->save();

                if(!empty($request->input())) 
                {
                    try {                       


                        /********divya code added on 10-oct-23***********************/

                        if($isChange==1) //if condition added on 27-oct-23
                        { 
                            //Get max patient id
                            $maxPatientId = PatientsModel::max('id');                      
                            $updateServicecollection = $this->UpdateServiceRecordModel->where('service_id',$id)->orderBy('id','desc')->first();
                            if(!empty($updateServicecollection))
                            {
                                //If already updated by cron then not update previous record
                                if($updateServicecollection->is_reminder_updated==0 && $updateServicecollection->updated_by=="Cron"){

                                }
                                else
                                {
                                    $this->UpdateServiceRecordModel->where('service_id',$id)->update(['is_reminder_updated'=>0,'updated_by'=>'Admin']);
                                }
                            }
                            $updateServicecollection =  new $this->UpdateServiceRecordModel;
                            $updateServicecollection->user_id = auth()->user()->id;
                            $updateServicecollection->service_id = $id;
                            $updateServicecollection->is_reminder_updated = 1;
                            $updateServicecollection->start_patient_id = 0;
                            $updateServicecollection->max_patient_id = $maxPatientId;
                            $updateServicecollection->updated_by = 'Admin';
                            $updateServicecollection->inserted_through = 'Admin';
                            $updateServicecollection->activated_reminder  = 'checkup'; 
                            $updateServicecollection->checkup_period_controls  = $request->checkup_period_controls;
                            $updateServicecollection->checkup_period_frequency_type = $request->checkup_period_frequency_type;
                            $updateServicecollection->checkup_new_frequency   = $request->checkup_new_frequency;
                            $updateServicecollection->checkup_new_frequency_type  = $request->checkup_new_frequency_type;
                            $updateServicecollection->checkup_first_frequency     = $request->checkup_first_frequency;
                            $updateServicecollection->checkup_first_frequency_type= $request->checkup_first_frequency_type;
                            $updateServicecollection->checkup_time_interval       = $request->checkup_time_interval;
                            $updateServicecollection->checkup_time_interval_frequency_type  = $request->checkup_time_interval_frequency_type;
                            $updateServicecollection->checkup_number_of_interval = $request->checkup_number_of_interval;
                            $updateServicecollection->checkup_end_cycle   = $request->checkup_end_cycle;
                            $updateServicecollection->checkup_end_cycle_frequency_type = $request->checkup_end_cycle_frequency_type;
                            $updateServicecollection->save();
                           
                            /********divya code added on 8-sept-23***********************/       

                            /*************code added on 3-oct-23*****************/

                             /*$getAllPatientIds =  DB::table('patient_has_service_reminder')
                                                    ->select('patient_has_service_reminder.patient_id', 'patient_has_service_reminder.service_id')     
                                                    ->join('patients','patients.id','=','patient_has_service_reminder.patient_id')
                                                    ->where('patient_has_service_reminder.service_id',$id)
                                                    ->whereNull('patients.deleted_at')
                                                     ->distinct()
                                                     ->get();

                              if(isset($getAllPatientIds) && !empty($getAllPatientIds)){

                                foreach ($getAllPatientIds as $ke => $v){
                                    $updateServiceReminderArr = [];
                                    $updateServiceReminderArr['patient_id'] = $v->patient_id;
                                    $updateServiceReminderArr['service_id'] = $v->service_id;
                                    $updateServiceReminderArr['type'] = 'Checkup';
                                    $reminder_id = DB::table('update_service_reminders')->insertGetId(
                                        $updateServiceReminderArr);    
                                }

                              }//if getAllPatientIds   
                              */

                              $checkIsExists = DB::table('update_service_reminders') 
                              ->whereNull('deleted_at')->where('service_id',$id)->count();
                                if($checkIsExists>0)
                                {
                                    DB::table('update_service_reminders')
                                    ->whereNull('deleted_at')
                                    ->where('service_id',$id)
                                    ->update(['deleted_at'=>date('Y-m-d H:i:s'),'deleted_through'=>'admin']);
                                    //->delete();
                                }



                            /*  DB::statement("
                                INSERT INTO update_service_reminders (patient_id, service_id, type,deleted_through)
                                SELECT DISTINCT patient_id, service_id, 'checkup','' as type
                                FROM patient_has_service_reminder
                                JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                WHERE service_id = :id
                                AND patients.deleted_at IS NULL", ['id' => $id]);  */

                             /* DB::statement("
                                INSERT INTO update_service_reminders (patient_id, service_id, type,deleted_through,update_service_id)
                                SELECT DISTINCT patient_id, service_id, 'checkup','',$updateServicecollection->id as type
                                FROM patient_has_service_reminder
                                JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                WHERE service_id = :id
                                AND patients.deleted_at IS NULL 
                                AND patient_has_service_reminder.deleted_at IS NULL
                                AND patient_id IN (16011,7666,42237,39749,34712,35795,31037,20053,20303,15746,22227,13466,26252,12848,20295,31961,19164,21530,16913)", ['id' => $id]);  */

                             DB::statement("
                                INSERT INTO update_service_reminders (patient_id, service_id, type,deleted_through,update_service_id)
                                SELECT DISTINCT patient_id, service_id, 'checkup','',$updateServicecollection->id as type
                                FROM patient_has_service_reminder
                                JOIN patients ON patients.id = patient_has_service_reminder.patient_id
                                WHERE service_id = :id
                                AND patients.deleted_at IS NULL 
                                AND patient_has_service_reminder.deleted_at IS NULL
                                ", ['id' => $id]);    

                        }//if isChange is 1

                        /************code added on 10-oct-23******************************/         

                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['url']    = route($this->ModulePath.'.index');
                        $this->JsonData['msg']    = __('admin.TITLE_REMINDER_UPDATE'); 
                        DB::commit();
                    }
                    catch(\Exception $e) {

                        $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                        $this->JsonData['error_msg'] = $e->getMessage();
                        DB::rollback();
                    }
                }
                else
                {
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    = route($this->ModulePath.'.index');
                    $this->JsonData['msg']    = __('admin.EXAM_UPDATED');
                    DB::commit();
                }
            }
            else 
            {
                 DB::rollback();
            }   
        }
        catch(\Exception $e) {

            $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }//updateCheckupReminder

    public function destroy($encID)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_DELETE'); 

        $id = base64_decode(base64_decode($encID));

        $BaseModel = $this->BaseModel->find($id); 
        if($BaseModel->delete())
        {
            $newData = $BaseModel->toArray();
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData); 

            //delete multiple check list with in exam
            $deleteExamheckList = $this->ExaminationsHasMultipleCheckListModel->where('fk_examinations_id',$id)->delete();

            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.EXAM_DELETED');
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
            // dd($column+' = '+$dir);

            // filter columns
            $filter = array(
                0 => 'id',
                1 => 'examinations.sorting_order',
                2 => 'examinations.name',
                3 => 'examinations.url',
                4 => 'status',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            // $modelQuery =  $this->BaseModel->select('examinations.*','appointment.start_date')
            //                     ->leftjoin('appointment','appointment.appointment_type_id','=','examinations.id')
            //                     ->where('examinations.fk_specialist_id',$request->specialist_id)->groupBy('examinations.id');
            $modelQuery =  $this->BaseModel
                                ->where('fk_specialist_id',$request->specialist_id);
            
            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

            ## FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {
                if (!empty($request->custom['name'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['name'];
                    $modelQuery    = $modelQuery->where('examinations.name','LIKE','%'.$key.'%');
                }
                if (isset($request->custom['status'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['status'];
                    $modelQuery    = $modelQuery->where('examinations.status', $key);
                }
            }
            ## filter options for commen search box 
            if (!empty($request->search))
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];
                    $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orwhere('examinations.name', 'LIKE', "%".$search."%");
                        $query->orwhere('examinations.url', 'LIKE', '%'.$search.'%');
                        // if(strtolower($search)=="active") {
                        //     $query->orwhere('examinations.status', '=', 1);
                        // }
                        // else {
                        //     $query->orwhere('examinations.status', '=', 0);
                        // }
                    });
                }
            }
            // get total filtered
            $filteredQuery = clone($modelQuery);
            $totalFiltered  = $filteredQuery->count();

            // dd($filter[$column]);
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
                    // $appDate = ($row->start_date)?$row->start_date:$row->created_at;
                    $data[$key]['id']     = $row->id;
                    // $data[$key]['start_date'] = '<span>'.date('d-m-Y H:i:s', strtotime($appDate)).'</span>';
                    $data[$key]['sorting_order']   = $row->sorting_order;
                    $data[$key]['name']   = '<span title="'.ucfirst($row->name).'">'.ucfirst($row->name).'</span>';
                    $data[$key]['url']    = '<a title="'.$row->url.'" href="'.$row->url.'" target="_blank" >'.strtolower($row->url).'</a>';
                    $data[$key]['status'] = $row->status==1 ?__('admin.TITLE_STATUS_ACTIVE_TEXT'):__('admin.TITLE_STATUS_INACTIVE_TEXT');
                    $edit="";
                    $delete="";
                    // Check Permission
                    if(auth()->user()->can('exams-add'))
                    {
                        $edit = '<a href="'.route('admin.examinations.edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                        if(empty($row->default_service))
                        {
                            $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.examinations.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                        }
                    }
                    $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';
                }
            }
            ## SEARCH HTML
            $searchHTML['id']       =  '';
            // $searchHTML['start_date'] =  '';
            $searchHTML['sorting_order'] = '';
            $searchHTML['name']     =  '<input type="text" class="form-control" id="exam_name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['url']      =  '';
            $searchHTML['status']   =  '<select name="status" id="exam_status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_EXAMINATION_STATUS').'</option>
                    <option class="theme-black blue-select" value="1" '.(isset($request->custom['status']) && $request->custom['status'] == "1" ? 'selected' : '').' >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</option>
                    <option class="theme-black blue-select" value="0" '.(isset($request->custom['status']) &&  $request->custom['status'] == "0" ? 'selected' : '').'>'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</option>
                    </select>';
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
        //dd($request->all());
        $collection->fk_specialist_id = $request->specialist_id;
        $collection->name   = $request->name;
        $collection->url    = $request->url;
        $collection->description     = $request->description;
        if($request->add==1){
            $collection->sorting_order   = $request->sorting_order;   
        }

          $setReminderFlag = 0; //added on 10-sept-24


        /*if($request->add==0)
        {
             $collectionDB = $this->BaseModel->find($collection->id);
             $dbSortingOrder = $collectionDB->sorting_order;
            
             if($dbSortingOrder==0){
                $maxSortOrder = $this->BaseModel->max('sorting_order'); 
                $collection->sorting_order=$maxSortOrder+1;
             }
             
        }*/

         //dd($collection);
        
        // if (!empty($request->document_name)) 
        // {
        //     $path = 'exam-doc';

        //     $objDocument = $request->document_name;

        //     $original_file  = $objDocument->getClientOriginalName();
        //     $extension      = strtolower($objDocument->getClientOriginalExtension());

        //     $filename       = date('YmdHis').'-'.$original_file;
        //     // dd($filename,$path);
        //     $file           = Storage::putFileAs($path, $objDocument, $filename);

        //     $filePath       = "/app/exam-doc/".$filename;

        //     $collection->document_name      = $original_file;
        //     $collection->document_path      = $filePath;

        //     if(is_file(storage_path().$request->old_doc_data))
        //     {
        //         unlink(storage_path().$request->old_doc_data);
        //     }  

        // }else if(empty($request->old_file) && !empty($request->old_doc_data)) 
        // {
        //     $collection->document_name = NULL;
        //     $collection->document_path = NULL;
            
        //     if(is_file(storage_path().$request->old_doc_data))
        //     {
        //         unlink(storage_path().$request->old_doc_data);
        //     }
        // }

        $collection->document_status = $request->document_status;
        
        $collection->status = !empty($request->status)?1:0;

        $collection->show_as_control = $request->Show_as_control == 'on' ? 1 : 0; //uncommented on 29-sept-23
        //$collection->trigger_exam_flag = !empty($request->trigger_exam_flag)?1:0; 
       
        if(isset($request->show_as_reminder))
        {
            if($request->show_as_reminder == 'on')
            {
                $setReminderFlag='1';    
                //$collection->show_as_reminder = '1';//commented on 10-setp-24

            }
            else
            {
                $setReminderFlag = '0';
               // $collection->show_as_reminder = '0';//commented on 10-setp-24
                $collection->show_as_control = 0;  // Added on 13-sept-23
            }
        }
        else
        {
            $setReminderFlag = '0';
            //$collection->show_as_reminder = '0';// Added on 10-sept-24


             //commented below code on 22-sept-23
            //below conditon added on 13-sept-23 (for add if select 1 then 1 or 0 and for update if no show as reminder then it becomes 0)
           /* if(isset($request->hd_exam_id)){
              $collection->show_as_control = 0;
            }else{
             $collection->show_as_control = $request->Show_as_control == 'on' ? 1 : 0;
            }*/

            // added new on service add form 22-sept-23
            $collection->show_as_control = 0;
        }

        $collection->on_dashboard  = !empty($request->dashboard_setting)? $request->dashboard_setting:'0';
        $collection->show_as_recommended  = !empty($request->show_as_recommended)? $request->show_as_recommended:'0';
        //Save data

         $collection->show_as_reminder = $setReminderFlag; //added on 10-sept-24
      
        if($collection->save())
        {

             // Added on 10-sept-24
            if(isset($setReminderFlag) && $setReminderFlag==1)
            {
                DB::table('examinations')->where('id', $collection->id)->update(['show_as_reminder' => $setReminderFlag]);
            }

            
            //dd($collection);
            /*
            |Multiple Check List Add with in exam
            */
            $deleteExamheckList = $this->ExaminationsHasMultipleCheckListModel->where('fk_examinations_id',$collection->id)->forceDelete();
            if(isset($request->check_list))
            {
                foreach ($request->check_list as $check_list) 
                {
                    $ExaminationsHasMultipleCheckList = new $this->ExaminationsHasMultipleCheckListModel;
                    $ExaminationsHasMultipleCheckList->fk_examinations_id = $collection->id;
                    $ExaminationsHasMultipleCheckList->fk_check_list_id   = $check_list;
                    $ExaminationsHasMultipleCheckList->fk_specialist_id = $request->specialist_id;
                    $ExaminationsHasMultipleCheckList->created_at         = Date('Y-m-d');
                    $ExaminationsHasMultipleCheckList->save();
                }
            }   

            /*
            |Multiple Document List Add with in exam
            */
            $deleteExamheckList = $this->ExaminationsHasMultipleDocumentListModel->where('fk_examinations_id',$collection->id)->forceDelete();
            if(isset($request->document_list))
            {
                foreach ($request->document_list as $document_list) 
                {
                    $ExaminationsHasMultipleDocumentList = new $this->ExaminationsHasMultipleDocumentListModel;
                    $ExaminationsHasMultipleDocumentList->fk_examinations_id = $collection->id;
                    $ExaminationsHasMultipleDocumentList->fk_document_list_id   = $document_list;
                    $ExaminationsHasMultipleDocumentList->fk_specialist_id = $request->specialist_id;
                    $ExaminationsHasMultipleDocumentList->created_at         = Date('Y-m-d');
                    $ExaminationsHasMultipleDocumentList->save();
                }
            }    

            //start ---->Reminder popup code for save entry to the preferred channel table after service created added on 22-sept-23

            //Get service record
            $examCollection = $this->BaseModel->find($collection->id);

             if($request->hidden_chkReminder=="general" && isset($request->show_as_reminder) && $request->hd_exam_id==""){
                $reminderCollection = $this->ChannelsRemindersSettingModel->where('service_id',$collection->id)->where('type','service')->first();
                if(empty($reminderCollection))
                {
                    $reminderCollection =  new $this->ChannelsRemindersSettingModel;
                }
                else
                {
                    $old_collection =  $reminderCollection;
                }
                if(!empty($old_collection))
                {
                    $status = $this->_chnagesDone($old_collection,$request);
                    if($status == 1)
                    {
                        $reminderCollection->is_reminder_updated =1;
                    }                
                }else
                {
                    $reminderCollection->is_reminder_updated =0;
                }
                $notify_time =  $this->ChannelsRemindersSettingModel->where('type','global')->pluck('notify_time')->first();
                $reminderCollection->notify_time       = $notify_time; 
                $reminderCollection->activated_reminder  = 'general'; 
                $reminderCollection->general_period       = $request->hidden_general_period;
                $reminderCollection->general_period_frequency_type   = $request->hidden_general_period_frequency_type;
                $reminderCollection->general_new_frequency       = $request->hidden_general_new_frequency;
                $reminderCollection->general_new_frequency_type       = $request->hidden_general_new_frequency_type;
                $reminderCollection->general_first_frequency     = $request->hidden_general_first_frequency;
                $reminderCollection->general_first_frequency_type       = $request->hidden_general_first_frequency_type;
                $reminderCollection->general_time_interval       = $request->hidden_general_time_interval;
                $reminderCollection->general_time_interval_frequency_type   = $request->hidden_general_time_interval_frequency_type;
                $reminderCollection->general_number_of_interval  = $request->hidden_general_number_of_interval;
                $reminderCollection->general_end_cycle       = $request->hidden_general_end_cycle;
                $reminderCollection->general_end_cycle_frequency_type = $request->hidden_general_end_cycle_frequency_type;
                $reminderCollection->type       = 'service'; 
                $reminderCollection->service_id     =  $collection->id;
                $reminderCollection->recommanded_service_id    =  $request->hidden_reminder_service;                
                $reminderCollection->save();

                //commented on 29-sept-23
                /*if($reminderCollection){
                     //update show as control field to 0 for general reminder
                     $examCollection->show_as_control = 0;
                     $examCollection->save();
                }*/
                 


            }//if hidden checkup reminder is general

            if($request->hidden_chkReminder=="age" && isset($request->show_as_reminder) && $request->hd_exam_id=="")
            {
                $ageReminderCollection = $this->ChannelsRemindersSettingModel->where('service_id',$collection->id)->where('type','service')->first();
                if(empty($ageReminderCollection))
                {
                    $ageReminderCollection =  new $this->ChannelsRemindersSettingModel;
                }
                else
                {
                    $old_collection =  $ageReminderCollection;
                }
            
                if(!empty($old_collection))
                {
                    $status = $this->_chnagesDone($old_collection,$request);
                    if($status == 1)
                    {
                        $ageReminderCollection->is_reminder_updated =1;
                    }                
                }else
                {
                    $ageReminderCollection->is_reminder_updated =0;
                }

                $ageReminderCollection->activated_reminder       = 'age'; 
                
                $notify_time =  $this->ChannelsRemindersSettingModel->where('type','global')->pluck('notify_time')->first();
                $ageReminderCollection->notify_time       = $notify_time;
                $ageReminderCollection->age_period_controls       = $request->hidden_age_period_controls;
                $ageReminderCollection->age_period_frequency_type       = $request->hidden_age_period_frequency_type;
                $ageReminderCollection->age_new_frequency       = $request->hidden_age_new_frequency;
                $ageReminderCollection->age_new_frequency_type       = $request->hidden_age_new_frequency_type;
                $ageReminderCollection->age_first_frequency       = $request->hidden_age_first_frequency;
                $ageReminderCollection->age_first_frequency_type       = $request->hidden_age_first_frequency_type;
                $ageReminderCollection->age_time_interval       = $request->hidden_age_time_interval;
                $ageReminderCollection->age_time_interval_frequency_type       = $request->hidden_age_time_interval_frequency_type;
                $ageReminderCollection->age_number_of_interval       = $request->hidden_age_number_of_interval;
                $ageReminderCollection->age_from       = $request->hidden_age_age_from;
                $ageReminderCollection->age_to       = $request->hidden_age_age_to;
                $ageReminderCollection->age_end_cycle       = $request->hidden_age_end_cycle;
                $ageReminderCollection->age_end_cycle_frequency_type = $request->hidden_age_end_cycle_frequency_type;
                $ageReminderCollection->type       = 'service'; 
                $ageReminderCollection->service_id =  $collection->id;
                $ageReminderCollection->save();

                //commented on 29-sept-23
                /*if($ageReminderCollection){
                     //update show as control field to 0 for age reminder
                     $examCollection->show_as_control = 0;
                     $examCollection->save();
                }*/

            }//if hidden checkup reminder is age

            if($request->hidden_chkReminder=="checkup" && isset($request->show_as_reminder) && $request->hd_exam_id=="")
            {
                $checkupRemindercollection = $this->ChannelsRemindersSettingModel->where('service_id',$collection->id)->where('type','service')->first();
                if(empty($checkupRemindercollection))
                {
                    $checkupRemindercollection =  new $this->ChannelsRemindersSettingModel;
                }
                else
                {
                    $old_collection =  $checkupRemindercollection;
                }

                $notify_time =  $this->ChannelsRemindersSettingModel->where('type','global')->pluck('notify_time')->first();
                $checkupRemindercollection->notify_time    = $notify_time; 
                $checkupRemindercollection->activated_reminder    = 'checkup'; 
                $checkupRemindercollection->checkup_period_controls       = $request->hidden_checkup_period_controls;
                $checkupRemindercollection->checkup_period_frequency_type  = $request->hidden_checkup_period_frequency_type;
                $checkupRemindercollection->checkup_new_frequency   = $request->hidden_checkup_new_frequency;
                $checkupRemindercollection->checkup_new_frequency_type       = $request->hidden_checkup_new_frequency_type;
                $checkupRemindercollection->checkup_first_frequency   = $request->hidden_checkup_first_frequency;
                $checkupRemindercollection->checkup_first_frequency_type       = $request->hidden_checkup_first_frequency_type;
                $checkupRemindercollection->checkup_time_interval       = $request->hidden_checkup_time_interval;
                $checkupRemindercollection->checkup_time_interval_frequency_type  = $request->hidden_checkup_time_interval_frequency_type;
                $checkupRemindercollection->checkup_number_of_interval  = $request->hidden_checkup_number_of_interval;
                $checkupRemindercollection->checkup_end_cycle       = $request->hidden_checkup_end_cycle;
                $checkupRemindercollection->checkup_end_cycle_frequency_type = $request->hidden_checkup_end_cycle_frequency_type;
                $checkupRemindercollection->type       = 'service'; 
                $checkupRemindercollection->service_id =  $collection->id;
                $checkupRemindercollection->save();

                //commented on 29-sept-23
                /*if($checkupRemindercollection){
                     //update show as control field to 1 for checkup reminder
                     $examCollection->show_as_control = 1;
                     $examCollection->save();
                }*/

            }//if hidden checkup reminder is age
               
            //-------End code here-for reminder popup----added on 22-sept-23--------------------//




        }// if collection save
        return $collection;   
    }

    /*Check List view */
    public function checkList($encID)
    {
        // Default site settings
        $encID = base64_decode(base64_decode($encID));
        //$this->CheckListModel
        //$this->CheckListHasHeadingSectionModel
        $this->ModuleTitle              = __('admin.TITLE_CHECKLIST_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;

        // All examsdata
        $this->ViewData['examinations'] = $this->BaseModel->get();

        $this->ViewData['heading_section'] = __('admin.TITLE_CHECKLIST_HEADING_SECTION');
        $this->ViewData['question']        = __('admin.TITLE_CHECKLIST_QUESTION');


        // view file with data
        return view($this->ModuleView.'check_list', $this->ViewData);  
    }

    // CHeck List Show 
    public function getAllActivecheckList(Request $request)
    {
        $specilist_id = '';
        if(!empty(Session::get('specialist')))
        {
            $specilist_id       = Session::get("specialist");
            $specialist_details = $this->SpecialistModel->find($specilist_id);
        }
        if($request->exam_id == null)
        {
            $check_list = $this->CheckListModel
                        ->where('fk_specialist_id',$specilist_id)
                        ->where('type_of_checklist','performance')
                        ->get();;
            if(!empty($check_list) && sizeof($check_list)>0)
            {
                $list = '';

                foreach ($check_list as $key => $value) 
                {
                    // $list.='<ul>
                    //             <li>
                    //                 <input 
                    //                 type="checkbox" 
                    //                 class="form-check-input" 
                    //                 id="check_id_'.$value['id'].'"
                    //                 name="check_list[]" 
                    //                 value="'.$value['id'].'" 
                    //                 ><a target="_blank" class="action-icon" title="'.__('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION').'" href="'.url('admin/check-list/view/'.base64_encode(base64_encode($value['id']))) .'" >'.$value['check_list_name'].'</a>
                    //             </li>
                    //         </ul> ';

                    $list.='<div class="custom-control custom-checkbox">
                              <input class="custom-control-input" type="checkbox" name="check_list[]"  id="customCheckbox'.$value['id'].'" value="'.$value['id'].'">
                              <label for="customCheckbox'.$value['id'].'" class="custom-control-label"><a target="_blank"  class="action-icon" title="'.__('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION').'" href="'.url('admin/check-list/view/'.base64_encode(base64_encode($value['id']))) .'" >'.$value['check_list_name'].'</a></label>
                            </div>';
                }
            }
        }
        else
        {
            $specilist_id = '';
            if(!empty(Session::get('specialist')))
            {
                $specilist_id       = Session::get("specialist");
                $specialist_details = $this->SpecialistModel->find($specilist_id);
            }

            $check_list = $this->CheckListModel
                        ->where('fk_specialist_id',$specilist_id)
                        ->where('type_of_checklist','performance')
                        ->where('status',1)
                        ->get();
            if(!empty($check_list) && sizeof($check_list)>0)
            {
                $list = '';
                foreach ($check_list as $key => $value) 
                {
                    $getcheckListId = $this->ExaminationsHasMultipleCheckListModel
                                  ->where('fk_examinations_id',$request->exam_id)
                                  ->where('fk_check_list_id',$value['id'])
                                  ->get();
                    if(!empty($getcheckListId) && sizeof($getcheckListId)>0) 
                    {

                    }
                    else
                    {
                        $list.='<div class="custom-control custom-checkbox">
                              <input class="custom-control-input" type="checkbox" name="check_list[]"  id="customCheckbox'.$value['id'].'" value="'.$value['id'].'">
                              <label for="customCheckbox'.$value['id'].'" class="custom-control-label"><a target="_blank"  class="action-icon" title="'.__('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION').'" href="'.url('admin/check-list/view/'.base64_encode(base64_encode($value['id']))) .'" >'.$value['check_list_name'].'</a></label>
                            </div>';
                    }
                }
            }
        }
        return $list;
    }

    // Document list show
    public function getAllActiveDocumentList(Request $request)
    {
        $specilist_id = '';
        if(!empty(Session::get('specialist')))
        {
            $specilist_id       = Session::get("specialist");
            $specialist_details = $this->SpecialistModel->find($specilist_id);
        }
        if($request->exam_id == null)
        {
            $document_list = $this->SpecialistDocumentsModel
                             ->where('fk_specialist_id',$specilist_id)
                             ->where('type_of_document','service')
                             ->get();
                             
            if(!empty($document_list) && sizeof($document_list)>0)
            {
                //dd($document_list);
                $str = '';

                foreach ($document_list as $key => $value) 
                {
                    $str.='<div class="custom-control custom-checkbox">
                              <input class="custom-control-input" type="checkbox" name="document_list[]"  id="customDocumentbox'.$value['id'].'" value="'.$value['id'].'">
                              <label for="customDocumentbox'.$value['id'].'" class="custom-control-label"><a target="_blank"  class="action-icon" title="'.__('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION').'" href="'.url('admin/specialist/documentsView/'.base64_encode(base64_encode($value['id']))) .'" >'.$value['name'].'</a></label>
                            </div>';
                }
            }
        }
        else
        {
            $document_list = $this->SpecialistDocumentsModel
                            ->where('fk_specialist_id',$specilist_id)
                             ->where('type_of_document','service')
                            ->get();
            if(!empty($document_list) && sizeof($document_list)>0)
            {
                $str = '';
                foreach ($document_list as $key => $value) 
                {
                    $getDocumentListId = $this->ExaminationsHasMultipleDocumentListModel
                                  ->where('fk_examinations_id',$request->exam_id)
                                  ->where('fk_document_list_id',$value['id'])
                                  ->get();

                    if(!empty($getDocumentListId) && sizeof($getDocumentListId)>0) 
                    {

                    }
                    else
                    {
                        $str.='<div class="custom-control custom-checkbox">
                              <input class="custom-control-input" type="checkbox" name="document_list[]"  id="customDocumentbox'.$value['id'].'" value="'.$value['id'].'">
                              <label for="customDocumentbox'.$value['id'].'" class="custom-control-label"><a target="_blank"  class="action-icon" title="'.__('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION').'" href="'.url('admin/specialist/documentsView/'.base64_encode(base64_encode($value['id']))) .'" >'.$value['name'].'</a></label>
                            </div>';
                    }
                }
            }
        }
        return $str;
    }
    public function _chnagesAgeDone($old_array,$new_array)
    {
        if(!empty($old_array) && !empty($new_array))
        {
            $change = 0;

            if($old_array->activated_reminder != 'age')
            {
                 $change = 1;
            }
            if($old_array->age_from !=$new_array->age_age_from )
            {
                 $change = 1;
            }

            if($old_array->age_to !=$new_array->age_age_to )
            {
                 $change = 1;
            }
            if($old_array->age_period_controls !=$new_array->age_period_controls )
            {
                 $change = 1;
            }
            if($old_array->age_period_frequency_type !=$new_array->age_period_frequency_type )
            {
                 $change = 1;
            }
            if($old_array->age_new_frequency !=$new_array->age_new_frequency )
            {


                 $change = 1;
            }

            if($old_array->age_new_frequency_type !=$new_array->age_new_frequency_type )
            {
                 $change = 1;
            }
            if($old_array->age_first_frequency !=$new_array->age_first_frequency )
            {
                 $change = 1;
            }

            if($old_array->age_first_frequency_type !=$new_array->age_first_frequency_type )
            {
                 $change = 1;
            }

            if($old_array->age_time_interval !=$new_array->age_time_interval )
            {
                 $change = 1;
            }
            if($old_array->age_time_interval_frequency_type !=$new_array->age_time_interval_frequency_type )
            {
                 $change = 1;
            }

            if($old_array->age_number_of_interval !=$new_array->age_number_of_interval )
            {
                 $change = 1;
            }

            if($old_array->age_end_cycle !=$new_array->age_end_cycle )
            {
                 $change = 1;
            }

            if($old_array->age_end_cycle_frequency_type !=$new_array->age_end_cycle_frequency_type )
            {
                 $change = 1;
            }

           return $change;
        }
    }

    public function _chnagesDone($old_array,$new_array)
    {

        if(!empty($old_array) && !empty($new_array))
        {
            $change = 0;

           
            if($old_array->activated_reminder != 'general')
            {
                 $change = 1;
            }

            if($old_array->general_period !=$new_array->general_period )
            {
                 $change = 1;
            }

            if($old_array->general_period_frequency_type !=$new_array->general_period_frequency_type )
            {
                 $change = 1;
            }
            if($old_array->general_new_frequency !=$new_array->general_new_frequency )
            {
                 $change = 1;
            }
            if($old_array->general_new_frequency_type !=$new_array->general_new_frequency_type )
            {
                 $change = 1;
            }
            if($old_array->general_first_frequency !=$new_array->general_first_frequency )
            {


                 $change = 1;
            }

            if($old_array->general_first_frequency_type !=$new_array->general_first_frequency_type )
            {
                 $change = 1;
            }
            if($old_array->general_time_interval !=$new_array->general_time_interval )
            {
                 $change = 1;
            }

            if($old_array->general_time_interval_frequency_type !=$new_array->general_time_interval_frequency_type )
            {
                 $change = 1;
            }

            if($old_array->general_number_of_interval !=$new_array->general_number_of_interval )
            {
                 $change = 1;
            }         
            if($old_array->general_end_cycle !=$new_array->general_end_cycle )
            {
                 $change = 1;
            }

            if($old_array->general_end_cycle_frequency_type !=$new_array->general_end_cycle_frequency_type )
            {
                 $change = 1;
            }   

            return $change;
        }
    }

    public function sortOrderaction(Request $request){
       //echo "here";echo "<pre>";print_r($request->rows);exit;
       foreach($request->input('rows', []) as $row)
        {
            $BaseModel = $this->BaseModel->find($row['id']);
            $BaseModel->sorting_order =$row['sorting_order'];
            $BaseModel->save();
        }
        $this->JsonData['status']   = 'success';
        //$this->JsonData['msg']      = __('admin.MAIL_STATUS_SUCCESS');
        return response()->json($this->JsonData);
    }
    
}
