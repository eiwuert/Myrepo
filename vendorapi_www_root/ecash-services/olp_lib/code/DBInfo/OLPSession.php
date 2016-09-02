<?php
/**
 * Database connection information for the OLP Session database.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DBInfo_OLPSession
{
	/**
	 * Returns an array of the database information for the given mode.
	 *
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	public static function getDBInfo($mode)
	{
		$db_config = new DB_Config();
		$server = $db_config->getLegacyDatabaseConfig('olpsession', $mode);
		
		return $server;
	}
}
