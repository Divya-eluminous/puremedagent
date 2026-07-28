<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BeaconsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('setting'))) ?? null;   
        // dd($id);
        if ($id == null) 
        {
            return [
                'uuid.*'     => 'required',
                'major.*'     => 'required',
                'identifier.*'     => 'required', 
                'minor.*'     => 'required'                   
            ];
        }
       
    }

    public function messages()
    {
        return [
            'uuid.*.required' => __('admin.ERR_BEACONS_UUID_REQUIRED'),
            'major.*.required' => __('admin.ERR_BEACONS_MAJOR_REQUIRED'),
            'identifier.*.required' => __('admin.ERR_BEACONS_IDENTIFIER_REQUIRED'),
            'minor.*.required' => __('admin.ERR_BEACONS_MINOR_REQUIRED'),           
        ];
    }
}
