<?php
  
namespace App\Imports;
  
use App\Models\PatientsModel;
use Maatwebsite\Excel\Concerns\ToModel;
  
class PatientImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function __construct($PatientsModel){        
        $this->PatientsModel = $PatientsModel;
    }

    public function model(array $row)
    {
        dd($row);
    }
}