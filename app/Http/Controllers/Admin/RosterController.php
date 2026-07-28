<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;  
use Illuminate\Support\Facades\Lang;
use Carbon\Carbon;

// Models
use App\Models\RosterModel; 
use App\Models\AdminUserModel;
use App\Models\ActivityLogModel;
use App\Models\AppointmentModel;  
use App\Models\AppointmentTypesModel;  
use App\Models\WeekDaysModel;
use App\Models\RosterHasDatesModel;
use App\Models\RosterHasDatesHasTimeFramesModel;
use App\Models\RosterHasWeeksHasTimeFramesModel;
use App\Models\SettingsModel;

// Request 
use App\Http\Requests\Admin\RosterRequest;

// plugins
use Auth;
use DB;
use App\Traits\GeneralTrait;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

use Illuminate\Support\Facades\Log; 
 


class RosterController extends Controller
{
    use GeneralTrait;
    private $BaseModel; 

    public function __construct(
        RosterModel $RosterModel,
        AdminUserModel $AdminUserModel,
        ActivityLogModel $ActivityLogModel,
        AppointmentModel $AppointmentModel,
        AppointmentTypesModel $AppointmentTypesModel,
        WeekDaysModel $WeekDaysModel,
        RosterHasDatesModel $RosterHasDatesModel,
        RosterHasDatesHasTimeFramesModel $RosterHasDatesHasTimeFramesModel,
        SettingsModel $SettingsModel,
        RosterHasWeeksHasTimeFramesModel $RosterHasWeeksHasTimeFramesModel
    ){
        $this->BaseModel                = $RosterModel;
        $this->AdminUserModel           = $AdminUserModel;
        $this->ActivityLogModel         = $ActivityLogModel;
        $this->AppointmentModel         = $AppointmentModel;
        $this->AppointmentTypesModel    = $AppointmentTypesModel;
        $this->WeekDaysModel            = $WeekDaysModel;
        $this->RosterHasDatesModel   = $RosterHasDatesModel;
        $this->RosterHasDatesHasTimeFramesModel   = $RosterHasDatesHasTimeFramesModel;
        $this->SettingsModel   = $SettingsModel;
        $this->RosterHasWeeksHasTimeFramesModel   = $RosterHasWeeksHasTimeFramesModel;

        $this->ViewData = [];
        $this->JsonData = []; 

        $this->ModuleTitle = __('admin.TITLE_ROSTER_MODULE');
        $this->ModuleView  = 'admin.roster.';
        $this->ModulePath  = 'admin.roster';  

        // Permission Middleware
        $this->middleware(['permission:roster-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:roster-add'], ['only' => ['create','store']]);
    }

    public function index()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_ROSTER_MODULE');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');
        $this->ViewData['modulePath']   = $this->ModulePath; 

        // view file with data
        //dd($this->ViewData);
        return view($this->ModuleView.'index', $this->ViewData);  
    }

    public function create()    
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_ROSTER_MODULE');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All user
        $this->ViewData['user'] = $this->AdminUserModel
                                        ->where('status', 1)
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'doctor');
                                        })
                                        ->get(); 
        // All appointment types 
        // $this->ViewData['appointment'] = $this->AppointmentTypesModel
        //                                 ->where('status', 1)
        //                                 ->get(); 

        $this->ViewData['weekdays']     = $this->WeekDaysModel
                                        ->where('status', 1)
                                        ->get(); 
        
        //for getting default time slot duration
        $setting = $this->SettingsModel
                        //->where('id',12) //commented on 24-apr-25
                        ->where('setting_key', 'TIME_SLOTS_DURATION') //added on 24-apr-25
                        ->first(['setting_key','setting_value']);
        // dd($settings);
        if(!empty($setting)){
            $this->ViewData['default_time_duration'] = $setting['setting_value'];                         
        }else{
            $this->ViewData['default_time_duration'] = 5;                         
        }                        
        
        // view file with data
        return view($this->ModuleView.'create', $this->ViewData);   
    } 

    public function _getDateForSpecificDayBetweenDates($startDate,$endDate,$day_number){
        $endDate = strtotime($endDate);
        $days=array('1'=>'Monday','2' => 'Tuesday','3' => 'Wednesday','4'=>'Thursday','5' =>'Friday','6' => 'Saturday','7'=>'Sunday');
        $date_array = [];
        for($i = strtotime($days[$day_number], strtotime($startDate)); $i <= $endDate; $i = strtotime('+1 week', $i))
            $date_array[]=date('Y-m-d',$i);
        return $date_array;
    }

    public function store(RosterRequest $request)  
    {
        //dd($request->all());
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg'] = __('admin.FAIL_ROSTER_CREATE'); 

        try {
            $msg = self::_checkRosterTimeSlotExist($request);
            if(!empty($msg)){
                $this->JsonData['msg'] = $msg; 
                return response()->json($this->JsonData);
                exit();
            }

            DB::beginTransaction();  

            $collection     = new $this->BaseModel;   
            $collection     = self::_storeOrUpdate($collection,$request);
            if ($collection) 
            {
                $newData = $collection->toArray();

                $all_transactions = [];
                $date_index=0;
                ## ADD WEEKDAY DATA
                if (!empty($request->date_data) && count($request->date_data) > 0) 
                {    //dump("--");
                    foreach ($request->date_data as $date_key=>$date_data) 
                    {
                        //dump($date_data['start_date']);
                        //dump($date_data['end_date']);
                        if(!empty($date_data['start_date']) && !empty($date_data['end_date']) && !empty($date_data['from_time']) && !empty($date_data['to_time']) && !empty($date_data['time_frames']))
                        {
                            //dump("----dfsdbfkskdhfds---->");
                            // $dates = explode(",", $date_data['dates']);
                            //strtotime($days[$day_number], strtotime($startDate))
                            $dates = [];
                            $dates = self::_getDateForSpecificDayBetweenDates($date_data['start_date'],$date_data['end_date'],$date_data['week_day_id']);

                            foreach ($dates as $date) {

                                $rosterDateObj = new $this->RosterHasDatesModel;
                                $rosterDateObj->roster_id   = $collection->id;
                                $rosterDateObj->week_day_id =  !empty($date_data['week_day_id']) ? $date_data['week_day_id'] : 0;
                                $rosterDateObj->start_date  = !empty($date_data['start_date']) ? $date_data['start_date']:NULL;
                                $rosterDateObj->end_date    = !empty($date_data['end_date']) ? $date_data['end_date']:NULL;
                                $rosterDateObj->date        = !empty($date) ? date("Y-m-d",strtotime($date)) : NULL;
                                $rosterDateObj->from_time   =  !empty($date_data['from_time']) ? $date_data['from_time'] : 0;
                                $rosterDateObj->to_time   = !empty($date_data['to_time']) ? $date_data['to_time'] : 0;
                                $rosterDateObj->date_index   = $date_index;
                                $rosterDateObj->is_excluded  = 0; // Initially Included
                                //dd($rosterDateObj);
                                if ($rosterDateObj->save()) 
                                {
                                    $all_transactions[] = 1;

                                    /*if (!empty($date_data['time_frames']) && count($date_data['time_frames']) > 0) 
                                    {    
                                        foreach ($date_data['time_frames'] as $time_frame) 
                                        {
                                            $timeFrameObj = new $this->RosterHasDatesHasTimeFramesModel;
                                            $timeFrameObj->roster_id   = $collection->id;
                                            $timeFrameObj->date_id      = $rosterDateObj->id;
                                            $timeFrameObj->time_frame   = $time_frame;

                                            if ($timeFrameObj->save()) 
                                            {
                                                $all_transactions[] = 1;

                                            }else{
                                                $all_transactions[] = 0;
                                            }

                                        }
                                    }*/

                                }else{
                                    $all_transactions[] = 0;
                                }

                            }

                            if (!empty($date_data['time_frames']) && count($date_data['time_frames']) > 0) 
                            {    
                                // Track unique time frames to prevent duplicates
                                $inserted_time_frames = [];
                                
                                foreach ($date_data['time_frames'] as $time_frame) 
                                {
                                    // Create a unique key for this time frame combination
                                    $timeFrameKey = $date_data['week_day_id'].'_'.$date_data['start_date'].'_'.$date_data['end_date'].'_'.$time_frame;
                                    
                                    // Skip if we've already inserted this exact time frame in this request
                                    if(in_array($timeFrameKey, $inserted_time_frames)){
                                        Log::info('Skipping duplicate time frame: '.$timeFrameKey);
                                        continue;
                                    }
                                    
                                    $timeFrameObj = new $this->RosterHasWeeksHasTimeFramesModel;
                                    $timeFrameObj->roster_id   = $collection->id;
                                    $timeFrameObj->week_day_id = $date_data['week_day_id'];
                                    $timeFrameObj->time_frame  = $time_frame;
                                    $timeFrameObj->start_date  = !empty($date_data['start_date']) ? $date_data['start_date']:NULL;
                                    $timeFrameObj->end_date    = !empty($date_data['end_date']) ? $date_data['end_date']:NULL;

                                    if ($timeFrameObj->save()) 
                                    {
                                        $all_transactions[] = 1;
                                        $inserted_time_frames[] = $timeFrameKey;

                                    }else{
                                        $all_transactions[] = 0;
                                    }
                                }
                            }



                        }
                        $date_index++;
                    }
                }

            }else{
                $all_transactions[] = 0;
            }
            //dd('d');

            if (!in_array(0,$all_transactions)) 
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has created roster','Add',null,$newData); 
                   
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url'] =  route($this->ModulePath.'.index');
                $this->JsonData['msg'] = __('admin.ROSTER_CREATED');
                DB::commit();
            }else
            {
                DB::rollback();
                $this->JsonData['error_msg'] = $e->getMessage();
            }
        } 
        catch(\Exception $e) {
            DB::rollback();
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
        $this->ModuleTitle = __('admin.TITLE_ROSTER_MODULE');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All userdata
        $id = base64_decode(base64_decode($encID));
        $roster = $this->BaseModel
                        ->with(['assignedDoctor','assignedAppointmentType',
                            'hasDates'=>function($q)use($id){
                                $q->with(['assignedWeekDay','assignedRoster','hasTimeFrames'=>function($q2)use($id){
                                    $q2->where('roster_id',$id);
                                }]);
                            }])
                        ->find($id);
        // dd($roster);
        // $roster = $this->BaseModel->hasDates($id);
        // dd($roster->toArray());
        // Initialize so the variable always exists even when no row has 2+ time frames
        // (e.g. user saved a roster with only one slot per row). Fallback order:
        // overridden inside the loop when possible -> global TIME_SLOTS_DURATION -> 5.
        $oldTimeDuration = null;

        if(!empty($roster->hasDates)){
            $group_dates = [];
            foreach ($roster->hasDates as $key => $hasDate) {
                // dd($hasDate);

                $has_date_frame = $hasDate->hasTimeFrames;
                $s_date = $hasDate->start_date;
                $e_date = $hasDate->end_date;
                $from_time = $hasDate->from_time;
                $to_time = $hasDate->to_time;

                $has_date_frame = $has_date_frame->filter(function($index) use($s_date,$e_date,$from_time,$to_time)
                {   
                    if(empty($index->start_date) && empty($index->end_date))
                    return true;
                    
                    // Normalize time format for comparison
                    $indexFromTime = date("H:i:s",strtotime($from_time));
                    $indexToTime = date("H:i:s",strtotime($to_time));
                                   
                    if((!empty($index->start_date) && strtotime($index->start_date) == strtotime($s_date)) && 
                       (!empty($index->end_date) && strtotime($index->end_date) == strtotime($e_date)) &&
                       (!empty($index->time_frame) && $index->time_frame >= $indexFromTime && $index->time_frame < $indexToTime))
                    {
                        return true;
                    }
                // });
                })->values();
                
                // Remove duplicate time frames by time_frame value
                $unique_time_frames = [];
                $seen_times = [];
                foreach($has_date_frame as $frame){
                    $time_value = $frame->time_frame;
                    if(!in_array($time_value, $seen_times)){
                        $seen_times[] = $time_value;
                        $unique_time_frames[] = $frame;
                    }
                }
                $has_date_frame = collect($unique_time_frames);
                
               
                $group_dates['dates'][$hasDate->date_index][] = $hasDate->date;
                $group_dates['time_frames'][$hasDate->date_index] = $has_date_frame;
                $group_dates['from_to'][$hasDate->date_index]['from_time'] = $hasDate->from_time;
                $group_dates['from_to'][$hasDate->date_index]['to_time']   = $hasDate->to_time;
                $group_dates['from_to'][$hasDate->date_index]['start_date']   = $hasDate->start_date;
                $group_dates['from_to'][$hasDate->date_index]['end_date']   = $hasDate->end_date;
                $group_dates['weekdays'][$hasDate->date_index]['week_day_id'] = $hasDate->week_day_id;
                // $group_dates['weekdays'][$hasDate->date_index]['week_day_id'] = $hasDate->week_day_id;
                if(sizeof($has_date_frame) > 0){

                    //commented below code on 7-oct-24 for roster issue
                    // Log::info("Has date frame data count: ".sizeof($has_date_frame));
                    // $tempFromTime = $has_date_frame[0]['time_frame'];
                    // Log::info("From time: ".$tempFromTime);
                    // $tempToTime = $has_date_frame[1]['time_frame'];

                    // $fromTime = \DateTime::createFromFormat('H:i:s', $tempFromTime);
                    // $toTime = \DateTime::createFromFormat('H:i:s', $tempToTime);
                    // $interval = $fromTime->diff($toTime);
                    // $oldTimeDuration = $interval->i;

                    //added if condition on 7-oct-24 for roster issue
                    if(isset($has_date_frame[0]['time_frame']) && isset($has_date_frame[1]['time_frame']))
                    {
                        $tempFromTime = $has_date_frame[0]['time_frame'];
                        $tempToTime = $has_date_frame[1]['time_frame'];

                        $fromTime = \DateTime::createFromFormat('H:i:s', $tempFromTime);
                        $toTime = \DateTime::createFromFormat('H:i:s', $tempToTime);
                        $interval = $fromTime->diff($toTime);
                        $oldTimeDuration = $interval->i;
                    }//if


                }

            }
            $roster->custom_data = $group_dates;
        }
        // dd($group_dates);
        // echo '<pre>';
        // print_r($roster);
        // die;
        $this->ViewData['roster'] = $roster;
         // All user
        $this->ViewData['user']        = $this->AdminUserModel
                                        ->where('status', 1)
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'doctor');
                                        })
                                        ->get(); 
        // All appointment types 
       /* $this->ViewData['appointment'] = $this->AppointmentTypesModel
                                        ->where('status', 1)
                                        ->get(); */
        
       $this->ViewData['weekdays']    = $this->WeekDaysModel
                                        ->where('status', 1)
                                        ->get(); 

        //for getting default time slot duration
        $setting = $this->SettingsModel
                        //->where('id',12) //commented on 24-apr-25
                        ->where('setting_key', 'TIME_SLOTS_DURATION') //added on 24-apr-25
                        ->first(['setting_key','setting_value']);
        // dd($settings);
        // Fall back to the current TIME_SLOTS_DURATION setting if no row had enough
        // frames to infer a duration, and finally 5 if the setting is missing too.
        if ($oldTimeDuration === null) {
            $oldTimeDuration = !empty($setting) ? (int) $setting['setting_value'] : 5;
        }
        $this->ViewData['oldTimeDuration'] = $oldTimeDuration;
        if(!empty($setting)){
            $this->ViewData['default_time_duration'] = $setting['setting_value'];
        }else{
            $this->ViewData['default_time_duration'] = 5;                         
        }
        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function update(RosterRequest $request, $encID) 
    {
        $week_day_flag = 0;
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_ROSTER_UPDATE');       

        try {  

            $id = base64_decode(base64_decode($encID));
            $msg = self::_checkRosterTimeSlotExist($request,$id);
            
            if(!empty($msg)){
                $this->JsonData['msg'] = $msg; 
                return response()->json($this->JsonData);
                exit();
            }   

            DB::beginTransaction();

            /*******start*on 14-oct-24**************************************************/

             $rosterDates = $this->RosterHasDatesModel
                        ->where('roster_id',$id)
                        ->get();
                        

             if(isset($rosterDates) && !empty($rosterDates))
             {
                 $group_dates = [];
                 foreach ($rosterDates as $key => $hasDate) 
                 {
                   
                   $group_dates[$hasDate->date_index]['start_date']   = $hasDate->start_date;
                   $group_dates[$hasDate->date_index]['end_date']   = $hasDate->end_date;
                 }
             }           
                       
             $diffInMinutes =0; $isUpdateFlag=1;
            if(isset($group_dates) && !empty($group_dates))
            { 
                foreach($group_dates as $k=>$v)
                {
                    $rosterStartDate = $v['start_date'];
                    $rosterEndDate = $v['end_date'];
                    // dump($rosterStartDate);
                    // dump($rosterEndDate);
                    // dump($id);

                    $isRosterFlagBooked = $this->RosterHasWeeksHasTimeFramesModel                        
                            ->where('roster_id',$id)
                            ->where('time_frame_flag','2')
                            ->where('start_date',$rosterStartDate)
                            ->where('end_date',$rosterEndDate)
                            ->get()->toArray();

                           // dump("isRosterFlagBooked");
                           // dump($isRosterFlagBooked);

                    $getLastTimeDiff = $this->RosterHasWeeksHasTimeFramesModel                        
                        ->where('roster_id',$id)
                        ->where('start_date',$rosterStartDate)
                        ->where('end_date',$rosterEndDate) 
                        ->orderBy('id','desc')
                        ->limit(2)
                        ->get()->toArray(); 

                         // dump("getLastTimeDiff");
                         // dump($getLastTimeDiff);

                    // Need at least two saved time frames to compute a duration; otherwise
                    // leave $diffInMinutes at 0. Guarding both indices prevents the
                    // "Undefined array key 1" crash when a row has 0 or 1 saved frame.
                    if (!empty($getLastTimeDiff) && isset($getLastTimeDiff[0]['time_frame']) && isset($getLastTimeDiff[1]['time_frame']))
                    {
                        $start = Carbon::parse($getLastTimeDiff[0]['time_frame']);
                        $end   = Carbon::parse($getLastTimeDiff[1]['time_frame']);
                        $diffInMinutes = $end->diffInMinutes($start);
                    }

                     $checkSetting = $this->SettingsModel
                    ->where('setting_key', 'TIME_SLOTS_DURATION')
                    ->first(['setting_key', 'setting_value']);

                     if(isset($isRosterFlagBooked) && !empty($isRosterFlagBooked) && $checkSetting['setting_value'] != $diffInMinutes)
                     {
                        $isUpdateFlag=0;
                     }else if(isset($isRosterFlagBooked) && !empty($isRosterFlagBooked) && $checkSetting['setting_value'] == $diffInMinutes) {
                         $isUpdateFlag=1;
                     }else if(empty($isRosterFlagBooked) && $checkSetting['setting_value'] != $diffInMinutes) {
                        $isUpdateFlag=1;
                    }else if(empty($isRosterFlagBooked) && $checkSetting['setting_value'] == $diffInMinutes) {
                        $isUpdateFlag=1;
                     }        

                }//foreach
                
            }//group_dates  

                            
            //commented on 21-may-25                       
            /*if($isUpdateFlag==0){
                $this->JsonData['msg'] = __('admin.ROSTER_CANNOT_CHANGE');
                $this->JsonData['last_app_duration'] = $diffInMinutes;
                return response()->json($this->JsonData);
            }*/      
             
            /**********end roster code 14-oct-24************************************************/




            //commented on 21-may-25   
            // aaded by vijay #177 2/9/2024
            /*$checkSetting = $this->SettingsModel
                ->where('setting_key', 'TIME_SLOTS_DURATION')
                ->first(['setting_key', 'setting_value']);
      

            $checkAppointmentBookedslot = $this->AppointmentModel
                ->where('doctor_id', $request->doctor_id)
                // ->whereBetween('start_date', [$startDate, $endDate])
                // ->where('start_date', '>=', $today) 
                ->orderBy('id', 'desc')
                ->get();


            if(sizeof($checkAppointmentBookedslot) > 0){
                $start = Carbon::parse($checkAppointmentBookedslot[0]['start_date']);
                $end = Carbon::parse($checkAppointmentBookedslot[0]['end_date']);
                $diffInMinutes = $end->diffInMinutes($start);
                if($checkSetting['setting_value'] != $diffInMinutes){
                    $this->JsonData['msg'] = __('admin.ROSTER_CANNOT_CHANGE');
                    $this->JsonData['last_app_duration'] = $diffInMinutes;
                   // return response()->json($this->JsonData); //commented vj code on 14-oct-24
                }
            }*/
            // end







            $collection = $this->BaseModel->find($id); 
            $oldData =  $collection->toArray();

            $collection = self::_storeOrUpdate($collection,$request);
            $newData =  $collection->toArray();

            if ($collection)  
            {
                $all_transactions = [];
                $date_index=0;

                // ALWAYS clear existing rows first — even if the user removed ALL rows.
                // Previously this delete sat inside the `if (!empty($request->date_data))`
                // guard, so removing every row and saving was a silent no-op (nothing to
                // insert => nothing was deleted either, old rows persisted in the DB).
                $deleted_time_slot = $this->RosterHasDatesModel->where('roster_id', $collection->id)->where('is_excluded','1')->select('date')->get();
                $this->RosterHasDatesModel->where('roster_id', $collection->id)->delete();
                $this->RosterHasWeeksHasTimeFramesModel->where('roster_id', $collection->id)->delete();

                ## ADD WEEKDAY DATA
                if (!empty($request->date_data) && count($request->date_data) > 0)
                {
                    foreach ($request->date_data as $date_data)
                    {
                        if(!empty($date_data['week_day_id']))
                        {
                            if(!empty($date_data['start_date']) && !empty($date_data['end_date']) && !empty($date_data['from_time']) && !empty($date_data['to_time']) && !empty($date_data['time_frames']))
                            {
                               
                                // $dates = explode(",", $date_data['dates']); week_day_id
                                $dates = [];
                                $dates = self::_getDateForSpecificDayBetweenDates($date_data['start_date'],$date_data['end_date'],$date_data['week_day_id']);
                                $exclude_date = array_column($deleted_time_slot->toArray(),'date');

                                foreach ($dates as $date) {
                                   $excluded = 0;
                                    if(in_array(date("Y-m-d",strtotime($date)),$exclude_date))
                                    {
                                        $excluded = 1;
                                    }
                                    $rosterDateObj = new $this->RosterHasDatesModel;
                                    $rosterDateObj->roster_id   = $collection->id;
                                    $rosterDateObj->week_day_id =  !empty($date_data['week_day_id']) ? $date_data['week_day_id'] : 0;
                                    $rosterDateObj->start_date  = !empty($date_data['start_date']) ? date('Y-m-d',strtotime($date_data['start_date'])):NULL;
                                    $rosterDateObj->end_date    = !empty($date_data['end_date']) ? date('Y-m-d',strtotime($date_data['end_date'])):NULL;
                                    $rosterDateObj->date        = !empty($date) ? date("Y-m-d",strtotime($date)) : NULL;
                                    $rosterDateObj->from_time   =  !empty($date_data['from_time']) ? $date_data['from_time'] : 0;
                                    $rosterDateObj->to_time   = !empty($date_data['to_time']) ? $date_data['to_time'] : 0;
                                    $rosterDateObj->date_index   = $date_index;
                                    $rosterDateObj->is_excluded  = $excluded; // Initially Included
                                   
                                    if ($rosterDateObj->save()) 
                                    {
                                        $all_transactions[] = 1;

                                        /*if (!empty($date_data['time_frames']) && count($date_data['time_frames']) > 0) 
                                        {    
                                            foreach ($date_data['time_frames'] as $time_frame) 
                                            {
                                                $timeFrameObj = new $this->RosterHasDatesHasTimeFramesModel;
                                                $timeFrameObj->roster_id   = $collection->id;
                                                $timeFrameObj->date_id      = $rosterDateObj->id;
                                                $timeFrameObj->time_frame   = $time_frame;

                                                if ($timeFrameObj->save()) 
                                                {
                                                    $all_transactions[] = 1;

                                                }else{
                                                    $all_transactions[] = 0;
                                                }

                                            }
                                        }*/

                                    }else{
                                        $all_transactions[] = 0;
                                    }

                                }

                                if (!empty($date_data['time_frames']) && count($date_data['time_frames']) > 0) 
                                {    
                                    // Track unique time frames to prevent duplicates
                                    $inserted_time_frames = [];
                                    
                                    foreach ($date_data['time_frames'] as $time_frame) 
                                    {
                                        // Create a unique key for this time frame combination
                                        $timeFrameKey = $date_data['week_day_id'].'_'.$date_data['start_date'].'_'.$date_data['end_date'].'_'.$time_frame;
                                        
                                        // Skip if we've already inserted this exact time frame in this request
                                        if(in_array($timeFrameKey, $inserted_time_frames)){
                                            continue;
                                        }
                                        
                                        $timeFrameObj = new $this->RosterHasWeeksHasTimeFramesModel;
                                        $timeFrameObj->roster_id   = $collection->id;
                                        $timeFrameObj->week_day_id = $date_data['week_day_id'];
                                        $timeFrameObj->time_frame  = $time_frame;
                                        $timeFrameObj->start_date  = !empty($date_data['start_date']) ? $date_data['start_date']:NULL;
                                        $timeFrameObj->end_date    = !empty($date_data['end_date']) ? $date_data['end_date']:NULL;

                                        if ($timeFrameObj->save()) 
                                        {
                                            $all_transactions[] = 1;
                                            $inserted_time_frames[] = $timeFrameKey;

                                        }else{
                                            $all_transactions[] = 0;
                                        }
                                    }
                                }

                            }
                            $date_index++;
                        }
                        else
                        {
                            DB::rollback();
                            $week_day_flag = 1;
                            $this->JsonData['status'] = __('admin.RESP_ERROR');
                            $this->JsonData['url'] =  route($this->ModulePath.'.index');
                            $this->JsonData['msg'] = __('admin.ROSTER_WEEK_DAYS_ERROR');
                            return;
                        }
                        
                    }
                   
                    
                    
                }
                
            }  
            else{
                $all_transactions[] = 0;
            } 

            if (!in_array(0,$all_transactions)) 
            {
                $this->ActivityLogModel->addLog($this->ModuleTitle,'has updated roster','Update',$oldData,$newData); 

                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url'] =  route($this->ModulePath.'.index');
                $this->JsonData['msg'] = __('admin.ROSTER_UPDATED');
                DB::commit();
            }else
            {
                DB::rollback();
                $this->JsonData['error_msg'] = $e->getMessage();
            } 

        }
        catch(\Exception $e) {
            
            if($e->getMessage() == "Undefined index: ")
            {
               
                DB::rollback();
                $this->JsonData['msg'] = __('admin.ROSTER_WEEK_DAYS_ERROR');
                $this->JsonData['error_msg'] = $e->getMessage();
            }
            else
            {

                DB::rollback();
                $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $e->getMessage();
            }
            
        }

        return response()->json($this->JsonData);
    }

    public function destroy($encID)
    {
        $this->JsonData['status'] =  __('admin.RESP_ERROR');
        $this->JsonData['msg']    =  __('admin.FAIL_ROSTER_DELETE');'';

        $id = base64_decode(base64_decode($encID));
        try 
        {
            DB::beginTransaction();
            $collection = $this->BaseModel->find($id);
            if($collection->delete())
            {
                $newData = $collection->toArray();
                //Delete records
                $this->RosterHasDatesModel->where('roster_id', $id)->delete(); 
                $this->RosterHasWeeksHasTimeFramesModel->where('roster_id',$id)->delete();  

                $this->ActivityLogModel->addLog($this->ModuleTitle,'has deleted appointment','Delete',null,$newData);
                DB::commit();  

                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['msg'] = __('admin.ROSTER_DELETED');
            }

        } catch (Exception $e) 
        {
            DB::rollback();
           $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

    public function getRecords(Request $request)
    {
        // dd("ppp--->");
        /*--------------------------------------
        |  Variables
        ------------------------------*/
            
            // skip and limit
            $start = $request->start;
            $length = $request->length;

            // Login user id 
            $userId = Auth::user()->id;

            // serach value
            $search = $request->search['value']; 

            // order
            $column = $request->order[0]['column'];
            $dir = $request->order[0]['dir'];

            // filter columns
            $filter = array(
                0 => 'id',
                1 => 'roster.doctor_id', 
                2 => 'roster_has_dates.date',
                3 => 'roster_has_dates.from_time',
                4 => 'roster_has_dates.to_time'
                // 3 => 'roster.appointment_type_id',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/ 
        // $current_date = date('Y-m-d',time());
        $current_date = date('Y-m-d');
            // start model query
        if(auth()->user()->hasRole('Doctor')){
            $modelQuery =  $this->BaseModel->with(['hasDates'=>function($q)use($current_date){
                                    $q->with(['assignedWeekDay']);
                                    $q->whereDate('date','>=',$current_date);
                                    $q->orderBy('date', 'ASC');
                                   
                                }])
                                ->leftjoin('users', 'users.id' , '=', 'roster.doctor_id')  
                                ->join('roster_has_dates','roster_has_dates.roster_id', '=', 'roster.id')
                                ->where('users.id', $userId)
                                ->whereDate('roster_has_dates.date', '>=',date('Y-m-d'));
            

         } else{
             $modelQuery =  $this->BaseModel->with(['hasDates'=>function($q)use($current_date){
                                    $q->with(['assignedWeekDay']);
                                    $q->whereDate('date','>=',$current_date);
                                    $q->orderBy('date', 'ASC');
                                }])
                                ->leftjoin('users','users.id', '=', 'roster.doctor_id')
                                ->join('roster_has_dates','roster_has_dates.roster_id', '=', 'roster.id')
                                ->whereDate('roster_has_dates.date', '>=',date('Y-m-d'));
                                // ->orderBy('roster_has_dates.date','ASC');
            
        }
             
                          
            // get total count 
            // $countQuery = clone($modelQuery);            
            // $totalData  = $countQuery->count();

            $countQuery = clone($modelQuery);   
            $totalData  = $countQuery->selectRaw('COUNT(DISTINCT(roster.id)) as cnt')->first();
            $totalData = $totalData->cnt;

            ## FILTER OPTIONS for specific field 
            $custom_search = false;
            $totalFiltered = $totalData;
            if (!empty($request->custom))  
            {
                if (isset($request->custom['doctor_id'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['doctor_id'];
                    $modelQuery     = $modelQuery
                    ->where('roster.doctor_id', $key);
                }

                // if (isset($request->custom['appointment_type_id'])) 
                // {
                //     $custom_search  = true;
                //     $key            = $request->custom['appointment_type_id'];
                //     $modelQuery     = $modelQuery
                //     ->where('roster.appointment_type_id', $key);
                // }
                if (isset($request->custom['date'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['date'];
                    $modelQuery     = $modelQuery
                    ->where('roster_has_dates.date', $key);
                }

                if (isset($request->custom['from_time'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['from_time'];
                    $modelQuery     = $modelQuery
                    ->where('roster_has_dates.from_time', $key);
                }

                if (isset($request->custom['to_time'])) 
                {
                    $custom_search  = true;
                    $key            = $request->custom['to_time'];
                    $modelQuery     = $modelQuery
                    ->where('roster_has_dates.to_time', $key);
                }

                 // get total filtered
                $filteredQuery = clone($modelQuery);            
                $totalFiltered = $filteredQuery->selectRaw('COUNT(DISTINCT(roster.id)) as cnt')->first();
                $totalFiltered = $totalFiltered->cnt;         

            }

            // filter options for commen search box
            if (!empty($request->search))  
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];

                     $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'LIKE', "%".$search."%");  
                        // $query->orwhere('appointment_types.name', 'LIKE', '%'.$search.'%');  
                       /*$query->orwhere('roster.from_time', 'LIKE', '%'.$search.'%');   
                       $query->orwhere('roster.to_time', 'LIKE', '%'.$search.'%');
                        if(strtolower($search)=="active"){
                            $query->orwhere('roster.status', '=', 1);
                        }
                        else{
                            $query->orwhere('roster.status', '=', 0);
                        }*/
                    });
                } 
            }

            // get total filtered
            // $filteredQuery = clone($modelQuery);            
            // $totalFiltered  = $filteredQuery->count();
            
            // offset and limit
            $object = $modelQuery->orderBy($filter[$column], $dir)
                                 ->skip($start)
                                 ->take($length)
                                 ->groupBy('roster.id')
                                 ->get(['roster.*','users.first_name',
                                        'users.last_name']);
            //dd($object->toArray());            
        /*--------------------------------------  
        |  data binding
        ------------------------------*/
            $data = [];
            $from_to = [];
            if (!empty($object) && sizeof($object) > 0)  
            {
                foreach ($object as $key => $row)  
                {

                        $fname  = $row->first_name;
                        $lname  = $row->last_name; 
                        $name   = $fname .' '. $lname;

                        $data[$key]['id']  = $row->id;
                       
                        $data[$key]['doctor_id']   = '<span title="'.ucfirst($name).'">'.ucfirst($name).'</span>';

                        $hasDates = $row->hasDates;
                        // $hasDatesHtml = '<select name="hasDates" id="hasDates" class="form-control my-select">';
                        // $hasDatesFromHtml = '<select class="form-control my-select">';
                        // $hasDatesToHtml = '<select class="form-control my-select">';

                        $hasDatesHtml = '';
                        $hasDatesFromHtml = '';
                        $hasDatesToHtml = '';


                        if(!empty($hasDates)){
                            $sep = "";
                            foreach ($hasDates as $index=>$hasDate) {
                                // dd($hasDate);

                                if($index>0 && strlen($hasDatesHtml)>0){
                                    $sep = "<br/>";
                                }

                                if($hasDate->is_excluded==0){

                                    // dd($hasDate);
                                    
                                    $hasDatesHtml.=  $sep.$hasDate->assignedWeekDay->day.", ".$hasDate->date;
                                    /*$hasDatesFromHtml.= $sep.date("H:i",strtotime($hasDate->from_time));
                                    $hasDatesToHtml.= $sep.date("H:i",strtotime($hasDate->to_time));*/

                                    $hasDatesFromHtml.= date("H:i",strtotime($hasDate->from_time))."</br/>";
                                    $hasDatesToHtml.= date("H:i",strtotime($hasDate->to_time))."</br/>";


                                   // $hasDatesHtml.='<option class="theme-black blue-select">'.$hasDate->date.'</option>';

                                    // if(!in_array($hasDate->date_index, $from_to)){

                                        $from_to[] = $hasDate->date_index;
                                        
                                        // $date_index = $hasDate->date_index;
                                        // $hasDatesFromHtml.='<option class="theme-black blue-select">'.date("H:i",strtotime($hasDate->from_time)).'</option>';

                                        // $hasDatesToHtml.='<option class="theme-black blue-select">'.date("H:i",strtotime($hasDate->to_time)).'</option>';
                                    // }
                                }
                                
                            }
                        }
                       /* $hasDatesHtml.= "</select>";
                        $hasDatesFromHtml.= "</select>";
                        $hasDatesToHtml.= "</select>";*/
                        $hasDatesHtml.= "";
                        $hasDatesFromHtml.= "";
                        $hasDatesToHtml.= "";

                        $data[$key]['date']   = $hasDatesHtml;
                        $data[$key]['from_time']   =  $hasDatesFromHtml;
                        $data[$key]['to_time']   =  $hasDatesToHtml;

                        // $data[$key]['appointment_type_id']   = '<span>'.$row->appointment.'</span>'; 
                        
                        // $data[$key]['status'] = $row->status==1 ?'Active':'Inactive';

                        $edit="";
                        $delete="";
                        $exclude_dates = "";
                        // Check Permission
                        if(auth()->user()->can('roster-add')){
                            $edit = '<a href="'.route('admin.roster.edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';
 
                            $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.roster.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp'; 

                            $exclude_dates = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_ROSTER_EXCLUDE').'" onclick="return getExcludeDateCollection(this)" data-href="'.route('admin.roster.getExcludeDates', [base64_encode(base64_encode($row->id))]).'" ><span class="fas fa-minus-square"></span></a>'; 
                        }

                        $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.$exclude_dates.'</div>';
                }
            }
     
            ## SEARCH HTML 
            // Doctors
            $user = $this->AdminUserModel
                            ->where('status', 1)
                            ->whereHas('roles',function($query){
                               $query->where('name', 'doctor');
                            })
                            ->get();

            // Appointment Types
            // $appointment_type = $this->AppointmentTypesModel
            //                             ->where('status', 1)
            //                             ->get();

             // Search for doctor column
            $doctorName = '';
            //if(auth()->user()->hasRole('super-admin')){
                $doctorName= '<select name="doctor_id" id="doctor_id" class="form-control my-select">';

                $doctorName.='<option class="theme-black blue-select" value="">'.__('admin.TITLE_SELECT_DOCTOR').'</option>';

                foreach ($user as $users) {
                    $dname = $users->first_name.' '.$users->last_name;
                    $doctorName.='<option class="theme-black blue-select" value='.$users->id.' '. ( isset($request->custom['doctor_id']) && $request->custom['doctor_id'] == $users->id ? 'selected' : '').'>'.$dname.'</option>';
                }             
                $doctorName.= "</select>";
            //}

            // Search for appointment type column
            // $appointmentTypeName= '<select name="appointment_type_id" id="appointment_type_id" class="form-control my-select">';

            // $appointmentTypeName.='<option class="theme-black blue-select" value="">'.__('admin.TITLE_SELECT_TYPE').'</option>';

            // foreach ($appointment_type as $appointment_types) {
            //     $pname = $appointment_types->name;
            //     $appointmentTypeName.='<option class="theme-black blue-select" value='.$appointment_types->id.' '. ( $request->custom['appointment_type_id'] == $appointment_types->id ? 'selected' : '').'>'.$pname.'</option>';
            // }             
            // $appointmentTypeName.= "</select>";

            $searchHTML['id']       =  '';   
           // $searchHTML['doctor_id']  =  auth()->user()->hasRole('super-admin')?$doctorName : '';  
            // $searchHTML['appointment_type_id']   =  $appointmentTypeName; 
            $searchHTML['doctor_id']        =  $doctorName; 
            $searchHTML['date']  =  '';
            $searchHTML['from_time']       =  '';
            $searchHTML['to_time']       =  '';

           /*$searchHTML['date']  =  '<input type="text" class="form-control" id="date" value="" placeholder="Search...">';
            $searchHTML['from_time']       =  '<input type="text" class="form-control" id="from_time" value="" placeholder="Search...">';
            $searchHTML['to_time']       =  '<input type="text" class="form-control" id="to_time" value="" placeholder="Search...">';*/

           // $searchHTML['to_time']   =  ''; 
           /* $searchHTML['status']   =  '<select name="status" id="status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">Status</option>
                    <option class="theme-black blue-select" value="1" '.( $request->custom['status'] == "1" ? 'selected' : '').' >Active</option>
                    <option class="theme-black blue-select" value="0" '.( $request->custom['status'] == "0" ? 'selected' : '').'>Inactive</option>            
                    </select>';*/
            
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
        if(!empty($request->doctor_id)){
             $doctor_id    =        $request->doctor_id;          
        }else{
             $doctor_id    =         Auth::user()->id;          

        }

        $collection->user_id                = auth()->user()->id;
        $collection->doctor_id   = $doctor_id ;
        //$collection->doctor_id              = auth()->user()->hasRole('super-admin') ? $request->doctor_id : Auth::user()->id; 
       // $collection->appointment_type_id    = $request->appointment_type_id;
        $collection->status                 = 1;  
      
        //Save data
        $collection->save();   

        return $collection;    
    }

    public function _checkRosterTimeSlotExist($request,$id=false)
    {
        //dd($request->date_data);
        $msg = '';
        if (!empty($request->date_data) && count($request->date_data) > 0) 
        {
            
            // Array to store all date-time combinations in current request for duplicate detection
            $currentRequestSlots = [];
            
            foreach ($request->date_data as $dateIndex => $date_data) 
            {
                if(!empty($date_data['start_date']) && !empty($date_data['end_date']) && !empty($date_data['from_time']) && !empty($date_data['to_time']) && !empty($date_data['time_frames']))
                {
                    //$dates = explode(",", $date_data['dates']);
                    $dates = [];
                    // dd($date_data);
                    if(!empty($date_data['week_day_id'])){
                        $dates = self::_getDateForSpecificDayBetweenDates($date_data['start_date'],$date_data['end_date'],$date_data['week_day_id']);
                        Log::info($dates);
                        if(empty($dates) && sizeof($dates)==0){
                            return $msg = __('admin.TITLE_ROSTER_WEEK_MISMATCH'); 
                            exit();
                        }
                        
                        // Format times for comparison
                        $fromTime = date("H:i:s",strtotime($date_data['from_time']));
                        $toTime = date("H:i:s",strtotime($date_data['to_time']));
                        
                        // Check for duplicates within the current request (same edit/create submission)
                        foreach ($dates as $date) {
                            foreach ($currentRequestSlots as $existingSlot) {
                                if ($existingSlot['date'] === $date) {
                                    // Two time ranges overlap when: start_A < end_B AND end_A > start_B
                                    // This catches ALL overlap types: partial, complete containment, and exact match.
                                    if ($fromTime < $existingSlot['to_time'] && $toTime > $existingSlot['from_time']) {
                                        return $msg = __('admin.TITLE_ROSTER_DATE').':'.$date." ".__('admin.ERR_TIME_FRAME_EXIST');
                                        exit();
                                    }
                                }
                            }
                            // Add current slot to tracking array
                            $currentRequestSlots[] = [
                                'date' => $date,
                                'from_time' => $fromTime,
                                'to_time' => $toTime
                            ];
                        }
                        
                        //dd($dates,$request->all());

                        // Check against OTHER rosters for same doctor
                        $date_exists = $this->BaseModel
                            ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                            ->whereIn('roster_has_dates.date',$dates)
                            ->where('roster_has_dates.is_excluded','=',0)
                            ->where('roster.doctor_id','=',$request->doctor_id);
                            // ->get();

                        if(!empty($id)){
                            $date_exists = $date_exists->where('roster.id','!=',$id);
                        }
                        $date_exists = $date_exists->get();
                       // dd($date_exists);
                        if(!empty($date_exists) && count($date_exists)>0){
                            foreach ($date_exists as $date_exist) {
                                $requestFromTime = date("H:i:s",strtotime($date_data['from_time']));
                                $requestToTime = date("H:i:s",strtotime($date_data['to_time']));
                                // Two time ranges overlap when: start_A < end_B AND end_A > start_B
                                if ($requestFromTime < $date_exist->to_time && $requestToTime > $date_exist->from_time) {
                                    return $msg = __('admin.TITLE_ROSTER_DATE').':'.$date_exist->date." ".__('admin.ERR_TIME_FRAME_EXIST');
                                    exit();
                                }
                            }
                        }
                    }  
                    else
                    {
                        $msg = __('admin.ROSTER_WEEK_DAYS_ERROR');
                    }  

                }

            }

        }

        return $msg;
        exit();
    }

    public function getExcludeDates(Request $request,$encID)
    {

        $this->JsonData['status'] =  __('admin.RESP_ERROR');
        $this->JsonData['msg']    =  __('admin.FAIL_ROSTER_EXCLUDED_DATES');

        try 
        {
            $id = base64_decode(base64_decode($encID));

            $current_date = date('Y-m-d',time());
            $roster = $this->BaseModel
                            ->with(['hasDates'=>function($q) use($current_date){
                                            $q->where('date','>=',$current_date);
                                            $q->orderBy('date', 'ASC');
                                        }])
                            ->find($id);
            $html = "";
            if(!empty($roster->hasDates) && sizeof($roster->hasDates)>0){
                $group_dates = [];

                foreach ($roster->hasDates as $key => $hasDate) {

                    $excludedCss = '';
                    $iconClass = 'fa-minus-square';
                    $iconTitle = 'Exclude';
                    if($hasDate->is_excluded==1){
                        $excludedCss = ' style="background: darkgray;"';
                        $iconClass = 'fa-plus-square';
                        $iconTitle = 'Include';
                    }
                    
                    $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.$iconTitle.'" onclick="return excludeDateCollection(this)" data-href="'.route('admin.roster.excludeDate', [base64_encode(base64_encode($hasDate->id))]) .'" ><span class="fas '.$iconClass.'"></span></a>'; 
                    $html .= "<tr".$excludedCss.">";
                    $html .= "<td>".$hasDate->date."</td>";
                    $html .= "<td>".$delete."</td>";
                    $html .= "</tr>";
                    
                }
            }
            
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
            $this->JsonData['msg']    =  __('admin.ROSTER_EXCLUDED_DATES');
            $this->JsonData['excludeDatesHtml']    = $html;

        }
        catch (Exception $e) 
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);  


    }

    public function excludeDate(Request $request,$encID)
    {

        $this->JsonData['status'] =  __('admin.RESP_ERROR');
        $this->JsonData['msg']    =  __('admin.FAIL_ROSTER_EXCLUDED_DATE_UPDATE');

        try 
        {
            $id = base64_decode(base64_decode($encID));

            $collection = $this->RosterHasDatesModel->find($id);

            if(!empty($collection)){

                $updateExcludeInclude = [];
                $msg = '';
                if($collection->is_excluded==0){
                    $collection->is_excluded = 1;  
                    $msg = __('admin.TITLE_ROSTER_EXCLUDE').':'.$collection->date;
                }else{
                    $collection->is_excluded = 0;    
                    $msg = __('admin.TITLE_ROSTER_INCLUDE').':'.$collection->date;
                }
                
                if($collection->save()){
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']    =  $msg;
                    $this->JsonData['data']   = $collection;
                }

            }

            // dd($id,$collection);

        }
        catch (Exception $e) 
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);  

    }

    public function getDoctorDates(Request $request)
    {
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');

        try 
        {
            $doctor_id = $request->doctor_id;
           
            $html= "";
            $msg =  __('admin.ERR_TIME_FRAME_NOT_FOUND');
            $current_date = date('Y-m-d',time());

            $compare_function = function($a,$b) {
 
                        $a_timestamp = strtotime($a); // convert a (string) date/time to a (int) timestamp
                        $b_timestamp = strtotime($b);
                 
                        // new feature in php 7
                        return $a_timestamp <=> $b_timestamp;
                    };

            $getDoctorDates =  $this->BaseModel
                                        ->with(['hasDates'=>function($q) use($current_date){
                                            $q->where('date','>=',$current_date);
                                            $q->orderBy('date', 'ASC');
                                        }])
                                        ->whereHas('hasDates', function ($query) use ($current_date) 
                                        { 
                                            $query->where('date','>=',$current_date);
                                        })
                                        ->where('roster.doctor_id', $doctor_id)
                                        ->get();

            // dd($getDoctorDates->toArray());
            $hasDatesHtml = '<option value="">'.__('admin.TITLE_SELECT_TEXT').' '.__('admin.TITLE_ROSTER_DATE').'</option>';
            if(!empty($getDoctorDates) && count($getDoctorDates)>0){
                $date_exist = [];
                foreach($getDoctorDates as $getDoctorDate){   
                    
                   $hasDates = $getDoctorDate->hasDates;
                   if(!empty($hasDates)){
                        foreach ($hasDates as $hasDate) {

                            if($hasDate->is_excluded==0){
                                $dayName = '';
                                /*if(!empty($hasDate->assignedWeekDay)){
                                     $dayName = $hasDate->assignedWeekDay->day.", ";
                                }*/
                                if(!in_array($hasDate->date, $date_exist)){
                                    $date_exist[] = $hasDate->date;
                                    //$hasDatesHtml .='<option value="'.$hasDate->date.'">'.$dayName.$hasDate->date.'</option>';
                                }
                            }
                        }
                    }
                }
                usort($date_exist, $compare_function);

                foreach($date_exist as $date_value){  
                    $hasDatesHtml .='<option value="'.$date_value.'">'.$date_value.'</option>';
                }

            }


            $hasDatesHtml .= '';
           
            $this->JsonData['html'] = $hasDatesHtml;
            $this->JsonData['data'] = $getDoctorDates;
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');

        } catch (Exception $e) 
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData); 

    }

    public function getDoctorDutyRoster(Request $request)
    {
        // dump($request->all());
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_APPOINTMENT_TIME_FRAME');

        try 
        {
            $doctor_id   = $request->doctor_id;
           // $doctor_date = $request->doctor_date;

            $appointment_date       = date("Y-m-d",strtotime($request->doctor_date));

            // $day_of_week = date('N',strtotime($appointment_date));
            // dd($day_of_week);
           
            $html= "";
            $msg =  __('admin.ERR_TIME_FRAME_NOT_FOUND');

            $getDoctorDutyRosters =  $this->BaseModel
                                        ->with(['hasDates'=>function($q) use ($appointment_date){
                                            $q->with(['assignedWeekDay','hasTimeFrames']);
                                            $q->where('date','=',$appointment_date); 
                                        }])
                                        ->whereHas('hasDates', function ($query) use ($appointment_date) 
                                        { 
                                            $query->where('date',$appointment_date); 
                                        })
                                        ->where('roster.doctor_id', $doctor_id)
                                        ->get();
                                        // ->join('roster_has_dates','roster_has_dates.roster_id', '=', 'roster.id')
                                        // ->whereDate('roster_has_dates.date', '>=',date('Y-m-d'))
                                        // ->groupBy('roster.id')
            // dd($getDoctorDutyRosters->toArray());

            
            $doctor_appointment_time_frames = $this->AppointmentModel
                                                    ->where('doctor_id',$doctor_id)
                                                    ->whereDate('start_date',$appointment_date)
                                                    ->whereStatus(1)
                                                    ->select(DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date,(DATE_FORMAT(end_date,'%H:%i')) as end_date"))
                                                    ->get();
            $booked_time_slots = '';
            if(!empty($doctor_appointment_time_frames) && sizeof($doctor_appointment_time_frames)>0){
                $booked_time_slots = '';
                foreach ($doctor_appointment_time_frames as $doctor_appointment_time_frame) {
                   $booked_time_slots .= $doctor_appointment_time_frame->start_date." - ".$doctor_appointment_time_frame->end_date.", ";
                }
            }

            $hasDatesFrom = '';
            $hasDatesTo   = '';
            $hasDatesHtml     = '';
             $current_time = date("H:i",time());  
            $today_date = date("Y-m-d",time());  
            $ignore_time_slots = [];
            $final_time_slots = [];
            if(!empty($getDoctorDutyRosters) && count($getDoctorDutyRosters)>0){
                foreach($getDoctorDutyRosters as $getDoctorDutyRoster){   
                    
                   $hasDates = $getDoctorDutyRoster->hasDates;
                   // dd($hasDates->toArray());
                   if(!empty($hasDates) && sizeof($hasDates)>0){

                        /*$selected_doctor_date = $hasDates->filter(function ($item, $key)use($appointment_date) {
                                                return data_get($item, 'date') == $appointment_date;
                                            });*/
                       
                        // dd($selected_doctor_date);
                        if(!empty($hasDates) && sizeof($hasDates)>0){

                            $sep = '';
                            foreach ($hasDates as $selected_doctor_date_value) {

                                $roster_id = $selected_doctor_date_value->roster_id;
                                // dd($selected_doctor_date_value);

                                 $selected_doctor_date_value->hasTimeFrames = $selected_doctor_date_value->hasTimeFrames->filter(function ($item, $key)use($roster_id) {
                                                return data_get($item, 'roster_id') == $roster_id;
                                            });
                                

                                if(strlen($hasDatesFrom)>0){
                                    $sep .= '<br/>';
                                }
                                $has_time_frames = array_column($selected_doctor_date_value->hasTimeFrames->toArray(), 'time_frame');
                                $hasDatesFrom .= $sep.date("H:i",strtotime($selected_doctor_date_value->from_time));
                                $hasDatesTo .= $sep.date("H:i",strtotime($selected_doctor_date_value->to_time));
                            }
                            foreach ($has_time_frames as $has_time_frame_key => $has_time_frame_value) {

                                 $time      = date("H:i",strtotime($has_time_frame_value));  
                                 // dd($time);
                                // $added_time_frame =  date("H:i",strtotime($time) + $appointmentDuration);

                                 if(!empty($doctor_appointment_time_frames)){

                                    foreach ($doctor_appointment_time_frames as $doctor_appointment_time_frame) {

                                         if($time==$doctor_appointment_time_frame->start_date || ( strtotime($time) >= strtotime($doctor_appointment_time_frame->start_date) && strtotime($time) < strtotime($doctor_appointment_time_frame->end_date) )){
                                            //case for begin date, inbetween, overide after add
                                            $ignore_time_slots[] = $time;
                                        }

                                        
                                    }
                                }

                                if(!in_array($time, $ignore_time_slots)) {
                                    if(strtotime($today_date)==strtotime($appointment_date))
                                    {
                                        if(($time>=$current_time)){

                                            $final_time_slots[$has_time_frame_key] = $time;
                                        }

                                    }elseif(strtotime($today_date)!==strtotime($appointment_date)){
                                        $final_time_slots[$has_time_frame_key] = $time;

                                    }
                                }

                                
                            }

                            // dd($hasDatesFrom,$hasDatesTo,$has_time_frames);
                        }
                       
                    }
                   
                }

            }

            
            // dd($has_time_frames);
            $has_time_frames = implode(", ", $final_time_slots);
            $html = '<tr>
                          <td style="width: 100px;">'.$appointment_date.'</td>
                          <td>'.$hasDatesFrom.'</td>
                          <td>'.$hasDatesTo.'</td>
                          <td>'.$has_time_frames.'</td>
                          <td  style="width: 112px;">'.$booked_time_slots.'</td>
                        </tr>';
           
            $this->JsonData['html'] = $html;
            $this->JsonData['data'] = $getDoctorDutyRosters;
            $this->JsonData['msg']  = $msg;
            $this->JsonData['status'] = __('admin.RESP_SUCCESS');

        } catch (Exception $e) 
        {
            $this->JsonData['exception'] = $e->getMessage();
        }

        return response()->json($this->JsonData);   
        
    }

}
