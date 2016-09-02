<?php

/**
 * Rule collection factory for CashNet.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_Factory_RuleCollectionCashNet extends OLPBlackbox_Factory_RuleCollection
{
	/**
	 * Override parent to add CashNet rules to a target.
	 *
	 * @param Blackbox_Models_IReadableTarget The target model to use to find
	 * the rules that need added.
	 * @param OLPBlackbox_ITarget $target The target to add the rules to.
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
			foreach ($this->getBloomRules($target_model) as $rule)
			{
				$target->getRules()->addRule($rule);
			}
		}		
	}
	
	/**
	 * Get the bloom rules.
	 * @return ArrayObject
	 */
	protected function getBloomRules(Blackbox_Models_IReadableTarget $target_model)
	{
		$bloom_files = array(
			'DUPE' => array(
				'file' => '/virtualhosts/bloom_files/working.bloom',
				'expected' => FALSE
			)
			// Will eventually have previous customer bloom file here
		);

		$rules = new ArrayObject();
		foreach ($bloom_files as $key => $bloom_data)
		{
			$rule = $this->getRuleFactory($target_model->property_short)->getOLPBlackboxRule(
				'CashNet_BloomFilter',
				array(
					OLPBlackbox_Rule::PARAM_FIELD => array('email_primary', 'social_security_number'),
					OLPBlackbox_Rule::PARAM_VALUE  => $bloom_data['file'],
				)
			);

			$rule->setEventName('CN_BLOOM_' . $key);
			$rule->setExpectedResult($bloom_data['expected']);

			$rules->append($rule);
		}
		return $rules;
	}
}

?>