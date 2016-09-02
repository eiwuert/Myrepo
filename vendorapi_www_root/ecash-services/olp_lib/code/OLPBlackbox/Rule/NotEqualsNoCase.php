<?php

/**
 * Determines if two values are not equal.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_NotEqualsNoCase extends OLPBlackbox_Rule_EqualsNoCase
{
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !parent::runRule($data, $state_data);
	}
}

?>
