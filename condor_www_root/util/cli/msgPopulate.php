<?php

require_once('AutoLoad.1.php');
set_include_path(realpath(dirname(__FILE__).'/../lib/').':'.get_include_path());


$fp = $argv[1] ? $argv[1] : '/tmp/journal.db';

$db = new DB_Database_1('sqlite:'.$fp);

$ss = Message_1::getSubscriptionSet();

$rs = Message_1::populateDelivery($db, $ss);

var_dump($rs);

print_r(Message_1::$stat);

?>
