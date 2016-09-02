<?php

	/**
	 * An action that creates a comment for an application.
	 *
	 * @author Will! Parker
	 */
	class ECash_CFE_Action_LoanActionAdd extends ECash_CFE_Base_BaseAction 
	{
		
		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "loan_action":
					$model = ECash::getFactory()->getModel('LoanActionsList');
					$model->loadActiveByType();
					foreach($model as $loan_action) 
					{
						$retval[] = array( 
						$loan_action->loan_action_id,
						$loan_action->description,
						0);
					}
					break;
			}
			return $retval;
		}
		
		
		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('loan_action', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB())
			);
		}
		
		public function getType()
		{
			return 'LoanActionAdd';
		}

		/**
		 * Inserts a comment
		 *
		 * @param CFE_IContext $c
		 */
		public function execute(ECash_CFE_IContext $c)
		{
			// evaluate any expression parameters
			$params = $this->evalParameters($c);
			
			//Create the loan action
			$loan_action_history = new ECash_Models_LoanActionHistory();
			$loan_action_history->loan_action_id = $params['loan_action'];
			$loan_action_history->application_id = $c->getAttribute('application_id');
			$loan_action_history->date_created = date('Y-m-d H:i:s');
			$loan_action_history->application_status_id = $c->getAttribute('application_status_id');
			//Getting Agent_id from the SESSION.  I'm sorry.
			$loan_action_history->agent_id = ECash::getAgent()->getAgentId();
			//Insert the loan_action_history object
			$loan_action_history->insert();

		}
	}

?>
