@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }} 
@endsection
@section('style') 
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/timepicker/bootstrap-timepicker.min.css') }}">
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
                    <form id="rosterForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.update', [base64_encode(base64_encode($roster->id))]) }}">
                        <input type="hidden" name="_method" value="PUT">

                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6"> 
                                    <div class="form-group"> 
                                        <label>@lang('admin.TITLE_ROSTER_DOCTOR')</label> 
                                        <select 
                                            name="doctor_id" 
                                            required
                                            data-error="@lang('admin.ERR_DOCTOR_ID_REQUIRED')"
                                            class="form-control" 
                                            >
                                            <option value="">@lang('admin.TITLE_ROSTER_SELECT_DOCTOR')</option>
                                            @foreach($user as $users)
                                            <option value="{{ $users->id }}" @if($users->id==$roster->doctor_id) selected @endif>{{ $users->first_name .' '. $users->last_name}}</option>
                                            @endforeach
                                        </select> 
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_doctor_id"></li>
                                            </ul>
                                        </span>
                                    </div>
                                </div>
                                 <div class="col-sm-6"> 
                                    <div class="form-group"> 
                                        <label>@lang('admin.TITLE_ROSTER_APPOINTMENT_TYPE')</label> 
                                        <select 
                                            name="appointment_type_id" 
                                            id="appointment_type_id" 
                                            required
                                            data-error="@lang('admin.ERR_APPOINTMENT_TYPE_ID_REQUIRED')"
                                            class="form-control" 
                                             onchange="showTimeFrames(0)" 
                                            >
                                            <option value="" data-duration="0">@lang('admin.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE')</option>
                                            @foreach($appointment as $appointments)
                                            <option value="{{ $appointments->id }}" @if($appointments->id==$roster->appointment_type_id) selected @endif data-duration="{{ $appointments->duration}}">{{ $appointments->name }} ({{ $appointments->duration }})</option>
                                            @endforeach
                                        </select> 
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_appointment_type_id"></li>
                                            </ul>
                                        </span>
                                    </div>
                                </div>
                            </div>

            <div class="with-border col-md-12">
                <h4 class="">@lang('admin.TITLE_ROSTER_WEEKDAY_HEADING')</h4>
            </div>
            <div class="col-md-12">
                <table class="table mb-0 border-none" id="plan-table">
                    <thead class="theme-bg-blue-light-opacity-15">
                        <tr class="heading-tr">                                  
                            <th width="150px">@lang('admin.TITLE_ROSTER_WEEKDAY')</th>
                            <th width="150px">@lang('admin.TITLE_ROSTER_TIME_FROM')</th>
                            <th width="150px">@lang('admin.TITLE_ROSTER_TIME_TO')</th>
                            <th width="">@lang('admin.TITLE_ROSTER_TIME_FRAME')</th>
                            <th width="50px"></th>
                        </tr>
                    </thead>
                    <tbody class="no-border">
                @php
                 $k = 0;
                 $time_frame = array();
                @endphp
                @foreach($roster->hasWeekDays as $hasWeekDay)              
                    <tr class="inner-td add_plan_area plan">                    
                    <td>
                    <div class="form-group"> 
                        <select 
                            class="form-control weekdays" 
                            name="weekday[{{$k}}][week_day_id]"
                            id="week_day_id_{{$k}}"
                            required
                            data-placeholder="@lang('admin.TITLE_SELECT_TEXT')"
                            data-error="@lang('admin.ERR_WEEKDAY_REQUIRED')" 
                        >
                            <option value="">Select</option>
                            @if(!empty($weekdays) && sizeof($weekdays) > 0)
                            @foreach($weekdays as $weekday)
                             plan_options += `<option value="{{ $weekday->id }}" @if($weekday->id==$hasWeekDay->weekday_id) selected @endif>{{ $weekday->day }}</option>`;
                            @endforeach
                            @endif
                        </select>
                        <span class="help-block invalid-feedback with-errors">
                            <ul class="list-unstyled">
                                <li class="err_weekday[{{$k}}][week_day_id][] err_weekday"></li>
                            </ul>
                        </span>
                    </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input 
                                type="text" 
                                class="form-control timepicker"
                                name="weekday[{{$k}}][from_time]"
                                id="from_time_{{$k}}"
                                value="{{ $hasWeekDay->from_time }}" 
                                onchange="showTimeFrames({{$k}})" 
                                required 
                                data-error="@lang('admin.ERR_TIME_FROM_REQUIRED')." 
                                >
                            <span class="help-block invalid-feedback with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_from_time[{{$k}}][week_day_id][] err_from_time"></li>
                                </ul>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input 
                                type="text" 
                                class="form-control timepicker" 
                                name="weekday[{{$k}}][to_time]"
                                id="to_time_{{$k}}" 
                                value="{{ $hasWeekDay->to_time }}" 
                                onchange="showTimeFrames({{$k}})" 
                                required 
                                data-error="@lang('admin.ERR_TIME_TO_REQUIRED')." 
                                >
                            <span class="help-block invalid-feedback with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_to_time"></li>
                                </ul>
                            </span>
                        </div>
                    </td>
                    <td>
                     <div class="form-group time_frames">
                        <select 
                            name="weekday[{{$k}}][time_frames][]"
                            required
                            class="form-control select2" 
                            id="time_frames_{{$k}}"
                            multiple="multiple" 
                            data-placeholder="@lang('admin.TITLE_SELECT_TEXT') @lang('admin.TITLE_ROSTER_TIME_FRAME')"
                            data-error="@lang('admin.ERR_TIME_FRAME_REQUIRED')"
                            style="width: 100%;"
                            >
                           <!--  <option value="">Select Time Frames</option> -->
                        </select> 
                        <span class="help-block invalid-feedback with-errors">
                            <ul class="list-unstyled">
                                <li class="err_time_frames"></li>
                            </ul>
                        </span>
                    </div> 
                    </td>
                    <td>
                        <p class="m-0 red bold deletebtn" style="display:block;cursor:pointer" onclick="return deletePlan(this)"  id="{{$k}}" style="cursor:pointer">Remove</p>
                    </td>
                    </tr>
                    @php
                        if(!empty($hasWeekDay->hasTimeFrames)){
                            $time_frame[$hasWeekDay->weekday_id] = [];
                            foreach($hasWeekDay->hasTimeFrames as $key_tf=>$hasTimeFrame)
                            {
                                $time_frame[$hasWeekDay->weekday_id][$key_tf] = date("H:i",strtotime($hasTimeFrame->time_frame));
                            }
                        }
                        $k++;
                    @endphp
                    @endforeach 
                   
                     <input type="hidden" name="total_items" id="total_items" value="{{$k}}">
                    </tbody>
                </table>
                <div class="col-md-8">
                    <a href="javascript:void(0)" class="add-more-btn"
                                    onclick="return addPlan()" style="cursor: pointer;">
                        <span class="mr-2"><img src="{{ url('/assets/admin/images') }}/icons/green_plus.svg" alt=" view"></span> @lang('admin.TITLE_ADD_BUTTON')
                    </a>
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
<script type="text/javascript" src="{{ asset('assets/plugins/timepicker/bootstrap-timepicker.min.js') }}"></script>
<script type="text/javascript">
    var removeText = "{{ __('admin.TITLE_REMOVE_BUTTON') }}";
    var time_frames_data = "{{ json_encode($time_frame) }}";
    var time_frames_data = time_frames_data.replace(/&quot;/g, '"');
    var parsedJson = jQuery.parseJSON(time_frames_data);
    // console.log(time_frames_data);
    //console.log(parsedJson);

    // PLAN OPTIONS
    var plan_options = '<option value="">Select</option>';
    @if(!empty($weekdays) && sizeof($weekdays) > 0)
    @foreach($weekdays as $weekday)
     plan_options += `<option value="{{ $weekday->id }}">{{ $weekday->day }}</option>`;
    @endforeach
    @endif
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/roster/create-edit.js') }}"></script>
@endsection