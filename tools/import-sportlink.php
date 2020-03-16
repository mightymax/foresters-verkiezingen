<?php
/*
 * Relatiecode	Roepnaam	Achternaam	Naam	E-mail	Geslacht	Geboortedatum	Leeftijd	Failed
*/
require_once '../public/api/include.php';

if(php_sapi_name()!=='cli') {
	techerr(__LINE__, 'CLI mode only!');
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

$fp = fopen($csv, 'r');

$i = 0;
$db = new MyDb();

$stmt = $db->prepare('INSERT INTO emails (relatiecode, naam, email, leeftijd, failed) VALUES (:relatiecode, :naam, :email, :leeftijd, :failed)');
if (!$stmt) techerr(__LINE__, 'INSERT statement failed.');

while ($row = fgetcsv($fp, 1000, "\t")) {
	if ($i === 0) {
		$cols = $row;
		$i++;
		continue;
	}
	if (count($row)===8) {
		$row[] = 0;
	}
	if (count($row) !== count($cols)) {
		fwrite(STDERR, implode('|', $row).": wrong ammount of cols.\n");
		continue;
	}
	$row = array_combine($cols, $row);

	if(!isValidEmail($row['E-mail'])) {
		fwrite(STDERR, "{$row['E-mail']},validateAddress fail\n");
		continue;
	}
	
	$nameParts = explode(',', $row['Naam'], 2);
	$naam = trim($nameParts[1]).' '.trim($nameParts[0]);

	$stmt->bindParam(':relatiecode', $row['Relatiecode']);
	$stmt->bindParam(':naam', $naam);
	$stmt->bindParam(':email', $row['E-mail']);
	$stmt->bindParam(':leeftijd', $row['Leeftijd']);
	$stmt->bindParam(':failed', $row['Failed']);
	if (@$stmt->execute()) {
		$stmt->reset();
	} else {
		fwrite(STDERR, "Failed to insert record: {$db->lastErrorMsg()}\n");
	}

	fwrite(STDOUT, "{$row['Relatiecode']},{$naam},{$row['E-mail']},{$row['Leeftijd']}\n");
	$i++;
}
fclose($fp);