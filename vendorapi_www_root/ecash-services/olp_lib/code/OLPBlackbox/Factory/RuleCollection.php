<?php
/**
 * Factory for creating OLP Blackbox rule collections.
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class OLPBlackbox_Factory_RuleCollection extends OLPBlackbox_Factory_ModelFactory
{
	const DEFAULT_CACHE_TYPE = 'default_collection';
	const PICK_TARGET_CACHE_TYPE = 'pick_target_collection';
	
	/**
	 * Array of rule collection factory instances.
	 *
	 * Array will keep instances of the different company instances.
	 *
	 * @var array
	 */
	protected static $factory_instances = array();

	/**
	 * Instance of the rule factory to use.
	 *
	 * @var OLPBlackbox_Factory_Rule
	 */
	protected $rule_factory;
	
	/**
	 * Whether to cache collections and try to pull those cached collections.
	 *
	 * @var bool
	 */
	protected $cache_collections = FALSE;

	/**
	 * Returns an instantiation of a rule collection object for use in olpblackbox.
	 * @param Blackbox_Models_IReadableTarget $target_model The model for which
	 * to fetch the rule collection. (Uses $target_model->rule_id).
	 * @return Blackbox_RuleCollection
	 */
	protected function newRuleCollection(Blackbox_Models_IReadableTarget $target_model)
	{
		// Get what rule collection class that needs to be created
		$rule_collection_class_ref_table = $this->getModelFactory()->getReferenceTable('RuleCollectionClass', TRUE);
		
		if (!empty($target_model->rule_collection_class_id))
		{
			$class_name = $rule_collection_class_ref_table->toName($target_model->rule_collection_class_id);
		}
		else
		{
			$class_name = "OLPBlackbox_RuleCollection";
		}
		
		// Create the rule collection
		$rule_collection = new $class_name();
		$rule_collection->setEventName(OLPBlackbox_Config::EVENT_RULES);
		
		$this->subscribeCollectionToEvents($rule_collection);

		return $rule_collection;
	}
	
	/**
	 * Constructor for OLPBlackbox_Factory_RuleCollection.
	 *
	 * @param OLPBlackbox_Factory_Rule $rule_factory The factory to use to create rules
	 */
	public function __construct(OLPBlackbox_Factory_Rule $rule_factory)
	{
		$this->rule_factory = $rule_factory;
	}
	
	/**
	 * Subscribe rule collections to events they listen to on the event bus.
	 *
	 * @param OLP_ISubscriber $rule_collection
	 * @return void
	 */
	protected function subscribeCollectionToEvents(OLP_ISubscriber $rule_collection)
	{
		if (!$this->getConfig()->event_bus instanceof OLP_IEventBus) return;
		
		// all rule collections listen for timeout events.
		$this->getConfig()->event_bus->subscribeTo(
			OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT, $rule_collection
		);
		
		// Listen to the global military failure event
		$this->getConfig()->event_bus->subscribeTo(
			OLPBlackbox_Event::EVENT_GLOBAL_MILITARY_FAILURE, $rule_collection
		);
	}
	
	/**
	 * Sets whether the rule collection class will cache collections.
	 *
	 * Default is to not cache collections.
	 *
	 * @param bool $cache
	 * @return void
	 */
	public function setCaching($cache)
	{
		$this->cache_collections = (bool)$cache;
	}

	/**
	 * Create an OLPBlackbox_RuleCollection
	 *
	 * @param Blackbox_Models_IReadableTarget $target_model Model to use to find
	 * the rules.
	 * @param OLPBlackbox_ITarget $target The target to set the rules for.
	 * @return void
	 */
	public function setRuleCollections(
		Blackbox_Models_IReadableTarget $target_model,
		OLPBlackbox_ITarget $target
	)
	{
		$rule_collection = FALSE;
		$pick_target_collection = FALSE;
		
		$duplicate_lead_rule = FALSE;
		
		if ($this->cache_collections)
		{
			$rule_collection = $this->getCachedCollection($target_model->rule_id, self::DEFAULT_CACHE_TYPE);
			$pick_target_collection = $this->getCachedCollection($target_model->rule_id, self::PICK_TARGET_CACHE_TYPE);
		}
		
		if ($rule_collection === FALSE || $pick_target_collection === FALSE || !$this->cache_collections)
		{
			$rule_collection = $this->newRuleCollection($target_model);
			
			$pick_target_collection = new OLPBlackbox_RuleCollection();
			
			$daily_model = NULL;
			$hourly_model = NULL;
			
			// Create and add rules
			foreach ($this->getActiveRules($target_model->rule_id) as $rule_model)
			{
				
				$rule_definition_model = $this->getRuleDefinition($rule_model->rule_definition_id);
				
				// limit rules have their own factory, see after loop.
				if ($rule_definition_model->name_short == 'daily_limit')
				{
					$daily_model = $rule_model;
					continue;
				}
				
				if ($rule_definition_model->name_short == 'hourly_limit')
				{
					$hourly_model = $rule_model;
					continue;
				}
				
				if ($rule_definition_model->name_short == 'frequency_decline')
				{
					// freq scoring does some special deserialization and stuff
					$rule = $this->rule_factory->getFrequencyScoreRule($rule_model);
				}
				else
				{
					$rule = $this->rule_factory->getRule($rule_model);
				}
				
				
				if ($rule instanceof Blackbox_IRule)
				{
					if ($this->isDuplicateLeadRule($rule))
					{
						if (!empty($duplicate_lead_rule)) {
							throw new Blackbox_Exception(
								"Target ({$target}) has multiple duplicate lead rules!");
						}
						$duplicate_lead_rule = $rule;
					}
					elseif ($rule instanceof OLPBlackbox_ISellRule)
					{
						if (!$target->hasSellRule())
						{
							$target->setSellRule($rule);
						}
						else
						{
							throw new Blackbox_Exception("target ($target) has two sell rules!");
						}
					}
					elseif ($rule instanceof OLPBlackbox_IPickTargetRule)
					{
						$pick_target_collection->addRule($rule);
					}
					else
					{
						$rule_collection->addRule($rule);
					}
				}
			}
	
			// Add limit rule(s) if needed
			if ($daily_model)
			{
				$limit_rule = $this->getLimitCollectionFactory($target_model)->getLimitCollection(
					$target_model,
					$daily_model,
					$hourly_model
				);
				
				if ($limit_rule != FALSE)
				{
					$rule_collection->addRule($limit_rule);
				}
			}
			
			// manually add the auto campaign shutoff check to the end of the rule collection for non enterprise campaigns
			if (isset($target_model->class_name)
				&& strcasecmp($target_model->class_name, 'CAMPAIGN') == 0
				&& !EnterpriseData::isEnterprise($target_model->property_short))
			{
				$rule_collection->addRule($this->rule_factory->getOLPBlackboxRule('CampaignShutoff'));
			}
	
			$this->addWithheldTargetsRule($pick_target_collection);

			/**
			 * READ THIS BEFORE ADDING ANYTHING BELOW THIS POINT IN THIS FUNCTION!!!!!
			 * 
			 * The duplicate lead rule MUST run just before posting as it will mark this lead
			 * as posted to the supplied target.  If it is not last, the likelihood of that target
			 * failing due to false positives in duplicate lead detection will be great.
			 * [GFORGE #39975[AE]] 
			 */
			if (!empty($duplicate_lead_rule))
			{
				$pick_target_collection->addRule($duplicate_lead_rule);
			}
			
			// If we are supposed to cache the collection, cache it
			if ($this->cache_collections)
			{
				$this->cacheRuleCollection($target_model->rule_id, $rule_collection, self::DEFAULT_CACHE_TYPE);
				$this->cacheRuleCollection($target_model->rule_id, $pick_target_collection, self::PICK_TARGET_CACHE_TYPE);
			}
		}
		
		$target->setRules($rule_collection);
		$target->setPickTargetRules($pick_target_collection);
	}

	/**
	 * Generates and returns the cache key for the rule collection.
	 *
	 * @param int $rule_id
	 * @param string $type a string with the type of collection (pick target or default)
	 * @return string
	 */
	public function getCacheKey($rule_id, $type)
	{
		return sprintf('%s/%s/%d', $this->getDebugConfCacheKey(), $type, $rule_id);
	}
	
	/**
	 * Returns a cached collection if available.
	 *
	 * @param int $rule_id
	 * @param string $type a string with the type of collection (pick target or default)
	 * @return Blackbox_IRule
	 */
	public function getCachedCollection($rule_id, $type)
	{
		return $this->getConfig()->memcache->get($this->getCacheKey($rule_id, $type));
	}
	
	/**
	 * Caches the collection into memcache.
	 *
	 * @param int $rule_id
	 * @param Blackbox_IRule $collection
	 * @param string $type a string with the type of collection (pick target or default)
	 * @return void
	 */
	public function cacheRuleCollection($rule_id, Blackbox_IRule $collection, $type)
	{
		$this->getConfig()->memcache->set($this->getCacheKey($rule_id, $type), $collection);
	}
	
	/**
	 * Adds a WithheldTargets rule to the collection if none is present.
	 *
	 * @todo Remove this when GF#20080 is done. Currently, withheld targets rule
	 * has the list of other targets to invalidate. Eventually, though, each
	 * withheld targets rule should point to the parent and thus each rule can
	 * be assembled like the other rules since the data will be specific to the
	 * target assigned the rule. Until then, we have to provide a WithheldTargets
	 * rule for each target because it must invalidate every target that's
	 * listed by another, unrelated target. [DO]
	 *
	 * @param OLPBlackbox_RuleCollection $collection The collection to add the
	 * WithheldTargets rule to.
	 * @return void
	 */
	protected function addWithheldTargetsRule(OLPBlackbox_RuleCollection $collection)
	{
		$found = FALSE;
		
		foreach ($collection as $rule)
		{
			/* @var $rule OLPBlackbox_Rule_WithheldTargets */
			if ($rule instanceof OLPBlackbox_Rule_WithheldTargets)
			{
				$found = TRUE;
				break;
			}
		}
		
		if (!$found)
		{
			$withheld_targets_rule = new OLPBlackbox_Rule_WithheldTargets();
			$withheld_targets_rule->setEventName(OLPBlackbox_Config::EVENT_WITHHELD_TARGETS);
			$collection->addRule($withheld_targets_rule);
		}
	}

	/**
	 * Returns the rule collection containing the suppression lists.
	 *
	 * @param array $lists an array of lists to add to the collection
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getSuppressionLists(array $lists)
	{
		$suppression_list = new OLPBlackbox_Factory_Legacy_SuppressionList();

		return $suppression_list->getSuppressionLists($lists);
	}
	
	/**
	 * Returns the latest revision id for a rule collection.
	 * @param int $rule_collection_id The rule_collection's id
	 * @return NULL|int active rule_revision.rule_revision_id
	 */
	protected function getLatestRuleRevision($rule_collection_id)
	{
		static $rule_revision_model = NULL;
		if (!$rule_revision_model)
		{
			$rule_revision_model = $this->getModelFactory()->getModel('RuleRevision');
		}
		
		$loaded = $rule_revision_model->loadBy(array(
			'rule_id' => $rule_collection_id,
			'active' => 1)
		);

		if (!$loaded)
		{
			return NULL;
		}
		
		return $rule_revision_model->rule_revision_id;
	}
	
	/**
	 * Return the rule_mode_type_id which represents the current blackbox_mode.
	 * @return int rule_mode_type.rule_mode_type_id
	 */
	protected function getCurrentModeID()
	{
		$rule_mode_type_table = $this->getModelFactory()->getReferenceTable('RuleModeType', TRUE);
		$rule_mode_type_id = $rule_mode_type_table->toId($this->getConfig()->blackbox_mode);
		
		if (!$rule_mode_type_id)
		{
			throw new Blackbox_Exception(sprintf(
				'unknown mode: %s', $this->getConfig()->blackbox_mode)
			);
		}
		
		return $rule_mode_type_id;
	}
	
	/**
	 * Retrieves an active rule by name.
	 * @param int $rule_collection_id The rule ID to pull the rule with.
	 * @param string $rule_name The name of the rule to search for.
	 * @return NULL|Blackbox_Models_Rule
	 */
	public function getActiveRule($rule_collection_id, $rule_name)
	{
		foreach ($this->getActiveRules($rule_collection_id) as $rule_model)
		{
			if (strcasecmp($rule_model->name, $rule_name) == 0)
			{
				return $rule_model;
			}
		}
		
		return NULL;
	}
	
	/**
	 * Gets an iterative list of active rules.
	 *
	 * @param int $rule_collection_id
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getActiveRules($rule_collection_id)
	{
		/* @var $rule_model Blackbox_Models_Rule */
		$rule_model = $this->getModelFactory()->getModel('Rule');
		$rules = $rule_model->getActiveRules($rule_collection_id, $this->getCurrentModeID());
		
		return $rules;
	}

	/**
	 * Is rule a duplicate lead rule
	 *
	 * @param Blackbox_IRule $rule
	 * @return bool
	 */
	protected function isDuplicateLeadRule(Blackbox_IRule $rule)
	{
		return $rule instanceof OLPBlackbox_Rule_DuplicateLead;
	}
}
?>
