<?php

class ECash_Monitoring_Timer extends ECash_Monitoring_RequestLog
{
	private $start_time;
	public $company_id;
	private $agent_id;
	private $module;
	private $mode;
	private $action;
	private $database;
	private $levels;	
	private $sql_h;
	private $times = array();


	/*
	 *  Global timer
	 */
	public function __construct()
	{
	}
		
	public function start()
	{
		$this->start_time = round(microtime(true), 4);
	}
	

	public function setDatabase($database) 
	{
		$this->database = $database;
	}
	
	public function setRequestInformation($company_id, $agent_id, $module, $mode, $action, $levels) 
	{
		$this->company_id = $company_id;
		$this->agent_id = $agent_id;
		$this->mode = $mode;
		$this->module = $module;
		$this->action = $action;
		$this->levels = implode('|', $levels);
	}

	/*
	 *  Global timer
	 */	
	public function stop()
	{
		$rusage = getrusage();
		$user_time = $rusage['ru_utime.tv_sec'] + ($rusage['ru_utime.tv_usec'] / 1000000);
		$system_time = $rusage['ru_stime.tv_sec'] + ($rusage['ru_stime.tv_usec'] / 1000000);
		
		$values = array(
			'company_id' => $this->company_id,
			'agent_id' => $this->agent_id,
			'module' => $this->module,
			'mode' => $this->mode,
			'action' => $this->action,
			'levels' => $this->levels,
			'start_time' => $this->start_time,
			'stop_time' => round(microtime(true), 4),
			'elapsed_time' => round(microtime(true) - $this->start_time, 4),
			'memory_usage' => memory_get_usage(),
			'user_time' => $user_time,
			'system_time' => $system_time
		);
		
		$result = $this->setRequests($values);
		
		$log_text = "Elapsed time for [Request]  is " . $values['elapsed_time'] . " seconds.";
		if ($this->agent_id) 
		{
			$log_text .= " [agent_id:".$this->agent_id."]";
		}

		if ($result) 
		{
			$log_text .= " [request_log_id:".$result."]";
		} 
		else 
		{
			ECash::getLog()->Write("Could not save request: ".$this->last_error);
		}
		
		ECash::getLog()->Write($log_text);
	}
	

	/*
	 *  Inprocess timer
	 */
	public function startTimer($time_name)
	{
		// set name and time
		$this->times = array_merge($this->times, array($time_name => microtime()));
	}

	/**
	 * Stops the running time, compute the time, and write to the log
	 * @param string $time_name This is a unique name for the time you are calculating.
	 * @return void
	 */
	public function stopTimer($timer_name)
	{

		if (isset($this->times[$timer_name]))
		{
			$stop_time = microtime();

			$elapsed_time = number_format(((substr($stop_time, 0, 9) + substr($stop_time, -10))
								- (substr($this->times[$timer_name], 0, 9))
									- (substr($this->times[$timer_name], -10))), 4);

			$log_text = "Elapsed time for [" . $timer_name . "]  is " . $elapsed_time . " seconds.";

		}
		else
		{
			$log_text = "Elapsed time for [" . $timer_name . "] has no start time.";
		}

		ECash::getLog()->write($log_text, LOG_INFO);
	}	
}
?>