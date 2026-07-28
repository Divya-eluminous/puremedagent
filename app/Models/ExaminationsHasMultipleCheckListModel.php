<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CheckListModel;

class ExaminationsHasMultipleCheckListModel extends Model 
{
    use SoftDeletes; 

    protected $table = 'examinations_has_multiple_check_list';  
    protected $dates = ['deleted_at'];   


    public function getmultipleCheckList($id)
    {
    	$modelQuery = self::where('fk_examinations_id',$id)->orderBy('id','desc');
		$result = $modelQuery->get();
		return $result;
    }

    public function getChecklistSingQR()
    {
    	return $this->hasMany(CheckListModel::class, 'id','fk_check_list_id')->whereNull('deleted_at')->where('signDoc','sign');
    }
    public function getChecklistQR()
    {
        return $this->hasMany(CheckListModel::class, 'id','fk_check_list_id')->whereNull('deleted_at');
    }

    public function getGeneralChecklist()
    {
        return $this->hasMany(CheckListModel::class, 'id','fk_check_list_id')->where('type_of_checklist','general')->whereNull('deleted_at');;
    }

}
