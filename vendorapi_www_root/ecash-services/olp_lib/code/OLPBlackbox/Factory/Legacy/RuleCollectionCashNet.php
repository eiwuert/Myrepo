<?php

/**
 * Rule collection factory for CashNet.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_RuleCollectionCashNet extends OLPBlackbox_Factory_Legacy_RuleCollection
{
	/**
	 * Returns the default CashNet rule collection.
	 *
	 * @param array $rules an array of rules we'll add to the collection
	 * @param array $allowed_rules an array of strings of rule names that this collection will be restricted to
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getDefaultRuleCollection(array $rules, array $allowed_rules = NULL)
	{
		$collection = parent::getDefaultRuleCollection($rules, $allowed_rules);
		
		$bloom_files = array(
			'DUPE' => array(
				'file' => '/virtualhosts/bloom_files/working.bloom',
				'expected' => FALSE
			)
			// Will eventually have previous customer bloom file here
		);

		foreach ($bloom_files as $key => $bloom_data)
		{
			$rule = OLPBlackbox_Factory_Rules::getRule(
				'CashNet_BloomFilter',
				array(
					OLPBlackbox_Rule::PARAM_FIELD => array('email_primary', 'social_security_number'),
					OLPBlackbox_Rule::PARAM_VALUE  => $bloom_data['file'],
				)
			);

			$rule->setEventName('CN_BLOOM_' . $key);
			$rule->setExpectedResult($bloom_data['expected']);

			$collection->addRule($rule);
		}
		
		return $collection;
	}
}

?>