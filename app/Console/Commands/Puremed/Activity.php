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

class Activity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:get';

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
                                AdminUserModel $AdminUserModel
                                )
    {
        parent::__construct();       
        $this->BaseModel  = $AdminUserModel; 
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $lastPatientId = DB::connection('puremed_puregyn')->table('activity_logs')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('activity_logs')->where('id', ">", $lastPatientId)->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('activity_logs')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $data = [];

             
                $data['migration_id'] = $value->id;
                $user_id = 0;
                if(!empty($value->user_id) || $value->user_id!=0)
                {
                    $user_id = DB::connection('puremed_puregyn')->table('users')->where('migration_id',$value->user_id)->pluck('id')->first();
                }                

                $data['user_id'] =$user_id ?? '';


                $patient_id = DB::connection('puremed_puregyn')->table('patients')->where('migration_id',$value->patient_id)->pluck('id')->first();
                $data['patient_id'] = $patient_id ?? '';  

                $data['module'] =$value->module;
                $data['action']     =$value->action;
                $data['old_data']      =$value->old_data;
                $data['new_data']  =$value->new_data;
                $data['message']    =$value->message;
                $data['method']       =$value->method;
                $data['url']       =$value->url;

                
                $data['ip']        = $value->ip;
                $data['agent']    = $value->agent;
                
               
                $data['created_at']            = $value->created_at;
                $data['updated_at'] = $value->updated_at; 
                $data['deleted_at'] = $value->deleted_at;

                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('activity_logs')->insertGetId($data);


               

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
