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
            <td> Lieber {{ $user->first_name }}, <br /><br /> Wir haben eine Anfrage zum Zurücksetzen des Passworts für Ihr mit dieser E-Mail-Adresse verknüpftes Konto erhalten. Wenn Sie diese Anfrage gestellt haben, befolgen Sie bitte die nachstehenden Anweisungen. <br />
                <br />
                Klicken Sie auf den folgenden Link, um Ihr Passwort über unseren sicheren Server zurückzusetzen:
                <br />
                <a href="{{ $user->url }}">Passwort zurücksetzen</a>
                <br /><br>
                Wenn das Klicken auf den obigen Link nicht funktioniert, können Sie den Link kopieren und in das Adressfenster Ihres Browsers einfügen oder dort erneut eingeben. Sobald Sie zu <a target="_blank" href="{{ url('/admin') }}">{{  url('/admin') }}</a>, zurückgekehrt sind, geben wir Anweisungen zum Zurücksetzen Ihres Passworts.<br /> <br /> Wenn Sie keine Anfrage gestellt haben, Ihr Passwort zurückzusetzen, können Sie diese E-Mail ignorieren. Seien Sie versichert, dass Ihr Konto sicher ist. <br /> <br /> Da wir die Sicherheit Ihrer persönlichen Daten ernst nehmen, werden wir Ihnen niemals eine E-Mail
senden und Sie bitten, Ihr Passwort oder Ihre Kreditkartennummer offenzulegen oder zu überprüfen. Wenn Sie eine verdächtige E-Mail erhalten, in der Sie aufgefordert werden, Ihre Kontoinformationen zu aktualisieren, löschen Sie die Email bitte.
            </td>
            <tr>
                <td style="padding-top:2%;">
                    <br>
                      Mit freundlichen Grüßen,<br>
                      Ihr PureGyn Team
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