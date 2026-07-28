@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content')
<!-- Main content -->        
<section class="content"> 
<div class="container-fluid"> 
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- jquery validation -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button>
                </div>
                
                <form id="uploadApkForm" role="form" data-toggle="validator" action="{{ route($modulePath.'store') }}">
                   <div class="card-body">
                       <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SIGNDOC_APP') </label>
                                    <input 
                                        type="file" 
                                        name="signdoc_app" 
                                        class="form-control"
                                        accept=".apk" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_signdoc_app"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_MASTER_DATA_APP') </label>
                                    <input 
                                        type="file" 
                                        name="master_data_app" 
                                        class="form-control" 
                                        accept=".apk"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_master_data_app"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_WAITING_NUM_APP') </label>
                                    <input
                                        type="file" 
                                        name="wating_num_app" 
                                        class="form-control" 
                                        accept=".apk"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_wating_num_app"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                        <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>    
</section>
@endsection

@section('scripts')
<script>
    var editor = "";
</script>
<script type="text/javascript" src="{{ asset('assets/admin/js/apks/create-edit.js') }}"></script>
@endsection