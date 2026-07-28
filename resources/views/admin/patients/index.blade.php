@extends('admin.layout.master') 

@section('title')
{{ $moduleAction ?? 'Manage Patients' }} 
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
                <!-- <a onclick="return addRole(this)" data-href="#" data-toggle="modal" class="btn btn-primary float-right">Import</a> -->
                <h3 class=""><a onclick="return addPatient(this)" data-href="{{ route('admin.patients.import') }}" class="btn btn-primary">Import</a></h3> 
                @can('patients-add')
                <h3 class=""><a href="{{ route($modulePath.'create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3>
                @endcan
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
                            <th class="w-200-px">@lang('admin.TITLE_PATIENT_BIRTH_DATE') {{ $show_ordination }} </th>
                            <th class="w-200-px" @if($show_ordination == false)
                     style="visibility: hidden;" @endif>@lang('admin.TITLE_ORDINATION_TEXT')</th> 
                            <th class="w-100-px">@lang('admin.TITLE_PATIENT_STATUS')</th>
                            <th class="w-100-px">@lang('admin.SEND_MAIL')</th>
                            <th class="w-100-px">@lang('admin.SEND_SMS')</th>
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
    <script type="text/javascript" src="{{ asset('/assets/admin/js/patients/index.js') }}">
        
    </script>
    <script type="text/javascript">
        var show_ordination = '{{$show_ordination}}';
    </script>
    
@endsection 