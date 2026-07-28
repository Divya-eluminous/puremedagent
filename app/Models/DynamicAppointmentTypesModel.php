<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    use App\Models\PatientHasDocumentsModel;
    use App\Models\DynamicAppointmentTypeHasExaminationsModel;

    class DynamicAppointmentTypesModel extends Model
    {
        use SoftDeletes;

        protected $table = 'dynamic_appointment_types';
        protected $dates = ['deleted_at'];

        // protected $fillable = [
        // 	'name',
        // 	'duration',
     //    ];

        // public function hasPatientDocuments(){
        //     return $this->hasMany(PatientHasDocumentsModel::class, 'exam_app_type_id','id')->where('record_type',1);
        // }

        public function hasDynamicAppointmentExaminations(){
            //return $this->belongsToMany('App\Models\ExaminationsModel', 'examinations_has_profiles', 'examination_id', 'profile_id');
            return $this->hasMany(DynamicAppointmentTypeHasExaminationsModel::class, 'appointment_id','id')->orderBy('id', 'asc');
        }
        
    }
