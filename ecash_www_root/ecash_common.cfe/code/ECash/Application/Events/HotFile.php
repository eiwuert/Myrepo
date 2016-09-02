<?php

class ECash_Application_Events_HotFile extends ECash_Application_Events_State
{
	private function getNextStatus()
	{
		return array('hotfile','verification','applicant','*root');
	}
	
	/*
	 *  Perform Queue Operations
	 */	
	private function performQueueOperations()
	{
		//$this->queue_manager = high_risk
		$application_id = $this->application->getModel()->application_id;
		$qi = $this->queue_manager->getQueue("hotfile")->getNewQueueItem($application_id);
		$this->queue_manager->moveToQueue($qi, "hotfile");		
	}
}
?>