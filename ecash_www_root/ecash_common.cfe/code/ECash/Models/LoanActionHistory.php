<?php

	require_once 'WritableModel.php';
	require_once 'IApplicationFriend.php';
	require_once 'LoanActions.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_LoanActionHistory extends ECash_Models_ObservableWritableModel implements ECash_Models_IApplicationFriend
	{
		public $LoanAction;
		public $Application;
		public $ApplicationStatus;
		public $Agent;
		public function getColumns()
		{
			static $columns = array(
				'loan_action_history_id', 'loan_action_id',
				'application_id', 'date_created', 'application_status_id',
				'agent_id', 'loan_action_section_id','is_resolved'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('loan_action_history_id');
		}
		public function getAutoIncrement()
		{
			return 'loan_action_history_id';
		}
		public function getTableName()
		{
			return 'loan_action_history';
		}
		
		public function setApplicationData(ECash_Models_Application $application)
		{
			$this->application_id = $application->application_id;
			if (is_numeric($application->agent_id))
			{
				$this->agent_id = $application->agent_id;
			}
			elseif (is_numeric($application->modifying_agent_id))
			{
				$this->agent_id = $application->modifying_agent_id;
			}
			$this->application_status_id = $application->application_status_id;
		}

		public function setLoanAction($name)
		{
			$this->LoanAction = ECash::getFactory()->getModel('LoanActions');
			$this->LoanAction->loadBy(array('name_short' => $name));
			$this->loan_action_id = $this->LoanAction->loan_action_id;		
		}		
		
	}
?>
