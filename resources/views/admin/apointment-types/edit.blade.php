@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content') 
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') }}">
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
                
                <form id="appointmentForm" role="form" data-toggle="validator" action="{{ route($modulePath.'update', [base64_encode(base64_encode($appointment->id))]) }}">
                    
                     <input type="hidden" name="specialist_id" id="specialist_id" value="{{$specialist_id}}">
                     
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="defaultExaminationID" id="defaultExaminationID" value="{{$defaultExaminationID}}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_HAS_SPECIALIST') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="specialist" 
                                        class="form-control" 
                                        value="{{$specialist_details->name}}" 
                                        readonly
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_specialist"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_TYPE_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        value="{{ $appointment->name }}"  
                                        class="form-control" 
                                        required
                                        readonly 
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_APPOINTMENT_TYPE_NAME_REQUIRED')" 
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
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_TYPE_DURATION') <span class="required">*</span></label>
                                    <input type="number" 
                                    class="form-control"
                                    name="duration"
                                    required
                                    data-error="@lang('admin.ERR_APPOINTMENT_TYPE_DURATION_REQUIRED')" min='2' 
                                    value="{{$appointment->duration}}" 
                                    >
                                    <!-- <select
                                    class="form-control"
                                    value="{{ $appointment->name }}"  
                                    name="duration"
                                    required
                                    data-error="@lang('admin.ERR_APPOINTMENT_TYPE_DURATION_REQUIRED')" 
                                    >
                                        <option value="">@lang('admin.TITLE_SELECT_DURATION_TEXT')</option>
                                        @foreach($duraionArray as $duation)
                                        <option @if($appointment->duration==$duation) selected @endif value="{{ $duation }}">{{ $duation }}</option>
                                        @endforeach
                                    </select> -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_duration"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_TYPE_DESCRIPTION') <span class="required">*</span></label>
                                    <textarea
                                        type="text" 
                                        name="description"
                                        id="description" 
                                        class="form-control" 
                                        required
                                        data-error="@lang('admin.ERR_APPOINTMENT_TYPE_DESCRIPTION_REQUIRED')" 
                                    >{{ $appointment->description??'' }}</textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_description"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_RECOMMEND_EXAM_STATUS')</label>
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="recommend_exams"
                                            name="recommend_exams" 
                                            value="1" 
                                             @if(!empty($appointment->recommend_exams) && $appointment->recommend_exams==1) checked @endif
                                            >
                                        <label class="form-check-label" for="recommend_exams">@lang('admin.TITLE_APPOINTMENT_TYPE_STATUS_ACTIVE')</label>
                                      </div>
                                </div>
                            </div>
                            <div class="col-sm-3"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_TYPE_STATUS') <span class="required">*</span></label>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="status"
                                         name="status" value="1" @if(!empty($appointment->status) && $appointment->status==1) checked @endif>
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_TYPE_STATUS_ACTIVE')</label>
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
                                            @if(!empty($appointment->on_dashboard) && $appointment->on_dashboard=='1') checked @endif
                                            >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_TYPE_DASHBOARD_SETTING')</label>
                                      </div>
                                </div>
                            </div> 
                        </div>
                        
                        <!-- <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group fileParentDiv">
                                    @php 
                                        if(!empty($appointment->patient_document))
                                        {
                                            $str =  $appointment->patient_document;
                                            $hasFile = true;
                                        }
                                        else
                                        {
                                            $str = 'No File Selected.';
                                            $hasFile = false;
                                        }
                                    @endphp
                                    <label class="theme-blue">@lang('admin.TITLE_APPOINTMENT_TYPE_UPLOAD_DOC_ACTIVE')</label>
                                    <input class="form-control image" 
                                            type="file" 
                                            name="patient_document" 
                                            id="file"
                                            >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                           <li class="err_patient_document"></li>
                                        </ul>
                                    </span>
                                </div>
                                @if(!empty($appointment->patient_document) && is_file(storage_path().$appointment->patient_document_path))
                               
                                    <a href="{{ url('storage'.$appointment->patient_document_path) }}" target="_blank" class="old_file" title="document">{{ $appointment->patient_document ?? '' }}</a>
                                    <input type="hidden" name="old_doc_data" id="old_doc_data" value="{{ $appointment->patient_document_path }}">
                                    <input type="hidden" name="old_file" class="old_file" id="old_file" value="{{ $appointment->patient_document_path }}">
                                @endif
                                <button type="button" class="btn btn-danger removefile" @if(!$hasFile) style="display: none" @endif onclick="removeFile(this)" >@lang('admin.TITLE_REMOVE_BUTTON')</button>

                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_DOCUMENT_STATUS') </label>
                                    <select 
                                        class="form-control" 
                                        name="patient_document_status"
                                        id="patient_document_status"
                                        data-error="@lang('admin.ERR_DOCUMENT_STATUS_REQUIRED')">
                                            <option value="0">@lang('admin.TITLE_SELECT_DOCUMENT_STATUS')</option>
                                        
                                            <option value="1" {{ $appointment->patient_document_status == 1 ? 'selected' : '' }}>@lang('admin.TITLE_SELECT_DOCUMENT_READ')</option>
                                            <option value="2" {{ $appointment->patient_document_status == 2 ? 'selected' : '' }}>@lang('admin.TITLE_SELECT_DOCUMENT_SIGN')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_document_status"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div> --> 

                            <div class="card card-default"> 
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>@lang('admin.TITLE_PROFILE_EXAMINATIONS')</label>
                                        <select 
                                            name="examinations" 
                                            id="examinations"
                                            maxlength="250" 
                                            data-error="@lang('admin.ERR_EXAM_REQUIRED')"
                                            class="duallistbox" 
                                            multiple="multiple"
                                            >
                                        @foreach($exams as $exam)
                                            @if(!in_array($exam->id,$assigned_exam_ids))
                                                @if($exam->fk_specialist_id == $specialist_id))  
                                                <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                                                @endif
                                            @else
                                                <option @if(!empty($exam->default_service) && ucfirst($exam->name) == ucfirst($appointment->name)) disabled @endif  value="{{ $exam->id }}" selected="selected">{{ $exam->name }}</option>
                                            @endif
                                        @endforeach
                                      </select>
                                    </div>
                                <!-- /.form-group -->
                              </div>
                              <!-- /.col -->
                            </div>
                            <!-- /.row -->
                        </div>
                        <!-- added by vijay 12/3/2024 -->
                        <div class="card card-default"> 
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>@lang('admin.TITLE_PROFILE_NON_EXAMINATIONS')</label>
                                        <select 
                                            name="non_examinations"
                                            id="nonExaminations" 
                                            maxlength="250" 
                                            data-error="@lang('admin.ERR_EXAM_REQUIRED')"
                                            class="duallistbox" 
                                            multiple="multiple"
                                            >
                                        @foreach($exams as $exam)
                                            @if(!in_array($exam->id,$assigned_non_exam_ids))
                                                @if($exam->fk_specialist_id == $specialist_id))  
                                                <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                                                @endif
                                            @else
                                                <option @if(!empty($exam->default_service) && ucfirst($exam->name) == ucfirst($appointment->name)) disabled @endif  value="{{ $exam->id }}" selected="selected">{{ $exam->name }}</option>
                                            @endif
                                        @endforeach
                                      </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--  -->
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue">@lang('admin.TITLE_OPTIMAL_APPOINTMENT_LABEL')</label>
                                    <div class="custom-control custom-switch">
                                        <input 
                                            type="checkbox" 
                                            class="custom-control-input" 
                                            id="optimal_appointment" 
                                            name="optimal_appointment" 
                                            value="1" 
                                            @if(!empty($appointment->optimal_appointment) && $appointment->optimal_appointment=='1') checked @endif
                                        >
                                        <label class="custom-control-label" for="optimal_appointment" data-on="@lang('admin.TITLE_OPTIMAL_APPOINTMENT_YES')" data-off="@lang('admin.TITLE_OPTIMAL_APPOINTMENT_NO')"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div><!-- /.card-body -->

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
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/appointment-types/create-edit.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') }}"></script>
<script type="text/javascript">
       var flag = 1;
    // added by vijay
    $(document).ready(function(){
    
        $('#examinations').change(function(){
            // var selectedExaminations = $(this).val(); 
            var selectedExaminations = [];
            $(this).find('option:selected').each(function() {
                selectedExaminations.push($(this).val());
            });

            // Include disabled options in selectedExaminations
            $(this).find('option:disabled:selected').each(function() {
                var optionValue = $(this).val();
                if (!selectedExaminations.includes(optionValue)) {
                    selectedExaminations.push(optionValue);
                }
            });
            var selectedNonExaminations = $('#nonExaminations').val();
            $('#nonExaminations').empty();
            @foreach($exams as $exam)
                    if (!selectedExaminations || !selectedExaminations.includes("{{ $exam->id }}")) {
                        var selected = selectedNonExaminations && selectedNonExaminations.includes("{{ $exam->id }}") ? 'selected' : '';
                        $('#nonExaminations').append('<option value="{{ $exam->id }}" ' + selected + '>{{ $exam->name }}</option>');
                    }
   
            @endforeach
            $('#nonExaminations').bootstrapDualListbox('refresh');
        });

       
        $('#nonExaminations').change(function(){
            var selectedNonExaminations = $(this).val();
            var selectedExaminations = $('#examinations').val();
            
            $('#examinations').empty();
            
            @foreach($exams as $exam)
                if (!selectedNonExaminations || !selectedNonExaminations.includes("{{ $exam->id }}")) {
                    var selected = selectedExaminations && selectedExaminations.includes("{{ $exam->id }}") ? 'selected' : '';
                    var selectedAndDisabled = "{{ !empty($exam->default_service) && ucfirst($exam->name) == ucfirst($appointment->name) }}";
                    if(!selectedAndDisabled){
                        $('#examinations').append('<option value="{{ $exam->id }}" ' + selected + '>{{ $exam->name }}</option>');
                    }
                }
            @endforeach

            @foreach($exams as $exam)
                if (!selectedNonExaminations || !selectedNonExaminations.includes("{{ $exam->id }}")) {
                    // var selectedAndDisabled = "{{ !empty($exam->default_service) && ucfirst($exam->name) == ucfirst($appointment->name) }}";//commented on 12-may-25

                    //did changes on 12-may-25
                    var selectedAndDisabled = "{{ !empty($exam->default_service) && ucfirst($exam->name) == ucfirst($appointment->name) && $exam->fk_specialist_id == $specialist_id }}";

                    console.log("selectedAndDisabled");
                    console.log(selectedAndDisabled);
                    // var exm = {{ $exam->id }};
                    // console.log("exm");
                    // console.log(exm); 

                    if(selectedAndDisabled){
                        $('#examinations').append('<option value="{{ $exam->id }}" selected disabled>{{ $exam->name }}</option>');
                    }
                }
            @endforeach
            
            $('#examinations').bootstrapDualListbox('refresh');
        });
         $('#examinations').change();
        $('#nonExaminations').change();
    });
    </script>
@endsection