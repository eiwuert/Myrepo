<?php
/**
 * Extends the Blackbox_Rule class to include funcationality for setting and hitting events and
 * stats.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Rule extends Blackbox_StandardRule implements 
	OLPBlackbox_Rule_IMultiDataSource, OLPBlackbox_Rule_IHasValueAccess, OLPBlackbox_Rule_ISkippable 
{
	
	const PARAM_ACTION = 3;
	const ACTION_EXCLUDE = 'EXCLUDE';
	const ACTION_VERIFY = 'VERIFY';
		
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
	 * Event bus events to hit based upon triggers.
	 *
	 * @param array
	 */
	protected $event_triggers = array();

	/**
	 * Which category this rule belongs to if it's a global rule.
	 * 
	 * @var string
	 */
	protected $global_rule_category;
	
	/**
	 * The source to use. Defaults to pull from blackbox data
	 * @var Integer
	 */
	protected $source = OLPBlackbox_Config::DATA_SOURCE_BLACKBOX;

	/**
	 * Run when the rule returns as valid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->isConfiguredToLogPasses($state_data))
		{
			$this->hitRuleEvent(OLPBlackbox_Config::EVENT_RESULT_PASS, $data, $state_data);
		}
		
		$this->triggerEvents(__FUNCTION__, $state_data);
	}

	/**
	 * Whether or not this rule should log passes in the event_log
	 *
	 * @param Blackbox_IStateData $state_data The state of the target running
	 * this rule.
	 * @return bool TRUE if this rule should log passes in event_log
	 */
	protected function isConfiguredToLogPasses(Blackbox_IStateData $state_data)
	{
		return (bool)$state_data->eventlog_show_rule_passes
			|| ($this->getConfig()->app_flags instanceof OLP_ApplicationFlag
				&& $this->getConfig()->app_flags->flagExists(OLP_ApplicationFlag::TEST_APP));
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

		$this->sendEvent($this->event_name, $state_data); //Send specific event related to the failed rule
		$this->setGlobalRuleFailureOnInvalid($state_data);
		
		$this->triggerEvents(__FUNCTION__, $state_data);
	}
	
	/**
	 * Trigger events for this rule.
	 *
	 * @param $trigger
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	protected function triggerEvents($trigger, Blackbox_IStateData $state_data)
	{
		$trigger = strtolower($trigger);
		
		if (isset($this->event_triggers[$trigger]))
		{
			foreach ($this->event_triggers[$trigger] AS $event_type)
			{
				$this->sendEvent($event_type, $state_data);
			}
		}
	}

	/**
	 * Send an event whenever the rule fails.
	 *
	 * @param string $event_type
	 * @param Blackbox_IStateData $state_data
	 */
	protected function sendEvent($event_type, Blackbox_IStateData $state_data)
	{
		if ($this->getEventBus() instanceof OLP_IEventBus)
		{
			$this->getEventBus()->notify(
				new OLPBlackbox_Event($event_type, $this->getDefaultEventAttrs($state_data))
			);
		}
	}
	
	/**
	 * Returns either the event bus or NULL.
	 *
	 * @return OLP_IEventBus|NULL
	 */
	protected function getEventBus()
	{
		$event_bus = NULL;
		
		if ($this->getConfig() instanceof OLPBlackbox_Config
			&& $this->getConfig()->event_bus instanceof OLP_IEventBus)
		{
			$event_bus = $this->getConfig()->event_bus;
		}
		
		return $event_bus;
	}

	/**
	 * Default attributes for an event sent from this object.
	 *
	 * @return array
	 */
	protected function getDefaultEventAttrs(Blackbox_IStateData $state_data)
	{
		return array(
			'runtime_target' => $state_data->name,
			'class' => get_class($this),
			OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_RULE,
			OLPBlackbox_Event::ATTR_SENDER_HASH => spl_object_hash($this),
		);
	}
	
	/**
	 * Set property global_rule_failure when rule fails (called in method $this->onInvalid() only).
	 * 
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function setGlobalRuleFailureOnInvalid(Blackbox_IStateData $state_data)
	{
		if (!empty($this->global_rule_category))
		{
			$state_data->global_rule_failure = $this->global_rule_category;
		}
	}

	/**
	 * Sets rule category.
	 *
	 * @param string $category
	 * @return void
	 */
	public function setGlobalRuleCategory($category)
	{
		$this->global_rule_category = $category;
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
	 * Adds event bus events for a rule.
	 *
	 * @param string $trigger
	 * @param string $event_type
	 * @return void
	 */
	public function addEventTrigger($trigger, $event_type)
	{
		$trigger = strtolower($trigger);
		
		if (!isset($this->event_triggers[$trigger])) $this->event_triggers[$trigger] = array();
		
		$this->event_triggers[$trigger][] = $event_type;
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
			$target_name = $state_data->campaign_name;
			if (empty($target_name)) $target_name = $state_data->name;
			
			$this->hitEvent(
				$this->event_name,
				$result,
				$data->application_id,
				$target_name,
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
			$statpro = Stats_StatPro::getInstance(
				$config->mode, 
				Stats_ClientList::getStatClient('username', OLPBlackbox_Config::STATS_BBRULES)
			);
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
	 * Hits stats for enterprise companies.
	 *
	 * @param string $event_name the name of the stat to hit
	 * @param Blackbox_Data $data the data passed to the rule
	 * @param Blackbox_IState $state_data the state data passed to the rule
	 * @return void
	 */
	public function hitTargetStat($event_name, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
		
		if ($config->hit_stats_site)
		{
			$resolved_target = EnterpriseData::resolveAlias($state_data->target_name);
			$statclient_properties = Stats_ClientList::getStatClient('property_short', $resolved_target);
			$statpro = Stats_StatPro::getInstance($config->mode, $statclient_properties);
			$space_key = $statpro->setupBucket($resolved_target);
			$statpro->hitStat($event_name, NULL, NULL, $config->track_key, $space_key);
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
		else if (!is_null($action_value = $this->getActionValue()))
		{
			$this->hitRuleEvent($action_value, $data, $state_data);
		}
		
		$this->triggerEvents(__FUNCTION__, $state_data);

		return TRUE;
	}

	/**
	 * Handler for when this rule, or children, throw an exception during a run.
	 *
	 * @param Exception $e Exception that happened during isValid()
	 * @param Blackbox_Data $data Info about the app being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 *
	 * @return bool Whether to treat the run
	 */
	protected function onError(Exception $e, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// Re-throw all propagating exceptions
		if ($e instanceof OLPBlackbox_IPropagatingException) throw $e;
		
		$config = $this->getConfig();
		if ($config->applog)
		{
			$config->applog->Write(sprintf(
				'%s->%s called with %s[%s]',
				__CLASS__,
				__FUNCTION__,
				get_class($e),
				$e->getMessage()),
				LOG_CRIT
			);
		}
		
		$this->hitRuleEvent(OLPBlackbox_Config::EVENT_RESULT_ERROR, $data, $state_data);
		$this->triggerEvents(__FUNCTION__, $state_data);
		
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
	 * Gets the current action for this rule.
	 * This value is determined by using the PARAM_ACTION index of
	 * the $this->params array.
	 *
	 * @return mixed
	 */
	protected function getActionValue()
	{
		$value = NULL;

		if (isset($this->params[self::PARAM_ACTION]))
		{
			$value = $this->params[self::PARAM_ACTION];
		}

		return $value;
	}

	/**
	 * Sets the action value for this rule
	 *
	 * The value that is set is determined by using the PARAM_ACTION index
	 * of the $this->params array
	 * 
	 * @param mixed $value
	 * @return void
	 */
	protected function setActionValue($value)
	{
		$this->params[self::PARAM_ACTION] = $value;
	}

	/**
	 * Gets the current param value for this rule.
	 * This value is determined by using the PARAM_VALUE index of
	 * the $this->params array.
	 *
	 * @return mixed
	 */
	protected function getParamValue()
	{
		$value = NULL;

		if (isset($this->params[self::PARAM_VALUE]))
		{
			$value = $this->params[self::PARAM_VALUE];
		}

		return $value;
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

	/**
	 * Get the value of the rule
	 *
	 * @return mixed
	 */
	public function getRuleValue()
	{
		return (is_array($this->params) && isset($this->params[self::PARAM_VALUE]))
			? $this->params[self::PARAM_VALUE]
			: NULL;
	}
	
	/**
	 * Set the value of the rule used to compare to input data during runRule().
	 * 
	 * @see OLPBlackbox_Rule_IHasValueAccess::setRuleValue()
	 * @param mixed $value The value to use for comparing to the input data.
	 * @return void
	 */
	public function setRuleValue($value)
	{
		$this->params[self::PARAM_VALUE] = $value;
	}
	
	/**
	 * Returns the cache value at $key.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	protected function getCacheValue($key)
	{
		return $this->getConfig()->memcache->get($key);
	}
	
	/**
	 * Adds a value to the cache.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expiration
	 * @return void
	 */
	protected function addCacheValue($key, $value, $expiration = Cache_Memcache::MEMCACHE_EXPIRE)
	{
		$this->getConfig()->memcache->add($key, $value, $expiration);
	}
	
	/**
	 * Returns the database connection object.
	 *
	 * @return DB_IConnection_1
	 */
	protected function getOLPConnection()
	{
		return $this->getConfig()->olp_db->getConnection()->getConnection();
	}
	
	/**
	 * Set the data source this rule will look at
	 * @param source
	 */
	public function setDataSource($source) 
	{
		if ($source == OLPBlackbox_Config::DATA_SOURCE_STATE) 
		{
			// In order to support this, every rule implementation we have
			// would need to be changed, and I don't need it yet.
			throw new RuntimeException("Unsupported data source: ".$source);
		}
		if (OLPBlackbox_Config::isValidDataSource($source))
		{
			$this->source = $source;
		}
	}
	
	/**
	 * respect the source when getting the stuff
	 * @return string
	 */
	protected function getDataValue(BlackBox_Data $data) 
	{
		switch ($this->source)
		{
			case OLPBlackbox_Config::DATA_SOURCE_BLACKBOX:
				return parent::getDataValue($data);
			case OLPBlackbox_Config::DATA_SOURCE_CONFIG:
				return $this->getValueFromConfig();
			default:
				throw new RuntimeException("Unsupported data source: ".$this->source);
		}
	}
	
	/**
	 * Return a value from the config
	 * @return mixed
	 */
	protected function getValueFromConfig() 
	{
		$value = NULL;
		$config = $this->getConfig();
		if (isset($this->params[self::PARAM_FIELD]))
		{
			if (is_array($this->params[self::PARAM_FIELD]))
			{
				foreach ($this->params[self::PARAM_FIELD] as $field)
				{
					$value[$field] = $config->{$field};
				}
			}
			else
			{
				$value = $config->{$this->params[self::PARAM_FIELD]};
			}
		}
		return $value;
	}
}
