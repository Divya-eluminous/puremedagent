<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\UserHasRetrievedCertificateModel;
use App\Models\MigrationTables;
use App\Models\AppointmentModel;
use Orchestra\Parser\Xml\Facade as XmlParser;
use Illuminate\Support\Facades\Log;
use DB;
use Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PatientMigrationCollectionExport;
use App\Exports\PatientMigrationUpdateGanymedCollectionExport;


class PatientMigrateFromGanymedNew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patient-migrate-new:add';

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
    public function __construct(AppointmentModel $AppointmentModel)
    {
        parent::__construct();
        $this->AppointmentModel = $AppointmentModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Add log in log file        
        dump('start');
        $data = array();
        $random_number = rand(3,1000);
        
        Log::info("called New patient cron=".$random_number);

        $a = array('status','old_id','pat_nr','family_name','first_name','email','road','postal_code','place','insurance_number','birth_date','age','ganymed_mobile_no','country_code','mobile_no','size','weight','title','salutation','family_doctor','additional_insurance','gender');
        try 
        {       
            /*    New user, update user.track duplicate user     */
                dump('1st section start');
                $report_title = date('Y-m-d H:i:s').'New-updated-entries.xls';
                $getLastRecord =  DB::table("patients")
                                     ->where('old_id','!=','0')
                                     ->where('old_id','!=','99999')
                                     ->orderBy('id','DESC')
                                     ->first(['old_id']);

                //dd($getLastRecord);        
                if(!empty($getLastRecord))
                {
                   $patientGanymedRecords = DB::connection('sqlsrv')->table('patient');
                    //$patientGanymedRecords = DB::table('patient');

                    $patientGanymedRecords = $patientGanymedRecords
                                            ->where('ID',">",$getLastRecord->old_id)
                                            ->get();
                                       
                    //dd(count($patientGanymedRecords));
                    $all_records = [];
                    if(!empty($patientGanymedRecords) && count($patientGanymedRecords)>0)
                    {
                        foreach ($patientGanymedRecords as  $key=>$patientGanymedRecord) 
                        {
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
                                $tmp['mobile_no']           = ltrim(trim($mobile_no),0);
                                $tmp['size']                = trim($patientGanymedRecord->groesse);
                                $tmp['weight']              = trim($patientGanymedRecord->gewicht);
                                $tmp['title']               = trim($patientGanymedRecord->titel);
                                $tmp['salutation']          = 'Fr';
                                $tmp['family_doctor']       = trim($patientGanymedRecord->Hausarzt);
                               
                                $tmp['insurance_number'] = trim($patientGanymedRecord->vers_nr);

                                $tmp['additional_insurance'] = trim($patientGanymedRecord->zu_vers);
                                $tmp['gender']       = trim($patientGanymedRecord->geschl);

                                $data[$key] = $tmp;
                                $checkRecord =  DB::table("patients")
                                                    ->where('old_id','=',$tmp['old_id'])
                                                    ->first(['id']);

                                if(empty($checkRecord))
                                {
                                    $first_name = preg_split("/[\s,\-,\_]+/", $tmp['first_name']);

                                    $splited_first_name = $first_name[0];
                                   
                                    $checkSplittedAppRecord =  DB::table("patients")
                                                        ->where(DB::raw('upper(family_name)'),'=',strtoupper($tmp['family_name']))
                                                        ->where(DB::raw('upper(first_name)'),'=',strtoupper($splited_first_name))
                                                        ->where('old_id','99999')
                                                        ->whereNULL('deleted_at')
                                                        ->get();

                                    $checkAppRecord =  DB::table("patients")
                                                         ->where(DB::raw('upper(family_name)'),'=',strtoupper($tmp['family_name']))
                                                        ->where(DB::raw('upper(first_name)'),'=',strtoupper($tmp['first_name']))
                                                        ->where('old_id','99999')
                                                        ->whereNULL('deleted_at')
                                                        ->get();

                                    $finalAppRecord = collect();
                                    $splited_string = '';

                                    if(!empty($checkAppRecord) && count($checkAppRecord)> 0)
                                    {                                       
                                        $splited_string = 'First Name take as it is - ';
                                        $finalAppRecord = $checkAppRecord;
                                    }

                                    if(!empty($checkSplittedAppRecord) && count($checkSplittedAppRecord)> 0)
                                    {
                                        $splited_string = 'First Name splited -';
                                        $finalAppRecord = $checkSplittedAppRecord;
                                    }

                                    if(!empty($finalAppRecord) && count($finalAppRecord) > 0)
                                    {
                                        if(count($finalAppRecord) == 1)
                                        {
                                            $data[$key]['operation_status'] = $splited_string.'Record is exist in the app server. Updated app server data with all entries.';

                                            //dd($data); 
                                            DB::table('patients')->where('id',$finalAppRecord[0]->id)->update($tmp);
                                        }
                                        else
                                        {
                                            foreach ($finalAppRecord as $f_key => $value) 
                                            {
                                                if($value->old_id == '99999')
                                                {
                                                    $appoitments = $this->AppointmentModel
                                                                        ->where('patient_id',$value->id) 
                                                                        ->get();
                                                    //dd($appoitment_count);
                                                    if(count($appoitments) == 1)
                                                    {
                                                        DB::table('patients')->where('id',$value->id)->update($tmp);

                                                        $data[$key]['operation_status'] = $splited_string.'Appointment exist. Take the recent Appointment patient id';       
                                                    }
                                                    elseif(count($appoitments) >1)
                                                    {
                                                        foreach ($appoitments as $a_key => $appoitment) 
                                                        {
                                                            $appoitmnet_date = date('Y-m-d',strtotime($appoitments->start_date));

                                                            if($appoitmnet_date == date('Y-m-d'))
                                                            {
                                                                DB::table('patients')->where('id',$value->id)->update($tmp); 
                                                                $data[$key]['operation_status'] = $splited_string.'Appointment is matched with current date. Update the record';
                                                            }else
                                                            {
                                                                DB::table('patients')->where('id',$value->id)->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                                                                $data[$key]['operation_status'] = $splited_string.'Appointment is not match with current date. Deleted the record';
                                                            }
                                                        }
                                                    }
                                                    else
                                                    {
                                                        DB::table('patients')->where('id',$value->id)->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                                                        $data[$key]['operation_status'] = $splited_string.'Appointment Not exist. Delete the record';
                                                    }
                                                }
                                                else
                                                {
                                                    DB::table('patients')->where('id',$value->id)->update(['deleted_at'=>date('Y-m-d H:i:s')]);
                                                    $data[$key]['operation_status'] = $splited_string.'Patient not having 99999 old id. Record is deleted';
                                                }         
                                            }
                                        }
                                    }
                                    else
                                    {
                                        DB::table("patients")->insert($tmp);
                                        $data[$key]['operation_status'] = 'New record is added.';
                                    }
                                }
                                else
                                {
                                    // DB::table("patients")
                                    //     ->where('id','=',$checkRecord->id)
                                    //     ->update($tmp);  

                                    $data[$key]['operation_status'] = 'Ganymed data is exist on the app server data.No operation is perform';
                                }
                            }                       
                        }
                    }
                    else
                    {
                         echo "no record found";
                    }
                }           
                if(!empty($data) && count($data) > 0)
                {
                    $file = Excel::store(new PatientMigrationCollectionExport($data,$a), 'migration_files/'.$report_title);
                }
                dump('1st section end');
            /*     End    */

            /*    update_ganydb = 1      */
                dump('2nd section start');
                $report_title = date('Y-m-d H:i:s').'-update-ganymed-server-after-app-server-changes.xls';
                $getupdateRecords =  DB::table("patients")
                                     ->where('update_ganydb','=','1')
                                     ->get();

                $update_ganydb_data = array();
                //dd($getupdateRecords);
                if(!empty($getupdateRecords) && sizeof($getupdateRecords)>0)
                {
                    foreach ($getupdateRecords as  $key=>$patientRecord) 
                    {
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
                       
                        $update_ganydb_data[$key] = $tmp;
                        $update_ganydb_data[$key]['old_id'] = $patientRecord->old_id;
                        $update_ganydb_data[$key]['age'] = $patientRecord->age;
                        $update_ganydb_data[$key]['country_code'] = $patientRecord->country_code;
                        $update_ganydb_data[$key]['mobile_no'] = $patientRecord->mobile_no;
                        $update_ganydb_data[$key]['gender'] = $patientRecord->gender;
                        $update_ganydb_data[$key]['salutation'] = $patientRecord->salutation;
                        // $patientGanymedRecord->geb_dat
                        //if($tmp['eMail'] == 'judith.huck.97@googlemail.com')
                        $check_record_exist = DB::connection('sqlsrv')->table('patient')
                                            ->where('ID','=',$patientRecord->old_id)
                                            ->first();

                        if(!empty($check_record_exist))
                        {
                            $update_ganyData = DB::connection('sqlsrv')->table('patient')
                            ->where('ID','=',$patientRecord->old_id)
                            ->update($tmp);

                            DB::table("patients")
                            ->where('id','=',$patientRecord->id)
                            ->update(['update_ganydb'=>0]);  

                            $update_ganydb_data[$key]['operation_status'] = 'Ganymed server data updated, who is having update_ganydb status is 1 on the app server';                    
                        }else
                        {
                            $update_ganydb_data[$key]['operation_status'] = 'Not updated. Record Not found on ganymed server.';
                        }
                        

                    }            
                } 
                if(!empty($update_ganydb_data) && count($update_ganydb_data) > 0)
                {
                    $file = Excel::store(new PatientMigrationUpdateGanymedCollectionExport($update_ganydb_data,$a), 'migration_files/'.$report_title);
                }
                dump('2nd section end');
            /*     End    */

            /*    Ganymed id 9999999      */
                dump('3rd section start');
                $report_title = date('Y-m-d H:i:s').'-app-server-data-change-for-99999-users.xls';
                $getupdateRecords =  DB::table("patients")
                                     ->where('old_id','=','99999')
                                     ->get();

                $update_app_data = array();
                //dd(count($getupdateRecords));
                if(!empty($getupdateRecords) && sizeof($getupdateRecords)>0)
                {
                    foreach ($getupdateRecords as  $patientRecord) 
                    {

                        $patientGanymedRecord = DB::connection('sqlsrv')
                                                    ->table('patient')
                                                    ->where('tel_nr','LIKE','%'.$patientRecord->mobile_no.'%')
                                                    ->where('famname','LIKE','%'.$patientRecord->family_name.'%')
                                                    ->where('vorname','LIKE','%'.$patientRecord->first_name.'%')
                                                    ->first();
                        // dump($patientGanymedRecord);
                        if(!empty($patientGanymedRecord))
                        {

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
                            $update_app_data[$key] = $tmp; 
                            $update_app_data[$key]['country_code'] = $patientGanymedRecord->tel_nr;
                            $update_app_data[$key]['mobile_no'] = $patientGanymedRecord->tel_nr;
                            $update_app_data[$key]['gender'] = $patientGanymedRecord->geschl;
                            $update_app_data[$key]['salutation'] = 'Fr';  
                            $update_ganydb_data[$key]['operation_status'] = 'App server record checked with the ganymed record and updated.';        
                           
                           // dump($tmp);
                            DB::table("patients")
                                        ->where('id','=',$patientRecord->id)
                                        ->update($tmp);                                     
                        }
                        else
                        {
                             $update_ganydb_data[$key]['operation_status'] = 'Not Updated. Record not found in ganymed.';
                        }
                    }
                } 

                if(!empty($update_app_data) && count($update_app_data) > 0)
                {
                    $file = Excel::store(new PatientMigrationCollectionExport($update_ganydb_data,$a), 'migration_files/'.$report_title);
                }
                dump('3rd section end');
            /*    End    */
        }
        catch(\Exception $e) 
        { 
            Log::info("Error in patinet cron-".Date('Y-m-d-h-i-s')."=".$e->getMessage());
        } 
    }
}