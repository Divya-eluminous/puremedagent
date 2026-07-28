@extends('web.layout.master')
@section('title', 'User Profile')
@section('content')
<style type="text/css">
  .myPerformanceSlides {
    display: none;
    padding: 80px;
    text-align: center;
  }
  /* .prev, .next {
    cursor: pointer;
    position: absolute;
    top: 50%;
    padding: 16px;
    color: white;
    font-size: 18px;
    font-weight: bold;
    background-color: rgba(0, 0, 0, 0.8);
    border: none;
    border-radius: 50%;
    user-select: none;
    z-index: 1000;
    transform: translateY(-50%);
  } */

  #prev_button {
    left: 296px;
  }

  #next_button {
    right: 296px;
  }

  /* Ensure that the slideshow container has relative positioning */
  .slideshow-container {
    position: relative;
  }

#demo > a {    top: 50%;}
#demo > a#prev_button {    left: -59px;}
div#demo {    position: relative;}
#demo > a#next_button {    right: -59px;}


form#performancecheckListForm {    position: relative;}
form#performancecheckListForm a.prev {
    left: -59px;
}
form#performancecheckListForm a.next {
    right: -59px;
}


</style>
<style type="text/css">
  #examinationForm fieldset:not(:first-of-type) {
    display: none;
  }
</style>
<div class="container">
  <?php
  $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  ?>
  <div id="main_div">
    <input type="hidden" name="hd_general_checklist" id="hd_general_checklist" value="{{$general_checklist}}">
    <input type="hidden" name="hd_examination_flag" id="hd_examination_flag" value="{{$examination_flag}}">
    <input type="hidden" name="hd_performance_checklist" id="hd_performance_checklist" value="{{$performance_checklist}}">
    <input type="hidden" name="hd_general_doc" id="hd_general_doc" value="{{$general_doc}}">
    <input type="hidden" name="hd_service_doc" id="hd_service_doc" value="{{$service_doc}}">
    <input type="hidden" name="hd_type" id="hd_type" value="{{$type}}">
    <input type="hidden" name="hd_exam" id="hd_exam" value="@if(isset($exaination_html)){{$exaination_html}} @endif">
    <input type="hidden" name="hd_document" id="hd_document" value="@if(isset($document_html)){{$document_html}} @endif">
    <input type="hidden" name="hd_performance" id="hd_performance" value="@if(isset($getHtmlForPerformanceCheckList)){{$getHtmlForPerformanceCheckList}} @endif">

    @php
     //dd($generalCheckList);
    @endphp

    <!-- Check List Div -->
    @if(!empty($generalCheckList) && sizeof($generalCheckList)>0 )
      <div data-toggle="collapse" data-target="#demo" class="card card-primary" style="width: 100%;">
        <div class="card-header">
          <h3 class="card-title">@lang('front.TITLE_GENERAL_CHECK_LIST')</h3>
        </div>
      </div>
      <div id="demo" class="collapse show" style="display: block;">
         <!---style added in below clas on 16-june-25---->
        <div class="slideshow-container" style="background:#fff">
          @php
            $chk_counter = 0;
          @endphp
          @foreach ($generalCheckList as $key => $value)
            <div class="mySlides">

              <!--start added on 17-june-15 for header footer path------>
               <img style="max-width: 100%;" src="{{ $value['header_image_path'] }}" alt="{{ basename($value['header_image_path']) }}">
               <!--end added on 17-june-15 for header footer path------>

              <form id="checkListForm" role="form" data-toggle="validator" action="{{url('/online-appointment/generate-check-listPdf')}}" method="POST">
                <input type="hidden" name="chk_type" id="chk_type" value="{{$chk_type}}">
                <div id="general-checklist-data" data-count="{{ count($generalCheckList) }}" style="display: none;"></div>
                <div class="row">
                  <div class="col-md-5" style="text-align:left; overflow: auto;">
                    <h2>
                      <input type="hidden" name="check_list[{{$chk_counter}}][exam_id]" value="@if(isset($value['exam_id'])){{$value['exam_id']}} @endif">
                      <input type="hidden" name="check_list[{{$chk_counter}}][checklist_id]" value="{{$value['checklist_id']}}">
                      {{$value['check_list_name']}}
                    </h2>
                    <hr>
                    <h6>{!! $value['introduction_text'] !!}</h6>
                    <hr>
                    <h6>{!! $value['final_name'] !!}</h6>
                  </div>
                  <div class="col-md-1">&nbsp;</div>
                  <div class="col-md-6" style="text-align:left;">
                    @php
                      $h_cnt = 0;
                    @endphp
                    @foreach($value['heading'] as $hd_key => $hd_value)
                      <div class="col-sm-12">
                        <div class="p-0 form-group">
                          <h4>
                            <input type="hidden" name="check_list[{{$chk_counter}}][Heading][{{$h_cnt}}][heading_id]" value="{{$hd_value['heading_id']}}">
                            {{$hd_value['heading']}}
                          </h4>
                        </div>
                      </div>
                      @php
                        $q_cnt = 0;
                      @endphp
                      @foreach($hd_value['question'] as $qs_key => $qs_value)
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="p-0 form-group">
                              <div class="form-check" style="margin-left: 5px;">
                                <input type="hidden" name="check_list[{{$chk_counter}}][Heading][{{$h_cnt}}][question_hd][{{$q_cnt}}]" value="{{$qs_value['question_id']}}">
                                <input type="checkbox" class="form-check-input" name="check_list[{{$chk_counter}}][Heading][{{$h_cnt}}][question][{{$q_cnt}}]" value="{{$qs_value['question_id']}}">
                                <label class="form-check-label" for="status">{{$qs_value['question']}}</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        @php
                          $q_cnt++;
                        @endphp
                      @endforeach
                      <hr>
                      @php
                        $h_cnt++;
                      @endphp
                    @endforeach
                  </div>
                </div>

                 <!--start added on 17-june-15 for header footer path------>
                <img style="max-width: 100%;" src="{{ $value['footer_image_path'] }}" alt="{{ basename($value['footer_image_path']) }}">
                 <!--end added on 17-june-15 for header footer path------>

                 
                @if($key != count($generalCheckList)-1)
                  <div class="col-lg-12 text-center mb-4 cfooter" style="margin-top: 10px;">
                    <input class="btn btn-success" type="button" onclick="submitChecklist(this, {{$chk_counter}})" value="Bestätigen">
                  </div>
                @else
                  <div class="col-lg-12 text-center mb-4 cfooter" style="margin-top: 10px;">
                    <input class="btn btn-success" onclick="submitFrm(this)" id="btn-sub" type="button" value="Bestätigen">
                  </div>
                @endif
                @php
                  $chk_counter++;
                @endphp
              </form>
            </div>
          @endforeach
        </div>
        <!-- Navigation Arrows -->
        <a class="prev" id="prev_button" onclick="moveSlide(-1)">&#10094;</a>
        <a class="next" id="next_button"onclick="moveSlide(1)">&#10095;</a>
      </div>
    @elseif($general_checklist == 1)
      <div data-toggle="collapse" data-target="#demo" class="card card-primary" style="width: 100%;">
        <div class="card-header">
          <h3 class="card-title">@lang('front.TITLE_USER_GENERAL_CHECK_LIST')</h3>
        </div>
      </div>
    @endif
  </div>
</div>

<!-- Specialist Modal -->
<button type="button" id="getSpecilistbtn" class="btn fc-button-primary" data-toggle="modal" data-target="#getspecilist" style="display: none"></button>
<div class="modal fade" id="getspecilist" style="position:fixed;">
  <!--------added modal-xl on 16-june-25 for doc width---------------------->
  <div class="modal-dialog modal-dialog-scrollable modal-xl">
    
    <form id="frmAssignSpecilist" role="form" data-toggle="validator" action="{{ url('admin/specialist/assignedSpecialist') }}" class="w-100">

      <!--------commented on 16-june-25 for doc width---------------------->
      <!-- <div class="modal-content" style="max-height: calc(50vh - -50.5rem)!important;max-width: 680px!important;"> -->

        <!--------changed on 16-june-25 for doc width--remove width-------------------->
        <div class="modal-content" style="max-height: calc(50vh - -50.5rem)!important;">

        <div class="modal-header">
          <h3 class="card-title">@lang('front.Document')</h3>
          <!-- <button id="btnClose" type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button> -->
        </div>
        <div class="modal-body" id="document_page">
          <!-- details -->
        </div>
        <div class="modal-footer">
          <button type="reset" id="btn_swal" class="btn btn-success" data-dismiss="modal" aria-label="Close">@lang('front.Document_submit')</button>
          <!-- <button onclick="CancelDoc()" type="reset" class="btn btn-danger" class="close" data-dismiss="modal" aria-label="Close">@lang('front.TITLE_CANCEL_BUTTON')</button> -->
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
<script src="{{ asset('assets/admin-lte/plugins/daterangepicker/daterangepicker.js') }}"></script> 
<script type="text/javascript" src="{{asset('assets/web/js/user-profile-check-list.js?ver=0.01')}}"></script> 
@stop