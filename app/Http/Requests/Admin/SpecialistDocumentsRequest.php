<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest; 

class SpecialistDocumentsRequest extends FormRequest 
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        //dd($this->route('type_of_document'));
        $id = base64_decode(base64_decode($this->route('specialist'))) ?? null;
      
        if($id == null){
                return [
                    
                    'type_of_document' => 'required',
                    'name' => 'required|unique:specialist_has_documents,name',
                    // 'header_image' => 'required',
                    // 'footer_image' => 'required',
                    // 'date_of_last_activation' => 'required',
                    'background_color' => 'required',
                    // 'frequency'=>'required',
                    // 'frequency_type'=>'required',
                    'html_text' => 'required',
                ];
        } else{
              
            return [
                // 'name'    => 'required|unique:specialist,name,'.$id,
                'type_of_document' => 'required',
                'name' => 'required|unique:specialist_has_documents,name',
                // 'header_image' => 'required',
                // 'footer_image' => 'required',
                // 'date_of_last_activation' => 'required',
                'background_color' => 'required',
                // 'frequency'=>'required',
                // 'frequency_type'=>'required',
                'html_text' => 'required',
                ];
        }
    }

    public function messages() 
    {
        return [

            'type_of_document.required' => __('admin.ERR_DOCUMENT_TYPE_OF_DOCUMENT'),   
            'name.required'             => __('admin.ERR_SPECIALIST_DOCUMENT_NAME_REQUIRED'),     
            'name.unique'               => __('admin.ERR_SPECIALIST_DOCUMENT_NAME_UNIQUE_REQUIRED'), 
            // 'header_image.required'     => __('admin.ERR_DOCUMENT_HEADER_IMAGE'),
            // 'footer_image.required'     => __('admin.ERR_DOCUMENT_FOOTER_IMAGE'),
            'date_of_last_activation.required'     => __('admin.ERR_DOCUMENT_DATE_OF_ACTIVATION'),
            'background_color.required'    => __('admin.ERR_DOCUMENT_BACKGROUND_COLOR'),
            'frequency.required'        => __('admin.ERR_DOCUMENT_FREQUENCY'),
            'frequency_type.required'   => __('admin.ERR_DOCUMENT_FREQUENCY_TYPE'),
            'html_text.required'        => __('admin.ERR_DOCUMENT_HTML_TEXT'),        
        ];
    }
}
