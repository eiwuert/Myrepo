<?php
/**
 * Blackbox class file.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @todo Add in ability to re-run Blackbox if post fails on a winner.
 */

/**
 * Blackbox root class.
 * 
 * The blackbox class builds a list of targets and rules that then run to determine
 * valid targets. Then a target amongst the remaining valid targets is picked to determine
 * the winner.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Root
{
	/**
	 * The root Blackbox_ITarget object.
	 *
	 * @var Blackbox_ITarget
	 */
	protected $target_collection;
	
	/**
	 * State data saved by Blackbox.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;
	
	/**
	 * @var bool
	 */
	protected $valid;

	/**
	 * Blackbox constructor.
	 * 
	 * Optionally you can pass a state data object to Blackbox to use as the root level state data.
	 * If nothing is passed, it will create a generic Blackbox_StateData object.
	 * 
	 * @param Blackbox_IStateData $state_data the state data object to use for Blackbox
	 */
	public function __construct(Blackbox_IStateData $state_data = NULL)
	{
		if (!is_null($state_data))
		{
			$this->state_data = $state_data;
		}
		else
		{
			$this->state_data = new Blackbox_StateData();
		}
	}
	
	/**
	 * Picks a winner of the available, valid targets.
	 *
	 * Runs through all available targets, running the rules for each against the data. Once
	 * it has a collection of valid targets, it will pick the first available, valid target.
	 * 
	 * @param Blackbox_Data $data data to run Blackbox validation against
	 * @return Blackbox_IWinner|bool
	 */
	public function pickWinner(Blackbox_Data $data)
	{
		if (!$this->target_collection instanceof Blackbox_ITarget)
		{
			throw new Blackbox_Exception('Root collection not instantiated properly');
		}
		
		/**
		 * Is our root collection valid?
		 * This essentially runs Blackbox, as each collection under the root
		 * collection then runs isValid() on its targets and its rules.
		 */
		if ($this->isValid($data))
		{
			return $this->pickNextWinner($data);
		}
		return FALSE;
	}
		
	/**
	 * Returns whether the root collection is valid
	 * @param Blackbox_Data $data
	 * @return bool
	 */
	protected function isValid(Blackbox_Data $data)
	{
		if ($this->valid === NULL)
		{
			$this->valid = $this->target_collection->isValid($data, $this->state_data);
	}
		return $this->valid;
	}
	
	/**
	 * Picks the next winner from the root collection
	 * @param Blackbox_Data $data
	 * @return false|Blackbox_IWinner
	 */
	protected function pickNextWinner(Blackbox_Data $data)
	{
		$winner = $this->target_collection->pickTarget($data);

		if (!$winner instanceof Blackbox_IWinner)
		{
			// once we stop receiving winners, we're done
			$this->valid = FALSE;
			return FALSE;
		}

		return $winner;
	}

	/**
	 * Sets the root target collection for an instance of Blackbox.
	 * 
	 * @param Blackbox_TargetCollection $collection the collection to use as the root collection
	 * @return void
	 */
	public function setRootCollection(Blackbox_ITarget $collection)
	{
		$this->target_collection = $collection;
	}
	
	/**
	 * Returns the state data for the Blackbox object.
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
		return strval($this->target_collection);
	}
}
?>
