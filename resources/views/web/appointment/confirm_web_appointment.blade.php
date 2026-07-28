@extends('web.layout.master')
 
@section('title')
    {{ $moduleTitle ?? 'Confirm web appointment' }}
@endsection
<style type="text/css">
	 .card+.bottom_footer {
            position: fixed;
            width: 100%;
            bottom: 0;
        }
</style>
@section('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert/sweetalert.css') }}">
@endsection
 
@section('content')
<div class="card card-primary" style="width: 520px; height: 147px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0px 0px 10px rgba(0,0,0,0.2); border: none;">
<div class="card-body login-card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
<p class="login-box-msg" style="font-size: 25px;">@lang('admin.PLEASE_WAIT')</p>
<div class="d-flex justify-content-center">
<button type="button" style="display: none;" class="btn btn-success mr-2" onclick="confirmedAppointment('yes', {{$app_id}})">Termin bestätigen</button>
 
        <!-- <button type="button" class="btn btn-danger" onclick="confirmedAppointment('no', {{$app_id}})">Nicht bestätigen</button> -->
</div>
</div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('assets/plugins/sweetalert/sweetalert.js') }}"></script>
 
    <script type="text/javascript" src="{{ asset('assets/web/js/appointment.js') }}"></script>
 
    <script>
    // Automatically trigger the confirmedAppointment function when the page loads
    $(document).ready(function()
{
        confirmedAppointment('yes', {{$app_id}});
});
</script>
@endsection