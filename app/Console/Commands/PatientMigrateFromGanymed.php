<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\UserHasRetrievedCertificateModel;
use App\Models\MigrationTables;
use Orchestra\Parser\Xml\Facade as XmlParser;
use Illuminate\Support\Facades\Log;
use DB;
use Storage;


class PatientMigrateFromGanymed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patient-migrate:add';

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
        $random_number = rand(3,1000);
        //Log::info("called patient add cron=".$random_number);

        try {
        /*$patientGanymedRecords = DB::connection('sqlsrv')
                                    ->table('patient')
                                    ->where('ID',"=",1)
                                    ->get();
        dd($patientGanymedRecords);*/
        $txt = "";
        //insert
        $getLastRecord =  DB::table("patients")
                             ->where('old_id','!=','0')
                             ->where('old_id','!=','99999')
                             ->orderBy('id','DESC')
                             ->first(['old_id']);

                          //   dd($getLastRecord);
        
        if(!empty($getLastRecord)){

            $patientGanymedRecords = DB::connection('sqlsrv')
                                    ->table('patient')
                                    ->where('ID',">",$getLastRecord->old_id)
                                    ->get();
                                // ->orderBy('ID','DESC')
                                // ->paginate(10);
           // dd(count($patientGanymedRecords));
            $all_records = [];
            if(!empty($patientGanymedRecords) && count($patientGanymedRecords)>0){

                foreach ($patientGanymedRecords as  $patientGanymedRecord) {
                    
                    if(!empty($patientGanymedRecord))
                    {   

                        $gany_mobile_no     = trim($patientGanymedRecord->tel_nr);

                        // $diff = (date('Y') - date('Y',strtotime($dob)));
                        $birth_date = date("Y-m-d", strtotime(trim($patientGanymedRecord->geb_dat)));
                        $age = (date('Y') - date('Y',strtotime($birth_date)));

                        $internationalFormat = substr($gany_mobile_no, 0, 1);
                        $country_code = '';
                        $mobile_no    = '';
                        if($internationalFormat == '+'){
                            $country_code = trim(substr($gany_mobile_no, 1,2));
                            $mobile_no      = trim(str_replace(" ","",substr($gany_mobile_no, 3)));
                        }

                        $internationalFormat = substr($gany_mobile_no, 0, 2);
                        if($internationalFormat == '00'){
                            $country_code = trim(substr($gany_mobile_no, 0,4));
                            $mobile_no      = trim(str_replace(" ","",substr($gany_mobile_no, 4)));
                        }

                        $localFormat = substr($gany_mobile_no, 0, 1);
                        if($localFormat == '0' && $internationalFormat != '00'){
                            $country_code = trim(substr($gany_mobile_no, 0,1));
                            $mobile_no      = trim(str_replace(" ","",substr($gany_mobile_no, 1)));
                        }

                        $mobile_no      = trim(str_replace("/","",$mobile_no));
                        $mobile_no      = trim(str_replace("-","",$mobile_no));

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
                        $tmp['birth_date']          = $birth_date;
                        $tmp['age']                 = $age;
                        $tmp['ganymed_mobile_no']   = trim($patientGanymedRecord->tel_nr);
                        $tmp['country_code']        = trim($country_code);
                        $tmp['mobile_no']           = trim($mobile_no);
                        $tmp['size']                = trim($patientGanymedRecord->groesse);
                        $tmp['weight']              = trim($patientGanymedRecord->gewicht);
                        $tmp['title']               = trim($patientGanymedRecord->titel);
                        $tmp['salutation']          = 'Fr';
                        $tmp['family_doctor']       = trim($patientGanymedRecord->Hausarzt);
                       
                        $tmp['additional_insurance'] = trim($patientGanymedRecord->zu_vers);
                        $tmp['gender']       = trim($patientGanymedRecord->geschl);

                        $checkRecord =  DB::table("patients")
                                            ->where('old_id','=',$tmp['old_id'])
                                            ->first(['id']);

                        if(!empty($checkRecord)){
                            //$all_records[] = $tmp;
                            /*DB::table("patients")
                                ->where('id','=',$checkRecord->id)
                                ->update($tmp);*/
                                echo "not updated";
                                $txt .= "\n not updated";
                        }else{
                            //$all_records[] = $tmp;

                             $checkAppRecord =  DB::table("patients")
                            ->where('family_name','=',$tmp['family_name'])
                            ->where('first_name','=',$tmp['first_name'])
                            ->where('old_id','99999')
                            ->whereNULL('deleted_at')
                            ->get();

                            if(!empty($checkAppRecord) && count($checkAppRecord) > 0)
                            {
                                if(count($checkAppRecord) == 1)
                                {
                                    // DB::table('patients')->where('id',$checkAppRecord[0]->id)->update(['old_id'=>$tmp['old_id'],'pat_nr'=>$tmp['pat_nr']]); 

                                     DB::table('patients')->where('id',$checkAppRecord[0]->id)->update($tmp); 

                                    $txt .= '\n 1 record exist, update '.$checkAppRecord[0]->id.' having old id'.$checkAppRecord[0]->old_id." with ".$tmp['old_id'];   
                                }else
                                {
                                    foreach ($checkAppRecord as $key => $value) 
                                    {
                                        if($value->old_id == '99999')
                                        {
                                            $appoitment_count = $this->AppointmentModel->where('patient_id',$value->id)->get();
                                            //dd($appoitment_count);
                                            if(count($appoitment_count) > 0)
                                            {
                                               DB::table('patients')->where('id',$value->id)->update(['old_id'=>$tmp['old_id'],'pat_nr'=>$tmp['pat_nr']]); 
                                                $txt .= '\n more record exist, update '.$value->id.' having old id'.$value->old_id." with ".$tmp['old_id'];
                                            }
                                        }
                                        else
                                        {
                                            $txt .= '\n dont insert or update me'.$tmp['old_id']."=".$value->old_id;
                                        }         
                                    }
                                }
                            }
                            else
                            {
                                DB::table("patients")->insert($tmp);
                                echo "inserted:".$tmp['old_id'];
                                $txt .= "\n inserted:".$tmp['old_id'];
                            }
                        }


                    }
                   
                }
                // print_r($all_records);
                // exit();

               //DB::table("patients")->insert($all_records);

            }else{
                 echo "no record found";
            }

        }



        //update gany db records
        $getupdateRecords =  DB::table("patients")
                             ->where('update_ganydb','=','1')
                             ->get();
        //dd($getupdateRecords);
        if(!empty($getupdateRecords) && sizeof($getupdateRecords)>0){

            foreach ($getupdateRecords as  $patientRecord) {

                $tmp = [];
                $tmp['pat_nr']  = trim($patientRecord->pat_nr);
                $tmp['famname'] = trim($patientRecord->family_name);
                $tmp['vorname'] = trim($patientRecord->first_name);
                $tmp['eMail']   = trim($patientRecord->email);
                $tmp['strasse'] = trim($patientRecord->road);
                $tmp['plz']     = trim($patientRecord->postal_code);
                $tmp['ort']     = trim($patientRecord->place);
                $tmp['vers_nr'] = trim($patientRecord->insurance_number);
                $tmp['tel_nr']  = $patientRecord->country_code.$patientRecord->mobile_no;
                $tmp['groesse'] = trim($patientRecord->size);
                $tmp['gewicht'] = trim($patientRecord->weight);
                $tmp['titel']   = trim($patientRecord->title);
                $tmp['Hausarzt'] = trim($patientRecord->family_doctor);
                $tmp['zu_vers']  = trim($patientRecord->additional_insurance);
                //$tmp['geschl']   = "'".trim($patientRecord->gender)."'";
                $tmp['geb_dat']  =  date('Y-m-d\TH:i:s',strtotime($patientRecord->birth_date));
                // $patientGanymedRecord->geb_dat
                //if($tmp['eMail'] == 'judith.huck.97@googlemail.com')
                $check_record_exist = DB::connection('sqlsrv')
                                    ->table('patient')
                                    ->where('ID','=',$patientRecord->old_id)
                                    ->first();

                if(!empty($check_record_exist))
                {
                    $update_ganyData = DB::connection('sqlsrv')
                    ->table('patient')
                    ->where('ID','=',$patientRecord->old_id)
                    ->update($tmp);

                    DB::table("patients")
                    ->where('id','=',$patientRecord->id)
                    ->update(['update_ganydb'=>0]);                               

                    echo $txt .= "\n updated:".$patientRecord->id;
                }
                

            }            
        }    


        $datetime = Date('Y-m-d-h-i-s');
        //Storage::append("reports/syn-".$datetime.".txt", $txt);
        if(!empty($txt) && strlen($txt)>0)
        {
            Storage::append("reports/syn-".$datetime.".txt", $txt);
        }
        //Log::info("new stoped patient add cron=".$random_number);
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