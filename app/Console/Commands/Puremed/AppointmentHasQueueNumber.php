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
class AppointmentHasQueueNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointment_has_queue_number:get';

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
        $lastPatientId = DB::connection('puremed_puregyn')->table('appointment_has_queue_number')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('appointment_has_queue_number')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('appointment_has_queue_number')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $patient_id = DB::connection('puremed_puregyn')->table('patients')->where('migration_id',$value->patient_id)->pluck('id')->first();

                $symbol_id = DB::connection('puremed_puregyn')->table('waiting_number_symbols')->where('id',$value->symbol_id)->pluck('id')->first();

                $appointment_id = DB::connection('puremed_puregyn')->table('appointment')->where('migration_id',$value->appointment_id)->pluck('id')->first();

                $data = [];
                $data['migration_id'] = $value->id;
                $data['patient_id'] = $patient_id ?? '';                
                $data['appointment_id'] = $appointment_id ?? '';
                $data['symbol_id'] = $symbol_id; 
                $data['date'] = $value->date; 
                $data['queue_number'] = $value->queue_number; 
                $data['queue_number_type'] = $value->queue_number_type;
                $data['called_status'] = $value->called_status;
                $data['called_time'] = $value->called_time;
                $data['status'] = $value->status ?? '';
                $data['created_at']  = $value->created_at;
                $data['updated_at'] = $value->updated_at; 
                $data['deleted_at'] = $value->deleted_at; 

                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('appointment_has_queue_number')->insertGetId($data);

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
