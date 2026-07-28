<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;


class ForgotPasswordRequest extends FormRequest
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
                'email'          => 'required'
            ];
       
    }

    public function messages()
    {
        return [            
            'email.required'         => __('admin.ERR_EMAIL_REQUIRED'),
        ];
    }
}