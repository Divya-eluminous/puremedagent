<?php

namespace App\Models;   

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenusSettingsModel extends Model 
{
	use SoftDeletes;

    protected $table = 'menus_settings';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'name',
		'url',
		'status',
		'user_id',
    ];
}
