@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}  
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

                <form id="frmNotification" role="form" data-toggle="validator" action="{{ route($modulePath.'update', [base64_encode(base64_encode($notification->id))]) }}">
                        <input type="hidden" name="_method" value="PUT">

                         <input type="hidden" name="appointment_id" value="{{ $notification->appointment_id? $notification->appointment_id:0}}">
                         
                    <div class="card-body">  
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="d-flex flex-column mb-25 form-group">
                                    <label>@lang('admin.TITLE_PATIENT_NAME')</label> 
                                    <select 
                                        name="patient_id" 
                                        id="patient_id"  
                                        required
                                        data-error="@lang('admin.ERR_NOTIFICATION_PATIENT_NAME_REQUIRED')"
                                        class="form-control" 
                                        >
                                        <!-- <option value="">@lang('admin.TITLE_SELECT_PATIENT')</option> -->
                                        @foreach($patient as $patients)
                                        <option value="{{ $patients->id }}" @if($patients->id==$notification->patient_id) selected @endif>{{ $patients->first_name .' '. $patients->family_name}}</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_patient_id"></li>
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
                                        value="{{ $notification->notify_time }}"  
                                        class="form-control" 
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
                                        value="{{ $notification->title }}"  
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
                                    >{{ $notification->content }}</textarea> 
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
                                        <option value="">@lang('admin.TITLE_SELECT_STATUS_TEXT')</option>
                                        <option value="0" {{ $notification->status == 0 ? 'selected' : '' }}>Added</option>
                                        <option value="1" {{ $notification->status == 1 ? 'selected' : '' }}>Notified</option>
                                        <option value="2" {{ $notification->status == 2 ? 'selected' : '' }}>Read</option>
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