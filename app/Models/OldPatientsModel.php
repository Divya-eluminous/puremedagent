<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model; 

use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject; 
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\AppointmentModel; 
use App\Models\PatientsHasOldFindingModel; 

class OldPatientsModel extends Authenticatable implements JWTSubject
{ 
	use SoftDeletes;

    protected $table = 'old_patients';
    protected $dates = ['deleted_at']; 
	
	

    public function getJWTIdentifier() 
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getOldAppoinmant()
    {
    	return $this->hasMany(PatientsHasOldFindingModel::class, 'fk_patient_id','id');
    }

    public function checklist()
    {
        return $this->hasMany(PatientsHasOldFindingModel::class, 'fk_patient_id','id');
    }
 
}
