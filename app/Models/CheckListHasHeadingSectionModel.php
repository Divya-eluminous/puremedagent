<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\HeadingSectionHasQuestionModel;

class CheckListHasHeadingSectionModel extends Model 
{
    use SoftDeletes; 

    protected $table = 'check_list_has_heading_section';  
    protected $dates = ['deleted_at'];   


    public function HeadingSectionHasQuestion(){
        return $this->hasMany(HeadingSectionHasQuestionModel::class, 'fk_check_list_heading_section_id','id');
    }

    public function getQuestionQR(){
        return $this->hasMany(HeadingSectionHasQuestionModel::class, 'fk_check_list_heading_section_id','id');
    }

}
