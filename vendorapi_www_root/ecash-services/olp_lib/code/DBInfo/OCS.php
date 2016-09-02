<?php
/**
 * Database connection information for OCS.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class DBInfo_OCS
{
	/**
	 * Returns an array of the database information for the given mode.
	 *
	 * @param string $mode
	 * @return array
	 */
	public static function getDBInfo($mode)
	{
		$db_config = new DB_Config();
		$server = $db_config->getLegacyDatabaseConfig('ocs', $mode);
		
		return $server;
	}
}
?>
