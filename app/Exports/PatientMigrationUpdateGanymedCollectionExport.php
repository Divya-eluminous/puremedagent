<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;


use App\Models\PatientsModel;
use DB;


class PatientMigrationUpdateGanymedCollectionExport implements FromCollection, WithHeadings,ShouldAutoSize
{
    use Exportable;
    public function __construct($collection,$headings){
        
        $this->headings = $headings;
        $this->collection = $collection;       
    }

    public function collection()
    {

   
        $data = [];
     
        if(!empty($this->collection) && sizeof($this->collection)>0)
        {

            $collection = $this->collection;
            foreach ($collection as $key=>$patientRecord) 
            {
//dd($patientRecord);
                $data[$key]['status']    = $patientRecord['operation_status'];
                $data[$key]['old_id']    = $patientRecord['old_id'];
                $data[$key]['pat_nr']    = $patientRecord['pat_nr'];
                $data[$key]['family_name']  =  $patientRecord['famname'];
                $data[$key]['first_name']    = $patientRecord['vorname'];
                $data[$key]['email']    = $patientRecord['eMail'];
                $data[$key]['road']    = $patientRecord['strasse'];
                $data[$key]['postal_code']    = $patientRecord['plz'];
                $data[$key]['place']    = $patientRecord['ort'];
                $data[$key]['insurance_number']    = $patientRecord['vers_nr'];

                $data[$key]['birth_date']    = $patientRecord['geb_dat'];
                $data[$key]['age']    = $patientRecord['age'];
                $data[$key]['ganymed_mobile_no']    = $patientRecord['tel_nr'];
                $data[$key]['country_code']    = $patientRecord['country_code'];
                $data[$key]['mobile_no']    = $patientRecord['mobile_no'];
                $data[$key]['size']    = $patientRecord['groesse'];
                $data[$key]['weight']    = $patientRecord['gewicht'];

                $data[$key]['title']    = $patientRecord['titel'];
                $data[$key]['salutation']    = $patientRecord['salutation'];
                $data[$key]['family_doctor']    = $patientRecord['Hausarzt'];
                $data[$key]['additional_insurance']    = $patientRecord['zu_vers'];
                $data[$key]['gender']    = $patientRecord['gender'];
               
            }
        }

        //dd(collect($data));
        return collect($data);
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