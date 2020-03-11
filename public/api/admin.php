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
	case 'stats':
		$db = new MyDB();
		header('Content-Type: application/json');
		echo json_encode($db->getStats());
		break;
	case 'codes':
		$db = new MyDB();
		header('Content-Type: application/json');
		echo json_encode($db->getCodes(@$_GET['code']));
	default:
		header("HTTP/1.1 405 Method Not Allowed");
		err("Unkown or illegale remote procedure call.");
	
}
