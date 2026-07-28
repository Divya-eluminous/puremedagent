<?php

namespace App\Console\Commands\Puremed; 

use Illuminate\Console\Command;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\AdminUserModel;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
class MenstruationCycleHasCycle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menstruation_cycle_has_cycles:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all menstruation_cycle_has_cycles data';

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
        $lastPatientId = DB::connection('puremed_puregyn')->table('menstruation_cycle_has_cycles')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('menstruation_cycle_has_cycles')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('menstruation_cycle_has_cycles')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $menstruation_cycle_id = DB::connection('puremed_puregyn')->table('menstruation_cycle')->where('migration_id',$value->menstruation_cycle_id)->pluck('id')->first();

                if(!empty($menstruation_cycle_id ))
                {

                    $data = [];
                    $data['migration_id'] = $value->id;
                    $data['menstruation_cycle_id'] = $menstruation_cycle_id;
                    $data['date'] = $value->date;                
                    $data['length'] = $value->length;               
                    $data['cycle'] = $value->cycle;

                    $tenant_patinet_id = DB::connection('puremed_puregyn')->table('menstruation_cycle_has_cycles')->insertGetId($data);
                }

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
