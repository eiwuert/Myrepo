<?php

/**
 * Logs to a file any log() call it gets, designed to be put in a OLP_LoggingEventBus.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLP
 * @subpackage EventBus
 */
class OLP_EventBus_FileEventLogger implements Log_ILog_1
{
	/**
	 * The file to log to.
	 *
	 * @var string File path.
	 */
	protected $filename;
	
	/**
	 * Create a logger that logs events to a file.
	 * @param string $filename The file to write to.
	 * @return void
	 */
	public function __construct($filename)
	{
		if (!$this->canWriteTo($filename))
		{
			throw new InvalidArgumentException("unable to write to $filename");
		}
		$this->filename = $filename;
		file_put_contents($filename, "------ event bus log -------\n");
	}
	
	/**
	 * @see Log_ILog_1::write()
	 * @param string $string The string to log.
	 * @return void
	 */
	public function write($message, $log_level = Log_ILog_1::LOG_WARNING)
	{
		if (!$this->canWriteTo($this->filename))
		{
			throw new Exception("file {$this->filename} can't be written to.");
		}
		
		file_put_contents($this->filename, date('d/m H:i:s') . ">> $message\n", FILE_APPEND);
	}
	
	/**
	 * Internal function to determine if the location named by $filename can be written to.
	 *
	 * @param string $filename
	 * @return bool
	 */
	protected function canWriteTo($filename)
	{
		return (is_writeable($filename)
			|| (!file_exists($filename) && is_writable(dirname($filename)))
		);
	}
}

?>