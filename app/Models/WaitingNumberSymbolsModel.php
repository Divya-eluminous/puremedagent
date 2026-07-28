<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Request;

class WaitingNumberSymbolsModel extends Model 
{

    protected $table = 'waiting_number_symbols';

    protected $fillable = [
        'id', 
        'name', 
        'url', 
    ];

    // public $timestamps = false;
    
}
