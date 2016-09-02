<?php
/**
 * Web service connection client wrapper
 * 
 * The web service is passed in to service specific objects and is used to seperate them from
 * the specifics of service communication (here currently using the soap client wrapper).
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package WebService
 */
abstract class WebServices_WebService
{
	/**
	 * Soap client wrapper for application service calls
	 *
	 * @var SoapClientWrapper
	 */
	protected $soap_client;

	/**
	 * applog object for logging errors
	 *
	 * @var Applog
	 */
	protected $log;

	/**
	 * Gets the module specific enabled flag
	 * 
	 * @return bool
	 */
	abstract protected function getEnabled();
	
	/**
	 * Gets the module specific reads enabled flag
	 * 
	 * @var string $function
	 * @return bool
	 */
	abstract protected function getReadEnabled();

	/**
	 * Constructor for the WebService object
	 *
	 * @param Applog $log
	 * @param string $url
	 * @param string $user
	 * @param string $pass
	 * @param array $class_map
	 * @return void
	 */
	public function __construct(Applog $log, $url, $user, $pass, $class_map = NULL, $options = NULL)
	{
		$this->log = $log;
		$this->soap_client = $this->createSoapClient($url, $user, $pass, $class_map, $options);
		
	}

	/**
	 * Resolve unhandled function calls to the service to the service client
	 *
	 * @param string $name - name of the function being called
	 * @param array $params - params to be passed
	 * @return mixed
	 */
	public function __call($name, $params)
	{
		$return = call_user_func_array(array(&$this->soap_client, $name), $params);
		return $return;
	}

	/**
	 * Creates the soap client
	 * 
	 * @param string $url
	 * @param string $user
	 * @param string $pass
	 * @param array $class_map
	 * @return SoapClientWrapper
	 */
	protected function createSoapClient($url, $user, $pass, $class_map = NULL, $opt = NULL)
	{
		$options = array(
			"trace"=>TRUE,
			'exceptions' => TRUE,
			'encoding' => 'ISO-8859-1',
			'connection_timeout' => 5,
			'login' => $user,
			'password' => $pass);
		
		if (!is_null($class_map))
		{
			$options["classmap"] = $class_map;
		}
		if(is_array($opt))
		{
			$options = array_merge($options, $opt);
		}

		return new SoapClientWrapper(
			$url,
			$options,
			$this->log
		);
	}

	/**
	 * Returns the previous soap request (if available.)
	 *
	 * @return string
	 */
	public function getLastSoapRequest()
	{
		return $this->soap_client->__getLastRequest();
	}

	/**
	 * Check to make sure the application service is enabled
	 *
	 * @param string $function - __FUNCTION__ - name of the calling function
	 * @return bool - Whether the service is enabled or not
	 */
	public function isEnabled($function)
	{
		$enabled = TRUE;
		if (!$this->getEnabled())
		{
			$enabled = FALSE;
		}

		return $enabled;
	}

	/**
	 * Check to make sure the application service is enabled for inserts
	 *
	 * @param string $function - __FUNCTION__ - name of the calling function
	 * @return bool - Whether the service is enabled or not
	 */
	public function isInsertEnabled($function)
	{
		$enabled = TRUE;
		if (!$this->getEnabledInserts())
		{
			$enabled = FALSE;
		}

		return $enabled;
	}

	/**
	 * Check to make sure that reads are enabled
	 *
	 * @param string $function
	 * @return bool
	 */
	public function isReadEnabled($function)
	{
		$enabled = TRUE;
		if (!$this->isEnabled($function) || !$this->getReadEnabled())
		{
			$enabled = FALSE;
		}

		return $enabled;
	}
}
?>
