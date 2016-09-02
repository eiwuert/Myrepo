<?php

/**
 * A base abstract of the OLP_ECashClient_IDriver that stores the mode and
 * property_short for you. Also handles the getMethodList() if you only
 * have simple methods.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class OLP_ECashClient_Base implements OLP_ECashClient_IDriver
{
	/**
	 * @var string
	 */
	protected $mode;
	
	/**
	 * @var string
	 */
	protected $property_short;
	
	/**
	 * Timeout for the RPC/whatever connection
	 * 
	 * @var int
	 */
	protected $connection_timeout = 22; // Seconds for the RPC timeout
	
	/**
	 * Sets up the variables needed for this class.
	 *
	 * @param string $mode
	 * @param string $property_short
	 */
	public function __construct($mode, $property_short)
	{
		$this->mode = $mode;
		$this->property_short = $property_short;
	}
	
	/**
	 * Gets a simple listing of all methods that this class will handle. The
	 * returned array will just be a listing of method names.
	 *
	 * @return array
	 */
	public function getMethodList()
	{
		$methods = array();
		
		$reflect_class = new ReflectionClass(get_class($this));
		foreach ($reflect_class->getMethods(ReflectionMethod::IS_PUBLIC) AS $method)
		{
			if ($this->isExposedMethod($method))
			{
				$methods[] = $method->getName();
			}
		}
		
		return $methods;
	}
	
	/**
	 * Gets a more verbose listing of all methods that can fully describe the
	 * driver. The returned array will be a listing of methods that contain a
	 * subarray that fully describe each method in human readable form.
	 *
	 * array()
	 *   array()
	 *     name => string
	 *     parameters => string
	 *     comments => string
	 *
	 * @return array
	 */
	public function getVerboseMethodList()
	{
		$verbose_descriptions = array();
		
		$reflect_class = new ReflectionClass(get_class($this));
		foreach ($reflect_class->getMethods(ReflectionMethod::IS_PUBLIC) AS $method)
		{
			if ($this->isExposedMethod($method))
			{
				$parameters = array();
				foreach ($method->getParameters() AS $reflection_parameter)
				{
					$parameters[] = (string)$reflection_parameter;
				}
				$parameters = implode("\n", $parameters);
				
				$verbose_descriptions[] = array(
					'name' => $method->getName(),
					'parameters' => $parameters,
					'comments' => $method->getDocComment(),
				);
			}
		}
		
		return $verbose_descriptions;
	}
	
	/**
	 * Determines if a method is an exposed method for getMethodList().
	 *
	 * @param ReflectionMethod $method
	 * @return bool
	 */
	protected function isExposedMethod(ReflectionMethod $method)
	{
		// Default to TRUE, as it is already public
		$expose = TRUE;
		
		if (substr($method->getName(), 0, 2) == '__')
		{
			// If a magic method , don't let this be exposed.
			$expose = FALSE;
		}
		elseif (preg_match('/@reflection_ignore/i', $method->getDocComment()))
		{
			// If the method comment contain this keyword, then we know that
			// we want to ignore it.
			$expose = FALSE;
		}
		else
		{
			// Ignore all functions from the interface
			$reflection_interface = new ReflectionClass('OLP_ECashClient_IDriver');
			foreach ($reflection_interface->getMethods(ReflectionMethod::IS_PUBLIC) AS $interface_method)
			{
				if ($method->getName() == $interface_method->getName())
				{
					$expose = FALSE;
					break;
				}
			}
		}
		
		return $expose;
	}
	
	/**
	 * Gets the eCash hostname for this property_short and mode.
	 *
	 * @param string $mode Defaults to $this->mode
	 * @param string $property_short Defaults to $this->property_short
	 * @return string
	 */
	protected function getHostName($mode = NULL, $property_short = NULL)
	{
		if ($mode === NULL) $mode = $this->mode;
		if ($property_short === NULL) $property_short = $this->property_short;
		
		$hostname = EnterpriseData::getEnterpriseHostname($property_short, $mode);
		
		return $hostname;
	}
	
	/**
	 * What username should we use?
	 *
	 * @param string $mode
	 * @return string
	 */
	protected function getUsername($mode)
	{
		return 'olp_api';
	}
	
	/**
	 * And password?
	 *
	 * @param string $mode
	 * @return string
	 */
	protected function getPassword($mode)
	{
		return '28eDJsrc';
	}
	
	/**
	 * Returns the URL for the API.
	 *
	 * @return string
	 */
	protected function getURL()
	{
		$mode = $this->getMode();
		
		$hostname = EnterpriseData::getEnterpriseHostname($this->property_short, $mode);
		$url = NULL;
		
		if (!empty($hostname))
		{
			$url = sprintf("http%s://%s/api/%s?company=%s&user=%s&pass=%s",
				($mode === 'LIVE' || $mode === 'STAGING') ? 's' : '', // Always use HTTPS for live servers
				$hostname,
				$this->getURLFilename(),
				strtolower(EnterpriseData::resolveAlias($this->property_short)),
				$this->getUsername($mode),
				$this->getPassword($mode)
			);
		}
		
		return $url;
	}
	
	/**
	 * Determine the real mode we are in.
	 *
	 * @return string
	 */
	protected function getMode()
	{
		$mode = OLP_Environment::getOverrideEnvironment($this->mode);
		
		return strtoupper($mode);
	}
	
	/**
	 * Returns a connection to an API.
	 * 
	 * @return object
	 */
	abstract protected function getAPI();
	
	
	/**
	 * The filename of the API.
	 *
	 * @return string
	 */
	abstract protected function getURLFilename();
}

?>
