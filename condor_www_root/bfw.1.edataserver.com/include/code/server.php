<?php
require_once 'Enterprise_Data.php';

/**
 * Returns the server/database information based on mode and type.
 * 
 * This class returns the server information based on what mode (local/live/rc) is passed
 * in, what type is passed (mysql/css/etc), and what property short, if given.
 * 
 * Refactored the file to use the DBInfo classes in olp_lib. [BF]
 * 
 * @todo Make this file a wrapper and create a db info file that makes more sense.
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Server
{
	/**
	 * The mode we're running in.
	 *
	 * @var string
	 */
	static private $mode;
	
	/**
	 * The type of connection we're retrieving.
	 *
	 * @var string
	 */
	static private $type;
	
	/**
	 * The property short of the company we're retrieving.
	 *
	 * @var string
	 */
	static private $property_short;
	
	/**
	 * Get server configuration information.
	 *
	 * @param string $mode the mode we're running in
	 * @param string $type the type of connection to retrieve
	 * @param string $property_short the property short of the company to get
	 * @return array
	 */
	public static function Get_Server($mode, $type, $property_short = NULL)
	{

		// set our variables
		self::$mode = strtoupper($mode);
		self::$type = strtoupper($type);
		self::$property_short = Enterprise_Data::resolveAlias($property_short);

		switch (self::$type)
		{

			case "BLACKBOX":
			case "EVENT_LOG":
			case "TRENDEX_LOG":
			case "BLACKBOX_STATS":
				$server = self::Get_Blackbox();
				break;

			case "REACT":
				$server = self::Get_React();
				break;

			case "SITE_TYPES":
				$server = self::Get_Site_Types();
				break;

			case "MYSQL":
				$server = self::Get_MySQL();
				break;

			case "STATS":
				$server = self::Get_Stats();
				break;

			case "CCS_MYSQLI":
				$server = self::Get_CCS('mysqli');
				break;

			case "CONDOR":
				$server = self::Get_Condor();
				break;

			case "MANAGEMENT":
				$server = self::Get_Management();
				break;
			
			case 'OCS':
				$server = self::Get_OCS();
				break;
				
			case 'FRAUD':
				$server = self::getFraudDB();
				break;
				
			case 'OCP':
				$server = self::Get_OCP();
				break;
		}

		return $server;
	}
	
	/**
	 * Returns the OCP database information.
	 * 
	 * This is apparently only used by the application checker on Jubilee and should
	 * be moved to it's own DBInfo file.
	 *
	 * @param string $db_type the DB type to use
	 * @return array
	 */
	private static function Get_OCP( $db_type = null )
	{
		switch( self::$mode )
		{
			case 'RC':
			case 'RC_READONLY':
				$server = array(
					'host' => 'db101.ept.tss',
					'user' => 'ocp',
					'db' => 'ocp',
					'port' => '3322',
					'password' => 'password',
				);
				break;
			
			case 'LIVE':
			case 'SLAVE':
				$server = array(
					'host' => 'writer.cc.ept.tss',
					'user' => 'ocp',
					'db' => 'ocp',
					'port' => '3306',
					'password' => 'password',
				);
				break;
			
			case 'LIVE_READONLY':
				$server = array(
					'host' => 'reader.cc.ept.tss',
					'user' => 'ocp',
					'db' => 'ocp',
					'port' => '3306',
					'password' => 'password',
				);
				break;
			
			case 'LOCAL':
			default:
				$server = array(
					'host' => 'monster.tss',
					'user' => 'ocp',
					'db' => 'ocp',
					'port' => '3322',
					'password' => 'password',
				);
				break;
		}
		
		$server["db_type"] = (!$db_type) ?  "mysql" : $db_type;
		
		return $server;
	}

	/**
	 * The highly inappropriately named function to return an array of eCash database information.
	 *
	 * @return array
	 */
	private static function Get_MySQL()
	{
		return DBInfo_Enterprise::getDBInfo(self::$property_short, self::$mode);
	}

	/**
	 * Retrieves the database information for the olp_site_type database.
	 *
	 * @return array
	 */
	private static function Get_Site_Types()
	{
		// We're really just connecting to OLP and using a different database name
		$server = DBInfo_OLP::getDBInfo(self::$mode);
		
		switch (self::$mode)
		{
			case 'RC':
				$server['db'] = 'rc_olp_site_types';
				break;
			default:
				$server['db'] = 'olp_site_types';
				break;
		}

		return $server;
	}

	/**
	 * The slightly inappropriately named function to get the OLP database connection info.
	 *
	 * @return array
	 */
	private static function Get_Blackbox()
	{
		return DBInfo_OLP::getDBInfo(self::$mode);
	}

	/**
	 * Returns Stats database information.
	 * 
	 * This function returns where we keep the Stats database, which isn't really accurate. I believe
	 * it's actually used to find where we have the Stat_Limits table is, which is in OLP. This is not
	 * to be confused with StatPro.
	 *
	 * @return array
	 */
	private static function Get_Stats()
	{
		return DBInfo_OLP::getDBInfo(self::$mode);
	}

	/**
	 * Returns database connection information for the react database.
	 * 
	 * The database is located on the OLP database, so we're just using this to specify the
	 * database name.
	 *
	 * @return array
	 */
	private static function Get_React()
	{
		// This is WEIRD, but we're leaving it here so we don't break anything
		switch (strtolower(self::$property_short))
		{
			case "ips":
			case "ic":
			case "ic_t1":
			case "ifs":
			case "icf":
			case "ipdl":
				return self::Get_MySQL();
				break;
		}
		
		$server = DBInfo_OLP::getDBInfo(self::$mode);
		$server['db'] = 'react_db';

		return $server;
	}

	/**
	 * Returns the database information for the management database.
	 * 
	 * This really just changes the database name to management, but uses the OLP database
	 * connection information.
	 *
	 * @return unknown
	 */
	private static function Get_Management()
	{
		$server = DBInfo_OLP::getDBInfo(self::$mode);
		$server['db'] = 'management';
		
		return $server;
	}

	/**
	 * Returns the Condor API link to use.
	 * 
	 * This should not be here and should be moved to another location.
	 *
	 * @todo Move this to a more appropriate place.
	 * @return string
	 */
	private static function Get_Condor()
	{
		$condor_user_map = array(
			'generic'  => array('enterprise','password'),
        );
		$user = (empty($condor_user_map[self::$property_short][0])) ? 'impact' : $condor_user_map[self::$property_short][0];
		$pass = (empty($condor_user_map[self::$property_short][1])) ? 'c0nd0r1mpact' : $condor_user_map[self::$property_short][1];
		switch( self::$mode )
		{
			default:
			case "RC":
			case "LOCAL":
				$server = "$user:$pass@rc.condor.4.edataserver.com";
				break;
			case "LIVE":
				$server = "$user:$pass@condor.4.internal.edataserver.com";
				break;
			
			//default:
			//    $server ="$user:$pass@condor.4.edataserver.com.ds70.tss:8080";
			//    break;
		}
		return $server;
	}
	
	/**
	 * Returns the database information for OCS.
	 * 
	 * The OCS database is just located on OLP and so the database name and db_type are the only
	 * thing that change.
	 *
	 * @return array
	 */
	private static function Get_OCS()
	{
		$server = DBInfo_OLP::getDBInfo(self::$mode);
		$server['db'] = 'ocs';
		$server['db_type'] = 'mysqli';
		
		return $server;
	}

	/**
     * Find the LDB Connection info for the database
     * containing Fraud rules.
     *
     * @return array
     */
	private static function getFraudDB()
	{
		switch (strtolower(self::$property_short))
		{
			//All CLK companies use the same FRAUD database
			case 'ufc':
			case 'ucl':
			case 'pcl':
			case 'd1':
			case 'ca':
			case 'clk':
				//We happen to know that CLK Fraud rules
				//are stored in the ufc database so this
				//is the easiest way to get that.
				self::$property_short = 'ufc';
				$server = self::Get_MySQL();
			break;
		}
		return $server;
	}
}
?>