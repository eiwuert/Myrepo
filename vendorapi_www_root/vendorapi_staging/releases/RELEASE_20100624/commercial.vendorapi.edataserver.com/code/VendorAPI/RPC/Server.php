<?php

/**
 * VendorAPI_Loader
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_RPC_Server extends Rpc_Server_1
{
	/**
	 * @var Log_ILog_1
	 */
	protected $log;

	/**
	 * Constructor
	 *
	 * @param string $class Class name of the default service
	 * @param Log_ILog_1 $log The log for RPC errors
	 * @param array $arg Constructor args for the default service
	 * @param bool $process Whether or not to immediately process the RPC call
	 */
	public function __construct($class, Log_ILog_1 $log, $arg = NULL, $process = TRUE)
	{
		$this->log = $log;
		parent::__construct($class, $arg, $process);
	}

	/**
	 * Process a call
	 *
	 * @param Rpc_Call_1 $call
	 * @return array
	 */
	public function processCall(Rpc_Call_1 $call)
	{
		$res = parent::processCall($call);

		foreach ($res as $key=>$data)
		{
			if ($data[0] == Rpc_1::T_THROW)
			{
				$exception = $data[1];
				$this->log->write("RPC Error in {$key}: {$exception->getMessage()}\nTRACE: {$exception->getTraceAsString()}", Log_ILog_1::LOG_CRITICAL);
				if (get_class($exception) !== 'Exception')
				{
					$data[1] = new Exception($exception->getMessage());
					$res[$key] = $data;
				}
			}
		}

		return $res;
	}
}

?>