@extends('web.layout.master')
@section('title', 'Register')
@section('content')

<!-- <div class="container">
  <div class="row">
    <div class="main_content book_data">
            <div class="card card-primary">   
                <div class="card-header">
                    <h3 class="card-title">Services</h3>
                </div>
        
                <form id="frmExamination" method="post" data-toggle="validator" action="{{url('/online-appointment/get-document-examination')}}">
                  {{ csrf_field() }}
                  <div class="card-body">
                    @if(!empty($getExamination) && sizeof($getExamination)>0)
                      @foreach($getExamination as $exam_key =>$exam_val)

                      <div class="row">
                        <div class="col-sm-12"> 
                            <div class="p-0 form-group"> 
                                <div class="form-check" style="margin-left: 5px;">

                                      <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        name="exam[{{$exam_key}}]" 
                                        value="{{$exam_val['id']}}" 
                                        >
                                      <label class="form-check-label" for="status">
                                        {{$exam_val['name']}}
                                      </label>
                                </div>  
                            </div>
                        </div>
                      </div>
                      @endforeach
                    @endif  
                  </div>
                  <div class="card-footer">
                      <button type="submit" class="btn btn-success" onclick="getDoctorTimeFrames()">@lang('front.TITLE_SEARCH_TEXT')</button>
                  </div>
                </form>
            </div>
        </div>
    </div>
</div>  -->
  

<!-- Document Submit -->

@if(!empty($generalDocumentList) && sizeof($generalDocumentList)>0)
<div class="container">
  <form id="frmExamination" method="post" data-toggle="validator" action="{{url('/user-profile/generate-Document-listPdf')}}">
    {{ csrf_field() }}
  <input type="hidden" name="type" id="type" value="{{$chk_type}}">

  <div class="row">
    <div class="main_content book_data">
            <div class="card card-primary">   
                <div class="card-header">
                    <h3 class="card-title">@lang('front.Document')</h3>
                </div>
                <div class="card-body">
                  @if(!empty($generalDocumentList) && sizeof($generalDocumentList)>0)
                    <?php $cnt =1;?>
                    @foreach($generalDocumentList as $doc_key =>$doc_val)
                    <div class="row">
                      <div class="col-sm-12"> 
                          <div class="p-0 form-group"> 
                              <div class="form-check" style="margin-left: 5px;">
                                    <input type="hidden" name="doc_hd[]" value="{{$doc_val["doc_id"]}}">
                                    <input type="hidden" name="exam_id[]" id="exam_id" value="{{$doc_val["exam_id"]}}">
                                   <input 
                                        onclick="getDocument('{{$doc_val["doc_id"]}}')"
                                        type="checkbox" 
                                        class="form-check-input" 
                                        name="doc[]" 
                                        value="{{$doc_val["doc_id"]}}" 
                                    >
                                    <label class="form-check-label" for="status">
                                      {{ucfirst($doc_val['name'])}}
                                    </label>
                                    
                              </div>  
                          </div>
                          <hr>
                      </div>
                   
                    </div>
                    <?php $cnt++;?>
                    @endforeach
                  @endif  
                </div><!-- /.card-body -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-success" onclick="getDoctorTimeFrames()">@lang('front.TITLE_SEARCH_TEXT')</button>
                </div>
                
            </div>
        </div>
    </div>
  </form>
</div> 
  
@endif

<!--  -->
<button type="button" id="getSpecilistbtn" class="btn fc-button-primary" data-toggle="modal" data-target="#getspecilist" style="display: none">
</button>
<div class="modal fade" id="getspecilist" style="position:fixed;">
  <div class="modal-dialog modal-dialog-scrollable" >
  
    <form id="frmAssignSpecilist" role="form" data-toggle="validator" action="{{ url('admin/specialist/assignedSpecialist') }}" class="w-100">
      <div class="modal-content" style="max-height: calc(50vh - -50.5rem)!important;max-width: 680px!important;">
          <div class="modal-header">
            <h3 class="card-title">@lang('front.Document')</h3>
            <button id="btnClose" type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span></button>                
          </div>               
          <div class="modal-body" id="document_page">
              <!-- details -->
          </div>
          <div class="modal-footer">
          <button type="reset" id="btn_swal" class="btn btn-success" data-dismiss="modal" aria-label="Close" >@lang('front.Document_submit')</button>
          <button onclick="CancelDoc()" type="reset" class="btn btn-danger" class="close" data-dismiss="modal" aria-label="Close">@lang('admin.TITLE_CANCEL_BUTTON')</button>
      </div>
      </div>      
      
    </form>
      <!-- /.modal-content -->
  </div>
   <!-- /.modal-dialog -->
</div>

@section('scripts')

<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
<script src="{{ asset('assets/admin-lte/plugins/daterangepicker/daterangepicker.js') }}"></script> 
<script type="text/javascript" src="{{asset('assets/web/js/user-profile-document-list.js')}}"></script> 
<script>
var slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) {
  showSlides(slideIndex += n);
}

function currentSlide(n) {
  showSlides(slideIndex = n);
}

function showSlides(n) {
  var i;
  var slides = document.getElementsByClassName("mySlides");
  var dots = document.getElementsByClassName("dot");
  if (n > slides.length) {slideIndex = 1}    
  if (n < 1) {slideIndex = slides.length}
  for (i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";  
  }
  for (i = 0; i < dots.length; i++) {
      dots[i].className = dots[i].className.replace(" active", "");
  }
  slides[slideIndex-1].style.display = "block";  
  dots[slideIndex-1].className += " active";
}
</script>
@stop