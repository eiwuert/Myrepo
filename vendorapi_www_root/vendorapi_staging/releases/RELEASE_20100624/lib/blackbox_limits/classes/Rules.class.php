<?php

/**
 * @brief
 * A class encapsulating a Rules record in the database
 *
 * This class Encapsulates a set of business rules in the database. It's a wrapper class which is designed to load and save instances of itself in the database.
 */

class Rules
{

	// Private fields
	var $date_modified;
	var $date_created;
	var $rule_id;
	var $target_id;
	var $datax_idv;
	var $vendor_qualify_post;
	var $verify_post_type;
	var $weekends = 'FALSE';
	var $list_mgmt_nosell = 'FALSE';
	var $non_dates; // Serialized array of dates or NULL
	var $bank_account_type; // CHECKING, SAVINGS, NULL
	var $minimum_income;
	var $reference_data;
	var $income_direct_deposit; // TRUE, FALSE, NULL
	var $excluded_states; // Serialized array of two char state or NULL
	var $restricted_states; // Serialized array of two char state or NULL
	var $income_frequency; // Serialized array of frequencies or NULL
	var $force_promo_id; // Serialized array of Promo IDs or NULL
	var $force_site_id; // Serialized array of Site IDs or NULL
	var $status; // ACTIVE, INACTIVE
	var $username;
	var $state_id_required = 'FALSE';
	var $state_issued_id_required = 'FALSE';
	var $minimum_recur = 30; // Days back to check for duplicate leads
	var $income_recur = array();
	var $pay_date_recur = array();
	var $dd_check = 0;
	var $income_type = 'BOTH'; // BOTH/BENEFITS/EMPLOYMENT
	var $income_source;
	var $operating_hours;
    var $minimum_age;
    var $identical_phone_numbers = 'FALSE';
    var $identical_work_cell_numbers = 'FALSE';
    var $paydate_minimum = 0;
    var $filter;
    var $withheld_targets;
    var $required_references = 2;
	var $excluded_zips;
	var $suppression_lists;
	//********************************************* 
	// GForge 6672 [AuMa]
	// New fields added to our system
	// min_loan_amount_requested (drop down)
	// max_loan_amount_requested (drop down)
	// residence_length (date/drop down)
	// employer_length (date/drop down)
	// residence_type (RENT/OWN)
	//********************************************* 
	var $min_loan_amount_requested = NULL;
	var $max_loan_amount_requested = NULL;
	var $residence_length = NULL;
	var $employer_length = NULL;
	var $residence_type = NULL;
	
	/*
	New rules for [#17091] [RV]
	*/
	var $minimum_recur_withheld_ssn;
	var $minimum_recur_withheld_email;

	var $sql;

	function Rules()
	{
		global $sql;
		$this->sql = &$sql;
	}

	function find_by_id($id)
	{
		if (!$id)
			return NULL;

		$query = "
			SELECT date_modified, date_created, rule_id, target_id, weekends, list_mgmt_nosell, non_dates, datax_idv, 
                vendor_qualify_post, verify_post_type, bank_account_type, minimum_income, income_direct_deposit, 
                excluded_states, suppression_lists, excluded_zips, restricted_states, income_frequency,
                force_promo_id, force_site_id, status, username, state_id_required, state_issued_id_required, minimum_recur, dd_check,
                minimum_age, identical_phone_numbers, identical_work_cell_numbers, paydate_minimum, filter, income_type,
				operating_hours, withheld_targets, required_references, reference_data,	military, post_url, frequency_decline,
				max_loan_amount_requested, min_loan_amount_requested, residence_length, employer_length, residence_type,
				income_recur, pay_date_recur, minimum_recur_withheld_ssn, minimum_recur_withheld_email, income_source
			FROM rules
				WHERE rule_id = {$id}
				LIMIT 1";
		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		if ($row = $sql->Fetch_Array_Row($result))
		{

			$current_rules = new Rules();
			$current_rules->set_sql($sql);
			$current_rules->set_date_modified($row['date_modified']);
			$current_rules->set_date_created($row['date_created']);
			$current_rules->set_rule_id($row['rule_id']);
			$current_rules->set_target_id($row['target_id']);
			$current_rules->set_weekends($row['weekends']);
			$current_rules->set_list_mgmt_nosell($row['list_mgmt_nosell']);
			$current_rules->non_dates = $row['non_dates'];
			$current_rules->set_bank_account_type($row['bank_account_type']);
			$current_rules->set_minimum_income($row['minimum_income']);
			$current_rules->set_income_direct_deposit($row['income_direct_deposit']);
			$current_rules->excluded_states = $row['excluded_states'];
			$current_rules->excluded_zips = $row['excluded_zips'];
			$current_rules->suppression_lists = $row['suppression_lists'];
			$current_rules->restricted_states = $row['restricted_states'];
			$current_rules->income_frequency = $row['income_frequency'];
			$current_rules->force_promo_id = $row['force_promo_id'];
			$current_rules->force_site_id = $row['force_site_id'];
			$current_rules->set_status($row['status']);
			$current_rules->set_username($row['username']);
			$current_rules->set_state_id_required($row['state_id_required']);
			$current_rules->set_state_issued_id_required($row['state_issued_id_required']);
			$current_rules->set_minimum_recur($row['minimum_recur']);
			$current_rules->income_recur = $row['income_recur'];
			$current_rules->pay_date_recur = $row['pay_date_recur'];
			$current_rules->set_dd_check($row['dd_check']);
            $current_rules->set_minimum_age($row['minimum_age']);
            $current_rules->set_identical_phone_numbers($row['identical_phone_numbers']);
            $current_rules->set_identical_work_cell_numbers($row['identical_work_cell_numbers']);
            $current_rules->set_paydate_minimum($row['paydate_minimum']);
            $current_rules->filter = $row['filter'];
			$current_rules->set_income_type($row['income_type']);
			$current_rules->set_datax_idv($row['datax_idv']);
			$current_rules->set_vendor_qualify_post($row['vendor_qualify_post']);
			$current_rules->set_verify_post_type($row['verify_post_type']);
			$current_rules->operating_hours = $row['operating_hours'];
			$current_rules->set_withheld_targets($row['withheld_targets']);
			$current_rules->set_required_references($row['required_references']);
			$current_rules->reference_data = $row['reference_data'];
			$current_rules->military = $row['military'];
			$current_rules->post_url = $row['post_url'];
			$current_rules->frequency_decline = $row['frequency_decline'];

			$current_rules->max_loan_amount_requested = $row['max_loan_amount_requested'];
			$current_rules->min_loan_amount_requested = $row['min_loan_amount_requested'];
			$current_rules->residence_length = $row['residence_length'];
			$current_rules->employer_length = $row['employer_length'];
			$current_rules->residence_type = $row['residence_type'];
			$current_rules->set_income_source($row['income_source']);
			$current_rules->minimum_recur_withheld_ssn = $row['minimum_recur_withheld_ssn'];
			$current_rules->minimum_recur_withheld_email = $row['minimum_recur_withheld_email'];

			return $current_rules;
		}

		return NULL;
	}

	function find_all()
	{
		$query = "
			SELECT date_modified, date_created, rule_id, target_id, weekends, list_mgmt_nosell, non_dates, bank_account_type,
				minimum_income, income_direct_deposit, excluded_states, excluded_zips, suppression_lists, 
            	restricted_states, income_frequency, force_promo_id, force_site_id, status, username, 
            	state_id_required, state_issued_id_required, minimum_recur, dd_check, minimum_age, 
				identical_phone_numbers, identical_work_cell_numbers, paydate_minimum, filter, income_type, datax_idv, vendor_qualify_post,
            	verify_post_type, operating_hours, withheld_targets, required_references, reference_data, military, post_url, frequency_decline,
				max_loan_amount_requested, min_loan_amount_requested, residence_length, employer_length, residence_type,
				income_recur, pay_date_recur, minimum_recur_withheld_ssn, minimum_recur_withheld_email, income_source
			FROM rules
			ORDER BY date_created DESC";
		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		$rules_array = Array();
		while ($row = $sql->Fetch_Array_Row($result))
		{
			$current_rules = new Rules();
			$current_rules->set_sql($sql);
			$current_rules->set_date_modified($row['date_modified']);
			$current_rules->set_date_created($row['date_created']);
			$current_rules->set_rule_id($row['rule_id']);
			$current_rules->set_target_id($row['target_id']);
			$current_rules->set_weekends($row['weekends']);
			$current_rules->set_list_mgmt_nosell($row['list_mgmt_nosell']);						
			$current_rules->non_dates = $row['non_dates'];
			$current_rules->set_bank_account_type($row['bank_account_type']);
			$current_rules->set_minimum_income($row['minimum_income']);
			$current_rules->set_income_direct_deposit($row['income_direct_deposit']);
			$current_rules->excluded_states = $row['excluded_states'];
			$current_rules->suppression_lists = $row['suppression_lists'];
			$current_rules->excluded_zips = $row['excluded_zips'];
			$current_rules->restricted_states = $row['restricted_states'];
			$current_rules->income_frequency = $row['income_frequency'];
			$current_rules->force_promo_id = $row['force_promo_id'];
			$current_rules->force_site_id = $row['force_site_id'];
			$current_rules->set_status($row['status']);
			$current_rules->set_username($row['username']);
			$current_rules->set_state_id_required($row['state_id_required']);
			$current_rules->set_state_issued_id_required($row['state_issued_id_required']);
			$current_rules->set_minimum_recur($row['minimum_recur']);
			$current_rules->income_recur = $row['income_recur'];
			$current_rules->pay_date_recur = $row['pay_date_recur'];
			$current_rules->set_dd_check($row['dd_check']);
            $current_rules->set_minimum_age($row['minimum_age']);
            $current_rules->set_identical_phone_numbers($row['identical_phone_numbers']);
            $current_rules->set_identical_work_cell_numbers($row['identical_work_cell_numbers']);
            $current_rules->set_paydate_minimum($row['paydate_minimum']);
            $current_rules->filter = $row['filter'];
			$current_rules->set_income_type($row['income_type']);
			$current_rules->set_datax_idv($row['datax_idv']);
			$current_rules->set_vendor_qualify_post($row['vendor_qualify_post']);
			$current_rules->set_verify_post_type($row['verify_post_type']);
			$current_rules->operating_hours = $row['operating_hours'];
			$current_rules->set_withheld_targets($row['withheld_targets']);
			$current_rules->set_required_references($row['required_references']);
			$current_rules->reference_data = $row['reference_data'];
			$current_rules->military = $row['military'];
			$current_rules->post_url = $row['post_url'];
			$current_rules->max_loan_amount_requested = $row['max_loan_amount_requested'];
			$current_rules->min_loan_amount_requested = $row['min_loan_amount_requested'];
			$current_rules->residence_length = $row['residence_length'];
			$current_rules->employer_length = $row['employer_length'];
			$current_rules->residence_type = $row['residence_type'];
			$current_rules->frequency_decline = $row['frequency_decline'];
			$current_rules->set_income_source($row['income_source']);
			$current_rules->minimum_recur_withheld_ssn = $row['minimum_recur_withheld_ssn'];
			$current_rules->minimum_recur_withheld_email = $row['minimum_recur_withheld_email'];
			$rules_array[] = $current_rules;
		}

		return $rules_array;
	}

	function find_current_by_target_id($target_id)
	{
		if (!$target_id)
			return NULL;

		$query = "
			SELECT date_modified, date_created, rule_id, target_id, weekends, list_mgmt_nosell, non_dates,
				bank_account_type, minimum_income, income_direct_deposit, excluded_states, suppression_lists, 
                excluded_zips, restricted_states, income_frequency, force_promo_id, force_site_id, status,
				username, state_id_required, state_issued_id_required, minimum_recur, dd_check, minimum_age, 
				identical_phone_numbers, identical_work_cell_numbers, paydate_minimum, filter, income_type, datax_idv,
				max_loan_amount_requested, min_loan_amount_requested, residence_length, employer_length,residence_type,
                vendor_qualify_post, verify_post_type, operating_hours, withheld_targets, required_references, 
				reference_data, military, post_url, frequency_decline, income_recur, pay_date_recur, minimum_recur_withheld_ssn,
				minimum_recur_withheld_email, income_source
			FROM rules
			WHERE target_id = {$target_id}
				AND status = 'ACTIVE'
			ORDER BY date_created DESC
			LIMIT 1";

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		if (is_a($result, 'Error_2'))
			print_r($result) && die();
		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_rules = new Rules();
			$current_rules->set_sql($sql);
			$current_rules->set_date_modified($row['date_modified']);
			$current_rules->set_date_created($row['date_created']);
			$current_rules->set_rule_id($row['rule_id']);
			$current_rules->set_target_id($row['target_id']);
			$current_rules->set_weekends($row['weekends']);
			$current_rules->set_list_mgmt_nosell($row['list_mgmt_nosell']);
			$current_rules->non_dates = $row['non_dates'];
			$current_rules->set_bank_account_type($row['bank_account_type']);
			$current_rules->set_minimum_income($row['minimum_income']);
			$current_rules->set_income_direct_deposit($row['income_direct_deposit']);
			$current_rules->excluded_states = $row['excluded_states'];
			$current_rules->excluded_zips = $row['excluded_zips'];
			$current_rules->suppression_lists = $row['suppression_lists'];
			$current_rules->restricted_states = $row['restricted_states'];
			$current_rules->income_frequency = $row['income_frequency'];
			$current_rules->force_promo_id = $row['force_promo_id'];
			$current_rules->force_site_id = $row['force_site_id'];
			$current_rules->set_status($row['status']);
			$current_rules->set_username($row['username']);
			$current_rules->set_state_id_required($row['state_id_required']);
			$current_rules->set_state_issued_id_required($row['state_issued_id_required']);
			$current_rules->set_minimum_recur($row['minimum_recur']);
			$current_rules->income_recur = $row['income_recur'];
			$current_rules->pay_date_recur = $row['pay_date_recur'];
			$current_rules->set_dd_check($row['dd_check']);
            $current_rules->set_minimum_age($row['minimum_age']);
            $current_rules->set_identical_phone_numbers($row['identical_phone_numbers']);
            $current_rules->set_identical_work_cell_numbers($row['identical_work_cell_numbers']);
            $current_rules->set_paydate_minimum($row['paydate_minimum']);
            $current_rules->filter = $row['filter'];
			$current_rules->set_income_type($row['income_type']);
			$current_rules->set_datax_idv($row['datax_idv']);
			$current_rules->set_vendor_qualify_post($row['vendor_qualify_post']);
			$current_rules->set_verify_post_type($row['verify_post_type']);
			$current_rules->operating_hours = $row['operating_hours'];
			$current_rules->set_withheld_targets($row['withheld_targets']);
			$current_rules->set_required_references($row['required_references']);
			$current_rules->reference_data = $row['reference_data'];
			$current_rules->military = $row['military'];
			$current_rules->post_url = $row['post_url'];
			$current_rules->max_loan_amount_requested = $row['max_loan_amount_requested'];
			$current_rules->min_loan_amount_requested = $row['min_loan_amount_requested'];
			$current_rules->residence_length = $row['residence_length'];
			$current_rules->employer_length = $row['employer_length'];
			$current_rules->residence_type = $row['residence_type'];
			$current_rules->frequency_decline = $row['frequency_decline'];
			$current_rules->set_income_source($row['income_source']);
			$current_rules->minimum_recur_withheld_ssn = $row['minimum_recur_withheld_ssn'];
			$current_rules->minimum_recur_withheld_email = $row['minimum_recur_withheld_email'];
			return $current_rules;
		}

		return NULL;
	}

	function find_by_date_and_target_id($date, $target_id)
	{
		if (!$date || !$target_id || ($date == '0000-00-00'))
			return NULL;

		$query = "
			SELECT date_modified, date_created, rule_id, target_id, weekends, list_mgmt_nosell, non_dates,
				bank_account_type, minimum_income, income_direct_deposit, excluded_states, excluded_zips,
				suppression_lists, restricted_states, income_frequency, force_promo_id, force_site_id, status,
				username, state_id_required, state_issued_id_required, minimum_recur, dd_check, minimum_age, 
				identical_phone_numbers, identical_work_cell_numbers, paydate_minimum, filter, income_type, datax_idv,
                vendor_qualify_post, verify_post_type, withheld_targets, required_references, reference_data, military, 
				max_loan_amount_requested, min_loan_amount_requested, residence_length, employer_length, residence_type,
				post_url, frequency_decline, income_recur, pay_date_recur, minimum_recur_withheld_ssn, 
				minimum_recur_withheld_email, income_source
			FROM rules
			WHERE target_id = {$target_id}
				AND date_created < '{$date}' + interval 1 day
			ORDER BY date_created DESC
			LIMIT 1";

//		die($query);

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		if (is_a($result, 'Error_2'))
			print_r($result) && die();
		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_rules = new Rules();
			$current_rules->set_sql($sql);
			$current_rules->set_date_modified($row['date_modified']);
			$current_rules->set_date_created($row['date_created']);
			$current_rules->set_rule_id($row['rule_id']);
			$current_rules->set_target_id($row['target_id']);
			$current_rules->set_weekends($row['weekends']);
			$current_rules->set_list_mgmt_nosell($row['list_mgmt_nosell']);		
			$current_rules->non_dates = $row['non_dates'];
			$current_rules->set_bank_account_type($row['bank_account_type']);
			$current_rules->set_minimum_income($row['minimum_income']);
			$current_rules->set_income_direct_deposit($row['income_direct_deposit']);
			$current_rules->excluded_states = $row['excluded_states'];
			$current_rules->excluded_zips = $row['excluded_zips'];
			$current_rules->suppression_lists = $row['suppression_lists'];
			$current_rules->restricted_states = $row['restricted_states'];
			$current_rules->income_frequency = $row['income_frequency'];
			$current_rules->force_promo_id = $row['force_promo_id'];
			$current_rules->force_site_id = $row['force_site_id'];
			$current_rules->set_status($row['status']);
			$current_rules->set_username($row['username']);
			$current_rules->set_state_id_required($row['state_id_required']);
			$current_rules->set_state_issued_id_required($row['state_issued_id_required']);
			$current_rules->set_minimum_recur($row['minimum_recur']);
			$current_rules->income_recur = $row['income_recur'];
			$current_rules->pay_date_recur = $row['pay_date_recur'];
			$current_rules->set_dd_check($row['dd_check']);
            $current_rules->set_minimum_age($row['minimum_age']);
            $current_rules->set_identical_phone_numbers($row['identical_phone_numbers']);
            $current_rules->set_identical_work_cell_numbers($row['identical_work_cell_numbers']);
            $current_rules->set_paydate_minimum($row['paydate_minimum']);
            $current_rules->filter = $row['filter'];
			$current_rules->set_income_type($row['income_type']);
			$current_rules->set_datax_idv($row['datax_idv']);
			$current_rules->set_vendor_qualify_post($row['vendor_qualify_post']);
			$current_rules->set_verify_post_type($row['verify_post_type']);
			$current_rules->operating_hours = $row['operating_hours'];
			$current_rules->set_withheld_targets($row['withheld_targets']);
			$current_rules->set_required_references($row['required_references']);
			$current_rules->reference_data = $row['reference_data'];
			$current_rules->military = $row['military'];
			$current_rules->max_loan_amount_requested = $row['max_loan_amount_requested'];
			$current_rules->min_loan_amount_requested = $row['min_loan_amount_requested'];
			$current_rules->residence_length = $row['residence_length'];
			$current_rules->employer_length = $row['employer_length'];
			$current_rules->residence_type = $row['residence_type'];
			$current_rules->post_url = $row['post_url'];
			$current_rules->frequency_decline = $row['frequency_decline'];
			$current_rules->set_income_source($row['income_source']);
			
			$current_rules->minimum_recur_withheld_ssn = $row['minimum_recur_withheld_ssn'];
			$current_rules->minimum_recur_withheld_email = $row['minimum_recur_withheld_email'];

			return $current_rules;
		}

		return NULL;

	}

	function update()
	{

		if (!$this->rule_id)
			return $this->insert();

		$idv = (!is_null($this->datax_idv)) ? "'".$this->get_datax_idv()."'" : 'NULL';
		$query = "
			UPDATE rules
			SET
				date_modified = sysdate(),
				target_id ='" . $this->get_target_id() . "', 
				weekends ='" . $this->get_weekends() . "', 
				list_mgmt_nosell ='". $this->get_list_mgmt_nosell() . "',
				non_dates ='" . $this->non_dates . "', 
				bank_account_type ='" . $this->get_bank_account_type() . "', 
				minimum_income ='" . $this->get_minimum_income() . "', 
				income_direct_deposit ='" . $this->get_income_direct_deposit() . "', 
				excluded_states ='" . mysql_escape_string($this->excluded_states) . "', 
				excluded_zips ='" . mysql_escape_string($this->excluded_zips) . "',
				suppression_lists ='" . mysql_escape_string($this->suppression_lists) . "',
				restricted_states ='" . $this->restricted_states . "',
				income_frequency ='" . $this->income_frequency . "',
				force_promo_id ='" . $this->force_promo_id . "',
				force_site_id ='" . $this->force_site_id . "',
				status ='" . $this->get_status() . "',
				username = '" . $this->get_username() . "',
				state_id_required = '" . $this->get_state_id_required() . "',
				state_issued_id_required = '" . $this->get_state_issued_id_required() . "',
				minimum_recur = '" . $this->get_minimum_recur() . "',
				income_recur = '" . mysql_escape_string($this->income_recur) . "',
				pay_date_recur = '" . mysql_escape_string($this->pay_date_recur) . "',
				dd_check = '" . $this->get_dd_check() . "',
                minimum_age = " . ((int)$this->get_minimum_age()) . ",
				identical_phone_numbers ='" . $this->get_identical_phone_numbers() . "',
				identical_work_cell_numbers ='" . $this->get_identical_work_cell_numbers() . "',
				paydate_minimum ='" . $this->get_paydate_minimum() . "',
                filter = '" . $this->filter . "',
				income_type = '" . $this->get_income_type() . "',
				datax_idv = $idv,
				vendor_qualify_post = '" . $this->get_vendor_qualify_post() . "',
				vendor_qualify_post = '" . $this->get_verify_post_type() . "',
				operating_hours = '". $this->operating_hours . "',
				withheld_targets = '" . $this->get_withheld_targets() . "',
				required_references = " . $this->get_required_references() . ",
				reference_data = '"  . mysql_escape_string($this->reference_data) ."',
				military = '".mysql_escape_string($this->military)."',
				frequency_decline = '".mysql_escape_string($this->frequency_decline)."',
				max_loan_amount_requested = '".mysql_escape_string($this->max_loan_amount_requested)."',
				min_loan_amount_requested = '".mysql_escape_string($this->min_loan_amount_requested)."',
				residence_length = '".mysql_escape_string($this->residence_length)."',
				employer_length = '".mysql_escape_string($this->employer_length)."',
				residence_type = '".mysql_escape_string($this->residence_type)."',
				income_source = '".mysql_escape_string($this->income_source)."',
				post_url = '".mysql_escape_string($this->post_url)."',
				minimum_recur_withheld_ssn = '".mysql_escape_string($this->minimum_recur_withheld_ssn)."',
				minimum_recur_withheld_email = '".mysql_escape_string($this->minimum_recur_withheld_email)."'  
			WHERE rule_id = '" . $this->get_rule_id() . "'
			LIMIT 1";
		$result = $this->sql->Query(MYSQL_DB, $query);
		return $result;

	}

	function insert()
	{

		$idv = (!is_null($this->datax_idv)) ? "'".$this->get_datax_idv()."'" : 'NULL';
		$vendor_qualify_post = (!is_null($this->vendor_qualify_post)) ? "'".$this->get_vendor_qualify_post()."'" : 'NULL';
		$query = "
			INSERT INTO rules
			(date_modified, date_created, target_id, weekends, list_mgmt_nosell, non_dates, bank_account_type,
				minimum_income, income_direct_deposit, excluded_states, excluded_zips, suppression_lists, 
               	restricted_states, income_frequency, force_promo_id, force_site_id, status, username,
				state_id_required, state_issued_id_required, minimum_recur, dd_check, minimum_age, 
				identical_phone_numbers, identical_work_cell_numbers, paydate_minimum, filter, income_type, 
				datax_idv, vendor_qualify_post, verify_post_type,withheld_targets,operating_hours, 
				required_references,reference_data,military, frequency_decline, max_loan_amount_requested,
				min_loan_amount_requested, residence_length, employer_length, residence_type,
				income_recur, pay_date_recur, post_url, minimum_recur_withheld_ssn, 
				minimum_recur_withheld_email, income_source)
			VALUES(
				sysdate(),
				sysdate(),
				'" . $this->get_target_id() . "',
				'" . $this->get_weekends() . "',
				'" . $this->get_list_mgmt_nosell() . "',			
				'" . $this->non_dates . "',
				'" . $this->get_bank_account_type() . "',
				'" . $this->get_minimum_income() . "',
				'" . $this->get_income_direct_deposit() . "',
				'" . $this->excluded_states . "',
				'" . $this->excluded_zips . "',
				'" . mysql_escape_string($this->suppression_lists) . "',
				'" . $this->restricted_states . "',
				'" . $this->income_frequency . "',
				'" . $this->force_promo_id . "',
				'" . $this->force_site_id . "',
				'" . $this->get_status() . "',
				'" . $this->get_username() . "',
				'" . $this->get_state_id_required() . "',
				'" . $this->get_state_issued_id_required() . "',
				'" . $this->get_minimum_recur() . "',
				'" . $this->get_dd_check() . "',
                " . ((int)$this->get_minimum_age()) . ",
				'" . $this->get_identical_phone_numbers() . "',
				'" . $this->get_identical_work_cell_numbers() . "',
                " . ((int)$this->get_paydate_minimum()) . ",
                '" . $this->filter . "',
				'" . $this->get_income_type() . "',
				{$idv},
				{$vendor_qualify_post},
				'" . $this->get_verify_post_type() . "',
				'" . $this->get_withheld_targets() . "',
				'" . $this->operating_hours . "',
				" . $this->get_required_references() . ",
				'" . mysql_escape_string($this->reference_data). "',
				'" . mysql_escape_string($this->military). "',
				'" . mysql_escape_string($this->frequency_decline). "',
				'" . mysql_escape_string($this->max_loan_amount_requested). "',
				'" . mysql_escape_string($this->min_loan_amount_requested). "',
				'" . mysql_escape_string($this->residence_length). "',
				'" . mysql_escape_string($this->employer_length). "',
				'" . mysql_escape_string($this->residence_type). "',
				'" . mysql_escape_string($this->income_recur) . "',
				'" . mysql_escape_string($this->pay_date_recur) . "',
				'" . mysql_escape_string($this->post_url) . "',
				'" . mysql_escape_string($this->minimum_recur_withheld_ssn) . "',
				'" . mysql_escape_string($this->minimum_recur_withheld_email) . "',
				'" . mysql_escape_string($this->income_source)."')";
		$result = $this->sql->Query(MYSQL_DB, $query);
//********************************************* 
// new fields
//*********************************************  
		if (!is_a($result, 'Error_2'))
			$this->set_rule_id($this->sql->Insert_Id());
		return $result;
	}

	function get_non_dates_string($delim)
	{
		return join($delim, $this->get_non_dates());
	}

	function get_excluded_states_string($delim)
	{
		return join($delim, $this->get_excluded_states());
	}

	function get_restricted_states_string($delim)
	{
		return join($delim, $this->get_restricted_states());
	}

	function get_income_frequency_string($delim)
	{
		return join($delim, $this->get_income_frequency());
	}

	function get_force_promo_id_string($delim)
	{
		return join($delim, $this->get_force_promo_id());
	}

	function get_force_site_id_string($delim)
	{
		return join($delim, $this->get_force_site_id());
	}

	function equals($other_rules)
	{

		$zips = $this->get_excluded_zips();
		$other_zips = $other_rules->get_excluded_zips();

		$lists = $this->get_suppression_lists();
		$other_lists = $other_rules->get_suppression_lists();

		sort($zips); sort($other_zips);
		asort($lists); asort($other_lists);

		$equals = TRUE;
		$equals = ($equals && ($this->get_target_id() == $other_rules->get_target_id()));
		$equals = ($equals && ($this->get_weekends() == $other_rules->get_weekends()));
		$equals = ($equals && ($this->get_list_mgmt_nosell() == $other_rules->get_list_mgmt_nosell()));		
		$equals = ($equals && ($this->get_non_dates() == $other_rules->get_non_dates()));
		$equals = ($equals && ($this->get_bank_account_type() == $other_rules->get_bank_account_type()));
		$equals = ($equals && ($this->get_minimum_income() == $other_rules->get_minimum_income()));
		$equals = ($equals && ($this->get_income_direct_deposit() == $other_rules->get_income_direct_deposit()));
		$equals = ($equals && ($this->get_excluded_states() == $other_rules->get_excluded_states()));
		$equals = ($equals && ($this->get_restricted_states() == $other_rules->get_restricted_states()));
		$equals = ($equals && ($this->get_income_frequency() == $other_rules->get_income_frequency()));
		$equals = ($equals && ($this->get_force_promo_id() == $other_rules->get_force_promo_id()));
		$equals = ($equals && ($this->get_force_site_id() == $other_rules->get_force_site_id()));
		$equals = ($equals && ($this->get_status() == $other_rules->get_status()));
		$equals = ($equals && ($this->get_state_id_required() == $other_rules->get_state_id_required()));
		$equals = ($equals && ($this->get_state_issued_id_required() == $other_rules->get_state_issued_id_required()));
		$equals = ($equals && ($this->get_minimum_recur() == $other_rules->get_minimum_recur()));
		$equals = ($equals && ($this->get_income_recur() == $other_rules->get_income_recur()));
		$equals = ($equals && ($this->get_pay_date_recur() == $other_rules->get_pay_date_recur()));
		$equals = ($equals && ($this->get_dd_check() == $other_rules->get_dd_check()));
        $equals = ($equals && ($this->get_minimum_age() == $other_rules->get_minimum_age()));
		$equals = ($equals && ($this->get_identical_phone_numbers() == $other_rules->get_identical_phone_numbers()));
		$equals = ($equals && ($this->get_identical_work_cell_numbers() == $other_rules->get_identical_work_cell_numbers()));
		$equals = ($equals && ($this->get_paydate_minimum() == $other_rules->get_paydate_minimum()));
        $equals = ($equals && ($this->get_filter() == $other_rules->get_filter()));
		$equals = ($equals && ($this->get_withheld_targets() == $other_rules->get_withheld_targets()));
		$equals = ($equals && ($this->get_income_type() == $other_rules->get_income_type()));
		$equals = ($equals && ($this->get_datax_idv() == $other_rules->get_datax_idv()));
		$equals = ($equals && ($this->get_vendor_qualify_post() == $other_rules->get_vendor_qualify_post()));
		$equals = ($equals && ($this->get_verify_post_type() == $other_rules->get_verify_post_type()));
		$equals = ($equals && ($this->get_operating_hours() == $other_rules->get_operating_hours()));
		$equals = ($equals && ($this->get_required_references() == $other_rules->get_required_references()));
		$equals = ($equals && ($this->get_reference_data() == $other_rules->get_reference_data()));
		$equals = ($equals && ($this->get_military() == $other_rules->get_military()));
		$equals = ($equals && ($this->get_post_url() == $other_rules->get_post_url()));
		$equals = ($equals && ($this->get_frequency_decline() == $other_rules->get_frequency_decline()));
		$equals = ($equals && (md5(serialize($zips)) == md5(serialize($other_zips))));
		$equals = ($equals && (md5(serialize($lists)) == md5(serialize($other_lists))));
		$equals = ($equals && ($this->get_max_loan_amount_requested() == $other_rules->get_max_loan_amount_requested()));
		$equals = ($equals && ($this->get_min_loan_amount_requested() == $other_rules->get_min_loan_amount_requested()));
		$equals = ($equals && ($this->get_residence_length() == $other_rules->get_residence_length()));
		$equals = ($equals && ($this->get_employer_length() == $other_rules->get_employer_length()));
		$equals = ($equals && ($this->get_residence_type() == $other_rules->get_residence_type()));
		$equals = ($equals && ($this->get_minimum_recur_withheld_ssn() == $other_rules->get_minimum_recur_withheld_ssn()));
		$equals = ($equals && ($this->get_minimum_recur_withheld_email() == $other_rules->get_minimum_recur_withheld_email()));
		$equals = ($equals && ($this->get_income_source() == $other_rules->get_income_source()));

		return (boolean)$equals;

	}

	function is_income_frequency_monthly()
	{
		return (in_array('MONTHLY', $this->get_income_frequency()));
	}

	function is_income_frequency_twice_monthly()
	{
		return (in_array('TWICE_MONTHLY', $this->get_income_frequency()));
	}

	function is_income_frequency_weekly()
	{
		return (in_array('WEEKLY', $this->get_income_frequency()));
	}

	function is_income_frequency_bi_weekly()
	{
		return (in_array('BI_WEEKLY', $this->get_income_frequency()));
	}

	function is_income_frequency_four_weekly()
	{
		return (in_array('FOUR_WEEKLY', $this->get_income_frequency()));
	}

	// Getter and Setter methods
	
	function set_minimum_recur_withheld_ssn($minimum_recur_withheld_ssn)
	{
		$this->minimum_recur_withheld_ssn = $minimum_recur_withheld_ssn;
	}

	function get_minimum_recur_withheld_ssn()
	{
		return $this->minimum_recur_withheld_ssn;
	}

	function set_minimum_recur_withheld_email($minimum_recur_withheld_email)
	{
		$this->minimum_recur_withheld_email = $minimum_recur_withheld_email;
	}

	function get_minimum_recur_withheld_email()
	{
		return $this->minimum_recur_withheld_email;
	}

	function set_date_modified($date_modified)
	{
		$this->date_modified = $date_modified;
	}

	function get_date_modified()
	{
		return $this->date_modified;
	}

	function set_date_created($date_created)
	{
		$this->date_created = $date_created;
	}

	function get_date_created()
	{
		return $this->date_created;
	}

	function set_rule_id($rule_id)
	{
		$this->rule_id = $rule_id;
	}

	function get_rule_id()
	{
		return $this->rule_id;
	}

	function set_target_id($target_id)
	{
		$this->target_id = $target_id;
	}

	function get_target_id()
	{
		return $this->target_id;
	}

	function set_weekends($weekends)
	{
		$this->weekends = $weekends;
	}

	function get_weekends()
	{
		return $this->weekends;
	}
	
	function set_list_mgmt_nosell($list_mgmt_nosell)
	{
		$this->list_mgmt_nosell = $list_mgmt_nosell;
	}

	function get_list_mgmt_nosell()
	{
		return $this->list_mgmt_nosell;
	}

	function set_non_dates($non_dates)
	{
		if (is_array($non_dates))
			$this->non_dates = serialize($non_dates);
		else
			$this->non_dates = serialize(Array());
	}

	function get_non_dates()
	{
		if (!$this->non_dates)
			$this->set_non_dates(NULL);

		return unserialize($this->non_dates);
	}

	function set_bank_account_type($bank_account_type)
	{
		$this->bank_account_type = $bank_account_type;
	}

	function get_bank_account_type()
	{
		return $this->bank_account_type;
	}

	function set_minimum_income($minimum_income)
	{
		$this->minimum_income = $minimum_income;
	}

	function get_minimum_income()
	{
		return $this->minimum_income;
	}

	function set_income_direct_deposit($income_direct_deposit)
	{
		$this->income_direct_deposit = $income_direct_deposit;
	}

	function get_income_direct_deposit()
	{
		return $this->income_direct_deposit;
	}

	function set_excluded_states($excluded_states)
	{
		if (is_array($excluded_states))
		{
			for ($count = 0; $count < sizeof($this->excluded_states); $count++)
				$this->excluded_states[$count] = strtoupper($this->excluded_states[$count]);
			$this->excluded_states = serialize($excluded_states);
		}
		else
			$this->excluded_states = serialize(Array());
	}

	function get_excluded_states()
	{
		if (!$this->excluded_states)
			$this->set_excluded_states(NULL);
		return unserialize($this->excluded_states);
	}

	function set_excluded_zips($zips)
	{

		if (!is_array($zips))
		{
			$zips = array();
		}
		else
		{
			sort($zips);
		}

		$this->excluded_zips = serialize($zips);

	}

	function set_suppression_lists($lists)
	{

		if (!is_array($lists))
		{
			$lists = array();
		}

		$this->suppression_lists = serialize($lists);

	}

	function get_excluded_zips()
	{

		if (!$this->excluded_zips)
		{
			$this->set_excluded_zips(NULL);
		}

		$zips = unserialize($this->excluded_zips);
		return($zips);

	}

	function get_suppression_lists()
	{

		if (!$this->suppression_lists)
		{
			$this->set_suppression_lists(NULL);
		}

		$lists = unserialize($this->suppression_lists);
		return($lists);

	}

	function set_restricted_states($restricted_states)
	{
		if (is_array($restricted_states))
		{
			for ($count = 0; $count < sizeof($this->restricted_states); $count++)
				$this->restricted_states[$count] = strtoupper($this->restricted_states[$count]);
			$this->restricted_states = serialize($restricted_states);
		}
		else
			$this->restricted_states = serialize(Array());
	}

	function get_restricted_states()
	{
		if (!$this->restricted_states)
			$this->set_restricted_states(NULL);
		return unserialize($this->restricted_states);
	}

	function set_income_frequency($income_frequency)
	{
		if (is_array($income_frequency))
			$this->income_frequency = serialize($income_frequency);
		else
			$this->income_frequency = serialize(Array());
	}

	function get_income_frequency()
	{
		if (!$this->income_frequency)
			$this->set_income_frequency(NULL);
		return unserialize($this->income_frequency);
	}

	function set_force_promo_id($force_promo_id)
	{
		if (is_array($force_promo_id))
			$this->force_promo_id = serialize($force_promo_id);
		else
			$this->force_promo_id = serialize(Array());
	}

	function get_force_promo_id()
	{
		if (!$this->force_promo_id)
			$this->set_force_promo_id(NULL);
		return unserialize($this->force_promo_id);
	}

	function set_force_site_id($force_site_id)
	{
		if (is_array($force_site_id))
			$this->force_site_id = serialize($force_site_id);
		else
			$this->force_site_id = serialize(Array());
	}

	function get_force_site_id()
	{
		if (!$this->force_site_id)
			$this->set_force_site_id(NULL);
		return unserialize($this->force_site_id);
	}

	function set_status($status)
	{
		$this->status = $status;
	}

	function get_status()
	{
		return $this->status;
	}

	function set_username($username)
	{
		$this->username = $username;
	}

	function get_username()
	{
		return $this->username;
	}

	function set_minimum_recur($minimum_recur)
	{
		$this->minimum_recur = $minimum_recur;
	}

	function get_minimum_recur()
	{
		return $this->minimum_recur;
	}
	
	function set_income_recur($recur)
	{
		$this->income_recur = (is_array($recur)) ? serialize($recur) : serialize(array());
	}

	function get_income_recur()
	{
		return (!empty($this->income_recur)) ? @unserialize($this->income_recur) : array();
	}
	
	function set_pay_date_recur($recur)
	{
		$this->pay_date_recur = (is_array($recur)) ? serialize($recur) : serialize(array());
	}

	function get_pay_date_recur()
	{
		return (!empty($this->pay_date_recur)) ? @unserialize($this->pay_date_recur) : array();
	}

    function set_minimum_age($minimum_age)
    {
        $this->minimum_age = $minimum_age;
    }

    function get_minimum_age()
    {
        return $this->minimum_age;
    }

	function set_identical_phone_numbers($identical_phone_numbers)
	{
		$this->identical_phone_numbers = $identical_phone_numbers;
	}

	function get_identical_phone_numbers()
	{
		return $this->identical_phone_numbers;
	}

	function set_identical_work_cell_numbers($identical_work_cell_numbers)
	{
		$this->identical_work_cell_numbers = $identical_work_cell_numbers;
	}

	function get_identical_work_cell_numbers()
	{
		return $this->identical_work_cell_numbers;
	}
	
	function set_paydate_minimum($paydate_minimum)
	{
		$this->paydate_minimum = $paydate_minimum;
	}

	function get_paydate_minimum()
	{
		return (int) $this->paydate_minimum;
	}

	function set_withheld_targets($withheld_targets)
	{
		$this->withheld_targets = $withheld_targets;
	}

	function get_withheld_targets()
	{
		return $this->withheld_targets;
	}

	function set_filter($filter)
	{
		$this->filter = (is_array($filter)) ? serialize($filter) : serialize(array());
	}

	function get_filter()
	{
		return (!empty($this->filter)) ? @unserialize($this->filter) : array();
	}

	function has_filter($name)
	{
		return in_array($name, $this->get_filter());
	}

	function set_state_id_required($state_id_required)
	{
		$this->state_id_required = $state_id_required;
	}

	function get_state_id_required()
	{
		return $this->state_id_required;
	}

	function set_state_issued_id_required($state_issued_id_required)
	{
		$this->state_issued_id_required = $state_issued_id_required;
	}

	function get_state_issued_id_required()
	{
		return $this->state_issued_id_required;
	}
	function set_income_source($income_sources)
	{
		if (is_array($income_sources))
		{
			$this->income_source = serialize($income_sources);
		}
		elseif (is_string($income_sources) && !empty($income_sources))
		{
			$s = unserialize($income_sources); // Mkae sure it's valid
			if (is_array($s))
			{
				$this->income_source = $income_sources;
			}
			else
			{
				$this->income_sources = serialize(Array());
			}
		}
		else
		{
			$this->income_sources = serialize(Array());
		}
	}
	function set_income_type($income_type)
	{
		$this->income_type = $income_type;
	}

	function get_income_type()
	{
		return $this->income_type;
	}
	function get_income_source()
	{
		if (!$this->income_source)
		{
			$this->set_income_source(NULL);
		}
		$u = unserialize($this->income_source);
		return is_array($u) ? $u : Array();
	}
	function set_sql($sql)
	{
		$this->sql = $sql;
	}

	function get_sql()
	{
		return $this->sql;
	}

	function get_datax_idv()
	{
		return($this->datax_idv);
	}

	function set_datax_idv($idv)
	{
		if ($idv === '') $idv = NULL;
		$this->datax_idv = $idv;
	}

	function get_vendor_qualify_post()
	{
		return($this->vendor_qualify_post);
	}

	function set_vendor_qualify_post($vendor_qualify_post)
	{
		if ($vendor_qualify_post != 'TRUE') $vendor_qualify_post = 'FALSE';
		$this->vendor_qualify_post = $vendor_qualify_post;
	}

	// verify_post_type defines the method in which we send
	// vendor_qualify_post data
	function get_verify_post_type()
	{
		return($this->verify_post_type);
	}

	function set_verify_post_type($verify_post_type)
	{
		if ($verify_post_type!="XML" && $verify_post_type!="HTTP")
		{
			$verify_post_type="XML"; // default value
		}
		$this->verify_post_type = $verify_post_type;
	}


	function set_operating_hours($operating_hours)
	{
		if (is_array($operating_hours))
			$this->operating_hours = serialize($operating_hours);
		else
			$this->operating_hours = serialize(Array());

	}


	function get_operating_hours()
	{
		if (!$this->operating_hours)
			$this->set_operating_hours(NULL);
		return unserialize($this->operating_hours);

	}


	function get_dd_check()
	{
		return $this->dd_check;
	}

	function set_dd_check($dd_check)
	{
		$this->dd_check = $dd_check;
	}

	function get_required_references()
	{
		return $this->required_references;
	}
	function set_required_references($r)
	{
		$this->required_references = (int)$r;
	}
	function set_reference_data($reference_data)
	{
			$this->reference_data = serialize($reference_data);
	}
	function get_reference_data()
	{
		return unserialize($this->reference_data);
		
	}
	function set_military($military)
	{
		$this->military = $military;
	}
	function get_military()
	{
		return $this->military;
	}
	
	function set_post_url($post_url)
	{
		$this->post_url = $post_url;
	}
	function get_post_url()
	{
		return $this->post_url;
	}
	function set_frequency_decline($frequency_decline)
	{
		if (is_array($frequency_decline))
			$this->frequency_decline = serialize($frequency_decline);
		else
			$this->frequency_decline = serialize(Array());
	}
	function get_frequency_decline()
	{
		if (!$this->frequency_decline)
			$this->set_frequency_decline(NULL);
		return unserialize($this->frequency_decline);
		
	}

    function set_max_loan_amount_requested($max_loan_amount_requested)
    {    
        $this->max_loan_amount_requested = $max_loan_amount_requested;
    }    

    function get_max_loan_amount_requested()
    {    
        return $this->max_loan_amount_requested;
    }    

    function set_min_loan_amount_requested($min_loan_amount_requested)
    {    
        $this->min_loan_amount_requested = $min_loan_amount_requested;
    }    

    function get_min_loan_amount_requested()
    {    
        return $this->min_loan_amount_requested;
    }    

    function set_residence_length($residence_length)
    {    
        $this->residence_length = $residence_length;
    }    

    function get_residence_length()
    {    
        return $this->residence_length;
    }    

    function set_employer_length($employer_length)
    {    
        $this->employer_length = $employer_length;
    }    

    function get_employer_length()
    {    
        return $this->employer_length;
    }    

    function set_residence_type($residence_type)
    {    
        $this->residence_type = $residence_type;
    }    

    function get_residence_type()
    {    
        return $this->residence_type;
    }    

}
