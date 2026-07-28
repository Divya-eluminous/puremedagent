<?php

namespace App\Http\Controllers\Api\v3;

use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PatientsModel;
use Validator;
use Session;
use Artisan;
use Carbon\Carbon;
use PHPHtmlParser\Dom;
use App\Mail\PatientRegistrationMail;
use App\Models\ActivityLogModel;
use App\Models\SettingsModel;
use App\Models\PatientHasDeviceModel;
use App\Models\PatientHasDocumentsModel;
use App\Models\AppointmentTypesModel;
use App\Models\ExaminationsModel;
use App\Models\AppointmentModel;
use App\Models\PatientHasOrdinationsModel;
use App\Models\OrdinationsModel;
use App\Models\OrdinationHasSpecialistModel;
use App\Models\SpecialistModel;
use App\Models\SpecialistDocumentsModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\SmartphoneAppsModel;
// TODO: Replace with Stancl tenancy equivalent
// use Hyn\Tenancy\Models\Website;
// TODO: Replace with Stancl tenancy equivalent
// use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\AppointmentHasNotificationModel;
// TODO: Replace with Stancl tenancy equivalent
// use Hyn\Tenancy\Environment;
// TODO: Replace with Stancl tenancy equivalent
// use Hyn\Tenancy\Models\Hostname;
use App\Models\AppointmentHasExaminationsModel;
use Config;
//use Hyn\Tenancy\Models\Environment;
use App\Models\CountryCodesModel; //added on 03-march-26 for country code list

//Trait
use App\Traits\GeneralTrait;

use DB;
use Hash;
use Mail;
use PDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input; 
session_start();

use Illuminate\Support\Facades\Http; //added on 29-may-25 for header footer image


class AuthController extends BaseController
{
	private $BaseModel;
    use GeneralTrait;

	public function __construct(
		// TODO: Replace with Stancl tenancy equivalents
		// Website $website,
		// Hostname $Hostname,
		//Environment $Environment,
        PatientsModel $PatientsModel,
        ActivityLogModel $ActivityLogModel,
        SettingsModel $SettingsModel,
        PatientHasDeviceModel $PatientHasDeviceModel,
        PatientHasDocumentsModel $PatientHasDocumentsModel,
        AppointmentTypesModel $AppointmentTypesModel,
        ExaminationsModel $ExaminationsModel,
        AppointmentModel $AppointmentModel,
        PatientHasOrdinationsModel $PatientHasOrdinationsModel,
        OrdinationsModel $OrdinationsModel,
        OrdinationHasSpecialistModel $OrdinationHasSpecialistModel,
        SpecialistModel $SpecialistModel,
        SpecialistDocumentsModel $SpecialistDocumentsModel,
        AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
        ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
        SmartphoneAppsModel $SmartphoneAppsModel,
        PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
        AppointmentHasNotificationModel $AppointmentHasNotificationModel,
        AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
		CountryCodesModel $CountryCodesModel


    )
        {
		// dump("hi");

    	// TODO: Replace with Stancl tenancy equivalent
    	// $this->HostnameRepository = app(HostnameRepository::class);
        $this->BaseModel  = $PatientsModel;
        $this->PatientsModel  = $PatientsModel;
        // $this->website  = $website; 
       
        // $this->Hostname = $Hostname;
        $this->ActivityLogModel  = $ActivityLogModel;
        $this->SettingsModel  = $SettingsModel;
        $this->PatientHasDeviceModel = $PatientHasDeviceModel;
        $this->PatientHasDocumentsModel = $PatientHasDocumentsModel;
        $this->AppointmentTypesModel = $AppointmentTypesModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->AppointmentModel = $AppointmentModel;
        $this->PatientHasOrdinationsModel   = $PatientHasOrdinationsModel;
        $this->OrdinationsModel             = $OrdinationsModel;
        $this->OrdinationHasSpecialistModel = $OrdinationHasSpecialistModel;
        $this->SpecialistModel              = $SpecialistModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;
        $this->SmartphoneAppsModel = $SmartphoneAppsModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->AppointmentHasNotificationModel = $AppointmentHasNotificationModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
		$this->CountryCodesModel = $CountryCodesModel; //added on 03-march-26 for country code list	

        // $this->ViewData = [];
        // $this->JsonData = [];

        // $this->ModuleTitle = 'Patients';
        // $this->ModuleView  = 'admin.patients.';
        // $this->ModulePath = 'admin.patients.';
    }

    public function registerPatient(Request $request)
    {
    	//dd($request->all());
    	Log::info('innnnnnnnnn registerPatient');
    	Log::info($request->all());

		$errors = [];
		$data = [];
		$message = __('api.AUTH_INVALID_USER');
		$status = false;
		$log_id = '';
    	$request_mobile_no  = str_replace(" ", "", $request->mobile_no);

		$inputdata = $request->all();
		$inputdata['mobile_no'] = ltrim($request_mobile_no,'0');
		//dd($inputdata['mobile_no']);
    	try {

    		$validator = Validator::make($inputdata,[
						  'first_name' 	=> 'required',
						  'family_name' 	=> 'required',
						//   'country_code' => 'required|~^(\+[1-9][0-9]*|00[1-9][0-9]*)$~',
						  'country_code' => [
												'required',
												'regex:~^(\+[1-9][0-9]*|00[1-9][0-9]*)$~',
											],
						  'mobile_no' 	=> 'required|regex:/^(?!0{2})0?[0-9]+$/|numeric',
						  //'mobile_no' 	=> 'required|regex:/^[1-9][0-9]*$/|numeric|unique:patients,mobile_no,id,deleted_at,NULL',
						  // 'email'		=> 'required|email|unique:patients,email',
						  // 'email'		=> 'required|email',
						  'email'		=> 'required|email',
						  'birth_date'  => 'required',
						  'age' 		=> 'required|numeric',
						  'login_type' 	=> 'required',
						  'password' 	=> 'required',
						  'postal_code' => 'required',
                		  'gender'      => 'required',
                		  'country' => 'required|in:Austria,Germany,Switzerland',//Roshani Added For CR #102
						],
						[
						  'first_name.required'	=> __('api.AUTH_FIRSTNAME_REQ'),
						  'family_name.required' 	=> __('api.AUTH_FAMILYNAME_REQ'),
						  'country_code.regex' => __('api.ERR_COUNTRY_CODE_WRONG'),
						  'country_code.required' => __('api.AUTH_COUNTRY_CODE_REQ'),
						  'mobile_no.required' 	=> __('api.AUTH_MOBILENO_REQ'),
						  'mobile_no.regex'       =>  __('api.ERR_MOBILE_NO_FORMAT'),
						  //'mobile_no.numeric' => __('api.AUTH_FORMAT_MOBILE_USER'),
						  // 'mobile_no.unique' => __('api.AUTH_UNIQUE_MOBILE_USER'),
						  'email.required' 		=> __('api.AUTH_EMAIL_REQ'),
						  //'email.unique' 		=> __('api.AUTH_UNIQUE_EMAIL_USER'),
						  'email.email' 		=> __('api.AUTH_FORMAT_EMAIL_USER'),
						  'birth_date.required' => __('api.PATIENT_BIRTH_DATE_REQ'),
						  'age.required'		=> __('api.AUTH_AGE_REQ'),
						  'age.numeric' 		=> __('api.AUTH_FORMAT_AGE_USER'),
						  'login_type.required' => __('api.PATIENT_LOGIN_TYPE_FIELD_REQUIRED'),
						  'password.required'   => __('api.AUTH_USER_PAAWORD_REQUEIED'),
						  'postal_code.required'=> __('api.PATIENT_POSTALCODE_REQ'),
                          'gender.required'     => __('api.ERR_PATIENT_GENDER_REQUIRED'),
                          'country.required'   	=> __('api.ERR_COUNTRY_REQUIRED'),//Roshani Added For CR #102
                          'country.in'   	=> __('api.ERR_COUNTRY_IN'),//Roshani Added For CR #102
						]
						);
    		// Add custom postal code validation logic
			// $validator->after(function ($validator) use ($request) {
			//     $country = $request->country;
			//     $postalCode = $request->postal_code;

			//     if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
			//         // $validator->errors()->add('postal_code', __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY'));
			//         $data = [];
			//         $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY');
			// 		          	$status = false;
			// 		          	return self::_sendResult($message,$data,$errors,$status);
			//     }

			//     if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
			//         // $validator->errors()->add('postal_code', __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2'));
			//         $data = [];

			//         $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2');
			// 		          	$status = false;
			// 		          	return self::_sendResult($message,$data,$errors,$status);
			//     }
			// });
			if ($validator->fails()) {

			  	$errors[] = $validator->errors();
			}else{

				try
				{

					//Roshani Added For CR #102
						$country = $request->country;
			   	 		$postalCode = $request->postal_code;
						// Postal Code Validation for Germany
					    if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
					        $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY');
					        return self::_sendResult($message, $data, ['postal_code' => $message], $status);
					    }

					    // Postal Code Validation for Austria and Switzerland
					    if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
					        $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2');
					        return self::_sendResult($message, $data, ['postal_code' => $message], $status);
					    }

					//Roshani Added For CR #102
					//dd("---"); first name ,last name, mobile, birth date

					//Commented below code on 11-nov-22
					/*$collection = $this->BaseModel
							      ->where('first_name',trim($request->first_name))
								  ->where('family_name',trim($request->family_name))
								  ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date)))
								  ->where('mobile_no','0'.$inputdata['mobile_no'])
								  ->whereNull('deleted_at')
								  ->get();*/

					//Here remove zero from mobile no below code on 10-nov-22
					$collection = $this->BaseModel
							      // ->where('first_name',trim($request->first_name)) //commented on 15-dec-23
								  // ->where('family_name',trim($request->family_name)) //commented on 15-dec-23
								  ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date)))
								  ->where('mobile_no',$inputdata['mobile_no'])
								  ->whereNull('deleted_at')
								  ->get();


					if(!empty($collection) && count($collection)>0)
					{
						$errors[] =
						[
			              // "mobile_no" => __('api.PATIENT_MOBILE_NO_UNIQUE'), //commented on 15-dec-23
							"mobile_no" => __('api.ERR_PATIENT_UNIQUE'), //added on 15-dec-23 for duplicate patient
			          	];
					}
					else
					{

						/*********CR#191***added on 24-oct-24**************************/
						// $minimumAgeSetting = $this->SettingsModel
				        //             ->where('setting_key','=','MINIMUM_AGE')
				        //             ->whereStatus(1)
				        //             ->first(['setting_value']);

				        //  if(isset($minimumAgeSetting)) 
				        //  {
				        //  	// $age   = (date('Y') - date('Y',strtotime($request->birth_date))); //commented on 16-june-25 for #343

				        //  	//if($age<=$minimumAgeSetting['setting_value'])// commented on 16-june-25
				        //  	//{

				        //  	//added on 16-june-25 for #343
			            //     $birthDate = Carbon::parse($request->birth_date);
			            //     $minAgeYears = (int)$minimumAgeSetting['setting_value'];
			            //     $today = Carbon::now();
			            //     $minAgeDate = $birthDate->copy()->addYears($minAgeYears);
			            //     if ($today->lt($minAgeDate)) 
			            //     {
				        //  	 	$errors[] = 
						// 		[
						// 			"birth_date" => __('api.ERR_MIMIMUM_AGE'), 
					    //       	];

					    //       	$message = __('api.ERR_MIMIMUM_AGE');
					    //       	$status = false;
					    //       	return self::_sendResult($message,$data,$errors,$status);
					    //     }
				        //  	//}//end of if
				        //  }
				         /***********CR#191****added on 24-oct-24*********************/


				        $collection     = new $this->BaseModel;
			            $collection     = self::_storeOrUpdate($collection,$request);
			            $log_id = $collection->id;
			            Log::info("log id=========>");
			            Log::info($log_id);

			            if(!empty(Config('ordination_id')))
			            {
			                Log::info("in ordination_id=========>");

			                $ordination_patient = self::_storePatientOrdination($collection->id);
			            }
			            //Added by Shyam 16-02-22
	                    if(isset($collection->id) && $collection->id != '')
	                    {
	                        Log::info("in before ==checkPatientAgeReminder=======>");
	                        Log::info($collection->id);

	                        $setReminders = app('App\Http\Controllers\Admin\DashboardController')->checkPatientAgeReminder($collection->id);
	                    }
			            // End
						if ($collection)
			            {
			            	$status = true;
			            	$message = __('api.AUTH_REGISTER_USER_SUCCESS');
			            	$data[] = $collection->only(['first_name','family_name','email','country_code', 'mobile_no','age','login_type','postal_code','birth_date','gender','country']);//Roshani Added country For CR #102

			            	if(isset($request->email))
			            	{
			            	 $result = Mail::to(config('constants.ADMINEMAIL'))->send(new PatientRegistrationMail($collection));
			            	}

			            	self::_createLog('RegisterPatient',$log_id,'info');
			            	$this->ActivityLogModel->addApiLog('Register Patient','Patient Register','Create',null,$data);
			            }
			        }
		        }
	        	catch(\Exception $e) {

	            	$message = __('api.ERR_SOMETHING_WRONG');
	            	$errors[] = [
			              "error" => $e->getMessage(),
			          ];
					self::_createLog('RegisterPatient',$errors,'error');
					// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
	        	}
			}
    	} catch (Exception $e) {

    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

    	}
	    return self::_sendResult($message,$data,$errors,$status);

    }

    public function _storeOrUpdate($collection, $request)
    {

    	Log::info("in authcontroller api _storeOrUpdate");
    	Log::info($request->all());

    	//dd($request->all());
    	$mobile_no  = str_replace(" ", "", $request->mobile_no);

        $collection->first_name     =self::string_operation($request->first_name);

        $collection->family_name    =self::string_operation($request->family_name);
        $collection->country_code   = $request->country_code;
        $collection->road           = $request->road;
        $collection->street_no      = $request->street_no;
        // $collection->mobile_no  	= str_replace("-", "", $request->mobile_no);
        $collection->mobile_no  	= ltrim($mobile_no,'0');;
        $collection->email  	    = $request->email;
        $collection->birth_date     = $request->birth_date;
        $collection->age            = $request->age;
        $collection->old_id         = '99999';
        $collection->status         = 1;//Active
        //uncoment on 13-Jan-23 by swati
        $collection->is_updated     = '1';
        $collection->postal_code    = $request->postal_code;
        $collection->password       = Hash::make($request->password);
        // $collection->str_password   = $request->password;
        $collection->login_type     = $request->login_type;
        $collection->gender  	    = $request->gender;
        $collection->country  	    = $request->country; //Roshani added this line for CR #102 on 11-oct-24
        //Save data
        $collection->save();
        return $collection;
    }

	public function signupSendOtp_renamed_13_oct_23(Request $request)
    {
    	//dd($request->all());

    	Log::info("inn signupSendOtp...........");

		$errors = [];
		$data = [];
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$social_security_number = null;
		$flag = 0;
    	$inputdata = $request->all();
    	try
    	{
    		Log::info("inn try block...........");

    		$validator = Validator::make($inputdata,[
							'first_name' 	=> 'required',
							'family_name' 	=> 'required',
							'country_code' => 'required',
							'mobile_no' 	=> 'required|regex:/^[0-9][0-9]*$/',
							// 'password' 	=> 'required',
						], [
							'first_name.required'	=> __('api.AUTH_FIRSTNAME_REQ'),
							'family_name.required' => __('api.AUTH_FAMILYNAME_REQ'),
							'country_code' => __('api.AUTH_COUNTRY_CODE_REQ'),
							'mobile_no.required' 	=> __('api.AUTH_MOBILENO_REQ'),
							'mobile_no.regex'       =>  __('api.AUTH_MOBILENO_NOTSTARTWITHZERO'),
							// 'password' 	          => __('api.AUTH_USER_PAAWORD_REQUEIED'),
						]);
			if ($validator->fails())
			{
			  	$errors[] = $validator->errors();
			}
			else {

				Log::info("inn else part.....");


				$mobile_no = str_replace(" ", "", $request->mobile_no);
				$mobile_no = ltrim($mobile_no,0);
				$collection = collect([]);
				$collection = $this->BaseModel
									 ->where('first_name',trim($request->first_name))
									 ->where('family_name',trim($request->family_name))
									 ->where('mobile_no',trim($mobile_no))
									 ->first();

				 Log::info($collection);


				if(!empty($collection))
				{
					 Log::info("if not empty collection.........");

					$Authtoken=self::generateAuthTokent($request,$collection);

					 Log::info($Authtoken);

					if(isset($collection->password) && !empty($collection->password) && !empty($request->password) && isset($request->password))
					{
						Log::info("inn password...........");


						$password = Hash::check($request->password, $collection->password);

						Log::info($password);

						if($password)
						{

							Log::info("inn after password..field..........");

							// social_security_number is not null
							if(!empty($request->social_security_number))
							{
								$chkSocialActiveNumber = $this->BaseModel
													 	->where('insurance_number',$request->social_security_number)
													 	->first();
								if(!empty($chkSocialActiveNumber))
								{
									$flag = 1;
									$social_security_number = $chkSocialActiveNumber->insurance_number;
									$collection->social_security_number = $social_security_number;
								}
								else {
									// error message
									$message  = __('api.AUTH_SOCIAL_SECURITY_NUMBER');
						       		$errors[] = [
						              		"error" => __('api.AUTH_SOCIAL_SECURITY_NUMBER'),
						          		];
									self::_createLog('SignupSendOtp',$errors,'error');
									// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
								}
							}
							else {
								$flag = 1;
							}
						}
						else {
							$message = __('api.AUTH_USER_PAAWORD_WORNG');
				       		$errors[] = [
				              		"error" => __('api.AUTH_USER_PAAWORD_WORNG'),
				          		];
							self::_createLog('SignupSendOtp',$errors,'error');
							// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
						}
					}
					else {

						Log::info("inn else part............flag 1..");
						$flag = 1;
					}
					if($collection->status==1 && $flag == 1)
					{

						Log::info("inn collection->status==1");
						Log::info($collection);

						if(!empty($request->device_id))
						{
							$checkAlreadyExist = $this->PatientHasDeviceModel
													 ->where('patient_id','=',$collection->id)
													 ->where('device_type','=',$request->device_type)
													 ->where('device_id','=',$request->device_id)
													 ->whereNull('deleted_at')
													 ->first();
							if(empty($checkAlreadyExist))
							{
								$device_data[] = array(
	                                    'patient_id'=> $collection->id,
	                                    'device_type'=> $request->device_type,
	                                    'device_id'=> $request->device_id,
	                                );
								$this->PatientHasDeviceModel->insert($device_data);
							}
						}


						$status = true;
						$collection = $this->_updateOtp($collection);
						$collection->social_security_number = $collection->insurance_number;
						$log_id = $collection->id;

						Log::info("inn log_id...");
						Log::info($log_id);

						$message = __('api.AUTH_USER_VALIDATED_SUCCESS');
						$data[]  = $collection->only(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','message','social_security_number']);
						self::_createLog('SignupSendOtp',$log_id,'info');
						$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
					}
					else {
						$message = __('api.AUTH_INACTIVE_USER');
			       		$errors[] = [
			              		"error" => __('api.AUTH_INACTIVE_USER'),
			          		];
						self::_createLog('SignupSendOtp',$errors,'error');
						// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					}
				}
				else {
					$message = __('api.AUTH_INVALID_PATIENT');
			       	$errors[] = [
			              	"error" => __('api.AUTH_INVALID_PATIENT'),
			          	];
					self::_createLog('SignupSendOtp',$errors,'error');
					// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
				}
			}
    	}
    	catch (Exception $e)
    	{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
					"error" => __('api.ERR_SOMETHING_WRONG'),
					"error_msg" => $e->getMessage(),
			    ];
			self::_createLog('SignupSendOtp',$errors,'error');
    		// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Delete',null,$data);
    	}
	    return self::_sendResult($message,$data,$errors,$status);
    }//


    public function signupSendOtp(Request $request)
    {
    	//dd($request->all());

    	Log::info("inn signupSendOtp...........");

		$errors = [];
		$data = [];
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$social_security_number = null;
		$flag = 0;
    	$inputdata = $request->all();
    	try
    	{
    		Log::info("inn try block...........");

    		$validator = Validator::make($inputdata,[
							'first_name' 	=> 'required',
							'family_name' 	=> 'required',
							'country_code' => 'required',
							'mobile_no' 	=> 'required|regex:/^[0-9][0-9]*$/',
							// 'password' 	=> 'required',
						], [
							'first_name.required'	=> __('api.AUTH_FIRSTNAME_REQ'),
							'family_name.required' => __('api.AUTH_FAMILYNAME_REQ'),
							'country_code' => __('api.AUTH_COUNTRY_CODE_REQ'),
							'mobile_no.required' 	=> __('api.AUTH_MOBILENO_REQ'),
							'mobile_no.regex'       =>  __('api.AUTH_MOBILENO_NOTSTARTWITHZERO'),
							// 'password' 	          => __('api.AUTH_USER_PAAWORD_REQUEIED'),
						]);
			if ($validator->fails())
			{
			  	$errors[] = $validator->errors();
			}
			else {

				Log::info("inn else part.....");


				$mobile_no = str_replace(" ", "", $request->mobile_no);
				$mobile_no = ltrim($mobile_no,0);
				$collection = collect([]);
				$collection = $this->BaseModel
									 ->where('first_name',trim($request->first_name))
									 ->where('family_name',trim($request->family_name))
									 ->where('mobile_no',trim($mobile_no))
									 ->first();

				 Log::info($collection);


				if(!empty($collection))
				{
					 Log::info("if not empty collection.........");

					$Authtoken=self::generateAuthTokent($request,$collection);

					 Log::info($Authtoken);

					if(isset($collection->password) && !empty($collection->password) && !empty($request->password) && isset($request->password))
					{
						Log::info("inn password...........");


						$password = Hash::check($request->password, $collection->password);

						Log::info($password);

						if($password)
						{

							Log::info("inn after password..field..........");

							// social_security_number is not null
							if(!empty($request->social_security_number))
							{
								$chkSocialActiveNumber = $this->BaseModel
													 	->where('insurance_number',$request->social_security_number)
													 	->first();
								if(!empty($chkSocialActiveNumber))
								{
									$flag = 1;
									$social_security_number = $chkSocialActiveNumber->insurance_number;
									$collection->social_security_number = $social_security_number;
								}
								else {
									// error message
									$message  = __('api.AUTH_SOCIAL_SECURITY_NUMBER');
						       		$errors[] = [
						              		"error" => __('api.AUTH_SOCIAL_SECURITY_NUMBER'),
						          		];
									self::_createLog('SignupSendOtp',$errors,'error');
									// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
								}
							}
							else {
								$flag = 1;
							}
						}
						else {
							$message = __('api.AUTH_USER_PAAWORD_WORNG');
				       		$errors[] = [
				              		"error" => __('api.AUTH_USER_PAAWORD_WORNG'),
				          		];
							self::_createLog('SignupSendOtp',$errors,'error');
							// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
						}
					}
					else {

						Log::info("inn else part............flag 1..");
						$flag = 0;
					}
					if($collection->status==1 && $flag == 1)
					{

						Log::info("inn collection->status==1");
						Log::info($collection);

						if(!empty($request->device_id))
						{
							$checkAlreadyExist = $this->PatientHasDeviceModel
													 ->where('patient_id','=',$collection->id)
													 ->where('device_type','=',$request->device_type)
													 ->where('device_id','=',$request->device_id)
													 ->whereNull('deleted_at')
													 ->first();
							if(empty($checkAlreadyExist))
							{
								$device_data[] = array(
	                                    'patient_id'=> $collection->id,
	                                    'device_type'=> $request->device_type,
	                                    'device_id'=> $request->device_id,
	                                );
								$this->PatientHasDeviceModel->insert($device_data);
							}
						}


						$status = true;
						$collection = $this->_updateOtp($collection);
						$collection->social_security_number = $collection->insurance_number;
						$log_id = $collection->id;

						Log::info("inn log_id...");
						Log::info($log_id);

						$message = __('api.AUTH_USER_VALIDATED_SUCCESS');
						$data[]  = $collection->only(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','message','social_security_number']);
						self::_createLog('SignupSendOtp',$log_id,'info');
						$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
					}
					else if($flag==0)
					{
							$message = __('api.AUTH_USER_PAAWORD_WORNG');
				       		$errors[] = [
				              		"error" => __('api.AUTH_USER_PAAWORD_WORNG'),
				          		];
							self::_createLog('SignupSendOtp',$errors,'error');
							// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					}
					else {
						$message = __('api.AUTH_INACTIVE_USER');
			       		$errors[] = [
			              		"error" => __('api.AUTH_INACTIVE_USER'),
			          		];
						self::_createLog('SignupSendOtp',$errors,'error');
						// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					}
				}
				else {
					$message = __('api.AUTH_INVALID_PATIENT');
			       	$errors[] = [
			              	"error" => __('api.AUTH_INVALID_PATIENT'),
			          	];
					self::_createLog('SignupSendOtp',$errors,'error');
					// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
				}
			}
    	}
    	catch (Exception $e)
    	{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
					"error" => __('api.ERR_SOMETHING_WRONG'),
					"error_msg" => $e->getMessage(),
			    ];
			self::_createLog('SignupSendOtp',$errors,'error');
    		// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Delete',null,$data);
    	}
	    return self::_sendResult($message,$data,$errors,$status);
    }//

    //did changes on 8-jan-23
    public function signupSendOtpNew(Request $request)
    {
    	//dd($request->all());

    	Log::info("inn signupSendOtp...........");

		$errors = [];
		$data = [];
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$social_security_number = null;
		$flag = 0;
    	$inputdata = $request->all();
    	try
    	{
    		Log::info("inn try block...........");

    		$validator = Validator::make($inputdata,[
							// 'first_name' 	=> 'required',//commented on 8-jan-23
							// 'family_name' 	=> 'required', //commented on 8-jan-23
							// 'country_code' => 'required',
							// 'mobile_no' 	=> 'required|regex:/^[0-9][0-9]*$/',
							'birth_date' => 'required', //added on 8-jan-23
							// 'password' 	=> 'required',
							'country_code' => [
												'required',
												'regex:~^(\+[1-9][0-9]*|00[1-9][0-9]*)$~',
											],
						    'mobile_no' 	=> 'required|regex:/^(?!0{2})0?[0-9]+$/|numeric',
						], [
							// 'first_name.required'	=> __('api.AUTH_FIRSTNAME_REQ'), //commented on 8-jan-23
							// 'family_name.required' => __('api.AUTH_FAMILYNAME_REQ'), //commented on 8-jan-23
							'country_code.required' => __('api.AUTH_COUNTRY_CODE_REQ'),
							'mobile_no.required' 	=> __('api.AUTH_MOBILENO_REQ'),
							'mobile_no.regex'       =>  __('api.ERR_MOBILE_NO_FORMAT'),
							'birth_date.required' => __('api.PATIENT_BIRTH_DATE_REQ'),
							// 'password' 	          => __('api.AUTH_USER_PAAWORD_REQUEIED'),
							'mobile_no.regex'       =>  __('api.ERR_MOBILE_NO_FORMAT'),
							'country_code.regex'    =>  __('api.ERR_COUNTRY_CODE_WRONG'),

						]);
			if ($validator->fails())
			{
			  	$errors[] = $validator->errors();
			}
			else {

				Log::info("inn else part.....");


				$mobile_no = str_replace(" ", "", $request->mobile_no);
				$mobile_no = ltrim($mobile_no,0);
				$collection = collect([]);
				// $collection = $this->BaseModel
				// 					 // ->where('first_name',trim($request->first_name)) //commented on 8-jan-23
				// 					 // ->where('family_name',trim($request->family_name)) //commented on 8-jan-23
				// 					 ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date))) //added on 8-jan-23
				// 					 ->where('mobile_no',trim($mobile_no))
				// 					 ->where('country_code',trim($request->country_code))
				// 					 ->first();
				// 🧩 Normalize country code
				$countryCode = trim($request->country_code);
				$normalizedCode1 = preg_replace('/^\+/', '00', $countryCode); // +43 → 0043
				$normalizedCode2 = preg_replace('/^00/', '+', $countryCode); // 0043 → +43
				$collection = $this->BaseModel
				->whereDate('birth_date', date('Y-m-d', strtotime($request->birth_date)))
				->where('mobile_no', trim($mobile_no))
				->where(function ($query) use ($countryCode, $normalizedCode1, $normalizedCode2) {
					$query->where('country_code', $countryCode)
						->orWhere('country_code', $normalizedCode1)
						->orWhere('country_code', $normalizedCode2);
				})
				->first();

				 Log::info($collection);
				

				if(!empty($collection))
				{
					 Log::info("if not empty collection.........");

					$Authtoken=self::generateAuthTokent($request,$collection);

					 Log::info($Authtoken);

					if(isset($collection->password) && !empty($collection->password) && !empty($request->password) && isset($request->password))
					{
						Log::info("inn password...........");


						$password = Hash::check($request->password, $collection->password);

						Log::info($password);

						if($password)
						{

							Log::info("inn after password..field..........");

							// social_security_number is not null
							if(!empty($request->social_security_number))
							{
								$chkSocialActiveNumber = $this->BaseModel
													 	->where('insurance_number',$request->social_security_number)
													 	->first();
								if(!empty($chkSocialActiveNumber))
								{
									$flag = 1;
									$social_security_number = $chkSocialActiveNumber->insurance_number;
									$collection->social_security_number = $social_security_number;
								}
								else {
									// error message
									$message  = __('api.AUTH_SOCIAL_SECURITY_NUMBER');
						       		$errors[] = [
						              		"error" => __('api.AUTH_SOCIAL_SECURITY_NUMBER'),
						          		];
									self::_createLog('SignupSendOtp',$errors,'error');
									// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
								}
							}
							else {
								$flag = 1;
							}
						}
						else {
							$message = __('api.AUTH_USER_PAAWORD_WORNG');
				       		$errors[] = [
				              		"error" => __('api.AUTH_USER_PAAWORD_WORNG'),
				          		];
							self::_createLog('SignupSendOtp',$errors,'error');
							// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
						}
					}
					else {

						Log::info("inn else part............flag 1..");
						$flag = 0;
					}
					// if($collection->status==1 && $flag == 1)
					if($flag == 1)
					{

						Log::info("inn collection->status==1");
						Log::info($collection);

						if(!empty($request->device_id))
						{
							/***********12-aug-24*****************************/
							$this->PatientHasDeviceModel
							     ->where('patient_id','=',$collection->id)
				        		 //->where('device_id',$request->device_id)
				        		 ->where('device_type',$request->device_type)
				        		 ->delete();
				        	/*************12-aug-24****************************/


				        	/*************8-may-25***************************/
				        	$checkDeviceIdExist = $this->PatientHasDeviceModel
													 ->where('patient_id','!=',$collection->id)
													 ->where('device_type','=',$request->device_type)
													 ->where('device_id','=',$request->device_id)
													 ->whereNull('deleted_at')
													 ->get();
						    if(isset($checkDeviceIdExist) && !empty($checkDeviceIdExist))
						    {
						    	foreach($checkDeviceIdExist as $k=>$v)
						    	{
						    		$this->PatientHasDeviceModel
								     ->where('patient_id','=',$v->patient_id)
					        		 ->where('device_id',$v->device_id)
					        		 ->where('device_type',$v->device_type)
					        		 ->delete();
						    	}
						    	 
						    }//checkDeviceIdExist													 

				        	/*************8-may-25*****************************/




							$checkAlreadyExist = $this->PatientHasDeviceModel
													 ->where('patient_id','=',$collection->id)
													 ->where('device_type','=',$request->device_type)
													 ->where('device_id','=',$request->device_id)
													 ->whereNull('deleted_at')
													 ->first();
							if(empty($checkAlreadyExist))
							{
								$device_data[] = array(
	                                    'patient_id'=> $collection->id,
	                                    'device_type'=> $request->device_type,
	                                    'device_id'=> $request->device_id,
	                                );
								$this->PatientHasDeviceModel->insert($device_data);
							}
						}


						$status = true;
						$collection = $this->_updateOtp($collection);
						$collection->social_security_number = $collection->insurance_number;
						$log_id = $collection->id;

						Log::info("inn log_id...");
						Log::info($log_id);

						$message = __('api.AUTH_USER_VALIDATED_SUCCESS');
						$data[]  = $collection->only(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','message','social_security_number']);
						self::_createLog('SignupSendOtp',$log_id,'info');
						$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
					}
					// else if($flag==0)
					else
					{
							$message = __('api.AUTH_USER_PAAWORD_WORNG');
				       		$errors[] = [
				              		"error" => __('api.AUTH_USER_PAAWORD_WORNG'),
				          		];
							self::_createLog('SignupSendOtp',$errors,'error');
							// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					}
					// else {
					// 	$message = __('api.AUTH_INACTIVE_USER');
			       	// 	$errors[] = [
			        //       		"error" => __('api.AUTH_INACTIVE_USER'),
			        //   		];
					// 	self::_createLog('SignupSendOtp',$errors,'error');
					// 	// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					// }
				}
				else {
					// $message = __('api.AUTH_INVALID_PATIENT'); //commented on 5-nov-25
					$message = __('api.ERR_APP_LOGIN_COUNTRY_CODE'); //added on 5-nov-25
			       	$errors[] = [
			              	// "error" => __('api.AUTH_INVALID_PATIENT'), //commented on 5-nov-25
			       		    "error" => __('api.ERR_APP_LOGIN_COUNTRY_CODE'), //added on 5-nov-25
			          	];
					self::_createLog('SignupSendOtp',$errors,'error');
					// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
				}
			}
    	}
    	catch (Exception $e)
    	{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
					"error" => __('api.ERR_SOMETHING_WRONG'),
					"error_msg" => $e->getMessage(),
			    ];
			self::_createLog('SignupSendOtp',$errors,'error');
    		// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Delete',null,$data);
    	}
	    return self::_sendResult($message,$data,$errors,$status);
    }//signupSendOtpNew



    public function generateAuthTokent($request,$collection)
    {
    	//dd("dfdsf");
    	$flag = $nullPass = 0;
    	$credentials = [];
    	if(isset($collection->password) && !empty($collection->password) && isset($request->password) && !empty($request->password))
		{
			//dd("dfdsf");
			$credentials['id']       = $collection->id;
	    	$credentials['password'] = $request->password;
		}
		else
		{
			//dd("---");
			//$collection = $this->BaseModel->find($collection->id);
			$collection->str_password = $collection->email;
			$collection->password     = Hash::make($collection->email);
			$collection->save();

			$credentials['id']        = $collection->id;
	    	$credentials['password']  = $collection->email;

	    	$nullPass = 1;
		}

		if(!empty($credentials))
    	{
    		$setting_logged_mins = $this->SettingsModel
				                    ->where('setting_key','=','APP_LOGGED_MINS')
				                    ->whereStatus(1)
				                    ->first(['setting_value']);

			$app_logged_mins = 60*24;//default 1 day if record not found
			if(!empty($setting_logged_mins)){
				$app_logged_mins = (int)$setting_logged_mins->setting_value;
			}
	     	config()->set('jwt.ttl', $app_logged_mins);

        	if (! $token = auth('api')->attempt($credentials))
        	{

	      	}
	      	else
	      	{
	      		$collection->api_access_token = $token;
	      		$collection->save();
	      		if($nullPass == 1)
	      		{
	      			$collection->str_password = '';
					$collection->password     = '';
					$collection->save();
	      		}
                $flag = 1;
	      	}
    	}
    	return $flag;
    }

  //   public function generateAuthTokenttest($request,$collection)
  //   {
  //   	$flag = $nullPass = 0;
  //   	$credentials = [];
  //   	//dd($collection->password,$request->password);
  //   	if(!empty($collection->password) && isset($request->password) && !empty($request->password))
		// {
		// 	$credentials['id']       = $collection->id;
	 //    	$credentials['password'] = $request->password;
		// }
		// else
		// {
		// 	dd("--");
		// 	//$collection = $this->BaseModel->find($collection->id);
		// 	$collection->str_password = $collection->email;
		// 	$collection->password     = Hash::make($collection->email);
		// 	$collection->save();

		// 	$credentials['id']        = $collection->id;
	 //    	$credentials['password']  = $collection->email;

	 //    	$nullPass = 1;
		// }

		// if(!empty($credentials))
  //   	{
  //   		$setting_logged_mins = $this->SettingsModel
		// 		                    ->where('setting_key','=','APP_LOGGED_MINS')
		// 		                    ->whereStatus(1)
		// 		                    ->first(['setting_value']);

		// 	$app_logged_mins = 60*24;//default 1 day if record not found
		// 	if(!empty($setting_logged_mins)){
		// 		$app_logged_mins = (int)$setting_logged_mins->setting_value;
		// 	}
	 //     	config()->set('jwt.ttl', $app_logged_mins);

  //       	if (! $token = auth('api')->attempt($credentials))
  //       	{

	 //      	}
	 //      	else
	 //      	{
	 //      		$collection->api_access_token = $token;
	 //      		$collection->save();
	 //      		if($nullPass == 1)
	 //      		{
	 //      			$collection->str_password = '';
		// 			$collection->password     = '';
		// 			$collection->save();
	 //      		}
  //               $flag = 1;
	 //      	}
  //   	}
  //   	return $flag;
  //   }
  	public function signupVerifyOtp(Request $request)
    {
		$errors = [];
		$data = [];
		$message = __('api.AUTH_INVALID_OTP');
		$status = false;

		$validator = Validator::make($request->all(), [
                'patient_id' => 'required',
                'otp' => 'required|numeric',
	            ],
				[
				  'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
				  'otp.required' => __('api.AUTH_OTP_REQ'),
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{
			$data = [];

			$collection = $this->BaseModel->find($request->patient_id);

			if(empty($collection->password))
			{
				$collection->password     = Hash::make($request->otp);
				// $collection->str_password = $request->otp;
				$collection->save();
				$is_password_set = 0;
				$data['is_password_set'] = 0;
			}
			else
			{
				$is_password_set = 1;
				$data['is_password_set'] = 1;
			}

			//dd($collection);
			if(!empty($collection))
			{
				$start = date('Y-m-d H:i:s', strtotime($collection->otp_created_at));
				$start = new Carbon($start);

				$end =  new Carbon(date('Y-m-d H:i:s', time()));
				$diffInMinutes = $start->diffInMinutes($end);


				if($diffInMinutes<=5){

					if(!empty($collection))
					{//dd($collection->login_otp.'=='.$request->otp);
						if($collection->login_otp==$request->otp){

	     				 $credentials['id']       = $collection->id;
	     				 $credentials['password'] = $collection->password;

						//dd($credentials);
						// var_dump( auth('api')->attempt($credentials));
						//exit();

	     				$setting_logged_mins = $this->SettingsModel
				                                ->where('setting_key','=','APP_LOGGED_MINS')
				                                ->whereStatus(1)
				                                ->first(['setting_value']);

						$app_logged_mins = 60*24;//default 1 day if record not found
						if(!empty($setting_logged_mins)){
							$app_logged_mins = (int)$setting_logged_mins->setting_value;
						}
	     				config()->set('jwt.ttl', $app_logged_mins);
	     				//dd($collection->api_access_token);
			            	if($collection->api_access_token == '')
			            	{
					          	$errors = [
					              "error" => __('api.AUTH_SYSTEM_FAIL'),
					          	];
					          	$message = __('api.AUTH_SYSTEM_FAIL');

								self::_createLog('signupVerifyOtp',$errors,'error');
								// $this->ActivityLogModel->addApiLog('Signup Verify Otp','Verify otp and create login token','Create');
					      	}
					      	else
					      	{

								$status = true;
					      		$token = $collection->api_access_token;
					      		//dd($token);
					      		// $updateQry = DB::table('patients')
	            //                     ->where('id', $collection->id)
	            //                     ->update([
	            //                     			'api_access_token' => $token,
	            //                     		]);
					          	$message = __('api.AUTH_VERIFY_USER_SUCCESS');
					          	// $user = auth()->guard('api')->user()->only(['first_name','family_name','email','mobile_no','age','birth_date','postal_code']);
					          	$user = $collection->only(['first_name','family_name','email','mobile_no','age','birth_date','postal_code']);
					          	//dd($token);
						          //dd($user);
						          // $data[] = [
						          // 	  'user'=>$user,
						          //     'api_access_token' => "Bearer ".$token,
						          //     'token_type' => 'bearer',
						          //     'expires_in' => auth('api')->factory()->getTTL() * 60 * 7
						          // ];
						          $data['user'] = $user;
						          $data['api_access_token'] =  "Bearer ".$token;
						          $data['token_type'] = 'bearer';
						          $data['expires_in'] = auth('api')->factory()->getTTL() * 60 * 7;

					          	//Get Patient Ordination Managemant Listing

						          if($is_password_set == 0)
						          {
						          	$collection->password     = '';
									// $collection->str_password = '';
									$collection->save();
						          }
						    	if(!empty($collection->postal_code))
						    	{
						    		$is_available = 1;

						    		//commented on 23-may-25 old code upgrade
									// $getAllOrdination = self::getLocationPatientHasOrdination($collection->postal_code,$collection->id);

						    		//added on 23-may-25 new code upgrade
                                    $getAllOrdination = self::getLocationPatientHasOrdination($collection->postal_code,$collection->id,strtoupper($collection->country));//Roshani hidden the url below code #102 CR

										if(!empty($getAllOrdination) && sizeof($getAllOrdination)>0)
								        {
								        	// if(!empty($getAllOrdination['error_msg']))
								        	// {
								        	// 	$message = $getAllOrdination['error_msg'];
								        	// }
								          	$ordination_data = $getAllOrdination;
								        }
								        else
								        {
								          $ordination_data = [];
								        }


						    	}
						    	else
						    	{
						    		//$status = false;
						    		$is_available = 0;
						    		$ordination_data = [];
						    		$message      = __('api.AUTH_ORDINATION_POSTAL_CODE');
						    	}
					    		$data['ordination_data'] = $ordination_data;
					    		$data['is_available'] = $is_available;
					          // END PATIENT ORDINATION MANAGEMANT

					          // $user[0]=$logData[0];
					          // $user['api_access_token'] = $data[0]['api_access_token'];
					          // $user['token_type'] = $data[0]['token_type'];
					          // $user['expires_in'] = $data[0]['expires_in'];

					          $user['api_access_token'] = $data['api_access_token'];

					          $user['token_type']       = $data['token_type'];
					          $user['expires_in']       = $data['expires_in'];
					          $data['is_updated'] = $collection->is_updated;

					          self::_createLog('signupVerifyOtp',$data,'info');

					          $this->ActivityLogModel->addApiLog('Signup Verify Otp','Verify otp and create login token','Create',null,$user);
					      	}

						}else{
				        $errors = [
				              "error" => __('api.AUTH_INVALID_OTP'),
				          ];
				        $message = __('api.AUTH_INVALID_OTP');
           	            self::_createLog('signupVerifyOtp',$errors,'error');
           	            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					}
				}

				}else{

		        $errors[] = [
		              "error" => __('api.AUTH_OTP_EXPIRED'),
		          ];
		        $message = __('api.AUTH_OTP_EXPIRED');
		        self::_createLog('signupVerifyOtp',$errors,'error');
		        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
			}
		 }else{

		        $errors = [
		              "error" => __('api.AUTH_INVALID_PATIENT'),
		          ];
		        $message = __('api.AUTH_INVALID_PATIENT');
		        self::_createLog('signupVerifyOtp',$errors,'error');
		        // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
			}

		}

	    return self::_sendResult($message,$data,$errors,$status);
    }

    //Added Swati 14-Jul-2022-=============================
   public function signupVerifyOtpPuremed(Request $request)
    {
    	Log::info("in signupVerifyOtpPuremed.........");
    	$errors = [];
		$data = [];
		$message = __('api.AUTH_INVALID_OTP');
		$status = false;

		$validator = Validator::make($request->all(), [
                'patient_id' => 'required',
                'otp' => 'required|numeric',
	            ],
				[
				  'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
				  'otp.required' => __('api.AUTH_OTP_REQ'),
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{
			$data = [];

			$collection = $this->BaseModel->find($request->patient_id);

			 Log::info("in collection.........");
    	    Log::info($collection);

			if(empty($collection->password))
			{
				$collection->password     = Hash::make($request->otp);
				// $collection->str_password = $request->otp;
				$collection->save();
				$is_password_set = 0;
				$data['is_password_set'] = 0;
			}
			else
			{
				$is_password_set = 1;
				$data['is_password_set'] = 1;
			}
			if(!empty($collection))
			{
				$start = date('Y-m-d H:i:s', strtotime($collection->otp_created_at));
				$start = new Carbon($start);
				$end =  new Carbon(date('Y-m-d H:i:s', time()));
				$diffInMinutes = $start->diffInMinutes($end);
				if($diffInMinutes<=5){
					if(!empty($collection))
					{
						   Log::info("if not empty collection...");

						if($collection->login_otp==$request->otp){
	     				 $credentials['id']       = $collection->id;
	     				 $credentials['password'] = $collection->password;
	     				 $setting_logged_mins = $this->SettingsModel
				                                ->where('setting_key','=','APP_LOGGED_MINS')
				                                ->whereStatus(1)
				                                ->first(['setting_value']);
							$app_logged_mins = 60*24;//default 1 day if record not found
							if(!empty($setting_logged_mins)){
								$app_logged_mins = (int)$setting_logged_mins->setting_value;
							}
							config()->set('jwt.ttl', $app_logged_mins);
			            	if($collection->api_access_token == '')
			            	{
					          	$errors = ["error" => __('api.AUTH_SYSTEM_FAIL'),];
					          	$message = __('api.AUTH_SYSTEM_FAIL');
								self::_createLog('signupVerifyOtpPuremed',$errors,'error');
							}
					      	else
					      	{
					      		 Log::info("in else part...");

								$status = true;
					      		$token = $collection->api_access_token;
					          	$message = __('api.AUTH_VERIFY_USER_SUCCESS');
					          	// $user = auth()->guard('api')->user()->only(['first_name','family_name','email','mobile_no','age','birth_date','postal_code']);
					          	$user = $collection->only(['first_name','family_name','email','mobile_no','age','birth_date','postal_code']);
					          	  $data['user'] = $user;
						          $data['api_access_token'] =  "Bearer ".$token;
						          $data['token_type'] = 'bearer';
						          $data['expires_in'] = auth('api')->factory()->getTTL() * 60 * 7;
					          	//Get Patient Ordination Managemant Listing
								if($is_password_set == 0)
						          {
						          	$collection->password     = '';
									$collection->save();
						          }
						    	if(!empty($collection->postal_code))
						    	{
						    		 Log::info("in postal_code...");


						    		$is_available = 1;

						    		//commented on 23-may-25 old code upgrade
									// $getAllOrdination = self::getLocationPatientHasOrdinationPuremed($collection->postal_code,$collection->id);

						    		//commented on 23-may-25 new code upgrade
									$getAllOrdination = self::getLocationPatientHasOrdinationPuremed($collection->postal_code,$collection->id,strtoupper($collection->country));//Roshani hidden the url below code #102 CR


								   Log::info("in getAllOrdination...");
									Log::info($getAllOrdination);

										if(!empty($getAllOrdination) && sizeof($getAllOrdination)>0)
								        	$ordination_data = $getAllOrdination;
								        else $ordination_data = [];
						    	}
						    	else
						    	{
						    		$is_available = 0;
						    		$ordination_data = [];
						    		$message      = __('api.AUTH_ORDINATION_POSTAL_CODE');
						    	}
					    		$data['ordination_data'] = $ordination_data;
					    		$data['is_available'] = $is_available;
					          $user['api_access_token'] = $data['api_access_token'];
							  $user['token_type']       = $data['token_type'];
					          $user['expires_in']       = $data['expires_in'];
					          $data['is_updated'] = $collection->is_updated;
					          self::_createLog('signupVerifyOtpPuremed',$data,'info');
					          $this->ActivityLogModel->addApiLog('Signup Verify Otp','Verify otp and create login token','Create',null,$user);
					      	}
						}else{
				        $errors = ["error" => __('api.AUTH_INVALID_OTP'),];
				        $message = __('api.AUTH_INVALID_OTP');
           	            self::_createLog('signupVerifyOtpPuremed',$errors,'error');
           	        }
				}
				}else{
		        $errors[] = [ "error" => __('api.AUTH_OTP_EXPIRED'),];
		        $message = __('api.AUTH_OTP_EXPIRED');
		        self::_createLog('signupVerifyOtpPuremed',$errors,'error');
		    }
		 }else{

		        $errors = ["error" => __('api.AUTH_INVALID_PATIENT'),];
		        $message = __('api.AUTH_INVALID_PATIENT');
		        self::_createLog('signupVerifyOtpPuremed',$errors,'error');
		    }
		}

	    return self::_sendResult($message,$data,$errors,$status);
    }

    public function slugify($text, $divider = '-')
    {
      // replace non letter or digits by divider
      $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

      // transliterate
      $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

      // remove unwanted characters
      $text = preg_replace('~[^-\w]+~', '', $text);

      // trim
      $text = trim($text, $divider);

      // remove duplicate divider
      $text = preg_replace('~-+~', $divider, $text);

      // lowercase
      $text = strtolower($text);

      if (empty($text)) {
        return 'n-a';
      }

      return $text;
    }

    // Get Patient Ordinations
   //  public function _getPatientOrdinations($patient_id)
   //  //function getPatientOrdinations(Request $request)
   //  {
   //  	//dd($request->all());
   //  	$data = [];
   //  	$patient_id = $patient_id;
   //  	$getPatientPostalCode = $this->BaseModel->find($patient_id);

   //  	if(!empty($getPatientPostalCode->postal_code))
   //  	{
			// $data = self::getLocationPatientHasOrdination($getPatientPostalCode->postal_code);
   //  	}
   //  	// else
   //  	// {
   //  	// 	$data['error'] = __('api.AUTH_ORDINATION_POSTAL_CODE');
   //  	// }

   //  	return $data;
   //  }

    public function searchOrdination_dev(Request $request)
    {

    	//Log::info('in searchOrdination');


    	//dd($request->all());
		$errors = [];
		$data = [];
		$is_available = 0;
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$ordination_url = null;
		$mapsApiKey    = Config('mapsApiKey');
		$getOrdination = [];
		// $sessting = $this->SettingsModel->where('setting_key','MAX_DISTANCE')->first(['setting_value']);
		// $redius = (int)$sessting->setting_value;
		$redius = self::max_distance();
        $inputdata   = $request->all();

		//Roshani added the below error message or condition for #102 CR

		$validator = Validator::make($inputdata,[
                          'patient_id'  => 'required',
                          'country' => 'required|in:Austria,Germany,Switzerland',
                          'postal_code' => 'required',
                        ],
                        [
                          'patient_id.required'  => __('api.ERR_PATIENT_ID_REQ'),
                          'country.required'   	=> __('api.ERR_COUNTRY_REQUIRED'),
                          'country.in'   	=> __('api.ERR_COUNTRY_IN'),
                          'postal_code.required'   	=> __('api.AUTH_POSTAL_CODE_REQ'),
                        ]
                        );
		// Add custom postal code validation logic
		$validator->after(function ($validator) use ($request) {
		    $country = $request->country;
		    $postalCode = $request->postal_code;

		    if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
		        $validator->errors()->add('postal_code', __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY'));
		    }

		    if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
		        $validator->errors()->add('postal_code', __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('admin.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2'));
		    }
		});

        if ($validator->fails())
        {
            $errors[] = $validator->errors();
        }else{

		//Roshani added the below error message or condition for #102 CR

			try
			{
				$code = $request->postal_code;
				$patient_id = $request->patient_id;
		    	//$getPatientPostalCode = $this->BaseModel->find($patient_id);
		    	$checkCodeIsExist = DB::connection('system')
	                    				->table("ordination")
	                    				->where('postal_code',$code)
	                    				// ->where('status',1)
	                    				->whereNull('deleted_at')
	                    				->get();	
	            // Above query added roshani for check the postal code is exist in table or not if not then show error msg which given by peter in trello point 328 (b => 1) on 10 april 2025        				
		    	if ($checkCodeIsExist->isNotEmpty()) 
		    	{
		    		$data['is_available']    = 1;
		    		// dd($patient_id);
		    		$getOrdinationIds = DB::connection('system')
	                    				->table("patients_has_ordination")
	                    				->where('fk_patient_id',$patient_id)
	                    				->whereNull('deleted_at')
	                    				->get(['fk_ordination_id as id']);
	                // dd($getOrdinationIds);    

	                 // Log::info("getOrdinationIds===>");
	                //  Log::info($getOrdinationIds);


	                $result = null;
	                if(!empty($getOrdinationIds))
	                {
	                	foreach ($getOrdinationIds as $key => $value)
	                	{
	                		if($key == 0)
	                		{
	                			$result .= $value->id;
	                		}
	                		else
	                		{
	                			$result .= ','.$value->id;
	                		}
	                	}
	                }

	                /*********** Roshani Added this code for CR #102 on 11-10-24 *************/

	                // $getPatientDetails = $this->BaseModel
					// 				 	->where('id',$patient_id)
					// 				 	->first();
					$getPatientCountry = 'AUSTRIA';
					$getPatientCountry = strtoupper($request->country);
	                //$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."&sensor=false";
	                // $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+AUSTRIA&sensor=false";
	                $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+".$getPatientCountry."&sensor=false";
	                // $url = "https://maps.googleapis.com/maps/api/geocode/json?address=AU&components=postal_code:".$code."&sensor=false&key=".$mapsApiKey;

	                /*********** Roshani Added this code for CR #102 on 11-10-24 *************/

	               //  Log::info("url===>");
	               //   Log::info($url);



					$URLdata = file_get_contents($url);
					//dd($URLdata);
					if($URLdata)
					{
						$decode_data = json_decode($URLdata);
						if(!empty($decode_data->results) && sizeof($decode_data->results)>0)
						{
							$lat = $decode_data->results[0]->geometry->location->lat;
						  	$lng = $decode_data->results[0]->geometry->location->lng;

		                	if(!empty($result))
		                	{
						      //           		$getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS(".$lat." * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
												// from ordination
												// WHERE ordination.id NOT IN (".$result.")
												// ORDER BY distance"));//LIMIT 10



		                		//commented on 2-aug-24
								/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT
									  ordination.*,(
									    3959 * acos (
									      cos ( radians(".$lat.") )
									      * cos( radians( latitude ) )
									      * cos( radians( longitude ) - radians(".$lng.") )
									      + sin ( radians(".$lat.") )
									      * sin( radians( latitude ) )
									    )
									  ) AS distance
									FROM ordination
									HAVING distance < ".$redius."
									ORDER BY distance
									"));//LIMIT 10
										*/


							$getOrdination=DB::connection('system')->select("SELECT
								  ordination.*,(
								    3959 * acos (
								      cos ( radians(".$lat.") )
								      * cos( radians( latitude ) )
								      * cos( radians( longitude ) - radians(".$lng.") )
								      + sin ( radians(".$lat.") )
								      * sin( radians( latitude ) )
								    )
								  ) AS distance
								FROM ordination
								WHERE ordination.deleted_at IS NULL
								AND ordination.status = 1
								HAVING distance < ".$redius."
								ORDER BY distance
								");//LIMIT 10 // Roshani Added AND ordination.status = 1 condition in above query for trello point 328 (a) on 9 april 2025 updated code on 23-may-25 upgrade




								//WHERE ordination.id NOT IN (".$result.")

		                	}
		                	else
		                	{
						      	// $getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
								// from ordination
								// ORDER BY distance"));//LIMIT 10

		                		//commented on 2-aug-24
								/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT
									  ordination.*,(
									    3959 * acos (
									      cos ( radians(".$lat.") )
									      * cos( radians( latitude ) )
									      * cos( radians( longitude ) - radians(".$lng.") )
									      + sin ( radians(".$lat.") )
									      * sin( radians( latitude ) )
									    )
									  ) AS distance
									FROM ordination
									HAVING distance < ".$redius."
									ORDER BY distance
									"));//LIMIT 10
									*/

							//did changes on 2-aug-24
							$getOrdination=DB::connection('system')->select("SELECT
								  ordination.*,(
								    3959 * acos (
								      cos ( radians(".$lat.") )
								      * cos( radians( latitude ) )
								      * cos( radians( longitude ) - radians(".$lng.") )
								      + sin ( radians(".$lat.") )
								      * sin( radians( latitude ) )
								    )
								  ) AS distance
								FROM ordination
								WHERE ordination.deleted_at IS NULL
								AND ordination.status = 1
								HAVING distance < ".$redius."
								ORDER BY distance
								");//LIMIT 10 // Roshani Added AND ordination.status = 1 condition in above query for trello point 328 (a) on 9 april 2025 added status on 23-may-25 upgrade


								//HAVING distance < ".$redius."

		                	}//else
						}//if


	                }
	                $getOrdination = collect($getOrdination);
	                 // dd($getOrdination);

					if(sizeof($getOrdination)>0)
					{
						$getOrdination = $getOrdination->map(function($item)
						{

							if($item->name == 'puregyn')
			            	{
			            		$item->name = strtoupper($item->name);
			            	}
							$item->logo_path = self::getFilePath($item->logo_path);
							$gethostnames = DB::connection('system')
				                    				->table("domains")
				                    				->where('ordination_id',$item->id)
				                    				->whereNull('deleted_at')
				                    				->first(['fqdn']);
		                    if(!empty($gethostnames))
		                    {
		                    	$fqdn = "https://". $gethostnames->fqdn;
		                    	// $ordination_url = $gethostnames->fqdn;
		                    	$ordination_url = $fqdn;

		                    	$item->ordination_url = $ordination_url;
		                    }
		                    else
		                    {
		                    	$item->ordination_url = null;
		                    }

							$item->distance = number_format(($item->distance * 1.609344),2);
							//$item->distance = round($item->distance);

							$getSpecilistid = DB::connection('system')
		                    				->table("ordination_has_specialist")
		                    				->where('ordination_id',$item->id)
		                    				->whereNull('deleted_at')
		                    				->get(['specialist_id']);

				            if(sizeof($getSpecilistid)>0)
				            {
				            	$getSpecilistid = $getSpecilistid->map(function($spitem)
			            		{
			            			$specialistDetails = DB::connection('system')
			                    						->table("specialist")
			                    						->where('id',$spitem->specialist_id)
			                    						->whereNull('deleted_at')
			                    						->first(['name']);
			                    	// dump("000");
			                    	// dump($specialistDetails); specialist_id
			                    	if(!empty($specialistDetails))
			                    	{

			                    		$spitem->specialist_name = $specialistDetails->name;
			                    		return $spitem;
			                    	}

			            		});
				            }
				            if(!empty($getSpecilistid))
				            {

				            	$item->specialist = $getSpecilistid;
							    return $item;
				            }


						});
						$data['ordination_data'] = $getOrdination;
						$data['is_available'] = 1;
						$message   =  __('api.AUTH_ORDINATION_DATA_SUCCESS');
						$status    = true;
					}
					else
					{
						$data['is_available'] = 1;
						$data['ordination_data'] = [];
			    		$message   = __('api.AUTH_ORDINATION_DATA_NOT_EXIST');
						$status    = false;
					}
		    	}
		    	else
		    	{
		    		// $data = self::getLocationPatientHasOrdination($getPatientPostalCode,$patient_id,'address');
		    		$data['is_available'] = 1;
		    		$data['ordination_data'] = [];
		    		// $message   = __('api.AUTH_ORDINATION_POSTAL_CODE');
		    		$message   = __('api.AUTH_ORDINATION_POSTAL_CODE_NOT_EXIST');
					$status    = false;

		    	}
			}
			catch (Exception $e)
			{
	    		$message = __('api.ERR_SOMETHING_WRONG');
				$errors[] = [
				              "error" => __('api.ERR_SOMETHING_WRONG'),
				              "error_msg" => $e->getMessage(),
				          				];
				self::_createLog('RegisterPatient',$errors,'error');
				// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
	    	}
    	}
		return self::_sendResult($message,$data,$errors,$status);
    }
	  public function searchOrdination(Request $request)
    {

    	Log::info('in searchOrdination v3');


    	//dd($request->all());
		$errors = [];
		$data = [];
		$is_available = 0;
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$ordination_url = null;
		$mapsApiKey    = Config('mapsApiKey');
		$getOrdination = [];
		// $sessting = $this->SettingsModel->where('setting_key','MAX_DISTANCE')->first(['setting_value']);
		// $redius = (int)$sessting->setting_value;
		$redius = self::max_distance();
        $inputdata   = $request->all();

		//Roshani added the below error message or condition for #102 CR

		$validator = Validator::make($inputdata,[
                          'patient_id'  => 'required',
                          'country' => 'required|in:Austria,Germany,Switzerland',
                          'postal_code' => 'required',
                        ],
                        [
                          'patient_id.required'  => __('api.ERR_PATIENT_ID_REQ'),
                          'country.required'   	=> __('api.ERR_COUNTRY_REQUIRED'),
                          'country.in'   	=> __('api.ERR_COUNTRY_IN'),
                          'postal_code.required'   	=> __('api.AUTH_POSTAL_CODE_REQ'),
                        ]
                        );
		// Add custom postal code validation logic
		$validator->after(function ($validator) use ($request) {
		    $country = $request->country;
		    $postalCode = $request->postal_code;

		    if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
		        $validator->errors()->add('postal_code', __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY'));
		    }

		    if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
		        $validator->errors()->add('postal_code', __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('admin.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2'));
		    }
		});

        if ($validator->fails())
        {
            $errors[] = $validator->errors();
        }else{

		//Roshani added the below error message or condition for #102 CR

			try
			{
			log::info("in try block of searchOrdination v3");

				$code = $request->postal_code;
				$patient_id = $request->patient_id;
		    	//$getPatientPostalCode = $this->BaseModel->find($patient_id);
		    	$checkCodeIsExist = DB::connection('system')
	                    				->table("ordination")
	                    				->where('postal_code',$code)
	                    				// ->where('status',1)
	                    				->whereNull('deleted_at')
	                    				->get();	
	            // Above query added roshani for check the postal code is exist in table or not if not then show error msg which given by peter in trello point 328 (b => 1) on 10 april 2025        				
		    	// if ($checkCodeIsExist->isNotEmpty()) 
		    	// {
		    		$data['is_available']    = 1;
		    		// dd($patient_id);
		    		$getOrdinationIds = DB::connection('system')
	                    				->table("patients_has_ordination")
	                    				->where('fk_patient_id',$patient_id)
	                    				->whereNull('deleted_at')
	                    				->get(['fk_ordination_id as id']);
	                // dd($getOrdinationIds);    

	                 // Log::info("getOrdinationIds===>");
	                //  Log::info($getOrdinationIds);


	                $result = null;
	                if(!empty($getOrdinationIds))
	                {
	                	foreach ($getOrdinationIds as $key => $value)
	                	{
	                		if($key == 0)
	                		{
	                			$result .= $value->id;
	                		}
	                		else
	                		{
	                			$result .= ','.$value->id;
	                		}
	                	}
	                }

	                /*********** Roshani Added this code for CR #102 on 11-10-24 *************/

	                // $getPatientDetails = $this->BaseModel
					// 				 	->where('id',$patient_id)
					// 				 	->first();
					$getPatientCountry = 'AUSTRIA';
					$getPatientCountry = strtoupper($request->country);
	                //$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."&sensor=false";
	                // $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+AUSTRIA&sensor=false";
	                $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+".$getPatientCountry."&sensor=false";
	                // $url = "https://maps.googleapis.com/maps/api/geocode/json?address=AU&components=postal_code:".$code."&sensor=false&key=".$mapsApiKey;

	                /*********** Roshani Added this code for CR #102 on 11-10-24 *************/

	                Log::info("url===>");
	                 Log::info($url);



					$URLdata = file_get_contents($url);
					//dd($URLdata);
					if($URLdata)
					{
						$decode_data = json_decode($URLdata);
						if(!empty($decode_data->results) && sizeof($decode_data->results)>0)
						{
							//Log::info("decode_data->results===>");
							//Log::info($decode_data->results[0]->address_components[0]->types[0]);
							if(isset($decode_data->results[0]->address_components[0]->types[0]) && $decode_data->results[0]->address_components[0]->types[0]=='postal_code')
							{
								//Log::info("decode_data->results[0]->address_components[0]->types===>");
								//Log::info($decode_data->results[0]->address_components[0]->types[0]);
								$country = $decode_data->results[0]->address_components[0]->types[0];
							$lat = $decode_data->results[0]->geometry->location->lat;
						  	$lng = $decode_data->results[0]->geometry->location->lng;

		                	if(!empty($result))
		                	{
								Log::info("if not empty result...");

		                		// $getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS(".$lat." * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
						      //           		$getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS(".$lat." * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
												// from ordination
												// WHERE ordination.id NOT IN (".$result.")
												// ORDER BY distance"));//LIMIT 10



		                		//commented on 2-aug-24
								/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT
									  ordination.*,(
									    3959 * acos (
									      cos ( radians(".$lat.") )
									      * cos( radians( latitude ) )
									      * cos( radians( longitude ) - radians(".$lng.") )
									      + sin ( radians(".$lat.") )
									      * sin( radians( latitude ) )
									    )
									  ) AS distance
									FROM ordination
									HAVING distance < ".$redius."
									ORDER BY distance
									"));//LIMIT 10
										*/


							$getOrdination=DB::connection('system')->select("SELECT
								  ordination.*,(
								    3959 * acos (
								      cos ( radians(".$lat.") )
								      * cos( radians( latitude ) )
								      * cos( radians( longitude ) - radians(".$lng.") )
								      + sin ( radians(".$lat.") )
								      * sin( radians( latitude ) )
								    )
								  ) AS distance
								FROM ordination
								WHERE ordination.deleted_at IS NULL
								AND ordination.status = 1
								HAVING distance < ".$redius."
								ORDER BY distance
								");//LIMIT 10 // Roshani Added AND ordination.status = 1 condition in above query for trello point 328 (a) on 9 april 2025 updated code on 23-may-25 upgrade




								//WHERE ordination.id NOT IN (".$result.")

		                	}
		                	else
		                	{
								Log::info("else not empty result...");

		                		// $getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
						      	// $getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
								// from ordination
								// ORDER BY distance"));//LIMIT 10

		                		//commented on 2-aug-24
								/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT
									  ordination.*,(
									    3959 * acos (
									      cos ( radians(".$lat.") )
									      * cos( radians( latitude ) )
									      * cos( radians( longitude ) - radians(".$lng.") )
									      + sin ( radians(".$lat.") )
									      * sin( radians( latitude ) )
									    )
									  ) AS distance
									FROM ordination
									HAVING distance < ".$redius."
									ORDER BY distance
									"));//LIMIT 10
									*/

							//did changes on 2-aug-24
							$getOrdination=DB::connection('system')->select("SELECT
								  ordination.*,(
								    3959 * acos (
								      cos ( radians(".$lat.") )
								      * cos( radians( latitude ) )
								      * cos( radians( longitude ) - radians(".$lng.") )
								      + sin ( radians(".$lat.") )
								      * sin( radians( latitude ) )
								    )
								  ) AS distance
								FROM ordination
								WHERE ordination.deleted_at IS NULL
								AND ordination.status = 1
								HAVING distance < ".$redius."
								ORDER BY distance
								");//LIMIT 10 // Roshani Added AND ordination.status = 1 condition in above query for trello point 328 (a) on 9 april 2025 added status on 23-may-25 upgrade


								//HAVING distance < ".$redius."

		                	}//else
		                	}
							else
							{
								Log::info("postal code data does not exist...");
								// dd("hi");
								// $data = self::getLocationPatientHasOrdination($getPatientPostalCode,$patient_id,'address');
								$data['is_available'] = 1;
								$data['ordination_data'] = [];
								// $message   = __('api.AUTH_ORDINATION_POSTAL_CODE');
								$message   = __('api.AUTH_ORDINATION_POSTAL_CODE_NOT_EXIST');
								$status    = false;
								return self::_sendResult($message,$data,$errors,$status);

							}
						}//if


	                }
	                $getOrdination = collect($getOrdination);
	                 // dd($getOrdination);

					if(sizeof($getOrdination)>0)
					{
						Log::info("getOrdination size is greater than 0...");
						$getOrdination = $getOrdination->map(function($item)
						{

							//commented on 7-nov-25 for #395
							/*if($item->name == 'puregyn')
			            	{
			            		$item->name = strtoupper($item->name);
			            	}*/


							$item->logo_path = self::getFilePath($item->logo_path);
							$gethostnames = DB::connection('system')
				                    				->table("domains")
				                    				->where('ordination_id',$item->id)
				                    				->whereNull('deleted_at')
				                    				->first(['fqdn']);
		                    if(!empty($gethostnames))
		                    {
		                    	$fqdn = "https://". $gethostnames->fqdn;
		                    	// $ordination_url = $gethostnames->fqdn;
		                    	$ordination_url = $fqdn;

		                    	$item->ordination_url = $ordination_url;
		                    }
		                    else
		                    {
		                    	$item->ordination_url = null;
		                    }

							$item->distance = number_format(($item->distance * 1.609344),2);
							//$item->distance = round($item->distance);

							$getSpecilistid = DB::connection('system')
		                    				->table("ordination_has_specialist")
		                    				->where('ordination_id',$item->id)
		                    				->whereNull('deleted_at')
		                    				->get(['specialist_id']);

				            if(sizeof($getSpecilistid)>0)
				            {
				            	$getSpecilistid = $getSpecilistid->map(function($spitem)
			            		{
			            			$specialistDetails = DB::connection('system')
			                    						->table("specialist")
			                    						->where('id',$spitem->specialist_id)
			                    						->whereNull('deleted_at')
			                    						->first(['name']);
			                    	// dump("000");
			                    	// dump($specialistDetails); specialist_id
			                    	if(!empty($specialistDetails))
			                    	{

			                    		$spitem->specialist_name = $specialistDetails->name;
			                    		return $spitem;
			                    	}

			            		});
				            }
				            if(!empty($getSpecilistid))
				            {

				            	$item->specialist = $getSpecilistid;
							    return $item;
				            }


						});
						$data['ordination_data'] = $getOrdination;
						$data['is_available'] = 1;
						$message   =  __('api.AUTH_ORDINATION_DATA_SUCCESS');
						$status    = true;
					}
					else
					{
						Log::info("getOrdination size is 0...");
						Log::info("ordination data does not exist...");
						$data['is_available'] = 1;
						$data['ordination_data'] = [];
			    		$message   = __('api.AUTH_ORDINATION_DATA_NOT_EXIST');
						$status    = false;
					}
		    	// }
		    	// else
		    	// {
		    	// 	// $data = self::getLocationPatientHasOrdination($getPatientPostalCode,$patient_id,'address');
		    	// 	$data['is_available'] = 1;
		    	// 	$data['ordination_data'] = [];
		    	// 	// $message   = __('api.AUTH_ORDINATION_POSTAL_CODE');
		    	// 	$message   = __('api.AUTH_ORDINATION_POSTAL_CODE_NOT_EXIST');
				// 	$status    = false;

		    	// }
			}
			catch (Exception $e)
			{
			log::info("in catch block of searchOrdination v3");
				Log::error("Error in searchOrdination: " . $e->getMessage());
				Log::error('An error occurred: ' . $e->getMessage(), [
					'file' => $e->getFile(),
					'line' => $e->getLine(),
					'trace' => $e->getTraceAsString(),
				]);
	    		$message = __('api.ERR_SOMETHING_WRONG');
				$errors[] = [
				              "error" => __('api.ERR_SOMETHING_WRONG'),
				              "error_msg" => $e->getMessage(),
				          				];
				self::_createLog('RegisterPatient',$errors,'error');
				// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
	    	}
    	}
		return self::_sendResult($message,$data,$errors,$status);
    }
    // public function getOrdination(Request $request)
    public function changeOrdination_dev(Request $request)
    {

		$errors = [];
		$data = [];
		$is_available = 0;
		$getOrdination = [];
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$ordination_url = null;
		$ordination_id = $request->ordination_id;
		$patient_id    = $request->patient_id;
		$mapsApiKey    = Config('mapsApiKey');
		$redius = self::max_distance();
		// $sessting = $this->SettingsModel->where('setting_key','MAX_DISTANCE')->first(['setting_value']);
		// $redius = (int)$sessting->setting_value;
		try
		{
	    	$getPatientPostalCode = $this->BaseModel->find($patient_id);
	    	$code = $getPatientPostalCode->postal_code;
	    	// dd($code);
	    	if(!empty($code) && $code>0)
	    	{
				// $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."&sensor=false";

			 	/*********** Roshani Added this code for CR #102 on 11-10-24 *************/

                $getPatientDetails = $this->BaseModel
								 	->where('id',$patient_id)
								 	->first();
				$getPatientCountry = 'AUSTRIA';
				$getPatientCountry = strtoupper($getPatientDetails['country']);
				// $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+AUSTRIA&sensor=false";
				$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+".$getPatientCountry."&sensor=false";
				// dump($url);
			 	/*********** Roshani Added this code for CR # 102 on 11-10-24 *************/

				$URLdata = file_get_contents($url);
				if($URLdata)
				{
				    // convert into readable format
					$decode_data = json_decode($URLdata);
					if(!empty($decode_data->results) && sizeof($decode_data->results)>0)
					{
				  		$lat = $decode_data->results[0]->geometry->location->lat;
				  		$lng = $decode_data->results[0]->geometry->location->lng;

				  		//commented below code on 6-aug-24
						/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT
										  ordination.*, (
										    3959 * acos (
										      cos ( radians(".$lat.") )
										      * cos( radians( latitude ) )
										      * cos( radians( longitude ) - radians(".$lng.") )
										      + sin ( radians(".$lat.") )
										      * sin( radians( latitude ) )
										    )
										  ) AS distance
										FROM ordination
										INNER JOIN patients_has_ordination
										ON patients_has_ordination.fk_ordination_id=ordination.id
										WHERE (patients_has_ordination.fk_patient_id = ".$patient_id." AND patients_has_ordination.fk_ordination_id != ".$ordination_id.")
										HAVING distance < ".$redius."
										ORDER BY distance
										"));//LIMIT 10
										*/

						//changed below code on 6-aug-24
						$getOrdination=DB::connection('system')->select(("SELECT
										  ordination.*, (
										    3959 * acos (
										      cos ( radians(".$lat.") )
										      * cos( radians( latitude ) )
										      * cos( radians( longitude ) - radians(".$lng.") )
										      + sin ( radians(".$lat.") )
										      * sin( radians( latitude ) )
										    )
										  ) AS distance
										FROM ordination
										INNER JOIN patients_has_ordination
										ON patients_has_ordination.fk_ordination_id=ordination.id
										WHERE (patients_has_ordination.fk_patient_id = ".$patient_id." AND patients_has_ordination.fk_ordination_id != ".$ordination_id.")
										AND ordination.deleted_at IS NULL
										AND ordination.status = 1
										HAVING distance < ".$redius."
										ORDER BY distance
										"));//LIMIT 10

					  	if(sizeof($getOrdination )>0)
					  	{
					  		$getOrdination = collect($getOrdination);
						  	$getOrdination = $getOrdination->map(function($item)
				            {
				            	if($item->name == 'puregyn')
				            	{
				            		$item->name = strtoupper($item->name);
				            	}
            					$item->logo_path = self::getFilePath($item->logo_path);
				            	$ordination_url = null;
				            	if (!empty($item->id) )
				                {
				                	$logo_path = null;
				                	$logo_path = url('/storage/app'.$item->logo_path);
				                	$item->logo_path = self::getFilePath($item->logo_path);
				                	//$item->logo_path = $logo_path;
				                	//get Host URL
				                	$gethostnames = DB::connection('system')
				                    				->table("domains")
				                    				->where('ordination_id',$item->id)
				                    				->whereNull('deleted_at')
				                    				->first(['fqdn']);
				                    if(!empty($gethostnames))
				                    {
				                    	$fqdn = "https://". $gethostnames->fqdn;
				                    	// $ordination_url = $gethostnames->fqdn;
				                    	$ordination_url = $fqdn;
				                    	$item->ordination_url = $ordination_url;
				                    }
				                    else
				                    {
				                    	$item->ordination_url = null;
				                    }
				                    //End
				                    //GET Specialist
				                    $getSpecilistid = DB::connection('system')
				                    				->table("ordination_has_specialist")
				                    				->where('ordination_id',$item->id)
				                    				->whereNull('deleted_at')
				                    				->get(['specialist_id']);
				                    //dd($getSpecilist);
				                    $getSpecilistid = $getSpecilistid->map(function($spitem)
				            		{
				            			//dd($spitem->specialist_id);
				            			$specialistDetails = DB::connection('system')
				                    						->table("specialist")
				                    						->where('id',$spitem->specialist_id)
				                    						->whereNull('deleted_at')
				                    						->first(['name']);
				                    	$spitem->specialist_name = $specialistDetails->name;
				                    	return $spitem;
				            		});
				            		//dd($getSpecilistid);
				                 	$item->specialist = $getSpecilistid;
				                 	$item->distance = number_format(($item->distance * 1.609344),2);
				                 	//$getSpecilistid = [];
				                    return $item;
				                }
				            });


				            $data['ordination_data'] = $getOrdination;
							$data['is_available'] = 1;
							$message      =  __('api.AUTH_ORDINATION_DATA_SUCCESS');
							$status       = true;
						}
						else
						{
							$message      =  __('api.AUTH_ORDINATION_DATA_NOT_EXIST');
							$data['ordination_data'] = [];
							$data['is_available'] = 1;
							$status       = false;
						}
					}
					else
					{
						$message      =  __('api.AUTH_ORDINATION_DATA_NOT_EXIST');
						$data['ordination_data'] = [];
						$data['is_available'] = 1;
						$status       = false;
					}
				}
	    	}
	    	else
	    	{
	    		$data['ordination_data'] = [];
	    		$data['is_available'] = 0;
	    		$message              = __('api.AUTH_ORDINATION_POSTAL_CODE');
				$status              = false;

	    	}
		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}
    	//dd($data);
		return self::_sendResult($message,$data,$errors,$status);
    }
	 public function changeOrdination(Request $request)
    {

		$errors = [];
		$data = [];
		$is_available = 0;
		$getOrdination = [];
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$ordination_url = null;
		$ordination_id = $request->ordination_id;
		$patient_id    = $request->patient_id;
		$mapsApiKey    = Config('mapsApiKey');
		$redius = self::max_distance();
		// $sessting = $this->SettingsModel->where('setting_key','MAX_DISTANCE')->first(['setting_value']);
		// $redius = (int)$sessting->setting_value;
		try
		{
	    	$getPatientPostalCode = $this->BaseModel->find($patient_id);
	    	$code = $getPatientPostalCode->postal_code;
	    	// dd($code);
	    	if(!empty($code) && $code>0)
	    	{
				// $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."&sensor=false";

			 	/*********** Roshani Added this code for CR #102 on 11-10-24 *************/

                $getPatientDetails = $this->BaseModel
								 	->where('id',$patient_id)
								 	->first();
				$getPatientCountry = 'AUSTRIA';
				$getPatientCountry = strtoupper($getPatientDetails['country']);
				// $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+AUSTRIA&sensor=false";
				$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+".$getPatientCountry."&sensor=false";
				// dump($url);
			 	/*********** Roshani Added this code for CR #102 on 11-10-24 *************/

				$URLdata = file_get_contents($url);
				if($URLdata)
				{
				    // convert into readable format
					$decode_data = json_decode($URLdata);
					if(!empty($decode_data->results) && sizeof($decode_data->results)>0)
					{
						if(isset($decode_data->results[0]->address_components[0]->types[0]) && $decode_data->results[0]->address_components[0]->types[0]=='postal_code')
						{
							//Log::info("decode_data->results[0]->address_components[2]->long_name===>");
							//Log::info($decode_data->results[0]->address_components[2]->long_name);
							// $country = $decode_data->results[0]->address_components[2]->long_name;
							$lat = $decode_data->results[0]->geometry->location->lat;
							$lng = $decode_data->results[0]->geometry->location->lng;

							//commented below code on 6-aug-24
							/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT
											ordination.*, (
												3959 * acos (
												cos ( radians(".$lat.") )
												* cos( radians( latitude ) )
												* cos( radians( longitude ) - radians(".$lng.") )
												+ sin ( radians(".$lat.") )
												* sin( radians( latitude ) )
												)
											) AS distance
											FROM ordination
											INNER JOIN patients_has_ordination
											ON patients_has_ordination.fk_ordination_id=ordination.id
											WHERE (patients_has_ordination.fk_patient_id = ".$patient_id." AND patients_has_ordination.fk_ordination_id != ".$ordination_id.")
											HAVING distance < ".$redius."
											ORDER BY distance
											"));//LIMIT 10
											*/

							//changed below code on 6-aug-24
							$getOrdination=DB::connection('system')->select(("SELECT
											ordination.*, (
												3959 * acos (
												cos ( radians(".$lat.") )
												* cos( radians( latitude ) )
												* cos( radians( longitude ) - radians(".$lng.") )
												+ sin ( radians(".$lat.") )
												* sin( radians( latitude ) )
												)
											) AS distance
											FROM ordination
											INNER JOIN patients_has_ordination
											ON patients_has_ordination.fk_ordination_id=ordination.id
											WHERE (patients_has_ordination.fk_patient_id = ".$patient_id." AND patients_has_ordination.fk_ordination_id != ".$ordination_id.")
											AND ordination.deleted_at IS NULL
											AND ordination.status = 1
											HAVING distance < ".$redius."
											ORDER BY distance
											"));//LIMIT 10

							if(sizeof($getOrdination )>0)
							{
								$getOrdination = collect($getOrdination);
								$getOrdination = $getOrdination->map(function($item)
								{

									//commented on 7-nov-25 for #395
									/*if($item->name == 'puregyn')
									{
										$item->name = strtoupper($item->name);
									}*/

									$item->logo_path = self::getFilePath($item->logo_path);
									$ordination_url = null;
									if (!empty($item->id) )
									{
										$logo_path = null;
										$logo_path = url('/storage/app'.$item->logo_path);
										$item->logo_path = self::getFilePath($item->logo_path);
										//$item->logo_path = $logo_path;
										//get Host URL
										$gethostnames = DB::connection('system')
														->table("domains")
														->where('ordination_id',$item->id)
														->whereNull('deleted_at')
														->first(['fqdn']);
										if(!empty($gethostnames))
										{
											$fqdn = "https://". $gethostnames->fqdn;
											// $ordination_url = $gethostnames->fqdn;
											$ordination_url = $fqdn;
											$item->ordination_url = $ordination_url;
										}
										else
										{
											$item->ordination_url = null;
										}
										//End
										//GET Specialist
										$getSpecilistid = DB::connection('system')
														->table("ordination_has_specialist")
														->where('ordination_id',$item->id)
														->whereNull('deleted_at')
														->get(['specialist_id']);
										//dd($getSpecilist);
										$getSpecilistid = $getSpecilistid->map(function($spitem)
										{
											//dd($spitem->specialist_id);
											$specialistDetails = DB::connection('system')
																->table("specialist")
																->where('id',$spitem->specialist_id)
																->whereNull('deleted_at')
																->first(['name']);
											$spitem->specialist_name = $specialistDetails->name;
											return $spitem;
										});
										//dd($getSpecilistid);
										$item->specialist = $getSpecilistid;
										$item->distance = number_format(($item->distance * 1.609344),2);
										//$getSpecilistid = [];
										return $item;
									}
								});


								$data['ordination_data'] = $getOrdination;
								$data['is_available'] = 1;
								$message      =  __('api.AUTH_ORDINATION_DATA_SUCCESS');
								$status       = true;
							}
							else
							{
								$message      =  __('api.AUTH_ORDINATION_DATA_NOT_EXIST');
								$data['ordination_data'] = [];
								$data['is_available'] = 1;
								$status       = false;
							}
						}
						else
						{
							Log::info("postal code data does not exist...");
							$data['is_available'] = 1;
							$data['ordination_data'] = [];
							$message   = __('api.AUTH_ORDINATION_POSTAL_CODE_NOT_EXIST');
							$status    = false;
							return self::_sendResult($message,$data,$errors,$status);

						}
					}
					else
					{
						$message      =  __('api.AUTH_ORDINATION_DATA_NOT_EXIST');
						$data['ordination_data'] = [];
						$data['is_available'] = 1;
						$status       = false;
					}
				}
	    	}
	    	else
	    	{
	    		$data['ordination_data'] = [];
	    		$data['is_available'] = 0;
	    		$message              = __('api.AUTH_ORDINATION_POSTAL_CODE');
				$status              = false;

	    	}
		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}
    	//dd($data);
		return self::_sendResult($message,$data,$errors,$status);
    }
    //Roshani made changes in below code for pass contry #102 CR
    public function getLocationPatientHasOrdination($code,$patient_id,$country)
    {

    	$data = [];
    	$getOrdination = [];
    	$final_getOrdination = [];
    	$error_message = null;
    	$is_available = 1;
    	$patientHasOrdination = [];
    	$mapsApiKey    = Config('mapsApiKey');
    	// $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."&sensor=false";
   		//Roshani hidden the url below code #102 CR
    	// $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+AUSTRIA&sensor=false";
   		//Roshani hidden the url below code #102 CR
   		//Roshani added the url below code #102 CR
	     $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+".$country."&sensor=false";
   		//Roshani added the url below code #102 CR
		$data = file_get_contents($url);
		// $sessting = $this->SettingsModel->where('setting_key','MAX_DISTANCE')->first(['setting_value']);
		// $redius = (int)$sessting->setting_value;
		$redius = self::max_distance();
		//dd($redius);
		if($data)
		{
			// convert into readable format
			$decode_data = json_decode($data);
			if(!empty($decode_data->results) && sizeof($decode_data->results)>0)
			{
				$lat = $decode_data->results[0]->geometry->location->lat;
		  		$lng = $decode_data->results[0]->geometry->location->lng;
		  		if(!empty($patient_id))
			  	{
			  		$patientHasOrdination = $this->PatientHasOrdinationsModel
						    	->where('fk_patient_id',$patient_id)
						    	->where('status','1')
						    	->get();

					$parentPatientId = $this->PatientsModel->find($patient_id);

					$masterPatientId =  DB::connection('system')
                						->table("patients")
                                    	->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
                                    	->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
                                    	->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
                                		->where('mobile_no', $parentPatientId->mobile_no)
                                    	->whereNULL('deleted_at')
                                    	->orderBy('created_at','DESC')
                                    	->first();
                    //dd($masterPatientId);
			  	}
				if(sizeof($patientHasOrdination)>0)
				{
					// $getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - ordination.latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(ordination.latitude * pi()/180) * POWER(SIN(( ".$lng." - ordination.longitude) * pi()/180 / 2), 2) ))) as distance
					// 	from ordination
					// 	INNER JOIN patients_has_ordination
					// 	ON patients_has_ordination.fk_ordination_id=ordination.id
					// 	WHERE patients_has_ordination.fk_patient_id = ".$patient_id."
					// 	ORDER BY distance"));//LIMIT 10
					//dump($redius);

					//commented below query on 2-aug-24
					/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT
									  ordination.*, (
									    3959 * acos (
									      cos ( radians(".$lat.") )
									      * cos( radians( latitude ) )
									      * cos( radians( longitude ) - radians(".$lng.") )
									      + sin ( radians(".$lat.") )
									      * sin( radians( latitude ) )
									    )
									  ) AS distance
									FROM ordination
									INNER JOIN patients_has_ordination
									ON patients_has_ordination.fk_ordination_id=ordination.id
									WHERE patients_has_ordination.fk_patient_id = ".$masterPatientId->id."
									HAVING distance < ".$redius."
									ORDER BY distance
									"));//LIMIT 10
									*/

					//did changes on 2-aug-24
					$getOrdination=DB::connection('system')->select("SELECT
									  ordination.*, (
									    3959 * acos (
									      cos ( radians(".$lat.") )
									      * cos( radians( latitude ) )
									      * cos( radians( longitude ) - radians(".$lng.") )
									      + sin ( radians(".$lat.") )
									      * sin( radians( latitude ) )
									    )
									  ) AS distance
									FROM ordination
									INNER JOIN patients_has_ordination
									ON patients_has_ordination.fk_ordination_id=ordination.id
									WHERE patients_has_ordination.fk_patient_id = ".$masterPatientId->id."
									AND ordination.deleted_at IS NULL
									HAVING distance < ".$redius."
									ORDER BY distance
									");//LIMIT 10

				}
				else
				{
					//commented below query on 2-aug-24
					/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
						from ordination
						HAVING distance < ".$redius."
						ORDER BY distance"));//LIMIT 10
						*/

					//Roshani hidden the below query when upgrade the laravel version
					//did changes in below query on 2-aug-24
					// $getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
					// 	from ordination
					// 	WHERE ordination.deleted_at IS NULL
					// 	HAVING distance < ".$redius."
					// 	ORDER BY distance"));//LIMIT 10
					//Roshani hidden the above query when upgrade the laravel version

					//Roshani added the below query when upgrade the laravel version
					$getOrdination = DB::connection('system')
					    ->table('ordination')
					    ->selectRaw("ordination.*,
					        (3956 * 2 * ASIN(SQRT(POWER(SIN((? - latitude) * pi()/180 / 2), 2)
					        + COS(? * pi()/180) * COS(latitude * pi()/180)
					        * POWER(SIN((? - longitude) * pi()/180 / 2), 2)))) as distance", [$lat, $lat, $lng])
					    ->whereNull('ordination.deleted_at')
					    ->having('distance', '<', $redius)
					    ->orderBy('distance')
					    ->get();
					//Roshani added the above query when upgrade the laravel version

				}
			}
		}
		//echo "<pre>";print_r($getOrdination);exit;
		//dd($getOrdination);
		if(sizeof($getOrdination )>0)
	  	{
	  		$getOrdination = collect($getOrdination);

		  	$getOrdination = $getOrdination->map(function($item)
            {

            	//commented on 7-nov-25 for #395
            	/*if($item->name == 'puregyn')
            	{
            		$item->name = strtoupper($item->name);
            	}*/

            	$ordination_url = null;
            	if (!empty($item->id) )
                {
                	$logo_path = null;
                	//$logo_path = url('/storage/app'.$item->logo_path);
                	//$item->logo_path = $logo_path;
                	$item->logo_path = self::getFilePath($item->logo_path);
                	//dd($item->logo_path);
                	//get Host URL
                	$gethostnames = DB::connection('system')
                    				->table("domains")
                    				->where('ordination_id',$item->id)
                    				->whereNull('deleted_at')
                    				->first(['fqdn']);
                    if(!empty($gethostnames))
                    {
                    	$fqdn = "https://". $gethostnames->fqdn;
                    	// $ordination_url = $gethostnames->fqdn;
                    	$ordination_url = $fqdn;
                    	$item->ordination_url = $ordination_url;
                    }
                    else
                    {
                    	$item->ordination_url = null;
                    }
                    //End
                    //GET Specialist
                    $getSpecilistid = DB::connection('system')
                    				->table("ordination_has_specialist")
                    				->where('ordination_id',$item->id)
                    				->whereNull('deleted_at')
                    				->get(['specialist_id']);
                    $getSpecilistid = $getSpecilistid->map(function($spitem)
            		{
            			$specialistDetails = DB::connection('system')
                    						->table("specialist")
                    						->where('id',$spitem->specialist_id)
                    						// ->where('id',7)//When error ocurre name on null like this the replace the line with (->where('id',7))
                    						->whereNull('deleted_at')
                    						->first(['name']);
                    						// dd($specialistDetails);
                    	$spitem->specialist_name = $specialistDetails->name;
                    	return $spitem;
            		});
            		//dd($getSpecilistid);
                 	$item->specialist = $getSpecilistid;
                 	if(isset($item->distance))
                 	{
                 		$item->distance = number_format(($item->distance * 1.609344),2);
                 	}
                 	else
                 	{
                 		$item->distance = null;

                 	}

                 	//$getSpecilistid = [];
                    return $item;
                }
            });
		}
		//$a = array_push($getOrdination, $is_available)
		// $final_getOrdination['ordination_list'] = $getOrdination;
		// $final_getOrdination['error_msg'] = $error_message;
		return $getOrdination;


    }

    //Added by Swati 14-Jul-2022======================================
    //Roshani made changes in below code for pass contry #102 CR

    public function getLocationPatientHasOrdinationPuremed($code,$patient_id,$country)
    {
    	Log::info(" in getLocationPatientHasOrdinationPuremed....");

    	// Log::info("code....");
    	// Log::info($code);
    	// Log::info($patient_id);


    	$data = [];
    	$getOrdination = [];
    	$final_getOrdination = [];
    	$error_message = null;
    	$is_available = 1;
    	$patientHasOrdination = [];
    	$mapsApiKey    = Config('mapsApiKey');
    	//Roshani hidden the url below code #102 CR
    	//$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+AUSTRIA&sensor=false";
    	//Roshani hidden the url below code #102 CR

    	//Roshani added the url below code #102 CR
    	$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+".$country."&sensor=false";
    	//Roshani added the url below code #102 CR
		$data = file_get_contents($url);
		$redius = self::max_distance();

		 Log::info("url....");
		// Log::info($url);

		//Log::info($data);

		if($data)
		{
			Log::info("if data....");

			// convert into readable format
			$decode_data = json_decode($data);

			 Log::info("if decode_data....");
			// Log::info($decode_data);

			if(!empty($decode_data->results) && sizeof($decode_data->results)>0)
			{
				$lat = $decode_data->results[0]->geometry->location->lat;
		  		$lng = $decode_data->results[0]->geometry->location->lng;
		  		if(!empty($patient_id))
			  	{
			  	    Log::info("if not empty patient_id....");

			  	    //commented on 2-aug-24
					/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
							from ordination
							HAVING distance < ".$redius."
							ORDER BY distance"));//LIMIT 10
							*/

					//did changes on 2-aug-24
					$getOrdination=DB::connection('system')->select(("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
							from ordination
							WHERE ordination.deleted_at IS NULL
							AND ordination.status = 1   
							HAVING distance < ".$redius."
							ORDER BY distance"));//LIMIT 10	added sttus condition on 14-may-25	





					//echo "<pre>";print_r($getOrdination);exit;

					 Log::info("getOrdination.....");
					 // Log::info($getOrdination);

					if(!empty($getOrdination)){

						 Log::info("if not emtpy getOrdination.....");

						foreach($getOrdination as $ordination){
							$checkOrdination=DB::connection('system')
									->table("patients_has_ordination")
									->where('fk_patient_id',$patient_id)
									->where('fk_ordination_id',$ordination->id)
									->first();
							if(empty($checkOrdination))
							{
								$tmp['fk_patient_id']    = $patient_id;
								$tmp['fk_ordination_id'] = $ordination->id;
								$tmp['status']           = '1';
								DB::connection('system')->table("patients_has_ordination")->insert($tmp);
							}
						}
					}
			  		$patientHasOrdination = $this->PatientHasOrdinationsModel
						    	->where('fk_patient_id',$patient_id)
						    	->where('status','1')
						    	->get();
					$parentPatientId = $this->PatientsModel->find($patient_id);

					 Log::info("...parentPatientId.....");

					// Log::info($parentPatientId);

					if(sizeof($patientHasOrdination)>0)
					{
						 Log::info("if size of ....patientHasOrdination.");
						 //  dump($lat);
						 // dump($lng);
						 // dump($patient_id);
						 //  dump($redius);


						 //commented on 2-aug-24
						/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT
										ordination.*, (
											3959 * acos (
											cos ( radians(".$lat.") )
											* cos( radians( latitude ) )
											* cos( radians( longitude ) - radians(".$lng.") )
											+ sin ( radians(".$lat.") )
											* sin( radians( latitude ) )
											)
										) AS distance
										FROM ordination
										INNER JOIN patients_has_ordination
										ON patients_has_ordination.fk_ordination_id=ordination.id
										WHERE patients_has_ordination.fk_patient_id = ".$patient_id."
										HAVING distance < ".$redius."
										ORDER BY distance
										"));//LIMIT 10
										*/

						//did changes on 2-aug-24
						$getOrdination=DB::connection('system')->select("SELECT
										ordination.*, (
											3959 * acos (
											cos ( radians(".$lat.") )
											* cos( radians( latitude ) )
											* cos( radians( longitude ) - radians(".$lng.") )
											+ sin ( radians(".$lat.") )
											* sin( radians( latitude ) )
											)
										) AS distance
										FROM ordination
										INNER JOIN patients_has_ordination
										ON patients_has_ordination.fk_ordination_id=ordination.id
										WHERE patients_has_ordination.fk_patient_id = ".$patient_id."
										AND ordination.deleted_at IS NULL
										AND ordination.status = 1
										HAVING distance < ".$redius."
										ORDER BY distance

										");//LIMIT 10 added status condition on 14-may-25					


						//dump($getOrdination); 
						
					}	
					else
					{
						 Log::info("else of ....patientHasOrdination.");

						 //commented on 2-aug-24
						/*$getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
							from ordination
							HAVING distance < ".$redius."
							ORDER BY distance"));//LIMIT 10
							*/

					    //did changes on 2-aug-24
						$getOrdination=DB::connection('system')->select("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
							from ordination
							WHERE ordination.deleted_at IS NULL
							AND ordination.status = 1
							HAVING distance < ".$redius."

							ORDER BY distance");//LIMIT 10	added status condition on 14-may-25	

					}
			  	}
			}
		}
		if(sizeof($getOrdination )>0)
	  	{
	  		$getOrdination = collect($getOrdination);

	  		 Log::info("if ....getOrdination.");
	  		// Log::info($getOrdination);

		  	$getOrdination = $getOrdination->map(function($item)
            {
            	//commented on 7-nov-25 for #395
            	/*if($item->name == 'puregyn')
            	{
            		$item->name = strtoupper($item->name);
            	}*/ 

            	$ordination_url = null;
            	if (!empty($item->id) )
                {
                	$logo_path = null;
                	//$logo_path = url('/storage/app'.$item->logo_path);
                	//$item->logo_path = $logo_path;
                	$item->logo_path = self::getFilePath($item->logo_path);
                	//dd($item->logo_path);
                	//get Host URL
                	$gethostnames = DB::connection('system')
                    				->table("domains")
                    				->where('ordination_id',$item->id)
                    				->whereNull('deleted_at')
                    				->first(['fqdn']);
                    if(!empty($gethostnames))
                    {
                    	$fqdn = "https://". $gethostnames->fqdn;
                    	// $ordination_url = $gethostnames->fqdn;
                    	$ordination_url = $fqdn;
                    	$item->ordination_url = $ordination_url;
                    }
                    else
                    {
                    	$item->ordination_url = null;
                    }
                    //End
                    //GET Specialist
                    $getSpecilistid = DB::connection('system')
                    				->table("ordination_has_specialist")
                    				->where('ordination_id',$item->id)
                    				->whereNull('deleted_at')
                    				->get(['specialist_id']);
                    //dd($getSpecilist);
                    $getSpecilistid = $getSpecilistid->map(function($spitem)
            		{
            			//dd($spitem->specialist_id);
            			$specialistDetails = DB::connection('system')
                    						->table("specialist")
                    						->where('id',$spitem->specialist_id)
                    						->whereNull('deleted_at')
                    						->first(['name']);
                    	$spitem->specialist_name = $specialistDetails->name;
                    	return $spitem;
            		});
            		//dd($getSpecilistid);
                 	$item->specialist = $getSpecilistid;
                 	if(isset($item->distance))
                 	{
                 		$item->distance = number_format(($item->distance * 1.609344),2);
                 	}
                 	else
                 	{
                 		$item->distance = null;
                 	}
                    return $item;
                }
            });
		}
		return $getOrdination;
    }

    public function _updateOtp($collection)
    {
        if(!empty($collection)){

        	if($collection->id==23662 || $collection->id==32409){
        		$otp_code = 1234; //Ios user 23662 testing and for android user 32409
        	}else{
				$otp_code = rand(1000, 9999);
        	}

	        //update otp for the patient and send sms to the patient
	        $password  = Hash::make($otp_code);

	        $updateQry = DB::table('patients')
	                        ->where('id', $collection->id)
	                        ->update([
	                                    'login_otp' => $otp_code,
	                                    // 'password' => $password,
	                                    // 'str_password' => $otp_code,
	                                    'otp_created_at' => date('Y-m-d H:i:s')
	                                ]);
			$country_code = $collection->country_code;
			/*if($country_code==0){
                $country_code = str_replace("0", "91",$collection->country_code); //for testing indian mobile
            }else{
                $country_code = str_replace("00", "",$collection->country_code);
            }*/
            if(!empty($country_code)){
                $country_code = str_replace("00", "",$collection->country_code);
            }elseif(empty($country_code) || $country_code=='0'){
                $country_code = '43'; //Austria country code
            }

            $country_code = str_replace("+", "",$country_code);

	        $phone   = $country_code."".str_replace("-", "",$collection->mobile_no);
	       // $message = 'Hallo '.$collection->first_name.' , Ihr Otp:'.$otp_code.' ist der Bestätigungscode für Ihre Registrierung, der 5 Minuten gültig ist ';
	        $message = 'Hallo '.$collection->salutation.'.'.$collection->family_name.', lhr Login-Code für die PUREGYN-App lautet '.$otp_code.'. Er ist 5 Minuten gültig.';
	        $collection->login_otp = $otp_code;
	        $collection->message = $message;
	        // dd($phone,$message);
	       // $message .= "test message from puregyn api...please ignore.";
	        self::_sendSms($phone,$message);

        }

        return $collection;
    }

    public function resendOtp(Request $request){


    	$errors = [];
		$data = [];
		$message = __('api.AUTH_FAILED_OTP');
		$status = false;
		$validator = Validator::make($request->all(), [
                'patient_id' => 'required',
	            ],
				[
				  'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
				]);
		if($validator->fails()) {
		  $errors = $validator->errors();
		}else{
			$data = [];
			$status = true;

			$collection = $this->BaseModel->find($request->patient_id);

			if(!empty($collection)){

				$collection = $this->_updateOtp($collection);

				$message = __('api.AUTH_USER_RESEND_OTP_SUCCESS');
				/*$data = [
						  'patient'=>$collection
						];*/
				$data[] = $collection->only(['id','first_name','family_name','email','mobile_no','birth_date','login_otp']);

				self::_createLog('ResendOtp',$errors,'info');
				$this->ActivityLogModel->addApiLog('Resend Otp','Resend otp for login','Create',null,$data);
			}else{

				/*$data = [
						  'patient'=>$collection
						];*/
				$data[] = [];

			}
		}
	    return self::_sendResult($message,$data,$errors,$status);
    }
   /************ Roshani made chenges in below api for 146 ****************/

    public function logout(Request $request)
    {
    	$status = false;
	    $errors = [] ;

    	$userExist = auth()->guard('api')->user();
    	if(isset($userExist) && !empty($userExist))
    	{
    		$collection = auth()->guard('api')->user()->only(['id','first_name','family_name','email','mobile_no','birth_date','api_access_token']);
	        $updateQry = DB::table('patients')
	                                ->where('id', $collection['id'])
	                                ->update([
	                                			'api_access_token' => '',
	                                		]);
	        if(!empty($request->device_id)){
	        	$this->PatientHasDeviceModel
	        		->where('device_id',$request->device_id)
	        		->orwhere('device_type',$request->device_type)
	        		->delete();
	        }

	        $message = __('api.AUTH_LOGOUT');
	        $collection['api_access_token'] = '';
	        $data[] = $collection;
	        $status = true;
	        $errors = [] ;

	        self::_createLog('logout',$collection['id'],'info');
	        $this->ActivityLogModel->addApiLog('Logout','Logout patient','Create',null,$data);

	        auth()->guard('api')->logout();
    	}
        else
        {
        	$message = __('api.USER_NOT_EXIST');
	        $data = [] ;
    		$status = false;
	        $errors = [] ;
        }

        return self::_sendResult($message,$data,$errors,$status);


      /* $errors = [];
		$data = [];
		$message = __('api.AUTH_FAIL_LOGOUT');
		$status = false;

		$validator = Validator::make($request->all(), [
                	'patient_id' => 'required',
	            ],
				[
				  	'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{
			$data = [];

			$collection = $this->BaseModel->find($request->patient_id);

			if(!empty($collection)){

				$collection->api_access_token = '';

				if($collection->save()){
					$message = __('api.AUTH_LOGOUT');
					$data[] = $collection->only(['id','first_name','family_name','email','mobile_no','birth_date','login_otp']);

					auth()->guard('api')->logout();
				}
			}*/
       /*if($user) {
            $user->api_token = null;
            if(isset($user->v_device_token) && $user->v_device_token != '') {
                $user->v_device_token = '';
            }
            $user->save();
        }*/
    }
   /************ Roshani made chenges in above api for 146 ****************/

    public function refreshToken(Request $request)
    {
        $token = auth()->guard('api')->refresh();
        $errors = [];
        $status = true;
		$message = "Refresh Token";
		$data = [
		  'access_token' => $token,
		  'token_type'   => 'bearer',
		  'expires_in'   => auth('api')->factory()->getTTL() * 60
		];

		self::_createLog('refreshToken',$data,'info');
		$this->ActivityLogModel->addApiLog('Refresh Token','Refresh token after expired','create',null,$data);

		return self::_sendResult($message,$data,$errors,$status);
    }
    /*---------------------------------
	|   Master Data(Patient data)
	------------------------------------------*/

	public function getMasterData(Request $request)
    {
		$errors = [];
		$data = [];
		$message = __('api.INVALID_PATIENT_ID');
		$status = false;

		$patientId   = $request->patient_id;

		$validator = Validator::make($request->all(), [
               		'patient_id' => 'required',
	            ], [
					'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{

			try {
					$status = true;
					$collection = $this->BaseModel->find($patientId);

	                if($collection){
	                	$log_id = $collection->id;
						$collection = $collection->only(['first_name','family_name','email','country_code','mobile_no','birth_date','age','road','street_no','postal_code','place','gender','country']);//Roshani Added country For CR #102
	                	$message = __('api.PATIENT_GET_SUCCESS');
	                	$data[] = $collection;
	                	// dd($collection);
		          		self::_createLog('getMasterData',$log_id,'info');
		          		// $this->ActivityLogModel->addApiLog('Get Master Data','Get master data','Get',null,$data);
	                 } else{
	                 	$message = __('api.PATIENT_GET_FAIL');
	                 	self::_createLog('getMasterData',$message,'error');
	                 	// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
	                 }
                }
	        	catch(\Exception $e) {

	            	$message = __('api.ERR_SOMETHING_WRONG');
	            	$errors[] = [
			              "error" => $e->getMessage(),
			          ];
	                self::_createLog('getMasterData',$errors,'error');
	                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

	        	}
			}

	    return self::_sendResult($message,$data,$errors,$status);

    }

	public function editMasterData(Request $request)
    {
		$errors = [];
		$data = [];
		$message = __('api.INVALID_PATIENT_ID');
		$status = false;
		$patientId   = $request->patient_id;
		$request_mobile_no  = str_replace(" ", "", $request->mobile_no);
		$inputdata = $request->all();
		$inputdata['mobile_no'] = ltrim($request_mobile_no,'0');
		// dd($request->age);
		$validator = Validator::make($inputdata, [
					'patient_id' => 'required',
					'first_name' => 'required',
					'family_name'  => 'required',
					// 'email' 	 => 'required|unique:patients,email,'.$patientId,
					// 'email' => "required|unique:patients,email,{$patientId},id,deleted_at,NULL",
					'email' => "required",
					'country_code' => 'required',
					// 'mobile_no'  => 'required|unique:patients,mobile_no,'.$patientId.',id,deleted_at,NULL',
					'mobile_no'  => 'required',
					'birth_date' => 'required',
					'age' => 'required',
	            ], [
					'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
					'first_name.required'	=> __('api.PATIENT_FIRST_NAME_REQ'),
					'family_name.required'  => __('api.PATIENT_FAMILY_NAME_REQ'),
					'email.required' 		=> __('api.PATIENT_EMAIL_REQ'),
					// 'email.unique' 		=> __('api.PATIENT_EMAIL_UNIQUE'),
					'country_code' 		=> __('api.AUTH_COUNTRY_CODE_REQ'),
					'mobile_no.required'  => __('api.PATIENT_MOBILE_NO_REQ'),
					'mobile_no.unique'  	=> __('api.PATIENT_MOBILE_NO_UNIQUE'),
					'birth_date.required' => __('api.PATIENT_BIRTH_DATE_REQ'),
					'age.required' => __('api.PATIENT_AGE_REQ'),

				]);
		if($validator->fails())
		{
		  $errors[] = $validator->errors();
		}
		else {
			try
			{
					//Roshani Added For CR #102
						$country = $request->country;
			   	 		$postalCode = $request->postal_code;
						// Postal Code Validation for Germany
						if(!empty($postalCode) && !empty($country))
						{
							if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
					        $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY');
					        return self::_sendResult($message, $data, ['postal_code' => $message], $status);
						    }

						    // Postal Code Validation for Austria and Switzerland
						    if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
						        $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2');
						        return self::_sendResult($message, $data, ['postal_code' => $message], $status);
						    }
						}
					//Roshani Added For CR #102
				// $status = true;
				// Check validation for moblie no
				$collection = $this->BaseModel->where('mobile_no','0'.$inputdata['mobile_no'])
											->where('id','!=',$patientId)
											->whereNull('deleted_at')
											->first();
				if(!empty($collection))
				{
					$errors[] = [
		              	"mobile_no" => __('api.PATIENT_MOBILE_NO_UNIQUE'),
		          	];
				}
				else {
					$status = true;
					$collection     = $this->BaseModel->find($patientId);
					$oldData = [];
					$oldData['first_name'] = $collection->first_name;
					$oldData['family_name'] = $collection->family_name;
					$oldData['email'] = $collection->email;
					$oldData['country_code'] = $collection->country_code;
					$oldData['mobile_no'] = $collection->mobile_no;
					$oldData['birth_date'] = date('Y-m-d', strtotime($collection->birth_date));
					$oldData['age'] = $collection->age;
					$oldData['road'] 		= $collection->road;
					$oldData['street_no'] 		= $collection->street_no;
					$oldData['postal_code'] = $collection->postal_code;
					$oldData['place'] 		= $collection->place;
					$street_no = $request->street_no;
					$road = $request->road!='' ? $request->road : $collection->road;
					// dd($oldData);
	                if($collection)
	                {
						$this->BaseModel->where('id',$collection->id)
										->update([
			                    			'first_name' 	=>self::string_operation($request->first_name),
			                    			'family_name'  	=>self::string_operation($request->family_name),
			                    			'email' 	 	=> $request->email,
			                    			'country_code' 	=> $request->country_code,
			                    			'mobile_no' 	=> str_replace("-", "", ltrim($request->mobile_no,0)),
			                    			'birth_date' 	=> date('Y-m-d', strtotime($request->birth_date)),
			                    			'age' 			=> $request->age,
			                    			'road' 			=>self::string_operation($request->road),
			                    			'street_no' 	=> $street_no,
			                    			'postal_code' 	=> $request->postal_code,
			                    			'place' 		=>self::string_operation($request->place),
			                    			'update_ganydb' => '1',
			                    			'patient_status_flag' => '0',
			                    			'new_flag' => '1',
		                    			]);
						$new_data = $this->BaseModel->find($collection->id);
						$ordination_patient_update = self::_updatePatientOrdination($new_data,$collection);
						//Log::info('edit master data(_updatePatientOrdination) line number: 1651 . patient Name :' .$new_data->first_name.' '.$new_data->family_name);
						//$ordination_patient_update = self::_oldPatient($collection);
						if(!empty($oldData) && $oldData['birth_date'] != date('Y-m-d',strtotime($request->birth_date)))
			            {
			            	// $this->_ageReminderOnUpdateAge($collection->id);//commented on 28-Sep-23
			               	// $this->_ageReminderAppoitment($collection->id);
			            }
						$ordination_patient_update = self::_oldPatient($collection);
						//Log::info('edit master data(_oldPatient) line number: 1660 . patient Name :' .$collection->first_name.' '.$collection->family_name);
						$log_id = $collection->id;
						$collection = $collection->only(['first_name','family_name','email','country_code','mobile_no','birth_date','age','road','street_no','postal_code','place']);
	                	$message = __('api.PATIENT_UPDATE_SUCCESS');
		          		$data[] = $collection;
		          		self::_createLog('editMasterData',$log_id,'info');
		          		$this->ActivityLogModel->addApiLog('Edit Master Data','Edit master data','Update',$oldData,$data[0]);
	                }
	                else {
	                 	$message = __('api.PATIENT_UPDATE_FAIL');
		          		self::_createLog('editMasterData',$message,'error');
		          		// $this->ActivityLogModel->addApiLog('editMasterData','send otp for login','Get');
	                }
	            }
            }
        	catch(\Exception $e)
        	{
            	$message = __('api.ERR_SOMETHING_WRONG');
            	$errors[] = [
		            	"error" => $e->getMessage(),
		           ];
          		self::_createLog('editMasterData',$errors,'error');
          		// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        	}
		}
	    return self::_sendResult($message,$data,$errors,$status);
    }

    public function editMasterDataNew(Request $request)
    {
    	Log::info("in editMasterDataNew");
    	Log::info($request->all());


		$errors = [];
		$data = [];
		$message = __('api.INVALID_PATIENT_ID');
		$status = false;
		$patientId   = $request->patient_id;
		$request_mobile_no  = str_replace(" ", "", $request->mobile_no);
		$inputdata = $request->all();
		$inputdata['mobile_no'] = ltrim($request_mobile_no,'0');
		// dd($request->age);
		$validator = Validator::make($inputdata, [
					'patient_id' => 'required',
					'first_name' => 'required',
					'family_name'  => 'required',
					// 'email' 	 => 'required|unique:patients,email,'.$patientId,
					// 'email' => "required|unique:patients,email,{$patientId},id,deleted_at,NULL",
					'email' => "required|email",
					// 'country_code' => 'required',
					// 'mobile_no'  => 'required|unique:patients,mobile_no,'.$patientId.',id,deleted_at,NULL',
					// 'mobile_no'  => 'required',
					'country_code' => [
										'required',
										'regex:~^(\+[1-9][0-9]*|00[1-9][0-9]*)$~',
									],
					'mobile_no' 	=> 'required|regex:/^(?!0{2})0?[0-9]+$/|numeric',
					'birth_date' => 'required',
					'age' => 'required',
					// 'road' => 'required',
					// 'place' => 'required',
					// 'street_no' => 'required',
					'gender'  =>  'required',
					'country' => 'required|in:Austria,Germany,Switzerland', //Roshani Added For CR #102

	            ], [
					'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
					'first_name.required'	=> __('api.PATIENT_FIRST_NAME_REQ'),
					'family_name.required'  => __('api.PATIENT_FAMILY_NAME_REQ'),
					'email.required' 		=> __('api.PATIENT_EMAIL_REQ'),
					// 'email.unique' 		=> __('api.PATIENT_EMAIL_UNIQUE'),
					'mobile_no.required'  => __('api.PATIENT_MOBILE_NO_REQ'),
					'country_code.regex' => __('api.ERR_COUNTRY_CODE_WRONG'),
					'country_code.required' => __('api.AUTH_COUNTRY_CODE_REQ'),
					'mobile_no.regex'       =>  __('api.ERR_MOBILE_NO_FORMAT'),
					'mobile_no.unique'  	=> __('api.PATIENT_MOBILE_NO_UNIQUE'),
					'birth_date.required' => __('api.PATIENT_BIRTH_DATE_REQ'),
					'age.required' => __('api.PATIENT_AGE_REQ'),
					// 'road.required' => __('api.PATIENT_ROAD_REQ'),
					// 'place.required' => __('api.PATIENT_PLACE_REQ'),
					// 'street_no.required' => __('api.PATIENT_STREET_NO_REQ'),
					'gender.required'                =>  __('front.ERR_PATIENT_GENDER_REQUIRED'),
                	'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
                	'country.required'   	=> __('api.ERR_COUNTRY_REQUIRED'),//Roshani Added For CR #102
                    'country.in'   	=> __('api.ERR_COUNTRY_IN'),//Roshani Added For CR #102
				]);
		if($validator->fails())
		{
		  $errors[] = $validator->errors();
		}
		else {
			try
			{
				//Roshani Added For CR #102
						$country = $request->country;
			   	 		$postalCode = $request->postal_code;
						// Postal Code Validation for Germany
						if(!empty($postalCode) && !empty($country))
						{
							if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
					        $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY');
					        return self::_sendResult($message, $data, ['postal_code' => $message], $status);
						    }

						    // Postal Code Validation for Austria and Switzerland
						    if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
						        $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2');
						        return self::_sendResult($message, $data, ['postal_code' => $message], $status);
						    }
						}
					//Roshani Added For CR #102

				//below if condition added on 15-dec-23 for patient duplication
				$checkedPatientExist = $this->BaseModel
                                ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date)))
                                ->where('mobile_no',$inputdata['mobile_no'])
                                ->where('id','!=',$patientId)
                                ->whereNULL('deleted_at')
                                ->count();

                //below if condition commented on 15-dec-23
                if(!empty($checkedPatientExist))
				{
					$errors[] = [
			              	"error" => __('api.PATIENT_MOB_DOB_UNIQUE'),
			          	];
			    }
			    else
			    {

					// $status = true;
					// Check validation for moblie no
					$collection = $this->BaseModel->where('mobile_no','0'.$inputdata['mobile_no'])
												->where('id','!=',$patientId)
												->whereNull('deleted_at')
												->first();
					/*if(!empty($collection))
					{
						$errors[] = [
			              	"mobile_no" => __('api.PATIENT_MOBILE_NO_UNIQUE'),
			          	];
					}
					else
					{*/
						// dd("hi");
						$status = true;
						$collection     = $this->BaseModel->find($patientId);
						$oldData = [];
						$oldData['first_name'] = $collection->first_name;
						$oldData['family_name'] = $collection->family_name;
						$oldData['email'] = $collection->email;
						$oldData['country_code'] = $collection->country_code;
						$oldData['mobile_no'] = $collection->mobile_no;
						$oldData['birth_date'] = date('Y-m-d', strtotime($collection->birth_date));
						$oldData['age'] = $collection->age;
						$oldData['road'] 		= $collection->road;
						$oldData['street_no'] 		= $collection->street_no;
						$oldData['postal_code'] = $collection->postal_code;
						$oldData['place'] 		= $collection->place;
						$oldData['gender'] 		= $collection->gender;
						$oldData['country'] 		= $collection->country;

						$street_no = $request->street_no;
						$road = $request->road!='' ? $request->road : $collection->road;
						// dd($oldData);
		                if($collection)
		                {
							$this->BaseModel->where('id',$collection->id)
											->update([
				                    			'first_name' 	=>self::string_operation($request->first_name),
				                    			'family_name'  	=>self::string_operation($request->family_name),
				                    			'email' 	 	=> $request->email,
				                    			'country_code' 	=> $request->country_code,
				                    			'mobile_no' 	=> str_replace("-", "", ltrim($request->mobile_no,0)),
				                    			'birth_date' 	=> date('Y-m-d', strtotime($request->birth_date)),
				                    			'age' 			=> $request->age,
				                    			'road' 			=>self::string_operation($request->road),
				                    			'street_no' 	=> $street_no,
				                    			'postal_code' 	=> $request->postal_code,
				                    			'place' 		=>self::string_operation($request->place),
				                    			'update_ganydb' => '1',
				                    			'patient_status_flag' => '0',
				                    			'new_flag' => '1',
		                    					'gender' => $request->gender,
		                    					'country' 			=> $request->country,//Roshani Added For CR #102

			                    			]);
							$new_data = $this->BaseModel->find($collection->id);
							$ordination_patient_update = self::_updatePatientOrdination($new_data,$collection);
							//Log::info('edit master data(_updatePatientOrdination) line number: 1651 . patient Name :' .$new_data->first_name.' '.$new_data->family_name);
							//$ordination_patient_update = self::_oldPatient($collection);
							if(!empty($oldData) && $oldData['birth_date'] != date('Y-m-d',strtotime($request->birth_date)))
				            {
				               	// $this->_ageReminderAppoitment($collection->id);
				               	// $this->_ageReminderOnUpdateAge($collection->id);//commented on 28-Sep-23
				               	// log::info("editMasterDataNew-_ageReminderOnUpdateAge");
				            }
							$ordination_patient_update = self::_oldPatient($collection);
							//Log::info('edit master data(_oldPatient) line number: 1660 . patient Name :' .$collection->first_name.' '.$collection->family_name);
							$log_id = $collection->id;

							$collection = $new_data->only(['first_name','family_name','email','country_code','mobile_no','birth_date','age','road','street_no','postal_code','place','gender','country']);//,'country' Roshani Added country For CR #102
		                	$message = __('api.PATIENT_UPDATE_SUCCESS');
			          		$data[] = $collection;
			          		self::_createLog('editMasterData',$log_id,'info');
			          		$this->ActivityLogModel->addApiLog('Edit Master Data','Edit master data','Update',$oldData,$data[0]);
		                }
		                else {
		                 	$message = __('api.PATIENT_UPDATE_FAIL');
			          		self::_createLog('editMasterData',$message,'error');
			          		// $this->ActivityLogModel->addApiLog('editMasterData','send otp for login','Get');
		                }
		            //}//else //commented on 15-dec-23

			    }//else condition
            }
        	catch(\Exception $e)
        	{
            	$message = __('api.ERR_SOMETHING_WRONG');
            	$errors[] = [
		            	"error" => $e->getMessage(),
		           ];
          		self::_createLog('editMasterData',$errors,'error');
          		// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        	}
		}
	    return self::_sendResult($message,$data,$errors,$status);
    }

    public function editGanyPatientData(Request $request)
    {
    	Log::info("in editGanyPatientData");
    	Log::info($request->all());


		$errors = [];
		$data = [];
		$message = __('api.ERR_NOT_FOUND');
		$status = false;

		$msg_change = 0;

		$oldId   = $request->old_id;
		$patientId   = $request->patient_id;

		$request_mobile_no  = str_replace(" ", "", $request->mobile_no);

		$inputdata = $request->all();
		$inputdata['mobile_no'] = ltrim($request_mobile_no,'0');
		// dd($request->age);
		$validator = Validator::make($inputdata, [
			 	'old_id' => 'required',
			 	//'patient_id' => 'required',
                'first_name' => 'required',
                'family_name'  => 'required',
                'email' => 'required|email',
                // 'email' => "required|unique:patients,email,{$patientId},id,deleted_at,NULL",
                // 'country_code' => 'required',

                // 'mobile_no'  => 'required',
                'birth_date' => 'required',
                'age' => 'required',
                // 'road' => 'required',
                // 'place' => 'required',
                'postal_code' => 'required',
                // 'social_security_number'=>'required',
                'gender' => 'required',
                'country' => 'required|in:Austria,Germany,Switzerland', //Roshani Added For CR #102
				'country_code' => [
									'required',
									'regex:~^(\+[1-9][0-9]*|00[1-9][0-9]*)$~',
								],
				'mobile_no' 	=> 'required|regex:/^(?!0{2})0?[0-9]+$/|numeric',

	            ],
				[
				  'old_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
				  'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
				  'first_name.required'	=> __('api.PATIENT_FIRST_NAME_REQ'),
				  'family_name.required'  => __('api.PATIENT_FAMILY_NAME_REQ'),
				  'email.required' 		=> __('api.PATIENT_EMAIL_REQ'),
				  // 'email.unique' 		=> __('api.PATIENT_EMAIL_UNIQUE'),
				  'mobile_no.required'  => __('api.PATIENT_MOBILE_NO_REQ'),
				  'country_code.regex' => __('api.ERR_COUNTRY_CODE_WRONG'),
				  'country_code.required' => __('api.AUTH_COUNTRY_CODE_REQ'),
				  'mobile_no.regex'       =>  __('api.ERR_MOBILE_NO_FORMAT'),
				  // 'mobile_no.unique'  	=> __('api.PATIENT_MOBILE_NO_UNIQUE'),
				  'birth_date.required' => __('api.PATIENT_BIRTH_DATE_REQ'),
				  'age.required' => __('api.PATIENT_AGE_REQ'),
				  // 'road.required' => __('api.PATIENT_ROAD_REQ'),
				  // 'place.required' => __('api.PATIENT_PLACE_REQ'),
				  'postal_code.required' => __('api.PATIENT_POSTALCODE_REQ'),
				  // 'social_security_number.required' => __('api.PATIENT_SOCIAL_SECURITY_NUMBER_REQ'),
				  'gender.required'                =>  __('front.ERR_PATIENT_GENDER_REQUIRED'),
                  'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
                  'country.required'   	=> __('api.ERR_COUNTRY_REQUIRED'),//Roshani Added For CR rgb(117, 30, 204)
                  'country.in'   	=> __('api.ERR_COUNTRY_IN'),//Roshani Added For CR #102
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{
			// dd($request->all());
			try
			{
					//Roshani Added For CR #102
						$country = $request->country;
			   	 		$postalCode = $request->postal_code;
						// Postal Code Validation for Germany
						if(!empty($postalCode) && !empty($country))
						{
							if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
					        $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY');
					        return self::_sendResult($message, $data, ['postal_code' => $message], $status);
						    }

						    // Postal Code Validation for Austria and Switzerland
						    if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
						        $message = __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('api.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2');
						        return self::_sendResult($message, $data, ['postal_code' => $message], $status);
						    }
						}
					//Roshani Added For CR #102
				$collection = $this->BaseModel
							 ->where('id', $patientId)
							 ->first();
				//Roshani hide the below condition and add other condition on 14-mar-2023

				// $street_no = $request->street_no!='' ? $request->street_no : $collection->street_no;

				//Roshani hide the below condition and add other condition on 14-mar-2023

				// $street_no = $request->street_no!='' ? $request->street_no : $collection->street_no;

				//Roshani hide the below condition and add other condition
					// Check if $request is an object and has the property 'street_no'
					// $request_street_no = (is_object($request) && property_exists($request, 'street_no')) ? $request->street_no : '';
					$request_street_no = (isset($request->street_no) && !empty($request->street_no)) ? $request->street_no : '';
					// Check if $collection is an object and has the property 'street_no'
					// $collection_street_no = (is_object($collection) && property_exists($collection, 'street_no')) ? $collection->street_no : '';
					$collection_street_no = (isset($collection->street_no) && !empty($collection->street_no)) ? $collection->street_no : '';
					$street_no = ($request_street_no !== '' && !empty($request_street_no)) ? $request_street_no : $collection_street_no;
				//Roshani add the above condition and add other condition  on 14-mar-2023


				 // start commented first name and family name in below query and added in condition in 15-dec-23
				$checkedPatientExist = $this->BaseModel
                                // ->where(DB::raw('upper(family_name)'),'=',strtoupper($inputdata['family_name']))
                                // ->where(DB::raw('upper(first_name)'),'=',strtoupper($inputdata['first_name']))
                                ->whereDate('birth_date', date('Y-m-d',strtotime($inputdata['birth_date'])))
                                ->where('mobile_no', $inputdata['mobile_no'])
                                ->where('id','!=',$patientId)
                                ->whereNULL('deleted_at')
                                ->count();

                 if($checkedPatientExist>0)
                {
                	$message = __('api.PATIENT_MOB_DOB_UNIQUE');
                	$errors[] = [
		              	"error" => __('api.PATIENT_MOB_DOB_UNIQUE'),
		          	];
		          	return self::_sendResult($message,$data,$errors,$status);
                }//if

               	// end commented first name and family name in below query and added in condition in 2-nov-23

                if($checkedPatientExist > 0)
                {
                	$msg_change =1 ;

                	// start commented first name and family name in below query and added in condition in 15-dec-23
                	$checkedPatientExist = $this->BaseModel
                            // ->where(DB::raw('upper(family_name)'),'=',strtoupper($inputdata['family_name']))
                            // ->where(DB::raw('upper(first_name)'),'=',strtoupper($inputdata['first_name']))
                            ->whereDate('birth_date', date('Y-m-d',strtotime($inputdata['birth_date'])))
                            ->where('mobile_no', $inputdata['mobile_no'])
                            ->where('id','!=',$patientId)
                            ->whereNULL('deleted_at')
                            ->get();


		            foreach($checkedPatientExist as $key=>$value)
		            {
						$collection_duplicate = $this->BaseModel
											->where('id', $value->id)
											->first();

						// if(!empty($collection_duplicate) && ($collection_duplicate->count() > 0))
						// {
						// $duplicate_old_data = $this->BaseModel->find($collection_duplicate->id);

						// $gdpr = 0;
						// if(!empty($request->gdpr)){
						// $gdpr = $request->gdpr;
						// }

						// $this->BaseModel->where('id',$collection_duplicate->id)
						// ->update([
						// 'first_name' 	=> self::string_operation($request->first_name),
						// 'family_name'  	=> self::string_operation($request->family_name),
						// 'email' 	 	=> $request->email,
						// 'country_code' 	=> $request->country_code,
						// 'mobile_no' 	=> str_replace("-", "", ltrim($request->mobile_no,0)),
						// 'birth_date' 	=> date('Y-m-d', strtotime($request->birth_date)),
						// 'age' 			=> $request->age,
						// 'road' 			=> self::string_operation($request->road),
						// 'street_no' 			=> $street_no,
						// 'postal_code' 	=> $request->postal_code,
						// 'place' 		=> self::string_operation($request->place),
						// 'gdpr'			=> $gdpr,
						// 'insurance_number'			=> $request->social_security_number,
						// ]);

						// $new_data = $this->BaseModel->find($collection_duplicate->id);

						// $ordination_patient_update = self::_updatePatientOrdination($duplicate_old_data,$new_data);
						// }
		            }
		        }

					$status = true;

	                if(!empty($collection) && ($collection->count() > 0)){

	                	$gdpr = 0;
	                	if(!empty($request->gdpr)){
							$gdpr = $request->gdpr;
	                	}

	       			$old_collection = $this->BaseModel->find($collection->id);
					// $current_scanned_qrcode_appitment_id = $this->AppointmentModel
					// 					->where('patient_id',$collection->id)
					// 					->whereDate('start_date',date('Y-m-d'))
					// 					->Where(function($q) {
					// 						 $q->orwhere('appointment_status', 'Heute')
					//                          ->whereNull('appointment_status');
					//                     })
					// 					->pluck('id')
					// 					->first();
					$current_scanned_qrcode_appitment_id = $this->AppointmentModel
										->where('patient_id',$collection->id)
										->whereDate('start_date',date('Y-m-d'))
										->where('appointment_status','Heute')
										->pluck('id')
										->first();

					if(empty($current_scanned_qrcode_appitment_id))
					{
						$current_scanned_qrcode_appitment_id = $this->AppointmentModel
								->where('patient_id',$collection->id)
								->whereDate('start_date',date('Y-m-d'))
								->where('appointment_status','')
								->pluck('id')
								->first();
					}


				    $this->BaseModel->where('id',$collection->id)
										->update([
		                    			'first_name' 	=> self::string_operation($request->first_name),
		                    			'family_name'  	=> self::string_operation($request->family_name),
		                    			'email' 	 	=> $request->email,
		                    			'country_code' 	=> $request->country_code,
		                    			'mobile_no' 	=> str_replace("-", "", ltrim($request->mobile_no,0)),
		                    			'birth_date' 	=> date('Y-m-d', strtotime($request->birth_date)),
		                    			'age' 			=> $request->age,
		                    			'road' 			=> self::string_operation($request->road),
		                    			'street_no' 	=> self::string_operation($street_no),
		                    			'postal_code' 	=> $request->postal_code,
		                    			'place' 		=> self::string_operation($request->place),
		                    			'gdpr'			=> $gdpr,
		                    			//'update_ganydb' => '1',
		                    			'insurance_number'			=> $request->social_security_number,
		                    			'gender' 	 	=> $request->gender,
		                    			'country' 			=> $request->country,//Roshani Added For CR #102


	                    			]);

						$new_patient_flag = $this->BaseModel->find($collection->id);
						// ======================================================================
						////Added by Shyam 15-12-21
						// if($new_patient_flag->new_flag == '0')
						// {
						// 	$this->BaseModel->where('id',$collection->id)
						// 					->update([
						// 						'update_ganydb'=>'1',
						// 						'patient_status_flag'=>'0',
						// 						'new_flag'=>'1'
						// 					]);
						// }
						////Added by Shyam 15-12-21

						if($new_patient_flag->patient_status_flag =='0' && $new_patient_flag->new_flag == '0')
						{
							$this->BaseModel->where('id',$collection->id)
											->update([
												'new_flag'=>'1'
											]);
						}
						else
						// if(($new_patient_flag->patient_status_flag =='1' && $new_patient_flag->new_flag =='1')|| ($new_patient_flag->patient_status_flag =='0' && $new_patient_flag->new_flag == '1'))
						{
							$this->BaseModel->where('id',$collection->id)
										    ->update([
												'update_ganydb'=>'1',
												'patient_status_flag'=>'0',
												'new_flag'=>'1'
											]);
						}
						// --------------------------------------------------------------------
						$new_collection = $this->BaseModel->find($collection->id);

						$ordination_patient_update = self::_updatePatientOrdination($new_collection,$old_collection);

						//Log::info('edit Gany Patient Data(_updatePatientOrdination) line number: 2290 . patient Name :' .$new_collection->first_name.' '.$new_collection->family_name);
						//======================================================

						//======================================================

						if(!empty($old_collection) && $old_collection->birth_date != date('Y-m-d',strtotime($request->birth_date)))
			            {
			               // $this->_ageReminderAppoitment($collection->id);
			            	// $this->_ageReminderOnUpdateAge($collection->id);//commented on 28-Sep-23
			               	// log::info("editGanyPatientData-_ageReminderOnUpdateAge");
			            }

						$oldPatient = self::_oldPatient($old_collection);
						//Log::info('edit Gany Patient Data(_oldPatient) line number: 2298 . patient Name :' .$old_collection->first_name.' '.$old_collection->family_name);

						$updateApprec = $this->AppointmentModel->where('id',$current_scanned_qrcode_appitment_id)->update(['appointment_status'=>'Aktuell']);


						//dd($newcollection);
						$log_id = $collection->id;
						$collection = $collection->only(['first_name','family_name','email','country_code','mobile_no','birth_date','age','road','street_no','place','postal_code','gdpr','gender']);
	                	$new_collection = $new_collection->only(['first_name','family_name','email','country_code','mobile_no','birth_date','age','road','street_no','place','postal_code','gdpr','gender']);


						//start did changes on 17-oct-24 for stamdaten issue of profile
						$collection = $this->BaseModel
						       ->select('first_name','family_name','email','country_code','mobile_no','birth_date','age','road','street_no','place','postal_code','gdpr','gender','country')
							 ->where('id', $log_id)
							 ->first();
	                	//end did changes on 17-oct-24 for stamdaten issue of profile



	                	$message = __('api.PATIENT_UPDATE_SUCCESS');

						if($msg_change == 1)
						{
							$message = __('api.PATIENT_DUPLICATE_UPDATE_SUCCESS');
						}

		          		// $data[] = $collection;
		          		$data[] = $new_collection;
		          		self::_createLog('editGanyPatientData',$log_id,'info');
	                } else{
	                 	$message = __('api.PATIENT_UPDATE_FAIL');
		          		self::_createLog('editGanyPatientData',$message,'error');
	                }

            }
        	catch(\Exception $e) {

            	$message = __('api.ERR_SOMETHING_WRONG');
            	$errors[] = [
		              "error" => $e->getMessage(),
		          ];
          		self::_createLog('editMasterData',$errors,'error');
        	}
		}
	    return self::_sendResult($message,$data,$errors,$status);
    }

  //   public function editGanyPatientDatatest(Request $request)
  //   {
		// $errors = [];
		// $data = [];
		// $message = __('api.ERR_NOT_FOUND');
		// $status = false;

		// $msg_change = 0;

		// $oldId   = $request->old_id;
		// $patientId   = $request->patient_id;

		// $request_mobile_no  = str_replace(" ", "", $request->mobile_no);

		// $inputdata = $request->all();
		// $inputdata['mobile_no'] = ltrim($request_mobile_no,'0');
		// // dd($request->age);
		// $validator = Validator::make($inputdata, [
		// 	 	'old_id' => 'required',
		// 	 	//'patient_id' => 'required',
  //               'first_name' => 'required',
  //               'family_name'  => 'required',
  //               'email' => 'required',
  //               // 'email' => "required|unique:patients,email,{$patientId},id,deleted_at,NULL",
  //               'country_code' => 'required',

  //               'mobile_no'  => 'required',
  //               'birth_date' => 'required',
  //               'age' => 'required',
  //               'road' => 'required',
  //               'place' => 'required',
  //               'postal_code' => 'required',
  //               'social_security_number'=>'required',
	 //            ],
		// 		[
		// 		  'old_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
		// 		  'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
		// 		  'first_name.required'	=> __('api.PATIENT_FIRST_NAME_REQ'),
		// 		  'family_name.required'  => __('api.PATIENT_FAMILY_NAME_REQ'),
		// 		  'email.required' 		=> __('api.PATIENT_EMAIL_REQ'),
		// 		  // 'email.unique' 		=> __('api.PATIENT_EMAIL_UNIQUE'),
		// 		  'country_code' 		=> __('api.AUTH_COUNTRY_CODE_REQ'),
		// 		  'mobile_no.required'  => __('api.PATIENT_MOBILE_NO_REQ'),
		// 		  // 'mobile_no.unique'  	=> __('api.PATIENT_MOBILE_NO_UNIQUE'),
		// 		  'birth_date.required' => __('api.PATIENT_BIRTH_DATE_REQ'),
		// 		  'age.required' => __('api.PATIENT_AGE_REQ'),
		// 		  'road.required' => __('api.PATIENT_ROAD_REQ'),
		// 		  'place.required' => __('api.PATIENT_PLACE_REQ'),
		// 		  'postal_code.required' => __('api.PATIENT_POSTALCODE_REQ'),
		// 		  'social_security_number.required' => __('api.PATIENT_SOCIAL_SECURITY_NUMBER_REQ'),
		// 		]);
		// if($validator->fails()) {
		//   $errors[] = $validator->errors();
		// }else{

		// 	try
		// 	{

		// 		$collection = $this->BaseModel
		// 					 ->where('id', $patientId)
		// 					 ->first();
		// 		$street_no = $request->street_no!='' ? $request->street_no : $collection->street_no;

		// 		$checkedPatientExist = $this->BaseModel
  //                               ->where(DB::raw('upper(family_name)'),'=',strtoupper($inputdata['family_name']))
  //                               ->where(DB::raw('upper(first_name)'),'=',strtoupper($inputdata['first_name']))
  //                               ->whereDate('birth_date', date('Y-m-d',strtotime($inputdata['birth_date'])))
  //                               ->where('mobile_no', $inputdata['mobile_no'])
  //                               ->where('id','!=',$patientId)
  //                               ->whereNULL('deleted_at')
  //                               ->count();

  //               if($checkedPatientExist > 0)
  //               {
  //               	$msg_change =1 ;

  //               	$checkedPatientExist = $this->BaseModel
  //                           ->where(DB::raw('upper(family_name)'),'=',strtoupper($inputdata['family_name']))
  //                           ->where(DB::raw('upper(first_name)'),'=',strtoupper($inputdata['first_name']))
  //                           ->whereDate('birth_date', date('Y-m-d',strtotime($inputdata['birth_date'])))
  //                           ->where('mobile_no', $inputdata['mobile_no'])
  //                           ->where('id','!=',$patientId)
  //                           ->whereNULL('deleted_at')
  //                           ->get();

		//             foreach($checkedPatientExist as $key=>$value)
		//             {
		// 				$collection_duplicate = $this->BaseModel
		// 									->where('id', $value->id)
		// 									->first();

		//      //            if(!empty($collection_duplicate) && ($collection_duplicate->count() > 0))
		//      //            {
		//      //            	$duplicate_old_data = $this->BaseModel->find($collection_duplicate->id);

		//      //            	$gdpr = 0;
		//      //            	if(!empty($request->gdpr)){
		// 					// 	$gdpr = $request->gdpr;
		//      //            	}

		// 				 //    $this->BaseModel->where('id',$collection_duplicate->id)
		// 					// 				->update([
		// 	    //                 			'first_name' 	=> self::string_operation($request->first_name),
		// 	    //                 			'family_name'  	=> self::string_operation($request->family_name),
		// 	    //                 			'email' 	 	=> $request->email,
		// 	    //                 			'country_code' 	=> $request->country_code,
		// 	    //                 			'mobile_no' 	=> str_replace("-", "", ltrim($request->mobile_no,0)),
		// 	    //                 			'birth_date' 	=> date('Y-m-d', strtotime($request->birth_date)),
		// 	    //                 			'age' 			=> $request->age,
		// 	    //                 			'road' 			=> self::string_operation($request->road),
		// 	    //                 			'street_no' 			=> $street_no,
		// 	    //                 			'postal_code' 	=> $request->postal_code,
		// 	    //                 			'place' 		=> self::string_operation($request->place),
		// 	    //                 			'gdpr'			=> $gdpr,
		// 	    //                 			'insurance_number'			=> $request->social_security_number,
		//      //                			]);

		// 					// $new_data = $this->BaseModel->find($collection_duplicate->id);

		// 					// $ordination_patient_update = self::_updatePatientOrdination($duplicate_old_data,$new_data);
		//      //            }
		//             }
		//         }

		// 			$status = true;

	 //                if(!empty($collection) && ($collection->count() > 0)){

	 //                	$gdpr = 0;
	 //                	if(!empty($request->gdpr)){
		// 					$gdpr = $request->gdpr;
	 //                	}

	 //       			$old_collection = $this->BaseModel->find($collection->id);
		// 			// $current_scanned_qrcode_appitment_id = $this->AppointmentModel
		// 			// 					->where('patient_id',$collection->id)
		// 			// 					->whereDate('start_date',date('Y-m-d'))
		// 			// 					->Where(function($q) {
		// 			// 						 $q->orwhere('appointment_status', 'Heute')
		// 			//                          ->whereNull('appointment_status');
		// 			//                     })
		// 			// 					->pluck('id')
		// 			// 					->first();
		// 			$current_scanned_qrcode_appitment_id = $this->AppointmentModel
		// 								->where('patient_id',$collection->id)
		// 								->whereDate('start_date',date('Y-m-d'))
		// 								->where('appointment_status','Heute')
		// 								->pluck('id')
		// 								->first();

		// 			if(empty($current_scanned_qrcode_appitment_id))
		// 			{
		// 				$current_scanned_qrcode_appitment_id = $this->AppointmentModel
		// 						->where('patient_id',$collection->id)
		// 						->whereDate('start_date',date('Y-m-d'))
		// 						->where('appointment_status','')
		// 						->pluck('id')
		// 						->first();
		// 			}


		// 		    $this->BaseModel->where('id',$collection->id)
		// 								->update([
		//                     			'first_name' 	=> self::string_operation($request->first_name),
		//                     			'family_name'  	=> self::string_operation($request->family_name),
		//                     			'email' 	 	=> $request->email,
		//                     			'country_code' 	=> $request->country_code,
		//                     			'mobile_no' 	=> str_replace("-", "", ltrim($request->mobile_no,0)),
		//                     			'birth_date' 	=> date('Y-m-d', strtotime($request->birth_date)),
		//                     			'age' 			=> $request->age,
		//                     			'road' 			=> self::string_operation($request->road),
		//                     			'street_no' 	=> self::string_operation($street_no),
		//                     			'postal_code' 	=> $request->postal_code,
		//                     			'place' 		=> self::string_operation($request->place),
		//                     			'gdpr'			=> $gdpr,
		//                     			//'update_ganydb' => '1',
		//                     			'insurance_number'			=> $request->social_security_number,
	 //                    			]);

		// 				$new_patient_flag = $this->BaseModel->find($collection->id);
		// 				// ======================================================================
		// 				if($new_patient_flag->patient_status_flag =='0' && $new_patient_flag->new_flag == '0')
		// 				{
		// 					//dd("dgfhdg");
		// 					$this->BaseModel->where('id',$collection->id)
		// 								    ->update([
		//                     			      'new_flag'=>'1'
	 //                    			        ]);
		// 				}
		// 				else
		// 				// if(($new_patient_flag->patient_status_flag =='1' && $new_patient_flag->new_flag =='1')|| ($new_patient_flag->patient_status_flag =='0' && $new_patient_flag->new_flag == '1'))
		// 				{
		// 					//dd("dgfhdg");
		// 					$this->BaseModel->where('id',$collection->id)
		// 								    ->update([
		//                     			      'update_ganydb'=>'1',
		//                     			      'patient_status_flag'=>'0',
		//                     			      'new_flag'=>'1'
	 //                    			        ]);
		// 				}
		// 				// --------------------------------------------------------------------
		// 				$new_collection = $this->BaseModel->find($collection->id);

		// 				$ordination_patient_update = self::_updatePatientOrdination($new_collection,$old_collection);

		// 				//Log::info('edit Gany Patient Data(_updatePatientOrdination) line number: 2290 . patient Name :' .$new_collection->first_name.' '.$new_collection->family_name);
		// 				//======================================================

		// 				//======================================================

		// 				if(!empty($old_collection) && $old_collection->birth_date != date('Y-m-d',strtotime($request->birth_date)))
		// 	            {
		// 	               $this->_ageReminderAppoitment($collection->id);
		// 	            }

		// 				$oldPatient = self::_oldPatient($old_collection);
		// 				//Log::info('edit Gany Patient Data(_oldPatient) line number: 2298 . patient Name :' .$old_collection->first_name.' '.$old_collection->family_name);

		// 				$updateApprec = $this->AppointmentModel->where('id',$current_scanned_qrcode_appitment_id)->update(['appointment_status'=>'Aktuell']);


		// 				//dd($newcollection);
		// 				$log_id = $collection->id;
		// 				$collection = $collection->only(['first_name','family_name','email','country_code','mobile_no','birth_date','age','road','street_no','place','postal_code','gdpr']);



	 //                	$message = __('api.PATIENT_UPDATE_SUCCESS');

		// 				if($msg_change == 1)
		// 				{
		// 					$message = __('api.PATIENT_DUPLICATE_UPDATE_SUCCESS');
		// 				}

		//           		$data[] = $collection;

		//           		self::_createLog('editGanyPatientData',$log_id,'info');
	 //                } else{
	 //                 	$message = __('api.PATIENT_UPDATE_FAIL');
		//           		self::_createLog('editGanyPatientData',$message,'error');
	 //                }

  //           }
  //       	catch(\Exception $e) {

  //           	$message = __('api.ERR_SOMETHING_WRONG');
  //           	$errors[] = [
		//               "error" => $e->getMessage(),
		//           ];
  //         		self::_createLog('editMasterData',$errors,'error');
  //       	}
		// }
	 //    return self::_sendResult($message,$data,$errors,$status);
  //   }

    public function getFaq(Request $request)
    {
        $errors = [];
        $status = true;
		$message = "Faq page";

		//$url = "https://www.frauenarzt-1170-wien.at/faq/"; //commented on 28-nov-23

		$url = "https://puregyn.puremed.biz/faq"; //added on 28-nov-23

    	/*$dom = new Dom;
		$dom->loadFromUrl($url);
		// dd($html = $dom->outerHtml);
		// foreach($dom->find('a') as $a) {
		//     if($a->href) {
		//         $a->setAttribute('href', '#');
		//         echo $a->href . "n";
		//     }
		// }
        $html_content = $dom->getElementById('primary')->outerHtml;

		// echo "<pre>";
  		//print_r($html_content);

		$data[] = $html_content;*/

		$data[0]['url'] = $url;

		return self::_sendResult($message,$data,$errors,$status);
    }

	  	//   public function gdprDetails(Request $request)
	  	//   {
	  	//       $errors = [];
	  	//       $status = true;
		// $message = "GDPR detail page";

		// $url = url('/storage/app/GDPR/GDPR_details.php');
    	/*$dom = new Dom;
		$dom->loadFromUrl($url);
		// dd($html = $dom->outerHtml);
		// foreach($dom->find('a') as $a) {
		//     if($a->href) {
		//         $a->setAttribute('href', '#');
		//         echo $a->href . "n";
		//     }
		// }
        $html_content = $dom->getElementById('primary')->outerHtml;

		// echo "<pre>";
  		//print_r($html_content);

		$data[] = $html_content;*/

		// $data[0]['url'] = $url;

		// return self::_sendResult($message,$data,$errors,$status);
       //   }

    public function _storeOrUpdateLoginWithGoogle($collection, $request)
    {
        $otp_code = rand(1000, 9999);
        $password  = Hash::make($otp_code);

        $collection->first_name     = self::string_operation($request->first_name);
        $collection->family_name    = self::string_operation($request->family_name);
        $collection->mobile_no  	= str_replace("-", "", $request->mobile_no);
        $collection->email          = $request->email;
        $collection->login_type  	= $request->login_type;
        // $collection->str_password   = $otp_code;
        // $collection->password   	= $password;
        // $collection->status         = 1;//Active
        // dd($collection);
        //$collection->username       = $request->username;

        //Save data
        $collection->save();
        return $collection;
    }

	 //    public function verifyLoginWithGoogle(Request $request)
	 //    {
	 //        $errors		= [];
	 //        $data 		= [];
	 //        $message 	= __('api.ERR_NOT_FOUND');
	 //        $status 	= false;

 	//        $emailId  		= $request->email;

 	//        $inputdata  = $request->all();

 	//        try{
 	//        $validator  = Validator::make($inputdata,[
	// 			'email'		=> 'required|email',
	//             ],
	// 			[
	// 			  'email.required' 		=> __('api.PATIENT_EMAIL_REQ'),
 	//                ]);

 	//        if ($validator->fails())
    //        {
    //          $errors[] = $validator->errors();
    //        }else
    //        {
	//         $collection = collect([]);
	//         $collection = $this->BaseModel
	//                             ->where('email', '=', Input::get('email'))
	//                             ->first(['mobile_no']);
	// 		// dd($collection);

	// 	        if(!empty($collection) && ($collection->count() > 0)){

	// 	        	$status = true;
	//             	$message = __('api.AUTH_REGISTER_USER_SUCCESS');

    //             		$data[0]['id'] 			= $collection->id;
	// 	          	$data[0]['first_name'] 	= $collection->first_name;
	// 	          	$data[0]['family_name'] = $collection->family_name;
	// 	          	$data[0]['email'] 		= $collection->email;
	// 	          	$data[0]['mobile_no'] 	= $collection->mobile_no;
	// 	          	$data[0]['birth_date'] 	= $collection->birth_date;
	// 	          	$data[0]['age'] 		= $collection->age;
	// 				self::_createLog('verifyLoginWithGoogle',$data,'info');
	// 	        }else{

	//        			$message = __('api.ERR_SOMETHING_WRONG');
	//             	self::_createLog('verifyLoginWithGoogle',$data,'info');
	// 	            }
	//             }
	//         }
    //        catch(\Exception $e) {
    //            $message = __('api.ERR_SOMETHING_WRONG');
    //            $errors[] = [
    //                  "error" => $e->getMessage(),
    //              ];
    //             self::_createLog('loginWithGoogle',$errors,'error');
    //        }
	//     return self::_sendResult($message,$data,$errors,$status);
	// }


	public function loginWithGoogle(Request $request)
    {
        $errors		= [];
        $data 		= [];
        $message 	= __('api.ERR_NOT_FOUND');
        $status 	= false;

        $firstName  	= $request->first_name;
        $familyName  	= $request->family_name;
        $emailId  		= $request->email;
        // $MobileNo  		= $request->mobile_no;
        $loginType  	= $request->login_type;

        $inputdata  = $request->all();

        try{
        $validator  = Validator::make($inputdata,[
                'first_name' => 'required',
                'family_name'  => 'required',
                // 'mobile_no' 	=> 'required|numeric',
				'email'		=> 'required|email',
                'login_type' => 'required',
	            ],
				[
				  'first_name.required'	=> __('api.PATIENT_FIRST_NAME_REQ'),
				  'family_name.required'  => __('api.PATIENT_FAMILY_NAME_REQ'),
				  'email.required' 		=> __('api.PATIENT_EMAIL_REQ'),
				  // 'mobile_no.required'  => __('api.PATIENT_MOBILE_NO_REQ'),
				  // 'mobile_no.numeric' => __('api.AUTH_FORMAT_MOBILE_USER'),
				  'login_type' => __('api.PATIENT_LOGIN_TYPE_FIELD_REQUIRED'),
                ]);

        if ($validator->fails())
        {
          $errors[] = $validator->errors();
        }else
        {
	        $collection = collect([]);
	        $patientData = $this->BaseModel
	                            ->where('email', $emailId)
	                            ->first();

	                            // dd($patientData);
		        if(!empty($patientData) && ($patientData->count() > 0)){
		        	// dd('if');
		        	$patientCredentials['email']    = $patientData->email;
	     			$patientCredentials['password'] = $patientData->str_password;
						// print_r($patientCredentials);
						// var_dump( auth('api')->attempt($patientCredentials));
						// exit();
	     				 // dd();
			            if (! $token = auth('api')->attempt($patientCredentials)) {
			            	// dd($token);
					          	$errors = [
					              "error" => __('api.AUTH_SYSTEM_FAIL'),
					          	];
					          	$message = __('api.AUTH_SYSTEM_FAIL');

								self::_createLog('loginWithGoogle',$errors,'error');
								// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					      }else{
					      	// dd($token);
								$status  = true;
				                $message = __('api.AUTH_ALLREADY_REGISTERD_SUCCESS');
				                // return self::_sendResult($message,$status);
				                // $data  = $collection;

					          	// $data[] = $collection->only([
					          	// 	'first_name',
					          	// 	'family_name',
					          	// 	'email',
					          	// 	'mobile_no',
					          	// ]);
					          	$data[0]['id'] = $patientData->id;
					          	$data[0]['first_name'] = $patientData->first_name;
					          	$data[0]['family_name'] = $patientData->family_name;
					          	$data[0]['email'] = $patientData->email;
					          	$data[0]['mobile_no'] = $patientData->mobile_no;
					          	$data[0]['birth_date'] = $patientData->birth_date;
					          	$data[0]['api_access_token'] = "Bearer ".$token;
					          	$data[0]['login_type'] = $patientData->login_type;
					          	self::_createLog('loginWithGoogle',$patientData->id,'info');
					          	$this->ActivityLogModel->addApiLog('Login With Google','Login with google','Create',null,$data);
					      	}
		        }else{
		        	// dd('else');
		        	$collection     = new $this->BaseModel;
		            $collection     = self::_storeOrUpdateLoginWithGoogle($collection,$request);
		            // dd($collection);
					if ($collection)
		            {
		            	$patientCredentials['email']    = $collection->email;
		     			$patientCredentials['password'] = $collection->str_password;
							// print_r($patientCredentials);
							// var_dump( auth('api')->attempt($patientCredentials));
							// exit();
		     				 // dd();
			            if (! $token = auth('api')->attempt($patientCredentials)) {
			            	// dd($token);
					          	$errors = [
					              "error" => __('api.AUTH_SYSTEM_FAIL'),
					          	];
					          	$message = __('api.AUTH_SYSTEM_FAIL');

								self::_createLog('loginWithGoogle',$errors,'error');
								// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					      }else{
			            	$status = true;
			            	$message = __('api.AUTH_REGISTER_USER_SUCCESS');
			            	$log_id = $collection->id;
			            	$data[] = $collection->only([
			            			'id',
					          		'first_name',
					          		'family_name',
					          		'email',
					          		'mobile_no',
					          		'login_type',
					          	]);
					        $data[0]['api_access_token'] = "Bearer ".$token;

			            	// $result = Mail::to(config('constants.ADMINEMAIL'))->send(new PatientRegistrationMail($collection));
			            	// return self::_sendResult($message,$data,$status);

			            	self::_createLog('loginWithGoogle',$log_id,'info');
			            	$this->ActivityLogModel->addApiLog('Login With Google','Login with google','Create',null,$data);
			            }
		            }
		        }
			}
		}
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
             self::_createLog('loginWithGoogle',$errors,'error');
             // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
	    return self::_sendResult($message,$data,$errors,$status);
	}


	public function getGanyPatientData(Request $request)
    {
		$errors = [];
		$data = [];
		$message = __('api.ERR_NOT_FOUND');
		$status = false;

		$oldId   = $request->old_id;
		$tab_pin   = $request->tab_pin;

		$validator = Validator::make($request->all(), [
               		'old_id' => 'required',
               		'tab_pin' => 'required',
	            ],
				[
					'old_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					'tab_pin.required'	=> 'Tab-Pin ist erforderlich',
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{

			try {

					$valid_tab_pin = $this->SettingsModel
				                                ->where('setting_key','=','TAB_PIN')
				                                ->whereStatus(1)
				                                ->first(['setting_value']);
					if(!empty($valid_tab_pin)){

						if($valid_tab_pin->setting_value == $tab_pin){
							$status = true;
							$collection = $this->BaseModel
									  ->where('old_id', $oldId)
									  ->first();
							// dd($collection);
							$collection->social_security_number = $collection->insurance_number;

			                if(!empty($collection) && ($collection->count() > 0)){
			                	$log_id = $collection->id;
								$collection = $collection->only(['id','old_id','first_name','family_name','email','country_code','mobile_no','birth_date','age','road','street_no','place','postal_code','gdpr','social_security_number','gender','country']);
								$message = __('api.DATA_FOUND_SUCCESS');
				          		$data[] = $collection;

				          		self::_createLog('getGanyPatientData',$log_id,'info');
			                 } else{
			                 	$message = __('api.ERR_NOT_FOUND');
			                 	self::_createLog('getGanyPatientData',$message,'error');
			                 }
						}else{
							$message = 'Tab Pin ist ungültig';
			                self::_createLog('getGanyPatientData',$message,'error');
						}

					}else{

						$message = 'Der Tab-Pin ist im Admin-Bereich nicht festgelegt';
			            self::_createLog('getGanyPatientData',$message,'error');

					}



                }
	        	catch(\Exception $e) {

	            	$message = __('api.ERR_SOMETHING_WRONG');
	            	$errors[] = [
			              "error" => $e->getMessage(),
			          ];
	                self::_createLog('getGanyPatientData',$errors,'error');
	                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
	        	}
			}
	    return self::_sendResult($message,$data,$errors,$status);
    }


    public function getGanyPatientDataQRCode(Request $request)
    {
		$errors = [];
		$data = [];
		$message = __('api.ERR_NOT_FOUND');
		$status = false;

		$id   = $request->id;
		$tab_pin   = $request->tab_pin;

		$validator = Validator::make($request->all(), [
               		'id' => 'required',
               		'tab_pin' => 'required',
	            ],
				[
					'id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					'tab_pin.required'	=> 'Tab-Pin ist erforderlich',
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{

			try {

					$valid_tab_pin = $this->SettingsModel
				                                ->where('setting_key','=','TAB_PIN')
				                                ->whereStatus(1)
				                                ->first(['setting_value']);
					if(!empty($valid_tab_pin)){

						if($valid_tab_pin->setting_value == $tab_pin){
							$status = true;
							$collectionPatient = $this->BaseModel
									  ->where('id', $id)
									  ->first();
							if(!empty($collectionPatient) && isset($collectionPatient->insurance_number))
								$collectionPatient->social_security_number = $collectionPatient->insurance_number;
							// dd($collection);
							if(!empty($collectionPatient) && ($collectionPatient->count() > 0)){
			                	$log_id = $collectionPatient->id;
								$collection = $collectionPatient->only(['id','old_id','first_name','family_name','email','country_code','mobile_no','birth_date','age','road','street_no','place','postal_code','gdpr','social_security_number','gender','country']);
								//added by swati 10-Jan-23=======
								$getSettings = $this->SettingsModel->where('setting_key','GDPR_TEXT_LABEL')->first();
					            if(!empty($getSettings))
					            $collection['gdpr_text_label']=$getSettings->setting_value;
					            //==============================
			                	$message = __('api.DATA_FOUND_SUCCESS');
				          		$data[] = $collection;

				          		self::_createLog('getGanyPatientData',$log_id,'info');
			                 } else{
			                 	$status = false;
			                 	$message = __('api.ERR_NOT_FOUND');
			                 	self::_createLog('getGanyPatientData',$message,'error');
			                 }
						}else{
							$status = false;
							$message = 'Tab Pin ist ungültig';
			                self::_createLog('getGanyPatientData',$message,'error');
						}

					}else{
						$status = false;
						$message = 'Der Tab-Pin ist im Admin-Bereich nicht festgelegt';
			            self::_createLog('getGanyPatientData',$message,'error');

					}



                }
	        	catch(\Exception $e) {

	            	$message = __('api.ERR_SOMETHING_WRONG');
	            	$errors[] = [
			              "error" => $e->getMessage(),
			          ];
	                self::_createLog('getGanyPatientData',$errors,'error');
	                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
	        	}
			}
	    return self::_sendResult($message,$data,$errors,$status);
    }

    public function getPatientSignedDocuments(Request $request)
    {
		$errors = [];
		$data = [];
		$message = __('api.ERR_NOT_FOUND');
		$status = false;

		$old_id   = $request->old_id;
		// $tab_pin   = $request->tab_pin;
		// dd($old_id);
		$validator = Validator::make($request->all(), [
               		'old_id' => 'required',
               		// 'tab_pin' => 'required',
	            ],
				[
					'old_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					// 'tab_pin.required'	=> 'Tab-Pin ist erforderlich',
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{

			try {

					$status = true;
					$patient_data = $this->BaseModel
							  ->where('old_id', $old_id)
							  ->first();
							  // dd($patient_data);
	                if(!empty($patient_data) && ($patient_data->count() > 0)){
	                	$patient_id = $patient_data->id;

	                	$collections = collect([]);
		                $appointment_type_docs = collect([]);
		                $appointment_exams_docs = collect([]);

	                	$appointment_type_docs = $this->PatientHasDocumentsModel
			                						->with('hasAppointmentTypeDocument')
			                						->whereHas('hasAppointmentTypeDocument',function($q){
			                							$q->whereNotNull('patient_document');
			                							$q->where('patient_document','!=','');
			                						})
			                						->where('patient_id',$patient_data->id)
			                						->where('record_type',1)
			                						->where('doc_status','0')
			                						->get();

						$appointment_exams_docs = $this->PatientHasDocumentsModel
		                								->with('hasExamDocument')
				                						->whereHas('hasExamDocument',function($q){
				                							$q->whereNotNull('document_name');
				                							$q->where('document_name','!=','');
				                						})
				                						->where('doc_status','0')
		                                                ->get();

		                if((!empty($appointment_type_docs) && sizeof($appointment_type_docs) > 0)){

		                    $appointment_type_docs = $appointment_type_docs->map(function($item)
		                                {
		                                	//dd($item->hasAppointmentTypeDocument->toArray());

		                                	$doc_path = '';
		                                	$doc_name = '';
		                                	if(!empty($item->hasAppointmentTypeDocument) && sizeof($item->hasAppointmentTypeDocument)>0){

		                                		$new_patient_document_path = self::StorePath($item->hasAppointmentTypeDocument[0]->patient_document_path.'/');

			                                    // if (!empty($item->hasAppointmentTypeDocument[0]->patient_document_path) && is_file(storage_path().$item->hasAppointmentTypeDocument[0]->patient_document_path))
			                                    if (!empty($item->hasAppointmentTypeDocument[0]->patient_document_path))
			                                    {
			                                    	$doc_path = self::getFilePath($item->hasAppointmentTypeDocument[0]->patient_document_path);
			                                        //$doc_path = url('/storage'.$item->hasAppointmentTypeDocument[0]->patient_document_path);
			                                        $doc_name = $item->hasAppointmentTypeDocument[0]->patient_document;
			                                    }

		                                	}
		                                    //$item->record_type = 'appointment_types';
		                                    $item->document_path = $doc_path;
		                                    $item->doc_name = $doc_name;
		                                    return $item;
		                                });

		                }

		                if((!empty($appointment_exams_docs) && sizeof($appointment_exams_docs) > 0)){

		                    $appointment_exams_docs = $appointment_exams_docs->map(function($item)
		                                {
		                                    /*$item->record_type = 'exams';
		                                    $item->exam_app_type_id	= $item->id;

		                                    $doc_path = '';
		                                    if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
		                                    {
		                                        $doc_path = url('/storage'.$item->document_path);
		                                    }
		                                    $item->document_path = $doc_path;*/
		                                    $doc_path = '';
		                                	$doc_name = '';
		                                	if(!empty($item->hasExamDocument) && sizeof($item->hasExamDocument)>0){

		                                		$new_document_path = self::StorePath($item->hasExamDocument[0]->document_path.'/');

			                                    // if (!empty($item->hasExamDocument[0]->document_path) && is_file(storage_path().$item->hasExamDocument[0]->document_path))
			                                    if (!empty($item->hasExamDocument[0]->document_path))
			                                    {
			                                    	$doc_path = self::getFilePath($item->hasExamDocument[0]->document_path);
			                                        //$doc_path = url('/storage'.$item->hasExamDocument[0]->document_path);
			                                        $doc_name = $item->hasExamDocument[0]->document_name;
			                                    }

		                                	}
		                                    //$item->record_type = 'appointment_types';
		                                    $item->document_path = $doc_path;
		                                    $item->doc_name = $doc_name;
		                                    return $item;
		                                });


		                }

						$collections = $appointment_type_docs->merge($appointment_exams_docs);

						// $collection = $collection->only(['id','old_id','first_name','family_name','email','country_code','mobile_no','birth_date','age','road','place','postal_code','gdpr']);

						if(!empty($collections) && sizeof($collections)>0){
							$data[] = $collections;
		                	$message = __('api.DATA_FOUND_SUCCESS');
						}else{

			          		$data[] = $collections;
		                	$message = __('api.ERR_NOT_FOUND');
						}


		          		self::_createLog('getPatientSignedDocuments',$data,'info');
	                } else{
	                 	$message = __('api.ERR_NOT_FOUND');
	                 	self::_createLog('getPatientSignedDocuments',$message,'error');
	                }

                }
	        	catch(\Exception $e) {

	            	$message = __('api.ERR_SOMETHING_WRONG');
	            	$errors[] = [
			              "error" => $e->getMessage(),
			          ];
	                self::_createLog('getPatientSignedDocuments',$errors,'error');
	                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
	        	}
			}
	    return self::_sendResult($message,$data,$errors,$status);
    }

    public function getPatientSignedDocumentsByQRCode(Request $request)
    {
		$errors = [];
		$data = [];
		$message = __('api.ERR_NOT_FOUND');
		$status = false;

		$appoitment_id   = $request->appoitment_id;
		// $tab_pin   = $request->tab_pin;
		// dd($old_id);
		$validator = Validator::make($request->all(), [
               		'appoitment_id' => 'required',
               		// 'tab_pin' => 'required',
	            ],
				[
					'appoitment_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					// 'tab_pin.required'	=> 'Tab-Pin ist erforderlich',
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{

			try {

					$status = true;
					$patient_data = $this->AppointmentModel->where('id',$appoitment_id)->first(['patient_id']);

					$patient_data = $this->BaseModel
							  ->where('id', $patient_data->patient_id)
							  ->first();
							  // dd($patient_data);
	                if(!empty($patient_data) && ($patient_data->count() > 0)){
	                	$patient_id = $patient_data->id;

	                	$collections = collect([]);
		                $appointment_type_docs = collect([]);
		                $appointment_exams_docs = collect([]);

	                	$appointment_type_docs = $this->PatientHasDocumentsModel
			                						->with('hasAppointmentTypeDocument')
			                						->whereHas('hasAppointmentTypeDocument',function($q){
			                							$q->whereNotNull('patient_document');
			                							$q->where('patient_document','!=','');
			                						})
			                						->where('patient_id',$patient_data->id)
			                						->where('record_type',1)
			                						->where('doc_status','0')
			                						->get();

						$appointment_exams_docs = $this->PatientHasDocumentsModel
		                								->with('hasExamDocument')
				                						->whereHas('hasExamDocument',function($q){
				                							$q->whereNotNull('document_name');
				                							$q->where('document_name','!=','');
				                						})
				                						->where('doc_status','0')
		                                                ->get();

		                if((!empty($appointment_type_docs) && sizeof($appointment_type_docs) > 0)){

		                    $appointment_type_docs = $appointment_type_docs->map(function($item)
		                                {
		                                	//dd($item->hasAppointmentTypeDocument->toArray());

		                                	$doc_path = '';
		                                	$doc_name = '';
		                                	if(!empty($item->hasAppointmentTypeDocument) && sizeof($item->hasAppointmentTypeDocument)>0){

			                                    // if (!empty($item->hasAppointmentTypeDocument[0]->patient_document_path) && is_file(storage_path().$item->hasAppointmentTypeDocument[0]->patient_document_path))
			                                    $new_patient_document_path = self::StorePath($item->hasAppointmentTypeDocument[0]->patient_document_path.'/');
			                                    if (!empty($item->hasAppointmentTypeDocument[0]->patient_document_path))
			                                    {
			                                    	$doc_path = self::getFilePath($item->hasAppointmentTypeDocument[0]->patient_document_path);

			                                        //$doc_path = url('/storage'.$item->hasAppointmentTypeDocument[0]->patient_document_path);
			                                        $doc_name = $item->hasAppointmentTypeDocument[0]->patient_document;
			                                    }

		                                	}
		                                    //$item->record_type = 'appointment_types';
		                                    $item->document_path = $doc_path;
		                                    $item->doc_name = $doc_name;
		                                    return $item;
		                                });

		                }

		                if((!empty($appointment_exams_docs) && sizeof($appointment_exams_docs) > 0)){

		                    $appointment_exams_docs = $appointment_exams_docs->map(function($item)
		                                {
		                                    /*$item->record_type = 'exams';
		                                    $item->exam_app_type_id	= $item->id;

		                                    $doc_path = '';
		                                    if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
		                                    {
		                                        $doc_path = url('/storage'.$item->document_path);
		                                    }
		                                    $item->document_path = $doc_path;*/
		                                    $doc_path = '';
		                                	$doc_name = '';
		                                	if(!empty($item->hasExamDocument) && sizeof($item->hasExamDocument)>0){

		                                		$new_document_path = self::StorePath($item->hasExamDocument[0]->document_path.'/');

		                                		// if (!empty($item->hasExamDocument[0]->document_path) && is_file(storage_path().$item->hasExamDocument[0]->document_path))
			                                    if (!empty($item->hasExamDocument[0]->document_path))
			                                    {
			                                    	$doc_path = self::getFilePath($item->hasExamDocument[0]->document_path);
			                                        //$doc_path = url('/storage'.$item->hasExamDocument[0]->document_path);
			                                        $doc_name = $item->hasExamDocument[0]->document_name;
			                                    }

		                                	}
		                                    //$item->record_type = 'appointment_types';
		                                    $item->document_path = $doc_path;
		                                    $item->doc_name = $doc_name;
		                                    return $item;
		                                });

		                        // //$item->record_type = 'appointment_types';
		                        //             $item->document_path = $doc_path;
		                        //             $item->doc_name = $doc_name;
		                        //             return $item;
		                        //         });

		                }

		                if((!empty($appointment_exams_docs) && sizeof($appointment_exams_docs) > 0)){

		                    $appointment_exams_docs = $appointment_exams_docs->map(function($item)
		                                {
		                                    /*$item->record_type = 'exams';
		                                    $item->exam_app_type_id	= $item->id;

		                                    $doc_path = '';
		                                    if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
		                                    {
		                                        $doc_path = url('/storage'.$item->document_path);
		                                    }
		                                    $item->document_path = $doc_path;*/
		                                    $doc_path = '';
		                                	$doc_name = '';
		                                	if(!empty($item->hasExamDocument) && sizeof($item->hasExamDocument)>0){

		                                		$new_document_path = self::StorePath($item->hasExamDocument[0]->document_path.'/');

		                                		// if (!empty($item->hasExamDocument[0]->document_path) && is_file(storage_path().$item->hasExamDocument[0]->document_path))
			                                    if (!empty($item->hasExamDocument[0]->document_path))
			                                    {
			                                    	$doc_path = self::getFilePath($item->hasExamDocument[0]->document_path);
			                                        //$doc_path = url('/storage'.$item->hasExamDocument[0]->document_path);
			                                        $doc_name = $item->hasExamDocument[0]->document_name;
			                                    }

		                                	}
		                                    //$item->record_type = 'appointment_types';
		                                    $item->document_path = $doc_path;
		                                    $item->doc_name = $doc_name;
		                                    return $item;
		                                });


		                }

						$collections = $appointment_type_docs->merge($appointment_exams_docs);

						// $collection = $collection->only(['id','old_id','first_name','family_name','email','country_code','mobile_no','birth_date','age','road','place','postal_code','gdpr']);

						if(!empty($collections) && sizeof($collectionsmpty($appointment_exams_docs) && sizeof($appointment_exams_docs) > 0)){

		                    $appointment_exams_docs = $appointment_exams_docs->map(function($item)
		                                {
		                                    /*$item->record_type = 'exams';
		                                    $item->exam_app_type_id	= $item->id;

		                                    $doc_path = '';
		                                    if (!empty($item->document_path) && is_file(storage_path().$item->document_path))
		                                    {
		                                        $doc_path = url('/storage'.$item->document_path);
		                                    }
		                                    $item->document_path = $doc_path;*/
		                                    $doc_path = '';
		                                	$doc_name = '';
		                                	if(!empty($item->hasExamDocument) && sizeof($item->hasExamDocument)>0){

		                                		$new_document_path = self::StorePath($item->hasExamDocument[0]->document_path.'/');

		                                		// if (!empty($item->hasExamDocument[0]->document_path) && is_file(storage_path().$item->hasExamDocument[0]->document_path))
			                                    if (!empty($item->hasExamDocument[0]->document_path))
			                                    {
			                                    	$doc_path = self::getFilePath($item->hasExamDocument[0]->document_path);
			                                        //$doc_path = url('/storage'.$item->hasExamDocument[0]->document_path);
			                                        $doc_name = $item->hasExamDocument[0]->document_name;
			                                    }

		                                	}
		                                    //$item->record_type = 'appointment_types';
		                                    $item->document_path = $doc_path;
		                                    $item->doc_name = $doc_name;
		                                    return $item;
		                                });


		                }

						$collections = $appointment_type_docs->merge($appointment_exams_docs);

						// $collection = $collection->only(['id','old_id','first_name','family_name','email','country_code','mobile_no','birth_date','age','road','place','postal_code','gdpr']);

						if(!empty($collections) && sizeof($collections)>0){
							$data[] = $collections;
		                	$message = __('api.DATA_FOUND_SUCCESS');
						}else{

			          		$data[] = $collections;
		                	$message = __('api.ERR_NOT_FOUND');
						}


		          		self::_createLog('getPatientSignedDocuments',$data,'info');
	                } else{
	                 	$message = __('api.ERR_NOT_FOUND');
	                 	self::_createLog('getPatientSignedDocuments',$message,'error');
	                }

                }
	        	catch(\Exception $e) {

	            	$message = __('api.ERR_SOMETHING_WRONG');
	            	$errors[] = [
			              "error" => $e->getMessage(),
			          ];
	                self::_createLog('getPatientSignedDocuments',$errors,'error');
	                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
	        	}
			}
	    return self::_sendResult($message,$data,$errors,$status);
    }

    public function getPatientSignedDocumentsQRCodeOld(Request $request) //Used on live App
    {
    	//dd($request->all());
		$errors = [];
		$data = [];
		$message = __('api.DATA_FOUND_SUCCESS');
		$status = false;
		$finalSeviceDoc = $service_doc = $finalGeneralDoc = $general_doc = [];
		$appoitment_id   = $request->appoitment_id;
		$str = '';
		// $tab_pin   = $request->tab_pin;
		// dd($old_id);
		$validator = Validator::make($request->all(), [
               		'appoitment_id' => 'required',
               		// 'tab_pin' => 'required',
	            ], [
					'appoitment_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					// 'tab_pin.required'	=> 'Tab-Pin ist erforderlich',
				]);
		if($validator->fails())
		{
			$errors[] = $validator->errors();
		}
		else {
			try
			{
				$status = true;
				$getAppointment = $this->AppointmentModel->where('id',$appoitment_id)->first();
				if(!empty($getAppointment))
                {
                    // GENERAL DOCUMENT
                    $getGeneralDocument = $this->SpecialistDocumentsModel
                               ->where('status','1')
                               ->where('type_of_document','general')
                               ->get();

                    if(!empty($getGeneralDocument) && sizeof($getGeneralDocument)>0)
                    {
                    	//dd("--");
                        foreach ($getGeneralDocument as $generalDoc_key => $generalDoc_val)
                        {
                            $getSpecilistDocument = $this->SpecialistDocumentsModel
                                                   ->where('id',$generalDoc_val['id'])
                                                   ->first();

                            if(!empty($getSpecilistDocument))
                            {
                                // $l_date = self::checkFrequency($getAppointment->patient_id,$appointmentId,$getSpecilistDocument,null);

                                //if(!empty($l_date))
                                //{
                                	$doc = self::existDocumentRecord($getAppointment,$getSpecilistDocument,null,'general');

                                    if($doc != 'false')
                                    {
                                    	$header_path_gen = self::getFilePath($getSpecilistDocument['header_image_path']);
	                                    //dump($header_path_gen);
	                                    $footer_path_gen = self::getFilePath($getSpecilistDocument['footer_image_path']);
                                    	// $general_doc = self::getDocumentDetails($generalDoc_key,$getSpecilistDocument,$generalDoc_val,$doc,null);
                                    	$getPatientDetails = PatientsModel::where('id',$getAppointment->patient_id)->first();
							        	if(isset($getPatientDetails) && !empty($getPatientDetails))
							        	{
							        		$patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
							        	    $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
							        	    $patientFullName = $patientFirstName.' '.$patientLastName;
							        	    $patientDob = isset($getPatientDetails->birth_date)?date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
							        	}

							        	//commented on 17oct22
							        	/*$str.='<br/><br/>
							            <div style="width: 100%;" align="right">
							               <label>Patientenname : </label> '.$patientFullName.'
							            </div>

							            <div style="width: 100%;" align="right">
							              <label>Patient Birthdate : </label>'.$patientDob.'</div>
							            <br/><br/>';

                                    	$str .= '<div style="width: 100%;">
			                                      <div  style="background-color: '.$generalDoc_val['background_color'].'">';
		                                if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
		                                {
		                                	$str .= '<img style="width: 100%;height: auto;" src="'.$header_path_gen.'" >';
		                                }
		                                $str .= '<div style="margin-left: 52px;margin-right: 20px;">
		                                        <h4>'.ucfirst($generalDoc_val['name']).'</h4>
		                                        <p>'.ucfirst($generalDoc_val['html_text']).'</p>
		                                    </div>';
		                                if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
		                                {
		                                	$str .= '<img style="width: 100%;height: auto;" src="'.$footer_path_gen.'" >';
		                                }
		                                $str .= '</div>
		                                    <div>

		                                        <p>Ihr pureGyn Team</p>
		                                    </div>
		                                </div>';*/



		                                $str.='<br/><br/>
							            <div style="width: 100%;" align="right">
							               <label>Patientenname : </label> '.$patientFullName.'
							            </div>

							            <div style="width: 100%;" align="right">
							              <label>Geburtsdatum : </label>'.$patientDob.'</div>
							            <br/><br/>';

                                    	$str .= '<div style="width: 100%;">
			                                      <div  style="background-color: '.$generalDoc_val['background_color'].'">';
		                                if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
		                                {
		                                	$str .= '<img style="width: 100%;height: auto;" src="'.$header_path_gen.'" >';
		                                }
		                                $str .= '<div style="margin-left: 52px;margin-right: 20px;">
		                                        <h4>'.ucfirst($generalDoc_val['name']).'</h4>
		                                        <p>'.ucfirst($generalDoc_val['html_text']).'</p>
		                                    </div>';

		                                $str .= '</div> <br/>
		                                    <div style="margin-left: 52px;">
		                                        <p>Ihr pureGyn Team</p>
		                                    </div>';

		                                 if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
		                                {
		                                	$str .= '<img style="width: 100%;height: auto;" src="'.$footer_path_gen.'" >';
		                                }

		                                 $str .='</div>';



	                                    $service_doc[$generalDoc_key]['Html']  = $str;
	                                    $service_doc[$generalDoc_key]['examination_id']  = null;
	                                    $service_doc[$generalDoc_key]['doc_id']  = $doc->id;
	                                    $service_doc[$generalDoc_key]['doc_status']  = $doc->doc_status;
	                                    $service_doc[$generalDoc_key]['type']    = 'general';
	                                    $service_doc[$generalDoc_key]['name']    = $generalDoc_val['name'];
	                                    $service_doc[$generalDoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($generalDoc_val->created_at));
	                                    $service_doc[$generalDoc_key]['singDoc']    = $getSpecilistDocument->signDoc;
	                                    $str = '';
                                    //}
                                }
                            }
                        }
	                    $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
                        $service_doc = [];
                        $str = '';
                    }
                    //dd($finalGeneralDoc);
                    // END GENERAL DOCUMENT getSpecilistDocument
                    // $getExamDocument = $this->AppointmentTypeHasExaminationsModel
                    //                     ->where('appoinment_id',$getAppointment->appointment_type_id)
                    //                     ->get();
                    $getExamDocument = $this->AppointmentHasExaminationsModel
                                        ->where('appointment_id',$appoitment_id)
                                        ->get();
                    if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                    {
                        foreach ($getExamDocument as $exam_key => $exam_val)
                        {
                            $getExamDocument = $this->ExaminationsHasMultipleDocumentListModel
                                                 ->where('fk_examinations_id',$exam_val['examination_id'])
                                                 ->get();
                            //dump($getExamDocument);
                            if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                            {
                                foreach ($getExamDocument as $examdoc_key => $examdoc_val)
                                {
                                    $getSpecilistDoc = $this->SpecialistDocumentsModel
                                                           ->where('status','1')
                                                           ->where('type_of_document','service')
                                                           ->where('id',$examdoc_val['fk_document_list_id'])
                                                           ->first();
                                    if(!empty($getSpecilistDoc))
                                    {
                                    	$doc = self::existDocumentRecord($getAppointment,$getSpecilistDoc,$exam_val['examination_id'],'performance');
	                                    if($doc != 'false')
	                                    {
	                                    	$header_path = self::getFilePath($getSpecilistDoc['header_image_path']);
                                    		$footer_path = self::getFilePath($getSpecilistDoc['footer_image_path']);
	                                    	// $service_doc = self::getDocumentDetails($examdoc_key,$getSpecilistDoc,$getSpecilistDoc,$doc,$exam_val['examination_id']);

	                                    	// $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
                                			// $service_doc = [];

                                			$getPatientDetails = PatientsModel::where('id',$getAppointment->patient_id)->first();
								        	if(isset($getPatientDetails) && !empty($getPatientDetails))
								        	{
								        		$patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
								        	    $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
								        	    $patientFullName = $patientFirstName.' '.$patientLastName;
								        	    $patientDob = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
								        	}


								        	/*$str.='<br/><br/>
								            <div style="width: 100%;" align="right">
								               <label>Patientenname : </label> '.$patientFullName.'
								            </div>

								            <div style="width: 100%;" align="right">
								              <label>Patient Birthdate : </label>'.$patientDob.'</div>
								            <br/><br/>';
                                			$str .= '<div style="width: 100%;">
				                                      <div  style="background-color: '.$getSpecilistDoc['background_color'].'">';
				                                if(isset($getSpecilistDoc['header_image_path']) && !empty($getSpecilistDoc['header_image_path']))
				                                {
				                                    $str .= '<img style="width: 100%;height: auto;" src="'.$header_path.'" >';
				                                }
				                                $str .= '<div style="margin-left: 52px;margin-right: 20px;">
			                                            <h4>'.ucfirst($getSpecilistDoc['name']).'</h4>
			                                            <p>'.ucfirst($getSpecilistDoc['html_text']).'</p>
			                                        </div>';
				                                if(isset($getSpecilistDoc['footer_image_path']) && !empty($getSpecilistDoc['footer_image_path']))
				                                {
				                                    $str .= '<img style="width: 100%;height: auto;" src="'.$footer_path.'" >';
				                                }
				                                $str .= '</div>
				                                    <div>

				                                        <p>Ihr pureGyn Team</p>
				                                    </div>
				                                </div>';*/



				                           $str.='<br/><br/>
								            <div style="width: 100%;" align="right">
								               <label>Patientenname : </label> '.$patientFullName.'
								            </div>

								            <div style="width: 100%;" align="right">
								              <label>Geburtsdatum : </label>'.$patientDob.'</div>
								            <br/><br/>';
                                			$str .= '<div style="width: 100%;">
				                                      <div  style="background-color: '.$getSpecilistDoc['background_color'].'">';
				                                if(isset($getSpecilistDoc['header_image_path']) && !empty($getSpecilistDoc['header_image_path']))
				                                {
				                                    $str .= '<img style="width: 100%;height: auto;" src="'.$header_path.'" >';
				                                }
				                                $str .= '<div style="margin-left: 52px;margin-right: 20px;">
			                                            <h4>'.ucfirst($getSpecilistDoc['name']).'</h4>
			                                            <p>'.ucfirst($getSpecilistDoc['html_text']).'</p>
			                                        </div>';

				                                $str .= '</div> <br/>
				                                    <div style="margin-left: 52px;">
				                                        <p>Ihr pureGyn Team</p>
				                                    </div>';

				                                if(isset($getSpecilistDoc['footer_image_path']) && !empty($getSpecilistDoc['footer_image_path']))
				                                {
				                                    $str .= '<img style="width: 100%;height: auto;" src="'.$footer_path.'" >';
				                                }

				                                $str .= '</div>';


                                           	$service_doc[$examdoc_key]['Html']  = $str;
                                           	$service_doc[$examdoc_key]['examination_id']  = $exam_val['examination_id'];
                                           	$service_doc[$examdoc_key]['doc_id']  = $doc->id;
                                            $service_doc[$examdoc_key]['doc_status']  = $doc->doc_status;
                                           	$service_doc[$examdoc_key]['type']  = 'service';
                                           	$service_doc[$examdoc_key]['name']  = $getSpecilistDoc['name'];
                                           	$service_doc[$examdoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($getSpecilistDoc->created_at));
                                           	$service_doc[$examdoc_key]['singDoc']    = $getSpecilistDoc['signDoc'];
                                           	$str = '';
	                                    }
                                    }
                                }
                                $str = '';
                            	$finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
                            	$service_doc = [];
                            }
                        }
                    }
                }
                else {
                 	$message = __('api.ERR_NOT_FOUND');
                 	self::_createLog('getPatientSignedDocuments',$message,'error');
                }
            }
        	catch(\Exception $e)
        	{
            	$message = __('api.ERR_SOMETHING_WRONG');
            	$errors[] = [
		            	"error" => $e->getMessage(),
		          	];
                self::_createLog('getPatientSignedDocuments',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get'); generalDoc_key
        	}
		}
		//dd($finalSeviceDoc);
	    return self::_sendResult($message,$finalSeviceDoc,$errors,$status);
    }

    //26-Dec-22 addded by swati=================================================
     public function getPatientSignedDocumentsQRCode(Request $request) //Used on live App
    {
    	Log::info('in getPatientSignedDocumentsQRCode ..');

    	$errors = [];
		$data = [];
		$message = __('api.DATA_FOUND_SUCCESS');
		$status = false;
		$finalSeviceDoc = $service_doc = $finalGeneralDoc = $general_doc = [];
		$appoitment_id   = $request->appoitment_id;
		$str = '';

		Log::info($request->all());


		$validator = Validator::make($request->all(), [
               		'appoitment_id' => 'required',
	            ], [
					'appoitment_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
				]);
		if($validator->fails())
		{
			$errors[] = $validator->errors();
		}
		else {
			try
			{

				Log::info('in getPatientSignedDocumentsQRCode try block..');


				$status = true;
				$getAppointment = $this->AppointmentModel->where('id',$appoitment_id)->first();
				if(!empty($getAppointment))
                {

                	Log::info('in getPatientSignedDocumentsQRCode in not empty appointment..');


                    // GENERAL DOCUMENT
                    $getGeneralDocument = $this->SpecialistDocumentsModel
                               ->where('status','1')
                               ->where('type_of_document','general');
                            //    ->get();
					 if (!empty($appoitment_id)) {
                        $getSpecialistId = $this->AppointmentModel
                                            ->with('appointmentType')
                                            ->find($appoitment_id)
                                            ->appointmentType
                                            ->fk_specialist_id ?? null;

                        if ($getSpecialistId) {
                            $getGeneralDocument->where('fk_specialist_id', $getSpecialistId);
                        }
						$getGeneralDocument = $getGeneralDocument->get();
                    }
                    if(!empty($getGeneralDocument) && sizeof($getGeneralDocument)>0)
                    {
                    	foreach ($getGeneralDocument as $generalDoc_key => $generalDoc_val)
                        {
                            $getSpecilistDocument = $this->SpecialistDocumentsModel
                                                   ->where('id',$generalDoc_val['id'])
                                                   ->first();

                            if(!empty($getSpecilistDocument))
                            {
                                	$doc = self::existDocumentRecordNew($getAppointment,$getSpecilistDocument,null,'general');
                                	if($doc != 'false')
                                    {
                                    	$header_path_gen = self::getFilePath($getSpecilistDocument['header_image_path']);
	                                    $footer_path_gen = self::getFilePath($getSpecilistDocument['footer_image_path']);
                                    	$getPatientDetails = PatientsModel::where('id',$getAppointment->patient_id)->first();
							        	if(isset($getPatientDetails) && !empty($getPatientDetails))
							        	{
							        		$patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
							        	    $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
							        	    $patientFullName = $patientFirstName.' '.$patientLastName;
							        	    $patientDob = isset($getPatientDetails->birth_date)?date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
							        	}
		                                $str.='<br/><br/>
							            <div style="width: 100%;" align="right">
							               <label>Patientenname : </label> '.$patientFullName.'
							            </div>

							            <div style="width: 100%;" align="right">
							              <label>Geburtsdatum : </label>'.$patientDob.'</div>
							            <br/><br/>';

                                    	$str .= '<div style="width: 100%;">
			                                      <div  style="background-color: '.$generalDoc_val['background_color'].'">';
		                                if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
		                                {
											// changes by vijay 12/9/24 #195
		                                	// $str .= '<img style="width: 100%;height: auto;" src="'.$header_path_gen.'" >';
											//if (file_exists($header_path_gen)) {


												//$str .= '<img style="width: 100%;height: auto;" src="' . $header_path_gen . '" >'; //commented on 29-may-25


											//}//commented if condition on 14-feb-25
											// end


											 //added on 29-may-25 for header footer not display   
                                            // $response = Http::withOptions([
                                            //     'verify' => false, // disables SSL cert validation
                                            // ])->head($header_path_gen);
                                            // if ($response->ok()) 
                                            // {    
                                            //     $str .= '<img style="width: 100%;height:auto;" src="'.$header_path_gen.'" >';
                                            // }	
											$str .= '<img style="width: 100%;height:auto;" src="'.$header_path_gen.'" >';


		                                }
		                                $str .= '<div style="margin-left: 52px;margin-right: 20px;">
		                                        <h4>'.ucfirst($generalDoc_val['name']).'</h4>
		                                        <p>'.ucfirst($generalDoc_val['html_text']).'</p>
		                                    </div>';

		                                $str .= '</div> <br/>
		                                    <div style="margin-left: 52px;">
		                                        <p>Ihr pureGyn Team</p>
		                                    </div>';

		                                 if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
		                                {
											// changes by vijay 12/9/24 #195
		                                	// $str .= '<img style="width: 100%;height: auto;" src="'.$footer_path_gen.'" >';
											//if (file_exists($footer_path_gen)) {


											//$str .= '<img style="width: 100%;height: auto;" src="' . $footer_path_gen . '" >';//commented on 29-may-25

											//} //commented if condition on 14-feb-25

		                                	 //added on 28-may-25 for header footer not display   
                                            // $response = Http::withOptions([
                                            //     'verify' => false, // disables SSL cert validation
                                            // ])->head($footer_path_gen);
                                            // if ($response->ok()) 
                                            // {    
                                            //     $str .= '<img style="width: 100%;height:auto;" src="'.$footer_path_gen.'" >';
                                            // }
											$str .= '<img style="width: 100%;height:auto;" src="'.$footer_path_gen.'" >';


		                                }   

		                                 $str .='</div>';
	                                    $service_doc[$generalDoc_key]['Html']  = $str;
	                                    $service_doc[$generalDoc_key]['examination_id']  = null;
	                                    $service_doc[$generalDoc_key]['doc_id']  = $doc->id;
	                                    $service_doc[$generalDoc_key]['doc_status']  = $doc->doc_status;
	                                    $service_doc[$generalDoc_key]['type']    = 'general';
	                                    $service_doc[$generalDoc_key]['name']    = $generalDoc_val['name'];
	                                    $service_doc[$generalDoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($generalDoc_val->created_at));
	                                    $service_doc[$generalDoc_key]['singDoc']    = $getSpecilistDocument->signDoc;
	                                    $str = '';
                                }
                            }
                        }
	                    $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
                        $service_doc = [];
                        $str = '';
                    }
                    $getExamDocument = $this->AppointmentHasExaminationsModel
                                        ->where('appointment_id',$appoitment_id)
                                        ->get();
                    if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                    {
                        foreach ($getExamDocument as $exam_key => $exam_val)
                        {
                            $getExamDocument = $this->ExaminationsHasMultipleDocumentListModel
                                                 ->where('fk_examinations_id',$exam_val['examination_id'])
                                                 ->get();
                            //dump($getExamDocument);
                            if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
                            {
                                foreach ($getExamDocument as $examdoc_key => $examdoc_val)
                                {
                                    $getSpecilistDoc = $this->SpecialistDocumentsModel
                                                           ->where('status','1')
                                                           ->where('type_of_document','service')
                                                           ->where('id',$examdoc_val['fk_document_list_id'])
                                                           ->first();
                                    if(!empty($getSpecilistDoc))
                                    {
                                    	$doc = self::existDocumentRecordNew($getAppointment,$getSpecilistDoc,$exam_val['examination_id'],'performance');
	                                    if($doc != 'false')
	                                    {
	                                    	$header_path = self::getFilePath($getSpecilistDoc['header_image_path']);
                                    		$footer_path = self::getFilePath($getSpecilistDoc['footer_image_path']);
                                			$getPatientDetails = PatientsModel::where('id',$getAppointment->patient_id)->first();
								        	if(isset($getPatientDetails) && !empty($getPatientDetails))
								        	{
								        	    $patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
								        	    $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
								        	    $patientFullName = $patientFirstName.' '.$patientLastName;
								        	    $patientDob = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)):'';
								        	}
				                           $str.='<br/><br/>
								            <div style="width: 100%;" align="right">
								               <label>Patientenname : </label> '.$patientFullName.'
								            </div>

								            <div style="width: 100%;" align="right">
								              <label>Geburtsdatum : </label>'.$patientDob.'</div>
								            <br/><br/>';
                                			$str .= '<div style="width: 100%;">
				                                      <div  style="background-color: '.$getSpecilistDoc['background_color'].'">';
				                                if(isset($getSpecilistDoc['header_image_path']) && !empty($getSpecilistDoc['header_image_path']))
				                                {
													// changes by vijay 12/9/24 #195
				                                    // $str .= '<img style="width: 100%;height: auto;" src="'.$header_path.'" >';
													//if (file_exists($header_path)) {


														//$str .= '<img style="width: 100%;height: auto;" src="' . $header_path . '" >';//commented on 29-may-25


													//}//commented if condition on 14-feb-25
													// end

													 //added on 29-may-25 for header footer not display   
		                                            // $response = Http::withOptions([
		                                            //     'verify' => false, // disables SSL cert validation
		                                            // ])->head($header_path);
		                                            // if ($response->ok()) 
		                                            // {    
		                                            //     $str .= '<img style="width: 100%;height:auto;" src="'.$header_path.'" >';
		                                            // }
		                                                $str .= '<img style="width: 100%;height:auto;" src="'.$header_path.'" >';


				                                }
				                                $str .= '<div style="margin-left: 52px;margin-right: 20px;">
			                                            <h4>'.ucfirst($getSpecilistDoc['name']).'</h4>
			                                            <p>'.ucfirst($getSpecilistDoc['html_text']).'</p>
			                                        </div>';

				                                $str .= '</div> <br/>
				                                    <div style="margin-left: 52px;">
				                                        <p>Ihr pureGyn Team</p>
				                                    </div>';

				                                if(isset($getSpecilistDoc['footer_image_path']) && !empty($getSpecilistDoc['footer_image_path']))
				                                {
														// changes by vijay 12/9/24 #195
														// $str .= '<img style="width: 100%;height: auto;" src="' . $footer_path . '" >';
														//if (file_exists($footer_path)) {


														//$str .= '<img style="width: 100%;height: auto;" src="' . $footer_path . '" >';//commented on 29-may-25

														//}//commented if condition on 14-feb-25
														// end

				                                	 //added on 28-may-25 for header footer not display   
			                                            // $response = Http::withOptions([
			                                            //     'verify' => false, // disables SSL cert validation
			                                            // ])->head($footer_path);
			                                            // if ($response->ok()) 
			                                            // {    
			                                            //     $str .= '<img style="width: 100%;height:auto;" src="'.$footer_path.'" >';
			                                            // }
			                                                $str .= '<img style="width: 100%;height:auto;" src="'.$footer_path.'" >';


												}

				                                $str .= '</div>';


                                           	$service_doc[$examdoc_key]['Html']  = $str;
                                           	$service_doc[$examdoc_key]['examination_id']  = $exam_val['examination_id'];
                                           	$service_doc[$examdoc_key]['doc_id']  = $doc->id;
                                            $service_doc[$examdoc_key]['doc_status']  = $doc->doc_status;
                                           	$service_doc[$examdoc_key]['type']  = 'service';
                                           	$service_doc[$examdoc_key]['name']  = $getSpecilistDoc['name'];
                                           	$service_doc[$examdoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($getSpecilistDoc->created_at));
                                           	$service_doc[$examdoc_key]['singDoc']    = $getSpecilistDoc['signDoc'];
                                           	$str = '';
	                                    }
                                    }
                                }
                                $str = '';
                            	$finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
                            	$service_doc = [];
                            }
                        }
                    }
                }
                else {
                 	$message = __('api.ERR_NOT_FOUND');
                 	self::_createLog('getPatientSignedDocuments',$message,'error');
                }
            }
        	catch(\Exception $e)
        	{
        		Log::info('in getPatientSignedDocumentsQRCode catch block..');

            	$message = __('api.ERR_SOMETHING_WRONG');
            	$errors[] = [
		            	"error" => $e->getMessage(),
		          	];
                self::_createLog('getPatientSignedDocuments',$errors,'error');
        	}
		}
		//dd($finalSeviceDoc);
	    return self::_sendResult($message,$finalSeviceDoc,$errors,$status);
    }



    public function existDocumentRecordNew($getAppointment,$getSpecilistDocument,$exam_id,$type)
    {
	    $patintDocument = $this->PatientHasDocumentsModel
				          ->where('patient_id',$getAppointment['patient_id'])
                          ->where('appointment_id',$getAppointment['id'])
                          //->where('exam_app_type_id',$getAppointment['appointment_type_id'])
                          ->where('fk_document_id',$getSpecilistDocument->id)
                          //->where('record_type',0)
				         ->first();
		//dd($patintDocument);
		if(!empty($patintDocument))
		{
			$collections = $this->SpecialistDocumentsModel->where('signDoc','sign')
			                    ->find($patintDocument->fk_document_id);
			$doc_status = $patintDocument->doc_status;
			$d_status = explode(',', $doc_status);
            if(in_array('0', $d_status) && !in_array('2', $d_status) && !in_array('1', $d_status)) return $patintDocument;
			else if(!in_array('2', $d_status) && !empty($collections)) return $patintDocument;
			else return 'false';
		}
		else
		{
			$l_date = self::checkFrequency($getAppointment['patient_id'],$getAppointment['id'],$getSpecilistDocument,$exam_id);
			 if(!empty($l_date))
            {
                $getAppointment = $this->AppointmentModel->find($getAppointment['id']);
                $getdocrecord   = self::saveGeneralDocument($getSpecilistDocument['id'],$getAppointment,$type,$l_date,$exam_id);

                return $getdocrecord;

            }
            else
            {
            	return 'false';
            }
		}
    }

    //End ======================================================================

	public function getPatientSignedDocumentsQRCodePdf(Request $request) //Clone of getPatientSignedDocumentsQRCode
	{
		//dd($request->all());

		//Log::info('in getPatientSignedDocumentsQRCodePdf function :');

		$errors = [];
		$data = [];
		//$message = __('api.DATA_FOUND_SUCCESS');
		$message = __('api.ERR_NOT_FOUND');  // Added at 10aug22
		$status = false;
		$finalSeviceDoc = $service_doc = $finalGeneralDoc = $general_doc = [];
		$appoitment_id   = $request->appoitment_id;
		$str = '';
		$validator = Validator::make($request->all(), [
					'appoitment_id' => 'required',
					// 'tab_pin' => 'required',
				], [
					'appoitment_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					// 'tab_pin.required'	=> 'Tab-Pin ist erforderlich',
				]);
		if($validator->fails())
		{
			$errors[] = $validator->errors();
		}
		else {
			try
			{
				// $status = true; //commented at 10aug22
				$getAppointment = $this->AppointmentModel->where('id',$appoitment_id)->first();
				if(!empty($getAppointment))
				{
					$pdfURL = '';
					$pdfURLId = '';
					$pdfName = '';
					//Fetch PDF file path... //Added by Shyam 04-01-22
					$patientHasDocuments = $this->PatientHasDocumentsModel
												->where('appointment_id',$appoitment_id)
												//->where('doc_status','!=','2')
												->whereRaw('NOT FIND_IN_SET(2,doc_status)')
												->whereNotNull('pdf_path')
												->first();
					if(!empty($patientHasDocuments))
					{
						//Log::info('in getPatientSignedDocumentsQRCodePdf function patientHasDocuments:');

					   $status = true;	// Added at 10aug22

						$getDatabase = DB::connection('system')->table("tenants")
										->where('ordination_id',Config('ordination_id'))->first(['uuid']);
						$pdfURLId = $patientHasDocuments->id;
						$pdfName = $patientHasDocuments->pdf_name;
						$pdfURL = url('storage/tenancy/tenants/'.$getDatabase->uuid.$patientHasDocuments->pdf_path);
					//}//commented at 10-aug-22

					//Added by Shyam 04-01-22
					// GENERAL DOCUMENT
					$getGeneralDocument = $this->SpecialistDocumentsModel
												->where('status','1')
												->where('type_of_document','general')
												->get();
					$patientFirstName = $patientLastName = $patientFullName= $patientDob= '';

					if(!empty($getGeneralDocument) && sizeof($getGeneralDocument)>0)
					{
						foreach ($getGeneralDocument as $generalDoc_key => $generalDoc_val)
						{
							$getSpecilistDocument = $this->SpecialistDocumentsModel
														->where('id',$generalDoc_val['id'])
														->first();
							if(!empty($getSpecilistDocument))
							{

							//Log::info('in getPatientSignedDocumentsQRCodePdf function getSpecilistDocument:');

								// $l_date = self::checkFrequency($getAppointment->patient_id,$appointmentId,$getSpecilistDocument,null);
								//if(!empty($l_date)) {

								$doc = self::existDocumentRecord($getAppointment,$getSpecilistDocument,null,'general');
								if($doc != 'false')
								{
									$header_path_gen = self::getFilePath($getSpecilistDocument['header_image_path']);
									//dump($header_path_gen);
									$footer_path_gen = self::getFilePath($getSpecilistDocument['footer_image_path']);
									// $general_doc = self::getDocumentDetails($generalDoc_key,$getSpecilistDocument,$generalDoc_val,$doc,null);



									/*$str .= '<div style="width: 100%;">
										<div  style="background-color: '.$generalDoc_val['background_color'].'">';
									if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
									{
										$str .= '<img style="width: 100%;height: auto;" src="'.$header_path_gen.'" >';
									}
									$str .= '<div style="margin-left: 52px;margin-right: 20px;">
										<h4>'.ucfirst($generalDoc_val['name']).'</h4>
										<p>'.ucfirst($generalDoc_val['html_text']).'</p>
									</div>';
									if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
									{
										$str .= '<img style="width: 100%;height: auto;" src="'.$footer_path_gen.'" >';
									}
									$str .= '</div>
										<div>

											<p>Ihr pureGyn Team</p>
										</div>
									</div>'; */


									$str .= '<div style="width: 100%;">
										<div  style="background-color: '.$generalDoc_val['background_color'].'">';
									if(isset($getSpecilistDocument['header_image_path']) && !empty($getSpecilistDocument['header_image_path']))
									{
										$str .= '<img style="width: 100%;height: auto;" src="'.$header_path_gen.'" >';
									}
									$str .= '<div style="margin-left: 52px;margin-right: 20px;">
										<h4>'.ucfirst($generalDoc_val['name']).'</h4>
										<p>'.ucfirst($generalDoc_val['html_text']).'</p>
									</div>';

									$str .= '</div> <br/>
										<div style="margin-left: 52px;">
											<p>Ihr pureGyn Team</p>
										</div>';

									if(isset($getSpecilistDocument['footer_image_path']) && !empty($getSpecilistDocument['footer_image_path']))
									{
										$str .= '<img style="width: 100%;height: auto;" src="'.$footer_path_gen.'" >';
									}
									$str .= '</div>';



									$service_doc[$generalDoc_key]['Html']  = $str;
									$service_doc[$generalDoc_key]['examination_id']  = null;
									$service_doc[$generalDoc_key]['doc_id']  = $doc->id;
									$service_doc[$generalDoc_key]['doc_status']  = $doc->doc_status;
									$service_doc[$generalDoc_key]['type']    = 'general';
									$service_doc[$generalDoc_key]['name']    = $generalDoc_val['name'];
									$service_doc[$generalDoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($generalDoc_val->created_at));
									$service_doc[$generalDoc_key]['singDoc']    = $getSpecilistDocument->signDoc;
									$service_doc[$generalDoc_key]['pdf_id']    = $pdfURLId;
									$service_doc[$generalDoc_key]['pdf_url']   = $pdfURL;
									$str = '';
								} //}
							}
						}
						$finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
						$service_doc = [];
						$str = '';
					}
					// END GENERAL DOCUMENT getSpecilistDocument
					$getExamDocument = $this->AppointmentTypeHasExaminationsModel
											->where('appoinment_id',$getAppointment->appointment_type_id)
											->get();
					if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
					{
						//Log::info('in getPatientSignedDocumentsQRCodePdf function getExamDocument:');

						foreach ($getExamDocument as $exam_key => $exam_val)
						{
							$getExamDocument = $this->ExaminationsHasMultipleDocumentListModel
													->where('fk_examinations_id',$exam_val['examination_id'])
													->get();
							if(!empty($getExamDocument) && sizeof($getExamDocument)>0)
							{
								foreach ($getExamDocument as $examdoc_key => $examdoc_val)
								{
									$getSpecilistDoc = $this->SpecialistDocumentsModel
															->where('status','1')
															->where('type_of_document','service')
															->where('id',$examdoc_val['fk_document_list_id'])
															->first();
									if(!empty($getSpecilistDoc))
									{

										$doc = self::existDocumentRecord($getAppointment,$getSpecilistDoc,$exam_val['examination_id'],'performance');
										if($doc != 'false')
										{
											$header_path = self::getFilePath($getSpecilistDoc['header_image_path']);
											$footer_path = self::getFilePath($getSpecilistDoc['footer_image_path']);
											// $service_doc = self::getDocumentDetails($examdoc_key,$getSpecilistDoc,$getSpecilistDoc,$doc,$exam_val['examination_id']);
											// $finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
											// $service_doc = [];


											/*$str .= '<div style="width: 100%;">
												<div style="background-color: '.$getSpecilistDoc['background_color'].'">';
											if(isset($getSpecilistDoc['header_image_path']) && !empty($getSpecilistDoc['header_image_path']))
											{
												$str .= '<img style="width: 100%;height:auto;" src="'.$header_path.'" >';
											}
											$str .= '<div style="margin-left: 52px;margin-right: 20px;">
												<h4>'.ucfirst($getSpecilistDoc['name']).'</h4>
												<p>'.ucfirst($getSpecilistDoc['html_text']).'</p>
											</div>';
											if(isset($getSpecilistDoc['footer_image_path']) && !empty($getSpecilistDoc['footer_image_path']))
											{
												$str .= '<img style="width: 100%;height:auto;" src="'.$footer_path.'" >';
											}
											$str .= '</div>
												<div>

													<p>Ihr pureGyn Team</p>
												</div>
											</div>'; */


											$str .= '<div style="width: 100%;">
												<div style="background-color: '.$getSpecilistDoc['background_color'].'">';
											if(isset($getSpecilistDoc['header_image_path']) && !empty($getSpecilistDoc['header_image_path']))
											{
												$str .= '<img style="width: 100%;height:auto;" src="'.$header_path.'" >';
											}
											$str .= '<div style="margin-left: 52px;margin-right: 20px;">
												<h4>'.ucfirst($getSpecilistDoc['name']).'</h4>
												<p>'.ucfirst($getSpecilistDoc['html_text']).'</p>
											</div>';

											$str .= '</div><br/>
												<div style="margin-left: 52px;">
													<p>Ihr pureGyn Team</p>
												</div>';

											if(isset($getSpecilistDoc['footer_image_path']) && !empty($getSpecilistDoc['footer_image_path']))
											{
												$str .= '<img style="width: 100%;height:auto;" src="'.$footer_path.'" >';
											}
											$str .= '</div>';


											$service_doc[$examdoc_key]['Html']  = $str;
											$service_doc[$examdoc_key]['examination_id']  = $exam_val['examination_id'];
											$service_doc[$examdoc_key]['doc_id']  = $doc->id;
											$service_doc[$examdoc_key]['doc_status']  = $doc->doc_status;
											$service_doc[$examdoc_key]['type']  = 'service';
											$service_doc[$examdoc_key]['name']  = $getSpecilistDoc['name'];
											$service_doc[$examdoc_key]['created_at']    = date('Y-m-d H:i:s',strtotime($getSpecilistDoc->created_at));
											$service_doc[$examdoc_key]['singDoc']    = $getSpecilistDoc['signDoc'];
											$service_doc[$examdoc_key]['pdf_id']    = $pdfURLId;
											$service_doc[$examdoc_key]['pdf_url']    = $pdfURL;
											$str = '';
										}
									}
								}
								$str = '';
								$finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
								$service_doc = [];
							}
						}
					}
					if(sizeof($finalSeviceDoc) == 0)
					{
						$service_doc['0']['Html']           = '';
						$service_doc['0']['examination_id'] = '';
						$service_doc['0']['doc_id']         = '';
						$service_doc['0']['doc_status']     = '';
						$service_doc['0']['type']           = 'service';
						$service_doc['0']['name']           = $pdfName;
						$service_doc['0']['created_at']     = date('Y-m-d H:i:s');
						$service_doc['0']['singDoc']        = '';
						$service_doc['0']['pdf_id']         = $pdfURLId;
						$service_doc['0']['pdf_url']        = $pdfURL;
						$finalSeviceDoc = array_merge($finalSeviceDoc,$service_doc);
					}//if

					$message = __('api.DATA_FOUND_SUCCESS');

					//Log::info('in getPatientSignedDocumentsQRCodePdf function end of function:');

				   }//if patientHasDocuments
				}
				else {
					$message = __('api.ERR_NOT_FOUND');
					self::_createLog('getPatientSignedDocuments',$message,'error');
				}
			}
			catch(\Exception $e)
			{
				$message = __('api.ERR_SOMETHING_WRONG');
				$errors[] = [
								"error" => $e->getMessage(),
							];
				self::_createLog('getPatientSignedDocuments',$errors,'error');
			}
		}
		// dd($finalSeviceDoc);
		return self::_sendResult($message,$finalSeviceDoc,$errors,$status);
	}



    public function getDocumentDetails($key,$getSpecilistDocument,$generalDoc_val,$doc,$exam_id)
    {
    	$service_doc[$key]['examination_id']  = $exam_id;
        $service_doc[$key]['doc_id']  = $doc->id;
        $service_doc[$key]['type']    = 'general';
        $service_doc[$key]['name']    = $getSpecilistDocument['name'];
        $service_doc[$key]['html_text']               = $generalDoc_val['html_text'];
        $service_doc[$key]['header_image']            = $generalDoc_val['header_image'];
        $service_doc[$key]['header_image_path']       = $generalDoc_val['header_image_path'];
        $service_doc[$key]['footer_image']            = $generalDoc_val['footer_image'];
        $service_doc[$key]['footer_image_path']       = $generalDoc_val['footer_image_path'];
        $service_doc[$key]['background_color']        = $generalDoc_val['background_color'];
        $service_doc[$key]['frequency']               = $generalDoc_val['frequency'];
        $service_doc[$key]['frequency_type']          = $generalDoc_val['frequency_type'];
        $service_doc[$key]['last_date'] = date('Y-m-d H:i:s',strtotime($doc->activation_last_date));
        return $service_doc;
    }
    public function existDocumentRecord($getAppointment,$getSpecilistDocument,$exam_id,$type)
    {
    	$patintDocument = $this->PatientHasDocumentsModel
				          ->where('patient_id',$getAppointment['patient_id'])
                          ->where('appointment_id',$getAppointment['id'])
                          //->where('exam_app_type_id',$getAppointment['appointment_type_id'])
                          ->where('fk_document_id',$getSpecilistDocument->id)
                          //->where('record_type',0)
				         ->first();

		//dd($patintDocument);
		if(!empty($patintDocument))
		{
			$doc_status = $patintDocument->doc_status;
			$d_status = explode(',', $doc_status);
			//dump($d_status);
			//$status = explode(',', $patientHasDoc->doc_status);
			//$d_status = explode(',', $doc_status);
            if(in_array('0', $d_status))
			{
				return $patintDocument;
			}

			else if(!in_array('2', $d_status))
			{
				return $patintDocument;

			}
			else
			{
				return 'false';
			}
		}
		else
		{
			$l_date = self::checkFrequency($getAppointment['patient_id'],$getAppointment['id'],$getSpecilistDocument,$exam_id);
			 if(!empty($l_date))
            {
                $getAppointment = $this->AppointmentModel->find($getAppointment['id']);
                $getdocrecord   = self::saveGeneralDocument($getSpecilistDocument['id'],$getAppointment,$type,$l_date,$exam_id);

                return $getdocrecord;

            }
            else
            {
            	return 'false';
            }
		}
    }

    public function saveGeneralDocument($doc_id,$getAppointment,$type,$last_date,$exam_id)
    {
        if($type == 'general')
        {
            $existRec = $this->PatientHasDocumentsModel
                         ->where('type',$type)
                         ->where('patient_id',$getAppointment->patient_id)
                         ->where('appointment_id',$getAppointment->id)
                         ->where('exam_app_type_id',$getAppointment->appointment_type_id)
                         ->where('record_type',0)
                         ->where('fk_document_id',$doc_id)
                         ->first();
            $record_type = 0;
        }
        else
        {
            $existRec = $this->PatientHasDocumentsModel
                         ->where('type','service')
                         ->where('patient_id',$getAppointment->patient_id)
                         ->where('appointment_id',$getAppointment->id)
                         ->where('exam_app_type_id',$getAppointment->appointment_type_id)
                         ->where('record_type',1)
                         ->where('fk_document_id',$doc_id)
                         ->first();

            $record_type = 1;
        }

        if(!empty($existRec))
        {
            $getrecord = $this->PatientHasDocumentsModel->find($existRec->id);
            $getrecord->activation_start_date = Date('Y-m-d H:i:s');
            $getrecord->activation_last_date  = $last_date;
            $getrecord->doc_status            ='0';
            $getrecord->record_type      = $record_type;
            $getrecord->fk_document_id   = $doc_id;
            $getrecord->save();
            $id = $getrecord->id;
        }
        else
        {
            $getrecord = new PatientHasDocumentsModel;
            $getrecord->appointment_id   = $getAppointment->id;
            $getrecord->patient_id       = $getAppointment->patient_id;
            $getrecord->exam_app_type_id = $getAppointment->appointment_type_id;
            $getrecord->fk_examinations_id = $exam_id;
            $getrecord->fk_document_id   = $doc_id;
            $getrecord->record_type      = $record_type;
            $getrecord->doc_status       = 0;
            $getrecord->type             = $type;
            $getrecord->activation_start_date  = Date('Y-m-d H:i:s');
            $getrecord->activation_last_date   = $last_date;
            $getrecord->save();
            $id = $getrecord->id;
        }
        return $getrecord ;
    }

    public function checkFrequency($patient_id,$appointment_id,$getDocument,$exam_id)
    {
        //dd($getDocument['date_of_last_activation']);
        $data = [];
        $flag = 0;
        $l_date = '';
        $current_date = date('Y-m-d H:i:s');
        $activation_date = null ;
        $start_date = $end_date =null;
        if(!empty($getDocument['date_of_last_activation']) && $getDocument['date_of_last_activation']!= "0000-00-00 00:00:00")
        {
            $activation_date = date('Y-m-d H:i:s',strtotime($getDocument['date_of_last_activation']));
        }

        if(!empty($exam_id))
        {
            $patientHasDoc = $this->PatientHasDocumentsModel
                             ->where('appointment_id',$appointment_id)
                             ->where('patient_id',$patient_id)
                             ->where('fk_document_id',$getDocument['id'])
                             ->where('fk_examinations_id',$exam_id)
                             ->first();
        }
        else
        {
            $patientHasDoc = $this->PatientHasDocumentsModel
                            ->where('appointment_id',$appointment_id)
                            ->where('patient_id',$patient_id)
                            ->where('fk_document_id',$getDocument['id'])
                            ->first();
        }

        // ----------------------------------------------------------

        if(!empty($patientHasDoc))
        {
            $status = explode(',', $patientHasDoc->doc_status);

            if(in_array('0', $status))
            {
                $flag = 1;
            }
            else
            {
                $start_date   = Date('Y-m-d H:i:s',strtotime($patientHasDoc['activation_start_date']));
                $end_date     = Date('Y-m-d H:i:s',strtotime($patientHasDoc['activation_last_date']));
                $days = null;
                if(strtotime($activation_date) > strtotime($start_date) && !empty($activation_date))
                {
                    $flag = 1;
                }
                else if(strtotime($current_date) > strtotime($end_date))
                {
                    $flag = 1;
                }
            }

        }
        else
        {
            $flag = 1;
        }

        if($flag == 1)
        {
            if(!empty($getDocument->frequency_type))
            {
                switch ($getDocument->frequency_type)
                {
                    case "day":
                        $days = (int)$getDocument->frequency;
                    break;
                    case "month":
                        $days = 30 * (int)$getDocument->frequency;
                    break;
                    case "year":
                        $days = 365 * (int)$getDocument->frequency;
                    break;
                }
            }
            else
            {
                $l_date = $current_date;
            }

            if(!empty($days))
            {
                $duration  = (int)$days;
                $last_date = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +".$duration." day");
                $l_date    = Date('Y-m-d H:i:s',$last_date);
            }
        }
        //dd($l_date);
        return $l_date;
    }

    public function updatePatientSign(Request $request) //Used on live App
    {
		$errors = [];
		$data = [];
		$message = __('api.ERR_NOT_FOUND');
		$status = false;

		$id   = $request->id;
		// $tab_pin   = $request->tab_pin;
		// dd($old_id);
		$validator = Validator::make($request->all(), [
               		//'old_id' => 'required',
               		'id' => 'required',
               		//'patient_signature' => 'required',
	            ],
				[
					//'old_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					'id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					//'patient_signature.required'	=> 'Tab-Pin ist erforderlich',
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}else{

			try {

					$status = true;

					$patient_doc = $this->PatientHasDocumentsModel->find($id);
					$patient_doc->doc_status = 2;
					$patient_doc->remarks = '';

					 if(!empty($request->patient_signature))
                    {
                        $file_data = $request->patient_signature;

                        if(!empty(Config('ordination_id')))
		                {
		                    $getDatabaseName = DB::connection('system')
		                                ->table("tenants")
		                                ->where('ordination_id',Config('ordination_id'))
		                                ->first(['uuid']);

		                    $signPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/sign/';
		                }
		                else
		                {
		                    $signPath = '/opt/app-shared/php/data/storage/app/public/sign/';
		                }

                        $file_name = 'image_' . time() . '.png'; //generating unique file name;

                        //$a = Storage::put($file_name, base64_decode($file_data));
                        $a = Storage::disk('public')->put($signPath.$file_name, base64_decode($file_data));

						$patient_doc->remarks = $file_name;
                    }



	                if($patient_doc->save()){

	                	$message = __('api.DATA_FOUND_SUCCESS');
		          		$data[] = $patient_doc;

		          		self::_createLog('updatePatientSign',$data,'info');
	                } else{
	                 	$message = __('api.ERR_NOT_FOUND');
	                 	self::_createLog('updatePatientSign',$message,'error');
	                }

                }
	        	catch(\Exception $e) {

	            	$message = __('api.ERR_SOMETHING_WRONG');
	            	$errors[] = [
			              "error" => $e->getMessage(),
			          ];
	                self::_createLog('updatePatientSign',$errors,'error');
	                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
	        	}
			}
	    return self::_sendResult($message,$data,$errors,$status);
    }

    //Added by Shyam 01-01-22
	public function updatePatientSignPdf(Request $request) //Clone of updatePatientSign
	{
		$errors = [];
		$data = [];
		$message = __('api.ERR_NOT_FOUND');
		$status = false;
		$id   = $request->id;
		$validator = Validator::make($request->all(), [
						'id' => 'required',
					], [
						'id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					]);
		if($validator->fails())
		{
			$errors[] = $validator->errors();
		}
		else {
			try {
				$status = true;
				$patient_doc = $this->PatientHasDocumentsModel->find($id);
				if(!empty($patient_doc))
				{
					$getDatabase = DB::connection('system')->table("tenants")
									->where('ordination_id',Config('ordination_id'))->first(['uuid']);
					$pdfURL = url('storage/tenancy/tenants/'.$getDatabase->uuid.$patient_doc->pdf_path);
					$storagePath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabase->uuid; //server path
					// $storagePath = storage_path().'/app/public'; //local path
					$pdfFile = $storagePath.$patient_doc->pdf_path;
					$imageArray = array();
					array_push($imageArray, $id);
					array_push($imageArray, $pdfURL);
					// $pdf = new \App\Libraries\PdfToImage($pdfFile);
					// // dd($pdf->getNumberOfPages());
					// foreach (range(1, $pdf->getNumberOfPages()) as $pageNum)
					// {
					// 	$imageName = $storagePath.'/diagnostic_findings/'.date('YmdHis').'_'.$pageNum.'.png';
					// 	$pdf->setPage($pageNum)->saveImage($imageName);
					// 	array_push($imageArray, $imageName);
					// }
					$message = __('api.DATA_FOUND_SUCCESS');
					$data[] = $imageArray;
					self::_createLog('updatePatientSignPdf',$data,'info');
				}
				else {
					$message = __('api.ERR_NOT_FOUND');
					self::_createLog('updatePatientSignPdf',$message,'error');
				}
			}
			catch(\Exception $e)
			{
				$message = __('api.ERR_SOMETHING_WRONG');
				$errors[] = [
							"error" => $e->getMessage(),
						];
				self::_createLog('updatePatientSign',$errors,'error');
			}
		}
		return self::_sendResult($message,$data,$errors,$status);
	}

	public function updatePatientSignPdfTest(Request $request) //Clone for testing of GS & Imagick
	{
		$errors = [];
		$data = [];
		$message = __('api.ERR_NOT_FOUND');
		$status = false;
		$id   = $request->id;
		$validator = Validator::make($request->all(), [
						'id' => 'required',
					], [
						'id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					]);
		if($validator->fails())
		{
			$errors[] = $validator->errors();
		}
		else {
			try {
				$status = true;
				$patient_doc = $this->PatientHasDocumentsModel->find($id);
				if(!empty($patient_doc))
				{
					$getDatabase = DB::connection('system')->table("tenants")
									->where('ordination_id',Config('ordination_id'))->first(['uuid']);
					$pdfURL = url('storage/tenancy/tenants/'.$getDatabase->uuid.$patient_doc->pdf_path);
					$storagePath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabase->uuid; //server path
					// $storagePath = storage_path().'/app/public'; //local path
					$pdfFile = $storagePath.$patient_doc->pdf_path;
					$imageArray = array();
					// array_push($imageArray, $id);
					// array_push($imageArray, $pdfURL);
					// dd($pdfFile);
					$pdf = new \App\Libraries\PdfToImage($pdfFile);
					// dd($pdf->getNumberOfPages());
					foreach (range(1, $pdf->getNumberOfPages()) as $pageNum)
					{
						$imageName = $storagePath.'/diagnostic_findings/'.date('YmdHis').'_'.$pageNum.'.png';
						$pdf->setPage($pageNum)->saveImage($imageName);
						array_push($imageArray, $imageName);
					}
					$message = __('api.DATA_FOUND_SUCCESS');
					$data[] = $imageArray;
					self::_createLog('updatePatientSignPdf',$data,'info');
				}
				else {
					$message = __('api.ERR_NOT_FOUND');
					self::_createLog('updatePatientSignPdf',$message,'error');
				}
			}
			catch(\Exception $e)
			{
				$message = __('api.ERR_SOMETHING_WRONG');
				$errors[] = [
							"error" => $e->getMessage(),
						];
				self::_createLog('updatePatientSign',$errors,'error');
			}
		}
		return self::_sendResult($message,$data,$errors,$status);
	}
    //Added by Shyam 01-01-22

    //Added by Shyam 01-01-22
	public function updatePatientDocumentSignPdf(Request $request) //New API for updatePatientSignPdf
	{
		$errors = [];
		$data = [];
		$message = __('api.DATA_FOUND_SUCCESS');
		$status = false;
		$validator = Validator::make($request->all(), [
					'id' => 'required',
					'pdf_doc' => 'required|mimes:pdf',
	            ], [
					'id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					'pdf_doc.required'	=> 'PDF ist erforderlich',
				]);
		if($validator->fails())
		{
			$errors[] = $validator->errors();
		}
		else {
			$status = true;
			$id = $request->id;
			$patient_doc = $this->PatientHasDocumentsModel->find($id);
			$doc_status = explode(',', $patient_doc->doc_status);
			if(!in_array('2', $doc_status))
			{
				if(in_array('0', $doc_status))
				{
					$patient_doc->doc_status  = '2';
				}
				else {
					$patient_doc->doc_status  = $patient_doc->doc_status.',2';
				}
			}
            $getDatabaseName = DB::connection('system')
                        ->table("tenants")
                        ->where('ordination_id',Config('ordination_id'))
                        ->first(['uuid']);
            $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/document_pdf/'; //server path
            // $PdfPath = storage_path().'/app/public/document_pdf'; //local path
			$pdf_doc = $request->file('pdf_doc')->getClientOriginalName();
            $doc_name_pdf = str_replace(" ", '', str_replace(".pdf",'',$pdf_doc));
            $PDFname   = $doc_name_pdf.'_'.time().'.pdf';
            $accessPath = '/document_pdf/'.$PDFname;
            $request->file('pdf_doc')->move($PdfPath, $PDFname);
            //Update data...
            $patient_doc->pdf_name    = $PDFname;
            $patient_doc->pdf_path    = $accessPath;
            if($patient_doc->save())
            {
            	$message = __('api.DATA_FOUND_SUCCESS');
          		$data[] = $patient_doc;
          		self::_createLog('updatePatientSign',$data,'info');
            }
            else {
             	$message = __('api.ERR_NOT_FOUND');
             	self::_createLog('updatePatientSign',$message,'error');
            }
        }
	    return self::_sendResult($message,$data,$errors,$status);
    }
    //Added by Shyam 01-01-22

    public function updatePatientDocumentSign(Request $request)
    {
    	//Log::info('in updatePatientDocumentSign function :');

		$errors = [];
		$data = [];
		$message = __('api.DATA_FOUND_SUCCESS');
		$status = false;

		$id   = $request->id;
		// $tab_pin   = $request->tab_pin;
		// dd($old_id);
		$validator = Validator::make($request->all(), [
               		//'old_id' => 'required',
               		'id' => 'required',
               		//'patient_signature' => 'required',
	            ], [
					//'old_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					'id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					//'patient_signature.required'	=> 'Tab-Pin ist erforderlich',
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}
		else {
		// try {
			$status = true;
			$patient_doc = $this->PatientHasDocumentsModel->find($id);
			$doc_status = explode(',', $patient_doc->doc_status);
			$patient_doc->notes  = $request->notes;
           	if(!in_array('2', $doc_status))
           	{
           		if(in_array('0', $doc_status))
           		{
           			$patient_doc->doc_status  = '2';
           		}
           		else {
           			$patient_doc->doc_status  = $patient_doc->doc_status.',2';
           		}

           	}
           	//dd($patient_doc->doc_status);
			$patient_doc->remarks = '';
			//dd($request->patient_signature);
			if(!empty($request->patient_signature))
            {
            	//Log::info('if patient has signature :');

            	//dump($request->patient_signature);
                $file_data = $request->patient_signature;
				//dd($file_data);
				// $file_name = 'public/image_' . time() . '.png'; //generating

				// $a = Storage::put($file_name, base64_decode($file_data));
				//$file_name = 'image_' . time() . '.png'; //generating unique file name;

				//$a = Storage::put($file_name, base64_decode($file_data));
				// $a = Storage::disk('public')->put('/sign/'.$file_name, base64_decode($file_data));

                if(!empty(Config('ordination_id')))
                {
                    $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
                    $signPath = '/tenancy/tenants/'.$getDatabaseName->uuid.'/sign/';
                }
                else {
                    $signPath = '/opt/app-shared/php/data/storage/app/public/sign/';
                }
                $file_name = 'image_' . time() . '.png'; //generating unique file name;
				//$a = Storage::put($file_name, base64_decode($file_data));
				//dump($signPath.$file_name);
				$a = Storage::disk('public')->put($signPath.$file_name, base64_decode($file_data));
				//dd($a);
				// $patient_doc;
				$name = explode('/', $file_name);
				$patient_doc->remarks = $file_name;
            }
            if($patient_doc->save())
            {

            	//Log::info('if patient document saved then create pdf call :');
            	// Generate PDF
            	self::_createGeneralDocumentPdf($patient_doc,$patient_doc->patient_id);

            	$message = __('api.DATA_FOUND_SUCCESS');
          		$data[] = $patient_doc;

          		self::_createLog('updatePatientSign',$data,'info');
            }
            else {
             	$message = __('api.ERR_NOT_FOUND');
             	self::_createLog('updatePatientSign',$message,'error');
            }
			// }
			// catch(\Exception $e) {
			// $message = __('api.ERR_SOMETHING_WRONG');
			// $errors[] = [
			// "error" => $e->getMessage(),
			// ];
			// self::_createLog('updatePatientSign',$errors,'error');
			// // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
			// }
		}
	    return self::_sendResult($message,$data,$errors,$status);
    }


    public function _createGeneralDocumentPdf($doc_details,$id)
    {
    	//Log::info('in _createGeneralDocumentPdf function :' .$id);

        $data = $dataFinal = [];
        $flag = '0';
        $file_name ='';

        $collections = $this->SpecialistDocumentsModel->find($doc_details->fk_document_id);
        //dd($collections);
        if(!empty($collections))
        {
        	//Get Patient details added at 11aug22
        	$patientFirstName = $patientLastName = $patientFullName= $patientDob= '';
        	$getPatientDetails = PatientsModel::where('id',$id)->first();

        	if(!empty($getPatientDetails))
        	{
        		$patientFirstName = isset($getPatientDetails->first_name)?$getPatientDetails->first_name:'';
        	    $patientLastName = isset($getPatientDetails->family_name)?$getPatientDetails->family_name:'';
        	    $patientFullName = $patientFirstName.' '.$patientLastName;
        	    $patientDob = isset($getPatientDetails->birth_date)? date("d-m-Y",strtotime($getPatientDetails->birth_date)) :'';
        	}


        	$sign_path = self::getFilePath('/sign/'.$doc_details->remarks);

        	$notes_path = self::getFilePath('/notes/'.$doc_details->notes); // Added on 13oct22

        	//dd($sign_path);
            $data['doc_id']            = $collections->id;
            $data['name']              = $collections->name;
            $data['html_text']         = $collections->html_text;
            $data['background_color']  = $collections->background_color;
            $data['header_image']      = $collections->header_image;
            //$data['header_image_path'] = $collections->header_image_path;
            $data['footer_image']      = $collections->footer_image;
            //$data['footer_image_path'] = $collections->footer_image_path;
            $data['background_color']  = $collections->background_color;
            // $data['signature']         = $doc_details->remarks;
            $data['signature']         = $sign_path;
            // $data['notes']         = $doc_details->notes;  // Commented on 13oct22

            $data['notes']         = $notes_path; // Added on 13oct22



            //dd($doc_details->remarks);
           	//$PdfPath = self::StorePath('document_pdf');
           	if(!empty(Config('ordination_id')))
            {
                $getDatabaseName = DB::connection('system')
                            ->table("tenants")
                            ->where('ordination_id',Config('ordination_id'))
                            ->first(['uuid']);

                //$PdfPath = 'storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/document_pdf/';

                $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/document_pdf/';
                $data['header_image_path'] = url('storage/tenancy/tenants/'.$getDatabaseName->uuid.'/'.$collections->header_image_path);
            	$data['footer_image_path'] = url('storage/tenancy/tenants/'.$getDatabaseName->uuid.'/'.$collections->footer_image_path);
            }
            else {
                $PdfPath = '/opt/app-shared/php/data/storage/app/public/check_list_pdf/';

                $data['header_image_path'] = url('storage/app/public'.$collections->header_image_path);
                $data['footer_image']      = $collections->footer_image;
            	$data['footer_image_path'] = url('storage/app/public'.$collections->footer_image_path);
            }

            // Add patient data to data array added at 11aug22
            $data['patientFullName'] = $patientFullName;
            $data['patientDob'] = $patientDob;
            $data['currentDate'] = date('m/d/Y');

           // Log::info($data);
           	// dd($PdfPath);
            //$PdfPath   = storage_path().'/app/public/document_pdf/';
            $doc_name_pdf = str_replace(" ", '', $collections['name']);
            //$PDFname   = $doc_name_pdf.'_'.time().'.pdf';
            $PDFname = self::createPdfFileName($doc_details->patient_id,$collections['name']);
            // Invoice full path
            $StorePath = $PdfPath.$PDFname;
            //dd($StorePath);
            $accessPath = '/document_pdf/'.$PDFname;

            //added by swati 19-Jul-23 to work image if ssl is changed=====================
             $pdf = app('dompdf.wrapper');
             //############ Permitir ver imagenes si falla ################################
              $contxt = stream_context_create([
                'ssl' => [
                    'verify_peer' => FALSE,
                    'verify_peer_name' => FALSE,
                    'allow_self_signed' => TRUE,
                ]
            ]);

            $pdf = \PDF::setOptions(['isHTML5ParserEnabled' => true, 'isRemoteEnabled' => true]);
            $pdf->getDomPDF()->setHttpContext($contxt);
            //#################################################################################

            $PDFPath = 'admin.pdf.documentLists';
            //dd($PDFPath);
            $pdf->loadView($PDFPath,compact('data'))->save($StorePath);
            // dd("adbash");
            // end
            //========================================================================
            // pdf
            $current_date = date('Y-m-d H:i:s');

            $start_date   = null;
            $end_date     = null;
            $days = null;
            if(!empty($collections->frequency_type))
            {
            	switch ($collections->frequency_type)
	            {
	                case "day":

	                    $days = (int)$collections->frequency;

	                break;
	                case "month":

	                    $days = 30 * (int)$collections->frequency;

	                break;
	                case "year":

	                    $days = 365 * (int)$collections->frequency;
	                break;
	            }
            }
            // -------------------------
            if(!empty($days))
            {
                $duration  = (int)$days;
                $last_date = strtotime(date("Y-m-d H:i:s", strtotime($current_date)) . " +".$duration." day");
                $end_date    = Date('Y-m-d H:i:s',$last_date);
                $start_date  = $current_date;
            }
            // ===========================================================
            /* exam_id
                |Check List Selected questions inputdata
            */
           	$patient_doc = $this->PatientHasDocumentsModel->find($doc_details->id);
            $patient_doc->pdf_name    = $PDFname;
            $patient_doc->pdf_path    = $accessPath;
            $patient_doc->save();
            $dataFinal[] = $data;
            $data = [];
            // ===========================================================
            //$cnt++;
        }

        return $dataFinal;
    }



	public function smartphoneAppsSetting(Request $request)
	{

		$errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;

        try
        {

        	$forceSetting = DB::table('settings')
							->where('setting_key','=','FORCED UPDATE FOR SMARTPHONE APPS')
							->first(['status']);

            if(isset($forceSetting) && !empty($forceSetting)){
            	$forceStatus = $forceSetting->status;
            }							


            $collections = $this->SmartphoneAppsModel
                            ->orderBy('id','desc')
                            ->limit(1)
                            ->first();
            //dd($collections);
            if(!empty($collections))
            {
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                // $collections->android_review = '0'; //added on 10-jan-24 commented on 12-feb-26
                // $collections->ios_review = '1';  //added on 10-jan-24 commented on 6-feb-26
                $collections->ios_review = (string)$forceStatus;  //added on 6-feb-26
                $data  = $collections;
                self::_createLog('smartphoneAppsSetting',array($data),'info');
                // $this->ActivityLogModel->addApiLog('Get Examinations','get examinations','Get',null,$data);
            }
            else
            {
                $message  = __('api.ERR_NOT_FOUND');
                $errors[] = [
                      "error" => __('api.DATA_NOT_FOUND'),
                  ];
                self::_createLog('smartphoneAppsSetting',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('smartphoneAppsSetting',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

       return self::_sendResult($message,$data,$errors,$status);
	}

	public function ourServices(Request $request)
	{
		$errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;
        try
        {
        	$order = $request->order_by;
        	$data = $this->ExaminationsModel
        				   ->where('status',1)
        				   ->orderBy('sequence_no',$order)
        				   ->get();
            if(!empty($data))
            {
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                //$data  = $data;
                self::_createLog('ourServices',array($data),'info');
                // $this->ActivityLogModel->addApiLog('Get Examinations','get examinations','Get',null,$data);
            }
            else
            {
                $message  = __('api.ERR_NOT_FOUND');
                $errors[] = [
                      "error" => __('api.DATA_NOT_FOUND'),
                  ];
                self::_createLog('ourServices',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('ourServices',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

       return self::_sendResult($message,$data,$errors,$status);
	}

	public function manageServicesSequence(Request $request)
	{
		$errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;
        try
        {
        	if(!empty($request->services) && sizeof($request->services)>0)
        	{
        		foreach ($request->services as $key => $value)
        		{//dd($value['sequence_no'],$value['id']);
        			$ExaminationsModel = $this->ExaminationsModel->find($value['id']);
        			if(!empty($ExaminationsModel))
        			{
        				$ExaminationsModel->sequence_no	 =  $value['sequence_no'];
        				$ExaminationsModel->save();
        			}

        		}
        	}

            $status  = true;
            $message = __('api.EXAMINATION_UPDATE_SUCCESS');
            //$message = __('api.DATA_FOUND_SUCCESS');
            //$data  = $data;
            self::_createLog('manageServicesSequence',array(),'info');

        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('smartphoneAppsSetting',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

       return self::_sendResult($message,$data,$errors,$status);
	}

	public function getNotification(Request $request)
    {

    	$errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;

        try
        {
			$get_all_reminder = $this->PatientsHasServiceReminderModel
				->where('patient_id',$request->patient_id)
				->whereDate('reminder_date',date('Y-m-d'))
				->where('reminder_status','Set')
				->get();

			$data['examination_reminder_count'] = count($get_all_reminder);
			$data['examination_reminder'] = $get_all_reminder;


			// Get Examinations
			$app_exams = $this->AppointmentHasExaminationsModel
						->with(['assignedExamination'])
                        ->where('appointment_id',$collection->appointment_id)
                        ->get();

            $exam_exist  = 0;
            $exam_document_exist  = 0;
            $past_exist  = 0;
            $exams  = [];
            if(!empty($app_exams) && sizeof($app_exams)>0){
                $exam_exist  = 1;
                foreach ($app_exams as  $haskey=>$hasExamination)
                {
                    $exams[$haskey]['id'] = $hasExamination->assignedExamination->id;
                    $exams[$haskey]['name'] = $hasExamination->assignedExamination->name;
                    $exams[$haskey]['url'] = $hasExamination->assignedExamination->url;
                }
            }

			// End Examinations

            $data['examinations'] = $exams;

			$get_all_appoitment = $this->AppointmentHasNotificationModel
			->where('patient_id',$request->patient_id)
			->whereDate('notify_time',date('Y-m-d'))
			->where('status','0')
			->get();


			$data['appoitment_reminder_count'] = count($get_all_reminder);
			$data['appoitment_reminder'] = $get_all_appoitment;



			$status  = true;
            $message = __('api.EXAMINATION_UPDATE_SUCCESS');
            self::_createLog('Reminder',array($data),'info');
	    }
	    catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('smartphoneAppsSetting',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return self::_sendResult($message,$data,$errors,$status);

    }

    public function assignedOrdinationToPatient(Request $request)
    {
    	//dd($request->all());
    	// asset('assets/admin/images/default-image.png');
    	// $new_img_path = self::StorePath($item->img_path.'/');
    	$errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;
        $flag       = 0;
        $patient_id    = $request->patient_id;
        $ordination_id = $request->ordination_id;
        $device_id = $request->device_id;
        try
        {
        	$parentPatientId =  DB::table("patients")
                                ->where('id',$patient_id)
                                ->first();

            if(!empty($parentPatientId))
            {
            	$getDatabaseName = DB::connection('system')
            					   ->table("tenants")
                                   ->where('ordination_id',$ordination_id)
                                   ->first();

                $ordinationDetails = DB::connection('system')
            					   ->table("ordination")
            					   ->select('ordination.*','domains.fqdn')
            					   ->join('domains','domains.ordination_id','ordination.id')
                                   ->where('ordination.id',$ordination_id)
                                   ->first();

                if(!empty($ordinationDetails))
                {
                	$fqdn = "https://". $ordinationDetails->fqdn;
			                    	// $ordination_url = $gethostnames->fqdn;
			        $ordination_url = $fqdn;
			        $ordinationDetails->ordination_url = $ordination_url;
			        //$logo_path = self::StorePath($ordinationDetails->logo_path);
			        $ordinationDetails->logo_path = self::getFilePath($ordinationDetails->logo_path);
                }


                if(!empty($getDatabaseName))
                {
                	$database_name = $getDatabaseName->uuid;
                	$tenantPatientId =  DB::connection('system')
                						->table("patients")
                                    	->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
                                    	->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
                                    	->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
                                		->where('mobile_no', $parentPatientId->mobile_no)
                                    	->whereNULL('deleted_at')
                                    	->orderBy('created_at','DESC')
                                    	->first();

	                if(!empty($tenantPatientId))
	                {
	                	$flag = 1;
	                	$master_patient_id = $tenantPatientId->id;
	                	//dd($master_patient_id);
	                }
	                else
	                {
	                	$patientRec['old_id']      = $parentPatientId->old_id;
	                	$patientRec['pat_nr']      = $parentPatientId->pat_nr;
	                	$patientRec['family_name'] = $parentPatientId->family_name;
	                	$patientRec['first_name']  = $parentPatientId->first_name;
	                	$patientRec['email']       = $parentPatientId->email;
	                	$patientRec['country_code'] = $parentPatientId->country_code;
	                	$patientRec['mobile_no']   = $parentPatientId->mobile_no;
	                	$patientRec['ganymed_mobile_no'] = $parentPatientId->ganymed_mobile_no;
	                	$patientRec['birth_date'] = $parentPatientId->birth_date;
	                	$patientRec['age']        = $parentPatientId->age;
	                	$patientRec['password']   = $parentPatientId->password;
	                	// $patientRec['str_password'] = $parentPatientId->str_password;
	                	$patientRec['login_otp']  = $parentPatientId->login_otp;
	                	$patientRec['otp_created_at'] = $parentPatientId->otp_created_at;
	                	$patientRec['api_access_token'] = $parentPatientId->api_access_token;
	                	$patientRec['last_login_at'] = $parentPatientId->last_login_at;
	                	$patientRec['login_type'] = $parentPatientId->login_type;
	                	$patientRec['is_blocked'] = $parentPatientId->is_blocked;
	                	$patientRec['status']     = $parentPatientId->status;
	                	$patientRec['mobile_token'] = $parentPatientId->mobile_token;
	                	$patientRec['token']      = $parentPatientId->token;
	                	$patientRec['road']       = $parentPatientId->road;
	                	$patientRec['place']      = $parentPatientId->place;
	                	$patientRec['postal_code'] = $parentPatientId->postal_code;
	                	$patientRec['gender']     = $parentPatientId->gender;
	                	$patientRec['size']       = $parentPatientId->size;
	                	$patientRec['weight']     = $parentPatientId->weight;
	                	$patientRec['title']      = $parentPatientId->title;
	                	$patientRec['salutation'] = $parentPatientId->salutation;
	                	$patientRec['family_doctor'] = $parentPatientId->family_doctor;
	                	$patientRec['insurance_number'] = $parentPatientId->insurance_number;
	                	$patientRec['additional_insurance'] = $parentPatientId->additional_insurance;
	                	$patientRec['gdpr']       = $parentPatientId->gdpr;
	                	$patientRec['update_ganydb'] = $parentPatientId->update_ganydb;
	                	$patientRec['social_security_number'] = $parentPatientId->insurance_number;
	                	$patientRec['patient_status_flag'] = $parentPatientId->patient_status_flag;
	                	$patientRec['note_report_request'] = $parentPatientId->note_report_request;
	                	$patientRec['note_report_request_flag'] = $parentPatientId->note_report_request_flag;
	                	$patientRec['additional_insurance'] = $parentPatientId->street_no;
	                	$patientRec['additional_insurance'] = $parentPatientId->reminder_active;
	                	//dd($patientRec);
	                	$tenantPatientInsert =  DB::table($database_name.".patients")
                                    		    ->insertGetId($patientRec);
                        $flag = 1;
                        $master_patient_id = $tenantPatientInsert;
	                }
	                //dd($master_patient_id);
	                if($flag == 1)
	                {
	                	$checkOrdination =  DB::connection('system')
	            					   		->table("patients_has_ordination")
	                					   	->where('fk_patient_id',$patient_id)
	                					   	->where('fk_ordination_id',$ordination_id)
	                					   	->first();
	                	if(empty($checkOrdination))
	                	{
	                		$tmp['fk_patient_id']    = $patient_id;
	                		$tmp['fk_ordination_id'] = $ordination_id;
	                		$tmp['status']           = '1';
	                		$checkOrdination =  DB::connection('system')
	            					   			->table("patients_has_ordination")
	            					   			->insert($tmp);
	                	}
	                	if(!empty($request->device_id))
						{
							$this->PatientHasDeviceModel
				        		 ->where('device_id',$request->device_id)
				        		 ->where('device_type',$request->device_type)
				        		 ->delete();

							$checkAlreadyExist = $this->PatientHasDeviceModel
													 ->where('patient_id','=',$patient_id)
													 ->where('device_type','=',$request->device_type)
													 ->where('device_id','=',$request->device_id)
													 ->whereNull('deleted_at')
													 ->first();
							if(empty($checkAlreadyExist))
							{
								$device_data[] = array(
	                                    'patient_id'=> $patient_id,
	                                    'device_type'=> $request->device_type,
	                                    'device_id'=> $request->device_id,
	                                );
								$this->PatientHasDeviceModel->insert($device_data);
							}
						}
	                }
                }
                $data = $ordinationDetails;
                $message    = __('api.EXAMINATION_UPDATE_SUCCESS');
        		$status     = true;
            }

	    }
	    catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('AssignedOrdinationToPatient',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return self::_sendResult($message,$data,$errors,$status);
    }

    //Added by Swati 14-Jul-2022=================================
    public function assignedOrdinationToPatientPuremedOLD(Request $request)
    {
    	//dd($request->all());
    	// asset('assets/admin/images/default-image.png');
    	// $new_img_path = self::StorePath($item->img_path.'/');
    	$errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;
        $flag       = 0;
        $patient_id    = $request->patient_id;
        $ordination_id = $request->ordination_id;
        try
        {
        	$parentPatientId =  DB::table("patients")
                                ->where('id',$patient_id)
                                ->first();
            $master_patient_id=0;
            if(!empty($parentPatientId))
            {
            	$getDatabaseName = DB::connection('system')
            					   ->table("tenants")
                                   ->where('ordination_id',$ordination_id)
                                   ->first();

                $ordinationDetails = DB::connection('system')
            					   ->table("ordination")
            					   ->select('ordination.*','domains.fqdn')
            					   ->join('domains','domains.ordination_id','ordination.id')
                                   ->where('ordination.id',$ordination_id)
                                   ->first();

                if(!empty($ordinationDetails))
                {
                	$fqdn = "https://". $ordinationDetails->fqdn;
			                    	// $ordination_url = $gethostnames->fqdn;
			        $ordination_url = $fqdn;
			        $ordinationDetails->ordination_url = $ordination_url;
			        //$logo_path = self::StorePath($ordinationDetails->logo_path);
			        $ordinationDetails->logo_path = self::getFilePath($ordinationDetails->logo_path);
                }

                if(!empty($getDatabaseName))
                {
                	$database_name = $getDatabaseName->uuid;

                	$tenantPatientId =  DB::table($database_name.".patients")
                                    	->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
                                    	->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
                                    	->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
                                		->where('mobile_no', $parentPatientId->mobile_no)
                                    	->whereNULL('deleted_at')
                                    	->orderBy('created_at','DESC')
                                    	->first();

	                if(!empty($tenantPatientId))
	                {
	                	$flag = 1;
	                	$master_patient_id = $tenantPatientId->id;
	                	$patientRec['api_access_token'] = $parentPatientId->api_access_token;
	                	$patientRec['mobile_token'] = $parentPatientId->mobile_token;
	                	$patientRec['token']      = $parentPatientId->token;
	                	$patientRec['updated_at'] = date("Y-m-d H:i:s");
						DB::table($database_name.".patients")->where('id',$master_patient_id)->update($patientRec);
	                	//dd($master_patient_id);
	                }
	                else
	                {
	                	$patientRec['old_id']      = $parentPatientId->old_id;
	                	$patientRec['pat_nr']      = $parentPatientId->pat_nr;
	                	$patientRec['family_name'] = $parentPatientId->family_name;
	                	$patientRec['first_name']  = $parentPatientId->first_name;
	                	$patientRec['email']       = $parentPatientId->email;
	                	$patientRec['country_code'] = $parentPatientId->country_code;
	                	$patientRec['mobile_no']   = $parentPatientId->mobile_no;
	                	$patientRec['ganymed_mobile_no'] = $parentPatientId->ganymed_mobile_no;
	                	$patientRec['birth_date'] = $parentPatientId->birth_date;
	                	$patientRec['age']        = $parentPatientId->age;
	                	$patientRec['password']   = $parentPatientId->password;
	                	// $patientRec['str_password'] = $parentPatientId->str_password;
	                	$patientRec['login_otp']  = $parentPatientId->login_otp;
	                	$patientRec['otp_created_at'] = $parentPatientId->otp_created_at;
	                	$patientRec['api_access_token'] = $parentPatientId->api_access_token;
	                	$patientRec['last_login_at'] = $parentPatientId->last_login_at;
	                	$patientRec['login_type'] = $parentPatientId->login_type;
	                	$patientRec['is_blocked'] = $parentPatientId->is_blocked;
	                	$patientRec['status']     = $parentPatientId->status;
	                	$patientRec['mobile_token'] = $parentPatientId->mobile_token;
	                	$patientRec['token']      = $parentPatientId->token;
	                	$patientRec['road']       = $parentPatientId->road;
	                	$patientRec['place']      = $parentPatientId->place;
	                	$patientRec['postal_code'] = $parentPatientId->postal_code;
	                	$patientRec['gender']     = $parentPatientId->gender;
	                	$patientRec['size']       = $parentPatientId->size;
	                	$patientRec['weight']     = $parentPatientId->weight;
	                	$patientRec['title']      = $parentPatientId->title;
	                	$patientRec['salutation'] = $parentPatientId->salutation;
	                	$patientRec['family_doctor'] = $parentPatientId->family_doctor;
	                	$patientRec['insurance_number'] = $parentPatientId->insurance_number;
	                	$patientRec['additional_insurance'] = $parentPatientId->additional_insurance;
	                	$patientRec['gdpr']       = $parentPatientId->gdpr;
	                	$patientRec['update_ganydb'] = $parentPatientId->update_ganydb;
	                	$patientRec['social_security_number'] = $parentPatientId->insurance_number;
	                	$patientRec['patient_status_flag'] = $parentPatientId->patient_status_flag;
	                	$patientRec['note_report_request'] = $parentPatientId->note_report_request;
	                	$patientRec['note_report_request_flag'] = $parentPatientId->note_report_request_flag;
	                	$patientRec['additional_insurance'] = $parentPatientId->street_no;
	                	$patientRec['additional_insurance'] = $parentPatientId->reminder_active;
	                	$patientRec['created_at'] = date("Y-m-d H:i:s");
	                	//dd($patientRec);
	                	$tenantPatientInsert =  DB::table($database_name.".patients")
                                    		    ->insertGetId($patientRec);
                        $flag = 1;
                        $master_patient_id = $tenantPatientInsert;
	                }
	                //dd($master_patient_id);
	                if($flag == 1)
	                {
	                	if(!empty($request->device_id))
						{
							$this->PatientHasDeviceModel
				        		 ->where('device_id',$request->device_id)
				        		 ->where('device_type',$request->device_type)
				        		 ->delete();

							$checkAlreadyExist = $this->PatientHasDeviceModel
													 ->where('patient_id','=',$patient_id)
													 ->where('device_type','=',$request->device_type)
													 ->where('device_id','=',$request->device_id)
													 ->whereNull('deleted_at')
													 ->first();
							if(empty($checkAlreadyExist))
							{
								$device_data[] = array(
	                                    'patient_id'=> $patient_id,
	                                    'device_type'=> $request->device_type,
	                                    'device_id'=> $request->device_id,
	                                );
								$this->PatientHasDeviceModel->insert($device_data);
							}
						}
	                	$checkOrdination =  DB::connection('system')
	            					   		->table("patients_has_ordination")
	                					   	->where('fk_patient_id',$master_patient_id)
	                					   	->where('fk_ordination_id',$ordination_id)
	                					   	->first();
	                	if(empty($checkOrdination))
	                	{
	                		$tmp['fk_patient_id']    = $master_patient_id;
	                		$tmp['fk_ordination_id'] = $ordination_id;
	                		$tmp['status']           = '1';
	                		$checkOrdination =  DB::connection('system')
	            					   			->table("patients_has_ordination")
	            					   			->insert($tmp);
	                	}
	                }
                }
                $parentPatientDetails =  DB::table($database_name.".patients")
                				->select("patients.*","patients.id as ordinationID")
                                ->where('id',$master_patient_id)
                                ->first();
                $data['ordinationDetails'] = $ordinationDetails;
				$data['patientID'] = $parentPatientDetails;
                $message    = __('api.EXAMINATION_UPDATE_SUCCESS');
        		$status     = true;
            }

	    }
	    catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('assignedOrdinationToPatientPuremed',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return self::_sendResult($message,$data,$errors,$status);
    }

     public function assignedOrdinationToPatientPuremed(Request $request)
    {

    	Log::info("in ...assignedOrdinationToPatientPuremed==>");

    	//dd($request->all());
    	// asset('assets/admin/images/default-image.png');
    	// $new_img_path = self::StorePath($item->img_path.'/');
    	$errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;
        $flag       = 0;
        $newPatient = 0;
        $patient_id    = $request->patient_id;
        $ordination_id = $request->ordination_id;
        try
        {
        	$parentPatientId =  DB::table("patients")
                                ->where('id',$patient_id)
                                ->first();
            $master_patient_id=0;
            if(!empty($parentPatientId))
            {
            	$getDatabaseName = DB::connection('system')
            					   ->table("tenants")
                                   ->where('ordination_id',$ordination_id)
                                   ->first();

                $ordinationDetails = DB::connection('system')
            					   ->table("ordination")
            					   ->select('ordination.*','domains.fqdn')
            					   ->join('domains','domains.ordination_id','ordination.id')
                                   ->where('ordination.id',$ordination_id)
                                   ->first();

                  Log::info("in ...ordinationDetails==>");
 			     // Log::info($ordinationDetails);

                if(!empty($ordinationDetails))
                {
                	$fqdn = "https://". $ordinationDetails->fqdn;
			                    	// $ordination_url = $gethostnames->fqdn;
			        $ordination_url = $fqdn;
			        $ordinationDetails->ordination_url = $ordination_url;
			        //$logo_path = self::StorePath($ordinationDetails->logo_path);
			        $ordinationDetails->logo_path = self::getFilePath($ordinationDetails->logo_path);
                }
				//----- Add age validation on 7th aug 2025 start-----//
				if (!empty($getDatabaseName)) {

					// Get database name for this ordination
					$ordinationDB = $getDatabaseName->uuid;

					if ($ordinationDB) {


						//Start check patient is active or not added on 3-feb-26
			            $ordianationPatientId =  
			                     DB::table($ordinationDB.".patients")
			                                      ->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
			                                    ->where('mobile_no', $parentPatientId->mobile_no)
			                                      ->whereNULL('deleted_at')
			                                      ->orderBy('created_at','DESC')
			                                      ->first();

	                    if(!empty($ordianationPatientId))
	                    {
	                      $patientStatus = $ordianationPatientId->status;
	                      if(isset($patientStatus) && $patientStatus==0)
	                      {
		                        $errors[] = [
				                    "error" => __('api.ERR_PATIENT_BLOCK_ORDINATION'),
				                ];
				                $message = __('api.ERR_PATIENT_BLOCK_ORDINATION');
				                $status = false;
				                return self::_sendResult($message, $data, $errors, $status);
	                      }
	                    }//if ordianationPatientId
			            //End check patient is active or not added on 3-feb-26


						// Get minimum age from settings table for this ordination
						$minAgeSetting = DB::table($ordinationDB.".settings")
							->where('setting_key','=','MINIMUM_AGE')
							->whereStatus(1)
							->first(['setting_value']);
							if (!empty($minAgeSetting)) 
							{
								$birthDate = Carbon::parse($parentPatientId->birth_date);
								$minAgeYears = is_numeric($minAgeSetting->setting_value) ? (int)$minAgeSetting->setting_value : 0;
								Log::info('MINIMUM_AGE setting_value: ' . var_export($minAgeSetting->setting_value, true) . ', type: ' . gettype($minAgeSetting->setting_value));
								Log::info('minAgeYears (int): ' . var_export($minAgeYears, true) . ', type: ' . gettype($minAgeYears));
								$today = Carbon::now();
								$minAgeDate = $birthDate->copy()->addYears($minAgeYears);

								if ($today->lt($minAgeDate)) 
								{
									$errors[] = [
										"birth_date" => __('api.ERR_MINIMUM_AGE_ORDINATION'),
									];
									$message = __('api.ERR_MINIMUM_AGE_ORDINATION');
									$status = false;
									return self::_sendResult($message, $data, $errors, $status);
								}
							}
						// if (!empty($minAgeSetting)) 
						// {
						// 	$birthDate = Carbon::parse($parentPatientId->birth_date);
						// 	// $minAgeYears = (int)$minAgeSetting->setting_value;
						// 	$minAgeYears = is_numeric($minAgeSetting->setting_value) ? (int)$minAgeSetting->setting_value : 0;
						// 	Log::info('MINIMUM_AGE setting_value: ' . var_export($minAgeSetting->setting_value, true) . ', type: ' . gettype($minAgeSetting->setting_value));
						// 	Log::info('minAgeYears (int): ' . var_export($minAgeYears, true) . ', type: ' . gettype($minAgeYears));
						// 	$today = Carbon::now();
						// 	// $minAgeDate = $birthDate->copy()->addYears($minAgeYears);
						// 	$safeMinAgeYears = is_numeric($minAgeYears) ? (int)$minAgeYears : 0;
						// 	$minAgeDate = $birthDate->copy()->addYears($safeMinAgeYears);
						// 	if ($today->lt($minAgeDate)) 
			            //     {
				        //  	 	$errors[] = 
						// 		[
						// 			"birth_date" => __('api.ERR_MINIMUM_AGE_ORDINATION'), 
					    //       	];

					    //       	$message = __('api.ERR_MINIMUM_AGE_ORDINATION');
					    //       	$status = false;
					    //       	return self::_sendResult($message,$data,$errors,$status);
					    //     }
						// }
					}
				}
				//----- Add age validation on 7th aug 2025 End-----//
                if(!empty($getDatabaseName))
                {
                	 Log::info("in ...getDatabaseName==>");

                	$database_name = $getDatabaseName->uuid;

                	 //commented first and family name on 15-dec-23
                	$tenantPatientId =  DB::table($database_name.".patients")
                                    	// ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
                                    	// ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
                                    	->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
                                		->where('mobile_no', $parentPatientId->mobile_no)
                                    	->whereNULL('deleted_at')
                                    	->orderBy('created_at','DESC')
                                    	->first();

	                if(!empty($tenantPatientId))
	                {
	                	 Log::info("in ...tenantPatientId==>");

	                	$flag = 1;
	                	$master_patient_id = $tenantPatientId->id;
	                	$patientRec['api_access_token'] = $parentPatientId->api_access_token;
	                	$patientRec['mobile_token'] = $parentPatientId->mobile_token;
	                	$patientRec['token']      = $parentPatientId->token;
	                	$patientRec['updated_at'] = date("Y-m-d H:i:s");
						DB::table($database_name.".patients")->where('id',$master_patient_id)->update($patientRec);
	                	//dd($master_patient_id);
	                }
	                else
	                {
	                     Log::info("in ..else of .tenantPatientId==>");

	                	$patientRec['old_id']      = $parentPatientId->old_id;
	                	$patientRec['pat_nr']      = $parentPatientId->pat_nr;
	                	$patientRec['family_name'] = $parentPatientId->family_name;
	                	$patientRec['first_name']  = $parentPatientId->first_name;
	                	$patientRec['email']       = $parentPatientId->email;
	                	$patientRec['country_code'] = $parentPatientId->country_code;
	                	$patientRec['mobile_no']   = $parentPatientId->mobile_no;
	                	$patientRec['ganymed_mobile_no'] = $parentPatientId->ganymed_mobile_no;
	                	$patientRec['birth_date'] = $parentPatientId->birth_date;
	                	$patientRec['age']        = $parentPatientId->age;
	                	$patientRec['password']   = $parentPatientId->password;
	                	// $patientRec['str_password'] = $parentPatientId->str_password;
	                	$patientRec['login_otp']  = $parentPatientId->login_otp;
	                	$patientRec['otp_created_at'] = $parentPatientId->otp_created_at;
	                	$patientRec['api_access_token'] = $parentPatientId->api_access_token;
	                	$patientRec['last_login_at'] = $parentPatientId->last_login_at;
	                	$patientRec['login_type'] = $parentPatientId->login_type;
	                	$patientRec['is_blocked'] = $parentPatientId->is_blocked;
	                	$patientRec['status']     = $parentPatientId->status;
	                	$patientRec['mobile_token'] = $parentPatientId->mobile_token;
	                	$patientRec['token']      = $parentPatientId->token;
	                	$patientRec['road']       = $parentPatientId->road;
	                	$patientRec['place']      = $parentPatientId->place;
	                	$patientRec['postal_code'] = $parentPatientId->postal_code;
	                	$patientRec['gender']     = $parentPatientId->gender;
	                	$patientRec['size']       = $parentPatientId->size;
	                	$patientRec['weight']     = $parentPatientId->weight;
	                	$patientRec['title']      = $parentPatientId->title;
	                	$patientRec['salutation'] = $parentPatientId->salutation;
	                	$patientRec['family_doctor'] = $parentPatientId->family_doctor;
	                	$patientRec['insurance_number'] = $parentPatientId->insurance_number;
	                	$patientRec['additional_insurance'] = $parentPatientId->additional_insurance;
	                	$patientRec['gdpr']       = $parentPatientId->gdpr;
	                	$patientRec['update_ganydb'] = $parentPatientId->update_ganydb;
	                	$patientRec['social_security_number'] = $parentPatientId->insurance_number;
	                	$patientRec['patient_status_flag'] = $parentPatientId->patient_status_flag;
	                	$patientRec['note_report_request'] = $parentPatientId->note_report_request;
	                	$patientRec['note_report_request_flag'] = $parentPatientId->note_report_request_flag;
	                	$patientRec['additional_insurance'] = $parentPatientId->street_no;
	                	$patientRec['additional_insurance'] = $parentPatientId->reminder_active;
	                	$patientRec['created_at'] = date("Y-m-d H:i:s");
	                	$patientRec['country'] = $parentPatientId->country; //added on 22-nov-24


	                	//dd($patientRec);
	                	$tenantPatientInsert =  DB::table($database_name.".patients")
                                    		    ->insertGetId($patientRec);
                        $flag = 1;
                        $newPatient = 1;
                        $master_patient_id = $tenantPatientInsert;

                         Log::info("in ..newPatient==>");
                        // Log::info($newPatient);
                         Log::info("in ..after patient insert to .db in puregyn==>");

	                }
	                //dd($master_patient_id);
	                $parentPatientDetails =  DB::table($database_name.".patients")
                				->select("patients.*","patients.id as ordinationID")
                                ->where('id',$master_patient_id)
                                ->first();


                     Log::info("after ..parentPatientDetails==>");


                    if(!empty($request->device_id) && $parentPatientDetails)
					{
						// DB::table($database_name.".patient_has_device")
						// 	  ->where('device_id',$request->device_id)
			   // 			      ->where('device_type',$request->device_type)
			   // 				  ->delete();

						/***********13-aug-24*****************************/
							DB::table($database_name.".patient_has_device")
							     ->where('patient_id','=',$parentPatientDetails->ordinationID)
				        		 ->where('device_type',$request->device_type)
				        		 ->delete();
				       /*************13-aug-24****************************/


			   			$checkAlreadyExist =  DB::table($database_name.".patient_has_device")
									 ->where('patient_id','=',$parentPatientDetails->ordinationID)
									 ->where('device_type','=',$request->device_type)
									 ->where('device_id','=',$request->device_id)
									 ->whereNull('deleted_at')
									 ->first();
						if(empty($checkAlreadyExist))
						{
							$device_data[] = array(
                                    'patient_id'=> $parentPatientDetails->ordinationID,
                                    'device_type'=> $request->device_type,
                                    'device_id'=> $request->device_id,
                                );
							DB::table($database_name.".patient_has_device")->insert($device_data);
						}
					}

	                if($flag == 1)
	                {
	                	Log::info("in ..flag==>");
	                	$checkOrdination =  DB::connection('system')
	            					   		->table("patients_has_ordination")
	                					   	->where('fk_patient_id',$master_patient_id)
	                					   	->where('fk_ordination_id',$ordination_id)
	                					   	->first();
	                	if(empty($checkOrdination))
	                	{
	                		$tmp['fk_patient_id']    = $master_patient_id;
	                		$tmp['fk_ordination_id'] = $ordination_id;
	                		$tmp['status']           = '1';
	                		$checkOrdination =  DB::connection('system')
	            					   			->table("patients_has_ordination")
	            					   			->insert($tmp);
	                	}
	                }
                }

                $data['ordinationDetails'] = $ordinationDetails;
				$data['patientID'] = $parentPatientDetails;
				$data['newPatient'] = $newPatient;
                $message    = __('api.EXAMINATION_UPDATE_SUCCESS');
        		$status     = true;
            }

	    }
	    catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('assignedOrdinationToPatientPuremed',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return self::_sendResult($message,$data,$errors,$status);
    }

    public function assignedOrdinationReminders(Request $request){
		$errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;
        $flag       = 0;
        $patient_id    = $request->patient_id;
        try
        {
        	$parentPatientId =  DB::table("patients")
                                ->where('id',$patient_id)
                                ->first();
            if(!empty($parentPatientId))
            {
            	$setReminders = app('App\Http\Controllers\Admin\DashboardController')->checkPatientAgeReminder($patient_id);
                $data['patientID'] = $parentPatientDetails;
                $message    = __('api.EXAMINATION_UPDATE_SUCCESS');
        		$status     = true;
            }
	    }
	    catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('assignedOrdinationReminders',$errors,'error');
        }

        return self::_sendResult($message,$data,$errors,$status);
	}

    public function getServiceAppointmentType(Request $request)
    {
    	$errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;
        $flag       = 0;

        try
        {
        	$examinations = $request->exam;

            $data = $ordinationDetails;
            $message    = __('api.APPOITMENT_TYPE_DATA_FOUND');
    		$status     = true;
	    }
	    catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(),
              ];
            self::_createLog('AssignedOrdinationToPatient',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','get examinations','Delete');
        }

        return self::_sendResult($message,$data,$errors,$status);
    }

    public function getOrdinationQrcode(Request $request)
    {
    	log::info('in getOrdinationQrcode');

		$errors = [];
		$data = [];
		$is_available = 0;
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$ordination_url = null;
		$mapsApiKey    = Config('mapsApiKey');
		$getOrdination = [];
		try
		{
    		$data['is_available']    = 1;
    		// dd($patient_id);
    		$getOrdination=DB::connection('system')
    					   ->table('ordination')
                           ->where('status', 1)
                           ->whereNull('deleted_at')
                           ->get();//LIMIT 10

            log::info('in getOrdination.');
			log::info($getOrdination);

			if(sizeof($getOrdination)>0)
			{
				log::info('in sizeof getOrdination.');

				$getOrdination = collect($getOrdination);
				  	$getOrdination = $getOrdination->map(function($item)
		            {
		            	$ordination_url = null;
		            	if (!empty($item->id) )
		                {
		                	$logo_path = null;
		                	$logo_path = url('/storage/app'.$item->logo_path);
		                	$item->logo_path = self::getFilePath($item->logo_path);
		                	//get Host URL
		                	$gethostnames = DB::connection('system')
		                    				->table("domains")
		                    				->where('ordination_id',$item->id)
		                    				->whereNull('deleted_at')
		                    				->first(['fqdn']);
		                    if(!empty($gethostnames))
		                    {
		                    	$fqdn = "https://". $gethostnames->fqdn;
		                    	// $ordination_url = $gethostnames->fqdn;
		                    	$ordination_url = $fqdn;
		                    	$item->ordination_url = $ordination_url;
		                    }
		                    else
		                    {
		                    	$item->ordination_url = null;
		                    }
		                    //End
		                    //GET Specialist
		                    $getSpecilistid = DB::connection('system')
		                    				->table("ordination_has_specialist")
		                    				->where('ordination_id',$item->id)
		                    				->whereNull('deleted_at')
		                    				->get(['specialist_id']);
		                    //dd($getSpecilist);
		                    $getSpecilistid = $getSpecilistid->map(function($spitem)
		            		{
		            			//dd($spitem->specialist_id);
		            			$specialistDetails = DB::connection('system')
		                    						->table("specialist")
		                    						->where('id',$spitem->specialist_id)
		                    						->whereNull('deleted_at')
		                    						->first(['name']);
		                    	$spitem->specialist_name = $specialistDetails->name;
		                    	return $spitem;
		            		});
		            		//dd($getSpecilistid);
		                 	$item->specialist = $getSpecilistid;
		                 	//$getSpecilistid = [];
		                    return $item;
		                }
		            });
					$data['ordination_data'] = $getOrdination;
				$data['ordination_data'] = $getOrdination;
				$data['is_available'] = 1;
				$message   =  __('api.AUTH_ORDINATION_DATA_SUCCESS');
				$status    = true;
			}
			else
			{
				log::info('else sizeof getOrdination.');

				$data['is_available'] = 1;
				$data['ordination_data'] = [];
	    		$message   = __('api.AUTH_ORDINATION_DATA_NOT_EXIST');
				$status    = false;
			}

		}
		catch (Exception $e)
		{


    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];

			log::info('in catch block..');
			log::info($e->getMessage());

			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}
    	//dd($data);
		return self::_sendResult($message,$data,$errors,$status);
    }
    public function ordinationLoginQrcode(Request $request)
    {
    	//dd($request->all());
		$errors = [];
		$data = [];
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$email         = $request->email;
		$password      = $request->password;
		$ordination_id = $request->ordination_id;
		try
		{
			$getWebsites = DB::connection('system')
	    					   ->table('tenants')
	    					   ->where('ordination_id',$ordination_id)
	    					   ->first(['uuid']);
	    		//dd($getWebsites);
	    	$database_name = $getWebsites->uuid;
	    	$getuserDetails =  DB::table($database_name.".users")
	    						->where('email',$email)
    							->first();
    		if(!empty($getuserDetails))
    		{

    			if (Hash::check($request->password, $getuserDetails->password))
				{
					$getOrdination=DB::connection('system')
	    					   ->table('ordination')
	    					   ->where('id',$ordination_id)
	                           ->where('status', 1)
	                           ->get();//LIMIT 10

					if(sizeof($getOrdination)>0)
					{
						$data['ordination_data'] = $getOrdination;
					}
					else
					{
						$data['ordination_data'] = [];
					}

					$message   =  __('api.AUTH_ORDINATION_LOGIN_SUCCESS');
					$status    = true;
				}
				else
				{
		    		$message   = __('api.ERR_SOMETHING_WRONG');
					$status    = false;
				}
    		}
    		else
			{
	    		$message   = __('api.ERR_SOMETHING_WRONG');
				$status    = false;
			}

		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}
		return self::_sendResult($message,$data,$errors,$status);
    }

    public function isUpdated(Request $request)
    {
    	$errors = [];
		$data = [];
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		try
		{
			$data['isUpdated'] = 1;
			$message   =  __('api.IS_UPDATED_FLAG_SEND_SUCCESSFULLY');
			$status    = true;
		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}
		return self::_sendResult($message,$data,$errors,$status);
    }

    public function updatePassword(Request $request)
    {
    	$message   = __('api.ERR_SOMETHING_WRONG');
		$status    = false;
		$errors = [];
		$data = [];
		$str_password = $request->password;
		$patient_id   = $request->patient_id;
		$password = Hash::make($request->password);
		try
		{
			$patientRec['password']      = $password;
			// $patientRec['str_password']  = $str_password;

			$patient = DB::table('patients')
	    					->where('id',$patient_id)
	    					->update(['password'=>$password,'is_updated'=>'1']);

	   		// ->update(['password'=>$password,'str_password'=>$str_password]);
	    	if(!empty($patient))
	    	{
	    		$parentPatientId = $this->PatientsModel->find($patient_id);

	    		$master_patient  = DB::connection('system')
    					   			->table('patients')
    					  			->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
                                	->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
                                	->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
                                	->where('mobile_no', $parentPatientId->mobile_no)
                                	->whereNULL('deleted_at')
                                	->first();

                if(!empty($master_patient))
                {

                	$masterUPpatient = DB::connection('system')
                					->table('patients')
	    							->where('id',$master_patient->id)
	    							->update(['password'=>$password]);

	    			if($masterUPpatient)
	    			{
	    				$collection = $parentPatientId;

						$collection->social_security_number = $collection->insurance_number;

						$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
						$log_id = $collection->id;
						$data[]  = $collection->only(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','message','social_security_number']);
						self::_createLog('SignupSendOtp',$log_id,'info');
						$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
						$status    = true;

	    			}
	    			else
	    			{
	    				$message   = __('api.ERR_SOMETHING_WRONG');
						$status    = false;
	    			}
                }

	    	}


		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}

		return self::_sendResult($message,$data,$errors,$status);
    }
    //Added by Swati 15-Jul-2022========================================
    public function updatePasswordPuremed(Request $request)
    {
    	$message   = __('api.ERR_SOMETHING_WRONG');
		$status    = false;
		$errors = [];
		$data = [];
		$str_password = $request->password;
		$patient_id   = $request->patient_id;
		$password = Hash::make($request->password);
		try
		{
				$masterPatientupdate = DB::table('patients')
	    					->where('id',$patient_id)
	    					->update(['password'=>$password,'is_updated'=>'1','updated_at'=>date("Y-m-d H:i:s")]);
				if(!empty($masterPatientupdate)){
					$getOrdination=DB::connection('system')->table('patients_has_ordination')->where('fk_patient_id',$patient_id)->get();
					if(!empty($getOrdination)){
						foreach($getOrdination as $ordination){
							$getWebsites = DB::connection('system')
									->table('tenants')
									->where('ordination_id',$ordination->fk_ordination_id)
									->first(['uuid']);
							//dd($getWebsites);
							$database_name = $getWebsites->uuid;
							$patient = $this->PatientsModel->find($patient_id);

							//commented below qry on 15-dec-23 for family name and first name

							$ordination_patient  = DB::table($database_name.'.patients')
												// ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($patient->family_name))
												// ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($patient->first_name))
												->whereDate('birth_date', date('Y-m-d',strtotime($patient->birth_date)))
												->where('mobile_no', $patient->mobile_no)
												->whereNULL('deleted_at')
												->first();
							//log::info($ordination_patient->id);
							if(!empty($ordination_patient)){
								$Patientupdate = DB::table($database_name.'.patients')
											->where('id',$ordination_patient->id)
											->update(['password'=>$password,'is_updated'=>'1','updated_at'=>date("Y-m-d H:i:s")]);
							}
						}
					}
					$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
					$log_id = $patient_id;
					$collection = DB::table('patients')->select(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','social_security_number'])->where('id',$patient_id)->first();
					$data[]  = $collection;
					self::_createLog('SignupSendOtp',$log_id,'info');
					$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
					$status    = true;
				}
				else{
					$message   = __('api.ERR_SOMETHING_WRONG');
					$status    = false;
				}

		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}

		return self::_sendResult($message,$data,$errors,$status);
    }
    //Added by Swati 15-Jul-2022========================================
	public function updatePasswordPuremedOrdination(Request $request)
    {
    	$message   = __('api.ERR_SOMETHING_WRONG');
		$status    = false;
		$errors = [];
		$data = [];
		$str_password = $request->password;
		$patient_id   = $request->patient_id;
		$password = Hash::make($request->password);
		try
		{
				$patientupdate = DB::table('patients')
							->where('id',$patient_id)
							->update(['password'=>$password,'is_updated'=>'1','updated_at'=>date("Y-m-d H:i:s")]);

				$parentPatientId = $this->PatientsModel->find($patient_id);

				//commented family name and first name on 15-dec-23

				$getMasterData  = DB::connection('system')
								->table('patients')
								// ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
								// ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
								->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
								->where('mobile_no', $parentPatientId->mobile_no)
								->whereNULL('deleted_at')
								->first();
				if(!empty($getMasterData)){
					$masterPatientupdate =  DB::connection('system')->table('patients')
				    					->where('id',$getMasterData->id)
				    					->update(['password'=>$password,'is_updated'=>'1','updated_at'=>date("Y-m-d H:i:s")]);
					$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
					$log_id = $patient_id;
					$collection = DB::table('patients')->select(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','social_security_number'])->where('id',$patient_id)->first();
					$data[]  = $collection;
					self::_createLog('SignupSendOtp',$log_id,'info');
					$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
					$status    = true;
				}
				else{
					$message   = __('api.ERR_SOMETHING_WRONG');
					$status    = false;
				}

		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}

		return self::_sendResult($message,$data,$errors,$status);
    }

    public function changePassword(Request $request)
    {
    	$message   = __('api.ERR_SOMETHING_WRONG');
		$status    = false;
		$errors = [];
		$data = [];
		$str_password = $request->password;
		$old_password = $request->old_password;
		$patient_id   = $request->patient_id;
		$password = Hash::make($request->password);
		//dd($str_password,$password);
		try
		{
			$patient = DB::table('patients')
	    				->where('id',$patient_id)
	    				->first();

			if (Hash::check($old_password, $patient->password))
			{
				$patient = DB::table('patients')
	    					->where('id',$patient_id)
	    					->update(['password'=>$password,'is_updated'=>'1']);

		    	if(!empty($patient))
		    	{
		    		$parentPatientId = $this->PatientsModel->find($patient_id);

		    		$master_patient  = DB::connection('system')
	    					   			->table('patients')
	    					  			->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
	                                	->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
	                                	->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
	                                	->where('mobile_no', $parentPatientId->mobile_no)
	                                	->whereNULL('deleted_at')
	                                	->first();
	                if(!empty($master_patient))
	                {

	                	$masterUPpatient = DB::connection('system')
	                					->table('patients')
		    							->where('id',$master_patient->id)
		    							->update(['password'=>$password]);
		    			if($masterUPpatient)
		    			{
		    				$collection = $parentPatientId;

							$collection->social_security_number = $collection->insurance_number;

							$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
							$log_id = $collection->id;
							$data[]  = $collection->only(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','message','social_security_number']);
							self::_createLog('SignupSendOtp',$log_id,'info');
							$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
							$status    = true;

		    			}
		    			else
		    			{
		    				$message   = __('api.ERR_SOMETHING_WRONG');
							$status    = false;
		    			}
	                }
	                else{
	                	//this else added by Swati 05 Jul 2022
	    				$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
						$log_id = $patient_id;
						$collection = DB::table('patients')->select(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','social_security_number'])->where('id',$patient_id)->first();
						$data[]  = $collection;
						self::_createLog('SignupSendOtp',$log_id,'info');
						$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
						$status    = true;
		    		}
		    	}
			}
			else
			{
				$message   = __('api.OLD_PASSWORD_IS_NOT_MATCH');
				$status    = false;
			}
		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}

		return self::_sendResult($message,$data,$errors,$status);
    }

    //Added By Swati 15-Jul-2022===========================
    public function changePasswordPuremed(Request $request)
    {
    	$message   = __('api.ERR_SOMETHING_WRONG');
		$status    = false;
		$errors = [];
		$data = [];
		$str_password = $request->password;
		$old_password = $request->old_password;
		$patient_id   = $request->patient_id;
		$password = Hash::make($request->password);
		try
		{
			log::info('innnnnnnnnnnnnnnnnnn');

			log::info($request->old_password);

			log::info($patient_id);

			// dump($request->old_password);

			// dump($patient_id);

			$patient = DB::table('patients')
	    				->where('id',$patient_id)
	    				->first();

			 // dump($patient);
			 // dump($old_password);

	    			//log::info($patient);

      	    		//log::info($patient->password);

			if (Hash::check($old_password, $patient->password))
			{
				$masterPatientupdate = DB::table('patients')
	    					->where('id',$patient_id)
	    					->update(['password'=>$password,'is_updated'=>'1']);
				if(!empty($masterPatientupdate)){
					$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
					$log_id = $patient_id;
					$collection = DB::table('patients')->select(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','social_security_number'])->where('id',$patient_id)->first();
					$data[]  = $collection;
					self::_createLog('SignupSendOtp',$log_id,'info');
					$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
					$status    = true;
				}
				else{
					$message   = __('api.ERR_SOMETHING_WRONG');
					$status    = false;
				}
				$getOrdination=DB::table('patients_has_ordination')->where('fk_patient_id',$patient_id)->get();
				if(!empty($getOrdination) && !empty($masterPatientupdate)){
					foreach($getOrdination as $ordination){
						$getWebsites = DB::connection('system')
	    					   ->table('tenants')
	    					   ->where('ordination_id',$ordination->fk_ordination_id)
	    					   ->first(['uuid']);
	    				//dd($getWebsites);
	    				$database_name = $getWebsites->uuid;

	    				 //Commented first name and family name on 15-dec-23

						$ordination_patient  = DB::table($database_name.'.patients')
											// ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($patient->family_name))
											// ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($patient->first_name))
											->whereDate('birth_date', date('Y-m-d',strtotime($patient->birth_date)))
											->where('mobile_no', $patient->mobile_no)
											->whereNULL('deleted_at')
											->first();
						//log::info($ordination_patient->id);
						if(!empty($ordination_patient)){
							$Patientupdate = DB::table($database_name.'.patients')
										->where('id',$ordination_patient->id)
										->update(['password'=>$password,'is_updated'=>'1','updated_at'=>date("Y-m-d H:i:s")]);
						}
					}
					$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
					$log_id = $patient_id;
					$collection = DB::table('patients')->select(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','social_security_number'])->where('id',$patient_id)->first();

					//$data[]  = $collection;  //commented on 28-nov-24
					if (!in_array($collection, $data)) {
					    $data[] = $collection;
					}//added on 28-nov-24


					self::_createLog('SignupSendOtp',$log_id,'info');
					$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
					$status    = true;
				}
				else{


					$message   = __('api.ERR_SOMETHING_WRONG');
					$status    = false;
				}

			}
			else
			{
					// log::info('in else part ');
				$message   = __('api.OLD_PASSWORD_IS_NOT_MATCH');
				$status    = false;
			}
		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}

		return self::_sendResult($message,$data,$errors,$status);
    }

    public function passwordSendOtp_renamed_on_18_dec_23_orignal(Request $request)
    {
		$errors = [];
		$data = [];
		$message = __('api.AUTH_INVALID_USER');
		$status = false;
		$social_security_number = null;
		$flag = 0;

    	$inputdata = $request->all();

    	try {
    		$validator = Validator::make($inputdata,[
						  'first_name' 	=> 'required',
						  'family_name' 	=> 'required',
						  'country_code' => 'required',
						  'mobile_no' 	=> 'required|regex:/^[0-9][0-9]*$/',
						  // 'password' 	=> 'required',
						],
						[
						  'first_name.required'	=> __('api.AUTH_FIRSTNAME_REQ'),
						  'family_name.required' => __('api.AUTH_FAMILYNAME_REQ'),
						  'country_code' => __('api.AUTH_COUNTRY_CODE_REQ'),
						  'mobile_no.required' 	=> __('api.AUTH_MOBILENO_REQ'),

						  'mobile_no.regex'       =>  __('api.AUTH_MOBILENO_NOTSTARTWITHZERO'),
						  // 'password' 	          => __('api.AUTH_USER_PAAWORD_REQUEIED'),
						]
						);

			if ($validator->fails()) {
			  	$errors[] = $validator->errors();
			}else{
				// $mobile_no = str_replace("-", "", $request->mobile_no);
				$mobile_no = str_replace(" ", "", $request->mobile_no);

				//dd($request->all());
				$mobile_no = ltrim($mobile_no,0);
				$collection = collect([]);

				$collection = $this->BaseModel
									 ->where('first_name',trim($request->first_name))
									 ->where('family_name',trim($request->family_name))
									 ->where('mobile_no',trim($mobile_no))
									 ->first();
						//dd($collection);
				if(!empty($collection))
				{
					if($collection->status==1)
					{
						$status = true;

						$collection = $this->_updateOtp($collection);
						//dd($collection);
						$collection->social_security_number = $collection->insurance_number;

						$message = __('api.AUTH_USER_VALIDATED_SUCCESS');
						$log_id = $collection->id;
						$data[]  = $collection->only(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','message','social_security_number']);
						self::_createLog('SignupSendOtp',$log_id,'info');
						$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);

					}
					else
					{
						$message = __('api.AUTH_INACTIVE_USER');
			       		$errors[] = [
			              "error" => __('api.AUTH_INACTIVE_USER'),
			          				];
						self::_createLog('SignupSendOtp',$errors,'error');
						// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					}
				}
				else
				{

					$message = __('api.AUTH_INVALID_PATIENT');
			       	$errors[] = [
			              "error" => __('api.AUTH_INVALID_PATIENT'),
			          				];
					self::_createLog('SignupSendOtp',$errors,'error');
					// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
				}
			}
    	}
    	catch (Exception $e)
    	{

    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('SignupSendOtp',$errors,'error');
    		// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Delete',null,$data);
    	}
	    return self::_sendResult($message,$data,$errors,$status);

    }//passwordSendOtp

     public function passwordSendOtp(Request $request)
    {
		$errors = [];
		$data = [];
		$message = __('api.AUTH_INVALID_USER');
		$status = false;
		$social_security_number = null;
		$flag = 0;

    	$inputdata = $request->all();

    	try {
    		$validator = Validator::make($inputdata,[
						  // 'first_name' 	=> 'required', //commented on 18-dec-23
						  // 'family_name' 	=> 'required', //commented on 18-dec-23
						  'country_code' => 'required',
						  'mobile_no' 	=> 'required|regex:/^[0-9][0-9]*$/',
						     'birth_date' => 'required',
						  // 'password' 	=> 'required',
						],
						[
						  // 'first_name.required'	=> __('api.AUTH_FIRSTNAME_REQ'), //commented on 18-dec-23
						  // 'family_name.required' => __('api.AUTH_FAMILYNAME_REQ'), //commented on 18-dec-23
						  'country_code' => __('api.AUTH_COUNTRY_CODE_REQ'),
						  'mobile_no.required' 	=> __('api.AUTH_MOBILENO_REQ'),

						  'mobile_no.regex'       =>  __('api.AUTH_MOBILENO_NOTSTARTWITHZERO'),
						  'birth_date.required' => __('api.PATIENT_BIRTH_DATE_REQ')
						  // 'password' 	          => __('api.AUTH_USER_PAAWORD_REQUEIED'),
						]
						);

			if ($validator->fails()) {
			  	$errors[] = $validator->errors();
			}else{
				// $mobile_no = str_replace("-", "", $request->mobile_no);
				$mobile_no = str_replace(" ", "", $request->mobile_no);

				//dd($request->all());
				$mobile_no = ltrim($mobile_no,0);
				$collection = collect([]);

				$collection = $this->BaseModel
									 // ->where('first_name',trim($request->first_name)) //commented on 18-dec-23
									 // ->where('family_name',trim($request->family_name)) //commented on 18-dec-23
									 ->whereDate('birth_date', date('Y-m-d',strtotime($request->birth_date))) //added on 18-dec-23
									 ->where('mobile_no',trim($mobile_no))
									 ->first();
						//dd($collection);
				if(!empty($collection))
				{
					if($collection->status==1)
					{
						$status = true;

						$collection = $this->_updateOtp($collection);
						//dd($collection);
						$collection->social_security_number = $collection->insurance_number;

						$message = __('api.AUTH_USER_VALIDATED_SUCCESS');
						$log_id = $collection->id;
						$data[]  = $collection->only(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','message','social_security_number']);
						self::_createLog('SignupSendOtp',$log_id,'info');
						$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);

					}
					else
					{
						$message = __('api.AUTH_INACTIVE_USER');
			       		$errors[] = [
			              "error" => __('api.AUTH_INACTIVE_USER'),
			          				];
						self::_createLog('SignupSendOtp',$errors,'error');
						// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
					}
				}
				else
				{

					$message = __('api.AUTH_INVALID_PATIENT');
			       	$errors[] = [
			              "error" => __('api.AUTH_INVALID_PATIENT'),
			          				];
					self::_createLog('SignupSendOtp',$errors,'error');
					// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
				}
			}
    	}
    	catch (Exception $e)
    	{

    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('SignupSendOtp',$errors,'error');
    		// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Delete',null,$data);
    	}
	    return self::_sendResult($message,$data,$errors,$status);

    }//passwordSendOtp

    public function passwordVerifyOtp(Request $request)
    {
    	//dd($request->all());
		$errors = [];
		$data = [];
		$message = __('api.AUTH_INVALID_OTP');
		$status = false;

		$validator = Validator::make($request->all(), [
                'patient_id' => 'required',
                'otp' => 'required|numeric',
	            ],
				[
				  'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
				  'otp.required' => __('api.AUTH_OTP_REQ'),
				]);
		if($validator->fails())
		{
		  $errors[] = $validator->errors();
		}
		else
		{
			$data = [];

			$collection = $this->BaseModel->find($request->patient_id);

			if(!empty($collection))
			{
				$start = date('Y-m-d H:i:s', strtotime($collection->otp_created_at));
				$start = new Carbon($start);

				$end =  new Carbon(date('Y-m-d H:i:s', time()));
				$diffInMinutes = $start->diffInMinutes($end);

				if($diffInMinutes<=5)
				{
					if($collection->login_otp==$request->otp)
					{
						$status  = true;
				        $message = __('api.AUTH_VERIFY_USER_SUCCESS');
				        $log_id = $collection->id;
				        $data[]  = $collection->only(['first_name','family_name','email','country_code', 'mobile_no','age','postal_code','birth_date']);

					    //$data['user'] = $user;
				        self::_createLog('passwordVerifyOtp',$log_id,'info');

				        $this->ActivityLogModel->addApiLog('passwordVerifyOtp','Password verify otp and create login token','Create',null,$data);
					}
					else
					{
			        	$errors = [
			              "error" => __('api.AUTH_INVALID_OTP'),
			          	];
			        	$message = __('api.AUTH_INVALID_OTP');
	   	            	self::_createLog('passwordVerifyOtp',$errors,'error');
					}
				}
				else
				{
					$errors[] = [
		              "error" => __('api.AUTH_OTP_EXPIRED'),
		          	];
		        	$message = __('api.AUTH_OTP_EXPIRED');
		        	self::_createLog('passwordVerifyOtp',$errors,'error');
				}

			}
			else
		 	{
		        $errors = [
		              "error" => __('api.AUTH_INVALID_PATIENT'),
		          ];
		        $message = __('api.AUTH_INVALID_PATIENT');
		        self::_createLog('passwordVerifyOtp',$errors,'error');
			}

		}
	    return self::_sendResult($message,$data,$errors,$status);
    }

    public function forgetPassword(Request $request)
    {
    	//dd($request->all());
    	$message   = __('api.ERR_SOMETHING_WRONG');
		$status    = false;
		$errors = [];
		$data = [];
		$str_password = $request->password;
		$patient_id   = $request->patient_id;
		$password = Hash::make($request->password);
		try
		{

			$patient = DB::table('patients')
	    				->where('id',$patient_id)
	    				->first();


			$patient = DB::table('patients')
	    				->where('id',$patient_id)
	    				->update(['password'=>$password,'is_updated'=>'1']);

	    	if(!empty($patient))
	    	{
	    		$parentPatientId = $this->PatientsModel->find($patient_id);

	    		$master_patient  = DB::connection('system')
    					   			->table('patients')
    					  			->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($parentPatientId->family_name))
                                	->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($parentPatientId->first_name))
                                	->whereDate('birth_date', date('Y-m-d',strtotime($parentPatientId->birth_date)))
                                	->where('mobile_no', $parentPatientId->mobile_no)
                                	->whereNULL('deleted_at')
                                	->first();

                if(!empty($master_patient))
                {

                	$masterUPpatient = DB::connection('system')
                					->table('patients')
	    							->where('id',$master_patient->id)
	    							->update(['password'=>$password,'is_updated'=>'1']);

	    			if($masterUPpatient)
	    			{
	    				$collection = $parentPatientId;

						$collection->social_security_number = $collection->insurance_number;

						$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
						$log_id = $collection->id;
						$data[]  = $collection->only(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','message','social_security_number']);

						self::_createLog('SignupSendOtp',$log_id,'info');
						$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
						$status    = true;

	    			}
	    			else
	    			{
	    				$message   = __('api.ERR_SOMETHING_WRONG');
						$status    = false;
	    			}
                }
	    	}

		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}

		return self::_sendResult($message,$data,$errors,$status);
    }

    //Added by Swati 15-Jul-2022==========================
    public function forgetPasswordPuremed(Request $request)
    {
    	//dd($request->all());
    	$message   = __('api.ERR_SOMETHING_WRONG');
		$status    = false;
		$errors = [];
		$data = [];
		$str_password = $request->password;
		$patient_id   = $request->patient_id;
		$password = Hash::make($request->password);
		try
		{
				$masterPatientupdate = DB::table('patients')
	    					->where('id',$patient_id)
	    					->update(['password'=>$password,'is_updated'=>'1']);
				if(!empty($masterPatientupdate)){
					$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
					$log_id = $patient_id;
					$collection = DB::table('patients')->select(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','social_security_number'])->where('id',$patient_id)->first();
					$data[]  = $collection;
					self::_createLog('SignupSendOtp',$log_id,'info');
					$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
					$status    = true;
				}
				else{
					$message   = __('api.ERR_SOMETHING_WRONG');
					$status    = false;
				}
				$getOrdination=DB::table('patients_has_ordination')->where('fk_patient_id',$patient_id)->get();
				if(!empty($getOrdination) && !empty($masterPatientupdate)){
					foreach($getOrdination as $ordination){
						$getWebsites = DB::connection('system')
	    					   ->table('tenants')
	    					   ->where('ordination_id',$ordination->fk_ordination_id)
	    					   ->first(['uuid']);
	    				//dd($getWebsites);
	    				$database_name = $getWebsites->uuid;
	    				$patient = DB::table('patients')
				    				->where('id',$patient_id)
				    				->first();

				    	//commented first name and family name on 15-dec-23
						$ordination_patient  = DB::table($database_name.'.patients')
											// ->where(DB::raw('upper(family_name)'),'=',mb_strtoupper($patient->family_name))
											// ->where(DB::raw('upper(first_name)'),'=',mb_strtoupper($patient->first_name))
											->whereDate('birth_date', date('Y-m-d',strtotime($patient->birth_date)))
											->where('mobile_no', $patient->mobile_no)
											->whereNULL('deleted_at')
											->first();
						if(!empty($ordination_patient)){
							$Patientupdate = DB::table($database_name.'.patients')
										->where('id',$ordination_patient->id)
										->update(['password'=>$password,'is_updated'=>'1','updated_at'=>date("Y-m-d H:i:s")]);
						}
					}
					$message   = __('api.PASSWORD_UPDATED_SUCCESSFULLY');
					$log_id = $patient_id;
					$collection = DB::table('patients')->select(['id','first_name','family_name','email','country_code','mobile_no','birth_date','login_otp','social_security_number'])->where('id',$patient_id)->first();
					$data[]  = $collection;
					self::_createLog('SignupSendOtp',$log_id,'info');
					$this->ActivityLogModel->addApiLog('Signup Send Otp','Send otp for login','Create',null,$data);
					$status    = true;
				}
				else{
					$message   = __('api.ERR_SOMETHING_WRONG');
					$status    = false;
				}
		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}

		return self::_sendResult($message,$data,$errors,$status);
    }


    public function debugModeFun(Request $request)
    {
    	$type = 'info';
    	$name = "debug_api_function";
    	$data = json_encode($request->all());

    	config(['logging.channels.api.path' => '/opt/app-shared/php/data/storage/logs/api/debug_log_'.date('Y-m-d').'.log']);
        Log::channel('api')->$type($name,array($data));

        $message = "Debug record added successfully";
        $errors  = [];
        $status = true;

        return self::_sendResult($message,$data,$errors,$status);
    }

    public function testsearchOrdination(Request $request)
    {
    	//dd($request->all());
		$errors = [];
		$data = [];
		$is_available = 0;
		$message = __('api.ERR_SOMETHING_WRONG');
		$status = false;
		$ordination_url = null;
		// $mapsApiKey    = Config('mapsApiKey');
		$mapsApiKey    = 'AIzaSyAZlUm6ZfRn-ljTE4GB8MKXUamh9hwLZw4';
		$getOrdination = [];
		// $sessting = $this->SettingsModel->where('setting_key','MAX_DISTANCE')->first(['setting_value']);
		// $redius = (int)$sessting->setting_value;
		$redius = self::max_distance();
		try
		{
			$code = $request->postal_code;
			$patient_id = $request->patient_id;
	    	//$getPatientPostalCode = $this->BaseModel->find($patient_id);

	    	if(!empty($code) && $code>0)
	    	{
	    		$data['is_available']    = 1;
	    		// dd($patient_id);
	    		$getOrdinationIds = DB::connection('system')
                    				->table("patients_has_ordination")
                    				->where('fk_patient_id',$patient_id)
                    				->whereNull('deleted_at')
                    				->get(['fk_ordination_id as id']);
                //dd($getOrdinationIds);
                $result = null;
                if(!empty($getOrdinationIds))
                {
                	foreach ($getOrdinationIds as $key => $value)
                	{
                		if($key == 0)
                		{
                			$result .= $value->id;
                		}
                		else
                		{
                			$result .= ','.$value->id;
                		}
                	}
                }
               	//dd($result);
                //$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."&sensor=false";
                $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+AUSTRIA&sensor=false";
                // $url = "https://maps.googleapis.com/maps/api/geocode/json?address=AU&components=postal_code:".$code."&sensor=false&key=".$mapsApiKey;
               	//dd($url);
				$URLdata = file_get_contents($url);

				if($URLdata)
				{
					$decode_data = json_decode($URLdata);
					if(!empty($decode_data->results) && sizeof($decode_data->results)>0)
					{
						$lat = $decode_data->results[0]->geometry->location->lat;
					  	$lng = $decode_data->results[0]->geometry->location->lng;

	                	if(!empty($result))
	                	{
					      //           		$getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS(".$lat." * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
											// from ordination
											// WHERE ordination.id NOT IN (".$result.")
											// ORDER BY distance"));//LIMIT 10

							$getOrdination=DB::connection('system')->select(DB::raw("SELECT
								  ordination.*,(
								    3959 * acos (
								      cos ( radians(".$lat.") )
								      * cos( radians( latitude ) )
								      * cos( radians( longitude ) - radians(".$lng.") )
								      + sin ( radians(".$lat.") )
								      * sin( radians( latitude ) )
								    )
								  ) AS distance
								FROM ordination
								HAVING distance < ".$redius."
								ORDER BY distance
								"));//LIMIT 10
							//WHERE ordination.id NOT IN (".$result.")

	                	}
	                	else
	                	{
					      	// $getOrdination=DB::connection('system')->select(DB::raw("SELECT ordination.* , (3956 * 2 * ASIN(SQRT( POWER(SIN(( ".$lat." - latitude) *  pi()/180 / 2), 2) +COS( $lat * pi()/180) * COS(latitude * pi()/180) * POWER(SIN(( ".$lng." - longitude) * pi()/180 / 2), 2) ))) as distance
							// from ordination
							// ORDER BY distance"));//LIMIT 10
							$getOrdination=DB::connection('system')->select(DB::raw("SELECT
								  ordination.*,(
								    3959 * acos (
								      cos ( radians(".$lat.") )
								      * cos( radians( latitude ) )
								      * cos( radians( longitude ) - radians(".$lng.") )
								      + sin ( radians(".$lat.") )
								      * sin( radians( latitude ) )
								    )
								  ) AS distance
								FROM ordination
								HAVING distance < ".$redius."
								ORDER BY distance
								"));//LIMIT 10
							//HAVING distance < ".$redius."
	                	}
					}


                }
                $getOrdination = collect($getOrdination);
                 // dd($getOrdination);

				if(sizeof($getOrdination)>0)
				{
					$getOrdination = $getOrdination->map(function($item)
					{

						if($item->name == 'puregyn')
		            	{
		            		$item->name = strtoupper($item->name);
		            	}
						$item->logo_path = self::getFilePath($item->logo_path);
						$gethostnames = DB::connection('system')
			                    				->table("domains")
			                    				->where('ordination_id',$item->id)
			                    				->whereNull('deleted_at')
			                    				->first(['fqdn']);
	                    if(!empty($gethostnames))
	                    {
	                    	$fqdn = "https://". $gethostnames->fqdn;
	                    	// $ordination_url = $gethostnames->fqdn;
	                    	$ordination_url = $fqdn;

	                    	$item->ordination_url = $ordination_url;
	                    }
	                    else
	                    {
	                    	$item->ordination_url = null;
	                    }

						$item->distance = number_format(($item->distance * 1.609344),2);
						//$item->distance = round($item->distance);

						$getSpecilistid = DB::connection('system')
	                    				->table("ordination_has_specialist")
	                    				->where('ordination_id',$item->id)
	                    				->whereNull('deleted_at')
	                    				->get(['specialist_id']);
			            if(sizeof($getSpecilistid)>0)
			            {
			            	$getSpecilistid = $getSpecilistid->map(function($spitem)
		            		{
		            			$specialistDetails = DB::connection('system')
		                    						->table("specialist")
		                    						->where('id',$spitem->specialist_id)
		                    						->whereNull('deleted_at')
		                    						->first(['name']);

		                    	$spitem->specialist_name = $specialistDetails->name;
		                    	return $spitem;
		            		});
			            }
	                    $item->specialist = $getSpecilistid;
						return $item;

					});
					$data['ordination_data'] = $getOrdination;
					$data['is_available'] = 1;
					$message   =  __('api.AUTH_ORDINATION_DATA_SUCCESS');
					$status    = true;
				}
				else
				{
					$data['is_available'] = 1;
					$data['ordination_data'] = [];
		    		$message   = __('api.AUTH_ORDINATION_DATA_NOT_EXIST');
					$status    = false;
				}
	    	}
	    	else
	    	{
	    		// $data = self::getLocationPatientHasOrdination($getPatientPostalCode,$patient_id,'address');
	    		$data['is_available'] = 1;
	    		$data['ordination_data'] = [];
	    		$message   = __('api.AUTH_ORDINATION_POSTAL_CODE');
				$status    = false;

	    	}
		}
		catch (Exception $e)
		{
    		$message = __('api.ERR_SOMETHING_WRONG');
			$errors[] = [
			              "error" => __('api.ERR_SOMETHING_WRONG'),
			              "error_msg" => $e->getMessage(),
			          				];
			self::_createLog('RegisterPatient',$errors,'error');
			// $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
    	}
    	//dd($data);
		return self::_sendResult($message,$data,$errors,$status);
    }//


    	//Below function added for testing purpose on 13oct22
     public function updatePatientDocumentSignTest(Request $request)
    {
    	//Log::info('in updatePatientDocumentSign function :');

		$errors = [];
		$data = [];
		$message = __('api.DATA_FOUND_SUCCESS');
		$status = false;

		$id   = $request->id;
		// $tab_pin   = $request->tab_pin;
		// dd($old_id);
		$validator = Validator::make($request->all(), [
               		//'old_id' => 'required',
               		'id' => 'required',
               		//'patient_signature' => 'required',
	            ], [
					//'old_id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					'id.required'	=> __('api.AUTH_GANY_PATIENT_ID_REQ'),
					//'patient_signature.required'	=> 'Tab-Pin ist erforderlich',
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}
		else {
		// try {
			$status = true;
			$patient_doc = $this->PatientHasDocumentsModel->find($id);
			$doc_status = explode(',', $patient_doc->doc_status);
			//$patient_doc->notes  = $request->notes;   //commented on 13oct22
           	if(!in_array('2', $doc_status))
           	{
           		if(in_array('0', $doc_status))
           		{
           			// $patient_doc->doc_status  = '2'; //commented on 13-dec-24 for signdoc app status 2 when doc is read only issue

           			//start added pn 13-dec-24
           			if(empty($request->patient_signature)){
           				$patient_doc->doc_status  = '1';
           			}else{
           				$patient_doc->doc_status  = '2';
           			}
           			//end added pn 13-dec-24

           		}
           		else {
           			$patient_doc->doc_status  = $patient_doc->doc_status.',2';
           		}

           	}
			$patient_doc->remarks = '';
			//dd($request->patient_signature);
			if(!empty($request->patient_signature))
            {

                $file_data = $request->patient_signature;
                if(!empty(Config('ordination_id')))
                {
                    $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
                    $signPath = '/tenancy/tenants/'.$getDatabaseName->uuid.'/sign/';
                }
                else {
                    $signPath = '/opt/app-shared/php/data/storage/app/public/sign/';
                }
                $file_name = 'image_' . time() . '.png'; //generating unique file name;
				//$a = Storage::put($file_name, base64_decode($file_data));
				//dump($signPath.$file_name);
				$a = Storage::disk('public')->put($signPath.$file_name, base64_decode($file_data));
				$name = explode('/', $file_name);
				$patient_doc->remarks = $file_name;
            }//if

            if(!empty($request->notes))
            {
                $notes_file_data = $request->notes;
                if(!empty(Config('ordination_id')))
                {
                    $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
                    $noteSignPath = '/tenancy/tenants/'.$getDatabaseName->uuid.'/notes/';
                }
                else {
                    $noteSignPath = '/opt/app-shared/php/data/storage/app/public/notes/';
                }
                $note_file_name = 'image_' . time() . '.png'; //generating unique file name;
				$a = Storage::disk('public')->put($noteSignPath.$note_file_name, base64_decode($notes_file_data));
				$name = explode('/', $note_file_name);
				$patient_doc->notes = $note_file_name;
            }


            if($patient_doc->save())
            {

            	//Log::info('if patient document saved then create pdf call :');
            	// Generate PDF
            	self::_createGeneralDocumentPdf($patient_doc,$patient_doc->patient_id);

            	$message = __('api.DATA_FOUND_SUCCESS');
          		$data[] = $patient_doc;

          		self::_createLog('updatePatientSign',$data,'info');
            }
            else {
             	$message = __('api.ERR_NOT_FOUND');
             	self::_createLog('updatePatientSign',$message,'error');
            }

		}
	    return self::_sendResult($message,$data,$errors,$status);
    }//updatePatientDocumentSignTest


     //Below function added for testing purpose on 5-jan-23
    public function updatePatientDocumentSignTesting(Request $request)
    {
        $errors = [];
	    $data = [];
	    $message = __('api.DATA_FOUND_SUCCESS');
	    $status = false;

	    $id   = $request->id;
	    // $tab_pin   = $request->tab_pin;
	    // dd($old_id);
	    $validator = Validator::make($request->all(), [
	                  //'old_id' => 'required',
	                  'id' => 'required',
	                  //'patient_signature' => 'required',
	              ], [
	          //'old_id.required' => __('api.AUTH_GANY_PATIENT_ID_REQ'),
	          'id.required' => __('api.AUTH_GANY_PATIENT_ID_REQ'),
	          //'patient_signature.required'  => 'Tab-Pin ist erforderlich',
	        ]);
	    if($validator->fails()) {
	      $errors[] = $validator->errors();
	    }
	    else
	    {
	    // try {
	      $status = true;
	      $patient_doc = $this->PatientHasDocumentsModel->find($id);
	      $doc_status = explode(',', $patient_doc->doc_status);
	      //$patient_doc->notes  = $request->notes;   //commented on 13oct22
	            if(!in_array('2', $doc_status))
	            {
	              if(in_array('0', $doc_status))
	              {
	                $patient_doc->doc_status  = '2';
	              }
	              else {
	                $patient_doc->doc_status  = $patient_doc->doc_status.',2';
	              }

	            }
	      $patient_doc->remarks = '';
         //dd($request->patient_signature);
           if(!empty($request->patient_signature))
           {

                $file_data = $request->patient_signature;
                if(!empty(Config('ordination_id')))
                {
                    $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
                    $signPath = '/tenancy/tenants/'.$getDatabaseName->uuid.'/sign/';
                }
                else {
                    $signPath = '/opt/app-shared/php/data/storage/app/public/sign/';
                }
                $file_name = 'image_' . time() . '.png'; //generating unique file name;
		        //$a = Storage::put($file_name, base64_decode($file_data));
		        //dump($signPath.$file_name);
		        $a = Storage::disk('public')->put($signPath.$file_name, base64_decode($file_data));
		        $name = explode('/', $file_name);
		        $patient_doc->remarks = $file_name;
            }//if

            if(!empty($request->notes))
            {
               $notes_file_data = base64_encode($request->notes);
              // $notes_file_data = base64_encode($request->file('notes'));
                if(!empty(Config('ordination_id')))
                {
                    $getDatabaseName = DB::connection('system')
                                ->table("tenants")
                                ->where('ordination_id',Config('ordination_id'))
                                ->first(['uuid']);
                    $noteSignPath = '/tenancy/tenants/'.$getDatabaseName->uuid.'/notes/';
                }
                else {
                    $noteSignPath = '/opt/app-shared/php/data/storage/app/public/notes/';
                }
                $note_file_name = 'image_' . time() . '.png'; //generating unique file name;
                $a = Storage::disk('public')->put($noteSignPath.$note_file_name, base64_decode($notes_file_data));
		        $name = explode('/', $note_file_name);
		        $patient_doc->notes = $note_file_name;
            }


            if($patient_doc->save())
            {

              //Log::info('if patient document saved then create pdf call :');
              // Generate PDF
              self::_createGeneralDocumentPdf($patient_doc,$patient_doc->patient_id);

              $message = __('api.DATA_FOUND_SUCCESS');
              $data[] = $patient_doc;

              self::_createLog('updatePatientSign',$data,'info');
            }
            else {
              $message = __('api.ERR_NOT_FOUND');
              self::_createLog('updatePatientSign',$message,'error');
            }

       }
      return self::_sendResult($message,$data,$errors,$status);
    }//updatePatientDocumentSignTesting

   /************ Roshani added below api for 146 ****************/

   public function VerifyUserExist(Request $request)
    {
        $errors     = [];
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');
        $status     = false;

        $validator = Validator::make($request->all(), [
                'patient_id' => 'required',
	            ],
				[
				  'patient_id.required'	=> __('api.AUTH_PATIENT_ID_REQ'),
				]);
		if($validator->fails()) {
		  $errors[] = $validator->errors();
		}
		else
		{
	        try
	        {
	        	$patientId = $request->patient_id;
	            if(isset($patientId) && $patientId != ''){
	                $user = $this->BaseModel::where('id', '=', $patientId)->first();
	                if ($user === null) {
	                    $status = false;
	                    $message = __('api.USER_NOT_EXIST');
	                }else
	                {
	                    $status = true;
	                    $message = __('api.USER_EXIST');
	                }
	            }//if

	        }//try
	        catch(\Exception $e) {
	            $message = __('api.ERR_SOMETHING_WRONG');
	            $errors[] = [
	                "error" => $e->getMessage(),
	            ];
	        }//catch
        }//else
        return self::_sendResult($message, $data, $errors, $status);
    }//function

   /************ Roshani added above api for 146 ****************/

  	//Roshani added the below error message or condition for 102 CR
		public function withValidator($request)
	    {
	            $country = $request->country;
	            $postalCode = $request->postal_code;

	            if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
	                $request->errors()->add('postal_code', __('admin.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY'));
	            }

	            if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
	                $request->errors()->add('postal_code', __('admin.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('admin.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2'));
	            }
	    }
	//Roshani added the below error message or condition for 102 CR

	//Roshnai added the below function for get country code for #413
	public function getCountries(Request $request)
	{
		$errors     = [];
		$data       = [];
		$message    = __('api.ERR_NOT_FOUND');
		$status     = false;
			try
			{
				$country = $request->country;
				$countryData = $this->CountryCodesModel::all();
				if ($countryData) {
					$status = true;
					$message = __('api.COUNTRY_FOUND_SUCCESS');
					$data['country_code'] = $countryData;
				} else {
					$message = __('api.COUNTRY_CODE_NOT_FOUND');
				}
			}
			catch(\Exception $e) {
	            $message = __('api.ERR_SOMETHING_WRONG');
	            $errors[] = [
	                "error" => $e->getMessage(),
	            ];
	        }
		return self::_sendResult($message, $data, $errors, $status);
	}
}