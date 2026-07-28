<html>
<head>
    <meta charset="utf-8">  
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
</head>

<body style="background:#fff; font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:25px;">
    <table border="0" cellspacing="0" cellpadding="0"
        style="background:#eaeaea; max-width:800px; width:100%; padding:0px 15px;" align="center">
        <tr>
            <td>
                <table border="0" cellspacing="0" cellpadding="0" align="center"
                style="margin-bottom: 35px;max-width:700px;width:95%">
                    <tr>
                        <td>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" 
                            style="padding: 40px 0px 20px;">
                                <tr>
                                    <td> <!-- <img src="" alt="logo" style="display:block; border:0;"> --></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff;"> 
                            <table width="100%" border="0" cellspacing="0" cellpadding="0"> 
                                <tr>
                                <td style="text-align: justify; padding: 5%">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td>
                                                Hallo Admin,<br/> <br/>                       
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                              {{ $content }} <br/>                        
                                            </td>
                                        </tr>
                                            <tr>
                                                <td style="padding-top:2%;">
                                                  <br>
                                                  Herzliche Grüße,<br>
                                                  das 
                                                  <?php 
                                                  if(!empty(config('ordination_name')))
                                                       echo ucfirst(config('ordination_name'));
                                                  else echo "Purmed";
                                                  ?>
                                                  -Team
                                                    <br>
                                                    <a target="_blank" href=""></a><br>
                                                    </td>  
                                              </tr>
                                        </tr>
                                    </table>
                                </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
