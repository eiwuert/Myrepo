<?php
/**
 * Rule collection factory for Impact.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Impact_Factory_Legacy_RuleCollection extends OLPBlackbox_Factory_Legacy_RuleCollection
{
	/**
	 * Returns a rule collection, stripped down for react rules.
	 * 
	 * Removes the minimum_income and military checks.
	 *
	 * @param array $rules
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getReactRuleCollection(array $rules)
	{
		$allowed_rules = array(
			'suppression_lists',
			'excluded_states',
			'restricted_states'
		);
		
		return $this->getDefaultRuleCollection($rules, $allowed_rules);
	}
}
?>