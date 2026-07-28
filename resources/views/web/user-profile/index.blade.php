@extends('web.layout.master')
@section('title', 'User Profile')
@section('content')
<head>
    <style>
       .profile-wrapper+.bottom_footer {
            position: fixed;
            width: 100%;
            bottom: 0;
        }

        .profile-wrapper {
            padding-bottom: 90px !important;
        }
    </style>
</head>
<!-- All Content Section Start -->
  <div class="container py-3 profile-wrapper">
    <div class="row">
      <div class="col-md-12"> 
        <hr class="mb-4">
        <div class="row justify-content-center">
          <div class="col-lg-6 col-md-9 col-sm-9">
            <span class="anchor" id="formUserEdit"></span>
            <div class="card card-outline-secondary form_data">
            @if($validAppointment == 1)
              <div class="card-header personal_head">
                <h3 class="mb-0 text-center">PERSÖNLICHE DATEN</h3>
              </div>
              <div class="card-body">
                <div class="para_content">
                  <p>Um die Bearbeitung Ihres Termins und die Kontaktaufnahme zu erleichtern füllen Sie bitte alle Felder vollständig aus.</p>     
                </div>
                <form name="userProfile" id='userProfile'  action="{{ url('/user-profile/update') }}" data-toggle="validator" autocomplete="off" class="form" role="form">
                    <div class="form-group row">
                    <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_MOBILE_NO') <span class="required">*</span></label>
                    <div class="col-sm-2 col-md-2 col-lg-3 custCountryCode">
                      <div class="form-group">
                        <div class="select-editable">
                            @php
                                if(!in_array($countryCode, $country_codes)){
                                    $country_codes[] = $countryCode;
                                }
                                $selectedCode = old('country_code', $countryCode ?? $country_codes[0] ?? '');
                                $showOther = $selectedCode !== '' && !in_array($selectedCode, $country_codes);
                            @endphp
                          <select name="country_code" class="form-control" id="country_code"  onchange="handleCountrySelect(this)">
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
                              placeholder="@lang('front.TITLE_PATIENT_COUNTRY_CODE')"   
                              required
                              value='{{ $showOther ? $selectedCode : ($selectedCode ?? '') }}'
                              maxlength="5" 
                              pattern="^(\+[1-9][0-9]*|00[1-9][0-9]*)$"
                              data-error="@lang('front.ERR_COUNTRY_CODE_REQUIRED')"
                              data-pattern-error ="@lang('front.ERR_COUNTRY_CODE_WRONG')"  />
                        </div>
                              <!-- pattern="(\+[1-9][0-9]*|0[1-9](?!\d)|00[1-9][0-9]*)" -->

                        <span class="help-block with-errors">
                          <ul class="list-unstyled">
                            <li class="err_format"></li>
                          </ul>
                        </span>
                      </div>
                    </div>
                     
                    <div class="col-sm-6 col-md-7 col-lg-5 custMobileNum">
                      <div class="form-group">
                         <input class="form-control" type="tel" name="mobile_no" value="{{$mobileNo}}" maxlength="15" 
                                        pattern="^(?!0{2})0?[0-9]+$" id="phone" data-pattern-error="@lang('front.ERR_MOBILE_FORMAT')" data-error="@lang('front.ERR_MOBILE_NO_REQUIRED_USER_PROFILE')" required>
                        <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                               <li class="err_mobile_no"></li>
                            </ul>
                        </span>
                      </div>
                    </div>

                  </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_FIRST_NAME') <span class="required">*</span></label>
                            <div class="col-sm-8 col-md-9 col-lg-8 ">
                                <input class="form-control" type="text" id="first_name" name="first_name" value="{{$firstName}}" data-error="@lang('front.ERR_FIRSTNAME_REQUIRED')" required>
                            
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
                            <input class="form-control" type="text" id="family_name" name="family_name" value="{{$family_name}}" data-error="@lang('front.ERR_LASTNAME_REQUIRED_USER_PROFILE')" required>
                            
                            <span class="help-block with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_family_name"></li>
                                </ul>
                            </span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-md-3  col-lg-4 col-form-label form-control-label">@lang('front.TITLE_EMAIL_ADDRESS') <span class="required">*</span></label>
                            <div class="col-sm-8 col-md-9 col-lg-8 ">
                            <input class="form-control" type="email" name="email" value="{{$email}}"  pattern="^[^\s$#%!@]+[^$#%!@]+@[^\s@]+\.[^\s@]+$" data-pattern-error="@lang('front.ERR_EMAIL_FORMAT')" data-error="@lang('front.ERR_EMAIL_ADDRESS_REQUIRED')" required>
                            <span class="help-block with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_email"></li>
                                </ul>
                            </span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class=" col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_CHOOSE_GENDER') <span class="required">*</span></label>
                            <div class="col-sm-8 col-md-9 col-lg-5 ">
                                <select class="form-control" id="gender" size="0" name="gender" data-error="@lang('front.ERR_PATIENT_GENDER_REQUIRED')" required>
                                    <option value="" name="">@lang('front.TITLE_CHOOSE_GENDER')</option>
                                    <option value="M" @if($gender == 'M') selected @endif>M</option>
                                    <option value="W" @if($gender == 'W') selected @endif>W</option>
                                    <option value="D" @if($gender == 'D') selected @endif>D</option>
                                </select>
                                <span class="help-block with-errors">
                                    <ul class="list-unstyled">
                                        <li class="err_gender"></li>
                                    </ul>
                                </span>
                            </div>
                        </div>
                        <!-- # Roshani Added this code #  CR #102-->
                        <div class="form-group row">
                            <label class=" col-sm-4 col-md-3 col-lg-4 col-form-label form-control-label">@lang('front.TITLE_COUNTRY') <span class="required">*</span></label>
                            <div class="col-sm-8 col-md-9 col-lg-5 ">
                                <select 
                                        class="form-control"
                                        name="country"
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_COUNTRY_REQUIRED')" >
                                        <option value="" name="">@lang('admin.TITLE_SELECT_COUNTRY')</option>
                                        <option value="Austria" @if($country == 'Austria') selected="selected" @endif>@lang('admin.TITLE_COUNTRY_AUSTRIA')</option>
                                        <option value="Germany" @if($country == 'Germany') selected="selected" @endif>@lang('admin.TITLE_COUNTRY_GERMANY')</option>
                                        <option value="Switzerland" @if($country == 'Switzerland') selected="selected" @endif>@lang('admin.TITLE_COUNTRY_SWITZERLAND')</option>
                                    </select>
                                <span class="help-block with-errors">
                                    <ul class="list-unstyled">
                                        <li class="err_country"></li>
                                    </ul>
                                </span>
                            </div>
                        </div>
                        <!-- # Roshani Added this code #  CR #102-->

                        <div class="form-group row">
                            <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_PATIENT_ROAD')</label>
                            <div class="col-sm-6 col-md-9 col-lg-8 ">
                            <input class="form-control" type="text" name="road" value="{{$road}}">
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
                            <input class="form-control" type="text" name="street_no" value="{{$streetNo}}">
                            <!--  data-error="@lang('front.ERR_ROAD_REQUIRED')" required -->
                            <span class="help-block with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_street_no"></li>
                                </ul>
                            </span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.TITLE_PATIENT_POSTAL_CODE')<span class="required">*</span></label>
                            <div class="col-sm-8 col-md-9 col-lg-8 ">
                                <input class="form-control"  type="text" 
                                        name="postal_code" 
                                        class="form-control" 
                                        maxlength="5" 
                                        required
                                        data-error="@lang('front.ERR_PATIENT_POSTAL_CODE_REQUIRED_WEB')"
                                        inputmode="numeric" 
                                        pattern="\d{4,5}" 
                                        oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length < 4) this.setCustomValidity('Please enter at least 4 digits'); else this.setCustomValidity('')"
                                        value="{{$postalCode}}"  
                                        > 
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
                            <input class="form-control" type="text" name="place" value="{{$place}}">
                            <!-- data-error="@lang('front.ERR_PLACE_REQUIRED')" required="" -->
                            <span class="help-block with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_place"></li>
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
                                value="{{ $birthDate ? date('d-m-Y', strtotime($birthDate)) : '' }}"
                                data-error="@lang('front.ERR_BIRTH_DATE_REQUIRED')" required  readonly="readonly" style="background-color:white;"
                                >
                            <span class="help-block with-errors">
                                <ul class="list-unstyled">
                                <li class="err_birth_date"></li>
                                </ul>
                            </span>
                            </div> 
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-md-3 col-lg-4  col-form-label form-control-label">@lang('front.USER_PROFILE_TITLE_SOZIAL')</label>
                            <div class="col-sm-8 col-md-9 col-lg-8 ">
                            <input class="form-control" type="text" name="social_security_number" value="{{$socialSecurityNumber}}">
                            <!-- data-error="@lang('front.ERR_PLACE_REQUIRED')" required="" -->
                            <span class="help-block with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_social_security_number"></li>
                                </ul>
                            </span>
                            </div>
                        </div>
                        <input type="hidden" name="patient_id" value="{{ $patientId }}">
                        <input type="hidden" name="appointment_id" value="{{ $appointmentId }}">


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
            @else
                <div class="card-body">
                    <div class="alert alert-danger text-center" role="alert">
                        Der Link ist abgelaufen. Sie können im Moment nicht fortfahren.
                    </div>
                </div>
            @endif
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
<script type="text/javascript" src="{{asset('assets/web/js/user-profile.js?ver=01')}}"></script> 
<!-- jQuery library -->
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> -->

<!-- jQuery Validation plugin -->
<!-- <script src="https://cdn.jsdelivr.net/jquery.validation/1.19.3/jquery.validate.min.js"></script> -->

<script>
    $(function() { 
    $('.ui-datepicker').attr("translate","no").addClass('notranslate').css({ notranslate });
    });
</script>

@stop