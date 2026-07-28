<!DOCTYPE html>
<html>
<head>
  @php
    use Illuminate\Support\Str;
  @endphp
  <?php 
  // Header('Location: '.$_SERVER['PHP_SELF']);
  ?>
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
      <!-- <li class="nav-item d-none d-sm-inline-block">
        <a href="index3.html" class="nav-link">Home</a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link">Contact</a>
      </li> -->
    </ul>

    <!-- SEARCH FORM -->
    <!-- <form class="form-inline ml-3">
      <div class="input-group input-group-sm">
        <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
        <div class="input-group-append">
          <button class="btn btn-navbar" type="submit">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </div>
    </form> -->
  <div>
  <!--   @php echo "<b>Current database connection : </b>".config('database.default')."(".$databaseName = DB::connection()->getDatabaseName().")"; 
      @endphp  -->
    </div>
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      
       <!-- Header Navbar: style can be found in header.less 
        if(!empty($website ) && auth()->user()->roles[0]->id==1 && auth()->user()->roles[0]->name=='super-admin')
       -->
    <nav class="navbar navbar-static-top">
     
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          @php
          // TODO: Replace with Stancl tenancy logic
          // $website   = \Hyn\Tenancy\Facades\TenancyFacade::website();
          
          // Stancl Tenancy: Get current tenant
          $website = tenancy()->tenant;
          $cancelAppointment = Request::segment(1);
          
          // Check if user is authenticated and has super-admin role
          if(!empty($website) && Auth::check() && auth()->user()->roles[0]->id==1 && auth()->user()->roles[0]->name=='super-admin')
          {
          @endphp         
            <li class="nav-item dropdown">
               <div class=""> 
                @if(@$cancelAppointment != 'cancelAppointment')
                  <!-- <h3 class=""><a href="javascript:void(0)" onclick="getSpecilist(this)" class="btn btn-primary float-right" >{{ Illuminate\Support\Str::singular(__('admin.TITLE_SPECIALIST_TEXT')).' '.__('admin.TITLE_ADD_BUTTON') }}</a></h3> -->

                   <!------changed on 29-jan-26------------------>
                   <h3 class=""><a href="javascript:void(0)" onclick="getSpecilist(this)" class="btn btn-primary float-right" >{{ __('admin.TITLE_SPECIALIST_ADD') }}</a></h3>

                @endif
              </div>
            </li>
          @php
        }
        @endphp
          <li class="nav-item dropdown">
            
            <a class="nav-link" data-toggle="dropdown" href="#">
               <!--  <img src="{{ asset('assets/adminLte/dist/img/user2-160x160.jpg') }}" class="user-image" alt="User Image"> -->
              
              @if(Auth::check())
               <span>{{ ucwords(auth()->user()->first_name ?? "") }} {{ ucwords(auth()->user()->last_name?? "") }} </span><!-- <i class="fas fa-caret-down"></i> -->
              @endif
            </a>
            <div class="dropdown-menu dropdown-menu-right">
              <a href="#" id="changePasswordfrm" data-toggle="modal" data-target="#updateUserPassword" onclick="document.getElementById('updateUserPasswordForm').reset()" class="dropdown-item" ><i class="fas fa-lock mr-2"></i> @lang('admin.TITLE_CHANGE_PASSWORD_MODULE')</a>
              <a href="{{ url('admin/logout') }}" class="dropdown-item"><i class="fas fa-sign-out-alt mr-2"></i> @lang('admin.TITLE_BUTTON_LOGOUT')</a>
             </div>
          </li>

          <!-- Language Dropdown Menu -->
          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
              <i class="flag-icon @if(app()->getlocale()=='en') flag-icon-us @else flag-icon-de @endif"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right p-0" >
              <a href="javascript:void(0)" class="dropdown-item @if(app()->getlocale()=='en') active activeLanguage @endif" lang="@if(app()->getlocale()=='en') en @endif" onclick="updateLanguage('en')">
                <i class="flag-icon flag-icon-us mr-2"></i> English
              </a>
              <a href="javascript:void(0)" class="dropdown-item @if(app()->getlocale()=='de') active activeLanguage @endif" lang="@if(app()->getlocale()=='de') de @endif" onclick="updateLanguage('de')">
                <i class="flag-icon flag-icon-de mr-2"></i> German
              </a>
            </div>
          </li>
          <!-- Control Sidebar Toggle Button -->
          <!-- <li>
            <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
          </li> -->
        </ul>
      </div>
    </nav>
      
      
    </ul>
  </nav>
  <!-- /.navbar -->

@if(Auth::check())
   @include('admin.layout.partials.sidebar')
@endif

<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark"> {{ $moduleAction??''}}</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">@lang('admin.TITLE_SITE_HOME')</a></li>
              <li class="breadcrumb-item active">{{ $moduleAction??''}}</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
