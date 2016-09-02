<?php

	/**
	 * CFE action that assess a fee with a fixed amount
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class ECash_CFE_Action_AssessFeeFixed extends ECash_CFE_Base_ScheduleAction
	{
		public function getType()
		{
			return 'AssessFeeFixed';
		}

		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('amount', ECash_CFE_API_VariableDef::TYPE_NUMBER, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('event_type', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('event_amount_type', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
			);
		}

		public function execute(ECash_CFE_IContext $c)
		{
			$params = $this->evalParameters($c);

			$this->assessFee(
				$c->getAttribute('application_id'),
				$params['event_type'],
				$params['event_amount_type'],
				$params['amount']
			);
		}
		
		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "event_amount_type":
					$amount_type_list = ECash::getFactory()->getModel('EventAmountTypeList');
					$amount_type_list->loadBy(array());
					foreach($amount_type_list as $amount_type) {
						$retval[] = array($amount_type->name_short, $amount_type->name, 0);
					}
					break;
				case "event_type":
					$event_type_list = ECash::getFactory()->getModel('EventTypeList');
					$event_type_list->loadBy(array());
					foreach($event_type_list as $event_type) {
						$retval[] = array($event_type->name_short, $event_type->name, $event_type->company_id);
					}
					break;
			}
			return $retval;
		}
	}

?>
