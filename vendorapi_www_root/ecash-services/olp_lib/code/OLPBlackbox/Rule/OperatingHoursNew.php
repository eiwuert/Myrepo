<?php
/**
 * Checks to see if the target has specific operating hours set, and
 * makes sure the current date/time meet those requirements.
 *
 * @author  Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_Rule_OperatingHoursNew extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	/**
	 * We can only run if the external class OperatingHours exists.  Let's fail instead of
	 * triggering a class not found fatal error during runRule
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return ($this->getParamValue() !== NULL);
	}
	
	/**
	 * Runs the OperatingHours rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 *
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$today = Blackbox_Utils::getInstance()->getToday();
		$date_time_string = date('Y-m-d H:i:s', $today);
				
		// Instantiate an instance of OperatingHours and load it with the rule data
		$operating_hours = new OLPBlackbox_OperatingHours();
		$operating_hours->fromArray($this->getParamValue());

		return $operating_hours->isOpen($date_time_string);
	}
	

}
?>