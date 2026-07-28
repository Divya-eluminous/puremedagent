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
                    <form id="rosterForm" role="form" data-toggle="validator" action="{{ route($modulePath . '.update', [base64_encode(base64_encode($roster->id))]) }}">
                        <input type="hidden" name="_method" value="PUT">

                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                @if (Auth::user()->hasRole('super-admin') || (Auth::user()->hasRole('Assistant')) || (Auth::user()->hasRole('Doctor')))
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
                                            <option value="{{ $users->id }}" @if($users->id == $roster->doctor_id) selected @endif>{{ $users->first_name . ' ' . $users->last_name}}</option>
                                            @endforeach
                                        </select> 
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_doctor_id"></li>
                                            </ul>
                                        </span>
                                    </div>
                                @endif
                                </div>
                                 <div class="col-sm-6"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_DEFAULT_TIME_DURATION') </label>
                                        <input type="text" value="{{ $default_time_duration ?? '' }}" disabled="" name="default_time_duration"  id="default_time_duration" class="form-control" maxlength="250">
                                        <input type="hidden" value="{{ $oldTimeDuration ?? '' }}" disabled="" name="oldTimeDuration"  id="oldTimeDuration" class="form-control">
                                    </div>
                                </div>
                            </div>

            <div class="with-border col-md-12">
                <h4 class="">@lang('admin.TITLE_ROSTER_DATE_HEADING')</h4>
            </div>
            <div class="col-md-12">
                <table class="table mb-0 border-none" id="plan-table">
                    <thead class="theme-bg-blue-light-opacity-15">
                        <tr class="heading-tr">                                  
                            <th width="150px">@lang('admin.TITLE_ROSTER_WEEKDAY')</th>
                            <th width="150px">@lang('admin.TITLE_ROSTER_STARTDATE')</th>
                            <th width="150px">@lang('admin.TITLE_ROSTER_ENDDATE')</th>
                            <th width="100px">@lang('admin.TITLE_ROSTER_TIME_FROM')</th>
                            <th width="100px">@lang('admin.TITLE_ROSTER_TIME_TO')</th>
                            <th width="">@lang('admin.TITLE_ROSTER_TIME_FRAME')</th>
                            <th width="50px"></th>
                        </tr>
                    </thead>
                    <tbody class="no-border">
                @php
$k = 0;
$time_frame = [];
$old_duration = [];
$tmpDisabledDates_multi = [];

// Reindex time_frames by sequential $k matching HTML row order.
// $roster->custom_data['time_frames'] may be keyed by arbitrary $custom_key values
// (e.g. after deletions), but the HTML rows use a sequential 0,1,2... counter,
// so parsedJson must be reindexed to match or rows will display the wrong slots.
$reindexed_time_frames = [];
$sequential_k = 0;
foreach ($roster->custom_data['dates'] as $ck => $cd) {
    if (!empty($roster->custom_data['time_frames'][$ck])) {
        $reindexed_time_frames[$sequential_k] = $roster->custom_data['time_frames'][$ck];
    }
    $sequential_k++;
}
                @endphp

                @foreach($roster->custom_data['dates'] as $custom_key => $custom_data)  
            
                    <tr class="inner-td add_plan_area plan">    
                    <td style="display:none;">
                        <input type="hidden" id="start_date_hidden_{{$k}}" value="{{ $roster->custom_data['from_to'][$custom_key]['start_date'] }}">
                        <input type="hidden" id="end_date_hidden_{{$k}}" value="{{ $roster->custom_data['from_to'][$custom_key]['end_date'] }}">
                    </td>                
                    <td>
                    <div class="form-group"> 
                       <div class="form-group">

                             <select 
                            class="form-control weekdays" 
                            name="date_data[{{$k}}][week_day_id]"
                            id="week_day_id_{{$k}}"
                            required
                            data-placeholder="@lang('admin.TITLE_SELECT_TEXT')"
                            data-error="@lang('admin.ERR_WEEKDAY_REQUIRED')" 
                            >
                                <option value="">@lang('admin.TITLE_SELECT_TEXT')</option>
                                @if(!empty($weekdays) && sizeof($weekdays) > 0)
                                @foreach($weekdays as $weekday)
                                 plan_options += `<option value="{{ $weekday->id }}" @if($weekday->id == $roster->custom_data['weekdays'][$custom_key]['week_day_id']) selected @endif>{{ $weekday->day }}</option>`;
                                @endforeach
                                @endif
                            </select>
                            <span class="help-block invalid-feedback with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_weekday[{{$k}}][week_day_id][] err_weekday"></li>
                                </ul>
                            </span>
                        </div>
                    </div>
                    </td>
                    <td>
                    <div class="form-group">
                        <input 
                            type="text" 
                            name="date_data[{{$k}}][start_date]" 
                            class="form-control date"
                            id="start_date_{{$k}}" 
                            onchange="selectWeekOnSameDate({{$k}}); showTimeFrames({{$k}},'')"
                            value="{{ $roster->custom_data['from_to'][$custom_key]['start_date'] }}" 
                            autocomplete="off"
                            required
                            data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                        >
                            <!-- onchange="selectWeekOnSameDate({{$k}}); showTimeFrames({{$k}},'new')" -->

                        <span class="help-block invalid-feedback with-errors">
                            <ul class="list-unstyled">
                                <li class="err_start_date"></li>
                            </ul>
                        </span>
                    </div>
                    </td>
                    <td>
                    <div class="form-group">
                        <input 
                            type="text" 
                            name="date_data[{{$k}}][end_date]" 
                            class="form-control date"
                            id="end_date_{{$k}}" 
                            onchange="selectWeekOnSameDate({{$k}}); showTimeFrames({{$k}},'')"
                            value="{{ $roster->custom_data['from_to'][$custom_key]['end_date'] }}" 
                            autocomplete="off"
                            required
                            data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                        >
                            <!-- onchange="selectWeekOnSameDate({{$k}}); showTimeFrames({{$k}},'new')" -->
                        <span class="help-block invalid-feedback with-errors">
                            <ul class="list-unstyled">
                                <li class="err_end_date"></li>
                            </ul>
                        </span>
                    </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input 
                                type="text" 
                                class="form-control timepicker"
                                name="date_data[{{$k}}][from_time]"
                                id="from_time_{{$k}}"
                                value="{{ $roster->custom_data['from_to'][$custom_key]['from_time'] }}" 
                                onchange="showTimeFrames({{$k}},'');" 
                                required 
                                data-error="@lang('admin.ERR_TIME_FROM_REQUIRED')." 
                                >
                                <input type="hidden" id="from_time_hidden_{{$k}}" value="{{ $roster->custom_data['from_to'][$custom_key]['from_time'] }}">
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
                                name="date_data[{{$k}}][to_time]"
                                id="to_time_{{$k}}" 
                                value="{{ $roster->custom_data['from_to'][$custom_key]['to_time'] }}" 
                                onchange="showTimeFrames({{$k}},'');" 
                                required 
                                data-error="@lang('admin.ERR_TIME_TO_REQUIRED')." 
                                >
                                <input type="hidden" id="to_time_hidden_{{$k}}" value="{{ $roster->custom_data['from_to'][$custom_key]['to_time'] }}">
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
                            name="date_data[{{$k}}][time_frames][]"
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
                        <p data-href="{{route('admin.roster.destroy', [base64_encode(base64_encode($roster->id))])}}" class="m-0 red bold deletebtn" style="display:block;cursor:pointer" onclick="return deletePlan(this)"  id="{{$k}}" style="cursor:pointer">@lang('admin.TITLE_REMOVE_BUTTON')</p>
                    </td>
                    </tr>
                    @php
    // $tmpDisabledDates_multi[$k] = $custom_data;
    $k++;
                    @endphp
                    @endforeach 

                   
                    @php

// Build $time_frame and $old_duration keyed by sequential $temp_k so they
// align with the HTML row indices (and therefore parsedJson[index] in JS).
if (!empty($reindexed_time_frames)) {
    foreach ($reindexed_time_frames as $temp_k => $time_frames) {
        // collect time frame display values
        foreach ($time_frames as $key => $hasTimeFrame) {
            $time_frame[$temp_k][] = date("H:i", strtotime($hasTimeFrame->time_frame));
        }

        // Infer the duration this row was saved at by taking the MINIMUM gap across
        // all consecutive saved slots — not just the first pair. Using only the first
        // two slots breaks whenever the user deleted a slot near the start: e.g. saved
        // [06:00, 06:20, 06:30, 06:40, 06:50] (06:10 deleted) would yield first-pair-gap
        // of 20min, even though the original step was 10min. The min gap (10) survives
        // any deletion as long as at least one pair of consecutive originals remains.
        $tf_count = count($time_frames);
        if ($tf_count >= 2) {
            $minDiff = null;
            for ($i = 1; $i < $tf_count; $i++) {
                $t0 = strtotime($time_frames[$i - 1]->time_frame);
                $t1 = strtotime($time_frames[$i]->time_frame);
                $diff = abs(($t1 - $t0) / 60);
                if ($diff > 0 && ($minDiff === null || $diff < $minDiff)) {
                    $minDiff = $diff;
                }
            }
            $old_duration[$temp_k] = (int) ($minDiff ?? (isset($default_time_duration) ? $default_time_duration : 5));
        } else {
            // fallback to default duration
            $old_duration[$temp_k] = isset($default_time_duration) ? (int) $default_time_duration : 5;
        }
    }
}

                    @endphp
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
    var selectText = "{{ __('admin.TITLE_SELECT_TEXT') }}";
    var weekDayText = "{{ __('admin.ERR_WEEKDAY_REQUIRED') }}";
    var dateText = "{{ __('admin.ERR_APPOINTMENT_DATE_REQUIRED') }}";
    var fromText = "{{ __('admin.ERR_TIME_FROM_REQUIRED') }}";
    var toText = "{{ __('admin.ERR_TIME_TO_REQUIRED') }}";
    var timeText = "{{ __('admin.ERR_TIME_FRAME_REQUIRED') }}";
   /* var time_frames_data = "{{ json_encode($time_frame) }}";
    var time_frames_data = time_frames_data.replace(/&quot;/g, '"');
    var parsedJson = jQuery.parseJSON(time_frames_data);*/
    //var tmpDisabledDates_multi = "{{ json_encode($tmpDisabledDates_multi) }}";
   // var tmpDisabledDates_multi = tmpDisabledDates_multi.replace(/&quot;/g, '"');
    var parsedJson = JSON.parse('{!! json_encode($time_frame) !!}');
    var oldDurations = JSON.parse('{!! json_encode($old_duration ?? []) !!}');
    //var tmpDisabledDates_multi = JSON.parse('{!! json_encode($tmpDisabledDates_multi) !!}');

    // PLAN OPTIONS
    var plan_options = '<option value="">'+selectText+'</option>';
    @if(!empty($weekdays) && sizeof($weekdays) > 0)
    @foreach($weekdays as $weekday)
     plan_options += `<option value="{{ $weekday->id }}">{{ $weekday->day }}</option>`;
    @endforeach
    @endif

    // console.log(time_frames_data);
    //console.log(parsedJson);

</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/roster/create-edit.js') }}"></script>
@endsection
