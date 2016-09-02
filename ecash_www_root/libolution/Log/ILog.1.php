<?php

	/**
	 * basic interface that all loggers must implement
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	interface Log_ILog_1
	{
		/**
		 * Output method.
		 *
		 * @param string $message
		 * @param int $log_level
		 */
		public function write($message, $log_level = Log_ILog_1::LOG_WARNING);

		const LOG_EMERGENCY = 0;
		const LOG_ALERT = 1;
		const LOG_CRITICAL = 2;
		const LOG_ERROR = 3;
		const LOG_WARNING = 4;
		const LOG_NOTICE = 5;
		const LOG_INFO = 6;
		const LOG_DEBUG = 7;
	}
?>