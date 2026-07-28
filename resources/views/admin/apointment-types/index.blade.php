@extends('admin.layout.master')

@section('title')
{{ $moduleAction ?? 'Manage Appointment Types' }}
@endsection

@section('styles') 
@endsection

@section('content')
<section class="content"> 
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                @can('appointment-types-add')
                <h3 class=""><a href="{{ route($modulePath.'create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3>
                @endcan
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
                                @if(!empty($specialists) && sizeof($specialists)>0)
                                    @foreach($specialists as $key =>$val)
                                        <option @if(!empty($specialist_details) && $specialist_details->id == $val['id'] ) selected @endif value="{{$val['id']}}">{{ucfirst($val['name'])}}</option>
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

               <!--  <div class="col-sm-3"> 
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
                            <th class="text-center w-100-px">@lang('admin.TITLE_SORTORDER')</th>
                            <th class="w-140-px">@lang('admin.TITLE_APPOINTMENT_TYPE_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_APPOINTMENT_TYPE_DURATION')</th> 
                            <th class="w-200-px">@lang('admin.TITLE_APPOINTMENT_TYPE_STATUS')</th>
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

    <style type="text/css" href="https://cdn.datatables.net/rowreorder/1.2.8/css/rowReorder.dataTables.min.css"></style>
    <script type="text/javascript" src="https://cdn.datatables.net/rowreorder/1.2.8/js/dataTables.rowReorder.min.js"></script>
    <script type="text/javascript" src="{{ asset('/assets/admin/js/appointment-types/index.js?ver=0.5') }}"></script>
     @if(Session::has('error'))
    <script>
        $(document).ready(function () {
            setTimeout(function () {
                toastr.error('{{Session::get('error')}}');
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
@endsection