@extends('admin.layout.web')

@section('title')
    {{ $moduleTitle ?? 'Reset Password' }}
@endsection

@section('styles')

@endsection

@section('content')
<div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">@lang('admin.TITLE_RESET_PASSWORD_HEADING')</p>

        <form id='resetPasswordForm' action="{{ route($modulePath,[$token]) }}" data-toggle="validator">

            {{ csrf_field() }}

            <div class="card-body d-flex flex-column">

                <div class="form-group">
                    <input type="text" 
                        class="form-control" 
                        name="username"
                        value="{{ $email ?? '' }}" 
                        disabled="true"
                        required
                        data-error="@lang('admin.ERR_EMAIL_REQUIRED')" 
                    >
                    <span class=" help-block with-errors">
                        <ul class="list-unstyled">
                            <li class="err_username"></li>
                        </ul>
                    </span>
                </div>

                <div class="form-group">
                    <div class="input-group mb-1">
                      <input type="password" class="form-control" placeholder="@lang('admin.TITLE_PASSWORD')" required  name="password"
                            data-error="@lang('admin.ERR_PASSWORD_REQUIRED')" >
                      <div class="input-group-append">
                        <div class="input-group-text">
                          <span class="fas fa-lock"></span>
                        </div>
                      </div>
                    </div>
                    <span class="help-block invalid-feedback  with-errors">
                        <ul class="list-unstyled">
                            <li class="err_password"></li>
                        </ul>
                    </span>
                </div>

                <div class="form-group">
                    <div class="input-group mb-1">
                      <input type="password" class="form-control" placeholder="@lang('admin.TITLE_RESET_PASSWORD_CONFIRM_PASSWORD')"  name="confirm_password" 
                        required
                        data-error="@lang('admin.ERR_RESET_PASSWORD_CONFIRM_PASSWORD')" >
                      <div class="input-group-append">
                        <div class="input-group-text">
                          <span class="fas fa-lock"></span>
                        </div>
                      </div>
                    </div>
                    <span class="help-block invalid-feedback with-errors">
                        <ul class="list-unstyled">
                            <li class="err_confirm_password"></li>
                        </ul>
                    </span>
                </div>

                <div class="form-group text-center mt-1">
                    <div class="row">
                      <div class="col-12">
                         <button type="submit" class="btn btn-primary btn-block" >@lang('admin.TITLE_RESET_PASSWORD_BUTTON')</button>
                      </div>
                      <!-- /.col -->
                    </div>
                   
                </div>

            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ url('assets/admin/js/auth/reset-password.js') }}"></script>
@endsection