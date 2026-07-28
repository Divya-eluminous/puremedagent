<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\UserHasRetrievedCertificateModel;
use App\Models\MigrationTables;
use Orchestra\Parser\Xml\Facade as XmlParser;
use DB;
use Storage;


class PatientFindingMigrateCheckFromGanymed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patient-migrate-finding:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        //Step1: to get all the patient data iterate and create an array and do bulk insert in our db
        dump('start');

        /*$patientGanyFindings = DB::connection('sqlsrv')
                                    ->table('patient')
                                    ->where('ID',"=",1)
                                    ->get();
        dd($patientGanyFindings);*/

        //insert
        /*$getLastRecord =  DB::table("patients")
                             ->where('old_id','!=','0')
                             ->orderBy('id','DESC')
                             ->first(['old_id']);
        
        if(!empty($getLastRecord)){*/

            $patientGanyFindings = DB::connection('sqlsrv')
                                    ->table('fremdbefunde')
                                    ->get();
                                    // ->paginate(10);;
           /* $patientGanyFindings = DB::table('fremdbefunde')
                                ->paginate(10);*/
                                    // ->get();                                    
                                // ->where('ID',">",$getLastRecord->old_id)
                                // ->orderBy('ID','DESC')
           // dd($patientGanyFindings);
            $all_records = [];
            $index_key = 0;
            if(!empty($patientGanyFindings) && count($patientGanyFindings)>0){

                foreach ($patientGanyFindings as $patientGanyFinding) {


                    $tmp = [];
                    $tmp['pat_nr']      = trim($patientGanyFinding->pat_nr);
                    $tmp['dat'] = trim($patientGanyFinding->dat);
                    $tmp['sysdat']   = trim($patientGanyFinding->sysdat);
                    $tmp['text']            = trim($patientGanyFinding->text);
                    $tmp['datei']         = trim($patientGanyFinding->datei);
                    $tmp['langtext']          = trim($patientGanyFinding->langtext);
                    $tmp['von_fremd']          = trim($patientGanyFinding->von_fremd);
                    $tmp['BenutzerID']          = trim($patientGanyFinding->BenutzerID);
                    $tmp['gelesen']          = trim($patientGanyFinding->gelesen);

                   /* DB::table("fremdbefunde")
                        ->insert($tmp);*/

                    echo "Inserted";
                }


                    

            }else{
                 echo "no record found";
            }

       // }

           
       

       
        


       
    }
}