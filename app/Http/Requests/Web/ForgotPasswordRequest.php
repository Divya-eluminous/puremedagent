<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

use App\Traits\GeneralTrait;

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
            //'email.required'         => __('admin.ERR_EMAIL_REQUIRED'), //commented on 30-jan-26
            'email.required'         => __('front.FORGOT_PASSWORD_WEB_EMAIL_ERR'), //added on 30-jan-26 for #417

        ];
    }
}