<?php
/**
 * Blackbox_Target class file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

/**
 * Blackbox_Target class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Target implements Blackbox_ITarget
{
	/**
	 * Collection of rules to run for this target.
	 *
	 * @var Blackbox_IRule
	 */
	protected $rules;

	/**
	 * State data about this target.
	 *
	 * @var Blackbox_IStateData
	 */
	protected $state_data;

	/**
	 * Whether the target is currently valid.
	 *
	 * @var bool
	 */
	protected $valid = NULL;
	
	/**
	 * Whether we've picked this target before
	 *
	 * @var bool
	 */
	protected $picked = FALSE;

	/**
	 * Blackbox_Target constructor.
	 *
	 * @param Blackbox_IStateData $state_data Default StateData to add
	 */
	public function __construct(Blackbox_IStateData $state_data = NULL)
	{
		$this->initState($state_data);
	}

	/**
	 * Runs all rules for this target and returns if the target is still valid.
	 *
	 * @param Blackbox_Data $data data to run validation on
	 * @param Blackbox_IStateData $state_data state data to use for validation
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// If we've already run the rules, we don't want to run them again
		if (!is_null($this->valid)) return $this->valid;
		
		$this->state_data->addStateData($state_data);

		if (!$this->rules instanceof Blackbox_IRule)
		{
			throw new Blackbox_Exception('Rules not setup for Target.');
		}

		// Run our rules, or rule if it's only one
		$this->valid = $this->rules->isValid($data, $this->state_data);

		return $this->valid;
	}

	/**
	 * Returns this target.
	 *
	 * @param Blackbox_Data $data data to run validation on
	 * @return Blackbox_IWinner
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		$picked = FALSE;
		
		if (!$this->picked)
		{
			$picked = $this->getWinner($data);
			$this->picked = TRUE;
		}
		
		return $picked;
	}
	
	/**
	 * Returns the winner object.
	 *
	 * $data isn't used in the base function, but extended classes can use it to do validation
	 *
	 * @param Blackbox_Data $data object containing data to do validation on
	 * @return Blackbox_IWinner
	 */
	protected function getWinner(Blackbox_Data $data)
	{
		return new Blackbox_Winner($this);
	}

	/**
	 * Sets the rules for this target.
	 *
	 * The rules can be either a RuleCollection or an individual Rule.
	 *
	 * @param Blackbox_IRule $rules the rules to use for this target
	 * @return void
	 */
	public function setRules(Blackbox_IRule $rules)
	{
		$this->rules = $rules;
	}

	/**
	 * Initializes state data.
	 *
	 * Children may want to override this with a more specific IStateData implementation.
	 *
	 * @param Blackbox_IStateData $state_data StateData to add to the state
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{
		$this->state_data = new Blackbox_StateData();
		
		if (!is_null($state_data))
		{
			$this->state_data->addStateData($state_data);
		}
	}
	
	/**
	 * Returns the target's state data.
	 *
	 * @return Blackbox_IStateData
	 */
	public function getStateData()
	{
		return $this->state_data;
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
		$string .= preg_replace('/^/m', '   ', strval($this->rules));
		return $string;
	}
}
?>
