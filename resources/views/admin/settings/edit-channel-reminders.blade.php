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
                    
                    <form id="reminderSettingForm" role="form" data-toggle="validator" action="{{ route($modulePath.'updateReminder', [base64_encode(base64_encode($setting->id))]) }}">                   
                        <div class="card-body">
                             <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SETTING_KEY') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="setting_key" 
                                        value="{{ $setting->setting_key }}"  
                                        class="form-control" 
                                        disabled
                                    >
                                    <!-- <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_setting_key"></li>
                                        </ul>
                                    </span> -->
                                </div>
                            </div>
                        </div>

                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="p-0 form-group"> 
                                        <label class="theme-blue" >@lang('admin.TITLE_CHOICE_OF_CHANNELS_SETTING')</label>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                     <div class="row"> 
                                       
                                              <div class="col-sm-1">
                                                 <label class="form-check-label" for="status">@lang('admin.TITLE_RECOMMEND_SMS_SETTING')</label>
                                                <input 
                                                type="radio" 
                                                class="form-check-input" 
                                                name="choice_of_change" 
                                                value="sms" 
                                                style="margin-left: 10px" 
                                                {{ !empty($channel_reminders->choice_of_channels) && $channel_reminders->choice_of_channels=='sms'?'checked':''}}
                                                >
                                             
                                          </div>
                                          <div class="col-sm-1">
                                              <label class="form-check-label" for="status">@lang('admin.TITLE_RECOMMEND_EMAIL_SETTING')</label>
                                              <input 
                                                type="radio" 
                                                class="form-check-input" 
                                                name="choice_of_change" 
                                                value="email" 
                                                  style="margin-left: 10px" 
                                                {{ !empty($channel_reminders->choice_of_channels) && $channel_reminders->choice_of_channels=='email'?'checked':''}}
                                                >                                              
                                          </div>
                                        
                                    </div>
                               
                                </div>                            
                            </div>
                            <div class="row" style="display:none">
                               <div class="col-sm-3"> 
                                    <div class="p-0 form-group"> 
                                        <label class="theme-blue">@lang('admin.TITLE_REMINDER_FOR_RECOMMENED_SETTING')</label>
                                    </div>
                                </div>
                               
                                <!--  -->
                                <div class="col-sm-9"> 
                                    <div class="p-0 form-group"> 
                                        <div class="form-check">
                                              <input 
                                                type="checkbox" 
                                                class="form-check-input" 
                                                name="recommend_setting" 
                                                value="1" 
                                                {{ !empty($channel_reminders->holiday_reminder) && $channel_reminders->holiday_reminder==1?'checked':''}}
                                                >
                                              <label class="form-check-label" for="status">@lang('admin.TITLE_RECOMMEND_WEEKENDS_SETTING')/@lang('admin.TITLE_RECOMMEND_HOLIDAY_SETTING')</label>
                                        </div>  
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                               <div class="col-sm-3"> 
                                    <div class="p-0 form-group">
                                        <label class="theme-blue">@lang('admin.TITLE_NOTIFY_TIME')</label>
                                    </div>
                                </div>
                               
                                <!--  -->
                                <div class="col-sm-9"> 
                                    <div class="p-0  form-group">
                                        <div class="form-check">
                                        <input 
                                                type="time" 
                                                class="form-check-input form-control timepicker" 
                                                name="notify_time" 
                                                value="{{$channel_reminders->notify_time ?? '' }}" 
                                                required
                                                >
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_notify_time">@lang('admin.ERR_NOTIFICATION_TIME_REQUIRED')</li>
                                            </ul>
                                        </span>  
                                        </div>                                    
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_DEFAULT_TEXT_PUSH_REMINDER_SETTING') <span class="required">*</span></label> 
                                    </div>
                                </div>
                                <div class="col-sm-9"> 
                                    <div class="form-group"><textarea
                                            type="text" 
                                            name="default_push_text" 
                                            class="form-control" 
                                            id="default_text"
                                            data-error="@lang('admin.ERR_DEFAULT_TEXT_REQUIRED')"
                                        >{{$channel_reminders->reminder_push_notification_text ?? '' }}</textarea>
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_default_push_text">@lang('admin.ERR_DEFAULT_TEXT_REQUIRED')</li>
                                            </ul>
                                        </span>
                                    </div>
                                </div> 
                            </div> 
                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_DEFAULT_TEXT_MAIL_REMINDER_SETTING') <span class="required">*</span></label> 
                                    </div>
                                </div>
                                 <div class="col-sm-9"> 
                                    <div class="form-group">
                                        <textarea
                                            type="text" 
                                            name="default_mail_text" 
                                            class="form-control" 
                                            id="default_text"
                                            data-error="@lang('admin.ERR_DEFAULT_TEXT_REQUIRED')"
                                        >{{$channel_reminders->reminder_mail_notification_text ?? ''}}</textarea>
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="">@lang('admin.ERR_DEFAULT_TEXT_REQUIRED')</li>
                                            </ul>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_DEFAULT_TEXT_SMS_REMINDER_SETTING') <span class="required">*</span></label> 
                                    </div>
                                </div>
                                <div class="col-sm-9"> 
                                    <textarea
                                    type="text" 
                                    name="default_sms_text" 
                                    class="form-control" 
                                    id="default_sms_text"
                                    data-error="@lang('admin.ERR_DEFAULT_TEXT_REQUIRED')"
                                    onkeyup="countChar(this)"
                                    >{{$channel_reminders->reminder_sms_notification_text ?? ''}}</textarea>
                                    <div id="sms_char_num"></div>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="">@lang('admin.ERR_DEFAULT_TEXT_REQUIRED')</li>
                                        </ul>
                                    </span>
                                </div>                                
                            </div>
                            <hr/>
                            <div class="row">
                                <div class="col-sm-12">                                 
                                       <h4><label class="theme-blue"> 
                                        @lang('admin.TITLE_GENERAL_REMINDER') </label> </h4>
                                </div>
                            </div>
                           
                            <div class="row">                                                     
                                <div class="col-sm-3">  <label class="theme-blue"> 
                                @lang('admin.TITLE_PERIOD_OF_REMINDER_GENERAL_REMINDER') <span class="required">*</span></label></div>
                                <div class="col-sm-9"  > 
                                    <div class="row">                                       
                                       <div class="col-sm-2 form-group">  
                                            <input                                       
                                                type="text" 
                                                name="general_period"
                                                id="general_period" 
                                                class="form-control number" 
                                                required 
                                                value="{{ $channel_reminders->general_period ?? ''}}" 
                                            >
                                        </div>
                                        <div class="col-sm-2"  >       
                                            <select                                        
                                            class="form-control" 
                                            name="general_period_frequency_type"
                                            id="general_period_frequency_type"              
                                            ><option value="day" {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                <option value="month" {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                <option value="year" {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                <option value="week" {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">                                                     
                                <div class="col-sm-3"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_NEW_REMINDER') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-9"  >
                                    <div class="row"> 
                                        <div class="col-sm-2 form-group">
                                            <input                                           
                                                type="text" 
                                                name="general_new_frequency"
                                                id="general_new_frequency" 
                                                required
                                                class="form-control number"  
                                                value="{{ $channel_reminders->general_new_frequency ?? ''}}" 
                                            >
                                        </div>
                                         <div class="col-sm-2">                  
                                             <select 
                                                class="form-control" 
                                                name="general_new_frequency_type"
                                                id="general_new_frequency_type"
                                                >
                                                    <option value="day" {{ !empty($channel_reminders->general_new_frequency_type) && $channel_reminders->general_new_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->general_new_frequency_type) && $channel_reminders->general_new_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year"{{ !empty($channel_reminders->general_new_frequency_type) && $channel_reminders->general_new_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                    <option value="week" {{ !empty($channel_reminders->general_new_frequency_type) && $channel_reminders->general_new_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                            </select> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">                                                     
                                <div class="col-sm-3"> 
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_FIRST_REMINDER') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-9"> 
                                    <div class="row">
                                    <div class="col-sm-2"  > 
                                        <div class="form-group">
                                            <input 
                                                type="text" 
                                                name="general_first_frequency"
                                                id="general_first_frequency" 
                                                class="form-control number"  
                                                required
                                                value="{{ $channel_reminders->general_first_frequency ?? '' }}" 
                                            >
                                        </div>
                                    </div>
                                    <div class="col-sm-2" > 
                                        <div class="form-group">                  
                                             <select                                            
                                                class="form-control" 
                                                name="general_first_frequency_type"
                                                id="general_first_frequency_type"
                                                >
                                                     <option value="day" {{ !empty($channel_reminders->general_first_frequency_type) && $channel_reminders->general_first_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->general_first_frequency_type) && $channel_reminders->general_first_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year"{{ !empty($channel_reminders->general_first_frequency_type) && $channel_reminders->general_first_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                    <option value="week" {{ !empty($channel_reminders->general_first_frequency_type) && $channel_reminders->general_first_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>

                                            </select> 
                                        </div>
                                    </div> 
                                    </div>
                                </div>
                            </div>
                            <div class="row">                                                     
                                <div class="col-sm-3"> 
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_TIME_INTERVAL') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-9">
                                     <div class="row">
                                <div class="col-sm-2"  > 
                                    <div class="form-group">
                                        <input                                           
                                            type="text" 
                                            name="general_time_interval"
                                            id="general_time_interval" 
                                            class="form-control number"  
                                            required
                                             value="{{ $channel_reminders->general_time_interval ?? '' }}" 
                                        >
                                    </div>
                                </div> 
                                 <div class="col-sm-2" > 
                                        <div class="form-group">                  
                                             <select                                            
                                                class="form-control" 
                                                name="general_time_interval_frequency_type"
                                                id="general_time_interval_frequency_type"
                                                >
                                                   
                                                     <option value="day" {{ !empty($channel_reminders->general_time_interval_frequency_type) && $channel_reminders->general_time_interval_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->general_time_interval_frequency_type) && $channel_reminders->general_time_interval_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year"{{ !empty($channel_reminders->general_time_interval_frequency_type) && $channel_reminders->general_time_interval_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                    <option value="week" {{ !empty($channel_reminders->general_time_interval_frequency_type) && $channel_reminders->general_time_interval_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                   
                                            </select> 
                                        </div>
                                    </div> 
                                    </div> 
                                    </div>                         
                            </div>
                            <div class="row">                                                     
                                <div class="col-sm-3"> 
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_NUMBER_OF_INTERVAL') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-2"  > 
                                    <div class="form-group">
                                        <input                                           
                                            type="text" 
                                            name="general_number_of_interval"
                                            id="general_number_of_interval" 
                                            class="form-control number"  
                                            required
                                             value="{{ $channel_reminders->general_number_of_interval ?? '' }}" 
                                        >
                                    </div>
                                </div>                           
                            </div>

                            <!----------general end cycle------------>

                             <div class="row">                                                     
                                <div class="col-sm-3"> 
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_END_CYCLE') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-9">
                                     <div class="row">
                                <div class="col-sm-2"  > 
                                    <div class="form-group">
                                        <input                                           
                                            type="text" 
                                            name="general_end_cycle"
                                            id="general_end_cycle" 
                                            class="form-control number"  
                                            required
                                             value="{{ ($channel_reminders->general_end_cycle && $channel_reminders->general_end_cycle!=0) ? $channel_reminders->general_end_cycle:'' }}" 
                                        >
                                    </div>
                                </div> 
                                 <div class="col-sm-2" > 
                                        <div class="form-group">                  
                                             <select                                            
                                                class="form-control" 
                                                name="general_end_cycle_frequency_type"
                                                id="general_end_cycle_frequency_type"
                                                >
                                                   
                                                     <option value="day" {{ !empty($channel_reminders->general_end_cycle_frequency_type) && $channel_reminders->general_end_cycle_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->general_end_cycle_frequency_type) && $channel_reminders->general_end_cycle_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year"{{ !empty($channel_reminders->general_end_cycle_frequency_type) && $channel_reminders->general_end_cycle_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                    <option value="week" {{ !empty($channel_reminders->general_end_cycle_frequency_type) && $channel_reminders->general_end_cycle_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                   
                                            </select> 
                                        </div>
                                    </div> 
                                    </div> 
                                    </div>                         
                            </div>
                            <!----------general end cycle------------>


                            <hr/>
                            <div class="row">
                                <div class="col-sm-12">                                 
                                       <h4><label class="theme-blue"> 
                                        @lang('admin.TITLE_CHECKUP_REMINDER') </label> </h4>
                                </div>
                            </div>
                            <div class="row">                                                     
                                <div class="col-sm-3">  <label class="theme-blue"> 
                                @lang('admin.TITLE_PERIOD_OF_REMINDER_CONTROL') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-9"  > 
                                    <div class="row">                                        
                                       <div class="col-sm-2 form-group">  
                                            <input                                       
                                                type="text" 
                                                name="checkup_period_controls"
                                                id="checkup_period_controls" 
                                                class="form-control number" 
                                                required 
                                                value="{{ $channel_reminders->checkup_period_controls ?? '' }}"
                                            >
                                        </div>
                                            <div class="col-sm-2"  >       
                                            <select                                        
                                            class="form-control" 
                                            name="checkup_period_frequency_type"
                                            id="checkup_period_frequency_type"              
                                            >
                                           
                                            <option value="day" {{ !empty($channel_reminders->checkup_period_frequency_type) && $channel_reminders->checkup_period_frequency_type == 'day' ? 'selected' : '' }}
                                               >@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                <option value="month" {{ !empty($channel_reminders->checkup_period_frequency_type) && $channel_reminders->checkup_period_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                <option value="year" {{ !empty($channel_reminders->checkup_period_frequency_type) && $channel_reminders->checkup_period_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                <option value="week" {{ !empty($channel_reminders->checkup_period_frequency_type) && $channel_reminders->checkup_period_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="row">                                                     
                                <div class="col-sm-3"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_NEW_REMINDER') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-9"  >
                                    <div class="row"> 
                                        <div class="col-sm-2 form-group">
                                            <input                                           
                                                type="text" 
                                                name="checkup_new_frequency"
                                                id="checkup_new_frequency" 
                                                required
                                                class="form-control number"  
                                                value="{{ $channel_reminders->checkup_new_frequency ?? '' }}" 
                                            >
                                        </div>
                                         <div class="col-sm-2">                  
                                             <select 
                                                class="form-control" 
                                                name="checkup_new_frequency_type"
                                                id="checkup_new_frequency_type"
                                                >
                                                    <option value="day" {{ !empty($channel_reminders->checkup_new_frequency_type) && $channel_reminders->checkup_new_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->checkup_new_frequency_type) && $channel_reminders->checkup_new_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year"{{ !empty($channel_reminders->checkup_new_frequency_type) && $channel_reminders->checkup_new_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                    <option value="week" {{ !empty($channel_reminders->checkup_new_frequency_type) && $channel_reminders->checkup_new_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                            </select> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">                                                     
                                <div class="col-sm-3"> 
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_FIRST_REMINDER') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-9"> 
                                    <div class="row">
                                    <div class="col-sm-2"  > 
                                        <div class="form-group">
                                            <input 
                                                type="text" 
                                                name="checkup_first_frequency"
                                                id="checkup_first_frequency" 
                                                class="form-control number"  
                                                required
                                                value="{{ $channel_reminders->checkup_first_frequency ?? '' }}" 
                                            >
                                        </div>
                                    </div>
                                    <div class="col-sm-2" > 
                                        <div class="form-group">                  
                                             <select                                            
                                                class="form-control" 
                                                name="checkup_first_frequency_type"
                                                id="checkup_first_frequency_type"
                                                >
                                                     <option value="day" {{ !empty($channel_reminders->checkup_first_frequency_type) && $channel_reminders->checkup_first_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->checkup_first_frequency_type) && $channel_reminders->checkup_first_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year"{{ !empty($channel_reminders->checkup_first_frequency_type) && $channel_reminders->checkup_first_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                    <option value="week" {{ !empty($channel_reminders->checkup_first_frequency_type) && $channel_reminders->checkup_first_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>

                                            </select> 
                                        </div>
                                    </div> 
                                    </div>
                                </div>
                            </div>
                            <div class="row">                                                     
                                <div class="col-sm-3"> 
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_TIME_INTERVAL') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-9">
                                     <div class="row">
                                <div class="col-sm-2"  > 
                                    <div class="form-group">
                                        <input                                           
                                            type="text" 
                                            name="checkup_time_interval"
                                            id="checkup_time_interval" 
                                            class="form-control number"  
                                            required
                                             value="{{ $channel_reminders->checkup_time_interval ?? '' }}" 
                                        >
                                    </div>
                                </div> 
                                 <div class="col-sm-2" > 
                                        <div class="form-group">                  
                                             <select                                            
                                                class="form-control" 
                                                name="checkup_time_interval_frequency_type"
                                                id="checkup_time_interval_frequency_type"
                                                >
                                                   
                                                     <option value="day" {{ !empty($channel_reminders->checkup_time_interval_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->checkup_time_interval_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year"{{ !empty($channel_reminders->checkup_time_interval_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                    <option value="week" {{ !empty($channel_reminders->checkup_time_interval_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                   
                                            </select> 
                                        </div>
                                    </div> 
                                    </div> 
                                    </div>                         
                            </div>
                            <div class="row">                                                     
                                <div class="col-sm-3"> 
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_NUMBER_OF_INTERVAL') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-2"  > 
                                    <div class="form-group">
                                        <input                                           
                                            type="text" 
                                            name="checkup_number_of_interval"
                                            id="checkup_number_of_interval" 
                                            class="form-control number"  
                                            required
                                             value="{{ $channel_reminders->checkup_number_of_interval ?? '' }}" 
                                        >
                                    </div>
                                </div>                           
                            </div>

                            <!--------checkup end cycle----------------->
                            <div class="row">                                                     
                                <div class="col-sm-3"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_END_CYCLE') <span class="required">*</span></label> 
                                </div>
                                <div class="col-sm-9">
                                    <div class="row">
                                        <div class="col-sm-2"  > 
                                            <div class="form-group">
                                                <input                                           
                                                    type="text" 
                                                    name="checkup_end_cycle"
                                                    id="checkup_end_cycle" 
                                                    class="form-control number"  
                                                    required
                                                     value="{{ ($channel_reminders->checkup_end_cycle && $channel_reminders->checkup_end_cycle!=0) ? $channel_reminders->checkup_end_cycle:'' }}" 
                                                >
                                            </div>
                                        </div> 
                                        <div class="col-sm-2" > 
                                                <div class="form-group">                  
                                                     <select                                            
                                                        class="form-control" 
                                                        name="checkup_end_cycle_frequency_type"
                                                        id="checkup_end_cycle_frequency_type"
                                                        >
                                                           
                                                             <option value="day" {{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_end_cycle_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                            <option value="month" {{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_end_cycle_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                            <option value="year"{{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_end_cycle_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                            <option value="week" {{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_end_cycle_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                           
                                                    </select> 
                                                </div>
                                        </div> 
                                    </div> 
                                </div>                         
                            </div>

                            <!--------checkup end cycle----------------->

                            <div class="row">
                                <div class="col-sm-12"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_SETTING_STATUS') <span class="required">*</span></label>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="status"
                                             name="status" value="1" @if(!empty($setting->status) && $setting->status==1) checked @endif
                                            >
                                            <label class="form-check-label" for="status">@lang('admin.TITLE_SETTING_STATUS_ACTIVE')</label>
                                          </div>
                                    </div> 
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" id="btn_sub" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                            <!-- <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button> -->
                            <!-- Roshani hiden this button trello 325 - f - on 9 april 2025  -->
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
function countChar(val) {
  var len = val.value.length;
  if (len >= 85) {
    val.value = val.value.substring(0, 85);
  } else {
    $('#sms_char_num').text(85 - len);
  }
};

</script>
<script type="text/javascript" src="{{ url('assets/admin/js/settings/edit-update-reminder.js') }}"></script>
@endsection