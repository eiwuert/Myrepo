<?php

/**
 * Rule for comparing work and home phone.
 *
 * Normally, lenders might want to deny applicants with the same work and home
 * phone, but sometimes they only want to do that if the applicant doesn't receive
 * benefits. This rule accomodates both cases.
 *
 * The "value" of this rule will be either "INCOME" or something else. "INCOME"
 * means do not fail if the applicant's income source is "BENEFITS." Yes, this
 * is not awesome.
 *
 * NB: This behavior/rule was based on an Partner Weekly OLP rule named "IncomePhone"
 * which, coupled with the poor name, was badly coded.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_CompareWorkAndHomePhone extends VendorAPI_Blackbox_Rule
{
    /**
	 * Determines whether this rule can run.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !empty($data->phone_home) && !empty($data->phone_work);
	}

	/**
	 * Ensure home and work phone numbers are not the same unless the income source
	 * is benefits.
	 * 
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->allowBecauseIncomeIsBenefits($data))
		{
			return TRUE;
		}

		return !($data->phone_home == $data->phone_work);
	}

	
	protected function allowBecauseIncomeIsBenefits(Blackbox_Data $data)
	{
		return (strtoupper($data->income_source) == 'BENEFITS')
			&& ($this->getRuleValue() == 'INCOME');
	}

	/**
	 * Runs when the rule returns invalid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);

		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_ENTERPRISE);
		}
	}
}

?>
