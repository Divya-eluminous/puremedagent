@extends('web.layout.master')
@section('title', 'Register')
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
                <h3 class="mb-0 text-center">PERSÖNLICHE DATEN</h3>
              </div>
              <div class="card-body">
                <div class="para_content">
                  <p>Um die Bearbeitung Ihres Termins und die Kontaktaufnahme zu erleichtern füllen Sie bitte alle Felder vollständig aus.</p>
                </div>

                <form name="userLogin" id='userLogin'  action="{{ url('/online-appointment/register') }}" data-toggle="validator" autocomplete="off" class="form" role="form">

                  <!-- <div class="form-group row">
                    <label class=" col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_ANREDE_TEXT')</label>
                    <div class="col-sm-8 col-md-9 col-lg-5 ">
                      <select class="form-control" id="salutation" size="0" name="salutation" data-error="@lang('front.ERR_PATIENT_SALUTATION_REQUIRED')" required>
                        <option value="Mr">
                          Herr
                        </option>
                        <option value="Fr" selected>
                          Frau
                        </option>

                      </select>
                      <span class="help-block with-errors">
                          <ul class="list-unstyled">
                              <li class="err_salutation"></li>
                          </ul>
                      </span>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class=" col-sm-4 col-md-3 col-sm-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_TITLE_TEXT')</label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" name="title" value="">
                     data-error="@lang('front.ERR_PATIENT_TITLE_REQUIRED')" required
                      <span class="help-block with-errors">
                          <ul class="list-unstyled">
                              <li class="err_title"></li>
                          </ul>
                      </span>
                      </div>
                  </div> -->
                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_FIRST_NAME') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" id="first_name" name="first_name" value="" data-error="@lang('front.ERR_FIRSTNAME_REQUIRED')" required>

                    <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_first_name"></li>
                        </ul>
                    </span>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_LAST_NAME') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" id="family_name" name="family_name" value="" data-error="@lang('front.ERR_LASTNAME_REQUIRED_USER_PROFILE')" required> 

                    <span class="help-block with-errors">
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
                        @if(session()->has('birth_date'))
                         <input
                          type="text"
                          name="birth_date"
                          class="form-control"
                          maxlength="250"
                          value="{{ session('birth_date') }}"
                          readonly="readonly"
                          >

                        @else
                         <input
                          type="text"
                          name="birth_date"
                          class="form-control"
                          id="birth_date"
                          maxlength="250"
                          required
                          data-error="@lang('front.ERR_BIRTH_DATE_REQUIRED')"   readonly="readonly" style="background-color:white;"
                          >
                        @endif
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
                    <label class="col-sm-4 col-md-3  col-lg-4 col-form-label form-control-label">@lang('front.TITLE_EMAIL_ADDRESS') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">

                      @if(session()->has('email'))
                       <input class="form-control" type="email" name="email" value="{{ session('email') }}" readonly="readonly">
                      @else
                       <input class="form-control" type="email" name="email" value="" data-error="@lang('front.ERR_EMAIL_ADDRESS_REQUIRED')" required>
                      @endif

                     <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_email"></li>
                        </ul>
                    </span>
                    </div>
                  </div>

                  <!-- Roshani added this code  -->
                   <div class="form-group row">
                    <label class=" col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_SELECT_GENDER') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-5 ">
                      <select class="form-control" id="gender" size="0" name="gender" data-error="@lang('front.ERR_PATIENT_GENDER_REQUIRED')" required>
                         <option value="" name="">@lang('front.TITLE_CHOOSE_GENDER')</option>
                        <option value="M">
                          M
                        </option>
                        <option value="W">
                          W
                        </option>
                        <option value="D">
                          D
                        </option>
                      </select>
                      <span class="help-block with-errors">
                          <ul class="list-unstyled">
                              <li class="err_gender"></li>
                          </ul>
                      </span>
                    </div>
                  </div>
                  <!-- Roshani added this code  -->

                  <!-- # Roshani Added this code # CR #102 -->

                  <div class="form-group row">
                    <label class=" col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_COUNTRY') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-5 ">
                      <select class="form-control" id="country" size="0" name="country" data-error="@lang('front.ERR_COUNTRY_REQUIRED')" required>
                         <option value="" name="">@lang('front.TITLE_SELECT_COUNTRY')</option>
                        <option value="Austria">
                          @lang('front.TITLE_COUNTRY_AUSTRIA')
                        </option>
                        <option value="Germany">
                          @lang('front.TITLE_COUNTRY_GERMANY')
                        </option>
                        <option value="Switzerland">
                          @lang('front.TITLE_COUNTRY_SWITZERLAND')
                        </option>
                      </select>
                      <span class="help-block with-errors">
                          <ul class="list-unstyled">
                              <li class="err_country"></li>
                          </ul>
                      </span>
                    </div>
                  </div>

                  <!-- # Roshani Added this code # CR #102 -->


                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_MOBILE_NO') <span class="required">*</span></label>
                    <div class="col-sm-2 col-md-2 col-lg-3 custCountryCode">
                         <div class="select-editable">
                          <select name="country_code" class="form-control" id="country_code"
                          onchange="this.nextElementSibling.value=this.value" readonly="" style="pointer-events: none;">
                            <option value="+43">+43</option>
                            <option value="0">0</option>
                            <option value="0043">0043</option>
                        </select>
                         @if(session()->has('format'))
                          <input
                          type="text"
                          name="format"
                          id="format"
                          class="form-control"
                          placeholder="@lang('front.TITLE_PATIENT_COUNTRY_CODE')"
                          required
                          value='{{ session("format") }}'
                          maxlength="5"
                          pattern="(\+[0-9]+|0[0-9]+|00[0-9]+)"
                          data-error="@lang('front.ERR_COUNTRY_CODE_REQUIRED')"
                          data-pattern-error ="@lang('front.ERR_COUNTRY_CODE_WRONG')" readonly  />
                        @else
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
                        @endif

                                          <span class="help-block invalid-feedback with-errors" >
                                        <ul class="list-unstyled">
                                            <li class="err_format"></li>
                                        </ul>
                                    </span>
                      </div>
                      <span class="help-block ">
                        <ul class="list-unstyled">

                        </ul>
                      </span>
                    </div>
                    <div class="col-sm-6 col-md-7 col-lg-5 custMobileNum">
                      @if(session()->has('mobile_no'))
                      <input class="form-control" type="tel" name="mobile_no"  id="phone" value="{{ session('mobile_no') }}" readonly="">
                      @else
                      <input class="form-control" type="tel" name="mobile_no"  id="phone" data-error="@lang('front.ERR_MOBILE_NO_REQUIRED')" required>
                      @endif

                    <span id="validateNumber"></span>
                    <span class="help-block with-errors" >
                        <ul class="list-unstyled">
                            <li class="err_mobile_no"></li>
                        </ul>
                    </span>
                    </div>

                    <!-- <div class="col-lg-12 text-center card-footer">
                        <button type="button" class="btn btn-success" onclick="sendOtp()">@lang('front.TITLE_SEND_OTP')</button>
                    </div> -->

                  </div>

                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3  col-lg-4 col-form-label form-control-label">@lang('front.TITLE_PASSWORD') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8" id="show_hide_password">
                      <div class="input-group">
                        <input class="form-control" type="password" name="password" value="" required="" data-error="@lang('front.ERR_PASSWORD_REQUIRED')" ><!--  data-error="@lang('front.ERR_EMAIL_ADDRESS_REQUIRED')" required -->
                        <!-- /***************** Roshani added this for CR #229 *****************/ -->
                        <div class="input-group-append">
                          <span class="input-group-text" id="basic-addon2"><a href=""><i class="fa fa-eye-slash" aria-hidden="true" style="color: #969090;"></i></a></span>
                        </div>
                      </div>
                    <!-- /***************** Roshani added this for CR #229 *****************/ -->
                     <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_password"></li>
                        </ul>
                    </span>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3  col-lg-4 col-form-label form-control-label">@lang('front.TITLE_CON_PASSWORD') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8" id="show_hide_password_confirm">
                      <div class="input-group mb-3">
                      <input class="form-control" type="password" name="confirm_password" value="" required="" data-error="@lang('front.ERR_CONFIRM_PASSWORD')" ><!--  data-error="@lang('front.ERR_EMAIL_ADDRESS_REQUIRED')" required -->
                      <!-- /***************** Roshani added this for CR #229 *****************/ -->
                      <div class="input-group-append">
                        <span class="input-group-text" id="basic-addon2"><a href=""><i class="fa fa-eye-slash" aria-hidden="true" style="color: #969090;"></i></a></span>
                      </div>
                    </div>
                    <!-- /***************** Roshani added this for CR #229 *****************/ -->
                     <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_confirm_password"></li>
                        </ul>
                    </span>
                    </div>
                  </div>
                  <!--  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_OTP')<span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" value="" id="otp_code" required name="otp_code" data-error="@lang('front.ERR_OTP_REQUIRED')">
                      <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                               <li class="err_otp_code"></li>
                            </ul>
                        </span>
                    </div>
                  </div> -->


                  <!-- <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_PATIENT_ROAD')</label>
                    <div class="col-sm-6 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" name="road">
                     data-error="@lang('front.ERR_ROAD_REQUIRED')" required
                    <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_road"></li>
                        </ul>
                    </span>
                    </div>
                  </div> -->
                  <!-- <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_PATIENT_STREET_NO')</label>
                    <div class="col-sm-6 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" name="street_no">
                     data-error="@lang('front.ERR_ROAD_REQUIRED')" required
                    <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_street_no"></li>
                        </ul>
                    </span>
                    </div>
                  </div> -->
                  <!-- <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_PATIENT_POSTAL_CODE')</label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="number" name="postal_code">
                     <span class="help-block with-errors">
                          <ul class="list-unstyled">
                              <li class="err_postal_code"></li>
                          </ul>
                      </span>
                      </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_PATIENT_PLACE')</label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" name="place" value="">
                    <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_place"></li>
                        </ul>
                    </span>
                    </div>
                  </div> -->

                  <input type="hidden" name="doctor_id" value="{{ $appointment['doctor_id'] }}">
                  <input type="hidden" name="appointment_type_id" value="{{ $appointment['appointment_type_id'] }}">
                  <input type="hidden" name="roster_date" value="{{ $appointment['roster_date'] }}">
                  <input type="hidden" name="roster_time_slot" value="{{ $appointment['roster_time_slot'] }}">
                  <input type="hidden" name="roster_time_slot_hd_id" value="{{ $appointment['roster_time_slot_hd_id'] }}">
                  <input type="hidden" name="dr_type" value="{{ $appointment['dr_type'] }}">

                <div class="para_content">
                  <p>Indem Sie fortfahren, erklären Sie sich mit den <!-- <a href="#"> -->Allgemeinen Geschäftsbedingungen<!-- </a> --> und unserer  <a href="#" id="gdprLink"> Datenschutzerklärung</a> einverstanden. Sie verstehen, dass die Überprüfung von Kontaktdaten, Bestätigungen, Erinnerungen und Feedback zu unserem Kernservice gehören.</p>
                </div>
                <div class="form-group row">
                    <div class="col-sm-12  col-md-12">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="gdpr" id="gdpr" data-error="Bitte aktivieren Sie dieses Kontrollkästchen, um fortzufahren." required>
                        <label class="form-check-label" for="gdpr">
                           Ich akzeptiere die <a href="#" class="gdprLink">Datenschutzrichtlinien </a>
                        </label>
                        <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_gdpr"></li>
                        </ul>
                    </span>
                      </div>
                    </div>
                  </div>
                  <div class="form-group row submit_btn"><!--
                    <label class="col-lg-3 col-form-label form-control-label"></label> -->
                    <div class="col-lg-12 text-center card-footer">
                      <input id="btn_registation_sub" class="btn btn-success" type="submit" value="Bestätigen">
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
<script type="text/javascript" src="{{asset('assets/web/js/register.js?ver=01')}}"></script>

<script>



$(function() {
  $('.ui-datepicker').attr("translate","no").addClass('notranslate').css({ notranslate });
});
 function sendOtp() {

    //var patient_id = $("#patient_id").val();
    var first_name = $("#first_name").val();
    var family_name = $("#family_name").val();
    var country_code = $("#format").val();
    var mobile_no = $("#phone").val();

    var birth_date = $("#birth_date").val(); //added on 15-dec-23 for duplicate patient

    // console.log(doctor_id);
    // console.log(appointment_type_id);
    // console.log(appointment_date);

    //$("#appointment_date").blur();
    //return false;
    if (first_name != "" && family_name != "" && country_code != "" && mobile_no != ""  && birth_date!="") {
        var action = WEBURL + '/online-appointment/send-register-otp';
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, {
                first_name: first_name,
                family_name: family_name,
                country_code: country_code,
                mobile_no: mobile_no,
                birth_date:birth_date   //added on 15-dec-23
            })
            .then(response => {
              console.log(response);
              const resp =  response.data;
              console.log(resp.otp);
              //alert(resp.otp);
                $('.card-body').LoadingOverlay("hide");

                  $("#otp_code").attr('required',true);
                  if (resp.status == 'success')
                  {
                    toastr.success(resp.msg);
                  }

                  if (resp.status == 'error')
                  {
                    toastr.error(resp.msg);
                  }

            })
            .catch(error => {
                $('.card-body').LoadingOverlay("hide");
            })
    }else{
       toastr.error('Bitte füllen Sie die erforderlichen Felder aus');
    }

    return false;
}
 $(document).ready(function () {
    var currentUrl = window.location.href;
    var baseUrl = window.location.protocol + "//" + window.location.hostname;
    var newUrl = baseUrl + "/gdpr-details";
    $("#gdprLink").attr("href", newUrl);
    $("#gdprLink").attr("target", "_blank");

    $(".gdprLink").attr("href", newUrl);
    $(".gdprLink").attr("target", "_blank");
  });


</script>

@stop