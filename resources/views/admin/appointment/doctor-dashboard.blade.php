@extends('admin.layout.master')
@section('title')
{{ $moduleAction ?? 'Manage Dashboard' }}
@endsection
@section('style')
<!-- fullCalendar -->
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-daygrid/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-timegrid/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-bootstrap/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/qtip2/jquery.qtip.min.css') }}">
<style type="text/css">
  .checkbox_wraper {
    position: relative;
    padding-left: 15px;
  }

  .checkbox_wraper input[type="checkbox"] {
    position: absolute;
    left: 0;
    top: 5px;
  }

  .loadingoverlay_element {
    top: 50%;
    left: 50%;
    margin-top: -50px;
    margin-left: -50px;
    position: absolute;
  }

  .loadingoverlay {
    background: rgb(25 19 19 / 2%) !important;
  }

  /*.frm_check_col{
  padding-left: 30px;
}*/
  @media(max-width: 821px) {
    /*.frm_check_col{
    padding-left: 0px!important;
  }*/
  }
</style>
@endsection
@section('content')
<!-- Main content -->
<section class="content">
  <span id="donload_files" style="display: none;">

  </span>
  <!-- <div id='loader'>Loading</div> -->
  <div id="divToPrint" style="display:none;">
    <div style="width:700px;height:700px;background-color:teal;">
      <img id="doc_img">
    </div>
  </div>
  <div>
    <input class="print-dr-dashboard" type="button" value="print" onclick="PrintDiv();" />
  </div>
  <div class="container-fluid blue-box-wrapper">
    <div class="row">

    </div>
    @php
    // \Carbon\Carbon::setLocale('de');
    $date = \Carbon\Carbon::now();

    $days = [
      'Sunday' => 'Sonntag',
      'Monday' => 'Montag',
      'Tuesday' => 'Dienstag',
      'Wednesday' => 'Mittwoch',
      'Thursday' => 'Donnerstag',
      'Friday' => 'Freitag',
      'Saturday' => 'Samstag'
    ];

    $months = [
      'January' => 'Januar',
      'February' => 'Februar',
      'March' => 'März',
      'April' => 'April',
      'May' => 'Mai',
      'June' => 'Juni',
      'July' => 'Juli',
      'August' => 'August',
      'September' => 'September',
      'October' => 'Oktober',
      'November' => 'November',
      'December' => 'Dezember'
    ];

    $formattedDate = $days[$date->format('l')] . ' ' . $date->format('d') . '. ' . $months[$date->format('F')] . ' ' . $date->format('Y');
    @endphp

    <table>
      <tbody>
        <th>
        <td>@lang('admin.TITLE_SERVICES')</td>
        <td>@lang('admin.TITLE_REVERS')</td>
        <td>@lang('admin.TITLE_FINDING')</td>
        <td>{{ $formattedDate }}</td>
        </th>
      </tbody>
    </table>
    <div class="row">
      <div class="col-md-12">
        <input type="hidden" name="action_url" id="action_url"
          value="{{  route('admin.doctor-dashboard.updateReminder') }}" />
        <div id="calendar">
        </div>
      </div>
    </div>
    <div id="appointmentModal" class="modal" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">@lang('admin.APPOINTMENT_DETAILS')
              <i class="fa fa-edit" data-toggle="modal" id="editAppointmentModal" data-id="" data-toggle="modal"
                data-target="#editAppointmentDataModal" title="Edit Appointment"></i>
              <i class="fa fa-trash" id="deleteAppointmentModal" data-id="" title="Delete Appointment"></i>
              <i class="fas fa-user-injured" data-toggle="modal" id="redirectToPatient" data-id="" data-toggle="modal"
                title="Edit Patient"></i>
            </h3>
            <button type="button" class="close btnClosePopup" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body border-0 col-md-12">
            <div class="col-md-8" id="popup_description">
            </div>
            <div class="col-md-4" id="qr_code">
            </div>
          </div>
          <div class="modal-footer">
          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>

    <!-- The Modal -->
    <div class="modal" id="editAppointmentDataModal">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">
              @lang('admin.EDIT_APPOINTMENT')
              <i class="fa fa-edit" data-toggle="modal" id="editAppointmentModal1" data-id=""></i>
            </h3>
            <button type="button" class="close btnClosePopup" data-dismiss="modal">×</button>
          </div>
          <div class="modal-body">
            <form id="frmAppointmentEdit" role="form" data-toggle="validator" action="">

            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- The Modal -->

    <!-- The Modal -->
    <div class="modal" id="editAppointmentTypeModal">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">
              @lang('admin.TITLE_APPOINTMENT_TYPE_TEXT')
              <i class="fa fa-edit" data-toggle="modal" id="editAppointmentTypeModal" data-id=""></i>

            </h3>
            <button type="button" class="close btnClosePopup" data-dismiss="modal">×</button>
          </div>
          <div class="modal-body">
            <form id="frmAppointmentTypeEdit" role="form" data-toggle="validator" action="">

            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- The Modal -->

    <!-- /.row -->
    <!--If Setting Is ON -->
    <a style="display: none" id="btn-send-finding-via-email" type="button" data-toggle="modal"
      data-target="#modal-default-send-finding-via-email" class="btn btn btn-primary" href="javascript:void(0)"></a>
    <div class="modal fade" id="modal-default-send-finding-via-email">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">@lang('admin.TITLE_TODO_LIST_SEND_FINDING')</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span></button>

          </div>
          <div class="modal-body">
            <form id="frm_send_doc" method="POST" enctype="multipart/form-data" role="form"
              action="{{ url('admin/appoitment/sendDocumentForPatients') }}">
              <input type="hidden" name="_token" value="{{csrf_token()}}">
              <input type="hidden" class="form-control" id="hd_patient_id" name="hd_patient_id" value="">
              <input type="hidden" class="form-control" id="hd_doc_id" name="hd_doc_id" value="">
              <input type="hidden" class="form-control" id="type" name="type" value="">
              <input type="hidden" class="form-control" id="doc_type" name="doc_type" value="">
              <input type="hidden" class="form-control" id="exam_id" name="exam_id" value="">
              <input type="hidden" class="form-control" id="a_id" name="a_id" value="">

              <div class="box-body">
                <div class="box-body" id="popup_div">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-sm-12">
                        <div class="form-group">
                          <label class="theme-blue">
                            @lang('admin.TITLE_PATIENT_NAME') : </label>
                          <input type="text" id="patient_name" name="patient_name" class="form-control" readonly>
                        </div>
                      </div>
                      <div class="col-sm-12">
                        <div class="form-group">
                          <label class="theme-blue">
                            @lang('admin.SEND_MAIL_TO') : </label>
                          <input required type="text" id="to" name="to" class="form-control">
                        </div>
                      </div>
                      <div id="hd_noties_div" class="col-sm-12" style="display: none;">
                        <div class="form-group">
                          <label class="theme-blue">
                            @lang('admin.TITLE_APPOINTMENT_NOTE') : </label>
                          <textarea type="text" name="hd_notes" id="hd_notes" class="form-control"></textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="box-footer">
                  <div class="col-md-12 align-right">
                    <button type="button" class="btn btn-primary btn_submit"
                      id="btn-update-permission-submit">Save</button>&nbsp;
                    <button id="close" type="button" class="btn btn-danger close-div" data-dismiss="modal"
                      aria-label="Close">Cancel</button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!-- Section: Live preview -->
  </div>
  <!-- /.container-fluid -->
</section>
@endsection
@section('scripts')
<script type="text/javascript">
  $(document).on('click', '.dropdown-menu', function (e) {
    e.stopPropagation();
  });

  var dismissContent_title = "{{ __('admin.TITLE_DISMISSAL_BUTTON') }}";
  var dismissContent_yes = "{{ __('admin.WARNING_TITLE') }}";
  var dismissContent_no = "{{ __('admin.WARNING_TITLE_NO') }}";
  var msg_send_doc_for_patient = '{{$msg_send_doc_for_patient}}';
  var title_warning = '{{$title_warning}}';
  var patient_id = '{{$patient_id}}';
  var doc_send_msg = '{{$doc_send_msg}}';
  var show_examination = "{{ __('admin.SHOW_EXAMINATION') }}";
  var close_examination = "{{ __('admin.HIDE_EXAMINATION') }}";
</script>
<script type="text/javascript" src="{{ url('assets/admin/js/appointment/dashboard.js?ver=0.9') }}"></script>
@endsection