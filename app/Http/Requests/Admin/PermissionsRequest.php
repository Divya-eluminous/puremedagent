<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PermissionsRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
                'role'=> 'required'
            ];
    }

    public function messages()
    {
        return [
            'role.required'   =>  __('admin.ERR_ROLE')
        ];
    }
}
