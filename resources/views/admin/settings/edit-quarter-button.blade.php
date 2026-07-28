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
                    <!-- Roshani Added this back button 325 - d - 9 april-2025 -->

                </div>


              
                 <form id="OptimalDateForm" role="form" data-toggle="validator" action="{{ url('/')}}/admin/settings/updateOptimal/{{ base64_encode(base64_encode($setting->id)) }}" method="POST">
                    @csrf
                  <!--   <input type="hidden" name="_method" value="PUT"> -->
                    <div class="card-body">                       
                        <div class="row">
                          
                            <div class="form-group">
                             <label class="theme-blue"> 
                                    @lang('admin.TITLE_OPTIMAL_APPOINTMENT') <span class="required">*</span>
                             </label>
                             <div class="list-group-item d-flex">                                 
                                <label class="theme-blue col-4">@lang('admin.TITLE_OFF') </label>

                                <div class="label-anchor">
                                    <label class="switch ml-auto">
                                        <input type="checkbox" class="checkbox-optimal-date"
                                            id="checkbox-optimal-date" name="setting_value" @if(isset($setting->setting_value) && $setting->setting_value==0)
                                            value="0" @elseif(isset($setting->setting_value) && $setting->setting_value==1) value="1" checked @endif onchange="return getsettingvalue()">
                                        <span class="knob"></span>
                                    </label>                                 
                                </div>
                                <label class="theme-blue col-4">@lang('admin.TITLE_ON') </label>
                            </div>
                           </div>
                        </div>
                        
                        <!-- <div class="row">
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
<script type="text/javascript" src="{{ url('assets/admin/js/settings/optimal-date-create-edit.js') }}"></script>
<!-- <script>
    var editor = CKEDITOR.replace( 'setting_value' );
</script> -->
<script>
    function getsettingvalue()
    {
        var val = $("#checkbox-optimal-date").val();
       if(val==0)
       {
        $("#checkbox-optimal-date").val(1);
       }else if(val==1){
        $("#checkbox-optimal-date").val(0);
       }
    }
</script>
@endsection