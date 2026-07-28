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
class Appointment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointment:get';

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
        $lastPatientId = DB::connection('puremed_puregyn')->table('appointment')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('appointment')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('appointment')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $patient_id = DB::connection('puremed_puregyn')->table('patients')->where('migration_id',$value->patient_id)->pluck('id')->first();

                $doctor_id = DB::connection('puremed_puregyn')->table('users')->where('migration_id',$value->doctor_id)->pluck('id')->first();

                $appointment_type_id = DB::connection('puremed_puregyn')->table('appointment_types')->where('migration_id',$value->appointment_type_id)->pluck('id')->first();

                $data = [];
                $data['migration_id'] = $value->id;
                $data['google_event_id'] = $value->google_event_id; 
                $data['start_date'] = $value->start_date; 
                $data['end_date'] = $value->end_date; 
                $data['doctor_id'] = $doctor_id;
                $data['patient_id'] = $patient_id ?? '';                
                $data['appointment_type_id'] = $appointment_type_id;
                $data['notes'] = $value->notes;
                $data['status'] = $value->status;
                $data['appointment_status'] = $value->appointment_status ?? '';
                $data['created_at']  = $value->created_at;
                $data['updated_at'] = $value->updated_at; 
                $data['deleted_at'] = $value->deleted_at; 

                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('appointment')->insertGetId($data);

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
