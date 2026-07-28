@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content')

<style>
    select .form-control
    {
        margin-top: 5px;
    }

</style>

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
                    
                    <form id="AppsSettingForm" role="form" data-toggle="validator" action="{{ url('admin/settings/updateSmartphoneApps/'.base64_encode(base64_encode($setting->id)))}}">                   
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_SETTING_TEXT_IPHONE') <span class="required">*</span></label> 
                                    </div>
                                </div>
                                <div class="col-sm-9"> 
                                    <div class="form-group"><input 
                                        type="text" 
                                        name="iphone" 
                                        class="form-control"  
                                        maxlength="250" 
                                        value="{{$SmartphoneAppsModel->iphone ?? ''}}" 
                                        required 
                                        data-error="@lang('admin.ERR_SETTING_IPHONE_REQUIRED')"
                                    >
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_iphone">@lang('admin.ERR_SETTING_IPHONE_REQUIRED')</li>
                                            </ul>
                                        </span>
                                    </div>
                                </div> 
                            </div> 
                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_SETTING_TEXT_ANDOID') <span class="required">*</span></label> 
                                    </div>
                                </div>
                                <div class="col-sm-9"> 
                                    <div class="form-group">
                                        <input 
                                        type="text" 
                                        name="andoid" 
                                        class="form-control"  
                                        maxlength="250" 
                                        required 
                                        data-error="@lang('admin.ERR_SETTING_ANDOID_REQUIRED')"
                                        value="{{$SmartphoneAppsModel->andoid ?? ''}}" 
                                        >
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_andoid">@lang('admin.ERR_SETTING_ANDOID_REQUIRED')</li>
                                            </ul>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_SETTING_TEXT_MASTER_TABLET') <span class="required">*</span></label> 
                                    </div>
                                </div>
                                <div class="col-sm-9"> 
                                    <div class="form-group">
                                        <input 
                                        type="text" 
                                        name="master_tablet" 
                                        class="form-control"  
                                        required 
                                        data-error="@lang('admin.ERR_SETTING_TABLET_REQUIRED')" 
                                        value="{{$SmartphoneAppsModel->master_data_tablet ?? ''}}" 
                                        >
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_master_tablet">@lang('admin.ERR_SETTING_TABLET_REQUIRED')</li>
                                            </ul>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_SETTING_TEXT_WAITING_NO_TABLET') <span class="required">*</span></label> 
                                    </div>
                                </div>
                                <div class="col-sm-9"> 
                                    <div class="form-group">
                                        <input 
                                        type="text" 
                                        name="waiting_no_tablet" 
                                        class="form-control"  
                                        required 
                                        data-error="@lang('admin.ERR_SETTING_TABLET_REQUIRED')" 
                                        value="{{$SmartphoneAppsModel->waiting_no_tablet ?? ''}}" 
                                        >
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_waiting_no_tablet">@lang('admin.ERR_SETTING_TABLET_REQUIRED')</li>
                                            </ul>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_SETTING_TEXT_SINGDOC_TABLET') <span class="required">*</span></label> 
                                    </div>
                                </div>
                                <div class="col-sm-9"> 
                                    <div class="form-group">
                                        <input 
                                        type="text" 
                                        name="singDoc_tablet" 
                                        class="form-control"  
                                        required 
                                        data-error="@lang('admin.ERR_SETTING_TABLET_REQUIRED')" 
                                        value="{{$SmartphoneAppsModel->singdoc_tablet ?? ''}}" 
                                        >
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_singDoc_tablet">@lang('admin.ERR_SETTING_TABLET_REQUIRED')</li>
                                            </ul>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_SETTING_TEXT_SMARTPHONE') <span class="required">*</span></label> 
                                    </div>
                                </div>
                                
                                <div class="col-sm-9"> 
                                    <textarea
                                    type="text" 
                                    name="default_text" 
                                    class="form-control" 
                                    id="default_sms_text"
                                    required 
                                    data-error="@lang('admin.ERR_SETTING_TEXT_REQUIRED')"
                                    >{{$SmartphoneAppsModel->text ?? ''}}</textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_default_text">@lang('admin.ERR_SETTING_TEXT_REQUIRED')</li>
                                        </ul>
                                    </span>
                                </div>                                
                            </div>

                            <!---start--added on 4-feb-26----------> 
                            <div class="row mt-3">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_IOS_FLAG') <span class="required">*</span></label>
                                    </div>
                                </div>
                                <div class="col-sm-9">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="status"
                                         name="status" value="1" @if(!empty($setting->status) && $setting->status==1) checked @endif
                                        > 
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_SETTING_STATUS_ACTIVE')</label>
                                    </div>
                                </div>
                            </div> 
                            <!----end 4-feb-26------------------------>

                            <!---start--added on 12-feb-26----------> 
                            <div class="row mt-1">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_ANDROID_FLAG') <span class="required">*</span></label>
                                    </div>
                                </div>
                                <div class="col-sm-9">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="android_review"
                                         name="android_review" value="1" @if(!empty($SmartphoneAppsModel->android_review) && $SmartphoneAppsModel->android_review==1) checked @endif
                                        > 
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_SETTING_STATUS_ACTIVE')</label>
                                    </div>
                                </div>
                            </div> 
                            <!----end 12-feb-26------------------------>

                        </div>
                        <div class="card-footer">
                            <button type="submit" id="btn_sub" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                            <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
                        </div>
                    </form> 
                </div>
            </div>
        </div>
    </div>    
</section>
@php
$title = __('admin.TITLE_SELECT_DOCTOR');
@endphp
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript">


</script>
<script type="text/javascript" src="{{ url('assets/admin/js/settings/edit-samrtphone-apps.js') }}"></script>
@endsection