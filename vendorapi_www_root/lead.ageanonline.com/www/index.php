<?php

$config = parse_ini_file('../config/config.ini');
$override_file = '../config/override.ini';
if (file_exists($override_file))
{
	$override = parse_ini_file($override_file);
	$config = array_merge($config, $override);
}

if (!empty($config['service_location'])) {
	$url = $config['service_location'];
} else {
	$url = (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'
		.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
}

if (isset($_GET['wsdl'])) {
	require '../resource/service.wsdl.php';
	exit();
}

$opt = array('exceptions' => TRUE);
$user = null;
if (isset($config['username'])) {
	$user = $opt['login'] = $config['username'];
	$opt['password'] = $config['password'];
}
elseif (isset($_SERVER['PHP_AUTH_USER']))
{
	$user = $opt['login'] = $_SERVER['PHP_AUTH_USER'];
	$opt['password'] = $_SERVER['PHP_AUTH_PW'];
}
else
{
	header('WWW-Authenticate: Basic realm="CM SOAP"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Unauthorized';
	exit;
}

$opt['encoding'] = isset($config['encoding']) ? $config['encoding'] : 'ISO-8859-1';

require_once 'libolution/AutoLoad.1.php';
AutoLoad_1::addSearchPath('../code/');

$cs_url = $config['customer_service.wsdl'];
if (!empty($user) && isset($config["$user.wsdl"])) {
	$cs_url = $config["$user.wsdl"];
}

try {
	$flow = new SoapClient($cs_url, $opt);

	$soap = new SoapServer($url.'?wsdl');
	$soap->setObject(new CM_Service($flow, new CM_ErrorMessages()));
	$soap->handle();
} catch (Exception $e) {
	error_log("Unhandled service error for CM SOAP Adapter: " . $e);
	throw new SoapFault('Unknown', 'An unknown error occured while processing your request.');
}
