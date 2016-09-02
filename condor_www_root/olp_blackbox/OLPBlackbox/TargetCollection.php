<?php
/**
 * OLPBlackbox_TargetCollection class file.
 *
 * @package OLPBlackbox
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

/**
 * OLP Blackbox target collection class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_TargetCollection extends Blackbox_TargetCollection implements OLPBlackbox_ITarget
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
	 * Constructs a OLPBlackbox_TargetCollection object.
	 *
	 * @param string $name name of the collection object
	 * @param Blackbox_IStateData $state_data Default StateData to add
	 * @return void
	 */
	public function __construct($name, Blackbox_IStateData $state_data = NULL)
	{
		if (!is_string($name) || $name == '')
		{
			throw new InvalidArgumentException("Collection must have a non-empty string name.");
		}

		$this->name = $name;

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
		// Run our normal isValid process
		$valid = parent::isValid($data, $state_data);

		// If we have any post target rules set and we're still valid, run them
		if ($valid && $this->post_target_rules instanceof Blackbox_IRule)
		{
			$valid = $this->post_target_rules->isValid($data, $this->state_data);
		}

		return $valid;
	}

	/**
	 * Picks a target from the available, valid targets
	 *
	 * @param Blackbox_Data $data data to use for any validation
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		$winner = FALSE;

		// Run any pre-pickTarget rules
		if ($this->pick_target_rules instanceof Blackbox_IRule)
		{
			$valid = $this->pick_target_rules->isValid($data, $this->state_data);

			// We've failed
			if (!$valid) return FALSE;
		}

		// Use the default picker
		if (is_null($this->picker))
		{
			$winner = parent::pickTarget($data);
		}
		else
		{
			$winner = $this->picker->pickTarget($data, $this->getStateData(), $this->valid_list);
		}

		return $winner;
	}

	/**
	 * Overloads the base addTarget functionality, requiring that the added target be an instance
	 * of OLPBlackbox_Campaign.
	 *
	 * @param Blackbox_ITarget $target the target to add to the collection
	 * @return void
	 */
	public function addTarget(Blackbox_ITarget $target)
	{
		$this->target_list[] = $target;
	}

	/**
	 * Initializes state information.
	 *
	 * @param Blackbox_IStateData $state_data StateData to add to the state
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{
		$initial_data = array(
			'target_collection_name' => $this->name,
			'name' => $this->name
		);
		$this->state_data = new OLPBlackbox_TargetCollectionStateData($initial_data);

		if (!is_null($state_data))
		{
			$this->state_data->addStateData($state_data);
		}
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
		$string = sprintf("OLPBlackbox_TargetCollection: %s%s\n",
			$this->getStateData()->target_collection_name,
			$this->collection_id ? ' [' . $this->collection_id . '] ' : ''
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
	 */
	public function setID($id)
	{
		// RC and LIVE have no ctype_digit. :(
		if (!is_numeric(strval($id)))
		{
			throw new InvalidArgumentException(sprintf(
				'id must be numeric, got: %s',
				var_export($id, true))
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
}
?>
