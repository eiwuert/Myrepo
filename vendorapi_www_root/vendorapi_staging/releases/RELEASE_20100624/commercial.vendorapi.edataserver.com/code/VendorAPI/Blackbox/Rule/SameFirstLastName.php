<?php
/**
 * Verify rule for same first and last name
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_SameFirstLastName extends VendorAPI_Blackbox_VerifyRule
{

	/**
	 * Define the action name for this
	 * verify rule
	 *
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log)
	{
		parent::__construct($log);
		$this->addActionToStack('VERIFY_SAME_FIRST_LAST');
	}

	protected function getEventName()
	{
		return 'VERIFY_SAME_FIRST_LAST';
	}

	/**
	 * Always run?
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return unknown
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
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
