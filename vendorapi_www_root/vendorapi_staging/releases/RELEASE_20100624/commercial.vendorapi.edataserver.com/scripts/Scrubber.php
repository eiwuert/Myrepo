<?php

set_include_path(
	'.'
	. PATH_SEPARATOR . realpath(dirname(__FILE__).'/../lib/')
	. PATH_SEPARATOR . '/usr/share/php'
	. PATH_SEPARATOR . '/virtualhosts'
);

require_once 'libolution/AutoLoad.1.php';
AutoLoad_1::addSearchPath(
	'../code/',
	'../lib/blackbox/'
);

define('PID_FILE', '/var/run/apiscrubber.pid');
define('JOURNAL_PATH', '/var/state/vendor_api');
define('VENDORAPI_BASE_DIR', realpath(dirname(__FILE__).'/../'));

$options = getopt('vm:c:');
$mode = $options['m'];

if (!in_array($mode, array('LOCAL', 'DEV', 'RC', 'LIVE', 'QA', 'QA_AUTOMATED',  'QA_SEMI_AUTOMATED', 'QA_MANUAL', 'QA2')))
{
	echo("Invalid mode $mode\n");
	exit(2);
}

if (!file_exists($options['c']))
{
	echo("Invalid config {$options['c']}\n");
	exit(3);
}

function output($str, $force = FALSE)
{
	if (VERBOSE || $force)
	{
		echo("$str\n");
	}
}

define('VERBOSE', array_key_exists('v', $options));

$config_file = $options['c'];
$data = parse_ini_file($config_file, TRUE);
$config = $data['CONFIG'];
unset($data['CONFIG']);
if (!empty($config['include_path']))
{
	set_include_path($config['include_path'].PATH_SEPARATOR.get_include_path());
}
$scrubber = new VendorAPI_Scrubber_Master($mode, $data);
$scrubber->execute();
