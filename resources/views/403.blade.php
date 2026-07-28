@extends('web.master')

@section('title', 'Page Not Found')

@section('content')
<!-- All Content Section Start -->
<div class="main-wrapper">
    
    <!-- Banner Section Start -->
    <section id="about" class="not-fonud about-bg banner blue-overlay d-flex align-items-center justify-content-center">
        <div class="container text-center text-white">
            <div class="content">
                <h1>403</h1>
                <p>This page does not exist. </p>
            </div>
        </div>
    </section>

    <!-- About Trtcle -->
    <section class="about-trtcle not-found">
        <div class="container">
            <div class="f-row justify-content-center">
                <div class="f-col-sm-11 f-col-md-10 f-col-lg-8">                            
                    <h2>Page Not Found</h2>
                    <p>Sorry, we can’t seem to find the page you’re looking for. <br/> Please try these links instead:</p>
                    <div class="menu-list-wrap">
                        <p><strong><a href="#">Homepage</a></strong></p>
                        <p><strong><a href="#">CLE Bundles</a></strong></p>
                        <p><strong><a href="#">Unlimited CLE</a></strong></p>
                        <p><strong><a href="#">Frequently Asked Questions</a></strong></p>
                    </div>
                    <p>Need assistance? <strong><a href="contact.php">Contact us</a></strong> and a customer representative will be in touch!</p>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
