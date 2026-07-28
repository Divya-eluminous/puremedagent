<?php

namespace App\Models;  

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
  
class EmergyncyModel extends Model
{
	use SoftDeletes; 

    protected $table = 'emergency';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'patient_id',
		'current_complaint',
		'previous_treatment',
    ];

}
