<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

// Models
use App\Models\AdminUserModel;
use App\Models\RoleHasPermissionsModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\ActivityLogModel;

use App\Http\Requests\Admin\PermissionsRequest;
use App\Traits\GeneralTrait;
use Artisan, Log;

class PermissionsController extends Controller
{
    use GeneralTrait;
    private $BaseModel;
    private $ViewData;
    private $JsonData;
    private $ModuleTitle;
    private $ModuleView;
    private $ModulePath;

    public function __construct(
        AdminUserModel $AdminUserModel,
        Role $RoleModel,
        Permission $Permission,
        RoleHasPermissionsModel $RoleHasPermissionsModel,
        ActivityLogModel $ActivityLogModel
    )
    {
        $this->BaseModel    = $Permission;
        $this->RoleModel    = $RoleModel;
        $this->RoleHasPermissionsModel = $RoleHasPermissionsModel;
        $this->ActivityLogModel  = $ActivityLogModel;

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle =  __('admin.TITLE_PERMISSION_MODULE');
        $this->ModuleView  = 'admin.permissions.';
        $this->ModulePath = 'admin.permissions';

        // Permission Middleware
        $this->middleware(['permission:manage-permissions'], ['only' => ['index']]);
    }
    
    public function index()
    {
        Artisan::call('cache:clear');
        // Default site settings
        $this->ModuleTitle =  __('admin.TITLE_PERMISSION_MODULE');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;

        // All userdata
        $this->ViewData['users'] = $this->BaseModel->orderBy('id', 'DESC')->get();
        // dd($this->ViewData);
        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }

    public function byRole(Request $request)
    {
        $id = base64_decode(base64_decode($request->id));
        Log::info('role id '.$id);
        $this->JsonData['object'] = $this->RoleHasPermissionsModel
                                        ->with(['permissions'])
                                        ->where('role_id', $id)
                                        ->get(['permission_id']);   
        Log::info('permissions data '.print_r($this->JsonData['object']->toArray(),true));
        return response()->json($this->JsonData);
    }   

    public function getRole(Request $request)
    {
        $options = '';

        $rolesCollection = $this->RoleModel->where('name', '!=', 'super-admin')->orderBy('name', 'ASC')->get();
        if(!empty($rolesCollection) && sizeof($rolesCollection))
        {
            $options = '<option value="" >Please select</option>';
            
            foreach ($rolesCollection as $key => $value) 
            {
                $options.= '<option value="'.base64_encode(base64_encode($value->id)).'" >'.ucfirst($value->name).'</option>';
            }

        }

        echo $options;
    } 
   
    public function create()
    {
        //
    }

    public function store(PermissionsRequest $request)
    {
        Log::info('in store function');
         // flush permission cache added on 16-feb-24
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Log::info('after flush permission cache');
        //dd($request->all());
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] = __('admin.FAIL_PERMISSION_UPDATE');

        $role_id  = base64_decode(base64_decode($request->role));
        Log::info('role id '.$role_id);

        $role     = $this->RoleModel->find($role_id);
        // dd($role);
        $role->syncPermissions($request->except(['role']));
        Log::info('after sync permission');
        Log::info('role data '.print_r($role->toArray(),true));
         // Activity Log
        if ($role) 
        {
            Log::info('in activity log');
            Log::info('role data '.print_r($role->toArray(),true));
            // dd($role->toArray());s
            // die;
            $newData = [];
            $newData['id'] = $role->id;  
            $newData['name'] = $role->name; 
            $newData['identifier'] = $role->identifier;
            $newData['guard_name'] = $role->guard_name; 
            $newData['created_at'] = date("Y-m-d H:i:s",strtotime($role->created_at));
            $newData['updated_at'] = date("Y-m-d H:i:s",strtotime($role->updated_at));
            $permissions = $role->permissions;

            Log::info('permissions data '.print_r($permissions->toArray(),true));
            // print_r($permissions);
            // die;
            // $key = 0;
            // foreach ($permissions as $permission) {
            //     // $permission->name);
            //     // $key++;
            //     $newData[] .= $permission;
            // }
            // dd($newData);
            // $newData['permission_id'] = $role->permissions->name;
            // dd($newData);
          
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has created permission','Add',null,$newData);
                Log::info('after activity log');
                Log::info('new data '.print_r($newData,true));
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            $this->JsonData['msg'] =  __('admin.PERMISSION_UPDATED');
        }

        // flush permission cache
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json($this->JsonData);

    }

    public function show($id) 
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
