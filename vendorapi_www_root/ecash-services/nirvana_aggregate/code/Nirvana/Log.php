<?php

class Nirvana_Log implements Log_ILog_1 {
	/**
	 * @var Log_ILog_1
	 */
	private $log;

	/**
	 * @var int
	 */
	private $log_level;

	public function __construct(Log_ILog_1 $log, $log_level = Log_ILog_1::LOG_WARNING) {
		$this->log = $log;
		$this->log_level = $log_level;
	}

	public function debug($message) {
		if ($this->enabled(Log_ILog_1::LOG_DEBUG)) {
			$args = func_get_args();
			if (count($args) > 1) {
				$message = call_user_func_array('sprintf', $args);
			} else {
				$message = $args[0];
			}
			$this->log->write($message, Log_ILog_1::LOG_DEBUG);
		}
	}

	public function info($message) {
		if ($this->enabled(Log_ILog_1::LOG_INFO)) {
			$args = func_get_args();
			if (count($args) > 1) {
				$message = call_user_func_array('sprintf', $args);
			} else {
				$message = $args[0];
			}
			$this->log->write($message, Log_ILog_1::LOG_INFO);
		}
	}

	public function warn($message) {
		if ($this->enabled(Log_ILog_1::LOG_WARNING)) {
			$args = func_get_args();
			if (count($args) > 1) {
				$message = call_user_func_array('sprintf', $args);
			} else {
				$message = $args[0];
			}
			$this->log->write($message, Log_ILog_1::LOG_WARNING);
		}
	}

	public function error($message) {
		if ($this->enabled(Log_ILog_1::LOG_ERROR)) {
			$args = func_get_args();
			if (count($args) > 1) {
				$message = call_user_func_array('sprintf', $args);
			} else {
				$message = $args[0];
			}
			$this->log->write($message, Log_ILog_1::LOG_ERROR);
		}
	}

	public function write($message, $log_level = Log_ILog_1::LOG_WARNING) {
		$this->log->write($message, $log_level);
	}

	public function enabled($level) {
		return ($level <= $this->log_level);
	}
}