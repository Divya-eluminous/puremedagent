<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdatePasswordRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
                'old_password'      => 'required',
                'password'          => 'required|min:6',
                'confirm_password'  => 'required|same:password',
            ];
    }

    public function messages()
    {
        return [

            'old_password.required'     => __('admin.ERR_CHANGE_PASSWORD_OLD'),
            
            'password.required'         => __('admin.ERR_CHANGE_PASSWORD_NEW'),
            'password.min'          => __('admin.ERR_CHANGE_PASSWORD_NEW_LENGTH'),

            'confirm_password.required' => __('admin.ERR_CONFIRM_PASS'),
            'confirm_password.same'     => __('admin.ERR_COMPARE_PASS'),
        ];
    }
}
