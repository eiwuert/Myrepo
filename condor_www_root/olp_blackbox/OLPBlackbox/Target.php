<?php
/**
 * OLPBlackbox_Target class file.
 *
 * @package OLPBlackbox
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

/**
 * OLPBlackbox_Target class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Target extends Blackbox_Target implements OLPBlackbox_ITarget
{
	/**
	 * Name of this target. (Important for OLPBlackbox_Data registries)
	 *
	 * @var string
	 */
	protected $name = NULL;

	/**
	 * Target's ID.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * The rule or rule collection to run on pickTarget.
	 *
	 * @var Blackbox_IRule
	 */
	protected $pick_target_rules = NULL;

	/**
	 * Constructs a OLPBlackbox_Target object.
	 *
	 * @param string $name name of the collection object
	 * @param int $id ID of the target
	 * @param Blackbox_IStateData $state_data Default StateData to add
	 */
	public function __construct($name, $id, Blackbox_IStateData $state_data = NULL)
	{
		if (!is_string($name) || $name == '')
		{
			throw new InvalidArgumentException("Target must have a non-empty string name.");
		}
		$this->name = $name;
		$this->id = (int)$id;

		// init parent last, as it calls initData();
		parent::__construct($state_data);
	}

	/**
	 * Returns the {@see Blackbox_IRule} object of this target.
	 *
	 * @return Blackbox_IRule object.
	 */
	public function getRules()
	{
		return $this->rules;
	}
	
	/**
	 * Set the state for this Target object.
	 *
	 * The state is a IStateData class which contains the mutable
	 * and immutable information about the state of the ITarget
	 * which is used primarily by Rules to access information that may
	 * be relevant to their validation, but not available in their class
	 * scope. To that end, it's passed to each IRule in the isValid()
	 * method call.
	 *
	 * @param Blackbox_IStateData $state_data StateData to add to the state
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{
		$initial_data = array(
			'target_name' => $this->name,
			'name' => $this->name,
			'target_id' => $this->id,
		);
		$this->state_data = new OLPBlackbox_TargetStateData($initial_data);

		if (!is_null($state_data))
		{
			$this->state_data->addStateData($state_data);
		}
	}

	/**
	 * Sets the targets validity to FALSE.
	 *
	 * @return void
	 */
	public function setInvalid()
	{
		$this->valid = FALSE;
	}

	/**
	 * Sets the rule or rules to run on pick target.
	 *
	 * @param Blackbox_IRule $rules the rule or rule collection to run
	 *
	 * @return void
	 */
	public function setPickTargetRules(Blackbox_IRule $rules)
	{
		$this->pick_target_rules = $rules;
	}

	/**
	 * Returns this target as a winner after running any pickTarget rules, if any.
	 *
	 * @param Blackbox_Data $data the data to do validation against
	 *
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		// We need to increment the target's bb_*_look stat limit before we run pickTarget.
		// @todo We may need to specify more react options
		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER
			&& !isset(OLPBlackbox_Config::getInstance()->site_config->ecash_react))
		{
			$look_stat = 'bb_' . strtolower($this->state_data->campaign_name) . '_look';
			OLPBlackbox_Config::getInstance()->stat_limits->Increment($look_stat, NULL, NULL, NULL);
		}

		if ($this->pick_target_rules instanceof Blackbox_IRule)
		{
			$valid = $this->pick_target_rules->isValid($data, $this->state_data);

			if (!$valid) return FALSE;
		}

		return parent::pickTarget($data);
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
		$string .= "Target: " . $this->getStateData()->name . "\n";
		
		if ($this->rules instanceof Blackbox_IRule)
		{
			$string .= preg_replace('/^/m', '   ', strval($this->rules));
		}
		
		if ($this->pick_target_rules instanceof Blackbox_IRule) 
		{
			$string .= preg_replace('/^/m', '   ', 'Pick Target ' . 
				strval($this->pick_target_rules));
		}
		return $string;
	}
}
?>
