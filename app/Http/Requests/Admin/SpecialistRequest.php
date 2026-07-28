<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest; 

class SpecialistRequest extends FormRequest 
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        //dd($this->route('specialist'));
        $id = base64_decode(base64_decode($this->route('specialist'))) ?? null;
      
        if($id == null){
                return [
                    'name' => 'required|unique:specialist,name',
                ];
        } else{
              
            return [
                'name'    => 'required|unique:specialist,name,'.$id,
                ];
        }
    }

    public function messages() 
    {
        return [

            'name.required'   => __('admin.ERR_SPECIALIST_NAME_REQUIRED'),     
            'name.unique'     => __('admin.ERR_ORDINATION_NAME_UNIQUE_REQUIRED'),        
        ];
    }
}
