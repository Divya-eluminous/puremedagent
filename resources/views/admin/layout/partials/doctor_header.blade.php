<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>@yield('title') | @lang('admin.TITLE_SITE_BEGIN')@lang('admin.TITLE_SITE_END')</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <meta name="admin-path" content="{{ url('/admin') }}">
  <meta name="base-path" content="{{ url('/') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}">

  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fontawesome-free/css/all.min.css') }}">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/select2/css/select2.min.css') }}">
   <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
  <!-- iCheck -->
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
  <!-- JQVMap -->
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/jqvmap/jqvmap.min.css') }}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/dist/css/adminlte.min.css') }}">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
   <!-- flag-icon-css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.3.0/css/flag-icon.min.css">
  <!-- Daterange picker -->
<!--   <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/daterangepicker/daterangepicker.css') }}"> -->
  <!-- summernote -->
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/summernote/summernote-bs4.css') }}"> 

  <!-- bootstrap wysihtml5 - text editor -->
  <link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/datatables-bs4/css/dataTables.bootstrap4.css') }}">

  <!-- Date Picker -->
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ url('assets/plugins/sweetalert/sweetalert.css') }}">
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  
  @yield('style')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">


 <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>     
    </ul>
    
   


    
    <div class="container">
    <div class="row w-100 d-flex justify-content-between">
      <div class="col-6 col-sm-8">
         @if (Auth::user()->hasRole('super-admin'))
        <div class="form-group mb-0">

          <select 
          name="doctor_id" 
          id="doctor_id"  

          class="form-control select2 select_100" 
          >
          <option value="">@lang('admin.TITLE_SELECT_DOCTOR')</option>
          @foreach($user as $users)
          <option value="{{ $users->id }}" lang="{{ $users->status }}">{{ $users->first_name .' '. $users->last_name}}</option>
          @endforeach
          </select> 
        </div>
        @endif
      </div>
      <div class="col-6 col-sm-4">
       @php
       $url = url('admin/doctor-dashboard');
       @endphp
       <a href="javascript:void(0)" class="btn btn-primary float-right" onclick="openMe()">
       @lang('admin.TITLE_NEW_WINDOW')</a>
       <script>
        function openMe()
        {
          var width = `{{ $width }}`;
          var height = `{{ $height }}`;
          top_position = 0;
          left_position = 0;
        
          switch(`{{ $position }}`)
          {   
              case 'top_right':                      
                         left_position = screen.width;
              break;

              case 'bottom_left':
                         top_position = screen.height;
              break;

              case 'bottom_right':
                         top_position = screen.height;
                         left_position = screen.width;
              break;

              case 'center':          
                         left_position = (screen.width /2) - (width/2);
                         top_position = (screen.height /2) - (height/2);
              break;
          }

          window.open('{{ $url }}', '_blank', 'menubar=no,status=no,titlebar=yes,resizable=yes,scrollbars=yes,toolbar=no,width={{ $width }},height={{ $width }},top='+top_position+',left='+left_position); 
         }
       </script>
      </div>
    </div>
  </div>
  </div>
    
           
  </nav>
  <!-- /.navbar -->
@if(Auth::check())
   @include('admin.layout.partials.sidebar')
@endif

<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
    
    </div>
    <!-- /.content-header -->

