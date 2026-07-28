@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }} 
@endsection

@section('content') 
<link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css') }}">
<!-- Main Content  -->
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

                <form id="diagnosticFindingTypeForm" role="form" data-toggle="validator" action="{{ route($modulePath.'update', [base64_encode(base64_encode($diagnostic_findings_types->id))]) }}">
                    <input type="hidden" name="_method" value="PUT">
                    <div class="card-body">  
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        class="form-control"  
                                        required
                                        value="{{ $diagnostic_findings_types->name }}"
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_DIAGNOSTIC_FINDING_TYPES_NAME_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div> 
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_COLOR') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control my-colorpicker1 colorpicker-element" 
                                        name="colour"
                                        required
                                        id="color"
                                        maxlength="250" 
                                        value="{{ $diagnostic_findings_types->colour }}"
                                        data-error="@lang('admin.ERR_DIAGNOSTIC_FINDING_TYPES_COLOR_REQUIRED')"
                                        onchange="validateColor()"
                                        >
                                    <span id="validateColor"></span>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_colour"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="d-flex flex-column mb-25 form-group">
                                     <label class="theme-blue">@lang('admin.TITLE_EXAMINATION_STATUS')</label>
                                    <div class="checkbox">
                                          <input 
                                            type="checkbox"   
                                            name="status" 
                                            value="1"
                                            {{$diagnostic_findings_types->status==1?'checked':''}}
                                            >
                                            @lang('admin.TITLE_EXAMINATION_STATUS_ACTIVE') 
                                    </div>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_status"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div> 
                            <div class="col-sm-6"> 
                                
                            </div>
                        </div>
                    </div><!-- /.card-body -->

                    <div class="card-footer">
                        <!-- <button type="submit" class="btn btn-danger">Cancel</button> -->
                        <button type="submit" id="savebtn" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>    
</section>
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/diagnostic-finding-types/create-edit.js') }}"></script>
<script src="{{ asset('assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js') }}"></script> 
@endsection