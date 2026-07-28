<?php

namespace App\Http\Controllers\Admin; 

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 

// Models
use App\Models\SpecialistModel;
use App\Models\AdminUserModel;
use App\Models\SpecialistDocumentsModel;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Session;
// Request
use App\Http\Requests\Admin\SpecialistRequest;
use App\Http\Requests\Admin\SpecialistDocumentsRequest;
use App\Models\OrdinationHasSpecialistModel;
use App\Models\AppointmentTypesModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\ExaminationsModel;
use App\Models\CheckListModel; 
use App\Models\CheckListHasHeadingSectionModel; 
use App\Models\HeadingSectionHasQuestionModel; 
use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\ChannelsRemindersSettingModel;
use Illuminate\Contracts\Filesystem\Filesystem;
use App\Models\ActivityLogModel;
// use Hyn\Tenancy\Models\Website; // Commented out - using Stancl Tenancy now
use Illuminate\Support\Facades\Log; 

//Trait
use App\Traits\GeneralTrait;

// plugins
use Hash;
use DB;
use Auth;
use Storage; 
use PDF;
use Validator;
use Config;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024
use Illuminate\Validation\Rule;//added on30-5-25 by aishwarya



class SpecialistController extends Controller
{
    private $BaseModel;
    use GeneralTrait;

    public function __construct(
        SpecialistModel $SpecialistModel,
        AdminUserModel $AdminUserModel,
        Role $RoleModel,
        SpecialistDocumentsModel $SpecialistDocumentsModel,
        OrdinationHasSpecialistModel $OrdinationHasSpecialistModel,
        AppointmentTypesModel $AppointmentTypesModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        ExaminationsModel $ExaminationsModel,
        CheckListModel $CheckListModel,
        CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
        HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
        ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
        ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
        ActivityLogModel $ActivityLogModel
        // Website $website // Commented out - using Stancl Tenancy now
    )
    {
        $this->BaseModel      = $SpecialistModel;
        $this->AdminUserModel = $AdminUserModel;
        $this->RoleModel      = $RoleModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->OrdinationHasSpecialistModel = $OrdinationHasSpecialistModel;
        $this->AppointmentTypesModel = $AppointmentTypesModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->CheckListModel = $CheckListModel;
        $this->CheckListHasHeadingSectionModel = $CheckListHasHeadingSectionModel;
        $this->HeadingSectionHasQuestionModel = $HeadingSectionHasQuestionModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;
        $this->ExaminationsHasMultipleCheckListModel = $ExaminationsHasMultipleCheckListModel;
        $this->SpecialistModel = $SpecialistModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->ActivityLogModel = $ActivityLogModel;
        // $this->website = $website; // Commented out - using Stancl Tenancy now 
       
        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle  = __('admin.TITLE_CHECKLIST_TEXT');  
        $this->ModuleView   = 'admin.specialist.';
        $this->ModulePath   = 'admin.specialist'; 
        $this->specialistDocument   = 'admin.specialistDocument';

        // Permission Middleware
        $this->middleware(['permission:manage-specialist-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:manage-specialist-add'], ['only' => ['create','store']]);
    }

    public function index()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_SPECIALIST_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);   
    }

    public function create()
     {
        //Default site settings
        $this->ModuleTitle              = __('admin.TITLE_SPECIALIST_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['login_user'] = $this->AdminUserModel
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'Ordination');
                                        })
                                        ->get(); 
        //dd($this->ViewData['login_user']);
        // view file with data
        //dd($this->ModuleView);
        return view($this->ModuleView.'create', $this->ViewData); 
    }

    public function store(SpecialistRequest $request)
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
                $this->JsonData['msg']    = __('admin.SPECIALIST_CREATED'); 
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
        $this->ModuleTitle              = __('admin.TITLE_SPECIALIST_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        
        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        $this->ViewData['login_user'] = $this->AdminUserModel
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'Ordination');
                                        })
                                        ->get(); 
        // All examsdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['colection'] = $this->BaseModel->find($id);
    
        // view file with data
        //dd($this->ViewData);
        return view($this->ModuleView.'edit', $this->ViewData);
    }
 
    public function update(SpecialistRequest $request, $encID)
    {
        DB::beginTransaction();
        $id = base64_decode(base64_decode($encID));
        
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_ORDINATION_UPDATE');       
        $flag = 0;

        try {
        
            $collection = $this->BaseModel->find($id);
            $collection = self::_storeOrUpdate($collection,$request);
            
            if ($collection) 
            {
                DB::commit();
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'.index');
                $this->JsonData['msg']    = __('admin.ORDINATION_UPDATED'); 
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
        $this->JsonData['msg']    = __('admin.FAIL_SPECIALIST_DELETE'); 

        $id = base64_decode(base64_decode($encID));
        /*Examinations*/

        //$BaseModel = $this->BaseModel->find($id); 
        $BaseModel = $this->SpecialistDocumentsModel->find($id);
        $specialist_id =  $BaseModel->fk_specialist_id;
        if($BaseModel->delete())
        {                                                    
            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.SPECIALIST_DELETED');
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
                0 => 'specialist.id',
                1 => 'specialist.name',
                2 => 'specialist.status',
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
                    ->where('specialist.name', 'LIKE', '%'.$key.'%');
                }
               

                if (isset($request->custom['status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('specialist.status', $key);
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
                        $query->orwhere('specialist.name', 'LIKE', '%'.$search.'%'); 
                        // $query->orwhere('specialist.status', 'LIKE', '%'.$search.'%'); 
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
            //dd($object);
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row) 
                {   
                    Session::put("specialist",$row->id);

                    $data[$key]['id']       = $row->id;
                   
                    $data[$key]['name']   = '<span title="'.ucfirst($row->name).'">'.ucfirst($row->name).'</span>';
                    
                    $data[$key]['status']   = $row->status==1 ?__('admin.TITLE_STATUS_ACTIVE_TEXT'):__('admin.TITLE_STATUS_INACTIVE_TEXT');

                    $edit="";
                    $delete ="";
                    $appointment_types = "";
                    $documents  = "";
                    $checklists = "";
                    $services = "";
                   
                    // Check Permission
                    if(auth()->user()->can('ordination-add')){
                        $edit = '<a href="'.route('admin.specialist.edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';

                        // $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.specialist.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';

                        $checklists = '<a onclick="SetSession(this)" href="javascript:void(0)" lang-id="'.$row->id.'" class="delete-user checkList action-icon" title="'.__('admin.TITLE_CHECKLIST_BUTTON').'"  lang="'.url('admin/check-list').'" ><span class="fa fa-check-circle nav-icon"></span></a>&nbsp&nbsp';

                        $services = '<a onclick="SetSession(this)" href="javascript:void(0)" lang-id="'.$row->id.'" class="delete-user checkList action-icon" title="'.__('admin.TITLE_EXAMINATIONS_TEXT').'"  lang="'.url('admin/examinations').'" ><span class="nav-icon fas fa-diagnoses"></span></a>&nbsp&nbsp';

                        $documents = '<a class="delete-user action-icon" title="'.__('admin.TITLE_DOCUMENT_BUTTON').'"  href="'.url('admin/specialist/documents/'.base64_encode(base64_encode($row->id))) .'" target="_new"><span class="fa fa-server" aria-hidden="true"></span></a>&nbsp&nbsp';

                        $appointment_types = '<a onclick="SetSession(this)" href="javascript:void(0)" lang-id="'.$row->id.'" class="delete-user action-icon" title="'.__('admin.TITLE_APPOINMENT_TYPE_BUTTON').'" lang="'.url('admin/apointment-types').'"><span class="nav-icon fas fa-calendar-check"></span></a>&nbsp&nbsp';
                       
                    }

                    $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.$appointment_types.$services.$checklists.$documents.'</div>';
                }
            }

            ## SEARCH HTML
            $searchHTML['id']               =  '';   
            $searchHTML['name']             =  '<input type="text" class="form-control" id="name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            
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
        //dd($collection->all());
        $collection->name   = $request->name;
        $collection->status = !empty($request->status)?'1':'0'; 
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

    // Document
    public function documents($encID = false)
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_SPECIALIST_DOCUMENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');

        $id = base64_decode(base64_decode($encID));
        $this->ViewData['specialist_details']= $this->BaseModel->find($id);
        $this->ViewData['specialists']       = $this->BaseModel->get();

        $this->ViewData['type']        = 'index';
        $this->ViewData['id']          = $id;
        //dd($this->ViewData);
        // view file with data
        return view($this->ModuleView.'documents.index', $this->ViewData);   
    }

    public function getDocumentRecords(Request $request)
    { 
        //dd($request->specialist_id);
        Session::put("specialist",$request->specialist_id);
       
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
                0 => 'specialist_has_documents.id',
                1 => 'specialist_has_documents.name',
                2 => 'specialist_has_documents.type_of_document',
                3 => 'specialist_has_documents.frequency',
                4 => 'specialist_has_documents.frequency_type',
                5 => 'specialist_has_documents.status',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
        if($request->specialist_id!='all')
        {
            $modelQuery =  $this->SpecialistDocumentsModel
                           ->where('fk_specialist_id',$request->specialist_id);
        }
        else
        {
            $modelQuery =  $this->SpecialistDocumentsModel;
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
                    $custom_search = true;
                    $key           = $request->custom['name'];                
                    $modelQuery    = $modelQuery
                    ->where('specialist_has_documents.name', 'LIKE', '%'.$key.'%');
                }

                if (!empty($request->custom['type_of_document'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['type_of_document'];                
                    $modelQuery    = $modelQuery
                    ->where('specialist_has_documents.type_of_document', 'LIKE', '%'.$key.'%');
                }

                if (!empty($request->custom['frequency'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['frequency'];                
                    $modelQuery    = $modelQuery
                    ->where('specialist_has_documents.frequency', 'LIKE', '%'.$key.'%');
                }
                if (!empty($request->custom['frequency_type'])) 
                {
                    $custom_search = true;
                    $key           = $request->custom['frequency_type'];                
                    $modelQuery    = $modelQuery
                    ->where('specialist_has_documents.frequency_type', 'LIKE', '%'.$key.'%');
                }
               

                if (isset($request->custom['status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('specialist_has_documents.status', $key);
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
                        $query->orwhere('specialist_has_documents.name', 'LIKE', '%'.$search.'%'); 
                        $query->orwhere('specialist_has_documents.type_of_document', 'LIKE', '%'.$search.'%'); 
                        $query->orwhere('specialist_has_documents.frequency', 'LIKE', '%'.$search.'%');
                        $query->orwhere('specialist_has_documents.frequency_type', 'LIKE', '%'.$search.'%'); 
                        // $query->orwhere('specialist.status', 'LIKE', '%'.$search.'%'); 
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
            //dd($object);
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row) 
                {   
                    $data[$key]['id']       = $row->id;
                   
                    $data[$key]['name']             = '<span title="'.ucfirst($row->name).'">'.ucfirst($row->name).'</span>';
                    $data[$key]['type_of_document'] = '<span title="'.ucfirst($row->type_of_document).'">'.ucfirst($row->type_of_document).'</span>';
                    $data[$key]['frequency'] = '<span title="'.ucfirst($row->frequency).'">'.ucfirst($row->frequency).'</span>';
                    $data[$key]['frequency_type'] = '<span title="'.ucfirst($row->frequency_type).'">'.ucfirst($row->frequency_type).'</span>';
                    
                    $data[$key]['status']   = $row->status==1 ?__('admin.TITLE_STATUS_ACTIVE_TEXT'):__('admin.TITLE_STATUS_INACTIVE_TEXT');

                    $edit="";
                    $delete ="";
                   
                    // Check Permission
                    if(auth()->user()->can('ordination-add')){
                        $edit = '<a href="'.url('admin/specialist/documentEdit/'.base64_encode(base64_encode($row->id))).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';

                        //$delete = '<a href="javascript:void(0)" data-href="'.url('admin/specialist/documentDelete/'.base64_encode(base64_encode($row->id))).'" onclick="return deleteCollection(this)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'">
                                 //<span class="fas fa-trash"></span></a>&nbsp&nbsp';

                       $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.specialist.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                       
                    }

                    $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';
                }
            }

            ## SEARCH HTML
            $searchHTML['id']               =  '';   
            $searchHTML['name']             =  '<input type="text" class="form-control" id="name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['type_of_document']  =  '<input type="text" class="form-control" id="type_of_document" value="'.($request->custom['type_of_document'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['frequency']  =  '<input type="text" class="form-control" id="frequency" value="'.($request->custom['frequency'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['frequency_type']  =  '<input type="text" class="form-control" id="frequency_type" value="'.($request->custom['frequency_type'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            
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

    public function SetSession(Request $request)
    {   
        Session::put("specialist",$request->specialist_id);
        return 'true';
    }

    public function documetCreate($encID= false)
    {
        // Default site settings
        //$id = base64_decode(base64_decode($encID));
        $id = Session::get('specialist');
        $this->ModuleTitle              = __('admin.TITLE_SPECIALIST_DOCUMENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        $this->ViewData['type']         = 'index';
        $this->ViewData['specialist_id']  = $id;
         $this->ViewData['specialist_details'] = self::__GetSecialits();
        $this->ViewData['specialists']       = $this->SpecialistModel->get();

        // view file with data
        return view($this->ModuleView.'documents.create', $this->ViewData);   
    }

    public function documentStore(SpecialistDocumentsRequest $request)
    {
        
        DB::beginTransaction();
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');
        $flag = 0;
        // try {
            $collection =  new $this->SpecialistDocumentsModel;    
            $collection = self::_documentStoreOrUpdate($collection,$request);
            $newData = $collection->toArray();
            
            if ($collection) 
            {
                DB::commit();
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  url('/admin/specialist/documents/'.base64_encode(base64_encode($request->specialist_id)));
                $this->JsonData['msg']    = __('admin.SPECIALIST_DOCUMENT_CREATED'); 
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has added document','Add',null,$newData);
            }
            else
            {
                $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $e->getMessage();
                DB::rollback();
            }
        // }
        // catch(\Exception $e) {

        //     $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
        //     $this->JsonData['error_msg'] = $e->getMessage();
        // }
        return response()->json($this->JsonData);
    }

    public function _documentStoreOrUpdate($collection, $request)
    {   //dd($request->signDoc);
        $collection->fk_specialist_id  = $request->specialist_id;
        $collection->name              = $request->name;
        $collection->type_of_document  = $request->type_of_document;
        $collection->name              = $request->name;
        $collection->html_text         = $request->html_text;
        // $collection->header_image      = $request->text_color_code;
        // $collection->footer_image      = $request->background_color;
        $collection->background_color  = $request->background_color;
        if($request->hd_flag == 'yes')
        {
            $collection->date_of_last_activation = date('Y-m-d H:i:s',strtotime($request->date_of_last_activation));
        }
        $collection->frequency         = $request->frequency;
        $collection->frequency_type    = $request->frequency_type;
        $collection->signDoc           = $request->signDoc;
       
        $collection->status            = !empty($request->status)?'1':'0'; 

        if (!empty($request->header_image)) 
        {
            $path = 'specialist_document';

            $objDocument = $request->header_image;

            $original_file  = $objDocument->getClientOriginalName();
            $extension      = strtolower($objDocument->getClientOriginalExtension());

            $filename       = date('YmdHis').'-footer-'.$original_file;
           
            //$file           = Storage::putFileAs($path, $objDocument, $filename);
            //$file           = Storage::disk('tenant')->putFileAs($path, $objDocument, $filename);
            $file = self::putFilePath($path, $objDocument, $filename);

            //$filePath       = "/app/specialist_document/".$filename;
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

        }

        //dd('out');

        if (!empty($request->footer_image)) 
        {
            $path = 'specialist_document';

            $objDocument = $request->footer_image;

            $original_file  = $objDocument->getClientOriginalName();
            $extension      = strtolower($objDocument->getClientOriginalExtension());

            $filename       = date('YmdHis').'-footer-'.$original_file;
           
            //$file           = Storage::putFileAs($path, $objDocument, $filename);
            //$file           = Storage::disk('tenant')->putFileAs($path, $objDocument, $filename);
            $file = self::putFilePath($path, $objDocument, $filename);

            //$filePath       = "/app/specialist_document/".$filename;
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

        }
        $collection->save();
        
        return $collection;   
    }

    public function documentEdit($encID)
    {

         if(!empty(Config('ordination_id')))
        {
            $getDatabase = DB::connection('system')->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))->first(['uuid']);
            $this->ViewData['imagaPath'] = url('storage/tenancy/tenants/'.$getDatabase->uuid);
        }
        else{
            $this->ViewData['imagaPath'] = url('storage/');
        }
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_SPECIALIST_DOCUMENT_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        
        $this->ViewData['modulePath']   = $this->specialistDocument;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        
        // All examsdata
        $id = base64_decode(base64_decode($encID));
      
        $this->ViewData['collection'] = $this->SpecialistDocumentsModel->find($id);


        /***************code added on 16-sept-24*************************************/

        $headerImageExists = $footerImageExists = 0;
        $getSpecialistDocument = $this->SpecialistDocumentsModel->find($id);
        if(isset($getSpecialistDocument))
        {
            $header_image_path = $getSpecialistDocument->header_image_path;
            $footer_image_path =  $getSpecialistDocument->footer_image_path;

             if(!empty(Config('ordination_id')))
            {
               $getDatabase = DB::connection('system')->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))->first(['uuid']);
               $headerImagePath = 'storage/tenancy/tenants/'.$getDatabase->uuid.'/'.$header_image_path;
               $footerImagePath = 'storage/tenancy/tenants/'.$getDatabase->uuid.'/'.$footer_image_path;
            }
            else{
                $headerImagePath = 'storage/'.$header_image_path;
                $footerImagePath = 'storage/'.$footer_image_path;
            }

            //$headerImagePath = self::getFilePath($header_image_path); 

            if (file_exists($headerImagePath)) {
                $headerImageExists = 1;
            }

           // $footerImagePath = self::getFilePath($footer_image_path);

            if (file_exists($footerImagePath)) {
                $footerImageExists = 1;
            }

          $this->ViewData['headerImageExists'] = $headerImageExists;
          $this->ViewData['footerImageExists'] = $footerImageExists; 
         

        }//specialistDocument
        

        /***************code added on 16-sept-24***************************************/
        
    
        // view file with data
        return view($this->ModuleView.'documents.edit', $this->ViewData);  
    }

    public function documentImageDelete(Request $request)
    {
        $updated = '';
        $id = $request->id;
        $type = $request->type;
        $imgUrl = $request->imgUrl;
        if($id != '' && $imgUrl != '')
        {
            if($type == 'header')
            {
                $this->SpecialistDocumentsModel->where('id',$id)
                     ->update(['header_image' => '', 'header_image_path' => '']);
                $updated = 'Header Updated';
            }
            if($type == 'footer')
            {
                $this->SpecialistDocumentsModel->where('id',$id)
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
    }

    public function documentupdate(Request $request, $encID)
    {
        $id = base64_decode(base64_decode($encID));
        //--------------------------------------- 
        $validator = Validator::make($request->all(),[
                        'type_of_document' => 'required',
                          /*  'name' => 'required|unique:specialist_has_documents,name,'.$id,*/
                      /*Added validation for name on 30-5-25 by aishwarya*/
                      'name' => [
                            'required',
                            Rule::unique('specialist_has_documents')->where(function ($query) use ($request) {
                                return $query->where('fk_specialist_id', $request->specialist_id);
                            })->ignore($id),
                        ],
                        'date_of_last_activation' => 'required',
                        'background_color' => 'required',
                        'frequency'=>'required',
                        'frequency_type'=>'required',
                        'html_text' => 'required',
                         
                        ], 
                        [
                            'type_of_document.required' => __('admin.ERR_DOCUMENT_TYPE_OF_DOCUMENT'),   
                            'name.required'             => __('admin.ERR_SPECIALIST_DOCUMENT_NAME_REQUIRED'),     
                            'name.unique'               => __('admin.ERR_SPECIALIST_DOCUMENT_NAME_UNIQUE_REQUIRED'), 
                            'date_of_last_activation.required'     => __('admin.ERR_DOCUMENT_DATE_OF_ACTIVATION'),
                            'background_color.required'    => __('admin.ERR_DOCUMENT_BACKGROUND_COLOR'),
                            'frequency.required'        => __('admin.ERR_DOCUMENT_FREQUENCY'),
                            'frequency_type.required'   => __('admin.ERR_DOCUMENT_FREQUENCY_TYPE'),
                            'html_text.required'        => __('admin.ERR_DOCUMENT_HTML_TEXT'),   
                            ]
                        );

            if ($validator->fails()) 
            { 
                return redirect('/admin/specialist/documentEdit/'.$encID)
                       ->withErrors($validator)
                       ->withInput();
            }
            else
            {
                // --------------------------------------
                //dd($request->all());
                DB::beginTransaction();
                
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['msg']    = __('admin.FAIL_ORDINATION_UPDATE');       
                $flag = 0;

              //  try {
                
                    $collection = $this->SpecialistDocumentsModel->find($id);
                    $old_date = $collection->toArray();
                    $collection = self::_documentStoreOrUpdate($collection,$request);
                    $newData = $collection->toArray();
                    if ($collection) 
                    {
                        DB::commit();
                        // $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        // $this->JsonData['url']    =  url('/admin/specialist/documents/'.$encID);
                        // $this->JsonData['msg']    = __('admin.SPECIALIST_DOCUMENT_UPDATED'); 
                        $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated document','edit',$old_date,$newData);
                        return redirect('/admin/specialist/documents/'.base64_encode(base64_encode($request->specialist_id)))
                                ->with('success' ,  __('admin.SPECIALIST_DOCUMENT_UPDATED'))
                                ->withInput();
                       
                    }
                    else
                    {
                        return redirect('/admin/specialist/documents/'.base64_encode(base64_encode($request->specialist_id)))
                              ->with('error', __('admin.ERR_SOMETHING_WRONG'))
                              ->withInput();

                           
                        DB::rollback();
                    }
              //  }
               // catch(\Exception $e) {

                    // $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                   // $this->JsonData['error_msg'] = $e->getMessage();
                    // return redirect('/admin/specialist/documents/'.base64_encode(base64_encode($request->specialist_id)))
                    //         ->with('error', __('admin.ERR_SOMETHING_WRONG'))
                    //           ->withInput();
               // }
                return response()->json($this->JsonData);
            }

    }

    public function documentDelete($encID)
    {
        //base64_encode(base64_encode($row->id)))
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_SPECIALIST_DELETE'); 

        $id = base64_decode(base64_decode($encID));
        /*Examinations*/

        $BaseModel = $this->SpecialistDocumentsModel->find($id);
        $specialist_id =  $BaseModel->fk_specialist_id;
        if($BaseModel->delete())
        {
             DB::commit();
            return redirect('/admin/specialist/documents/'.base64_encode(base64_encode($specialist_id)))
                    ->with('success' ,  __('admin.SPECIALIST_DOCUMENT_DELETED'))
                    ->withInput();
                   
        }
        else
        {
            return redirect('/admin/specialist/documents/'.base64_encode(base64_encode($specialist_id)))
                  ->with('error', __('admin.ERR_SOMETHING_WRONG'))
                  ->withInput();

            DB::rollback();
        }
       
    }   
    // End Document

    // checkList
    public function checklist($encID)
    {
        dd("underconstraction");
    }

    // Appointment Type
    public function appointment_types($encID)
    {
        dd("underconstraction");
    }

    public function getSpecilistRecord(Request $request)
    {
        // Hyn Tenancy (commented out)
        // if(!empty(Config('website_id')))
        // {
        //     $websiteId     = Config('website_id');
        //     $ordination_id = Config('ordination_id');
        // }

        // Stancl Tenancy: Get current tenant
        $tenant = tenancy()->tenant;
        if($tenant)
        {
            $ordination_id = $tenant->ordination_id;
        }
        else
        {
            $ordination_id = 1; // Fallback
        }

        $specilist = $finalSpecilist = [];
        $masterSpecilistDocument = DB::connection('system')
                        ->table("specialist")
                        ->select('specialist.name','specialist.id')
                        //->join('specialist_has_documents','specialist_has_documents.fk_specialist_id','specialist.id')
                        ->where('specialist.status','1')
                        //->where('specialist_has_documents.status','1')
                        ->groupBy('specialist.name','specialist.id')
                        ->whereNull('deleted_at')
                        ->get();
        //dd($masterSpecilistDocument);
        $cnt = 0;
        foreach ($masterSpecilistDocument as $key => $value) 
        {
            $tenantSpecilist = $this->OrdinationHasSpecialistModel
                               ->where('status','1')
                               ->where('specialist_id',$value->id)
                               ->where('ordination_id',$ordination_id)
                               ->first();

            $specilist[$cnt]['id']   = $value->id;
            $specilist[$cnt]['name'] = $value->name;  

            if(!empty($tenantSpecilist))
            {
                $specilist[$cnt]['type'] ='1' ;
            } 
            else
            {
                $specilist[$cnt]['type'] ='0' ;
            } 

            $cnt++;                 
        }
        $str = '';
        if(!empty($specilist) && sizeof($specilist)>0)
        {
            $checkedVar = '';
            $cnt = 0;
            $str .='<div class="row">
                          <div class="col-1">
                          </div>
                          <div class="col-11">
                          <div class="form-group"><label class="theme-blue"> 
                          '.__('admin.TITLE_SPECIALIST_TEXT').' </label>
                          </div>    
                          </div>
                        </div>';
            foreach ($specilist as $key => $value) 
            {
                
                if($value['type'] == '1')
                {
                    $checkedVar = 'checked';
                }    
                else
                {
                    $checkedVar = '';
                }
                ;
                $str .= '<div class="row">
                          <div class="col-1">
                            <div class="form-group" >
                              <div class="form-check" > 
                                <input '.$checkedVar.' type="checkbox" class="form-check-input" id="status"
                              name="chk_specilist[]" value="'.$value["id"].'"
                              >
                                <label class="form-check-label" for="status"></label>
                              </div>
                            </div>
                          </div>
                          <div class="col-11">
                            <input 
                            type="text" 
                            name="specilist['.$value["id"].']" 
                            class="form-control"
                            readonly 
                            value="'.$value['name'].'"
                            >
                            <span class="help-block invalid-feedback with-errors">
                              <ul class="list-unstyled">
                                <li class="err_date"></li>
                              </ul>
                            </span>
                          </div>
                        </div><br/>';   
                $cnt++;
            };
        }
        else
        {
            $str .='<div class="row">
                        <div class="col-12">
                                '.__('admin.TITLE_SPECIALIST_NOT_EXIST').'
                        </div>
                    </div>';
        }
        return $str;
    }

    public function assignedSpecialist(Request $request)
    {
        DB::beginTransaction();
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');
        $flag = 0;
        // try {
             
            if(!empty($request->chk_specilist) && count($request->chk_specilist)>0)
            {
                $collection = self::_storeOrUpdateSpecilist($request);

                // if ($collection) 
                // {
                    DB::commit();
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    =  route($this->ModulePath.'.index');
                    $this->JsonData['msg']    = __('admin.SPECIALIST_ASSIGNED'); 
                // }
                // else
                // {
                //     $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
                //     $this->JsonData['error_msg'] = $e->getMessage();
                //     DB::rollback();
                // }
            }
            else
            {
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['url']    =  route($this->ModulePath.'.index');
                $this->JsonData['msg']    = __('admin.SPECIALIST_RECORD_ERROR'); 
            }
        // }
        // catch(\Exception $e) {

        //     $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
        //     $this->JsonData['error_msg'] = $e->getMessage();
        // }
        return response()->json($this->JsonData);
    }

    public function _storeOrUpdateSpecilist($request)
    {
        $collection = $int_array = [];
        $flag = 0 ;
        if(!empty($request->chk_specilist) && count($request->chk_specilist)>0 )
        {
            // TODO: Replace with Stancl tenancy logic
            // $website   = \Hyn\Tenancy\Facades\TenancyFacade::website();
            $tenant = tenancy()->tenant;
            $ordination_id = $tenant->ordination_id ?? 1;
            // Temporary solution - get ordination_id from session or request
            // $ordination_id = session('ordination_id') ?? request('ordination_id') ?? 1;
            // $websiteId = 1; // Temporary fallback
            // CHECK SPECIALIST ASSIGNED OR NOT
            
            //commented on 25-nov-24
            /*$isexistSpecilist = $this->OrdinationHasSpecialistModel
                                ->where('ordination_id',$ordination_id)
                                ->whereNull('deleted_at')
                                ->pluck('specialist_id');*/

            //did changes on 25-nov-24                     
            $isexistSpecilist = $this->OrdinationHasSpecialistModel
                                ->where('ordination_id',$ordination_id)
                                ->whereNull('deleted_at')
                                ->pluck('specialist_id')->toArray();                   


            // log::info("here2>>");
            // log::info($isexistSpecilist);
            if(!empty($isexistSpecilist) && sizeof($isexistSpecilist)>0)
            {

               //commented on 25-nov-24
               /* foreach ($request->chk_specilist as $chk_key => $chk_value) 
                {
                    $int_array[] = (int)$chk_value;
                }
               
                $arr_merge = array_merge($int_array,$isexistSpecilist->toArray());
                
                $arr_diff = array_unique($arr_merge);
                if(sizeof($arr_diff)>0)
                {
                    foreach ($arr_diff as $diff_key => $diff_value) 
                    {
                        if (in_array($diff_value, $int_array))
                        {
                            $isexistOrdination = $this->OrdinationHasSpecialistModel
                                             ->where('ordination_id',$ordination_id)
                                             ->where('specialist_id',$diff_value)
                                             ->first();
                           // dd($isexistOrdination); //commented on 5-nov-24 for switch specialist                
                            if(empty($isexistOrdination))
                            {
                               $this->importSpecilistRecord($diff_value,$ordination_id);
                               //insert record
                            }
                                            
                        }
                        else
                        {
                           $this->importDeleteRecord($diff_value);
                        }
                    }
                }*/
 

                /*****added on 25-nov-24*****************************/
                 // Convert request data to an integer array
                $newSpecialists = array_map('intval', $request->chk_specilist);

                // Find specialists to delete (in the database but not in the new list)
                $specialistsToDelete = array_diff($isexistSpecilist, $newSpecialists);
                foreach ($specialistsToDelete as $specialistId) {
                    $this->importDeleteRecord($specialistId); // Call delete function
                }

                // Find specialists to add (in the new list but not in the database)
                $specialistsToAdd = array_diff($newSpecialists, $isexistSpecilist);
                foreach ($specialistsToAdd as $specialistId) {
                    $this->importSpecilistRecord($specialistId, $ordination_id); // Add new record
                }

                /******added on 25-nov-24****************************/
                
            }
            else
            {
                // new template
                $flag = 1;
            }
           
            // RECORD INSERTFOR THE CURRENT ORDINATION
            if($flag == 1)
            {
                //dd($request->chk_specilist);
                foreach ($request->chk_specilist as $key => $value) 
                {
                    $this->importSpecilistRecord($value,$ordination_id);                      
                }
            }
        }    
        return $collection;
    }

    public function importSpecilistRecord($id,$ordination_id)
    {
        $masterSpecilistDetails = DB::connection('system')
                                   ->table("specialist")
                                   ->where('id',$id)
                                   ->whereNull('deleted_at')
                                   ->first();

        if(!empty($masterSpecilistDetails))
        {
            //SPECILIST INSERT
            $collection         =  new $this->OrdinationHasSpecialistModel;
            $collection->ordination_id = $ordination_id;
            $collection->specialist_id = $id; 
            $collection->status        = '1'; 
            $collection->save();
            // log::info("here2");
            // log::info($collection);
            $hasExistPeciality = DB::connection('system')
                                ->table('ordination_has_specialist')
                                ->where('ordination_id',$ordination_id)
                                ->where('specialist_id',$id)
                                ->where('status',1)
                                ->whereNull('deleted_at')
                                ->first();
            if(empty($hasExistPeciality))  
            {
                $collection2['ordination_id'] = $ordination_id;
                $collection2['specialist_id'] = $id;
                $collection2['status']        = 1;  
            
                $ordination_has_specialist = DB::connection('system')
                                        ->table('ordination_has_specialist')
                                        ->insertGetId($collection2);
            }                  
            // if($ordination_has_specialist)
            // {
            $specilist = new $this->BaseModel;
            $specilist->name   = $masterSpecilistDetails->name;
            $specilist->status = '1';
            if($specilist->save())
            {
                // appointment type hase services
                $getAppointmentType = self::_importAppointmentTypeNode($id,$specilist->id);
            }
            //}
        }    
    }

    public function _importAppointmentTypeNode($master_specialits_id,$id)
    {
        //GET APPOINTMENT TYPE
        $mastergetAppointmentType = DB::connection('system')
                                   ->table("appointment_types")
                                   ->where('fk_specialist_id',$master_specialits_id)
                                   // ->where('status',1)
                                   ->whereNull('deleted_at')
                                   ->get();

        //dump($mastergetAppointmentType);                           
        if(!empty($mastergetAppointmentType) && count($mastergetAppointmentType)>0)
        {
             
            foreach ($mastergetAppointmentType as $mat_key => $mat_val) 
            {
                //dd($mat_val,$mat_val['name']);
                $getAppointmentType = new $this->AppointmentTypesModel;
                $getAppointmentType->fk_specialist_id = $id;
                $getAppointmentType->name             = $mat_val->name;
                $getAppointmentType->duration         = $mat_val->duration;
                $getAppointmentType->description      = $mat_val->description;
                $getAppointmentType->status           = $mat_val->status;
                $getAppointmentType->recommend_exams  = $mat_val->recommend_exams;
                $getAppointmentType->patient_document = $mat_val->patient_document;
                $getAppointmentType->on_dashboard = $mat_val->on_dashboard;

                $getAppointmentType->optimal_appointment = $mat_val->optimal_appointment;//added for #243 issue on 16-dec-24 

                /********** This 2 lines added by roshani for resolved issue 117 in issue pdf on 26-june-24***********/
                $maxSortOrder = $this->AppointmentTypesModel->max('sorting_order'); 
                $getAppointmentType->sorting_order=$maxSortOrder+1;
                /********** This 2 lines added by roshani for resolved issue 117 in issue pdf on 26-june-24***********/
                if($getAppointmentType->save())
                {
                    // GET SERVICES FOR THAT POINTMENT
                    $getmasterAppointmentTypehasDoc = DB::connection('system')
                                    ->table("appoinment_type_has_examinations")
                                    ->where('appoinment_id',$mat_val->id)
                                    ->where('fk_specialist_id',$master_specialits_id)
                                    ->whereNull('deleted_at')
                                    ->get();

                    if(!empty($getmasterAppointmentTypehasDoc) && sizeof($getmasterAppointmentTypehasDoc)>0)
                    {
                        // Examination(services)
                        foreach ($getmasterAppointmentTypehasDoc as $ate_key => $ate_val) 
                        {

                            // GET EXAMINATION
                            $masterExam = DB::connection('system')
                                    ->table("examinations")
                                    ->where('id',$ate_val->examination_id)
                                    ->whereNull('deleted_at')
                                    ->first();
                            
                            if(!empty($masterExam))
                            {
                                //CHECK EXAM ALLREADY EXIST OR NOT
                                $isexistExam = $this->ExaminationsModel
                                               ->where('fk_specialist_id',$id)
                                               ->where('name',$masterExam->name)
                                               ->first();

                                if(empty($isexistExam))
                                {
                                    // INSERT SERVICES
                                    $services = new $this->ExaminationsModel;

                                    $services->fk_specialist_id = $id;
                                    $services->name             = $masterExam->name;
                                    $services->description      = $masterExam->description;
                                    $services->url              = $masterExam->url;
                                    $services->status           = $masterExam->status;
                                    $services->show_as_control  = $masterExam->show_as_control;
                                    $services->check_list_pdf_name = $masterExam->check_list_pdf_name;
                                    $services->check_list_pdf_path = $masterExam->check_list_pdf_path;
                                    $services->check_list_status   = $masterExam->check_list_status;
                                    $services->trigger_exam_flag   = $masterExam->trigger_exam_flag;
                                    $services->default_service     = $masterExam->default_service;

                                    /*****start***added new***on 18-dec-24*for #242 issue**********/
                                    $services->document_name  = $masterExam->document_name;
                                    $services->document_path  = $masterExam->document_path;
                                    $services->document_status  = $masterExam->document_status;
                                    $services->trigger_exam_flag  = $masterExam->trigger_exam_flag;

                                    $services->show_as_recommended  = $masterExam->show_as_recommended;
                                    $services->show_as_reminder  = $masterExam->show_as_reminder;
                                    $services->on_dashboard  = $masterExam->on_dashboard;
                                    /*****end*****added new***on 18-dec-24*for #242 issue***********/



                                    /********** This 2 lines added by roshani for resolved issue 117 in issue pdf on 26-june-24***********/
                                    $maxSortOrder = $this->ExaminationsModel->max('sorting_order'); 
                                    $services->sorting_order=$maxSortOrder+1;
                                    /********** This 2 lines added by roshani for resolved issue 117 in issue pdf on 26-june-24***********/
                                    if($services->save())
                                    {


                                        $masterIsSettingSet = DB::connection('system')
                                                                ->table("preferred_channels_for_reminders_setting")
                                                                ->where('service_id',$ate_val->examination_id)
                                                                ->whereNull('deleted_at')
                                                                ->first();
                                        if(!empty($masterIsSettingSet))
                                        {
                                            $isSettingSet = new $this->ChannelsRemindersSettingModel;
                                            $isSettingSet->choice_of_channels = $masterIsSettingSet->choice_of_channels;
                                            $isSettingSet->holiday_reminder = $masterIsSettingSet->holiday_reminder;
                                            $isSettingSet->reminder_push_notification_text = $masterIsSettingSet->reminder_push_notification_text;
                                            $isSettingSet->reminder_sms_notification_text = $masterIsSettingSet->reminder_sms_notification_text;
                                            $isSettingSet->reminder_mail_notification_text = $masterIsSettingSet->reminder_mail_notification_text;
                                            $isSettingSet->type = $masterIsSettingSet->type;

                                             /**start****added new*on 18-dec-24*for #242 issue*******/
                                           

                                            $getMasterExamName =  DB::connection('system')
                                                    ->table("examinations")
                                                    ->where('id',$masterIsSettingSet->recommanded_service_id)
                                                    ->whereNull('deleted_at')
                                                    ->first();

                                                    // dump("getMasterExamName===>");    
                                                    // dump($getMasterExamName);

                                            if(isset($getMasterExamName) && !empty($getMasterExamName)){
                                               // dump("in getMasterExamName===>");

                                                $masterServiceName = $getMasterExamName->name;
                                                 //dump($masterServiceName);

                                                $newOrdinationServiceName = $this->ExaminationsModel->where('name',$masterServiceName)->whereNull('deleted_at')->first();

                                               // dump("newOrdinationServiceName===>");    
                                                //dump($newOrdinationServiceName);

                                                if(isset($newOrdinationServiceName) && !empty($newOrdinationServiceName))
                                                {
                                                    //dump("isset newOrdinationServiceName===>");  
                                                     //dump($newOrdinationServiceName->id);
                                                    
                                                    $isSettingSet->recommanded_service_id = $newOrdinationServiceName->id;
                                                }
                                            }//if



                                            
                                            /**end****added new*on 18-dec-24*for #242 issue*******/


                                            $isSettingSet->service_id = $services->id;
                                            $isSettingSet->activated_reminder = $masterIsSettingSet->activated_reminder;
                                            $isSettingSet->general_period = $masterIsSettingSet->general_period;
                                            $isSettingSet->general_period_frequency_type = $masterIsSettingSet->general_period_frequency_type;
                                            $isSettingSet->general_new_frequency = $masterIsSettingSet->general_new_frequency;
                                            $isSettingSet->general_new_frequency_type = $masterIsSettingSet->general_new_frequency_type;
                                            $isSettingSet->general_first_frequency = $masterIsSettingSet->general_first_frequency;
                                            $isSettingSet->general_first_frequency_type = $masterIsSettingSet->general_first_frequency_type;
                                            $isSettingSet->general_time_interval = $masterIsSettingSet->general_time_interval;
                                            $isSettingSet->general_time_interval_frequency_type = $masterIsSettingSet->general_time_interval_frequency_type;
                                            $isSettingSet->general_number_of_interval = $masterIsSettingSet->general_number_of_interval;

                                            /**start**added new*on 18-dec-24*for #242 issue***********/
                                            $isSettingSet->general_end_cycle       = $masterIsSettingSet->general_end_cycle;
                                            $isSettingSet->general_end_cycle_frequency_type = $masterIsSettingSet->general_end_cycle_frequency_type;
                                            /***end*added new*on 18-dec-24*for #242 issue******/


                                            $isSettingSet->age_from = $masterIsSettingSet->age_from;
                                            $isSettingSet->age_to = $masterIsSettingSet->age_to;


                                             /*******start****added new*on 18-dec-24*for #242 issue**/
                                             $isSettingSet->age_period_controls   = isset($masterIsSettingSet->age_period_controls)?$masterIsSettingSet->age_period_controls:'';

                                             $isSettingSet->age_period_frequency_type  = isset($masterIsSettingSet->age_period_frequency_type)?$masterIsSettingSet->age_period_frequency_type:'';

                                             $isSettingSet->age_new_frequency   = isset($masterIsSettingSet->age_new_frequency)? $masterIsSettingSet->age_new_frequency:'';

                                             $isSettingSet->age_new_frequency_type  = isset($masterIsSettingSet->age_new_frequency_type)? $masterIsSettingSet->age_new_frequency_type:'';

                                             $isSettingSet->age_first_frequency  = isset($masterIsSettingSet->age_first_frequency)? $masterIsSettingSet->age_first_frequency:'';

                                             $isSettingSet->age_first_frequency_type   = isset($masterIsSettingSet->age_first_frequency_type)?$masterIsSettingSet->age_first_frequency_type:'';

                                             $isSettingSet->age_time_interval  = isset($masterIsSettingSet->age_time_interval)?$masterIsSettingSet->age_time_interval:'';


                                             $isSettingSet->age_time_interval_frequency_type   = isset($masterIsSettingSet->age_time_interval_frequency_type)?$masterIsSettingSet->age_time_interval_frequency_type:'';

                                             $isSettingSet->age_number_of_interval       = isset($masterIsSettingSet->age_number_of_interval)? $masterIsSettingSet->age_number_of_interval:'';

                                             $isSettingSet->age_end_cycle  = isset( $masterIsSettingSet->age_end_cycle)?$masterIsSettingSet->age_end_cycle:'';

                                             $isSettingSet->age_end_cycle_frequency_type = isset($masterIsSettingSet->age_end_cycle_frequency_type)?$masterIsSettingSet->age_end_cycle_frequency_type:'';
                                             /*******end*added new*on 18-dec-24*for #242 issue********/


                                            $isSettingSet->checkup_period_controls = $masterIsSettingSet->checkup_period_controls;
                                            $isSettingSet->checkup_period_frequency_type = $masterIsSettingSet->checkup_period_frequency_type; 
                                            $isSettingSet->checkup_new_frequency = $masterIsSettingSet->checkup_new_frequency; 
                                            $isSettingSet->checkup_new_frequency_type = $masterIsSettingSet->checkup_new_frequency_type;
                                            $isSettingSet->checkup_first_frequency = $masterIsSettingSet->checkup_first_frequency;
                                            $isSettingSet->checkup_first_frequency_type = $masterIsSettingSet->checkup_first_frequency_type;
                                            $isSettingSet->checkup_time_interval = $masterIsSettingSet->checkup_time_interval;
                                            $isSettingSet->checkup_time_interval_frequency_type = $masterIsSettingSet->checkup_time_interval_frequency_type;
                                            $isSettingSet->checkup_number_of_interval = $masterIsSettingSet->checkup_number_of_interval;

                                             /***start*****added new*on 18-dec-24*for #242 issue********/

                                            $isSettingSet->checkup_end_cycle       = $masterIsSettingSet->checkup_end_cycle;
                                            $isSettingSet->checkup_end_cycle_frequency_type = $masterIsSettingSet->checkup_end_cycle_frequency_type;

                                            /******end***added new*on 18-dec-24*for #242 issue*******/



                                            $isSettingSet->notify_time = $masterIsSettingSet->notify_time; //added on 3-feb-25

                                            $isSettingSet->save();
                                        }
                                       
                                        // INSERT THE APPOINTMENT TYPE HAS EXAMINATION
                                        $athexam = new $this->AppointmentTypeHasExaminationsModel;
                                        $athexam->appoinment_id = $getAppointmentType->id;
                                        $athexam->examination_id = $services->id;
                                        $athexam->fk_specialist_id = $id;
                                        $athexam->save();
                                    }

                                    //GET CHECKLIST AND DOCUMENT
                                    $getCheckList    = self::getAllchecklist($master_specialits_id,$masterExam->id,$services->id,$id,$masterExam->id);
                                    $getDocumentList = self::getAllDocumentlist($master_specialits_id,$masterExam->id,$services->id,$id,$masterExam->id);
                                }
                                else
                                {
                                    $athexam = new $this->AppointmentTypeHasExaminationsModel;
                                    $athexam->appoinment_id    = $getAppointmentType->id;
                                    $athexam->examination_id   = $isexistExam->id;
                                    $athexam->fk_specialist_id = $id;
                                    $athexam->save();
                                } 
                            }   
                        }
                    } 
                    
                    $get_all_exam_ids = array_column($getmasterAppointmentTypehasDoc->toArray(), 'examination_id');
                    // dump($get_all_exam_ids);


                    $masterwithoutAssingedExam = DB::connection('system')
                                ->table("examinations")
                                ->whereNull('default_service')
                                ->whereNotIn('id',$get_all_exam_ids)
                                ->where('fk_specialist_id',$master_specialits_id)
                                ->whereNull('deleted_at')
                                ->get();

                    //dd($masterwithoutAssingedExam);

                    if(!empty($masterwithoutAssingedExam) && sizeof($masterwithoutAssingedExam)>0)
                    {
                        //CHECK EXAM ALLREADY EXIST OR NOT
                        foreach($masterwithoutAssingedExam as $key=>$value)
                        {

                            $isexistWExam = $this->ExaminationsModel
                                       ->where('fk_specialist_id',$id)
                                       ->where('name',$value->name)
                                       ->first();
                           
                            if(empty($isexistWExam))
                            {
                                // INSERT SERVICES
                                $servicesww = new $this->ExaminationsModel;

                                $servicesww->fk_specialist_id = $id;
                                $servicesww->name             = $value->name;
                                $servicesww->description      = $value->description;
                                $servicesww->url              = $value->url;
                                $servicesww->status           = $value->status;
                                $servicesww->show_as_control  = $value->show_as_control;
                                $servicesww->check_list_pdf_name = $value->check_list_pdf_name;
                                $servicesww->check_list_pdf_path = $value->check_list_pdf_path;
                                $servicesww->check_list_status   = $value->check_list_status;
                                $servicesww->trigger_exam_flag   = $value->trigger_exam_flag;
                                $servicesww->default_service     = $value->default_service;


                                /*****start***added new*on 18-dec-24*for #242 issue**********/
                                $servicesww->document_name  = $value->document_name;
                                $servicesww->document_path  = $value->document_path;
                                $servicesww->document_status  = $value->document_status;
                                $servicesww->trigger_exam_flag  = $value->trigger_exam_flag;

                                $servicesww->show_as_recommended  = $value->show_as_recommended;
                                $servicesww->show_as_reminder  = $value->show_as_reminder;
                                $servicesww->on_dashboard  = $value->on_dashboard;
                                /*****end**added new*on 18-dec-24*for #242 issue**************/




                                /********** This 2 lines added by roshani for resolved issue 117 in issue pdf on 26-june-24***********/
                                $maxSortOrder = $this->ExaminationsModel->max('sorting_order'); 
                                $servicesww->sorting_order=$maxSortOrder+1;
                                /********** This 2 lines added by roshani for resolved issue 117 in issue pdf on 26-june-24***********/
                                if($servicesww->save())
                                {


                                   //GET CHECKLIST AND DOCUMENT
                                    $getCheckList    = self::getAllWithoutAssignedchecklist($master_specialits_id,$value->id,$servicesww->id,$id);

                                    $getDocumentList = self::getAllWithoutAssignedDocumentlist($master_specialits_id,$value->id,$servicesww->id,$id);

                                    /****start***added new*on 18-dec-24*for #242 issue***********/
                                    $masterIsSettingSet1 = DB::connection('system')
                                                                ->table("preferred_channels_for_reminders_setting")
                                                                ->where('service_id',$value->id)
                                                                ->whereNull('deleted_at')
                                                                ->first();

                                    if(!empty($masterIsSettingSet1))
                                    {

                                            $isSettingSet1 = new $this->ChannelsRemindersSettingModel;
                                            $isSettingSet1->choice_of_channels = $masterIsSettingSet1->choice_of_channels;
                                            $isSettingSet1->holiday_reminder = $masterIsSettingSet1->holiday_reminder;
                                            $isSettingSet1->reminder_push_notification_text = $masterIsSettingSet1->reminder_push_notification_text;
                                            $isSettingSet1->reminder_sms_notification_text = $masterIsSettingSet1->reminder_sms_notification_text;
                                            $isSettingSet1->reminder_mail_notification_text = $masterIsSettingSet1->reminder_mail_notification_text;
                                            $isSettingSet1->type = $masterIsSettingSet1->type;

                                            /**start****added new*on 18-dec-24*for #242 issue*******/
                                            $getMasterExamName = DB::connection('system')
                                                    ->table("examinations")
                                                    ->where('id',$masterIsSettingSet1->recommanded_service_id)
                                                    ->whereNull('deleted_at')
                                                    ->first();

                                                    // dump("getMasterExamName===>");    
                                                    // dump($getMasterExamName);

                                             if(isset($getMasterExamName) && !empty($getMasterExamName)){
                                               // dump("in getMasterExamName===>");

                                                $masterServiceName = $getMasterExamName->name;
                                                // dump($masterServiceName);

                                                $newOrdinationServiceName = $this->ExaminationsModel->where('name',$masterServiceName)->whereNull('deleted_at')->first();

                                                // dump("newOrdinationServiceName===>");    
                                                // dump($newOrdinationServiceName);

                                                if(isset($newOrdinationServiceName) && !empty($newOrdinationServiceName))
                                                {
                                                    // dump("isset newOrdinationServiceName===>");  
                                                    //  dump($newOrdinationServiceName->id);

                                                    $isSettingSet1->recommanded_service_id = $newOrdinationServiceName->id;
                                                }
                                            }//if
                                            /**end****added new*on 18-dec-24*for #242 issue*******/


                                            $isSettingSet1->service_id = $servicesww->id;
                                            $isSettingSet1->activated_reminder = $masterIsSettingSet1->activated_reminder;
                                            $isSettingSet1->general_period = $masterIsSettingSet1->general_period;
                                            $isSettingSet1->general_period_frequency_type = $masterIsSettingSet1->general_period_frequency_type;
                                            $isSettingSet1->general_new_frequency = $masterIsSettingSet1->general_new_frequency;
                                            $isSettingSet1->general_new_frequency_type = $masterIsSettingSet1->general_new_frequency_type;
                                            $isSettingSet1->general_first_frequency = $masterIsSettingSet1->general_first_frequency;
                                            $isSettingSet1->general_first_frequency_type = $masterIsSettingSet1->general_first_frequency_type;
                                            $isSettingSet1->general_time_interval = $masterIsSettingSet1->general_time_interval;
                                            $isSettingSet1->general_time_interval_frequency_type = $masterIsSettingSet1->general_time_interval_frequency_type;
                                            $isSettingSet1->general_number_of_interval = $masterIsSettingSet1->general_number_of_interval;

                                              /**start****added new*on 18-dec-24*for #242 issue*******/

                                            $isSettingSet1->general_end_cycle       = $masterIsSettingSet1->general_end_cycle;
                                            $isSettingSet1->general_end_cycle_frequency_type = $masterIsSettingSet1->general_end_cycle_frequency_type;

                                            /****end***added new*on 18-dec-24*for #242 issue****/


                                            $isSettingSet1->age_from = $masterIsSettingSet1->age_from;
                                            $isSettingSet1->age_to = $masterIsSettingSet1->age_to;
 
                                             /*******start***added new*on 18-dec-24*for #242 issue******/
                                             $isSettingSet1->age_period_controls   = isset($masterIsSettingSet1->age_period_controls)?$masterIsSettingSet1->age_period_controls:'';
                                             
                                             $isSettingSet1->age_period_frequency_type  = isset($masterIsSettingSet1->age_period_frequency_type)?$masterIsSettingSet1->age_period_frequency_type:'';


                                             $isSettingSet1->age_new_frequency   = isset($masterIsSettingSet1->age_new_frequency)?$masterIsSettingSet1->age_new_frequency:'';

                                             $isSettingSet1->age_new_frequency_type  = isset($masterIsSettingSet1->age_new_frequency_type)?$masterIsSettingSet1->age_new_frequency_type:'';


                                             $isSettingSet1->age_first_frequency  = isset($masterIsSettingSet1->age_first_frequency)?$masterIsSettingSet1->age_first_frequency:'';

                                             $isSettingSet1->age_first_frequency_type = isset($masterIsSettingSet1->age_first_frequency_type)? $masterIsSettingSet1->age_first_frequency_type:'';


                                             $isSettingSet1->age_time_interval       = isset($masterIsSettingSet1->age_time_interval)? $masterIsSettingSet1->age_time_interval:'';

                                             $isSettingSet1->age_time_interval_frequency_type   = isset($masterIsSettingSet1->age_time_interval_frequency_type)? $masterIsSettingSet1->age_time_interval_frequency_type:'';


                                             $isSettingSet1->age_number_of_interval   = isset($masterIsSettingSet1->age_number_of_interval)? $masterIsSettingSet1->age_number_of_interval:'';


                                             $isSettingSet1->age_end_cycle  = isset($masterIsSettingSet1->age_end_cycle) ? $masterIsSettingSet1->age_end_cycle:'';

                                             $isSettingSet1->age_end_cycle_frequency_type = isset($masterIsSettingSet1->age_end_cycle_frequency_type)? $masterIsSettingSet1->age_end_cycle_frequency_type:'';

                                             /****end**added new*on 18-dec-24*for #242 issue*********/

                                            $isSettingSet1->checkup_period_controls = $masterIsSettingSet1->checkup_period_controls;
                                            $isSettingSet1->checkup_period_frequency_type = $masterIsSettingSet1->checkup_period_frequency_type; 
                                            $isSettingSet1->checkup_new_frequency = $masterIsSettingSet1->checkup_new_frequency; 
                                            $isSettingSet1->checkup_new_frequency_type = $masterIsSettingSet1->checkup_new_frequency_type;
                                            $isSettingSet1->checkup_first_frequency = $masterIsSettingSet1->checkup_first_frequency;
                                            $isSettingSet1->checkup_first_frequency_type = $masterIsSettingSet1->checkup_first_frequency_type;
                                            $isSettingSet1->checkup_time_interval = $masterIsSettingSet1->checkup_time_interval;
                                            $isSettingSet1->checkup_time_interval_frequency_type = $masterIsSettingSet1->checkup_time_interval_frequency_type;
                                            $isSettingSet1->checkup_number_of_interval = $masterIsSettingSet1->checkup_number_of_interval;

                                            /****start***added new*on 18-dec-24*for #242 issue*****/
                                            $isSettingSet1->checkup_end_cycle       = $masterIsSettingSet1->checkup_end_cycle;
                                            $isSettingSet1->checkup_end_cycle_frequency_type = $masterIsSettingSet1->checkup_end_cycle_frequency_type;
                                            /*****end***added new*on 18-dec-24*for #242 issue****/


                                            $isSettingSet1->notify_time = $masterIsSettingSet1->notify_time; //added on 3-feb-25


                                            $isSettingSet1->save();
                                    }//if not empty masterIsSettingSet1     

                                    /****end***added new*on 18-dec-24*for #242 issue***********/                  

                                }//if servicesww save
                            }
                        }//foreach of masterwithoutAssingedExam

                    }//if not empty masterwithoutassignedexam 
                                   

                }//if apptype save    

                /********************************************/

                  /* $masterwithoutAssingedExam1 = DB::connection('system')
                                ->table("examinations")
                                ->whereNull('default_service')
                                ->where('fk_specialist_id',$master_specialits_id)
                                ->whereNull('deleted_at')
                                ->get();

                 
                    if(!empty($masterwithoutAssingedExam1) && sizeof($masterwithoutAssingedExam1)>0)
                    {
                         foreach($masterwithoutAssingedExam1 as $key=>$value)
                        {
                            $isexistWExam = $this->ExaminationsModel
                                       ->where('fk_specialist_id',$id)
                                       ->where('name',$value->name)
                                        ->whereNull('deleted_at')
                                       ->first();

                           
                            if(!empty($isexistWExam))
                            {
                                    $masterIsSettingSet1 = DB::connection('system')
                                                                ->table("preferred_channels_for_reminders_setting")
                                                                ->where('service_id',$value->id)
                                                                ->whereNull('deleted_at')
                                                                ->orderBy('id','desc')
                                                                ->first();

                                                                

                                    if(!empty($masterIsSettingSet1))
                                    {
                                        

                                        $getMasterExamName = DB::connection('system')
                                                    ->table("examinations")
                                                    ->where('id',$masterIsSettingSet1->recommanded_service_id)
                                                    ->whereNull('deleted_at')
                                                    ->first();

                                                   

                                            if(isset($getMasterExamName) && !empty($getMasterExamName))
                                            {

                                                $masterServiceName = $getMasterExamName->name;

                                                $newOrdinationServiceName = $this->ExaminationsModel->where('name',$masterServiceName)->whereNull('deleted_at')->first();


                                                if(isset($newOrdinationServiceName) && !empty($newOrdinationServiceName))
                                                {

                                                     $isSettingSet1 =  DB::table("preferred_channels_for_reminders_setting")
                                                     ->where("service_id",$isexistWExam->id)
                                                     ->update(['recommanded_service_id',$newOrdinationServiceName->id]);
                                                  
                                                }
                                            }//if
                                    }
                            } //if not empty isexistWExam           
                        }//foreach
                    }//if masterwithoutAssingedExam1 
                    */           
                /*******************************************/

            }//foreach mastergetAppointmentType

        }//if mastergetAppointmentType  



        //start new code here added on 13-may-25
        $mastergetAppointmentType = DB::connection('system')
                                   ->table("appointment_types")
                                   ->where('fk_specialist_id',$master_specialits_id)
                                   // ->where('status',1)
                                   ->whereNull('deleted_at')
                                   ->get();
        //dump($mastergetAppointmentType);                           
        if(!empty($mastergetAppointmentType) && count($mastergetAppointmentType)>0)
        {
             
            foreach ($mastergetAppointmentType as $mat_key => $mat_val) 
            {

                /*******start*new code here********************************/
                Log::info("new code here==>");

                $getmasterAppointmentTypehasDoc = DB::connection('system')
                                ->table("appoinment_type_has_examinations")
                                ->where('appoinment_id',$mat_val->id)
                                ->where('fk_specialist_id',$master_specialits_id)
                                ->whereNull('deleted_at')
                                ->get();

                 Log::info("getmasterAppointmentTypehasDoc==>");
                 Log::info($getmasterAppointmentTypehasDoc);                

                if(!empty($getmasterAppointmentTypehasDoc) && sizeof($getmasterAppointmentTypehasDoc)>0)
                {
                    // Examination(services)
                    foreach ($getmasterAppointmentTypehasDoc as $ate_key => $ate_val) 
                    {

                        // GET EXAMINATION
                        $masterExam = DB::connection('system')
                                ->table("examinations")
                                ->where('id',$ate_val->examination_id)
                                ->whereNull('deleted_at')
                                ->first();

                         Log::info("masterExam==>");
                         //Log::info($masterExam);        
                        
                        if(!empty($masterExam))
                        {
                            Log::info("in masterExam==>");
                            Log::info($masterExam->name);

                            //CHECK EXAM ALLREADY EXIST OR NOT
                            $isexistExam = $this->ExaminationsModel
                                           ->where('fk_specialist_id',$id)
                                           ->where('name',$masterExam->name)
                                           ->first();

                             Log::info("isexistExam==>");
                             //dump($isexistExam);                

                            if(!empty($isexistExam))
                            {
                                Log::info("in isexistExam==>");
                                Log::info($isexistExam->id);

                                $masterIsSettingSet = DB::connection('system')
                                                        ->table("preferred_channels_for_reminders_setting")
                                                        ->where('service_id',$ate_val->examination_id)
                                                        ->whereNull('deleted_at')
                                                        ->first();

                                Log::info("masterIsSettingSet==>");
                               // Log::info($masterIsSettingSet);   

                                if(!empty($masterIsSettingSet))
                                {
                                     Log::info("in masterIsSettingSet==>");
                                      Log::info($masterIsSettingSet->recommanded_service_id);

                                    $getMasterExamName =  DB::connection('system')
                                            ->table("examinations")
                                            ->where('id',$masterIsSettingSet->recommanded_service_id)
                                            ->whereNull('deleted_at')
                                            ->first();

                                     Log::info("getMasterExamName===>");    
                                    //dump($getMasterExamName);

                                    if(isset($getMasterExamName) && !empty($getMasterExamName))
                                    {
                                         Log::info("in getMasterExamName===>");

                                        $masterServiceName = $getMasterExamName->name;
                                        //dump($masterServiceName);

                                        $newOrdinationServiceName = $this->ExaminationsModel->where('name',$masterServiceName)->whereNull('deleted_at')->first();

                                         Log::info("newOrdinationServiceName===>");    
                                        //dump($newOrdinationServiceName);

                                        if(isset($newOrdinationServiceName) && !empty($newOrdinationServiceName))
                                        {
                                            Log::info("in newOrdinationServiceName===>");


                                            $getReminderSetting = DB::table('preferred_channels_for_reminders_setting')
                                            ->where(
                                                'service_id', $isexistExam->id
                                              )->first();

                                             if(isset($getReminderSetting) && !empty($getReminderSetting))
                                             {

                                                Log::info("masterIsSettingSet id===>");
                                                Log::info($masterIsSettingSet->id);

                                                  Log::info("getReminderSetting id===>");
                                                Log::info($getReminderSetting->id);

                                                Log::info("newOrdinationServiceName id===>");
                                                Log::info($newOrdinationServiceName->id);

                                                $update = DB::table('preferred_channels_for_reminders_setting')
                                                    ->where('id', $getReminderSetting->id)
                                                    ->update([
                                                        'recommanded_service_id' => $newOrdinationServiceName->id
                                                    ]);

                                                Log::info("Update query result: " . $update);
                                            }//if isset getReminderSetting
                                    
                                        }
                                    }//if
                            
                                }//if
                            }//if 
                           
                        }//if not empty masterExam   
                    }//foreach
                }//if getmasterAppointmentTypehasDoc 

               /*******new code end here********************************/

            }//foreach new code here
        }//if new code here
        //end new code here added on 13-may-25


    }//end

    public function getAllchecklist($master_specialits_id,$me_id,$s_id,$sp_id,$master_exam_id)
    {
        // GET MASTER ALL CHECKLIST
        $masterExamHasChk = DB::connection('system')
                        ->table("examinations_has_multiple_check_list")
                       // ->where('fk_examinations_id',$me_id)
                        ->where('fk_specialist_id',$master_specialits_id)
                        ->whereNull('deleted_at')
                       // ->where('fk_examinations_id',$master_exam_id)
                        ->get();

        if(!empty($masterExamHasChk) && sizeof($masterExamHasChk)>0)
        {
            foreach ($masterExamHasChk as $mc_key => $mc_value) 
            {
                $masterChk = DB::connection('system')
                            ->table("examinations_check_list")
                            ->where('id',$mc_value->fk_check_list_id)
                            ->where('fk_specialist_id',$master_specialits_id)
                            ->whereNull('deleted_at')
                            ->get();

                if(!empty($masterChk) && sizeof($masterChk)>0)
                {
                    //CHECK check ALLREADY EXIST OR NOT
                    $isexist = $this->CheckListModel
                               ->where('fk_specialist_id',$sp_id)
                               ->where('check_list_name',$masterChk[0]->check_list_name)
                               ->first();
                    if(empty($isexist))
                    {
                        // ORDINATION CHECK list INSERT
                        $newChk = new $this->CheckListModel;
                        $newChk->fk_specialist_id  = $sp_id;
                        $newChk->check_list_name   = $masterChk[0]->check_list_name;
                        $newChk->type_of_checklist = $masterChk[0]->type_of_checklist;
                        $newChk->introduction_text = $masterChk[0]->introduction_text;
                        $newChk->final_name        = $masterChk[0]->final_name;
                        $newChk->frequency         = $masterChk[0]->frequency;
                        $newChk->frequency_type    = $masterChk[0]->frequency_type;
                        $newChk->date_of_last_activation = $masterChk[0]->date_of_last_activation;
                        $newChk->status            = $masterChk[0]->status;
                        if($newChk->save())
                        {
                            // coonection of the check list and examination

                            $existRec = $this->ExaminationsHasMultipleCheckListModel
                                             ->where('fk_examinations_id',$s_id)
                                             ->where('fk_check_list_id',$newChk->id)
                                             ->where('fk_specialist_id',$sp_id)
                                             ->first();
                            if(empty($existRec))
                            {
                                $masterrec = DB::connection('system')
                                            ->table("examinations_has_multiple_check_list")
                                            ->where('fk_check_list_id',$mc_value->fk_check_list_id)
                                            ->where('fk_examinations_id',$master_exam_id)
                                            ->where('fk_specialist_id',$master_specialits_id)
                                            ->whereNull('deleted_at')
                                            ->first();
                                if(!empty($masterrec))
                                {
                                    $hasmultiplchk = new $this->ExaminationsHasMultipleCheckListModel;
                                    $hasmultiplchk->fk_examinations_id = $s_id;
                                    $hasmultiplchk->fk_check_list_id   = $newChk->id;
                                    $hasmultiplchk->fk_specialist_id   = $sp_id;
                                    $hasmultiplchk->save(); 
                                }
                               
                            }                 
                            

                            $masterChkHeading = DB::connection('system')
                                ->table("check_list_has_heading_section")
                                ->where('fk_check_list_id',$masterChk[0]->id)
                                ->whereNull('deleted_at')
                                ->get();

                            if(!empty($masterChkHeading) && sizeof($masterChkHeading)>0)
                            {
                                foreach ($masterChkHeading as $ch_key => $ch_value) 
                                {
                                    // ORDINATION HEADING
                                    $newChkHeading = new $this->CheckListHasHeadingSectionModel;
                                    $newChkHeading->fk_check_list_id = $newChk->id;
                                    $newChkHeading->heading_section  = $ch_value->heading_section;
                                    $newChkHeading->status           = $ch_value->status;

                                    if($newChkHeading->save())
                                    {
                                        $masterChkHeadingQus = DB::connection('system')
                                        ->table("heading_has_question")
                                        ->where('fk_check_list_heading_section_id',$ch_value->id)
                                        ->whereNull('deleted_at')
                                        ->get();

                                        if(!empty($masterChkHeadingQus) && sizeof($masterChkHeadingQus)>0)
                                        {
                                            foreach ($masterChkHeadingQus as $chq_key => $chq_value) 
                                            {
                                                // ORDINATION QUESTION
                                                $question = new $this->HeadingSectionHasQuestionModel;
                                                $question->fk_check_list_heading_section_id = $newChkHeading->id;
                                                $question->question = $chq_value->question;
                                                $question->save();
                                                //end
                                            }
                                        }
                                    }
                                }
                               
                            }    
                        }
                    }
                    else
                    {
                        $existRec = $this->ExaminationsHasMultipleCheckListModel
                                             ->where('fk_examinations_id',$s_id)
                                             ->where('fk_check_list_id',$isexist->id)
                                             ->where('fk_specialist_id',$sp_id)
                                             ->first();
                        if(empty($existRec))
                        {
                            $masterrec = DB::connection('system')
                                            ->table("examinations_has_multiple_check_list")
                                            ->where('fk_check_list_id',$mc_value->fk_check_list_id)
                                            ->where('fk_examinations_id',$master_exam_id)
                                            ->where('fk_specialist_id',$master_specialits_id)
                                            ->whereNull('deleted_at')
                                            ->first();
                            if(!empty($masterrec))
                            {
                                $hasmultiplchk = new $this->ExaminationsHasMultipleCheckListModel;
                                $hasmultiplchk->fk_examinations_id = $s_id;
                                $hasmultiplchk->fk_check_list_id   = $isexist->id;
                                $hasmultiplchk->fk_specialist_id   = $sp_id;
                                $hasmultiplchk->save(); 
                            }
                            
                        }   
                    }           
                }            
            }
        } 


        //specialist assogned but not assinged by examination
        $get_all_chk_ids = array_column($masterExamHasChk->toArray(), 'fk_check_list_id');
        $masterwithoutAssingedchk = DB::connection('system')
                                    ->table("examinations_check_list")
                                    ->whereNotIn('id',$get_all_chk_ids)
                                    ->where('fk_specialist_id',$master_specialits_id)
                                    ->whereNull('deleted_at')
                                    ->get(); 
        if(!empty($masterwithoutAssingedchk) && sizeof($masterwithoutAssingedchk)>0)
        {
            self::withoutAssingedChk($masterwithoutAssingedchk,$sp_id,$s_id,$master_exam_id);    
        }                            
        //End 

    }

    public function getAllWithoutAssignedchecklist($master_specialits_id,$me_id,$s_id,$sp_id)
    {
       
        $flag = 1;
        $masterChk = DB::connection('system')
                    ->table("examinations_check_list")
                    // ->where('id',$mc_value->fk_check_list_id)
                    ->where('fk_specialist_id',$master_specialits_id)
                    ->whereNull('deleted_at')
                    ->get();
        
        if(!empty($masterChk) && sizeof($masterChk)>0)
        {
            //CHECK check ALLREADY EXIST OR NOT
          
            if(!empty($masterChk) && sizeof($masterChk))
            {
                foreach ($masterChk as $key => $value) 
                {
                    $isexist = $this->CheckListModel
                           ->where('fk_specialist_id',$sp_id)
                           ->where('check_list_name',$value->check_list_name)
                           ->first();
                    
                    // Check if record exists before accessing id
                    if(!empty($isexist))
                    {
                        $chk_id = $isexist->id;
                    }
                    else
                    {
                        $chk_id = null;
                    }
                    
                    if(empty($isexist))
                    {
                        // ORDINATION CHECK list INSERT
                        $newChk = new $this->CheckListModel;
                        $newChk->fk_specialist_id  = $sp_id;
                        $newChk->check_list_name   = $masterChk[0]->check_list_name;
                        $newChk->type_of_checklist = $masterChk[0]->type_of_checklist;
                        $newChk->introduction_text = $masterChk[0]->introduction_text;
                        $newChk->final_name        = $masterChk[0]->final_name;
                        $newChk->frequency         = $masterChk[0]->frequency;
                        $newChk->frequency_type    = $masterChk[0]->frequency_type;
                        $newChk->date_of_last_activation = $masterChk[0]->date_of_last_activation;
                        $newChk->status            = $masterChk[0]->status;
                        if($newChk->save())
                        {
                            $flag = 0;
                            $chk_id = $newChk->id;
                            $masterChkHeading = DB::connection('system')
                                ->table("check_list_has_heading_section")
                                ->where('fk_check_list_id',$masterChk[0]->id)
                                ->whereNull('deleted_at')
                                ->get();

                            if(!empty($masterChkHeading) && sizeof($masterChkHeading)>0)
                            {
                                foreach ($masterChkHeading as $ch_key => $ch_value) 
                                {
                                    // ORDINATION HEADING
                                    $newChkHeading = new $this->CheckListHasHeadingSectionModel;
                                    $newChkHeading->fk_check_list_id = $newChk->id;
                                    $newChkHeading->heading_section  = $ch_value->heading_section;
                                    $newChkHeading->status           = $ch_value->status;

                                    if($newChkHeading->save())
                                    {
                                        $masterChkHeadingQus = DB::connection('system')
                                        ->table("heading_has_question")
                                        ->where('fk_check_list_heading_section_id',$ch_value->id)
                                        ->whereNull('deleted_at')
                                        ->get();

                                        if(!empty($masterChkHeadingQus) && sizeof($masterChkHeadingQus)>0)
                                        {
                                            foreach ($masterChkHeadingQus as $chq_key => $chq_value) 
                                            {
                                                // ORDINATION QUESTION
                                                $question = new $this->HeadingSectionHasQuestionModel;
                                                $question->fk_check_list_heading_section_id = $newChkHeading->id;
                                                $question->question = $chq_value->question;
                                                $question->save();
                                                //end
                                            }
                                        }
                                    }
                                }
                               
                            }    
                        }
                    }
                    else
                    {
                        $flag =0;
                    }

                    if($flag ==0)
                    {
                        $existRec = $this->ExaminationsHasMultipleCheckListModel
                                                 ->where('fk_examinations_id',$s_id)
                                                 ->where('fk_check_list_id',$chk_id)
                                                 ->where('fk_specialist_id',$sp_id)
                                                 ->whereNull('deleted_at')
                                                 ->first();
                                                 
                            if(empty($existRec))
                            {
                                $masterrec = DB::connection('system')
                                            ->table("examinations_has_multiple_check_list")
                                            ->where('fk_check_list_id',$value->id)
                                            ->where('fk_examinations_id',$me_id)
                                            ->where('fk_specialist_id',$master_specialits_id)
                                            ->whereNull('deleted_at')
                                            ->first();
                                           
                                if(!empty($masterrec))
                                {
                                    $hasmultiplchk = new $this->ExaminationsHasMultipleCheckListModel;
                                    $hasmultiplchk->fk_examinations_id = $s_id;
                                    $hasmultiplchk->fk_check_list_id   = $chk_id;
                                    $hasmultiplchk->fk_specialist_id   = $sp_id;
                                    $hasmultiplchk->save(); 
                                }
                               
                            } 
                    }

                }
            }
          
        }            
            
    }

    public function withoutAssingedChk($masterwithoutAssingedchk,$sp_id,$s_id,$master_exam_id)
    {
        foreach ($masterwithoutAssingedchk as $key => $value) 
        {
            //CHECK check ALLREADY EXIST OR NOT
            $isexist = $this->CheckListModel
                       ->where('fk_specialist_id',$sp_id)
                       ->where('check_list_name',$value->check_list_name)
                       ->first();
            if(empty($isexist))
            {
                // ORDINATION CHECK list INSERT
                $newChk = new $this->CheckListModel;
                $newChk->fk_specialist_id  = $sp_id;
                $newChk->check_list_name   = $value->check_list_name;
                $newChk->type_of_checklist = $value->type_of_checklist;
                $newChk->introduction_text = $value->introduction_text;
                $newChk->final_name        = $value->final_name;
                $newChk->frequency         = $value->frequency;
                $newChk->frequency_type    = $value->frequency_type;
                $newChk->date_of_last_activation = $value->date_of_last_activation;
                $newChk->status            = $value->status;
                if($newChk->save())
                {
                    $masterChkHeading = DB::connection('system')
                        ->table("check_list_has_heading_section")
                        ->where('fk_check_list_id',$value->id)
                        ->whereNull('deleted_at')
                        ->get();

                    if(!empty($masterChkHeading) && sizeof($masterChkHeading)>0)
                    {
                        foreach ($masterChkHeading as $ch_key => $ch_value) 
                        {
                            // ORDINATION HEADING
                            $newChkHeading = new $this->CheckListHasHeadingSectionModel;
                            $newChkHeading->fk_check_list_id = $newChk->id;
                            $newChkHeading->heading_section  = $ch_value->heading_section;
                            $newChkHeading->status           = $ch_value->status;

                            if($newChkHeading->save())
                            {
                                $masterChkHeadingQus = DB::connection('system')
                                ->table("heading_has_question")
                                ->where('fk_check_list_heading_section_id',$ch_value->id)
                                ->whereNull('deleted_at')
                                ->get();

                                if(!empty($masterChkHeadingQus) && sizeof($masterChkHeadingQus)>0)
                                {
                                    foreach ($masterChkHeadingQus as $chq_key => $chq_value) 
                                    {
                                        // ORDINATION QUESTION
                                        $question = new $this->HeadingSectionHasQuestionModel;
                                        $question->fk_check_list_heading_section_id = $newChkHeading->id;
                                        $question->question = $chq_value->question;
                                        $question->save();
                                        //end
                                    }
                                }
                            }
                        }
                    }    
                }
            }
           
        }
                
    }

    // 
    public function getAllDocumentlist($master_specialits_id,$me_id,$e_id,$sp_id,$master_exam_id)
    {
        // GET MASTER ALL CHECKLIST
        $masterExamHasDoc = DB::connection('system')
                        ->table("examinations_has_multiple_document_list")
                        // ->where('fk_examinations_id',$me_id)
                        // ->where('fk_examinations_id',$master_exam_id)
                        ->where('fk_specialist_id',$master_specialits_id)
                        ->whereNull('deleted_at')
                        ->get();
        //dump($masterExamHasDoc);     
        if(!empty($masterExamHasDoc) && sizeof($masterExamHasDoc))
        {           
            if(!empty($masterExamHasDoc) && sizeof($masterExamHasDoc)>0)
            {
                foreach ($masterExamHasDoc as $ed_key => $ed_value) 
                {
                    // GET DOCUMENT DETAILS
                    $masterDocDetails = DB::connection('system')
                            ->table("specialist_has_documents")
                            ->where('id',$ed_value->fk_document_list_id)
                            ->whereNull('deleted_at')
                            ->first();
                    if(!empty($masterDocDetails))
                    {
                        //CHECK check ALLREADY EXIST OR NOT
                        $isexist = $this->SpecialistDocumentsModel
                                   ->where('fk_specialist_id',$sp_id)
                                   ->where('name',$masterDocDetails->name)
                                   ->where('type_of_document',$masterDocDetails->type_of_document)
                                   ->first();
                        if(empty($isexist))
                        {
                            // ORDINATION INSERT DOCUMENT
                            $document = new $this->SpecialistDocumentsModel;
                            $document->fk_specialist_id = $sp_id;
                            $document->type_of_document = $masterDocDetails->type_of_document;
                            $document->name             = $masterDocDetails->name;
                            $document->html_text        = $masterDocDetails->html_text;
                            $document->header_image     = $masterDocDetails->header_image;
                            $document->header_image_path= $masterDocDetails->header_image_path;
                            $document->footer_image     = $masterDocDetails->footer_image;
                            $document->footer_image_path= $masterDocDetails->footer_image_path;
                            $document->background_color = $masterDocDetails->background_color;
                            $document->frequency        = $masterDocDetails->frequency;
                            $document->frequency_type   = $masterDocDetails->frequency_type;
                            $document->date_of_last_activation = $masterDocDetails->date_of_last_activation;
                            $document->status           = $masterDocDetails->status;

                            if($document->save())
                            {
                                // ORDINATION INSERT EXAM HAS DOCUMENT
                                $existRec  = $this->ExaminationsHasMultipleDocumentListModel
                                             ->where('fk_examinations_id',$e_id)
                                             ->where('fk_document_list_id',$document->id)
                                             ->where('fk_specialist_id',$sp_id)
                                             ->first();

                                if(empty($existRec))
                                {
                                    $masterrec = DB::connection('system')
                                                ->table("examinations_has_multiple_document_list")
                                                ->where('fk_document_list_id',$ed_value->fk_document_list_id)
                                                ->where('fk_examinations_id',$master_exam_id)
                                                ->where('fk_specialist_id',$master_specialits_id)
                                                ->whereNull('deleted_at')
                                                ->first();
                                    if(!empty($masterrec))
                                    {
                                        $exaHasDoc = new $this->ExaminationsHasMultipleDocumentListModel;
                                        $exaHasDoc->fk_examinations_id  = $e_id;
                                        $exaHasDoc->fk_document_list_id = $document->id;
                                        $exaHasDoc->fk_specialist_id    = $sp_id;
                                        $exaHasDoc->save();
                                    }            
                                   
                                }          
                                // END
                            }
                        }
                        else
                        {
                            // ORDINATION INSERT EXAM HAS DOCUMENT
                            $existRec  = $this->ExaminationsHasMultipleDocumentListModel
                                        ->where('fk_examinations_id',$e_id)
                                        ->where('fk_document_list_id',$isexist->id)
                                        ->where('fk_specialist_id',$sp_id)
                                        ->first();
                            if(empty($existRec))
                            {
                                $masterrec = DB::connection('system')
                                                ->table("examinations_has_multiple_document_list")
                                                ->where('fk_document_list_id',$ed_value->fk_document_list_id)
                                                ->where('fk_examinations_id',$master_exam_id)
                                                ->where('fk_specialist_id',$master_specialits_id)
                                                ->whereNull('deleted_at')
                                                ->first();
                                if(!empty($masterrec))
                                {
                                    $exaHasDoc = new $this->ExaminationsHasMultipleDocumentListModel;
                                    $exaHasDoc->fk_examinations_id  = $e_id;
                                    $exaHasDoc->fk_document_list_id = $isexist->id;
                                    $exaHasDoc->fk_specialist_id    = $sp_id;
                                    $exaHasDoc->save();
                                }    
                            }            
                           
                        }           
                        
                    }        
                }
            } 
        }

        $get_all_doc_ids = array_column($masterExamHasDoc->toArray(), 'fk_document_list_id');
        //dump($get_all_doc_ids);
        $masterwithoutAssingedDoc = DB::connection('system')
                                    ->table("specialist_has_documents")
                                    ->whereNotIn('id',$get_all_doc_ids)
                                    ->where('fk_specialist_id',$master_specialits_id)
                                    ->whereNull('deleted_at')
                                    ->get(); 
                              
        if(!empty($masterwithoutAssingedDoc) && sizeof($masterwithoutAssingedDoc)>0)
        {
            self::withoutAssingedDocument($masterwithoutAssingedDoc,$master_specialits_id,$sp_id,$e_id,$master_exam_id);    
        }                            
        //End                
    }

    public function getAllWithoutAssignedDocumentlist($master_specialits_id,$me_id,$e_id,$sp_id)
    {
        // GET DOCUMENT DETAILS
        $flag = 1;
        $masterDocDetails = DB::connection('system')
                ->table("specialist_has_documents")
                ->where('fk_specialist_id',$master_specialits_id)
                ->whereNull('deleted_at')
                ->get();

        if(!empty($masterDocDetails) && sizeof($masterDocDetails)>0)
        {
            foreach ($masterDocDetails as $key => $value) 
            {
                //CHECK check ALLREADY EXIST OR NOT
                $isexist = $this->SpecialistDocumentsModel
                           ->where('fk_specialist_id',$sp_id)
                           ->where('name',$value->name)
                           ->where('type_of_document',$value->type_of_document)
                           ->first();
                
                // Check if record exists before accessing id
                if(!empty($isexist))
                {
                    $doc_id = $isexist->id;
                }
                else
                {
                    $doc_id = null;
                }
                
                if(empty($isexist))
                {
                    // ORDINATION INSERT DOCUMENT
                    $document = new $this->SpecialistDocumentsModel;
                    $document->fk_specialist_id = $sp_id;
                    $document->type_of_document = $masterDocDetails[0]->type_of_document;
                    $document->name             = $masterDocDetails[0]->name;
                    $document->html_text        = $masterDocDetails[0]->html_text;
                    $document->header_image     = $masterDocDetails[0]->header_image;
                    $document->header_image_path= $masterDocDetails[0]->header_image_path;
                    $document->footer_image     = $masterDocDetails[0]->footer_image;
                    $document->footer_image_path= $masterDocDetails[0]->footer_image_path;
                    $document->background_color = $masterDocDetails[0]->background_color;
                    $document->frequency        = $masterDocDetails[0]->frequency;
                    $document->frequency_type   = $masterDocDetails[0]->frequency_type;
                    $document->date_of_last_activation = $masterDocDetails[0]->date_of_last_activation;
                    $document->status           = $masterDocDetails[0]->status;
                    $document->save();

                    // ORDINATION INSERT EXAM HAS DOCUMENT
                    $flag = 0; 
                    $doc_id = $document->id;     
                    // END
                }
                else
                {
                    $flag = 0;
                }

                if($flag == 0)
                {
                     $existRec  = $this->ExaminationsHasMultipleDocumentListModel
                                 ->where('fk_examinations_id',$e_id)
                                 ->where('fk_document_list_id',$doc_id)
                                 ->where('fk_specialist_id',$sp_id)
                                 ->whereNull('deleted_at')
                                 ->first();

                    if(empty($existRec))
                    {
                        $masterrec = DB::connection('system')
                                    ->table("examinations_has_multiple_document_list")
                                    ->where('fk_document_list_id',$value->id)
                                    ->where('fk_examinations_id',$me_id)
                                    ->where('fk_specialist_id',$master_specialits_id)
                                    ->whereNull('deleted_at')
                                    ->first();
                        if(!empty($masterrec))
                        {
                            $exaHasDoc = new $this->ExaminationsHasMultipleDocumentListModel;
                            $exaHasDoc->fk_examinations_id  = $e_id;
                            $exaHasDoc->fk_document_list_id = $doc_id;
                            $exaHasDoc->fk_specialist_id    = $sp_id;
                            $exaHasDoc->save();
                        }            
                       
                    }    
                }
            }
        }        
    }


    public function withoutAssingedDocument($masterwithoutAssingedDoc,$master_specialits_id,$sp_id,$e_id,$master_exam_id)
    {
        if(!empty($masterwithoutAssingedDoc) && sizeof($masterwithoutAssingedDoc))
        {
            foreach ($masterwithoutAssingedDoc as $ed_key => $ed_value) 
            {
                // GET DOCUMENT DETAILS
                if(!empty($masterwithoutAssingedDoc) && sizeof($masterwithoutAssingedDoc)>0)
                {
                    //CHECK check ALLREADY EXIST OR NOT
                    $isexist = $this->SpecialistDocumentsModel
                               ->where('fk_specialist_id',$sp_id)
                               ->where('name',$ed_value->name)
                               ->where('type_of_document',$ed_value->type_of_document)
                               ->first();

                    if(empty($isexist))
                    {
                        // ORDINATION INSERT DOCUMENT
                        $document = new $this->SpecialistDocumentsModel;
                        $document->fk_specialist_id = $sp_id;
                        $document->type_of_document = $ed_value->type_of_document;
                        $document->name             = $ed_value->name;
                        $document->html_text        = $ed_value->html_text;
                        $document->header_image     = $ed_value->header_image;
                        $document->header_image_path= $ed_value->header_image_path;
                        $document->footer_image     = $ed_value->footer_image;
                        $document->footer_image_path= $ed_value->footer_image_path;
                        $document->background_color = $ed_value->background_color;
                        $document->frequency        = $ed_value->frequency;
                        $document->frequency_type   = $ed_value->frequency_type;
                        $document->date_of_last_activation = $ed_value->date_of_last_activation;
                        $document->status           = $ed_value->status;
                        $document->save();

                           
                    }          
                        // END
                       
                }
            } 
        }       
    }

    public function importDeleteRecord($diff_value)
    {
        // TODO: Replace with Stancl tenancy logic
        // $website   = \Hyn\Tenancy\Facades\TenancyFacade::website();
        
        // Temporary solution - get ordination_id from session or request
        $tenant = tenancy()->tenant;
        $ordination_id = $tenant->ordination_id ?? 1;
        // $ordination_id = session('ordination_id') ?? request('ordination_id') ?? 1;
        $websiteId = 1; // Temporary fallback
        $isexistOrdination = $this->OrdinationHasSpecialistModel
                             ->where('ordination_id',$ordination_id)
                             ->where('specialist_id',$diff_value)
                             ->first();

        if(!empty($isexistOrdination))
        {  
            $DeleteExistOrdination = $this->OrdinationHasSpecialistModel
                             ->where('ordination_id',$ordination_id)
                             ->where('specialist_id',$diff_value)
                             ->delete();
            
            if($DeleteExistOrdination)
            {
                //DELETE SPECIALIST FOR SECIALIST TABEL
                $masterSpecilistDetails = DB::connection('system')
                                       ->table("specialist")
                                       ->where('id',$diff_value)
                                       ->whereNull('deleted_at')
                                       ->first();

                $deletExistSpecilist = $this->BaseModel
                                       ->whereNull('deleted_at')
                                       ->where('name',$masterSpecilistDetails->name)
                                       ->first();

                $new_specialist_id = $deletExistSpecilist->id;
                // examinations                      
                $delExm = $this->BaseModel
                         ->where('id',$new_specialist_id)
                         ->delete(); 

                if(!empty($deletExistSpecilist))
                {
                    // appointment_types
                    $delAppoTyp = $this->AppointmentTypesModel
                                  ->where('fk_specialist_id',$new_specialist_id)
                                  ->delete();

                    if($delAppoTyp)
                    {
                        // appoinment_type_has_examinations
                        $delAppoTypHasExam = $this->AppointmentTypeHasExaminationsModel
                                  ->where('fk_specialist_id',$new_specialist_id)
                                  ->delete();

                        if($delAppoTypHasExam)
                        {
                            // Examination
                             $delExam = $this->ChannelsRemindersSettingModel
                                        ->where('service_id',$new_specialist_id)
                                        ->delete();

                            $delExamSettings = $this->ExaminationsModel
                                  ->where('fk_specialist_id',$new_specialist_id)
                                  ->delete();      
                            if($delExam)
                            {
                                // examinations_has_multiple_check_list
                                $delExmHasmulchk = $this->ExaminationsHasMultipleCheckListModel
                                      ->where('fk_specialist_id',$new_specialist_id)
                                      ->delete();

                                if($delExmHasmulchk)
                                {  
                                    //examinations_check_list
                                    $delchk = self::_deleleAllchk($new_specialist_id); 
                                } 

                                //document delete
                                $delExmHasmulDoc = $this->ExaminationsHasMultipleDocumentListModel
                                                   ->where('fk_specialist_id',$new_specialist_id)
                                                   ->delete();

                                if($delExmHasmulDoc)
                                {  
                                    //examinations_check_list
                                    $delchkdoc = self::_deleleAllDoc($new_specialist_id); 
                                } 
                            }      
                            
                            
                        }
                    }              
                }
            }                    
        }

    }

    // 
    public function _deleleAllchk($sp_id)
    {
        $getchk = $this->CheckListModel
                    ->where('fk_specialist_id',$sp_id)
                    ->get();
        if(!empty($getchk) && sizeof($getchk)>0)
        {  
            foreach ($getchk as $delchk_key => $delchk_value) 
            {
                //check_list_has_heading_section
                $delchkheading = $this->CheckListHasHeadingSectionModel
                          ->where('fk_check_list_id',$delchk_value['id'])
                          ->get();

                if(!empty($delchkheading) && sizeof($delchkheading)>0)
                {  
                    foreach ($delchkheading as $hev_key => $hev_value) 
                    { 
                        //heading_has_question
                        $delchkheadingqus = $this->HeadingSectionHasQuestionModel
                                            ->where('fk_check_list_heading_section_id',$hev_value['id'])
                                            ->delete();
                    }
                   
                    //heading delete
                    $deletechkheading = $this->CheckListHasHeadingSectionModel
                                     ->where('id',$hev_value['id'])
                                     ->delete();           
                    
                } 

                $delchkheading = $this->CheckListModel
                                 ->where('id',$delchk_value['id'])
                                 ->delete();      
            }
            
        } 
        return true;
    }

    public function _deleleAllDoc($sp_id)
    {
        $getDoc = $this->SpecialistDocumentsModel
                  ->where('fk_specialist_id',$sp_id)
                  ->delete();

        return true;          
    }

    public function documentsView($encID)
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_DOCUMENTLIST_TEXT');
       $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        
        $this->ViewData['modulePath']   = $this->specialistDocument;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        
        // All examsdata
        $id = base64_decode(base64_decode($encID));
      
        $this->ViewData['collection'] = $this->SpecialistDocumentsModel->find($id);
    
        // view file with data
        return view($this->ModuleView.'documents.document-view', $this->ViewData);  
    }
}
