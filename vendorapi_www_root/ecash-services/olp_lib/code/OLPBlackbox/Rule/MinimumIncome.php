<?php

/**
 * Extend GreaterThanEquals to allow a failure reason to be set when invalid.
 * 
 * @see OLPBlackbox_FailureReason_MinimumIncome
 *
 * @package OLPBlackbox
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumIncome extends OLPBlackbox_Rule_GreaterThanEquals
{
	/**
	 * Override the default onInvalid event to add a failure reason.
	 *
	 * @see OLPBlackbox_FailureReason_MinimumIncome
	 * @param Blackbox_Data $data Info about the app being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 * @return void
	 */
	public function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);
		if ($state_data->failure_reasons instanceof OLPBlackbox_FailureReasonList)
		{
			$state_data->failure_reasons->add(
				new OLPBlackbox_FailureReason_MinimumIncome(
					$this->getRuleValue(),
					$this->getDataValue($data))
			);
		}
	}
	
	/**
	 * Variable checking for value
	 *
	 * @param array $params
	 */
	public function setupRule($params)
	{
		$required_amount = intval($params[OLPBlackbox_Rule::PARAM_VALUE]);
		if (!is_numeric($required_amount))
		{
			throw new InvalidArgumentException(sprintf(
				'required income amount must be int, not %s',
				$required_amount)
			);
		}

		$params[OLPBlackbox_Rule::PARAM_VALUE] = $required_amount;
		parent::setupRule($params);
	}
}

?>
