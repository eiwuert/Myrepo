<?php
/**
 * Defines the class OLPBlackbox_Rule_WinnerVerifiedStatus.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * Post pickWinner rule that always "passes" as a check, but records information for queueing later.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatus extends OLPBlackbox_Rule implements OLPBlackbox_IWinnerRule
{
	/**
	 * Event logging function.
	 *
	 * @param string $name title of whatever event fired
	 * @param string $result what happened that caused the log to fire
	 * @param string $target the name of the ITarget (property_short)
	 *
	 * @return void
	 */
	protected function logEvent($name, $result, $target = NULL)
	{
		$config = $this->getConfig();
		$config->event_log->Log_Event(
			$name, $result, $target, NULL, $config->blackbox_mode
		);
	}

	/**
	 * Determines if the current mode/data allows this rule to run.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether or not this rule object should run.
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return $this->canRunPaydateProximityCheck($data, $state_data)
			&& $this->canRunVerifyIncomeCheck($data, $state_data);
	}

	/**
	 * Determines whether or not the income verifying method of this Rule can be run.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether the test can run
	 */
	protected function canRunVerifyIncomeCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();

		return ($config->blackbox_mode !== OLPBlackbox_Config::MODE_CONFIRMATION
				&& $config->blackbox_mode !== OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION)
				|| !empty($data->income_monthly_net);
	}

	/**
	 * Whether or not to enter "debug_skip" instead of running.
	 *
	 * @return bool TRUE if "debug_skip" should be logged, FALSE if the rule should run
	 */
	protected function debugSkip()
	{
		$debug = $this->getConfig()->debug;
		return $debug->debugSkipRule();
	}

	/**
	 * Determines whether or not payday proximity verification method can run for this Rule.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether the test can run
	 */
	protected function canRunPaydateProximityCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();

		return ($config->blackbox_mode !== OLPBlackbox_Config::MODE_CONFIRMATION
				&& $config->blackbox_mode !== OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION)
				|| (is_array($data->paydates) || $data->paydates instanceof Traversable);
	}

	/**
	 * Determines if the fraud check can be run for this rule.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool Whether or not we can run this check.
	 */
	protected function canRunFraudCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// /virtualhosts/bfw.1.edataserver.com/include/modules/blackbox/blackbox.target.php line 1406
		// "Since CLK is the only one that can run fraud right now"
		// We are CLK, we can run.
		return TRUE;
	}

	/**
	 * Whether or not the Rule can run it's phone verification method.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool Whether or not the method can be run.
	 */
	protected function canRunSameWorkAndHomePhoneCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();

		return ($config->blackbox_mode !== OLPBlackbox_Config::MODE_CONFIRMATION
				&& $config->blackbox_mode !== OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION)
				|| (!empty($data->phone_home) && !empty($data->phone_work));
	}

	/**
	 * Actually run the rule, returning whether the rule passes and as a side effect doing logging/state updating.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @throws Blackbox_Exception if something goes wrong in the rule.
	 *
	 * @return bool whether the rule ran successfully or not.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->verifyIncomeCheck($data, $state_data);
		$this->paydateProximityCheck($data, $state_data);
		return TRUE;
	}

	/**
	 * Check the income of the application and optionally flag for manual verification of the loan.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return void
	 */
	protected function verifyIncomeCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_name = 'VERIFY_MIN_INCOME';

		if ($this->debugSkip())
		{
			$this->logEvent(
				$event_name, 
				OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, 
				$state_data->name
			);
			return;
		}

		$status = '';
		if ($data->income_monthly_net < 1300)
		{
			$status = 'VERIFY';		// eCash vendor will have to verify this app.
		}
		else
		{
			$status = 'VERIFIED';	// eCash will not have to verify based on income.
		}

		$this->logEvent($event_name, $status, $state_data->name);
	}

	/**
	 * Checks to see if the applicant's pay dates are too close together and need manual verification.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return void
	 */
	protected function paydateProximityCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_name = 'VERIFY_PAYDATES';

		if ($this->debugSkip())
		{
			$this->logEvent(
				$event_name, 
				OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, 
				$state_data->name
			);
			return;
		}

		// assume it's fine
		$status = 'VERIFIED';

		$dates = array_values($data->paydates);
		$date_count = count($dates);
		for ($i=0; $i<$date_count; ++$i)
		{
			if (!empty($dates[$i+1]) && (strtotime($dates[$i+1]) < strtotime('+5 days', $dates[$i])))
			{
				$status = 'VERIFY';		// paydates within 5 days of each other must be verified
				break;
			}
		}
		$this->logEvent($event_name, $status, $state_data->name);
	}

	/**
	 * Runs a fraud check and records whether or not CLK will need to look into the application.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return void
	 */
	protected function fraudCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_name = 'FRAUD_CHECK';

		if ($this->debugSkip())
		{
			$this->logEvent(
				$event_name, 
				OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, 
				$state_data->name
			);
			return;
		}

		if ($this->fraudCheckOLP($data, $state_data) > 0)
		{
			$status = 'VERIFY';
		}
		else
		{
			$status = 'PASS';
		}
		$this->logEvent($event_name, $status, $state_data->name);
	}

	/**
	 * Internal function which calls out to legacy OLP code to check for application fraud.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return int number of 'violations'
	 */
	protected function fraudCheckOLP(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->debugSkip())
		{
			$this->logEvent(
				'FRAUD_CHECK_OLP', 
				OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, 
				$state_data->name
			);
			return;
		}

		$fraud = new OLPFraud(BFW_MODE, $state_data->target_name);
		$ecash_app = OLPFraud::buildECashAppFromArray($data, $data->application_id);
		return $fraud->runFraudRules($ecash_app);
	}

	/**
	 * Checks to see if the applicant has put the same number for home and work and logs if needed.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return void
	 */
	protected function sameWorkAndHomePhoneCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_name = 'VERIFY_SAME_WH';

		if ($this->debugSkip())
		{
			$this->logEvent($event_name, OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, $state_data->name);
		}
		else
		{
			$this->logEvent($event_name, ($data->phone_home == $data->phone_work ? 'VERIFY' : 'VERIFIED'), $state_data->name);
		}
	}

	/**
	 * Gets the DataX response regarding phone number validation (see: perf-l3, idv-l3)
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return string XML DataX response.
	 */
	protected function getDataXResponse(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();

		$auth = new Authentication($config->olp_db, $config->olp_db->db_info['db'], $config->applog);
		return reset($auth->Get_Records($data->application_id, Authentication::DATAX_PERF));
	}
}
?>
