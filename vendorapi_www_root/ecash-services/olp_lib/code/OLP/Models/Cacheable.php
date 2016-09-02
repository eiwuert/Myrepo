<?php

/**
 * An OLP_IModel which pulls/stores in a cache strategy object 
 * instead of the database.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLP_Models_Cacheable implements OLP_IModel
{
	/**
	 * The model we'll use to load and wrap
	 *
	 * @var unknown_type
	 */
	protected $model;
	
	/**
	 * Caching object to use.
	 *
	 * @var OLP_IModelCache
	 */
	protected $cache;
	
	/**
	 * Create a "Model" that will use a cache object to find/store information.
	 * @param OLP_IModel $model The model to use for set/get operations.
	 * @param OLP_IModelCache $cache The cache of models to use for load() and
	 * loadAllBy() methods.
	 * @return void
	 */
	function __construct(OLP_IModel $model, OLP_IModelCache $cache)
	{
		$this->model = $model;
		$this->cache = $cache;
	}
	
	/**
	 * @return int > 0 if it the delete was successful, 0 otherwise. 
	 * @see OLP_IModel::delete()
	 */
	public function delete()
	{
		if ($this->model instanceof OLP_IModel)
		{
			$this->cache->remove($this->model);
			unset($this->model);
		}
	}
	
	/**
	 * @param array $where_args 
	 * @return array List of models. 
	 * @see OLP_IModel::loadAllBy()
	 */
	public function loadAllBy(array $where_args = array())
	{
		$models = array();
		foreach ($this->cache->find($where_args) as $object) 
		{
			if (!$object instanceof OLP_IModel) continue;
			
			$models[] = $object;
		}
		return $models;
	}
	
	/**
	 * @param array $where_args 
	 * @return bool Whether the correct model was loaded into this object. 
	 * @see OLP_IModel::loadBy()
	 */
	public function loadBy(array $where_args)
	{
		$results = $this->cache->find($where_args);
		
		$return = FALSE;
		
		if (count($results) > 0)
		{
			$this->model = $results[0];
			$return = TRUE;
		}

		return $return;
	}
	
	/**
	 * @return int > 0 if it the save was successful, 0 otherwise. 
	 * @see OLP_IModel::save()
	 */
	public function save()
	{
		$return = 0;
		if ($this->model instanceof OLP_IModel)
		{
			if ($this->cache->store($this->model))
			{
				$return = 1;
			}
		}
		
		return $return;
	}

	/**
	 * Allow callers to get items on loaded models.
	 * 
	 * @throws RuntimeException
	 * @param string $name The name of the property to get from
	 * the model.
	 * @return mixed
	 */
	public function __get($name)
	{
		if ($this->model instanceof OLP_IModel)
		{
			return $this->model->$name;
		}

		throw new RuntimeException(
			'attempt to access unloaded model property'
		);
	}

	/**
	 * Allows properties to be set on the loaded model.
	 * 
	 * @param string $name the property to set
	 * @param mixed $value the Value to set the property to.
	 * @return void
	 */
	public function __set($name, $value)
	{
		if ($this->model instanceof OLP_IModel)
		{
			$this->model->$name = $value;
			return;
		}

		throw new RuntimeException(
			'attempt to set property on unloaded model'
		);
	}

	/**
	 * Check whether a property is set on a model.
	 *
	 * This is possibly stupid to throw an exception,
	 * but I'll chalk the stupidity up to our loadBy()
	 * ideas.
	 *
	 * @throws RuntimeException
	 * @param string $name The property to set.
	 * @return bool
	 */
	public function __isset($name)
	{
		if ($this->model instanceof OLP_IModel)
		{
			return isset($this->model->$name);
		}

		throw new RuntimeException(
			'attempt to check property for unloaded model.'
		);
	}
}

?>
