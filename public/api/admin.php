<?php
require_once __DIR__ . '/include.php';

$db = new MyDB();

$config = getConfig('admin');

if (!in_array($_SERVER['REMOTE_ADDR'], $config['ip_addresses'])) {
	header("HTTP/1.1 403 Forbidden");
	err("You do not have access to this section of our website.");
}

$cmd = @$_GET['cmd']; //RPC light ...
switch ($cmd) {
	case 'power_on':
		$db->setParam('power', 'on');
		msg("Power is on");
		break;
	case 'power_off':
		$db->setParam('power', 'off');
		msg("Power is off");
		break;
	default:
		header("HTTP/1.1 405 Method Not Allowed");
		err("Unkown or illegale remote procedure call.");
	
}
