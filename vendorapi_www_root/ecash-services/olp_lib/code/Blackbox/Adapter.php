<?php

/**
 * Adapter to interface with OLP's old and new Blackbox classes.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
abstract class Blackbox_Adapter implements OLPBlackbox_IRestorable
{
	/**
	 * Blackbox class
	 *
	 * @var mixed
	 */
	protected $blackbox;
	
	/**
	 * The default value for what percentage of leads should
	 * use the new Blackbox as opposed to the old.  This is a
	 * measure put in place to better transition traffic onto
	 * the new system.
	 */
	const NEW_PERCENTAGE = 100;
	
	/**
	 * Instance of a Blackbox_Adapter object.
	 *
	 * @var Blackbox_Adapter The Blackbox object
	 */
	protected static $instance = NULL;

	/**
	 * Current mode for blackbox
	 *
	 * @var string
	 */
	protected $mode;
	
	/**
	 * Class with config data.
	 *
	 * @var stdClass
	 */
	protected $config_data;
	
	/**
	 * List of targets who have been chosen at some point,
	 * whether they ultimately failed their posts or not.
	 *
	 * @var array
	 */
	protected $winners = array();

	/**
	 * Rework information recorded when a {@see OLPBlackbox_ReworkException} is thrown.
	 *
	 * @var array
	 */
	protected $rework_info = array();

	/**
	 * Sets up the adapter.
	 *
	 * @param string $mode Blackbox mode for this run.
	 * @param stdClass $config_data Class containing config
	 * 	data used to set up Blackbox.
	 */
	public function __construct($mode, $config_data)
	{
		$this->mode = $mode;
		$this->config_data = $config_data;
		
		$this->preConfigure();
	}
	
	/**
	 * Sets the current winners.  Needed if we're going to switch
	 * between Blackboxes.
	 *
	 * @param array $winners Array of winners to set
	 * @return void
	 */
	public function setWinners($winners)
	{
		$this->winners = $winners;
	}
	
	/**
	 * Returns the list of targets that have had post attempts.
	 *
	 * @return array
	 */
	public function getWinners()
	{
		return $this->winners;
	}
	
	/**
	 * Gets all the tiers currently in use.
	 *
	 * @return array
	 * 
	 * @todo Determine a way to get active tiers from new Blackbox
	 */
	public function getTiers()
	{
		return range(0, 4);
	}
	
	/**
	 * Gets the last DataX decision from the last run.
	 *
	 * @return array
	 */
	abstract public function getDataXDecision();
	
	
	/**
	 * Gets the DataX Track Hash from the last run.
	 *
	 * @return string
	 */
	abstract public function getDataxTrackHash();
	
	/**
	 * Returns a list of property shorts who could
	 * potentially be sold the lead (mainly leads who
	 * have passed all their business rules).
	 *
	 * @param int $tier The tier you want to look for winners in.
	 * @return array A list of possible winners.
	 * 
	 * @todo Determine way of implementing this in new Blackbox
	 */
	public function getPossibleWinners($tier = NULL)
	{
		return array();//$this->blackbox->Get_Possible_Winners($tier);
	}
	
	/**
	 * Runs when the Adapter is instantiated.
	 *
	 * @return void
	 */
	abstract protected function preConfigure();
	
	/**
	 * Runs at the end of Configure_Blackbox() in olp.php
	 *
	 * @return void
	 */
	abstract public function postConfigure();
	
	/**
	 * Sets and returns the Blackbox mode.
	 *
	 * @param string $mode The mode you want to set.
	 * @return string Current Blackbox mode
	 */
	abstract public function mode($mode = NULL);
	
	/**
	 * Sets debug flags in Blackbox.
	 *
	 * @param array $debug_opt Debug options to set
	 * @return void
	 */
	abstract public function setDebugOptions($debug_opt);
	
	/**
	 * Restricts targets or tiers.
	 *
	 * @param array $targets List of targets and tiers to restrict.
	 * @param bool $restrict TRUE to restrict, FALSE to exclude
	 * @return void
	 */
	abstract public function restrict($targets, $restrict = TRUE);

	/**
	 * Restricts tiers.
	 *
	 * @param array $tiers List of tiers to restrict.
	 * @param bool $restrict TRUE to restrict, FALSE to exclude
	 * @return void
	 */
	abstract public function restrictTiers($tiers, $restrict = TRUE);
	
	/**
	 * Picks a winning vendor from Blackbox
	 *
	 * @param bool $reset TRUE to reset current processing
	 * @param bool $bypass_used_info TRUE to bypass used_info check
	 * @return object|bool Object for the winner that was chosen or FALSE
	 * 	if no winner could be found.
	 */
	abstract public function pickWinner($reset = FALSE, $bypass_used_info = FALSE);
	
	/**
	 * Returns information about the current Blackbox winner.
	 *
	 * @return array List of winner data
	 */
	abstract public function winner();
	
	/**
	 * Runs an individual rule on a target.
	 *
	 * @param string $property_short The property short of the target
	 * @param string $rule The name of the rule.
	 * @param mixed $value The data to use when checking the rule
	 * @return bool TRUE if rule passed.
	 */
	abstract public function runRule($property_short, $rule, $value = NULL);
	
	/**
	 * Returns a target object
	 *
	 * @param string $name The property short of the target
	 * @return object Target object
	 */
	abstract public function getTarget($name);
	
	/**
	 * Returns the current winner's property short.
	 * 
	 * @return string The property short of the winner
	 */
	abstract public function getPropertyShort();
	
	/**
	 * Determines whether the current winner is valid.
	 * 
	 * @return bool TRUE if winner object is valid.
	 */
	abstract protected function winnerExists();
	
	/**
	 * Withholds targets from being picked as winners.
	 *
	 * @return void
	 */
	abstract public function addWithheldTargets();
	
	/**
	 * Determines whether the current winner will allow
	 * its lead to be sent to list management.
	 *
	 * @return bool TRUE if we can sell to the list.
	 */
	abstract public function sellToListManagement();
	
	/**
	 * Gets the current Blackbox snapshot.
	 *
	 * @return mixed Snapshot data.
	 */
	abstract public function getSnapshot();
	
	/**
	 * Gets an adapter to run Blackbox with.  You can force it to
	 * use the new Blackbox by setting the use_new_blackbox option
	 * in Webadmin1, or you can force a different percentage weighting
	 * by setting the new_blackbox_percentage option.
	 *
	 * @param string $mode The Blackbox mode to use when setting up the object.
	 * @param stdClass $config_data A class with config data used to set up Blackbox.
	 * @param bool $reset Force it to choose a new object.
	 * @return Blackbox_Adapter
	 */
	public static function getInstance($mode = OLPBlackbox_Config::MODE_BROKER, $config_data = NULL, $reset = FALSE)
	{
		if (self::$instance === NULL || $reset)
		{
			if (self::useNewBlackbox())
			{
				self::$instance = new Blackbox_Adapter_New($mode, $config_data);
			}
			else
			{
				self::$instance = new Blackbox_Adapter_Old($mode, $config_data);
			}
		}

		return self::$instance;
	}
	
	/**
	 * Determines whether the instance is set up as a proper Blackbox_Adapter
	 *
	 * @return bool
	 */
	public static function instanceExists()
	{
		return (self::$instance instanceof Blackbox_Adapter);
	}
	
	/**
	 * Determines if we're using the new or old Blackbox
	 *
	 * @return bool TRUE if we're using the new Blackbox
	 */
	public static function isNewBlackbox()
	{
		return (self::getInstance() instanceof Blackbox_Adapter_New);
	}
	
	/**
	 * Determines whether we want to use the new Blackbox or not.
	 *
	 * @return bool
	 */
	private static function useNewBlackbox()
	{
		$result = isset(SiteConfig::getInstance()->use_new_blackbox);
		
		//If use_new_blackbox isn't set, we'll try percentage-based weighting.
		if (!$result)
		{
			$percentage = (isset(SiteConfig::getInstance()->new_blackbox_percentage))
							? SiteConfig::getInstance()->new_blackbox_percentage
							: self::NEW_PERCENTAGE;

			$result = (mt_rand(1,100) <= $percentage);
		}
			
		return $result;
	}

	
	/**
	 * Gets disallowed states for a property
	 *
	 * @param string $property Property short
	 * @return array Array of disallowed states
	 */
	public function getDisallowedStates($property)
	{
		return array();
	}
	
	/**
	* Sets a blackbox object (used for oldschool sleeping)
	*
	* @param object $blackbox Blackbox object
	* @param object $winner Blackbox target object
	* @return void
	*/
	public function setBlackbox($blackbox, $winner = NULL)
	{
		$this->blackbox = $blackbox;

		if (is_object($winner))
		{
			$this->winner = $winner;
		}
	}

	/**
	 * Returns the state data from the blackbox object.
	 *
	 * @return Blackbox_IStateData object or NULL.
	 */
	abstract public function getStateData();

	/**
	 * Get the event log from the adapter
	 *
	 * @return Event_Log
	 */
	abstract protected function getEventLog();
	
	
	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function sleep()
	{
		if (!($this->blackbox instanceof OLPBlackbox_IRestorable))
		{
			throw new Exception("Attempting to sleep an unrestorable Blackbox state");
		}
		return $this->blackbox->sleep();
	}

	/**
	 * Restore the runtime state to from a previous sleep 
	 *
	 * @param array $data Data to restore the object's state
	 * @return void
	 */
	public function wakeup(array $data)
	{
		if (!($this->blackbox instanceof OLPBlackbox_IRestorable))
		{
			throw new Exception("Attempting to sleep an unrestorable Blackbox state");
		}
		$this->blackbox->wakeup($data);
		$log = $this->getEventLog();
		if ($log) $log->Log_Event('BLACKBOX_WAKEUP', OLPBlackbox_Config::EVENT_RESULT_PASS);
	}
}

?>
