@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content') 
<!-- Main content -->  
<?php 

?>      
<section class="content">
<div class="container-fluid">
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- jquery validation -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">@lang('admin.TITLE_ASSISTANT_APPOINMENT_TITLE')</h3>
                   <!--  <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button> -->
                </div>
                    <input type="hidden" name="_method" value="PUT">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_QR_CODE')</label>
                                    <div class="form-check app_detail"> 
                                        <span id="qr_code">
                                            
                                        </span> 
                                           
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label>@lang('admin.TITLE_APPOINTMENT_PATIENT')</label> 
                                    <input 
                                        disabled
                                        type="text" 
                                        name="patients" 
                                        class="form-control"
                                        id="patients"  
                                        value="{{ $patient->first_name .' '. $patient->family_name}}"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_patient_id"></li>
                                        </ul>
                                    </span> 
                                </div>
                            
                                @if (Auth::user()->hasRole('super-admin')  || (Auth::user()->hasRole('Assistant')) || (Auth::user()->hasRole('Doctor')) )
                                <div class="form-group"> 
                                    <label>@lang('admin.TITLE_APPOINTMENT_DOCTOR')</label> 
                                    <select
                                        style="width: 100%" 
                                        disabled
                                        name="doctor_id" 
                                        id="doctor_id"  
                                        required
                                        data-error="@lang('admin.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                        class="form-control select2" 
                                        
                                        >
                                        <option value="">@lang('admin.TITLE_SELECT_DOCTOR')</option>
                                        @foreach($user as $users)
                                        <option value="{{ $users->id }}" lang="{{ $users->status }}"  @if($users->id==$appointment->doctor_id) selected @endif>{{ $users->first_name .' '. $users->last_name}}</option> 
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_user_id"></li>
                                        </ul>
                                    </span> 
                                </div>
                                @endif
                            </div>
                           
                        </div>
                        <div class="row">
                            
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label>@lang('admin.TITLE_APPOINTMENT_TYPE')</label>  
                                    <select 
                                        style="width: 100%"
                                        disabled
                                        name="appointment_type_id" 
                                        id="appointment_type_id"  
                                        required
                                        data-error="@lang('admin.ERR_APPOINTMENT_TYPE_REQUIRED')"
                                        class="form-control select2" 
                                        onchange ="getDoctorTimeFrames();" 

                                        >
                                        <option value="">@lang('admin.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE')</option>
                                        @foreach($appointment_type as $appointment_types)
                                        <option value="{{ $appointment_types->id }}" @if($appointment_types->id==$appointment->appointment_type_id) selected @endif>{{ $appointment_types->name}} ({{ $appointment_types->duration }})</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_appointment_type_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_DATE') <span class="required">*</span></label> 
                                    <input 
                                        disabled
                                        type="text" 
                                        name="date" 
                                        class="form-control"
                                        id="appointment_date"  
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                        onchange ="getDoctorTimeFrames()" 
                                        value="{{ date('Y-m-d',strtotime($appointment->start_date)) }}"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                          
                        </div> 
                        <div class="row">
                              
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>@lang('admin.TITLE_APPOINTMENT_TIME_FRAME') <span class="required">*</span></label>  
                                    <select 
                                        disabled
                                        name="time_frame"
                                        id="time_frame"
                                        class="form-control active_status" 
                                        data-placeholder="@lang('admin.TITLE_SELECT_TEXT') @lang('admin.TITLE_ROSTER_TIME_FRAME')"
                                        onchange="assignValueToText()" 
                                        style="width: 100%;"
                                        >
                                        <option selected>{{ date('H:i',strtotime($appointment->start_date)) }}</option>
                                       <!--  <option value="">Select Time Frames</option> -->
                                    </select> 
                                    <input type="time" 
                                        name="time_frame"
                                        id="time_frame1"  
                                        class="form-control inactive_status timepicker"  
                                        maxlength="12" 
                                       value="{{ date('H:i',strtotime($appointment->start_date)) }}"
                                       style="display: none" 
                                       />
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_time_frame"></li>
                                        </ul>
                                    </span>
                                </div> 
                            </div>
                             <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_NOTE')</label> 
                                    <textarea
                                        disabled
                                        type="text" 
                                        name="notes" 
                                        class="form-control" 
                                    >{{ $appointment->notes }}</textarea>
                                    <!--  required
                                        data-error="@lang('admin.ERR_APPOINTMENT_NOTE_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_notes"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                           
                        </div>   
                        <div class="row">
                            
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_STATUS') </label>
                                    <div class="form-check"> 
                                        <input 
                                            disabled
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status"  
                                            value="1" @if(!empty($appointment->status) && $appointment->status==1) checked @endif
                                        >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_STATUS_ACTIVE')</label>
                                    </div>
                                </div>
                            </div>

                            @if(sizeof($services)>0)
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_EXAMINATIONS_TEXT') : </label>
                                    @foreach($services as $services_key => $services_val)
                                    @if($services_val['checked'] == 1)
                                        @php $checked = 'checked'; @endphp
                                    @else
                                        @php $checked = ''; @endphp   
                                    @endif
                                    <div class="form-check"> 
                                        <input disabled type='checkbox' {{$checked}} class='form-check-input' name='app_services[]'
                                            name='status' value="{{$services_val['assignedExamination']->id}}" 
                                            >
                                        <label class='form-check-label' for='status'>{{$services_val['assignedExamination']->name}}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            
                        </div>

                    </div><!-- /.card-body -->

                   <!--  <div class="card-footer">
                        <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                        <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
                    </div> -->
            </div>
        </div>
    </div>
</div>    
</section>
@endsection

@section('scripts')
<script type="text/javascript">
    var QRCODE = '{{$qr_code}}';

    $.getScript( 'https://puremed.biz/assets/admin/js/dashboard/qrcode.js', function( data, textStatus, jqxhr ) {
       $("#qr_code").qrcode(QRCODE);
    } );


    var sel_time_frame = "{{ date('H:i',strtotime($appointment->start_date)) }}";
    $(function(){
        getDoctorTimeFrames();
    });


</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>

@endsection