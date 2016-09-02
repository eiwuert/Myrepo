<?php

/**
 * Vendor API service object
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Service
{
	/**
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 * @var VendorAPI_CallContext
	 */
	protected $call_context;

	/**
	 * @var Log_ILog_1
	 */
	protected $request_log;

	/**
	 * @param VendorAPI_IDriver $driver
	 */
	public function __construct(VendorAPI_IDriver $driver, VendorAPI_CallContext $call_context, Log_ILog_1 $request_log = null)
	{
		$this->driver = $driver;
		$this->call_context = $call_context;
		$this->request_log = $request_log;
	}

	/**
	 * Default handler for action calls.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		$id = md5(microtime().getmypid());
		$this->logRequest($id, $name, $arguments);

		try
		{
			$action = $this->driver->getAction(ucfirst($name));
			$action->setCallContext($this->call_context);
			$response = call_user_func_array(array($action, 'execute'), $arguments);
            
			$real_response = $response->toArray();
			$this->driver->getTimer()->write();
		}
		catch (Exception $e)
		{
			// return a response with the exception if we're not in live mode
		//	if (strcasecmp($this->driver->getMode(), VendorAPI_BasicDriver::MODE_LIVE) != 0)
		//	{
				$response = new VendorAPI_Response(new VendorAPI_StateObject(), FALSE, array(
					'exception' => array(
						'message' => $e->getMessage(),
						'trace' => $e->getTraceAsString()
					)
				));
				$real_response = $response->toArray();
		//	}

			$this->driver->getLog()->write(
				sprintf(
					'[%s:%s] %s thrown, Message: %s, File: %s, Line: %u',
					$this->call_context->getCompany(),
					$this->call_context->getApplicationId(),
					get_class($e),
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				),
				Log_ILog_1::LOG_CRITICAL
			);

			$this->driver->getTimer()->stop();
		}

		$this->logResponse($id, $name, $real_response);
		return $real_response;
	}

	private function logRequest($id, $name, array $args)
	{
		if ($this->request_log) {
			$company_id = $this->driver->getCompanyId();
			$this->request_log->write("[".date("Y-m-d H:i:s")."][{$id}][company:{$company_id}] REQUEST {$name} ".serialize($args), Log_ILog_1::LOG_DEBUG);
		}
	}

	private function logResponse($id, $name, array $response)
	{
		if ($this->request_log) {
			unset($response['state_object']);
			$company_id = $this->driver->getCompanyId();
			$this->request_log->write("[".date("Y-m-d H:i:s")."][{$id}][company:{$company_id}] RESPONSE {$name} ".serialize($response), Log_ILog_1::LOG_DEBUG);
		}
	}
}
