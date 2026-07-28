<?php

namespace App\Console\Commands\Puremed; 

use Illuminate\Console\Command;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\PatientsModel;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
class patients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients:get';

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
                                PatientsModel $PatientsModel
                                )
    {
        parent::__construct();       
        $this->BaseModel  = $PatientsModel; 
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {


        $lastPatientId = DB::connection('puremed_puregyn')->table('patients')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('patients')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->paginate(20000);
        }
        else
        {
            $rows = DB::connection('migration')->table('patients')->orderBy('id', 'ASC')->paginate(20000);
        }
//dd($lastPatientId,count($rows),$rows);
        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $data = [];
                $data['migration_id'] = $value->id;
                $data['old_id'] =$value->old_id;
                $data['pat_nr'] =$value->pat_nr;
                $data['family_name']     =$value->family_name;
                $data['first_name']      =$value->first_name;
                $data['email']  =$value->email;
                $data['country_code']    =$value->country_code;
                $data['mobile_no']       =$value->mobile_no;
                $data['ganymed_mobile_no']       =$value->ganymed_mobile_no;

                if(!empty($value->birth_date))
                {
                    $data['birth_date']  = date('Y-m-d', strtotime($value->birth_date));
                    $data['age']         = (date('Y') - date('Y',strtotime($data['birth_date'])));
                }
                else
                {
                    $data['birth_date']  = null;
                    $data['age']         = 0;
                }
                $data['password']        = $value->password;
                // $data['str_password']    = $value->str_password;
                $data['login_otp']    = $value->login_otp;
                $data['otp_created_at']    = $value->otp_created_at;
                $data['api_access_token']    = $value->api_access_token;
                $data['last_login_at']    = $value->last_login_at;
                $data['login_type'] = $value->login_type;
                $data['is_blocked'] = $value->is_blocked;
                $data['status'] = $value->status;
                $data['mobile_token'] = $value->mobile_token;
                $data['token'] = $value->token;
                $data['road']   =$value->road;
                $temp['street_no']      =$value->street_no;
                $data['place']  =$value->place;
                $data['postal_code']     =$value->postal_code;
                $data['gender'] =$value->gender;
                $data['size']   =$value->size;
                $data['weight'] =$value->weight;
                $data['title']  =$value->title;
                $data['salutation']      =$value->salutation;
                $data['family_doctor']   =$value->family_doctor;
                $data['insurance_number']= $value->insurance_number;
                $data['additional_insurance'] = $value->additional_insurance;
                $data['gdpr']            = $value->gdpr;
                $data['update_ganydb']= $value->update_ganydb;
                $data['patient_status_flag'] = $value->patient_status_flag;
                $data['note_report_request']            = $value->note_report_request;
                $data['note_report_request_flag'] = $value->note_report_request_flag;
                $data['created_at']            = $value->created_at;
                $data['updated_at'] = $value->updated_at;
                $data['deleted_at']            = $value->deleted_at;

                $tenant_patinet_id = DB::connection('puremed_puregyn')->table('patients')->insertGetId($data);

                if(!empty($tenant_patinet_id))
                {
                    unset($data['migration_id']);
                   
                    $master_patinet_id = DB::connection('system')->table('patients')->insertGetId($data);

                    if(!empty($master_patinet_id))
                    {
                        $data_ordiantion = [];
                        $data_ordiantion['fk_ordination_id'] = 2;
                        $data_ordiantion['fk_patient_id'] = $master_patinet_id; 
                        $data_ordiantion['status'] = '1';
                        $master_patinet_id = DB::connection('system')->table('patients_has_ordination')->insertGetId($data_ordiantion);
                    }
                }

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
