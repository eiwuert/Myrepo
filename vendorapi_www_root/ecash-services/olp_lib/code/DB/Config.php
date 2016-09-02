<?php

/**
 * Easy access to database information.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class DB_Config
{
	const CONFIG_DATABASE_INFO_XML = 'dbinfo.xml';
	const CONFIG_DATABASE_INFO_LOCAL_XML = 'dbinfo-local.xml';
	
	/**
	 * @var OLP_Config_XML
	 */
	protected static $config = NULL;
	
	/**
	 * Returns the full database connection information.
	 *
	 * @param string $connection_name
	 * @param string $mode
	 * @return array
	 */
	public function getDatabaseConfig($connection_name, $mode)
	{
		$config = $this->getConfig();
		
		$connection_names = explode('/', strtolower($connection_name));
		$mode = strtolower($mode);
		
		$connection = $config['dbinfo'];
		foreach ($connection_names AS $name)
		{
			if (isset($connection[$name]))
			{
				$connection = $connection[$name];
			}
			else
			{
				return FALSE;
			}
		}
		
		if (isset($connection['environments'][$mode]))
		{
			$environment = $connection['environments'][$mode];
			
			if (isset($connection['accounts'][$environment['account']], $connection['hosts'][$environment['host']]))
			{
				$server = array_merge(
					$connection['accounts'][$environment['account']],
					$connection['hosts'][$environment['host']]
				);
			}
			else
			{
				throw new InvalidArgumentException(sprintf("DB info for mode '%s' has incorrect definitions. Environment Account: '%s' (%s)  Environment Host: '%s' (%s)",
					$mode,
					@$environment['account'],
					isset($connection['accounts'][$environment['account']]) ? 'exists' : 'DOES NOT EXIST',
					@$environment['host'],
					isset($connection['hosts'][$environment['host']]) ? 'exists' : 'DOES NOT EXIST'
				));
			}
		}
		else
		{
			throw new InvalidArgumentException("DB info for mode '{$mode}' has not been defined for '{$connection_name}'. Connection:\n" . print_r($connection, TRUE));
		}
		
		return $server;
	}
	
	/**
	 * Returns the legacy format for database connection, for the old DBInfos.
	 *
	 * @param string $connection_name
	 * @param string $mode
	 * @return array
	 */
	public function getLegacyDatabaseConfig($connection_name, $mode)
	{
		$server = $this->getDatabaseConfig($connection_name, $mode);
		
		if (is_array($server))
		{
			$server = array_map(array($this, 'cleanNonScalar'), $server);
			
			if (isset($server['port']) && empty($server['port'])) unset($server['port']);
			
			$data_map = array(
				'database' => 'db',
				'host' => 'host',
				'port' => 'port',
				'username' => 'user',
				'password' => 'password',
				'db_type' => 'db_type',
			);
			
			$server = OLP_Util::dataMap($server, $data_map);
		}
		
		return $server;
	}
	
	/**
	 * Gets the config.
	 *
	 * @return OLP_Config_XML
	 */
	protected function getConfig()
	{
		if (!self::$config)
		{
			$filename_config = realpath(dirname(__FILE__)) . '/config/' . self::CONFIG_DATABASE_INFO_XML;
			$filename_local = realpath(dirname(__FILE__)) . '/config/' . self::CONFIG_DATABASE_INFO_LOCAL_XML;
			
			self::$config = new OLP_Config_XML();
			
			if (!self::$config->loadXMLFile($filename_config))
			{
				throw new RuntimeException("Could not load database config file: {$filename_config}");
			}
			
			// Append local file. If it does not load, that is fine.
			self::$config->loadXMLFile($filename_local, TRUE);
		}
		return self::$config;
	}
	
	/**
	 * Cleans up any values that are not scalar.
	 *
	 * @param mixed $var
	 * @return string
	 */
	protected function cleanNonScalar($var)
	{
		return is_scalar($var) ? $var : '';
	}

	public function getHostModes($server_type)
	{
		$config = $this->getConfig();
		$server_type = strtolower($server_type);
		if (empty($config['dbinfo'][$server_type]))
		{
			throw new RuntimeException("Invalid server type {$server_type}");
		}
		return array_keys($config['dbinfo'][$server_type]['hosts']);
	}
}

?>
