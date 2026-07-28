<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request; 
use App\Http\Controllers\Controller;

// Models
use App\Models\MenusSettingsModel; 
use App\Models\ActivityLogModel;

// Request
use App\Http\Requests\Admin\MenuSettingsRequest;

// plugins
use DB; 
use Auth;
use App\Traits\GeneralTrait;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

class MenuSettingsController extends Controller 
{
    use GeneralTrait;
    private $BaseModel;

    public function __construct(
        MenusSettingsModel $MenusSettingsModel,
        ActivityLogModel $ActivityLogModel
    )
    {
        $this->BaseModel         = $MenusSettingsModel;
        $this->ActivityLogModel  = $ActivityLogModel;

        $this->ViewData = [];
        $this->JsonData = []; 

        $this->ModuleTitle  = __('admin.TITLE_MENU_SETTING_TEXT'); 
        $this->ModuleView   = 'admin.menus-settings.';
        $this->ModulePath   = 'admin.menus-settings.'; 

        // Permission Middleware
        $this->middleware(['permission:menu-setting-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:menu-setting-add'], ['only' => ['create','store']]);
    }

    public function index() 
    { 
        // Default site menus
        $this->ModuleTitle              = __('admin.TITLE_MENU_SETTING_TEXT'); 
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
        $this->ModuleTitle              = __('admin.TITLE_MENU_SETTING_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        // dd($this->ViewData);
        // view file with data
        return view($this->ModuleView.'create', $this->ViewData);
    }

    public function store(MenuSettingsRequest $request)
    {
        // dd('hii'); 
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_MENU_CREATE'); 

        try {

            $collection     = new $this->BaseModel;   
            $collection     = self::_storeOrUpdate($collection,$request);
            if ($collection) 
            {
                $newData = $collection->toArray();
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has created menu setting','Add',null,$newData);
               
                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.MENU_CREATED');
            }
        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
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
        $this->ModuleTitle              = __('admin.TITLE_MENU_SETTING_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All userdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['menus'] = $this->BaseModel->find($id);
    
        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function update(MenuSettingsRequest $request, $encID)
    {
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_MENU_UPDATE');       
              
        try {

            $collection = $this->BaseModel->find($id);   
            $oldData = $collection->toArray();
            $collection = self::_storeOrUpdate($collection,$request);
            $newData = $collection->toArray();
            if ($collection)  
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated menu setting','Update',$oldData,$newData);

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.MENU_UPDATED');
            }
            
        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function destroy($encID)
    {
        $this->JsonData['status']   = 'error';
        $this->JsonData['msg']      = __('admin.FAIL_MENU_DELETE');
        $id = base64_decode(base64_decode($encID));

        $BaseModel = $this->BaseModel->find($id);
        if($BaseModel->delete())
        {
            $newData = $BaseModel->toArray();
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData);

            $this->JsonData['status']   = 'success';
            $this->JsonData['msg']      = __('admin.MENU_DELETED');
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
                1 => 'menus_settings.name',
                2 => 'menus_settings.url',
                3 => 'status',
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
                if (!empty($request->custom['name'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['name'];                
                    $modelQuery     = $modelQuery
                    ->where('menus_settings.name','LIKE','%'.$key.'%');
                }

                if (!empty($request->custom['url'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['url'];                
                    $modelQuery     = $modelQuery
                    ->where('menus_settings.url','LIKE','%'.$key.'%');
                }

                if (isset($request->custom['status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('menus_settings.status', $key);
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
                        $query->orwhere('menus_settings.name', 'LIKE', '%'.$search.'%');   
                        $query->orwhere('menus_settings.url', 'LIKE', '%'.$search.'%');    
                        // if(strtolower($search)=="active"){
                        //     $query->orwhere('menus_settings.status', '=', 1);
                        // }
                        // else{
                        //     $query->orwhere('menus_settings.status', '=', 0);
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

                        $data[$key]['name']         = '<span title="'.$row->name.'">'.$row->name.'</span>';   

                        $data[$key]['url']          =  "<span title='".$row->url."'>".$row->url."</span>";

                        if (!empty($row->status)) 
                        {
                            $data[$key]['status']   = '<span class="theme-green semibold text-center f-18" >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</span>';
                        }
                        else 
                        {
                            $data[$key]['status']   = '<span class="theme-black-light semibold text-center f-18" >'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</span>';
                        }

                        $edit="";
                        $delete="";

                        // Check Permission
                        if(auth()->user()->can('menu-setting-add')){
                            $edit = '<a href="'.route($this->ModulePath.'edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                            $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route($this->ModulePath.'destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>';
                        }

                        $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';                   
                } 
            }

            ## SEARCH HTML
            $searchHTML['id']       =  '';   
            $searchHTML['name']     =  '<input type="text" class="form-control" id="name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['url']      =  '<input type="text" class="form-control" id="url" value="'.($request->custom['url'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['status']   =  '<select name="status" id="status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_EXAMINATION_STATUS').'</option>
                    <option class="theme-black blue-select" value="1" '.(!empty($request->custom['status']) && $request->custom['status'] == "1" ? 'selected' : '').' >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</option>
                    <option class="theme-black blue-select" value="0" '.(!empty($request->custom['status']) && $request->custom['status'] == "0" ? 'selected' : '').'>'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</option>            
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
        $collection->name       = $request->name;
        $collection->url        = $request->url;
        $collection->status     = !empty($request->status)?1:0;
        $collection->user_id    = Auth::user()->id;

        //Save data
        $collection->save();

        return $collection;        
    }

} 
