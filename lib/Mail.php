<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once(__DIR__ . '/PHPMailer/src/Exception.php');
require_once(__DIR__ . '/PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/PHPMailer/src/SMTP.php');

function sendEmail($mailTo, $subject, $content)
{
		$mail = new PHPMailer(true);
		$mail->IsSMTP();
		// $mail->SMTPDebug = SMTP::DEBUG_SERVER;
		$mail->SMTPDebug = 0;
		$mail->SMTPAuth = TRUE;
		$mail->SMTPSecure = "tls";
		$mail->Port     = 587;  
		$mail->Username = "verkiezingen.deforesters@gmail.com";
		$mail->Password = "tsJ5zdwCjBaQlp8rZ6qkKU9h43AXMGIP";
		$mail->Host     = "smtp.gmail.com";
		$mail->Mailer   = "smtp";
		$mail->SetFrom("verkiezingen.deforesters@gmail.com", "Foresters Verkiezing 2020");
		$mail->AddReplyTo("verkiezingen.deforesters@gmail.com", "Foresters Verkiezing 2020");
		$mail->AddAddress($mailTo);
		$mail->WordWrap   = 80;
		$mail->Subject = $subject;
		$mail->MsgHTML($content);
		$mail->IsHTML(true);
		return $mail->Send();
}
