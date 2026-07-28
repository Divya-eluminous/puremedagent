<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

use App\Traits\GeneralTrait;

class CustomerRequest extends FormRequest
{
    use GeneralTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('customer'))) ?? null;   

        // dd($this->get('email'));
        // $email      = $this->get('email');
        // $countEmail = self::_checkCompanyUniqueUser($id,$email);

        //dd($countEmail);

        /*if ($id === null) 
        {*/

            return [
                'contact_name'     => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
                // 'company_id'     => 'required',
                'email'            => 'required',//|email|unique:users,email
            ];
       /* }
        else
        {
            // dd('ttt',$id);

             return [
                'contact_name'     => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
                // 'company_id'     => 'required',
                'email'            => 'required',//|email|unique:users,email,.$id
            ];
        }*/

       /* $tmp = [];
        
        if ($id === null) 
        {

            $tmp['contact_name']   = 'required|regex:/^[a-zA-Z0-9\s]+$/u';
            $tmp['email']          = 'required|email|unique:users,email';

            // return [
            //     'contact_name'     => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
            //     'email'            => 'required|email|unique:users,email',
            // ];
        }
        else
        {
            $tmp['contact_name']   = 'required|regex:/^[a-zA-Z0-9\s]+$/u';
            $tmp['email']          = 'required|email|unique:users,email,'.$id,
            //  return [
            //     'contact_name'     => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
            //     'email'            => 'required|email|unique:users,email,'.$id,
            // ];
        }

         return $tmp;*/
    }

    public function messages()
    {
        return [
            'contact_name.required' => 'Name field is required.',            
            'contact_name.regex'    => 'Name field should be in letter\'s and number\'s only.',            
            // 'company_id.required'   => 'Company Name field is required.',     

            'email.required'        => __('admin.ERR_EMAIL_NAME'),
            'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
            'email.unique'          => __('admin.ERR_EMAIL_DUP'),      
        ];
    }
}
