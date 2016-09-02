<?php

	/**
	 * An action that creates a comment for an application.
	 *
	 * @author Will! Parker
	 */
	class ECash_CFE_Action_UpdateApplicationStatus extends ECash_CFE_Base_BaseAction 
	{
		
		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('application_status', ECash_CFE_API_VariableDef::TYPE_NUMBER , ECash::getFactory()->getDB())
			);
		}
		
		
		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "application_status":
					$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
					
					foreach(ECash::getFactory()->getModel('ApplicationStatusList')->getOrderedBy(array()) as $application_status)
					{
						$status_chain = $status_list->toName($application_status->application_status_id);		
						$retval[] = array($application_status->application_status_id, $status_chain,  0);
					}
					break;
			}
			return $retval;
		}
		
		public function getType()
		{
			return 'UpdateApplicationStatus';
		}

		/**
		 * Updates an application's application status
		 *
		 * @param CFE_IContext $c
		 */
		public function execute(ECash_CFE_IContext $c)
		{
			// evaluate any expression parameters
			$params = $this->evalParameters($c);

			$application = ECash::getApplicationById($c->getAttribute('application_id'));
			$application->application_status_id = $params['application_status'];
			$application->update();
			
		}
	}

?>
