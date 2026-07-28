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
                  <p>Um die Bearbeitung Ihres Termins und die Kontaktaufnahme zu erleichtern füllen Sie bitte alle Felder vollständig aus.</br>
                  Der Termin wird automatisch vorgemerkt und Sie erhalten eine E-Mail nach Bestätigung durch den Arzt</p>     
                </div>
                <form name="userLogin" id='userLogin'  action="{{ url('/online-appointment/register') }}" data-toggle="validator" autocomplete="off" class="form" role="form">

                  <div class="form-group row">
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
                    <!--  data-error="@lang('front.ERR_PATIENT_TITLE_REQUIRED')" required -->
                      <span class="help-block with-errors">
                          <ul class="list-unstyled">
                              <li class="err_title"></li>
                          </ul>
                      </span>
                      </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_FIRST_NAME') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" name="first_name" value="" data-error="@lang('front.ERR_FIRSTNAME_REQUIRED')" required>
                   
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
                      <input class="form-control" type="text" name="family_name" value="" data-error="@lang('front.ERR_LASTNAME_REQUIRED')" required>
                    
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
                    <label class="col-sm-4 col-md-3  col-lg-4 col-form-label form-control-label">@lang('front.TITLE_EMAIL_ADDRESS')</label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="email" name="email" value=""><!--  data-error="@lang('front.ERR_EMAIL_ADDRESS_REQUIRED')" required -->
                     <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_email"></li>
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
                      <input class="form-control" type="tel" name="mobile_no"  id="phone" data-error="@lang('front.ERR_MOBILE_NO_REQUIRED')" required>
                    
                    <span id="validateNumber"></span>
                    <span class="help-block with-errors" >
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
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_OTP')<span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" value="" id="otp_code" required name="otp_code" data-error="@lang('front.ERR_OTP_REQUIRED')">
                      <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                               <li class="err_otp_code"></li>
                            </ul>
                        </span>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_PATIENT_ROAD')</label>
                    <div class="col-sm-6 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" name="road">
                    <!--  data-error="@lang('front.ERR_ROAD_REQUIRED')" required -->
                    <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_road"></li>
                        </ul>
                    </span>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_PATIENT_STREET_NO')</label>
                    <div class="col-sm-6 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" name="street_no">
                    <!--  data-error="@lang('front.ERR_ROAD_REQUIRED')" required -->
                    <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_street_no"></li>
                        </ul>
                    </span>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_PATIENT_POSTAL_CODE')</label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="number" name="postal_code"> 
                    <!--  data-error="@lang('front.ERR_PATIENT_POSTAL_CODE_REQUIRED')" required -->
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
                    <!-- data-error="@lang('front.ERR_PLACE_REQUIRED')" required="" -->
                    <span class="help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_place"></li>
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

                <div class="para_content">
                  <p>Indem Sie fortfahren, erklären Sie sich mit den <!-- <a href="#"> -->Allgemeinen Geschäftsbedingungen<!-- </a> --> und unserer <!-- <a href="#"> -->Datenschutzerklärung<!-- </a> --> einverstanden. Sie verstehen, dass die Überprüfung von Kontaktdaten, Bestätigungen, Erinnerungen und Feedback zu unserem Kernservice gehören.</p>     
                </div>
                <div class="form-group row">
                    <div class="col-sm-12  col-md-12">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="gdpr" id="gdpr" data-error="Bitte aktivieren Sie dieses Kontrollkästchen, um fortzufahren." required>
                        <label class="form-check-label" for="gdpr">
                           Ich akzeptiere die DatenschutzRichtlinien
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
<script type="text/javascript" src="{{asset('assets/web/js/register.js')}}"></script> 
<script>
  
 function sendOtp() {

    //var patient_id = $("#patient_id").val();
    var first_name = $("#first_name").val();
    var family_name = $("#family_name").val();
    var country_code = $("#format").val();
    var mobile_no = $("#phone").val();

    // console.log(doctor_id);
    // console.log(appointment_type_id);
    // console.log(appointment_date);

    //$("#appointment_date").blur();
    //return false;
    if (first_name != "" && family_name != "" && country_code != "" && mobile_no != "") {
        var action = WEBURL + '/online-appointment/send-register-otp';
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, {
                first_name: first_name,
                family_name: family_name,
                country_code: country_code,
                mobile_no: mobile_no,
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
</script>
@stop