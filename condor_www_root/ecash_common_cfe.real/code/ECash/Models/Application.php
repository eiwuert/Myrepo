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
			'application_id', 'customer_id', 'archive_db2_id',
			'archive_mysql_id', 'archive_cashline_id', 'login_id',
			'is_react', 'loan_type_id', 'rule_set_id',
			'enterprise_site_id', 'application_status_id',
			'date_application_status_set', 'date_next_contact',
			'ip_address', 'application_type', 'bank_name', 'bank_aba',
			'bank_account', 'bank_account_type', 'date_fund_estimated',
			'date_fund_actual', 'date_first_payment', 'fund_requested',
			'fund_qualified', 'fund_actual', 'finance_charge',
			'payment_total', 'apr', 'income_monthly', 'income_source',
			'income_direct_deposit', 'income_frequency',
			'income_date_soap_1', 'income_date_soap_2', 'paydate_model',
			'day_of_week', 'last_paydate', 'day_of_month_1',
			'day_of_month_2', 'week_1', 'week_2', 'track_id',
			'agent_id', 'agent_id_callcenter', 'dob', 'ssn',
			'legal_id_number', 'legal_id_state', 'legal_id_type',
			'identity_verified', 'email', 'email_verified', 'name_last',
			'name_first', 'name_middle', 'name_suffix', 'street',
			'unit', 'city', 'state', 'zip_code', 'tenancy_type',
			'phone_home', 'phone_cell', 'phone_fax', 'call_time_pref',
			'contact_method_pref', 'marketing_contact_pref',
			'employer_name', 'job_title', 'supervisor', 'shift',
			'date_hire', 'job_tenure', 'phone_work', 'phone_work_ext',
			'work_address_1', 'work_address_2', 'work_city',
			'work_state', 'work_zip_code', 'employment_verified',
			'pwadvid', 'olp_process', 'is_watched', 'schedule_model_id',
			'modifying_agent_id', 'county', 'cfe_rule_set_id',
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
		$modified['date_fund_estimated'] = date("Y-m-d", $modified['date_fund_estimated']);		
		$modified['date_fund_actual'] = $modified['date_fund_actual'] === NULL ? NULL : date("Y-m-d", $modified['date_fund_actual']);
		$modified['date_first_payment'] = $modified['date_first_payment'] === NULL ? NULL : date("Y-m-d", $modified['date_first_payment']);		
		$modified['income_date_soap_1'] = $modified['income_date_soap_1'] === NULL ? NULL : date("Y-m-d", $modified['income_date_soap_1']);
		$modified['income_date_soap_2'] = $modified['income_date_soap_2'] === NULL ? NULL : date("Y-m-d", $modified['income_date_soap_2']);
		$modified['last_paydate'] = $modified['last_paydate'] === NULL ? NULL : date("Y-m-d", $modified['last_paydate']); 
		$modified['dob'] = date("Y-m-d", $modified['dob']);
		$modified['date_hire'] = date("Y-m-d", $modified['date_hire']);

		return $modified;
	}

	public function setColumnData($column_data)
	{
		//mysql timestamps
		$column_data['date_modified'] = strtotime( $column_data['date_modified']);
		$column_data['date_created'] = strtotime( $column_data['date_created']);
		$column_data['date_application_status_set'] = strtotime( $column_data['date_application_status_set']);
		$column_data['date_next_contact'] = $column_data['date_next_contact'] === NULL ? NULL : strtotime( $column_data['date_next_contact']);

		//mysql dates
		$column_data['date_fund_estimated'] = strtotime( $column_data['date_fund_estimated']);
		$column_data['date_fund_actual'] = ($column_data['date_fund_actual'] === NULL || $column_data['date_fund_actual'] === '0000-00-00') ? NULL : strtotime($column_data['date_fund_actual']);
		$column_data['date_first_payment'] = $column_data['date_first_payment'] === '0000-00-00' ? NULL : strtotime($column_data['date_first_payment']);
		$column_data['income_date_soap_1'] = $column_data['income_date_soap_1'] === NULL ? NULL : strtotime( $column_data['income_date_soap_1']);
		$column_data['income_date_soap_2'] = $column_data['income_date_soap_2'] === NULL ? NULL : strtotime( $column_data['income_date_soap_2']);
		$column_data['last_paydate'] = $column_data['last_paydate'] === NULL ? NULL : strtotime($column_data['last_paydate']);
		$column_data['dob'] = strtotime($column_data['dob']);
		$column_data['date_hire'] = strtotime( $column_data['date_hire']);

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

		if (count($this->altered_columns))
		{
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
		
			if(isset($_SESSION['LOCK_LAYER']['App_Info'][$this->application_id]) && $_SESSION['LOCK_LAYER']['App_Info'][$this->application_id]['date_modified'] != false)
			{
				if(is_numeric($_SESSION['LOCK_LAYER']['App_Info'][$this->application_id]['date_modified']))
					$query .= " AND date_modified = FROM_UNIXTIME('" . $_SESSION['LOCK_LAYER']['App_Info'][$this->application_id]['date_modified'] ."')";
				else
					$query .= " AND date_modified = FROM_UNIXTIME('" . strtotime($_SESSION['LOCK_LAYER']['App_Info'][$this->application_id]['date_modified']) ."')";
			}

			$st = $db->prepare($query);
			$st->execute(
				array_merge(
					array_values($modified),
					array_values($pk)
				)
			);
						
			
			//we now update the lock layer stuff so that the application can be updated multiple times per request
			//that's not ideal, but sometimes that's what good coding with a bad base requires -jeffd
			if(isset($_SESSION['LOCK_LAYER']['App_Info'][$this->application_id]) && $_SESSION['LOCK_LAYER']['App_Info'][$this->application_id]['date_modified'] != false)
			{
				$this->loadBy(array("application_id" => $this->application_id));
				$_SESSION['LOCK_LAYER']['App_Info'][$this->application_id]['date_modified'] = date('Y-m-d H:i:s',$this->date_modified);
			}

			$this->affected_row_count = $st->rowCount();
			$this->setDataSynched();

			$event = new stdClass();
			$event->type = self::EVENT_UPDATE;
			$this->notifyObservers($event);

		}
		
		return $this->affected_row_count;
	}

}
?>
