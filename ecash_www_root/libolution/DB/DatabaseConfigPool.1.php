<?php
/**
 * @package DB
 */

/**
 * Database configuration pool manager
 * @author John Hargrove <john.hargrove@sellingsource.com>
 */
class DB_DatabaseConfigPool_1
{
	/**
	 * holds alias cache
	 *
	 * @var array
	 */
	private static $config_pool = array();

	/**
	 * holds cache of active database connections
	 *
	 * @var unknown_type
	 */
	private static $connection_cache;

	/**
	 * Adds a database configuration alias to the pool
	 * The second argument can be a string representing an existing alias
	 * or an instance of a class implementing the IDatabaseConfig interface
	 *
	 * @param string $alias
	 * @param mixed $database_config
	 * @return void
	 */
	public static function add($alias, $database_config)
	{
		if (is_string($database_config))
		{
			if (!isset(self::$config_pool[$database_config]))
			{
				throw new Exception("Attempt to alias to non-existent alias.", 1);
			}
			self::$config_pool[$alias] = self::$config_pool[$database_config];
		}
		else if ($database_config instanceof DB_IDatabaseConfig_1)
		{
			self::$config_pool[$alias] = $database_config;
		}
		else
		{
			throw new Exception("Unsupported type for argument 2 in " . __METHOD__, 2);
		}
	}

	/**
	 * Returns an instance of a class implementing IDatabaseConfig
	 * associated with the alias provided.  throws an Exception if
	 * the alias does not exist
	 *
	 * @param string $alias
	 * @return DB_IDatabaseConfig_1
	 */
	public static function get($alias)
	{
		if (isset(self::$config_pool[$alias]))
			return self::$config_pool[$alias];
		throw new Exception("Attempt to load non-existent database config.", 0);
	}

	/**
	 * returns a database connection using the configuration stored
	 * for the alias provided.  Will reuse existing database connections
	 * unless otherwise specified.
	 *
	 * @param string $alias
	 * @param bool $use_cache
	 * @return DB_IConnection_1
	 */
	public static function getConnection($alias, $use_cache = TRUE)
	{
		$connection = NULL;
		$config = self::get($alias);

		if (isset(self::$connection_cache[$alias]) && $use_cache)
		{
			$connection = self::$connection_cache[$alias];
		}
		else
		{
			$connection = $config->getConnection();
			if ($use_cache)
			{
				self::$connection_cache[$alias] = $connection;
			}
		}
		return $connection;
	}
}

?>
