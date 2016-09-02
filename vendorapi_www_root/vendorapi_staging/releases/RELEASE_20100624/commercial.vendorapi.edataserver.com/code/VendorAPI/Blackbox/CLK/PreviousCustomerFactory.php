<?php

/**
 * A factory for the previous customer checks
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_CLK_PreviousCustomerFactory extends VendorAPI_Blackbox_Generic_PreviousCustomerFactory
{
	/**
	 * CLK allows two active loans (w/ different companies)
	 * 
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var int
	 */ 
	protected $active_threshold = 1;
	
	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var int
	 */
	protected $disagreed_threshold = 1;
	
	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var int
	 */
	protected $disagreed_react_threshold = NULL;	

	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var string
	 */
	protected $disagreed_time_threshold = '-24 hours';
	
	/*
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var string
	 */
	protected $disagreed_react_time_threshold = '-240 hours';
	

	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var int
	 */
	protected $allowed_withdrawn_threshold = 3;

	/**
	 * Adds the individual previous customer criteria to the container
	 *
	 * @param VendorAPI_PreviousCustomer_CriteriaContainer $container
	 * @return void
	 */
	protected function addCriteria(VendorAPI_PreviousCustomer_CriteriaContainer $container)
	{
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_Ssn($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_EmailDob($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_BankAccountDob($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_BankAccount($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_HomePhoneDob($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_License($this->getCustomerHistoryStatusMap()));
	}

	/**
	 * Adds the individual react previous customer criteria to the container
	 * By default, this just calls $this->addCriteria().
	 *
	 * @param VendorAPI_PreviousCustomer_CriteriaContainer $container
	 * @return void
	 */
	protected function addReactCriteria(VendorAPI_PreviousCustomer_CriteriaContainer $container)
	{
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_Ssn($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_EmailDob($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_BankAccountDob($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_HomePhoneDob($this->getCustomerHistoryStatusMap()));
	}

	/**
	 * Gets the customer history loader for AMG (for their custom expiration rules)
	 * @param VendorAPI_PreviousCustomer_CriteriaContainer $criteria
	 * @return VendorAPI_PreviousCustomer_HistoryLoader
	 */
	protected function getCustomerHistoryLoader(VendorAPI_PreviousCustomer_CriteriaContainer $criteria)
	{
		// [#45226] CS Reacts/Email Reacts do not expire apps like others do
		$expire_apps = ($this->config->is_react && in_array($this->config->olp_process,array("cs_react","email_react"))) ? FALSE : $this->config->is_react;

		return new VendorAPI_PreviousCustomer_HistoryLoader($this->driver->getAppClient(), $this->getCustomerHistoryStatusMap(), $criteria, $expire_apps);
	}

	/**
	 * Gets the ECash provider
	 *
	 * @param array $companies
	 * @return VendorAPI_Blackbox_CLK_ECashProvider
	 */
	protected function getProvider(array $companies)
	{
		$composite = new ECash_CompositeProvider();
		$preact = FALSE; //$this->config->debug->flagTrue(VendorAPI_Blackbox_DebugConf::PREACT_CHECK);
		
		foreach ($companies as $company)
		{
			
			$provider = new ECash_HistoryProvider(
				$this->driver->getDatabase($company),
				array($company),
				$expire_apps,
				$preact
			);

			$composite->addProvider($provider);
		}

		return $composite;
	}

	/**
	 * Gets the company's decider
	 *
	 * @return VendorAPI_Blackbox_Generic_Decider
	 */
	protected function getDecider()
	{
		// Set values of thresholds to use.
		// Remove the disagreed threshold from non-reacts (set it to null) - GForge #8774 [DW]
		// Remove the denied threshold from non-marketing sites (set it to null) - GForge #8062 [DW]
		$active_threshold = $this->active_threshold;
		$denied_time_threshold = ($this->config->is_enterprise) ? NULL : $this->denied_time_threshold;
		$disagreed_threshold = ($this->config->is_react) ? $this->disagreed_react_threshold : $this->disagreed_threshold;
		$disagreed_time_threshold =  ($this->config->is_react) ? $this->disagreed_react_time_threshold : $this->disagreed_time_threshold;
		$allowed_status_site = $this->isAllowedStatusSite($this->config->site_name);

		// Add the disagreed threshold to check for disagreed apps - GForge #8774 [DW]
		$decider = new VendorAPI_Blackbox_CLK_Decider(
			$active_threshold,
			$denied_time_threshold,
			$disagreed_threshold,
			$disagreed_time_threshold,
			$this->withdrawn_threshold,
			$this->config->company
		);
		
		$decider->setUseAllowedStatus($allowed_status_site, $this->allowed_withdrawn_threshold);
		
		return $decider;
	}

	protected function isAllowedStatusSite($site_name)
	{

		switch(strtolower($site_name))
		{
			case 'fastcashnow.com':
			case 'acceptmycash.com':
				return true;
				break;
			default:
				break;
		}
		return false;
	}
}

?>
