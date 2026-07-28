
@extends('web.layout.master')

@section('title')
    {{ $moduleTitle ?? 'Confirm web appointment' }}
@endsection
<head>
    <style type="text/css">
    .card+.bottom_footer {
            position: fixed;
            width: 100%;
            bottom: 0;
        }
        
</style>
</head>
@section('styles')


@endsection

@section('content')
<div class="card card-success" style="width: 706px; height: 294px; margin: 0 auto 50px auto; box-shadow: 0px 0px 10px rgba(0,0,0,0.2); border: none;">
    <div class="card-body login-card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
      <!-- <h3 class="center" style="font-weight: bold;">Ihr Termin wurde bereits bestätigt. Danke schön!</h3> -->
      @if($cancelAppoinmentStatus == 0)
            <!-- <h3 class="center" style="font-weight: bold;">Ihr Termin wurde bereits bestätigt. Danke schön!</h3> -->
            <span>Sehr geehrte*r {{$name}} </span></br>
            <span>Vielen Dank für Ihre Terminbuchung bei Puregyn. Wir freuen uns auf Ihren Besuch.</span></br>
            <span> {{$dateTime}} </span></br>
            <!-- <span> Sollten Sie zu dem Termin verhindert sein, können Sie ihn hier stornieren. </span></br> -->
            <span> Herzliche Grüße, das Puregyn-Team</span></br>

        @elseif($cancelAppoinmentStatus == 1)
            <h3 class="center" style="font-weight: bold;">Ihr Termin wurde bereits abgesagt.</h3>
        @endif
    </div>
</div>
@endsection

@section('scripts')
@endsection