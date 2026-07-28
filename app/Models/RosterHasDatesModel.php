<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class RosterHasDatesModel extends Model
{
	// use SoftDeletes;

    protected $table = 'roster_has_dates';
    // protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'roaster_id',
		'date',
		'from_time',
		'to_time'
    ]; 

    public function assignedRoster()
    {
        return $this->belongsTo(RosterModel::class, 'roster_id', 'id');
    }

    public function hasTimeFrames(){
    	return $this->hasMany(RosterHasWeeksHasTimeFramesModel::class, 'week_day_id','week_day_id');
    }

    public function assignedWeekDay()
    {
        return $this->belongsTo(WeekDaysModel::class, 'week_day_id', 'id');
    }
}  
