<?php

/**
 * Ordered picker which remembers it's choice. (Thereby 'invalidating' the others.)
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_OrderedPicker extends OLPBlackbox_Picker
{
	/**
	 * Remember the target we picked the first time and pick it until it's gone.
	 *
	 * @var ITarget object
	 */
	protected $picked_target = NULL;
	
	/**
	 * Constructor used to remove the "picker" argument from parent's constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		// pass, do not require picker argument like parent
	}
	/**
	 * 
	 * @param Blackbox_Data $data data that will can be used for further validation 
	 * @param Blackbox_IStateData $state_data data for the ITarget running using this picker 
	 * @param array $targets an array of Blackbox_ITargets to pick from 
	 * @return Blackbox_ITarget 
	 * @see OLPBlackbox_IPicker::pickTarget()
	 */
	public function pickTarget(Blackbox_Data $data, Blackbox_IStateData $state_data, array $targets)
	{
		$winner = FALSE;
		
		$snapshot = array('targets' => array(), 'picker_type' => 'vetting_percent');

		// save in snapshot all the potential targets
		foreach ($targets as $target)
		{
			$snapshot['targets'][$target->getStateData()->name] = array();
		}
		
		if ($this->picked_target instanceof Blackbox_ITarget)
		{
			$repicked_name = $this->picked_target->getStateData()->name;  
			$winner = $this->picked_target->pickTarget($data);
			$event = ($winner === FALSE) ? 'repick_winner_fail' : 'repick_winner';
			$snapshot[$event] = $repicked_name;
		}
		else 
		{
			foreach ($targets as $target)
			{
				$target_name = $target->getStateData()->campaign_name;
				$winner = $target->pickTarget($data);
				if ($winner)
				{
					$snapshot['targets'][$target_name]['original_winner'] = TRUE;
					$this->picked_target = $target;
					break;
				}
				else
				{
					$snapshot['targets'][$target_name]['lost'] = TRUE;
				}
			}
		}
		
		// save snapshot in state data (owned by Blackbox itself.)
		if (OLPBlackbox_Config::getInstance()->allowSnapshot)
		{
			$this->prepSnapshotData($state_data);
			$state_data->snapshot->stack->append($snapshot);
		}
		return $winner;
	}
}

?>
