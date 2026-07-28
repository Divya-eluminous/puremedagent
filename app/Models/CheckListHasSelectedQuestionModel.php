<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckListHasSelectedQuestionModel extends Model 
{
    use SoftDeletes; 

    protected $table = 'check_list_has_selected_questions';  
    protected $dates = ['deleted_at'];   

}
