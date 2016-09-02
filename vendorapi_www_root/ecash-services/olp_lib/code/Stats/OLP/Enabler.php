<?php

/** Allow this system to be turned on and off.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class Stats_OLP_Enabler extends Stats_OLP_Base
{
	/**
	 * @var bool
	 */
	protected $enabler_allow;
	
	/** Initialize statpro instance;
	 *
	 * @param string $mode The mode statpro is running under.
	 * @param array $property_data Stat client's properties.
	 * @param int $application_id
	 */
	protected function __construct($mode, array $property_data, $application_id = NULL)
	{
		$this->enabler_allow = TRUE;
		
		parent::__construct($mode, $property_data, $application_id);
	}
	
	/** Have the enabler work in an automatic, magical way. If we cannot
	 * find what mode to run based upon historical information, use the passed
	 * in default value.
	 *
	 * @param bool $default_on
	 * @return bool
	 */
	public function setEnablerAutomaticMode($default_on = TRUE)
	{
		if (isset($_SESSION['stats']['use_new_system']))
		{
			$this->setEnablerMode($_SESSION['stats']['use_new_system']);
		}
		else
		{
			$this->setEnablerMode($default_on);
			$_SESSION['stats']['use_new_system'] = (int)$this->enabler_allow;
		}
		
		return $this->enabler_allow;
	}
	
	/** Force the enabler into a specific mode.
	 *
	 * @param bool $turn_on
	 * @return void
	 */
	public function setEnablerMode($turn_on)
	{
		$this->enabler_allow = (bool)$turn_on;
	}
	
	/** Get the current enabler status mode.
	 *
	 * @return bool
	 */
	public function getEnablerMode()
	{
		return $this->enabler_allow;
	}
	
	/** If we don't want to actually hit the stat, we need to tell the
	 * children that we hit this stat even if we didn't, so they would still
	 * process as if we were running.
	 *
	 * @param string $event_type_key
	 * @param int $date_occurred
	 * @param int $event_amount
	 * @param string $track_key
	 * @param string $space_key
	 * @return bool
	 */
	public function hitStat($event_type_key, $date_occurred = NULL, $event_amount = NULL, $track_key = NULL, $space_key = NULL)
	{
		if ($this->enabler_allow)
		{
			return parent::hitStat($event_type_key, $date_occurred, $event_amount, $track_key, $space_key);
		}
		else
		{
			return TRUE;
		}
	}
}

?>
