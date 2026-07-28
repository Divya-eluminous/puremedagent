<?php
//This controller added by roshani on 2-may-2024 for optimal settings
namespace App\Http\Controllers\Api\v3;

use Illuminate\Http\Request;  
use App\Http\Controllers\Controller; 


//Models
use App\Models\AppointmentModel;
use App\Models\WeekDaysModel;
use App\Models\RosterModel;
use App\Models\AppointmentTypesModel;
use App\Models\SettingsModel;


//Plugins
use DB;
use Validator;
use Carbon\Carbon;

//Trait
use App\Traits\GeneralTrait;

class OptimalAppointmentController extends BaseController
{
	private $BaseModel;
    use GeneralTrait;
	public function __construct(

        AppointmentModel $AppointmentModel,
        WeekDaysModel $WeekDaysModel,
        RosterModel $RosterModel,
        AppointmentTypesModel $AppointmentTypesModel,
        SettingsModel $SettingsModel

	)
	{
        $this->BaseModel     = 	$AppointmentModel;
        $this->WeekDaysModel =  $WeekDaysModel;
        $this->RosterModel =  $RosterModel;
        $this->AppointmentTypesModel =  $AppointmentTypesModel;
        $this->SettingsModel =  $SettingsModel;

	}
	////////////Code added by roshani on 2-may-2024

	/*---------------------------------
    |   Weekdays Listing
    ------------------------------------------*/

    public function getWeekDaysListing()
    {
    	$errors     = [];   
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;

        try{
        	$collection = collect([]);
    		$collection   = $this->WeekDaysModel->where('status', 1)->get(); 

            if($collection->count() > 0)
            {               
                $response = array();                
                foreach($collection as $info)
                {
		            $response['id']         = $info->id;
		            $response['name']  = $info->day;
		            $response['status']  = $info->status;


		            $data[]   = $response;
		            $response = [];
		        }//foreach
		        $status  = true;
            	$message = __('api.DATA_FOUND_SUCCESS');
		    }//if
		    else
		    {
            	$message = __('api.WEEK_DATA_NOT_FOUND');
		    }
        }//try
        catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
        }//catch
        return self::_sendResult($message,$data,$errors,$status);
    }

	////////////Code added by roshani on 2-may-2024

	////////////Code added by roshani on 2-may-2024

    /*---------------------------------
    |   Get From Date
    ------------------------------------------*/

    public function getFromDate(Request $request)
    {
    	$errors     = [];   
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;
        $inputdata  = $request->all();
        $validator = Validator::make($inputdata,[
                // 'start_date'    => 'required',
                'doctor_id'    => 'required',
                'patient_id' => 'required',
            ],
            [   
                // 'start_date.required'   => __('api.ERR_START_DATE_REQ'),
                'doctor_id.required'   => __('api.ERR_DOCTOR_ID_REQUIRED'),
                'patient_id.required' => __('api.AUTH_PATIENT_ID_REQ'),

            ]
            );
        if($validator->fails()) {
            $message = __('api.REQUIRED_FIELDS');
            $errors[] = $validator->errors();
        }else
        {

            try
            {
           		//Check if quarter setting is off then show the first avaliable date of doctor selected if its on then check according quarter if appointment booked in current quarter then it should show the first avaliable date of current quarter otherwise check for the next quarter first avaliable date.

           if(isset($request->doctor_id) && !empty($request->doctor_id))
           {

            $quarter_setting=0;
            $OPTIMAL_APPOINTMENT ='OPTIMAL_APPOINTMENT';
            $optimal_appointment = DB::table('settings')->where(['setting_key'=>$OPTIMAL_APPOINTMENT])->select('setting_key','setting_value','description')->first();
            if(isset($optimal_appointment) && !empty($optimal_appointment))
            {
                $quarter_setting = $optimal_appointment->setting_value;
               

            }//if optimal appointment

            // changes by vijay 7/3/24
            $optimalAppointment = 0;
            if (isset ($request->appointment_type_id) && !empty ($request->appointment_type_id)) {
                $checkAppointmentType = $this->AppointmentTypesModel->where('id', $request->appointment_type_id)->first();
                if($checkAppointmentType){
                    $optimalAppointment = $checkAppointmentType->optimal_appointment;
                }else
                {
                    $optimalAppointment = null; 
                    $message    = __('api.ERR_APP_ID_NOT_EXIST'); 
                    $status     = false;
                    return self::_sendResult($message,$data,$errors,$status);
                }
               
            }
            //end changes

            $todaysdate = date('Y-m-d');
            $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$request->doctor_id and STATUS=1 AND start_date>='$todaysdate'");   

            //is_already_registered means if its login
            // if($quarter_setting==1 && $request->is_already_registered==1)  //changes by vijay 8/3/24 
            if ($optimalAppointment == 1  && ($quarter_setting == 1 || $quarter_setting == 0))
            {

                    $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
                    $bookingtimeframe = DB::table('settings')->where(['setting_key'=>$APPOINTMENT_TIME_PERIOD])->select('setting_key','setting_value','description')->first();
                    $description =  $bookingtimeframe->description;
                    $setting_value =  $bookingtimeframe->setting_value;
                    $patient_id = $request->patient_id;
                    $doctor_id = $request->doctor_id;
                    $month = date("n");
                    $Quarter = ceil($month / 3);
                    

                    $todaysdate = date('Y-m-d');
                    $flag_set = array('0','1');
                    $year = date("Y");

                    $count = 0;
                    $avaliable_date = "";
                    $no_of_days = 0;
             
                    for ($i = $Quarter;$i <= 6;$i++) {
                       

                        $j = $i;
                        $quarters = [5 => 1, 6 => 2, 7 => 3, 8 => 4];
                        if (in_array($i, [5, 6, 7, 8])) {
                            $j = $quarters[$i];
                            $year = date("Y", strtotime("+1 year"));
                        }
                        $time_slots=[];

                        $check_appointment_exists = $this->BaseModel
                                            ->whereRaw("quarter(start_date)=$j and year(start_date)=$year")
                                            //->where('doctor_id',$doctor_id) //commented on 14-sept-22
                                            ->where('patient_id',$patient_id)->where('status',1)
                                            ->where('appointment_status','!=','Vermisst')   // Added on 21-sept-22
                                            ->first();
                        if(empty($check_appointment_exists)){

                        $appointdatesarr = isset($getStartDates[0]->appointment_dates)?$getStartDates[0]->appointment_dates:0;  

                          $time_slots = $this->RosterModel
                                    ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                    ->join("roster_has_weeks_has_time_frames",function($join){
                                        $join->on("roster_has_weeks_has_time_frames.roster_id","=","roster_has_dates.roster_id")
                                            ->on("roster_has_weeks_has_time_frames.start_date","=","roster_has_dates.start_date")
                                            ->on("roster_has_weeks_has_time_frames.end_date","=","roster_has_dates.end_date");

                                    })

                                    ->where('roster.doctor_id',$doctor_id)
                                    ->where('roster_has_dates.is_excluded','=',0)
                                    ->where('roster_has_dates.date','>=',$todaysdate)
                                    ->whereRaw("quarter(date)=$j and year(date)=$year")
                                    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                                    // ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN (SELECT start_date FROM appointment where doctor_id=$doctor_id and STATUS=1))")
                                     ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")

                                    ->groupBy('roster_has_dates.date')
                                    ->first(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_weeks_has_time_frames.id as r_id']);
                                }

                        if (!empty($time_slots) && isset($time_slots)) {
                            $time_slots = $time_slots->toArray();
                             //dd($time_slots);
                            //exit;
                            $count=1;

                           // $avaliable_date1 =$time_slots[0]['date'];
                            $avaliable_date1 =$time_slots['date'];
                            $avaliable_date =  date("Y/m/d", strtotime($avaliable_date1));
                            if($description=="week")
                            {
                                $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' week'));
                            }
                            elseif($description=="month")
                            {
                              $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' month'));
                            }
                            $now = strtotime($avaliable_date);
                            $your_date = strtotime($endDate);
                            $datediff = $your_date- $now;  
                            $no_of_days =  round($datediff / (60 * 60 * 24));
                            $data = [
    		                            'count'=>$count,
    		                            // 'start_date'=>(isset($avaliable_date) && !empty($avaliable_date))?date("Y-m-d",strtotime($avaliable_date)):'',
    		                            // 'end_date'=> (isset($endDate) && !empty($endDate))?date("Y-m-d",strtotime($endDate)):'',

                                        'start_date'=>(isset($avaliable_date) && !empty($avaliable_date))? self::formatDate($avaliable_date) :'',

                                        // 'end_date'=> (isset($endDate) && !empty($endDate))?date("Y-m-d",strtotime($endDate)):'',
                                        'end_date'=> (isset($endDate) && !empty($endDate))? self::formatDate($endDate) :'',
    		                            'description'=>$description,
    		                            'setting_value'=>$setting_value,
    		                            'no_of_days'=>$no_of_days,
    		                            'from_time' => '06:00',
    		                            'to_time' => '21:00'
    		                        ];   
    		                        $message    = __('api.DATA_FOUND_SUCCESS'); 
            						$status     = true;   
            					return self::_sendResult($message,$data,$errors,$status);

                        }//if time slot 
                      
                    }//for


            }//if quarter setting is 1 
            else
            {
                $avaliable_date=$endDate='';
                $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
                $bookingtimeframe = DB::table('settings')->where(['setting_key'=>$APPOINTMENT_TIME_PERIOD])->select('setting_key','setting_value','description')->first();
                $doctor_id = $request->doctor_id;
                $todaysdate = date('Y-m-d');
                $flag_set = array('0','1');
                $patient_id = $request->patient_id;
                $doctor_id = $request->doctor_id;  

                // commented below code on 2 sept 22
               /* $data = DB::table('roster_has_dates')->select('roster_has_dates.date as avaliable_date')
                    ->join('roster', 'roster.id', '=', 'roster_has_dates.roster_id')
                    ->join('roster_has_weeks_has_time_frames', 'roster_has_weeks_has_time_frames.roster_id', '=', 'roster_has_dates.roster_id')
                    ->where('roster_has_dates.date', '>=', $todaysdate)->where('roster.doctor_id',$doctor_id)
                    ->WhereIn('roster_has_weeks_has_time_frames.time_frame_flag',$flag_set)
                    ->orderBy('roster_has_dates.date','ASC')
                    ->get();*/

                 

                if(isset($patient_id) && !empty($patient_id))
                {

                    // $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$request->doctor_id and STATUS=1 AND start_date>='$todaysdate' and patient_id=$patient_id");

                    $appointdatesarr = isset($getStartDates[0]->appointment_dates)?$getStartDates[0]->appointment_dates:0;

                     $data = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                        $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
                    })->where('roster.doctor_id', $doctor_id)
                     ->where('roster_has_dates.is_excluded', '=', 0)
                     ->where('roster_has_dates.date', '>=', $todaysdate)
                     ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                     ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))

                     // ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN (SELECT start_date FROM appointment where doctor_id=$doctor_id and STATUS=1 and patient_id=$patient_id))")

                     ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")

                     ->groupBy('roster_has_dates.date')
                     ->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);
                }else
                {

                     // $getStartDates = DB::select("SELECT GROUP_CONCAT(CONCAT('''', start_date, '''' )) as appointment_dates FROM appointment where doctor_id=$request->doctor_id and STATUS=1 AND start_date>='$todaysdate'");

                     $appointdatesarr = isset($getStartDates[0]->appointment_dates)?$getStartDates[0]->appointment_dates:0;


                      $data = $this->RosterModel->join('roster_has_dates', 'roster_has_dates.roster_id', 'roster.id')->join("roster_has_weeks_has_time_frames", function ($join) {
                        $join->on("roster_has_weeks_has_time_frames.roster_id", "=", "roster_has_dates.roster_id")->on("roster_has_weeks_has_time_frames.start_date", "=", "roster_has_dates.start_date")->on("roster_has_weeks_has_time_frames.end_date", "=", "roster_has_dates.end_date");
                    })->where('roster.doctor_id', $doctor_id)
                      ->where('roster_has_dates.is_excluded', '=', 0)
                      ->where('roster_has_dates.date', '>=', $todaysdate)
                      ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '>=', DB::raw('CAST(roster_has_dates.from_time as time) '))
                      ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '), '<=', DB::raw('CAST(roster_has_dates.to_time as time) '))

                      // ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN (SELECT start_date FROM appointment where doctor_id=$doctor_id and STATUS=1))")

                      ->whereRaw("(CONCAT(roster_has_dates.date, ' ', roster_has_weeks_has_time_frames.time_frame) NOT IN ($appointdatesarr))")

                      ->groupBy('roster_has_dates.date')
                      ->first(['roster_has_dates.date', 'roster_has_weeks_has_time_frames.time_frame', 'roster_has_weeks_has_time_frames.id as r_id']);
                }//else

              

                // dump($data);    
                $description =  $bookingtimeframe->description;
                $setting_value =  $bookingtimeframe->setting_value;
                if(isset($data) && !empty($data))
                {
                    $count = 1;
                    // $avaliable_date1 =$data[0]->avaliable_date;
                    $avaliable_date1 = $data->date;
                    $avaliable_date =  date("Y/m/d", strtotime($avaliable_date1));
                    if($description=="week")
                    {
                        $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' week'));
                    }
                    elseif($description=="month")
                    {
                      $endDate =  date('Y/m/d', strtotime($avaliable_date. ' + '.$setting_value.' month'));
                    }
                    $now = strtotime($avaliable_date);
                    $your_date = strtotime($endDate);
                    $datediff = $your_date- $now;  
                    $no_of_days =  round($datediff / (60 * 60 * 24));

                    $data = [
                            'count'=>$count,
                            // 'start_date'=>(isset($avaliable_date) && !empty($avaliable_date))?date("Y-m-d",strtotime($avaliable_date)):'',
                            'start_date'=>(isset($avaliable_date) && !empty($avaliable_date))? self::formatDate($avaliable_date) :'',

                            // 'end_date'=> (isset($endDate) && !empty($endDate))?date("Y-m-d",strtotime($endDate)):'',
                            'end_date'=> (isset($endDate) && !empty($endDate))? self::formatDate($endDate) :'',

                            'description'=>$description,
                            'setting_value'=>$setting_value,
                            'no_of_days'=>$no_of_days,
                            'from_time' => '06:00',
                            'to_time' => '21:00'
                        ];      
                        $message    = __('api.DATA_FOUND_SUCCESS'); 
                        $status     = true; 
                        return self::_sendResult($message,$data,$errors,$status);
                }
                else
                {   
                    // $count = 0;
                    // $avaliable_date = "";
                    // $description =  $bookingtimeframe->description;
                    // $setting_value =  $bookingtimeframe->setting_value;
                    // $no_of_days = 0;


                    $data = [];
                    $message    = __('api.ERR_NOT_FOUND'); 
                    $status     = false; 
                }
               //  $data = [
    		         //        'count'=>$count,
    		         //        'start_date'=>(isset($avaliable_date) && !empty($avaliable_date))?date("Y-m-d",strtotime($avaliable_date)):'',
    		         //        'end_date'=> (isset($endDate) && !empty($endDate))?date("Y-m-d",strtotime($endDate)):'',
    		         //        'description'=>$description,
    		         //        'setting_value'=>$setting_value,
    		         //        'no_of_days'=>$no_of_days,
    		         //        'from_time' => '06:00',
    		         //        'to_time' => '21:00'
    		         //    ];      
    		         //    $message    = __('api.DATA_FOUND_SUCCESS'); 
            			// $status     = true; 
            			// return self::_sendResult($message,$data,$errors,$status);

           }//else of quarter setting
          }//if isset doctor id 

    		}//try
            catch(\Exception $e) {
                    $message = __('api.ERR_SOMETHING_WRONG');
                    $errors[] = [
                          "error" => $e->getMessage(), 
                      ];
            }//catch
        }//validation
        return self::_sendResult($message,$data,$errors,$status);

    }//getWebAppointmentStartDate

    ////////////Code added by roshani on 2-may-2024

    //Code added by roshani on 6-may-24
    public function getEndDate(Request $request)
    {
    	$errors     = [];   
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;
        $inputdata  = $request->all();


        $validator = Validator::make($inputdata,[
                'start_date'    => 'required|date_format:d.m.Y',
            ],
            [   
                'start_date.required'   => __('api.ERR_START_DATE_REQ'),
                'start_date.date_format' => __('api.ERR_DATE_FORMAT_DD_MM_YYYY'),
            ]
            );
        if($validator->fails()) {
            $message = __('api.REQUIRED_FIELDS');
            $errors[] = $validator->errors();
        }else
        {
	        try
	        {
		        if(isset($request->start_date) && !empty($request->start_date))
		        {
		            // $start_date = $request->start_date;
                    // $start_date = date("d/m/Y",strtotime($request->start_date));

                    $carbonDate = Carbon::createFromFormat('d.m.Y', $request->start_date);
                    $start_date = $carbonDate->format('d/m/Y');
                    // $start_date = $request->start_date;
                    // dd($start_date);

                    // 
		            $end_date='';
		            $APPOINTMENT_TIME_PERIOD = 'BOOKING_TIMEFRAME';
		            $bookingtimeframe = DB::table('settings')->where(['setting_key'=>$APPOINTMENT_TIME_PERIOD])->select('setting_key','setting_value','description')->first();
		           
		            $description =  $bookingtimeframe->description;
		            $setting_value =  $bookingtimeframe->setting_value;

		            if($description=="week")
		            {
		                // $end_date =  date('d-m-Y', strtotime(($start_date). ' + '.$setting_value.' week'));
                        // Parse the start date using Carbon
                        $start_date_carbon = Carbon::createFromFormat('d/m/Y', $start_date);
                        // Add weeks to the start date
                        $end_date_carbon = $start_date_carbon->addWeeks((int)$setting_value);
                        // Format the end date as 'd-m-Y'
                        $end_date = $end_date_carbon->format('d/m/Y');
		            }
		            elseif($description=="month")
		            {
                        // $end_date =  date('Y/m/d', strtotime($start_date. ' + '.$setting_value.' month'));
                        // Parse the start date using Carbon
                        $start_date_carbon = Carbon::createFromFormat('d-m-Y', $start_date);
                        // Add months to the start date
                        $end_date_carbon = $start_date_carbon->addMonths((int)$setting_value);
                        // Format the end date
                        $end_date = $end_date_carbon->format('d-m-Y');
		            }

		        }//if not empty start date

		   		$message    = __('Api.DATA_FOUND_SUCCESS'); 
	        	$status     = true; 
		        $data = [
		            // 'start_date'=>(isset($start_date) && !empty($start_date))?date("Y-m-d",strtotime($start_date)):'',
		            // 'end_date'=> (isset($end_date) && !empty($end_date))?date("Y-m-d",strtotime($end_date)):'',
                    'start_date'=>(isset($start_date) && !empty($start_date))? self::formatDate($start_date) :'',
                    'end_date'=> (isset($end_date) && !empty($end_date))? self::formatDate($end_date) :'',
		            'description'=>$description,
		            'setting_value'=>$setting_value,
                    'from_time' => '06:00',
                    'to_time' => '21:00'
		        ];      
	        }//try
	        catch(\Exception $e) {
	                $message = __('api.ERR_SOMETHING_WRONG');
	                $errors[] = [
	                      "error" => $e->getMessage(), 
	                  ];
	        }//catch
    	}
        return self::_sendResult($message,$data,$errors,$status);
    }//getEndDate
    //Code added by roshani on 6-may-24

     //Smart Appoimnet 7-may-24 added by roshnai ======================
    public function getDoctorSlots(Request $request)
    {
        $errors     = [];   
        $data       = []; 
        $message    = __('api.FAIL_APPOINTMENT_TIME_FRAME');
        $status     = false;
        $inputdata  = $request->all();
        $appointment_type_name = Null;

        // Converted data array
        $convertedData = [];

        $validator = Validator::make($inputdata,[
                'appointment_type_id'    => 'required',
                'doctor_id'    => 'required',
                'end_date'    => 'required|date_format:d.m.Y',
                'from_time'    => 'required',
                'start_date'    => 'required|date_format:d.m.Y',
                'to_time'    => 'required',
                // 'week_day_id'    => 'required',
                'week_day_id' => ['required', function ($attribute, $value, $fail) {
                    $ids = explode(',', $value);

                    foreach ($ids as $id) {
                        if (!($id >= 1 && $id <= 7)) {
                            $fail(__('api.ERR_WEEKDAY_INVALID'));
                        }
                    }
                }],
            ],
            [   
                'appointment_type_id.required'   => __('api.ERR_APPOINTMENT_TYPE_ID_REQUIRED'),
                'doctor_id.required'   => __('api.ERR_DOCTOR_ID_REQUIRED'),
                'end_date.required'   => __('api.ERR_END_DATE_REQ'),
                'from_time.required'   => __('api.ERR_TIME_FROM_REQUIRED'),
                'start_date.required'   => __('api.ERR_START_DATE_REQ'),
                'to_time.required'   => __('api.ERR_TIME_TO_REQUIRED'),
                'week_day_id.required'   => __('api.ERR_WEEKDAY_REQUIRED'),
                'start_date.date_format' => __('api.ERR_DATE_FORMAT_DD_MM_YYYY'),
                'end_date.date_format' => __('api.ERR_DATE_FORMAT_DD_MM_YYYY'),


            ]
            );
        if($validator->fails()) {
            $message = __('api.REQUIRED_FIELDS');
            $errors[] = $validator->errors();
        }else
        {
            try 
            {
                if($request->week_day_id)
                {
                    $string = $request->week_day_id;
                    $array = explode(",", $string);
                    $week_day_ids = array_values($array);
                }
                $doctor_id              = $request->doctor_id;
                $appointment_type_id    = $request->appointment_type_id;
                // $week_day_ids           = $request->week_day_id;
                // $start_date             = $request->start_date;
                // $end_date               = $request->end_date;
                // $start_date             = date("Y-m-d",strtotime($request->start_date));
                // $end_date               = date("Y-m-d",strtotime($request->end_date));

                $carbonStartDate = Carbon::createFromFormat('d.m.Y', $request->start_date);
                $start_date = $carbonStartDate->format('Y-m-d');
                $carbonEndDate = Carbon::createFromFormat('d.m.Y', $request->end_date);
                $end_date = $carbonEndDate->format('Y-m-d');


                $from_time              = $request->from_time;
                $to_time                = $request->to_time;
                $hidden_patient_id      = $request->hidden_patient_id;


               /*************************************************************/

                $quarter_setting=0;
                $OPTIMAL_APPOINTMENT ='OPTIMAL_APPOINTMENT';
                $optimal_appointment = DB::table('settings')->where(['setting_key'=>$OPTIMAL_APPOINTMENT])->select('setting_key','setting_value','description')->first();
                if(isset($optimal_appointment) && !empty($optimal_appointment))
                {
                    $quarter_setting = $optimal_appointment->setting_value;
                }//if optimal appointment


                if($quarter_setting==1 && isset($request->hidden_patient_id))
               {

                    $ignoreQuarterArr = $ignoreYearArr=$ignoreArray=[];
                    $get_quarters = $this->get_quarters($start_date,$end_date);
                    if(isset($get_quarters) && !empty($get_quarters))
                    {
                        $ignoreArr=[]; $whereQuarter=''; $quarterCheckFlag=0;
                        $whereQuarter="Case ";
                        foreach($get_quarters as $k=>$v)
                        {
                           $quarter = $v->period;
                           $year = $v->year;

                            $checkAppoimentBooked = $this->BaseModel
                                                ->whereRaw("quarter(start_date)=$quarter and year(start_date)=$year")
                                               // ->where('doctor_id',$doctor_id) //commented on 14-sept-22
                                                ->where('patient_id',$hidden_patient_id)
                                                ->where('status',1)
                                                // ->where('appointment_status','!=','Vermisst')   // Added on 21-sept-22 //Roshani hidden the line on 15-april-25 for point Trello 281
                                                ->first();

                            if(isset($checkAppoimentBooked) && !empty($checkAppoimentBooked))
                            {
                                $ignoreQuarterArr[] = $quarter;
                                $ignoreYearArr[] = $year;
                                $ignoreArr['quarter'] = $quarter;
                                $ignoreArr['year'] = $year;

                                $whereQuarter.="WHEN quarter(roster_has_dates.date)=$quarter THEN year(roster_has_dates.date)!='$year'";

                                $quarterCheckFlag=1;
         
                            }//if checkAppoimentBooked                    

                            if(isset($ignoreArr) && !empty($ignoreArr))
                            {
                                 $ignoreArray[] = $ignoreArr;
                             }//

                           
                           
                        }//foreach

                        $whereQuarter.="ELSE 1=1 END ";
                    }//if
               }
               
               /*************************************************************/
                    


                 $setting = $this->SettingsModel
                            ->where('id',12)
                            ->first(['setting_key','setting_value']);
                // dd($settings);
                if(!empty($setting)){
                    $default_time_duration = $setting['setting_value'];                         
                }else{
                    $default_time_duration = 10;                         
                } 
                //$day_of_week = date('N',strtotime($appointment_date));
              
                $appointmentType = $this->AppointmentTypesModel->find($appointment_type_id);
                $appointmentDuration = 0;
                if(!empty($appointmentType)){
                    $appointmentDuration = $appointmentType->duration * 60;//convert min into sec
                }
               
                $roster_time_slots_date_wise = array();

                      $time_frames = $this->RosterModel
                                    ->join('roster_has_dates','roster_has_dates.roster_id','roster.id')
                                    // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.week_day_id','roster_has_dates.week_day_id')

                                    // ->join('roster_has_weeks_has_time_frames','roster_has_weeks_has_time_frames.roster_id','roster_has_dates.roster_id') //commented on 26-may-25 for #337

                                    ->join('roster_has_weeks_has_time_frames', function ($join) {
                                         $join->on('roster_has_weeks_has_time_frames.roster_id', '=', 'roster_has_dates.roster_id')
                                         ->on('roster_has_weeks_has_time_frames.week_day_id', '=', 'roster_has_dates.week_day_id');
                                    })   //changed on 26-may-25 for #337



                                    ->where('roster.doctor_id',$doctor_id)
                                    ->where('roster_has_dates.is_excluded','=',0)
                                     ->whereDate('roster_has_dates.date','>=', $start_date)
                                     ->whereDate('roster_has_dates.date','<=', $end_date)
                                     ->whereIn('roster_has_weeks_has_time_frames.week_day_id',$week_day_ids)
                                    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'>=',DB::raw('CAST(roster_has_dates.from_time as time) '))
                                    ->where(DB::raw('CAST(roster_has_weeks_has_time_frames.time_frame as time) '),'<=',DB::raw('CAST(roster_has_dates.to_time as time) '))
                                    // ->where(function($query) use($end_date,$start_date){
                                    //     $query->where(function($query) use($end_date,$start_date){
                                    //         $query->where('roster_has_weeks_has_time_frames.start_date','<',$end_date)
                                    //         ->where('roster_has_weeks_has_time_frames.end_date','>',$start_date);
                                    //     })->orWhere(function($query){
                                    //         $query->whereNull('roster_has_weeks_has_time_frames.start_date')
                                    //         ->whereNull('roster_has_weeks_has_time_frames.end_date');
                                    //     });
                                    // })
                                    ->where('roster_has_weeks_has_time_frames.start_date','<=',$end_date)
                                    ->where('roster_has_weeks_has_time_frames.end_date','>=',$start_date);
                                    //->where('roster_has_weeks_has_time_frames.time_frame_flag','0')

                         if(isset($quarterCheckFlag) && $quarterCheckFlag==1 && $quarter_setting==1)
                         {
                            $time_frames =$time_frames->whereRaw($whereQuarter);
                         }           

                         $time_frames = $time_frames->get(['roster_has_dates.date','roster_has_weeks_has_time_frames.time_frame','roster_has_dates.start_date','roster_has_dates.end_date','roster_has_dates.week_day_id','roster_has_weeks_has_time_frames.id as r_id','roster_has_dates.to_time as roster_to_time']);//roster_to_time Added on 3-march-23 for last slot should not come

             
                
                $response = [];
               
                $message =  __('api.ERR_TIME_FRAME_NOT_FOUND');

                $current_time = date("H:i",time());  
                $morning_time = date("H:i", mktime(12, 0));  
                $today_date = date("Y-m-d",time());  

                
               
               //for testing purpose
                // $appointment_date = '2020-05-05';
               //  var_dump(strtotime($today_date)==strtotime($appointment_date));
               // exit();
               $ignore_time_slots = [];

               
                if(!empty($time_frames) && count($time_frames)>0){
                    $msg = '';
                    foreach($time_frames as $time_frame)
                    { 
                       

                        $roster_time_slots_date_wise[$time_frame->date]['weekday'] = $this->WeekDaysModel->where('id',$time_frame->week_day_id)->pluck('day')->first();
                        
                        $response['duration'] = $default_time_duration;  

                        $time = date("H:i",strtotime($time_frame->time_frame)); 
                        $added_time_frame =  date("H:i",strtotime($time) + $appointmentDuration); 
                        $selected="";   

                       
                                           

                        $t= Carbon::parse($time)->format('H:i');
                        $ft= Carbon::parse($from_time)->format('H:i');
                        $to= Carbon::parse($to_time)->format('H:i');

                         //Added on 3-march-23 for last slot should not come
                        $roster_to_time = date("H:i",strtotime($time_frame->roster_to_time)); 

                        if( $t >= $ft && $t <= $to)
                        { 
                            $doctor_appointment_time_frames = $this->BaseModel
                                    ->where('doctor_id',$doctor_id)
                                    ->whereDate('start_date',$time_frame->date) 
                                    ->whereStatus(1)
                                    ->select(DB::raw("(DATE_FORMAT(start_date,'%H:%i')) as start_date,(DATE_FORMAT(end_date,'%H:%i')) as end_date"))
                                    ->get();

                           
                            if(!empty($doctor_appointment_time_frames))
                            {
                                foreach ($doctor_appointment_time_frames as $doctor_appointment_time_frame) 
                                { 

                                    if(strtotime($time) < strtotime($doctor_appointment_time_frame->start_date) && strtotime($added_time_frame) > strtotime($doctor_appointment_time_frame->end_date) ){
                                            //case for 9:20-9:50 from booked 9:30-9:45
                                            $ignore_time_slots[$time_frame->date][] = $time;
                                            //dump($time.'1s condition');
                                    }                          
                                    if($time==$doctor_appointment_time_frame->start_date || ($added_time_frame>$doctor_appointment_time_frame->start_date && $added_time_frame<=$doctor_appointment_time_frame->end_date)){
                                        //case for begin date, inbetween, overide after add
                                        $ignore_time_slots[$time_frame->date][] = $time;
                                        //dump($time.'2nd condition');
                                    }                            
                                    if(($time>=$doctor_appointment_time_frame->start_date && $time<$doctor_appointment_time_frame->end_date)){
                                        $ignore_time_slots[$time_frame->date][] = $time; 
                                       // dump($time.'3rd condition');  
                                    }
                                }
                            }     
                            
                             // Added on 3-march-23 for last slot should not come
                            if($added_time_frame>$roster_to_time)
                            {
                              $ignore_time_slots[$time_frame->date][] = $time; 
                            }     

                            if (array_key_exists($time_frame->date,$ignore_time_slots))
                            {
                                //dump($time, $ignore_time_slots[$time_frame->date]);
                                if(!in_array($time, $ignore_time_slots[$time_frame->date])) 
                                {

                                    if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                    {
                                       $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                       $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                                   
                                    }
                                    elseif(strtotime($today_date)!==strtotime($time_frame->date))
                                    {                    
                                       $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                       $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                                    }
                                }
                            }
                            else
                            {
                                  if(strtotime($today_date)==strtotime($time_frame->date) && ($time>=$current_time))
                                    {
                                       $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                       $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                                   
                                    }elseif(strtotime($today_date)!=strtotime($time_frame->date))
                                    {
                                        $roster_time_slots_date_wise[$time_frame->date]['time_slots'][] = $time;
                                        $roster_time_slots_date_wise[$time_frame->date]['time_slots_id'][$time] = $time_frame['r_id'];
                                    }
                                   
                            }

                            if(!empty($roster_time_slots_date_wise[$time_frame->date]['time_slots']))
                            {
                                $roster_time_slots_date_wise[$time_frame->date]['time_slots'] = array_unique($roster_time_slots_date_wise[$time_frame->date]['time_slots']);
                                //dump($roster_time_slots_date_wise[$time_frame->date]['time_slots'] );
                            }
                        }

                    }                 
                     //   dump($ignore_time_slots);
                }
                if(!empty($roster_time_slots_date_wise))
                {
                    ksort($roster_time_slots_date_wise);

                }
     
                //dd($roster_time_slots_date_wise);//,$doctor_appointment_time_frames
                        /*<thead>
                                  <tr class="main_head">
                                     <th colspan="3">
                                        <h3>Online Terminvereinbarung</h3>
                                     </th>
                                  </tr>
                               </thead>*/
                $html= '<table id="customers">
                        <thead>
                            <tr>
                                <td colspan="3"  style="text-align: center;">Wählen Sie einen der verfügbaren
                                    Termine für die von Ihnen gewählte Terminart <b>"'.$appointmentType->name.'"</b>
                                    aus.
                                </td>
                            </tr>                       
                            <tr class="custMobThead">
                                <th width="50%">Datum</th>
                                <th>Uhrzeit</th>
                                <th>&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            
                        ';
                $msg =  __('admin.ERR_TIME_FRAME_NOT_FOUND');

                $index_key = 0;
                if(!empty($roster_time_slots_date_wise) && count($roster_time_slots_date_wise)>0){
                    foreach($roster_time_slots_date_wise as $roster_date=>$roster_time_slot){   

                        if(!empty($roster_time_slot['time_slots']) && sizeof($roster_time_slot['time_slots'])>0){
                            
                            $select_rosters = '<div class="custMobileVisible">Uhrzeit</div><select 
                                            name="time_slot_'.$index_key.'" onChange="assignValueToText('.$index_key.')" 
                                            id="time_slot_'.$index_key.'"  
                                            class="form-control select2" 
                                            >';
                            sort($roster_time_slot['time_slots']);
                            // sort($roster_time_slot['time_slots_id']);

                            foreach ($roster_time_slot['time_slots'] as $key=>$time_slot) {
                              
                                 $select_rosters .='<option data-dr="single doctor" value="'.$time_slot.'" lang="'.$roster_time_slot['time_slots_id'][$time_slot].'">'.$time_slot.'</option>';
                            }
                            $select_rosters .= '</select>';
                            // dd($roster_date,$roster_time_slot['weekday']);
                            $html.='<tr>
                                        <td class="right2"><div class="custMobileVisible">Datum</div><b>'.$roster_time_slot['weekday'].'</b>, '.date('d.m.Y',strtotime($roster_date)).'</td>
                                        <td>'.$select_rosters.'</td>
                                        <td  class="card-footer"><button type="button" roster_date="'.$roster_date.'" class="btn btn-success" onclick="arrangeTimeSlot(this,'.$index_key.')">VEREINBAREN</button>
                                        </td>
                                    </tr>';
                            
                            $index_key++;

                        }
                        
                        
                    }

                }else{
                    $html.='<tr>
                                <td class="right2" colspan="3"><b>'.$msg.'</b></td>
                            </tr>';
                }
                $html .= '<input type="hidden" id= "time_fram_hd_id" name="time_fram_hd_id" value=""></tbody></table>';
               
                // $this->JsonData['html'] = $html;
                // $this->JsonData['data'] = $roster_time_slots_date_wise;
                // $this->JsonData['type'] = 'All doctors';
                // $this->JsonData['msg']  = $msg;
                // $this->JsonData['status'] = __('front.RESP_SUCCESS');

                // return $this->JsonData['data'];
                //get apoointment type name
                if(isset($appointment_type_id) && !empty($appointment_type_id))
                {
                    $appointment_type_id = $this->AppointmentTypesModel::where('id',$appointment_type_id)->first();
                }
                // Loop through the original data and restructure it
                if(isset($roster_time_slots_date_wise) && !empty($roster_time_slots_date_wise))
                {
                    foreach ($roster_time_slots_date_wise as $date => $data) 
                    {

                        if(!empty($data['time_slots']) && sizeof($data['time_slots'])>0){  

                            $time_slots_ids = array_values($data['time_slots_id']);

                            //changed date format by roshani

                            // $date = date("Y/m/d",strtotime($date));
                            $convertedData[] = [
                                "weekday" => $data["weekday"],
                                // "slot_date" => $date,
                                "slot_date" => self::formatDate($date),
                                "time_slots" => $data["time_slots"],
                                "time_slots_id" => $time_slots_ids
                            ];
                        }//if condition added on 3-june-24
                    }
                }             
                if(isset($convertedData) && !empty($convertedData))
                {
                    $message    = __('api.DATA_FOUND_SUCCESS'); 
                    $status     = true; 
                    $appointment_type_name = isset($appointment_type_id['name']) && !empty($appointment_type_id['name']) ? $appointment_type_id['name'] : null;
                    $data = $convertedData;
                }else
                {
                    $message    = __('api.ERR_DOCTOR_NOT_AVALIABLE'); 
                    $status     = false; 
                    $appointment_type_name = Null;
                    $data = [];
                }

                
            } catch (Exception $e) 
            {
                $this->JsonData['exception'] = $e->getMessage();
            }
        }
        $result = [
          "message" => $message,
          "status" => $status,
          'appointment_type_name' => $appointment_type_name,
          "data" => $data,
          "errors" => $errors
        ];
        return response()->json($result);
    }//getDoctorSlots
    //End Smart Appoimnet 7-may-24 added by roshnai  ======================


}