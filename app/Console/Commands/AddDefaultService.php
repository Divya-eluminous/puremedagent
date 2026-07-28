<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
// Hyn tenancy imports (commented out)
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;

// Stancl tenancy imports
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\PatientsModel;
use App\Models\ExaminationsModel;
use App\Models\AppointmentModel;
use App\Models\AppointmentHasExaminationsModel;


use WebSmsCom_Client;
use WebSmsCom_AuthenticationMode;
use WebSmsCom_TextMessage;
use WebSmsCom_ParameterValidationException; 
use WebSmsCom_AuthorizationFailedException;
use WebSmsCom_ApiException;
use WebSmsCom_HttpConnectionException;
use WebSmsCom_UnknownResponseException;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use DB;
use Mail;
use DateTime; 


class AddDefaultService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'defaultservice:daily {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add default service for the HPV improgram';

    // /**
    //  * @var Connection (Hyn tenancy - commented out)
    //  */
    // // private $connection;

    // /**
    //  * @var WebsiteRepository (Hyn tenancy - commented out)
    //  */
    // // private $websites;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
                                PatientsModel $PatientsModel,
                                AppointmentModel $AppointmentModel,
                                ExaminationsModel $ExaminationsModel,
                                AppointmentHasExaminationsModel $AppointmentHasExaminationsModel
                                )
    {

        parent::__construct();
        // Hyn tenancy initialization (commented out)
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
        
        $this->PatientsModel = $PatientsModel;
        $this->AppointmentModel = $AppointmentModel;
        $this->ExaminationsModel = $ExaminationsModel;
        $this->AppointmentHasExaminationsModel = $AppointmentHasExaminationsModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        log::info("AddDefaultService handle function start");

        // $website_id = $this->option('website_id');
        // try
        // {            
        //     if(!empty($website_id) && $website_id!='0')
        //     {               
        //         // Hyn tenancy code (commented out)
        //         // $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
        //         // $this->connection->set($website);   

        //         // Stancl tenancy code
        //         $tenant = DB::connection('system')->table('tenants')->where('id', $website_id)->firstOrFail();
        //         Tenancy::initialize($tenant);

        //          //log::info($website_id);   

        //         self::_commandOperation($website_id);
                
        //         // Hyn tenancy cleanup (commented out)
        //         // $this->connection->purge();
                
        //         // Stancl tenancy cleanup
        //         Tenancy::end();
        //     }
        // } 
        // catch (ModelNotFoundException $e) 
        // {
        //     throw new RuntimeException(
        //         sprintf(
        //             'The tenancy website_id=%d does not exist.',
        //             $website_id
        //         )
        //     );
        // } 
        // log::info("AddDefaultService handle function end");
         $tenant_id = $this->option('tenant_id');
        // dump($tenant_id);
        try
        {
            if(!empty($tenant_id) && $tenant_id!='0')
            { 
                self::_commandOperation($tenant_id);
                 // Stancl tenancy cleanup
                 tenancy()->end();
            }
           
        } 
        catch (ModelNotFoundException $e) 
        {
            throw new RuntimeException(
                sprintf(
                    'The tenancy tenant_id=%d does not exist.',
                    $tenant_id
                )
            );
        }

    }

    /*-------------------------------------- 
      | Actual functionality
    --------------------------------------*/

    public function _commandOperation($tenant_id)
    {
        $tenant = \App\Models\Tenant::find($tenant_id);
        if ($tenant) {
            // Initialize tenant context
            tenancy()->initialize($tenant);
            // Force tenant DB connection for console/cron
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
        }
        Log::info("In AddDefaultService _commandOperation function ..........".$tenant_id); 
        
        $cnt=0;

        //commented on 8-sept-25
       /* $appointments = DB::connection('tenant')->table('appointment as a')
            ->join('appointment_types', 'a.appointment_type_id', '=', 'appointment_types.id')
            ->join('examinations', 'appointment_types.name', '=', 'examinations.name')
            ->select(
                'a.id',
                 'a.patient_id',
                'a.appointment_type_id',
                'appointment_types.id as appointment_type_id',
                'appointment_types.name as appointment_type_name',
                'examinations.id as examination_id',
                'examinations.name as examination_name'
            )
            //->where(DB::raw('DATE(a.start_date)'), '>=', date('Y-m-d'))
            //->where('a.patient_id',56476)
            ->whereNull('examinations.deleted_at')
            ->orderBy('a.id','desc')
            ->get();*/

        //Added on 8-sept-25    
        $appointments = DB::connection('tenant')->table('appointment as a')
            ->join('appointment_types', 'a.appointment_type_id', '=', 'appointment_types.id')
            ->join('examinations', 'appointment_types.name', '=', 'examinations.name')
            ->leftJoin('appointment_has_examinations as ahx', function($join) {
                $join->on('ahx.appointment_id', '=', 'a.id')
                     ->on('ahx.examination_id', '=', 'examinations.id')
                     ->on('ahx.patient_id', '=', 'a.patient_id');
            })
            ->select(
                'a.id',
                'a.patient_id',
                'a.appointment_type_id',
                'appointment_types.id as appointment_type_id',
                'appointment_types.name as appointment_type_name',
                'examinations.id as examination_id',
                'examinations.name as examination_name'
            )
            ->whereNull('examinations.deleted_at')
            ->whereNull('ahx.id') 
            //->where('a.patient_id',47259)
            ->orderBy('a.id', 'desc')
            ->get();    

         Log::info("appointments===>");
         Log::info($appointments);   

        if (isset($appointments) && !empty($appointments)) {
            foreach ($appointments as $value) {
                // Accessing properties using object notation
                $appointment_id = $value->id; // Use ->id to access property
                $appointment_type_id = $value->appointment_type_id; // Use ->appointment_type_id
                $examination_id = $value->examination_id; // Use ->examination_id
                $patient_id = $value->patient_id;

                //commented on 8-sept-25
                // Fetch related records from the `appointment_has_examinations` table
                /*$appointmentHasExaminations = DB::connection('tenant')->table('appointment_has_examinations')
                    ->where('appointment_id', $appointment_id)
                    ->where('examination_id', $examination_id)
                    ->where('patient_id', $patient_id)
                    ->get()->toArray();*/

                //added on 8-sept-25    
                $appointmentHasExaminations = DB::connection('tenant')->table('appointment_has_examinations')
                    ->where('appointment_id', $appointment_id)
                    ->where('examination_id', $examination_id)
                    ->where('patient_id', $patient_id)
                    ->first();         


                 //dump($appointment_id.'======>'.$examination_id);
                //commented if condition on 8-sept-25    
               // if (isset($appointmentHasExaminations) && count($appointmentHasExaminations) > 0) {

                if(isset($appointmentHasExaminations) && !empty($appointmentHasExaminations)) 
                {     
                   Log::info("innnn already exists examinationid=>".$examination_id.'==>appointment_id=>'.$appointment_id.'==>patient_id=>'.$patient_id);
                }
                else
                {
                    $cnt++;
                   Log::info('else=insert=appid:==>'.$appointment_id.'==patientid==>'.$patient_id.'===cnt====>'.$cnt.'==examination_id==>'.$examination_id);

                    //commented on 8-sept-25
                    /* $insertData[] = [
                        'appointment_id' => $appointment_id,
                        'patient_id' => $patient_id,
                        'examination_id' => $examination_id,
                        'dismissal_flag' => 0,
                        'create_from' => NULL,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];*/

                    //added on 8-sept-25  
                    DB::connection('tenant')->table('appointment_has_examinations')->insert([
                        'appointment_id' => $appointment_id,
                        'patient_id' => $patient_id,
                        'examination_id' => $examination_id,
                        'dismissal_flag' => 0,
                        'create_from' => NULL,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('Inserted new examination records.');

                }
            } // foreach
        } //if appointments


        /*if(isset($insertData) && !empty($insertData))
        {
            Log::info($insertData);
            //DB::connection('tenant')->table('appointment_has_examinations')->insert($insertData);
            Log::info('Inserted new examination records.');
        }
        else
        {
            Log::info('No new examination records to insert.');
        }*/

    }//_commandOperation

    public function _commandOperation_kosten($website_id)
    {

         log::info("In AddDefaultService _commandOperation function ..........");


            $collections = DB::connection('tenant')->table('appointment')
                ->leftJoin('appointment_has_examinations', function ($join) {
                    $join->on('appointment.id', '=', 'appointment_has_examinations.appointment_id')
                        ->where('appointment_has_examinations.examination_id', '=', 185); //live
                    //->where('appointment_has_examinations.examination_id', '=', 185);//local
                    //->where('appointment_has_examinations.examination_id', '=', 178); //stage
                })
                ->where('appointment.appointment_type_id', 124) //live
                // ->where('appointment.appointment_type_id', 119) //stage
                //->where('appointment.appointment_type_id', 117) //local
                ->whereNull('appointment.deleted_at')
                ->whereNull('appointment_has_examinations.examination_id')
                ->orderBy('appointment.id')
                ->get([
                    'appointment.id',
                    'appointment.appointment_type_id',
                    'appointment.patient_id',
                ]);


     
        log::info("Collections...."); 
        log::info($collections);
 
        if(!empty($collections))
        {
            foreach ($collections as $key => $value)
            { 

                log::info('appointment_id');
                log::info($value->id);

                log::info('appointment_type_id');
                log::info($value->appointment_type_id);

                log::info('patient_id');
                log::info($value->patient_id);

                $appointment_id= $value->id;
                $appointment_type_id= $value->appointment_type_id;
                $patient_id= $value->patient_id;
                $examination_id= 184; //live 
                $remexamination_id = 185; //live 
               // $examination_id= 185; //local
               // $examination_id= 177; //stage

                // $isExamExists =DB::connection('tenant')->table('appointment_has_examinations')
                //                             ->where('appointment_id',$appointment_id) 
                //                             ->whereIn('examination_id',$examination_id)
                //                             ->where('patient_id',$patient_id)
                //                             ->first();

                $existingExams = DB::connection('tenant')
                    ->table('appointment_has_examinations')
                    ->where('appointment_id', $appointment_id)
                    ->whereIn('examination_id', [$examination_id, $remexamination_id])
                    ->where('patient_id', $patient_id)
                    ->pluck('examination_id')
                    ->toArray();

                // Prepare data for insertion
                if (!in_array($examination_id, $existingExams)) {
                    $insertData[] = [
                        'appointment_id' => $appointment_id,
                        'patient_id' => $patient_id,
                        'examination_id' => $examination_id,
                        'dismissal_flag' => 0,
                        'create_from' => NULL,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!in_array($remexamination_id, $existingExams)) {
                    $insertData[] = [
                        'appointment_id' => $appointment_id,
                        'patient_id' => $patient_id,
                        'examination_id' => $remexamination_id,
                        'dismissal_flag' => 0,
                        'create_from' => NULL,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                


                //  if(empty($isExamExists)){

                //     log::info('empty isExamExists...');

                //         $temp = [];                    
                //         //$temp['migration_id'] = '';
                //         $temp['appointment_id'] =  $appointment_id;
                //         $temp['patient_id'] =  $patient_id;
                //         $temp['examination_id'] =  $examination_id;
                //         $temp['dismissal_flag'] = 0;
                //         $temp['create_from'] =  NULL;
                //         $temp['created_at'] =  date('Y-m-d H:i:s');
                        

                //         log::info('temp');
                //         log::info($temp);

                //         $app_exam_id = DB::connection('tenant')->table('appointment_has_examinations')->insertGetId($temp);

                //  }//if isExamExists 
                //  else{
                //     log::info('else empty isExamExists...');
                //  }                         

            }//foreach
            if (!empty($insertData)) {
                DB::connection('tenant')->table('appointment_has_examinations')->insert($insertData);
                Log::info('Inserted new examination records.');
            } else {
                Log::info('No new examination records to insert.');
            }
        }//if
    }//_commandOperation




}
