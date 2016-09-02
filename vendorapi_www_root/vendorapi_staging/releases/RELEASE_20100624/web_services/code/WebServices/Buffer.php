<?php
/**
 * Web service Buffer
 * 
 * This buffer is used to save calls to web services that will be packaged and sent as one 
 * Soap Call
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * @package WebService
 */
class WebServices_Buffer
{
	/**
	 * Contents of buffer
	 *
	 * @var array
	 */
	protected $buffer;
	/**
	 * applog object for logging errors
	 *
	 * @var Applog
	 */
	protected $log;
	/**
	 * Constructor for the WebService Buffer object
	 *
	 * @param Applog $log
	 * @return void
	 */
	public function __construct($log)
	{
		$this->log = $log;
		$this->buffer = array();
	}
	/**
	 * adds a call to the buffer
	 *
	 * @param string $service_name - name of the web service being called
	 * @param string $function_name - name of the web service function being called
	 * @param array $params - params to be passed
	 * @return void
	 */
	public function addToBuffer($service_name, $function_name, $params)
	{
		$entry = array();
		$entry['service'] = $service_name;
		$entry['function'] = $function_name;
		$entry['args'] = json_encode($params);
		$this->buffer[] = $entry;	
	}
	/**
	 * Returns the contents of the buffer
	 *
	 * @return array
	 */
	public function getBuffer()
	{
		return $this->buffer;
	}
	/**
	 * Returns and clears the buffer
	 *
	 * @return array
	 */
	public function flush()
	{
		//get buffer
		//clear buffer
		$buffer = $this->buffer;
		$this->buffer = array();
		return $buffer;
		
	}

}



?>
