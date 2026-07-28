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
                                                Liebes Ordinations-Team,<br/> <br/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                              {{ $details['body'] }} <br/> 
                                               <br>
                                              @foreach ($details['appoinmant_date'] as $key => $dates)
                                                Datum der Untersuchung : {{$dates}} <br>
                                              @endforeach 
                                              <br>

                                              Note : {{$details['note']}}
                                            </td>
                                        </tr>
                                            <tr>
                                                <td style="padding-top:2%;">
                                                  <br>
                                                  Herzliche Grüße,<br>
                                                  das $details['patients_name']
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
