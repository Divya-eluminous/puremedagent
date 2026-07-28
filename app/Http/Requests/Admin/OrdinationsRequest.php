<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest; 
use Illuminate\Validation\Rule;

class OrdinationsRequest extends FormRequest 
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('ordination'))) ?? null;
      
        if($id == null){ 
                return [
                    'name'            => 'required|unique:ordination,name',
                    // 'text_color_code' => 'required',
                    'logo'            => 'required',
                    // 'background_color'=> 'required',
                    'address'         => 'required',
                    'postal_code'     => 'required',

                    //Uncommented roshani for 06-nov-24  for CR #126
                    'email'           => 'required|email', //commented on 31-july-24

                    //commented roshani for 06-nov-24 for CR #126

                   // 'email'                  => [
                   //  'required',
                   //  'email',
                   //  Rule::unique('ordination', 'email')->whereNull('deleted_at'),
                   //  ],//added on 31-july-24

                    // 'calendar_id'     => 'required',
                    'button_colors_code'   => 'required',
                    'screen_bg_color' => 'required',
                    'app_bar_color'   => 'required',
                    'tabs_selection_color'     => 'required',
                    // 'home_screen_options_color'=> 'required',//roshani hide the code for issue 59 on 31-07-24
                    'menu_header_colors'       => 'required',
                    'menu_bg_color'   => 'required',
                    'dark_text_color' => 'required',
                    'light_text_color'=> 'required',
                    'header_text_color'=> 'required',
                    'country' => 'required',//Roshani Added For CR #102
                ];
        } else{
              
            return [
               // 'name'            => 'required|unique:ordination,name,'.$id,
                // 'text_color_code' => 'required',
                // 'logo'            => 'required',
                // 'background_color'=> 'required',
                'address'         => 'required',
                'postal_code'     => 'required',

                // 'email'           => 'required|email|unique:ordination,email,'.$id, //commented on 31-july-24
                //Uncommented roshani for 06-nov-24  for CR #126
                    'email'           => 'required|email', //commented on 31-july-24
                //commented roshani for 06-nov-24 for CR #126

                //  'email'           => [
                //     'required',
                //     'email',
                //     Rule::unique('ordination', 'email')->ignore($id)->whereNull('deleted_at'),
                // ],  //added on 31-july-24

                'button_colors_code'   => 'required',
                'screen_bg_color' => 'required',
                'app_bar_color'   => 'required',
                'tabs_selection_color'     => 'required',
                // 'home_screen_options_color'=> 'required',//roshani hide the code for issue 59 on 31-07-24
                'menu_header_colors'       => 'required',
                'menu_bg_color'   => 'required',
                'dark_text_color' => 'required',
                'light_text_color'=> 'required',
                'header_text_color'=> 'required',
                'country' => 'required',//Roshani Added For CR #102

                ];
        }
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $country = $this->input('country');
            $postalCode = $this->input('postal_code');

            if ($country === 'Germany' && !preg_match('/^\d{5}$/', $postalCode)) {
                $validator->errors()->add('postal_code', __('admin.MSG_INVALID_POSTAL_CODE_COUNTRY_GERMANY'));
            }

            if (in_array($country, ['Austria', 'Switzerland']) && !preg_match('/^\d{4}$/', $postalCode)) {
                $validator->errors()->add('postal_code', __('admin.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI1') . $country . __('admin.MSG_INVALID_POSTAL_CODE_COUNTRY_AUS_SWI2'));
            }
        });
    }

    public function messages() 
    {
        return [

            'name.required'             => __('admin.ERR_ORDINATION_NAME_REQUIRED'),     
            'name.unique'               => __('admin.ERR_ORDINATION_NAME_UNIQUE_REQUIRED'),        
            'logo.required'             => __('admin.ERR_ORDINATION_LOGO'),    
            'text_color_code.required'  => __('admin.ERR_ORDINATION_TEST_COLOR_CODE'),    
            'background_color.required' => __('admin.ERR_ORDINATION_BACKGROUND_COLOR_CODE'),    
  
            'address.required'          => __('admin.ERR_ORDINATION_ADDRESS_REQUIRED'),   
            'postal_code.required'      => __('admin.ERR_PATIENT_POSTAL_CODE_REQUIRED'),
            'email.required'        =>  __('admin.ERR_PATIENT_EMAIL_ADDRESS'),
            'email.email'           => __('admin.ERR_EMAIL_FORMAT'),
            'email.unique'          => __('admin.ERR_EMAIL_DUP'), 
            'calendar_id.required'        =>  __('admin.ERR_ORDINATION_CALENDAR_ID_REQUIRED'),
            'calendar_id.unique'          => __('admin.ERR_ORDINATION_CALENDAR_ID_DUP'), 
            'button_colors_code'         => __('admin.ERR_ORDINATION_BUTTONE_COLOR_CODE'), 
            'screen_bg_color'       => __('admin.ERR_ORDINATION_SCREEN_COLOR_CODE'), 
            'app_bar_color'         => __('admin.ERR_ORDINATION_APP_BAR_COLOR_CODE'), 
            'tabs_selection_color'  => __('admin.ERR_ORDINATION_TABS_SELECTION_COLOR_CODE'), 
            'home_screen_options_color'=> __('admin.ERR_ORDINATION_HOME_SCREEN_OPTION_COLOR_CODE'), 
            'menu_header_colors'       => __('admin.ERR_ORDINATION_MENU_HEARDER_COLOR_CODE'), 
            'menu_bg_color'         => __('admin.ERR_ORDINATION_MENU_BG_COLOR_COLOR_CODE'), 
            'dark_text_color'       => __('admin.ERR_ORDINATION_DARK_TEXT_COLOR_CODE'), 
            'light_text_color'      => __('admin.ERR_ORDINATION_LIGHT_TEXT_COLOR_CODE'), 
            'header_text_color'     => __('admin.ERR_ORDINATION_HEADER_FOOTER_COLOR_CODE'), 
            'country.required'      => __('admin.ERR_COUNTRY_REQUIRED'),//Roshani Added For CR #102
        ];
    }
}
