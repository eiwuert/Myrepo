#!/usr/bin/php
<?php

if (posix_getuid() !== 0) die($argv[0]." must be run as root.");

require_once('AutoLoad.1.php');

set_include_path(realpath(dirname(__FILE__).'/../lib/').':'.get_include_path());

define('SYS_FQDN', Message_1::getFqdn());

if (! isset($_SERVER['VMODE'])) $_SERVER['VMODE'] = 'live';

$opt = getopt('d:hlrsz:');

if (isset($opt['h']))
{
	$help = <<<EOD

Usage: $argv[0] [-d <level>] [-z <slice>] [-hls]

  -d <level>    Set debug level. (todo)
  -h            Print this help message.
  -l            Use syslog. (todo)
  -r			Record run info.
  -s            Use subscription set.
  -z <slice>    Only process some slices. (12 will proc slice 1 and 2)

EOD;
	echo $help;
	exit;
}

if (! isset($opt['z']))
{
	$slice = range(0, 9);
}
else
{
	$slice = str_split($opt['z']);
}

foreach ($slice as $s)
{
	if (! is_numeric($s) || ($s < 0 || $s > 9))
	{
		throw new Exception("Invalid slice: $s");
	}
}

/*
** PID
*/
$pid_file = "/var/run/{$_SERVER['VMODE']}_scrub_{$slice[0]}.pid";

if (file_exists($pid_file))
{
	$pid = file_get_contents($pid_file);
	if ($pid && posix_kill(trim($pid), 0))
	{
		$ps = trim(shell_exec("ps ax | grep {$pid} | grep \"{$argv[0]}\" | grep -v grep | wc -l"));
		if ($ps)
		{
			Message_1::log("Process already running with PID: $pid");
			exit(0);
		}
		else
		{
			Message_1::log("Stale PID file: $pid_file");
		}
	}
}

file_put_contents($pid_file, posix_getpid(), LOCK_EX);

/*
** Subscription Repository
*/
try
{
	$subscription_set = isset($opt['s']) ? Message_1::getSubscriptionSet() : NULL;
}
catch (Exception $e)
{
	Message_1::log("WARNING: getSubscriptionSet failed: ".$e->getMessage());
	unset($opt['s']);
}

/*
** Process Files
*/
$pre = $_SERVER['VMODE'].'_';
$glob = array();
foreach (array('/tmp/', Message_1::TMP) as $dir)
{
	foreach ($slice as $s)
	{
		$glob = array_merge($glob, glob($dir.$pre.'msg_pid*_slice'.$s.'.db'));
		if (! $s)
		{
			$glob = array_merge($glob, glob($dir.$pre.'msg_pid*_slice10.db'));
		}
	}
}

Message_1::log("Start proc ".count($glob)." journals");
Message_1::$stat['process']['journal'] = count($glob);
$ts0 = microtime(1);

$num = array('journal' => count($glob), 'batch' => 0, 'message' => 0, 'fail' => 0, 'row' => 0);
foreach ($glob as $file)
{
	$lock = $file.'-lock';

	touch($lock);
	//Message_1::log("scrub $file");

	$db = new DB_Database_1('sqlite:'.$file, NULL, NULL, array(PDO::ATTR_TIMEOUT => 1200));

	Message_1::populateDelivery($db, $subscription_set);

	$tmp = Message_1::processDelivery($db);
	foreach ($tmp as $k => $v) $num[$k] += $v;

	Message_1::removeFinished($db);

	unlink($lock);
}

$ts1 = microtime(1);
$dt = $ts1 - $ts0;
Message_1::log("Done sending {$num['message']} msgs in {$num['batch']} batches with {$num['row']} rows. {$num['fail']} failed msgs. Max mem: ".number_format(memory_get_peak_usage(1)).". Took: ".number_format($dt, 2)." sec.");

if ($num['message'])
{
	if (! $dt) { Message_1::log("WARNING: {$num['message']} messages processed in 0 seconds!"); $dt = .00000000001; }
	Message_1::log(number_format($num['batch']/$dt, 1)." call/sec. ".number_format($num['message']/$dt, 1)." msg/sec. ".number_format($num['row']/$dt, 1)." row/sec.");
}

if(is_array(Message_1::$stat['url']))
{
	uksort(Message_1::$stat['url'], create_function('$a,$b', 'return strlen($a)-strlen($b);'));
	foreach (Message_1::$stat['url'] as $url => $info)
	{
		if (! $info['time']) { Message_1::log("WARNING: {$url} did {$info['rows']} in 0 seconds!"); $info['time'] = .00000000001; }
		Message_1::log("Url {$url} did {$info['rows']} rows in ".number_format($info['time'], 1)." sec (".number_format($info['rows']/$info['time'], 1). " row/sec). ".number_format(($info['size']/1024)/$info['time'], 1)." kb/sec. ".$info['fail']." failed. {$info['batch']} batches. {$info['message']} messages.");
	}
}

if (isset($opt['r']) && in_array(0, $slice))
{
	$last = new LastValue();

	$url = array(
		'http://sp2.epointps.net/action/' => 'sp2-action-rps',
		'http://sp2.epointps.net/event/log/' => 'sp2-log-rps',
		'http://sp2.epointps.net/event/agg/space/' => 'sp2-aggspace-rps',
		'http://sp2.epointps.net/event/agg/context/' => 'sp2-aggcontext-rps',
	);
	foreach ($url as $k => $v)
	{
		if(is_array(Message_1::$stat['url'][$k]))
		{
			$info = Message_1::$stat['url'][$k]; 
			$last->update($v, (int)round($info['rows']/$info['time']));
		}
	}

	$last->update('msg-cron-done', time());
	$idx = array(
		'populate' => array('subscribe', 'http'),
		'process' => array('journal', 'message', 'batch'),
		'remove' => array('empty', 'delete'),
		'queue' => array('NEW', 'FIN','ERR','DL0', 'DL1', 'DL2', 'DL3', 'DL4'),
		'delivery' => array('NEW', 'FIN','ERR','DL0', 'DL1', 'DL2', 'DL3', 'DL4'),
	);
	foreach ($idx as $k => $v)
	{
		foreach($v as $type)
		{
			$num = isset(Message_1::$stat[$k][$type]) ? Message_1::$stat[$k][$type] : 0;
			$last->update('msg-'.$k.'-'.$type, $num);
		}
	}
}

unlink($pid_file);

?>
