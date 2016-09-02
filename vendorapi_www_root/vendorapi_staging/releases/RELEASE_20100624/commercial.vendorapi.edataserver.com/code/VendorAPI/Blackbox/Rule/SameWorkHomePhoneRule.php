<?php

/**
 * Verify same work and home phone numbers
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_SameWorkHomePhoneRule extends VendorAPI_Blackbox_Rule 
{
	/**
	 * @return string
	 */
	public function getEventName()
	{
		return 'SAME_WH';
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
		return $data->phone_home != $data->phone_work;
	}
}

?>
