<?php
/**
 * Verify rule for same first and last name
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_SameFirstLastNameRule extends VendorAPI_Blackbox_Rule
{
	/**
	 * @return string
	 */
	public function getEventName()
	{
		return 'SAME_FIRST_LAST';
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
		return ((!empty($data->name_first) && !empty($data->name_last)) ? ($data->name_first != $data->name_last) : TRUE);
	}
}
