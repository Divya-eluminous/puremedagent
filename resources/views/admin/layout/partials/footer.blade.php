
   <!-- /.content -->
</div>
<!-- /.content-wrapper -->
  <footer class="main-footer">
    <!-- <div class="pull-right hidden-xs">
      <b>Version</b> 2.4.18
    </div> -->
    <strong>@lang('admin.TITLE_SITE_COPYRIGHT') &copy; 2019-{{ date("Y",time()) }} <a href="{{ url('/admin') }}">@lang('admin.TITLE_SITE_PUREMADE')</a>.</strong> @lang('admin.TITLE_SITE_RIGHTS')
    
  </footer>
<?php 
  $is_updated = '1';
  //dd($is_updated);
?>
</div>  
@section('models') 
@include('admin.activity-logs.activity-action-details-model')
@include('admin.users.change-password-model')
@show
<!-- ./wrapper --> 
<!-- jQuery -->
<script src="{{ asset('assets/admin-lte/plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('assets/admin-lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{ asset('assets/admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- Morris.js charts -->
<!-- <script src="{{ asset('assets/adminLte/bower_components/raphael/raphael.min.js') }}"></script>
<script src="{{ asset('assets/adminLte/bower_components/morris.js/morris.min.js') }}"></script> -->
<!-- Sparkline -->
<script src="{{ asset('assets/admin-lte/plugins/sparklines/sparkline.js') }}"></script>
<!-- jvectormap -->
<script src="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js') }}"></script>
<!-- jQuery Knob Chart -->
<script src="{{ asset('assets/admin-lte/plugins/jquery-knob/jquery.knob.min.js') }}"></script>
<!-- daterangepicker -->
<script src="{{ asset('assets/admin-lte/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/daterangepicker/daterangepicker.js') }}"></script> 
<!-- datepicker -->
<script src="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<!-- Bootstrap WYSIHTML5 -->
<script src="{{ asset('assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js') }}"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="{{ asset('assets/admin-lte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
<!-- Slimscroll -->
<!-- <script src="{{ asset('assets/adminLte/bower_components/jquery-slimscroll/jquery.slimscroll.min.js') }}"></script> -->
<!-- FastClick -->
<!-- <script src="{{ asset('assets/adminLte/bower_components/fastclick/lib/fastclick.js') }}"></script> -->
<script src="{{ asset('assets/admin-lte/plugins/select2/js/select2.full.min.js') }}"></script>
<!-- overlayScrollbars -->
<script src="{{ asset('assets/admin-lte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('assets/admin-lte/dist/js/adminlte.js') }}"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<!-- <script src="{{ asset('assets/adminLte/dist/js/pages/dashboard.js') }}"></script>
 --><!-- AdminLTE for demo purposes -->
<!-- <script src="{{ asset('assets/adminLte/dist/js/demo.js') }}"></script> -->

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
<script src="{{ asset('assets/plugins/ckeditor/ckeditor.js') }}"></script>
<!-- <script src="https://cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script> -->
 <script type="text/javascript">
  const ADMINURL = $('meta[name="admin-path"]').attr('content');
  const BASEURL = $('meta[name="base-path"]').attr('content');
  const CSRFTOKEN = document.querySelector("meta[name=csrf-token]").content
  axios.defaults.headers.common['X-CSRF-Token'] = CSRFTOKEN; 
  var deleteContent = {};
  deleteContent.title = "{{ __('admin.TITLE_DELETE_SURE') }}";
  deleteContent.text  = "{{ __('admin.TITLE_DELETE_QUESTION') }}";
  deleteContent.other_text  = "{{ __('admin.TITLE_OTHER_TEXT') }}";
  deleteContent.confirm = "{{ __('admin.TITLE_DELETE_BUTTON') }}";
  deleteContent.other_confirm = "{{ __('admin.TITLE_OK_BUTTON') }}";
  deleteContent.cancel = "{{ __('admin.TITLE_CANCEL_BUTTON') }}";
  deleteContent.textnew  = "{{ __('admin.TITLE_DELETE_QUESTION2') }}";
  var ON = "{{ __('admin.TITLE_ON') }}"; 
  var OFF = "{{ __('admin.TITLE_OFF') }}";

  var PAGE_SHOW = "{{ __('admin.TITLE_PAGE_SHOW') }}"; 
  var PAGE_TO = "{{ __('admin.TITLE_PAGE_TO') }}";
  var PAGE_OF = "{{ __('admin.TITLE_PAGE_OF') }}";
   $(function () {
        //Initialize Select2 Elements
        $('.select2').select2()
    });
   
  
 var note_patient_appointmentdate_err = "{{ __('admin.ERR_NOTE_APPOINTMENT_DATE') }}";
 var note_patient_err = "{{ __('admin.ERR_NOTE') }}";

 var deleteContentPatient = "{{ __('admin.TITLE_DELETE_BUTTON_PATIENT') }}";

</script>

<script src="{{ asset('assets/admin-lte/plugins/datatables/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/datatables-bs4/js/dataTables.bootstrap4.js') }}"></script>
<script type="text/javascript">
function getSpecilist(elements)
{
  $.ajax({
        url: ADMINURL + "/specialist/getSpecilistRecord",
        type: "GET",
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(responce)
        {
          $('#div_specilist').html(responce);
          $('#getSpecilistbtn').trigger('click');
          // session set   
        }
  });
}

var is_updated = '{{$is_updated}}';
if(is_updated == "0")
{
  $("#changePasswordfrm").click();
  $('#changePasswordfrm').modal({backdrop: 'static', keyboard: false});  
}
// else
// {
//   //alert("ppppp--->//");
//   $('#changePasswordfrm').modal({backdrop: '', keyboard: true});
// }

</script>
<button type="button" id="getSpecilistbtn" class="btn fc-button-primary" data-toggle="modal" data-target="#getspecilist" style="display: none">
</button>
<div class="modal fade" id="getspecilist" style="position:fixed;">
  <div class="modal-dialog modal-dialog-scrollable">
  
    <form id="frmAssignSpecilist" role="form" data-toggle="validator" action="{{ url('admin/specialist/assignedSpecialist') }}" class="w-100">
      <div class="modal-content" style="max-height: calc(100vh - -20.5rem)!important;max-width: 680px!important;">
          <div class="modal-header">
            <h3 class="card-title">@lang('admin.TITLE_SPECIALIST_TEXT')</h3>
            <button id="btnClose" type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span></button>                
          </div>               
          <div class="modal-body" id="div_specilist">
          </div>
          <div class="modal-footer">
          <button  type="button" id="btn_swal" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
          <button style="display: none;" id="btn_submit" type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
          <button type="reset" class="btn btn-danger" class="close" data-dismiss="modal" aria-label="Close">@lang('admin.TITLE_CANCEL_BUTTON')</button>
      </div>
      </div>      
      
    </form>
      <!-- /.modal-content -->
  </div>
   <!-- /.modal-dialog -->
</div>
<script type="text/javascript">

var Confirm = "@lang('admin.TITLE_WARNING_CONFIRM')";
var Warning_msg = "@lang('admin.TITLE_WARNING_MSG')";

$("#btn_swal").click(function()
{
    swal({
      title: deleteContent.title,
      text: Warning_msg,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: Confirm,
      confirmButtonClass: "btn-danger",
      closeOnConfirm: true,
      showLoaderOnConfirm: true
    },
    function ()
    {
      $('#btn_swal').hide();
      $('#btn_submit').show();
      $('#btn_submit').trigger('click');
    });
});
$('#frmAssignSpecilist').validator().on('submit', function (e)  
{
    
      if (!e.isDefaultPrevented()) {

          const $this = $(this);
          const action = $this.attr('action');
          const formData = new FormData($this[0]);
          //formData.append('description', editor.getData());
          
          $('.wrapper').LoadingOverlay("show", {
              background: "rgba(165, 190, 100, 0.4)",
          });

          axios.post(action, formData)
              .then(function (response) {
                  const resp = response.data;

                  if (resp.status == 'success') {
                      // $this[0].reset();
                      toastr.success(resp.msg);
                      $('.wrapper').LoadingOverlay("hide");
                      setTimeout(function () {
                        $('#btn_swal').show();
                        $('#btn_submit').hide();
                        $('#btnClose').trigger('click');
                         location.reload();
                      }, 2000)

                  }

                  if (resp.status == 'error') {
                      $('.wrapper').LoadingOverlay("hide");
                      toastr.error(resp.msg);

                      const errorBag = resp.errors;

                      $.each(errorBag, function (fieldName, value) {
                          $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                          $('.err_' + fieldName).text(value[0]).closest('span').show();
                      })
                  }
              })
              .catch(function (error) {
                  $('.card-body').LoadingOverlay("hide");

                  const errorBag = error.response.data.errors;

                  $.each(errorBag, function (fieldName, value) {
                      $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                      $('.err_' + fieldName).text(value[0]).closest('span').show();
                  })
              });

          return false;
      }
})
</script>
<!-- <script src="{{ asset('assets/adminLte/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script> -->
@yield('scripts')
</body>
</html>