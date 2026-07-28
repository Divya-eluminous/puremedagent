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
                
                <form id="frmMenusSettings" role="form" data-toggle="validator" action="{{ route($modulePath.'store') }}"> 
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_MENU_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_MENU_NAME_REQUIRED')." 
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
                                    @lang('admin.TITLE_MENU_URL') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="url" 
                                        class="form-control"   
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_MENU_URL_REQUIRED')." 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_url"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_MENU_STATUS') </label>
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status" 
                                            value="1" 
                                            checked
                                            >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_MENU_STATUS_ACTIVE')</label>
                                    </div> 
                                </div>
                            </div>
                            <div class="col-sm-6">  
                                <div class="form-group"> 
                                    
                                </div>
                            </div>         
                        </div>
                    </div>

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
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/admin/js/menus-settings/create-edit.js') }}"></script> 
@endsection 