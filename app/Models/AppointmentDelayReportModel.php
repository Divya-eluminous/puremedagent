<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentDelayReportModel extends Model
{
	use SoftDeletes;

    protected $table = 'appointment_delay_report';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'patient_id',
		'appointment_id',
		'delay_time',
		'custome_message',
    ];
}
