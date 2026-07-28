<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PatientHasDocumentsModel;
use App\Models\ExaminationsHasMultipleCheckListModel;

class ExaminationsModel extends Model 
{
    use SoftDeletes;

    protected $table = 'examinations';  
    protected $dates = ['deleted_at'];   

    protected $fillable = [
        'name',
        'url',
        'status',  
        'trigger_exam_flag',
    ];

    public function hasPatientDocuments(){
    	return $this->hasMany(PatientHasDocumentsModel::class, 'exam_app_type_id','id')->where('record_type',0);
    }

    public function hasMultipleChecklistQR()
    {
        return $this->hasMany(ExaminationsHasMultipleCheckListModel::class, 'fk_examinations_id','id');
    }

    public function hasMultipleDcoQR()
    {
        return $this->hasMany(ExaminationsHasMultipleDocumentListModel::class, 'fk_examinations_id','id');
    }
}
