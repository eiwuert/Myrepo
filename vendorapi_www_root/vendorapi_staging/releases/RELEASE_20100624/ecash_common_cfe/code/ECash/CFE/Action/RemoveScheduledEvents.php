<?php

	/**
	 * CFE action that removes all unregistered events from the schedule
	 *
	 * Use the following SQL to add the action:
	 *
	 * INSERT INTO cfe_action VALUES (NOW(), NOW(), 'active', NULL, 'RemoveScheduledEvents');
	 *
	 * @author Brian Ronald <brian.ronald@sellingsource.com>
	 */
	class ECash_CFE_Action_RemoveScheduledEvents extends ECash_CFE_Base_ScheduleAction
	{
		public function getType()
		{
			return 'RemoveScheduledEvents';
		}

		public function getParameters()
		{
			return array();
		}

		public function execute(ECash_CFE_IContext $c)
		{
			$this->removeScheduledEvents(
				$c->getAttribute('application_id')
			);
		}

		public function getReferenceData($param_name)
		{
			return array();
		}
	}

?>
