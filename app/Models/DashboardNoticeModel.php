<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DashboardNoticeModel extends Model
{
	use SoftDeletes;

    protected $table = 'dashboard_notice';
}  
