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
			
		return $ldb->querySingleValue($query, array($property_short));
	}
	
	/**
	 * Returns a property short for a given company_id.
	 * Will return FALSE if no property short is found.
	 *
	 * @param DB_Database_1 $ldb LDB connection
	 * @param int $company_id Company ID you want a property short for.
	 * @return string
	 */
	public function getPropertyShort(DB_Database_1 $ldb, $company_id)
	{
		$query = "SELECT name_short
			FROM company
			WHERE company_id = ?
			AND active_status = 'active'";
			
		return $ldb->querySingleValue($query, array($company_id));
	}
	
	/**
	 * Returns an agent_id for the given agent name.
	 * Will return FALSE is no agent_id is found.
	 *
	 * @param DB_Database_1 $ldb LDB connection
	 * @param string $agent_name The agent you want an ID for
	 * @return int
	 */
	public function getAgentID(DB_Database_1 $ldb, $agent_name)
	{
		$query = "SELECT agent_id
				FROM agent
				WHERE login = ?
				AND active_status = 'active'";
				
		return $ldb->querySingleValue($query, array($agent_name));
	}
	
	/**
	 * Returns the site_id for a given license key.
	 * Will return FALSE if no site_id is found.
	 *
	 * @param DB_Database_1 $ldb LDB connection
	 * @param string $license_key License key as defined in webadmin1
	 * @return int
	 */
	public function getSiteID(DB_Database_1 $ldb, $license_key)
	{
		$query = "SELECT site_id
				FROM site
				WHERE license_key = ?";

		return $ldb->querySingleValue($query, array($license_key));
	}
}

?>