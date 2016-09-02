<?php
/**
 * Check a dob against a maximum age.
 *
 * @package OLPBlackbox
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class OLPBlackbox_Rule_MaximumAge extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
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
		return parent::canRun($data, $state_data) 
			&& strtotime($this->getDataValue($data)) !== FALSE;
	}
	
	/**
	 * Runs the Maximum age rule. Expects birthday in strtotime compatible string.
	 *
	 * @param Blackbox_Data $data 
	 * @param Blackbox_IStateData $state_data
	 * 
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$dob_timestamp = strtotime($this->getDataValue($data));
		
		/**
		 * People who are exactly the max age should still pass so check max age
		 * based on if their birthdate is less than the next year. 
		 * Max age 50 is basically < 51
		 */
		$min_timestamp = strtotime("-" . ($this->getRuleValue()+1) . " years");

		return $dob_timestamp > $min_timestamp;
	}
}

?>
