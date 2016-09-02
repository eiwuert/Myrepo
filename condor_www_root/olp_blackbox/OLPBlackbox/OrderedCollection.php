<?php
/**
 * OLPBlackbox_OrderedCollection runs each target's rules in order, stopping when it finds a target
 * that is valid.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_OrderedCollection extends OLPBlackbox_TargetCollection
{
	/**
	 * The previous target index.
	 * 
	 * This allows us to skip the last target that was valid.
	 *
	 * @var int
	 */
	protected $previous_target = 0;
	
	/**
	 * Checks to see if the targets in this collection are valid and stops checking once it
	 * finds a valid target or if all targets are invalid.
	 *
	 * @param Blackbox_Data $data the data to run validation against
	 * @param Blackbox_IStateData $state_data state data to run validation on
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!$this->have_state_data)
		{
			$this->state_data->addStateData($state_data);
			$this->have_state_data = TRUE;
		}

		if ($this->rules instanceof Blackbox_IRule)
		{
			// Run the rules associated with the collection
			$rules_valid = $this->rules->isValid($data, $this->state_data);

			if (!$rules_valid)
			{
				// If the collection's rules fail, there's no reason to continue
				return FALSE;
			}
		}
		
		$valid = $this->runTargetRules($data);

		return $valid;
	}
	
	/**
	 * Runs the target rules.
	 * 
	 * This is broken out from isValid, because it is also used by pickTarget if a pickTarget returns
	 * FALSE to run the rules on the next target in line.
	 * 
	 * Previous target used to be set the the current target + 1. This would cause the previous target
	 * not to be run again and to move on to the next target. We actually want it to keep repicking the
	 * same target as long as it's valid. It will be up to the calling process to invalidate the target.
	 * OLP does this by default. However, in OrderedCollection:pickTarget(), we actually will set
	 * previous_target to be it's current value + 1 in order to bypass the previous target if it
	 * returns FALSE on pickTarget.
	 *
	 * @param Blackbox_Data $data the data passed for validation
	 * @return bool
	 */
	protected function runTargetRules(Blackbox_Data $data)
	{
		$valid = FALSE;
		$this->valid_list = array();
		
		// Run each target's rules
		for ($i = $this->previous_target; $i < count($this->target_list) && $valid === FALSE; $i++)
		{
			$target = $this->target_list[$i];
			if ($target->isValid($data, $this->state_data))
			{
				/**
				 * In ordered collections, we will only ever have one valid target at a time, but
				 * the rest of the Collection/Target code expects valid_list to be an array. To
				 * avoid any targets being left over, we'll just re-create the array here.
				 */
				$this->valid_list = array($target);
				$this->previous_target = $i;
				$valid = TRUE;
			}
		}
		
		return $valid;
	}
	
	/**
	 * Picks a target from among the valid targets.
	 * 
	 * Returns a Blackbox_IWinner or FALSE on failure.
	 *
	 * @param Blackbox_Data $data the data passed for validation
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		$winner = parent::pickTarget($data);
		
		if ($winner === FALSE)
		{
			$this->previous_target += 1;
			
			$valid = $this->runTargetRules($data);
			
			if ($valid)
			{
				$winner = $this->pickTarget($data);
			}
		}
		
		return $winner;
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
		$string = "OrderedCollection: " . $this->getStateData()->target_collection_name . "\n";
		
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
