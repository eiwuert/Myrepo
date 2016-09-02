<?php
/**
 * Blackbox_Rule_Required class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Checks to see if a required value exists.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class Blackbox_Rule_Required extends Blackbox_StandardRule
{
	/**
	 * Runs the Required rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 *
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$data_value = $this->getDataValue($data);
		return (!empty($data_value)) ? TRUE : FALSE;
	}
}

?>
