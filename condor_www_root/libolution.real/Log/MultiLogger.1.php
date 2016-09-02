<?php

	/**
	 * A logger which contains other loggers. Primarily used for having a
	 * single entry point for multiple loggers.
	 *
	 * ex:
	 *
	 * <code>
	 * $syslogger = new Log_SysLog_1('myappname');
	 * $screenlogger = new Log_ScreenLogger_1();
	 *
	 * $multilogger = new Log_MultiLogger_1();
	 * $multilogger->addLogger($syslogger);
	 * $multilogger->addLogger($screenlogger);
	 *
	 * // At this point, writing to $multilogger will write to both
	 * // screenlogger and syslogger
	 *
	 * </code>
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class Log_MultiLogger_1 extends Object_1 implements Log_ILog_1
	{
		/**
		 * @var array
		 */
		protected $loggers = array();

		/**
		 * Add a logger to our list of child loggers
		 *
		 * @param Log_ILog_1 $logger
		 */
		public function addLogger(Log_ILog_1 $logger)
		{
			$this->loggers[] = $logger;
		}

		/**
		 * Writes to all children loggers
		 *
		 * @param string $message
		 */
		public function write($message, $log_level = Log_ILog_1::LOG_INFO)
		{
			foreach ($this->loggers as $logger)
			{
				$logger->write($message, $log_level);
			}
		}
	}
?>