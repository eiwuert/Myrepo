<?php

require_once '../API.php';
require_once '../Models/Queue.php';
require_once '../Models/CurrentQueueStatus.php';

class ECash_API_CFC extends ECash_API
{
	public function __construct()
	{
		parent::__construct();
		$this->addRequiredIndex(array(
			self::INDEX_BUREAU_INQUIRY,
			self::INDEX_DEMOGRAPHICS
			));
	}

	protected function getModelClass($index)
	{
		switch($index)
		{
			case self::INDEX_APPLICATION:
				include_once $this->DIR_MODELS . "CFC{$index}.php";
				return "ECash_Models_CFC{$index}";
				break;
				
			default:
				return parent::getModelClass($index);
				break;
		}
	}
	
	protected function getValidatorClass($index)
	{
		switch($index)
		{
			case self::INDEX_APPLICATION:
				include_once $this->DIR_VALIDATION . "CFC{$index}.php";
				return "ECash_Validation_CFC{$index}";
				break;
				
			default:
				return parent::getValidatorClass($index);
				break;
		}
	}

	protected function saveApp()
	{
		parent::saveApp();
		//put the app in a queue if neccessary
		switch($this->application->getApplicationStatus())
		{
			case 'queued::verification::applicant::*root':
				//put in verification queue
				$this->insertIntoQueue('Verification');
				break;

			default:
				//do not queue
				break;
		}
	}

	private function insertIntoQueue($name)
	{
		//this is a temporary hack, the *new* queue functionality
		//should be implemented, as well as an application decorator
		//so other auxilary things can happen on changes (i.e. stats
		//hit, status change, queue change, etc.) [JustinF]
		$queue = new ECash_Models_Queue();
		$queue->date_created = time();
		$queue->created_by = $this->application->agent_id;
		$queue->date_available = time();
		$queue->queue_name = $name;
		$queue->company_id = $this->application->company_id;
		$queue->key_value = $this->application->application_id;
		//echo 'Would save: Queue ', $name, PHP_EOL;
		$queue->save();
		
		// Yet Another Hack to insert the application into the
		// current_queue_status table which is required for 
		// queue recycling.
		$cqs = new ECash_Models_CurrentQueueStatus();
		$cqs->date_created = date('Y-m-d');
		$cqs->application_id = $this->application->application_id;
		$cqs->queue_name = $name;
		$cqs->cnt = 1; // Recycle Count
		$cqs->save();
	}
	
	
}

?>