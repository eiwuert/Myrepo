<?php

/**
 * Verify same work and cell phone
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_SameWorkCellPhoneRule extends VendorAPI_Blackbox_Rule 
{
	/**
	 * @return string
	 */
	public function getEventName()
	{
		return 'SAME_WC';
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
		return $data->phone_cell != $data->phone_work;
	}
}

?>
