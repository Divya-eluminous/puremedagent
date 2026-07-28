<?php
return [
	
	/*--------------------------------------------------------
	|  General Constants
	------------------------------------*/
		// 'ADMINEMAIL' 	=> 'puregyntest@gmail.com',//commented on 22-july-25
	    'ADMINEMAIL' 		=> 'app@puremed.biz',//changed on 22-july-25
		'ADMINFROMNAME'     => 'ADMIN',
		'SUPERADMINROLENAME'=> 'super-admin',
		'SITENAME'     		=> 'Puregyn',

		'FROMADDRESS' => 'app@puregyn.at',
		'FROMNAME'    => 'Puregyn',

		//Test Mail
		//'ADMINEMAIL' 		=> 'eluminous_se42@eluminoustechnologies.com',

	/*--------------------------------------------------------
	|  SMS GATEWAY
	------------------------------------*/
		'SMS_URL' 	   => 'https://api.websms.com/',
		'SMS_TOKEN'    => '8970e079-37cc-4e3e-a41f-67c6cc405e17',
		// 'SMS_USERNAME' => 'office@lucymarx.at',
		// 'SMS_PASSWORD' => 'e3!o9!?1',
		// 'SMS_SENDERID' => '',
		
	/*--------------------------------------------------------
	|  API CONSTANTS
	------------------------------------*/  
		'API_VERSION_ONE'=> 'v1',
		'APP_TOKEN' => env('APP_TOKEN', 'admin123456'),
    	'API_URL' => env('APP_URL', 'http://localhost/puregyn').'/api/v1/',
		/*'APP_ADMIN' => env('APP_ADMIN', 'admin'),
    	'APP_TOKEN' => env('APP_TOKEN', 'admin123456'),
    	'HTTP_UNAUTHORIZED' => '401',
	    'SUCCESS' => '200',
	    'UNSUCCESS' => '404',	    
	    'STATUS_SUCCESS' => 'Success',

	    'STATUS_UNSUCCESS' => 'Failed',	 
		'OTP_VALID_MINS' => 15,
		'STATUS_ACTIVE'=>1,
    	'STATUS_INACTIVE'=>0,
    	'API_PAGE_LIMIT'=>10,
*/
    	//Test Account
    	//'ONESIGNAL_APP_ID' => '59844fb6-53c1-43aa-9da2-6ceea23b166a',
		//'ONESIGNAL_REST_API_KEY' => 'OTEyZWExMWQtY2Q3ZC00MDg3LWE2OGYtNWY5YmJhM2M4YzQ1',

		//'ONESIGNAL_APP_ID' => '98822efb-248a-4d41-8389-2b351e12635b',
		'ONESIGNAL_APP_ID' => 'b1c86b4b-388c-415b-b7e2-c82ce7032b42',
		//'ONESIGNAL_REST_API_KEY' => 'NGFjMzUyMTctMDZmYi00YWE5LTg4ZGItNzJjMDRkODBkMmFk',
		'ONESIGNAL_REST_API_KEY' => 'ZGZhNGVlMzktODU3OC00NDBkLWIyNmYtODRiMWQ5NTFiOWRm',

		'speciality_exist' => '0',
		'current_ordination' => '0',
		'website_id' =>'',
		'PUREGYN_LINK' => 'http://www.puregyn.at/',


];

?>