<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
// Hyn Tenancy imports (commented out)
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\ActivityLogModel;
use App\Models\PatientHasDeviceModel;
use App\Models\PatientsModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\AppointmentModel;
use App\Models\ExaminationsModel;
use App\Mail\AppointmentMail; 

use WebSmsCom_Client;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_TextMessage;
use WebSmsCom_ParameterValidationException; 
use WebSmsCom_AuthorizationFailedException;
use WebSmsCom_ApiException;
use WebSmsCom_HttpConnectionException;
use WebSmsCom_UnknownResponseException;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
use Mail;

use App\Mail\ReminderNotificationMail; //added on 4-june-24 (13-june-24)

use DateTime; //added on 5-july-24
use Stancl\Tenancy\Facades\Tenancy;


class SetReminderNotificationUnSent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Hyn Tenancy signature (commented out)
    // protected $signature = 'setreminderunset:daily {--website_id=}';
    
    // Stancl Tenancy signature
    protected $signature = 'setreminderunset:daily {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set reminder notification flag unset';

    // Hyn Tenancy properties (commented out)
    // /**
    //  * @var Connection
    //  */
    // private $connection;

    // /**
    //  * @var WebsiteRepository
    //  */
    // private $websites;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
                                 PatientsHasServiceReminderModel $PatientsHasServiceReminderModel,
                                PatientHasDeviceModel $PatientHasDeviceModel,
                                PatientsModel $PatientsModel,
                                ChannelsRemindersSettingModel $ChannelsRemindersSettingModel,
                                AppointmentModel $AppointmentModel,
                                ExaminationsModel $ExaminationsModel,
                                ActivityLogModel $ActivityLogModel
                                )
    {
        parent::__construct();
        // Hyn Tenancy initialization (commented out)
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
        $this->BaseModel  = $PatientsHasServiceReminderModel;
        $this->PatientHasDeviceModel = $PatientHasDeviceModel;
        $this->PatientsModel = $PatientsModel;
        $this->ChannelsRemindersSettingModel = $ChannelsRemindersSettingModel;
        $this->AppointmentModel = $AppointmentModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->ActivityLogModel  = $ActivityLogModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        //log::info("ReminderStatus-Handle");
        
        // Hyn Tenancy (commented out)
        // $website_id = $this->option('website_id');
        // log::info("website_id=in handle function=of SetReminderNotificationUnSent=====>");
        // log::info($website_id);
        // try
        // {
        //     // if(!empty($website_id) && $website_id!='0')
        //     // {
        //     //     $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
        //     //     $this->connection->set($website);
        //     //     self::_commandOperation($website_id);
        //     //     $this->connection->purge();
        //     // }
        //     $websites = $this->websites->query()->select('id')->get();
        //     if(isset($websites) && !empty($websites)){
        //         foreach($websites as $k=>$v){
        //             $website = $this->websites->query()->where('id', $v->id)->firstOrFail();
        //             $this->connection->set($website);
        //             self::_commandOperation($v->id);
        //             $this->connection->purge();
        //         }
        //     }
        // } 
        // catch (ModelNotFoundException $e) 
        // {
        //     throw new RuntimeException(
        //         sprintf(
        //             'The tenancy website_id=%d does not exist.',
        //             $website_id
        //         )
        //     );
        // }
        
        // Stancl Tenancy
        $tenant_id = $this->option('tenant_id');
        //log::info("tenant_id=in handle function=of SetReminderNotificationUnSent=====>");
        //log::info($tenant_id);
        
        try
        {
            if(!empty($tenant_id) && $tenant_id!='0')
            {
                self::_commandOperation($tenant_id);
                
                // Stancl tenancy cleanup
                tenancy()->end();
            }
            // else
            // {
            //     // Process all tenants if no specific tenant_id provided
            //     $tenants = \App\Models\Tenant::all();
            //     if(isset($tenants) && !empty($tenants)){
            //         foreach($tenants as $tenant){
            //             self::_commandOperation($tenant->id);
                        
            //             // Stancl tenancy cleanup for each tenant
            //             tenancy()->end();
            //         }
            //     }
            // }
        } 
        catch (ModelNotFoundException $e) 
        {
            throw new RuntimeException(
                sprintf(
                    'The tenancy tenant_id=%d does not exist.',
                    $tenant_id
                )
            );
        } 
    }

      public function _commandOperation($tenant_id)
    //public function _commandOperation($website_id)
    {
          //  log::info("tenant_id===>");
          // log::info($tenant_id);

        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
            //Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
            //Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }

            //log::info("In SetReminderNotificationUnSent _commandOperation function ..........");


            //log::info(date('Y-m-d'));
           
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            //log::info("yesterday===>");
            //log::info($yesterday);
       
            $collections =  DB::connection('tenant')->table('patient_has_service_reminder')
                        ->join('patients', 'patients.id' , '=', 'patient_has_service_reminder.patient_id')
                        ->join('examinations','examinations.id','patient_has_service_reminder.service_id')
                        ->where(function($query) use ($yesterday) {
                                    $query->where(function($query) use ($yesterday) {
                                        $query->whereDate('patient_has_service_reminder.reminder_date', '=', $yesterday);
                                             
                                    })
                                    ->orWhere(function($query) use ($yesterday) {
                                        $query->whereDate('patient_has_service_reminder.next_reminder_date', '=', $yesterday);
                                             
                                    });
                        })
                        ->where('patient_has_service_reminder.reminder_status','Set')
                        //->where('patients.id',47287) //live commented by vijay 4/4/2024
                        ->where('patient_has_service_reminder.is_sent',1) 
                        ->whereNull('patient_has_service_reminder.deleted_at') //added on 28-march-24
                        ->whereNull('patients.deleted_at')  
                        ->orderBy('patient_has_service_reminder.id','desc')
                        ->get(['patient_has_service_reminder.id as reminder_id','patient_has_service_reminder.reminder_date','patients.id as patient_id','patients.mobile_no','patients.age as patient_age','patients.country_code','patients.email','examinations.name as name','examinations.id as exam_id','patients.sendMail','patients.sendSMS',
                             'patient_has_service_reminder.appointment_id', 
                            'patient_has_service_reminder.status',  
                            'patient_has_service_reminder.reminder_status', 
                             'patient_has_service_reminder.service_id', 
                              'patient_has_service_reminder.type',  
                              'patient_has_service_reminder.next_reminder_date',
                       ]);

           // log::info("Send Notify before collections");
           // log::info($collections);


             // $currentDate = Date('d-m-Y H:i');    //commented on 8-apr-24  

            $currentDate = Date('d-m-Y');    

            //log::info("currentDate===>");  
            //log::info($currentDate);   

            if(!empty($collections))
            {
                foreach ($collections as $key => $value)
                {
                     //log::info('reminder_id');
                    // log::info($value->reminder_id);

                    $patientid= $value->patient_id;
                    $id= $value->reminder_id;

                    $responseRecord = DB::connection('tenant')
                          ->table('patient_has_service_reminder')
                          ->where('id',$id)
                          ->update(['is_sent'=>0,'sent_date'=>null]); 

   
                }//foreach
            }//if
    }//_commandOperation

  


}
