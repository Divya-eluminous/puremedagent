<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class RosterHasDatesHasTimeFramesModel extends Model
{
    protected $table = 'roster_has_dates_has_time_frames';
	
	protected $fillable = [
		'roaster_id',
		'date_id',
		'time_frame',
    ]; 

    public function assignedTimeFrame()
    {
        return $this->belongsTo(RosterHasDatesModel::class, 'date_id', 'id');
    }
}  
