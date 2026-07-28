<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;

use Log;
use DB;
use DateTime;
use App\Models\PatientsModel;
use App\Models\PatientsHasServiceReminderModel;
use Mail;

class reminderTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'remindertest:cron {--website_id=}';
    protected $signature = 'remindertest:cron {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update remindertest  cron';

//    /**
//      * @var Connection
//      */
//     private $connection;

//     /**
//      * @var WebsiteRepository
//      */
//     private $websites;

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function __construct()
    {
        parent::__construct();
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
    //     log::info("in update service reminder function..handle........");
    //     $website_id = $this->option('website_id');
    //    // dd($website_id);

    //     try
    //     {
    //         if(!empty($website_id) && $website_id!='0')
    //         {
    //             $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
    //             $this->connection->set($website);
    //             self::_commandOperation($website_id);
    //             $this->connection->purge();
    //         }
    //     } 
    //     catch (ModelNotFoundException $e) 
    //     {
    //         throw new RuntimeException(
    //             sprintf(
    //                 'The tenancy website_id=%d does not exist.',
    //                 $website_id
    //             )
    //         );
    //     } 
    // Stancl Tenancy
    $tenant_id = $this->option('tenant_id');
    log::info("tenant_id=in handle function=of ReminderTestCommand=====>");
    log::info($tenant_id);

    try
    {
        if(!empty($tenant_id) && $tenant_id!='0')
        {
            self::_commandOperation($tenant_id);
            
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
    }//handle

     public function handle_old()
    {
        log::info("in test update service reminder function..........");
       // dump('innnnnnnnnnnnn');

    }//handle

     /*-------------------------------------- 
      | Actual functionality
    --------------------------------------*/
    public function _commandOperation($tenant_id)
    {
       // dd('innnnnnnnnnnnn');
        log::info("in test update service reminder function.._commandOperation........");
        log::info("tenant_id===>");
        log::info($tenant_id);

        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
            Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
            Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }
      
    }//handle


}
