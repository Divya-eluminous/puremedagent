<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PatientHasDeviceModel extends Model
{
	use SoftDeletes;

    protected $table = 'patient_has_device';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'patient_id',
		'device_type',
		'device_id',
    ];
}