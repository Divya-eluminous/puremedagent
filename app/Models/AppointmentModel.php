<?php

namespace App\Models;  

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\PatientsModel;
use App\Models\AdminUserModel; 
use App\Models\AppointmentTypesModel; 
use App\Models\AppointmentHasExaminationsModel;
  
class AppointmentModel extends Model
{
	//use SoftDeletes; 

    protected $table = 'appointment';
    //protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'start_date',
		'end_date',
		'patient_id',
		'doctor_id',
		'appointment_type_id', 
		'notes'
    ];

    public function assignedPatient(){
    	return $this->belongsTo(PatientsModel::class, 'patient_id', 'id');
    }

    public function assignedDoctor(){
    	return $this->belongsTo(AdminUserModel::class, 'doctor_id', 'id');
    }

    public function assignedAppointmentType(){
    	return $this->belongsTo(AppointmentTypesModel::class, 'appointment_type_id', 'id')->withTrashed();
    }

    //Added on 7-march-24 (8-march-24) for showing appointment type name in tablet app
    public function assignedAppointmentTypeQRCodeApp(){
        return $this->belongsTo(AppointmentTypesModel::class, 'appointment_type_id', 'id')->withTrashed();
    }


    public function hasExaminations(){
        return $this->hasMany(AppointmentHasExaminationsModel::class, 'appointment_id','id');
    }

    public function appointmentType()
    {
        return $this->belongsTo(AppointmentTypesModel::class, 'appointment_type_id');
    }

}
