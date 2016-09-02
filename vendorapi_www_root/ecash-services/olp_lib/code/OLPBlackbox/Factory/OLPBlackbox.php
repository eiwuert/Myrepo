<?php
/**
 * Factory to return blackbox object created from the olp_blackbox database structure.
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Factory_OLPBlackbox extends OLPBlackbox_Factory_ModelFactory
{
	const BLACKBOX_VERSION = '3.1';
	
	/**
	 * The debuging flags to use.
	 *
	 * @var OLPBlackbox_DebugConf
	 */
	protected $debug = NULL;

	/**
	 * The Blackbox config object.
	 *
	 * @var OLPBlackbox_Config
	 */
	protected $config;
	
	/**
	 * Used to produce StateData for campaign objects built inline with the 
	 * campaign factory.
	 * 
	 * @var OLPBlackbox_Factory_TargetStateData
	 */
	protected $target_state_data_factory;

	/**
	 * Construct a OLPBlackbox factory.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->config = OLPBlackbox_Config::getInstance();
		$this->debug = $this->config->debug;

		// log event to denote that this is "new" blackbox running
		$this->config->event_log->Log_Event('BLACKBOX_VERSION', self::BLACKBOX_VERSION);
	}
	
	/**
	 * @return OLPBlackbox_Root
	 */
	public function getEmptyBlackbox()
	{
		$blackbox = new OLPBlackbox_Root();
		$blackbox->setRootCollection(new Blackbox_TargetCollection());
	}

	/**
	 * Gets the blackbox object.
	 *
	 * Returns the OLPBlackbox_Root object or FALSE if the object has no targets.
	 *
	 * @param string $root_property_short
	 * @return OLPBlackbox_Root|bool
	 */
	public function getBlackbox($root_property_short)
	{
		//Set up blackbox and add the root collection
		$init_data = array(
			'deferred' => new OLPBlackbox_DeferredQueue(),
		);

		if ($this->config->blackbox_mode == OLPBlackbox_Config::MODE_ECASH_REACT)
		{
			$init_data['failure_reasons'] = new OLPBlackbox_FailureReasonList();
		}

		$state_data = new OLPBlackbox_StateData($init_data);
		$blackbox = new OLPBlackbox_Root($state_data);

		//Set up the root collection
		$root_target_model = $this->getModelFactory()->getModel('Target');
		$root_loaded = $root_target_model->loadByPropertyShort(
			$this->getRootPropertyShort($root_property_short),
			$this->getBlackboxType('COLLECTION')
			);
		
		if (!$root_loaded)
		{
			throw new Blackbox_Exception('could not load root collection');
		}
		elseif (!$root_target_model->active)
		{
			$this->config->event_log->Log_Event('INACTIVE_ROOT', OLPBlackbox_Config::EVENT_RESULT_PASS);
			return FALSE;
		}

		$root_target_collection = $this->getTargetCollectionFactory()->getTargetCollection(
			$root_target_model
		);

		// If we don't have a root collection, return FALSE.
		if (!$root_target_collection instanceof Blackbox_TargetCollection)
		{
			$this->config->event_log->Log_Event('SKIPPED_BLACKBOX', OLPBlackbox_Config::EVENT_RESULT_SKIP);
			return FALSE;
		}

		$root_rules = $root_target_collection->getRules();
		if (!$root_rules) $root_rules = new Blackbox_RuleCollection();
		$this->addLegacyStateCheckRules($root_rules);
		$this->addGlobalSuppression($root_rules);
		$root_target_collection->setRules($root_rules);

		/**
		 * order the rules here. Give the object instance of the rules that are needed in order
		 * @example $root_target_collection->orderRules(array(new OLPBlackbox_Rule_AllowMilitary(),
		 * new OLPBlackbox_Rule_MaximumAge)); would run military first and maximum age next.
		 * @see  olp_lib/lib/Blackbox/RuleCollection.php's orderRules() 
		 */
		$root_target_collection->orderRules(array(new OLPBlackbox_Rule_AllowMilitary()));
		//Set up blackbox and add the root collection
		$init_data = array(
			'deferred' => new OLPBlackbox_DeferredQueue(),
		);

		if ($this->config->blackbox_mode == OLPBlackbox_Config::MODE_ECASH_REACT)
		{
			$init_data['failure_reasons'] = new OLPBlackbox_FailureReasonList();
		}

		$state_data = new OLPBlackbox_StateData($init_data);
		$wht_state_data = new OLPBlackbox_StateDataDecoratorWithheldTargets($state_data);
		$blackbox = new OLPBlackbox_Root($wht_state_data);
		$blackbox->setRootCollection($root_target_collection);
		
		if ($this->config->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			// for legacy reasons, the ordering here is important.
			// "T0" must be after sequential preferred so we prepend to do that.
			$this->movePreferredTargets($blackbox);
			$this->addSequentialPreferred($blackbox);
		}
		$this->getListenerHandler()->attachListeners();

		return $blackbox;
	}
	
	/**
	 * Gets a proper RPS for MODE_ONLINE_CONFIRMATION, MODE_AGREE, and
	 * MODE_ECASH_REACT.
	 *
	 * @param string $root_property_short
	 * @return string
	 */
	protected function getRootPropertyShort($root_property_short)
	{
		$override_modes = array(
			OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION,
			OLPBlackbox_Config::MODE_AGREE,
			OLPBlackbox_Config::MODE_ECASH_REACT,
		);
		
		if (in_array($this->config->blackbox_mode, $override_modes))
		{
			$force_winner = array_shift($this->getFactoryConfig()->restrictedTargets());
			$cc_model = $this->getModelFactory()->getViewModel('CampaignCollection');
			$collection = $cc_model->getCollections($force_winner);

			/* @var $collection DB_Models_DefaultIterativeModel_1 */
			if ($collection->count() > 0)
			{
				$root_collection = $collection->next();
				if (!empty($root_collection->property_short))
				{
					$root_property_short = $root_collection->property_short;
				}
			}
		}
		
		return $root_property_short;
	}
	
	/**
	 * Returns a list of Blackbox_Models_View_TargetCollectionChild objects
	 * constructed from property_shorts.
	 *
	 * @todo This function assumes that it's going to find only one campaign
	 * with this property short and that it will have the correct weight for
	 * the preferred tier that's being assembled by the caller of this function.
	 * This is clearly not a good assumption, but until Blackbox admin can
	 * actually specify alternate root nodes, this will have to do. [DO]
	 *
	 * @todo Fix this function, it's horrible now. [DO]
	 *
	 * @param array|Traversable $property_shorts List of property shorts to
	 * retrieve models for.
	 * @return ArrayObject full of models.
	 */
	protected function getCampaignModelsByPropertyShort(array $property_shorts)
	{
		$model = new Blackbox_Models_View_TargetCollectionChild(
			$this->getDbConnection()
		);

		$return = new ArrayObject();

		foreach ($property_shorts as $property_short)
		{
			$found = NULL;
			foreach ($model->getChild($property_short) as $target)
			{
				$found = $target;
				break;	// see method comment
			}

			if (!$found)
			{
				// now try collections.
				// TODO: fix method name
				$iter = $model->getChild($property_short, Blackbox_Models_View_TargetCollectionChild::TYPE_COLLECTION);
				foreach ($iter as $target)
				{
					$found = $target;
					break;
				}
			}

			if ($found)
			{
				$return->append($found);
			}
		}

		return $return;
	}

	/**
	 * Move preferred targets out of Blackbox and into Tier 0
	 * @param OLPBlackbox_Root $blackbox The blackbox object to move the targets
	 * around in.
	 * @return void
	 */
	protected function movePreferredTargets(OLPBlackbox_Root $blackbox)
	{
		$tier_zero_exists = TRUE;

		$preferred_targets = $this->getPreferredTargetPropertyShorts();

		$campaign_objects = $this->getCampaignModelsByPropertyShort(
			$preferred_targets
		);

		$campaign_objects = $this->getFactoryConfig()->excludeTargets(
			$campaign_objects
		);

		if (empty($campaign_objects))
		{
			return;
		}

		$tier_zero = $blackbox->getTargetObject('T0');

		// if tier zero doesn't already exist (accept level probably excluded it)
		// create a "tier zero" but do NOT use the factory to assemble (or it
		// will put together all children of tier zero, not just our targets)
		if (!$tier_zero instanceof Blackbox_ITarget)
		{
			$tier_zero_exists = FALSE;
			/* @var $tier_zero OLPBlackbox_TargetCollection */
			$tier_zero = $this->createArtificialTierZero();
		}

		foreach ($campaign_objects as $campaign_object)
		{
			// check to see if the preferred target is in blackbox already
			$target = $this->getAndRemoveTarget(
				$campaign_object->property_short, $blackbox
			);

			if (!$target instanceof Blackbox_ITarget)
			{
				// target isn't in blackbox, so create it.
				$target = $this->getCampaignFactory($campaign_object->property_short)->getCampaign(
					$campaign_object,
					$this->getTargetStateDataFactory()
				);
			}

			if ($target instanceof Blackbox_ITarget)
			{
				$tier_zero->addTarget($target);
			}
		}

		if (!$tier_zero_exists)
		{
			$blackbox->prependTargetCollection(
				new OLPBlackbox_Campaign('T0', 0, 1, $tier_zero)
			);
		}
	}
	
	/**
	 * Make a new factory for producing StateData, used to build campaigns inline
	 * with the campaign factory.
	 * 
	 * This could use a caching implementation of OLP_IModel, but this is a fix
	 * going out on a release night and the frequency with which this factory even
	 * produces campaigns inline is very low.
	 * 
	 * @return OLPBlackbox_Factory_TargetStateData
	 */
	protected function getTargetStateDataFactory()
	{
		if (!$this->target_state_data_factory instanceof OLPBlackbox_Factory_TargetStateData)
		{
			$this->target_state_data_factory = new OLPBlackbox_Factory_TargetStateData(
				$this->getModelFactory()->getModel('TargetData'),
				$this->getModelFactory()->getModel('TargetDataType')
			);
		}
		
		return $this->target_state_data_factory;
	}

	/**
	 * Checks for preferred targets and normalizes them when found.
	 * @return NULL|array List of preferred target property shorts or NULL.
	 */
	protected function getPreferredTargetPropertyShorts()
	{
		if (!SiteConfig::getInstance()->preferred_targets)
		{
			return array();
		}

		$preferred_targets = SiteConfig::getInstance()->preferred_targets;
		if (!is_array($preferred_targets))
		{
			$preferred_targets = explode(',', $preferred_targets);
		}
		$preferred_targets = array_map('trim', $preferred_targets);
		$preferred_targets = array_filter($preferred_targets);

		return $preferred_targets;
	}

	/**
	 * Used to put preferred targets in a "tier zero" even if the accept level
	 * does not allow a T0 to be assembled normally.
	 * @return OLPBlackbox_TargetCollection
	 */
	protected function createArtificialTierZero()
	{
		$state_data = new OLPBlackbox_TierStateData(
			array('tier_number' => 0)
		);

		$collection = new OLPBlackbox_TargetCollection(
			'T0',
			$state_data,
			array('submitlevel0')
		);
		$collection->setPicker(new OLPBlackbox_PriorityPicker());

		return $collection;
	}

	/**
	 *
	 * @param string $property_short The property short of the target to get.
	 * @param OLPBlackbox_Root $blackbox The Blackbox object to search.
	 * @return NULL|Blackbox_ITarget The target, if present.
	 */
	protected function getAndRemoveTarget($property_short, OLPBlackbox_Root $blackbox)
	{
		$location = $blackbox->getTargetLocation($property_short);
		if (!is_array($location))
		{
			return NULL;
		}

		$target_object = $location['collection']->getTargetAtIndex(
			$location['index']
		);
		$location['collection']->unsetTargetIndex($location['index']);

		return $target_object;
	}

	/**
	 * Returns the sequential preferred targets configured for this site.
	 * @return array
	 */
	protected function getSequentialPreferredTargets()
	{
		if (empty($this->config->bb_sequential_preferred))
		{
			return array();
		}

		$sequential_preferred_targets = $this->config->bb_sequential_preferred;

		if (!is_array($sequential_preferred_targets))
		{
			$sequential_preferred_targets = explode(
				',', $sequential_preferred_targets
			);
		}

		$sequential_preferred_targets = array_map(
			'trim', $sequential_preferred_targets
		);

		return $sequential_preferred_targets;
	}

	/**
	 * Restrict and exclude array of property shorts.
	 * @param ArrayObject $target_models The models to filter based on the
	 * restrict/exclude configuration options.
	 * @return ArrayObject List of Blackbox_Models_IReadableTarget
	 */
	protected function restrictAndExclude(ArrayObject $target_models)
	{
		$target_models = $this->getFactoryConfig()->restrictTargets(
			$target_models
		);
		$target_models = $this->getFactoryConfig()->excludeTargets(
			$target_models
		);

		return $target_models;
	}

	/**
	 * Add sequential preferred tier to Blackbox object.
	 * @param Blackbox $blackbox Object to add sequential preferred objects to
	 * @return void
	 */
	protected function addSequentialPreferred(Blackbox_Root $blackbox)
	{
		$campaign_models = $this->getCampaignModelsByPropertyShort(
			$this->getSequentialPreferredTargets()
		);

		$campaign_models = $this->restrictAndExclude($campaign_models);

		if (empty($campaign_models))
		{
			return;
		}

		// Load into an array first because we don't know if we will
		// find a uk target in advance
		$sequential_target_objects = array();
		foreach ($campaign_models as $campaign_model)
		{
			$target_object = $this->getAndRemoveTarget(
				$campaign_model->property_short,
				$blackbox
			);

			if (!$target_object instanceof Blackbox_ITarget)
			{
				$target_object = $this->getCampaignFactory($campaign_model->property_short)->getCampaign(
					$campaign_model,
					$this->getTargetStateDataFactory()
				);
			}

			if ($target_object instanceof Blackbox_ITarget)
			{
				$sequential_target_objects[] = $target_object;
			}
		}

		if (!empty($sequential_target_objects))
		{
			// Figure out what tier to feed in the state_data for the collection
			foreach ($sequential_target_objects as $sequential_target_object)
			{
				if (preg_match('/\_uk\d*$/i', $sequential_target_object->property_short))
				{
					$sequential_state_data = new OLPBlackbox_TierStateData(
						array('tier_number' => 0)
					);
					$tags = array('submitlevel0');
					break;
				}
				else
				{
					$sequential_state_data = new OLPBlackbox_TierStateData(
						array('tier_number' => 1)
					);
					$tags = array('slt1');
				}
			}

			// Create a collection for sequential targets
			$sequential_collection = new OLPBlackbox_OrderedCollection(
				'sequential',
				$sequential_state_data,
				$tags
			);

			// Load all of the targets into the collection
			foreach ($sequential_target_objects as $sequential_target_object)
			{
				$sequential_collection->addTarget($sequential_target_object);
			}

			// The collection must be wrapped in a campaign
			// (weight is 1 because it's going in an ordered collection)
			$sequential_collection_campaign = new OLPBlackbox_Campaign(
				'sequential_campaign',
				0,
				1,
				$sequential_collection,
				new Blackbox_RuleCollection()
			);

			// Add sequential collection (campaign) to the start of the
			// root collection
			$blackbox->prependTargetCollection(
				$sequential_collection_campaign
			);
		}
	}

	/**
	 * Determines if the ABA rule should be added.
	 *
	 * This was created for mocking purposes, but hey, it's a pretty readable
	 * method name! [DO]
	 *
	 * @return bool
	 */
	protected function shouldSkipAbaRule()
	{
		if (OLPBlackbox_Config::getInstance()->unit_test)
		{
			// this sucks, but an OLP_Application requires things that are
			// irrelevant for factory unit tests [DO]
			return TRUE;
		}
		return $this->debug->flagFalse(OLPBlackbox_DebugConf::ABA)
			|| $this->config->app_flags->flagExists(OLP_ApplicationFlag::UK_APP);
	}

	/**
	 * Prevent leads with bad ABA numbers from going anywhere
	 *
	 * @param Blackbox_RuleCollection $rules Rule collection to add to
	 *
	 * @return Blackbox_IRule object
	 */
	protected function addAbaRule(Blackbox_RuleCollection $rules)
	{
		if ($this->shouldSkipAbaRule())
		{
			$rule = new OLPBlackbox_DebugRule();
		}
		else
		{
			$rule = new OLPBlackbox_Rule_AbaCheck();
		}
		if (OLPBlackbox_Config::getInstance()->blackbox_mode != OLPBlackbox_Config::MODE_BROKER)
		{
			$rule->setSkippable(TRUE);
		}
		$rule->setEventName(OLPBlackbox_Config::EVENT_ABA_BAD);
		$rule->setStatName(strtoupper(OLPBlackbox_Config::EVENT_ABA_BAD));
		$rules->addRule($rule);
	}

	/**
	 * Add some hard coded rules that Legacy BBx ran before any targets
	 * Only add rules if mode is not ECASH_REACT and bypass_state_exclusion is "empty"
	 *
	 * @param Blackbox_RuleCollection $rules Rule collection to add to
	 *
	 * @return NULL
	 */
	protected function addLegacyStateCheckRules(Blackbox_RuleCollection $rules)
	{
		static $excluded_states = array(
			'VA', 'WV', 'GA',
		);

		$bb_mode = OLPBlackbox_Config::getInstance()->blackbox_mode;
		if ($this->getConfig()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER
			&& empty($this->getConfig()->bypass_state_exclusion))
		{
			foreach ($excluded_states as $state)
			{
				$rules->addRule(new OLPBlackbox_Rule_LegacyStateExclude($state));
			}
		}
	}

	/**
	 * Adds global suppression lists to a rule collection
	 *
	 * @param Blackbox_RuleCollection $rules
	 * @return void
	 */
	protected function addGlobalSuppression(Blackbox_RuleCollection $rules)
	{
		if (OLPBlackbox_Config::getInstance()->debug->debugSkipRule()
			&& strtoupper($this->getConfig()->mode) != 'LIVE')
		{
			$rule = new OLPBlackbox_DebugRule();
		}
		else
		{
			$lists_model = $this->getModelFactory()->getModel('Lists');
			$add_lists = array();

			$global_suppression_lists = array(
				'Global Track Key Suppression' => 'EXCLUDE'
			);

			foreach ($global_suppression_lists as $name => $type)
			{
				if ($lists_model->loadBy(array('name' => $name)))
				{
					$add_lists[$lists_model->list_id] = $type;
				}
			}

			if (!empty($add_lists))
			{
				$suppression_factory = new OLPBlackbox_Factory_Legacy_SuppressionList();
				$rule = $suppression_factory->getSuppressionLists($add_lists);
			}
		}

		$rules->addRule($rule);
	}

	/**
	 * Set category on given rule/rules.
	 * 
	 * @param OLPBlackbox_Rule|Blackbox_RuleCollection $rules
	 * @param string $category
	 * @return void
	 */
	protected function setGlobalRuleCategory($rules, $category)
	{
		if ($rules instanceof Blackbox_RuleCollection)
		{
			foreach ($rules as $rule)
			{
				$this->setGlobalRuleCategory($rule, $category);
			}
		}
		else if ($rules instanceof OLPBlackbox_Rule)
		{
			$rules->setGlobalRuleCategory($category);
		}
	}
}

?>
