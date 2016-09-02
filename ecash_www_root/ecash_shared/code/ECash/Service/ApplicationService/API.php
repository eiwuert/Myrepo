<?php
require_once "crypt.3.php";
require_once "Queries.php";

/**
 * ECash Commercial Application Sevice API abstract
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
abstract class ECash_Service_ApplicationService_API implements ECash_Service_ApplicationService_IAPI
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
	
	// table to decode search fields
	protected $field_assc = array(
		                "name_first"                    => "name_first",
				"name_last"                     => "name_last",
				"social_security_number"        => "ap.ssn",
				"ssn"        			=> "ap.ssn",
				"ssn_last4"                     => "ssn_last4",
				"zip_code"                      => "zip_code",
				"bank_account"                  => "bank_account",
				"bank_aba"                      => "bank_aba",
				"email"                         => "email",
		                "phone"                         => array("phone_home", "phone_work", "phone_cell"),
		                "customer_id"                   => "applicant_account_id",
		                "ip_address"                    => "ip_address",
		                "track_id"                      => "track_id",
		                "application_id"                => "application_id",
		                "dateOfBirth"                   => "dob",
		                "homePhone"                     => "phone_home",
		                "bankAba"                       => "bank_aba",
		                "bankAccount"                   => "bank_account",
		                "legalIdNumber"                 => "legal_id_number"
		);
	
	public function __construct(
			ECash_Factory $ecash_factory,
			ECash_Service_ApplicationService_IECashAPIFactory $ecash_api_factory,
			$company_id,
			$agent_login,
			$use_web_services) {

		$this->ecash_factory = $ecash_factory;
		$this->ecash_api_factory = $ecash_api_factory;
		$this->company_id = $company_id;
		$this->agent_login = $agent_login;
		$this->use_web_services = $use_web_services;
		$this->query = new ECash_Service_ApplicationService_Queries();
	}

	protected function getCompanyID() {
		return $this->company_id;
	}

	/**
	 * @see ECash_Service_Loan_IAPI#testConnection
	 * @return bool
	 */
	public function testConnection() {
		return TRUE;
	}
	
	/**
	 * Decodes a strategy string into a sql where condition 
	 *
	 * @returns a string
	 */
	private function decode_strategy($strategy,$search) {
		switch ($strategy){
			case "is":
			case "like":
				$cond = " = ";
				$srch = $search;
				$ret = $cond."'".$srch."'";
				break;
			case "starts_with":
				$cond = " LIKE ";
				$srch = $search."%";
				$ret = $cond."'".$srch."'";
				break;
			case "ends_with":
				$cond = " LIKE ";
				$srch = $search."%";
				$ret = $cond."'".$srch."'";
				break;
			case "contains":
				$cond = " LIKE ";
				$srch = "%".$search."%";
				$ret = $cond."'".$srch."'";
				break;
			case "in":
				$cond = " IN ";
				$srch = "('".$search."')";
				$ret = $cond.$srch;
				break;
		}
		return $ret;
	}
	
	/**
	 * Stores a personal reference
	 *
	 * @returns true is successful
	 */
	public function addPersonalReferences($referenceObjAry){
		if (!is_array($referenceObjAry) && (count($referenceObjAry) < 1)){
			$this->insertLogEntry("Can not add personal reference none set.");
			return false;
		}
		
		$i = 0;
		$ret_ary = array();
		foreach($referenceObjAry as $refObj){
			$i++;
			if (!isset($refObj->application_id) || is_null($refObj->application_id) || ($refObj->application_id < 1)){
				$full_name = isset($refObj->application_id) ? $refObj->full_name : "Unknown";
				$this->insertLogEntry("Can not add personal reference ".$full_name.", application id not set.");
				$ret_ary[$i] = -1;
			} else {
				$this->insertLogEntry("Called addPersonalReferences for application_id: ".$refObj->application_id);
				if (!isset($refObj->modifying_agent_id) || is_null($refObj->modifying_agent_id) || ($refObj->modifying_agent_id < 1)){
					$this->insertLogEntry("Can not add personal reference ".$full_name." modifying agent id not set.");
					$ret_ary[$i] = -1;
				} else {
					try {
						if (!($app = $this->query->findApplication($refObj->application_id))){
							$this->insertLogEntry("Can not add personal reference ".$refObj->application_id." not found.");
							$ret_ary[$i] = -1;
						} else {
							$ret_ary[$i] = $this->updatePersonalReference(reference);
						};
					} catch  (Exception $e) {
						$response->error = "service_error";								
						$this->insertLogEntry("Unable to add personal reference: " . $e->getMessage());
						error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
						error_log("Unable to add personal reference: " . $e->getMessage());
						//error_log(print_r($e,true));
						$i++;
						$ret_ary[$i] = -1;
					}
				}
			}
		}
		return $ret_ary;
	}

	/**
	 * Returns application details
	 *
	 * @returns application object
	 */
	public function applicationSearch($criteria){

		if (!is_array($criteria) && (count($criteria) < 1)){
			$this->insertLogEntry("Can not search for application, criteria not set.");
			return false;
		}
		$where_clause = " WHERE ";
		$start = strlen($where_clause);

		$item = (isset($criteria->item)) ? $item = $criteria->item : $criteria;

		if (is_array($item)) $items = $item;
		else $items = array($item);

		foreach ($items as $item) {
			if (is_array($item)) {
				$temp = new stdClass();
				$temp->field = $item['field'];
				$temp->strategy = $item['strategy'];
				$temp->searchCriteria = $item['searchCriteria'];
				$item = $temp;
			}
			if (strlen($where_clause) > $start) $where_clause .= " AND ";

			if (is_array($this->field_assc[$item->field])){
				$where_clause .= " (";
				$idx = 1;
				foreach ($this->field_assc[$item->field] as $elem){
					$where_clause .= $elem.$this->decode_strategy($item->strategy,$item->searchCriteria);
					if ($idx++ < count($this->field_assc[$item->field])) $where_clause .= " OR ";
				}
				$where_clause .= ") ";
			} else {
				$where_clause .= $this->field_assc[$item->field].$this->decode_strategy($item->strategy,$item->searchCriteria);
			}
		}
		try {
			$this->insertLogEntry("Called searchApplication for search: ".$where_clause.".");
			$rows = $this->query->searchApplication($where_clause);
			if (!$rows){
				$this->insertLogEntry("ERROR: Failed searching for application by ".$where_clause.".");
				return false;
			}
			$item_ary = array();
			foreach ($rows as $row){
				$item = new stdClass;
				$item->date_created = $row->date_created;
				$item->application_id = $row->application_id;
				$item->company_id = $row->company_id;
				$item->loan_type_id = $row->loan_type_id;
				$item->customer_id = $row->customer_id;
				$item->external_id = $row->external_id;
				$item->name_first = $row->name_first;
				$item->name_last = $row->name_last;
				$item->city = $row->city;
				$item->state = $row->state;
				$item->street = $row->street;
				$item->ssn = $row->ssn;
				$item->application_status_name = $row->application_status;
				$item->date_fund_actual = $row->date_fund_actual;
				$item->is_react = $row->is_react;
				$item_ary [] = $item;
			}
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to application search: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to application search: " . $e->getMessage());
			//error_log(print_r($e,true));
			$item_ary = false;
		}
		return $item_ary;
	}

	/**
	 * Associates an applicant account with an applicant
	 *
	 * @returns true is successful
	 */
	public function associateApplicantAccount($applicantAccountObj){
		if (!is_object($applicantAccountObj)){
			$this->insertLogEntry("Can not accociate the application to an account, missing object.");
			return false;
		}
		if (!isset($applicantAccountObj->application_id) || is_null($applicantAccountObj->application_id) || ($applicantAccountObj->application_id < 1)){
			$this->insertLogEntry("Can not add associate account, application id not set.");
			return false;
		}
		if (!isset($applicantAccountObj->login) || is_null($applicantAccountObj->login) ||
		    !isset($applicantAccountObj->password) || is_null($applicantAccountObj->password)){
			$this->insertLogEntry("Can not add associate account, details not set.");
			return false;
		}
		if (!($app = $this->query->findApplication($applicantAccountObj->application_id))){
			$this->insertLogEntry("Can not add accociate the application ".$refObj->application_id." to an account, application not found.");
			return false;
		}
		$application_id = $refObj->application_id;
		if (!($ap_acnt_id = $this->query->findByLoginAndPassword($applicantAccountObj->login,$applicantAccountObj->password))){
			$this->insertLogEntry("Can not add accociate the application ".$refObj->application_id." to an account, login or password not found.");
			return false;
		}
		try {
			$this->insertLogEntry("Called setApplicantAccount for application_id: ".$application_id." applicant account id:".$ap_acnt_id.".");
			$result = $this->query->setApplicantAccount($application_id,$ap_acnt_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to application search: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to application search: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Retrieves all application data for a app id
	 *
	 * @returns object
	 */
	public function fetchAll($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not fetch all, application id not set.");
			return false;
		}
		$return = new stdClass;
		try {
			$this->insertLogEntry("Called fetchAll for application: ".$application_id.".");
			$application = $this->getApplicationInfoQuery($application_id);
			
			$return->application = $this->getApplicationInfo($application);
			$return->applicant_info = $this->getApplicantInfo($application_id);
			$return->applicant_account = $this->getApplicantAccountInfo($application_id);
			$return->bank_info = $this->getBankInfo($application_id);
			$return->employment_info = $this->getEmploymentInfo($application_id);
			$return->contact_info = $this->getContactInfo($application_id);
			$return->primary_contact_info = $this->getPrimaryContactInfo($application_id);
			$return->campaign_info = $this->getCampaignInfo($application_id);
			$return->react_affiliation = $this->getReactAffiliation($application_id);
			$return->regulatory_flag = $this->getRegulatoryFlag($application_id);
			$return->do_not_loan = $this->getDoNotLoanFlag($return->applicant_info->ssn);
			$return->audit_info = $this->getApplicationAuditInfo($application_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get application info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to application search: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $return;
	}

	/**
	 * Retrieves application data for a app id from db
	 *
	 * @returns object
	 */
	public function getApplicationInfoQuery($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get application info, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getApplicationInfoQuery for application: ".$application_id.".");
			$result = new stdClass;
			$result = $this->query->getApplicationQuery($application_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get application info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get application info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Organizes application data for web services
	 *
	 * @returns object
	 */
	public function getApplicationInfo($row){

		if (!isset($row) || is_null($row) || (!is_object($row) && !is_numeric($row))){
			$this->insertLogEntry("Can not get application info, application id not set.");
			return false;
		}
		try {
			if (is_numeric($row)) {
				$application_id = $row;
				$row = $this->getApplicationInfoQuery($application_id);
			}
			$application_id = $row->application_id;
			$this->insertLogEntry("Called getApplicationInfo for application: ".$application_id.".");
			
			$result->applicationId = $row->application_id;
			$result->applicationStatusName = $row->application_status_name;
			$result->apr = $row->apr;
			$result->dateApplicationStatusSet = $row->date_application_status_set;
			$result->dateCreated = $row->date_created;
			$result->dateFirstPayment = $row->date_first_payment;
			$result->dateFundActual = $row->date_fund_actual;
			$result->dateFundEstimated = $row->date_fund_estimated;
			$result->dateModified = $row->date_modified;
			$result->enterpriseSiteId = $row->enterprise_site_id;
			$result->externalId = $row->external_id;
			$result->financeCharge = $row->finance_charge;
			$result->fundActual = $row->fund_actual;
			$result->fundQualified = $row->fund_qualified;
			$result->id = $row->application_id;
			$result->ipAddress = $row->ip_address;
			$result->eSigIpAddress = $row->esig_ip_address;
			$result->isReact = ($row->is_react == 'yes') ? TRUE : FALSE;
			$result->isWatched = ($row->is_watched == 'yes') ? TRUE : FALSE;
			$result->loanTypeId = $row->loan_type_id;
			$result->modifyingAgentId = $row->modifying_agent_id;
			$result->modifying_agent_id = $row->modifying_agent_id;
			$result->paymentTotal = $row->payment_total;
			$result->pricePoint = $row->price_point;
			$result->ruleSetId = $row->rule_set_id;
			$result->trackKey = $row->track_key;
			$result->cfeRulesetId = $row->cfe_ruleset_id;
			$result->cfeRuleSetId = $row->cfe_ruleset_id;
			$result->companyId = $row->company_id;
			
			$customer = $this->query->getApplicantAccountQuery($application_id);
			$result->customerId = $customer->customer_id;
			
			$result->applicationSource = $this->getApplicationSourceInfo($row->application_source);
			$result->callTimePref = $this->getCallTimeInfo($row->call_time_pref);
			$result->contactMethodPref = $this->getContactMethodInfo($row->contact_method_pref);
			$result->marketingContactPref = $this->getMarketingContactInfo($row->marketing_contact_pref);
			$result->applicationType = $this->getApplicationTypeInfo($row->application_type);
			$result->olpProcess = $this->getOLPProcessInfo($row->olp_process);
			$result->applicationStatus = $this->query->getApplicationStatusQuery($row->application_status_id);
			
			$result->authoritative = $this->query->getApplicationAuthoritativeQuery($application_id);
			$result->applicationVersion = $this->query->getApplicationVersionQuery($application_id);
			$result->applicationStatusHistory = $this->getApplicationStatusHistory($row->application_id,true);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get application info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get application info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Organizes application source data for web services
	 *
	 * @returns object
	 */
	function getApplicationSourceInfo($application_source){
		$rtn = new stdClass();
		$rtn->active = 1;
		$rtn->id = 1;
		$rtn->name = $application_source;
		
		return $rtn;
	}

	/**
	 * Organizes the call time preference data for web services
	 *
	 * @returns object
	 */
	function getCallTimeInfo($call_time_pref){
		$rtn = new stdClass();
		$rtn->callTimePrefId = 1;
		$rtn->dateCreated = date("Y-m-d H:i:s");
		$rtn->dateModified = date("Y-m-d H:i:s");
		$rtn->id = 1;
		$rtn->name = $call_time_pref;
		
		return $rtn;
	}

	/**
	 * Organizes the contact method preference data for web services
	 *
	 * @returns object
	 */
	function getContactMethodInfo($contact_method_pref){
		$rtn = new stdClass();
		$rtn->dateCreated = date("Y-m-d H:i:s");
		$rtn->dateModified = date("Y-m-d H:i:s");
		$rtn->id = 1;
		$rtn->name = $contact_method_pref;
		
		return $rtn;
	}

	/**
	 * Organizes the marketing call time preference data for web services
	 *
	 * @returns object
	 */
	function getMarketingContactInfo($marketing_contact_pref){
		$rtn = new stdClass();
		$rtn->dateCreated = date("Y-m-d H:i:s");
		$rtn->dateModified = date("Y-m-d H:i:s");
		$rtn->id = 1;
		$rtn->marketingContactPrefId = 1;
		$rtn->name = $marketing_contact_pref;

		return $rtn;
	}

	/**
	 * Organizes the application type data for web services
	 *
	 * @returns object
	 */
	function getApplicationTypeInfo($application_type){
		$rtn = new stdClass();
		$rtn->active = 1;
		$rtn->id = 1;
		$rtn->name = $application_type;

		return $rtn;
	}

	/**
	 * Organizes the olp process data for web services
	 *
	 * @returns object
	 */
	function getOLPProcessInfo($olp_process){
		$rtn = new stdClass();
		$rtn->dateCreated = date("Y-m-d H:i:s");
		$rtn->dateModified = date("Y-m-d H:i:s");
		$rtn->id = 1;
		$rtn->olpProcessId = 1;
		$rtn->name = $olp_process;

		return $rtn;
	}

	/**
	 * Organizes the application status data for web services
	 *
	 * @returns object
	 */
	function getApplicationStatusInfo($application_status_id,$application_status_name){
		$rtn = new stdClass();
		$rtn->dateCreated = date("Y-m-d H:i:s");
		$rtn->dateModified = date("Y-m-d H:i:s");
		$rtn->id = $application_status_id;
		$rtn->name = $application_status_name;

		return $rtn;
	}

	/**
	 * Returns the applicant account information
	 *
	 * @return object
	 */
	public function getApplicantAccountInfo($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get applicant account info, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getApplicantAccountInfo for application: ".$application_id.".");
			$result = $this->query->getApplicantAccountQuery($application_id);
	
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get applicant account info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get applicant account info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Returns the applicant information
	 *
	 * @returns object
	 */
	public function getApplicantInfo($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get applicant info, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getApplicantInfo for application: ".$application_id.".");
			$result = $this->query->getApplicantQuery($application_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get applicant info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get applicant info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Retrieves the application audit information for the application 
	 *
	 * @returns object
	 */
	function getApplicationAuditInfo($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get application audit info, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getApplicationAuditInfo for application: ".$application_id.".");
			$result = $this->query->getApplicationAuditQuery($application_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get application audit info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get application audit info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Retrieves the application ids for a customer
	 *
	 * @returns integer
	 */
	public function getApplicationIdsForCustomer($customer_id){

		if (!isset($customer_id) || is_null($customer_id) || ($customer_id < 1)){
			$this->insertLogEntry("Can not get application ids, customer id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getApplicationIdsForCustomer for customer: ".$customer_id.".");
			$result = array();
			foreach($this->query->getApplicationIdsForCustomerQuery($customer_id) as $row){
				$result[] = $row->application_id;
			};
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get application ids: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get application ids: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;		
	}

	/**
	 * Retrieves the personal reference information for the application 
	 *
	 * @returns object
	 */
	function getApplicationPersonalReferences($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get application audit info, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getApplicationPersonalReferencesQuery for application: ".$application_id.".");
			$result = $this->query->getApplicationPersonalReferencesQuery($application_id);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get application audit info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get application audit info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Returns the entire application status history of the application for the application_info format
	 * - External version no details
	 * - Internal version details = true
	 *
	 * @returns array of objects
	 * - format array(obj(applicationStatus,dateCreated,modifyingAgentId,nameFirst,nameLast,statusHistoryId)
	 */
	public function getApplicationStatusHistory($application_id_ary, $details = false){
		if (is_object($application_id_ary)) $application_id = $application_id_ary->application_id;
		else if (is_numeric($application_id_ary)) $application_id = $application_id_ary*1;
		else if ((is_array($application_id_ary)) && (isset($application_id_ary['application_id']))) $application_id = $application_id_ary['application_id']*1;
		else $application_id = null;
		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get application status history, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getApplicationStatusHistory for application: ".$application_id.".");
			$result = array();
			$ap_statuses = $this->query->getApplicationStatusHistoryQuery($application_id);

			foreach ($ap_statuses as $ap_status){
				$status_details = $this->query->getApplicationStatusQuery($ap_status->id);
				if ($details) {
					$ap_status->applicationStatus = $status_details;
				}
				else $ap_status->applicationStatus = $status_details->name;
				$ap_status->application_status = $status_details->name;
				$ap_status->application_id = $application_id;
				$result[] = $ap_status;
			}
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get application status history: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get application status history: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Returns the latest version of the application information
	 *
	 * @returns integer
	 */
	public function getApplicationVersion($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get application version, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getApplicationVersionQuery for application: ".$application_id.".");
			$ret = $this->query->getApplicationVersionQuery($application_id); 
			$result = $ret->version;
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get application version: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get application version: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Returns the bank info associated with the application
	 *
	 * @returns object
	 */
	public function getBankInfo($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get bank info, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getBankInfo for application: ".$application_id.".");
			$result = $this->query->getBankQuery($application_id);
			$ret = $this->query->getApplicationVersionQuery($application_id);
			$result->application_version = $ret->version;
			$result->application_id = $application_id;
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get bank info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get bank info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Returns the campaign info associated with the application
	 *
	 * @returns object
	 */
	public function getCampaignInfo($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get campaign info, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getCampaignInfo for application: ".$application_id.".");
			$result = $this->query->getCampaignQuery($application_id); 

		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get campaign info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get campaign info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Returns the contact info associated with the application
	 *
	 * @returns object
	 */
	function getContactInfo($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get contact info, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getContactInfo for application: ".$application_id.".");
			$result = $this->query->getContactInfoQuery($application_id); 
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get contact info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get contact info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Returns the primary contact info associated with the application
	 *
	 * @returns object
	 */
	function getPrimaryContactInfo($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get primary contact info, application id not set.");
			return false;
		}
		try {
			$result = new stdClass();
			$contact_fields = array("phone_cell","phone_home","phone_fax","email","phone_work");
			foreach ($contact_fields as $ci) {
				$ret = $this->query->getContactInfoQuery($application_id,$ci);
				if ($ret) $result->{$ci} = $ret[0]->contact_info_value;
			}
			$this->insertLogEntry("Called getPrimaryContactInfo for application: ".$application_id.".");
			  
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get primary contact info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get primary contact info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Returns the do not loan audit for an applicant
	 *
	 * @returns object
	 */
	function getDoNotLoanAudit($ssn){

		if (!isset($ssn) || is_null($ssn) || ($ssn < 1)){
			$this->insertLogEntry("Can not get do not loan audit, ssn not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getDoNotLoanAuditQuery for ssn: ".$ssn.".");
			$result = $this->query->getDoNotLoanAuditQuery($ssn); 
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get do not loan audit: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get do not loan auditdo not loan audit: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Returns a do not loan flag for a given ssn
	 *
	 * @returns object
	 */
	function getDoNotLoanFlag($ssn){

		if (!isset($ssn) || is_null($ssn) || ($ssn < 1)){
			$this->insertLogEntry("Can not get do not loan audit, ssn not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getDoNotLoanFlag for application: ".$ssn.".");
			$result = $this->query->getDoNotLoanFlagQuery($ssn); 
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get do not loan flag: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get do not loan auditdo not loan flag: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Returns all do not loan flags for a given ssn
	 *
	 * @returns object
	 */
	public function getDoNotLoanFlagAll($ssn){
		if (!isset($ssn) || is_null($ssn) || ($ssn < 1)){
			$this->insertLogEntry("Can not get do not loan audit, ssn not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getDoNotLoanFlagAll for application: ".$ssn.".");
			$result = $this->query->getDoNotLoanFlagAllQuery($ssn); 
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get do not loan flag all: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get do not loan flag all: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Returns all the do not loan flag override records
	 * - none ever exists, so this function is simply a place holder.
	 *
	 * @returns empty array
	 */
	public function getDoNotLoanFlagOverrideAll($ssn){
		return new stdClass;;
	}
	
	/**
	 * Returns the employment info associated with the application
	 *
	 * @returns object
	 */
	public function getEmploymentInfo($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get employment info, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getEmploymentInfo for application: ".$application_id.".");
			$result = $this->query->getEmploymentQuery($application_id);
			$ret = $this->query->getApplicationVersionQuery($application_id);
			$result->application_version = $ret->version;
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get employment info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get employment info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Returns any applications that the customer may have had
	 *
	 * @returns object
	 */
	public function getPreviousCustomerApps($items){
		if (is_object($items->item)) ($items->item = array($items->item));
		if (!is_array($items->item) || (count($items->item) < 1)){
			$this->insertLogEntry("Can not get previous applications, criteria not set.");
			return false;
		}
		$where_clause = " WHERE (";
		$or_go = false;
		$or_check = false;
		foreach ($items->item as $item){
			if (!is_array($item->criteria)) $criterias = array($item->criteria);
			else $criterias = $item->criteria;
			$and_go = false;
			if ($or_go) {
				$where_clause .= " OR (";
				$or_check = true;
			}
			foreach ($criterias as  $criteria){
				if ($and_go) $where_clause .= " AND ";
				$and_go = true;
                        	$where_clause .= $this->field_assc[$criteria->field].$this->decode_strategy($criteria->strategy,$criteria->searchCriteria);
			}
			$or_go = true;
			if ($or_check) $where_clause .= ")";
		}
		$where_clause .= ")";
		try {
			$this->insertLogEntry("Called getPreviousCustomerApps for criteria: ".$where_clause.".");
			if (!($rows = $this->query->getPreviousCustomerQuery($where_clause))){
				$this->insertLogEntry("ERROR: Failed getting previous customer aps ".$where_clause.".");
				return false;
			}
			$item_ary = array();
			foreach ($rows as $row){
				$item = new stdClass;
				$item->application_id = $row->application_id;
				$item->date_application_status_set = $row->date_application_status_set;
				$item->date_created = $row->date_created;
				$item->company = $row->company;
				$item->application_status = $row->application_status;
				$item->olp_process = $row->olp_process;
				$item->bank_account = $row->bank_account;
				$item->bank_aba = $row->bank_aba;
				$item->name_first = $row->name_first;
				$item->name_last = $row->name_last;
				$item->ssn = $row->ssn;
				$item->date_first_payment = $row->date_first_payment;
				$item->street = $row->street;
				$item->ssn = $row->ssn;
				$item->regulatory_flag = false;
				$item->do_not_loan_override = false;
				$item->do_not_loan_other_company = false;
				
				$dnl = $this->query->getDoNotLoanFlagAllQuery($row->ssn);
				if (($dnl = $this->query->getDoNotLoanFlagAllQuery($row->ssn)) && (count($dnl)>0)){
					$item->do_not_loan_in_company = true;
				} else {
					$item->do_not_loan_in_company = false;
				}
				$item_ary [] = $item;
			}
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get previous customer aps: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to application search: " . $e->getMessage());
			//error_log(print_r($e,true));
			$item_ary = false;
		}
		return $item_ary;
	}
	
	/**
	 * Returns the last react affiliation application for the current application
	 *
	 * @returns object
	 */
	public function getReactAffiliation($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get react affilliation, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getReactAffiliation for application: ".$application_id.".");
			$result = $this->query->getReactAffiliationQuery($application_id,true);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get react affilliation: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get react affilliation: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Returns all of the react affiliation children applications for the current 
	 *
	 * @returns object
	 */
	public function getReactAffiliationChildren($application_id){

		if (!isset($application_id) || is_null($application_id) || ($application_id < 1)){
			$this->insertLogEntry("Can not get react affilliation children, application id not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called getReactAffiliationChildren for application: ".$application_id.".");
			$result = $this->query->getReactAffiliationQuery($application_id,false);
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to get react affilliation children: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to get react affilliation children: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Returns the regulatory flag, if exists
	 * - none ever exists, so this function is simply a place holder.
	 *
	 * @returns object
	 */
	function getRegulatoryFlag($application_id){
		return null;
	}
	
	/**
	 * Inserts an application details into the aalm database.
	 *
	 * @returns boolean
	 */
	public function insert($applicationObj){
		$app = $applicationObj;
		if (!is_object($app)){
			$this->insertLogEntry("Can not insert new application, object not set.");
			return false; 
		}
		
		// Set defaults
		$app->ssn = preg_replace('/\D/', '', $app->ssn);
		if (!$this->validSsn($app->ssn)) {
			$this->insertLogEntry("Can not insert new application, ssn not valid.");
			return false; 
		}
		$app = $this->handleBadObjectChars($app);
		$app->legal_id_number = preg_replace('/\D/', '', $app->legal_id_number);
		if (!is_null($app->ssn)) $app->ssn_last_four = substr($app->ssn,-4);
		if (is_null($app->tenancy_type)) $app->tenancy_type = "unspecified";
		if (is_null($app->legal_id_type)) $app->legal_id_type = "NA";
                if (isset($app->income_direct_deposit) && ((($app->income_direct_deposit === TRUE) && !(is_string($app->income_direct_deposit))) || (is_string($app->income_direct_deposit) && ($app->income_direct_deposit == 'yes')))) $app->income_direct_deposit = 'yes';
		else $app->income_direct_deposit = 'no';
		if (is_null($app->marketing_contact_pref)) $app->marketing_contact_pref = "no preference";
		if (is_null($app->contact_method_pref)) $app->contact_method_pref = "no preference";
		if (is_null($app->call_time_pref)) $app->call_time_pref = "no preference";
		if (is_null($app->application_type)) $app->application_type = "paperless";
                if (isset($app->is_watched) && ((($app->is_watched === TRUE) && !(is_string($app->is_watched))) || (is_string($app->is_watched) && ($app->is_watched == 'yes')))) $app->is_watched = 'yes';
		else $app->is_watched = 'no';
		if (is_null($app->fund_requested)) $app->fund_requested = $app->fund_qualified;
                if (is_null($app->price_point)) $app->price_point = 0;
                if (isset($app->is_react) && ((($app->is_react === TRUE) && !(is_string($app->is_react))) || (is_string($app->is_react) && ($app->is_react == 'yes')))) $app->is_react = 'yes';
		else $app->is_react = 'no';
		if (!(isset($app->apr)) || (is_null($app->apr)) || !(is_numeric($app->apr))) $app->apr = 0;
		if (!(isset($app->finance_charge)) || (is_null($app->finance_charge)) || !(is_numeric($app->finance_charge))) $app->finance_charge = 0;
		if (!(isset($app->fund_qualified)) || (is_null($app->fund_qualified)) || !(is_numeric($app->fund_qualified))) $app->fund_qualified = 0;
		if (!(isset($app->fund_requested)) || (is_null($app->fund_requested)) || !(is_numeric($app->fund_requested))) $app->fund_requested = 0;
		if (!(isset($app->payment_total)) || (is_null($app->payment_total)) || !(is_numeric($app->payment_total))) $app->payment_total = 0;

		// Remove the dates, they are prePersisted
		$app->date_created = null;
		$app->date_modified = null;
		$app->date_application_status_set = date('Y-M-d');

		try{

			if (!isset($app->application_id) || is_null($app->application_id) || (!$app->application_id)) {
				$application_id = $this->query->getAuthoritativeQuery($app->company_id);
				$app->application_id = $application_id;
				$this->insertLogEntry("Called insert for application: ".$app->application_id.".");
			}else{
				$application_id = $app->application_id;
				if ($this->query->findApplication($application_id)){
					$this->insertLogEntry("Can not insert new application ".$application_id." already exists.");
					return false; 
				}
			}
			// new customer?
			if (!($apl = $this->query->findPreviousApplicantAccountQuery($app))){
				$app->login_id = $this->generateLogin($app);
				$app->password = $this->generatePassword();
				$app->customer_id = $this->query->insertApplicantAccountQuery($app);
				$app->applicant_account_id = $app->customer_id;
				$this->query->insertCustomerQuery($app);
			} else {
				$app->customer_id = $apl->applicant_account_id;
				$app->applicant_account_id = $apl->applicant_account_id;
				$app->password = $apl->password;
				$app->login_id = $apl->login_id;

			}
			// main tables
			$app->Application_id = $this->query->insertApplicationQuery($app);
			// auxilliary tables (application_ids store in these tables)
			$app->status_history_id = $this->query->insertStatusHistoryQuery($app);
			$app->site_id = $this->getSiteID($app);
			$app->campaign__info_id = $this->query->insertCampaignQuery($app);
			$t= $this->query->getApplicationVersionQuery($app->application_id);

			if ($t) $app->application_version = $this->query->updateApplicationVersionQuery($app->application_id,$t->version+1);
			else $app->application_version = $this->query->insertApplicationVersionQuery($app);
			if (isset($app->personal_references) && ($app->personal_references)) {
				if (is_array($app->personal_references)) {
					foreach ($app->personal_references as $pr) {
						$app->personal_reference_id[] = $this->query->insertPersonalReferenceQuery($pr,$app);
					}
				} else {
					$app->personal_reference_id[]  = $this->query->insertPersonalReferenceQuery($app->personal_references,$app);
				}
			}
			if (isset($app->event_log) && ($app->event_log)) foreach ($app->event_log as $ev) {
				$app->event_log_id[] = $this->query->insertEventLogQuery($ev,$app);
			}
			$app->react_affiliation_id = false;
			if ($app->is_react && (isset($app->react_affiliation)) && ($app->react_affiliation)){
				//  Don't save the react affiliation, it is saved later via the dao modle
				//$app->react_affiliation_id = $this->query->insertReactAffiliationQuery($app->react_affiliation,$app);
			}

			$return = new stdClass();
			$return->applicationId = $app->application_id;
			$return->customerId  = $app->customer_id;
			$return->username  = $app->login_id;
			$return->password  = $app->password;
			$return->isNewCustomer = $app->react_affiliation_id > 0 ? true : false;
			$return->contactInfoIds = array();
			/*
			$contact_fields = array("phone_cell","phone_home","phone_fax","email","phone_work");
			foreach ($contact_fields as $ci) {
				if ($app->{$ci}){
					$entry = new stdClass();
					$entry->key = $ci;
					$entry->value = $this->query->insertContactQuery($app,$ci);
					$return->contactInfoIds[] = $entry;
				}
			}
			*/
			
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to insert application: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to insert application: " . $e->getMessage());
			//error_log(print_r($e,true));
			$return = false;
		}
		return $return;
	}

	/**
	 * Gets or generates a site id for a specific site
	 *
	 * @returns boolean
	 */
	function getSiteID($app){
		if (!($site_id = $this->query->getSiteQuery($app))) $site_id = $this->query->setSiteQuery($app);
		return $site_id;
	}

	/**
	 * Inserta a set of event log records
	 *
	 * @returns list of event log ids
	 */
	function insertEventlogRecords($ev_logs){
		foreach ($ev_logs->item as $ev){
			if (is_array($ev_logs)){
				$tmp = new stdClass();
				foreach($ev as $key => $val){
					$tmp->$key = $val;
				}
				$ev = $tmp;
			}
			// defaults
			try {
				$app = new stdClass();
				$app->external_id = $ev->external_id;
				$this->insertLogEntry("Called insert event log.");
				$return = $this->query->insertEventLogQuery($ev,$app );
			
			} catch  (Exception $e) {
				$response->error = "service_error";								
				$this->insertLogEntry("Unable to insert event log: " . $e->getMessage());
				error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
				error_log("Unable to insert event log: " . $e->getMessage());
				//error_log(print_r($e,true));
				$return = false;
			}
		}
		return $return;
	}

	/**
	 * Creates a generates a unique login id based off of the customers name
	 *
	 * @returns boolean
	 */
	function generateLogin($app){
		if (is_object($app)) $base_login = strtoupper(substr($app->name_first,0,1).$app->name_last);
		else if (is_string($app)) $base_login = strtoupper($app);
		else $base_login = "GENERIC";
		$last_login_num = $this->query->findUsernameCountQuery($base_login);
		$return = $base_login."_".($last_login_num+1);

		return $return;
	}

	/**
	 * Creates a generates an eight(default) character random password
	 *
	 * @returns boolean
	 */
	function generatePassword($length = 8){
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%^_-=+:";
		$return = crypt_3::Encrypt(trim(substr(str_shuffle($chars),0,$length)));

		return $return;
	}

	/**
	 * Creates a do not loan flag record in the database
	 *
	 * @returns boolean
	 */
	public function insertDoNotLoanFlag($DoNotLoanFlagObj){

		$dnl = $DoNotLoanFlagObj;
		if (is_array($dnl)){
			$tmp = new stdClass();
			foreach($dnl as $key => $val){
				$tmp->$key = $val;
			}
			$dnl = $tmp;
		}
		// defaults
		if (is_null($dnl->other_reason)) $dnl->other_reason = "";
		if (is_null($dnl->explanation)) $dnl->explanation = "";
		
		try {
			$this->insertLogEntry("Called insert do not loan flag.");
			$return = $this->query->insertDoNotLoanFlagQuery($dnl );
			
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to insert do not loan flag: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to insert do not loan flag: " . $e->getMessage());
			//error_log(print_r($e,true));
			$return = false;
		}
		return $return;
	}

	/**
	 * Inserts a new, unpurchased application in the Application Service
	 *  (creates a new application id (authoritative_id) and skips the entry)
	 *
	 * @returns integer application_id
	 */
	public function insertUnpurchasedApp($company_id){

		try {
			$this->insertLogEntry("Called insert unpurchased ap.");
			$return = $this->query->getAuthoritativeQuery($company_id); //$application_id
			
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to insert unpurchased ap: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to insert unpurchased ap: " . $e->getMessage());
			//error_log(print_r($e,true));
			$return = false;
		}
		return $return;		
	}

	/**
	 * Updates the all relavent applicant information tables using the application_id (authorization_id) as key
	 *
	 * @returns boolean
	 */
	function updateApplicant($applicantObj){

		$apl = $applicantObj;
		if (is_array($apl)){
			$tmp = new stdClass();
			foreach ($apl as $key => $val){
				$tmp->$key = $val;
			}
			$apl = $tmp;
		}
		$apl = $this->handleBadObjectChars($apl);

		if (!isset($apl->application_id) || is_null($apl->application_id) || ($apl->application_id < 1)){
			$this->insertLogEntry("Can not update applicant, application id not set.");
			return false;
		}
		if (!isset($apl->modifying_agent_id) || is_null($apl->modifying_agent_id) || ($apl->modifying_agent_id < 1)){
			$this->insertLogEntry("Can not update applicant, modifying agent id not set.");
			return false;
		}
		try {		
			// get aalm key id
			$ap = $this->query->findApplication($apl->application_id);
			if (!$ap){
				$this->insertLogEntry("Can not update applicant, application:".$apl->application_id." not found.");
				return false;
			}
			// update application_version
			if ($apl->application_version && ($apl->application_version >= 0)){
				if (!($ap_ver = $this->updateApplicationVersion($apl->application_id,$apl->application_version))) {
					$this->insertLogEntry("Can not update applicant, update version for:".$apl->application_id." failed.");
					return false;
				}
			}

			$this->insertLogEntry("Called updateApplicant for application: ".$apl->application_id.".");
			// gather original applicant data
			$apl_ref = $this->query->getApplicantQuery($apl->application_id);
			$upd = $this->fillDefault($apl,$apl_ref);

			$result = false;
			// update applicant table
			if ($upd) $result = $result || $this->query->updateApplicantQuery($upd,$ap->application_id);
			
			// update associated tables
			//if ($apl->ssn && !(is_null($apl->ssn)) && ($apl->ssn != 'null'))
			//	$result = (
			//   		$result 
			//		&& (
			//			$this->query->updateSsnQuery($apl->ssn,$apl_ref->ssn_id)	
			//		)
			//	);
			//
			//if ($apl->date_of_birth && !(is_null($apl->date_of_birth)) && ($apl->date_of_birth != 'null')) 
			//	$result = ($result && $this->query->updateDOBQuery($apl->date_of_birth,$apl_ref->date_of_birth_id));
			
			//if ($apl->legal_id_number && !(is_null($apl->legal_id_number)) && ($apl->legal_id_number != 'null'))
			//	$result = ($result && $this->query->updateLegalIDQuery($apl->legal_id_number,$apl_ref->legal_id_number_id));
			
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to update applicant info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to update applicant info: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Updates an applications account password after validating old password
	 *
	 * @returns boolean
	 */
	public function updateApplicantAccount($login, $old_password, $new_password){

		if (!(is_string($login)) || (strlen($login)<4) ||
			!(is_string($old_password)) || (strlen($old_password)<4) ||
			!(is_string($new_password)) || (strlen($new_password)<4)) {
			$this->insertLogEntry("Can not update applicant  account password, data not set.");
			return false;
		}
		try {
			$this->insertLogEntry("Called updateApplicantAccount for login: ".$login.".");
			
			if ($this->query->findByLoginAndPassword($login, $old_password)) {
				$result = $this->query->updateLoginPassword($login, $new_password);
			} else {
				$result = false;
			}

		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to update applicant account password: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to update applicant account password: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}

		return $result;
	}
	
	/**
	 * Updates an applicant table
	 *
	 * @returns boolean
	 */
	public function updateApplication($applicationObj){
		$app = $applicationObj;
		if (is_array($app)){
			$tmp = new stdClass();
			foreach ($app as $key => $val){
				$tmp->$key = $val;
			}
			$app = $tmp;
		}
		$app = $this->handleBadObjectChars($app);
		if (!isset($app->application_id) || is_null($app->application_id) || ($app->application_id < 1)){
			$this->insertLogEntry("Can not update application, application id not set.");
			return false;
		}
		if (!isset($app->modifying_agent_id) || is_null($app->modifying_agent_id) || ($app->modifying_agent_id < 1)){
			$this->insertLogEntry("Can not update application, modifying agent id not set.");
			return false;
		}
		try {
			if (is_numeric($app->date_fund_estimated)) $app->date_fund_estimated = date("Y-m-d",$app->date_fund_estimated);
			if (is_numeric($app->date_first_payment)) $app->date_first_payment = date("Y-m-d",$app->date_first_payment);
			if (is_numeric($app->date_fund_actual)) $app->date_fund_actual = date("Y-m-d",$app->date_fund_actual);
			if (is_numeric($app->date_next_contact)) $app->date_next_contact = date("Y-m-d",$app->date_next_contact);

			// get original
			if (!($ap_ref = $this->query->getApplicationDetailsQuery($app->application_id))){
				$this->insertLogEntry("Can not update application:".$app->application_id." not found.");
				return false;
			}
			// update application_version
			if ($app->application_version && ($app->application_version >= 0)){
				if (!($ap_ver = $this->updateApplicationVersion($ap_ref->application_id,$app->application_version))) {
					$this->insertLogEntry("Can not update applicant, update version for:".$app->application_id." failed.");
					return false;
				}
			}
			$this->insertLogEntry("Called updateApplication for application: ".$app->application_id.".");
			
			$upd = $this->fillDefault($app, $ap_ref);
			if (!isset($upd->payment_total) || empty($upd->payment_total)) $upd->payment_total = 0;
			if (!isset($upd->finance_charge) || empty($upd->finance_charge)) $upd->finance_charge = 0;
			if (!isset($upd->fund_actual) || empty($upd->fund_actual)) $upd->fund_actual = 0;
			if (!isset($upd->fund_requested) || empty($upd->fund_requested)) $upd->fund_requested = 0;

			$result = false;
			// update applicant table
			if ($upd) $result = $result || $this->query->updateApplicationQuery($upd);
			
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to update application: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to update application: " . $e->getMessage());
			//error_log(print_r($e,true));
			$result = false;
		}
		return $result;
	}

	/**
	 * Creates a generates a unique login id based off of the customers name
	 *
	 * @returns boolean
	 */
	function validSsn($ssn){
		$return = false;
		if ((strlen($ssn) == 9) && (is_numeric($ssn))) $return = true;
		return $return;
	}

	/**
	 * Fills an update object with defaults
	 *
	 * @returns boolean
	 */
	function fillDefault($fill,$default){
		$fill_ary = (array)$fill;
		$default_ary = (array)$default;
		
		$trigger = false;
		$upd = new stdClass();
		
		foreach ($default_ary as $key => $val){
			if (isset($fill_ary[$key])) {
				$upd->$key = $fill_ary[$key];
				if ($fill_ary[$key] != $val) $trigger = true;
			} else {
				$upd->$key = $val;
				$trigger = true;
			}
		}
                foreach ($fill_ary as $key => $val){
	                if (!isset($upd->$key)) {
        	                $upd->$key = $fill_ary[$key];
                	        $trigger = true;
                        }
                }

		if (!$trigger) $upd = false;
		return $upd;
	}

	/**
	 * Updates the banking information for the application
	 *
	 * @returns boolean
	 */
	function updateApplicationBankInfo($bankInfoObj){

		$bi = $bankInfoObj;
		if (is_array($bi)){
			$tmp = new stdClass();
			foreach ($bi as $key => $val){
			$tmp->$key = $val;
			}
			$bi = $tmp;
		}

		if (!isset($bi->application_id) || is_null($bi->application_id) || ($bi->application_id < 1)){
			$this->insertLogEntry("Can not update bank info, application id not set.");
			return false;
		}
		if (!isset($bi->modifying_agent_id) || is_null($bi->modifying_agent_id) || ($bi->modifying_agent_id < 1)){
			$this->insertLogEntry("Can not update bank info, modifying agent id not set.");
			return false;
		}
		try {
			// get original
			if (!($bi_ref = $this->query->getBankQuery($bi->application_id))){
				$this->insertLogEntry("Can not update bank info application:".$bi->application_id." not found.");
				return false;
			}
			// update application_version
			if ($bi->application_version && ($bi->application_version >= 0)){
				if (!($ap_ver = $this->updateApplicationVersion($ap->application_id,$bi->application_version))) {
					$this->insertLogEntry("Can not update bank info, update version for:".$bi->application_id." failed.");
					return false;
				}
			}
			$this->insertLogEntry("Called updateApplicationBankInfo for application: ".$bi->application_id.".");
			
			if (
				(
					!isset($bi->is_direct_deposit) || 
					is_null($bi->is_direct_deposit)
				) && (
					!isset($bi->income_direct_deposit) && 
					!is_null($bi->income_direct_deposit)
				)
			) $bi->is_direct_deposit = $bi->income_direct_deposit;
			$bi = $this->handleBadObjectChars($bi);
			$upd = $this->fillDefault($bi,$bi_ref);
			// set defaults to existing data and test to see if update is necessary
			$result = false;
			// update applicant table
			if ($upd) $result = $result || $this->query->updateBankQuery($upd);
			
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to update bank info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to update bank info: " . $e->getMessage());
			//error_log(print_r($e,true));
			return false;
		}
		$bi->return_value = $result;
		return true;
	}

	/**
	 * Updates the application information.
	 *
	 * @returns boolean
	 */
	public function updateApplicationComplete($applicationObj){
		if (is_array($applicationObj)){
			$tmp = new stdClass();
			foreach ($applicationObj as $key => $val){
				$tmp->$key = $val;
			}
			$applicationObj = $tmp;
		}
		$applicationObj = $this->handleBadObjectChars($applicationObj);

		$this->insertLogEntry("Called updateApplicationComplete for application: ".$applicationObj->application_id.".");
		$final = true;
		$success = true;
		
		$result_obj = new stdClass();
		
		$result_obj->updateApplicant = $this->updateApplicant($applicationObj);
		$result = $result_obj->updateApplicant;
		$final = $final && $result;
		$success = $success || $result;
		
		$result_obj->updateApplication = $this->updateApplication($applicationObj);
		$result = $result_obj->updateApplication;
		$final = $final && $result;
		$success = $success || $result;
		
		$result_obj->updateApplicationBankInfo = $this->updateApplicationBankInfo($applicationObj);
		$result = $result_obj->updateApplicationBankInfo;
		$final = $final && $result;
		$success = $success || $result;
		
		$result_obj->updateApplicationStatus = $this->updateApplicationStatus($applicationObj);
		$result = $result_obj->updateApplicationStatus;
		$final = $final && $result;
		$success = $success || $result;

		$contact_fields = array("phone_cell","phone_home","phone_fax","email","phone_work");
		foreach ($contact_fields as $cf) {
			if ($applicationObj->$cf){
				$ci->application_id = $applicationObj->application_id;
				$ci->contact_type = $cf;
				$ci->contact_info_value = $applicationObj->$cf;
				$ci->modifying_agent_id  = $applicationObj->modifying_agent_id;
				$result = $this->query->updateContactInfoQuery($ci);
				$final = $final && $result;
				$success = $success || $result;
			}
		}

		$applicationObj->return_value = !$success;
		return $success;
	}

	/**
	 * Updates the status of the application
	 *
	 * @returns boolean
	 */
	public function updateApplicationStatus($applicationObj){

		$app = $applicationObj;
		if (is_array($app)){
			$tmp = new stdClass();
			foreach ($app as $key => $val){
				$tmp->$key = $val; 
			}
			$app = $tmp;
		}
		if (!isset($app->application_id) || is_null($app->application_id) || ($app->application_id < 1)){
			$this->insertLogEntry("Can not update application status, application id not set.");
			return false;
		}

                if (!isset($app->application_status) || empty($app->application_status)){
                        $this->insertLogEntry("Can not update applicationi status, application status not set.");
                        return false;
		}

		if (!isset($app->modifying_agent_id) || is_null($app->modifying_agent_id) || ($app->modifying_agent_id < 1)){
			$this->insertLogEntry("Can not update application status, modifying agent id not set.");
			return false;
		}
		try {
			// get original
			if (!($ap_ref = $this->query->getApplicationDetailsQuery($app->application_id))){
				$this->insertLogEntry("Can not update application status:".$app->application_id." not found.");
				return false;
			}

			// update application_version
			if ($app->application_version && ($app->application_version >= 0)){
				if (!($ap_ver = $this->updateApplicationVersion($ap_ref->application_id,$app->application_version))) {
					$this->insertLogEntry("Can not update application, update version for:".$app->application_id." failed.");
					return false;
				}
			}

			$this->insertLogEntry("Called updateApplicationStatus for application: ".$app->application_id.".");

			$app = $this->handleBadObjectChars($app);
			$upd = $this->fillDefault($app,$ap_ref);
			$result = false;
			// update status and insert history tables
			if ($upd) {
				$result = $result || $this->query->updateStatusQuery($upd);
				//$result = $result && $this->query->insertStatusHistoryQuery($upd);
			}

		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to update application status: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to update application: " . $e->getMessage());
			//error_log(print_r($e,true));
			return false;
		}
		$app->return_value = $result;
		return true;
	}

	/**
	 * Updated the contact information
	 *
	 * @returns boolean
	 */
	function updateContactInfo($contactInfoObj){

		$ci = $contactInfoObj;
                if (is_array($ci)){
                        $tmp = new stdClass();
                        foreach ($ci as $key => $val){
                                $tmp->$key = $val;
                        }
                        $ci = $tmp;
		}

		if (!isset($ci->application_id) || is_null($ci->application_id) || ($ci->application_id < 1)){
			$this->insertLogEntry("Can not update contact info, application id not set.");
			return false;
		}
		if (!isset($ci->modifying_agent_id) || is_null($ci->modifying_agent_id) || ($ci->modifying_agent_id < 1)){
			$this->insertLogEntry("Can not update contact info, modifying agent id not set.");
			return false;
		}
		try {
			// get original
			if (!($ci_ref = $this->query->getContactInfoQuery($ci->application_id,$ci->contact_type))){
				$this->insertLogEntry("Can not update contact info:".$ci->application_id." - ". $ci->contact_type ." not found.");
				return false;
			}
			// update application_version
			if ($ci->application_version && ($ci->application_version >= 0)){
				if (!($ap_ver = $this->updateApplicationVersion($ci_ref->application_id,$ci->application_version))) {
					$this->insertLogEntry("Can not update contact info, update version for:".$ci->application_id." failed.");
					return false;
				}
			}
			$this->insertLogEntry("Called updateContactInfo for application: ".$ci->application_id." - ". $ci->contact_type.".");
			
			$ci = $this->handleBadObjectChars($ci);
			$upd = $this->fillDefault($ci, $ci_ref);
			// set defaults to existing data and test to see if update is necessary

			$result = false;
			// update status and insert history tables
			if ($upd) {
				$result = $result || $this->query->updateContactInfoQuery($upd);
			}
			
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to update contact info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to update contact info: " . $e->getMessage());
			//error_log(print_r($e,true));
			return false;
		}
		$ci->return_value = $result;
		return true;
	}

	/**
	 * Updated the employment information
	 *
	 * @returns boolean
	 */
	function updateEmploymentInfo($employmentInfoObj){

		$ei = $employmentInfoObj;
                if (is_array($ei)){
			$tmp = new stdClass();
			foreach ($ei as $key => $val){
				$tmp->$key = $val;
                        }
                        $ei = $tmp;
                }

		if (!isset($ei->application_id) || is_null($ei->application_id) || ($ei->application_id < 1)){
			$this->insertLogEntry("Can not update employment info, application id not set.");
			return false;
		}
		if (!isset($ei->modifying_agent_id) || is_null($ei->modifying_agent_id) || ($ei->modifying_agent_id < 1)){
			$this->insertLogEntry("Can not update employment info, modifying agent id not set.");
			return false;
		}
		try {
			// get original
			if (!($ei_ref = $this->query->getEmploymentDetailsQuery($ei->application_id))){
				$this->insertLogEntry("Can not update employment info:".$ei->application_id." not found.");
				return false;
			}
			// update application_version
			if ($ei->application_version && ($ei->application_version >= 0)){
				if (!($ap_ver = $this->updateApplicationVersion($ei_ref->application_id,$ei->application_version))) {
					$this->insertLogEntry("Can not update employment info, update version for:".$ei->application_id." failed.");
					return false;
				}
			}
			$this->insertLogEntry("Called updateEmploymentInfo for application: ".$ei->application_id.".");
			
			$ei = $this->handleBadObjectChars($ei);
			$upd = $this->fillDefault($ei, $ei_ref);

			$result = false;
			// update status and insert history tables
			if ($upd) {
				$result = $result || $this->query->updateEmploymentInfoQuery($upd);
			}

		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to update employment info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to update employment info: " . $e->getMessage());
			//error_log(print_r($e,true));
			return false;
		}
		$ei->return_value = $result;
		return true;
	}

	/**
	 * Updates the paydate information
	 *
	 * @returns boolean
	 */
	function updatePaydateInfo($paydateInfoObj){

		$pi = $paydateInfoObj;
                if (is_array($pi)){
                        $tmp = new stdClass();
                        foreach ($pi as $key => $val){
                                $tmp->$key = $val;
                        }
                        $pi = $tmp;
                }

		if (!isset($pi->application_id) || is_null($pi->application_id) || ($pi->application_id < 1)){
			$this->insertLogEntry("Can not update employment info, application id not set.");
			return false;
		}
		if (!isset($pi->modifying_agent_id) || is_null($pi->modifying_agent_id) || ($pi->modifying_agent_id < 1)){
			$this->insertLogEntry("Can not update paydate info, modifying agent id not set.");
			return false;
		}

		try {
			// get original
			if (!($pi_ref = $this->query->getPaydateDetailsQuery($pi->application_id))){  // paydate info comes with employment
				$this->insertLogEntry("Can not update paydate info:".$pi->application_id." not found.");
				return false;
			}
			// update application_version
			if ($pi->application_version && ($pi->application_version >= 0)){
				if (!($ap_ver = $this->updateApplicationVersion($pi_ref->application_id,$pi->application_version))) {
					$this->insertLogEntry("Can not update pay date info, update version for:".$pi->application_id." failed.");
					return false;
				}
			}
			$this->insertLogEntry("Called updatePaydateInfo for application: ".$pi->application_id.".");
			
			$pi = $this->handleBadObjectChars($pi);
			$upd = $this->fillDefault($pi, $pi_ref);

			$result = false;
			// update status and insert history tables
			if ($upd) {
				$upd->paydate_info_id = $pi_ref->paydate_info_id;
				if (!isset($upd->day_of_month_1) || !is_numeric($upd->day_of_month_1)) $upd->day_of_month_1 = 0;
				if (!isset($upd->day_of_month_2) || !is_numeric($upd->day_of_month_2)) $upd->day_of_month_2 = 0;
				if (!isset($upd->week_1) || !is_numeric($upd->week_1)) $upd->week_1 = 0;
				if (!isset($upd->week_2) || !is_numeric($upd->week_2)) $upd->week_2 = 0;

				$result = $result || $this->query->updatePaydateInfoQuery($upd);
			}
	
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to update paydate info: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to update paydate info: " . $e->getMessage());
			//error_log(print_r($e,true));
			return false;
		}
		$pi->return_value = $result;
		return true;
	}

	/**
	 * Updates a personal reference
	 *
	 * @returns boolean
	 */
	function updatePersonalReference($referenceObj){

		$rf = $referenceObj;
                if (is_array($rf)){
	                $tmp = new stdClass();
                        foreach ($rf as $key => $val){
		                $tmp->$key = $val;
		        }
		        $rf = $tmp;
		}

		if (!isset($rf->application_id) || is_null($rf->application_id) || ($rf->application_id < 1)){
			$this->insertLogEntry("Can not update personal reference, application id not set.");
			return false;
		}
		if (!isset($rf->modifying_agent_id) || is_null($rf->modifying_agent_id) || ($rf->modifying_agent_id < 1)){
			$this->insertLogEntry("Can not update personal reference, modifying agent id not set.");
			return false;
		}
		try {
			if (!($app = $this->query->getApplicationDetailsQuery($rf->application_id))){
				$this->insertLogEntry("Can not update personal reference, application:".$rf->application_id." not found.");
				return false;
			}
			// update application_version
			if ($rf->application_version && ($rf->application_version >= 0)){
				if (!($ap_ver = $this->updateApplicationVersion($app->application_id,$rf->application_version))) {
					$this->insertLogEntry("Can not update personal reference, update version for:".$rf->application_id." failed.");
					return false;
				}
			}
			// look for original
			if ($rf->personal_reference_id && ($rf_ref = $this->query->getPersonalReferencesQuery($rf->personal_reference_id))){
				$this->insertLogEntry("Called updatePersonalReference for application: ".$rf->application_id.", personal reference: ".$rf->personal_reference_id.".");
			
				$rf = $this->handleBadObjectChars($rf);
				$upd = $this->fillDefault($rf, $rf_ref);
	
				$result = false;
				// update status and insert history tables
				if ($upd) {
					$result = $result || $this->query->updatePersonalReferenceQuery($upd);
				}
			} else {
				$this->insertLogEntry("Called updatePersonalReference for application: ".$rf->application_id.".");
				// set defaults
				if (is_null($rf->ok_to_contact) || !(strlen(trim($rf->ok_to_contact)))) $rf->ok_to_contact = 'do_not_contact';
				if (is_null($rf->verified) || !(strlen(trim($rf->verified)))) $rf->verified = 'unverified';
				
				$result = $this->query->insertPersonalReferenceQuery($rf,$app);
			}
	
		} catch  (Exception $e) {
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to update personal reference: " . $e->getMessage());
			error_log(__FILE__.' :: '.__METHOD__.' :: '.__LINE__);
			error_log("Unable to update personal reference: " . $e->getMessage());
			//error_log(print_r($e,true));
			return false;
		}
		$rf->return_value = $result;
		return true;
	}


	/**
	 * Updates a personal reference
	 *
	 * @returns boolean
	 */
	function updateApplicationVersion($application_id,$version){
		
		$ap = new stdClass();
		$ap->application_id = $application_id;
		// look for original
		if (!($ap_ver = $this->query->getApplicationVersionQuery($ap))){
			$this->insertLogEntry("Can not update application version, table entry for:".$application_id." not found.");
			return false;
		}
		// test if its is an update
		if ($ap_ver->version > $version){
			$this->insertLogEntry("Application version table entry is older than for:".$application_id);
			return false;
		} else {
		// update application_version
			if ($ap_ver->version == $version) return true;
			$result = $this->query->updateApplicationVersionQuery($application_id,$ap_ver);
		}
		return $result;
	}

	/**
	 * Updates an ssn and sperates the applicant from the other aps
	 *
	 * @returns boolean
	 */
	function splitApplicants($args){
		if (is_array($args)){
			$tmp = new stdClass();
			foreach ($args as $key => $val){
				$tmp->$key = $val;
			}
			$args = $tmp;
		}

		if (!($args->modifying_agent_id>0)){
			$this->insertLogEntry("Can not split application off.  Invalid modifying agent id.");
			return false;
		}
		if (!(is_array($args->application_ids) || count($args->application_ids<1))){
			$this->insertLogEntry("Can not split application off.  No application ids were included id.");
			return false;
		}
		if (empty($args->base_login)){
			$this->insertLogEntry("Can not split application off.  The base_login is empty.");
			return false;
		}
		if (!$this->validSsn($args->ssn)){
			$this->insertLogEntry("Can not split application off.  Invalid ssn.");
			return false;
		}
		/* create a new applicant account */
		$args->login_id = $this->generateLogin($args->base_login);
		$args->login = $this->generateLogin($args->base_login);
		$args->password = $this->generatePassword();
		$args->customer_id = $this->query->insertApplicantAccountQuery($args);
		$args->applicant_account_id = $args->customer_id;
		$args->application_id = $args->application_ids[0];

		/* assign all of the applications to the new applicant account */
		$args->num_changed = $this->query->setApplicantAccount($args->application_ids,$args->applicant_account_id);
		
		$return = $args;

		return $return;
	}

	/**
	 * Updates an ssn and sperates the applicant from the other aps
	 *
	 * @returns boolean
	 */
	function mergeApplicants($args){
		if (is_array($args)){
			$tmp = new stdClass();
			foreach ($args as $key => $val){
				$tmp->$key = $val;
			}
			$args = $tmp;
		}

		if (!($args->modifying_agent_id>0)){
			$this->insertLogEntry("Can not merge applicant accounts.  Invalid modifying agent id.");
			return false;
		}
		if (!(is_array($args->application_ids) || count($args->application_ids)<1)){
			$this->insertLogEntry("Can not merge applicant accounts.  No application ids were included id.");
			return false;
		}
		if (empty($args->applicant_account_id)){
			$this->insertLogEntry("Can not merge applicant accounts.  Invalid customer id.");
			return false;
		}
		
		/* assign all of the applications to the new applicant account */
		$args->num_changed = $this->query->setApplicantAccount($args->application_ids,$args->applicant_account_id);
		
		$return = $true;

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
		return FALSE;
	}
	/**
	 * Resolve unhandled function calls to the service to the service client
	 *
	 * @param boolean $enabled - if aggregate service calls are enabled
	 * @return void
	 */
	public function setAggregateEnabled($enabled)
	{
	        return FALSE;
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
