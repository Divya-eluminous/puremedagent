<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

use App\Traits\GeneralTrait;

class ResetPasswordRequestWeb extends FormRequest
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
            'password.min'              => 'Das Passwort sollte mindestens 6 Zeichen lang sein.',
            'password.required'         => 'Das Feld „Passwort“ ist erforderlich.',
            'confirm_password.required' => 'Das Feld „Passwort bestätigen“ ist erforderlich.',
            'confirm_password.same'     => "Bestätigen Sie, dass das Passwort nicht mit dem Passwort übereinstimmt.",    
        ];
    }
}