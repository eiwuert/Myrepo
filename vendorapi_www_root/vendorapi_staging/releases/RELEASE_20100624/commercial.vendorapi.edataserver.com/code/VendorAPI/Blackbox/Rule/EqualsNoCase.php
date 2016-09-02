<?php
/**
 * VendorAPI_Blackbox_Rule_EqualsNoCase class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Perform a case-insensitive string comparison.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_EqualsNoCase extends VendorAPI_Blackbox_Rule
{
	/**
	 * Runs the EqualsNoCase rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return (strcasecmp(strval($this->getDataValue($data)), strval($this->getRuleValue())) === 0 ? TRUE : FALSE);
	}
}

?>
