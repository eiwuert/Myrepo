<?php
/**
 * Interface for eCash model containers
 * 
 * Model containers are impelemented to allow for multiple data sources
 * to be read and written to via an ecapsulating controller facade.
 * 
 * The colleciton can have one and only one authoritative model which is used to 
 * return values on get attempts while all models are loaded and used for writes.
 * 
 * The observers are used to perform functions based on the current state of the 
 * object and can query the teh object to determine if it has changed via the isChanged
 * public method
 * 
 * The validors will be used to validate the state of the data within the container
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
interface DB_Models_IContainer_1
{
	/**
	 * Add a container observer to the objects observers collection
	 *
	 * @param DB_Models_IContainerObserver_1 $observer
	 * @return void
	 */
	public function addObserver(DB_Models_IContainerObserver_1 $observer);

	/**
	 * Add a validator to the objects validators collection
	 *
	 * @param DB_Models_IContainerValidator_1 $validator
	 * @return void
	 */
	public function addValidator(DB_Models_IContainerValidator_1 $validator);

	/**
	 * Add model to the models non-authoritative collection
	 *
	 * @param DB_Models_IWritableModel_1 $model
	 * @return void
	 */
	public function addNonAuthoritativeModel(DB_Models_IWritableModel_1 $model);

	
	/**
	 * Get the collection of non-authoritative DB_Models_IWritableModel_1 models 
	 * as an array
	 *
	 * @return array
	 */
	public function getNonAuthoritativeModels();

	
	/**
	 * Get all models in the container including the 
	 *
	 * @return array Array of models
	 */
	public function getModels();

	
	/**
	 * Sets the container's authoritative model
	 *
	 * @param DB_Models_IWritableModel_1 $model
	 * @return void
	 */
	public function setAuthoritativeModel(DB_Models_IWritableModel_1 $model);

	/**
	 * Gets the container's authoritative model
	 *
	 * @return DB_Models_IWritableModel_1
	 */
	public function getAuthoritativeModel();

	/**
	 * Has the object changed
	 *
	 * @return bool
	 */
	public function isChanged();

	/**
	 * Get the validation exception stack as an array of 
	 * DB_Models_ContainerValidatorException objects
	 *
	 * @return array
	 */
	public function getValidationExceptionStack();

	/**
	 * Get the last non-authoritative model exception
	 *
	 * @return Exception
	 */
	public function getNonAuthoritativeModelException();
}
?>