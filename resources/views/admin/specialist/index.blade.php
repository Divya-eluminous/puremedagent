@extends('admin.layout.master')

@section('title')
{{ $moduleTitle ?? 'Manage Examinations' }}    
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
               
                    @if(empty(Config('ordination_id')))
                    <h3 class=""><a href="{{ route('admin.specialist.create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3> 
                    @endif
                @endcan 
            </div>
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                    <thead class="">
                        <tr>
                            <th style="visibility: hidden;"></th> 
                            <th class="w-140-px">@lang('admin.TITLE_ORDINATION_NAME')</th>
                            <th class="text-center w-100-px">@lang('admin.TITLE_ORDINATION_STATUS')</th>
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
<script type="text/javascript">
    var DELETE_WARNING_MSG     = "{{ __('admin.CHECK_LIST_WARNING_DELETED_MSG') }}"; 
</script> 
    <script type="text/javascript" src="{{ asset('/assets/admin/js/specialist/index.js') }}"></script>
@endsection