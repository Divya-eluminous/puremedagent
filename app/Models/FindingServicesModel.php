<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FindingServicesModel extends Model 
{
    use SoftDeletes; 

    protected $table = 'finding_services';  
    protected $dates = ['deleted_at'];   


    public function getFindingServices()
    {
    	$modelQuery = self::select('id','name','web_link','type','status');
		$result = $modelQuery->get();
		return $result;
    }
}
