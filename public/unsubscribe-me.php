<?php
// url=https://puremed-url-here/delete/mobile=
$mobiNumber = "";
// Check if the parameter exists and is not empty before using it
if (isset($_REQUEST['mobile']) && !empty($_REQUEST['mobile'])) {
    $mobiNumber = $_REQUEST['mobile'];
    $to = "ph@lucymarx.at"; //"jyoti_eluminous@yopmail.com";
    $subject = "Request received to delete the account.";
    $message = "Hello Admin, 
A new request has been received for deleting the user from the database. 
Here are the details for it.
Mobile No. - " . $mobiNumber;
    $headers = "From: eluminous.se68@gmail.com";
    $showMessage = "";
    // Attempt to send the email
    if (mail($to, $subject, $message, $headers)) {
        $showMessage = "Account delete request received successfully.";
    } else {
        $showMessage = "Account delete request received not sent.";
    }
    echo $showMessage;
} else {
    echo "Please add the mobile number to unsubscribe.";
}
