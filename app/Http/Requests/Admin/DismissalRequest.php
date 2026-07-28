<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DismissalRequest extends FormRequest
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
                'dismissal.*'     => 'required',
            ];
        }
       
    }

    public function messages()
    {
        return [
            'dismissal.*.required' => __('admin.ERR_DISMISSAL_REQUIRED'),         
        ];
    }
}
