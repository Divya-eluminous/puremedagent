<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
// Hyn Tenancy imports (commented out)
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\AppointmentModel;
use App\Models\AppointmentHasQueueNumberModel;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
use Stancl\Tenancy\Facades\Tenancy;
class AppointmentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Hyn Tenancy signature (commented out)
    // protected $signature = 'appointmentStatus:update {--website_id=}';
    
    // Stancl Tenancy signature
    protected $signature = 'appointmentStatus:update {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update appoitment status';

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
                                AppointmentModel $AppointmentModel,
                                AppointmentHasQueueNumberModel $AppointmentHasQueueNumberModel
                            )
    {
        parent::__construct();
        // Hyn Tenancy initialization (commented out)
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
        $this->BaseModel  = $AppointmentModel; 
        $this->AppointmentHasQueueNumberModel  = $AppointmentHasQueueNumberModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
       // Log::info("AppointmentStatus handle function start");

        // Hyn Tenancy (commented out)
        // $website_id = $this->option('website_id');
        // try
        // {
        //     if(!empty($website_id) && $website_id!='0')
        //     { 
        //         $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
        //         $this->connection->set($website);
        //         self::tenantHandle($website_id);
        //         $this->connection->purge();
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
        try
        {
            if(!empty($tenant_id) && $tenant_id!='0')
            { 
                self::tenantHandle($tenant_id);
                
                // Stancl tenancy cleanup
                tenancy()->end();
            }
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
        //Log::info("AppointmentStatus handle function end");
    }

    public function tenantHandle($tenant_id)
    {
        //Log::info(" in AppointmentStatus tenantHandle cron...");
        
        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
           // Log::info("Found tenant: " . $tenant->ordination_name);
            // Initialize tenant context
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
            //Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }
        
        $collections =  DB::connection('tenant')->table('appointment')
                                ->orderBy('start_date','ASC')
                                ->where('appointment_status','!=','Fertig') 
                                ->where('appointment_status','!=','Vermisst')
                                ->orWhere('appointment_status',NULL)
                                ->get(); 

        $Aktuell_time = date("Y-m-d H:i:s",time());

        //Log::info($collections);

        $errorMessages = []; // collect all errors here
        $ordinationName = $tenant->ordination_name ?? '';

        if(!empty($collections))
        {
            foreach ($collections as $collection) 
            {      

                try 
                {
                    $status = null; //Added on 13-nov-25

 
                    $start_date = date('Y-m-d',strtotime($collection->start_date));
                    // $start_date = '2021-01-28';
                    //log::info($collection->id);
                    if($start_date > date('Y-m-d'))
                    {
                          //  Log::info('in if part');

                        $status = 'Geplant';
                    }
                    elseif($start_date < date('Y-m-d'))
                    {            

                           // Log::info('in else if part');
                          //   Log::info($collection->id);

                        $sqlQuery= ' Select * from appointment_has_queue_number where appointment_id ='.$collection->id;
                        $get_status = collect(DB::connection('tenant')->select($sqlQuery));

                        //  Log::info($get_status);

                        if(count($get_status) >0)
                        {
                            //  Log::info('if get status count is greater than 0');

                            //  Log::info($get_status);

                            if($get_status[0]->deleted_at!='')
                            {
                               $status = 'Fertig';
                            } 
                            else 
                            {
                                $status = 'Vermisst';
                            }//else condition added on 19-nov-25 for status  
                        }
                        else {
                           //  Log::info('count is less than 0');
                           if($collection->appointment_status == 'Aktuell')
                            {
                                $status = 'Fertig';
                            }
                            else {
                                $status = 'Vermisst';
                            }
                        }
                    }
                    else {

                       //   Log::info('in else part');

                        $sqlQuery= ' Select * from appointment_has_queue_number where appointment_id ='.$collection->id;
                        $get_status = collect(DB::connection('tenant')->select($sqlQuery));
                        if(count($get_status) >0)
                        {                       
                            if($get_status[0]->called_status == '1')
                            {
                                if(is_null($get_status[0]->deleted_at))
                                {
                                   $status = 'Aktuell'; 
                                }
                                else {
                                   $status = 'Fertig';
                                }  
                            }
                            else {
                                $status = 'Heute';
                            }
                        }
                        else {
                           if($collection->appointment_status == 'Aktuell')
                            {
                                $status = 'Aktuell';
                            }
                            else {
                                $status = 'Heute';
                            }  
                        }
                    }

                    //Log::info($collection->id);
                    //Log::info($status);

                    //start Added on 12-nov-25
                    if (!isset($status) || empty($status)) {
                        throw new \Exception("Undefined status for appointment ID {$collection->id}");
                    }    

                    //end Added on 12-nov-25

                    DB::connection('tenant')->table('appointment')->where('id',$collection->id)->update(['appointment_status'=>$status]);

                }
                catch (\Throwable $e) {
                    Log::error("Error in AppointmentStatus for ordination {$ordinationName}, appointment ID {$collection->id}: " . $e->getMessage());

                    $errorMessages[] = "Appointment ID {$collection->id}: " . $e->getMessage();


                }//catch Added on 13-nov-25

                // log::info($start_date."=".$collection->id."= updated with=".$status);
                // log::info("appointment status running".$collection->id."= updated with=".$status);


            }//foreach    


            // Send single email after loop if any errors occurred added on 13-nov-25
            if (!empty($errorMessages)) {

                // $emailBody = "AppointmentStatus Cron Errors for tenant ID {$tenant_id}\n\n" . implode("\n", $errorMessages);

                $emailBody = "AppointmentStatus Cron Errors for ordination : {$ordinationName}\n\n";

                try {
                    \Mail::raw($emailBody, function ($message) {
                        $message->from('no-reply@puregyn.com', 'AppointmentStatus Cron');
                        $message->to(['ph@lucymarx.at'])
                                ->bcc([
                                    'eluminous_se64@eluminoustechnologies.com',
                                    'eluminous_se80@eluminoustechnologies.com',
                                    'ashish.panpatil@eluminoustechnologies.com'
                                ])
                                ->subject('AppointmentStatus Cron - Error Summary');
                    });
                } catch (\Throwable $mailError) {
                    Log::error("Failed to send consolidated error email: " . $mailError->getMessage());
                }
            }//if error message
        }//if
    }//public


}
