<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Requests\Admin\Auth\ForgotPasswordRequest;

use Illuminate\Support\Facades\Lang;
use App\Mail\ForgotPasswordMail; 

use App\PasswordReset;
use App\Http\Requests\Admin\Auth\ResetPasswordRequest;
use \Illuminate\Auth\Passwords\PasswordBroker;

use App\Models\OrdinationHasSpecialistModel;
use App\Models\AdminUserModel;
use App\Models\OrdinationsModel;
use Spatie\Permission\Models\Permission;

use WebSmsCom_Client;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_TextMessage;
use WebSmsCom_ParameterValidationException; 
use WebSmsCom_AuthorizationFailedException;
use WebSmsCom_ApiException;
use WebSmsCom_HttpConnectionException;
use WebSmsCom_UnknownResponseException;
use Illuminate\Support\Facades\Log;

use App;
use DB;
use Hash;
use Mail;
use Cookie;
use Carbon\Carbon;
use Config;
use Auth;
use Validator;


// temp
// use Spatie\Permission\Models\Role;

use App\Traits\GeneralTrait;
use Session;
use App\Models\CountryCodesModel; // new model for country code lookup
class AuthController extends Controller
{   
    private $BaseModel;
    private $ViewData;
    private $JsonData;
    private $ModuleTitle;
    private $ModuleView;
    private $ModulePath;

    use GeneralTrait; 

    public function __construct(
       
        AdminUserModel $AdminUserModel,
        // RememberMeModel $RememberMeModel,
        PasswordReset $PasswordResetModel,
        PasswordBroker $PasswordBroker,
        OrdinationsModel $OrdinationsModel,
        // Website $website, // Commented out - using Stancl Tenancy now
        // Hostname $hostname, // Commented out - using Stancl Tenancy now
        OrdinationHasSpecialistModel $OrdinationHasSpecialistModel,
        CountryCodesModel $CountryCodesModel
    )
    {

        $this->BaseModel = $AdminUserModel;   
        // $this->RememberMeModel = $RememberMeModel;   
        $this->PasswordResetModel = $PasswordResetModel;
        $this->PasswordBroker = $PasswordBroker;
        $this->OrdinationsModel = $OrdinationsModel;
        // $this->website  = $website; // Commented out - using Stancl Tenancy now
        // $this->hostname  = $hostname; // Commented out - using Stancl Tenancy now 
        $this->OrdinationHasSpecialistModel  = $OrdinationHasSpecialistModel;
        $this->CountryCodesModel = $CountryCodesModel; // country codes lookup

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleView  = 'admin.auth.';
        $this->ModulePath = 'admin.auth.';   
        
        $this->rememberTitle = 'LARAVEL_RSESSION'; 
        
        // Session::put('company_id', 1);
        // dd(Session::get('company_id'));
        // $companyId = self::_getCompanyId();
        // dd($companyId);
    }

        /*---------------------------------
    |   LANGUAGE TRANSLATION FUNCTION
    ------------------------------------------*/
    public function languageTranslation() 
    {  
        $tables = DB::connection('ganymed-mysql')->select('SHOW TABLES FROM schillermed');
        // view file with data
        return view('admin.database.databasetranslation',['tables' => $tables]);
    }   


    /*---------------------------------
    |   LOGIN AND LOGOUT
    ------------------------------------------*/

        public function login(Request $request)
        {
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            App::setLocale('de');

            $this->ViewData['moduleTitle']  = __('admin.TITLE_LOGIN_MODULE');
            $this->ViewData['moduleAction'] = __('admin.TITLE_LOGIN_MODULE');
            $this->ViewData['modulePath']   = $this->ModulePath.'login';

            $this->ViewData['ordination'] = $this->OrdinationsModel
                                            ->where('status',1)
                                            ->get();

            /*if (!empty($_COOKIE[$this->rememberTitle])) 
            {
                $token = $_COOKIE[$this->rememberTitle];

                $this->ViewData['user'] = $this->RememberMeModel
                                        ->where('remember_token', $token)
                                        ->first();
            }*/
            // add country code list for dropdown
            $this->ViewData['country_codes'] = $this->CountryCodesModel
                ->where('is_active',1)
                // ->orderBy('phone_code')
                ->pluck('phone_code')
                ->toArray();

            return view($this->ModuleView.'login', $this->ViewData);
        }

        public function checkLogin(LoginRequest $request,$enCompanyId=false)
        {   
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.ERR_ACCOUNT_INCORRECT'); 


            $language = 'en';
            if(!empty($request->language)){
                $language = $request->language;
            }

            $mobile_no = str_replace(" ", "", $request->phone);
            $mobile_no = ltrim($mobile_no,0);
            $countryCode = $request->format;

            // Convert both +43 and 0043 to a common format
            $normalizedCode = preg_replace('/^\+/', '00', $countryCode); // +43 → 0043
            $normalizedCodeAlt = preg_replace('/^00/', '+', $countryCode); // 0043 → +43

            $getUsersDetails = $this->BaseModel
                ->where('email', $request->email)
                ->where('mobile_number', $mobile_no)
                ->where(function ($query) use ($normalizedCode, $normalizedCodeAlt, $countryCode) {
                    $query->where('country_code', $countryCode)
                        ->orWhere('country_code', $normalizedCode)
                        ->orWhere('country_code', $normalizedCodeAlt);
                })
                ->first();
            
            // $getUsersDetails = $this->BaseModel
            //                    ->where('email',$request->email)
            //                    ->where('mobile_number',$mobile_no)
            //                    ->where('country_code',$request->format)
            //                    ->first();
         
            session::put('country_code_sess',$request->format);       
            if(!empty($getUsersDetails))
            {
                if (Hash::check($request->password, $getUsersDetails->password))
                {
                    $mobile_no = str_replace(" ", "", $request->phone);
                    $mobile_no = ltrim($mobile_no,0);
                    $collection = $this->_updateOtp($getUsersDetails,$request->format);
                    //dd($request->remember_me); 
                    setcookie ("email",$request->email,time()+ 3600);
                    setcookie ("password",$request->password,time()+ 3600);
                    setcookie ("mobile_no",$request->phone,time()+ 3600);
                    // 
                    // $getUsersDetails->str_password = $request->password;
                    $getUsersDetails->str_password = self::encodeString($request->password);
                    $getUsersDetails->save();
                    session::put('language',$language);

                    // 
                    $user_id = base64_encode(base64_encode($getUsersDetails->id));
                    $this->JsonData['url']    = url('admin/login-send-otp/'.$user_id);
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']    = __('admin.ACCOUNT_SUCCESS');
                }
                else
                {
                    $this->JsonData['status'] = __('admin.RESP_ERROR');
                    $this->JsonData['msg'] = __('admin.ERR_ACCOUNT_PASSWORD_WRONG');
                }
            }
            else
            {
                $this->JsonData['status'] = __('admin.RESP_ERROR');
                $this->JsonData['msg']    = __('admin.ERR_ACCOUNT_EMAIL_MOBILE');
            }
            //dd($this->JsonData);
            return response()->json($this->JsonData);exit;           
        }

        public function _updateOtp($collection,$code)
        {
            if(!empty($collection)){

                if($collection->id==23662){
                    $otp_code = 1234; //Ios user testing                               
                }else{
                    $otp_code = rand(1000, 9999);       
                }

                //update otp for the patient and send sms to the patient
                $password  = Hash::make($otp_code);

                $updateQry = DB::table('users')
                                ->where('id', $collection->id)
                                ->update([
                                            'login_otp' => $otp_code,
                                            // 'password' => $password,
                                            // 'str_password' => $otp_code,
                                            'otp_created_at' => date('Y-m-d H:i:s')
                                        ]);
                // $country_code = $code;
                $country_code = $collection->country_code;
                /*if($country_code==0){
                    $country_code = str_replace("0", "91",$collection->country_code); //for testing indian mobile
                }else{
                    $country_code = str_replace("00", "",$collection->country_code);
                }*/
                if(!empty($country_code)){
                    $country_code = str_replace("00", "",$code);
                }elseif(empty($country_code) || $country_code=='0'){
                    $country_code = '43'; //Austria country code
                }

                $country_code = str_replace("+", "",$country_code);
                //dd($collection);
                $phone   = $country_code."".str_replace("-", "",$collection->mobile_number);
               // $message = 'Hallo '.$collection->first_name.' , Ihr Otp:'.$otp_code.' ist der Bestätigungscode für Ihre Registrierung, der 5 Minuten gültig ist ';
                $message = 'Hallo '.$collection->family_name.', lhr Login-Code für die PUREGYN-App lautet '.$otp_code.'. Er ist 5 Minuten gültig.';
                $collection->login_otp = $otp_code;
                $collection->message = $message;
                //dd($phone,$message);
               // $message .= "test message from puregyn api...please ignore.";
                self::_sendSms($phone,$message);

            }
            
            return $collection; 
        }

        public function _sendSms($phones,$text)
        {

         if(!empty($phones) && !empty($text))
         {
                                     
            $gateway_url      = config('constants.SMS_URL'); 
            $accessToken      = config('constants.SMS_TOKEN');

            $recipientAddressList = array($phones);
            $utf8_message_text    = $text;
            
            $maxSmsPerMessage     = 1; 
            $test                 = false; // true: do not send sms for real, just test interface

            $responseRecord = array(
                                    'error' => 1 ,
                                    'code'  =>  1,
                                    'message'=> ''
                                    );

          try {

            // 1.) -- Alternatively authenticate over access token
            $smsClient = new WebSmsCom_Client($accessToken, '', $gateway_url, WebSmsCom_AuthenticationMode::ACCESS_TOKEN);

            $smsClient->setVerbose(false);
            $smsClient->setSslVerifyHost(2); // needed if CURLOPT_SSL_VERIFYHOST 

           // 2.) -- create text message ----------------
            $message  = new WebSmsCom_TextMessage($recipientAddressList, $utf8_message_text);

            // 3.) -- send message ------------------
            $Response = $smsClient->send($message, $maxSmsPerMessage, $test);

            // return success
             $responseRecord = array(
                                    'error'=>0,
                                    'code' =>$Response->getStatusCode(), 
                                    'message'=>$Response->getStatusMessage(),
                                    'transferId'=>$Response->getTransferId(),
                                   // 'messageId'=>$Response->getClientMessageId(),
                                    );
            
            //dd($Response);
            // catch everything that's not a successfully sent message
          } catch (WebSmsCom_ParameterValidationException $e) {
             $responseRecord = array(
                                    'error' => 1 ,
                                    'code'=>'1',
                                    'message' => "ParameterValidationException caught: ".$e->getMessage()
                                    );
            //exit("ParameterValidationException caught: ".$e->getMessage()."\n");die();
            
          } catch (WebSmsCom_AuthorizationFailedException $e) {
            exit("AuthorizationFailedException caught: ".$e->getMessage()."\n");die();
            $responseRecord = array(
                                    'error' => 1 ,
                                    'code'=>'1',
                                    'message' => "AuthorizationFailedException caught: ".$e->getMessage()
                                    );
          
          } catch (WebSmsCom_ApiException $e) {
           // echo $e; // possibility to handle API status codes $e->getCode()
           // exit("ApiException Exception\n");
           // die();
            $responseRecord['message'] = "ApiException Exception: ".$e->getMessage();
            
          } catch (WebSmsCom_HttpConnectionException $e) {
           // exit("HttpConnectionException caught: ".$e->getMessage()."HTTP Status: ".$e->getCode()."\n");die();
             $responseRecord['message'] = "HttpConnectionException caught: ".$e->getMessage();
          
          } catch (WebSmsCom_UnknownResponseException $e) {
            
           // exit("UnknownResponseException caught: ".$e->getMessage()."\n");
           //    $responseRecord['message'] =  "UnknownResponseException caught: ".$e->getMessage();
           //  die();
          } catch (Exception $e) {
            
              $responseRecord['message'] =  "Exception caught: ".$e->getMessage();
           // exit("Exception caught: ".$e->getMessage()."\n");die();
          }

          $responseRecord['receipient'] = $recipientAddressList;


          //self::_createLog('smsLog',$responseRecord,'info');
          //dd($responseRecord);
          return $responseRecord;

          
          }

        }

        public function login_send_otp($enID)
        {
            //dd("sdsfs");
            App::setLocale('de');
            $id = base64_decode(base64_decode($enID));
    
            $this->ViewData['moduleTitle']  = __('admin.TITLE_LOGIN_MODULE');
            $this->ViewData['moduleAction'] = __('admin.TITLE_LOGIN_MODULE');
            $this->ViewData['modulePath']   = $this->ModulePath.'login';
            $this->ViewData['user_id']   = $id;

            $this->ViewData['ordination'] = $this->OrdinationsModel
                                            ->where('status',1)
                                            ->get();

            return view($this->ModuleView.'otp', $this->ViewData);  
        }

        public function verify_otp(Request $request)
        {
            //dd($request->all());
            $errors = [];
            $data = [];
            $message = __('api.AUTH_INVALID_OTP');
            $status = false;

            $validator = Validator::make($request->all(), [
                    'user_id' => 'required',
                    'otp' => 'required|numeric',
                    ],
                    [
                      'user_id.required' => __('api.AUTH_PATIENT_ID_REQ'),
                      'otp.required' => __('api.AUTH_OTP_REQ'),     
                    ]);
            if($validator->fails()) 
            {     
              $errors[] = $validator->errors();
            }
            else
            {
                $data = [];
                $collection = $this->BaseModel->find($request->user_id);
                if(!empty($collection))
                {
                    $start = date('Y-m-d H:i:s', strtotime($collection->otp_created_at));
                    $start = new Carbon($start); 
                    
                    $end =  new Carbon(date('Y-m-d H:i:s', time()));
                    $diffInMinutes = $start->diffInMinutes($end); 
           
                    // if($diffInMinutes<=5)
                    // {
                        if(!empty($collection))
                        {
                            if($collection->login_otp==$request->otp)
                            {
                                session::put('is_updated',$collection->is_updated);
                                // check for valid username 
                                $credentials = [];
                                $credentials['email']         = $collection->email;
                                // $credentials['password']      = $collection->str_password;
                                // added by vijay 17/7/2024 
                                $credentials['password']      = self::decodeString($collection->str_password);
                                // end
                                $remember_me = !empty($request->remember) ? true : false;
                                //dd($credentials,$remember_me);
                                $url = route('admin.users.index');
                                if(!empty(Config('website_id')))
                                {
                                    $is_speciality = $this->OrdinationHasSpecialistModel->where('ordination_id',Config('ordination_id'))->whereNull('deleted_at')->count();
                                    if($is_speciality > 0)
                                    {
                                        $url = route('admin.dashboard');
                                    }
                                }                   
                
                                if (auth()->guard('admin')->attempt($credentials, $remember_me)) 
                                {
                                    $user = $this->BaseModel->where('email',$credentials['email'])->first();
                                    if (auth()->user()->roles->pluck('guard_name')->first() === 'admin') 
                                    {   
                                        if (auth()->user()->status) 
                                        {
                                            $language = 'en';
                                            $getlanguage = session::get('language');
                                            if(!empty($getlanguage)){
                                                $language = $getlanguage;
                                            }
                                            session(['locale' => $language]);
                                            //Set Lanuguage                        
                                            $updatePass = $this->BaseModel->find($user->id);
                                            $updatePass->str_password = null;
                                            $updatePass->save();
                                            session::put('country_code_sess',''); 

                                            //$this->JsonData['url'] = $url;
                                            $this->JsonData['url'] = url('admin/login');
                                            $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                                            $this->JsonData['msg'] = __('admin.ACCOUNT_SUCCESS');
                                        }
                                        else
                                        {
                                            auth()->logout();
                                            $this->JsonData['status'] = __('admin.RESP_ERROR');
                                            $this->JsonData['msg'] = __('admin.ERR_ACCOUNT_DEACTIVATE');
                                        }
                                    }
                                    else
                                    {
                                        auth()->logout();

                                        $this->JsonData['status'] = __('admin.RESP_ERROR');
                                        $this->JsonData['msg'] = __('admin.ERR_ACCOUNT_DEACTIVATE');
                                    }
                                }
                                else
                                {
                                    $this->JsonData['status'] = __('admin.RESP_ERROR');
                                    $this->JsonData['msg'] = __('admin.ERR_ACCOUNT_DEACTIVATE');
                                }
                            }
                            else
                            {
                                $this->JsonData['status'] = __('admin.RESP_ERROR');
                                $this->JsonData['msg'] = __('admin.AUTH_FAILED_OTP');
                            }
                        }
                    // }
                    // else
                    // {
                    //     $this->JsonData['url'] = url('/admin/login');
                    //     $this->JsonData['status'] = __('admin.RESP_ERROR');
                    //     $this->JsonData['msg'] = __('admin.AUTH_OTP_EXPIRED');
                    // }
                }
            }
            return response()->json($this->JsonData);exit;           
        }

        public function resendOtp($user_id)
        {   
            $flag = 0;
            $collection = $this->BaseModel->find($user_id);                 
            if(!empty($collection))
            {
                $country_code = session::get('country_code_sess'); 
                $collection = $this->_updateOtp($collection,$country_code);
                $flag = 1;
            }
            else
            {
                $flag = 0;
            }
            //dd($this->JsonData);
            return $flag = 1;;           
        }

        public function changePassword($user_id)
        {
            dd($user_id);
            App::setLocale('de');
            $this->ViewData['moduleTitle']  = __('admin.TITLE_FORGOT_PASSWORD_MODULE');
            $this->ViewData['moduleAction'] = __('admin.TITLE_FORGOT_PASSWORD_MODULE');
            $this->ViewData['modulePath']   = $this->ModulePath.'forgot.password';

            return view($this->ModuleView.'change-password', $this->ViewData);
        }

        public function logout()
        {
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            auth()->guard('admin')->logout();
          
            auth()->logout();
          
            return redirect('admin/login');
           
        }

    /*---------------------------------
    |   FORGOT PASSWORD 
    ------------------------------------------*/

        public function forgotPassword()
        {
            App::setLocale('de');
            $this->ViewData['moduleTitle']  = __('admin.TITLE_FORGOT_PASSWORD_MODULE');
            $this->ViewData['moduleAction'] = __('admin.TITLE_FORGOT_PASSWORD_MODULE');
            $this->ViewData['modulePath']   = $this->ModulePath.'forgot.password';

            return view($this->ModuleView.'forgot-password', $this->ViewData);
        } 
        
        public function forgotPasswordSubmit(ForgotPasswordRequest $request)
        {
            App::setLocale('de');
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.FAIL_FORGOT_PASSWORD_MATCH');

            $email = self::_validateUsername($request->email);
                
            $userCollection = $this->BaseModel->where('email',$email)->first();
            
            if (!empty($userCollection)) 
            {
                if (!$userCollection->status) 
                {
                    $this->JsonData['msg'] = __('admin.FAIL_FORGOT_PASSWORD_DISABLED');
                    return response()->json($this->JsonData);exit;
                }

                $userCollection->username = $userCollection->first_name." ".$userCollection->last_name;
                $token = $this->PasswordBroker->createToken($userCollection);

                $userCollection->url = url('/admin/reset-password/'.$token);

                try {

                    $result = Mail::to($userCollection->email)->send(new ForgotPasswordMail($userCollection,'admin'));

                    $post = $this->PasswordResetModel->create([
                        'email' => $userCollection->email,
                        'token' => $token
                    ]);

                    $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                    $this->JsonData['msg']      = __('admin.FORGOT_PASSWORD_STATUS');
                    $this->JsonData['url']      = route('admin.auth.login');
                } 
               catch(\Exception $e) {

                    $this->JsonData['exception'] = $e->getMessage();
                    return response()->json($this->JsonData);exit;

                }
            }

            return response()->json($this->JsonData);                    
        }


    /*---------------------------------
    |   RESET PASSWORD
    ------------------------------------------*/
   
        public function resetPassword($token)
        {
            App::setLocale('de');
            $this->ViewData['moduleTitle']  = __('admin.TITLE_RESET_PASSWORD_MODULE');
            $this->ViewData['moduleAction'] = __('admin.TITLE_RESET_PASSWORD_MODULE');
            $this->ViewData['modulePath']   = $this->ModulePath.'reset.password'; 
            
            $collection = $this->PasswordResetModel
                            ->where('token',$token)
                            ->where('created_at','>',Carbon::now()->subHours(24))
                            ->first();

            if(!empty($collection))
            {
                $this->ViewData['email'] = $collection->email; 
                $this->ViewData['token'] = $token;

                return view($this->ModuleView.'.reset-password', $this->ViewData);
            }
            else
            {
                return view($this->ModuleView.'.reset-token-expired', $this->ViewData);
            }
        }

        public function resetPasswordSubmit(ResetPasswordRequest $request, $token)
        {
            App::setLocale('de');
            $this->JsonData['status'] = __('admin.RESP_ERROR');
            $this->JsonData['msg']    = __('admin.FAIL_RESET_PASSWORD_STATUS_CHANGE');
            
            $isValidObject = $this->PasswordResetModel->where('token',$token)->first();
            if($isValidObject)
            {
                $collection = $this->BaseModel->where('email',$isValidObject->email)->first();
                // $this->BaseModel->where('id',$collection->id)->update(['password' => Hash::make($request->password),'str_password'=>$request->password]);
                $this->BaseModel->where('id',$collection->id)->update(['password' => Hash::make($request->password)]);
                $this->PasswordResetModel->where('token',$token)->delete();

                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['msg']      = __('admin.RESET_PASSWORD_STATUS');
                $this->JsonData['url']      = route('admin.auth.login');
            }
            return response()->json($this->JsonData);
        } 
    
    /*---------------------------------
    |   SUBTITUTE FUNCTIONS
    ------------------------------------------*/
        public function _validateUsername($email)
        {
            // dd($email);
            //$email = $username;
            // dd(filter_var($email, FILTER_VALIDATE_EMAIL));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
            {
                //dd('Testtttt');
                $userCollection = $this->BaseModel
                                        ->where('email',  $email)
                                        ->whereStatus(1)
                                        ->first(); 
                                        // dd($userCollection);

                if(empty($userCollection))
                {   
                    return response()->json($this->JsonData);exit;
                }
                /*if(!empty($userCollection) && !$userCollection->hasRole('super-admin'))
                {   // 
                    return response()->json($this->JsonData);exit;
                }*/
                
                $email = $userCollection->email;
            }

            return $email;
        }

        public function _applyOrDestroyRemember($remember_me, $request)
        {
            if ($remember_me) 
            {
                // removing database  record
                $this->RememberMeModel->where('user_id', auth()->user()->id)
                                        ->delete(); 

                // generating cokie
                $token = time('YmdHisa').auth()->user()->remember_token;
                $minutes = time() + (10 * 365 * 24 * 60 * 60);
                setcookie($this->rememberTitle,$token, $minutes);

                // register remember in database 
                $RememberMeModel = new $this->RememberMeModel;
                $RememberMeModel->user_id = auth()->user()->id;
                $RememberMeModel->username = $request->username;
                $RememberMeModel->password = $request->password;
                $RememberMeModel->remember_token = $token;
                $RememberMeModel->initial_login_date = Date('Y-m-d');
                $RememberMeModel->save();
            }
            else
            {
                if(!empty($_COOKIE[$this->rememberTitle]))
                {
                    // removing cookie
                    $remember_token = $_COOKIE[$this->rememberTitle];
                    setcookie($this->rememberTitle, null, -1);
                    unset($_COOKIE[$this->rememberTitle]);

                    // removing database  record
                    $this->RememberMeModel->where('remember_token', $remember_token)
                                        ->delete();                 
                }
            }  
        }
}