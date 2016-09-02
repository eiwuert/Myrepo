<?php

class ECash_Application_Events_Underwriting extends ECash_Application_Events_State
{
	private function getNextStatus()
	{
		return array('queued','underwriting','applicant','*root');
	}
	
	/*
	 *  Perform Queue Operations
	 */	
	private function performQueueOperations()
	{
		//$this->queue_manager = high_risk
		$application = $this->application->getModel();
		$react_status = ("no" == $application->is_react) ? "_non_react" : "_react";
		if($react_status == "react" && in_array($application->olp_process, array('online_confirmation')))
		{
			$process_status = '_review';
		}
		else
		{
			$process_status = '';
		}
		$queue_name = "underwriting_{$react_status}{$process_status}";			
		
		$application_id = $application->application_id;
		$qi = $this->queue_manager->getQueue($queue_name)->getNewQueueItem($application_id);
		$this->queue_manager->moveToQueue($qi, $queue_name);		
	}	
}
?>