<?php
/**
 * Checks to see if a customer is in the military based off of
 * email address and military flag.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_AllowMilitary extends VendorAPI_Blackbox_Rule
{
	/**
	 * Returns whether the rule has sufficient data to run
	 * If the rule can't be run, onSkip() will be called
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return ($data->email || $data->military);
	}
	
	/**
	 * Runs the AllowMilitary rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$is_military_email = preg_match('/.*@.*\.mil$/i',$data->email);

		switch ($this->getRuleValue())
		{
			case 'DENY':
				$valid = (!($is_military_email) && !$data->military);
				break;
			case 'ONLY':
				$valid = ($is_military_email || $data->military);
				break;
			default:
				$valid = TRUE;
				break;
		}
		
		return $valid;
	}

	/**
	 * Runs when the rule returns invalid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);

		/**
		 * Obviously none of the companies allow Military, so this is a hard fail.
		 */
		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_ENTERPRISE);
		}
	}
}

?>
