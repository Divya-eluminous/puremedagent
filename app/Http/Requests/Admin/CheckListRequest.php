<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest; 

class CheckListRequest extends FormRequest 
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('check-list'))) ?? null;
       
            return [
                // 'name'     => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
                'check_list_name'     => 'required',
                'type_of_checklist'     => 'required',
                'introduction_text'   => 'required',
                'final_text'          => 'required', 
            ];
    }

    public function messages() 
    {
        return [

            'check_list_name.required'  => __('admin.ERR_CHECK_LIST_NAME_REQUIRED'),            
            'type_of_checklist.required'  => __('admin.ERR_CHECK_LIST_TYPE_REQUIRED'),            
            'introduction_text.required'   => __('admin.ERR_CHECKLIST_INTRODUCER_TEXT_REQUIRED'),            
            'final_text.required'       => __('admin.ERR_CHECKLIST_FINAL_TEXT_REQUIRED'),
        ];
    }
}
