<?php
if (isset($_GET['xml']))
{
	include 'monitor.php';
	exit(0);
}

set_include_path(implode(PATH_SEPARATOR, array(
	realpath('../code'),
	get_include_path()
)));

require 'libolution/AutoLoad.1.php';

$monitor = new Monitor();

$servers = parse_ini_file('../config/servers.ini', TRUE);

foreach ($servers as $server => $options)
{
	$url = sprintf('http://%s%s', $server, $options['path']);
	$reader = new XMLReader();
	if (@$reader->open($url))
	{
		$monitor->storeStatuses($reader, $server);
	}
	$reader->close();
}

$statuses = $monitor->getRunningScrubbers();
$modules = array('clk', 'com');

include '../views/index.phtml';
