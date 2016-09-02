<?php

/**
 * Rule collection factory for ZipCash.
 *
 * @see [#16642] Black Box - ZIPCASH - Price Rejection Process
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 */
class OLPBlackbox_Factory_Legacy_RuleCollectionZipCash extends OLPBlackbox_Factory_Legacy_RuleCollection
{
	/**
	 * Returns the default ZipCash rule collection.
	 *
	 * @param array $rules an array of rules we'll add to the collection
	 * @param array $allowed_rules an array of strings of rule names that this collection will be restricted to
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getDefaultRuleCollection(array $rules, array $allowed_rules = NULL)
	{
		$collection = parent::getDefaultRuleCollection($rules, $allowed_rules);

		$rule = OLPBlackbox_Factory_Rules::getRule(
			'BadCustomer_ZipCash',
			array(
				OLPBlackbox_Rule::PARAM_FIELD => array('email_primary', 'social_security_number'),
				OLPBlackbox_Rule::PARAM_VALUE  => NULL,
			)
		);

		$rule->setEventName('BADCUSTOMER_ZIPCASH');

		$collection->addRule($rule);
		
		return $collection;
	}
}
