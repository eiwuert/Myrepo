<?php

require_once 'AutoLoad.1.php';
require_once 'applog.1.php';

AutoLoad_1::addSearchPath('../code');

$config = parse_ini_file('../config/config.ini');
$override_file = '../config/override.ini';
if (file_exists($override_file))
{
	$override = parse_ini_file($override_file);
	$config = array_merge($config, $override);
}

$log_level = Log_ILog_1::LOG_WARNING;
if (isset($config['log.level'])) {
	$log_level = (int)$config['log.level'];
}

$logger = new Legacy_Log_ApplogAdapter_1(new Applog());
$log = new Nirvana_Log($logger, $log_level);

$user_name = (get_magic_quotes_gpc()) ? stripslashes($_REQUEST['user']) : $_REQUEST['user'];
$password = (get_magic_quotes_gpc()) ? stripslashes($_REQUEST['pass']) : $_REQUEST['pass'];
$customer = $_REQUEST['customer'];

$factory = new Nirvana_SourceFactory();
$sources = $factory->getSources($config);

$order = array();
if (isset($config["customer.{$customer}.order"])) {
	$order = array_map('trim', explode(',', $config["customer.{$customer}.order"]));
}

$prpc = new Nirvana_Aggregate($sources, $order, $user_name, $password, $log);