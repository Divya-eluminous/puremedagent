<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserHasRetrievedCertificateModel;
use App\Models\MigrationTables;
use Orchestra\Parser\Xml\Facade as XmlParser;


use Illuminate\Support\Facades\Log;
use DB;
use Storage;

class PatientMigrateUpdateFromGanymed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patient-migrate:update';

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
        //Log::info("called the cron");
        try 
        {       
            $txt = "";
        
            //get all gynemed data 
            $patientGanymedRecords = DB::connection('sqlsrv')
                                ->table('patient')
                                //->where('pat_nr','23769')
                                ->get();
                            
           // dd($patientGanymedRecords);
            $all_records = [];
            if(!empty($patientGanymedRecords) && count($patientGanymedRecords)>0)
            {                
                foreach ($patientGanymedRecords as  $key=>$patientGanymedRecord) 
                {     
                    dump("\nid=".$key);
                    if(!empty($patientGanymedRecord))
                    {           
                        $checkRecord =  DB::table("patients")
                                            ->where('old_id','=',$patientGanymedRecord->ID)
                                            ->first();

                        if(!empty($checkRecord))
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
                            $mobile_no      = trim($mobile_no,"'");

                            $tmp = [];
                            $prev_tmp = [];
                            $famname = trim(trim($patientGanymedRecord->famname),"'");
                            if($famname != $checkRecord->family_name)
                            {
                                $tmp['family_name'] = $famname;
                                $prev_tmp['family_name'] = $checkRecord->family_name;
                            }
                            $vorname = trim(trim($patientGanymedRecord->vorname),"'");
                            
                            if(trim($vorname) != $checkRecord->first_name)
                            {
                                $tmp['first_name'] = trim($vorname);
                                $prev_tmp['first_name'] = $checkRecord->first_name;
                            }
                            $eMail = trim(trim($patientGanymedRecord->eMail),"'");
                            
                            if(trim($eMail) != $checkRecord->email)
                            {
                                $tmp['email'] = trim($eMail);
                                $prev_tmp['email'] = $checkRecord->email;
                            }
                            $strasse = trim(trim($patientGanymedRecord->strasse),"'");

                            if(trim($strasse) != $checkRecord->road)
                            {
                                $tmp['road'] = trim($strasse);
                                $prev_tmp['road'] = $checkRecord->road;
                            }                            
                            $plz = trim(trim($patientGanymedRecord->plz),"'");
                            
                            if(trim($plz) != $checkRecord->postal_code)
                            {
                                $tmp['postal_code'] = trim($plz);
                                $prev_tmp['postal_code'] = $checkRecord->postal_code;
                            }
                            $ort = trim(trim($patientGanymedRecord->ort),"'");
                            
                            if(trim($ort) != $checkRecord->place)
                            {
                                $tmp['place'] = trim($ort);
                                $prev_tmp['place'] = $checkRecord->place;
                            }
                            $vers_nr = trim(trim($patientGanymedRecord->vers_nr),"'");
                            
                            if(trim($vers_nr) != $checkRecord->insurance_number)
                            {
                                $tmp['insurance_number'] = trim($vers_nr);
                                $prev_tmp['insurance_number'] = $checkRecord->insurance_number;
                            }
                            if($birth_date != $checkRecord->birth_date)
                            {
                                $tmp['birth_date'] = $birth_date;
                                $prev_tmp['birth_date'] = $checkRecord->birth_date;
                            }
                            if($age != $checkRecord->age)
                            {
                                $tmp['age'] = $age;
                                $prev_tmp['age'] = $checkRecord->age;
                            }
                            $tel_nr = trim(trim($patientGanymedRecord->tel_nr),"'");
                            
                            if(trim($tel_nr) != $checkRecord->ganymed_mobile_no)
                            {
                                $tmp['ganymed_mobile_no'] = trim($tel_nr);
                                $prev_tmp['ganymed_mobile_no'] = $checkRecord->ganymed_mobile_no;
                            }
                            if(trim($country_code) != $checkRecord->country_code)
                            {
                                $tmp['country_code'] = trim($country_code);
                                $prev_tmp['country_code'] = $checkRecord->country_code;
                            }
                            if(trim($mobile_no) != $checkRecord->mobile_no)
                            {
                                $tmp['mobile_no'] = trim($mobile_no);
                                $prev_tmp['mobile_no'] = $checkRecord->mobile_no;
                            }
                            $groesse = trim(trim($patientGanymedRecord->groesse),"'");
                           
                            if(trim($groesse) != $checkRecord->size)
                            {
                                $tmp['size'] = trim($groesse);
                                $prev_tmp['size'] = $checkRecord->size;
                            }
                            $gewicht = trim(trim($patientGanymedRecord->gewicht),"'");
                           
                            if(trim($gewicht) != $checkRecord->weight)
                            {
                                $tmp['weight'] = trim($gewicht);
                                $prev_tmp['weight'] = $checkRecord->weight;
                            }
                            $titel = trim(trim($patientGanymedRecord->titel),"'");
                            
                            if(trim($titel) != $checkRecord->title)
                            {
                                $tmp['title'] = trim($titel);
                                $prev_tmp['title'] = $checkRecord->title;
                            }
                            $Hausarzt = trim(trim($patientGanymedRecord->Hausarzt),"'");

                            if(trim($Hausarzt) != $checkRecord->family_doctor)
                            {
                                $tmp['family_doctor'] = trim($Hausarzt);
                                $prev_tmp['family_doctor'] = $checkRecord->family_doctor;
                            }                            
                            $zu_vers = trim(trim($patientGanymedRecord->zu_vers),"'");
                           
                            if(trim($zu_vers) != $checkRecord->additional_insurance)
                            {
                                $tmp['additional_insurance'] = trim($zu_vers);
                                $prev_tmp['additional_insurance'] = $checkRecord->additional_insurance;
                            }
                            $geschl = trim(trim($patientGanymedRecord->geschl),"'");
                            if(trim(strtolower($geschl)) != strtolower($checkRecord->gender))
                            {
                                $tmp['gender'] = trim($geschl);
                                $prev_tmp['gender'] = $checkRecord->gender;
                            }
                            if(!empty($tmp) && count($tmp))
                            {
                               // dd($tmp);
                                $array_data = json_encode($tmp);
                                $prv_array_data = json_encode($prev_tmp);
                                $txt .= "\r\nGanymed id=".$patientGanymedRecord->ID." | first_name=".trim($vorname)." | last_name=".$famname." will be update \nNew Record=".$array_data."\nOld Record=".$prv_array_data;


                                DB::table("patients")
                                ->where('id','=',$checkRecord->id)
                                ->update($tmp);
                               
                                // dump("--------------------------------------------------------------");
                            }else
                            {
                                // $txt .= "\r\nNo change in Ganymed id=".$patientGanymedRecord->ID." | first_name=".$patientGanymedRecord->vorname." | last_name=".$patientGanymedRecord->famname."";
                                // dump($txt);
                                // dump("--------------------------------------------------------------");
                            }
                        }
                        else
                        {
                           // $txt .= "\r\nGanymed id=".$patientGanymedRecord->ID." Not Found in app server";
                           // dump($txt);
                            // dump("--------------------------------------------------------------");
                        }
                    }                    
                }
            }
            else
            {
                 echo "no record found";
            }
            if(!empty($txt) && strlen($txt)>0)
            {
                $datetime = Date('Y-m-d-h-i-s');

                Storage::append("reports/syn-updated-new".$datetime.".txt", $txt);
               // dd($txt,strlen($txt));
            }
        dump('end');
        dd('done');  

        }
        catch(\Exception $e) {
            $datetime = Date('Y-m-d-h-i-s');
             echo $txt .= "\n Error:".$e->getMessage();
             Storage::append("reports/syn-updated-".$datetime.".txt", $txt);
        }  
    }
}