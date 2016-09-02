<?php
/**
 * DBInfo_Enterprise class to get database information for Enterprise/ECash databases
 * 
 * @author Adam L. Englander <adam.englander@sellingsource.com>
 *
 */
class DBInfo_Enterprise
{
	/**
	 * Function returns an arry of datbabase connection information for Enterprise/ECash
	 * databases by property_short and mode
	 *
	 * @param string $property_short property_short of enterprise client
	 * @param string $mode Current application mode (LIVE, RC, LOCAL, etc.)
	 * @return array 
	 */
	public static function getDBInfo($property_short,$mode)
	{
		// IMPACT
		if (EnterpriseData::isCompanyProperty(EnterpriseData::COMPANY_IMPACT, $property_short))
		{
			$db_info = DBInfo_Enterprise_Impact::getDBInfo($property_short, $mode);
		}
		// AGEAN
		elseif (EnterpriseData::isCompanyProperty(EnterpriseData::COMPANY_AGEAN, $property_short))
		{
			$db_info = DBInfo_Enterprise_Agean::getDBInfo($property_short, $mode);
		}
		// AALM
		elseif (EnterpriseData::isCompanyProperty(EnterpriseData::COMPANY_GENERIC, $property_short))
		{
			$db_info = DBInfo_Enterprise_AALM::getDBInfo($property_short, $mode);
		}
		// FBOD
		elseif (EnterpriseData::isCompanyProperty(EnterpriseData::COMPANY_FBOD, $property_short))
		{
			$db_info = DBInfo_Enterprise_FBOD::getDBInfo($property_short, $mode);
		}
		// LCS
		elseif (EnterpriseData::isCompanyProperty(EnterpriseData::COMPANY_LCS, $property_short))
		{
			$db_info = DBInfo_Enterprise_LCS::getDBInfo($property_short, $mode);
		}
		// CLK
		else
		{
			/**
			 * By default, return CLK's information, which will, by default, return UFC's information,
			 * unless otherwise specified by property short.
			 */
			$db_info = DBInfo_Enterprise_CLK::getDBInfo($property_short, $mode);
		}
		
		return $db_info;
		
	}
}
?>