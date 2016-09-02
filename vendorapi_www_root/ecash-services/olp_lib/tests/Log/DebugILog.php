<?php

/**
 * Class to record logging from the event logging class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLP
 * @subpackage EventBus
 */
class Log_DebugILog implements Log_ILog_1
{
	/**
	 * The collected log statements.
	 *
	 * @var array
	 */
	public $logs = array();
	
	/**
	 * @param string $message 
	 * @param int $log_level 
	 * @see Log_ILog_1::write()
	 */
	public function write($message, $log_level = Log_ILog_1::LOG_WARNING)
	{
		$this->logs[] = $message;
	}
}

?>