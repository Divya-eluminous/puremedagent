<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\OrdinationsModel;

class PatientHasOrdinationsModel  extends Model 
{
    use SoftDeletes; 

    protected $table = 'patients_has_ordination';  
    protected $dates = ['deleted_at']; 

    function getOrdination()
    {
    	return $this->belongsTo(OrdinationsModel::class, 'fk_ordination_id', 'id');
    }  

}
