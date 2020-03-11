<?php
require_once __DIR__ . '/include.php';

@$_GET['code'] || err('Geen code ontvangen');
@$_GET['vote'] || err('Geen stem ontvangen.');
preg_match('/^(yes|no|blanco)$/', $_GET['vote']) || err('Ongeldige stem.');
preg_match('/^[A-Z0-9]{6}$/', $_GET['code']) || err('Ongeldige code.');

$db = new MyDB();

if ($db->getParam('power') == 'off') err("De website is momenteel niet beschikbaar.");

switch ($_GET['vote']) {
	case 'yes':    $vote =  1; break;
	case 'no' :    $vote =  0; break;
	case 'blanco': $vote = -1; break;
}

$code = @$_GET['code'];

$stmt = $db->prepare('SELECT * FROM codes WHERE code=:code');
$stmt->bindValue(':code', $code);
$result = $stmt->execute();
($row = $result->fetchArray(SQLITE3_ASSOC)) || err('Voer de code in die we je per mail gestuurd hebben of klik de link aan uit je e-mail.');

$db->throttle();

if ($row['voted'] && (int)$row['voted']===1) {
	$data = array(
		'found' => false,
		'reason' => 'De code is al eens eerder gebruikt om een stem uit te brengen.'
	);
} else {
	$stmt = $db->prepare('INSERT INTO votes (vote, voted_on) VALUES (:vote, DATETIME())');
	if (!$stmt) techerr(__LINE__);
	$stmt->bindValue(':vote', $vote);
	$result = $stmt->execute();
	
	$stmt = @$db->prepare('UPDATE codes SET voted=1 WHERE code=:code');
	if (!$stmt) techerr(__LINE__);

	$stmt->bindValue(':code', $code);
	$stmt->execute();
	
	$data = array('found' => true);
	
	if (@$_GET['confirm-email']) {
		try {
			$subject = 'Bedankt voor jouw stem';
			if ($vote === 1) {
				$vote_msg = 'Je wil dat het huidge (interim) bestuur doorgaat als nieuw bestuur, wij danken je voor je steun!';
			} elseif($vote == -1) {
				$vote_msg = 'Je hebt geen mening over voortzetting van het bestuur, maar je stem telt wel mee in de opkomst.';
			} else {
				$vote_msg = 'Je wil dat er nieuwe verkiezingen gehouden worden.';
			}
			$content = "<p>Hallo Foresters lid!</p><p>Bedankt dat je je stem hebt uitgebracht. {$vote_msg} Hopelijk zien we elkaar op de Algemene Ledenvergadering van 25 maart waar de uitslag bekend wordt gemaakt.</p><hr><p>Met vriendelijke groet,<br><br>Organisatie Foresters Bestuursverkiezing 2020</p><p><small style=\"color: #666666;\">Dit is een automatisch gegenereerde mail, het heeft geen zin deze te beantwoorden.</small></p>";
			sendEmail($_GET['confirm-email'], $subject, $content);
			$data['confirm-email'] = $_GET['confirm-email'];
		} catch (Exception $e) {
			header('X-Error-Message: ' . $e->getMessage());
		}
	} else {
		header('X-Error-Message: Geen e-mailadres bekend.');
	}
	
}

header('Content-Type: application/json');
echo json_encode($data);
