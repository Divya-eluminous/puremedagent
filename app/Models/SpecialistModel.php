<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecialistModel  extends Model 
{
    use SoftDeletes; 

    protected $table = 'specialist';  
    protected $dates = ['deleted_at'];   

}
