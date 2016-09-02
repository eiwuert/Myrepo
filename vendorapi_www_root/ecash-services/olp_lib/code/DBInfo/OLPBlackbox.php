<?php
/**
 * Database connection information for OLP.
 *
 * @author Brian Feaver <brian.feaver@olp.com>
 */
class DBInfo_OLPBlackbox
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
		$server = $db_config->getLegacyDatabaseConfig('olpblackbox', $mode);
		
		return $server;
	}
}
?>
