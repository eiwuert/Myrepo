<?php

	/**
	 * Class wrapping the PHP syslog functions
	 *
	 * @author Justin Foell <justin.foell@sellingsource.com>
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Log_SysLog_1 extends Object_1 implements Log_ILog_1
	{
		/**
		 * @var string
		 */
		private $program_name;

		/**
		 * @var Log_SysLogMultiplex_1
		 */
		private $syslog_multiplex;

		/**
		 * @param string $program_name
		 * @param int $option
		 */
		public function __construct($program_name, $option = NULL)
		{
			$this->program_name = $program_name;

			$this->syslog_multiplex = Log_SysLogMultiplex_1::getInstance();
			$this->syslog_multiplex->setProgram($program_name, $option, LOG_USER);
		}

		/**
		 * Translates libolution logger log levels to syslog log levels
		 *
		 * @param int $log_level
		 * @return int
		 */
		protected function translateLogLevel($log_level)
		{
			switch ($log_level)
			{
				case Log_ILog_1::LOG_ALERT: return LOG_ALERT;
				case Log_ILog_1::LOG_CRITICAL: return LOG_CRIT;
				case Log_ILog_1::LOG_DEBUG: return LOG_DEBUG;
				case Log_ILog_1::LOG_EMERGENCY: return LOG_EMERG;
				case Log_ILog_1::LOG_ERROR: return LOG_ERR;
				case Log_ILog_1::LOG_INFO: return LOG_INFO;
				case Log_ILog_1::LOG_NOTICE: return LOG_NOTICE;
				case Log_ILog_1::LOG_WARNING: return LOG_WARNING;
				default: return LOG_DEBUG;
			}
		}

		/**
		 * Write a message to the syslog daemon
		 *
		 * @param string $message the message you'd like to log
		 * @param int $log_level
		 */
		public function write($message, $log_level = Log_ILog_1::LOG_WARNING)
		{
			$this->syslog_multiplex->setActiveProgram($this->program_name);
			$priority = $this->translateLogLevel($log_level);
			syslog($priority, $message);
		}
	}

?>
