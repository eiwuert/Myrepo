<?php
/**
 * Database connection information for StatPro.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DBInfo_StatPro
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
		$server = $db_config->getLegacyDatabaseConfig('statpro', $mode);
		
		return $server;
	}
}
?>