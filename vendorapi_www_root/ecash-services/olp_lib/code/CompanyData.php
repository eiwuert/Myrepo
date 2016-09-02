<?php
require_once 'SiteConfig.php';

/**
 * Holds target data grouped into companies.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class CompanyData
{
	const COMPANY_GENERIC	= 'GENERIC';
	
	/**
	 * An array of companies with their properties in the format
	 * 'company' => array('PROP1', 'PROP2')
	 *
	 * @var array
	 */
	protected $companies = array(
		self::COMPANY_GENERIC	=> array('GENERIC'),
	);

	/**
	 * Used by business rule BadCustomer for ZipCash campaigns, and may be used by similar rules.
	 *
	 * @see [#16642] [DY]
	 * @var array
	 */
	protected $vendors = array(
		self::COMPANY_GENERIC => 1,
	);

	/**
	 * An instance of this class
	 *
	 * @var CompanyData
	 */
	protected static $instance = NULL;
	
	/**
	 * Gets an instance of this class.
	 * For internal use only!
	 *
	 * @return CompanyData An instance of this class.
	 */
	protected static function getInstance()
	{
		if (!(self::$instance instanceof CompanyData))
		{
			self::$instance = new CompanyData();
		}
		
		return self::$instance;
	}
	
	/**
	 * Returns whether or not a given property is actually a company.
	 *
	 * @param string $company The company name to check for
	 * @return bool TRUE if the property is a company
	 */
	public static function isCompany($company)
	{
		return isset(self::getInstance()->companies[strtoupper($company)]);
	}
	
	/**
	 * Finds the company for a given property short.
	 *
	 * @param string $property the property short
	 * @return string|null The name of the company found.
	 */
	public static function getCompany($property)
	{
		$company = NULL;
		
		// If we were passed in a company, just pass it back.
		if (self::isCompany($property))
		{
			$company = strtoupper($property);
		}
		else
		{
			$company_list = array_keys(self::getInstance()->companies);
			for ($i = 0; $i < count($company_list) && is_null($company); $i++)
			{
				if (self::isCompanyProperty($company_list[$i], $property))
				{
					$company = $company_list[$i];
				}
			}
		}
		
		return $company;
	}
	
	/**
	 * Gets all the property shorts for a given company.
	 *
	 * @param string $company The name of the company.
	 * @return array An array of property shorts that match the given company.
	 */
	public static function getCompanyProperties($company)
	{
		$properties = array();
		
		if (self::isCompany($company))
		{
			$properties = self::getInstance()->companies[strtoupper($company)];
		}
		
		return $properties;
	}
	
	/**
	 * Checks if the provided property belongs to the provided company.
	 * This is provided as an alternative to functions like Is_CLK('ufc').  Instead
	 * you would call this like:
	 * 
	 * EnterpriseData::isCompanyProperty(EnterpriseData::COMPANY_CLK, 'ufc')
	 *
	 * @param string $company The name of the company.
	 * @param string $property The property short to use.
	 * 		If not provided, it will default to the config's property_short value
	 * @return bool TRUE if the property belongs to the company.
	 */
	public static function isCompanyProperty($company, $property = NULL)
	{
		$is_property = FALSE;
		
		if (self::isCompany($company))
		{
			if (empty($property))
			{
				$property = SiteConfig::getInstance()->property_short;
			}

			$is_property = in_array(strtoupper($property), self::getInstance()->companies[strtoupper($company)]);
		}
		
		return $is_property;
	}

	/**
	 * Get vendor number. Used by business rules related to table `bad_customer`.
	 *
	 * @see [#16642] [DY]
	 * @param string|null $vendor
	 * @return string|null
	 */
	public static function getVendorNumber($vendor)
	{
		$vendor = strtoupper($vendor);
		return key_exists($vendor, self::getInstance()->vendors) ? self::getInstance()->vendors[$vendor] : NULL;
	}
}

?>
