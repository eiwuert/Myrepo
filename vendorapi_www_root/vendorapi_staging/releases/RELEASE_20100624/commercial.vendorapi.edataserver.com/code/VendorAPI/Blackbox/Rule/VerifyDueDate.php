<?php

/**
 * VerifyDueDate
 *
 */
class VendorAPI_Blackbox_Rule_VerifyDueDate extends VendorAPI_Blackbox_VerifyRule 
{
	/**
	 * Define the action name for this
	 * verify rule
	 *
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log)
	{
		parent::__construct($log);
			$this->addActionToStack('VERIFY_DUE_DATE');
	}

	protected function getEventName()
	{
		return 'VERIFY_DUE_DATE';
	}

	/**
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
		if (!isset($data->date_first_payment))
		{
			return TRUE;
		}

		$date_first_payment = strtotime($data->date_first_payment);
		if (!$date_first_payment)
		{
			return TRUE;
		}

		if (is_array($data->paydates))
		{
			$count = count($data->paydates);
			
			if ($count < 4)
			{
				return TRUE;
			}
			
			for ($i = 0; $i < $count; ++$i)
			{
				if (empty($data->paydates[$i]))
				{
					return TRUE;
				}
				
				if (strtotime($data->paydates[$i]) == $date_first_payment)
				{
					return TRUE;
				}	
			}
			
			return FALSE;
		}

		return TRUE;
	}
}

?>
