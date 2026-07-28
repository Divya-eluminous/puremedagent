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
                    <!-- Roshani Added this back button 325 - c - 9 april-2025 -->

                </div>

              
                 <form id="BookTimeframesettingForm" role="form" data-toggle="validator" action="{{ url('/')}}/admin/settings/updateBookingTimeframe/{{ base64_encode(base64_encode($setting->id)) }}" method="POST">
                    @csrf
                  <!--   <input type="hidden" name="_method" value="PUT"> -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_SETTING_KEY') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="setting_key" 
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
                                    @lang('admin.TITLE_SELECT_TIMEFRAME') <span class="required">*</span></label>
                                  
                                   
                                    <select name="description" class="form-control" required data-error="@lang('admin.ERR_SELECT_TIMEFRAME')">
                                        <option value="">Select</option>
                                        <option value="week" @if(isset($setting->description) && $setting->description=="week") selected @endif>Week</option>
                                        <!-- <option value="month"  @if(isset($setting->description) && $setting->description=="month") selected @endif>Month</option> -->
                                    </select>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_description"></li> 
                                        </ul>
                                    </span> 
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_ADD_TIMEFRAME') <span class="required">*</span></label>
                                    <input 
                                        type="number" 
                                        name="setting_value" 
                                        value="{{ $setting->setting_value }}" 
                                        class="form-control" 
                                        required  min="1" max="12"
                                        data-error="@lang('admin.ERR_ADD_TIMEFRAME')" 
                                    >                                    
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_setting_value"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                       <!--  <div class="row">
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
                        </div>  -->
                    
                    </div><!-- /.card-body -->

                    <div class="card-footer">
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
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/settings/booking-timeframe-create-edit.js') }}"></script>
<!-- <script>
    var editor = CKEDITOR.replace( 'setting_value' );
</script> -->
@endsection