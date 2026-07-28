@extends('admin.layout.master') 

@section('title')
{{ $moduleAction ?? 'Manage Patients Notification' }} 
@endsection

@section('styles')   
@endsection
 
@section('content')
<section class="content"> 
<?php //dd($show_ordination) ?>  
<div class="row">
    <div class="col-12"> 
        <div class="card">
            <div class="card-header"> 
               
                @can('patients-add')
                <h3 class=""><a href="{{ route($modulePath.'create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3>
                @endcan

                 <a href="#" onclick="return sendNotification()" class="btn btn-primary"> @lang('admin.SEND_NOTIFICATION_BTN')</a>
            </div>
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >  
                    <thead class="">    
                        <tr>
                            <th style="visibility: hidden;"></th>
                            <th class="w-140-px">@lang('admin.TITLE_PATIENT_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_PATIENT_EMAIL')</th>
                            <th class="text-center w-130-px">@lang('admin.TITLE_ACTIONS_TEXT')</th>
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

@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('/assets/admin/js/notificationpatients/index.js') }}">
</script>
<script>
  function sendNotification()
  {
    $.LoadingOverlay("show", {
       background  : "rgba(165, 190, 100, 0)",
    });

    var action = ADMINURL + '/notification-patient/sendNotification'; //changeSMSStatus
    if (action != '') {
      $.ajax({
            url: action,
            type: "GET",
            async:false,
            success: function(response)
            {
                console.log(response);
                $.LoadingOverlay("hide");
                if (response.status === 'success') {
                  swal("Success", response.msg, 'success');                 
                }
                else
                {
                  swal("Error", response.msg, 'error');
                }
              // swal("Yes", responce.msg, 'success');
            }
        });
    }//if action
  }// sendNotification
</script>      
    
@endsection 