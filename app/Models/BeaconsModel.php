<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeaconsModel extends Model
{

    protected $table = 'beacons';
	
	protected $fillable = [
		'beacon_id',
		'beacon_identifier',
		
		'status',
		'device',
		'beacon_UUID',
		'beacon_major',
		'beacon_minor',
		
    ];
}
