<?php

/**
 * Behavior related to applications
 *
 * Implementations should be able to handle the case where no state
 * object exists.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface VendorAPI_IApplication
{
	/**
	 * Returns the blackbox campaign the application was sold to
	 * @return string
	 */
	public function getCampaign();

	/**
	 * Indicates whether the application is a react
	 * @return bool
	 */
	public function isReact();

	/**
	 * Calculates financing information for the application
	 *
	 * This should recalculate the fund date, due date, and APR as
	 * appropriate. If the agent has changed the fund date or due date,
	 * those should not be recalculated unless they are invalid.
	 *
	 * If set is true, this should update the database (whether via the
	 * state object or otherwise).
	 *
	 * @todo In the future, idv_increase_eligible should probably be determined
	 * internally for commercial by examining the bureau inquiry rows.
	 *
	 * @param bool $set When true, updates the database
	 * @param float $loan_amount
	 * @param bool $idv_increase_eligible
	 * @return VendorAPI_QualifyInfo
	 */
	public function calculateQualifyInfo($set = FALSE, $loan_amount = NULL, array $extra = array());

	/**
	 * Saves the esig ip address
	 *
	 */
	public function setESigIPAddress($ip_address);

	/**
	 * Saves qualify info for an application to the application service
	 *
	 * @param Webservices_Client_AppClient $client
	 * @return NULL
	 */
	public function saveApplicationInfoToAppService(Webservices_Client_AppClient $client);

	/**
	 * Marks the application as a react
	 *
	 * This has to be done after the application is created particularly for
	 * calculated reacts, which aren't determined until Blackbox has been run.
	 *
	 * @param int $react_application_id The ID of the _original_ application
	 * @param VendorAPI_CallContext $context Call context
	 * @param int $agent_id Agent performing the react
	 * @return void
	 */
	public function setIsReact($react_application_id, VendorAPI_CallContext $context, $agent_id);

	/**
	 * Adds a new document for synching
	 * @param VendorAPI_DocumentData $document
	 * @param VendorAPI_CallContext $context
	 * @return boolean
	 */
	public function addDocument(VendorAPI_DocumentData $document, VendorAPI_CallContext $context);

	/**
	 * Record the hash from a document preview so that it can be verified later
	 * @param string $content
	 * @param string $template_name
	 * @param VendorAPI_CallContext $context
	 * @return void
	 */
	public function recordDocumentPreview(VEndorAPI_DocumentData $document, VendorAPI_CallContext $context);

	/**
	 * Expires a document hash so it can be ignored in later calls.
	 *
	 * @param VendorAPI_DocumentData $document
	 * @param VendorAPI_CallContext $context
	 */
	public function expireDocumentHash(VendorAPI_DocumentData $document, VendorAPI_CallContext $context);

	/**
	 *
	 * @return ECash_CFE_IContext
	 */
	public function getCfeContext(VendorAPI_CallContext $context);

	/**
	 * Calculate the array of fund amounts for this app
	 * and return them.
	 * @return Array
	 */
	public function getAmountIncrements();

	/**
	 * Expected to return a  path of the status tree returned
	 * @return string
	 */
	public function getApplicationStatus();

	/**
	 * Expected to return a  path of the status tree returned
	 * @return string
	 */
	public function getApplicationStatusId();

	/**
	 * Expected to return the template name of the loan document
	 * @return string
	 */
	public function getLoanDocumentTemplate();

	/**
	 * Expected to return the template name of the what my username email
	 * @return string
	 */
	public function getEmailUsernameTemplate();

	/**
	 * Expected to return the template name of the reset password email link
	 * @return string
	 */
	public function getEmailPasswordLinkTemplate();

	/**
	 * Update the status of this application
	 * @param String $status
	 * @return void
	 */
	public function updateStatus($new_status, $agent_id);

	/**
	 * process any triggers attached to this
	 * thing.
	 * @return void
	 */
	public function handleTriggers();

	/**
	 * Indicate whether loan actions exist for the application
	 * @param integer $agent_id
	 * @return void
	 */
	public function hasLoanActions($agent_id);

	/**
	 * Return an array of application data, formatted as required for rules
	 * @return array
	 */
	public function getData();

	/**
	 * Sets all the app data passsed in
	 * @param array $data
	 */
	public function setApplicationData(array $data);

	/**
	 * Adds a reference to this application
	 * @param VendorAPI_CallContext $context
	 * @param string $name
	 * @param string $phone
	 * @param string $relationship
	 * @return void
	 */
	public function addPersonalReference(VendorAPI_CallContext $context, $name, $phone, $relationship);

	/**
	 * Adds a campaign info record to this application
	 *
	 * @param vendorAPI_Context $context
	 * @param string $license_key
	 * @param string $site
	 * @param string $promo_id
	 * @param string $sub_code
	 * @param string $campaign
	 * @param string $reservation_id
	 * @return void
	 */
	public function addCampaignInfo(VendorAPI_CallContext $context, $license_key, $site, $promo_id, $sub_code, $campaign, $reservation_id = NULL);

	/**
	 * Updates the application id in this application.
	 *
 	 * @param int $application_id
	 * @return void
	 */
	public function updateApplicationId($application_id);

	/**
	 * Saves some or all of the data of an application to the provided persistor
	 * @param VendorAPI_IModelPersistor $persistor
	 * @param Boolean $save_all
	 * @return void
	 */
	public function save(VendorAPI_IModelPersistor $persistor, $save_all);

	/**
	 * Get the StatPro space key for the current Page/Promo/Sub
	 * @param VendorAPI_IDriver $driver
	 * @param VendorAPI_StatProClient $client
	 * @return void
	 */
	public function getSpaceKey(VendorAPI_IDriver $driver, VendorAPI_StatProClient $client);
	
	/**
	 * Get an applications track ID
	 * @return String Track ID 
	 */
	public function getTrackId();

	/**
	 * Returns the models for a column 
	 */
	public function getModelColumns();
}

?>
