<?php

/**
 * Tracks both the total request and any individual calls that a user
 * may want to track inside of the request.
 *
 * @author Brian Ronald <brian.ronald@fitech.com>
 */
class VendorAPI_RequestTimer
{
	/**
	 * The pointer to the log object
	 *
	 * @var <Log_ILog_1>
	 */
	private $log;

	/**
	 * Array of associative arrays containing call information
	 *
	 * @var array
	 */
	private $calls;

	/**
	 * Company short of the current company
	 *
	 * @var string
	 */
	private $company = NULL;

	/**
	 * The application_id assigned by the Application Service
	 *
	 * @var int
	 */
	private $application_id = NULL;

	/**
	 * The application_id presented by OLP
	 *
	 * @var int
	 */
	private $external_id = NULL;

	/**
	 * The current mode
	 *
	 * @var string
	 */
	private $mode = "HTTP";

	public function __construct(Log_ILog_1 $timer_log)
	{
		$this->log = $timer_log;
		$this->calls = array();
	}

	public function setCompany($company)
	{
		$this->company = $company;
	}

	public function setApplicationId($application_id)
	{
		$this->application_id = $application_id;
	}

	public function setExternalId($external_id)
	{
		$this->external_id = $external_id;
	}

	/**
	 * Sets the appropriate mode so the log shows whether the request
	 * was from the CLI or via HTTP
	 *
	 * @param string $mode
	 */
	public function setMode($mode)
	{
		$mode = strtoupper($mode);
		if($mode == 'HTTP' || $mode == 'CLI')
		{
			$this->mode = $mode;
		}
	}

	/**
	 * Start a new request timer for a given call name
	 *
	 * @param string $callName
	 */
	public function start($callName = NULL)
	{
		$start_time = sprintf('%.4F', microtime(TRUE));

		if(empty($callName) || empty($this->calls))
		{
			$this->calls['Overall Request'] = array('start_time' => $start_time, 'end_time' => NULL, 'duration' => NULL);
		}

		if(! empty($callName))
		{
			$this->calls[$callName] = array('start_time' => $start_time, 'end_time' => NULL, 'duration' => NULL);
		}
	}

	/**
	 * Stio the request timer for a given call name
	 *
	 * @param string $callName
	 * @return bool
	 */
	public function stop($callName = NULL)
	{
		if(! empty($callName) && array_key_exists($callName, $this->calls))
		{
			$start_time = $this->calls[$callName]['start_time'];
			$stop_time = sprintf('%.4F', microtime(TRUE));
			$duration = bcsub($stop_time, $start_time, 4);

			$this->calls[$callName]['stop_time'] = $stop_time;
			$this->calls[$callName]['duration'] = $duration;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Writes all of the current request log entries out to the log
	 * interface.  Should be the very last thing we do.
	 */
	public function write()
	{
		if(! empty($this->calls))
		{
			// Close out each call that may be lingering
			foreach($this->calls as $callName => $details)
			{
				if(empty($this->calls[$callName]['stop_time']))
				{
					$this->stop($callName);
				}
				$log_entry = $this->formatLogEntry($callName);
				$this->log->write($log_entry, Log_ILog_1::LOG_INFO);
			}
		}
	}

	/**
	 * Generates a log entry based on the callName
	 * 
	 * @param string $callName
	 * @return string
	 */
	private function formatLogEntry($callName)
	{
		if(! array_key_exists($callName, $this->calls))
				return NULL;

		$timestamp = date('Y-m-d H:i:s');
		$duration = $this->calls[$callName]['duration'];
		$format = "[%s] Mode: %s Company: %s External ID: %d Application ID: %d Call: [%s] [%0.4f]";
		return sprintf($format, $timestamp, $this->mode, $this->company, $this->external_id, $this->application_id, $callName, $duration);
	}
}
?>
