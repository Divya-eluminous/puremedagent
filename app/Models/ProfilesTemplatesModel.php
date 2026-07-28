<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfilesTemplatesModel extends Model  
{
    use SoftDeletes; 

    protected $table 		= 'profiles_templates';  

    protected $fillable = [ 
        'name',
        'age_from',
        'age_to',
        'status'  
    ];
    protected $dates = ['deleted_at']; 

    public function hasProfileExaminations(){
        //return $this->belongsToMany('App\Models\ExaminationsModel', 'examinations_has_profiles', 'examination_id', 'profile_id');
    	return $this->hasMany(ProfileHasExaminationsModel::class, 'profile_id','id');
    }
}