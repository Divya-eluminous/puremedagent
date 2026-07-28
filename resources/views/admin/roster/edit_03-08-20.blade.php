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
                                @if (Auth::user()->hasRole('super-admin')) 
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
                                @endif
                                </div>
                                 <div class="col-sm-6"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        Default Time duration (In Mins) </label>
                                        <input type="text" value="{{ $default_time_duration ?? '' }}" disabled="" name="default_time_duration"  id="default_time_duration" class="form-control" maxlength="250">
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
                            <th width="150px">@lang('admin.TITLE_ROSTER_DATE')</th>
                            <th width="150px">@lang('admin.TITLE_ROSTER_TIME_FROM')</th>
                            <th width="150px">@lang('admin.TITLE_ROSTER_TIME_TO')</th>
                            <th width="">@lang('admin.TITLE_ROSTER_TIME_FRAME')</th>
                            <th width="50px"></th>
                        </tr>
                    </thead>
                    <tbody class="no-border">
                @php
                 $k = 0;
                 $time_frame = [];
                 $tmpDisabledDates_multi = [];
                @endphp
                @foreach($roster->custom_data['dates'] as $custom_key=>$custom_data)  

                    <tr class="inner-td add_plan_area plan">                    
                    <td>
                    <div class="form-group"> 
                       <div class="form-group">
                            <input 
                                type="text" 
                                name="date_data[{{$k}}][dates]" 
                                class="form-control date"
                                id="dates_{{$k}}" 
                                value="{{ implode(',',$custom_data)}}" 
                                autocomplete="off"
                                required
                                data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                            >
                            <span class="help-block invalid-feedback with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_dates"></li>
                                </ul>
                            </span>
                        </div>
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
                                name="date_data[{{$k}}][to_time]"
                                id="to_time_{{$k}}" 
                                value="{{ $roster->custom_data['from_to'][$custom_key]['to_time'] }}" 
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
                        <p class="m-0 red bold deletebtn" style="display:block;cursor:pointer" onclick="return deletePlan(this)"  id="{{$k}}" style="cursor:pointer">Remove</p>
                    </td>
                    </tr>
                    @php
                        $tmpDisabledDates_multi[$k] = $custom_data;
                        $k++;
                    @endphp
                    @endforeach 
                    @php
                   
                        if(!empty($roster->custom_data['time_frames'])){
                           foreach($roster->custom_data['time_frames'] as $custom_key=>$time_frames)
                           {
                                foreach($time_frames as $key=>$hasTimeFrame)
                                {
                                    $time_frame[$custom_key][] = date("H:i",strtotime($hasTimeFrame->time_frame));
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
   /* var time_frames_data = "{{ json_encode($time_frame) }}";
    var time_frames_data = time_frames_data.replace(/&quot;/g, '"');
    var parsedJson = jQuery.parseJSON(time_frames_data);*/
    //var tmpDisabledDates_multi = "{{ json_encode($tmpDisabledDates_multi) }}";
   // var tmpDisabledDates_multi = tmpDisabledDates_multi.replace(/&quot;/g, '"');
    var parsedJson = JSON.parse('{!! json_encode($time_frame) !!}');
    var tmpDisabledDates_multi = JSON.parse('{!! json_encode($tmpDisabledDates_multi) !!}');

    // console.log(time_frames_data);
    //console.log(parsedJson);

</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/roster/create-edit.js') }}"></script>
@endsection