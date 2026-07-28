<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class HeadingSectionHasQuestionModel extends Model 
{
    use SoftDeletes; 

    protected $table = 'heading_has_question';  
    protected $dates = ['deleted_at'];   

    // protected $fillable = [
    //     'name',
    //     'url',
    //     'status',  
    //     'trigger_exam_flag',
    // ];

 
}
