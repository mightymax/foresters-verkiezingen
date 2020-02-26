<?php
require_once '../../lib/Mail.php';

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open(__DIR__ . '/../../verkiezingen.sqlite3');
    }
	
	function create_hash()
	{
		return str_shuffle(str_shuffle(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 6)));
	}
}



$db = new MyDB();

$email = @$_GET['email'];

$stmt = $db->prepare('SELECT * FROM leden WHERE nummer=:nummer AND email=:email');
$stmt->bindValue(':nummer', @$_GET['nummer']);
$stmt->bindValue(':email', $email);

$result = $stmt->execute();
$data = $result->fetchArray(SQLITE3_ASSOC);
if (!$data) {
	$stmt = $db->prepare('SELECT * FROM leden WHERE nummer=:nummer');
	$stmt->bindValue(':nummer', @$_GET['nummer']);
	$result = $stmt->execute();
	$data = $result->fetchArray(SQLITE3_ASSOC);
	if ($data) {
		$data['found'] = false;
		$email = explode('@', $data['email'], 2);
		$domain = explode('.', $email[1]);
		$part_last = array_pop($domain);
		$part_first = implode('.', $domain);
		$data['email'] = 
			substr($email[0], 0, 1) 
				. str_repeat('*', strlen($email[0]) - 2) 
				. substr($email[0], -1) 
				. '@' 
				. str_repeat('*', strlen($part_first)) 
				. '.' 
				. $part_last;
	} else {
		$data = array('found' => false);
	}
} else {
	$data['found'] = true;
	if (!$data['voted_on']) {
		$hash = $db->create_hash();
		$subject = "Jouw code om te stemmen";
		$content = "<p>Hallo Foresters lid!</p><p>Bedankt dat je je stem wilt uitbrengen. Om te stemmen heb je een code nodig.</p> <p>Jouw code is <strong>{$hash}</strong>.</p><p>Mocht je deze code niet hebben aangevraagd, maak je geen zorgen! Niemand kan op jouw naam stemmen zonder deze code die alleen aan jouw is verzonden.</p><hr><p>Met vriendelijke groet,<br><br>Organisatie Foresters Bestuursverkiezing 2020</p>";
		try {
			sendEmail($email, $subject, $content);
			$stmt = $db->prepare('UPDATE leden SET hash=:hash, login_on=CURRENT_TIMESTAMP WHERE nummer=:nummer');
			$stmt->bindValue(':nummer', $data['nummer']);
			$stmt->bindValue(':hash', $hash);
			$stmt->execute();
		} catch (Exception $e) {
			header('X-Error-Message: ' . $e->getMessage());
		}
	}
}

header('Content-Type: application/json');
echo json_encode($data);
