<?php
/**
 * ECash Database connection information for the Quick and Easy company.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DBInfo_Enterprise_QEasy
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
		// We ignore $name_short, becuase QEASY is a single company
		switch ($mode)
		{
			case 'SLAVE':
			case 'LIVE':
				$server = array(
					'host'		=> 'writer.ecashaalm.ept.tss',
					'user'		=> 'olp',
					'password'	=> 'password',
					'db'		=> 'ldb_qeasy'
				);
				break;
			case 'LIVE_READONLY':
				$server = array(
					'host'		=> 'reader.ecashaalm.ept.tss',
					'user'		=> 'olp',
					'password'	=> 'password',
					'db'		=> 'ldb_qeasy'
				);
				break;
			case 'LOCAL':
			case 'LOCAL_READONLY':
				$server = array(
					'host'		=> 'monster.tss',
					'port'		=> 3309,
					'user'		=> 'ecash',
					'password'	=> 'password',
					'db'		=> 'ldb_qeasy'
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
					'db'		=> 'ldb_qeasy'
				);
				break;
		}
		
		$server["db_type"] = "mysqli";

		return $server;
	}
}
?>