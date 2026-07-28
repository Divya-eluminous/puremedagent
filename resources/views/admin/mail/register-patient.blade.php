<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Registration</title>
</head>

<body style="background:#fff; font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:25px;">
<table border="0" cellspacing="0" cellpadding="0" style="background:#eaeaea; max-width:800px; width:100%;padding:0px 15px;" align="center">
  <tr>
    <td><table align="left" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 35px; max-width:700px; width:100%">
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:40px 0 20px;">
        <tr>
         <td>{{ config('constants.SITENAME') }} <!-- <img src="" alt="logo" style="display:block; border:0;"> --></td>
          <!-- <td style="text-align: right; font-weight: bold; color: #666666; font-size: 120%;"><a style="text-decoration:none;color:#666;" href="tel:+18006726253">+1-800-672-6253</a></td> -->
        </tr>
      </table></td>
  </tr>
  <tr>
    <td bgcolor="#ffffff" align="left">
      <table width="100%" border="0" cellspacing="0" cellpadding="10" style="max-width:700px;">
        
         <tr>
          <td style=""><h3 style="">Hello Admin,</h3></td>
        </tr><tr>
          <td><table align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:0 5%;">
           
              <tr>
                <td align="left" style="">New patient has been registered,<br/>Please find the below details:
                </td>
              </tr>
              <tr>
                <td align="left" style="">&nbsp;</td>
              </tr>
              <tr>
                <td align="left" style="">

                  <strong>Name:</strong> <span style='text-decoration: underline;'>{{  $user->first_name??"" }} {{  $user->last_name??"" }}</span>
                  
                </td>
              </tr>
              <tr>
                <td align="left" style="">

                 <strong>Email:</strong>  <span>{{  $user->email??"" }}</span>
                  
                </td>
              </tr>
              <tr>
                <td align="left" style="">

                 <strong>Mobile Number:</strong>  <span>{{  $user->mobile_no??"" }}</span>
                  
                </td>
              </tr>
              <tr>
                <td align="left" style="">

                 <strong>Age:</strong>  <span>{{  $user->age??"" }}</span>
                  
                </td>
              </tr>

              <tr>
                <td style="padding-top:5%;"></td>
              </tr>  
            </table></td>
        </tr>
        <tr>
                <td style="">
                  <!-- <p style="margin:0; color:#000000; text-align:justify;">Thank you for choosing us. We sincerely appreciate your business.
                  </p> -->
                  Regards,<br>

           {{ config('constants.SITENAME') }} <br>
         <!--  <a target="_blank" href=""></a> --><br>
              </td>
              </tr>
      </table></td>
  </tr>
  
</table>
</td>
</tr>
</table>
</body>
</html>