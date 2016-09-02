<?php

/**
 * Builder object for responses
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_ResponseBuilder 
{
	/**
	 * 
	 * @var VendorAPI_StateObject
	 */
	protected $state;
	
	/**
	 * 
	 * @var VendorAPI_Response::SUCCESS | VendorAPI_Response::ERROR
	 */
	protected $outcome = VendorAPI_Response::SUCCESS;
	
	/**
	 * @var mixed
	 */
	protected $error;
	
	/**
	 * 
	 * @var array
	 */
	protected $results = array();
	
	
	/**
	 * Set the error ?
	 * @return void;
	 */
	public function setError($error)
	{
		$this->error = $error;
		$this->setOutcome(VendorAPI_Response::ERROR);
	}
	
	/**
	 * Sets a result value for this thing.
	 * @param string $key
	 * @param string $val
	 */
	public function addResult($key, $val)
	{
		$this->results[$key] = $val;
	}
	
	/**
	 * Get the results?
	 * @param array $results
	 * @return void
	 */
	public function addResults(array $results)
	{
		$this->results = array_merge($this->results, $results);
	}
	
	/**
	 * Set the state object to use in this response.
	 * @param VendorAPI_StateObject $state
	 * @return void
	 */
	public function setState(VendorAPI_StateObject $state)
	{
		$this->state = $state;
	}

	/**
	 * Sets the outcome of this response
	 * @param int $val
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function setOutcome($val)
	{
		if (in_array($val, array(VendorAPI_Response::SUCCESS, VendorAPI_Response::ERROR), TRUE))
		{
			$this->outcome = $val;	
		}
		else
		{
			throw new InvalidArgumentException("Invalid response outcome.");
		}
	}
	
	/**
	 * Retunr the response object
	 * @return VendorAPI_Response
	 */
	public function getResponse()
	{
		return new VendorAPI_Response($this->state, $this->outcome, $this->results, $this->error);
	}
}