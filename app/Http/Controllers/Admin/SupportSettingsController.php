<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request; 
use App\Http\Controllers\Controller;

// Models
use App\Models\SupportSettingsModel; 
use App\Models\ActivityLogModel;

// Request
use App\Http\Requests\Admin\SupportSettingsRequest;
use Illuminate\Contracts\Filesystem\Filesystem;
// plugins
use DB; 
use Auth;
use Storage; 
use App\Traits\GeneralTrait;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

class SupportSettingsController extends Controller 
{
    private $BaseModel;
    use GeneralTrait;
    public function __construct(
        SupportSettingsModel $SupportSettingsModel,
        ActivityLogModel $ActivityLogModel
    )
    {
        $this->BaseModel         = $SupportSettingsModel;
        $this->ActivityLogModel  = $ActivityLogModel;

        $this->ViewData = [];
        $this->JsonData = []; 

        $this->ModuleTitle  = __('admin.TITLE_SUPPORT_SETTING_TEXT'); 
        $this->ModuleView   = 'admin.support-settings.';
        $this->ModulePath   = 'admin.support-settings.'; 

        // Permission Middleware
        $this->middleware(['permission:support-setting-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:support-setting-add'], ['only' => ['create','store']]);
    }

    public function index() 
    { 
        // Default site support
        $this->ModuleTitle              = __('admin.TITLE_SUPPORT_SETTING_TEXT'); 
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
        $this->ModuleTitle              = __('admin.TITLE_SUPPORT_SETTING_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        // dd($this->ViewData);
        // view file with data
        return view($this->ModuleView.'create', $this->ViewData);
    }

    public function store(SupportSettingsRequest $request)
    {
        // dd('hii'); 
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_SUPPORT_CREATE'); 

        try {

            $collection     = new $this->BaseModel;   
            $collection     = self::_storeOrUpdate($collection,$request);
            if ($collection) 
            {
                $newData = $collection->toArray();
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has created support setting','Add',null,$newData);
               
                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.SUPPORT_CREATED');
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
        $this->ModuleTitle              = __('admin.TITLE_SUPPORT_SETTING_TEXT'); 
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All userdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['support'] = $this->BaseModel->find($id);
    
        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function update(SupportSettingsRequest $request, $encID)
    {
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SUPPORT_UPDATE');       
              
        try {

            $collection = $this->BaseModel->find($id);   
            $oldData = $collection->toArray();
            $collection = self::_storeOrUpdate($collection,$request);
            $newData = $collection->toArray();
            if ($collection)  
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated support setting','Update',$oldData,$newData);

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.SUPPORT_UPDATED');
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
        $this->JsonData['msg']      = __('admin.FAIL_SUPPORT_DELETE');
        $id = base64_decode(base64_decode($encID));

        $BaseModel = $this->BaseModel->find($id);
        if($BaseModel->delete())
        {
            $newData = $BaseModel->toArray();
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData);

            $this->JsonData['status']   = 'success';
            $this->JsonData['msg']      = __('admin.SUPPORT_DELETED');
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
                1 => 'support_settings.name',
                2 => 'support_settings.url',
                3 => 'support_settings.apk',
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

            # FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {
                if (!empty($request->custom['name'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['name'];                
                    $modelQuery     = $modelQuery
                    ->where('support_settings.name','LIKE','%'.$key.'%');
                }

                if (!empty($request->custom['url'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['url'];                
                    $modelQuery     = $modelQuery
                    ->where('support_settings.url','LIKE','%'.$key.'%');
                }
                if (!empty($request->custom['apk'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['apk'];                
                    $modelQuery     = $modelQuery
                    ->where('support_settings.apk','LIKE','%'.$key.'%');
                }

                if (isset($request->custom['status'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('support_settings.status', $key);
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
                        $query->orwhere('support_settings.name', 'LIKE', '%'.$search.'%');   
                        $query->orwhere('support_settings.url', 'LIKE', '%'.$search.'%');  
                        $query->orwhere('support_settings.apk', 'LIKE', '%'.$search.'%');    
                        // if(strtolower($search)=="active"){
                        //     $query->orwhere('support_settings.status', '=', 1);
                        // }
                        // else{
                        //     $query->orwhere('support_settings.status', '=', 0);
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

                        $data[$key]['url']          =  "<a download href='".url('/storage/app/guideline-manual/'.$row->url)."' >".$row->url."</a>";
                        $data[$key]['apk']          =  "<a download href='".url('/storage/app/guideline-manual/'.$row->apk)."'>".$row->apk."</a>";

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
                        if(auth()->user()->hasRole('super-admin')){
                            if(auth()->user()->can('support-setting-add')){
                                $edit = '<a href="'.route($this->ModulePath.'edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                                $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route($this->ModulePath.'destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>';
                            }
                        }

                        $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';                   
                } 
            }

            ## SEARCH HTML
            $searchHTML['id']       =  '';   
            $searchHTML['name']     =  '<input type="text" class="form-control" id="name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['url']      =  '<input type="text" class="form-control" id="url" value="'.($request->custom['url'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['apk']      =  '<input type="text" class="form-control" id="apk" value="'.($request->custom['url'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

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
        $collection->status     = !empty($request->status)?1:0;
        $file = $request->file;
        $apk_file = $request->apk_file;
        if($file)
        {
            $path = 'guideline-manual';
            $original_file  = strtolower($file->getClientOriginalName());
            $extension      = strtolower($file->getClientOriginalExtension());
            $filename    = date('YmdHis').'-'.$original_file;
            $filePath  = url('/storage/app/guideline-manual/'.$filename);
            //$fileStorePath = Storage::putFileAs($path, $file, $filename);
            //$fileStorePath   = Storage::disk('tenant')->putFileAs($path, $file, $filename);

            //$fileStorePath = self::putFilePath($path, $file, $filename); //commented on 11-march-25
            $fileStorePath = self::putFilePathManual($path, $file, $filename);//changed on 11-march-25


           
            $collection->url     = $filename;
               //dd(storage_path().'/app/guideline-manual/'.$request->old_file);
            $oldFilep = self::getFilePath('guideline-manual/');
            if(is_file($oldFilep.$request->old_file))
            {
                $old_file_path = self::unlinkFilePath('/guideline-manual/'.$request->old_file);
                unlink($old_file_path);
                // unlink(storage_path().'/app/guideline-manual/'.$request->old_file);
            }  
            
        } 
        //APK file uload
        if($apk_file)
        {
            $path = 'guideline-manual';
            $original_file  = strtolower($apk_file->getClientOriginalName());
            $extension      = strtolower($apk_file->getClientOriginalExtension());
            $filename    = date('YmdHis').'-'.$original_file;
            $filePath  = url('/storage/app/guideline-manual/'.$filename);
            //$fileStorePath = Storage::putFileAs($path, $apk_file, $filename);
            // $fileStorePath   = Storage::disk('tenant')->putFileAs($path, $apk_file, $filename);

            // $fileStorePath = self::putFilePath($path, $apk_file, $filename);//commented on 11-march-25
            $fileStorePath = self::putFilePathManual($path, $apk_file, $filename);//changed on 11-march-25

            

            $collection->apk     = $filename;
               //dd(storage_path().'/app/guideline-manual/'.$request->old_file);
            // if(is_file(storage_path().'/app/guideline-manual/'.$request->old_file))
            // {
            //     unlink(storage_path().'/app/guideline-manual/'.$request->old_file);
            // } 
            $oldFilep = self::getFilePath('guideline-manual/');
            if(is_file($oldFilep.$request->apk_old_file))
            {
                $apk_file_path = self::unlinkFilePath('/guideline-manual/'.$request->apk_old_file);
                unlink($apk_file_path);
                // unlink(storage_path().'/app/guideline-manual/'.$request->old_file);
            }  
        } 
        
        //Save data
        $collection->save();

        return $collection;        
    }

} 
