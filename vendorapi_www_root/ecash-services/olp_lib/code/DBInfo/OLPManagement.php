<?php

/**
 * Database connection information for OLP management.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class DBInfo_OLPManagement extends DBInfo_OLP
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
		$server = $db_config->getLegacyDatabaseConfig('olpmanagement', $mode);
		
		return $server;
	}
}

?>
