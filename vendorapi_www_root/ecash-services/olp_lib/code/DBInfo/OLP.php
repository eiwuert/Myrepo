<?php
/**
 * Database connection information for OLP.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DBInfo_OLP
{
	/**
	 * Returns an array of the database information for the given company and mode.
	 *
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	public static function getDBInfo($mode)
	{
		$db_config = new DB_Config();
		$server = $db_config->getLegacyDatabaseConfig('olp', $mode);
		
		// Legacy usage
		$server['bbadmin_db'] = 'olp_blackbox';
		
		return $server;
	}
}
?>
