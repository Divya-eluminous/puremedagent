@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection

@section('content') 
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

                <form id="frmPatients" role="form" data-toggle="validator" action="{{ route($modulePath.'store') }}" autocomplete="off">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_FAMILY_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="family_name" 
                                        class="form-control"   
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_PATIENT_FAMILY_NAME_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_family_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_FIRST_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="first_name" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_FIRST_NAME_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_first_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                           <!--  <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_EMAIL') <span class="required">*</span></label>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        class="form-control" 
                                        maxlength="250" 
                                        autocomplete="off" readonly 
                                        onfocus="this.removeAttribute('readonly');"
                                        style="background-color: #fff;" 
                                    >
                                         required
                                        data-error="@lang('admin.ERR_PATIENT_EMAIL_ADDRESS')" 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_email"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div>  -->

                             <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_EMAIL')  <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="email" 
                                        class="form-control" 
                                        maxlength="250" 
                                        required
                                        data-error="@lang('admin.ERR_EMAIL_REQUIRED')"
                                        data-pattern-error="@lang('admin.ERR_EMAIL_FORMAT_INVALID')"
                                    >
                                        <!-- pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" -->


                                    <!-- readonly 
                                        onfocus="this.removeAttribute('readonly');"
                                        style="background-color: #fff;" -->
                                        <!-- required
                                        data-error="@lang('admin.ERR_PATIENT_EMAIL_ADDRESS')" -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_email"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div> 
                            <div class="col-sm-2"> 
                                <div class="form-group selector">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_COUNTRY_CODE') <span class="required">*</span></label> 
                                    <div class="select-editable">
                                    <select 
                                        class="form-control my-select"
                                        name="country_code"
                                        id="country_code"
                                        onchange="handleCountrySelect(this)">
                                        
                                        @php
                                            $selectedCode = old('country_code', $country_codes[0] ?? '');
                                            $showOther = $selectedCode !== '' && !in_array($selectedCode, $country_codes);
                                        @endphp
                                        @foreach($country_codes as $code)
                                            <option value="{{ $code }}" {{ !$showOther && $selectedCode == $code ? 'selected' : '' }}>{{ $code }}</option>
                                        @endforeach
                                        <option value="other" {{ $showOther ? 'selected' : '' }}>Weitere</option>
                                    </select>
                                      <input  
                                        type="text" 
                                        name="format"
                                        id="format"  
                                        class="form-control"
                                        placeholder="@lang('admin.TITLE_PATIENT_COUNTRY_CODE')"   
                                        required
                                         value='{{ $showOther ? $selectedCode : ($selectedCode ?? '') }}'
                                        maxlength="5" 
                                        pattern="^(\+[1-9][0-9]*|00[1-9][0-9]*)$"
                                        data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"
                                        data-pattern-error ="@lang('admin.ERR_COUNTRY_CODE_WRONG')"  />
                                </div>
                                    <span class="help-block invalid-feedback with-errors" >
                                        <ul class="list-unstyled">
                                            <li class="err_format"></li>
                                        </ul>
                                    </span>
                                </div> 
                            </div>
                        
                        
                            <div class="col-sm-4"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_MOBILE_NO') <span class="required">*</span></label> 
                                    <input  
                                        type="text" 
                                        name="mobile_no"
                                        id="mobile_no"  
                                        class="form-control"  
                                        required
                                        maxlength="15" 
                                        pattern="^(?!0{2})0?[0-9]+$"
                                        data-error="@lang('admin.ERR_MOBILE_NO_REQUIRED')" 
                                        data-pattern-error="@lang('admin.ERR_MOBILE_NO_INVALID')"
                                    >
                                    <span id="validateNumber"></span>
                                    <span class="help-block invalid-feedback with-errors" >
                                        <ul class="list-unstyled">
                                            <li class="err_mobile_no"></li>
                                        </ul>
                                    </span>
                                </div> 
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_BIRTH_DATE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="birth_date" 
                                        class="form-control"
                                        id="birth_date"  
                                        maxlength="250"
                                        data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"
                                        required   style="background-color:white;"
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_birth_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        
                            <div class="col-sm-6">  
                                <div class="form-group"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_ROAD') </label>
                                    <input 
                                        type="text" 
                                        name="road"  
                                        class="form-control"  
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_ROAD_REQUIRED')"   -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_road"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div> 

                        <div class="row">

                            <div class="col-sm-6">  
                                <div class="form-group"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_STREET_NO') </label>
                                    <input 
                                        type="text" 
                                        name="street_no"  
                                        class="form-control"  
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_ROAD_REQUIRED')"   -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_street_no"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_PLACE') </label>
                                    <input 
                                        type="text" 
                                        name="place" 
                                        class="form-control"  
                                        maxlength="250" 
                                        autocomplete="off" readonly 
                                        onfocus="this.removeAttribute('readonly');"
                                        style="background-color: #fff;" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_PLACE_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_place"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        
                        </div>

                        <div class="row">

                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_PASS') </label>
                                    <input 
                                        type="password" 
                                        name="str_password"  
                                        class="form-control"  
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_PASSWORD_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_password"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div> 

                           <div class="col-sm-6"> 
                                <div class="form-group">
                                    <!-- aishwarya commented on 30-5-25 -->
                                   <!--  <label class="theme-blue"> 
                                        @lang('admin.TITLE_PATIENT_POSTAL_CODE')
                                    </label> --> 
                                    <!-- span class added on 30-5-25 by aishwarya -->
                                     <label class="theme-blue"> 
                                        @lang('admin.TITLE_PATIENT_POSTAL_CODE')<span class="required"> *</span>
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


                         
                        </div>

                        <div class="row"> 

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_GENDER') <span class="required">*</span></label>
                                    <select 
                                        class="form-control my-select"
                                        name="gender"
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_PATIENT_GENDER_REQUIRED')" >
                                        <option value="" name="">@lang('admin.TITLE_SELECT_GENDER')</option>
                                        <option value="M">M</option>
                                        <option value="W">W</option>
                                        <option value="D">D</option>
                                    </select>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_gender"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>  

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_SIZE')</label>
                                    <input 
                                        type="text" 
                                        name="size" 
                                        class="form-control" 
                                        maxlength="250"  
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_size"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        
                            
                        </div>

                        <div class="row">

                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_WEIGHT')</label>
                                    <input 
                                        type="text" 
                                        name="weight" 
                                        class="form-control"  
                                        maxlength="250" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_weight"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_TITLE')</label> 
                                    <input 
                                        type="text" 
                                        name="title" 
                                        class="form-control"  
                                        maxlength="250"  
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_title"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        
                          
                        </div> 

                        <div class="row">

                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue">  
                                    @lang('admin.TITLE_PATIENT_SALUTATION') </label>
                                    <select 
                                        class="form-control my-select"
                                        name="salutation" 
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_PATIENT_SALUTATION_REQUIRED')">
                                        <option value="" name="">@lang('admin.TITLE_SELECT_SALUTATION')</option>
                                        <option value="Hr">@lang('admin.TITLE_MR_SALUTATION')</option>
                                        <option value="Fr" selected>@lang('admin.TITLE_MRS_SALUTATION')</option>
                                    </select>
                                   <!--  <input 
                                        type="text" 
                                        name="salutation" 
                                        class="form-control" 
                                        maxlength="250" 
                                    >  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_salutation"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_FAMILY_DOCTOR')</label>
                                    <input 
                                        type="text" 
                                        name="family_doctor"
                                        id="family_doctor"
                                        class="form-control" 
                                        maxlength="250" 
                                    >
                                    <span id="validate_name"></span>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_family_doctor"></li> 
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        
                           
                        </div>

                        <div class="row">

                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_ENSURANCE_NUMBER') </label>
                                    <input 
                                        type="text" 
                                        name="insurance_number" 
                                        class="form-control" 
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_PATIENT_ENSURANCE_NUMBER_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_insurance_number"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_ADDITIONAL_ENSURANCE')</label>
                                    <input 
                                        type="text" 
                                        name="additional_insurance" 
                                        class="form-control"
                                        maxlength="250" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_additional_insurance"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        
                           
                        </div>

                        

                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_STATUS') </label>
                                    <div class="form-check"> 
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status" 
                                            value="1" 
                                            checked
                                            >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_PATIENT_STATUS_ACTIVE')</label>
                                    </div> 
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                   <label class="theme-blue"> 
                                    @lang('admin.TITLE_GANY_PAT_NR')</label>
                                    <input 
                                        type="text" 
                                        name="old_id" 
                                        class="form-control"
                                        maxlength="250" 
                                        disabled="" 
                                        value="0"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_old_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">

                            <!-----start-commented on 30-july-25--for #340------------->
                            <!-- <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_IS_BLOCK') </label>
                                    <div class="form-check">
                                        <input 
                                            type="checkbox"
                                            class="form-check-input" 
                                            name="is_blocked"  
                                            id="block" 
                                            value="1"  
                                        >
                                        <label class="form-check-label" for="block"> @lang('admin.TITLE_PATIENT_BLOCK')</label>
                                    </div>
                                </div>
                            </div>  -->
                            <!-----end-commented on 30-july-25--------------->

                            <!-----start-commented on 6-nov-24 for #219 CR----------------->
                            <!-- <div class="col-sm-6"> 
                                <div class="form-group">
                                   <label class="theme-blue"> 
                                    @lang('admin.TITLE_GANY_PATIENT_ID')</label>
                                    <input 
                                        type="text" 
                                        name="old_id" 
                                        class="form-control"
                                        maxlength="250" 
                                        disabled="" 
                                        value="0"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_old_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div> -->

                            <!---end--commented on 6-nov-24 for #219 CR----------------------->


                        </div>

                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_REMINDER_ACTIVE')</label>
                                    <div class="form-check"> 
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="reminder_active"
                                            name="reminder_active"
                                            value="1" 
                                            checked="checked" 
                                        >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_ON')</label>
                                    </div>
                                </div>
                            </div>
                          <!-- # Roshani Added this code #  CR #102-->

                            <!-- <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_COUNTRY') <span class="required">*</span></label>
                                    <select 
                                        class="form-control my-select"
                                        name="country"
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

                        </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-success" id="savebtn">@lang('admin.TITLE_SAVE_BUTTON')</button>
                        <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>    
</section> 
@endsection

@section('scripts')
<script type="text/javascript">
    var familyNameText = "{{ __('admin.ERR_FAMILY_DOCTOR_NAME') }}";
</script>

<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/patients/create-edit.js?ver=0.5') }}"></script>
@endsection