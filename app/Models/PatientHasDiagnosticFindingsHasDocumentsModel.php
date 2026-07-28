<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;  
// use Illuminate\Database\Eloquent\SoftDeletes; 

class PatientHasDiagnosticFindingsHasDocumentsModel extends Model
{
	// use SoftDeletes; 

    protected $table = 'patient_has_diagnostic_findings_has_documents';
    // protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'finding_id',
		'original_name', 
		'file'
    ];   

    // public function getFileAttribute()
    // {
    //     $strFile = $this->attributes['file'];  
    //     if(!empty($strFile) && is_file(storage_path('app'.$strFile)))
    //     {
    //         $response = storage_path('app'.$strFile);
    //     }else{
    //         $response = __('api.ERR_DIAGNOSTIC_FILE');
    //         // self::_createLog('downloadFinding',$errors,'error');
    //     }  
    //     return $response;
    // }

    // public function assignedFinding()
    // {
    //     return $this->belongsTo(PatientsHasDiagnosticFindingsModel::class, 'finding_id', 'id');
    // }
 
}  
