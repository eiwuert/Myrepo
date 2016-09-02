<?php
/**
 * Blackbox_Rule_NotCompare class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Checks that multiple array values do not match.
 * All Values Match: FALSE (because they all do compare)
 * No Values Match: TRUE (because there arent any matches)
 * Some Values Match: TRUE (because they dont all match)
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_NotCompare extends OLPBlackbox_Rule_Compare
{
	/**
	 * Runs the Not Compare rule.
	 *
	 * @param BlackBox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool TRUE if the values do not match.
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !parent::runRule($data, $state_data);
	}
}

?>
