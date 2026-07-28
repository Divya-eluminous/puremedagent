<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\UserHasRetrievedCertificateModel;
use App\Models\MigrationTables;
use Orchestra\Parser\Xml\Facade as XmlParser;
use Illuminate\Support\Facades\Log;
use DB;
use Storage;


class AppPatientUpdatedFromGanymed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'New-patient-migrate:update';

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
        dump('start');
        //dd('in');
         $txt = "";
        $random_number = rand(3,1000);
        //Log::info("called 99999 patient add cron=".$random_number);

        try {
        
        //update live server records with id=99999
        $getupdateRecords =  DB::table("patients")
                             ->where('old_id','=','99999')
                             ->get();
        //dd(count($getupdateRecords));
        if(!empty($getupdateRecords) && sizeof($getupdateRecords)>0){

            foreach ($getupdateRecords as  $patientRecord) {

                 $patientGanymedRecord = DB::connection('sqlsrv')
                                            ->table('patient')
                                            ->where('tel_nr','LIKE','%'.$patientRecord->mobile_no.'%')
                                            ->where('famname','LIKE','%'.$patientRecord->family_name.'%')
                                            ->where('vorname','LIKE','%'.$patientRecord->first_name.'%')
                                            ->first();
               // dump($patientGanymedRecord);
                if(!empty($patientGanymedRecord)){

                    $birth_date = date("Y-m-d", strtotime(trim($patientGanymedRecord->geb_dat)));
                    $age = (date('Y') - date('Y',strtotime($birth_date)));

                    $tmp = [];
                    $tmp['old_id']      = trim($patientGanymedRecord->ID);
                    $tmp['pat_nr']      = trim($patientGanymedRecord->pat_nr);
                    $tmp['family_name'] = trim($patientGanymedRecord->famname);
                    $tmp['first_name']  = trim($patientGanymedRecord->vorname);
                    $tmp['email']       = trim($patientGanymedRecord->eMail);
                    $tmp['road']        = trim($patientGanymedRecord->strasse);
                    $tmp['postal_code'] = trim($patientGanymedRecord->plz);
                    $tmp['place']       = trim($patientGanymedRecord->ort);
                    $tmp['insurance_number']    = trim($patientGanymedRecord->vers_nr);
                    $tmp['ganymed_mobile_no']   = trim($patientGanymedRecord->tel_nr);
                    $tmp['size']                = trim($patientGanymedRecord->groesse);
                    $tmp['weight']             = trim($patientGanymedRecord->gewicht);
                    $tmp['title']               = trim($patientGanymedRecord->titel);
                    $tmp['family_doctor']       = trim($patientGanymedRecord->Hausarzt);
                    $tmp['additional_insurance'] = trim($patientGanymedRecord->zu_vers);
                    $tmp['birth_date']           =  $birth_date;
                    $tmp['age']                  = $age;
                   // dump($tmp);
                    DB::table("patients")
                                ->where('id','=',$patientRecord->id)
                                ->update($tmp); 
                   echo $txt .= "\n updated patient data for matching record-99999:".$patientRecord->id;                    
                }
            }
        }        

        $datetime = Date('Y-m-d-h-i-s');
        if(!empty($txt) && strlen($txt)>0)
        {
            Storage::append("reports/syn-".$datetime.".txt", $txt);
        }
        //Log::info("stoped 99999 patient add cron=".$random_number);
        dump('end');
        dd('done');   

        }
        catch(\Exception $e) {
            $datetime = Date('Y-m-d-h-i-s');
             echo $txt .= "\n Error:".$e->getMessage();
             Storage::append("reports/syn-".$datetime.".txt", $txt);
        }  


       
        


       
    }
}