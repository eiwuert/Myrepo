<?php

/**
 * VerifyEmailAddress
 *
 */
class VendorAPI_Blackbox_Rule_VerifyEmailAddress extends VendorAPI_Blackbox_VerifyRule 
{
	/**
	 * Define the action name for this
	 * verify rule
	 *
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log)
	{
		parent::__construct($log);
			$this->addActionToStack('VERIFY_EMAIL');
	}

	protected function getEventName()
	{
		return 'VERIFY_EMAIL';
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
		$email = strtolower($data->email);
		
		if (
			(strpos($email, "@charter.net") !== FALSE)
			||
			(strpos($email, "@comcast.com") !== FALSE)
			||
			(strpos($email, "@aol.com") !== FALSE)
		)
		{
			$return = FALSE;
		}

		if (
			strpos($email, "@") !== FALSE
			&&
			(
				strpos($email, ".com") !== FALSE
			       || strpos($email, ".org") !== FALSE
			       || strpos($email, ".net") !== FALSE
			       || strpos($email, ".int") !== FALSE
			       || strpos($email, ".edu") !== FALSE
			       || strpos($email, ".gov") !== FALSE
			       || strpos($email, ".mil") !== FALSE
			       || strpos($email, ".us") !== FALSE
			)
		)
		{
			$return = TRUE;
		}
		else
		{
			$return = FALSE;
		}
	
		return $return;
	}
}

?>
