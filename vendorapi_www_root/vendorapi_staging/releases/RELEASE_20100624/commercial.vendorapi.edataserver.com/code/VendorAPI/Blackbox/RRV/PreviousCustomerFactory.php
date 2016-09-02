<?php

/**
 * The previous customer check factory for RRV
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_RRV_PreviousCustomerFactory extends VendorAPI_Blackbox_Generic_PreviousCustomerFactory
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
		$rule = new VendorAPI_Blackbox_RRV_Rule_PreviousCustomer_BankAccount(
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
	
	/**
	 * Gets a composite provider composed of the ECash provider and the Legacy React
	 * Customer provider
	 * 
	 * @param array $companies
	 * @return ECash_CompositeProvider
	 */
	protected function getProvider(array $companies)
	{
		$provider = new ECash_CompositeProvider();

		$provider->addProvider(
			new ECash_HistoryProvider(
				$this->driver->getDatabase(),
				$companies));

		$provider->addProvider(
			new RRV_VendorAPI_Blackbox_LegacyReactProvider(
				$this->driver->getFactory()->getModel('LegacyReactCustomer'),
				$companies));

		return $provider;
	}

}

?>
