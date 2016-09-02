<?php

/**
 * A factory for the previous customer checks
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Factory_Legacy_PreviousCustomer extends OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer
{
	// CLK allows two active loans (w/ different companies)
	const ACTIVE_THRESHOLD = 1;

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
			$this->getRule('BankAccountDob'),
			$this->getRule('HomePhoneDob'),
			$this->getRule('License')
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

	/**
	 * This gets added to the CLK target collection, so we need all properties
	 *
	 * @return array
	 */
	protected function getCompanies()
	{
		// we can get either CLK (parent) or children
		$target_name = (strcasecmp($this->target_name, EnterpriseData::COMPANY_CLK) === 0)
			? $this->target_name
			: EnterpriseData::getCompany($this->target_name);
		return EnterpriseData::getCompanyProperties($target_name);
	}

	/**
	 * Gets the ECash provider
	 *
	 * @param array $companies
	 * @return OLPBlackbox_Enterprise_ECashProvider
	 */
	protected function getECashProvider(array $companies)
	{
		$preact = $this->config->debug->flagTrue(OLPBlackbox_DebugConf::PREACT_CHECK);

		$provider = new OLPBlackbox_Enterprise_Generic_ECashProvider(
			$companies,
			$this->react,
			$preact
		);
		return $provider;
	}

	/**
	 * Gets the OLP provider
	 *
	 * @param array $companies
	 * @return OLPBlackbox_Enterprise_OLPProvider
	 */
	protected function getOLPProvider(array $companies)
	{
		$provider = new OLPBlackbox_Enterprise_Generic_OLPProvider(
			$this->getOLPConnection(),
			$companies,
			$this->react
		);
		return $provider;
	}

	/**
	 * Gets the company's decider
	 *
	 * @return OLPBlackbox_Enterprise_ICustomerHistoryDecider
	 */
	protected function getDecider()
	{
		// Add the disagreed threshold to check for disagreed apps - GForge #8774 [DW]
		// Remove the denied threshold from non-marketing sites - GForge #8062 [DW]
		if ($this->enterprise)
		{
			// Don't use the denied threshold for non-marketing site
			return new OLPBlackbox_Enterprise_CLK_Decider(
				self::ACTIVE_THRESHOLD,
				NULL,
				self::DISAGREED_THRESHOLD
			);
		}
		else
		{
			// Use the denied threshold for marketing site
			return new OLPBlackbox_Enterprise_CLK_Decider(
				self::ACTIVE_THRESHOLD,
				self::DENIED_THRESHOLD,
				self::DISAGREED_THRESHOLD
			);
		}
	}
}

?>