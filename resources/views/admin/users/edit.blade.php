@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('style') 
<!-- Bootstrap Color Picker -->
<!-- // ############# Roshani Added this code ################# -->
 <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') }}"> 
 <!-- // ############# Roshani Added this code ################# -->
   <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/plugins/image-crop/css/imgareaselect.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/plugins/image-crop/css/jquery.Jcrop.min.css') }}">
@endsection

<style type="text/css">
    .aligncls{
        text-align: center;
    }
</style>

@php
$isCurrent = auth()->user()->id === (int)base64_decode(base64_decode(request()->segment(3))) ? true:false;  

@endphp

@section('content') 
<!-- <section class="content">
    <div class="box box-primary">
        <div class="box-body"> -->
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

        <form id="customerForm" role="form" data-toggle="validator" action="{{ route($modulePath.'update', [base64_encode(base64_encode($customer->id))]) }}">
                <input type="hidden" name="_method" value="PUT">
           <div class="card-body"> 


    <div class="row">
        @if(!$isCurrent)
        <div class="col-sm-6"> 
            <div class="form-group">
             <label class="theme-blue">@lang('admin.TITLE_SELECT_ROLE') <span
                class="required">*</span></label>
             <select class="form-control my-select role" name="role" id="role" required
                data-error="@lang('admin.ERR_ROLE')">
                <option value="" name="">@lang('admin.TITLE_SELECT_ROLE')</option>
                @php
                    $user_role = ''; 
                    if(count($customer->getRoleNames())>0){
                        $user_role = $customer->getRoleNames()[0];
                    }
                @endphp
                @if(!empty($roles) && sizeof($roles) > 0)
                    @foreach($roles as $key => $role)
                    <option value="{{ base64_encode(base64_encode($role->id)) }}" name="{{$role->name}}" @if($role->name == $user_role) selected @endif>
                       {{ ucfirst(str_replace('-', ' ',$role->identifier)) }}
                    </option>
                    @endforeach 
                @endif
             </select>
             <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                   <li class="err_role"></li>
                </ul>
             </span>
          </div>
        </div>
         @endif   

        <!-- Change the class -->
        @php
            $user_role="";
            if(count($customer->getRoleNames())>0){
              $user_role = strtolower($customer->getRoleNames()[0]);
            }
            $ClassName = "display:none";
            if($user_role == "doctor"){
              $ClassName = "display:block"; 
            } 
        @endphp
        <div class="col-sm-6" style="<?php echo $ClassName ?>" id="colorId">       
            <div class="row form-group"> 
              <div class="col-sm-9"> 
                <label>@lang('admin.TITLE_COLOR') 
                  <span class="required">*</span>
                </label>

                <select class="form-control my-select" placeholder="Select Google Color" name="google_color_id" id="google_color" required>
                  <option value="" data-code="#ffffff">Select Color</option>
                  @if(!empty($colors) && count($colors) > 0)
                    @foreach($colors as $color)
                      <option value="{{ $color->id }}" data-code="{{ $color->code }}" @if($customer->google_color_id == $color->id) selected="selected"
                    @endif>{{ $color->name }}</option>
                    @endforeach 
                  @endif
                </select>
                 <span class="help-block invalid-feedback with-errors">
                    <ul class="list-unstyled">
                       <li class="err_color"></li>
                    </ul>
                </span>
             </div>
              <div class="col-sm-2">   
                  <div class="d-flex flex-column" id="preview">
                      <label class="theme-blue aligncls">Preview</label>
                      <span class="wh-30 preview-color-box" style="background-color: @if(!empty($customer->assignedColor)) {{ $customer->assignedColor->code }} @endif"></span>
                  </div>
              </div> 
            </div> 
        </div>

        <div class="col-sm-6" id="doctor_speciality_id" style="<?php echo $ClassName ?>">       
            <div class="form-group">
                <label>@lang('admin.TITLE_DOCTOR_SPECIALITY') 
                  <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="doctor_speciality" 
                    class="form-control" 
                    id="doctor_speciality"
                    required
                    value="{{ $customer->doctor_speciality }}" 
                    maxlength="250" 
                    data-error="@lang('admin.ERR_DOCTOR_SPECIALITY_REQUIRED')" 
                >
                 <span class="help-block invalid-feedback with-errors">
                    <ul class="list-unstyled">
                       <li class="err_doctor_speciality"></li>
                    </ul>
                </span>
            </div> 
          </div>

         @php
            $user_role="";
            if(count($customer->getRoleNames())>0){
              $user_role = strtolower($customer->getRoleNames()[0]);
            }
            $doctorClassName = "display:none";
            if($user_role == "assistant"){
              $doctorClassName = "display:block"; 
            } 
        @endphp
        <div class="col-sm-6" id="doctorId" style="<?php echo $doctorClassName ?>">
            <div class="form-group">
                <label class="theme-blue">@lang('admin.TITLE_SELECT_DOCTOR') <span
                    class="required">*</span></label>
                <select class="form-control my-select" id="doctor" name="doctor_id"
                    data-error="@lang('admin.ERR_DOCTOR_REQUIRED')">
                    <option value="">@lang('admin.TITLE_SELECT_DOCTOR')</option>
                    @if(!empty($users) && count($users) > 0)
                        @foreach($users as $key => $user)
                        <option value="{{ $user->id }}" @if($customer->doctor_id == $user->id) selected="selected" @endif>
                           {{ ucfirst(str_replace('-', ' ',$user->first_name.' '.$user->last_name)) }}
                        </option>
                        @endforeach 
                    @endif
                </select> 
                <span class="help-block invalid-feedback with-errors">
                    <ul class="list-unstyled">
                       <li class="err_doctor_id"></li>
                    </ul>
                </span>
            </div>
          </div>
        </div>
            <div class="row">
             <div class="col-sm-6"> 
                <div class="d-flex flex-column mb-25 form-group">
                    <label class="theme-blue">@lang('admin.TITLE_FIRST_NAME') <span class="required">*</span></label>
                    <input 
                        type="text" 
                        name="first_name"
                        value="{{ $customer->first_name }}"  
                        class="form-control" 
                        required
                        maxlength="250" 
                        data-error="@lang('admin.ERR_FIRSTNAME_REQUIRED')" 
                    >
                    <span class="help-block invalid-feedback with-errors">
                        <ul class="list-unstyled">
                            <li class="err_first_name"></li>
                        </ul>
                    </span>
                </div>
            </div>
            <div class="col-sm-6"> 
                <div class="form-group">
                    <label class="theme-blue"> 
                    @lang('admin.TITLE_LAST_NAME') <span class="required">*</span></label>
                    <input 
                        type="text" 
                        name="last_name" 
                         value="{{ $customer->last_name }}"  
                        class="form-control" 
                        required
                        maxlength="250" 
                        data-error="@lang('admin.ERR_LASTNAME_REQUIRED')" 
                    >
                    <span class="help-block invalid-feedback with-errors">
                        <ul class="list-unstyled">
                            <li class="err_last_name"></li>
                        </ul>
                    </span>
                </div>
            </div>
            </div>

            <div class="row">
            <div class="col-sm-6"> 
                <div class="d-flex flex-column mb-25 form-group">
                    <label class="theme-blue">@lang('admin.TITLE_EMAIL_ADDRESS') <span class="required">*</span></label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ $customer->email }}"  
                        class="form-control" 
                        required
                        data-error="@lang('admin.ERR_EMAIL_REQUIRED')" 
                        pattern='^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$'
                        data-pattern-error="@lang('admin.ERR_EMAIL_FORMAT')">
                    <span class="help-block invalid-feedback with-errors">
                        <ul class="list-unstyled">
                            <li class="err_email"></li>
                        </ul>
                    </span>
                </div>
            </div>
            <div class="col-sm-2"> 
              <div class="form-group ">
                  <label class="theme-blue"> 
                  @lang('admin.TITLE_PATIENT_COUNTRY_CODE') <span class="required">*</span></label> 
                   <div class="select-editable">
                    @php
                        if(!in_array($customer->country_code, $country_codes)){
                            $country_codes[] = $customer->country_code;
                        }
                        $selectedCode = old('country_code', $customer->country_code ?? $country_codes[0] ?? '');
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
                          <li class="err_country_code"></li>
                      </ul>
                  </span>
              </div> 
            </div>
            <div class="col-sm-4"> 
                <div class="d-flex flex-column mb-25 form-group">
                    <label class="theme-blue">@lang('admin.TITLE_MOBILE_NO') <span class="required">*</span></label>
                    <input 
                        type="text" 
                        name="mobile_number" 
                        value="{{ $customer->mobile_number }}"  
                        class="form-control" 
                        required
                        maxlength="15" 
                        pattern="^(?!0{2})0?[0-9]+$"
                        data-error="@lang('admin.ERR_MOBILE_NO_REQUIRED')" 
                        data-pattern-error="@lang('admin.ERR_MOBILE_NO_INVALID')" 
                    >
                    <span class="help-block invalid-feedback with-errors">
                        <ul class="list-unstyled">
                            <li class="err_mobile_number"></li>
                        </ul>
                    </span>
                </div>
            </div>
        </div>
       <div class="row">
        <!-- LEFT SIDE -->
        <div class="col-sm-6">

    @php
        $profileImage = asset('assets/admin/images/default-image.png');
        if (!empty($customer->img_path)) {
            $profileImage = $imagaPath.$customer->img_path; 
        }
    @endphp

    <div class="mb-3" style="text-align:left;">
        <img src="{{ $profileImage }}" 
             style="width:150px; height:150px; border-radius:50%; object-fit:cover; display:block;">

        <label class="theme-blue d-block mt-2">
            @lang('admin.TITLE_PROFILE_IMAGE')
        </label>
    </div>

    <div class="form-group">
        <input 
            class="form-control image"  
            type="file" 
            name="profile_img" 
            id="file"  
            accept="image/*">

        <span class="help-block invalid-feedback with-errors">
            <ul class="list-unstyled">
                <li class="err_profile_img"></li>
            </ul>
        </span>

        <input type="hidden" name="old_image" id="old_image" value="{{ $customer->img_path }}">

        <input type="hidden" name="x" id="x">
        <input type="hidden" name="y" id="y">
        <input type="hidden" name="x2" id="x2">
        <input type="hidden" name="y2" id="y2">
        <input type="hidden" name="w" id="w">
        <input type="hidden" name="h" id="h">

        <img width="220" src="" id="previewimage" class="img-responsive default_img_size" style="display:none;">
        <img width="220" src="" id="sideprofileimage" class="img-responsive default_img_size" style="display:none;">
    </div>

</div>


        <!-- RIGHT SIDE -->
        <div class="col-sm-6"> 
            <div class="flex-column mb-25 form-group" id="isStatus">
                   <label class="theme-blue">@lang('admin.TITLE_EXAMINATION_STATUS')</label>
                  <div class="checkbox">
                        <input 
                          type="checkbox"   
                          name="status" 
                          value="1"
                           id="status"
                          {{ $customer->status==1?'checked':''}}
                          >
                          @lang('admin.TITLE_EXAMINATION_STATUS_ACTIVE') 
                  </div>
                  <span class="help-block invalid-feedback with-errors">
                      <ul class="list-unstyled">
                          <li class="err_status"></li>
                      </ul>
                  </span>
              </div>
            <div class="flex-column mb-25 form-group" id="isEmergencyDoctor">
                <label class="theme-blue">@lang('admin.TITLE_IS_EMERGENCY_DOCTOR')</label>
                <div class="d-flex align-items-center">
                    <div class="radio" style="margin-right:20px;">
                        <input type="radio" 
                            name="status" 
                            id="ed_yes"
                            value="1"
                            {{ $customer->status == 1 ? 'checked' : '' }}>
                        @lang('admin.WARNING_TITLE')
                    </div>

                    <div class="radio">
                        <input type="radio" 
                            name="status" 
                            value="0"
                            id="ed_no"
                            {{ $customer->status == 0 ? 'checked' : '' }}>
                        @lang('admin.WARNING_TITLE_NO')
                    </div>
                </div>

                <span class="help-block invalid-feedback with-errors">
                    <ul class="list-unstyled">
                        <li class="err_is_emergency_doctor"></li>
                    </ul>
                </span>
            </div>
        </div>
    </div>
 
      <div class="row">
        <div class="col-sm-6"> 
            <div class="form-group">
                 <label class="theme-blue">@lang('admin.TITLE_PASS') </label>
                 <input class="form-control" type="password" name="password" id="password"  data-error="@lang('admin.ERR_PASS')">
                 <span class="help-block invalid-feedback with-errors">
                    <ul class="list-unstyled">
                       <li class="err_password"></li>
                    </ul>
                 </span>
              </div>
          </div>
          <!-- <div class="col-sm-6"> 
              <div class="form-group">
                <label class="theme-blue">@lang('admin.TITLE_CONFIRM_PASS') 
                  <span class="required">*</span>
                </label>
                <input class="form-control" type="password" name="confirm_password" required data-error= "@lang('admin.ERR_CONFIRM_PASS')">
                  <span class="help-block invalid-feedback with-errors">
                    <ul class="list-unstyled">
                       <li class="err_confirm_password"></li>
                    </ul>
                 </span>
              </div> 
           </div> -->
      </div> 
         <!-- // ############# Roshani Added this code ################# -->
      <div class="card card-default" id="appointment_id_hide" style="display: none" > 
        <div class="row">   
            <div class="col-12">
                <div class="form-group">
                    <label>@lang('admin.TITLE_APPOINTMENT_TYPE_TEXT')</label>
                    <select 
                        name="appointments" 
                        maxlength="250"  
                        data-error="@lang('admin.ERR_APPOINTMENT_TYPE_ID_REQUIRED')"
                        class="duallistbox" 
                        multiple="multiple"
                        >
                    @foreach($appointments as $appointment)
                      @if(!in_array($appointment->id,$assigned_appointment_ids))
                          <option value="{{ $appointment->id }}">{{ $appointment->name }}</option>
                      @else
                          <option @if(!empty($appointment->default_service) && ucfirst($appointment->name) == ucfirst($appointment->name)) disabled @endif  value="{{ $appointment->id }}" selected="selected">{{ $appointment->name }}</option>
                      @endif
                    @endforeach
                  </select>
                   
                </div>
            <!-- /.form-group -->
          </div>
          <!-- /.col -->
        </div>
      </div>
      <span class="help-block invalid-feedback with-errors">
                    <ul class="list-unstyled">
                        <li class="err_appointments"></li>
                    </ul>
                  </span>
      <!-- // ############# Roshani Added this code ################# -->

      </div><!-- /.card-body -->

          <div class="card-footer">
              <button type="submit" class="btn btn-success save_button">@lang('admin.TITLE_SAVE_BUTTON')</button>
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
    var add_edit_page = true; 
</script>
<!-- // ############# Roshani Added this code ################# -->
<script src="{{ asset('assets/admin-lte/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') }}"></script> 
<!-- // ############# Roshani Added this code ################# -->
<!-- bootstrap color picker -->
<script src="{{ asset('assets/plugins/image-crop/js/jquery.Jcrop.min.js') }}"></script>
<script src="{{ asset('assets/plugins/image-crop/js/jquery.imgareaselect.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/users/create-edit.js') }}"></script>

<!-- // ############# Roshani Added this code on 16-may-24################# -->

<script type="text/javascript">
  var flag = 0;
</script>

<!-- // ############# Roshani Added this code on 16-may-24################# -->

@endsection