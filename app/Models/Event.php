<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\AppointmentModel;
use App\Models\PatientsModel;
use App\Models\AdminUserModel;

class Event extends Model
{
    // use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'summary',
        'description',
        'patient_id',
        'patient_email',
        'patient_name',
        'doctor_email',
        'color_id',
        'start_date_time',
        'end_date_time'
    ];

    // protected $guarded = [];
    public function appointments()
    {
        return $this->hasMany(AppointmentModel::class,'id','appointment_id');
    }

    public function patient()
    {
        return $this->hasMany(PatientsModel::class, 'id', 'patient_id');
    }

    public function doctor()
    {
        return $this->hasManyThrough(
            AdminUserModel::class,   
            AppointmentModel::class,  
            'id',                   
            'id',                 
            'appointment_id',       
            'doctor_id'            
        );
    }



}