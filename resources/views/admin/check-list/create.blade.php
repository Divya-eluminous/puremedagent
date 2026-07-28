@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content')

<style>
fieldset {
  background-color: #eeeeee;
}

legend {
  background-color: gray;
  color: white;
  padding: 5px 10px;
}

input {
  margin: 5px;
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
                
                <form id="checkListForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.store') }}">
                    <input type="hidden" name="specialist_id" id="specialist_id" value="@if(!empty($specialist_details)){{$specialist_details->id}}" @endif>
                    <input type="hidden" name="hd_flag" id="hd_flag" value="yes">
                    <div class="card-body">
                        <div class="row">
                            @if(!empty($specialist_details))
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
                            @endif
                            <div class="col-sm-1"> 
                                <div class="p-0 form-group"> 
                                <label class="theme-blue">@lang('admin.TITLE_SELECT_CHECK_LIST_SIGNDOC')</label>
                                <div class="form-check">
                                      <input 
                                        type="radio" 
                                        class="form-check-input" 
                                        name="signDoc" 
                                        value="read" 
                                        checked
                                        >
                                      <label class="form-check-label" for="status">@lang('admin.TITLE_SELECT_DOCUMENT_READ')</label>
                                </div>  
                                
                                </div>
                            </div>
                            <div class="col-sm-4"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">&nbsp;</label>
                                    <div class="form-check">
                                          <input 
                                            type="radio" 
                                            class="form-check-input" 
                                            name="signDoc" 
                                            value="sign" 
                                            >
                                          <label class="form-check-label" for="status">@lang('admin.TITLE_SELECT_DOCUMENT_SIGN')</label>
                                    </div>  
                                    
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_CHECKLIST_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="check_list_name" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_CHECK_LIST_NAME_REQUIRED')" 
                                        
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_check_list_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_CHECK_LIST_TYPE') <span class="required">*</span></label>
                                     <select 
                                        onchange="checkType(this)" 
                                        class="form-control" 
                                        name="type_of_checklist"
                                        style="margin-top: 5px;" 
                                        id="type_of_checklist"
                                        required 
                                        data-error="@lang('admin.ERR_CHECK_LIST_TYPE_REQUIRED')">
                                            <option value="">@lang('admin.TITLE_TOTO_SELECT_FINDING_TYPE')</option>
                                            <option value="performance">@lang('admin.TITLE_TYPE_CHECKLIST_PERFORMANCE')</option>
                                            <option value="general">@lang('admin.TITLE_TYPE_CHECKLIST_GENERAL')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_type_of_checklist"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4"> 
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
                       
                        </div>

                        <!-- frequncy ,date of last activation filed  -->
                        <div class="row" style="display: none;" id="div_general_type">
                            <div class="col-sm-4" id="div_frequncy" > 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY') <span class="required">*</span></label>
                                    <input 
                                        style="display: none;"
                                        type="text" 
                                        name="frequency"
                                        id="frequency" 
                                        class="form-control"  
                                    >
                                    <span class="invalid-feedback" id="err_frequency">
                                        <ul class="list-unstyled">
                                            <li class="err_frequency">@lang('admin.ERR_DOCUMENT_FREQUENCY')</li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4" id="div_frequncy_type"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY_TYPE') <span class="required">*</span></label>
                                     <select 
                                        style="display: none;margin-top: 5px!important;"
                                        class="form-control" 
                                        name="frequency_type"
                                        id="frequency_type"
                                        >
                                            <option value="">@lang('admin.TITLE_FREQUENCY_TYPE_SELECT')</option>
                                            <option value="day">@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                            <option value="month">@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                            <option value="year">@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                    </select> 
                                    <span class="invalid-feedback" id="err_frequency_type">
                                        <ul class="list-unstyled">
                                            <li class="">@lang('admin.ERR_DOCUMENT_FREQUENCY_TYPE')</li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4" id="div_date_of_last_activation"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">@lang('admin.TITLE_SPECIALIST_DOCUMENT_DATE_OF_LAST_ACTIVATION')<span class="required">*</span></label>
                                    <input
                                        style="display: none;" 
                                        type="text" 
                                        name="date_of_last_activation" 
                                        id="date_of_last_activation" 
                                        class="form-control"  
                                        value="{{date('d-m-Y H:i:s')}}"
                                        autocomplete="off" 

                                    > 
                                     <span class="invalid-feedback" id="err_date_of_last_activation">
                                        <ul class="list-unstyled">
                                            <li class="">@lang('admin.ERR_DOCUMENT_DATE_OF_ACTIVATION')</li>
                                        </ul>
                                    </span> 
                                </div>
                            </div>
                       
                        </div>



                          <!-----------Header and footer-code added on 26-dec-22----------->

                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_CHECKLIST_DOCUMENT_HEADER_IMAGE') <!-- <span class="required">*</span> --></label>
                                    <input 
                                        type="file" 
                                        name="header_image" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_CHECKLIST_DOCUMENT_HEADER_IMAGE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_header_image"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                             <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_CHECKLIST_DOCUMENT_FOOTER_IMAGE') <!-- <span class="required">*</span> --></label>
                                    <input 
                                        type="file" 
                                        name="footer_image" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_CHECKLIST_DOCUMENT_FOOTER_IMAGE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_footer_image"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                        
                        <!-----------Header and footer-code added on 26-dec-22----------->


                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_CHECKLIST_INTRODUCER_TEXT') <span class="required">*</span></label> 
                                    <textarea
                                        type="text" 
                                        name="introduction_text" 
                                        class="form-control" 
                                        id="introduction_text"
                                        required
                                        data-error="@lang('admin.ERR_CHECKLIST_INTRODUCER_TEXT_REQUIRED')"
                                    ></textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <!-- Aishwarya commented on 3-june-25 -->
                                           <!--  <li class="">dfsfsdfsdf sddd sss ss ss dfdsf</li> -->
                                           <!-- Aishwarya added on 3-june-25 -->
                                            <li class="err_introduction_text"></li>

                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_CHECKLIST_FINAL_TEXT') <span class="required">*</span></label> 
                                    <textarea
                                        type="text" 
                                        name="final_text" 
                                        class="form-control" 
                                        id="final_text"
                                        required
                                        data-error="@lang('admin.ERR_CHECKLIST_FINAL_TEXT_REQUIRED')"
                                    ></textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_final_text"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                     
                        <div class="wrapper">
                            <div class="row">
                                <div class="col-sm-8"> 
                                    <fieldset>
                                          <legend class="form-group">{{$heading_section}} :
                                            <input 
                                                type="text" 
                                                name="heading_section[1][heading_section][heading][]" 
                                                class="form-control"  
                                                required
                                                maxlength="250" 
                                                data-error="@lang('admin.ERR_CHECKLIST_HEADING_REQUIRED')" />
                                                <span class="help-block invalid-feedback with-errors">
                                                    <ul class="list-unstyled">
                                                        <li class="err_heading_section"></li>
                                                    </ul>
                                                </span>

                                            </legend>
                                            <div class="sub-wrapper-1">
                                                <div class="row"> 
                                                    <div class="form-group col-sm-8">
                                                        <label class="theme-blue"> 
                                                        {{$question}} <span class="required">*</span></label>
                                                        <input 
                                                            type="text" 
                                                            name="heading_section[1][heading_section][question][]" 
                                                            class="form-control"  
                                                            required
                                                            maxlength="250" 
                                                            data-error="@lang('admin.ERR_CHECKLIST_QUESTION_REQUIRED')" 
                                                        >
                                                        <span class="help-block invalid-feedback with-errors">
                                                            <ul class="list-unstyled">
                                                                <li class="err_heading_section"></li>
                                                            </ul>
                                                        </span>
                                                    </div>
                                                    <div class="col-sm-4" style="margin-top: 37px;">
                                                        <a onclick="AddQuetion(this,1)" class="action-icon" title="@lang('admin.TITLE_CHECKLIST_ADD_MORE')" ><button type="button" id="" class="btn btn-md btn-primary">+</button></a>
                                                       <!--  <button class="btn btn-danger remove" type="button"><span class="fas fa-trash"></span></button> -->
                                                    </div>
                                                </div>
                                            </div>
                                    </fieldset>
                                </div>
                                <div class="col-sm-4">
                                    <a href="javascript:void(0)" class="action-icon" title="@lang('admin.TITLE_CHECKLIST_ADD_MORE')" ><button type="button" id="addBtn" class="btn btn-md btn-primary">+</button></a>
                                  <!--   <a href="javascript:void(0)" class="action-icon" title="@lang('admin.TITLE_CHECKLIST_REMOVE_MORE')" ><button class="btn btn-danger remove" type="button"><span class="fas fa-trash"></span></button></a> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <!-- <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button> -->
                        <button type="button" id="btn_sub" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                        <button type="reset" id="reset_btn" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
                    </div>
                </form> 
            </div>
        </div>
    </div>
</div>    
</section>
@endsection

@section('scripts')
<script type="text/javascript">
    var Text            = "{{ __('admin.ERR_DOCUMENT_FORMAT') }}";
    var err_message     = "{{ __('admin.ERR_CHECK_LIST_NAME_REQUIRED') }}"; 
    var heading_section = "{{$heading_section}}";
    var question        = "{{$question}}";
    var err_question    = "{{ __('admin.ERR_CHECKLIST_QUESTION_REQUIRED') }}";
    var err_heading     = "{{ __('admin.ERR_CHECKLIST_HEADING_REQUIRED') }}"; 
    
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/check-list/create-edit.js') }}"></script>
<!-- <script src="//cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script> -->
<script src="{{ asset('assets/plugins/ckeditor/ckeditor.js') }}"></script>
<script type="text/javascript">

    var editor       = CKEDITOR.replace('introduction_text');
    var introduction = CKEDITOR.replace('final_text');

</script> 
@endsection 