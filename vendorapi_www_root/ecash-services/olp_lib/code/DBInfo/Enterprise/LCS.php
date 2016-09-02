<?php
/**
 * DB Connection class for Lending Cash Source (LCS)
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DBInfo_Enterprise_LCS
{
	/**
	 * Returns an array of the database information for the given company and mode.
	 *
	 * @param string $name_short the name short of the company to retrieve db info for
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	public static function getDBInfo($name_short, $mode)
	{
		$db_config = new DB_Config();
		$server = $db_config->getLegacyDatabaseConfig('enterprise/commercial/lcs', $mode);
		
		return $server;
	}
}
?>
