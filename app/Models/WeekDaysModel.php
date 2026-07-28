<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeekDaysModel extends Model
{
    protected $table = 'week_days';
	
	protected $fillable = [
		'id',
		'day',
		'status'
    ]; 
}  
