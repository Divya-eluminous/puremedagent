<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
class AppointmentTypesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(Request $request)
    {
        $id = base64_decode(base64_decode($this->route('apointment_type'))) ?? null;   

        if($id == null){
            return [
                'name'          => 'required|unique:appointment_types,name,NULL,NULL,deleted_at,NULL,fk_specialist_id,"'.$request->specialist.'"',
                'duration'      => 'required', 
                'description'   => 'required',
                'specialist'   => 'required',
            ];
        } else{
                return [
                'name'          => 'required|unique:appointment_types,name,NULL,NULL,deleted_at,'.$id,
                'duration'      => 'required', 
                'description'   => 'required',
                'specialist'   => 'required',
            ];
        }
    }

    public function messages() 
    {
        return [

            'name.required'     => __('admin.ERR_APPOINTMENT_TYPE_NAME_REQUIRED'), 
            'name.unique'     => __('admin.ERR_APPOINTMENT_TYPE_NAME_UNIQUE'), 
            'duration.required' => __('admin.ERR_APPOINTMENT_TYPE_DURATION_REQUIRED'),
            'description.required' => __('admin.ERR_APPOINTMENT_TYPE_DESCRIPTION_REQUIRED'),
        ];
    }
}
