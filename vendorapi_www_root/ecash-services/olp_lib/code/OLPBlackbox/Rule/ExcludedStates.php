<?php

/**
 * Runs excluded states check and records failure reasons.
 *
 * @package OLPBlackbox
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_ExcludedStates extends OLPBlackbox_Rule_NotIn
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
		
		if ($state_data->failure_reasons instanceof OLPBlackbox_FailureReasonList)
		{
			$state_data->failure_reasons->add(
				new OLPBlackbox_FailureReason_ExcludedStates(
					$this->getRuleValue(), $this->getDataValue($data))
			);
		}
	}
}

?>
