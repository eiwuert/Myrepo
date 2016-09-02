<?php

/**
 * Runs excluded states check and records failure reasons.
 *
 * @package VendorAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_ExcludedStates extends VendorAPI_Blackbox_Rule_NotIn
{
	/**
	 * Overridden onInvalid call to provide failure reasons for ecash reacts.
	 *
	 * @see 
	 * @param Blackbox_Data $data Information about application being looked at.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 */
	public function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);
		
		$this->addFailureReason(
			$state_data,
			new VendorAPI_Blackbox_FailureReason('EXCLUDED_STATES', 'State is excluded (' . $this->getDataValue($data) . ')')
		);

		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_COMPANY);
		}
	}
}

?>
