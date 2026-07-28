<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingsModel extends Model
{
	use SoftDeletes;

    protected $table = 'settings';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'setting_key',
		'setting_value',
		'description',
		'status'
    ];
}
