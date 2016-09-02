<?php

require_once('AutoLoad.1.php');
set_include_path(realpath(dirname(__FILE__).'/../lib/').':'.get_include_path());


$fp = $argv[1] ? $argv[1] : '/tmp/journal.db';

$db = new DB_Database_1('sqlite:'.$fp);

$rs = Message_1::prepareJournal($db);

var_dump($rs);

print_r(Message_1::$stat);

?>
