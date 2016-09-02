<?php

/**
 * Rule collection factory to produce ZipCash rule collections.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_RuleCollectionZipCash extends OLPBlackbox_Factory_RuleCollection 
{
	/**
	 * Overriden version of parent to add special ZipCash bad customer rule.
	 * @see OLPBlackbox_Factory_RuleCollection::setRuleCollections()
	 * @param Blackbox_Models_IReadableTarget $target_model The model to build
	 * the rule collection for.
	 * @param OLPBlackbox_ITarget $target Target to set the rule collections on.
	 * @return void
	 */
	public function setRuleCollections(
		Blackbox_Models_IReadableTarget $target_model,
		OLPBlackbox_ITarget $target
	)
	{
		parent::setRuleCollections($target_model, $target);
		
		if ($target->getRules() instanceof OLPBlackbox_RuleCollection
			&& !$this->getDebug()->debugSkipRule(OLPBlackbox_DebugConf::RULES))
		{
			$target->getRules()->addRule($this->getBadCustomerRule($target_model));
		}		
	}
	
	/**
	 * Gets the special ZipCash bad customer rule.
	 * @param Blackbox_Models_IReadableTarget $target_model The model to build
	 * the rule for.
	 * @return OLPBlackbox_Rule_ZipCash
	 */
	protected function getBadCustomerRule(Blackbox_Models_IReadableTarget $target_model)
	{
		$rule = $this->getRuleFactory($target_model->property_short)->getOLPBlackboxRule(
			'BadCustomer_ZipCash',
			array(
				OLPBlackbox_Rule::PARAM_FIELD => array('email_primary', 'social_security_number'),
				OLPBlackbox_Rule::PARAM_VALUE  => NULL,
			)
		);

		$rule->setEventName('BADCUSTOMER_ZIPCASH');
		
		return $rule;
	}
}

?>
