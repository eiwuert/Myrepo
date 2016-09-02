<?php

	/**
	 * base class for those actions that will perform an action on a single queue for a single application
	 *
	 */
	abstract class ECash_CFE_Base_QueueAction extends ECash_CFE_Base_BaseAction
	{
		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('queue', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
			);
		}
		
		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "queue":
					$queue_list = ECash::getFactory()->getModel('QueueList');
					$queue_list->loadBy(array());
					foreach($queue_list as $queue) 
					{
						$retval[] = array($queue->name_short, $queue->name_short, $queue->company_id);
					}
					break;
			}
			return $retval;
		}
	}
?>
