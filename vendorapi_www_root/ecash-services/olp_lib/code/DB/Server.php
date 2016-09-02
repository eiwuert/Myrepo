<?php

/**
 * Returns the server/database information based on mode and type.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class DB_Server
{
	/**
	 * Get server configuration information.
	 *
	 * @param string $type the type of connection to retrieve
	 * @param string $mode the mode we're running in
	 * @param string $property_short the property short of the company to get
	 * @return TSS_ArrayObject
	 */
	public static function getServer($type, $mode, $property_short = NULL)
	{
		// set our variables
		$mode = strtoupper(OLP_Environment::getOverrideEnvironment($mode));
		$type = strtoupper($type);
		$property_short = EnterpriseData::resolveAlias($property_short);
		$server = NULL;
		
		$dbconfig = new DB_Config();
		
		switch ($type)
		{
			case 'OLP':
			case 'BLACKBOX':
			case 'EVENT_LOG':
			case 'TRENDEX_LOG':
			case 'BLACKBOX_STATS':
			case 'GENERAL_LOG':
			case 'STATS': // Stat_Limits
				$server = $dbconfig->getLegacyDatabaseConfig('olp', $mode);
				$server['bbadmin_db'] = 'olp_blackbox'; // For legacy support...
				break;
			
			case 'OLPBLACKBOX':
			case 'BLACKBOX3':
				$server = $dbconfig->getLegacyDatabaseConfig('olpblackbox', $mode);
				break;

			case 'MYSQL':
			case 'LDB':
			case 'ECASH':
				$company_name = EnterpriseData::getCompany($property_short);
				
				if (!$company_name)
				{
					$company_name = '_default';
				}
				
				$connection_name = "enterprise/companies/{$company_name}";
				
				$server = $dbconfig->getLegacyDatabaseConfig(strtolower("{$connection_name}/{$property_short}"), $mode);
				
				if (!$server)
				{
					$server = $dbconfig->getLegacyDatabaseConfig(strtolower("{$connection_name}/_default"), $mode);
				}
				break;
			
			case 'CONDOR':
				// This is lame, it returns the URL, not the db connection info
				return OLPCondor_ServerInfo::getServerInfo($property_short, $mode);
			
			case 'CONDOR_DB':
				$server = $dbconfig->getLegacyDatabaseConfig('condor', $mode);
				break;
			
			case 'OLP_SESSION':
				$server = $dbconfig->getLegacyDatabaseConfig('olpsession', $mode);
				break;
			
			case 'SITE_TYPES':
				$server = $dbconfig->getLegacyDatabaseConfig('sitetype', $mode);
				break;
			
			case 'MANAGEMENT':
				$server = $dbconfig->getLegacyDatabaseConfig('olpmanagement', $mode);
				break;
			
			case 'OCS':
			case 'SCHEMA':
			case 'STATPRO':
			default:
				// Just pass it straight in as the connection name.
				$server = $dbconfig->getLegacyDatabaseConfig(strtolower($type), $mode);
				break;
		}
		if (!is_array($server))
		{
			throw new InvalidArgumentException("Could not get server connection information for: Type: {$type}  Mode: {$mode}  Property Short: {$property_short}");
		}
		
		return new TSS_ArrayObject($server);
	}
	
	/**
	 * Returns the database schema information.
	 *
	 * @return array
	 */
	private static function getSchema()
	{
		$server = DBInfo_OLP::getDBInfo(self::$mode);
		
		$server['db'] = 'information_schema';
		
		return $server;
	}
}
?>
