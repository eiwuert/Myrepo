<?php
/**
 * Database connection information for the AALM eCash company.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DBInfo_Enterprise_AALM
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
		// We ignore $name_short, becuase AALM is a single company
		switch ($mode)
		{
			case 'SLAVE':
			case 'LIVE':
				$server = array(
					'host'		=> 'writer.ecashaalm.ept.tss',
					'port'		=> 3306,
					'user'		=> 'olp',
					'password'	=> 'password',
					'db'		=> 'ldb_generic'
				);
				break;
			case 'LIVE_READONLY':
				$server = array(
					'host'		=> 'reader.ecashaalmolp.ept.tss',
					'port'		=> 3308,
					'user'		=> 'olp',
					'password'	=> 'password',
					'db'		=> 'ldb_generic'
				);
				break;
			case 'LOCAL':
			case 'LOCAL_READONLY':
				$server = array(
					'host'		=> 'db101.ept.tss',
					'port'		=> 3308,
					'user'		=> 'ecash',
					'password'	=> 'password',
					'db'		=> 'ldb_generic'
				);
				break;
			case 'RC':
			case 'RC_READONLY':
			default:
				$server = array(
					'host'		=> 'db101.ept.tss',
					'port'		=> 3308,
					'user'		=> 'ecash',
					'password'	=> 'password',
					'db'		=> 'ldb_generic'
				);
				break;
		}
		
		$server["db_type"] = "mysqli";

		return $server;
	}
}
?>