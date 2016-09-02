<?php
/**
 * The look picker class.
 * 
 * This picker is used to pick targets based on look percentage weighting.
 * 
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class OLPBlackbox_LookPicker extends OLPBlackbox_PriorityPicker
{
	/**
	 * Finds a potential winner by the 1st / 2nd / 3rd look percentages
	 * set up in Webadmin2.  The looks are dependent upon the current
	 * frequency score of the application, which is based on the email
	 * address.
	 * 
	 * In order to determine the best winner, we need to go through all
	 * active targets and get their current percentages, then determine
	 * who is furthest from their target percentage to best place the lead.
	 * If the current frequency score is greater than 3, we'll just return NULL.
	 *
	 * @param Blackbox_Data $data data that will can be used for further validation
	 * @param Blackbox_IStateData $state_data data related to the state of the ITarget running this picker
	 * @param array $target_list array of Blackbox_ITargets to pick from
	 * @param array &$snapshot Blackbox snapshot data
	 * @return OLPBlackbox_Target
	 */
	protected function findPotentialWinner(Blackbox_Data $data, Blackbox_IStateData $state_data, array $target_list, &$snapshot)
	{
		// Get our initial winner via standard priority weighting
		$winner = parent::findPotentialWinner($data, $state_data, $target_list, $snapshot);
		
		$accept_ratio = Accept_Ratio_Singleton::getInstance(NULL);
		$current_score = $accept_ratio->getMemScore($data->email_primary) + 1;
		
		$snapshot['frequency_score'] = $current_score;
		
		$index_map = array();
		foreach ($target_list as $index => $target)
		{
			$index_map[$target->getStateData()->campaign_name] = $index;
			$snapshot['targets'][$target->getStateData()->campaign_name]['percentages'] = array();
		}
		
		if ($current_score <= 3 && !empty($winner) && !empty($target_list))
		{
			$percentages = array();
			foreach ($target_list as $index => $target)
			{
				$target_scores = $target->getStateData()->look_percentages;

				$difference = NULL;
				if (!empty($target_scores) && array_sum($target_scores) > 0)
				{
					// Get the current percentages for the target
					// This function will actually return percentages and frequency scores,
					$scores = $accept_ratio->getVendorScores($target->getStateData()->campaign_name);
					
					// Yeah..... the above function returns a bunch of extraneous crap.
					// Indexes 1-3 are for frequency scoring, 4-6 are for look percentages
					$current_percent = $scores[$current_score + 3];
					$target_percent = $target_scores[$current_score - 1];
					
					$difference = intval($target_percent - $current_percent);
				}
				
				if (!is_null($difference))
				{
					// Store the difference
					$percentages[$target->getStateData()->campaign_name] = $difference;
				}
			}
			
			$snapshot['percentages'] = $percentages;
			
			if (!empty($percentages))
			{
				// If the initial winner we chose is below their percentage for this look
				// already, then send them the lead, otherwise, choose the best remaining
				// candidate.  We do this because we still want to honor the priority weighting.
				if (isset($percentages[$winner->getStateData()->campaign_name])
					&& $percentages[$winner->getStateData()->campaign_name] < 0)
				{
					// Sort the percentages so the highest ones are at the top, meaning
					// that the vendors furthest from their percentages are more likely
					// to be chosen.
					asort($percentages);
					
					// It's possible that more than one vendor will be the same distance
					// from its target percentage, so here we'll find all the vendors
					// who are furthest away, then randomly choose one.
					$values = array_values($percentages);
					$keys = array_keys($percentages, $values[0]);
					
					$key = (count($keys) > 1)
							? $keys[mt_rand(0, count($keys) - 1)]
							: $keys[0];
							
					$winner = $target_list[$index_map[$key]];
				}
			}
		}
		
		return $winner;
	}
}
?>
