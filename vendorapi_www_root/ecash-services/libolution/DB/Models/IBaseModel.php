<?php

/**
 * A base interface for model use allowing for loading, saving and deleting
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
interface DB_Models_IBaseModel
{
	/**
	 * Load a model using an array of search parameters.
	 *
	 * @param array $where_args
	 * @return bool Whether the correct model was loaded into this object.
	 */
	public function loadBy(array $where_args);
	
	/**
	 * Find a list of models matching the provided search parameters.
	 *
	 * @param array $where_args
	 * @return array List of models.
	 */
	public function loadAllBy(array $where_args = array());
	
	/**
	 * Save the model using whatever data store the implementing class uses.
	 * 
	 * (Most likely, this means saving to a database.)
	 * 
	 * @return int > 0 if it the save was successful, 0 otherwise.
	 */
	public function save();
	
	/**
	 * Delete the model from whatever data store the implementing class uses.
	 * 
	 * (Most likely, the data store is a database.)
	 * 
	 * @return int > 0 if it the delete was successful, 0 otherwise.
	 */
	public function delete();
}

?>