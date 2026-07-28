@extends('admin.layout.master')

@section('title')
{{ $moduleTitle ?? 'Manage Diagnostic Finding Types' }}    
@endsection

@section('styles')
@endsection

@section('content') 
<section class="content">  
<div class="row">
    <div class="col-12">    
        <div class="card">
            <div class="card-header"> 
                @can('exams-add')  
                <h3 class=""><a href="{{ route('admin.diagnostic-finding-types.create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3> 
                @endcan 
            </div>
            <!-- /.card-header --> 
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                    <thead class="">
                        <tr>
                            <th style="visibility: hidden;"></th> 
                            <th class="w-140-px">@lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_COLOR')</th> 
                            <th class="text-center w-100-px">@lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_STATUS')</th>
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
    <script type="text/javascript" src="{{ asset('/assets/admin/js/diagnostic-finding-types/index.js') }}"></script>
@endsection