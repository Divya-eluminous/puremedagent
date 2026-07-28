@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection

@section('content')
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

                <form id="frmSupportSettings" role="form" data-toggle="validator" action="{{ route($modulePath.'update', [base64_encode(base64_encode($support->id))]) }}">
                    <input type="hidden" name="_method" value="PUT">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SUPPORT_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        class="form-control"  
                                        value="{{ $support->name }}" 
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_NAME_REQUIRED')." 
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
                                    @lang('admin.TITLE_SUPPORT_STATUS') <span class="required">*</span></label>
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status"
                                            value="1" @if(!empty($support->status) && $support->status==1) checked @endif
                                        >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_SUPPORT_STATUS_ACTIVE')</label> 
                                    </div>
                                </div>
                            </div> 
                        </div>              
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SUPPORT_URL') <span class="required">*</span></label>
                                    <input type="file" name="file" class=" btn-primary form-control"  
                                        >
                                    <input type="hidden" name="old_file" class="old_file" id="old_file" value="{{ $support->url }}">
                                    
                                    <a href="{{ url('/storage/app/guideline-manual/'.$support->url) }} " target='_new'> {{ $support->url }}</a>                          
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_url"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                           
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SUPPORT_APK') </label>
                                    <input type="file" name="file" class=" btn-primary form-control"  
                                        >
                                    <input type="hidden" name="apk_old_file" class="apk_old_file" id="apk_old_file" value="{{ $support->apk }}">
                                    
                                    <a href="{{ url('/storage/app/guideline-manual/'.$support->apk) }} " target='_new'> {{ $support->apk }}</a>                          
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_url"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button> 
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
<script type="text/javascript" src="{{ url('assets/admin/js/support-settings/create-edit.js') }}"></script>
@endsection