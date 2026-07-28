<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExportPathRequest extends FormRequest
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
                'doctor_id.*'     => 'required',
                'export_path.*'     => 'required',        
            ];
        }
       
    }

    public function messages()
    {
        return [
            'doctor_id.*.required' => __('admin.ERR_DOCTOR_ID_REQUIRED'),
            'export_path.*.required' => __('admin.ERR_SETTING_EXPORT_PATH_REQUIRED'), 
        ];
    }
}
