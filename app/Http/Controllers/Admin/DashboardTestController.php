<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

//Models
use App\Models\AdminUserModel; 
use App\Models\RosterModel; 
use App\Models\RosterHasDatesModel;
use App\Models\AppointmentModel; 
use App\Models\PatientsModel;
use App\Models\GoogleColorsModel;
use App\Models\DashboardNoticeModel;
use App\Models\PatientHasDocumentsModel;
use App\Models\AppointmentTypesModel;
use App\Models\AppointmentHasNotificationModel;
use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\ActivityLogModel;
use App\Models\AppointmentHasExaminationsModel;
use App\Models\ExaminationsModel;
use App\Models\SpecialistDocumentsModel;
use App\Models\SpecialistModel;
use App\Models\ExaminationsHasMultipleDocumentListModel;
use App\Models\CheckListHasSelectedQuestionModel;
use App\Models\ExaminationsHasMultipleCheckListModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\PatientsHasServiceControlReminderModel;
use App\Models\PatientHasReminder;
use Illuminate\Support\Facades\Log; 
use App\Models\EventTypeHasExaminationsModel;
use App\Models\CheckListHasHeadingSectionModel;
use App\Models\HeadingSectionHasQuestionModel;
use App\Models\CheckListModel;
use App\Models\RosterHasWeeksHasTimeFramesModel;
use App\Models\DeletedAppointmentTrackModel;

use App\Traits\GeneralTrait; 
use Validator;
use DateTime;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Exception;

use Hash;
use Mail;
use DB;
use Auth;
use Config;
use Carbon\Carbon;
 ini_set('memory_limit', '-1');
use Illuminate\Support\Facades\Lang;

// Request
use App\Http\Requests\Admin\AppointmentRequest;

class DashboardTestController extends Controller
{
    use GeneralTrait;
    private $BaseModel;
    public function __construct(
                                AdminUserModel $AdminUserModel,
                                AppointmentModel $AppointmentModel,
                                PatientsModel $PatientsModel,
                                GoogleColorsModel $GoogleColorsModel,
                                AppointmentTypesModel $AppointmentTypesModel,
                                AppointmentHasNotificationModel $AppointmentHasNotificationModel,
                                ActivityLogModel $ActivityLogModel,
                                RosterModel $RosterModel,
                                DashboardNoticeModel $DashboardNoticeModel,
                                RosterHasDatesModel $RosterHasDatesModel,
                                PatientHasDocumentsModel $PatientHasDocumentsModel,
                                AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel,
                                CheckListHasHeadingSectionModel $CheckListHasHeadingSectionModel,
                                AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
                                ExaminationsModel $ExaminationsModel,
                                HeadingSectionHasQuestionModel $HeadingSectionHasQuestionModel,
                                SpecialistDocumentsModel $SpecialistDocumentsModel,
                                CheckListModel $CheckListModel,
                                SpecialistModel $SpecialistModel,
                                ExaminationsHasMultipleDocumentListModel $ExaminationsHasMultipleDocumentListModel,
                                CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
                                ExaminationsHasMultipleCheckListModel $ExaminationsHasMultipleCheckListModel,
                                PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
                                PatientsHasServiceControlReminderModel $PatientsHasServiceControlReminderModel,
                                PatientHasReminder $PatientHasReminder,
                                EventTypeHasExaminationsModel $EventTypeHasExaminationsModel,
                                RosterHasWeeksHasTimeFramesModel $RosterHasWeeksHasTimeFramesModel,
                                DeletedAppointmentTrackModel $DeletedAppointmentTrackModel
                            )
    {
        $this->ViewData             = []; 
        $this->JsonData             = [];
        $this->todosByDate          = [];
        $this->BaseModel            = $AppointmentModel; 
        $this->AdminUserModel       = $AdminUserModel;
        $this->AppointmentModel     = $AppointmentModel;
        $this->PatientsModel        = $PatientsModel;
        $this->GoogleColorsModel    = $GoogleColorsModel;
        $this->AppointmentTypesModel = $AppointmentTypesModel;
        $this->AppointmentHasNotificationModel  = $AppointmentHasNotificationModel;
        $this->ActivityLogModel                 = $ActivityLogModel;
        $this->RosterModel             = $RosterModel;
        $this->DashboardNoticeModel    = $DashboardNoticeModel;
        $this->RosterHasDatesModel     = $RosterHasDatesModel;
        $this->PatientHasDocumentsModel= $PatientHasDocumentsModel;
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->SpecialistDocumentsModel = $SpecialistDocumentsModel;
        $this->SpecialistModel = $SpecialistModel;
        $this->ExaminationsHasMultipleDocumentListModel = $ExaminationsHasMultipleDocumentListModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;
        $this->ExaminationsHasMultipleCheckListModel = $ExaminationsHasMultipleCheckListModel;
        $this->PatientsHasServiceReminderModel = $PatientsHasServiceReminderModel;
        $this->CheckListModel = $CheckListModel;
        $this->PatientsHasServiceControlReminderModel = $PatientsHasServiceControlReminderModel; 
        $this->PatientHasReminder = $PatientHasReminder; 
        $this->EventTypeHasExaminationsModel = $EventTypeHasExaminationsModel;
        $this->CheckListHasHeadingSectionModel = $CheckListHasHeadingSectionModel;
        $this->HeadingSectionHasQuestionModel = $HeadingSectionHasQuestionModel;
        $this->RosterHasWeeksHasTimeFramesModel = $RosterHasWeeksHasTimeFramesModel;
        $this->DeletedAppointmentTrackModel = $DeletedAppointmentTrackModel;

        $this->ModuleTitle  = __('admin.TITLE_DASHBOARD');  
        $this->ModuleView   = 'admin.dashboard.';
        $this->ModulePath   = 'admin.dashboard';
        
        $this->patientText      = 'Patient';
        $this->doctorText       = 'Arzt';
        $this->appointmentText  = 'Typ';
        $this->startDateText    = 'Beginn';
        $this->endDateText      = 'Ende';
        $this->notesText        = 'Notizen';
        $this->services         = 'Services';

            $this->tokenPath = public_path('google-calendar/token.json');
            $this->tokenPath = '/opt/app-shared/php/data/storage/app/google-calendar/token.json';
    }

    public function index()
    {


//     echo memory_get_usage() . "\n";
//         $guzzle = new \GuzzleHttp\Client;
// $stream = $guzzle->get(
//     'https://kpbs.streamguys1.com/?ck=1579203064304', 
//     ['stream' => true]
// )->getBody();

//     echo memory_get_usage() . "\n";
// die;

        // try {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://reqres.in/api/users?page=1', array('debug' => true,'stream' => true));
        echo $response->getBody();
    echo memory_get_usage() . "\n";
            die;
//         try {
//     $request = $this->guzzle->request('get', $url, array(
//         'debug'     =>  true,
//         'headers'   =>  array(
//             'User-Agent' => 'sortitoutsi',
//             'Accept' => 'application/json',
//             'Content-Type' => 'application/json',
//             'Authorization' => 'Bearer '.$access_token
//         )
//     ));
// } catch (ClientException $e) {
//     die((string)$e->getResponse()->getBody());
// }


    echo memory_get_usage() . "\n";
            $client = new \GuzzleHttp\Client(['base_uri' => 'https://reqres.in/']);
            $response = $client->request('GET', '/api/users?page=1', array('debug' => true,'stream' => true));
            echo $response->getBody();
    echo memory_get_usage() . "\n";
    die;
try {
    echo memory_get_usage() . "\n";
    // die;
            $client = new \GuzzleHttp\Client(['base_uri' => 'https://reqres.in/']);
            $response = $client->request('GET', '/api/users?page=1', array('debug' => true));
            echo $response->getBody();
    echo memory_get_usage() . "\n";

} catch (GuzzleHttp\Exception\ServerException $e) {
    print_r((string)$e->getResponse()->getBody());
}
            unset($client);
        // }
        // catch((\Exception $e) {
        //     echo 'hello catch <br>';
        //     print_r($e);
        // }
        dd('heloooo');


        echo 'heloooo';
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://reqres.in/api/users?page=1",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                // Set Here Your Requesred Headers
                'Content-Type: application/json',
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        print_r($response);


        dd('Hello ');


        // /*--------------------------------------
        //     | Google Client
        //     ------------------------------*/
        //     $client = new Google_Client();
        //     $client->setApplicationName('Puregyn');
        //     $client->setAuthConfig(public_path('google-calendar/client_secret.json'));
        //     $client->addScope(Google_Service_Calendar::CALENDAR);

        //     // $curl = curl_init();
        //     // curl_setopt_array($curl, array(
        //     //     CURLOPT_URL => "",
        //     //     CURLOPT_RETURNTRANSFER => true,
        //     //     CURLOPT_ENCODING => "",
        //     //     CURLOPT_SSL_VERIFYPEER => false,
        //     //     CURLOPT_TIMEOUT => 30000,
        //     //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //     //     CURLOPT_CUSTOMREQUEST => "GET",
        //     //     CURLOPT_HTTPHEADER => array(
        //     //         // Set Here Your Requesred Headers
        //     //         'Content-Type: application/json',
        //     //     ),
        //     // ));
        //     // $response = curl_exec($curl);
        //     // $err = curl_error($curl);
        //     // curl_close($curl);
        //     // $client->setHttpClient($response);

        //     $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false, CURLOPT_PROXY => '')));
        //     $client->setHttpClient($guzzleClient);

        //     //For Offline Access
        //     $client->setAccessType('offline');
        //     $client->setApprovalPrompt("force");//force //select_account consent

        //     $this->client = $client;


        
        // print_r($this->client);

        dd('on Dashboard Controller');
    }

    public function googleClient()
    {
        /*--------------------------------------
            | Google Client
            ------------------------------*/
            $client = new Google_Client();
            $client->setApplicationName('Puregyn');
            $client->setAuthConfig(public_path('google-calendar/client_secret.json'));
            $client->addScope(Google_Service_Calendar::CALENDAR);
            // $guzzleClient = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);
            $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false, CURLOPT_PROXY => '')));
            $client->setHttpClient($guzzleClient);
            //For Offline Access
            $client->setAccessType('offline');
            $client->setApprovalPrompt("force");//force //select_account consent

            $this->client = $client;
    }

}