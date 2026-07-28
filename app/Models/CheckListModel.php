<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CheckListHasHeadingSectionModel; 
use App\Models\ExaminationsHasMultipleCheckListModel; 

use App\Models\CheckListHasSelectedQuestionModel; //added on 30-sept-24 for #187


class CheckListModel extends Model 
{
    use SoftDeletes; 

    protected $table = 'examinations_check_list';  
    protected $dates = ['deleted_at'];   


    public function hasheadingSection(){
    	return $this->hasMany(CheckListHasHeadingSectionModel::class, 'fk_check_list_id','id');
    }

    public function getCheckList()
    {
    	$modelQuery = self::select('id','check_list_name','introduction_text','final_name','status')->where('status', 1);
		$result = $modelQuery->get();
		return $result;
    }

    public function hasExaminations()
    {
        return $this->hasMany(ExaminationsHasMultipleCheckListModel::class, 'fk_check_list_id','id');
    }

    public function getHEadingSectionQR()
    {
        return $this->hasMany(CheckListHasHeadingSectionModel::class, 'fk_check_list_id','id');
    }

     //added on 30-sept-24 for #187
    public function checklistHasSelectedQuestions()
    {
        return $this->hasMany(CheckListHasSelectedQuestionModel::class, 'fk_check_list_id', 'id');
    }
}
