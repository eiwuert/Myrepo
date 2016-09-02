<?php

	/**
	 * An action that creates a comment for an application.
	 *
	 * @author Will! Parker
	 */
	class ECash_CFE_Action_AddFollowUp extends ECash_CFE_Base_BaseAction 
	{
		
		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('comment', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('follow_up_type', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB())
			);
		}
		
		public function getType()
		{
			return 'AddFollowUp';
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

			$follow_up = new Follow_Up();
			$follow_up->Create_Follow_Up(
				$c->getAttribute('application_id'), 
				$params['follow_up_type'], 
				date("Y-m-d H:i:s", (time() + 15 * 60)), 
				$c->getAttribute('agent_id'), 
				$c->getAttribute('company_id'),
				$params['comment'],
				null,
				false
			);
		}

		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "follow_up_type":
					$fut_list = ECash::getFactory()->getModel('FollowUpTypeList');
					$fut_list->loadBy(array());
					foreach($fut_list as $model)
					{
						$retval[] = array($model->name_short, $model->name, 0);
					}
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
