<?php
/**
 * An OLP Blackbox Campaign decorator class for Targets.
 *
 * This class encapsulates a target class and provides the ability for the same target instance
 * to have different values for a subset of rules. An example of this, would be if we had two
 * identical targets, with the same business rules, but they wanted it to have different limits
 * depending on where it resdided in the tree.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Campaign extends OLPBlackbox_Target
{
	/**
	 * The target for this campaign.
	 *
	 * @var Blackbox_ITarget
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
	 * Determines if we've already set the state data.
	 *
	 * @var bool
	 */
	protected $have_state_data = FALSE;

	/**
	 * OLPBlackbox_Campaign constructor.
	 *
	 * @param string $name the name of the campaign
	 * @param int $id the campaign's id
	 * @param int $weight weight of the campaign
	 * @param Blackbox_ITarget $target the target to use for this campaign
	 * @param Blackbox_IRule $rules the rules to run for this campaign
	 */
	public function __construct(
		$name,
		$id,
		$weight,
		Blackbox_ITarget $target = NULL,
		Blackbox_IRule $rules = NULL)
	{
		if (!is_string($name) || strlen($name) < 2)
		{
			throw new InvalidArgumentException('Name must be a string of at least 2 characters.');
		}

		if (!is_integer($weight))
		{
			// Yes, I'm being mean and requiring this to be an integer
			throw new InvalidArgumentException('Weight must be an integer.');
		}

		$this->name = $name;
		$this->id = (int)$id;
		$this->weight = $weight;
		$this->target = $target;
		$this->rules = $rules;
		$this->initState();
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
		if (!$this->target instanceof Blackbox_ITarget)
		{
			throw new Blackbox_Exception("Campaign's target was not set.");
		}

		if (!$this->rules instanceof Blackbox_IRule)
		{
			throw new Blackbox_Exception("Campaign's rules were not set.");
		}

		if (!$this->have_state_data)
		{
			$this->state_data->addStateData($state_data);
			$this->have_state_data = TRUE;
		}

		// Run our campaign rules
		$valid = $this->rules->isValid($data, $this->state_data);

		// If we're still valid at this point, run the target rules.
		if ($valid)
		{
			$valid = $this->target->isValid($data, $this->state_data);
		}

		return $valid;
	}

	/**
	 * Runs the pickTarget function on the encapsulated target.
	 *
	 * @param Blackbox_Data $data the data to pass to the target
	 * @return OLPBlackbox_Winner
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		if (!$this->target instanceof Blackbox_ITarget)
		{
			throw new Blackbox_Exception("Campaign's target was not set.");
		}

		/**
		 * Originally I didn't like this, but I ended up liking CampaignCollection even less.
		 */
		if ($this->target instanceof Blackbox_TargetCollection)
		{
			return $this->target->pickTarget($data);
		}

		/**
		 * This bypasses the pickTarget() function for each target. Since the campaign will always
		 * contain a target object, we want to keep them together when returning the winner.
		 */
		$valid = $this->target->pickTarget($data);
		if (!$valid)
		{
			return FALSE;
		}

		return new OLPBlackbox_Winner($this);
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
	 * Returns the current leads for this target.
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
	 * Sets the rules for this campaign.
	 *
	 * @param Blackbox_RuleCollection $rules the rule collection to set for this campaign
	 * @return void
	 */
	public function setRules(Blackbox_IRule $rules)
	{
		$this->rules = $rules;
	}

	/**
	 * Sets the target for this campaign.
	 *
	 * @param Blackbox_ITarget $target the target to set for this campaign
	 * @return void
	 */
	public function setTarget(Blackbox_ITarget $target)
	{
		$this->target = $target;
	}

	/**
	 * Returns the target for this campaign.
	 *
	 * @return OLPBlackbox_ITarget
	 */
	public function getTarget()
	{
		return $this->target;
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
		$string .= preg_replace('/^/m', '  ', strval($this->getTarget()));
		return $string;
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
}
?>
