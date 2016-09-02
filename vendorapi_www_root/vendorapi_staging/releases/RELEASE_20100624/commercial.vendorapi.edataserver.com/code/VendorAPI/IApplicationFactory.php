<?php

/**
 * Creates and loads IApplication instances for the appropriate implementation
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface VendorAPI_IApplicationFactory
{
	/**
	 * Create a new state object starting at the current database version
	 *
	 * @param VendorAPI_CallContext $context
	 * @param int $application_id
	 * @return VendorAPI_StateObject
	 */
	public function createStateObject(VendorAPI_CallContext $context, $application_id = NULL);

	/**
	 * Loads an application from the database and state object
	 *
	 * This method should load whatever data is available in the database for
	 * the given application ID, and combine it with newer information from
	 * the state object (if any). This MUST be able to handle the case where
	 * no state object exists.
	 *
	 * If the given application ID cannot be found, this must throw a
	 * VendorAPI_ApplicationNotFoundException.
	 *
	 * @param int $application_id
	 * @param VendorAPI_IModelPersistor $state
	 * @return VendorAPI_IApplication
	 * @throws VendorAPI_ApplicationNotFoundException
	 */
	public function getApplication($application_id, VendorAPI_IModelPersistor $persistor, VendorAPI_StateObject $state);

	/**
	 * Loads an application from the database and state object
	 *
	 * This method should load whatever data is available in the database for
	 * the given SSN and DOB, and combine it with newer information from
	 * the state object (if any). This MUST be able to handle the case where
	 * no state object exists.
	 *
	 * If the given application ID cannot be found, this must throw a
	 * VendorAPI_ApplicationNotFoundException.
	 *
	 * @param int $ssn
	 * @param date $dob
	 * @param VendorAPI_IModelPersistor $state
	 * @return VendorAPI_IApplication
	 * @throws VendorAPI_ApplicationNotFoundException
	 */
	public function getApplicationBySSNDOB($ssn, $dob, VendorAPI_IModelPersistor $persistor, VendorAPI_StateObject $state);

	/**
	 * Create a new application and save it to the given model persistor.
	 *
	 * @param VendorAPI_IModelPersistor $persistor
	 * @param VendorAPI_StateObject $state
	 * @param VendorAPI_CallContext $context
	 * @param array $data
	 * @return VendorAPI_IApplication
	 */
	public function createApplication(
		VendorAPI_IModelPersistor $persistor,
		VendorAPI_StateObject $state,
		VendorAPI_CallContext $context,
		array $data
	);


}

?>
