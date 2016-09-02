<?php

/**
 * Limit collection factory.
 *
 * This factory generates a rule collection for hourly limits, daily limits, and the such.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_Factory_TargetCollectionLimitCollection extends OLPBlackbox_Factory_LimitCollection
{
	
	/**
	 * Returns the class name for the Limit rule
	 * 
	 * @return string
	 */
	protected function getRuleClassName()
	{
		return 'TargetCollectionLimit';
	}
}

?>
