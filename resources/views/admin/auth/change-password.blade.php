@extends('admin.layout.web')
@section('title')
{{ $moduleAction ?? 'Forgot Password' }}
@endsection
@section('content')

    <div class="card">
        <div class="card-body login-card-body">
          <p class="login-box-msg">@lang('admin.TITLE_FORGOT_PASSWORD_HEADING')</p>

         <form id="forgotPasswordForm" action="{{ route($modulePath) }}" data-toggle="validator">
            {{ csrf_field() }}
            <div class="form-group">
                <div class="input-group mb-1">
                  <input type="email" class="form-control" placeholder="@lang('admin.TITLE_EMAIL')" name="email" required  data-error="@lang('admin.ERR_EMAIL_REQUIRED')">
                  
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
            <div class="row">
              <div class="col-12">
                <button type="submit" class="btn btn-primary btn-block">@lang('admin.TITLE_FORGOT_PASSWORD_BUTTON')</button>
              </div>
              <!-- /.col -->
            </div>
          </form>

          <!-- <p class="mt-3 mb-1">
            <a href="login.html">Login</a>
          </p>
          <p class="mb-0">
            <a href="register.html" class="text-center">Register a new membership</a>
          </p> -->
        </div>
    <!-- /.login-card-body -->
  </div>

</div>

<!-- /.login-box -->
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/admin/js/auth/forgot-password.js') }}"></script>
@endsection