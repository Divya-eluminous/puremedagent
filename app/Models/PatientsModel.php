<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model; 

use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject; 
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\AppointmentModel; 
use App\Models\PatientsHasOldFindingModel; 
use App\Models\PatientsHasServiceReminderModel; 

class PatientsModel extends Authenticatable implements JWTSubject
{ 
	use SoftDeletes;

    protected $table = 'patients';
    protected $dates = ['deleted_at']; 
	
	

    public function getJWTIdentifier() 
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getAppointment()
    {
        return $this->hasMany(AppointmentModel::class, 'patient_id','id');
    }

    public function getOldAppoinmant()
    {
    	return $this->hasMany(PatientsHasOldFindingModel::class, 'fk_patient_id','id')->where('imported_flag','!=','2');
    }

    public function checklist()
    {
        return $this->hasMany(PatientsHasOldFindingModel::class, 'fk_patient_id','id');
    }

    public function patient_has_service_reminder()
    {
        return $this->hasMany(PatientsHasServiceReminderModel::class, 'patient_id','id');
    }
 
}
