<?php
/**
 * Database connection information for the Agean eCash companies.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DBInfo_Enterprise_Agean
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
		// We ignore $name_short, becuase all of the Agean companies are currently on the same database.
		switch ($mode)
		{
			case 'SLAVE':
			case 'LIVE':
				$server = array(
					'host'		=> 'writer.ecashagean.ept.tss',
					'port'		=> 3306,
					'user'		=> 'olp',
					'password'	=> 'password',
					'db'		=> 'ldb_agean'
				);
				break;
			case 'LIVE_READONLY':
				$server = array(
					'host'		=> 'reader.ecashageanolp.ept.tss',
					'port'		=> 3307,
					'user'		=> 'olp',
					'password'	=> 'password',
					'db'		=> 'ldb_agean'
				);
				break;
			default:
			case 'RC':
			case 'RC_READONLY':
				$server = array(
					'host'		=> 'db101.ept.tss',
					'port'		=> 3308,
					'user'		=> 'ecash',
					'password'	=> 'password',
					'db'		=> 'ldb_agean'
				);
			break;
		}
		
		$server["db_type"] = "mysqli";

		return $server;
	}
}
?>