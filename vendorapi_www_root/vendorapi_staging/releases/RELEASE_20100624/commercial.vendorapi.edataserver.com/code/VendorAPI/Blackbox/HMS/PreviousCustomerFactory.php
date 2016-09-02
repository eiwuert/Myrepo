<?php

/**
 * The previous customer check factory for HMS
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_HMS_PreviousCustomerFactory extends VendorAPI_Blackbox_Generic_PreviousCustomerFactory
{
	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var string
	 */
	protected $denied_time_threshold = '-60 days';

	/**
	 * Adds the individual previous customer rules to the collection
	 *
	 * @param VendorAPI_Blackbox_PreviousCustomerCollection $prev_cust
	 * @return void
	 */
	protected function addRules(VendorAPI_Blackbox_PreviousCustomerCollection $prev_cust)
	{
		$prev_cust->addRule($this->getRule('SSN', TRUE));
		$prev_cust->addRule($this->getRule('EmailDob', TRUE));
		$prev_cust->addRule($this->getRule('HomePhone'));
		$prev_cust->addRule($this->getRule('License'));

		// impact has their own special bank account
		// check that doesn't include the SSN
		$rule = new VendorAPI_Blackbox_Impact_Rule_PreviousCustomer_BankAccount(
			$this->config->event_log,
			$this->provider,
			$this->decider
		);

		if ($this->isSkippable('BankAccount'))
		{
			$rule->setSkippable(TRUE);
		}

		$prev_cust->addRule($rule);
	}
}

?>
