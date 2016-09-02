<?php

/**
 * OLPBlackbox_Target class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Target extends Blackbox_Target implements OLPBlackbox_ITarget
{
	/**
	 * The "sell" rule, which is a rule which will give the information to a lender.
	 * 
	 * This rule must be run _last_ because if the lender accepts the loan, we cannot
	 * then fail to sell it to them. (As of this writing, the rule will be a LenderPost
	 * rule.) [DO]
	 *
	 * @var OLPBlackbox_Rule
	 */
	protected $sell_rule = NULL;

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
	 * Cached pick target rules result
	 *
	 * @var bool
	 */
	protected $pick_target_rules_result = NULL;

	/**
	 * The list of tags for this object.
	 * 
	 * @var Collections_ImmutableList_1
	 */
	protected $tags = NULL;

	/**
	 * Constructs a OLPBlackbox_Target object.
	 *
	 * @param string $name name of the collection object
	 * @param int $id ID of the target
	 * @param Blackbox_IStateData $state_data Default StateData to add
	 * @param array $tags List of target tags. (target_tags in the db)
	 * @return void
	 */
	public function __construct($name, $id, Blackbox_IStateData $state_data = NULL, array $tags = array())
	{
		if (!is_string($name) || $name == '')
		{
			throw new InvalidArgumentException("Target must have a non-empty string name.");
		}
		$this->name = $name;
		$this->id = (int)$id;

		$this->tags = $tags;

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
	 * Returns the pick target rules for this Target.
	 * @see OLPBlackbox_ITarget
	 * @return Blackbox_IRule
	 */
	public function getPickTargetRules()
	{
		return $this->pick_target_rules;
	}

	/**
	 * Whether or not the sell rule is valid and can be run.
	 *
	 * @return bool
	 */
	public function hasSellRule()
	{
		return $this->sell_rule instanceof Blackbox_IRule;
	}

	/**
	 * Set a rule which will "sell" the lead (post) to a lender (target/campaign).
	 * @param OLPBlackbox_IPickTargetRule $rule Usually a LenderPost rule.
	 * @return void
	 */
	public function setSellRule(OLPBlackbox_ISellRule $rule = NULL)
	{
		$this->sell_rule = $rule;
	}
	
	/**
	 * Overriden version of parent isValid, implemented for event bus stuff.
	 *
	 * @see Blackbox_Target::isValid()
	 * @param Blackbox_Data $data The information about the application.
	 * @param Blackbox_IStateData $state_data Information about the state of 
	 * blackbox.
	 * @return bool Valid (TRUE) or invalid (FALSE).
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->sendEvent(OLPBlackbox_Event::EVENT_VALIDATION_START);
		$valid = parent::isValid($data, $state_data);
		$this->sendEvent(OLPBlackbox_Event::EVENT_VALIDATION_END);
		
		return $valid;
	}
	
	/**
	 * Runs the sell rule if it has one and returns this target
	 * if no sell rule or sell rule passes.  Returns FALSE on
	 * failure
	 *
	 * @param Blackbox_Data $data data to run validation on
	 * @return Blackbox_IWinner
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		$picked = parent::pickTarget($data);
		
		$valid = TRUE;
		if ($picked && $this->hasSellRule()) 
		{
			$valid = $this->runSellRule($data, $this->state_data);
		}
		return $valid ? $picked : FALSE;
	}	
	
	/**
	 * Send an OLP_IEvent on the event bus (if present)
	 *
	 * @param string $event_type The type of event to send.
	 * @return void
	 */
	protected function sendEvent($event_type)
	{
		
		if ($this->getEventBus() instanceof OLP_IEventBus)
		{
			$this->getEventBus()->notify(
				new OLPBlackbox_Event($event_type, $this->getDefaultEventAttrs())
			);
		}
	}
	
	/**
	 * The attributes normally associated with any generic event send from this
	 * type of target.
	 *
	 * @return array
	 */
	protected function getDefaultEventAttrs()
	{
		return array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_TARGET);
	}
	
	/**
	 * Return the event bus, if available.
	 *
	 * @return OLP_IEventBus|NULL
	 */
	protected function getEventBus()
	{
		if ($this->getConfig() instanceof OLPBlackbox_Config
			&& $this->getConfig()->event_bus instanceof OLP_IEventBus)
		{
			return $this->getConfig()->event_bus;
		}
			
		return NULL;
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
		$this->state_data = new OLPBlackbox_TargetStateData($this->getInitialStateData());

		if (!is_null($state_data))
		{
			$this->state_data->addStateData($state_data);
		}
	}

	/**
	 * Get the initial data to seed this collection's state data with.
	 * @return array Dictionary of initialization data.
	 */
	protected function getInitialStateData()
	{
		$initial_data = array(
			'target_name' => $this->name,
			'name' => $this->name,
			'target_id' => $this->id,
			'target_tags' => new OLPBlackbox_StateData_CombineKey($this->tags)
		);

		return $initial_data;
	}
	/**
	 * Runs the sell rule (LenderPost in most cases.)
	 *
	 * @param Blackbox_Data $data The data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * 
	 * @return bool Whether the rule passed or not.
	 */
	public function runSellRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!$this->hasSellRule())
		{
			throw new Blackbox_Exception('cannot run sell rule, none set.');
		}

		$return = $this->sell_rule->isValid($data, $state_data);

		if (!$return)
		{
			$this->addWithheldTargets($data);
		}

		return $return;
	}

	/**
	 * Adds withheldtargets rule value (if available) to $data so that later withheld targets rules have access
	 *
	 * @param Blackbox_Data  $data
	 * @return void
	 */
	public function addWithheldTargets(Blackbox_Data $data)
	{
		if ($this->getConfig()->bypass_withheld_targets == TRUE)
		{
			return;
		}

		$pick_target_rules = $this->getPickTargetRules();
		if (!empty($pick_target_rules))
		{
			$withheld_targets = array();
			foreach ($pick_target_rules as $rule)
			{
				if (!$rule instanceof OLPBlackbox_Rule_WithheldTargets) continue;

				if ($value = trim($rule->getRuleValue()))
				{
					$value = explode(',',$value);
					$value = array_map('trim', array_map('strtolower', $value));

					$withheld_targets = array_merge($withheld_targets,$value);
				}
			}

			$state_data = $this->getStateData();
			if (!empty($withheld_targets))
			{
				$state_data->withheld_targets = array_unique(
					array_merge(
						$state_data->withheld_targets,
						$withheld_targets
					)
				);
			}
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
	 * Returns the winner object.
	 *
	 * @param Blackbox_Data $data
	 * @return Blackbox_IWinner
	 */
	protected function getWinner(Blackbox_Data $data)
	{
		// If the target was explicitly set invalid, it should return FALSE
		if ($this->valid === FALSE) return FALSE;
		
		if ($this->pick_target_rules instanceof Blackbox_IRule)
		{
			// if the pick target rules have not been run, run them now
			if (is_null($this->pick_target_rules_result))
			{
				$this->pick_target_rules_result = $this->pick_target_rules->isValid($data, $this->state_data);
			}
			if (!$this->pick_target_rules_result)
			{
				$this->setValid(FALSE);
				return FALSE;
			}
		}
		
		if ($this->getConfig()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER
			&& !isset($this->getConfig()->ecash_react))
		{
			$freq_object = $this->getFrequencyScoreInstance();
			$freq_object->addPost($data->email_primary);
		}
		
		return parent::getWinner($data);
	}
	
	/**
	 * Use this instead of $this->valid = x();
	 * 
	 * @see Blackbox_TargetCollection::setValid() for why this is used.
	 * @param bool $bool TRUE/FALSE based on desired validity.
	 * @return void
	 */
	protected function setValid($bool)
	{
		$this->valid = $bool && $this->isInvalidated();
	}
	
	/**
	 * Whether this object has been deliberately invalidated.
	 * @return bool
	 */
	protected function isInvalidated()
	{
		return $this->valid === FALSE;
	}
	
	/**
	 * Returns the OLP database instance.
	 *
	 * @return MySQL_4
	 */
	protected function getDbInstance()
	{
		return $this->getConfig()->olp_db;
	}
	
	/**
	 * Returns the PDO OLP database instance.
	 *
	 * @return DB_IConnection_1
	 */
	protected function getPDOInstance()
	{
		return $this->getDbInstance()->getConnection()->getConnection();
	}
	
	/**
	 * Returns the instance of the Accept_Ratio_Singleton class (the Frequency Score class).
	 *
	 * @return OLP_FrequencyScore
	 */
	protected function getFrequencyScoreInstance()
	{
		$freq_object = new OLP_FrequencyScore(
			$this->getPDOInstance(),
			$this->getConfig()->memcache
		);
		
		return $freq_object;
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
	 * Allows you to get a nice pretty print out of the entire blackbox
	 * tree instead of having to do a print_r, or similar, and get the entire
	 * structure dumped to the screen.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$string = "Target: " . $this->getStateData()->name . "\n";
		
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

	/**
	 * Gets the location of a target.
	 *
	 * @return FALSE
	 */
	public function getTargetLocation()
	{
		return FALSE;
	}

	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function sleep()
	{
		return array(
			'valid' => $this->valid,
			'pick_target_rules_result' => $this->pick_target_rules_result,
			'state_data' => $this->state_data
		);
	}

	/**
	 * Restore the runtime state from a previous sleep 
	 *
	 * @param array $data Data to restore the object's state
	 * @return void
	 */
	public function wakeup(array $data)
	{
		$this->valid = $data['valid'];
		$this->pick_target_rules_result = $data['pick_target_rules_result'];
		$this->state_data = $data['state_data'];
	}
}
?>
