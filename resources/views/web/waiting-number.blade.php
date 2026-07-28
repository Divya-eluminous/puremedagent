<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="web-path" content="{{ url('/') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Puregyn | @lang('admin.TITLE_WAITING_SCREEEN')</title>
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
            /*align-items: center;
            display: flex;
            justify-content: center;*/
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

        /*===========14-09-20=================*/
        .content {
            text-align: center;
            max-width: 100%;
            /*width: 700px;*/
            width: 100%;
        }
        .coming-soon-header img {
            width: 100%;
            max-width: 110px;
        }
        .col-md-12.coming-soon-header {
            /*background-color: #e86a5a;*/
            background-color: <?php echo config('menu_header_colors');?>!important;
        }
        .coming-soon-content{
            /*background-color: #f8e7dd;*/
            color: #23395a;
            height: calc(100vh - 51px);
            align-items: center;
            /*display: flex;*/
            /*flex-direction: column;*/
            justify-content: center;
            background-color: <?php echo config('screen_bg_color');?>!important;
        }
        .coming-soon-main {
            display: flex;
            justify-content: center;
        }
            .right_box {
        margin-left: 30px;
        }
        .main_box {
            padding-top: 50px;
        }
        .left_box {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .coming-soon-content h3{
                margin: 0;
                padding-top: 30px;
                font-weight: bold;
                font-size: 38px;
        }
        .coming-soon-content img{
                width: 100%;
                max-width: 300px;
        }
        .coming-soon-content h2{
            font-size: 42px;
            margin: 16px 0;
        }
        h3.btm-head {
            padding: 0 0 40px 0;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            background-color: transparent;
            border: 1px solid transparent;
            padding: .375rem .75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: .25rem;
            transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }
        .btn.btn-success {
            background-color: #bd6f66;
            border-color: #bd6f66;
        }
        /*===========14-09-20=================*/
        /*===23-Dec-22================*/
           button.refresh_page_btn {
            position: fixed;
            right: 30px;
            top: 10%;
            max-width: 30px;
            padding: 0;
            border: 0;
            background-color: white;
            padding: 5px;
            border-radius: 50px;
            display: flex;
            justify-content: center;
            cursor: pointer;
            align-items: center;
            border: 1px solid #eaeaea;
            transition: all 0.3s;
        }
        button.refresh_page_btn:hover {
            transform: rotate(180deg);
        }
        /*===23-Dec-22================*/
        #content_waiting{
            background-color: <?php echo config('screen_bg_color');?>!important;
        }
    </style>
    <script src="{{ asset('assets/admin-lte/plugins/jquery/jquery.min.js') }}"></script>

    <script src="{{ asset('assets/plugins/axios/axios.min.js') }}"></script>
    <script type="text/javascript">    
        const WEBURL = $('meta[name="web-path"]').attr('content');
        const CSRFTOKEN = document.querySelector("meta[name=csrf-token]").content
        axios.defaults.headers.common['X-CSRF-Token'] = CSRFTOKEN; 

        function load_content()
        {

            $("#content_waiting").css('display','block'); 
            $("#button_setion").css('display','none');
            setTimeout(function()
            {
                var action = WEBURL + '/get-waiting-record';
                axios.post(action)
                  .then(response => {

                    const resp = response.data;
                    var prev_q_number = resp.data.queue_number;
                    var curent_q_number = $("#q_number").html();
                    check_string = prev_q_number.localeCompare(curent_q_number);

                    console.log(prev_q_number);
                    console.log(curent_q_number);
                    console.log(check_string);
                    if(resp.data.html!='' && prev_q_number != curent_q_number && prev_q_number!=''  )
                    {                       
                        //$("#chatAudio")[0].play();//commented code on 21-nov-22
                        //***********Added below code on 21-nov-22*****//
                        var resp_audio = $("#chatAudio")[0].play();
                         if (resp_audio!== undefined || resp_audio!="") {
                            resp_audio.then(_ => {
                                $("#chatAudio")[0].play();
                                $("#starttocontinue").html(' ');
                                $("#start").hide();
                            }).catch(error => {
                                console.log('in error');
                              //alert('Click start button to continue');
                              $("#starttocontinue").html('Click start button to continue');
                              $("#start").show();
                           });
                         }
                       //***********Added below code on 21-nov-22*****//

                    }
                    $("#waitingDetails").html(resp.data.html);
                    $("#queue_img").attr("src", resp.data.queue_img);
                    
                    load_content();
                  })
                  .catch(error => {

                  })

            },6000);
        } 

        // load_content();  //commneted on 21-nov-22 

        // $(document).ready(function() {
        //    $("#click_me").click(function()
        //     {
        //         load_content();    
        //     });  
        // })    


        //Added below code on 21-nov-22
        $(document).ready(function() {
            load_content(); 
            $("#start").click(function(){
               console.log('in start function');
               load_content();   
            });
        });     



    </script>
</head>
<body >
    <iframe width="420" height="345" style='display:none' src="{{ $audio_sound }}" allow="autoplay"></iframe>
    <audio id="chatAudio" >        
        <source src="{{ $audio_sound }}" type="audio/mp3">
    </audio>
       
  
    <div class="flex-center position-ref full-height"> 
        <div class="content">
            <div class="container">
               
            <div class="row" id="content_waiting" style="text-align: center;">
                <div class="col-md-12 coming-soon-header">
                    <!-- <a class="brand" href="#"> -->
                        <!-- <img src="{{ asset('assets/admin/images/logo-p-w.png') }}"/> -->
                        @if(!empty(Config('ordination_id')))
                            @if(!empty(Config('ordination_logo')))
                              <img src="{{ url(Config('ordination_logo')) }}" class="img-fluid" style="width: 50%;">
                            @endif
                        @else
                            <img src="{{ url('assets/admin/images/logo-p.png') }}" class="img-fluid">
                        @endif
                    <!-- </a> -->
                </div>

                <!-------Start--Added below start msg on 21-nov-22------------>
                  <h3 align="center" style="color:red; background-color: black;font-size: 20px;width: 250px;margin-left: 629px;" id="starttocontinue"  ></h3> 
                <!------End---Added below start msg on 21-nov-22------------>


                <div class="coming-soon-content">
                    <button type="submit" class="refresh_page_btn" onClick="window.location.reload();"><img src="{{ asset('assets/admin/images/refresh.png') }}"/></button>
                    <div class="col-md-12">
                         <div class="main_box">
                            <h3>Willkommen bei</h3>
                            <br/>
                            @if(!empty(Config('ordination_id')))
                                @if(!empty(Config('ordination_logo')))
                                  <img src="{{ url(Config('ordination_logo')) }}" class="img-fluid" style="width: 50%;">
                                @endif
                            @else
                                <img src="{{ url('assets/admin/images/logo-p.png') }}" class="img-fluid">
                            @endif
                            <!-- <img src="{{ asset('assets/admin/images/logo-p.png') }}"/> -->
                        </div>
                    </div>
                    <div class="col-md-12 coming-soon-main  d-flex">
                       
                        <div class="left_box" id="waitingDetails">

                            @if(!empty($waiting_details->queue_number))
                                
                                <h3>Wir bitten Wartenummer</h3>
                                <h2 id="q_number">{{ $waiting_details->queue_number ?? '' }}</h2>
                                <h3 class="btm-head">zur Anmeldung zu kommen</h3>
                            @else
                                <h3>Bitte warten Sie bis Ihre</h3><br>
                                <h3 class="btm-head">Wartenummer aufgerufen wird</h3>
                            @endif
                        </div>
                        <div class="right_box">
                            <div class="img_box">
                                <img id="queue_img" src="{{ $queue_img  }}">
                            </div> 
                        </div>

                    </div>

                    <!-------Start--Added below start button on 21-nov-22------------>
                     <button type="button" id="start" class="btn btn-success" style="display: none">Click To Start</button>
                    <!------End---Added below start button on 21-nov-22------------>

                </div>
            </div>
                
                <div class="row" id="button_setion" style="text-align: center;display: none">
                    <div class="col-md-12 coming-soon-header">
                    <!-- <a class="brand" href="#"> -->
                        <img src="{{ asset('assets/admin/images/logo-p-w.png') }}"/>
                    <!-- </a> -->
                </div>
                 <div class="coming-soon-content">
                     <div class="col-md-12">
                         <div class="main_box">
                    <button type="button" id="click_me" class="btn btn-success">Touch To continue</button>
                </div>
            </div>
                </div>
                </div>
                <!--/end row-->
            </div>
        </div>
    </div>

     

<!-- BEGIN FOOTER -->
<!-- <div class="page-footer">
   <footer class="main-footer" style="text-align: center;">
   
    <strong>@lang('admin.TITLE_SITE_COPYRIGHT') &copy; 2019-{{ date("Y",time()) }} <a href="#">@lang('admin.TITLE_SITE_BEGIN')@lang('admin.TITLE_SITE_END')</a>.</strong> @lang('admin.TITLE_SITE_RIGHTS')
  </footer>
</div> -->

</body>

<!-- END BODY -->
</html>