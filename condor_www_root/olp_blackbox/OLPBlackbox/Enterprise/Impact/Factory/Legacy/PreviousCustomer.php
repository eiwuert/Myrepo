<?php

/**
 * The previous customer check factory for Agean
 * They use the basic checks, except their active threshold is 0
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Impact_Factory_Legacy_PreviousCustomer extends OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer
{
	/**
	 * Gets the company's decider
	 * Have to redefine this to get the constant to work
	 *
	 * @return OLPBlackbox_Enterprise_ICustomerHistoryDecider
	 */
	protected function getDecider()
	{
		return new OLPBlackbox_Enterprise_Impact_Decider(
			self::ACTIVE_THRESHOLD,
			self::DENIED_THRESHOLD
		);
	}

	/**
	 * Adds the individual previous customer rules to the collection
	 *
	 * @param OLPBlackbox_Enterprise_Generic_PreviousCustomerCollection $prev_cust
	 * @return void
	 */
	protected function addRules(OLPBlackbox_Enterprise_Generic_PreviousCustomerCollection $prev_cust)
	{
		$rules = array(
			$this->getRule('SSN', TRUE),
			$this->getRule('EmailDob', TRUE),
			$this->getRule('HomePhone'),
			$this->getRule('License'),
			// impact has their own special bank account
			// check that doesn't include the SSN
			new OLPBlackbox_Enterprise_Impact_Rule_PreviousCustomer_BankAccount(
				$this->olp_provider,
				$this->ecash_provider,
				$this->decider
			)
		);
		
		foreach ($rules as $rule)
		{
			if ($this->config->blackbox_mode != OLPBlackbox_Config::MODE_BROKER)
			{
				$rule->setSkippable(TRUE);
			}
			
			$prev_cust->addRule($rule);
		}
	}
}

?>