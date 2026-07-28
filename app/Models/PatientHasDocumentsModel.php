<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AppointmentTypesModel;
use App\Models\ExaminationsModel;

class PatientHasDocumentsModel extends Model
{
	// use SoftDeletes;

    protected $table = 'patient_has_documents';
    // protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'patient_id',
		'exam_app_type_id',
		'record_type',
		'doc_status'
    ]; 

    public function hasAppointmentTypeDocument(){
        return $this->hasMany(AppointmentTypesModel::class, 'id','exam_app_type_id');
    }
    public function hasExamDocument(){
        return $this->hasMany(ExaminationsModel::class, 'id','exam_app_type_id');
    }

    /*public function assignedRoster()
    {
        return $this->belongsTo(RosterModel::class, 'roster_id', 'id');
    }

    public function assignedWeekDay()
    {
        return $this->belongsTo(WeekDaysModel::class, 'week_day_id', 'id');
    }*/
}  
