<?php
require_once __DIR__ . '/include.php';

$db = new MyDB();

header('Content-Type: application/json');
echo json_encode($db->getParams(['GmailAccessToken']));
