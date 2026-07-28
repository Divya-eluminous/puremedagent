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

                <form id="settingForm" role="form" data-toggle="validator" action="{{ route($modulePath.'updateFindings', [base64_encode(base64_encode($setting->id))]) }}">
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
                                            
                                            <th class="text-center" style="width:15%">@lang('admin.FINDINGS_TITLE')</th> 
                                            <th class="text-center" style="width:10%">@lang('admin.TITLE_SETTING_STATUS')</th>
                                             <th class="text-center" style="width:10%"></th>
                                            
                                        </tr> 

                                    </thead> 
                                <tbody id="tbody"> 
                                    <input type="hidden" name="total_count" value="{{ count($findings) }}" id="total_count"/>

                                @if(!empty($findings))
                                    @foreach($findings as $key=>$value)
                                        <tr id="R${++rowIdx}"> 
                                            
                                            
                                           
                                            <td class="row-index text-center form-group">    
                                                <input type="text" name="keywords[]" value="{{ $value->keyword }}" class="form-control" />
                                                <span class="help-block invalid-feedback with-errors">
                                                    <ul class="list-unstyled">
                                                        <li class="err_keywords_{{ $key }}"></li>
                                                    </ul>
                                                </span>  
                                            </td>
                                           
                                           <td class="row-index text-center form-group">  
                                             <select name="b_select[]" class="form-control">
                                                 <option value="W" @if(!empty($value->status) && $value->status=='W') selected='selected' @endif>@lang('admin.WHITELIST_STATUS')</option>
                                                 <option value="B" @if(!empty($value->status) && $value->status=='B') selected='selected' @endif>@lang('admin.BLACKLIST_STATUS')</option>
                                             </select>
                                            </td> 
                                            <td class="text-center"> 
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
@php
$WHITELIST = __('admin.WHITELIST_STATUS');
$BLACKLIST = __('admin.BLACKLIST_STATUS');
@endphp

@section('scripts')
<script type="text/javascript">
var status = `<option value="W">{{ $WHITELIST }}</option><option value="B">{{ $BLACKLIST }}</option>`;
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>

<script type="text/javascript" src="{{ url('assets/admin/js/settings/edit-findings.js') }}"></script>
@endsection