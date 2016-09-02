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
		// We ignore $name_short, becuase all of the Impact companies are currently on the same database.
		switch ($mode)
		{
			case 'SLAVE':
			case 'LIVE':
				$server = array(
					"host"		=> 'writer.ecashimpact.ept.tss',
					"user"		=> "olp",
					"db"		=> "ldb_impact",
					"password"	=> "password",
					"port"		=> 3307
				);
				break;
			
			case 'LIVE_READONLY':
				$server = array(
					"host"		=> 'reader.ecashimpactolp.ept.tss',
					"user"		=> "olp",
					"db"		=> "ldb_impact",
					"password"	=> "password",
					"port"		=> 3306
				);
				break;
			
			case 'RC':
			case 'RC_READONLY':
			case 'LOCAL':
			case 'LOCAL_READONLY':
			default:
				$server = array(
					"host"		=> "db101.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb_impact",
					"password"	=> "password",
					"port"		=> 3308
				);
				break;
		}
		
		$server["db_type"] = "mysqli";

		return $server;
	}
}
?>