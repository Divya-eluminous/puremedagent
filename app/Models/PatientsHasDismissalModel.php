<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PatientsModel;
use App\Models\DismissalModel;

class PatientsHasDismissalModel extends Model
{
	use SoftDeletes; 

    protected $table = 'patients_has_dismissal';
    protected $dates = ['deleted_at'];  
	
	// protected $fillable = [
	// 	'id',
	// 	'name',
		
	// 	'status',
	// 	'created_at',
	// 	'updated_at',
	// 	'deleted_at',
 //    ];

    public function getPatients(){
    	return $this->hasMany(PatientsModel::class, 'id','fk_patient_id');
    }

    public function getDismissal(){
    	return $this->hasMany(DismissalModel::class, 'id','fk_dismissal_id');
    }
}
