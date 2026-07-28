<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;


use App\Models\PatientsModel;
use DB;


class PatientMigrationCollectionExport implements FromCollection, WithHeadings,ShouldAutoSize
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

                $data[$key]['status']    = $patientRecord['operation_status'];
                $data[$key]['old_id']    = $patientRecord['old_id'];
                $data[$key]['pat_nr']    = $patientRecord['pat_nr'];
                $data[$key]['family_name']  =  $patientRecord['family_name'];
                $data[$key]['first_name']    = $patientRecord['first_name'];
                $data[$key]['email']    = $patientRecord['email'];
                $data[$key]['road']    = $patientRecord['road'];
                $data[$key]['postal_code']    = $patientRecord['postal_code'];
                $data[$key]['place']    = $patientRecord['place'];
                $data[$key]['insurance_number']    = $patientRecord['insurance_number'];

                $data[$key]['birth_date']    = $patientRecord['birth_date'];
                $data[$key]['age']    = $patientRecord['age'];
                $data[$key]['ganymed_mobile_no']    = $patientRecord['ganymed_mobile_no'];
                $data[$key]['country_code']    = $patientRecord['country_code'];
                $data[$key]['mobile_no']    = $patientRecord['mobile_no'];
                $data[$key]['size']    = $patientRecord['size'];
                $data[$key]['weight']    = $patientRecord['weight'];

                $data[$key]['title']    = $patientRecord['title'];
                $data[$key]['salutation']    = $patientRecord['salutation'];
                $data[$key]['family_doctor']    = $patientRecord['family_doctor'];
                $data[$key]['additional_insurance']    = $patientRecord['additional_insurance'];
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