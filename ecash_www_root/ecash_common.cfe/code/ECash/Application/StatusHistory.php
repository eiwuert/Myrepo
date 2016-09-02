<?php


class ECash_Application_StatusHistory extends ECash_Application_Component
{
	private $status_history_list;
	
	public function getStatusHistoryList()
	{
		if(!$this->status_history_list)
		{	
			$this->status_history_list = ECash::getFactory()->getModel('StatusHistoryList');
			$this->status_history_list->loadBy(array("application_id" => $this->application->getId()));
		}
		return $this->status_history_list;
	}
	
	public function getPreviousStatusHistory()
	{
		$status_history = $this->getStatusHistory();
		end($status_history);
		return prev($status_history);
	}
	
	public function setPreviousStatusHistory()
	{
		$application = ECash::getFactory()->getModel('Application');
		$application->application_id = $this->application->getId();
		$application->application_status_id = $this->getPreviousStatusHistory()->getModel()->status_id;
		$application->save();
		unset($this->status_history_list);
		return $application;
	}
}
?>
