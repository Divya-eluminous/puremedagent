<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileHasExaminationsModel extends Model
{
    protected $table 		= 'profile_has_examinations'; 

    protected $fillable = [
        'profile_id',
        'examination_id', 
    ];

    public function assignedProfile()
    {
        return $this->belongsTo(ProfilesTemplatesModel::class, 'profile_id', 'id');
    }

    public function assignedExamination()
    {
        return $this->belongsTo(ExaminationsModel::class, 'examination_id', 'id');
    }

}
