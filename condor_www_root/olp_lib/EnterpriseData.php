<?php
// TEMPORARY - This shouldn't be needed, but it's currently causing fatal errors without it.
require_once '/virtualhosts/bfw.1.edataserver.com/include/code/SiteConfig.php';

/**
 * Temporary wrapper class to the Enterprise Data class in BFW.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 * @author Matt Piper <matt.piper@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class EnterpriseData
{
	const COMPANY_CLK = 'clk';
	const COMPANY_IMPACT = 'impact';
	const COMPANY_AGEAN = 'agean';
	const COMPANY_GENERIC = 'generic';
	/** Added enterprise company LCS for GForge #9878 [AE] **/
	const COMPANY_LCS = 'lcs';
	/* Added QEASY for GF #10352 */
	const COMPANY_QEASY = 'qeasy';
	
	// Non-OLP enterprise clients
	const COMPANY_FBOD = 'fbod';
	
	/**
	 * A 1:1 mapping of property short aliases in the format
	 * 'ALIAS' => 'PROP'
	 *
	 * @var array
	 */
	private $aliases = array(
		'CBNK1'		=> 'CBNK',
		'IC_T1'		=> 'IC',
		'IC_PST'	=> 'IC',
		'IC_EST'	=> 'IC',
		'IC_CC'		=> 'IC',
		'IC_ND'		=> 'IC',
	);
	
	/**
	 * When an alias campaign successfully sells, we need to switch
	 * the promo on redirect so that the reports will show how
	 * the new campaign is working.
	 *
	 * @var array
	 */
	private $alias_promos = array(
		'CBNK1' => 31141,
		'IC_T1' => 30790,
		'IC_PST'=> 31173,
		'IC_EST'=> 31172,
		'IC_CC'	=> 31191,
		'IC_ND' => 32407,
	);
	
	/**
	 * An array of companies with their properties in the format
	 * 'company' => array('PROP1', 'PROP2')
	 *
	 * @var array
	 */
	private $companies = array(
		'generic'	=> array('GENERIC'),
	);
	
	/**
	 * A 1:1 mapping of sites to property shorts in the format
	 * 'sitename.com' => 'PROP'
	 *
	 * @var unknown_type
	 */
	private $ent_prop_short_list = array (
		'loanservicingcompany.com'      => 'GENERIC',
		'someloancompany.com'		=> 'GENERIC',
	);
	
	/**
	 * A list of enterprise data for all property shorts.
	 *
	 * @var array
	 */
	private $ent_prop_list = array(
		/** eCash Generic **/
		'GENERIC' => array(
			"site_name" => "someloancompany.com", 
			"license" => array (
							'LIVE' => 'somelicensekey',
							'RC' => 'somelicensekey',
							'LOCAL' => 'somelicensekey',
						),
			"legal_entity" => "someloancompany.com",
			"fax" => "877-000-0000",
			"db_type" => "mysql",
			"phone"=>"800-000-0000",
			'use_verify_queue',
			'new_ent' => FALSE,
			'use_soap' => FALSE,
			'property_short' => 'GENERIC',
			'use_cfe' => TRUE,
		),
	);
	
	/**
	 * An instance of this class
	 *
	 * @var EnterpriseData
	 */
	private static $instance = NULL;
	
	/**
	 * Gets an instance of this class.
	 * For internal use only!
	 *
	 * @return EnterpriseData An instance of this class.
	 */
	private static function getInstance()
	{
		if (!(self::$instance instanceof EnterpriseData))
		{
			self::$instance = new EnterpriseData();
		}
		
		return self::$instance;
	}
	
	/**
	 * Get the full Enterprise property list
	 *
	 * @return array
	 */
	public static function getEntPropList()
	{
		return self::getInstance()->ent_prop_list;
	}
	
	/**
	 * Get the full Enterprise property short list
	 *
	 * @return array
	 */
	public static function getEntPropShortList()
	{
		return self::getInstance()->ent_prop_short_list;
	}
	
	
	/**
	 * Check if a site exists in the Enterprise property short list
	 *
	 * @param string $site A full sitename (ex: sitename.com)
	 * @return bool TRUE if site is an enterprise site
	 */
	public static function siteIsEnterprise($site)
	{
		if (empty($site))
		{
			$site = SiteConfig::getInstance()->site_name;
		}
		
		return (empty($site)) ? FALSE : isset(self::getInstance()->ent_prop_short_list[strtolower($site)]);
	}
	
	/**
	 * Determines if the site belongs to the specified company.
	 *
	 * @param string $company The name of the company
	 * @param string $site The site's URL
	 * @return bool TRUE if the site belongs to the company.
	 */
	public static function siteIsCompany($company, $site = NULL)
	{
		$result = FALSE;
		
		if (isset(self::getInstance()->companies[strtolower($company)]))
		{
			if (is_null($site))
			{
				$site = SiteConfig::getInstance()->site_name;
			}
			
			if (!empty($site) && isset(self::getInstance()->ent_prop_short_list[strtolower($site)]))
			{
				$result = self::isCompanyProperty($company, self::getInstance()->ent_prop_short_list[strtolower($site)]);
			}
		}
		
		return $result;
	}
	
	/**
	 * Gets a property short based on the provided site.
	 *
	 * @param string $site A full sitename (ex: sitename.com)
	 * @return string|null The property short found for the given site.
	 */
	public static function getProperty($site)
	{
		$property = NULL;
		
		if (self::siteIsEnterprise($site))
		{
			$property = self::getInstance()->ent_prop_short_list[strtolower($site)];
		}
		
		return $property;
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
		
		if (self::isEnterprise($property))
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
		
		if (isset(self::getInstance()->companies[strtolower($company)]))
		{
			$properties = self::getInstance()->companies[strtolower($company)];
		}
		
		return $properties;
	}
	
	/**
	 * Checks if the provided property belongs to the provided company.
	 * This is provided as an alternative to functions like Is_CLK('ufc').  Instead
	 * you would call this like:
	 * 
	 * Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_CLK, 'ufc')
	 *
	 * @param string $company The name of the company.
	 * @param string $property The property short to use.
	 * 		If not provided, it will default to the config's property_short value
	 * @return bool TRUE if the property belongs to the company.
	 */
	public static function isCompanyProperty($company, $property = NULL)
	{
		$is_property = FALSE;
		
		if (isset(self::getInstance()->companies[strtolower($company)]))
		{
			if (empty($property))
			{
				$property = SiteConfig::getInstance()->property_short;
			}

			$is_property = in_array(self::resolveAlias($property), self::getInstance()->companies[strtolower($company)]);
		}
		
		return $is_property;
	}
	
	/**
	 * Checks if the provided property belongs to the provided company but is not
	 * an alias of a property. This is provided as an alternative to checks like
	 * Is_Impact($property) && $property != 'ic_t1'. Instead you would 
	 * call this like: 
	 * 
	 * Enterprise_Data::isCompanyPropertyNotAlias(Enterprise_Data::COMPANY_IMPACT, $property)
	 *
	 * @param string $company The name of the company.
	 * @param string $property The property short to use.
	 * 		If not provided, it will default to the config's property_short value
	 * @return bool TRUE if the property belongs to the company but is not an alias
	 * 		of one of the company's properties.
	 */
	public static function isCompanyPropertyNotAlias($company, $property = NULL)
	{
		return (self::isCompanyProperty($company, $property) && !self::isAlias($property));
	}
	
	/**
	 * Checks if the provided property is an alias.
	 *
	 * @param string $property the property short
	 * @return bool TRUE if the property is an alias.
	 */
	public static function isAlias($property)
	{
		return isset(self::getInstance()->aliases[strtoupper($property)]);
	}
	
	/**
	 * Determines whether the given property has aliases or not.
	 *
	 * @param string $property the property short
	 * @return bool TRUE if the property has aliases.
	 */
	public static function hasAliases($property)
	{
		return (count(self::getAliases($property)) > 0);
	}
	
	/**
	 * Gets the 'real' property short for the provided alias.
	 * Will default to the passed-in property short if it can't find an alias.
	 *
	 * @param string $property the property short
	 * @return string The property short found for the alias.  This value is always all uppercase.
	 */
	public static function resolveAlias($property)
	{
		if (self::isAlias($property))
		{
			$property = self::getInstance()->aliases[strtoupper($property)];
		}
		
		return strtoupper($property);
	}
	
	/**
	 * Gets all aliases for a given property short.
	 *
	 * @param string $property the property short
	 * @return array A list of property shorts that are aliases for the provided property short.
	 */
	public static function getAliases($property)
	{
		return array_keys(self::getInstance()->aliases, strtoupper($property));
	}
	
	/**
	 * Gets a promo_id to use on redirect to the enterprise site for an Alias.
	 *
	 * @param string $property The property short of the alias.
	 * @return int The promo_id to be used.  NULL if it doesn't exist.
	 */
	public static function getAliasPromo($property)
	{
		$promo = NULL;
		
		if (self::isAlias($property) && !empty(self::getInstance()->alias_promos[strtoupper($property)]))
		{
			$promo = self::getInstance()->alias_promos[strtoupper($property)];
		}
		
		return $promo;
	}
	
	/**
	 * Checks if a property short has an entry in the Enterprise property list.
	 *
	 * @param string $property the property short
	 * @return bool TRUE if an entry is found.
	 */
	public static function isEnterprise($property)
	{
		return isset(self::getInstance()->ent_prop_list[self::resolveAlias($property)]);
	}
	
	/**
	 * Gets all the Enterprise data for a property short.
	 *
	 * @param string $property the property short
	 * @return array An array with all the data (or an empty array if none is found)
	 */
	public static function getEnterpriseData($property)
	{
		$data = array();
		
		if (self::isEnterprise($property))
		{
			$data = self::getInstance()->ent_prop_list[self::resolveAlias($property)];
		}
		
		return $data;
	}
	
	/**
	 * Gets all Enterprise data for all properties associated with the given company.
	 *
	 * @param string $company a string of the company name
	 * @return array
	 */
	public static function getCompanyData($company)
	{
		$properties = self::getCompanyProperties($company);
		
		$data = array();
		foreach ($properties as $property)
		{
			$data[$property] = self::getEnterpriseData($property);
		}
		
		return $data;
	}
	
	/**
	 * Gets a specific option from the Enterprise data for the given property.
	 *
	 * @param string $property the property short
	 * @param string $option an optional parameter to retrieve
	 * @return mixed The data found for the option (or null if none is found).
	 */
	public static function getEnterpriseOption($property, $option)
	{
		$value = NULL;
		
		if (self::isEnterprise($property) && isset(self::getInstance()->ent_prop_list[self::resolveAlias($property)][$option]))
		{
			$value = self::getInstance()->ent_prop_list[self::resolveAlias($property)][$option];
		}
		
		return $value;
	}
	
	/**
	 * Gets the license key for the current mode for the given property short.
	 *
	 * @param string $property a string of the property short
	 * @param string $mode The current process mode (LOCAL/RC/LIVE)
	 * @return string The license key found or null otherwise.
	 */
	public static function getLicenseKey($property, $mode = NULL)
	{
		if (is_null($mode) && defined('BFW_MODE'))
		{
			$mode = BFW_MODE;
		}
		
		$keys = self::getEnterpriseOption($property, 'license');

		return (is_array($keys) && !is_null($mode)) ? $keys[$mode] : NULL;
	}
	
	/**
	 * Returns all Enterprise property shorts.
	 *
	 * @param bool $include_aliases If TRUE, this will also return aliases.
	 * @return array
	 */
	public static function getAllProperties($include_aliases = TRUE)
	{
		$properties = ($include_aliases) ? array_keys(self::getInstance()->aliases) : array();
		
		foreach (self::getInstance()->companies as $props)
		{
			$properties = array_merge($properties, $props);
		}
		
		return array_unique($properties);
	}
	
	/**
	 * Returns if the property short is set to use CFE or not.
	 *
	 * @param string $property Property short to check.
	 * @return boolean
	 */
	public static function isCFE($property)
	{
		$return = FALSE;
		if (self::isEnterprise($property))
		{
			$use_cfe = self::getEnterpriseOption($property, 'use_cfe');
			$return = (is_bool($use_cfe)) ? $use_cfe : FALSE;
		}
		return $return;
	}
}

?>
