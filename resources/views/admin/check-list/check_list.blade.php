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
            <input type="hidden"   name="site_url" id="site_url" value="{{ url('/')}}" />
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button>
                </div>
                
                <form id="examinationForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.store') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_CHECKLIST_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_CHECK_LIST_NAME_REQUIRED')" 
                                        
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6"> 
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
                                          <legend>{{$heading_section}} 1:
                                            <input 
                                                type="text" 
                                                name="heading_section[0][]" 
                                                class="form-control"  
                                                required
                                                maxlength="250" 
                                                data-error="@lang('admin.ERR_CHECK_LIST_NAME_REQUIRED')" >
                                            </legend>

                                           <input type="hidden" id="heading_section_0" name="heading_section[0][]" value="1">

                                            <div class="sub-wrapper-1">
                                                <div class="row"> 
                                                    <div class="form-group col-sm-8">
                                                        <label class="theme-blue"> 
                                                        {{$question}} <span class="required">*</span></label>
                                                        <input 
                                                            type="text" 
                                                            name="heading_section[0]['heading_section'][]" 
                                                            class="form-control"  
                                                            required
                                                            maxlength="250" 
                                                            data-error="@lang('admin.ERR_CHECK_LIST_NAME_REQUIRED')" 
                                                        >
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
                        <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                        <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
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
</script>

<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/examinations/check-list.js') }}"></script>
<!-- <script src="//cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script> -->
<script src="{{ asset('assets/plugins/ckeditor/ckeditor.js') }}"></script>
<script type="text/javascript">

    var editor       = CKEDITOR.replace('introduction_text');
    var introduction = CKEDITOR.replace('final_text');

    
</script> 
@endsection 