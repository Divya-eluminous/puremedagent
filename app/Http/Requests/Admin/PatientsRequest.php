<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

use App\Traits\GeneralTrait;

class PatientsRequest extends FormRequest 
{
    use GeneralTrait; 
 
    public function authorize()  
    {
        return true;
    }

    public function rules() 
    {
        $id = base64_decode(base64_decode($this->route('patient'))) ?? null;   

        if ($id == null) 
        {
         
            return [
                'family_name'                => 'required',
                'first_name'                 => 'required',
                // 'mobile_no'     => 'required|regex:/^[1-9][0-9]*$/|numeric|unique:patients,mobile_no,NULL,NULL,deleted_at,NULL',
                'mobile_no'     => ['required','regex:/^(?!0{2,})0?[1-9][0-9]*$/','numeric'],

                // 'birth_date'            => 'required|before:-13 years', //commneted on 11nov22

                'birth_date'            => 'required',


                'gender'                 => 'required',//|email|unique:users,email
                    'email'  => 'required|email',
                'format' => ['required','regex:/^(\+[0-9]+|0[0-9]+|00[0-9]+)$/'],
            // 'country' => 'required|in:Austria,Germany,Switzerland', //Roshani Added For CR #102

                // 'family_doctor'         =>  'required',
                // 'family_doctor'         =>  'regex:/^[a-zA-Z ]+$/',
                  // 'api_access_token'   =>  'required',
                // 'last_login_at'      =>  'required',
                // 'is_blocked'         =>  'required', 
                // 'status'             =>  'required', 
                // 'family_name'           =>  'required',
                // 'gany_patient_id'    =>  'required',
                // 'additional_insurance'  =>  'required',
                 // 'family_name'             => 'required|regex:/^[a-zA-Z ]+$/',
                // 'family_name'             => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
                // 'first_name'            => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
                // 'first_name'            => 'required|regex:regex:/^[a-zA-Z ]+$/',
                // 'mobile_no'             => 'required',
                // 'size'                  =>  'required',
                // 'weight'                =>  'required',
                // 'title'                 =>  'required',

                
            ]; 
        }
        else
        {

            return [
                
                'family_name'                => 'required',
                'first_name'                 => 'required',
                'mobile_no'             => ['required','regex:/^(?!0{2,})0?[1-9][0-9]*$/','numeric'],       

                // 'birth_date'            => 'required|before:-13 years', //commented on 11nov22

                  'birth_date'            => 'required',
                'email'                 => 'required|email',
                'format' => ['required','regex:/^(\+[0-9]+|0[0-9]+|00[0-9]+)$/'],
            'country' => 'required|in:Austria,Germany,Switzerland', //Roshani Added For CR #102
                  

                // 'mobile_no'             => 'required|regex:/^[1-9][0-9]*$/|numeric|unique:patients,mobilde_no,'.$id.',id,deleted_at,NULL',
                /*'email'                 => 'required',
                'birth_date'            => 'required',
                'str_password'          =>  'required',
                'road'                  =>  'required',
                'place'                 =>  'required',
                'postal_code'           =>  'numeric|required',
                'gender'                =>  'required',
                'salutation'            =>  'required',
                'insurance_number'      =>  'required|digits:4',*/
                
                // 'mobile_no'             => 'required',
                // 'gany_patient_id'    =>  'required',
                // 'api_access_token'   =>  'required',
                // 'last_login_at'      =>  'required',
                // 'is_blocked'         =>  'required', 
                // 'status'             =>  'required', 
                // 'family_name'           =>  'required',
                // 'size'                  =>  'required',
                // 'weight'                =>  'required',
                // 'title'                 =>  'required',
                // 'family_doctor'         =>  'required',
                // 'family_doctor'         =>  'regex:/^[a-zA-Z ]+$/',
                // 'additional_insurance'  =>  'required',
                  // 'family_name'             => 'required|regex:/^[a-zA-Z ]+$/',
                // 'first_name'            => 'required|regex:/^[a-zA-Z ]+$/',
            ]; 

        }
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
            'family_name.required'    =>  __('admin.ERR_PATIENT_FAMILY_NAME_REQUIRED'), 
            'first_name.required'   =>  __('admin.ERR_FIRST_NAME_REQUIRED'),  
            'email.required'        =>  __('admin.ERR_PATIENT_EMAIL_ADDRESS'),
            'mobile_no.required'    =>  __('admin.ERR_MOBILE_NO_REQUIRED'),
            'mobile_no.regex'       =>  __('admin.ERR_MOBILE_NOUMBERNOTSTARTWITHZERO'),
            'mobile_no.numeric' => __('admin.ERR_FORMAT_MOBILE_USER'),
            'mobile_no.unique'        => __('admin.ERR_MOBILE_UNIQUE'),
            'birth_date.required'   =>  __('admin.ERR_BIRTH_DATE_REQUIRED'),
            'str_password.required' =>  __('admin.ERR_PASSWORD_REQUIRED'),
            'road.required'         =>  'Road field is required.',
            'place.required'        =>  'Place field is required.',
            'postal_code.numeric'  =>  __('admin.ERR_PATIENT_POSTAL_CODE_NUMERIC_REQUIRED'),
            'postal_code.required'  =>  __('admin.ERR_PATIENT_POSTAL_CODE_REQUIRED'),
            'gender.required'       =>  __('admin.ERR_PATIENT_GENDER_REQUIRED'), 
            'salutation.required'   =>  __('admin.ERR_PATIENT_SALUTATION_REQUIRED'),
            'insurance_number.required'     =>  __('admin.ERR_PATIENT_ENSURANCE_NUMBER_REQUIRED'),
            'insurance_number.digits'     =>  __('admin.ERR_PATIENT_ENSURANCE_NUMBER_DIGITS_REQUIRED'),
            'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
            'format.required'       => __('admin.ERR_COUNTRY_CODE_REQUIRED'),
            'format.regex'          => __('admin.ERR_COUNTRY_CODE_WRONG'),
            // 'country.required'      => __('api.ERR_COUNTRY_REQUIRED'),//Roshani Added For CR #102
            // 'country.in'    => __('api.ERR_COUNTRY_IN'),//Roshani Added For CR #102
            // 'family_name.regex'       =>  __('admin.ERR_FAMILY_NAME_REGEX_REQUIRED'),
            // 'first_name.regex'      =>  __('admin.ERR_FIRST_NAME_REGEX_REQUIRED'),
            // 'gany_patient_id.required'  =>  __('admin.ERR_GANY_PATIENT_ID_REQUIRED'),
            // 'api_access_token.required' =>  'Name field is required.',
            // 'last_login_at.required'    =>  'Name field is required.',
            // 'is_blocked.required' =>  'Is Blocked field is required.',
            // 'status.required',

            // 'email.email'         => __('admin.ERR_EMAIL_FORMAT'),
            // 'email.unique'        => __('admin.ERR_EMAIL_DUP'),
            // 'family_name.required'  =>  __('admin.ERR_PATIENT_FAMILY_NAME_REQUIRED'),
            // 'size.required'         =>  __('admin.ERR_PATIENT_SIZE_REQUIRED'),
            // 'weight.required'       =>  __('admin.ERR_PATIENT_WEIGHT_REQUIRED'),
            // 'title.required'        =>  __('admin.ERR_PATIENT_TITLE_REQUIRED'),
            // 'family_doctor.required'        =>  __('admin.ERR_PATIENT_FAMILY_DOCTOR_REQUIRED'),
            // 'family_doctor.regex'        =>  __('admin.ERR_PATIENT_FAMILY_DOCTOR_REGEX_REQUIRED'),
            // 'additional_insurance.required' =>  __('admin.ERR_PATIENT_ADDITIONAL_ENSURANCE_REQUIRED'),      
        ];
    }
}
