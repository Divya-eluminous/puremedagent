<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

// Models
use App\Models\AdminUserModel;
use Spatie\Permission\Models\Role;
use App\Models\ActivityLogModel;
use App\Models\GoogleColorsModel;
use Illuminate\Contracts\Filesystem\Filesystem;
// Request
use App\Http\Requests\Admin\UsersRequest; 
use App\Http\Requests\Admin\UserUpdatePasswordRequest;
//Trait
use App\Traits\GeneralTrait;
//Mail
use App\Mail\CustomerRegistrationMail; 
use Illuminate\Http\File;
// plugins
use Image;
use Storage;
use Hash;
use Mail;
use DB;
use Auth;
use App\Tenant;
use Session;
use Illuminate\Support\Facades\Log;

use App\Models\ExaminationsModel;
use App\Models\AppointmentTypesModel;
use App\Models\UserHasAppointmentType;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024
use App\Models\CountryCodesModel; // new model for country code lookup



class UsersController extends Controller
{
    private $BaseModel;  
    use GeneralTrait;

    public function __construct(
        AdminUserModel $AdminUserModel,
        ActivityLogModel $ActivityLogModel,
        Role $RoleModel,
        GoogleColorsModel $GoogleColorsModel,
        // Hyn tenancy code (commented out)
        // Website $website,
        ExaminationsModel $ExaminationsModel,
        AppointmentTypesModel $AppointmentTypesModel,
        UserHasAppointmentType $UserHasAppointmentType,
        CountryCodesModel $CountryCodesModel
    )
    {
        $this->BaseModel            = $AdminUserModel;
        $this->RoleModel            = $RoleModel;
        $this->ActivityLogModel     = $ActivityLogModel;
        $this->GoogleColorsModel    = $GoogleColorsModel;
        // Hyn tenancy code (commented out)
        // $this->website  = $website; 
        $this->ExaminationsModel        = $ExaminationsModel;
        $this->AppointmentTypesModel    = $AppointmentTypesModel;
        $this->UserHasAppointmentType   = $UserHasAppointmentType;
        $this->CountryCodesModel = $CountryCodesModel;
        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle = __('admin.TITLE_USERS_MODULE');
        $this->ModuleView  = 'admin.users.';
        $this->ModulePath  = 'admin.users.';

        // Permission Middleware
        $this->middleware(['permission:users-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:users-add'], ['only' => ['create','store']]); 

    }

    public function index() 
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_USERS_MODULE');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');
        $this->ViewData['modulePath']   = $this->ModulePath;

        // $this->ViewData['users'] = $this->BaseModel->orderBy('id', 'DESC')->get();

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }

    public function create()
    { 
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_USERS_MODULE');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All userdata
        $this->ViewData['users'] = $this->BaseModel
                                        ->where('status', 1)
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'doctor');
                                        })
                                        ->get(); 
        // dd($this->ViewData['users']);                                 
        $this->ViewData['colors'] = $this->GoogleColorsModel->get();
        // ############# Roshani Added this code ################# 
        $this->ViewData['appointments']        = $this->AppointmentTypesModel->get();
        // ############# Roshani Added this code ################# 
        // dd($this->ViewData['users']);
        // view file with data
        // prepare country code options for dropdown
        $this->ViewData['country_codes'] = $this->CountryCodesModel
            ->where('is_active',1)
            // ->orderBy('phone_code')
            ->pluck('phone_code')
            ->toArray();
        return view($this->ModuleView.'create', $this->ViewData);
    } 

    public function store(UsersRequest $request)
    {
        // dd($request->all());  
        DB::beginTransaction();
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_USER_CREATE');

        // try {

         // ############# Roshani Added this code on 13-mar-24 ################# 
            //Custom error
            $appoinment_type = $this->AppointmentTypesModel->get()->count();
            if (isset($request->selected_appointment_list) && !empty($request->selected_appointment_list)) {
               $ids_array = explode(",", $request->selected_appointment_list);
                $count = count($ids_array);

                if($count == $appoinment_type)
                {
                    $this->JsonData['status'] = "custom_error_appointment_type";
                    $this->JsonData['msg']    = __('admin.ERR_APP_TYPE_RESTRICTED');
                    return response()->json($this->JsonData);
                }
            }
            // ############# Roshani Added this code on 13-mar-24 ################# 

            $collection     = new $this->BaseModel;   
            // $request->add   = 1; ////Roshani comment this line for #182
            $collection     = self::_storeOrUpdate($collection,$request);

            if ($collection) 
            {

                 // ############# Roshani Added this code #################
                $selected_appointment_list = [];
                if($request->selected_appointment_list){
                    $selected_appointment_list = explode(",", $request->selected_appointment_list);
                }

                //INDIVIUALI ADD Appointment
                $all_transactions = [];
                if (!empty($selected_appointment_list)) 
                {   

                    foreach ($selected_appointment_list as $appointments) 
                    {

                        $examinationObj = new $this->UserHasAppointmentType;
                        $examinationObj->user_id   = $collection->id;
                        $examinationObj->appointment_type_id   = $appointments;
                        
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
                // ############# Roshani Added this code #################


                // $newData = $collection->toArray();
                // attach role
                $role = $this->RoleModel->where('id', base64_decode(base64_decode($request->role)))
                                        ->pluck('name')
                                        ->first();
               
                $newData['doctor_id'] = $collection->doctor_id;
                $newData['first_name'] = $collection->first_name;
                $newData['last_name'] = $collection->last_name;
                $newData['country_code'] = $collection->country_code;
                $newData['mobile_number'] = $collection->mobile_number;
                $newData['email'] = $collection->email;
                $newData['profile_img'] = $collection->profile_img;
                $newData['img_path'] = $collection->img_path;
                $newData['color'] = $collection->color;
                $newData['role'] = $role;
                $newData['google_color_id'] = $collection->google_color_id;
                $newData['status'] = $collection->status;
                $newData['doctor_speciality'] = $collection->doctor_speciality;
                $newData['str_password'] = $collection->str_password;
                $newData['password'] = $collection->password;
                $newData['created_at'] = date("Y-m-d H:i:s",strtotime($collection->created_at));
                $newData['updated_at'] = date("Y-m-d H:i:s",strtotime($collection->updated_at));
                $newData['id'] = $collection->id; 
                // dd($newData);
                try {

                    $collection->assignRole(strtolower($role));

                    $this->ActivityLogModel->addLog($this->ModuleTitle,'has created user','Add',null,$newData);

                    DB::commit();

                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['url']    =  route($this->ModulePath.'index');
                    $this->JsonData['msg']    = __('admin.USER_CREATED');
                    

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
             // flush permission cache
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        // }
        // catch(\Exception $e) {
            
        //     // $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
        //     $this->JsonData['msg'] = $e->getMessage();
        //     $this->JsonData['error_msg'] = $e->getMessage();
        // }

        return response()->json($this->JsonData);
    }

    public function show($id)
    {
        dd('show');
    }

    public function edit($encID)
    {
        // dd(env('APP_URL'));
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_USERS_MODULE');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All userdata
        $id = base64_decode(base64_decode($encID)); 
        $this->ViewData['customer'] = $this->BaseModel->with('assignedColor')->find($id);

        // User as doctor
        $this->ViewData['users'] = $this->BaseModel
                                        ->where('status', 1)
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'doctor');
                                        })
                                        ->get();
        $this->ViewData['colors']   = $this->GoogleColorsModel->get();

         // ############# Roshani Added this code ################# 
        $appoinment_type = $this->BaseModel
                            ->with(['userHasAppointmentTypes'=>function($query){
                                    $query->with(['appointmentTypeAssinedToUser']);
                                }])
                            ->find($id);

        $assigned_appointment_ids = [];
        if(!empty($appoinment_type->userHasAppointmentTypes)){
            $assigned_appointment_ids = array_column($appoinment_type->userHasAppointmentTypes->toArray(), "appointment_type_id");
        } 
        // $this->ViewData['defaultExaminationID'] = $assigned_appointment_ids[0] ?? '';
        $this->ViewData['appointments']        = $this->AppointmentTypesModel->get();
        $this->ViewData['assigned_appointment_ids'] = $assigned_appointment_ids;
        // ############# Roshani Added this code ################# 

        if(!empty(Config('ordination_id')))
        {
            $getDatabase = DB::connection('system')->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))->first(['uuid']);
            $this->ViewData['imagaPath'] = url('storage/tenancy/tenants/'.$getDatabase->uuid);
        }
        else{
            $this->ViewData['imagaPath'] = url('storage/app/public');
        }
        // dd($this->ViewData);
        // view file with data
        $this->ViewData['country_codes'] = $this->CountryCodesModel
            ->where('is_active',1)
            // ->orderBy('phone_code')
            ->pluck('phone_code')
            ->toArray();
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function update(UsersRequest $request, $encID)
    {
        // dd($request->all());
        DB::beginTransaction(); 
        
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] = __('admin.FAIL_USER_CREATE');       
              
        try {
            // ############# Roshani Added this code on 13-mar-24 ################# 
            //Custom error
            $appoinment_type = $this->AppointmentTypesModel->get()->count();
            if (isset($request->selected_appointment_list) && !empty($request->selected_appointment_list)) {
               $ids_array = explode(",", $request->selected_appointment_list);
                $count = count($ids_array);

                if($count == $appoinment_type)
                {
                    $this->JsonData['status'] = "custom_error_appointment_type";
                    $this->JsonData['msg']    = __('admin.ERR_APP_TYPE_RESTRICTED');
                    return response()->json($this->JsonData);
                }
            }
            // ############# Roshani Added this code on 13-mar-24 #################

            $collection = $this->BaseModel->find($id);
            // dd($collection);
            $oldData = $collection->toArray();
            // $request->add = 0;//Roshani comment this line for #182
            $collection = self::_storeOrUpdate($collection,$request);

            if ($collection) 
            {
                $newData = $collection->toArray();
                // attach role
                if(!empty($request->role))
                {
                    $roleCollection = $this->RoleModel->where('id', base64_decode(base64_decode($request->role)))->first();
                    try {

                        $collection->syncRoles(strtolower($roleCollection->name));
                        $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated user','Update',$oldData,$newData);

                         // ############# Roshani Added this code ################# 
                        $all_transactions = [];
                        if($request->selected_appointment_list){
                            $selected_appointment_list = explode(",", $request->selected_appointment_list);
                        }
                        ## ADD PRODUCTION RAW MATERIAL DATA
                        if (!empty($selected_appointment_list))  
                        { 
 
                            //Delete records
                            //except default  examination id ,delete all examination.
                            $getrec = $this->UserHasAppointmentType
                                     ->where('user_id',$collection->id)->delete();
                                     // ->where('appointment_type_id','!=', $request->defaultExaminationID)->delete();
                            
                            foreach ($selected_appointment_list as $pkey => $appointment) 
                            {
                                //check default examination id ,if is not default examination then insert
                                if($request->defaultExaminationID != $appointment)
                                {
                                    $examinationObj = new $this->UserHasAppointmentType;
                                    $examinationObj->user_id   = $collection->id;
                                    $examinationObj->appointment_type_id   = $appointment;
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
                        else
                        {
                             $getrec = $this->UserHasAppointmentType
                                     ->where('user_id',$collection->id)->delete();
                        }
                    // ############# Roshani Added this code ################# 
                        
                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['url']  = route($this->ModulePath.'index');
                        $this->JsonData['msg']  = __('admin.USER_UPDATED');
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
                    $this->JsonData['url'] = route($this->ModulePath.'.index');
                    $this->JsonData['msg'] = __('admin.USER_UPDATED');
                    DB::commit();
                }
            }
            else
            {
                 DB::rollback();
                 $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                 $this->JsonData['error_msg'] = $e->getMessage();
            }
            
            // flush permission cache
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        

            
        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function updatePassword(UserUpdatePasswordRequest $request)
    {
       
        $new_pasword = $request->password;
        if (!empty($new_pasword)) 
        {
            $collection = $this->BaseModel
                        ->where('id', auth()->user()->id)
                        ->where('email', auth()->user()->email)
                        ->first();

            if (!empty($collection)) 
            {

                if (Hash::check($request->old_password, $collection->password))        
                {

                    $collection->password       = Hash::make($new_pasword);
                    //$collection->str_password   = $new_pasword;
                    $collection->is_updated   = '1';
                    if($collection->save())
                    {   
                        //dd("fdsdfds");
                        Session::put('is_updated','1');
                        $this->ActivityLogModel->addLog('Password','has updated password','Update');
                        $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                        $this->JsonData['msg'] = __('admin.CHANGE_PASSWORD_STATUS');
                        $this->JsonData['url'] = url('admin/dashboard');
                    }
                    else
                    {
                        //$this->JsonData['url'] = url('admin/doctor-dashboard');
                        $this->JsonData['status'] = __('admin.RESP_ERROR');
                        $this->JsonData['msg'] = __('admin.FAIL_CHANGE_PASSWORD_STATUS');
                    }
                }
                else
                {
                    //$this->JsonData['url'] = url('admin/doctor-dashboard');
                    $this->JsonData['status'] = __('admin.RESP_ERROR');
                    $this->JsonData['msg'] = __('admin.FAIL_CHANGE_PASSWORD_MATCH');
                }
            }
            else
            {
                //$this->JsonData['url'] = url('admin/doctor-dashboard');
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['msg'] = __('admin.ERR_SESSION_TIMEOUT');
            }
        }
        else
        {
            //$this->JsonData['url'] = url('/');
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg'] = __('admin.ERR_CHANGE_PASSWORD_NEW');
        }
        //dd($this->JsonData);
        
        return response()->json($this->JsonData);
    }

    public function destroy($encID)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_USER_DELETE');

        $id = base64_decode(base64_decode($encID));

        if ((int)$id === (int)auth()->user()->id) 
        {
            $this->JsonData['msg'] = __('admin.FAIL_USER_NOT_DELETE');
            return response()->json($this->JsonData);
            exit;
        }

        $BaseModel = $this->BaseModel->find($id);
        $BaseModel->syncRoles([]);  
        if($BaseModel->delete())
        {
            // dd($BaseModel);
            // $newData = $BaseModel->toArray();
            $newData['doctor_id'] = $BaseModel->doctor_id;
            $newData['first_name'] = $BaseModel->first_name;
            $newData['last_name'] = $BaseModel->last_name;
            $newData['mobile_number'] = $BaseModel->mobile_number;
            $newData['email'] = $BaseModel->email;
            $newData['profile_img'] = $BaseModel->profile_img;
            $newData['img_path'] = $BaseModel->img_path;
            $newData['color'] = $BaseModel->color;
            // $newData['role'] = $BaseModel->role[0];
            $newData['google_color_id'] = $BaseModel->google_color_id;
            $newData['status'] = $BaseModel->status;
            $newData['doctor_speciality'] = $BaseModel->doctor_speciality;
            $newData['str_password'] = $BaseModel->str_password;
            $newData['password'] = $BaseModel->password;
            $newData['created_at'] = date("Y-m-d H:i:s",strtotime($BaseModel->created_at));
            $newData['updated_at'] = date("Y-m-d H:i:s",strtotime($BaseModel->updated_at));
            $newData['id'] = $BaseModel->id;   
            // dd($newData);
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted user','Delete',null,$newData);
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            $this->JsonData['msg'] = __('admin.USER_DELETED');
        }

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
                0 => 'id',
                1 => 'users.first_name',
                2 => 'users.email',
                3 => 'users.mobile_number',
                4 => 'roles.identifier'
                // 5 => 'role',
                // 4 => 'status'
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel
                                ->with(['roles' => function($query){
                                   $query->where('guard_name', 'admin');
                                }]);
                                
            
                     

            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

                 # FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {
                if (!empty($request->custom['name'])) 
                {
                    $name = explode(" ", $request->custom['name']);

                    if(!empty($name[1])){
                        $key[0]         = $name[0];
                        $key[1]         = $name[1];
                        $custom_search  = true;                
                        $modelQuery     = $modelQuery
                        ->where('users.first_name','LIKE','%'.$key[0].'%')
                        ->orWhere('users.last_name','LIKE','%'.$key[1].'%');
                    } else{
                        $key[0]         = $name[0];
                        $custom_search  = true;                
                        $modelQuery     = $modelQuery
                        ->where('users.first_name','LIKE','%'.$key[0].'%');
                    }                    
                }

                if (!empty($request->custom['email'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['email'];                
                    $modelQuery     = $modelQuery
                    ->where('users.email','LIKE','%'.$key.'%');
                }

                if (!empty($request->custom['mobile_number'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['mobile_number'];              
                    $modelQuery     = $modelQuery
                    ->where('users.mobile_number','LIKE','%'.$key.'%');
                }
                if (!empty($request->custom['identifier'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['identifier'];              
                    $modelQuery     = $modelQuery->whereHas('roles', function($query) use($key) {
                                                $query->where('identifier','LIKE','%'.$key.'%');
                                            });
                }
            }

            // filter options
        //Aishwarya commented on 12-june-25
            /*if (!empty($request->search))
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value']; 
                    // dd($search);

                    $modelQuery =  $modelQuery->whereHas('roles', function($query) use($search) {
                                                $query->where('identifier','LIKE','%'.$search.'%');
                                            });

                    // $modelQuery = $modelQuery->where(function ($query) use($search)
                    // {
                    //     $query->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'LIKE', "%".$search."%");  
                    //     $query->orwhere('users.email', 'LIKE', '%'.$search.'%'); 
                    //     $query->orwhere('users.mobile_number', 'LIKE', '%'.$search.'%'); 
                    //     //$query->orwhere('users.name', 'LIKE', '%'.$search.'%');   
                        
                    // });     
                }
            }*/
             //Aishwarya added on 12-june-25
            if (!empty($request->search) && !empty($request->search['value'])) {
                $search = $request->search['value'];

                    $modelQuery = $modelQuery->where(function($query) use ($search) {
                        $query->where('users.first_name', 'LIKE', '%'.$search.'%')
                          ->orWhere('users.email', 'LIKE', '%'.$search.'%')
                          ->orWhere('users.mobile_number', 'LIKE', '%'.$search.'%')
                          ->orWhereHas('roles', function($q) use ($search) {
                              $q->where('identifier', 'LIKE', '%'.$search.'%');
                            });
                    });
            }


            // get total filtered
            $filteredQuery = clone($modelQuery);            
            $totalFiltered  = $filteredQuery->count();
            
            // offset and limit
            if($filter[$column] !='roles.identifier'){
                $object = $modelQuery->orderBy($filter[$column], $dir)
                            ->skip($start)
                            ->take($length)
                            ->get();
            }else{
                // addded by vijay 17/4/2024 because role.identifier giving error for sorting
                
                $object = $modelQuery
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', 'App\\Models\\AdminUserModel')
                    ->orderBy('roles.identifier', $dir)
                    ->select('users.*')
                    ->distinct() 
                    ->skip($start)
                    ->take($length)
                    ->get();

        }
              
            // dd($object);
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row) 
                {
                    if (!$row->hasRole('super-admin') || auth()->user()->hasRole('super-admin')) 
                    {
                        // dd($row);
                        $data[$key]['id']           = $row->id;
                        //users.last_name
                        $data[$key]['name']   = '<span title="'.ucfirst($row->first_name).'">'.ucfirst($row->first_name)." ".ucfirst($row->last_name).'</span>';
                        // $data[$key]['company_name']    = '<span title="'.ucfirst($row->company_name).'">'.ucfirst($row->company_name).'</span>';
                        $data[$key]['email']        = '<a title="'.$row->email.'" href="mailto:'.$row->email.'" target="_blank" >'.strtolower($row->email).'</a>';

                        // $data[$key]['mobile_number']  =  "<a href='tel:".$row->mobile_number."'>".$row->mobile_number."</a>";
                        $intCountryCode = $row->country_code;  
                        $data[$key]['mobile_number']  =  "<span title='".$intCountryCode.$row->mobile_number."'>".$intCountryCode.$row->mobile_number."</span>";
                        $roles = $row->roles;
                        $userRole = '';
                         if(!empty($roles)){
                            $sep = "";
                            foreach ($roles as $role) {

                                $userRole.=  $role->identifier;
                                
                            }
                        }
                        $userRole.= "";
                        $data[$key]['identifier']         = 
                        '<span title="'.ucfirst($userRole).'">'.ucfirst($userRole).'</span>';
                        // $data[$key]['role']         = ucfirst($row->getRoleNames()[0] ?? '');
                        
                        
                        /*if (!empty($row->status)) 
                        {
                            $data[$key]['status'] = '<span class="theme-green semibold text-center f-18" >Active</i></span>';
                        }
                        else
                        {
                            $data[$key]['status'] = '<span class="theme-black-light semibold text-center f-18" >Inactive</i></span>';
                        }*/

                        $edit="";
                        $delete="";

                        // Check Permission
                        if(auth()->user()->can('users-add')){
                            $edit = '<a href="'.route('admin.users.edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                            if(!$row->hasRole('super-admin'))
                            $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.users.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>';
                        }
                        

                        if ((int)$row->id === (int)auth()->user()->id) 
                        {
                            $delete = '';
                        }

                        $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';
                    }
                }
            } 

            ## SEARCH HTML
            
            $searchHTML['id']           =  '';     
            $searchHTML['name']     =  '<input type="text" class="form-control" id="name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['email']        =  '<input type="text" class="form-control" id="email" value="'.($request->custom['email'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['mobile_number']    =  '<input type="text" class="form-control" id="mobile_number" value="'.($request->custom['mobile_number'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';  

            // $searchHTML['role']           =  ''; 
            $searchHTML['identifier']   =  '<input type="text" class="form-control" id="identifier" value="'.($request->custom['identifier'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            // $searchHTML['place']     =  '<input type="text" class="form-control" id="place" value="'.($request->custom['place']).'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            
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
        // dump(app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions());
        //dump($request->all());
        //dd($request->all());  
        $mobile_no = str_replace("-", "", $request->mobile_number);
        $mobile_no = str_replace(" ", "", $request->mobile_number);
        $mobile_no = ltrim($mobile_no,0);

        $collection->doctor_id     = !empty($request->doctor_id)?$request->doctor_id:0; 
        $collection->first_name     = $request->first_name;
        $collection->last_name      = $request->last_name;
        $collection->country_code   = $request->country_code;
        if(!empty($request->format))
        {
           $collection->country_code       = $request->format; 
        }  
        $collection->mobile_number  = $mobile_no;
        $collection->email          = $request->email;
        $collection->status         = isset($request->status) ? $request->status : 0;

        // dd($request->hasfile('file'));
        $allowedfileExtension = [];
        if($request->hasfile('profile_img'))
        {
            $allowedFileExtension=['jpg','png'];
            // dd($allowedFileExtension);
            $file = $request->file('profile_img');
            // dd($file);
            $extension = strtolower($file->getClientOriginalExtension());
            // dd(trim($extension));
            /*$check = in_array($extension,['jpg','png']);
            // dd($check);
            if($check){*/

                $path = 'profile-images';
                $original_file  = strtolower($file->getClientOriginalName());
                $fileName    = date('YmdHis').'-'.$original_file;
                $filePath  = $path.'/'.$fileName;
                //$fileStorePath = Storage::putFileAs($path, $file, $fileName);
                // $fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $fileName);
                //dd($path, $file, $fileName);

                Log::info($path);


                $fileStorePath = self::putFilePath($path, $file, $fileName);
                //dd($fileStorePath);

                $new_img_path = self::StorePath($path.'/crop/');
                //dd($new_img_path);
                if(!file_exists($new_img_path)){
                   
                    Storage::makeDirectory($path.'/crop',0755);
                   // mkdir((storage_path().'/profile_images/crop'), 0755); 
                }
                // dd(storage_path().'/profile_images/'.$fileName);
                // crop image
                $fileCropPath  = $path.'/crop/';
                //dd($path.'/');
                $new_fileName = self::StorePath($path);
                $cropPath=dirname($fileStorePath);
                $requestImagePath = $file->getRealPath() . '.jpg';
                $interventionImage = Image::make($file)->crop(intval($request->input('w')), intval($request->input('h')),intval($request->input('x')), intval($request->input('y')))->encode('jpg');
                $interventionImage->save($requestImagePath);
                Storage::putFileAs($cropPath."/crop/", new File($requestImagePath),$fileName);
                $new_fileCropPath = self::StorePath($fileCropPath);
                $croppath = ($new_fileCropPath.$fileName);
                $collection->profile_img    = $fileName;  
                $collection->img_path       = '/'.$fileCropPath.$fileName;
                $old_img = self::StorePath($request->old_image);
                if(!empty($request->old_image) && is_file($old_img))
                {
                    $old_unlink = self::unlinkFilePath($request->old_image);
                    unlink($old_unlink);

                    $main_image = str_replace("/crop/", "/", $request->old_image);
                    $old_new_img = self::StorePath($main_image);
                    if(!empty($main_image) && is_file($old_new_img))
                    {
                        $main_unlink = self::unlinkFilePath($main_image);
                        unlink($main_unlink);
                    }
                } 
        }
            
        $collection->color              = $request->color;
        $collection->google_color_id    = $request->google_color_id;
       // $collection->status             = 1;//Active
        // dd($collection);
        $collection->doctor_speciality       = $request->doctor_speciality;
        // dump($request->add);
        // if($request->add==1){//Roshani comment this line for #182
            if(!empty($request->password))
            {
                $collection->str_password   = $request->password;
                $collection->password   = Hash::make($request->password);
            }

            /*$phone   = str_replace("-", "",$collection->mobile_number);
            $site_url = url('/');
            $company_name = "";

            $company_id = $collection->company_id;
            $company = "";
            if(!empty($company_id)){
                $company = $this->CompanyModel->find($company_id);
            }

            if(!empty($company->name)){
                $company_name = $company->name;
            }*/
            /*$message = 'Hi '.$collection->name.',Username: '.$collection->email.' Password: '.$collection->str_password;//.' ,Connect us at: '.$site_url
            $message .= " Thanks,".$company_name;
            self::_sendSms($phone,$message);*/

            //Send Mail
           //self::_sendRegisterMail($collection);
       // }////Roshani comment this line for #182
        
        //Save data
        $collection->save();

        if(config('database.default') == 'tenant')
        {
           DB::connection('system')->table('users')->insert(
            [
                'doctor_id'=> $collection->doctor_id,
                'first_name'=> $collection->first_name,
                'last_name'=> $collection->last_name,
                'mobile_number'=> $collection->mobile_number,
                'email'=> $collection->email,
                'status'=> $collection->status,
                'color'=> $collection->color,
                'google_color_id'=> $collection->google_color_id,
                'doctor_speciality'=> $collection->doctor_speciality,
                'str_password'=> $collection->str_password,
                'password'=> $collection->password,
            ]);
        }
        // $collection->assignRole('customer');
        // $collection->syncRoles(['customers']);
        

        return $collection;
        
    }

    public function _generatePassword($length = 20){
      $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.'0123456789';
                //`-=~!@#$%^&*()_+,./<>?;:[]{}\|

      $str = '';
      $max = strlen($chars) - 1;
        for ($i=0; $i < $length; $i++)
            $str .= $chars[mt_rand(0, $max)];

      return $str;
    }

    public function _sendRegisterMail($collection,$company){
        $mail_collection = new $collection;
        $mail_collection->contact_name = $collection->contact_name;
        $mail_collection->email = $collection->email;
        $mail_collection->str_password = $collection->str_password;
        
        if(!empty($company->logo) && is_file(storage_path().'/app/'.$company->logo)){
            $logo = 'storage/app/'.$company->logo;
        }else{
            $logo = 'assets/admin/images/logo.jpg';
        }
        $mail_collection->logo = url($logo);

        $mail_collection->company_name = "";
        if(!empty($company->name)){
            $mail_collection->company_name = $company->name;
        }
        $mail_collection->adminmail = config('constants.ADMINEMAIL');
        $mail_collection->login_url = url('/admin/login');
        $mail_collection->company_url = url('/');

        $result = Mail::to($mail_collection->email)->send(new CustomerRegistrationMail($mail_collection));
    }

    public function updateLanguage(Request $request)
    {
        // dd($request->all());
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] = __('admin.FAIL_LANGUAGE_STATUS');

        $lang = 'de';
        if(!empty($request->lang)){
            $lang = $request->lang;
        }
        
        Session(['locale' => $lang]);//Set Lanuguage
       

       $this->JsonData['status'] = __('admin.RESP_SUCCESS');
       $this->JsonData['msg'] = __('admin.CHANGE_LANGUAGE_STATUS');

        return response()->json($this->JsonData);
    }
}


