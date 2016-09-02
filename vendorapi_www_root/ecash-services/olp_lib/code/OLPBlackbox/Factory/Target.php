<?php
/**
 * Factory for creating OLP Blackbox targets.
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class OLPBlackbox_Factory_Target extends OLPBlackbox_Factory_ModelFactory
{
	/**
	 * Get the cache key for a target by it's property short.
	 * @param string $property_short
	 * @return string
	 */
	public function getTargetCacheKeyByPropertyShort($property_short)
	{
		return $this->getDebugConfCacheKey() . '/target/' . $property_short;
	}

	/**
	 * Get the cache key for the current target begin assembled. (relevant field is property_short)
	 * @param Blackbox_Models_View_TargetCollectionChild $target_model
	 * @return string
	 */
	protected function getTargetCacheKey(Blackbox_Models_View_TargetCollectionChild $target_model)
	{
		return $this->getTargetCacheKeyByPropertyShort($target_model->property_short);
	}

	/**
	 * Get the cache key for the current target begin assembled. (relevant field is property_short)
	 * @param Blackbox_Models_View_TargetCollectionChild $target_model
	 * @return NULL|Blackbox_ITarget
	 */
	protected function getCachedTarget(Blackbox_Models_View_TargetCollectionChild $target_model)
	{
		return $this->getConfig()->memcache->get($this->getTargetCacheKey($target_model));
	}

	/**
	 * Get the cache key for the current target begin assembled. (relevant field is property_short)
	 * @param Blackbox_Models_View_TargetCollectionChild $target_model
	 * @param Blackbox_ITarget $target
	 * @return void
	 */
	protected function cacheTarget(Blackbox_Models_View_TargetCollectionChild $target_model, Blackbox_ITarget $target)
	{
		$this->getConfig()->memcache->set($this->getTargetCacheKey($target_model), $target);
	}

	/**
	 * Gets an instance of target factories
	 *
	 * @param Blackbox_Models_Target $target_model
	 * @param OLPBlackbox_Factory_TargetStateData $state_data_factory The factory
	 * used to get state data out of the database and/or target model.
	 * @return OLPBlackbox_ITarget
	 */
	public function getTarget(
		Blackbox_Models_View_TargetCollectionChild $target_model,
		OLPBlackbox_Factory_TargetStateData $state_data_factory
	)
	{
		if (EnterpriseData::isEnterprise($target_model->property_short))
		{
			$target = $this->getEnterpriseFactory($target_model->property_short)
				->getTarget($target_model, $state_data_factory);
		}
		else
		{
			$cached_target = $this->getCachedTarget($target_model);
			
			if ($cached_target instanceof Blackbox_ITarget)
			{
				$target = $cached_target;
			}
			else 
			{
				$target = $this->getGenericTarget($target_model, $state_data_factory);
				
				$this->cacheTarget($target_model, $target);
			}
		}
		
		return $target;
	}
	
	/**
	 * Gets an enterprise Factory by property short.
	 *
	 * @param string $property_short The property short for the target we'd like
	 * to assemble.
	 * @return object Enterprise target factory of some sort.
	 */
	protected function getEnterpriseFactory($property_short)
	{
		$class_name = $this->getEnterpriseFactoryClassName(
			'Target', EnterpriseData::getCompany($property_short)
		);
		return new $class_name();
	}
	
	/**
	 * Returns the name of an enterprise factory class based on $class and $company
	 *
	 * @param string $class
	 * @param string $company
	 * @return string
	 */
	public function getEnterpriseFactoryClassName($class, $company)
	{
		// These companies were created with lowercase letters before we mandated that all new company directories
		// need to be all uppercase letters.
		$company_map = array(
			'QEASY' => 'Qeasy',
			'IMPACT' => 'Impact',
			'AGEAN' => 'Agean'
		);
		
		if (array_key_exists($company, $company_map))
		{
			$company = $company_map[$company];
		}
		
		$class_name = "OLPBlackbox_Enterprise_{$company}_Factory_{$class}";
		
		if (class_exists($class_name, TRUE))
		{
			return $class_name;
		}
		
		return "OLPBlackbox_Enterprise_Generic_Factory_{$class}";
	}

	/**
	 * Gets a generic target
	 *
	 * @param Blackbox_Models_Target $target_model
	 * @param OLPBlackbox_Factory_TargetStateData $state_data_factory The factory
	 * used to make a state data object
	 * @return OLPBlackbox_Target
	 */
	protected function getGenericTarget(
		Blackbox_Models_View_TargetCollectionChild $target_model,
		OLPBlackbox_Factory_TargetStateData $state_data_factory
	)
	{
		// Create the generic target.
		$target = new OLPBlackbox_Target(
			$target_model->property_short,
			$target_model->target_id,
			$state_data_factory->getTargetStateData($target_model),
			$this->getTargetTags($target_model)
		);
		$this->getListenerHandler()->registerChild($target_model->property_short, 'TARGET', $target);
		// Set the rule collection for this target.
		$this->getRuleCollectionFactory($target_model->property_short)
			->setRuleCollections($target_model, $target);
			
		if (!$target->getRules() instanceof Blackbox_IRule)
		{
			throw new Exception($target_model->property_short . "\n");
		}
				
		return $target;
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
}
?>
