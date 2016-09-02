<?php
set_include_path("../code:/virtualhosts/libolution/" . get_include_path());
require_once 'AutoLoad.1.php';

require_once 'applog.1.php';

$config = parse_ini_file('../config/config.ini');
$override_file = '../config/override.ini';
if (file_exists($override_file))
{
	$override = parse_ini_file($override_file);
	$config = array_merge($config, $override);
}

$logger = new Legacy_Log_ApplogAdapter_1(new Applog());

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

$url = $config['customer_service.wsdl'];
if (!empty($user) && isset($config["$user.customer_service.wsdl"])) {
	$url = $config["$user.customer_service.wsdl"];
}

$adapter = new BFWSoapAdapter(FALSE);
$adapter->setDebug($config['debug']);
$adapter->setLogger($logger);
$adapter->setSoapClient(new SoapClient($url, $opt));
$adapter->Prpc_Process();

?>
