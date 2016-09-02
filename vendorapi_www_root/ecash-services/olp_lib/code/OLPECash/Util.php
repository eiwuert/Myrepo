<?php

/**
 * Class to handle eCash interactions that don't hinge upong
 * having an application_id handy.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPECash_Util
{
	/**
	 * Returns the company_id for the passed-in property short.
	 * Will return FALSE if no company_id is found.
	 *
	 * @param DB_Database_1 $ldb LDB connection
	 * @param string $property_short Property short for the company.
	 * @return int
	 */
	public static function getCompanyID(DB_Database_1 $ldb, $property_short)
	{
		$query = "SELECT company_id
			FROM company
			WHERE name_short = ?
			AND active_status = 'active'";
			
		return DB_Util_1::querySingleValue($ldb, $query, array($property_short));
	}
	
	/**
	 * Returns a property short for a given company_id.
	 * Will return FALSE if no property short is found.
	 *
	 * @param DB_Database_1 $ldb LDB connection
	 * @param int $company_id Company ID you want a property short for.
	 * @return string
	 */
	public static function getPropertyShort(DB_Database_1 $ldb, $company_id)
	{
		$query = "SELECT name_short
			FROM company
			WHERE company_id = ?
			AND active_status = 'active'";
			
		return DB_Util_1::querySingleValue($ldb, $query, array($company_id));
	}
	
	/**
	 * Returns an agent_id for the given agent name.
	 * Will return FALSE is no agent_id is found.
	 *
	 * @param DB_Database_1 $ldb LDB connection
	 * @param string $agent_name The agent you want an ID for
	 * @return int
	 */
	public static function getAgentID(DB_Database_1 $ldb, $agent_name)
	{
		$query = "SELECT agent_id
				FROM agent
				WHERE login = ?
				AND active_status = 'active'";
				
		return DB_Util_1::querySingleValue($ldb, $query, array($agent_name));
	}
	
	/**
	 * Returns the site_id for a given license key.
	 * Will return FALSE if no site_id is found.
	 *
	 * @param DB_Database_1 $ldb LDB connection
	 * @param string $license_key License key as defined in webadmin1
	 * @return int
	 */
	public static function getSiteID(DB_Database_1 $ldb, $license_key)
	{
		$query = "SELECT site_id
				FROM site
				WHERE license_key = ?";

		return DB_Util_1::querySingleValue($ldb, $query, array($license_key));
	}
	
	/**
	 * Returns the campaign that's stored in eCash's database.
	 *
	 * @param DB_Database_1 $ldb
	 * @param int $application_id
	 * @return string
	 */
	public static function getCampaignName(DB_Database_1 $ldb, $application_id)
	{
		$query = "SELECT campaign_name
				FROM campaign_info
				WHERE application_id = ?
				ORDER BY date_created
				LIMIT 1";
		
		return DB_Util_1::querySingleValue($ldb, $query, array($application_id));
	}
	
	/**
	 * Returns the application's olp_process
	 *
	 * @param DB_Database_1 $ldb
	 * @param int $application_id
	 * @return string
	 */
	public static function getOLPProcess(DB_Database_1 $ldb, $application_id)
	{
		$query = "SELECT olp_process
				FROM application
				WHERE application_id = ?";
		
		return DB_Util_1::querySingleValue($ldb, $query, array($application_id));
	}

	/**
	 * Returns an eCash API object 
	 *
	 * @param string $mode Runtime mode
	 * @param string $property_short Enterprise property short
	 * @return ECash_API
	 */
	public static function getEcashAPI($mode, $property_short)
	{
		/**
		 * Gets an instance of the new ECash_API for inserting apps
		 * @todo This is harded coded to AALM --- FIIIIIX!
		 * @param string $mode
		 * @return ECash_API
		 */
		self::setEcashEnv($property_short);
		$class = EnterpriseData::getEnterpriseOption($property_short, 'ecash_api_class');
		
		//If no company specific class found...use the base eCash API
		$class = (is_null($class)) ? 'ECash_API' : $class;
		
		if (class_exists($class))
		{
			return new $class($mode, $property_short);
		}
		else
		{
			throw new Exception(__CLASS__ . '::' . __METHOD__ . ' - Could not load class ' . $class);
		}
	}
	
	/**
	 * Returns an eCash Config Object based on mode and property short
	 *
	 * @param string $mode
	 * @param string $property_short
	 * @return ECash_Config
	 */
	public static function getEcashConfig($mode, $property_short)
	{
		try 
		{
			$ecash_api = self::getEcashAPI($mode, $property_short);
			$ecash_config = ECash::getConfig();
			return $ecash_config;
		}
		catch (Exception $e)
		{
			throw new Exception('Unable to obtain eCash Config object from property short: '
				. $property_short . ' and mode: ' . $mode . ' due to error: ' . $e->getMessage(),
				$e->getCode()
			);
		}
	}
	
	/**
	 * Sets the OLP environment in order to run eCash libraries
	 *
	 * @param string $property_short
	 * @return void
	 */
	public static function setEcashEnv($property_short)
	{
		$path = EnterpriseData::getEnterpriseOption($property_short, 'ecash_api_path');
		$path = realpath(dirname(ECASH_COMMON_DIR)) . '/' . $path;
		
		if (!is_null($path) && strpos(get_include_path(), $path) === FALSE)
		{
			// Since this code is ran over and over in a loop to sync apps, we dont want
			// to keep adding the same thing to the path every time, so if the $path isnt
			// already in our include path, we need to add it.
			ini_set('include_path', get_include_path() . ":$path");
		}
	}
	
	/**
	 * Time formatter to mimic eCash's formatting for Condor
	 *
	 * @param string $dec
	 * @return string
	 */
	public static function formatEcashTime($dec)
	{
		if (empty($dec))
		{
			return 'Closed';
		}
		if ($dec > 1200)
		{
			return intval((substr(($dec),0,2) - 12)) . ':' . substr(($dec),2)  . 'pm';
		}
		elseif ($dec < 1200)
		{
			return intval((substr(($dec),0,2))) . ':' . substr(($dec),2)  . 'am';
		}
		else
		{
			return intval((substr(($dec),0,2))) . ':' . substr(($dec),2)  . 'pm';
		}
	}
}

?>
