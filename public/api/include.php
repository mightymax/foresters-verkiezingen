<?php
require __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function sendEmail($mailTo, $subject, $content)
{
	$config = @include(__DIR__ . '/../../config.php');
	if (!$config || !isset($config['email'])) {
		throw new Exception('Missing config file or "email" section');
	}

	$mail = new PHPMailer(true);
	
	//Defaults:
	$mail->SMTPDebug = 0;
	$mail->SMTPAuth = TRUE;
	$mail->SMTPSecure = "tls";
	$mail->Port     = 587;  
	$mail->Host     = "smtp.gmail.com";
	$mail->Mailer   = "smtp";
	
	foreach ($config['email'] as $key => $val) {
		if ($key === 'Mailer' && $val === 'smtp') $mail->IsSMTP();
		if (!is_array($val) && !is_object($val)) {
			$mail->set($key, $val);
		} elseif ($key == 'From') {
			$mail->SetFrom($val['email'], $val['name']);
		} elseif ($key == 'ReplyTo') {
			$mail->AddReplyTo($val['email'], $val['name']);
		}
	}
	
	$mail->WordWrap   = 80;
	$mail->Subject = $subject;
	$mail->AddAddress($mailTo);
	$mail->MsgHTML($content);
	$mail->IsHTML(true);

	return $mail->Send();
}

function create_hash()
{
	return str_shuffle(str_shuffle(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 6)));
}

function techerr($line, $msg = 'SQL error') {
	$data = array(
		'found' => false,
		'reason' => "Er heeft zich een technische fout voorgedaan (code {$line} / {$msg}"
	);
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;
	
}

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open(__DIR__ . '/../../verkiezingen.sqlite3');
    }
}

