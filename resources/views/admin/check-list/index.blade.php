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
                <h3 class=""><a href="{{ route('admin.check-list.create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3> 
                @endcan 
            </div>
            @if(!empty($specialists) && sizeof($specialists)>0)
            <div class="card-header row"> 
                <div class="col-sm-3"> 
                    <div class="form-group">
                        <label class="theme-blue"> 
                        @lang('admin.TITLE_SPECIALIST_TEXT') </label>
                        <select 
                            class="form-control" 
                            onchange="SetSession(this)" 
                            required
                            name="specialist" 
                            id="specialist" 
                            data-error="@lang('admin.ERR_DOCUMENT_TYPE_OF_DOCUMENT')">
                                <option value="">@lang('admin.TITLE_SPECIALIST_SELECT_TEXT')</option>
                                <!-- <option @if(empty($specialist_details)) selected @endif value="all">All</option> -->
                                @if(!empty($specialists) && sizeof($specialists)>0)
                                    @foreach($specialists as $key =>$val)
                                        <option @if($specialist_details->id == $val['id']) selected @endif value="{{$val['id']}}">{{ucfirst($val['name'])}}</option>
                                    @endforeach    
                                @endif
                        </select> 
                        <span class="help-block invalid-feedback with-errors">
                            <ul class="list-unstyled">
                                <li class="err_type_of_document"></li>
                            </ul>
                        </span>
                    </div>
                </div>
               
                <!-- <div class="col-sm-3"> 
                    <div class="form-group">
                        <label class="theme-blue"> 
                        </label>
                        <div>
                            <a style="margin-top: 10px;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span>
                            </a>
                        </div>
                       
                    </div>
                </div> -->
            </div>
            @endif
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                    <thead class="">
                        <tr>
                            <th style="visibility: hidden;"></th> 
                            <th class="w-140-px">@lang('admin.TITLE_CHECKLIST_NAME')</th>
                            <th class="w-140-px">@lang('admin.TITLE_CHECK_LIST_TYPE')</th>
                            <th class="w-200-px">@lang('admin.TITLE_CHECKLIST_INTRODUCER_TEXT')</th> 
                            <th class="w-200-px">@lang('admin.TITLE_CHECKLIST_FINAL_TEXT')</th> 
                            <th class="text-center w-100-px">@lang('admin.TITLE_CHECKLIST_EXAMINATION_STATUS')</th>
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
    <script type="text/javascript" src="{{ asset('/assets/admin/js/check-list/index.js') }}"></script>
@endsection