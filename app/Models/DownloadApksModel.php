<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DownloadApksModel extends Model
{
	use SoftDeletes;

    protected $table = 'tablet_apks';
    protected $dates = ['deleted_at'];
	
	protected $fillable = [
		'app_name',
		'apk_file_name',
		'apk_file_path',
		'apk_version',
		'uploaded_by',
		'is_new',
		'is_downloaded',
		'uploaded_at'
    ];
}
