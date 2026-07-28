<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SpecialistModel;

class OrdinationHasSpecialistModel  extends Model 
{
    use SoftDeletes; 

    protected $table = 'ordination_has_specialist';  
    protected $dates = ['deleted_at'];   

    public function getSpecilist()
    {
    	return $this->belongsTo(SpecialistModel::class, 'specialist_id','id');
    }

}
