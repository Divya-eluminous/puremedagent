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
                <input type="hidden"   name="site_url" id="site_url" value="{{ url('/')}}" />

                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button>
                </div>
                    
                <form id="appointmentForm" role="form" data-toggle="validator" action="{{ route($modulePath.'store') }}">
                    <input type="hidden" name="specialist_id" id="specialist_id" value="{{$specialist_id}}">
                    <input  type="hidden" name="url"  id="url" >
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ORDINATION_HAS_SPECIALIST') <span class="required">*</span></label>
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
                                        id="name"
                                        class="form-control" 
                                        required
                                        maxlength="250" 
                                        onblur="convertToSlug(this.value)"
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
                                    data-error="@lang('admin.ERR_APPOINTMENT_TYPE_DURATION_REQUIRED')" min='2' >
                                    <!-- <select
                                    class="form-control"
                                    name="duration"
                                    required
                                    data-error="@lang('admin.ERR_APPOINTMENT_TYPE_DURATION_REQUIRED')" 
                                    >
                                        <option value="">@lang('admin.TITLE_SELECT_DURATION_TEXT')</option>
                                        @foreach($duraionArray as $duation)
                                        <option value="{{ $duation }}">{{ $duation }}</option>
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
                                    ></textarea>
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
                                            >
                                        <label class="form-check-label" for="recommend_exams">@lang('admin.TITLE_APPOINTMENT_TYPE_STATUS_ACTIVE')</label>
                                      </div>
                                </div>
                            </div>
                            <div class="col-sm-3"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_TYPE_STATUS')</label>
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status" 
                                            value="1" 
                                            checked
                                            >
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
                                            >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_TYPE_DASHBOARD_SETTING')</label>
                                      </div>
                                </div>
                            </div>                        
                        </div>
                        <!-- <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
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
                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_DOCUMENT_STATUS') </label>
                                    <select 
                                        class="form-control" 
                                        name="patient_document_status"
                                        data-error="@lang('admin.ERR_DOCUMENT_STATUS_REQUIRED')">
                                            <option value="0">@lang('admin.TITLE_SELECT_DOCUMENT_STATUS')</option>
                                          
                                            <option value="1" selected>@lang('admin.TITLE_SELECT_DOCUMENT_READ')</option>
                                            <option value="2">@lang('admin.TITLE_SELECT_DOCUMENT_SIGN')</option>
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_patient_document_status"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>  -->

                        <div class="card card-default">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>@lang('admin.TITLE_PROFILE_EXAMINATIONS')</label>
                                        <select 
                                            name="examinations" 
                                            id="examinations"
                                            data-error="@lang('admin.ERR_EXAM_REQUIRED')"
                                            class="duallistbox" 
                                            multiple="multiple" >
                                            @foreach($exams as $exam)
                                                @if(empty($exam->default_service))  
                                                <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_exam"></li>
                                            </ul>
                                        </span> 
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
                                            data-error="@lang('admin.ERR_EXAM_REQUIRED')"
                                            class="duallistbox" 
                                            multiple="multiple" >
                                            @foreach($exams as $exam)
                                                @if(empty($exam->default_service) && !in_array($exam->id, $selected_examinations))  
                                                <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_non_exam"></li>
                                            </ul>
                                        </span> 
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
                                            checked
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
<script src="{{ asset('assets/admin-lte/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/appointment-types/create-edit.js') }}"></script>
<script type="text/javascript">
       var flag = 0;


    function convertToSlug(Text)
    {
        var slug =  Text
        .toLowerCase()
        .replace(/ /g,'-')
        .replace(/[^\w-]+/g,'')
        ;
        $url = document.getElementById("site_url").value;
        document.getElementById("url").value = $url+'/'+slug;
    }
    
    // added by vijay 12/3/2024
    $(document).ready(function(){
    
        $('#examinations').change(function(){
            var selectedExaminations = $(this).val(); 
            var selectedNonExaminations = $('#nonExaminations').val();
            $('#nonExaminations').empty();
            
            @foreach($exams as $exam)
                @if(empty($exam->default_service))  
                    if (!selectedExaminations || !selectedExaminations.includes("{{ $exam->id }}")) {
                        var selected = selectedNonExaminations && selectedNonExaminations.includes("{{ $exam->id }}") ? 'selected' : '';
                        $('#nonExaminations').append('<option value="{{ $exam->id }}" ' + selected + '>{{ $exam->name }}</option>');
                    }
                @endif
            @endforeach
            $('#nonExaminations').bootstrapDualListbox('refresh');
        });

        $('#nonExaminations').change(function(){
            var selectedNonExaminations = $(this).val();
            var selectedExaminations = $('#examinations').val();
            
            $('#examinations').empty();
            
            @foreach($exams as $exam)
                @if(empty($exam->default_service))  
                    if (!selectedNonExaminations || !selectedNonExaminations.includes("{{ $exam->id }}")) {
                        var selected = selectedExaminations && selectedExaminations.includes("{{ $exam->id }}") ? 'selected' : '';
                        $('#examinations').append('<option value="{{ $exam->id }}" ' + selected + '>{{ $exam->name }}</option>');
                    }
                @endif
            @endforeach
            
            $('#examinations').bootstrapDualListbox('refresh');
        });

    });

    </script>
@endsection