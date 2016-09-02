#!/usr/bin/php
<?php

$opt = getopt('h:p:i');

$host = isset($opt['h']) ? $opt['h'] : 'localhost';
$port = isset($opt['p']) ? $opt['p'] : 11211;

$mc = new Memcache();
$mc->addServer($host, $port);

$es = $mc->getExtendedStats();
$ss = array_shift($es);
if (! is_array($ss))
	exit(1);

foreach (array('rusage_user', 'rusage_system') as $k) $ss[$k] = (int)($ss[$k] * 1000000);

$i = 0;
ksort($ss);
if (isset($opt['i']))
	foreach($ss as $k => $v) echo sprintf('%4d', ++$i), '  ', str_pad($k, 50, ' '), $v, "\n";
else
	foreach($ss as $k => $v) echo $v, "\n";

?>
