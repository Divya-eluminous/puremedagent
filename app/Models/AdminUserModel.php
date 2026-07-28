<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Spatie\Permission\Traits\HasRoles;

class AdminUserModel extends Authenticatable 
{
    use Notifiable,HasRoles;

    protected $table 		= 'users'; 

    protected $guard_name 	= 'admin';

    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'str_password', 
        'country_code', 'mobile_number', 'status', 'doctor_id', 
        'login_otp', 'otp_created_at', 'is_updated', 'message'
    ];

    protected $hidden = [
        'password', 'remember_token',  
    ];

    /**
     * Get the database connection for the model.
     *
     * @return string
     */
    public function getConnectionName()
    {
        // Use tenant connection if available, otherwise use default
        return config('database.default') === 'tenant' ? 'tenant' : parent::getConnectionName();
    }

    public function assignedColor(){
    	return $this->belongsTo(GoogleColorsModel::class, 'google_color_id', 'id');
    }
     // ############# Roshani Added this code ################# 
    public function userHasAppointmentTypes(){
        return $this->hasMany(UserHasAppointmentType::class, 'user_id','id')->orderBy('id', 'asc');
    }
    // ############# Roshani Added this code #################
   
}
