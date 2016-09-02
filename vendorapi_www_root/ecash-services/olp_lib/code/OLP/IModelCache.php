<?php

interface OLP_IModelCache
{
	/**
	 * Store an OLP_IModel in whatever way the implementation uses (array,memcache,db,etc.)
	 * @throws OLP_StorageException
	 * @param OLP_IModel $model
	 * @return void 
	 */
	public function store(OLP_IModel $model);
	
	/**
	 * Remove an OLP_IModel from the cache.
	 * @param OLP_IModel $model
	 * @return void
	 */
	public function remove(OLP_IModel $model);
	
	/**
	 * Find all models in the cache matching the search filters given.
	 * @param array $filters
	 * @return array List of OLP_IModel objects.
	 */
	public function find(array $filters = array());
}

?>