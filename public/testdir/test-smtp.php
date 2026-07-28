<?php

require_once "Mail.php";

$host = "ssl://mail6.viennacix.com";
$username = "app@puregyn.at";
$password = "WULpmt3@LJdqc";
$port = "465";
$email_to = "hp@pawaq.at";
$email_from = "app@puregyn.at";
$email_subject = "Test-Mail" ;
$email_body = "This is a test mail." ;

$headers = [
    'From' => $email_from,
    'To' => $email_to,
    'Subject' => $email_subject
    ];

$smtp = Mail::factory('smtp', [
    'host' => $host,
    'port' => $port,
    'auth' => true,
    'username' => $username,
    'password' => $password]);

die('Disabled for security reasons.');

$mail = $smtp->send($email_to, $headers, $email_body);

if (PEAR::isError($mail)) {
    echo '<p>' . $mail->getMessage() . '</p>';
} else {
    echo '<p>Message successfully sent!</p>';
}
