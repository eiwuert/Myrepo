<?php

require_once 'ObservableWritableModel.php';
require_once 'LoanType.php';
require_once 'Company.php';
require_once 'Customer.php';
require_once 'ApplicationStatusFlat.php';
require_once 'Agent.php';

/**
 * @package Ecash.Models
 */
class ECash_Models_Application extends ECash_Models_ObservableWritableModel implements ECash_Models_ICustomerFriend
{
	public $Company;
	public $Customer;
	public $ArchiveDb2;
	public $ArchiveMysql;
	public $ArchiveCashline;
	public $Login;
	public $LoanType;
	public $RuleSet;
	public $EnterpriseSite;
	public $ApplicationStatus;
	public $ApplicationStatusFlat;
	public $Track;
	public $Agent;
	public $ScheduleModel;
	public $ModifyingAgent;
	
	public function getColumns()
	{

		static $columns = array(
			'date_modified', 'date_created', 'company_id',
			'application_id', 'customer_id', 'is_react',
			'loan_type_id', 'rule_set_id', 'enterprise_site_id',
			'application_status_id', 'date_application_status_set',
			'date_next_contact', 'ip_address', 'esig_ip_address', 'application_type',
			'bank_name', 'bank_aba', 'bank_account', 'bank_account_type',
			'date_fund_estimated', 'date_fund_actual', 'date_first_payment',
			'fund_requested', 'fund_qualified', 'fund_actual', 'finance_charge',
			'payment_total', 'apr', 'rate_override', 'income_monthly', 
			'income_source', 'income_direct_deposit', 'income_frequency',
			'income_date_soap_1', 'income_date_soap_2', 'paydate_model',
			'day_of_week', 'last_paydate', 'day_of_month_1',
			'day_of_month_2', 'week_1', 'week_2', 'track_id',
			'agent_id', 'dob', 'ssn', 'legal_id_number', 'legal_id_state',
			'legal_id_type', 'email', 'name_last', 'name_first',
			'name_middle', 'name_suffix', 'street', 'unit', 'city',
			'state', 'zip_code', 'tenancy_type', 'phone_home',
			'phone_cell', 'phone_fax', 'call_time_pref',
			'contact_method_pref', 'marketing_contact_pref',
			'employer_name', 'job_title', 'supervisor', 'shift',
			'date_hire', 'job_tenure', 'phone_work', 'phone_work_ext',
			'pwadvid', 'olp_process', 'is_watched', 'modifying_agent_id',
			'county', 'cfe_rule_set_id', 'price_point', 'banking_start_date',
			'residence_start_date', 'ssn_last_four', 'age'
			);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('application_id');
	}

	public function getAutoIncrement()
	{
		return null;
	}

	public function getTableName()
	{
		return 'application';
	}

	public function getColumnData()
	{
		$modified = $this->column_data;
		//mysql timestamps
		$modified['date_modified'] = date("Y-m-d H:i:s", $modified['date_modified']);
		$modified['date_created'] = date("Y-m-d H:i:s", $modified['date_created']);
		$modified['date_application_status_set'] = date("Y-m-d H:i:s", $modified['date_application_status_set']);
		$modified['date_next_contact'] = $modified['date_next_contact'] === NULL ? NULL : date("Y-m-d H:i:s", $modified['date_next_contact']); //was	date("Y-m-d H:i:s", is_numeric($modified['date_next_contact']) ? $modified['date_next_contact'] : strtotime($modified['date_next_contact']));
		//mysql dates
		$modified['date_fund_estimated'] = empty($modified['date_fund_estimated']) ? NULL : date("Y-m-d", $modified['date_fund_estimated']); //asm 15
		$modified['date_fund_actual'] = $modified['date_fund_actual'] === NULL ? NULL : date("Y-m-d", $modified['date_fund_actual']);
		$modified['date_first_payment'] = $modified['date_first_payment'] === NULL ? NULL : date("Y-m-d", $modified['date_first_payment']);		
		$modified['income_date_soap_1'] = $modified['income_date_soap_1'] === NULL ? NULL : date("Y-m-d", $modified['income_date_soap_1']);
		$modified['income_date_soap_2'] = $modified['income_date_soap_2'] === NULL ? NULL : date("Y-m-d", $modified['income_date_soap_2']);
		$modified['last_paydate'] = $modified['last_paydate'] === NULL ? NULL : date("Y-m-d", is_numeric($modified['last_paydate']) ? $modified['last_paydate'] : strtotime($modified['last_paydate'])); 
		$modified['date_hire'] = empty($modified['date_hire']) ? NULL : date("Y-m-d", $modified['date_hire']); //asm 15
		$modified['residence_start_date'] = empty($modified['residence_start_date']) ? NULL : date("Y-m-d", $modified['residence_start_date']);
		$modified['banking_start_date'] = empty($modified['banking_start_date']) ? NULL : date("Y-m-d", $modified['banking_start_date']); //asm 15
		$modified['dob'] = $modified['dob'] === NULL ? NULL : date("Y-m-d", is_numeric($modified['dob']) ? $modified['dob'] : strtotime($modified['dob']));
		//SSN Last Four
		$modified['ssn_last_four'] = $modified['ssn'] === NULL ? NULL : substr($modified['ssn'], strlen($modified['ssn']) - 4, 4);
		
		if($modified['ssn_last_four'] != $this->column_data['ssn_last_four'])
			$this->altered_columns['ssn_last_four'] = 'ssn_last_four';
																	   
		return $modified;
	}

	public function setColumnData($column_data)
	{
		//mysql timestamps
		$column_data['date_modified'] = strtotime( $column_data['date_modified']);
		$column_data['date_created'] = strtotime( $column_data['date_created']);
		$column_data['date_application_status_set'] = (empty($column_data['date_application_status_set']) || $column_data['date_application_status_set'] === '0000-00-00') ? NULL : strtotime($column_data['date_application_status_set']);
		$column_data['date_next_contact'] = empty($column_data['date_next_contact']) ? NULL : strtotime( $column_data['date_next_contact']);

		//mysql dates
		$column_data['date_fund_estimated'] = strtotime( $column_data['date_fund_estimated']);
		$column_data['date_fund_actual'] = (empty($column_data['date_fund_actual']) || $column_data['date_fund_actual'] === '0000-00-00') ? NULL : strtotime($column_data['date_fund_actual']);
		$column_data['date_first_payment'] = $column_data['date_first_payment'] === '0000-00-00' ? NULL : strtotime($column_data['date_first_payment']);
		$column_data['income_date_soap_1'] = empty($column_data['income_date_soap_1']) ? NULL : strtotime( $column_data['income_date_soap_1']);
		$column_data['income_date_soap_2'] = empty($column_data['income_date_soap_2']) ? NULL : strtotime( $column_data['income_date_soap_2']);
		$column_data['last_paydate'] = empty($column_data['last_paydate']) ? NULL :	(is_numeric($column_data['last_paydate']) ? $column_data['last_paydate'] : strtotime($column_data['last_paydate']));
		$column_data['dob'] = strtotime($column_data['dob']);
		$column_data['date_hire'] = empty($column_data['date_hire']) ? NULL : strtotime( $column_data['date_hire']);
		if (!empty($column_data['residence_start_date']) &&
			$column_data['residence_start_date'] != '0' && $column_data['residence_start_date'] != NULL)
			$column_data['residence_start_date'] = strtotime($column_data['residence_start_date']);

		if (!empty($column_data['banking_start_date']) &&
			$column_data['banking_start_date'] != '0' && $column_data['banking_start_date'] != NULL)
			$column_data['banking_start_date'] = strtotime($column_data['banking_start_date']);

		// Post encryption
		if ($column_data['dob'] != '0' && $column_data['dob'] != NULL)
			$column_data['dob'] = date("Y-m-d", is_numeric($column_data['dob']) ? $column_data['dob'] : strtotime($column_data['dob']));

		$this->column_data = $column_data;
	}

	public function setLoanType($type_name_short)
	{
		$this->LoanType = ECash::getFactory()->getModel('LoanType');
		$this->LoanType->loadBy(array('name_short' => $type_name_short));
		$this->loan_type_id = $this->LoanType->loan_type_id;
	}

	public function getLoanType()
	{
		return $this->LoanType->name_short;
	}

	public function setCompany($company_short)
	{
		$this->Company = ECash::getFactory()->getModel('Company');
		$this->Company->loadBy(array('name_short' => $company_short));
		$this->company_id = $this->Company->company_id;
	}

	public function getCompany()
	{
		$company = ECash::getFactory()->getModel('Company');
		$company->loadBy(array('company_id' => $this->company_id));
		return $company->name_short;
	}
	
	public function setRuleSet($name)
	{
		$this->RuleSet = ECash::getFactory()->getModel('RuleSet');
		$this->RuleSet->loadBy(array('name' => $name));
		$this->rule_set_id = $this->RuleSet->rule_set_id;
	}

	public function setAgent($name)
	{
		$this->Agent = ECash::getFactory()->getModel('Agent');
		$this->Agent->loadBy(array('login' => $name));
		$this->agent_id = $this->Agent->agent_id;
	}

	public function setModifyingAgent($name)
	{
		$this->ModifyingAgent = ECash::getFactory()->getModel('Agent');
		$this->ModifyingAgent->loadBy(array('login' => $name));
		$this->modifying_agent_id = $this->ModifyingAgent->agent_id;
	}

	public function setCustomerData(ECash_Models_Customer $customer)
	{
		$this->customer_id = $customer->customer_id;
	}

	/**
	 * I don't like overriding (and duplicating) this method, but
	 * it was the easiest way to add the LOCK_LAYER crap [JustinF]
	 * @todo DON'T DUPLICATE THIS UPDATE() FUNCTION!
	 */
	public function update()
	{
		$event = new stdClass();
		$event->type = self::EVENT_BEFORE_UPDATE;
		$this->notifyObservers($event);

		$as = ECash::getFactory()->getWebServiceFactory()->getWebService('application');

		if (count($this->altered_columns))
		{
			/**
			 * If we're not "safe", lets not perform the update at all
			 * and just retun 0 for the number of rows affected
			 */
			if(! $as->versionCheck($this->application_id))
				return 0;
			
			$column_data = $this->getColumnData();
			$modified = array_intersect_key($column_data, $this->altered_columns);
			$pk = array_intersect_key($column_data, array_flip($this->getPrimaryKey()));

			$db = $this->getDatabaseInstance(self::DB_INST_WRITE);

			$query = "
				UPDATE " . $this->getTableName() . "
				SET
				".implode(" = ?, ", array_keys($modified))." = ?
				WHERE
					".implode(" = ? AND ", array_keys($pk))." = ?
			";

			$st = $db->prepare($query);
			$st->execute(
				array_merge(
					array_values($modified),
					array_values($pk)
				)
			);

			$this->affected_row_count = $st->rowCount();
			$this->setDataSynched();

			$event = new stdClass();
			$event->type = self::EVENT_UPDATE;
			$event->altered_data =  $modified;
			$this->notifyObservers($event);
		}

		/**
		 * Update the version so we don't fail the version check on subsequent saves.
		 */
		$as->updateApplicationVersion($this->application_id);
		
		return $this->affected_row_count;
	}

	public function loadLegacyAll($application_id, &$response)
	{
		/**
		 * Grabs all the native data
		 */
		$row = $this->getNativeColumnData($application_id);

		$sql = "select ac.application_id, ac.application_contact_id, ac.value
				from application_contact ac
				where ac.application_id = {$application_id}
				and ac.type = 'co_borrower' limit 1";
		$stmt = $this->getDatabaseInstance(self::DB_INST_READ)->query($sql);
		if($title_info = $stmt->fetch(PDO::FETCH_OBJ))
		{
			$row['co_borrower'] = $title_info->value;
			$row['co_borrower_id'] = $title_info->application_contact_id;		
		}
		else
		{
			$row['co_borrower'] = '';
			$row['co_borrower_id'] = '';
		}

		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');

		/**
		 * Class used to get various pieces of data that either require some sort of
		 * processing or where we have to query ldb
		 */
		$applicationDataObj = ECash::getFactory()->getData('Application');

		$application_info = $app_client->getApplicationInfo($application_id);
		$customer_info = $app_client->getApplicantAccountInfo($application_id);
		$bank_info = $app_client->getBankInfo($application_id);
		$react_affiliation = $app_client->getReactAffiliation($application_id);
		$status_history = $app_client->getApplicationStatusHistory($application_id)->item;
		$campaign_info = $app_client->getCampaignInfo($application_id);
		$campaign_info = $campaign_info[0];

		$company = ECash::getFactory()->getModel('Company');
		$company->loadBy(array('company_id' => $row['company_id']));
		$row['display_short'] = $company->name_short;

		$loan_type = ECash::getFactory()->getModel('LoanType');
		$loan_type->loadBy(array('loan_type_id' => $row['loan_type_id']));
		$row['loan_type_name'] = $loan_type->name;
		$row['loan_type'] = $loan_type->name_short;
		$row['loan_type_abbreviation'] = $loan_type->abbreviation;

		$row['login'] = $customer_info->login;
		$row['crypt_password'] = Crypt_3::Encrypt($customer_info->password);

		$row['parent_application_id'] = ($application_info->isReact) ? $react_affiliation->application_id : NULL;

		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$row['application_status_id'] = $status_list->toId($application_info->applicationStatusName); //convert to id

 		$status = $status_list[$row['application_status_id']];
		$row['status_long'] = $status->level0_name;
		$row['application_status'] = $row['status_long'];
		$row['status'] = $status->level0;
		$row['level1'] = $status->level1;
		$row['level2'] = $status->level2;
		$row['level3'] = $status->level3;
		$row['level4'] = $status->level4;
		$row['level5'] = $status->level5;

		/**
		 * Grabs the rate override from the database if it exists.
		 * If trying to calculate using a rate, either overridden or
		 * the default, use ECash_Application::getRate() which will
		 * use the rate calculator to get the rate for you.
		 */
		$rate_override = NULL;
		$rate_model = ECash::getFactory()->getModel('RateOverride', $this->db);
		if($rate_model->loadBy(array('application_id' => $this->application_id)))
		{
			$rate_override = $rate_model->rate_override;
		}

		/**
		 * Miscellaneous fields required for ECash_Application_Data::Get_Application_Data
		 */
		$row['customer_county'] = $row['county'];

		if(! empty($row['date_first_payment']))
		{
			$row['date_first_payment_day']   = date('d', strtotime($row['date_first_payment']));
			$row['date_first_payment_month'] = date('m', strtotime($row['date_first_payment']));
			$row['date_first_payment_year']  = date('Y', strtotime($row['date_first_payment']));
		}

		if(! empty($row['date_fund_actual']))
		{
			$row['date_fund_actual_ymd'] = date('Y-m-d', strtotime($row['date_fund_actual']));
		}

		/**
		 * Fraud is disabled by default and the
		 * fraud data will need to be passed in
		 * via customer overrides.
		 */
		$row['fraud_rules'] = NULL;
		$row['fields'] = NULL;
		$row['fraud_fields'] = NULL;
		$row['risk_rules'] = NULL;
		$row['risk_fields'] = NULL;

		/**
		 * Duplicat IP Address Count
		 */
		$ip_address_count = $applicationDataObj->getDuplicateIpAddress($row['ip_address'], TRUE);
		$row['ip_address_count'] = ($ip_address_count > 0) ? $ip_address_count -1 : 0;

		/**
		 * Duplicate Bank ABA/Account Count
		 */
		$bank_count = $applicationDataObj->getDuplicateBankInfo($bank_info->bank_aba, $bank_info->bank_account, TRUE);
		$row['aba_account_count'] = ($bank_count > 0 ) ? $bank_count -1 : 0;

		/**
		 * Date Confirmed
		 */
		$date_confirmed = $applicationDataObj->getFirstStatusDate($status_history, 'confirmed::prospect::*root');
		if(! empty($date_confirmed))
		{
			$row['date_confirmed'] = date('m-d-Y', strtotime($date_confirmed));
		}
		else
		{
			$row['date_confirmed'] = NULL;
		}

		/**
		 * Campaign Stuff
		 */
		if(! empty($row['enterprise_site_id']))
		{
			$site = ECash::getFactory()->getModel('Site');
			if($site->loadBy(array('site_id' => $row['enterprise_site_id'])))
			{
				$enterprise_site_name = $site->name;
				
				/**
				 * If the marketing site id in campaign info is the same
				 * as the enterprise site id in the application table, we call it organic
				 */
				if($row['is_react'] == 'no' && $enterprise_site_name == $campaign_info->site)
				{
					$campaign_name = 'organic';
				}
				/**
				 * [#51659]/[#53132] look at enterprise site rather than olp_process
				 */
				else if($row['is_react'] == 'yes' && $enterprise_site_name == $campaign_info->site)
				{
					$campaign_name = 'organic react';
				}
				/**
				 * Otherwise we just show the campaign name
				 */
				else
				{
					$campaign_name = $campaign_info->campaign_name;
				}
			}
			else
			{
				$campaign_name = $campaign_info->campaign_name;
			}
		}
		else
		{
			/**
			 * Fail-Safe for now, if the enterprise_site_id is not populated
			 */
			$campaignData = $applicationDataObj->getCampaignInfo($application_id);
			$campaign_name = $campaignData->campaign_name;
		}

		$row['campaign_name'] = $campaign_name;
		$row['promo_id'] = $campaign_info->promo_id;
		$row['promo_sub_code'] = $campaign_info->promo_sub_code;
		$row['origin_url'] = $campaign_info->site;

		/**
		 * Takes the data in $row and puts it into $response
		 */
		ECash::getFactory()->getDisplay('LegacyApplication')->loadAll($row, $response);
		return $this;
	}

	public function loadLegacyAll_ldb($application_id, &$response)
	{
		$row = $this->getNativeColumnData($application_id);

		$this->getApplicationInfoLDB($application_id, &$row);

		$company = ECash::getFactory()->getModel('Company');
		$company->loadBy(array('company_id' => $row['company_id']));
		$row['display_short'] = $company->name_short;

		$loan_type = ECash::getFactory()->getModel('LoanType');
		$loan_type->loadBy(array('loan_type_id' => $row['loan_type_id']));
		$row['loan_type_name'] = $loan_type->name;
		$row['loan_type'] = $loan_type->name_short;
		$row['loan_type_abbreviation'] = $loan_type->abbreviation;
		
		$app = ECash::getApplicationById($application_id);
		$row['application_status_id'] = $app->getStatus()->application_status_id;
		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$status = $status_list[$row['application_status_id']];
		$row['status_long'] = $status->level0_name;
		$row['application_status'] = $row['status_long'];
		$row['status'] = $status->level0;
		$row['level1'] = $status->level1;
		$row['level2'] = $status->level2;
		$row['level3'] = $status->level3;
		$row['level4'] = $status->level4;
		$row['level5'] = $status->level5;

		/**
		* Grabs the rate override from the database if it exists.
		* If trying to calculate using a rate, either overridden or
		* the default, use ECash_Application::getRate() which will
		* use the rate calculator to get the rate for you.
		*/
		$rate_override = NULL;
		$rate_model = ECash::getFactory()->getModel('RateOverride', $this->db);
		if($rate_model->loadBy(array('application_id' => $this->application_id)))
		{
			$rate_override = $rate_model->rate_override;
		}

		/**
		* Miscellaneous fields required for ECash_Application_Data::Get_Application_Data
		*/
		$row['customer_county'] = $row['county'];

		if(! empty($row['date_first_payment']))
		{
			$row['date_first_payment_day']   = date('d', strtotime($row['date_first_payment']));
			$row['date_first_payment_month'] = date('m', strtotime($row['date_first_payment']));
			$row['date_first_payment_year']  = date('Y', strtotime($row['date_first_payment']));
		}

		if(! empty($row['date_fund_actual']))
		{
			$row['date_fund_actual_ymd'] = date('Y-m-d', strtotime($row['date_fund_actual']));
		}
		
		/**
		* Fraud is disabled by default and the
		* fraud data will need to be passed in
		* via customer overrides.
		*/
		$row['fraud_rules'] = NULL;
		$row['fields'] = NULL;
		$row['fraud_fields'] = NULL;
		$row['risk_rules'] = NULL;
		$row['risk_fields'] = NULL;

		/**
		* Campaign Stuff
		*/
		$ci = ECash::getFactory()->getModel('CampaignInfo');
		$ci_array = $ci->loadAllBy(array('application_id' => $application_id,));
		foreach ($ci_array as $ci_record)
		{
			$site_id = $ci_record->site_id;
			$campaign_name = $ci_record->campaign_name;
			$promo_id = $ci_record->promo_id;
			$promo_sub_code = $ci_record->promo_sub_code;
			break;
		}

		$s = ECash::getFactory()->getModel('Site');
		if($s->loadBy(array('site_id' => $site_id)))
		{
			$site_name = $s->name;
		}
		
		if(! empty($row['enterprise_site_id']))
		{
			$site = ECash::getFactory()->getModel('Site');
			if($site->loadBy(array('site_id' => $row['enterprise_site_id'])))
			{
				$enterprise_site_name = $site->name;
				
				/**
				* If the marketing site id in campaign info is the same
				* as the enterprise site id in the application table, we call it organic
				*/
				if($row['is_react'] == 'no' && $enterprise_site_name == $site_name)
				{
					$campaign_name = 'organic';
				}
				/**
				* [#51659]/[#53132] look at enterprise site rather than olp_process
				*/
				else if($row['is_react'] == 'yes' && $enterprise_site_name == $site_name)
				{
					$campaign_name = 'organic react';
				}
				/**
				* Otherwise we just show the campaign name
				*/
				else
				{
					$campaign_name = $campaign_name;
				}
			}
			else
			{
				$campaign_name = $campaign_name;
			}
		}
		else
		{
			$campaign_name = $campaign_name;
		}

		$row['campaign_name'] = $campaign_name;
		$row['promo_id'] = $promo_id;
		$row['promo_sub_code'] = $promo_sub_code;
		$row['origin_url'] = $site_name;

		//Date Confirmed
		$status_id = Status_Utility::Get_Status_ID_By_Chain('confirmed::prospect::*root');
		$sh = ECash::getFactory()->getModel('StatusHistory');
		$sh_array = $sh->loadAllBy(array("application_id" => $application_id));
		foreach ($sh_array as $sh_record)
		{
			if ($sh_record->application_status_id == $status_id)
			{
				$date_confirmed = $sh_record->date_created;
				break;
			}
		}

		if(! empty($date_confirmed))
		{
			$row['date_confirmed'] = date('m-d-Y', strtotime($date_confirmed));
		}
		else
		{
			$row['date_confirmed'] = NULL;
		}

		//Others: Temporarily from App service
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$customer_info = $app_client->getApplicantAccountInfo($application_id);
		$row['login'] = $customer_info->login;
		$row['crypt_password'] = Crypt_3::Encrypt($customer_info->password);

		$applicationDataObj = ECash::getFactory()->getData('Application');
		/**
		* Duplicat IP Address Count
		*/
		$ip_address_count = $applicationDataObj->getDuplicateIpAddress($row['ip_address'], TRUE);
		$row['ip_address_count'] = ($ip_address_count > 0) ? $ip_address_count -1 : 0;
		/**
		* Duplicate Bank ABA/Account Count
		*/
		$bank_count = $applicationDataObj->getDuplicateBankInfo($bank_info->bank_aba, $bank_info->bank_account, TRUE);
		$row['aba_account_count'] = ($bank_count > 0 ) ? $bank_count -1 : 0;

		//var_dump($row);
		ECash::getFactory()->getDisplay('LegacyApplication')->loadAll($row, $response);

		return $this;
	}

	/**
	 * Fetches data from the application service only for columns
	 * which are in the column list.
	 */
	protected function getNativeColumnData($application_id)
	{
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		if(!$full_app = $app_client->fetchAll($application_id))
		{
			throw new ECash_Application_NotFoundException("Unable to locate '{$application_id}' in Application Service!");
		}

		$customer_info = $app_client->getApplicantAccountInfo($application_id);
		$employment_info = $app_client->getEmploymentInfo($application_id);
		$applicant_info = $app_client->getApplicantInfo($application_id);
		$application_info = $app_client->getApplicationInfo($application_id);
		$bank_info = $app_client->getBankInfo($application_id);
		$react_affiliation = $app_client->getReactAffiliation($application_id);

		/**
		 * Class used to get various pieces of data that either require some sort of
		 * processing or where we have to query ldb
		 */
		$applicationDataObj = ECash::getFactory()->getData('Application');
		/**
		 * Returns a simplified object containing key => value pairs.
		 */
		$contact_info = $full_app->primary_contact_info;

		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$row = array();

		$row['age'] = empty($applicant_info->age) ? NULL : $applicant_info->age;
		$row['agent_id'] = $application_info->modifyingAgentId;
		$row['application_id'] = $application_id;
		$row['application_status_id'] = $status_list->toId($application_info->applicationStatusName);
		$row['application_type'] = 'paperless'; // Deprecated
		$row['apr'] = $application_info->apr;
		$row['bank_aba'] = $bank_info->bank_aba;
		$row['bank_account'] = $bank_info->bank_account;
		$row['bank_account_type'] = $bank_info->bank_account_type;
		$row['bank_name'] = $bank_info->bank_name;
		$row['banking_start_date'] = (! empty($bank_info->banking_start_date)) ? date("Y-m-d", strtotime($bank_info->banking_start_date)) : NULL;
		$row['call_time_pref'] = empty($application_info->callTimePref->name) ? NULL : strtolower($application_info->callTimePref->name);
		$row['cfe_rule_set_id'] = $application_info->cfeRuleSetId;
		$row['city'] = $applicant_info->city;
		$row['company_id'] = $application_info->companyId;
		$row['contact_method_pref'] = empty($application_info->contactMethodPref->name) ? NULL : $application_info->contactMethodPref->name;
		$row['county'] = empty($applicant_info->county) ? NULL : $applicant_info->county;
		$row['customer_id'] = $application_info->customerId;
		$row['date_application_status_set'] = (! empty($application_info->dateApplicationStatusSet)) ? date("Y-m-d H:i:s", strtotime($application_info->dateApplicationStatusSet)) : NULL;
		$row['date_created'] = (! empty($application_info->dateCreated)) ? date("Y-m-d H:i:s", strtotime($application_info->dateCreated)) : NULL;
		$row['date_first_payment'] = (! empty($application_info->dateFirstPayment)) ? date("Y-m-d", strtotime($application_info->dateFirstPayment)) : NULL;
		$row['date_fund_actual'] = (! empty($application_info->dateFundActual)) ? date("Y-m-d", strtotime($application_info->dateFundActual)) : NULL;
		$row['date_fund_estimated'] = (! empty($application_info->dateFundEstimated)) ? date("Y-m-d", strtotime($application_info->dateFundEstimated)) : NULL;
		$row['date_hire'] = (! empty($employment_info->date_hire)) ? date("Y-m-d", strtotime($employment_info->date_hire)) : NULL;
		$row['date_modified'] = (! empty($application_info->dateModified)) ? date("Y-m-d H:i:s", strtotime($application_info->dateModified)) : NULL;
		$row['date_next_contact'] = NULL; // Does this even get used?
		$row['job_tenure'] = empty($employment_info->job_tenure) ? NULL : $employment_info->job_tenure;
		$row['day_of_month_1'] = empty($employment_info->day_of_month_1) ? NULL : $employment_info->day_of_month_1;
		$row['day_of_month_2'] = empty($employment_info->day_of_month_2) ? NULL : $employment_info->day_of_month_2;
		$row['day_of_week'] = empty($employment_info->day_of_week) ? NULL : strtolower($employment_info->day_of_week);
		$row['dob'] = date("Y-m-d", strtotime($applicant_info->dob));
		$row['email'] = $contact_info->email;
		$row['employer_name'] = $employment_info->employer_name;

		/**
		 * @Note: This *should* be a site_id from the site table in ldb
		 */
		$row['enterprise_site_id'] = $application_info->enterpriseSiteId;
		$row['finance_charge'] = empty($application_info->financeCharge) ? NULL : $application_info->financeCharge;
		if(!empty($application_info->fundActual))
		{
			//app service return four zeros getting rid of them
			$row['fund_actual'] = number_format($application_info->fundActual, 2, '.', '');
		}
		if(!empty($application_info->fundQualified))
		{
			$row['fund_qualified'] = number_format($application_info->fundQualified, 2, '.', '');
		}
		if(!empty($application_info->fundRequested))
		{
			$row['fund_requested'] = number_format($application_info->fundRequested, 2, '.', '');
		}

		$row['income_date_soap_1'] = empty($employment_info->income_date_soap_1) ? NULL : $employment_info->income_date_soap_1;
		$row['income_date_soap_2'] = empty($employment_info->income_date_soap_2) ? NULL : $employment_info->income_date_soap_2;
		$row['income_direct_deposit'] = ($employment_info->income_direct_deposit == 0) ? "no" : "yes";
		$row['income_frequency'] = strtolower($employment_info->income_frequency);
		$row['income_monthly'] = $employment_info->income_monthly;
		$row['income_source'] = $employment_info->income_source;
		$row['ip_address'] = $application_info->ipAddress;
		$row['esig_ip_address'] = $application_info->eSigIpAddress;
		$row['is_react'] = ($application_info->isReact == TRUE) ? 'yes' : 'no';
		$row['is_watched'] = ($application_info->isWatched == TRUE) ? 'yes' : 'no';
		$row['job_tenure'] =  empty($employment_info->job_tenure) ? NULL : $employment_info->job_tenure;
		$row['job_title'] = empty($employment_info->job_title) ? NULL : $employment_info->job_title;
		$row['last_paydate'] = (! empty($employment_info->last_paydate)) ? date("Y-m-d", strtotime($employment_info->last_paydate)) : NULL;
		$row['legal_id_number'] = $applicant_info->legal_id_number;
		$row['legal_id_state'] = $applicant_info->legal_id_state;
		$row['legal_id_type'] = $applicant_info->legal_id_type;
		$row['loan_type_id'] = $application_info->loanTypeId;
		$row['marketing_contact_pref'] = empty($application_info->marketingContactPref->name) ? NULL : $application_info->marketingContactPref->name;
		$row['modifying_agent_id'] = $application_info->modifyingAgentId;
		$row['name_first'] = $applicant_info->name_first;
		$row['name_last'] = $applicant_info->name_last;
		$row['name_middle'] = empty($applicant_info->name_middle) ? NULL : $applicant_info->name_middle;
		$row['name_suffix'] = empty($applicant_info->name_suffix) ? NULL : $applicant_info->name_suffix;
		$row['olp_process'] = $application_info->olpProcess->name;
		$row['paydate_model'] = strtolower($employment_info->paydate_model);
		$row['payment_total'] = empty($application_info->paymentTotal) ? NULL : $application_info->paymentTotal;
		$row['phone_cell'] = $contact_info->phone_cell;
		$row['phone_fax'] = empty($contact_info->phone_fax) ? NULL : $contact_info->phone_fax;
		$row['phone_home'] = $contact_info->phone_home;
		$row['phone_work'] = $employment_info->phone_work;
		$row['phone_work_ext'] = empty($employment_info->phone_work_ext) ? NULL : $employment_info->phone_work_ext;
		if(! empty($application_info->pricePoint))
		{
			$row['price_point'] = money_format('%i', $application_info->pricePoint);
		}
		$row['pwadvid'] = empty($application_info->pwadvid) ? NULL : $application_info->pwadvid;
		$row['rate_override'] = NULL; // UH-OH
		$row['residence_start_date'] = (! empty($applicant_info->residence_start_date)) ? date("Y-m-d", strtotime($applicant_info->residence_start_date)) : NULL;
		$row['rule_set_id'] = $application_info->ruleSetId;
		$row['shift'] = empty($employment_info->shift) ? NULL : $employment_info->shift;
		$row['ssn'] = $applicant_info->ssn;
		$row['ssn_last_four'] = $applicant_info->ssn_last_four;
		$row['state'] = $applicant_info->state;
		$row['street'] = $applicant_info->street;
		$row['tenancy_type'] = $applicant_info->tenancy_type;
		$row['track_id'] = $application_info->trackKey;
		$row['unit'] = empty($applicant_info->unit) ? NULL : $applicant_info->unit;
		$row['week_1'] = empty($employment_info->week_1) ? NULL : $employment_info->week_1;
		$row['week_2'] = empty($employment_info->week_2) ? NULL : $employment_info->week_2;
		$row['zip_code'] = $applicant_info->zip_code;

		return $row;
	}

	protected function getNativeColumnData_ldb($application_id)
	{
		$application_info = $this->getColumnData();

		$row = array();

		//$row['age'] = empty($application_info["dob"]) ? NULL : Date_Util_1::getYearsElapsed($application_info["dob"]);
		$row['age'] = empty($application_info["age"]) ? NULL : $application_info["age"];
		$row['agent_id'] = empty($application_info["modifying_agent_id"]) ? NULL : $application_info["modifying_agent_id"];
		$row['application_id'] = $application_id;
		$row['application_status_id'] = empty($application_info["application_status_id"]) ? NULL : $application_info["application_status_id"];
		$row['application_type'] = 'paperless'; // Deprecated
		$row['apr'] = empty($application_info["apr"]) ? NULL : $application_info["apr"];
		$row['bank_aba'] = empty($application_info["bank_aba"]) ? NULL : $application_info["bank_aba"];
		$row['bank_account'] = empty($application_info["bank_account"]) ? NULL : $application_info["bank_account"];
		$row['bank_account_type'] = empty($application_info["bank_account_type"]) ? NULL : $application_info["bank_account_type"];
		$row['bank_name'] = empty($application_info["bank_name"]) ? NULL : $application_info["bank_name"];
		$row['banking_start_date'] = empty($application_info["banking_start_date"]) ? NULL : $application_info["banking_start_date"];
		$row['call_time_pref'] = empty($application_info["call_time_pref"]) ? NULL : strtolower($application_info["call_time_pref"]);
		$row['cfe_rule_set_id'] = empty($application_info["cfe_rule_set_id"]) ? NULL : $application_info["cfe_rule_set_id"];
		$row['city'] = empty($application_info["city"]) ? NULL : $application_info["city"];
		$row['company_id'] = empty($application_info["company_id"]) ? NULL : $application_info["company_id"];
		$row['contact_method_pref'] = empty($application_info["contact_method_pref"]) ? NULL : $application_info["contact_method_pref"];
		$row['county'] = empty($application_info["county"]) ? NULL : $application_info["county"];
		$row['customer_id'] = empty($application_info["customer_id"]) ? NULL : $application_info["customer_id"];
		$row['date_application_status_set'] = empty($application_info["date_application_status_set"]) ? NULL : $application_info["date_application_status_set"];
		$row['date_created'] = empty($application_info["date_created"]) ? NULL : $application_info["date_created"];
		$row['date_first_payment'] = empty($application_info["date_first_payment"]) ? NULL : $application_info["date_first_payment"];
		$row['date_fund_actual'] = empty($application_info["date_fund_actual"]) ? NULL : $application_info["date_fund_actual"];
		$row['date_fund_estimated'] = empty($application_info["date_fund_estimated"]) ? NULL : $application_info["date_fund_estimated"];
		$row['date_hire'] = empty($application_info["date_hire"]) ? NULL : $application_info["date_hire"];
		$row['date_modified'] = empty($application_info["date_modified"]) ? NULL : $application_info["date_modified"];
		$row['date_next_contact'] = empty($application_info["date_next_contact"]) ? NULL : $application_info["date_next_contact"];
		$row['job_tenure'] = empty($application_info["job_tenure"]) ? NULL : $application_info["job_tenure"];
		$row['day_of_month_1'] = empty($application_info["day_of_month_1"]) ? NULL : $application_info["day_of_month_1"];
		$row['day_of_month_2'] = empty($application_info["day_of_month_2"]) ? NULL : $application_info["day_of_month_2"];
		$row['day_of_week'] = empty($application_info["day_of_week"]) ? NULL : strtolower($application_info["day_of_week"]);
		$row['dob'] = empty($application_info["dob"]) ? NULL : $application_info["dob"];
		$row['email'] = empty($application_info["email"]) ? NULL : $application_info["email"];
		$row['employer_name'] = empty($application_info["employer_name"]) ? NULL : $application_info["employer_name"];
		$row['enterprise_site_id'] = empty($application_info["enterprise_site_id"]) ? NULL : $application_info["enterprise_site_id"];
		$row['finance_charge'] = empty($application_info["finance_charge"]) ? NULL : $application_info["finance_charge"];
		if(!empty($application_info["fund_actual"]))
		{
			$row['fund_actual'] = $application_info["fund_actual"];
		}
		if(!empty($application_info["fund_qualified"]))
		{
			$row['fund_qualified'] = $application_info["fund_qualified"];
		}
		if(!empty($application_info["fund_requested"]))
		{
			$row['fund_requested'] = $application_info["fund_requested"];
		}
		$row['income_date_soap_1'] = empty($application_info["income_date_soap_1"]) ? NULL : $application_info["income_date_soap_1"];
		$row['income_date_soap_2'] = empty($application_info["income_date_soap_2"]) ? NULL : $application_info["income_date_soap_2"];
		$row['income_direct_deposit'] = empty($application_info["income_direct_deposit"]) ? NULL : $application_info["income_direct_deposit"];
		$row['income_frequency'] = empty($application_info["income_frequency"]) ? NULL : strtolower($application_info["income_frequency"]);
		$row['income_monthly'] = empty($application_info["income_monthly"]) ? NULL : $application_info["income_monthly"];
		$row['income_source'] = empty($application_info["income_source"]) ? NULL : $application_info["income_source"];
		$row['ip_address'] = empty($application_info["ip_address"]) ? NULL : $application_info["ip_address"];
		$row['is_react'] = empty($application_info["is_react"]) ? NULL : $application_info["is_react"];
		$row['is_watched'] = empty($application_info["is_watched"]) ? NULL : $application_info["is_watched"];
		$row['job_title'] = empty($application_info["job_title"]) ? NULL : $application_info["job_title"];
		$row['last_paydate'] = empty($application_info["last_paydate"]) ? NULL : $application_info["last_paydate"];
		$row['legal_id_number'] = empty($application_info["legal_id_number"]) ? NULL : $application_info["legal_id_number"];
		$row['legal_id_state'] = empty($application_info["legal_id_state"]) ? NULL : $application_info["legal_id_state"];
		$row['legal_id_type'] = empty($application_info["legal_id_type"]) ? NULL : $application_info["legal_id_type"];
		$row['loan_type_id'] = empty($application_info["loan_type_id"]) ? NULL : $application_info["loan_type_id"];
		$row['marketing_contact_pref'] = empty($application_info["marketing_contact_pref"]) ? NULL : $application_info["marketing_contact_pref"];
		$row['modifying_agent_id'] = empty($application_info["modifying_agent_id"]) ? NULL : $application_info["modifying_agent_id"];
		$row['name_first'] = empty($application_info["name_first"]) ? NULL : $application_info["name_first"];
		$row['name_last'] = empty($application_info["name_last"]) ? NULL : $application_info["name_last"];
		$row['name_middle'] = empty($application_info["name_middle"]) ? NULL : $application_info["name_middle"];
		$row['name_suffix'] = empty($application_info["name_suffix"]) ? NULL : $application_info["name_suffix"];
		$row['olp_process'] = empty($application_info["olp_process"]) ? NULL : $application_info["olp_process"];
		$row['paydate_model'] = empty($application_info["paydate_model"]) ? NULL : strtolower($application_info["paydate_model"]);
		$row['payment_total'] = empty($application_info["payment_total"]) ? NULL : $application_info["payment_total"];
		$row['phone_cell'] = empty($application_info["phone_cell"]) ? NULL : $application_info["phone_cell"];
		$row['phone_fax'] = empty($application_info["phone_fax"]) ? NULL : $application_info["phone_fax"];
		$row['phone_home'] = empty($application_info["phone_home"]) ? NULL : $application_info["phone_home"];
		$row['phone_work'] = empty($application_info["phone_work"]) ? NULL : $application_info["phone_work"];
		$row['phone_work_ext'] = empty($application_info["phone_work_ext"]) ? NULL : $application_info["phone_work_ext"];
		if(!empty($application_info["price_point"]))
		{
			$row['price_point'] = $application_info["price_point"];
		}
		$row['pwadvid'] = empty($application_info["pwadvid"]) ? NULL : $application_info["pwadvid"];
		$row['rate_override'] = empty($application_info["rate_override"]) ? NULL : $application_info["rate_override"];
		$row['residence_start_date'] = empty($application_info["residence_start_date"]) ? NULL : $application_info["residence_start_date"];
		$row['rule_set_id'] = empty($application_info["rule_set_id"]) ? NULL : $application_info["rule_set_id"];
		$row['shift'] = empty($application_info["shift"]) ? NULL : $application_info["shift"];
		$row['ssn'] = empty($application_info["ssn"]) ? NULL : $application_info["ssn"];
		$row['ssn_last_four'] = empty($application_info["ssn_last_four"]) ? NULL : $application_info["ssn_last_four"];
		$row['state'] = empty($application_info["state"]) ? NULL : $application_info["state"];
		$row['street'] = empty($application_info["street"]) ? NULL : $application_info["street"];
		$row['tenancy_type'] = empty($application_info["tenancy_type"]) ? NULL : $application_info["tenancy_type"];
		$row['track_id'] = empty($application_info["track_id"]) ? NULL : $application_info["track_id"];
		$row['unit'] = empty($application_info["unit"]) ? NULL : $application_info["unit"];
		$row['week_1'] = empty($application_info["week_1"]) ? NULL : $application_info["week_1"];
		$row['week_2'] = empty($application_info["week_2"]) ? NULL : $application_info["week_2"];
		$row['zip_code'] = empty($application_info["zip_code"]) ? NULL : $application_info["zip_code"];
		
		return $row;
	}

	/**
	 * selects from the model's table based on the where args
	 *
	 * @param array $where_args
	 * @return bool
	 */
	public function loadBy(array $where_args, $check_ldb = FALSE)
	{
		if(!isset($where_args['application_id']))
		{
			throw new ECash_Application_NotFoundException("Unable to locate application without using application_id!");
		}

		/**
		 * Temporary Hack.  The scrubber needs to know whether or not the
		 * row exists before it will try to write to it.  We don't really need
		 * to read any of the data though.
		 */
		if((! $result = parent::loadBy($where_args)))
		{
			return FALSE;
		}

		$application_id = $where_args['application_id'];
		$response = $this->getNativeColumnData($application_id);

		$new_column_data = array();

		if(isset($response['application_id']))
		{
			/**
			 * We shouldn't really *have* to do this check, but I
			 * don't trust developers to NOT add items to getNativeColumnData
			 * that aren't columns in this model.  While we're still using
			 * the database for writes, we should not try to shoe-horn things into
			 * this model.
			 */
			foreach($this->getColumns() as $column_name)
			{
				if(isset($response[$column_name]))
				{
					$new_column_data[$column_name] = $response[$column_name];
				}
			}

			$this->setColumnData($new_column_data);
			$this->column_data['last_paydate'] = date('Y-m-d', $this->column_data['last_paydate']);

			return TRUE;
		}

		return FALSE;
	}

	//asm 15
	protected function getApplicationInfoLDB($application_id, &$row)
	{
		$db = $this->getDatabaseInstance(self::DB_INST_READ);

		//application_contact
		$sql = "select ac.application_id, ac.application_contact_id, ac.value
		from application_contact ac
		where ac.application_id = {$application_id}
		and ac.type = 'co_borrower' limit 1";
		$stmt = $db->query($sql);
		if($title_info = $stmt->fetch(PDO::FETCH_OBJ))
		{
			$row['co_borrower'] = $title_info->value;
			$row['co_borrower_id'] = $title_info->application_contact_id;		
		}
		else
		{
			$row['co_borrower'] = '';
			$row['co_borrower_id'] = '';
		}

		//parent application
		$sql = "select application_id
			from react_affiliation
			where react_application_id = {$application_id}";
		$stmt = $db->query($sql);
		if($info = $stmt->fetch(PDO::FETCH_OBJ))
		{
			$row['parent_application_id'] = $info->application_id;
		}
		else
		{
			$row['parent_application_id'] = NULL;
		}
	}
}
?>
