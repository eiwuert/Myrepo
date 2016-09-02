<?php

/**
 * Configuration logic controller for OLPBlackbox factories, particularly
 * targets and targetcollections.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_Config extends OLPBlackbox_Factory_ModelFactory
{
	/**
	 * Cache of restricted targets.
	 * @var array
	 */
	protected $restricted_targets = array();
	
	/**
	 * Cache of excluded targets.
	 * @var array
	 */
	protected $excluded_targets = array();
	/**
	 * Cache of property shorts for various target collection children.
	 * @var array
	 */
	protected $collection_children_shorts = array();

	/**
	 * Returns the site config.
	 * @return OLPBlackbox_Config
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}

	/**
	 * Determines whether the collection is valid to be built.
	 * @param Blackbox_Models_IReadableTarget $model The collection's property short.
	 * @return bool TRUE if the collection should be built.
	 */
	public function isAllowedCollection(Blackbox_Models_IReadableTarget $model)
	{
		if ($this->rejectTier($model))
		{
			return FALSE;
		}

		if ($this->isPreferredTier($model) && $this->superTierNotAllowed())
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Determines if we can add the super tier.
	 * @return bool TRUE disallow, FALSE allow.
	 */
	protected function superTierNotAllowed()
	{
		if ($this->getConfig()->disable_preferred_tier) return TRUE;

		if (($this->useTierArray() && !in_array(1, $this->useTierArray()))
			|| ($this->excludeTierArray() && in_array(1, $this->useTierArray())))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Determines if the target (model) is the preferred tier.
	 * @return bool
	 */
	public function isPreferredTier(Blackbox_Models_IReadableTarget $model)
	{
		// TODO: make sure "super tier 0" is created and the property short is
		// reflected correctly here.
		// TODO: What if st0.propa is rejected for post? it will be rejected
		// again when it hits in t1!
		// what happens when you ask for the parent here in config?
		// return strcasecmp($model->property_short, 'st0') === 0;
		return in_array(strtolower($model->property_short), array('st0', 'grv_gc_new'));
	}

	/**
	 * Determines if the property_short is a property.
	 *
	 * This is really primitive, hard coded logic so that we can tell if
	 * something is a TargetCollection which is not itself a company.
	 * For the most part this means all targets and 'CLK'
	 *
	 * @param Blackbox_Models_IReadableTarget $target Model to check.
	 * @return bool FALSE if not a company.
	 */
	protected function isCompany(Blackbox_Models_IReadableTarget $target)
	{
		return (bool) $target->company_id;
	}

	/**
	 * Parses a tier number from a property short.
	 * @param string $property_short The property short to parse
	 * @return int tier number
	 */
	public function getTierNumber($property_short)
	{
		$matches = array();

		if (preg_match('/^T([0-9]).*$/i', $property_short, $matches))
		{
			return intval($matches[1]);
		}

		return NULL;
	}

	/**
	 * Indicates property short is a Tier collection.
	 * @return bool
	 */
	protected function rejectTier(Blackbox_Models_IReadableTarget $model)
	{
		$tier_number = $this->getTierNumber($model->property_short);

		if ($tier_number === NULL)
		{
			return FALSE;
		}

		if (($this->useTierArray() && !in_array($tier_number, $this->useTierArray()))
			|| ($this->excludeTierArray() && in_array($tier_number, $this->excludeTierArray())))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns the USE_TIER entry from the OLPBlackbox_DebugConf.
	 * @return NULL|array
	 */
	protected function useTierArray()
	{
		return $this->getConfig()->debug->getFlag(OLPBlackbox_DebugConf::USE_TIER);
	}

	/**
	 * Returns the EXCLUDE_TIER entry from OLPBlackbox_DebugConf.
	 * @return NULL|array
	 */
	protected function excludeTierArray()
	{
		return $this->getConfig()->debug->getFlag(OLPBlackbox_DebugConf::EXCLUDE_TIER);
	}

	/**
	 * Returns the property short of children allowed to be added to a collection.
	 *
	 * @param ArrayObject $children List of Blackbox_Models_IReadableTarget
	 * which have been identified as children for the collection.
	 * @return ArrayObject list of Blackbox_Models_IReadableTarget to turn into
	 * actual OLPBlackbox targets.
	 */
	public function getAllowedChildren(ArrayObject $children)
	{
		$force_winners = $this->findBBForceWinner($children);
		if ($force_winners instanceof ArrayObject)
		{
			$children = $force_winners;
		}

		$this->restrictTargets($children);
		$this->excludeTargets($children);
		
		return $children;
	}

	/**
	 * Use the OLPBlackbox_DebugConf::TARGETS_RESTRICT info to prune child targets.
	 *
	 * This is public because it's used for preferred target stuff by the main
	 * blackbox factory. Otherwise it should really not be needed other than
	 * internally.
	 *
	 * @see OLPBlackbox_Factory_OLPBlackbox::getBlackbox()
	 * @param ArrayObject $targets List of Blackbox_Models_IReadableTarget
	 */
	public function restrictTargets(ArrayObject $targets)
	{
		if (!$this->restrictedTargets())
		{
			return $targets;
		}
		
		// for loop doesn't reset if we call unset, unlike foreach
		$target_count = $targets->count();

		// case insensitivity checking makes this a little inefficient
		for ($i = 0; $i < $target_count; ++$i)
		{
			// eg. current target is 'clk' and 'pcl' is in restricted targets.
			$children_found = array_intersect(
				$this->collectionChildrenShorts($targets[$i]),
				$this->restrictedTargets()
			);

			$allowed = (!$this->isCompany($targets[$i])
				|| in_array(strtolower($targets[$i]->property_short), $this->restrictedTargets())
				|| $children_found);

			if (!$allowed)
			{
				unset($targets[$i]);
			}
		}

		return $targets;
	}

	/**
	 * Removes targets if the config says they are to be excluded.
	 *
	 * This is public because it's used for preferred target stuff by the main
	 * blackbox factory. Otherwise it should really not be needed other than
	 * internally.
	 *
	 * @see OLPBlackbox_Factory_Blackbox::getBlackbox()
	 * @param ArrayObject $children List of target models for the children of a
	 * collection.
	 * @return ArrayObject List of models which are not excluded.
	 */
	public function excludeTargets(ArrayObject $targets)
	{
		if (!$this->excludedTargets())
		{
			return $targets;
		}

		// case insensitivity checking makes this a bit inefficient
		foreach ($targets as $key => $target)
		{
			if (in_array(strtolower($target->property_short), $this->excludedTargets()))
			{
				unset($targets[$key]);
			}
		}
		
		$targets = new ArrayObject(
			array_filter($targets->getArrayCopy())
		);

		return $targets;
	}

	/**
	 * Normalizes and returns the excluded targets in the config.
	 * @return NULL|array
	 */
	protected function excludedTargets()
	{
		if (!$this->excluded_targets && $this->getDebugFlag(OLPBlackbox_DebugConf::TARGETS_EXCLUDE))
		{
			$this->excluded_targets = array_map('strtolower',
				$this->getConfig()->debug->getFlag(
				OLPBlackbox_DebugConf::TARGETS_EXCLUDE)
			);
			$this->excluded_targets = array_map('trim', $this->excluded_targets);
		}

		return $this->excluded_targets;
	}
	
	/**
	 * Retrieves a debug flag from OLPBlackbox_Config.
	 * @see OLPBlackbox_Config::getFlag()
	 * @param string $flag Flag from OLPBlackbox_Config flag constants.
	 * @return mixed NULL if not set, TRUE/FALSE/Array otherwise.
	 */
	protected function getDebugFlag($flag)
	{
		if (!$this->getConfig()->debug instanceof OLPBlackbox_DebugConf)
		{
			return NULL;
		}
		
		return $this->getConfig()->debug->getFlag($flag);
	}

	/**
	 * @return NULL|array List of property shorts which are the restricted targets.
	 */
	public function restrictedTargets()
	{
		if (!$this->restricted_targets && $this->getDebugFlag(OLPBlackbox_DebugConf::TARGETS_RESTRICT))
		{
			$this->restricted_targets = array_map('strtolower',
				$this->getConfig()->debug->getFlag(
					OLPBlackbox_DebugConf::TARGETS_RESTRICT)
			);
			$this->restricted_targets = array_map(
				'trim', $this->restricted_targets
			);
		}

		return $this->restricted_targets;
	}
	
	/**
	 * Clear the "cache" of restricted targets (for unit tests.)
	 * @see OLPBlackbox_Factory_OLPBlackboxTest
	 * @return void
	 */
	public function clearRestrictAndExcludeCache()
	{
		$this->restricted_targets = array();
		$this->excluded_targets = array();
	}

	/**
	 * Returns a normalized copy of force winner.
	 * @return array List of force winners.
	 */
	public function getForceWinners()
	{
		// online confirm mode has resolved aliases which may not make sense with 
		// bb_force_winner (which may have aliases) so we ignore it unless we're in broker
		if ($this->getConfig()->force_winner
			&& $this->getConfig()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			if (!is_array($this->getConfig()->force_winner))
			{
				$force_winners = explode(',', $this->getConfig()->force_winner);
			}
			else
			{
				$force_winners = $this->getConfig()->force_winner;
			}

			$force_winners = array_map('trim', $force_winners);
			$force_winners = array_map('strtolower', $force_winners);
		}
		return $force_winners;
	}

	/**
	 * @param ArrayObject $children List of Blackbox_Models_IReadableTargets to 
	 * check to see if they are listed as bb_force_winner
	 * @return ArrayObject|NULL If children should be assembled based on 
	 * force winner, an ArrayObject of Blackbox_Models_IReadableTargets will be
	 * returned.
	 */
	protected function findBBForceWinner(ArrayObject $children)
	{
		$force_winners = $this->getForceWinners();
		
		if ($force_winners)
		{
			// this is to match something like 'clk' if the force winner is 'pcl'
			foreach ($force_winners as $force_winner)
			{
				$parents = array_map('strtolower',
					$this->getCollectionParentShorts($force_winner)
				);
				if ($parents)
				{
					$force_winners = array_unique(array_merge(
						$force_winners, $parents
					));
				}
			}
			
			$all_found = new ArrayObject();

			// look for bb_force_winners in these children and restrict
			foreach ($children as $child)
			{
				if (in_array(strtolower($child->property_short), $force_winners))
				{
					$all_found[] = $child;
				}
			}

			if ($all_found->count())
			{
				return $all_found;
			}
		}

		return NULL;
	}

	/**
	 * Determines if the property short presented will be nested inside a parent
	 * collection of the same company (This is pretty much CLK and maybe HMS as
	 * of this writing) [DO]
	 *
	 * @param string $property_short The target property short.
	 * @return array List of parent targets.
	 */
	protected function getCollectionParentShorts($property_short)
	{
		$parent_companies = array(
			EnterpriseData::COMPANY_CLK,
			EnterpriseData::COMPANY_HMS,
		);

		if (!in_array(EnterpriseData::getCompany($property_short), $parent_companies))
		{
			return array();
		}

		$target_model = $this->getModelFactory()->getModel('Target');
		$target_relation_model = $this->getModelFactory()->getModel(
			'TargetRelation'
		);
		
		$target_loaded = $target_model->loadBy(array(
			'property_short' => $property_short,
			'blackbox_type_id' => $this->getBlackboxType('CAMPAIGN'),
		));
		
		$collection_type = $this->getBlackboxType('COLLECTION');
		
		// return value, a list of property shorts
		$parents = array();
		
		while ($target_loaded)
		{
			// this means we've hit something like T1, not interested
			if (!$target_model->company_id) break;
			
			if ($target_model->blackbox_type_id == $collection_type)
			{
				// found one! collections are not wrapped in campaigns in the db
				$parents[] = $target_model->property_short;
			}

			// load up the next parent
			$relation_loaded = $target_relation_model->loadBy(
				array('child_id' => $target_model->target_id)
			);
			
			if (!$relation_loaded)
			{
				break;	// throw exception? I'm not 100% sure.
			}
			
			$target_loaded = $target_model->loadBy(
				array('target_id' => $target_relation_model->target_id)
			);
		}
		
		return $parents;
	}

	/**
	 * Determines the children for a property_short.
	 *
	 * @param string $property_short The property_short for a target.
	 * @return array The children for this property short. (lowercase)
	 */
	protected function collectionChildrenShorts(Blackbox_Models_IReadableTarget $target_model)
	{
		if ($target_model->blackbox_type_id != $this->getBlackboxType('COLLECTION')
			|| !$this->isCompany($target_model))
		{
			return array();
		}
		
		if ($this->getCachedCollectionChildrenShorts($target_model->property_short))
		{
			return $this->getCachedCollectionChildrenShorts($target_model->property_short);
		}
		
		$children = $this->getCollectionChildrenShorts($target_model);

		return $children;
	}
	
	/**
	 * Caches a list of property shorts that are children for a collection.
	 *
	 * @param string $collection_short The property short of the collection to
	 * store this list for.
	 * @param array $children_shorts The list of property shorts of children
	 * for the collection.
	 * @return void
	 */
	protected function cacheCollectionChildrenShorts($collection_short, array $children_shorts)
	{
		$this->collection_children_shorts[$collection_short] = $children_shorts;
	}
	
	/**
	 * Returns a cached list of property shorts for a target collection by the
	 * target collection's own property short.
	 * @param string $collection_short The property short for the collection.
	 * @return array|NULL List of property shorts or NULL if there is no cached
	 * list.
	 */
	protected function getCachedCollectionChildrenShorts($collection_short)
	{
		return isset($this->collection_children_shorts[$collection_short])
			? $this->collection_children_shorts[$collection_short]
			: NULL;
	}

	/**
	 * DO NOT call this function directly, call {@see collectionChildren}.
	 * @pre The property short must be a collection and must be a company.
	 * @param Blackbox_Models_IReadableTarget $target_model The model to pull
	 * the children for.
	 * @return array list of children property shorts.
	 */
	private function getCollectionChildrenShorts(Blackbox_Models_IReadableTarget $target_model)
	{
		/* @var $target_child_model Blackbox_Models_View_TargetCollectionChild */
		$target_child_model = $this->getModelFactory()->getViewModel(
			'TargetCollectionChild'
		);

		$children = $target_child_model->getCollectionTargets(
			$target_model->target_id
		);

		$property_shorts = array();
	
		foreach ($children as $child)
		{
			$property_shorts[] = $child->property_short;
			
			if ($child->blackbox_type_id == $this->getBlackboxType('COLLECTION')
				&& $this->isCompany($child))
			{
				$property_shorts = array_unique(array_merge(
					$property_shorts, $this->getCollectionChildrenShorts($child))
				);
			}
		}
		
		$property_shorts = array_map('strtolower', $property_shorts);
		
		$this->cacheCollectionChildrenShorts(
			$target_model->property_short, $property_shorts
		);
		
		return $property_shorts;
	}
}

?>
