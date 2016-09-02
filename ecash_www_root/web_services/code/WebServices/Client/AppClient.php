<?php
/**
 * Base class for application service client calls
 * 
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package WebService
 */
abstract class WebServices_Client_AppClient extends WebServices_Client
{
	/**
	 * The logged in agent
	 *
	 * @var Integer
	 */
	protected $agent_id;

	/**
	 * webservice cache object
	 *
	 * @var WebService_Cache
	 */
	protected $cache;

	/**
	 * The maximum size of the application version cache
	 */
	const APPLICATION_VERSION_CACHE_LIMIT = 100;
	
	/*
	 *  Employment Const
	 */
	const EMPLOYMENT = 'EMPLOYMENT';

	/**
	 * Applicant Info
	 */
	const APPLICANT_ACCOUNT = 'APPLICANT_ACCOUNT';

	/**
	 * Application Info
	 */
	const APPLICATION_REFERENCES = 'APPLICATION_REFERENCES';

	/**
	 * Bank Info
	 */
	const BANK_INFO = 'BANK_INFO';

	/**
	 * Applicant Info
	 */
	const APPLICANT_INFO = 'APPLICANT_INFO';
	/**
	 * Application Info
	 */
	const APPLICATION_INFO = 'APPLICATION_INFO';
	/**
	 * Do not loan
	 */
	const DO_NOT_LOAN = 'DO_NOT_LOAN';
	/**
	 * Do not loan audit
	 */
	const DO_NOT_LOAN_AUDIT = 'DO_NOT_LOAN_AUDIT';
	/**
	 * Audit info
	 */
	const AUDIT_INFO = 'AUDIT_INFO';
	/**
	 * Has Regulatory flags
	 */
	const HAS_REGULATORY_FLAGS = 'HAS_REGULATORY_FLAGS';
	/**
	 * DO_NOT_LOAN_ALL flags
	 */
	const DO_NOT_LOAN_ALL = 'DO_NOT_LOAN_ALL';
	/**
	 * Do Not Loan Override All
	 */
	const DO_NOT_LOAN_OVERRIDE_ALL = 'DO_NOT_LOAN_OVERRIDE_ALL';
	/**
	 * Regulatory flags
	 */
	const REGULATORY_FLAGS = 'REGULATORY_FLAGS';
	/**
	 * Contact Info
	 */
	const CONTACT_INFO = 'CONTACT_INFO';
	/**
	 * React Affiliation
	 */
	const REACT_AFFILIATION = 'REACT_AFFILIATION';
	/**
	 * React Affiliation Childern
	 */
	const REACT_AFFILIATION_CHILDERN = 'REACT_AFFILIATION_CHILDERN';
	/**
	 * Full App
	 */
	const FULL_APP = 'FULL_APP';

	/**
	 * Campaign Info
	 */
	const CAMPAIGN_INFO = 'CAMPAIGN_INFO';

	/**
	 * Gets all info for an application_id
	 * 
	 * @param integer $application_id
	 * @return string|FALSE
	 */
	public function fetchAll($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id) || !is_numeric($application_id))
		{
			return FALSE;
		}
		if ($this->cache->hasCache(self::FULL_APP, $application_id))
		{
			return $this->cache->getCache(self::FULL_APP, $application_id);
		}

		$retval = $this->getService()->fetchAll($application_id);
		if (is_object($retval) && count(get_object_vars($retval)) > 0)	
		{
			/**
			 * Add to Application Version Cache
			 */
			$this->addApplicationVersion($application_id, $retval->applicant_info->application_version);

			if(! empty($retval->employment_info))
			{
				if(! empty($retval->employment_info->date_hire))
				{
					$retval->employment_info->date_hire = date('Y-m-d H:i:s', strtotime($retval->employment_info->date_hire));
				}
				$this->cache->storeCache(self::EMPLOYMENT, $application_id, $retval->employment_info);
			}

			$this->cache->storeCache(self::REACT_AFFILIATION, $application_id, $retval->react_affiliation);
			$this->cache->storeCache(self::CONTACT_INFO, $application_id, empty($retval->contact_info) ? NULL : $retval->contact_info);
			$this->cache->storeCache(self::APPLICANT_INFO, $application_id, $retval->applicant_info);
			
			$retval->application->fundRequested = empty($retval->application->fundRequested) ? NULL : round($retval->application->fundRequested, 2);
			$retval->application->fundQualified = round($retval->application->fundQualified, 2);
			$retval->application->fundActual = empty($retval->application->fundActual) ? NULL : round($retval->application->fundActual, 2);
			$this->cache->storeCache(self::APPLICATION_INFO, $application_id, $retval->application);
			
			$this->cache->storeCache(self::BANK_INFO, $application_id, $retval->bank_info);
			$this->cache->storeCache(self::APPLICATION_REFERENCES, $application_id, empty($retval->personal_references) ? NULL : $retval->personal_references);
			$this->cache->storeCache(self::APPLICANT_ACCOUNT, $application_id, $retval->applicant_account);
			$this->cache->storeCache(self::REGULATORY_FLAGS, $application_id, $retval->regulatory_flag);
			$this->cache->storeCache(self::AUDIT_INFO, $application_id, empty($retval->audit_info) ? NULL : $retval->audit_info);
			$this->cache->storeCache(self::DO_NOT_LOAN_AUDIT, $retval->applicant_info->ssn, empty($retval->do_not_loan_audit) ? NULL : $retval->do_not_loan_audit);
			$this->cache->storeCache(self::DO_NOT_LOAN, $retval->applicant_info->ssn, $retval->do_not_loan);
			$this->cache->storeCache(self::DO_NOT_LOAN_ALL, $retval->applicant_info->ssn, empty($retval->do_not_loan_all) ? NULL : $retval->do_not_loan_all);
if (!empty($retval->do_not_loan_all)) error_log(print_r($retval->do_not_loan_all,true));
			$this->cache->storeCache(self::DO_NOT_LOAN_OVERRIDE_ALL, $retval->applicant_info->ssn, empty($retval->do_not_loan_override_all) ? NULL : $retval->do_not_loan_override_all);
			$this->cache->storeCache(self::HAS_REGULATORY_FLAGS, $application_id, empty($retval->has_regulatory_flag) ? NULL : $retval->has_regulatory_flag);

			if(! empty($retval->campaign_info))
			{
				$campaign_info = (is_array($retval->campaign_info)) ? array_values($retval->campaign_info) : array($retval->campaign_info);
				$this->cache->storeCache(self::CAMPAIGN_INFO, $application_id, $campaign_info);
			}

			$this->cache->storeCache(self::FULL_APP, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Application record returned for application_id: ' . $application_id);
		}
		
		return $retval;
	}
	/**
	 * Constructor for base appclient object
	 *
	 * @param Applog $log
	 * @param ApplicationService $app_service
	 * @param integer $agent_id
	 * @param WebServices_ICache $cache
	 * @return void
	 */
	public function __construct(Applog $log, ECash_ApplicationService_API $app_service, $agent_id, WebServices_ICache $cache)
	{
		parent::__construct($log, $app_service);
		$this->agent_id = $agent_id;
		$this->cache = $cache;
	}

	public function splitCustomer(array $applications, $login_prefix, $ssn)
 	{
		$return = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $return;

		$args = array();
		$args['ssn'] = $ssn;
		$args['base_login'] = $login_prefix;
		$args['application_ids'] = $applications;
		$args['modifying_agent_id'] = $this->agent_id;
		$return = $this->getService()->splitApplicants($args);
		$return->password = crypt_3::Encrypt($return->password);
		
		return $return;
 	}
	
 	/**
 	 * @param array $args - the data to send
 	 * @return int|FALSE
 	 */
	public function mergeCustomer(array $applications, $customer_id, $agent_id)
	{
		$return = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $return;
		$args = array();
		$args['applicant_account_id'] = $customer_id;
		$args['application_ids'] = $applications;
		$args['modifying_agent_id'] = $agent_id;
		$return = $this->getService()->mergeApplicants($args);
		
		return $return;
	}

	/**
	 * Inserts a contact info record into the app service
	 * @param array $args
	 * @return Integer
	 */
	public function insertContactInfo($args)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->insertContactInfo($args);

		return $retval;
	}

	/**
	 * Inserts eventlog records into the application service
	 * @param array $events
	 * @return Boolean
	 */
	public function insertEventlogRecords(array $events)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->insertEventlogRecords($events);
		
		return $retval;
	}

	/**
	 * Update applicant account information in the webservice
	 *
	 * @param string $login
	 * @param string $old_password - pre encrypted
	 * @param string $new_password - pre encrypted
	 * @return bool
	 */
	public function updateApplicantAccount($login, $old_password, $new_password)
	{
		$this->log->Write('Updating LOGIN:'.$login. ' OLD:'.$old_password.' NEW:'.$new_password);
	
		$retval = FALSE;
		if ($this->getService()->isEnabled(__FUNCTION__))
		{
			$retval = $this->getService()->updateApplicantAccount($login, $old_password, $new_password);
			if (!$retval)
			{
				$this->log->Write('Failed to update LOGIN:'.$login);
			}
		}

		$this->log->Write('Returning['.$retval.'] LOGIN:'.$login);
		$this->cache->removeCache(self::APPLICANT_ACCOUNT, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}

	/**
	 * Make a bulk price point update
	 * 
	 * @param array $data
	 * @return mixed
	 */
	public function bulkUpdateApplicationPricePoint($data)
	{
		$retval = FALSE;

		if (!$this->getService()->isEnabled(__FUNCTION__) || !is_array($data) || empty($data))
		{
			return $retval;
		}

		$data['modifying_agent_id'] = $this->agent_id;
		$retval = $this->getService()->bulkUpdateApplicationPricePoint($data);

		return $retval;
	}

	/**
	 * Update multiple applications to a status
	 *
	 * @param array $application_ids
	 * @param int $agent_id
	 * @param string $new_status
	 * @return mixed
	 */
	public function bulkUpdateApplicationStatus($application_ids, $agent_id, $new_status)
	{
		$retval = FALSE;

		if (!$this->getService()->isEnabled(__FUNCTION__)
			|| !is_array($application_ids)
			|| empty($application_ids))
		{
			return $retval;
		}

		$args = array();
		foreach ($application_ids as $application_id)
		{
			$args[] = array(
				'application_id' => $application_id,
				'modifying_agent_id' => $agent_id,
				'application_status' => $new_status
			);
		}

		$retval = $this->getService()->bulkUpdateApplicationStatus($args);

		return $retval;
	}

	/**
	 * Updates the application status in the application service
	 * 
	 * @param integer $application_id
	 * @param integer $agent_id
	 * @param string $new_status
	 * @return string|FALSE
	 */
	public function updateApplicationStatus($application_id, $agent_id, $new_status)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$args = array(
			'application_id' => $application_id,
			'modifying_agent_id' => $agent_id,
			'application_status' => $new_status
		);

		$retval = $this->getService()->updateApplicationStatus($args);

		return $retval;
	}

	/**
	 * inserts or updates a regulatory flag
	 * @param int $application_id
	 * @param int $agent_id
	 * @param boolean $status
	 * @param string $loan_action
	 * @param string $loan_action_section
	 * @return boolean
	 */
	public function updateRegulatoryFlag($application_id, $agent_id, $status, $loan_action, $loan_action_section)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$args = array(
			'application_id' => $application_id,
			'active_status' => $status,
			'loan_action_name' => $loan_action,
			'loan_action_section' => $loan_action_section,
			'modifying_agent_id' => $agent_id
		);

		$retval= $this->getService()->updateRegulatoryFlag($args);

		$this->cache->removeCache(self::REGULATORY_FLAGS, $application_id);
		$this->cache->removeCache(self::HAS_REGULATORY_FLAGS, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}
	
	/**
	 * Gets regulatory flags for an application_id
	 * 
	 * @param integer $application_id
	 * @return string|FALSE
	 */
	public function getRegulatoryFlag($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id) || !is_numeric($application_id))
		{
			return FALSE;
		}
				//check if application exists in cache for call
		if ($this->cache->hasCache(self::REGULATORY_FLAGS, $application_id))
		{
			return $this->cache->getCache(self::REGULATORY_FLAGS, $application_id);
		}

		$retval = $this->getService()->getRegulatoryFlag($application_id);
		if (is_object($retval) && count(get_object_vars($retval)) > 0)	
		{
			$this->cache->storeCache(self::REGULATORY_FLAGS, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Regulatory info record returned for application_id: ' . $application_id);
		}
		
		return $retval;
	}
	
	/**
	 * returns true or false if app_id has a regulatory flag
	 * 
	 * @param integer $application_id
	 * @return bool
	 */
	public function hasRegulatoryFlag($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id) || !is_numeric($application_id))
		{
			return FALSE;
		}
		//check if application exists in cache for call
		$cached_value = $this->cache->getCache(self::HAS_REGULATORY_FLAGS, $application_id);
		if ($cached_value !== NULL)
		{
			return $cached_value;
		}		
		$retval = $this->getService()->hasRegulatoryFlag($application_id);
		if ($retval != NULL)	
		{
			$this->cache->storeCache(self::HAS_REGULATORY_FLAGS, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Regulatory info record returned for application_id: ' . $application_id);
		}
		
		return $retval;
	}

	/**
	 * Returns the most recent application status for the application
	 * 
	 * @param integer $application_id
	 * @return string|FALSE
	 */
	public function getApplicationStatus($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$args = array('application_id' => $application_id);
		
		$retval = $this->getService()->getApplicationStatus($args);

		return $retval;
	}

	/**
	 * Returns the application status history in an array with the following format:
	 *	array(array('dateCreated' => date, 'applicationStatus' => status))
	 * 
	 * @param int $application_id
	 * @return array|NULL
	 */
	public function getApplicationStatusHistory($application_id)
	{
		$retval = NULL;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;
		
		$args = array('application_id' => $application_id);

		$retval = $this->getService()->getApplicationStatusHistory($args);
		if(is_object($retval)){
			if(! is_array($retval->item)) {
				$retval->item = array($retval->item);
			}
		} else if (is_array($retval)) {
			$temp = new stdClass();
			$temp->item = $retval;
			$retval = $temp;
		}
		return $retval;
	}

	/**
	 * Fields relevent to the banking information
	 *
	 * @return array
	 */
	protected function getFieldsBankInfo()
	{
		return array(
			'application_id',
			'bank_account_type',
			'bank_name',
			'bank_aba',
			'bank_account',
			'banking_start_date',
			'modifying_agent_id',
			'income_direct_deposit'
		);
	}

	/**
	 * Gets fields that are valid to be passed as args to customer information calls
	 *
	 * @return array
	 */
	protected function getFieldsApplicant()
	{
		$fields = array(
			'dob',
			'age',
			'ssn',
			'ssn_last_four',
			'legal_id_number',
			'legal_id_state',
			'legal_id_type',
			'name_title',	
			'name_first',
			'name_middle',
			'name_last',
			'name_suffix',
			'name_nick',
			'street',
			'unit',
			'city',
			'state',
			'zip_code',
			'county',
			'residence_start_date',
			'tenancy_type',
			'modifying_agent_id'
		);

		return $fields;
	}
	/**
	 * Gets fields that are valid to be passed as args to application information calls
	 *
	 * @return array
	 */
	protected function getFieldsApplication()
	{

		$fields = array(
			'apr',
			'cfe_ruleset_id',
			'date_first_payment',
			'date_fund_actual',
			'date_fund_estimated',
			'date_next_contact',
			'finance_charge',
			'fund_actual',	
			'fund_qualified',
			'fund_requested',
			'is_watched',
			'payment_total',
			'price_point',
			'pwadvid',
			'customer_id',
			'rule_set_id',
			'call_time_pref',
			'contact_method_pref',
			'marketing_contact_pref',
			'modifying_agent_id'
		);

		return $fields;
	}
	/**
	 * Filters arg fields for application information calls
	 *
	 * @param array $args - Arguments to be filtered
	 * @return array
	 */
	protected function filterFieldsApplication($args)
	{
		$fields = $this->getFieldsApplication();

		$args = array_intersect_key($args, array_flip($fields));

		return $args;
	}

	/**
	 * Filters arg fields for customer information calls
	 *
	 * @param array $args - Arguments to be filtered
	 * @return array
	 */
	protected function filterFieldsApplicant($args)
	{
		$fields = $this->getFieldsApplicant();
	
		// Attempt to grab ssn_last_four if it isn't set and ssn is
		if (!empty($args['ssn']) 
				&& empty($args['ssn_last_four'])
				&& is_numeric($args['ssn']))
		{
			$args['ssn_last_four'] = substr($args['ssn'], 5);
		}

		$args = array_intersect_key($args, array_flip($fields));

		return $args;
	}
	/**
	 * Updates customer information in the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @param array $args - Arguments to be passed to the client
	 * @return bool - Whether the update was performed or not
	 */
	public function updateApplicant($application_id, $args)
	{
		$args = $this->filterFieldsApplicant($args);
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__) || !is_array($args) || empty($args))
		{
			return $retval;
		}

		if (!empty($args))
		{
			$args['application_id'] = $application_id;
			$args['modifying_agent_id'] = $this->agent_id;
			$retval = $this->getService()->updateApplicant($args);
		}
		$this->cache->removeCache(self::APPLICANT_INFO, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}

	/**
	 * Updates application information in the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @param array $args - Arguments to be passed to the client
	 * @return bool - Whether the update was performed or not
	 */
	public function updateApplication($application_id, $args)
	{
		$args = $this->filterFieldsApplication($args);
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__) || !is_array($args) || empty($args))
		{
			return $retval;
		}

		if (!empty($args))
		{
			$args['application_id'] = $application_id;
			$args['modifying_agent_id'] = $this->agent_id;

			/**
			 *  convert the dates
			 *
			 * @todo Take timezone differences into consideration. It is just set to noon now
			 * because it was the quick and easy solution. Please do not post this to dailywtf
			 */
			if (!empty($args['date_first_payment']))
			{
				if (is_numeric($args['date_first_payment'])) {
					$args['date_first_payment'] = date('Y-m-d', $args['date_first_payment']);
				}
				$args['date_first_payment'] = strtotime($args['date_first_payment']." 12:00:00");
			}
			if (!empty($args['date_fund_actual']))
			{
				if (is_numeric($args['date_fund_actual'])) {
					$args['date_fund_actual'] = date('Y-m-d', $args['date_fund_actual']);
				}
				$args['date_fund_actual'] = strtotime($args['date_fund_actual']." 12:00:00");
			}
			if (!empty($args['date_fund_estimated']))
			{
				if (is_numeric($args['date_fund_estimated'])) {
					$args['date_fund_estimated'] = date('Y-m-d', $args['date_fund_estimated']);
				}
				$args['date_fund_estimated'] = strtotime($args['date_fund_estimated']." 12:00:00");
			}
			if (!empty($args['date_next_contact']))
			{
				if (is_numeric($args['date_next_contact'])) {
					$args['date_next_contact'] = date('Y-m-d', $args['date_next_contact']);
				}
				$args['date_next_contact'] = strtotime($args['date_next_contact']." 12:00:00");
			}

			     if(!empty($args['is_watched']))
                	     {
                        	    if($args['is_watched'] == 'yes')
        	                    {
	                                  $args['is_watched'] = true;
                	            }
                        	    else
                        	    {
                	                  $args['is_watched'] = false;
	                            }
        	             }


			$retval = $this->getService()->updateApplication($args);
		}
		$this->cache->removeCache(self::APPLICATION_INFO, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}

	/**
	 * Updates FULL application information in the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @param array $args - Arguments to be passed to the client
	 * @return bool - Whether the update was performed or not
	 */
	public function updateApplicationComplete($application_id, $args)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__) || !is_array($args) || empty($args))
		{
			return $retval;
		}

		if (!empty($args))
		{
			$args['application_id'] = $application_id;
			$args['modifying_agent_id'] = $this->agent_id;

			/**
			 *  convert the dates
			 *
			 * @todo Take timezone differences into consideration. It is just set to noon now
			 * because it was the quick and easy solution. Please do not post this to dailywtf
			 */
			if (!empty($args['date_first_payment']))
			{
				if (is_numeric($args['date_first_payment'])) {
					$args['date_first_payment'] = date('Y-m-d', $args['date_first_payment']);
				}
				$args['date_first_payment'] = strtotime($args['date_first_payment']." 12:00:00");
			}
			if (!empty($args['date_fund_actual']))
			{
				if (is_numeric($args['date_fund_actual'])) {
					$args['date_fund_actual'] = date('Y-m-d', $args['date_fund_actual']);
				}
				$args['date_fund_actual'] = strtotime($args['date_fund_actual']." 12:00:00");
			}
			if (!empty($args['date_fund_estimated']))
			{
				if (is_numeric($args['date_fund_estimated'])) {
					$args['date_fund_estimated'] = date('Y-m-d', $args['date_fund_estimated']);
				}
				$args['date_fund_estimated'] = strtotime($args['date_fund_estimated']." 12:00:00");
			}
			if (!empty($args['date_next_contact']))
			{
				if (is_numeric($args['date_next_contact'])) {
					$args['date_next_contact'] = date('Y-m-d', $args['date_next_contact']);
				}
				$args['date_next_contact'] = strtotime($args['date_next_contact']." 12:00:00");
			}

			if ($args['last_paydate'] == "0000-00-00") {
				unset($args['last_paydate']);
			}
			if (!empty($args['last_paydate']))
			{
				if (is_numeric($args['last_paydate'])) {
					$args['last_paydate'] = date('Y-m-d', $args['last_paydate']);
				}
				$args['last_paydate'] = strtotime($args['last_paydate']." 12:00:00");
			}
			if (!empty($args['income_direct_deposit']))
			{
				$args['is_direct_deposit'] = $args['income_direct_deposit'] == "yes" ? TRUE : FALSE;
			}

			// Don't think we need to update the date_created so remove it to prevent marshalling error
			unset($args['date_created']);

			$retval = $this->getService()->updateApplicationComplete($args);
		}

		//@todo we need a remove all cache type of method.
		$this->cache->removeCache(self::CONTACT_INFO, $application_id);
		$this->cache->removeCache(self::EMPLOYMENT, $application_id);
		$this->cache->removeCache(self::APPLICANT_INFO, $application_id);
		$this->cache->removeCache(self::APPLICATION_INFO, $application_id);
		$this->cache->removeCache(self::BANK_INFO, $application_id);
		$this->cache->removeCache(self::APPLICATION_REFERENCES, $application_id);
		$this->cache->removeCache(self::APPLICANT_ACCOUNT, $application_id);
		$this->cache->removeCache(self::REGULATORY_FLAGS, $application_id);
		$this->cache->removeCache(self::AUDIT_INFO, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}

	/**
	 * Updates bank info
	 * 
	 * @param int $application_id
	 * @param mixed $args
	 * @return boolean
	 */
	public function updateBankInfo($application_id, $args)
	{
		/* filter the arguements */
		$args = array_intersect_key($args, array_flip($this->getFieldsBankInfo()));

		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__) || !is_array($args) || empty($args))
		{
			return $retval;
		}

		$args['application_id'] = $application_id;
		$args['modifying_agent_id'] = $this->agent_id;
		if (!empty($args['income_direct_deposit']))
		{
			$args['is_direct_deposit'] = $args['income_direct_deposit'] == "yes" ? TRUE : FALSE;
		}

		$i = $this->getService()->updateApplicationBankInfo($args);
		$retval = ($i > 0);

		$this->cache->removeCache(self::BANK_INFO, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}

	/**
	 * Update personal references in the application service
	 *
	 * @param int $application_id
	 * @param int $personal_reference_id
	 * @param int $company_id
	 * @param string $name_full
	 * @param string $phone_home
	 * @param string $relationship
	 * @param string $ok_to_contact
	 * @param string $verified
	 * @return int|book - Either the personal reference id that was inserted or FALSE for fail
	 */
	public function updatePersonalReference(
		$application_id,
		$personal_reference_id,
		$company_id,
		$name_full,
		$phone_home,
		$relationship,
		$ok_to_contact,
		$verified
	)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)
			|| !$this->checkApplicationId($application_id))
		{
			return $retval;
		}

		$args = array(
			'application_id' => $application_id,
			'personal_reference_id' => $personal_reference_id,
			'modifying_agent_id' => $this->agent_id,
			'company_id' => $company_id,
			'name_full' => $name_full,
			'phone_home' => $phone_home,
			'relationship' => $relationship,
			'ok_to_contact' => $ok_to_contact,
			'verified' => $verified
		);

		$retval = $this->getService()->updatePersonalReference($args);

		//remove value from cache since it has been updated
		$this->cache->removeCache(self::APPLICATION_REFERENCES, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}

	/**
	 * Adds a Campaign record to the application
	 * @param array $args
	 * @return FALSE|Integer
	 */
	public function addCampaignInfo($args)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__) || !is_array($args) || empty($args))
		{
			return $retval;
		}

		if (!empty($args))
		{
			$retval = $this->getService()->addCampaignInfo($args);
		}

		return $retval;
		
	}

	/**
	 * Inserts personal references in the application service
	 *
	 * @param int $application_id
	 * @param int $company_id
	 * @param string $name_full
	 * @param string $phone_home
	 * @param string $relationship
	 * @param string $ok_to_contact
	 * @param string $verified
	 * @return int|book - Either the personal reference id that was inserted or FALSE for fail
	 */
	public function addPersonalReference(
		$application_id,
		$company_id,
		$name_full,
		$phone_home,
		$relationship,
		$ok_to_contact,
		$verified)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__)
			|| !$this->checkApplicationId($application_id))
		{
			return $retval;
		}

		$args = array(
			'application_id' => $application_id,
			'modifying_agent_id' => $this->agent_id,
			'company_id' => $company_id,
			'name_full' => $name_full,
			'phone_home' => $phone_home,
			'relationship' => $relationship,
			'ok_to_contact' => $ok_to_contact,
			'verified' => $verified
		);

		$retval = $this->getService()->addPersonalReferences(array($args));

		//remove value from cache since it has been updated
		$this->cache->removeCache(self::APPLICATION_REFERENCES, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}

	/**
	 * Gets fields that are valid to be passed as args to contact information calls
	 *
	 * @return array
	 */
	protected function getFieldsContactInfo()
	{
		$fields = array(
			'phone_home', 
			'phone_cell', 
			'phone_fax',
			'phone_work',
			'email'
		);
		
		return $fields;
	}

	/**
	 * Filters arg fields for contact information calls
	 *
	 * @param array $args - Arguments to be filtered
	 * @return array
	 */
	protected function filterFieldsContactInfo($args)
	{
		$fields = $this->getFieldsContactInfo();

		$args = array_intersect_key($args, array_flip($fields));

		return $args;
	}

	/**
	 * Updates contact information in the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @param array $args - Arguments to be passed to the client
	 * @return bool - Whether the update was performed or not
	 */
	public function updateContactInfo($application_id, $args)
	{
		if (!$this->getService()->isEnabled(__FUNCTION__) || !is_array($args) || empty($args))
		{
			return $retval;
		}

		$args = $this->filterFieldsContactInfo($args);

		$retarr = array();
		if (!empty($args))
		{
			$args_array = array();
			foreach ($args as $type => $value)
			{
				$args_array[] = array(
					'application_id' => $application_id,
					'type' => $type,
					'value' => $value,
					'modifying_agent_id' => $this->agent_id,
					'is_primary' => TRUE
				);
			}
			$args = $args_array;

			$retval = $this->getService()->updateContactInfo($args);
                	if (is_array($retval)){
		                $tmp = new stdClass();
				$tmp->item = $retval;
				$retval = $tmp;
			}

			$items = is_array($retval->item) ? $retval->item : array($retval->item);

			foreach ($items as $item)
			{
				$retarr[$item->type] = $item->contact_info_id;
			}
		}

		return $retarr;
	}

	/**
	 * Make a bulk bank info update
	 * 
	 * @param array $data
	 * @return mixed
	 */
	public function bulkUpdateBankInfo($data)
	{

		$retval = FALSE;
		$send_array = array();
		if (!$this->getService()->isInsertEnabled(__FUNCTION__) || !is_array($data) || empty($data))
		{
			return $retval;
		}
		foreach ($data as $app_id => $value)
		{
			$ary = $this->filterFieldsBankInfo($data[$app_id]);
			$ary['application_id'] = $data[$app_id];
			$ary['modifying_agent_id'] = $this->agent_id;
			$send_array[] = $ary;
		}

		$retval = $this->getService()->bulkUpdateApplicationBankInfo($send_array);

		return $retval;
	}

	/**
	 * Filters arg fields for bank information calls
	 *
	 * @param array $args - Arguments to be filtered
	 * @return array
	 */
	protected function filterFieldsBankInfo($args)
	{
		$fields = $this->getFieldsBankInfo();

		$args = array_intersect_key($args, array_flip($fields));

		return $args;
	}

	/**
	 * Gets fields that are valid to be passed as args to employment information calls
	 *
	 * @return array
	 */
	protected function getFieldsEmploymentInfo()
	{
		$fields = array(
			'shift', 
			'employer_name', 
			'department',
			'job_title',
			'supervisor',
			'date_hire',
			'job_tenure',
			'phone_work',
			'phone_work_ext',
			'modifying_agent_id'
		);
		
		return $fields;
	}

	/**
	 * Filters arg fields for employment information calls
	 *
	 * @param array $args - Arguments to be filtered
	 * @return array
	 */
	protected function filterFieldsEmploymentInfo($args)
	{
		$fields = $this->getFieldsEmploymentInfo();

		$args = array_intersect_key($args, array_flip($fields));

		return $args;
	}
	/**
	 * Gets Bank information from the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @return array|FALSE - array of key value pairs containing bank info data
	 */
	public function getBankInfo($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}

		//check if application exists in cache for call
		if ($this->cache->hasCache(self::BANK_INFO, $application_id))
		{
			return $this->cache->getCache(self::BANK_INFO, $application_id);
		}

		$retval = $this->getService()->getBankInfo($application_id);
		//store value in cache for application
		if (is_object($retval) && count(get_object_vars($retval)) > 0)
		{
			$this->cache->storeCache(self::BANK_INFO, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No bank info record returned for application_id: ' . $application_id);
		}

		return $retval;
	}

	/**
	 * Gets employment information from the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @return array|FALSE - array of key value pairs containing employment info data
	 */
	public function getEmploymentInfo($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}

		//check if application exists in cache for call
		if ($this->cache->hasCache(self::EMPLOYMENT, $application_id))
		{
			return $this->cache->getCache(self::EMPLOYMENT, $application_id);
		}

		$retval = $this->getService()->getEmploymentInfo($application_id);
		//store value in cache for application
		if (is_object($retval) && count(get_object_vars($retval)) > 0)
		{
			if (!empty($retval->date_hire))
			{
				$retval->date_hire = date('Y-m-d H:i:s', strtotime($retval->date_hire));
			}
			$this->cache->storeCache(self::EMPLOYMENT, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No employment info record returned for application_id: ' . $application_id);
		}

		return $retval;
	}

	/**
	 * Gets react affiliation from the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @return array|FALSE - array of key value pairs containing react affiliation data
	 */
	public function getReactAffiliation($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}

		//check if application exists in cache for call
		if ($this->cache->hasCache(self::REACT_AFFILIATION, $application_id))
		{
			return $this->cache->getCache(self::REACT_AFFILIATION, $application_id);
		}

		$retval = $this->getService()->getReactAffiliation($application_id);
		//store value in cache for application
		if (is_object($retval) && count(get_object_vars($retval)) > 0)
		{
			$this->cache->storeCache(self::REACT_AFFILIATION, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No react affiliation record returned for application_id: ' . $application_id);
		}

		return $retval;
	}

	/**
	 * Gets react affiliation from the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @return array|FALSE - array of key value pairs containing react affiliation data
	 */
	public function getReactAffiliationChildren($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}

		//check if application exists in cache for call
		if ($this->cache->hasCache(self::REACT_AFFILIATION_CHILDERN, $application_id))
		{
			return $this->cache->getCache(self::REACT_AFFILIATION_CHILDERN, $application_id);
		}

		$retval = $this->getService()->getReactAffiliationChildren($application_id);
		//store value in cache for application
		if (is_object($retval) && count(get_object_vars($retval)) > 0)
		{
			$this->cache->storeCache(self::REACT_AFFILIATION_CHILDERN, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No react affiliation childernrecord returned for application_id: ' . $application_id);
		}

		return $retval;
	}	
	/**
	 * Get application personal references by application id
	 *
	 * @param int $application_id
	 * @return mixed
	 */
	public function getAppPersonalRefs($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| !$this->checkApplicationId($application_id))
		{
			return $retval;
		}
		//check if application exists in cache for call
		if ($this->cache->hasCache(self::APPLICATION_REFERENCES, $application_id))
		{
			return $this->cache->getCache(self::APPLICATION_REFERENCES, $application_id);
		}

		$items = $this->getService()->getApplicationPersonalReferences($application_id);
                if (is_array($items)){
			$tmp = new stdClass();
			$tmp->item = $items;
			$items = $tmp;
		}

		$retval = (is_array($items->item)) ? array_values($items->item) : array($items->item);

		if (is_array($retval) && count(count($retval)) > 0)
		{
			$this->cache->storeCache(self::APPLICATION_REFERENCES, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No personal reference records returned for application_id: ' . $application_id);
		}

		return $retval;
	}

	/**
	 * Check that an application id is a valid format
	 *
	 * @param int $application_id
	 * @return bool
	 */
	protected function checkApplicationId($application_id)
	{
		return !empty($application_id) && is_numeric($application_id);	
	}

	/**
	 * Gets customer information from the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @return array|FALSE - array of key value pairs containing customer info data
	 */
	public function getApplicantAccountInfo($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}
		//check if application exists in cache for call
		if ($this->cache->hasCache(self::APPLICANT_ACCOUNT, $application_id))
		{
			return $this->cache->getCache(self::APPLICANT_ACCOUNT, $application_id);
		}
			
		$retval = $this->getService()->getApplicantAccountInfo($application_id);

		/**
		 * Add to Application Version Cache
		 */
		$this->addApplicationVersion($application_id, $retval->application_version);
		
		if (is_object($retval) && count(get_object_vars($retval)) > 0)	
		{
			$this->cache->storeCache(self::APPLICANT_ACCOUNT, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No customer info record returned for application_id: ' . $application_id);
		}

		return $retval;
	}
	/**
	 * Gets applicant information from the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @return array|FALSE - array of key value pairs containing customer info data
	 */
	public function getApplicantInfo($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}
		//check if application exists in cache for call
		if ($this->cache->hasCache(self::APPLICANT_INFO, $application_id))
		{
			return $this->cache->getCache(self::APPLICANT_INFO, $application_id);
		}
			
		$retval = $this->getService()->getApplicantInfo($application_id);
		
		if (is_object($retval) && count(get_object_vars($retval)) > 0)	
		{
			$this->cache->storeCache(self::APPLICANT_INFO, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Applicant info record returned for application_id: ' . $application_id);
		}

		return $retval;
	}
	/**
	 * Gets customer information from the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @return array|FALSE - array of key value pairs containing customer info data
	 */
	public function getApplicationInfo($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}
		//check if application exists in cache for call
		if ($this->cache->hasCache(self::APPLICATION_INFO, $application_id))
		{
			return $this->cache->getCache(self::APPLICATION_INFO, $application_id);
		}
			
		$retval = $this->getService()->getApplicationInfo($application_id);
		
		if (is_object($retval) && count(get_object_vars($retval)) > 0)	
		{
			$retval->fundRequested = round($retval->fundRequested, 2);
			$retval->fundQualified = round($retval->fundQualified, 2);
			$retval->fundActual = round($retval->fundActual, 2);
			$this->cache->storeCache(self::APPLICATION_INFO, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Application info record returned for application_id: ' . $application_id);
		}

		return $retval;
	}
	/**
	 * Updates employment information in the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @param array $args - Arguments to be passed to the client
	 * @return bool - Whether the update was performed or not
	 */
	public function updateEmploymentInfo($application_id, $args)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__) || !is_array($args) || empty($args))
		{
			return $retval;
		}

		$args = $this->filterFieldsEmploymentInfo($args);
		if (!empty($args))
		{
			if (!empty($args['income_direct_deposit']))
			{
				$args['is_direct_deposit'] = $args['income_direct_deposit'] == "yes" ? TRUE : FALSE;
			}
			$args['application_id'] = $application_id;
			$args['modifying_agent_id'] = $this->agent_id;
			$retval = $this->getService()->updateEmploymentInfo($args);
		}
		//remove value from cache since it has been updated
		$this->cache->removeCache(self::EMPLOYMENT, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}

	/**
	 * Gets do not loan flag overrides 
	 * 
	 * @param string $ssn
	 * @return array
	 */
	public function getDoNotLoanFlagOverrideAll($ssn)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)) return $retval;
		//check if application exists in cache for call
		if ($this->cache->hasCache(self::DO_NOT_LOAN_OVERRIDE_ALL, $ssn))
		{
			return $this->cache->getCache(self::DO_NOT_LOAN_OVERRIDE_ALL, $ssn);
		}

		$retval = $this->getService()->getDoNotLoanFlagOverrideAll($ssn);
		if (is_object($retval) && count(get_object_vars($retval)) > 0)	
		{
			$this->cache->storeCache(self::DO_NOT_LOAN_OVERRIDE_ALL, $ssn, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Do Not Loan Flag Override info record returned for ssn: ' . $ssn);
		}

		return $retval;
	}
	
	/**
	 * Gets all of the do not loan flags for an ssn (not filtered by company)
	 * 
	 * @param string $ssn
	 * @return array
	 */
	public function getDoNotLoanFlagAll($ssn)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)) return $retval;
		//check if application exists in cache for call
	//	if ($this->cache->hasCache(self::DO_NOT_LOAN_ALL, $ssn))
	//	{
	//		return $this->cache->getCache(self::DO_NOT_LOAN_ALL, $ssn);
	//	}
		$retval = $this->getService()->getDoNotLoanFlagAll($ssn);
		if (is_array($retval)) {
			$tmp = new stdClass();
			foreach ($retval as $key => $val){
				$tmp->$key = $val;
			}
			$retval = $tmp;
		}
		if (is_object($retval) && count(get_object_vars($retval)) > 0)	
		{
			$this->cache->storeCache(self::DO_NOT_LOAN_ALL, $ssn, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Do Not Loan info record returned for ssn: ' . $ssn);
		}
		return $retval;
	}

	/**
	 * Gets fields that are valid to be passed as args to paydate information calls
	 *
	 * @return array
	 */
	protected function getFieldsPaydateInfo()
	{
		$fields = array(
			'paydate_model', 
			'income_source', 
			'income_frequency',
			'day_of_week',
			'income_monthly',
			'income_direct_deposit',
			'income_date_soap_1',
			'income_date_soap_2',
			'last_paydate',
			'day_of_month_1',
			'day_of_month_2',
			'week_1',
			'week_2',
			'modifying_agent_id'
		);
		
		return $fields;
	}

	/**
	 * Filters arg fields for employment information calls
	 *
	 * @param array $args - Arguments to be filtered
	 * @return array
	 */
	protected function filterFieldsPaydateInfo($args)
	{
		$fields = $this->getFieldsPaydateInfo();

		$args = array_intersect_key($args, array_flip($fields));

		return $args;
	}
	
	/**
	 * Updates employment information in the application service
	 *
	 * @param int $application_id - ID of the application to update
	 * @param array $args - Arguments to be passed to the client
	 * @return bool - Whether the update was performed or not
	 */
	public function updatePaydateInfo($application_id, $args)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__) || !is_array($args) || empty($args))
		{
			return $retval;
		}

		$args = $this->filterFieldsPaydateInfo($args);
		if (!empty($args))
		{
			$args['application_id'] = $application_id;
			$args['modifying_agent_id'] = $this->agent_id;
			if (isset($args['income_direct_deposit']))
			{
				$args['income_direct_deposit'] = ($args['income_direct_deposit'] == 'yes') ? 1 : 0;
			}
			$retval = $this->getService()->updatePaydateInfo($args);
		}
		//remove value from cache since it has been updated
		$this->cache->removeCache(self::EMPLOYMENT, $application_id);
		$this->cache->removeCache(self::FULL_APP, $application_id);
		return $retval;
	}

	/**
	 * Inserts an app that wasn't purchased
	 * @param int $company_id
	 * @return int
	 */
	public function insertUnpurchasedApp($company_id)
	{
		return $this->getService()->insertUnpurchasedApp($company_id);
	}
	
	/**
	 * @param array $args - the data to send
	 * @return int|FALSE
	 */
	public function insert($args)
	{
		$application_id = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__)) return $application_id;

		if (!empty($args['date_first_payment']) && is_numeric($args['date_first_payment']))
		{
			$args['date_first_payment'] = strtotime(date("Y-m-d", $args['date_first_payment'])." 12:00:00");
		}
		if (!empty($args['last_paydate']) && is_numeric($args['last_paydate']))
		{
			$args['last_paydate'] = strtotime(date("Y-m-d", $args['last_paydate'])." 12:00:00");
		}
		if (!empty($args['income_direct_deposit']))
		{
			$args['is_direct_deposit'] = $args['income_direct_deposit'] == "yes" ? TRUE : FALSE;
		}
		$args['modifying_agent_id'] = $this->agent_id;
		$application_id = $this->getService()->insert($args);
		
		return $application_id;
	}

	/**
	 * Inserts the applicant account (ecash 'customer') into the application service
	 *
	 * @param int $application_id
	 * @param string $login - full login name
	 * @param string $password
	 * @return array - inserted data including full login
	 */
	public function associateApplicantAccount($application_id, $login, $password)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$args = array(
			'application_id' => $application_id,
			'login' => $login,
			'modifying_agent_id' => $this->agent_id,
			'password' => $password
		);

		$retval = $this->getService()->associateApplicantAccount($args);

		return $retval;
	}
	/**
	 * Inserts the applicant account (ecash 'customer') into the application service
	 *
	 * @param int $application_id
	 * @param string $base_login - login name minus the _#
	 * @param string $password
	 * @return array - inserted data including full login
	 */
	public function insertApplicantAccount($application_id, $base_login, $password)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$args = array(
			'application_id' => $application_id,
			'base_login' => $base_login,
			'modifying_agent_id' => $this->agent_id,
			'password' => $password
		);

		$retval = $this->getService()->insertApplicantAccount($args);

		return $retval;
	}

	/**
	 * Delete a do not loan flag override
	 *
	 * @param int $company_id
	 * @param int $ssn
	 * @return bool
	 */
	public function deleteDoNotLoanFlagOverride($company_id, $ssn)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__)) return $retval;

		$retval = $this->getService()->deleteDoNotLoanFlagOverride($ssn, $this->agent_id, $company_id);

		$this->cache->removeCache(self::DO_NOT_LOAN_ALL, $ssn);
		$this->cache->removeCache(self::DO_NOT_LOAN_OVERRIDE_ALL, $ssn);
		return $retval;
	}

	/**
	 * Set a do not loan flag for an ssn
	 * 
	 * @param unknown_type $company_id
	 * @param unknown_type $ssn
	 * @param unknown_type $category
	 * @param unknown_type $other_reason
	 * @param unknown_type $explanation
	 * @return bool
	 */
	public function insertDoNotLoanFlag($company_id, $ssn, $category, $other_reason, $explanation)
	{
		$retval = FALSE;
	//	if (!$this->getService()->isInsertEnabled(__FUNCTION__)) return $retval;

		$args = array(
			'ssn' => $ssn,
			'category' => $category,
			'company_id' => $company_id,
			'other_reason' => $other_reason,
			'explanation' => $explanation,
			'modifying_agent_id' => $this->agent_id
		);
		$retval = $this->getService()->insertDoNotLoanFlag($args);
		$this->cache->removeCache(self::DO_NOT_LOAN_ALL, $ssn);
		return $retval;
	}

	/**
	 * Set the do not loan flag to inactive
	 *
	 * @param int $company_id
	 * @param string $ssn
	 * @return bool
	 */
	public function deleteDoNotLoanFlag($company_id, $ssn)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__)) return $retval;

		$args = array(
			'ssn' => $ssn,
			'company_id' => $company_id,
			'modifying_agent_id' => $this->agent_id
		);
		$retval = $this->getService()->deleteDoNotLoanFlag($ssn,$company_id,$this->agent_id);
		$this->cache->removeCache(self::DO_NOT_LOAN_ALL, $ssn);
		return $retval;
	}

	/**
	 * Override a do not loan flag for an ssn
	 *
	 * @param int $company_id
	 * @param string $ssn
	 * @return bool
	 */
	public function overrideDoNotLoanFlag($company_id, $ssn)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$args = array(
			'ssn' => $ssn,
			'modifying_agent_id' => $this->agent_id,
			'company_id' => $company_id
		);
		$retval = $this->getService()->overrideDoNotLoanFlag($args);

		return $retval;
	}
	
	/**
	 * Fetches the Do Not Loan audit from the app service for the given SSN.
	 * 
	 * @param string $ssn
	 * @return array|FALSE - array of audit info
	 */
	public function getDoNotLoanAudit($ssn)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($ssn)
			|| !is_numeric($ssn))
		{
			return $retval;
		}
		if ($this->cache->hasCache(self::DO_NOT_LOAN_AUDIT, $application_id))
		{
			return $this->cache->getCache(self::DO_NOT_LOAN_AUDIT, $application_id);
		}
		$items = $this->getService()->getDoNotLoanAudit($ssn);
		if (is_array($items)){
		        $tmp = new stdClass();
			$tmp->item = $items;
			$items = $tmp;
		}

		$retval = (is_array($items->item)) ? array_values($items->item) : array($items->item);	
		if (count($retval) > 0)	
		{
			$this->cache->storeCache(self::DO_NOT_LOAN_AUDIT, $ssn, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Do Not Loan Audit info record returned for ssn: ' . $ssn);
		}

		return $retval;
	}

	/**
	 * Performs a search of the app service for applications which meet the proper criteria
	 * returns an array of applications
	 *
	 * @param array $request
	 * @param int $limit
	 * @return array
	 */
	public function applicationSearch($request, $limit)
	{
		$retval = array();
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| !is_array($request) || empty($request) || !is_numeric($limit))
		{
			return FALSE;
		}

		$app_service_result = $this->getService()->applicationSearch($request, $limit);
		if (is_array($app_service_result)){
			$tmp = new stdClass();
			$tmp->item = $app_service_result;
			$app_service_result = $tmp;
		}
		if(!empty($app_service_result->item))
		{
			if (is_object($app_service_result->item))
			{
				$app_service_result->item = array($app_service_result->item);
			}
			if (is_array($app_service_result->item))
			{
				$retval = $app_service_result->item;
			}
		}
		return $retval;
	}

	/**
	 * Performs a search of the app service for applications which meet the customer service criteria
	 *
	 * @param array $request
	 * @return array
	 */
	public function getPreviousCustomerApps(array $request)
	{
		$retval = array();
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($request))
		{
			return FALSE;
		}

		$app_service_result = $this->getService()->getPreviousCustomerApps($request);
		if (is_array($app_service_result)){
                        $tmp = new stdClass();
			$tmp->item = $app_service_result;
			$app_service_result = $tmp;
		}

		if (is_object($app_service_result->item))
		{
			$app_service_result->item = array($app_service_result->item);
		}
		if (!is_array($app_service_result->item))
		{
			$app_service_result->item = array();
		}
		return $app_service_result->item;
	}

	/**
	 * Gets application contact information from the application service
	 *
	 * @param int $application_id - ID of the application to get the contact info
	 * @return array|FALSE - array of contact info
	 */
	public function getContactInfo($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}
		if ($this->cache->hasCache(self::CONTACT_INFO, $application_id))
		{
			return $this->cache->getCache(self::CONTACT_INFO, $application_id);
		}
		$items = $this->getService()->getApplicationContactInfo($application_id);
                if (is_array($items)){
		        $tmp = new stdClass();
			$tmp->item = $items;
			$items = $tmp;
		}

		$retval = (is_array($items->item)) ? array_values($items->item) : array($items->item);

		if ((is_array($retval)) && (count($retval > 0)))
		{
			$this->cache->storeCache(self::CONTACT_INFO, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Contact Information records returned for application_id: ' . $application_id);
		}

		return $retval;
	}

	/**
	 * Gets application contact information from the application service
	 *
	 * @param int $contact_id - ID of the application to get the contact info
	 * @return contactinfo|FALSE - contact info record
	 */
	public function getContactInfoById($contact_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}

		$retval = $this->getService()->getApplicationContactInfoById($application_id);

		if (empty($retval))
		{
			$retval = FALSE;
			$this->log->Write('No Contact Information records returned for contact_id: ' . $contact_id);
		}

		return $retval;
	}

	/**
	 * Gets application audit from the application service
	 *
	 * @param int $application_id - ID of the application to audit
	 * @return array|FALSE - array of audit info
	 */
	public function getApplicationAudit($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}
		if ($this->cache->hasCache(self::AUDIT_INFO, $application_id))
		{
			return $this->cache->getCache(self::AUDIT_INFO, $application_id);
		}
		$items = $this->getService()->getApplicationAuditInfo($application_id);
                if (is_array($items)){
		        $tmp = new stdClass();
			$tmp->item = $items;
			$items = $tmp;
		}
		$retval = (is_array($items->item)) ? array_values($items->item) : array($items->item);

		if (is_array($retval) && count(($retval)) > 0)
		{
			$this->cache->storeCache(self::AUDIT_INFO, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Application Audit info record returned for application_id: ' . $application_id);
		}

		return $retval;
	}
	
	/**
	 * Adds the application_id and version to the cache for use by Application Locking
	 * 
	 * @param integer $application_id
	 * @param integer $version
	 * @return boolean
	 */
	protected function addApplicationVersion($application_id, $version)
	{
		if(	   (empty($application_id) || !is_numeric($application_id))
			|| (empty($version) || !is_numeric($version)))
			return FALSE;

		//[#40090] skip this if using CLI
		if(php_sapi_name() == 'cli')
			return TRUE;
		
		if(empty($_SESSION['application_version']) || !is_array($_SESSION['application_version']))
			$_SESSION['application_version'] = array();
			
		/**
		 * Don't let the cache grow to be larger than the limit
		 */
		while(count($_SESSION['application_version']) >= self::APPLICATION_VERSION_CACHE_LIMIT)
		{
			array_shift($_SESSION['application_version']);
		}

		$_SESSION['application_version'][$application_id] = $version;
		
		return TRUE;
	}
	
	/**
	 * Gets the current application version number from the App Service
	 * @param integer $application_id
	 * @return integer|FALSE
	 */
	public function getApplicationVersion($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}

		$retval = $this->getService()->getApplicationVersion($application_id);

		return $retval;
		
	}

	/**
	 * Refresh the version of the application.  To be used after
	 * saving a change in cases where there may be multiple updates.
	 *
	 * @param integer $application_id
	 * @return boolean
	 */
	public function updateApplicationVersion($application_id)
	{
		if(! empty($application_id))
		{
			$version = $this->getAPplicationVersion($application_id);
			$this->addApplicationVersion($application_id, $version);

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Compares the version in the session with what's 
	 * currently stored in the App Service
	 * 
	 * @param integer $application_id
	 * @return boolean TRUE if there have been no changes, FALSE if there have been
	 */
	public function versionCheck($application_id)
	{
		/**
		 * If reads are disabled, we have to return TRUE or else
		 * all checks will fail.
		 */
		if (!$this->getService()->isReadEnabled(__FUNCTION__)) return TRUE;
		
		$current_version = $this->getApplicationVersion($application_id);
		if(! empty($current_version) && isset($_SESSION['application_version'][$application_id]))
		{
			$app_version = $_SESSION['application_version'][$application_id];
			if($current_version == $app_version)
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}

		return TRUE;
	}
	
	/**
	 * Use the customer_id to fetch a list of applications associated with it
	 * @param integer $customer_id
	 * @return array|FALSE
	 */
	public function getApplicationIdsForCustomer($customer_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($customer_id)
			|| !is_numeric($customer_id))
		{
			return $retval;
		}

		$items = $this->getService()->getApplicationIdsForCustomer($customer_id);
                if (is_array($items)){
			$tmp = new stdClass();
			$tmp->item = $items;
			$items = $tmp;
		}
													
		$retval = (is_array($items->item)) ? array_values($items->item) : array($items->item);

		return $retval;
	}
	
	/**
	 * Get all do not loan flags, do not loan flag overrides and regulatory flags for an ssn
	 * 
	 * @param int $ssn
	 * @return array
	 */
	public function flagSearchBySsn($ssn)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)) return $retval;

		$retval = $this->getService()->flagSearchBySsn($ssn);

		return $retval;
	}


	/**
	 * Use the application_id to fetch a list of one or more campaign info records
	 * @param integer $application_id
	 * @return array|FALSE
	 */
	public function getCampaignInfo($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)
			|| empty($application_id)
			|| !is_numeric($application_id))
		{
			return $retval;
		}

		if ($this->cache->hasCache(self::CAMPAIGN_INFO, $application_id))
		{
			return $this->cache->getCache(self::CAMPAIGN_INFO, $application_id);
		}

		$items = $this->getService()->getCampaignInfo($application_id);
                if (is_array($items)){
                	$tmp = new stdClass();
		        $tmp->item = $items;
			$items = $tmp;
		}
		$retval = (is_array($items->item)) ? array_values($items->item) : array($items->item);
		if (count($retval) > 0)
		{
			$this->cache->storeCache(self::CAMPAIGN_INFO, $application_id, $retval);
		}
		else
		{
			$retval = FALSE;
			$this->log->Write('No Campaign Info record(s) returned for application_id: ' . $application_id);
		}

		return $retval;
	}

	/**
	 * Performs the call to the underlying service, clearing all buffered calls
	 *
	 * @return mixed
	 */	
	public function flush()
	{
		return $this->getService()->flush();
	}

	/**
	 * enables and disables buffering of calls
	 *
	 * @param boolean $enabled
	 * @return void
	 */
	public function enableBuffer($enabled)
	{
		$this->getService()->setAggregateEnabled($enabled);
	}
}

?>
