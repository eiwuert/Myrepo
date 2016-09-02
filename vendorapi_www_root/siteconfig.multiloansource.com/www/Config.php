<?php

define('BASE_DIR', realpath(dirname(__FILE__).'/../'));
require 'AutoLoad.1.php';

AutoLoad_1::addSearchPath(BASE_DIR.'/code/');

$config = parse_ini_file('../config/config.ini');
$override_file = '../config/override.ini';
if (file_exists($override_file))
{
	$override = parse_ini_file($override_file);
	$config = array_merge($config, $override);
}

if (!empty($config['service_location']))
{
	$soap_url = $config['service_location'];
}
else
{
	$scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
	$soap_url = $scheme.'://'.$_SERVER['HTTP_HOST'] . '/' . trim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/Config.php';
}

if (isset($_GET['wsdl'])) {
	include './wsdl.php';
	exit();
}

try {
	$base_dir = $config['config_dir'];
	if ($base_dir[0] != '/') {
		$base_dir = BASE_DIR.$base_dir;
	}

	$soap = new SoapServer($soap_url.'?wsdl');
	$soap->setObject(new Config_Service($base_dir));
	$soap->handle();
} catch (Exception $e) {
	$soap->fault($e->getCode(), $e->getMessage());
}
