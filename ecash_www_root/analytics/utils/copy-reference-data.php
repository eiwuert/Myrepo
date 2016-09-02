<?php

/**
 *
 *
 * IF YOU TOUCH THIS SCRIPT YOU WILL REWRITE IT, UNDERSTOOD?
 *
 *
 */

require_once('libolution/AutoLoad.1.php');

if ($argc < 2 || $argv[1] != 'yes')
{
	echo 'no', "\n";
	exit;
}

$a = new DB_MySQLConfig_1(
	'analytics.dx.tss',
	'datax',
	'Eth7eeDu',
	'analysis',
	3307
);

$b = new DB_MySQLConfig_1(
	'db117.ept.tss',
	'ecash',
	'ugd2vRjv',
	'analysis'
);

$a = $a->getConnection();
$b = $b->getConnection();

foreach (array('clk_costs', 'clk_profit_variables', 'promo') as $t)
{
	if ($argc > 2 && $argv[2] == 'really')
	{
		$b->query('TRUNCATE ' . $t);
	}

	$aa = $a->query('SELECT * FROM ' . $t);
	while ($aaa = $aa->fetch())
	{
		$b->query('INSERT INTO ' . $t . ' (' . implode(', ', array_map(array($b, 'quoteObject'), array_keys($aaa))) . ') VALUES (' . implode(', ', array_map(array($b, 'quote'), $aaa)) . ')');
	}
}

?>
