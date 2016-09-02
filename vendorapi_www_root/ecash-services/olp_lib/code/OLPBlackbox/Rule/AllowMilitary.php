<?php
/**
 * OLPBlackbox_Rule_AllowMilitary class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Checks to see if a customer is in the military based off of
 * email address and military flag.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_AllowMilitary extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
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
		return ($data->email_primary || $data->military);
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
		$is_military_email = preg_match('/.*@.*\.mil$/i',$data->email_primary);
		
		switch ($this->getRuleValue())
		{
			case 'DENY':
				$valid = (!($is_military_email) && ($data->military === "FALSE"));
				break;
			case 'ONLY':
				$valid = ($is_military_email || $data->military === "TRUE");
				break;
			default:
				$valid = TRUE;
				break;
		}
		
		return $valid;
	}
}

?>
