<html>
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
</head>
<body style="background:#fff; font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:25px;">
    <table border="0" cellspacing="0" cellpadding="0" style="background:#eaeaea; max-width:800px; width:100%; padding:0 15px;" align="center">
        <tr>
            <td style="padding:40px 0;">
                <table border="0" cellspacing="0" cellpadding="0" align="center" style="max-width:700px; width:95%; background:#fff;">
                    <tr>
                        <td style="padding:5%;">
                            @if (!empty($details['patientName']))
                                {{ $details['patientName'] }},<br><br>
                            @endif
                            @if ($german)
                                <span>Bitte verwenden Sie folgenden Code, um die Buchung zu bestätigen.</span><br>
                            @else
                                <span>Please use the following code to confirm your booking.</span><br>
                            @endif
                            <strong style="font-size:20px; letter-spacing:2px;">{{ $details['otp_code'] }}</strong><br><br>
                            @if ($german)
                                <span>Herzliche Grüße,</span><br>
                                <span>das {{ $ordination }} Team</span>
                            @else
                                <span>Kind regards,</span><br>
                                <span>the {{ $ordination }} team</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
