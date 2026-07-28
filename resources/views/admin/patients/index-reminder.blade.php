@extends('admin.layout.master') 

@section('title')
{{ $moduleAction ?? 'Manage Patients' }} 
@endsection

@section('styles')   
@endsection
 
@section('content')

<style type="text/css">
.modal-dialog {
    max-width: 1000px;
    margin: 1.75rem auto;
}
</style>

<section class="content"> 
<?php //dd($show_ordination) ?>  
<div class="row">
    <div class="col-12"> 
        <div class="card">
            <div class="card-header"> 
                <!-- <a onclick="return addRole(this)" data-href="#" data-toggle="modal" class="btn btn-primary float-right">Import</a> -->
               <!--  <h3 class=""><a onclick="return addPatient(this)" data-href="{{ route('admin.patients.import') }}" class="btn btn-primary">Import</a></h3> 
                @can('patients-add')
                <h3 class=""><a href="{{ route($modulePath.'create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3>
                @endcan -->

            </div>
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >  
                    <thead class="">    
                        <tr>
                            <th style="visibility: hidden;"></th>
                            <th class="w-140-px">@lang('admin.TITLE_PATIENT_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_PATIENT_EMAIL')</th>
                            <th class="w-200-px">@lang('admin.TITLE_PATIENT_MOBILE_NO')</th> 
                            <th class="w-200-px">@lang('admin.TITLE_REMINDER') </th>
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


@section('model') 
    @include('admin.patients.import-patient-model')
@show 

<div class="modal fade" id="getReminderModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm" >
     <div class="modal-content" style="max-height: calc(100vh - -20.5rem)!important;width: 100%!important;margin: 20px;">
      <div class="modal-header">
         <h3 class="card-title">@lang("admin.TITLE_REMINDER_POPUP")</h3>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >  
            <thead class="">    
                <tr>
                    <th class="w-140-px">@lang('admin.TITLE_SERVICE')</th>
                    <th class="w-200-px">@lang('admin.TITLE_REMINDER_DATE')</th>
                    <th class="w-200-px">@lang('admin.TITLE_REMINDER_TYPE')</th> 
                    <th class="w-200-px">@lang('admin.TITLE_REMINDER_APPOINTMENT_DATE')</th>
                    <th class="w-200-px">@lang('admin.TITLE_REMINDER_MEDIA')</th>
                   <th class="w-200-px">Status</th>
                   <th class="w-200-px">@lang('admin.TITLE_REMINDER_READ_STATUS')</th>
                   <th class="w-200-px">Cycle</th>
                </tr>
            </thead>
            <tbody id="reminderData">
                
            </tbody>
        </table>
      </div>      
    </div>
  </div>
</div>

@endsection

@section('scripts')

    <script type="text/javascript" src="{{ asset('/assets/admin/js/patients/index-reminder.js') }}">
    </script>
    <script type="text/javascript">
        var show_ordination = '{{$show_ordination}}';
    </script>
     <!------Below script added by divya on 21 sept-22------------------------>

    <script type="text/javascript">

        $(document).on('click','.btn-reminder-model',function()
        {
          var patientid = $(this).attr('patientid');
          if(patientid) 
          {
              $.ajax({
                 type: "GET",
                 url: "{{ url('/') }}"+"/admin/patients/getReminderData?patientid="+patientid,
                 success:function(response)
                 {
                    console.log(response);
                    $("#getReminderModal").modal('show');
                    $("#reminderData").html(response);
                 },
                 error: function(blob)
                 {
                   console.log(response);
                 }
              })
          }//if patientid
        });
       
    </script>
@endsection 