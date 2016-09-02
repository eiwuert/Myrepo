<?php

class OLPBlackbox_Rule_PropertyNotSet extends OLPBlackbox_Rule_PropertySet
{
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !parent::runRule($data, $state_data);
	}
}

?>