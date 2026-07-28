@extends('admin.layout.master')

@section('title')
    {{ $moduleTitle ?? 'User Login' }}
@endsection

@section('styles')

@endsection

@section('content')
                @php
                    $error_msg = "";
               @endphp
            @if(session()->has('message'))
               @php
                    $error_msg = session()->get('message');
               @endphp
            @endif

    <div class="d-flex pb-5">
    <div class="login-wrapper">
        <div class="card border-0 shadow">


            <h1 class="title blue-border-bottom">
                {{ $moduleAction }}
            </h1>

            <form id='loginForm' action="" data-toggle="validator">
                {{ csrf_field() }}
                <div class="card-body d-flex flex-column">

                    <div class="form-group">
                        <label for="usr" class="theme-blue bold">@lang('admin.TITLE_USERNAME')</label>
                        <input type="text" 
                            class="form-control" 
                            name="username" 
                            value="{{ $user->username ?? '' }}"
                            required
                            data-error="@lang('admin.ERR_USERNAME_REQUIRED')" 
                        >
                        <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                                <li class="err_username" ></li>
                            </ul>
                        </span>
                    </div>

                    <div class="form-group">
                        <label for="pwd" class="theme-blue bold">@lang('admin.TITLE_PASSWORD')</label>
                        <input type="password" 
                            class="form-control"
                            name="password" 
                            value="{{ $user->password ?? '' }}"
                            required
                            data-error="@lang('admin.ERR_PASSWORD_REQUIRED')" 
                        >
                        <span class="help-block  with-errors">
                            <ul class="list-unstyled">
                                <li class="err_password"></li>
                            </ul>
                        </span>
                    </div>

                    <div class="form-group d-flex align-items-center">
                        <!-- <label class="checkbox-container theme-blue">
                            <input type="checkbox"
                                class="form-control" 
                                name="remember" 
                                @if(!empty($user))
                                    checked
                                @endif 
                            >
                            <span class="checkmark"></span>
                            @lang('admin.TITLE_REMEMBER_ME')
                        </label> -->
                        <a href="{{ route('admin.auth.forgot.password') }}"
                            class="bold theme-green text-underline ml-auto">@lang('admin.TITLE_FORGOT_PASSWORD')</a>
                    </div>

                    <div class="form-group text-center mt-3">
                        <button type="submit" id="btnLogin" value="Login" class="blue-btn">@lang('admin.BUTTON_LOGIN')</button>
                    </div>
                </div>
            </form>

        </div>
    </div>
    </div>
@endsection

@section('scripts')
        

<script type="text/javascript">
    var error_msg = "{{ $error_msg }}";
    if(error_msg){
       toastr.error(error_msg);
    }
    //var company_id = "{{ \Request::segment(3) }}";
</script>
    <script type="text/javascript" src="{{ url('assets/admin/js/auth/login.js') }}"></script>
@endsection