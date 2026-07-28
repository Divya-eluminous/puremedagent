<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;


class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

   public function rules()
    {       
            return [               
                'password'           => 'required|min:6',
                'confirm_password'   => 'required|same:password'
            ];
       
    }

    public function messages()
    {
        return [            
            'password.min'              => 'Password should be minimum 6 characters long.',
            'password.required'         => 'Password field is required.',
            'confirm_password.required' => 'Confirm password field is required.',
            'confirm_password.same'     => "Confirm password does not match password.",    
        ];
    }
}