<?php
/**
 * The priority picker class.
 * 
 * This picker is used to pick targets based on priority weighting.
 * 
 * @todo Describe what priority weighting does
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_PriorityPicker extends OLPBlackbox_Picker
{
	/**
	 * Get the next viable target and remove that target from the pickable targets
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return Blackbox_ITarget
	 */
	protected function getNextTarget(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$winner = FALSE;
		$total = 0;
		
		// Get the total weight
		foreach ($this->pickable as $target)
		{
			$total += $target->getWeight();
			$this->snapshot['targets'][$target->getStateData()->campaign_name] = array();
			$this->snapshot['targets'][$target->getStateData()->campaign_name]['weight'] = $target->getWeight();
			$this->snapshot['targets'][$target->getStateData()->campaign_name]['run'] = FALSE;
		}

		$this->snapshot['total_weight'] = $total;
		
		$random = $multiplier = 0;
		list($random, $multiplier) = $this->random($total);
		$this->snapshot['random'] = $random;
		$this->snapshot['multiplier'] = $multiplier;
		
		$count = 0;
		foreach ($this->pickable as $i => $target)
		{
			$count += round($target->getWeight() * $multiplier);

			if ($random <= $count)
			{
				$winner = $target;
				unset($this->pickable[$i]);
				break;
			}
		}

		return $winner;
	}
	
	/**
	 * Returns an array containing a random number for the picker and a multiplier.
	 *
	 * @param int $total the total of all priorities
	 * @return array
	 */
	protected function random($total)
	{
		$multiplier = 10;
		$random = mt_rand(1, $multiplier * $total);
		return array($random, $multiplier);
	}
}
?>
