<?php

class ECash_Montioring_Process extends Object_1
{
	
	//static public $last_error;
	private $db;
	private $company_id;
	
	/*
	 *  Create Reques_Lgo object
	 * 	$db = database
	 * 	$company_id = company_id
	 */
	public function __construct($db, $company_id)
	{
		$this->db			= $db;
		$this->company_id 	= $company_id;
	}

	public function setActiveProcessId($process_id)
	{
		$this->process_id = (int)$process_id;
	}

	public function getActiveProcessId()
	{
		return $this->process_id;
	}
		
	public function getProcessById($process_id)
	{
		$process_log = ECash::getFactory()->getModel('ProcessLog');
		$process_log->process_id = $process_id;
		return $process_log;
	}

	/**
	 * Gets information about the last processes run by name.
	 *
	 * @param string $process_name
	 * @param string $business_day Date string or NULL for most recent
	 * @return array
	 */
	public function getProcessListByName($process_name, $business_day = NULL)
	{
		
		$query_args = array("step" => $process_name, "company_id" => $this->company_id);
		if ($business_day !== NULL)
		{
			$query_args['business_day'] = $business_day;
		}

		$process_log = ECash::getFactory()->getModel('ProcessLog');
		$process_log->loadBy($query_args);
		return $process_log;
	}
	
	/**
	 * Gets information about the last process run by name.
	 *
	 * @param string $process_name
	 * @param string $business_day Date string or NULL for most recent
	 * @return array
	 */	
	public function getProcessByName($process_name, $business_day = NULL)
	{
		$process_log = $this->getProcessListByName($process_name, $business_day);
		return end($process_log);
	}
	
	/**
	 * Gets information about the last process run by name and status.
	 *
	 * @param string $process_name
	 * @param string $status
	 * @return array
	 */
	public function getProcessByNameStatus($process_name, $status, $business_day = NULL, $before_business_day = false)
	{
		$process_log_items = array();
		
		$query_args = array("step" => $process_name, "company_id" => $this->company_id, "state" => $status);
		$process_log = ECash::getFactory()->getModel('ProcessLog');
		$process_log->loadBy($query_args);
		
		foreach($process_log as $process_log_item)
		{
			if 	(
					(
						($business_day !== NULL) &&
						(
							($before_business_day && $process_log_item->business_day < $business_day)
							|| 
							($process_log_item->business_day == $business_day)
						)
					)
					||
					($business_day === NULL)
				)			
			{
				$process_log_items[] = $process_log_item;
			}
				
		}
		return $process_log_items;

	}
	

	/**
	 * Start a new process based on information in the constructor.
	 *
	 * @param string $status 'started', 'completed', or 'failed'
	 * @return integer active process id
	 */
	public function startProcess($status = 'started')
	{


		$process_log = ECash::getFactory()->getModel('ProcessLog');
		$process_log->business_day 	= $this->business_day;
		$process_log->company_id 	= $this->company_id;
		$process_log->step 			= $this->process_name;
		$process_log->state			= $this->getSanitizedStatus($status);
		$process_log->date_started	= time();
		$process_log->date_modified	= time();
		$process_log->process_type	= $this->process_type;
		$process_log->save();
		return $process_log;

	}

	/**
	 * Update the active process to a specific status, or start a new one if none active.
	 *
	 * @param string $status 'started', 'completed', or 'failed'
	 * @return integer
	 */
	public function updateProcess($status)
	{
		if (empty($this->process_id))
		{
			return $this->startProcess($status);
		}
		else
		{
			$process_log = ECash::getFactory()->getModel('ProcessLog');
			$process_log->process_log_id 	= $this->process_id;
			$process_log->state				= $this->getSanitizedStatus($status);
			$process_log->date_modified		= time();
			$process_log->save();
			return $process_log;
		}
	}

	/**
	 * Sanitize and check if a given status is allowed.
	 *
	 * @param string $status
	 * @return string
	 */
	protected function getSanitizedStatus($status)
	{
		$status = strtolower(trim($status));

		if (!in_array($status, $this->allowed_status))
		{
			throw new General_Exception('Invalid process status specified: ' . $status);
		}

		return $status;
	}	
}

?>