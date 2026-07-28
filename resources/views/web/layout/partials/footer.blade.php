<!-- <footer class="black_11d_bg texture_banner banner">
    <div class="container text-white">
        <div class="row">
            
             <div class="col-xl-4 col-lg-3 col-md-3 col-sm-4">
                <div class="dr_add common">
                    <?php 
                    if(!empty(Config('ordination_id')))
                    {
                    ?>
                        <h6><?php echo ucfirst(config('ordination_name'));?></h6>
                    <?php 
                    }
                    else{
                    ?>
                        <h6>PURE GYN ADRESSDATEN</h6> 
                    <?php 
                    }
                    ?>
                    <div class="menu_wrap">
                        <ul class="menu">
                            <li>Dr. Gauff & Dr. Manurung</li>
                            <li>Fachärzte für Gynäkologie</li>
                            <li>und Geburtshilfe</li>
                            <li>Hormayrgasse 48</li>
                            <li>1170 Wien</li>
                        </ul>
                    </div>
                </div>
            </div>
             <div class="col-xl-4 col-lg-3 col-md-3 col-sm-4">
                <div class="site-map common">
                    <h6>ORDINATIONSZEITEN</h6>
                    <div class="menu_wrap">
                        <ul class="menu">
                            <li>MO: 14-17 Uhr</li>
                            <li>DI: 9-14 und 15-17 Uhr</li>
                            <li>MI: 9-14 und 15-20 Uhr</li>
                            <li>DO: 9-14 Uhr</li>
                            <li>FR: 9-14 Uhr</li>
                        </ul>
                    </div>
                </div>
            </div>
             <div class="col-xl-4 col-lg-5 col-md-5 col-sm-8">
                <div class="get-in-touch common">
                    <h6>KONTAKTDATEN</h6>
                    <ul class="det_menu">
                        <li class="">
                            <span class="top_call">TEL:</span><a href="tel:014842000">01-484 2000</a>                        </li>
                        <li class="">
                            <span>MAIL:</span><a href="mailto:office@puregyn.at">office@puregyn.at</a>                        </li>

                        <li class="">
                            <span>FAQ:</span><a href="#">hier weiterlesen</a>                        </li>
                        
                    </ul>              </div>
        </div>
    </div>
</footer> -->
<div class="bottom_footer">
  <div class="container">
    <div class="row">
      <div class="col-lg-8">
        <div class="term_privacy_wrap">
          <ul>
            <li><strong>@lang('admin.TITLE_SITE_COPYRIGHT') &copy; 2019-{{ date("Y",time()) }} <a href="{{ url('/admin') }}">@lang('admin.TITLE_SITE_PUREMADE')</a>.</strong> @lang('admin.TITLE_SITE_RIGHTS')</li>
            <!-- <li>© Dr. Gauff & Dr. Manurung, {{ date('Y') }}</li> -->
            <!-- <li><a href="#" target="_blank">Privacy Policy</a></li>
            <li><a href="#" target="_blank">Terms & Conditions</a></li> -->
          </ul>
        </div>
      </div>
      <!-- <div class="col-lg-4 d-flex align-items-center justify-content-lg-end">
        <div class="term_privacy_wrap">
          <ul class="d-flex">
            <li><a href="#" >FAQ</a></li>
            <li><a href="#" >LAGEPLAN</a></li>
            <li><a href="#" >IMPRESSUM</a></li>
            <li><a href="#" >DATENSCHUTZ</a></li>
          </ul>
        </div>
      </div> -->
    </div>
  </div>
</div>
    <!-- BEGIN FOOTER -->
<!-- <div class="page-footer">
   <footer class="main-footer" style="text-align: center;">
   
    <strong>@lang('admin.TITLE_SITE_COPYRIGHT') &copy; 2019-{{ date("Y",time()) }} <a href="#">@lang('admin.TITLE_SITE_BEGIN')@lang('admin.TITLE_SITE_END')</a>.</strong> @lang('admin.TITLE_SITE_RIGHTS')
  </footer>
</div> -->
<!-- /.login-box -->
<!-- jQuery -->
<script src="{{ asset('assets/admin-lte/plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('assets/admin-lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('assets/admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- datepicker -->
<!-- <script src="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script> -->
<script src="{{ asset('assets/common/js/validator.min.js') }}"></script>
<script src="{{ asset('assets/plugins/lodingoverlay/loadingoverlay.min.js') }}"></script>
<script src="{{ asset('assets/plugins/axios/axios.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.options.js') }}"></script>
<link rel="stylesheet" href="{{ asset('assets/admin/css/jquery-ui.css') }}">
<script>
    const WEBURL = $('meta[name="web-path"]').attr('content');
    const CSRFTOKEN = document.querySelector("meta[name=csrf-token]").content
    axios.defaults.headers.common['X-CSRF-Token'] = CSRFTOKEN;

    // Delete Account - open confirmation popup
    $(document).on('click', '#deleteAccountBtn', function () {
        $('#deleteAccountModal').modal('show');
    });

    // Delete Account - confirmed (OK): cancel all future appointments
    $(document).on('click', '#confirmDeleteAccountBtn', function () {
        $('body').LoadingOverlay("show", { background: "rgba(165, 190, 100, 0.4)" });
        axios.post(WEBURL + '/online-appointment/delete-account')
            .then(response => {
                const resp = response.data;
                $('body').LoadingOverlay("hide");
                $('#deleteAccountModal').modal('hide');
                if (resp.status == 'success') {
                    if (resp.msg) { toastr.success(resp.msg); }
                    if (resp.url) { window.location.href = resp.url; }
                } else {
                    toastr.error(resp.msg);
                }
            })
            .catch(error => {
                $('body').LoadingOverlay("hide");
            });
    });

  // $(function () {
  //   $('input').iCheck({
  //     checkboxClass: 'icheckbox_square-blue',
  //     radioClass: 'iradio_square-blue',
  //     increaseArea: '20%' /* optional */
  //   });
  // });
</script>



@yield('scripts')
</body>
</html>