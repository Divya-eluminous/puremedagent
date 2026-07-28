<?php
namespace App\Http\Controllers\Api\v3;

use App\Http\Controllers\Controller;
use Hash;
use Illuminate\Support\Facades\Log;

use WebSmsCom_Client;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_TextMessage;
use WebSmsCom_ParameterValidationException; 
use WebSmsCom_AuthorizationFailedException;
use WebSmsCom_ApiException;
use WebSmsCom_HttpConnectionException;
use WebSmsCom_UnknownResponseException;

class BaseController extends Controller {

   /*-----------------------------------
    |   Output Json Response for all apis
    -------------------------------------------------*/
      protected function _sendResult($message,$data,$errors = [],$status = true)
      {
        // $errorCode = $status ? 200 : 422;
        $result = [
            "message" => $message,
            "status" => $status,
            "data" => $data,
            "errors" => $errors
        ];
        return response()->json($result);  
      } 

  /*-----------------------------------
    |   Calculate distance in kilometer
    -------------------------------------------------*/
      public function _calculateDistance($lat1, $lon1, $lat2, $lon2, $unit='K') 
      {

        if (($lat1 == $lat2) && ($lon1 == $lon2)) 
        {
          return 0;
        }
        else 
        {
          $theta = $lon1 - $lon2;
          $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
          $dist = acos($dist);
          $dist = rad2deg($dist);
          $miles = $dist * 60 * 1.1515;
          $unit = strtoupper($unit);

          if ($unit == "K") {
           return ($miles * 1.609344);
          } else if ($unit == "N") {
            return ($miles * 0.8684);
          } else {
            return $miles;
          }
        }
     }

    /*-----------------------------------
    |  Send sms
    -------------------------------------------------*/

      public function _sendSms($phones,$text){

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
            

            // catch everything that's not a successfully sent message
          } catch (WebSmsCom_ParameterValidationException $e) {
             $responseRecord = array(
                                    'error' => 1 ,
                                    'code'=>'1',
                                    'message' => "ParameterValidationException caught: ".$e->getMessage()
                                    );
            //exit("ParameterValidationException caught: ".$e->getMessage()."\n");
            
          } catch (WebSmsCom_AuthorizationFailedException $e) {
          //  exit("AuthorizationFailedException caught: ".$e->getMessage()."\n");
            $responseRecord = array(
                                    'error' => 1 ,
                                    'code'=>'1',
                                    'message' => "AuthorizationFailedException caught: ".$e->getMessage()
                                    );
          
          } catch (WebSmsCom_ApiException $e) {
           // echo $e; // possibility to handle API status codes $e->getCode()
           // exit("ApiException Exception\n");
            $responseRecord['message'] = "ApiException Exception: ".$e->getMessage();
            
          } catch (WebSmsCom_HttpConnectionException $e) {
           // exit("HttpConnectionException caught: ".$e->getMessage()."HTTP Status: ".$e->getCode()."\n");
             $responseRecord['message'] = "HttpConnectionException caught: ".$e->getMessage();
          
          } catch (WebSmsCom_UnknownResponseException $e) {
            
           // exit("UnknownResponseException caught: ".$e->getMessage()."\n");
              $responseRecord['message'] =  "UnknownResponseException caught: ".$e->getMessage();
            
          } catch (Exception $e) {
            
              $responseRecord['message'] =  "Exception caught: ".$e->getMessage();
            //exit("Exception caught: ".$e->getMessage()."\n");
          }

          $responseRecord['receipient'] = $recipientAddressList;


          self::_createLog('smsLog',$responseRecord,'info');

          return $responseRecord;

          
          }

      }

      public function _sendSmsUsingCurl($recipientAddressList,$text){

        if(!empty($recipientAddressList) && !empty($text)){
                                     
            $url        = config('constants.SMS_URL'); 
            $token      = config('constants.SMS_TOKEN');
            $authorization = "Authorization: Bearer ".$token; // Prepare the authorisation token

            $messageContent       = urlencode('test message from api please ignore');
            $recipientAddressList = array("436649699060"); 

            // $username   = config('constants.SMS_USERNAME');
            // $password   = config('constants.SMS_PASSWORD');

            // Prepare data for POST request
            $data = array('recipientAddressList' => $recipientAddressList, 'messageContent' => $messageContent,'maxSmsPerMessage'=>1,'test'=>false);
            print_r($data);

           // $header = array("Content-Type:application/json", "Accept:application/json");

            $ch = curl_init($url);
           // curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch,CURLOPT_HTTPAUTH,  CURLAUTH_BASIC);

            //curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
           // curl_setopt($ch, CURLOPT_HEADER, true);
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=UTF-8', "Cache-Control: no-cache","Expect:",$authorization)); // Inject the token into the header
           // curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch,CURLOPT_FRESH_CONNECT , true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, "username=".$username."&password=".$password."&recipientAddressList=".$recipientAddressList."messageContent=".$messageContent);//
            // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

            //get response
            $response = curl_exec($ch);
            $errorMessage = curl_error($ch);

            curl_close($ch);

            var_dump($response);
            exit();
            $response = explode("&", $response);
           // print_r($response);

            $statusCode = explode("=", $response[0]);
            $statusMessage = explode("=", $response[1]);
           //  echo $response['statusCode'];
            // print_r($statusCode);
            // exit();

            if($errorMessage){
                $responseRecord = array('error' => 1 ,'code'=>'1', 'message' => $errorMessage);
            }
            else if($statusCode[1] == 2000 && $statusMessage[1]=='OK'){
              $responseRecord = array('error' => 0 ,'code'=>'2000', 'message' => 'Success');
            }
            else{
              $responseRecord = array('error' => 0 ,'code'=> $statusCode[1], 'message' => $statusMessage[1]);
            }

            print_r($responseRecord);
            exit();

            return $responseRecord; 

        }else{
            $errorMessage = "Mobile Number and Message fields are required";
            return array('error' => 1  ,'code'=>'1', 'message' => $errorMessage);
        }
        
      }  

/*-----------------------------------
 |  Save Log
 -------------------------------------------------*/
    public function _createLog($name,$data,$type='info') 
    {
      // config(['logging.channels.api.path' => storage_path('logs/api/api_'.date('Y-m-d').'.log')]);

      config(['logging.channels.api.path' => '/opt/app-shared/php/data/storage/logs/api/api_'.date('Y-m-d').'.log']);
      //Log::channel('api')->$type($name,array($data));
    } 
}
