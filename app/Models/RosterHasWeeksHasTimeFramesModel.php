<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class RosterHasWeeksHasTimeFramesModel extends Model
{
    protected $table = 'roster_has_weeks_has_time_frames';
	
	protected $fillable = [
		'roaster_id',
		'week_day_id',
		'time_frame',
    ]; 

    public function assignedTimeFrame()
    {
        return $this->belongsTo(RosterHasDatesModel::class, 'week_day_id', 'week_day_id');
    }
}  
