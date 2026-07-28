@if(isset($moduleTitle) && $moduleTitle == 'Doctor Dashboard')
	@include('admin.layout.partials.doctor_header')
@else
	@include('admin.layout.partials.header')
@endif
	@yield('content')
@include('admin.layout.partials.footer')