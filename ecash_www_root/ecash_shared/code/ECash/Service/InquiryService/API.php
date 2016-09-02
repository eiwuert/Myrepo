<?php
require_once "crypt.3.php";
require_once "Queries.php";

/**
 * ECash Commercial Application Sevice API abstract
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
abstract class ECash_Service_InquiryService_API implements ECash_Service_InquiryService_IAPI
{
	/**
	 * @var ECash_Factory
	 */
	protected $ecash_factory;
	
	/**
	 * @var ECash_Service_Loan_ICustomerLoanProvider
	 */
	protected $ecash_api_factory;

	/**
	 * @var ECash_Service_Loan_ICustomerLoanProvider
	 */
	private $loan_provider;

	/**
	 * @var int
	 */
	private $company_id;
	
	/**
	 * @var string
	 */
	private $agent_login;

	/**
	 * @var bool
	 */
	private $use_web_services;

	/**
	 * @var db driver class
	 */
	private $query;

	public function __construct(
			ECash_Factory $ecash_factory,
			ECash_Service_InquiryService_IECashAPIFactory $ecash_api_factory,
			$company_id,
			$agent_login,
			$use_web_services) {
		$this->ecash_factory = $ecash_factory;
		$this->ecash_api_factory = $ecash_api_factory;
		$this->company_id = $company_id;
		$this->agent_login = $agent_login;
		$this->use_web_services = $use_web_services;
		$this->query = new ECash_Service_InquiryService_Queries();
	}

	/**
	 * Test the service connection
	 *
	 * @return bool
	 */
	public function testConnection(){
		return TRUE;
	}

	/**
	 * Loads all of the inquiries for a specific application
	 *
	 * @return bool
	 */
	public function findInquiriesByApplicationId($application_id){
		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not find inquires, application id not set.");
			return false;
		}
		try {
			$result = new stdClass();
			$this->insertLogEntry("Called findInquiriesByApplicationId for application: ".$application_id.".");
			$result->item = $this->query->findInquiriesByApplicationQuery($application_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to find inquires: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to find inquires: " . $e->getMessage());
			error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Loads and individual inquiry
	 *
	 * @return bool
	 */
	public function findInquiryById($inquiry_id){
		if (!isset($inquiry_id) || is_null($inquiry_id) || ($inquiry_id < 1)){
			$this->insertLogEntry("Can not find inquires, inquiry id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called findInquiryById for: ".$inquiry_id.".");
			$result = $this->query->findInquiryQuery($inquiry_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to find inquires: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to find inquires: " . $e->getMessage());
			error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Loads the inquiry for the non-react (probably first) application of a customer
	 *
	 * @return bool
	 */
	public function findLastNonReactInquiries($application_id){
		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not find last non react inquiry, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called findLastNonReactInquiries for: ".$application_id.".");
			$non_react_app = $this->query->getApplicationQuery($application_id);
			while (($non_react_app) && ($non_react_app['is_react'])) {
				$react_afil = $this->query->getReactAffiliationQuery($application_id,true);
				$non_react_app = $this->query->getApplicationQuery($react_afil['application_id']);
			}
			if (!$non_react_app){
				$this->insertLogEntry("Can not find last non react inquiry, non react application not found.");
				return false;
			}
			$result = $this->query->findInquiryQuery($non_react_app['application_id']);
	
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to find last non react inquiry: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to find last non react inquiry: " . $e->getMessage());
			error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Get the inquiry failures for an ssn from the skip trace table
	 *
	 * @return bool
	 */
	public function getFailuresBySsn($ssn){
		if (!isset($ssn) || is_null($ssn) || !($this->validSsn($ssn))){
			$this->insertLogEntry("Can not find inquiry skip trace failures, ssn not valid.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getFailuresBySsn for: ".$ssn.".");
			$result = $this->query->findSkipTraceBySsnQuery($ssn);
	
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to find inquiry skip trace failures: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to find inquiry skip trace failures: " . $e->getMessage());
			error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Save an inquiry after the results are received
	 *
	 * @return bool
	 */
	public function recordInquiry($inquiryObj){
		try {
			$this->insertLogEntry("Called recordInquiry.");
			// make sure packages are uncompressed
			if (ord(substr($inquiryObj->receive_package, 5, 1)) == '156'){
				$inquiryObj->receive_package = gzuncompress(substr($inquiryObj->receive_package, 4));
			}
			if (ord(substr($inquiryObj->sent_package, 5, 1)) == '156'){
				$inquiryObj->sent_package = gzuncompress(substr($inquiryObj->sent_package, 4));
			}
			// Make sure you have an application_id (we don't care if it's accurate or not)
			if (isset($inquiryObj->application_id) && ($inquiryObj->application_id > 0)) $inquiryObj->application_id = $inquiryObj->application_id;
			elseif (isset($inquiryObj->external_id) && ($inquiryObj->external_id > 0)) $inquiryObj->application_id = $inquiryObj->external_id;
			else $inquiryObj->application_id = 0;
			// Make sure we have a default payrate
			if (!isset($inquiryObj->payrate)) $inquiryObj->payrate = 0;
			// Test if we have recorded a similar record already
			$row = $this->query->findDupInquiryQuery($inquiryObj);
			// If not record it, otherwise return the old inquiry id
			$inquiryObj = $this->handleBadObjectChars($inquiryObj);
			if (!$row) $return = $this->query->recordInquiryQuery($inquiryObj);
			else $return = $row->bureau_inquiry_id;
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to record inquiry: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to record inquiry: " . $e->getMessage());
			error_log(print_r($e,true));
			$return = false;
		}
		return $return;
	}

	/**
	 * Save the inquiry result (success/fail) in the skip trace table for an ssn
	 *
	 * @return bool
	 */
	public function recordSkipTrace($ssn, $external_id, $source, $call_type, $reason, $status, $contactInfoAry){
		if (!isset($ssn) || is_null($ssn) || !($this->validSsn($ssn))){
			$this->insertLogEntry("Can not record skip trace, ssn not valid.");
			return false;
		}
		try {
			$this->insertLogEntry("Called recordSkipTrace.");
			$return = $this->query->recordSkipTraceQuery($ssn, $external_id, $source, $call_type, $reason, $status);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to record inquiry: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to record inquiry: " . $e->getMessage());
			error_log(print_r($e,true));
			$return = false;
		}
		return $return;
	}

	/**
	 * Inserts a message into the log.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	abstract protected function insertLogEntry($message);

	/**
	 * Creates a generates a unique login id based off of the customers name
	 *
	 * @returns boolean
	 */
	private function validSsn($ssn){
		$return = false;
		if ((strlen($ssn) == 9) && (is_numeric($ssn))) $return = true;
		return $return;
	}
	
		
	/**
	 * Check to make sure the application service is enabled
	 *
	 * @param string $function - __FUNCTION__ - name of the calling function
	 * @return bool - Whether the service is enabled or not
	 */
	public function isEnabled($function)
	{
		$enabled = TRUE;
		if (!$this->getEnabled())
		{
			$enabled = FALSE;
		}

		return $enabled;
	}

	/**
	 * Check to make sure the application service is enabled for inserts
	 *
	 * @param string $function - __FUNCTION__ - name of the calling function
	 * @return bool - Whether the service is enabled or not
	 */
	public function isInsertEnabled($function)
	{
		$enabled = TRUE;
		if (!$this->getEnabledInserts())
		{
			$enabled = FALSE;
		}

		return $enabled;
	}

	/**
	 * Check to make sure that reads are enabled
	 *
	 * @param string $function
	 * @return bool
	 */
	public function isReadEnabled($function)
	{
		$enabled = TRUE;
		if (!$this->isEnabled($function) || !$this->getReadEnabled())
		{
			$enabled = FALSE;
		}

		return $enabled;
	}
	
	/**
	 * Gets the commercial specific enabled flag
	 * 
	 * @return bool
	 */
	protected function getEnabled()
	{
		return ECash::getConfig()->USE_WEB_SERVICES;
	}
	
	/**
	 * Gets the commercial specific enabled flag for inserts
	 * 
	 * @return bool
	 */
	protected function getEnabledInserts()
	{
		return ECash::getConfig()->INSERT_WEB_SERVICES;
	}
	
	/**
	 * Gets the commercial specific reads enabled flag
	 * 
	 * @return bool
	 */
	protected function getReadEnabled()
	{
		return ECash::getConfig()->USE_WEB_SERVICES_READS;
	}

	/**
	 *
	 * @return Boolean
	 */
	protected function getLastResponse()
	{
		return ECash::getConfig()->LOG_SERVICE_RESPONSE;
	}

	/**
	 *
	 * @return Boolean
	 */
	protected function getLastRequest()
	{
		return ECash::getConfig()->LOG_SERVICE_REQUEST;
	}
	
	function handleBadObjectChars($obj){
		foreach ($obj as $key => $value) {
			if (is_string($value)){
				while (!(strpos($value,"\\") == 0)) $value = str_replace('\\','',$value);
				$value = str_replace('"','\"',$value);
				$obj->$key = $value;
			} else if (is_object($value)){
				$this->handleBadObjectChars($value);
			}
		}
		return($obj);
	}
}
?>
