@extends('admin.layout.master')

@section('title', 'Page Not Found')

@section('content')
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            <div class="box-header with-border">
               <h1>403</h1>
                <p>{{ $exception->getMessage()??'' }} </p>
            </div>
         </div>
     </div>
</section>

@endsection
