@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection

@section('content')
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

                <form id="settingForm" role="form" data-toggle="validator" action="{{ route($modulePath.'updateDismissal', [base64_encode(base64_encode($setting->id))]) }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SETTING_KEY') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="setting_key"
                                        id="setting_key" 
                                        value="{{ $setting->setting_key }}"  
                                        class="form-control" 
                                        disabled
                                    >
                                    <!-- <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_setting_key"></li>
                                        </ul>
                                    </span> -->
                                </div>
                            </div>
                        </div>
                      <!--   <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SETTING_VALUE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="setting_value" 
                                        value="{{ $setting->setting_value }}" 
                                        class="form-control" 
                                        required
                                        data-error="@lang('admin.ERR_SETTING_VALUE_REQUIRED')" 
                                    >
                                    <span id="validate_value"></span>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_setting_value"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SETTING_DESCRIPTION') <span class="required">*</span></label>
                                    <textarea
                                        type="text" 
                                        name="description" 
                                        class="form-control" 
                                        row=0
                                        required
                                        data-error="@lang('admin.ERR_SETTING_DESCRIPTION_REQUIRED')" 
                                    >{{ $setting->description }}</textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_description"></li> 
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div> -->
                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SETTING_STATUS') <span class="required">*</span></label>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="status"
                                         name="status" value="1" @if(!empty($setting->status) && $setting->status==1) checked @endif
                                        >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_SETTING_STATUS_ACTIVE')</label>
                                      </div>
                                </div> 
                            </div>
                        </div>
                        <div class="container pt-4"> 
                            <div class="table-responsive"> 
                                <table class="table "> 
                                    <thead> 
                                        <tr> 
                                            <th class="text-center" style="width:10%">@lang('admin.TITLE_SETTING_STATUS_ACTIVE')</th>
                                            <th class="text-center" style="width:50%">@lang('admin.TITLE_DISMISSAL_NAME')</th> 
                                            <th class="text-center" style="width:50%"></th> 
                                        </tr> 

                                    </thead> 
                                <tbody id="tbody"> 
                                    <input type="hidden" name="total_count" value="{{ count($dismissal) }}" id="total_count"/>

                                @if(!empty($dismissal))
                                    @foreach($dismissal as $key=>$value)
                                        <tr id="R${++rowIdx}"> 
                                            <td class="row-index text-center form-group">  
                                                <input type="checkbox" name="b_status[]" value="1" class="form-check-input " 
                                                @if($value->status == '1') checked @endif />      
                                            </td> 
                                            <td class="row-index text-left form-group">    
                                                <input type="text" name="dismissal[]" value="{{ $value->name }}" class="form-control" />
                                                <span class="help-block invalid-feedback with-errors">
                                                    <ul class="list-unstyled">
                                                        <li class="err_dismissal_{{ $key }}"></li>
                                                    </ul>
                                                </span>  
                                            </td>
                                            <td class="text-left"> 
                                                <button class="btn btn-danger remove" type="button"><span class="fas fa-trash"></span></button> 
                                            </td> 
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody> 
                                </table> 
                            </div> 
                            <button class="btn btn-md btn-primary" 
                            id="addBtn" type="button"> 
                           @lang('admin.TITLE_ADD_BUTTON')
                            </button> 
                        </div> 
                    </div><!-- /.card-body -->

                    
                    <div class="card-footer">
                        <!-- <button onclick ="validateSettingValue()" type="submit" class="btn btn-success" id="savebtn">@lang('admin.TITLE_SAVE_BUTTON')</button> -->
                        <button type="submit" class="btn btn-success" id="savebtn">@lang('admin.TITLE_SAVE_BUTTON')</button>
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
<script type="text/javascript" src="{{ url('assets/admin/js/settings/edit-dismissal.js') }}"></script>
@endsection