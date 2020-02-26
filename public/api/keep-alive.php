<?php

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open(__DIR__ . '/../../verkiezingen.sqlite3');
    }
	
	function create_hash()
	{
		if (function_exists('random_bytes')) {
			return bin2hex(random_bytes(32));
		} else {
			return bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		}
	}
}

$db = new MyDB();


$stmt = $db->prepare('SELECT nummer, hash, login_on FROM leden WHERE nummer=:nummer AND hash=:hash');
$stmt->bindValue(':nummer', @$_GET['nummer']);
$stmt->bindValue(':hash', @$_GET['hash']);

// exit(str_replace([':nummer', ':hash'], ["'{$_GET['nummer']}'", "'{$_GET['hash']}'"], 'SELECT nummer, hash, login_on FROM leden WHERE nummer=:nummer AND hash=:hash'));

$result = $stmt->execute();
$data = $result->fetchArray(SQLITE3_ASSOC);
if ($data) {
	$stmt = $db->prepare('UPDATE leden SET login_on=DATETIME() WHERE nummer=:nummer');
	$stmt->bindValue(':nummer', @$_GET['nummer']);
	$stmt->execute();
}
header('Content-Type: application/json');
echo json_encode($data);
