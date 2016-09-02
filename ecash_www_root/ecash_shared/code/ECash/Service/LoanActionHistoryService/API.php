<?php
require_once "crypt.3.php";
require_once "Queries.php";

/**
 * ECash Commercial Loan Action History Sevice API abstract
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
abstract class ECash_Service_LoanActionHistoryService_API implements ECash_Service_LoanActionHistoryService_IAPI
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
			ECash_Service_LoanActionHistoryService_IECashAPIFactory $ecash_api_factory,
			$company_id,
			$agent_login,
			$use_web_services) {
		$this->ecash_factory = $ecash_factory;
		$this->ecash_api_factory = $ecash_api_factory;
		$this->company_id = $company_id;
		$this->agent_login = $agent_login;
		$this->use_web_services = $use_web_services;
		$this->query = new ECash_Service_LoanActionHistoryService_Queries();
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
	 * Saves a loan action history into the table
	 *
	 * @returns integer (loan action history id)
	 */
	public function save($loanActionHistoryObj){
		$lah = $loanActionHistoryObj;
		if (!isset($lah->application_id) || is_null($lah->application_id) || ($lah->application_id<1)){
			$this->insertLogEntry("Can not save loan action history, application id not set.");
			return false;
		}
		if (!isset($lah->agent_id) || is_null($lah->agent_id) || ($lah->agent_id<1)){
			$this->insertLogEntry("Can not save loan action history, agent id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called save in LoanActionHistoryService.");
			//$return = $lah->application_id;
			$return = $this->query->saveLoanActionHistoryQuery($lah);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to save loan action history: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to save loan action history: " . $e->getMessage());
			error_log(print_r($e,true));
			$return = false;
		}
		return $return;
	}
	
	/**
	 * Retrieves the loan action history for an application
	 *
	 * @returns query map array
	 */
	public function getLoanActions($criteria){
		if (is_object($criteria)){
			$application_id = $criteria->applicationId;
			$action = $criteria->actionName;
		} else {
                        $application_id = $criteria['applicationId'];
			$action = $criteriai['actionName'];			 
		}
		if (!isset($application_id) || is_null($application_id) || ($application_id<1)){
			$this->insertLogEntry("Can not get loan action history, application id not set.");
			return false;
		}
		try {
			$return = new stdClass();
			$entries = array();
			$this->insertLogEntry("Called getLoanActions for application: ".$application_id.".");
			$results = $this->query->getLoanActionQuery($application_id,$action);
			foreach ($results as $result){
				$entry = array();
				foreach ((array)$result as $key => $val){
					$rt = new stdClass();
					$rt->key = $key;
					$rt->value = $val;
					$entry[] = $rt;
				}
				$entries[] = $entry;
			}
			//$return->string2anyTypeMap = $entries;
			$return->return = $entries;

		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get loan action history: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to save loan action history: " . $e->getMessage());
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
	
	/**
	 * Performs the call to the underlying service, clearing all buffered calls
	 *
	 * @return mixed
	 */	
	public function flush()
	{
		$buffer = $this->buffer->flush();
		if (!empty($buffer))
		{		

			return	$this->aggregate_soap_client->AggregateCall($buffer);
		}
		else
		{
			return FALSE;
		}
	}
	/**
	 * Resolve unhandled function calls to the service to the service client
	 *
	 * @param boolean $enabled - if aggregate service calls are enabled
	 * @return void
	 */
	public function setAggregateEnabled($enabled)
	{
		$this->aggregate_enabled = $enabled;
	}
}
?>
