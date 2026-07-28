<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientsHasServiceReminderModel extends Model 
{
    use SoftDeletes;
    protected $table = 'patient_has_service_reminder'; 


    public function assignedAppointment(){
        return $this->belongsTo(AppointmentModel::class, 'appointment_id', 'id');
    }
   
}
