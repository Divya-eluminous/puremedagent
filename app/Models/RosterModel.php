<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RosterModel extends Model
{
	use SoftDeletes;

    protected $table = 'roster';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'user_id',
		'doctor_id',
		'appointment_type_id',
		'status'
    ]; 

    public function assignedDoctor(){
    	return $this->belongsTo(AdminUserModel::class, 'doctor_id', 'id');
    }

    public function assignedAppointmentType(){
    	return $this->belongsTo(AppointmentTypesModel::class, 'appointment_type_id', 'id');
    } 

    public function hasDates(){
    	return $this->hasMany(RosterHasDatesModel::class, 'roster_id','id');
    }

    /*public function hasTimeSlots(){
        return $this->hasMany(RosterHasDatesHasTimeFramesModel::class, 'roster_id','id');
    }*/
}  
