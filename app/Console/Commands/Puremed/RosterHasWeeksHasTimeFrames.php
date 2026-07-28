<?php

namespace App\Console\Commands\Puremed; 

use Illuminate\Console\Command;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
class RosterHasWeeksHasTimeFrames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roster_has_weeks_has_time_frames:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all roster has dates data';

     /**
     * @var Connection
     */
    private $connection;

    /**
     * @var WebsiteRepository
     */
    private $websites;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();   
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $lastPatientId = DB::connection('puremed_puregyn')->table('roster_has_weeks_has_time_frames')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('roster_has_weeks_has_time_frames')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('roster_has_weeks_has_time_frames')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $roster_id = DB::connection('puremed_puregyn')->table('roster')->where('migration_id',$value->roster_id)->pluck('id')->first();

               
                $data = [];
                $data['migration_id'] = $value->id;
                $data['roster_id'] = $roster_id;
                $data['week_day_id'] = $value->week_day_id;                
                $data['time_frame'] = $value->time_frame; 
                $data['created_at']  = $value->created_at;
                $data['updated_at'] = $value->updated_at; 

                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('roster_has_weeks_has_time_frames')->insertGetId($data);

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
