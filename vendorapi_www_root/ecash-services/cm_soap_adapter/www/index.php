<?php

$url = (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'
	.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

if (isset($_GET['wsdl'])) {
	require '../resource/service.wsdl.php';
	exit();
}

$config = parse_ini_file('../config/config.ini');
$override_file = '../config/override.ini';
if (file_exists($override_file))
{
	$override = parse_ini_file($override_file);
	$config = array_merge($config, $override);
}

$opt = array();
$user = null;
if (isset($config['username'])) {
	$user = $opt['login'] = $config['username'];
	$opt['password'] = $config['password'];
}
else if (isset($_SERVER['PHP_AUTH_USER']))
{
	$user = $opt['login'] = $_SERVER['PHP_AUTH_USER'];
	$opt['password'] = $_SERVER['PHP_AUTH_PW'];
}

$opt['encoding'] = isset($config['encoding']) ? $config['encoding'] : 'ISO-8859-1';

require_once 'libolution/AutoLoad.1.php';
AutoLoad_1::addSearchPath('../code/');

$cs_url = $config['customer_service.wsdl'];
if (!empty($user) && isset($config["$user.wsdl"])) {
	$cs_url = $config["$user.wsdl"];
}

$flow = new SoapClient($cs_url, $opt);

$soap = new SoapServer($url.'?wsdl');
$soap->setObject(new CM_Service($flow, new CM_ErrorMessages()));
$soap->handle();