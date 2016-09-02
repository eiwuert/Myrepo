<?php

/**
 * Adding a new rule to make sure datax recurs
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_UwRecur extends VendorAPI_Blackbox_Rule
{
	protected $days_to_check;
	protected $customer_history;
	protected $inquiry_client;
	protected $is_valid;
	protected $company_id;

	public function __construct(
		VendorAPI_Blackbox_EventLog $log,
		ECash_WebService_InquiryClient $inquiry_client,
		$days_to_check, 
		$company_id,
		$call_type)
	{
		parent::__construct($log);
		$this->inquiry_client = $inquiry_client;
		$this->days_to_check = $days_to_check;
		$this->is_valid = NULL;
		$this->company_id = $company_id;
		$this->call_type = $call_type;
	}

	/**
	 * CAN WE RUN THE RULE?!?!?!
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return unknown
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return (!empty($data->ssn));
	}

	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!is_bool($this->is_valid))
		{
			if ($data->application_id > 0)
			{
				$application_id = $data->application_id;
			}
			elseif  ($data->external_id > 0)
			{
				$application_id = $data->external_id;
			}
			else
			{
				$application_id = 0;
			}

			$cnt = $this->countUwFails($data->ssn, $application_id);
			$this->is_valid = ($cnt == 0);
		}

		return $this->is_valid;
	}
	
	/**
	 * Counts the number of failed under writer rules in the past X days.
	 *
	 * @param string $ssn
	 * @param int $application_id
	 * @return int
	 */
	protected function countUwFails($ssn, $application_id)
	{
		$failures = $this->inquiry_client->getFailuresBySsn($ssn);
		$count = 0;
		foreach ($failures as $failure)
		{	    // took this line out so repeated false application ids (ex zero, or 900000001) wont affect the count
			if (//((($failure->application_id != $application_id) && ($failure->application_id != 0)) &&
				($this->days_to_check === FALSE || strtotime('-' . $this->days_to_check . ' days') < strtotime($failure->date)))
			{
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Fired when the rule is invalid
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#onInvalid()
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);

		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_ENTERPRISE);
		}
	}

	protected function getEventName()
	{
		return 'UW_RECUR';
	}

	/**
	 * Return a failure short?
	 * @return string
	 */
	protected function failureShort()
	{
		return 'UW_RECUR';
	}

	/**
	 * Return a comment?
	 * @return string
	 */
	protected function failureComment()
	{
		return "Failed under writing check in the last ".$this->days_to_check." days.";
	}
}
