@extends('web.layout.master')
@section('title')
{{ $moduleAction ?? '' }}
@endsection
@section('style') 
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/timepicker/bootstrap-timepicker.min.css') }}">
@endsection
@section('content')

<div class="container">
  <div class="row">
    <div class="main_content book_data">
        <!-- jquery validation -->
            <div class="card card-primary">   
                <div class="card-header">
                    <h3 class="card-title">Online Terminvereinbarung</h3>
                </div>
        
                <form id="frmAppointment" role="form" data-toggle="validator" action="">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>@lang('front.TITLE_APPOINTMENT_DOCTOR')
                                        @if($doctor_id) <span class="required">*</span>@endif</label> 
                                        @if($doctor_id)
                                        <input type="hidden" name="fastest_appoitment" id="fastest_appoitment" value="0" />
                                        <select 
                                        name="doctor_id" 
                                        id="doctor_id"  
                                        required
                                        data-error="@lang('front.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                        class="form-control select2"
                                        >
                                        @else
                                         <input type="hidden" name="fastest_appoitment" id="fastest_appoitment" value="1" />
                                        <select 
                                        name="doctor_id" 
                                        id="doctor_id"  
                                        class="form-control select2"
                                        >
                                        @endif

                                   <!--  <select 
                                        name="doctor_id" 
                                        id="doctor_id"  
                                        required
                                        data-error="@lang('front.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                        class="form-control select2"
                                        > -->
                                        <option value="">@lang('front.TITLE_SELECT_DOCTOR')</option>
                                        @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" @if(!empty($doctor_id) && $doctor_id==$doctor->id) selected @endif>{{ $doctor->first_name .' '. $doctor->last_name}}</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_user_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label>@lang('front.TITLE_APPOINTMENT_TYPE') <span class="required">*</span></label>  
                                    <select 
                                        name="appointment_type_id" 
                                        id="appointment_type_id"  
                                        required
                                        data-error="@lang('front.ERR_APPOINTMENT_TYPE_REQUIRED')"
                                        class="form-control select2" 
                                        ><option value="">@lang('front.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE')</option>
                                        @foreach($appointment_type as $appointment_types)
                                        <option value="{{ $appointment_types->id }}">{{ $appointment_types->name}}</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_appointment_type_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                           <!--  <div class="col-sm-12">
                                <div class="green_btn right_arrow">
                                    <a href="javascript:void(0)" class="btn" id="toggle_icon" type="button" data-toggle="collapse">Detailsuche <i class="fa fa-arrow-down"></a></i>
                                </div>
                            </div> -->
                            <div class="col-sm-4 toggle_text"  style="display: block"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_WEEKDAY') <span class="required">*</span></label>
                                    <select 
                                            class="form-control weekdays" 
                                            multiple="" 
                                            name="week_day_id"
                                            id="week_day_id"
                                            required
                                            data-placeholder="@lang('front.TITLE_SELECT_TEXT')"
                                            data-error="@lang('front.ERR_WEEKDAY_REQUIRED')" 
                                        >
                                            @if(!empty($weekdays) && sizeof($weekdays) > 0)
                                            @foreach($weekdays as $weekday)
                                             plan_options += `<option value="{{ $weekday->id }}" @if($weekday->id<=5) selected @endif>{{ $weekday->day }}</option>`;
                                            @endforeach
                                            @endif
                                        </select>
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_week_day_id"></li>
                                            </ul>
                                        </span>
                                </div>
                            </div>
                            <div class="col-sm-4 toggle_text" style="display: block"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_STARTDATE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="start_date" 
                                        class="form-control chk"
                                        id="start_date"  
                                        autocomplete="off"
                                        required
                                         value="{{ date('d/m/Y') }}" 
                                        data-error="@lang('front.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_start_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4 toggle_text" style="display: block">
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_ENDDATE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="end_date" 
                                        class="form-control chk"
                                        id="end_date"  
                                        autocomplete="off"
                                        required
                                        value="{{ date('d/m/Y', strtotime('+7 days')) }}" 
                                        data-error="@lang('front.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_end_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4 toggle_text" style="display: block">
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_TIME_FROM') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="from_time" 
                                        class="form-control timepicker"
                                        id="from_time"  
                                        autocomplete="off"
                                        required
                                        value="06:00:00" 
                                        data-error="@lang('front.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_from_time"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4 toggle_text" style="display: block"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_TIME_TO') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="to_time" 
                                        class="form-control timepicker"
                                        id="to_time"  
                                        autocomplete="off"
                                        value="21:00:00" 
                                        required
                                        data-error="@lang('front.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_to_time"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                           
                        </div> 
                    </div><!-- /.card-body -->
                    <div class="card-footer">
                        <button type="button" class="btn btn-success" onclick="getDoctorTimeFrames()">@lang('front.TITLE_SEARCH_TEXT')</button>
                    </div>

                    <div class="table-responsive table_bottom" id="doctor_duty_rosters">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>    
@endsection
@section('scripts') 
<script type="text/javascript" src="{{ asset('assets/plugins/timepicker/bootstrap-timepicker.min.js') }}"></script>
<!-- <script type="text/javascript" src="{{ asset('assets/plugins/datepicker/bootstrap-datepicker.de.js') }}"></script> -->
<script type="text/javascript"
        src="http://www.ubalt.edu/lib/jquery-ui-1.8.5.custom/development-bundle/ui/i18n/jquery.ui.datepicker-de.js">
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/web/js/appointment.js') }}"></script>
@endsection