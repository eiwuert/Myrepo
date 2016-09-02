<?php
/**
 * This picker will pick in order, but if two or more targets are given the same weight,
 * it will create a PercentPicker to choose between them randomly.
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_OrderedpercentPicker extends OLPBlackbox_Picker
{
	/**
	 * List of weights and their associated targets.
	 *
	 * @var array
	 */
	protected $weights = array();

	/**
	 * Get the next viable target and remove that target from the pickable targets
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return Blackbox_ITarget
	 */
	protected function getNextTarget(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$picked_target = NULL;

		if (!empty($this->weights) && !empty($this->pickable))
		{
			reset($this->weights);
			$picked_weight = key($this->weights);
			$picked_choices = $this->weights[$picked_weight];

			$this->snapshot['picked_weight'] = $picked_weight;
			$this->snapshot['possible_choices'] = array_keys($picked_choices);

			$keys = array_keys($picked_choices);
			$picked_key = $keys[mt_rand(0, count($picked_choices) - 1)];

			$picked_target = $picked_choices[$picked_key];
			$this->snapshot['picked_target'] = $picked_target->getName();

			unset($this->weights[$picked_weight][$picked_key]);
			if (count($picked_choices) == 1)
			{
				unset($this->weights[$picked_weight]);
			}

			$pickable_key = array_search($picked_target, $this->pickable, TRUE);
			if (isset($this->pickable[$pickable_key]))
			{
				unset($this->pickable[$pickable_key]);
			}
		}

		return $picked_target;
	}
	
	/**
	 * Sets the pickable field
	 * 
	 * @param array $target_list
	 * @return void
	 */
	protected function setPickable(array $target_list)
	{
		$this->weights = array();
		$snapshot = array();
		foreach ($target_list as $target)
		{
			$weight = $target->getWeight();
			if (empty($this->weights[$weight]))
			{
				$this->weights[$weight] = array();
				$snapshot[$weight] = array();
			}
			
			$this->weights[$weight][$target->getName()] = $target;
			$snapshot[$weight][] = $target->getName();
		}
		
		ksort($this->weights);
		$this->snapshot['weights'] = $snapshot;

		parent::setPickable($target_list);
	}
}

?>
