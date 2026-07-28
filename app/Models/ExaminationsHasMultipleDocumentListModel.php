<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CheckListModel;

class ExaminationsHasMultipleDocumentListModel extends Model 
{
    use SoftDeletes; 

    protected $table = 'examinations_has_multiple_document_list';  
    protected $dates = ['deleted_at'];   


    public function getmultipleDocumentList($id)
    {
    	$modelQuery = self::where('fk_examinations_id',$id)->orderBy('id','desc');
		$result = $modelQuery->get();
		return $result;
    }
}
