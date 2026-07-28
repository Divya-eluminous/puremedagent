<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest; 

class RosterRequest extends FormRequest 
{
    public function authorize() 
    {
        return true; 
    }

    public function rules()  
    {
        $id = base64_decode(base64_decode($this->route('roster'))) ?? null;
        return [
            // 'doctor_id'     => 'required',
            //'date'        => 'required',
            //'from_time'   => 'required',   
            //'to_time'     => 'required', 
            // 'status'     => 'required',  
        ]; 
    }

    public function messages() 
    {
        return [ 
            // 'doctor_id.required'  => __('admin.ERR_USER_ID_REQUIRED'),  
           // 'date.required'     => __('admin.ERR_DATE_REQUIRED'),             
           // 'from_time.required' => __('admin.ERR_TIME_FROM_REQUIRED'),  
           // 'to_time.required'   => __('admin.ERR_TIME_TO_REQUIRED'),
            // 'status.required'   => 'Status field is required.',    
        ];
    }
}
