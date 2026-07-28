<!DOCTYPE html>
<!-- 
Template Name: Metronic - Responsive Admin Dashboard Template build with Twitter Bootstrap 3.2.0
Version: 3.3.0
Author: KeenThemes
Website: http://www.keenthemes.com/
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Like: www.facebook.com/keenthemes
Purchase: http://themeforest.net/item/metronic-responsive-admin-dashboard-template/4021469?ref=keenthemes
License: You must have a valid license purchased only from themeforest(the above link) in order to legally use the theme for your project.
-->
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<!-- BEGIN HEAD -->
<head>
<meta charset="utf-8"/>
<title>Puregyn |  Privacy-Policy</title>

    <!-- Styles -->
    <style>
        html, body { 
            color: #000;
            font-family: 'Raleway', sans-serif;
            font-weight: 100;
            height: 100vh;
            margin: 0; 
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #000;
            padding: 0 25px;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            /*text-transform: uppercase;*/
        }

        .m-b-md {
            margin-bottom: 400px;
            opacity: 1!important;
        }

        .flex-center.position-ref.full-height {
            position: relative;
            width: 100%;
            z-index: 1;
        }

        .flex-center.position-ref.full-height::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /*background-image: url("public/uploads/back4.webp");*/
            background-color: #EBF4FA;
            background-size: auto;
            opacity: 0.5;
            z-index: -1;
        } 

        div#heading {
            font-weight: bold;
            color: #000;
            opacity: 1!important; 
        }

        .policy{
            color: #000 !important;
        }

        div#link a{
            color: #000 !important;
            font-weight: bold !important;
        }
        #footer {
        	color: #000 !important;
        }
        .privacy-policy-content {
            background: #EBF4FA;
            padding: 50px 50px;
        }
        .page-footer {
            text-align: center;
            padding: 20px;
            background: #E5EEF4;
        }
    </style>
<body>
    <div class="privacy-policy-content">
              

        <div class="content">
        	<div class="container">
                <div class="row">
                    <div class="col-md-12 coming-soon-header">
                        <!-- <a class="brand" href="#"> -->
                            <h3 style="color: #000; font-size: 50px; font-weight: bold;">@lang('admin.TITLE_SITE_BEGIN')@lang('admin.TITLE_SITE_END')</h3>
                        <!-- </a> -->
                    </div>
                </div>
				
				<div class="row">
					<div class="col-md-12 coming-soon-content">
						{!! $gdprData !!}
					</div>
				</div>
				<!--/end row-->
			</div>
        </div>
    </div>
<!-- BEGIN FOOTER -->
<div class="page-footer">
   <footer class="main-footer">
   
    <strong>@lang('admin.TITLE_SITE_COPYRIGHT') &copy; 2019-{{ date("Y",time()) }} <a href="#">@lang('admin.TITLE_SITE_BEGIN')@lang('admin.TITLE_SITE_END')</a>.</strong> @lang('admin.TITLE_SITE_RIGHTS')
  </footer>
</div>

</body>
<!-- END BODY -->
</html>