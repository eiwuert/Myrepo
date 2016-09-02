<?php
/**
 * OLPBlackbox rule for running suppression lists.
 *
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
		parent::__construct();
	}
	
	/**
	 * Run when the rule passes.
	 *
	 * @param Blackbox_Data       $data       the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->hitRuleEvent($this->getResult(), $data, $state_data);
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
		
		if ($result)
		{
			$result = strtolower($result);
			
			// Hit the individual suppression list failure
			$this->hitBBStat('suppression_' .  $this->list->Field() . $result, $data, $state_data);
			
			// Hit the global suppression list failure
			$this->hitRuleStat($result, $data, $state_data);
		}
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
