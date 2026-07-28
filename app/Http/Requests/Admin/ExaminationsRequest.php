<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest; 

class ExaminationsRequest extends FormRequest 
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('examination'))) ?? null;
       
            return [
                // 'name'     => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
                'name'       => 'required',
                'url'        => 'required',
                //'check_list.*' => 'required',
                // 'status'   => 'required', 
            ];
    }

    public function messages() 
    {
        return [

            'name.required'       => __('admin.ERR_EXAM_NAME_REQUIRED'),            
            // 'name.regex'       => __('admin.ERR_EXAM_NAME_REGEX_REQUIRED'),            
            'url.required'        => __('admin.ERR_URL_REQUIRED'),
            //'check_list.required' => __('admin.ERR_CHECK_LIST_REQUIRED'),
            // 'status.required'=> 'Status field is required.',    
        ];
    }
}
