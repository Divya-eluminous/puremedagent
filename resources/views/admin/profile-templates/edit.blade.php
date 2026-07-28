@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}  
@endsection

@php
$isCurrent = auth()->user()->id === (int)base64_decode(base64_decode(request()->segment(3))) ?true:false;
@endphp

@section('content') 
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') }}">
<!-- Main Content -->
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

                <form id="frmProfileTemplates" role="form" data-toggle="validator" action="{{ route($modulePath.'.update', [base64_encode(base64_encode($templates->id))]) }}">
                        <input type="hidden" name="_method" value="PUT">
                    <div class="card-body">  
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="d-flex flex-column mb-25 form-group">
                                    <label class="theme-blue">@lang('admin.TITLE_PROFILE_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text"  
                                        name="name"
                                        value="{{ $templates->name }}"  
                                        class="form-control" 
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_PROFILE_NAME_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PROFILE_AGE_FROM') <span class="required">*</span></label>
                                    <select 
                                        name="age_from" 
                                        class="form-control" 
                                        value="{{ $templates->age_from }}"
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_AGE_FROM_REQUIRED')"
                                    > 
                                        <option value="">From Age</option>
                                        @for ($i = 12; $i <= 80; $i++)
                                            <option value="{{ $i }}" {{ $i == $templates['age_from'] ? 'selected="selected"' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_age_from"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PROFILE_AGE_TO') <span class="required">*</span></label>
                                    <select 
                                        name="age_to" 
                                        class="form-control" 
                                        value="{{ $templates->age_to }}"
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_AGE_TO_REQUIRED')"
                                    >
                                        <option value="">To Age</option>  
                                        @for ($i = 12; $i <= 80; $i++)
                                            <option value="{{ $i }}" {{ $i == $templates['age_to'] ? 'selected="selected"' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_age_to"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="d-flex flex-column mb-25 form-group">
                                     <label class="theme-blue">@lang('admin.TITLE_PROFILE_STATUS')</label>
                                    <div class="checkbox">
                                          <input 
                                            type="checkbox"   
                                            name="status" 
                                            value="1"
                                            {{$templates->status==1?'checked':''}}
                                            >
                                            @lang('admin.TITLE_PROFILE_STATUS_ACTIVE') 
                                    </div>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_status"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>  
                        </div>
                        <div class="card card-default"> 
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>@lang('admin.TITLE_PROFILE_EXAMINATIONS')</label>
                                        <select 
                                            name="examinations" 
                                            required
                                            maxlength="250" 
                                            data-error="@lang('admin.ERR_EXAM_REQUIRED')"
                                            class="duallistbox" 
                                            multiple="multiple"
                                            >
                                        @foreach($exams as $exam)
                                            @if(!in_array($exam->id,$assigned_exam_ids)) 
                                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                                            @else
                                                <option value="{{ $exam->id }}" selected="selected">{{ $exam->name }}</option>
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
                    </div><!-- /.card-body -->

                    <div class="card-footer">
                        <!-- <button type="submit" class="btn btn-danger">Cancel</button> -->
                        <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
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
// console.log(data);
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/admin/js/profile-templates/create-edit.js') }}"></script> 
<script src="{{ asset('assets/admin-lte/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') }}"></script> 
@endsection