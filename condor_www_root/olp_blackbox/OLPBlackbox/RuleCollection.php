<?php

/**
 * Adds event log stuff to the base rule collection
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_RuleCollection extends Blackbox_RuleCollection
{
	const PASS = 'pass';
	const FAIL = 'fail';

	/**
	 * The event name that will be hit for this rule.
	 *
	 * @var string
	 */
	protected $event_name;

	/**
	 * The stat name that will be hit for this rule.
	 *
	 * @var string
	 */
	protected $stat_name;

	/**
	 * Sets the event name for a rule.
	 *
	 * @param string $event the name of the event
	 * @return void
	 */
	public function setEventName($event)
	{
		$this->event_name = $event;
	}

	/**
	 * Sets the stat name for a rule.
	 *
	 * @param string $stat the name of the stat
	 * @return void
	 */
	public function setStatName($stat)
	{
		$this->stat_name = $stat;
	}

	/**
	 * Overloads base isValid and adds event hitting
	 *
	 * @param Blackbox_Data $data data passed to validate the collection
	 * @param Blackbox_IStateData $state_data state data passed to validate the collection
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = parent::isValid($data, $state_data);

		// hit pass/fail event
		$result = ($valid ? self::PASS : self::FAIL);
		$this->hitRuleEvent($result, $data, $state_data);

		return $valid;
	}

	/**
	 * Hits the event name for this rule.
	 *
	 * Returns TRUE if the event was hit successfully, FALSE on fail.
	 *
	 * @param string $result the result of the rule, used as the response for the event
	 * @param Blackbox_Data $data the data passed to the rule
	 * @param Blackbox_IState $state_data the state data passed to the rule
	 * @return bool
	 */
	protected function hitRuleEvent($result, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (strlen($this->event_name) > 0)
		{
			$this->hitEvent(
				$this->event_name,
				$result,
				$data->application_id,
				$state_data->campaign_name,
				$this->getConfig()->blackbox_mode
			);
		}
	}

	/**
	 * Hits an event in the event log.
	 *
	 * @param string $name           the name of the event to hit
	 * @param string $result         the reulst of the event
	 * @param int    $application_id the application ID to hit the event on
	 * @param string $target         the target associated with the event
	 * @param string $mode           the mode of the event
	 * @return void
	 */
	protected function hitEvent($name, $result, $application_id, $target = NULL, $mode = NULL)
	{
		OLPBlackbox_Config::getInstance()->event_log->Log_Event(
			$name,
			$result,
			$target,
			$application_id,
			$mode
		);
	}

	/**
	 * Hit a stat with the given name for the site's stats account.
	 *
	 * @param string $event_name the name of the stat to hit
	 * @param Blackbox_Data $data the data passed to the rule
	 * @param Blackbox_IState $state_data the state data passed to the rule
	 * @return void
	 */
	protected function hitSiteStat($event_name, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();

		if ($config->hit_stats_site)
		{
			$statpro = Stats_StatPro::getInstance($config->mode, $config->property_id);
			$statpro->hitStat($event_name, NULL, NULL, $config->track_key, $config->space_key);
		}
	}

	/**
	 * To facilitate unit testing this function should be how rules get the config.
	 *
	 * @return OLPBlackbox_Config object
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}
}

?>