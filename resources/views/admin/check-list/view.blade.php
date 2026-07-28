@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content')
<link rel="stylesheet" href="{{ asset('assets/notification/pnotify.custom.min.css') }}"> 
<link rel="stylesheet" href="{{ asset('assets/notification/animate.css') }}">
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
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_CHECKLIST_NAME'):</label>
                                <p>{{ $checkList->check_list_name }} </p>
                                
                            </div>
                        </div>
                        <div class="col-sm-3"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_CHECK_LIST_TYPE') <span class="required">*</span></label>
                                     <p>{{ucfirst($checkList->type_of_checklist)}}</p>
                                </div>
                        </div>
                        <div class="col-sm-2"> 
                            <div class="p-0 form-group"> 
                                <label class="theme-blue">@lang('admin.TITLE_EXAMINATION_STATUS') : </label>
                                <p>@if($checkList->status==1) Active @else Inactive @endif </p>
                                
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
                                        readonly="readonly" 
                                        {{$checkList->signDoc=='read'?'checked':''}}
                                        >
                                      <label class="form-check-label" for="status">@lang('admin.TITLE_SELECT_DOCUMENT_READ')</label>
                                </div> 
                                </div>
                        </div>
                        <div class="col-sm-1"> 
                            <div class="p-0 form-group"> 
                                <label class="theme-blue">&nbsp;</label>
                                <div class="form-check">
                                      <input 
                                        type="radio" 
                                        class="form-check-input" 
                                        name="signDoc" 
                                        value="sign" 
                                         readonly="readonly" 
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
                                    <p>{{$checkList->frequency}}</p>
                                </div>
                            </div>
                            <div class="col-sm-4" id="div_frequncy_type"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SPECIALIST_DOCUMENT_FREQUENCY_TYPE') <span class="required">*</span></label>
                                    <p>{{ucfirst($checkList->frequency_type)}}</p>
                                </div>
                            </div>
                            <div class="col-sm-4" id="div_date_of_last_activation"> 
                                <div class="p-0 form-group"> 
                                    <label class="theme-blue">@lang('admin.TITLE_SPECIALIST_DOCUMENT_DATE_OF_LAST_ACTIVATION')</label>
                                    <p>@if(!empty($checkList->date_of_last_activation)) {{date('d-m-Y',strtotime($checkList->date_of_last_activation))}} @endif</p>
                                </div>
                            </div>
                       
                        </div>
                    <div class="row">
                        <div class="col-sm-12"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_CHECKLIST_INTRODUCER_TEXT')</label>
                                <p>{{ strip_tags(htmlspecialchars_decode($checkList->introduction_text)) }}</p> 
                            </div>
                        </div>

                        <div class="col-sm-12"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_CHECKLIST_FINAL_TEXT') <span class="required">*</span></label> 
                                <p>{{ strip_tags(htmlspecialchars_decode($checkList->final_name)) }}</p>
                               
                            </div>
                        </div>
                    </div>

                    
                    @if(sizeof($checkList->hasheadingSection)>0)
                        <?php $subWpCnt = 1;?>
                        @foreach($checkList->hasheadingSection as $key => $val)
                            <div class="wrapper">
                                <div class="row">
                                    <div class="col-sm-12"> 
                                        <fieldset>
                                              <legend class="form-group">{{$heading_section}} : {{$subWpCnt}}
                                             
                                                </legend>
                                                <label class="theme-blue" style="margin-left: 21px;font-size: 20px;"> 
                                                    {{$val['heading_section']}}
                                                </label>
                                                <?php $queCnt = 1;?>
                                                @foreach($val['HeadingSectionHasQuestion'] as $quekey => $queval)
                                                    <div class="sub-wrapper-{{$subWpCnt}}">
                                                        <div class="row"> 
                                                            <div class="form-group col-sm-8">
                                                                <label style="margin-left: 21px;" class="theme-blue"> 
                                                                {{$queCnt}}. @lang('admin.TITLE_CHECKLIST_QUESTION') </label>
                                                                <p style="margin-left: 21px;">{{$queval['question']}}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <?php $queCnt++;?>
                                                @endforeach    
                                        </fieldset>
                                    </div>
                                </div>
                            </div>
                            <br/>
                            <?php $subWpCnt++;?>   
                        @endforeach
                    @endif  
                </div>  
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
<script src="{{ asset('assets/notification/pnotify.custom.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script src="//cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script>
<script type="text/javascript">

    var editor       = CKEDITOR.replace('introduction_text');
    var introduction = CKEDITOR.replace('final_text');

    
</script> 
@endsection 