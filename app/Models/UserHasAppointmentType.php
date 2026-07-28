<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Request;

class UserHasAppointmentType extends Model
{
	use SoftDeletes;

    protected $table = 'users_has_appointment_types';
    // ############# Roshani Added this code ################# 
    public function appointmentTypeAssinedToUser()
    {
        return $this->belongsTo(AdminUserModel::class, 'appointment_type_id', 'id');
    }
    // ############# Roshani Added this code ################# 

}
