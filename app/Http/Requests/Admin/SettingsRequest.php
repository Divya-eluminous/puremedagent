<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SettingsModel;

class SettingsRequest extends FormRequest
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
                'setting_key'     => 'required',
                'setting_value'     => 'required',
                'description'     => 'required',      
            ];
        }else{
            $setting = \App\Models\SettingsModel::find($id);
            return [
                // 'setting_key'     => 'required',
                // 'setting_value'     => 'required',
               'setting_value' => $setting && (
                    $setting->setting_key === 'APP_LOGGED_IN_IMAGE_LINK' || //Changed the setting name for #325 (m)
                    $setting->setting_key === 'ORDINATION_EMAIL_ADDRESS' || 
                    $setting->setting_key === 'EMERGENCY_BUTTON_EMAIL_ADDRESS'
                ) ? 'nullable' : 'required',
            // <!-- Roshani added setting key condition for required for point 325 (m) on 17-april-2025 -->
                'description'     => 'required',      
            ];
        }
       
    }

    public function messages()
    {
        return [

            'setting_key.required' => __('admin.ERR_SETTING_KEY_REQUIRED'),
            'setting_value.required' => __('admin.ERR_SETTING_VALUE_REQUIRED'),
            'description.required' => __('admin.ERR_SETTING_DESCRIPTION_REQUIRED'),
        ];
    }
}
