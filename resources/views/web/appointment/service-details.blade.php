@extends('web.layout.master')
@section('title')
{{ $moduleAction ?? '' }}
@endsection
@section('content')

<div class="container">
  <div class="row">
    <div class="main_content book_data">
        <!-- jquery validation -->
          
        {{-- ❗ Show error if patientDetails is missing --}}
        @if(isset($error))
            <div class="alert alert-danger mt-4">
                {{ $error }}
            </div>
        @else

            <div class="card card-primary">   
                <div class="card-header">
                    <h3 class="card-title">{{$serviceDetails->name}}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- roshani added the below code for issue 174 -->

                        <div class="col-md-12">
                            {!! $serviceDetails->description !!}
                        </div>
                        <!-- roshani hidden the below code for issue 174 -->
                        <!-- <p>
                            {!! html_entity_decode($serviceDetails->description, ENT_QUOTES, 'UTF-8') !!} 
                        </p> -->
                    </div> 
                </div><!-- /.card-body -->
                <div class="card-footer">


                    <!-- <a href="{{ url('/online-appointments/') }}" class="btn btn-success" > Online Termin</a> -->

                    <a href="{{ url('/online-appointments',['enc_doctor_id'=>$null_parameter,'service_name' => $service_name]) }}" class="btn btn-success" > Online Termin</a>


                </div>
            </div>

        @endif
    

        </div>
    </div>
</div>    
@endsection
@section('scripts') 
<script type="text/javascript" src="{{ asset('assets/plugins/timepicker/bootstrap-timepicker.min.js') }}"></script>
<!-- <script type="text/javascript" src="{{ asset('assets/plugins/datepicker/bootstrap-datepicker.de.js') }}"></script> -->
<script type="text/javascript"
        src="http://www.ubalt.edu/lib/jquery-ui-1.8.5.custom/development-bundle/ui/i18n/jquery.ui.datepicker-de.js">
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/web/js/appointment.js') }}"></script>
@endsection