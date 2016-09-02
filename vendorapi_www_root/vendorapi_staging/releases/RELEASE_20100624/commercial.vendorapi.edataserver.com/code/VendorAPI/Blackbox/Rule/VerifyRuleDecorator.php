<?php 
/**
 * Decorator to trigger Verify rather than Fail for a rule
 *
 * @author Richard Meyers <richard.meyers@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_VerifyRuleDecorator extends VendorAPI_Blackbox_Rule 
{
	/**
	 * Instance of the rule
	 * @var VendorAPI_Blackbox_Rule
	 */
	protected $rule;

	/**
	 * Prefix to the verify loan action name.
	 * @var string
	 */
	protected $action_prefix;

	/**
	 * @param VendorAPI_Blackbox_Rule $rule
	 * @param string $action_prefix Optional prefix to the verify loan action name.
	 *
	 */
	public function __construct(VendorAPI_Blackbox_Rule $rule, $action_prefix='')
	{
		$this->rule = $rule;
		$this->action_prefix = $action_prefix;

		// This is very hacky, and every "real" OOP developer will cry foul, but we're going to do
		// it like this because I don't want to be here all night.
		$this->event_log = $this->rule->event_log;
	}

	/**
	 * @return string
	 */
	protected function getEventName()
	{
		$event_name = $this->rule->getEventName();
		if (!empty($event_name))
		{
			$event_name = 'VERIFY_' . $event_name;
			if (!empty($this->action_prefix))
			{
				$event_name = $this->action_prefix . '_' . $event_name;
			}
		}

		return $event_name;
	}

	/**
	 *
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return unknown
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return $this->rule->canRun($data, $state_data);
	}

	/**
	 * Run the rule
	 * Verify decorated rules really don't actually fail, so we'll ALWAYS return true
	 * [and avoid a call to the decorated rule's onInvalid() method]
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!$this->rule->runRule($data, $state_data))
		{
			// call this onInvalid method here, to add loan action
			// the decorated rule's onInvalid method is never called 
			$this->onInvalid($data, $state_data);
		}
		return TRUE;
	}

	/**
	 * Sets the bool that determines whether onSkip returns true or false.
	 *
	 * @param bool $skippable TRUE if skipping the rule is success, FALSE if it should fail on skip
	 * @return void
	 */
	public function setSkippable($skippable = TRUE)
	{
		$this->rule->setSkippable($skippable);
	}

	/**
	 * Adds a loan action to the state data
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_name = $this->getEventName();
		if (!empty($event_name))
		{
			// this should be refactored into the StateData class
			if (!$state_data->loan_actions instanceof VendorAPI_Blackbox_LoanActions)
			{
				$state_data->loan_actions = new VendorAPI_Blackbox_LoanActions();
			}
			$state_data->loan_actions->addLoanAction($event_name);
		}
	}

	/**
	 * Called when the rule is skipped (canRun returns FALSE)
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return bool
	 */
	protected function onSkip(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->rule->onSkip($data, $state_data);
	}
}

?>
