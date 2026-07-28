<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
//use App\Traits\GeneralTrait;

class UsersRequest extends FormRequest
{
    //use GeneralTrait;
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        //$companyId = self::_getCompanyId();
        $id = base64_decode(base64_decode($this->route('user'))) ?? null;
        if ($id == null) 
        {
            return [
                //'first_name'        => 'required|regex:/^[a-zA-Z0-9_.\-\s]+$/u',//initial : regex:/^[a-zA-Z0-9\s]+$/u
                'first_name'        => 'required|regex:/^[a-zA-Z0-9_.\-\äöüßÄÖÜ\s]+$/u',
                //'last_name'         => 'required|regex:/^[a-zA-Z0-9_.\-\s]+$/u',//initial : regex:/^[a-zA-Z0-9\s]+$/u
                'last_name'         => 'required|regex:/^[a-zA-Z0-9_.\-\äöüßÄÖÜ\s]+$/u',
                'email'             => 'required|email|unique:users,email',      
                'password'          => 'required|min:6',
                'confirm_password'  => 'required|same:password',
                'role'              => 'required',
                'profile_img'       => 'nullable|mimes:jpeg,jpg,png,gif',
                'mobile_number'     => 'required|numeric',
            ];
        }
        else
        {
            return [
                'first_name'        => 'required|regex:/^[a-zA-Z0-9_.\-\s]+$/u',//initial : regex:/^[a-zA-Z0-9\s]+$/u
                'last_name'         => 'required|regex:/^[a-zA-Z0-9_.\-\s]+$/u',//initial : regex:/^[a-zA-Z0-9\s]+$/u
                'email'             => 'required|email|unique:users,email,'.$id,
                'password'          => 'nullable|min:6',
                'confirm_password'  => 'same:password',
                'profile_img'       => 'nullable|mimes:jpeg,jpg,png,gif',
                'mobile_number'     => 'required|numeric',
            ];
        }
    }

    public function messages()
    {
        return [

            'first_name.required'   => __('admin.ERR_FIRSTNAME_REQUIRED'),
            'first_name.regex'      => __('admin.ERR_FIRSTNAME_REGEX_REQUIRED'),
            'last_name.required'    => __('admin.ERR_LASTNAME_REQUIRED'),
            'last_name.regex'       => __('admin.ERR_LASTNAME_REGEX_REQUIRED'),

            'email.required'        => __('admin.ERR_EMAIL_NAME'),
            'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
            'email.unique'          => __('admin.ERR_EMAIL_DUP'),   
            'password.required'     => __('admin.ERR_PASS'),
            'password.min'          => __('admin.ERR_PASS_MIN_SIZE'),

            'confirm_password.required' => __('admin.ERR_CONFIRM_PASS'),
            'confirm_password.same' => __('admin.ERR_COMPARE_PASS'),
           
            'profile_img.mimes'     =>  __('admin.ERR_PROFILE_IMAGE_FORMAT'),
            //'profile_img.max'       => 'The profile image size mas size should be 10mb.',

            'role.required'         => __('admin.ERR_ROLE'),
            'mobile_number.numeric' => __('admin.ERR_MOBILE_NUMBER'),

        ];
    }
}
