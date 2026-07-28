<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientsHasServiceControlReminderModel extends Model 
{
    use SoftDeletes;
    protected $table = 'patient_has_service_control_reminder_setting'; 
   
}
