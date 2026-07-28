<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@isset($data[0]){{ $data[0]['check_list_name'] }} @endisset</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
/*            margin:0.9;padding:0.9; // you can set margin and padding 0 */
            margin-left: 20px;
            margin-right: 20px;
            margin-bottom: 40px;
          } 
        .container {
            font-family: 'arial';
            bottom: 10px; 
           /* left: 0px; 
            right: 0px;*/
            width: 100%;
        }
        .header {
            position: fixed;
            top: -20px;
            left: 0px;
            right: 0px;
            height: 50px;
        }
        .header img, {
            width: 100%;
            height: 100px;
        }
        footer:last-child {
            position: fixed;
            left: 0cm; 
            right: 0cm;
            height: 2cm;
        }
        footer {
            position: fixed; 
            bottom: 0cm; 
            left: 0cm; 
            right: 0cm;
            height: 2cm;
        }
        .content {
            padding: 20px;
        }
        .introduction-text {
            word-wrap: break-word;
        }
        .patient-info {
            margin-bottom: 20px;
        }
        .question-section {
            margin-bottom: 30px;
            padding: 20px;
        }
        .question-section h3 {
            margin: 0 0 15px;

        }
        .question {
            margin-bottom: 10px;
        }
        footer {
            position: fixed; 
            bottom: 0cm;                
            right: 0cm;
            text-align: right;
        }
        .question-text{
        	font-size: 15px;
            word-wrap: break-word;
        }
        .details{
            margin-left: 20px;
        }
        footer:first-child {
/*         background-color: yellow;*/
            padding-right:50px;
            bottom: -60px;
            vertical-align: bottom;
        }
        .heading-text{
            font-size: 20px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        @isset($data[0])
        <?php

        foreach ($data as $key => $value) {
        	// dump($value);
            if(isset($value['header_image']) && !empty($value['header_image'])) {
        ?>
        <div class="header">
            <img src="<?php echo $value['header_image_path'] ?>" /> 
            <!-- <img src="https://puregyn.puremed.biz/storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/specialist_document/20220929134311-footer-Header%20(1).png"> -->
        </div>
        <br><br>
        <?php
            }
        }
        ?>
        <?php
		foreach ($data as $key => $value) 
	    {
			?>
			<footer>
	          <!-- @if(isset($value['currentDate']) && !empty($value['currentDate']))
	            <div align="right"> {{ $value['currentDate'] }} </div>
	          @endif -->
	          <div align="right"> {{ date('d-m-Y') }} </div>
	      </footer> 

			<?php
		}
		?>
        <!--  -->
        <?php
    foreach ($data as $key => $value1) 
    {
        ?>
        <br/><br/>
        <div style="width: 100%;" align="left" class="details">
          @if(isset($value1['patientFullName']) && !empty($value1['patientFullName']))
            <b>Patientenname</b> :  {{ $value1['patientFullName'] }}
          @endif
        </div> 

        <div style="width: 100%;" align="left" class="details">
          @if(isset($value1['patientDob']) && !empty($value1['patientDob']))
            <b>Geburtsdatum</b> :  {{ $value1['patientDob'] }}
          @endif
        </div>
        

        <?php
    }
    ?>
        <?php
        foreach ($data as $key => $value) {
            $introduction_text = isset($value['introduction_text']) ? $value['introduction_text'] : "";
            $check_list_name = isset($value['check_list_name']) ? $value['check_list_name'] : "";
        ?>
        <div class="content">
            <h3>{!! $check_list_name !!}</h3>
            <div class="introduction-text">{!! $introduction_text !!}</div>
        </div>
        <?php
            $final_name = isset($value['final_name']) ? $value['final_name'] : "";
            if(!empty($value['heading']) && sizeof($value['heading']) > 0) {
                foreach ($value['heading'] as $keyheading => $heading) {
        ?>
            <table width="100%" style="margin-bottom: 30px; padding: 20px">
                <tr>
                    <td colspan="2">
                        <p class="heading-text"><b>{{$heading['heading']}}</b></p>
                    </td>
                </tr>
                <?php 
                    foreach($heading['question'] as $keyq=>$question){
                        foreach ($question as $keyquu => $ques) {
                ?>
                <tr>
                    <td class="question-text">
                        {{$ques['question']}}
                    </td>
                    <?php 
                    if($ques['flag'] == 1){
                    ?>
                    <td width="20px" valign="top">
                        <img src="{{ asset('assets/admin/images/checkbox-tick.png') }}" width="15px" />
                    </td>
                    <?php 
                    }
                    else 
                    {
                    ?>
                    <td width="20px" valign="top">
                        <img src="{{ asset('assets/admin/images/uncheck-box-icon.jpg') }}" width="15px" />
                    </td>
                    <?php 
                    }
                    ?>
                </tr>
                <?php }
                    }
                ?>
            </table>
        <?php
                }
            }
        ?>
        <div class="content">
            <div class="introduction-text">{!! $final_name !!}</div>
            <p>Ihr pureGyn Team</p>
        </div>
        <?php
        }
        ?>
        <?php
        $sign = 0;
        foreach ($data as $key => $value2) {
        if(isset($value2['signature']) && $value2['signature'] != '' && $sign == 0) {
        ?>
        <div class="footer">
            <p style="text-align: right;"><strong>Unterschrift</strong></p>
            <p style="text-align: right;"><img src="{{$value2['signature']}}" style="width: 400px; height: 150px;"></p>
        </div>
        <?php
            }
            $sign++;
        }
        ?>
        <?php
        if(isset($data) && !empty($data)) {
            foreach ($data as $key => $value) {
                if(isset($value['footer_image']) && !empty($value['footer_image'])) {
        ?>
        <footer>
            <img  width="100%" height="100%" src="<?php echo $value['footer_image_path'] ?>" > 
            <!-- <img style="width: 100%;height:auto;" src="<?php //echo $value['footer_image_path'] ?>" > -->
            <!-- <img width="100%" height="100%" src="https://puregyn.puremed.biz/storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/specialist_document/20220929134311-footer-Footer%20(1).png" > -->
        </footer>
        <?php
                }
            }
        }
        ?>
        @endisset
    </div>

     
</body>
</html>
