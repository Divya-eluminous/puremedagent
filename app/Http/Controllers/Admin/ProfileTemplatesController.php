<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;  

// Models
use App\Models\ProfilesTemplatesModel; 
use App\Models\ExaminationsModel;
use App\Models\ProfileHasExaminationsModel;  
use App\Models\ActivityLogModel;

// Request
use App\Http\Requests\Admin\ProfileTemplatesRequest;  
use App\Traits\GeneralTrait;

// plugins
use DB;
use Auth; 
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

class ProfileTemplatesController extends Controller
{
    use GeneralTrait;
    private $BaseModel;

    public function __construct(
        ProfilesTemplatesModel $ProfilesTemplatesModel,
        ExaminationsModel $ExaminationsModel,
        ProfileHasExaminationsModel $ProfileHasExaminationsModel,
        ActivityLogModel $ActivityLogModel
    )
    {
        $this->BaseModel            = $ProfilesTemplatesModel;
        $this->ExaminationsModel    = $ExaminationsModel;
        $this->ProfileHasExaminationsModel = $ProfileHasExaminationsModel ;
        $this->ActivityLogModel     = $ActivityLogModel;

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle = __('admin.TITLE_PROFILE_TEMPLATE_TEXT');
        $this->ModuleView  = 'admin.profile-templates.';
        $this->ModulePath  = 'admin.profile-templates'; 

        // Permission Middleware
        $this->middleware(['permission:profile-templates-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:profile-templates-add'], ['only' => ['create','store']]);  
    }

    public function index()
    {
        // Default site settings        
        $this->moduleTitle              = __('admin.TITLE_PROFILE_TEMPLATE_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData); 
    }

    public function create() 
    {
        // Default site settings
        $this->moduleTitle              =__('admin.TITLE_PROFILE_TEMPLATE_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All examinations
        $this->ViewData['exams']        = $this->ExaminationsModel->get();

        // view file with data
        return view($this->ModuleView.'create', $this->ViewData);   
    } 

    public function store(ProfileTemplatesRequest $request) 
    {   

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_PROFILE_CREATE');

        $msg = self::_validateProfile($request);
        if(!empty($msg)){
            $this->JsonData['msg'] = $msg; 
            return response()->json($this->JsonData);
            exit();
        } 

        $selected_exam_list = [];
        if($request->selected_exam_list){
            $selected_exam_list = explode(",", $request->selected_exam_list);
        }
        // dd($selected_exam_list);
        try {  

            DB::beginTransaction();   

            $collection = new $this->BaseModel;   
            $collection = self::_storeOrUpdate($collection,$request);

            if($collection)
            {
                // dd($collection);
                $newData['name'] = $collection->name;
                $newData['age_from'] = $collection->age_from;
                $newData['age_to'] = $collection->age_to;
                $newData['status'] = $collection->status;
                $newData['examination_ids'] = implode(",",$selected_exam_list);
                $newData['created_at'] = date("Y-m-d H:i:s",strtotime($collection->created_at));
                $newData['updated_at'] = date("Y-m-d H:i:s",strtotime($collection->updated_at));
                $newData['id'] = $collection->id;
                // dd($newData);
                // $newData = $collection->toArray();
                $all_transactions = [];
                if (!empty($selected_exam_list)) 
                {                    
                    foreach ($selected_exam_list as $exam) 
                    {
                        $examinationProfileObj = new $this->ProfileHasExaminationsModel;
                        $examinationProfileObj->examination_id   = $exam;
                        $examinationProfileObj->profile_id   = $collection->id;
                        
                        if ($examinationProfileObj->save()) 
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

            if (!in_array(0,$all_transactions)) 
            {
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    = route($this->ModulePath.'.index');
                $this->JsonData['msg']    = __('admin.PROFILE_CREATED'); 
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has created profile template','Add',null,$newData); 
                DB::commit();
            }else
            {
                DB::rollback();
                $this->JsonData['error_msg'] = $e->getMessage();
            }
        }
        catch(\Exception $e) {
            DB::rollback();
            $this->JsonData['error_msg'] = $e->getMessage();
            $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
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
        $this->ModuleTitle              =__('admin.TITLE_PROFILE_TEMPLATE_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        $id = base64_decode(base64_decode($encID)); 
        $profiles_template = $this->BaseModel
                                ->with(['hasProfileExaminations'=>function($query){
                                    $query->with(['assignedProfile','assignedExamination']);
                                }])
                                ->find($id);

        $assigned_exam_ids = [];
        if(!empty($profiles_template->hasProfileExaminations)){
            $assigned_exam_ids = array_column($profiles_template->hasProfileExaminations->toArray(), "examination_id");
        }                                
        
        // All profile data
        $this->ViewData['templates'] = $profiles_template; 
        $this->ViewData['exams']     = $this->ExaminationsModel->get();
        $this->ViewData['assigned_exam_ids'] = $assigned_exam_ids;
     
        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function update(ProfileTemplatesRequest $request, $encID)
    {
       // dd($request->all());die;
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_PROFILE_UPDATE');        

        $id = base64_decode(base64_decode($encID));
        $msg = self::_validateProfile($request,$id);
            
        if(!empty($msg)){
            $this->JsonData['msg'] = $msg; 
            return response()->json($this->JsonData);
            exit();
        } 

        $selected_exam_list = [];
        if($request->selected_exam_list){
            $selected_exam_list = explode(",", $request->selected_exam_list);
        }
        
        try {     

            DB::beginTransaction();     

            $collection = $this->BaseModel->find($id);
            // dd($collection);
            $oldData['id'] = $collection->id;
            $oldData['name'] = $collection->name;
            $oldData['age_from'] = $collection->age_from;
            $oldData['age_to'] = $collection->age_to;
            $oldData['status'] = $collection->status;
            $oldData['examination_ids'] = implode(",",$selected_exam_list);
            $oldData['created_at'] = date("Y-m-d H:i:s",strtotime($collection->created_at));
            $oldData['updated_at'] = date("Y-m-d H:i:s",strtotime($collection->updated_at));
            // dd($oldData);
            // $oldData = $collection->toArray();   

            $collection = self::_storeOrUpdate($collection,$request);
            // $newData = $collection->toArray();
            $newData['id'] = $collection->id;
            $newData['name'] = $collection->name;
            $newData['age_from'] = $collection->age_from;
            $newData['age_to'] = $collection->age_to;
            $newData['status'] = $collection->status;
            $newData['examination_ids'] = implode(",",$selected_exam_list);
            $newData['created_at'] = date("Y-m-d H:i:s",strtotime($collection->created_at));
            $newData['updated_at'] = date("Y-m-d H:i:s",strtotime($collection->updated_at));
            

            if($collection)
            {
                $all_transactions = [];
                ## ADD PRODUCTION RAW MATERIAL DATA
                if (!empty($selected_exam_list))  
                {   
                    //Delete records
                    $this->ProfileHasExaminationsModel->where('profile_id', $collection->id)->delete();
                    foreach ($selected_exam_list as $pkey => $exam) 
                    {

                            $examinationProfileObj = new $this->ProfileHasExaminationsModel;
                            $examinationProfileObj->profile_id   = $collection->id;
                            $examinationProfileObj->examination_id   = $exam;
                            if ($examinationProfileObj->save())  
                            {                                                           
                                $all_transactions[] = 1;
                            }
                            else
                            {
                                $all_transactions[] = 0;
                            }
                    } 
                }
            }//ifclose

            if (!in_array(0,$all_transactions)) 
            {   
    
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated profile template','Update',$oldData,$newData);

                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    = route($this->ModulePath.'.index');
                $this->JsonData['msg']    = __('admin.PROFILE_UPDATED'); 
       
                DB::commit();
            }else
            {
                DB::rollback();
                $this->JsonData['error_msg'] = $e->getMessage();
            }

        }
        catch(\Exception $e) {
            DB::rollback();
            $this->JsonData['error_msg'] = $e->getMessage();
            $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
        }

        return response()->json($this->JsonData);
    }

    public function _validateProfile($request,$id=false)
    {
        $msg = '';
        $request_age_from = $request->age_from;
        $request_age_to   =  $request->age_to;

        if($request_age_to < $request_age_from){
            return $msg =  __('admin.ERR_AGE_TO');
            exit();
        } 

        $selected_exam_list = [];
        if($request->selected_exam_list){
            $selected_exam_list = explode(",", $request->selected_exam_list);
        }

        if(empty($request->selected_exam_list)) 
        {
            return $msg =  __('admin.ERR_SELECT_EXAM');
            exit();
        }

       //DB::enableQueryLog();
        $age_range = range($request_age_from,$request_age_to);
        $ageOverlaps  = $this->BaseModel
                            ->where(function ($query)use ($age_range,$id) {
                                    if($id!=false){
                                        $query->where('id','!=',$id);
                                    }
                                $query->whereIn('age_from', $age_range);
                             })
                            ->orWhere(function($query)use ($age_range,$id) {
                                    if($id!=false){
                                        $query->where('id','!=',$id);
                                    }
                                    $query->whereIn('age_to', $age_range);
                                    
                                })
                            ->orWhere(function ($query)use ($request_age_from,$request_age_to,$id) {
                                    if($id!=false){
                                        $query->where('id','!=',$id);
                                    }
                                    $query->where('age_from','<=',$request_age_from);
                                    $query->where('age_to','>=',$request_age_to);
                                })
                            ->first();
            
       // dd($age_range,$ageOverlaps,$selected_exam_list);

        if (!empty($ageOverlaps)) {// && sizeof($ageOverlaps)>0

            $msg =  $ageOverlaps->name.' is already added from age group '.$ageOverlaps->age_from." to ".$ageOverlaps->age_to;
            
        }

       // dd($msg);

        return $msg;
        exit();
    }

    //Not in use due to different case
    public function _validateProfileWithExaminationValidation($request,$id=false)
    {
        $msg = '';
        $request_age_from = $request->age_from;
        $request_age_to   =  $request->age_to;

        if($request_age_to < $request_age_from){
            return $msg =  __('admin.ERR_AGE_TO');
            exit();
        } 

        $selected_exam_list = [];
        if($request->selected_exam_list){
            $selected_exam_list = explode(",", $request->selected_exam_list);
        }

        if(empty($request->selected_exam_list)) 
        {
            return $msg =  __('admin.ERR_SELECT_EXAM');
            exit();
        }

       //DB::enableQueryLog();
        $age_range = range($request_age_from,$request_age_to);
        $ageOverlaps  = $this->BaseModel
                            ->with(['hasProfileExaminations','hasProfileExaminations.assignedExamination'])
                            ->where(function ($query)use ($age_range,$id) {
                                    if($id!=false){
                                        $query->where('id','!=',$id);
                                    }
                                $query->whereIn('age_from', $age_range);
                             })
                            ->orWhere(function($query)use ($age_range,$id) {
                                    if($id!=false){
                                        $query->where('id','!=',$id);
                                    }
                                    $query->whereIn('age_to', $age_range);
                                    
                                })
                            ->orWhere(function ($query)use ($request_age_from,$request_age_to,$id) {
                                    if($id!=false){
                                        $query->where('id','!=',$id);
                                    }
                                    $query->where('age_from','<=',$request_age_from);
                                    $query->where('age_to','>=',$request_age_to);
                                })
                            ->get();
                            // ->whereBetween('age', [$ageFrom, $ageTo]);
                            // ->whereHas('hasProfileExaminations',function($q)use ($selected_exam_list){
                            //     $q->whereIn('examination_id', $selected_exam_list);
                            // })
                            // ->whereIn('examination_id', $selected_exam_list)
        // $ageOverlaps  = $ageOverlaps ->whereIn('examination_id', $selected_exam_list);
        // $ageOverlaps  = $ageOverlaps ->first();
       //dump(DB::getQueryLog());

       // dd($age_range,$ageOverlaps,$selected_exam_list);

        if (!empty($ageOverlaps) && sizeof($ageOverlaps)>0) {
            
            $msg = '';
            foreach ($ageOverlaps as $ageQuery) 
            {
                $profile_has_examList = '';
                if(!empty($ageQuery->hasProfileExaminations) && sizeof($ageQuery->hasProfileExaminations)>0)
                {
                    foreach ($ageQuery->hasProfileExaminations as $hasProfileExaminations) {
                        foreach ($selected_exam_list as $examIds) {
                            if($examIds==$hasProfileExaminations->examination_id){
                                if(!empty($profile_has_examList)){
                                    $profile_has_examList .= ',';
                                }
                                $profile_has_examList .= $hasProfileExaminations->assignedExamination->name;
                            }
                        }

                    }

                }
                // $hasExamIds = array_column($ageQuery->hasProfileExaminations->toArray(), "examination_id");
                //dd($profile_has_examList);
                // dd($ageQuery->hasProfileExaminations,$hasExamIds);                        
                
                if(!empty($profile_has_examList)){

                    $msg .=  $ageQuery->name.' is already added from age group '.$ageQuery->age_from." to ".$ageQuery->age_to." for ".$profile_has_examList." examinations.";
                }
            }
            return $msg;
            exit();
            
        }

       // dd($msg);

        return $msg;
        exit();
    }

    public function destroy($encID)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_PROFILE_DELETE');
        $id = base64_decode(base64_decode($encID));
        try {

                DB::beginTransaction();
                $BaseModel = $this->BaseModel->find($id); 
                if($BaseModel->delete())
                {
                    $newData = $BaseModel->toArray();
                    if($this->ProfileHasExaminationsModel->where('profile_id', $id)->delete())
                    {
                        DB::commit();
                       $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData);
                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['msg']    = __('admin.PROFILE_DELETED');
                    }else{

                         DB::rollback();
                        $this->JsonData['exception'] = $e->getMessage();
                    }

                }else
                {
                    DB::rollback();
                    $this->JsonData['exception'] = $e->getMessage();
                }
                
            } catch (Exception $e) 
            {
                DB::rollback();
                $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $e->getMessage();
            }
        return response()->json($this->JsonData);
    }

    public function getRecords(Request $request)
    {
        // dd($request->custom['age_from']);
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
                1 => 'profiles_templates.name',
                2 => 'profiles_templates.age_from',
                3 => 'profiles_templates.age_to',
                4 => 'status',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel;
                          
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
                    $modelQuery    = $modelQuery
                    ->where('profiles_templates.name',  $key);
                }

                if (isset($request->custom['age_from'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['age_from'];
                    $modelQuery     = $modelQuery
                    ->where('profiles_templates.age_from', $key);
                }

                if (isset($request->custom['age_to'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['age_to'];
                    $modelQuery     = $modelQuery
                    ->where('profiles_templates.age_to', $key);
                }

                if (isset($request->custom['status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('profiles_templates.status', $key);
                }
            }

            // filter options for commen search box
            if (!empty($request->search))
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];

                    $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orwhere('profiles_templates.name', 'LIKE', "%".$search."%");    
                        $query->orwhere('profiles_templates.age_from', 'LIKE', '%'.$search.'%');   
                        $query->orwhere('profiles_templates.age_to', 'LIKE', '%'.$search.'%');
                        // if($search=="Active"){
                        //     $query->orwhere('profiles_templates.status', '=', 1);
                        // }
                        // else{
                        //     $query->orwhere('profiles_templates.status', '=', 0);
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
            // dd($object);
        /*--------------------------------------
        |  data binding
        ------------------------------*/
    
            $data = []; 
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row) 
                {

                        $data[$key]['id']       = $row->id;
                       
                        $data[$key]['name']     = '<span title="'.ucfirst($row->name).'">'.ucfirst($row->name).'</span>';

                        $data[$key]['age_from'] = '<a title="'.$row->age_from.'" href="'.$row->age_from.'" target="_blank" >'.strtolower($row->age_from).'</a>';

                        $data[$key]['age_to']   = '<a title="'.$row->age_to.'" href="'.$row->age_to.'" target="_blank" >'.strtolower($row->age_to).'</a>';   
                        
                        $data[$key]['status']   = $row->status==1 ?__('admin.TITLE_STATUS_ACTIVE_TEXT'):__('admin.TITLE_STATUS_INACTIVE_TEXT');

                        $edit="";
                        $delete="";

                        // Check Permission
                        if(auth()->user()->can('profile-templates-add')){
                            $edit = '<a href="'.route('admin.profile-templates.edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
 
                            $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.profile-templates.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>'; 
                        } 

                        $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';
                }
            }

            // Search for from age
            $from_range = '<select name="age_from" id="age_from" class="form-control my-select">'; 

            $from_range.='<option class="theme-black blue-select" value="">'.__('admin.TITLE_SELECT_AGE_FROM_TEXT').'</option>';

            for($i = 12; $i <= 80; $i++){
            
                $from_range.='<option class="theme-black blue-select" value='.$i.' '. ( $request->custom['age_from'] == $i ? 'selected' : '').'>'.$i.'</option>';
            }     
            $from_range.= "</select>"; 

            // Search for to age
            $to_range = '<select name="age_to" id="age_to" class="form-control my-select">';

            $to_range.='<option class="theme-black blue-select" value="">'.__('admin.TITLE_SELECT_AGE_TO_TEXT').'</option>';

            for($i = 12; $i <= 80; $i++){

                $to_range.='<option class="theme-black blue-select" value='.$i.' '. ( $request->custom['age_to'] == $i ? 'selected' : '').'>'.$i.'</option>';
            }     
            $to_range.= "</select>";

            ## SEARCH HTML
            $searchHTML['id']       =  '';   
            $searchHTML['name']     =  '<input type="text" class="form-control" id="template_name" value="'.($request->custom['name']).'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>'; 

            $searchHTML['age_from'] =  $from_range; 

            $searchHTML['age_to']   =  $to_range;

            $searchHTML['status']   =  '<select name="status" id="template_status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_PROFILE_STATUS').'</option>
                    <option class="theme-black blue-select" value="1" '.( $request->custom['status'] == "1" ? 'selected' : '').' >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</option>
                    <option class="theme-black blue-select" value="0" '.( $request->custom['status'] == "0" ? 'selected' : '').'>'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</option>            
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
        $profiles =  $this->BaseModel->get();
            foreach ($profiles as $key => $profile) {
                $ageFrom = $profile->age_from;
                $ageTo = $profile->age_to;
            }
        $rfrom = $request->age_from;
        $rto   =  $request->age_to; 
        
        $collection->name       = $request->name;
        $collection->age_from   = $request->age_from;
        $collection->age_to     = $request->age_to;
        $collection->status     = !empty($request->status)?1:0;
      
        //Save data
        $collection->save();

        return $collection;   
    }

} 
