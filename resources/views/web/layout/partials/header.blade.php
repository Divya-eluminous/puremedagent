<?php 
if(!empty(Config('ordination_id')))
{
?>
@include('web/layout/partials/ordination_css')
<?php 
} 
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="web-path" content="{{ url('/') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title')</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
  
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/dist/css/adminlte.min.css') }}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fontawesome-free/css/all.min.css') }}">
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  
  <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/css/web.css') }}">
   <link rel="stylesheet" href="{{ asset('assets/admin/css/mediaquery.css') }}">
   <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/sweetalert/sweetalert.css') }}">
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  @yield('style')
  <!-- Global site tag (gtag.js) - Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=UA-183928142-1"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'UA-183928142-1');
  </script>
</head>
<body>
  <div class="overlay"></div>


<?php 
$header_menu = '';

if(!empty(Config('ordination_id')))
{
  $header_menu = 'web-header-light';
}
else
{
  $header_menu = 'bg-light';
}
?>
<nav class="navbar navbar-expand-md navbar-light main-menu <?php echo $header_menu;?>" style="box-shadow:none">
  <div class="container">

    <button type="button" id="sidebarCollapse" class="btn btn-link d-block d-md-none">
                <i class="bx bx-menu icon-single"></i>
            </button>

    <!-- <a class="navbar-brand logo" href="<?php echo config('constants.PUREGYN_LINK');?>"> -->
    <?php 
    if(!empty(Config('ordination_id')))
    {
    ?>
        <a class="navbar-brand logo" href="<?php echo config('ordination_url');?>">
    <?php 
    }
    else{
    ?>
        <a class="navbar-brand logo" href="<?php echo config('constants.PUREGYN_LINK');?>">
    <?php 
    }
    ?> 
      @if(!empty(Config('ordination_id')))
    
        @if(!empty(Config('ordination_logo')))
          <img src="{{ url(Config('ordination_logo')) }}" class="img-fluid" style="width: 50%;">
        @endif
      @else
        <img src="{{ url('assets/admin/images/logo-p.png') }}" class="img-fluid">
      @endif
     
      
    </a>

    {{-- Delete Account (only shown when a patient is logged in) --}}
    @if(!empty(session('loginPatientData')))
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a href="javascript:void(0);" id="deleteAccountBtn" class="btn btn-danger">{{ __('front.DELETE_ACCOUNT', [], 'de') }}</a>
      </li>
    </ul>
    @endif

    <ul class="navbar-nav ml-auto d-block d-md-none">
      <li class="nav-item">
        <a class="btn btn-link" href="#"><i class="bx bxs-cart icon-single"></i> </a>
      </li>
    </ul>

    <div class="collapse navbar-collapse">
     <!--  <form class="form-inline search_box">
        <input class="form-control" type="search" placeholder="Suche" aria-label="Search">
        <button class="btn search_btn my-2 my-sm-0 search_img" type="submit">
          <img src="{{ asset('assets/admin/images/search.png') }}" class="" alt="search"/>
        </button>
      </form> -->

      <!-- <ul class="navbar-nav details_menu">
        <li class="icons call">
          <a href="tel:014842000">01-484 2000</a>
        </li>
        <li class="icons ot">
          <a href="{{ url('/online-appointments') }}">ONLINE TERMIN</a> 
        </li>
                   
      </ul> -->
      <!-- <div class="top_toolbar">
                <div class="right">
                    
                    <ul class="call_number">
                        <li class="emergency_no">
                            <a href="#" class="btn">AT</a>
                          </li>                        
                        <li>
                            <a href="#" class="btn">EN</a> 
                             </li>  
                             <li>
                            <a href="#" class="btn">B/H/S</a> 
                             </li>                       
                    </ul>
                </div>
        </div> -->
    </div>

  </div>
</nav>

{{-- Delete Account confirmation popup (only rendered when a patient is logged in) --}}
@if(!empty(session('loginPatientData')))
<div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body">
        <p class="mb-0">{{ __('front.DELETE_ACCOUNT_CONFIRM', [], 'de') }}</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('front.DELETE_ACCOUNT_CANCEL', [], 'de') }}</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteAccountBtn">{{ __('front.DELETE_ACCOUNT_OK', [], 'de') }}</button>
      </div>
    </div>
  </div>
</div>
@endif



<div class="search-bar d-block d-md-none">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <form class="form-inline mb-3 mx-auto">
          <input class="form-control" type="search" placeholder="Search for products..." aria-label="Search">
          <button class="btn btn-success" type="submit"><i class="bx bx-search"></i></button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Sidebar -->
<nav id="sidebar">
  <div class="sidebar-header">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-10 pl-0">
         
        </div>

        <div class="col-2 text-left">
          <button type="button" id="sidebarCollapseX" class="btn btn-link">
                            <i class="bx bx-x icon-single"></i>
                        </button>
        </div>
      </div>
    </div>
  </div>

 
  

</nav>

                