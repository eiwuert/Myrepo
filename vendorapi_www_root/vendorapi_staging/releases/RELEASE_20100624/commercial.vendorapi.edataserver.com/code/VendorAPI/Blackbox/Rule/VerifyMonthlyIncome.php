<?php

/**
 * Verify the monthly income rule?
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_VerifyMonthlyIncome extends VendorAPI_Blackbox_VerifyRule 
{
	/**
	 * Define the action name for this
	 * verify rule
	 *
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, $amount)
	{
		parent::__construct($log);
		$this->amount = $amount;
		$this->addActionToStack('VERIFY_MIN_INCOME');
	}

	protected function getEventName()
	{
		return 'VERIFY_MIN_INCOME';
	}

	/**
	 * Always run?
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return unknown
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}

	/**
	 * Add a verify rule to the thing
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return ((!empty($data->income_monthly)) ? ($data->income_monthly >= $this->amount) : TRUE);
	}
}

?>