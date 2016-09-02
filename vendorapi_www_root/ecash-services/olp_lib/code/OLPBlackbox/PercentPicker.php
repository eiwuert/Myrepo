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
	 * Returns the target furthest from their percentage goal
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return OLPBlackbox_ITarget
	 */
	protected function getNextTarget(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$total_leads = 0;
		$total_real_leads = 0;
		$total_weight = 0;
	
		foreach ($this->pickable as $target)
		{
			if (!$target instanceof Blackbox_ITarget)
			{ 
				throw new InvalidArgumentException(sprintf(
					'cannot pick from non-targets (received %s)',
					var_export($target, TRUE))
				);
			}
			$this->snapshot['targets'][$target->getStateData()->campaign_name]['run'] = FALSE;

			$total_real_leads += $target->getCurrentLeads();
			$total_weight += $target->getWeight();
		}

		if ($total_weight < 100 && $total_weight != 0)
		{
			$total_leads = $total_real_leads / ($total_weight / 100);
		}
		else
		{
			$total_leads = $total_real_leads;
		}

		$this->snapshot['total_leads'] = $total_leads;
		$this->snapshot['total_real_leads'] = $total_real_leads;
		
		// lowest percent should just be higher than the initial item's percent
		// could ever be so it's always picked
		$lowest_percent = 1000;
		$lowest_target = array();
		foreach ($this->pickable as $i => $target)
		{
			$target_name = $target->getStateData()->campaign_name;
			$current_leads = $target->getCurrentLeads();
			$weight = $target->getWeight();
		
			$percent = $total_leads > 0 ? round(($current_leads / $total_leads) * 100, 3) : $weight;
			
			// Compute the difference betwen their calculated percentage and their weight percentage
			$difference = $percent - $weight;
			
			// save relevant snapshot info
			$this->snapshot['targets'][$target_name]['weight'] = $weight;
			$this->snapshot['targets'][$target_name]['leads'] = $current_leads;
			$this->snapshot['targets'][$target_name]['percent'] = $percent;
			$this->snapshot['targets'][$target_name]['difference'] = $difference;

			/**
			 * This is < for a reason. If it was <= it would pick the last target that matched
			 * the lowest percentage. We want the first one.
			 */
			if ($difference <= $lowest_percent)
			{
				if ($difference == $lowest_percent)
				{
					$lowest_target[$i] = $weight;
				}
				else
				{
					$lowest_percent = $difference;
					$lowest_target = array($i => $weight);
				}
			}
		}

		if (!empty($lowest_target))
		{
			$index = (count($lowest_target) > 1)
					? $this->getRandomIndex($lowest_target)
					: NULL;

			if ($index === NULL)
			{
				$index = array_pop(array_keys($lowest_target));
			}
			
			$next_target = array_pop(array_splice($this->pickable, $index, 1));
			$this->snapshot['targets'][$next_target->getName()]['run'] = TRUE;
			return $next_target;
		}
		
		return FALSE;
	}
	
	/**
	 * Gets a random winner from an array based on weight 
	 *
	 * @param array $lowest_target
	 * @return int Index of winner
	 */
	protected function getRandomIndex($lowest_target)
	{
		$index = NULL;

		$total = 0;
		$choices = array();
		$rand = $this->getRandomNumber(array_sum($lowest_target));
		foreach ($lowest_target as $i => $weight)
		{
			$total += $weight;
			$choices[$this->pickable[$i]->getName()] = $weight;

			if ($index === NULL && $total >= $rand)
			{
				$index = $i;
			}
		}
		
		$this->snapshot['tiebreaker'] = array(
			'sum' => array_sum($lowest_target),
			'rand' => $rand,
			'choices' => $choices,
		);

		$this->snapshot['tiebreaker']['winner'] = ($index !== NULL)
			? $this->pickable[$index]->getName()
			: 'No winner found; index empty!';
		
		return $index;
	}
	
	/**
	 * Returns a random number
	 *
	 * @param int $max Max number
	 * @return int
	 */
	protected function getRandomNumber($max)
	{
		return mt_rand(1, $max);
	}
}
?>
