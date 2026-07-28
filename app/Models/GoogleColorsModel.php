<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Request;

class GoogleColorsModel extends Model 
{

    protected $table = 'google_colors';

    protected $fillable = [
        'id', 
        'name', 
        'code', 
    ];

    public $timestamps = false;
    
}
