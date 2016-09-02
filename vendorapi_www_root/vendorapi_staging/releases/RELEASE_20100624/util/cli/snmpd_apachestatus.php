#!/usr/bin/php
<?php

$opt = getopt('h:p:i');

$host = isset($opt['h']) ? $opt['h'] : 'localhost';
$port = isset($opt['p']) ? $opt['p'] : 81;

$ss = @file_get_contents("http://{$host}:{$port}/server-status?auto");
if (! preg_match('/Accesses: (?<total_hits>\d+).*kBytes: (?<total_kbytes>\d+).*CPULoad: (?<cpuload>[.0-9]+).*Uptime: (?<uptime>\d+).*BusyWorkers: (?<busy_workers>\d+).*IdleWorkers: (?<idle_workers>\d+).*Scoreboard: (\S+)/s', $ss, $m))
	die('preg_match failed');
$sb = strtr($m[7], '_.', 'NE');
unset($m[0], $m[1], $m[2], $m[3], $m[4], $m[5], $m[6], $m[7]);

$m += array(
	'thread_N' => 0,	// Nothing to do (.)
	'thread_S' => 0,	// Starting up
	'thread_R' => 0,	// Reading request
	'thread_W' => 0,	// Writing reply
	'thread_K' => 0,	// Keepalive
	'thread_D' => 0,	// Dns lookup
	'thread_C' => 0,	// Closing connection
	'thread_L' => 0,	// Logging
	'thread_G' => 0,	// Graceful finishing
	'thread_I' => 0,	// Idle cleanup
	'thread_E' => 0,	// Empty slot (_)
);
for($i = 0 ; $i < strlen($sb) ; $i++)
	@$m['thread_'.$sb[$i]]++;

ksort($m);

$i = 0;
if (isset($opt['i']))
	foreach ($m as $k => $v) echo sprintf('%4d', ++$i), '  ', str_pad($k, 50, ' '), $v, "\n";
else
	foreach ($m as $k => $v) echo $v, "\n";

?>
