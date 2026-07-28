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
             <?php 
             if(!empty(Session::get('specialist')))
             {
                $specilist_id = Session::get('specialist');
             }
             else 
             {
                $specilist_id = $id;
             }
             
             ?>
                <h3 class=""><a href="{{ url('admin/specialist/document/create/'.base64_encode(base64_encode($specilist_id))) }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3> 
            </div>

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
                                        <option @if(!empty($specialist_details->id) && $specialist_details->id == $val['id']) selected @endif value="{{$val['id']}}">{{ucfirst($val['name'])}}</option>
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
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                    <thead class="">
                        <tr>
                            <th style="visibility: hidden;"></th> 
                            <th class="w-140-px">@lang('admin.TITLE_ORDINATION_NAME')</th>
                            <th class="text-center w-100-px">@lang('admin.TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT')</th>
                            <th class="text-center w-100-px">@lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY')</th>
                            <th class="text-center w-100-px">@lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY')</th>
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
   @if(Session::has('error'))
    <script>
        $(document).ready(function () {
            setTimeout(function () {
                toastr.success('{{Session::get('error')}}');
            }, 200);
        })
    </script>
    
@endif

@if(Session::has('success'))
    <script>
        $(document).ready(function () {
            setTimeout(function () {
                    toastr.success('{{Session::get('success')}}');
            }, 200);
        })
    </script>

    {{Session::forget('success')}}
@endif 
<script type="text/javascript" src="{{ asset('/assets/admin/js/specialist/documents/index.js') }}"></script>

@endsection