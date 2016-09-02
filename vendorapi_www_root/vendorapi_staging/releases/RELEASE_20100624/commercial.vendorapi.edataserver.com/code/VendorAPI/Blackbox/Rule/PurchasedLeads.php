<?php

/**
 * Checks to ensure a customer does not have more than X purchased leads in the last x days.
 *
 * @author Mike Lively <Mike.Lively@SellingSource.com>
 */
class VendorAPI_Blackbox_Rule_PurchasedLeads extends VendorAPI_Blackbox_Rule
{
	/**
	 * @var VendorAPI_PurchasedLeadStore_Memcache
	 */
	protected $store;

	/**
	 * @var string
	 */
	protected $enterprise_threshold;

	/**
	 * @var int
	 */
	protected $enterprise_count;

	/**
	 * @var string
	 */
	protected $company;

	/**
	 * @var string
	 */
	protected $company_threshold;

	/**
	 * @var int
	 */
	protected $company_count;

	/**
	 * @var string
	 */
	protected $enterprise_time_threshold;

	/**
	 * @var bool
	 */
	protected $failed_enterprise = FALSE;

	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param VendorAPI_PurchasedLeadStore_Memcache $store
	 * @param string $enterprise_threshold
	 */
	public function __construct(
			VendorAPI_Blackbox_EventLog $log,
			VendorAPI_PurchasedLeadStore_Memcache $store,
			$enterprise_threshold,
			$enterprise_count,
			$company,
			$company_threshold,
			$company_count,
			$enterprise_time_threshold = '60 minutes'
	)
	{
		parent::__construct($log);
		$this->store = $store;
		$this->enterprise_threshold = $enterprise_threshold;
		$this->enterprise_count = $enterprise_count;
		$this->company = $company;
		$this->company_threshold = $company_threshold;
		$this->company_count = $company_count;
		$this->enterprise_time_threshold = (empty($enterprise_time_threshold))
				? NULL
				: $enterprise_time_threshold;
	}

	/**
	 * Define the action name for this verify rule.
	 *
	 * @return string
	 */
	protected function getEventName()
	{
		return 'PURCHASED_LEADS';
	}

	/**
	 * Runs the purchased lead rule.
	 *
	 * @param Blackbox_Data $data the data used to use
	 * @param Blackbox_IStateData $state_data state data to use
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->failed_enterprise = FALSE;
		$this->store->lockSsn($data->ssn, 1000000);
		$applications = $this->store->getApplications($data->ssn);

		/**
		 * The idea of the code below was to remove the application from the cache
		 * since it wasn't actually purchased.  This is automatically done by the
		 * ECash_VendorAPI_Blackbox_DataX_PurchasedLeadsObserver, but in case another
		 * rule failed the app, this allowed us to ignore the app since it was never
		 * actually "purchased".
		 *
		 * It's safe to consider the application as "purchased" unless we encounter a
		 * "Soft" failure from DataX, where we'll allow the lead to run through the rules
		 * multiple times.  This will serve to avoid issues where more than one AMG company
		 * purchased(*) the same lead during periods of VendorAPI timeouts. [#49955]
		 *
		 * (*) = It's not actually purchased since the call timed out, but the application
		 * does usually get written into the Application Service, and we don't want the same
		 * application (external_id) in multiple company databases.
		 */
		//unset($applications[$data->external_id]);

		if (is_array($applications))
		{
			/**
			 * Remove application from the cache result *only* if it's for the same
			 * company. [#49955]
			 */
			if(array_key_exists($data->external_id, $applications))
			{
				if($applications[$data->external_id]['company'] == $this->company)
				{
					unset($applications[$data->external_id]);
				}
			}

			foreach ($applications as $app)
			{
				$state_data->customer_history->addLoan($app['company'], 'pending', $app['application_id'], $app['date'], $app['date'], array());
			}
		}

		$this->failed_enterprise = !$this->countEnterpriseLeads($state_data->customer_history) || !$this->countEnterpriseLeadTime($state_data->customer_history);
		if (!$this->failed_enterprise
			&& $this->countCompanyLeads($state_data->customer_history)
		)
		{
			$valid = TRUE;
			$this->store->addApplication($data->ssn, $this->company, $data->external_id, time());
		}
		else
		{
			$valid = FALSE;
		}

		$this->store->unlockSsn($data->ssn);
		return $valid;
	}

	/**
	 * This rule makes sure that a previous customer did not apply with another
	 * company in the enterrise within the determined timeframe (enterprise_time_threshold)
	 *
	 * @param ECash_CustomerHistory $history
	 */
	protected function countEnterpriseLeadTime(ECash_CustomerHistory $history)
	{
		if (!is_null($this->enterprise_time_threshold))
		{
			return $this->countPurchasedLeads($history, $this->enterprise_time_threshold, 1);
		}
		return TRUE;
	}

	/**
	 * If we have an enterprise threshold and count then validate them
	 * otherwise just pass.
	 * @param ECash_CustomerHistory $history
	 * @return Boolean
	 */
	protected function countEnterpriseLeads(ECash_CustomerHistory $history)
	{
		if (!is_null($this->enterprise_threshold) && !is_null($this->enterprise_count))
		{
			return $this->countPurchasedLeads($history, $this->enterprise_threshold, $this->enterprise_count);
		}
		return TRUE;
	}
	
	/**
	 * If we have an company threshold and count then validate them
	 * @param ECash_CustomerHistory $history
	 * @return Boolean
	 */
	protected function countCompanyLeads(ECash_CustomerHistory $history)
	{
		if (!is_null($this->company_threshold) && !is_null($this->company_count))
		{
			return $this->countPurchasedLeads(
				$history->getCompanyHistory($this->company), $this->company_threshold, $this->company_count);
		}
		return TRUE;
	}

	/**
	 * Checks the number of leads against the given thresholds. Returns true on pass
	 *
	 * @param ECash_CustomerHistory $history
	 * @param string $threshold
	 * @param int $count
	 * @return bool
	 */
	protected function countPurchasedLeads(ECash_CustomerHistory $history, $threshold, $count)
	{
		return $history->getPurchasedLeadCount($threshold) < $count;
	}

	/**
	 * Runs when the rule returns invalid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);

		/**
		 * Company specific rule
		 */
		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail($this->failed_enterprise ? VendorAPI_Blackbox_FailType::FAIL_ENTERPRISE :VendorAPI_Blackbox_FailType::FAIL_COMPANY);
		}
	}

}
?>
