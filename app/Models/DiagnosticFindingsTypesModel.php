<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiagnosticFindingsTypesModel extends Model 
{
    use SoftDeletes;  

    protected $table = 'diagnostic_findings_types';  
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'color', 
        'status', 
    ];
   
}
