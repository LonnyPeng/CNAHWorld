<?php

include_once __DIR__ . "/Db/Pdo.php";

// link mysql
$dsn = "mysql:host=127.0.0.1:3306;dbname=db_main";
$username = "root";
$password = "root";
$pdo = new Pdo($dsn, $username, $password);

$sql = "SELECT * FROM t_words ORDER BY world_id ASC LIMIT 1";
$words = $pdo->getAll($sql);
print_r($words);