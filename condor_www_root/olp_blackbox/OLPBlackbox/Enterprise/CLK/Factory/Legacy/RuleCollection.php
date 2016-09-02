<?php
/**
 * Rule collection factory for CLK.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Factory_Legacy_RuleCollection extends OLPBlackbox_Factory_Legacy_RuleCollection
{	
	/**
	 * Returns the rule colelction for the ONLINE_CONFIRMATION mode.
	 *
	 * @param array $rules the rules for this target
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getOnlineConfirmationRuleCollection(array $rules)
	{
		$allowed_rules = array(
			'weekend',
			'suppression_lists'
		);
		
		return parent::getDefaultRuleCollection($rules, $allowed_rules);
	}
}
?>