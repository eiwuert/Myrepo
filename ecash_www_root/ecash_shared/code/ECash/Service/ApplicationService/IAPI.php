<?php
/**
 * ApplicationService API interface defines the methods required to support the service's WSDL (app.wsdl)
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @package ApplicationService
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
interface ECash_Service_ApplicationService_IAPI
{
    /**
     * Test the service connection
     *
     * @returns bool
     */
    public function testConnection();

    /**
     * Stores a personal reference
     *
     * @returns true is successful
     */
    public function addPersonalReferences($application_id);

    /**
     * Returns application details
     *
     * @returns application details
     */
    public function applicationSearch($criteria);
    
    /**
     * Associates an applicant account with an applicant
     *
     * @returns true is successful
     */
    public function associateApplicantAccount($application_id);
    
    /**
     * Retrieves all application data for a app id
     *
     * @returns object
     */
    public function fetchAll($application_id);
    
    /**
     * Retrieves application data for a app id
     *
     * @returns object
     */
    public function getApplicationInfo($application_id);

    /**
     * Returns the applicant account information
     *
     * @return object
     */
    public function getApplicantAccountInfo($application_id);
    
    /**
     * Returns the applicant information
     *
     * @returns object
     */
    public function getApplicantInfo($application_id);

    /**
     * Retrieves the application audit information for the application 
     *
     * @returns object
     */
    function getApplicationAuditInfo($application_id);

    /**
     * Retrieves the application id for a customer
     *
     * @returns integer
     */
    public function getApplicationIdsForCustomer($customer_id);

    /**
     * Returns the personal references associated with the application
     *
     * @returns object
     */
    public function getApplicationPersonalReferences($application_id);

    /**
     * Returns the entire application status history of the application
     *
     * @returns object
     */
    public function getApplicationStatusHistory($application_id, $details = false);

    /**
     * Returns the latest version of the application information
     *
     * @returns integer
     */
    public function getApplicationVersion($application_id);

    /**
     * Returns the bank info associated with the application
     *
     * @returns object
     */
    public function getBankInfo($application_id);

    /**
     * Returns the campaign info associated with the application
     *
     * @returns object
     */
    public function getCampaignInfo($application_id);

    /**
     * Returns the do not loan audit for an applicant
     *
     * @returns object
     */
    function getDoNotLoanAudit($ssn);

    /**
     * Returns a do not loan flag for a given ssn
     *
     * @returns object
     */
    function getDoNotLoanFlag($ssn);

    /**
     * Returns all do not loan flags for a given ssn
     *
     * @returns object
     */
    public function getDoNotLoanFlagAll($ssn);

    /**
     * Returns all the do not loan flag override records
     *
     * @returns object
     */
    public function getDoNotLoanFlagOverrideAll($ssn);

    /**
     * Returns the employment info associated with the application
     *
     * @returns object
     */
    public function getEmploymentInfo($application_id);

    /**
     * Returns any applications that the customer may have had
     *
     * @returns object
     */
    public function getPreviousCustomerApps($application_id);

    /**
     * Returns the react affiliation for the current application
     *
     * @returns object
     */
    public function getReactAffiliation($application_id);

    /**
     * Returns the react affiliation children application for the current 
     *
     * @returns object
     */
    public function getReactAffiliationChildren($application_id);

    /**
     * Returns the regulatory flag, if exists
     *
     * @returns object
     */
    function getRegulatoryFlag($application_id);
	
    /**
     * Inserts an application details into the aalm database.
     *
     * @returns boolean
     */
    public function insert($applicationObj);

	
    /**
     * Tests to make sure that the ssn is properly formated.
     *
     * @returns boolean
     */
    function validSsn($ssn);
	
    /**
     * Generates a login id for the customer web interface.
     *
     * @returns string
     */
    function generateLogin($app);
	
    /**
     * Generates an initial password for the customer web interface.
     *
     * @returns 8 character random string
     */
    function generatePassword();

    /**
     * Creates a do not loan flag record in the database
     *
     * @returns boolean
     */
    public function insertDoNotLoanFlag($DoNotLoanFlagObj);

    /**
     * Inserts a new, unpurchased application in the Application Service
     *
     * @returns integer application_id
     */
    public function insertUnpurchasedApp($company_id);

    /**
     * Updates the applicant information
     *
     * @returns boolean
     */
    function updateApplicant($applicantObj);

    /**
     * Updates an applications account password
     *
     * @returns boolean
     */
    public function updateApplicantAccount($login, $old_password, $new_password);

    /**
     * Updates an application row
     *
     * @returns boolean
     */
    public function updateApplication($applicationObj);

    /**
     * Updates the banking information for the application
     *
     * @returns boolean
     */
    function updateApplicationBankInfo($bankInfoObj);

    /**
     * Updates the application information.
     *
     * @returns boolean
     */
    public function updateApplicationComplete($applicationObj);

    /**
     * Updates the status of the application
     *
     * @returns boolean
     */
    public function updateApplicationStatus($applicationObj);

    /**
     * Updated the contact information
     *
     * @returns boolean
     */
    function updateContactInfo($contactInfoObj);

    /**
     * Updated the employment information
     *
     * @returns boolean
     */
    function updateEmploymentInfo($employmentInfoObj);

    /**
     * Updates the paydate information
     *
     * @returns boolean
     */
    function updatePaydateInfo($paydateInfoObj);

    /**
     * Updates a personal reference
     *
     * @returns boolean
     */
    function updatePersonalReference($referenceObj);
}

