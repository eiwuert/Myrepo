<?php

/**
 * VerifyEmployer
 *
 */
class VendorAPI_Blackbox_Rule_VerifyEmployer extends VendorAPI_Blackbox_VerifyRule 
{
	/**
	 * Define the action name for this
	 * verify rule
	 *
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log)
	{
		parent::__construct($log);
			$this->addActionToStack('VERIFY_EMPLOYER');
	}

	protected function getEventName()
	{
		return 'VERIFY_EMPLOYER';
	}

	/**
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
		$employer_name = strtolower($data->employer_name);
		$applicant = strtolower($data->name_first . " " . $data->name_last);

		$return = !(in_array($employer_name, array('self','self employed','unemployed','n/a','na','none',$applicant)));
	
		return $return;
	}
}

?>
