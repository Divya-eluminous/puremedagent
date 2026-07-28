<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use App\Models\RoleHasPermissionsModel; 
use App\Models\ActivityLogModel;

use App\Http\Requests\Admin\RolesRequest; 
use App\Traits\GeneralTrait;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

class RolesController extends Controller
{
    use GeneralTrait;
    private $BaseModel;
    private $ViewData;
    private $JsonData;
    private $ModuleTitle;
    private $ModuleView;
    private $ModulePath;

    public function __construct(
        Role $RoleModel,
        RoleHasPermissionsModel $RoleHasPermissionsModel,
        ActivityLogModel $ActivityLogModel 
    )
    {
        $this->BaseModel = $RoleModel;
        $this->RoleHasPermissionsModel = $RoleHasPermissionsModel;
        $this->ActivityLogModel  = $ActivityLogModel;

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle = __('admin.TITLE_ROLES_MODULE');
        $this->ModuleView  = 'admin.roles.';
        $this->ModulePath = 'admin.roles.';

        // Permission Middleware
        $this->middleware(['permission:manage-roles'], ['only' => ['index', 'create', 'store']]);

    }

    public function index(Request $request)
    {
        // Default site settings
        $this->ModuleTitle = __('admin.TITLE_ROLES_MODULE');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');

        return view($this->ModuleView . 'index', $this->ViewData);
    }

    public function updateRole(RolesRequest $request, $endID)
    {
        $id = base64_decode(base64_decode($endID));

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] =  __('admin.FAIL_ROLE_UPDATE');

        $collection = $this->BaseModel->find($id);
        $oldData = $collection->toArray();
        // dd($request->name);
        // $collection->name = $request->name;
        $collection->identifier = $request->identifier;
        // dd($request->all());
       // $collection->shop_store_type = 1;//For Store Project

        if($collection->save()){
            $newData = $collection->toArray();
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated role','Update',$oldData,$newData);
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            $this->JsonData['msg'] = __('admin.ROLE_UPDATED');
        }

        // flush permission cache
        //app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        
        return response()->json($this->JsonData);
    }

    public function store(RolesRequest $request)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] =  __('admin.FAIL_ROLE_CREATE');
        // $request['shop_store_type']=1;//For Store Project
        //dd($request->all(),$request->only('name','shop_store_type'));
        if($this->BaseModel->create($request->only('name','identifier')))
        {
            $collection = $this->BaseModel->first();
            // dd($collection); 
            $newData = $collection->toArray(); 
            // dd($newData);
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has created role','Add',null,$newData);
                   
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            $this->JsonData['msg'] =  __('admin.ROLE_CREATED'); 
        }

         // flush permission cache
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json($this->JsonData);
    }

    public function destroy($endID)
    { 

        $id = base64_decode(base64_decode($endID));
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] = __('admin.FAIL_ROLE_DELETE');
                                // ->with(['permissions'])
        $role_has_permission = $this->RoleHasPermissionsModel
                                        ->where('role_id', $id)
                                        ->get(['permission_id'])
                                        ->count();   
        //dd($role_has_permission);
        if($role_has_permission>0){
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg'] =  __('admin.FAIL_ROLE_DELETE_ASSIGNED');
        }else{
            $collection = $this->BaseModel->find($id);
            if($collection->delete()){
                $newData = $collection->toArray();
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData);
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['msg'] = __('admin.ROLE_DELETED');
            }
        }
        //$collection->name = $request->name;
        return response()->json($this->JsonData);
    }

    public function getRecords(Request $request)
    {

        /*--------------------------------------
        |  Variables
        ------------------------------*/ 

            // skip and limit
            $start = $request->start;
            $length = $request->length;
            // serach value
            $search = $request->search['value'];
            // order
            $column = $request->order[0]['column'];
            $dir = $request->order[0]['dir'];

            // filter columns
            $filter = array(
                0 => 'roles.id',
                1 => 'roles.name',
                2 => 'roles.identifier',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery = $this->BaseModel
                ->where('name', '!=', 'super-admin');

        
            // get total count
            $countQuery = clone ($modelQuery);
            $totalData = $countQuery->count();

            // filter options
            /*$custom_search = false;
            if (!empty($request->custom)) {
                if (!empty($request->custom['name'])) {
                    $custom_search = true;
                    $key = $request->custom['name'];
                    $modelQuery = $modelQuery
                        ->where('roles.name', '=', $key);
                }
            }*/
             // filter options

            //Aishwarya commented on 12-june-25
            /*if (!empty($request->search))
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];
                    $modelQuery = $modelQuery
                        ->where('roles.name','LIKE', '%'.$search.'%'); 
                }

                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];
                    $modelQuery = $modelQuery
                        ->where('roles.identifier','LIKE', '%'.$search.'%'); 
                }
            }*/
                //Aishwarya added on 12-june-25
                if (!empty($request->search) && !empty($request->search['value'])) 
                {
                    $search = $request->search['value'];
                        $modelQuery = $modelQuery->where(function ($query) use ($search) {
                        $query->where('roles.name', 'LIKE', '%' . $search . '%')
                          ->orWhere('roles.identifier', 'LIKE', '%' . $search . '%');
                        });
                }


            // get total filtered
            $filteredQuery = clone ($modelQuery);
            $totalFiltered = $filteredQuery->count();

            // offset and limit
            if (empty($column)) {
                $columns = explode(',', $filter[$column]);
                foreach ($columns as $key => $tmpcolumn) {
                    $object = $modelQuery->orderBy($tmpcolumn, $dir);
                }
            } else {
                $object = $modelQuery->orderBy($filter[$column], $dir);
            }
            $object = $modelQuery
                ->skip($start)
                ->take($length)
                ->get();

        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];

            if (!empty($object) && sizeof($object) > 0) {
                $count = 1;
                foreach ($object as $key => $row) {
                
                    $order_data = $row->order_date ? date('m-d-Y H:i:s', strtotime($row->order_date)) : '';

                    $data[$key]['id'] = $row->id;
                    
                    $data[$key]['name'] = isset($row->name) ? ucfirst($row->name) : '';  

                    $data[$key]['identifier']   = isset($row->identifier) ? ucfirst($row->identifier) : '';

                    $edit = '<a href="javascript:void(0)" onclick="return editCollection(this)" data-edit="'.__('admin.TITLE_EDIT_TEXT').' ' .__('admin.TITLE_ROLE').'"  data-href="'.route('admin.roles.updateRole', [ base64_encode(base64_encode($row->id))]).'" role-name="'.$data[$key]['name'].'" role-identifier="'.$data[$key]['identifier'].'"class="edit-user action-icon" title="Edit"><span class="fas fa-edit"></span></a>&nbsp&nbsp';  
                    $delete = '<a href="javascript:void(0)" onclick="return deleteCollection(this)" data-href="'.route('admin.roles.destroy', [base64_encode(base64_encode($row->id))]) .'" class="delete-user action-icon" title="Delete"><span class="fas fa-trash"></span></a>';
                    
                    $data[$key]['actions'] =  '';
                    /*if(auth()->user()->can('manage-roles'))
                    {*/
                        $data[$key]['actions'] = '<div class="text-center">' . $edit .$delete. '</div>';
                    // }
                }
            }

           /* $searchHTML['name'] = '<input  name="name" id="name" value="' . ($request->custom['name'] ?? '') . '" type="text" class="form-control break-word" placeholder="Search...">';

            if ($custom_search) 
            {
                $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return removeSearch(this)" class="btn btn-danger">Remove Filter</a></div>';
            }
            else
            {
                $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary">Search</a></div>';
            }
             $searchHTML['name'] ='';
            $searchHTML['actions'] = '';
           
            array_unshift($data, $searchHTML);*/

        // wrapping up
        $this->JsonData['draw'] = intval($request->draw);
        $this->JsonData['recordsTotal'] = intval($totalData);
        $this->JsonData['recordsFiltered'] = intval($totalFiltered);
        $this->JsonData['data'] = $data;

        return response()->json($this->JsonData);
    }
}
