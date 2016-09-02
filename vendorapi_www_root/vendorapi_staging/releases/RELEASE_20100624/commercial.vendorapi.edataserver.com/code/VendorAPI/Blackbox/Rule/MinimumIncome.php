<?php

/**
 * Extend GreaterThanEquals to allow a failure reason to be set when invalid.
 * 
 * @see VendorAPI_Blackbox_Rule_MinimumIncome
 *
 * @package VendorAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_MinimumIncome extends VendorAPI_Blackbox_Rule_GreaterThanEquals
{
	/**
	 * Override the default onInvalid event to add a failure reason.
	 *
	 * @see VendorAPI_Blackbox_Rule_MinimumIncome
	 * @param Blackbox_Data $data Info about the app being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 * @return void
	 */
	public function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);
		
		$this->addFailureReason(
			$state_data,
			new VendorAPI_Blackbox_FailureReason('MINIMUM_INCOME', 'Income (' . $this->getDataValue($data) . ') is less than (' . $this->getRuleValue() . ')')
		);

		/**
		 * It's conceivable that the min income requirements may be different for each company.
		 */
		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_COMPANY);
		}
	}
	
	/**
	 * Variable checking for value
	 *
	 * @param array $params
	 */
	public function setupRule($params)
	{
		$required_amount = intval($params[Blackbox_StandardRule::PARAM_VALUE]);
		if (!is_numeric($required_amount))
		{
			throw new InvalidArgumentException(sprintf(
				'required income amount must be int, not %s',
				$required_amount)
			);
		}

		$params[Blackbox_StandardRule::PARAM_VALUE] = $required_amount;
		parent::setupRule($params);
	}
}

?>
