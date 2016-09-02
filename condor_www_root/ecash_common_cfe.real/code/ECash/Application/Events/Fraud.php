<?php

class ECash_Application_Events_Fraud extends ECash_Application_Events_State
{
	private function getNextStatus()
	{
		return array('queued','fraud','applicant','*root');
	}
	
	/*
	 *  Perform Queue Operations
	 */	
	private function performQueueOperations()
	{
		//$this->queue_manager = high_risk
		$application_id = $this->application->getModel()->application_id;
		$qi = $this->queue_manager->getQueue("fraud")->getNewQueueItem($application_id);
		$this->queue_manager->moveToQueue($qi, "fraud");		
	}
}
?>