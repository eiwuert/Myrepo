<?php
/**
 * Blackbox class file.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @todo Add in ability to re-run Blackbox if post fails on a winner.
 */

/**
 * Blackbox class.
 * 
 * The blackbox class builds a list of targets and rules that then run to determine
 * valid targets. Then a target amongst the remaining valid targets is picked to determine
 * the winner.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox
{
	/**
	 * The root Blackbox_TargetCollection object.
	 *
	 * @var Blackbox_TargetCollection
	 */
	protected $target_collection;
	
	/**
	 * The current winner in the last run of Blackbox.
	 *
	 * @var Blackbox_IWinner
	 */
	protected $winner;
	
	/**
	 * State data saved by Blackbox.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;
	
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
		if (!$this->target_collection instanceof Blackbox_TargetCollection)
		{
			throw new Blackbox_Exception('Root collection not instantiated properly');
		}
		
		// Reset the winner every time
		$this->winner = NULL;
		
		/**
		 * Is our root collection valid?
		 * This essentially runs Blackbox, as each collection under the root
		 * collection then runs isValid() on its targets and its rules.
		 */
		if ($this->target_collection->isValid($data, $this->state_data))
		{
			// Grab our winner
			$this->winner = $this->target_collection->pickTarget($data);
		}
		
		return $this->winner instanceof Blackbox_IWinner ? $this->winner : FALSE;
	}
	
	/**
	 * Sets the root target collection for an instance of Blackbox.
	 * 
	 * @param Blackbox_TargetCollection $collection the collection to use as the root collection
	 * @return void
	 */
	public function setRootCollection(Blackbox_TargetCollection $collection)
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
