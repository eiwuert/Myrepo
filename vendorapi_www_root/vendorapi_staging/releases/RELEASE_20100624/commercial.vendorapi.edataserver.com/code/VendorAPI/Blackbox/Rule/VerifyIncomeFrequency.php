<?php

/**
 * Verify VerifyIncomeFrequency
 *
 */
class VendorAPI_Blackbox_Rule_VerifyIncomeFrequency extends VendorAPI_Blackbox_VerifyRule 
{
	/**
	 * Define the action name for this
	 * verify rule
	 *
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log)
	{
		parent::__construct($log);
			$this->addActionToStack('VERIFY_INCOME_FREQUENCY');
	}

	protected function getEventName()
	{
		return 'VERIFY_INCOME_FREQUENCY';
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
		$income_frequency = strtolower($data->income_frequency);

		if(
			in_array($income_frequency, array("twice_monthly","semi_monthly"))
		)
		{
			if (
				(!($data->day_of_month_1 == 15 && in_array($data->day_of_month_2, array(28,29,30,31,32))))
				&&
				(!($data->day_of_month_1 == 1 && $data->day_of_month_2 ==15))
				&&
				(!($data->day_of_month_1 == 5 && $data->day_of_month_2 ==20))
				&&
				(!($data->day_of_month_1 == 7 && $data->day_of_month_2 ==22))
			)
			{
				return FALSE;
			}
			else
			{
				return TRUE;
			}
		}
		else
		{
			return TRUE;	
		}
	}
}

?>
