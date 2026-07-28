<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentHasQueueNumberModel extends Model
{
	use SoftDeletes;

    protected $table = 'appointment_has_queue_number';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'patient_id',
		'appointment_id',
		'date',
		'queue_number',
		'status'
    ];  

    public function assignedPatient(){
    	return $this->belongsTo(PatientsModel::class, 'patient_id', 'id');
    }

    public function assignedAppointment(){
    	return $this->belongsTo(AppointmentModel::class, 'appointment_id', 'id');
    }

    public function assignedSymbol(){
    	return $this->belongsTo(WaitingNumberSymbolsModel::class, 'symbol_id', 'id');
    }
 
}  
