<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecialistDocumentsModel  extends Model 
{
    use SoftDeletes; 

    protected $table = 'specialist_has_documents';  
    protected $dates = ['deleted_at']; 

    public function getDocumentList()
    {
    	$modelQuery = self::where('status', '1');
		$result = $modelQuery->get();
		return $result;
    }  

}
