<?php

/**
 * ECash Commercial Application Sevice Query Class
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @package
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

class ECash_Service_ApplicationService_Queries
{

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	public function __construct() {
		$this->db = ECash::getAppSvcDB();
	}

	/**
	 * Finds the aalm data set application table entry using the authoritative id (i.e. ecash application id.) 
	 *
	 * @returns single query result row object
	 */
	public function findApplication($application_id) {
		$query = 'SELECT ap.application_id AS application_id,
			ap.apr AS apr,
			ap.cfe_rule_set_id AS cfe_ruleset_id,
			ap.company_id AS company_id,
			ap.customer_id AS customer_id,
			ap.date_application_status_set AS date_application_status_set,
			ap.date_created AS date_created,
			ap.date_first_payment AS date_first_payment,
			if(ap.date_fund_actual>"1990-01-01",ap.date_fund_actual,NULL) AS date_fund_actual,
			ap.date_fund_estimated AS date_fund_estimated,
			ap.date_modified AS date_modified,
			ap.date_next_contact AS date_next_contact,
			ap.enterprise_site_id AS enterprise_site_id,
			ap.external_id AS external_id,
			ap.finance_charge AS finance_charge,
			ap.fund_actual AS fund_actual,
			ap.fund_qualified AS fund_qualified,
			ap.fund_requested AS fund_requested,
			ap.ip_address AS ip_address,
			ap.esig_ip_address AS esig_ip_address,
			ap.is_react AS is_react,
			ap.is_watched AS is_watched,
			ap.loan_type_id AS loan_type_id,
			ap.modifying_agent_id AS modifying_agent_id,
			ap.payment_total AS payment_total,
			ap.price_point AS price_point,
			ap.rule_set_id AS rule_set_id,
			ap.track_id AS track_key,
			ap.application_status_id AS application_status_id,
			ap.call_time_pref AS call_time_pref,
			ap.contact_method_pref AS contact_method_pref,
			ap.marketing_contact_pref AS marketing_contact_pref,
			ap.olp_process AS olp_process,
			ap.application_type AS application_type,
			ap.application_id AS application_id,
			ap.source AS application_source
		FROM application AS ap WHERE ap.application_id = '.$application_id.';';

		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Finds and returns an application set that meets a specific criteria (field names are structured through the query).
	 *
	 * @returns query result set object
	 */
	public function bigQuery($where_clause) {
		
		$query = 'SELECT
				ap.company_id AS company_id,
				ap.cfe_rule_set_id AS cfe_ruleset_id,
				ap.olp_process AS olp_process,
				ap.application_type AS application_type,
				ap.source AS application_source,
				ap.rule_set_id AS rule_set_id,
				ap.track_id AS track_key,
				ap.application_id AS application_id,
				ap.name_first AS name_first,
				ap.name_last AS name_last,
				ap.date_created AS date_created,
				ap.date_modified AS date_modified,
				ap.application_status_id AS application_status_id,
				ast.application_status_name AS application_status,
				ap.date_application_status_set AS date_application_status_set,
				ap.date_first_payment AS date_first_payment,
				ap.date_fund_estimated AS date_fund_estimated,
				if(ap.date_fund_actual>"1990-01-01",ap.date_fund_actual,NULL) AS date_fund_actual,
				ap.enterprise_site_id AS enterprise_site_id,
				ap.external_id AS external_id,
				ap.fund_actual AS fund_actual,
				ap.fund_qualified AS fund_qualified,
				ap.finance_charge AS finance_charge,
				ap.apr AS apr,
				ap.ip_address AS ip_address,
				ap.esig_ip_address AS esig_ip_address,
				ap.is_react AS is_react,
				ap.is_watched AS is_watched,
				ap.loan_type_id AS loan_type_id,
				ap.modifying_agent_id AS modifying_agent_id,
				ap.payment_total AS payment_total,
				ap.price_point AS price_point,
				ap.call_time_pref AS call_time_pref,
				ap.contact_method_pref AS contact_method_pref,
				ap.marketing_contact_pref AS marketing_contact_pref,
				ap.age AS age,
				ap.dob AS date_of_birth,
				ap.legal_id_type as legal_id_type_name,
				ap.legal_id_number,
				ap.legal_id_state AS legal_id_state,
				ap.street AS street,
				ap.city AS city,
				ap.state AS state,
				ap.zip_code AS zip_code,
				ap.tenancy_type AS tenancy_type,
				aa.login AS login,
				aa.'password' AS password,
				ap.bank_info_id AS bank_info_id,
				ap.bank_aba AS bank_aba,
				ap.bank_account AS bank_account_normal,
				ap.bank_account AS bank_account,
                                ap.income_direct_deposit AS is_direct_deposit_old,
				IF(ap.income_direct_deposit = "yes", TRUE, FALSE) as is_direct_deposit,
				ap.banking_start_date AS banking_start_date,
				ap.bank_name AS bank_name,
				ap.phone_home AS primary_phone,
				"phone_home" AS primary_phone_type,
				"" AS primary_phone_notes,
				ap.email AS email,
				"" AS email_notes,
				substr(ap.ssn,7,4) AS ssn_last4,
				ap.ssn AS social_security_number
			FROM application AS ap
				JOIN applicant_account AS aa USING (applicant_account_id)
				JOIN application_status AS ast USING (application_status_id)
				'.$where_clause;
				
		$result = $this->db->query($query);
		return($result->fetchAll(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Finds and returns an application set that meets a specific criteria (field names are structured through the query).
	 *
	 * @returns query result set object
	 */
	public function searchApplication($where_clause) {

		$query = 'SELECT
				ap.application_id AS application_id,
				ap.date_created AS date_created,
				ap.company_id AS company_id,
				ap.loan_type_id AS loan_type_id,
				aa.applicant_account_id AS customer_id,
				aa.applicant_account_id AS applicant_account_id,
				ap.external_id AS external_id,
				ap.name_first AS name_first,
				ap.name_last AS name_last,
				ap.street AS street,
				ap.city AS city,
				ap.state AS state,
				ap.unit AS unit,
				ap.ssn AS social_security_number,
				ap.ssn AS ssn,
				ast.application_status_name AS application_status,
				if(ap.date_fund_actual>"1990-01-01",ap.date_fund_actual,NULL) AS date_fund_actual,
				substr(ap.ssn,7,4) AS ssn_last4,
				ap.zip_code AS zip_code,
				ap.bank_aba AS bank_aba,
				ap.bank_account AS bank_account,
				ap.ip_address AS ip_address,
				ap.esig_ip_address AS esig_ip_address,
				ap.email AS email,
				ap.track_id AS track_key,
				ap.is_react AS is_react,
				ap.date_fund_actual AS date_fund_actual

			FROM application AS ap
				JOIN application_status AS ast USING (application_status_id)
				JOIN applicant_account AS aa USING (applicant_account_id)
				'.$where_clause.'
				ORDER BY date_created DESC';
                $result = $this->db->query($query);
		$return = $result->fetchAll(DB_IStatement_1::FETCH_OBJ);
		return($return);
	}

	/**
	 * Returns the applicant account by looking up the login id and password
	 *
	 * @returns single query result row object
	 */
	public function findByLoginAndPassword($login,$password) {
		$query = 'SELECT * FROM applicant_account WHERE login = "'.$login.'" and password ="'.$password.'";';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Sets the applicant account (login and pw) for an applicant associated with an application_id.
	 *
	 * @returns boolean
	 */
	public function setApplicantAccount($application_ids,$ap_acnt_id) {
		if (is_array($application_ids)) $application_ids = implode(',',$application_ids);
		$query = 'UPDATE application SET applicant_account_id ='.$ap_acnt_id.' WHERE application_id IN ('.$application_ids.');';
		$result = $this->db->query($query);
		return($result->execute());
	}

	/**
	 * Gathers basic application table info by application_id.
	 *
	 * @returns single query result row object
	 */
	public function getApplicationQuery($application_id) {
		$query = 'SELECT
				ap.application_id AS application_id,
				ap.application_status_id AS application_status_id,
				ast.application_status_name AS application_status_name,
				ap.apr AS apr,
				ap.date_application_status_set AS date_application_status_set,
				ap.date_created AS date_created,
				ap.date_first_payment AS date_first_payment,
				if(ap.date_fund_actual>"1990-01-01",ap.date_fund_actual,NULL) AS date_fund_actual,
				ap.date_fund_estimated AS date_fund_estimated,
				ap.date_modified AS date_modified,
				ap.enterprise_site_id AS enterprise_site_id,
				ap.external_id AS external_id,
				ap.finance_charge AS finance_charge,
				ap.fund_actual AS fund_actual,
				ap.fund_qualified AS fund_qualified,
				ap.ip_address AS ip_address,
				ap.esig_ip_address AS esig_ip_address,
				ap.is_react AS is_react,
				ap.is_watched AS is_watched,
				ap.loan_type_id AS loan_type_id,
				ap.modifying_agent_id AS modifying_agent_id,
				ap.payment_total AS payment_total,
				ap.price_point AS price_point,
				ap.rule_set_id AS rule_set_id,
				ap.track_id AS track_key,
				ap.cfe_rule_set_id AS cfe_ruleset_id,
				ap.company_id AS company_id,
				ap.call_time_pref AS call_time_pref,
				ap.contact_method_pref AS contact_method_pref,
				ap.marketing_contact_pref AS marketing_contact_pref,
				ap.olp_process AS olp_process,
				ap.application_type AS application_type,
				ap.source AS application_source
			FROM application AS ap
				JOIN application_status AS ast USING (application_status_id)
			WHERE application_id = '.$application_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gathers all application data related to updates by application_id.
	 *
	 * @returns single query result row object
	 */
	public function getApplicationDetailsQuery($application_id) {
		$query = 'SELECT
				ap.application_id AS application_id,
				ap.apr AS apr,
				ap.customer_id AS customer_id,
				ap.modifying_agent_id AS modifying_agent_id,
				ap.cfe_rule_set_id AS cfe_ruleset_id,
				ap.date_first_payment AS date_first_payment,
				if(ap.date_fund_actual>"1990-01-01",ap.date_fund_actual,NULL) AS date_fund_actual,
				ap.date_fund_estimated AS date_fund_estimated,
				ap.date_next_contact AS date_next_contact,
				ap.finance_charge AS finance_charge,
				ap.fund_actual AS fund_actual,
				ap.fund_qualified AS fund_qualified,
				ap.fund_requested AS fund_requested,
				ap.is_watched AS is_watched,
				ap.payment_total AS payment_total,
				ap.rule_set_id AS rule_set_id,
				ap.date_modified AS date_modified,
				ap.call_time_pref AS call_time_pref,
				ap.contact_method_pref AS contact_method_pref,
				ap.marketing_contact_pref AS marketing_contact_pref,
				ap.call_time_pref AS call_time_pref,
				ap.contact_method_pref AS contact_method_pref,
				ap.marketing_contact_pref AS marketing_contact_pref,
				ap.source AS application_source,
				ap.application_type AS application_type,
				IF (av.version IS NULL,1,av.version) AS application_version
			FROM application AS ap
				LEFT JOIN application_version AS av ON (ap.application_id = av.application_id)
			WHERE ap.application_id = '.$application_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the entry from the authoritative id table for the application_id.
	 *
	 * @returns single query result row object
	 */
	public function getApplicationAuthoritativeQuery($application_id) {
		$query = 'SELECT authoritative_id AS authoritativeId,
				company_id AS companyId,
				date_created AS dateCreated
			FROM authoritative_ids
			WHERE authoritative_id = '.$application_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the application version table entry details for the application_id.
	 *
	 * @returns single query result row object
	 */
	public function getApplicationVersionQuery($application_id) {
		$query = 'SELECT application_id AS applicationiId,
				date_created AS dateCreated,
				date_modified AS dateModified,
				version AS version
			FROM application_version
			WHERE application_id = '.$application_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the applicant account table entry detail for the application_id.
	 *
	 * @returns single query result row object
	 */
	public function getApplicantAccountQuery($application_id) {
		$query = 'SELECT aa.applicant_account_id AS applicant_account_id,
				aa.applicant_account_id AS customer_id,
				ap.application_id AS application_id,
				aa.password AS password,
				aa.login AS login,
				aa.modifying_agent_id AS modifying_agent_id
			FROM application AS ap
				JOIN applicant_account AS aa USING (applicant_account_id)
			WHERE ap.application_id = '.$application_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the application ids for all applications related to a customer id (applicant_account_id).
	 *
	 * @returns single query result row object
	 */
	public function getApplicationIdsForCustomerQuery($customer_id) {
		$query = 'SELECT ap.application_id AS application_id
			FROM application AS ap
			WHERE ap.applicant_account_id = '.$customer_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the personal references for an application.
	 *
	 * @returns single query result row object
	 */
	public function getApplicationPersonalReferencesQuery($application_id) {
		$query = 'SELECT pr.date_created AS date_created,
				pr.application_id AS application_id,
				pr.date_modified AS date_modified,
				pr.personal_reference_id AS personal_reference_id,
				pr.application_id AS application_id,
				pr.company_id AS company_id,
				pr.name_full AS name_full,
				pr.phone_home AS phone_home,
				pr.relationship AS relationship,
				pr.ok_to_contact AS ok_to_contact
			FROM personal_reference AS pr
			WHERE pr.application_id = '.$application_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetchAll(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the applicant account table entry detail for the application_id.
	 *
	 * @returns single query result row object
	 */
	public function getApplicantQuery($application_id) {
		$query = 'SELECT ap.date_modified AS date_modified,
				ap.date_created AS date_created,
				ap.application_id AS application_id,
				ap.external_id AS external_id,
				ap.legal_id_type AS legal_id_type,
				ap.tenancy_type AS tenancy_type,
				ap.dob AS date_of_birth,
				ap.dob AS dob,
				0 AS date_of_birth_id,
				ap.legal_id_number AS legal_id_number,
				0 AS legal_id_number_id,
				ap.legal_id_state AS legal_id_state,
				ap.ssn AS ssn,
				substr(ap.ssn,7,4) AS ssn_last_four,
				ap.name_last AS name_last,
				ap.name_first AS name_first,
				ap.street AS street,
				ap.city AS city,
				ap.county AS county,
				ap.unit AS unit,
				ap.state AS state,
				ap.zip_code AS zip_code,				
				ap.dob AS dob,
				ap.age AS age,
				ap.residence_start_date AS residence_start_date,
				substr(ap.ssn,7,4) AS ssn_last4,
				IF (av.version IS NULL,1,av.version) AS application_version,
				ap.modifying_agent_id AS modifying_agent_id
			FROM application AS ap
				LEFT JOIN application_version AS av USING (application_id)
			WHERE ap.application_id = '.$application_id.';';

		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the applicant account table entry detail for the application_id
	 *
	 * @returns single query result row object
	 */
	public function getApplicationAuditQuery($application_id) {
		$query = 'SELECT aa.application_id AS application_id,
				aa.table_name AS table_name,
				"application_id" AS primary_key_name,
				aa.application_id AS primary_key_value,
				aa.column_name AS column_name,
				aa.value_before AS old_value,
				aa.value_after AS new_value,
				aa.date_created AS date_updated,
				aa.agent_id AS modifying_agent_id
			FROM application_audit AS aa
			WHERE aa.application_id = '.$application_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetchAll(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the application history table entry detail for the application_id
	 *
	 * @returns query result set object
	 */
	public function getApplicationStatusHistoryQuery($application_id) {
		$result = array();
		$query = 'SELECT ash.application_id AS application_id,
				ash.date_created AS dateCreated,
				ash.agent_id AS modifyingAgentId,
				ash.agent_id AS modifying_agent_id,
				ash.status_history_id AS statusHistoryId,
				ash.application_status_id AS id
			FROM status_history AS ash 
			WHERE ash.application_id = '.$application_id.
			' ORDER BY ash.date_created;';
		$result = $this->db->query($query);
		$return = $result->fetchAll(DB_IStatement_1::FETCH_OBJ);
		return($return);
	}

	/**
	 * Gets the application status table entry detail for the application_status_id.
	 *
	 * @returns single query result row object
	 */
	public function getApplicationStatusQuery($application_status_id) {
		$query = 'SELECT date_created AS dateCreated,
				date_modified AS dateModified,
				application_status_id AS id,
				application_status_name AS name
			FROM application_status
			WHERE application_status_id = '.$application_status_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the bank information details for the application_id
	 *
	 * @returns single query result row object
	 */
	public function getBankQuery($application_id) {
		$query = 'SELECT ap.application_id AS application_id,
				ap.date_created AS date_created,
				ap.date_modified AS date_modified,
				IF (av.version IS NULL,1,av.version) AS version,
				0 AS bank_info_id,
				ap.bank_aba AS bank_aba,
				ap.bank_account AS bank_account_normal,
				ap.bank_account AS bank_account,
                                ap.income_direct_deposit AS is_direct_deposit_old,
				ap.income_direct_deposit AS income_direct_deposit,
				IF(ap.income_direct_deposit = "yes", TRUE, FALSE) as is_direct_deposit,
				ap.banking_start_date AS banking_start_date,
				ap.bank_name AS bank_name,
				ap.bank_account_type AS bank_account_type,
				ap.modifying_agent_id AS modifying_agent_id
			FROM application AS ap
				LEFT JOIN application_version AS av USING (application_id)
			WHERE ap.application_id = '.$application_id.' LIMIT 1;';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the campaign information details for the application_id
	 *
	 * @returns query result set object
	 */
	public function getCampaignQuery($application_id) {
		$query = 'SELECT cmi.campaign_info_id AS campaign_info_id,
				cmi.application_id AS application_id,
				cmi.campaign_name AS campaign_name,
				st.name AS friendly_name,
				cmi.promo_id AS promo_id,
				cmi.promo_sub_code AS promo_sub_code,
				st.name AS site,
				st.license_key AS license_key,
				cmi.application_id AS application_id
			FROM campaign_info AS cmi
				JOIN site AS st USING (site_id)
			WHERE cmi.application_id = '.$application_id.' LIMIT 1;';
		
		$result = $this->db->query($query);
                $rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
	        
		return($rows);
	}

	/**
	 * Finds a specific contact_info entry by application_id and contact type.
	 *
	 * @returns single query result row object
	 */
	public function getContactInfoQuery($application_id,$contact_type = false) {
		$primary = false;
		if ($contact_type == "primary") {
			$primary = true;
			$query = 'SELECT ap.contact_method_pref AS contact_type
				FROM application AS ap
				WHERE ap.application_id = '.$application_id;
			$result = $this->db->query($query);
			$row = $result->fetch(DB_IStatement_1::FETCH_OBJ);
			if ($row->contact_type == 'no preference') $contact_type = 'phone_home';
			else $contact_type = $row->contact_type;
			
		}
		if (!$contact_type) $contact_type_ary = array('phone_cell','phone_home','phone_fax','email','phone_work');
		else $contact_type_ary = array($contact_type);
		$rows = array();
		foreach($contact_type_ary as $contact_type){
			
			$contact_type_name_string = ', ap.'.$contact_type.' AS contact_info_value';
			$query = 'SELECT ap.application_id AS contact_info_id,
					ap.application_id AS application_id,
					"'.$contact_type.'" AS contact_type,
					ap.modifying_agent_id AS modifying_agent_id'.
					$contact_type_name_string.'
				FROM application AS ap
				WHERE ap.application_id = '.$application_id;
			$result = $this->db->query($query);
			$row = $result->fetch(DB_IStatement_1::FETCH_OBJ);
			$rows[] = $row;
		}
		return($rows);
	}

	/**
	 * Gets the do not loan audit details for a ssn.
	 *
	 * @returns query result set object
	 */
	public function getDoNotLoanAuditQuery($ssn) {
		$query = 'SELECT dl.ssn AS ssn,
				dl.company_id AS company_id,
				dl.value_before AS old_value,
				dl.value_after AS new_value,
				dl.table_name AS table_name,
				dl.agent_id AS modifying_agent_id,
				dl.date_created AS date_created
			FROM do_not_loan_audit AS dl
			WHERE dl.ssn = '.$ssn.'
			ORDER BY dl.date_created;';

		$result = $this->db->query($query);
		return($result->fetchAll(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Gets the last modified do not loan audit flag for a ssn.
	 *
	 * @returns single query result row object
	 */
	public function getDoNotLoanFlagQuery($ssn) {
		$query = 'SELECT dl.ssn AS ssn,
				ct.name AS category,
				dl.company_id AS company_id,
				dl.other_reason AS other_reason,
				dl.explanation AS explanation,
				dl.agent_id AS modifying_agent_id,
				if(dl.active_status = "inactive",false,true) AS active_status,
				dl.active_status AS active_status_new,
				dl.date_created AS date_created,
				dl.date_modified AS date_modified
			FROM do_not_loan_flag AS dl 
				JOIN do_not_loan_flag_category AS ct USING (category_id)
			WHERE dl.ssn = '.$ssn.'
			ORDER BY dl.date_modified;';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Gets the all of do not loan audit flags for a ssn.
	 *
	 * @returns query result set object
	 */
	public function getDoNotLoanFlagAllQuery($ssn) {
		$query = 'SELECT dl.ssn AS ssn,
				ct.name AS category,
				dl.company_id AS company_id,
				dl.other_reason AS other_reason,
				dl.explanation AS explanation,
				dl.agent_id AS modifying_agent_id,
				if(dl.active_status = "inactive",false,true) AS active_status,
				dl.active_status AS active_status_new,
				dl.date_created AS date_created,
				dl.date_modified AS date_modified
			FROM do_not_loan_flag AS dl
				JOIN do_not_loan_flag_category AS ct USING (category_id)
			WHERE dl.ssn = '.$ssn.'
			ORDER BY dl.date_modified;';
		$result = $this->db->query($query);
		return($result->fetchAll(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Get the employer and pay date information from an application_id
	 *
	 * @returns single query result row object
	 */
	public function getEmploymentQuery($application_id) {
		$query = 'SELECT ap.application_id AS application_id,
				ap.employer_name AS employer_name,
				ap.date_hire AS date_hire,
				ap.phone_work AS phone_work,
				ap.paydate_model AS paydate_model,
				ap.income_source AS income_source,
				ap.income_frequency AS income_frequency,
				ap.day_of_week AS day_of_week,
				ap.income_monthly AS income_monthly,
                                ap.income_direct_deposit AS income_direct_deposit_old,
				IF(ap.income_direct_deposit = "yes", TRUE, FALSE) as income_direct_deposit,				 
				ap.day_of_month_1 AS day_of_month_1,
				ap.day_of_month_2 AS day_of_month_2,
				ap.last_paydate AS last_paydate,
				ap.week_1 AS week_1,
				ap.week_2 AS week_2,
				ap.modifying_agent_id AS modifying_agent_id,
				ap.application_id AS application_id
			FROM application AS ap
			WHERE ap.application_id = '.$application_id.';';

		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);

		return($rows);
	}

	/**
	 * Finds and returns an previous customer set that meets a specific criteria (field names are structured through the query).
	 *
	 * @returns query result set object
	 */
	public function getPreviousCustomerQuery($where_clause) {

		$query = 'SELECT
				ap.application_id AS application_id,
				ap.date_application_status_set AS date_application_status_set,
				ap.date_created AS date_created,
				cp.name_short AS company,
				ap.application_status_id AS application_status_id,
				ast.application_status_name AS application_status,
				ap.olp_process AS olp_process,
				ap.bank_aba AS bank_aba,
				ap.bank_account AS bank_account,
				ap.name_first AS name_first,
				ap.name_last AS name_last,
				ap.ssn AS ssn,
				ap.date_first_payment AS date_first_payment,
				ap.dob AS date_of_birth,
				ap.email AS email,
				substr(ap.ssn,7,4) AS ssn_last4
			FROM application AS ap
				JOIN company AS cp USING (company_id)
				JOIN application_status AS ast USING (application_status_id) ';
				
		$query .= $where_clause." ORDER BY ap.application_id ASC";
		$result = $this->db->query($query);
		return($result->fetchAll(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Gets the affiliated application to a react application by application_id
	 *
	 * @returns query result set object or a single query result row object depending on $out_trigger
	 */
	public function getReactAffiliationQuery($application_id, $out_trigger) {
		$result = array();
		$query = 'SELECT ra.agent_id AS agent_id,
				ra.agent_id AS modifying_agent_id,
				ra.company_id AS company_id,
				ra.react_application_id AS react_application_id,
				ra.application_id AS application_id
			FROM react_affiliation AS ra
			WHERE ra.react_application_id = '.$application_id.
			' OR ra.react_application_id = (select MAX(application_id) FROM application WHERE ssn = '.$application_id.');';
		$result = $this->db->query($query);
		if ($out_trigger) {
			$return = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		}
		else $return = $result->fetchAll(DB_IStatement_1::FETCH_OBJ);
		return($return);
	}

	/**
	 * Generates a new application_id (authoritative_id) from the authoritative table.
	 *
	 * @returns the application_id (authoritative_id) key geneterated by the database auto number
	 */
	public function getAuthoritativeQuery($company_id) {
		$date = date("Y-m-d G:i:s");
		$query = 'INSERT INTO authoritative_ids (
				company_id,
				date_created
			) VALUES (
				'.$company_id.',
				"'.$date.'"
			);';
		$result = $this->db->query($query);
		return($this->db->lastInsertId());
	}

	/**
	 * Determines if there is an existing customer.
	 *
	 * @returns the customer_id (applicant_account_id) or false
	 */
	public function findPreviousApplicantAccountQuery($app){
		$query = 'SELECT ap.ssn AS ssn,
				ap.dob AS dob,
				ap.email AS email,
				ap.applicant_account_id AS applicant_account_id,
				aa.login AS login_id,
				aa.password AS password
			FROM application ap
				JOIN applicant_account AS aa USING (applicant_account_id)
			WHERE ap.ssn = '.$app->ssn.'
				AND ap.dob = "'.date("Y-m-d",strtotime($app->dob)).'";';
				//AND ap.email ="'.$app->email.'"
		$result = $this->db->query($query);
		$row = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($row);
	}

	/**
	 * Determines the next login id for a given login id
	 *
	 * @returns the customer_id (applicant_account_id) or false
	 */
	public function findUsernameCountQuery($username){
		$query = 'SELECT count(aa.applicant_account_id) AS COUNT
			FROM applicant_account AS aa
			WHERE aa.login LIKE "'.$username.'%";';
		$result = $this->db->query($query);
		$row = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($row->COUNT);
	}

	/**
	 * Inserts a new customer (applicant account) for the front end login interface.
	 *
	 * @returns the customer_id (applicant_account_id) key geneterated by the database auto number
	 */
	public function insertApplicantAccountQuery($app){
		$date = date("Y-m-d G:i:s");
		$query = 'INSERT INTO applicant_account (
				login,
				password,
				date_created,
				date_modified,
				modifying_agent_id
			) VALUES (
				"'.$app->login_id.'",
				"'.$app->password.'",
				"'.$date.'",
				"'.$date.'",
				'.$app->modifying_agent_id.'
			);';
		$result = $this->db->query($query);

		return($this->db->lastInsertId());
	}

	/**
	 * Determines if there is an existing customer.
	 *
	 * @returns the customer_id (customer_id) or false
	 */
	public function findCustomerQuery($app){
		$query = 'SELECT cst.customer_id AS ssn,
			FROM customer cst
			WHERE cst.ssn = '.$app->ssn.'";';
		$result = $this->db->query($query);
		$row = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($row);
	}

	/**
	 * Inserts a new customer (customer table) for the back end control.
	 *
	 * @returns the customer_id (customer_id) key geneterated by the database auto number
	 */
	public function insertCustomerQuery($app){
		$date = date("Y-m-d G:i:s");
		$query = 'INSERT INTO customer (
				company_id,
				customer_id,
				ssn,
				login,
				password,
				modifying_agent_id,
				date_created,
				date_modified
			) VALUES (
				'.$app->company_id.',
				'.$app->customer_id.',
				"'.$app->ssn.'",
				"'.$app->login_id.'",
				"'.$app->password.'",
				'.$app->modifying_agent_id.',
				"'.$date.'",
				"'.$date.'"
			);';
		$result = $this->db->query($query);

		return($this->db->lastInsertId());
	}

	/**
	 * Inserts a new date_of_birth into the table.
	 *
	 * @returns the date_of_birth_id key geneterated by the database auto number
	 */
	public function insertStatusHistoryQuery($status){
		$date = date("Y-m-d G:i:s");
		$query = 'INSERT INTO status_history (
				date_created,
				agent_id,
				application_id,
				application_status_id
			) VALUES (
				"'.$date.'",
				'.$status->modifying_agent_id.',
				'.$status->application_id.',
				(SELECT application_status_id FROM application_status WHERE application_status_name = "'.$status->application_status.'" LIMIT 1)
			);';
		$result = $this->db->query($query);

		return($this->db->lastInsertId());
	}

	/**
	 * Inserts a new application into the table.
	 *
	 * @returns the application_id key geneterated by the database auto number
	 */
	public function insertApplicationQuery($app){
		$date = date("Y-m-d G:i:s");
		$query = 'INSERT INTO application (
				apr,
				cfe_rule_set_id,
				company_id,
				customer_id,
				date_application_status_set,
				date_created,
				date_first_payment,
				date_fund_estimated,
				date_modified,
				enterprise_site_id,
				external_id,
				finance_charge,
				fund_qualified,
				fund_requested,
				ip_address,
				esig_ip_address,
				is_react,
				is_watched,
				loan_type_id,
				payment_total,
				price_point,
				rule_set_id,
				track_id,
				application_status_id,
				call_time_pref,
				contact_method_pref,
				marketing_contact_pref,
				olp_process,
				application_id,
				ssn,
				dob,
				legal_id_number,
				age,
				city,
				legal_id_state,
				name_first,
				name_last,
				residence_start_date,
				state,
				county,
				street,
				unit,
				zip_code,
				legal_id_type,
				tenancy_type,
				modifying_agent_id,
				applicant_account_id,
				day_of_month_1,
				day_of_month_2,
				income_date_soap_1,
				income_date_soap_2,
				income_direct_deposit,
				income_monthly,
				last_paydate,
				week_1,
				week_2,
				day_of_week,
				income_frequency,
				income_source,
				paydate_model,
				date_hire,
				employer_name,
				job_tenure,
				job_title,
				phone_work,
				phone_work_ext,
				shift,
				supervisor,
				bank_account,
				bank_aba,
				bank_name,
				banking_start_date,
				bank_account_type,
				email,
				phone_home,
				phone_cell
			) VALUES (
				'.$app->apr.',
				'.$app->cfe_rule_set_id.',
				'.$app->company_id.',
				'.$app->customer_id.',
				"'.date("Y-m-d",strtotime($app->date_application_status_set)).'",
				"'.date("Y-m-d",strtotime($app->date_created)).'",
				"'.date("Y-m-d",strtotime($app->date_first_payment)).'",
				"'.date("Y-m-d",strtotime($app->date_fund_estimated)).'",
				"'.date("Y-m-d",strtotime($app->date_modified)).'",
				'.$app->enterprise_site_id.',
				'.$app->external_id.',
				'.$app->finance_charge.',
				'.$app->fund_qualified.',
				'.$app->fund_requested.',
				"'.$app->ip_address.'",
				"'.$app->esig_ip_address.'",
				"'.$app->is_react.'",
				"'.$app->is_watched.'",
				'.$app->loan_type_id.',
				'.$app->payment_total.',
				'.$app->price_point.',
				'.$app->rule_set_id.',
				"'.$app->track_key.'",
				(SELECT application_status_id FROM application_status WHERE application_status_name = "'.$app->application_status.'" LIMIT 1),
				"'.$app->call_time_pref.'",
				"'.$app->contact_method_pref.'",
				"'.$app->marketing_contact_pref.'",
				"'.$app->olp_process.'",
				'.$app->application_id.',
				"'.$app->ssn.'",
				"'.$app->dob.'",
				"'.$app->legal_id_number.'",
				'.$app->age.',
				"'.$app->city.'",
				"'.$app->legal_id_state.'",
				"'.$app->name_first.'",
				"'.$app->name_last.'",
				"'.date("Y-m-d",strtotime($app->residence_start_date)).'",
				"'.$app->state.'",
				"'.$app->county.'",
				"'.$app->street.'",
				"'.$app->unit.'",
				"'.$app->zip_code.'",
				"'.$apl->legal_id__type.'",
				"'.$apl->tenancy_type.'",
				'.$app->modifying_agent_id.',
				'.$app->applicant_account_id.',
				"'.$app->day_of_month_1.'",
				"'.$app->day_of_month_2.'",
				"'.$app->income_date_soap_1.'",
				"'.$app->income_date_soap_2.'",
				"'.$app->income_direct_deposit.'",
				"'.$app->income_monthly.'",
				"'.$app->last_paydate.'",
				"'.$app->week_1.'",
				"'.$app->week_2.'",
				"'.$app->day_of_week.'",
				"'.strtolower($app->income_frequency).'",
				"'.strtolower($app->income_source).'",
				"'.strtolower($app->paydate_model).'",
				"'.date("Y-m-d",strtotime($app->date_hire)).'",
				"'.$app->employer_name.'",
				"'.$app->job_tenure.'",
				"'.$app->job_title.'",
				"'.$app->phone_work.'",
				"'.$app->phone_work_ext.'",
				"'.$app->shift.'",
				"'.$app->supervisor.'",
				"'.$app->bank_account.'",
				"'.$app->bank_aba.'",
				"'.$app->bank_name.'",
				"'.date("Y-m-d",strtotime($app->banking_start_date)).'",
				"'.strtolower($app->bank_account_type).'",
				"'.strtolower($app->email).'",
				"'.$app->phone_home.'",
				"'.$app->phone_cell.'"
			);';
		$result = $this->db->query($query);

		return($this->db->lastInsertId());
	}

	/**
	 * Inserts campaign information into the table.
	 *
	 * @returns the campaign_info_id key geneterated by the database auto number
	 */
	public function insertCampaignQuery($app){
		$date = date("Y-m-d G:i:s");
		$campaign_info = $app->campaign_info;
		$query = 'INSERT INTO campaign_info (
				date_created,
				date_modified,
				campaign_name,
				application_id,
				promo_id,
				promo_sub_code,
				site_id,
				reservation_id
			) VALUES (
				"'.$date.'",
				"'.$date.'",
				"'.$campaign_info->campaign_name.'",
				'.$app->application_id.',
				"'.$campaign_info->promo_id.'",
				"'.$campaign_info->promo_sub_code.'",
				'.$app->site_id.',
				"'.$campaign_info->reservation_id.'"
			);';
		$result = $this->db->query($query);

		return($this->db->lastInsertId());
	}

	/**
	 * Inserts a site for a URL and License.
	 *
	 * @returns site_id key geneterated by the database auto number
	 */
	public function getSiteQuery($app){
		$campaign_info = $app->campaign_info;
		$query = '(SELECT site_id
			FROM site
			WHERE LOWER(name) = "'.strtolower($campaign_info->site).'"
				AND license_key ="'.$campaign_info->license_key.'"
			LIMIT 1);';
		$result = $this->db->query($query);

		$rows = $result->fetchAll(DB_IStatement_1::FETCH_OBJ);
		if (count($rows)==0) $rtn = false;
		else $rtn = $rows[0]->site_id;
		return($rtn);
	}

	/**
	 * Inserts a site for a URL and License.
	 *
	 * @returns site_id key geneterated by the database auto number
	 */
	public function setSiteQuery($app){
		$date = date("Y-m-d G:i:s");
		$campaign_info = $app->campaign_info;
		$query = 'INSERT INTO site (
				date_created,
				date_modified,
				active_status,
				name,
				license_key
			) VALUES (
				"'.$date.'",
				"'.$date.'",
				"active",
				"'.$campaign_info->site.'",
				"'.$campaign_info->license_key.'"
			);';
		$result = $this->db->query($query);

		return($this->db->lastInsertId());
	}

	/**
	 * Inserts application version into the table.
	 *
	 * @returns the the version geneterated by the database
	 */
	public function insertApplicationVersionQuery($app){
		$date = date("Y-m-d G:i:s");
		$application_version = 0;
		$query = 'INSERT INTO application_version (
				application_id,
				version,
				date_created,
				date_modified
			) VALUES (
				'.$app->application_id.',
				'.$application_version.',
				"'.$date.'",
				"'.$date.'"
			);';
		$result = $this->db->query($query);

		return($application_version);
	}

	/**
	 * Inserts personal reference into the table.
	 *
	 * @returns the personal_reference_id key geneterated by the database auto number
	 */
	public function insertPersonalReferenceQuery($pr,$app){
		$date = date("Y-m-d G:i:s");

		$query = 'INSERT INTO personal_reference (
				date_created,
				date_modified,
				company_id,
				application_id,
				name_full,
				phone_home,
				relationship,
				reference_verified,
				contact_pref,
				agent_id
			) VALUES (
				"'.$date.'",
				"'.$date.'",
				'.$app->company_id.',
				'.$app->application_id.',
				"'.$pr->name_full.'",
				"'.$pr->phone_home.'",
				"'.$pr->relationship.'",
				"'.$pr->verified.'",
				"'.$pr->ok_to_contact.'",
				'.$pr->modifying_agent_id.'
			);';
		$result = $this->db->query($query);

		return($this->db->lastInsertId());
	}

	/**
	 * Inserts event logs into the table.
	 *
	 * @returns the event_log_id key geneterated by the database auto number
	 */
	public function insertEventLogQuery($ev,$app){
		$date = date("Y-m-d G:i:s");

		$query = 'INSERT INTO eventlog (
				date_created,
				application_id,
				eventlog_event_id,
				eventlog_response_id,
				eventlog_target_id
			) VALUES (
				"'.$date.'",
				'.$app->external_id.',
				(SELECT eventlog_event_id FROM eventlog_event WHERE ucase(name_short) = ucase("'.$ev->event.'") LIMIT 1),
				(SELECT eventlog_response_id FROM eventlog_response WHERE ucase(name_short) = ucase("'.$ev->response.'") LIMIT 1),
				(SELECT eventlog_target_id FROM eventlog_target WHERE ucase(name_short) = ucase("'.$ev->target.'") LIMIT 1)
			);';
		$result = $this->db->query($query);

		return($this->db->lastInsertId());
	}

	/**
	 * Inserts react affiliations into the table.
	 *
	 * @returns the react_affiliation_id key geneterated by the database auto number
	 */
	public function insertReactAffiliationQuery($ra,$app){
		$date = date("Y-m-d G:i:s");

		$query = 'INSERT INTO react_affiliation (
				react_application_id,
				date_created,
				date_modified,
				company_id,
				application_id,
				agent_id
			) VALUES (
				'.$app->application_id.',
				"'.$date.'",
				"'.$date.'",
				'.$app->company_id.',
				'.$ra->application_id.',
				'.$ra->agent_id.'
			);';
		$result = $this->db->query($query);

		return(true);
	}

	/**
	 * Inserts react affiliations into the table.
	 *
	 * @returns the react_affiliation_id key geneterated by the database auto number
	 */
	public function insertDoNotLoanFlagQuery($dnl){
		$date = date("Y-m-d G:i:s");

		$query = 'INSERT INTO do_not_loan_flag (
				ssn,
				company_id,
				category_id,
				other_reason,
				explanation,
				active_status,
				agent_id,
				date_created,
				date_modified
			) VALUES (
				"'.$dnl->ssn.'",
				'.$dnl->company_id.',
				(SELECT category_id FROM do_not_loan_flag_category WHERE name = "'.$dnl->category.'" LIMIT 1),
				"'.$dnl->other_reason.'",
				"'.$dnl->explanation.'",
				"active",
				'.$dnl->modifying_agent_id.',
				"'.$date.'",
				"'.$date.'"
			);';
		$result = $this->db->query($query);

		$id = $this->db->lastInsertId();

		$query = 'INSERT INTO do_not_loan_audit (
				date_created,
				company_id,
				ssn,
				table_name,
				column_name,
				value_before,
				value_after,
				agent_id
			) VALUES (
				"'.$date.'",
				'.$dnl->company_id.',
				"'.$dnl->ssn.'",
				"do_not_loan_flag",
				"",
				"not set",
				"'.$dnl->category.'  '.$dnl->explanation.'",
				'.$dnl->modifying_agent_id.'
			);';

		$result = $this->db->query($query);

		return($id);
	}

	/**
	 * Updates the applicant table.
	 *
	 * @returns boolean
	 */
	public function updateApplicantQuery($apl,$ap_id){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE application AS apl SET 
				apl.age = '.$apl->age.',
				apl.city = "'.$apl->city.'",
				apl.date_modified = "'.$date.'",
				apl.legal_id_state = "'.$apl->legal_id_state.'",
				apl.name_first = "'.$apl->name_first.'",
				apl.name_last = "'.$apl->name_last.'",
				apl.residence_start_date = "'.date("Y-m-d",strtotime($apl->residence_start_date)).'",
				apl.state = "'.$apl->state.'",
				apl.county = "'.$apl->county.'",
				apl.street = "'.$apl->street.'",
				apl.unit = "'.$apl->unit.'",
				apl.zip_code = "'.$apl->zip_code.'",
				apl.tenancy_type = "'.$apl->tenancy_type.'",
				apl.modifying_agent_id = '.$apl->modifying_agent_id.',
				apl.ssn = "'.$apl->ssn.'",
				apl.dob = "'.$apl->date_of_birth.'",
				apl.ssn_last_four = "'.substr($apl->ssn,-4).'",
				apl.legal_id_number = "'.$apl->legal_id_number.'"
			WHERE application_id ='.$ap_id.';';
		$result = $this->db->query($query);

		return($result->rowCount());
	}

	/**
	 * Updates the applicant table.
	 *
	 * @returns boolean
	 */
	public function updateContactInfoQuery($ci){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE application AS apl SET 
				apl.'.$ci->contact_type.' = "'.$ci->contact_info_value.'",
				apl.modifying_agent_id = '.$ci->modifying_agent_id.',
				apl.date_modified = "'.$date.'"
			WHERE application_id ='.$ap_id.';';
		$result = $this->db->query($query);

		return($result->rowCount());
	}

	/**
	 * Updates the applicant account password.
	 *
	 * @returns boolean
	 */
	public function updateLoginPassword($login,$password){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE applicant_account SET 
				date_modified = "'.$date.'",
				password = "'.$password.'"
			WHERE login ="'.$login.'";';

		$result = $this->db->query($query);
		return($result->rowCount());
	}

	/**
	 * Updates the application related table.
	 *
	 * @returns boolean
	 */
	public function updateApplicationQuery($app){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE application AS ap
			SET 
				ap.apr = '.$app->apr.',
				ap.customer_id = '.$app->customer_id.',
				ap.cfe_rule_set_id = '.$app->cfe_ruleset_id.',
				ap.date_first_payment = "'.date("Y-m-d",strtotime($app->date_first_payment)).'",
				ap.date_fund_actual = "'.date("Y-m-d",strtotime($app->date_fund_actual)).'",
				ap.date_fund_estimated = "'.date("Y-m-d",strtotime($app->date_fund_estimated)).'",
				ap.date_next_contact = "'.date("Y-m-d",strtotime($app->date_next_contact)).'",
				ap.finance_charge = '.$app->finance_charge.',
				ap.fund_actual = '.$app->fund_actual.',
				ap.fund_qualified = '.$app->fund_qualified.',
				ap.fund_requested = '.$app->fund_requested.',
				ap.is_watched = "'.$app->is_watched.'",
				ap.payment_total = '.$app->payment_total.',
				ap.rule_set_id = '.$app->rule_set_id.',
				ap.date_modified = "'.$date.'",
				ap.call_time_pref = "'.$app->call_time_pref.'",
				ap.contact_method_pref = "'.$app->contact_method_pref.'",
				ap.marketing_contact_pref = "'.$app->marketing_contact_pref.'",
				ap.esig_ip_address = "'.$app->esig_ip_address.'",
				ap.modifying_agent_id = '.$app->modifying_agent_id.'
			WHERE ap.application_id ='.$app->application_id.';';
		$result = $this->db->query($query);
		return($result->rowCount());
	}

	/**
	 * Updates the bank information related table.
	 *
	 * @returns boolean
	 */
	public function updateBankQuery($bi){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE application AS ap SET
				ap.date_modified = "'.$date.'",
				ap.bank_name = "'.$bi->bank_name.'",
				ap.bank_aba = "'.$bi->bank_aba.'",
				ap.banking_start_date = "'.date("Y-m-d",strtotime($bi->banking_start_date)).'",
				ap.bank_account_type = "'.$bi->bank_account_type.'",
				ap.bank_account = "'.$bi->bank_account.'",
				ap.income_direct_deposit = '.(((strtolower($bi->income_direct_deposit) == "no") || !($bi->income_direct_deposit)) ? '"no"' : '"yes"').',
				ap.modifying_agent_id = '.$bi->modifying_agent_id.'
			WHERE ap.application_id ='.$bi->application_id.';';
		$result = $this->db->query($query);
		return($result->rowCount());
	}

	/**
	 * Updates the application table status.
	 *
	 * @returns boolean
	 */
	public function updateStatusQuery($status){
		if (isset($status->application_status_id)) $ap_status = $status->application_status_id;
		else $ap_status = '(SELECT application_status_id FROM application_status WHERE application_status_name = "'.$status->application_status.'" LIMIT 1)';
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE application AS ap SET
				ap.application_status_id = '.$ap_status.',
				ap.date_application_status_set = "'.$date.'",
				ap.date_modified = "'.$date.'",
				ap.modifying_agent_id = '.$status->modifying_agent_id.'
			WHERE ap.application_id ='.$status->application_id.';';

		$result = $this->db->query($query);
		return($result->rowCount());
	}

	/**
	 * Retrieves the employer info details for the update from an application_id 
	 *
	 * @returns single query result row object
	 */
	public function getEmploymentDetailsQuery($application_id) {
		$query = 'SELECT ap.application_id AS application_id,
				ap.employer_name AS employer_name,
				"" AS department,
				ap.job_title AS job_title,
				ap.supervisor AS supervisor,
				ap.date_hire AS date_hire,
				ap.job_tenure AS job_tenure,
				ap.phone_work AS phone_work
			FROM application AS ap
			WHERE ap.application_id = '.$application_id.';';

		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Retrieves the pay date info details for the update from an application_id 
	 *
	 * @returns single query result row object
	 */
	public function getPaydateDetailsQuery($application_id) {
		$query = 'SELECT ap.application_id AS application_id,
				ap.paydate_model AS paydate_model,
				ap.income_source AS income_source,
				ap.income_frequency AS income_frequency,
				ap.day_of_week AS day_of_week,
				ap.income_monthly AS income_monthly,
				ap.income_direct_deposit AS income_direct_deposit_old,
				IF(ap.income_direct_deposit = "yes", TRUE, FALSE) as income_direct_deposit,
				ap.income_date_soap_1 AS income_date_soap_1,
				ap.income_date_soap_2 AS income_date_soap_2,
				ap.last_paydate AS last_paydate,
				ap.day_of_month_1 AS day_of_month_1,
				ap.day_of_month_2 AS day_of_month_2,
				ap.week_1 AS week_1,
				ap.week_2 AS week_2
			FROM application AS ap
			WHERE ap.application_id = '.$application_id.';';
		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);

		return($rows);
	}

	/**
	 * Updates the employment info table.
	 *
	 * @returns boolean
	 */
	public function updateEmploymentInfoQuery($employment){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE application SET
				employer_name = "'.$employment->employer_name.'",
				date_hire = "'.$employment->date_hire.'",
				job_title = "'.$employment->job_title.'",
				supervisor = "'.$employment->supervisor.'",
				date_hire = "'.$employment->date_hire.'",
				job_tenure = "'.$employment->job_tenure.'",
				phone_work = "'.$employment->phone_work.'",
				phone_work_ext = "'.$employment->phone_work_ext.'",
				date_modified = "'.$date.'",
				modifying_agent_id = '.$employment->modifying_agent_id.'
			WHERE application_id ='.$employment->application_id.';';
		$result = $this->db->query($query);
		return($result->rowCount());
	}

	/**
	 * Updates the employment info table.
	 *
	 * @returns boolean
	 */
	public function updatePaydateInfoQuery($paydate){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE application SET
				date_modified = "'.$date.'",
				day_of_month_1 = '.$paydate->day_of_month_1.',
				day_of_month_2 = '.$paydate->day_of_month_2.',
				income_date_soap_1 = "'.$paydate->income_date_soap_1.'",
				income_date_soap_2 = "'.$paydate->income_date_soap_2.'",
				income_direct_deposit = '.(((strtolower($paydate->income_direct_deposit) == "no") || !($paydate->income_direct_deposit)) ? '"no"' : '"yes"').',
				income_monthly = '.$paydate->income_monthly.',
				last_paydate = "'.date("Y-m-d",strtotime($paydate->last_paydate)).'",
				week_1 = '.$paydate->week_1.',
				week_2 = '.$paydate->week_2.',
				day_of_week = "'.$paydate->day_of_week.'",
				income_frequency = "'.$paydate->income_frequency.'",
				income_source = "'.$paydate->income_source.'",
				paydate_model = "'.$paydate->paydate_model.'",
				modifying_agent_id = '.$paydate->modifying_agent_id.'
			WHERE application_id ='.$paydate->application_id.';';
			// employer_name = "'.$paydate->employer_name.'",

		$result = $this->db->query($query);
		return($result->rowCount());
	}

	/**
	 * Updates the personal reference table.
	 *
	 * @returns boolean
	 */
	public function updatePersonalReferenceQuery($reference){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE personal_refernce SET
				date_modified = "'.$date.'",
				company_id = '.$reference->company_id.',
				name_full = "'.$reference->name_full.'",
				phone_home = "'.$reference->phone_home.'",
				relationship = "'.$reference->relationship.'",
				verified = "'.$reference->verified.'",
				ok_to_contact = "'.$reference->ok_to_contact.'",
				modifying_agent_id = '.$reference->modifying_agent_id.'
			WHERE personal_refernce_id ='.$reference->personal_refernce_id.';';

		$result = $this->db->query($query);
		return($result->rowCount());
	}

	/**
	 * Updates the application version table.
	 *
	 * @returns boolean
	 */
	public function updateApplicationVersionQuery($application_id, $version){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE application_version SET
				date_modified = "'.$date.'",
				version = '.$version.'
			WHERE application_id ='.$application_id.';';

		$result = $this->db->query($query);
		return($result->rowCount());
	}
}
