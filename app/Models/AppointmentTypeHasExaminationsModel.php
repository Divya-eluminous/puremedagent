<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use App\Models\ExaminationsModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentTypeHasExaminationsModel extends Model
{
    use SoftDeletes; 
    protected $table = 'appoinment_type_has_examinations';
	
	

    public function assignedExamination()
    {
        return $this->belongsTo(ExaminationsModel::class, 'examination_id', 'id');
    }

    // public function assignedPatient()
    // {
    //     return $this->belongsTo(PatientsModel::class, 'patient_id', 'id');
    // }
}