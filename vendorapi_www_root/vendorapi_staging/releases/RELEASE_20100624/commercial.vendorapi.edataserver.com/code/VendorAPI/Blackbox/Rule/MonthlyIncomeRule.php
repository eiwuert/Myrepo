<?php

/**
 * Verify the monthly income rule?
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_MonthlyIncomeRule extends VendorAPI_Blackbox_Rule 
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
	}

	/**
	 * @return string
	 */
	public function getEventName()
	{
		return 'MIN_INCOME';
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

	/**
	 * Override the default onInvalid event to add a failure reason.
	 *
	 * @see VendorAPI_Blackbox_Rule_MinimumIncome
	 * @param Blackbox_Data $data Info about the app being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 * @return void
	 */
	public function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);

		/**
		 * It's conceivable that the min income requirements may be different for each company.
		 * I have no idea why there is a Monthly Income and a Min Income, since they both seem
		 * to be the same thing.
		 */
		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_COMPANY);
		}
	}
}
?>