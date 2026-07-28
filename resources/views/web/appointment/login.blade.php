@extends('web.layout.master')
@section('title', 'Login')
<style type="text/css">
  .padding-bottom {    padding-bottom: 100px !important;}
   .container+.bottom_footer {
            position: fixed;
            width: 100%;
            bottom: 0;
        }

   #resetbtn{
    opacity: .65;
    margin-left: 50px;
   }     

        
</style>
@section('content')
<!-- All Content Section Start -->

  <div class="container py-3 padding-bottom" style="padding-bottom:100px !important;">
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
             <!--  <div class="card-body">
                <p>Wenn Sie die Online Terminvereinbarung zum ersten Mal nutzen, benötigen wir zur Bearbeitung Ihres Termins und zur Kontaktaufnahme Ihre persönlichen Daten (Vor- und Nachnamen, Kontaktmöglichkeit). 
                 <a href="{{ url('/online-appointment/register') }}">Ich buche zum ersten Mal Online.</a> -->
                  <!-- <a href="{{ url('/online-appointment/booking') }}">Ich buche zum ersten Mal Online.</a> 
                </p>
              </div> -->
              <div class="card-body">
                <div class="para_content">
                  <p></p>     
                </div>
                  <form name="userLogin" id='userLogin'  action="{{ url('/online-appointment/app-booking') }}" data-toggle="validator" autocomplete="off" class="form" role="form">

                  <input type="hidden" name="hidden_doc_id" id="hidden_doc_id" value="{{$doctorId}}">  

                    <!-----added below line on 18-dec-23-(29-feb-24)--------------->
                  <input type="hidden" name="hidden_service_id" id="hidden_service_id" value="{{$service_id}}">  
                  
                  <!-----added below line by roshani-for web booking flow-------------->
                  <input type="hidden" name="birth_date_hidden" id="birth_date_hidden">
                  <input type="hidden" name="format_hidden" id="format_hidden">
                  <input type="hidden" name="country_code_hidden" id="country_code_hidden">
                  <input type="hidden" name="mobile_no_hidden" id="mobile_no_hidden">
                  <!-----added below line by roshani--------------->


                  <!-----start--added below code for web otp CR------------->
                  <!-- <input type="text" name="has_email" id="has_email" > -->
                  <input type="hidden" name="isLogin" id="isLogin" >
                  <input type="hidden" name="patient_email" id="patient_email" value="">

                  
                  <input type="hidden" name="shown_otp_field" id="shown_otp_field" value="0">

                  <!-----start--added below code for web otp CR--on 27-may-24----------->
                  <input type="hidden" name="dbEmailExists" id="dbEmailExists" value="">
                  <!----end---added below code for web otp CR-on 27-may-24------------->

                  <!----end---added below code for web otp CR------------->

                  <!-- Roshani added the below code for CR #230 -->
                  <input type="hidden" name="birth_date" id="birth_date">
                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">
                        @lang('front.TITLE_PATIENT_BIRTH_DATE') <span class="required">*</span>
                    </label>

                    <!-- Day Selection -->
                    <div class="col-md-2">
                        <input type="number" class="form-control" id="day" name="day" placeholder="@lang('front.TITLE_DAY')" min="1" max="31">
                        <span class="error-message err_day text-danger"></span>
                    </div>

                    <!-- Month Selection -->
                    <div class="col-md-3">
                      <select class="form-control" id="month" name="month">
                          <option value="" disabled selected>@lang('front.TITLE_MONTH')</option>
                          <option value="1">Januar</option>
                          <option value="2">Februar</option>
                          <option value="3">März</option>
                          <option value="4">April</option>
                          <option value="5">Mai</option>
                          <option value="6">Juni</option>
                          <option value="7">Juli</option>
                          <option value="8">August</option>
                          <option value="9">September</option>
                          <option value="10">Oktober</option>
                          <option value="11">November</option>
                          <option value="12">Dezember</option>
                      </select>
                      <span class="error-message err_month text-danger"></span>
                  </div>

                    <!-- Year Selection -->
                    <div class="col-md-3">
                        <!-- <label for="year" class="form-label">@lang('front.TITLE_YEAR')</label> -->
                        <select class="form-control" name="year" id="year" required>
                          <option value="" selected disabled>@lang('front.TITLE_YEAR')</option>
                          <!-- Loop for years -->
                          <script>
                            const currentYear = new Date().getFullYear();
                            const compaireYear = currentYear - 100;
                            for (let i = currentYear; i >= compaireYear; i--) {
                              document.write(`<option value="${i}">${i}</option>`);
                            }
                          </script>
                        </select>
                    </div>
                </div>
                <div class="row">
                  <div class="col-md-4"></div>
                <div class="col-md-8">
                  <span class="help-block with-errors">
                    <ul class="list-unstyled">
                    <li class="err_birth_date"></li>
                    </ul>
                  </span>
                </div>
                </div>         
                 <!-- Roshani hidden the code for #230 -->
                  <!-- <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label"> 
                    @lang('front.TITLE_PATIENT_BIRTH_DATE') <span class="required">*</span> </label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                        <input 
                        type="text" 
                        name="birth_date" 
                        class="form-control"
                        id="birth_date"
                        required  
                        maxlength="250"                      
                        data-error="@lang('front.ERR_BIRTH_DATE_REQUIRED')"  readonly="readonly" style="background-color:white;"
                        onchange="hideErrorMessage()"
                        >
                      <span class="help-block with-errors">
                        <ul class="list-unstyled">
                        <li class="err_birth_date"></li>
                        </ul>
                      </span>
                    </div> 
                  </div> -->

                  <!-- Roshani added the below code for CR #230 -->

                  <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_MOBILE_NO') <span class="required">*</span></label>
                    <div class="col-sm-2 col-md-2 col-lg-3 custCountryCode">
                      <div class="form-group">
                        <div class="select-editable">
                           <!-- onchange="this.nextElementSibling.value=this.value" -->
                          <select name="country_code" class="form-control" id="country_code"  onchange="handleCountrySelect(this)">
                                        
                            @php
                                $selectedCode = old('country_code', $country_codes[0] ?? '');
                                $showOther = $selectedCode !== '' && !in_array($selectedCode, $country_codes);
                            @endphp
                            @foreach($country_codes as $code)
                                <option value="{{ $code }}" {{ !$showOther && $selectedCode == $code ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                            <option value="other" {{ $showOther ? 'selected' : '' }}>Other</option>
                          </select>
                          <input  
                              type="text" 
                              name="format"
                              id="format" 
                              required 
                              value='+43'
                              class="form-control"
                              placeholder="@lang('front.TITLE_PATIENT_COUNTRY_CODE')"   
                              maxlength="5" 
                              autocomplete="off"
                              pattern="^(\+[1-9][0-9]*|00[1-9][0-9]*)$"
                              data-error="@lang('front.ERR_COUNTRY_CODE_REQUIRED')"
                              data-pattern-error ="@lang('front.ERR_COUNTRY_CODE_WRONG')"  />
                                                            <!-- pattern="(\+[0-9]+|0[0-9]+|00[0-9]+)" -->
                              <!-- value='+43'  pattern="(\+[1-9][0-9]*|0[1-9][0-9]*|00[1-9][0-9]*)"-->

                        </div>
                        <span class="help-block with-errors">
                          <ul class="list-unstyled">
                            <li class="err_format"></li>
                          </ul>
                        </span>
                      </div>
                    </div>
                     
                    <div class="col-sm-6 col-md-7 col-lg-5 custMobileNum">
                      <div class="form-group">
                        <input class="form-control" maxlength="12" type="tel" required  id="mobile_no" name="mobile_no" data-error="@lang('front.ERR_MOBILE_NO_REQUIRED')"  onkeypress="return isNumber(event);" pattern="^(?!0{2})0?[0-9]+$"
                                        data-pattern-error="@lang('front.ERR_MOBILE_NO_INVALID')">
                        <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                               <li class="err_mobile_no"></li>
                            </ul>
                        </span>
                      </div>
                    </div>

                  </div>

                  <div class="form-group row" id="email_div" style="display:none">
                    <label class="col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label"> 
                    @lang('front.TITLE_EMAIL_ADDRESS') <span class="required">*</span> </label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                        <input 
                        type="email" 
                        name="email" 
                        class="form-control"
                        id="email"  
                        data-error="@lang('front.ERR_VALID_EMAIL_ADDRESS')" 
                        >
                      <!-- required
                      data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"  -->
                      <span class="help-block with-errors">
                        <ul class="list-unstyled">
                        <li class="err_email_login"></li>
                        </ul>
                      </span>
                    </div> 
                  </div>




                  <div class="form-group row" id="password_div" style="display: none;">
                    <label class="col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label"> 
                    @lang('front.TITLE_PASSWORD') <span class="required">*</span> </label>
                          <!-- /***************** Roshani added this for CR #229 *****************/ -->
                      <div class="col-sm-8 col-md-9 col-lg-8 input-group mb-3" id="show_hide_password">
                        <div class="input-group mb-3">
                          <input 
                          type="password" 
                          name="password" 
                          class="form-control"
                          id="password"  
                          maxlength="250"                      
                          autocomplete="new-password"
                          style="background-color: #fff;"
                          >
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
                      <button type="button" class="btn fc-button-primary" style="color: blue;" data-toggle="modal" data-target="#forgetPassword">
                    @lang('front.TITLE_FORGOT_PASSWORD')?
                    </button>
                   
                    <!--   <button type="button" class="btn fc-button-primary" data-toggle="modal" data-target="#forgetPassword" style="display: none">
                    @lang('front.TITLE_FORGOT_PASSWORD')
                    </button> -->
                  </div>
 
                   <div class="col-lg-12 text-center card-footer" id="otp_button" style="display:none">
                        <button type="button" class="btn btn-success" id="sendPatientOtp" 
                        onclick="sendOtpPatient()" disabled>@lang('front.TITLE_WEB_OTP')</button>
                    </div>
                    <br/>

                   <div class="form-group row" id="otp_field" style="display:none">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_WEB_OTP_CODE') <span class="required">*</span></label>
                    <div class="col-sm-8 col-md-9 col-lg-8 ">
                      <input class="form-control" type="text" value="" id="otp_code" name="otp_code" data-error="@lang('front.ERR_OTP_REQUIRED')" maxlength="4"  onkeypress="return isNumber(event);">
                      <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                               <li class="err_otp_code"></li>
                            </ul>
                        </span>
                    </div>
                  </div>

                 

                  <input type="hidden" name="hid_pass_check" id="hid_pass_check" value="">
                    <div class="col-lg-12 text-center card-footer">
                        <!-- <button type="button" class="btn btn-success" onclick="sendOtp()">Bestätigen</button> -->
                        <!-- <button type="button" class="btn btn-success">Bestätigen</button> -->

                         <button type="button" class="btn btn-success" id="resetbtn" onclick="return resetFrom()" >Zurücksetzen</button>
                         
                        <button type="submit" class="btn btn-success login-submit-btn" id="login-submit-btn">Bestätigen</button>

                       

                    </div>
                  </div>


                </form>

                  <!-- Model for forget password -->
                     <!--Roshani Added model here for forget password  on 25 - 03- 2024-->
                  <div class="modal fade" id="forgetPassword" style="position:fixed;">
                    <div class="modal-dialog modal-dialog-scrollable">
                    <form id="frmforgetPasswordWeb" role="form" data-toggle="validator" action="{{ url('/online-appointment/forgotPasswordWeb') }}">
                      <div class="modal-content">
                          <div class="modal-header">
                            <p class="card-title">Haben Sie Ihr Passwort vergessen? Hier können Sie einfach ein neues Passwort anfordern.</p>
                            <button type="button" class="close addBtnClosePopup" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>                
                          </div>               
                          <div class="modal-body appointment-loader">
                            <div class="form-grou  p">
                              <div class="input-group mb-1">

                                <input type="email" class="form-control"  placeholder="@lang('admin.TITLE_EMAIL')" name="email" id="emailInput" readonly="">
                                
                                <div class="input-group-append">
                                  <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                  </div>
                                </div>

                              </div>
                              <span class="help-block invalid-feedback with-errors">
                                  <ul class="list-unstyled">
                                      <li class="err_email"></li>
                                  </ul>
                              </span>
                            </div>

                              <input type="hidden" class="form-control" name="patient_id" id="patient_id" readonly="">

                          </div>               
                          <div class="modal-footer">
                              <button type="submit" id="s_button" class="btn btn-primary btn-block">@lang('front.TITLE_FORGOT_PASSWORD_BUTTON')</button>
                         </div>
                      </div>
                      </form>
                      <!-- /.modal-content -->
                   </div>
                    <!-- /.modal-dialog -->
                  </div>
                    <!--Roshani Added model here for forget password  on 25 - 03- 2024-->
                  <!-- Model for forget password -->


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

<link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert/sweetalert.css') }}">



<script src="{{ asset('assets/admin-lte/plugins/daterangepicker/daterangepicker.js') }}"></script> 
 <script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{asset('assets/web/js/login.js?ver=0.08')}}"></script>

<script src="{{ asset('assets/plugins/sweetalert/sweetalert.js') }}"></script>

<script>




/************* Roshani added this for autofill password field 231 **************/
$(document).ready(function() {
  // Function to handle clearing fields
  function handleFieldClear(selector, dependentSelector) {
    let isUserTyping = false; // Flag to determine if the user is typing

    // Clear the input field after a short delay
    setTimeout(function() {
      $(selector).val(''); // Clear the field after a delay
    }, 500);

    // Monitor input events
    $(selector).on('input', function() {
      if (isUserTyping) {
        return; // If the user is typing, do nothing
      }
      $(this).val(''); // Clear the field if not typing
    });

    // Set flag when the user starts typing
    $(selector).on('focus', function() {
      isUserTyping = true; // User is typing
    });

    // Reset flag when the user leaves the field
    $(selector).on('blur', function() {
      isUserTyping = false; // Reset when the user leaves the field
    });

    // Additional logic: Clear the dependent field when this field changes (handle autofill case)
    if (dependentSelector) {
      $(selector).on('input', function() {
        $(dependentSelector).val(''); // Clear the dependent field
      });
    }
  }

  // Apply the same logic to both password and mobile number fields
  handleFieldClear('#password');
  handleFieldClear('#mobile_no', '#password'); // Clear the password field if mobile number changes
});
/************* Roshani added this for autofill password field 231 **************/


$(function() { 
  $('.ui-datepicker').attr("translate","no").addClass('notranslate').css({ notranslate });
});
</script>

<script type="text/javascript">
function isNumber(e){
    e = e || window.event;
    var charCode = e.which ? e.which : e.keyCode;
    return /\d/.test(String.fromCharCode(charCode));
}


function resetFrom(){
  
        swal({
            title: "Bist du sicher",
            text: "Sie möchten das Formular zurücksetzen?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "JA",
            cancelButtonText: "NEIN",
            closeOnConfirm: false,
            closeOnCancel: true,

        },
        function(isConfirm) {
            if (isConfirm)
            {
                $('.showSweetAlert').LoadingOverlay("show", {
                    background: "rgba(165, 190, 100, 0.4)",
                });
                window.location.reload();
              
            }           
        });
    
   
}//function
</script>
<script>
    const dayInput = document.getElementById('day');

    // Prevent typing values greater than 31 or less than 1
    dayInput.addEventListener('input', function () {
        let value = parseInt(this.value, 10);
        
        // Ensure the value is between 1 and 31
        if (value > 31) {
            this.value = 31;
        } else if (value < 1) {
            this.value = 1;
        }
    });
</script>
@stop