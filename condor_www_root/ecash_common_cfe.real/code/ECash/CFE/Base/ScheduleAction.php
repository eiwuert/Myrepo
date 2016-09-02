<?php

	/**
	 * Provides some helper functions for actions that involve the schedule
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	abstract class ECash_CFE_Base_ScheduleAction extends ECash_CFE_Base_BaseAction
	{
		protected function assessFee($app_id, $event_type, $event_amount_type, $amount)
		{
			// @todo probably remove this gay logic
			//$ea_type = ($type != 'assess_service_chg') ? 'fee' : 'service_charge';

			// @todo dates??
			$event = Schedule_Event::MakeEvent(
				time(),
				time(),
				// single event_amount entry for the fee
				array(
					Event_Amount::MakeEventAmount($event_amount_type, $amount),
				),
				$event_type,
				''
			);

			Record_Event($app_id, $event);
		}

		protected function schedulePrincipalPayment($app_id, $amount, $date = NULL)
		{
			// @todo dates??
			$event = Schedule_Event::MakeEvent(
				$sc_date,
				$sc_date,
				array(
					Event_Amount::MakeEventAmount('principal', $amount),
				),
				$type,
				''
			);

			Record_Event($app_id, $e);
		}
		
		public function isEcashOnly()
		{
			return true;
		}
	}

?>