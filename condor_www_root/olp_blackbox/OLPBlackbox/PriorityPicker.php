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
	 * Picks a target based on priority weighting.
	 *
	 * @param Blackbox_Data $data data that will can be used for further validation
	 * @param Blackbox_IStateData $state_data data related to the state of the ITarget running this picker
	 * @param array $target_list array of Blackbox_ITargets to pick from
	 * @return Blackbox_IWinner
	 */
	public function pickTarget(Blackbox_Data $data, Blackbox_IStateData $state_data, array $target_list)
	{
		$winner = NULL;

		// we keep our own snapshot data until the very end (after recursive calls to ourself.)
		$snapshot = array('targets' => array(), 'picker_type' => 'priority');
		
		$target = $this->findPotentialWinner($data, $state_data, $target_list, $snapshot);
		
		if (!is_null($target))
		{
			// we will know, logically, if the target was run, but make it explicit
			// in the snapshot so we can parse it easier
			$snapshot['targets'][$target->getStateData()->campaign_name]['run'] = TRUE;
			
			$winner = $target->pickTarget($data);
			$winner_name = $target->getStateData()->campaign_name;
			
			if ($winner === FALSE && $this->repick_on_fail)
			{
				// the winner we picked this run failed, record that for snapshot
				$snapshot['winner_fail'] = $winner_name;
	
				// Unset the failing target
				unset($target_list[array_search($target, $target_list)]);
				$target_list = array_values($target_list);
				$winner = $this->pickTarget($data, $state_data, $target_list);
			}
			else
			{
				$snapshot['winner'] = $winner_name;
			}
		}

		// this must always come after any recursive calls to ourself have finished up
		if (OLPBlackbox_Config::getInstance()->allowSnapshot)
		{
			$this->prepSnapshotData($state_data);
			$state_data->snapshot->stack->append($snapshot);
		}
		
		return is_null($winner) ? FALSE : $winner;
	}
	
	/**
	 * Finds a potential winner.
	 *
	 * @param Blackbox_Data $data data that will can be used for further validation
	 * @param Blackbox_IStateData $state_data data related to the state of the ITarget running this picker
	 * @param array $target_list array of Blackbox_ITargets to pick from
	 * @param array &$snapshot Blackbox snapshot data
	 * @return OLPBlackbox_Target
	 */
	protected function findPotentialWinner(Blackbox_Data $data, Blackbox_IStateData $state_data, array $target_list, &$snapshot)
	{
		$winner = NULL;
		$total = 0;
		
		// Get the total weight
		foreach ($target_list as $target)
		{
			$total += $target->getWeight();
			$snapshot['targets'][$target->getStateData()->campaign_name] = array();
			$snapshot['targets'][$target->getStateData()->campaign_name]['weight'] = $target->getWeight();
			$snapshot['targets'][$target->getStateData()->campaign_name]['run'] = FALSE;
		}

		$snapshot['total_weight'] = $total;
		
		$random = $multiplier = 0;
		list($random, $multiplier) = $this->random($total);
		$snapshot['random'] = $random;
		$snapshot['multiplier'] = $multiplier;
		
		$count = 0;
		$target_count = count($target_list);
		for ($i = 0; $i < $target_count && is_null($winner); $i++)
		{
			$target = $target_list[$i];
			$count += round($target->getWeight() * $multiplier);

			if ($random <= $count)
			{
				$winner = $target;
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
