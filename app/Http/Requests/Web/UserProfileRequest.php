<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

use App\Traits\GeneralTrait;

class UserProfileRequest extends FormRequest 
{
    use GeneralTrait; 
 
    public function authorize()  
    {
        return true;
    }

    public function rules() 
    {
            return [
                'first_name'            => 'required',
                'family_name'           => 'required',
                //'mobile_no'     => 'required|regex:/^[1-9][0-9]*$/|numeric|unique:patients,mobile_no,NULL,NULL,deleted_at,NULL',
               
                // 'birth_date'            => 'required|before:-13 years', //commented on 11nov22
                'birth_date'            => 'required',
                'mobile_no'     => 'required|regex:/^(?!0{2})0?[0-9]+$/|numeric',
                // 'title'                 => 'required',
                'email'                 => 'required|email',
                // 'road'                  =>  'required',
                'postal_code'           =>  'numeric',
                // 'place'                 =>  'required',
                'country' => 'required',//Roshani Added For CR #102

            ]; 
       
    }
    //Roshani Added For CR #102
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $country = $this->input('country');
            $postalCode = $this->input('postal_code');

            if(!empty($country) && !empty($postalCode))
                {
                    if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
                    $validator->errors()->add('postal_code', __('front.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY'));
                }

                if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
                    $validator->errors()->add('postal_code', __('front.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('front.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2'));
                }
            }
            
        });
    }

    //Roshani Added For CR #102
    public function messages()
    {
        return [
            'first_name.required'   =>  __('admin.ERR_FIRST_NAME_REQUIRED'), 
            'family_name.required'    =>  __('admin.ERR_PATIENT_FAMILY_NAME_REQUIRED'),  
            'mobile_no.required'    =>  __('admin.ERR_MOBILE_NO_REQUIRED'),
            'mobile_no.regex'       =>  __('admin.ERR_MOBILE_NO_INVALID'),
            'mobile_no.numeric' => __('admin.ERR_FORMAT_MOBILE_USER'),
            'mobile_no.unique'        => __('admin.ERR_MOBILE_UNIQUE'),
            'birth_date.required'   =>  __('admin.ERR_BIRTH_DATE_REQUIRED'),
            'birth_date.before'   =>  __('front.ERR_BIRTH_DATE_BEFORE_REQUIRED'),
            // 'salutation.required'   =>  __('admin.ERR_PATIENT_SALUTATION_REQUIRED'),
            // 'title.required'   =>  __('admin.ERR_PATIENT_TITLE_REQUIRED'),
            'email.required'        =>  __('admin.ERR_PATIENT_EMAIL_ADDRESS'),
            // 'road.required'         =>   __('admin.ERR_ROAD_REQUIRED'),
            // 'postal_code.numeric'  =>  __('admin.ERR_PATIENT_POSTAL_CODE_NUMERIC_REQUIRED'),
            // 'postal_code.required'  =>  __('admin.ERR_PATIENT_POSTAL_CODE_REQUIRED'),
            // 'place.required'        =>   __('admin.ERR_PLACE_REQUIRED'),
            'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
        ];
    }
}
