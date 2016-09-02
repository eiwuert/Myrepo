<?php

/**
 * Vendor API service object
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_EnterpriseService
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
	 * @param VendorAPI_IDriver $driver
	 */
	public function __construct(VendorAPI_IDriver $driver, VendorAPI_CallContext $call_context)
	{
		$this->driver = $driver;
		$this->call_context = $call_context;
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
		try
		{
			$action = $this->driver->getAction(ucfirst($name));

			if (!$action instanceof VendorAPI_IEnterpriseAction)
			{
				throw new RuntimeException("Requested action is not a valid enterprise action.");
			}

			$action->setCallContext($this->call_context);

			$response = call_user_func_array(array($action, 'execute'), $arguments);
			/* @var $response VendorAPI_Response */
			return $response->getResult();
		}
		catch (Exception $e)
		{
			$this->driver->getLog()->write(
				sprintf(
					"%s thrown, Message: %s, File: %s, Line: %u Trace Below",
					get_class($e),
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				),
				Log_ILog_1::LOG_CRITICAL
			);

			foreach (explode("\n", $e->getTraceAsString()) as $traceLine)
			{
				$this->driver->getLog()->write($traceLine);
			}
			throw $e;
		}
	}
}
