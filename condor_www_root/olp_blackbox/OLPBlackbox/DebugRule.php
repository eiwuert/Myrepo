<?php
/**
 * Rule to use when forcing a rule to pass for debugging purposes.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_DebugRule extends OLPBlackbox_Rule
{
	/**
	 * Add the name of the rule we're skipping.
	 *
	 * In the factory, sometimes rules will be skipped and replaced with
	 * this class which will just hit the event log and record "debug_skip."
	 * 
	 * @param string $event Name of the event this is replacing for logging.
	 * 
	 * @return void
	 */
	public function __construct($event = NULL)
	{
		$this->name = 'OLPBlackbox_DebugRule';

		if ($event !== NULL)
		{
			$this->setEventName($event);
		}
	}

	/**
	 * This rule will never actually run.
	 *
	 * @param Blackbox_Data $data the data we would normally do validation on
	 * @param Blackbox_IStateData $state_data the state data we would normally do validation on
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}

	/**
	 * Called when we pass the rule, which we always will with a debug rule.
	 *
	 * @param Blackbox_Data $data the data we'd validate against
	 * @param Blackbox_IStateData $state_data the state data we'd use for validation
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->hitRuleEvent(OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, $data, $state_data);
	}

	/**
	 * Doesn't run any rule, but simply returns TRUE.
	 *
	 * This is only here because we're required to implement it. The superclass has it as abstract.
	 *
	 * @param Blackbox_Data $data the data we'd validate against
	 * @param Balckbox_IStateData $state_data the state data we'd use
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
}

?>
