<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;  

class ProfileTemplatesRequest extends FormRequest 
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('profile_template'))) ?? null;
            return [
                'name'          => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
                'age_from'      => 'required', 
                'age_to'        => 'required', 
                'examinations'  => 'required', 
                // 'status'     => 'required',  
            ]; 
    }

    public function messages() 
    {
        return [

            'name.required' => __('admin.ERR_PROFILE_NAME_REQUIRED'),            
            'name.regex'    => __('admin.ERR_PROFILE_NAME_REGEX_REQUIRED'),            
            'age_from.required'     => __('admin.ERR_AGE_FROM_REQUIRED'),
            'age_to.required'       => __('admin.ERR_AGE_TO_REQUIRED'), 
            'examinations.required' => __('admin.ERR_EXAM_REQUIRED'),
            // 'status.required'    => 'Status field is required.',    
        ];
    }
}
