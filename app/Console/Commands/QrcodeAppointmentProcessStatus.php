<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\AppointmentModel;
use App\Models\AppointmentHasQueueNumberModel;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
class QrcodeAppointmentProcessStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'QrcodeAppointmentProcessStatus:update {--website_id=}';
    protected $signature = 'QrcodeAppointmentProcessStatus:update {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update qucode appoitment process status';

    //  /**
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
    }

    public function tenantHandle($tenant_id)
    {
        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
            Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
            Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }

        $currentDay = date('Y-m-d', strtotime(date('Y-m-d')));

        $collections = DB::connection('tenant')->table('appointment')
            ->where('qrcode_process_status', 1)
            // ->where(function ($query) use ($currentDay) {
            //     $query->whereNull('appointment_status')
            //         ->orWhere('appointment_status', '');
            // })
            ->whereRaw('DATE(start_date) = ?', [$currentDay])
            ->get();

        if(!empty($collections))
        {
            foreach ($collections as $collection) 
            {        
                DB::connection('tenant')->table('appointment')->where('id',$collection->id)->update(['appointment_status'=> 'Aktuell']);
            }    
        }
    }
}
