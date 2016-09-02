<?php
set_include_path("../code:../soap:" . get_include_path());
require_once 'libolution/AutoLoad.1.php';

define('DEFAULT_INI', 'bootstrap.ini');
define('OVERRIDE_INI', 'override.ini');

define('THROW_EXCEPTION', TRUE);
define('DONT_THROW_EXCEPTION', FALSE);

$path = explode('/', dirname(__FILE__));
$prefix = $path[count($path)-2];
define('DEFAULT_WSDL_LOCATION', 'http://' . $prefix . '.' . php_uname('n') . '/index.php?WSDL');

ini_set("soap.wsdl_cache_enabled", "0");

/**
 * @return stdClass
 */
function bootstrap_config()
{
	static $ini;
	
	if (!$ini instanceof stdClass)
	{
		$config = array();
		$config = add_to_config($config, DEFAULT_INI, THROW_EXCEPTION);
		$config = add_to_config($config, OVERRIDE_INI, DONT_THROW_EXCEPTION);
		
		$ini = new stdClass();
		foreach ($config as $key => $value)
		{
			$ini->$key = $value;
		}
		$ini->pdo = new PDO($config['db_dsn'], $config['db_user'], $config['db_pass']);
	}
	
	return $ini;
}

function add_to_config(array $start_config, $config_file, $throw_exception = TRUE)
{
	if (!file_exists($config_file) || !is_readable($config_file))
	{
		if ($throw_exception) throw new Exception("Could not parse config file $config_file.");
	} 
	else 
	{
		$read_config = parse_ini_file($config_file, FALSE);
		if (is_array($read_config))
		{
			return array_merge($start_config, $read_config);
		}
	}
	
	return $start_config;
}
?>