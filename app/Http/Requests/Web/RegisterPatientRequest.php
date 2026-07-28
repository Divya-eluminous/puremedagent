<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

use App\Traits\GeneralTrait;

class RegisterPatientRequest extends FormRequest 
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
                // 'mobile_no'     => 'required|regex:/^[1-9][0-9]*$/|numeric|unique:patients,mobile_no,NULL,NULL,deleted_at,NULL',
               
                // 'birth_date'            => 'required|before:-13 years', //commented on 11nov22
                'birth_date'            => 'required',


                'mobile_no'     => 'required|regex:/^[1-9][0-9]*$/|numeric',
                // 'title'                 => 'required',
                'email'                 => 'required|email',
                // 'road'                  =>  'required',
                // 'postal_code'           =>  'numeric|required',
                // 'place'                 =>  'required',
                'gender'                 =>  'required',
                // 'otp_code'   => 'required'
                'password'          => 'required|min:6',
                'confirm_password'  => 'required|same:password',
            ]; 
       
    }

    public function messages()
    {
        return [
            'first_name.required'   =>  __('admin.ERR_FIRST_NAME_REQUIRED'), 
            'family_name.required'    =>  __('admin.ERR_PATIENT_FAMILY_NAME_REQUIRED'),  
            'mobile_no.required'    =>  __('admin.ERR_MOBILE_NO_REQUIRED'),
            'mobile_no.regex'       =>  __('admin.ERR_MOBILE_NOUMBERNOTSTARTWITHZERO'),
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
            'otp_code.required'   =>  __('front.ERR_OTP_REQUIRED'),
            'gender'                =>  __('front.ERR_PATIENT_GENDER_REQUIRED'),
            'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
            'password.required'     => __('admin.ERR_PASS'),
            'password.min'          => __('admin.ERR_PASS_MIN_SIZE'),

            'confirm_password.required' => __('admin.ERR_CONFIRM_PASS'),
            'confirm_password.same' => __('admin.ERR_COMPARE_PASS'),
        ];
    }
}
