<!-- @extends('admin.layout.master') -->

@section('title')
{{ $moduleAction ?? 'Notification' }} 
@endsection 

@section('styles')  
@endsection

@section('content')
<section class="content"> 
<div class="row">
    <div class="col-12"> 
        <div class="card">
            <div class="card-header">
              <h3 class=""><a href="{{ route('admin.notification.create') }}" class="btn btn-primary float-right">@lang('admin.TITLE_NOTIFICATION_DEFAULT')</a></h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped " style="width:100%" >
                    <thead class="">  
                        <tr>  
                            <th style="visibility: hidden;"></th> 
                            <th class="w-140-px">@lang('admin.TITLE_PATIENT_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_NOTIFY_TIME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_TITLE_TEXT')</th>
                            <th class="w-100-px">@lang('admin.TITLE_CONTENT_TEXT')</th>
                            <th class="w-130-px">@lang('admin.TITLE_STATUS_TEXT')</th>
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

@endsection

@section('scripts')
    <script type="text/javascript" src="{{ asset('/assets/admin/js/notification/index.js') }}"></script> 
@endsection