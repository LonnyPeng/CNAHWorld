<?php

include_once __DIR__ . "/Db/Pdo.php";
include_once __DIR__ . "/Google/Translate/GoogleTranslate.php";

// link mysql
$dsn = "mysql:host=127.0.0.1:3306;dbname=db_main";
$username = "root";
$password = "root";
$pdo = new Db\Pdo($dsn, $username, $password);

$translate = new \Google\Translate\GoogleTranslate();

$sql = "SELECT word_id, word_name_zhcn, word_name_zhtw
		FROM t_words 
		WHERE word_start = 1 
		AND word_name_zhtw = ''
		ORDER BY word_id ASC
		LIMIT 1";
$words = $pdo->getAll($sql);
if (!$words) {
	return "No data.";
}

$words = json_decode(json_encode($words), true);
foreach ($words as $row) {
	$set = $sqlMap = array();
	$newZhcn = $translate->api(array('tl' => 'zh-CN', 'text' => $row['word_name_zhcn']));
	if ($newZhcn != $row['word_name_zhcn']) {
		$set[] = "word_name_zhcn = :word_name_zhcn";
		$sqlMap['word_name_zhcn'] = $newZhcn;
	}

	if ($row['word_name_zhtw']) {
		$newZhtw = $translate->api(array('tl' => 'zh-TW', 'text' => $row['word_name_zhtw']));
		if ($newZhtw != $row['word_name_zhtw']) {
			$set[] = "word_name_zhtw = :word_name_zhtw";
			$sqlMap['word_name_zhtw'] = $newZhtw;
		}
	} else {
		$newZhtw = $translate->api(array('tl' => 'zh-TW', 'text' => $row['word_name_zhcn']));
		$set[] = "word_name_zhtw = :word_name_zhtw";
		$sqlMap['word_name_zhtw'] = $newZhtw;
	}

	if ($set) {
		$sqlMap['word_id'] = $row['word_id'];
		$sql = "UPDATE t_words SET " . implode(",", $set) . " WHERE word_id = :word_id";
		$pdo->exec($sql, $sqlMap);
	}
}

print_r("Lonny");