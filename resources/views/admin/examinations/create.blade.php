@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content')
<!-- Main content -->  
 
<section class="content">
<div class="container-fluid">      
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- jquery validation -->
            <div class="card card-primary"> 
            <input type="hidden"   name="site_url" id="site_url" value="{{ url('/')}}" />
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button>
                </div>
                
                <form id="examinationForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.store') }}">
                    <input type="hidden" name="specialist_id" id="specialist_id" value="{{$specialist_details->id}}" >
                    <input type="hidden" name="hd_exam_id" id="hd_exam_id" value="">

                      <!------------------Hidden fields started here--added on 22-sept-23----------------------->

                    <input type="hidden" name="hidden_chkReminder" id="hidden_chkReminder" class="chkReminder" value="">

                    <!-------------------Hidden fields added for popup-----general------------>
                    <input type="hidden" name="hidden_reminder_service" id="hidden_reminder_service" value="">
                    <input type="hidden" name="hidden_general_period" id="hidden_general_period" value="">
                    <input type="hidden" name="hidden_general_period_frequency_type" id="hidden_general_period_frequency_type" value="">
                    <input type="hidden" name="hidden_general_new_frequency" id="hidden_general_new_frequency" value="">
                    <input type="hidden" name="hidden_general_new_frequency_type" id="hidden_general_new_frequency_type" value="">
                    <input type="hidden" name="hidden_general_first_frequency" id="hidden_general_first_frequency" value="">
                    <input type="hidden" name="hidden_general_first_frequency_type" id="hidden_general_first_frequency_type" value="">
                    <input type="hidden" name="hidden_general_time_interval" id="hidden_general_time_interval" value="">
                    <input type="hidden" name="hidden_general_time_interval_frequency_type" id="hidden_general_time_interval_frequency_type" value="">
                    <input type="hidden" name="hidden_general_number_of_interval"  id="hidden_general_number_of_interval"  value="">
                    <input type="hidden" name="hidden_general_end_cycle" id="hidden_general_end_cycle" value="">
                    <input type="hidden" name="hidden_general_end_cycle_frequency_type"  id="hidden_general_end_cycle_frequency_type"  value="">

                    <!------------------Hidden field for popup age------------------------------------>
                    <input type="hidden" name="hidden_age_age_from" id="hidden_age_age_from" value="">
                    <input type="hidden" name="hidden_age_age_to" id="hidden_age_age_to" value="">
                    <input type="hidden" name="hidden_age_period_controls" id="hidden_age_period_controls" value="">
                    <input type="hidden" name="hidden_age_period_frequency_type"  id="hidden_age_period_frequency_type" value="">
                    <input type="hidden" name="hidden_age_new_frequency"  id="hidden_age_new_frequency" value="">
                    <input type="hidden" name="hidden_age_new_frequency_type" id="hidden_age_new_frequency_type" value="">
                    <input type="hidden" name="hidden_age_first_frequency" id="hidden_age_first_frequency" value="">
                    <input type="hidden" name="hidden_age_first_frequency_type"  id="hidden_age_first_frequency_type"  value="">
                    <input type="hidden" name="hidden_age_time_interval" id="hidden_age_time_interval" value="">
                    <input type="hidden" name="hidden_age_time_interval_frequency_type" id="hidden_age_time_interval_frequency_type" value="">
                    <input type="hidden" name="hidden_age_number_of_interval" id="hidden_age_number_of_interval" value="">
                    <input type="hidden" name="hidden_age_end_cycle" id="hidden_age_end_cycle" value="">
                    <input type="hidden" name="hidden_age_end_cycle_frequency_type" id="hidden_age_end_cycle_frequency_type" value="">

                    <!------------------Hidden field for checkup age------------------------------------>

                    <input type="hidden" name="hidden_checkup_period_controls" id="hidden_checkup_period_controls" value="">
                    <input type="hidden" name="hidden_checkup_period_frequency_type" id="hidden_checkup_period_frequency_type" value="">
                    <input type="hidden" name="hidden_checkup_new_frequency" id="hidden_checkup_new_frequency" value="">
                    <input type="hidden" name="hidden_checkup_new_frequency_type" id="hidden_checkup_new_frequency_type" value="">
                    <input type="hidden" name="hidden_checkup_first_frequency"  id="hidden_checkup_first_frequency" value="">
                    <input type="hidden" name="hidden_checkup_first_frequency_type"  id="hidden_checkup_first_frequency_type" value="">
                    <input type="hidden" name="hidden_checkup_time_interval" id="hidden_checkup_time_interval" value="">
                    <input type="hidden" name="hidden_checkup_time_interval_frequency_type" id="hidden_checkup_time_interval_frequency_type" value="">
                    <input type="hidden" name="hidden_checkup_number_of_interval" id="hidden_checkup_number_of_interval" value="">
                    <input type="hidden" name="hidden_checkup_end_cycle" id="hidden_checkup_end_cycle" value="">
                    <input type="hidden" name="hidden_checkup_end_cycle_frequency_type" id="hidden_checkup_end_cycle_frequency_type" value="">

                    <!------------------Hidden fields ended here---added on 22-sept-23---------------------->


                    <div class="card-body">
                         <div class="row">
                            <div class="col-sm-4"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_TEXT') </label>
                                   <select 
                                        class="form-control" 
                                        onchange="SetSession(this)" 
                                        required
                                        name="specialist" 
                                        id="specialist" 
                                        data-error="@lang('admin.ERR_DOCUMENT_TYPE_OF_DOCUMENT')">
                                            <option value="">@lang('admin.TITLE_SPECIALIST_SELECT_TEXT')</option>
                                            <!-- <option @if(empty($specialist_details)) selected @endif value="all">All</option> -->
                                            @if(!empty($specialists) && sizeof($specialists)>0)
                                                @foreach($specialists as $key =>$val)
                                                    <option @if($specialist_details->id == $val['id']) selected @endif value="{{$val['id']}}">{{ucfirst($val['name'])}}</option>
                                                @endforeach    
                                            @endif
                                    </select> 
                                
                                </div>
                            </div>
                            <!--------added below code in 22-sept-23------------------------>
                            <div class="col-sm-1"> 
                                <div class="form-group"> 
                                </div>
                            </div>
                             <div class="col-sm-3"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">&nbsp;</label>
                                    <div class="form-check">
                                          <input 
                                            type="checkbox" 
                                            class="form-check-input setReminder" 
                                            id="show_as_reminder"
                                            name="show_as_reminder" 
                                            >
                                          <label class="form-check-label" for="status">@lang('admin.TITLE_SHOW_AS_REMINDER')</label>
                                    </div>                                      
                                </div>
                            </div>
                              <div class="col-sm-4" id="btn_reminder">
                                <div class="form-group"> 
                                    <label class="theme-blue"> &nbsp; </label>
                                    <button type="button" id="reminderbutton" class="btn fc-button-primary  btn-danger form-control" data-toggle="modal" data-target="#createReminderModal">@lang('admin.TITLE_REMINDER_SERVICE_BTN') </button>
                                </div>
                            </div>

                            <!----------end above code on 22-sept-23---------------------------------------->
                           
                        </div>
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_EXAMINATION_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_EXAM_NAME_REQUIRED')" 
                                        onblur="convertToSlug(this.value)"
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
                                    @lang('admin.TITLE_EXAMINATION_URL') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="url" 
                                        id="url"
                                        class="form-control" 
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_URL_REQUIRED')"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_url"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                 <!------ Aishwarya commented on 2-jun-25------>
                                    <!-- <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_TYPE_DESCRIPTION') <span class="required">*</span></label>  -->
                                    <!------- Aishwarya added on 2-jun-25 ------>
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_TYPE_DESCRIPTION')</label> 
                                    

                                    <!-- commented on 30-may-25 by aishwarya for #350 -->
                                    <!-- <textarea
                                        type="text" 
                                        name="description" 
                                        class="form-control" 
                                        id="description"
                                        required
                                        data-error="@lang('admin.ERR_APPOINTMENT_TYPE_DESCRIPTION_REQUIRED')"
                                    ></textarea> -->
                                    <!-- Removed data-err0r on 30-may-25 by aishwarya -->
                                    <textarea
                                        type="text" 
                                        name="description" 
                                        class="form-control" 
                                        id="description"
                                        required
                                    ></textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_description"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                         <!-- <div class="col-sm-6"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_EXAMINATION_SORTINGORDER') <span class="required">*</span></label>
                                <input type="text" name="sorting_order" class="form-control" required  
                                    data-error="@lang('admin.ERR_TITLE_EXAMINATION_SORTINGORDER_REQUIRED')">
                                <span class="help-block invalid-feedback with-errors">
                                    <ul class="list-unstyled">
                                        <li class="err_sorting_order"></li>
                                    </ul>
                                </span>
                            </div>
                        </div> -->
                        <div class="row">
                            <div class="col-sm-3"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">@lang('admin.TITLE_EXAMINATION_STATUS')</label>
                                    <div class="form-check">
                                          <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status" 
                                            value="1" 
                                            checked
                                            >
                                          <label class="form-check-label" for="status">@lang('admin.TITLE_EXAMINATION_STATUS_ACTIVE')</label>
                                    </div>  
                                    
                                </div>
                            </div>  
                            <!-- <div class="col-sm-4"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">@lang('admin.TITLE_EXAM_TRIGGER')</label>
                                    <div class="form-check">
                                          <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="trigger_exam_flag"
                                            name="trigger_exam_flag" 
                                            value="1" 
                                            >
                                          <label class="form-check-label" for="trigger_exam_flag">@lang('admin.TITLE_EXAMINATION_TRIGGER')</label>
                                    </div>   
                                </div>
                            </div> -->
                            <div class="col-sm-3"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">&nbsp;</label>
                                    <div class="form-check">
                                          <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="Show_as_control"
                                            name="Show_as_control" 
                                            >
                                          <label class="form-check-label" for="status">@lang('admin.TITLE_SHOW_AS_CONTROL')</label>
                                    </div>  
                                    
                                </div>
                            </div>
                            <div class="col-sm-3"> 
                                <div class="form-group"> 
                                 <label class="theme-blue"> 
                                    &nbsp;</label>                                   
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="dashboard_setting"
                                            name="dashboard_setting" 
                                            value="1"
                                            >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_TYPE_DASHBOARD_SETTING')</label>
                                      </div>
                                </div>
                            </div>
                            <div class="col-sm-3"> 
                                <div class="form-group"> 
                                 <label class="theme-blue"> 
                                    &nbsp;</label>                                   
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="show_as_recommended"
                                            name="show_as_recommended" 
                                            value="1"
                                            >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_TYPE_RECOMMENDE_SETTING')</label>
                                      </div>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_DOCUMENT_NAME')</label>
                                    <input 
                                        type="file" 
                                        name="document_name" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_DOCUMENT_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_document_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_DOCUMENT_STATUS')</label>
                                    <select 
                                        class="form-control" 
                                        name="document_status"
                                        id="document_status"
                                        data-error="@lang('admin.ERR_DOCUMENT_STATUS_REQUIRED')">
                                            <option value="0">@lang('admin.TITLE_SELECT_DOCUMENT_STATUS')</option>
                                          
                                            <option value="1">@lang('admin.TITLE_SELECT_DOCUMENT_READ')</option>
                                            <option value="2">@lang('admin.TITLE_SELECT_DOCUMENT_SIGN')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_document_status"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div> -->
                        <!-- check List -->
                        
                        <hr>
                        <div class="row">
                            @if(!empty($checkList) && sizeof($checkList)>0)
                            <div class="col-sm-2"> 
                                <div class="form-group">
                                    <button onclick="getAllCheckList(this)" type="button" class="btn btn-primary">@lang('admin.CHECK_LIST_BUTTON')</button>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_check_list"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4"> 
                                
                                <div id="div_ul" style="display: none;" class="form-group">
                                    <label id="check_label" class="theme-blue" style="display: none;"> 
                                    @lang('admin.TITLE_CHECK_LIST') : <!-- <span class="required">*</span> -->
                                    </label>
                                    <div id="checkListId">
                                        <!-- after click on check lits there is create check list  -->
                                    </div>
                                </div>
                            </div>
                             @endif

                            <!-- Document -->
                            @if(!empty($DocumentList) && sizeof($DocumentList)>0)
                            <div class="col-sm-2"> 
                                <div class="form-group">
                                    <button onclick="getAllDocumentList(this)" type="button" class="btn btn-primary">@lang('admin.DOCUMENT_LIST_ADD_BUTTON')</button>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_document_list"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4"> 
                                <div id="div_doc" style="display: none;" class="form-group">
                                    <label id="document_label" class="theme-blue" style="display: none;"> 
                                    @lang('admin.TITLE_DOCUMENT_LIST') : 
                                    </label>
                                    <div id="documentId">
                                        <!-- after click on document lits there is create document list  -->
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                       
                    </div>
                    <div class="card-footer">

                        <!--------- Aishwarya comment on 10-june ------------->
                       <!--  <button id="btn_save" type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button> -->
                       
                       <!---------- Aishwarya added on 10-june-25 ------------->
                        <button id="btn_save" type="submit" class="btn btn-success" disabled>@lang('admin.TITLE_SAVE_BUTTON')</button>
                        <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
                    </div>
                </form> 
            </div>
        </div>
    </div>
</div>    


<!--------------Reminder popup added here---22-sept-23------------------------------>
<div class="modal fade" id="createReminderModal" style="position:fixed;">
         <div class="modal-dialog modal-dialog-scrollable" style="max-width: 646px;">
            <div class="modal-content">
               <div class="modal-header">
                  <h2 class="card-title">@lang('admin.TITLE_REMINDER') </h2>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button> 
               </div>               
                  <div class="modal-body">
                            <div class="row">
                                <div class="col-sm-12">  
                                   
                                   <input type="radio" name="chkReminder" class="chkReminder" id="chkReminder" value="general" @if(!empty($channel_reminders->activated_reminder) && $channel_reminders->activated_reminder == 'general') checked="checked" @endif>
                                   <label> @lang('admin.TITLE_GENERAL_REMINDER') </label>&nbsp;

                                   <input type="radio" name="chkReminder" class="chkReminder" id="chkReminder" value="age" @if(!empty($channel_reminders->activated_reminder) && $channel_reminders->activated_reminder == 'age') checked="checked" @endif>
                                   <label> @lang('admin.TITLE_AGE_REMINDER') </label>&nbsp;
                                   <input type="radio" name="chkReminder" class="chkReminder" id="chkReminder" value="checkup" @if(!empty($channel_reminders->activated_reminder) && $channel_reminders->activated_reminder == 'checkup') checked="checked" @endif>
                                   <label> @lang('admin.TITLE_CHECKUP_REMINDER') </label>
                                </div>
                            </div>
                            <hr/>
                            <div class="general reminderSetting " >
                                <form id="createreminderForm" role="form" data-toggle="validator" action="">
                                <div class="row">
                                    <div class="col-sm-12">                                 
                                           <h4><label class="theme-blue"> 
                                            @lang('admin.TITLE_GENERAL_REMINDER') </label> </h4>
                                    </div>
                                </div>
                                <div class="row">                                   
                                    <div class="col-sm-6">  <label class="theme-blue"> 
                                    @lang('admin.REMINDED_SERVICE') <span class="required">*</span></label></div>
                                    <div class="col-sm-6"  > 
                                        <div class="row">          
                                                <select class="form-control" 
                                                name="reminder_service"
                                                id="reminder_service">
                                                <option value="">@lang('admin.SELECT_SERVICE')</option>
                                                @foreach($examinations as $exam)
                                                    <option value="{{$exam->id}}" {{ !empty($channel_reminders->recommanded_service_id) && $channel_reminders->recommanded_service_id == $exam->id ? 'selected' : '' }}>{{$exam->name}}</option>
                                                @endforeach
                                                </select>
                                        </div>
                                    </div>
                                </div>
                                <br/>
                                <div class="row">                                   
                                    <div class="col-sm-6">  <label class="theme-blue"> 
                                    @lang('admin.TITLE_PERIOD_OF_REMINDER_GENERAL_REMINDER') <span class="required">*</span></label></div>
                                    <div class="col-sm-6"  > 
                                        <div class="row">                                       
                                           <div class="col-sm-6 form-group">  
                                                <input                                       
                                                    type="text" 
                                                    name="general_period"
                                                    id="general_period" 
                                                    class="form-control number" 
                                                    required 
                                                    value="{{ $channel_reminders->general_period ?? '0' }}"
                                                >
                                            </div>
                                            <div class="col-sm-6"  >       
                                                <select                                        
                                                class="form-control" 
                                                name="general_period_frequency_type"
                                                id="general_period_frequency_type"              
                                                ><option value="day"  {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'day' ? 'selected' : '' }}
                                               >@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year" {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'year' ? 'selected' : '' }} >@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                    <option value="week" {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                                   
                                    <div class="col-sm-6"> 
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_NEW_REMINDER') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"  >
                                        <div class="row"> 
                                            <div class="col-sm-6 form-group">
                                                <input                                           
                                                    type="text" 
                                                    name="general_new_frequency"
                                                    id="general_new_frequency" 
                                                    required
                                                    class="form-control number"  
                                                    value="{{ $channel_reminders->general_new_frequency ?? '0' }}" 
                                                >
                                            </div>
                                             <div class="col-sm-6">                  
                                                 <select 
                                                    class="form-control" 
                                                    name="general_new_frequency_type"
                                                    id="general_new_frequency_type"
                                                    >
                                                        <option value="day" {{ !empty($channel_reminders->general_new_frequency_type) && $channel_reminders->general_new_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->general_new_frequency_type) && $channel_reminders->general_new_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->general_new_frequency_type) && $channel_reminders->general_new_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                         <option value="week"{{ !empty($channel_reminders->general_new_frequency_type) && $channel_reminders->general_new_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                </select> 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_FIRST_REMINDER') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"> 
                                        <div class="row">
                                        <div class="col-sm-6"  > 
                                            <div class="form-group">
                                                <input 
                                                    type="text" 
                                                    name="general_first_frequency"
                                                    id="general_first_frequency" 
                                                    class="form-control number"  
                                                    required
                                                    value="{{ $channel_reminders->general_first_frequency ?? '0' }}" 
                                                >
                                            </div>
                                        </div>
                                        <div class="col-sm-6" > 
                                            <div class="form-group">                  
                                                 <select                                            
                                                    class="form-control" 
                                                    name="general_first_frequency_type"
                                                    id="general_first_frequency_type"
                                                    >
                                                       
                                                        <option value="day" {{ !empty($channel_reminders->general_first_frequency_type) && $channel_reminders->general_first_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->general_first_frequency_type) && $channel_reminders->general_first_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->general_first_frequency_type) && $channel_reminders->general_first_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->general_first_frequency_type) && $channel_reminders->general_first_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>

                                                </select> 
                                            </div>
                                        </div> 
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                                 
                                    <div class="col-sm-6"> 
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_TIME_INTERVAL') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="row">
                                            <div class="col-sm-6"  > 
                                                <div class="form-group">
                                                    <input                                           
                                                        type="text" 
                                                        name="general_time_interval"
                                                        id="general_time_interval" 
                                                        class="form-control number"  
                                                        required
                                                        value="{{ $channel_reminders->general_time_interval ?? '0' }}" 
                                                    >
                                                </div>
                                            </div> 
                                            <div class="col-sm-6" > 
                                                <div class="form-group">                  
                                                     <select                                            
                                                        class="form-control" 
                                                        name="general_time_interval_frequency_type"
                                                        id="general_time_interval_frequency_type"
                                                        >
                                                           
                                                           <option value="day" {{ !empty($channel_reminders->general_time_interval_frequency_type) && $channel_reminders->general_time_interval_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                            <option value="month" {{ !empty($channel_reminders->general_time_interval_frequency_type) && $channel_reminders->general_time_interval_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                            <option value="year"{{ !empty($channel_reminders->general_time_interval_frequency_type) && $channel_reminders->general_time_interval_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                            <option value="week"{{ !empty($channel_reminders->general_time_interval_frequency_type) && $channel_reminders->general_time_interval_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                           
                                                    </select> 
                                                </div>
                                            </div> 
                                        </div> 
                                    </div>                         
                                </div>
                                <div class="row">                                 
                                    <div class="col-sm-6"> 
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_NUMBER_OF_INTERVAL') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"  > 
                                        <div class="form-group">
                                            <input                                           
                                                type="text" 
                                                name="general_number_of_interval"
                                                id="general_number_of_interval" 
                                                class="form-control number"  
                                                required
                                                value="{{ !empty($channel_reminders->general_number_of_interval)  ? $channel_reminders->general_number_of_interval : '0'  }}" 
                                            >
                                        </div>
                                    </div>                           
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_END_CYCLE') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6">
                                         <div class="row">
                                    <div class="col-sm-6"  > 
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
                                     <div class="col-sm-6" > 
                                            <div class="form-group">                  
                                                 <select                                            
                                                    class="form-control" 
                                                    name="general_end_cycle_frequency_type"
                                                    id="general_end_cycle_frequency_type"
                                                    >
                                                       
                                                       <option value="day" {{ !empty($channel_reminders->general_end_cycle_frequency_type) && $channel_reminders->general_end_cycle_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->general_end_cycle_frequency_type) && $channel_reminders->general_end_cycle_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->general_end_cycle_frequency_type) && $channel_reminders->general_end_cycle_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->general_end_cycle_frequency_type) && $channel_reminders->general_end_cycle_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                       
                                                </select> 
                                            </div>
                                        </div> 
                                        </div> 
                                        </div>                         
                                </div>
                                <div class="row">
                                    <div class="col-sm-12"> 
                                        <div class="form-group">
                                           <div class="card-footer text-center">
                                            <button type="submit" id="btn_sub" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>                                           
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            </div>
                            <div class="age reminderSetting d-none" >
                                <form id="createreminderAgeForm" role="form" data-toggle="validator" action="">
                             
                                <div class="row">
                                    <div class="col-sm-12">                                 
                                           <h4><label class="theme-blue"> 
                                            @lang('admin.TITLE_AGE_REMINDER') </label> </h4>
                                    </div>
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6">  <label class="theme-blue"> 
                                    @lang('admin.TITLE_AGE') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"  > 
                                        <div class="row">                                        
                                           <div class="col-sm-6 form-group">  
                                                <input                                       
                                                    type="text" 
                                                    name="age_age_from"
                                                    id="age_age_from" 
                                                    class="form-control number age_from" 
                                                    required 
                                                    placeholder="@lang('admin.TITLE_PROFILE_AGE_FROM')" 
                                                    value="{{ $channel_reminders->age_from ?? '0'}}"
                                                >
                                            </div>
                                            <div class="col-sm-6 form-group"  >       
                                                <input                                       
                                                    type="text" 
                                                    name="age_age_to"
                                                    id="age_age_to" 
                                                    class="form-control number age_to" 
                                                    required 
                                                    placeholder="@lang('admin.TITLE_PROFILE_AGE_TO')" 
                                                     value="{{ !empty($channel_reminders->age_to) ? $channel_reminders->age_to : '0' }}"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                                <div class="row">                                                     
                                    <div class="col-sm-6">  <label class="theme-blue"> 
                                    @lang('admin.TITLE_PERIOD_OF_REMINDER_CONTROL') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"  > 
                                        <div class="row">                                        
                                           <div class="col-sm-6 form-group">  
                                                <input                                       
                                                    type="text" 
                                                    name="age_period_controls"
                                                    id="age_period_controls" 
                                                    class="form-control number" 
                                                    required 
                                                   value="{{ $channel_reminders->age_period_controls ?? '0' }}"
                                                >
                                            </div>
                                            <div class="col-sm-6"  >       
                                                <select                                        
                                                class="form-control" 
                                                name="age_period_frequency_type"
                                                id="age_period_frequency_type"              
                                                >
                                               
                                                    <option value="day" {{ !empty($channel_reminders->age_period_frequency_type) && $channel_reminders->age_period_frequency_type == 'day' ? 'selected' : '' }}
                                                   >@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->age_period_frequency_type) && $channel_reminders->age_period_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year" {{ !empty($channel_reminders->age_period_frequency_type) && $channel_reminders->age_period_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                    <option value="week" {{ !empty($channel_reminders->age_period_frequency_type) && $channel_reminders->age_period_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>                           
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_NEW_REMINDER') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"  >
                                        <div class="row"> 
                                            <div class="col-sm-6 form-group">
                                                <input                                           
                                                    type="text" 
                                                    name="age_new_frequency"
                                                    id="age_new_frequency" 
                                                    required
                                                    class="form-control number"  
                                                    value="{{ !empty($channel_reminders->age_new_frequency) ? $channel_reminders->age_new_frequency : '0' }}"
                                                >
                                            </div>
                                             <div class="col-sm-6">                  
                                                 <select 
                                                    class="form-control" 
                                                    name="age_new_frequency_type"
                                                    id="age_new_frequency_type"
                                                    >
                                                         <option value="day" {{ !empty($channel_reminders->age_new_frequency_type) && $channel_reminders->age_new_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->age_new_frequency_type) && $channel_reminders->age_new_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->age_new_frequency_type) && $channel_reminders->age_new_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->age_new_frequency_type) && $channel_reminders->age_new_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                </select> 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_FIRST_REMINDER') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"> 
                                        <div class="row">
                                        <div class="col-sm-6"  > 
                                            <div class="form-group">
                                                <input 
                                                    type="text" 
                                                    name="age_first_frequency"
                                                    id="age_first_frequency" 
                                                    class="form-control number"  
                                                    required
                                                    value="{{ !empty($channel_reminders->age_first_frequency) ? $channel_reminders->age_first_frequency : '0'}}" 
                                                >
                                            </div>
                                        </div>
                                        <div class="col-sm-6" > 
                                            <div class="form-group">                  
                                                 <select                                            
                                                    class="form-control" 
                                                    name="age_first_frequency_type"
                                                    id="age_first_frequency_type"
                                                    >
                                                      <option value="day" {{ !empty($channel_reminders->age_first_frequency_type) && $channel_reminders->age_first_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->age_first_frequency_type) && $channel_reminders->age_first_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->age_first_frequency_type) && $channel_reminders->age_first_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->age_first_frequency_type) && $channel_reminders->age_first_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>

                                                </select> 
                                            </div>
                                        </div> 
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_TIME_INTERVAL') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6">
                                         <div class="row">
                                    <div class="col-sm-6"  > 
                                        <div class="form-group">
                                            <input                                           
                                                type="text" 
                                                name="age_time_interval"
                                                id="age_time_interval" 
                                                class="form-control number"  
                                                required
                                               value="{{ $channel_reminders->age_time_interval ?? '0' }}" 
                                            >
                                        </div>
                                    </div> 
                                     <div class="col-sm-6" > 
                                            <div class="form-group">                  
                                                 <select                                            
                                                    class="form-control" 
                                                    name="age_time_interval_frequency_type"
                                                    id="age_time_interval_frequency_type"
                                                    >
                                                     <option value="day" {{ !empty($channel_reminders->age_time_interval_frequency_type) && $channel_reminders->age_time_interval_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->age_time_interval_frequency_type) && $channel_reminders->age_time_interval_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->age_time_interval_frequency_type) && $channel_reminders->age_time_interval_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->age_time_interval_frequency_type) && $channel_reminders->age_time_interval_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                       
                                                </select> 
                                            </div>
                                        </div> 
                                        </div> 
                                        </div>                         
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_NUMBER_OF_INTERVAL') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"  > 
                                        <div class="form-group">
                                            <input                                           
                                                type="text" 
                                                name="age_number_of_interval"
                                                id="age_number_of_interval" 
                                                class="form-control number"  
                                                required
                                               value="{{ !empty($channel_reminders->age_number_of_interval) ? $channel_reminders->age_number_of_interval : '0' }}" 
                                            >
                                        </div>
                                    </div>                           
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_END_CYCLE') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6">
                                         <div class="row">
                                    <div class="col-sm-6"  > 
                                        <div class="form-group">
                                            <input                                           
                                                type="text" 
                                                name="age_end_cycle"
                                                id="age_end_cycle" 
                                                class="form-control number"  
                                                required
                                                value="{{ ($channel_reminders->age_end_cycle && $channel_reminders->age_end_cycle!=0) ? $channel_reminders->age_end_cycle:'' }}" 
                                            >
                                        </div>
                                    </div> 
                                     <div class="col-sm-6" > 
                                            <div class="form-group">                  
                                                 <select                                            
                                                    class="form-control" 
                                                    name="age_end_cycle_frequency_type"
                                                    id="age_end_cycle_frequency_type"
                                                    >
                                                     <option value="day" {{ !empty($channel_reminders->age_end_cycle_frequency_type) && $channel_reminders->age_end_cycle_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->age_end_cycle_frequency_type) && $channel_reminders->age_end_cycle_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->age_end_cycle_frequency_type) && $channel_reminders->age_end_cycle_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->age_end_cycle_frequency_type) && $channel_reminders->age_end_cycle_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                       
                                                </select> 
                                            </div>
                                        </div> 
                                        </div> 
                                        </div>                         
                                </div>
                                <div class="row">
                                    <div class="col-sm-12"> 
                                        <div class="form-group">
                                           <div class="card-footer text-center">
                                            <button type="submit" id="btn_sub" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                                           
                                        </div>
                                    </div>
                                </div>
                                </form>
                            </div>
                            
                           
                        </div>               
              <div class="checkup reminderSetting d-none" >
                                <form id="createreminderCheckupForm" role="form" data-toggle="validator" action="">
                             
                                <div class="row">
                                    <div class="col-sm-12">                                 
                                           <h4><label class="theme-blue"> 
                                            @lang('admin.TITLE_CHECKUP_REMINDER') </label> </h4>
                                    </div>
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6">  <label class="theme-blue"> 
                                    @lang('admin.TITLE_PERIOD_OF_REMINDER_CONTROL') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"  > 
                                        <div class="row">                                        
                                           <div class="col-sm-6 form-group">  
                                                <input                                       
                                                    type="text" 
                                                    name="checkup_period_controls"
                                                    id="checkup_period_controls" 
                                                    class="form-control number" 
                                                    required 
                                                   value="{{ $channel_reminders->checkup_period_controls ?? '0' }}"
                                                >
                                            </div>
                                            <div class="col-sm-6"  >       
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
                                    <div class="col-sm-6"> 
                                        <label class="theme-blue"> 
                                        @lang('admin.TITLE_NEW_REMINDER') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"  >
                                        <div class="row"> 
                                            <div class="col-sm-6 form-group">
                                                <input                                           
                                                    type="text" 
                                                    name="checkup_new_frequency"
                                                    id="checkup_new_frequency" 
                                                    required
                                                    class="form-control number"  
                                                   value="{{ !empty($channel_reminders->checkup_new_frequency) ? $channel_reminders->checkup_new_frequency : '0' }}"  
                                                >
                                            </div>
                                             <div class="col-sm-6">                  
                                                 <select 
                                                    class="form-control" 
                                                    name="checkup_new_frequency_type"
                                                    id="checkup_new_frequency_type"
                                                    >
                                                        <option value="day" {{ !empty($channel_reminders->checkup_new_frequency_type) && $channel_reminders->checkup_new_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->checkup_new_frequency_type) && $channel_reminders->checkup_new_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->checkup_new_frequency_type) && $channel_reminders->checkup_new_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->checkup_new_frequency_type) && $channel_reminders->checkup_new_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                </select> 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_FIRST_REMINDER') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"> 
                                        <div class="row">
                                        <div class="col-sm-6"  > 
                                            <div class="form-group">
                                                <input 
                                                    type="text" 
                                                    name="checkup_first_frequency"
                                                    id="checkup_first_frequency" 
                                                    class="form-control number"  
                                                    required
                                                     value="{{ !empty($channel_reminders->checkup_first_frequency) ? $channel_reminders->checkup_first_frequency : '0'}}" 
                                                >
                                            </div>
                                        </div>
                                        <div class="col-sm-6" > 
                                            <div class="form-group">                  
                                                 <select                                            
                                                    class="form-control" 
                                                    name="checkup_first_frequency_type"
                                                    id="checkup_first_frequency_type"
                                                    >
                                                        <option value="day" {{ !empty($channel_reminders->checkup_first_frequency_type) && $channel_reminders->checkup_first_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->checkup_first_frequency_type) && $channel_reminders->checkup_first_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->checkup_first_frequency_type) && $channel_reminders->checkup_first_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->checkup_first_frequency_type) && $channel_reminders->checkup_first_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>

                                                </select> 
                                            </div>
                                        </div> 
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_TIME_INTERVAL') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6">
                                         <div class="row">
                                    <div class="col-sm-6"  > 
                                        <div class="form-group">
                                            <input                                           
                                                type="text" 
                                                name="checkup_time_interval"
                                                id="checkup_time_interval" 
                                                class="form-control number"  
                                                required
                                                value="{{ $channel_reminders->checkup_time_interval ?? '0' }}" 
                                            >
                                        </div>
                                    </div> 
                                     <div class="col-sm-6" > 
                                            <div class="form-group">                  
                                                 <select                                            
                                                    class="form-control" 
                                                    name="checkup_time_interval_frequency_type"
                                                    id="checkup_time_interval_frequency_type"
                                                    >
                                                    <option value="day" {{ !empty($channel_reminders->checkup_time_interval_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->checkup_time_interval_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->checkup_time_interval_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->checkup_time_interval_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                       
                                                </select> 
                                            </div>
                                        </div> 
                                        </div> 
                                        </div>                         
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_NUMBER_OF_INTERVAL') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6"  > 
                                        <div class="form-group">
                                            <input                                           
                                                type="text" 
                                                name="checkup_number_of_interval"
                                                id="checkup_number_of_interval" 
                                                class="form-control number"  
                                                required
                                                value="{{ !empty($channel_reminders->checkup_number_of_interval) ? $channel_reminders->checkup_number_of_interval : '0' }}" 
                                            >
                                        </div>
                                    </div>                           
                                </div>
                                <div class="row">                                                     
                                    <div class="col-sm-6"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_END_CYCLE') <span class="required">*</span></label> 
                                    </div>
                                    <div class="col-sm-6">
                                         <div class="row">
                                    <div class="col-sm-6"  > 
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
                                     <div class="col-sm-6" > 
                                            <div class="form-group">                  
                                                 <select                                            
                                                    class="form-control" 
                                                    name="checkup_end_cycle_frequency_type"
                                                    id="checkup_end_cycle_frequency_type"
                                                    >
                                                       
                                                        <option value="day" {{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_time_interval_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                       
                                                       
                                                </select> 
                                            </div>
                                        </div> 
                                        </div> 
                                        </div>                         
                                </div>
                                <div class="row">
                                    <div class="col-sm-12"> 
                                        <div class="form-group">
                                           <div class="card-footer text-center">
                                            <button type="submit" id="btn_sub" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                                           
                                        </div>
                                    </div>
                                </div>
                                </form>
                            </div>
                    </div>
            </form>
            <!-- /.modal-content -->
         </div>
         <!-- /.modal-dialog -->
</div>

<!-------------end of reminder popup--22-sept-23--------------------------------->





</section>
@endsection

@section('scripts')
<script type="text/javascript">
    var Text = "{{ __('admin.ERR_DOCUMENT_FORMAT') }}";
    var reminderMsg = "{{ __('admin.Reminder_success_msg') }}"; // added on 22-sept-23

</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/examinations/create-edit.js') }}"></script>
<!-- <script src="//cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script> -->
<script src="{{ asset('assets/plugins/ckeditor/ckeditor.js') }}"></script>

<script type="text/javascript">
    $("#checkup_end_cycle,#age_end_cycle,#general_end_cycle").on("input", function() {
        if (/^0/.test(this.value)) {
            this.value = this.value.replace(/^0/, "")
        }
    });
    
    var editor = CKEDITOR.replace('description');
    var warning_msg = "{{__('admin.TITLE_EARMING_MESSAGE_SERVICE_INACTIVE')}}";

    function convertToSlug(Text)
    {
        var slug =  Text
        .toLowerCase()
        .replace(/ /g,'-')
        .replace(/[^\w-]+/g,'')
        ;
        $url = document.getElementById("site_url").value;
        document.getElementById("url").value = $url+'/'+slug;
    }
</script> 



<!-----------added below code on 25-sept-23----------------------------------------->

<!--code addded by swapnil pawar 11/10/2022 -->
<!-- It must not be possible to enter negative numbers  -->

<script>

$('#createreminderForm').keypress(function() {
   return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57
});
$('#createreminderAgeForm').keypress(function() {
   return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57
});
$('#createreminderCheckupForm').keypress(function() {
   return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57
});



var year_of_control_reminder_msg = "{{__('admin.TITLE_ERR_MESSAGE_PERIOD_OF_CONTROL')}}";
 

</script>
<!--code addded by swapnil pawar 11/10/2022 -->

<!-----------added above code on 25-sept-23----------------------------------------->
<!-- Added by Aishwarya on 29/5/25 -->
<!-- <script>
    function checkDescriptionField() {
        const editor = CKEDITOR.instances['description'];
        if (!editor) return;

        // Get plain text without HTML tags and trim spaces
        const description = editor.getData().replace(/<[^>]*>/g, '').trim();

        const saveButton = document.getElementById('btn_save');

        if (description) {
            saveButton.removeAttribute('disabled');
        } else {
            saveButton.setAttribute('disabled', 'disabled');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.on('instanceReady', function () {
                const editor = CKEDITOR.instances['description'];

                // Check on every change, with a slight delay for reliable data
                editor.on('change', function () {
                    setTimeout(checkDescriptionField, 5);
                });

                // Initial check when editor is ready
                checkDescriptionField();
            });
        }
    });
</script> -->
<!------------------- Aishwarya added code on 10-june-25 ------------------------->
<script>
$(document).ready(function () {
    function toggleSaveButton() {
        const name = $('input[name="name"]').val().trim();
        const url = $('input[name="url"]').val().trim();

        if (name !== '' && url !== '') {
            $('#btn_save').removeClass('disabled').prop('disabled', false);
        } else {
            $('#btn_save').addClass('disabled').prop('disabled', true);
        }
    }


    // Run when typing in name or url
    $('input[name="name"], input[name="url"]').on('input', toggleSaveButton);

    // Also run toggle after blur, because convertToSlug() runs onblur
    $('input[name="name"]').on('blur', function () {
        // wait briefly to ensure url is auto-filled
        setTimeout(toggleSaveButton, 100);
    });
});
</script>

@endsection 