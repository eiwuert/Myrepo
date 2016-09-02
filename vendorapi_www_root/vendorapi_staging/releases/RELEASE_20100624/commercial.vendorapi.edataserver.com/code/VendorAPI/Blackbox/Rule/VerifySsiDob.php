<?php

/**
 * If the applicant's employer is listed as Social Security Income (SSI) and
 * the applicant's age is under 62, then send the application to the
 * verification queue.
 *
 * @author Jim Wu <jim.wu@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_VerifySsiDob extends VendorAPI_Blackbox_VerifyRule
{
	/**
	 * @var string
	 */
	protected $company;

	/**
	 * @var VendorAPI_SuppressionList_ILoader
	 */
	protected $suppression_list;

	/**
	 * @param integer $company
	 * @param VendorAPI_SuppressionList_ILoader $suppression_list
	 * @param VendorAPI_Blackbox_EventLog $log
	 */
	public function __construct($company, VendorAPI_SuppressionList_ILoader $suppression_list, VendorAPI_Blackbox_EventLog $log)
	{
		$this->company = $company;
		$this->suppression_list = $suppression_list;
		parent::__construct($log);
	}

	/**
	 * Define the action name for this verify rule.
	 *
	 * @return string
	 */
	protected function getEventName()
	{
		return 'VERIFY_SSI_DOB';
	}

	/**
	 * Determines whether this rule can run.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}

	/**
	 * Run this rule.  Add loan action and mark as invalid if the employer is
	 * on the SSI suppression list and the applicant's age is under 62.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$ssi_employers = $this->suppression_list->getByName('SSI Employer'); // Hack, since SuppressionList uses id as key.

		/**
		 * Very simple logic.  If they're under 62 years of age and their employer is listed in the SSI Employer list
		 * or their income source is listed as 'Benefits', then we want to generate a loan action and get them sent to
		 * the Verification Queue.
		 */
		if(Date_Util_1::getAge(strtotime($data->dob)) < 62)
		{
			if(isset($ssi_employers) && $ssi_employers->match($data->employer_name)
			|| stristr($data->income_source, 'BENEFITS') != FALSE)
			{
				$this->addActionToStack($this->getEventName());
				return FALSE;
			}
		}
		return TRUE;
	}
}

?>