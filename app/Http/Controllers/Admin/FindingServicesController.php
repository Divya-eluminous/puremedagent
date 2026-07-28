<?php

namespace App\Http\Controllers\Admin; 

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 

// Models
use App\Models\FindingServicesModel;

// Request
use App\Http\Requests\Admin\FindingServicesRequest;

// plugins
use Hash;
use DB;
use Auth;
use Storage; 
use PDF;
use App\Traits\GeneralTrait;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

class FindingServicesController extends Controller
{
    use GeneralTrait;
    private $BaseModel;

    public function __construct(
        FindingServicesModel $FindingServicesModel
    )
    {
        $this->BaseModel   = $FindingServicesModel;
       

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle  = __('admin.TITLE_CHECKLIST_TEXT');  
        $this->ModuleView   = 'admin.finding-services.';
        $this->ModulePath   = 'admin.finding-services'; 

        // Permission Middleware
        $this->middleware(['permission:finding-services-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:finding-services-add'], ['only' => ['create','store']]);
    }

    public function index()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_FINDING_SERVICES_TEXT');
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
        $this->ModuleTitle              = __('admin.TITLE_FINDING_SERVICES_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;

        // 
        $this->ViewData['examinations'] = $this->BaseModel->get();

        $this->ViewData['heading_section'] = __('admin.TITLE_CHECKLIST_HEADING_SECTION');
        $this->ViewData['question']        = __('admin.TITLE_CHECKLIST_QUESTION');

        // view file with data
        return view($this->ModuleView.'create', $this->ViewData); 
    }

    public function store(FindingServicesRequest $request)
    {
        DB::beginTransaction();
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');
        $flag = 0;
        try {
            $collection =  new $this->BaseModel;    
            $collection = self::_storeOrUpdate($collection,$request);
            
            if ($collection) 
            {
                DB::commit();
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'.index');
                $this->JsonData['msg']    = __('admin.FINDING_SERVICES_CREATED'); 
            }
            else
            {
                $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $e->getMessage();
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
        dd('show');
    }

    public function edit($encID)
    {
        //dd($encID);
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_FINDING_SERVICES_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        
        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All examsdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['colection'] = $this->BaseModel->find($id);
    
        // view file with data
        //dd($this->ViewData);
        return view($this->ModuleView.'edit', $this->ViewData);
    }
 
    public function update(FindingServicesRequest $request, $encID)
    {
        DB::beginTransaction();
        
        $id = base64_decode(base64_decode($encID));
        
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_FINDING_SERVICES_CREATE');       
        $flag = 0;

        try {
        
            $collection = $this->BaseModel->find($id);
            $collection = self::_storeOrUpdate($collection,$request);
            
            if ($collection) 
            {
                DB::commit();
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'.index');
                $this->JsonData['msg']    = __('admin.FINDING_SERVICES_UPDATED'); 
            }
            else
            {
                $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $e->getMessage();
                DB::rollback();
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
        $this->JsonData['msg']    = __('admin.FAIL_FINDING_SERVICES_DELETE'); 

        $id = base64_decode(base64_decode($encID));
        /*Examinations*/

        $BaseModel = $this->BaseModel->find($id); 
        if($BaseModel->delete())
        {                                                    
            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.FINDING_SERVICES_DELETED');
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
                0 => 'finding_services.id',
                1 => 'finding_services.name',
                2 => 'finding_services.web_link',
                3 => 'finding_services.type',
                4 => 'finding_services.status',
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
                    ->where('finding_services.name', 'LIKE', '%'.$key.'%');
                }
                
                if (!empty($request->custom['type'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['type'];                
                    $modelQuery    = $modelQuery
                    ->where('finding_services.type', 'LIKE', '%'.$key.'%');
                }

                if (isset($request->custom['status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('finding_services.status', $key);
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
                        $query->orwhere('finding_services.name', 'LIKE', '%'.$search.'%');   
                        $query->orwhere('finding_services.type', 'LIKE', '%'.$search.'%');   
                    
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
                   
                    $data[$key]['name']   = '<span title="'.ucfirst($row->name).'">'.ucfirst($row->name).'</span>';

                    $data[$key]['web_link'] = '<span title="'.ucfirst(substr($row->web_link, 0, 50)).'">'.ucfirst(substr($row->web_link, 0, 100)).'</span>';
                     $data[$key]['type'] = '<span title="'.ucfirst(substr($row->type, 0, 50)).'">'.ucfirst(substr($row->type, 0, 100)).'</span>';
                    
                    $data[$key]['status']   = $row->status==1 ?__('admin.TITLE_STATUS_ACTIVE_TEXT'):__('admin.TITLE_STATUS_INACTIVE_TEXT');

                    $edit="";
                    $delete="";
                   
                    // Check Permission
                    if(auth()->user()->can('finding-services-add')){
                        $edit = '<a href="'.route('admin.finding-services.edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';

                        $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.finding-services.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                       
                    }

                    $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';
                }
            }

            ## SEARCH HTML
            $searchHTML['id']       =  '';   
            $searchHTML['name']     =  '<input type="text" class="form-control" id="name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['web_link'] =  '';
            $searchHTML['type']     =  '<input type="text" class="form-control" id="type" value="'.($request->custom['type'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';;
            

            $searchHTML['status']   =  '<select name="status" id="status" class="form-control my-select">
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
    }    
 
    public function _storeOrUpdate($collection, $request)
    { 
        $collection->name     = $request->name;
        $collection->type     = $request->type;
        $collection->web_link = $request->web_url;
        $collection->status   = !empty($request->status)?1:0; 

        $collection->save();
        
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
    }

}
