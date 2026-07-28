<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SmartphoneAppsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('setting'))) ?? null;   
        // dd($id);
        if ($id == null) 
        {
            return [
                'iphone'       => 'required',
                'andoid'       => 'required',
                'master_tablet'=> 'required',
                'waiting_no_tablet'=> 'required',
                'singDoc_tablet'=> 'required',
                'default_text' => 'required',
               
            ];
        }
       
    }

    public function messages()
    {
        return [
                'iphone' => __('admin.ERR_SETTING_IPHONE_REQUIRED'),   
                'andoid.required' => __('admin.ERR_SETTING_ANDOID_REQUIRED'),
                'master_tablet.required' => __('admin.ERR_SETTING_TABLET_REQUIRED'),    
                'waiting_no_tablet.required' => __('admin.ERR_SETTING_TABLET_REQUIRED'),    
                'singDoc_tablet.required' => __('admin.ERR_SETTING_TABLET_REQUIRED'),    
                'default_text.required'      => __('admin.ERR_DEFAULT_TEXT_REQUIRED'),  

              
        ];
    }
}
