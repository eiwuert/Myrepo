<?php
/**
 * Previous Customer Check Rule.
 *
 * This rule will load a customer history object with all of the applications matched
 * to the current customer utilizing a customer history loader. It will then run
 * the loaded customer history object through a decider that will do various time
 * and count threshold type of checks to ensure the customer doesn't have bad
 * loans, too many loans, etc.
 *
 * @author Mike Lively <mike.lively>
 */
class VendorAPI_Blackbox_Rule_PreviousCustomer extends VendorAPI_Blackbox_Rule
{
	/**
	 * @var ECash_CustomerHistory
	 */
	protected $customer_history;

	/**
	 * @var VendorAPI_Blackbox_ICustomerHistoryDecider
	 */
	protected $decider;

	/**
	 *
	 * @var VendorAPI_Blackbox_Generic_Decision
	 */
	protected $result;

	/**
	 * @var VendorAPI_PreviousCustomer_HistoryLoader
	 */
	protected $loader;

	/**
	 * @var string
	 */
	protected $company;

	/**
	 * @var bool
	 */
	protected $is_react;

	/**
	 * @var bool
	 */
	protected $is_enterprise;

	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param ECash_CustomerHistory $customer_history
	 * @param VendorAPI_Blackbox_ICustomerHistoryDecider $decider
	 * @param VendorAPI_PreviousCustomer_HistoryLoader $loader
	 * @param string $company
	 * @param int $is_react
	 * @param int $is_enterprise
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, ECash_CustomerHistory $customer_history, VendorAPI_Blackbox_ICustomerHistoryDecider $decider, VendorAPI_PreviousCustomer_HistoryLoader $loader, $company, $is_react = FALSE, $is_enterprise = FALSE)
	{
		parent::__construct($log);
		$this->customer_history = $customer_history;
		$this->decider = $decider;
		$this->loader = $loader;
		$this->company = $company;
		$this->is_react = $is_react;
		$this->is_enterprise = $is_enterprise;
	}

	/**
	 * Run the actual validation for the rule
	 *
	 * If this method returns TRUE, onValid will be called and isValid will also
	 * return TRUE. If this method returns FALSE, onInvalid will be called and
	 * isValid will also return FALSE.
	 *
	 * @param Blackbox_Data $data The data used to validate the rule.
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->loader->loadHistoryObject($this->customer_history, $data->toArray(), $data->application_id);

		$customer_history = $this->customer_history;
		if (
			$this->is_react
			|| (
				$this->is_enterprise
				&& $customer_history->getIsReact($this->company)
			)
		)
		{
			$customer_history = $this->customer_history->getCompanyHistory($this->company);
		}
		$this->result = $this->decider->getDecision($customer_history);

		return $this->result->isValid();
	}

	/**
	 * The name of this illustrious rule.
	 *
	 * @return string
	 */
	protected function getEventName()
	{
		return 'PREVIOUS_CUSTOMER';
	}

	/**
	 * Avoid extraneous stat/event hits; we do that ourselves
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->logEvent(
			$this->getEventName(),
			$this->result->getDecision()
		);
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
		 * 
		 */
		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			switch($this->result->getDecision())
			{
				case VendorAPI_Blackbox_Generic_Decision::CUSTOMER_BAD:
				case VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED:
				case VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DISAGREED:
				case VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE:
				case VendorAPI_Blackbox_Generic_Decision::CUSTOMER_WITHDRAWN:
				case VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DONOTLOAN:
					$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_COMPANY);
					break;
			}
		}
	}

	/**
	 * Returns the failure comment for the rule
	 * @return string
	 */
	protected function failureComment()
	{
		$decision = $this->result;
		$debug_info = $this->result->getDebugInfo();
		$debug_str = empty($debug_info) ? "" : " Debug Info: {$debug_info}";
		
		return "Decision: {$this->result->getDecision()}{$debug_str}";

	}

	/**
	 * Returns the failure short for the rule.
	 */
	protected function failureShort()
	{
		return $this->getEventName();
	}
}
?>
