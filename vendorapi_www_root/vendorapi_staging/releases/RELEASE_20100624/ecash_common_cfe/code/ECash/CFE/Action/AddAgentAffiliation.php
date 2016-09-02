<?php

	/**
	 * An action that creates a comment for an application.
	 *
	 * @author Will! Parker
	 */
	class ECash_CFE_Action_AddAgentAffiliation extends ECash_CFE_Base_BaseAction 
	{
		
		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('agent_affiliation_reason', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('affiliation_area', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('affiliation_type', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
			);
		}
		
		public function getType()
		{
			return 'AddAgentAffiliation';
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
			$normalizer= new Date_Normalizer_1(new Date_BankHolidays_1());
			$date_expiration = $normalizer->advanceBusinessDays(time(), 1);
			$application = ECash::getApplicationById($c->getAttribute('application_id'));
			$affiliations = $application->getAffiliations();
			$affiliations->add(ECash::getAgentById($c->getAttribute('agent_id')),
								$params['affiliation_area'], 
								$params['affiliation_type'],
								$date_expiration
								);
		}

		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "agent_affiliation_reason":
					$aar_list = ECash::getFactory()->getModel('AgentAffiliationReasonList');
					$aar_list->loadBy(array());
					foreach($aar_list as $model)
					{
						$retval[] = array($model->name_short, $model->name, 0);
					}
					break;
				case "affiliation_type":
					$retval = array(
						array('owner', 'owner', 0),
						array('manager', 'manager', 0),
						array('creator', 'creator', 0),
					);
					break;
				case "affiliation_area":
					$retval = array(
						array('collections', 'collections', 0),
						array('conversion', 'conversion', 0),
						array('watch', 'watch', 0),
						array('manual', 'manual', 0),
						array('queue', 'queue', 0),
					);
					break;
			}
			return $retval;
		}

		public function isEcashOnly()
		{
			return true;
		}
	}

?>
