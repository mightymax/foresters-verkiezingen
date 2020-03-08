<?php

require_once './public/api/include.php';
use PHPMailer\PHPMailer\PHPMailer;

$voteURL = rtrim(getConfig('url'), '/') . '?code=[CODE]&confirm-email=[EMAIL]#/vote';
$voteURLHTML = rtrim(getConfig('url'), '/') . '?code=[CODE]&amp;confirm-email=[EMAIL]#/vote';

if(php_sapi_name()!=='cli') {
	techerr('CLI mode only!');
}

if (count($argv) !== 2) {
	fwrite(STDERR, "Usage: {$argv[0]} input-file.csv\n");
	exit(1);
}

$csv = $argv[1];
if (!file_exists($csv) || !is_file($csv) || !is_readable($csv)) {
	fwrite(STDERR, "File '{$csv} does not exist or is unreadble.\n");
	exit(2);
}

$fp = fopen($argv[1], 'r');
$cols = ['naam', 'email', 'geboren'];
$i = 0;

$subject = "Jouw unieke Foresters code voor de bestuursverkiezingen.";
$TMPL = <<<HTML
Beste [NAAM],

<p>Sinds oktober 2019 heeft onze voetbalclub een nieuw bestuur, bestaande uit <em>Sjoerd Stoker</em>
(voorzitter), <em>Remco Teerhuis</em> (Penningmeester) en <em>Mark Lindeman</em> (Secretaris). Dit bestuur
is voortgekomen uit de werkgroep “<em>Foresters 2.0</em>”. Deze werkgroep (naast de
eerdergenoemden bestaande uit <em>Marc Brunekreef</em>, <em>Tom Leguit</em> en <em>Dennis Oostindie</em>) heeft
<a href="https://www.deforesters.nl/wp-content/uploads/2019/10/presentatie_plan_29.10.19-DEF.pdf">een plan geschreven</a> om de organisatie op enkele punten anders in te richten en te helpen
met het vinden van nieuwe vrijwilligers.</p>

<p>Het huidige bestuur is aangedragen door het vorige bestuur en is benoemd op de <a href="https://www.deforesters.nl/foresters-gaat-nieuwe-fase-in-na-positieve-alv/">ALV van
oktober 2019</a>. Het nieuwe bestuur heeft altijd gesteld op interim basis (dus tijdelijk) aan het
roer te willen staan om de voorgestelde aanpassingen aan de organisatie mede te helpen
uitvoeren. Aan het eind van dit seizoen (2019/2020) komt de interim periode tot een eind
en zal er een normaal gekozen bestuur verder moeten kunnen gaan op de ingeslagen weg.
Wij, Sjoerd, Remco en Mark, hebben de afgelopen periode met veel plezier meegewerkt aan
Foresters 2.0 en zijn meer dan bereid te zijn om door te gaan als nieuw bestuur. We willen
dit echter niet doen zonder dat onze leden zich hierover hebben kunnen uitspreken.
Normaal zouden leden op de Algemene Leden Vergadering (ALV) dit kunnen doen, maar we
vinden het belangrijk dat een zo groot mogelijk aantal leden kan meebeslissen over de koers
van onze club. We zijn immers een vereniging bestaand uit leden die het uiteindelijk voor
het zeggen hebben!</p>

<p>En daarom organiseren wij voor het eerst in onze geschiedenis verkiezingen! In deze mail vind je
een unieke code (je "stempas") waarmee je kunt stemmen of het interim bestuur doorgaat als nieuw
bestuur, of dat je liever wilt dat er een nieuw bestuur komt. </p>

<p>Oh ja: uiteraard breng je je stem anoniem uit, de code die je van ons per e-mail krijgt is
eenmalig te gebruiken en niet terug te herleiden naar een specifiek Foresters lid. Op de ALV
maken we de uitslag bekend, des te meer reden om op 25 maart deze ALV bij te wonen.</p>

<h1>Jouw unieke code is <a href="{$voteURL}">[CODE]</a></h1>
<p>Je kunt je stem uitbrengen door op <a href="{$voteURL}">deze link te klikken</a> of door deze link in je browser te plakken: {$voteURLHTML}</p>
<hr>
<p>Met vriendelijke groet,
	<br><br>Organisatie Foresters Bestuursverkiezing 2020</p>
<p><small style="color: #666666;">Dit is een automatisch gegenereerde mail, het heeft geen zin deze te beantwoorden.</small></p>

HTML;
$tz  = new DateTimeZone('Europe/Amsterdam');
$db = new MyDB();
$stmt = $db->prepare('INSERT INTO codes (code, voted) VALUES (:code, 0)');
if (!$stmt) techerr(__LINE__);

while (($data = fgetcsv($fp, 1000, ",")) !== FALSE) {
	$i++;
	if ($i === 1) {
		if(array_diff($cols, $data)) {
			fwrite(STDERR, "expected '".implode('|', $cols)."' but got '".implode('|', $data)."' as header in your CSV file\n");
			exit(3);
		}
		continue;
	}
	$data = array_combine($cols, $data);
	if( false === PHPMailer::validateAddress($data['email'])) {
		fwrite(STDERR, "{$data['email']},validateAddress fail\n");
		continue;
	} else {
		$age = DateTime::createFromFormat('d/m/Y', $data['geboren'], $tz)->diff(new DateTime('now', $tz))->y;
		$name = ($age < 18 ? '(ouders/verzorgers van) ' : '') . $data['naam'];
		$stmt = $db->prepare('SELECT * FROM codes WHERE code=:code');

		$row = true;
		//Prevents duplicate codes
		while($row) {
			$code = create_hash();
			$stmt->bindValue(':code', $code);
			$result = $stmt->execute();
			$row = $result->fetchArray();
			$stmt->reset();
		}
		
		$body = str_replace(
			['[NAAM]', '[EMAIL]', '[CODE]'],
			[$name, $data['email'], $code],
			$TMPL
		);
		if (sendEmail( $data['email'], $subject, $body)) {
			fwrite(STDOUT, "Code [{$code}] sent to [{$data['email']}]\n");
			$stmt->reset();
			$stmt->bindParam(':code', $code);
			if (!$stmt->execute()) {
				fwrite(STDERR, "{$data['email']},code {$code} not saved in DB\n");
				fwrite(STDERR, "PANIC MODE!!!");
				exit(6);
			}
		} else {
			fwrite(STDERR, "{$data['email']},sendMail failed\n");
			continue;
		}
		break;
	}
}

fclose($fp);
