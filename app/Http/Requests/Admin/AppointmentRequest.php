<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class AppointmentRequest extends FormRequest 
{
    public function authorize()
    {
        return true;
    }

    public function rules(Request $request) 
    {
        //dd($request->all());

        // $id = base64_decode(base64_decode($this->route('appointment'))) ?? null;   
        // dd($id);

      //  dd(auth()->user()->hasRole('super-admin'));
        if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('Assistant') || auth()->user()->hasRole('Staff')){

            $required_fields = [];

            if(!empty($request->new_patient_chkbox) && $request->new_patient_chkbox==1){
                $required_fields['family_name'] = 'required';
                $required_fields['first_name']  = 'required';
                // $required_fields['birth_date']  = 'required|before:-13 years'; //commented on 11nov22

                 $required_fields['birth_date']  = 'required';
                // country code must start with + or 00 followed by non-zero digits
                // use a non-slash delimiter so the regex is never confused with the validation parser
                // putting the regex into an array prevents Laravel from splitting on the internal |
                $required_fields['format']  = [
                    'required',
                    'regex:~^(\+[1-9][0-9]*|00[1-9][0-9]*)$~',
                ];

                // $required_fields['mobile_no']   = 'required|regex:/^[1-9][0-9]*$/|numeric|unique:patients,mobile_no,NULL,NULL,deleted_at,NULL';   /Commented on 29-sept22 

                // $required_fields['mobile_no']   = 'required|regex:/^[1-9][0-9]*$/|numeric'; // Added on 29sept22
                // 
                 // Roshani remove the regex for 134
                $required_fields['mobile_no']   = 'required|numeric|regex:/^(?!0{2})0?[0-9]+$/'; // Added on 29sept22
                 // Roshani remove the regex  for 134

                //<!-- Roshani added this code  -->
                $required_fields['gender']  = 'required';
                // $required_fields['country']  = 'required';
                $required_fields['email']  = 'required|email';
                //<!-- Roshani added this code  -->


            }else{

                $required_fields['patient_id']  = 'required';
            }
            
            $required_fields['doctor_id']           = 'required';
            $required_fields['appointment_type_id'] = 'required';
            $required_fields['date']                = 'required';
            $required_fields['time_frame']          = 'required';

            return $required_fields;
        } else{

             $required_fields = [];

            if(!empty($request->new_patient_chkbox) && $request->new_patient_chkbox==1){
                $required_fields['family_name'] = 'required';
                $required_fields['first_name']  = 'required';
                // $required_fields['birth_date']  = 'required|before:-13 years'; //commented on 11nov22

                 $required_fields['birth_date']  = 'required';



                // $required_fields['mobile_no']   = 'required|regex:/^[1-9][0-9]*$/|numeric|unique:patients,mobile_no';
                // $required_fields['mobile_no']   = 'required|regex:/^[1-9][0-9]*$/|numeric|unique:patients,mobile_no,NULL,NULL,deleted_at,NULL';   //Commented on 29sept22
                $required_fields['mobile_no']   = 'required|regex:/^(?!0{2})0?[0-9]+$/|numeric'; // Added on 29sept22
                //<!-- Roshani added this code  -->
                $required_fields['gender']  = 'required';
                // $required_fields['country']  = 'required';
                $required_fields['email']  = 'required|email';
                //<!-- Roshani added this code  -->

            }else{
                $required_fields['patient_id']  = 'required';
            }
           // $required_fields['doctor_id']           = 'required';
            $required_fields['appointment_type_id'] = 'required';
            $required_fields['date']                = 'required';
            $required_fields['time_frame']          = 'required';

            return $required_fields;

        }

    }

    public function messages()
    {
        if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('Assistant') || auth()->user()->hasRole('Staff')){
            return [
                'family_name.required'          => __('admin.ERR_PATIENT_FAMILY_NAME_REQUIRED'),
                'first_name.required'           => __('admin.ERR_FIRST_NAME_REQUIRED'),
                'mobile_no.required'            => __('admin.ERR_MOBILE_NO_REQUIRED'),
                'mobile_no.regex'               =>  __('admin.ERR_MOBILE_NO_INVALID'),
                'mobile_no.numeric'             => __('admin.ERR_FORMAT_MOBILE_USER'),
                'mobile_no.unique'              => __('admin.ERR_MOBILE_UNIQUE'),
                'patient_id.required'           => __('admin.ERR_APPOINTMENT_PATIENT_REQUIRED'),
                'doctor_id.required'            => __('admin.ERR_DOCTOR_ID_REQUIRED'),
                'appointment_type_id.required'  => __('admin.ERR_APPOINTMENT_TYPE_REQUIRED'),
                'date.required'                 => __('admin.ERR_APPOINTMENT_DATE_REQUIRED'),
                'time_frame.required'           => __('admin.ERR_TIME_FRAME_REQUIRED'),
                'birth_date.required'   =>  __('admin.ERR_BIRTH_DATE_REQUIRED'),
                'email.required'   =>  __('admin.ERR_EMAIL_REQUIRED'),
                'gender'                =>  __('admin.ERR_PATIENT_GENDER_REQUIRED'),
                'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
                'format.required'       =>  __('admin.ERR_COUNTRY_CODE_REQUIRED'),
                'format.regex'       =>  __('admin.ERR_COUNTRY_CODE_WRONG'),

            ];
        } else{
            return [
                'family_name.required'          => __('admin.ERR_PATIENT_FAMILY_NAME_REQUIRED'),
                'first_name.required'           => __('admin.ERR_FIRST_NAME_REQUIRED'),
                'mobile_no.required'            => __('admin.ERR_MOBILE_NO_REQUIRED'),
                'mobile_no.regex'               =>  __('admin.ERR_MOBILE_NO_INVALID'),
                'mobile_no.numeric'             => __('admin.ERR_FORMAT_MOBILE_USER'),
                'mobile_no.unique'              => __('admin.ERR_MOBILE_UNIQUE'),
                'patient_id.required'           => __('admin.ERR_APPOINTMENT_PATIENT_REQUIRED'),
                'appointment_type_id.required'  => __('admin.ERR_APPOINTMENT_TYPE_REQUIRED'),
                'date.required'                 => __('admin.ERR_APPOINTMENT_DATE_REQUIRED'),
                'time_frame.required'           => __('admin.ERR_TIME_FRAME_REQUIRED'),
                'birth_date.required'   =>  __('admin.ERR_BIRTH_DATE_REQUIRED'),
               // 'doctor_id.required'            => __('admin.ERR_DOCTOR_ID_REQUIRED'),
                'email.required'         => __('admin.ERR_EMAIL_REQUIRED'),
                    'gender.required'       =>  __('admin.ERR_PATIENT_GENDER_REQUIRED'),
                    'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
                    // 'country.required'       =>  __('admin.ERR_COUNTRY_REQUIRED'),
                'format.required'       =>  __('admin.ERR_COUNTRY_CODE_REQUIRED'),
                'format.regex'       =>  __('admin.ERR_COUNTRY_CODE_WRONG'),

            ];
        }
        
    }
}
