@extends('web.layout.master')
@section('title', 'Login')
@section('content')
<!-- All Content Section Start -->
  <div class="container py-3">
    <div class="row">
      <div class="col-md-12"> 
        <hr class="mb-4">
        <div class="row justify-content-center">
          <div class="col-lg-6 col-md-9 col-sm-9">
            <span class="anchor" id="formUserEdit"></span>
           
            <!-- form user info -->
            <div class="card card-outline-secondary form_data">
              <div class="card-header personal_head">
                <h3 class="mb-0 text-center">ONLINE TERMINVEREINBARUNG</h3>

              </div>
              <div class="card-body">
                <p>Wenn Sie die Online Terminvereinbarung zum ersten Mal nutzen, benötigen wir zur Bearbeitung Ihres Termins und zur Kontaktaufnahme Ihre persönlichen Daten (Vor- und Nachnamen, Kontaktmöglichkeit). <a href="{{ url('/online-appointment/register') }}">Ich buche zum ersten Mal Online.</a></p>
              </div>
              <div class="card-body">
                <div class="para_content">
                  <p></p>     
                </div>
                <form name="userLogin" id='userLogin'  action="{{ url('/online-appointment/book') }}" data-toggle="validator" autocomplete="off" class="form" role="form">

                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_FIRST_NAME') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" value="" name="first_name" id="first_name" data-error="@lang('front.ERR_FIRSTNAME_REQUIRED')" required>
                      <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                               <li class="err_first_name"></li>
                            </ul>
                        </span>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_LAST_NAME') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" value="" name="family_name" id="family_name" data-error="@lang('front.ERR_LASTNAME_REQUIRED')" required>
                      <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                               <li class="err_family_name"></li>
                            </ul>
                        </span>
                    </div>
                  </div>
                   <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label"> 
                    @lang('front.TITLE_PATIENT_BIRTH_DATE')<span class="required">*</span> </label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                        <input 
                        type="text" 
                        name="birth_date" 
                        class="form-control"
                        id="birth_date"  
                        maxlength="250"
                        readonly="readonly" 
                        data-error="@lang('front.ERR_BIRTH_DATE_REQUIRED')" required
                        >
                      <!-- required
                      data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"  -->
                      <span class="help-block with-errors">
                        <ul class="list-unstyled">
                        <li class="err_birth_date"></li>
                        </ul>
                      </span>
                    </div> 
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_MOBILE_NO') <span class="required">*</span></label>
                    <div class="col-sm-2 col-md-2 col-lg-3 custCountryCode">
                      <div class="select-editable">
                        <select name="country_code" class="form-control" id="country_code" 
                          onchange="this.nextElementSibling.value=this.value">
                            <option value="+43">+43</option>
                            <option value="0">0</option>
                            <option value="0043">0043</option>
                        </select>
                        <input  
                            type="text" 
                            name="format"
                            id="format"  
                            class="form-control"
                            placeholder="@lang('front.TITLE_PATIENT_COUNTRY_CODE')"   
                            required
                             value='+43'
                            maxlength="5" 
                            pattern="(\+[0-9]+|0[0-9]+|00[0-9]+)"
                            data-error="@lang('front.ERR_COUNTRY_CODE_REQUIRED')"
                            data-pattern-error ="@lang('front.ERR_COUNTRY_CODE_WRONG')"  />
                      </div>
                      <span class="help-block ">
                        <ul class="list-unstyled">

                        </ul>
                      </span>
                    </div>
                    <div class="col-sm-6 col-md-7 col-lg-5 custMobileNum">
                      <input class="form-control" maxlength="12" type="tel"  id="mobile_no" name="mobile_no" data-error="@lang('front.ERR_MOBILE_NO_REQUIRED')" required>
                      <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                               <li class="err_mobile_no"></li>
                            </ul>
                        </span>
                    </div>
                    <div class="col-lg-12 text-center card-footer">
                        <button type="button" class="btn btn-success" onclick="sendOtp()">@lang('front.TITLE_SEND_OTP')</button>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_OTP')</label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" value="" id="otp_code" name="otp_code" data-error="@lang('front.ERR_OTP_REQUIRED')">
                      <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                               <li class="err_otp_code"></li>
                            </ul>
                        </span>
                    </div>
                  </div>
                  
                  <input type="hidden" name="doctor_id" value="{{ $appointment['doctor_id'] }}">
                  <input type="hidden" name="appointment_type_id" value="{{ $appointment['appointment_type_id'] }}">
                  <input type="hidden" name="roster_date" value="{{ $appointment['roster_date'] }}">
                  <input type="hidden" name="roster_time_slot" value="{{ $appointment['roster_time_slot'] }}">
                  <input type="hidden" name="roster_time_slot_hd_id" value="{{ $appointment['roster_time_slot_hd_id'] }}">
                  <input type="hidden" name="dr_type" value="{{ $appointment['dr_type'] }}">
                  
                  <div class="form-group row submit_btn"><!-- 
                    <label class="col-lg-3 col-form-label form-control-label"></label> -->
                    <div class="col-lg-12 text-center card-footer">
                        <button type="submit" class="btn btn-success login-submit-btn">Bestätigen</button>
                    </div>
                  </div>
                   
                </form>
              </div>
            </div><!-- /form user info -->
          </div>
        </div>
      </div><!--/col-->
    </div><!--/row-->
  </div><!--/container-->

@endsection
@section('scripts')
 <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
<script src="{{ asset('assets/admin-lte/plugins/daterangepicker/daterangepicker.js') }}"></script> 
 <script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{asset('assets/web/js/login.js?ver=0.01')}}"></script>

@stop