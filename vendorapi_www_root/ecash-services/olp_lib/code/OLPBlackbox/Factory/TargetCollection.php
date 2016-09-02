<?php
/**
 * Factory for creating OLP Blackbox Target Collections
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_TargetCollection extends OLPBlackbox_Factory_ModelFactory
{
	/**
	 * Caching target data type model to be passed to the campaign factory.
	 *
	 * @var OLP_IModel
	 */
	protected $target_data_type_model;
	
	/**
	 * Storage for target collection target data
	 *
	 * @var OLPBlackbox_Factory_Storage_TargetData
	 */
	protected $target_data_store;

	/**
	 * Get a target collection for a target model collection
	 *
	 * @param Blackbox_Models_Target $target_model
	 * @return Blackbox_Models_IReadableTarget
	 * @todo Need to fix weight order (?)
	 */
	public function getTargetCollection(Blackbox_Models_IReadableTarget $target_model)
	{
		if (!$this->targetIsCollection($target_model))
		{
			$this->logError(
				sprintf(
					"Cannot build OLPBlackbox_TargetCollection from non-collection model %s",
					get_class($target_model)
				),
				$target_model->property_short
			);
		}
		
		if (!$this->getFactoryConfig()->isAllowedCollection($target_model))
		{
			return NULL;
		}

		// get immediate children (non-recursive)
		$children = $this->getTargetCollectionChildren($target_model);
		
		// ask the config object which children to include
		$allowed_children = $this->getFactoryConfig()->getAllowedChildren($children);
		
		if (!$allowed_children->count())
		{
			return NULL;
		}
		
		// we have children we can build, make a TargetCollection
		/* @var $target_collection Blackbox_TargetCollection */
		$target_collection = $this->getCollectionClass($target_model);
		
		// Get array of limit stats that have hit their cap
		$capped_stats = $this->getConfig()->capped_stats;
		
		$target_tags = $target_collection->getStateData()->target_tags->getData();
		$target_name = $target_collection->getStateData()->name;
		// If we're an invalid target (IE no property short, or we're over the capped stats for
		// this collection, return NULL)
		if (!$target_collection
			|| !$this->underCappedStats($target_name, $capped_stats, $target_tags)
		)
		{
			return NULL;
		}
		
		//Add rule collection for target collection here
		$this->getRuleCollectionFactory($target_model->property_short)
			->setRuleCollections($target_model, $target_collection);
			
		// cache version of data models.
		$state_data_factory = $this->makeStateDataFactoryFrom($allowed_children);

		$children = array();
		foreach ($allowed_children as $child)
		{
			if (!$child->active)
			{
				/* inactive children can sometimes be gathered
				 * {@see getTargetCollectionChildren} */
				continue;
			}

			// Try to get a child
			try
			{
				$campaign_factory = $this->getCampaignFactory($child->property_short);
				
				$campaign = $campaign_factory->getCampaign($child, $state_data_factory);
				
				if ($campaign instanceof Blackbox_ITarget)
				{
					$campaign_target = $campaign->getTarget();
					
					$target_tags = $campaign_target->getStateData()->target_tags->getData();
					if ($this->underCappedStats($campaign->getName(), $capped_stats, $target_tags))
					{
						$target_collection->addTarget($campaign);
					}
					
					if ($campaign_target instanceof Blackbox_TargetCollection)
					{
						$children = array_merge($children, $campaign_target->getStateData()->children);
					}
					else
					{
						$children[] = $child->property_short;
					}
				}
			}
			catch (PDOException $e)
			{
				// Re-throw PDO exceptions for factories
				throw $e;
			}
			catch (MySQL_Exception $e)
			{
				// Re-throw MySQL exceptions for factories
				throw $e;
			}
			catch (Exception $e)
			{
				// Catch and log all other exceptions
				$this->logError(
					sprintf(
						"Exception thrown getting child %s for target collection %s: %s",
						$child->property_short,
						$target_model->property_short,
						$e->getMessage()
					),
					$child->property_short
				);
			}
		}
		
		$target_collection->getStateData()->children = $children;

		$this->getListenerHandler()->registerChild($target_model->property_short, 'COLLECTION', $target_collection);
		return $target_collection;
	}
	
	/**
	 * 
	 * @param String $target_name
	 * @param array $capped_stats
	 * @param array $target_tags
	 * @return boolean
	 */
	protected function underCappedStats($target_name, $capped_stats, $target_tags)
	{
		// Determine this target's tags are in the capped tags in the config
		$cap_reached = FALSE;
		
		if (!empty($target_tags) && !empty($capped_stats))
		{
			$cap_reached = count(array_intersect($target_tags, $capped_stats)) > 0;
		}

		// Determine if we're debug skipping stats
		$limit_skip = $this->getConfig()->debug->debugSkipRule(OLPBlackbox_DebugConf::LIMITS);

		// If the cap has been reached and we're not skipping limits, don't add the campaign and log that fact 
		if ($cap_reached && !$limit_skip)
		{
			$this->logEvent(
				OLPBlackbox_Config::EVENT_STAT_CAP,
				OLPBlackbox_Config::EVENT_RESULT_FAIL,
				$target_name);
			return FALSE;
		}
		else
		{
			// If the cap has been reached log that the stat cap was debug skipped
			if ($cap_reached)
			{
				$this->logEvent(
					OLPBlackbox_Config::EVENT_STAT_CAP,
					OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP,
					$target_name);
			}
		}
		return TRUE;
	}
	
	/**
	 * Make a state data factory using caching target_data models.
	 * 
	 * @param ArrayObject $models The list of models that should have target data
	 * information pulled by this method.
	 * @return OLPBlackbox_Factory_TargetStateData
	 */
	protected function makeStateDataFactoryFrom(ArrayObject $models)
	{
		if (!$this->target_data_type_model instanceof OLP_IModel)
		{
			// assumption here is that types of target data the factory 
			// is interested in won't change.
			$this->target_data_type_model = $this->newCachedTargetDataTypeModel();
		}

		$data_type_ids = array();

		foreach ($this->target_data_type_model->loadAllBy() as $type) 
		{
			$data_type_ids[] = $type->target_data_type_id;
		}
		
		return new OLPBlackbox_Factory_TargetStateData(
			$this->newCachedTargetDataModel($models, $data_type_ids), 
			$this->target_data_type_model
		);
	}
	
	/**
	 * Return a new cached version of a TargetDataType model.
	 * 
	 * @return OLP_Models_Cacheable A caching version of a Blackbox_Models_TargetDataType.
	 */
	protected function newCachedTargetDataTypeModel()
	{
		$target_data_type_model = $this->getModelFactory()->getModel('TargetDataType');
		
		$relevant_data_types = $target_data_type_model->loadAllBy(
			array('name' => OLPBlackbox_Factory_TargetStateData::relevantFactoryKeys())
		);
		
		$target_data_type_cache = new Cache_Model_InMemory();
		$target_data_type_cache->setCache($relevant_data_types);
		
		
		return new OLP_Models_Cacheable(
			$target_data_type_model, $target_data_type_cache
		);
	}
	
	/**
	 * Return a new caching version of the TargetData model using all the 
	 * target_ids of the child models ($models) and all the $target_data_type_ids.
	 *
	 * @param array|Traversable $models List of Target models to get target data
	 * for.
	 * @param array $target_data_type_ids list of target_data_type ids to use to select only the 
	 * target data which is relevant for factories.
	 * @return OLP_Models_Cacheable A caching version of a Blackbox_Models_TargetData
	 * object.
	 */
	protected function newCachedTargetDataModel($models, $target_data_type_ids)
	{
		$target_data_cache = new Cache_Model_InMemory();
		$target_data_model = $this->getModelFactory()->getModel('TargetData');
		$relevant_target_data = array();
		
		$target_ids = $this->getIdsFromModelsAndChildren($models);
		if ($target_ids && $target_data_type_ids)
		{
			$relevant_target_data = $target_data_model->loadAllBy(
				array('target_id' => $target_ids, 'target_data_type_id' => $target_data_type_ids)
			);
		}
		
		$target_data_cache->setCache($relevant_target_data);
		
		return new OLP_Models_Cacheable($target_data_model, $target_data_cache);
	}
	
	/**
	 * Get all target_ids of the models passed in AND any target_ids from the
	 * direct children of the targets these models represent.
	 *
	 * @param array|Traversable $models List of OLP_IModel objects.
	 * @return array List of target_ids
	 */
	protected function getIdsFromModelsAndChildren($models)
	{
		// ids of all children, result
		$target_ids = array();
		
		// ids of campaigns which we will use to query for child targets.
		$campaign_ids = array();
		
		foreach ($models as $model)
		{
			if ($this->getBlackboxTypeName($model->blackbox_type_id) == self::CAMPAIGN_TYPE)
			{
				$target_ids[] = $model->target_id;
				$campaign_ids[] = $model->target_id;
			}
			elseif ($this->getBlackboxTypeName($model->blackbox_type_id) == self::TARGET_TYPE)
			{
				$target_ids[] = $model->target_id;
			}
		}
		
		if ($campaign_ids)
		{
			/* @var $relation_model Blackbox_Models_TargetRelation */
			$relation_model = $this->getModelFactory()->getModel('TargetRelation');
			foreach ($relation_model->loadAllBy(array('target_id' => $campaign_ids)) as $relation)
			{
				// even if this gets collections, too, it's not that big a deal.
				$target_ids[] = $relation->child_id;
			}
		}
		
		return $target_ids;
	}
	
	/**
	 * Returns all Blackbox_Models_TargetRelation which are children of a target.
	 *
	 * @pre $target_model is a collection
	 * @param DB_Models_DatabaseModel_1 $target_model The parent collection.
	 *  This can be either a Blackbox_Models_Target or alternatively a
	 *  Blackbox_Models_View_TargetCollectionChild object.
	 * @return ArrayObject List of Blackbox_Models_View_TargetCollectionChild objects.
	 */
	protected function getTargetCollectionChildren(Blackbox_Models_IReadableTarget $target_model)
	{
		$child_model = new Blackbox_Models_View_TargetCollectionChild(
			$this->getModelFactory()->getDbInstance()
		);
		
		$children = new ArrayObject();
		
		$child_models = $child_model->getCollectionTargets(
			$target_model->target_id, 
			1,												// <- active targets only
			$this->getFactoryConfig()->getForceWinners()	// <- with the exception of these. (gf#19929)
		);
		
		foreach ($child_models as $relation)
		{
			$children->append(clone $relation);
		}
		
		return $children;
	}
	
	/**
	 * Load up and return the tags for a target using a target model.
	 *
	 * @param Blackbox_Models_Target $target_model Model of the target to use to find tags.
	 * @return array List of tags (possibly empty).
	 */
	protected function getTargetTags(Blackbox_Models_IReadableTarget $target_model)
	{
		$tags = array();
		
		/* @var $map_model Blackbox_Models_TargetTagMap */
		$map_model = $this->getModelFactory()->getModel('TargetTagMap');
		
		/* @var $iterable DB_Models_IterativeModel_1 */
		$iterable = $map_model->loadAllBy(
			array('target_id' => $target_model->target_id)
		);
		
		/* @var $tag_model Blackbox_Models_TargetTag */
		$tag_model = $this->getModelFactory()->getModel('TargetTag');

		foreach ($iterable as $map)
		{
			$tag_model->loadBy(array('target_tag_id' => $map->tag_id));
			$tags[] = $tag_model->tag;
		}
		
		return $tags;
	}
	
	/**
	 * Get the class that is needed for a particular target collection
	 *
	 * @pre $target_model is a collection.
	 * @param DB_Models_DatabaseModel_1 $target_model
	 * @return OLPBlackbox_TargetCollection
	 */
	protected function getCollectionClass(Blackbox_Models_IReadableTarget $target_model)
	{
		// Get the class from the db
		$target_collection_class_ref_table = $this->getModelFactory()->getReferenceTable('TargetCollectionClass');
		$target_collection_class = $target_collection_class_ref_table->toName(
			$target_model->target_collection_class_id
		);
		
		if (class_exists($target_collection_class))
		{
			$tags = $this->getTargetTags($target_model);
	
			// Grabs the state data
			$state_data = $this->getStateData($target_model);
	
			// Create an instance of the class
			// TODO: temporary fix to hit submitlevel1 stats properly, rewrite to use
			// the OLPBlackbox_Enterprise_Generic/CLK_Factory_TargetCollection factory
			if ($target_collection_class == 'OLPBlackbox_Enterprise_TargetCollection')
			{
					$target_collection = new OLPBlackbox_Enterprise_TargetCollection(
						$target_model->property_short,
						$state_data,
						TRUE,
						$tags
					);
			}
			else
			{
				$target_collection = new $target_collection_class(
					$target_model->property_short,
					$state_data,
					$tags
				);
			}
			
			$target_collection->setPicker($this->getPicker($target_model));
		}
		else
		{
			$this->logError(
				sprintf(
					"Target collection class %s does not exist for %s",
					$target_collection_class,
					$target_model->property_short
				),
				$target_model->property_short
			);
			$target_collection = FALSE;
		}
		
		$this->subscribeCollectionToEvents($target_collection);
		
		return $target_collection;
	}
	
	/**
	 * Hooks up the target collection to the event bus for the events it needs
	 * to handle.
	 * 
	 * @param OLP_ISubscriber $collection The collection to attach events to.
	 * @return void
	 */
	protected function subscribeCollectionToEvents(OLP_ISubscriber $collection)
	{
		if (!$this->getConfig()->event_bus instanceof OLP_IEventBus) return;
		
		$this->getConfig()->event_bus->subscribeTo(
			OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT, $collection
		);
		$this->getConfig()->event_bus->subscribeTo(
			OLPBlackbox_Event::EVENT_GLOBAL_MILITARY_FAILURE, $collection
		);
	}

	/**
	 * Returns the state data for the target collection being assembled.
	 * @param Blackbox_Models_IReadableTarget $target_model
	 * @return NULL|OLPBlackbox_TargetCollectionStateData
	 */
	protected function getStateData(Blackbox_Models_IReadableTarget $target_model)
	{
		$state_data = NULL;
		$tier_number = NULL;
		
		if ($this->getFactoryConfig()->getTierNumber($target_model->property_short))
		{
			$tier_number = $this->getFactoryConfig()->getTierNumber($target_model->property_short);
		}
		elseif ($this->getFactoryConfig()->isPreferredTier($target_model))
		{
			$tier_number = 1;
		}

		if ($tier_number !== NULL)
		{
			$state_data = new OLPBlackbox_TargetCollectionStateData(
				array('tier_number' => $tier_number)
			);
		}

		return $state_data;
	}

	/**
	 * Get the picker for the targetCollection
	 *
	 * @param Blackbox_Models_IReadableTarget $target_model
	 * @return OLPBlackbox_Factory_Picker
	 */
	protected function getPicker(Blackbox_Models_IReadableTarget $target_model)
	{
		// Get the the weight class to set the picker
		$weight_class_ref_table = $this->getModelFactory()->getReferenceTable('WeightClass');
		
		return $this->getPickerFactory()->getPicker($weight_class_ref_table->toName($target_model->weight_class_id));
	}
	
	/**
	 * Determines if a target is a COLLECTION type.
	 *
	 * @param Blackbox_Models_IReadableTarget $target Target to check for type.
	 * @return bool TRUE if COLLECTION, FALSE otherwise.
	 */
	protected function targetIsCollection(Blackbox_Models_IReadableTarget $target)
	{
		if ($target instanceof Blackbox_Models_View_TargetCollectionChild)
		{
			return strcasecmp($target->class_name, 'COLLECTION') == 0;
		}
		elseif ($target instanceof Blackbox_Models_Target)
		{
			$bbx_type_ref = $this->getModelFactory()->getReferenceTable('BlackboxType', TRUE);
			
			return strcasecmp($bbx_type_ref->toName($target->blackbox_type_id), 'COLLECTION') == 0;
		}
		else
		{
			throw new Blackbox_Exception(
				'unable to determine if target is collection, unknown class'
			);
		}
	}

	/**
	 * Get the event name for the error event log entry
	 *
	 * @return string
	 */
	protected function getErrorEvent()
	{
		return 'COLLECTION_FACTORY';
	}
}
?>
