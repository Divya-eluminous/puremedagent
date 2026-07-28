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
use App\Models\AppointmentTypeHasExaminationsModel;


class AddServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addservice:daily {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add services';

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
                                AppointmentHasExaminationsModel $AppointmentHasExaminationsModel,
                                AppointmentTypeHasExaminationsModel $AppointmentTypeHasExaminationsModel
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
        $this->AppointmentTypeHasExaminationsModel = $AppointmentTypeHasExaminationsModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        log::info("Ads Service handle function start");
        $tenant_id = $this->option('tenant_id');
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
        Log::info("In Add Service _commandOperation function ..........".$tenant_id); 
        
        $cnt=0;

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
           // ->where('a.patient_id',17)
            ->where('a.created_at', '>=', Carbon::now()->subMinutes(15)) //  last 15 minutes
            ->where('a.appointment_status','!=','Fertig') 
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

                
                $appointmentHasExaminations = DB::connection('tenant')->table('appointment_has_examinations')
                    ->where('appointment_id', $appointment_id)
                    ->where('examination_id', $examination_id)
                    ->where('patient_id', $patient_id)
                    ->first();         



                if(isset($appointmentHasExaminations) && !empty($appointmentHasExaminations)) 
                {     
                    Log::info("innnn already exists examinationid=>".$examination_id.'==>appointment_id=>'.$appointment_id.'==>patient_id=>'.$patient_id);
                }
                else
                {
                    $cnt++;

                    Log::info("innnn not exists examinationid=>".$examination_id.'==>appointment_id=>'.$appointment_id.'==>patient_id=>'.$patient_id);

                    Log::info("appointment_type_id==>");
                    Log::info($appointment_type_id);

                     $collections1 = DB::connection('tenant')->table('appoinment_type_has_examinations')
                        ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')    
                        ->where('appoinment_type_has_examinations.appoinment_id', $appointment_type_id)
                        ->whereNull('appoinment_type_has_examinations.deleted_at') // ignore deleted rows
                        ->get([
                            'examinations.id',
                            'examinations.name',
                            'examinations.url',
                            'examinations.description',
                            'examinations.status',
                            'examinations.created_at',
                            'examinations.show_as_recommended'
                        ]);

                    Log::info($collections1);
                    Log::info("collections1 data:", $collections1->toArray());

                    $today_date = date("Y-m-d");

                    $collections1 = $collections1->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) {

                        $app_type_name = DB::connection('tenant')
                            ->table('appointment_types')
                            ->where('id', $appointment_type_id)
                            ->first();


                        if ($item->name == $app_type_name->name) {

                            return $item;
                        }
                        else
                        {

                            $collectionsFilter = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status'))
                                ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                                ->join(
                                    DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id 
                                                    FROM patient_has_service_reminder 
                                                    WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                                    and status='activate'
                                                    and deleted_at is NULL GROUP BY service_id)
                                                patientremidners"),
                                    function ($join) {
                                        $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                        $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                                    }
                                )
                                ->where('patient_has_service_reminder.patient_id', $patient_id)
                                ->where('patient_has_service_reminder.status', 'activate')
                                ->where('examinations.id', $item->id)
                                ->whereRaw("date(reminder_date) <= '" . $today_date . "'")
                                ->groupBy('patient_has_service_reminder.service_id')
                                ->get();

                    

                            if (isset($collectionsFilter) && !empty($collectionsFilter) && $collectionsFilter->count() > 0) {

                                $collectionsFilter = $collectionsFilter->filter(function ($item) use ($patient_id, $appointment_type_id, $today_date) {

                                  
                                    $app_type_name = DB::connection('tenant')
                                        ->table('appointment_types')
                                        ->where('id', $appointment_type_id)
                                        ->first();

                                    $age_service =  DB::connection('tenant')
                                        ->table('preferred_channels_for_reminders_setting')
                                        ->where('service_id', $item->id)
                                        ->where('activated_reminder', 'age')
                                        ->first();
                                    //Added by swati 2-nov-22=========================
                                    $general_reminder_service =  DB::connection('tenant')
                                        ->table('preferred_channels_for_reminders_setting')
                                        ->where('service_id', $item->id)
                                        ->where('activated_reminder', 'general')
                                        ->first();
                                    //============================                  
                                    if (!empty($age_service) && $item->name != $app_type_name->name) {
                                        //$getPatientAge = $this->PatientsModel->find($patient_id);
                                        $getPatientAge = DB::connection('tenant')
                                                     ->table('patients')
                                                     ->find($patient_id);


                                        if (!empty($getPatientAge)) {
                                            $patient_age = $getPatientAge->age;
                                            if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                                //commented on 26-dec-23
                                                return $item;
                                            } //if
                                        }
                                    } else if (!empty($general_reminder_service)) {
                                        $checkGenaralService =   DB::connection('tenant')
                                                     ->table('patient_has_service_reminder')
                                            ->where('service_id', $item->id)
                                            ->where('patient_id', $patient_id)
                                            ->where('reminder_status', 'Set')
                                            ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                            ->first();
                                        if (empty($checkGenaralService)) return $item;
                                    } //Added this else by swati 2-nov-22
                                    else {
                                        return $item;
                                    }
                                });
                            } //if isset collection filter
                            else {

                                $hasReminderSet =  DB::connection('tenant')
                                                     ->table('patient_has_service_reminder')
                                    ->where('patient_has_service_reminder.patient_id', $patient_id)
                                    ->where('patient_has_service_reminder.service_id', $item->id)
                                    ->first();
                                if (isset($hasReminderSet) && !empty($hasReminderSet)) {
                                } //if hasReminderSet
                                else {
                                    return $item;
                                }

                                // return $item;  
                            } //else   

                        } //else not defaultservice name

                    });


                    Log::info("2nd ...collections1.again..");
                    Log::info($collections1);
                    // dump($collections1);

                    $exams_ids  = array_unique(array_column(array_values($collections1->toArray()), 'id'));
                    Log::info("Extracted exams_ids:", $exams_ids);  

                    //dump($exams_ids);

                    $collections2 = DB::connection('tenant')->table('patient_has_service_reminder')
                        ->select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status,patient_has_service_reminder.id as reminderid'))
                        ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
                        ->join(
                           
                            DB::raw("(SELECT service_id,patient_has_service_reminder.id as reminderid,MAX(appointment_id) appointment_id 
                                FROM patient_has_service_reminder 
                                WHERE patient_id='" . $patient_id . "' and reminder_status IN('ignore','Set') 
                                and status='activate'
                                AND (
                                ( (deleted_at IS NULL AND cycle_no = 1 AND date(reminder_date) <= '" . $today_date . "' AND type!='age' ) 
                                   OR
                                   (  deleted_at IS NULL and cycle_no>=0 AND date(reminder_date) <= '" . $today_date . "' and type='age' 
                                   )
                                )
                                OR 
                                ( (deleted_at IS NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' AND type!='age') 
                                   OR (deleted_at IS  NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today_date . "' and type='age') 
                                )
                            )  GROUP BY service_id) 
                            patientremidners"),

                            function ($join) {
                                $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                                $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');

                            }
                        )
                        ->where('patient_has_service_reminder.patient_id', $patient_id)
                        ->where('patient_has_service_reminder.status', 'activate')
                        ->whereRaw("examinations.show_as_reminder='1'")
                        ->whereNotIn('examinations.id', $exams_ids)
                        ->groupBy('patient_has_service_reminder.service_id')
                        ->get();

                    Log::info("collections2 data:", $collections2->toArray());              
                    Log::info($collections2);
                    //dump($collections2);



                    $collections2 = $collections2->filter(function ($item) use ($patient_id, $today_date) {
                        $age_service =  DB::connection('tenant')
                                        ->table('preferred_channels_for_reminders_setting')
                                        ->where('service_id', $item->id)
                                        ->where('activated_reminder', 'age')
                                        ->first();
                        if (!empty($age_service)) {
                            //log::info($patient_id);


                            //$getPatientAge = $this->PatientsModel->find($patient_id);
                            $getPatientAge = DB::connection('tenant')
                                                     ->table('patients')
                                                     ->find($patient_id);
                                                 
                            if (!empty($getPatientAge)) {

                                Log::info("in getPatientAge ..");

                                $patient_age = $getPatientAge->age;

                                Log::info($patient_age);      


                                if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                    if ($item->reminder_status == 'executed') {
                                        $checkServiceReminders =  DB::connection('tenant')->table('patient_has_service_reminder')
                                            ->where('service_id', $item->id)
                                            ->where('patient_id', $patient_id)
                                            ->where('reminder_status', 'Set')
                                            ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                            ->first();
                                        //echo "<pre>";print_r($checkServiceReminders);
                                        if (empty($checkServiceReminders))
                                            return $item;
                                    } else return $item;
                                }
                            }
                        }
                       
                        $general_reminder_service =  DB::connection('tenant')
                                        ->table('preferred_channels_for_reminders_setting')
                            ->where('service_id', $item->id)
                            ->where('activated_reminder', 'general')
                            ->first();

                        if (!empty($general_reminder_service)) {

                            $today_date = date("Y-m-d");


                            if($item->reminder_status == 'executed')
                            {     
                                $checkServiceReminders =  DB::connection('tenant')
                                                     ->table('patient_has_service_reminder')
                                    ->where('service_id', $item->id)
                                    ->where('patient_id', $patient_id)
                                    ->where('reminder_status', 'Set')
                                    ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                    ->first();
                                if (empty($checkServiceReminders))
                                    return $item;   
                            }else{
                                return $item;
                            }

                        }
                        $checkup_reminder_service =  DB::connection('tenant')
                                        ->table('preferred_channels_for_reminders_setting')
                            ->where('service_id', $item->id)
                            ->where('activated_reminder', 'checkup')
                            ->first();

                        if (!empty($checkup_reminder_service)) {

                            $today_date = date("Y-m-d");
                            
                            if($item->reminder_status == 'executed')
                            {
                                $checkServiceReminders =  DB::connection('tenant')
                                                     ->table('patient_has_service_reminder')
                                ->where('service_id', $item->id)
                                ->where('patient_id', $patient_id)
                                ->where('reminder_status', 'Set')
                                ->whereRaw("date(reminder_date) >= '" . $today_date . "'")
                                ->first();
                                if (empty($checkServiceReminders))
                                 return $item;
                            }else{
                                return $item;
                            }    

                        }
                        //================================================
                    });


                   // Log::info("collections2 filter completed. Count: " . $collections2->count());
                    // log::info("getRecord");
                    // log::info("getServices");

                    Log::info("collections2 .again..");
                    Log::info($collections2);

                    //dump("collections2 .again..");
                    //dump($collections2);

                    $getRecord = $collections1->merge($collections2);

                    Log::info("getRecord.");
                    Log::info($getRecord);

                    //dump("getRecord.");
                    //dump($getRecord);


                    if (!empty($getRecord) && count($getRecord) > 0 && !empty($appointment_id)) {

                        // Fetch appointment type once
                        $appTypeNameDefault = DB::connection('tenant')
                            ->table('appointment_types')
                            ->where('id', $appointment_type_id)
                            ->first();

                        // Fetch non-examination IDs for this appointment type
                        $getAppointmentNonServciesIds = DB::connection('tenant')
                            ->table('appoinment_type_has_non_examinations')
                            ->where('appointment_type_id', $appointment_type_id)
                            ->pluck('examination_id'); // collection of IDs

                        $getRecord = $getRecord->map(function ($item) use ($appTypeNameDefault, $getAppointmentNonServciesIds) {

                            // Exclude non-examination records
                            if ($getAppointmentNonServciesIds->contains($item->id)) {
                                return null;
                            }

                            // When description is blank
                            if (empty($item->description)) {
                                return $item;
                            }

                            // When name matches appointment type
                            if (!empty($appTypeNameDefault) && $item->name == $appTypeNameDefault->name) {
                                return $item;
                            }

                            return null; // exclude everything else
                        })
                        ->filter() // remove nulls
                        ->values(); // reindex collection
                    }

                    Log::info("getRecord.again");
                    Log::info($getRecord);

                    //dump("getRecord.again");
                    //dump($getRecord);



                    /* $checkIsNew =  DB::connection('tenant')
                                ->table('patient_has_service_reminder')
                        ->where('patient_id', $patient_id)
                        ->count();

                 
                    if($checkIsNew == 0)
                    {                   


                        $getPatientAge = DB::connection('tenant')
                             ->table('patients')
                             ->find($patient_id);

                        $birth_date = $getPatientAge->birth_date;
                        $getRecordExtra = DB::connection('tenant')
                        ->table('preferred_channels_for_reminders_setting')
                        ->join('examinations', 'examinations.id', 'preferred_channels_for_reminders_setting.service_id')
                        ->where('preferred_channels_for_reminders_setting.type', 'service')
                        ->where('preferred_channels_for_reminders_setting.activated_reminder', 'age')
                        ->where('examinations.show_as_reminder', '1')
                        ->whereNull('examinations.deleted_at') //added on 15-dec-23 for "testservice1" shown
                        ->whereNotIn('examinations.id', $exams_ids)
                        ->get([
                            'examinations.id',
                            'examinations.name',
                            'examinations.url',
                            'examinations.description',
                            'examinations.status',
                            'examinations.created_at',
                            'examinations.show_as_recommended'
                        ]);

                        Log::info("getRecordExtra");
                        Log::info($getRecordExtra);  

                        //dump("getRecordExtra.");
                        //dump($getRecordExtra);


                        $getRecordExtra = $getRecordExtra->filter(function ($item) use ($birth_date) {
                            Log::info("Filtering item - ID: " . $item->id . ", Name: " . $item->name);
                            
                            $age_service =  DB::connection('tenant')
                                  ->table('preferred_channels_for_reminders_setting')
                                ->where('service_id', $item->id)
                                ->where('activated_reminder', 'age')
                                ->first();
                            
                           // dump("Age service found for item " . $item->id . ": " . (!empty($age_service) ? 'Yes' : 'No'));
                            
                            if (!empty($age_service)) {
                                Log::info("Age service details - age_from: " . $age_service->age_from . ", age_to: " . $age_service->age_to);
                                
                                if (!empty($birth_date)) {
                                    $patient_age = (date('Y') - date('Y', strtotime($birth_date)));
                                    Log::info("Patient age calculated: " . $patient_age . " (birth_date: " . $birth_date . ")");
                                    
                                    if ($age_service->age_from <= $patient_age && $age_service->age_to >= $patient_age) {
                                        Log::info("Item " . $item->id . " passed age filter - INCLUDED");
                                        return $item;
                                    } else {
                                        Log::info("Item " . $item->id . " failed age filter - EXCLUDED (age " . $patient_age . " not in range " . $age_service->age_from . "-" . $age_service->age_to . ")");
                                    }
                                } else {
                                    Log::info("Birth date is empty for item " . $item->id . " - EXCLUDED");
                                }
                            } else {
                                Log::info("No age service for item " . $item->id . " - INCLUDED (no age restriction)");
                                return $item;
                            }
                        });


                        Log::info("getRecord filter completed. Count: " . $getRecord->count());
                        Log::info("Starting getRecord map...");

                       
                        

                        $getRecordExtra = $getRecordExtra->filter(function ($item) use ($appointment_type_id) {
                            Log::info("Mapping item - ID: " . $item->id . ", Name: " . $item->name);
                            
                            //$app_type_name = $this->AppointmentTypesModel->find($appointment_type_id);

                            $app_type_name = DB::connection('tenant')
                                            ->table('appointment_types')
                                            ->where('id', $appointment_type_id)
                                            ->first();

                            Log::info("Appointment type found: " . (!empty($app_type_name) ? $app_type_name->name : 'Not found'));
                            
                            if (!empty($app_type_name)) {
                                if ($item->name == $app_type_name->name) {
                                     return $item;
                                } else if (empty($item->description)) {
                                     return $item;
                                }
                               
                            }
                        })->values();


                        Log::info("getRecordExtra again");
                        Log::info($getRecordExtra);

                        //dump("getRecordExtra again");
                       // dump($getRecordExtra);


                       $getAppointmentNonServciesIds = DB::connection('tenant')
                                ->table('appoinment_type_has_non_examinations')
                                ->where('appointment_type_id', $appointment_type_id)
                                ->pluck('examination_id'); // collection of IDs

                        $getRecordExtra = $getRecordExtra->filter(function ($item) use ($appointment_type_id, $getAppointmentNonServciesIds) {
                            Log::info("Mapping item - ID: " . $item->id . ", Name: " . $item->name);

                            $app_type_name = DB::connection('tenant')
                                ->table('appointment_types')
                                ->where('id', $appointment_type_id)
                                ->first();

                            Log::info("Appointment type found: " . (!empty($app_type_name) ? $app_type_name->name : 'Not found'));

                            if (empty($app_type_name)) {
                                return false; // exclude if appointment type not found
                            }

                            // Exclude items that are in the non-services list
                            if ($getAppointmentNonServciesIds->contains($item->id)) {
                                Log::info("Excluding item ID {$item->id} because it is a non-service");
                                return false;
                            }

                            // Keep items that match name OR have empty description
                            if ($item->name == $app_type_name->name || empty($item->description)) {
                                return true;
                            }

                            return false; // exclude everything else
                        })->values();


                        Log::info("getRecordExtra again");
                        Log::info($getRecordExtra);

                       // dump("getRecordExtra again ...");
                       // dump($getRecordExtra);

                    }//if checkisnew
                    */
                    



                    // Merge into a single collection and reindex
                    if (isset($getRecordExtra)) {
                        $final = $getRecord->merge($getRecordExtra)->values();
                    } else {
                        $final = $getRecord->values();
                    }

                    Log::info("final services ...");
                    Log::info($final);

                    // dump("final services ...");
                    // dump($final);

                    //commented on 7-oct-25
                   /* DB::connection('tenant')->table('appointment_has_examinations')->insert(
                        $final->map(function ($item) use ($value) {
                            return [
                                'appointment_id'  => $value->id,
                                'patient_id'      => $value->patient_id,
                                'examination_id'  => $item->id,
                                'dismissal_flag'  => 0,
                                'create_from'     => null,
                                'created_at'      => now(),
                                'updated_at'      => now(),
                            ];
                        })->toArray()
                   );


                    // Extract only service IDs into the format needed
                     $services = $final->pluck('id')->toArray();

                    $getAppointmentRec = DB::connection('tenant')
                                            ->table('appointment')
                                            ->where('id', $value->id)
                                            ->first();
                    $this->deactivateReminderNew($getAppointmentRec,$services); */


                    //Added on 7-oct-25
                    $insertedServiceIds = [];

                    foreach ($final as $item) {
                        $exists = DB::connection('tenant')
                            ->table('appointment_has_examinations')
                            ->where('appointment_id', $value->id)
                            ->where('patient_id', $value->patient_id)
                            ->where('examination_id', $item->id)
                            ->exists();

                        if (!$exists) {
                            DB::connection('tenant')->table('appointment_has_examinations')->insert([
                                'appointment_id'  => $value->id,
                                'patient_id'      => $value->patient_id,
                                'examination_id'  => $item->id,
                                'dismissal_flag'  => 0,
                                'create_from'     => null,
                                'created_at'      => now(),
                                'updated_at'      => now(),
                            ]);

                            // collect only the inserted IDs
                            $insertedServiceIds[] = $item->id;
                        }
                    }

                    Log::info($insertedServiceIds); 

                    // Only pass inserted IDs to deactivate function
                    if (!empty($insertedServiceIds)) {
                        $getAppointmentRec = DB::connection('tenant')
                            ->table('appointment')
                            ->where('id', $value->id)
                            ->first();

                        $this->deactivateReminderNew($getAppointmentRec, $insertedServiceIds);
                    } 
                    //end



                }//else insert
            } // foreach
        } //if appointments

    }//_commandOperation




   public function deactivateReminderNew($appoitment,$services=array())
    {
        
        Log::info("in _deactivateReminderNew function");
        Log::info("appoitment=id=>");
        Log::info($appoitment->id);

        Log::info("services==>");
        Log::info($services);

        $appointmentServices=array();
        $all_services = DB::connection('tenant')
                        ->table('appoinment_type_has_examinations')
                        ->select('examination_id')
                        ->where(['appoinment_id'=>$appoitment->appointment_type_id])
                        ->get();

        Log::info("all_services==>");
        Log::info($all_services);                


        foreach ($all_services as $key => $value) {
            $appointmentServices[]=$value->examination_id;
            // Log::info($appointmentServices[]);
            if(is_array($services) && in_array($value->examination_id, $services)) //condition added in 2-jan-24
           {
                $ids = DB::connection('tenant')->table('patient_has_service_reminder')
                                ->where(['patient_id'=>$appoitment->patient_id,
                                'status'=>'activate',
                                'service_id'=>$value->examination_id])
                                ->whereIn('reminder_status',['Set','ignore'])
                                ->get();
                $id_holder = [];
                $generalServcieCheck=1;
                //Dont deactivdate general remidner when we create the appoitment added by Swati 20-Oct-22
                 $checkGeneralServcie=DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                    ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                    ->where('service_id',$value->examination_id)
                    ->get();
                // if(!empty($checkGeneralServcie)) //commented on 2-jan-24 for deactivate services on book and //added on 2-jan-24              
                if(!empty($checkGeneralServcie) && isset($checkGeneralServcie) && $checkGeneralServcie->count() > 0) 
                {

                    $today_date=date("Y-m-d");
                    $checkServiceReminders = DB::connection('tenant')->table('patient_has_service_reminder')
                                    ->where('service_id',$value->examination_id)
                                    ->where('patient_id',$appoitment->patient_id)
                                    ->whereIn('reminder_status',['Set','ignore'])
                                    ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                    ->first();

                    if(!empty($ids) && empty($checkServiceReminders))
                    {

                        foreach($ids as $id=>$value_id)
                        {                    
                            $id_holder[] = $value_id->id;
                        }
                    }
                    else $generalServcieCheck=0;
                }
                else{
                    Log::info("in else");
                     if(!empty($ids))
                    {
                        foreach($ids as $id=>$value_id)
                        {                    
                            $id_holder[] = $value_id->id;
                        }
                    }
                }
                //End====================================================================
                if($generalServcieCheck){
                    $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                               ->whereIn('service_reminder_id',$id_holder)
                                               ->update(['status'=>'deactivate']);


                   if(isset($value->examination_id)){
                        Log::info("in above condition exam to be updated deactivate is ".$value->examination_id);                  
                   }                            
                   DB::connection('tenant')->table('patient_has_service_reminder')->where(['patient_id'=>$appoitment->patient_id,'status'=>'activate','service_id'=>$value->examination_id])->whereIn('reminder_status',['Set','ignore'])->update(['status'=>'deactivate']);
                }
            }//if inarray   

        }//foreach

        Log::info("if services below condition");
        if(is_array($services) && !empty($services)){
            foreach ($services as $value) {
                // log::info($value);
                // log::info($appointmentServices);
                if(!in_array($value, $appointmentServices)){
                    $ids = DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where(['patient_id'=>$appoitment->patient_id,
                                        'status'=>'activate',
                                        'service_id'=>$value])
                                        ->whereIn('reminder_status',['Set','ignore'])
                                        ->get();
                    $id_holder = [];
                    $generalServcieCheck=1;
                    //Dont deactivdate general remidner when we create the appoitment added by Swati 20-Oct-22
                    $checkGeneralServcie=DB::connection('tenant')->table('preferred_channels_for_reminders_setting')
                        ->where('preferred_channels_for_reminders_setting.activated_reminder','general')
                        ->where('service_id',$value)
                        ->get();
                    if(!empty($checkGeneralServcie)){
                        $today_date=date("Y-m-d");
                        $checkServiceReminders =  DB::connection('tenant')->table('patient_has_service_reminder')
                                        ->where('service_id',$value)
                                        ->where('patient_id',$appoitment->patient_id)
                                        ->whereIn('reminder_status',['Set','ignore'])
                                        ->whereRaw("date(reminder_date) >= '".$today_date."'") 
                                        ->first();
                        if(!empty($ids) && empty($checkServiceReminders))
                        {
                            foreach($ids as $id=>$value_id)
                            {                    
                                $id_holder[] = $value_id->id;
                            }
                        }
                    }
                    else{
                         if(!empty($ids))
                        {
                            foreach($ids as $id=>$value_id)
                            {                    
                                $id_holder[] = $value_id->id;
                            }
                        }
                    }
                    if($generalServcieCheck){
                        // log::info($value);
                        $reactivateReminder =  DB::connection('tenant')->table('patient_has_reminder')
                                                   ->whereIn('service_reminder_id',$id_holder)
                                                   ->update(['status'=>'deactivate']);

                        if(isset($value)){
                           Log::info("in below condition exam to be updated deactivate is".$value);
                        }                              
                                                     
                        DB::connection('tenant')->table('patient_has_service_reminder')->where(['patient_id'=>$appoitment->patient_id,'status'=>'activate','service_id'=>$value])->whereIn('reminder_status',['Set','ignore'])->update(['status'=>'deactivate']);
                    }
                }
            }
        }
        Log::info("stop if"); 
    }

}
