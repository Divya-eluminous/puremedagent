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

                <form id="frmPatients" role="form" data-toggle="validator" action="{{ route($modulePath.'update', [base64_encode(base64_encode($patient->id))]) }}" autocomplete="off">
                    <input type="hidden" name="_method" value="PUT">
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
                                        value="{{ $patient->family_name }}"  
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
                                        value="{{ $patient->first_name }}" 
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
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_EMAIL') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="email" 
                                        class="form-control"  
                                        value="{{ $patient->email }}"
                                        maxlength="250" 
                                        required
                                        data-error="@lang('admin.ERR_EMAIL_REQUIRED')"
                                    >
                                        
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
                                     @php
                                         if(!in_array($patient->country_code, $country_codes)){
                                             $country_codes[] = $patient->country_code;
                                         }
                                         $selectedCode = old('country_code', $patient->country_code ?? $country_codes[0] ?? '');
                                         $showOther = $selectedCode !== '' && !in_array($selectedCode, $country_codes);
                                     @endphp
                                     <select 
                                        class="form-control my-select"
                                        name="country_code"
                                        id="country_code"
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"
                                        onchange="handleCountrySelect(this)">
                                        @foreach($country_codes as $item)
                                            <option value="{{$item}}" {{ !$showOther && $selectedCode == $item ? 'selected' : '' }}>{{$item}}</option>
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
                                        <!-- pattern="(\+[1-9][0-9]*|0[1-9](?!\d)|00[1-9][0-9]*)" -->

                        
                            <div class="col-sm-4"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_MOBILE_NO') <span class="required">*</span></label> 
                                    <input 
                                        type="text" 
                                        name="mobile_no" 
                                        id="mobile_no"  
                                        class="form-control"  
                                        value="{{ $patient->mobile_no }}"
                                        maxlength="15" 
                                        required
                                        pattern="^(?!0{2})0?[0-9]+$"
                                        data-error="@lang('admin.ERR_MOBILE_NO_REQUIRED')" 
                                        data-pattern-error="@lang('admin.ERR_MOBILE_NO_INVALID')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
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
                                        value="{{ $patient->birth_date?date('d-m-Y',strtotime($patient->birth_date)):'' }}"
                                        maxlength="250"
                                        data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"
                                        required  style="background-color:white;"
                                    >
                                        <!-- required readonly="readonly"
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
                                        value="{{ $patient->road }}" 
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_ROAD_REQUIRED')"  -->
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
                                        value="{{$patient->street_no}}" 
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
                                        value="{{ $patient->place }}" 
                                        maxlength="250" 
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
                                        value=""
                                        maxlength="250"
                                    ><!-- required data-error="@lang('admin.ERR_PASSWORD_REQUIRED')" -->
                                    <?php // {{ $patient->password }} removed from str_password->value ?>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_password"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_POSTAL_CODE') </label>
                                    <input 
                                        type="text" 
                                        name="postal_code" 
                                        class="form-control" 
                                        value="{{ $patient->postal_code }}"
                                        maxlength="5" 
                                         data-error="@lang('admin.ERR_PATIENT_POSTAL_CODE_REQUIRED')"
                                        inputmode="numeric" 
                                        pattern="\d{4,5}" 
                                        oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length < 4) this.setCustomValidity('Please enter at least 4 digits'); else this.setCustomValidity('')"
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_PATIENT_POSTAL_CODE_REQUIRED')"   -->
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
                                    @lang('admin.TITLE_PATIENT_GENDER')  <span class="required">*</span></label>
                                    <select name="gender" id="gender" class="form-control"
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_PATIENT_GENDER_REQUIRED')">
                                        <option value="" name="">@lang('admin.TITLE_SELECT_GENDER')</option>
                                        @foreach(['M', 'W', 'D'] as $item)
                                            <option value="{{$item}}"
                                            @if($patient->gender == old('gender', $item)) selected="selected" @endif >{{$item}}</option>
                                        @endforeach 
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
                                        value="{{ $patient->size }}" 
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
                                        value="{{ $patient->weight }}"
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
                                        value="{{ $patient->title }}"
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
                                    <select name="salutation" id="salutation" class="form-control my-select" required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_PATIENT_SALUTATION_REQUIRED')">
                                        <option value="" name="">@lang('admin.TITLE_SELECT_SALUTATION')</option>
                                        @foreach(['Hr', 'Fr'] as $item)
                                            <option value="{{$item}}"
                                            @if($patient->salutation == old('salutation', $item)) selected="selected" @endif >{{$item}}</option>
                                        @endforeach 
                                    </select>
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
                                        value="{{ $patient->family_doctor }}" 
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
                                        value="{{ $patient->insurance_number }}"
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
                                    @lang('admin.TITLE_PATIENT_ADDITIONAL_ENSURANCE') </label>
                                    <input 
                                        type="text" 
                                        name="additional_insurance" 
                                        class="form-control"   
                                        value="{{ $patient->additional_insurance }}"
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
                                            value="1"  @if(!empty($patient->status) && $patient->status==1) checked @endif
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
                                        value="{{ $patient->pat_nr }}"
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
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                   <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_NOTE')</label>
                                    <textarea
                                    name="note" 
                                    class="form-control"
                                    id="note"
                                    > </textarea>
                                    <!-- {{ $patient->note_report_request }} -->
                                    <span style="color: red;">
                                        <ul class="list-unstyled">
                                            <li class="err_note">@lang('admin.ERR_PATIENT_FINDING_NOTE')</li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
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
                                            value="1"  @if(!empty($patient->reminder_active) && $patient->reminder_active==1) checked @endif
                                        >
                                   <label class="form-check-label" for="status">@lang('admin.TITLE_ON')</label>
                               </div>
                                </div>
                            </div>
                        </div>



                        <!--date dropdown code added by swapnil pawar 22-09-2022-->
                        <div class="row">
                            <div class="col-sm-6">
                               <div class="form-group">
                                  <label class="theme-blue"> 
                                  <!-- @lang('admin.TITLE_REMINDER_ACTIVE') -->@lang('admin.TITLE_APPOINTMENT_DATE')</label>
                                  <!--dropdown code here-->
                                  <select name="last_appointment" class="form-control" id="last_appointment">
                                     <option value="">Select</option>
                                     @foreach($lastAppointment as $lastPatientsAppointment)
                                     <option value='{{ $lastPatientsAppointment->start_date }}'>{{date("Y-m-d",strtotime($lastPatientsAppointment->start_date))}}</option>
                                     @endforeach
                                  </select>
                                   <span style="color: red;">
                                        <ul class="list-unstyled">
                                            <li class="err_last_appointment"></li>
                                        </ul>
                                    </span>
                                  <!--dropdown code here-->
                               </div>
                            </div>
                          <!-- # Roshani Added this code #  CR #102-->

                             <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_COUNTRY') <span class="required">*</span></label>
                                   <!--   <input 
                                        type="text" 
                                        class="form-control"
                                        maxlength="250" 
                                        disabled="" 
                                        value="{{ $patient->country }}"
                                    > -->
                                    <select 
                                        class="form-control my-select"
                                        name="country"
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_COUNTRY_REQUIRED')" >
                                        <option value="" name="">@lang('admin.TITLE_SELECT_COUNTRY')</option>
                                        <option value="Austria" @if($patient->country == 'Austria') selected="selected" @endif>@lang('admin.TITLE_COUNTRY_AUSTRIA')</option>
                                        <option value="Germany" @if($patient->country == 'Germany') selected="selected" @endif>@lang('admin.TITLE_COUNTRY_GERMANY')</option>
                                        <option value="Switzerland" @if($patient->country == 'Switzerland') selected="selected" @endif>@lang('admin.TITLE_COUNTRY_SWITZERLAND')</option>
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
                        <!-- date dropdown code added by swapnil pawar 22-09-2022-->


                        <div class="row">

                            <!------start-commented on 30-july-25-----for #340--------------->
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
                                            value="1"  @if(!empty($patient->is_blocked) && $patient->is_blocked==1) checked @endif
                                        >
                                        <label class="form-check-label" for="block"> @lang('admin.TITLE_PATIENT_BLOCK')</label>
                                    </div>
                                </div>
                            </div>  -->
                            <!----------end-commented on 30-july-25---------------------->

                          <!-----start-commented on 6-nov-24 for #219 CR----------------->
 
                           <!--  <div class="col-sm-6"> 
                                <div class="form-group">
                                   <label class="theme-blue"> 
                                    @lang('admin.TITLE_GANY_PATIENT_ID')</label>
                                    <input 
                                        type="text" 
                                        name="old_id" 
                                        class="form-control"
                                        maxlength="250" 
                                        disabled="" 
                                        value="{{ $patient->old_id }}"
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

                    </div><!-- /.card-body -->

                    <div class="card-footer">   
                        <button type="submit" id="savebtn" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button> 
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>    
</section>
@endsection
<script type="text/javascript">
    var title            = "{{ __('admin.TITLE_REMINDER_TITLE') }}";
    var sub_title     = "{{ __('admin.TITLE_REMINDER_DEACTIVATE_TITLE') }}";
   
</script>
@section('scripts')
<script type="text/javascript">
    var familyNameText = "{{ __('admin.ERR_FAMILY_DOCTOR_NAME') }}";
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/patients/create-edit.js?ver=0.1') }}"></script>
@endsection