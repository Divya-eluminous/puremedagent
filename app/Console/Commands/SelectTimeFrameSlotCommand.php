<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
// Hyn Tenancy imports (commented out)
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\RosterHasWeeksHasTimeFramesModel;
use Illuminate\Support\Facades\Log;
use DB;
use Stancl\Tenancy\Facades\Tenancy;

class SelectTimeFrameSlotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Hyn Tenancy signature (commented out)
    // protected $signature = 'SelectTimeFrameSlotCommand:command {--website_id=}';
    
    // Stancl Tenancy signature
    protected $signature = 'SelectTimeFrameSlotCommand:command {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Time Frame slot getting free';

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
                                RosterHasWeeksHasTimeFramesModel $RosterHasWeeksHasTimeFramesModel
                                )
    {
        parent::__construct();
        // Hyn Tenancy initialization (commented out)
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
        $this->RosterHasWeeksHasTimeFramesModel = $RosterHasWeeksHasTimeFramesModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       // log::info("SelectTimeFrameSlotCommand function handle start");

        // Hyn Tenancy (commented out)
        // $website_id = $this->option('website_id');
        // try
        // {
        //     if(!empty($website_id) && $website_id!='0')
        //     {
        //         $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
        //         $this->connection->set($website);
        //         self::_commandOperation($website_id);
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
        //log::info("SelectTimeFrameSlotCommand function handle end");
    }

    public function _commandOperation($tenant_id)
    //public function _commandOperation($website_id)
    {
       //log::info("...............SelectTimeFrameSlotCommand _commandOperation............");
       
       // Stancl Tenancy - Get tenant and initialize context
       $tenant = \App\Models\Tenant::find($tenant_id);
       if($tenant) {
           //Log::info("Found tenant: " . $tenant->ordination_name);
           tenancy()->initialize($tenant);
           config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
           //Log::info("Tenant context initialized for: " . $tenant->ordination_name);
       }

        $getAllSelectedSlotRecord = DB::connection('tenant')
                                    ->table('roster_has_weeks_has_time_frames')
                                    ->where('time_frame_flag','1')
                                    ->whereDate('time_frame_flag_date',date('Y-m-d'))
                                    ->get();
       
        foreach($getAllSelectedSlotRecord as $key=>$value)
        {
           
            $teleconferce_end_time = $value->time_frame_flag_date;
            $end_time = date('Y:m:d H:i:s');
            $teleconferce_end_time = strtotime($teleconferce_end_time.' - 5 minute');
            $end_time = strtotime($end_time);
            if($teleconferce_end_time <= $end_time)
            {
                DB::connection('tenant')
                ->table('roster_has_weeks_has_time_frames')
                ->where('id',$value->id)->update(['time_frame_flag'=>'0']);
            }   
        }        
    }
}