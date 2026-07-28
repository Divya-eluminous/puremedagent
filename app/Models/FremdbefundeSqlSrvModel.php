<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FremdbefundeSqlSrvModel extends Model
{
    protected $table = 'fremdbefunde';	

    protected $connection = 'sqlsrv';

    public $timestamps = false;

	
}  
