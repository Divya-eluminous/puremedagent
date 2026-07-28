<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest; 

class FindingServicesRequest extends FormRequest 
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('finding-services'))) ?? null;
       
            return [
                // 'name'     => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
                'name'     => 'required',
                'web_url'   => 'required',
            ];
    }

    public function messages() 
    {
        return [

            'name.required'    => __('admin.ERR_FINDING_SERVICES_NAME_REQUIRED'),            
            'web_url.required' => __('admin.ERR_FINDING_SERVICES_WEB_URL_REQUIRED'),            
        ];
    }
}
