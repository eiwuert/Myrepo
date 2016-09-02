<?php
/**
 * Abstract class for picker.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Picker implements OLPBlackbox_IPicker
{
	/**
	 * Determines whether a picker will re-pick if the target returns FALSE on pickTarget().
	 *
	 * @var bool
	 */
	protected $repick_on_fail;
	
	/**
	 * Keeps track of whether this picker has been invalidated which causes it
	 * to stop picking and return FALSE.
	 *
	 * @var bool
	 */
	protected $valid = TRUE;
	
	/**
	 * Array of winners who have been picked on this run.
	 *
	 * @var array
	 */
	protected $picked = array();

	/**
	 * Array of targets that are still pickable
	 *
	 * @var array
	 */
	protected $pickable = array();

	/**
	 * Current target in process.  This is helpful for determining the state of a picker when
	 * it halts processing during a pick for things like rework exceptions
	 *
	 * @var Blackbox_ITarget
	 */
	protected $current_target;

	/**
	 * The winner found by pickWinner
	 *
	 * @var Blackbox_IWinner|bool
	 */
	protected $winner;

	/**
	 * Snapshot entries
	 *
	 * @var array
	 */
	protected $snapshot;

	/**
	 * Does the picker need to restore its state when it runs next
	 *
	 * @var bool
	 */
	protected $restore_needed = FALSE;

	/**
	 * Restore data for future restore
	 *
	 * @var array
	 */
	protected $restore_data;
	
	/**
	 * OLPBlackbox_Picker constructor.
	 *
	 * @param bool $repick whether a picker will re-pick on target failure, on by default
	 */
	public function __construct($repick = TRUE)
	{
		$this->repick_on_fail = $repick;

		$this->snapshot = array(
			'targets' => array(),
			'picker_type' => get_class($this),
		);
	}

	/**
	 * Picks a target based on percentage weighting.
	 *
	 * @param Blackbox_Data $data data that will can be used for further validation
	 * @param Blackbox_StateData $state_data state data for the ITarget using this picker object
	 * @param array $target_list array of Blackbox_ITargets to pick from
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data, Blackbox_IStateData $state_data, array $target_list)
	{
		// Restore the state if necessary before picking the target
		$this->setupState($target_list);

		// we'll collect our own snapshot data and only append it to the snapshot 
		// data in $state_data after all recursive calls to ourself have been done

		$this->winner = FALSE;
		$total_weight = 0;
		
		$this->saveTargetNamesToSnapshot($target_list);

		do
		{
			// If we do not have a current target set by another process, get the next target
			if (is_null($this->current_target))
			{
				$this->current_target = $this->getNextTarget($data, $state_data);

				// If we got a target, do before target look
				if (!empty($this->current_target))
				{
					$this->beforeTargetLook($this->current_target, $data);
				}
			}

			// If we did not get a valid target or we've been invalidated, exit the loop
			if (empty($this->current_target) || $this->valid === FALSE) break;
			
			$this->snapshot['targets'][$this->current_target->getName()]['run'] = TRUE;

			$this->winner = $this->current_target->pickTarget($data);
		
			if ($this->winner)
			{
				$this->snapshot['winner'] = $this->current_target->getName();
				break;  // We have a winner...exit the loop
			}

			// Reset the current target as we have a winner response
			$this->current_target = NULL;
		}
		while ($this->repick_on_fail && count($this->pickable) > 0);
		
		// this must always come after any recursive calls to ourself have finished up
		if (OLPBlackbox_Config::getInstance()->allowSnapshot)
		{
			$this->prepSnapshotData($state_data);
			$state_data->snapshot->stack->append($this->snapshot);
		}
		return $this->valid ? $this->winner : FALSE;
	}

	/**
	 * Get the next viable target and remove that target from the pickable targets
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return Blackbox_ITarget
	 */
	abstract protected function getNextTarget(Blackbox_Data $data, Blackbox_IStateData $state_data);
	
	/**
	 * Marks the picker invalid, meaning it will stop picking items and not return
	 * a winner.
	 *
	 * @return void
	 */
	public function setInvalid()
	{
		$this->valid = FALSE;
	}
	
	/**
	 * Makes sure the appropriate data structure exists in a Blackbox_IStateData object.
	 * 
	 * @param Blackbox_IStateData $state_data state object we'd like to store snapshot info in 
	 * @return void
	 */
	public function prepSnapshotData(Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
		
		// only add snapshot to state data if it hasn't been prepped yet.
		if ($config->allowSnapshot && is_null($state_data->snapshot))
		{
			$state_data->snapshot = new stdClass();
			$state_data->snapshot->debug = $config->debug->getFlags();
			if ($config->force_winner)
			{
				$state_data->snapshot->force_winner = $config->force_winner;
			}
			$state_data->snapshot->stack = new ArrayObject();
		}
	}
	
	/**
	 * Adds a target to the picked array.
	 *
	 * @param Blackbox_ITarget $target Target to add
	 * @param Blackbox_Data $data Blackbox data
	 * @return NULL
	 */
	protected function addPickedTarget(Blackbox_ITarget $target, Blackbox_Data $data)
	{
		$this->picked[] = $target;
		
		$this->incrementFrequencyScore($target);
	}

	/**
	 * Increment the frequency score for a target
	 *
	 * @param Blackbox_ITarget $target
	 * @return void
	 */
	protected function incrementFrequencyScore($target)
	{
		// Grab the frequency score before we pick the winner.  Needs to be
		// incremented by one since it hasn't been 'officially' incremented
		// until the pickTarget rules are run
		$freq_object = new OLP_FrequencyScore(
			$this->getConfig()->olp_db->getConnection()->getConnection(),
			$this->getConfig()->memcache
		);
		
		$target->getStateData()->frequency_score = $freq_object->getMemScore($data->email_primary) + 1;
	}
	
	/**
	 * Returns the picked targets.
	 *
	 * @return array Array of Blackbox_ITargets
	 */
	public function getPickedTargets()
	{
		return $this->picked;
	}
	
	/**
	 * Resets the picked targets array
	 * 
	 * @return NULL
	 */
	public function resetPickedTargets()
	{
		$this->picked = array();
	}
	
	/**
	 * Saves target names to the Blackbox snapshot
	 *
	 * @param array $target_list List of active targets
	 * @return void
	 */
	protected function saveTargetNamesToSnapshot($target_list)
	{
		foreach ($target_list as $target)
		{
			// when looking at a snapshot, we'd like to see all potential targets
			$this->snapshot['targets'][$target->getName()] = array();
		}
	}
	
	/**
	 * Adds extra functionality before a target is picked.  Used 
	 * in overridden classes.
	 *
	 * @param Blackbox_ITarget $target The picked target
	 * @param Blackbox_Data $data data that will can be used for further validation
	 * @return void
	 */
	protected function beforeTargetLook(Blackbox_ITarget $target, Blackbox_Data $data)
	{
		$this->addPickedTarget($target, $data);
	}
	
	/**
	 * Returns an instance of OLPBlackbox_Config.
	 *
	 * @return OLPBlackbox_Config
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}

	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function sleep()
	{
		$data = array(
			'repick_on_fail' => $this->repick_on_fail,
			'picked' => $this->getNamesFromCampaigns($this->picked),
			'pickable' => $this->getNamesFromCampaigns($this->pickable),
			'current_target' => empty($this->current_target) ? $this->current_target : $this->current_target->getName(),
		);
		
		return $data;
	}

	/**
	 * Schedule a restore for teh next time pickTarget runs
	 *
	 * @param array $data Data to restore the object's state
	 * @return void
	 */
	public function wakeup(array $data)
	{
		$this->restore_data = $data;
		$this->restore_needed = TRUE;
	}

	/**
	 * Setup the state if the picker based on the target list and any wakeup changes needed
	 *
	 * @param array $target_list
	 * @return void
	 */
	public function setupState($target_list)
	{
		if ($this->restore_needed)
		{
			$data = $this->restore_data;
			$this->repick_on_fail = $data['repick_on_fail'];
			$this->setPickable($this->getCampaignsFromNames($data['pickable'], $target_list));
			$this->picked = $this->getCampaignsFromNames($data['picked'], $target_list);
			
			if (!empty($data['current_target']))
			{
				$this->current_target = $this->getCampaignFromName($data['current_target'], $target_list);
			}

			// Restore was successful, turn the flag off
			$this->restore_needed = FALSE;
			$this->restore_data = NULL;
		}
		else
		{
			$this->setPickable($target_list);
		}
	}
	
	/**
	 * Sets the pickable field
	 * 
	 * @param array $target_list
	 * @return void
	 */
	protected function setPickable(array $target_list)
	{
		$this->pickable = $target_list;
	}
	
	/**
	 * Returns array of pickable targets.
	 * 
	 * @return array
	 */
	public function getPickable()
	{
		return $this->pickable;
	}

	/**
	 * Get an array of campaign names from an array of campaigns
	 *
	 * @param array $campaigns
	 * @return array
	 */
	protected function getNamesFromCampaigns(array $campaigns)
	{
		$names = array();
		foreach ($campaigns as $campaign)
		{
			$names[] = $campaign->getName();
		}
		return $names;
	}

	/**
	 * Get an array of campaigns from an array of names using the provided campaign list
	 *
	 * @param array $names
	 * @param array $campaign_list
	 * @return array
	 */
	protected function getCampaignsFromNames(array $names, array $campaign_list)
	{
		$campaigns = array();
		foreach ($names as $name)
		{
			$campaign = $this->getCampaignFromName($name, $campaign_list);
			if (!empty($campaign))
			{
				$campaigns[] = $campaign;
			}
		}
		return $campaigns;
	}

	/**
	 * Get a campaign by its name using the provided campaign list
	 *
	 * @param string $name
	 * @param array $campaign_list
	 * @return OLPBlackbox_Campaign
	 */
	protected function getCampaignFromName($name, array $campaign_list)
	{
		foreach ($campaign_list as $campaign)
		{
			$campaign_name = $campaign->getName();
			if ($campaign_name == $name)
			{
				return $campaign;
			}
		}
		$this->log("Picker unable to get campaign from name $name");
		return NULL;
	}

	/**
	 * Log text to the applog in the config
	 *
	 * @param string $text
	 * @return void
	 */
	protected function log($text)
	{
		if ($this->getConfig()->applog)
		{
			$this->getConfig()->applog->Write($text);
		}
	}
}
?>
