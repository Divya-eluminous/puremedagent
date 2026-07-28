<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReminderRequest extends FormRequest
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
                'default_push_text'     => 'required',
                'default_sms_text'      => 'required',
                'default_mail_text'     => 'required',
                // 'period_frequency'      => 'required', 
                // 'period_frequency_type'      => 'required', 
                // 'new_frequency'      => 'required', 
                // 'new_frequency_type'      => 'required', 
                // 'first_frequency'      => 'required', 
                // 'first_frequency_type'      => 'required',
                // 'time_interval'      => 'required', 
                // 'number_of_interval'      => 'required',
            ];
        }
       
    }

    public function messages()
    {
        return [
                'default_push_text' => __('admin.ERR_DEFAULT_TEXT_REQUIRED'),   
                'default_sms_text' => __('admin.ERR_DEFAULT_TEXT_REQUIRED'),   
                'default_mail_text' => __('admin.ERR_DEFAULT_TEXT_REQUIRED'), 
                // 'period_frequency'      => __('admin.ERR_PERIOD_FREQUENCY_TYPE'),  
                // 'period_frequency_type'      => __('admin.ERR_DOCUMENT_FREQUENCY_TYPE'),  
                // 'new_frequency'      => __('admin.ERR_NEW_FREQUENCY'),  
                // 'new_frequency_type'      => __('admin.ERR_DOCUMENT_FREQUENCY_TYPE'),  
                // 'first_frequency'      => __('admin.ERR_FIRST_FREQUENCY'),  
                // 'first_frequency_type'      => __('admin.ERR_DOCUMENT_FREQUENCY_TYPE'), 
                // 'time_interval'      => __('admin.ERR_TIME_INTERVAL'),  
                // 'number_of_interval'      => __('admin.ERR_NUMBER_OF_INTERVAL'),       
        ];
    }
}
