<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class RolesRequest extends FormRequest
{

    public function authorize() 
    {
        return true;
    }

    public function rules(Request $request)
    {

        $id = base64_decode(base64_decode($this->route('endID'))) ?? null; 
        // dd($this->route('endID'),$id,$request->all());
        if ($id === null || $id=="") 
        {
            return [
                'name'=> 'regex:/^[a-zA-Z\-]+$/u|unique:roles,name',
                'identifier'=> 'required|regex:/^[a-zA-Z\-]+$/u|unique:roles,name'
            ];
        }else{
             return [
                'name'=> 'regex:/^[a-zA-Z\-]+$/u|unique:roles,name,'.$id,
                'identifier'=> 'required|regex:/^[a-zA-Z\-]+$/u|unique:roles,name,'.$id
            ];
        }
    }

    public function messages()
    {
        return [
            // 'name.required' => __('admin.ERR_ROLE_NAME'),
            'name.regex'   => __('admin.ERR_ROLE_REGEX'),
            'name.unique'  => __('admin.ERR_ROLE_UNIQUE'),
            'identifier.required' => __('admin.ERR_ROLE_IDENTIFIER'),
            'identifier.regex'   => __('admin.ERR_ROLE_IDENTIFIER_REGEX'),
            'identifier.unique'  => __('admin.ERR_ROLE_IDENTIFIER_UNIQUE')
        ];
    }
}
