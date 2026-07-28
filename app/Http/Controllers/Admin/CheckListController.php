<?php

namespace App\Http\Controllers\Admin; 

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Log;

// Models
use App\Models\CheckListModel; 
use App\Models\CheckListHasHeadingSectionModel; 
use App\Models\HeadingSectionHasQuestionModel; 
use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\SpecialistModel;
use App\Models\ActivityLogModel;
use App\Models\ExaminationsModel;

// Request
use App\Http\Requests\Admin\CheckListRequest;

//Trait
use App\Traits\GeneralTrait;

// plugins
use Hash;
use DB;
use Auth;
use Storage; 
use PDF;
use Session;
use File;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024


class CheckListController extends Controller
{
    private $BaseModel;
    use GeneralTrait;

    public function __construct(
        CheckListModel $CheckListModel,
        CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
        HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
        ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
        SpecialistModel $SpecialistModel,
        ActivityLogModel $ActivityLogModel,
        ExaminationsModel $ExaminationsModel
    )
    {
        $this->BaseModel   = $CheckListModel;
        $this->CheckListHasHeadingSectionModel   = $CheckListHasHeadingSectionModel;
        $this->HeadingSectionHasQuestionModel   = $HeadingSectionHasQuestionModel;
        $this->ExaminationsHasMultipleCheckListModel = $ExaminationsHasMultipleCheckListModel;
        $this->SpecialistModel = $SpecialistModel;
        $this->ActivityLogModel     = $ActivityLogModel;
        $this->ExaminationsModel = $ExaminationsModel;

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle  = __('admin.TITLE_CHECKLIST_TEXT');  
        $this->ModuleView   = 'admin.check-list.';
        $this->ModulePath   = 'admin.check-list'; 

        // Permission Middleware
        $this->middleware(['permission:manage-check-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:manage-check-add'], ['only' => ['create','store']]);
    }

    public function index()
    {
        
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_CHECKLIST_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');
        $this->ViewData['specialists']       = $this->SpecialistModel->get();
        $this->ViewData['specialist_details']= self::__GetSecialits();
        //dd($this->ViewData['specialists']);
        
        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);   
    }

    public function create() 
    {
        // Default site settings

        if(!empty(Session::get('specialist')))
        {
            $specilist_id       = Session::get("specialist");
            $specialist_details = $this->SpecialistModel->find($specilist_id);
        }
        
        $this->ModuleTitle              = __('admin.TITLE_CHECKLIST_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['formTitle']    = __('admin.TITLE_CHECK_LIST_FORMTITLE');

        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['specialist_details'] = $specialist_details;
        $this->ViewData['specialists']       = $this->SpecialistModel->get();

        // 
        $this->ViewData['examinations'] = $this->BaseModel->get();

        $this->ViewData['heading_section'] = __('admin.TITLE_CHECKLIST_HEADING_SECTION');
        $this->ViewData['question']        = __('admin.TITLE_CHECKLIST_QUESTION');

        // view file with data
        return view($this->ModuleView.'create', $this->ViewData); 
    }

    public function store(CheckListRequest $request)
    {

        DB::beginTransaction();
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');
        $flag = 0;
        try {
            if(sizeof($request->heading_section)>0)
            {
                $cntt = 0;
                foreach ($request->heading_section as $key => $value) 
                {
                    
                    if(sizeof($value['heading_section']['heading'])<0)
                    {
                        $flag = 1;
                        $this->JsonData['status'] = __('admin.RESP_ERROR');
                        $this->JsonData['url']    =  route($this->ModulePath.'.create');
                        $this->JsonData['msg']    = __('admin.CHECK_LIST_CREATED'); 
                     }
                     else
                     {
                        foreach ($value['heading_section']['question'] as $question) 
                        {
                            if(empty($value['heading_section']['heading'][0]))
                            {
                                $flag = 1;
                                $this->JsonData['status'] = __('admin.RESP_ERROR');
                                $this->JsonData['url']    =  route($this->ModulePath.'.create');
                                $this->JsonData['msg']    = __('admin.ERR_CHECKLIST_QUESTION_HEADING_REQ'); 
                            }
                            if(!isset($question))
                            {
                                $flag = 1;
                                $this->JsonData['status'] = __('admin.RESP_ERROR');
                                $this->JsonData['url']    =  route($this->ModulePath.'.create');
                                $this->JsonData['msg']    = __('admin.ERR_CHECKLIST_QUESTION_HEADING_REQ'); 
                            }
                        }
                     } $cntt++;  
                }
            }

            if($flag == 0)
            {
                $collection =  new $this->BaseModel;    
                $collection = self::_storeOrUpdate($collection,$request);
                $newData = $collection;
                if ($collection) 
                {
                    DB::commit();
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    =  route($this->ModulePath.'.index');
                    $this->JsonData['msg']    = __('admin.CHECK_LIST_CREATED'); 
                    $this->ActivityLogModel->addLog($this->ModuleTitle,'has added Checklist','Add',null,$newData);
                    //self::_creatWebLog('checkListStore',array($collection),'info');
                }
                else
                {
                    $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                    $this->JsonData['error_msg'] = $e->getMessage();
                    DB::rollback();
                }
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
        dd('show');
    }

    public function edit($encID)
    {
        // Default site settings
        if(!empty(Session::get('specialist')))
        {
            $specilist_id       = Session::get("specialist");
            $specialist_details = $this->SpecialistModel->find($specilist_id);
        }
        $this->ModuleTitle              = __('admin.TITLE_CHECKLIST_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        
        $this->ViewData['modulePath']   = $this->ModulePath;

        // $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        $this->ViewData['formTitle']    = __('admin.TITLE_CHECK_LIST_FORMTITLE');
        $this->ViewData['specialist_details'] = $specialist_details;
        // All examsdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['checkList'] = $this->BaseModel
                                       ->with(['hasheadingSection.HeadingSectionHasQuestion'])
                                       ->find($id);

        $this->ViewData['heading_section'] = __('admin.TITLE_CHECKLIST_HEADING_SECTION');
        $this->ViewData['question']        = __('admin.TITLE_CHECKLIST_QUESTION');


        //Added on 26-dec-22 for header footer point
         if(!empty(Config('ordination_id')))
        {
            $getDatabase = DB::connection('system')->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))->first(['uuid']);
            $this->ViewData['imagaPath'] = url('storage/tenancy/tenants/'.$getDatabase->uuid);
        }
        else{
            $this->ViewData['imagaPath'] = url('storage/app/public');
        }



    
        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }
 
    public function update(CheckListRequest $request, $encID)
    {
        //dd($request->all());
        DB::beginTransaction();
        
        $id = base64_decode(base64_decode($encID));
        
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');       
        $flag = 0;

        try {

            //Roshani added this on 24-06-2024 for check the checklist is added to any service or not
            if(empty($request->status))
            {
                // dump($id);
                $examinationsHasChecklist = $this->ExaminationsHasMultipleCheckListModel
                            ->where('fk_check_list_id', $id)
                            ->where('deleted_at', null)
                            ->orderByDesc('id')
                            ->first(['fk_examinations_id']);
                // dd($examinationsHasChecklist['fk_examinations_id']);
                if($examinationsHasChecklist)
                {
                    $examinations = $this->ExaminationsModel->where('deleted_at',null)
                            ->where('id',$examinationsHasChecklist['fk_examinations_id'])
                            ->first();
                    if(!empty($examinations))
                    {

                        $this->JsonData['status'] = __('admin.RESP_ERROR');
                        $this->JsonData['url']    =  route($this->ModulePath.'.index');
                        $this->JsonData['msg']    = __('admin.ERR_CHECKLIST_ALREADY_ADDED_TO_SERVICE').' '. $examinations->name; 
                        return response()->json($this->JsonData);
                    }
                }
                
            }
            //Roshani added this on 24-06-2024 for check the checklist is added to any service or not


            if(sizeof($request->heading_section)>0)
            {
                $cntt = 0;
                foreach ($request->heading_section as $key => $value) 
                {
                    
                    if(sizeof($value['heading_section']['heading'])<0)
                    {
                        $flag = 1;
                        $this->JsonData['status'] = __('admin.RESP_ERROR');
                        $this->JsonData['url']    =  route($this->ModulePath.'.create');
                        $this->JsonData['msg']    = __('admin.CHECK_LIST_CREATED'); 
                     }
                     else
                     {
                        foreach ($value['heading_section']['question'] as $question) 
                        {
                            if(empty($value['heading_section']['heading'][0]))
                            {
                                $flag = 1;
                                $this->JsonData['status'] = __('admin.RESP_ERROR');
                                $this->JsonData['url']    =  route($this->ModulePath.'.create');
                                $this->JsonData['msg']    = __('admin.ERR_CHECKLIST_QUESTION_HEADING_REQ'); 
                            }
                            if(!isset($question))
                            {
                                $flag = 1;
                                $this->JsonData['status'] = __('admin.RESP_ERROR');
                                $this->JsonData['url']    =  route($this->ModulePath.'.create');
                                $this->JsonData['msg']    = __('admin.ERR_CHECKLIST_QUESTION_HEADING_REQ'); 
                            }
                        }
                     } $cntt++;  
                }
            }

            if($flag == 0)
            {
                $collection = $this->BaseModel->find($id);
                $old_date = $collection->toArray();
                $collection = self::_storeOrUpdate($collection,$request);
                $newData = $collection->toArray();

                if ($collection) 
                {
                    DB::commit();
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    =  route($this->ModulePath.'.index');
                    $this->JsonData['msg']    = __('admin.CHECK_LIST_UPDATED'); 
                    $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated Checklist','edit',$old_date,$newData);
                    //$this->ActivityLogModel->addLog($this->ModuleTitle,'has created Checklist','Add',null,$newData);
                    //self::_creatWebLog('CheckListUpdate',array($collection),'info');
                }
                else
                {
                    $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                    $this->JsonData['error_msg'] = $e->getMessage();
                    DB::rollback();
                }
            }      
        }
        catch(\Exception $e) {

            $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
        return response()->json($this->JsonData);
    }


    public function destroy($encID)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_DELETE'); 

        $id = base64_decode(base64_decode($encID));
        /*Examinations*/

        $BaseModel = $this->BaseModel->find($id); 
        if(!empty($BaseModel))
        {
            $examinations = $this->ExaminationsHasMultipleCheckListModel
                           ->where('fk_check_list_id',$id)
                           ->get(); 

                     
            if(sizeof($examinations)>0)
            {
                $examinations = $this->ExaminationsHasMultipleCheckListModel
                               ->where('fk_check_list_id',$id)
                               ->delete(); 
            } 

            $collection = $this->CheckListHasHeadingSectionModel
                               ->where('fk_check_list_id',$id)
                               ->get();

                             
            if(sizeof($collection)>0)
            {
                foreach ($collection as $key => $value) 
                {
                    $details = $this->HeadingSectionHasQuestionModel
                               ->where('fk_check_list_heading_section_id',$value['id'])
                               ->delete();  
                }
            }                   
        
            $this->CheckListHasHeadingSectionModel
                               ->where('fk_check_list_id',$id)
                               ->delete();  


            $BaseModel->delete();                                                        

            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.CHECK_LIST_DELETED');
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
                0 => 'examinations_check_list.id',
                1 => 'examinations_check_list.check_list_name',
                1 => 'examinations_check_list.type_of_checklist',
                1 => 'examinations_check_list.introduction_text',
                2 => 'examinations_check_list.final_name',
                3 => 'examinations_check_list.status',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            // $modelQuery =  $this->BaseModel
            //                ->with(['hasExaminations']);
           
            $modelQuery =  $this->BaseModel
                           ->with(['hasExaminations'])
                           ->where('fk_specialist_id',$request->specialist_id);
                       
            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

            ## FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {
                if (!empty($request->custom['check_list_name'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['check_list_name'];                
                    $modelQuery    = $modelQuery
                    ->where('examinations_check_list.check_list_name', $key);
                }
                if (!empty($request->custom['type_of_checklist'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['type_of_checklist'];                
                    $modelQuery    = $modelQuery
                    ->where('examinations_check_list.type_of_checklist', $key);
                }
                if (!empty($request->custom['introduction_text'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['introduction_text'];                
                    $modelQuery    = $modelQuery
                    ->where('examinations_check_list.introduction_text','LIKE','%'.$key.'%');
                }
                if (!empty($request->custom['final_name'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['final_name'];                
                    $modelQuery    = $modelQuery
                    ->where('examinations_check_list.final_name','LIKE','%'.$key.'%');
                }

                if (isset($request->custom['status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('examinations_check_list.status', $key);
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
                        $query->orwhere('examinations_check_list.check_list_name', 'LIKE', "%".$search."%");   
                        $query->orwhere('examinations_check_list.type_of_checklist', 'LIKE', "%".$search."%");   
                        $query->orwhere('examinations_check_list.introduction_text', 'LIKE', '%'.$search.'%');
                        $query->orwhere('examinations_check_list.final_name', 'LIKE', '%'.$search.'%');   
                    
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
                       
                        $data[$key]['check_list_name']   = '<span title="'.ucfirst($row->check_list_name).'">'.ucfirst($row->check_list_name).'</span>';
                        if($row->type_of_checklist == 'general')
                        {
                            $data[$key]['type_of_checklist']   = __('admin.TITLE_TYPE_CHECKLIST_GENERAL');
                        }
                        else
                        {
                            $data[$key]['type_of_checklist'] = __('admin.TITLE_TYPE_CHECKLIST_PERFORMANCE');
                        }
                        

                        $data[$key]['introduction_text'] = '<span title="'.ucfirst(substr($row->introduction_text, 0, 50)).'">'.ucfirst(substr(strip_tags(htmlspecialchars_decode($row->introduction_text)), 0, 100)).'</span>';
                         $data[$key]['final_name'] = '<span title="'.ucfirst(substr($row->final_name, 0, 50)).'">'.ucfirst(substr(strip_tags(htmlspecialchars_decode($row->final_name)), 0, 100)).'</span>';
                        
                        $data[$key]['status']   = $row->status==1 ?__('admin.TITLE_STATUS_ACTIVE_TEXT'):__('admin.TITLE_STATUS_INACTIVE_TEXT');

                        $edit="";
                        $delete="";
                        $view = "";

                        // Check Permission
                        if(auth()->user()->can('exams-add')){
                            $edit = '<a href="'.route('admin.check-list.edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
 
                            
                            $view = '<a class="action-icon" title="'.__('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION').'" href="'.url('admin/check-list/view/'.base64_encode(base64_encode($row->id))) .'" ><span class="fa fa-eye"></span></a>&nbsp&nbsp';
                            if(sizeof($row->hasExaminations)>0)
                            {
                                $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this,1)" data-href="'.route('admin.check-list.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                               
                            }
                            else
                            {

                                $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this,0)" data-href="'.route('admin.check-list.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                            }
                           
                        }

                        $data[$key]['actions'] = '<div class="text-center">'.$view.$edit.$delete.'</div>';
                }
            }

            ## SEARCH HTML
            $searchHTML['id']       =  '';   
            $searchHTML['check_list_name']     =  '<input type="text" class="form-control" id="check_list_name" value="'.($request->custom['check_list_name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['type_of_checklist']     =  '<select name="status" id="type_of_checklist" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_CHECK_LIST_TYPE').'</option>
                    <option class="theme-black blue-select" value="general" '.( isset( $request->custom['type_of_checklist']) && $request->custom['type_of_checklist'] == "general" ? 'selected' : '').' >'.__('admin.TITLE_TYPE_CHECKLIST_GENERAL').'</option>
                    <option class="theme-black blue-select" value="performance" '.( isset( $request->custom['type_of_checklist']) && $request->custom['type_of_checklist'] == "performance" ? 'selected' : '').'>'.__('admin.TITLE_TYPE_CHECKLIST_PERFORMANCE').'</option>            
                    </select>';
            $searchHTML['introduction_text']     =  '';
            $searchHTML['final_name']     =  '';
            

            $searchHTML['status']   =  '<select name="status" id="exam_status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_EXAMINATION_STATUS').'</option>
                    <option class="theme-black blue-select" value="1" '.(!empty($request->custom['status']) && $request->custom['status'] == "1" ? 'selected' : '').' >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</option>
                    <option class="theme-black blue-select" value="0" '.(!empty($request->custom['status']) && $request->custom['status'] == "0" ? 'selected' : '').'>'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</option>            
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
    } //

    //Below function added on 26-dec-22 for heder footer point
      public function putFilePath($path, $file, $fileName)    
    {
         
        if(!empty(Config('ordination_id')))
        {
          
            //$fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $fileName);
            if(!File::isDirectory($path))
            {
                File::makeDirectory($path, 0777, true, true);
            }

            //commented on 26dec22
            /*$getDataBaseName = $this->website->get(); 
           
            $getDataBaseName = $this->website
                                   ->where('ordination_id',Config('ordination_id'))
                                   ->first(); */

            $getDataBaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);    

            $fileStorePath = 'public/tenancy/tenants/'.$getDataBaseName->uuid.'/'.$path;
    
            $fileStorePath = Storage::putFileAs($fileStorePath, $file, $fileName);
        }
        else
        {
           
            $path = 'public/'.$path;
            $fileStorePath = Storage::putFileAs($path, $file, $fileName);
        }
        return $fileStorePath;
    }//putFilePath



 
    public function _storeOrUpdate($collection, $request)
    { 
        $collection->check_list_name  = $request->check_list_name;
        $collection->fk_specialist_id = $request->specialist_id;
        $collection->type_of_checklist= $request->type_of_checklist;
        if($request->type_of_checklist == 'general')
        {
            $collection->frequency        = $request->frequency;
            $collection->frequency_type   = $request->frequency_type;
            if($request->hd_flag == 'yes')
            {
                //dd(Date('Y-m-d h:i:s',strtotime($request->date_of_last_activation)));
                $collection->date_of_last_activation  = Date('Y-m-d H:i:s',strtotime($request->date_of_last_activation));
            }
            
        }
        else
        {
            $collection->frequency                = null;
            $collection->frequency_type           = null;
            $collection->date_of_last_activation  = null;
        }
        //dd($request->introduction_text,$request->final_text);
        $collection->introduction_text= $request->introduction_text;
        $collection->final_name       = $request->final_text;
        //dd(htmlspecialchars($request->introduction_text),htmlspecialchars($request->final_text));
        $collection->status           = !empty($request->status)?1:0; 
        $collection->signDoc          = $request->signDoc;

        /*************header-and-footer-code*uploaded--on-26-dec-22**********/

          if (!empty($request->header_image)) 
        {
            $path = 'specialist_document';

            $objDocument = $request->header_image;

            $original_file  = $objDocument->getClientOriginalName();
            $extension      = strtolower($objDocument->getClientOriginalExtension());

            $filename       = date('YmdHis').'-header-checklist-'.$original_file;
           
            //$file           = Storage::putFileAs($path, $objDocument, $filename);
            //$file           = Storage::disk('tenant')->putFileAs($path, $objDocument, $filename);

            $file = $this->putFilePath($path, $objDocument, $filename);
          

            //$filePath       = "/app/checklist_images/".$filename;
            $filePath = self::StorePath('specialist_document/');
            $accesspath = '/specialist_document/'.$filename;

            $collection->header_image      = $filename;
            $collection->header_image_path = $accesspath;

            if(!empty($request->old_header_image))
            {
                $unlinkFile = self::unlinkFilePath($request->old_header_image);
                if(is_file($unlinkFile))
                {
                    unlink($unlinkFile);
                }
            }  

        }//if


        if (!empty($request->footer_image)) 
        {
            $path = 'specialist_document';

            $objDocument = $request->footer_image;

            $original_file  = $objDocument->getClientOriginalName();
            $extension      = strtolower($objDocument->getClientOriginalExtension());

            $filename       = date('YmdHis').'-footer-checklist-'.$original_file;
           
            //$file           = Storage::putFileAs($path, $objDocument, $filename);
            //$file           = Storage::disk('tenant')->putFileAs($path, $objDocument, $filename);
            $file = self::putFilePath($path, $objDocument, $filename);

            //$filePath       = "/app/checklist_images/".$filename;
            $filePath = self::StorePath('specialist_document/');
            $accesspath = '/specialist_document/'.$filename;

            $collection->footer_image      = $filename;
            $collection->footer_image_path = $accesspath;

            if(!empty($request->old_footer_image))
            {
                $unlinkFile = self::unlinkFilePath($request->old_footer_image);
                if(is_file($unlinkFile))
                {
                    unlink($unlinkFile);
                }
            }  

        }//if



        /***********header-footer-code-end**************************/

      
        if($collection->save()) 
        {
            //heading Section/question 
            $deleteCheckListHasHeadingSectionModel = $this->CheckListHasHeadingSectionModel
                                                     ->where('fk_check_list_id',$collection->id)
                                                     ->first();

            if(!empty($deleteCheckListHasHeadingSectionModel))
            {
                $deleteId = $deleteCheckListHasHeadingSectionModel['id'];
                                                    
                $deleteHeadingSectionHasQuestionModel = $this->HeadingSectionHasQuestionModel
                                                         ->where('fk_check_list_heading_section_id',$deleteId)
                                                         ->forceDelete();     

                $deleteCheckListHasHeadingSectionModel = $this->CheckListHasHeadingSectionModel
                                                         ->where('fk_check_list_id',$collection->id)
                                                         ->forceDelete();
            }                                    
            
                                                                                                                                  
            if(sizeof($request->heading_section)>0)
            {
                $order_id = 1;
                foreach ($request->heading_section as $key => $value) 
                {
                    $CheckListHasHeadingSectionModel = new $this->CheckListHasHeadingSectionModel;
                    $CheckListHasHeadingSectionModel->fk_check_list_id = $collection->id;
                    $CheckListHasHeadingSectionModel->heading_section  = $value['heading_section']['heading'][0];
                    $CheckListHasHeadingSectionModel->created_at       = Date('Y-m-d');

                    if($CheckListHasHeadingSectionModel->save())
                    {
                        foreach ($value['heading_section']['question'] as $question) 
                        {
                            if(isset($question) && $question!=null)
                            {
                                $HeadingSectionHasQuestionModel = new $this->HeadingSectionHasQuestionModel;
                                $HeadingSectionHasQuestionModel->fk_check_list_heading_section_id = $CheckListHasHeadingSectionModel->id;
                                $HeadingSectionHasQuestionModel->question         = $question;
                                $HeadingSectionHasQuestionModel->status           = !empty($request->status)?1:0;
                                $HeadingSectionHasQuestionModel->created_at       = Date('Y-m-d');
                                $HeadingSectionHasQuestionModel->save();   
                            }
                        }
                    }    
                    $order_id++;
                }
            }
        }
        return $collection;   
    }

    public function check_list_delete(Request $request)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_DELETE'); 

        $BaseModel = $this->CheckListHasHeadingSectionModel->find($request->id);
                   
        if($BaseModel->delete())
        {
            $deleteQue = $this->HeadingSectionHasQuestionModel
                        ->where('fk_check_list_heading_section_id',$request->id)
                        ->delete();     

            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.CHECK_LIST_HEADING_SECTION_DELETED');
        }
        return response()->json($this->JsonData);
    }

    public function check_list_question_delete(Request $request)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_DELETE'); 

        $BaseModel = $this->HeadingSectionHasQuestionModel->find($request->id);
                   
        if($BaseModel->delete())
        {
            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.CHECK_LIST_QUESTION_DELETED');
        }
        return response()->json($this->JsonData);
    }

    public function view($encID)
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_CHECKLIST_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION');
        
        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All examsdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['checkList'] = $this->BaseModel
                                       ->with(['hasheadingSection.HeadingSectionHasQuestion'])
                                       ->find($id);

        $this->ViewData['heading_section'] = __('admin.TITLE_CHECKLIST_HEADING_SECTION');
        $this->ViewData['question']        = __('admin.TITLE_CHECKLIST_QUESTION');
    
        // view file with data
        return view($this->ModuleView.'view', $this->ViewData);
    }//



    public function checklistImageDelete(Request $request)
    {
        $updated = '';
        $id = $request->id;
        $type = $request->type;
        $imgUrl = $request->imgUrl;
        if($id != '' && $imgUrl != '')
        {
            if($type == 'header')
            {
                $this->BaseModel->where('id',$id)
                     ->update(['header_image' => '', 'header_image_path' => '']);
                $updated = 'Header Updated';
            }
            if($type == 'footer')
            {
                $this->BaseModel->where('id',$id)
                     ->update(['footer_image' => '', 'footer_image_path' => '']);
                $updated = 'Footer Updated';
            }
            if($updated != '')
            {
                $getDB = DB::connection('system')->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))->first(['uuid']);
                $fullPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDB->uuid.$imgUrl;
                unlink($fullPath);
                echo $id."_".$type;
            }
            else {
                echo "Image not deleted";
            }
        }
        else {
            echo "Image not deleted";
        }
    }//checklistImageDelete

}
