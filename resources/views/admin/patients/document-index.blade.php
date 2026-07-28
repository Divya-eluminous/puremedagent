@extends('admin.layout.master') 

@section('title')
{{ $moduleAction ?? 'Manage Patients' }} 
@endsection

@section('styles')   
@endsection
 
@section('content')
<style type="text/css">
table.dataTable thead th:nth-child(2) {
    width: 340px !important;
}
table.dataTable {
    table-layout: fixed;
}
</style>
<section class="content">   
<div class="row"> 
    <div class="col-12"> 
        <div class="card">
            <div class="card-header">
                @if(Session::has('success') && !empty(Session::has('success')))
                <div class="alert alert-success">
                  {{ Session::get('success')}}
                </div>
              @endif
                  @php  Session::forget('success') @endphp
                <!-- @can('patients-add')
                <h3 class=""><a href="{{ route($modulePath.'create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3>
                @endcan -->
            </div>

            <!-- /.card-header -->

            <div class="card-body">          
                <table id="listingExamTable" class="table table-bordered table-striped" style="width:100%" > 

                    <thead class="">    
                        <tr>
                            <th style="visibility: hidden;"></th>
                            <th class="w-200-px">@lang('admin.TITLE_EXAMINATION_NAME')</th>  
                            <th class="w-200-px">@lang('admin.TITLE_EXAMINATION_URL')</th>  
                            <th class="w-200-px">@lang('admin.TITLE_DOCUMENT_TYPE')</th>
                            <!-- <th class="w-200-px">@lang('admin.TITLE_DOCUMENT_TYPE')</th> -->
                            <th class="w-200-px">@lang('admin.TITLE_DOCUMENT_STATUS')</th> 
                            <th class="w-200-px">@lang('admin.TITLE_DOCUMENT_DATE_TIME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_DOCUMENT_PRINT')</th>
                            <th class="w-200-px">@lang('admin.TITLE_DOCUMENT_EMAIL')</th>
                            <!-- <th class="w-200-px">@lang('admin.TITLE_PATIENT_BIRTH_DATE')</th> -->
                            <!-- <th class="w-200-px">@lang('admin.TITLE_PATIENT_PLACE')</th> -->
                            <!-- <th class="w-100-px">@lang('admin.TITLE_PATIENT_STATUS')</th>
                            <th class="text-center w-130-px">@lang('admin.TITLE_ACTIONS_TEXT')</th> -->
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</section>
 <!-- /.row -->
      <!--If Setting Is ON -->
      <a style="display: none" id="btn-send-finding-via-email"  type="button" data-toggle="modal" data-target="#modal-default-send-finding-via-email"  class="btn btn btn-primary" href="javascript:void(0)" ></a>
      <div class="modal fade" id="modal-default-send-finding-via-email">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header">
                     <h4 class="modal-title">@lang('admin.TITLE_TODO_LIST_SEND_FINDING')</h4>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span></button>

                  </div>
                  <div class="modal-body">
                      <form id="frm_send_doc"  method="POST" enctype="multipart/form-data"  role="form"  action="{{ url('admin/patients/sendDocumentForPatients') }}">
                        <input type="hidden" name="_token" value="{{csrf_token()}}">
                        <input type="hidden" class="form-control" id="hd_patient_id" name="hd_patient_id" value="">
                        <input type="hidden" class="form-control" id="hd_doc_id" name="hd_doc_id" value="">
                        <input type="hidden" class="form-control" id="type" name="type" value="">
                        <input type="hidden" class="form-control" id="doc_type" name="doc_type" value="">
                        <input type="hidden" class="form-control" id="exam_id" name="exam_id" value="">

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
                                            <input required type="text" id="to" name="to" class="form-control" >
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
                                    <button type="post" class="btn btn-primary btn_submit" id="btn-update-permission-submit">@lang('admin.TITLE_TODO_LIST_SEND') </button>&nbsp;
                                    <button id="close" type="button" class="btn btn-danger close-div" data-dismiss="modal" aria-label="Close">@lang('admin.TITLE_TOTO_ABORT')</button>
                                </div>
                            </div>
                        </div>
                      </form>
                  </div>
              </div>
              <!-- /.modal-content -->
          </div>
        <!-- /.modal-dialog -->
    </div><!-------added on 21-aug-24--------------->
@endsection

@section('scripts')
<script type="text/javascript">
   var patientId = <?php echo $id; ?>
   //console.log(patientId,"document-index.blade.php");

</script>
<script type="text/javascript">
  var dismissContent_title = "{{ __('admin.TITLE_DISMISSAL_BUTTON') }}";
  var dismissContent_yes = "{{ __('admin.WARNING_TITLE') }}";
  var dismissContent_no = "{{ __('admin.WARNING_TITLE_NO') }}";
  var msg_send_doc_for_patient = '{{$msg_send_doc_for_patient}}';
  var title_warning = '{{$title_warning}}';
  var patient_id   = '{{$patient_id}}';
  var doc_send_msg = '{{$doc_send_msg}}';
  var show_examination = "{{ __('admin.SHOW_EXAMINATION') }}";
  var close_examination = "{{ __('admin.HIDE_EXAMINATION') }}";
</script>
    <script type="text/javascript" src="{{ asset('/assets/admin/js/patients/document-index.js') }}"></script>
@endsection

