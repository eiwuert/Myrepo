<?php

/**
 * API Driver
 *
 * The API driver abstracts different ways of accessing necessary ECash
 * application compononents (ACL, etc.) and allows the API to use a
 * consistent interface. It is instantiated within the loader and is
 * the primary bridge between the actions and the ECash environment.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface VendorAPI_IDriver
{
	/**
	 * Returns an authentication adapter
	 * @return VendorAPI_IAuthenticator
	 */
	public function getAuthenticator();

	/**
	 * Returns a handler for the given action
	 *
	 * @param string $name Action being requested
	 * @return VendorAPI_Actions_Base
	 */
	public function getAction($name);

	/**
	 * Returns the application service object
	 *
	 * @return WebServices_Client_AppClient
	 */
	public function getAppClient();

	/**
	 * @return WebServices_Client_DocumentClient
	 */
	public function getDocumentClient();

	/**
	 * Returns the wsdl for an enterprise soap interface
	 *
	 * @return array
	 */
	public function getEnterpriseSoapWsdl($soap_url);

	/**
	 * Gets a standard database connection to the given company
	 *
	 * If the company is not provided, return a connection for the current company.
	 *
	 * @param string $company
	 * @return DB_IConnection_1
	 */
	public function getDatabase($company = NULL);

	/**
	 * Returns the enterprise in use
	 * @return string
	 */
	public function getEnterprise();

	/**
	 * Returns the company for the driver
	 * @return string
	 */
	public function getCompany();

	/**
	 * Returns the company ID for the current company
	 */
	public function getCompanyID();

	/**
	 * Returns the logging object for the api
	 *
	 * @return Log_ILog_1
	 */
	public function getLog();

	/**
	 * Returns the blackbox factory for the api.
	 *
	 * @param Blackbox_Config $config
	 * @param int $loan_type_id
	 * @return VendorAPI_Blackbox_Factory
	 */
	public function getBlackboxFactory(Blackbox_Config $config, $loan_type_id);

	/**
	 * Returns the blackbox rule factory for the api.
	 *
	 * @param Blackbox_Config $config
	 * @param int $loan_type_id
	 * @return VendorAPI_Blackbox_Factory
	 */
	public function getBlackboxRuleFactory(Blackbox_Config $config, $loan_type_id);

	public function getTribalCall($loan_type_id);
	public function getTribalCallType($loan_type_id);
	public function getTribalRequest($loan_type_id);
	public function getTribalResponse($loan_type_id);
	
	/**
	 * Returns the TSS_DataX_Call object for the api.
	 *
	 * @param int $loan_type_id
	 * @return TSS_DataX_Call
	 */
	public function getDataXCall($loan_type_id);

	/**
	 * Returns the DataX call type.
	 *
	 * @param int $loan_type_id
	 * @return string
	 */
	public function getDataXCallType($loan_type_id);

	/**
	 * Returns a TSS_DataX_IRequest object.
	 *
	 * @param int $loan_type_id
	 * @return TSS_DataX_IRequest
	 */
	public function getDataXRequest($loan_type_id);

	/**
	 * Returns a TSS_DataX_IResponse object.
	 *
	 * @param int $loan_type_id
	 * @return TSS_DataX_IResponse
	 */
	public function getDataXResponse($loan_type_id);

	/**
	 * Returns the FactorTrust_UW_Call object for the api.
	 *
	 * @param int $loan_type_id
	 * @return UW_FactorTrust_Call
	 */
	public function getFactorTrustCall($inquiry, $store, $loan_type_id);

	/**
	 * Returns the FactorTrust_UW call type.
	 *
	 * @param int $loan_type_id
	 * @return string
	 */
	public function getFactorTrustCallType($loan_type_id);

	/**
	 * Returns a FactorTrust_UW_IRequest object.
	 *
	 * @param int $loan_type_id
	 * @return FactorTrust_UW_IRequest
	 */
	public function getFactorTrustRequest($inquiry, $store, $loan_type_id);

	/**
	 * Returns a FactorTrust_UW_IResponse object.
	 *
	 * @param int $loan_type_id
	 * @return FactorTrust_UW_IResponse
	 */
	public function getFactorTrustResponse($loan_type_id);

	/**
	 * Returns the Clarity_UW_Call object for the api.
	 *
	 * @param int $loan_type_id
	 * @return TSS_Clarity_Call
	 */
	public function getClarityCall($inquiry, $store, $loan_type_id);

	/**
	 * Returns the Clarity_UW call type.
	 *
	 * @param int $loan_type_id
	 * @return string
	 */
	public function getClarityCallType($loan_type_id);

	/**
	 * Returns a Clarity_UW_IRequest object.
	 *
	 * @param int $loan_type_id
	 * @return Clarity_UW_IRequest
	 */
	public function getClarityRequest($store, $inquiry, $loan_type_id);

	/**
	 * Returns a Clarity_UW_IResponse object.
	 *
	 * @param int $loan_type_id
	 * @return Clarity_UW_IResponse
	 */
	public function getClarityResponse($loan_type_id);

	/**
	 * Returns a VendorAPI_StatProClient object.
	 *
	 * @return VendorAPI_StatProClient
	 */
	public function getStatProClient();
	
	/**
	 * Returns the database connection holding the state objects.
	 * @return DB_IConnection_1
	 */
	public function getStateObjectDB();

	/**
	 * Returns the environment we're running in (local, rc, live).
	 *
	 * @return string
	 */
	public function getEnvironment();
	
	/**
	 * Sets the environment we're running in (local, rc, live).
	 *
	 * @return string
	 */
	public function setEnvironment($environment);

	/**
	 * Returns the ECash_Factory.
	 *
	 * @return ECash_Factory
	 */
	public function getFactory();

	/**
	 * Returns a DB_Models_DatabaseModel_1 object.
	 *
	 * @param String $table
	 * @param DB_IConnection_1 $db
	 * @return DB_Models_DatabaseModel_1
	 */
	public function getDataModelByTable($table, DB_IConnection_1 $db = NULL);

	/**
	 * Returns the mode we're running in (local, rc, live).
	 *
	 * @return string
	 */
	public function getMode();

	/**
	 * Returns the license key for the company's enterprise site.
	 *
	 * @return string
	 */
	public function getEnterpriseSiteLicenseKey();

	/**
	 * Returns the site_id for the company's enterprise site.
	 *
	 * @return int
	 */
	public function getEnterpriseSiteId();
	
	/**
	 * Defined by VendorAPI_IDriver.
	 *
	 * @return string
	 */
	public function getEnterpriseSiteName();
	
	/**
	 * Defined by VendorAPI_IDriver.
	 *
	 * @return string
	 */
	public function getEnterpriseSiteURL();

	/**
	 * Get the qualify object
	 *
	 * @return VendorAPI_IQualify
	 */
	public function getQualify();

	/**
	 * Returns a dom document containing the
	 * pageflow config.
	 * @todo Make this a business rule?
	 * @return DOMDocument
	 */
	public function getPageflowConfig();

	/**
	 * Returns a config for page flow
	 * @return DOMDocument
	 */
	public function getPostConfig();

	/**
	 * Returns url to make a prpc call to bfw (for state object)
	 *
	 * @return string
	 */
	public function getBFW_PRPC_URL();
	
	/**
	 * Get the sites configuration
	 * @param string 32+ character license
	 * @param $promo_id int Promo ID
	 * @param $promo_sub_code int Promo Sub Code
	 * @return Returns the site configuration as an object
	 */
	public function getSiteConfig($license, $promo_id = NULL, $promo_sub_code = NULL);


	/**
	 * Returns the Request Timer
	 *
	 * @return VendorAPI_RequestTimer
	 */
	public function getTimer();
}
