<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;

// Models
use App\Models\SettingsModel;
use App\Models\BeaconsModel;
use App\Models\FindingsModel;
use App\Models\ActivityLogModel;
use App\Models\ExportPathModel;
use App\Models\AdminUserModel;
use App\Models\DismissalModel;

// Request
use App\Http\Requests\Admin\SettingsRequest;
use App\Http\Requests\Admin\BeaconsRequest;
use App\Http\Requests\Admin\DismissalRequest;
use App\Http\Requests\Admin\FindingsRequest;
use App\Http\Requests\Admin\ExportPathRequest;
use App\Models\AppointmentHasQueueNumberModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Http\Requests\Admin\ReminderRequest;
use App\Http\Requests\Admin\SmartphoneAppsRequest;
use App\Models\SmartphoneAppsModel;
use Illuminate\Contracts\Filesystem\Filesystem;
use App\Models\PatientsModel;

// plugins
use Hash;
use Mail;
use DB;
use Auth;
use Storage;
use App\Traits\GeneralTrait;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

class SettingController extends Controller
{
    use GeneralTrait;
    private $BaseModel;

    public function __construct(
        SettingsModel $SettingsModel,
        ActivityLogModel $ActivityLogModel,
        AppointmentHasQueueNumberModel $AppointmentHasQueueNumberModel,
        BeaconsModel $BeaconsModel,
        ExportPathModel $ExportPathModel,
        AdminUserModel $AdminUserModel,
        FindingsModel $FindingsModel,
        DismissalModel $DismissalModel,
        ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
        SmartphoneAppsModel $SmartphoneAppsModel,
        PatientsModel $PatientsModel,
        // Hyn tenancy code (commented out)
        // Website $website
    )
    {
        $this->BaseModel        = $SettingsModel;
        $this->ActivityLogModel = $ActivityLogModel;
        $this->AppointmentHasQueueNumberModel = $AppointmentHasQueueNumberModel;
        $this->BeaconsModel = $BeaconsModel;
        $this->ExportPathModel = $ExportPathModel;
        $this->AdminUserModel = $AdminUserModel;
        $this->FindingsModel = $FindingsModel;
        $this->DismissalModel = $DismissalModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->SmartphoneAppsModel = $SmartphoneAppsModel;
        $this->PatientsModel = $PatientsModel;
        // Hyn tenancy code (commented out)
        // $this->website  = $website;

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle  = __('admin.TITLE_SETTING_TEXT');
        $this->ModuleView   = 'admin.settings.';
        $this->ModulePath   = 'admin.settings.';

        // Permission Middleware
        $this->middleware(['permission:setting-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:setting-add'], ['only' => ['create','store']]);
    }

    public function index()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_SETTING_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');
        $this->ViewData['modulePath']   = $this->ModulePath;

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }

    public function create()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_SETTING_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // view file with data
        return view($this->ModuleView.'create', $this->ViewData);
    }

    public function store(SettingsRequest $request)
    {

        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');

        try {

            $collection     = new $this->BaseModel;
            $collection     = self::_storeOrUpdate($collection,$request);
            if ($collection)
            {
                $newData = $collection->toArray();
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has created setting','Add',null,$newData);

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.SETTING_CREATED');
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
        $this->ModuleTitle              = __('admin.TITLE_SETTING_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT'); 

        $this->ViewData['modulePath']   = $this->ModulePath; 
        
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All userdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['setting'] = $this->BaseModel->find($id);

        // dd($this->ViewData['setting']['setting_key']);
        if(($this->ViewData['setting']['setting_key'] == 'APP_LOGGED_OUT_IMAGE_LINK') || ($this->ViewData['setting']['setting_key'] == 'APP_LOGGED_IN_IMAGE_LINK')){
            return view($this->ModuleView.'edit-app-logged-in-out', $this->ViewData);
        }

        if($this->ViewData['setting']['setting_key'] == 'GDPR_CONTENT'){
            return view($this->ModuleView.'edit-gdpr-content', $this->ViewData);
        }

        if($this->ViewData['setting']['setting_key'] == 'DSGVO_LANGTEXT'){
            return view($this->ModuleView.'edit-privacy-content', $this->ViewData);
        }

        if($this->ViewData['setting']['setting_key'] == 'GDPR_SHORT_TEXT'){
            return view($this->ModuleView.'edit-privacy-content', $this->ViewData);
        }

        if($this->ViewData['setting']['setting_key'] == 'AUDIO_SOUND_FILE'){
            return view($this->ModuleView.'edit-audio', $this->ViewData);
        }
        if($this->ViewData['setting']['setting_key'] == 'BEACONS'){
            $this->ViewData['beacons'] = $this->BeaconsModel->get();
            //dd($this->ViewData['beacons']);
            return view($this->ModuleView.'edit-beacons', $this->ViewData);
        }
        if($this->ViewData['setting']['setting_key'] == 'DISMISSAL'){
            $this->ViewData['dismissal'] = $this->DismissalModel->get();
            return view($this->ModuleView.'edit-dismissal', $this->ViewData);
        }
        if($this->ViewData['setting']['setting_key'] == 'FORCED UPDATE FOR SMARTPHONE APPS'){
            $this->ViewData['SmartphoneAppsModel'] = $this->SmartphoneAppsModel
                                                     ->orderBy('id','desc')
                                                     ->limit(1)
                                                     ->first();
            return view($this->ModuleView.'edit-smartphone-apps', $this->ViewData);
        }

        if($this->ViewData['setting']['setting_key'] == 'FINDING_KEYWORDS'){
            $this->ViewData['findings'] = $this->FindingsModel->get();
            return view($this->ModuleView.'edit-findings', $this->ViewData);
        }

        if($this->ViewData['setting']['setting_key'] == 'EXPORT_PATH'){
            $this->ViewData['export_paths'] = $this->ExportPathModel->get();
            $this->ViewData['doctor_list']  = $this->AdminUserModel
                                            ->whereHas('roles',function($query){
                                            $query->where('name', 'doctor');
                                            })
                                            ->get();

            return view($this->ModuleView.'edit-export-path', $this->ViewData);
        }
        if($this->ViewData['setting']['setting_key'] == 'REMINDER_SETTING')
        {
            $this->ViewData['channel_reminders'] = $this->ChannelsRemindersSettingModel->where('type','global')->first();
            return view($this->ModuleView.'edit-channel-reminders', $this->ViewData);
        }


        //start added setting on 18-oct-24
        if($this->ViewData['setting']['setting_key'] == 'WEBBUCHUNG_TEXT_STARTSEITE'){
            return view($this->ModuleView.'edit-privacy-content', $this->ViewData);
        }
        //end added setting on 18-oct-24


        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function update(SettingsRequest $request, $encID)
    {
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');
        // dd($request->setting_value);
        try {

            $collection = $this->BaseModel->find($id);
            // dd($request->setting_value);
            $oldData = $collection->toArray();
            // dd($oldData);
            // $msg = self::_validateSettingValue($request,$id);

            // if(!empty($msg)){
            //     $this->JsonData['msg'] = $msg;
            //     return response()->json($this->JsonData);
            //     exit();
            // }
            $msg = '';
            $setttingKye = $collection->setting_key;
            // dd($setttingKye);
            if($setttingKye == 'APP_LOGGED_MINS'){
                $settingValue = $request->setting_value;
                if (!is_numeric(trim($settingValue))) {
                    // dd('test');
                    $msg =  __('admin.ERR_SETTING_VALUE');
                    // exit();
                }
            }//

            if($setttingKye == 'MINIMUM_AGE')
            {
                $settingValue = $request->setting_value;
                if (!is_numeric($settingValue) || $settingValue < 0) {
                    $msg =  __('admin.ERR_MIMIMUM_AGE');
                }
            }


            if(!empty($msg)){
                $this->JsonData['msg'] = $msg;
                return response()->json($this->JsonData);
                exit();
            }

            $collection = self::_storeOrUpdate($collection,$request);
            // dd($request->setting_value);
            $newData = $collection->toArray();
            // dd($newData);

            if ($collection)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated setting','Update',$oldData,$newData);

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                if(!empty($request->hd_setting_key) && $request->hd_setting_key == 'IS_UPDATED')
                {
                    $this->JsonData['msg']      = __('admin.PASSWORD_SETTING_UPDATED');
                }
                else
                {
                    $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
                }
            }

        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    // public function _validateSettingValue($request,$id=false)
    // {
    //     // dd('test');
    //     $msg = '';
    //     $setttingKye = $request->setting_key;
    //     // dd($setttingKye);
    //     if($setttingKye == 'APP_LOGGED_MINS'){
    //         dd('test');
    //         if (!is_int($request->setting_value)) {
    //             dd('test');
    //             return $msg =  __('admin.ERR_SETTING_VALUE');
    //             exit();
    //         }
    //     }
    // }

    public function destroy($encID)
    {
        $this->JsonData['status']   = 'error';
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_DELETE');

        $id = base64_decode(base64_decode($encID));

        $BaseModel = $this->BaseModel->find($id);
        if($BaseModel->delete())
        {
            $newData = $BaseModel->toArray();
            $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData);
            $this->JsonData['status']   = 'success';
            $this->JsonData['msg']      = __('admin.SETTING_DELETED');
        }

        return response()->json($this->JsonData);
    }
    function getEnglishKeysFromPartialGerman($germanText, $langArray)
    {
        $matches = [];
        foreach ($langArray as $en => $de) {
            if (stripos($de, $germanText) !== false) {
                $matches[] = $en;
            }
        }
        return $matches;
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
                1 => 'settings.setting_key',
                2 => 'settings.setting_value',
                3 => 'settings.description',
                4 => 'settings.status',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel;

           /**********other ordinations***added on 28-aug-24*****************************/
            if (!empty(Config('ordination_id'))) {
                $modelQuery = $modelQuery
                    ->where('settings.setting_key', '!=', 'APP_LOGGED_OUT_IMAGE_LINK')
                    ->where('settings.setting_key', '!=', 'FORCED UPDATE FOR SMARTPHONE APPS') // Roshani added this condition for CR #70 on 4-nov-2024
                    ->where('settings.setting_key', '!=', 'ORDINATION_EMAIL') // Roshani added this condition for CR #126 on 6-nov-2024
                    ->where('settings.setting_key', '!=', 'ORDINATION_MOBILE') // Roshani added this condition for CR #126 on 6-nov-2024
                    ->where('settings.setting_key', '!=', 'APP_LOGGED_OUT_SALUTATION_TEXT') // Roshani added this condition for CR #325 (n) on 22-april-2025
                    ->where('settings.setting_key', '!=', 'APP_LOGGED_MINS'); // Roshani added this condition for CR #325 (o) on 22-april-2025

            }
            //Roshani made the changes for point 309 on 6-aug-2025
            $modelQuery = $modelQuery
                    ->where('settings.setting_key', '!=', 'ADMINISTRATOR_EMAIL')
                    ->where('settings.setting_key', '!=', 'FINDING_KEYWORDS')
                     ->where('settings.setting_key', '!=', 'PREFERRED CHANNEL FOR REMINDERS');//added on 4-feb-26 
            //Roshani made the changes for point 309 on 6-aug-2025

            /*********other ordinations***added on 28-aug-24*******************************/


            // get total count
            $countQuery = clone($modelQuery);
            $totalData  = $countQuery->count();

            ## FILTER OPTIONS for specific field
            $custom_search = false;
            if (!empty($request->custom))
            {
                // if (!empty($request->custom['setting-key']))
                // {
                //     $custom_search  = true;
                //     // $key            = $request->custom['setting-key'];
                //     // $modelQuery     = $modelQuery
                //     // ->where('settings.setting_key','LIKE','%'.$key.'%');
                //    $langArray = Lang::get('admin');
                //     $searchKey = $request->custom['setting-key'] ?? '';
                //     $enKeys = self::getEnglishKeysFromPartialGerman($searchKey, $langArray);

                //     if (!empty($enKeys)) {
                //         // If multiple keys matched, search for any of them
                //         $modelQuery = $modelQuery->where(function($query) use ($enKeys) {
                //             foreach ($enKeys as $key) {
                //                 $query->orWhere('settings.setting_key', 'LIKE', '%' . $key . '%');
                //             }
                //         });
                //     } else {
                //         // Fallback: search as before
                //         $modelQuery = $modelQuery->where('settings.setting_key','LIKE','%'.$searchKey.'%');
                //     }
                // }
                if (!empty($request->custom['setting-key']))
                {
                    $custom_search  = true;
                    // $key            = $request->custom['setting-key'];
                    // $modelQuery     = $modelQuery
                    // ->where('settings.setting_key','LIKE','%'.$key.'%');
                    $langArray = Lang::get('admin');
                    $searchKey = $request->custom['setting-key'] ?? '';
                    $enKeys = self::getEnglishKeysFromPartialGerman($searchKey, $langArray);

                    // Build a query that searches by any matched English keys AND
                    // also always includes the original search term so plain English
                    // keys (or partials like "reminder") are found.
                    $modelQuery = $modelQuery->where(function($query) use ($searchKey, $enKeys) {
                        // include any language-derived English keys
                        if (!empty($enKeys)) {
                            foreach ($enKeys as $key) {
                                $query->orWhere('settings.setting_key', 'LIKE', '%' . $key . '%');
                            }
                        }

                        // always search the original input against the key too
                        $query->orWhere('settings.setting_key','LIKE','%' . $searchKey . '%');
                    });
                }
               
                //Roshani added this searching code on 14-jul-2025
                // if (!empty($request->custom['setting-value']))
                // {
                //     $custom_search  = true;
                //     $key            = $request->custom['setting-value'];
                //     $modelQuery     = $modelQuery
                //     ->where('settings.setting_value','LIKE','%'.$key.'%');
                // }
                if (!empty($request->custom['setting-value'])) {
                    $custom_search = true;

                    // Safely encode keyword to match stored values
                    $key = htmlentities($request->custom['setting-value'], ENT_QUOTES, 'UTF-8');

                    $modelQuery = $modelQuery->where('settings.setting_value', 'LIKE', '%' . $key . '%');
                }
                if (!empty($request->custom['description']))
                {
                    $custom_search  = true;
                    $key            = $request->custom['description'];
                    $modelQuery     = $modelQuery
                    ->where('settings.description','LIKE','%'.$key.'%');
                }
                //Roshani added this searching code on 14-jul-2025

                if (isset($request->custom['status']))
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('settings.status', $key);
                }
            }

            // filter options
            if (!empty($request->search))
            {
                if (!empty($request->search['value']))
                {
                    // $search = $request->search['value'];
                    // $langArray = Lang::get('admin');
                    // $enKeys = self::getEnglishKeysFromPartialGerman($search, $langArray);

                    // $modelQuery = $modelQuery->where(function ($query) use($search, $enKeys)
                    // {
                    //     // Search by English key(s) if German match found
                    //     if (!empty($enKeys)) {
                    //         foreach ($enKeys as $key) {
                    //             $query->orWhere('settings.setting_key', 'LIKE', '%' . $key . '%');
                    //         }
                    //     }
                    //     // Always search value and description as before
                    //     $query->orWhere('settings.setting_value', 'LIKE', '%' . $search . '%');
                    //     $query->orWhere('settings.description', 'LIKE', '%' . $search . '%');
                    // });
                    $search = $request->search['value'];
                    $langArray = Lang::get('admin');
                    $enKeys = self::getEnglishKeysFromPartialGerman($search, $langArray);

                    $modelQuery = $modelQuery->where(function ($query) use($search, $enKeys) {
                        // Search by English key(s) if German match found
                        if (!empty($enKeys)) {
                            foreach ($enKeys as $key) {
                                $query->orWhere('settings.setting_key', 'LIKE', '%' . $key . '%');
                            }
                        }
                        // Always search for the original search term as well
                        $query->orWhere('settings.setting_key', 'LIKE', '%' . $search . '%');
                        $query->orWhere('settings.setting_value', 'LIKE', '%' . $search . '%');
                        $query->orWhere('settings.description', 'LIKE', '%' . $search . '%');
                    });
                    // $search = $request->search['value'];

                    //  $modelQuery = $modelQuery->where(function ($query) use($search)
                    // {

                    //     $query->orwhere('settings.setting_key', 'LIKE', '%'.$search.'%');
                    //     $query->orwhere('settings.setting_value', 'LIKE', '%'.$search.'%');
                    //     //Roshani added this searching code on 14-jul-2025

                    //     $query->orwhere('settings.description', 'LIKE', '%'.$search.'%');
                    //     // if(strtolower($search)=="active"){
                    //     //     $query->orwhere('settings.status', '=', 1);
                    //     // }
                    //     // else{
                    //     //     $query->orwhere('settings.status', '=', 0);
                    //     // }
                    // //Roshani added this searching code on 14-jul-2025
                        
                    // });
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
            // dd($object);
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0)
            {
                foreach ($object as $key => $row)
                {

                        $data[$key]['id']            = $row->id;

                        $setting_title = $row->setting_key;
                        if(Lang::has('admin.'.$row->setting_key))
                        {
                            $setting_title = __('admin.'.$row->setting_key);
                        }
                        $data[$key]['setting_key']   = '<span title="'.ucfirst($setting_title).'">'.$setting_title.'</span>';
                        if(($row->setting_key == 'APP_LOGGED_OUT_IMAGE_LINK') || ($row->setting_key == 'APP_LOGGED_IN_IMAGE_LINK')){
                            $data[$key]['setting_value'] = '<a href="'.$row->setting_value.'">'.$row->setting_value.'</a>';
                        }
                        else if($row->setting_key == 'Import Setting'){
                            $data[$key]['setting_value'] = '<span title="'.$row->setting_value.'">'.strtoupper($row->setting_value).'</span>';
                        }
                        else if($row->setting_key == 'SEND_FINDING_VIA_EMAIL')
                        {
                            $data[$key]['setting_value'] = '<span title="'.$row->setting_value.'">'.strtoupper($row->setting_value).'</span>';
                        }
                        else if($row->setting_key == 'OPTIMAL_APPOINTMENT')
                        {
                            if($row->setting_value==0)
                            {
                                $optimal_val = "Off";
                            }
                            else
                            {
                                 $optimal_val = "On";
                            }

                            $data[$key]['setting_value'] = '<span title="'.$optimal_val.'">'.strtoupper($optimal_val).'</span>';
                        }
                        // Roshani added this code for CR #214 on 24-oct-24
                        else if($row->setting_key == 'SHOW_CYCLE_CALENDAR_IN_APP')
                        {
                            if($row->setting_value==0)
                            {
                                $cycle_cal = "Off";
                            }
                            else
                            {
                                 $cycle_cal = "On";
                            }

                            $data[$key]['setting_value'] = '<span title="'.$cycle_cal.'">'.strtoupper($cycle_cal).'</span>';
                        }
                        // Roshani added this code for CR #214 on 24-oct-24

                        else
                        {
                            $data[$key]['setting_value'] = '<span title="'.$row->setting_value.'">'.$row->setting_value.'</span>';
                        }


                        $data[$key]['description']   =  "<span title='".$row->description."'>".$row->description."</span>";

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
                        if(auth()->user()->can('setting-add')){
                           //  if(($data[$key]['setting_key'] == 'APP_LOGGED_OUT_IMAGE_LINK') || ($data[$key]['setting_key'] == 'APP_LOGGED_IN_IMAGE_LINK')){
                           //     $edit = '<a href="'.route($this->ModulePath.'edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="Edit"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                           // }else{
                            // $edit = '<a href="'.route($this->ModulePath.'edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                           // }
                          /**********start***to edit booking timeframe and optimal app 15-Sep-22 Divya***********/
                           if($row->setting_key == 'BOOKING_TIMEFRAME')
                           {
                             $edit = '<a href="'.route('createBookingTimeframe').'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                           }
                           else
                           {
                               if($row->setting_key == 'OPTIMAL_APPOINTMENT')
                               {
                                 $edit = '<a href="'.route('createOptimalAppointment').'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                               }
                               else if($row->setting_key == 'SHOW_CYCLE_CALENDAR_IN_APP')
                               {
                                    $edit = '<a href="'.route('createCycleCalender').'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                               }
                               else
                               {
                                 $edit = '<a href="'.route($this->ModulePath.'edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
                               }

                           }//else

                            /********end**to edit booking timeframe and optimal app**********/

                            if (is_null(config('ordination_id')) || empty(config('ordination_id'))) {
                              $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route($this->ModulePath.'destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>';
                            }else{
                              $delete = '';
                           }
                        }

                        $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';
                }
            }

             ## SEARCH HTML
            $searchHTML['id']               =  '';
            $searchHTML['setting_key']      =  '<input type="text" class="form-control" id="setting_key" value="'.($request->custom['setting-key'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            //Roshani added this searching code on 14-jul-2025

            $searchHTML['setting_value']    =  '<input type="text" class="form-control" id="setting_value" value="'.($request->custom['setting-value'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['description']      =  '<input type="text" class="form-control" id="description" value="'.($request->custom['description'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            //Roshani added this searching code on 14-jul-2025

            $searchHTML['status']   =  '<select name="status" id="setting_status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_EXAMINATION_STATUS').'</option>
                    <option class="theme-black blue-select" value="1" '.( !empty($request->custom['status']) && $request->custom['status'] == "1" ? 'selected' : '').' >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</option>
                    <option class="theme-black blue-select" value="0" '.( !empty($request->custom['status']) && $request->custom['status'] == "0" ? 'selected' : '').'>'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</option>
                    </select>';

            // $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';
            //Roshani added this searching code on 14-jul-2025

            $seachAction  =  '<div class="text-center d-flex justify-content-center gap-2" style="gap: 5px;">
                <a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a>
                <a style="cursor:pointer;" onclick="return removeSearch(this)" class="btn btn-primary"><span class="fa fa-times"></span></a>
            </div>';
            
            //Roshani added this searching code on 14-jul-2025

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
        if(!empty($request->setting_key)){
         $collection->setting_key  = $request->setting_key;
        }
        // dd($collection->setting_key);
        if(($collection->setting_key == 'APP_LOGGED_OUT_IMAGE_LINK') || ($collection->setting_key == 'APP_LOGGED_IN_IMAGE_LINK')){
            // dd('test');
            $fileName = $filePath = [];
             if(!empty(Config('ordination_id')))
            {
                $getDatabase = DB::connection('system')->table("tenants")
                                    ->where('ordination_id',Config('ordination_id'))->first(['uuid']);
                $imagaPath = url('storage/tenancy/tenants/'.$getDatabase->uuid);
            }
            else{
                $imagaPath = url('storage/');
            }
            if($request->hasfile('setting_value'))
            {
                foreach($request->file('setting_value') as $file)
                {
                    $path = 'setting-value';
                    $original_file  = strtolower($file->getClientOriginalName());
                    $extension      = strtolower($file->getClientOriginalExtension());
                    $filename    = date('YmdHis').'-'.$original_file;
                    $fileName[]  = $filename;
                    //$filePath[]  = url('/storage/app/setting-value/'.$filename);
                    //$fileStorePath = Storage::putFileAs($path, $file, $filename);
                    //$fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $filename);
                    $fileStorePath   = self::putFilePath($path, $file, $filename);
                    $filePath[]  = $imagaPath.'/setting-value/'.$filename;
                    // dd($fileStorePath);
                }
            }
            $collection->setting_value = implode('||', $filePath);
            // dd($collection->setting_value);
        }
        else if(($collection->setting_key == 'AUDIO_SOUND_FILE')){
            // dd('test');
            $fileName = $filePath = [];
            if($request->hasfile('setting_value'))
            {
                foreach($request->file('setting_value') as $file)
                {
                    $path = 'setting-value';
                    $original_file  = strtolower($file->getClientOriginalName());
                    $extension      = strtolower($file->getClientOriginalExtension());
                    $filename    = date('YmdHis').'-'.$original_file;
                    $fileName[]  = $filename;
                    $filePath[]  = url('/storage/app/setting-value/'.$filename);

                    $fileStorePath   = self::putFilePath($path, $file, $filename);
                    //$fileStorePath = Storage::putFileAs($path, $file, $filename);
                    //$fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $filename);
                    // dd($fileStorePath);
                }
            }
            $collection->setting_value = implode('||', $filePath);
            // dd($collection->setting_value);
        }
        else{
            // dd($request->get('setting_value'));
            $collection->setting_value = $request->setting_value;
            // dd($collection->setting_value);
        }
        if(!empty($request->hd_setting_key) && $request->hd_setting_key == 'IS_UPDATED')
        {
            $collection->setting_value = Date('Y-m-d H:i:s');
            self::updatepatientFlag();

        }
        $collection->description   = $request->description;
        $collection->status        = !empty($request->status)?1:0;
        // dd($request->setting_value);
        // dd($collection);
        //echo "<pre>";print_r($collection);exit;
        //Save data
        $collection->save();

        return $collection;

    }
    public function updatepatientFlag()
    {
        $updatepatientFlag = $this->PatientsModel
                             ->where('id', '>', 0)
                             ->update(['is_updated'=>'0']);


        $updateUserFlag = $this->AdminUserModel
                          ->where('id', '>', 0)
                          ->update(['is_updated'=>'0']);
        return 1;
    }
    public function getGDPRDetails()
    {
        $this->ModuleView   = 'web.gdpr-details';
        $gdprDetails = $this->BaseModel
                                                ->where('setting_key', 'GDPR_CONTENT')
                                                ->first(['setting_value']);
        //dd($this->ViewData['gdprDetails']);
        //$data = trim($this->ViewData['gdprDetails']);
        //$gdprData = str_replace (array('[', ']'), '' , $data);
        $gdprData = '';
        if(!empty($gdprDetails)){
            $gdprData = $gdprDetails->setting_value;

        }
        $this->ViewData['gdprDetails'] = $gdprData;
            // dd($aa);
        // return view('GDPR.gdpr-details', $this->ViewData);
        return view($this->ModuleView, compact('gdprData'));
    }


    public function updateBeacons(BeaconsRequest $request, $encID)
    {
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');
        try
        {

            $newData = array();
            // Update status
            $this->BaseModel->where('id',$id)->update(['status'=>$request->status]);
            $oldData = $this->BeaconsModel->get();
            $a = $this->BeaconsModel->truncate();

            foreach($request->uuid as $key=>$value)
            {
               $tmp = array();
               $tmp['beacon_identifier'] = $request->identifier[$key];
               $tmp['beacon_major'] = $request->major[$key];
               $tmp['beacon_minor'] = $request->minor[$key];
               $tmp['beacon_UUID'] = $request->uuid[$key];
               if(isset($request->b_status[$key]) && $request->b_status[$key]!='')
                $tmp['status'] = '1';
               else
                $tmp['status'] = '0';
               $newData[] = $tmp;
               $this->BeaconsModel->insert($tmp);
            }

            if (count($newData)  > 0)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated setting','Update',$oldData,json_encode($newData));

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
            }

        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function updateDismissal(DismissalRequest $request, $encID)
    {
        //dd($request->all());
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');
        try
        {

            $newData = array();
            // Update status
            $this->BaseModel->where('id',$id)->update(['status'=>$request->status]);
            $oldData = $this->DismissalModel->get();
            $a = $this->DismissalModel->truncate();
            //dd($request->dismissal);
            foreach($request->dismissal as $key=>$value)
            {
                //dump($request->b_status);
               $tmp = array();
               $tmp['name'] = $request->dismissal[$key];
               if(isset($request->b_status[$key]) && $request->b_status[$key]!='')
                $tmp['status'] = '1';
               else
                $tmp['status'] = '0';
               $newData[] = $tmp;
               $this->DismissalModel->insert($tmp);
            }

            if (count($newData)  > 0)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated setting','Update',$oldData,json_encode($newData));

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
            }

        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }
       // dd("-->");
        return response()->json($this->JsonData);
    }


    public function updateExportPath(ExportPathRequest $request, $encID)
    {
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');
        try
        {

            $newData = array();
            // Update status
            $this->BaseModel->where('id',$id)->update(['status'=>$request->status]);
            $oldData = $this->ExportPathModel->get();
            $a = $this->ExportPathModel->truncate();

            foreach($request->doctor_id as $key=>$value)
            {
               $tmp = array();
               $tmp['doctor_id'] = $request->doctor_id[$key];
               $tmp['directory_path'] = $request->export_path[$key];
               $newData[] = $tmp;
               $this->ExportPathModel->insert($tmp);
            }

            if (count($newData)  > 0)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated setting','Update',$oldData,json_encode($newData));

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
            }

        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function updateFindings(findingsRequest $request, $encID)
    {
       // dd($request->all());
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');
        try
        {
            $newData = array();

            // Update status
            $this->BaseModel->where('id',$id)->update(['status'=>$request->status]);
            $oldData = $this->FindingsModel->get();
            $a = $this->FindingsModel->truncate();

            foreach($request->keywords as $key=>$value)
            {
               $tmp = array();
               $tmp['keyword'] = $value;
               $tmp['status'] = $request->b_select[$key];
               $newData[] = $tmp;
               $this->FindingsModel->insert($tmp);
            }

            if (count($newData)  > 0)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated setting','Update',$oldData,json_encode($newData));

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
            }

        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function updateReminder(ReminderRequest $request, $encID)
    {

        $id = base64_decode(base64_decode($encID));

        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');
        try
        {

            $newData = array();
            // Update status
            $this->BaseModel->where('id',$id)->update(['status'=>$request->status]);
            $oldData = $this->ChannelsRemindersSettingModel->where('type','global')->get();

            $newData = $this->ChannelsRemindersSettingModel->where('type','global')->orderBy('id','desc')->limit(1)->first();
            $update_all_services = 0;
            if(empty($newData))
            {
                $newData = new $this->ChannelsRemindersSettingModel;
            }else
            {
                if($newData->notify_time!=$request->notify_time)
                {
                    $update_all_services = 1;
                }
            }
           // dd($request->all());
            $newData->choice_of_channels  = $request->choice_of_change;
            $newData->notify_time  = $request->notify_time;
            $newData->holiday_reminder   = !empty($request->recommend_setting)?1:0;
            $newData->reminder_push_notification_text       = $request->default_push_text;
            $newData->reminder_sms_notification_text       = $request->default_sms_text;
            $newData->reminder_mail_notification_text       = $request->default_mail_text;

            $newData->type       = 'global';

            $newData->general_period       = $request->general_period;
            $newData->general_period_frequency_type       = $request->general_period_frequency_type;
            $newData->general_new_frequency       = $request->general_new_frequency;
            $newData->general_new_frequency_type       = $request->general_new_frequency_type;
            $newData->general_first_frequency       = $request->general_first_frequency;
            $newData->general_first_frequency_type       = $request->general_first_frequency_type;
            $newData->general_time_interval       = $request->general_time_interval;
            $newData->general_time_interval_frequency_type       = $request->general_time_interval_frequency_type;
            $newData->general_number_of_interval       = $request->general_number_of_interval;

            /*******added on 28-apr-25**********************************/
            $newData->general_end_cycle       = $request->general_end_cycle;
            $newData->general_end_cycle_frequency_type = $request->general_end_cycle_frequency_type;
             /********added on 28-apr-25***********************************/

            $newData->checkup_period_controls       = $request->checkup_period_controls;
            $newData->checkup_period_frequency_type       = $request->checkup_period_frequency_type;
            $newData->checkup_new_frequency       = $request->checkup_new_frequency;
            $newData->checkup_new_frequency_type       = $request->checkup_new_frequency_type;
            $newData->checkup_first_frequency       = $request->checkup_first_frequency;
            $newData->checkup_first_frequency_type       = $request->checkup_first_frequency_type;
            $newData->checkup_time_interval       = $request->checkup_time_interval;
            $newData->checkup_time_interval_frequency_type       = $request->checkup_time_interval_frequency_type;
            $newData->checkup_number_of_interval       = $request->checkup_number_of_interval;

            /*******added on 28-apr-25**********************************/
            $newData->checkup_end_cycle       = $request->checkup_end_cycle;
            $newData->checkup_end_cycle_frequency_type = $request->checkup_end_cycle_frequency_type;
             /********added on 28-apr-25***********************************/

            $newData->save();
            if($update_all_services == 1)
            {
                $this->ChannelsRemindersSettingModel
                ->where('id','!=',$newData->id)
                ->update(['notify_time'=>$request->notify_time,'is_reminder_updated'=>'1']);
            }
            if ($newData)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated reminder setting','Update',$oldData,json_encode($newData));

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
            }

        } 
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function updateSmartphoneApp(SmartphoneAppsRequest $request, $encID)
    {
        $id = base64_decode(base64_decode($encID));

        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');
        try
        {

            $newData = array();
            // Update status
            $this->BaseModel->where('id',$id)->update(['status'=>$request->status]);
            $oldData = $this->SmartphoneAppsModel->get();

            $newData = $this->SmartphoneAppsModel->orderBy('id','desc')->limit(1)->first();
            //dd($newData);
            $newData->iphone             = $request->iphone;
            $newData->master_data_tablet = $request->master_tablet;
            $newData->waiting_no_tablet  = $request->waiting_no_tablet;
            $newData->singdoc_tablet     = $request->singDoc_tablet;
            $newData->andoid             = $request->andoid;
            $newData->text               = $request->default_text;
            $newData->android_review     = $request->android_review;

            $newData->save();
            if ($newData)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated smartphone apps setting','Update',$oldData,json_encode($newData));

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
            }

        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }
    public function getPrivacyDetails()
    {
        $this->ModuleView   = 'web.privacy-policy';
        $privacyDetails = $this->BaseModel->where('setting_key', 'DSGVO_LANGTEXT')->first(['setting_value']);
        $gdprData = '';
        if(!empty($privacyDetails)){
            $gdprData = $privacyDetails->setting_value;

        }
        $this->ViewData['gdprDetails'] = $gdprData;
        return view($this->ModuleView, compact('gdprData'));
    }

     public function getGDPRshortText()
    {
        $this->ModuleView   = 'web.gdpr-short-text';
        $privacyDetails = $this->BaseModel->where('setting_key', 'GDPR_SHORT_TEXT')->first(['setting_value']);
        $gdprData = '';
        if(!empty($privacyDetails)){
            $gdprData = $privacyDetails->setting_value;

        }
        $this->ViewData['gdprDetails'] = $gdprData;
        return view($this->ModuleView, compact('gdprData'));
    }

    //Added new function for booking time frame 15 Sep by Divya==========================
    public function createBookingTimeframe()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_BOOKING_TIMEFRAME');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle);

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle);

        $this->ViewData['setting'] = $this->BaseModel->where('setting_key','BOOKING_TIMEFRAME')->first();
        // view file with data
        return view($this->ModuleView.'edit-booking-timeframe', $this->ViewData);
    }//

    public function updateBookingTimeframe(Request $request,$encID)
    {
        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');

        try {

            $collection = $this->BaseModel->find($id);
            $oldData = $collection->toArray();
            $msg = '';
            $setttingKye = $collection->setting_key;
            // dd($setttingKye);
            if($setttingKye == 'BOOKING_TIMEFRAME'){
                $settingValue = $request->setting_value;
                if (!is_numeric(trim($settingValue)) || ($settingValue<=0)) {
                    $msg =  __('admin.ERR_BOOKING_TIMEFRAME_VALUE');
                }
            }//if

            if($setttingKye == 'BOOKING_TIMEFRAME')
            {
                 $settingDescription = $request->description;

                 if(($settingDescription=='week') || ($settingDescription=='Week') || ($settingDescription=='month') || ($settingDescription=='Month'))
                 {
                 }else{
                   $msg =  __('admin.ERR_BOOKING_TIMEFRAME_DESC');
                 }
            }//if

            if($setttingKye == 'BOOKING_TIMEFRAME')
            {
                if(isset($request->description) && (($request->description=="month") || ($request->description=="month"))){
                    if($request->setting_value>12){
                         $msg =  __('admin.ERR_BOOKING_TIMEFRAME_VALUE');
                    }
                }//
            }//

            if(!empty($msg)){
                $this->JsonData['msg'] = $msg;
                return response()->json($this->JsonData);
                exit();
            }

            $request->status =1;
            $collection = self::_storeOrUpdate($collection,$request);
            // dd($request->setting_value);
            $newData = $collection->toArray();
            // dd($newData);

            if ($collection)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated setting','Update',$oldData,$newData);

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      = url('/').'/admin/settings/createBookingTimeframe';
                if(!empty($request->hd_setting_key) && $request->hd_setting_key == 'IS_UPDATED')
                {
                    $this->JsonData['msg']      = __('admin.PASSWORD_SETTING_UPDATED');
                }
                else
                {
                    $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
                }
            }

        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }


        return response()->json($this->JsonData);
    }//updateBookingTimeframe

    //Added new function for quarter toggle button
    public function createOptimalAppointment()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_OPTIMAL_APPOINTMENT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle);

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle);

        $this->ViewData['setting'] = $this->BaseModel->where('setting_key','OPTIMAL_APPOINTMENT')->first();

        // view file with data
        return view($this->ModuleView.'edit-quarter-button', $this->ViewData);
    }//

     public function updateOptimal(Request $request,$encID)
    {

        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');
        try {

            $collection = $this->BaseModel->find($id);
            $oldData = $collection->toArray();
            $msg = '';
            $setttingKye = $collection->setting_key;

            if(!empty($msg)){
                $this->JsonData['msg'] = $msg;
                return response()->json($this->JsonData);
                exit();
            }//if

            $request->setting_value =isset($request->setting_value)?1:0;
            $request->description ='Quarter Settings';
            $request->status =1;

            $collection = self::_storeOrUpdate($collection,$request);
            $newData = $collection->toArray();

            if ($collection)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated setting','Update',$oldData,$newData);

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      = url('/').'/admin/settings/createOptimalAppointment';
                if(!empty($request->hd_setting_key) && $request->hd_setting_key == 'IS_UPDATED')
                {
                    $this->JsonData['msg']      = __('admin.PASSWORD_SETTING_UPDATED');
                }
                else
                {
                    $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
                }
            }//if collection

        }//try
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }//catch
        return response()->json($this->JsonData);
    }//updateOptimalDate

     //END new function for booking time frame 15 Sep by Divya==========================



    //added on 28-nov-23
    public function getFaqDetails()
    {
        $this->ModuleView   = 'web.faq';
        $privacyDetails = $this->BaseModel->where('setting_key', 'DSGVO_LANGTEXT')->first(['setting_value']);
        $gdprData = '';
        if(!empty($privacyDetails)){
            $gdprData = $privacyDetails->setting_value;

        }
        $this->ViewData['gdprDetails'] = $gdprData;
        return view($this->ModuleView, compact('gdprData'));
    }//getFaqDetails

    // Roshani added this code for CR #214 on 24-oct-24
    public function createCycleCalender()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_CYCLE_CALENDER');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle);

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle);

        $this->ViewData['setting'] = $this->BaseModel->where('setting_key','SHOW_CYCLE_CALENDAR_IN_APP')->first();

        // view file with data
        return view($this->ModuleView.'edit-cycle-calender', $this->ViewData);
    }//

     public function updateCycleCalender(Request $request,$encID)
    {

        $id = base64_decode(base64_decode($encID));
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');
        try {

            $collection = $this->BaseModel->find($id);
            $oldData = $collection->toArray();
            $msg = '';
            $setttingKye = $collection->setting_key;

            if(!empty($msg)){
                $this->JsonData['msg'] = $msg;
                return response()->json($this->JsonData);
                exit();
            }//if

            $request->setting_value =isset($request->setting_value)?1:0;
            $request->description ='Cycle Calender Settings';
            $request->status =1;

            $collection = self::_storeOrUpdate($collection,$request);
            $newData = $collection->toArray();

            if ($collection)
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated setting','Update',$oldData,$newData);

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      = url('/').'/admin/settings';
                if(!empty($request->hd_setting_key) && $request->hd_setting_key == 'IS_UPDATED')
                {
                    $this->JsonData['msg']      = __('admin.PASSWORD_SETTING_UPDATED');
                }
                else
                {
                    $this->JsonData['msg']      = __('admin.SETTING_UPDATED');
                }
            }//if collection

        }//try
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }//catch
        return response()->json($this->JsonData);
    }//updateOptimalDate

    // Roshani added this code for CR #214 on 24-oct-24

}
