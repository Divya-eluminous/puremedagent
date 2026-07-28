<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOQ Notification</title>
</head>

<body style="background:#fff; font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:25px;">
    <table border="0" cellspacing="0" cellpadding="0" style="background:#bbbbbb42; max-width:800px; width:100%;padding:0px 15px;border: 1px solid #00000052;" align="center">
        <tr>
            <td>
            <table align="center" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 35px; max-width:700px; width:100%">
                <tr>
                    <td>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:40px 0 20px;">
                            <tr>
                                <!-- <td> <img src=" alt="logo" style="display:block; border:0;"></td> -->
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #00000052; padding: 35px;" bgcolor="#ffffff" align="center">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:700px;">
                    <tr>
                    <td style=" background-image:url({{  asset('assets/web/images/header-bg.png') }}; min-height: 185px; color: #ffffff; text-align: center; font-size: 26px; line-height: 38px; background-size: 100% auto; background-repeat: no-repeat; padding:7% 0; background-color:#667ab8;">                                        
                    </td>
                </tr>
                <tr>
                <td>
                    <table align="center" width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="margin-bottom: 15px; display: inline-block;">Hello Admin,<br /> Please find raw material details whose quantity is less than moq limit as below:
                            </td>
                        </tr>
                        <tr>
                    @if(!empty($data))
                    @foreach($data as $companyName=>$company)
                    @if(!empty($company))
                    <td><strong style="margin-top:5px;margin-bottom: 10px; display: inline-block;">Company Name: {{ $companyName ?? "" }}</strong></td>
                    <table style="width:100%" border="1" cellspacing="0" cellpadding="0" style="padding:40px 0 20px;" align="center">
                        <tr>
                            <th style="padding:5px">Raw Material</th>
                            <th style="padding:5px">Material Type</th>
                            <th style="padding:5px">Balance</th>
                            <th style="padding:5px">Moq</th>
                        </tr>
                        @foreach($company as $raw_material)
                        <tr>
                          <td style="padding:5px">{{ $raw_material->name ?? "" }} </td>
                          <td style="padding:5px">{{ $raw_material->material_type ?? "" }} </td>
                          <td style="padding:5px">{{ number_format($raw_material->total_balance,2) }} </td>
                          <td style="padding:5px">{{ number_format($raw_material->moq,2) }} </td>
                        </tr>
                        @endforeach
                        </table>
                        </tr>
                        <tr>
                        @endif
                   @endforeach
                  @endif
                    <td style="padding-top:5%;">
                        <p style="margin:0; color:#000000; text-align:justify;">Thank you for choosing us. We sincerely appreciate your business.
                        </p>
                        <br>
                        Regards,<br>
                        Store Team
                        <br>
                    </td>
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