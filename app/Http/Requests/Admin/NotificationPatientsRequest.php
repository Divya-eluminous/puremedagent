<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

use App\Traits\GeneralTrait;

class NotificationPatientsRequest extends FormRequest 
{
    use GeneralTrait; 
 
    public function authorize()  
    {
        return true;
    }

    public function rules() 
    {
        $id = base64_decode(base64_decode($this->route('notification-patient'))) ?? null;   

        if ($id == null) 
        {
         
            return [
                'user_name'                => 'required'
             
                
            ]; 
        }
        else
        {

            return [
                
                'user_name'                => 'required'                
             
            ]; 

        }
    }

    public function messages()
    {
        return [
            'user_name.required'    =>  __('admin.PATIENT_REQUIRED'), 
        ];
    }
}
