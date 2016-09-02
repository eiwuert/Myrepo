<?php

/**
 * External management of model persistence
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface VendorAPI_IModelPersistor
{
	/**
	 * @param DB_Models_IWritableModel_1 $model
	 * @return bool
	 */
	public function save(DB_Models_IWritableModel_1 $model);

	/**
	 * Loads data into a model of the given type and returns it
	 *
	 * Returns FALSE if a matching model cannot be loaded.
	 * NOTE: The model returned may not be the same instance passed in.
	 *
	 * @param DB_Models_IWritableModel_1 $model
	 * @param array $where
	 * @return DB_Models_IWritableModel_1|bool
	 */
	public function loadBy(DB_Models_IWritableModel_1 $model, array $where);
	
	/**
	 * Set the version of the object we're persisting
	 * @param Integer $version
	 * @return void
	 */
	public function setVersion($version);
	
	/**
	 * Load all instances of a model
	 * @param DB_Models_IWritableModel_1 $model
	 * @param array $where
	 * @return array
	 */
	public function loadAllBy(DB_Models_IWritableModel_1 $model, array $where, $check_db = TRUE);

}

?>