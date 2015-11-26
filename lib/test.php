<?php 
	require("/usr/local/www/owncloud/3rdparty/phpmailer/phpmailer/class.phpmailer.php"); 
	$mail = new PHPMailer(); 
	$body = "</pre>
<div>";
$body .= " Hello Dimitrios
";
$body .= "<i>Your</i> personal photograph to this message.
";
$body .= "Sincerely,
";
$body .= "phpmailer test message ";
$body .= "</div>" ;
 
// And the absolute required configurations for sending HTML with attachement
$mail->IsSMTP(); 
$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
$mail->SMTPAuth = true; // authentication enabled
$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
$mail->Host = "smtp.test.data.deic.dk";
$mail->Port = 465; // or 587
$mail->From  = "from@example.com"; 
$mail->FromName = "Your Name";
$mail->AddAddress("s141277@student.dtu.dk");
$mail->Subject = "test for phpmailer-3";
$mail->MsgHTML($body);
$mail->AddAttachment("phpmailer.gif");
if(!$mail->Send()) {
echo "There was an error sending the message";
exit;
}
echo "Message was sent successfully";
 
?>

