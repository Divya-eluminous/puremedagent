@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }} 
@endsection

@section('content')  

<!-- Main Content  -->
<section class="content">
<div class="container-fluid"> 
    <div class="row"> 
               <!-- left column -->
        <div class="col-md-12">
            <input type="hidden"   name="site_url" id="site_url" value="{{ url('/')}}" />
            <!-- jquery validation -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3> 
                    <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button>  
                </div>

                <form id="updateExaminationForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.update', [base64_encode(base64_encode($exams->id))]) }}">
                        <input type="hidden" name="specialist_id" id="specialist_id" value="{{$specialist_details->id}}" >
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="hd_exam_id" id="hd_exam_id" value="{{$exams->id}}">
                    <div class="card-body"> 
                        <div class="row">
                            <div class="col-sm-4"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_TEXT') </label>
                                    <input 
                                        type="text" 
                                        name="specialist_name" 
                                        class="form-control"  
                                        required
                                        disabled
                                        maxlength="250" 
                                        value="{{$specialist_details->name}}"   
                                    >
                                
                                </div>
                            </div>
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
                                            {{$exams->show_as_reminder=='1'?'checked':''}}
                                            >
                                          <label class="form-check-label" for="status">@lang('admin.TITLE_SHOW_AS_REMINDER')</label>
                                    </div>  
                                    
                                </div>
                            </div>

                            <div class="col-sm-4" id="btn_reminder" style="@if($exams->show_as_reminder!='0')display: none;@endif">
                                <div class="form-group"> 
                                    <label class="theme-blue"> &nbsp; </label>
                                    <button type="button" id="reminderbutton" class="btn fc-button-primary  btn-danger form-control" data-toggle="modal" data-target="#addReminderModal">@lang('admin.TITLE_REMINDER_SERVICE_BTN') </button>
                                    <!--  @if($exams->default_service == 1)    -->
                                    <!--   @endif -->
                                </div>
                            </div>
                           
                        </div> 
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="d-flex flex-column mb-25 form-group">
                                    <label class="theme-blue">@lang('admin.TITLE_EXAMINATION_NAME') <span class="required">*</span></label> 
                                    <input 
                                        type="text" 
                                        name="name"
                                        value="{{ $exams->name }}"  
                                        class="form-control" 
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_EXAM_NAME_REQUIRED')" 
                                        onblur="convertToSlug(this.value)"
                                        @if(!empty($exams->default_service))
                                        readonly
                                        @endif
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
                                        value="{{ $exams->url }}"  
                                        class="form-control" 
                                        required
                                        maxlength="50" 
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
                                   <!--  <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_TYPE_DESCRIPTION') <span class="required">*</span></label>  -->

                                    <!------- Aishwarya added on 2-jun-25 ------>
                                    <label class="theme-blue"> 

                                    @lang('admin.TITLE_APPOINTMENT_TYPE_DESCRIPTION')</label> 
                                    <textarea
                                        type="text" 
                                        name="description" 
                                        class="form-control ckeditor" 
                                        required
                                        data-error="@lang('admin.ERR_APPOINTMENT_TYPE_DESCRIPTION_REQUIRED')"
                                    >{{ $exams->description??'' }}</textarea>
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
                                <input type="text" name="sorting_order" class="form-control" required maxlength="250" 
                                    data-error="@lang('admin.ERR_TITLE_EXAMINATION_SORTINGORDER_REQUIRED')" value="{{ $exams->sorting_order }}" >
                                <span class="help-block invalid-feedback with-errors">
                                    <ul class="list-unstyled">
                                        <li class="err_sorting_order"></li>
                                    </ul>
                                </span>
                            </div>
                        </div> -->
                        <div class="row">
                            <div class="col-sm-3"> 
                                <div class="d-flex flex-column mb-25 form-group">
                                     <label class="theme-blue">@lang('admin.TITLE_EXAMINATION_STATUS')</label>
                                    <div class="checkbox">
                                          <input 
                                            type="checkbox"   
                                            name="status" 
                                            id="status"
                                            value="1"
                                            {{$exams->status==1?'checked':''}}
                                            >
                                            @lang('admin.TITLE_EXAMINATION_STATUS_ACTIVE') 
                                    </div>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_status"></li>
                                        </ul>
                                    </span>
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
                                            {{$exams->trigger_exam_flag==1?'checked':''}} 
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
                                            {{$exams->show_as_control==1?'checked':''}}
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
                                            {{$exams->on_dashboard=='1'?'checked':''}}
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
                                            {{$exams->show_as_recommended=='1'?'checked':''}}
                                            >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_TYPE_RECOMMENDE_SETTING')</label>
                                      </div>
                                </div>
                            </div>
                            
                        </div>

                        <!-- <div class="row">
                            <div class="col-sm-6"> 
                                @php 
                                        if(!empty($exams->document_name))
                                        {
                                            $str =  $exams->document_name;
                                            $hasFile = true;
                                        }
                                        else
                                        {
                                            $str = 'No File Selected.';
                                            $hasFile = false;
                                        }
                                    @endphp
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_DOCUMENT_NAME') </label>
                                    <input 
                                        type="file" 
                                        name="document_name" 
                                        class="form-control" 
                                        value="{{ $exams->document_name }}" 
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_DOCUMENT_REQUIRED')" 

                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_document_name"></li>
                                        </ul>
                                    </span>
                                </div> 
                                <div class="col-sm-6"> 
                                    @if(!empty($exams->document_name) && is_file(storage_path().$exams->document_path))
                                  
                                        <a href="{{ url('storage'.$exams->document_path) }}" class="old_file" target="_blank" title="document">{{ $exams->document_name?? '' }}</a>
                                        <input type="hidden" name="old_doc_data" id="old_doc_data" value="{{ $exams->document_path }}">
                                        <input type="hidden" name="old_file" class="old_file" id="old_file" value="{{ $exams->document_path }}">
                                    @endif
                                    <button type="button" class="btn btn-danger removefile" @if(!$hasFile) style="display: none" @endif onclick="removeFile(this)">@lang('admin.TITLE_REMOVE_BUTTON')</button>
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
                                     
                                            <option value="1" {{ $exams->document_status == 1 ? 'selected' : '' }}>@lang('admin.TITLE_SELECT_DOCUMENT_READ')</option>
                                            <option value="2" {{ $exams->document_status == 2 ? 'selected' : '' }}>@lang('admin.TITLE_SELECT_DOCUMENT_SIGN')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_document_status"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                         -->

                        <!-- Check List -->
                        
                        <hr>
                        <div class="row">
                            @if(!empty($checkList) && sizeof($checkList)>0)
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <button onclick="getAllCheckList(this)" type="button" class="btn btn-primary">@lang('admin.CHECK_LIST_BUTTON')</button>
                                    <span class="help-block invalid-feedback with-errors">
                                       
                                    </span>
                                </div>
                            </div>
                            
                           
                            <div class="col-sm-4"> 
                                @if(!empty($MultipleCheckList) && sizeof($MultipleCheckList)>0)
                                <label id="check_label" class="theme-blue" > 
                                    @lang('admin.TITLE_CHECK_LIST') :
                                </label>
                                @else
                                <label id="check_label_else" class="theme-blue" style="display: none" > 
                                    @lang('admin.TITLE_CHECK_LIST') :
                                </label>
                                @endif
                                <div class="form-group">
                                    @if(!empty($MultipleCheckList) && sizeof($MultipleCheckList)>0)
                                    <div>
                                      <!--   <ul > -->
                                            @foreach($MultipleCheckList as $val)
                                           <!--  <li>
                                                <input 
                                                type="checkbox" 
                                                checked 
                                                class="form-check-input" 
                                                id="check_id_{{$val['id']}}"
                                                name="check_list[]" 
                                                value="{{$val['id']}}" 
                                                onclick="uncheck('{{$val["id"]}}')" 
                                                >
                                                <a target="_blank"  class="action-icon" title="@lang('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION')" href="{{url('admin/check-list/view/'.base64_encode(base64_encode($val['id']))) }}" >{{$val['check_list_name']}}</a>
                                             
                                            </li> -->
                                            <div class="custom-control custom-checkbox">
                                              <input onclick="uncheck('{{$val["id"]}}')" checked class="custom-control-input" type="checkbox" name="check_list[]"  id="customCheckbox{{$val['id']}}" value="{{$val['id']}}">
                                              <label for="customCheckbox{{$val['id']}}" class="custom-control-label"><a target="_blank"  class="action-icon" title="@lang('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION')" href="{{url('admin/check-list/view/'.base64_encode(base64_encode($val['id']))) }}" >{{$val['check_list_name']}}</a></label>
                                            </div>
                                            @endforeach
                                       <!--  </ul> -->
                                    </div>
                                    @endif
                                    <div id="checkListId">
                                        <!-- after click on check lits there is create check list  -->

                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Document list -->
                            @if(!empty($DocumentList) && sizeof($DocumentList)>0)
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <button onclick="getAllDocumentList(this)" type="button" class="btn btn-primary">@lang('admin.DOCUMENT_LIST_ADD_BUTTON')</button>
                                    <span class="help-block invalid-feedback with-errors">
                                       
                                    </span>
                                </div>
                            </div>
                           
                            <div class="col-sm-4"> 
                                @if(!empty($MultipleDocumentList) && sizeof($MultipleDocumentList)>0)
                                <label id="document_label" class="theme-blue" > 
                                    @lang('admin.TITLE_DOCUMENT_LIST') :
                                </label>
                                @else
                                <label id="document_label_else" class="theme-blue" style="display: none" > 
                                    @lang('admin.TITLE_DOCUMENT_LIST') :
                                </label>
                                @endif
                                <div class="form-group">
                                    @if(!empty($MultipleDocumentList) && sizeof($MultipleDocumentList)>0)
                                    <div>
                                        @foreach($MultipleDocumentList as $d_val)
                                     
                                        <div class="custom-control custom-checkbox">
                                          <input onclick="uncheckDocument('{{$d_val["id"]}}')" checked class="custom-control-input" type="checkbox" name="document_list[]"  id="customDocumentbox{{$d_val['id']}}" value="{{$d_val['id']}}">
                                          <label for="customDocumentbox{{$d_val['id']}}" class="custom-control-label"><a target="_blank"  class="action-icon" title="@lang('admin.TITLE_CHECKLIST_VIEW_HEADING_SECTION')" href="{{url('admin/specialist/documentsView/'.base64_encode(base64_encode($d_val['id']))) }}" >{{$d_val['name']}}</a></label>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                    <div id="documentId">
                                        <!-- after click on document lits there is create document list  -->

                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                       
                    </div><!-- /.card-body -->
                    
                    <div class="card-footer">
                        <!-- <button type="submit" class="btn btn-danger">Cancel</button> -->
                        <button id="btn_save" type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>    


<div class="modal fade" id="addReminderModal" style="position:fixed;">
         <div class="modal-dialog modal-dialog-scrollable" style="max-width: 646px;">
          
           
            <div class="modal-content">
               <div class="modal-header">
                  <h2 class="card-title">@lang('admin.TITLE_REMINDER') </h2>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button> 
                  <input type="hidden" name="examination_id" value="{{ $exams->id }}">              
               </div>               
                  <div class="modal-body">
                            <div class="row">
                                <div class="col-sm-12">  
                                   
                                   <input type="radio" name="chkReminder" class="chkReminder" value="general" @if(!empty($channel_reminders->activated_reminder) && $channel_reminders->activated_reminder == 'general') checked="checked" @endif>
                                   <label> @lang('admin.TITLE_GENERAL_REMINDER') </label>&nbsp;

                                   <input type="radio" name="chkReminder" class="chkReminder" value="age" @if(!empty($channel_reminders->activated_reminder) && $channel_reminders->activated_reminder == 'age') checked="checked" @endif>
                                   <label> @lang('admin.TITLE_AGE_REMINDER') </label>&nbsp;
                                   <input type="radio" name="chkReminder" class="chkReminder" value="checkup" @if(!empty($channel_reminders->activated_reminder) && $channel_reminders->activated_reminder == 'checkup') checked="checked" @endif>
                                   <label> @lang('admin.TITLE_CHECKUP_REMINDER') </label>
                                </div>
                            </div>
                            <hr/>
                            <div class="general reminderSetting " >
                                <form id="reminderForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.updateReminder', [base64_encode(base64_encode($exams->id))]) }}">
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
                                                    <option value="{{$exam->id}}" {{ !empty($channel_reminders->recommanded_service_id) && $channel_reminders->recommanded_service_id == $exam->id ? 'selected' : '' }} >{{$exam->name}}</option>
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
                                                ><option value="day" 
                                                {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                    <option value="month" {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                    <option value="year" {{ !empty($channel_reminders->general_period_frequency_type) && $channel_reminders->general_period_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
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
                                <form id="reminderAgeForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.updateAgeReminder', [base64_encode(base64_encode($exams->id))]) }}">
                             
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
                                <form id="reminderCheckupForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.updateCheckupReminder', [base64_encode(base64_encode($exams->id))]) }}">
                             
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
                                                       
                                                        <option value="day" {{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_end_cycle_frequency_type == 'day' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                                        <option value="month" {{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_end_cycle_frequency_type == 'month' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                                        <option value="year"{{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_end_cycle_frequency_type == 'year' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                                        <option value="week"{{ !empty($channel_reminders->checkup_end_cycle_frequency_type) && $channel_reminders->checkup_end_cycle_frequency_type == 'week' ? 'selected' : '' }}>@lang('admin.TITLE_FREQUENCY_WEEK')</option>
                                                       
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

</section>
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/examinations/create-edit.js') }}"></script> 
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
<!--code addded by swapnil pawar 11/10/2022 -->
<!-- It must not be possible to enter negative numbers  -->
<script>
$('#reminderForm').keypress(function() {
   return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57
});
$('#reminderAgeForm').keypress(function() {
   return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57
});
$('#reminderCheckupForm').keypress(function() {
   return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57
});


var year_of_control_reminder_msg = "{{__('admin.TITLE_ERR_MESSAGE_PERIOD_OF_CONTROL')}}";
</script>
<!--code addded by swapnil pawar 11/10/2022 -->

<!---------- Aishwarya added on 10-june-25----------->
<script>
$(document).ready(function () {
    const $btn = $('#btn_save');
    
    // Always start by removing forced 'disabled' class or attribute
    $btn.removeClass('disabled').prop('disabled', false);

    function toggleSaveButton() {
        const nameVal = $('input[name="name"]').val().trim();
        const urlVal = $('input[name="url"]').val().trim();

        if (nameVal === '' || urlVal === '') {
            $btn.addClass('disabled').prop('disabled', true);
        } else {
            $btn.removeClass('disabled').prop('disabled', false);
        }
    }

    // Run when typing in either input
    $('input[name="name"], input[name="url"]').on('input', toggleSaveButton);

    $('input[name="name"]').on('blur', function () {
        // wait briefly to ensure url is auto-filled
        setTimeout(toggleSaveButton, 100);
    });
});
</script>

@endsection