<?php

require_once('cashline_processor.php');
require_once('libolution/AutoLoad.1.php');

if ($argc < 3) die("usage: php runloan.php property customer_id\n");

$db = new DB_MySQLConfig_1('serenity.verihub.com', 'serenity', 'firefly', 'datax');
$db = $db->getConnection();

$prop = $argv[1];
$args = $db->queryPrepared($q="
	select
		custnum,
		transaction_date,
		transaction_id,
		type,
		amount,
		paid,
		payment_history,
		unix_timestamp(duedate) dd0,
		unix_timestamp(transaction_date) td0,
		unix_timestamp(datepaid) pd0
	from {$prop}_raw_transact
	where custnum=?",
	array($argv[2])
);
$args = $args->fetchAll(PDO::FETCH_OBJ);
$a = new Cashline_Processor();
$a = $a->Process_Customer($args);
foreach($args as $t)
{
	echo "tran: {$t->transaction_date}, {$t->custnum}, {$t->type}, {$t->amount}, {$t->paid},\n";
}

foreach ($a as $k=>$loan)
{
if($k==6) print_r($loan);
	//echo "loan {$k}:" . print_r($loan->legacy,true) . "\n" . print_r($loan->anal, true);
}
?>
