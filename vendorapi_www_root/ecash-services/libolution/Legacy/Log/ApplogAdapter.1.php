<?php

/**
 * Wraps an Applog to look like a Log_ILog_1.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Legacy_Log_ApplogAdapter_1 implements Log_ILog_1
{
	/**
	 * @var Applog
	 */
	protected $log;

	/**
	 * Wraps an Applog.
	 *
	 * @param Applog $log
	 */
	public function __construct($log)
	{
		$this->log = $log;
	}

	/**
	 * Just passes this over to the Applog.
	 *
	 * @param string $message
	 * @param int $log_level
	 * @return void
	 */
	public function write($message, $log_level = Log_ILog_1::LOG_WARNING)
	{
		$this->log->write($message, $log_level);
	}
}

?>
