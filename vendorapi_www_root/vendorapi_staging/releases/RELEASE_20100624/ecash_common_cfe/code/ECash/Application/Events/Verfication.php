<?php

class ECash_Application_Events_Verification extends ECash_Application_Events_State
{
	private function getNextStatus()
	{
		return array('queued','verification','applicant','*root');
	}
	
	
	/*
	 *  Perform Queue Operations
	 */	
	private function performQueueOperations()
	{
		//$this->queue_manager = high_risk
		$application = $this->application->getModel();
		$react_status = ("no" == $application->is_react) ? "non_react" : "react" ;	
		$queue_name = "verification_{$react_status}";
		
		$application_id = $application->application_id;
		$qi = $this->queue_manager->getQueue($queue_name)->getNewQueueItem($application_id);
		$this->queue_manager->moveToQueue($qi, $queue_name);		
	}		
}
?>