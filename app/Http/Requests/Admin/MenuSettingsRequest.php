<?php

namespace App\Http\Requests\Admin; 

use Illuminate\Foundation\Http\FormRequest; 


class MenuSettingsRequest extends FormRequest 
{

    public function authorize() 
    {
        return true;
    }

    public function rules() 
    {
        $id = base64_decode(base64_decode($this->route('menus_setting'))) ?? null;   
            return [
                'name'     => 'required',
                'url'      => 'required',
            ];
    }

    public function messages()
    {
        return [
            'name.required'   =>  __('admin.ERR_MENU_NAME_REQUIRED'),
            'url.required'    =>  __('admin.ERR_MENU_URL_REQUIRED'),    
        ];
    }
}
