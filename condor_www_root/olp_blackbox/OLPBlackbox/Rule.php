<?php
/**
 * Extends the Blackbox_Rule class to include funcationality for setting and hitting events and
 * stats.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Rule extends Blackbox_StandardRule
{
	/**
	 * The stat name that will be hit for this rule.
	 *
	 * @var string
	 */
	protected $stat_name;

	/**
	 * The event name that will be hit for this rule.
	 *
	 * @var string
	 */
	protected $event_name;

	/**
	 * Whether or not this rule is allowed to skip.
	 *
	 * @var bool
	 */
	protected $skippable = FALSE;

	/**
	 * Run when the rule returns as valid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->hitRuleEvent(OLPBlackbox_Config::EVENT_RESULT_PASS, $data, $state_data);
		
		// By default, do not hit stats on pass, it floods the server with 
		// data that vendors aren't really interested in.
	}

	/**
	 * Run when the rule returns as invalid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->hitRuleEvent(OLPBlackbox_Config::EVENT_RESULT_FAIL, $data, $state_data);
		$this->hitRuleStat(OLPBlackbox_Config::STAT_RESULT_FAIL, $data, $state_data);
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
	 * Sets the bool that determines whether onSkip returns true or false.
	 *
	 * @param bool $skippable TRUE if skipping the rule is success, FALSE if it should fail on skip
	 *
	 * @return void
	 */
	public function setSkippable($skippable = TRUE)
	{
		$this->skippable = $skippable;
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
	 * Hits the stat specified for this rule.
	 *
	 * If $result is specified it will append that result to the end of the stat name.
	 *
	 * Example: hitRuleStat(OLPBlackbox_Config::STAT_RESULT_PASS); would hit stat_name_pass
	 *
	 * @param string $result the result of the rule, appended to the end of the stat
	 * @param Blackbox_Data $data the data passed to the rule
	 * @param Blackbox_IState $state_data the state data passed to the rule
	 * @return void
	 */
	protected function hitRuleStat($result = NULL, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$stat = $this->stat_name;

		if (strlen($stat) > 0)
		{
			if (!is_null($result))
			{
				// append the result to the stat name
				$stat .= '_' . $result;
			}

			$this->hitBBStat($stat, $data, $state_data);
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
		$this->getConfig()->event_log->Log_Event(
			$name,
			$result,
			$target,
			$application_id,
			$mode
		);
	}

	/**
	 * Hit a stat with the given name for the Blackbox stats account.
	 *
	 * @param string $event_name the name of the stat to hit
	 * @param Blackbox_Data $data the data passed to the rule
	 * @param Blackbox_IState $state_data the state data passed to the rule
	 * @return void
	 */
	protected function hitBBStat($event_name, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
		
		if ($config->hit_stats_bb)
		{
			$statpro = Stats_StatPro::getInstance($config->mode, OLPBlackbox_Config::STATS_BBRULES);
			$statpro->enableBatch();
			
			$space_key = $statpro->createSpaceKey(
				array(
					'target_id' => $state_data->campaign_id,
					'bb_mode' => $config->blackbox_mode,
					'page_id' => $config->page_id,
					'promo_id' => $config->promo_id,
					'promo_sub_code' => $config->promo_sub_code,
				),
				FALSE
			);
			
			$statpro->hitStat($event_name, NULL, NULL, $config->track_key, $space_key);
		}
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
	 * Called when this rule returns FALSE from canRun() in isValid()
	 *
	 * @param Blackbox_Data $data Data related to the application we're processing.
	 * @param Blackbox_IStateData $state_data Data related to the ITarget running this rule.
	 * @return bool Determines if this is a pass/fail on skip.
	 */
	protected function onSkip(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!$this->skippable)
		{
			/**
			 * This rule is not skippable, but onSkip() has been called.
			 * This is an error, because we either should have enough information
			 * to run (which means onSkip would not be called) or if it's
			 * possible the info isn't there, this should be skippable.
			 * This logging is done to make sure we're aware of the situation.
			 */
			$error_message = sprintf(
				"Rule %s(%s)'s onSkip was called but is not skippable!",
				__CLASS__,
				$this->name
			);
			$this->getConfig()->applog->Write(
				$error_message, LOG_CRIT
			);
			$this->onInvalid($data, $state_data);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Handler for when this rule, or children, throw an exception during a run.
	 *
	 * @param Blackbox_Exception $e Exception that happened during isValid()
	 * @param Blackbox_Data $data Info about the app being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 *
	 * @return bool Whether to treat the run
	 */
	protected function onError(Blackbox_Exception $e, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->getConfig()->applog->Write(sprintf(
			'%s->%s called with %s[%s]',
			__CLASS__,
			__FUNCTION__,
			get_class($e),
			$e->getMessage()),
			LOG_CRIT
		);
		$this->hitRuleEvent(OLPBlackbox_Config::EVENT_RESULT_ERROR, $data, $state_data);
		return FALSE;
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
	
	/**
	 * Returns the currently set event name.
	 *
	 * @return string
	 */
	public function getEventName()
	{
		return $this->event_name;
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
		if ($this->params[self::PARAM_VALUE])
		{
			$param_value = " (";
			if (is_array($this->params[self::PARAM_VALUE]))
			{
				$param_value .= join(",", $this->params[self::PARAM_VALUE]);
			}
			else
			{
				$param_value .= $this->params[self::PARAM_VALUE];
			}
			$param_value .= ")";
		}
		$string .= "Rule: " . $this->event_name . "      [" . $this->name . $param_value . "]\n";
		return $string;
	}
}
?>
