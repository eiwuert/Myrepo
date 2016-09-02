<?php

/**
 * Specifically set up to allow targets to not have any rules.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_NoRuleTarget extends OLPBlackbox_Target
{
	/**
	 * If this target has no rules, it is valid.
	 *
	 * @param Blackbox_Data $data info about the app being processed
	 * @param Blackbox_IStateData $state_data info about the calling ITarget
	 * 
	 * @return bool TRUE if the target is valid, FALSE otherwise
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->state_data->addStateData($state_data);
		
		if ($this->rules instanceof Blackbox_IRule)
		{
			return parent::isValid($data, $state_data);
		}
		
		return TRUE;
	}

}

?>
