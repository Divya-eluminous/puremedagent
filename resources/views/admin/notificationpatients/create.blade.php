@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection

@section('content') 


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.9/css/bootstrap-select.css" />

<style> 
    .bootstrap-select:not([class*="col-"]):not([class*="form-control"]):not(.input-group-btn){
        width:100%;
    }
</style>

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

                <form id="frmPatients" role="form" data-toggle="validator" action="{{ route($modulePath.'store') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue">@lang('admin.TITTLE_PATIENT') <span class="required">*</span></label>
                                
                                    <select id="mySelect" multiple="multiple" class="form-control selectpicker " data-live-search="true" name="user_name[]">
                                        @if(isset($getPatients) && !empty($getPatients))
                                         @foreach($getPatients as $k=>$v)
                                          @php 
                                            $selected='';
                                            if($v['sendNotification']==1)
                                            $selected = "selected";
                                          @endphp
                                          <option value="{{ $v['id'] }}" {{ $selected }}>{{ $v['first_name'] }} {{ $v['family_name'] }}</option>
                                         @endforeach
                                        @endif
                                    </select>


                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_user_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>

                     

                    <div class="card-footer">
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
<script type="text/javascript">
    var familyNameText = "{{ __('admin.ERR_FAMILY_DOCTOR_NAME') }}";
</script>

<script type="text/javascript" src="{{ url('assets/admin/js/notificationpatients/create-edit.js') }}"></script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.9/js/bootstrap-select.min.js"></script>
<script>
$(document).ready(function () {
    $('select').selectpicker();
});
</script>
@endsection