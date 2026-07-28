<?php

namespace App\Http\Requests\Admin; 

use Illuminate\Foundation\Http\FormRequest; 


class SupportSettingsRequest extends FormRequest 
{

    public function authorize() 
    {
        return true;
    }

    public function rules() 
    {
        $id = base64_decode(base64_decode($this->route('support_setting'))) ?? null;  

        if($id)
        {
            return [
                'name'     => 'required'               
            ];
        }else
        {
            return [
                'name'     => 'required',
                'file'      => 'required|max:10000|mimes:doc,docx,pdf' 
            ];
        }
            
    }

    public function messages()
    {
        return [
            'name.required'   =>  __('admin.ERR_SUPPORT_NAME_REQUIRED'),
            'file.required'    =>  __('admin.ERR_SUPPORT_URL_REQUIRED'),    
        ];
    }
}
