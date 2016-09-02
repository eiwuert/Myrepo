<?php
/**
 * Database connection information for the Impact eCash companies.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DBInfo_Enterprise_Impact
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
		
		if (!strcasecmp($name_short, 'IIC'))
		{
			$server = $db_config->getLegacyDatabaseConfig('enterprise/commercial/iic', $mode);
		}
		else
		{
			$server = $db_config->getLegacyDatabaseConfig('enterprise/commercial/impact', $mode);
		}
		
		return $server;
	}
}
?>
