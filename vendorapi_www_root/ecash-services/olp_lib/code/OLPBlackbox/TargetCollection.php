<?php
/**
 * OLP Blackbox target collection class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_TargetCollection extends Blackbox_TargetCollection implements OLPBlackbox_ITarget, OLP_ISubscriber
{
	/**
	 * The ID of this collection.
	 * 
	 * Not all collections will have ID's, but it's needed for stat hits
	 * often times.
	 * 
	 * @var int
	 */
	protected $collection_id = NULL;

	/**
	 * Name of this collection. (Important for OLPBlackbox_Data registries)
	 *
	 * @var string
	 */
	protected $name = NULL;

	/**
	 * The weighting picker this collection will use.
	 *
	 * @var OLPBlackbox_IPicker
	 */
	protected $picker;

	/**
	 * Rules we run after target rules have run.
	 *
	 * @var Blackbox_IRule
	 */
	protected $post_target_rules;

	/**
	 * Rules that we run before we pick a target.
	 *
	 * @var Blackbox_IRule
	 */
	protected $pick_target_rules;

	/**
	 * The list of tags for this object.
	 * 
	 * @var Collections_ImmutableList_1
	 */
	protected $tags = NULL;

	/**
	 * Cached winner
	 *
	 * @var OLPBlackbox_Winner
	 */
	protected $winner = NULL;

	/**
	 * Cached pick target rules result
	 *
	 * @var bool
	 */
	protected $pick_target_rules_result = NULL;
	/**
	 * Constructs a OLPBlackbox_TargetCollection object.
	 *
	 * @param string $name name of the collection object
	 * @param Blackbox_IStateData $state_data Default StateData to add
	 * @param array $tags List of target tags. (target_tags in the db)
	 * Target tags replace the old submit level stats and are cumulatively hit
	 * outside of blackbox via the state data (at the time of this writing.)
	 * @return void
	 */
	public function __construct($name, Blackbox_IStateData $state_data = NULL, array $tags = array())
	{
		if (!is_string($name) || $name == '')
		{
			throw new InvalidArgumentException(
				'Collection must have a non-empty string name.'
			);
		}

		$this->name = $name;
		
		$this->tags = $tags;

		// call parent last, as it calls init initState();
		parent::__construct($state_data);
	}

	/**
	 * Sets the weighting picker for this collection.
	 *
	 * @param OLPBlackbox_IPicker $picker weighting picker to use
	 * @return void
	 */
	public function setPicker(OLPBlackbox_IPicker $picker)
	{
		$this->picker = $picker;
	}
	
	/**
	 * WARNING: DO NOT USE. This is simply for unit testing purposes, rules
	 * should not be poking at the picker.
	 * @return Blackbox_IPicker 
	 */
	public function getPicker()
	{
		return $this->picker;
	}

	/**
	 * Runs the normal isValid functionality for collections, but includes another
	 * post-Target rules run for rules that need to be run after the targets have
	 * run their rules.
	 *
	 * @param Blackbox_Data $data the data to validate against
	 * @param Blackbox_IStateData $state_data the state data to validate against
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->sendEvent(OLPBlackbox_Event::EVENT_VALIDATION_START);

		// If we haven't validated the collection yet, do it now
		if (is_null($this->valid))
		{
			// Run our normal isValid process
			$this->setValid(parent::isValid($data, $state_data));
	
			// If we have any post target rules set and we're still valid, run them
			if ($this->valid && $this->post_target_rules instanceof Blackbox_IRule)
			{
				$this->setValid($this->post_target_rules->isValid($data, $this->state_data));
			}
		}
		
		$this->sendEvent(OLPBlackbox_Event::EVENT_VALIDATION_END);

		return $this->valid;
	}
	
	/**
	 * Receive events from an OLP_IEventBus
	 *
	 * @param OLP_IEvent $event
	 * @return void
	 */
	public function notify(OLP_IEvent $event)
	{
		if (in_array($event->getType(), array(
			OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT,
			OLPBlackbox_Event::EVENT_GLOBAL_MILITARY_FAILURE,
		)))
		{
			$this->valid = FALSE;
			if ($this->picker instanceof OLPBlackbox_IPicker)
			{
				$this->picker->setInvalid();
			}
		}
	}
	
	/**
	 * Picks a target from the available, valid targets
	 *
	 * @param Blackbox_Data $data data to use for any validation
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		$this->sendEvent(OLPBlackbox_Event::EVENT_PICK_START);

		$this->winner = FALSE;

		// No need to continue if we're not valid
		if ($this->valid)
		{
			if (is_null($this->pick_target_rules_result))
			{
				// Run any pre-pickTarget rules
				if ($this->pick_target_rules instanceof Blackbox_IRule)
				{
					$this->valid = $this->pick_target_rules_result = $this->pick_target_rules->isValid($data, $this->state_data);
					// We've failed
					if (!$this->valid) return FALSE;
				}
			}
			// Use the default picker
			if (is_null($this->picker))
			{
				$this->winner = parent::pickTarget($data);
			}
			else
			{
				$this->winner = $this->picker->pickTarget(
					$data, $this->getStateData(), $this->valid_list
				);
			}
		}
		
		$this->sendEvent(OLPBlackbox_Event::EVENT_PICK_END);
		
		return $this->valid ? $this->winner : FALSE;
	}

	/**
	 * Sends an event
	 * 
	 * @todo refactor into common functionality with rulecollection?
	 *
	 * @param string $type The type of event to send.
	 * @return void
	 */
	protected function sendEvent($type)
	{
		if ($this->getEventBus() instanceof OLP_IEventBus)
		{
			$this->getEventBus()->notify(
				new OLPBlackbox_Event($type, $this->getDefaultEventAttrs())
			);
		}
	}
	
	/**
	 * Get the event bus for this object, if available.
	 *
	 * @return OLP_IEventBus|NULL
	 */
	protected function getEventBus()
	{
		if ( $this->getConfig() instanceof OLPBlackbox_Config
			&& $this->getConfig()->event_bus instanceof OLP_IEventBus)
		{
			return $this->getConfig()->event_bus; 
		}
		
		return NULL;
	}
	
	/**
	 * The configuration object olp blackbox uses.
	 *
	 * @return OLPBlackbox_Config
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}
	
	/**
	 * Assemble a list of event attributes we commonly send out.
	 *
	 * @return array
	 */
	protected function getDefaultEventAttrs()
	{
		return array(
			OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_TARGET_COLLECTION,
			OLPBlackbox_Event::ATTR_SENDER_HASH => spl_object_hash($this),
		);
	}
	
	/**
	 * Initializes state information.
	 *
	 * @param Blackbox_IStateData $state_data StateData to add to the state
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{		
		$this->state_data = new OLPBlackbox_TargetCollectionStateData(
			$this->getInitialStateData()
		);

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
			'target_collection_name' => $this->name,
			'name' => $this->name,
			'target_tags' => new OLPBlackbox_StateData_CombineKey($this->tags),
			'children' => array()
		);
		
		return $initial_data;
	}

	/**
	 * Sets the rules to be invalid for this collection.
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
	 * @return void
	 */
	public function setPickTargetRules(Blackbox_IRule $rules)
	{
		$this->pick_target_rules = $rules;
	}

	/**
	 * Prints out a representation of this object.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$string = sprintf("%s: %s%s (%s targets)\n",
			get_class($this),
			$this->getStateData()->target_collection_name,
			($this->collection_id ? ' [ collection_id: ' . $this->collection_id . '] ' : ''),
			(is_array($this->target_list) ? count($this->target_list) : 'unknown')
		);
		
		if ($this->picker)
		{
			$string .= preg_replace('/^/m', '   ', "[".get_class($this->picker)."]\n");
		}

		if ($this->rules)
		{
			$string .= preg_replace('/^/m', '   ', strval($this->rules));
		}

		foreach ($this->target_list as $target)
		{
			$string .= preg_replace('/^/m', '   ', strval($target));
		}

		if ($this->post_target_rules)
		{
			$string .= preg_replace('/^/m', '   ', strval($this->post_target_rules));
		}

		if ($this->pick_target_rules)
		{
			$string .= preg_replace('/^/m', '   ', strval($this->pick_target_rules));
		}

		return $string;
	}

	/**
	 * Sets the rules to run after all the targets in this collection have run their rules.
	 *
	 * @param Blackbox_IRule $rules the rules to run after the targets
	 * @return void
	 */
	public function setPostTargetRules(Blackbox_IRule $rules)
	{
		$this->post_target_rules = $rules;
	}

	/**
	 * Set the ID for this collection.
	 *
	 * @param int $id ID of the collection from DB
	 * @return void
	 */
	public function setID($id)
	{
		// RC and LIVE have no ctype_digit. :(
		if (!is_numeric(strval($id)))
		{
			throw new InvalidArgumentException(sprintf(
				'id must be numeric, got: %s',
				var_export($id, TRUE))
			);
		}
		
		$this->collection_id = intval($id);
	}
	
	/**
	 * Hit stat convenience function (mostly for mocking)
	 *
	 * @param string $stat_name Stat to hit.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 * 
	 * @return void
	 */
	protected function hitStat($stat_name, Blackbox_IStateData $state_data)
	{
		OLPBlackbox_Config::getInstance()->hitSiteStat(
			$stat_name, $state_data
		);
	}

	/**
	 * Returns the name of the collection
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * Remove a target at a given index.
	 * 
	 * This is used for manipulating the blackbox tree structure after it has
	 * been assembled, which is usually done in the factory. This should never
	 * be used once blackbox is being run unless there is some darn good reason
	 * for it.
	 * 
	 * @param int $index The numeric index to remove the target from, gotten with
	 * {@see getTargetLocation}
	 * @return void
	 */
	public function unsetTargetIndex($index)
	{
		unset($this->target_list[$index]);
		//Clean up any gaps in the indexes
		$this->target_list = array_values($this->target_list);
	}
	
	/**
	 * Returns the object at the given index.
	 * @see unsetTargetIndex
	 * @param int $index The index, gotten with {@see getTargetLocation} of the
	 * target object to retrieve.
	 * @return Blackbox_ITarget
	 */
	public function getTargetAtIndex($index)
	{
		return $this->target_list[$index];
	}
	
	/**
	 * Gets the location of a target.
	 * @see unsetTargetIndex
	 * @param string $property_short the property short of the target to find.
	 * @return array with keys: string collection, int index
	 */
	public function getTargetLocation($property_short)
	{
		$index = 0;
		foreach ($this->target_list as $target)
		{
			//If the response that comes back isn't false something was found
			if ($result = $target->getTargetLocation($property_short))
			{
				if (!is_array($result))
				{
					$found_target = array('collection' => $this,'index' => $index);
				}
				else
				{
					$found_target = $result;
				}
				break;
			}
			$index++;
		}

		if (isset($found_target))
		{
			return $found_target;
		}

		return FALSE;
	}
		
	/**
	 * Add a target to the start of the target_list
	 *
	 * @param Blackbox_ITarget $target
	 * @return void
	 */
	public function prependTarget(Blackbox_ITarget $target)
	{
		array_unshift($this->target_list,$target);
	}
	
	/**
	 * Returns a Blackbox_Target from the rule collection
	 *
	 * @param string $property_short
	 * @param bool $children_only Only search for children (don't return current class)
	 * @return Blackbox_ITarget|NULL NULL if unable to locate.
	 */
	public function getTargetObject($property_short, $children_only = FALSE)
	{
		$target = NULL;
		
		if (!$children_only && strcasecmp($property_short, $this->getName()) == 0)
		{
			return $this;
		}
		
		$location = $this->getTargetLocation($property_short);
		
		if (is_array($location))
		{
			$target = $location['collection']->getTargetAtIndex($location['index']);
		}
		return $target;
	}

	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function sleep()
	{
		$data = array(
			'valid' => $this->valid,
			'pick_target_rules_result' => $this->pick_target_rules_result,
			'state_data' => $this->state_data,
			'children' => array()
		);

		if ($this->getPicker() instanceof OLPBlackbox_IRestorable)
		{
			$data['picker'] = $this->getPicker()->sleep();
		}
		
		foreach ($this->target_list as $target)
		{
			if ($target instanceof OLPBlackbox_IRestorable)
			{
				$data['children'][$target->getName()] = $target->sleep();
			}
		}
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
		// Do not restore valid if it is TRUE.  If it is TRUE, we need to run 
		// isValid() to populate the valid targets list
		if (!$data['valid']) $this->valid = $data['valid'];
		$this->pick_target_rules_result = $data['pick_target_rules_result'];
		$this->state_data = $data['state_data'];
		
		// If the picker is restorable, wake it up
		if ($this->getPicker() instanceof OLPBlackbox_IRestorable)
		{
			$this->getPicker()->wakeup($data['picker']);
		}

		// If the target is restorable and it has sleep data, wake it up
		foreach ($this->target_list as $target)
		{
			if ($target instanceof OLPBlackbox_IRestorable && !empty($data['children'][$target->getName()]))
			{
				$target->wakeup($data['children'][$target->getName()]);
			}
		}
		
	}
	
	/**
	 * Orders the rules inside the rule collection. This method will simply
	 * delegate the task.
	 *
	 * @param array $rules
	 * @return void
	 */
	public function orderRules(array $rules)
	{
		if ($this->rules instanceof OLPBlackbox_RuleCollection)
		{
			$this->rules->orderRules($rules);
		}
	}
}
?>
