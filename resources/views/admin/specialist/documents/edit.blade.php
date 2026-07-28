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


<style>
   /*  added by swapnil The Modal (background) */
   .modal {
   padding: 70px; /* Location of the box */
   left: 0;
   top: 0;
   width: 100%; /* Full width */
   height: 100%; /* Full height */
   overflow: auto; /* Enable scroll if needed */
   background-color: rgb(0,0,0); /* Fallback color */
   background-color: rgba(0,0,0,0.9); /* Black w/ opacity */
   }
   /* Modal Content (image) */
   .modal-content {
   margin: auto;
   display: block;
   }
   /* The Close Button */
   .close {
   position: absolute;
   top: 15px;
   right: 35px;
   color: #f1f1f1;
   font-size: 40px;
   font-weight: bold;
   transition: 0.3s;
   }
   .close:hover,
   .close:focus {
   color: #bbb;
   text-decoration: none;
   cursor: pointer;
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

               <!-- <form method="post"  enctype="multipart/form-data" id="form-create-user" name="form-create-user" action="{{ url('admin/specialist/documentUpdate/'.base64_encode(base64_encode($collection->id))) }}"> -->

                <form enctype="multipart/form-data" action="{{ url('admin/specialist/documentUpdate/'.base64_encode(base64_encode($collection->id))) }}" method="post" >
                    {{ csrf_field() }}
                <input type="hidden" name="specialist_id" id="specialist_id" value="{{$collection->fk_specialist_id}}">
                <input type="hidden" name="hd_flag" id="hd_flag" value="no">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-4"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_NAME') <span class="required">*</span></label>
                                    <input 
                                        style="margin-top: 0px;" 
                                        type="text" 
                                        name="name" 
                                        id="doc_name" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        value="{{$collection->name}}" 
                                        data-error="@lang('admin.ERR_SPECIALIST_DOCUMENT_NAME_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback" style="display: block!important;">
                                        <ul class="list-unstyled">
                                            <li class="err_name">{{$errors->first('type_of_document')}}</li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT') <!-- <span class="required">*</span> --></label>
                                    <select 
                                        onchange="checkType(this)" 
                                        class="form-control" 
                                        name="type_of_document"
                                        id="type_of_document"
                                        required
                                        data-error="@lang('admin.ERR_DOCUMENT_TYPE_OF_DOCUMENT')">
                                            <option value="">@lang('admin.TITLE_SPECIALIST_DOCUMENT_TYPE_OF_DOCUMENT_SELECT')</option>
                                            <!-- <option value="0">@lang('admin.TITLE_SELECT_DOCUMENT_UNREAD')</option> -->
                                            <option @if($collection->type_of_document=='general') selected @endif value="general">@lang('admin.TITLE_GENERAL')</option>
                                            <option @if($collection->type_of_document=='service') selected @endif  value="service" @if($collection->type_of_document== 'service') 'selected' @endif>@lang('admin.TITLE_SERVICE')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback" style="display: block!important;">
                                        <ul class="list-unstyled">
                                            <li class="err_type_of_document">{{$errors->first('type_of_document')}}</li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4" @if($collection->type_of_document == 'service') style="display: none;" @endif id="active_btn_div"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    &nbsp;</label>
                                    <input 
                                        type="button" 
                                        name="active_btn" 
                                        id="active_btn" 
                                        class="btn btn-primary"  
                                        value="@lang('admin.TITLE_CHECKLIST_ACTIVE_NEW')" 
                                        style="margin-top: 30px;" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_check_list_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <input type="hidden" name="old_header_image" value="{{$collection->header_image_path}}">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_HEADER_IMAGE') <!-- <span class="required">*</span> --></label>
                                    <input 
                                        type="file" 
                                        name="header_image" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_DOCUMENT_HEADER_IMAGE')" 
                                    > 
                                    @if($collection->header_image_path && isset($headerImageExists) && $headerImageExists==1)
                                    <p class="{{$collection->id}}_header">
                                       <!--  <img src="{{$imagaPath.$collection->header_image_path}}" style="padding-left:6px;" width="250px" height="80px">
                                        <a href="javascript:void(0)" class="delete-user action-icon" title="@lang('admin.DELETE_IMAGE_MSG')" onclick="deleteImage('{{$collection->id}}', '{{$collection->header_image_path}}', 'header');">&nbsp;&nbsp;&nbsp;
                                            <span class="fas fa-trash" style="font-size: 25px;"></span>
                                        </a> -->

                                        <!-----------added by swapnil------------------------------->    
                                        <div class="row">
                                          <div class="col-md-11">

                                             <img id="myImgheader" src="{{$imagaPath.$collection->header_image_path}}" style="width:100%;">
                                          </div>
                                          <div class="col-md-1">
                                        
                                            <a href="javascript:void(0)" class="delete-user action-icon" title="@lang('admin.DELETE_IMAGE_MSG')" onclick="deleteImage('{{$collection->id}}', '{{$collection->header_image_path}}', 'header');">&nbsp;&nbsp;&nbsp;
                                                <span class="fas fa-trash" style="font-size: 25px;"></span>
                                            </a>
                                         </div>
                                         </div>
                                          <!-------------added by swapnil----------------------------->  
                                    </p>
                                    @endif
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
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FOOTER_IMAGE') <!-- <span class="required">*</span> --></label>
                                    <input type="hidden" name="old_footer_image" value="{{$collection->footer_image_path}}">
                                    <input 
                                        type="file" 
                                        name="footer_image" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_DOCUMENT_FOOTER_IMAGE')" 
                                    >
                                    @if($collection->footer_image_path && isset($footerImageExists) && $footerImageExists==1)
                                    <p class="{{$collection->id}}_footer">
                                        <!-- <img src="{{$imagaPath.$collection->footer_image_path}}" style="padding-left:6px;" width="250px" height="80px">
                                        <a href="javascript:void(0)" class="delete-user action-icon" title="@lang('admin.DELETE_IMAGE_MSG')" onclick="deleteImage('{{$collection->id}}', '{{$collection->footer_image_path}}', 'footer');">&nbsp;&nbsp;&nbsp;
                                            <span class="fas fa-trash" style="font-size: 25px;"></span>
                                        </a> -->

                                         <!---------added by swapnil----------------->
                                         <div class="row">
                                             <div class="col-md-11">
                                                <img id="myImg" src="{{$imagaPath.$collection->footer_image_path}}" style="width:100%;">
                                             </div>
                                             <div class="col-md-1">
                                                 <a href="javascript:void(0)" class="delete-user action-icon" title="@lang('admin.DELETE_IMAGE_MSG')" onclick="deleteImage('{{$collection->id}}', '{{$collection->footer_image_path}}', 'footer');">&nbsp;&nbsp;&nbsp;
                                                   <span class="fas fa-trash" style="font-size: 25px;"></span>
                                                 </a>
                                             </div>
                                          </div>
                                        <!----------added by swapnil------------------>


                                    </p>
                                    @endif
                                    <span class="help-block invalid-feedback" style="display: block!important;">
                                        <ul class="list-unstyled">
                                            <li class="err_footer_image">{{ $errors->first('footer_image') }}</li>
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
                                        readonly 
                                        name="date_of_last_activation" 
                                        class="form-control date_of_last_activation_frm"  
                                        value="{{Date('d-m-Y',strtotime($collection->date_of_last_activation))}}"
                                        autocomplete="off" 
                                        data-error="@lang('admin.ERR_DOCUMENT_DATE_OF_ACTIVATION')" 
                                    >  
                                    <span class="help-block invalid-feedback" style="display: block!important;">
                                        <ul class="list-unstyled">
                                            <li class="err_date_of_last_activation">{{ $errors->first('date_of_last_activation') }}</li>
                                        </ul>
                                    </span>
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
                            </div>
                            <div class="col-md-1">
                                <input style="background-color: {{$collection->background_color}}" 
                                    readonly 
                                    class="form-control documentColorCls textsetColorCode textcolorpicker" 
                                > 
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3" id="div_frequency"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY') <span class="required">*</span></label>
                                    <input 
                                        style="margin-top: 0px;" 
                                        type="text" 
                                        name="frequency" 
                                        class="form-control"  
                                        value="{{$collection->frequency}}" 
                                        data-error="@lang('admin.ERR_DOCUMENT_FREQUENCY')" 
                                    >
                                    <span class="help-block invalid-feedback" style="display: block!important;">
                                        <ul class="list-unstyled">
                                            <li class="err_frequency">{{ $errors->first('frequency') }}</li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-3" id="div_frequency_type"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY_TYPE') <span class="required">*</span></label>
                                     <select 
                                        class="form-control" 
                                        name="frequency_type"
                                        id="frequency_type"
                                        data-error="@lang('admin.ERR_DOCUMENT_FREQUENCY_TYPE')">
                                            <option value="">@lang('admin.TITLE_FREQUENCY_TYPE_SELECT')</option>
                                            <option @if($collection->frequency_type=='day') selected @endif  value="day">@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                            <option @if($collection->frequency_type=='month') selected @endif value="month">@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                            <option @if($collection->frequency_type=='year') selected @endif value="year">@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback" style="display: block!important;">
                                        <ul class="list-unstyled">
                                            <li class="err_frequency_type">{{ $errors->first('frequency_type') }}</li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-2"> 
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
                             <div class="col-sm-1"> 
                                <div class="p-0 form-group"> 
                                <label class="theme-blue">@lang('admin.TITLE_SELECT_CHECK_LIST_SIGNDOC')</label>
                                <div class="form-check">
                                      <input 
                                        type="radio" 
                                        class="form-check-input" 
                                        name="signDoc" 
                                        value="read" 
                                        {{$collection->signDoc=='read'?'checked':''}}
                                        checked
                                        >
                                      <label class="form-check-label" for="status">@lang('admin.TITLE_SELECT_DOCUMENT_READ')</label>
                                </div>  
                                
                                </div>
                            </div>
                            <div class="col-sm-3"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">&nbsp;</label>
                                    <div class="form-check">
                                          <input 
                                            type="radio" 
                                            class="form-check-input" 
                                            name="signDoc" 
                                            value="sign" 
                                            {{$collection->signDoc=='sign'?'checked':''}}
                                            >
                                          <label class="form-check-label" for="status">@lang('admin.TITLE_SELECT_DOCUMENT_SIGN')</label>
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
                                    >{{$collection->html_text}}</textarea>
                                    <span class="help-block " style="display: block!important;">
                                        <ul class="list-unstyled">
                                            <li class="err_html_text">{{ $errors->first('html_text') }}</li>
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

 <!--code added by swapnil pawar 17-10-2022-->
   <!-- The Modal -->
   <div id="myModal" class="modal">
      <span class="close">&times;</span>
      <img class="modal-content" id="img01">
      <div id="caption"></div>
   </div>
   <!--code added by swapnil pawar 17-10-2022-->


</div>    
</section>
@endsection

@section('scripts')

<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/specialist/documents/create-edit.js') }}"></script>
<!-- <script src="//cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script> -->
<!-- Roshani added the below url of ckeditor -->
<script src="{{ asset('assets/plugins/ckeditor/ckeditor.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/js/bootstrap-colorpicker.min.js"></script>
<script type="text/javascript">

    var editor = CKEDITOR.replace('html_text');
    var last_date_confirmation = "{{__('admin.TITLE_CHECKLIST_LAST_ACTIVATION_CONFIRMATION')}}";
    var warming_title = "{{__('admin.WARNING_TITLE')}}";
    var current_date = "{{Date('d-m-Y')}}";
</script>

<script type="text/javascript">
function deleteImage(id, imgUrl, type)
{
    if(imgUrl != '' && imgUrl != null)
    {
        swal({
            title: deleteContent.title,
            text: "@lang('admin.DELETE_IMAGE_ALERT')",
            type: "warning",
            showCancelButton: true,
            cancelButtonText: deleteContent.cancel,
            confirmButtonText: warming_title,
            confirmButtonClass: "btn-danger",
            closeOnConfirm: true,
            showLoaderOnConfirm: true
        },
        function ()
        {
            $.ajax({
                url: "{{ url('admin/specialist/documentImageDelete') }}",
                type: "POST",
                data: "id="+id+"&imgUrl="+imgUrl+"&type="+type,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response)
                {
                    console.log(response);
                    if(response != 'Image not deleted')
                    {
                        $('.'+response).hide();
                    }
                }
            });
        });
    }
}
</script>



<!--code added by swapnil pawar 17-10-2022-->
<script>
   // Get the modal
   var modal = document.getElementById("myModal");
   // Get the image and insert it inside the modal - use its "alt" text as a caption
   var img = document.getElementById("myImg");
   var modalImg = document.getElementById("img01");
   img.onclick = function(){
     modal.style.display = "block";
     modalImg.src = this.src;
   }
   var img1 = document.getElementById("myImgheader");
   var modalImg1 = document.getElementById("img01");
   img1.onclick = function(){
     modal.style.display = "block"; 
     modalImg1.src = this.src;
   }
   // Get the <span> element that closes the modal
   var span = document.getElementsByClassName("close")[0];
   // When the user clicks on <span> (x), close the modal
   span.onclick = function() { 
     modal.style.display = "none";
   }
</script>
<!--code added by swapnil pawar 17-10-2022-->

@endsection 