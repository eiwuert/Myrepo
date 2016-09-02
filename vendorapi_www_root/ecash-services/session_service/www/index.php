<?php
// make soap service
$dir = dirname(__FILE__);
set_include_path("../code:../soap:" . get_include_path());
require_once 'AutoLoad.1.php';
require_once 'applog.1.php';

ini_set("soap.wsdl_cache_enabled", "0");	// TODO: remove?


$config = parse_ini_file('../config.ini');
$override_file = '../override.ini';
if (file_exists($override_file))
{
	$override = parse_ini_file($override_file);
	$config = array_merge($config, $override);
}


if (empty($config['service_location']))
{
	$path = '/';
	$request_uri = trim($_SERVER['SCRIPT_NAME'], '/');
	if (stristr($request_uri, '/')) {
		$uri_bits = explode('/', $request_uri);
		if (count($uri_bits) > 1) {
			$path = '/' . implode('/', array_slice($uri_bits, 0, count($uri_bits) - 1));
		}
	}
	$config['service_location'] = sprintf('http%s://%s%s%s',
		(!empty($_SERVER['HTTPS']) ? 's' : ''),
		$_SERVER['SERVER_NAME'],
		($_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : ''),
		$path
	);
}



$pdo = new PDO($config['db_dsn'], $config['db_user'], $config['db_pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$session_service = new SessionService(new Session($pdo));
if (isset($config['std_class_key_file']))
{
	$std_class_file = "../config/{$config['std_class_key_file']}";
	include_once $std_class_file;
	if (isset($stdClassKeys))
	{
		$session_service->setStdClassKeys($stdClassKeys);
	}
}

$log = new Legacy_Log_ApplogAdapter_1(new Applog());

$server = new SoapServer($config['service_location'] . '/sessionwsdl.php?service_location=' . $config['service_location']);
$server->setObject($session_service);



// exception details.
$exception = $code = $message = $details = $name = null;
$actor = 'SessionService';


// actually handle the SOAP/WSDL requests
try
{
	$server->handle();
}
catch (SenderException $e)
{
	$exception = $e;
	$code = 'Sender';
	$message = $e->getMessage();
	$details = $e->getMessage();
}
catch (Exception $e)
{
	$exception = $e;
	$code = 'Receiver';
	$message = 'Server side error';
	if ($log instanceof Log_ILog_1)
	{
		$error_code = 'ERRCODE' . substr(strtoupper(md5('ERROR ' . time() . rand(0, getrandmax()))), 0, 8);
		$log->write("[SessionService][$error_code] " . get_class($e) . ' -- ' . $e->getMessage());
		$message .= ', please contact maintainer with code ' . $error_code;
	}
	$details = $message;
}

if ($exception instanceof Exception)
{
	$server->fault($code, $message, $actor, $details, $name);
}
?>
