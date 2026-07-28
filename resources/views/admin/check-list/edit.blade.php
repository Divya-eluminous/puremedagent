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
                <form id="checkListForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.update', [base64_encode(base64_encode($checkList->id))]) }}" >
                     
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="specialist_id" id="specialist_id" value="@if(!empty($specialist_details)){{$specialist_details->id}}" @endif" >
                    <input type="hidden" name="hd_flag" id="hd_flag" value="no">
                    <div class="card-body">
                        <div class="row">
                            @if(!empty($specialist_details))
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
                                        value="@if(!empty($specialist_details)){{$specialist_details->name}} @endif" 
                                    >
                                </div>
                            </div>
                            @endif
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
                                            <option value="performance" {{$checkList->type_of_checklist=='performance'?'selected':''}}>@lang('admin.TITLE_TYPE_CHECKLIST_PERFORMANCE')</option>
                                            <option value="general" {{$checkList->type_of_checklist=='general'?'selected':''}}>@lang('admin.TITLE_TYPE_CHECKLIST_GENERAL')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_type_of_checklist"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4" @if($checkList->type_of_checklist == 'performance') style="display: none;" @endif id="active_btn_div"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    &nbsp;</label>
                                    <input 
                                        type="button" 
                                        name="active_btn" 
                                        id="active_btn" 
                                        class="btn btn-primary"  
                                        value="@lang('admin.TITLE_CHECKLIST_ACTIVE_NEW')" 
                                        style="margin-top: 36px;" 
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
                                        value="{{ $checkList->check_list_name }}"   
                                        data-error="@lang('admin.ERR_CHECK_LIST_NAME_REQUIRED')">
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_check_list_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-1">&nbsp;</div>
                            <div class="col-sm-2"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">@lang('admin.TITLE_EXAMINATION_STATUS')</label>
                                    <div class="form-check">
                                          <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status" 
                                            value="1" 
                                            {{$checkList->status==1?'checked':''}}
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
                                        {{$checkList->signDoc=='read'?'checked':''}}
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
                                             {{$checkList->signDoc=='sign'?'checked':''}}
                                            >
                                          <label class="form-check-label" for="status">@lang('admin.TITLE_SELECT_DOCUMENT_SIGN')</label>
                                    </div>  
                                    
                                </div>
                            </div>
                        </div>
                       
                        <div  class="row"  id="div_general_type"  @if($checkList->type_of_checklist == 'performance') style="display: none;" @endif>
                            <div class="col-sm-4" id="div_frequncy" > 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="frequency"
                                        id="frequency" 
                                        value="{{$checkList->frequency}}" 
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
                                        style="margin-top: 5px!important;"
                                        class="form-control" 
                                        name="frequency_type"
                                        id="frequency_type"
                                        >
                                            <option value="">@lang('admin.TITLE_FREQUENCY_TYPE_SELECT')</option>
                                            <option {{$checkList->frequency_type=='day'?'selected':''}} value="day">@lang('admin.TITLE_FREQUENCY_DAY')</option>
                                            <option {{$checkList->frequency_type=='month'?'selected':''}} value="month">@lang('admin.TITLE_FREQUENCY_MONTH')</option>
                                            <option {{$checkList->frequency_type=='year'?'selected':''}} value="year">@lang('admin.TITLE_FREQUENCY_YEAR')</option>
                                    </select> 
                                    <span class="invalid-feedback" id="err_frequency_type">
                                        <ul class="list-unstyled">
                                            <li class="">@lang('admin.ERR_DOCUMENT_FREQUENCY_TYPE')</li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4" id="div_date_of_last_activation" > 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">@lang('admin.TITLE_SPECIALIST_DOCUMENT_DATE_OF_LAST_ACTIVATION')<span class="required">*</span></label>
                                    <input
                                        type="text" 
                                        readonly
                                        name="date_of_last_activation" 
                                        class="form-control date_of_last_activation_frm"  
                                        value="@if(!empty($checkList->date_of_last_activation)) {{date('d-m-Y h:i:s',strtotime($checkList->date_of_last_activation))}} @endif" 
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


                         <!-------------Header and footer-code added on 26-dec-22------------------>
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <input type="hidden" name="old_header_image" value="{{$checkList->header_image_path}}">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_CHECKLIST_DOCUMENT_HEADER_IMAGE') <!-- <span class="required">*</span> --></label>
                                    <input 
                                        type="file" 
                                        name="header_image" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_CHECKLIST_DOCUMENT_HEADER_IMAGE')" 
                                    >
                                    @if($checkList->header_image_path)
                                    <p class="{{$checkList->id}}_header">
                                       <!--  <img src="{{$imagaPath.$checkList->header_image_path}}" style="padding-left:6px;" height="80px">
                                        <a href="javascript:void(0)" class="delete-user action-icon" title="@lang('admin.DELETE_IMAGE_MSG')" onclick="deleteImage('{{$checkList->id}}', '{{$checkList->header_image_path}}', 'header');">&nbsp;&nbsp;&nbsp;
                                            <span class="fas fa-trash" style="font-size: 25px;"></span>
                                        </a> -->

                                          <div class="row">
                                             <div class="col-md-11">
                                                <img id="myImgheader" src="{{$imagaPath.$checkList->header_image_path}}" style="width:100%;">
                                             </div>
                                             <div class="col-md-1">
                                                   <a href="javascript:void(0)" class="delete-user action-icon" title="@lang('admin.DELETE_IMAGE_MSG')" onclick="deleteImage('{{$checkList->id}}', '{{$checkList->header_image_path}}', 'header');">&nbsp;&nbsp;&nbsp;
                                                      <span class="fas fa-trash" style="font-size: 25px;"></span>
                                                   </a>
                                             </div>
                                          </div> 

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
                                    @lang('admin.TITLE_CHECKLIST_DOCUMENT_FOOTER_IMAGE') <!-- <span class="required">*</span> --></label>
                                    <input type="hidden" name="old_footer_image" value="{{$checkList->footer_image_path}}">
                                    <input 
                                        type="file" 
                                        name="footer_image" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_CHECKLIST_DOCUMENT_FOOTER_IMAGE')" 
                                    >
                                    @if($checkList->footer_image_path)
                                    <p class="{{$checkList->id}}_footer">
                                       <!--  <img src="{{$imagaPath.$checkList->footer_image_path}}" style="padding-left:6px;" height="80px">
                                        <a href="javascript:void(0)" class="delete-user action-icon" title="@lang('admin.DELETE_IMAGE_MSG')" onclick="deleteImage('{{$checkList->id}}', '{{$checkList->footer_image_path}}', 'footer');">&nbsp;&nbsp;&nbsp;
                                            <span class="fas fa-trash" style="font-size: 25px;"></span>
                                        </a> -->

                                        <div class="row">
                                             <div class="col-md-11">
                                                <img id="myImg" src="{{$imagaPath.$checkList->footer_image_path}}" style="width:100%;">
                                             </div>
                                             <div class="col-md-1">
                                                 <a href="javascript:void(0)" class="delete-user action-icon" title="@lang('admin.DELETE_IMAGE_MSG')" onclick="deleteImage('{{$checkList->id}}', '{{$checkList->footer_image_path}}', 'footer');">&nbsp;&nbsp;&nbsp;
                                                  <span class="fas fa-trash" style="font-size: 25px;"></span>
                                               </a>
                                             </div>
                                          </div>


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

                     
                        <!-----------Header and footer-code added on 26-dec22----------->


                        

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
                                    >{{$checkList->introduction_text }}</textarea>
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
                                    >{{$checkList->final_name }}</textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_final_text"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if(sizeof($checkList->hasheadingSection)>0)
                            <?php $subWpCnt = 1;?>
                            @foreach($checkList->hasheadingSection as $key => $val)
                                <div class="wrapper">
                                    <div class="row">
                                        <div class="col-sm-8"> 
                                            <fieldset>
                                                  <legend class="form-group">{{$heading_section}} :
                                                    <input 
                                                        type="text" 
                                                        name="heading_section[{{$subWpCnt}}][heading_section][heading][]" 
                                                        class="form-control"  
                                                        required
                                                        value="{{$val['heading_section']}}" 
                                                        data-error="@lang('admin.ERR_CHECK_LIST_NAME_REQUIRED')" />
                                                        <span class="help-block invalid-feedback with-errors">
                                                            <ul class="list-unstyled">
                                                                <li class="err_heading_section"></li>
                                                            </ul>
                                                        </span>
                                                    </legend>
                                                    <?php $queCnt = 1;?>
                                                    @foreach($val['HeadingSectionHasQuestion'] as $quekey => $queval)
                                                        <div class="sub-wrapper-{{$subWpCnt}}">
                                                            <div class="row"> 
                                                                <div class="form-group col-sm-8">
                                                                    <label class="theme-blue"> 
                                                                    @lang('admin.TITLE_CHECKLIST_QUESTION') <span class="required">*</span></label>
                                                                    <input 
                                                                        type="text" 
                                                                        name="heading_section[{{$subWpCnt}}][heading_section][question][]" 
                                                                        class="form-control"  
                                                                        required
                                                                        value="{{$queval['question']}}"
                                                                        data-error="@lang('admin.ERR_CHECK_LIST_NAME_REQUIRED')" 
                                                                    >
                                                                    <span class="help-block invalid-feedback with-errors">
                                                                        <ul class="list-unstyled">
                                                                            <li class="err_heading_section"></li>
                                                                        </ul>
                                                                    </span>
                                                                </div>
                                                                <div class="col-sm-4" style="margin-top: 37px;">
                                                                    @if($queCnt == 1)
                                                                    <a onclick="AddQuetion(this,'{{$subWpCnt}}')" class="action-icon" title="@lang('admin.TITLE_CHECKLIST_ADD_MORE')" ><button type="button" id="" class="btn btn-md btn-primary">+</button></a>
                                                                    @else
                                                                    <a onclick="removeDivEdit(this,'{{$subWpCnt}}',1,'{{$queval["id"]}}')" class="action-icon" title="" ><button class="btn btn-danger remove" type="button"><span class="fas fa-trash"></span></button></a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php $queCnt++;?>
                                                    @endforeach    
                                            </fieldset>
                                        </div>
                                        <div class="col-sm-4">
                                            @if($subWpCnt == 1)
                                            <a href="javascript:void(0)" class="action-icon" title="@lang('admin.TITLE_CHECKLIST_ADD_MORE')" ><button type="button" id="addBtn" class="btn btn-md btn-primary">+</button></a>
                                            @else
                                                <a onclick="headingSectionRemove(this,'{{$subWpCnt}}',1,'{{$val["id"]}}')" class="action-icon" title="" ><button class="btn btn-danger remove" type="button"><span class="fas fa-trash"></span></button></a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <br/>
                                <?php $subWpCnt++;?>   
                            @endforeach
                        @else
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
                        @endif  
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                        <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
                    </div>
                </form> 
            </div>
        </div>
    </div>


 <!--code added by swapnil pawar 26-dec-2022-uploaded--->
   <!-- The Modal -->
   <div id="myModal" class="modal">
      <span class="close">&times;</span>
      <img class="modal-content" id="img01">
      <div id="caption"></div>
   </div>
   <!--code added by swapnil pawar 26-dec-2022-->
   

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
    var last_date_confirmation = "{{__('admin.TITLE_CHECKLIST_LAST_ACTIVATION_CONFIRMATION')}}";
    var warming_title = "{{__('admin.WARNING_TITLE')}}";
    var current_date = "{{Date('d-m-Y H:i:s')}}";
   
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/check-list/create-edit.js') }}"></script>
<!-- <script src="//cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script> -->
<script src="{{ asset('assets/plugins/ckeditor/ckeditor.js') }}"></script>
<script type="text/javascript">

    var editor       = CKEDITOR.replace('introduction_text');
    var introduction = CKEDITOR.replace('final_text');

    
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
                url: "{{ url('admin/check-list/checklistImageDelete') }}",
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