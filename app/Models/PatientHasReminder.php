<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientHasReminder  extends Model 
{
    use SoftDeletes;

    protected $table = 'patient_has_reminder';  
    protected $dates = ['deleted_at']; 
}