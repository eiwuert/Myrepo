<?php
/**
 * Blackbox_Rule_Compare class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Checks that multiple array values match.
 * All Values Match: TRUE
 * No Values Match: FALSE
 * Some Values Match: FALSE
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class Blackbox_Rule_Compare extends Blackbox_StandardRule
{
	/**
	 * Runs the Compare rule. Note this rule does not pay any attention to
	 * $this->getRuleValue, since its only intent is to compare multiple
	 * Blackbox_Data elements.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 *
	 * @return bool TRUE if the values match.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!is_array($this->getDataValue($data)) || count($this->getDataValue($data)) < 2)
		{
			throw new Blackbox_Exception('Data type mismatch: rule data should be an array.');
		}

		$data_keys = $this->getDataValue($data);

		return (count(array_unique($data_keys)) == 1);
	}
}

?>
