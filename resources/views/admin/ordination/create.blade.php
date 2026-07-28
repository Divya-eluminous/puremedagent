@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content')

<style>
fieldset {
  background-color: #eeeeee;
}

legend {
  background-color: gray;
  color: white;
  padding: 5px 10px;
}

input {
  margin: 5px;
}
</style>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/css/bootstrap-colorpicker.min.css" rel="stylesheet">
<!-- Main content -->        
<section class="content">
<div class="container-fluid">      
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- jquery validation -->
            <div class="card card-primary"> 
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button>
                </div>

                <form id="OrdinationsForm" role="form"  data-toggle="validator" action="{{ route($modulePath.'.store') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        class="form-control"  
                                        required
                                        maxlength="250"
                                        oninput="this.value = this.value.replace(/[^A-Za-zÄÖÜäöüß\s-]/g, '')"
                                        title="Only letters, spaces, and hyphens are allowed" 
                                        data-error="@lang('admin.ERR_ORDINATION_NAME_REQUIRED')" 
                                        
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_LOGO')<span class="required">*</span>
                                    <input 
                                        type="file" 
                                        name="logo" 
                                        class="form-control"  
                                        maxlength="250" 
                                        required
                                        data-error="@lang('admin.ERR_ORDINATION_LOGO')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_logo"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- ################ Roshani hide this code ################# -->

                       <!--  <div class="row">
                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_TEXT_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="text_color_code" 
                                        id="text_color_code" 
                                        class="form-control text_color"  
                                        required
                                        autocomplete="off" 
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_ORDINATION_TEST_COLOR_CODE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_text_color_code"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control textsetColorCode textcolorpicker" 
                                > 
                            </div>

                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_BACKGROUND_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="background_color" 
                                        class="form-control background_color"  
                                        required
                                        maxlength="250" 
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_ORDINATION_BACKGROUND_COLOR_CODE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_background_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control bgcolorpicker setColorCode" 
                                > 
                            </div>
                        </div> -->
                        
                        <!-- ################ Roshani hide this code ################# -->

                        <div class="row">
                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_BUTTONE_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="button_colors_code" 
                                        id="button_colors_code" 
                                        class="form-control button_colors_code"  
                                        required
                                        autocomplete="off" 
                                        maxlength="250"
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')"
                                        data-error="@lang('admin.ERR_ORDINATION_BUTTONE_COLOR_CODE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_button_colors_code"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control buttonsetColorCode buttoncolorpicker" 
                                > 
                            </div>

                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_SCREEBBG_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="screen_bg_color" 
                                        id="screen_bg_color" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_ORDINATION_SCREEN_COLOR_CODE')"
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_screen_bg_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control screenbgcolorpicker screensetColorCode" 
                                > 
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_APP_BAR_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="app_bar_color" 
                                        id="app_bar_color" 
                                        class="form-control"  
                                        required
                                        autocomplete="off" 
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_ORDINATION_APP_BAR_COLOR_CODE')"
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_app_bar_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control appbrsetColorCode appbrcolorpicker" 
                                > 
                            </div>

                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_TAB_SELECTION_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="tabs_selection_color" 
                                        id="tabs_selection_color" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_ORDINATION_TABS_SELECTION_COLOR_CODE')"
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_tabs_selection_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control tabscolorpicker tabssetColorCode" 
                                > 
                            </div>
                        </div>

                        <div class="row">
                            <!-- <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_HOME_SCREEN_OPTION_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="home_screen_options_color" 
                                        id="home_screen_options_color" 
                                        class="form-control"  
                                        required
                                        autocomplete="off" 
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_ORDINATION_HOME_SCREEN_OPTION_COLOR_CODE')" 
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_home_screen_options_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control homescreensetColorCode homescreencolorpicker" 
                                > 
                            </div> -->

                            <!-- <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_MENU_HEARDER_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="menu_header_colors" 
                                        id="menu_header_colors" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_ORDINATION_MENU_HEARDER_COLOR_CODE')" 
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_tabs_selection_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control menuHeadercolorpicker menuHeadersetColorCode" 
                                > 
                            </div> -->
                        </div>

                        <div class="row">
                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_MENU_BG_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="menu_bg_color" 
                                        id="menu_bg_color" 
                                        class="form-control"  
                                        required
                                        autocomplete="off" 
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_ORDINATION_MENU_BG_COLOR_COLOR_CODE')" 
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_menu_bg_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control menubgsetColorCode menubgcolorpicker" 
                                > 
                            </div>

                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_DARK_TEXT_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="dark_text_color" 
                                        id="dark_text_color" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_ORDINATION_DARK_TEXT_COLOR_CODE')" 
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_dark_text_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control darktextcolorpicker darktextsetColorCode" 
                                > 
                            </div>
                        </div>
                     
                        <div class="row">

                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_LIGHT_TEXT_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="light_text_color" 
                                        id="light_text_color" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_ORDINATION_LIGHT_TEXT_COLOR_CODE')" 
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_light_text_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control lightcolorpicker lightsetColorCode" 
                                > 
                            </div>

                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_HEADER_FOOTER_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="header_text_color" 
                                        id="header_text_color" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_ORDINATION_HEADER_FOOTER_COLOR_CODE')" 
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_header_text_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control headercolorpicker headersetColorCode" 
                                > 
                            </div>                      
                            
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_MENU_HEARDER_COLOR_CODE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="menu_header_colors" 
                                        id="menu_header_colors" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_ORDINATION_MENU_HEARDER_COLOR_CODE')" 
                                        pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" 
                                        data-pattern-error="@lang('admin.ERR_PATTERN_COLOR_CODE')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_tabs_selection_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input  style="margin-top: 35px;"
                                    readonly 
                                    class="form-control menuHeadercolorpicker menuHeadersetColorCode" 
                                > 
                            </div>


                              <!-- <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_MOBILE_NO') <span class="required">*</span></label>
                                     <input 
                                        type="text" 
                                        name="mobile_no" 
                                        class="form-control" 
                                        maxlength="250" 
                                        required
                                        data-error="@lang('admin.ERR_MOBILE_NO_REQUIRED')"  >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_mobile_no"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div> -->
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_ADDRESS') <span class="required">*</span></label>
                                     <textarea
                                        style="margin-top: 5px;"
                                        rows="1" 
                                        type="text" 
                                        name="address" 
                                        class="form-control" 
                                        id="address"
                                        required
                                        data-error="@lang('admin.ERR_ORDINATION_ADDRESS_REQUIRED')"
                                    ></textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_address"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">

                             <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                        @lang('admin.TITLE_PATIENT_POSTAL_CODE')
                                    </label>
                                    <input 
                                        type="text" 
                                        name="postal_code" 
                                        class="form-control" 
                                        maxlength="5" 
                                        required
                                        data-error="@lang('admin.ERR_PATIENT_POSTAL_CODE_REQUIRED')"
                                        inputmode="numeric" 
                                        pattern="\d{4,5}" 
                                        oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length < 4) this.setCustomValidity('Please enter at least 4 digits'); else this.setCustomValidity('')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_postal_code"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>


                            <!-- <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_EMAIL') <span class="required">*</span></label>
                                      <input 
                                        type="email" 
                                        name="email" 
                                        class="form-control" 
                                        maxlength="250" 
                                        autocomplete="off" 
                                    >
                                       
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_email"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div> -->
                             <!-- # Roshani Added this code #  CR #102-->

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_COUNTRY') <span class="required">*</span></label>
                                    <select 
                                        class="form-control my-select"
                                        name="country"
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_COUNTRY_REQUIRED')" >
                                        <option value="" name="">@lang('admin.TITLE_SELECT_COUNTRY')</option>
                                        <option value="Austria">@lang('admin.TITLE_COUNTRY_AUSTRIA')</option>
                                        <option value="Germany">@lang('admin.TITLE_COUNTRY_GERMANY')</option>
                                        <option value="Switzerland">@lang('admin.TITLE_COUNTRY_SWITZERLAND')</option>
                                    </select>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_country"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                          <!-- # Roshani Added this code #  CR #102-->

                       
                        </div>



                        <div class="row">
                          <!-- # Roshani Added this code #  CR #102-->

                            <!-- <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_COUNTRY') <span class="required">*</span></label>
                                    <select 
                                        class="form-control my-select"
                                        name="country"
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_COUNTRY_REQUIRED')" >
                                        <option value="" name="">@lang('admin.TITLE_SELECT_COUNTRY')</option>
                                        <option value="Austria">@lang('admin.TITLE_COUNTRY_AUSTRIA')</option>
                                        <option value="Germany">@lang('admin.TITLE_COUNTRY_GERMANY')</option>
                                        <option value="Switzerland">@lang('admin.TITLE_COUNTRY_SWITZERLAND')</option>
                                    </select>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_country"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div> -->
                          <!-- # Roshani Added this code #  CR #102-->
                            
                            <div class="col-sm-6"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">@lang('admin.TITLE_ORDINATION_STATUS')</label>
                                     <div class="form-check">
                                          <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status" 
                                            value="1" 
                                            checked
                                            >
                                          <label class="form-check-label" for="status">@lang('admin.TITLE_EXAMINATION_STATUS_ACTIVE')</label>
                                    </div>   
                                </div>
                            </div>    
                        </div>

                        <div class="row">
                            <!-- <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_ADDRESS') <span class="required">*</span></label>
                                     <textarea
                                        style="margin-top: 5px;"
                                        rows="1" 
                                        type="text" 
                                        name="address" 
                                        class="form-control" 
                                        id="address"
                                        required
                                        data-error="@lang('admin.ERR_ORDINATION_ADDRESS_REQUIRED')"
                                    ></textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_address"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div> -->
                            <!-- //This code added by roshani for CR #126 on 6-nov-24 -->
                            @php
                            $mobile = '';
                            $email = '';

                            if($settings)
                            {

                                foreach($settings as $setting)
                                {
                                    if($setting->setting_key == 'ORDINATION_EMAIL')
                                    {
                                        $email = $setting->setting_value;
                                    }
                                    if($setting->setting_key == 'ORDINATION_MOBILE')
                                    {
                                        $mobile = $setting->setting_value;
                                    }
                                }
                            }

                            @endphp
                            <!-- //This code added by roshani for CR #126 on 6-nov-24 -->
                            
                            <div class="col-sm-6"  style="display: none;"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_MOBILE_NO') <span class="required">*</span></label>
                                     <input 
                                        type="text" 
                                        name="mobile_no" 
                                        class="form-control" 
                                        maxlength="250" 
                                        required
                                        data-error="@lang('admin.ERR_MOBILE_NO_REQUIRED')"
                                        value="{{$mobile}}"  >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_mobile_no"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6" style="display: none;"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_EMAIL') <span class="required">*</span></label>
                                      <input 
                                        type="email" 
                                        name="email" 
                                        class="form-control" 
                                        maxlength="250" 
                                        autocomplete="off" 
                                        value="{{$email}}"
                                    >
                                       
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_email"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div>
                            <!-- <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.CALENDAR_ID') <span class="required">*</span></label>
                                      <input 
                                        type="calendar_id" 
                                        name="calendar_id" 
                                        class="form-control" 
                                        required 
                                         data-error="@lang('admin.ERR_ORDINATION_CALENDAR_ID_REQUIRED')"
                                        autocomplete="off" 
                                    >
                                   
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_calendar_id"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div> -->
                        </div>



                       
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                        <button type="reset"  class="btn btn-danger reset">@lang('admin.TITLE_RESET_BUTTON')</button>
                    </div>
                </form> 
            </div>
        </div>
    </div>
</div>    
</section>
@endsection

@section('scripts')

<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/ordination/create-edit.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/js/bootstrap-colorpicker.min.js"></script>


@endsection 