<?php
/**
 * Database connection information for site types.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class DBInfo_SiteTypes
{
	/**
	 * Get connection parameters for the db for site types.
	 *
	 * @param string $mode the mode the caller believes we're in (RC/LIVE/LOCAL/etc)
	 * @return array List of connection parameters.
	 */
	public static function getDBInfo($mode)
	{
		$db_config = new DB_Config();
		$server = $db_config->getLegacyDatabaseConfig('sitetype', $mode);
		
		return $server;
	}
}
?>
