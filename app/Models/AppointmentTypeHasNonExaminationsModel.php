<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;
use App\Models\ExaminationsModel;

class AppointmentTypeHasNonExaminationsModel extends Model
{
    
    protected $table = 'appoinment_type_has_non_examinations';

    public function assignedExamination()
    {
        return $this->belongsTo(ExaminationsModel::class, 'examination_id', 'id');
    }

    public static function getAppointmentNonServcies($appointment_type_id){
        $data = self::select('examination_id')->where('appointment_type_id',$appointment_type_id)->get();
        $data = $data->toArray();
        $ids = array_map(function ($item) {
            return $item['examination_id'];
        }, $data);
        return $ids;
    }
}