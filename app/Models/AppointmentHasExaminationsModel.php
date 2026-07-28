<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;


class AppointmentHasExaminationsModel extends Model
{
    
    protected $table = 'appointment_has_examinations';
	
	// protected $fillable = [
	// 	'appointment_id',
	// 	'patient_id',
	// 	'examination_id',
 //    ];

    public function assignedExamination()
    {
        return $this->belongsTo(ExaminationsModel::class, 'examination_id', 'id')->withTrashed();
    }

    public function assignedPatient()
    {
        return $this->belongsTo(PatientsModel::class, 'patient_id', 'id');
    }
}