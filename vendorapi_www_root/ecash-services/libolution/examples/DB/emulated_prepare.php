<?php

require 'mysqli.1.php';
require_once 'libolution/AutoLoad.1.php';

$mysqli = new MySQLi_1('localhost', 'andrewm', '');
$db = new DB_MySQLiAdapter_1($mysqli);

$query = new DB_EmulatedPrepare_1($db, 'SELECT * FROM test WHERE name = ?');
var_dump($query->getQuery(array("tes's")));

?>
