<?php
/**
 * Buffered Web service connection client wrapper
 * 
 * The web service is passed in to service specific objects and is used to seperate them from
 * the specifics of service communication (here currently using the soap client wrapper).
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * @package WebService
 */
abstract class WebServices_BufferedWebService extends WebServices_WebService
{
	/**
	 * Soap client wrapper for aggregate application service calls
	 *
	 * @var SoapClientWrapper
	 */
	protected $aggregate_soap_client;

	/**
	 * Determines if aggregate call should be made
	 *
	 * @var boolean
	 */
	protected $aggregate_enabled;

	/**
	 * Name of service
	 *
	 * @var string
	 */
	protected $service_name;

	/**
	 * buffer object
	 *
	 * @var WebServices_Buffer
	 */
	protected $buffer;

	/**
	 * array of functions on a service that can be buffered
	 *
	 * @var array
	 */
	protected $allowed_functions;

	/**
	 * Constructor for the WebService object
	 *
	 * @param Applog $log
	 * @param string $url
	 * @param string $user
	 * @param string $pass
	 * @param string $service_name
	 * @param string $aggregate_url
	 * @param WebServices_Buffer $buffer
	 * @param array $class_map
	 * @return void
	 */
	public function __construct(Applog $log, $url, $user, $pass, $service_name, $aggregate_url, WebServices_Buffer $buffer, $class_map = NULL, $options = NULL)
	{
		$this->log = $log;
		$this->soap_client = $this->createSoapClient($url, $user, $pass, $class_map, $options);
		$this->aggregate_soap_client = $this->createSoapClient($aggregate_url, $user, $pass);
		$this->aggregate_enabled = FALSE;
		$this->service_name = $service_name;
		$this->buffer = $buffer;
		$this->allowed_functions = array();
		$this->allowed_functions['inquiry'] = array("recordInquiry", "recordSkipTrace");
		$this->allowed_functions['application'] = array("updateApplicationStatus", 
								"updateApplicant", "updateApplicationBankInfo", 
								"updatePaydateInfo", "updateEmploymentInfo", 
								"updateApplication", "updateContactInfo");
		
	}
	/**
	 * Resolve unhandled function calls to the service to the service client
	 *
	 * @param boolean $enabled - if aggregate service calls are enabled
	 * @return void
	 */
	public function setAggregateEnabled($enabled)
	{
		$this->aggregate_enabled = $enabled;
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
		if ($this->aggregate_enabled && !empty($this->allowed_functions[$this->service_name]) 
			&& in_array($name, $this->allowed_functions[$this->service_name]))
		{
			$this->buffer->addToBuffer($this->service_name, $name, $params);
			return TRUE;
		}
		else
		{
			return call_user_func_array(array(&$this->soap_client, $name), $params);
		}
	}

	/**
	 * Performs the call to the underlying service, clearing all buffered calls
	 *
	 * @return mixed
	 */	
	public function flush()
	{
		$buffer = $this->buffer->flush();
		if (!empty($buffer))
		{		

			return	$this->aggregate_soap_client->AggregateCall($buffer);
		}
		else
		{
			return FALSE;
		}
	}


}
?>
