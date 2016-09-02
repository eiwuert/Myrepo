<?php
require_once "crypt.3.php";
require_once "Queries.php";

/**
 * ECash Commercial Document Sevice API abstract
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @package
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
abstract class ECash_Service_DocumentService_API implements ECash_Service_DocumentService_IAPI
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
			ECash_Service_DocumentService_IECashAPIFactory $ecash_api_factory,
			$company_id,
			$agent_login,
			$use_web_services) {
		$this->ecash_factory = $ecash_factory;
		$this->ecash_api_factory = $ecash_api_factory;
		$this->company_id = $company_id;
		$this->agent_login = $agent_login;
		$this->use_web_services = $use_web_services;
		$this->query = new ECash_Service_DocumentService_Queries();
	}

	protected function getCompanyID()
	{
		return $this->company_id;
	}

	/**
	 * @see ECash_Service_Loan_IAPI#testConnection
	 * @return bool
	 */
	public function testConnection()
	{
		return TRUE;
	}
	
	/**
	 * Finds all of the documents related by application id 
	 *
	 * @returns array of documents
	 */
	public function findAllDocumentsByApplicationId($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not find documents, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called findAllDocumentsByApplicationId for application: ".$application_id.".");
			$result = $this->query->findApplicationDocumentsQuery($application_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to find documents " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get find documents: " . $e->getMessage());
			error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Finds all of the documents related by archive id
	 *
	 * @returns array of documents
	 */
	public function findDocumentByArchiveId($archive_id){

		if (!isset($archive_id) || is_null($archive_id) || ($archive_id < 1)){
			$this->insertLogEntry("Can not find archived document, archive id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called findDocumentByArchiveId for archive: ".$archive_id.".");
			$result = $this->query->findArchivedDocumentQuery($archive_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to find archived document: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get find archived document: " . $e->getMessage());
			error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Finds a documents by document id
	 *
	 * @returns a document
	 */
	public function findDocumentById($document_id){
		if (!isset($document_id) || is_null($document_id) || ($document_id < 1)){
			$this->insertLogEntry("Can not get document, document id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called findDocumentById for document: ".$document_id.".");
			$result = $this->query->getDocumentQuery($document_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get document: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get document: " . $e->getMessage());
			error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Finds the next documents id
	 *
	 * @returns a document id
	 */
	public function findNextDocumentId(){

		try {
			$this->insertLogEntry("Called findNextDocumentId.");
			$result = $this->query->getNextDocumentID();
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable find next document id: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get nwxt document id: " . $e->getMessage());
			error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Save a document
	 *
	 * @returns true is successful
	 */
	public function saveDocuments($documentObjArray){

		if (!is_array($documentObjArray) && (count($documentObjArray) < 1)){

			$this->insertLogEntry("Can not add documents none set.");
			return false;
		}
		
		$i = 0;
		$ret_ary = array();
		foreach($documentObjArray as $doc){
			if (is_array($doc)) {
				$tmp = new stdClass();
				foreach ($doc as $key => $val){
					$tmp->$key = $val;
				}
				$doc = $tmp;
			}
			if (!isset($doc->application_id) || is_null($doc->application_id) || ($doc->application_id < 1)){

				$this->insertLogEntry("Can not save document, application id not set.");
				return false;
			}
			if (!isset($doc->agent_id) || is_null($doc->agent_id) || ($doc->agent_id < 1)){

				$this->insertLogEntry("Can not save document, modifying agent id not set.");
				return false;
			}
			try {
				if (!($app = $this->query->getApplicationDetailsQuery($doc->application_id))){
					$this->insertLogEntry("Can not save document, application:".$doc->application_id." not found.");
					return false;
				}
				// look for original
				if ($doc->document_id && ($doc_ref = $this->query->getDocument($doc->document_id))){

					$this->insertLogEntry("Called saveDocument for application: ".$doc->application_id.", document: ".$rf->document_id.".");
				
					// set defaults to existing data and test to see if update is necessary
					$trigger = false;
					$trigger = $trigger || !is_null($doc->company_id 		= $this->fillDefault($doc->company_id,			$doc_ref->company_id));
					$trigger = $trigger || !is_null($doc->document_list_name 	= $this->fillDefault($doc->document_list_name,		$doc_ref->document_list_name));
					$trigger = $trigger || !is_null($doc->document_method 		= $this->fillDefault($doc->document_method,		$doc_ref->document_method));
					$trigger = $trigger || !is_null($doc->document_method_legacy	= $this->fillDefault($doc->document_method_legacy,	$doc_ref->document_method_legacy));
					$trigger = $trigger || !is_null($doc->document_event_type 	= $this->fillDefault($doc->document_event_type,		$doc_ref->document_event_type));
					$trigger = $trigger || !is_null($doc->name_other 		= $this->fillDefault($doc->name_other,			$doc_ref->name_other));
					$trigger = $trigger || !is_null($doc->document_id_ext 		= $this->fillDefault($doc->document_id_ext,		$doc_ref->document_id_ext));
					$trigger = $trigger || !is_null($doc->signature_status 		= $this->fillDefault($doc->signature_status,		$doc_ref->signature_status));
					$trigger = $trigger || !is_null($doc->sent_to 			= $this->fillDefault($doc->sent_to,			$doc_ref->sent_to));
					$trigger = $trigger || !is_null($doc->transport_method 		= $this->fillDefault($doc->transport_method,		$doc_ref->transport_method));
					$trigger = $trigger || !is_null($doc->archive_id 		= $this->fillDefault($doc->archive_id,			$doc_ref->archive_id));
					$trigger = $trigger || !is_null($doc->sent_from 		= $this->fillDefault($doc->sent_from,			$doc_ref->sent_from));
		
					$result = false;
					// update status and insert history tables
					if ($trigger) {
						$result = $result || $this->query->updateDocumentQuery($doc);
					}
				} else {
					$this->insertLogEntry("Called saveDocument for application: ".$doc->application_id.".");
					$doc->document_id = $this->query->insertDocumentQuery($doc);
				}
			} catch  (Exception $e) {
				$response->error = "service_error";								
				$this->insertLogEntry("Unable to save document: " . $e->getMessage());
				error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
				error_log("Unable to update save document: " . $e->getMessage());
				error_log(print_r($e,true));
				$result = false;
			}
			
			$ret_ary[] = $doc;
		}

		return $ret_ary;
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
