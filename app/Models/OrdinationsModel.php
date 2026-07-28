<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\OrdinationHasSpecialistModel;
use App\Models\SpecialistModel;

class OrdinationsModel  extends Model 
{
    use SoftDeletes; 

    protected $table = 'ordination';  
    protected $dates = ['deleted_at'];   

    public function hasSpecilist()
    {
    	return $this->hasMany(OrdinationHasSpecialistModel::class, 'ordination_id','id');
    }
}
