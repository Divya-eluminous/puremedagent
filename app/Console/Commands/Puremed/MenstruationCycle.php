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
class MenstruationCycle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menstruation_cycle:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all menstruation_cycle data';

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
        $lastPatientId = DB::connection('puremed_puregyn')->table('menstruation_cycle')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('menstruation_cycle')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('menstruation_cycle')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $patient_id = DB::connection('puremed_puregyn')->table('patients')->where('migration_id',$value->patient_id)->pluck('id')->first();

                if(!empty($patient_id ))
                {

                    $data = [];
                    $data['migration_id'] = $value->id;                  
                    $data['patient_id'] = $patient_id;                
                    $data['latest_date'] = $value->latest_date;               
                    $data['latest_length'] = $value->latest_length;                   
                    $data['created_at']  = $value->created_at;
                    $data['updated_at'] = $value->updated_at; 
                    $data['deleted_at'] = '';

                    $tenant_patinet_id = DB::connection('puremed_puregyn')->table('menstruation_cycle')->insertGetId($data);
                }

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
