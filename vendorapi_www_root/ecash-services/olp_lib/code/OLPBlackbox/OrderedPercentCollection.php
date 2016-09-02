<?php
/**
 * OLPBlackbox_OrderedPercentCollection runs each target's rules in order, stopping when it finds
 * a group of targets with a similar weight that are valid.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_OrderedPercentCollection extends OLPBlackbox_OrderedCollection
{
	/**
	 * Array of targets sorted by weight
	 *
	 * @var array
	 */
	protected $weights = array();

	/**
	 * Current weight being looked at
	 *
	 * @var array
	 */
	protected $current_weight = 0;


	/**
	 * Creates a valid list for the collection
	 * 
	 * @param Blackbox_Data $data the data passed for validation
	 * @return bool
	 */
	protected function runTargetRules(Blackbox_Data $data)
	{
		$valid = FALSE;
		$this->valid_list = array();
		
		if (!empty($this->current_weight))
		{
			unset($this->weights[$this->current_weight]);
		}

		foreach ($this->weights as $weight => $targets)
		{
			foreach ($targets as $target)
			{
				if ($target->isValid($data, $this->state_data))
				{
					$this->valid_list[] = $target;
				}
			}

			if (!empty($this->valid_list))
			{
				$valid = TRUE;
				$this->current_weight = $weight;
				break;
			}

			unset($this->weights[$weight]);
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

		if ($this->picker instanceof OLPBlackbox_Picker)
		{
			// Check the picker to see which ones it's already picked and then
			// remove them from the valid list.  We shouldn't need to pick them again.
			foreach ($this->picker->getPickedTargets() as $picked_campaign)
			{
				$key = array_search($picked_campaign, $this->valid_list, TRUE);
				if (isset($this->valid_list[$key]))
				{
					unset($this->valid_list[$key]);
				}	
			}
		}
		
		return $winner;
	}
	
	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function sleep()
	{
		$data = parent::sleep();
		$weights = array();
		foreach ($this->weights as $weight => $targets)
		{
			$weights[$weight] = array();
			foreach ($targets as $target)
			{
				$weights[$weight][] = $target->getName();
			}
		}
		
		$data['weights'] = $weights;
		$data['current_weight'] = $this->current_weight;
		return $data;
	}
	
	/**
	 * Restore the runtime state from a previous sleep 
	 *
	 * @param array $data Data to restore the object's state
	 * @return void
	 */
	public function wakeup(array $data)
	{
		parent::wakeup($data);
		
		$this->weights = array();
		foreach ($data['weights'] as $weight => $targets)
		{
			$this->weights[$weight] = array();
			foreach ($targets as $target)
			{
				$this->weights[$weight][$target] = $this->getTargetObject($target, TRUE);
			}
		}

		if ($this->valid) $this->valid_list = array_values($this->weights[$data['current_weight']]);
	}

	/**
	 * Override this functionality to build the weights list.
	 *
	 * @param Blackbox_ITarget $target
	 * @return void
	 */
	public function addTarget(Blackbox_ITarget $target)
	{
		parent::addTarget($target);

		if ($target instanceof OLPBlackbox_Campaign)
		{
			$weight = $target->getWeight();
			if (empty($this->weights[$weight]))
			{
				$this->weights[$weight] = array();
			}

			$this->weights[$weight][$target->getName()] = $target;
			ksort($this->weights);
		}
	}
	
}
?>
