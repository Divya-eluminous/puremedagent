<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;


use App\Models\PatientsModel;
use DB;


class PatientCollectionExport implements FromCollection, WithHeadings,ShouldAutoSize
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
            foreach ($this->collection as  $key=>$patientRecord) 
            {
                $data[$key]['Id']           = $patientRecord->id;
                $data[$key]['Previous Appoitment Date']    ='';
                $data[$key]['Next Appoitment Date']    ='';
               // $data[$key]['Email Address']  = $patientRecord->email;
                $data[$key]['First Name']  =  $patientRecord->first_name;
                $data[$key]['Family Name']    = $patientRecord->family_name;
                $next_appoitments = DB::table('appointment')->select('start_date')
                            ->where('patient_id',$patientRecord->id)
                            ->whereDate('start_date','>=',date('Y-m-d'))
                            ->get()
                            ->toArray();

                $prev_appoitments = DB::table('appointment')->select('start_date')
                            ->where('patient_id',$patientRecord->id)
                            ->whereDate('start_date','<',date('Y-m-d'))
                            ->orderBy('start_date','desc')
                            ->first();

                $date = array();
                foreach ($next_appoitments as  $value) {
                    if(!empty($value->start_date))
                    {
                        $date[] = $value->start_date;
                    }
                }
               //$data[$key]['Appoitment_date']    = implode(",",$date);
                $data[$key]['Next Appoitment Date']    = implode(",",$date);
                $data[$key]['Previous Appoitment Date']    = $prev_appoitments->start_date ?? '';
               
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