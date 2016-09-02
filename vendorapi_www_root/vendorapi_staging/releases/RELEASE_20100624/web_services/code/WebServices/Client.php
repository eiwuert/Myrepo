<?php
/**
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
abstract class WebServices_Client
{
	/**
	 * @var WebService
	 */
	protected $service;

	/**
	 * @var AppLog
	 */
	protected $log;

	/**
	 * @param Applog $log
	 * @param WebService $service
	 */
	public function __construct(Applog $log, WebServices_WebService $service)
	{
		$this->log = $log;
		$this->service = $service;
	}

	/**
	 * Gets the webservice for the client to use
	 *
	 * @return WebService
	 */
	protected function getService()
	{
		return $this->service;
	}

	/**
	 * Parses a client result and returns an array of result objects
	 * 
	 * This helps alleviate having to constantly check
	 * 
	 * @param stdClass $client_result
	 * @return array
	 */
	protected function resultToObjectArray($client_result)
	{
		$result = array();

		if (isset($client_result->item))
		{
			// If a single result is found item will be an object, if multiple it will be an array
			$result = is_array($client_result->item) ? $client_result->item : array($client_result->item);
		}

		return $result;
	}
	
	/**
	 * Log exceptions
	 *
	 * @param string $function - __FUNCTION__ - Function that is throwing the error
	 * @param Exception $e
	 * @return void
	 */
	protected function logException($function, Exception $e)
	{
		$this->log->Write(__CLASS__."::$function failed: " . $e->getMessage());
		$this->log->Write($e->getTraceAsString());
	}
}
