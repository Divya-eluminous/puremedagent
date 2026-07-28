<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromArray;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Models\PatientsModel;
use DB;

class PatientCollectionExportNew implements FromArray, WithMultipleSheets, WithHeadings,ShouldAutoSize
{
    use Exportable;

    public function __construct($collection,$headings){
        
        $this->headings = $headings;
        $this->collection = $collection; 
    }

    public function array(): array
    {
        return $this->sheets;
    }
    public function sheets(): array
    {
       // dd($this->collection);
        if(!empty($this->collection) && count($this->collection) > 0)
        {

            foreach ($this->collection as  $key=>$patientRecord) 
            {  
                dump($key);
                $prev_appoitments = DB::table('appointment')->select('start_date')
                                    ->where('patient_id',$patientRecord->id)
                                    ->whereDate('start_date','<',date('Y-m-d'))
                                    ->orderBy('start_date','desc')
                                    ->first();

                $next_appoitments = DB::table('appointment')->select('start_date')
                                    ->where('patient_id',$patientRecord->id)
                                    ->whereDate('start_date','>=',date('Y-m-d'))
                                    ->orderBy('start_date','desc')
                                    ->first();     
               $step1[$key]['app_id']           = $patientRecord->id;
                $step1[$key]['ganymed_id']           = $patientRecord->old_id;
                $step1[$key]['pat_nr']      = $patientRecord->pat_nr; 
                $step1[$key]['previous_appoitment_date']      = $prev_appoitments->start_date ?? ''; 
                $step1[$key]['next_appoitment_date']      = $next_appoitments->start_date ?? '';
                $step1[$key]['family_name']    = $patientRecord->family_name;
                $step1[$key]['first_name']  =  $patientRecord->first_name;
                $step1[$key]['birth_date']          = $patientRecord->birth_date;
                $step1[$key]['mobile_no']           = $patientRecord->mobile_no;
                $step1[$key]['road']        = $patientRecord->road;
                $step1[$key]['postal_code'] = $patientRecord->postal_code;
                $step1[$key]['place']       = $patientRecord->place;
                $step1[$key]['mobile_no']           = $patientRecord->mobile_no;
                $step1[$key]['insurance_number']    = $patientRecord->insurance_number;

                $splited_first_name = preg_split("/[\s,\-,\_]+/", $patientRecord->first_name);
                $first_name = $patientRecord->first_name;
                if(count($splited_first_name)>1)
                {
                    $splited_first_name =  $splited_first_name[0];
                }               

                $checkAppRecord = DB::table("patients")
                                    ->select('old_id','pat_nr','first_name','family_name','birth_date','mobile_no','road','postal_code','place','email','insurance_number','age')
                                    // ->where(DB::raw('upper(family_name)'),'=',strtoupper($patientRecord->family_name))
                                    // ->where(DB::raw('upper(first_name)'),'=',strtoupper($patientRecord->first_name))
                                    ->whereRaw("MATCH(first_name) AGAINST('".$patientRecord->first_name."')")
                                    ->whereRaw("MATCH(family_name) AGAINST('".$patientRecord->family_name."')") 
                                    ->where('id','<>',$patientRecord->id)
                                    ->whereNULL('deleted_at')
                                    ->get();  

                                   
                $str = '';
                $all_matchs = 0; 
                $no_match = 0; 
                $more_matches = 0;
                $str_removed ='';
                $pat_nr_ids = [];
              
                $result = '';
                if(count($checkAppRecord) > 0)
                {    
                    foreach ($checkAppRecord as $ckey => $value) 
                    {

                        $mobile_number = ltrim($value->mobile_no,'0');
                        // dd($value,"hi".$mobile_number.strlen($mobile_number),strlen($value->postal_code),$value->age);
                        if(strlen($mobile_number) >= 6 && (strlen($value->postal_code) == 4 || strlen($value->postal_code) == 5) && $value->age > 14) 
                        {     

                           // dd('if');                      
                            $all_matchs++;
                            if(
                                (!empty($value->birth_date) && !empty($patientRecord->birth_date) && $value->birth_date !=$patientRecord->birth_date) && 
                                (!empty($value->ninsurance_number) && !empty($patientRecord->ninsurance_number) && $value->ninsurance_number !=$patientRecord->ninsurance_number) && 
                                (!empty($value->mobile_no) && !empty($patientRecord->mobile_no) && $value->mobile_no !=$patientRecord->mobile_no))  
                                {
                                    $no_match++;
                                    $str .='Failed   old_id='.$value->old_id.'     pat_nr='.$value->pat_nr.'     first_name='.$value->first_name.'     last_name='.$value->family_name.'     birth_date='.$value->birth_date.'     birth_date_matches='.$patientRecord->birth_date.'     mobile_no='.$value->mobile_no.'     mobile_number_matches='.$patientRecord->mobile_no.'     street='.$value->road.'     street_matches='.$patientRecord->road.'     postal_code='.$value->postal_code.'     place='.$value->place.'     email='.$value->email.'     insurance_number='.$value->insurance_number.'     insurance_number='.$patientRecord->insurance_number;
                                }
                                else
                                {
                                   $more_matches++;
                                    $str .='match”-fields accordingly with “OK”    old_id='.$value->old_id.'     pat_nr='.$value->pat_nr.'     first_name='.$value->first_name.'     last_name='.$value->family_name.'     birth_date='.$value->birth_date.'     birth_date_matches='.$patientRecord->birth_date.'     mobile_no='.$value->mobile_no.'     mobile_number_matches='.$patientRecord->mobile_no.'     street='.$value->road.'     street_matches='.$patientRecord->road.'     postal_code='.$value->postal_code.'     place='.$value->place.'     email='.$value->email.'     insurance_number='.$value->insurance_number.'     insurance_number='.$patientRecord->insurance_number;
                                    $pat_nr_ids[] = $value->pat_nr;
                                }
                        }
                        else
                        {
                           // dd("else");
                            $no_match++;
                            $str_removed .= 'old_id='.$patientRecord->old_id.'     pat_nr='.$patientRecord->pat_nr.'     first_name='.$patientRecord->first_name.'     last_name='.$patientRecord->family_name.'     birth_date='.$patientRecord->birth_date.'     mobile_no='.$patientRecord->mobile_no.'     street='.$patientRecord->road.'     postal_code='.$patientRecord->postal_code.'     place='.$patientRecord->place.'     email='.$patientRecord->email.'     insurance_number='.$patientRecord->insurance_number.'     insurance_number=';
                        }
                    
                        $step1[$key]['name_matches']    = $all_matchs;
                        $step1[$key]['maching_info']    = $str;
                        $step1[$key]['99999 removed']    = $str_removed;
                        $step1[$key]['match']    = '';
                        if($no_match==1)
                        {
                            $step1[$key]['match']    = 'app server match found.';
                            if(!empty($value->old_id))
                            $result .=  '    old_id='.$value->old_id;
                            else
                            $result .=  '    old_id='.$patientRecord->old_id;

                            if(!empty($value->pat_nr))
                            $result .=  '       pat_nr='.$value->pat_nr;
                            else
                            $result .= '  pat_nr='.$patientRecord->pat_nr;

                            if(!empty($value->first_name))
                            $result .=  '    first_name='.$value->first_name;
                            else
                            $result .=  '    first_name='.$patientRecord->first_name;

                            if(!empty($value->family_name))
                            $result .=  '    family_name='.$value->family_name;
                            else
                            $result .=  '    family_name='.$patientRecord->family_name;


                            if(!empty($value->birth_date))
                            $result .=  '    birth_date='.$value->birth_date;
                            else
                            $result .=  '    birth_date='.$patientRecord->birth_date;

                            if(!empty($value->insurance_number))
                            $result .=  '    insurance number='.$value->insurance_number;
                            else
                            $result .=  '    insurance number='.$patientRecord->insurance_number;

                            if(!empty($value->mobile_no))
                            $result .=  '    mobile_no='.$value->mobile_no;
                            else 
                            $result .=  '    mobile_no='.$patientRecord->mobile_no;
                            

                            if(!empty($value->road))
                            $result .=  '    street='.$value->road;
                            else
                            $result .=  '    street='.$patientRecord->road;

                            if(!empty($value->postal_code))
                            $result .=  '    postal_code='.$value->postal_code;
                            else
                            $result .=  '    postal_code='.$patientRecord->postal_code;

                            if(!empty($value->email))
                            $result .=  '    email='.$value->email;
                            else
                            $result .=  '    email='.$patientRecord->email;

                            $step1[$key]['result']    =    $result;

                        }elseif($more_matches ==1)
                        {
                            $patientGanymedRecords = DB::connection('sqlsrv')
                                    ->table('patient')
                                    ->where('ID',$value->old_id)
                                    ->first();
                            if($patientGanymedRecords)
                            {
                                $step1[$key]['match']    = 'GOT A HIT';
                                if(!empty($patientGanymedRecords->ID))
                                $result .=  '    old_id='.$patientGanymedRecords->ID;
                                else
                                $result .=  '    old_id='.$value->old_id;

                                if(!empty($patientGanymedRecords->pat_nr))
                                $result .=  '       pat_nr='.$patientGanymedRecords->pat_nr;
                                else
                                $result .= '  pat_nr='.$value->pat_nr;

                                if(!empty($patientGanymedRecords->vorname))
                                $result .=  '    first_name='.$patientGanymedRecords->vorname;
                                else
                                $result .=  '    first_name='.$value->first_name;

                                if(!empty($patientGanymedRecords->famname))
                                $result .=  '    family_name='.$patientGanymedRecords->famname;
                                else
                                $result .=  '    family_name='.$value->family_name;


                                if(!empty($patientGanymedRecords->geb_dat)){
                                $birth_date = date("Y-m-d", strtotime(trim($patientGanymedRecords->geb_dat)));
                                $result .=  '    birth_date='.$birth_date;
                                }
                                else
                                $result .=  '    birth_date='.$value->birth_date;

                                if(!empty($patientGanymedRecords->vers_nr))
                                $result .=  '    insurance number='.$patientGanymedRecords->vers_nr;
                                else
                                $result .=  '    insurance number='.$value->insurance_number;

                                if(!empty($value->mobile_no))
                                $result .=  '    mobile_no='.$value->mobile_no;
                                else
                                {
                                if(!empty($patientGanymedRecords->tel_nr))
                                {
                                $gany_mobile_no     = trim($patientGanymedRecords->tel_nr);
                                $mobile_no    = '';
                                $internationalFormat = substr($gany_mobile_no, 0, 1);
                                $country_code = '';

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
                                $result .=  '    mobile_no='.$mobile_no;
                                }
                                }

                                if(!empty($value->road))
                                $result .=  '    street='.$value->road;
                                elseif(!empty($patientGanymedRecords->strasse))
                                $result .=  '    street='.trim($patientGanymedRecords->strasse);

                                if(!empty($value->postal_code))
                                $result .=  '    postal_code='.$value->postal_code;
                                elseif(!empty($patientGanymedRecords->plz))
                                $result .=  '    postal_code='.trim($patientGanymedRecords->plz);

                                if(!empty($value->email))
                                $result .=  '    email='.$value->email;
                                elseif(!empty($patientGanymedRecords->eMail))
                                $result .=  '    email='.trim($patientGanymedRecords->eMail);

                                $step1[$key]['result']    =    $result;
                            }else
                            {
                                $step1[$key]['match']    = 'app server match found but has unknown patientID. No ganymed entry.'; 
                                if(!empty($value->old_id))
                                $result .=  '    old_id='.$value->old_id;
                                else
                                $result .=  '    old_id='.$patientRecord->old_id;

                                if(!empty($value->pat_nr))
                                $result .=  '       pat_nr='.$value->pat_nr;
                                else
                                $result .= '  pat_nr='.$patientRecord->pat_nr;

                                if(!empty($value->first_name))
                                $result .=  '    first_name='.$value->first_name;
                                else
                                $result .=  '    first_name='.$patientRecord->first_name;

                                if(!empty($value->family_name))
                                $result .=  '    family_name='.$value->family_name;
                                else
                                $result .=  '    family_name='.$patientRecord->family_name;


                                if(!empty($value->birth_date))
                                $result .=  '    birth_date='.$value->birth_date;
                                else
                                $result .=  '    birth_date='.$patientRecord->birth_date;

                                if(!empty($value->insurance_number))
                                $result .=  '    insurance number='.$value->insurance_number;
                                else
                                $result .=  '    insurance number='.$patientRecord->insurance_number;

                                if(!empty($value->mobile_no))
                                $result .=  '    mobile_no='.$value->mobile_no;
                                else 
                                $result .=  '    mobile_no='.$patientRecord->mobile_no;
                                

                                if(!empty($value->road))
                                $result .=  '    street='.$value->road;
                                else
                                $result .=  '    street='.$patientRecord->road;

                                if(!empty($value->postal_code))
                                $result .=  '    postal_code='.$value->postal_code;
                                else
                                $result .=  '    postal_code='.$patientGanymedRecords->postal_code;

                                if(!empty($value->email))
                                $result .=  '    email='.$value->email;
                                else
                                $result .=  '    email='.$patientGanymedRecords->email;

                                $step1[$key]['result']    =    $result;                
                            }
                        }elseif($more_matches > 1)
                        {
                            $step1[$key]['match']    = 'Multiple match entries found.pat_nr ids ='.implode(",",$pat_nr_ids);
                        }
                        
                    }
                }               
                else
                {
                    $step1[$key]['name_matches']    = '';
                    $step1[$key]['maching_info']    = 'Check this info with ganymed server';
                    $step1[$key]['99999 removed']    = '';

                    $appServerOnGanymed = DB::connection('sqlsrv')
                                    ->table('patient')
                                    // ->whereRaw("MATCH(vorname) AGAINST('".$patientRecord->first_name."')")
                                    // ->whereRaw("MATCH(famname) AGAINST('".$patientRecord->family_name."')")
                                     ->where(DB::raw('upper(famname)'),'=',strtoupper($patientRecord->family_name))
                                    ->where(DB::raw('upper(vorname)'),'=',strtoupper($patientRecord->first_name))
                                    ->first();
                    if(!empty($appServerOnGanymed))
                    {
                        $step1[$key]['match']    = 'Patient found on ganymed server.';
                        $step1[$key]['result']    = json_encode($appServerOnGanymed);
                    }
                    else
                    {
                        $step1[$key]['match']    = 'No match found';
                        $step1[$key]['result']    = '';
                    }
                   
                }
            }
        }

        $sheets = [
            new step1Export($step1,'step1'),          
        ];

        return $sheets;
    }    

    public function headings(): array
    {
        return $this->headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = 'A1:W1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
            },
        ];
    }

}