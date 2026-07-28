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
            @if(empty(Config('ordination_id')))
            <div class="card-header"> 
                @can('exams-add')  
                <h3 class=""><a href="{{ route('admin.ordination.create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3> 
                @endcan 
            </div>
            @endif
            <!-- <form id="OrdinationsForm" enctype="multipart/form-data" method="post" action="{{url('admin/ordination/uploadOCR')}}">
                {{ csrf_field() }}
            <div class="card-header"> 
                <input 
                    type="file" 
                    name="logo" 
                    class="form-control"  
                    maxlength="250" 
                    required
                    data-error="@lang('admin.ERR_ORDINATION_LOGO')" 
                > 
                <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
            </div>
            </form> -->
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                    <thead class="">
                        <tr>
                            <th style="visibility: hidden;"></th> 
                            <th class="w-140-px">@lang('admin.TITLE_ORDINATION_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_ORDINATION_LINK')</th> 
                        <th class="w-200-px">@lang('admin.TITLE_ORDINATION_LOGO')</th>
                           
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
    <script type="text/javascript" src="{{ asset('/assets/admin/js/ordination/index.js?ver=0.1') }}"></script>
@endsection