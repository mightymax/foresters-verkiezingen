<?php
define('DRY_RUN', false);
include '../public/api/include.php';
use PHPMailer\PHPMailer\PHPMailer;

if(php_sapi_name()!=='cli') {
	techerr('CLI mode only!');
}

if (count($argv) !== 2) {
	fwrite(STDERR, "Usage: {$argv[0]} db-with-emails.sqlite3\n");
	exit(1);
}

$dbName = $argv[1];
if (!file_exists($dbName) || !is_file($dbName) || !is_readable($dbName)) {
	fwrite(STDERR, "File '{$dbName} does not exist or is unreadble.\n");
	exit(2);
}

$voteURL = rtrim(getConfig('url'), '/') . '?code=[CODE]&confirm-email=[EMAIL]#/vote';
$voteURLHTML = rtrim(getConfig('url'), '/') . '?code=[CODE]&amp;confirm-email=[EMAIL]#/vote';

$subject = "Jouw unieke Foresters code voor de bestuursverkiezingen.";
$TMPL = file_get_contents(__DIR__ . '/mailBody.html');
$TMPL = str_replace(
	['[voteURL]', '[voteURLHTML]'],
	[$voteURL, $voteURLHTML],
	$TMPL
);

$dbEmails = new SQLite3($dbName);
$stmtEmails = $dbEmails->prepare('SELECT relatiecode, naam, email, leeftijd, code FROM emails');
$stmtEmailUpdate = $dbEmails->prepare('UPDATE emails SET code=:code WHERE relatiecode=:relatiecode');

if (!$stmtEmails || !$stmtEmailUpdate) {
	fwrite(STDERR, "DB statement failed, make sure a table 'email' existst with cols 'relatiecode, naam, email, leeftijd, code'");
	exit(2);
}

$emailResult = $stmtEmails->execute();
while ($row = $emailResult->fetchArray(SQLITE3_ASSOC)) {
	if ($row['code']) {
		fwrite(STDERR, "WARNING: Code [{$row['code']}] already sent to {$row['naam']} [{$row['email']}]\n");
		continue;
	}

	if( false === PHPMailer::validateAddress($row['email'])) {
		fwrite(STDERR, "{$row['email']},validateAddress fail\n");
		continue;
	} else {
		$name = ((int)$row['leeftijd'] < 18 ? '(ouders/verzorgers van) ' : '') . $row['naam'];

		$stmt = $dbEmails->prepare('SELECT * FROM emails WHERE code=:code');
		$rowCode = true;
		//Prevents duplicate codes
		while($rowCode) {
			$code = create_hash();
			$stmt->bindValue(':code', $code);
			$result = $stmt->execute();
			$rowCode = $result->fetchArray();
			$stmt->reset();
		}
		
		$body = str_replace(
			['[NAAM]', '[EMAIL]', '[CODE]'],
			[$name, $row['email'], $code],
			$TMPL
		);
		
		try {
			$mailSendResult = sendEmail($row['email'], $subject, $body, DRY_RUN);
		} catch (Exception $e) {
			fwrite(STDERR, "{$row['email']},sendMail failed\n");
			fwrite(STDERR, "sendMail error: {$e->getMessage()}\n");
			fwrite(STDERR, "PANIC MODE ON LINE ".__LINE__."!!!\n");
			exit(6);
		}
		if ($mailSendResult) {
			
			$stmtEmailUpdate->reset();
			$stmtEmailUpdate->bindParam(':code', $code);
			$stmtEmailUpdate->bindParam(':relatiecode', $row['relatiecode']);
			if (!$stmtEmailUpdate->execute()) {
				fwrite(STDERR, "{$row['email']},code {$code} not saved in DB\n");
				fwrite(STDERR, "PANIC MODE!!!");
				exit(6);
			}
			
			$jsonResponse = file_get_contents(getConfig('url') . '/api/admin.php?cmd=reset-code&code=' . $code);
			if (!$jsonResponse || !json_decode($jsonResponse)) {
				fwrite(STDERR, "{$row['email']},code {$code} not saved in remote DB (using API)\n");
				fwrite(STDERR, "PANIC MODE ON LINE ".__LINE__."!!!");
				exit(6);
			}
			fwrite(STDOUT, "Code [{$code}] sent to {$row['naam']} [{$row['email']}]");
			for ($i=0; $i<5; $i++) {
				fwrite(STDOUT, " ".($i+1));
				sleep(1);
			}
			fwrite(STDERR, "\n");
			
		} else {
			fwrite(STDERR, "{$row['email']},sendMail failed\n");
			fwrite(STDERR, "PANIC MODE ON LINE ".__LINE__."!!!\n");
			exit(6);
			continue;
		}
	}
}

