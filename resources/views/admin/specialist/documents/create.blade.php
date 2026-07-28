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
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/css/bootstrap-colorpicker.min.css" rel="stylesheet">
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

                <form id="DocumentForm" role="form" data-toggle="validator" action="{{ url('admin/specialist/documentStore/') }}">
                    <input type="hidden" name="specialist_id" id="specialist_id" value="{{$specialist_id}}">
                    <div class="card-body">
                         <div class="row">
                            <div class="col-sm-6"> 
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

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT') <!-- <span class="required">*</span> --></label>
                                    <select 
                                        class="form-control" 
                                        name="type_of_document"
                                        id="type_of_document"
                                        required
                                        data-error="@lang('admin.ERR_DOCUMENT_TYPE_OF_DOCUMENT')">
                                            <option value="">@lang('admin.TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT_SELECT')</option>
                                            <!-- <option value="0">@lang('admin.TITLE_SELECT_DOCUMENT_UNREAD')</option> -->
                                            <option value="general">@lang('admin.TITLE_GENERAL')</option>
                                            <option value="service">@lang('admin.TITLE_SERVICE')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_type_of_document"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_SPECIALIST_DOCUMENT_NAME_REQUIRED')" 
                                        
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                        </div>

                               <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_HEADER_IMAGE') <!-- <span class="required">*</span> --></label>
                                    <input 
                                        type="file" 
                                        name="header_image" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_DOCUMENT_HEADER_IMAGE')" 
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
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FOOTER_IMAGE') <!-- <span class="required">*</span> --></label>
                                    <input 
                                        type="file" 
                                        name="footer_image" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_DOCUMENT_FOOTER_IMAGE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_footer_image"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                    
                        <div class="row">
                            
                            <div class="col-sm-6"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">@lang('admin.TITLE_SPECIALIST_DOCUMENT_DATE_OF_LAST_ACTIVATION')</label>
                                    <input 
                                        type="text" 
                                        name="date_of_last_activation" 
                                        id="date_of_last_activation" 
                                        class="form-control"  
                                        required
                                        value="{{date('d-m-Y H:i:s')}}" 
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_DOCUMENT_DATE_OF_ACTIVATION')" 
                                    >  
                                </div>
                            </div>
                              <div class="col-sm-5"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_BACKGROUND_COLOR') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="background_color" 
                                        id="background_color" 
                                        class="form-control text_color"  
                                        required
                                        autocomplete="off" 
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_ORDINATION_TEST_COLOR_CODE')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_background_color"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <input 
                                id="background_color1" 
                                    readonly 
                                    class="form-control documentColorCls textsetColorCode textcolorpicker" 
                                > 
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="frequency" 
                                        class="form-control"  
                                        required
                                        data-error="@lang('admin.ERR_DOCUMENT_FREQUENCY')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_frequency"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                             <div class="col-sm-3"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY_TYPE') <span class="required">*</span></label>
                                     <select 
                                        class="form-control" 
                                        name="frequency_type"
                                        id="frequency_type"
                                        required 
                                        data-error="@lang('admin.ERR_DOCUMENT_FREQUENCY_TYPE')">
                                            <option value="">@lang('admin.TITLE_FREQUENCY_TYPE_SELECT')</option>
                                            <option value="day">@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                            <option value="month">@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                            <option value="year">@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_frequency_type"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">@lang('admin.TITLE_SPECIALIST_DOCUMENT_STATUS')</label>
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


                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_HTML_TEXT') <span class="required">*</span></label> 
                                    <textarea
                                        type="text" 
                                        name="html_text" 
                                        class="form-control" 
                                        id="html_text"
                                        required
                                        data-error="@lang('admin.ERR_DOCUMENT_HTML_TEXT')"
                                    ></textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_html_text"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                        </div>

                       

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
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

<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/specialist/documents/create-edit.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/js/bootstrap-colorpicker.min.js"></script>
<!-- <script src="//cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script> -->
<!-- Roshani added the below url of ckeditor -->
<script src="{{ asset('assets/plugins/ckeditor/ckeditor.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/js/bootstrap-colorpicker.min.js"></script>

<script type="text/javascript">

    var editor = CKEDITOR.replace('html_text');
</script> 
<!-- Aishwarya added on 2-jun-25 -->
<script>
$('#reset_btn').on('click', function () {
    $('#background_color1').css('background-color', '');
    if (CKEDITOR.instances['html_text']) {
        CKEDITOR.instances['html_text'].setData(''); // Reset CKEditor content
    }
});
</script>
@endsection 