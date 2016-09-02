<?php
/**
 * OLPBlackbox rule for running suppression lists.
 *
 * @package OLPBlackbox
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Rule_Suppression extends OLPBlackbox_Rule
{
	/**
	 * The suppression list object.
	 *
	 * @var Suppress_List
	 */
	protected $list;
	
	/**
	 * Stores whether the list is valid.
	 *
	 * @var bool
	 */
	protected $valid;
	
	/**
	 * The result of the list.
	 * 
	 * What this is can change depending on what type of list is being run.
	 *
	 * @var string
	 */
	protected $result;
	
	/**
	 * OLPBlackbox_Rule_Suppression_Exclude constructor.
	 *
	 * @param Suppress_List $list the suppression list object
	 */
	public function __construct(Suppress_List $list)
	{
		$this->list = $list;
		$this->setStatName('suppress_lists');
		$this->event_name = sprintf(
			'LIST_%s_%s_%u',
			strtoupper($this->listType()),
			strtoupper($this->list->Field()),
			$this->list->ID()
		);
		parent::__construct();
	}
	
	/**
	 * Mandate subclasses report what type they are. (e.g. 'EXCLUDE')
	 *
	 * This is required for {@see OLPBlackbox_FailureReason} purposes.
	 * 
	 * @return string
	 */
	abstract public function listType();
	
	/**
	 * Run when the rule passes.
	 *
	 * @param Blackbox_Data       $data       the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->getConfig()->log_lists && $this->isConfiguredToLogPasses($state_data))
		{
			$this->hitRuleEvent($this->getResult(), $data, $state_data);
		}
		
		$this->triggerEvents(__FUNCTION__, $state_data);
	}

	/**
	 * Run when the rule fails.
	 *
	 * @param Blackbox_Data       $data       the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$result = $this->getResult();
		$this->hitRuleEvent($result, $data, $state_data);

		$this->setGlobalRuleFailureOnInvalid($state_data); // inherited from parent class

		if ($result)
		{
			$result = strtolower($result);
			
			// Hit the individual suppression list failure
			$this->hitBBStat('suppression_' .  $this->list->Field() . $result, $data, $state_data);
			
			// Hit the global suppression list failure
			$this->hitRuleStat($result, $data, $state_data);
		}
		
		// If the factory has decided we need to log failure reasons, do so here.
		if ($state_data->failure_reasons instanceof OLPBlackbox_FailureReasonList)
		{
			$failure = new OLPBlackbox_FailureReason_Suppression(
				$this->list->Name(),
				$this->listType(),
				$this->list->Field(),
				$this->getDataValue($data)
			);
			$state_data->failure_reasons->add($failure);
		}
		
		$this->triggerEvents(__FUNCTION__, $state_data);
	}
	
	/**
	 * Returns the result or NULL if the rule hasn't been run.
	 *
	 * @return string
	 */
	public function getResult()
	{
		return $this->result;
	}
}
?>
