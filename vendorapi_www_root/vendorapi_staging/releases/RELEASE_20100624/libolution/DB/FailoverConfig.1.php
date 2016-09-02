<?php

/**
 * Composite config that can failover if a connection can't be made.
 *
 * This attempts to acquire a database connection from the list of given
 * configurations in order, returning the first successful connection. This is
 * useful in situations where a connection must always be established, but
 * certain configurations are preferred over others. For instance, attempting
 * to use one or more slaves when possible, but falling back to the master to
 * guarantee that a connection is always available.
 *
 * In addition, this attempts to guarantee that the returned connection is
 * actually connected. These liveliness checks are currently specific to
 * DB_Database_1 implementations.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_FailoverConfig_1 implements DB_IDatabaseConfig_1
{
	/**
	 * @var array DB_IDatabaseConfig_1[]
	 */
	protected $config = array();

	/**
	 * Adds a configuration to the failover order
	 *
	 * NOTE: Configurations will be used in the order they're added.
	 *
	 * @param DB_IDatabaseConfig_1 $config
	 * @return void
	 */
	public function addConfig(DB_IDatabaseConfig_1 $config)
	{
		$this->config[] = $config;
	}

	/**
	 * Gets a database connection, attempting each configuration in order
	 *
	 * @param void $winning_config Will be set to the config that connected (by reference)
	 * @return DB_IConnection_1
	 */
	public function getConnection(&$winning_config = NULL)
	{
		/* @var $config DB_IDatabaseConfig_1 */
		foreach ($this->config as $config)
		{
			try
			{
				$db = $config->getConnection();
				if ($this->checkConnection($db))
				{
					$winning_config = $config;
					return $db;
				}
			}
			catch (Exception $e)
			{
			}
		}

		throw new Exception('Could not connect to database');
	}

	/**
	 * Attempts the verify the liveliness of a connection before it's returned.
	 *
	 * Returning a dead connection from this config would defeat its entire
	 * purpose. In particular, {@link DB_Database_1} defers connection
	 * until the first use, which is unacceptable in this use case.
	 *
	 * @todo A parameter should be added to DB_IDatabaseConfig_1::getConnection
	 * 	to indicate that a live connection is required and lazily creating
	 * 	the actual database connection is unacceptable.
	 *
	 * @param DB_IConnection_1 $db Database connection to test
	 * @return boolean True if the connection is considered live
	 */
	protected function checkConnection(DB_IConnection_1 $db)
	{
		// if its not an instanceof DB_Database_1, there's
		// really nothing generic enough that we can do...
		if (!$db instanceof DB_Database_1)
		{
			return TRUE;
		}

		// DB_Database_1 defers connection until first use;
		// ensure we have a real connection before continuing
		if (!$db->getIsConnected())
		{
			$db->connect();
			return TRUE;
		}

		// can't reconnect because we may lose setup work that the
		// config did on the connection (eg. setting the time zone)
		return $db->ping(FALSE);
	}
}

?>
