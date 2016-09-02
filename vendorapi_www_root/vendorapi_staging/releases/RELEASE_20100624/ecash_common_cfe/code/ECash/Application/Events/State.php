<?php

/*
 *  ECash_Application_Events_State
 * 
 *  Extend this class to change Application State functionality:
 * 		Status Changes
 * 		Stat Operations
 *  	Queue Operations
 */

abstract class ECash_Application_Events_State
{
	/**
	 * @var ECash_Application
	 */
	private $application;
	private $queue_manager;
	private $status_list;
	private $status;
	
	/*
	 * Standard Opertions to perform Application Event State Change 
	 */
	public function applyTo(ECash_Application $a)
	{
		$this->application = $a;
		$this->queue_manager = ECash::getFactory()->getQueueManager();
		$this->status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$this->status = $this->getNextStatus();

		if($this->status != null)
		{
			$this->application->application_status_id = is_numeric($this->status) 
															? $this->status 
															: $this->status_list->toId($this->status);
			$this->application->save();
		}
		
		$this->performStatOperations();
		$this->performQueueOperations();
	}

	/*
	 *  Perform Stat Operations
	 */
	private function performStatOperations()
	{
		
	}
	
	/*
	 *  Perform Queue Operations
	 */	
	private function performQueueOperations()
	{
		
	}

}
?>