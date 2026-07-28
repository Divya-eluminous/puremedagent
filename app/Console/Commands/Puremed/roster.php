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
class roster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roster:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all roster data';

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
                                AdminUserModel $AdminUserModel,
                                RosterModel $RosterModel
                                )
    {
        parent::__construct();       
        $this->BaseModel  = $AdminUserModel; 
        $this->RosterModel  = $RosterModel; 
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $lastPatientId = DB::connection('puremed_puregyn')->table('roster')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('roster')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('roster')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $user_id = DB::connection('puremed_puregyn')->table('users')->where('migration_id',$value->user_id)->pluck('id')->first();

                $doctor_id = DB::connection('puremed_puregyn')->table('users')->where('migration_id',$value->doctor_id)->pluck('id')->first();

                $data = [];
                $data['migration_id'] = $value->id;
                $data['doctor_id'] = $doctor_id;
                $data['user_id'] = $user_id;                
                $data['appointment_type_id'] = $value->appointment_type_id;               
                $data['status'] = $value->status;
                $data['created_at']  = $value->created_at;
                $data['updated_at'] = $value->updated_at; 
                $data['deleted_at'] = null;

                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('roster')->insertGetId($data);

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
