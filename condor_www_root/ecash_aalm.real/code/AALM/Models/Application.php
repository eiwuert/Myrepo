<?php

class AALM_Models_Application extends ECash_Models_Application
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
	
	/**
	 * We should try to stop using this and switch to biz objects & models
	 * 
	 * @depricated
	 */
	public function loadLegacyAll($application_id, &$response, $server = NULL, array $override_dbs = NULL)
	{
		$company_id = ECash::getCompany()->company_id;

		/**
		 * @TODO Move this crap into company-specific models
		 */
		$restrictions = NULL;
		if(file_exists(CUSTOMER_LIB . "application_restrict.func.php"))
		{
			require_once(CUSTOMER_LIB . "application_restrict.func.php");
			$restrictions = getSearchControlRestrictions($server);
		}

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
				-- below here I don't care what you do for formatting as long as they don't match columns in application [JustinF]
                (case
                    when c.name_short = 'd1' then '5FC'
                    when c.name_short = 'pcl' then 'OCC'
                    when c.name_short = 'ca' then 'AML'
                    else upper(c.name_short)
                end) as display_short,
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
                aac.count as aba_account_count,
                site.name AS web_url,
                camp.promo_id,
                date_format(stathist.date_created, '%m-%d-%Y') AS date_confirmed,
                if (dnl.application_id = NULL, 1, 0) AS do_not_loan,
                fr.fraud_rules,
                ff.fields,
                ff.fields AS fraud_fields,
                rr.risk_rules,
                rf.risk_fields,
                if (ra.count > 0, true, false) AS has_reacts,
                ra.application_id as parent_application_id

            FROM
                application ap
			JOIN company c ON (c.company_id = ap.company_id)
			LEFT JOIN application_status_flat asf ON ap.application_status_id = asf.application_status_id
			LEFT JOIN customer ON ap.customer_id = customer.customer_id
			LEFT JOIN loan_type lt ON (ap.loan_type_id = lt.loan_type_id)
			LEFT JOIN (
				SELECT
					COUNT(bank_account)-1 as count, bank_aba, bank_account, company_id
				FROM
					application
				GROUP BY bank_account,bank_aba, company_id
			) aac on (ap.bank_aba = aac.bank_aba AND ap.bank_account = aac.bank_account AND ap.company_id = aac.company_id)
			LEFT JOIN (
				SELECT
					MIN(date_effective) as date_next_payment,
					application_id
				FROM
					event_schedule es
				WHERE
					(es.amount_principal < 0 OR es.amount_non_principal < 0)
					AND es.event_status     = 'scheduled'
				AND application_id   = {$application_id}
					GROUP BY application_id
				) nsp ON (ap.application_id = nsp.application_id)
			-- LEFT JOIN site ON ap.enterprise_site_id = site.site_id
			LEFT JOIN (
				SELECT MIN(campaign_info_id) as promo_id, application_id, site_id
				FROM campaign_info cref
				WHERE application_id   = {$application_id}
				GROUP BY application_id
			) camp ON (ap.application_id = camp.application_id)
			LEFT JOIN (
				SELECT
					sh.date_created         AS date_created,
					sh.application_id
				FROM
					status_history sh
				JOIN
					application_status_flat asf ON (asf.application_status_id = sh.application_status_id)
				WHERE
					(asf.level0='confirmed' AND asf.level1='prospect' AND asf.level2='*root')
				AND
					application_id   = {$application_id}
				GROUP BY
					sh.application_id
			) stathist ON (ap.application_id = stathist.application_id)
			LEFT JOIN (
				select afa.field_name, table_row_id AS application_id
				from application_field af
				join application_field_attribute afa on (af.application_field_attribute_id = afa.application_field_attribute_id)
				where table_name = 'application'
				AND table_row_id  = {$application_id}
				and field_name = 'do_not_loan'
			) dnl ON (ap.application_id = dnl.application_id)
			LEFT JOIN (
				SELECT GROUP_CONCAT(fr.name SEPARATOR ';') as fraud_rules, fa.application_id
				FROM fraud_rule fr
				JOIN fraud_application fa on (fa.fraud_rule_id = fr.fraud_rule_id)
				WHERE fr.rule_type = 'FRAUD'
				AND application_id   = {$application_id}
				GROUP BY fa.application_id
			) fr ON (fr.application_id = ap.application_id)
			LEFT JOIN (
				SELECT GROUP_CONCAT(af.column_name SEPARATOR ',') AS fields,
					af.table_row_id as application_id
				FROM application_field af
				JOIN application_field_attribute afa on (afa.application_field_attribute_id = af.application_field_attribute_id)
				WHERE af.table_name = 'application'
				AND afa.field_name = 'fraud'
				AND af.table_row_id   = {$application_id}
				GROUP BY af.table_row_id
			) ff ON (ff.application_id = ap.application_id)
			LEFT JOIN (
				SELECT GROUP_CONCAT(fr.name SEPARATOR ';') AS risk_rules,
					fa.application_id
				FROM fraud_rule fr
				JOIN fraud_application fa on (fa.fraud_rule_id = fr.fraud_rule_id)
				WHERE fr.rule_type = 'RISK'
				AND application_id   = {$application_id}
				GROUP BY fa.application_id
			) rr ON (rr.application_id = ap.application_id)
			LEFT JOIN (
				SELECT GROUP_CONCAT(af.column_name SEPARATOR ',') as risk_fields,
					af.table_row_id as application_id
				FROM application_field af
				INNER JOIN application_field_attribute afa on (afa.application_field_attribute_id = af.application_field_attribute_id)
				WHERE af.table_name = 'application'
				AND afa.field_name = 'high_risk'
				AND af.table_row_id  = {$application_id}
				GROUP BY af.table_row_id
			) rf ON (rf.application_id = ap.application_id)
			LEFT JOIN (
				SELECT COUNT(react_application_id) as count,
					react_application_id as application_id
				FROM react_affiliation
				WHERE application_id   = {$application_id}
				GROUP BY react_application_id
			) ra ON (ra.application_id = ap.application_id)


                LEFT JOIN site on (site.site_id = camp.site_id)
	";
	
		if($restrictions)
		{
			foreach($restrictions['join'] as $join_text)
			{
				$query .= "JOIN " . $join_text;
			}
		}
		
		$query .= "
				WHERE
					ap.application_id	= {$application_id}
				AND ap.company_id = {$company_id}
		";

		if($restrictions)
		{
			foreach($restrictions['where'] as $where_text)
			{
				$query .= "
					AND
						{$where_text}";
			}
		}
		if (($row = DB_Util_1::querySingleRow($this->getDatabaseInstance(), $query)) !== FALSE)
		{                       
			// Set the lock layer
			unset($_SESSION['LOCK_LAYER']['App_Info']);
			$_SESSION['LOCK_LAYER']['App_Info'][$application_id]['date_modified'] = $row->date_modified;
			
			//save application data
			$this->fromDbRow($row);
			
			//this loads the front-end display vars
			ECash::getFactory()->getDisplay('LegacyApplication')->loadAll($row, $response);
			
			return TRUE;
		}
		return FALSE;
	}
	
}

?>
