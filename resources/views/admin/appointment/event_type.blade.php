@extends('admin.layout.master')

@section('title')
{{ $moduleAction ?? 'Manage Appointment' }}
@endsection

@section('styles') 
@endsection

@section('content')
<section class="content"> 
<div class="row"> 
    <div class="col-12">
        <div class="card">
            <!-- <div class="card-header"> 
                <h3 class=""><a onclick="return addBooking(this)" data-href="{{ route('admin.appointment.import') }}" class="btn btn-primary">Import</a></h3> 
                @can('appointment-add')
                <h3 class=""><a href="{{ route($modulePath.'create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3> 
                @endcan 
            </div> -->
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                    <thead class="">
                        <tr>
                            <th style="visibility: hidden;"></th>  
                             <th class="w-140-px">@lang('admin.TITLE_REMINDER_APPOINTMENT_DATE')</th>
                             <th class="w-140-px">@lang('admin.INTERACTION_DATE')</th>
                           <!-- <th class="w-140-px">@lang('admin.TITLE_APPOINTMENT_END_DATE')</th> -->
                            <th class="w-140-px">@lang('admin.TITLE_APPOINTMENT_PATIENT')</th>
                            <th class="w-200-px">
                            @lang('admin.TITLE_EXAMINATIONS_TEXT')</th>
                            <th class="w-140-px">@lang('admin.TITLE_EVENT_TYPE_SERVICES')</th>
                            <th class="w-200-px">@lang('admin.TITLE_SETTING_EVENT_TYPE')</th>
                            <th class="w-200-px">@lang('admin.TITLE_SETTING_STATUS')</th>
                            <!-- <th class="text-center w-130-px">@lang('admin.TITLE_ACTIONS_TEXT')</th> -->
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
    @include('admin.appointment.import-appointment-model')
@show 
@endsection

@section('scripts') 
    <script type="text/javascript" src="{{ asset('/assets/admin/js/appointment/event_type.js') }}"></script>
    <script type="text/javascript">
    </script>
@endsection