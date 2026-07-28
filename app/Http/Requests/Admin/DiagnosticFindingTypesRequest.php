<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DiagnosticFindingTypesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('iagnostic-finding-types'))) ?? null;   
        // dd($id);
        // if ($id == null) 
        // {
            return [
                'name'     => 'required',
                'colour'     => 'required',     
            ];
        // }else{
        //     return [
        //         // 'setting_key'     => 'required',
        //         'setting_value'     => 'required',
        //         'description'     => 'required',      
        //     ];
        // }
       
    }

    public function messages()
    {
        return [

            'name.required' => __('admin.ERR_DIAGNOSTIC_FINDING_TYPES_NAME_REQUIRED'),
            'colour.required' => __('admin.ERR_DIAGNOSTIC_FINDING_TYPES_COLOR_REQUIRED'),
            
        ];
    }
}
