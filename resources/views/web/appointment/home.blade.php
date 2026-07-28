@extends('web.layout.master')

@section('title')
{{ $moduleAction ?? 'Home' }}
@endsection

@section('style') 

@endsection
@section('content')

<!--commented below line on 25-june-25--->
<!-- <link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css"> -->

<script src="{{ asset('assets/admin-lte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.options.js') }}"></script>
<script type="text/javascript">
  var msg = '{{$getmsg}}';
  if(msg!='')
  {
    toastr.success(msg);
  }
</script>
<div class="container">
  <div class="row">
    <div class="main_content">

      <!----------commented on 18-oct-24------------------->
     <!--  <h6>Flexibilität für Patientinnen</h6>
      <h2>Unser Terminangebot für Sie</h2>
      <h5>Liebe Patientinnen!</h5>
      <div class="para">
        <p>Wir sind sehr bemüht, Ihnen von Seiten des Terminangebots ein hohes Maß an Flexibilität und kurzfristiger Verfügbarkeit anzubieten.</p>
        <p>Bitte beachten Sie daher, dass montags Herr Dr. Horak zusätzlich Ihr behandelnder Arzt in der Ordination ist!</p>
        <p>Einen entsprechenden Hinweis finden Sie auch im Online-Terminkalender!</p>
      </div> -->
      <!-------------commented on 18-oct-24---------------->

       <!----------added on 18-oct-24------------------->
       @if(isset($webContent))
         {!! $webContent !!}
       @endif
        <!----------added on 18-oct-24------------------->
        
        <ul class="dr_list">
            @if(!empty($doctors) && sizeof($doctors)>0)
                @foreach($doctors as $doctor)
                    <li><span>Buchungslink {{ $doctor->first_name .' '. $doctor->last_name}}:</span> <a href="{{ url('/online-appointments/'.base64_encode(base64_encode($doctor->doctor_id))) }}" > Online Terminvereinbarung</a></li>   
                @endforeach
            @endif
                    <li><span>Schnellstmöglicher Termin:</span> <a href="{{ url('/online-appointments/') }}" > Online Terminvereinbarung</a></li>    

        </ul>
    </div>
  </div>
</div>
@endsection
@section('scripts') 

@endsection
