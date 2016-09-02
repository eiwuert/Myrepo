<?php

/**
 *
 * @author Asa Ayers <Asa.Ayers@SellingSource.com>
 */
class VendorAPI_Blackbox_Rule_MinimumRecurPayDate extends VendorAPI_Blackbox_Rule
{
	/**
	 * The number of times pay dates can change for this rule.
	 *
	 * @var int
	 */
	protected $pay_date_changes;

	/**
	 *
	 * @var int
	 */
	protected $days;

	/**
	 * @var ECash_CustomerHistory
	 */
	protected $customer_history;

	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param ECash_CustomerHistory
	 * @param integer $pay_date_changes
	 * @param integer $days
	 */
	public function __construct(
		VendorAPI_Blackbox_EventLog $log,
		ECash_CustomerHistory $customer_history = NULL,
		$pay_date_changes = NULL,
		$days = NULL
	)
	{
		parent::__construct($log);
		$this->pay_date_changes = $pay_date_changes;
		$this->days = $days;
		$this->customer_history = $customer_history;
	}
	
	/**
	 * @return string
	 */
	public function getEventName()
	{
		return 'PAY_DATE_RECUR';
	}

	public function setCustomerHistory(ECash_CustomerHistory $customer_history)
	{
		$this->customer_history = $customer_history;
	}

	public function setupRule($params)
	{
		parent::setupRule($params);
		if (is_array($params[Blackbox_StandardRule::PARAM_VALUE]))
		{
			$this->pay_date_changes = $params[Blackbox_StandardRule::PARAM_VALUE]['changes'];
			$this->days = $params[Blackbox_StandardRule::PARAM_VALUE]['days'];
		}
	}

    /**
	 * Checks to see if we have enough information to run this rule.
	 *
	 * @param Blackbox_Data $data the data to do validation against
	 * @param Blackbox_IStateData $state_data the state data to do validation against
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !empty($data->ssn);
	}

	/**
	 * Runs the pay date recur rule.
	 *
	 * Normally we would just run the runRecurRule(), but in this case, we have two rule values
	 * and the runRecurRule() function doesn't cover more than just the date.
	 *
	 * @param Blackbox_Data $data the data used to use
	 * @param Blackbox_IStateData $state_data state data to use
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$dates = array(
			date('Y-m-d', strtotime($data->date_first_payment)) => TRUE,
		);
		$threshold_date = date('Y-m-d', strtotime('-'.$this->days.' day'));

		foreach ($this->customer_history->getLoans() as $loan)
		{
			if ($loan['additional_info']['ssn'] == $data->ssn
				&& date('Y-m-d', $loan['purchase_date']) > $threshold_date)
			{
				$dates[date('Y-m-d', strtotime($loan['additional_info']['date_first_payment']))] = TRUE;
			}
		}

		return (count($dates) <= $this->pay_date_changes);
	}

	/**
	 * Runs when the rule returns invalid.
	 *                                                                       _S
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);

		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_ENTERPRISE);
		}
	}

}

?>
