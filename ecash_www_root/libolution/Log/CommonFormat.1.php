<?php

/**
 * A Super Logger Thing that implements filter, labels, and date/log level
 * insertion into the message to have a common output for logs.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Log_CommonFormat_1 implements Log_ILog_1
{
	const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * @var Log_ILog_1
	 */
	protected $log;

	/**
	 * @var int
	 */
	protected $filter_level;

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var string
	 */
	protected $date_format;

	/**
	 * Defines the logging options.
	 *
	 * @param Log_ILog_1 $log
	 * @param int $filter_level
	 * @param string $label
	 * @param string $date_format
	 */
	public function __construct(Log_ILog_1 $log, $filter_level =  Log_ILog_1::LOG_WARNING,  $label = '', $date_format = self::DEFAULT_DATE_FORMAT)
	{
		$this->log = $log;
		$this->filter_level = $filter_level;
		$this->label = $label;
		$this->date_format = $date_format;
	}

	/**
	 * Deteremines if we should filter this log message. If not, add in a
	 * common header to the message and pass it along.
	 *
	 * @param string $message
	 * @param int $log_level
	 * @return void
	 */
	public function write($message, $log_level = Log_ILog_1::LOG_WARNING)
	{
		if ($log_level <= $this->filter_level)
		{
			$this->log->write(
				sprintf("[%s][%d]%s %s",
					date($this->date_format),
					$log_level,
					$this->label ? "[{$this->label}]" : '',
					$message
				),
				$log_level
			);
		}
	}
}

?>
