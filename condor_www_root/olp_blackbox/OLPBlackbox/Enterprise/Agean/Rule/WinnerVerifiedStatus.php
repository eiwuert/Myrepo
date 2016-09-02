<?php
/**
 * Defines the OLPBlackbox_Enterprise_Agean_Rule_WinnerVerifiedStatus class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * Add verify flags for Agean for winning targets.
 *
 * This rule class, like the other WinnerVerifiedStatus classes, is run
 * during the pickWinner() pipeline as opposed to the isValid() checks done
 * on all rules. Mostly, they do event logging that signifies to the companies
 * who own the winning campaign whether or not the app will need manual verification.
 * While it is technically possible to fail ITargets based on these rules, it's not common.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Agean_Rule_WinnerVerifiedStatus extends OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatus
{
	/**
	 * Decides if the IRule object can run at all.
	 *
	 * @param Blackbox_Data $data Data object containing state related to the application being processed.
	 * @param Blackbox_IStateData $state_data State data related to the ITarget running this rule
	 *
	 * @return bool Whether or not this rule can run.
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return $this->canRunSameWorkAndHomePhoneCheck($data, $state_data)
			&& $this->canRunPaydateProximityCheck($data, $state_data)
			&& $this->canRunIncomeTypeCheck($data, $state_data);
	}

	/**
	 * Check to see if the Rule can run the incomeTypeCheck.
	 *
	 * @param Blackbox_Data $data Data object containing state related to the application being processed.
	 * @param Blackbox_IStateData $state_data State data related to the ITarget running this rule
	 *
	 * @return bool Whether or not this rule can run the incomeTypeCheck method.
	 */
	protected function canRunIncomeTypeCheck($data, $state_data)
	{
		return !empty($data->income_type);
	}

	/**
	 * Actually run the IRule object's main checks.
	 *
	 * @param Blackbox_Data $data Data object containing state related to the application being processed.
	 * @param Blackbox_IStateData $state_data State data related to the ITarget running this rule
	 *
	 * @return bool Whether the rule ran successfully or not.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->sameWorkAndHomePhoneCheck($data, $state_data);
		$this->paydateProximityCheck($data, $state_data);
		$this->incomeTypeCheck($data, $state_data);
		return TRUE;
	}

	/**
	 * Set event log flags based on the income type of the applicant.
	 *
	 * @param Blackbox_Data $data Data object containing state related to the application being processed.
	 * @param Blackbox_IStateData $state_data State data related to the ITarget running this rule
	 *
	 * @return void
	 */
	protected function incomeTypeCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
		if ($this->debugSkip())
		{
			$this->logEvent('AGEAN_TRIGGER_13', OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP);
			$this->logEvent('AGEAN_TRIGGER_14', OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP);
			return;
		}

		if (strcasecmp($data->income_type, 'BENEFITS') === 0)
		{
			// do Agean event logging with the following values
			OLPBlackbox_Enterprise_Agean_Triggers::logTrigger($config->blackbox_mode, 13);
			OLPBlackbox_Enterprise_Agean_Triggers::logTrigger($config->blackbox_mode, 14);
		}
	}
}
?>
