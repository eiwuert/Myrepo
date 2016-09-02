<?php
/**
 * Database connection information for OLP.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DBInfo_OLP
{
	/**
	 * Returns an array of the database information for the given company and mode.
	 *
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	public static function getDBInfo($mode)
	{
		$server = array();
		
		switch (strtoupper($mode))
		{
			case 'RC':
				$server = array(
					'db'		=> 'rc_olp',
					'host'		=> 'db101.ept.tss:3317',
					'user'		=> 'sellingsource',
					'password'	=> 'password'
				);
				break;
			case 'LIVE':
				$server = array(
					'db'		=> 'olp',
					'host'		=> 'writer.olp.ept.tss',
					'user'		=> 'sellingsource',
					'password'	=> 'password'
				);
				break;
			case 'SLAVE':
				$server = array(
					'db'		=> 'olp',
					'host'		=> 'reader.olp.ept.tss:3307',
					'user'		=> 'sellingsource',
					'password'	=> 'password'
				);
				break;
			case 'REPORT':
				$server = array(
					'db'		=> 'olp',
					'host'		=> 'reporting.olp.ept.tss',
					'user'		=> 'sellingsource',
					'password'	=> 'password'
				);
				break;
			case 'ARCHIVE':
				$server = array(
					'db'		=> 'olp_200505',
					'host'		=> 'analytics.dx',
					'user'		=> 'sellingsource',
					'password'	=> 'password'
				);
				break;
			case 'LOCAL':
			default:
				$server = array(
					'db'		=> 'olp',
					'host'		=> 'monster.tss:3326',
					'user'		=> 'olp',
					'password'	=> 'password'
				);
				break;
		}
		
		$server['db_type'] = 'mysql';

		return $server;
	}
}
?>