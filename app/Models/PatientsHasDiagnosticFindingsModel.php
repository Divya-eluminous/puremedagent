<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class PatientsHasDiagnosticFindingsModel extends Model 
{
    use SoftDeletes;   

    protected $table = 'patients_has_diagnostic_findings';
  
    protected $dates = ['deleted_at'];  

    protected $fillable = [
        'patient_id',
        'finding_type_id',  
        'document_name',
        'date',
        'comment',
        'status', 
    ];

    public function hasFindingDocument(){
        return $this->hasMany(PatientHasDiagnosticFindingsHasDocumentsModel::class, 'finding_id','id');
    } 
   
} 
