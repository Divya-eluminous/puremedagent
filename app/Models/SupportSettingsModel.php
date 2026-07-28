<?php

namespace App\Models;   

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportSettingsModel extends Model 
{
	use SoftDeletes;

    protected $table = 'support_settings';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'name',
		'url',
		'status',
    ];
}
