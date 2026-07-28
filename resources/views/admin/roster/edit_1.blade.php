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
                        <h3 class="card-title">Roster Information</h3>  
                        <button class="btn btn-light float-right" onclick="window.history.back()">Back</button>
                    </div>
                    <form id="rosterForm" role="form" data-toggle="validator" action="{{ route($modulePath.'.update', [base64_encode(base64_encode($roster->id))]) }}">
                        <input type="hidden" name="_method" value="PUT">

                        <div class="card-body">
                            <div class="row"> 
                                <div class="col-sm-6"> 
                                    <div class="form-group"> 
                                        <label>Doctor</label> 
                                        <select 
                                            name="user_id" 
                                            value="$roster->user_id" 
                                            required
                                            data-error="@lang('admin.ERR_USER_ID_REQUIRED')"
                                            class="form-control" 
                                            >
                                            <option value="">Select Doctor</option>
                                            @foreach($user as $users)
                                            <option value="{{  $users->id }}" {{ $users->id == $roster['user_id'] ? 'selected="selected"' : '' }}>{{ $users->first_name .' '. $users->last_name}}</option> 
                                            @endforeach
                                        </select> 
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_user_id"></li>
                                            </ul>
                                        </span>
                                    </div>
                                </div> 
                                <div class="col-sm-6"> 
                                    <div class="form-group">
                                        <label class="theme-blue">  
                                        Date <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            name="date" 
                                            value="{{ $roster->date }}"
                                            class="form-control"  
                                            id="date"
                                            required
                                            maxlength="250" 
                                            data-error="@lang('admin.ERR_DATE_REQUIRED')." 
                                        >
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_date"></li>
                                            </ul>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="row"> 
                                <div class="col-sm-6"> 
                                    <div class="bootstrap-timepicker">
                                        <div class="form-group">
                                            <label>Time From</label>
                                            <div class="input-group date" id="time_from" data-target-input="nearest">
                                                <input 
                                                    type="text" 
                                                    class="form-control datetimepicker-input" data-target="#time_from"
                                                    id="from_time_date" 
                                                    name="from_time"
                                                    required 
                                                    data-error="@lang('admin.ERR_TIME_FROM_REQUIRED')." 
                                                    >
                                                <div class="input-group-append" data-target="#time_from" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="far fa-clock"></i></div>
                                                </div>
                                                <span class="help-block invalid-feedback with-errors">
                                                    <ul class="list-unstyled">
                                                        <li class="err_from_time"></li>
                                                    </ul>
                                                </span>
                                              </div>
                                            <!-- /.input group -->
                                        </div>
                                        <!-- /.form group -->
                                    </div>  
                                </div>
                                <div class="col-sm-6">   
                                    <div class="bootstrap-timepicker">
                                        <div class="form-group">
                                            <label>Time To</label>
                                            <div class="input-group date" id="time_to" data-target-input="nearest">
                                                <input 
                                                    type="text" 
                                                    class="form-control datetimepicker-input" data-target="#time_to"
                                                    id="to_time_date" 
                                                    name="to_time"
                                                    required 
                                                    data-error="@lang('admin.ERR_TIME_TO_REQUIRED')." 
                                                    >
                                                <div class="input-group-append" data-target="#time_to" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="far fa-clock"></i></div>
                                                </div>
                                                <span class="help-block invalid-feedback with-errors">
                                                    <ul class="list-unstyled">
                                                        <li class="err_to_time"></li>
                                                    </ul>
                                                </span>
                                            </div>
                                            <!-- /.input group -->
                                        </div>
                                        <!-- /.form group -->
                                    </div>
                                </div>
                            </div>
                            <div class="row"> 
                                <div class="col-sm-6"> 
                                    <div class="form-group">
                                        <label class="theme-blue"> 
                                        Status <span class="required">*</span></label>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="status"
                                             name="status" value="1" @if(!empty($roster->status) && $roster->status==1) checked @endif
                                            >
                                            <label class="form-check-label" for="status">Active</label>
                                          </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">

                                </div>
                            </div>
                        </div><!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">Save</button>
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
<script type="text/javascript" src="{{ url('assets/admin/js/roster/create-edit.js') }}"></script>
<script type="text/javascript">
    var time_from = "{{ $roster->from_time }}";
    var time_to = "{{ $roster->to_time }}";
    $('#from_time_date').val(time_from);
    $('#to_time_date').val(time_to);

</script>
@endsection