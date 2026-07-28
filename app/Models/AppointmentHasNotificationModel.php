<?php

namespace App\Models;  

use Illuminate\Database\Eloquent\Model; 
use App\Models\AppointmentModel;
  
class AppointmentHasNotificationModel extends Model
{
   
    protected $table = 'appointment_has_notification'; 
	
	protected $fillable = [
		'patient_id',
		'appointment_id',
		'notify_time',
		'status'
    ]; 

    public function assignedAppointment(){
        return $this->belongsTo(AppointmentModel::class, 'appointment_id', 'id');
    }

   /* public function assignedPatient(){
    	return $this->belongsTo(PatientsModel::class, 'patient_id', 'id');
    }

    public function assignedDoctor(){
    	return $this->belongsTo(AdminUserModel::class, 'doctor_id', 'id');
    }*/



}
