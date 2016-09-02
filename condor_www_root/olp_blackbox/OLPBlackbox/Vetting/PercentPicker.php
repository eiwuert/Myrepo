<?php

/**
 * Percent Picker that remembers it's first choice and repicks that every time.
 * 
 * The purpose of this picker is to, conceptual, invalidate all members of a 
 * collection other than the one that is picked first. This is accomplished by
 * this picker repicking the same target every time once the initial choice is 
 * made.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_PercentPicker extends OLPBlackbox_PercentPicker
{
	/**
	 * The first target selected, which will be repicked to "invalidate" other targets.
	 *
	 * Vetting pickers 
	 * 
	 * @var Blackbox_ITarget
	 */
	protected $picked_target = NULL;
	
	/**
	 * Remove the expected parameter from our parent.
	 * 
	 * @return void
	 */
	function __construct()
	{
		// pass, we don't use the parent's repick option
	}
	
	/**
	 * Pick a target and then repick that target exclusively forever.
	 * 
	 * @param Blackbox_Data $data data that will can be used for further validation 
	 * @param Blackbox_IStateData $state_data data for the ITarget running using this picker 
	 * @param array $target_list an array of Blackbox_ITargets to pick from
	 *  
	 * @return Blackbox_ITarget
	 *  
	 * @see OLPBlackbox_IPicker::pickTarget()
	 */
	public function pickTarget(Blackbox_Data $data, Blackbox_IStateData $state_data, array $target_list)
	{
		$snapshot = array('targets' => array(), 'picker_type' => 'vetting_percent');
		
		$winner = FALSE;
		
		// save in snapshot all the potential targets
		$this->saveTargetNamesToSnapshot($target_list, $snapshot);
		
		if ($this->picked_target instanceof Blackbox_ITarget)
		{
			$repicked_name = $this->picked_target->getStateData()->campaign_name;
			$winner = $this->picked_target->pickTarget($data);
			$event = ($winner === FALSE) ? 'repick_winner_fail' : 'repick_winner';
			$snapshot[$event] = $repicked_name;
		}
		else
		{
			// we can't really call the parent here because it returns an
			// IWinner and we need to store an ITarget
			$target = $this->getLowestPercentTarget($target_list, $snapshot);
			
			if ($target instanceof Blackbox_ITarget)
			{
				$winner = $target->pickTarget($data);
	
				if ($winner === FALSE && $this->repick_on_fail)
				{
					$snapshot['winner_fail'] = $target->getStateData()->campaign_name;
					unset($target_list[array_search($target, $target_list, TRUE)]);
					$target_list = array_values($target_list);
					$winner = $this->pickTarget($data, $state_data, $target_list);
				}
				else
				{
					$this->picked_target = $target;
					$snapshot['winner'] = $target->getStateData()->campaign_name;
				}
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
}

?>
