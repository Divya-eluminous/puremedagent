<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class PatientsHasOldFindingModel extends Model 
{
    use SoftDeletes;
    protected $table = 'patients_has_old_finding'; 
   
}
