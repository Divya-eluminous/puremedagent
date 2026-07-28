</div>
<!-- /.login-box -->
<!-- jQuery -->
<script src="{{ asset('assets/admin-lte/plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('assets/admin-lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- iCheck -->
<script src="{{ asset('assets/plugins/iCheck/icheck.min.js') }}"></script>
<script>
  $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%' /* optional */
    });
  });
</script>

<script type="text/javascript">
</script>
<script src="{{ asset('assets/common/js/validator.min.js') }}"></script>

<script src="{{ asset('assets/plugins/lodingoverlay/loadingoverlay.min.js') }}"></script>
<script src="{{ asset('assets/plugins/axios/axios.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.options.js') }}"></script>
<script src="{{ asset('assets/plugins/sweetalert/sweetalert.js') }}"></script>
<script src="{{ asset('assets/admin/js/users/model.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<link rel="stylesheet" href="{{ asset('assets/admin/css/jquery-ui.css') }}">  
<script type="text/javascript" src="{{ asset('assets/admin/js/jquery-ui.js') }}"></script>  
<script type="text/javascript" src="{{ asset('assets/admin/js/jquery.ui.datepicker-de.js') }}"></script>
@yield('scripts')
</body>
</html>