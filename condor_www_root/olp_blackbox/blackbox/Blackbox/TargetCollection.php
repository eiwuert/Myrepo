<?php
/**
 * Blackbox_TargetCollection class file.
 *
 * @package Blackbox
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

/**
 * Blackbox target collection class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_TargetCollection implements Blackbox_ITarget
{
	/**
	 * This collection's list of targets.
	 *
	 * @var array
	 */
	protected $target_list = array();

	/**
	 * List of targets that are valid
	 *
	 * @var array
	 */
	protected $valid_list = array();

	/**
	 * This collection's rule collection.
	 *
	 * @var Blackbox_IRule
	 */
	protected $rules = NULL;

	/**
	 * Cache the results of the rules for this collection.
	 *
	 * @var bool
	 */
	protected $valid = NULL;

	/**
	 * State information about this collection.
	 *
	 * @var Blackbox_IStateData
	 */
	protected $state_data;
	
	/**
	 * Boolean to tell us if we already have the state data.
	 *
	 * @var bool
	 */
	protected $have_state_data = FALSE;

	/**
	 * Blackbox_TargetCollection constructor.
	 * 
	 * @param Blackbox_IStateData $state_data Default StateData to add
	 */
	public function __construct(Blackbox_IStateData $state_data = NULL)
	{
		$this->initState($state_data);
	}

	/**
	 * Picks a target from the available, valid targets.
	 *
	 * By default, it will pick the first valid target in the list. This needs to be
	 * overwritten.
	 *
	 * @param Blackbox_Data $data data to use for any validation
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		$winner = FALSE;
		
		/**
		 * Refactored to use for loop. We get lazy with foreach and start using breaks. ;P
		 * 
		 * For loops will re-run count() on every run, and since we can unset values, this screws
		 * with things.
		 */
		$count = count($this->valid_list);
		for ($k = 0; $k < $count && $winner === FALSE; $k++)
		{
			$target = $this->valid_list[$k];
			$winner = $target->pickTarget($data);
			
			if ($winner === FALSE)
			{
				unset($this->valid_list[$k]);
			}
		}

		return $winner;
	}

	/**
	 * Called when the rules for this collection have passed (are valid.)
	 * 
	 * Implemented for {@see OLPBlackbox_Vetting_TargetCollection}.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * 
	 * @return void
	 */
	protected function onRulesValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// pass
	}
	
	/**
	 * Called when the rules for this collection have failed.
	 * 
	 * Implemented for {@see OLPBlackbox_Vetting_TargetCollection}.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * 
	 * @return void
	 */
	protected function onRulesInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// pass
	}
	
	/**
	 * When all the targets have been run for isValid, this "event handler" fires.
	 *
	 * This was added for gforge 9922, {@see OLPBlackbox_Vetting_TargetCollection}.
	 * 
	 * @return void
	 */
	protected function onTargetsRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// pass
	}
	
	/**
	 * Runs all rules for available targets and returns if the collection is still valid.
	 *
	 * If no targets are left valid after running through them all, then this will return FALSE.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data state data to use for validation
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = TRUE;
		$this->valid_list = array();
		
		// If we already have the state data, don't add it again.
		if (!$this->have_state_data)
		{
			// We need to add the previous state data to our own so further on down the line we have
			// access to that info.
			$this->state_data->addStateData($state_data);
			$this->have_state_data = TRUE;
		}

		if ($this->rules instanceof Blackbox_IRule)
		{
			if (!is_null($this->valid))
			{
				// If we've already set validity, no reason to re-run the rules
				$valid = $this->valid;
			}
			else
			{
				// Run the rules associated with the collection
				$valid = $this->rules->isValid($data, $this->state_data);
				$this->valid = $valid;
				if ($this->valid)
				{
					$this->onRulesValid($data, $state_data);
				}
				else
				{
					$this->onRulesInvalid($data, $state_data);
				}
			}
		}

		if ($valid)
		{
			// Run each target's rules
			foreach ($this->target_list as $target)
			{
				if ($target->isValid($data, $this->state_data))
				{
					// If one target is valid, the collection is valid
					$this->valid_list[] = $target;
				}
			}
			
			$valid = !empty($this->valid_list);
			
			$this->onTargetsRun($data, $state_data);
		}

		return $valid;
	}

	/**
	 * Adds a target to the collection.
	 *
	 * @param Blackbox_ITarget $target the target to add to this collection
	 * @return void
	 */
	public function addTarget(Blackbox_ITarget $target)
	{
		$this->target_list[] = $target;
	}

	/**
	 * Sets the rule collection for this target collection.
	 *
	 * @param Blackbox_IRule $rules a rules collection object containing collection rules
	 * @return void
	 */
	public function setRules(Blackbox_IRule $rules)
	{
		$this->rules = $rules;
	}

	/**
	 * Initializes the state information for this TargetCollection
	 * 
	 * @param Blackbox_IStateData $state_data StateData to add to the state
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{
		$this->state_data = new Blackbox_StateData();
		
		if (!is_null($state_data))
		{
			$this->state_data->addStateData($state_data);
		}
	}
	
	/**
	 * Returns the rules set for this object.
	 *
	 * @return Blackbox_IRule
	 */
	public function getRules()
	{
		return $this->rules;
	}
	
	/**
	 * Returns the target's state data.
	 *
	 * @return Blackbox_StateData
	 */
	public function getStateData()
	{
		return $this->state_data;
	}
	
	/**
	 * Allows you to get a nice pretty print out of the entire blackbox
	 * tree instead of having to do a print_r, or similar, and get the entire
	 * structure dumped to the screen.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		$string = "TargetCollection: " . $this->getStateData()->target_collection_name . "\n";
		
		if ($this->rules)
		{
			$string .= preg_replace('/^/m', '   ', strval($this->rules));
		}
		foreach ($this->target_list as $target)
		{
			$string .= preg_replace('/^/m', '   ', strval($target));
		}
		return $string;
	}
}
?>
