<?php

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open(__DIR__ . '/../../verkiezingen.sqlite3');
    }
}

$db = new MyDB();

if (isset($_GET['is_group'])) {
	$is_group = (int)$_GET['is_group'] === 1 ? 1 : 0;
	$stmt = $db->prepare('SELECT code, naam, rol, is_group FROM kandidaat WHERE hoort_by IS NULL AND is_group='.$is_group.' ORDER BY aangemeld_op');
} else {
	$stmt = $db->prepare('SELECT code, naam, rol, is_group FROM kandidaat WHERE hoort_by IS NULL ORDER BY aangemeld_op');
}

$result = $stmt->execute();
$data = [];
while($row = $result->fetchArray(SQLITE3_ASSOC)) {
	if ((int)$row['is_group'] === 1) {
		$row['anderen'] = [];
		$stmt = $db->prepare('SELECT naam, rol FROM kandidaat WHERE hoort_by=:code');
		$stmt->bindValue(':code', $row['code']);
		$hoort_by_result = $stmt->execute();
		while($hoort_by = $hoort_by_result->fetchArray(SQLITE3_ASSOC)) {
			$row['anderen'][] = $hoort_by;
		}
	}
	$data[] = $row;
}
header('Content-Type: application/json');
echo json_encode($data);
