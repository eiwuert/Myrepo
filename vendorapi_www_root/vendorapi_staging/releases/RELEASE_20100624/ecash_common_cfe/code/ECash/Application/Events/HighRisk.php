<?php

class ECash_Application_Events_HighRisk extends ECash_Application_Events_State
{
	private function getNextStatus()
	{
		return array('queued','high_risk','applicant','*root');
	}
	
	/*
	 *  Perform Queue Operations
	 */	
	private function performQueueOperations()
	{
		//$this->queue_manager = high_risk
		$application_id = $this->application->getModel()->application_id;
		$qi = $this->queue_manager->getQueue("high_risk")->getNewQueueItem($application_id);
		$this->queue_manager->moveToQueue($qi, "high_risk");		
	}
}
?>