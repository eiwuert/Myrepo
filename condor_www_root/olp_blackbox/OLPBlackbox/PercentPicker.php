<?php
/**
 * The percentage picker class.
 * 
 * This picker is used to pick targets based on percentage weighting. Percentage weighting uses
 * the target's percentage of leads to determine who gets the next lead.
 * 
 * Example:
 * Assume the following targets have their stated percentage weights and current leads for today.
 * 
 * Target - Percent - Current # of Leads
 * UFC    - 25%     - 249
 * CA     - 50%     - 500
 * UCL    - 25%     - 250
 * Total            - 999
 * 
 * We'll find the difference between each target's percentage and where they are currently. This
 * would come out to:
 * 
 * Target - Current % - Difference to actual %
 * UFC    - 24.9%     - -0.1
 * CA     - 50.1%     - 0.1
 * UCL    - 25.0%     - 0.0
 * 
 * Whoever has the lowest diffence will get the lead next. If targets are tied, whoever is
 * first in the list will get the lead. In the examples above, UFC would get the next lead.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_PercentPicker extends OLPBlackbox_Picker
{
	/**
	 * Picks a target based on percentage weighting.
	 *
	 * @param Blackbox_Data $data data that will can be used for further validation
	 * @param Blackbox_StateData $state_data state data for the ITarget using this picker object
	 * @param array $target_list array of Blackbox_ITargets to pick from
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data, Blackbox_IStateData $state_data,  array $target_list)
	{
		// we'll collect our own snapshot data and only append it to the snapshot 
		// data in $state_data after all recursive calls to ourself have been done
		$snapshot = array('targets' => array(), 'picker_type' => 'percent');

		$winner = FALSE;
		$total_weight = 0;
		
		$this->saveTargetNamesToSnapshot($target_list, $snapshot);
		
		$target = $this->getLowestPercentTarget($target_list, $snapshot);
		
		if ($target instanceof Blackbox_ITarget)
		{
			$winner = $target->pickTarget($data);

			if ($winner === FALSE && $this->repick_on_fail)
			{
				$snapshot['winner_fail'] = $target->getStateData()->capaign_name;
				unset($target_list[array_search($target, $target_list, TRUE)]);
				$target_list = array_values($target_list);
				$winner = $this->pickTarget($data, $state_data, $target_list);
			}
			else
			{
				$snapshot['winner'] = $target->getStateData()->campaign_name;
			}
		}

		// this must always come after any recursive calls to ourself have finished up
		if (OLPBlackbox_Config::getInstance()->allowSnapshot)
		{
			$this->prepSnapshotData($state_data);
			$state_data->snapshot->stack->append($snapshot);
		}
		
		return $winner;
	}
		
	protected function saveTargetNamesToSnapshot($target_list, &$snapshot)
	{
		foreach ($target_list as $target)
		{
			// when looking at a snapshot, we'd like to see all potential targets
			$snapshot['targets'][$target->getStateData()->campaign_name] = array();
		}
	}
	
	protected function getLowestPercentTarget(array $target_list, &$snapshot)
	{
		$total_leads = 0;
		
		foreach ($target_list as $target)
		{
			if (!$target instanceof Blackbox_ITarget)
			{ 
				throw new InvalidArgumentException(sprintf(
					'cannot pick from non-targets (received %s)',
					var_export($target, true))
				);
			}
			$total_real_leads += $target->getCurrentLeads();
			$total_weight += $target->getWeight();
		}
		
		if ($total_weight < 100)
		{
			$total_leads = $total_real_leads / ($total_weight / 100);
		}
		else
		{
			$total_leads = $total_real_leads;
		}

		$snapshot['total_leads'] = $total_leads;
		$snapshot['total_real_leads'] = $total_real_leads;
		
		// lowest percent should just be higher than the initial item's percent
		// could ever be so it's always picked
		$lowest_percent = 1000;
		$lowest_target = NULL;
		
		$target_count = count($target_list);
		for ($i = 0; $i < $target_count; $i++)
		{
			$target = $target_list[$i];
			$target_name = $target->getStateData()->campaign_name;
			$current_leads = $target->getCurrentLeads();
			$weight = $target->getWeight();
			
			$percent = $total_leads > 0 ? round(($current_leads / $total_leads) * 100, 3) : $weight;
			
			// Compute the difference betwen their calculated percentage and their weight percentage
			$difference = $percent - $weight;
			
			// save relevant snapshot info
			$snapshot['targets'][$target_name]['weight'] = $weight;
			$snapshot['targets'][$target_name]['leads'] = $current_leads;
			$snapshot['targets'][$target_name]['percent'] = $percent;
			$snapshot['targets'][$target_name]['difference'] = $difference;
			
			/**
			 * This is < for a reason. If it was <= it would pick the last target that matched
			 * the lowest percentage. We want the first one.
			 */
			if ($difference < $lowest_percent)
			{
				$lowest_percent = $difference;
				$lowest_target = $i;
			}
		}
		
		if (!is_null($lowest_target))
		{
			return $target_list[$lowest_target];
		}
		
		return FALSE;
	}
}
?>
