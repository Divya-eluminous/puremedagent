@extends('admin.layout.web')
@section('title')
{{ $moduleTitle ?? 'Log In' }}
@endsection
@section('content')

<div class="card"> 
    <div class="card-body login-card-body">
      <p class="login-box-msg">@lang('admin.TITLE_LOGIN_HEADING')</p>

      <form id='loginForm' method="post" action="{{ route('admin.auth.check.login')}}" data-toggle="validator">
        {{ csrf_field() }}
        <div class="form-group  has-feedback">
            <span class="glyphicon glyphicon-envelope form-control-feedback"></span> 
             <div class="input-group mb-1"> 
            <input type="text" 
                class="form-control" 
                name="email" 
                placeholder="@lang('admin.TITLE_EMAIL')" 
                value="{{ $user->email ?? '' }}"
                required
                data-error="@lang('admin.ERR_EMAIL_REQUIRED')" 
            >
            <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
          </div>
            <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                    <li class="err_email" ></li>
                </ul>
            </span>
        </div>

        <div class="form-group has-feedback">
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>  
            <div class="input-group mb-1">
                <input type="password" 
                    class="form-control" 
                    name="password" 
                    placeholder="@lang('admin.TITLE_PASSWORD')" 
                    value="{{ $user->password ?? '' }}"
                    required
                    data-error="@lang('admin.ERR_LOGIN_PASSWORD_REQUIRED')" 
                >
                <div class="input-group-append">
                    <div class="input-group-text">
                      <span class="fas fa-lock"></span>
                    </div>
                </div>
            </div>
            <!-- <p style="color: red;">*Bitte geben sie die Handynummer ohne führende Null oder internationale Vorwahl ein (z.B. 66412345678 statt 0664... oder +43664...)</p> -->

            <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                    <li class="err_password" ></li>
                </ul>
            </span>
        </div>
       <!--  <div class="form-group has-feedback">
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>  
            <div class="input-group mb-1">
                <input type="mobile_no" 
                    class="form-control" 
                    name="mobile_no" 
                    placeholder="@lang('admin.TITLE_MOBILE_NO')" 
                    value="{{ $user->mobile_no ?? '' }}"
                    required
                    data-error="@lang('admin.ERR_MOBILE_NUMBER_REQUIRED')" 
                >
                <div class="input-group-append">
                    <div class="input-group-text">
                      <span class="fa fa-mobile"></span>
                    </div>
                </div>
            </div>

            <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                    <li class="err_password" ></li>
                </ul>
            </span>
        </div> -->
        <div class="row">
            <div class="col-4">
                <div class="form-group has-feedback">
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span> 
                    <div class="input-group mb-1">
                        <div class="select-editable">

                            @php
                                // Ensure +43 always exists
                                if(!in_array('+43', $country_codes ?? [])){
                                    $country_codes = $country_codes ?? [];
                                    array_unshift($country_codes, '+43');
                                }

                                // Default selection logic
                                $selectedCode = old('country_code') ?? '+43';

                                // Check if selected is not in list
                                $showOther = !in_array($selectedCode, $country_codes);
                            @endphp

                            <!-- Country Code Dropdown -->
                            <select 
                                class="form-control my-select"
                                name="country_code"
                                id="country_code"
                                onchange="handleCountrySelect(this)">

                                @foreach($country_codes as $code)
                                    <option value="{{ $code }}" 
                                        {{ !$showOther && $selectedCode == $code ? 'selected' : '' }}>
                                        {{ $code }}
                                    </option>
                                @endforeach

                                <option value="other" {{ $showOther ? 'selected' : '' }}>
                                    Weitere
                                </option>
                            </select>

                            <!-- Manual Input Field -->
                            <input  
                                type="text" 
                                name="format"
                                id="format"  
                                class="form-control"
                                placeholder="@lang('admin.TITLE_PATIENT_COUNTRY_CODE')"   
                                required
                                value="{{ $showOther ? $selectedCode : $selectedCode }}"
                                maxlength="5" 
                                pattern="^(\+[1-9][0-9]*|00[1-9][0-9]*)$"
                                data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"
                                data-pattern-error="@lang('admin.ERR_COUNTRY_CODE_WRONG')"  
                            />

                        </div>

                        <!-- Error Message -->
                        <span class="help-block invalid-feedback with-errors">
                            <ul class="list-unstyled">
                                <li class="err_format"></li>
                            </ul>
                        </span>
                    </div>

                    <!-- <span class="help-block invalid-feedback with-errors">
                        <ul class="list-unstyled">
                            <li class="err_country_code" ></li>
                        </ul>
                    </span> -->
                </div>
            </div>
          <!-- /.col -->
          <div class="col-8">
            <div class="form-group has-feedback">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>  
                <div class="input-group mb-1">
                    <input type="text" 
                        class="form-control" 
                        name="phone" 
                        placeholder="@lang('admin.TITLE_MOBILE_NO')" 
                        value="{{ $user->mobile_number ?? '' }}"
                        required
                        maxlength="15"
                        pattern="^(?!0{2})0?[0-9]+$"
                        data-error="@lang('admin.ERR_MOBILE_NUMBER_REQUIRED')" 
                        data-pattern-error="@lang('admin.ERR_MOBILE_NO_INVALID')"
                    >
                    <div class="input-group-append">
                        <div class="input-group-text">
                          <span class="fa fa-mobile"></span>
                        </div>
                    </div>
                </div>

                <span class="help-block invalid-feedback with-errors">
                    <ul class="list-unstyled">
                        <li class="err_phone" ></li>
                    </ul>
                </span>
            </div>    
          </div>
          <!-- /.col -->
        </div>
        <div class="form-group"> 
            <select 
                name="language" 
                id="language" 
                required
                data-error="@lang('admin.ERR_LANGUAGE_REQUIRED')"
                class="form-control" 
                >
                <option value="">@lang('admin.TITLE_SELECT_LANGUAGE')</option>
                <option value="de" @if(app()->getlocale()=='de') selected @endif>@lang('admin.TITLE_LANGUAGE_GERMAN')</option>
                <option value="en" @if(app()->getlocale()=='en') selected @endif>@lang('admin.TITLE_LANGUAGE_ENGLISH')</option>
            </select> 
            <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                    <li class="err_language"></li>
                </ul>
            </span>
        </div>
        <!-- <div class="form-group"> 
            <select 
                name="ordination" 
                id="ordination"
                class="form-control" 
                >
                <option value="">Select Ordination</option>
                @foreach($ordination as $key=>$value)
                <option value="{{ $value->id }}">{{ $value->name }}</option>
                @endforeach               
            </select> 
            <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                    <li class="err_ordination"></li>
                </ul>
            </span>
        </div> -->

        <div class="row">
          <div class="col-8">
            <a href="{{ route('admin.auth.forgot.password') }}">@lang('admin.TITLE_FORGOT_PASSWORD')</a><br>
            <!-- <div class="icheck-primary">
               <input type="checkbox" id="remember"
                                class="" 
                                name="remember" 
                                @if(!empty($user))
                                    checked
                                @endif 
                            >
              <label for="remember">
                Remember Me
              </label>
            </div> -->
          </div>
          <!-- /.col -->
          <div class="col-4">
           <button type="submit" id="btnLogin" value="Login" class="btn btn-primary btn-block btn-flat">@lang('admin.TITLE_BUTTON_LOGIN')</button>
          </div>
          <!-- /.col -->
        </div>

        
        
        </form>
       

    <!-- /.social-auth-links -->
<!-- <p class="mb-1">
    <a href="{{ route('admin.auth.forgot.password') }}">I forgot my password</a><br>
    </p> -->
    <!-- <a href="register.html" class="text-center">Register a new membership</a> -->

  </div>
  </div>
  <!-- /.login-box-body -->

@endsection
@section('scripts')
<script type="text/javascript" src="{{ asset('assets/admin/js/auth/login.js') }}"></script>
@endsection
