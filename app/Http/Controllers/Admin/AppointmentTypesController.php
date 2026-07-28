<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;

// Models
use App\Models\AppointmentTypesModel;
use App\Models\AppointmentModel;
use App\Models\ActivityLogModel; 
use App\Models\ExaminationsModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\SpecialistModel;
use App\Models\SettingsModel;
use App\Models\AppointmentTypeHasNonExaminationsModel;

// Request
use App\Http\Requests\Admin\AppointmentTypesRequest;

// plugins
use App\Traits\GeneralTrait;
use Hash;
use Mail;
use DB;
use Auth;
use Storage;
use File;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

class AppointmentTypesController extends Controller
{
    use GeneralTrait;
    private $BaseModel;

    public function __construct(
        AppointmentTypesModel $AppointmentTypesModel, 
        AppointmentModel $AppointmentModel,
        ActivityLogModel $ActivityLogModel,
        ExaminationsModel $ExaminationsModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        SpecialistModel $SpecialistModel,
        SettingsModel $SettingsModel,
        AppointmentTypeHasNonExaminationsModel $AppointmentTypeHasNonExaminationsModel
    )
    {
        $this->BaseModel         = $AppointmentTypesModel;
        $this->AppointmentModel            = $AppointmentModel;
        $this->ActivityLogModel  = $ActivityLogModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->SpecialistModel = $SpecialistModel;
        $this->SettingsModel=$SettingsModel;
        $this->AppointmentTypeHasNonExaminationsModel = $AppointmentTypeHasNonExaminationsModel;

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle  = __('admin.TITLE_APPOINTMENT_TYPE_TEXT'); 
        $this->ModuleView   = 'admin.apointment-types.';
        $this->ModulePath   = 'admin.apointment-types.';

        // Permission Middleware
        $this->middleware(['permission:appointment-types-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:appointment-types-add'], ['only' => ['create','store']]);
    }

    public function index()
    {

        
     
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_APPOINTMENT_TYPE_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');
        

       
        $this->ViewData['specialist_details']= self::__GetSecialits();;

        $this->ViewData['specialists']       = $this->SpecialistModel->get();

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }

    public function create() 
    {
        //dd(Session::get('specialist'));
        if(!empty(Session::get('specialist')) && Session::get('specialist') !='all')
        {
            // Default site settings
            $this->ModuleTitle              = __('admin.TITLE_APPOINTMENT_TYPE_TEXT'); 
            $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
            $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

            $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT'); 

            $this->ViewData['modulePath']   = $this->ModulePath;
            $this->ViewData['exams']        = $this->ExaminationsModel->where('fk_specialist_id',Session::get('specialist'))->get();
            $this->ViewData['specialist_id']  = Session::get('specialist');
            $this->ViewData['specialists']       = $this->SpecialistModel->get();
            $specialist_details = $this->SpecialistModel->find(Session::get('specialist'));
            $this->ViewData['specialist_details']= $specialist_details;
            $durationSetting = $this->SettingsModel
                            ->where('setting_key', 'TIME_SLOTS_DURATION')
                            ->whereStatus(1)
                            ->first(['setting_value']);
            $duraionArray=array(10,20,30,40,50,60);
            if($durationSetting) array_push($duraionArray,$durationSetting->setting_value);
            $duraionArray2=array_unique($duraionArray);
            sort($duraionArray2);
            $this->ViewData['duraionArray']= $duraionArray2;
            $this->ViewData['selected_examinations'] = [];
            // view file with data
            return view($this->ModuleView.'create', $this->ViewData);
        }
        else
        {
            return redirect('/admin/apointment-types')
                    ->with('error' ,  __('admin.TITLE_ERR_SPECIALIST'))
                    ->withInput();;
        }
        
    }

    public function store(AppointmentTypesRequest $request)
    {
        //dd($request->all());
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TYPE_CREATE');

        $selected_exam_list = [];
        if($request->selected_exam_list){
            $selected_exam_list = explode(",", $request->selected_exam_list);
        }
        // added by vijay 12/3/2024
        $selected_non_exam_list = [];
        if ($request->selected_non_exam_list) {
            $selected_non_exam_list = explode(",", $request->selected_non_exam_list);
        }
        try {

            $collection     = new $this->BaseModel;   
            $request->add   = 1;
            $maxSortOrder = $this->BaseModel->max('sorting_order'); 
            $request->sorting_order=$maxSortOrder+1; 

            $collection     = self::_storeOrUpdate($collection,$request);
            if ($collection) 
            {
                //start added code on 9-may-24 for default service of app type
                $sericeMaxSortOrder = $this->ExaminationsModel->max('sorting_order'); 
                $sericeSortingOrder = $sericeMaxSortOrder+1;
                //end added code on 9-may-24 for default service of app type


                // DEFAULT CREATE EXAMINATION
                $ExaminationsModel = new $this->ExaminationsModel;
                $ExaminationsModel->name = $request->name;
                $ExaminationsModel->url = $request->url;
                $ExaminationsModel->fk_specialist_id = $request->specialist_id;
                $ExaminationsModel->status = 1;
                $ExaminationsModel->default_service = 1;

                //start added code on 9-may-24 for default service of app type
                $ExaminationsModel->sorting_order = $sericeSortingOrder;   
                //end added code on 9-may-24 for default service of app type

                if($ExaminationsModel->save())
                {
                    $AppointmentHasExaminationsModel = new $this->AppointmentTypeHasExaminationsModel;
                    $AppointmentHasExaminationsModel->appoinment_id    = $collection->id;
                    $AppointmentHasExaminationsModel->examination_id   = $ExaminationsModel->id;
                    $AppointmentHasExaminationsModel->fk_specialist_id = $request->specialist_id;
                    $AppointmentHasExaminationsModel->save();
                }

                //INDIVIUALI ADD EXAMINATION
                $all_transactions = [];
                if (!empty($selected_exam_list)) 
                {                    
                    foreach ($selected_exam_list as $exam) 
                    {
                        $examinationObj = new $this->AppointmentTypeHasExaminationsModel;
                        $examinationObj->appoinment_id   = $collection->id;;
                        $examinationObj->examination_id   = $exam;
                        $examinationObj->fk_specialist_id = $request->specialist_id;
                        
                        if ($examinationObj->save()) 
                        {                            
                            $all_transactions[] = 1;
                        }
                        else
                        {
                            $all_transactions[] = 0;
                        }
                    }                        
                }
                // added by vijay 12/3/2024
                if (!empty ($selected_non_exam_list)) {
                    foreach ($selected_non_exam_list as $nonExam) {
                        $nonExaminationObj = new $this->AppointmentTypeHasNonExaminationsModel;
                        $nonExaminationObj->appointment_type_id = $collection->id;
                        $nonExaminationObj->examination_id = $nonExam;
                        $nonExaminationObj->fk_specialist_id = $request->specialist_id;
                        $nonExaminationObj->save();

                    }
                }
                // Examination End
                $newData = $collection->toArray();
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has created appointment type','Add',null,$newData);
                DB::commit();

                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'index');
                $this->JsonData['msg']    = __('admin.APPOINTMENT_TYPE_CREATED');
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
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_APPOINTMENT_TYPE_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        $specialist_details = $this->SpecialistModel->find(Session::get('specialist'));
            $this->ViewData['specialist_details']= $specialist_details;

        // All userdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['appointment'] = $this->BaseModel->find($id);

        $appoinment_type = $this->BaseModel
                            ->with(['hasAppointmentExaminations'=>function($query){
                                    $query->with(['assignedExamination']);
                                }])
                            ->find($id);

       
        $assigned_exam_ids = [];
        if(!empty($appoinment_type->hasAppointmentExaminations)){
            $assigned_exam_ids = array_column($appoinment_type->hasAppointmentExaminations->toArray(), "examination_id");
        }

        // added by vijay 12/3/24
        $appoinment_type_non_examination = $this->BaseModel
            ->with([
                'hasAppointmentNonExaminations' => function ($query) {
                    $query->with(['assignedExamination']);
                }
            ])
            ->find($id);
        $assigned_non_exam_ids = [];
        if (!empty ($appoinment_type->hasAppointmentNonExaminations)) {
            $assigned_non_exam_ids = array_column($appoinment_type->hasAppointmentNonExaminations->toArray(), "examination_id");
        }

        $this->ViewData['assigned_non_exam_ids'] = $assigned_non_exam_ids;
        //
        $this->ViewData['defaultExaminationID'] = $assigned_exam_ids[0] ?? '';

        // $this->ViewData['exams']     = $this->ExaminationsModel
        //                                ->orderBy('id','DESC')
        //                                ->get();

        $this->ViewData['exams']  = $this->ExaminationsModel
                                    ->get();


        $this->ViewData['assigned_exam_ids'] = $assigned_exam_ids;
        $this->ViewData['specialist_id']  = Session::get('specialist');
        $durationSetting = $this->SettingsModel
                        ->where('setting_key', 'TIME_SLOTS_DURATION')
                        ->whereStatus(1)
                        ->first(['setting_value']);
        $duraionArray=array(10,20,30,40,50,60);
        if($durationSetting) array_push($duraionArray,$durationSetting->setting_value);
        $duraionArray2=array_unique($duraionArray);
        sort($duraionArray2);
        $this->ViewData['duraionArray']= $duraionArray2;
        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function update(AppointmentTypesRequest $request, $encID)
    {
        //dd($request->all());

        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TYPE_UPDATE');        
        
        $selected_exam_list = [];
        if($request->selected_exam_list){
            $selected_exam_list = explode(",", $request->selected_exam_list);
        }

        // added by vijay 12/3/24
        $selected_non_exam_list = [];
        if ($request->selected_non_exam_list) {
            $selected_non_exam_list = explode(",", $request->selected_non_exam_list);
        }
        // 
        try {

            $collection = $this->BaseModel->find($id); 
            $oldData = $collection->toArray();

            $collection = self::_storeOrUpdate($collection,$request);
            $newData = $collection->toArray();
            if ($collection) 
            {
                $getService = $this->ExaminationsModel
                              ->where('default_service',1)
                              ->where('name',$collection->name)
                              ->first();
                if(!empty($getService))
                {
                    $getService->status = $collection->status;
                    $getService->save();
                }
                $all_transactions = [];
                ## ADD PRODUCTION RAW MATERIAL DATA
                if (!empty($selected_exam_list))  
                {  
                    //Delete records
                    //except default  examination id ,delete all examination.
                    $getrec = $this->AppointmentTypeHasExaminationsModel
                             ->where('appoinment_id',$collection->id)
                             ->where('examination_id','!=', $request->defaultExaminationID)->delete();
                    
                    foreach ($selected_exam_list as $pkey => $exam) 
                    {
                        //check default examination id ,if is not default examination then insert
                        if($request->defaultExaminationID != $exam)
                        {
                            $examinationObj = new $this->AppointmentTypeHasExaminationsModel;
                            $examinationObj->appoinment_id   = $collection->id;
                            $examinationObj->examination_id   = $exam;
                            $examinationObj->fk_specialist_id = $request->specialist_id;
                            if ($examinationObj->save()) 
                            {                            
                                $all_transactions[] = 1;
                            }
                            else
                            {
                                $all_transactions[] = 0;
                            }
                        }
                    } 
                }

                // added by vijay 12/3/24
                $deleteNonExemption = $this->AppointmentTypeHasNonExaminationsModel
                    ->where('appointment_type_id', $collection->id)->delete();
                    if (!empty ($selected_non_exam_list)) {
                        foreach ($selected_non_exam_list as $pkey => $nonExam) {
                            //check default examination id ,if is not default examination then insert
                            if ($request->defaultExaminationID != $nonExam) {
                                $nonExaminationObj = new $this->AppointmentTypeHasNonExaminationsModel;
                                $nonExaminationObj->appointment_type_id = $collection->id;
                                $nonExaminationObj->examination_id = $nonExam;
                                $nonExaminationObj->fk_specialist_id = $request->specialist_id;
                                $nonExaminationObj->save();

                            }
                    }
                }
                // 
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated appointment type','Update',$oldData,$newData);

                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'index');
                $this->JsonData['msg']    = __('admin.APPOINTMENT_TYPE_UPDATED');
            }
            
        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function destroy($encID)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TYPE_DELETE');   
        $id = base64_decode(base64_decode($encID));

        // $appoint_has_appointment_type = $this->AppointmentModel
        //                                 ->where('appointment_type_id', $id)
        //                                 ->get(['id'])
        //                                 ->count();

        // if($appoint_has_appointment_type>0){
        //     $this->JsonData['status'] = __('admin.RESP_ERROR');
        //     $this->JsonData['msg'] =  __('admin.FAIL_APPOINTMENT_TYPE_DELETE_ASSIGNED');
        // }else{
            $collection = $this->BaseModel->find($id);
            $getService = $this->ExaminationsModel
                          ->where('name',$collection->name)
                          ->where('default_service',1)
                          ->first();

            if($collection->delete()){
                if(isset($getService))
                {
                    $service_id = $getService->id;
                    $deleteService = $this->ExaminationsModel->find($service_id);
                    if($deleteService->delete()){
                    $newData = $collection->toArray();
                    $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData);
                    $this->JsonData['status'] = 'success';
                    $this->JsonData['msg']    = __('admin.APPOINTMENT_TYPE_DELETED');
                }
                }
            // added by vijay 14/3/24
            $deleteNonServices = $this->AppointmentTypeHasNonExaminationsModel->where('appointment_type_id', $id)->delete();
                
            }
        //}        

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
                1 => 'appointment_types.sorting_order',
                2 => 'appointment_types.name',
                3 => 'appointment_types.duration',
                4 => 'appointment_types.status',
                5 => 'appointment_types.duration',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
        if($request->specialist_id !='all')
        {
            $modelQuery =  $this->BaseModel
                           ->where('fk_specialist_id',$request->specialist_id);
        }
        else
        {
            $modelQuery =  $this->BaseModel;
        }
            

            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

            ## FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {
                if (!empty($request->custom['name'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['name'];                
                    $modelQuery     = $modelQuery
                    ->where('appointment_types.name','LIKE','%'.$key.'%');
                }

                if (isset($request->custom['duration'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['duration'];
                    $modelQuery     = $modelQuery
                    ->where('appointment_types.duration', $key);
                }

                if (isset($request->custom['status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('appointment_types.status', $key);
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
                        $query->orwhere('appointment_types.name', 'LIKE', "%".$search."%");   
                        $query->orwhere('appointment_types.duration', 'LIKE', '%'.$search.'%');
                        // if(strtolower($search)=="active"){
                        //     $query->orwhere('appointment_types.status', '=', 1);
                        // }
                        // else{
                        //     $query->orwhere('appointment_types.status', '=', 0);
                        // }      
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
                                 ->get();            
   
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row) 
                {

                        $data[$key]['id']       = $row->id;
                        $data[$key]['sorting_order']   = $row->sorting_order;
                        $data[$key]['name']     = '<span title="'.ucfirst($row->name).'">'.ucfirst($row->name).'</span>';
                        $data[$key]['duration'] = '<span title="'.$row->duration.'">'.$row->duration.'</span>';  

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
                        if(auth()->user()->can('appointment-types-add')){
                            $edit = '<a href="'.route($this->ModulePath.'edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                            $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route($this->ModulePath.'destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>';
                        }

                        $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';
                    
                }
            }

             ## SEARCH HTML
            $searchHTML['id']       =  '';   
            $searchHTML['sorting_order'] = '';
            $searchHTML['name']     =  '<input type="text" class="form-control" id="name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['duration'] =  ''; 
            $searchHTML['status']   =  '<select name="status" id="appointment_status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_EXAMINATION_STATUS').'</option>
                    <option class="theme-black blue-select" value="1" '.( !empty($request->custom['status']) && $request->custom['status'] == "1" ? 'selected' : '').' >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</option>
                    <option class="theme-black blue-select" value="0" '.( !empty($request->custom['status']) && $request->custom['status'] == "0" ? 'selected' : '').'>'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</option>            
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
 
    public function _storeOrUpdate($collection, $request)
    {
        //dd($request->specialist_id);
        $collection->fk_specialist_id = $request->specialist_id;
        $collection->name            = $request->name;
        $collection->duration        = $request->duration;
        $collection->description     = $request->description;
        $collection->recommend_exams = !empty($request->recommend_exams)?1:0;
        $collection->status          = !empty($request->status)?1:0;
        // $collection->patient_document_status  = !empty($request->patient_document_status)?$request->patient_document_status:0;
        $collection->on_dashboard  = !empty($request->dashboard_setting)? $request->dashboard_setting:'0';
        // added vy vijay
        $collection->optimal_appointment = !empty ($request->optimal_appointment) ? 1 : 0;
        if($request->add==1){
            $collection->sorting_order   = $request->sorting_order;   
        }
        // if (!empty($request->patient_document)) 
        // {
        //     $path = 'appointment-type';

        //     $objDocument = $request->patient_document;

        //     $original_file  = $objDocument->getClientOriginalName();
        //     $extension      = strtolower($objDocument->getClientOriginalExtension());

        //     $filename       = date('YmdHis').'-'.$original_file;
        //     $file           = Storage::putFileAs($path, $objDocument, $filename);

        //     $filePath       = "/app/appointment-type/".$filename;
        //     // dd($filename,$path);

        //     $collection->patient_document      = $original_file;
        //     $collection->patient_document_path = $filePath;

        //     if(is_file(storage_path().$request->old_doc_data))
        //     {
        //         unlink(storage_path().$request->old_doc_data);
        //     }  

        // }else if(empty($request->old_file) && !empty($request->old_doc_data)) 
        // {
        //     $collection->patient_document = NULL;
        //     $collection->patient_document_path = NULL;
            
        //     if(is_file(storage_path().$request->old_doc_data))
        //     {
        //         unlink(storage_path().$request->old_doc_data);
        //     }
        // }

        //Save data
        $collection->save();

        return $collection;       
    }

    public function sortOrderaction(Request $request){
        foreach($request->input('rows', []) as $row)
         {
             $BaseModel = $this->BaseModel->find($row['id']);
             $BaseModel->sorting_order =$row['sorting_order'];
             $BaseModel->save();
         }
         $this->JsonData['status']   = 'success';
         return response()->json($this->JsonData);
     }

}
