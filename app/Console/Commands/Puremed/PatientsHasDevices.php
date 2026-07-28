<?php

namespace App\Console\Commands\Puremed; 

use Illuminate\Console\Command;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\PatientsModel;
use App\Models\PatientHasDeviceModel;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
class PatientsHasDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients_has_devices:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all patinet data';

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
    public function __construct(
                                PatientsModel $PatientsModel,
                                PatientHasDeviceModel $PatientHasDeviceModel
                                )
    {
        parent::__construct();       
        $this->BaseModel  = $PatientsModel; 
        $this->PatientHasDeviceModel  = $PatientHasDeviceModel; 
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $lastPatientId = DB::connection('puremed_puregyn')->table('patient_has_device')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('patient_has_device')->where('id', ">", $lastPatientId)->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('patient_has_device')->get();
        }

//dd(count($rows));
        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $patient_id = DB::connection('puremed_puregyn')->table('patients')->where('migration_id',$value->patient_id)->pluck('id')->first();
                
                $data = [];
                $data['migration_id'] = $value->id;
                $data['patient_id'] =$patient_id ?? '';
                $data['device_type'] =$value->device_type;
                $data['device_id']     =$value->device_id;
                $data['created_at']      =$value->created_at;
                $data['updated_at']  =$value->updated_at;
                $data['deleted_at']    =$value->deleted_at;                

                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('patient_has_device')->insertGetId($data);

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
