<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
use App\Models\AppointmentModel;
use App\Models\PatientsModel;


// usaepay model
use App\dicom_convert;

use Carbon;
use DB;
use Str;
use Storage;

class CheckFileAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file_access';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check folder file access';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(dicom_convert $dicom_convert,AppointmentModel $AppointmentModel, PatientsModel $PatientsModel)
    {
        parent::__construct();
        $this->dicom_convert = $dicom_convert;
        $this->AppointmentModel = $AppointmentModel;
        $this->PatientsModel = $PatientsModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */ 

    //Asp-öck Asp.öck öck Asp,öck
    public function handle()
    {  

        $patientGanymedRecords = DB::connection('sqlsrv')
                                    ->table('patient')
                                    ->where('ID',"=",1)
                                    ->get();
        dd( $patientGanymedRecords);

        $all_data = $this->PatientsModel->orderby('id','ASC')->get();
        $txt = '';
      
        foreach($all_data as $key=>$value)
        {   
            dump($key);
            $update = 0;
            $converted_family_name = $this::string_operation($value->family_name);
            if($value->family_name != $converted_family_name)
            {
                $update = 1;
                $txt .= $value->family_name."=".$converted_family_name."\=";
            } 
           
            $converted_first_name = $this::string_operation($value->first_name);
            if($value->first_name != $converted_first_name)
            {
                $update = 1;
                $txt .= $value->first_name."=".$converted_first_name."\=";
            } 
           
            $converted_road = $this::string_operation($value->road);
            if($value->road != $converted_road)
            {
                $update = 1;
                $txt .= $value->road."=".$converted_road."\=";
            } 
            
            $converted_place = $this::string_operation($value->place);
            if($value->place != $converted_place)
            {
                $update = 1;
                $txt .= $value->place."=".$converted_place."\=";
            }
          
            if($update == 1 )
            {
                $this->PatientsModel
                    ->where('id',$value->id)
                    ->update([
                    'family_name' => $converted_family_name,
                    'first_name' => $converted_first_name,
                    'road' => $converted_road,
                    'place' => $converted_place,
                    'update_ganydb' => 1
                    ]);
                    $txt .= "=updated ".$value->id."\n";
            }
                     
        }  
        if(!empty($txt) && strlen($txt)>0)
        {
            Storage::append("basedata-".date('y-m-d').".txt", $txt);
        }     
        
    }

    public function string_operation($string)
    {
        $string = str_replace(array(".",","), array(".-",",-"),$string);           
        $string = ucwords(mb_convert_case($string, MB_CASE_TITLE, "UTF-8"));
        $string = str_replace(array(".-",",-"), array(".",","),$string);
        return $string ;
    }
}
