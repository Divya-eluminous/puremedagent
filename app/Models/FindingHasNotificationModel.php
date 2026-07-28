<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FindingHasNotificationModel extends Model 
{
    use SoftDeletes; 

    protected $table = 'finding_has_notification';  
    protected $dates = ['deleted_at'];   

}
