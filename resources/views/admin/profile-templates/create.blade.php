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
                
                <form id="frmProfileTemplates" role="form" data-toggle="validator" action="{{ route($modulePath.'.store') }}"> 
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PROFILE_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="name" 
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
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_AGE_FROM_REQUIRED')"
                                    > 
                                        <option value="">@lang('admin.TITLE_PROFILE_AGE_FROM')</option>
                                        @for ($i = 12; $i <= 80; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
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
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_AGE_TO_REQUIRED')"
                                    >
                                        <option value="">@lang('admin.TITLE_PROFILE_AGE_TO')</option>
                                        @for ($i = 12; $i <= 80; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
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
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PROFILE_STATUS') </label>
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status" 
                                            value="1" 
                                            checked
                                            >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_PROFILE_STATUS_ACTIVE')</label>
                                      </div>
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
                                            data-error="@lang('admin.ERR_EXAM_REQUIRED')"
                                            class="duallistbox" 
                                            multiple="multiple" >
                                        @foreach($exams as $exam)
                                        <option value="{{ $exam->id }}">{{ $exam->name }}</option>
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
<script src="{{ asset('assets/admin-lte/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') }}"></script> 
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/admin/js/profile-templates/create-edit.js') }}"></script> 
@endsection  