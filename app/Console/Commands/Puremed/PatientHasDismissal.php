<?php

namespace App\Console\Commands\Puremed; 

use Illuminate\Console\Command;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\AdminUserModel;
use App\Models\RosterModel; 
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
class PatientHasDismissal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients_has_dismissal:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all appointment data';

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
        $lastPatientId = DB::connection('puremed_puregyn')->table('patients_has_dismissal')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('patients_has_dismissal')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('patients_has_dismissal')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $patient_id = DB::connection('puremed_puregyn')->table('patients')->where('migration_id',$value->fk_patient_id)->pluck('id')->first();
                

                $fk_dismissal_id = DB::connection('puremed_puregyn')->table('dismissal')->where('id',$value->fk_dismissal_id)->pluck('id')->first();

                $data = [];
                $data['migration_id'] = $value->id;
                $data['fk_patient_id'] = $patient_id ?? '';                
                $data['appointment_id'] = $appointment_id ?? '';
                $data['fk_dismissal_id'] = $fk_dismissal_id ?? ''; 
                $data['dismissal_flag'] = $value->dismissal_flag; 
                $data['type'] = $value->type ?? '';                
                $data['status'] = $value->status;               
                $data['created_at']  = $value->created_at;
                $data['updated_at'] = $value->updated_at; 
                $data['deleted_at'] = $value->deleted_at; 
                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('patients_has_dismissal')->insertGetId($data);

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
