<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenstruationCycleHasCalendarModel extends Model
{
	use SoftDeletes;

    protected $table = 'menstruation_cycle_has_calendar';
    protected $dates = ['deleted_at'];
 	
	protected $fillable = [
		'patient_id',
		'ovulation',
		'blood_test_possible',
		'urine_test_possible',
		'menstruation',
		'fertile',
		'very_fertile',
    ];     
}
