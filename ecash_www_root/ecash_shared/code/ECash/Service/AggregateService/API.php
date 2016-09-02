<?php
require_once "crypt.3.php";

/**
 * ECash Commercial Aggregate Sevice API abstract
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
abstract class ECash_Service_AggregateService_API implements ECash_Service_AggregateService_IAPI
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

	/**
	 * @var application service
	 */
	private $appServ;

	/**
	 * @var inquiry service
	 */
	private $inqServ;

	public function __construct(
			ECash_Factory $ecash_factory,
			ECash_Service_AggregateService_IECashAPIFactory $ecash_api_factory,
			$company_id,
			$agent_login,
			$use_web_services) {
		$this->ecash_factory = $ecash_factory;
		$this->ecash_api_factory = $ecash_api_factory;
		$this->loan_provider = $loan_provider;
		$this->company_id = $company_id;
		$this->agent_login = $agent_login;
		$this->use_web_services = $use_web_services;

		$company = ECash::getFactory()->getModel('Company');
		if (!$company->loadBy(array('company_id' => $company_id)))
		{
			throw new Exception('Unknown company id');
		}

		$ecash_ap_factory = new ECash_ApplicationService_ECashAPIFactory($ecash_api_factory->getDb(), $company);
		$ecash_iq_factory = new ECash_InquiryService_ECashAPIFactory($ecash_api_factory->getDb(), $company);
		$this->appServ = new ECash_ApplicationService_API(
			$ecash_factory,
			$ecash_ap_factory,
			$company_id,
			$agent_login,
			$use_web_services);
		$this->inqServ = new ECash_InquiryService_API(
			$ecash_factory,
			$ecash_iq_factory,
			$company_id,
			$agent_login,
			$use_web_services);
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
	public function AggregateCall($aggregateObj){
		$actions = $aggregateObj->item;
		$return = array();
		foreach ($actions as $action){
			$args = json_decode($action->args);
			$ret_elem = $action;
			switch ($action->service){
				case "application":
					$result = $this->appServ->{$action->function}($args[0]);
					break;
				case "inquiry":
					$result = $this->inqServ->{$action->function}($args[0]);
					break;
			}
			$ret_elem->return_value = json_encode(array('return_value' => $result->return_value));
			$return[] = $ret_elem;
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
