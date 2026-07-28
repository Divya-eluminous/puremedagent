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
class users extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:get';

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
        $lastPatientId = DB::connection('puremed_puregyn')->table('users')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('users')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')->table('users')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $data = [];

             
                $data['migration_id'] = $value->id;
                $doctor_id = 0;
                if(!empty($value->doctor_id) || $value->doctor_id!=0)
                {
                    $doctor_id = DB::connection('puremed_puregyn')->table('users')->where('migration_id',$value->doctor_id)->pluck('id')->first();
                }                

                $data['doctor_id'] =$doctor_id;
                $data['first_name'] =$value->first_name;
                $data['last_name']     =$value->last_name;
                $data['email']      =$value->email;
                $data['profile_img']  =$value->profile_img;
                $data['img_path']    =$value->img_path;
                $data['password']       =$value->password;
                // $data['str_password']       =$value->str_password;

                
                $data['mobile_number']        = $value->mobile_number;
                $data['remember_token']    = $value->remember_token;
                $data['google_color_id']    = $value->google_color_id;
                $data['color']    = $value->color;
                $data['doctor_speciality']    = $value->doctor_speciality;
               
                $data['status'] = $value->status;
                $data['created_at']            = $value->created_at;
                $data['updated_at'] = $value->updated_at;

                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('users')->insertGetId($data);

               

                $model_role_rows = DB::connection('migration')->table('model_has_roles')->where('model_id',  $value->id)->get();
                dump($model_role_rows);
              
                if(count($model_role_rows) > 0)
                {
                    foreach($model_role_rows as $m_key=>$m_value)
                    {
                        $model_data = [];
                        $model_data['role_id'] = $m_value->role_id;
                        $model_data['model_type'] = $m_value->model_type;
                        $model_data['model_id'] = $tenant_patinet_id;    

                        $tenant_model_role = DB::connection('puremed_puregyn')->table('model_has_roles')->insertGetId($model_data);

                    }
                }

               

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
