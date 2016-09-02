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
		// We ignore $name_short, becuase LCS is a single company
		switch($mode)
		{
			case 'SLAVE':
			case 'LIVE':
				$server = array(
					'host' => 'writer.ecashaalm.ept.tss',
					'port' => '3306',
					'user' => 'olp',
					'password' => 'password',
					'db' => 'ldb_lcs'
				);
				break;
			
			case 'LIVE_READONLY':
				$server = array(
					'host' => 'reader.ecashaalmolp.ept.tss',
					'port' => '3308',
					'user' => 'olp',
					'password' => 'password',
					'db' => 'ldb_lcs'
				);
				break;
			
			case 'RC':
			case 'RC_READONLY':
				$server = array(
					'host' => 'db101.clkonline.com',
					'port' => '3308',
					'user' => 'ecash',
					'password' => 'password',
					'db' => 'ldb_lcs'
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
					'db' => 'ldb_lcs'
				);
			break;
		}

		$server["db_type"] = "mysqli";

		return $server;
	}
}
?>