<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenstruationCycleModel extends Model
{
	use SoftDeletes;

    protected $table = 'menstruation_cycle';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'beginning_date',
		'length',
    ];     
}
