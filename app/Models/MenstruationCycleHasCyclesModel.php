<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenstruationCycleHasCyclesModel extends Model
{

    protected $table = 'menstruation_cycle_has_cycles';
 	
 	public $timestamps = false;	
 	
	protected $fillable = [
		'menstruation_cycle_id',
		'date',
		'length',
		'cycle',
    ];     
}
