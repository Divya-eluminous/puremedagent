<?php

namespace App\Models;  

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\PatientsModel;
use App\Models\AdminUserModel; 
use App\Models\AppointmentTypesModel; 
use App\Models\AppointmentHasExaminationsModel;
  
class DeletedAppointmentTrackModel extends Model
{
	use SoftDeletes; 

    protected $table = 'deleted_appointment_track';
    protected $dates = ['deleted_at'];
	
}
