<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PatientHasDocumentsModel;
use App\Models\AppointmentTypeHasExaminationsModel;

class AppointmentTypesModel extends Model
{
	use SoftDeletes;

    protected $table = 'appointment_types';
    protected $dates = ['deleted_at'];
	
	// protected $fillable = [
	// 	'name',
	// 	'duration',
 //    ];

    public function hasPatientDocuments(){
    	return $this->hasMany(PatientHasDocumentsModel::class, 'exam_app_type_id','id')->where('record_type',1);
    }

    public function hasAppointmentExaminations(){
        //return $this->belongsToMany('App\Models\ExaminationsModel', 'examinations_has_profiles', 'examination_id', 'profile_id');
        return $this->hasMany(AppointmentTypeHasExaminationsModel::class, 'appoinment_id','id')->orderBy('id', 'asc');
    }

    // added by vijay
    public function hasAppointmentNonExaminations()
    {
        return $this->hasMany(AppointmentTypeHasNonExaminationsModel::class, 'appointment_type_id', 'id')->orderBy('id', 'asc');
    }
}
