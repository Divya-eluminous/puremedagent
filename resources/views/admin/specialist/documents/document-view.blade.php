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
.documentColorClss
{
    border-radius: 20px!important;
    width: 80px;
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

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT') <!-- <span class="required">*</span> --></label>
                                <p>{{ucfirst($collection->type_of_document)}}</p>
                            </div>
                        </div>

                        <div class="col-sm-3"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_SPECIALIST_DOCUMENT_NAME') </label>
                                <p>{{ucfirst($collection->name)}}</p>
                            </div>
                        </div>

                        <div class="col-sm-3"> 
                            <div class="p-0 form-group"> 
                                <label class="theme-blue">@lang('admin.TITLE_SPECIALIST_DOCUMENT_DATE_OF_LAST_ACTIVATION')</label>
                                <p>{{Date('d-m-Y',strtotime($collection->date_of_last_activation))}}</p>
                            </div>
                        </div>
                    </div>

              <!--       <div class="row">
                        <div class="col-sm-6"> 
                            <div class="form-group">
                                <input type="hidden" name="old_header_image" value="{{$collection->header_image_path}}">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_SPECIALIST_DOCUMENT_HEADER_IMAGE') </label>
                                <input 
                                    type="file" 
                                    name="header_image" 
                                    class="form-control"  
                                    maxlength="250" 
                                    data-error="@lang('admin.ERR_DOCUMENT_HEADER_IMAGE')" 
                                >
                                <span class="help-block invalid-feedback" style="display: block!important;">
                                    <ul class="list-unstyled">
                                        <li class="err_header_image">{{ $errors->first('header_image') }}</li>
                                    </ul>
                                </span>
                            </div>
                        </div>

                        <div class="col-sm-6"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_SPECIALIST_DOCUMENT_FOOTER_IMAGE') </label>
                                <input type="hidden" name="old_footer_image" value="{{$collection->footer_image_path}}">
                                <input 
                                    type="file" 
                                    name="footer_image" 
                                    class="form-control"  
                                    maxlength="250" 
                                    data-error="@lang('admin.ERR_DOCUMENT_FOOTER_IMAGE')" 
                                >
                                <span class="help-block invalid-feedback" style="display: block!important;">
                                    <ul class="list-unstyled">
                                        <li class="err_footer_image">{{ $errors->first('footer_image') }}</li>
                                    </ul>
                                </span>
                            </div>
                        </div>
                    </div> -->

                
                    <div class="row">
                      <!--   <div class="col-sm-5"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_SPECIALIST_DOCUMENT_BACKGROUND_COLOR') <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    name="background_color" 
                                    id="background_color" 
                                    class="form-control text_color"  
                                    required
                                    value="{{$collection->background_color}}" 
                                    autocomplete="off" 
                                    maxlength="250" 
                                    data-error="@lang('admin.ERR_ORDINATION_TEST_COLOR_CODE')" 
                                >
                                <span class="help-block invalid-feedback" style="display: block!important;">
                                    <ul class="list-unstyled">
                                        <li class="err_background_color">{{ $errors->first('background_color') }}</li>
                                    </ul>
                                </span>
                            </div>
                        </div> -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_SPECIALIST_DOCUMENT_BACKGROUND_COLOR') <span class="required">*</span></label>
                                <input style="background-color: {{$collection->background_color}}" 
                                readonly 
                                class="form-control documentColorClss textsetColorCode textcolorpicker" 
                                > 
                            </div>    
                        </div>

                         <div class="col-sm-3"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY')</label>
                                <p>{{ucfirst($collection->frequency)}}</p>
                            </div>
                        </div>
                        <div class="col-sm-3"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY_TYPE')</label>
                                 <p>{{ucfirst($collection->frequency_type)}}</p>
                            </div>
                        </div>

                        <div class="col-sm-3"> 
                            <div class="p-0 form-group"> 
                                <label class="theme-blue">@lang('admin.TITLE_SPECIALIST_DOCUMENT_STATUS')</label>
                                 <div class="form-check">
                                      <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        id="status"
                                        name="status" 
                                        value="1" 
                                        
                                        @if($collection->status=='1') checked @endif
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
                                <p>{{strip_tags($collection->html_text)}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                   
            </div>
        </div>
    </div>
</div>    
</section>
@endsection

@section('scripts')

<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/specialist/documents/create-edit.js') }}"></script>
<script src="//cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/js/bootstrap-colorpicker.min.js"></script>
<script type="text/javascript">

    var editor = CKEDITOR.replace('html_text');
</script> 

@endsection 