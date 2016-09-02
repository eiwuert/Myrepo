<?php

/**
 * Database connection information for the FBOD eCash companies.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class DBInfo_Enterprise_FBOD
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
		switch (strtoupper($mode))
		{
			case 'LIVE':
			case 'LIVE_READONLY':
			case 'SLAVE':
				$server = array(
					'host' => 'writer.ecashfbod.ept.tss',
					'port' => '3306',
					'user' => 'ecashfbod',
					'password' => 'password',
					'db' => 'ldb_fbod',
					'db_type' => 'mysqli',
				);
				break;
			
			case 'RC':
			case 'RC_READONLY':
				$server = array(
					'host' => 'db101.ept.tss',
					'port' => '3308',
					'user' => 'ecash',
					'password' => 'password',
					'db' => 'ldb_fbod',
					'db_type' => 'mysqli',
				);
			break;

			case 'LOCAL':
			case 'LOCAL_READONLY':
			default:
				$server = array(
					'host' => 'monster.tss',
					'port' => '3309',
					'user' => 'ecash',
					'password' => 'password',
					'db' => 'ldb_fbod',
					'db_type' => 'mysqli',
				);
			break;
		}
		
		return $server;
	}
}
?>
