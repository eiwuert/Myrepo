<?php

/**
 * Implements a model cache by storing the models in an array ("in memory.")
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class Cache_Model_InMemory implements OLP_IModelCache
{
	/**
	 * The cache of objects to retain.
	 *
	 * @var array
	 */
	protected $cache = array();
	
	/**
	 * 
	 * @param array $filters 
	 * @return array List of OLP_IModel objects. 
	 * @see OLP_IModelCache::find()
	 */
	public function find(array $filters = array())
	{
		$return = array();
		
		foreach ($this->cache as $object)
		{
			if ($this->discoversObjectMatchesFilters($object, $filters))
			{
				$return[] = $object;
			}
		}
		
		return $return;
	}
	
	/**
	 * Set up the cache of OLP_IModel objects to use.
	 *
	 * @param array|Traversable $cache A list of OLP_ICache models.
	 * @return void
	 */
	public function setCache($cache)
	{
		if (!is_array($cache) && !$cache instanceof Traversable)
		{
			throw new InvalidArgumentException(
				'cache parameter must be traversable'
			);
		}
		
		$this->cache = array();
		
		foreach ($cache as $object)
		{
			if ($object instanceof OLP_IModel)
			{
				$this->cache[] = $object;
			}
		}
	}
	
	/**
	 * Compare an object's properties with an associative array of items to see 
	 * if all items match.
	 *
	 * @param OLP_IModel $object The object to compare to the filters.
	 * @param array $filters Associative array list of properties to use to 
	 * examine the object.
	 * @return bool TRUE if the properties of the object match, by key and value,
	 * all the filters passed in.
	 */
	protected function discoversObjectMatchesFilters(OLP_IModel $object, array $filters = array())
	{
		if (!$filters) return TRUE;
		
		foreach ($filters as $key => $value)
		{
			if (!isset($object->$key)) return FALSE;

			if (is_array($value)) 
			{
				if (!in_array($object->$key, $value)) return FALSE;
			}
			else
			{
				if ($object->$key != $value) return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * @todo Once Models or IModels have a way to be compared, implement this.
	 * @throws OLP_NotImplementedException
	 * @param OLP_IModel $model 
	 * @return void 
	 * @see OLP_IModelCache::remove()
	 */
	public function remove(OLP_IModel $model)
	{
		throw new OLP_NotImplementedException(
			"until there is a uniform way to compare models, this is not implemented."
		);
	}
	
	/**
	 * @todo Once Models or IModels have a way to be compared, implement this.
	 * @throws OLP_StorageException
	 * @throws OLP_NotImplementedException
	 * @param OLP_IModel $model 
	 * @return void 
	 * @see OLP_IModelCache::store()
	 */
	public function store(OLP_IModel $model)
	{
		throw new OLP_NotImplementedException(
			"until there is a uniform way to compare models, this is not implemented."
		);
	}
}

?>
