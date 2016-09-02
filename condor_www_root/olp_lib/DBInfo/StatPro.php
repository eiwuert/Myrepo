<?php
/**
 * Database connection information for StatPro.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DBInfo_StatPro
{
	/**
	 * Returns an array of the database information for the given mode.
	 *
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	public static function getDBInfo($mode)
	{
		$server = array();
		
		switch (strtoupper($mode))
		{
			/**
			 * OLP shouyld never directly access any instance but
			 * the reporting instance so have all of the "Live" type
			 * requests go to the reporting instance
			 */
			case 'LIVE':
			case 'SLAVE':
			case 'REPORT':
			case 'ARCHIVE':
				$server = array(
					'db'		=> 'sp2',
					'host'		=> 'reporting.statpro2.ept.tss:3307',
					'user'		=> 'olp',
					'password'	=> 'password'
				);
				break;
			
			/**
			 * There is no "development" instance for statpro so
			 * point all non-live requests to RC
			 */
			case 'LOCAL':
			case 'RC':
			default:
				$server = array(
					'db'		=> 'sp2',
					'host'		=> 'db101.clkonline.com:3325',
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