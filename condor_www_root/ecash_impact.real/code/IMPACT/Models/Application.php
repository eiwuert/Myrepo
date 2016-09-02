<?php

class IMPACT_Models_Application extends ECash_Models_Application
{


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
			'modifying_agent_id', 'banking_start_date', 'residence_start_date',
			'county','cfe_rule_set_id','date_confirmed'
			);
		return $columns;
	}

	public function getColumnData()
	{
		$modified = parent::getColumnData();
		//mysql dates
		$modified['banking_start_date'] = date("Y-m-d", $modified['banking_start_date']);
		$modified['residence_start_date'] = date("Y-m-d", $modified['residence_start_date']);
		return $modified;
	}

	public function setColumnData($column_data)
	{
		parent::setColumnData($column_data);

		$this->column_data['banking_start_date'] = strtotime( $column_data['banking_start_date']);
		$this->column_data['residence_start_date'] = strtotime( $column_data['residence_start_date']);
	}

	public function loadLegacyAll($application_id, &$response, $server = NULL, array $override_dbs = NULL)
	{
		$company_id = ECash::getCompany()->company_id;

		/**
		 * @TODO Move this crap into company-specific models
		 */
		//aliases here that don't match this model's table should
		//have entries in $this->legacy_column_map
		$query = "
			SELECT  ap.application_id,
                ap.company_id,
                ap.customer_id,
                NULL as archive_db2_id,
                ap.archive_cashline_id,
                ap.application_status_id,
                ap.date_modified,
				ap.date_created,
                ap.is_react,
                ap.is_watched,
				ap.olp_process,
                ap.fund_actual,
				ap.date_first_payment,
				ap.date_fund_actual,
				ap.date_next_contact,
                ap.finance_charge,
                ap.payment_total,
                ap.apr,
                ap.income_direct_deposit,
                ap.income_monthly,
                ap.income_frequency,
                ap.income_source,
                ap.paydate_model,
                ap.day_of_week,
				ap.last_paydate,
                ap.day_of_month_1,
                ap.day_of_month_2,
                ap.week_1,
                ap.week_2,
                ap.bank_name,
                ap.bank_aba,
                ap.bank_account,
                ap.bank_account_type,
				ap.fund_qualified,
				ap.date_fund_estimated,
                ap.street,
                ap.unit,
                ap.city,
                ap.state,
				ap.zip_code,
                ap.tenancy_type,
                ap.ip_address,
                ap.name_first,
                ap.name_middle,
                ap.name_last,
                ap.name_suffix,
				ap.dob,
                ap.ssn,
                ap.employer_name,
                ap.job_title,
                ap.shift,
				ap.date_hire,
                ap.job_tenure,
                ap.phone_work,
                ap.phone_work_ext,
                ap.phone_home,
                ap.phone_cell,
                ap.phone_fax,
                ap.track_id,
                ap.call_time_pref,
                ap.legal_id_number,
                ap.legal_id_state,
				ap.email,
                ap.rule_set_id,
                ap.cfe_rule_set_id,
                ap.residence_start_date,
                ap.banking_start_date,
                ap.county,
                upper(c.name_short) as display_short,
                asf.level0_name as status_long,
                asf.level0 as status,
                asf.level1,
                asf.level2,
                asf.level3,
                asf.level4,
                asf.level5,
                lt.name AS loan_type_name,
                lt.name_short AS loan_type,
                lt.abbreviation AS loan_type_abbreviation,
                customer.login,
                customer.password as crypt_password,
                (
                    SELECT	COUNT(bank_account)-1
                    FROM	application AS b1
                    WHERE   b1.bank_aba     = ap.bank_aba
                    AND 	b1.bank_account = ap.bank_account
                    AND 	b1.company_id   = ap.company_id
                    GROUP BY bank_account, bank_aba, company_id
                ) AS aba_account_count,
                (
                    SELECT	COUNT(b1.ip_address)-1
                    FROM	application AS b1
                    WHERE   b1.ip_address     = ap.ip_address
                    AND b1.ip_address NOT IN ('') AND b1.ip_address IS NOT NULL
                    AND 	b1.company_id   = ap.company_id
                    GROUP BY ip_address
                ) AS ip_address_count,
                
                if (dnl.application_id = NULL, 1, 0) AS do_not_loan,
                if (ra.count > 0, true, false) AS has_reacts,
                ra.application_id as parent_application_id,
				(
					SELECT	DATE_FORMAT(sh.date_created, '%m-%d-%Y')
					FROM	status_history AS sh
					JOIN	application_status_flat asf ON (asf.application_status_id = sh.application_status_id)
					WHERE	(asf.level0='confirmed' AND asf.level1='prospect' AND asf.level2='*root')
					AND		sh.application_id   = ap.application_id
					GROUP BY sh.application_id
				) AS date_confirmed,
	            NULL AS fraud_rules,
                NULL AS fields,
                NULL AS fraud_fields,
                NULL AS risk_rules,
                NULL AS risk_fields
            FROM
                application ap
			JOIN company c ON (c.company_id = ap.company_id)
			LEFT JOIN application_status_flat asf ON ap.application_status_id = asf.application_status_id
			LEFT JOIN customer ON ap.customer_id = customer.customer_id
			LEFT JOIN loan_type lt ON (ap.loan_type_id = lt.loan_type_id)
			LEFT JOIN (
				select afa.field_name, table_row_id AS application_id
				from application_field af
				join application_field_attribute afa on (af.application_field_attribute_id = afa.application_field_attribute_id)
				where table_name = 'application'
				AND table_row_id  = {$application_id}
				and field_name = 'do_not_loan'
			) dnl ON (ap.application_id = dnl.application_id)
			LEFT JOIN (
				SELECT COUNT(react_application_id) as count,
					react_application_id as application_id
				FROM react_affiliation
				WHERE application_id   = {$application_id}
				GROUP BY react_application_id
			) ra ON (ra.application_id = ap.application_id) ";

		$query .= "
				WHERE
					ap.application_id	= {$application_id}
				AND ap.company_id = {$company_id}
		";

//		$base = new self();
//		$base->setOverrideDatabases($override_dbs);
		if (($row = $this->getDatabaseInstance(self::DB_INST_READ)->querySingleRow($query)) !== FALSE)
		{
			// Set the lock layer
			unset($_SESSION['LOCK_LAYER']['App_Info']);
			$_SESSION['LOCK_LAYER']['App_Info'][$application_id]['date_modified'] = $row->date_modified;

			//save application data
			$this->fromDbRow($row);

			//this loads the front-end display vars
			ECash::getFactory()->getDisplay('LegacyApplication')->loadAll($row, $response);

			return $this;
		}
		return NULL;
	}

}

?>
