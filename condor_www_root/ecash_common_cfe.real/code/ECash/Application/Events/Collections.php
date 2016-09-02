<?php

class ECash_Application_Events_Collections extends ECash_Application_Events_State
{
	private function getNextStatus()
	{
		//new::collections::customer::*root':
		//'queued::contact::collections::customer::*root':
		//return array('hotfile','verification','applicant','*root');
	}
	
	/*
	 *  Perform Queue Operations
	 */	
	private function performQueueOperations()
	{
		$application_id = $this->application->getModel()->application_id;
		$queue_name = (true) ? "collections_new" : "collections_general";		
		$qi = $this->queue_manager->getQueue($queue_name)->getNewQueueItem($application_id);
		$qi->Priority = (!$this->application->getSchedule()->getAnalyzer()->HasFatalReturns ? 200 : 100);
		$this->queue_manager->moveToQueue($qi, $queue_name);
	}
}
?>