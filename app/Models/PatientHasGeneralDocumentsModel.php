<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientHasGeneralDocumentsModel  extends Model 
{
    use SoftDeletes; 

    protected $table = 'patients_has_general_document';  
    protected $dates = ['deleted_at'];   

   
}
