@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}  
@endsection
@section('style') 
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/timepicker/bootstrap-timepicker.min.css') }}">
@endsection

@section('content') 
<!-- Main Content -->
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
                @php
                    $notify_date = '';
                    $day_selected = '0';
                    $content = 'Hallo ##PATIENT_NAME##, Ihr Termin mit Dr. ##DOCTOR_SURNAME##(##APPOINTMENT_TYPE##) ist am ##DATE_TIME##';
                    $notify_time = '';
                    if(!empty($notification)){
                       $notify_date = date('Y-m-d',strtotime($notification->notify_time));
                       $notify_time = date('H:i',strtotime($notification->notify_time));
                       $day_selected = $notification->day;
                       $content = $notification->content;
                    }
                @endphp
                <form id="frmNotification" role="form" data-toggle="validator" action="{{ route($modulePath.'store') }}">
                    <div class="card-body">  
                        <div class="row">
                            <!-- <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_NOTIFY_DATE') <span class="required">*</span></label>
                                    <input 
                                        type="text"  
                                        name="notify_date"
                                        value=""  
                                        class="form-control date" 
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_NOTIFICATION_DATE_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_notify_time"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div> -->
                            <div class="col-sm-6"> 
                                <div class="d-flex flex-column mb-25 form-group">
                                     <label class="theme-blue">@lang('admin.TITLE_Day')<span class="required">*</span></label>
                                    <select 
                                        name="day" 
                                        id="day"  
                                        required
                                        data-error="@lang('admin.ERR_NOTIFICATION_DAY_REQUIRED')"
                                        class="form-control" 
                                        >
                                       <!--  <option value="0" @if($day_selected==0) selected @endif>Current Day</option>
                                        <option value="1" @if($day_selected==1) selected @endif>Previous Day</option> -->

                                        <option value="0" @if($day_selected==0) selected @endif>@lang('admin.TITLE_Current_Day')</option>
                                        <option value="1" @if($day_selected==1) selected @endif>@lang('admin.TITLE_Previous_Day')</option>

                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_day"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_NOTIFY_TIME') <span class="required">*</span></label>
                                    <input 
                                        type="text"  
                                        name="notify_time"
                                        value="{{ $notify_time }}"  
                                        class="form-control timepicker" 
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_NOTIFICATION_TIME_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_notify_time"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_TITLE_TEXT') <span class="required">*</span></label>
                                    <input 
                                        type="text"  
                                        name="title"
                                        value="{{ $notification->title ?? 'Erinnerung an Ihren Termin' }}"  
                                        class="form-control" 
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_NOTIFICATION_TITLE_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_title"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="d-flex flex-column mb-25 form-group">
                                     <label class="theme-blue">@lang('admin.TITLE_CONTENT_TEXT')</label>
                                    <textarea
                                       type="text"  
                                        name="content"
                                        class="form-control" 
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_NOTIFICATION_CONTENT_REQUIRED')" 
                                    >{{ $content  }}</textarea> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_content"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>  
                        </div>
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="d-flex flex-column mb-25 form-group">
                                     <label class="theme-blue">@lang('admin.TITLE_STATUS_TEXT')</label>
                                    <select 
                                        name="status" 
                                        id="status"  
                                        required
                                        data-error="@lang('admin.ERR_NOTIFICATION_STATUS_REQUIRED')"
                                        class="form-control" 
                                        >
                                        <option value="3">@lang('admin.TITLE_STATUS_UPDATE_ALL')</option>
                                        <!-- <option value="1">Notified</option>
                                        <option value="2">Read</option> -->
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_status"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.card-body -->

                    <div class="card-footer">
                        <!-- <button type="submit" class="btn btn-danger">Cancel</button> -->
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
<script type="text/javascript" src="{{ asset('assets/plugins/timepicker/bootstrap-timepicker.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/admin/js/notification/create-edit.js') }}"></script> 
@endsection