<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Registration</title>
</head>

<body style="background:#fff; font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:25px;">
<table border="0" cellspacing="0" cellpadding="0" style="background:#eaeaea; max-width:800px; width:100%;padding:0px 15px;" align="center">
  <tr>
    <td><table align="left" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 35px; max-width:700px; width:100%">
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:40px 0 20px;">
        <tr>
         <td>{{ config('constants.SITENAME') }} <!-- <img src="{{ $user->logo }}" alt="logo" style="display:block; border:0;"> --></td>
          <!-- <td><img src="https://www.trtcle.com/user_assets/images/logo.png" alt="" style="display:block; border:0;"></td> -->
          <!-- <td style="text-align: right; font-weight: bold; color: #666666; font-size: 120%;"><a style="text-decoration:none;color:#666;" href="tel:+18006726253">+1-800-672-6253</a></td> -->
        </tr>
      </table></td>
  </tr>
  <tr>
    <td bgcolor="#ffffff" align="left">
      <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:700px;">
        <tr>
          <td style=" background-image:url({{  asset('assets/web/images/header-bg.png') }}); min-height: 185px; "></td>
        </tr>
         <tr>
          <td style=""><h2 style="text-align: center; font-size:18px;text-transform: uppercase;">Welcome to {{ config('constants.SITENAME') }}</h2></td>
        </tr><tr>
          <td><table align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:0 5%;">
           
              <tr>
                <td align="left" style="">We are glad to inform you that you have successfully registered with {{ config('constants.SITENAME') }}. Please find here your login detail.
                </td>
              </tr>
              <tr>
                <td align="left" style="">&nbsp;</td>
              </tr>
              <tr>
                <td align="left" style="">

                  <strong>Username:</strong> <span style='text-decoration: underline;'>{{  $user->email??"" }}</span></br></br>
                  <strong>Password:</strong>  <span>{{  $user->str_password??"" }}</span></br></br>
                   <strong>Link:</strong> <span> <a target="_blank" href="{{  $user->login_url }}">Login</a></span></br>
                </td>
              </tr>
             
              <tr>
                <td style="padding-top:5%;">
                  <p style="margin:0; color:#000000; text-align:justify;">If you have any questions about our services, please don’t hesitate to contact our customer service team. You can be reached by email at <strong><a style="text-decoration:none; color:#000;" href="mailto:{{$user->adminmail}}" target="_top"> {{  $user->adminmail??"" }} </a></strong>
                  </p></td>
              </tr>
              <tr>
                <td style="padding-top:5%;">
                  <p style="margin:0; color:#000000; text-align:justify;">Thank you for choosing us. We sincerely appreciate your business.
                  </p><br>
                  Regards,<br>

           {{ config('constants.SITENAME') }} <br>
         <!--  <a target="_blank" href="{{  $user->company_url }}">{{  $user->company_url }}</a> --><br>
              </td>
              </tr>
              <tr>
                <td style="padding-top:5%;"></td>
              </tr>  
            </table></td>
        </tr>
      </table></td>
  </tr>
  
</table>
</td>
</tr>
</table>
</body>
</html>