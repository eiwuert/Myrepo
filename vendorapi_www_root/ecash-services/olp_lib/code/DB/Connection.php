<?php

/**
 * Returns a Database connection using the DB_Server class.
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class DB_Connection
{
	/* Connection timeout in seconds */
	const CONNECTION_TIMEOUT = 5;
	
	/**
	 * Holds database connection instances
	 *
	 * @var array
	 */
	private static $instance = array();
	
	/**
	 * Return a PDO looking object. 
	 * Uses $type/$mode/$property_short just like Server::Get_Server
	 * returns DB_Database_1
	 *
	 * @param string $type
	 * @param string $mode
	 * @param string $property_short
	 * @return DB_Database_1
	 */
	public static function getInstance($type, $mode, $property_short = NULL)
	{
		$info = DB_Server::getServer($type, $mode, $property_short);
		
		$hash = md5($info['host'] . (int)$info->get('port', 3306) . $info['db'] . $info['user'] . $info['password']);
		
		if (!isset(self::$instance[$hash]) || !self::$instance[$hash] instanceof DB_Database_1)
		{
			if (strpos($info['host'], ':'))
			{
				list($host, $port) = explode(':', $info['host']);
			}
			else 
			{
				$host = $info['host'];
				$port = (int)$info->get('port', 3306);
			}

			$config = new DB_MySQLConfig_1(
				$host,
				$info['user'],
				$info['password'],
				$info['db'],
				$port,
				array(PDO::ATTR_TIMEOUT => $info->get('timeout', self::CONNECTION_TIMEOUT))
			);

			try
			{
				self::$instance[$hash] = $config->getConnection();
			}
			catch (PDOException $e)
			{
				//Failover for ecash databases GForge #6018 [MJ]
				if (strcasecmp($type, 'MYSQL') === 0)
				{
					switch (strtoupper($mode))
					{
						case 'LIVE_READONLY': //LIVE_READONLY is out, try SLAVE
							return self::getInstance($type, 'SLAVE', $property_short);
							break;
						case 'SLAVE': //SLAVE is out, try LIVE
							return self::getInstance($type, 'LIVE', $property_short);
							break;
						case 'LIVE': //LIVE is out, no more options left, throw error as usual.
						default:
							throw $e;
							break;
					}
				}
				else
				{
					throw $e;
				}
			}
		}

		return self::$instance[$hash];
	}
}
	
?>
