<?php
if (file_exists(__DIR__ . '/defines.php')) {
	require(__DIR__ . '/defines.php');
}

//This is not suitable for production envirements, use the defines.php in stead to define proper paths
if (!defined('CONFIG_FILE_LOCATION')) define('CONFIG_FILE_LOCATION', __DIR__ . '/../../config.php');
if (!defined('AUTOLOADER_LOCATION')) define('AUTOLOADER_LOCATION', __DIR__ . '/../../vendor/autoload.php');


require AUTOLOADER_LOCATION;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function getConfig($section = null)
{
	$config = @include(CONFIG_FILE_LOCATION);
	if (!$config || ($section !== null && !isset($config[$section]))) {
		throw new Exception('Missing config file or specific section');
	}
	return $section === null ? $config : $config[$section];
}

function sendEmail($mailTo, $subject, $content, $dryRun = false)
{
	$config = getConfig('email');

	$mail = new PHPMailer(true);
	
	//Defaults:
	$mail->SMTPDebug = 0;
	$mail->SMTPAuth = TRUE;
	$mail->SMTPSecure = "tls";
	$mail->Port     = 587;  
	$mail->Host     = "smtp.gmail.com";
	$mail->Mailer   = "smtp";
	
	foreach ($config as $key => $val) {
		if ($key === 'Mailer' && $val === 'smtp') $mail->IsSMTP();
		if (!is_array($val) && !is_object($val)) {
			$mail->set($key, $val);
		} elseif ($key == 'From') {
			$mail->SetFrom($val['email'], $val['name']);
		} elseif ($key == 'ReplyTo') {
			$mail->AddReplyTo($val['email'], $val['name']);
		}
	}
	
	$mail->WordWrap   = 80;
	$mail->Subject = $subject;
	$mail->AddAddress($mailTo);
	$mail->MsgHTML($content);
	$mail->IsHTML(true);

	if(true === $dryRun) {
		return true;
	} else {
		// throw new Exception('Better safe than sorry!');
		return $mail->Send();
	}
}

function create_hash($size = 6)
{
	return str_shuffle(str_shuffle(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 6)));
}

function techerr($line, $msg = 'SQL error') {
	$data = array(
		'found' => false,
		'reason' => "Er heeft zich een technische fout voorgedaan (code {$line} / {$msg})"
	);
	if(php_sapi_name()=='cli') {
		fwrite(STDERR, $data['reason']."\n");
	} else {
		header('Content-Type: application/json');
		echo json_encode($data);
	}
	exit($line);
	
}

function err($msg) {
	$data = array(
		'found' => false,
		'reason' => $msg
	);
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;
}

function msg($msg) {
	$data = array(
		'found' => true,
		'reason' => $msg
	);
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;
}

class MyDB extends SQLite3
{
	public $throttle_timeout = 60;
	public $max_attempts = 5;
	
	protected static $params = null;
	
    function __construct()
    {
		$dbPath = getConfig('dbPath');
        $this->open($dbPath);
    }
	
	public static function limitReached()
	{
		sleep(20);
		$data = array(
			'found' => false,
			'reason' => "You've exceeded the number of API requests. We've blocked IP address {$_SERVER['REMOTE_ADDR']} for a few minutes."
		);
		header('Content-Type: application/json');
		header("HTTP/1.1 429 Too Many Requests");
		echo json_encode($data);
		exit;
	}
	
	public function throttle()
	{
		$ip = @$_SERVER['REMOTE_ADDR'];
		if (!$ip) self::limitReached();
		$stmt = @$this->prepare('SELECT attempts, last_seen FROM throttle WHERE strftime("%s",CURRENT_TIMESTAMP) - strftime("%s", last_seen) <= :throttle_timeout AND ip=:ip');
		if (!$stmt) techerr(__LINE__);
		$stmt->bindValue(':throttle_timeout', $this->throttle_timeout);
		$stmt->bindValue(':ip', $ip);
		$result = $stmt->execute();
		$row = $result->fetchArray(SQLITE3_ASSOC);
		if (!$row) {
			$stmt = @$this->prepare('DELETE FROM throttle WHERE ip=:ip');
			if (!$stmt) techerr(__LINE__);
			$stmt->bindValue(':ip', $ip);
			$stmt->execute();

			$stmt = @$this->prepare('INSERT INTO throttle (ip, attempts, last_seen) VALUES (:ip, 1, DATETIME())');
			if (!$stmt) techerr(__LINE__);
			$stmt->bindValue(':ip', $ip);
			$stmt->execute();
			return true;
		} else {
			$stmt = @$this->prepare('UPDATE throttle SET attempts=attempts+1, last_seen=DATETIME() WHERE ip==:ip AND last_seen=:last_seen');
			if (!$stmt) techerr(__LINE__);
			$stmt->bindValue(':ip', $ip);
			$stmt->bindValue(':last_seen', $row['last_seen']);
			$stmt->execute();

			if ($row['attempts'] > $this->max_attempts) {
				self::limitReached();
			} else {
				return true;
			}
		}
	}
	public function getParams()
	{
		if (self::$params === null) {
			$stmt = @$this->prepare('SELECT * FROM params');
			if (!$stmt) techerr(__LINE__);
			$result = $stmt->execute();
			self::$params = array();
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				self::$params[$row['key']] = $row['val'];
			}
		}
		return self::$params;
	}
	
	public function getParam($key) 
	{
		return isset($this->getParams()[$key]) ? $this->getParams()[$key] : null;
	}
	
	public function resetCode($code, $delete = false)
	{
		if (!$code) return;
		$stmt = @$this->prepare('DELETE FROM codes WHERE code=:code');
		if (!$stmt) techerr(__LINE__);
		$stmt->bindValue(':code', $code);
		$stmt->execute();
		
		if (false === $delete) {
			$stmt = @$this->prepare('INSERT INTO codes (code, voted) VALUES (:code, 0)');
			$stmt->bindValue(':code', $code);
			$stmt->execute();
		}
		
		return true;
		
	}
	public function setParam($key, $val = null)
	{
		$stmt = @$this->prepare('DELETE FROM params WHERE key=:key');
		if (!$stmt) techerr(__LINE__);
		$stmt->bindValue(':key', $key);
		$stmt->execute();
		
		if (null !== $val) {
			$stmt = @$this->prepare('INSERT INTO params VALUES (:key, :val)');
			$stmt->bindValue(':key', $key);
			$stmt->bindValue(':val', $val);
			$stmt->execute();
		}
		
		return true;
		
	}
	
	public function getStats()
	{
		$stats = ['codes' => [], 'votes' => []];
		$stmt = @$this->prepare('SELECT voted, COUNT(*) AS c FROM codes GROUP BY voted');
		$result = $stmt->execute();
		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
			$k = (int)$row['voted']===1 ? 'voted' : 'not-voted';
			$stats['codes'][$k] = $row['c'];
		}
		$stmt = @$this->prepare('SELECT vote, COUNT(vote) AS c FROM votes GROUP BY vote;');
		$result = $stmt->execute();
		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
			switch ((int)$row['vote']) {
				case -1:
					$k = 'blanco'; break;
				case 1:
					$k = 'yes'; break;
				case 0:
					$k = 'no'; break;
			}
			$stats['votes'][$k] = $row['c'];
		}
		return $stats;
	}
}


