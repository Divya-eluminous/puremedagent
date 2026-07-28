<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryCodesModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'country_codes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_name_de',
        'country_name_en',
        'iso_code',
        'phone_code',
        'is_active',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
}
