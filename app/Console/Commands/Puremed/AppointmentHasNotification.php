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
class appointmentHasNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointment_has_notification:get';

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
        $lastPatientId = DB::connection('puremed_puregyn')->table('appointment_has_notification')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('appointment_has_notification')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('appointment_has_notification')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $patient_id = DB::connection('puremed_puregyn')->table('patients')->where('migration_id',$value->patient_id)->pluck('id')->first();


                $appointment_id = DB::connection('puremed_puregyn')->table('appointment')->where('migration_id',$value->appointment_id)->pluck('id')->first();

                $data = [];
                $data['migration_id'] = $value->id;
                $data['patient_id'] = $patient_id ?? '';                
                $data['appointment_id'] = $appointment_id ?? '';
                $data['notify_time'] = $value->notify_time; 
                $data['title'] = $value->title; 
                $data['day'] = $value->day ?? ''; 
                $data['content'] = $value->content;
                $data['status'] = $value->status;
                $data['one_signal_response'] = $value->one_signal_response    ;
                $data['created_at']  = $value->created_at;
                $data['updated_at'] = $value->updated_at; 
                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('appointment_has_notification')->insertGetId($data);

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
