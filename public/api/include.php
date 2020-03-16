<?php
if (file_exists(__DIR__ . '/defines.php')) {
	require(__DIR__ . '/defines.php');
}

//This is not suitable for production envirements, use the defines.php in stead to define proper paths
if (!defined('CONFIG_FILE_LOCATION')) define('CONFIG_FILE_LOCATION', __DIR__ . '/../../config.php');
if (!defined('AUTOLOADER_LOCATION')) define('AUTOLOADER_LOCATION', __DIR__ . '/../../vendor/autoload.php');

require AUTOLOADER_LOCATION;

function getConfig($section = null)
{
	$config = @include(CONFIG_FILE_LOCATION);
	if (!$config || ($section !== null && !isset($config[$section]))) {
		throw new Exception('Missing config file or specific section');
	}
	return $section === null ? $config : $config[$section];
}

function sendEmail($mailTo, $subject, $strBody, $dryRun = false)
{
	$config = getConfig('Gmail');
	
	$swift = (new Swift_Message())
	        ->setSubject($subject)
	        ->setFrom($config['From'])
	        ->setTo($mailTo)
	        ->setBody($strBody, 'text/html');
	if (isset($config['ReplyTo'])) {
		$swift->setReplyTo($config['ReplyTo']);
	}

	$message = new Google_Service_Gmail_Message();
	$message->setRaw(strtr(base64_encode($swift->toString()), array('+' => '-', '/' => '_')));

	// Get the API client and construct the service object.
	$client = getGmailClient();
	$service = new Google_Service_Gmail($client);
	if (false === $dryRun) {
		return $service->users_messages->send("me", $message);
	} else {
		return true;
	}
}

function isValidEmail($email)
{
	$validator = new Egulias\EmailValidator\EmailValidator();
	return $validator->isValid($email, new Egulias\EmailValidator\Validation\RFCValidation());
}

function getJustTheGmailClient()
{
	$client = new Google_Client();
	$client->setApplicationName('Foresters Verkiezingen');
	$client->setScopes([
		Google_Service_Gmail::GMAIL_COMPOSE, 
		Google_Service_Gmail::GMAIL_READONLY, 
		Google_Service_Gmail::GMAIL_SEND
	]);
	$config = getConfig('Gmail')['AuthConfig'];
	$client->setClientId($config['client_id']);
	$client->setClientSecret($config['client_secret']);
	
	if (isset($config['redirect_uris'])) {
		$client->setRedirectUri($config['redirect_uris'][0]);
	}
	
	$client->setAccessType('offline');
	$client->setPrompt('select_account consent');
	return $client;
}

function getGmailClient($testAccessToken = false)
{
	$client = getJustTheGmailClient();
	$db = new MyDb();
	$accessTokenString = $db->getParam('GmailAccessToken');
	if ($accessTokenString && json_decode($accessTokenString)) {
		$accessToken = (array)json_decode($accessTokenString);
		try {
			$client->setAccessToken($accessToken);
		} catch (InvalidArgumentException $e) {
			$db->setParam('GmailAuthCode');
			$msg = [
				'error' => $e->getMessage(),
				'trace' => $e->getTrace(),
				'token' => $accessToken,
				'$accessTokenString' => $accessTokenString
			];
			header('Content-Type: application/json');
			echo json_encode($msg);
			exit;
		}
	}
	
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
			if (true === $testAccessToken) {
				return $authUrl;
			}
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
			techerr(__LINE__, 'visit `'.$authUrl.'` to get a verification code and setParam `GmailAccessToken` next.');

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
		$db->setParam('GmailAuthCode', json_encode($client->getAccessToken()));
    }
	return $client;
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
	public function getParams(Array $excludes = NULL)
	{
		if (self::$params === null) {
			if ($excludes and count($excludes)) {
				foreach($excludes as &$val) $val = $this->escapeString($val);
				$stmt = @$this->prepare('SELECT * FROM params WHERE NOT(key IN (:excludes))');
				if (!$stmt) techerr(__LINE__);
				$stmt->bindValue(':excludes', implode(', ', $excludes));
			} else {
				$stmt = @$this->prepare('SELECT * FROM params');
				if (!$stmt) techerr(__LINE__);
			}
			$result = $stmt->execute();
			self::$params = array();
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				self::$params[$row['key']] = $row['val'];
			}
		}
		return self::$params;
	}
	
	public function getCodes($code = null, $voted = null)
	{
		if ($code) {
			$stmt = @$this->prepare('SELECT * FROM codes WHERE code=:code');
		} elseif (null !== $voted) {
			$stmt = @$this->prepare('SELECT * FROM codes WHERE voted=:voted');
		} else {
			$stmt = @$this->prepare('SELECT * FROM codes');
		}
		if (!$stmt) techerr(__LINE__);
		$stmt->bindValue(':code', $code);
		$stmt->bindValue(':voted', (int)$voted);
		$result = $stmt->execute();
		$data = array();
		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
			$data[$row['code']] = $row['voted'];
		}
		return $data;
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


