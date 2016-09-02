<?php
/**
 * An OLP Blackbox Campaign decorator class for Targets.
 *
 * This class encapsulates a target class and provides the ability for the same
 * target instance to have different values for a subset of rules. An example of
 * this, would be if we had two identical targets, with the same business rules,
 * but they wanted it to have different limits depending on where it resdided in
 * the tree.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class OLPBlackbox_Campaign extends OLPBlackbox_Target
{
	/**
	 * The target for this campaign.
	 *
	 * @var OLPBlackbox_ITarget
	 */
	protected $target;

	/**
	 * The rules associated with this campaign.
	 *
	 * @var Blackbox_RuleCollection
	 */
	protected $rules;

	/**
	 * State data for this campaign.
	 *
	 * @var Blackbox_IStateData
	 */
	protected $state_data;

	/**
	 * The name of the campaign.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Campaign's ID.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * The weight of the campaign.
	 *
	 * @var int
	 */
	protected $weight;
	
	/**
	 * @var boolean
	 */
	protected $invalidated;
	
	/**
	 * Creates a campaign.
	 *
	 * @param string $property_short The name of the campaign.
	 * @param int $id The ID for the campaign from the database.
	 * @param int $weight The weight of the target, used by the parent collection
	 * in picking targets as winners.
	 * @param Blackbox_ITarget $target The target to wrap in this Campaign.
	 * @param Blackbox_IRule $rules The rules for this campaign to run
	 * when isValid() is called.
	 */
	public function __construct(
		$property_short,
		$id = NULL,
		$weight = NULL,
		Blackbox_ITarget $target = NULL,
		Blackbox_IRule $rules = NULL,
		Blackbox_IStateData $state_data = NULL)
	{
		$this->name = $property_short;
		$this->id = (int)$id;
		$this->weight = $weight;
		$this->target = $target;
		if (empty($rules))
		{
			//If there are no rules we still have to set up a dummy rule_collection
			$rules = new OLPBlackbox_RuleCollection();
		}
		$this->rules = $rules;
		
		$this->initState($state_data);
	}

	/**
	 * Add target and campaign level witheld target values to $data
	 *
	 * @param Blackbox_Data $data
	 * @return void
	 */
	public function addWithheldTargets(Blackbox_Data $data)
	{
		$this->getTarget()->addWithheldTargets($data);
		parent::addWithheldTargets($data);
	}

	/**
	 * Checks to see if this campaign and the target associated with this campaign is valid.
	 *
	 * @param Blackbox_Data $data the data to do validation on
	 * @param Blackbox_IStateData $state_data state data to do validation on
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->invalidated)
		{
			return FALSE;
		}
		try
		{
			if (!$this->target instanceof Blackbox_ITarget)
			{
				throw new Blackbox_Exception("Campaign's target was not set.");
			}
			
			$this->copyRelevantStateData($this->target->getStateData());
			
			// Run the campaign rules (call parent and pass state data we got as parameter)
			$this->valid = parent::isValid($data, $state_data);
	
			// If the campaign is still valid at this point, run the target rules.
			if ($this->valid)
			{
				// The campaign's state data has been setup in the parent isValid method, pass
				// the campaign's state data, not the state date we got as a parameter.
				$this->valid = $this->target->isValid($data, $this->state_data);
			}
		}
		catch (Exception $e)
		{
			$this->onError('is_valid', $data, $this->state_data, $e);
			$this->valid = FALSE;
		}

		return $this->valid;
	}

	/**
	 * Copy any relevant state data from the target that we need to pass to our
	 * rules.
	 * 
	 * This is not great, but currently bbxadmin can't be configured to set campaign
	 * target data and furthermore GForge #35550 requires all campaigns of a
	 * target to be affected by eventlog_show_rule_passes. Granted this is backwards,
	 * but the larger task to make campaign target_data able to be set was not 
	 * approved/was decided to be out of the scope of the ticket. [DO]
	 *
	 * @param Blackbox_IStateData $state_data
	 */
	protected function copyRelevantStateData(Blackbox_IStateData $state_data)
	{
		if (isset($state_data->eventlog_show_rule_passes))
		{
			try
			{
				$this->state_data->eventlog_show_rule_passes = $state_data->eventlog_show_rule_passes;
			}
			catch (Exception $e)
			{
				$this->getConfig()->applog->Write($e->getMessage());
			}
		}
	}

	/**
	 * Runs the pickTarget function on the encapsulated target.
	 *
	 * @param Blackbox_Data $data the data to pass to the target
	 * @return OLPBlackbox_Winner
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		try
		{
			if (!$this->target instanceof Blackbox_ITarget)
			{
				throw new Blackbox_Exception("Campaign's target was not set.");
			}
			
			if ($this->valid === FALSE) return FALSE;

			if ($this->pick_target_rules instanceof Blackbox_IRule)
			{
				// If pick target rules have not been run. run them now
				if (is_null($this->pick_target_rules_result))
				{
					$this->pick_target_rules_result = $this->pick_target_rules->isValid($data, $this->state_data);
				}
	
				if (!$this->pick_target_rules_result)
				{
					$this->valid = FALSE;
					return FALSE;
				}
			}
			
			/**
			 * Originally I didn't like this, but I ended up liking
			 * CampaignCollection even less.
			 */
			if ($this->target instanceof Blackbox_TargetCollection)
			{
				return $this->target->pickTarget($data);
			}
	
			/**
			 * The current method bypasses the pickTarget() function for each target. Since the 
			 * campaign will always contain a target object, we want to keep them together when 
			 * returning the winner.
			 */
			$valid = $this->target->pickTarget($data);
			if (!$valid)
			{
				$this->valid = FALSE;
				return FALSE;
			}
	
			if ($this->hasSellRule()) 
			{
				$valid = $this->runSellRule($data, $this->state_data);
			}
			if (!$valid)
			{
				$this->valid = FALSE;
				return FALSE;
			}
			$winner = new OLPBlackbox_Winner($this);
		}
		catch (Exception $e)
		{
			// Re-throw all propagating exceptions
			if ($e instanceof OLPBlackbox_IPropagatingException) throw $e;
 
			$this->onError('pick_target', $data, $this->state_data, $e);
			$winner = FALSE;
		}
		return $winner;
	}

	/**
	 * Initializes state data.
	 *
	 * @param Blackbox_IStateData $state_data StateData to add to current state data.
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{
		$initial_data = array(
			'campaign_name' => $this->name,
			'campaign_id' => $this->id,
			'name' => $this->name
		);
		$this->state_data = new OLPBlackbox_CampaignStateData($initial_data);
		
		if ($state_data)
		{
			$this->state_data->addStateData($state_data);
		}
	}
	
	/**
	 * Overloaded setInvalid function.
	 *
	 * This function throws an exception, since there's really no reason for setInvalid to be
	 * called for a Campaign.
	 *
	 * @throws Blackbox_Exception
	 * @return void
	 */
	public function setInvalid()
	{
		throw new Blackbox_Exception(
			'setInvalid called on Campaign object. You probably meant to call it on a Target object.'
		);
	}

	/**
	 * Sets the rules for the campaign.
	 *
	 * @param Blackbox_RuleCollection $rules the rule collection to set for this campaign
	 * @return void
	 */
	public function setRules(Blackbox_IRule $rules)
	{
		$this->rules = $rules;
	}

	/**
	 * Sets the target for the campaign.
	 *
	 * @param Blackbox_ITarget $target the target to set for this campaign
	 * @return void
	 */
	public function setTarget(Blackbox_ITarget $target)
	{
		$this->target = $target;
	}

	/**
	 * Returns the weight of the campaign.
	 *
	 * @return int
	 */
	public function getWeight()
	{
		return $this->weight;
	}

	/**
	 * Returns the current leads for the target.
	 *
	 * @return int
	 */
	public function getCurrentLeads()
	{
		return isset($this->state_data->current_leads)
			? $this->state_data->current_leads
			: NULL;
	}

	/**
	 * Returns the target for the campaign.
	 *
	 * @return OLPBlackbox_ITarget
	 */
	public function getTarget()
	{
		return $this->target;
	}
	
	/**
	 * Returns the name of the campaign (property_short)
	 *
	 * @return unknown
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Gets the location of a property_short in relation to its parent collection
	 *
	 * @param string $property_short
	 * @return TRUE/FALSE
	 */
	public function getTargetLocation($property_short)
	{
		if (strcasecmp($this->name, $property_short) == 0)
		{
			return TRUE;
		}

		if ($target_result = $this->target->getTargetLocation($property_short))
		{
			return $target_result;
		}

		return FALSE;
	}

	/**
	 * Allows you to get a nice pretty print out of the entire blackbox
	 * tree instead of having to do a print_r, or similar, and get the entire
	 * structure dumped to the screen.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$string .= "Campaign: " . $this->name . "\n";
		if ($this->rules instanceof Blackbox_IRule)
		{
			$string .= preg_replace('/^/m', '   ', strval($this->rules));
		}
		if ($this->pick_target_rules instanceof Blackbox_IRule)
		{
			$string .= preg_replace('/^/m', '   ', strval($this->pick_target_rules));
		}
		if ($this->hasSellRule())
		{
			$string .= preg_replace('/^/m', '   ', strval($this->sell_rule));
		}
		$string .= preg_replace('/^/m', '  ', strval($this->getTarget()));
		return $string;
	}

	/**
	 * Log an error from the campaign
	 *
	 * @param string $process Process where error occurred
	 * @param Blackbox_Data $data
	 * @param Blackbox_StateData $state
	 * @param Exception $e
	 */
	protected function onError($process, Blackbox_Data $data, Blackbox_StateData $state, Exception $e)
	{
		$config = OLPBlackbox_Config::getInstance();
		if ($config->applog)
		{
			$config->applog->Write(sprintf(
				'Error processing campaign %s: %s->%s called with %s[%s]',
				$this->getName(),
				__CLASS__,
				__FUNCTION__,
				get_class($e),
				$e->getMessage()),
				LOG_CRIT
			);
		}
		
		if ($config->event_log)
		{
			$config->event_log->Log_Event(
				$process,
				OLPBlackbox_Config::EVENT_RESULT_ERROR,
				$this->getName(),
				$data->application_id,
				$config->blackbox_mode
			);
		}
	}

	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function sleep()
	{
		$data = parent::sleep();
		$data['target'] = $this->target->sleep();
		return $data;
	}

	/**
	 * Restore the runtime state to from a previous sleep 
	 *
	 * @param array $data Data to restore the object's state
	 * @return void
	 */
	public function wakeup(array $data)
	{
		parent::wakeup($data);
		$this->target->wakeup($data['target']);
	}
	
	/**
	 * Attempts to return the lead cost of
	 * this campaign.
	 * @return string
	 */
	public function getLeadCost()
	{
		if ($this->state_data->lead_cost)
		{
			return $this->state_data->lead_cost;
		}
		return FALSE;
	}
	
	/**
	 * Invalidate this campaign from an outside
	 * place
	 * @return string
	 */
	public function invalidate()
	{
		$this->invalidated = TRUE;
	}
	
}
?>
