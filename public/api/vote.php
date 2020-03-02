<?php
require_once __DIR__ . '/include.php';

$voted_for = @$_GET['kandidaat'];

if (!@$_GET['code']) {
	$data = array(
		'found' => false,
		'reason' => 'Voer de code in die we je per mail gestuurd hebben of vraag een nieuwe code aan (alleen als je nog niet eerder je stem hebt uitgebracht).'
	);
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;
}

$db = new MyDB();


$stmt = $db->prepare('SELECT * FROM kandidaat WHERE code=:code');
$stmt->bindValue(':code', $voted_for);
$result = $stmt->execute();
$kandidaat = $result->fetchArray(SQLITE3_ASSOC);

if (!$kandidaat) {
	$data = array(
		'found' => false,
		'reason' => 'Ongeldige kandidaat: de gekozen kandidaat staat niet in de lijst met kandidaten.'
	);
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;
} elseif ((int)$kandidaat['is_group'] === 1) {
		$kandidaat['anderen'] = [];
		$stmt = $db->prepare('SELECT naam, rol FROM kandidaat WHERE hoort_by=:code');
		$stmt->bindValue(':code', $kandidaat['code']);
		$hoort_by_result = $stmt->execute();
		while($hoort_by = $hoort_by_result->fetchArray(SQLITE3_ASSOC)) {
			$kandidaat['anderen'][] = $hoort_by;
		}
}

$stmt = $db->prepare('SELECT *, strftime("%s",CURRENT_TIMESTAMP) - strftime("%s", login_on) AS sec_passed FROM leden WHERE hash=:hash');
$stmt->bindValue(':hash', @$_GET['code']);

$result = $stmt->execute();
$lid = $result->fetchArray(SQLITE3_ASSOC);
if (!$lid) {
	$data = array(
		'found' => false,
		'reason' => 'Ongeldige of verlopen code: vraag eventueel een nieuwe code aan door opnieuw in te loggen.'
	);
} else {
	if ($lid['voted_on']) {
		$data = array(
			'found' => false,
			'reason' => 'Je hebt je stem al uitgebracht op '.date('d-m-Y', strtotime($lid['voted_on'])).'.'
		);
	} elseif ($lid['sec_passed'] > 12 * 3600) {
		$data = array(
			'found' => false,
			'reason' => 'Je code is verlopen: vraag eventueel een nieuwe code aan door opnieuw in te loggen.'
		);
	} else {
		$stmt = @$db->prepare('UPDATE kandidaat SET votes=votes+1 WHERE code=:code');
		if (!$stmt) techerr(__LINE__);
		$stmt->bindValue(':code', $voted_for);
		$result = $stmt->execute();
		
		//Backup votes, maybe for statistical purpuses?
		$stmt = $db->prepare('INSERT INTO votes (kandidaat, voted_on) VALUES (:code, DATETIME())');
		if (!$stmt) techerr(__LINE__);
		$stmt->bindValue(':code', $voted_for);
		$result = $stmt->execute();
		
		$stmt = @$db->prepare('UPDATE leden SET voted_on=DATETIME() WHERE hash=:hash');
		if (!$stmt) techerr(__LINE__);

		$stmt->bindValue(':hash', $lid['hash']);
		$stmt->execute();
		
		$data = array('found' => true);
		
		if (@$_GET['confirm-email']) {
			$naam = $kandidaat['naam'];
			if ($kandidaat['is_group']) {
				$naam .= ', ' . $kandidaat['anderen'][0]['naam'] . ' en ' . $kandidaat['anderen'][1]['naam'];
			}
		
			try {
				$subject = 'Bedankt voor jouw stem';
				$content = "<p>Hallo Foresters lid!</p><p>Bedankt dat je je stem hebt uitgebracht op <strong>{$naam}</strong>. Hopelijk zien we elkaar op de Algemene Ledenvergadering van 25 maart waar de uitslag bekend wordt gemaakt.</p><hr><p>Met vriendelijke groet,<br><br>Organisatie Foresters Bestuursverkiezing 2020</p><p><small style=\"color: #666666;\">Dit is een automatisch gegenereerde mail, het heeft geen zin deze te beantwoorden.</small></p>";
				sendEmail($_GET['confirm-email'], $subject, $content);
				$data['confirm-email'] = $_GET['confirm-email'];
			} catch (Exception $e) {
				header('X-Error-Message: ' . $e->getMessage());
			}
		} else {
			header('X-Error-Message: Geen e-mailadres bekend.');
		}
		
	}
}

header('Content-Type: application/json');
echo json_encode($data);
