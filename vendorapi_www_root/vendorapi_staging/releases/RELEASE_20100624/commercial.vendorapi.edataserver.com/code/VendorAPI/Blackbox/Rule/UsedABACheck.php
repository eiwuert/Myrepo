<?php

/**
 * Restricts the number of SSNs used with a given bank account
 *
 * This rule restricts the number of SSNs used with the same
 * bank account within a given period of time. The default is to allow
 * one additional SSN (that's two total) within the past year.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_UsedABACheck extends VendorAPI_Blackbox_Rule
{
	/**
	 * Amount of time to look backwards
	 * @var string
	 */
	protected $date_threshold;

	/**
	 * Number of additional SSNs allowed
	 * @var int
	 */
	protected $count_threshold;

	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param array $db Databases to run against
	 * @param int $count_threshold Number of additional SSNs allowed
	 * @param string $date_threshold Amount of time to search
	 * @return void
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, $count_threshold = 1, $date_threshold = '-1 year')
	{
		parent::__construct($log);
		$this->count_threshold = $count_threshold;
		$this->date_threshold = $date_threshold;
	}

	/**
	 * Returns the event name for the rule.
	 *
	 * @return string
	 */
	protected function getEventName()
	{
		return 'USED_ABA';
	}

	/**
	 * Determines whether or not this rule can run at all.
	 *
	 * @param Blackbox_Data $data data specific to the application we're processing.
	 * @param Blackbox_IStateData $state_data data specific to the ITarget running this rule.
	 *
	 * @return bool whether or not this rule can run.
	 */
	public function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return isset($data->bank_aba)
			&& isset($data->ssn);
	}

	/**
	 * Actually run the rule and determine whether the application contains data which has been used elsewhere.
	 *
	 * @param Blackbox_Data $data Data about the application we're considering.
	 * @param Blackbox_IStateData $state_data Data about the state of the ITarget requesting we run.
	 *
	 * @return bool Whether or not the rule passes.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		try
		{
			$criteria = new VendorAPI_PreviousCustomer_Criteria_BankAccountGroupedAba(new VendorAPI_PreviousCustomer_CustomerHistoryStatusMap(), array($data->bank_aba));
			$container = new VendorAPI_PreviousCustomer_CriteriaContainer(array($criteria));

			$applications = ECash::getFactory()->getAppClient()->getPreviousCustomerApps($container->getAppServiceObject($data->toArray()));

			$apps = array();
			if (isset($applications[0]->results))
			{
				if (!is_array($applications[0]->results))
				{
					$apps = array($applications[0]->results);
				}
				else
				{
					$apps = $applications[0]->results;
				}
			}

			$ssns = array();
			foreach ($apps as $app_info)
			{
			  if ($app_info->ssn != $data->ssn
					&& strtotime($app_info->date_created) > $this->getDateThreshold())
				{
					$ssns[] = $app_info->ssn;
				}
			}

			$ssns = array_unique($ssns);
		}
		catch (Exception $e)
		{
			throw new Blackbox_Exception($e->getMessage());
		}

		// 1 other ssn+aba+bank account allowed for joint accounts
		return (count($ssns) <= $this->count_threshold);
	}

	/**
	 * Gets the threshold date as a timestamp
	 * @return int
	 */
	protected function getDateThreshold()
	{
		return strtotime($this->date_threshold);
	}

	/**
	 * Returns the failure reason.
	 *
	 * @return VendorAPI_Blackbox_FailureReason
	 */
	protected function getFailureReason()
	{
		return new VendorAPI_Blackbox_FailureReason('USED_ABA', 'Used ABA check failed');
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
		 * These failures are only company specific
		 */
		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_ENTERPRISE);
		}
	}

}
