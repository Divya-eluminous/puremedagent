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
            <td> Sehr geehrte*r {{$user['patient_name']}}, <br /><br />

                <span>Vielen Dank für Ihre Terminbuchung bei {{ $ordination }} für</span><br>
                <span>{{$user['datetime']}}</span><br><br>
               <!--  <span>Bitte bestätigen sie noch diesen Link, um die Buchung abzuschließen:</span><br>
                <span><a href="{{$user['Confirm_url']}}">Termin bestätigen</a></span><br> -->
                <span>Sollten Sie zu dem Termin verhindert sein, können Sie ihn </span><br>
                <span><a href="{{$user['Cancel_url']}}">hier stornieren</a></span><br><br>
                <span>Herzliche Grüße,</span><br>
                <!-- <span>das Puregyn-Team</span><br> -->
                <span>das {{ $ordination }} Team</span><br>
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