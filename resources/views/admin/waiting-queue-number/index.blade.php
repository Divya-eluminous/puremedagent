@extends('admin.layout.master') 

@section('title')
{{ $moduleAction ?? 'Manage Waiting Queue Number' }} 
@endsection

@section('styles')  
@endsection

@section('content')
<section class="content"> 
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class=""><a href="{{ route('waiting.screen') }}" class="btn btn-primary float-right">@lang('admin.TITLE_WAITING_SCREEEN')</a></h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">         
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >   
                    <thead class="">    
                        <tr> 
                            <th style="visibility: hidden;"></th>
                            <th class="w-140-px">@lang('admin.TITLE_WAITING_PATIENT_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_WAITING_PATIENT_DOCTOR_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_WAITING_PATIENT_APPOINTMENT_TYPE_NAME')</th> 
                            <th class="text-center w-130-px">@lang('admin.TITLE_WAITING_PATIENT_APPOINTMENT_TIME')</th>
                            <th class="text-center w-130-px">@lang('admin.TITLE_WAITING_PATIENT_APPOINTMENT_STATUS')</th>
                            <th class="w-200-px">@lang('admin.TITLE_WAITING_PATIENT_QUEUE_NUMBER')</th>
                            <th class="w-200-px">@lang('admin.TITLE_WAITING_PATIENT_QUEUE_NUMBER_TYPE')</th>
                            <th class="w-200-px">@lang('admin.TITLE_WAITING_PATIENT_SCAN_TIME')</th>
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
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ asset('/assets/admin/js/waiting-queue-number/index.js') }}"></script> 
@endsection 