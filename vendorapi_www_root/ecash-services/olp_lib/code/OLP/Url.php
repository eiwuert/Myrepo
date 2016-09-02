<?php

require_once('mode_test.php');

/**
 * Class to build a url based on mode.
 * Accepts a site name and an array of parameters to build a query string
 *
 * @author David Watkins <david.watkins@sellingsource.com>
 */
class OLP_Url
{
	/**
	 * Server mode (local, rc, live, etc...)
	 *
	 * @var string
	 */
	protected $mode;
	
	/**
	 * Constructor
	 *
	 * @param string $mode - Server mode (local, rc, live, etc...)
	 */
	public function __construct($mode)
	{
		$this->mode = strtolower(OLP_Environment::getOverrideEnvironment($mode));
	}
	
	/**
	 * Builds the url for the site
	 *
	 * @param string $site - site name
	 * @param array $params - parameters to build a query string
	 * @return string
	 */
	public function buildUrl($site, array $params = array())
	{
		$http = $this->buildHttp();
		$site = preg_replace('!^http(s)?://!i', '', $this->buildSite($site));
		$query_string = $this->buildQueryString($params);
		
		return $http.$site.$query_string;
	}
	
	/**
	 * Build http prefix. Checks if using http or https
	 *
	 * @return string
	 */
	public function buildHttp()
	{
		return $_SERVER['HTTPS'] ? 'https://' : 'http://';
	}
	
	/**
	 * Build a site name depending on server mode
	 *
	 * @param string $site - site name
	 * @return string
	 */
	public function buildSite($site)
	{
		if (preg_match('/^(www\.)?[\w-]+\.[a-z]+$/i', $site))
		{
			switch ($this->mode)
			{
				case 'local':
					$prefix = EnterpriseData::siteIsEnterprise($site) ? 'ent.' : 'sites.';
					$site = $prefix.$site.'.'.Mode_Test::Get_Local_Machine_Name().'.tss';
					break;
				case 'live':
					// Doesn't need to add a prefix
					break;
				default:
					$prefix = OLP_Environment::getDomainPrefix();
					if (empty($prefix)) $prefix = $this->mode.'.';
					$site = $prefix.$site;
					break;
			}
		}
		
		return $site;
	}
	
	/**
	 * Build a query string.
	 * Prepends '/?' automatically if $params contains values
	 *
	 * @param array $params
	 * @return string
	 */
	public function buildQueryString(array $params)
	{
		return empty($params) ? '' : '/?'.http_build_query($params);
	}
}
