<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/include.php';

try {
	$config = getConfig('admin');
} catch(Exception $e) {
	header("HTTP/1.1 500 Internal Server Error");
	err("Failed to get config.");
}

if (!in_array($_SERVER['REMOTE_ADDR'], $config['ip_addresses'])) {
	header("HTTP/1.1 403 Forbidden");
	err("You do not have access to this section of our website.");
}

$cmd = @$_GET['cmd']; //RPC light ...
switch ($cmd) {
	case 'power-on':
		$db = new MyDB();
		$db->setParam('power', 'on');
		msg("Power is on");
	case 'power-off':
		$db = new MyDB();
		$db->setParam('power', 'off');
		msg("Power is off");
	case 'reset-code':
		$db = new MyDB();
		$db->resetCode(@$_GET['code']);
		msg("Code reset");
	case 'clear-code':
		$db = new MyDB();
		$db->resetCode(@$_GET['code'], true);
		msg("Code cleared");
	case 'print-defines':
		highlight_file(__DIR__ . '/defines.php');
		break;
	case 'print-config':
		echo str_replace(getConfig('email')['Password'], '****', highlight_file(CONFIG_FILE_LOCATION, true));
		break;
	case 'phpinfo':
		phpinfo();
		break;
	case 'sendmail':
		if (!isset($_POST['email'])) {
			$html = <<<HTML
<html>
	<head>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	</head>
	<body style="padding:10px;">
		<form class="form-inline" method=POST action="./admin.php?cmd=sendmail">
			<label for="email">Send test mail to:</label> &nbsp;
			<input type=email class="form-control" name="email" id="email"> &nbsp;
			<button type="submit" class="btn btn-primary">send test email</button>
		</form>
	</body>
</html>
HTML;
			exit($html);
		} else {
			$res = sendEmail($_POST['email'], "Test mail", "This is a <strong>test</strong> mail from the admin API.");
			header('Content-Type: application/json');
			echo json_encode($res);
			exit;
		}
		break;
	case 'chechGmailClient':
		$client = getGmailClient(true);
		if (is_string($client)) {
			$html = <<<HTML
<html>
	<head>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	</head>
	<body style="padding:10px;">
		<p><a target="_blank" href="$client">$client</a></p>
		<form class="form-inline" method=POST action="./admin.php?cmd=setGmailAuthCode">
			<label for="GmailAuthCode">GmailAuthCode:</label> &nbsp;
			<input type=text class="form-control" name="GmailAuthCode" id="GmailAuthCode"> &nbsp;
			<button type="submit" class="btn btn-primary">saveGmailAccessToken</button>
		</form>
	</body>
</html>
HTML;
			exit($html);
		}
		msg('Looks fine to me');
		break;
	case 'setGmailAuthCode':
		if (isset($_POST['GmailAuthCode'])) {
			$db = new MyDb();
			$client= getJustTheGmailClient();
			$accessToken = $client->fetchAccessTokenWithAuthCode($_POST['GmailAuthCode']);
			if (array_key_exists('error', $accessToken)) {
				$db->setParam('GmailAccessToken');
				$msg = [
					'error' => $accessToken['error'],
					'accessToken' => $accessToken,
					'GmailAuthCode' => $_POST['GmailAuthCode'],
					'Solution' => 'Fetch new AuthToken via admin.php?cmd=chechGmailClient'
				];
				header('Content-Type: application/json');
				echo json_encode($msg);
				exit;
            } else {
				$db->setParam('GmailAccessToken', json_encode($accessToken));
            	header('Content-Type: application/json');
				echo json_encode($accessToken);
				exit;
            }
		} else {
			techerr(__LINE__, 'No POST var recieved');
		}
	case 'setParam':
		$key = @$_GET['key'];
		if (!$key) techerr(__LINE__, 'no `key` provided');
		$db = new MyDb();
		$db->setParam($key, isset($_GET['val']) ? $_GET['val'] : null);
		msg('Param `'.$key.'` set');
	case 'stats':
		$db = new MyDB();
		header('Content-Type: application/json');
		echo json_encode($db->getStats());
		break;
	case 'createAuthUrl':
		$url = getGmailCLient()->createAuthUrl();
		exit('<p><a href="'.$url.'">'.$url.'</a></p>');
	case 'codes':
		$db = new MyDB();
		header('Content-Type: application/json');
		echo json_encode($db->getCodes(@$_GET['code'], @$_GET['voted']));
		break;
	default:
		header("HTTP/1.1 405 Method Not Allowed");
		err("Unkown or illegale remote procedure call.");
	
}
